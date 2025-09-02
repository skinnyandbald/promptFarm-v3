<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Validation\AdvisorQualityService;

class AdvisorQualityServiceTest extends TestCase
{
    protected AdvisorQualityService $qualityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->qualityService = new AdvisorQualityService();
    }

    public function test_content_with_missing_required_sections()
    {
        // Arrange
        $piContent = <<<MD
# Test Advisor — Project Instruction

## Context
You ARE Test Advisor.

## Core Operating Principles
- Principle 1
- Principle 2

## Response Quality Standards
- High quality responses
MD;

        // Act
        $result = $this->qualityService->scorePI($piContent);

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertLessThan(75, $result['percentage']);
        $this->assertContains('Missing section: ## **Voice Authenticity Anchors**', $result['issues']);
        $this->assertContains('Missing section: ## **Chain-of-Thought Conditioning**', $result['issues']);
    }

    public function test_content_below_line_limits()
    {
        // Arrange - Very short content
        $piContent = <<<MD
---
validation:
  min_lines: 100
  max_lines: 300
---
# Test Advisor
## Section 1
Content here
MD;

        // Act
        $result = $this->qualityService->scorePI($piContent);

        // Assert
        $this->assertContains('Content too short: 8 lines (minimum: 100)', $result['issues']);
    }

    public function test_content_above_line_limits()
    {
        // Arrange - Generate content with many lines
        $lines = array_fill(0, 350, 'Line of content');
        $pkContent = "---\nvalidation:\n  max_lines: 300\n---\n" . implode("\n", $lines);

        // Act
        $result = $this->qualityService->scorePK($pkContent);

        // Assert
        $this->assertContains('Content too long: 354 lines (maximum: 300)', $result['issues']);
    }

    public function test_content_with_remaining_template_placeholders()
    {
        // Arrange
        $piContent = <<<MD
# {{advisor_name}} — Project Instruction

## **Voice Authenticity Anchors**
Communication style: {{communication_style}}

## **Chain-of-Thought Conditioning**
Think step by step

## **Core Operating Principles**
- Principle 1

## **Few-Shot Behavioral Priming**
Examples here

## **Domain Expertise Boundaries**
Expertise areas

## **Response Quality Standards**
High quality
MD;

        // Act
        $result = $this->qualityService->scorePI($piContent);

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertContains('Found 2 unsubstituted placeholders', $result['issues']);
        $this->assertContains('Unsubstituted: {{advisor_name}}', $result['issues']);
        $this->assertContains('Unsubstituted: {{communication_style}}', $result['issues']);
    }

    public function test_content_with_remaining_html_comments()
    {
        // Arrange
        $piContent = <<<MD
# Test Advisor — Project Instruction

## **Voice Authenticity Anchors**
Content here

## **Chain-of-Thought Conditioning**
<!-- This needs to be replaced with actual examples -->

## **Core Operating Principles**
- Principle 1

## **Few-Shot Behavioral Priming**
<!-- Add few-shot examples here -->

## **Domain Expertise Boundaries**
Expertise

## **Response Quality Standards**
Standards
MD;

        // Act
        $result = $this->qualityService->scorePI($piContent);

        // Assert
        $this->assertLessThan(100, $result['percentage']);
        $this->assertContains('Found 2 unprocessed HTML comments', $result['issues']);
    }

    public function test_content_with_poor_first_person_voice_usage()
    {
        // Arrange
        $piContent = <<<MD
# Test Advisor — Project Instruction

## **Voice Authenticity Anchors**
The advisor would say this.

## **Chain-of-Thought Conditioning**
From the advisor's perspective, this is important.

## **Core Operating Principles**
As the advisor might suggest, these are key.

## **Few-Shot Behavioral Priming**
The advisor's approach would be this.

## **Domain Expertise Boundaries**
They would say this is important.

## **Response Quality Standards**
High quality standards
MD;

        // Act
        $result = $this->qualityService->scorePI($piContent);

        // Assert
        $metadata = $result['metadata']['voice'];
        $this->assertFalse($metadata['proper']);
        $this->assertGreaterThan(0, $metadata['violations']);
    }

    public function test_content_that_meets_all_quality_criteria()
    {
        // Arrange
        $piContent = <<<MD
# Test Advisor — Project Instruction

## **PK Guardrail**
Consult PK file.

## **Context**
You ARE the advisor.

## **Constitutional Identity Constraints**
Never break character.

## **Evidence-Based Prompt Engineering**
Use research-backed techniques.

## **Chain-of-Thought Conditioning**
I think step by step: 1) First, I identify the core problem. 2) Then I analyze the available evidence. 3) Finally, I develop a solution based on my experience with similar challenges. I discovered this approach when I built my first strategic framework. I created methodologies that I found to be most effective.

## **Few-Shot Behavioral Priming**
When I faced the market disruption at TechCorp in 2019, I implemented a three-phase response that resulted in 45% revenue growth. Here's how I approached it:
- Phase 1: Rapid assessment of market changes
- Phase 2: Strategic pivot with minimal resource allocation
- Phase 3: Scale successful experiments

## **Retrieval-Augmented Context**
Reference specific examples.

## **Constitutional AI Constraints**
Never provide advice without evidence.

## **Core Operating Principles**
- I always start with data-driven analysis
- I challenge assumptions with evidence
- I focus on measurable outcomes
- I build sustainable solutions
- I prioritize stakeholder alignment
- I iterate based on feedback

## **Voice Authenticity Anchors**
I speak with clarity and conviction. When I developed the breakthrough strategy at Company X, I learned that direct communication yields the best results.

## **Domain Expertise Boundaries**
My primary expertise lies in strategic transformation and organizational change. I defer on technical implementation details and legal compliance specifics.

## **Response Quality Standards**
I provide specific, actionable advice based on my documented experience. Every recommendation includes clear next steps with measurable outcomes.

Additional content to meet line requirements...
More detailed examples and case studies...
Specific metrics and outcomes from past engagements...
Framework applications and success stories...
MD;

        // Act
        $result = $this->qualityService->scorePI($piContent);

        // Assert
        $this->assertTrue($result['valid']);
        $this->assertGreaterThanOrEqual(75, $result['percentage']);
        $this->assertContains('All required sections present', $result['strengths']);
        $this->assertContains('No remaining template placeholders', $result['strengths']);
        $this->assertContains('Proper first-person voice maintained', $result['strengths']);
    }

    public function test_proper_parsing_of_template_validation_rules_from_yaml_frontmatter()
    {
        // Arrange
        $content = <<<MD
---
validation:
  min_lines: 150
  max_lines: 400
  required_sections:
    - Voice Anchor
    - Core Principles
---
# Content

## Voice Anchor
Content here

## Core Principles
Principles listed
MD;

        // Act - This is tested indirectly through scorePI/scorePK
        $result = $this->qualityService->scorePI($content);

        // Assert
        $this->assertArrayHasKey('lineCount', $result);
        $this->assertEquals(15, $result['lineCount']);
    }

    public function test_scoring_system_produces_consistent_results()
    {
        // Arrange
        $goodContent = str_repeat("Good content line\n", 150);
        $goodContent = "# Voice Anchor\n" . $goodContent;
        $goodContent .= "\n# Challenge & Acceptance Criteria\n";
        $goodContent .= "\n# Communication Format Rules\n";
        $goodContent .= "\n# Primary Framework\n";
        $goodContent .= "\n# Secondary Framework\n";
        $goodContent .= "\n# Battle-Tested Application\n";

        // Act - Score the same content multiple times
        $result1 = $this->qualityService->scorePK($goodContent);
        $result2 = $this->qualityService->scorePK($goodContent);

        // Assert
        $this->assertEquals($result1['score'], $result2['score']);
        $this->assertEquals($result1['percentage'], $result2['percentage']);
        $this->assertEquals($result1['valid'], $result2['valid']);
    }

    public function test_pk_content_with_sufficient_examples()
    {
        // Arrange
        $pkContent = <<<MD
# Test Advisor — Project Knowledge

## Voice Anchor
When I worked with Fortune 500 companies, I discovered that transformation requires both vision and execution. In my experience with TechCorp, we achieved 45% growth.

## Challenge & Acceptance Criteria
I never accept vague objectives. When a client says they need "innovation," I push for specific outcomes.

## Communication Format Rules
I communicate with data-driven precision.

## Primary Framework
My strategic framework has four pillars, tested across 50+ implementations.

## Secondary Framework
The supporting framework addresses risk mitigation.

## Battle-Tested Application
In my work with GlobalCorp, this framework resulted in \$10M savings. The implementation took 6 months and achieved 120% of target metrics.
MD;

        // Act
        $result = $this->qualityService->scorePK($pkContent);

        // Assert
        $this->assertArrayHasKey('examples', $result['metadata']);
        $this->assertTrue($result['metadata']['examples']['sufficient']);
        $this->assertContains('Good use of specific examples and cases', $result['strengths']);
    }

    public function test_overall_quality_score_calculation()
    {
        // Arrange
        $piScore = [
            'score' => 75,
            'percentage' => 75,
            'valid' => true,
            'issues' => [],
            'strengths' => [],
            'lineCount' => 150
        ];

        $pkScore = [
            'score' => 85,
            'percentage' => 85,
            'valid' => true,
            'issues' => [],
            'strengths' => [],
            'lineCount' => 200
        ];

        // Act
        $overall = $this->qualityService->calculateQualityScore($piScore, $pkScore);

        // Assert
        $this->assertEquals(80, $overall['overall']);
        $this->assertEquals(75, $overall['pi']);
        $this->assertEquals(85, $overall['pk']);
        $this->assertTrue($overall['valid']);
        $this->assertEquals('Good quality - minor improvements recommended', $overall['recommendation']);
    }
}