<?php

namespace Tests\Feature;

use App\Services\AdvisorConfigService;
use App\Services\AdvisorGenerationService;
use App\Services\LLMService;
use App\Services\TemplateService;
use App\Services\Validation\AdvisorQualityService;
use App\Services\Validation\AIEmbodimentQualityScorer;
use App\Services\Validation\TemplateComplianceValidator;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class AdvisorGenerationTest extends TestCase
{
    protected AdvisorGenerationService $generationService;

    protected $mockLLMService;

    protected $mockTemplateService;

    protected $mockConfigService;

    protected $mockQualityService;

    protected $mockAIEmbodimentScorer;

    protected $mockTemplateComplianceValidator;

    protected function setUp(): void
    {
        parent::setUp();

        // Refresh migrations for test database
        $this->artisan('migrate:fresh');

        // Create mock services
        $this->mockLLMService = Mockery::mock(LLMService::class);
        $this->mockTemplateService = Mockery::mock(TemplateService::class);
        $this->mockConfigService = Mockery::mock(AdvisorConfigService::class);
        $this->mockQualityService = Mockery::mock(AdvisorQualityService::class);
        $this->mockAIEmbodimentScorer = Mockery::mock(AIEmbodimentQualityScorer::class);
        $this->mockTemplateComplianceValidator = Mockery::mock(TemplateComplianceValidator::class);

        // Bind mocks to container
        $this->app->instance(LLMService::class, $this->mockLLMService);
        $this->app->instance(TemplateService::class, $this->mockTemplateService);
        $this->app->instance(AdvisorConfigService::class, $this->mockConfigService);
        $this->app->instance(AdvisorQualityService::class, $this->mockQualityService);
        $this->app->instance(AIEmbodimentQualityScorer::class, $this->mockAIEmbodimentScorer);
        $this->app->instance(TemplateComplianceValidator::class, $this->mockTemplateComplianceValidator);

        // Create service with mocks
        $this->generationService = new AdvisorGenerationService(
            $this->mockTemplateService,
            $this->mockLLMService,
            $this->mockConfigService,
            $this->mockQualityService,
            $this->mockTemplateComplianceValidator,
            $this->mockAIEmbodimentScorer
        );

        // Setup storage fake
        Storage::fake('advisors');
    }

    public function test_deterministic_template_loading_and_variable_substitution()
    {
        // Arrange
        $advisorData = [
            'slug' => 'test-advisor',
            'name' => 'Test Advisor',
            'fullName' => 'Test Expert Advisor',
        ];

        $template = 'Hello {{advisor_name}}, your expertise is {{core_expertise}}';
        $mappedVars = [
            'advisor_name' => 'Test Expert Advisor',
            'core_expertise' => 'Strategic Advisory',
        ];

        $this->mockConfigService
            ->shouldReceive('getAdvisorConfig')
            ->once()
            ->with('test-advisor')
            ->andReturn($advisorData);

        $this->mockConfigService
            ->shouldReceive('mapVariables')
            ->once()
            ->andReturn($mappedVars);

        $this->mockTemplateService
            ->shouldReceive('loadTemplate')
            ->twice()
            ->andReturnValues([$template, $template]);



        $this->mockTemplateService
            ->shouldReceive('extractHTMLComments')
            ->once()
            ->andReturn([]);

        // Mock all LLM calls - need to handle both regular text and JSON responses
        $this->mockLLMService
            ->shouldReceive('generateText')
            ->withArgs(function($prompt, $options = []) {
                // For structured output (PK generation), return JSON
                if (isset($options['response_format'])) {
                    return true;
                }
                // For regular text (PI enhancement), accept any args
                return true;
            })
            ->andReturnUsing(function($prompt, $options = []) {
                if (isset($options['response_format'])) {
                    // Check if this is PI or PK generation based on prompt content
                    if (strpos($prompt, 'Project Instructions') !== false || strpos($prompt, 'template variables') !== false) {
                        // Return JSON for PI variable generation
                        return json_encode([
                            'chain_of_thought' => 'I think step by step to analyze problems systematically and develop evidence-based solutions.',
                            'few_shot_examples' => 'When I worked with TechCorp, I implemented a strategic transformation that resulted in 40% efficiency gains.',
                            'retrieval_context' => 'Always reference specific case studies and documented outcomes from past engagements.',
                            'constitutional_constraints' => 'Never provide advice without evidence. Always demand specific metrics and measurable outcomes.',
                            'operating_principles' => '- I always start with data-driven analysis\n- I challenge assumptions with evidence\n- I focus on measurable outcomes\n- I build sustainable solutions',
                            'communication_style' => 'Direct, evidence-based, and results-oriented',
                            'decision_making_approach' => 'Evidence-first methodology with measurable outcomes',
                            'key_phrases' => 'What\'s the evidence?, Show me the data, Measurable outcomes',
                            'emotional_characteristics' => 'Confident, analytical, challenging',
                            'unique_perspectives' => 'Complexity often masks simple solutions',
                            'core_expertise' => 'Strategic transformation and organizational change',
                            'related_expertise' => 'Change management and process optimization',
                            'scenarios_to_defer' => 'Technical implementation details and legal compliance',
                            'explicit_limitations' => 'Financial modeling and regulatory compliance'
                        ]);
                    } else {
                        // Return JSON for PK generation
                        return json_encode([
                            'voice_dna' => 'A strategic advisor who challenges conventional thinking and demands evidence-based decisions',
                            'voice_example_1' => 'When I worked with a Fortune 500 client, I discovered their biggest obstacle wasnt what they thought - it was their assumption that growth required complexity. We simplified their operations and saw 40% efficiency gains.',
                            'patterns_list' => '- Always question the obvious solution\n- Demand specific metrics before making decisions\n- Challenge assumptions with evidence\n- Focus on measurable outcomes\n- Seek root causes, not symptoms',
                            'anti_patterns_list' => '- Never accept vague objectives without clarification\n- Avoid solutions looking for problems\n- Dont ignore historical data and precedents\n- Never overcomplicate simple problems\n- Dont underestimate implementation complexity',
                            'analytical_tensions' => 'The paradox of seeking simplicity while acknowledging complexity creates the most powerful insights.',
                            'topic_1' => 'Strategic Planning',
                            'topic_2' => 'Decision Making', 
                            'topic_3' => 'Problem Solving',
                            'advisor_name' => 'Test Expert Advisor',
                            'date' => date('Y-m-d')
                        ]);
                    }
                } else {
                    // Return text for PI enhancement
                    return str_repeat('Generated content with enough text to pass validation and length checks for the advisor generation service. This content is specifically designed to be long enough to meet all validation requirements and provide meaningful test data. ', 15);
                }
            });

        // Mock quality scoring
        $this->mockQualityService
            ->shouldReceive('scorePI')
            ->once()
            ->andReturn(['percentage' => 85, 'valid' => true, 'issues' => []]);

        $this->mockQualityService
            ->shouldReceive('scorePK')
            ->once() // Only called once in the main flow
            ->andReturn(['percentage' => 90, 'valid' => true, 'issues' => []]);

        $this->mockQualityService
            ->shouldReceive('getValidationReport')
            ->once()
            ->andReturn(['summary' => ['overall_score' => 87.5]]);

        // Mock template compliance validation - needs to be called multiple times for retry logic
        $this->mockTemplateComplianceValidator
            ->shouldReceive('validate')
            ->atLeast(1)
            ->andReturn(['score' => 95]);

        // Mock AI embodiment scoring
        $this->mockAIEmbodimentScorer
            ->shouldReceive('scoreAIEmbodiment')
            ->once()
            ->andReturn([
                'total_score' => 85, 
                'valid' => true,
                'breakdown' => [
                    'static_analysis' => ['score' => 30],
                    'semantic_analysis' => ['score' => 55]
                ]
            ]);

        // Act
        $result = $this->generationService->generateAdvisor($advisorData);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Test Advisor', $result['advisor_name']);
        $this->assertArrayHasKey('quality', $result);
        $this->assertArrayHasKey('pi_content', $result);
        $this->assertArrayHasKey('pk_content', $result);
        $this->assertNull($result['exported_files']); // No files exported by default
        Storage::disk('advisors')->assertMissing('test-expert-advisor/PI.md');
        Storage::disk('advisors')->assertMissing('test-expert-advisor/PK.md');
    }

    public function test_llm_powered_pi_enhancement_with_mocked_responses()
    {
        // Arrange
        $advisorData = [
            'slug' => 'test-advisor',
            'name' => 'Test Advisor',
            'fullName' => 'Test Expert Advisor',
            'core_expertise_area' => 'Strategic Planning',
        ];

        $templateWithComments = '# Template\n<!-- Chain-of-thought example needed -->';
        $enhancedTemplate = '# Template\nThink step by step: 1) Identify the problem...';

        $this->mockConfigService
            ->shouldReceive('getAdvisorConfig')
            ->andReturn($advisorData);

        $this->mockConfigService
            ->shouldReceive('mapVariables')
            ->andReturn(['advisor_name' => 'Test Expert Advisor']);

        $this->mockTemplateService
            ->shouldReceive('loadTemplate')
            ->andReturn($templateWithComments);

        $this->mockTemplateService
            ->shouldReceive('extractVariables')
            ->andReturn(['advisor_name']);

        $this->mockTemplateService
            ->shouldReceive('substituteVariables')
            ->andReturn($templateWithComments);

        $this->mockTemplateService
            ->shouldReceive('extractHTMLComments')
            ->andReturn([
                ['full_match' => '<!-- Chain-of-thought example needed -->', 'content' => 'Chain-of-thought example needed'],
            ]);

        // Mock all LLM calls - handle both text and JSON responses
        $this->mockLLMService
            ->shouldReceive('generateText')
            ->withArgs(function($prompt, $options = []) {
                if (isset($options['response_format'])) {
                    return true; // structured output
                }
                return true; // regular text
            })
            ->andReturnUsing(function($prompt, $options = []) use ($enhancedTemplate) {
                if (isset($options['response_format'])) {
                    // Return valid JSON for structured output (PK generation)
                    return json_encode([
                        'voice_dna' => 'A strategic advisor who challenges conventional thinking',
                        'voice_example_1' => 'When I worked with a Fortune 500 client, I discovered their biggest obstacle wasnt what they thought - it was their assumption that growth required complexity.',
                        'patterns_list' => '- Always question the obvious solution\n- Demand specific metrics before making decisions\n- Challenge assumptions with evidence\n- Focus on measurable outcomes\n- Seek root causes, not symptoms',
                        'anti_patterns_list' => '- Never accept vague objectives without clarification\n- Avoid solutions looking for problems\n- Dont ignore historical data and precedents\n- Never overcomplicate simple problems\n- Dont underestimate implementation complexity',
                        'analytical_tensions' => 'The paradox of seeking simplicity while acknowledging complexity creates the most powerful insights.',
                        'topic_1' => 'Strategic Planning',
                        'topic_2' => 'Decision Making',
                        'topic_3' => 'Problem Solving',
                        'advisor_name' => 'Test Expert Advisor',
                        'date' => date('Y-m-d')
                    ]);
                } else {
                    // Return enhanced template for PI enhancement
                    if (str_contains($prompt, 'enhancing') || str_contains($prompt, 'template')) {
                        return $enhancedTemplate;
                    }
                    // For other text calls, return generic long content
                    return str_repeat('Generated content with enough text to pass validation and length checks for the advisor generation service. This content is specifically designed to be long enough to meet all validation requirements. ', 15);
                }
            });

        // Mock quality scoring
        $this->mockQualityService
            ->shouldReceive('scorePI')
            ->once()
            ->andReturn(['percentage' => 80, 'valid' => true, 'issues' => []]);

        $this->mockQualityService
            ->shouldReceive('scorePK')
            ->once()
            ->andReturn(['percentage' => 85, 'valid' => true, 'issues' => []]);

        $this->mockQualityService
            ->shouldReceive('getValidationReport')
            ->once()
            ->andReturn(['summary' => ['overall_score' => 82.5]]);

        // Mock template compliance validation - called multiple times for retry logic
        $this->mockTemplateComplianceValidator
            ->shouldReceive('validate')
            ->atLeast(1)
            ->andReturn(['score' => 95]);

        // Mock AI embodiment scoring
        $this->mockAIEmbodimentScorer
            ->shouldReceive('scoreAIEmbodiment')
            ->once()
            ->andReturn([
                'total_score' => 85, 
                'valid' => true,
                'breakdown' => [
                    'static_analysis' => ['score' => 30],
                    'semantic_analysis' => ['score' => 55]
                ]
            ]);

        // Act
        $result = $this->generationService->generateAdvisor($advisorData);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Think step by step', $result['pi_content']);
        $this->assertStringNotContainsString('<!--', $result['pi_content']);
    }

    public function test_pk_generation_with_mocked_llm_responses()
    {
        // Arrange
        $advisorData = [
            'slug' => 'cal-henderson',  // Keep temperature at 0.7 for stable test behavior
            'name' => 'Test Advisor',
            'fullName' => 'Test Expert Advisor',
        ];

        $pkTemplate = '# PK Template\n{{advisor_name}}\n\n{{analytical_tensions}}';

        $this->mockConfigService
            ->shouldReceive('getAdvisorConfig')
            ->andReturn($advisorData);

        $this->mockConfigService
            ->shouldReceive('mapVariables')
            ->andReturn(['advisor_name' => 'Test Expert Advisor']);

        // Mock PI generation
        $this->mockTemplateService
            ->shouldReceive('loadTemplate')
            ->with('meta_pi_template_v1')
            ->andReturn('PI template');

        $this->mockTemplateService
            ->shouldReceive('extractVariables')
            ->andReturn([]);

        $this->mockTemplateService
            ->shouldReceive('substituteVariables')
            ->andReturn('PI content');

        $this->mockTemplateService
            ->shouldReceive('extractHTMLComments')
            ->andReturn([]);

        // Mock PK generation
        $this->mockTemplateService
            ->shouldReceive('loadTemplate')
            ->with('meta_pk_template_v1')
            ->andReturn($pkTemplate);

        // Mock all LLM calls - handle both text and JSON responses
        $this->mockLLMService
            ->shouldReceive('generateText')
            ->withArgs(function($prompt, $options = []) {
                if (isset($options['response_format'])) {
                    return true; // structured output
                }
                return true; // regular text
            })
            ->andReturnUsing(function($prompt, $options = []) {
                if (isset($options['response_format'])) {
                    // Return valid JSON for structured output (PK generation)
                    return json_encode([
                        'advisor_name' => 'Test Expert Advisor',
                        'voice_dna' => 'A strategic advisor who challenges conventional thinking',
                        'patterns_list' => '- Always question the obvious solution\n- Demand specific metrics before making decisions',
                        'anti_patterns_list' => '- Never accept vague objectives without clarification\n- Avoid solutions looking for problems',
                        'analytical_tensions' => 'Detailed knowledge base for the advisor. The paradox of seeking simplicity while acknowledging complexity creates powerful insights.',
                        'date' => date('Y-m-d')
                    ]);
                } else {
                    // Return text for PI enhancement
                    return 'Detailed knowledge base for the advisor. '.str_repeat('Generated content with enough text to pass validation and length checks for the advisor generation service. This content is specifically designed to be long enough to meet all validation requirements and provide meaningful test data. ', 20);
                }
            });

        // Mock quality scoring
        $this->mockQualityService
            ->shouldReceive('scorePI')
            ->once()
            ->andReturn(['percentage' => 75, 'valid' => true, 'issues' => []]);

        $this->mockQualityService
            ->shouldReceive('scorePK')
            ->once()
            ->andReturn(['percentage' => 95, 'valid' => true, 'issues' => []]);

        $this->mockQualityService
            ->shouldReceive('getValidationReport')
            ->once()
            ->andReturn(['summary' => ['overall_score' => 85]]);

        // Mock template compliance validation - called multiple times for retry logic
        $this->mockTemplateComplianceValidator
            ->shouldReceive('validate')
            ->atLeast(1)
            ->andReturn(['score' => 95]);

        // Mock AI embodiment scoring
        $this->mockAIEmbodimentScorer
            ->shouldReceive('scoreAIEmbodiment')
            ->once()
            ->andReturn([
                'total_score' => 85, 
                'valid' => true,
                'breakdown' => [
                    'static_analysis' => ['score' => 30],
                    'semantic_analysis' => ['score' => 55]
                ]
            ]);

        // Act
        $result = $this->generationService->generateAdvisor($advisorData);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Detailed knowledge base', $result['pk_content']);
    }

    public function test_file_storage_in_correct_location_using_advisors_disk()
    {
        // Arrange
        $advisorData = [
            'name' => 'Test Advisor',
            'fullName' => 'Test Expert Advisor',
        ];

        $this->mockConfigService
            ->shouldReceive('mapVariables')
            ->andReturn(['advisor_name' => 'Test Expert Advisor']);

        $this->mockTemplateService
            ->shouldReceive('loadTemplate')
            ->andReturn('Template content');

        $this->mockTemplateService
            ->shouldReceive('extractVariables')
            ->andReturn([]);

        $this->mockTemplateService
            ->shouldReceive('substituteVariables')
            ->andReturn('Processed content');

        $this->mockTemplateService
            ->shouldReceive('extractHTMLComments')
            ->andReturn([]);

        // Mock all LLM calls - handle both text and JSON responses
        $this->mockLLMService
            ->shouldReceive('generateText')
            ->withArgs(function($prompt, $options = []) {
                if (isset($options['response_format'])) {
                    return true; // structured output
                }
                return true; // regular text
            })
            ->andReturnUsing(function($prompt, $options = []) {
                if (isset($options['response_format'])) {
                    // Return valid JSON for structured output (PK generation)
                    return json_encode([
                        'advisor_name' => 'Test Expert Advisor',
                        'voice_dna' => 'A strategic advisor who challenges conventional thinking',
                        'patterns_list' => '- Always question the obvious solution\n- Demand specific metrics before making decisions',
                        'anti_patterns_list' => '- Never accept vague objectives without clarification\n- Avoid solutions looking for problems',
                        'analytical_tensions' => 'The paradox of seeking simplicity while acknowledging complexity creates powerful insights.',
                        'date' => date('Y-m-d')
                    ]);
                } else {
                    // Return text for PI enhancement
                    return str_repeat('Generated content with enough text to pass validation and length checks for the advisor generation service. This content is specifically designed to be long enough to meet all validation requirements and provide meaningful test data. ', 20);
                }
            });

        $this->mockQualityService
            ->shouldReceive('scorePI')
            ->once()
            ->andReturn(['percentage' => 80, 'valid' => true, 'issues' => []]);

        $this->mockQualityService
            ->shouldReceive('scorePK')
            ->once()
            ->andReturn(['percentage' => 85, 'valid' => true, 'issues' => []]);

        $this->mockQualityService
            ->shouldReceive('getValidationReport')
            ->once()
            ->andReturn(['summary' => ['overall_score' => 82.5]]);

        // Mock template compliance validation - called multiple times for retry logic
        $this->mockTemplateComplianceValidator
            ->shouldReceive('validate')
            ->atLeast(1)
            ->andReturn(['score' => 95]);

        // Mock AI embodiment scoring
        $this->mockAIEmbodimentScorer
            ->shouldReceive('scoreAIEmbodiment')
            ->once()
            ->andReturn([
                'total_score' => 85, 
                'valid' => true,
                'breakdown' => [
                    'static_analysis' => ['score' => 30],
                    'semantic_analysis' => ['score' => 55]
                ]
            ]);

        // Act - Test file export functionality
        $result = $this->generationService->generateAdvisor($advisorData, null, true);

        // Assert
        $this->assertNotNull($result['exported_files']); // Files should be exported
        $this->assertArrayHasKey('pi', $result['exported_files']);
        $this->assertArrayHasKey('pk', $result['exported_files']);
        $this->assertArrayHasKey('metadata', $result['exported_files']);

        // Check files exist with correct naming format
        $this->assertStringContainsString('_PI.md', $result['exported_files']['pi']);
        $this->assertStringContainsString('_PK.md', $result['exported_files']['pk']);

        Storage::disk('advisors')->assertExists($result['exported_files']['pi']);
        Storage::disk('advisors')->assertExists($result['exported_files']['pk']);
        Storage::disk('advisors')->assertExists($result['exported_files']['metadata']);

        $metadata = json_decode(
            Storage::disk('advisors')->get($result['exported_files']['metadata']),
            true
        );

        $this->assertEquals('Test Expert Advisor', $metadata['name']);
        $this->assertArrayHasKey('quality', $metadata);
    }

    public function test_quality_validation_scoring_for_both_pi_and_pk()
    {
        // Arrange
        $advisorData = [
            'name' => 'Test Advisor',
            'fullName' => 'Test Expert Advisor',
        ];

        $piScore = [
            'percentage' => 75,
            'valid' => true,
            'issues' => ['Minor formatting issue'],
            'strengths' => ['Good structure'],
        ];

        $pkScore = [
            'percentage' => 85,
            'valid' => true,
            'issues' => [],
            'strengths' => ['Comprehensive knowledge'],
        ];

        $qualityReport = [
            'summary' => [
                'overall_score' => 80,
                'status' => 'PASSED',
                'recommendation' => 'Good quality - minor improvements recommended',
            ],
            'pi' => $piScore,
            'pk' => $pkScore,
        ];

        $this->mockConfigService
            ->shouldReceive('mapVariables')
            ->andReturn(['advisor_name' => 'Test Expert Advisor']);

        $this->mockTemplateService
            ->shouldReceive('loadTemplate')
            ->andReturn('Template');

        $this->mockTemplateService
            ->shouldReceive('extractVariables')
            ->andReturn([]);

        $this->mockTemplateService
            ->shouldReceive('substituteVariables')
            ->andReturn('Content');

        $this->mockTemplateService
            ->shouldReceive('extractHTMLComments')
            ->andReturn([]);

        // Mock all LLM calls - handle both text and JSON responses
        $this->mockLLMService
            ->shouldReceive('generateText')
            ->withArgs(function($prompt, $options = []) {
                if (isset($options['response_format'])) {
                    return true; // structured output
                }
                return true; // regular text
            })
            ->andReturnUsing(function($prompt, $options = []) {
                if (isset($options['response_format'])) {
                    // Return valid JSON for structured output (PK generation)
                    return json_encode([
                        'advisor_name' => 'Test Expert Advisor',
                        'voice_dna' => 'A strategic advisor who challenges conventional thinking',
                        'patterns_list' => '- Always question the obvious solution\n- Demand specific metrics before making decisions',
                        'anti_patterns_list' => '- Never accept vague objectives without clarification\n- Avoid solutions looking for problems',
                        'analytical_tensions' => 'The paradox of seeking simplicity while acknowledging complexity creates powerful insights.',
                        'date' => date('Y-m-d')
                    ]);
                } else {
                    // Return text for PI enhancement
                    return str_repeat('Generated content with enough text to pass validation and length checks for the advisor generation service. This content is specifically designed to be long enough to meet all validation requirements and provide meaningful test data. ', 20);
                }
            });

        $this->mockQualityService
            ->shouldReceive('scorePI')
            ->once()
            ->andReturn($piScore);

        $this->mockQualityService
            ->shouldReceive('scorePK')
            ->once()
            ->andReturn($pkScore);

        $this->mockQualityService
            ->shouldReceive('getValidationReport')
            ->once()
            ->with($piScore, $pkScore)
            ->andReturn($qualityReport);

        // Mock template compliance validation - called multiple times for retry logic
        $this->mockTemplateComplianceValidator
            ->shouldReceive('validate')
            ->atLeast(1)
            ->andReturn(['score' => 95]);

        // Mock AI embodiment scoring
        $this->mockAIEmbodimentScorer
            ->shouldReceive('scoreAIEmbodiment')
            ->once()
            ->andReturn([
                'total_score' => 85, 
                'valid' => true,
                'breakdown' => [
                    'static_analysis' => ['score' => 30],
                    'semantic_analysis' => ['score' => 55]
                ]
            ]);

        // Act
        $result = $this->generationService->generateAdvisor($advisorData);

        // Assert
        $this->assertEquals(80, $result['quality']['summary']['overall_score']);
        $this->assertEquals('PASSED', $result['quality']['summary']['status']);
        $this->assertEquals(75, $result['quality']['pi']['percentage']);
        $this->assertEquals(85, $result['quality']['pk']['percentage']);
    }

    public function test_error_handling_for_missing_variables_or_validation_failures()
    {
        // Arrange
        $advisorData = [
            'name' => 'Test Advisor',
        ];

        $this->mockConfigService
            ->shouldReceive('mapVariables')
            ->andReturn([]);

        $this->mockTemplateService
            ->shouldReceive('loadTemplate')
            ->andThrow(new \Exception('Template not found'));

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Template not found');

        $this->generationService->generateAdvisor($advisorData);
    }
}
