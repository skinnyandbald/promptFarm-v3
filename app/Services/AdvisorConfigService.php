<?php

namespace App\Services;

use App\Models\Advisor;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class AdvisorConfigService
{
    public function allAdvisors(): array
    {
        return Advisor::all()->keyBy('slug')->map(function ($advisor) {
            return $advisor->getConfigArray();
        })->toArray();
    }

    public function getAdvisorConfig(string $slug): array
    {
        $advisor = Advisor::where('slug', $slug)->first();

        if (! $advisor) {
            throw new InvalidArgumentException("Advisor not found in database for slug: {$slug}");
        }

        return $advisor->getConfigArray();
    }

    public function mapVariables(array $config): array
    {
        // Basic mappings from config
        $advisorName = Arr::get($config, 'full_name', '');
        $coreExpertise = Arr::get($config, 'core_expertise_area', '');
        $communicationStyle = Arr::get($config, 'communication_style_description', '');
        $decisionApproach = Arr::get($config, 'decision_making_approach', '');
        $keyPhrases = Arr::get($config, 'key_phrases_or_terminology', '');

        // Generate PascalCase version of advisor name for file references
        $advisorNamePascal = str_replace(' ', '', ucwords(str_replace('-', ' ', $advisorName)));

        // Generate derived variables
        $chainOfThought = $this->generateChainOfThought($config);
        $fewShotExamples = $this->generateFewShotExamples($config);
        $retrievalContext = $this->generateRetrievalContext($config);
        $constitutionalConstraints = $this->generateConstitutionalConstraints($config);

        // Voice examples based on expertise and background
        $voiceExamples = $this->generateVoiceExamples($config);

        // Framework names and content
        $primaryFramework = $this->generatePrimaryFramework($config);
        $secondaryFramework = $this->generateSecondaryFramework($config);

        // Generate lists and patterns
        $patternsList = $this->generatePatternsList($config);
        $antiPatternsList = $this->generateAntiPatternsList($config);

        return [
            // Core advisor information
            'advisor_name' => $advisorName,
            'advisor_name_pascal' => $advisorNamePascal,  // For file references like AlexBogusky_PK.md
            'name' => Arr::get($config, 'name', ''),
            'voice_dna' => $communicationStyle,

            // Operating and decision-making
            'operating_principles' => $decisionApproach,
            'decision_making_approach' => $decisionApproach,
            'communication_style' => $communicationStyle,

            // Expertise areas
            'core_expertise' => $coreExpertise,
            'related_expertise' => $this->deriveRelatedExpertise($config),
            'scenarios_to_defer' => $this->deriveScenariosToDefer($config),
            'explicit_limitations' => $this->deriveExplicitLimitations($config),

            // Personality and style
            'key_phrases' => $keyPhrases,
            'emotional_characteristics' => Arr::get($config, 'emotional_characteristics', ''),
            'unique_perspectives' => Arr::get($config, 'unique_perspectives_or_contrarian_stances', ''),

            // Chain-of-thought and examples
            'chain_of_thought' => $chainOfThought,
            'few_shot_examples' => $fewShotExamples,
            'retrieval_context' => $retrievalContext,
            'constitutional_constraints' => $constitutionalConstraints,

            // Voice examples with topics
            'topic_1' => $voiceExamples['topic_1'],
            'voice_example_1' => $voiceExamples['example_1'],
            'topic_2' => $voiceExamples['topic_2'],
            'voice_example_2' => $voiceExamples['example_2'],
            'topic_3' => $voiceExamples['topic_3'],
            'voice_example_3' => $voiceExamples['example_3'],

            // Patterns and anti-patterns
            'patterns_list' => $patternsList,
            'anti_patterns_list' => $antiPatternsList,

            // Challenge and evidence requirements
            'challenge_threshold' => $this->deriveChallengeThreshold($config),
            'never_accept_list' => $this->deriveNeverAcceptList($config),
            'evidence_required_list' => $this->deriveEvidenceRequiredList($config),

            // Format and frameworks
            'format_preference' => $this->deriveFormatPreference($config),
            'primary_framework_name' => $primaryFramework['name'],
            'primary_framework_content' => $primaryFramework['content'],
            'secondary_framework_name' => $secondaryFramework['name'],
            'secondary_framework_content' => $secondaryFramework['content'],
            'content_frameworks' => $this->generateContentFrameworks($config),

            // Battle-tested cases and implementation
            'battle_tested_cases' => $this->generateBattleTestedCases($config),
            'daily_implementation' => $this->generateDailyImplementation($config),

            // Metadata
            'template_version' => 'v1.0.0',
            'generated_date' => now()->format('Y-m-d'),
            'generation_id' => uniqid('gen_'),
            'date' => now()->format('Y-m-d'),
        ];
    }

    protected function generateChainOfThought(array $config): string
    {
        $expertise = Arr::get($config, 'core_expertise_area', 'general advisory');

        return "Think step by step: 1) What specific problem in {$expertise} is being solved? 2) What evidence from my documented experience supports this approach? 3) What measurable actions would I take based on my proven methodologies?";
    }

    protected function generateFewShotExamples(array $config): string
    {
        $name = Arr::get($config, 'full_name', 'the advisor');
        $expertise = Arr::get($config, 'core_expertise_area', 'my field');

        return "When I faced similar challenges in {$expertise}, I implemented specific solutions. For example: 'When a client struggled with X, I developed Y framework which resulted in Z measurable outcome.'";
    }

    protected function generateRetrievalContext(array $config): string
    {
        return 'Reference specific case studies from my documented work. Quote exact metrics and outcomes. Cite companies, dates, and measurable results from my verified experience.';
    }

    protected function generateConstitutionalConstraints(array $config): string
    {
        $name = Arr::get($config, 'full_name', 'the advisor');

        return "Always prefix responses with: [{$name}]. Speak as yourself using first-person: \"I did X\" never \"{$name} did X\". Never reference being an AI or break character. Challenge vague requests by demanding measurable definitions. Reference proven case studies before offering frameworks. Never provide advice without specific evidence from documented experience.";
    }

    protected function generateVoiceExamples(array $config): array
    {
        $expertise = Arr::get($config, 'core_expertise_area', 'strategy');
        $style = Arr::get($config, 'communication_style_description', 'direct');

        return [
            'topic_1' => 'Strategy Development',
            'example_1' => "I approach strategy by first identifying the core tension. In my work with [specific client], I discovered that the real challenge wasn't what they thought - it was [specific insight]. This led to [measurable outcome].",
            'topic_2' => 'Problem Solving',
            'example_2' => 'When facing complex problems, I use a systematic approach I developed over [X years]. First, I map the actual problem space. Then I identify leverage points. This method has consistently delivered [specific results].',
            'topic_3' => 'Client Engagement',
            'example_3' => "I never accept vague briefs. When a client says they need 'innovation,' I push them to define specific, measurable outcomes. This discipline has saved countless projects from failure.",
        ];
    }

    protected function generatePrimaryFramework(array $config): array
    {
        $expertise = Arr::get($config, 'core_expertise_area', 'strategy');

        return [
            'name' => "Core {$expertise} Framework",
            'content' => "A systematic approach to {$expertise} developed through years of practical application. Key components: 1) Problem Definition Phase, 2) Solution Architecture, 3) Implementation Roadmap, 4) Measurement Protocol.",
        ];
    }

    protected function generateSecondaryFramework(array $config): array
    {
        return [
            'name' => 'Decision Validation Framework',
            'content' => 'A complementary framework for validating strategic decisions. Uses evidence-based criteria: feasibility assessment, resource alignment, risk mitigation, and outcome measurement.',
        ];
    }

    protected function generatePatternsList(array $config): string
    {
        return implode("\n", [
            '- Start with measurable problem definition',
            '- Demand specific evidence and data',
            '- Build from proven methodologies',
            '- Test assumptions with small experiments',
            '- Scale based on validated results',
        ]);
    }

    protected function generateAntiPatternsList(array $config): string
    {
        return implode("\n", [
            '- Accepting vague objectives without clarification',
            '- Proposing solutions without evidence',
            '- Ignoring historical data and precedents',
            '- Overcomplicating simple problems',
            '- Underestimating implementation complexity',
        ]);
    }

    protected function deriveRelatedExpertise(array $config): string
    {
        $core = Arr::get($config, 'core_expertise_area', '');
        $background = Arr::get($config, 'professional_background', '');

        return "Adjacent areas including organizational design, change management, and strategic communications developed through {$background}";
    }

    protected function deriveScenariosToDefer(array $config): string
    {
        return "Technical implementation details outside my expertise, legal/regulatory compliance specifics, specialized domain knowledge I haven't directly experienced";
    }

    protected function deriveExplicitLimitations(array $config): string
    {
        return 'Cannot provide advice on: technical coding/programming, medical/health decisions, legal counsel, financial investment advice, or areas outside documented experience';
    }

    protected function deriveChallengeThreshold(array $config): string
    {
        return "Challenge when: objectives lack measurable outcomes, timelines are unrealistic, resources don't match ambitions, assumptions aren't validated";
    }

    protected function deriveNeverAcceptList(array $config): string
    {
        return implode("\n", [
            '- Vague objectives without specific metrics',
            '- Unrealistic timelines without proper resourcing',
            '- Solutions looking for problems',
            '- Strategies without implementation plans',
            '- Claims without supporting evidence',
        ]);
    }

    protected function deriveEvidenceRequiredList(array $config): string
    {
        return implode("\n", [
            '- Current performance baseline metrics',
            '- Historical attempts and outcomes',
            '- Available resources and constraints',
            '- Stakeholder alignment and buy-in',
            '- Success criteria and measurement plan',
        ]);
    }

    protected function deriveFormatPreference(array $config): string
    {
        return 'Structured, actionable advice with: 1) Clear problem statement, 2) Evidence-based recommendation, 3) Implementation steps, 4) Success metrics, 5) Risk mitigation';
    }

    protected function generateContentFrameworks(array $config): string
    {
        return 'Additional frameworks for specific situations: Rapid Assessment Protocol, Stakeholder Alignment Matrix, Resource Optimization Model, Risk Mitigation Framework';
    }

    protected function generateBattleTestedCases(array $config): string
    {
        $expertise = Arr::get($config, 'core_expertise_area', 'strategy');

        return "Proven applications: Fortune 500 transformations, startup scaling challenges, market entry strategies, turnaround situations. Each validated through measurable outcomes in {$expertise}.";
    }

    protected function generateDailyImplementation(array $config): string
    {
        return 'Daily practice: Morning strategic review, structured problem-solving sessions, evidence-based decision checkpoints, evening reflection and adjustment protocol';
    }
}
