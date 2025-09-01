<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Advisor;
use App\Services\AdvisorGenerationService;
use App\Services\SimpleQualityService;
use App\Services\Validation\AdvisorQualityService;
use Illuminate\Support\Facades\Storage;

class TestBoguskyGeneration extends Command
{
    protected $signature = 'advisor:test-bogusky 
                            {--compare : Compare with baseline}
                            {--save : Save the new generation}';
    
    protected $description = 'Test improved Bogusky generation and compare quality with baseline';

    public function handle(
        AdvisorGenerationService $generationService,
        SimpleQualityService $simpleQualityService,
        AdvisorQualityService $qualityService
    ) {
        $this->info('🚀 Testing Bogusky Generation with Stage 1 Improvements');
        $this->newLine();
        
        // Find or create Bogusky advisor
        $advisor = Advisor::where('name', 'LIKE', '%Bogusky%')
            ->orWhere('key', 'alex_bogusky')
            ->first();
            
        if (!$advisor) {
            $this->error('Bogusky advisor not found in database. Creating test advisor...');
            $advisor = Advisor::create([
                'name' => 'Alex Bogusky',
                'key' => 'alex_bogusky',
                'advisor_type' => 'contrarian',
                'core_expertise_area' => 'Advertising and Brand Strategy',
                'background_description' => 'Co-founder and former chief creative officer of Crispin Porter + Bogusky',
                'notable_achievements' => 'Burger King "Subservient Chicken", MINI Cooper US launch, Truth anti-smoking campaign',
                'decision_making_approach' => 'Challenge conventions, embrace controversy, create cultural moments',
                'key_phrases_or_terminology' => 'Cultural hijacking, earned media, fearless creativity'
            ]);
        }
        
        $this->info("Using advisor: {$advisor->name} (ID: {$advisor->id})");
        $this->newLine();
        
        // Score baseline if requested
        if ($this->option('compare')) {
            $this->info('📊 Analyzing Baseline Quality...');
            $this->analyzeBaseline($qualityService);
            $this->newLine();
        }
        
        // Generate new version with improvements
        $this->info('🔧 Generating with Stage 1 Improvements...');
        $this->newLine();
        
        $progressBar = $this->output->createProgressBar(100);
        $progressBar->start();
        
        try {
            $result = $generationService->generateAdvisor($advisor, 'v1', function($progress, $message) use ($progressBar) {
                $progressBar->setProgress($progress);
                if ($progress % 25 === 0) {
                    $this->newLine();
                    $this->info($message);
                }
            });
            
            $progressBar->finish();
            $this->newLine(2);
            
            // Display quality results
            $this->info('✅ Generation Complete!');
            $this->newLine();
            
            $quality = $result['quality'];
            $this->displayQualityResults($quality);
            
            // Check for improvements
            $this->checkForPlaceholders($result['pk_content']);
            $this->checkVoiceConsistency($result['pk_content']);
            $this->checkSpecificity($result['pk_content']);
            
            // Save if requested
            if ($this->option('save')) {
                $this->saveNewGeneration($result);
            }
            
            // Compare with baseline
            if ($this->option('compare')) {
                $this->compareWithBaseline($quality);
            }
            
        } catch (\Exception $e) {
            $progressBar->finish();
            $this->newLine(2);
            $this->error('Generation failed: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    protected function analyzeBaseline($qualityService)
    {
        $baselinePath = storage_path('app/advisors/alex-bogusky-baseline');
        
        if (!file_exists($baselinePath)) {
            $this->warn('No baseline files found');
            return;
        }
        
        $piFiles = glob($baselinePath . '/*PI*.md');
        $pkFiles = glob($baselinePath . '/*PK*.md');
        
        if (empty($piFiles) || empty($pkFiles)) {
            $this->warn('Baseline PI or PK files not found');
            return;
        }
        
        $piContent = file_get_contents($piFiles[0]);
        $pkContent = file_get_contents($pkFiles[0]);
        
        $piScore = $qualityService->scorePI($piContent);
        $pkScore = $qualityService->scorePK($pkContent);
        
        $this->table(
            ['Metric', 'PI Score', 'PK Score', 'Overall'],
            [
                ['Score', $piScore['percentage'] . '%', $pkScore['percentage'] . '%', round(($piScore['percentage'] + $pkScore['percentage']) / 2, 2) . '%'],
                ['Valid', $piScore['valid'] ? '✅' : '❌', $pkScore['valid'] ? '✅' : '❌', ($piScore['valid'] && $pkScore['valid']) ? '✅' : '❌'],
                ['Issues', count($piScore['issues']), count($pkScore['issues']), count($piScore['issues']) + count($pkScore['issues'])]
            ]
        );
        
        // Store baseline score for comparison
        cache()->put('bogusky_baseline_score', [
            'pi' => $piScore['percentage'],
            'pk' => $pkScore['percentage'],
            'overall' => round(($piScore['percentage'] + $pkScore['percentage']) / 2, 2)
        ], now()->addHour());
    }
    
    protected function displayQualityResults($quality)
    {
        $overallScore = $quality['summary']['overall_score'] ?? 0;
        $piScore = $quality['pi']['percentage'] ?? 0;
        $pkScore = $quality['pk']['percentage'] ?? 0;
        
        // Color code based on score
        $overallColor = $overallScore >= 85 ? 'info' : ($overallScore >= 70 ? 'comment' : 'error');
        
        $this->$overallColor("Overall Quality Score: {$overallScore}%");
        $this->newLine();
        
        $this->table(
            ['Component', 'Score', 'Status', 'Issues'],
            [
                ['PI (Instructions)', $piScore . '%', $piScore >= 70 ? '✅ Valid' : '❌ Invalid', count($quality['pi']['issues'] ?? [])],
                ['PK (Knowledge)', $pkScore . '%', $pkScore >= 70 ? '✅ Valid' : '❌ Invalid', count($quality['pk']['issues'] ?? [])],
            ]
        );
        
        if (!empty($quality['pi']['issues']) || !empty($quality['pk']['issues'])) {
            $this->newLine();
            $this->warn('Quality Issues Detected:');
            
            foreach ($quality['pi']['issues'] ?? [] as $issue) {
                $this->line("  PI: {$issue}");
            }
            
            foreach ($quality['pk']['issues'] ?? [] as $issue) {
                $this->line("  PK: {$issue}");
            }
        }
    }
    
    protected function checkForPlaceholders($content)
    {
        $this->info('🔍 Checking for Placeholders...');
        
        $placeholders = ['[company]', '[brand]', '{{', '}}', 'INSERT_', 'PLACEHOLDER_'];
        $found = [];
        
        foreach ($placeholders as $placeholder) {
            if (stripos($content, $placeholder) !== false) {
                $found[] = $placeholder;
            }
        }
        
        if (empty($found)) {
            $this->info('✅ No placeholders found - content is specific!');
        } else {
            $this->error('❌ Placeholders found: ' . implode(', ', $found));
        }
    }
    
    protected function checkVoiceConsistency($content)
    {
        $this->info('🎭 Checking Voice Consistency...');
        
        $firstPersonCount = substr_count($content, 'I ') + 
                           substr_count($content, 'my ') + 
                           substr_count($content, "I've") +
                           substr_count($content, 'My ');
        
        if ($firstPersonCount > 10) {
            $this->info("✅ Strong first-person voice detected ({$firstPersonCount} instances)");
        } else {
            $this->warn("⚠️  Weak first-person voice ({$firstPersonCount} instances)");
        }
    }
    
    protected function checkSpecificity($content)
    {
        $this->info('🎯 Checking Specificity...');
        
        // Check for specific companies
        $companies = ['Burger King', 'MINI', 'Domino', 'Nike', 'Apple', 'Coca-Cola', 'Truth'];
        $foundCompanies = [];
        
        foreach ($companies as $company) {
            if (stripos($content, $company) !== false) {
                $foundCompanies[] = $company;
            }
        }
        
        // Check for specific metrics (percentages)
        preg_match_all('/\d+%/', $content, $percentages);
        
        // Check for years/dates
        preg_match_all('/\b(19|20)\d{2}\b/', $content, $years);
        
        $this->table(
            ['Specificity Check', 'Result'],
            [
                ['Real Companies', count($foundCompanies) > 0 ? '✅ ' . implode(', ', array_slice($foundCompanies, 0, 3)) . '...' : '❌ None found'],
                ['Exact Metrics', count($percentages[0]) > 0 ? '✅ ' . count($percentages[0]) . ' percentages found' : '❌ No metrics'],
                ['Dates/Years', count($years[0]) > 0 ? '✅ ' . count($years[0]) . ' dates found' : '❌ No dates'],
            ]
        );
    }
    
    protected function compareWithBaseline($newQuality)
    {
        $baseline = cache()->get('bogusky_baseline_score');
        
        if (!$baseline) {
            $this->warn('No baseline score available for comparison');
            return;
        }
        
        $this->newLine();
        $this->info('📈 Comparison with Baseline:');
        
        $newOverall = $newQuality['summary']['overall_score'] ?? 0;
        $improvement = $newOverall - $baseline['overall'];
        
        $this->table(
            ['Metric', 'Baseline', 'New', 'Change'],
            [
                ['Overall', $baseline['overall'] . '%', $newOverall . '%', ($improvement >= 0 ? '+' : '') . round($improvement, 2) . '%'],
                ['PI Score', $baseline['pi'] . '%', ($newQuality['pi']['percentage'] ?? 0) . '%', ($newQuality['pi']['percentage'] - $baseline['pi'] >= 0 ? '+' : '') . round($newQuality['pi']['percentage'] - $baseline['pi'], 2) . '%'],
                ['PK Score', $baseline['pk'] . '%', ($newQuality['pk']['percentage'] ?? 0) . '%', ($newQuality['pk']['percentage'] - $baseline['pk'] >= 0 ? '+' : '') . round($newQuality['pk']['percentage'] - $baseline['pk'], 2) . '%'],
            ]
        );
        
        if ($improvement > 0) {
            $this->info("🎉 Quality improved by {$improvement}%!");
        } elseif ($improvement < 0) {
            $this->warn("⚠️  Quality decreased by " . abs($improvement) . "%");
        } else {
            $this->comment("Quality unchanged");
        }
        
        // Check against target
        if ($newOverall >= 85) {
            $this->info("✨ Achieved target quality of 85%+!");
        } else {
            $gap = 85 - $newOverall;
            $this->comment("📊 {$gap}% below target quality of 85%");
        }
    }
    
    protected function saveNewGeneration($result)
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $path = "advisors/alex-bogusky-improved/{$timestamp}";
        
        Storage::disk('advisors')->put("{$path}/PI.md", $result['pi_content']);
        Storage::disk('advisors')->put("{$path}/PK.md", $result['pk_content']);
        Storage::disk('advisors')->put("{$path}/quality.json", json_encode($result['quality'], JSON_PRETTY_PRINT));
        
        $this->info("💾 Saved to: storage/app/{$path}");
    }
}