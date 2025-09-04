# PI A/B/C Testing Implementation Plan

**Date**: 2025-09-03  
**Status**: Ready for Implementation  
**Purpose**: Systematic testing framework to optimize Project Instructions (PI) for improved conversational quality

## Executive Summary

Systematic testing framework to optimize Project Instructions (PI) for improved conversational quality, based on empirical evidence from `/docs/implementation/advisor-generation-lessons-learned.md` and AI embodiment research.

## Three PI Variations & Hypotheses

### Variation A: "Invisible Density Engine" 
**Hypothesis**: Internal word budgets create conversational density while maintaining natural flow
- **Internal Constraint**: "Respond like you're talking to someone face-to-face. Keep it tight: sharp opening (≤25 words), meaty insight with proof (≤75 words), clear next move (≤50 words). Natural paragraphs, no labels, no structure markers."
- **Expected Impact**: 40% reduction in response length, 60% increase in actionable insights, zero visible templating
- **Theory**: Conversational constraints work invisibly, user experiences natural dialogue

**Example Flow:**
Instead of:
> **Gist:** Your brand awareness campaign is backwards.  
> **Why:** Most B2B campaigns...  
> **Next:** Here's what you do...

It would be:
> Your brand awareness campaign is backwards. 
> 
> Most B2B campaigns chase impressions when they should chase arguments. I proved this with Mini Cooper - we got 80% sales lift by making the car's smallness into cultural rebellion, not hiding it. 
> 
> Start by naming what your competitors are too scared to say about your industry, then build your entire campaign around that uncomfortable truth.

### Variation B: "Pure Voice Anchor"
**Hypothesis**: Minimal structure with strong identity produces most authentic responses  
- **Structure**: Only Voice Anchor (3-4 sentences) + Constitutional constraints, no frameworks
- **Expected Impact**: Zero numbered lists, 80% increase in signature phrase usage
- **Theory**: Less instruction = more personality expression (validated by 0 vs 15 numbered lists data)

### Variation C: "Constitutional Density" 
**Hypothesis**: Constitutional AI boundaries + word budgets create reliable quality floor
- **Design**: Voice Anchor + Self-Critique Protocol + "Each section ≤2 sentences"
- **Expected Impact**: 90% consistency in boundary enforcement, 50% reduction in generic advice
- **Theory**: AI self-regulation prevents drift while density rules maintain value

## Controlled Testing Methodology

### Test Infrastructure

**1. Generate Three PI Files**
```bash
# Create testing directory structure
mkdir -p storage/app/testing/pi-comparison/{variation-a,variation-b,variation-c}

# Copy latest advisor PK from database (default: alex-bogusky, can override with --advisor flag)
# Command will auto-detect latest job and copy both PI and PK files for comparison baseline
```

**2. Create Custom Artisan Command**
```bash
php artisan make:command PIComparisonTest
```

**3. Test Prompt Library**

**Default Test Prompts** (runs all unless --prompt flag provided):

**Prompt A: "AI Advisor Tool Development"**
"Hey guys, here's a product that I'm looking to build for myself. The problem I have, and I think others have too, is like I've been creating and experimenting with different LLM advisors to give me feedback on various topics. I want these advisors to be experts and often reference real historical figures, but give me genuine advice aligned with me. Right now, I have 6 different advisors, so basically 6 different ChatGPT projects. Sometimes, what I'll do is record a voice memo in the morning and want feedback or advice from one of these advisors. Part of the problem I have is, I don't know if I'm leaving a lot on the table. I feel like I could probably be getting better advice and feedback if I tweaked the prompt, but I don't know what to do to the prompt. I also have examples of different prompts that people have said, like "I think people have given examples of, Oh, you should be using this as your performance coach for software." It's not super easy for me to go in and it's relatively tedious for you to go in and test to see, "Well, are you actually getting a better result than my current advisor?" And frankly just want to rerun that new potential advisor against several other trends. Several of the previous questions have asked where I can compare it to the answers that my other sort of working advisor in that situation offers. And one bill experiment with new advisors. But I think the key thing here is there's a lot of questions that historically asked, and what I want is to be able to quickly see does changing the AI model improve the output? More frequently, it's a different like a completely different or slightly tweaked like prompt instruction or project instruction yield a better result for me as a person. I don't know how much of this can be anecdotal evidence vs. I tried and someone else suggested doing like real pair battling using ELO and low cognitive load AV votes. I went down that path a few different times, and it just feels so scientific that I'm honestly not sure it's actually getting me much or actually helping with the thing that I want. What I'm really trying to do is put something together quickly that will be practically practical and useful for me, especially now that I'm experimenting with different approaches to creating deep advisors with deep project knowledge and/or hybrid advisors where I'm using a structure or approach that I've developed over the weekend that seems to yield much better advisors. And if I think is actually an interesting structure in a way that would make sense and be useful for other people but it needs to be done programmatically. This is about taking situations where being able to take historical transcripts and questions and compare the feedback from like with a new prompt to see if it's any better. I think part of the challenge there is often these prompts might need project knowledge behind them to be a meaningful comparison as opposed to just a prompt. But we could start with just a prompt, but I think it's important to consider that we will need that project knowledge behind it and it'll be specific to each prompt. I can go to greater detail of exactly what my current process is. But I think that's beyond the scope of what we're currently talking about. Let me know if you have any questions about what I'm asking about so you can clarify exactly what the goal is and what is V1 vs the roadmap? I'm really looking for guidance on how to approach this: to quickly build something that's functionally useful for me without it becoming like a science experiment (the last few times I've tried to do this it has). Also, honing in on what's the interesting thing here? And what's the thing that's actually going to be useful? For more than who knows that it'll be useful for 6 months, 3 months, or years? I think this is something that actually would genuinely be helpful for me, especially if we could get it into a stage where I could use it in ChatGPT projects, Claude, or a standalone web app that can take in inputs and transcripts from my wearable device that records conversations or my digital note taker that consistent web hook when I'm in a meeting is going. I have a 3-4 different sources of essentially transcripts (some of them are voice memo, some of them are in-person meetings, some of them are recorded Zoom meetings) in all of them in different situations. I want to get feedback from one or multiple imaginary advisors. And then I wanna be experimenting with different prompts and see how would that other potential prompt yield a response. I think part of the challenge here is it's I'm not sure how often you can judge based off the one-off response vs. needing to have a little bit of a back-and-forth conversation with each prompt to see: - What questions does it ask? - How does it respond to my follow-ups Bogusky, I'm curious - first on what's the big idea here? Before we get into what the build would look like, let's nail down the idea."

**Prompt B: "SaaS Product Positioning"**
"Imagine I'm building an AI assistant for SaaS founders that auto-summarizes customer interviews. It pulls transcripts from Zoom, tags themes (feature requests, bugs, pricing objections), and spits out a weekly digest with charts. Right now, founders either skip interviews because they're time-consuming or let notes rot in Notion. My product costs $99/month, integrates with Zoom + Notion, and claims to cut interview synthesis from 5 hours to 15 minutes. I want to position and launch this so founders actually pay attention—what's the smartest way to do it so it doesn't get lumped in with all the other AI noise?"

**Custom Prompt Option:**
`--prompt="Custom prompt text here"` - Override defaults with single custom prompt

### Implementation Steps

**Phase 1: Setup (Day 1)**

```bash
# 1. Create PI variations based on research findings
php artisan make:command PIVariationGenerator
```

**Phase 2: Systematic Testing (Day 2)**

```bash
# 2. Generate responses for all PI variations with default prompt library
php artisan pi:compare-test \
  --advisor=alex-bogusky \
  --variations=all \
  --save-experiment-metadata

# Alternative: Single custom prompt override  
php artisan pi:compare-test \
  --advisor=alex-bogusky \
  --variations=all \
  --prompt="Custom scenario here..." \
  --save-experiment-metadata

# 3. Results folder structure automatically captures:
# - Experiment variables (3 PI variations × 2 default prompts = 6 total responses)
# - All test prompts used (or single custom prompt)  
# - Response outputs for each variation/prompt combination
# - Ready for comprehensive embodiment scoring analysis
```

**Phase 3: Measurement (Day 3)**

```bash
# 4. Run embodiment scoring on all variations and update results metadata  
php artisan pi:score-variations \
  --batch=latest \
  --update-results-with-scores \
  --generate-recommendations

# 5. This command will:
# - Score all PI variations using AIEmbodimentQualityScorer
# - Update experiment metadata with scores and breakdown
# - Generate actionable next steps based on results
# - Focus on dramatic improvements, not incremental gains
```

## Success Metrics

### Quantitative Measures
1. **Response Density**: Words per actionable insight
2. **Authenticity Score**: AI Embodiment Quality Score breakdown
3. **Structure Analysis**: Count of numbered lists, bullet points, academic language
4. **Signature Phrase Usage**: Frequency of persona-specific language patterns

### Qualitative Assessment (Primary Focus)
1. **Authenticity**: Does advisor speak/think like the actual person would?
2. **Actionability**: Advice rooted in real expertise with specific next steps
3. **Depth**: Insights go beyond surface-level; show deep domain knowledge  
4. **Research Accuracy**: Recommendations align with advisor's actual documented beliefs/methods

**Goal**: Dramatic improvements in advisor quality - authentic voice + actionable depth + accurate expertise

## Expected Outcomes by Variation

| Metric | Variation A (Density) | Variation B (Pure Voice) | Variation C (Constitutional) |
|--------|----------------------|-------------------------|----------------------------|
| Word Count | 150-200 words | 200-300 words | 175-225 words |
| Numbered Lists | 0-1 | 0 | 0-1 |
| Signature Phrases | 3-4 per response | 5-6 per response | 2-3 per response |
| AI Embodiment Score | 75-85 | 85-95 | 80-90 |
| User Implementation Time | <5 minutes | Variable | <10 minutes |

## Risk Mitigation

**Potential Issues:**
1. **Over-constraint**: Word limits kill personality
   - *Mitigation*: Test multiple word limit thresholds
2. **Under-specification**: Pure voice anchor lacks direction  
   - *Mitigation*: Include minimal behavioral triggers
3. **Context Loss**: Testing in isolation vs real conversations
   - *Mitigation*: Run follow-up conversation tests

## Implementation Commands Sequence

```bash
# Day 1: Setup
php artisan make:command PIVariationGenerator
php artisan make:command PIComparisonTest  
mkdir -p storage/app/testing/{pi-variations,results,analysis}

# Day 2: Generate & Test
php artisan pi:generate-variations alex-bogusky
php artisan pi:comparison-test --advisor=alex-bogusky --batch=standard-prompts

# Day 3: Analysis  
php artisan pi:analyze-results --comparison-batch=latest
php artisan pi:recommendation-report --output=storage/app/testing/final-report.md
```

## Decision Framework

**Winning Variation Criteria:**
1. **Primary**: Highest user satisfaction in blind testing
2. **Secondary**: Best AI Embodiment Quality Score
3. **Tertiary**: Optimal word efficiency ratio
4. **Qualifier**: Maintains Bogusky authenticity markers

**Rollout Plan:**
- **Phase 1**: Implement winning variation for Alex Bogusky only
- **Phase 2**: Apply learnings to other advisors (Hormozi, Halbert, Henderson)  
- **Phase 3**: Establish new PI template standards based on findings

## Evidence Base

### Historical Research Foundation
Based on `/docs/implementation/advisor-generation-lessons-learned.md`:
- **Framework kills authenticity**: Test B (WITH Framework) showed 15 numbered lists vs Test A & C (NO Framework) with 0 numbered lists
- **Voice Anchor essential**: Maintains consistent personality across responses  
- **Temperature optimization**: 0.7-0.85 range prevents hallucinations while preserving creativity
- **Original V2 performed best**: Raw, confrontational, specific examples, natural flow

### AI Embodiment Quality Research
From `/thoughts/shared/diary/2025-09-02-ai-embodiment-scoring-analysis.md`:
- **Hybrid scoring approach**: 35% static analysis, 65% LLM semantic analysis
- **Constitutional AI critical**: Biggest improvement opportunity (+40 points potential)
- **Score thresholds**: 80-90 range indicates "Strong embodiment, reliable behavior"
- **Observable behavior changes**: Higher scores correlate with more authentic, actionable responses

### Persona Consistency Research  
From `/docs/local-research/ai-persona-research.md`:
- **User perception vs technical authenticity**: Disagreement between user ratings and technical consistency
- **Believability multidimensional**: Behavior, intelligence, social engagement all matter
- **Authenticity ≠ perceived realness**: Users value warmth and relatability over strict personality consistency

## Next Steps

1. **Immediate**: Create PI variations based on this plan
2. **Short-term**: Implement testing infrastructure with artisan commands
3. **Medium-term**: Run systematic comparison testing
4. **Long-term**: Apply winning approach to all advisors

This plan provides controlled, measurable testing of the three core hypotheses while maintaining scientific rigor and practical implementation pathways.