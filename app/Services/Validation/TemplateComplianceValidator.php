<?php

namespace App\Services\Validation;

class TemplateComplianceValidator
{
    private array $pkCriticalMarkers = [
        '## Voice Anchor (CRITICAL - STUDY THIS)',     // FIXED: Removed bold
        '**Voice DNA:**',                               // OK: Bold within content
        '**Voice Examples (STUDY THESE):**',           // OK: Bold within content
        '**Patterns (ALWAYS Follow):**',               // OK: Bold within content
        '**Anti-Patterns (NEVER Do):**',               // OK: Bold within content
        '## Useful Tension Protocol',                  // FIXED: Removed bold
        '## Battle-Tested Case Studies',               // FIXED: Removed bold
        '## Analytical Tensions',                      // FIXED: Removed bold
    ];

    public function validate(string $content, string $templateType = 'pk'): array
    {
        $score = 100;
        $issues = [];

        // Check for unreplaced mustache variables
        if (preg_match('/\{\{[^}]+\}\}/', $content)) {
            $score -= 30;
            $issues[] = 'Unreplaced template variables found';
        }

        // Check for remaining HTML comments
        if (preg_match('/<!--.*?-->/s', $content)) {
            $score -= 20;
            $issues[] = 'HTML comments not replaced';
        }

        // Check minimum content length
        $minLength = $templateType === 'pi' ? 3000 : 2000;
        if (strlen($content) < $minLength) {
            $score -= 20;
            $issues[] = "Content too short (minimum {$minLength} characters)";
        }

        // PI-specific checks
        if ($templateType === 'pi') {
            // Check for required sections (FIXED: Correct markdown syntax)
            $requiredSections = [
                '## Core Operating Principles',           // FIXED: Removed bold
                '## Voice Authenticity Anchors',         // FIXED: Removed bold
                '## Domain Expertise Boundaries',        // FIXED: Removed bold
                '## Response Quality Standards',          // FIXED: Removed bold
                '## Version Notes',                        // FIXED: Removed bold
            ];

            foreach ($requiredSections as $section) {
                if (strpos($content, $section) === false) {
                    $score -= 10;
                    $issues[] = "Missing required section: {$section}";
                }
            }

            // Check for Version Notes YAML
            if (! preg_match('/```yaml[\s\S]*?pi_version:/', $content)) {
                $score -= 10;
                $issues[] = 'Missing Version Notes YAML block';
            }
        }

        // PK-specific checks
        if ($templateType === 'pk') {
            // Check critical markers for PK
            foreach ($this->pkCriticalMarkers as $marker) {
                if (! str_contains($content, $marker)) {
                    $issues[] = "Missing: {$marker}";
                    $score -= 10;
                }
            }
        }

        // NEW: Add markdown hierarchy validation
        $markdownValidation = $this->validateMarkdownHierarchy($content);
        $score = min($score, $markdownValidation['score']);
        $issues = array_merge($issues, $markdownValidation['issues']);

        return [
            'score' => max(0, $score),
            'valid' => $score >= 90,
            'issues' => $issues,
        ];
    }

    private function validateMarkdownHierarchy(string $content): array
    {
        $issues = [];
        $score = 100;

        // Check for invalid header + bold combinations
        if (preg_match('/^#+\s*\*\*.*\*\*\s*$/m', $content)) {
            $score -= 20;
            $issues[] = 'Invalid markdown: Headers should not be combined with bold formatting';
        }

        // Check for proper H1 usage (should be only one)
        $h1Count = preg_match_all('/^#\s+/m', $content);
        if ($h1Count > 1) {
            $score -= 10;
            $issues[] = 'Multiple H1 headers found - should have only one main title';
        } elseif ($h1Count === 0) {
            $score -= 15;
            $issues[] = 'Missing H1 main title header';
        }

        // Check for proper header hierarchy (H2 before H3, etc.)
        $lines = explode("\n", $content);
        $lastHeaderLevel = 0;

        foreach ($lines as $lineNum => $line) {
            if (preg_match('/^(#+)\s/', $line, $matches)) {
                $currentLevel = strlen($matches[1]);

                // Skip more than 2 levels jump (H1 to H4 without H2, H3)
                if ($currentLevel > $lastHeaderLevel + 2) {
                    $score -= 5;
                    $issues[] = 'Header hierarchy skip on line '.($lineNum + 1).": H{$lastHeaderLevel} to H{$currentLevel}";
                }

                $lastHeaderLevel = $currentLevel;
            }
        }

        return ['score' => $score, 'issues' => $issues];
    }
}
