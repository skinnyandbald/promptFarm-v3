<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Validation\AdvisorQualityService;

class AnalyzeHistoricalPIs extends Command
{
    protected $signature = 'advisor:analyze-historical-pis';
    protected $description = 'Analyze the PI files from historically good conversational versions';

    public function handle(AdvisorQualityService $qualityService)
    {
        $this->info('🔍 ANALYZING HISTORICALLY GOOD PI FILES');
        $this->info('(The ones you said worked best in conversation)');
        $this->info('=' . str_repeat('=', 60));
        $this->newLine();
        
        $historicalPIs = [
            'Bog Halbert Homz Cal' => '/Users/ben/code/promptFarm-v2/storage/app/advisor-files/versions/Advisors - Bog Halbert Homz Cal/PI.md',
            'Hybrid (OG Good)' => '/Users/ben/code/promptFarm-v2/storage/app/advisor-files/versions/Advisors-Hybrid (OG Good)/PI.md'
        ];
        
        $currentPIs = [
            'V2 Main' => '/Users/ben/code/promptFarm-v2/storage/app/advisor-files/AlexBogusky_PI.md',
            'V3 Current' => '/Users/ben/code/promptFarm-v3/storage/app/advisors/alex-bogusky-improved/2025-09-01_05-49-43/PI.md'
        ];
        
        $allAnalysis = [];
        
        // Analyze historical "good" PIs
        $this->info('📜 HISTORICAL "GOOD" PIs (Best Conversation Experience)');
        foreach ($historicalPIs as $name => $path) {
            if (file_exists($path)) {
                $analysis = $this->analyzePI($path, $qualityService);
                $allAnalysis[$name] = $analysis;
                $this->displayPIAnalysis($name, $analysis, true);
            }
        }
        
        $this->newLine();
        
        // Analyze current PIs for comparison
        $this->info('📋 CURRENT PIs (For Comparison)');
        foreach ($currentPIs as $name => $path) {
            if (file_exists($path)) {
                $analysis = $this->analyzePI($path, $qualityService);
                $allAnalysis[$name] = $analysis;
                $this->displayPIAnalysis($name, $analysis, false);
            }
        }
        
        // Comparison analysis
        $this->newLine();
        $this->info('🔬 WHAT MADE THE HISTORICAL PIs BETTER FOR CONVERSATION?');
        $this->compareConversationalElements($allAnalysis);
        
        // Extract best practices
        $this->newLine();
        $this->info('💡 RECOMMENDED PI IMPROVEMENTS BASED ON HISTORICAL SUCCESS:');
        $this->extractBestPractices($allAnalysis);
        
        return 0;
    }
    
    protected function analyzePI($path, $qualityService): array
    {
        $content = file_get_contents($path);
        $qualityScore = $qualityService->scorePI($content);
        
        return [
            'content' => $content,
            'size' => strlen($content),
            'quality_score' => $qualityScore['percentage'],
            'quality_issues' => $qualityScore['issues'] ?? [],
            'conversational_elements' => $this->analyzeConversationalElements($content),
            'behavioral_instructions' => $this->analyzeBehavioralInstructions($content),
            'interaction_patterns' => $this->analyzeInteractionPatterns($content)
        ];
    }
    
    protected function analyzeConversationalElements($content): array
    {
        return [
            'question_asking' => $this->countPatterns($content, [
                '/ask.*question/i',
                '/question.*user/i', 
                '/what.*you think/i',
                '/how.*feel/i'
            ]),
            'challenge_encouragement' => $this->countPatterns($content, [
                '/challenge/i',
                '/push.*back/i',
                '/disagree/i',
                '/contrarian/i',
                '/devil.*advocate/i'
            ]),
            'engagement_words' => $this->countPatterns($content, [
                '/engage/i',
                '/interactive/i',
                '/conversation/i',
                '/dialogue/i',
                '/back.*forth/i'
            ]),
            'personality_markers' => $this->countPatterns($content, [
                '/personality/i',
                '/voice/i',
                '/tone/i',
                '/style/i',
                '/character/i'
            ]),
            'excitement_encouragement' => $this->countPatterns($content, [
                '/exciting/i',
                '/energy/i',
                '/passionate/i',
                '/enthusiastic/i'
            ])
        ];
    }
    
    protected function analyzeBehavioralInstructions($content): array
    {
        return [
            'response_style' => $this->extractPatterns($content, [
                '/respond.*by/i',
                '/when.*respond/i',
                '/answer.*should/i'
            ]),
            'interaction_rules' => $this->extractPatterns($content, [
                '/always.*do/i',
                '/never.*do/i',
                '/if.*user/i',
                '/when.*user/i'
            ]),
            'conversation_flow' => $this->extractPatterns($content, [
                '/follow.*up/i',
                '/build.*on/i',
                '/expand.*on/i',
                '/dive.*deeper/i'
            ])
        ];
    }
    
    protected function analyzeInteractionPatterns($content): array
    {
        return [
            'has_specific_examples' => str_contains($content, 'example') ? 1 : 0,
            'encourages_specificity' => $this->countPatterns($content, [
                '/specific/i',
                '/detail/i',
                '/concrete/i',
                '/exact/i'
            ]),
            'builds_rapport' => $this->countPatterns($content, [
                '/rapport/i',
                '/relationship/i',
                '/connect/i',
                '/understand.*user/i'
            ]),
            'maintains_character' => $this->countPatterns($content, [
                '/stay.*character/i',
                '/remain/i',
                '/maintain/i',
                '/consistent/i'
            ])
        ];
    }
    
    protected function countPatterns($content, $patterns): int
    {
        $count = 0;
        foreach ($patterns as $pattern) {
            $count += preg_match_all($pattern, $content);
        }
        return $count;
    }
    
    protected function extractPatterns($content, $patterns): array
    {
        $matches = [];
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $found)) {
                $matches = array_merge($matches, $found[0]);
            }
        }
        return array_unique($matches);
    }
    
    protected function displayPIAnalysis($name, $analysis, $isHistorical)
    {
        $emoji = $isHistorical ? '🏆' : '📊';
        $this->line("$emoji $name");
        $this->line("   Size: {$analysis['size']} chars | Quality Score: {$analysis['quality_score']}%");
        
        // Show conversational elements
        $conv = $analysis['conversational_elements'];
        $conversationalScore = array_sum($conv);
        $this->line("   Conversational Elements Score: $conversationalScore");
        
        $highlights = [];
        if ($conv['question_asking'] > 2) $highlights[] = "Questions({$conv['question_asking']})";
        if ($conv['challenge_encouragement'] > 2) $highlights[] = "Challenge({$conv['challenge_encouragement']})";
        if ($conv['engagement_words'] > 3) $highlights[] = "Engaging({$conv['engagement_words']})";
        
        if (!empty($highlights)) {
            $this->line("   Strong in: " . implode(', ', $highlights));
        }
        
        // Show some behavioral instruction examples
        if (!empty($analysis['behavioral_instructions']['response_style'])) {
            $example = array_slice($analysis['behavioral_instructions']['response_style'], 0, 1)[0] ?? '';
            if ($example) {
                $this->line("   Example instruction: \"" . substr($example, 0, 60) . "...\"");
            }
        }
        
        $this->newLine();
    }
    
    protected function compareConversationalElements($allAnalysis)
    {
        $historical = ['Bog Halbert Homz Cal', 'Hybrid (OG Good)'];
        $current = ['V2 Main', 'V3 Current'];
        
        // Calculate average conversational scores
        $historicalAvg = 0;
        $currentAvg = 0;
        $historicalCount = 0;
        $currentCount = 0;
        
        foreach ($allAnalysis as $name => $analysis) {
            $score = array_sum($analysis['conversational_elements']);
            if (in_array($name, $historical)) {
                $historicalAvg += $score;
                $historicalCount++;
            } elseif (in_array($name, $current)) {
                $currentAvg += $score;
                $currentCount++;
            }
        }
        
        $historicalAvg = $historicalCount > 0 ? $historicalAvg / $historicalCount : 0;
        $currentAvg = $currentCount > 0 ? $currentAvg / $currentCount : 0;
        
        $this->table(
            ['Element', 'Historical Avg', 'Current Avg', 'Difference'],
            [
                ['Question Asking', 
                 round($this->getAverage($allAnalysis, $historical, 'conversational_elements', 'question_asking'), 1),
                 round($this->getAverage($allAnalysis, $current, 'conversational_elements', 'question_asking'), 1),
                 $this->getDifference($allAnalysis, $historical, $current, 'conversational_elements', 'question_asking')
                ],
                ['Challenge/Contrarian',
                 round($this->getAverage($allAnalysis, $historical, 'conversational_elements', 'challenge_encouragement'), 1),
                 round($this->getAverage($allAnalysis, $current, 'conversational_elements', 'challenge_encouragement'), 1),
                 $this->getDifference($allAnalysis, $historical, $current, 'conversational_elements', 'challenge_encouragement')
                ],
                ['Engagement Focus',
                 round($this->getAverage($allAnalysis, $historical, 'conversational_elements', 'engagement_words'), 1),
                 round($this->getAverage($allAnalysis, $current, 'conversational_elements', 'engagement_words'), 1),
                 $this->getDifference($allAnalysis, $historical, $current, 'conversational_elements', 'engagement_words')
                ],
                ['Overall Conversational',
                 round($historicalAvg, 1),
                 round($currentAvg, 1),
                 round($historicalAvg - $currentAvg, 1) > 0 ? '+' . round($historicalAvg - $currentAvg, 1) : round($historicalAvg - $currentAvg, 1)
                ]
            ]
        );
    }
    
    protected function getAverage($allAnalysis, $groups, $category, $element): float
    {
        $total = 0;
        $count = 0;
        foreach ($groups as $group) {
            if (isset($allAnalysis[$group][$category][$element])) {
                $total += $allAnalysis[$group][$category][$element];
                $count++;
            }
        }
        return $count > 0 ? $total / $count : 0;
    }
    
    protected function getDifference($allAnalysis, $historical, $current, $category, $element): string
    {
        $histAvg = $this->getAverage($allAnalysis, $historical, $category, $element);
        $currAvg = $this->getAverage($allAnalysis, $current, $category, $element);
        $diff = $histAvg - $currAvg;
        return ($diff > 0 ? '+' : '') . round($diff, 1);
    }
    
    protected function extractBestPractices($allAnalysis)
    {
        $bestPractices = [];
        
        // Find what historical PIs did well
        $historical = ['Bog Halbert Homz Cal', 'Hybrid (OG Good)'];
        
        foreach ($historical as $name) {
            if (!isset($allAnalysis[$name])) continue;
            
            $analysis = $allAnalysis[$name];
            $conv = $analysis['conversational_elements'];
            
            if ($conv['question_asking'] > 3) {
                $bestPractices[] = "Add more question-asking instructions (historical had {$conv['question_asking']} references)";
            }
            
            if ($conv['challenge_encouragement'] > 2) {
                $bestPractices[] = "Strengthen contrarian/challenge instructions (historical had {$conv['challenge_encouragement']} references)";
            }
            
            if ($conv['engagement_words'] > 4) {
                $bestPractices[] = "Emphasize engagement and interactivity (historical had {$conv['engagement_words']} references)";
            }
        }
        
        // Show specific examples from historical PIs
        $this->line('Specific elements to incorporate:');
        foreach ($historical as $name) {
            if (!isset($allAnalysis[$name])) continue;
            
            $behaviorExamples = $allAnalysis[$name]['behavioral_instructions']['interaction_rules'];
            if (!empty($behaviorExamples)) {
                $this->line("From $name:");
                foreach (array_slice($behaviorExamples, 0, 2) as $example) {
                    $this->line("  • " . substr($example, 0, 80) . "...");
                }
            }
        }
        
        if (empty($bestPractices)) {
            $bestPractices[] = "Historical PIs show similar patterns - focus on overall engagement quality";
        }
        
        foreach ($bestPractices as $practice) {
            $this->line("• $practice");
        }
    }
}