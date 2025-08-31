# PromptFarm Post-MVP Optimization & Scaling

**Prerequisites:** Complete MILESTONE_BREAKDOWN.md first (working council mode)  
**Goal:** Optimize performance, add caching, scale to 10+ advisors  
**Timeline:** After MVP validation with users

## Phase 1: Performance Optimization (1 week)

### Milestone 1: Implement Smart Caching Strategy

#### 1.1: Redis Caching Layer (2 days)

**Create Cache Service**
```php
// app/Services/AdvisorGeneration/CacheService.php
<?php

namespace App\Services\AdvisorGeneration;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    private const CACHE_PREFIX = 'advisor:';
    private const TTL_HOURS = 24;
    
    public function getCachedGeneration(string $advisorId, string $contextHash): ?array
    {
        $key = $this->buildKey($advisorId, $contextHash);
        
        $cached = Cache::get($key);
        if ($cached) {
            Log::info("Cache hit for advisor {$advisorId}");
            return $cached;
        }
        
        return null;
    }
    
    public function cacheGeneration(
        string $advisorId, 
        string $contextHash,
        array $content
    ): void {
        $key = $this->buildKey($advisorId, $contextHash);
        
        Cache::put($key, $content, now()->addHours(self::TTL_HOURS));
        Log::info("Cached generation for advisor {$advisorId}");
    }
    
    public function cachePartialPK(string $advisorId, string $section, string $content): void
    {
        // Cache individual PK sections for reuse
        $key = self::CACHE_PREFIX . "pk:{$advisorId}:{$section}";
        Cache::put($key, $content, now()->addDays(7));
    }
    
    public function getPartialPK(string $advisorId, string $section): ?string
    {
        $key = self::CACHE_PREFIX . "pk:{$advisorId}:{$section}";
        return Cache::get($key);
    }
    
    public function invalidateAdvisor(string $advisorId): void
    {
        // Clear all cache for an advisor
        Cache::tags(['advisor', $advisorId])->flush();
    }
    
    private function buildKey(string $advisorId, string $contextHash): string
    {
        return self::CACHE_PREFIX . "{$advisorId}:{$contextHash}";
    }
}
```

**Cache Strategy:**
- Cache validated PI/PK pairs for 24 hours
- Cache PK sections (examples, frameworks) for 7 days
- Use Redis tags for easy invalidation
- Warm cache during off-peak hours

#### 1.2: Database Query Optimization (1 day)

**Optimize Eloquent Queries**
```php
// Use eager loading for council generation
$council = Council::with(['advisors.generations' => function ($query) {
    $query->latest()->limit(1);
}])->find($councilId);

// Add database indexes
Schema::table('advisor_generations', function (Blueprint $table) {
    $table->index(['advisor_definition_id', 'created_at']);
    $table->index(['quality_score', 'created_at']);
});

// Use chunking for batch operations
AdvisorDefinition::chunk(100, function ($advisors) {
    foreach ($advisors as $advisor) {
        ProcessAdvisorJob::dispatch($advisor);
    }
});
```

### Milestone 2: Implement Model Routing

#### 2.1: Smart Model Selection (2 days)

**Model Router Service**
```php
// app/Services/AdvisorGeneration/ModelRouterService.php
<?php

namespace App\Services\AdvisorGeneration;

class ModelRouterService
{
    private array $modelConfig = [
        'pi_generation' => [
            'provider' => 'openai',
            'model' => 'gpt-3.5-turbo', // Cheaper for deterministic PI
            'temperature' => 0.3,
            'max_tokens' => 2000
        ],
        'pk_generation' => [
            'provider' => 'anthropic',
            'model' => 'claude-3-opus', // Better for rich content
            'temperature' => 0.7,
            'max_tokens' => 6000
        ],
        'quality_validation' => [
            'provider' => 'google',
            'model' => 'gemini-pro', // Good at analysis
            'temperature' => 0.1,
            'max_tokens' => 1000
        ],
        'synthesis' => [
            'provider' => 'openai',
            'model' => 'gpt-4-turbo', // Best for synthesis
            'temperature' => 0.5,
            'max_tokens' => 3000
        ]
    ];
    
    public function getModelForTask(string $task): array
    {
        return $this->modelConfig[$task] ?? $this->modelConfig['pk_generation'];
    }
    
    public function routeByComplexity(string $content): string
    {
        $complexity = $this->calculateComplexity($content);
        
        if ($complexity < 3) {
            return 'gpt-3.5-turbo'; // Simple task
        } elseif ($complexity < 7) {
            return 'gpt-4-turbo'; // Medium complexity
        } else {
            return 'claude-3-opus'; // High complexity
        }
    }
    
    private function calculateComplexity(string $content): int
    {
        // Analyze content complexity
        $factors = [
            'length' => strlen($content) / 1000,
            'sections' => substr_count($content, '##'),
            'examples' => substr_count($content, 'Example:'),
            'frameworks' => substr_count($content, 'Framework:')
        ];
        
        return array_sum($factors);
    }
}
```

#### 2.2: Cost Optimization (1 day)

**Track and Optimize API Costs**
```php
// app/Services/AdvisorGeneration/CostTrackerService.php
class CostTrackerService
{
    private array $pricing = [
        'gpt-3.5-turbo' => ['input' => 0.0005, 'output' => 0.0015],
        'gpt-4-turbo' => ['input' => 0.01, 'output' => 0.03],
        'claude-3-opus' => ['input' => 0.015, 'output' => 0.075],
        'gemini-pro' => ['input' => 0.00025, 'output' => 0.0005]
    ];
    
    public function trackUsage(string $model, int $inputTokens, int $outputTokens): void
    {
        $cost = $this->calculateCost($model, $inputTokens, $outputTokens);
        
        DB::table('api_usage')->insert([
            'model' => $model,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'cost' => $cost,
            'created_at' => now()
        ]);
        
        // Alert if daily cost exceeds threshold
        if ($this->getDailyCost() > config('limits.daily_api_cost')) {
            Log::alert("Daily API cost exceeded threshold");
            // Switch to cheaper models or use cache more aggressively
        }
    }
}
```

### Milestone 3: Queue Optimization with Horizon

#### 3.1: Parallel Processing (1 day)

**Optimize Queue Configuration**
```php
// config/horizon.php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['high', 'default'],
            'balance' => 'auto',
            'minProcesses' => 1,
            'maxProcesses' => 10,
            'balanceMaxShift' => 1,
            'balanceCooldown' => 3,
            'tries' => 3,
        ],
        'council-generation' => [
            'connection' => 'redis',
            'queue' => 'councils',
            'balance' => 'simple',
            'processes' => 4, // One per advisor
            'tries' => 2,
            'timeout' => 300,
        ],
    ],
],

// app/Jobs/GenerateCouncilJob.php
class GenerateCouncilJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function handle()
    {
        // Generate advisors in parallel
        $jobs = collect($this->advisors)->map(function ($advisor) {
            return new GenerateAdvisorJob($advisor);
        });
        
        Bus::batch($jobs)
            ->name('Council Generation')
            ->dispatch();
    }
}
```

#### 3.2: Rate Limiting & Retry Logic (1 day)

**Implement Smart Retries**
```php
// app/Jobs/GenerateAdvisorJob.php
class GenerateAdvisorJob implements ShouldQueue
{
    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min
    
    public function middleware()
    {
        return [
            new RateLimited('openrouter'),
            new WithoutOverlapping($this->advisor->id)
        ];
    }
    
    public function retryUntil()
    {
        return now()->addHours(2);
    }
    
    public function failed(Throwable $exception)
    {
        // Log failure and notify
        Log::error("Advisor generation failed", [
            'advisor' => $this->advisor->name,
            'error' => $exception->getMessage()
        ]);
        
        // Try with fallback model
        dispatch(new GenerateAdvisorWithFallbackJob($this->advisor));
    }
}
```

## Phase 2: Scaling to 10+ Advisors (1 week)

### Milestone 4: Expand Advisor Library

#### 4.1: Add New Advisor Categories (2 days)

**Expand Advisor Definitions**
```php
// database/seeders/ExpandedAdvisorSeeder.php
class ExpandedAdvisorSeeder extends Seeder
{
    public function run()
    {
        $advisors = [
            // Marketing & Growth
            'seth_godin' => ['category' => 'Marketing Philosophy'],
            'gary_vee' => ['category' => 'Social Media Marketing'],
            'neil_patel' => ['category' => 'SEO & Content'],
            
            // Product & Design
            'julie_zhuo' => ['category' => 'Product Management'],
            'jony_ive' => ['category' => 'Design Excellence'],
            'marty_cagan' => ['category' => 'Product Strategy'],
            
            // Leadership & Culture
            'simon_sinek' => ['category' => 'Leadership'],
            'brene_brown' => ['category' => 'Vulnerability & Culture'],
            'ray_dalio' => ['category' => 'Principles & Systems'],
            
            // Technical & Engineering
            'martin_fowler' => ['category' => 'Software Architecture'],
            'kent_beck' => ['category' => 'Agile Development'],
            'kelsey_hightower' => ['category' => 'Cloud Native'],
        ];
        
        foreach ($advisors as $key => $data) {
            AdvisorDefinition::create([
                'id' => Str::ulid(),
                'name' => str_replace('_', ' ', ucfirst($key)),
                'category' => $data['category'],
                'is_default' => false,
                // ... rest of definition
            ]);
        }
    }
}
```

#### 4.2: Dynamic Council Composition (2 days)

**Smart Council Builder**
```php
// app/Services/AdvisorGeneration/CouncilBuilderService.php
class CouncilBuilderService
{
    public function buildOptimalCouncil(string $problemDomain): array
    {
        $categories = $this->identifyNeededExpertise($problemDomain);
        
        $council = [];
        foreach ($categories as $category) {
            $advisor = AdvisorDefinition::where('category', $category)
                ->orderBy('quality_score', 'desc')
                ->first();
            
            if ($advisor) {
                $council[] = $advisor;
            }
        }
        
        // Ensure diversity
        if (count($council) < 4) {
            $council = $this->addComplementaryAdvisors($council);
        }
        
        return $council;
    }
    
    private function identifyNeededExpertise(string $problem): array
    {
        // Use NLP to identify problem domain
        $keywords = $this->extractKeywords($problem);
        
        $categoryMap = [
            'technical' => ['Technical Architecture', 'Software Architecture'],
            'marketing' => ['Creative Strategy', 'Direct Marketing'],
            'business' => ['Business Growth', 'Leadership'],
            'product' => ['Product Management', 'Design Excellence']
        ];
        
        $needed = [];
        foreach ($keywords as $keyword) {
            if (isset($categoryMap[$keyword])) {
                $needed = array_merge($needed, $categoryMap[$keyword]);
            }
        }
        
        return array_unique($needed);
    }
}
```

### Milestone 5: Advanced Features

#### 5.1: A/B Testing Framework (2 days)

**Test Different Templates**
```php
// app/Services/AdvisorGeneration/ABTestingService.php
class ABTestingService
{
    public function runTemplateTest(string $advisorId): array
    {
        $templates = [
            'v1.0.0' => 'Original template',
            'v1.1.0' => 'Enhanced specificity',
            'v1.2.0' => 'More examples required'
        ];
        
        $results = [];
        
        foreach ($templates as $version => $description) {
            $result = $this->generateWithTemplate($advisorId, $version);
            $results[$version] = [
                'quality_score' => $result['score'],
                'generation_time' => $result['time'],
                'token_count' => $result['tokens']
            ];
        }
        
        // Determine winner
        $winner = collect($results)->sortByDesc('quality_score')->first();
        
        return [
            'winner' => $winner,
            'results' => $results
        ];
    }
}
```

#### 5.2: Auto-Quality Improvement (1 day)

**Self-Improving System**
```php
// app/Services/AdvisorGeneration/AutoImprovementService.php
class AutoImprovementService
{
    public function analyzeFailures(): array
    {
        $failures = DB::table('generation_failures')
            ->where('created_at', '>', now()->subDays(7))
            ->get();
        
        $patterns = [];
        foreach ($failures as $failure) {
            $patterns[] = $this->extractFailurePattern($failure);
        }
        
        // Identify common issues
        $commonIssues = $this->findCommonPatterns($patterns);
        
        // Generate template improvements
        return $this->suggestTemplateImprovements($commonIssues);
    }
    
    public function autoEnhanceTemplate(string $template, array $issues): string
    {
        foreach ($issues as $issue) {
            switch ($issue['type']) {
                case 'missing_examples':
                    $template = $this->addExampleRequirement($template);
                    break;
                case 'generic_content':
                    $template = $this->addSpecificityRequirement($template);
                    break;
                case 'voice_inconsistency':
                    $template = $this->strengthenVoiceAnchors($template);
                    break;
            }
        }
        
        return $template;
    }
}
```

## Phase 3: Production Readiness (1 week)

### Milestone 6: Monitoring & Observability

#### 6.1: Comprehensive Logging (1 day)

**Structured Logging**
```php
// app/Logging/AdvisorGenerationLogger.php
class AdvisorGenerationLogger
{
    public function logGeneration(array $context): void
    {
        Log::channel('advisor')->info('Generation completed', [
            'advisor' => $context['advisor'],
            'duration' => $context['duration'],
            'quality_score' => $context['score'],
            'model_used' => $context['model'],
            'token_count' => $context['tokens'],
            'cost' => $context['cost'],
            'cache_hit' => $context['cached'],
            'trace_id' => $context['trace_id']
        ]);
    }
    
    public function logQualityFailure(array $context): void
    {
        Log::channel('advisor')->warning('Quality validation failed', [
            'advisor' => $context['advisor'],
            'issues' => $context['issues'],
            'attempt' => $context['attempt'],
            'will_retry' => $context['will_retry']
        ]);
    }
}
```

#### 6.2: Metrics & Dashboards (2 days)

**Prometheus Metrics**
```php
// app/Metrics/AdvisorMetrics.php
class AdvisorMetrics
{
    private $registry;
    
    public function __construct()
    {
        $this->registry = new CollectorRegistry(new Redis());
        
        $this->generationCounter = $this->registry->registerCounter(
            'advisor',
            'generations_total',
            'Total advisor generations',
            ['advisor', 'status']
        );
        
        $this->generationDuration = $this->registry->registerHistogram(
            'advisor',
            'generation_duration_seconds',
            'Time to generate advisor',
            ['advisor']
        );
        
        $this->qualityScore = $this->registry->registerGauge(
            'advisor',
            'quality_score',
            'Current quality score',
            ['advisor']
        );
    }
    
    public function recordGeneration(string $advisor, float $duration, int $score): void
    {
        $this->generationCounter->inc(['advisor' => $advisor, 'status' => 'success']);
        $this->generationDuration->observe($duration, ['advisor' => $advisor]);
        $this->qualityScore->set($score, ['advisor' => $advisor]);
    }
}
```

**Grafana Dashboard Configuration**
```yaml
# grafana/dashboards/advisor-generation.yaml
dashboard:
  title: Advisor Generation Metrics
  panels:
    - title: Generation Rate
      type: graph
      targets:
        - expr: rate(advisor_generations_total[5m])
    
    - title: Quality Scores
      type: gauge
      targets:
        - expr: advisor_quality_score
    
    - title: API Costs
      type: stat
      targets:
        - expr: sum(rate(api_cost_dollars[1h])) * 3600
    
    - title: Cache Hit Rate
      type: graph
      targets:
        - expr: rate(cache_hits_total) / rate(cache_requests_total)
```

### Milestone 7: Error Recovery & Resilience

#### 7.1: Circuit Breaker Pattern (1 day)

**Implement Circuit Breakers**
```php
// app/Services/CircuitBreakerService.php
class CircuitBreakerService
{
    private array $states = [];
    private array $failures = [];
    
    public function call(string $service, callable $callback)
    {
        $state = $this->getState($service);
        
        if ($state === 'open') {
            if ($this->shouldAttemptReset($service)) {
                $state = 'half-open';
            } else {
                throw new CircuitOpenException("Service {$service} is unavailable");
            }
        }
        
        try {
            $result = $callback();
            
            if ($state === 'half-open') {
                $this->reset($service);
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->recordFailure($service);
            
            if ($this->failures[$service] >= config('circuit.threshold')) {
                $this->trip($service);
            }
            
            throw $e;
        }
    }
    
    private function trip(string $service): void
    {
        $this->states[$service] = 'open';
        $this->failures[$service] = 0;
        
        Cache::put("circuit:{$service}:opened_at", now(), 3600);
        
        Log::alert("Circuit breaker tripped for {$service}");
    }
}
```

#### 7.2: Graceful Degradation (1 day)

**Fallback Strategies**
```php
// app/Services/AdvisorGeneration/FallbackService.php
class FallbackService
{
    public function generateWithFallback(string $advisor, array $data): array
    {
        try {
            // Try primary generation
            return $this->primaryGeneration($advisor, $data);
        } catch (RateLimitException $e) {
            // Use cached version if available
            if ($cached = $this->getCachedVersion($advisor)) {
                Log::warning("Using cached advisor due to rate limit");
                return $cached;
            }
            
            // Try alternative model
            return $this->fallbackGeneration($advisor, $data);
        } catch (ModelUnavailableException $e) {
            // Use pre-generated static version
            return $this->getStaticVersion($advisor);
        }
    }
    
    private function fallbackGeneration(string $advisor, array $data): array
    {
        // Use cheaper/faster model with reduced quality
        $result = $this->llm->generateWithModel('gpt-3.5-turbo', $data);
        
        // Mark as fallback for tracking
        $result['is_fallback'] = true;
        $result['quality_warning'] = 'Generated with fallback model';
        
        return $result;
    }
}
```

## Performance Targets

### Generation Performance
- Single advisor: < 10 seconds (with cache: < 100ms)
- Full council: < 30 seconds (parallel processing)
- Quality score: > 85% consistency

### System Performance
- API success rate: > 99.5%
- Cache hit rate: > 60%
- Queue processing: < 1 minute average

### Cost Targets
- Per advisor generation: < $0.10
- Per council generation: < $0.35
- Monthly API costs: < $500 for 1000 generations

## Deployment Checklist

### Pre-Production
- [ ] All tests passing (> 90% coverage)
- [ ] Performance benchmarks met
- [ ] Security audit completed
- [ ] Backup strategy implemented
- [ ] Monitoring dashboards configured

### Production Launch
- [ ] Blue-green deployment setup
- [ ] Database migrations tested
- [ ] Redis cluster configured
- [ ] Horizon supervisors running
- [ ] SSL certificates valid

### Post-Launch
- [ ] Monitor error rates
- [ ] Track API costs
- [ ] Analyze quality scores
- [ ] Gather user feedback
- [ ] Plan next iteration

## Next Steps After Optimization

1. **Multi-language Support**: Generate advisors in different languages
2. **Voice Cloning**: Add audio responses with advisor-specific voices
3. **Interactive Councils**: Real-time council discussions
4. **Custom Advisors**: User-created advisor templates
5. **API Marketplace**: Expose advisor API for third-party apps