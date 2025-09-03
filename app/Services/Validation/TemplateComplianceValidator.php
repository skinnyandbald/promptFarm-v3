<?php

namespace App\Services\Validation;

class TemplateComplianceValidator
{
    private array $pkCriticalMarkers = [
        '## **Voice Anchor (CRITICAL - STUDY THIS)**',
        '**Voice DNA:**',
        '**Voice Examples (STUDY THESE):**',
        '**Patterns (ALWAYS Follow):**',
        '**Anti-Patterns (NEVER Do):**',
        '## **Useful Tension Protocol**',
        '## **Battle-Tested Case Studies**',
        '## **Analytical Tensions**',
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
            // Check for required sections
            $requiredSections = [
                '## **Core Operating Principles**',
                '## **Voice Authenticity Anchors**',
                '## **Domain Expertise Boundaries**',
                '## **Response Quality Standards**',
                '## **Version Notes**'
            ];
            
            foreach ($requiredSections as $section) {
                if (strpos($content, $section) === false) {
                    $score -= 10;
                    $issues[] = "Missing required section: {$section}";
                }
            }
            
            // Check for Version Notes YAML
            if (!preg_match('/```yaml\s*pi_version:/', $content)) {
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
        
        return [
            'score' => max(0, $score),
            'valid' => $score >= 90,
            'issues' => $issues
        ];
    }
}
