# PI Structured Output Implementation Plan

## Executive Summary

Enhance PI generation reliability by adding structured output to Stage 1 (variable filling) while preserving the critical Stage 2 enhancement that creates authentic advisor personalities.

## Current State Analysis

### The Two-Stage Approach (Working Well)
1. **Stage 1**: Fill template variables with basic content
2. **Stage 2**: Enhance entire document with personality-specific content via `enhancePIWithExamples`

### What Works Well (MUST PRESERVE)

#### The Enhancement System
- **`enhancePIWithExamples` method**: Takes template and transforms it into personality-rich document
  - NOT just about HTML comments - it's comprehensive document enhancement
  - Adds anecdotes, questions, mental models, response protocols
  - Creates authentic advisor voice
  - This is what makes Bogusky sound like Bogusky

- **`buildPIEnhancementPrompt` method**: Creates sophisticated enhancement prompts
  - Question-first approaches
  - Anecdote deployment (Domino's, McKinsey examples)
  - Mental model shifts
  - "Three Offers Rule"
  - Natural conversational flow

### What Needs Improvement

#### Stage 1 Issues
- Simple substitution often leaves variables empty (`$mappedVars[$varName] ?? ''`)
- No validation that all variables are filled
- Results in `{{variable}}` markers in output
- No structured approach to ensure completeness

#### Validation Gaps
- No check for required sections
- Missing Version Notes YAML verification
- Quality scores averaging 66% instead of 85%+ target

## Desired End State

- **Stage 1**: Reliable variable filling via structured output
- **Stage 2**: Enhancement preserved exactly as is
- **Validation**: Post-enhancement compliance checking
- **Quality**: 85%+ average scores while maintaining personality

## What We're NOT Doing

- **NOT removing enhancement step** - It's critical for personality
- **NOT converting to single-stage** - Two stages serve different purposes
- **NOT prioritizing compliance over personality** - Rich content matters more
- **NO backward compatibility needed** - This is still local development

## Implementation Approach

### Core Strategy
1. Use structured output for Stage 1 variable generation
2. Keep Stage 2 enhancement completely unchanged
3. Add validation after enhancement (log issues but don't fail)
4. Direct error handling if structured generation fails (no fallback)

## Phase 1: Enhanced Variable Generation

### Overview
Replace simple variable mapping with structured LLM generation while preserving enhancement.

### Changes Required

#### 1. Update generatePI Method
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
    
    // NEW: Validate compliance (but don't fail)
    $result = $this->templateComplianceValidator->validate($enhancedTemplate, 'pi');
    if ($result['score'] < 90) {
        Log::warning('PI compliance below threshold', [
            'score' => $result['score'],
            'issues' => $result['issues']
        ]);
    }
    
    // Remove any leftover unreplaced variable markers
    $enhancedTemplate = preg_replace('/\{\{\s*([^\}]+)\s*\}\}/', '', $enhancedTemplate);
    
    return $enhancedTemplate;
}
```

#### 2. Add generatePIVariables Method
**File**: `app/Services/AdvisorGenerationService.php`
**New method to add**

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
    
    // All attempts failed - throw exception
    throw new \Exception('Failed to generate PI variables with structured output after ' . $maxAttempts . ' attempts');
}
```

## Phase 2: Schema and Prompt Builders

### 1. Add buildPIVariableSchema Method
**File**: `app/Services/AdvisorGenerationService.php`

```php
private function buildPIVariableSchema(string $template): array
{
    preg_match_all('/\{\{([^}]+)\}\}/', $template, $matches);
    $variables = array_unique($matches[1]);
    
    $properties = [];
    $required = [];
    
    foreach ($variables as $variable) {
        // Skip static variables we'll add manually
        if (in_array($variable, ['advisor_name', 'advisor_name_pascal', 'date'])) {
            continue;
        }
        
        $required[] = $variable;
        
        switch ($variable) {
            case 'chain_of_thought':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 200,
                    'maxLength' => 600,
                    'description' => 'Step-by-step reasoning process that will be enhanced with specific examples'
                ];
                break;
                
            case 'few_shot_examples':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 300,
                    'maxLength' => 800,
                    'description' => 'Initial behavioral examples that will be enriched with anecdotes'
                ];
                break;
                
            case 'retrieval_context':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 200,
                    'maxLength' => 500,
                    'description' => 'Evidence citation instructions to be enhanced'
                ];
                break;
                
            case 'constitutional_constraints':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 200,
                    'maxLength' => 600,
                    'description' => 'Safety constraints that will be personalized'
                ];
                break;
                
            case 'operating_principles':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 400,
                    'maxLength' => 1000,
                    'description' => '6-8 core principles that define approach'
                ];
                break;
                
            case 'communication_style':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 50,
                    'maxLength' => 200,
                    'description' => 'How the advisor communicates'
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
                    'description' => 'Emotional tone and personality'
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
                    'description' => 'Secondary areas with development story'
                ];
                break;
                
            case 'scenarios_to_defer':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 100,
                    'maxLength' => 400,
                    'description' => 'When to redirect to other experts'
                ];
                break;
                
            case 'explicit_limitations':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 100,
                    'maxLength' => 400,
                    'description' => 'Areas to never advise on'
                ];
                break;
                
            default:
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 50,
                    'maxLength' => 500,
                    'description' => "Content for {$variable}"
                ];
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

### 2. Add buildPIVariablePrompt Method
**File**: `app/Services/AdvisorGenerationService.php`

```php
private function buildPIVariablePrompt(array $advisorData): string
{
    $advisorName = $advisorData['name'];
    $expertise = $advisorData['expertise'] ?? '';
    $background = $advisorData['background'] ?? '';
    $notableWork = $advisorData['notable_work'] ?? '';
    $methodology = $advisorData['methodology'] ?? '';
    $keyPhrases = $advisorData['key_phrases'] ?? '';
    
    return <<<PROMPT
Generate initial template variables for {$advisorName}'s Project Instructions.

Advisor Profile:
- Expertise: {$expertise}
- Background: {$background}
- Notable Work: {$notableWork}
- Methodology: {$methodology}
- Key Phrases: {$keyPhrases}

Instructions:
1. Fill all variables with appropriate baseline content
2. Use first-person voice where applicable
3. These will be enhanced later with specific examples and anecdotes
4. Focus on core characteristics and approaches
5. Follow the character limits for each variable

Return valid JSON with all template variables filled appropriately.
PROMPT;
}
```

## Phase 3: What We're NOT Changing

### Preserved Methods (CRITICAL - DO NOT MODIFY)

#### 1. enhancePIWithExamples - COMPLETELY UNCHANGED
Lines 224-291 remain exactly as they are. This method:
- Transforms the template into a personality-rich document
- Adds anecdotes, questions, mental models
- Creates authentic advisor voice
- Is the heart of advisor personality generation

#### 2. buildPIEnhancementPrompt - COMPLETELY UNCHANGED
Lines 296-371 remain exactly as they are. This method:
- Creates sophisticated enhancement prompts
- Includes question-first approaches
- Adds anecdote deployment instructions
- Ensures natural conversational flow

## Phase 4: Validation Updates

### Update TemplateComplianceValidator
**File**: `app/Services/Validation/TemplateComplianceValidator.php`

Already updated in previous work to handle both PI and PK validation.

## Testing Strategy

### Unit Tests
- Test structured variable generation
- Test error handling when generation fails
- Test enhancement preservation
- Test validation scoring

### Integration Tests
- End-to-end PI generation
- Verify two-stage process works
- Check enhancement quality
- Validate compliance scores

### Manual Testing
1. Generate PI for test advisor
2. Verify all variables filled (no `{{}}` markers)
3. Confirm enhancement adds personality content
4. Check quality scores improve to 85%+
5. Verify Version Notes YAML present

## Success Metrics

- **Variable Filling**: 100% of variables filled (no `{{variable}}` in output)
- **Enhancement Quality**: Rich personality content preserved
- **Compliance Score**: Average 85%+ (from current 66%)
- **Error Handling**: Clear exception if structured generation fails after retries
- **Performance**: Similar generation time (enhancement dominates time anyway)

## Risk Mitigation

1. **Structured generation fails**: Throws clear exception after 3 retries
2. **Validation score low**: Log warning but still return enhanced content
3. **Enhancement breaks**: Impossible - we're not touching it
4. **Variables incomplete**: Structured output ensures all required fields are filled

## Implementation Priority

**Priority 1**: Preserve enhancement (done by not changing it)
**Priority 2**: Reliable variable filling
**Priority 3**: Validation and compliance

The enhancement that creates authentic advisor personality is more important than perfect compliance scores.

## References

- Current implementation: `app/Services/AdvisorGenerationService.php:181-371`
- PI template: `resources/advisor-templates/meta_pi_template_v1.md`
- PK structured approach: `app/Services/AdvisorGenerationService.php:376-449`