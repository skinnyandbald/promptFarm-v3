<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Validation\AdvisorQualityService;
use Illuminate\Support\Facades\Storage;

class CompareBoguskyQuality extends Command
{
    protected $signature = 'advisor:compare-bogusky 
                            {--all : Compare all versions}
                            {--baseline=v2 : Which baseline to use (v2, v3-original)}';
    
    protected $description = 'Compare Bogusky quality across different versions';

    public function handle(AdvisorQualityService $qualityService)
    {
        $this->info('📊 Bogusky Quality Comparison Analysis');
        $this->newLine();
        
        $results = [];
        
        // Analyze v2 baseline (previous implementation)
        $this->info('Analyzing V2 Baseline (Previous Implementation)...');
        $v2Path = storage_path('app/advisors/baseline-v2');
        if (file_exists($v2Path . '/AlexBogusky_PI.md')) {
            $results['V2 Baseline'] = $this->analyzeVersion(
                $v2Path . '/AlexBogusky_PI.md',
                $v2Path . '/AlexBogusky_PK.md',
                $qualityService
            );
            $this->displayVersionResults('V2 Baseline', $results['V2 Baseline']);
        }
        
        // Analyze v3 original files if they exist
        $this->info('Analyzing V3 Original Files...');
        $v3OriginalPath = storage_path('app/advisors/alex-bogusky-baseline');
        $piFiles = glob($v3OriginalPath . '/*PI*.md');
        $pkFiles = glob($v3OriginalPath . '/*PK*.md');
        
        if (!empty($piFiles) && !empty($pkFiles)) {
            $results['V3 Original'] = $this->analyzeVersion(
                $piFiles[0],
                $pkFiles[0],
                $qualityService
            );
            $this->displayVersionResults('V3 Original', $results['V3 Original']);
        }
        
        // Check for any new generations
        $this->info('Checking for New Generations...');
        $improvedPath = Storage::disk('advisors')->path('alex-bogusky-improved');
        if (file_exists($improvedPath)) {
            $generations = array_filter(glob($improvedPath . '/*'), 'is_dir');
            foreach ($generations as $gen) {
                $timestamp = basename($gen);
                if (file_exists($gen . '/PI.md') && file_exists($gen . '/PK.md')) {
                    $results["New Gen {$timestamp}"] = $this->analyzeVersion(
                        $gen . '/PI.md',
                        $gen . '/PK.md',
                        $qualityService
                    );
                    $this->displayVersionResults("New Generation ({$timestamp})", $results["New Gen {$timestamp}"]);
                }
            }
        }
        
        // Summary comparison
        $this->newLine();
        $this->info('📈 QUALITY SUMMARY COMPARISON');
        $this->newLine();
        
        $tableData = [];
        foreach ($results as $version => $data) {
            $tableData[] = [
                $version,
                $data['scores']['pi'] . '%',
                $data['scores']['pk'] . '%',
                $data['scores']['overall'] . '%',
                $data['quality']['placeholders'] ? '❌' : '✅',
                $data['quality']['voice_strength'],
                $data['quality']['specificity_score']
            ];
        }
        
        $this->table(
            ['Version', 'PI Score', 'PK Score', 'Overall', 'No Placeholders', 'Voice', 'Specificity'],
            $tableData
        );
        
        // Identify best performer
        $bestScore = 0;
        $bestVersion = '';
        foreach ($results as $version => $data) {
            if ($data['scores']['overall'] > $bestScore) {
                $bestScore = $data['scores']['overall'];
                $bestVersion = $version;
            }
        }
        
        $this->newLine();
        $this->info("🏆 Best Quality: {$bestVersion} with {$bestScore}% overall score");
        
        // Check against target
        if ($bestScore >= 85) {
            $this->info("✅ Target quality of 85% achieved!");
        } else {
            $gap = 85 - $bestScore;
            $this->warn("⚠️  {$gap}% below target quality of 85%");
        }
        
        return 0;
    }
    
    protected function analyzeVersion($piPath, $pkPath, $qualityService): array
    {
        $piContent = file_get_contents($piPath);
        $pkContent = file_get_contents($pkPath);
        
        $piScore = $qualityService->scorePI($piContent);
        $pkScore = $qualityService->scorePK($pkContent);
        
        // Check for quality indicators
        $placeholders = $this->checkForPlaceholders($pkContent);
        $voiceStrength = $this->measureVoiceStrength($pkContent);
        $specificityScore = $this->measureSpecificity($pkContent);
        
        return [
            'scores' => [
                'pi' => $piScore['percentage'],
                'pk' => $pkScore['percentage'],
                'overall' => round(($piScore['percentage'] + $pkScore['percentage']) / 2, 2)
            ],
            'issues' => [
                'pi' => $piScore['issues'] ?? [],
                'pk' => $pkScore['issues'] ?? []
            ],
            'quality' => [
                'placeholders' => $placeholders,
                'voice_strength' => $voiceStrength,
                'specificity_score' => $specificityScore
            ],
            'content_length' => [
                'pi' => strlen($piContent),
                'pk' => strlen($pkContent)
            ]
        ];
    }
    
    protected function displayVersionResults($version, $data)
    {
        $this->newLine();
        $this->comment("--- {$version} ---");
        
        $overallScore = $data['scores']['overall'];
        $scoreColor = $overallScore >= 85 ? 'info' : ($overallScore >= 70 ? 'comment' : 'error');
        
        $this->$scoreColor("Overall Score: {$overallScore}%");
        $this->line("PI: {$data['scores']['pi']}% | PK: {$data['scores']['pk']}%");
        $this->line("Content Size: PI={$data['content_length']['pi']} chars, PK={$data['content_length']['pk']} chars");
        
        // Quality indicators
        $qualityIndicators = [];
        if (!$data['quality']['placeholders']) {
            $qualityIndicators[] = '✅ No placeholders';
        } else {
            $qualityIndicators[] = '❌ Has placeholders';
        }
        
        $qualityIndicators[] = "Voice: {$data['quality']['voice_strength']}";
        $qualityIndicators[] = "Specificity: {$data['quality']['specificity_score']}";
        
        $this->line(implode(' | ', $qualityIndicators));
        
        // Show top issues if any
        $totalIssues = count($data['issues']['pi']) + count($data['issues']['pk']);
        if ($totalIssues > 0) {
            $this->warn("Issues detected: {$totalIssues} total");
            if (!empty($data['issues']['pk']) && count($data['issues']['pk']) > 0) {
                $this->line("  PK: " . $data['issues']['pk'][0]);
            }
        }
    }
    
    protected function checkForPlaceholders($content): bool
    {
        $placeholders = [
            '[company]', '[brand]', '[client]', '{{', '}}', 
            'INSERT_', 'PLACEHOLDER_', '<insert', '<placeholder'
        ];
        
        foreach ($placeholders as $placeholder) {
            if (stripos($content, $placeholder) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    protected function measureVoiceStrength($content): string
    {
        $firstPersonCount = substr_count($content, 'I ') + 
                           substr_count($content, 'my ') + 
                           substr_count($content, "I've") +
                           substr_count($content, 'My ') +
                           substr_count($content, "I'm");
        
        if ($firstPersonCount > 20) {
            return 'Strong';
        } elseif ($firstPersonCount > 10) {
            return 'Medium';
        } else {
            return 'Weak';
        }
    }
    
    protected function measureSpecificity($content): string
    {
        $score = 0;
        
        // Check for real company names
        $companies = ['Burger King', 'MINI', 'Domino', 'Nike', 'Apple', 
                     'Coca-Cola', 'Truth', 'Volkswagen', 'Microsoft', 'Google'];
        foreach ($companies as $company) {
            if (stripos($content, $company) !== false) {
                $score += 10;
            }
        }
        
        // Check for specific metrics
        preg_match_all('/\d+%/', $content, $percentages);
        $score += count($percentages[0]) * 5;
        
        // Check for years/dates
        preg_match_all('/\b(19|20)\d{2}\b/', $content, $years);
        $score += count($years[0]) * 5;
        
        // Check for specific campaign names in quotes
        preg_match_all('/"[^"]{3,30}"/', $content, $campaigns);
        $score += count($campaigns[0]) * 3;
        
        if ($score >= 80) {
            return 'High';
        } elseif ($score >= 40) {
            return 'Medium';
        } else {
            return 'Low';
        }
    }
}