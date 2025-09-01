<?php

namespace App\Console\Commands\Testing;

use Illuminate\Console\Command;
use App\Services\Validation\AdvisorQualityService;
use Illuminate\Support\Facades\Storage;
use App\Models\Advisor;

class CompareAdvisorVersions extends Command
{
    protected $signature = 'testing:compare 
                            {advisor? : Advisor key to compare (e.g., bogusky, henderson)}
                            {--all : Compare all advisors}
                            {--baseline=current : Which baseline to compare against (v2, v3-original, current)}
                            {--output=table : Output format (table, json, detailed)}';
    
    protected $description = 'Compare advisor quality across different versions';

    public function handle(AdvisorQualityService $qualityService): int
    {
        $this->info('📊 Advisor Version Comparison Analysis');
        $this->newLine();
        
        // Determine which advisors to compare
        $advisors = $this->getAdvisorsToCompare();
        
        if (empty($advisors)) {
            $this->error('No advisors found to compare.');
            return Command::FAILURE;
        }
        
        $allResults = [];
        
        foreach ($advisors as $advisor) {
            $this->info("Analyzing {$advisor->name}...");
            $results = $this->compareAdvisorVersions($advisor, $qualityService);
            
            if (!empty($results)) {
                $allResults[$advisor->key] = $results;
                $this->displayResults($advisor->name, $results);
            }
        }
        
        // Output summary
        if ($this->option('all') && count($allResults) > 1) {
            $this->displaySummary($allResults);
        }
        
        // Save results if JSON output requested
        if ($this->option('output') === 'json') {
            $this->saveJsonResults($allResults);
        }
        
        return Command::SUCCESS;
    }
    
    private function getAdvisorsToCompare(): array
    {
        if ($this->option('all')) {
            return Advisor::all()->toArray();
        }
        
        $advisorKey = $this->argument('advisor');
        if (!$advisorKey) {
            // Interactive selection
            $advisors = Advisor::pluck('name', 'key')->toArray();
            $advisorKey = $this->choice(
                'Which advisor would you like to compare?',
                array_keys($advisors),
                0
            );
        }
        
        $advisor = Advisor::where('key', $advisorKey)
            ->orWhere('key', str_replace('-', '_', $advisorKey))
            ->first();
            
        return $advisor ? [$advisor] : [];
    }
    
    private function compareAdvisorVersions(object $advisor, AdvisorQualityService $qualityService): array
    {
        $results = [];
        $advisorKey = str_replace('_', '-', $advisor->key);
        
        // Check current production version
        $currentPath = storage_path("app/advisors/{$advisorKey}");
        if (file_exists($currentPath . '/PI.md')) {
            $results['Current'] = $this->analyzeVersion(
                $currentPath . '/PI.md',
                $currentPath . '/PK.md',
                $qualityService
            );
        }
        
        // Check baseline versions
        $baseline = $this->option('baseline');
        
        // V2 baseline (if exists)
        if ($baseline === 'v2' || $baseline === 'all') {
            $v2Path = storage_path("app/advisor-backups/baseline-v2");
            $v2Files = $this->findAdvisorFiles($v2Path, $advisor->name);
            if ($v2Files) {
                $results['V2 Baseline'] = $this->analyzeVersion(
                    $v2Files['pi'],
                    $v2Files['pk'],
                    $qualityService
                );
            }
        }
        
        // V3 original (if exists)
        if ($baseline === 'v3-original' || $baseline === 'all') {
            $v3Path = storage_path("app/advisor-backups/{$advisorKey}-v3-original");
            if (file_exists($v3Path)) {
                $v3Files = $this->findAdvisorFiles($v3Path, $advisor->name);
                if ($v3Files) {
                    $results['V3 Original'] = $this->analyzeVersion(
                        $v3Files['pi'],
                        $v3Files['pk'],
                        $qualityService
                    );
                }
            }
        }
        
        // Check for test versions
        $testPath = storage_path("app/advisor-tests/comparisons/{$advisorKey}");
        if (file_exists($testPath)) {
            $testVersions = glob($testPath . '/*/PI.md');
            foreach ($testVersions as $piFile) {
                $versionName = basename(dirname($piFile));
                $pkFile = str_replace('/PI.md', '/PK.md', $piFile);
                if (file_exists($pkFile)) {
                    $results["Test: {$versionName}"] = $this->analyzeVersion(
                        $piFile,
                        $pkFile,
                        $qualityService
                    );
                }
            }
        }
        
        return $results;
    }
    
    private function findAdvisorFiles(string $path, string $advisorName): ?array
    {
        $searchName = str_replace(' ', '', $advisorName); // Remove spaces
        
        // Look for PI file
        $piPatterns = [
            "{$searchName}_PI.md",
            "{$searchName}PI.md",
            "*{$searchName}*PI*.md"
        ];
        
        $piFile = null;
        foreach ($piPatterns as $pattern) {
            $files = glob($path . '/' . $pattern);
            if (!empty($files)) {
                $piFile = $files[0];
                break;
            }
        }
        
        if (!$piFile) {
            return null;
        }
        
        // Find corresponding PK file
        $pkFile = str_replace('PI', 'PK', $piFile);
        if (!file_exists($pkFile)) {
            // Try alternative patterns
            $pkFile = str_replace('_PI.md', '_PK.md', $piFile);
        }
        
        return file_exists($pkFile) ? ['pi' => $piFile, 'pk' => $pkFile] : null;
    }
    
    private function analyzeVersion(string $piPath, string $pkPath, AdvisorQualityService $qualityService): array
    {
        $piContent = file_exists($piPath) ? file_get_contents($piPath) : '';
        $pkContent = file_exists($pkPath) ? file_get_contents($pkPath) : '';
        
        // Get quality scores
        $piQuality = $qualityService->validatePI($piContent);
        $pkQuality = $qualityService->validatePK($pkContent);
        
        // Extract metadata if present
        $metadata = $this->extractMetadata($piContent);
        
        return [
            'pi_score' => $piQuality['score'] ?? 0,
            'pk_score' => $pkQuality['score'] ?? 0,
            'overall_score' => round(
                (($piQuality['score'] ?? 0) + ($pkQuality['score'] ?? 0)) / 2, 
                1
            ),
            'pi_issues' => $piQuality['failed_checks'] ?? [],
            'pk_issues' => $pkQuality['failed_checks'] ?? [],
            'generated_at' => $metadata['generated_at'] ?? 'Unknown',
            'file_sizes' => [
                'pi' => file_exists($piPath) ? filesize($piPath) : 0,
                'pk' => file_exists($pkPath) ? filesize($pkPath) : 0,
            ]
        ];
    }
    
    private function extractMetadata(string $content): array
    {
        $metadata = [];
        
        // Extract frontmatter
        if (preg_match('/^---\n(.*?)\n---/s', $content, $matches)) {
            $lines = explode("\n", $matches[1]);
            foreach ($lines as $line) {
                if (strpos($line, ':') !== false) {
                    list($key, $value) = explode(':', $line, 2);
                    $metadata[trim($key)] = trim($value);
                }
            }
        }
        
        return $metadata;
    }
    
    private function displayResults(string $advisorName, array $results): void
    {
        $this->newLine();
        $this->info("═══ {$advisorName} Comparison ═══");
        
        $tableData = [];
        foreach ($results as $version => $data) {
            $tableData[] = [
                $version,
                $data['overall_score'] . '%',
                $data['pi_score'] . '%',
                $data['pk_score'] . '%',
                count($data['pi_issues']) + count($data['pk_issues']),
                $this->formatFileSize($data['file_sizes']['pi'] + $data['file_sizes']['pk'])
            ];
        }
        
        $this->table(
            ['Version', 'Overall', 'PI Score', 'PK Score', 'Issues', 'Size'],
            $tableData
        );
        
        if ($this->option('output') === 'detailed') {
            foreach ($results as $version => $data) {
                $this->displayDetailedIssues($version, $data);
            }
        }
    }
    
    private function displayDetailedIssues(string $version, array $data): void
    {
        if (empty($data['pi_issues']) && empty($data['pk_issues'])) {
            return;
        }
        
        $this->info("Issues in {$version}:");
        
        if (!empty($data['pi_issues'])) {
            $this->line("  PI Issues:");
            foreach ($data['pi_issues'] as $issue) {
                $this->line("    - {$issue}");
            }
        }
        
        if (!empty($data['pk_issues'])) {
            $this->line("  PK Issues:");
            foreach ($data['pk_issues'] as $issue) {
                $this->line("    - {$issue}");
            }
        }
        
        $this->newLine();
    }
    
    private function displaySummary(array $allResults): void
    {
        $this->newLine();
        $this->info('═══ Overall Summary ═══');
        
        $summaryData = [];
        foreach ($allResults as $advisorKey => $versions) {
            $advisor = Advisor::where('key', $advisorKey)->first();
            $currentScore = $versions['Current']['overall_score'] ?? 0;
            $improvement = 0;
            
            // Calculate improvement from baseline
            if (isset($versions['V2 Baseline'])) {
                $improvement = $currentScore - $versions['V2 Baseline']['overall_score'];
            } elseif (isset($versions['V3 Original'])) {
                $improvement = $currentScore - $versions['V3 Original']['overall_score'];
            }
            
            $summaryData[] = [
                $advisor->name ?? $advisorKey,
                $currentScore . '%',
                ($improvement >= 0 ? '+' : '') . $improvement . '%',
                count($versions)
            ];
        }
        
        $this->table(
            ['Advisor', 'Current Score', 'Improvement', 'Versions'],
            $summaryData
        );
    }
    
    private function saveJsonResults(array $results): void
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "advisor-comparison-{$timestamp}.json";
        $path = storage_path("app/advisor-tests/comparisons/{$filename}");
        
        // Ensure directory exists
        $dir = dirname($path);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($path, json_encode($results, JSON_PRETTY_PRINT));
        $this->info("Results saved to: {$path}");
    }
    
    private function formatFileSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        } else {
            return round($bytes / 1048576, 2) . ' MB';
        }
    }
}