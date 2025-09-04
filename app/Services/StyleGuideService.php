<?php

namespace App\Services;

class StyleGuideService
{
    private array $config;
    
    public function __construct()
    {
        $this->config = config('style-guide');
    }

    /**
     * Generate system prompt constraints based on style guide rules
     */
    public function generateSystemPromptConstraints(): string
    {
        if (!$this->config['enabled']) {
            return '';
        }

        $constraints = [];
        
        // Add forbidden pattern constraints
        if (!empty($this->config['forbidden_patterns'])) {
            $constraints[] = "## Anti-AI Language Constraints";
            $constraints[] = "NEVER use these artificial phrases:";
            $forbiddenList = implode(', ', array_slice($this->config['forbidden_patterns'], 0, 20));
            $constraints[] = "Forbidden: {$forbiddenList}";
            $constraints[] = "";
        }

        // Add structure rules
        if ($this->config['structure_rules']['avoid_numbered_lists']) {
            $constraints[] = "- Write in natural paragraphs, never numbered lists";
        }
        
        if ($this->config['structure_rules']['avoid_bullet_points']) {
            $constraints[] = "- Avoid bullet points and formal structure";
        }

        if ($this->config['structure_rules']['prefer_short_sentences']) {
            $constraints[] = "- Use short, conversational sentences";
        }

        // Add authenticity requirements
        if ($this->config['authenticity_rules']['require_contractions']) {
            $constraints[] = "- Use contractions (I'm, don't, can't) for natural flow";
        }

        if ($this->config['authenticity_rules']['require_specific_examples']) {
            $constraints[] = "- Always use specific examples, never abstract concepts";
        }

        return implode("\n", $constraints);
    }

    /**
     * Analyze text for AI language patterns
     */
    public function analyzeText(string $text): array
    {
        $violations = [];
        $score = 0;
        
        // Check for forbidden patterns
        foreach ($this->config['forbidden_patterns'] as $pattern) {
            if (stripos($text, $pattern) !== false) {
                $violations[] = [
                    'type' => 'forbidden_pattern',
                    'pattern' => $pattern,
                    'severity' => 'high',
                    'penalty' => $this->config['scoring']['forbidden_pattern_penalty']
                ];
                $score += $this->config['scoring']['forbidden_pattern_penalty'];
            }
        }

        // Check for warning patterns
        foreach ($this->config['warning_patterns'] as $pattern) {
            if (stripos($text, $pattern) !== false) {
                $violations[] = [
                    'type' => 'warning_pattern', 
                    'pattern' => $pattern,
                    'severity' => 'medium',
                    'penalty' => $this->config['scoring']['warning_pattern_penalty']
                ];
                $score += $this->config['scoring']['warning_pattern_penalty'];
            }
        }

        // Check structure violations
        $structureViolations = $this->checkStructureViolations($text);
        foreach ($structureViolations as $violation) {
            $violations[] = $violation;
            $score += $violation['penalty'];
        }

        // Check authenticity bonuses
        $authenticityBonuses = $this->checkAuthenticityBonuses($text);
        foreach ($authenticityBonuses as $bonus) {
            $violations[] = $bonus; // Track positive signals too
            $score += $bonus['bonus'];
        }

        return [
            'score' => $score,
            'violations' => $violations,
            'total_violations' => count(array_filter($violations, fn($v) => ($v['penalty'] ?? 0) < 0)),
            'authenticity_signals' => count(array_filter($violations, fn($v) => ($v['bonus'] ?? 0) > 0)),
            'recommendation' => $this->getRecommendation($score, $violations)
        ];
    }

    /**
     * Check for structure violations
     */
    private function checkStructureViolations(string $text): array
    {
        $violations = [];
        
        // Check for numbered lists
        if ($this->config['structure_rules']['avoid_numbered_lists']) {
            if (preg_match('/^\s*\d+\.\s/m', $text)) {
                $violations[] = [
                    'type' => 'structure_violation',
                    'pattern' => 'numbered_list',
                    'severity' => 'medium',
                    'penalty' => $this->config['scoring']['structure_violation_penalty']
                ];
            }
        }

        // Check for bullet points  
        if ($this->config['structure_rules']['avoid_bullet_points']) {
            if (preg_match('/^\s*[-*•]\s/m', $text)) {
                $violations[] = [
                    'type' => 'structure_violation',
                    'pattern' => 'bullet_points',
                    'severity' => 'medium', 
                    'penalty' => $this->config['scoring']['structure_violation_penalty']
                ];
            }
        }

        // Check paragraph length
        $paragraphs = explode("\n\n", $text);
        foreach ($paragraphs as $paragraph) {
            $wordCount = str_word_count($paragraph);
            if ($wordCount > $this->config['structure_rules']['max_paragraph_length']) {
                $violations[] = [
                    'type' => 'structure_violation',
                    'pattern' => 'long_paragraph',
                    'severity' => 'low',
                    'penalty' => $this->config['scoring']['structure_violation_penalty'] / 2
                ];
            }
        }

        return $violations;
    }

    /**
     * Check for authenticity bonuses
     */
    private function checkAuthenticityBonuses(string $text): array
    {
        $bonuses = [];

        // Check for contractions
        if ($this->config['authenticity_rules']['require_contractions']) {
            $contractionCount = preg_match_all("/\b\w+'\w+\b/", $text);
            if ($contractionCount > 2) {
                $bonuses[] = [
                    'type' => 'authenticity_bonus',
                    'pattern' => 'contractions',
                    'severity' => 'positive',
                    'bonus' => $this->config['scoring']['authenticity_bonus']
                ];
            }
        }

        // Check for personal pronouns (I, my, we, our)
        if ($this->config['authenticity_rules']['encourage_personal_pronouns']) {
            $pronounCount = preg_match_all('/\b(I|my|we|our|me|us)\b/i', $text);
            if ($pronounCount > 5) {
                $bonuses[] = [
                    'type' => 'authenticity_bonus', 
                    'pattern' => 'personal_pronouns',
                    'severity' => 'positive',
                    'bonus' => $this->config['scoring']['authenticity_bonus']
                ];
            }
        }

        return $bonuses;
    }

    /**
     * Get recommendation based on analysis
     */
    private function getRecommendation(int $score, array $violations): string
    {
        $totalPenalty = abs($score);
        $threshold = $this->config['integration']['violation_threshold'];

        if ($totalPenalty >= $threshold) {
            return 'REJECT - Too many AI language patterns detected';
        }

        if ($totalPenalty > $threshold / 2) {
            return 'REVISE - Multiple AI patterns found, needs human voice improvement';
        }

        if ($totalPenalty > 0) {
            return 'MINOR - Few AI patterns detected, generally good human voice';
        }

        return 'EXCELLENT - Strong human voice, minimal AI patterns';
    }

    /**
     * Check if style guide is enabled
     */
    public function isEnabled(): bool
    {
        return $this->config['enabled'];
    }

    /**
     * Get violation threshold for auto-rejection
     */
    public function getViolationThreshold(): int
    {
        return $this->config['integration']['violation_threshold'];
    }

    /**
     * Generate feedback message for violations
     */
    public function generateFeedback(array $analysis): string
    {
        if (empty($analysis['violations'])) {
            return "✅ Excellent human voice - no AI language patterns detected.";
        }

        $feedback = [];
        $feedback[] = "🔍 Style Guide Analysis:";
        $feedback[] = "Score: {$analysis['score']} | Violations: {$analysis['total_violations']} | Authenticity: {$analysis['authenticity_signals']}";
        $feedback[] = "";

        // Group violations by type
        $grouped = [];
        foreach ($analysis['violations'] as $violation) {
            $type = $violation['type'];
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $violation;
        }

        foreach ($grouped as $type => $violations) {
            if ($type === 'authenticity_bonus') continue; // Skip positive signals in feedback
            
            $feedback[] = "❌ " . ucwords(str_replace('_', ' ', $type)) . ":";
            foreach (array_slice($violations, 0, 3) as $violation) { // Limit to 3 examples
                $feedback[] = "   • {$violation['pattern']}";
            }
            $feedback[] = "";
        }

        $feedback[] = "💡 {$analysis['recommendation']}";

        return implode("\n", $feedback);
    }
}