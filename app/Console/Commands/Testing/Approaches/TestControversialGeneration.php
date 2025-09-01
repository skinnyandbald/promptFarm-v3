<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AdvisorGenerationService;
use App\Services\Validation\AdvisorQualityService;
use App\Models\Advisor;
use Illuminate\Support\Facades\Storage;

class TestControversialGeneration extends Command
{
    protected $signature = 'advisor:test-controversial 
                           {--advisor=alex-bogusky : Advisor slug to test}
                           {--compare : Generate both safe and controversial versions}';
    
    protected $description = 'Test controversial content generation vs safe generation';

    protected AdvisorGenerationService $generationService;
    protected AdvisorQualityService $qualityService;

    public function __construct(
        AdvisorGenerationService $generationService,
        AdvisorQualityService $qualityService
    ) {
        parent::__construct();
        $this->generationService = $generationService;
        $this->qualityService = $qualityService;
    }

    public function handle()
    {
        $advisorSlug = $this->option('advisor');
        $compare = $this->option('compare');
        
        $this->info('🔥 CONTROVERSIAL GENERATION TEST');
        $this->info('=' . str_repeat('=', 50));
        $this->newLine();
        
        // Get advisor by slug
        $advisor = Advisor::where('slug', $advisorSlug)->first();
        
        if (!$advisor) {
            $this->error("Advisor not found: {$advisorSlug}");
            $this->info("Available advisors:");
            Advisor::select('slug', 'name')->get()->each(function ($a) {
                $this->line("  {$a->slug} - {$a->name}");
            });
            return 1;
        }
        
        $this->info("Testing with: {$advisor->name}");
        $this->newLine();
        
        if ($compare) {
            $this->compareVersions($advisor);
        } else {
            $this->generateControversial($advisor);
        }
        
        return 0;
    }
    
    protected function compareVersions($advisor)
    {
        $this->info('Generating SAFE version (old prompt)...');
        $safeContent = $this->generateWithOldPrompt($advisor);
        
        $this->info('Generating CONTROVERSIAL version (new prompt)...');
        $controversialContent = $this->generateWithNewPrompt($advisor);
        
        $this->newLine();
        $this->info('📊 COMPARISON RESULTS');
        $this->info('=' . str_repeat('=', 50));
        
        // Analyze both versions
        $safeAnalysis = $this->analyzeContent($safeContent, 'SAFE');
        $controversialAnalysis = $this->analyzeContent($controversialContent, 'CONTROVERSIAL');
        
        // Display comparison
        $this->table(
            ['Metric', 'Safe Version', 'Controversial Version', 'Difference'],
            [
                ['Quality Score', $safeAnalysis['quality'] . '%', $controversialAnalysis['quality'] . '%', 
                 ($controversialAnalysis['quality'] - $safeAnalysis['quality']) . '%'],
                ['Company Names', $safeAnalysis['companies'], $controversialAnalysis['companies'],
                 ($controversialAnalysis['companies'] - $safeAnalysis['companies'])],
                ['Controversial Phrases', $safeAnalysis['controversial'], $controversialAnalysis['controversial'],
                 ($controversialAnalysis['controversial'] - $safeAnalysis['controversial'])],
                ['Generic Phrases', $safeAnalysis['generic'], $controversialAnalysis['generic'],
                 ($controversialAnalysis['generic'] - $safeAnalysis['generic'])],
                ['Specific Metrics', $safeAnalysis['metrics'], $controversialAnalysis['metrics'],
                 ($controversialAnalysis['metrics'] - $safeAnalysis['metrics'])],
                ['Memorability Score', $safeAnalysis['memorable'] . '%', $controversialAnalysis['memorable'] . '%',
                 ($controversialAnalysis['memorable'] - $safeAnalysis['memorable']) . '%'],
            ]
        );
        
        // Save both versions
        $timestamp = now()->format('Y-m-d_H-i-s');
        Storage::put("advisors/comparison/{$timestamp}/safe_PK.md", $safeContent);
        Storage::put("advisors/comparison/{$timestamp}/controversial_PK.md", $controversialContent);
        Storage::put("advisors/comparison/{$timestamp}/analysis.json", json_encode([
            'safe' => $safeAnalysis,
            'controversial' => $controversialAnalysis,
            'improvement' => [
                'quality' => $controversialAnalysis['quality'] - $safeAnalysis['quality'],
                'controversy' => $controversialAnalysis['controversial'] - $safeAnalysis['controversial'],
                'memorability' => $controversialAnalysis['memorable'] - $safeAnalysis['memorable'],
            ]
        ], JSON_PRETTY_PRINT));
        
        $this->newLine();
        $this->info("✅ Saved comparison to: storage/app/advisors/comparison/{$timestamp}/");
        
        // Show sample controversial content
        $this->newLine();
        $this->info('🌶️ SAMPLE CONTROVERSIAL CONTENT:');
        $this->info('=' . str_repeat('=', 50));
        
        // Extract and show controversial snippets
        $this->showControversialSnippets($controversialContent);
    }
    
    protected function generateWithOldPrompt($advisor): string
    {
        // Temporarily use old safe prompt
        $prompt = $this->buildSafePrompt($advisor);
        $response = $this->callOpenAI($prompt);
        return $response;
    }
    
    protected function generateWithNewPrompt($advisor): string
    {
        // Use the updated controversial prompt
        $result = $this->generationService->generateAdvisor($advisor);
        
        // Return the PK content directly from result
        return $result['pk_content'] ?? '';
    }
    
    protected function buildSafePrompt($advisor): string
    {
        return "Generate professional Project Knowledge for {$advisor->name}, 
                an expert in {$advisor->core_expertise_area}.
                Include specific examples and measurable outcomes.
                Maintain a professional and authoritative tone.";
    }
    
    protected function analyzeContent(string $content, string $type): array
    {
        // Count company names
        $companies = preg_match_all('/\b(Apple|Google|Facebook|Amazon|Microsoft|Tesla|Nike|McDonald|Uber|WeWork|Quibi|McKinsey|Domino|Coca-Cola|Netflix|Spotify|Twitter|LinkedIn|TikTok|Shopify|Stripe|Square|PayPal|Airbnb|Dropbox|Slack|Zoom|Disney|Warner|HBO|CNN|Fox|NBC|CBS|ABC|New York Times|Wall Street Journal|Washington Post)\b/i', $content);
        
        // Count controversial phrases
        $controversialPhrases = [
            'wrong about', 'lying about', 'secretly', 'dirty secret', 
            'nobody admits', 'unpopular opinion', 'controversial', 
            'get fired', 'pissed off', 'bullshit', 'destroying', 
            'killing', 'dead', 'failure', 'burned', 'waste'
        ];
        
        $controversial = 0;
        foreach ($controversialPhrases as $phrase) {
            $controversial += substr_count(strtolower($content), $phrase);
        }
        
        // Count generic phrases (bad)
        $genericPhrases = [
            'best practices', 'drive growth', 'unlock potential',
            'leverage synergies', 'innovative solutions', 'transform',
            'empower', 'optimize', 'streamline', 'enhance'
        ];
        
        $generic = 0;
        foreach ($genericPhrases as $phrase) {
            $generic += substr_count(strtolower($content), $phrase);
        }
        
        // Count specific metrics
        $metrics = preg_match_all('/\d+%|\$[\d,]+[MBK]?|\d+x|\d+ (days|months|years|hours)/', $content);
        
        // Calculate quality score
        $qualityResult = $this->qualityService->scorePK($content);
        $quality = $qualityResult['percentage'] ?? 0;
        
        // Calculate memorability score (custom metric)
        $memorable = min(100, 
            ($companies * 5) + 
            ($controversial * 10) - 
            ($generic * 15) + 
            ($metrics * 3)
        );
        
        return [
            'quality' => $quality,
            'companies' => $companies,
            'controversial' => $controversial,
            'generic' => $generic,
            'metrics' => $metrics,
            'memorable' => max(0, $memorable),
            'content_length' => strlen($content),
        ];
    }
    
    protected function showControversialSnippets(string $content)
    {
        // Find and display the most controversial sentences
        $sentences = preg_split('/(?<=[.!?])\s+/', $content);
        
        $controversialSentences = [];
        $controversialPhrases = [
            'wrong', 'lying', 'secretly', 'dirty secret', 
            'nobody admits', 'unpopular', 'controversial', 
            'get fired', 'pissed', 'bullshit', 'destroying', 
            'killing', 'dead', 'failure', 'burned', 'waste',
            'McKinsey', 'WeWork', 'Quibi', 'Theranos'
        ];
        
        foreach ($sentences as $sentence) {
            $score = 0;
            foreach ($controversialPhrases as $phrase) {
                if (stripos($sentence, $phrase) !== false) {
                    $score += 10;
                }
            }
            if ($score > 0) {
                $controversialSentences[] = ['sentence' => $sentence, 'score' => $score];
            }
        }
        
        // Sort by controversy score
        usort($controversialSentences, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        // Show top 5
        $top = array_slice($controversialSentences, 0, 5);
        foreach ($top as $i => $item) {
            $this->line(($i + 1) . ". " . trim($item['sentence']));
            $this->newLine();
        }
    }
    
    protected function generateControversial($advisor)
    {
        $this->info('Generating with controversial requirements...');
        
        $result = $this->generationService->generateAdvisor($advisor);
        
        if ($result['success']) {
            $this->info('✅ Generation successful!');
            $this->newLine();
            
            $pkContent = $result['pk_content'];
            $analysis = $this->analyzeContent($pkContent, 'CONTROVERSIAL');
            
            $this->info('📊 CONTENT ANALYSIS:');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Quality Score', $analysis['quality'] . '%'],
                    ['Company Names', $analysis['companies']],
                    ['Controversial Phrases', $analysis['controversial']],
                    ['Generic Phrases (lower is better)', $analysis['generic']],
                    ['Specific Metrics', $analysis['metrics']],
                    ['Memorability Score', $analysis['memorable'] . '%'],
                ]
            );
            
            $this->newLine();
            $this->info('🌶️ MOST CONTROVERSIAL CONTENT:');
            $this->info('=' . str_repeat('=', 50));
            $this->showControversialSnippets($pkContent);
            
            $this->newLine();
            $this->info("✅ Saved to: advisors/{$result['files']['base_path']}");
        } else {
            $this->error('Generation failed');
        }
    }
    
    protected function callOpenAI(string $prompt): string
    {
        $client = \OpenAI::client(config('services.openai.api_key'));
        
        $response = $client->chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'You are an expert advisor.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 4000,
        ]);
        
        return $response->choices[0]->message->content;
    }
}