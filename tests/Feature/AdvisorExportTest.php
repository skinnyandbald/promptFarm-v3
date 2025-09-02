<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\User;
use App\Models\Advisor;
use App\Models\PlayerContext;
use App\Services\PlayerContextService;
use App\Services\SimpleQualityService;
use App\Services\AdvisorGenerationService;
use App\Services\Validation\AdvisorQualityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * Testing Framework for Two-Stage Implementation
 * 
 * Tests Stage 1 (Standalone) and Stage 2 (PlayerContext) functionality.
 */
class AdvisorExportTest extends TestCase
{
    use RefreshDatabase;

    protected PlayerContextService $playerContextService;
    protected SimpleQualityService $qualityService;
    protected AdvisorGenerationService $generationService;
    protected Advisor $testAdvisor;
    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->testUser = User::factory()->create();
        $this->testAdvisor = Advisor::factory()->create([
            'name' => 'Test Advisor',
            'core_expertise_area' => 'Marketing Strategy',
            'key_phrases_or_terminology' => ['ROI', 'brand equity', 'market penetration']
        ]);
        
        // Set up services
        $this->playerContextService = app(PlayerContextService::class);
        $this->qualityService = app(SimpleQualityService::class);
        $this->generationService = app(AdvisorGenerationService::class);
    }

    /**
     * Stage 1 Tests: Standalone Advisor Generation
     */
    
    #[Test]
    public function test_stage1_pk_generation_quality_improvements()
    {
        // Mock the LLM service to return content with good specificity
        $mockContent = $this->generateMockPKContent(true); // Good content
        
        $this->mock('App\Services\LLMService', function ($mock) use ($mockContent) {
            $mock->shouldReceive('generateText')
                ->andReturn($mockContent);
        });
        
        // Generate advisor
        $result = $this->generationService->generateAdvisor($this->testAdvisor);
        
        // Assert quality improvements
        $this->assertArrayHasKey('quality', $result);
        $quality = $result['quality'];
        
        // Target: 85%+ quality score
        $this->assertGreaterThanOrEqual(85, $quality['summary']['overall_score']);
        
        // Check for specificity
        $this->assertStringNotContainsString('[company]', $result['pk_content']);
        $this->assertStringNotContainsString('{{', $result['pk_content']);
        
        // Verify voice consistency
        $this->assertStringContainsString('I ', $result['pk_content']);
        $this->assertStringContainsString('my ', $result['pk_content']);
        
        Log::info('Stage 1 quality test passed', [
            'score' => $quality['overall_percentage']
        ]);
    }
    
    #[Test]
    public function test_stage1_voice_consistency_and_specificity()
    {
        $mockPK = <<<PK
# Project Knowledge

## Battle-Tested Frameworks

When I led the turnaround at Domino's Pizza in 2009, we faced a 16% year-over-year decline. I implemented the "Pizza Tracker" technology system that gave customers real-time visibility. Result: 14% same-store growth within 18 months.

At Nike (2015-2017), I challenged the conventional wisdom that digital marketing should prioritize reach. Instead, I focused on engagement depth, resulting in a 47% increase in customer lifetime value.

## Signature Methodologies

My "Triple-R Framework" (Relevance, Resonance, Revenue) has driven success at Apple, Tesla, and Coca-Cola. Each campaign must pass all three filters before launch.
PK;

        $this->mock('App\Services\LLMService', function ($mock) use ($mockPK) {
            $mock->shouldReceive('generateText')
                ->andReturn($mockPK);
        });
        
        $result = $this->generationService->generateAdvisor($this->testAdvisor);
        
        // Check for specific company names
        $this->assertStringContainsString("Domino's Pizza", $result['pk_content']);
        $this->assertStringContainsString('Nike', $result['pk_content']);
        $this->assertStringContainsString('Apple', $result['pk_content']);
        
        // Check for exact metrics
        $this->assertMatchesRegularExpression('/\d+%/', $result['pk_content']);
        
        // Check first-person voice
        $voiceCount = substr_count($result['pk_content'], 'I ') + 
                     substr_count($result['pk_content'], 'my ') + 
                     substr_count($result['pk_content'], "I've");
        $this->assertGreaterThan(5, $voiceCount, 'Should have strong first-person voice');
    }
    
    #[Test]
    public function test_stage1_cost_and_speed_improvements()
    {
        $startTime = microtime(true);
        
        // Mock optimized model
        $this->mock('App\Services\LLMService', function ($mock) {
            $mock->shouldReceive('generateText')
                ->with(Mockery::on(function ($prompt) {
                    return str_contains($prompt, 'SPECIFICITY IS MANDATORY');
                }), Mockery::on(function ($options) {
                    // Verify optimized model settings
                    return $options['model'] === 'gpt-4-turbo-preview' &&
                           $options['temperature'] === 0.4;
                }))
                ->andReturn($this->generateMockPKContent(true));
        });
        
        $result = $this->generationService->generateAdvisor($this->testAdvisor);
        
        $executionTime = microtime(true) - $startTime;
        
        // Should be fast (under 10 seconds for test)
        $this->assertLessThan(10, $executionTime);
        
        // Verify model configuration
        $this->assertTrue($result['success']);
    }
    
    #[Test]
    public function test_stage1_template_artifact_elimination()
    {
        $mockPKWithArtifacts = <<<PK
# Project Knowledge

## Battle-Tested Frameworks

<!-- This should be replaced -->
When working with [company], I achieved [result].
{{variable_name}}
INSERT_CAMPAIGN_HERE

## Real Content
At Apple, I led the Think Different campaign.
PK;

        $this->mock('App\Services\LLMService', function ($mock) use ($mockPKWithArtifacts) {
            $mock->shouldReceive('generateText')
                ->once()
                ->andReturn($mockPKWithArtifacts)
                ->shouldReceive('generateText')
                ->andReturn($this->generateMockPKContent(true)); // Retry with good content
        });
        
        $result = $this->generationService->generateAdvisor($this->testAdvisor);
        
        // Should not contain artifacts
        $this->assertStringNotContainsString('<!--', $result['pk_content']);
        $this->assertStringNotContainsString('[company]', $result['pk_content']);
        $this->assertStringNotContainsString('{{', $result['pk_content']);
        $this->assertStringNotContainsString('INSERT_', $result['pk_content']);
    }
    
    #[Test]
    public function test_stage1_export_format_quality()
    {
        $this->actingAs($this->testUser);
        
        // Test full export
        $response = $this->postJson("/api/advisors/{$this->testAdvisor->id}/export", [
            'format' => 'full',
            'include_quality' => true
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'advisor',
            'stage',
            'export',
            'quality' => [
                'overall_score',
                'pi_score',
                'pk_score'
            ],
            'metadata'
        ]);
        
        $this->assertEquals('Stage 1 - Standalone', $response->json('stage'));
        
        // Test condensed export
        $response = $this->postJson("/api/advisors/{$this->testAdvisor->id}/export", [
            'format' => 'condensed'
        ]);
        
        $response->assertStatus(200);
        $exportLength = strlen($response->json('export'));
        $this->assertLessThan(60000, $exportLength, 'Condensed export should fit ChatGPT limits');
    }

    /**
     * Stage 2 Tests: PlayerContext Integration
     */
    
    #[Test]
    public function test_stage2_pi_level_personalization()
    {
        $this->actingAs($this->testUser);
        
        // Create player context
        $playerContext = PlayerContext::create([
            'user_id' => $this->testUser->id,
            'industry' => 'SaaS',
            'business_type' => 'Startup',
            'communication_style' => 'analytical',
            'detail_level' => 'high',
            'current_challenges' => ['user retention', 'pricing strategy'],
            'goals' => ['reach $1M ARR', 'expand to enterprise']
        ]);
        
        // Generate with player context
        $result = $this->playerContextService->generatePersonalizedAdvisor(
            $this->testAdvisor,
            $playerContext,
            true // Include player context
        );
        
        $this->assertTrue($result['personalized']);
        $this->assertNotNull($result['player_context_summary']);
        
        // PI should be adapted
        $piContent = $result['personalized_pi'];
        $this->assertStringContainsString('SaaS', $result['player_context_summary']);
        $this->assertStringContainsString('analytical', $result['export_package']['export_metadata']['stage']);
    }
    
    #[Test]
    public function test_stage2_context_aware_responses()
    {
        $playerContext = PlayerContext::factory()->create([
            'user_id' => $this->testUser->id,
            'industry' => 'E-commerce',
            'business_type' => 'Enterprise',
            'example_preference' => 'industry_specific'
        ]);
        
        $result = $this->playerContextService->generatePersonalizedAdvisor(
            $this->testAdvisor,
            $playerContext,
            true
        );
        
        // Check context integration
        $this->assertEquals('Stage 2 - PlayerContext', $result['export_package']['export_metadata']['stage']);
        $this->assertStringContainsString('E-commerce', $result['player_context_summary']);
    }
    
    #[Test]
    public function test_stage2_pk_level_context_filtering()
    {
        $playerContext = PlayerContext::factory()->create([
            'user_id' => $this->testUser->id,
            'industry' => 'Healthcare',
            'framework_preferences' => ['Lean', 'Agile']
        ]);
        
        $mockPersonalizedPK = <<<PK
# Project Knowledge - Healthcare Focus

## Battle-Tested Frameworks (Healthcare Relevant)

My work at Mayo Clinic (2019) revolutionized patient engagement using Lean principles. We reduced appointment wait times by 43% while improving satisfaction scores.

## Agile Marketing at Cleveland Clinic

Using Agile sprints, we launched 12 campaigns in 6 months, each iteration improving based on real patient feedback.
PK;

        $this->mock('App\Services\LLMService', function ($mock) use ($mockPersonalizedPK) {
            $mock->shouldReceive('generateText')
                ->andReturn($mockPersonalizedPK);
        });
        
        $result = $this->playerContextService->generatePersonalizedAdvisor(
            $this->testAdvisor,
            $playerContext,
            true
        );
        
        // PK should emphasize healthcare and preferred frameworks
        $pkContent = $result['personalized_pk'];
        $this->assertStringContainsString('Healthcare', $pkContent);
        $this->assertStringContainsString('Lean', $pkContent);
        $this->assertStringContainsString('Agile', $pkContent);
    }
    
    #[Test]
    public function test_stage2_personalized_export_quality()
    {
        $this->actingAs($this->testUser);
        
        $playerContext = PlayerContext::factory()->create([
            'user_id' => $this->testUser->id,
            'industry' => 'FinTech',
            'business_type' => 'Startup'
        ]);
        
        $response = $this->postJson("/api/advisors/{$this->testAdvisor->id}/export-personalized", [
            'format' => 'full',
            'include_player_context' => true,
            'include_quality' => true
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'stage' => 'Stage 2 - PlayerContext',
            'personalized' => true
        ]);
        
        $this->assertNotNull($response->json('context_summary'));
        $this->assertStringContainsString('FinTech', $response->json('context_summary'));
    }

    /**
     * Export Quality Tests
     */
    
    #[Test]
    public function test_export_chatgpt_compatibility()
    {
        $result = $this->playerContextService->generatePersonalizedAdvisor(
            $this->testAdvisor,
            null,
            false
        );
        
        $fullExport = $result['export_package']['full_export'];
        $condensedExport = $result['export_package']['condensed_export'];
        
        // Check markdown formatting
        $this->assertStringContainsString('# ', $fullExport);
        $this->assertStringContainsString('## ', $fullExport);
        
        // Check size limits
        $this->assertLessThanOrEqual(100000, strlen($fullExport), 'Full export too large');
        $this->assertLessThanOrEqual(60000, strlen($condensedExport), 'Condensed export too large');
        
        // Check instructions
        $instructions = $result['export_package']['setup_instructions'];
        $this->assertStringContainsString('ChatGPT', $instructions);
        $this->assertStringContainsString('Setup', $instructions);
    }
    
    #[Test]
    public function test_export_file_download()
    {
        $this->actingAs($this->testUser);
        
        $response = $this->postJson("/api/advisors/{$this->testAdvisor->id}/download", [
            'format' => 'full'
        ]);
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/markdown');
        $response->assertHeader('Content-Disposition');
    }

    /**
     * Simple Quality Measurement Tests
     */
    
    #[Test]
    public function test_generation_time_quality_scoring()
    {
        $piContent = $this->generateMockPIContent();
        $pkContent = $this->generateMockPKContent(true);
        
        $score = $this->qualityService->scoreGeneratedAdvisor($piContent, $pkContent);
        
        $this->assertArrayHasKey('overall_score', $score);
        $this->assertArrayHasKey('pi_score', $score);
        $this->assertArrayHasKey('pk_score', $score);
        $this->assertArrayHasKey('stage', $score);
        
        // Check stage determination
        if ($score['overall_score'] >= 85) {
            $this->assertEquals('excellent', $score['stage']);
        } elseif ($score['overall_score'] >= 70) {
            $this->assertEquals('good', $score['stage']);
        }
    }
    
    #[Test]
    public function test_quality_trend_tracking()
    {
        // Generate multiple advisors to create trend data
        for ($i = 0; $i < 5; $i++) {
            $score = 70 + ($i * 5); // Increasing scores
            $this->createQualityMetric($score);
        }
        
        $trend = $this->qualityService->getQualityTrend();
        
        $this->assertIsArray($trend);
        $this->assertNotEmpty($trend);
        
        // Verify increasing trend
        if (count($trend) > 1) {
            $firstScore = $trend[0]['score'];
            $lastScore = end($trend)['score'];
            $this->assertGreaterThanOrEqual($firstScore, $lastScore);
        }
    }
    
    #[Test]
    public function test_feedback_collection_and_storage()
    {
        $this->qualityService->collectFeedback(
            $this->testAdvisor->id,
            5,
            'Excellent advisor! Very helpful.'
        );
        
        $satisfaction = $this->qualityService->getUserSatisfactionScore();
        
        $this->assertNotNull($satisfaction);
        $this->assertGreaterThanOrEqual(1, $satisfaction);
        $this->assertLessThanOrEqual(5, $satisfaction);
    }
    
    #[Test]
    public function test_quality_alert_thresholds()
    {
        // Test low quality alert
        $lowQualityScore = [
            'overall_score' => 45,
            'pi_score' => 40,
            'pk_score' => 50,
            'total_issues' => 15
        ];
        
        // This should trigger an alert
        $this->expectsLogMessage('quality', 'warning', 'Quality critical alert');
        
        $this->invokeMethod(
            $this->qualityService,
            'checkQualityThresholds',
            [$lowQualityScore]
        );
    }

    /**
     * Integration Tests
     */
    
    #[Test]
    public function test_seamless_stage1_to_stage2_progression()
    {
        // Stage 1: Generate standalone advisor
        $stage1Result = $this->playerContextService->generatePersonalizedAdvisor(
            $this->testAdvisor,
            null,
            false
        );
        
        $this->assertFalse($stage1Result['personalized']);
        $this->assertNull($stage1Result['player_context_summary']);
        
        // Create player context
        $playerContext = PlayerContext::factory()->create([
            'user_id' => $this->testUser->id
        ]);
        
        // Stage 2: Enhance with player context
        $stage2Result = $this->playerContextService->generatePersonalizedAdvisor(
            $this->testAdvisor,
            $playerContext,
            true
        );
        
        $this->assertTrue($stage2Result['personalized']);
        $this->assertNotNull($stage2Result['player_context_summary']);
        
        // Quality should be maintained or improved
        if (isset($stage1Result['export_package']['quality_score']) && 
            isset($stage2Result['export_package']['quality_score'])) {
            $this->assertGreaterThanOrEqual(
                $stage1Result['export_package']['quality_score']['overall_score'] - 5,
                $stage2Result['export_package']['quality_score']['overall_score'],
                'Personalization should not degrade quality significantly'
            );
        }
    }
    
    #[Test]
    public function test_export_workflow_end_to_end()
    {
        $this->actingAs($this->testUser);
        
        // 1. Save player context
        $contextResponse = $this->postJson('/api/player-context', [
            'industry' => 'EdTech',
            'business_type' => 'Startup',
            'communication_style' => 'collaborative'
        ]);
        
        $contextResponse->assertStatus(200);
        
        // 2. Export with context
        $exportResponse = $this->postJson("/api/advisors/{$this->testAdvisor->id}/export-personalized", [
            'format' => 'full',
            'include_player_context' => true
        ]);
        
        $exportResponse->assertStatus(200);
        $exportResponse->assertJson([
            'success' => true,
            'personalized' => true
        ]);
        
        // 3. Submit feedback
        $feedbackResponse = $this->postJson("/api/advisors/{$this->testAdvisor->id}/feedback", [
            'rating' => 5,
            'feedback' => 'Great personalization!'
        ]);
        
        $feedbackResponse->assertStatus(200);
        
        // 4. Check metrics
        $metricsResponse = $this->getJson('/api/quality-dashboard');
        $metricsResponse->assertStatus(200);
        $metricsResponse->assertJsonStructure([
            'success',
            'metrics' => [
                'current_average',
                'generation_success_rate'
            ]
        ]);
    }

    /**
     * Helper Methods
     */
    
    protected function generateMockPIContent(): string
    {
        return <<<PI
# Project Instructions

## Core Operating Principles
1. Always demand specificity in strategy discussions
2. Challenge assumptions with data
3. Focus on measurable outcomes

## Communication Framework
Direct, analytical approach with emphasis on ROI and results.

## Primary Expertise
Marketing strategy, brand development, growth hacking.
PI;
    }
    
    protected function generateMockPKContent(bool $highQuality = false): string
    {
        if ($highQuality) {
            return <<<PK
# Project Knowledge

## Battle-Tested Frameworks

When I led the turnaround at Domino's Pizza in 2009, we faced a 16% year-over-year decline. I implemented the "Pizza Tracker" technology, resulting in 14% same-store growth in 18 months.

At Nike (2015-2017), I increased customer lifetime value by 47% through engagement-focused digital marketing.

## Signature Methodologies

My Triple-R Framework (Relevance, Resonance, Revenue) has driven success at Apple, Tesla, and Coca-Cola.
PK;
        } else {
            return <<<PK
# Project Knowledge

## Frameworks

I worked with [company] to achieve [result]. Using my framework, businesses can improve performance.

## Methodologies

My approach helps companies succeed.
PK;
        }
    }
    
    protected function createQualityMetric(float $score): void
    {
        \DB::table('advisor_quality_metrics')->insert([
            'overall_score' => $score,
            'pi_score' => $score - 5,
            'pk_score' => $score + 5,
            'stage' => $score >= 85 ? 'excellent' : 'good',
            'total_issues' => max(0, 100 - $score),
            'created_at' => now()
        ]);
    }
    
    protected function invokeMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
    
    protected function expectsLogMessage(string $channel, string $level, string $message): void
    {
        Log::shouldReceive('channel')
            ->with($channel)
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive($level)
            ->withArgs(function ($logMessage) use ($message) {
                return str_contains($logMessage, $message);
            })
            ->once();
    }
}