<?php

namespace App\Console\Commands;

use App\Models\AdvisorGenerationJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\WorkloadRepository;

class HorizonStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:status 
                            {--detailed : Show detailed job information}
                            {--jobs : Show recent advisor generation jobs}
                            {--queue= : Show status for specific queue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Horizon and Redis queue status for advisor generation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('            Horizon & Queue Status Check               ');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        // Check Redis connection
        if (!$this->checkRedisConnection()) {
            return Command::FAILURE;
        }

        // Check Horizon status
        $this->checkHorizonStatus();

        // Show queue statistics
        $this->showQueueStatistics();

        // Show recent advisor generation jobs if requested
        if ($this->option('jobs')) {
            $this->showRecentJobs();
        }

        return Command::SUCCESS;
    }

    /**
     * Check Redis connection
     */
    protected function checkRedisConnection(): bool
    {
        $this->comment('Checking Redis Connection...');
        
        try {
            Redis::ping();
            $this->info('✅ Redis is connected and responding');
            
            // Show Redis info
            $info = Redis::info('server');
            $version = $info['redis_version'] ?? 'unknown';
            $this->line("   Redis Version: {$version}");
            
            return true;
        } catch (\Exception $e) {
            $this->error('❌ Redis connection failed: ' . $e->getMessage());
            $this->warn('Make sure Redis is running and configured correctly in .env');
            return false;
        }
    }

    /**
     * Check Horizon status
     */
    protected function checkHorizonStatus(): void
    {
        $this->newLine();
        $this->comment('Checking Horizon Status...');
        
        try {
            $masters = app(MasterSupervisorRepository::class)->all();
            
            if (empty($masters)) {
                $this->warn('⚠️  Horizon is not running');
                $this->line('   Start Horizon with: php artisan horizon');
            } else {
                $this->info('✅ Horizon is running');
                
                foreach ($masters as $master) {
                    $status = $master->status ?? 'unknown';
                    $pid = $master->pid ?? 'N/A';
                    $this->line("   Master Supervisor: PID {$pid}, Status: {$status}");
                    
                    if (isset($master->supervisors) && is_array($master->supervisors)) {
                        foreach ($master->supervisors as $supervisor) {
                            $this->line("     - {$supervisor}");
                        }
                    }
                }
            }
            
            // Show Horizon dashboard URL
            $this->newLine();
            $this->info('📊 Horizon Dashboard: ' . url('/horizon'));
            
        } catch (\Exception $e) {
            $this->warn('⚠️  Could not check Horizon status: ' . $e->getMessage());
        }
    }

    /**
     * Show queue statistics
     */
    protected function showQueueStatistics(): void
    {
        $this->newLine();
        $this->comment('Queue Statistics:');
        
        $queueName = $this->option('queue') ?? config('advisors.queue.name', 'advisor-generation');
        
        try {
            // Get queue size
            $queueSize = Redis::llen("queues:{$queueName}");
            $this->line("📌 Queue '{$queueName}': {$queueSize} jobs pending");
            
            // Get failed jobs count
            $failedCount = Redis::zcard('failed_jobs');
            if ($failedCount > 0) {
                $this->warn("⚠️  Failed jobs: {$failedCount}");
            }
            
            // Show workload if available
            if (class_exists(WorkloadRepository::class)) {
                $workload = app(WorkloadRepository::class)->get();
                
                if (!empty($workload)) {
                    $this->newLine();
                    $this->comment('Current Workload:');
                    
                    foreach ($workload as $queue => $info) {
                        $processes = $info['processes'] ?? 0;
                        $length = $info['length'] ?? 0;
                        $wait = isset($info['wait']) ? round($info['wait'] / 1000, 2) . 's' : 'N/A';
                        
                        $this->line("   {$queue}: {$length} jobs, {$processes} workers, {$wait} avg wait");
                    }
                }
            }
            
            // Show metrics if detailed flag is set
            if ($this->option('detailed') && class_exists(MetricsRepository::class)) {
                $this->showDetailedMetrics();
            }
            
        } catch (\Exception $e) {
            $this->error('Could not retrieve queue statistics: ' . $e->getMessage());
        }
    }

    /**
     * Show detailed metrics
     */
    protected function showDetailedMetrics(): void
    {
        try {
            $metrics = app(MetricsRepository::class);
            
            $jobsPerMinute = $metrics->jobsProcessedPerMinute();
            $throughput = $metrics->throughputPerMinute();
            
            $this->newLine();
            $this->comment('Performance Metrics (last hour):');
            $this->line("   Jobs/minute: " . round($jobsPerMinute, 2));
            $this->line("   Throughput/minute: " . round($throughput, 2));
            
        } catch (\Exception $e) {
            // Metrics might not be available
        }
    }

    /**
     * Show recent advisor generation jobs
     */
    protected function showRecentJobs(): void
    {
        $this->newLine();
        $this->comment('Recent Advisor Generation Jobs:');
        
        $jobs = AdvisorGenerationJob::recent()
            ->limit(10)
            ->get();
        
        if ($jobs->isEmpty()) {
            $this->line('   No generation jobs found');
            return;
        }
        
        $headers = ['ID', 'Advisor', 'Status', 'Progress', 'Step', 'Created'];
        $rows = [];
        
        foreach ($jobs as $job) {
            $statusIcon = match($job->status) {
                'pending' => '⏳',
                'processing' => '🔄',
                'completed' => '✅',
                'failed' => '❌',
                default => '❓'
            };
            
            $rows[] = [
                $job->id,
                $job->advisor_key,
                $statusIcon . ' ' . $job->status,
                $job->progress . '%',
                substr($job->current_step ?? 'N/A', 0, 30),
                $job->created_at->diffForHumans()
            ];
        }
        
        $this->table($headers, $rows);
        
        // Show summary
        $summary = [
            'Total' => $jobs->count(),
            'Pending' => $jobs->where('status', 'pending')->count(),
            'Processing' => $jobs->where('status', 'processing')->count(),
            'Completed' => $jobs->where('status', 'completed')->count(),
            'Failed' => $jobs->where('status', 'failed')->count(),
        ];
        
        $this->newLine();
        $this->comment('Summary:');
        foreach ($summary as $label => $count) {
            if ($count > 0) {
                $this->line("   {$label}: {$count}");
            }
        }
    }
}