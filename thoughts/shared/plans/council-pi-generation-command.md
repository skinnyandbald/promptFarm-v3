# Council PI Generation Command Implementation Plan

## Overview

We need to create a command that generates a single council PI file that can orchestrate multiple advisor voices, incorporating lessons learned from our current single-advisor system and the proven "B version" Pure Voice Anchor strategy. The system will create a hybrid council PI under 8000 characters that enables dynamic routing between advisors while maintaining voice authenticity.

## Current State Analysis

Based on research of the codebase, we have:

### Current Advisors:
- **Alex Bogusky**: `storage/app/advisors/alex-bogusky/` (creative disruption)
- **Gary Halbert**: `storage/app/advisors/gary-halbert/` (direct response copy)
- **Cal Henderson**: `storage/app/advisors/cal-henderson/` (technical architecture)  
- **Alex Hormozi**: `storage/app/advisors/alex-hormozi/` (business scaling)

### Current System Architecture:
- **Two-stage generation**: Variable extraction → content enhancement (`AdvisorGenerationService.php:42-176`)
- **Quality scoring**: AI embodiment (65%) + static analysis (35%) (`AIEmbodimentQualityScorer.php:28-44`)
- **Template system**: Versioned PI/PK templates with constitutional constraints
- **File structure**: Individual PI/PK pairs with job-based versioning

### Key Discoveries:
- Current system produces individual PI files (7152-7823 chars) with separate PK files
- Quality scores: PI typically 68-94%, PK consistently fails (54-76%)
- Common failures: missing sections, length issues, voice violations
- B version strategy shows 85-95% AI embodiment scores with minimal structure
- Reference council system uses mode routing with compressed structure

### Existing Commands:
- `PIComparisonTest.php` - Tests A/B/C variations including B version
- `PIVariationGenerator.php` - Generates variations with anti-AI integration
- `GenerateAdvisor.php` - Single advisor generation

## Desired End State

A new `php artisan pi:generate-council` command that creates:

1. **Single Council PI File** (under 8000 chars) that orchestrates multiple advisors
2. **Mode-based routing** system enabling individual advisor selection or council mode
3. **Voice loading protocol** that dynamically references existing PK files
4. **Pure Voice Anchor strategy** integration for authentic personality expression
5. **Anti-AI style guide** integration to prevent robotic language
6. **Quality validation** ensuring council PI meets embodiment standards

### Verification Criteria:
- Council PI file generated under 8000 characters
- All current advisors integrated with proper routing
- Mode tags enable switching between individual advisors and council
- B version strategy applied (minimal structure, strong voice anchors)
- Quality score >80% on AI embodiment metrics
- Integration with existing PK files (no duplication)

## What We're NOT Doing

- Creating new individual advisor generations (use existing)
- Modifying existing PK files or single-advisor PIs
- Rebuilding the quality scoring system
- Changing the current advisor database schema
- Creating a web UI for council generation

## Implementation Approach

**Strategy**: Extend existing advisor generation system with new council-specific command and service. Use reference BHCH hybrid system as template, compress using proven character optimization techniques, and integrate B version Pure Voice Anchor strategy.

## Phase 1: Core Command Structure

### Overview
Create the basic console command and service architecture for council PI generation.

### Changes Required:

#### 1. Console Command
**File**: `app/Console/Commands/PIGenerateCouncil.php`
**Changes**: New command extending existing advisor generation patterns

```php
<?php

namespace App\Console\Commands;

use App\Services\CouncilPIGenerationService;
use Illuminate\Console\Command;

class PIGenerateCouncil extends Command
{
    protected $signature = 'pi:generate-council 
                           {advisors* : Advisor slugs to include}
                           {--style=pure-voice-anchor : Generation style}
                           {--anti-ai-style=on : Enable anti-AI constraints}
                           {--max-chars=8000 : Maximum characters}
                           {--export-path= : Custom export path}';

    protected $description = 'Generate a council PI that orchestrates multiple advisors';

    public function handle(CouncilPIGenerationService $service): int
    {
        $advisors = $this->argument('advisors');
        $options = [
            'style' => $this->option('style'),
            'anti_ai_style' => $this->option('anti-ai-style') === 'on',
            'max_chars' => (int) $this->option('max-chars'),
            'export_path' => $this->option('export-path'),
        ];

        $result = $service->generateCouncilPI($advisors, $options);

        if ($result['success']) {
            $this->info("Council PI generated successfully: {$result['file_path']}");
            $this->table(['Metric', 'Value'], [
                ['Character Count', $result['char_count']],
                ['Advisor Count', $result['advisor_count']],
                ['Quality Score', $result['quality_score']],
            ]);
        } else {
            $this->error("Failed to generate council PI: {$result['error']}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
```

#### 2. Council PI Generation Service
**File**: `app/Services/CouncilPIGenerationService.php`
**Changes**: Core service implementing council PI generation logic

```php
<?php

namespace App\Services;

use App\Models\Advisor;
use App\Services\Validation\AIEmbodimentQualityScorer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class CouncilPIGenerationService
{
    public function __construct(
        private LLMService $llmService,
        private StyleGuideService $styleGuideService,
        private AIEmbodimentQualityScorer $qualityScorer,
        private TemplateService $templateService
    ) {}

    public function generateCouncilPI(array $advisorSlugs, array $options): array
    {
        // Load advisors
        $advisors = Advisor::whereIn('slug', $advisorSlugs)->get();
        
        if ($advisors->count() !== count($advisorSlugs)) {
            return ['success' => false, 'error' => 'Some advisors not found'];
        }

        // Generate council PI content
        $councilPI = $this->buildCouncilPI($advisors, $options);
        
        // Validate character limit
        if (strlen($councilPI) > $options['max_chars']) {
            $councilPI = $this->compressCouncilPI($councilPI, $options['max_chars']);
        }
        
        // Quality validation
        $qualityScore = $this->qualityScorer->scorePI($councilPI);
        
        // Export file
        $filePath = $this->exportCouncilPI($councilPI, $advisors, $options);
        
        return [
            'success' => true,
            'file_path' => $filePath,
            'char_count' => strlen($councilPI),
            'advisor_count' => $advisors->count(),
            'quality_score' => $qualityScore,
        ];
    }
    
    private function buildCouncilPI(Collection $advisors, array $options): string
    {
        return $this->templateService->render('council-pi-template', [
            'advisors' => $advisors,
            'style' => $options['style'],
            'anti_ai_constraints' => $options['anti_ai_style'] 
                ? $this->styleGuideService->getConstraints() : '',
        ]);
    }
}
```

### Success Criteria:

#### Automated Verification:
- [ ] Command executes without errors: `php artisan pi:generate-council alex-bogusky gary-halbert`
- [ ] Service class loads successfully
- [ ] All tests pass: `vendor/bin/phpunit tests/Feature/PIGenerateCouncilTest.php`
- [ ] Linting passes: `vendor/bin/pint`

#### Manual Verification:
- [ ] Command shows proper help text with `php artisan pi:generate-council --help`
- [ ] Error handling works for invalid advisor names
- [ ] Options are properly parsed and passed to service

---

## Phase 2: Council PI Template System

### Overview
Create the template that generates council PI content using the Pure Voice Anchor strategy and reference system patterns.

### Changes Required:

#### 1. Council PI Template
**File**: `resources/advisor-templates/council-pi-template.md`
**Changes**: New template implementing BHCH hybrid pattern with B version strategy

```markdown
# **{{council_name}} Council — Project Instruction**
**Version:** v1.0 | **Date:** {{date}} | **Git:** {{git_hash}}

**Mode Tag:** `[Mode: {{advisor_aliases}}|Council]`

## Critical: Voice Loading Protocol
{{#advisors}}
- **[Mode: {{alias}}]** → Load **{{slug}}_PK.md** ({{specialty}})
{{/advisors}}
- **[Mode: Council]** → Load all PK files, present distinct perspectives then synthesis

## Pure Voice Anchors (B Strategy)
{{#advisors}}
**{{name}} Voice**: {{voice_anchor}}

{{/advisors}}

## Council Protocol
When [Mode: Council]:
1. Present each advisor's distinct perspective with attribution
2. No blended advice—keep voices separate  
3. Format: **[{{advisor}}]:** insight, then synthesis
4. Synthesis must be actionable with specific next steps

## Router Logic
{{#routing_rules}}
- **{{trigger}}** → **[Mode: {{advisor}}]**
{{/routing_rules}}
- **Complex/multi-domain** → Council

{{#anti_ai_constraints}}
## Anti-AI Constitutional Boundaries
**Forbidden phrases**: {{forbidden_phrases}}

**Self-critique protocol**:
- Is this specific enough to act on?
- Does it maintain advisor voice authenticity?
- Is there proof, not just opinion?
- Would this challenge conventional thinking?
{{/anti_ai_constraints}}

## Ben Fisher Context (MAB)
Builder-CEO; full-stack founder. **Defaults:** Laravel+Inertia/React, Claude Code. **Style:** timebox, iterate, ship fast. **Target:** $100k/mo profit path.

## Success Metrics  
ctr, cvr, reply_rate, close_rate, cycle_time, ACV, CAC_payback, time_to_value

## Control
```yaml
council_mode: enabled
voice_loading: required  
{{#anti_ai_style}}anti_ai_style: mandatory{{/anti_ai_style}}
max_response: 500_words
voice_strategy: pure_anchor
constitutional: active
```
```

#### 2. Template Processing Logic
**File**: `app/Services/CouncilPITemplateService.php` 
**Changes**: Service to process council-specific template variables

```php
<?php

namespace App\Services;

use App\Models\Advisor;
use Illuminate\Support\Collection;

class CouncilPITemplateService
{
    public function buildTemplateVariables(Collection $advisors, array $options): array
    {
        return [
            'council_name' => $this->generateCouncilName($advisors),
            'date' => now()->toDateString(),
            'git_hash' => $this->getGitHash(),
            'advisor_aliases' => $advisors->pluck('alias')->join('|'),
            'advisors' => $this->buildAdvisorData($advisors),
            'routing_rules' => $this->buildRoutingRules($advisors),
            'anti_ai_constraints' => $options['anti_ai_style'] ?? false,
            'forbidden_phrases' => $this->getForbiddenPhrases(),
        ];
    }

    private function buildAdvisorData(Collection $advisors): array
    {
        return $advisors->map(function ($advisor) {
            return [
                'name' => $advisor->name,
                'alias' => $this->generateAlias($advisor->name),
                'slug' => $advisor->slug,
                'specialty' => $this->getSpecialty($advisor),
                'voice_anchor' => $this->generateVoiceAnchor($advisor),
            ];
        })->toArray();
    }

    private function generateVoiceAnchor(Advisor $advisor): string
    {
        // B version strategy: 3-4 sentence identity declaration
        return match($advisor->slug) {
            'alex-bogusky' => "I'm Alex Bogusky, and I don't do safe. I built my career making competitors uncomfortable, turning weaknesses into weapons, and creating cultural movements instead of advertisements.",
            'gary-halbert' => "I'm Gary Halbert. I write copy that makes people buy things. No theories, no clever wordplay—just words that turn readers into customers with mathematical precision.",
            'cal-henderson' => "I'm Cal Henderson. I build systems that scale from startup to billions of users. I care about reliability, not cleverness—make it work, make it fast, make it maintainable.", 
            'alex-hormozi' => "I'm Alex Hormozi. I turn businesses into cash-generating machines. I buy companies, optimize operations, and sell for multiples using systematic, repeatable processes.",
            default => $this->generateGenericVoiceAnchor($advisor),
        };
    }
}
```

### Success Criteria:

#### Automated Verification:
- [ ] Template renders without errors
- [ ] All mustache variables are populated
- [ ] Generated PI is under 8000 characters
- [ ] Template service unit tests pass

#### Manual Verification:
- [ ] Voice anchors capture authentic advisor personalities
- [ ] Routing logic covers all advisor specialties
- [ ] Anti-AI constraints are properly integrated
- [ ] Council protocol is clear and actionable

---

## Phase 3: Integration and Quality Validation

### Overview
Integrate council generation with existing quality systems and add comprehensive validation.

### Changes Required:

#### 1. Quality Scoring Enhancement
**File**: `app/Services/Validation/AIEmbodimentQualityScorer.php`
**Changes**: Add council-specific scoring logic

```php
// Add method to existing class
public function scoreCouncilPI(string $councilPI): array
{
    $baseScore = $this->scorePI($councilPI);
    
    // Council-specific validations
    $councilValidations = [
        'mode_tag_present' => $this->validateModeTag($councilPI),
        'voice_loading_protocol' => $this->validateVoiceLoading($councilPI), 
        'routing_coverage' => $this->validateRoutingCoverage($councilPI),
        'voice_anchor_authenticity' => $this->validateVoiceAnchors($councilPI),
        'character_limit' => strlen($councilPI) <= 8000,
    ];
    
    $councilScore = collect($councilValidations)->values()->sum() / count($councilValidations) * 100;
    
    return [
        'base_score' => $baseScore,
        'council_score' => $councilScore,
        'overall_score' => ($baseScore * 0.7) + ($councilScore * 0.3),
        'validations' => $councilValidations,
    ];
}
```

#### 2. Export and File Management
**File**: `app/Services/CouncilPIExportService.php`
**Changes**: Handle council PI file export with proper versioning

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class CouncilPIExportService  
{
    public function exportCouncilPI(string $content, array $advisors, array $options): string
    {
        $baseDir = $options['export_path'] ?? storage_path('app/advisors/council');
        $timestamp = now()->format('Y-m-d-H-i-s');
        $advisorSlugs = collect($advisors)->pluck('slug')->join('-');
        
        $dir = "{$baseDir}/{$advisorSlugs}/{$timestamp}";
        File::ensureDirectoryExists($dir);
        
        $filePath = "{$dir}/Council_PI.md";
        File::put($filePath, $content);
        
        // Export metadata
        $metadataPath = "{$dir}/metadata.json";
        File::put($metadataPath, json_encode([
            'advisors' => $advisors,
            'options' => $options,
            'generated_at' => now()->toISOString(),
            'character_count' => strlen($content),
        ], JSON_PRETTY_PRINT));
        
        return $filePath;
    }
}
```

### Success Criteria:

#### Automated Verification:
- [ ] Quality scoring runs without errors
- [ ] Council-specific validations work
- [ ] Files export to correct locations with proper structure
- [ ] Integration tests pass

#### Manual Verification:
- [ ] Quality scores are meaningful and actionable
- [ ] Exported files contain all expected content
- [ ] Metadata accurately reflects generation parameters
- [ ] File structure follows existing conventions

---

## Testing Strategy

### Unit Tests:
**Key Test Cases:**
- Council PI template rendering with various advisor combinations
- Voice anchor generation for each advisor type
- Character limit compression algorithms
- Quality scoring for council-specific patterns
- Anti-AI constraint integration

### Integration Tests:
**End-to-End Scenarios:**
- Generate council PI with all 4 current advisors
- Generate smaller council with 2-3 advisors  
- Test with anti-AI style guide enabled/disabled
- Validate quality scores meet thresholds
- Export files and verify structure

### Manual Testing Steps:
1. **Generate basic council PI**: `php artisan pi:generate-council alex-bogusky gary-halbert cal-henderson alex-hormozi`
2. **Test mode routing**: Verify generated PI properly routes different question types
3. **Validate voice anchors**: Confirm each advisor voice anchor captures authentic personality
4. **Check character limits**: Ensure PI stays under 8000 characters even with all advisors
5. **Quality validation**: Confirm quality scores are >80%
6. **Anti-AI integration**: Test with style guide constraints enabled

### Performance Testing:
- **Generation time**: Should complete within 30 seconds for 4 advisors
- **Character optimization**: Must stay under 8000 chars for up to 6 advisors
- **Quality consistency**: Scores should be repeatable within 5% variance

## Performance Considerations

### Character Optimization:
- Progressive compression based on advisor count
- Smart alias generation (B/H/He/Ho for 4 advisors)
- Inline heuristics vs. detailed routing tables
- Template inheritance to avoid duplication

### Caching Strategy:
- Cache voice anchors per advisor to avoid regeneration
- Template compilation caching
- Quality score caching with advisor combination keys

### Scalability:
- Support for 2-8 advisor configurations
- Dynamic template sections based on advisor count
- Fallback compression strategies if character limit exceeded

## Migration Notes

### Backward Compatibility:
- Existing individual advisor PIs remain unchanged
- Current PK files are referenced, not modified
- No changes to existing database schema
- All existing commands continue to work

### Data Migration:
- No database changes required
- Council PIs stored in new directory structure
- Existing advisor metadata preserved

## References

- **Reference PI System**: `/Users/ben/code/promptFarm-v3/storage/app/advisors/historical/Advisors - Bog Halbert Homz Cal/PI.md`
- **Current Generation Service**: `app/Services/AdvisorGenerationService.php`
- **B Version Strategy**: `thoughts/shared/plans/pi-abc-testing-implementation-plan.md`
- **Quality Scoring**: `app/Services/Validation/AIEmbodimentQualityScorer.php`
- **Template System**: `resources/advisor-templates/meta_pi_template_v1.md`