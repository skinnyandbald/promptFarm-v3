<?php

namespace App\Services;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class LLMService
{
    protected array $config;

    protected int $maxRetries = 3;

    protected int $retryDelay = 2;

    protected GuzzleClient $httpClient;

    public function __construct()
    {
        $this->config = Config::get('services.openrouter', []);
        $this->httpClient = new GuzzleClient;
    }

    /**
     * Generate text using the OpenAI API
     */
    public function generateText(string $prompt, array $options = []): string
    {
        // We're only using OpenRouter models, so always use OpenRouter
        return $this->generateTextWithOpenRouter($prompt, $options);
    }

    /**
     * Generate text using deep research models (o3/o4) via v1/responses endpoint
     */
    protected function generateWithDeepResearch(string $prompt, array $options = []): string
    {
        $model = $options['model'] ?? $this->config['model'] ?? config('ai-models.purposes.fallback');
        $maxTokens = $options['max_tokens'] ?? $this->config['max_tokens'] ?? 100000;
        $temperature = $options['temperature'] ?? $this->config['temperature'] ?? 0.7;

        $payload = [
            'model' => $model,
            'input' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'tools' => [
                ['type' => 'web_search_preview'],
            ],
            'max_output_tokens' => $maxTokens,
            'reasoning' => [
                'effort' => 'medium',  // deep-research models only support medium
            ],
            'text' => [
                'format' => ['type' => 'text'],
                'verbosity' => 'medium',  // deep-research models support medium verbosity
            ],
        ];

        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            try {
                Log::info('Deep Research API Call', [
                    'model' => $model,
                    'prompt_length' => strlen($prompt),
                    'attempt' => $attempt + 1,
                ]);

                $response = $this->httpClient->post('https://api.openai.com/v1/responses', [
                    'json' => $payload,
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->config['api_key'],
                        'Content-Type' => 'application/json',
                    ],
                    'timeout' => $this->config['timeout'] ?? 300,
                ]);

                $result = json_decode($response->getBody()->getContents(), true);

                // Handle the response structure
                $content = $this->extractContentFromDeepResearchResponse($result);

                Log::info('Deep Research API Response', [
                    'model' => $model,
                    'response_id' => $result['id'] ?? 'unknown',
                    'status' => $result['status'] ?? 'unknown',
                    'response_length' => strlen($content),
                ]);

                return $content;

            } catch (\GuzzleHttp\Exception\ClientException $e) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $error = json_decode($responseBody, true);

                Log::warning('Deep Research API Error', [
                    'error' => $error['error']['message'] ?? 'Unknown error',
                    'attempt' => $attempt + 1,
                    'model' => $model,
                ]);

                $lastException = new \Exception($error['error']['message'] ?? 'API request failed');
                $attempt++;

                if ($attempt < $this->maxRetries) {
                    sleep($this->retryDelay * $attempt);
                }
            } catch (\Exception $e) {
                $lastException = $e;
                $attempt++;

                Log::warning('Deep Research API Error', [
                    'error' => $e->getMessage(),
                    'attempt' => $attempt,
                    'model' => $model,
                ]);

                if ($attempt < $this->maxRetries) {
                    sleep($this->retryDelay * $attempt);
                }
            }
        }

        throw new \Exception(
            "Failed to generate text after {$this->maxRetries} attempts: ".
            ($lastException ? $lastException->getMessage() : 'Unknown error')
        );
    }

    /**
     * Extract content from deep research response
     */
    protected function extractContentFromDeepResearchResponse(array $response): string
    {
        // For completed responses, look for the message output
        if ($response['status'] === 'completed' && isset($response['output'])) {
            foreach ($response['output'] as $output) {
                // Look for message type output which contains the final answer
                if ($output['type'] === 'message' && isset($output['content'])) {
                    foreach ($output['content'] as $content) {
                        if ($content['type'] === 'output_text') {
                            return $content['text'];
                        }
                    }
                }
            }
        }

        // Check if we have output_text directly (some models might use this)
        if (isset($response['output_text'])) {
            return $response['output_text'];
        }

        // If status is incomplete, the model is still processing
        if ($response['status'] === 'incomplete') {
            $reason = $response['incomplete_details']['reason'] ?? 'processing';

            // For deep research models, incomplete usually means it's still working
            // We should either wait or increase tokens
            Log::info('Deep Research still processing', [
                'reason' => $reason,
                'response_id' => $response['id'] ?? 'unknown',
                'output_count' => isset($response['output']) ? count($response['output']) : 0,
            ]);

            // Deep research models often need more time and tokens
            // Return a message indicating this
            return 'Deep research is still processing. The model performed '.
                   (isset($response['output']) ? count($response['output']) : 0).
                   ' reasoning/search steps. Consider increasing max_output_tokens or using background=true for async processing.';
        }

        // Log the structure for debugging
        Log::info('Deep Research response structure', [
            'status' => $response['status'] ?? 'unknown',
            'has_output' => isset($response['output']),
            'output_count' => isset($response['output']) ? count($response['output']) : 0,
            'output_types' => isset($response['output']) ? array_column($response['output'], 'type') : [],
        ]);

        // Return message about status
        return 'Response status: '.($response['status'] ?? 'unknown').
               '. Deep research models may need more tokens or time to complete.';
    }

    /**
     * Generate completion for a given context
     */
    public function generateCompletion(string $context, string $instruction, array $options = []): string
    {
        $prompt = $this->buildPrompt($context, $instruction);

        return $this->generateText($prompt, $options);
    }

    /**
     * Build a structured prompt from context and instruction
     */
    protected function buildPrompt(string $context, string $instruction): string
    {
        return <<<PROMPT
Context:
{$context}

Instruction:
{$instruction}

Please provide a comprehensive response based on the context and instruction above.
PROMPT;
    }

    /**
     * Check if a model is a deep research model
     */
    public function isDeepResearchModel(string $model): bool
    {
        return str_contains($model, 'deep-research');
    }

    /**
     * Generate text using OpenRouter API for access to multiple models
     */
    public function generateTextWithOpenRouter(string $prompt, array $options = []): string
    {
        $apiKey = config('services.openrouter.api_key');
        if (! $apiKey) {
            throw new \Exception('OPENROUTER_API_KEY not configured');
        }

        $model = $options['model'] ?? config('ai-models.primary.model');
        $temperature = $options['temperature'] ?? 0.8;
        $maxTokens = $options['max_tokens'] ?? 4000;
        $systemMessage = $options['system_message'] ?? 'You are a brutally honest business advisor who reveals uncomfortable truths through analytical reasoning. You name specific companies and people. You explain why popular advice fails.';

        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            try {
                Log::info('OpenRouter API Call', [
                    'model' => $model,
                    'prompt_length' => strlen($prompt),
                    'attempt' => $attempt + 1,
                ]);

                $payload = [
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
                ];

                // ADD THIS: Structured output support
                if (isset($options['response_format'])) {
                    // Validate model supports JSON mode
                    $capabilities = config("ai-models.capabilities.{$model}");
                    if (! ($capabilities['json_mode'] ?? false)) {
                        throw new \Exception("Model {$model} does not support JSON mode required for template compliance");
                    }

                    $payload['response_format'] = $options['response_format'];
                }

                $response = $this->httpClient->post('https://openrouter.ai/api/v1/chat/completions', [
                    'json' => $payload,
                    'headers' => [
                        'Authorization' => 'Bearer '.$apiKey,
                        'HTTP-Referer' => config('app.url'),
                        'X-Title' => 'Advisor Generation',
                        'Content-Type' => 'application/json',
                    ],
                    'timeout' => 300, // Increased timeout for reasoning models like Grok 4
                ]);

                $result = json_decode($response->getBody()->getContents(), true);

                if (! isset($result['choices'][0]['message']['content'])) {
                    throw new \Exception('Invalid response structure from OpenRouter');
                }

                $content = $result['choices'][0]['message']['content'];

                // After getting response, validate JSON if structured output was requested
                if (isset($options['response_format'])) {
                    $jsonTest = json_decode($content, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::error('Structured output returned invalid JSON', [
                            'error' => json_last_error_msg(),
                            'content' => substr($content, 0, 500),
                        ]);
                        throw new \Exception('Model returned invalid JSON despite structured output mode');
                    }
                }

                Log::info('OpenRouter API Response', [
                    'model' => $model,
                    'response_length' => strlen($content),
                    'finish_reason' => $result['choices'][0]['finish_reason'] ?? 'unknown',
                ]);

                return $content;

            } catch (\GuzzleHttp\Exception\ClientException $e) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $error = json_decode($responseBody, true);

                Log::warning('OpenRouter API Error', [
                    'error' => $error['error']['message'] ?? 'Unknown error',
                    'attempt' => $attempt + 1,
                    'model' => $model,
                ]);

                $lastException = new \Exception($error['error']['message'] ?? 'OpenRouter API request failed');
                $attempt++;

                if ($attempt < $this->maxRetries) {
                    sleep($this->retryDelay * $attempt);
                }
            } catch (\Exception $e) {
                $lastException = $e;
                $attempt++;

                Log::warning('OpenRouter API Error', [
                    'error' => $e->getMessage(),
                    'attempt' => $attempt,
                    'model' => $model,
                ]);

                if ($attempt < $this->maxRetries) {
                    sleep($this->retryDelay * $attempt);
                }
            }
        }

        throw new \Exception(
            "Failed to generate text with OpenRouter after {$this->maxRetries} attempts: ".
            ($lastException ? $lastException->getMessage() : 'Unknown error')
        );
    }
}
