<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateAdvisorJob;
use App\Models\Advisor;
use App\Models\AdvisorGenerationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdvisorGenerationController extends Controller
{
    /**
     * Start a new advisor generation job.
     */
    public function startGeneration(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'advisor_slug' => 'required|string|max:255',
        ]);

        $advisor = Advisor::where('slug', $validated['advisor_slug'])->first();

        if (! $advisor) {
            throw ValidationException::withMessages([
                'advisor_slug' => 'The specified advisor does not exist.',
            ]);
        }

        $generationJob = AdvisorGenerationJob::create([
            'advisor_id' => $advisor->id,
            'status' => AdvisorGenerationJob::STATUS_PENDING,
            'progress' => 0,
            'current_step' => 'Queued for generation',
        ]);

        GenerateAdvisorJob::dispatch($generationJob)
            ->onQueue(config('advisors.queue.name'));

        return response()->json([
            'message' => 'Advisor generation job started successfully',
            'job_id' => $generationJob->id,
            'status' => $generationJob->status,
            'polling_url' => route('advisors.jobs.status', $generationJob->id),
        ], 201);
    }

    /**
     * Get the status of a generation job.
     */
    public function getStatus(string $jobId): JsonResponse
    {
        $job = AdvisorGenerationJob::findOrFail($jobId);

        $response = [
            'job_id' => $job->id,
            'advisor_slug' => $job->advisor->slug ?? 'N/A',
            'status' => $job->status,
            'progress' => $job->progress,
            'current_step' => $job->current_step,
            'created_at' => $job->created_at,
            'started_at' => $job->started_at,
            'completed_at' => $job->completed_at,
        ];

        if ($job->status === AdvisorGenerationJob::STATUS_COMPLETED) {
            $response['result'] = [
                'pi_content' => $job->pi_content,
                'pk_content' => $job->pk_content,
                'quality_report' => $job->quality_report,
            ];
        } elseif ($job->status === AdvisorGenerationJob::STATUS_FAILED) {
            $response['error'] = $job->error_message;
        }

        return response()->json($response);
    }

    /**
     * Get completed advisor generation result.
     */
    public function getResult(string $jobId): JsonResponse
    {
        $job = AdvisorGenerationJob::findOrFail($jobId);

        if ($job->status !== AdvisorGenerationJob::STATUS_COMPLETED) {
            return response()->json([
                'message' => 'Job is not completed yet',
                'status' => $job->status,
                'progress' => $job->progress,
            ], 202);
        }

        return response()->json([
            'job_id' => $job->id,
            'advisor_slug' => $job->advisor->slug ?? 'N/A',
            'pi_content' => $job->pi_content,
            'pk_content' => $job->pk_content,
            'quality_report' => $job->quality_report,
            'completed_at' => $job->completed_at,
        ]);
    }

    /**
     * List recent generation jobs.
     */
    public function listJobs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'advisor_slug' => 'nullable|string|max:255',
            'status' => 'nullable|in:pending,processing,completed,failed',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = AdvisorGenerationJob::query();

        if (isset($validated['advisor_slug'])) {
            $advisor = Advisor::where('slug', $validated['advisor_slug'])->first();
            if ($advisor) {
                $query->where('advisor_id', $advisor->id);
            }
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $jobs = $query->recent()
            ->limit($validated['limit'] ?? 20)
            ->with('advisor:id,slug')
            ->get(['id', 'advisor_id', 'status', 'progress', 'current_step', 'created_at', 'completed_at']);

        return response()->json([
            'jobs' => $jobs,
            'count' => $jobs->count(),
        ]);
    }

    /**
     * Cancel a pending generation job.
     */
    public function cancelJob(string $jobId): JsonResponse
    {
        $job = AdvisorGenerationJob::findOrFail($jobId);

        if ($job->status !== AdvisorGenerationJob::STATUS_PENDING) {
            return response()->json([
                'message' => 'Only pending jobs can be cancelled',
                'status' => $job->status,
            ], 400);
        }

        $job->markAsFailed('Job cancelled by user');

        return response()->json([
            'message' => 'Job cancelled successfully',
            'job_id' => $job->id,
        ]);
    }
}
