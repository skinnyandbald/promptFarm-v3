<?php

namespace App\Console\Commands\Testing;

use App\Models\Advisor;
use App\Services\AdvisorGenerationService;
use App\Services\SimpleQualityService;
use App\Services\Validation\AdvisorQualityService;
use Illuminate\Console\Command;

class TestSpecificAdvisor extends Command
{
    protected $signature = 'testing:advisor 
                            {advisor : Advisor slug (e.g., alex-bogusky, cal-henderson, alex-hormozi, gary-halbert)}
                            {--compare : Compare with baseline}
                            {--save : Save the new generation}
                            {--temperature= : Override default temperature}
                            {--approach=current : Generation approach (current, tension-v2, analytical, hybrid)}
                            {--output-dir= : Custom output directory for test results}';

    protected $description = 'Test generation for a specific advisor with various approaches';

    public function handle(
        AdvisorGenerationService $generationService,
        SimpleQualityService $simpleQualityService,
        AdvisorQualityService $qualityService
    ): int {
        $advisorKey = $this->argument('advisor');

        $this->info("🚀 Testing {$advisorKey} Generation");
        $this->newLine();

        // Find advisor
        $advisor = Advisor::where('slug', $advisorKey)
            ->orWhere('slug', str_replace('-', '_', $advisorKey))
            ->first();

        if (! $advisor) {
            $this->error("Advisor '{$advisorKey}' not found in database.");
            $this->line('Available advisors:');
            Advisor::pluck('slug')->each(fn ($slug) => $this->line("  - {$slug}"));

            return Command::FAILURE;
        }

        $this->info("Using advisor: {$advisor->name} (ID: {$advisor->id})");
        $this->newLine();

        // Set up output directory
        $outputDir = $this->option('output-dir')
            ?? "advisor-tests/experiments/{$advisor->slug}/".now()->format('Y-m-d_H-i-s');

        // Score baseline if requested
        if ($this->option('compare')) {
            $this->compareWithBaseline($advisor, $qualityService);
        }

        // Configure generation based on approach
        $approach = $this->option('approach');
        $this->configureGenerationApproach($generationService, $approach, $advisor);

        // Override temperature if specified
        if ($temperature = $this->option('temperature')) {
            $this->info("Using custom temperature: {$temperature}");
            // This would need to be implemented in AdvisorGenerationService
            // to accept temperature override
        }

        // Generate new version
        $this->info("Generating new {$advisor->name} with '{$approach}' approach...");
        $startTime = microtime(true);

        try {
            $result = $generationService->generateAdvisor($advisor);

            $duration = round(microtime(true) - $startTime, 2);
            $this->info("✅ Generation completed in {$duration} seconds");

            // Analyze quality
            $this->analyzeGeneratedQuality($result, $qualityService, $simpleQualityService);

            // Save if requested
            if ($this->option('save')) {
                $this->saveTestResults($advisor, $result, $outputDir);
            }

            // Compare specific improvements
            if ($this->option('compare')) {
                $this->compareImprovements($advisor, $result);
            }

        } catch (\Exception $e) {
            $this->error('Generation failed: '.$e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function compareWithBaseline(Advisor $advisor, AdvisorQualityService $qualityService): void
    {
        $this->info('📊 Analyzing Baseline Quality...');

        $advisorKey = str_replace('_', '-', $advisor->slug);
        $baselinePath = storage_path("app/advisors/{$advisorKey}");

        $piFile = collect(glob($baselinePath.'/*_PI.md'))->sort()->last();
        $pkFile = collect(glob($baselinePath.'/*_PK.md'))->sort()->last();
        if (! $piFile || ! $pkFile) {
            $this->warn('No baseline found for comparison.');
            return;
        }
        $piContent = file_get_contents($piFile);
        $pkContent = file_get_contents($pkFile);

        $piQuality = $qualityService->scorePI($piContent);
        $pkQuality = $qualityService->scorePK($pkContent);

        $this->table(
            ['Component', 'Score', 'Issues'],
            [
                ['PI', ($piQuality['percentage'] ?? $piQuality['score']).'%', count($piQuality['issues'] ?? [])],
                ['PK', ($pkQuality['percentage'] ?? $pkQuality['score']).'%', count($pkQuality['issues'] ?? [])],
            ]
        );

        $this->newLine();
    }

    private function configureGenerationApproach(
        AdvisorGenerationService $service,
        string $approach,
        Advisor $advisor
    ): void {
        // This would need to be implemented in AdvisorGenerationService
        // to support different generation approaches

        switch ($approach) {
            case 'tension-v2':
                $this->info('Using Tension V2 approach (confrontational, unfiltered)');
                // Configure for tension v2
                break;

            case 'analytical':
                $this->info('Using Analytical Tension approach');
                // Configure for analytical
                break;

            case 'hybrid':
                $this->info('Using Hybrid approach (Voice Anchor + Analytical)');
                // Configure for hybrid
                break;

            case 'current':
            default:
                $this->info('Using current production approach');
                break;
        }
    }

    private function analyzeGeneratedQuality(
        array $result,
        AdvisorQualityService $qualityService,
        SimpleQualityService $simpleQualityService
    ): void {
        $this->newLine();
        $this->info('📊 Quality Analysis:');

        // Structural quality
        $piQuality = $qualityService->scorePI($result['pi'] ?? '');
        $pkQuality = $qualityService->scorePK($result['pk'] ?? '');

        $this->table(
            ['Component', 'Structural Score', 'Issues'],
            [
                ['PI', $piQuality['score'].'%', count($piQuality['failed_checks'] ?? [])],
                ['PK', $pkQuality['score'].'%', count($pkQuality['failed_checks'] ?? [])],
            ]
        );

        // Content quality
        $contentScore = $simpleQualityService->calculateQualityScore(
            $result['pi'] ?? '',
            $result['pk'] ?? ''
        );

        $this->newLine();
        $this->info("Content Quality Score: {$contentScore}%");

        // Check for specific issues
        $this->checkForCommonIssues($result);
    }

    private function checkForCommonIssues(array $result): void
    {
        $issues = [];

        $content = ($result['pi'] ?? '').($result['pk'] ?? '');

        // Check for name corruption
        if (preg_match('/I\'m ([^\.]+)/', $content, $matches)) {
            $declaredName = $matches[1];
            if (stripos($declaredName, 'nodelbert') !== false ||
                stripos($declaredName, 'I I I') !== false) {
                $issues[] = "❌ Name corruption detected: '{$declaredName}'";
            }
        }

        // Check for repetition
        if (preg_match('/(I I I|the the the|and and and)/i', $content)) {
            $issues[] = '❌ Token repetition detected';
        }

        // Check for placeholder content
        if (preg_match('/\[Insert.*?\]|\{.*?\}|TBD|TODO/i', $content)) {
            $issues[] = '⚠️ Placeholder content detected';
        }

        // Check for generic business speak (for technical advisors)
        $advisor = Advisor::find($result['advisor_id'] ?? 0);
        if ($advisor && in_array($advisor->slug, ['henderson', 'halbert'])) {
            if (preg_match('/McKinsey|viral marketing|growth hacking/i', $content)) {
                $issues[] = '⚠️ Generic business content in technical advisor';
            }
        }

        if (! empty($issues)) {
            $this->newLine();
            $this->warn('Common Issues Found:');
            foreach ($issues as $issue) {
                $this->line($issue);
            }
        } else {
            $this->info('✅ No common issues detected');
        }
    }

    private function saveTestResults(Advisor $advisor, array $result, string $outputDir): void
    {
        $fullPath = storage_path("app/{$outputDir}");

        if (! file_exists($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        // Save PI and PK
        file_put_contents($fullPath.'/PI.md', $result['pi'] ?? '');
        file_put_contents($fullPath.'/PK.md', $result['pk'] ?? '');

        // Save metadata
        $metadata = [
            'advisor' => $advisor->slug,
            'approach' => $this->option('approach'),
            'temperature' => $this->option('temperature'),
            'generated_at' => now()->toIso8601String(),
            'quality_scores' => [
                'pi' => $result['pi_score'] ?? 0,
                'pk' => $result['pk_score'] ?? 0,
            ],
        ];

        file_put_contents(
            $fullPath.'/metadata.json',
            json_encode($metadata, JSON_PRETTY_PRINT)
        );

        $this->info("✅ Test results saved to: {$outputDir}");
    }

    private function compareImprovements(Advisor $advisor, array $result): void
    {
        $this->newLine();
        $this->info('📈 Improvement Analysis:');

        // This would compare specific aspects between baseline and new generation
        // For example:
        // - Voice consistency
        // - Domain relevance
        // - Controversial stance strength
        // - Specific terminology usage

        $improvements = [
            'Voice Consistency' => $this->checkVoiceConsistency($result),
            'Domain Relevance' => $this->checkDomainRelevance($advisor, $result),
            'Tension Strength' => $this->checkTensionStrength($result),
        ];

        $tableData = [];
        foreach ($improvements as $aspect => $score) {
            $tableData[] = [$aspect, $score.'%'];
        }

        $this->table(['Aspect', 'Score'], $tableData);
    }

    private function checkVoiceConsistency(array $result): int
    {
        // Simple heuristic: check if voice anchor is present and consistent
        $content = $result['pi'] ?? '';

        if (preg_match('/I\'m [^\.]+\./', $content)) {
            return 85; // Has voice anchor
        }

        return 50; // Missing voice anchor
    }

    private function checkDomainRelevance(Advisor $advisor, array $result): int
    {
        $content = ($result['pi'] ?? '').($result['pk'] ?? '');
        $score = 50; // Base score

        // Check for domain-specific terms based on advisor
        $domainTerms = match ($advisor->slug) {
            'henderson' => ['microservices', 'kubernetes', 'monolith', 'API', 'scaling'],
            'bogusky' => ['brand', 'campaign', 'creative', 'advertising', 'culture'],
            'hormozi' => ['offer', 'revenue', 'profit', 'scaling', 'acquisition'],
            'halbert' => ['copy', 'headline', 'sales letter', 'conversion', 'response'],
            default => []
        };

        foreach ($domainTerms as $term) {
            if (stripos($content, $term) !== false) {
                $score += 10;
            }
        }

        return min($score, 100);
    }

    private function checkTensionStrength(array $result): int
    {
        $content = $result['pk'] ?? '';

        // Look for tension indicators
        $tensionIndicators = [
            '/everyone believes.*?vs\.?\s*what actually happens/i' => 20,
            '/the paradox:/i' => 15,
            '/the uncomfortable truth:/i' => 15,
            '/the lie they believed:/i' => 10,
            '/\$\d+[MBK]?\s*(million|billion|thousand)?/i' => 10, // Specific numbers
        ];

        $score = 0;
        foreach ($tensionIndicators as $pattern => $points) {
            if (preg_match($pattern, $content)) {
                $score += $points;
            }
        }

        return min($score, 100);
    }
}
