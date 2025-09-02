<?php

namespace App\Services;

use App\Models\Advisor;
use App\Services\Validation\AdvisorQualityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Simple Quality Measurement Framework for External Deployment
 * 
 * Provides lightweight quality measurement that works with external ChatGPT usage.
 * Focuses on generation-time quality and periodic sampling rather than session tracking.
 */
class SimpleQualityService
{
    public function __construct(
        protected AdvisorQualityService $qualityService
    ) {}

    /**
     * Score advisor quality at generation time
     */
    public function scoreGeneratedAdvisor(string $piContent, string $pkContent): array
    {
        Log::info('Scoring generated advisor quality');
        
        // Get individual scores
        $piScore = $this->qualityService->scorePI($piContent);
        $pkScore = $this->qualityService->scorePK($pkContent);
        
        // Calculate combined metrics
        $overallScore = $this->calculateOverallScore($piScore, $pkScore);
        
        // Store quality metrics for tracking
        $this->storeQualityMetrics($overallScore);
        
        // Check quality thresholds and generate alerts if needed
        $this->checkQualityThresholds($overallScore);
        
        return $overallScore;
    }

    /**
     * Calculate overall quality score from PI and PK scores
     */
    protected function calculateOverallScore(array $piScore, array $pkScore): array
    {
        // Weight PK slightly higher based on research findings
        $piWeight = 0.4;
        $pkWeight = 0.6;
        
        $weightedScore = ($piScore['percentage'] * $piWeight) + ($pkScore['percentage'] * $pkWeight);
        
        return [
            'overall_score' => round($weightedScore, 2),
            'pi_score' => $piScore['percentage'],
            'pk_score' => $pkScore['percentage'],
            'pi_valid' => $piScore['valid'],
            'pk_valid' => $pkScore['valid'],
            'pi_issues' => $piScore['issues'] ?? [],
            'pk_issues' => $pkScore['issues'] ?? [],
            'total_issues' => count($piScore['issues'] ?? []) + count($pkScore['issues'] ?? []),
            'timestamp' => now()->toIso8601String(),
            'stage' => $this->determineQualityStage($weightedScore)
        ];
    }

    /**
     * Determine quality stage based on score
     */
    protected function determineQualityStage(float $score): string
    {
        if ($score >= 85) {
            return 'excellent';
        } elseif ($score >= 70) {
            return 'good';
        } elseif ($score >= 60) {
            return 'acceptable';
        } else {
            return 'needs_improvement';
        }
    }

    /**
     * Store quality metrics for trend analysis
     */
    protected function storeQualityMetrics(array $metrics): void
    {
        try {
            DB::table('advisor_quality_metrics')->insert([
                'overall_score' => $metrics['overall_score'],
                'pi_score' => $metrics['pi_score'],
                'pk_score' => $metrics['pk_score'],
                'stage' => $metrics['stage'],
                'total_issues' => $metrics['total_issues'],
                'created_at' => now()
            ]);
            
            // Update cached average
            Cache::forget('quality_metrics_average');
            
        } catch (\Exception $e) {
            Log::warning('Could not store quality metrics', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check quality thresholds and generate alerts
     */
    protected function checkQualityThresholds(array $metrics): void
    {
        $thresholds = config('advisors.quality.thresholds', [
            'minimum_acceptable' => 60,
            'target' => 80,
            'alert_below' => 50
        ]);
        
        if ($metrics['overall_score'] < $thresholds['alert_below']) {
            $this->sendQualityAlert('critical', $metrics);
        } elseif ($metrics['overall_score'] < $thresholds['minimum_acceptable']) {
            $this->sendQualityAlert('warning', $metrics);
        }
        
        // Check for regression
        $this->checkForQualityRegression($metrics);
    }

    /**
     * Send quality alert
     */
    protected function sendQualityAlert(string $level, array $metrics): void
    {
        Log::channel('quality')->warning("Quality {$level} alert", [
            'level' => $level,
            'overall_score' => $metrics['overall_score'],
            'pi_score' => $metrics['pi_score'],
            'pk_score' => $metrics['pk_score'],
            'total_issues' => $metrics['total_issues']
        ]);
        
        // Store alert for dashboard
        Cache::put('latest_quality_alert', [
            'level' => $level,
            'metrics' => $metrics,
            'timestamp' => now()
        ], now()->addHours(24));
    }

    /**
     * Check for quality regression compared to recent average
     */
    protected function checkForQualityRegression(array $currentMetrics): void
    {
        $recentAverage = $this->getRecentAverageScore();
        
        if ($recentAverage && $currentMetrics['overall_score'] < ($recentAverage - 10)) {
            Log::warning('Quality regression detected', [
                'current_score' => $currentMetrics['overall_score'],
                'recent_average' => $recentAverage,
                'regression' => $recentAverage - $currentMetrics['overall_score']
            ]);
        }
    }

    /**
     * Perform periodic quality sampling
     */
    public function performPeriodicSampling(): array
    {
        Log::info('Starting periodic quality sampling');
        
        $results = [];
        $sampleAdvisors = $this->getSampleAdvisors();
        
        foreach ($sampleAdvisors as $advisor) {
            try {
                // Generate test advisor
                $testResult = $this->generateTestAdvisor($advisor);
                
                // Score the test
                $score = $this->scoreGeneratedAdvisor(
                    $testResult['pi_content'],
                    $testResult['pk_content']
                );
                
                $results[] = [
                    'advisor' => $advisor->name,
                    'score' => $score,
                    'timestamp' => now()
                ];
                
            } catch (\Exception $e) {
                Log::error('Periodic sampling failed for advisor', [
                    'advisor' => $advisor->name,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Store sampling results
        $this->storeSamplingResults($results);
        
        return $results;
    }

    /**
     * Get sample advisors for periodic testing
     */
    protected function getSampleAdvisors(): array
    {
        // Get a diverse sample of advisors
        return Advisor::query()
            ->whereIn('advisor_type', ['strategic', 'contrarian', 'analytical'])
            ->inRandomOrder()
            ->limit(3)
            ->get()
            ->all();
    }

    /**
     * Generate test advisor for sampling
     */
    protected function generateTestAdvisor(Advisor $advisor): array
    {
        // This would use the AdvisorGenerationService
        // Simplified for now
        return [
            'pi_content' => 'Test PI content for sampling',
            'pk_content' => 'Test PK content for sampling'
        ];
    }

    /**
     * Store periodic sampling results
     */
    protected function storeSamplingResults(array $results): void
    {
        Cache::put('latest_sampling_results', $results, now()->addDays(7));
        
        // Calculate and store average
        if (count($results) > 0) {
            $avgScore = collect($results)->avg('score.overall_score');
            Cache::put('sampling_average_score', $avgScore, now()->addDays(7));
        }
    }

    /**
     * Get quality metrics dashboard data
     */
    public function getDashboardMetrics(): array
    {
        return [
            'current_average' => $this->getCurrentAverageScore(),
            'recent_average' => $this->getRecentAverageScore(),
            'trend' => $this->getQualityTrend(),
            'generation_success_rate' => $this->getGenerationSuccessRate(),
            'latest_alert' => Cache::get('latest_quality_alert'),
            'top_issues' => $this->getTopQualityIssues(),
            'stage_distribution' => $this->getStageDistribution(),
            'export_metrics' => $this->getExportMetrics()
        ];
    }

    /**
     * Get current average quality score
     */
    public function getCurrentAverageScore(): ?float
    {
        return Cache::remember('quality_metrics_average', 3600, function () {
            $result = DB::table('advisor_quality_metrics')
                ->where('created_at', '>=', now()->subDays(7))
                ->avg('overall_score');
            
            return $result ? round($result, 2) : null;
        });
    }

    /**
     * Get recent average score (last 30 days)
     */
    public function getRecentAverageScore(): ?float
    {
        return Cache::remember('recent_quality_average', 3600, function () {
            $result = DB::table('advisor_quality_metrics')
                ->where('created_at', '>=', now()->subDays(30))
                ->avg('overall_score');
            
            return $result ? round($result, 2) : null;
        });
    }

    /**
     * Get quality trend over time
     */
    public function getQualityTrend(): array
    {
        return Cache::remember('quality_trend', 3600, function () {
            return DB::table('advisor_quality_metrics')
                ->selectRaw('DATE(created_at) as date, AVG(overall_score) as avg_score')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($row) {
                    return [
                        'date' => $row->date,
                        'score' => round($row->avg_score, 2)
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get generation success rate
     */
    public function getGenerationSuccessRate(): float
    {
        $total = DB::table('advisor_generation_jobs')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        
        if ($total === 0) {
            return 100.0;
        }
        
        $successful = DB::table('advisor_generation_jobs')
            ->where('created_at', '>=', now()->subDays(7))
            ->where('status', 'completed')
            ->count();
        
        return round(($successful / $total) * 100, 2);
    }

    /**
     * Get top quality issues
     */
    public function getTopQualityIssues(): array
    {
        // This would analyze stored issues
        // Simplified for now
        return [
            'placeholder_text' => 12,
            'voice_consistency' => 8,
            'specificity' => 6,
            'sentence_length' => 4
        ];
    }

    /**
     * Get stage distribution
     */
    public function getStageDistribution(): array
    {
        return Cache::remember('stage_distribution', 3600, function () {
            $results = DB::table('advisor_quality_metrics')
                ->selectRaw('stage, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('stage')
                ->get();
            
            $distribution = [];
            foreach ($results as $row) {
                $distribution[$row->stage] = $row->count;
            }
            
            return $distribution;
        });
    }

    /**
     * Get export metrics
     */
    public function getExportMetrics(): array
    {
        return [
            'total_exports' => DB::table('player_contexts')->sum('exported_advisors_count'),
            'recent_exports' => DB::table('player_contexts')
                ->where('last_advisor_export_at', '>=', now()->subDays(7))
                ->count(),
            'avg_exports_per_user' => DB::table('player_contexts')
                ->where('exported_advisors_count', '>', 0)
                ->avg('exported_advisors_count')
        ];
    }

    /**
     * Collect user feedback
     */
    public function collectFeedback(int $advisorId, int $rating, ?string $feedback = null): void
    {
        DB::table('advisor_feedback')->insert([
            'advisor_id' => $advisorId,
            'rating' => $rating,
            'feedback' => $feedback,
            'created_at' => now()
        ]);
        
        // Update cached metrics
        Cache::forget('user_satisfaction_score');
    }

    /**
     * Get user satisfaction score
     */
    public function getUserSatisfactionScore(): ?float
    {
        return Cache::remember('user_satisfaction_score', 3600, function () {
            $avg = DB::table('advisor_feedback')
                ->where('created_at', '>=', now()->subDays(30))
                ->avg('rating');
            
            return $avg ? round($avg, 2) : null;
        });
    }

    /**
     * Run A/B test on prompt variations
     */
    public function runPromptABTest(string $promptA, string $promptB, Advisor $advisor): array
    {
        Log::info('Running A/B test on prompt variations');
        
        // Test prompt A
        $resultA = $this->testPromptVariation($promptA, $advisor);
        
        // Test prompt B
        $resultB = $this->testPromptVariation($promptB, $advisor);
        
        // Compare results
        $comparison = [
            'prompt_a' => [
                'score' => $resultA['score'],
                'generation_time' => $resultA['time'],
                'cost' => $resultA['cost']
            ],
            'prompt_b' => [
                'score' => $resultB['score'],
                'generation_time' => $resultB['time'],
                'cost' => $resultB['cost']
            ],
            'winner' => $resultA['score'] > $resultB['score'] ? 'prompt_a' : 'prompt_b',
            'score_difference' => abs($resultA['score'] - $resultB['score'])
        ];
        
        // Store test results
        $this->storeABTestResults($comparison);
        
        return $comparison;
    }

    /**
     * Test a prompt variation
     */
    protected function testPromptVariation(string $prompt, Advisor $advisor): array
    {
        // This would actually test the prompt
        // Simplified for now
        return [
            'score' => rand(60, 95),
            'time' => rand(2, 8),
            'cost' => rand(1, 5) / 100
        ];
    }

    /**
     * Store A/B test results
     */
    protected function storeABTestResults(array $results): void
    {
        Cache::put('latest_ab_test', $results, now()->addDays(7));
    }
}