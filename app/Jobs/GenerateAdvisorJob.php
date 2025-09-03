<?php

namespace App\Jobs;

use App\Models\AdvisorGenerationJob;
use App\Services\AdvisorGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateAdvisorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    public $tries = 3;

    public $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public AdvisorGenerationJob $generationJob,
        public bool $exportFiles = false
    ) {
        $this->onQueue('advisor-generation');
    }

    /**
     * Execute the job.
     */
    public function handle(AdvisorGenerationService $service): void
    {
        $this->generationJob->markAsProcessing();

        try {
            $advisor = $this->generationJob->advisor;

            if (! $advisor) {
                throw new \Exception("Advisor with slug '{$this->generationJob->advisor_slug}' not found");
            }

            $result = $service->generateAdvisor(
                $advisor,
                function (int $progress, string $step) {
                    $this->generationJob->updateProgress($progress, $step);
                },
                $this->exportFiles, // Export files if requested
                $this->generationJob->id // Pass the incremental job ID
            );

            $this->generationJob->update([
                'pi_content' => $result['pi_content'] ?? null,
                'pk_content' => $result['pk_content'] ?? null,
                'quality_report' => $result['quality_report'] ?? null,
            ]);

            $this->generationJob->markAsCompleted();

            Log::info("Advisor generation completed for {$advisor->slug}", [
                'job_id' => $this->generationJob->id,
                'advisor_slug' => $advisor->slug,
            ]);
        } catch (Throwable $exception) {
            Log::error("Advisor generation failed for {$this->generationJob->advisor_slug}", [
                'job_id' => $this->generationJob->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $this->generationJob->markAsFailed($exception->getMessage());

            throw $exception;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        if ($exception) {
            $this->generationJob->markAsFailed($exception->getMessage());
        }
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [60, 120, 300];
    }
}
