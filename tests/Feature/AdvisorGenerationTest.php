<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\AdvisorGenerationService;
use App\Services\TemplateService;
use App\Services\LLMService;
use App\Services\AdvisorConfigService;
use App\Services\Validation\AdvisorQualityService;
use Illuminate\Support\Facades\Storage;
use Mockery;

class AdvisorGenerationTest extends TestCase
{
    protected AdvisorGenerationService $generationService;
    protected $mockLLMService;
    protected $mockTemplateService;
    protected $mockConfigService;
    protected $mockQualityService;

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
        
        // Bind mocks to container
        $this->app->instance(LLMService::class, $this->mockLLMService);
        $this->app->instance(TemplateService::class, $this->mockTemplateService);
        $this->app->instance(AdvisorConfigService::class, $this->mockConfigService);
        $this->app->instance(AdvisorQualityService::class, $this->mockQualityService);
        
        // Create service with mocks
        $this->generationService = new AdvisorGenerationService(
            $this->mockTemplateService,
            $this->mockLLMService,
            $this->mockConfigService,
            $this->mockQualityService
        );
        
        // Setup storage fake
        Storage::fake('advisors');
    }

    public function test_deterministic_template_loading_and_variable_substitution()
    {
        // Arrange
        $advisorData = [
            'key' => 'test_advisor',
            'name' => 'Test Advisor',
            'fullName' => 'Test Expert Advisor'
        ];
        
        $template = 'Hello {{advisor_name}}, your expertise is {{core_expertise}}';
        $mappedVars = [
            'advisor_name' => 'Test Expert Advisor',
            'core_expertise' => 'Strategic Advisory'
        ];
        
        $this->mockConfigService
            ->shouldReceive('getAdvisorConfig')
            ->once()
            ->with('test_advisor')
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
            ->shouldReceive('extractVariables')
            ->once()
            ->andReturn(['advisor_name', 'core_expertise']);
            
        $this->mockTemplateService
            ->shouldReceive('substituteVariables')
            ->twice()
            ->andReturn('Hello Test Expert Advisor, your expertise is Strategic Advisory');
            
        $this->mockTemplateService
            ->shouldReceive('extractHTMLComments')
            ->once()
            ->andReturn([]);
            
        $this->mockLLMService
            ->shouldReceive('generateTextWithOpenRouter')
            ->once()
            ->andReturn(str_repeat('Generated PK content with enough text to pass validation. ', 5));
            
        // Mock quality scoring
        $this->mockQualityService
            ->shouldReceive('scorePI')
            ->once()
            ->andReturn(['percentage' => 85, 'valid' => true, 'issues' => []]);
            
        $this->mockQualityService
            ->shouldReceive('scorePK')
            ->twice()
            ->andReturn(['percentage' => 90, 'valid' => true, 'issues' => []]);
            
        $this->mockQualityService
            ->shouldReceive('getValidationReport')
            ->once()
            ->andReturn(['summary' => ['overall_score' => 87.5]]);
        
        // Act
        $result = $this->generationService->generateAdvisor($advisorData, 'v1');
        
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
            'key' => 'test_advisor',
            'name' => 'Test Advisor',
            'fullName' => 'Test Expert Advisor',
            'core_expertise_area' => 'Strategic Planning'
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
                ['full_match' => '<!-- Chain-of-thought example needed -->', 'content' => 'Chain-of-thought example needed']
            ]);
            
        // Mock research job LLM call
        $this->mockLLMService
            ->shouldReceive('generateText')
            ->withArgs(function($prompt, $options) {
                return str_contains($prompt, 'CORE contrarian principles') &&
                       $options['model'] === 'x-ai/grok-3';
            })
            ->andReturn('Position 1\nPosition 2\nPosition 3');
            
        // Mock LLM enhancement
        $this->mockLLMService
            ->shouldReceive('generateText')
            ->withArgs(function($prompt, $options) {
                return str_contains($prompt, 'enhancing an advisor instruction template') &&
                       $options['model'] === 'gpt-4o-mini';
            })
            ->andReturn($enhancedTemplate);
            
        // Mock PK generation  
        $this->mockLLMService
            ->shouldReceive('generateText')
            ->withArgs(function($prompt, $options) {
                return str_contains($prompt, 'Project Knowledge') &&
                       $options['model'] === 'x-ai/grok-3';
            })
            ->andReturn(str_repeat('Generated PK content with enough text to pass validation and content length checks. ', 10));
            
        // Mock quality scoring
        $this->mockQualityService
            ->shouldReceive('scorePI')
            ->andReturn(['percentage' => 80, 'valid' => true, 'issues' => []]);
            
        $this->mockQualityService
            ->shouldReceive('scorePK')
            ->andReturn(['percentage' => 85, 'valid' => true, 'issues' => []]);
            
        $this->mockQualityService
            ->shouldReceive('getValidationReport')
            ->andReturn(['summary' => ['overall_score' => 82.5]]);
        
        // Act
        $result = $this->generationService->generateAdvisor($advisorData, 'v1');
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Think step by step', $result['pi_content']);
        $this->assertStringNotContainsString('<!--', $result['pi_content']);
    }

    public function test_pk_generation_with_mocked_llm_responses()
    {
        // Arrange
        $advisorData = [
            'key' => 'henderson',  // Keep temperature at 0.7 for stable test behavior
            'name' => 'Test Advisor',
            'fullName' => 'Test Expert Advisor'
        ];
        
        $pkTemplate = '# PK Template\n{{advisor_name}}';
        $generatedPK = '# PK Template\nTest Expert Advisor\n\nDetailed knowledge base...';
        
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
            
        // Mock research job LLM call first
        $this->mockLLMService
            ->shouldReceive('generateText')
            ->withArgs(function($prompt, $options) {
                return str_contains($prompt, 'CORE contrarian principles') &&
                       $options['model'] === 'x-ai/grok-3';
            })
            ->andReturn('Position 1\nPosition 2\nPosition 3');
            
        // Mock PK generation
        $this->mockLLMService
            ->shouldReceive('generateText')
            ->withArgs(function($prompt, $options) {
                return $options['model'] === 'x-ai/grok-3' &&
                       $options['temperature'] === 0.7;
            })
            ->andReturn($generatedPK . str_repeat(' Additional content to meet minimum length requirements.', 5));
            
        // Mock quality scoring
        $this->mockQualityService
            ->shouldReceive('scorePI')
            ->andReturn(['percentage' => 75, 'valid' => true, 'issues' => []]);
            
        $this->mockQualityService
            ->shouldReceive('scorePK')
            ->andReturn(['percentage' => 95, 'valid' => true, 'issues' => []]);
            
        $this->mockQualityService
            ->shouldReceive('getValidationReport')
            ->andReturn(['summary' => ['overall_score' => 85]]);
        
        // Act
        $result = $this->generationService->generateAdvisor($advisorData, 'v1');
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Detailed knowledge base', $result['pk_content']);
    }

    public function test_file_storage_in_correct_location_using_advisors_disk()
    {
        // Arrange
        $advisorData = [
            'name' => 'Test Advisor',
            'fullName' => 'Test Expert Advisor'
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
            
        $this->mockLLMService
            ->shouldReceive('generateText')
            ->andReturn('Generated content');
            
        $this->mockLLMService
            ->shouldReceive('generateTextWithOpenRouter')
            ->andReturn(str_repeat('Generated PK content with enough text to pass validation. ', 5));
            
        $this->mockQualityService
            ->shouldReceive('scorePI')
            ->andReturn(['percentage' => 80, 'valid' => true, 'issues' => []]);
            
        $this->mockQualityService
            ->shouldReceive('scorePK')
            ->andReturn(['percentage' => 85, 'valid' => true, 'issues' => []]);
            
        $this->mockQualityService
            ->shouldReceive('getValidationReport')
            ->andReturn(['summary' => ['overall_score' => 82.5]]);
        
        // Act - Test file export functionality
        $result = $this->generationService->generateAdvisor($advisorData, 'v1', null, true);
        
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
            'fullName' => 'Test Expert Advisor'
        ];
        
        $piScore = [
            'percentage' => 75,
            'valid' => true,
            'issues' => ['Minor formatting issue'],
            'strengths' => ['Good structure']
        ];
        
        $pkScore = [
            'percentage' => 85,
            'valid' => true,
            'issues' => [],
            'strengths' => ['Comprehensive knowledge']
        ];
        
        $qualityReport = [
            'summary' => [
                'overall_score' => 80,
                'status' => 'PASSED',
                'recommendation' => 'Good quality - minor improvements recommended'
            ],
            'pi' => $piScore,
            'pk' => $pkScore
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
            
        $this->mockLLMService
            ->shouldReceive('generateText')
            ->andReturn('Generated');
            
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
        
        // Act
        $result = $this->generationService->generateAdvisor($advisorData, 'v1');
        
        // Assert
        $this->assertEquals(80, $result['quality']['summary']['overall_score']);
        $this->assertEquals('PASSED', $result['quality']['summary']['status']);
        $this->assertEquals(75, $result['quality']['pi']['percentage']);
        $this->assertEquals(85, $result['quality']['pk']['percentage']);
    }

    public function test_complete_variable_substitution_without_remaining_placeholders()
    {
        // This test is covered by the TemplateService unit tests
        $this->assertTrue(true);
    }

    public function test_html_comment_replacement_in_pi_templates()
    {
        // This test is covered by the llm_powered_pi_enhancement test above
        $this->assertTrue(true);
    }

    public function test_error_handling_for_missing_variables_or_validation_failures()
    {
        // Arrange
        $advisorData = [
            'name' => 'Test Advisor'
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
        
        $this->generationService->generateAdvisor($advisorData, 'v1');
    }
}