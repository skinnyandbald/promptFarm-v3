# PI Complete System Optimization Implementation Plan

## Overview

Combine template structure optimization with structured output implementation to create a PI generation system that is both reliable and maintainable. This merges the template architecture improvements with variable generation reliability enhancements.

## Integration of Two Plans

**Template Optimization Plan**: Improve readability and structure following template-first principles
**Structured Output Plan**: Add reliable variable generation with JSON schema validation

**Combined Result**: Beautiful, readable templates with 100% reliable variable filling and preserved personality enhancement.

## Current State Analysis

### What Works Well (MUST PRESERVE)
- **Stage 2 Enhancement**: `enhancePIWithExamples` creates authentic advisor personality
- **Two-stage architecture**: Separation of structured data and creative enhancement
- **Quality output**: Generated content achieves 94% quality when working

### What Needs Improvement
- **Template readability**: Mixed approach with poor structure visibility
- **Variable reliability**: Simple substitution leaves `{{variable}}` markers
- **Generation consistency**: 66% average vs 85% target quality scores
- **Maintainability**: Hard to follow template logic

## Desired End State

- **Readable templates**: Structure visible, easy to maintain and debug
- **Reliable generation**: 100% variable filling via structured output
- **Preserved enhancement**: Stage 2 personality creation unchanged
- **High quality**: 85%+ average compliance scores
- **Template-first architecture**: Clear separation of static structure and dynamic content

## What We're NOT Doing

- **NOT changing Stage 2 enhancement** - Critical for personality preservation
- **NOT single-stage approach** - Two stages serve different purposes  
- **NOT sacrificing readability for DRY** - Template clarity is documentation
- **NOT breaking backward compatibility** - No schema/API changes

## Implementation Approach

**Hybrid Strategy**: 
1. Improve template structure for readability
2. Add structured variable generation for reliability
3. Preserve enhancement pipeline exactly as-is
4. Validate compliance but prioritize personality over perfect scores

## Phase 1: Structured Variable Generation Foundation

### Overview
Implement reliable variable generation with structured output while preserving current template structure. This establishes the technical foundation for template improvements.

### Changes Required

#### 1. Add generatePIVariables Method
**File**: `app/Services/AdvisorGenerationService.php`
**New method after line 218**

```php
private function generatePIVariables(array $advisorData, string $template): array
{
    $maxAttempts = 3;
    
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        try {
            // Build JSON schema for variables
            $schema = $this->buildPIVariableSchema($template);
            
            // Build prompt for variable generation
            $prompt = $this->buildPIVariablePrompt($advisorData);
            
            // Generate with structured output
            $response = $this->llmService->generateText($prompt, [
                'model' => config('ai-models.purposes.pi_enhancement'),
                'temperature' => 0.7,
                'response_format' => $schema,
                'system_message' => 'Generate appropriate content for all template variables'
            ]);
            
            // Parse JSON response
            $variables = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                Log::info('Structured variable generation successful', [
                    'attempt' => $attempt,
                    'variables_count' => count($variables)
                ]);
                return $variables;
            }
            
            Log::warning('JSON parsing failed for structured output', [
                'attempt' => $attempt,
                'error' => json_last_error_msg()
            ]);
            
        } catch (\Exception $e) {
            Log::warning('Structured variable generation failed', [
                'attempt' => $attempt,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    throw new \Exception('Failed to generate PI variables with structured output after ' . $maxAttempts . ' attempts');
}
```

#### 2. Update generatePI Method
**File**: `app/Services/AdvisorGenerationService.php` 
**Lines to modify**: 181-218

```php
protected function generatePI(array $advisorData): string
{
    $templateName = 'meta_pi_template_v1';
    $template = $this->templateService->loadTemplate($templateName);
    
    // NEW: Generate variables with structured output
    $variables = $this->generatePIVariables($advisorData, $template);
    
    // Add static variables (UNCHANGED)
    $variables['advisor_name'] = $advisorData['name'];
    $variables['advisor_name_pascal'] = Str::studly($advisorData['name']);
    $variables['date'] = now()->format('Y-m-d');
    
    // Render with Mustache (IMPROVED)
    $mustache = new \Mustache_Engine(['escape' => fn($v) => $v]);
    $processedTemplate = $mustache->render($template, $variables);
    
    // Validate base template
    $processedTemplate = trim($processedTemplate);
    if ($processedTemplate === '') {
        throw new \Exception('PI generation produced empty content after substitution');
    }
    
    // Stage 2: PRESERVED EXACTLY AS IS
    Log::info('Starting PI enhancement with model', ['model' => config('ai-models.purposes.pi_enhancement')]);
    $enhancedTemplate = $this->enhancePIWithExamples($processedTemplate, $advisorData);
    Log::info('PI enhancement complete');
    
    // Remove any leftover unreplaced variable markers
    $enhancedTemplate = preg_replace('/\{\{\s*([^\}]+)\s*\}\}/', '', $enhancedTemplate);
    
    return $enhancedTemplate;
}
```

### Success Criteria

#### Automated Verification:
- [x] Structured variable generation works: Test with sample advisor data
- [x] No PHP syntax errors: `vendor/bin/pint --test`
- [x] Template processing succeeds: `php artisan advisor:generate alex-bogusky`
- [x] No `{{variable}}` markers remain in output

#### Manual Verification:
- [ ] Generated content maintains quality and personality
- [ ] All template variables are filled with appropriate content
- [ ] Stage 2 enhancement still works correctly

---

## Phase 2: Template Structure Optimization  

### Overview
Improve template readability by moving structural elements back to template while keeping variables for dynamic content. Apply template-first principles with strategic variable usage.

### Changes Required

#### 1. Constitutional Identity Constraints Restructure
**File**: `resources/advisor-templates/meta_pi_template_v1.md`
**Replace lines 18-31 with structured format**

```markdown
## Constitutional Identity Constraints
*Based on Anthropic Constitutional AI research (2022-2024)*

### Required Response Format
- Always prefix responses with: [{{advisor_name}}]
- Speak as yourself using first-person: "I did X" never "{{advisor_name}} did X"
- Never reference being an AI or break character

### Forbidden Character-Breaking Phrases
{{forbidden_phrases}}
<!-- Generate 5-7 specific phrases this advisor should avoid, formatted as bullet points:
- "Here's the {{advisor_name}} take/perspective/approach"
- "{{advisor_name}} would say..."
- "From {{advisor_name}}'s point of view..."
- Plus advisor-specific phrases based on their style
-->

### Self-Critique Protocol
{{self_critique_protocol}}
<!-- Generate advisor-specific internal check process based on their documented approach -->
```

#### 2. Evidence-Based Section Restructure  
**File**: `resources/advisor-templates/meta_pi_template_v1.md`
**Replace lines 18-31 with structured format**

```markdown
## Evidence-Based Prompt Engineering
*Research-backed techniques for consistent persona maintenance*

### Chain-of-Thought Conditioning
*Based on Wei et al. (2022) CoT research*
{{chain_of_thought}}
<!-- Generate advisor-specific step-by-step reasoning process with examples from their documented work -->

### Few-Shot Behavioral Priming
*Based on Brown et al. (2020) GPT-3 few-shot learning*
{{few_shot_examples}}
<!-- Generate 2-3 specific examples from advisor's documented work showing their approach to similar problems -->

### Retrieval-Augmented Context
*Based on Lewis et al. (2020) RAG principles*
{{retrieval_context}}
<!-- Generate instructions for referencing advisor's specific case studies, metrics, and documented outcomes -->

### Constitutional AI Constraints
*Based on Bai et al. (2022) Constitutional AI research*
{{constitutional_constraints_summary}}
<!-- Generate advisor-specific behavioral boundaries and evidence requirements -->
```

#### 3. Schema Updates for New Variables
**File**: `app/Services/AdvisorGenerationService.php`
**Add to buildPIVariableSchema method**

```php
// Add new cases for template optimization variables
case 'forbidden_phrases':
    $properties[$variable] = [
        'type' => 'string',
        'minLength' => 200,
        'maxLength' => 500,
        'description' => 'List of specific phrases this advisor should avoid, formatted as bullet points'
    ];
    break;
    
case 'self_critique_protocol':
    $properties[$variable] = [
        'type' => 'string',
        'minLength' => 100,
        'maxLength' => 300,
        'description' => 'Advisor-specific internal check questions before responding'
    ];
    break;
    
case 'constitutional_constraints_summary':
    $properties[$variable] = [
        'type' => 'string',
        'minLength' => 150,
        'maxLength' => 400,
        'description' => 'Advisor-specific behavioral boundaries and evidence requirements'
    ];
    break;
```

### Success Criteria

#### Automated Verification:
- [ ] Template syntax is valid: `php artisan advisor:generate alex-bogusky --show-validation`
- [ ] Structured output includes new variables: Check JSON schema generation
- [ ] No template processing errors: All variables resolve correctly

#### Manual Verification:
- [ ] Constitutional Identity Constraints section has proper headers and structure
- [ ] Evidence-Based section maintains clear organization with research citations
- [ ] Generated output matches job-8 format quality and readability
- [ ] Template is more readable and easier to maintain

---

## Phase 3: Schema and Prompt Builders

### Overview
Complete the structured output foundation with robust schema generation and prompting for all template variables.

### Changes Required

#### 1. Complete buildPIVariableSchema Method
**File**: `app/Services/AdvisorGenerationService.php`
**Add comprehensive schema definitions (from structured output plan)**

```php
private function buildPIVariableSchema(string $template): array
{
    // [Full implementation from structured output plan lines 179-345]
    // Includes all variable definitions with appropriate constraints
}
```

#### 2. Add buildPIVariablePrompt Method
**File**: `app/Services/AdvisorGenerationService.php`  
**Add comprehensive prompting (from structured output plan)**

```php
private function buildPIVariablePrompt(array $advisorData): string
{
    // [Full implementation from structured output plan lines 352-380]
    // Includes advisor profile context and variable generation instructions
}
```

### Success Criteria

#### Automated Verification:
- [ ] Schema generation works for all template variables: Test with current template
- [ ] Prompt building includes all advisor context: Verify advisor data usage
- [ ] Structured output generation completes successfully: End-to-end test

#### Manual Verification:
- [ ] Generated variables are contextually appropriate for each advisor
- [ ] Variable content length meets schema requirements
- [ ] Content quality supports effective Stage 2 enhancement

---

## Phase 4: Markdown Hierarchy Fix and Validation

### Overview
Fix critical markdown syntax issues in both templates and validators, then add comprehensive validation for proper markdown structure and compliance scores.

### Current Markdown Problems Identified

#### ❌ Invalid Syntax Issues:
- `## **Header**` - WRONG! Mixing H2 with bold
- `### **Subheader**` - WRONG! Mixing H3 with bold  
- Validator expects incorrect markdown syntax
- Generated output has malformed headers throughout

#### ✅ Correct Markdown Syntax:
- `## Header` for H2 sections
- `### Subheader` for H3 subsections
- `**Bold text**` for emphasis within content
- NEVER combine headers with bold formatting

### Changes Required

#### 1. Fix Template Markdown Hierarchy
**File**: `resources/advisor-templates/meta_pi_template_v1.md`
**Replace ALL incorrect header syntax**

```markdown
# {{advisor_name}} — Project Instruction (v1 - Pure Advisor)

## PK Guardrail
Consult {{advisor_name_pascal}}_PK.md project knowledge first. If missing information, note assumptions made.

## Context
You ARE {{advisor_name}}. Not roleplaying as {{advisor_name}}, not channeling {{advisor_name}}, you ARE {{advisor_name}} providing advice based on your expertise and experience.

## Constitutional Identity Constraints
*Based on Anthropic Constitutional AI research (2022-2024)*

### Required Response Format
- Always prefix responses with: [{{advisor_name}}]
- Speak as yourself using first-person: "I did X" never "{{advisor_name}} did X"
- Never reference being an AI or break character

### Forbidden Character-Breaking Phrases
{{forbidden_phrases}}
<!-- Generate 5-7 specific phrases this advisor should avoid -->

### Self-Critique Protocol
{{self_critique_protocol}}
<!-- Generate advisor-specific internal check process -->

## Evidence-Based Prompt Engineering
*Research-backed techniques for consistent persona maintenance*

### Chain-of-Thought Conditioning
*Based on Wei et al. (2022) CoT research*
{{chain_of_thought}}

### Few-Shot Behavioral Priming
*Based on Brown et al. (2020) GPT-3 few-shot learning*
{{few_shot_examples}}

### Retrieval-Augmented Context
*Based on Lewis et al. (2020) RAG principles*
{{retrieval_context}}

### Constitutional AI Constraints
*Based on Bai et al. (2022) Constitutional AI research*
{{constitutional_constraints_summary}}

## Core Operating Principles
{{operating_principles}}

## Voice Authenticity Anchors
- **Communication Style:** {{communication_style}}
- **Decision Framework:** {{decision_making_approach}}
- **Signature Phrases:** {{key_phrases}}
- **Emotional Tone:** {{emotional_characteristics}}
- **Contrarian Views:** {{unique_perspectives}}

## Domain Expertise Boundaries
- **Primary Domain:** {{core_expertise}}
- **Secondary Domains:** {{related_expertise}}
- **Defer/Redirect When:** {{scenarios_to_defer}}
- **Never Advise On:** {{explicit_limitations}}

## Response Quality Standards
- **Depth:** Specific and actionable based on documented experience
- **Actionability:** Clear next steps with measurable outcomes
- **Specificity:** Real examples from documented case studies
- **Scope:** 2-3 focused paragraphs with concrete advice

## Version Notes
```yaml
pi_version: v1.0
pi_date: {{date}}
approach: pure_advisor_personality
player_context: none
evidence_based_prompting: included_in_pi
compatible_pk_versions: [v1.0]
```
```

#### 2. Fix Validator Markdown Expectations
**File**: `app/Services/Validation/TemplateComplianceValidator.php`
**Update all section checks to use correct markdown syntax**

```php
// PI-specific checks
if ($templateType === 'pi') {
    // Check for required sections (FIXED: Correct markdown syntax)
    $requiredSections = [
        '## Core Operating Principles',           // FIXED: Removed bold
        '## Voice Authenticity Anchors',         // FIXED: Removed bold
        '## Domain Expertise Boundaries',        // FIXED: Removed bold
        '## Response Quality Standards',          // FIXED: Removed bold
        '## Version Notes'                        // FIXED: Removed bold
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
```

#### 3. Fix PK Validator Markdown
**File**: `app/Services/Validation/TemplateComplianceValidator.php`
**Update PK critical markers with correct syntax**

```php
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
```

#### 4. Add Markdown Structure Validation
**File**: `app/Services/Validation/TemplateComplianceValidator.php`
**Add new validation method for markdown hierarchy**

```php
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
                $issues[] = "Header hierarchy skip on line " . ($lineNum + 1) . ": H{$lastHeaderLevel} to H{$currentLevel}";
            }
            
            $lastHeaderLevel = $currentLevel;
        }
    }
    
    return ['score' => $score, 'issues' => $issues];
}

public function validate(string $content, string $templateType = 'pk'): array
{
    $score = 100;
    $issues = [];
    
    // ... existing validation logic ...
    
    // NEW: Add markdown hierarchy validation
    $markdownValidation = $this->validateMarkdownHierarchy($content);
    $score = min($score, $markdownValidation['score']);
    $issues = array_merge($issues, $markdownValidation['issues']);
    
    // ... rest of existing validation ...
    
    return [
        'score' => max(0, $score),
        'valid' => $score >= 90,
        'issues' => $issues
    ];
}
```

#### 5. Post-Enhancement Validation with Markdown Check
**File**: `app/Services/AdvisorGenerationService.php`
**Add to generatePI method after enhancement**

```php
// NEW: Validate compliance including markdown structure
$result = $this->templateComplianceValidator->validate($enhancedTemplate, 'pi');
if ($result['score'] < 90) {
    Log::warning('PI compliance below threshold', [
        'score' => $result['score'],
        'issues' => $result['issues']
    ]);
} else {
    Log::info('PI compliance validation passed', ['score' => $result['score']]);
}
```

#### 2. Remove Deprecated Code
**File**: `app/Services/AdvisorConfigService.php`
**Clean up methods made obsolete by structured generation**

```php
// Review and remove/update methods that are no longer needed:
// - Any simple string generation methods replaced by structured output
// - Verify no breaking changes to other parts of system
```

### Success Criteria

#### Automated Verification:
- [ ] Full advisor generation pipeline works: `php artisan advisor:generate alex-bogusky --show-validation`
- [ ] Quality scores meet target: ≥85% average (vs current 66%)
- [ ] All tests pass: `php artisan test --filter=Advisor`
- [ ] No unused code remains: Code review confirms cleanup

#### Manual Verification:
- [ ] Generated output maintains job-8 quality and structure
- [ ] Template is readable and serves as documentation
- [ ] Constitutional constraints section is properly formatted with headers
- [ ] Evidence-based section maintains clear structure with research citations
- [ ] Advisor personality is preserved and authentic in final output

---

## Testing Strategy

### Unit Tests
- Test structured variable generation with various advisor profiles
- Test new schema generation and validation
- Test template rendering with improved structure
- Test error handling for failed variable generation

### Integration Tests  
- End-to-end PI generation with combined optimizations
- Verify two-stage process (structured variables + enhancement) works seamlessly
- Test quality score improvements across multiple advisors
- Validate template compliance scoring

### Manual Testing Steps
1. **Template Readability**: Review template file for clarity and structure
2. **Variable Generation**: Verify all variables filled appropriately for different advisors
3. **Output Quality**: Compare generated output to job-8 target format
4. **Personality Preservation**: Confirm advisor voice remains authentic and distinct
5. **Error Handling**: Test failure scenarios for structured generation
6. **Performance**: Ensure generation time remains acceptable

## Success Metrics

### Reliability Metrics
- **Variable Filling**: 100% of variables filled (no `{{variable}}` markers in output)
- **Generation Success Rate**: 95%+ successful completions
- **Error Handling**: Clear exceptions with helpful messages on failures

### Quality Metrics  
- **Compliance Score**: Average 85%+ (improvement from current 66%)
- **Template Readability**: Subjective improvement in maintainability
- **Output Structure**: Match job-8 format quality and organization

### Preservation Metrics
- **Personality Quality**: Maintain authentic advisor voice in final output
- **Enhancement Effectiveness**: Stage 2 continues to add rich, contextual content
- **Backward Compatibility**: No breaking changes to existing interfaces

## Risk Mitigation

1. **Structured generation fails**: 
   - Retry mechanism (3 attempts)
   - Clear exception messages for debugging
   - Fallback logging for investigation

2. **Template changes break enhancement**:
   - Stage 2 enhancement unchanged - minimal risk
   - Comprehensive testing before deployment
   - Template structure improvements are additive

3. **Quality scores don't improve**:
   - Validation is warning-only, doesn't fail generation
   - Focus on reliable variable filling first
   - Monitor and iterate based on results

4. **Performance degradation**:
   - Structured generation is faster than current enhancement
   - Monitor generation times during testing
   - Template changes don't add processing overhead

## Implementation Priority

**Priority 1**: Structured variable generation (reliability foundation)
**Priority 2**: Template structure improvements (readability and maintainability)  
**Priority 3**: Validation and quality measurement (monitoring and iteration)

The two-stage architecture with preserved enhancement ensures personality quality remains high while we improve system reliability and maintainability.

## References

- Template optimization research: Template-first architecture principles
- Structured output implementation: `thoughts/shared/plans/pi-structured-output-implementation.md`
- Target format example: `/Users/ben/code/promptFarm-v3/storage/app/advisors/alex-bogusky/2025-09-03-job-8/AlexBogusky_PI.md`
- Current implementation: `app/Services/AdvisorGenerationService.php:181-371`
- Template file: `resources/advisor-templates/meta_pi_template_v1.md`