<?php

namespace App\Services;

use App\Models\Advisor;
use App\Models\PlayerContext;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * PlayerContext Integration Service
 *
 * Supports three stages of advisor deployment:
 * - Stage 1: Standalone advisor (PI + PK only, no player context)
 * - Stage 2: PlayerContext integration (PI-level primary, PK-level secondary)
 * - Stage 3: Council mode (future - not implemented yet)
 *
 * Based on research findings:
 * - PI-level integration is PRIMARY for Stage 2 (adapts communication style, response format)
 * - PK-level integration is SECONDARY for Stage 2 (filters relevance, not content removal)
 */
class PlayerContextService
{
    public function __construct(
        protected AdvisorGenerationService $generationService,
        protected LLMService $llmService
    ) {}

    /**
     * Create or update player context for a user
     */
    public function savePlayerContext(User $user, array $contextData): PlayerContext
    {
        Log::info('Saving player context', ['user_id' => $user->id]);

        $context = PlayerContext::updateOrCreate(
            ['user_id' => $user->id],
            [
                'background_story' => $contextData['background_story'] ?? '',
                'industry' => $contextData['industry'] ?? '',
                'business_type' => $contextData['business_type'] ?? '',
                'current_challenges' => $contextData['current_challenges'] ?? [],
                'goals' => $contextData['goals'] ?? [],
                'communication_style' => $contextData['communication_style'] ?? 'direct',
                'detail_level' => $contextData['detail_level'] ?? 'medium',
                'example_preference' => $contextData['example_preference'] ?? 'mixed',
                'framework_preferences' => $contextData['framework_preferences'] ?? [],
            ]
        );

        Log::info('Player context saved', ['context_id' => $context->id]);

        return $context;
    }

    /**
     * Generate a personalized advisor with OPTIONAL player context integration
     *
     * @param  Advisor  $advisor  The advisor to generate
     * @param  PlayerContext|null  $playerContext  Optional player context for personalization
     * @param  bool  $includePlayerContext  Explicit flag to enable player context integration (default: false)
     * @param  callable|null  $progressCallback  Optional progress callback
     */
    public function generatePersonalizedAdvisor(
        Advisor $advisor,
        ?PlayerContext $playerContext = null,
        bool $includePlayerContext = false,
        ?callable $progressCallback = null
    ): array {
        Log::info('Generating advisor', [
            'advisor' => $advisor->name,
            'include_player_context' => $includePlayerContext,
            'player_context_id' => $playerContext?->id,
        ]);

        if ($progressCallback) {
            $progressCallback(0, 'Starting advisor generation');
        }

        try {
            // Generate base advisor with standard generation service
            if ($progressCallback) {
                $progressCallback(20, 'Generating base advisor');
            }

            $baseAdvisor = $this->generationService->generateAdvisor($advisor);

            // Only personalize if explicitly requested AND player context exists
            if ($includePlayerContext && $playerContext) {
                // Enhance PI with player context
                if ($progressCallback) {
                    $progressCallback(40, 'Personalizing advisor instructions (PI)');
                }

                $personalizedPI = $this->personalizePI(
                    $baseAdvisor['pi_content'],
                    $advisor,
                    $playerContext
                );

                // Enhance PK with player context (lighter touch)
                if ($progressCallback) {
                    $progressCallback(60, 'Personalizing advisor knowledge (PK)');
                }

                $personalizedPK = $this->personalizePK(
                    $baseAdvisor['pk_content'],
                    $advisor,
                    $playerContext
                );

                // Create export package for ChatGPT with personalization
                if ($progressCallback) {
                    $progressCallback(80, 'Creating personalized ChatGPT export package');
                }

                $exportPackage = $this->createExportPackage(
                    $advisor,
                    $personalizedPI,
                    $personalizedPK,
                    $playerContext
                );

                // Update export tracking
                $playerContext->increment('exported_advisors_count');
                $playerContext->update(['last_advisor_export_at' => now()]);

                $contextSummary = $this->generateContextSummary($playerContext);
            } else {
                // Use base advisor without personalization
                $personalizedPI = $baseAdvisor['pi_content'];
                $personalizedPK = $baseAdvisor['pk_content'];

                // Create standard export package without player context
                if ($progressCallback) {
                    $progressCallback(80, 'Creating standard ChatGPT export package');
                }

                $exportPackage = $this->createStandardExportPackage(
                    $advisor,
                    $personalizedPI,
                    $personalizedPK
                );

                $contextSummary = null;
            }

            if ($progressCallback) {
                $progressCallback(100, 'Advisor ready for export');
            }

            Log::info('Advisor generated successfully', [
                'advisor' => $advisor->name,
                'personalized' => $includePlayerContext && $playerContext,
                'export_size' => strlen($exportPackage['full_export']),
            ]);

            return [
                'success' => true,
                'advisor_name' => $advisor->name,
                'personalized_pi' => $personalizedPI,
                'personalized_pk' => $personalizedPK,
                'export_package' => $exportPackage,
                'player_context_summary' => $contextSummary,
                'personalized' => $includePlayerContext && $playerContext,
                'generated_at' => now()->toIso8601String(),
            ];

        } catch (\Exception $e) {
            Log::error('Advisor generation failed', [
                'error' => $e->getMessage(),
                'advisor' => $advisor->name,
            ]);
            throw $e;
        }
    }

    /**
     * PI-Level Integration: Inject player context into advisor instructions
     */
    protected function personalizePI(string $piContent, Advisor $advisor, PlayerContext $context): string
    {
        Log::info('Personalizing PI with player context');

        // Build context injection prompt
        $prompt = $this->buildPIPersonalizationPrompt($piContent, $advisor, $context);

        try {
            $personalizedContent = $this->llmService->generateText($prompt, [
                'model' => config('ai-models.purposes.player_context'),
                'temperature' => 0.3,
                'max_tokens' => 6000,
            ]);

            Log::info('PI personalization complete', [
                'original_length' => strlen($piContent),
                'personalized_length' => strlen($personalizedContent),
            ]);

            return $personalizedContent;

        } catch (\Exception $e) {
            Log::warning('PI personalization failed, using base content', [
                'error' => $e->getMessage(),
            ]);

            return $piContent; // Fallback to original
        }
    }

    /**
     * PK-Level Integration: Filter and customize knowledge based on player context
     */
    protected function personalizePK(string $pkContent, Advisor $advisor, PlayerContext $context): string
    {
        Log::info('Personalizing PK with player context');

        // For PK, we do lighter personalization focused on relevance
        $prompt = $this->buildPKPersonalizationPrompt($pkContent, $advisor, $context);

        try {
            $personalizedContent = $this->llmService->generateText($prompt, [
                'model' => config('ai-models.purposes.player_context'),
                'temperature' => 0.2, // Lower temperature for PK modifications
                'max_tokens' => 8000,
            ]);

            Log::info('PK personalization complete', [
                'original_length' => strlen($pkContent),
                'personalized_length' => strlen($personalizedContent),
            ]);

            return $personalizedContent;

        } catch (\Exception $e) {
            Log::warning('PK personalization failed, using base content', [
                'error' => $e->getMessage(),
            ]);

            return $pkContent; // Fallback to original
        }
    }

    /**
     * Build PI personalization prompt
     */
    protected function buildPIPersonalizationPrompt(string $piContent, Advisor $advisor, PlayerContext $context): string
    {
        $background = $context->background_story;
        $industry = $context->industry;
        $businessType = $context->business_type;
        $challenges = json_encode($context->current_challenges);
        $goals = json_encode($context->goals);
        $communicationStyle = $context->communication_style;
        $detailLevel = $context->detail_level;

        return <<<PROMPT
You are personalizing advisor instructions for a specific player's context.

PLAYER CONTEXT:
- Background: {$background}
- Industry: {$industry}
- Business Type: {$businessType}
- Current Challenges: {$challenges}
- Goals: {$goals}
- Communication Style Preference: {$communicationStyle}
- Detail Level Preference: {$detailLevel}

CURRENT ADVISOR INSTRUCTIONS (PI):
{$piContent}

PERSONALIZATION TASKS:
1. **Communication Style Adjustment**:
   - Adapt the advisor's communication style to match the player's {$communicationStyle} preference
   - Adjust response detail level to {$detailLevel}
   - Maintain the advisor's core personality while adapting delivery

2. **Context Integration**:
   - Add specific references to the player's {$industry} industry where relevant
   - Include awareness of their {$businessType} business model
   - Reference their specific challenges when providing guidance

3. **Goal Alignment**:
   - Ensure advisor guidance aligns with player's stated goals
   - Prioritize advice relevant to their current challenges
   - Add specific success metrics relevant to their industry

4. **Example Customization**:
   - Replace or supplement generic examples with {$industry}-specific ones
   - Use terminology familiar to {$businessType} businesses
   - Reference challenges similar to what the player faces

IMPORTANT:
- Maintain the advisor's expertise and authority
- Keep all core advisor characteristics intact
- Only personalize delivery and context, not fundamental expertise
- Ensure the personalized version fits within ChatGPT context limits

Generate the personalized PI that maintains the advisor's expertise while being specifically tailored for this player:
PROMPT;
    }

    /**
     * Build PK personalization prompt
     */
    protected function buildPKPersonalizationPrompt(string $pkContent, Advisor $advisor, PlayerContext $context): string
    {
        $industry = $context->industry;
        $businessType = $context->business_type;
        $challenges = json_encode($context->current_challenges);
        $frameworkPreferences = json_encode($context->framework_preferences);
        $examplePreference = $context->example_preference;

        return <<<PROMPT
You are filtering and customizing advisor knowledge for a specific player's context.

PLAYER CONTEXT:
- Industry: {$industry}
- Business Type: {$businessType}
- Current Challenges: {$challenges}
- Framework Preferences: {$frameworkPreferences}
- Example Preference: {$examplePreference}

CURRENT ADVISOR KNOWLEDGE (PK):
{$pkContent}

PERSONALIZATION TASKS:
1. **Industry Relevance**:
   - Emphasize case studies and examples from {$industry} or similar industries
   - Highlight frameworks most applicable to {$businessType}
   - Maintain all content but adjust emphasis based on relevance

2. **Challenge-Focused Filtering**:
   - Prioritize knowledge sections most relevant to player's challenges
   - Add brief notes connecting advisor expertise to specific challenges
   - Don't remove content, but reorder for relevance

3. **Framework Alignment**:
   - If player has framework preferences, highlight those methodologies
   - Add connections between preferred frameworks and advisor's approach
   - Maintain advisor's methodology while showing alignment

4. **Example Customization**:
   - Based on {$examplePreference} preference, adjust example selection
   - For industry_specific: emphasize {$industry} examples
   - For general: maintain broad examples but add relevance notes
   - For mixed: balance both types

IMPORTANT:
- Do NOT remove any core knowledge or expertise
- Only adjust emphasis, ordering, and add relevance notes
- Maintain all battle-tested examples and specific metrics
- Keep the advisor's voice and authority intact

Generate the personalized PK that emphasizes relevance to this player while maintaining all advisor expertise:
PROMPT;
    }

    /**
     * Create STANDARD export package for ChatGPT deployment (Stage 1 - No player context)
     */
    protected function createStandardExportPackage(
        Advisor $advisor,
        string $pi,
        string $pk
    ): array {
        // Full export (PI + PK) - Stage 1 format
        $fullExport = "# {$advisor->name} - Advisor Profile\n\n";
        $fullExport .= "## Project Instructions (PI)\n\n{$pi}\n\n";
        $fullExport .= "---\n\n";
        $fullExport .= "## Project Knowledge (PK)\n\n{$pk}";

        // Condensed export (essential information within ChatGPT limits)
        $condensedExport = $this->createCondensedExport($advisor, $pi, $pk, null);

        // Setup instructions
        $setupInstructions = $this->generateSetupInstructions($advisor);

        return [
            'full_export' => $fullExport,
            'condensed_export' => $condensedExport,
            'setup_instructions' => $setupInstructions,
            'context_summary' => null,
            'export_metadata' => [
                'advisor_name' => $advisor->name,
                'stage' => 'Stage 1 - Standalone',
                'personalized_for' => null,
                'export_date' => now()->toIso8601String(),
                'full_size' => strlen($fullExport),
                'condensed_size' => strlen($condensedExport),
            ],
        ];
    }

    /**
     * Create PERSONALIZED export package for ChatGPT deployment (Stage 2 - With player context)
     */
    protected function createExportPackage(
        Advisor $advisor,
        string $personalizedPI,
        string $personalizedPK,
        PlayerContext $context
    ): array {
        $contextSummary = $this->generateContextSummary($context);

        // Full export (PI + PK) - Stage 2 format with context
        $fullExport = "# {$advisor->name} - Personalized Advisor\n\n";
        $fullExport .= "## Player Context Summary\n{$contextSummary}\n\n";
        $fullExport .= "---\n\n";
        $fullExport .= "## Project Instructions (PI)\n\n{$personalizedPI}\n\n";
        $fullExport .= "---\n\n";
        $fullExport .= "## Project Knowledge (PK)\n\n{$personalizedPK}";

        // Condensed export (essential information within ChatGPT limits)
        $condensedExport = $this->createCondensedExport($advisor, $personalizedPI, $personalizedPK, $contextSummary);

        // Setup instructions
        $setupInstructions = $this->generateSetupInstructions($advisor);

        return [
            'full_export' => $fullExport,
            'condensed_export' => $condensedExport,
            'setup_instructions' => $setupInstructions,
            'context_summary' => $contextSummary,
            'export_metadata' => [
                'advisor_name' => $advisor->name,
                'stage' => 'Stage 2 - PlayerContext',
                'personalized_for' => $context->user->name ?? 'User',
                'export_date' => now()->toIso8601String(),
                'full_size' => strlen($fullExport),
                'condensed_size' => strlen($condensedExport),
            ],
        ];
    }

    /**
     * Create condensed export for ChatGPT token limits
     */
    protected function createCondensedExport(
        Advisor $advisor,
        string $pi,
        string $pk,
        ?string $contextSummary
    ): string {
        // Extract most important sections from PI
        $essentialPI = $this->extractEssentialSections($pi, [
            'Core Operating Principles',
            'Communication Framework',
            'Primary Expertise',
        ]);

        // Extract most important sections from PK
        $essentialPK = $this->extractEssentialSections($pk, [
            'Battle-Tested Frameworks',
            'Key Case Studies',
            'Signature Methodologies',
        ]);

        $condensed = "# {$advisor->name} - Essential Advisor Profile\n\n";

        // Only include context if it exists (Stage 2)
        if ($contextSummary) {
            $condensed .= "## Context\n{$contextSummary}\n\n";
        }

        $condensed .= "## Core Instructions\n{$essentialPI}\n\n";
        $condensed .= "## Essential Knowledge\n{$essentialPK}";

        // Ensure it fits within reasonable ChatGPT limits (approximately 15k tokens ~ 60k characters)
        if (strlen($condensed) > 60000) {
            $condensed = mb_substr($condensed, 0, 60000)."\n\n[Content truncated for ChatGPT limits]";
        }

        return $condensed;
    }

    /**
     * Extract essential sections from content
     */
    protected function extractEssentialSections(string $content, array $sectionHeaders): string
    {
        $extracted = [];

        foreach ($sectionHeaders as $header) {
            $pattern = '/#{1,3}\s*'.preg_quote($header, '/').'.*?(?=#{1,3}\s|\z)/si';
            if (preg_match($pattern, $content, $matches)) {
                $extracted[] = trim($matches[0]);
            }
        }

        return implode("\n\n", $extracted);
    }

    /**
     * Generate context summary for advisor reference
     */
    public function generateContextSummary(PlayerContext $context): string
    {
        $summary = "**Player Profile:**\n";
        $summary .= "- Industry: {$context->industry}\n";
        $summary .= "- Business Type: {$context->business_type}\n";

        if (! empty($context->background_story)) {
            $summary .= '- Background: '.Str::limit($context->background_story, 200)."\n";
        }

        if (! empty($context->current_challenges)) {
            $challenges = is_array($context->current_challenges)
                ? implode(', ', array_slice($context->current_challenges, 0, 3))
                : $context->current_challenges;
            $summary .= "- Current Focus: {$challenges}\n";
        }

        if (! empty($context->goals)) {
            $goals = is_array($context->goals)
                ? implode(', ', array_slice($context->goals, 0, 2))
                : $context->goals;
            $summary .= "- Goals: {$goals}\n";
        }

        $summary .= "- Communication Style: {$context->communication_style}\n";
        $summary .= "- Detail Preference: {$context->detail_level}";

        return $summary;
    }

    /**
     * Generate ChatGPT setup instructions
     */
    protected function generateSetupInstructions(Advisor $advisor): string
    {
        return <<<INSTRUCTIONS
# ChatGPT Setup Instructions for {$advisor->name}

## Quick Setup (Recommended)
1. Open ChatGPT (Plus or Team subscription required for custom GPTs)
2. Click on "Explore GPTs" or "Create a GPT"
3. Choose "Create" to make a new custom GPT
4. In the "Configure" tab:
   - Name: {$advisor->name}
   - Description: Your personalized {$advisor->expertise_area} advisor
   - Instructions: Paste the exported advisor content (PI + PK sections)
5. Save and start using your personalized advisor

## Alternative Setup (For ChatGPT Conversations)
1. Start a new ChatGPT conversation
2. Paste the exported advisor content at the beginning
3. Add: "You are {$advisor->name}. Please acknowledge and follow the instructions and knowledge provided above."
4. The advisor will now respond according to the personalized profile

## Best Practices
- For best results, use the Full Export if under token limits
- Use Condensed Export if you encounter token limit errors
- Reference specific sections when asking questions
- Remind the advisor of your context when needed
- Save the conversation for future sessions

## Troubleshooting
- **Token Limit Error**: Use the Condensed Export instead
- **Advisor Not Following Instructions**: Remind it of its role and key principles
- **Generic Responses**: Reference specific methodologies or case studies from the PK
- **Context Lost**: Paste the Context Summary at the start of new conversations

## Tips for Maximum Value
1. Ask for specific frameworks: "Use your [Framework Name] to analyze..."
2. Request case studies: "Share a relevant battle-tested example..."
3. Seek contrarian views: "What would you challenge about this approach?"
4. Get actionable advice: "Give me specific next steps using your methodology"

Remember: This advisor is personalized for your specific context and challenges.
INSTRUCTIONS;
    }

    /**
     * Get player context for a user
     */
    public function getPlayerContext(User $user): ?PlayerContext
    {
        return PlayerContext::where('user_id', $user->id)->first();
    }

    /**
     * Get all personalized advisors for a player
     */
    public function getPersonalizedAdvisors(PlayerContext $context): array
    {
        // This would track which advisors have been personalized for this player
        // For now, return basic tracking info
        return [
            'total_exports' => $context->exported_advisors_count,
            'last_export' => $context->last_advisor_export_at,
            'player_id' => $context->id,
        ];
    }
}
