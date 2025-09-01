<?php

namespace App\Services;

/**
 * Improved PK Generation Prompt that enforces specificity
 * This replaces the generic prompt in AdvisorGenerationService
 */
class ImprovedPKPrompt
{
    /**
     * Build a high-quality PK generation prompt that forces specificity
     */
    public static function build(string $template, array $advisorData): string
    {
        $name = $advisorData['full_name'] ?? 'Unknown';
        $expertise = $advisorData['core_expertise_area'] ?? '';
        $background = $advisorData['background_description'] ?? '';
        $achievements = $advisorData['notable_achievements'] ?? '';
        $approach = $advisorData['decision_making_approach'] ?? '';
        $phrases = $advisorData['key_phrases_or_terminology'] ?? '';
        
        return <<<PROMPT
You are generating Project Knowledge (PK) for {$name}, a world-class expert in {$expertise}.

CONTEXT ABOUT {$name}:
- Background: {$background}
- Achievements: {$achievements}
- Approach: {$approach}
- Signature Phrases: {$phrases}

CRITICAL REQUIREMENTS - THESE ARE PASS/FAIL:

1. SPECIFICITY RULES (MANDATORY):
   - Every example MUST name a real company (Nike, Stripe, Domino's, etc.)
   - Every metric MUST be exact (22%, $8.50, 3.2x, Q3 2019)
   - Every framework MUST have a memorable name ("Enemy-First Formula", "60-Second Test")
   - Writing "[company]" or "significant results" = IMMEDIATE FAILURE

2. VOICE AUTHENTICITY:
   - Write in {$name}'s actual voice, not generic business speak
   - Short, punchy sentences (max 20 words average)
   - Include their signature phrases naturally
   - Sound like a real person, not a LinkedIn profile

3. CONCRETE EXAMPLES:
   - Name specific campaigns/projects they've done
   - Include real outcomes with numbers
   - Describe actual tools/methods they use
   - Make it feel like insider knowledge

4. ANTI-GENERIC VALIDATION:
   These phrases are BANNED:
   - "various clients" → Name them
   - "significant improvement" → Give the number
   - "innovative approach" → Describe the specific method
   - "industry best practices" → Name the actual practice
   - "strategic initiatives" → What specifically?

EXAMPLES OF WHAT I WANT:

BAD: "I helped a major retailer improve their marketing ROI"
GOOD: "I took Target's email revenue from $2M to $18M in 18 months using my 'Trigger-Stack' methodology"

BAD: "My framework helps companies identify opportunities"
GOOD: "The 'Enemy-First Formula' helped Domino's identify Pizza Hut's weakness (slow delivery) and exploit it for 40% market share"

BAD: "I use data-driven approaches"
GOOD: "I run 50+ A/B tests monthly. My 'Micro-Conversion Mapping' caught a 0.3% checkout bug worth $8M/year for Shopify"

TEMPLATE TO FILL:
{$template}

FINAL CHECKS:
- Count company names: Should be 5+ real companies mentioned
- Count specific numbers: Should be 10+ concrete metrics
- Check sentence length: Should average <20 words
- Scan for generic phrases: Should be ZERO

Now generate the PK. Be specific. Be memorable. Be real.
PROMPT;
    }
    
    /**
     * Validate that generated content meets specificity requirements
     */
    public static function validate(string $content): array
    {
        $issues = [];
        
        // Check for generic phrases that indicate low quality
        $genericPhrases = [
            'various clients',
            'significant improvement',
            'major company',
            'innovative approach',
            'best practices',
            'strategic initiatives',
            '[company]',
            '[client]',
            'leading organization',
            'fortune 500',  // without specific company name
        ];
        
        foreach ($genericPhrases as $phrase) {
            if (stripos($content, $phrase) !== false) {
                $issues[] = "Contains generic phrase: '{$phrase}'";
            }
        }
        
        // Check for specific company names (should have several)
        $knownCompanies = [
            'Apple', 'Google', 'Microsoft', 'Amazon', 'Facebook', 'Meta',
            'Stripe', 'Shopify', 'Slack', 'Uber', 'Airbnb', 'Netflix',
            'Target', 'Walmart', 'Nike', 'Coca-Cola', 'McDonald\'s',
            'Domino\'s', 'Pizza Hut', 'Starbucks', 'Tesla', 'SpaceX'
        ];
        
        $companyCount = 0;
        foreach ($knownCompanies as $company) {
            if (stripos($content, $company) !== false) {
                $companyCount++;
            }
        }
        
        if ($companyCount < 3) {
            $issues[] = "Too few specific company names (found {$companyCount}, need 3+)";
        }
        
        // Check for specific metrics (numbers with context)
        preg_match_all('/\d+\.?\d*\s*(%|x|\$|M|K|B)/', $content, $matches);
        $metricCount = count($matches[0]);
        
        if ($metricCount < 5) {
            $issues[] = "Too few specific metrics (found {$metricCount}, need 5+)";
        }
        
        // Calculate average sentence length
        $sentences = preg_split('/[.!?]+/', $content);
        $totalWords = 0;
        $sentenceCount = 0;
        
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (strlen($sentence) > 0) {
                $totalWords += str_word_count($sentence);
                $sentenceCount++;
            }
        }
        
        $avgWords = $sentenceCount > 0 ? $totalWords / $sentenceCount : 0;
        if ($avgWords > 25) {
            $issues[] = "Sentences too long (avg {$avgWords} words, target <25)";
        }
        
        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'metrics' => [
                'company_mentions' => $companyCount,
                'specific_metrics' => $metricCount,
                'avg_sentence_length' => round($avgWords, 1)
            ]
        ];
    }
}