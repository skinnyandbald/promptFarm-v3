<?php

namespace App\Services\Validation;

class TemplateComplianceValidator
{
    private array $criticalMarkers = [
        '## **Voice Anchor (CRITICAL - STUDY THIS)**',
        '**Voice DNA:**',
        '**Voice Examples (STUDY THESE):**',
        '**Patterns (ALWAYS Follow):**',
        '**Anti-Patterns (NEVER Do):**',
        '## **Useful Tension Protocol**',
        '## **Battle-Tested Case Studies**',
        '## **Analytical Tensions**',
    ];

    public function validate(string $content): array
    {
        $score = 100;
        $issues = [];

        // Check critical markers
        foreach ($this->criticalMarkers as $marker) {
            if (! str_contains($content, $marker)) {
                $issues[] = "Missing: {$marker}";
                $score -= 10;
            }
        }

        // Check for unreplaced variables
        if (preg_match('/\{\{[^}]+\}\}/', $content)) {
            $issues[] = 'Unreplaced mustache variables found';
            $score -= 20;
        }

        // Check for minimum content
        if (strlen($content) < 2000) {
            $issues[] = 'Content too short';
            $score -= 10;
        }

        return [
            'valid' => $score >= 90,
            'score' => max(0, $score),
            'issues' => $issues,
        ];
    }
}
