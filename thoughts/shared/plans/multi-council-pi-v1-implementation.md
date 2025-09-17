# Multi-Council PI v1 Implementation Plan

## Overview

Build the first version of a multi-advisor council PI system that generates a single, compact PI file (under 8000 characters) capable of orchestrating multiple advisor voices using conditional routing and the proven Pure Voice Anchor strategy. This system will integrate with existing PK files while maintaining the voice authenticity that scored 85-95% in testing.

## Current State Analysis

### Existing Foundation:
- **Single-advisor system** produces PIs averaging 7152-7823 chars with variable quality (68-94%)
- **Reference BHCH hybrid** at ~3000 chars demonstrates effective council routing
- **B version strategy** (Pure Voice Anchor) achieves 85-95% AI embodiment scores
- **Quality infrastructure** includes multi-layer validation and A/B/C testing framework
- **Anti-AI style guide** with 85+ forbidden patterns prevents generic responses

### Key Discoveries:
- Character budget is critical: must fit 4+ advisors in 8000 chars
- Voice anchors (3-4 sentences) are more effective than structural rules
- Conditional routing (`IF/THEN` logic) supported by ChatGPT
- Mode tags enable dynamic advisor switching
- PK files can be referenced, not duplicated

### Current Advisors:
1. **Alex Bogusky** - Cultural disruption, PR-able ideas
2. **Gary Halbert** - Direct response copy, offers
3. **Cal Henderson** - Technical architecture, scaling
4. **Alex Hormozi** - Business models, profit optimization

## Desired End State

A production-ready `php artisan pi:generate-council` command that creates:

1. **Single council PI file** under 8000 characters
2. **Dynamic advisor routing** using ChatGPT conditional logic
3. **Pure Voice Anchor integration** for authentic personalities
4. **Quality validation** scoring >80% on AI embodiment
5. **A/B testing capability** for continuous optimization

### Verification Criteria:
- Council PI stays under 8000 characters with 4 advisors
- Quality score consistently >80% on AI embodiment metrics
- Each advisor maintains distinct voice (verified via testing)
- Mode switching works reliably in ChatGPT
- Anti-AI patterns successfully blocked

## What We're NOT Doing

- Modifying existing single-advisor generation system
- Creating new PK files (reference existing ones)
- Building multi-model orchestration (ChatGPT only for v1)
- Implementing real-time advisor selection
- Creating web UI for council management

## Implementation Approach

Use existing PI generation infrastructure as foundation, implement Pure Voice Anchor strategy from B version testing, apply aggressive character optimization, and integrate with current quality validation systems.

## Phase 1: Core Council Generation System

### Overview
Create the foundational command and service architecture for multi-advisor council PI generation.

### Changes Required:

#### 1. Console Command
**File**: `app/Console/Commands/PIGenerateCouncil.php`
**Changes**: New command with council-specific options

```php
<?php

namespace App\Console\Commands;

use App\Services\CouncilPIGenerationService;
use App\Services\Validation\AIEmbodimentQualityScorer;
use App\Models\Advisor;
use Illuminate\Console\Command;

class PIGenerateCouncil extends Command
{
    protected $signature = 'pi:generate-council
                           {--advisors=all : Comma-separated advisor slugs or "all"}
                           {--style=voice-anchor : Generation style (voice-anchor|hybrid|compressed)}
                           {--anti-ai=on : Anti-AI style guide enforcement}
                           {--test-mode : Generate multiple variations for testing}
                           {--export-path= : Custom export directory}';

    protected $description = 'Generate a multi-advisor council PI under 8000 characters';

    public function handle(
        CouncilPIGenerationService $service,
        AIEmbodimentQualityScorer $scorer
    ): int {
        $this->info('🚀 Starting council PI generation...');

        // Parse advisors
        $advisorSlugs = $this->option('advisors') === 'all'
            ? ['alex-bogusky', 'gary-halbert', 'cal-henderson', 'alex-hormozi']
            : explode(',', $this->option('advisors'));

        // Validate advisors exist
        $advisors = Advisor::whereIn('slug', $advisorSlugs)->get();
        if ($advisors->count() !== count($advisorSlugs)) {
            $this->error('Some advisors not found. Available: ' .
                Advisor::pluck('slug')->join(', '));
            return Command::FAILURE;
        }

        // Generate council PI
        $options = [
            'style' => $this->option('style'),
            'anti_ai' => $this->option('anti-ai') === 'on',
            'test_mode' => $this->option('test-mode'),
            'export_path' => $this->option('export-path'),
        ];

        $result = $service->generateCouncilPI($advisors, $options);

        if (!$result['success']) {
            $this->error("Generation failed: {$result['error']}");
            return Command::FAILURE;
        }

        // Display results
        $this->info("✅ Council PI generated successfully!");
        $this->table(['Metric', 'Value'], [
            ['File Path', $result['file_path']],
            ['Character Count', number_format($result['char_count']) . ' / 8000'],
            ['Advisor Count', $result['advisor_count']],
            ['Quality Score', $result['quality_score'] . '%'],
            ['Voice Score', $result['voice_score'] . '%'],
            ['Anti-AI Score', $result['anti_ai_score'] . '%'],
        ]);

        // Character budget breakdown
        if ($this->output->isVerbose()) {
            $this->info("\nCharacter Budget Breakdown:");
            foreach ($result['char_breakdown'] as $section => $chars) {
                $this->line("  {$section}: {$chars} chars");
            }
        }

        return Command::SUCCESS;
    }
}
```

#### 2. Council PI Generation Service
**File**: `app/Services/CouncilPIGenerationService.php`
**Changes**: Core service with character optimization

```php
<?php

namespace App\Services;

use App\Models\Advisor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class CouncilPIGenerationService
{
    public function __construct(
        private LLMService $llmService,
        private TemplateService $templateService,
        private StyleGuideService $styleGuideService,
        private AIEmbodimentQualityScorer $qualityScorer
    ) {}

    public function generateCouncilPI(Collection $advisors, array $options): array
    {
        try {
            // Build council PI using template
            $template = $this->selectTemplate($options['style']);
            $variables = $this->buildTemplateVariables($advisors, $options);
            $councilPI = $this->templateService->renderTemplate($template, $variables);

            // Character optimization
            $councilPI = $this->optimizeCharacterUsage($councilPI, 8000);

            // Validate quality
            $scores = $this->validateQuality($councilPI);

            // Export file
            $filePath = $this->exportCouncilPI($councilPI, $advisors, $options);

            return [
                'success' => true,
                'file_path' => $filePath,
                'char_count' => strlen($councilPI),
                'advisor_count' => $advisors->count(),
                'quality_score' => $scores['overall'],
                'voice_score' => $scores['voice_authenticity'],
                'anti_ai_score' => $scores['anti_ai_compliance'],
                'char_breakdown' => $this->analyzeCharacterUsage($councilPI),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function optimizeCharacterUsage(string $content, int $limit): string
    {
        while (strlen($content) > $limit) {
            // Progressive optimization strategies
            $content = $this->removeRedundantWhitespace($content);
            if (strlen($content) <= $limit) break;

            $content = $this->useAbbreviations($content);
            if (strlen($content) <= $limit) break;

            $content = $this->compressExamples($content);
            if (strlen($content) <= $limit) break;

            $content = $this->removeOptionalSections($content);
        }

        return $content;
    }
}
```

### Success Criteria:

#### Automated Verification:
- [ ] Command runs: `php artisan pi:generate-council`
- [ ] All unit tests pass: `vendor/bin/phpunit tests/Feature/CouncilPITest.php`
- [ ] Linting passes: `vendor/bin/pint --dirty`
- [ ] Character limit enforced (<8000 chars)

#### Manual Verification:
- [ ] Generated PI contains all advisor mode tags
- [ ] Voice anchors are authentic and distinct
- [ ] Anti-AI constraints properly integrated
- [ ] File exports to correct location

---

## Phase 2: Voice Anchor Template System

### Overview
Implement the Pure Voice Anchor strategy with aggressive character optimization for multi-advisor coordination.

### Changes Required:

#### 1. Council PI Template
**File**: `resources/advisor-templates/council-pi-voice-anchor.md`
**Changes**: Ultra-compact template using B version insights

```markdown
# {{council_acronym}} Council PI
**v1.0** | **{{date}}** | **Mode:** `[{{mode_list}}]`

## Voice Protocol
When mode set, load corresponding PK:
{{#advisors}}
**[{{short}}]**→{{slug}}_PK.md
{{/advisors}}

## Voice Anchors
{{#advisors}}
**{{name}}**: {{voice_anchor}}
{{/advisors}}

## Router
{{routing_rules}}

## Council Mode
When [Council]: Present each voice separately → synthesis with action

## Anti-AI Guard
Never: {{forbidden_phrases}}
Always: Proof over opinion, specific over general

## Player: Ben Fisher
Builder-CEO, Laravel/React, ship fast, $100k/mo target

## Control
```yaml
voice_loading: required
anti_ai: enforced
max_chars: 8000
```
```

#### 2. Voice Anchor Generator
**File**: `app/Services/VoiceAnchorService.php`
**Changes**: Generate authentic 2-3 sentence anchors per advisor

```php
<?php

namespace App\Services;

class VoiceAnchorService
{
    private array $voiceAnchors = [
        'alex-bogusky' => "I'm Bogusky. I weaponize truth against comfortable lies. Find the cultural friction, amplify it until competitors panic.",
        'gary-halbert' => "I'm Halbert. Words that sell, not impress. Every sentence earns its keep or gets cut.",
        'cal-henderson' => "I'm Cal. Ship code that scales. No clever abstractions—reliability beats brilliance.",
        'alex-hormozi' => "I'm Hormozi. Math drives everything. Model the money, test the offer, scale what converts.",
    ];

    public function getVoiceAnchor(string $slug): string
    {
        return $this->voiceAnchors[$slug] ?? $this->generateDynamicAnchor($slug);
    }

    public function getCompressedRouting(array $advisors): string
    {
        $rules = [
            'creative/PR/culture' => 'Bogusky',
            'copy/offers/headlines' => 'Halbert',
            'tech/scale/architecture' => 'Cal',
            'money/growth/metrics' => 'Hormozi',
            'multi-domain' => 'Council',
        ];

        return collect($rules)
            ->map(fn($advisor, $trigger) => "{$trigger}→[{$advisor}]")
            ->join('; ');
    }
}
```

### Success Criteria:

#### Automated Verification:
- [ ] Template renders under 8000 chars with 4 advisors
- [ ] All mustache variables populate correctly
- [ ] Voice anchors maintain authenticity (AI scoring >85%)

#### Manual Verification:
- [ ] Each voice anchor captures advisor essence in 2-3 sentences
- [ ] Routing rules cover all major use cases
- [ ] Anti-AI constraints effectively integrated

---

## Phase 3: Quality Validation & A/B Testing

### Overview
Integrate council PI with existing quality systems and enable A/B/C testing for optimization.

### Changes Required:

#### 1. Council Quality Validator
**File**: `app/Services/Validation/CouncilPIValidator.php`
**Changes**: Council-specific quality checks

```php
<?php

namespace App\Services\Validation;

class CouncilPIValidator
{
    public function validate(string $councilPI): array
    {
        $validations = [
            'character_limit' => $this->validateCharacterLimit($councilPI),
            'mode_tags' => $this->validateModeTags($councilPI),
            'voice_loading' => $this->validateVoiceLoading($councilPI),
            'routing_coverage' => $this->validateRoutingCoverage($councilPI),
            'anti_ai_presence' => $this->validateAntiAI($councilPI),
            'voice_distinction' => $this->validateVoiceDistinction($councilPI),
        ];

        $score = collect($validations)
            ->filter(fn($v) => $v['valid'])
            ->count() / count($validations) * 100;

        return [
            'valid' => $score >= 80,
            'score' => $score,
            'validations' => $validations,
            'recommendations' => $this->generateRecommendations($validations),
        ];
    }

    private function validateVoiceDistinction(string $content): array
    {
        // Check each advisor has unique voice anchor
        $anchors = [];
        preg_match_all('/\*\*([^:]+)\*\*: ([^\n]+)/', $content, $matches);

        foreach ($matches[2] as $anchor) {
            $anchors[] = $anchor;
        }

        $uniqueness = count($anchors) === count(array_unique($anchors));

        return [
            'valid' => $uniqueness,
            'message' => $uniqueness
                ? 'All voice anchors are distinct'
                : 'Duplicate voice anchors detected',
        ];
    }
}
```

#### 2. A/B Testing Integration
**File**: `app/Console/Commands/PICouncilABTest.php`
**Changes**: Test multiple council variations

```php
<?php

namespace App\Console\Commands;

class PICouncilABTest extends Command
{
    protected $signature = 'pi:council-ab-test
                           {--variations=3 : Number of variations to test}
                           {--prompts=10 : Test prompts per variation}';

    public function handle(): int
    {
        $variations = [
            'voice-anchor' => 'Pure voice anchors with minimal structure',
            'hybrid' => 'Voice anchors plus routing rules',
            'compressed' => 'Maximum compression with abbreviations',
        ];

        foreach ($variations as $style => $description) {
            $this->info("Testing variation: {$style} - {$description}");

            $pi = $this->generateVariation($style);
            $results = $this->testWithPrompts($pi);

            $this->storeResults($style, $results);
        }

        $this->displayComparison();
        return Command::SUCCESS;
    }
}
```

### Success Criteria:

#### Automated Verification:
- [ ] Validation runs for all council PIs
- [ ] A/B testing generates meaningful comparisons
- [ ] Quality scores tracked in database

#### Manual Verification:
- [ ] Voice distinction validated across advisors
- [ ] A/B test results show clear winners
- [ ] Recommendations actionable for optimization

---

## Phase 4: Production Optimization

### Overview
Optimize for production use with caching, monitoring, and performance tuning.

### Changes Required:

#### 1. Character Usage Analytics
**File**: `app/Services/CharacterUsageAnalyzer.php`
**Changes**: Analyze character budget efficiency

```php
<?php

namespace App\Services;

class CharacterUsageAnalyzer
{
    public function analyze(string $content): array
    {
        return [
            'header' => $this->countSection($content, 'header'),
            'voice_protocol' => $this->countSection($content, 'voice_protocol'),
            'voice_anchors' => $this->countSection($content, 'voice_anchors'),
            'routing' => $this->countSection($content, 'routing'),
            'anti_ai' => $this->countSection($content, 'anti_ai'),
            'control' => $this->countSection($content, 'control'),
            'whitespace' => $this->countWhitespace($content),
            'efficiency' => $this->calculateEfficiency($content),
        ];
    }

    public function recommendOptimizations(array $analysis): array
    {
        $recommendations = [];

        if ($analysis['whitespace'] > 500) {
            $recommendations[] = 'Reduce whitespace (potential savings: ' .
                ($analysis['whitespace'] - 200) . ' chars)';
        }

        if ($analysis['routing'] > 1000) {
            $recommendations[] = 'Compress routing rules using abbreviations';
        }

        return $recommendations;
    }
}
```

#### 2. Production Monitoring
**File**: `app/Jobs/MonitorCouncilPIQuality.php`
**Changes**: Scheduled job for quality monitoring

```php
<?php

namespace App\Jobs;

use App\Models\CouncilPI;
use App\Services\Validation\CouncilPIValidator;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class MonitorCouncilPIQuality implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(CouncilPIValidator $validator): void
    {
        $recentPIs = CouncilPI::where('created_at', '>=', now()->subDays(7))
            ->get();

        foreach ($recentPIs as $pi) {
            $validation = $validator->validate($pi->content);

            if ($validation['score'] < 80) {
                // Alert for quality degradation
                $this->notifyQualityIssue($pi, $validation);
            }

            // Track metrics
            $pi->update([
                'quality_score' => $validation['score'],
                'last_validated_at' => now(),
            ]);
        }
    }
}
```

### Success Criteria:

#### Automated Verification:
- [ ] Character analysis provides actionable insights
- [ ] Monitoring job runs on schedule
- [ ] Alerts trigger for quality degradation

#### Manual Verification:
- [ ] Optimization recommendations are practical
- [ ] Quality trends visible in monitoring
- [ ] Production PIs maintain >80% quality

---

## Testing Strategy

### Unit Tests:
- Voice anchor generation for each advisor
- Character optimization algorithms
- Template rendering with various configurations
- Quality validation logic
- A/B test result analysis

### Integration Tests:
- Full council PI generation pipeline
- Quality scoring with external services
- Export and file management
- Database persistence

### Manual Testing Steps:
1. Generate basic 4-advisor council: `php artisan pi:generate-council`
2. Test with anti-AI enabled: `php artisan pi:generate-council --anti-ai=on`
3. Run A/B tests: `php artisan pi:council-ab-test`
4. Verify character limit with 6 advisors
5. Test mode switching in ChatGPT interface
6. Validate voice authenticity through user testing

## Performance Considerations

### Character Budget Allocation:
- Header/metadata: 150 chars
- Voice protocol: 200 chars
- Voice anchors: 600 chars (150/advisor × 4)
- Routing rules: 400 chars
- Anti-AI constraints: 300 chars
- Player context: 200 chars
- Control/YAML: 150 chars
- **Total baseline**: ~2000 chars (6000 available for expansion)

### Optimization Strategies:
1. **Abbreviations**: B/H/C/Ho for advisor names
2. **Symbol shortcuts**: → instead of "routes to"
3. **Compressed YAML**: Single-line where possible
4. **Smart whitespace**: Only where required for parsing

### Caching:
- Cache voice anchors for 24 hours
- Cache routing rules until advisor update
- Cache quality scores for 1 hour

## Migration Notes

### Database Schema:
```sql
CREATE TABLE council_pis (
    id BIGINT PRIMARY KEY,
    advisor_ids JSON NOT NULL,
    content TEXT NOT NULL,
    style VARCHAR(50),
    character_count INT,
    quality_score DECIMAL(5,2),
    voice_score DECIMAL(5,2),
    anti_ai_score DECIMAL(5,2),
    metadata JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### File Structure:
```
storage/app/advisors/council/
├── 2025-01-16-10-30-45/
│   ├── Council_PI.md
│   ├── metadata.json
│   └── quality_report.json
├── variations/
│   ├── voice-anchor/
│   ├── hybrid/
│   └── compressed/
└── production/
    └── current_Council_PI.md
```

## References

- Original plan: `thoughts/shared/plans/council-pi-generation-command.md`
- Reference BHCH: `/storage/app/advisors/historical/Advisors - Bog Halbert Homz Cal/PI.md`
- B version strategy: Quality scores 85-95% with pure voice anchors
- Current generation: `app/Services/AdvisorGenerationService.php`
- Quality scoring: `app/Services/Validation/AIEmbodimentQualityScorer.php`
- Anti-AI patterns: `config/style-guide.php` (85+ forbidden phrases)