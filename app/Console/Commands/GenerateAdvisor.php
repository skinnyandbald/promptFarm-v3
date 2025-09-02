<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AdvisorGenerationService;
use App\Services\AdvisorConfigService;
use App\Services\Validation\AdvisorQualityService;
use App\Jobs\GenerateAdvisorJob;
use App\Models\Advisor;
use App\Models\AdvisorGenerationJob;
use InvalidArgumentException;
use Exception;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;

class GenerateAdvisor extends Command
{
    protected $signature = 'advisor:generate {name? : The advisor key from config} {--all : Generate all advisors} {--template-version=v1 : Template version} {--show-validation : Display detailed validation feedback} {--background : Run generation in background queue} {--poll : Poll background job status} {--export-files : Export generated files to local storage for testing}';
    protected $description = 'Generate an advisor by key from database (supports background processing with Redis/Horizon)';

    public function __construct(
        protected AdvisorGenerationService $generationService,
        protected AdvisorConfigService $configService,
        protected AdvisorQualityService $qualityService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $all = $this->option('all');
        $version = $this->option('template-version') ?? 'v1';
        $background = $this->option('background');
        $poll = $this->option('poll');

        // Get list of advisors to generate
        $advisorsToGenerate = [];
        
        if ($all) {
            // Generate all advisors from database
            $advisorsToGenerate = Advisor::pluck('key')->unique()->toArray();
            
            if (empty($advisorsToGenerate)) {
                $this->error("No advisors found in database!");
                return 1;
            }
            
            $this->info("🎭 Generating ALL advisors: " . implode(', ', $advisorsToGenerate));
            $this->info("📋 Using template version: {$version}");
        } else {
            $name = $this->argument('name');
            if (!$name) {
                $this->error("Please specify an advisor key or use --all flag");
                return 1;
            }
            
            $advisorsToGenerate = [$name];
            
            $this->info("🎭 Generating advisor: {$name}");
            $this->info("📋 Using template version: {$version}");
            
            // If polling an existing job for single advisor
            if ($poll) {
                return $this->pollBackgroundJob($name);
            }
        }

        // Process each advisor
        $results = [];
        foreach ($advisorsToGenerate as $advisorKey) {
            if ($all) {
                $this->line("\n" . str_repeat('=', 50));
                $this->info("Processing: {$advisorKey}");
            }
            
            // If running in background mode
            if ($background) {
                $this->runInBackground($advisorKey);
                $results[$advisorKey] = 'queued';
                continue;
            }
            
            // Run generation synchronously
            $result = $this->generateAdvisor($advisorKey, $version);
            $results[$advisorKey] = $result ? 'success' : 'failed';
        }
        
        // Show summary for --all
        if ($all) {
            $this->showAllGenerationSummary($results);
        }
        
        return 0;
    }
    
    protected function generateAdvisor($name, $version)
    {

        // Synchronous generation (existing code)
        try {
            // Load advisor config
            $advisorData = $this->configService->getAdvisorConfig($name);
            $advisorData['key'] = $name; // Add key for generation service

            $this->line("✓ Loaded configuration for {$advisorData['full_name']}");

            // Progress bar for generation steps
            $progressBar = $this->output->createProgressBar(4);
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');

            $progressBar->setMessage('Generating PI (Instructions)...');
            $progressBar->start();

            // Generate advisor
            // TODO: this process is lies.
            // Based on the actual logic of how this works, by the time it gets to generatedVisor, wouldn't it already have generated the knowledge, since that all happens within the generatedVisor method?
            // How can we do this so that the progress is accurate? Do we need to embed the progress part within GeneratedAdvisor? That doesn't make sense. Go through events: what's the best practice here?
            $result = $this->generationService->generateAdvisor(
                $advisorData, 
                $version, 
                null, 
                $this->option('export-files')
            );

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

                // Display file paths if files were exported
                if ($result['exported_files']) {
                    $this->table(['Component', 'File Path', 'Quality'], [
                        ['PI (Instructions)', $result['exported_files']['pi'], $quality['pi']['percentage'] . '%'],
                        ['PK (Knowledge)', $result['exported_files']['pk'], $quality['pk']['percentage'] . '%'],
                        ['Metadata', $result['exported_files']['metadata'], 'N/A']
                    ]);

                    $this->info("📁 Files exported to: storage/app/advisors/{$result['exported_files']['base_path']}");
                } else {
                    $this->info("📄 Content generated and stored in database (use --export-files flag to save local files)");
                }

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
                $this->line("  - {$key} ({$config['full_name']})");
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
     * Run advisor generation in background queue
     */
    protected function runInBackground(string $name): int
    {
        try {
            $advisor = Advisor::where('key', $name)->first();

            if (!$advisor) {
                $this->error("❌ Advisor not found: {$name}");
                return Command::FAILURE;
            }

            // Create job record
            $generationJob = AdvisorGenerationJob::create([
                'advisor_key' => $advisor->key,
                'status' => AdvisorGenerationJob::STATUS_PENDING,
                'progress' => 0,
                'current_step' => 'Queued for generation',
            ]);

            // Dispatch to queue
            GenerateAdvisorJob::dispatch($generationJob)
                ->onQueue(config('advisors.queue.name'));

            $this->info("✅ Advisor generation job queued successfully!");
            $this->line("📋 Job ID: {$generationJob->id}");
            $this->line("🔄 Status: {$generationJob->status}");
            $this->newLine();

            $this->info("To check status:");
            $this->comment("  php artisan advisor:generate {$name} --poll");
            $this->comment("  curl " . url("/api/advisors/jobs/{$generationJob->id}/status"));
            $this->newLine();

            $this->info("To monitor with Horizon:");
            $this->comment("  php artisan horizon");
            $this->comment("  Visit: " . url('/horizon'));

            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->error("❌ Failed to queue job: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Poll status of a background generation job
     */
    protected function pollBackgroundJob(string $name): int
    {
        try {
            // Find the most recent job for this advisor
            $job = AdvisorGenerationJob::where('advisor_key', $name)
                ->recent()
                ->first();

            if (!$job) {
                $this->error("❌ No generation jobs found for advisor: {$name}");
                return Command::FAILURE;
            }

            $this->info("📊 Polling job #{$job->id} for advisor: {$name}");
            $this->newLine();

            // Display initial status
            $this->displayJobStatus($job);

            // Poll until completion if still processing
            while (in_array($job->status, [AdvisorGenerationJob::STATUS_PENDING, AdvisorGenerationJob::STATUS_PROCESSING])) {
                sleep(config('advisors.polling.interval', 5));
                $job->refresh();

                // Clear previous output and show updated status
                $this->output->write("\033[2J\033[0;0H");
                $this->info("📊 Polling job #{$job->id} for advisor: {$name}");
                $this->newLine();
                $this->displayJobStatus($job);
            }

            // Final status
            if ($job->status === AdvisorGenerationJob::STATUS_COMPLETED) {
                $this->newLine();
                $this->info("✅ Generation completed successfully!");

                if ($job->quality_report) {
                    $this->displayQualityScores($job->quality_report);
                }

                return Command::SUCCESS;
            } else {
                $this->newLine();
                $this->error("❌ Generation failed: {$job->error_message}");
                return Command::FAILURE;
            }

        } catch (Exception $e) {
            $this->error("❌ Polling failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Display current job status
     */
    protected function displayJobStatus(AdvisorGenerationJob $job): void
    {
        $statusColor = match($job->status) {
            AdvisorGenerationJob::STATUS_PENDING => 'comment',
            AdvisorGenerationJob::STATUS_PROCESSING => 'info',
            AdvisorGenerationJob::STATUS_COMPLETED => 'info',
            AdvisorGenerationJob::STATUS_FAILED => 'error',
            default => 'line'
        };

        $this->$statusColor("Status: {$job->status}");

        if ($job->progress > 0) {
            $bar = $this->output->createProgressBar(100);
            $bar->setProgress($job->progress);
            $this->newLine();
        }

        if ($job->current_step) {
            $this->line("Current Step: {$job->current_step}");
        }

        if ($job->started_at) {
            $duration = $job->started_at->diffForHumans(null, true);
            $this->line("Duration: {$duration}");
        }
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
    
    /**
     * Display summary of all advisor generation results
     */
    protected function showAllGenerationSummary(array $results): void
    {
        $this->newLine();
        $this->line(str_repeat('=', 50));
        $this->info('📊 Generation Summary');
        $this->line(str_repeat('=', 50));
        
        $success = 0;
        $failed = 0;
        $queued = 0;
        
        foreach ($results as $advisor => $status) {
            $emoji = match($status) {
                'success' => '✅',
                'failed' => '❌',
                'queued' => '⏳',
                default => '❓'
            };
            
            $this->line("{$emoji} {$advisor}: {$status}");
            
            if ($status === 'success') $success++;
            elseif ($status === 'failed') $failed++;
            elseif ($status === 'queued') $queued++;
        }
        
        $this->newLine();
        $this->info("Results: {$success} succeeded, {$failed} failed, {$queued} queued");
        
        if ($queued > 0) {
            $this->comment("Use 'php artisan advisor:generate <name> --poll' to check queued jobs");
        }
    }
}
