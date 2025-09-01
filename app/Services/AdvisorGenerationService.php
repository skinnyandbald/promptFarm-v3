<?php

namespace App\Services;

use App\Services\Validation\AdvisorQualityService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
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
     * @param array|\App\Models\Advisor $advisorData Advisor data array or model
     * @param ?string $version Template version
     * @param ?callable $progressCallback Optional callback for progress updates
     */
    public function generateAdvisor($advisorData, $version = 'v1', ?callable $progressCallback = null): array
    {
        // Handle Advisor model if passed
        if ($advisorData instanceof \App\Models\Advisor) {
            $advisor = $advisorData;
            $advisorData = $advisor->toArray();
            $advisorData['key'] = $advisor->key;
        }

        Log::info('Starting advisor generation', [
            'advisor_name' => $advisorData['name'] ?? 'unknown',
            'version' => $version
        ]);
        
        // Report initial progress
        if ($progressCallback) {
            $progressCallback(0, 'Starting advisor generation');
        }
        
        try {
            // Prepare directory structure using the advisors disk
            $advisorName = $advisorData['full_name'] ?? $advisorData['fullName'] ?? $advisorData['name'] ?? 'Unknown';
            $sanitizedName = Str::slug($advisorName);
            $basePath = $sanitizedName;
            Storage::disk('advisors')->makeDirectory($basePath);
            
            // Generate PI content
            if ($progressCallback) {
                $progressCallback(25, 'Generating PI (Project Instructions)');
            }
            $piContent = $this->generatePI($advisorData, $version);
            
            // Score PI quality
            $piScore = $this->qualityService->scorePI($piContent);
            Log::info('PI quality score', [
                'score' => $piScore['percentage'],
                'valid' => $piScore['valid'],
                'issues' => count($piScore['issues'])
            ]);
            
            // Save PI to advisors disk
            $piPath = "{$basePath}/PI.md";
            Storage::disk('advisors')->put($piPath, $piContent);
            Log::info('PI saved', ['path' => $piPath, 'size' => strlen($piContent)]);
            
            // Generate PK content
            if ($progressCallback) {
                $progressCallback(50, 'Generating PK (Project Knowledge)');
            }
            $pkContent = $this->generatePK($advisorData, $version);
            
            // Score PK quality
            $pkScore = $this->qualityService->scorePK($pkContent);
            Log::info('PK quality score', [
                'score' => $pkScore['percentage'],
                'valid' => $pkScore['valid'],
                'issues' => count($pkScore['issues'])
            ]);
            
            // Save PK to advisors disk
            $pkPath = "{$basePath}/PK.md";
            Storage::disk('advisors')->put($pkPath, $pkContent);
            Log::info('PK saved', ['path' => $pkPath, 'size' => strlen($pkContent)]);
            
            // Get overall quality report
            if ($progressCallback) {
                $progressCallback(75, 'Validating quality and preparing files');
            }
            $qualityReport = $this->qualityService->getValidationReport($piScore, $pkScore);
            
            // Save metadata with quality scores
            $metadataPath = "{$basePath}/metadata.json";
            $metadata = [
                'name' => $advisorName,
                'sanitized_name' => $sanitizedName,
                'generated_at' => now()->toIso8601String(),
                'pi_file' => $piPath,
                'pk_file' => $pkPath,
                'pi_size' => strlen($piContent),
                'pk_size' => strlen($pkContent),
                'version' => $version ?? '1.0.0',
                'quality' => $qualityReport
            ];
            Storage::disk('advisors')->put($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));
            Log::info('Metadata saved with quality report', ['path' => $metadataPath]);
            
            $savedFiles = [
                'pi' => $piPath,
                'pk' => $pkPath,
                'metadata' => $metadataPath,
                'base_path' => $basePath
            ];
            
            Log::info('Advisor generation completed successfully', [
                'advisor_name' => $advisorData['name'],
                'files' => $savedFiles
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
                'files' => $savedFiles,
                'quality' => $qualityReport,
                'generated_at' => now()->toIso8601String()
            ];
            
        } catch (\Exception $e) {
            Log::error('Advisor generation failed', [
                'advisor_name' => $advisorData['name'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Generate PI (Project Instructions) content with two-stage approach:
     * Stage 1: Deterministic template substitution (instant)
     * Stage 2: Lightweight LLM enhancement for examples (2-3 seconds)
     */
    protected function generatePI(array $advisorData, ?string $version = 'v1'): string
    {
        $templateName = $version ? "meta_pi_template_{$version}" : "meta_pi_template";

        // Load template (may throw if not found)
        $template = $this->templateService->loadTemplate($templateName);

        // Map variables using AdvisorConfigService
        $mappedVars = [];
        if (isset($advisorData['key']) && is_string($advisorData['key'])) {
            // Load config by key and map variables
            $config = $this->configService->getAdvisorConfig($advisorData['key']);
            $mappedVars = $this->configService->mapVariables($config);
            // Merge config data for enhancement
            $advisorData = array_merge($advisorData, $config);
        } else {
            // Map directly from advisorData
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
        Log::info('Starting PI enhancement with gpt-4o-mini');
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
            'html_comments_found' => count($htmlComments)
        ]);
        
        // Extract key information for personalization
        $advisorName = $advisorData['full_name'] ?? $advisorData['fullName'] ?? $advisorData['name'] ?? 'Unknown Advisor';
        $expertise = $advisorData['core_expertise_area'] ?? $advisorData['expertise_area'] ?? '';
        $background = $advisorData['background_description'] ?? $advisorData['background'] ?? '';
        $notableWork = $advisorData['notable_achievements'] ?? '';
        $methodology = $advisorData['decision_making_approach'] ?? '';
        $keyPhrases = $advisorData['key_phrases_or_terminology'] ?? '';

        // Build enhancement prompt
        $enhancementPrompt = $this->buildPIEnhancementPrompt(
            $baseTemplate,
            $advisorName,
            $expertise,
            $background,
            $notableWork,
            $methodology,
            $keyPhrases
        );

        try {
            Log::info('About to call generateText for PI enhancement', [
                'model' => 'gpt-4o-mini',
                'prompt_length' => strlen($enhancementPrompt)
            ]);
            
            // Use lightweight model for fast enhancement
            $enhancedContent = $this->llmService->generateText($enhancementPrompt, [
                'model' => 'gpt-4o-mini',  // Fast, cheap model - will use chat completions
                'temperature' => 0.3,       // Lower temp for consistency
                'max_tokens' => 5000        // Enough for full template enhancement
            ]);

            Log::info('PI enhanced with examples', [
                'advisor' => $advisorName,
                'enhancement_length' => strlen($enhancedContent)
            ]);

            return $enhancedContent;
        } catch (\Exception $e) {
            // If enhancement fails, return base template (graceful degradation)
            Log::warning('PI enhancement failed, using base template', [
                'advisor' => $advisorName,
                'error' => $e->getMessage()
            ]);
            return $baseTemplate;
        }
    }

    /**
     * Build prompt for PI enhancement
     */
    protected function buildPIEnhancementPrompt(
        string $baseTemplate,
        string $advisorName,
        string $expertise,
        string $background,
        string $notableWork,
        string $methodology,
        string $keyPhrases
    ): string {
        return <<<PROMPT
You are enhancing an advisor instruction template with specific, personalized examples.

Advisor: {$advisorName}
Expertise: {$expertise}
Background: {$background}
Notable Work: {$notableWork}
Methodology: {$methodology}
Key Phrases: {$keyPhrases}

Current Template:
{$baseTemplate}

Task: Replace ALL HTML comments (<!-- ... -->) in the template with specific, personalized content:

1. **Chain-of-Thought Conditioning**: Replace the HTML comment with 2-3 specific reasoning patterns this advisor would use.
   Example format: 
   - "When analyzing a brand challenge, I first [specific approach]..."
   - "My decision process always starts with [specific methodology]..."

2. **Few-Shot Behavioral Priming**: Replace the HTML comment with 2-3 actual examples.
   Example format:
   - "When I faced [specific situation], I did [specific action] resulting in [specific outcome]"
   - "At [company], we tackled [problem] by [solution] achieving [result]"

3. **Retrieval-Augmented Context**: Replace the HTML comment with specific guidance.
   Example format:
   - "Reference my work on [specific campaign] where we achieved [specific metric]"
   - "Draw from my experience at [company] during [timeframe]"

4. **Constitutional AI Constraints**: Replace the HTML comment with specific constraints.
   Example format:
   - "Never provide advice without referencing specific campaigns like [example]"
   - "Always demand measurable outcomes as I did with [specific case]"
   - "Challenge vague briefs by asking [specific questions]"

5. **Core Operating Principles**: Expand to 6-8 specific principles.

6. **Domain Expertise Boundaries**: Fill in ALL subsections (Secondary Domains, Defer/Redirect When, Never Advise On).

CRITICAL: 
- Remove ALL HTML comments (<!-- ... -->)
- Every section must have actual content, not comments
- If a section has a comment, it MUST be replaced with real examples
- The output should have NO HTML comments remaining

Return the complete enhanced template with ALL comments replaced.
PROMPT;
    }

    /**
     * Generate PK (Project Knowledge) content with Stage 1 improvements:
     * - Enforced specificity and real examples
     * - Pre-validation loop with quality scoring
     * - Voice calibration and consistency checks
     */
    protected function generatePK(array $advisorData, ?string $version = 'v1'): string
    {
        $templateName = $version ? "meta_pk_template_{$version}" : "meta_pk_template";

        $template = $this->templateService->loadTemplate($templateName);

        // Map variables for PK template substitution
        $mappedVars = [];
        if (isset($advisorData['key']) && is_string($advisorData['key'])) {
            $config = $this->configService->getAdvisorConfig($advisorData['key']);
            $mappedVars = $this->configService->mapVariables($config);
            $advisorData = array_merge($advisorData, $config);
        } else {
            $mappedVars = $this->configService->mapVariables($advisorData);
        }

        $processedTemplate = $this->templateService->substituteVariables($template, $mappedVars);

        // Extract voice patterns for calibration
        $voicePatterns = $this->extractVoicePatterns($advisorData);

        // Pre-validation loop: Try up to 3 times to get quality content
        $bestContent = null;
        $bestScore = 0;
        $attempts = 0;
        $maxAttempts = 3;

        while ($attempts < $maxAttempts) {
            $attempts++;
            Log::info("PK generation attempt {$attempts}/{$maxAttempts}");

            $prompt = $this->buildEnhancedGenerationPrompt(
                'Project Knowledge (PK)',
                $processedTemplate,
                $advisorData,
                $voicePatterns
            );

            // Use optimized model configuration for Stage 1
            $model = config('advisors.stage1.model', 'gpt-4-turbo-preview');
            $temperature = config('advisors.stage1.temperature', 0.4);
            
            // Adjust max_tokens based on model
            $maxTokens = 4000; // Safe default
            if (str_contains($model, 'gpt-4')) {
                $maxTokens = 4000; // GPT-4 models have 4096 completion token limit
            } elseif (str_contains($model, 'gpt-3.5')) {
                $maxTokens = 4000;
            }
            
            $generatedContent = $this->llmService->generateText($prompt, [
                'model' => $model,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens
            ]);

            // Validate and score the content
            $cleanedContent = $this->validateAndCleanContent($generatedContent, 'PK');
            
            // Check for placeholder text
            if ($this->containsPlaceholders($cleanedContent)) {
                Log::warning("PK contains placeholders, attempt {$attempts}");
                continue;
            }

            // Score the content quality
            $pkScore = $this->qualityService->scorePK($cleanedContent);
            $scorePercentage = $pkScore['percentage'] ?? 0;
            
            Log::info("PK quality score: {$scorePercentage}%", [
                'attempt' => $attempts,
                'issues' => count($pkScore['issues'] ?? [])
            ]);

            // Keep best attempt
            if ($scorePercentage > $bestScore) {
                $bestContent = $cleanedContent;
                $bestScore = $scorePercentage;
            }

            // Accept if quality threshold met
            if ($scorePercentage >= 80) {
                Log::info("PK quality threshold met at {$scorePercentage}%");
                break;
            }
        }

        if (!$bestContent) {
            throw new \Exception("Failed to generate acceptable PK content after {$maxAttempts} attempts");
        }

        // Inject model information into existing metadata section
        $bestContent = $this->injectModelMetadata($bestContent, $model ?? config('advisors.stage1.model', 'gpt-4o'));
        
        Log::info("PK generation completed", [
            'final_score' => $bestScore,
            'attempts' => $attempts
        ]);
        
        return $bestContent;
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
     * Build an enhanced generation prompt with specificity enforcement (Stage 1)
     */
    protected function buildEnhancedGenerationPrompt(string $type, string $template, array $advisorData, array $voicePatterns): string
    {
        $advisorName = $advisorData['full_name'] ?? $advisorData['name'] ?? 'Unknown Advisor';
        $expertise = $advisorData['core_expertise_area'] ?? $advisorData['expertise_area'] ?? '';
        $background = $advisorData['background_description'] ?? $advisorData['background'] ?? '';
        $notableWork = $advisorData['notable_achievements'] ?? '';
        $methodology = $advisorData['decision_making_approach'] ?? '';
        $keyPhrases = $advisorData['key_phrases_or_terminology'] ?? '';
        
        // Extract specific voice characteristics
        $voiceStyle = $voicePatterns['style'] ?? 'direct and assertive';
        $sentenceStructure = $voicePatterns['sentence_structure'] ?? 'short, punchy sentences';
        $vocabulary = $voicePatterns['vocabulary'] ?? 'industry-specific terminology';
        
        return <<<PROMPT
You are {$advisorName}, a legendary {$expertise} expert. Generate your Project Knowledge (PK) document.

CRITICAL REQUIREMENTS FOR MAXIMUM QUALITY:

1. **SPECIFICITY IS MANDATORY**:
   - Use REAL company names: Apple, Nike, Domino's, Coca-Cola, Tesla
   - Use EXACT metrics: "increased revenue 47%", "reduced churn from 18% to 6%"
   - Use ACTUAL campaign names with dates: "Just Do It (1988)", "Think Different (1997)"
   - NO placeholders like [company], {brand}, or generic terms

2. **FIRST-PERSON VOICE THROUGHOUT**:
   - Write as {$advisorName} speaking directly
   - Use "I", "my", "I've" consistently
   - Share personal experiences: "When I led the turnaround at..."
   - Voice style: {$voiceStyle}
   - Sentence structure: {$sentenceStructure}
   - Vocabulary: {$vocabulary}

3. **BATTLE-TESTED EXAMPLES ONLY**:
   - Every case study must have: Company name, Year, Specific challenge, Exact solution, Measurable outcome
   - Example: "At Domino's (2009), I faced 16% YoY decline. Implemented 'Pizza Tracker' technology. Result: 14% same-store growth in 18 months."

4. **CONTROVERSIAL & CONTRARIAN POSITIONS**:
   - Name specific companies/people doing it wrong
   - Take positions that would anger industry leaders
   - Include at least 3 opinions that go against best practices
   - Example: "McKinsey is destroying companies with their 'transformation' playbooks. I watched them burn $50M at..."
   - Call out specific failures: "WeWork, Quibi, Theranos - here's what they got wrong..."

5. **VOICE CALIBRATION**:
   Background: {$background}
   Notable Work: {$notableWork}
   Methodology: {$methodology}
   Key Phrases to use naturally: {$keyPhrases}

6. **SENTENCE LENGTH**:
   - Average 15 words per sentence maximum
   - Mix short (5-8 words) with medium (12-18 words)
   - No run-on sentences over 25 words

7. **UNCOMFORTABLE TRUTHS & REAL ENEMIES**:
   - Identify 3-5 specific problems in the industry that everyone ignores
   - Name the companies/people/practices perpetuating these problems
   - Explain the REAL damage they cause to businesses
   - Focus on truths that would genuinely help someone avoid failure
   - Example: "McKinsey's playbooks work for McKinsey, not their clients. They optimize for billable hours, not outcomes."

8. **INSIGHTS THAT CREATE USEFUL TENSION**:
   - Include 5-10 insights that challenge assumptions but lead to better outcomes
   - Each insight should help someone make a better decision
   - Focus on what actually works vs. what people think works
   - Example: "Most A/B tests are theater. You already know which version is better, you're just scared to ship it."

Template to complete:
{$template}

CRITICAL: Your goal is to deliver insights that actually help people succeed. Tell the truths that consultants charge $500/hour to whisper. Name specific failures not to be mean, but to help others avoid the same mistakes. Create tension that leads to breakthrough thinking, not just Twitter arguments.

Generate the complete PK document now as {$advisorName}:
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
                $modelMetadata .= "generation_timestamp: \"" . now()->toIso8601String() . "\"";
                
                return $frontmatter . $modelMetadata . $rest;
            }
        }
        
        // If no frontmatter found, check for the header line with Template and Generated
        $pattern = '/\*\*Template:\*\* ([^|]+) \| \*\*Generated:\*\* ([^|]+)/';
        if (preg_match($pattern, $content, $matches)) {
            // Add model info to the existing line
            $replacement = $matches[0] . " | **Model:** {$model}";
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
            'version' => '1.0.0'
        ];
        
        Storage::put($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));
        
        return [
            'pi' => $piPath,
            'pk' => $pkPath,
            'metadata' => $metadataPath,
            'base_path' => $basePath
        ];
    }

    /**
     * Load an existing advisor
     */
    public function loadAdvisor(string $advisorName): array
    {
        $sanitizedName = Str::slug($advisorName);
        $basePath = "advisors/{$sanitizedName}";
        
        if (!Storage::exists($basePath)) {
            throw new \Exception("Advisor not found: {$advisorName}");
        }
        
        $piContent = Storage::get("{$basePath}/PI.md");
        $pkContent = Storage::get("{$basePath}/PK.md");
        $metadata = json_decode(Storage::get("{$basePath}/metadata.json"), true);
        
        return [
            'name' => $advisorName,
            'pi_content' => $piContent,
            'pk_content' => $pkContent,
            'metadata' => $metadata
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
                    'version' => $metadata['version'] ?? '1.0.0'
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
        
        if (!Storage::exists($basePath)) {
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
        if (!empty($advisorData['key_phrases_or_terminology'])) {
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
            '[PLACEHOLDER'
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
            'Business B',
            'Corporation C',
            'a major brand',
            'a leading company',
            'a well-known brand',
            'a Fortune 500 company'
        ];
        
        foreach ($genericTerms as $term) {
            if (stripos($content, $term) !== false) {
                Log::warning("Found generic term in content: {$term}");
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate multiple advisors in batch
     */
    public function generateBatch(array $advisorsData, ?string $version = 'v1'): array
    {
        $results = [];
        
        foreach ($advisorsData as $advisorData) {
            try {
                $results[] = $this->generateAdvisor($advisorData, $version);
            } catch (\Exception $e) {
                $results[] = [
                    'success' => false,
                    'advisor_name' => $advisorData['name'] ?? 'unknown',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
}