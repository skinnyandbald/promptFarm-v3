<?php

namespace App\Services\Validation;

use App\Services\LLMService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AIEmbodimentQualityScorer
{
    public function __construct(protected LLMService $llmService) {}

    /**
     * Score AI embodiment quality using hybrid approach
     * 
     * @param string $content PI content to score
     * @param array $advisorData Optional advisor context for personalized scoring
     * @return array Quality score breakdown and recommendations
     */
    public function scoreAIEmbodiment(string $content, array $advisorData = []): array
    {
        Log::info('Starting AI embodiment quality scoring', [
            'content_length' => strlen($content),
            'advisor' => $advisorData['name'] ?? 'unknown'
        ]);

        try {
            // Static Analysis (35%)
            $informationDensity = $this->analyzeInformationDensity($content);
            $constitutionalAI = $this->analyzeConstitutionalAI($content);
            
            $staticScore = ($informationDensity['score'] * 20) + ($constitutionalAI['score'] * 15);

            // LLM Semantic Analysis (65%)
            $semanticResults = $this->performSemanticAnalysis($content, $advisorData);
            
            $voiceAuthenticityScore = $semanticResults['voice_authenticity'] * 0.30;
            $behavioralTriggersScore = $semanticResults['behavioral_triggers'] * 0.25;
            $contextEngineeringScore = $semanticResults['context_engineering'] * 0.10;
            
            $llmScore = $voiceAuthenticityScore + $behavioralTriggersScore + $contextEngineeringScore;
            
            // Calculate final score
            $totalScore = $staticScore + $llmScore;
            
            $result = [
                'total_score' => round($totalScore, 1),
                'valid' => $totalScore >= 75,
                'breakdown' => [
                    'static_analysis' => [
                        'score' => round($staticScore, 1),
                        'weight' => '35%',
                        'components' => [
                            'information_density' => [
                                'score' => round($informationDensity['score'] * 100, 1),
                                'weight' => '20%',
                                'details' => $informationDensity
                            ],
                            'constitutional_ai' => [
                                'score' => round($constitutionalAI['score'] * 100, 1),
                                'weight' => '15%',
                                'details' => $constitutionalAI
                            ]
                        ]
                    ],
                    'semantic_analysis' => [
                        'score' => round($llmScore, 1),
                        'weight' => '65%',
                        'components' => [
                            'voice_authenticity' => [
                                'score' => round($semanticResults['voice_authenticity'], 1),
                                'weight' => '30%'
                            ],
                            'behavioral_triggers' => [
                                'score' => round($semanticResults['behavioral_triggers'], 1),
                                'weight' => '25%'
                            ],
                            'context_engineering' => [
                                'score' => round($semanticResults['context_engineering'], 1),
                                'weight' => '10%'
                            ]
                        ]
                    ]
                ],
                'analysis' => $semanticResults['analysis'] ?? 'Detailed semantic analysis completed',
                'recommendations' => $semanticResults['recommendations'] ?? [],
                'metadata' => [
                    'scorer_version' => '1.0.0',
                    'scored_at' => now()->toIso8601String(),
                    'approach' => 'hybrid_intelligence',
                    'static_vs_llm' => '35/65'
                ]
            ];

            Log::info('AI embodiment scoring completed', [
                'total_score' => $totalScore,
                'valid' => $result['valid'],
                'advisor' => $advisorData['name'] ?? 'unknown'
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('AI embodiment scoring failed', [
                'error' => $e->getMessage(),
                'advisor' => $advisorData['name'] ?? 'unknown'
            ]);

            // Return fallback scores as specified in plan
            return $this->getFallbackScores($e->getMessage());
        }
    }

    /**
     * Analyze information density using static pattern analysis (20% weight)
     */
    protected function analyzeInformationDensity(string $content): array
    {
        $score = 0;
        $details = [];
        
        // Count actionable sentences (imperative verbs, instructions)
        $actionablePatterns = [
            '/\b(must|should|ensure|always|never|avoid)\b/i',
            '/\b(analyze|identify|determine|evaluate|assess)\b/i',
            '/^[-*•]\s+/m', // Bullet points tend to be actionable
            '/\b(step|process|method|approach|technique)\b/i'
        ];
        
        $actionableCount = 0;
        foreach ($actionablePatterns as $pattern) {
            $actionableCount += preg_match_all($pattern, $content);
        }
        
        $sentences = preg_split('/[.!?]+/', $content);
        $sentenceCount = count($sentences);
        $actionableDensity = $sentenceCount > 0 ? $actionableCount / $sentenceCount : 0;
        
        // Score actionable density (40% of information density score)
        if ($actionableDensity >= 0.3) {
            $score += 0.4;
        } elseif ($actionableDensity >= 0.2) {
            $score += 0.3;
        } elseif ($actionableDensity >= 0.1) {
            $score += 0.2;
        }
        
        // Measure specific examples vs abstract concepts
        $specificityPatterns = [
            '/\d+%/',  // Percentages
            '/\$[\d,]+/', // Dollar amounts
            '/\b\d{4}\b/', // Years
            '/\b(when|example|instance|case)\b/i',
            '/\b[A-Z][a-z]+\s+[A-Z][a-z]+\b/', // Proper names
        ];
        
        $specificityCount = 0;
        foreach ($specificityPatterns as $pattern) {
            $specificityCount += preg_match_all($pattern, $content);
        }
        
        $specificityDensity = strlen($content) > 0 ? $specificityCount / (strlen($content) / 1000) : 0;
        
        // Score specificity density (35% of information density score)  
        if ($specificityDensity >= 5) {
            $score += 0.35;
        } elseif ($specificityDensity >= 3) {
            $score += 0.25;
        } elseif ($specificityDensity >= 1) {
            $score += 0.15;
        }
        
        // Check for numbered lists and structured content (25% of information density score)
        $structurePatterns = [
            '/^\d+\.\s+/m', // Numbered lists
            '/^#{2,3}\s+/m', // Headers (H2, H3)
            '/\*\*[^*]+\*\*/', // Bold formatting for emphasis
        ];
        
        $structureCount = 0;
        foreach ($structurePatterns as $pattern) {
            $structureCount += preg_match_all($pattern, $content);
        }
        
        if ($structureCount >= 10) {
            $score += 0.25;
        } elseif ($structureCount >= 5) {
            $score += 0.15;
        } elseif ($structureCount >= 2) {
            $score += 0.1;
        }
        
        return [
            'score' => min(1.0, $score),
            'actionable_density' => round($actionableDensity, 3),
            'specificity_density' => round($specificityDensity, 2),
            'structure_elements' => $structureCount,
            'total_sentences' => $sentenceCount
        ];
    }

    /**
     * Analyze constitutional AI implementation using static pattern analysis (15% weight)
     */
    protected function analyzeConstitutionalAI(string $content): array
    {
        $score = 0;
        $details = [];
        
        // Check explicit constraints/boundaries (40% of constitutional AI score)
        $constraintPatterns = [
            '/\b(must not|never|avoid|forbidden|prohibited)\b/i',
            '/\b(always|required|mandatory|essential)\b/i',
            '/\b(boundary|limit|constraint|rule)\b/i'
        ];
        
        $constraintCount = 0;
        foreach ($constraintPatterns as $pattern) {
            $constraintCount += preg_match_all($pattern, $content);
        }
        
        if ($constraintCount >= 10) {
            $score += 0.4;
        } elseif ($constraintCount >= 5) {
            $score += 0.3;
        } elseif ($constraintCount >= 2) {
            $score += 0.2;
        }
        
        // Detect behavioral boundaries (30% of constitutional AI score)
        $behavioralPatterns = [
            '/\b(refuse|decline|defer|redirect)\b/i',
            '/\b(inappropriate|unethical|harmful)\b/i',
            '/\b(outside (my|your) expertise)\b/i'
        ];
        
        $behavioralCount = 0;
        foreach ($behavioralPatterns as $pattern) {
            $behavioralCount += preg_match_all($pattern, $content);
        }
        
        if ($behavioralCount >= 5) {
            $score += 0.3;
        } elseif ($behavioralCount >= 3) {
            $score += 0.2;
        } elseif ($behavioralCount >= 1) {
            $score += 0.15;
        }
        
        // Validate evidence requirements (30% of constitutional AI score)
        $evidencePatterns = [
            '/\b(evidence|proof|documentation|verified)\b/i',
            '/\b(source|citation|reference)\b/i',
            '/\b(documented|proven|validated)\b/i'
        ];
        
        $evidenceCount = 0;
        foreach ($evidencePatterns as $pattern) {
            $evidenceCount += preg_match_all($pattern, $content);
        }
        
        if ($evidenceCount >= 5) {
            $score += 0.3;
        } elseif ($evidenceCount >= 3) {
            $score += 0.2;
        } elseif ($evidenceCount >= 1) {
            $score += 0.1;
        }
        
        return [
            'score' => min(1.0, $score),
            'constraint_mentions' => $constraintCount,
            'behavioral_boundaries' => $behavioralCount,
            'evidence_requirements' => $evidenceCount
        ];
    }

    /**
     * Perform LLM semantic analysis for voice authenticity, behavioral triggers, and context engineering
     */
    protected function performSemanticAnalysis(string $content, array $advisorData): array
    {
        // Create cache key for LLM results based on content hash
        $contentHash = hash('sha256', $content . serialize($advisorData));
        $cacheKey = "ai_embodiment_semantic_{$contentHash}";
        
        // Check cache first for cost optimization
        if (Cache::has($cacheKey)) {
            Log::info('Using cached semantic analysis results');
            return Cache::get($cacheKey);
        }
        
        $advisorName = $advisorData['name'] ?? 'Unknown Advisor';
        $expertise = $advisorData['core_expertise_area'] ?? $advisorData['expertise_area'] ?? '';
        
        $prompt = $this->buildSemanticAnalysisPrompt($content, $advisorName, $expertise);
        $schema = $this->buildSemanticAnalysisSchema();
        
        try {
            $response = $this->llmService->generateText($prompt, [
                'model' => config('ai-models.purposes.pi_enhancement', 'anthropic/claude-3-5-sonnet'),
                'temperature' => 0.1, // Low temperature for consistent evaluation
                'response_format' => $schema,
                'system_message' => 'You are an expert in AI embodiment and prompt engineering effectiveness. Provide precise, actionable analysis.'
            ]);
            
            $result = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response from LLM: ' . json_last_error_msg());
            }
            
            // Cache results for cost optimization (1 hour)
            Cache::put($cacheKey, $result, 3600);
            
            Log::info('Semantic analysis completed successfully');
            
            return $result;
            
        } catch (\Exception $e) {
            Log::warning('Semantic analysis failed, using fallback scores', [
                'error' => $e->getMessage()
            ]);
            
            // Return fallback scores as specified in plan
            return [
                'voice_authenticity' => 75,
                'behavioral_triggers' => 70,
                'context_engineering' => 65,
                'analysis' => 'Semantic analysis unavailable - using conservative fallback scores',
                'recommendations' => ['LLM evaluation failed - consider manual review']
            ];
        }
    }

    /**
     * Build prompt for semantic analysis
     */
    protected function buildSemanticAnalysisPrompt(string $content, string $advisorName, string $expertise): string
    {
        return <<<PROMPT
Evaluate the AI embodiment quality of this Project Instruction for {$advisorName}, expert in {$expertise}.

Rate these aspects on a 0-100 scale:

**Voice Authenticity Preservation (30% weight):**
- Are there signature phrases that capture unique voice?
- Is personality consistent throughout?
- Does it feel authentically first-person?
- Are there contrarian positioning differentiators?
- Are there memorable interaction phrases?

**Behavioral Trigger Effectiveness (25% weight):**
- Are there clear behavioral directives?
- Are forbidden phrases/behaviors well-defined?
- Are there self-critique protocols for consistency?
- Are response format requirements enforceable?
- Are there internal processing decision guides?

**Context Engineering Quality (10% weight):**
- Quality of few-shot examples if present
- Chain-of-thought conditioning patterns
- Evidence-based prompting techniques
- Retrieval-augmented context instructions
- Constitutional AI constraint implementation

Content to analyze:
{$content}

Focus on how effectively this would trigger the right AI behaviors, not just technical compliance.
Provide specific recommendations for improvement.
PROMPT;
    }

    /**
     * Build JSON schema for semantic analysis response
     */
    protected function buildSemanticAnalysisSchema(): array
    {
        return [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'ai_embodiment_analysis',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'voice_authenticity' => [
                            'type' => 'number',
                            'minimum' => 0,
                            'maximum' => 100,
                            'description' => 'Score for voice authenticity preservation (0-100)'
                        ],
                        'behavioral_triggers' => [
                            'type' => 'number',
                            'minimum' => 0,
                            'maximum' => 100,
                            'description' => 'Score for behavioral trigger effectiveness (0-100)'
                        ],
                        'context_engineering' => [
                            'type' => 'number',
                            'minimum' => 0,
                            'maximum' => 100,
                            'description' => 'Score for context engineering quality (0-100)'
                        ],
                        'analysis' => [
                            'type' => 'string',
                            'minLength' => 50,
                            'maxLength' => 500,
                            'description' => 'Detailed explanation of the scores and assessment'
                        ],
                        'recommendations' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                                'minLength' => 10,
                                'maxLength' => 200
                            ],
                            'minItems' => 1,
                            'maxItems' => 5,
                            'description' => 'Specific improvement recommendations'
                        ]
                    ],
                    'required' => ['voice_authenticity', 'behavioral_triggers', 'context_engineering', 'analysis', 'recommendations'],
                    'additionalProperties' => false
                ]
            ]
        ];
    }

    /**
     * Get fallback scores when LLM evaluation fails
     */
    protected function getFallbackScores(string $error): array
    {
        return [
            'total_score' => 70.0,
            'valid' => false,
            'breakdown' => [
                'static_analysis' => [
                    'score' => 25.0,
                    'weight' => '35%',
                    'components' => [
                        'information_density' => ['score' => 70.0, 'weight' => '20%'],
                        'constitutional_ai' => ['score' => 65.0, 'weight' => '15%']
                    ]
                ],
                'semantic_analysis' => [
                    'score' => 45.0,
                    'weight' => '65%',
                    'components' => [
                        'voice_authenticity' => ['score' => 75.0, 'weight' => '30%'],
                        'behavioral_triggers' => ['score' => 70.0, 'weight' => '25%'],
                        'context_engineering' => ['score' => 65.0, 'weight' => '10%']
                    ]
                ]
            ],
            'analysis' => 'Evaluation failed - using conservative fallback scores: ' . $error,
            'recommendations' => [
                'LLM evaluation unavailable - manual review recommended',
                'Check system configuration and API connectivity',
                'Consider running analysis again when services are available'
            ],
            'metadata' => [
                'scorer_version' => '1.0.0',
                'scored_at' => now()->toIso8601String(),
                'approach' => 'fallback_mode',
                'static_vs_llm' => '35/65',
                'error' => $error
            ]
        ];
    }
}