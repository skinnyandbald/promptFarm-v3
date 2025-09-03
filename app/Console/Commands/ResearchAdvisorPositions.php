<?php

namespace App\Console\Commands;

use App\Jobs\ResearchAdvisorPositionsJob;
use App\Models\AdvisorPosition;
use App\Services\LLMService;
use Illuminate\Console\Command;

class ResearchAdvisorPositions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'advisor:research 
        {advisor? : The advisor slug (e.g., alex-hormozi, alex-bogusky)} 
        {--all : Research all advisors} 
        {--force : Force re-research even if cached} 
        {--sync : Run synchronously instead of dispatching as job}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Research and cache an advisor\'s actual positions using low-temperature fact-checking';

    public function __construct(
        protected LLMService $llmService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        $sync = $this->option('sync');
        $all = $this->option('all');

        // Get list of advisors to research
        $advisorsToResearch = [];

        if ($all) {
            // Get all unique advisor keys from database
            $advisorsToResearch = \App\Models\Advisor::pluck('slug')->unique()->toArray();

            if (empty($advisorsToResearch)) {
                $this->error('No advisors found in database!');

                return 1;
            }

            $this->info('🔍 Researching ALL advisors from database: '.implode(', ', $advisorsToResearch));
        } else {
            $advisorKey = $this->argument('advisor');
            if (! $advisorKey) {
                $this->error('Please specify an advisor slug or use --all flag');

                return 1;
            }
            $advisorsToResearch = [$advisorKey];
        }

        // Process each advisor
        foreach ($advisorsToResearch as $advisorKey) {
            $this->line("\n".str_repeat('=', 50));
            $this->info("Processing: {$advisorKey}");

            // Check if we already have cached positions
            $existing = AdvisorPosition::where('advisor_slug', $advisorKey)->first();

            if ($existing && ! $force) {
                $this->warn("✓ Positions already cached for {$advisorKey}");
                $this->info("Cached at: {$existing->created_at}");

                continue;
            }

            $this->info("🔍 Researching positions for advisor: {$advisorKey}");

            // Either dispatch job or run synchronously
            if ($sync) {
                $this->line('Running synchronously...');
                $job = new ResearchAdvisorPositionsJob($advisorKey, [], $force);
                $job->handle($this->llmService);

                // Show the results
                $result = AdvisorPosition::where('advisor_slug', $advisorKey)->first();
                if ($result) {
                    $this->info("✅ Research completed for {$advisorKey}!");
                    if (! $all) {
                        // Only show full positions if researching single advisor
                        $this->line("\nResearched positions:");
                        $this->line($result->researched_positions);
                    }
                }
            } else {
                $this->line('Dispatching research job...');
                ResearchAdvisorPositionsJob::dispatch($advisorKey, [], $force);
                $this->info("✅ Job dispatched for {$advisorKey}!");
            }
        }

        if ($all) {
            $this->line("\n".str_repeat('=', 50));
            $this->info('✅ All advisors processed!');
        }

        return 0;
    }
}
