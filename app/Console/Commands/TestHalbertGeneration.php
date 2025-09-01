<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LLMService;

class TestHalbertGeneration extends Command
{
    protected $signature = 'test:halbert-generation 
                           {--temperature=0.7 : Temperature to test}
                           {--prompt=short : Use short or full prompt}';
    
    protected $description = 'Test Halbert generation with different temperatures';

    public function handle(LLMService $llmService)
    {
        $temperature = (float) $this->option('temperature');
        $useShortPrompt = $this->option('prompt') === 'short';
        
        $this->info("Testing Halbert generation with temperature: {$temperature}");
        
        // Short test prompt focused on Halbert's copywriting style
        $shortPrompt = "Write a 3-paragraph introduction as Gary Halbert, legendary copywriter. Include your background, philosophy, and signature style.";
        
        // Full prompt similar to PK generation
        $fullPrompt = "Generate Project Knowledge for Gary Halbert, expert in direct response copywriting.

## Voice Anchor
Write a 3-4 sentence first-person declaration that captures:
- Who I am and what I've accomplished
- My core philosophy and approach
- Why I'm different from other advisors
- A signature phrase or stance I'm known for

## Analytical Tension Example
Present ONE major topic in direct response copywriting as:
**The Paradox:** [What everyone believes] vs [What actually happens]
**The Evidence:** [Specific company/campaign with numbers]

Write in first person as Gary Halbert. Be specific.";

        $prompt = $useShortPrompt ? $shortPrompt : $fullPrompt;
        
        try {
            // Test with same system message as production
            $systemMessage = 'You are a brutally honest business advisor who reveals uncomfortable truths through analytical reasoning. You name specific companies and people. You explain why popular advice fails.';
            
            $response = $llmService->generateTextWithOpenRouter($prompt, [
                'model' => 'x-ai/grok-3',
                'temperature' => $temperature,
                'max_tokens' => 1000,
                'system_message' => $systemMessage
            ]);
            
            // Check for typos and issues
            $issues = [];
            if (preg_match('/II|II\'/', $response)) {
                $issues[] = "Double I issue (II, II')";
            }
            if (preg_match('/([a-z])\1{2,}/', $response)) {
                $issues[] = "Repeated characters";
            }
            if (preg_match('/[\x{4E00}-\x{9FFF}\x{AC00}-\x{D7AF}]/u', $response)) {
                $issues[] = "Non-English characters (Chinese/Korean)";
            }
            if (preg_match('/\b(\w+)(\1)/', $response)) {
                $issues[] = "Doubled words";
            }
            
            // Save output
            $filename = "halbert-test-temp-{$temperature}-" . ($useShortPrompt ? 'short' : 'full') . ".md";
            $path = storage_path("app/advisors/test-debug/{$filename}");
            file_put_contents($path, $response);
            
            $this->info("Response generated and saved to: {$filename}");
            
            if (empty($issues)) {
                $this->info("✅ No quality issues detected!");
            } else {
                $this->warn("⚠️ Quality issues found:");
                foreach ($issues as $issue) {
                    $this->warn("  - {$issue}");
                }
            }
            
            // Show first 500 chars
            $this->newLine();
            $this->line("First 500 characters:");
            $this->line(substr($response, 0, 500));
            
        } catch (\Exception $e) {
            $this->error("Generation failed: " . $e->getMessage());
        }
    }
}