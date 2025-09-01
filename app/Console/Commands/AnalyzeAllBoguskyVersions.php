<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Validation\AdvisorQualityService;

class AnalyzeAllBoguskyVersions extends Command
{
    protected $signature = 'advisor:analyze-all-bogusky';
    
    protected $description = 'Analyze ALL Bogusky versions from v2 and v3 projects';

    public function handle(AdvisorQualityService $qualityService)
    {
        $this->info('🔍 COMPREHENSIVE BOGUSKY VERSION ANALYSIS');
        $this->info('=' . str_repeat('=', 60));
        $this->newLine();
        
        $allVersions = [];
        
        // 1. V2 Project - Main files
        $this->info('📁 V2 PROJECT - MAIN FILES');
        $this->line('Path: /Users/ben/code/promptFarm-v2/storage/app/advisor-files/');
        $v2MainPath = '/Users/ben/code/promptFarm-v2/storage/app/advisor-files';
        
        if (file_exists("$v2MainPath/AlexBogusky_PI.md") && file_exists("$v2MainPath/AlexBogusky_PK.md")) {
            $result = $this->analyzeVersion(
                "$v2MainPath/AlexBogusky_PI.md",
                "$v2MainPath/AlexBogusky_PK.md",
                $qualityService
            );
            $allVersions['V2 Main (Latest)'] = $result;
            $this->displayResult('V2 Main (Latest)', $result);
        }
        
        // 2. V2 Versions - Advisors-Hybrid (OG Good)
        $this->newLine();
        $this->info('📁 V2 VERSIONS - "Advisors-Hybrid (OG Good)"');
        $this->line('Path: /versions/Advisors-Hybrid (OG Good)/');
        $this->comment('Note: You mentioned this historically had best output');
        
        $hybridPath = "$v2MainPath/versions/Advisors-Hybrid (OG Good)";
        if (file_exists("$hybridPath/Bogusky_PK.md")) {
            // Note: This version might not have PI file
            $piPath = file_exists("$hybridPath/Bogusky_PI.md") ? "$hybridPath/Bogusky_PI.md" : null;
            if (!$piPath) {
                $this->warn("  No PI file found, analyzing PK only");
            }
            $result = $this->analyzeVersionPKOnly(
                "$hybridPath/Bogusky_PK.md",
                $qualityService
            );
            $allVersions['V2 Hybrid (OG Good)'] = $result;
            $this->displayResult('V2 Hybrid (OG Good)', $result);
        }
        
        // 3. V2 Versions - Advisors - Bog Halbert Homz Cal
        $this->newLine();
        $this->info('📁 V2 VERSIONS - "Advisors - Bog Halbert Homz Cal"');
        $this->line('Path: /versions/Advisors - Bog Halbert Homz Cal/');
        $this->comment('Note: You mentioned this historically had best output');
        
        $bogPath = "$v2MainPath/versions/Advisors - Bog Halbert Homz Cal";
        if (file_exists("$bogPath/AlexBogusky_PK.md")) {
            $piPath = file_exists("$bogPath/AlexBogusky_PI.md") ? "$bogPath/AlexBogusky_PI.md" : null;
            if (!$piPath) {
                $this->warn("  No PI file found, analyzing PK only");
            }
            $result = $this->analyzeVersionPKOnly(
                "$bogPath/AlexBogusky_PK.md",
                $qualityService
            );
            $allVersions['V2 Bog/Halbert/Homz/Cal'] = $result;
            $this->displayResult('V2 Bog/Halbert/Homz/Cal', $result);
        }
        
        // 4. Other V2 versions
        $this->newLine();
        $this->info('📁 OTHER V2 VERSIONS');
        
        // LeanV1
        $leanPath = "$v2MainPath/versions/LeanV1";
        if (file_exists("$leanPath/AlexBogusky_PK.md")) {
            $result = $this->analyzeVersionPKOnly(
                "$leanPath/AlexBogusky_PK.md",
                $qualityService
            );
            $allVersions['V2 LeanV1'] = $result;
            $this->displayResult('V2 LeanV1', $result);
        }
        
        // hybrid-test
        $hybridTestPath = "$v2MainPath/versions/hybrid-test";
        if (file_exists("$hybridTestPath/AlexBogusky_PK.md")) {
            $result = $this->analyzeVersionPKOnly(
                "$hybridTestPath/AlexBogusky_PK.md",
                $qualityService
            );
            $allVersions['V2 hybrid-test'] = $result;
            $this->displayResult('V2 hybrid-test', $result);
        }
        
        // 5. V3 Project versions
        $this->newLine();
        $this->info('📁 V3 PROJECT VERSIONS');
        $this->line('Path: /Users/ben/code/promptFarm-v3/storage/app/advisors/');
        
        // V3 baseline (from v2)
        $v3BaselinePath = storage_path('app/advisors/baseline-v2');
        if (file_exists("$v3BaselinePath/AlexBogusky_PI.md")) {
            $result = $this->analyzeVersion(
                "$v3BaselinePath/AlexBogusky_PI.md",
                "$v3BaselinePath/AlexBogusky_PK.md",
                $qualityService
            );
            $allVersions['V3 Baseline (from V2)'] = $result;
            $this->displayResult('V3 Baseline (copied from V2)', $result);
        }
        
        // V3 original
        $v3OrigPath = storage_path('app/advisors/alex-bogusky-baseline');
        $piFiles = glob("$v3OrigPath/*PI*.md");
        $pkFiles = glob("$v3OrigPath/*PK*.md");
        if (!empty($piFiles) && !empty($pkFiles)) {
            $result = $this->analyzeVersion(
                $piFiles[0],
                $pkFiles[0],
                $qualityService
            );
            $allVersions['V3 Original'] = $result;
            $this->displayResult('V3 Original', $result);
        }
        
        // V3 improved (new generation)
        $v3ImprovedPath = storage_path('app/advisors/alex-bogusky-improved');
        if (file_exists($v3ImprovedPath)) {
            $generations = array_filter(glob("$v3ImprovedPath/*"), 'is_dir');
            foreach ($generations as $gen) {
                $timestamp = basename($gen);
                if (file_exists("$gen/PI.md") && file_exists("$gen/PK.md")) {
                    $result = $this->analyzeVersion(
                        "$gen/PI.md",
                        "$gen/PK.md",
                        $qualityService
                    );
                    $allVersions["V3 Improved ($timestamp)"] = $result;
                    $this->displayResult("V3 Improved ($timestamp)", $result);
                }
            }
        }
        
        // SUMMARY COMPARISON
        $this->newLine();
        $this->info('=' . str_repeat('=', 60));
        $this->info('📊 COMPREHENSIVE QUALITY COMPARISON');
        $this->newLine();
        
        $tableData = [];
        foreach ($allVersions as $name => $data) {
            $tableData[] = [
                $name,
                isset($data['pi_score']) ? $data['pi_score'] . '%' : 'N/A',
                $data['pk_score'] . '%',
                $data['overall_score'] . '%',
                $data['has_placeholders'] ? '❌' : '✅',
                $data['voice_strength'],
                $data['specificity']
            ];
        }
        
        $this->table(
            ['Version', 'PI', 'PK', 'Overall', 'Clean', 'Voice', 'Specific'],
            $tableData
        );
        
        // Find best performers
        $bestOverall = 0;
        $bestPK = 0;
        $bestOverallName = '';
        $bestPKName = '';
        
        foreach ($allVersions as $name => $data) {
            if ($data['overall_score'] > $bestOverall) {
                $bestOverall = $data['overall_score'];
                $bestOverallName = $name;
            }
            if ($data['pk_score'] > $bestPK) {
                $bestPK = $data['pk_score'];
                $bestPKName = $name;
            }
        }
        
        $this->newLine();
        $this->info('🏆 BEST PERFORMERS:');
        $this->line("  Best Overall: $bestOverallName ({$bestOverall}%)");
        $this->line("  Best PK: $bestPKName ({$bestPK}%)");
        
        // Explain current baseline
        $this->newLine();
        $this->info('📍 WHAT\'S IN OUR CURRENT BASELINE COMPARISON:');
        $this->line('  - "V2 Baseline" = /Users/ben/code/promptFarm-v2/storage/app/advisor-files/AlexBogusky_*.md');
        $this->line('  - "V3 Original" = /Users/ben/code/promptFarm-v3/docs/instructions-to-rebuild/starter-files/public/advisors/bogusky_*.md');
        $this->line('  - "V3 Improved" = New generations with Stage 1 improvements');
        
        return 0;
    }
    
    protected function analyzeVersion($piPath, $pkPath, $qualityService): array
    {
        $piContent = file_get_contents($piPath);
        $pkContent = file_get_contents($pkPath);
        
        $piScore = $qualityService->scorePI($piContent);
        $pkScore = $qualityService->scorePK($pkContent);
        
        return $this->buildAnalysis($piContent, $pkContent, $piScore, $pkScore);
    }
    
    protected function analyzeVersionPKOnly($pkPath, $qualityService): array
    {
        $pkContent = file_get_contents($pkPath);
        $pkScore = $qualityService->scorePK($pkContent);
        
        return $this->buildAnalysis(null, $pkContent, null, $pkScore);
    }
    
    protected function buildAnalysis($piContent, $pkContent, $piScore, $pkScore): array
    {
        $result = [
            'pk_score' => $pkScore['percentage'],
            'pk_issues' => count($pkScore['issues'] ?? []),
            'pk_size' => strlen($pkContent),
            'has_placeholders' => $this->hasPlaceholders($pkContent),
            'voice_strength' => $this->analyzeVoice($pkContent),
            'specificity' => $this->analyzeSpecificity($pkContent)
        ];
        
        if ($piContent && $piScore) {
            $result['pi_score'] = $piScore['percentage'];
            $result['pi_issues'] = count($piScore['issues'] ?? []);
            $result['pi_size'] = strlen($piContent);
            $result['overall_score'] = round(($piScore['percentage'] + $pkScore['percentage']) / 2, 1);
        } else {
            $result['overall_score'] = $pkScore['percentage'];
        }
        
        return $result;
    }
    
    protected function displayResult($name, $data)
    {
        $this->line("  $name:");
        
        if (isset($data['pi_score'])) {
            $this->line("    PI: {$data['pi_score']}% ({$data['pi_size']} chars, {$data['pi_issues']} issues)");
        } else {
            $this->line("    PI: Not available");
        }
        
        $this->line("    PK: {$data['pk_score']}% ({$data['pk_size']} chars, {$data['pk_issues']} issues)");
        $this->line("    Overall: {$data['overall_score']}%");
        
        $qualities = [];
        if (!$data['has_placeholders']) $qualities[] = '✅ Clean';
        if ($data['voice_strength'] === 'Strong') $qualities[] = '💪 Strong Voice';
        if ($data['specificity'] === 'High') $qualities[] = '🎯 High Specificity';
        
        if (!empty($qualities)) {
            $this->line("    " . implode(' | ', $qualities));
        }
    }
    
    protected function hasPlaceholders($content): bool
    {
        $placeholders = ['[company]', '[brand]', '{{', '}}', 'INSERT_', 'PLACEHOLDER_'];
        foreach ($placeholders as $p) {
            if (stripos($content, $p) !== false) return true;
        }
        return false;
    }
    
    protected function analyzeVoice($content): string
    {
        $firstPerson = substr_count($content, 'I ') + substr_count($content, 'my ') + 
                      substr_count($content, "I've") + substr_count($content, 'My ');
        
        if ($firstPerson > 20) return 'Strong';
        if ($firstPerson > 10) return 'Medium';
        return 'Weak';
    }
    
    protected function analyzeSpecificity($content): string
    {
        $score = 0;
        
        // Real companies
        $companies = ['Burger King', 'MINI', 'Nike', 'Apple', 'Domino', 'Coca-Cola', 'Truth'];
        foreach ($companies as $c) {
            if (stripos($content, $c) !== false) $score += 10;
        }
        
        // Metrics
        preg_match_all('/\d+%/', $content, $m);
        $score += count($m[0]) * 5;
        
        // Dates
        preg_match_all('/\b(19|20)\d{2}\b/', $content, $y);
        $score += count($y[0]) * 5;
        
        if ($score >= 80) return 'High';
        if ($score >= 40) return 'Medium';
        return 'Low';
    }
}