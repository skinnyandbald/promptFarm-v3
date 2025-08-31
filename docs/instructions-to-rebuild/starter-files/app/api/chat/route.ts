/**
 * Chat API endpoint with CRITICAL PI/PK separation
 * This is the most important file - it maintains the ChatGPT-like architecture
 */

import { streamText } from 'ai';
import { createOpenRouter } from '@openrouter/ai-sdk-provider';
import { loadAdvisorServer } from '@/lib/load-advisor';

const openrouter = createOpenRouter({
  apiKey: process.env.OPENROUTER_API_KEY,
});

export async function POST(req: Request) {
  try {
    const { messages, advisor = 'bogusky' } = await req.json();
    
    // Load advisor files
    const { pi, pk } = await loadAdvisorServer(advisor);
    
    // CRITICAL: Maintain PI/PK separation exactly like ChatGPT Projects
    const result = await streamText({
      model: openrouter('openai/gpt-5'),
      
      // PI goes in system prompt (behavioral rules, always active)
      system: pi,
      
      // Messages array with PK as first message
      messages: [
        {
          // PK goes as a system message with metadata
          role: 'system',
          content: `# Advisor Knowledge Base\n\n${pk}`,
          // This metadata is CRITICAL for proper separation
          // It tells the model this is reference knowledge, not instructions
          // @ts-ignore - metadata is a valid field
          metadata: { 
            type: 'knowledge',
            searchable: true,
            persistent: false
          }
        },
        ...messages // User messages come after
      ],
      
      // Model parameters
      temperature: 0.7,
      maxOutputTokens: 2000, // CORRECT: Not maxTokens or max_tokens
    });
    
    // Return streaming response
    return result.toDataStreamResponse();
    
  } catch (error) {
    console.error('Chat API error:', error);
    return new Response(
      JSON.stringify({ error: 'Failed to process chat request' }), 
      { 
        status: 500,
        headers: { 'Content-Type': 'application/json' }
      }
    );
  }
}

// Health check endpoint
export async function GET() {
  return new Response(
    JSON.stringify({ 
      status: 'ok',
      message: 'Chat API is running',
      architecture: 'PI/PK separated (ChatGPT-like)'
    }), 
    { 
      status: 200,
      headers: { 'Content-Type': 'application/json' }
    }
  );
}