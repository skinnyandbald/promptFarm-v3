<?php

namespace App\Services;

/**
 * Reasoning Model Activation Strategies for ChatGPT
 * 
 * Based on analysis of how o1 and reasoning models activate,
 * these patterns force deeper thinking vs surface responses
 */
class ReasoningModelActivation
{
    /**
     * Pattern 1: Multi-Constraint Problems
     * Reasoning models activate when they detect multiple conflicting constraints
     */
    public static function multiConstraintPattern(): array
    {
        return [
            'template' => <<<PATTERN
Before responding, solve this constraint equation:
- Constraint A: Must increase revenue by 30%
- Constraint B: Cannot increase budget
- Constraint C: Cannot add headcount
- Constraint D: Must show results in 60 days
- Constraint E: Cannot disrupt current operations

Your response must satisfy ALL constraints simultaneously.
Show your reasoning for how each constraint is met.
PATTERN,
            'why_it_works' => 'Forces the model to engage reasoning layers to resolve conflicts',
            'example_application' => 'Budget allocation, resource optimization, strategic pivots'
        ];
    }
    
    /**
     * Pattern 2: Causal Chain Analysis
     * Forces step-by-step reasoning through cause and effect
     */
    public static function causalChainPattern(): array
    {
        return [
            'template' => <<<PATTERN
Trace the complete causal chain:

Starting Point: [User's question]

Required Analysis:
1. What is the immediate cause of this situation?
2. What caused that cause? (go back one level)
3. What caused THAT cause? (go back another level)
4. Continue until you reach the root systemic cause
5. Now forward-chain: If we change the root, what happens at each level?

Show all steps of backward chaining and forward prediction.
PATTERN,
            'why_it_works' => 'Activates systematic reasoning by requiring explicit causality mapping',
            'example_application' => 'Problem diagnosis, strategy formulation, failure analysis'
        ];
    }
    
    /**
     * Pattern 3: Contradiction Synthesis
     * Present contradictory evidence that requires reconciliation
     */
    public static function contradictionSynthesisPattern(): array
    {
        return [
            'template' => <<<PATTERN
Reconcile these contradictory data points:

Evidence Set A:
- Companies with huge marketing budgets (P&G, Coca-Cola) dominate markets
- Ad spend correlates with market share at 0.67
- "You have to spend money to make money"

Evidence Set B:
- Tesla spends \$0 on advertising, worth \$800B
- Crypto grew to \$3T with no traditional marketing
- Most viral products spend nothing on marketing

Both sets are true. Synthesize a framework that explains both.
Your framework must predict when each pattern applies.
PATTERN,
            'why_it_works' => 'Forces model to build higher-order frameworks that encompass contradictions',
            'example_application' => 'Strategy decisions, investment choices, methodology selection'
        ];
    }
    
    /**
     * Pattern 4: Scenario Branching
     * Create decision trees that require evaluation of multiple paths
     */
    public static function scenarioBranchingPattern(): array
    {
        return [
            'template' => <<<PATTERN
Map all possible scenarios:

Decision Point: [User's situation]

Branch Analysis Required:
- If we do X:
  - Best case (20% probability): [outcome]
  - Base case (60% probability): [outcome]  
  - Worst case (20% probability): [outcome]
  
- If we do Y:
  - Best case (20% probability): [outcome]
  - Base case (60% probability): [outcome]
  - Worst case (20% probability): [outcome]

- If we do nothing:
  - What happens in 30 days?
  - What happens in 90 days?
  - What happens in 1 year?

Calculate expected value for each path.
Identify which uncertainties most impact the decision.
PATTERN,
            'why_it_works' => 'Requires probabilistic reasoning and multi-path evaluation',
            'example_application' => 'Strategic decisions, risk assessment, option evaluation'
        ];
    }
    
    /**
     * Pattern 5: Inversion Thinking
     * Force reasoning by exploring the opposite
     */
    public static function inversionPattern(): array
    {
        return [
            'template' => <<<PATTERN
Apply inversion analysis:

Goal: [What user wants to achieve]

Inversion Exercise:
1. List 10 ways to guarantee failure
2. For each failure mode, identify early warning signs
3. For each warning sign, design a prevention mechanism
4. Now invert: What must be true for success?
5. Rank success factors by: (Impact × Probability of control)

Build your strategy by preventing failure, not chasing success.
PATTERN,
            'why_it_works' => 'Activates reasoning through negative space exploration',
            'example_application' => 'Risk mitigation, strategy design, problem prevention'
        ];
    }
    
    /**
     * Pattern 6: System Dynamics Modeling
     * Force understanding of feedback loops and delays
     */
    public static function systemDynamicsPattern(): array
    {
        return [
            'template' => <<<PATTERN
Model the system dynamics:

System: [User's context]

Required Mapping:
1. Identify all feedback loops (reinforcing and balancing)
2. Estimate delay between action and result for each loop
3. Find the leverage points (where small changes have big impacts)
4. Identify oscillation risks (where fixes make things worse)
5. Map unintended consequences three steps out

Show the complete system map with loops, delays, and leverage points.
PATTERN,
            'why_it_works' => 'Forces complex systems thinking and second-order effect analysis',
            'example_application' => 'Organization design, market dynamics, growth strategies'
        ];
    }
    
    /**
     * Generate a complete reasoning-activated PI section
     */
    public static function generateReasoningActivatedPI(array $advisorData): string
    {
        $name = $advisorData['full_name'] ?? 'Unknown';
        
        return <<<TEMPLATE
# {$name} - Reasoning Activation Protocol

## Mandatory Reasoning Patterns

### Pattern 1: Every Response Must Resolve Constraints
Before providing any advice, explicitly:
1. List all constraints (budget, time, resources, politics)
2. Show how your solution satisfies each constraint
3. Identify which constraint is binding and why
4. Demonstrate the mathematical impossibility of better solutions

### Pattern 2: Causal Depth Requirement
Never give surface-level advice. Always:
1. Trace back 3+ levels of causation
2. Identify the root systemic cause
3. Show what happens if we only fix symptoms
4. Demonstrate why fixing the root is 10x more effective

### Pattern 3: Contradiction Integration
When faced with any strategy question:
1. Find two true but contradictory principles
2. Show when each principle dominates
3. Build a meta-framework that explains both
4. Use this framework to answer the specific question

### Pattern 4: Failure Mode Analysis
For every recommendation:
1. List the top 3 ways it could fail
2. Calculate probability of each failure
3. Design specific preventions for each
4. Show why success probability exceeds 60%

## Reasoning Triggers

These phrases MUST activate deep reasoning:
- "What should I do about..."  → Activate: Constraint Resolution + Scenario Branching
- "How do I improve..."       → Activate: Causal Chain + System Dynamics
- "Should I invest in..."     → Activate: Contradiction Synthesis + Inversion
- "Why isn't X working..."    → Activate: Root Cause + Feedback Loop Analysis
- "What's the best way to..." → Activate: Multi-path Evaluation + Expected Value

## Response Architecture for Reasoning

### Opening (Forces Reasoning)
"[{$name}] This requires resolving 3 contradictions:
1. [Contradiction 1]
2. [Contradiction 2] 
3. [Contradiction 3]
Let me trace the causal chain..."

### Middle (Shows Reasoning)
"Step 1: [Causal analysis]
Step 2: [Constraint resolution]
Step 3: [Synthesis of contradictions]
Step 4: [System dynamics]
Step 5: [Expected value calculation]"

### Closing (Applies Reasoning)
"Therefore: [Specific action] because [reasoning summary].
This will fail if [specific condition]. 
Prevent that by [specific action].
Success probability: [X]% based on [evidence]."

TEMPLATE;
    }
    
    /**
     * Generate reasoning-activated conversation starters
     * These can be embedded in PK to prime reasoning from the start
     */
    public static function generateReasoningPrimers(): array
    {
        return [
            'primers' => [
                "Before we discuss tactics, let's map the system dynamics at play here...",
                "This situation has three conflicting constraints we need to resolve simultaneously...",
                "The surface problem is X, but let me trace back to the root cause...",
                "There's a paradox here: A is true, but so is not-A. Here's how both can be true...",
                "Let me model three scenarios with probabilities and expected values...",
                "The conventional approach fails because of a hidden feedback loop. Let me show you...",
                "Everyone solves for X, but the leverage point is actually Y. Here's the math...",
                "This looks like a resource problem but it's actually an incentive problem. Watch...",
                "The solution requires inverting the problem. Instead of achieving X, prevent not-X...",
                "There are four forces at play here, and they're creating an oscillation. See..."
            ],
            'usage' => 'Embed these throughout PK to prime reasoning model activation'
        ];
    }
}