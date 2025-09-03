# PI Template System Optimization Implementation Plan

## Overview

Refactor the PI template system to follow optimal template architecture principles: template-first with strategic variables, prioritizing readability over theoretical optimization, based on comprehensive research of industry best practices.

## Current State Analysis

### What Exists Now:
- **Mixed approach**: Some sections hardcoded in template, others fully variable-ized
- **Poor readability**: Evidence-Based section uses all variables, making template hard to understand
- **Inconsistent structure**: Beautiful formatted output (job-8) doesn't match template structure
- **Working functionality**: System generates high-quality output (94% score) but with suboptimal maintainability

### Key Discoveries:
- `meta_pi_template_v1.md:18-31` - Evidence-Based section uses all variables, loses readability
- Job-8 output has excellent structure with proper headers and formatting
- Job-9 compressed format is much harder to read and maintain
- Research shows template-first approach with strategic variables is optimal
- Current AI enhancement process works well but template structure needs improvement

### Constraint:
- Must maintain existing AI enhancement pipeline
- Cannot break existing generation quality (94% target)
- Must preserve two-stage architecture (static substitution + AI enhancement)

## Desired End State

A template system that:
- **Maintains beautiful formatting** like job-8 output directly in the template
- **Uses variables strategically** only for content requiring personalization
- **Keeps structure readable** for both humans and AI
- **Produces identical high-quality output** with better maintainability
- **Follows template-first principles** with clear separation of concerns

### Success Verification:
- Template is readable and self-documenting
- Generated output maintains 90%+ quality score
- Constitutional constraints section has proper formatting
- No functional regressions in generation process

## What We're NOT Doing

- Changing the two-stage architecture (static + AI enhancement)
- Modifying the AI enhancement pipeline or prompts  
- Adding new functionality beyond optimization
- Changing database schema or configuration structure
- Optimizing token usage (separate effort)

## Implementation Approach

**Template-First Refactoring**: Move structural elements back to template while keeping variables only for content requiring genuine personalization. Maintain AI enhancement for dynamic content expansion.

## Phase 1: Constitutional Identity Constraints Restructure

### Overview
Add properly formatted Constitutional Identity Constraints section to template, removing compressed variable approach in favor of readable template structure.

### Changes Required:

#### 1. Template Structure Enhancement
**File**: `resources/advisor-templates/meta_pi_template_v1.md`
**Changes**: Add Constitutional Identity Constraints section with proper formatting

```markdown
## **Constitutional Identity Constraints**
*Based on Anthropic Constitutional AI research (2022-2024)*

### **Required Response Format**
- Always prefix responses with: [{{advisor_name}}]
- Speak as yourself using first-person: "I did X" never "{{advisor_name}} did X"
- Never reference being an AI or break character

### **Forbidden Character-Breaking Phrases**
{{forbidden_phrases}}
<!-- Generate 5-7 specific phrases this advisor should avoid, formatted as bullet points:
- "Here's the {{advisor_name}} take/perspective/approach"
- "{{advisor_name}} would say..."
- "From {{advisor_name}}'s point of view..."
- Plus advisor-specific phrases based on their style
-->

### **Self-Critique Protocol**
{{self_critique_protocol}}
<!-- Generate advisor-specific internal check process based on their documented approach -->
```

#### 2. Variable Generation Updates  
**File**: `app/Services/AdvisorConfigService.php`
**Changes**: Add methods to generate new template variables

```php
protected function generateForbiddenPhrases(array $config): string
{
    $name = Arr::get($config, 'full_name', 'the advisor');
    return implode("\n", [
        "- \"Here's the {$name} take/perspective/approach\"",
        "- \"{$name} would say...\"", 
        "- \"From {$name}'s point of view...\"",
        "- \"Let me channel {$name}...\"",
        "- \"As {$name} might suggest...\""
    ]);
}

protected function generateSelfCritiqueProtocol(array $config): string
{
    $name = Arr::get($config, 'full_name', 'the advisor');
    return "Before responding, ask: \"Am I speaking as {$name} or about {$name}?\" If about, rewrite in first person.";
}
```

### Success Criteria:

#### Automated Verification:
- [ ] Template syntax is valid: `php artisan advisor:generate alex-bogusky --show-validation`
- [ ] No PHP syntax errors: `vendor/bin/pint --test`
- [ ] Unit tests pass: `php artisan test --filter=AdvisorConfigServiceTest`
- [ ] Template variables resolve correctly: Check template substitution works

#### Manual Verification:
- [ ] Generated PI has Constitutional Identity Constraints section with proper headers
- [ ] Forbidden phrases are formatted as bullet points
- [ ] Self-critique protocol appears with advisor-specific content
- [ ] Overall template readability is improved

---

## Phase 2: Evidence-Based Section Restructure

### Overview
Refactor Evidence-Based Prompt Engineering section to maintain readable structure while preserving AI enhancement capabilities.

### Changes Required:

#### 1. Template Structure Optimization
**File**: `resources/advisor-templates/meta_pi_template_v1.md` 
**Changes**: Replace variable-heavy approach with structured template

```markdown
## **Evidence-Based Prompt Engineering**
*Research-backed techniques for consistent persona maintenance*

### **Chain-of-Thought Conditioning**
*Based on Wei et al. (2022) CoT research*
{{chain_of_thought}}
<!-- Generate advisor-specific step-by-step reasoning process with examples from their documented work -->

### **Few-Shot Behavioral Priming** 
*Based on Brown et al. (2020) GPT-3 few-shot learning*
{{few_shot_examples}}
<!-- Generate 2-3 specific examples from advisor's documented work showing their approach to similar problems -->

### **Retrieval-Augmented Context**
*Based on Lewis et al. (2020) RAG principles*
{{retrieval_context}}
<!-- Generate instructions for referencing advisor's specific case studies, metrics, and documented outcomes -->

### **Constitutional AI Constraints**
*Based on Bai et al. (2022) Constitutional AI research*
{{constitutional_constraints_summary}}
<!-- Generate advisor-specific behavioral boundaries and evidence requirements -->
```

#### 2. Variable Mapping Updates
**File**: `app/Services/AdvisorConfigService.php`
**Changes**: Update return array to include new variables

```php
return [
    // ... existing variables ...
    
    // Constitutional constraints
    'forbidden_phrases' => $this->generateForbiddenPhrases($config),
    'self_critique_protocol' => $this->generateSelfCritiqueProtocol($config),
    'constitutional_constraints_summary' => $this->generateConstitutionalConstraintsSummary($config),
    
    // ... rest of variables ...
];
```

### Success Criteria:

#### Automated Verification:
- [ ] Template processes without errors: `php artisan advisor:generate alex-bogusky`
- [ ] All template variables are resolved: No `{{variable}}` markers remain in output
- [ ] Quality score maintains 90%+: Check generated metadata
- [ ] No regressions in existing functionality

#### Manual Verification:
- [ ] Evidence-Based section has clear structure with research citations
- [ ] Each technique has proper headers and formatting
- [ ] Content flows logically and maintains advisor voice
- [ ] Section is readable to both humans and AI

---

## Phase 3: Template Consistency Validation

### Overview
Ensure the refactored template produces output matching the quality and structure of the best examples (job-8) while maintaining all functionality.

### Changes Required:

#### 1. Remove Deprecated Variables
**File**: `app/Services/AdvisorConfigService.php`
**Changes**: Clean up unused variables and methods

```php
// Remove or update these if no longer needed:
// - generateConstitutionalConstraints() (if replaced by more specific methods)
// - Any other variables made obsolete by template changes
```

#### 2. Template Comments Cleanup
**File**: `resources/advisor-templates/meta_pi_template_v1.md`
**Changes**: Ensure all HTML comments provide clear guidance for AI enhancement

### Success Criteria:

#### Automated Verification:
- [ ] Full advisor generation completes successfully: `php artisan advisor:generate alex-bogusky --show-validation`
- [ ] Quality score meets target: ≥90% in generated metadata
- [ ] All tests pass: `php artisan test --filter=Advisor`
- [ ] No unused variables remain: Code review confirms cleanup

#### Manual Verification:
- [ ] Generated output matches job-8 format quality and structure
- [ ] Constitutional constraints section is properly formatted with headers
- [ ] Evidence-based section maintains clear structure
- [ ] Template is more readable and maintainable than before
- [ ] AI enhancement still works correctly for personalized content

---

## Testing Strategy

### Unit Tests:
- Test new variable generation methods (generateForbiddenPhrases, generateSelfCritiqueProtocol)
- Verify template variable mapping includes all new variables
- Test edge cases with missing or invalid advisor data

### Integration Tests:
- Full advisor generation pipeline with refactored template
- Quality score validation remains consistent
- Template substitution and AI enhancement work together

### Manual Testing Steps:
1. Generate Alex Bogusky advisor using refactored template
2. Compare output structure to job-8 (target format)
3. Verify Constitutional Identity Constraints section formatting
4. Confirm Evidence-Based section maintains readability
5. Test with 2-3 different advisors to ensure consistency
6. Validate template is easier to read and understand

## Performance Considerations

No significant performance impact expected:
- Changes are primarily structural reorganization
- Same number of template variables (some replaced, not added)
- AI enhancement pipeline remains unchanged
- Template processing overhead minimal

## Migration Notes

**Backward Compatibility**: Changes are template structure only - no breaking changes to:
- Database schema
- API interfaces  
- Existing generated files
- Configuration format

**Rollback Plan**: Keep backup of original template file for quick reversion if needed.

## References

- Template architecture research findings from web-search-researcher
- Original analysis: `/Users/ben/code/promptFarm-v3/storage/app/advisors/alex-bogusky/2025-09-03-job-8/AlexBogusky_PI.md` (target format)
- Comparison example: `/Users/ben/code/promptFarm-v3/storage/app/advisors/alex-bogusky/2025-09-03-job-9/AlexBogusky_PI.md` (avoid this format)
- Template best practices: Industry standards for template vs code vs configuration separation