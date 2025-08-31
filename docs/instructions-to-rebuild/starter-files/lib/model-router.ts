/**
 * Intelligent model routing based on query complexity
 * Add this on Day 3 to optimize model selection
 */

export interface ComplexityAnalysis {
  requiresReasoning: boolean;
  needsSpeed: boolean;
  isCoding: boolean;
  isCreative: boolean;
  estimatedTokens: number;
  suggestedModel: string;
}

/**
 * Analyze query complexity to determine best model
 */
export function analyzeComplexity(query: string): ComplexityAnalysis {
  const lowerQuery = query.toLowerCase();
  
  // Check for reasoning indicators
  const reasoningKeywords = [
    'analyze', 'compare', 'evaluate', 'explain why', 'break down',
    'step by step', 'pros and cons', 'trade-offs', 'decision',
    'strategy', 'plan', 'architect', 'design'
  ];
  const requiresReasoning = reasoningKeywords.some(kw => lowerQuery.includes(kw));
  
  // Check for speed requirements
  const speedKeywords = [
    'quick', 'brief', 'summary', 'tldr', 'short', 'simple',
    'yes or no', 'list', 'bullet'
  ];
  const needsSpeed = speedKeywords.some(kw => lowerQuery.includes(kw));
  
  // Check for coding tasks
  const codingKeywords = [
    'code', 'function', 'implement', 'debug', 'error', 'syntax',
    'typescript', 'javascript', 'react', 'api', 'component'
  ];
  const isCoding = codingKeywords.some(kw => lowerQuery.includes(kw));
  
  // Check for creative tasks
  const creativeKeywords = [
    'campaign', 'creative', 'idea', 'brainstorm', 'concept',
    'copy', 'headline', 'tagline', 'brand', 'story'
  ];
  const isCreative = creativeKeywords.some(kw => lowerQuery.includes(kw));
  
  // Estimate token usage
  const estimatedTokens = query.length * 0.25; // Rough estimate
  
  // Determine best model
  let suggestedModel = 'anthropic/claude-sonnet-4'; // Default balanced
  
  if (requiresReasoning && !needsSpeed) {
    suggestedModel = 'openai/gpt-5'; // Best for complex reasoning
  } else if (isCoding) {
    suggestedModel = 'anthropic/claude-opus-4.1'; // Best for code
  } else if (needsSpeed && estimatedTokens < 100) {
    suggestedModel = 'anthropic/claude-3.5-haiku'; // Fast and cheap
  } else if (isCreative) {
    suggestedModel = 'anthropic/claude-sonnet-4'; // Good for creative
  } else if (estimatedTokens > 500) {
    suggestedModel = 'google/gemini-2.5-pro'; // Best value for long context
  }
  
  return {
    requiresReasoning,
    needsSpeed,
    isCoding,
    isCreative,
    estimatedTokens,
    suggestedModel
  };
}

/**
 * Get the appropriate model based on query
 */
export function selectModel(query: string): string {
  const complexity = analyzeComplexity(query);
  return complexity.suggestedModel;
}

/**
 * Model configurations with their strengths
 */
export const modelConfigs = {
  'openai/gpt-5': {
    name: 'GPT-5',
    provider: 'openai',
    strengths: ['reasoning', 'analysis', 'strategy'],
    costPerMillion: 30,
    speedRating: 3
  },
  'anthropic/claude-opus-4.1': {
    name: 'Claude Opus 4.1',
    provider: 'anthropic',
    strengths: ['coding', 'technical', 'logic'],
    costPerMillion: 15,
    speedRating: 2
  },
  'anthropic/claude-sonnet-4': {
    name: 'Claude Sonnet 4',
    provider: 'anthropic',
    strengths: ['balanced', 'creative', 'conversational'],
    costPerMillion: 3,
    speedRating: 4
  },
  'anthropic/claude-3.5-haiku': {
    name: 'Claude Haiku 3.5',
    provider: 'anthropic',
    strengths: ['speed', 'simple', 'cost-effective'],
    costPerMillion: 0.25,
    speedRating: 5
  },
  'google/gemini-2.5-pro': {
    name: 'Gemini 2.5 Pro',
    provider: 'google',
    strengths: ['value', 'long-context', 'multimodal'],
    costPerMillion: 1.25,
    speedRating: 4
  }
};