<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Advisor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;

class TestGrokViaVibeTools extends Command
{
    protected $signature = 'advisor:test-grok-vibe 
                           {--advisor=alex-bogusky : Advisor slug to test}
                           {--compare : Compare GPT vs Grok generation}';
    
    protected $description = 'Test PK generation using Grok via vibe-tools for less filtered content';

    public function handle()
    {
        $advisorSlug = $this->option('advisor');
        $compare = $this->option('compare');
        
        $this->info('🔥 GROK GENERATION TEST (via vibe-tools) - ANALYTICAL TENSION APPROACH');
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
            $this->testAnalyticalTensionApproach($advisor);
        }
        
        return 0;
    }
    
    protected function compareGenerations($advisor)
    {
        $this->info('📝 Generating with GPT-4o (standard controversial approach)...');
        $gptContent = $this->generateWithGPT($advisor);
        
        $this->info('🧠 Generating with Grok (analytical tension approach)...');
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
            ['Metric', 'GPT-4o', 'Grok', 'Improvement'],
            [
                ['Controversial Phrases', $gptAnalysis['controversial'], $grokAnalysis['controversial'], 
                 $this->formatDiff($grokAnalysis['controversial'] - $gptAnalysis['controversial'])],
                ['Analytical Tensions', $gptAnalysis['tensions'], $grokAnalysis['tensions'],
                 $this->formatDiff($grokAnalysis['tensions'] - $gptAnalysis['tensions'])],
                ['Named Failures', $gptAnalysis['failures'], $grokAnalysis['failures'],
                 $this->formatDiff($grokAnalysis['failures'] - $gptAnalysis['failures'])],
                ['Reasoning Triggers', $gptAnalysis['reasoning'], $grokAnalysis['reasoning'],
                 $this->formatDiff($grokAnalysis['reasoning'] - $gptAnalysis['reasoning'])],
                ['Specific Companies', $gptAnalysis['companies'], $grokAnalysis['companies'],
                 $this->formatDiff($grokAnalysis['companies'] - $gptAnalysis['companies'])],
            ]
        );
        
        $this->newLine();
        $this->info("✅ Saved to: storage/app/{$basePath}/");
        
        // Show sample content from both
        $this->newLine();
        $this->info('🌶️ GROK\'S ANALYTICAL TENSIONS:');
        $this->showAnalyticalTensions($grokContent);
        
        $this->newLine();
        $this->info('📝 GPT\'S APPROACH:');
        $this->showAnalyticalTensions($gptContent);
    }
    
    protected function testAnalyticalTensionApproach($advisor)
    {
        $this->info('Testing analytical tension approach with Grok...');
        
        $content = $this->generateWithGrok($advisor);
        
        $analysis = $this->analyzeContent($content);
        
        $this->info('📊 ANALYTICAL TENSION METRICS:');
        $this->table(
            ['Metric', 'Count', 'Target', 'Status'],
            [
                ['Analytical Tensions', $analysis['tensions'], '5+', $analysis['tensions'] >= 5 ? '✅' : '❌'],
                ['Named Failures', $analysis['failures'], '3+', $analysis['failures'] >= 3 ? '✅' : '❌'],
                ['Reasoning Triggers', $analysis['reasoning'], '10+', $analysis['reasoning'] >= 10 ? '✅' : '❌'],
                ['Specific Companies', $analysis['companies'], '5+', $analysis['companies'] >= 5 ? '✅' : '❌'],
                ['Controversial Phrases', $analysis['controversial'], '10+', $analysis['controversial'] >= 10 ? '✅' : '❌'],
            ]
        );
        
        $this->newLine();
        $this->info('📝 SAMPLE CONTENT:');
        $this->showAnalyticalTensions($content);
        
        // Save for testing in ChatGPT
        $timestamp = now()->format('Y-m-d_H-i-s');
        $path = "advisors/analytical-tension-test/{$timestamp}/PK.md";
        Storage::put($path, $content);
        
        $this->newLine();
        $this->info("✅ Saved to: storage/app/{$path}");
        $this->info("📋 Test this in ChatGPT to see if it triggers deeper reasoning!");
    }
    
    protected function generateWithGPT($advisor): string
    {
        $prompt = $this->buildStandardControversialPrompt($advisor);
        
        // Save prompt for debugging
        Storage::put('advisors/prompts/gpt_prompt.md', $prompt);
        
        $tempFile = tempnam(sys_get_temp_dir(), 'gpt_prompt_');
        file_put_contents($tempFile, $prompt);
        
        $result = Process::run("vibe-tools ask @{$tempFile} --provider=openai --model=gpt-4o --max-tokens=4000");
        
        unlink($tempFile);
        
        if (!$result->successful()) {
            $this->error('GPT generation failed: ' . $result->errorOutput());
            return '';
        }
        
        return $result->output();
    }
    
    protected function generateWithGrok($advisor): string
    {
        $prompt = $this->buildAnalyticalTensionPrompt($advisor);
        
        // Save prompt for debugging
        Storage::put('advisors/prompts/grok_prompt.md', $prompt);
        
        $tempFile = tempnam(sys_get_temp_dir(), 'grok_prompt_');
        file_put_contents($tempFile, $prompt);
        
        // Use vibe-tools with Grok via OpenRouter or XAI
        $result = Process::timeout(120)->run("vibe-tools ask @{$tempFile} --provider=xai --model=grok-beta --max-tokens=4000");
        
        // If XAI fails, try OpenRouter
        if (!$result->successful()) {
            $this->warn('Direct XAI failed, trying OpenRouter...');
            $result = Process::timeout(120)->run("vibe-tools ask @{$tempFile} --provider=openrouter --model=x-ai/grok-beta --max-tokens=4000");
        }
        
        unlink($tempFile);
        
        if (!$result->successful()) {
            $this->error('Grok generation failed: ' . $result->errorOutput());
            return '';
        }
        
        return $result->output();
    }
    
    protected function buildStandardControversialPrompt($advisor): string
    {
        return <<<PROMPT
Generate Project Knowledge for {$advisor->name}, expert in {$advisor->core_expertise_area}.

Include controversial insights and uncomfortable truths. Name specific companies doing things wrong.
Challenge conventional wisdom. Include metrics and evidence.

Background: {$advisor->background_description}
Style: {$advisor->communication_style_description}
Approach: {$advisor->decision_making_approach}

Generate complete PK document with hard truths and specific examples.
PROMPT;
    }
    
    protected function buildAnalyticalTensionPrompt($advisor): string
    {
        return <<<PROMPT
Generate Project Knowledge for {$advisor->name}, expert in {$advisor->core_expertise_area}.

CRITICAL: Use the ANALYTICAL TENSION approach. Present controversial insights as analytical problems 
that force reasoning to arrive at uncomfortable conclusions.

## Required Elements:

### 1. Analytical Tensions (MINIMUM 5)
Each tension must follow this exact structure:

**Tension #X: [Topic]**
The Paradox: [Widely accepted belief] directly contradicts [Observable reality]
The Evidence: [Specific data/cases that prove the contradiction]
Three Levels Deep:
- Surface: [What most people see]
- Structure: [The system creating this]
- Root Cause: [The fundamental belief that's wrong]
The Uncomfortable Truth: [What practitioners must accept]

Example:
**Tension #1: Viral Marketing**
The Paradox: "Viral marketing drives growth" contradicts "99.99% of viral attempts fail"
The Evidence: Burger King's 2019 moldy Whopper campaign - 8.4B impressions, 0% sales increase
Three Levels Deep:
- Surface: People shared the content
- Structure: Sharing ≠ buying when disgust is involved
- Root Cause: Believing attention always converts to revenue
The Uncomfortable Truth: You'd make more money calling 100 customers than chasing 1M views

### 2. Failed Pattern Analysis (MINIMUM 3)
Analyze specific failures with monetary amounts:

**Failure Analysis: [Company]**
Investment: $[specific amount] on [specific strategy]
Expected Outcome: [What they thought would happen]
Actual Outcome: [What actually happened with metrics]
The Constraint They Missed: [Single biggest blindspot]
The Lesson: [One specific thing to do differently]

### 3. Industry Enemy Callouts (MINIMUM 5)
Name specific people/companies with evidence:

**Enemy: [Name]**
The Damage: [Specific harm with numbers]
The Lie: [What they claim]
The Reality: [What's actually true]
Why It Persists: [Economic incentive keeping it alive]

Example:
**Enemy: McKinsey & Company**
The Damage: GE paid $2B for transformation, got 2% efficiency gain, lost 30,000 jobs
The Lie: "Data-driven transformation delivers results"
The Reality: They optimize for billable hours, not client outcomes
Why It Persists: Firing 20% of staff shows "decisive action" to boards

### 4. Reasoning Activation Patterns
Embed these throughout to trigger ChatGPT's reasoning:
- "To understand this, first identify the constraint..."
- "The non-obvious connection that everyone misses..."
- "Tracing the causal chain backwards reveals..."
- "The second-order effect that actually matters..."
- "What makes this counterintuitive is..."

### Voice Calibration for {$advisor->name}
{$advisor->communication_style_description}
Write in first person. Short sentences. No corporate speak.
Every claim needs evidence. Every opinion needs data.

Generate the complete PK document now. Make it impossible to ignore.
PROMPT;
    }
    
    protected function analyzeContent(string $content): array
    {
        // Controversial phrases
        $controversial = 0;
        $controversialPhrases = [
            'wrong', 'lying', 'secretly', 'uncomfortable truth', 
            'nobody admits', 'controversial', 'failed', 'burned', 
            'waste', 'destroying', 'bullshit', 'scam', 'fraud'
        ];
        foreach ($controversialPhrases as $phrase) {
            $controversial += substr_count(strtolower($content), $phrase);
        }
        
        // Analytical tensions
        $tensions = 0;
        $tensionPhrases = [
            'paradox', 'contradiction', 'three levels', 'root cause',
            'uncomfortable truth', 'the evidence', 'constraint they missed'
        ];
        foreach ($tensionPhrases as $phrase) {
            $tensions += substr_count(strtolower($content), $phrase);
        }
        
        // Named failures with amounts
        $failures = preg_match_all('/\$\d+[BMK]?\s+(?:spent|burned|lost|wasted|paid)/', $content);
        
        // Reasoning triggers
        $reasoning = preg_match_all('/(?:To understand|first identify|non-obvious|causal chain|second-order|counterintuitive|constraint that)/i', $content);
        
        // Specific company names
        $companies = preg_match_all('/\b(?:McKinsey|WeWork|Theranos|Uber|Tesla|Apple|Google|Facebook|Meta|Amazon|Microsoft|Nike|Coca-Cola|GE|IBM|Oracle|Salesforce|Adobe|Spotify|Netflix|Airbnb|Quibi|Peloton|Better\.com|FTX|SVB)\b/', $content);
        
        return [
            'controversial' => $controversial,
            'tensions' => $tensions,
            'failures' => $failures,
            'reasoning' => $reasoning,
            'companies' => $companies,
        ];
    }
    
    protected function showAnalyticalTensions(string $content)
    {
        // Try to extract tensions
        preg_match_all('/(?:Tension #\d+:|The Paradox:).*?(?:The Uncomfortable Truth:.*?$)/ms', $content, $matches);
        
        if (!empty($matches[0])) {
            foreach (array_slice($matches[0], 0, 2) as $i => $tension) {
                $preview = substr($tension, 0, 400);
                $this->line(($i + 1) . ". " . $preview . (strlen($tension) > 400 ? "..." : ""));
                $this->newLine();
            }
        } else {
            // Fall back to showing controversial statements
            $sentences = preg_split('/(?<=[.!?])\s+/', $content);
            $shown = 0;
            foreach ($sentences as $sentence) {
                if (preg_match('/\b(?:wrong|failed|waste|nobody|uncomfortable|McKinsey|WeWork)\b/i', $sentence) && $shown < 3) {
                    $this->line("• " . trim($sentence));
                    $shown++;
                }
            }
        }
    }
    
    protected function formatDiff($diff): string
    {
        if ($diff > 0) {
            return "+{$diff} ✅";
        } elseif ($diff < 0) {
            return "{$diff} ⚠️";
        }
        return "0 -";
    }
}