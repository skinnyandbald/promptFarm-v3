<?php

namespace Tests\Feature;

use App\Models\Advisor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UnifiedAnalysisCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup advisors disk for testing
        Storage::fake('advisors');
        
        // Clean up any leftover test data from previous runs
        if (File::exists(storage_path('app/advisor-tests'))) {
            File::deleteDirectory(storage_path('app/advisor-tests'));
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up test artifacts
        if (File::exists(storage_path('app/advisor-tests'))) {
            File::deleteDirectory(storage_path('app/advisor-tests'));
        }
        
        parent::tearDown();
    }

    public function test_unified_analysis_command_exists(): void
    {
        $this->artisan('advisor:analyze --help')
            ->assertSuccessful()
            ->expectsOutputToContain('Unified command for all advisor quality analysis');
    }

    public function test_historical_analysis_type_runs_without_error(): void
    {
        $this->artisan('advisor:analyze historical')
            ->assertSuccessful()
            ->expectsOutputToContain('Analyzing Historical PI Patterns');
    }

    public function test_versions_analysis_with_no_advisors_shows_error(): void
    {
        $this->artisan('advisor:analyze versions')
            ->assertFailed()
            ->expectsOutputToContain('No advisors found');
    }

    public function test_versions_analysis_with_advisor_runs_successfully(): void
    {
        // Create test advisor
        Advisor::create([
            'name' => 'Test Advisor',
            'slug' => 'test-advisor',
            'full_name' => 'Test Expert Advisor',
            'known_for' => 'Testing expertise',
            'era' => '2020s',
            'style' => 'Analytical and precise approach',
            'industry' => 'Software Testing',
            'primary_objective' => 'Help teams write better tests',
            'core_expertise_area' => 'Testing',
            'related_expertise_areas' => ['analytical', 'precise'],
            'communication_style_description' => 'direct',
            'decision_making_approach' => 'Data-driven testing decisions',
            'key_phrases_or_terminology' => ['test coverage', 'quality assurance'],
            'emotional_characteristics' => 'Patient and methodical',
            'unique_perspectives_or_contrarian_stances' => 'Tests should fail first',
        ]);

        // Create test files matching actual directory structure
        $timestamp = now()->format('Y-m-d');
        $jobId = 'test123';
        $basePath = "test-advisor/{$timestamp}-job-{$jobId}";
        Storage::disk('advisors')->makeDirectory($basePath);
        Storage::disk('advisors')->put("{$basePath}/TestAdvisor_PI.md", 'Test PI content');
        Storage::disk('advisors')->put("{$basePath}/TestAdvisor_PK.md", 'Test PK content');
        Storage::disk('advisors')->put("{$basePath}/metadata.json", json_encode(['version' => '1.0.0']));

        $this->artisan('advisor:analyze versions --advisor=test-advisor')
            ->assertSuccessful()
            ->expectsOutputToContain('Comparing Advisor Versions')
            ->expectsOutputToContain('Test Advisor');
    }

    public function test_quality_analysis_requires_conversation_file(): void
    {
        $this->artisan('advisor:analyze quality')
            ->assertFailed()
            ->expectsOutputToContain('Conversation file not found');
    }

    public function test_quality_analysis_with_valid_json_conversation(): void
    {
        // Create test conversation file
        $conversationFile = base_path('test_conversation.json');
        $conversation = [
            'messages' => [
                ['role' => 'user', 'content' => 'Hello! This is amazing!'],
                ['role' => 'assistant', 'content' => 'Thank you! How can I help you today?'],
                ['role' => 'user', 'content' => 'Can you explain something?'],
                ['role' => 'assistant', 'content' => 'Of course! For example, let me show you...'],
            ],
        ];

        File::put($conversationFile, json_encode($conversation));

        try {
            $this->artisan("advisor:analyze quality --advisor={$conversationFile}")
                ->assertSuccessful()
                ->expectsOutputToContain('Analyzing Conversation Quality')
                ->expectsOutputToContain('Total Messages')
                ->expectsOutputToContain('Quality Score');
        } finally {
            File::delete($conversationFile);
        }
    }

    public function test_approach_analysis_shows_comparison_table(): void
    {
        $this->artisan('advisor:analyze approach')
            ->assertSuccessful()
            ->expectsOutputToContain('Testing Generation Approaches')
            ->expectsOutputToContain('standard')
            ->expectsOutputToContain('analytical')
            ->expectsOutputToContain('controversial');
    }

    public function test_invalid_analysis_type_shows_error(): void
    {
        $this->artisan('advisor:analyze invalid_type')
            ->assertFailed()
            ->expectsOutputToContain('Invalid analysis type');
    }

    public function test_versions_analysis_can_output_json(): void
    {
        // Create test advisor
        Advisor::create([
            'name' => 'Test Advisor',
            'slug' => 'test-advisor',
            'full_name' => 'Test Expert Advisor',
            'known_for' => 'Testing expertise',
            'era' => '2020s',
            'style' => 'Analytical approach',
            'industry' => 'Software Testing',
            'primary_objective' => 'Help teams write better tests',
            'core_expertise_area' => 'Testing',
            'related_expertise_areas' => ['analytical'],
            'communication_style_description' => 'direct',
            'decision_making_approach' => 'Data-driven decisions',
            'key_phrases_or_terminology' => ['test coverage'],
            'emotional_characteristics' => 'Patient',
            'unique_perspectives_or_contrarian_stances' => 'Tests should fail first',
        ]);

        $this->artisan('advisor:analyze versions --output=json')
            ->assertSuccessful();

        // Check that JSON file was created
        $this->assertFileExists(storage_path('app/advisor-tests/version_comparison.json'));

        // Clean up
        File::delete(storage_path('app/advisor-tests/version_comparison.json'));
    }

    public function test_historical_analysis_with_existing_data(): void
    {
        // Create historical PI file
        $historicalPath = storage_path('app/alex_bogusky/v1');
        File::makeDirectory($historicalPath, 0755, true, true);
        File::put("{$historicalPath}/chatgpt-pi-conversation.md", 'Historical content with questions? And challenges!');

        // Create current PI file
        Storage::disk('advisors')->put('bogusky/current/PI.md', 'Current content with fewer questions');

        try {
            $this->artisan('advisor:analyze historical --advisor=bogusky')
                ->assertSuccessful()
                ->expectsOutputToContain('Analyzing Historical PI Patterns')
                ->expectsOutputToContain('Historical Avg')
                ->expectsOutputToContain('Current')
                ->expectsOutputToContain('Best Practices');
        } finally {
            // Clean up
            File::deleteDirectory(storage_path('app/alex_bogusky'));
        }
    }

    public function test_quality_analysis_calculates_engagement_metrics(): void
    {
        $conversationFile = base_path('test_conversation.json');
        $conversation = [
            'messages' => [
                ['role' => 'user', 'content' => 'This is amazing! How does it work?'],
                ['role' => 'assistant', 'content' => 'Great question! For example, it works like this...'],
                ['role' => 'user', 'content' => 'Wow! Can you give me another example?'],
                ['role' => 'assistant', 'content' => 'Actually, here\'s a challenge to consider...'],
            ],
        ];

        File::put($conversationFile, json_encode($conversation));

        try {
            $this->artisan("advisor:analyze quality --advisor={$conversationFile}")
                ->assertSuccessful()
                ->expectsOutputToContain('Questions Asked')
                ->expectsOutputToContain('Excitement Indicators')
                ->expectsOutputToContain('Quality Recommendations');
        } finally {
            File::delete($conversationFile);
        }
    }

    public function test_approach_analysis_with_specific_advisor(): void
    {
        $this->artisan('advisor:analyze approach --advisor=bogusky --compare=analytical')
            ->assertSuccessful()
            ->expectsOutputToContain('Testing analytical approach for bogusky');
    }

    public function test_versions_analysis_can_output_csv(): void
    {
        // Create test advisor with consistent key/slug
        $advisor = Advisor::create([
            'name' => 'CSV Test Advisor',
            'slug' => 'csv-test-advisor',
            'full_name' => 'CSV Test Full Name',
            'known_for' => 'CSV output testing',
            'era' => '2020s',
            'style' => 'Data-driven approach',
            'industry' => 'Software Testing',
            'primary_objective' => 'Test CSV output functionality',
            'core_expertise_area' => 'Testing',
            'related_expertise_areas' => ['csv', 'data'],
            'communication_style_description' => 'clear',
            'decision_making_approach' => 'Structured data decisions',
            'key_phrases_or_terminology' => ['csv format', 'data export'],
            'emotional_characteristics' => 'Calm',
            'unique_perspectives_or_contrarian_stances' => 'CSV > JSON',
        ]);

        // Create test version files matching actual directory structure
        $timestamp = now()->format('Y-m-d');
        $jobId = 'csv123';
        $basePath = "csv-test-advisor/{$timestamp}-job-{$jobId}";
        Storage::disk('advisors')->makeDirectory($basePath);
        Storage::disk('advisors')->put("{$basePath}/CSVTestAdvisor_PI.md", 'Test PI content');
        Storage::disk('advisors')->put("{$basePath}/CSVTestAdvisor_PK.md", 'Test PK content');
        Storage::disk('advisors')->put("{$basePath}/metadata.json", json_encode(['version' => '1.0.0']));

        // Run analysis specifically for this advisor to ensure we get results
        $this->artisan('advisor:analyze versions --advisor=csv-test-advisor --output=csv')
            ->assertSuccessful();

        // Check that CSV file was created
        $this->assertFileExists(storage_path('app/advisor-tests/version_comparison.csv'));

        // Verify CSV content structure
        $csvContent = File::get(storage_path('app/advisor-tests/version_comparison.csv'));
        $this->assertNotEmpty($csvContent, 'CSV file should not be empty');

        // Check for proper CSV structure
        $lines = explode("\n", trim($csvContent));
        $this->assertGreaterThanOrEqual(1, count($lines), 'CSV should have at least a header row');

        // Check header
        $this->assertStringContainsString('Advisor', $lines[0]);
        $this->assertStringContainsString('Version', $lines[0]);

        // If we have a data row, verify it contains the advisor name
        if (count($lines) > 1) {
            // Find the row with our test data (might not be first row due to existing data)
            $foundTestData = false;
            foreach ($lines as $index => $line) {
                if ($index === 0) continue; // Skip header
                if (str_contains($line, 'CSV Test Advisor')) {
                    $foundTestData = true;
                    break;
                }
            }
            $this->assertTrue($foundTestData, 'Should find CSV Test Advisor in output');
        }

        // Clean up
        File::delete(storage_path('app/advisor-tests/version_comparison.csv'));
        Storage::disk('advisors')->deleteDirectory('csv-test-advisor');
        $advisor->delete();
    }
}
