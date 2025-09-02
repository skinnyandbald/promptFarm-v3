<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\TemplateService;
use Illuminate\Support\Facades\File;

class TemplateServiceTest extends TestCase
{
    protected TemplateService $templateService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templateService = new TemplateService();
    }

    public function test_loading_templates_with_different_versions()
    {
        // Arrange
        File::shouldReceive('exists')
            ->once()
            ->with(resource_path('advisor-templates/test_template_v1.md'))
            ->andReturn(true);
            
        File::shouldReceive('get')
            ->once()
            ->with(resource_path('advisor-templates/test_template_v1.md'))
            ->andReturn('Template v1 content');

        // Act
        $content = $this->templateService->loadTemplate('test_template', 'v1');

        // Assert
        $this->assertEquals('Template v1 content', $content);
    }

    public function test_extracting_all_variables_from_complex_templates()
    {
        // Arrange
        $template = <<<MD
# {{advisor_name}} — Project Instruction

## Context
You ARE {{advisor_name}}. Your expertise is {{core_expertise}}.

## Voice DNA
Communication style: {{communication_style}}
Key phrases: {{key_phrases}}

## Nested Variables
Even in code blocks:
```
function {{function_name}}() {
    return "{{return_value}}";
}
```
MD;

        // Act
        $variables = $this->templateService->extractVariables($template);

        // Assert
        $expected = [
            'advisor_name',
            'core_expertise',
            'communication_style',
            'key_phrases',
            'function_name',
            'return_value'
        ];
        
        // Sort both arrays for comparison
        sort($variables);
        sort($expected);
        
        $this->assertEquals($expected, $variables);
    }

    public function test_detecting_html_comments_that_need_llm_processing()
    {
        // Arrange
        $template = <<<MD
# Template

## Section 1
<!-- This needs LLM processing for examples -->

## Section 2
Regular content here

## Section 3
<!-- Another comment for LLM enhancement -->
More content
<!-- Multi-line comment
that spans multiple lines
for complex instructions -->
MD;

        // Act
        $comments = $this->templateService->extractHTMLComments($template);

        // Assert
        $this->assertCount(3, $comments);
        $this->assertEquals('This needs LLM processing for examples', $comments[0]['content']);
        $this->assertEquals('Another comment for LLM enhancement', $comments[1]['content']);
        $this->assertStringContainsString('Multi-line comment', $comments[2]['content']);
    }

    public function test_validating_template_structure_and_required_sections()
    {
        // Arrange
        $validTemplate = <<<MD
# Voice Anchor
Content here

# Primary Framework
Framework details

# Core Operating Principles
- Principle 1
- Principle 2

# Chain-of-Thought
Thinking process

# Few-Shot Priming
Examples

# Expertise Integration
Integration details
MD;

        $invalidTemplate = <<<MD
# Some Section
Content

# Another Section
More content
MD;

        // Act
        $validResult = $this->templateService->validateTemplateStructure($validTemplate);
        $invalidResult = $this->templateService->validateTemplateStructure($invalidTemplate);

        // Assert
        $this->assertTrue($validResult['valid']);
        $this->assertEmpty($validResult['issues']);
        
        $this->assertFalse($invalidResult['valid']);
        $this->assertNotEmpty($invalidResult['issues']);
        $this->assertStringContainsString('Missing required section', $invalidResult['issues'][0]);
    }

    public function test_handling_malformed_templates()
    {
        // Arrange
        $malformedTemplate = <<<MD
# Template with issues

## Unclosed variable {{advisor_name

## Multiple issues
{{var1}} and {{var2}}
<!-- Unclosed HTML comment

## More content
{{var3}}
MD;

        // Act
        $variables = $this->templateService->extractVariables($malformedTemplate);
        $hasUnsubstituted = $this->templateService->hasUnsubstitutedVariables($malformedTemplate);
        
        // Assert
        $this->assertEquals(['var1', 'var2', 'var3'], $variables);
        $this->assertTrue($hasUnsubstituted);
    }

    public function test_template_metadata_extraction_from_yaml_frontmatter()
    {
        // Arrange
        $templateWithMetadata = <<<MD
---
template_type: "meta_pi"
template_version: "v1.0.0"
description: "Test template"
validation:
  min_lines: 100
  max_lines: 300
---
# Template Content

Content goes here
MD;

        $templateWithoutMetadata = <<<MD
# Template Content

No frontmatter here
MD;

        // Act
        $metadata = $this->templateService->getTemplateMetadata($templateWithMetadata);
        $noMetadata = $this->templateService->getTemplateMetadata($templateWithoutMetadata);

        // Assert
        $this->assertEquals('meta_pi', $metadata['template_type']);
        $this->assertEquals('v1.0.0', $metadata['template_version']);
        $this->assertEquals('Test template', $metadata['description']);
        $this->assertEquals(100, $metadata['validation']['min_lines']);
        
        $this->assertEmpty($noMetadata);
    }

    public function test_variable_substitution_with_edge_cases()
    {
        // Arrange
        $template = <<<MD
# {{advisor_name}}

## Edge Cases
- Empty variable: {{empty_var}}
- Special chars: {{special_!@#}}
- Nested braces: {{{nested}}}
- Adjacent vars: {{var1}}{{var2}}
- In URL: https://example.com/{{path}}/page
MD;

        $variables = [
            'advisor_name' => 'Test Advisor',
            'empty_var' => '',
            'special_!@#' => 'Special Value',
            'nested' => 'Nested Value',
            'var1' => 'First',
            'var2' => 'Second',
            'path' => 'docs'
        ];

        // Act
        $result = $this->templateService->substituteVariables($template, $variables);

        // Assert
        $this->assertStringContainsString('# Test Advisor', $result);
        $this->assertStringContainsString('Empty variable: ', $result);
        $this->assertStringContainsString('Special chars: Special Value', $result);
        $this->assertStringContainsString('{Nested Value}', $result);
        $this->assertStringContainsString('FirstSecond', $result);
        $this->assertStringContainsString('https://example.com/docs/page', $result);
    }

    public function test_get_unsubstituted_variables()
    {
        // Arrange
        $template = <<<MD
# {{advisor_name}} Template

Substituted content here.

## Remaining Variables
- {{unsubstituted_1}}
- {{unsubstituted_2}}
- Duplicate: {{unsubstituted_1}}
MD;

        // Act
        $unsubstituted = $this->templateService->getUnsubstitutedVariables($template);

        // Assert
        $this->assertCount(3, $unsubstituted);
        $this->assertContains('advisor_name', $unsubstituted);
        $this->assertContains('unsubstituted_1', $unsubstituted);
        $this->assertContains('unsubstituted_2', $unsubstituted);
    }

    public function test_validate_template_with_required_variables()
    {
        // Arrange
        $template = '# {{title}}\n\nContent by {{author}} on {{date}}';
        $requiredVars = ['title', 'author', 'date', 'missing_var'];

        // Act
        $result = $this->templateService->validateTemplate($template, $requiredVars);

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertContains('missing_var', $result['missing']);
        $this->assertContains('title', $result['found']);
        $this->assertContains('author', $result['found']);
        $this->assertContains('date', $result['found']);
    }
}