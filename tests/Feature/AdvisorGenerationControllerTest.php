<?php

namespace Tests\Feature;

use App\Jobs\GenerateAdvisorJob;
use App\Models\Advisor;
use App\Models\AdvisorGenerationJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdvisorGenerationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // RefreshDatabase trait already handles database setup
    }

    public function test_start_generation_creates_job_and_dispatches_queue()
    {
        // Arrange
        Queue::fake();

        $advisor = Advisor::factory()->create([
            'slug' => 'test-advisor',
            'name' => 'Test Advisor',
        ]);

        // Act
        $response = $this->postJson('/api/advisors/generate', [
            'advisor_slug' => 'test-advisor',
        ]);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'job_id',
            'status',
            'polling_url',
        ]);

        $this->assertDatabaseHas('advisor_generation_jobs', [
            'advisor_id' => $advisor->id,
            'status' => AdvisorGenerationJob::STATUS_PENDING,
            'progress' => 0,
        ]);

        Queue::assertPushed(GenerateAdvisorJob::class);
    }

    public function test_start_generation_validates_advisor_slug_required()
    {
        // Act
        $response = $this->postJson('/api/advisors/generate', []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['advisor_slug']);
    }

    public function test_start_generation_validates_advisor_exists()
    {
        // Act
        $response = $this->postJson('/api/advisors/generate', [
            'advisor_slug' => 'non-existent-advisor',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['advisor_slug']);
    }

    public function test_get_status_returns_job_status()
    {
        // Arrange
        $advisor = Advisor::factory()->create();
        $job = AdvisorGenerationJob::create([
            'advisor_id' => $advisor->id,
            'status' => AdvisorGenerationJob::STATUS_PROCESSING,
            'progress' => 50,
            'current_step' => 'Generating PI',
        ]);

        // Act
        $response = $this->getJson('/api/advisors/jobs/'.$job->id.'/status');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'job_id' => $job->id,
            'status' => AdvisorGenerationJob::STATUS_PROCESSING,
            'progress' => 50,
            'current_step' => 'Generating PI',
        ]);
    }

    public function test_get_status_returns_404_for_non_existent_job()
    {
        // Act
        $response = $this->getJson('/api/advisors/jobs/999/status');

        // Assert
        $response->assertStatus(404);
    }

    public function test_get_result_returns_completed_job_result()
    {
        // Arrange
        $advisor = Advisor::factory()->create();
        $job = AdvisorGenerationJob::create([
            'advisor_id' => $advisor->id,
            'status' => AdvisorGenerationJob::STATUS_COMPLETED,
            'progress' => 100,
            'current_step' => 'Completed',
            'pi_content' => 'Test PI',
            'pk_content' => 'Test PK',
            'quality_report' => ['score' => 85],
        ]);

        // Act
        $response = $this->getJson('/api/advisors/jobs/'.$job->id.'/result');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'job_id',
            'advisor_slug',
            'pi_content',
            'pk_content',
            'quality_report',
            'completed_at',
        ]);
    }

    public function test_get_result_returns_202_for_incomplete_job()
    {
        // Arrange
        $advisor = Advisor::factory()->create();
        $job = AdvisorGenerationJob::create([
            'advisor_id' => $advisor->id,
            'status' => AdvisorGenerationJob::STATUS_PROCESSING,
            'progress' => 50,
        ]);

        // Act
        $response = $this->getJson('/api/advisors/jobs/'.$job->id.'/result');

        // Assert
        $response->assertStatus(202);
        $response->assertJson([
            'message' => 'Job is not completed yet',
            'status' => AdvisorGenerationJob::STATUS_PROCESSING,
            'progress' => 50,
        ]);
    }

    public function test_list_jobs_returns_jobs_list()
    {
        // Arrange
        $advisor = Advisor::factory()->create();
        $jobs = collect(range(1, 3))->map(function ($i) use ($advisor) {
            return AdvisorGenerationJob::create([
                'advisor_id' => $advisor->id,
                'status' => AdvisorGenerationJob::STATUS_PENDING,
                'progress' => 0,
            ]);
        });

        // Act
        $response = $this->getJson('/api/advisors/jobs');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'jobs' => [
                '*' => [
                    'id',
                    'advisor_id',
                    'status',
                    'progress',
                    'current_step',
                    'created_at',
                ],
            ],
            'count',
        ]);
        $response->assertJsonCount(3, 'jobs');
        $response->assertJson(['count' => 3]);
    }

    public function test_cancel_job_marks_as_failed()
    {
        // Arrange
        $advisor = Advisor::factory()->create();
        $job = AdvisorGenerationJob::create([
            'advisor_id' => $advisor->id,
            'status' => AdvisorGenerationJob::STATUS_PENDING,
            'progress' => 0,
        ]);

        // Act
        $response = $this->deleteJson('/api/advisors/jobs/'.$job->id);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Job cancelled successfully',
            'job_id' => $job->id,
        ]);

        $this->assertDatabaseHas('advisor_generation_jobs', [
            'id' => $job->id,
            'status' => AdvisorGenerationJob::STATUS_FAILED,
            'error_message' => 'Job cancelled by user',
        ]);
    }

    public function test_cancel_job_returns_400_for_non_pending_job()
    {
        // Arrange
        $advisor = Advisor::factory()->create();
        $job = AdvisorGenerationJob::create([
            'advisor_id' => $advisor->id,
            'status' => AdvisorGenerationJob::STATUS_COMPLETED,
            'progress' => 100,
        ]);

        // Act
        $response = $this->deleteJson('/api/advisors/jobs/'.$job->id);

        // Assert
        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Only pending jobs can be cancelled',
            'status' => AdvisorGenerationJob::STATUS_COMPLETED,
        ]);
    }
}
