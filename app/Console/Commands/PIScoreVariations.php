<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Services\Validation\AIEmbodimentQualityScorer;

class PIScoreVariations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pi:score-variations {--batch=latest : Batch timestamp or "latest"} 
                            {--update-results-with-scores : Update results metadata with scores}
                            {--generate-recommendations : Generate actionable recommendations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Score PI variation responses using AI Embodiment Quality Scorer and generate recommendations';

    protected AIEmbodimentQualityScorer $scorer;
    
    public function __construct(AIEmbodimentQualityScorer $scorer)
    {
        parent::__construct();
        $this->scorer = $scorer;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $batch = $this->option('batch');
        $updateResults = $this->option('update-results-with-scores');
        $generateRecommendations = $this->option('generate-recommendations');
        
        $this->info("🎯 Scoring PI variation results...");
        
        // Find batch directory
        $batchPath = $this->findBatchPath($batch);
        if (!$batchPath) {
            $this->error("Batch not found: {$batch}");
            return Command::FAILURE;
        }
        
        $this->info("📁 Using batch: " . basename($batchPath));
        
        // Load experiment metadata
        $metadataPath = "{$batchPath}/experiment-metadata.json";
        if (!File::exists($metadataPath)) {
            $this->error("Experiment metadata not found at: {$metadataPath}");
            return Command::FAILURE;
        }
        
        $metadata = json_decode(File::get($metadataPath), true);
        $this->info("📊 Found " . count($metadata['results']) . " results to score");
        
        // Score each response
        $scoredResults = [];
        $totalScored = 0;
        
        foreach ($metadata['results'] as $result) {
            if (isset($result['error'])) {
                $scoredResults[] = $result; // Keep error results as-is
                continue;
            }
            
            try {
                $this->info("🔄 Scoring {$result['variation']} prompt-" . ($result['prompt_index'] + 1));
                
                $score = $this->scorer->scoreAIEmbodiment($result['response'], [
                    'name' => 'Alex Bogusky',
                    'expertise_area' => 'Creative Advertising'
                ]);
                
                $result['embodiment_score'] = $score;
                $scoredResults[] = $result;
                $totalScored++;
                
                $this->info("✅ Score: {$score['total_score']}/100 (" . ($score['valid'] ? 'Valid' : 'Invalid') . ")");
                
            } catch (\Exception $e) {
                $this->error("❌ Scoring failed: " . $e->getMessage());
                $result['scoring_error'] = $e->getMessage();
                $scoredResults[] = $result;
            }
        }
        
        // Update metadata with scores
        if ($updateResults) {
            $metadata['results'] = $scoredResults;
            $metadata['scoring_completed'] = now()->toISOString();
            $metadata['total_scored'] = $totalScored;
            
            File::put($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));
            $this->info("💾 Updated experiment metadata with scores");
        }
        
        // Generate analysis and recommendations
        if ($generateRecommendations) {
            $this->generateAnalysisReport($batchPath, $scoredResults, $metadata);
        }
        
        // Display summary
        $this->displaySummary($scoredResults);
        
        $this->info("🎉 PI variation scoring complete!");
        $this->info("📂 Results in: {$batchPath}");
        
        return Command::SUCCESS;
    }
    
    protected function findBatchPath(string $batch): ?string
    {
        $resultsPath = storage_path('app/testing/results');
        
        if ($batch === 'latest') {
            $batches = collect(File::directories($resultsPath))
                ->sortByDesc(fn($dir) => basename($dir));
            return $batches->first();
        }
        
        $batchPath = "{$resultsPath}/{$batch}";
        return File::isDirectory($batchPath) ? $batchPath : null;
    }
    
    protected function generateAnalysisReport(string $batchPath, array $scoredResults, array $metadata): void
    {
        $this->info("📝 Generating analysis report...");
        
        $analysis = $this->analyzeResults($scoredResults);
        $recommendations = $this->generateRecommendations($analysis);
        
        $report = $this->buildReport($metadata, $analysis, $recommendations);
        
        File::put("{$batchPath}/analysis-report.md", $report);
        $this->info("📄 Analysis report saved: analysis-report.md");
    }
    
    protected function analyzeResults(array $scoredResults): array
    {
        $analysis = [
            'by_variation' => [],
            'overall_insights' => []
        ];
        
        // Group by variation
        $byVariation = collect($scoredResults)
            ->filter(fn($r) => isset($r['embodiment_score']))
            ->groupBy('variation');
            
        foreach ($byVariation as $variation => $results) {
            $scores = $results->pluck('embodiment_score.total_score');
            $avgScore = $scores->avg();
            $avgLength = $results->pluck('response_length')->avg();
            
            $analysis['by_variation'][$variation] = [
                'avg_score' => round($avgScore, 1),
                'avg_length' => round($avgLength),
                'responses_count' => $results->count(),
                'valid_responses' => $results->where('embodiment_score.valid', true)->count(),
                'scores' => $scores->toArray(),
            ];
        }
        
        // Overall insights
        $allScores = collect($scoredResults)
            ->filter(fn($r) => isset($r['embodiment_score']))
            ->pluck('embodiment_score.total_score');
            
        $analysis['overall_insights'] = [
            'total_responses' => $allScores->count(),
            'avg_score' => round($allScores->avg(), 1),
            'max_score' => $allScores->max(),
            'min_score' => $allScores->min(),
            'score_range' => $allScores->max() - $allScores->min(),
        ];
        
        return $analysis;
    }
    
    protected function generateRecommendations(array $analysis): array
    {
        $recommendations = [];
        
        // Find best performing variation
        $bestVariation = collect($analysis['by_variation'])
            ->sortByDesc('avg_score')
            ->first();
            
        $bestVariationName = collect($analysis['by_variation'])
            ->sortByDesc('avg_score')
            ->keys()
            ->first();
        
        if ($bestVariation) {
            $recommendations[] = "🏆 **Winner**: {$bestVariationName} with {$bestVariation['avg_score']}/100 average score";
        }
        
        // Dramatic improvement opportunities
        $scoreRange = $analysis['overall_insights']['score_range'];
        if ($scoreRange > 20) {
            $recommendations[] = "🚀 **High Impact Opportunity**: {$scoreRange} point spread between variations suggests significant optimization potential";
        } else {
            $recommendations[] = "📈 **Incremental Gains**: {$scoreRange} point spread indicates variations are similar - consider more dramatic changes";
        }
        
        // Specific variation insights
        foreach ($analysis['by_variation'] as $variation => $data) {
            if ($data['avg_score'] < 75) {
                $recommendations[] = "⚠️ **{$variation}**: Below production threshold (75+). Score: {$data['avg_score']}/100";
            } elseif ($data['avg_score'] > 85) {
                $recommendations[] = "✨ **{$variation}**: Excellent performance. Score: {$data['avg_score']}/100 - consider this approach";
            }
        }
        
        return $recommendations;
    }
    
    protected function buildReport(array $metadata, array $analysis, array $recommendations): string
    {
        $timestamp = $metadata['timestamp'];
        $advisor = $metadata['advisor'];
        
        $report = "# PI A/B/C Testing Analysis Report\n\n";
        $report .= "**Generated**: " . now()->format('Y-m-d H:i:s') . "\n";
        $report .= "**Batch**: {$timestamp}\n";
        $report .= "**Advisor**: {$advisor}\n";
        $report .= "**Variations Tested**: " . implode(', ', $metadata['variations_tested']) . "\n\n";
        
        $report .= "## Executive Summary\n\n";
        $report .= "Tested " . $analysis['overall_insights']['total_responses'] . " responses ";
        $report .= "with scores ranging from {$analysis['overall_insights']['min_score']} to {$analysis['overall_insights']['max_score']} ";
        $report .= "(avg: {$analysis['overall_insights']['avg_score']}/100).\n\n";
        
        $report .= "## Results by Variation\n\n";
        foreach ($analysis['by_variation'] as $variation => $data) {
            $report .= "### " . ucwords(str_replace('-', ' ', $variation)) . "\n";
            $report .= "- **Average Score**: {$data['avg_score']}/100\n";
            $report .= "- **Average Length**: {$data['avg_length']} characters\n";
            $report .= "- **Valid Responses**: {$data['valid_responses']}/{$data['responses_count']}\n";
            $report .= "- **Individual Scores**: " . implode(', ', $data['scores']) . "\n\n";
        }
        
        $report .= "## Recommendations\n\n";
        foreach ($recommendations as $rec) {
            $report .= "- {$rec}\n";
        }
        
        $report .= "\n## Next Steps\n\n";
        $report .= "1. **Implement Winner**: Apply the best-performing variation to production\n";
        $report .= "2. **A/B Test**: Run user satisfaction tests with top 2 variations\n";
        $report .= "3. **Iterate**: Use insights to create new test variations\n";
        $report .= "4. **Scale**: Apply learnings to other advisors\n\n";
        
        $report .= "---\n*Report generated by PI A/B/C Testing Framework*\n";
        
        return $report;
    }
    
    protected function displaySummary(array $scoredResults): void
    {
        $this->info("\n📊 SCORING SUMMARY");
        $this->info("==================");
        
        $byVariation = collect($scoredResults)
            ->filter(fn($r) => isset($r['embodiment_score']))
            ->groupBy('variation');
            
        foreach ($byVariation as $variation => $results) {
            $avgScore = $results->pluck('embodiment_score.total_score')->avg();
            $count = $results->count();
            $this->info(sprintf("%-15s: %.1f/100 (%d responses)", $variation, $avgScore, $count));
        }
        
        $this->info("");
    }
}
