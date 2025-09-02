<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BreakthroughPromptArchitecture;
use App\Services\ReasoningModelActivation;
use App\Services\AdvisorGenerationService;

class MigrateToReasoningArchitecture extends Command
{
    protected $signature = 'advisor:migrate-reasoning 
                            {advisor? : The advisor key to migrate (optional, migrates all if not specified)}
                            {--test : Run in test mode without saving}
                            {--compare : Generate both old and new versions for comparison}';
    
    protected $description = 'Migrate advisors to breakthrough reasoning-activated architecture';
    
    public function handle()
    {
        $this->info('🚀 Starting Migration to Reasoning-Activated Architecture');
        $this->newLine();
        
        $advisorKey = $this->argument('advisor');
        $testMode = $this->option('test');
        $compareMode = $this->option('compare');
        
        if ($advisorKey) {
            $this->migrateAdvisor($advisorKey, $testMode, $compareMode);
        } else {
            $this->migrateAllAdvisors($testMode, $compareMode);
        }
    }
    
    private function migrateAdvisor(string $advisorKey, bool $testMode, bool $compareMode)
    {
        $this->info("Migrating advisor: {$advisorKey}");
        
        // Load advisor data
        $advisorData = $this->loadAdvisorData($advisorKey);
        
        if ($compareMode) {
            $this->generateComparison($advisorData);
            return;
        }
        
        // Generate new PI with reasoning activation
        $this->line('→ Generating enhanced PI with reasoning patterns...');
        $enhancedPI = $this->generateEnhancedPI($advisorData);
        
        // Generate new PK with analytical tensions
        $this->line('→ Generating enhanced PK with analytical frameworks...');
        $enhancedPK = $this->generateEnhancedPK($advisorData);
        
        // Test effectiveness
        $this->line('→ Testing reasoning activation...');
        $testResults = $this->testReasoningActivation($enhancedPI, $enhancedPK);
        
        $this->displayTestResults($testResults);
        
        if (!$testMode && $testResults['score'] >= 70) {
            $this->saveEnhancedAdvisor($advisorKey, $enhancedPI, $enhancedPK);
            $this->info("✅ Advisor migrated successfully!");
        } elseif ($testResults['score'] < 70) {
            $this->warn("⚠️ Reasoning activation score too low ({$testResults['score']}%). Manual review needed.");
        }
    }
    
    private function generateEnhancedPI(array $advisorData): string
    {
        // Core PI structure
        $pi = BreakthroughPromptArchitecture::generateEnhancedPI($advisorData);
        
        // Add reasoning activation protocol
        $reasoningProtocol = ReasoningModelActivation::generateReasoningActivatedPI($advisorData);
        
        // Combine with pattern injection
        return $this->combineAndOptimize($pi['prompt_template'], $reasoningProtocol);
    }
    
    private function generateEnhancedPK(array $advisorData): string
    {
        // Core PK structure
        $pk = BreakthroughPromptArchitecture::generateEnhancedPK($advisorData);
        
        // Add reasoning primers throughout
        $primers = ReasoningModelActivation::generateReasoningPrimers();
        
        // Inject primers at strategic points
        return $this->injectReasoningPrimers($pk['knowledge_template'], $primers['primers']);
    }
    
    private function testReasoningActivation(string $pi, string $pk): array
    {
        $score = 0;
        $tests = [];
        
        // Test 1: Reasoning pattern density
        $patternDensity = $this->calculatePatternDensity($pi . $pk);
        $tests['pattern_density'] = $patternDensity;
        $score += min(30, $patternDensity * 10);
        
        // Test 2: Analytical frame count
        $frameCount = $this->countAnalyticalFrames($pi . $pk);
        $tests['analytical_frames'] = $frameCount;
        $score += min(30, $frameCount * 3);
        
        // Test 3: Contradiction pairs
        $contradictionCount = $this->countContradictions($pk);
        $tests['contradictions'] = $contradictionCount;
        $score += min(20, $contradictionCount * 5);
        
        // Test 4: Evidence specificity
        $specificityScore = $this->measureSpecificity($pk);
        $tests['specificity'] = $specificityScore;
        $score += min(20, $specificityScore);
        
        return [
            'score' => $score,
            'tests' => $tests,
            'recommendation' => $this->getRecommendation($score)
        ];
    }
    
    private function calculatePatternDensity(string $content): float
    {
        $patterns = [
            '/constraint[s]?\s*:/i',
            '/step\s+\d+:/i',
            '/paradox|contradiction/i',
            '/however|yet|despite/i',
            '/analyze|examine|investigate/i',
            '/because|therefore|thus/i'
        ];
        
        $totalMatches = 0;
        foreach ($patterns as $pattern) {
            $totalMatches += preg_match_all($pattern, $content);
        }
        
        $wordCount = str_word_count($content);
        return ($totalMatches / $wordCount) * 100; // Patterns per 100 words
    }
    
    private function countAnalyticalFrames(string $content): int
    {
        $frames = [
            '/analyze why/i',
            '/map the incentive/i',
            '/trace the caus/i',
            '/examine the pattern/i',
            '/investigate the mechanism/i',
            '/reconcile the contradiction/i'
        ];
        
        $count = 0;
        foreach ($frames as $frame) {
            $count += preg_match_all($frame, $content);
        }
        
        return $count;
    }
    
    private function countContradictions(string $content): int
    {
        // Look for paired contradictory evidence
        $contradictionMarkers = [
            '/evidence set a:.*evidence set b:/is',
            '/on one hand.*on the other/is',
            '/conventional wisdom.*yet.*reality/is',
            '/everyone believes.*but.*actually/is'
        ];
        
        $count = 0;
        foreach ($contradictionMarkers as $marker) {
            $count += preg_match_all($marker, $content);
        }
        
        return $count;
    }
    
    private function measureSpecificity(string $content): float
    {
        $specificElements = [
            'companies' => preg_match_all('/\b(Apple|Google|Microsoft|Amazon|Tesla|Nike|McKinsey|Deloitte|etc)\b/i', $content),
            'metrics' => preg_match_all('/\d+\.?\d*\s*(%|\$|x|M|B|K)/', $content),
            'dates' => preg_match_all('/\b(19|20)\d{2}\b/', $content),
            'campaigns' => preg_match_all('/"[^"]+"\s*(campaign|project|initiative)/i', $content)
        ];
        
        $totalSpecific = array_sum($specificElements);
        $wordCount = str_word_count($content);
        
        return min(100, ($totalSpecific / $wordCount) * 1000); // Specific elements per 1000 words
    }
    
    private function combineAndOptimize(string $template1, string $template2): string
    {
        // Intelligent combination that avoids redundancy
        $combined = $template1 . "\n\n" . $template2;
        
        // Remove duplicate sections
        $combined = $this->removeDuplicateSections($combined);
        
        // Optimize for ChatGPT token limits
        if (strlen($combined) > 30000) {
            $combined = $this->prioritizeContent($combined);
        }
        
        return $combined;
    }
    
    private function injectReasoningPrimers(string $content, array $primers): string
    {
        // Find strategic injection points
        $sections = explode("\n## ", $content);
        
        // Inject primers between sections
        $enhanced = [];
        foreach ($sections as $i => $section) {
            $enhanced[] = $section;
            
            // Add primer after every 2 sections
            if ($i % 2 === 1 && $i < count($sections) - 1) {
                $primerIndex = array_rand($primers);
                $enhanced[] = "\n💭 *{$primers[$primerIndex]}*\n";
            }
        }
        
        return implode("\n## ", $enhanced);
    }
    
    private function removeDuplicateSections(string $content): string
    {
        // Simple deduplication - in production, use more sophisticated approach
        $lines = explode("\n", $content);
        $seen = [];
        $result = [];
        
        foreach ($lines as $line) {
            $normalized = strtolower(trim($line));
            if (!in_array($normalized, $seen) || strlen($normalized) < 10) {
                $result[] = $line;
                $seen[] = $normalized;
            }
        }
        
        return implode("\n", $result);
    }
    
    private function prioritizeContent(string $content): string
    {
        // Prioritize reasoning activation content over generic instructions
        // This is a simplified version - enhance for production
        $priority = [
            'Reasoning Activation Protocol' => 1,
            'Analytical Tensions' => 2,
            'Conflicting Case Studies' => 3,
            'System Failure Analysis' => 4,
            'Voice Anchor' => 5
        ];
        
        // Split into sections and reorder by priority
        // ... implementation details ...
        
        return $content; // Simplified for now
    }
    
    private function getRecommendation(float $score): string
    {
        if ($score >= 85) {
            return 'Excellent - Strong reasoning activation expected';
        } elseif ($score >= 70) {
            return 'Good - Reasoning should activate in most cases';
        } elseif ($score >= 50) {
            return 'Moderate - Partial reasoning activation likely';
        } else {
            return 'Poor - Unlikely to trigger reasoning models';
        }
    }
    
    private function displayTestResults(array $results): void
    {
        $this->newLine();
        $this->info('Test Results:');
        $this->line('─────────────────────────────');
        
        foreach ($results['tests'] as $test => $value) {
            $label = str_replace('_', ' ', ucfirst($test));
            $this->line("{$label}: {$value}");
        }
        
        $this->newLine();
        $this->line("Overall Score: {$results['score']}%");
        $this->line("Recommendation: {$results['recommendation']}");
        $this->line('─────────────────────────────');
    }
    
    private function generateComparison(array $advisorData): void
    {
        $this->info('Generating comparison between old and new approaches...');
        
        // Generate using both methods
        $oldPI = $this->generateOldStylePI($advisorData);
        $newPI = $this->generateEnhancedPI($advisorData);
        
        // Save comparison files
        $timestamp = now()->format('Y-m-d_H-i-s');
        $comparisonDir = storage_path("app/comparisons/{$timestamp}");
        
        if (!file_exists($comparisonDir)) {
            mkdir($comparisonDir, 0755, true);
        }
        
        file_put_contents("{$comparisonDir}/old_approach_PI.md", $oldPI);
        file_put_contents("{$comparisonDir}/new_approach_PI.md", $newPI);
        
        $this->info("Comparison saved to: {$comparisonDir}");
        
        // Generate test prompts
        $testPrompts = [
            "How do I improve my marketing?",
            "Should I hire McKinsey?",
            "What's wrong with best practices?",
            "Why do transformations fail?",
            "How do I compete with bigger companies?"
        ];
        
        file_put_contents("{$comparisonDir}/test_prompts.txt", implode("\n\n", $testPrompts));
        
        $this->info("Test with these prompts in ChatGPT to see the difference!");
    }
    
    private function generateOldStylePI($advisorData): string
    {
        // Simulate old generation approach for comparison
        return "# Traditional Advisor PI\n\nBe controversial. Challenge everything. Name enemies...";
    }
    
    private function loadAdvisorData(string $key): array
    {
        // Load from database or config
        return [
            'key' => $key,
            'full_name' => 'Test Advisor',
            'core_expertise_area' => 'Strategic Advisory',
            // ... other fields
        ];
    }
    
    private function saveEnhancedAdvisor(string $key, string $pi, string $pk): void
    {
        // Save to storage
        $basePath = storage_path("app/advisors/{$key}_enhanced");
        
        if (!file_exists($basePath)) {
            mkdir($basePath, 0755, true);
        }
        
        file_put_contents("{$basePath}/PI.md", $pi);
        file_put_contents("{$basePath}/PK.md", $pk);
        
        // Log the migration
        $this->logMigration($key);
    }
    
    private function logMigration(string $key): void
    {
        $log = [
            'advisor' => $key,
            'migrated_at' => now()->toIso8601String(),
            'version' => 'reasoning_v1'
        ];
        
        $logFile = storage_path('logs/advisor_migrations.json');
        $logs = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];
        $logs[] = $log;
        
        file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));
    }
    
    private function migrateAllAdvisors(bool $testMode, bool $compareMode): void
    {
        // Get all advisor keys
        $advisorKeys = ['alex-bogusky', 'strategic-advisor', 'contrarian-advisor']; // Load from DB
        
        foreach ($advisorKeys as $key) {
            $this->migrateAdvisor($key, $testMode, $compareMode);
            $this->newLine();
        }
    }
}