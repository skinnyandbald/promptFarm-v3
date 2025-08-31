'use client';

/**
 * Main chat interface for testing advisors
 * Day 1: Basic chat with hardcoded Bogusky
 * Day 2: Add generation button
 * Day 3: Add model selection
 */

import { useChat } from 'ai/react';
import { useState } from 'react';

export default function ChatPage() {
  const [advisor, setAdvisor] = useState('bogusky');
  const [showModelInfo, setShowModelInfo] = useState(false);
  
  const { messages, input, handleInputChange, handleSubmit, isLoading } = useChat({
    api: '/api/chat',
    body: {
      advisor
    }
  });

  return (
    <div className="flex flex-col h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white shadow-sm border-b">
        <div className="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">
              PromptFarm v3 - Advisor Test
            </h1>
            <p className="text-sm text-gray-500 mt-1">
              Testing {advisor.charAt(0).toUpperCase() + advisor.slice(1)} with PI/PK separation
            </p>
          </div>
          
          {/* Day 2: Add this button */}
          <button
            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            onClick={() => alert('Generation will be added in Day 2!')}
          >
            Generate New Advisor
          </button>
        </div>
      </header>

      {/* Messages */}
      <div className="flex-1 overflow-y-auto px-4 py-6">
        <div className="max-w-4xl mx-auto space-y-4">
          {messages.length === 0 ? (
            <div className="text-center py-12">
              <p className="text-gray-500 mb-4">
                Start a conversation with {advisor.charAt(0).toUpperCase() + advisor.slice(1)}
              </p>
              <div className="space-y-2 text-sm text-gray-400">
                <p>Try asking:</p>
                <p className="italic">"What makes a great creative campaign?"</p>
                <p className="italic">"How do you approach brand strategy?"</p>
                <p className="italic">"Tell me about the Subservient Chicken"</p>
              </div>
            </div>
          ) : (
            messages.map((message) => (
              <div
                key={message.id}
                className={`flex ${
                  message.role === 'user' ? 'justify-end' : 'justify-start'
                }`}
              >
                <div
                  className={`max-w-3xl px-4 py-3 rounded-lg ${
                    message.role === 'user'
                      ? 'bg-blue-600 text-white'
                      : 'bg-white border shadow-sm'
                  }`}
                >
                  {message.role === 'assistant' && (
                    <div className="text-xs text-gray-500 mb-1">
                      [{advisor.toUpperCase()}]
                    </div>
                  )}
                  <div className="whitespace-pre-wrap">{message.content}</div>
                </div>
              </div>
            ))
          )}
          
          {isLoading && (
            <div className="flex justify-start">
              <div className="bg-white border shadow-sm px-4 py-3 rounded-lg">
                <div className="flex space-x-2">
                  <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" />
                  <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '0.1s' }} />
                  <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '0.2s' }} />
                </div>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Input */}
      <div className="border-t bg-white">
        <form onSubmit={handleSubmit} className="max-w-4xl mx-auto px-4 py-4">
          <div className="flex space-x-4">
            <input
              type="text"
              value={input}
              onChange={handleInputChange}
              placeholder="Ask something..."
              className="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              disabled={isLoading}
            />
            <button
              type="submit"
              disabled={isLoading}
              className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              Send
            </button>
          </div>
        </form>
        
        {/* Day 3: Add model info */}
        {showModelInfo && (
          <div className="max-w-4xl mx-auto px-4 pb-4">
            <p className="text-xs text-gray-500">
              Using: GPT-5 (will add routing in Day 3)
            </p>
          </div>
        )}
      </div>
    </div>
  );
}