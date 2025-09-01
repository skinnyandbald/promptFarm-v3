<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AdvisorGenerationService;
use App\Services\AdvisorConfigService;
use InvalidArgumentException;
use Exception;
use Symfony\Component\Console\Helper\ProgressBar;

class GenerateAdvisor extends Command
{
    protected $signature = 'advisor:generate {name : The advisor key from config} {--template-version=v1 : Template version}';
    protected $description = 'Generate an advisor by key from advisors.json config';

    public function __construct(
        protected AdvisorGenerationService $generationService, 
        protected AdvisorConfigService $configService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $name = $this->argument('name');
        $version = $this->option('template-version') ?? 'v1';

        $this->info("🎭 Generating advisor: {$name}");
        $this->info("📋 Using template version: {$version}");

        try {
            // Load advisor config
            $advisorData = $this->configService->getAdvisorConfig($name);
            $advisorData['key'] = $name; // Add key for generation service
            
            $this->line("✓ Loaded configuration for {$advisorData['fullName']}");

            // Generate advisor
            $result = $this->generationService->generateAdvisor($advisorData, $version);

            if ($result['success']) {
                $this->info("✅ Advisor generated successfully!");
                $this->newLine();
                
                $this->table(['Component', 'File Path'], [
                    ['PI (Instructions)', $result['files']['pi']],
                    ['PK (Knowledge)', $result['files']['pk']],
                    ['Metadata', $result['files']['metadata']]
                ]);
                
                $this->info("📁 Files saved to: storage/app/{$result['files']['base_path']}");
                return Command::SUCCESS;
            }

        } catch (InvalidArgumentException $e) {
            $this->error("❌ Advisor not found: {$name}");
            $this->newLine();
            $this->info("Available advisors:");
            $advisors = $this->configService->allAdvisors();
            foreach (array_keys($advisors) as $key) {
                $config = $advisors[$key];
                $this->line("  - {$key} ({$config['fullName']})");
            }
            return Command::FAILURE;
            
        } catch (Exception $e) {
            $this->error("❌ Generation failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}