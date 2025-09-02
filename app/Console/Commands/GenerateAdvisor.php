<?php

namespace App\Console\Commands;

use App\Jobs\GenerateAdvisorJob;
use App\Jobs\ResearchAdvisorPositionsJob;
use App\Models\Advisor;
use App\Models\AdvisorGenerationJob;
use App\Services\AdvisorConfigService;
use App\Services\AdvisorGenerationService;
use App\Services\Validation\AdvisorQualityService;
use Exception;
use Illuminate\Console\Command;
use InvalidArgumentException;

class GenerateAdvisor extends Command
{
    protected $signature = 'advisor:generate 
        {name? : The advisor slug from database} 
        {--all : Generate all advisors} 
        {--template-version=v1 : Template version} 
        {--show-validation : Display detailed validation feedback} 
        {--poll : Poll background job status} 
        {--export-files : Export generated files to local storage for testing}';

    protected $description = 'Generate an advisor by slug from database (sync/async determined by queue driver)';

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
        $poll = $this->option('poll');

        // Get list of advisors to generate
        $advisorsToGenerate = [];

        if ($all) {
            // Generate all advisors from database
            $advisorsToGenerate = Advisor::pluck('slug')->unique()->toArray();

            if (empty($advisorsToGenerate)) {
                $this->error('No advisors found in database!');

                return 1;
            }

            $this->info('🎭 Generating ALL advisors: '.implode(', ', $advisorsToGenerate));
            $this->info("📋 Using template version: {$version}");
        } else {
            $name = $this->argument('name');
            if (! $name) {
                $this->error('Please specify an advisor key or use --all flag');

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
                $this->line("\n".str_repeat('=', 50));
                $this->info("Processing: {$advisorKey}");
            }

            // Always use job dispatch (sync/async determined by queue driver)
            $result = $this->dispatchAdvisorGeneration($advisorKey, $version);
            $results[$advisorKey] = $result;
        }

        // Show summary for --all
        if ($all) {
            $this->showAllGenerationSummary($results);
        }

        return 0;
    }

    protected function dispatchAdvisorGeneration(string $name, string $version): string
    {
        try {
            $advisor = Advisor::where('slug', $name)->first();

            if (!$advisor) {
                $this->error("❌ Advisor not found: {$name}");
                return 'failed';
            }

            // Create job record for tracking
            $generationJob = AdvisorGenerationJob::create([
                'advisor_id' => $advisor->id,
                'status' => AdvisorGenerationJob::STATUS_PENDING,
                'progress' => 0,
                'current_step' => 'Queued for generation',
            ]);

            // Simple dispatch - let Laravel handle sync vs async
            ResearchAdvisorPositionsJob::withChain([
                new GenerateAdvisorJob($generationJob, $this->option('export-files'))
            ])->dispatch(
                $advisor->slug,
                $advisor->toArray(),
                false // don't force refresh
            );

            // Always return the same way - jobs handle the work
            $this->info('✅ Advisor generation dispatched successfully!');
            $this->line("📋 Job ID: {$generationJob->id}");
            
            return 'success';

        } catch (Exception $e) {
            $this->error('❌ Generation failed: '.$e->getMessage());
            return 'failed';
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
     * Poll status of a background generation job
     */
    protected function pollBackgroundJob(string $name): int
    {
        try {
            // Find the most recent job for this advisor
            $advisor = Advisor::where('slug', $name)->first();
            if (!$advisor) {
                $this->error("❌ Advisor not found: {$name}");
                return Command::FAILURE;
            }
            
            $job = AdvisorGenerationJob::where('advisor_id', $advisor->id)
                ->recent()
                ->first();

            if (! $job) {
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
                $this->info('✅ Generation completed successfully!');

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
            $this->error('❌ Polling failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Display current job status
     */
    protected function displayJobStatus(AdvisorGenerationJob $job): void
    {
        $statusColor = match ($job->status) {
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

        if (! empty($quality['pi']['strengths'])) {
            $this->info('   ✓ Strengths:');
            foreach ($quality['pi']['strengths'] as $strength) {
                $this->line("     • {$strength}");
            }
        }

        if (! empty($quality['pi']['issues'])) {
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

        if (! empty($quality['pk']['strengths'])) {
            $this->info('   ✓ Strengths:');
            foreach ($quality['pk']['strengths'] as $strength) {
                $this->line("     • {$strength}");
            }
        }

        if (! empty($quality['pk']['issues'])) {
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
            $emoji = match ($status) {
                'success' => '✅',
                'failed' => '❌',
                'queued' => '⏳',
                default => '❓'
            };

            $this->line("{$emoji} {$advisor}: {$status}");

            if ($status === 'success') {
                $success++;
            } elseif ($status === 'failed') {
                $failed++;
            } elseif ($status === 'queued') {
                $queued++;
            }
        }

        $this->newLine();
        $this->info("Results: {$success} succeeded, {$failed} failed, {$queued} queued");

        if ($queued > 0) {
            $this->comment("Use 'php artisan advisor:generate <name> --poll' to check queued jobs");
        }
    }
}
