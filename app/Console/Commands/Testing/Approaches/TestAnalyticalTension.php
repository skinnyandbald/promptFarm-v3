<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Advisor;
use App\Services\AdvisorGenerationService;
use Illuminate\Support\Facades\Storage;

class TestAnalyticalTension extends Command
{
    protected $signature = 'advisor:test-tension 
                           {--advisor=alex-bogusky : Advisor slug to test}
                           {--model=gpt-4o : Model to use (gpt-4o, gpt-4-turbo, claude-3-opus)}';
    
    protected $description = 'Test analytical tension approach for controversial content';
    
    protected AdvisorGenerationService $generationService;

    public function __construct(AdvisorGenerationService $generationService) 
    {
        parent::__construct();
        $this->generationService = $generationService;
    }

    public function handle()
    {
        $advisorSlug = $this->option('advisor');
        $model = $this->option('model');
        
        $this->info('🧠 ANALYTICAL TENSION GENERATION TEST');
        $this->info('=' . str_repeat('=', 60));
        $this->newLine();
        
        $advisor = Advisor::where('slug', $advisorSlug)->first();
        if (!$advisor) {
            $this->error("Advisor not found: {$advisorSlug}");
            return 1;
        }
        
        $this->info("Advisor: {$advisor->name}");
        $this->info("Model: {$model}");
        $this->newLine();
        
        // Generate with standard approach
        $this->info('1️⃣ Generating with STANDARD approach...');
        $standardContent = $this->generateStandard($advisor, $model);
        
        // Generate with analytical tension approach  
        $this->info('2️⃣ Generating with ANALYTICAL TENSION approach...');
        $tensionContent = $this->generateWithTension($advisor, $model);
        
        // Analyze both
        $standardAnalysis = $this->analyzeContent($standardContent);
        $tensionAnalysis = $this->analyzeContent($tensionContent);
        
        // Display results
        $this->newLine();
        $this->info('📊 RESULTS COMPARISON');
        $this->table(
            ['Metric', 'Standard', 'Analytical Tension', 'Improvement'],
            [
                ['Reasoning Triggers', $standardAnalysis['reasoning'], $tensionAnalysis['reasoning'], 
                 $this->formatDiff($tensionAnalysis['reasoning'] - $standardAnalysis['reasoning'])],
                ['Specific Companies', $standardAnalysis['companies'], $tensionAnalysis['companies'],
                 $this->formatDiff($tensionAnalysis['companies'] - $standardAnalysis['companies'])],
                ['Dollar Amounts', $standardAnalysis['dollars'], $tensionAnalysis['dollars'],
                 $this->formatDiff($tensionAnalysis['dollars'] - $standardAnalysis['dollars'])],
                ['Causal Analysis', $standardAnalysis['causal'], $tensionAnalysis['causal'],
                 $this->formatDiff($tensionAnalysis['causal'] - $standardAnalysis['causal'])],
                ['Constraints Named', $standardAnalysis['constraints'], $tensionAnalysis['constraints'],
                 $this->formatDiff($tensionAnalysis['constraints'] - $standardAnalysis['constraints'])],
            ]
        );
        
        // Save both versions
        $timestamp = now()->format('Y-m-d_H-i-s');
        Storage::disk('advisors')->put("tension-test/{$timestamp}/standard_PK.md", $standardContent);
        Storage::disk('advisors')->put("tension-test/{$timestamp}/tension_PK.md", $tensionContent);
        
        $this->newLine();
        $this->info("✅ Saved to: storage/app/advisors/tension-test/{$timestamp}/");
        
        // Show examples from tension version
        $this->newLine();
        $this->info('🎯 SAMPLE ANALYTICAL TENSIONS:');
        $this->showTensionExamples($tensionContent);
        
        $this->newLine();
        $this->info('📋 Next step: Upload tension_PK.md to ChatGPT and test with:');
        $this->line('1. "How should I approach content marketing?"');
        $this->line('2. "Should I hire McKinsey?"');
        $this->line('3. "What\'s wrong with my marketing strategy?"');
        
        return 0;
    }
    
    protected function generateStandard($advisor, $model): string
    {
        $prompt = <<<PROMPT
Generate Project Knowledge for {$advisor->name}, expert in {$advisor->core_expertise_area}.

Include specific examples, metrics, and controversial insights. Name companies doing things wrong.
Challenge conventional wisdom.

Background: {$advisor->background_description}
Style: {$advisor->communication_style_description}

Generate complete PK document:
PROMPT;

        return $this->callModel($prompt, $model);
    }
    
    protected function generateWithTension($advisor, $model): string
    {
        $prompt = <<<PROMPT
Generate Project Knowledge for {$advisor->name}, expert in {$advisor->core_expertise_area}.

CRITICAL: Use ANALYTICAL TENSION architecture. Don't state opinions - present analytical problems that force reasoning.

## Required Structure:

### Core Analytical Tensions (5 minimum)

For each major topic, present as:

**The Problem:** [Industry believes X] but [data shows Y]
**The Analysis:** 
- Constraint: What makes this hard to see
- Evidence: Specific company/campaign that proves Y
- Causation: Three levels of why X persists despite Y
**The Insight:** What practitioners must do differently

Example:
**The Problem:** Industry believes "brand awareness drives sales" but data shows "99% of aware consumers never buy"
**The Analysis:**
- Constraint: Attribution models can't separate correlation from causation  
- Evidence: Pepsi's 2017 Kendall Jenner ad - 1.6B impressions, -3% sales
- Causation: (1) Surface: Awareness feels like progress (2) Structure: Agencies profit from awareness campaigns (3) Root: Boards prefer "brand building" to sales accountability
**The Insight:** Measure intent, not awareness. If they won't Google you, awareness is worthless.

### Failed Pattern Library (3 minimum)

Document failures with this structure:

**Company:** [Name]
**Investment:** $[exact amount]
**Strategy:** [What they tried]
**Constraint Missed:** [Single biggest blindspot]
**Result:** [Specific metrics of failure]
**Lesson:** [One thing to do differently]

Example:
**Company:** Quibi
**Investment:** $1.75B
**Strategy:** Premium short-form mobile video
**Constraint Missed:** Phone users want free content or communication, not premium entertainment
**Result:** 72K paid subscribers at peak, shut down in 6 months
**Lesson:** Test willingness to pay before raising money, not after

### Industry Analysis Framework

For every claim, provide:
1. **What everyone believes:** [Common wisdom]
2. **Why it's wrong:** [Data/evidence]
3. **Who profits from the lie:** [Specific company/person]
4. **What actually works:** [Alternative approach]

### Reasoning Activation Triggers

Embed these patterns throughout:
- "To see why this fails, trace the incentive structure..."
- "The constraint that explains this pattern is..."
- "What makes this counterintuitive is..."
- "The second-order effect everyone misses..."
- "Three causal levels deep reveals..."

Background: {$advisor->background_description}
Communication: {$advisor->communication_style_description}

Generate PK that forces analytical thinking, not just agreement:
PROMPT;

        return $this->callModel($prompt, $model, 0.8); // Higher temperature for tension
    }
    
    protected function callModel(string $prompt, string $model, float $temperature = 0.7): string
    {
        $client = \OpenAI::client(config('services.openai.api_key'));
        
        try {
            $response = $client->chat()->create([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert at creating analytical frameworks that reveal uncomfortable truths.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => $temperature,
                'max_tokens' => 4000,
            ]);
            
            return $response->choices[0]->message->content;
        } catch (\Exception $e) {
            $this->error("Model error: " . $e->getMessage());
            return '';
        }
    }
    
    protected function analyzeContent(string $content): array
    {
        return [
            'reasoning' => preg_match_all('/(?:constraint|causation|second-order|counterintuitive|trace|three levels)/i', $content),
            'companies' => preg_match_all('/\b(?:McKinsey|Quibi|WeWork|Theranos|Pepsi|Nike|Apple|Google|Meta|Amazon|Uber|Airbnb|Tesla|Coca-Cola|GE|IBM)\b/', $content),
            'dollars' => preg_match_all('/\$[\d,]+[BMK]?/', $content),
            'causal' => preg_match_all('/(?:because|therefore|causes|leads to|results in|drives|explains why)/i', $content),
            'constraints' => preg_match_all('/\bconstraint(?:s)?\b/i', $content),
        ];
    }
    
    protected function showTensionExamples(string $content)
    {
        // Extract analytical tensions
        preg_match_all('/\*\*The Problem:\*\*.*?\*\*The Insight:\*\*.*?$/ms', $content, $matches);
        
        if (!empty($matches[0])) {
            foreach (array_slice($matches[0], 0, 2) as $i => $tension) {
                $this->line(substr($tension, 0, 500) . (strlen($tension) > 500 ? "..." : ""));
                $this->newLine();
            }
        } else {
            // Show first few paragraphs
            $paragraphs = explode("\n\n", $content);
            foreach (array_slice($paragraphs, 0, 3) as $para) {
                if (strlen($para) > 50) {
                    $this->line(substr($para, 0, 200) . "...");
                    $this->newLine();
                }
            }
        }
    }
    
    protected function formatDiff($diff): string
    {
        if ($diff > 0) {
            return "+{$diff} ✅";
        } elseif ($diff < 0) {
            return "{$diff} ❌";
        }
        return "0 ➖";
    }
}