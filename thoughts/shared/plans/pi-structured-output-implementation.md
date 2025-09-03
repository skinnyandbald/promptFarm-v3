# PI Structured Output Implementation Plan

## Overview

Enhance PI (Project Instructions) generation to ensure all template sections are preserved while maintaining the critical enhancement step that adds personality-specific content.

## Current State Analysis

The current PI generation uses a two-stage approach:
1. **Stage 1**: Deterministic template substitution with variables
2. **Stage 2**: LLM enhancement that replaces HTML comments with personality-specific content
   - Adds anecdotes, questions, mental models
   - Creates authentic advisor voice
   - **CRITICAL**: This step MUST be preserved

### Issues with Current Approach:
- Sometimes skips required sections
- Doesn't always preserve template structure
- Can miss Version Notes YAML
- No structured validation

### Key Discoveries:
- PK generation uses structured output with 100% template compliance
- PI template has Mustache variables + HTML comment sections
- **HTML comments contain instructions for enhancement, not just placeholders**
- The enhancement step is what makes each advisor unique

## Desired End State

PI generation that:
- **Preserves the two-stage approach** (variables + enhancement)
- Adds structured validation after enhancement
- Ensures all template sections are preserved
- Maintains personality-specific enhancements
- Achieves 90%+ template compliance scores

### Verification:
- All generated PIs contain all required sections
- Enhancement content remains rich and personality-specific
- Version Notes YAML present at the end
- Quality scores improve from current 66% average to 85%+

## What We're NOT Doing

- **NOT removing the enhancement step** (enhancePIWithExamples)
- **NOT converting to single-stage structured output**
- NOT changing the PI template structure itself
- NOT modifying PK generation (it's working well)
- NOT altering the quality scoring system

## Implementation Approach

Keep the two-stage approach but add validation:
1. Stage 1: Structured output for template variables
2. Stage 2: Enhancement with personality-specific content
3. Add validation step to ensure compliance
4. Implement retry logic if validation fails

## Phase 1: Enhanced PI Generation with Validation

### Overview
Keep the two-stage approach but ensure Stage 1 uses structured output for variables, then Stage 2 enhances with personality content.

### Changes Required:

#### 1. Update generatePI Method - Stage 1
**File**: `app/Services/AdvisorGenerationService.php`
**Changes**: Use structured output for initial variables, then enhance

```php
protected function generatePI(array $advisorData, array $mappedVars = []): string
{
    $templateName = 'meta_pi_template_v1';
    $template = $this->templateService->loadTemplate($templateName);
    
    // Stage 1: Generate variables with structured output
    $variables = $this->generatePIVariables($advisorData, $template);
    
    // Add static variables
    $variables['advisor_name'] = $advisorData['name'];
    $variables['advisor_name_pascal'] = Str::studly($advisorData['name']);
    $variables['date'] = now()->format('Y-m-d');
    
    // Render initial template with variables
    $mustache = new \Mustache_Engine(['escape' => fn($v) => $v]);
    $processedTemplate = $mustache->render($template, $variables);
    
    // Stage 2: Enhance with personality-specific content (KEEP THIS!)
    $enhancedTemplate = $this->enhancePIWithExamples($processedTemplate, $advisorData);
    
    // Validate compliance
    $result = $this->templateComplianceValidator->validate($enhancedTemplate, 'pi');
    
    if ($result['score'] < 90) {
        // Log issues but don't fail - the enhancement is more important
        Log::warning('PI compliance score below threshold', [
            'score' => $result['score'],
            'issues' => $result['issues']
        ]);
    }
    
    return $enhancedTemplate;
}

private function generatePIVariables(array $advisorData, string $template): array
{
    $maxAttempts = 3;
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        try {
            // Build prompt for structured generation
            $prompt = $this->buildPIVariablePrompt($advisorData);
            
            // Build JSON schema for PI variables
            $schema = $this->buildPIVariableSchema($template);
            
            // Generate with structured output
            $response = $this->llmService->generateText($prompt, [
                'model' => config('ai-models.purposes.pi_enhancement'),
                'temperature' => 0.7,
                'response_format' => $schema,
                'system_message' => 'Return valid JSON with all template variables filled'
            ]);
            
            // Parse JSON response
            $variables = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON: '.json_last_error_msg());
            }
            
            return $variables;
            
        } catch (\Exception $e) {
            Log::warning('PI variable generation attempt failed', [
                'attempt' => $attempt,
                'error' => $e->getMessage()
            ]);
            
            if ($attempt === $maxAttempts) {
                // Fall back to mapped variables if structured generation fails
                return $mappedVars;
            }
        }
    }
}
```

### Success Criteria:

#### Automated Verification:
- [ ] Unit tests pass: `php artisan test --filter=AdvisorGenerationTest`
- [ ] All existing advisor generation tests pass: `php artisan test`
- [ ] No PHP syntax errors: `php -l app/Services/AdvisorGenerationService.php`
- [ ] Code style passes: `vendor/bin/pint --dirty`

#### Manual Verification:
- [ ] Generate a test advisor and verify all sections present
- [ ] Version Notes YAML appears at the end
- [ ] No missing template sections
- [ ] Quality scores improve from baseline

---

## Phase 2: Build PI Variable Schema

### Overview
Create schema builder for PI template variables with appropriate constraints.

### Changes Required:

#### 1. Add buildPIVariableSchema Method
**File**: `app/Services/AdvisorGenerationService.php`
**Changes**: Add new method to build JSON schema for PI variables

```php
private function buildPIVariableSchema(string $template): array
{
    preg_match_all('/\{\{([^}]+)\}\}/', $template, $matches);
    $variables = array_unique($matches[1]);
    
    $properties = [];
    $required = [];
    
    foreach ($variables as $variable) {
        // Skip variables we'll add statically
        if (in_array($variable, ['advisor_name', 'advisor_name_pascal', 'date'])) {
            continue;
        }
        
        $required[] = $variable;
        
        switch ($variable) {
            // Voice & Communication Variables
            case 'communication_style':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 50,
                    'maxLength' => 200,
                    'description' => 'How the advisor communicates (e.g., "Mathematical, direct, example-heavy. Everything has a formula.")'
                ];
                break;
                
            case 'decision_making_approach':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 50,
                    'maxLength' => 200,
                    'description' => 'Framework for making decisions'
                ];
                break;
                
            case 'key_phrases':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 50,
                    'maxLength' => 200,
                    'description' => 'Signature phrases (comma-separated)'
                ];
                break;
                
            case 'emotional_characteristics':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 30,
                    'maxLength' => 150,
                    'description' => 'Emotional tone and personality traits'
                ];
                break;
                
            case 'unique_perspectives':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 50,
                    'maxLength' => 250,
                    'description' => 'Contrarian or unique viewpoints'
                ];
                break;
                
            // Expertise Domain Variables
            case 'core_expertise':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 50,
                    'maxLength' => 200,
                    'description' => 'Primary domain of expertise'
                ];
                break;
                
            case 'related_expertise':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 100,
                    'maxLength' => 300,
                    'description' => 'Secondary areas including how they were developed'
                ];
                break;
                
            case 'scenarios_to_defer':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 100,
                    'maxLength' => 400,
                    'description' => 'Specific scenarios when to redirect to other experts'
                ];
                break;
                
            case 'explicit_limitations':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 100,
                    'maxLength' => 400,
                    'description' => 'Areas to never advise on (list format)'
                ];
                break;
                
            // Evidence-Based Content Variables
            case 'chain_of_thought':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 200,
                    'maxLength' => 600,
                    'description' => 'Step-by-step reasoning instructions with specific examples'
                ];
                break;
                
            case 'few_shot_examples':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 300,
                    'maxLength' => 800,
                    'description' => '2-3 behavioral examples: "When I faced X, I did Y and achieved Z"'
                ];
                break;
                
            case 'retrieval_context':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 200,
                    'maxLength' => 500,
                    'description' => 'Instructions for citing specific evidence'
                ];
                break;
                
            case 'constitutional_constraints':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 200,
                    'maxLength' => 600,
                    'description' => 'Safety constraints ensuring grounded advice'
                ];
                break;
                
            case 'operating_principles':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 400,
                    'maxLength' => 1000,
                    'description' => '6-8 core principles in first person, one per line'
                ];
                break;
                
            default:
                // Generic fallback for any unmapped variables
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 50,
                    'maxLength' => 500,
                    'description' => "Content for {$variable}"
                ];
                break;
        }
    }
    
    return [
        'type' => 'json_schema',
        'json_schema' => [
            'name' => 'pi_template_variables',
            'strict' => true,
            'schema' => [
                'type' => 'object',
                'properties' => $properties,
                'required' => $required,
                'additionalProperties' => false
            ]
        ]
    ];
}
```

#### 2. Add buildPIGenerationPrompt Method
**File**: `app/Services/AdvisorGenerationService.php`
**Changes**: Create focused prompt for structured PI generation

```php
private function buildPIGenerationPrompt(array $advisorData, string $template): string
{
    $advisorName = $advisorData['name'];
    $expertise = $advisorData['expertise'] ?? '';
    $background = $advisorData['background'] ?? '';
    $notableWork = $advisorData['notable_work'] ?? '';
    $methodology = $advisorData['methodology'] ?? '';
    $keyPhrases = $advisorData['key_phrases'] ?? '';
    
    // Add secondary perspectives if available
    $secondaryPerspectives = '';
    if (!empty($advisorData['secondary_perspectives'])) {
        $secondaryPerspectives = "\nCRITICAL PERSPECTIVE: {$advisorData['secondary_perspectives']}";
    }
    
    return <<<PROMPT
Generate Project Instructions (PI) content for {$advisorName}.

Advisor Profile:
- Expertise: {$expertise}
- Background: {$background}
- Notable Work: {$notableWork}
- Methodology: {$methodology}
- Key Phrases: {$keyPhrases}
{$secondaryPerspectives}

Template Context:
{$template}

Instructions:
1. Fill ALL template variables with appropriate content
2. Use first-person voice for all content
3. Include specific examples from the advisor's documented work
4. Ensure each section is complete and substantial
5. Follow the character limits for each variable
6. Create authentic voice based on the advisor's actual approach

Key Requirements:
- operating_principles: 6-8 principles that define their approach
- few_shot_examples: 2-3 specific examples with outcomes
- chain_of_thought: Step-by-step reasoning process
- All voice variables must reflect their actual communication style

Return ONLY valid JSON with the template variables.
PROMPT;
}
```

### Success Criteria:

#### Automated Verification:
- [ ] Schema generation creates valid JSON schema: `php artisan tinker` test
- [ ] All PI template variables are mapped correctly
- [ ] Schema includes proper constraints for each variable type
- [ ] Unit tests for schema builder pass

#### Manual Verification:
- [ ] Generated schema handles all PI variables
- [ ] Constraints match expected content types
- [ ] Schema validation works with test data

---

## Phase 3: Update Template Compliance Validation

### Overview
Ensure the template compliance validator works correctly for PI templates.

### Changes Required:

#### 1. Update TemplateComplianceValidator
**File**: `app/Services/Validation/TemplateComplianceValidator.php`
**Changes**: Add PI-specific validation rules

```php
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
    
    return [
        'score' => max(0, $score),
        'valid' => $score >= 90,
        'issues' => $issues
    ];
}
```

### Success Criteria:

#### Automated Verification:
- [ ] Validator tests pass: `php artisan test --filter=TemplateComplianceValidator`
- [ ] PI validation correctly identifies missing sections
- [ ] Score calculation works as expected
- [ ] Both PI and PK validation work correctly

#### Manual Verification:
- [ ] Generated PIs pass validation with 90%+ scores
- [ ] Missing sections are properly detected
- [ ] Version Notes presence is validated

---

## Phase 4: Testing & Validation

### Overview
Comprehensive testing to ensure all advisors generate complete, compliant PIs.

### Changes Required:

#### 1. Update Unit Tests
**File**: `tests/Feature/AdvisorGenerationTest.php`
**Changes**: Update test expectations for structured PI generation

```php
public function test_pi_generation_with_structured_output()
{
    // Mock LLM service to return structured JSON
    $this->mock(LLMService::class, function ($mock) {
        $mock->shouldReceive('generateText')
            ->andReturnUsing(function($prompt, $options = []) {
                if (isset($options['response_format'])) {
                    // Return valid JSON for structured output
                    return json_encode([
                        'communication_style' => 'Direct and analytical...',
                        'decision_making_approach' => 'Data-driven framework...',
                        'key_phrases' => 'Show me the data, test everything...',
                        'emotional_characteristics' => 'Intense, focused...',
                        'unique_perspectives' => 'Contrarian views on...',
                        'core_expertise' => 'Business optimization and scaling...',
                        'related_expertise' => 'Marketing, sales, operations...',
                        'scenarios_to_defer' => 'Technical implementation details...',
                        'explicit_limitations' => 'Legal advice, medical decisions...',
                        'chain_of_thought' => 'Step 1: Identify the constraint...',
                        'few_shot_examples' => 'When I faced declining sales...',
                        'retrieval_context' => 'Reference specific metrics...',
                        'constitutional_constraints' => 'Never advise without evidence...',
                        'operating_principles' => 'Test everything, measure results...'
                    ]);
                }
                return 'Fallback content';
            });
    });
    
    $service = app(AdvisorGenerationService::class);
    $result = $service->generatePI($this->advisorData);
    
    // Verify all sections present
    $this->assertStringContainsString('## **Core Operating Principles**', $result);
    $this->assertStringContainsString('## **Voice Authenticity Anchors**', $result);
    $this->assertStringContainsString('## **Version Notes**', $result);
    $this->assertStringContainsString('pi_version: v1.0', $result);
}
```

#### 2. Manual Testing Checklist
1. Generate PI for each advisor type (Bogusky, Hormozi, Henderson, Halbert)
2. Verify all sections are present
3. Check quality scores improve to 85%+
4. Confirm Version Notes YAML at the end
5. Compare file sizes with previous generations
6. Validate content quality and authenticity

### Success Criteria:

#### Automated Verification:
- [ ] All unit tests pass: `php artisan test`
- [ ] Integration tests pass: `php artisan test --filter=AdvisorGenerationControllerTest`
- [ ] No regression in existing functionality
- [ ] CI/CD pipeline passes

#### Manual Verification:
- [ ] Generate 4 test advisors successfully
- [ ] All PIs contain complete sections
- [ ] Quality scores average 85%+
- [ ] Version Notes present in all PIs
- [ ] Content reads naturally and authentically

---

## Testing Strategy

### Unit Tests:
- Test PI variable schema generation
- Test structured output JSON parsing
- Test template compliance validation for PI
- Test retry logic with escalating strictness

### Integration Tests:
- End-to-end PI generation for multiple advisors
- Quality score validation
- File export verification
- API endpoint testing

### Manual Testing Steps:
1. Run generation for Bogusky: `php artisan advisors:generate alex-bogusky`
2. Check generated file has all sections
3. Verify Version Notes at end of file
4. Compare with previous generation for completeness
5. Repeat for other advisors

## Performance Considerations

- Structured output may be slightly slower than free-form (1-2 seconds)
- Retry logic adds time if first attempt fails
- Cache template parsing to avoid repeated regex operations
- Consider parallel generation for multiple advisors

## Migration Notes

- Existing PI files remain unchanged
- New generations will use structured output
- Quality scores expected to improve significantly
- No database migrations required

## References

- Original issue: Missing sections in Bogusky PI generation
- PK implementation: `app/Services/AdvisorGenerationService.php:350-450`
- PI template: `resources/advisor-templates/meta_pi_template_v1.md`
- Similar implementation: PK structured output (lines 390-424)