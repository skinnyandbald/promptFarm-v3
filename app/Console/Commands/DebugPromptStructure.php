<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class DebugPromptStructure extends Command
{
    protected $signature = 'advisor:debug-prompts 
                           {--model=x-ai/grok-3 : Model to test with}';

    protected $description = 'Debug which prompt structures cause formalization';

    protected array $testPrompts = [
        'original-v2' => 'Original V2 (No Voice Anchor, No Framework)',
        'test-a' => 'Test A (Voice Anchor + Comm Rules, NO Framework)',
        'test-b' => 'Test B (Voice Anchor + Framework, NO Comm Rules)',
        'test-c' => 'Test C (Voice Anchor ONLY)',
    ];

    protected string $userPrompt = "I'm building PromptFarm - a platform that creates AI advisors modeled after real experts like you. Each advisor maintains their authentic voice and expertise through systematic prompt engineering. Unlike generic ChatGPT, these advisors have strong personalities, specific frameworks, and challenge thinking like the real experts would. The problem: Every AI tool sounds the same. Generic. Safe. Useless for real strategic thinking. Our solution: Councils of 4 expert advisors that actually sound like Bogusky, Hormozi, Halbert, and Cal Henderson. They argue, they push back, they have opinions. Need your perspective on five critical questions: 1. How should we position PromptFarm against ChatGPT and other generic AI tools? 2. What mechanisms should we build to ensure our advisors maintain authentic voices and don't devolve into generic AI responses? 3. What cultural tension or industry friction should PromptFarm tap into? 4. What tool or feature would create natural conversation and demonstrate our value? 5. What uncomfortable truth about AI advisors should we address head-on?";

    public function handle(): int
    {
        $model = $this->option('model');
        $apiKey = config('services.openrouter.api_key');

        if (! $apiKey) {
            $this->error('OPENROUTER_API_KEY not configured');

            return 1;
        }

        $this->info('🔬 SYSTEMATIC PROMPT STRUCTURE DEBUG');
        $this->info('Testing model: '.((string) $model));
        $this->newLine();

        $results = [];

        foreach ($this->testPrompts as $file => $description) {
            $this->info("Testing: {$description}");

            // Load the prompt template
            $promptPath = storage_path("app/advisors/test-debug/prompt-{$file}.md");
            if (! file_exists($promptPath)) {
                $this->error("Prompt file not found: {$promptPath}");

                continue;
            }

            $systemPrompt = file_get_contents($promptPath);
            if ($systemPrompt === false) {
                $this->error("Could not read prompt file: {$promptPath}");

                continue;
            }

            // Generate response
            $response = $this->generateWithOpenRouter($systemPrompt, (string) $model, $apiKey);

            // Analyze response characteristics
            $analysis = $this->analyzeResponse($response);
            $results[$file] = [
                'description' => $description,
                'response' => $response,
                'analysis' => $analysis,
            ];

            // Save response
            $outputPath = "test-debug/responses/{$file}-response.md";
            Storage::disk('advisors')->put($outputPath, $response);

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Confrontational Score', $analysis['confrontational']],
                    ['Numbered Lists', $analysis['numbered_lists']],
                    ['Questions Asked', $analysis['questions']],
                    ['Specific Companies', $analysis['companies']],
                    ['First Person Usage', $analysis['first_person']],
                    ['Academic Tone', $analysis['academic_tone']],
                ]
            );
            $this->newLine();
        }

        // Compare results
        $this->info('📊 COMPARATIVE ANALYSIS');
        $this->compareResults($results);

        return 0;
    }

    protected function generateWithOpenRouter(string $systemPrompt, string $model, string $apiKey): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'HTTP-Referer' => config('app.url'),
            'X-Title' => 'Prompt Structure Debug',
            'Content-Type' => 'application/json',
        ])->timeout(120)->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
                [
                    'role' => 'user',
                    'content' => $this->userPrompt,
                ],
            ],
            'temperature' => 0.9,
            'max_tokens' => 2000,
        ]);

        if (! $response->successful()) {
            throw new \Exception('OpenRouter error: '.$response->body());
        }

        return $response->json()['choices'][0]['message']['content'] ?? '';
    }

    protected function analyzeResponse(string $response): array
    {
        $analysis = [];

        // Confrontational language
        $confrontationalPhrases = ['you\'re wrong', 'that\'s bullshit', 'here\'s the truth',
            'stop pretending', 'the lie', 'wake up', 'hate', 'enemy'];
        $confrontationalCount = 0;
        foreach ($confrontationalPhrases as $phrase) {
            $confrontationalCount += substr_count(strtolower($response), $phrase);
        }
        $analysis['confrontational'] = $confrontationalCount;

        // Numbered lists (sign of formalization)
        $analysis['numbered_lists'] = preg_match_all('/^\d+\./m', $response);

        // Questions asked (engagement)
        $analysis['questions'] = substr_count($response, '?');

        // Specific companies mentioned
        $companies = ['McKinsey', 'Domino', 'Burger King', 'Mini', 'Pepsi', 'Coca-Cola',
            'WeWork', 'Quibi', 'Theranos', 'WPP', 'Google', 'ChatGPT'];
        $companyCount = 0;
        foreach ($companies as $company) {
            if (stripos($response, $company) !== false) {
                $companyCount++;
            }
        }
        $analysis['companies'] = $companyCount;

        // First person usage (authenticity)
        $analysis['first_person'] = substr_count($response, 'I ') +
                                    substr_count($response, "I'") +
                                    substr_count($response, 'my ') +
                                    substr_count($response, 'me ');

        // Academic tone indicators
        $academicPhrases = ['furthermore', 'therefore', 'consequently', 'thus',
            'in conclusion', 'framework', 'methodology', 'systematic'];
        $academicCount = 0;
        foreach ($academicPhrases as $phrase) {
            $academicCount += substr_count(strtolower($response), $phrase);
        }
        $analysis['academic_tone'] = $academicCount;

        return $analysis;
    }

    protected function compareResults(array $results): void
    {
        $comparison = [];
        foreach ($results as $key => $result) {
            $comparison[] = [
                $result['description'],
                $result['analysis']['confrontational'],
                $result['analysis']['numbered_lists'],
                $result['analysis']['questions'],
                $result['analysis']['companies'],
                $result['analysis']['first_person'],
                $result['analysis']['academic_tone'],
            ];
        }

        $this->table(
            ['Test', 'Confrontational', 'Lists', 'Questions', 'Companies', '1st Person', 'Academic'],
            $comparison
        );

        // Identify the culprit
        $this->newLine();
        $this->info('🎯 DIAGNOSIS:');

        // Compare original vs others
        $original = $results['original-v2']['analysis'] ?? null;
        if ($original) {
            foreach (['test-a', 'test-b', 'test-c'] as $test) {
                if (isset($results[$test])) {
                    $testAnalysis = $results[$test]['analysis'];
                    if ($testAnalysis['academic_tone'] > $original['academic_tone'] * 1.5) {
                        $this->warn("⚠️ {$results[$test]['description']} shows increased formalization");
                    }
                    if ($testAnalysis['confrontational'] < $original['confrontational'] * 0.7) {
                        $this->warn("⚠️ {$results[$test]['description']} reduced confrontational tone");
                    }
                }
            }
        }
    }
}
