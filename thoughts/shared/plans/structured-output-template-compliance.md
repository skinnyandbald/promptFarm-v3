# Structured Output for Template Compliance Implementation Plan

## Overview

Implement structured output support in the LLM service to ensure generated content exactly matches our template structures, particularly preserving critical emphasis markers like "Voice Anchor (CRITICAL - STUDY THIS)" that signal importance to both LLMs and humans.

## Current State Analysis

The PK generation process currently has a critical flaw where carefully designed templates with emphasis markers are completely ignored in favor of hardcoded prompts, resulting in loss of structural requirements and emphasis that make advisors effective.

### Key Discoveries:
- Template `meta_pk_template_v1.md` defines exact structure with emphasis markers (`AdvisorGenerationService.php:380`)
- `buildEnhancedGenerationPrompt` receives but ignores the processed template (`AdvisorGenerationService.php:610`)
- OpenRouter supports structured output via `response_format` parameter (OpenRouter API docs)
- Models like `x-ai/grok-3` have `json_mode: true` capability (`config/ai-models.php:110-136`)
- Emphasis markers (CAPS, bold, parentheses) are critical for LLM attention but currently lost

## Desired End State

After implementation, the PK generation will:
1. Use structured output to guarantee exact template section compliance
2. Preserve all emphasis markers from templates in generated content
3. Validate generated content against template requirements
4. Provide fallback to template-based generation for non-JSON models

Verification: Generated PKs will contain exact headers like "## **Voice Anchor (CRITICAL - STUDY THIS)**" instead of plain "## Voice Anchor"

## What We're NOT Doing

- NOT changing the template structure itself
- NOT modifying PI generation (separate concern)
- NOT implementing structured output for all LLM calls (only where template compliance is critical)
- NOT requiring all models to support structured output (will provide fallback)

## Implementation Approach

Use a three-layer approach:
1. Add structured output support to LLMService
2. Create JSON schemas from template requirements
3. Update PK generation to use structured output with template-based fallback

## Phase 1: Add Structured Output Support to LLMService

### Overview
Enable the LLMService to send `response_format` parameters to OpenRouter and handle structured responses.

### Changes Required:

#### 1. Update LLMService OpenRouter Integration
**File**: `app/Services/LLMService.php`
**Changes**: Add response_format support to generateTextWithOpenRouter method

```php
// Around line 481-495, modify the JSON payload
$response = $this->httpClient->post('https://openrouter.ai/api/v1/chat/completions', [
    'json' => [
        'model' => $model,
        'messages' => [
            [
                'role' => 'system',
                'content' => $systemMessage,
            ],
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ],
        'temperature' => $temperature,
        'max_tokens' => $maxTokens,
        // Add structured output support
        'response_format' => $options['response_format'] ?? null,
    ],
    // ... headers remain the same
]);
```

#### 2. Add Model Capability Validation
**File**: `app/Services/LLMService.php`
**Changes**: Validate JSON mode support before using structured output

```php
// Add after line 464 (after model selection)
if (isset($options['response_format'])) {
    $responseFormat = $options['response_format'];
    
    if ($responseFormat['type'] === 'json_schema' || $responseFormat['type'] === 'json_object') {
        $capabilities = config("ai-models.capabilities.{$model}");
        if (!($capabilities['json_mode'] ?? false)) {
            Log::warning("Model {$model} does not support JSON mode, falling back to text");
            unset($options['response_format']);
        }
    }
}
```

#### 3. Handle Structured Responses
**File**: `app/Services/LLMService.php`
**Changes**: Parse JSON responses when structured output is used

```php
// After line 511 (after getting content)
// If structured output was requested, validate and parse JSON
if (isset($options['response_format']) && 
    in_array($options['response_format']['type'], ['json_schema', 'json_object'])) {
    
    $jsonContent = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        Log::error('Structured output returned invalid JSON', [
            'error' => json_last_error_msg(),
            'content' => substr($content, 0, 500)
        ]);
        throw new \Exception('Invalid JSON in structured output response');
    }
    
    // If schema validation is needed, it would go here
    // For now, return the JSON as string for backward compatibility
    $content = json_encode($jsonContent, JSON_PRETTY_PRINT);
}
```

### Success Criteria:

#### Automated Verification:
- [ ] Unit test for response_format parameter passing: `php artisan test --filter LLMServiceTest`
- [ ] Integration test with OpenRouter (mock): `php artisan test --filter OpenRouterIntegrationTest`
- [ ] Validation test for non-JSON models: `php artisan test --filter ModelCapabilityTest`

#### Manual Verification:
- [ ] Test with JSON-capable model returns valid JSON
- [ ] Test with non-JSON model falls back gracefully
- [ ] Verify no regression in existing PI/PK generation

---

## Phase 2: Create PK Structure Schema

### Overview
Define a JSON schema that enforces the exact template structure with emphasis markers.

### Changes Required:

#### 1. Create Schema Definition Class
**File**: `app/Services/Schema/PKStructureSchema.php` (new file)
**Changes**: Define the structured output schema for PK

```php
<?php

namespace App\Services\Schema;

class PKStructureSchema
{
    public static function getSchema(): array
    {
        return [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'advisor_pk',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'voice_anchor' => [
                            'type' => 'object',
                            'properties' => [
                                'section_title' => [
                                    'type' => 'string',
                                    'const' => '## **Voice Anchor (CRITICAL - STUDY THIS)**'
                                ],
                                'voice_dna' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'label' => ['type' => 'string', 'const' => '**Voice DNA:**'],
                                        'content' => ['type' => 'string', 'minLength' => 50]
                                    ],
                                    'required' => ['label', 'content']
                                ],
                                'voice_examples' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'label' => ['type' => 'string', 'const' => '**Voice Examples (STUDY THESE):**'],
                                        'examples' => [
                                            'type' => 'array',
                                            'minItems' => 3,
                                            'maxItems' => 3,
                                            'items' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'topic' => ['type' => 'string'],
                                                    'quote' => ['type' => 'string', 'minLength' => 20]
                                                ],
                                                'required' => ['topic', 'quote']
                                            ]
                                        ]
                                    ],
                                    'required' => ['label', 'examples']
                                ],
                                'patterns' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'label' => ['type' => 'string', 'const' => '**Patterns (ALWAYS Follow):**'],
                                        'items' => [
                                            'type' => 'array',
                                            'minItems' => 5,
                                            'items' => ['type' => 'string']
                                        ]
                                    ],
                                    'required' => ['label', 'items']
                                ],
                                'anti_patterns' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'label' => ['type' => 'string', 'const' => '**Anti-Patterns (NEVER Do):**'],
                                        'items' => [
                                            'type' => 'array',
                                            'minItems' => 5,
                                            'items' => ['type' => 'string']
                                        ]
                                    ],
                                    'required' => ['label', 'items']
                                ]
                            ],
                            'required' => ['section_title', 'voice_dna', 'voice_examples', 'patterns', 'anti_patterns']
                        ],
                        // Additional sections would follow similar pattern...
                    ],
                    'required' => ['voice_anchor']
                ]
            ]
        ];
    }
    
    public static function formatJsonToMarkdown(array $jsonContent): string
    {
        $markdown = [];
        
        // Voice Anchor section
        if (isset($jsonContent['voice_anchor'])) {
            $va = $jsonContent['voice_anchor'];
            $markdown[] = $va['section_title'];
            $markdown[] = '';
            $markdown[] = $va['voice_dna']['label'] . ' ' . $va['voice_dna']['content'];
            $markdown[] = '';
            $markdown[] = $va['voice_examples']['label'];
            $markdown[] = '';
            
            foreach ($va['voice_examples']['examples'] as $example) {
                $markdown[] = "*On {$example['topic']}:* \"{$example['quote']}\"";
                $markdown[] = '';
            }
            
            $markdown[] = $va['patterns']['label'];
            foreach ($va['patterns']['items'] as $pattern) {
                $markdown[] = "- {$pattern}";
            }
            $markdown[] = '';
            
            $markdown[] = $va['anti_patterns']['label'];
            foreach ($va['anti_patterns']['items'] as $antiPattern) {
                $markdown[] = "- {$antiPattern}";
            }
        }
        
        return implode("\n", $markdown);
    }
}
```

### Success Criteria:

#### Automated Verification:
- [ ] Schema validation test: `php artisan test --filter PKStructureSchemaTest`
- [ ] JSON to Markdown conversion test: `php artisan test --filter SchemaFormatterTest`

#### Manual Verification:
- [ ] Schema enforces exact header text with emphasis
- [ ] Schema requires minimum content lengths
- [ ] Formatted output preserves all emphasis markers

---

## Phase 3: Update PK Generation to Use Templates Properly

### Overview
Fix the PK generation to actually use the template content and structured output when available.

### Changes Required:

#### 1. Fix buildEnhancedGenerationPrompt to Use Template
**File**: `app/Services/AdvisorGenerationService.php`
**Changes**: Use the template parameter that's currently being ignored

```php
protected function buildEnhancedGenerationPrompt(string $type, string $template, array $advisorData, array $voicePatterns): string
{
    $advisorName = $advisorData['full_name'] ?? $advisorData['name'] ?? 'Unknown Advisor';
    
    // Add known positions constraint
    $knownPositions = $this->getKnownAdvisorPositions($advisorData['slug'] ?? 'default');
    
    // NEW: Actually use the template!
    return <<<PROMPT
Generate {$type} content for {$advisorName} following this EXACT template structure.

CRITICAL: You MUST preserve ALL formatting, including:
- Bold text marked with ** **
- CAPITAL LETTERS for emphasis
- Parenthetical instructions like (CRITICAL - STUDY THIS)
- Exact section headers as shown

ACCURACY CONSTRAINTS:
{$knownPositions}

TEMPLATE TO FOLLOW:
{$template}

Fill in all {{placeholders}} with appropriate content while maintaining the exact structure and emphasis markers shown above.
PROMPT;
}
```

#### 2. Add Structured Output Support to generatePK
**File**: `app/Services/AdvisorGenerationService.php`
**Changes**: Use structured output when model supports it

```php
// Around line 423, before calling generateText
$modelCapabilities = config("ai-models.capabilities.{$model}");
$useStructuredOutput = $modelCapabilities['json_mode'] ?? false;

if ($useStructuredOutput) {
    // Use structured output for guaranteed format compliance
    $generatedContent = $this->llmService->generateText($prompt, [
        'model' => $model,
        'temperature' => $temperature,
        'max_tokens' => config('ai-models.settings.pk_generation.max_tokens'),
        'system_message' => 'Generate structured advisor knowledge following the exact schema provided.',
        'response_format' => \App\Services\Schema\PKStructureSchema::getSchema()
    ]);
    
    // Convert JSON response to markdown
    $jsonContent = json_decode($generatedContent, true);
    if ($jsonContent) {
        $generatedContent = \App\Services\Schema\PKStructureSchema::formatJsonToMarkdown($jsonContent);
    }
} else {
    // Fallback to template-based generation
    $generatedContent = $this->llmService->generateText($prompt, [
        'model' => $model,
        'temperature' => $temperature,
        'max_tokens' => config('ai-models.settings.pk_generation.max_tokens'),
        'system_message' => 'You are a brutally honest business advisor. Preserve ALL formatting and emphasis from the template.'
    ]);
}
```

### Success Criteria:

#### Automated Verification:
- [ ] PK generation test with structured output: `php artisan test --filter PKGenerationStructuredTest`
- [ ] PK generation test with template fallback: `php artisan test --filter PKGenerationTemplateTest`
- [ ] Emphasis marker preservation test: `php artisan test --filter EmphasisPreservationTest`
- [ ] Quality validation passes: `php artisan test --filter PKQualityTest`

#### Manual Verification:
- [ ] Generated PKs contain "Voice Anchor (CRITICAL - STUDY THIS)" header
- [ ] Bold markers (**) are preserved in output
- [ ] CAPS emphasis words remain capitalized
- [ ] Template structure is followed exactly
- [ ] Content quality remains high

---

## Phase 4: Post-Generation Validation Hook

### Overview
Add a post-generation validation hook that automatically checks if the generated content adheres to the template structure and emphasis markers.

### Changes Required:

#### 1. Create Template Compliance Validator
**File**: `app/Services/Validation/TemplateComplianceValidator.php` (new file)
**Changes**: Create dedicated validator for template structure compliance

```php
<?php

namespace App\Services\Validation;

use Illuminate\Support\Facades\Log;

class TemplateComplianceValidator
{
    private array $requiredMarkers = [
        'voice_anchor_title' => '## **Voice Anchor (CRITICAL - STUDY THIS)**',
        'voice_dna_label' => '**Voice DNA:**',
        'voice_examples_label' => '**Voice Examples (STUDY THESE):**',
        'patterns_label' => '**Patterns (ALWAYS Follow):**',
        'anti_patterns_label' => '**Anti-Patterns (NEVER Do):**',
        'tension_protocol_title' => '## **Useful Tension Protocol**',
        'challenge_threshold_label' => '**Challenge Threshold:**',
    ];
    
    /**
     * Validate that generated content follows template structure
     */
    public function validate(string $content, string $templateName = 'meta_pk_template_v1'): array
    {
        $issues = [];
        $warnings = [];
        $score = 100;
        
        // Check each required marker
        foreach ($this->requiredMarkers as $key => $marker) {
            if (!str_contains($content, $marker)) {
                $issues[] = "Missing required marker: {$marker}";
                $score -= 10;
                
                // Check for common mistakes
                $plainVersion = str_replace(['**', '(', ')'], '', $marker);
                if (str_contains($content, $plainVersion)) {
                    $warnings[] = "Found plain version without emphasis: {$plainVersion}";
                }
            }
        }
        
        // Check for voice examples structure
        if (!preg_match('/\*On .+:\* ".+"/', $content)) {
            $issues[] = 'Missing proper voice example format (*On topic:* "quote")';
            $score -= 5;
        }
        
        // Check for minimum content in critical sections
        if (str_contains($content, '**Voice DNA:**')) {
            $voiceDnaMatch = preg_match('/\*\*Voice DNA:\*\*\s*(.+)/', $content, $matches);
            if ($voiceDnaMatch && strlen(trim($matches[1])) < 50) {
                $warnings[] = 'Voice DNA content too short (< 50 characters)';
                $score -= 5;
            }
        }
        
        // Log validation results
        if (!empty($issues)) {
            Log::warning('Template compliance validation failed', [
                'template' => $templateName,
                'issues' => $issues,
                'warnings' => $warnings,
                'score' => $score
            ]);
        }
        
        return [
            'valid' => empty($issues),
            'score' => max(0, $score),
            'issues' => $issues,
            'warnings' => $warnings,
            'required_markers_found' => $this->countFoundMarkers($content),
            'required_markers_total' => count($this->requiredMarkers)
        ];
    }
    
    private function countFoundMarkers(string $content): int
    {
        $found = 0;
        foreach ($this->requiredMarkers as $marker) {
            if (str_contains($content, $marker)) {
                $found++;
            }
        }
        return $found;
    }
    
    /**
     * Auto-fix common issues (optional enhancement)
     */
    public function attemptAutoFix(string $content): array
    {
        $fixed = $content;
        $fixes = [];
        
        // Fix missing emphasis on Voice Anchor
        if (str_contains($fixed, '## Voice Anchor') && !str_contains($fixed, '## **Voice Anchor')) {
            $fixed = str_replace('## Voice Anchor', '## **Voice Anchor (CRITICAL - STUDY THIS)**', $fixed);
            $fixes[] = 'Added emphasis to Voice Anchor header';
        }
        
        // Fix missing bold on labels
        $labelsToFix = [
            'Voice DNA:' => '**Voice DNA:**',
            'Voice Examples:' => '**Voice Examples (STUDY THESE):**',
            'Patterns:' => '**Patterns (ALWAYS Follow):**',
            'Anti-Patterns:' => '**Anti-Patterns (NEVER Do):**',
        ];
        
        foreach ($labelsToFix as $plain => $emphasized) {
            if (str_contains($fixed, $plain) && !str_contains($fixed, $emphasized)) {
                $fixed = str_replace($plain, $emphasized, $fixed);
                $fixes[] = "Added emphasis to {$plain}";
            }
        }
        
        return [
            'content' => $fixed,
            'fixes_applied' => $fixes,
            'was_modified' => !empty($fixes)
        ];
    }
}
```

#### 2. Integrate Validation Hook into Generation
**File**: `app/Services/AdvisorGenerationService.php`
**Changes**: Add post-generation validation

```php
// Add after line 432 (after validateAndCleanContent)
// Validate template compliance
$complianceValidator = new \App\Services\Validation\TemplateComplianceValidator();
$complianceResult = $complianceValidator->validate($cleanedContent, 'meta_pk_template_v1');

if (!$complianceResult['valid']) {
    Log::warning('Generated PK failed template compliance', [
        'advisor' => $advisorData['name'] ?? 'unknown',
        'compliance_score' => $complianceResult['score'],
        'issues' => $complianceResult['issues']
    ]);
    
    // Optionally attempt auto-fix
    if (config('advisors.auto_fix_template_compliance', false)) {
        $autoFix = $complianceValidator->attemptAutoFix($cleanedContent);
        if ($autoFix['was_modified']) {
            Log::info('Applied template compliance auto-fixes', [
                'fixes' => $autoFix['fixes_applied']
            ]);
            $cleanedContent = $autoFix['content'];
            
            // Re-validate after fixes
            $complianceResult = $complianceValidator->validate($cleanedContent, 'meta_pk_template_v1');
        }
    }
    
    // If still invalid after fixes, consider regeneration
    if (!$complianceResult['valid'] && $attempts < $maxAttempts) {
        Log::info('Regenerating due to template compliance failure');
        continue; // Try again with next attempt
    }
}

// Store compliance score for quality tracking
$metadata['template_compliance_score'] = $complianceResult['score'];
```

### Success Criteria:

#### Automated Verification:
- [ ] Validation catches missing emphasis markers: `php artisan test --filter TemplateComplianceTest`
- [ ] Auto-fix correctly adds emphasis: `php artisan test --filter TemplateAutoFixTest`
- [ ] Validation hook triggers on generation: `php artisan test --filter PostGenerationHookTest`

#### Manual Verification:
- [ ] Generation logs show compliance validation results
- [ ] Non-compliant content triggers regeneration (if configured)
- [ ] Auto-fixes are applied when enabled

---

## Phase 5: Integration Testing Strategy

### Overview
Since we're dealing with actual LLM responses, traditional unit tests cannot validate the real behavior. Instead, we need integration tests that can verify the post-generation hook works correctly and monitoring tools to track compliance in production.

### Changes Required:

#### 1. Create Integration Test for Post-Generation Hook
**File**: `tests/Feature/TemplateComplianceIntegrationTest.php` (new file)
**Changes**: Test the validation hook with mock content

```php
<?php

namespace Tests\Feature;

use App\Services\Validation\TemplateComplianceValidator;
use Tests\TestCase;

class TemplateComplianceIntegrationTest extends TestCase
{
    /**
     * Test that compliance validator correctly identifies missing emphasis
     */
    public function test_validator_catches_missing_emphasis_markers()
    {
        $validator = new TemplateComplianceValidator();
        
        // Test content with plain headers (no emphasis)
        $badContent = "## Voice Anchor\nSome content here\nVoice DNA: Missing bold";
        
        $result = $validator->validate($badContent);
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Missing required marker: ## **Voice Anchor (CRITICAL - STUDY THIS)**', $result['issues']);
        $this->assertLessThan(100, $result['score']);
    }
    
    /**
     * Test that auto-fix correctly adds emphasis
     */
    public function test_auto_fix_adds_missing_emphasis()
    {
        $validator = new TemplateComplianceValidator();
        
        $content = "## Voice Anchor\nVoice DNA: Not bold\nPatterns: Missing emphasis";
        
        $fixed = $validator->attemptAutoFix($content);
        
        $this->assertTrue($fixed['was_modified']);
        $this->assertStringContainsString('## **Voice Anchor (CRITICAL - STUDY THIS)**', $fixed['content']);
        $this->assertStringContainsString('**Voice DNA:**', $fixed['content']);
        $this->assertStringContainsString('**Patterns (ALWAYS Follow):**', $fixed['content']);
    }
    
    /**
     * Test that valid content passes validation
     */
    public function test_properly_formatted_content_passes_validation()
    {
        $validator = new TemplateComplianceValidator();
        
        $goodContent = <<<CONTENT
## **Voice Anchor (CRITICAL - STUDY THIS)**

**Voice DNA:** I'm a test advisor with proper emphasis markers.

**Voice Examples (STUDY THESE):**

*On strategy:* "This is a properly formatted example quote."

**Patterns (ALWAYS Follow):**
- Pattern one
- Pattern two

**Anti-Patterns (NEVER Do):**
- Anti-pattern one
- Anti-pattern two
CONTENT;
        
        $result = $validator->validate($goodContent);
        
        $this->assertTrue($result['valid']);
        $this->assertEquals(100, $result['score']);
        $this->assertEmpty($result['issues']);
    }
}
```

#### 2. Add Compliance Monitoring Command
**File**: `app/Console/Commands/MonitorTemplateCompliance.php` (new file)
**Changes**: Command to check existing PKs for compliance

```php
<?php

namespace App\Console\Commands;

use App\Models\Advisor;
use App\Services\Validation\TemplateComplianceValidator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MonitorTemplateCompliance extends Command
{
    protected $signature = 'advisor:check-compliance 
                            {--advisor= : Check specific advisor}
                            {--fix : Attempt to auto-fix issues}';
    
    protected $description = 'Check existing advisor PKs for template compliance';
    
    public function handle(TemplateComplianceValidator $validator)
    {
        $advisorSlug = $this->option('advisor');
        $attemptFix = $this->option('fix');
        
        $advisors = $advisorSlug 
            ? Advisor::where('slug', $advisorSlug)->get()
            : Advisor::all();
        
        $results = [];
        
        foreach ($advisors as $advisor) {
            $pkPath = "advisors/{$advisor->slug}/PK.md";
            
            if (!Storage::exists($pkPath)) {
                $this->warn("PK not found for {$advisor->name}");
                continue;
            }
            
            $content = Storage::get($pkPath);
            $compliance = $validator->validate($content);
            
            $results[$advisor->slug] = [
                'name' => $advisor->name,
                'valid' => $compliance['valid'],
                'score' => $compliance['score'],
                'issues' => count($compliance['issues']),
                'warnings' => count($compliance['warnings'])
            ];
            
            if (!$compliance['valid']) {
                $this->error("❌ {$advisor->name}: Score {$compliance['score']}%");
                foreach ($compliance['issues'] as $issue) {
                    $this->line("   - {$issue}");
                }
                
                if ($attemptFix) {
                    $fixed = $validator->attemptAutoFix($content);
                    if ($fixed['was_modified']) {
                        Storage::put($pkPath, $fixed['content']);
                        $this->info("   ✅ Applied fixes: " . implode(', ', $fixed['fixes_applied']));
                    }
                }
            } else {
                $this->info("✅ {$advisor->name}: Score {$compliance['score']}%");
            }
        }
        
        // Summary
        $this->newLine();
        $this->table(
            ['Advisor', 'Valid', 'Score', 'Issues', 'Warnings'],
            collect($results)->map(fn($r, $slug) => [
                $r['name'],
                $r['valid'] ? '✅' : '❌',
                $r['score'] . '%',
                $r['issues'],
                $r['warnings']
            ])->values()
        );
        
        $validCount = collect($results)->where('valid', true)->count();
        $totalCount = count($results);
        
        $this->info("Overall compliance: {$validCount}/{$totalCount} advisors valid");
        
        return $validCount === $totalCount ? 0 : 1;
    }
}
```

#### 3. Add Compliance Metrics to Generation Jobs
**File**: `database/migrations/xxxx_add_compliance_score_to_advisor_generation_jobs.php` (new file)
**Changes**: Track compliance scores in database

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('advisor_generation_jobs', function (Blueprint $table) {
            $table->integer('template_compliance_score')->nullable()->after('quality_report');
            $table->json('compliance_issues')->nullable()->after('template_compliance_score');
            $table->boolean('auto_fixed')->default(false)->after('compliance_issues');
        });
    }
    
    public function down()
    {
        Schema::table('advisor_generation_jobs', function (Blueprint $table) {
            $table->dropColumn(['template_compliance_score', 'compliance_issues', 'auto_fixed']);
        });
    }
};
```

### Success Criteria:

#### Automated Verification:
- [ ] Integration tests pass: `php artisan test --filter TemplateComplianceIntegrationTest`
- [ ] Monitoring command works: `php artisan advisor:check-compliance --advisor=alex-bogusky`
- [ ] Database migration runs: `php artisan migrate`

#### Manual Verification:
- [ ] Post-generation hook logs compliance scores
- [ ] Non-compliant content is detected and logged
- [ ] Auto-fix works when enabled
- [ ] Monitoring command provides useful compliance report

---

## Testing Strategy

### Unit Tests:
- LLMService response_format parameter handling
- Schema generation and validation
- JSON to Markdown conversion
- Template variable substitution
- Emphasis marker preservation

### Integration Tests:
- End-to-end PK generation with structured output
- Fallback to template-based generation
- OpenRouter API integration with response_format
- Quality validation of generated content

### Manual Testing Steps:
1. Generate PK for test advisor with structured output enabled
2. Verify "Voice Anchor (CRITICAL - STUDY THIS)" appears exactly
3. Check all bold formatting is preserved
4. Validate CAPS emphasis words remain
5. Compare against template to ensure structure match
6. Test with model that doesn't support JSON mode for fallback
7. Run quality validation to ensure scores reflect compliance

## Performance Considerations

- Structured output may increase token usage slightly due to JSON formatting
- Schema validation adds minimal overhead (< 50ms)
- Caching compiled schemas could improve performance if needed
- Consider batching multiple generations to amortize schema overhead

## Migration Notes

- Existing PKs should be regenerated to include proper emphasis markers
- No database changes required
- Backward compatible - old generation still works as fallback
- Can be rolled out incrementally per advisor

## References

- Template structure: `resources/advisor-templates/meta_pk_template_v1.md`
- Current implementation: `app/Services/AdvisorGenerationService.php:377-450`
- OpenRouter API docs: https://openrouter.ai/docs/structured-outputs
- Related research: `docs/lessons-learned.md` (Voice Anchor importance)