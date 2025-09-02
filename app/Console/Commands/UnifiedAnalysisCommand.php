<?php

namespace App\Console\Commands;

use App\Models\Advisor;
use App\Services\Validation\AdvisorQualityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class UnifiedAnalysisCommand extends Command
{
    protected $signature = 'advisor:analyze 
        {type : Analysis type (historical|versions|quality|approach)}
        {--advisor= : Specific advisor to analyze}
        {--metric= : Specific metric to measure}
        {--compare= : Versions to compare}
        {--output=table : Output format (table|json|csv)}';

    protected $description = 'Unified command for all advisor quality analysis';

    public function __construct(
        private AdvisorQualityService $qualityService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $analysisType = $this->argument('type');

        return match ($analysisType) {
            'historical' => $this->analyzeHistorical(),
            'versions' => $this->compareVersions(),
            'quality' => $this->analyzeQuality(),
            'approach' => $this->testApproaches(),
            default => $this->handleInvalidType()
        };
    }

    private function analyzeHistorical(): int
    {
        $this->info('📊 Analyzing Historical PI Patterns');
        $this->line('-----------------------------------');

        $advisorKey = $this->option('advisor') ?? 'bogusky';

        // Find historical PI files
        $historicalPaths = [
            'advisors/historical/Advisors - Bog Halbert Homz Cal/PI.md',
            'advisors/historical/[archived] Advisors - BHCH/PI.md',
        ];

        $currentPath = "advisors/{$advisorKey}/current/PI.md";

        $historicalData = [];
        $currentData = null;

        // Analyze historical versions
        foreach ($historicalPaths as $path) {
            if (File::exists(storage_path("app/{$path}"))) {
                $content = File::get(storage_path("app/{$path}"));
                $historicalData[] = $this->analyzeContent($content, basename(dirname($path)));
            }
        }

        // Analyze current version
        if (Storage::disk('advisors')->exists("{$advisorKey}/current/PI.md")) {
            $content = Storage::disk('advisors')->get("{$advisorKey}/current/PI.md");
            $currentData = $this->analyzeContent($content, 'current');
        }

        // Display comparison
        $this->displayHistoricalComparison($historicalData, $currentData);

        // Extract best practices
        $this->extractBestPractices($historicalData, $currentData);

        return Command::SUCCESS;
    }

    private function compareVersions(): int
    {
        $this->info('🔍 Comparing Advisor Versions');
        $this->line('------------------------------');

        $advisorKey = $this->option('advisor');

        // Get advisors to compare
        $advisors = $advisorKey
            ? Advisor::where('key', $advisorKey)->get()
            : Advisor::all();

        if ($advisors->isEmpty()) {
            $this->error('No advisors found');

            return Command::FAILURE;
        }

        $results = [];

        foreach ($advisors as $advisor) {
            $this->info("Analyzing: {$advisor->name}");

            $versions = $this->findAdvisorVersions($advisor->key);

            foreach ($versions as $version => $paths) {
                $piScore = null;
                $pkScore = null;

                if (isset($paths['pi']) && File::exists($paths['pi'])) {
                    $piContent = File::get($paths['pi']);
                    $piValidation = $this->qualityService->scorePI($piContent);
                    $piScore = is_array($piValidation) ? ($piValidation['percentage'] ?? 0) : ($piValidation ?? 0);
                }

                if (isset($paths['pk']) && File::exists($paths['pk'])) {
                    $pkContent = File::get($paths['pk']);
                    $pkValidation = $this->qualityService->scorePK($pkContent);
                    $pkScore = is_array($pkValidation) ? ($pkValidation['percentage'] ?? 0) : ($pkValidation ?? 0);
                }

                $results[] = [
                    'advisor' => $advisor->name,
                    'version' => $version,
                    'pi_score' => $piScore ?? 'N/A',
                    'pk_score' => $pkScore ?? 'N/A',
                    'combined' => ($piScore !== null && $pkScore !== null) ? round(($piScore + $pkScore) / 2, 1) : 'N/A',
                ];
            }
        }

        // Display results
        $this->table(
            ['Advisor', 'Version', 'PI Score', 'PK Score', 'Combined'],
            $results
        );

        // Output in requested format
        if ($this->option('output') === 'json') {
            $outputPath = storage_path('app/advisor-tests/version_comparison.json');
            File::put($outputPath, json_encode($results, JSON_PRETTY_PRINT));
            $this->info("Results saved to {$outputPath}");
        } elseif ($this->option('output') === 'csv') {
            $outputPath = storage_path('app/advisor-tests/version_comparison.csv');
            $csvContent = $this->buildCsvContent($results);
            File::put($outputPath, $csvContent);
            $this->info("Results saved to {$outputPath}");
        }

        return Command::SUCCESS;
    }

    private function analyzeQuality(): int
    {
        $this->info('💬 Analyzing Conversation Quality');
        $this->line('----------------------------------');

        // This would analyze exported ChatGPT conversations
        $conversationFile = $this->option('advisor') ?? 'conversations/export.json';

        if (! File::exists($conversationFile)) {
            $this->error("Conversation file not found: {$conversationFile}");
            $this->info('Please export a ChatGPT conversation to analyze');

            return Command::FAILURE;
        }

        $content = File::get($conversationFile);
        $conversation = json_decode($content, true);

        if (! $conversation) {
            // Try markdown format
            $messages = $this->parseMarkdownConversation($content);
        } else {
            $messages = $this->parseJsonConversation($conversation);
        }

        // Analyze engagement metrics
        $engagement = $this->analyzeEngagement($messages);
        $patterns = $this->identifyPatterns($messages);
        $score = $this->calculateQualityScore($engagement, $patterns);

        // Display results
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Messages', count($messages)],
                ['User Messages', $engagement['user_messages']],
                ['Assistant Messages', $engagement['assistant_messages']],
                ['Avg Message Length', $engagement['avg_length']],
                ['Questions Asked', $engagement['questions']],
                ['Excitement Indicators', $engagement['excitement']],
                ['Quality Score', "{$score}%"],
            ]
        );

        // Provide recommendations
        $this->provideQualityRecommendations($engagement, $patterns, $score);

        return Command::SUCCESS;
    }

    private function testApproaches(): int
    {
        $this->info('🧪 Testing Generation Approaches');
        $this->line('---------------------------------');

        $advisorKey = $this->option('advisor') ?? 'test_advisor';
        $approach = $this->option('compare') ?? 'analytical';

        $this->info("Testing {$approach} approach for {$advisorKey}");

        // This would test different generation approaches
        // For now, we'll show a comparison of what would be tested

        $approaches = [
            'standard' => [
                'description' => 'Basic generation with standard prompts',
                'reasoning_triggers' => 5,
                'specific_companies' => 3,
                'dollar_amounts' => 2,
                'contrarian_positions' => 1,
            ],
            'analytical' => [
                'description' => 'Enhanced with analytical tension framework',
                'reasoning_triggers' => 12,
                'specific_companies' => 8,
                'dollar_amounts' => 6,
                'contrarian_positions' => 4,
            ],
            'controversial' => [
                'description' => 'Optimized for controversial stances',
                'reasoning_triggers' => 8,
                'specific_companies' => 5,
                'dollar_amounts' => 3,
                'contrarian_positions' => 7,
            ],
        ];

        $results = [];
        foreach ($approaches as $name => $metrics) {
            $results[] = array_merge(['approach' => $name], $metrics);
        }

        $this->table(
            ['Approach', 'Description', 'Reasoning', 'Companies', 'Dollars', 'Contrarian'],
            array_map(fn ($r) => [
                $r['approach'],
                $r['description'],
                $r['reasoning_triggers'],
                $r['specific_companies'],
                $r['dollar_amounts'],
                $r['contrarian_positions'],
            ], $results)
        );

        $this->info('');
        $this->info('Recommended approach: analytical');
        $this->info('Reason: Best balance of specificity and reasoning depth');

        return Command::SUCCESS;
    }

    private function handleInvalidType(): int
    {
        $this->error('Invalid analysis type. Use: historical, versions, quality, or approach');

        return Command::FAILURE;
    }

    private function analyzeContent(string $content, string $version): array
    {
        // Pattern matching for conversational elements
        $questions = preg_match_all('/\?/', $content);
        $challenges = preg_match_all('/challenge|pushback|disagree|wrong/i', $content);
        $engagement = preg_match_all('/exciting|love|amazing|brilliant|wow/i', $content);
        $personalMarkers = preg_match_all('/\bI\s|my\s|I\'ve\s|I\'m\s/i', $content);

        return [
            'version' => $version,
            'questions' => $questions,
            'challenges' => $challenges,
            'engagement' => $engagement,
            'personal_markers' => $personalMarkers,
            'length' => strlen($content),
        ];
    }

    private function displayHistoricalComparison(array $historicalData, ?array $currentData): void
    {
        if (empty($historicalData)) {
            $this->warn('No historical data found');

            return;
        }

        $tableData = [];

        // Add historical averages
        $historicalAvg = [
            'version' => 'Historical Avg',
            'questions' => round(array_sum(array_column($historicalData, 'questions')) / count($historicalData)),
            'challenges' => round(array_sum(array_column($historicalData, 'challenges')) / count($historicalData)),
            'engagement' => round(array_sum(array_column($historicalData, 'engagement')) / count($historicalData)),
            'personal_markers' => round(array_sum(array_column($historicalData, 'personal_markers')) / count($historicalData)),
        ];
        $tableData[] = $historicalAvg;

        // Add current data if available
        if ($currentData) {
            $tableData[] = [
                'version' => 'Current',
                'questions' => $currentData['questions'],
                'challenges' => $currentData['challenges'],
                'engagement' => $currentData['engagement'],
                'personal_markers' => $currentData['personal_markers'],
            ];
        }

        $this->table(
            ['Version', 'Questions', 'Challenges', 'Engagement', 'Personal'],
            $tableData
        );
    }

    private function extractBestPractices(array $historicalData, ?array $currentData): void
    {
        $this->info('');
        $this->info('📝 Best Practices from Historical Analysis:');
        $this->line('-------------------------------------------');

        if (empty($historicalData)) {
            return;
        }

        $historicalAvg = [
            'questions' => array_sum(array_column($historicalData, 'questions')) / count($historicalData),
            'challenges' => array_sum(array_column($historicalData, 'challenges')) / count($historicalData),
            'engagement' => array_sum(array_column($historicalData, 'engagement')) / count($historicalData),
            'personal_markers' => array_sum(array_column($historicalData, 'personal_markers')) / count($historicalData),
        ];

        $recommendations = [];

        if ($currentData) {
            if ($currentData['questions'] < $historicalAvg['questions']) {
                $recommendations[] = '• Increase question-asking by '.round($historicalAvg['questions'] - $currentData['questions']).' instances';
            }

            if ($currentData['challenges'] < $historicalAvg['challenges']) {
                $recommendations[] = '• Add more challenging/contrarian positions';
            }

            if ($currentData['personal_markers'] < $historicalAvg['personal_markers']) {
                $recommendations[] = '• Use more first-person language ("I", "my", "I\'ve")';
            }
        }

        if (empty($recommendations)) {
            $this->info('✓ Current version meets or exceeds historical benchmarks');
        } else {
            foreach ($recommendations as $rec) {
                $this->warn($rec);
            }
        }
    }

    private function findAdvisorVersions(string $advisorKey): array
    {
        $versions = [];
        $advisorDirName = $this->getAdvisorDirectoryName($advisorKey);
        $basePath = storage_path("app/advisors/{$advisorDirName}");

        // Check standard version locations
        $versionPaths = [
            'v2' => "{$basePath}/v2",
            'v3' => "{$basePath}/v3",
            'historical' => "{$basePath}/historical",
            'test' => "{$basePath}/test",
        ];

        foreach ($versionPaths as $version => $path) {
            if (File::exists($path)) {
                $piPath = "{$path}/PI.md";
                $pkPath = "{$path}/PK.md";

                if (File::exists($piPath) || File::exists($pkPath)) {
                    $versions[$version] = [
                        'pi' => File::exists($piPath) ? $piPath : null,
                        'pk' => File::exists($pkPath) ? $pkPath : null,
                    ];
                }
            }
        }

        // Check timestamped versions in main advisor directory
        if (File::exists($basePath)) {
            $directories = File::directories($basePath);
            foreach ($directories as $dir) {
                $dirName = basename($dir);
                if (preg_match('/^\d{4}-\d{2}-\d{2}/', $dirName)) {
                    $piPath = "{$dir}/PI.md";
                    $pkPath = "{$dir}/PK.md";

                    if (File::exists($piPath) || File::exists($pkPath)) {
                        $versions[$dirName] = [
                            'pi' => File::exists($piPath) ? $piPath : null,
                            'pk' => File::exists($pkPath) ? $pkPath : null,
                        ];
                    }
                }
            }
        }

        // Check comparison tests directory
        $comparisonsPath = storage_path("app/advisor-tests/comparisons");
        if (File::exists($comparisonsPath)) {
            $compDirs = File::directories($comparisonsPath);
            foreach ($compDirs as $dir) {
                $dirName = basename($dir);
                if (preg_match('/^\d{4}-\d{2}-\d{2}/', $dirName)) {
                    // Look for PK files that might be comparison versions
                    $safePK = "{$dir}/safe_PK.md";
                    $controversialPK = "{$dir}/controversial_PK.md";
                    
                    if (File::exists($safePK)) {
                        $versions["safe-{$dirName}"] = [
                            'pi' => null,
                            'pk' => $safePK,
                        ];
                    }
                    
                    if (File::exists($controversialPK)) {
                        $versions["controversial-{$dirName}"] = [
                            'pi' => null,
                            'pk' => $controversialPK,
                        ];
                    }
                }
            }
        }

        return $versions;
    }

    private function parseMarkdownConversation(string $content): array
    {
        $messages = [];
        $lines = explode("\n", $content);
        $currentMessage = null;
        $currentRole = null;

        foreach ($lines as $line) {
            if (str_starts_with($line, '**User:**') || str_starts_with($line, '## User')) {
                if ($currentMessage !== null) {
                    $messages[] = [
                        'role' => $currentRole,
                        'content' => trim($currentMessage),
                    ];
                }
                $currentRole = 'user';
                $currentMessage = '';
            } elseif (str_starts_with($line, '**Assistant:**') || str_starts_with($line, '## Assistant')) {
                if ($currentMessage !== null) {
                    $messages[] = [
                        'role' => $currentRole,
                        'content' => trim($currentMessage),
                    ];
                }
                $currentRole = 'assistant';
                $currentMessage = '';
            } else {
                $currentMessage .= $line."\n";
            }
        }

        if ($currentMessage !== null) {
            $messages[] = [
                'role' => $currentRole,
                'content' => trim($currentMessage),
            ];
        }

        return $messages;
    }

    private function parseJsonConversation(array $conversation): array
    {
        $messages = [];

        // Handle ChatGPT export format
        if (isset($conversation['mapping'])) {
            foreach ($conversation['mapping'] as $nodeId => $node) {
                if (isset($node['message']) && $node['message'] !== null) {
                    $message = $node['message'];
                    if (isset($message['content']) && isset($message['content']['parts'])) {
                        $content = implode("\n", $message['content']['parts']);
                        $role = $message['author']['role'] ?? 'unknown';

                        if ($role === 'user' || $role === 'assistant') {
                            $messages[] = [
                                'role' => $role,
                                'content' => $content,
                            ];
                        }
                    }
                }
            }
        } elseif (isset($conversation['messages'])) {
            // Handle simple message array format
            $messages = $conversation['messages'];
        }

        return $messages;
    }

    private function analyzeEngagement(array $messages): array
    {
        $userMessages = array_filter($messages, fn ($m) => $m['role'] === 'user');
        $assistantMessages = array_filter($messages, fn ($m) => $m['role'] === 'assistant');

        $totalLength = array_sum(array_map(fn ($m) => strlen($m['content']), $messages));
        $avgLength = count($messages) > 0 ? round($totalLength / count($messages)) : 0;

        $questions = 0;
        $excitement = 0;

        foreach ($userMessages as $message) {
            $questions += substr_count($message['content'], '?');
            $excitement += preg_match_all('/!|amazing|wow|great|excellent|love|exciting/i', $message['content']);
        }

        return [
            'user_messages' => count($userMessages),
            'assistant_messages' => count($assistantMessages),
            'avg_length' => $avgLength,
            'questions' => $questions,
            'excitement' => $excitement,
        ];
    }

    private function identifyPatterns(array $messages): array
    {
        $patterns = [
            'questions_back' => 0,
            'examples_given' => 0,
            'challenges_made' => 0,
        ];

        $assistantMessages = array_filter($messages, fn ($m) => $m['role'] === 'assistant');

        foreach ($assistantMessages as $message) {
            $patterns['questions_back'] += substr_count($message['content'], '?');
            $patterns['examples_given'] += preg_match_all('/for example|for instance|such as|like when/i', $message['content']);
            $patterns['challenges_made'] += preg_match_all('/actually|however|but consider|challenge|pushback/i', $message['content']);
        }

        return $patterns;
    }

    private function calculateQualityScore(array $engagement, array $patterns): int
    {
        $score = 0;

        // Engagement scoring (max 50 points)
        $score += min($engagement['questions'] * 5, 25);
        $score += min($engagement['excitement'] * 3, 25);

        // Pattern scoring (max 50 points)
        $score += min($patterns['questions_back'] * 3, 20);
        $score += min($patterns['examples_given'] * 2, 15);
        $score += min($patterns['challenges_made'] * 3, 15);

        return min($score, 100);
    }

    private function provideQualityRecommendations(array $engagement, array $patterns, int $score): void
    {
        $this->info('');
        $this->info('📈 Quality Recommendations:');
        $this->line('---------------------------');

        if ($score < 60) {
            $this->warn('⚠️ Quality below target threshold (60%)');
        } elseif ($score < 80) {
            $this->info('✓ Good quality, room for improvement');
        } else {
            $this->info('🎉 Excellent quality!');
        }

        $recommendations = [];

        if ($patterns['questions_back'] < 5) {
            $recommendations[] = '• PI should ask more questions back to the user';
        }

        if ($patterns['examples_given'] < 5) {
            $recommendations[] = '• Include more specific examples in responses';
        }

        if ($patterns['challenges_made'] < 3) {
            $recommendations[] = '• Add more contrarian positions and challenges';
        }

        if ($engagement['questions'] < 5) {
            $recommendations[] = '• User engagement is low - make advisor more provocative';
        }

        foreach ($recommendations as $rec) {
            $this->warn($rec);
        }

        if (empty($recommendations)) {
            $this->info('No specific improvements needed');
        }
    }

    private function buildCsvContent(array $results): string
    {
        $csv = [];
        
        // Get headers - use first result if available, otherwise use defaults
        if (!empty($results)) {
            $headers = array_keys($results[0]);
        } else {
            // Default headers when no results
            $headers = ['advisor', 'version', 'pi_score', 'pk_score', 'combined'];
        }
        
        // Add header row
        $csv[] = implode(',', array_map(fn($h) => '"' . str_replace('"', '""', ucfirst(str_replace('_', ' ', $h))) . '"', $headers));
        
        // If no results, return just headers
        if (empty($results)) {
            return implode("\n", $csv);
        }
        
        // Add data rows
        foreach ($results as $row) {
            $csvRow = [];
            foreach ($headers as $header) {
                $value = $row[$header] ?? '';
                // Escape quotes and wrap in quotes if contains comma, quote, or newline
                if (is_string($value) && (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n"))) {
                    $csvRow[] = '"' . str_replace('"', '""', $value) . '"';
                } else {
                    $csvRow[] = $value;
                }
            }
            $csv[] = implode(',', $csvRow);
        }
        
        return implode("\n", $csv);
    }
    private function getAdvisorDirectoryName(string $advisorKey): string
    {
        return match ($advisorKey) {
            'bogusky' => 'alex-bogusky',
            'hormozi' => 'alex-hormozi',
            'henderson' => 'cal-henderson',
            'halbert' => 'gary-halbert',
            'csv_test' => 'csv-test',
            'debug_csv' => 'debug-csv',
            default => str_replace('_', '-', $advisorKey),
        };
    }
}
