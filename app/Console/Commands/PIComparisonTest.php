<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Services\LLMService;
use App\Services\StyleGuideService;

class PIComparisonTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pi:compare-test {advisor=alex-bogusky : The advisor slug to test}
                            {--variations=all : Comma-separated list of variations (control,a,b,c) or "all"}
                            {--prompt= : Custom prompt text (overrides defaults)}
                            {--anti-ai-style=off : Enable anti-AI style guide constraints (on/off)}
                            {--save-experiment-metadata : Save detailed experiment metadata}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run PI comparison tests across variations with default prompt library or custom prompt';

    protected LLMService $llmService;
    protected StyleGuideService $styleGuideService;

    public function __construct(LLMService $llmService, StyleGuideService $styleGuideService)
    {
        parent::__construct();
        $this->llmService = $llmService;
        $this->styleGuideService = $styleGuideService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $advisor = $this->argument('advisor');
        $customPrompt = $this->option('prompt');
        $antiAiStyle = $this->option('anti-ai-style') === 'on';
        $saveMetadata = $this->option('save-experiment-metadata');

        $this->info("🧪 Running PI comparison tests for {$advisor}");

        if ($antiAiStyle) {
            $this->info("🚫 Anti-AI style guide enabled");
        }

        // Get variations to test
        $variations = $this->parseVariations($this->option('variations'));
        $this->info("Testing variations: " . implode(', ', $variations));

        // Get test prompts
        $testPrompts = $customPrompt ? [$customPrompt] : $this->getDefaultTestPrompts();
        $this->info("Using " . count($testPrompts) . " test prompt(s)");

        // Validate PI variations exist
        $variationsPath = storage_path('app/testing/pi-variations');
        if (!File::isDirectory($variationsPath)) {
            $this->error("PI variations not found. Run 'php artisan pi:generate-variations' first.");
            return Command::FAILURE;
        }

        // Create results directory with timestamp
        $timestamp = now()->format('Y-m-d_H-i-s');
        $resultsPath = storage_path("app/testing/results/{$timestamp}");
        File::makeDirectory($resultsPath, 0755, true);

        // Run tests for each variation/prompt combination
        $experimentData = [
            'timestamp' => $timestamp,
            'advisor' => $advisor,
            'variations_tested' => $variations,
            'custom_prompt' => $customPrompt !== null,
            'prompt_count' => count($testPrompts),
            'anti_ai_style_enabled' => $antiAiStyle,
            'results' => []
        ];

        $totalTests = count($variations) * count($testPrompts);
        $currentTest = 0;

        foreach ($variations as $variation) {
            foreach ($testPrompts as $promptIndex => $testPrompt) {
                $currentTest++;
                $this->info("🔄 [{$currentTest}/{$totalTests}] Testing {$variation} with prompt " . ($promptIndex + 1));

                try {
                    $result = $this->runTest($variationsPath, $variation, $testPrompt, $promptIndex, $antiAiStyle);

                    // Add style guide analysis if enabled
                    if ($antiAiStyle && $this->styleGuideService->isEnabled()) {
                        $styleAnalysis = $this->styleGuideService->analyzeText($result['response']);
                        $result['style_guide_analysis'] = $styleAnalysis;

                        $this->info("   📝 Style Analysis: Score {$styleAnalysis['score']}, Violations: {$styleAnalysis['total_violations']}");
                    }

                    $experimentData['results'][] = $result;

                    // Save individual result with original prompt
                    $resultFileName = "{$variation}_prompt-" . ($promptIndex + 1) . "_result.md";
                    $resultContent = "# Original Prompt\n\n{$testPrompt}\n\n---\n\n# Response\n\n{$result['response']}";
                    File::put("{$resultsPath}/{$resultFileName}", $resultContent);

                    $this->info("✅ {$variation} completed (" . strlen($result['response']) . " chars)");

                } catch (\Exception $e) {
                    $this->error("❌ {$variation} failed: " . $e->getMessage());

                    $experimentData['results'][] = [
                        'variation' => $variation,
                        'prompt_index' => $promptIndex,
                        'error' => $e->getMessage(),
                        'timestamp' => now()->toISOString(),
                    ];
                }
            }
        }

        // Save experiment metadata
        if ($saveMetadata) {
            File::put("{$resultsPath}/experiment-metadata.json", json_encode($experimentData, JSON_PRETTY_PRINT));
            File::put("{$resultsPath}/test-prompts.json", json_encode($testPrompts, JSON_PRETTY_PRINT));
            $this->info("💾 Experiment metadata saved");
        }

        $this->info("🎉 PI comparison testing complete!");
        $this->info("📂 Results saved to: {$resultsPath}");
        $this->info("📊 Next step: Run 'php artisan pi:score-variations --batch={$timestamp}' to analyze results");

        return Command::SUCCESS;
    }

    protected function parseVariations(string $variationsInput): array
    {
        if ($variationsInput === 'all') {
            return ['control', 'variation-a', 'variation-b', 'variation-c'];
        }

        return collect(explode(',', $variationsInput))
            ->map(fn($v) => trim($v) === 'control' ? 'control' : 'variation-' . trim($v))
            ->toArray();
    }

    protected function getDefaultTestPrompts(): array
    {
        return [

            // Prompt 6: PromptFarm Framing Competition
            "Context: PromptFarm is a platform to spin up a personalized board of expert AI advisors. Alex, give me 3 competing ways to frame what PromptFarm is and why it matters. For each: a 1-sentence hook + a 40–60 word story + the audience it will resonate with.",

            // Prompt 7: PromptFarm 30-Second Pitch
            "Context: PromptFarm = build-your-own AI advisory board. Alex, explain PromptFarm in 30 seconds to a sharp founder who hates buzzwords. Give 2 versions: (A) plain-spoken, (B) analogy/metaphor.",

            // Prompt 8: PromptFarm We Believe Manifesto
            "Context: PromptFarm replaces one generic model with a board of perspectives. Alex, write a 120–150 word \"We believe…\" that would make fans nod and skeptics argue. No jargon. Make it shareable.",

            // Prompt 9: PromptFarm Generous Artifact
            "Context: PromptFarm. Alex, propose 1 generous artifact (tool, template, toy) we could release that markets PromptFarm by being useful on its own. Describe it in 4–6 sentences and outline the first version we can ship in a day."
        ];
    }

    protected function runTest(string $variationsPath, string $variation, string $testPrompt, int $promptIndex, bool $antiAiStyle = false): array
    {
        // Load PI and PK files
        $piPath = "{$variationsPath}/{$variation}/AlexBogusky_PI.md";
        $pkPath = "{$variationsPath}/{$variation}/AlexBogusky_PK.md";

        if (!File::exists($piPath) || !File::exists($pkPath)) {
            throw new \Exception("PI or PK file missing for {$variation}");
        }

        $piContent = File::get($piPath);
        $pkContent = File::get($pkPath);

        // Build combined context prompt
        $systemPrompt = $this->buildSystemPrompt($piContent, $pkContent, $antiAiStyle);

        // Generate response using LLMService
        $startTime = microtime(true);
        $response = $this->llmService->generateTextWithOpenRouter($testPrompt, [
            'model' => config('ai-models.primary.model'),
            'temperature' => config('ai-models.settings.pk_generation.temperature', 0.8),
            'max_tokens' => 4000,
            'system_message' => $systemPrompt,
        ]);
        $endTime = microtime(true);

        return [
            'variation' => $variation,
            'prompt_index' => $promptIndex,
            'response' => $response,
            'response_length' => strlen($response),
            'response_time_seconds' => round($endTime - $startTime, 2),
            'timestamp' => now()->toISOString(),
        ];
    }

    protected function buildSystemPrompt(string $piContent, string $pkContent, bool $antiAiStyle = false): string
    {
        $basePrompt = <<<PROMPT
You are operating under the following Project Instructions (PI):

{$piContent}

---

Your Project Knowledge (PK) base is:

{$pkContent}

---

Follow the PI instructions precisely. Use the PK knowledge base to inform your responses with specific examples, methodologies, and insights. Respond as the advisor specified in the instructions.
PROMPT;

        // Add style guide constraints if enabled
        if ($antiAiStyle && $this->styleGuideService->isEnabled()) {
            $styleConstraints = $this->styleGuideService->generateSystemPromptConstraints();
            if ($styleConstraints) {
                $basePrompt .= "\n\n---\n\n" . $styleConstraints;
            }
        }

        return $basePrompt;
    }
}
