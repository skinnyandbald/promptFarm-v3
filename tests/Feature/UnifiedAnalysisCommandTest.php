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
            'key' => 'test_advisor',
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

        // Create test files
        Storage::disk('advisors')->put('test_advisor/current/PI.md', 'Test PI content');
        Storage::disk('advisors')->put('test_advisor/current/PK.md', 'Test PK content');

        $this->artisan('advisor:analyze versions --advisor=test_advisor')
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
            'key' => 'test_advisor',
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
        $this->assertFileExists(base_path('version_comparison.json'));

        // Clean up
        File::delete(base_path('version_comparison.json'));
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
}