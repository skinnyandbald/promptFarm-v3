<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Advisor;
use App\Services\AdvisorGenerationService;
use App\Services\LLMService;
use App\Services\Validation\AdvisorQualityService;
use Illuminate\Support\Facades\Log;
use Mockery;

class CompareModelGenerationTest extends TestCase
{
    /**
     * Test PK generation quality across different models
     * 
     * This test compares:
     * 1. Deep Research model with web_search_preview tool
     * 2. GPT-4 Turbo with structured output
     * 3. Claude 3.5 Sonnet with structured output
     * 
     * @test
     */
    public function it_compares_pk_generation_quality_across_models()
    {
        // Sample advisor for testing
        $testAdvisor = Advisor::factory()->create([
            'key' => 'test-advisor',
            'full_name' => 'Sarah Chen',
            'tagline' => 'Product Strategy Expert',
            'core_expertise_area' => 'Product Management and Go-to-Market Strategy',
            'background_description' => 'Former VP Product at Stripe, led launches of 10+ major features',
            'notable_achievements' => 'Scaled Stripe Connect from 0 to $1B in payment volume',
            'decision_making_approach' => 'Data-driven with strong user empathy',
            'key_phrases_or_terminology' => 'product-market fit, user journey, growth loops',
        ]);

        $qualityService = app(AdvisorQualityService::class);
        $results = [];

        // Test 1: Deep Research Model (current approach)
        Log::info('Testing Deep Research Model');
        $deepResearchStart = microtime(true);
        
        $deepResearchPK = $this->generatePKWithModel($testAdvisor, 'o4-mini-deep-research', true);
        
        $deepResearchTime = microtime(true) - $deepResearchStart;
        $deepResearchScore = $qualityService->scorePK($deepResearchPK);
        
        $results['deep_research'] = [
            'model' => 'o4-mini-deep-research',
            'time' => $deepResearchTime,
            'score' => $deepResearchScore['percentage'],
            'issues' => $deepResearchScore['issues'],
            'strengths' => $deepResearchScore['strengths'],
            'cost_estimate' => $this->estimateCost($deepResearchPK, 'o4-mini-deep-research'),
            'has_web_search' => true,
        ];

        // Test 2: GPT-4 Turbo with Structured Output
        Log::info('Testing GPT-4 Turbo');
        $gpt4Start = microtime(true);
        
        $gpt4PK = $this->generatePKWithModel($testAdvisor, 'gpt-4-turbo-preview', false);
        
        $gpt4Time = microtime(true) - $gpt4Start;
        $gpt4Score = $qualityService->scorePK($gpt4PK);
        
        $results['gpt4_turbo'] = [
            'model' => 'gpt-4-turbo-preview',
            'time' => $gpt4Time,
            'score' => $gpt4Score['percentage'],
            'issues' => $gpt4Score['issues'],
            'strengths' => $gpt4Score['strengths'],
            'cost_estimate' => $this->estimateCost($gpt4PK, 'gpt-4-turbo-preview'),
            'has_web_search' => false,
        ];

        // Test 3: GPT-4o (current default for PK)
        Log::info('Testing GPT-4o');
        $gpt4oStart = microtime(true);
        
        $gpt4oPK = $this->generatePKWithModel($testAdvisor, 'gpt-4o', false);
        
        $gpt4oTime = microtime(true) - $gpt4oStart;
        $gpt4oScore = $qualityService->scorePK($gpt4oPK);
        
        $results['gpt4o'] = [
            'model' => 'gpt-4o',
            'time' => $gpt4oTime,
            'score' => $gpt4oScore['percentage'],
            'issues' => $gpt4oScore['issues'],
            'strengths' => $gpt4oScore['strengths'],
            'cost_estimate' => $this->estimateCost($gpt4oPK, 'gpt-4o'),
            'has_web_search' => false,
        ];

        // Generate comparison report
        $this->generateComparisonReport($results);

        // Assert that non-deep-research models can achieve 80%+ quality
        $this->assertGreaterThanOrEqual(75, $results['gpt4_turbo']['score'], 
            'GPT-4 Turbo should achieve at least 75% quality score');
        
        $this->assertGreaterThanOrEqual(75, $results['gpt4o']['score'], 
            'GPT-4o should achieve at least 75% quality score');

        // Compare performance metrics
        $this->assertLessThan($results['deep_research']['time'], $results['gpt4_turbo']['time'],
            'GPT-4 Turbo should be faster than deep research');
        
        $this->assertLessThan($results['deep_research']['cost_estimate'], $results['gpt4_turbo']['cost_estimate'],
            'GPT-4 Turbo should be cheaper than deep research');
    }

    /**
     * Generate PK with specific model
     */
    private function generatePKWithModel(Advisor $advisor, string $model, bool $useWebSearch): string
    {
        $service = app(AdvisorGenerationService::class);
        
        // Mock the LLMService to use specific model
        $llmService = Mockery::mock(LLMService::class)->makePartial();
        
        if ($useWebSearch) {
            // For deep research, include web search in the prompt
            $llmService->shouldReceive('generateText')
                ->andReturnUsing(function ($prompt, $options) use ($model) {
                    // Add instruction to research the person
                    $researchPrompt = "Use web search to research information about this advisor and their expertise. " . $prompt;
                    
                    // Call actual service with deep research
                    return app(LLMService::class)->generateText($researchPrompt, [
                        'model' => $model,
                        'temperature' => 0.7,
                        'max_tokens' => 8000,
                    ]);
                });
        } else {
            // For standard models, use structured output
            $llmService->shouldReceive('generateText')
                ->andReturnUsing(function ($prompt, $options) use ($model) {
                    // Add structured output instructions
                    $structuredPrompt = $prompt . "\n\nIMPORTANT: Generate exactly the required sections with proper markdown headers. Ensure all sections are complete and detailed.";
                    
                    return app(LLMService::class)->generateText($structuredPrompt, [
                        'model' => $model,
                        'temperature' => 0.7,
                        'max_tokens' => 8000,
                        'response_format' => ['type' => 'text'], // Structured output
                    ]);
                });
        }
        
        // Inject mocked service
        app()->instance(LLMService::class, $llmService);
        
        // Generate PK
        $result = $service->generateAdvisor($advisor, 'v1');
        
        return $result['pk_content'];
    }

    /**
     * Estimate cost for generation
     */
    private function estimateCost(string $content, string $model): float
    {
        $tokens = strlen($content) / 4; // Rough estimate
        
        $pricing = [
            'o4-mini-deep-research' => 0.03, // per 1K tokens
            'gpt-4-turbo-preview' => 0.01,
            'gpt-4o' => 0.005,
        ];
        
        return ($tokens / 1000) * ($pricing[$model] ?? 0.01);
    }

    /**
     * Generate comparison report
     */
    private function generateComparisonReport(array $results): void
    {
        $report = "# PK Generation Model Comparison Report\n\n";
        $report .= "## Test Results\n\n";
        
        foreach ($results as $key => $result) {
            $report .= "### {$result['model']}\n";
            $report .= "- **Quality Score**: {$result['score']}%\n";
            $report .= "- **Generation Time**: " . round($result['time'], 2) . " seconds\n";
            $report .= "- **Estimated Cost**: $" . round($result['cost_estimate'], 4) . "\n";
            $report .= "- **Web Search**: " . ($result['has_web_search'] ? 'Yes' : 'No') . "\n";
            
            if (!empty($result['strengths'])) {
                $report .= "- **Strengths**:\n";
                foreach ($result['strengths'] as $strength) {
                    $report .= "  - {$strength}\n";
                }
            }
            
            if (!empty($result['issues'])) {
                $report .= "- **Issues**:\n";
                foreach ($result['issues'] as $issue) {
                    $report .= "  - {$issue}\n";
                }
            }
            
            $report .= "\n";
        }
        
        // Save report
        file_put_contents(
            storage_path('app/test-results/pk-model-comparison-' . date('Y-m-d-His') . '.md'),
            $report
        );
        
        Log::info('Model comparison report generated', ['results' => $results]);
    }
}