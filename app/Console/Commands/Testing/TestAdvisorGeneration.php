<?php

namespace App\Console\Commands\Testing;

use App\Services\AdvisorGenerationService;
use App\Services\LLMService;
use App\Services\TemplateService;
use Illuminate\Console\Command;

class TestAdvisorGeneration extends Command
{
    protected $signature = 'test:advisor 
                            {--template : Test template service only}
                            {--llm : Test LLM service only}
                            {--generate : Test full advisor generation}
                            {--name= : Advisor name for generation test}';

    protected $description = 'Test advisor generation services';

    public function handle(
        TemplateService $templateService,
        LLMService $llmService,
        AdvisorGenerationService $advisorService
    ): int {
        $this->info('🧪 Testing Advisor Generation Services...');
        $this->newLine();

        if ($this->option('template') || ! ($this->option('llm') || $this->option('generate'))) {
            $this->testTemplateService($templateService);
        }

        if ($this->option('llm')) {
            $this->testLLMService($llmService);
        }

        if ($this->option('generate')) {
            $this->testAdvisorGeneration($advisorService);
        }

        return Command::SUCCESS;
    }

    protected function testTemplateService(TemplateService $templateService): void
    {
        $this->info('📄 Testing Template Service...');

        try {
            // Test loading templates
            $this->line('Loading meta_pi_template_v1...');
            $template = $templateService->loadTemplate('meta_pi_template', 'v1');
            $this->info('✓ Template loaded successfully ('.strlen($template).' characters)');

            // Test variable extraction
            $variables = $templateService->extractVariables($template);
            $this->info('✓ Found '.count($variables).' variables: '.implode(', ', array_slice($variables, 0, 5)).'...');

            // Test variable substitution
            $testVars = [
                'advisor_name' => 'Test Advisor',
                'personality_trait' => 'analytical and thoughtful',
                'speaking_style' => 'clear and concise',
            ];
            $processed = $templateService->substituteVariables($template, $testVars);
            $this->info('✓ Variable substitution working');

            // List available templates
            $templates = $templateService->getAvailableTemplates();
            $this->info('✓ Found '.count($templates).' templates:');
            foreach ($templates as $t) {
                $this->line('  - '.$t['name'].' ('.$t['type'].')');
            }

        } catch (\Exception $e) {
            $this->error('✗ Template service error: '.$e->getMessage());
        }

        $this->newLine();
    }

    protected function testLLMService(LLMService $llmService): void
    {
        $this->info('🤖 Testing LLM Service...');

        try {
            // Check configuration
            $this->line('Validating OpenAI configuration...');

            if (! config('services.openai.api_key')) {
                $this->warn('⚠️  OpenAI API key not configured in .env');
                $this->line('Add OPENAI_API_KEY=your-key-here to your .env file');

                return;
            }

            $this->info('✓ API key configured');
            $this->info('✓ Model: '.config('ai-models.primary.model'));
            $this->info('✓ Max tokens: '.config('services.openai.max_tokens'));

            if ($this->confirm('Do you want to test a real API call? (This will use your API credits)', false)) {
                $this->line('Making test API call...');
                $response = $llmService->generateText(
                    'Say "Hello, I am working!" in exactly 5 words.',
                    ['max_tokens' => 50, 'temperature' => 0.5]
                );
                $this->info('✓ Response: '.$response);
            }

        } catch (\Exception $e) {
            $this->error('✗ LLM service error: '.$e->getMessage());
        }

        $this->newLine();
    }

    protected function testAdvisorGeneration(AdvisorGenerationService $advisorService): void
    {
        $this->info('🎭 Testing Full Advisor Generation...');

        $name = $this->option('name') ?? $this->ask('Enter advisor name', 'Sage Mentor');

        try {
            // Check if API key is configured
            if (! config('services.openai.api_key')) {
                $this->warn('⚠️  Cannot test generation without OpenAI API key');
                $this->line('Add OPENAI_API_KEY=your-key-here to your .env file');

                return;
            }

            $this->warn('⚠️  This will make real API calls to OpenAI and consume credits!');
            if (! $this->confirm('Do you want to proceed with generating an advisor?', false)) {
                $this->info('Generation cancelled.');

                return;
            }

            $advisorData = [
                'name' => $name,
                'description' => 'A wise and knowledgeable advisor with expertise in guidance and mentorship',
                'personality_trait' => 'patient and insightful',
                'speaking_style' => 'thoughtful and encouraging',
                'expertise_area' => 'personal development and strategic thinking',
                'background' => 'decades of experience in mentoring leaders',
                'core_belief' => 'wisdom comes from understanding both success and failure',
            ];

            $this->line('Generating advisor: '.$name.'...');
            $this->line('This may take a few minutes...');

            $result = $advisorService->generateAdvisor($advisorData, 'v1');

            if ($result['success']) {
                $this->info('✓ Advisor generated successfully!');
                $this->info('Files saved:');
                foreach ($result['files'] as $type => $path) {
                    $this->line('  - '.$type.': '.$path);
                }

                // Show a preview
                if ($this->confirm('Show preview of generated content?', true)) {
                    $this->newLine();
                    $this->info('PI Preview (first 500 chars):');
                    $this->line(substr($result['pi_content'], 0, 500).'...');
                    $this->newLine();
                    $this->info('PK Preview (first 500 chars):');
                    $this->line(substr($result['pk_content'], 0, 500).'...');
                }
            }

        } catch (\Exception $e) {
            $this->error('✗ Generation error: '.$e->getMessage());
        }

        // List existing advisors
        $this->newLine();
        $this->info('📚 Existing Advisors:');
        try {
            $advisors = $advisorService->listAdvisors();
            if (empty($advisors)) {
                $this->line('  No advisors found yet.');
            } else {
                foreach ($advisors as $advisor) {
                    $this->line('  - '.$advisor['name'].' (generated: '.$advisor['generated_at'].')');
                }
            }
        } catch (\Exception $e) {
            $this->warn('Could not list advisors: '.$e->getMessage());
        }
    }
}
