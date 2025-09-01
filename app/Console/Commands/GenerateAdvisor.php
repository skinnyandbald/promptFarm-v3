<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AdvisorGenerationService;
use App\Services\AdvisorConfigService;
use App\Services\Validation\AdvisorQualityService;
use InvalidArgumentException;
use Exception;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;

class GenerateAdvisor extends Command
{
    protected $signature = 'advisor:generate {name : The advisor key from config} {--template-version=v1 : Template version} {--show-validation : Display detailed validation feedback} {--quality-threshold= : Override quality requirements (0-100)}';
    protected $description = 'Generate an advisor by key from advisors.json config';

    public function __construct(
        protected AdvisorGenerationService $generationService, 
        protected AdvisorConfigService $configService,
        protected AdvisorQualityService $qualityService
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

            // Progress bar for generation steps
            $progressBar = $this->output->createProgressBar(4);
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
            
            $progressBar->setMessage('Generating PI (Instructions)...');
            $progressBar->start();
            
            // Generate advisor
            $result = $this->generationService->generateAdvisor($advisorData, $version);
            
            $progressBar->advance();
            $progressBar->setMessage('Generating PK (Knowledge)...');
            $progressBar->advance();
            
            $progressBar->setMessage('Validating quality...');
            $progressBar->advance();
            
            $progressBar->setMessage('Saving files...');
            $progressBar->advance();
            $progressBar->finish();
            $this->newLine(2);
            
            if ($result['success']) {
                $this->info("✅ Advisor generated successfully!");
                $this->newLine();
                
                // Display quality scores
                $quality = $result['quality'];
                $this->displayQualityScores($quality);
                
                // Show validation details if requested
                if ($this->option('show-validation')) {
                    $this->displayValidationDetails($quality);
                }
                
                // Display file paths
                $this->table(['Component', 'File Path', 'Quality'], [
                    ['PI (Instructions)', $result['files']['pi'], $quality['pi']['percentage'] . '%'],
                    ['PK (Knowledge)', $result['files']['pk'], $quality['pk']['percentage'] . '%'],
                    ['Metadata', $result['files']['metadata'], 'N/A']
                ]);
                
                $this->info("📁 Files saved to: storage/app/advisors/{$result['files']['base_path']}");
                
                // Check quality threshold if specified
                $threshold = $this->option('quality-threshold');
                if ($threshold !== null && $quality['summary']['overall_score'] < $threshold) {
                    $this->warn("⚠️ Quality score ({$quality['summary']['overall_score']}%) is below threshold ({$threshold}%)");
                    return Command::FAILURE;
                }
                
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
    
    /**
     * Display quality scores in a formatted way
     */
    protected function displayQualityScores(array $quality): void
    {
        $status = $quality['summary']['status'];
        $statusIcon = $status === 'PASSED' ? '✅' : '⚠️';
        $statusColor = $status === 'PASSED' ? 'info' : 'warn';
        
        $this->$statusColor("{$statusIcon} Quality Status: {$status}");
        $this->line("📊 Overall Score: {$quality['summary']['overall_score']}%");
        $this->line("💡 Recommendation: {$quality['summary']['recommendation']}");
        $this->newLine();
    }
    
    /**
     * Display detailed validation feedback
     */
    protected function displayValidationDetails(array $quality): void
    {
        $this->info('=== Detailed Validation Report ===');
        $this->newLine();
        
        // PI Validation
        $this->comment('📄 PI (Project Instructions) Validation:');
        $this->line("   Score: {$quality['pi']['score']}/{$quality['pi']['percentage']}%");
        $this->line("   Lines: {$quality['pi']['lineCount']}");
        
        if (!empty($quality['pi']['strengths'])) {
            $this->info('   ✓ Strengths:');
            foreach ($quality['pi']['strengths'] as $strength) {
                $this->line("     • {$strength}");
            }
        }
        
        if (!empty($quality['pi']['issues'])) {
            $this->warn('   ⚠ Issues:');
            foreach ($quality['pi']['issues'] as $issue) {
                $this->line("     • {$issue}");
            }
        }
        $this->newLine();
        
        // PK Validation
        $this->comment('📚 PK (Project Knowledge) Validation:');
        $this->line("   Score: {$quality['pk']['score']}/{$quality['pk']['percentage']}%");
        $this->line("   Lines: {$quality['pk']['lineCount']}");
        
        if (!empty($quality['pk']['strengths'])) {
            $this->info('   ✓ Strengths:');
            foreach ($quality['pk']['strengths'] as $strength) {
                $this->line("     • {$strength}");
            }
        }
        
        if (!empty($quality['pk']['issues'])) {
            $this->warn('   ⚠ Issues:');
            foreach ($quality['pk']['issues'] as $issue) {
                $this->line("     • {$issue}");
            }
        }
        $this->newLine();
    }
}