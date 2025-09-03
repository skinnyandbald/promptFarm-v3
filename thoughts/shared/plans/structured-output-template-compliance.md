# Template Compliance Implementation Plan (Simplified)

## Overview

Implement a realistic approach to template compliance that accepts LLMs aren't perfect but uses retry logic, validation, and Mustache as a safety net to achieve high compliance rates.

## Current State Analysis

The PK generation process ignores templates, creating unauthorized sections and losing critical emphasis markers. LLMs treat templates as suggestions rather than requirements.

### Key Discoveries:
- Template `meta_pk_template_v1.md` has specific structure with emphasis markers
- Current generation completely ignores template, creates its own sections
- LLMs are inherently creative and resist strict structure
- Perfect compliance on first attempt is unrealistic

## Desired End State

A system that:
1. Shows the template to the LLM with strict instructions
2. Gets JSON response with variable values
3. Uses Mustache to render the template
4. Validates compliance
5. Retries with stronger instructions if needed
6. Fails loudly after max attempts

Success rate target: 80% on first attempt, 95% after retries.

## What We're NOT Doing

- NOT expecting 100% compliance on first attempt
- NOT hiding the template from the LLM (loses context)
- NOT implementing complex multi-stage generation
- NOT creating fallback generation strategies

## Implementation Approach

**The Reality:** LLMs will sometimes ignore instructions. Instead of preventing this, we detect and retry.

## Phase 1: Install Mustache and Add JSON Support

### Overview
Basic infrastructure for template rendering and structured responses.

### Changes Required:

#### 1. Install Mustache
```bash
composer require mustache/mustache
```

#### 2. Add Structured Output Support to LLMService
**File**: `app/Services/LLMService.php`
**Changes**: Add response_format parameter support for OpenRouter

```php
// In generateTextWithOpenRouter method, modify the payload:
$payload = [
    'model' => $model,
    'messages' => [
        ['role' => 'system', 'content' => $systemMessage],
        ['role' => 'user', 'content' => $prompt]
    ],
    'temperature' => $temperature,
    'max_tokens' => $maxTokens,
];

// ADD THIS: Structured output support
if (isset($options['response_format'])) {
    // Validate model supports JSON mode
    $capabilities = config("ai-models.capabilities.{$model}");
    if (!($capabilities['json_mode'] ?? false)) {
        throw new \Exception("Model {$model} does not support JSON mode required for template compliance");
    }
    
    $payload['response_format'] = $options['response_format'];
}

$response = $this->httpClient->post('https://openrouter.ai/api/v1/chat/completions', [
    'json' => $payload,
    'headers' => [
        'Authorization' => 'Bearer ' . $apiKey,
        'HTTP-Referer' => config('app.url'),
        'X-Title' => config('app.name'),
    ],
]);

// After getting response, validate JSON if structured output was requested
if (isset($options['response_format'])) {
    $content = $response['choices'][0]['message']['content'] ?? '';
    $jsonTest = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        Log::error('Structured output returned invalid JSON', [
            'error' => json_last_error_msg(),
            'content' => substr($content, 0, 500)
        ]);
        throw new \Exception('Model returned invalid JSON despite structured output mode');
    }
}
```

### Success Criteria:
- [x] Mustache installed
- [x] JSON mode parameter passes to OpenRouter
- [x] Non-JSON models throw exception

---

## Phase 2: Simple PK Generation with Retry Logic

### Overview
The core generation logic with built-in retry mechanism.

### Changes Required:

#### 1. Rewrite generatePK Method
**File**: `app/Services/AdvisorGenerationService.php`
**Changes**: Simple approach with retries

```php
protected function generatePK(array $advisorData, int $maxAttempts = 3): array
{
    $template = $this->loadPKTemplate();
    $mustache = new \Mustache_Engine(['escape' => fn($v) => $v]);
    
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        try {
            // Step 1: Build prompt with template and strict instructions
            $prompt = $this->buildTemplateCompliancePrompt(
                $template, 
                $advisorData, 
                $attempt
            );
            
            // Step 2: Generate with structured output (JSON schema)
            $schema = $this->buildVariableSchema($template);
            
            $response = $this->llmService->generateText($prompt, [
                'model' => config('ai-models.purposes.pk_generation'),
                'temperature' => 0.7,
                'response_format' => $schema,  // Schema already includes the full structure
                'system_message' => 'You must return valid JSON with template variables only'
            ]);
            
            // Step 3: Parse JSON
            $variables = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON: ' . json_last_error_msg());
            }
            
            // Step 4: Render with Mustache
            $rendered = $mustache->render($template, $variables);
            
            // Step 5: Validate compliance
            $validator = new TemplateComplianceValidator();
            $result = $validator->validate($rendered);
            
            if ($result['score'] >= 90) {
                return [
                    'content' => $rendered,
                    'compliance_score' => $result['score'],
                    'attempt' => $attempt
                ];
            }
            
            // Log issues for debugging
            Log::warning('Template compliance failed', [
                'attempt' => $attempt,
                'score' => $result['score'],
                'issues' => $result['issues']
            ]);
            
        } catch (\Exception $e) {
            Log::error('PK generation attempt failed', [
                'attempt' => $attempt,
                'error' => $e->getMessage()
            ]);
            
            if ($attempt === $maxAttempts) {
                throw new TemplateComplianceException(
                    "Failed to generate compliant PK after {$maxAttempts} attempts",
                    ['last_error' => $e->getMessage()]
                );
            }
        }
    }
    
    throw new TemplateComplianceException('Max attempts reached without compliance');
}
```

#### 2. Build JSON Schema for Variables
**File**: `app/Services/AdvisorGenerationService.php`
**Changes**: Create schema to enforce structured output

```php
private function buildVariableSchema(string $template): array
{
    // Extract all {{variables}} from template
    preg_match_all('/\{\{([^}]+)\}\}/', $template, $matches);
    $variables = array_unique($matches[1]);
    
    $properties = [];
    $required = [];
    
    foreach ($variables as $variable) {
        $required[] = $variable;
        
        // Define specific requirements for known variables
        switch ($variable) {
            case 'voice_dna':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 50,
                    'maxLength' => 150,
                    'description' => 'One-line essence of the advisor'
                ];
                break;
                
            case 'patterns_list':
            case 'anti_patterns_list':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 100,
                    'description' => '5-6 bullet points starting with "- "'
                ];
                break;
                
            case 'voice_example_1':
            case 'voice_example_2':
            case 'voice_example_3':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 50,
                    'description' => 'First-person quote with specific examples'
                ];
                break;
                
            case 'analytical_tensions':
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 200,
                    'description' => 'Paradoxes with evidence and uncomfortable truths'
                ];
                break;
                
            default:
                $properties[$variable] = [
                    'type' => 'string',
                    'minLength' => 10,
                    'description' => 'Content for ' . $variable
                ];
        }
    }
    
    return [
        'type' => 'json_schema',
        'json_schema' => [
            'name' => 'pk_template_variables',
            'strict' => true,  // CRITICAL: Enforces exact schema compliance
            'schema' => [
                'type' => 'object',
                'properties' => $properties,
                'required' => $required,
                'additionalProperties' => false  // CRITICAL: Prevents adding ANY extra fields
            ]
        ]
    ];
}
```

#### 3. Build Prompt with Escalating Strictness
**File**: `app/Services/AdvisorGenerationService.php`
**Changes**: Stronger instructions on retry

```php
private function buildTemplateCompliancePrompt(
    string $template, 
    array $advisorData, 
    int $attempt
): string {
    $advisorName = $advisorData['full_name'] ?? $advisorData['name'];
    
    // Base instructions
    $instructions = "Fill in ONLY the {{variables}} in this template for {$advisorName}.";
    
    // Escalate strictness with each attempt
    if ($attempt === 1) {
        $instructions .= "\n\nRULES:
1. Replace {{variable}} markers with appropriate content
2. Return as JSON: {\"variable_name\": \"value\"}
3. Do NOT modify any other text
4. Use first-person voice for examples";
    } elseif ($attempt === 2) {
        $instructions .= "\n\nCRITICAL - YOU FAILED LAST TIME:
1. You MUST ONLY replace {{variables}}
2. You MUST return ONLY JSON
3. You MUST NOT change ANY formatting
4. You MUST preserve ALL emphasis markers
5. FAILURE TO COMPLY WILL CAUSE REJECTION";
    } else {
        $instructions .= "\n\n⚠️ FINAL ATTEMPT - STRICT COMPLIANCE REQUIRED ⚠️
YOU HAVE FAILED TWICE. THIS IS YOUR LAST CHANCE.

MANDATORY REQUIREMENTS:
- ONLY replace text between {{ and }}
- Return PURE JSON: {\"variable\": \"value\"}
- DO NOT add sections
- DO NOT remove emphasis
- DO NOT be creative with structure
- JUST FILL IN THE VARIABLES

IF YOU FAIL AGAIN, THE GENERATION WILL BE REJECTED.";
    }
    
    // Extract variable list for clarity
    preg_match_all('/\{\{([^}]+)\}\}/', $template, $matches);
    $variables = array_unique($matches[1]);
    
    $instructions .= "\n\nVARIABLES TO FILL:\n" . implode("\n", array_map(
        fn($v) => "- {$v}: " . $this->getVariableDescription($v),
        $variables
    ));
    
    $instructions .= "\n\nTEMPLATE:\n{$template}";
    
    $instructions .= "\n\nADVISOR CONTEXT:\n" . json_encode($advisorData, JSON_PRETTY_PRINT);
    
    return $instructions;
}
```

---

## Phase 3: Simple Validation

### Overview
Basic validation to check if the template structure is preserved.

### Changes Required:

#### 1. Create Simple Validator
**File**: `app/Services/Validation/TemplateComplianceValidator.php`
**Changes**: Check for critical markers

```php
<?php

namespace App\Services\Validation;

class TemplateComplianceValidator
{
    private array $criticalMarkers = [
        '## **Voice Anchor (CRITICAL - STUDY THIS)**',
        '**Voice DNA:**',
        '**Voice Examples (STUDY THESE):**',
        '**Patterns (ALWAYS Follow):**',
        '**Anti-Patterns (NEVER Do):**',
        '## **Useful Tension Protocol**',
        '## **Battle-Tested Case Studies**',
        '## **Analytical Tensions**'
    ];
    
    public function validate(string $content): array
    {
        $score = 100;
        $issues = [];
        
        // Check critical markers
        foreach ($this->criticalMarkers as $marker) {
            if (!str_contains($content, $marker)) {
                $issues[] = "Missing: {$marker}";
                $score -= 10;
            }
        }
        
        // Check for unreplaced variables
        if (preg_match('/\{\{[^}]+\}\}/', $content)) {
            $issues[] = "Unreplaced mustache variables found";
            $score -= 20;
        }
        
        // Check for minimum content
        if (strlen($content) < 2000) {
            $issues[] = "Content too short";
            $score -= 10;
        }
        
        return [
            'valid' => $score >= 90,
            'score' => max(0, $score),
            'issues' => $issues
        ];
    }
}
```

---

## Phase 4: Exception Handling

### Overview
Clear exceptions for debugging and monitoring.

### Changes Required:

#### 1. Create Custom Exception
**File**: `app/Exceptions/TemplateComplianceException.php`

```php
<?php

namespace App\Exceptions;

use Exception;

class TemplateComplianceException extends Exception
{
    protected array $details;
    
    public function __construct(string $message, array $details = [])
    {
        parent::__construct($message);
        $this->details = $details;
        
        \Log::error('Template Compliance Failed', [
            'message' => $message,
            'details' => $details
        ]);
    }
    
    public function getDetails(): array
    {
        return $this->details;
    }
}
```

---

## Testing Strategy

### Unit Tests:
- JSON mode validation
- Mustache rendering
- Compliance scoring
- Retry logic

### Integration Tests:
- Full PK generation with retries
- Exception handling
- Compliance validation

### Manual Testing:
1. Generate PK for test advisor
2. Check logs to see retry attempts
3. Verify final output has correct structure
4. Test with different temperature settings
5. Confirm exceptions thrown after max attempts

---

## Implementation Timeline

### Day 1 (3-4 hours):
1. Install Mustache (15 min)
2. Add JSON mode support (1 hour)
3. Implement basic generatePK with retry (1 hour)
4. Create validator (30 min)
5. Test with one advisor (30 min)
6. Debug and adjust (30 min)

### Day 2 (2-3 hours):
1. Add exception handling (30 min)
2. Improve retry prompt escalation (1 hour)
3. Add logging and monitoring (30 min)
4. Test with multiple advisors (30 min)
5. Document findings (30 min)

---

## Key Insights

### Why This Works:
1. **Realistic Expectations**: Accepts that LLMs aren't perfect
2. **Progressive Enforcement**: Each retry uses stronger language
3. **Safety Net**: Mustache ensures structure even if LLM partially complies
4. **Clear Failure**: After max attempts, fails loudly

### Expected Outcomes:
- 70-80% success on first attempt
- 90-95% success after 2 attempts
- 95-99% success after 3 attempts
- 1-5% require manual intervention

### The Critical Realization:
**We were trying to prevent the LLM from being creative. The better approach is to detect when it was creative and try again with stronger instructions.**

---

## Monitoring and Improvement

### Metrics to Track:
- Success rate by attempt number
- Most common compliance failures
- Average compliance score
- Failure patterns by model

### Continuous Improvement:
1. Analyze failure patterns
2. Adjust prompt language based on what works
3. Consider model-specific prompts
4. Fine-tune retry escalation

---

## Rollback Plan

If this approach doesn't work:
1. Revert to original generation
2. Manually fix emphasis markers post-generation
3. Consider fine-tuning a model specifically for template compliance
4. Explore alternative templating engines

---

## Summary

This simplified approach combines three key technologies:

### 1. **Structured Output (JSON Schema)**
- Forces the LLM to return valid JSON
- Prevents adding extra fields with `additionalProperties: false`
- Enforces minimum content lengths
- Guarantees all required variables are present

### 2. **Mustache Templating**
- Renders the template with the JSON variables
- Preserves exact structure and formatting
- Acts as a safety net if LLM partially complies

### 3. **Retry Logic with Escalation**
- Accepts that LLMs aren't perfect
- Uses progressively stricter language
- Fails loudly after max attempts

The combination ensures:
- **Structured output** prevents the LLM from returning free-form text
- **Mustache** guarantees template structure preservation
- **Retry logic** handles the reality that LLMs sometimes ignore instructions

This is simple, maintainable, and achieves good results without over-engineering.