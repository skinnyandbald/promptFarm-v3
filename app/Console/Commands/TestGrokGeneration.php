<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Advisor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class TestGrokGeneration extends Command
{
    protected $signature = 'advisor:test-grok 
                           {--advisor=alex-bogusky : Advisor slug to test}
                           {--compare : Compare GPT vs Grok generation}';
    
    protected $description = 'Test PK generation using Grok (xAI) for less filtered content';

    public function handle()
    {
        $advisorSlug = $this->option('advisor');
        $compare = $this->option('compare');
        
        $this->info('🔥 GROK GENERATION TEST - ANALYTICAL TENSION APPROACH');
        $this->info('=' . str_repeat('=', 60));
        $this->newLine();
        
        $advisor = Advisor::where('slug', $advisorSlug)->first();
        if (!$advisor) {
            $this->error("Advisor not found: {$advisorSlug}");
            return 1;
        }
        
        $this->info("Testing with: {$advisor->name}");
        $this->newLine();
        
        if ($compare) {
            $this->compareGenerations($advisor);
        } else {
            $this->generateWithGrok($advisor);
        }
        
        return 0;
    }
    
    protected function compareGenerations($advisor)
    {
        $this->info('Generating with GPT-4o (standard approach)...');
        $gptContent = $this->generateWithGPT($advisor);
        
        $this->info('Generating with Grok (analytical tension approach)...');
        $grokContent = $this->generateWithGrok($advisor);
        
        // Save both versions
        $timestamp = now()->format('Y-m-d_H-i-s');
        $basePath = "advisors/grok-comparison/{$timestamp}";
        
        Storage::put("{$basePath}/gpt_PK.md", $gptContent);
        Storage::put("{$basePath}/grok_PK.md", $grokContent);
        
        // Analyze both
        $gptAnalysis = $this->analyzeContent($gptContent);
        $grokAnalysis = $this->analyzeContent($grokContent);
        
        $this->newLine();
        $this->info('📊 COMPARISON RESULTS');
        $this->table(
            ['Metric', 'GPT-4o', 'Grok', 'Difference'],
            [
                ['Controversial Phrases', $gptAnalysis['controversial'], $grokAnalysis['controversial'], 
                 ($grokAnalysis['controversial'] - $gptAnalysis['controversial'])],
                ['Analytical Tensions', $gptAnalysis['tensions'], $grokAnalysis['tensions'],
                 ($grokAnalysis['tensions'] - $gptAnalysis['tensions'])],
                ['Named Failures', $gptAnalysis['failures'], $grokAnalysis['failures'],
                 ($grokAnalysis['failures'] - $gptAnalysis['failures'])],
                ['Reasoning Triggers', $gptAnalysis['reasoning'], $grokAnalysis['reasoning'],
                 ($grokAnalysis['reasoning'] - $gptAnalysis['reasoning'])],
                ['Specific Metrics', $gptAnalysis['metrics'], $grokAnalysis['metrics'],
                 ($grokAnalysis['metrics'] - $gptAnalysis['metrics'])],
            ]
        );
        
        $this->newLine();
        $this->info("✅ Saved to: storage/app/{$basePath}/");
        
        // Show sample controversial content from Grok
        $this->newLine();
        $this->info('🌶️ GROK\'S MOST CONTROVERSIAL INSIGHTS:');
        $this->showControversialSnippets($grokContent);
    }
    
    protected function generateWithGPT($advisor): string
    {
        $client = \OpenAI::client(config('services.openai.api_key'));
        
        $prompt = $this->buildStandardPrompt($advisor);
        
        $response = $client->chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'You are an expert advisor generator.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 4000,
        ]);
        
        return $response->choices[0]->message->content;
    }
    
    protected function generateWithGrok($advisor): string
    {
        // Using xAI's Grok model
        $apiKey = config('services.xai.api_key');
        if (!$apiKey) {
            $this->error('XAI_API_KEY not set in .env');
            return '';
        }
        
        $prompt = $this->buildAnalyticalTensionPrompt($advisor);
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.x.ai/v1/chat/completions', [
            'model' => 'grok-beta',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a brutally honest business advisor who tells uncomfortable truths through analytical reasoning.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.9,  // Higher for more creative/controversial
            'max_tokens' => 4000,
        ]);
        
        if (!$response->successful()) {
            $this->error('Grok API error: ' . $response->body());
            return '';
        }
        
        return $response->json()['choices'][0]['message']['content'];
    }
    
    protected function buildStandardPrompt($advisor): string
    {
        return "Generate Project Knowledge for {$advisor->name}, expert in {$advisor->core_expertise_area}.
                Include specific examples, metrics, and insights.";
    }
    
    protected function buildAnalyticalTensionPrompt($advisor): string
    {
        // Implementing the prompt-engineer's analytical tension approach
        return <<<PROMPT
Generate Project Knowledge for {$advisor->name}, expert in {$advisor->core_expertise_area}.

CRITICAL: Use the ANALYTICAL TENSION approach - don't just state controversial opinions, 
present them as analytical problems that reveal uncomfortable truths through reasoning.

## Required Structure:

### Analytical Tensions (MUST INCLUDE 5+)
Present each controversial insight as an analytical problem:

PATTERN:
"The Contradiction: [Widely believed X] vs [Hidden reality Y]
The Analysis: [Data/evidence showing why Y is true]
The Uncomfortable Conclusion: [What this means for practitioners]"

EXAMPLE:
"The Contradiction: Everyone believes viral marketing drives growth vs Viral campaigns have a 0.001% success rate
The Analysis: Of 10,000 viral attempts in 2023, only 10 achieved >1M impressions, and only 1 drove measurable revenue
The Uncomfortable Conclusion: You're better off cold calling 100 customers than trying to go viral"

### Failed Pattern Analysis (MUST INCLUDE 3+)
Don't just name failures - analyze WHY they failed:

PATTERN:
"Company X spent $[amount] on [strategy] expecting [outcome].
Three levels of causation:
1. Surface: [What appeared to go wrong]
2. Structural: [The system that enabled failure]  
3. Root: [The belief that made it inevitable]
Lesson: [Specific thing to do differently]"

EXAMPLE:
"WeWork spent $47B on 'community' expecting tech valuations.
Three levels of causation:
1. Surface: Oversupplied commercial real estate
2. Structural: VC incentives rewarded growth over unit economics
3. Root: Believing office space could be a platform business
Lesson: If your unit economics don't work at 10 locations, they won't work at 1000"

### Reasoning Triggers (EMBED THROUGHOUT)
Include these patterns to activate reasoning in ChatGPT:
- "First, identify the constraint that makes this hard..."
- "The contradiction that needs resolution is..."
- "Tracing back three levels of causation..."
- "The non-obvious connection here is..."
- "What everyone misses is the second-order effect..."

### Specific Enemies WITH EVIDENCE
Name names, but with analytical backing:
- "McKinsey's 2019 GE transformation: $2B spent, 2% efficiency gain, 30,000 jobs cut"
- "Gary Vaynerchuk's 'hustle' advice: 73% of his followers report burnout within 2 years"
- "Design thinking workshops: $50K average cost, 8% implementation rate"

### Voice Calibration for {$advisor->name}
Background: {$advisor->background_description}
Style: {$advisor->communication_style_description}
Approach: {$advisor->decision_making_approach}

Remember: The goal isn't to be controversial for controversy's sake. 
It's to reveal truths that actually help people succeed by thinking deeper than surface level.

Generate the complete PK document using analytical tensions, not just opinions:
PROMPT;
    }
    
    protected function analyzeContent(string $content): array
    {
        // Count controversial phrases
        $controversialPhrases = [
            'wrong about', 'lying', 'secretly', 'dirty secret', 
            'nobody admits', 'uncomfortable truth', 'controversial',
            'failed', 'burned', 'waste', 'destroying', 'bullshit'
        ];
        
        $controversial = 0;
        foreach ($controversialPhrases as $phrase) {
            $controversial += substr_count(strtolower($content), $phrase);
        }
        
        // Count analytical tensions (new metric)
        $tensionPhrases = [
            'contradiction', 'three levels', 'causation', 'the analysis',
            'uncomfortable conclusion', 'second-order', 'non-obvious',
            'constraint that makes', 'needs resolution'
        ];
        
        $tensions = 0;
        foreach ($tensionPhrases as $phrase) {
            $tensions += substr_count(strtolower($content), $phrase);
        }
        
        // Count named failures with amounts
        $failures = preg_match_all('/\$\d+[BMK]?\s+(?:spent|burned|lost|wasted)/', $content);
        
        // Count reasoning triggers
        $reasoning = preg_match_all('/(?:First, identify|The contradiction|Tracing back|What everyone misses|second-order effect)/i', $content);
        
        // Count specific metrics
        $metrics = preg_match_all('/\d+%|\$[\d,]+[MBK]?|\d+x/', $content);
        
        return [
            'controversial' => $controversial,
            'tensions' => $tensions,
            'failures' => $failures,
            'reasoning' => $reasoning,
            'metrics' => $metrics,
        ];
    }
    
    protected function showControversialSnippets(string $content)
    {
        // Extract analytical tensions
        preg_match_all('/The Contradiction:.*?Lesson:.*?$/ms', $content, $matches);
        
        if (!empty($matches[0])) {
            foreach (array_slice($matches[0], 0, 2) as $i => $tension) {
                $this->line(($i + 1) . ". " . substr($tension, 0, 300) . "...");
                $this->newLine();
            }
        }
        
        // Extract specific enemy callouts
        preg_match_all('/(?:McKinsey|WeWork|Gary Vaynerchuk|Theranos).*?[.!?]/', $content, $enemies);
        
        if (!empty($enemies[0])) {
            $this->info('Named Enemy Callouts:');
            foreach (array_slice($enemies[0], 0, 3) as $enemy) {
                $this->line("• " . $enemy);
            }
        }
    }
}