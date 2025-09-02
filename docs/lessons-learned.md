# Lessons Learned - PromptFarm Advisor Generation

## Date: 2025-09-01

## Prompt Structure Testing: Identifying Formalization Causes

### Test Overview
We conducted A/B/C testing to identify which prompt structural elements cause AI responses to become formalized vs maintaining authentic voice.

### Test Variables
- **Original V2**: No Voice Anchor, No Framework - just analytical tensions
- **Test A**: Voice Anchor + Communication Rules, NO Framework  
- **Test B**: Voice Anchor + Framework, NO Communication Rules
- **Test C**: Voice Anchor ONLY

### Test Results

#### Quantitative Metrics
| Test | Confrontational | Numbered Lists | Questions | Companies | 1st Person | Academic Tone |
|------|----------------|---------------|-----------|-----------|------------|---------------|
| Original V2 | 0 | 12 | 15 | 7 | 39 | 1 |
| Test A | 4 | 0 | 11 | 9 | 71 | 2 |
| Test B | 6 | 15 | 11 | 10 | 65 | 4 |
| Test C | 5 | 0 | 11 | 10 | 62 | 1 |

#### Key Finding: Primary Framework Causes Formalization
- **Test B (WITH Framework)** showed worst formalization: 15 numbered lists, 4 academic tone markers
- **Test A & C (NO Framework)** maintained authenticity: 0 numbered lists, minimal academic tone
- **Voice Anchor** is essential for maintaining consistent personality across responses

### Qualitative Assessment

#### Original V2 (Best Response)
- **Strengths**: Raw, confrontational, specific examples, natural flow
- **Voice**: Authentic Bogusky - uses phrases like "spits out safe, middle-of-the-road pablum"
- **Structure**: Organic analytical tensions without rigid formatting
- **Example Quote**: "ChatGPT is a glorified autocomplete that spits out safe, middle-of-the-road pablum"

#### Test A (Second Best)
- **Strengths**: Maintains voice with communication rules, no rigid structure
- **Voice**: Strong personality with phrases like "Blunt as hell. I say 'bullshit' when it's bullshit"
- **Structure**: Natural flow despite having communication rules
- **Weakness**: Slightly more meta-commentary about structure

#### Test B (Worst - Most Formalized)
- **Problems**: Rigid framework creates academic tone
- **Structure**: Heavy use of numbered lists, step-by-step methodology
- **Voice**: Lost authenticity in favor of structured methodology
- **Example**: "Step 1: Identify the Cultural Fault Line" - too procedural

#### Test C (Good Balance)
- **Strengths**: Voice Anchor alone maintains personality
- **Structure**: Natural, conversational flow
- **Voice**: Strong opening declaration maintains throughout
- **Best Practice**: Minimal structure, maximum authenticity

## Grok Model Selection & Configuration

### Model Performance Comparison
- **Grok-4**: 2-4 minutes per generation, high quality but impractical
- **Grok-3**: 5-10 seconds per generation, same quality as Grok-4
- **Grok-beta**: Deprecated, replaced by Grok-3

### Optimal Configuration
```php
$model = 'x-ai/grok-3';  // Fast, high quality
$temperature = 0.9;       // High creativity for authentic voice
$max_tokens = 4000;       // Sufficient for detailed responses
```

### OpenRouter Integration
- **Benefit**: Unified API for all models (Grok, Claude, OpenAI, etc.)
- **Endpoint**: https://openrouter.ai/api/v1/chat/completions
- **Key Advantage**: No need for multiple API integrations

## Prompt Engineering Best Practices

### What Works
1. **Voice Anchor**: 3-4 sentence first-person declaration establishing identity
2. **Analytical Tensions**: Paradox → Evidence → Constraint → Causation → Truth
3. **Specific Examples**: Real campaigns with numbers (e.g., "Subservient Chicken got 500M views")
4. **Confrontational Language**: Direct challenges to conventional wisdom
5. **Natural Language**: Avoid structured frameworks in prompts

### What Doesn't Work
1. **Primary Frameworks**: Step-by-step methodologies cause academic tone
2. **Communication Rules**: Over-specification leads to meta-commentary
3. **Numbered Lists in Prompts**: Trigger formal, structured responses
4. **Generic Instructions**: "Be creative" or "think outside the box"
5. **Quality Scores**: Measuring template compliance instead of actual effectiveness

## Production Implementation

### Current Approach
```php
// Minimal structure for authentic voice
$pkPrompt = "Generate Project Knowledge for {$advisor}...
CRITICAL: Use analytical tension to reveal uncomfortable truths...

## Voice Anchor
[3-4 sentence identity declaration]

## Analytical Tensions
[Paradox/Evidence/Constraint/Causation/Truth structure]

Write in first person as {$advisor}. Be specific. Name names. Show receipts.";
```

### Key Changes Made
1. Removed Primary Framework section entirely
2. Removed Communication Format Rules
3. Kept Voice Anchor as essential element
4. Preserved analytical tension structure from V2
5. Added "uncomfortable truths" emphasis

## Quality Measurement Issues

### Problem
- Quality scores measured template compliance, not effectiveness
- High scores correlated with generic, safe responses
- Low scores (V2 tension) actually performed better in ChatGPT

### Solution
- Focus on qualitative assessment of responses
- Measure confrontational tone, specificity, authenticity
- Test advisors in actual ChatGPT conversations
- User feedback is more valuable than automated scoring

## Temperature Settings

### Critical Discovery (2025-09-01)
**Temperature 0.9 causes hallucinations and errors:**
- Name corruption: "Gary Halbert" → "Gary Nodelbert"
- Token repetition: "I I I I I I I I I"
- Random characters: Korean/Chinese characters appearing

### Optimal Temperature by Advisor Type
- **Technical (Henderson)**: 0.7 - Precision and accuracy critical
- **Copywriting (Halbert)**: 0.7 - Name accuracy and clean copy essential
- **Business (Hormozi)**: 0.8 - Balance of data accuracy and personality
- **Creative (Bogusky)**: 0.85 - Can handle higher creativity without breaking

### How Temperature Works
- **Range**: 0.0 to 2.0 (typically 0.0-1.0 used)
- **0.0**: Deterministic, always picks most probable token
- **0.7**: Balanced creativity and coherence
- **0.9+**: High randomness, can cause hallucinations
- **Effect**: Controls probability distribution for next token selection

## Future Considerations

### For All Advisors
1. **Bogusky**: Confrontational, creative disruption focus
2. **Hormozi**: Data-driven intensity, growth obsession
3. **Halbert**: Direct response, psychological triggers
4. **Henderson**: Technical depth, systems thinking

### Each Needs
- Voice Anchor for consistency
- Unique analytical tensions relevant to their expertise
- Specific examples from their actual work
- Minimal structural constraints

## Critical Insight
**"Quality scores are bullshit"** - They measure the wrong things. Real quality comes from:
- Authentic voice that challenges thinking
- Specific examples and evidence
- Confrontational insights that make users uncomfortable
- Natural, conversational flow vs rigid structure

## Summary
The key to maintaining authentic AI advisor voices is **minimal structure, maximum personality**. Voice Anchors establish identity, analytical tensions drive insight, but rigid frameworks kill authenticity. Grok-3 with high temperature and OpenRouter integration provides the optimal technical stack.

## Advisor-Specific Observations (2025-09-01)

### How Minimal Structure Works Across Expertise Types

#### Alex Bogusky (Creative/Advertising)
- **Natural Voice**: Strong confrontational tone maintained throughout
- **Structure**: Analytical tensions work perfectly for his contrarian style
- **Lists**: Zero numbered lists - stays conversational and raw
- **Authenticity**: "If it doesn't make you nervous, it's not worth doing"
- **Result**: Minimal structure enhances his rebellious voice

#### Alex Hormozi (Business/Growth)
- **Natural Voice**: Data-driven intensity comes through clearly
- **Structure**: Still uses analytical tensions effectively despite being numbers-focused
- **Lists**: Minimal lists, focuses on evidence and metrics
- **Authenticity**: "I don't care about your feelings, I care about your numbers"
- **Result**: Works well - his natural style is already direct and structured

#### Gary Halbert (Copywriting/Sales)
- **Issues**: Generation had typos and broken words (e.g., "II've", "themthem")
- **Voice**: Direct response focus clear but quality issues
- **Structure**: Analytical tensions translate well to sales/copywriting domain
- **Lists**: Some natural bullet points emerge for sales points
- **Result**: Needs quality improvement but structure approach works

#### Cal Henderson (Engineering/Technical)
- **Natural Voice**: Technical precision with personality: "Ship small, measure everything"
- **Structure**: Uses three-level causation effectively for systems thinking
- **Lists**: Natural emergence of numbered sublists for technical concepts
- **Authenticity**: "Complexity is the silent killer of scalable systems"
- **Result**: Works excellently - technical expertise naturally creates some structure without forcing it

### Key Insights

1. **Voice Anchor Works Universally**: All advisors benefit from 3-4 sentence identity declaration
2. **Analytical Tensions Are Flexible**: Work across creative, business, and technical domains
3. **Natural Structure Emerges**: Experts like Hormozi naturally create some structure without forcing it
4. **Quality Control Issues**: Need to refine generation quality checks (typos, false positives)
5. **One Attempt Is Sufficient**: Multiple attempts don't improve quality scores meaningfully
6. **Domain-Specific Tensions Critical**: Each advisor needs tensions relevant to their expertise (e.g., Cal shouldn't discuss McKinsey, should discuss microservices)
7. **Language Must Match Domain**: Section headers should adapt to expertise (e.g., "My Engineering Decisions" not "My Campaigns" for technical advisors)
8. **PI Drives Actual Behavior, Not PK**: The PI (Instructions) is what ChatGPT uses during conversations. PK provides context but PI determines how the advisor actually responds. Focus enhancement efforts on PI.
9. **Cross-Functional Perspectives Matter**: Advisors aren't one-dimensional. Cal Henderson isn't just technical - he's product-minded. These secondary perspectives need to be embedded in PI to affect actual advice quality.

### PI Enhancement Strategy

**Key Discovery**: To change how advisors actually respond in ChatGPT:
1. Focus on PI (Instructions), not PK (Knowledge)
2. Embed secondary perspectives directly in PI enhancement prompt
3. Keep it simple - add one line of guidance per advisor
4. Examples:
   - Cal: "measures engineering success by user impact, not system elegance"
   - Bogusky: "uses creativity to solve business problems, not just make pretty ads"
   - Hormozi: "every decision filters through 'What's the leverage here?'"
   - Halbert: "everything must drive immediate action - no fluff, just conversion"

This minimal approach avoids adding complex configuration while ensuring advisors maintain their unique multi-dimensional perspectives.

### Final Assessment

The minimal structure approach (Voice Anchor + Analytical Tensions) works universally across all expertise types:
- **Creative experts** (Bogusky) stay raw and confrontational
- **Business experts** (Hormozi) maintain data-driven intensity
- **Sales experts** (Halbert) keep direct response focus
- **Technical experts** (Henderson) naturally organize without losing personality

The key discovery: **Let the expert's natural voice determine structure**, don't impose it. Some experts naturally use lists (engineers), others naturally avoid them (creatives). The Voice Anchor ensures consistency while analytical tensions provide depth without formalization.