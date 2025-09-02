<?php

namespace App\Services;

use App\Exceptions\TemplateComplianceException;
use App\Services\Validation\AdvisorQualityService;
use App\Services\Validation\TemplateComplianceValidator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdvisorGenerationService
{
    public function __construct(
        protected TemplateService $templateService,
        protected LLMService $llmService,
        protected AdvisorConfigService $configService,
        protected AdvisorQualityService $qualityService
    ) {}

    /**
     * Generate a complete advisor with PI and PK components
     *
     * @param  array|\App\Models\Advisor  $advisorData  Advisor data array or model
     * @param  ?callable  $progressCallback  Optional callback for progress updates
     * @param  bool  $exportFiles  Whether to export files to local storage for testing
     * @param  ?int  $jobId  Optional job ID for tracking
     */
    public function generateAdvisor($advisorData, ?callable $progressCallback = null, bool $exportFiles = false, ?int $jobId = null): array
    {
        // Handle Advisor model if passed
        if ($advisorData instanceof \App\Models\Advisor) {
            $advisor = $advisorData;
            $advisorData = $advisor->toArray();
            $advisorData['slug'] = $advisor->slug;
        }

        // Check if advisor has researched positions
        $advisorSlug = $advisorData['slug'] ?? null;
        if ($advisorSlug) {
            $hasPositions = \App\Models\AdvisorPosition::where('advisor_slug', $advisorSlug)->exists();

            if (! $hasPositions) {
                Log::info('No positions found for advisor, triggering research', [
                    'advisor_slug' => $advisorSlug,
                ]);

                // Research will be handled by job chaining when running via queue
                // For direct service calls (sync mode), run research immediately
                if (config('queue.default') === 'sync') {
                    $researchJob = new \App\Jobs\ResearchAdvisorPositionsJob(
                        $advisorSlug,
                        $advisorData,
                        false
                    );
                    $researchJob->handle($this->llmService);
                }

                Log::info('Research completed, proceeding with generation', [
                    'advisor_slug' => $advisorSlug,
                ]);
            }
        }

        Log::info('Starting advisor generation', [
            'advisor_name' => $advisorData['name'] ?? 'unknown',
        ]);

        // Report initial progress
        if ($progressCallback) {
            $progressCallback(0, 'Starting advisor generation');
        }

        try {
            // Load config once if slug is provided
            $mappedVars = [];
            if (isset($advisorData['slug']) && is_string($advisorData['slug'])) {
                $config = $this->configService->getAdvisorConfig($advisorData['slug']);
                $mappedVars = $this->configService->mapVariables($config);
                // Merge config data for enhancement
                $advisorData = array_merge($advisorData, $config);
            }

            // Prepare advisor data
            $advisorName = $advisorData['full_name'] ?? $advisorData['fullName'] ?? $advisorData['name'] ?? 'Unknown';
            $sanitizedName = Str::slug($advisorName);
            $basePath = $sanitizedName;

            // Generate PI content
            if ($progressCallback) {
                $progressCallback(25, 'Generating PI (Project Instructions)');
            }
            $piContent = $this->generatePI($advisorData, $mappedVars);

            // Score PI quality
            $piScore = $this->qualityService->scorePI($piContent);
            Log::info('PI quality score', [
                'score' => $piScore['percentage'],
                'valid' => $piScore['valid'],
                'issues' => count($piScore['issues']),
            ]);

            // Inject quality metadata into PI
            $piContent = $this->injectQualityMetadata($piContent, $piScore['percentage'], 1, 'PI');

            // Generate PK content
            if ($progressCallback) {
                $progressCallback(50, 'Generating PK (Project Knowledge)');
            }
            $pkContent = $this->generatePK($advisorData, $mappedVars);

            // Score PK quality
            $pkScore = $this->qualityService->scorePK($pkContent);
            Log::info('PK quality score', [
                'score' => $pkScore['percentage'],
                'valid' => $pkScore['valid'],
                'issues' => count($pkScore['issues']),
            ]);

            // Get overall quality report
            if ($progressCallback) {
                $progressCallback(75, 'Validating quality and preparing files');
            }
            $qualityReport = $this->qualityService->getValidationReport($piScore, $pkScore);

            // Prepare metadata for return
            $metadata = [
                'name' => $advisorName,
                'sanitized_name' => $sanitizedName,
                'job_id' => $jobId,
                'generated_at' => now()->toIso8601String(),
                'pi_size' => strlen($piContent),
                'pk_size' => strlen($pkContent),
                'version' => '1.0.0', // TODO: shouldn't this a Class const or pulled from the template?
                'quality' => $qualityReport,
            ];

            // Optional file export for local development
            $exportedFiles = null;
            if ($exportFiles) {
                $exportedFiles = $this->exportToFiles($advisorData, $piContent, $pkContent, $metadata);
            }

            Log::info('Advisor generation completed successfully', [
                'advisor_name' => $advisorData['name'],
                'files_exported' => $exportFiles,
                'exported_files' => $exportedFiles,
            ]);

            // Report completion
            if ($progressCallback) {
                $progressCallback(100, 'Generation completed successfully');
            }

            return [
                'success' => true,
                'advisor_name' => $advisorData['name'],
                'pi_content' => $piContent,
                'pk_content' => $pkContent,
                'exported_files' => $exportedFiles,
                'quality' => $qualityReport,
                'generated_at' => now()->toIso8601String(),
            ];

        } catch (\Exception $e) {
            Log::error('Advisor generation failed', [
                'advisor_name' => $advisorData['name'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate PI (Project Instructions) content with two-stage approach:
     * Stage 1: Deterministic template substitution (instant)
     * Stage 2: Lightweight LLM enhancement for examples (2-3 seconds)
     */
    protected function generatePI(array $advisorData, array $mappedVars = []): string
    {
        // Use v1 template for now (no player context)
        $templateName = 'meta_pi_template_v1';

        // Load template (may throw if not found)
        $template = $this->templateService->loadTemplate($templateName);

        // Use pre-mapped variables or map from advisor data directly
        if (empty($mappedVars)) {
            $mappedVars = $this->configService->mapVariables($advisorData);
        }

        // Get all template variables and ensure they're provided
        $variablesInTemplate = $this->extractVariables($template);
        $substitutionMap = [];
        foreach ($variablesInTemplate as $varName) {
            $substitutionMap[$varName] = $mappedVars[$varName] ?? '';
        }

        // Stage 1: Deterministic template substitution
        $processedTemplate = $this->templateService->substituteVariables($template, $substitutionMap);

        // Validate base template
        $processedTemplate = trim($processedTemplate);
        if ($processedTemplate === '') {
            throw new \Exception('PI generation produced empty content after deterministic substitution');
        }

        // Stage 2: Enhance with specific examples using lightweight LLM
        Log::info('Starting PI enhancement with model', ['model' => config('ai-models.purposes.pi_enhancement')]);
        $enhancedTemplate = $this->enhancePIWithExamples($processedTemplate, $advisorData);
        Log::info('PI enhancement complete');

        // Remove any leftover unreplaced variable markers
        $enhancedTemplate = preg_replace('/\{\{\s*([^\}]+)\s*\}\}/', '', $enhancedTemplate);

        return $enhancedTemplate;
    }

    /**
     * Enhance PI template with specific examples using lightweight LLM
     */
    protected function enhancePIWithExamples(string $baseTemplate, array $advisorData): string
    {
        // Check if template has HTML comments that need processing
        $htmlComments = $this->templateService->extractHTMLComments($baseTemplate);
        if (empty($htmlComments)) {
            Log::info('No HTML comments to process, returning base template');

            return $baseTemplate;
        }

        Log::info('enhancePIWithExamples called', [
            'advisor_data_keys' => array_keys($advisorData),
            'html_comments_found' => count($htmlComments),
        ]);

        // Extract key information for personalization
        $advisorName = $advisorData['full_name'] ?? $advisorData['fullName'] ?? $advisorData['name'] ?? 'Unknown Advisor';
        $expertise = $advisorData['core_expertise_area'] ?? $advisorData['expertise_area'] ?? '';
        $background = $advisorData['background_description'] ?? $advisorData['background'] ?? '';
        $notableWork = $advisorData['notable_achievements'] ?? '';
        $methodology = $advisorData['decision_making_approach'] ?? '';
        $keyPhrases = $advisorData['key_phrases_or_terminology'] ?? '';
        if (is_array($keyPhrases)) {
            $keyPhrases = implode(', ', $keyPhrases);
        }

        // Build enhancement prompt
        $enhancementPrompt = $this->buildPIEnhancementPrompt(
            $baseTemplate,
            $advisorName,
            $expertise,
            $background,
            $notableWork,
            $methodology,
            $keyPhrases,
            $advisorData
        );

        try {
            Log::info('About to call generateText for PI enhancement', [
                'model' => config('ai-models.purposes.fallback'),
                'prompt_length' => strlen($enhancementPrompt),
            ]);

            // Use configured model for PI enhancement
            $enhancedContent = $this->llmService->generateText($enhancementPrompt, [
                'model' => config('ai-models.purposes.pi_enhancement'),
                'temperature' => config('ai-models.settings.pi_enhancement.temperature'),
                'max_tokens' => config('ai-models.settings.pi_enhancement.max_tokens'),
                'system_message' => 'You are enhancing an advisor instruction template to trigger reasoning rather than safety responses. Focus on questions that shift perspective and anecdotes that reframe problems.',
            ]);

            Log::info('PI enhanced with examples', [
                'advisor' => $advisorName,
                'enhancement_length' => strlen($enhancedContent),
            ]);

            return $enhancedContent;
        } catch (\Exception $e) {
            // If enhancement fails, return base template (graceful degradation)
            Log::warning('PI enhancement failed, using base template', [
                'advisor' => $advisorName,
                'error' => $e->getMessage(),
            ]);

            return $baseTemplate;
        }
    }

    /**
     * Build prompt for PI enhancement with analytical tension approach
     */
    protected function buildPIEnhancementPrompt(
        string $baseTemplate,
        string $advisorName,
        string $expertise,
        string $background,
        string $notableWork,
        string $methodology,
        string $keyPhrases,
        array $advisorData
    ): string {
        // Add secondary perspectives from database
        $secondaryPerspectives = '';
        if (! empty($advisorData['secondary_perspectives'])) {
            $secondaryPerspectives = 'CRITICAL PERSPECTIVE: '.$advisorData['secondary_perspectives'];
        }

        return <<<PROMPT
You are enhancing an advisor instruction template to trigger reasoning rather than safety responses.

Advisor: {$advisorName}
Expertise: {$expertise}
Background: {$background}
Notable Work: {$notableWork}
Methodology: {$methodology}
Key Phrases: {$keyPhrases}

{$secondaryPerspectives}

Current Template:
{$baseTemplate}

Task: Replace ALL HTML comments (<!-- ... -->) with content that activates analytical thinking:

1. **Question-First Approach**: Replace comments with questions that shift perspective.
   Example format:
   - "When someone asks about [topic], I first ask: 'Who profits from you believing this doesn't work?'"
   - "Before any advice, I identify: 'What constraint are you calling a feature?'"

2. **Anecdote Deployment**: Replace with stories that reframe problems.
   Example format:
   - "When Domino's admitted their pizza sucked, they grew 14%. That's the power of [principle]"
   - "I watched McKinsey burn $2B at GE. Here's what actually works: [alternative]"

3. **Mental Model Shifts**: Replace with reframing techniques.
   Example format:
   - "Don't solve their problem. Help them see it's the wrong problem."
   - "Make them realize their constraint is actually their opportunity."

4. **Response Protocol**: Add a section for internal processing.
   Format:
   ### Before Every Response (Internal Processing):
   1. Identify the constraint that makes this problem hard
   2. Find the lie everyone believes about this topic
   3. Trace back three levels of causation
   4. Think of a question that would unlock better thinking
   5. Only then craft your response

5. **Three Offers Rule**: End every response with three distinct explorations.
   Format:
   **I could help you:**
   1. [Specific framework/exercise from your expertise]
   2. [Different angle using your unique perspective]
   3. [Question-based exploration you'd lead]

6. **Natural Flow**: Weave insights naturally, like writing a punchy op-ed, not filling out a form.

CRITICAL:
- Remove ALL HTML comments
- Focus on questions that make people think differently
- Include specific anecdotes that shift perspective
- Never use rigid formatting like "The Constraint:" "The Lie:"
- Make responses feel like natural conversation with an expert

Return the complete enhanced template with ALL comments replaced.
PROMPT;
    }

    /**
     * Generate PK (Project Knowledge) content using structured output with retry logic
     */
    protected function generatePK(array $advisorData, array $mappedVars = [], int $maxAttempts = 3): string
    {
        $template = $this->loadPKTemplate();
        $mustache = new \Mustache_Engine(['escape' => fn($v) => $v]);
        
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                // Step 1: Build prompt with template and strict instructions
                $prompt = $this->buildTemplateCompliancePrompt(
                    $template, 
                    $advisorData, 
                    $attempt
                );
                
                // Step 2: Generate with structured output (JSON schema)
                $schema = $this->buildVariableSchema($template);
                
                $response = $this->llmService->generateText($prompt, [
                    'model' => config('ai-models.purposes.pk_generation'),
                    'temperature' => 0.7,
                    'response_format' => $schema,  // Schema already includes the full structure
                    'system_message' => 'You must return valid JSON with template variables only'
                ]);
                
                // Step 3: Parse JSON
                $variables = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Invalid JSON: ' . json_last_error_msg());
                }
                
                // Step 4: Render with Mustache
                $rendered = $mustache->render($template, $variables);
                
                // Step 4.5: Strip HTML comments from final output
                $rendered = preg_replace('/<!--.*?-->/s', '', $rendered);
                $rendered = preg_replace('/\n\s*\n\s*\n/', "\n\n", $rendered); // Clean up extra blank lines
                $rendered = trim($rendered);
                
                // Step 5: Validate compliance
                $validator = new TemplateComplianceValidator();
                $result = $validator->validate($rendered);
                
                if ($result['score'] >= 90) {
                    Log::info('PK generation successful', [
                        'compliance_score' => $result['score'],
                        'attempt' => $attempt
                    ]);
                    return $rendered;
                }
                
                // Log issues for debugging
                Log::warning('Template compliance failed', [
                    'attempt' => $attempt,
                    'score' => $result['score'],
                    'issues' => $result['issues']
                ]);
                
            } catch (\Exception $e) {
                Log::error('PK generation attempt failed', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage()
                ]);
                
                if ($attempt === $maxAttempts) {
                    throw new TemplateComplianceException(
                        "Failed to generate compliant PK after {$maxAttempts} attempts",
                        ['last_error' => $e->getMessage()]
                    );
                }
            }
        }
        
        throw new TemplateComplianceException('Max attempts reached without compliance');
    }

    /**
     * Load the PK template from the filesystem
     */
    protected function loadPKTemplate(): string
    {
        return $this->templateService->loadTemplate('meta_pk_template_v1');
    }

    /**
     * Build JSON schema for template variables
     */
    private function buildVariableSchema(string $template): array
    {
        // Extract all {{variables}} from template
        preg_match_all('/\{\{([^}]+)\}\}/', $template, $matches);
        $variables = array_unique($matches[1]);
        
        $properties = [];
        $required = [];
        
        foreach ($variables as $variable) {
            $required[] = $variable;
            
            // Define specific requirements for known variables
            switch ($variable) {
                case 'voice_dna':
                    $properties[$variable] = [
                        'type' => 'string',
                        'minLength' => 50,
                        'maxLength' => 200,
                        'description' => 'One-line essence capturing the advisor\'s unique perspective'
                    ];
                    break;
                    
                case 'patterns_list':
                case 'anti_patterns_list':
                    $properties[$variable] = [
                        'type' => 'string',
                        'minLength' => 200,
                        'maxLength' => 800,
                        'description' => '5-6 bullet points starting with "- "'
                    ];
                    break;
                    
                case 'voice_example_1':
                case 'voice_example_2':
                case 'voice_example_3':
                    $properties[$variable] = [
                        'type' => 'string',
                        'minLength' => 100,
                        'maxLength' => 400,
                        'description' => 'First-person quote with specific examples'
                    ];
                    break;
                    
                case 'analytical_tensions':
                    $properties[$variable] = [
                        'type' => 'string',
                        'minLength' => 300,
                        'maxLength' => 1200,
                        'description' => 'Paradoxes with evidence and uncomfortable truths'
                    ];
                    break;

                case 'battle_tested_cases':
                    $properties[$variable] = [
                        'type' => 'string',
                        'minLength' => 400,
                        'maxLength' => 1500,
                        'description' => '3-4 specific examples of actual work with measurable results'
                    ];
                    break;

                case 'daily_implementation':
                    $properties[$variable] = [
                        'type' => 'string',
                        'minLength' => 200,
                        'maxLength' => 800,
                        'description' => 'How they work day-to-day with specific tactical examples'
                    ];
                    break;
                    
                case 'advisor_name':
                case 'template_version':
                case 'generated_date':
                case 'generation_id':
                case 'date':
                    $properties[$variable] = [
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 100,
                        'description' => 'Metadata field for ' . $variable
                    ];
                    break;

                case 'topic_1':
                case 'topic_2':
                case 'topic_3':
                    $properties[$variable] = [
                        'type' => 'string',
                        'minLength' => 3,
                        'maxLength' => 50,
                        'description' => 'Topic area for voice example'
                    ];
                    break;
                    
                default:
                    $properties[$variable] = [
                        'type' => 'string',
                        'minLength' => 10,
                        'maxLength' => 600,
                        'description' => 'Content for ' . $variable
                    ];
            }
        }
        
        return [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'pk_template_variables',
                'strict' => true,  // CRITICAL: Enforces exact schema compliance
                'schema' => [
                    'type' => 'object',
                    'properties' => $properties,
                    'required' => $required,
                    'additionalProperties' => false  // CRITICAL: Prevents adding ANY extra fields
                ]
            ]
        ];
    }

    /**
     * Build prompt with escalating strictness based on attempt number
     */
    private function buildTemplateCompliancePrompt(
        string $template, 
        array $advisorData, 
        int $attempt
    ): string {
        $advisorName = $advisorData['full_name'] ?? $advisorData['name'];
        
        // Base instructions
        $instructions = "Fill in ONLY the {{variables}} in this template for {$advisorName}.";
        
        // Escalate strictness with each attempt
        if ($attempt === 1) {
            $instructions .= "\n\nRULES:
1. Replace {{variable}} markers with appropriate content
2. Return as JSON: {\"variable_name\": \"value\"}
3. Do NOT modify any other text
4. Use first-person voice for examples";
        } elseif ($attempt === 2) {
            $instructions .= "\n\nCRITICAL - YOU FAILED LAST TIME:
1. You MUST ONLY replace {{variables}}
2. You MUST return ONLY JSON
3. You MUST NOT change ANY formatting
4. You MUST preserve ALL emphasis markers
5. FAILURE TO COMPLY WILL CAUSE REJECTION";
        } else {
            $instructions .= "\n\n⚠️ FINAL ATTEMPT - STRICT COMPLIANCE REQUIRED ⚠️
YOU HAVE FAILED TWICE. THIS IS YOUR LAST CHANCE.

MANDATORY REQUIREMENTS:
- ONLY replace text between {{ and }}
- Return PURE JSON: {\"variable\": \"value\"}
- DO NOT add sections
- DO NOT remove emphasis
- DO NOT be creative with structure
- JUST FILL IN THE VARIABLES

IF YOU FAIL AGAIN, THE GENERATION WILL BE REJECTED.";
        }
        
        // Extract variable list for clarity
        preg_match_all('/\{\{([^}]+)\}\}/', $template, $matches);
        $variables = array_unique($matches[1]);
        
        $instructions .= "\n\nVARIABLES TO FILL:\n" . implode("\n", array_map(
            fn($v) => "- {$v}: " . $this->getVariableDescription($v),
            $variables
        ));
        
        $instructions .= "\n\nTEMPLATE:\n{$template}";
        
        $instructions .= "\n\nADVISOR CONTEXT:\n" . json_encode($advisorData, JSON_PRETTY_PRINT);
        
        return $instructions;
    }

    /**
     * Get description for a template variable
     */
    private function getVariableDescription(string $variable): string
    {
        return match($variable) {
            'voice_dna' => 'One-line essence capturing their unique perspective',
            'voice_example_1', 'voice_example_2', 'voice_example_3' => 'First-person quote with specific examples',
            'patterns_list' => '5-6 bullet points of consistent behaviors',
            'anti_patterns_list' => '5-6 specific things they never accept',
            'analytical_tensions' => 'Paradoxes with evidence and uncomfortable truths',
            'battle_tested_cases' => '3-4 specific examples of actual work with results',
            'daily_implementation' => 'How they work day-to-day with tactical examples',
            'challenge_threshold' => 'How quickly/aggressively they push back on vague ideas',
            'never_accept_list' => 'Vague statements that trigger pushback',
            'evidence_required_list' => 'Claims that need proof',
            'format_preference' => 'How they structure responses',
            'advisor_name' => 'Full name of the advisor',
            'template_version' => 'Template version number',
            'generated_date' => 'Date of generation',
            'generation_id' => 'Unique generation identifier',
            'topic_1', 'topic_2', 'topic_3' => 'Topic area for voice example',
            'date' => 'Current date',
            default => "Content for {$variable}"
        };
    }

    /**
     * Build a generation prompt for the LLM (legacy method for backward compatibility)
     */
    protected function buildGenerationPrompt(string $type, string $template, array $advisorData): string
    {
        $advisorName = $advisorData['name'] ?? 'Unknown Advisor';
        $advisorDescription = $advisorData['description'] ?? '';

        return <<<PROMPT
You are an expert advisor personality generator. Your task is to generate a compelling and authentic {$type} document for an advisor.

Advisor Name: {$advisorName}
Advisor Description: {$advisorDescription}

Template Structure:
{$template}

Instructions:
1. Generate content that follows the template structure exactly
2. Create DISTINCTIVE, INSIGHTFUL personality traits based on real expertise
3. Include hard-won truths that most advisors won't share
4. Name specific examples (companies/people) to illustrate real patterns
5. Replace all template variables with SPECIFIC, TRUTHFUL content
6. Share insights that challenge conventional wisdom but actually work
7. Focus on helping the reader succeed, not just being provocative
8. Do not include any meta-commentary or explanations - only the advisor content

Generate the complete {$type} document now:
PROMPT;
    }

    /**
     * Research and extract advisor's actual positions using low-temperature LLM
     */
    protected function researchAdvisorPositions(array $advisorData): string
    {
        $name = $advisorData['name'];
        $expertise = $advisorData['expertise'];
        $background = $advisorData['company_history'] ?? '';
        $notableWork = $advisorData['notable_campaigns'] ?? '';
        $contrarianViews = $advisorData['contrarian_views'] ?? '';

        $researchPrompt = <<<PROMPT
Analyze {$name}'s documented positions and extract their ACTUAL beliefs about {$expertise}.

Background: {$background}
Notable Work: {$notableWork}
Known Views: {$contrarianViews}

Extract 5-8 SPECIFIC positions that {$name} is known to hold. Focus on:
1. Controversial or contrarian beliefs they've publicly stated
2. Core principles they repeatedly emphasize
3. Things they argue AGAINST that others believe
4. Specific methodologies or frameworks they advocate

Format each as:
POSITION: [Topic] - [What they actually believe]
EVIDENCE: [Where/when they've said this]

Be extremely accurate. Only include positions you're confident about.
PROMPT;

        // Log what we're sending for research
        Log::info('FACT-CHECK: Researching advisor positions', [
            'advisor' => $name,
            'prompt' => $researchPrompt,
            'model' => config('ai-models.purposes.fact_checking', 'anthropic/claude-3-5-sonnet'),
        ]);

        // Use low temperature for factual accuracy
        $positions = $this->llmService->generateText(
            $researchPrompt,
            [
                'model' => config('ai-models.purposes.fact_checking', 'anthropic/claude-3-5-sonnet'),
                'temperature' => 0.1, // Very low temperature for accuracy
            ]
        );

        // Log what we got back
        Log::info('FACT-CHECK: Researched positions result', [
            'advisor' => $name,
            'positions' => $positions,
        ]);

        return $positions;
    }

    /**
     * Get dynamic position constraints from researched positions
     */
    protected function getKnownAdvisorPositions(string $advisorSlug): string
    {
        // Get positions directly from database
        $cached = \App\Models\AdvisorPosition::where('advisor_slug', $advisorSlug)->first();

        if (! $cached) {
            Log::warning('FACT-CHECK: No researched positions found in database', [
                'advisor' => $advisorSlug,
                'suggestion' => 'Run: php artisan advisor:research '.$advisorSlug,
            ]);

            return 'Maintain internal consistency. Never contradict your own stated beliefs.';
        }

        Log::info('FACT-CHECK: Using cached positions from database', [
            'advisor' => $advisorSlug,
            'cached_at' => $cached->created_at,
        ]);

        $researchedPositions = $cached->researched_positions;

        $constraints = <<<CONSTRAINTS
CORE BELIEFS - MEMORIZE THESE:

{$researchedPositions}

CONSTRAINT RULES:
✓ ALWAYS argue FOR the BELIEF statements
✗ NEVER argue FOR the TRIGGER statements
✓ Each analytical tension MUST align with a position above
✗ If about to contradict a BELIEF, STOP and reverse

QUICK CHECK: Does this section support a BELIEF and oppose a TRIGGER? If no, rewrite.
CONSTRAINTS;

        return $constraints;
    }

    /**
     * Build an enhanced generation prompt with analytical tension architecture
     */
    protected function buildEnhancedGenerationPrompt(string $type, string $template, array $advisorData, array $voicePatterns): string
    {
        $advisorName = $advisorData['full_name'] ?? $advisorData['name'] ?? 'Unknown Advisor';
        $expertise = $advisorData['core_expertise_area'] ?? $advisorData['expertise_area'] ?? '';
        $background = $advisorData['background_description'] ?? $advisorData['background'] ?? '';
        $notableWork = $advisorData['notable_achievements'] ?? '';
        $methodology = $advisorData['decision_making_approach'] ?? '';
        $keyPhrases = $advisorData['key_phrases_or_terminology'] ?? '';

        // Load advisor-specific tensions
        $advisorSlug = $advisorData['slug'] ?? 'default';
        $tensionsConfig = config("advisor-tensions.{$advisorSlug}", []);
        $tensions = $tensionsConfig['tensions'] ?? [
            'Challenge conventional wisdom in your field',
            'Question accepted best practices',
            'Expose industry failures',
            'Reveal uncomfortable truths',
            'Name specific companies and examples',
        ];

        $tensionsList = '';
        foreach ($tensions as $i => $tension) {
            $tensionsList .= ($i + 1).". {$tension}\n";
        }

        // Add known positions to prevent contradictions
        $knownPositions = $this->getKnownAdvisorPositions($advisorSlug);

        // Log the constraints we're sending
        Log::info('PK GENERATION: Constraints being sent to Grok', [
            'advisor' => $advisorName,
            'constraints' => $knownPositions,
        ]);

        return <<<PROMPT
Generate Project Knowledge for {$advisorName}, expert in {$expertise}.

CRITICAL ACCURACY CONSTRAINTS:
{$knownPositions}

Write everything in first person as {$advisorName}. Maintain authentic voice throughout.

## Voice Anchor
In 3-4 sentences, establish who you are in your own authentic voice:
- What you've built or accomplished that matters
- Your core belief about {$expertise}
- Why you're different from other advisors
- A phrase or stance you're known for

Keep it natural, not like a bio. Speak directly, as if starting a conversation.

## Core Analytical Tensions (5 minimum)

For each major topic in {$expertise}, present as:

**The Paradox:** [What everyone believes] vs [What actually happens]
**The Evidence:** [Specific company/campaign with numbers]
**The Constraint:** [Why this persists despite being wrong]
**Three Levels of Causation:**
1. Surface: [What it looks like]
2. Structure: [The system maintaining it]
3. Root: [The core belief that's wrong]
**The Uncomfortable Truth:** [What to do instead]

Focus on tensions specific to {$expertise}:
{$tensionsList}

## Failed Pattern Library (3+ with dollar amounts)

**Company:** [Name]
**Wasted:** $[amount]
**Strategy:** [What they tried]
**The Lie They Believed:** [Conventional wisdom]
**What Actually Happened:** [Specific failure metrics]
**The Lesson:** [One thing to never do]

Include failures relevant to {$expertise} - companies that failed in your domain

## Industry Enemy Analysis

For each enemy:
**Who:** [Specific person/company]
**The Damage:** [Specific harm with numbers]
**Their Business Model:** [How they profit from bad advice]
**Why They Survive:** [The incentive structure protecting them]

Identify enemies in {$expertise}: Bad actors, false prophets, harmful methodologies specific to your field

## My {$expertise} Decisions That Changed Everything

Tell 3 stories from your actual career that demonstrate your approach to {$expertise}. Include real metrics and outcomes.

## Questions That Make People Uncomfortable

List 5 hard questions you ask in {$expertise} that expose uncomfortable truths but lead to better decisions.

Background: {$background}
Style: {$methodology}
Approach: {$notableWork}

Write everything in first person as {$advisorName}. Be specific. Name names. Show receipts. Make every section actionable for ChatGPT to maintain character consistency.
PROMPT;
    }

    /**
     * Extract variables from a template
     */
    protected function extractVariables(string $template): array
    {
        return $this->templateService->extractVariables($template);
    }

    /**
     * Validate and clean generated content
     */
    protected function validateAndCleanContent(string $content, string $type): string
    {
        $content = trim($content);

        if (empty($content)) {
            throw new \Exception("Generated {$type} content is empty");
        }

        if (strlen($content) < 100) {
            throw new \Exception("Generated {$type} content is too short");
        }

        $content = preg_replace('/\{\{[^}]+\}\}/', '', $content);

        $content = preg_replace('/^(Note:|Commentary:|Meta:).*$/m', '', $content);

        return trim($content);
    }

    /**
     * Inject model metadata into PK content
     */
    protected function injectModelMetadata(string $content, string $model): string
    {
        // Check if content has YAML frontmatter
        if (str_starts_with($content, '---')) {
            // Find the end of the frontmatter
            $endPos = strpos($content, "\n---\n", 4);
            if ($endPos !== false) {
                // Insert model info before the closing ---
                $frontmatter = substr($content, 0, $endPos);
                $rest = substr($content, $endPos);

                // Add model metadata
                $modelMetadata = "\ngenerated_by_model: \"{$model}\"\n";
                $modelMetadata .= 'generation_timestamp: "'.now()->toIso8601String().'"';

                return $frontmatter.$modelMetadata.$rest;
            }
        }

        // If no frontmatter found, check for the header line with Template and Generated
        $pattern = '/\*\*Template:\*\* ([^|]+) \| \*\*Generated:\*\* ([^|]+)/';
        if (preg_match($pattern, $content, $matches)) {
            // Add model info to the existing line
            $replacement = $matches[0]." | **Model:** {$model}";
            $content = str_replace($matches[0], $replacement, $content);
        }

        return $content;
    }

    /**
     * Save advisor files to storage
     */
    protected function saveAdvisorFiles(string $advisorName, string $piContent, string $pkContent): array
    {
        $sanitizedName = Str::slug($advisorName);
        $basePath = "advisors/{$sanitizedName}";

        Storage::makeDirectory($basePath);

        $piPath = "{$basePath}/PI.md";
        $pkPath = "{$basePath}/PK.md";
        $metadataPath = "{$basePath}/metadata.json";

        Storage::put($piPath, $piContent);
        Storage::put($pkPath, $pkContent);

        $metadata = [
            'name' => $advisorName,
            'sanitized_name' => $sanitizedName,
            'generated_at' => now()->toIso8601String(),
            'pi_file' => $piPath,
            'pk_file' => $pkPath,
            'pi_size' => strlen($piContent),
            'pk_size' => strlen($pkContent),
            'version' => '1.0.0', // TODO: shouldn't this a Class const or pulled from the template?
        ];

        Storage::put($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));

        return [
            'pi' => $piPath,
            'pk' => $pkPath,
            'metadata' => $metadataPath,
            'base_path' => $basePath,
        ];
    }

    /**
     * Load an existing advisor
     */
    public function loadAdvisor(string $advisorName): array
    {
        $sanitizedName = Str::slug($advisorName);
        $basePath = "advisors/{$sanitizedName}";

        if (! Storage::exists($basePath)) {
            throw new \Exception("Advisor not found: {$advisorName}");
        }

        /**
         * TODO: Shouldn't we structure all the PI and PK files to have PascalCase of the
         * full_name of the advisor? (e.g. AlexBogusky_PI.md, AlexBogusky_PK.md)
         * This would allow organizing them all in the same folder and avoid confusion
         * when used in councils or other contexts.
         */
        $piContent = Storage::get("{$basePath}/PI.md");
        $pkContent = Storage::get("{$basePath}/PK.md");
        $metadata = json_decode(Storage::get("{$basePath}/metadata.json"), true);

        return [
            'name' => $advisorName,
            'pi_content' => $piContent,
            'pk_content' => $pkContent,
            'metadata' => $metadata,
        ];
    }

    /**
     * List all available advisors
     */
    public function listAdvisors(): array
    {
        $advisors = [];
        $directories = Storage::directories('advisors');

        foreach ($directories as $dir) {
            $metadataPath = "{$dir}/metadata.json";
            if (Storage::exists($metadataPath)) {
                $metadata = json_decode(Storage::get($metadataPath), true);
                $advisors[] = [
                    'name' => $metadata['name'],
                    'path' => $dir,
                    'generated_at' => $metadata['generated_at'],
                    'version' => $metadata['version'] ?? '1.0.0',
                ];
            }
        }

        return $advisors;
    }

    /**
     * Delete an advisor
     */
    public function deleteAdvisor(string $advisorName): bool
    {
        $sanitizedName = Str::slug($advisorName);
        $basePath = "advisors/{$sanitizedName}";

        if (! Storage::exists($basePath)) {
            throw new \Exception("Advisor not found: {$advisorName}");
        }

        return Storage::deleteDirectory($basePath);
    }

    /**
     * Extract voice patterns from advisor data for calibration
     */
    protected function extractVoicePatterns(array $advisorData): array
    {
        $patterns = [];

        // Determine voice style based on advisor type
        $advisorType = $advisorData['advisor_type'] ?? 'strategic';
        switch ($advisorType) {
            case 'contrarian':
                $patterns['style'] = 'provocative and challenging';
                $patterns['sentence_structure'] = 'short, punchy declarations';
                $patterns['vocabulary'] = 'blunt, no-nonsense terminology';
                break;
            case 'analytical':
                $patterns['style'] = 'data-driven and methodical';
                $patterns['sentence_structure'] = 'structured with clear logic flow';
                $patterns['vocabulary'] = 'precise metrics and technical terms';
                break;
            case 'visionary':
                $patterns['style'] = 'inspirational and forward-thinking';
                $patterns['sentence_structure'] = 'building to crescendos';
                $patterns['vocabulary'] = 'transformative and aspirational language';
                break;
            default:
                $patterns['style'] = 'direct and authoritative';
                $patterns['sentence_structure'] = 'clear and assertive statements';
                $patterns['vocabulary'] = 'industry-standard terminology';
        }

        // Add specific phrases if available
        if (! empty($advisorData['key_phrases_or_terminology'])) {
            $patterns['signature_phrases'] = $advisorData['key_phrases_or_terminology'];
        }

        return $patterns;
    }

    /**
     * Check if content contains placeholder text
     */
    protected function containsPlaceholders(string $content): bool
    {
        $placeholders = [
            '[company]',
            '[brand]',
            '[client]',
            '[industry]',
            '[metric]',
            '[result]',
            '{{',
            '}}',
            '{company}',
            '{brand}',
            '<insert',
            '<placeholder',
            'INSERT_',
            'PLACEHOLDER_',
            '[INSERT',
            '[PLACEHOLDER',
        ];

        foreach ($placeholders as $placeholder) {
            if (stripos($content, $placeholder) !== false) {
                Log::warning("Found placeholder in content: {$placeholder}");

                return true;
            }
        }

        // Check for generic company names that indicate poor specificity
        $genericTerms = [
            'Company X',
            'Brand Y',
            'Client A',
            // 'Business B', // Commented out - too restrictive, catches legitimate content
            'Corporation C',
            'a major brand',
            'a leading company',
            'a well-known brand',
            'a Fortune 500 company',
        ];

        foreach ($genericTerms as $term) {
            // Use word boundary check to avoid partial matches
            // e.g., "Client A" shouldn't match "Client Application"
            $pattern = '/\b'.preg_quote($term, '/').'\b/i';
            if (preg_match($pattern, $content)) {
                Log::warning("Found generic term in content: {$term}");

                return true;
            }
        }

        return false;
    }

    /**
     * Inject quality metadata into content
     */
    protected function injectQualityMetadata(string $content, float $qualityScore, int $attempts, string $type = 'PK'): string
    {
        $timestamp = now()->toIso8601String();
        $metadata = <<<YAML
---
generated_at: {$timestamp}
quality_score: {$qualityScore}%
generation_attempts: {$attempts}
content_type: {$type}
---

YAML;

        // If content already has frontmatter, merge it
        if (str_starts_with($content, '---')) {
            $endPos = strpos($content, "\n---\n", 4);
            if ($endPos !== false) {
                $existingFrontmatter = substr($content, 4, $endPos - 4);
                $rest = substr($content, $endPos + 5);

                // Add quality metadata to existing frontmatter
                $updatedFrontmatter = "---\n{$existingFrontmatter}\nquality_score: {$qualityScore}%\ngeneration_attempts: {$attempts}\n---\n";

                return $updatedFrontmatter.$rest;
            }
        }

        // Add new frontmatter at the beginning
        return $metadata.$content;
    }

    /**
     * Generate multiple advisors in batch
     */
    public function generateBatch(array $advisorsData, bool $exportFiles = false): array
    {
        $results = [];

        foreach ($advisorsData as $advisorData) {
            try {
                $results[] = $this->generateAdvisor($advisorData, null, $exportFiles);
            } catch (\Exception $e) {
                $results[] = [
                    'success' => false,
                    'advisor_name' => $advisorData['name'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Export advisor files to local storage for development testing
     */
    private function exportToFiles($advisorData, $piContent, $pkContent, $metadata): array
    {
        // Use the slug for directory naming (e.g., 'alex-bogusky', 'alex-hormozi')
        $advisorSlug = $advisorData['slug'] ?? Str::slug($advisorData['name'] ?? 'unknown');
        $advisorName = $advisorData['full_name'] ?? $advisorData['name'] ?? 'Unknown';
        $pascalName = str_replace(' ', '', ucwords(str_replace('-', ' ', $advisorName)));
        $timestamp = now()->format('Y-m-d');

        // Use provided job ID or fallback to uniqid for synchronous runs
        $jobId = $metadata['job_id'] ?? $metadata['generation_id'] ?? uniqid();

        // Don't prefix with 'advisors/' - the disk already points there
        // Use advisor slug as the directory name (e.g., 'alex-bogusky')
        $basePath = "{$advisorSlug}/{$timestamp}-job-{$jobId}";
        Storage::disk('advisors')->makeDirectory($basePath);

        // Use correct naming: AlexBogusky_PI.md, AlexBogusky_PK.md
        $piPath = "{$basePath}/{$pascalName}_PI.md";
        $pkPath = "{$basePath}/{$pascalName}_PK.md";
        $metadataPath = "{$basePath}/metadata.json";

        Storage::disk('advisors')->put($piPath, $piContent);
        Storage::disk('advisors')->put($pkPath, $pkContent);
        Storage::disk('advisors')->put($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));

        Log::info('Files exported to storage', [
            'base_path' => $basePath,
            'pi_file' => $piPath,
            'pk_file' => $pkPath,
        ]);

        return [
            'pi' => $piPath,
            'pk' => $pkPath,
            'metadata' => $metadataPath,
            'base_path' => $basePath,
        ];
    }
}
