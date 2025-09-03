# Product Requirements Document: Advisor Reference Diversity System

## Executive Summary

The AI Advisor system currently suffers from repetitive example usage, where advisors repeatedly reference the same 3-4 examples from their personal portfolio rather than demonstrating broader industry knowledge. This PRD outlines a system to generate, store, and utilize diverse reference pools during advisor generation, improving the authenticity and value of advisor responses.

## Problem Statement

### Current Issue
When using AI advisors (particularly in ChatGPT), advisors repeatedly reference the same personal achievements (e.g., Bogusky always mentions Domino's, Truth Campaign, CP+B) instead of demonstrating broader industry expertise through varied examples.

### User Impact
- **Reduced Credibility**: Advisors appear one-dimensional and less knowledgeable
- **Lower Engagement**: Users get bored hearing the same examples repeatedly
- **Decreased Value**: Missing insights from contemporary work and industry trends
- **FOMBA (Fear Of Missing Better Advice)**: Users worry they're getting generic, limited perspectives

### Business Impact
- Reduced user satisfaction with advisor quality
- Lower perceived value of the advisor generation system
- Potential loss of competitive advantage to systems with richer, more varied responses

## Solution Overview

Implement a **Reference Diversity System** that:
1. Researches and generates diverse example pools during advisor creation
2. Stores reference pools in the database as part of advisor records
3. Injects varied references during the PI/PK generation process
4. Enforces diversity rules to prevent repetition

## Functional Requirements

### 1. Database Schema Enhancement

#### 1.1 New Fields for Advisors Table
```sql
- core_portfolio (JSON): 3-5 advisor's personal major achievements
- observed_campaigns (JSON): 8-10 contemporary campaigns they'd reference
- industry_failures (JSON): 5-6 relevant failures with lessons
- historical_precedents (JSON): 4-5 classic campaigns establishing principles
- contemporary_references (JSON): Recent work from last 2 years
- parallel_industries (JSON): 4-5 examples from adjacent industries
```

#### 1.2 Data Structure Example
```json
{
  "core_portfolio": [
    {
      "name": "Domino's Pizza Turnaround",
      "year": 2009,
      "metrics": "14% growth, $1.5B market cap increase",
      "lesson": "Radical transparency beats spin"
    }
  ],
  "observed_campaigns": [
    {
      "name": "Liquid Death",
      "agency": "Internal",
      "year": 2023,
      "relevance": "Enemy-based positioning in commodity market"
    }
  ]
}
```

### 2. Reference Generation During Advisor Creation

#### 2.1 Research Phase
- **When**: During initial advisor creation workflow
- **Method**: LLM-powered research using advisor's expertise domain
- **Model**: Use fast model (Grok-3) for research, ~5-10 seconds
- **Validation**: Ensure examples are real, verifiable, and relevant

#### 2.2 Research Prompting Requirements
Generate examples that are:
- **Specific**: Include company names, dates, metrics
- **Verifiable**: Real campaigns/companies, not hypotheticals
- **Relevant**: Connected to advisor's expertise domain
- **Contemporary**: Include work from last 24 months
- **Diverse**: Span different companies, time periods, approaches

### 3. Integration with Generation Pipeline

#### 3.1 PI Enhancement Integration
Modify `enhancePIWithExamples()` to:
- Load reference pools from database
- Include reference diversity instructions in enhancement prompt
- Enforce "max 1 core portfolio example" rule
- Require 2-3 contemporary/observed examples

#### 3.2 PK Generation Integration
Modify `buildEnhancedGenerationPrompt()` to:
- Include full reference pools in context
- Add diversity requirements to prompt
- Enforce pattern recognition across different example types
- Prevent same example appearing multiple times in document

### 4. Reference Diversity Rules

#### 4.1 Distribution Requirements
Per advisor response:
- **Core Portfolio**: Maximum 1 reference
- **Contemporary/Observed**: 2-3 references
- **Historical/Failures**: 1-2 references
- **Time Diversity**: At least 1 example from last 2 years

#### 4.2 Anti-Repetition Logic
- Track example usage within single document
- Rotate between reference categories
- Prioritize unused examples from pools
- Flag if falling back to same examples

### 5. Management Features

#### 5.1 Reference Refresh Command
```bash
php artisan advisor:refresh-references {advisor_key}
```
- Updates contemporary_references with recent examples
- Refreshes observed_campaigns for current relevance
- Maintains core_portfolio (doesn't change)

#### 5.2 Manual Override Capability
- Ability to add/remove specific references via admin panel
- Mark certain references as "featured" or "avoid"
- Set expiration dates for time-sensitive examples

## Non-Functional Requirements

### Performance
- Reference generation: < 10 seconds during advisor creation
- No impact on PI/PK generation time (remains 2-3 seconds)
- Database queries optimized with proper indexing

### Scalability
- Support 50+ reference examples per advisor
- Handle 100+ advisors without performance degradation
- Efficient JSON storage and retrieval

### Quality
- References must be factually accurate
- Examples should demonstrate clear principles
- Contemporary references updated quarterly

## Implementation Plan

### Phase 1: Database & Core Generation (Week 1)
1. Create migration for new reference fields
2. Implement `generateReferencePoolsForAdvisor()` method
3. Update advisor creation flow to populate references
4. Test with 2-3 advisors

### Phase 2: Generation Integration (Week 2)
1. Modify PI enhancement to use reference pools
2. Update PK generation with diversity requirements
3. Implement anti-repetition logic
4. Quality testing with existing advisors

### Phase 3: Management Tools (Week 3)
1. Build refresh command for updating references
2. Create admin interface for reference management
3. Add monitoring for reference usage patterns
4. Documentation and training

## Success Metrics

### Quantitative
- **Reference Diversity Score**: > 0.7 (unique examples / total examples)
- **Core Portfolio Usage**: < 20% of total references
- **Contemporary Reference Rate**: > 30% from last 2 years
- **Generation Success Rate**: > 95% without errors

### Qualitative
- User feedback on advisor authenticity
- Reduced complaints about repetitive examples
- Increased engagement with advisor responses
- Higher perceived expertise and knowledge breadth

## Technical Considerations

### Dependencies
- OpenRouter API for reference research
- Database JSON field support
- Existing AdvisorGenerationService architecture

### Risks & Mitigations
| Risk | Impact | Mitigation |
|------|--------|------------|
| Invalid/outdated references | High | Quarterly refresh process, fact-checking during generation |
| Increased token usage | Medium | Use efficient models (Grok-3), cache reference pools |
| Breaking existing advisors | High | Backward compatibility, gradual rollout |
| Reference quality issues | Medium | Validation checks, manual review process |

## Alternative Approaches Considered

### 1. Runtime Reference Generation
- **Rejected because**: Too slow, expensive, inconsistent results

### 2. Static Configuration Files
- **Rejected because**: Hard to maintain, not scalable, no personalization

### 3. Simple Prompt Instructions
- **Rejected because**: Insufficient control, doesn't solve core problem

## Questions for Development Team

1. Should we implement versioning for reference pools to track changes over time?
2. Do we need an approval workflow for newly generated references?
3. Should references be shared across similar advisors or kept unique?
4. How should we handle advisors in non-business domains (e.g., philosophy, science)?
5. Should we expose reference pools in the advisor export for ChatGPT?

## Appendix A: Example Reference Pool for Bogusky

```json
{
  "core_portfolio": [
    "Domino's Pizza Turnaround (2009) - 14% growth",
    "Truth Campaign vs Big Tobacco (2000) - 22% smoking reduction",
    "Burger King Subservient Chicken (2004) - 14M visitors",
    "MINI Cooper US Launch (2002) - 50k units year 1"
  ],
  "observed_campaigns": [
    "Liquid Death 'Murder Your Thirst' (2023)",
    "Patagonia 'Don't Buy This Jacket' (2011)",
    "REI #OptOutside Black Friday (2015)",
    "Old Spice Response Campaign (2010)",
    "Nike 'Dream Crazy' Kaepernick (2018)",
    "Wendy's Twitter Roasts (2017-present)",
    "Aviation Gin Reynolds Sale (2020)",
    "Oatly 'Milk Made for Humans' (2021)"
  ],
  "industry_failures": [
    "Pepsi Kendall Jenner Protest Ad (2017) - $5M waste",
    "Bud Light Dylan Mulvaney (2023) - 25% sales drop",
    "Facebook Metaverse Pivot (2021) - $13B loss",
    "CNN+ Streaming Service (2022) - $300M, 3 weeks",
    "Quibi Mobile Platform (2020) - $1.75B, 6 months"
  ]
}
```

## Appendix B: Sample Diversity-Enhanced Response

**Before** (Current State):
> [Alex Bogusky] When I turned Domino's around by admitting the pizza sucked, we grew 14%. The same principle I used at CP+B with the Truth campaign. It's what I always say about finding the enemy...

**After** (With Reference Diversity):
> [Alex Bogusky] The principle of radical honesty works across categories. When I pushed Domino's to admit their pizza sucked, we grew 14%. But look at Liquid Death - they're doing the same thing right now, making fun of their own product category. Even Wendy's Twitter strategy follows this playbook - they roast their own old menu items. The pattern is clear: brands that can laugh at themselves win trust...

---

*Document Version: 1.0*  
*Date: 2025-09-01*  
*Author: AI Advisor System Team*  
*Status: For Review*