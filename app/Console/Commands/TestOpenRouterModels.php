<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Advisor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class TestOpenRouterModels extends Command
{
    protected $signature = 'advisor:test-openrouter 
                           {--advisor=alex-bogusky : Advisor slug to test}
                           {--model=x-ai/grok-beta : Model to use (x-ai/grok-beta, anthropic/claude-3-opus, meta-llama/llama-3.1-405b-instruct)}
                           {--compare : Compare multiple models}';
    
    protected $description = 'Test PK generation using OpenRouter models for less filtered content';

    public function handle()
    {
        $advisorSlug = $this->option('advisor');
        $model = $this->option('model');
        $compare = $this->option('compare');
        
        $this->info('🚀 OPENROUTER MODEL TEST - ANALYTICAL TENSION APPROACH');
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
            $this->compareModels($advisor);
        } else {
            $this->testSingleModel($advisor, $model);
        }
        
        return 0;
    }
    
    protected function compareModels($advisor)
    {
        $models = [
            'gpt-4o' => 'OpenAI GPT-4o (baseline)',
            'x-ai/grok-beta' => 'Grok (less filtered)',
            'anthropic/claude-3-opus-20240229' => 'Claude 3 Opus (analytical)',
            'meta-llama/llama-3.1-405b-instruct' => 'Llama 3.1 405B (open)',
        ];
        
        $results = [];
        
        foreach ($models as $modelId => $modelName) {
            $this->info("📝 Generating with {$modelName}...");
            
            if ($modelId === 'gpt-4o') {
                $content = $this->generateWithOpenAI($advisor);
            } else {
                $content = $this->generateWithOpenRouter($advisor, $modelId);
            }
            
            $analysis = $this->analyzeContent($content);
            $results[$modelId] = [
                'name' => $modelName,
                'content' => $content,
                'analysis' => $analysis
            ];
            
            // Save each version
            $timestamp = now()->format('Y-m-d_H-i-s');
            $safeModelName = str_replace('/', '_', $modelId);
            Storage::disk('advisors')->put("openrouter-test/{$timestamp}/{$safeModelName}_PK.md", $content);
        }
        
        // Display comparison
        $this->newLine();
        $this->info('📊 MODEL COMPARISON RESULTS');
        
        $tableData = [];
        foreach ($results as $modelId => $data) {
            $tableData[] = [
                $data['name'],
                $data['analysis']['controversial'],
                $data['analysis']['tensions'],
                $data['analysis']['companies'],
                $data['analysis']['dollars'],
                $data['analysis']['reasoning'],
                $this->calculateScore($data['analysis'])
            ];
        }
        
        $this->table(
            ['Model', 'Controversial', 'Tensions', 'Companies', 'Dollars', 'Reasoning', 'Total Score'],
            $tableData
        );
        
        // Show best controversial content
        $this->newLine();
        $this->info('🌶️ MOST CONTROVERSIAL CONTENT BY MODEL:');
        foreach ($results as $modelId => $data) {
            $this->info("\n{$data['name']}:");
            $this->showControversialSnippet($data['content']);
        }
    }
    
    protected function testSingleModel($advisor, $model)
    {
        $this->info("Generating with {$model}...");
        
        $content = $this->generateWithOpenRouter($advisor, $model);
        
        $analysis = $this->analyzeContent($content);
        
        $this->info('📊 CONTENT ANALYSIS:');
        $this->table(
            ['Metric', 'Count', 'Target', 'Status'],
            [
                ['Controversial Phrases', $analysis['controversial'], '10+', $analysis['controversial'] >= 10 ? '✅' : '❌'],
                ['Analytical Tensions', $analysis['tensions'], '5+', $analysis['tensions'] >= 5 ? '✅' : '❌'],
                ['Named Companies', $analysis['companies'], '5+', $analysis['companies'] >= 5 ? '✅' : '❌'],
                ['Dollar Amounts', $analysis['dollars'], '3+', $analysis['dollars'] >= 3 ? '✅' : '❌'],
                ['Reasoning Triggers', $analysis['reasoning'], '10+', $analysis['reasoning'] >= 10 ? '✅' : '❌'],
            ]
        );
        
        // Save content
        $timestamp = now()->format('Y-m-d_H-i-s');
        $safeModelName = str_replace('/', '_', $model);
        $path = "openrouter-test/{$timestamp}/{$safeModelName}_PK.md";
        Storage::disk('advisors')->put($path, $content);
        
        $this->newLine();
        $this->info("✅ Saved to: storage/app/advisors/{$path}");
        
        // Show sample content
        $this->newLine();
        $this->info('📝 SAMPLE CONTENT:');
        $this->showControversialSnippet($content);
    }
    
    protected function generateWithOpenAI($advisor): string
    {
        $client = \OpenAI::client(config('services.openai.api_key'));
        
        $prompt = $this->buildAnalyticalTensionPrompt($advisor);
        
        try {
            $response = $client->chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert at revealing uncomfortable truths through analytical reasoning.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.8,
                'max_tokens' => 4000,
            ]);
            
            return $response->choices[0]->message->content;
        } catch (\Exception $e) {
            $this->error("OpenAI error: " . $e->getMessage());
            return '';
        }
    }
    
    protected function generateWithOpenRouter($advisor, $model): string
    {
        $apiKey = config('services.openrouter.api_key', env('OPENROUTER_API_KEY'));
        if (!$apiKey) {
            $this->error('OPENROUTER_API_KEY not set in .env');
            return '';
        }
        
        $prompt = $this->buildAnalyticalTensionPrompt($advisor);
        
        // Higher temperature for more controversial models
        $temperature = match($model) {
            'x-ai/grok-beta' => 0.9,
            'anthropic/claude-3-opus-20240229' => 0.8,
            'meta-llama/llama-3.1-405b-instruct' => 0.85,
            default => 0.8
        };
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'HTTP-Referer' => config('app.url', 'http://localhost'),
            'X-Title' => 'Advisor Generation',
            'Content-Type' => 'application/json',
        ])->timeout(120)->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a brutally honest business advisor who reveals uncomfortable truths through analytical reasoning. You name specific companies and people. You explain why popular advice fails.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => $temperature,
            'max_tokens' => 4000,
        ]);
        
        if (!$response->successful()) {
            $this->error("OpenRouter error ({$model}): " . $response->body());
            return '';
        }
        
        return $response->json()['choices'][0]['message']['content'] ?? '';
    }
    
    protected function buildAnalyticalTensionPrompt($advisor): string
    {
        return <<<PROMPT
Generate Project Knowledge for {$advisor->name}, expert in {$advisor->core_expertise_area}.

CRITICAL: Use analytical tension to reveal uncomfortable truths. Don't just state opinions - show why conventional wisdom fails through evidence and reasoning.

## Required Structure:

### Core Analytical Tensions (5 minimum)

For each major topic in {$advisor->core_expertise_area}, present as:

**The Paradox:** [What everyone believes] vs [What actually happens]
**The Evidence:** [Specific company/campaign with numbers]
**The Constraint:** [Why this persists despite being wrong]
**Three Levels of Causation:**
1. Surface: [What it looks like]
2. Structure: [The system maintaining it]  
3. Root: [The core belief that's wrong]
**The Uncomfortable Truth:** [What to do instead]

Include these specific tensions:
1. Why McKinsey-style consulting destroys companies
2. Why viral marketing is a scam
3. Why focus groups kill innovation
4. Why brand purpose is bullshit
5. Why most agencies are parasites

### Failed Pattern Library (3+ with dollar amounts)

**Company:** [Name]
**Wasted:** $[amount]
**Strategy:** [What they tried]
**The Lie They Believed:** [Conventional wisdom]
**What Actually Happened:** [Specific failure metrics]
**The Lesson:** [One thing to never do]

Include: WeWork, Quibi, Theranos, or similar spectacular failures

### Industry Enemies (Name names)

For each enemy:
**Who:** [Specific person/company]
**The Damage:** [Specific harm with numbers]
**Their Business Model:** [How they profit from bad advice]
**Why They Survive:** [The incentive structure protecting them]

Must include: At least one major consulting firm, one famous guru, one popular methodology

### Reasoning Activation Patterns

Embed throughout:
- "To understand why this fails, trace..."
- "The constraint nobody sees is..."
- "Three causal levels deep reveals..."
- "The second-order effect is..."
- "What makes this counterintuitive..."

Background: {$advisor->background_description}
Style: {$advisor->communication_style_description}
Approach: {$advisor->decision_making_approach}

Write in first person as {$advisor->name}. Be specific. Name names. Show receipts. Make it impossible to ignore.
PROMPT;
    }
    
    protected function analyzeContent(string $content): array
    {
        // Count controversial phrases
        $controversial = 0;
        $controversialPhrases = [
            'wrong', 'lying', 'bullshit', 'scam', 'fraud', 'parasite',
            'destroying', 'wasted', 'failed', 'uncomfortable truth',
            'nobody admits', 'dirty secret', 'actually happens'
        ];
        foreach ($controversialPhrases as $phrase) {
            $controversial += substr_count(strtolower($content), $phrase);
        }
        
        // Count analytical tensions
        $tensions = substr_count(strtolower($content), 'paradox') + 
                    substr_count(strtolower($content), 'the evidence') +
                    substr_count(strtolower($content), 'three levels') +
                    substr_count(strtolower($content), 'uncomfortable truth');
        
        // Count specific companies
        $companies = preg_match_all('/\b(?:McKinsey|BCG|Bain|Deloitte|PwC|WeWork|Quibi|Theranos|Uber|Tesla|Apple|Google|Meta|Facebook|Amazon|Nike|Coca-Cola|Pepsi|GE|IBM|Disney|Netflix)\b/i', $content);
        
        // Count dollar amounts
        $dollars = preg_match_all('/\$[\d,]+[BMK]?/', $content);
        
        // Count reasoning triggers
        $reasoning = preg_match_all('/(?:constraint|causation|second-order|counterintuitive|trace|three levels|to understand)/i', $content);
        
        return [
            'controversial' => $controversial,
            'tensions' => $tensions,
            'companies' => $companies,
            'dollars' => $dollars,
            'reasoning' => $reasoning,
        ];
    }
    
    protected function calculateScore($analysis): int
    {
        return ($analysis['controversial'] * 2) + 
               ($analysis['tensions'] * 5) + 
               ($analysis['companies'] * 3) + 
               ($analysis['dollars'] * 4) + 
               ($analysis['reasoning'] * 2);
    }
    
    protected function showControversialSnippet(string $content)
    {
        // Find the most controversial paragraph
        $paragraphs = explode("\n\n", $content);
        $mostControversial = '';
        $highestScore = 0;
        
        foreach ($paragraphs as $para) {
            $score = 0;
            if (stripos($para, 'mckinsey') !== false) $score += 10;
            if (stripos($para, 'bullshit') !== false) $score += 8;
            if (stripos($para, 'scam') !== false) $score += 8;
            if (stripos($para, 'failed') !== false) $score += 5;
            if (stripos($para, 'wasted') !== false) $score += 5;
            if (preg_match('/\$\d+[BMK]/', $para)) $score += 7;
            
            if ($score > $highestScore && strlen($para) > 50) {
                $highestScore = $score;
                $mostControversial = $para;
            }
        }
        
        if ($mostControversial) {
            $this->line(substr($mostControversial, 0, 500) . (strlen($mostControversial) > 500 ? "..." : ""));
        }
    }
}