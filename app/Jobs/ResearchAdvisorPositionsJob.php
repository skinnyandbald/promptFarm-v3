<?php

namespace App\Jobs;

use App\Models\AdvisorPosition;
use App\Services\LLMService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ResearchAdvisorPositionsJob implements ShouldQueue
{
    use Queueable;

    public string $advisorKey;
    public array $advisorData;
    public bool $forceRefresh;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $advisorKey,
        array $advisorData,
        bool $forceRefresh = false
    ) {
        $this->advisorKey = $advisorKey;
        $this->advisorData = $advisorData;
        $this->forceRefresh = $forceRefresh;
    }

    /**
     * Execute the job.
     */
    public function handle(LLMService $llmService): void
    {
        // Check if we already have cached positions and shouldn't force refresh
        $existing = AdvisorPosition::where('advisor_key', $this->advisorKey)->first();
        
        if ($existing && !$this->forceRefresh) {
            Log::info('ResearchAdvisorPositionsJob: Positions already cached', [
                'advisor' => $this->advisorKey,
                'cached_at' => $existing->created_at,
            ]);
            return;
        }
        
        Log::info('ResearchAdvisorPositionsJob: Starting research', [
            'advisor' => $this->advisorKey,
            'force_refresh' => $this->forceRefresh,
        ]);
        
        // Research positions
        $positions = $this->researchPositions($llmService);
        
        // Save or update in database
        if ($existing) {
            $existing->update([
                'researched_positions' => $positions,
                'research_model' => config('ai-models.purposes.fact_checking'),
                'research_temperature' => 0.1,
                'metadata' => [
                    'advisor_name' => $this->advisorData['name'] ?? '',
                    'expertise' => $this->advisorData['expertise'] ?? '',
                    'researched_at' => now()->toIso8601String(),
                ],
            ]);
            Log::info('ResearchAdvisorPositionsJob: Updated researched positions', [
                'advisor' => $this->advisorKey,
            ]);
        } else {
            AdvisorPosition::create([
                'advisor_key' => $this->advisorKey,
                'researched_positions' => $positions,
                'research_model' => config('ai-models.purposes.fact_checking'),
                'research_temperature' => 0.1,
                'metadata' => [
                    'advisor_name' => $this->advisorData['name'] ?? '',
                    'expertise' => $this->advisorData['expertise'] ?? '',
                    'researched_at' => now()->toIso8601String(),
                ],
            ]);
            Log::info('ResearchAdvisorPositionsJob: Saved new researched positions', [
                'advisor' => $this->advisorKey,
            ]);
        }
    }
    
    protected function researchPositions(LLMService $llmService): string
    {        
        $prompt = <<<PROMPT
Extract {$this->advisorKey}'s CORE contrarian principles in the most concise form possible.

Format EXACTLY as shown (MAX 25 words per position):

POSITION 1: [Topic in 2-3 words]
BELIEF: [What they believe - 15 words max]
TRIGGER: [What mainstream view they reject - 10 words max]

POSITION 2: [Topic in 2-3 words]
BELIEF: [What they believe - 15 words max]
TRIGGER: [What mainstream view they reject - 10 words max]

[Continue for 8-10 positions]

Focus on:
- Specific tactics they always advocate
- Measurable principles (percentages, metrics)
- Named enemies or opposition
- Signature phrases or axioms
- Actions they say NEVER to do

Make each position a sharp, memorable principle.
NO explanations. NO context. Just the core contrarian beliefs.

Start immediately with "POSITION 1:"
PROMPT;

        $maxRetries = 3;
        $attempt = 1;
        
        while ($attempt <= $maxRetries) {
            Log::info('ResearchAdvisorPositionsJob: Calling fact-checking AI', [
                'advisor' => $this->advisorKey,
                'model' => config('ai-models.purposes.fact_checking'),
                'attempt' => $attempt,
            ]);

            $positions = $llmService->generateText(
                $prompt,
                [
                    'model' => config('ai-models.purposes.fact_checking'),
                    'temperature' => 0.1,
                    'max_tokens' => (int) 2000,
                ]
            );
            
            if ($this->validatePositionFormat($positions)) {
                Log::info('ResearchAdvisorPositionsJob: Valid format generated', [
                    'advisor' => $this->advisorKey,
                    'length' => strlen($positions),
                    'attempt' => $attempt,
                ]);
                return $positions;
            }
            
            Log::warning('ResearchAdvisorPositionsJob: Invalid format, retrying', [
                'advisor' => $this->advisorKey,
                'attempt' => $attempt,
                'max_retries' => $maxRetries,
            ]);
            
            $attempt++;
        }
        
        Log::error('ResearchAdvisorPositionsJob: Failed to generate valid format after max retries', [
            'advisor' => $this->advisorKey,
            'max_retries' => $maxRetries,
        ]);
        
        return $positions;
    }
    
    protected function validatePositionFormat(string $positions): bool
    {
        $lines = explode("\n", trim($positions));
        $positionCount = 0;
        $invalidPositionLines = 0;
        
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if (empty($trimmedLine)) continue;
            
            // Check if this line looks like a position header
            if (preg_match('/^(POSITION|mel|pos)\s+\d+:/i', $trimmedLine)) {
                if (preg_match('/^POSITION\s+\d+:/', $trimmedLine)) {
                    $positionCount++;
                } else {
                    // This is a malformed position header (like "mel 3:")
                    $invalidPositionLines++;
                }
            }
        }
        
        // Must have at least 8 valid positions AND no invalid position headers
        return $positionCount >= 8 && $invalidPositionLines === 0;
    }
}
