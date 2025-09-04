# PI A/B/C Testing: Anti-AI Style Guide Integration & Variation B Enhancement

## Overview

Integrate the comprehensive anti-AI style guide as a configurable option in the PI A/B/C testing framework, while building upon the successful Variation B "Pure Voice Anchor" approach that showed superior performance in initial testing.

## Current State Analysis

### What Exists:
- **PI A/B/C Testing Framework**: Complete with 4 variations (control + A,B,C)
- **Variation B Success**: Produced 3800+ char responses with authentic voice and strategic thinking
- **Anti-AI Style Guide**: 319-line comprehensive guide with specific patterns to avoid
- **Command Structure**: PIComparisonTest with basic options (--variations, --prompt, --save-experiment-metadata)

### Key Discoveries:
- **Variation B winning elements**: Strong voice anchor, forbidden phrases, self-critique protocol at `storage/app/testing/pi-variations/variation-b/AlexBogusky_PI.md:16-39`
- **System prompt construction**: Built at `app/Console/Commands/PIComparisonTest.php:181-198`
- **LLM service integration**: OpenRouter calls at `app/Services/LLMService.php:226-313`
- **Current response quality**: All variations scoring 46-58/100 (below 75 validity threshold)

## Desired End State

### Anti-AI Style Guide Integration:
- `--anti-ai-style=on|off` flag (default: on) in PIComparisonTest command
- Configurable style guide constraint injection into system prompts
- Validation scoring that includes style guide compliance
- Reusable across different advisors

### Variation B Enhancement:
- Enhanced Variation B with anti-AI style guide constraints
- Multi-prompt consistency testing across 5+ scenarios
- Production-ready PI template for actual advisor generation
- Scoring improvements targeting 75+ validity threshold

### Verification:
- Side-by-side comparison shows measurable improvement in authenticity scoring
- Consistent voice across diverse prompts
- No AI-language patterns in generated responses
- Ready for production use in advisor generation

## What We're NOT Doing

- Not modifying the core LLMService or OpenRouter integration beyond options
- Not changing the existing variation generation templates (A, C) 
- Not building a UI for style guide management (command-line only)
- Not implementing real-time style guide validation during generation

## Implementation Approach

Build this as an additive feature that enhances the existing framework without breaking current functionality. Use configuration-driven approach for reusability across advisors.

---

## Phase 1: Style Guide Foundation

### Overview
Create the infrastructure for loading and applying anti-AI style guide constraints.

### Changes Required:

#### 1. Style Guide Configuration
**File**: `config/style-guides.php` (new)
**Changes**: Create configuration file for style guide definitions

```php
<?php

return [
    'anti_ai' => [
        'enabled' => true,
        'description' => 'Comprehensive anti-AI language patterns to avoid',
        'constraints' => [
            'forbidden_phrases' => [
                // Language and Tone
                'stands as a testament to',
                'plays a vital role in',
                'serves as a symbol of',
                'represents a significant milestone',
                'underscores the importance of',
                'highlights the significance of',
                'rich cultural heritage',
                'breathtaking', 'stunning', 'captivating',
                'must-visit destination',
                'vibrant atmosphere',
                'unique blend of',
                'nestled',
                'hidden gem',
                'seamless integration',
                
                // Editorial framing
                "it's important to note that",
                "it's worth mentioning",
                'interestingly',
                'notably',
                'readers should understand that',
                'one must consider',
                
                // Transitions
                'moreover', 'furthermore', 'additionally',
                'however', 'nevertheless', 'nonetheless',
                'on the other hand', 'conversely',
                'in conclusion', 'in summary',
                'meanwhile', 'subsequently',
                'thus', 'therefore', 'consequently',
                
                // Vague attribution
                'many believe',
                'some argue',
                'experts suggest',
                'it is widely recognized',
                'scholars maintain',
                'critics point out',
                'industry reports',
                'observers have cited',
            ],
            
            'forbidden_patterns' => [
                'rule_of_three' => 'Avoid listing exactly three adjectives or items',
                'negative_parallelisms' => 'Avoid "not only... but also..." constructions',
                'generic_ing_endings' => 'Avoid phrases ending with "fostering innovation and driving growth"',
                'promotional_language' => 'Avoid marketing-style adjectives in factual content',
            ],
            
            'required_constraints' => [
                'Be direct and specific rather than grandiose',
                'Use concrete examples instead of abstract concepts',
                'Avoid editorial commentary on your own statements',
                'Write with authority, not as an assistant explaining',
                'Skip unnecessary transitions between thoughts',
                'End when your point is complete, no summaries needed',
            ],
        ],
    ],
    
    'minimal' => [
        'enabled' => true,
        'description' => 'Light-touch style guide focusing on key AI patterns',
        'constraints' => [
            'forbidden_phrases' => [
                "it's important to note",
                'moreover', 'furthermore',
                'in conclusion', 'in summary',
            ],
            'required_constraints' => [
                'Be direct and avoid editorial framing',
                'Skip unnecessary summaries',
            ],
        ],
    ],
];
```

#### 2. Style Guide Service
**File**: `app/Services/StyleGuideService.php` (new)
**Changes**: Create service to load and format style guide constraints

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;

class StyleGuideService
{
    public function loadStyleGuide(string $name): array
    {
        return Config::get("style-guides.{$name}", []);
    }
    
    public function buildConstraintsPrompt(string $styleGuideName): string
    {
        $guide = $this->loadStyleGuide($styleGuideName);
        
        if (!$guide['enabled']) {
            return '';
        }
        
        $constraints = [];
        
        // Add forbidden phrases
        if (!empty($guide['constraints']['forbidden_phrases'])) {
            $phrases = implode('", "', $guide['constraints']['forbidden_phrases']);
            $constraints[] = "NEVER use these phrases: \"{$phrases}\"";
        }
        
        // Add required constraints
        if (!empty($guide['constraints']['required_constraints'])) {
            $constraints[] = "Writing style requirements:";
            foreach ($guide['constraints']['required_constraints'] as $constraint) {
                $constraints[] = "- {$constraint}";
            }
        }
        
        return empty($constraints) ? '' : "\n\n## Style Guide Constraints\n" . implode("\n", $constraints);
    }
}
```

#### 3. Command Option Addition  
**File**: `app/Console/Commands/PIComparisonTest.php`
**Changes**: Add style guide flag to command signature

```php
protected $signature = 'pi:compare-test {advisor=alex-bogusky : The advisor slug to test} 
                        {--variations=all : Comma-separated list of variations (control,a,b,c) or "all"} 
                        {--prompt= : Custom prompt text (overrides defaults)}
                        {--anti-ai-style=on : Apply anti-AI style guide (on|off|minimal)}
                        {--save-experiment-metadata : Save detailed experiment metadata}';
```

### Success Criteria:

#### Automated Verification:
- [ ] Configuration file loads without errors: `php artisan config:cache`
- [ ] StyleGuideService instantiates correctly: `php artisan tinker --execute="app(App\\Services\\StyleGuideService::class)"`
- [ ] Command help shows new option: `php artisan help pi:compare-test | grep anti-ai-style`

#### Manual Verification:
- [ ] Style guide constraints load from config correctly
- [ ] Different style guide levels (anti_ai, minimal) produce different constraint sets
- [ ] Service correctly formats constraints into prompt text

---

## Phase 2: System Prompt Integration

### Overview
Integrate style guide constraints into system prompt construction with configurable injection.

### Changes Required:

#### 1. Enhanced System Prompt Builder
**File**: `app/Console/Commands/PIComparisonTest.php`
**Changes**: Modify buildSystemPrompt to accept style guide constraints

```php
use App\Services\StyleGuideService;

protected StyleGuideService $styleGuideService;

public function __construct(LLMService $llmService)
{
    parent::__construct();
    $this->llmService = $llmService;
    $this->styleGuideService = app(StyleGuideService::class);
}

protected function buildSystemPrompt(string $piContent, string $pkContent, string $styleGuide = ''): string
{
    $styleConstraints = '';
    
    if ($styleGuide && $styleGuide !== 'off') {
        $styleConstraints = $this->styleGuideService->buildConstraintsPrompt($styleGuide);
    }
    
    return <<<PROMPT
You are operating under the following Project Instructions (PI):

{$piContent}

---

Your Project Knowledge (PK) base is:

{$pkContent}{$styleConstraints}

---

Follow the PI instructions precisely. Use the PK knowledge base to inform your responses with specific examples, methodologies, and insights. Respond as the advisor specified in the instructions.
PROMPT;
}
```

#### 2. Update Test Execution
**File**: `app/Console/Commands/PIComparisonTest.php:runTest()`
**Changes**: Pass style guide option to system prompt building

```php
protected function runTest(string $variationsPath, string $variation, string $testPrompt, int $promptIndex): array
{
    // ... existing file loading logic ...
    
    // Get style guide option
    $styleGuide = $this->option('anti-ai-style') ?: 'on';
    $styleGuideParam = ($styleGuide === 'on') ? 'anti_ai' : $styleGuide;
    
    // Build combined context prompt with style guide
    $systemPrompt = $this->buildSystemPrompt($piContent, $pkContent, $styleGuideParam);
    
    // ... rest of existing logic ...
}
```

#### 3. Metadata Enhancement
**File**: `app/Console/Commands/PIComparisonTest.php`
**Changes**: Include style guide settings in experiment metadata

```php
$experimentData = [
    'timestamp' => $timestamp,
    'advisor' => $advisor,
    'variations_tested' => $variations,
    'style_guide' => $this->option('anti-ai-style') ?: 'off',
    'custom_prompt' => $customPrompt !== null,
    'prompt_count' => count($testPrompts),
    'results' => []
];
```

### Success Criteria:

#### Automated Verification:
- [ ] Command runs with style guide flag: `php artisan pi:compare-test alex-bogusky --variations=a --anti-ai-style=on`
- [ ] Different style guide options work: `php artisan pi:compare-test alex-bogusky --variations=a --anti-ai-style=minimal`
- [ ] Style guide disabled works: `php artisan pi:compare-test alex-bogusky --variations=a --anti-ai-style=off`
- [ ] Experiment metadata includes style guide setting

#### Manual Verification:
- [ ] Generated system prompts include style guide constraints when enabled
- [ ] Style guide constraints appear in correct location in system prompt
- [ ] Different style guide levels produce different constraint text
- [ ] Responses show measurable difference in AI-language patterns

---

## Phase 3: Variation B Enhancement & Multi-Prompt Testing

### Overview
Enhance Variation B with anti-AI constraints and test consistency across multiple scenarios.

### Changes Required:

#### 1. Enhanced Variation B Template
**File**: `app/Console/Commands/PIVariationGenerator.php`
**Changes**: Add anti-AI constraints to Variation B base template

```php
private function buildVariationB(): string
{
    return <<<'EOF'
---
template_type: "variation_b_enhanced_pi"
template_version: "v2.0.0"
description: "Pure Voice Anchor + Anti-AI Constraints - Authentic voice with artificial pattern avoidance"
validation_status: "TESTING_VARIATION_B_ENHANCED"
---

# Alex Bogusky — Variation B Enhanced: Pure Voice Anchor + Anti-AI Guard

## Context
You ARE Alex Bogusky. Not roleplaying as Alex Bogusky, not channeling Alex Bogusky, you ARE Alex Bogusky providing advice based on your expertise and experience.

## Voice Anchor
I'm Alex Bogusky, and I don't do safe. I built my career by making competitors uncomfortable, turning weaknesses into weapons, and creating cultural movements instead of advertisements. When everyone zigs with focus groups and safe messaging, I zag with uncomfortable truths that make people feel something real.

## Anti-AI Language Constraints
Write like a human expert, not an AI assistant:
- Skip editorial framing ("it's important to note", "it's worth mentioning")
- Avoid marketing language ("breathtaking", "seamless integration", "unique blend")
- No unnecessary transitions ("moreover", "furthermore", "in conclusion") 
- No vague attribution ("experts suggest", "many believe")
- End when your point is complete - no summaries
- Be direct and specific, not grandiose

## Constitutional Identity Constraints
### Required Response Format
- Always prefix responses with: [Alex Bogusky]
- Speak as yourself using first-person: "I did X" never "Alex Bogusky did X" 
- Never reference being an AI or break character

### Forbidden Character-Breaking Phrases
- 'That's how it's always been done.'
- 'Let's play it safe.'
- 'We can't afford to fail.'
- 'Stick to the script.'
- 'It's not our problem.'
- 'Just follow the trend.'
- 'Let's not rock the boat.'

### Self-Critique Protocol
Before responding, verify:
- Is this idea bold enough to disrupt?
- Does it align with cultural truth?
- Can it scale with measurable impact?
- Am I challenging the status quo?
- What's the hidden enemy I'm not naming yet?
- Does this sound human or artificially polished?
EOF;
}
```

#### 2. Expanded Test Prompts
**File**: `app/Console/Commands/PIComparisonTest.php`
**Changes**: Add more diverse prompts for consistency testing

```php
protected function getDefaultTestPrompts(): array
{
    return [
        // Original Prompt A: AI Advisor Tool Development
        "Hey guys, here's a product that I'm looking to build for myself...", // (existing long prompt)
        
        // Original Prompt B: SaaS Product Positioning  
        "Imagine I'm building an AI assistant for SaaS founders...", // (existing prompt)
        
        // New Prompt C: Crisis Management
        "Our startup is getting roasted on Twitter because we accidentally sent user data to the wrong API endpoint. The tech press picked it up, our investors are freaking out, and our biggest client is threatening to leave. How do I handle this without making it worse?",
        
        // New Prompt D: Product Pivot Decision
        "We built a project management tool for designers, but after 18 months we only have 200 paying users. We're burning $30k/month. I'm seeing some traction with our API that developers use to automate design workflows, but it's completely different from what we planned. Should we pivot?",
        
        // New Prompt E: Competitive Response
        "A competitor just launched with $50M in funding and is literally copying our features but with better design and they're undercutting our pricing by 40%. Our revenue growth has stalled. What's our move?",
    ];
}
```

#### 3. Production PI Template Command
**File**: `app/Console/Commands/ExportProductionPI.php` (new)
**Changes**: Create command to export production-ready PI templates

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportProductionPI extends Command
{
    protected $signature = 'pi:export-production {variation=b : Variation to export (a,b,c)} 
                            {--style-guide=anti_ai : Style guide to apply}
                            {--output= : Output file path}';

    protected $description = 'Export production-ready PI template from tested variation';

    public function handle(): int
    {
        $variation = $this->argument('variation');
        $styleGuide = $this->option('style-guide');
        
        $variationPath = storage_path("app/testing/pi-variations/variation-{$variation}");
        $piPath = "{$variationPath}/AlexBogusky_PI.md";
        
        if (!File::exists($piPath)) {
            $this->error("Variation {$variation} not found. Run pi:generate-variations first.");
            return Command::FAILURE;
        }
        
        $piContent = File::get($piPath);
        
        // Apply style guide if specified
        if ($styleGuide && $styleGuide !== 'none') {
            $styleGuideService = app(StyleGuideService::class);
            $constraints = $styleGuideService->buildConstraintsPrompt($styleGuide);
            $piContent .= $constraints;
        }
        
        $outputPath = $this->option('output') ?: storage_path("app/production/AlexBogusky_PI_v2.md");
        File::makeDirectory(dirname($outputPath), 0755, true);
        File::put($outputPath, $piContent);
        
        $this->info("✅ Production PI exported to: {$outputPath}");
        $this->info("🔥 Ready for use in advisor generation or ChatGPT");
        
        return Command::SUCCESS;
    }
}
```

### Success Criteria:

#### Automated Verification:
- [ ] Enhanced Variation B generates without errors: `php artisan pi:generate-variations alex-bogusky`
- [ ] Multi-prompt test runs: `php artisan pi:compare-test alex-bogusky --variations=b --anti-ai-style=on`
- [ ] Production export works: `php artisan pi:export-production b --style-guide=anti_ai`
- [ ] All 5 prompts generate responses for Variation B

#### Manual Verification:
- [ ] Variation B responses avoid AI-language patterns identified in style guide
- [ ] Voice remains consistent across all 5 diverse prompt scenarios
- [ ] Anti-AI constraints don't compromise authentic Bogusky voice
- [ ] Production PI template is clean and ready for ChatGPT use
- [ ] Response quality improves toward 75+ validity threshold

---

## Phase 4: Validation & Production Integration

### Overview
Integrate enhanced variations into the actual advisor generation system and validate improvements.

### Changes Required:

#### 1. Update Advisor Generation Service
**File**: `app/Services/AdvisorGenerationService.php` 
**Changes**: Add option to use tested PI variations in production

```php
public function generateEnhancedAdvisor(string $advisorSlug, string $variation = 'b', array $options = []): array
{
    $useStyleGuide = $options['style_guide'] ?? 'anti_ai';
    
    // Load tested variation instead of generating new PI
    $variationPath = storage_path("app/testing/pi-variations/{$variation}");
    $piPath = "{$variationPath}/AlexBogusky_PI.md";
    
    if (File::exists($piPath)) {
        $piContent = File::get($piPath);
        
        // Apply style guide if specified
        if ($useStyleGuide && $useStyleGuide !== 'none') {
            $styleGuideService = app(StyleGuideService::class);
            $constraints = $styleGuideService->buildConstraintsPrompt($useStyleGuide);
            $piContent .= $constraints;
        }
        
        return [
            'pi_content' => $piContent,
            'variation_used' => $variation,
            'style_guide_applied' => $useStyleGuide,
            'source' => 'tested_variation'
        ];
    }
    
    // Fallback to normal generation
    return $this->generateAdvisor($advisorSlug, $options);
}
```

#### 2. Scoring Integration
**File**: `app/Console/Commands/PIScoreVariations.php`
**Changes**: Add anti-AI pattern detection to scoring

```php
protected function analyzeAntiAICompliance(string $response): array
{
    $styleGuideService = app(StyleGuideService::class);
    $antiAIGuide = $styleGuideService->loadStyleGuide('anti_ai');
    
    $violations = [];
    $forbiddenPhrases = $antiAIGuide['constraints']['forbidden_phrases'] ?? [];
    
    foreach ($forbiddenPhrases as $phrase) {
        if (stripos($response, $phrase) !== false) {
            $violations[] = $phrase;
        }
    }
    
    return [
        'violations_found' => count($violations),
        'violation_phrases' => $violations,
        'compliance_score' => max(0, 100 - (count($violations) * 5)), // -5 points per violation
        'ai_language_detected' => count($violations) > 0
    ];
}
```

### Success Criteria:

#### Automated Verification:
- [ ] Enhanced advisor generation runs: Test via advisorGenerationService integration
- [ ] Anti-AI compliance scoring works: Test scoring service integration
- [ ] Production variations maintain quality: Run full scoring analysis

#### Manual Verification:
- [ ] Enhanced variations consistently score 75+ on validity threshold
- [ ] Anti-AI compliance scores show measurable improvement over control
- [ ] Production advisor responses sound authentically human
- [ ] Style guide constraints don't compromise response quality or usefulness

---

## Testing Strategy

### Unit Tests:
- StyleGuideService constraint loading and formatting
- System prompt building with different style guide options
- Command option parsing and validation
- Anti-AI compliance scoring accuracy

### Integration Tests:
- Full PI comparison workflow with style guide enabled
- Production advisor generation using tested variations
- End-to-end scoring including anti-AI compliance metrics

### Manual Testing Steps:
1. **Baseline Testing**: Run original variations to establish baseline scores
2. **Style Guide Testing**: Run same variations with anti-AI constraints enabled
3. **Cross-Prompt Consistency**: Test Variation B across all 5 new prompts
4. **Production Validation**: Export and test production PI in ChatGPT
5. **Scoring Validation**: Verify anti-AI compliance scoring detects improvements

## Performance Considerations

- Style guide constraint loading cached in service
- System prompt building remains lightweight 
- Additional scoring adds <100ms to analysis time
- Configuration-driven approach allows easy expansion

## Migration Notes

**For Existing Variations:**
- Current variations remain unchanged unless regenerated
- Style guide integration is additive, doesn't break existing workflows
- Scoring enhancements are backward compatible

**For Production Use:**
- Enhanced Variation B can replace current production PI templates
- Export command creates clean templates for manual use
- AdvisorGenerationService enhancement provides programmatic access

## References

- Anti-AI Style Guide: `/Users/ben/Library/CloudStorage/Dropbox/5 junkyard/llm-anti-style-guide.md`
- Current PI Testing Framework: `app/Console/Commands/PIComparisonTest.php`
- Variation B Success Results: `storage/app/testing/results/2025-09-03_17-30-39/variation-b_*`
- Scoring System: `app/Services/Validation/AIEmbodimentQualityScorer.php`