<?php

namespace App\Services\Validation;

use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

class AdvisorQualityService
{
    protected array $piRequiredSections = [
        '# Voice Authenticity Anchors',
        '# Core Operating Principles',
        '# Chain-of-Thought Conditioning',
        '# Few-Shot Behavioral Priming',
        '# Domain Expertise Boundaries',
        '# Response Quality Standards'
    ];

    protected array $pkRequiredSections = [
        '# Voice Anchor',
        '# Challenge & Acceptance Criteria',
        '# Communication Format Rules',
        '# Primary Framework',
        '# Secondary Framework',
        '# Battle-Tested Application'
    ];

    /**
     * Score PI content quality
     */
    public function scorePI(string $content): array
    {
        $score = 0;
        $maxScore = 100;
        $issues = [];
        $strengths = [];

        // Parse YAML frontmatter for validation rules
        $metadata = $this->extractMetadata($content);
        $validationRules = $metadata['validation'] ?? [];

        // Check structure (30 points)
        $structureResult = $this->validatePIStructure($content);
        if ($structureResult['valid']) {
            $score += 30;
            $strengths[] = 'All required sections present';
        } else {
            $score += max(0, 30 - (count($structureResult['missing']) * 5));
            foreach ($structureResult['missing'] as $missing) {
                $issues[] = "Missing section: {$missing}";
            }
        }

        // Check for remaining placeholders (20 points)
        $placeholderResult = $this->checkForRemainingPlaceholders($content);
        if ($placeholderResult['clean']) {
            $score += 20;
            $strengths[] = 'No remaining template placeholders';
        } else {
            $issues[] = "Found {$placeholderResult['count']} unsubstituted placeholders";
            $issues = array_merge($issues, array_map(fn($p) => "Unsubstituted: {{{{{$p}}}}}", $placeholderResult['placeholders']));
        }

        // Check for HTML comments (20 points)
        $htmlCommentResult = $this->checkForHTMLComments($content);
        if ($htmlCommentResult['clean']) {
            $score += 20;
            $strengths[] = 'All HTML comments properly processed';
        } else {
            $score += max(0, 20 - ($htmlCommentResult['count'] * 5));
            $issues[] = "Found {$htmlCommentResult['count']} unprocessed HTML comments";
        }

        // Check content depth (15 points)
        $depthResult = $this->analyzeContentDepth($content);
        $score += min(15, $depthResult['score'] * 15);
        if ($depthResult['score'] >= 0.8) {
            $strengths[] = 'Rich, detailed content';
        } else {
            $issues[] = "Content depth could be improved (score: {$depthResult['score']})";
        }

        // Check first-person voice usage (15 points)
        $voiceResult = $this->checkFirstPersonVoice($content);
        if ($voiceResult['proper']) {
            $score += 15;
            $strengths[] = 'Proper first-person voice maintained';
        } else {
            $score += max(0, 15 - ($voiceResult['violations'] * 3));
            $issues[] = "Found {$voiceResult['violations']} voice violations";
        }

        // Line count validation
        $lineCount = substr_count($content, "\n") + 1;
        $minLines = $validationRules['min_lines'] ?? 100;
        $maxLines = $validationRules['max_lines'] ?? 300;
        
        if ($lineCount < $minLines) {
            $issues[] = "Content too short: {$lineCount} lines (minimum: {$minLines})";
        } elseif ($lineCount > $maxLines) {
            $issues[] = "Content too long: {$lineCount} lines (maximum: {$maxLines})";
        } else {
            $strengths[] = "Appropriate length: {$lineCount} lines";
        }

        return [
            'score' => round($score),
            'maxScore' => $maxScore,
            'percentage' => round(($score / $maxScore) * 100),
            'valid' => $score >= 75,
            'issues' => $issues,
            'strengths' => $strengths,
            'lineCount' => $lineCount,
            'metadata' => [
                'sections' => $structureResult,
                'placeholders' => $placeholderResult,
                'htmlComments' => $htmlCommentResult,
                'contentDepth' => $depthResult,
                'voice' => $voiceResult
            ]
        ];
    }

    /**
     * Score PK content quality
     */
    public function scorePK(string $content): array
    {
        $score = 0;
        $maxScore = 100;
        $issues = [];
        $strengths = [];

        // Parse YAML frontmatter for validation rules
        $metadata = $this->extractMetadata($content);
        $validationRules = $metadata['validation'] ?? [];

        // Check structure (30 points)
        $structureResult = $this->validatePKStructure($content);
        if ($structureResult['valid']) {
            $score += 30;
            $strengths[] = 'All required sections present';
        } else {
            $score += max(0, 30 - (count($structureResult['missing']) * 5));
            foreach ($structureResult['missing'] as $missing) {
                $issues[] = "Missing section: {$missing}";
            }
        }

        // Check for remaining placeholders (20 points)
        $placeholderResult = $this->checkForRemainingPlaceholders($content);
        if ($placeholderResult['clean']) {
            $score += 20;
            $strengths[] = 'No remaining template placeholders';
        } else {
            $issues[] = "Found {$placeholderResult['count']} unsubstituted placeholders";
            $issues = array_merge($issues, array_map(fn($p) => "Unsubstituted: {{{{{$p}}}}}", $placeholderResult['placeholders']));
        }

        // Check for HTML comments (10 points - less critical for PK)
        $htmlCommentResult = $this->checkForHTMLComments($content);
        if ($htmlCommentResult['clean']) {
            $score += 10;
            $strengths[] = 'No HTML comments remaining';
        } else {
            $score += max(0, 10 - ($htmlCommentResult['count'] * 2));
            $issues[] = "Found {$htmlCommentResult['count']} HTML comments";
        }

        // Check content depth and specificity (25 points)
        $depthResult = $this->analyzeContentDepth($content);
        $score += min(25, $depthResult['score'] * 25);
        if ($depthResult['score'] >= 0.8) {
            $strengths[] = 'Comprehensive knowledge documentation';
        } else {
            $issues[] = "Knowledge depth could be improved (score: {$depthResult['score']})";
        }

        // Check for specific examples and cases (15 points)
        $examplesResult = $this->checkForExamples($content);
        if ($examplesResult['sufficient']) {
            $score += 15;
            $strengths[] = 'Good use of specific examples and cases';
        } else {
            $score += max(0, 15 - ((3 - $examplesResult['count']) * 5));
            $issues[] = "Need more specific examples (found: {$examplesResult['count']})";
        }

        // Line count validation
        $lineCount = substr_count($content, "\n") + 1;
        $minLines = $validationRules['min_lines'] ?? 150;
        $maxLines = $validationRules['max_lines'] ?? 500;
        
        if ($lineCount < $minLines) {
            $issues[] = "Content too short: {$lineCount} lines (minimum: {$minLines})";
        } elseif ($lineCount > $maxLines) {
            $issues[] = "Content too long: {$lineCount} lines (maximum: {$maxLines})";
        } else {
            $strengths[] = "Appropriate length: {$lineCount} lines";
        }

        return [
            'score' => round($score),
            'maxScore' => $maxScore,
            'percentage' => round(($score / $maxScore) * 100),
            'valid' => $score >= 80,
            'issues' => $issues,
            'strengths' => $strengths,
            'lineCount' => $lineCount,
            'metadata' => [
                'sections' => $structureResult,
                'placeholders' => $placeholderResult,
                'htmlComments' => $htmlCommentResult,
                'contentDepth' => $depthResult,
                'examples' => $examplesResult
            ]
        ];
    }

    /**
     * Validate PI structure
     */
    public function validatePIStructure(string $content): array
    {
        $missing = [];
        $found = [];

        foreach ($this->piRequiredSections as $section) {
            if (Str::contains($content, $section)) {
                $found[] = $section;
            } else {
                $missing[] = $section;
            }
        }

        return [
            'valid' => empty($missing),
            'missing' => $missing,
            'found' => $found
        ];
    }

    /**
     * Validate PK structure
     */
    public function validatePKStructure(string $content): array
    {
        $missing = [];
        $found = [];

        foreach ($this->pkRequiredSections as $section) {
            if (Str::contains($content, $section)) {
                $found[] = $section;
            } else {
                $missing[] = $section;
            }
        }

        return [
            'valid' => empty($missing),
            'missing' => $missing,
            'found' => $found
        ];
    }

    /**
     * Check for remaining placeholders
     */
    public function checkForRemainingPlaceholders(string $content): array
    {
        preg_match_all('/{{([^}]+)}}/', $content, $matches);
        
        return [
            'clean' => empty($matches[1]),
            'count' => count($matches[1]),
            'placeholders' => array_unique($matches[1])
        ];
    }

    /**
     * Check for HTML comments
     */
    protected function checkForHTMLComments(string $content): array
    {
        preg_match_all('/<!--[^>]+-->/', $content, $matches);
        
        return [
            'clean' => empty($matches[0]),
            'count' => count($matches[0]),
            'comments' => $matches[0]
        ];
    }

    /**
     * Analyze content depth
     */
    public function analyzeContentDepth(string $content): array
    {
        $score = 0;
        $factors = [];

        // Check word count
        $wordCount = str_word_count($content);
        if ($wordCount > 1000) {
            $score += 0.3;
            $factors['wordCount'] = 'good';
        } elseif ($wordCount > 500) {
            $score += 0.2;
            $factors['wordCount'] = 'adequate';
        } else {
            $score += 0.1;
            $factors['wordCount'] = 'minimal';
        }

        // Check for specific details (numbers, percentages, quotes)
        if (preg_match_all('/\d+%|\$[\d,]+|\d{4}/', $content) > 3) {
            $score += 0.2;
            $factors['specificity'] = 'high';
        } elseif (preg_match_all('/\d+/', $content) > 5) {
            $score += 0.1;
            $factors['specificity'] = 'moderate';
        }

        // Check for examples and case studies
        if (preg_match_all('/[Ff]or example|[Cc]ase study|[Ww]hen I|[Ii]n my experience/', $content) > 2) {
            $score += 0.3;
            $factors['examples'] = 'rich';
        } elseif (preg_match_all('/[Ff]or example|[Ww]hen/', $content) > 0) {
            $score += 0.15;
            $factors['examples'] = 'present';
        }

        // Check for structured content (lists, frameworks)
        if (preg_match_all('/^\s*[-*•]\s+/m', $content) > 10) {
            $score += 0.2;
            $factors['structure'] = 'well-structured';
        } elseif (preg_match_all('/^\s*[-*•]\s+/m', $content) > 5) {
            $score += 0.1;
            $factors['structure'] = 'structured';
        }

        return [
            'score' => min(1.0, $score),
            'factors' => $factors
        ];
    }

    /**
     * Check first-person voice usage
     */
    protected function checkFirstPersonVoice(string $content): bool
    {
        $violations = 0;
        
        // Check for third-person references that should be first-person
        $thirdPersonPatterns = [
            '/[Tt]he advisor would/',
            '/[Aa]dvisor\'s perspective/',
            '/[Ff]rom .+ point of view/',
            '/[Aa]s .+ might suggest/',
            '/[Tt]hey would say/'
        ];

        foreach ($thirdPersonPatterns as $pattern) {
            $violations += preg_match_all($pattern, $content);
        }

        // Check for proper first-person usage
        $firstPersonCount = preg_match_all('/\bI\s+(did|developed|created|built|found|discovered|learned)\b/i', $content);

        return [
            'proper' => $violations === 0 && $firstPersonCount > 3,
            'violations' => $violations,
            'firstPersonUsage' => $firstPersonCount
        ];
    }

    /**
     * Check for specific examples and cases
     */
    protected function checkForExamples(string $content): array
    {
        $patterns = [
            '/[Ww]hen I .+ with .+ (client|company|organization)/',
            '/[Ii]n my (work|experience) with/',
            '/[Ss]pecific (example|case|instance)/',
            '/\d+%\s+(increase|decrease|improvement|growth)/',
            '/[Rr]esulted in .+ (outcome|result|improvement)/'
        ];

        $count = 0;
        foreach ($patterns as $pattern) {
            $count += preg_match_all($pattern, $content);
        }

        return [
            'sufficient' => $count >= 3,
            'count' => $count
        ];
    }

    /**
     * Calculate overall quality score
     */
    public function calculateQualityScore(array $piScore, array $pkScore): array
    {
        $overallScore = ($piScore['percentage'] + $pkScore['percentage']) / 2;
        
        return [
            'overall' => round($overallScore),
            'pi' => $piScore['percentage'],
            'pk' => $pkScore['percentage'],
            'valid' => $piScore['valid'] && $pkScore['valid'],
            'recommendation' => $this->getRecommendation($overallScore)
        ];
    }

    /**
     * Get quality recommendation
     */
    protected function getRecommendation(float $score): string
    {
        if ($score >= 90) {
            return 'Excellent quality - ready for production';
        } elseif ($score >= 80) {
            return 'Good quality - minor improvements recommended';
        } elseif ($score >= 70) {
            return 'Acceptable quality - consider addressing issues';
        } elseif ($score >= 60) {
            return 'Below standard - significant improvements needed';
        } else {
            return 'Poor quality - major revision required';
        }
    }

    /**
     * Get detailed validation report
     */
    public function getValidationReport(array $piScore, array $pkScore): array
    {
        $overall = $this->calculateQualityScore($piScore, $pkScore);
        
        return [
            'summary' => [
                'overall_score' => $overall['overall'],
                'status' => $overall['valid'] ? 'PASSED' : 'FAILED',
                'recommendation' => $overall['recommendation']
            ],
            'pi' => [
                'score' => $piScore['score'],
                'percentage' => $piScore['percentage'],
                'issues' => $piScore['issues'],
                'strengths' => $piScore['strengths'],
                'lineCount' => $piScore['lineCount']
            ],
            'pk' => [
                'score' => $pkScore['score'],
                'percentage' => $pkScore['percentage'],
                'issues' => $pkScore['issues'],
                'strengths' => $pkScore['strengths'],
                'lineCount' => $pkScore['lineCount']
            ],
            'metadata' => [
                'validated_at' => now()->toIso8601String(),
                'validator_version' => '1.0.0'
            ]
        ];
    }

    /**
     * Extract metadata from YAML frontmatter
     */
    protected function extractMetadata(string $content): array
    {
        if (!Str::startsWith($content, '---')) {
            return [];
        }

        $parts = explode('---', $content, 3);
        if (count($parts) < 3) {
            return [];
        }

        try {
            return Yaml::parse($parts[1]) ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }
}