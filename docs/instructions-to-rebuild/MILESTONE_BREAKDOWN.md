# PromptFarm PI/PK Generation System - Comprehensive Milestone Breakdown

**Updated:** August 31, 2025  
**Strategy:** Single-Advisor First → Councils M1+
**Goal (M0/M1):** Nail single-advisor quality and testability; then layer council orchestration
**Approach:** Build robust PI/PK generation and validation → Introduce council once single-advisor is strong
**Success Metric:** Single-advisor passes quality gates; later, councils produce productive disagreement and synthesis

## The Real Architecture [UPDATED]

### Core System (Laravel - Current Implementation):
- Meta templates with hybrid PI generation (deterministic + enhancement) ✅
- Analytical tensions framework for PK generation (NOT deep research) ✅
- **Council orchestration planned but NOT IMPLEMENTED** ❌
- Individual advisor quality: 80%+ achieved ✅
- Model Strategy: x-ai/grok-3 via OpenRouter for BOTH PI and PK [ACTUAL]

### Testing Approach (CLI‑First):
- Generate advisors and review PI/PK files directly from `storage/app/advisor-files`
- Primary: test single advisor first; councils after single‑advisor quality
- Focus on authenticity metrics: confrontational tone, specificity, uncomfortable truths
- Avoid building UI until advisor docs meet quality gates

## Strategic Prioritization: Why Single-Advisor First?

### The Key Insight
- Strong advisors are prerequisites; weak advisors compound into weak councils
- Establish authentic voice, tension protocol, and evidence depth per advisor
- Councils come after single-advisor quality gates are consistently met

### Quality Strategy [IMPLEMENTATION STATUS]
- **Individual Advisors:** 80%+ authenticity achieved ✅
- **PI Generation:** Hybrid approach implemented ✅
- **PK Generation:** Analytical tensions with x-ai/grok-3 [CHANGED] ✅
- **Council Orchestration:** NOT IMPLEMENTED ❌
- **Position Research:** NEW FEATURE - Fact-checking system added ✅

## Hour 1: Generate and Review (30–45 min)
**Goal:** Quick way to test PI/PK files via CLI

### Milestone 1.1: Generate First Advisor
```bash
php artisan advisor:generate AlexBogusky
ls storage/app/advisor-files
```

### Milestone 1.2: Review Quality (30 minutes)

**Step 1: Inspect Outputs (5 min)**
```bash
ls -la storage/app/advisor-files
```

**Step 2: Manual Checks (20 min)**
- PI voice authenticity: first‑person, tension protocol present
- PK evidence density: numbers, dates, named projects
- Structure completeness: all required sections

**Step 3: Document Issues (5 min)**
Create `quality-issues.md` and log gaps to fix next.

**Success Criteria:**
- ✅ Generated PI/PK present
- ✅ Identified quality issues to fix

---

## Day 1: Build Laravel Generation System [COMPLETED]
**Status:** ✅ Core generation system implemented with enhancements
**Actual Implementation:** More sophisticated than planned with quality validation and position research

### Milestone 2.1: Set Up Laravel 12 Project (45 minutes)

**Bootstrap (Deterministic, Latest Stable)**
```bash
# Create clean app (temp)
composer create-project laravel/laravel ~/code/promptFarm-v4-tmp "^12.0" --no-interaction --prefer-dist

# Install deps
cd ~/code/promptFarm-v4-tmp
composer require guzzlehttp/guzzle:"^7.10" --no-interaction --prefer-dist
composer require --dev laravel/pint:"^1" pestphp/pest:"^4" pestphp/pest-plugin-laravel:"^4" phpunit/phpunit:"^11" nunomaduro/larastan:"^3" --no-interaction --prefer-dist
php artisan pest:install --no-interaction

# Env + key + defaults
php -r "copy('.env.example','.env');" && php artisan key:generate --no-interaction
printf "\nQUEUE_CONNECTION=sync\nOPENAI_API_KEY=sk-openai-...\nOPENROUTER_API_KEY=sk-or-...\nOPENAI_MODEL_PK=gpt-4o-mini\nOPENROUTER_MODEL_PI_ENHANCE=x-ai/grok-3\n" >> .env

# Optional SQLite
mkdir -p database && touch database/database.sqlite
sed -i '' 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env

# Update to latest patches
composer update --no-interaction --prefer-dist
```

**Install Laravel Boost MCP (Required)**
```bash
# Install Boost
composer require laravel-boost/boost --dev --no-interaction --prefer-dist
php artisan boost:status

# Configure Claude MCP
mkdir -p ~/.config/claude
cat > ~/.config/claude/config.json << 'EOF'
{
  "mcpServers": {
    "laravel-boost": {
      "command": "npx",
      "args": ["@laravel-boost/mcp-server"],
      "cwd": "~/code/promptFarm-v4"
    }
  }
}
EOF
```

Note: Redis/Horizon come later (M1+) once generation surfaces async needs.

**Configure Services (Hybrid Model Approach)**
```php
// config/services.php
return [
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'pk_model' => env('OPENAI_MODEL_PK', 'gpt-4o-mini'),  // PK generation
    ],
    'openrouter' => [
        'key' => env('OPENROUTER_API_KEY'),
        'base_url' => 'https://openrouter.ai/api/v1',
        'pi_enhancement_model' => env('OPENROUTER_MODEL_PI_ENHANCE', 'x-ai/grok-3'),  // PI enhancement
    ],
];
```

**Step 6: Set Up Development Tools**
```bash
# Configure Git hooks with Husky
npm install --save-dev husky
npx husky init

# Add pre-commit hook for quality
echo "php artisan pint" > .husky/pre-commit
echo "php artisan test" >> .husky/pre-commit

# Configure PHPStan
cat > phpstan.neon << 'EOF'
includes:
    - vendor/nunomaduro/larastan/extension.neon
parameters:
    level: 6
    paths:
        - app
EOF
```

**Commit:**
```bash
git init
git add .
git commit -m "feat: initialize Laravel 12 with Boost MCP and QA tooling"
```

### Milestone 2.2: Create Template Structure with Boost (1 hour)

**Step 1: Use Laravel Boost to Generate Structure**
```bash
# Use Boost MCP to create services
php artisan make:service AdvisorGeneration/TemplateLoaderService
php artisan make:service AdvisorGeneration/LLMIntegrationService
php artisan make:service AdvisorGeneration/AdvisorGenerationService
php artisan make:service AdvisorGeneration/ValidationService
php artisan make:service AdvisorGeneration/FileGenerationService

# Create directories (non-versioned templates)
mkdir -p resources/advisor-templates/meta
mkdir -p storage/app/advisor-files

# Use Boost to analyze project structure
php artisan boost:analyze
```

**Step 2: Copy Meta Templates**
```bash
# Copy meta templates from starter-files
cp ./starter-files/templates/meta_pi_template_v1.md \
   resources/advisor-templates/meta/meta_pi_template.md

cp ./starter-files/templates/meta_pk_template_v1.md \
   resources/advisor-templates/meta/meta_pk_template.md

# Verify files copied correctly
ls -la resources/advisor-templates/meta/
```

**Step 3: Create Template Loader Service**
```php
// app/Services/AdvisorGeneration/TemplateLoaderService.php
<?php

namespace App\Services\AdvisorGeneration;

class TemplateLoaderService
{
    public function loadPITemplate(): string
    {
        return file_get_contents(resource_path('advisor-templates/meta/meta_pi_template.md'));
    }
    
    public function loadPKTemplate(): string
    {
        return file_get_contents(resource_path('advisor-templates/meta/meta_pk_template.md'));
    }
}
```

**Commit:**
```bash
git add .
git commit -m "feat: add meta template structure and loader service"
```

### Milestone 2.3: Build Generation Service (2 hours)

**Step 1: Create LLM Integration Service with OpenRouter**
```php
// app/Services/AdvisorGeneration/LLMIntegrationService.php
<?php

namespace App\Services\AdvisorGeneration;

use OpenAI;
use Illuminate\Support\Facades\Log;

class LLMIntegrationService
{
    private $client;
    
    public function __construct()
    {
        // OpenRouter uses OpenAI-compatible API
        $this->client = OpenAI::factory()
            ->withApiKey(config('services.openrouter.key'))
            ->withBaseUri(config('services.openrouter.base_url'))
            ->withHttpHeader('HTTP-Referer', config('app.url'))
            ->withHttpHeader('X-Title', 'PromptFarm Advisor Generation')
            ->make();
    }
    
    public function generatePI(string $template, array $advisorData): string
    {
        // Stage 1: Deterministic template substitution
        Log::info('Stage 1: Building PI base for ' . $advisorData['name']);
        
        $piContent = $this->preparePrompt($template, $advisorData);
        
        // Stage 2: Enhance with Grok-3 via OpenRouter
        Log::info('Stage 2: Enhancing PI with examples');
        
        $enhancementPrompt = "Add specific examples and uncomfortable truths to this PI: \n\n" . $piContent;
        
        $response = $this->client->chat()->create([
            'model' => config('services.openrouter.pi_enhancement_model', 'x-ai/grok-3'),
            'messages' => [
                ['role' => 'user', 'content' => $enhancementPrompt]
            ],
            'temperature' => 0.3,  // Lower for consistency
            'max_tokens' => 5000,
        ]);
        
        return $response->choices[0]->message->content;
    }
    
    public function generatePK(string $template, array $advisorData): string
    {
        // PK uses analytical tensions framework (NOT deep research)
        $prompt = $this->buildAnalyticalTensionsPrompt($template, $advisorData);
        
        Log::info('Generating PK with analytical tensions for ' . $advisorData['name']);
        
        // Analytical tensions system prompt
        $systemPrompt = "Generate uncomfortable truths for {$advisorData['name']} using analytical tensions:\n" .
                       "1. Paradox: What everyone believes vs reality\n" .
                       "2. Evidence: Specific companies, real numbers\n" .
                       "3. Constraint: Why problems persist\n" .
                       "4. Uncomfortable Truth: What to do instead\n" .
                       "Be confrontational. Name names. Show receipts.";
        
        // Use gpt-4o-mini for PK with analytical tensions
        $response = $this->openaiClient->chat()->create([
            'model' => config('services.openai.pk_model', 'gpt-4o-mini'),
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.75,  // Optimal range: 0.7-0.85
            'max_tokens' => 8000,
        ]);
        
        $pkContent = $response->choices[0]->message->content;
        
        // Add metadata
        $pkContent = str_replace('{{generated_date}}', now()->format('Y-m-d'), $pkContent);
        $pkContent = str_replace('{{generation_id}}', uniqid('gen_'), $pkContent);
        
        return $pkContent;
    }
    
    private function preparePrompt(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $template = str_replace("{{{$key}}}", $value, $template);
        }
        return $template;
    }
}
```

**Step 2: Create Main Generation Service**
```php
// app/Services/AdvisorGeneration/AdvisorGenerationService.php
<?php

namespace App\Services\AdvisorGeneration;

class AdvisorGenerationService
{
    public function __construct(
        private TemplateLoaderService $templateLoader,
        private LLMIntegrationService $llmService,
        private FileGenerationService $fileService
    ) {}
    
    public function generateAdvisor(string $advisorName, array $data): array
    {
        // Load templates
        $piTemplate = $this->templateLoader->loadPITemplate();
        $pkTemplate = $this->templateLoader->loadPKTemplate();
        
        // Generate content
        $piContent = $this->llmService->generatePI($piTemplate, $data);
        $pkContent = $this->llmService->generatePK($pkTemplate, $data);
        
        // Save files
        $piPath = $this->fileService->savePIFile($advisorName, $piContent);
        $pkPath = $this->fileService->savePKFile($advisorName, $pkContent);
        
        return [
            'pi_path' => $piPath,
            'pk_path' => $pkPath,
            'pi_content' => $piContent,
            'pk_content' => $pkContent
        ];
    }
}
```

**Step 3: Create Artisan Command**
```php
// app/Console/Commands/GenerateAdvisorCommand.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AdvisorGeneration\AdvisorGenerationService;

class GenerateAdvisorCommand extends Command
{
    protected $signature = 'advisor:generate {name}';
    protected $description = 'Generate PI/PK files for an advisor';
    
    public function handle(AdvisorGenerationService $service)
    {
        $name = $this->argument('name');
        
        // Get advisor data from config
        $advisorData = config("advisors.{$name}");
        
        if (!$advisorData) {
            $this->error("Advisor {$name} not found in config");
            return 1;
        }
        
        $this->info("Generating {$name}...");
        
        $result = $service->generateAdvisor($name, $advisorData);
        
        $this->info("✅ Generated PI: {$result['pi_path']}");
        $this->info("✅ Generated PK: {$result['pk_path']}");
        
        return 0;
    }
}
```

**Commit:**
```bash
git add .
git commit -m "feat: implement core advisor generation services"
```

### Milestone 2.4: Add Advisor Configurations (1 hour)

**Step 1: Create Advisor Config**
```php
// config/advisors.php
<?php

return [
    'bogusky' => [
        'name' => 'Alex Bogusky',
        'category' => 'Creative Strategy',
        'expertise' => 'Creative Strategy, Advertising, Brand Positioning',
        'background' => 'Former Chief Creative Officer at CP+B, creator of truth campaign',
        'signature_work' => [
            'truth Anti-Smoking Campaign',
            'Subservient Chicken for Burger King',
            'Domino\'s Pizza Turnaround'
        ],
        'philosophy' => 'Find the cultural tension. Make the enemy visible.',
        'voice_markers' => [
            'Direct, no-BS communication',
            'Focus on cultural enemies',
            'Anti-advertising advertising'
        ]
    ],
    'hormozi' => [
        'name' => 'Alex Hormozi',
        'category' => 'Business Growth',
        'expertise' => 'Business Strategy, Offer Creation, Sales',
        'background' => 'Built and sold multiple 8-figure businesses',
        'signature_work' => [
            '$100M Offers framework',
            'Gym Launch scaling system',
            'Acquisition.com portfolio'
        ],
        'philosophy' => 'Make offers so good people feel stupid saying no.',
        'voice_markers' => [
            'Data-driven approach',
            'No-fluff business advice',
            'Focus on unit economics'
        ]
    ],
    'henderson' => [
        'name' => 'Cal Henderson',
        'category' => 'Technical Architecture',
        'expertise' => 'Scalable Systems, Engineering Leadership',
        'background' => 'CTO and co-founder of Slack',
        'signature_work' => [
            'Building Scalable Web Sites book',
            'Slack\'s real-time architecture',
            'Flickr\'s scaling journey'
        ],
        'philosophy' => 'Build for scale from day one, but ship fast.',
        'voice_markers' => [
            'Pragmatic engineering',
            'Focus on reliability',
            'Clear technical communication'
        ]
    ],
    'halbert' => [
        'name' => 'Gary Halbert',
        'category' => 'Direct Marketing',
        'expertise' => 'Copywriting, Direct Response Marketing',
        'background' => 'The Prince of Print, legendary direct mail copywriter',
        'signature_work' => [
            'The Boron Letters',
            'Coat of Arms letter (700M mailed)',
            'Dollar bill letters'
        ],
        'philosophy' => 'Motion beats meditation. Write to one person.',
        'voice_markers' => [
            'Conversational copy style',
            'Focus on emotional triggers',
            'Test everything, assume nothing'
        ]
    ]
];
```

**Step 2: Test Generation**
```bash
# Register command
php artisan make:command GenerateAdvisorCommand

# Test generation
php artisan advisor:generate bogusky

# Check output
ls -la storage/app/advisor-files/
cat storage/app/advisor-files/AlexBogusky_PI.md
```

**Commit:**
```bash
git add .
git commit -m "feat: add advisor configurations and test generation"
```

### Milestone 2.5: Test with Chat Interface (30 minutes)

**Step 1: Copy Generated Files to Test Chat**
```bash
# In the test chat directory
cp ~/code/promptFarm-v4/storage/app/advisor-files/AlexBogusky_PI.md test-advisors/current_PI.md
cp ~/code/promptFarm-v4/storage/app/advisor-files/AlexBogusky_PK.md test-advisors/current_PK.md
```

**Step 2: Test Quality**
```markdown
# Test these specific aspects:
1. Does Bogusky mention specific campaigns?
2. Does he push back on vague requests?
3. Does he use his signature phrases?
4. Are responses generic or specific?
```

**Commit:**
```bash
git commit -m "test: validate first generation with test chat"
```

### Milestone 2.6: Set Up Monitoring & Debugging (30 minutes)

**Step 1: Configure Horizon Dashboard**
```bash
# Start Horizon for queue monitoring
php artisan horizon

# Access dashboard at http://promptfarm-v3.test/horizon

# Create supervisor config for production
php artisan horizon:supervisor
```

**Step 2: Use Laravel Boost for Debugging**
```bash
# Use Boost MCP commands in Claude:
# - boost:tinker - Execute PHP in Laravel context
# - boost:database-query - Query database directly
# - boost:read-log-entries - Read application logs
# - boost:last-error - Get last error details
# - boost:application-info - Get app configuration

# Example debugging workflow:
php artisan tinker
>>> $service = app(App\Services\AdvisorGeneration\AdvisorGenerationService::class);
>>> $result = $service->generateAdvisor('bogusky', config('advisors.bogusky'));
>>> dd($result);
```

**Step 3: Set Up Logging**
```php
// config/logging.php - Add advisor channel
'channels' => [
    // ... existing channels
    
    'advisor' => [
        'driver' => 'daily',
        'path' => storage_path('logs/advisor.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
    ],
],
```

**Commit:**
```bash
git add .
git commit -m "feat: add monitoring with Horizon and Boost debugging"
```

**Success Criteria:**
- ✅ Laravel project running on Herd at promptfarm-v3.test
- ✅ Laravel Boost MCP configured for Claude
- ✅ Redis queues with Horizon monitoring
- ✅ OpenRouter integration working
- ✅ Bogusky mentions specific campaigns (Truth, Subservient Chicken)
- ✅ Pushes back on vague requests
- ✅ Uses signature phrases naturally
- ✅ Provides battle-tested examples, not theory

---

## Day 2: Implement Quality Validation (6 hours)
**Goal:** Ensure consistent high quality across all generations

### Milestone 3.1: Create Quality Validation Service (2 hours)

**Step 1: Build Quality Validator**
```php
// app/Services/AdvisorGeneration/ValidationService.php
<?php

namespace App\Services\AdvisorGeneration;

class ValidationService
{
    private array $requiredPISections = [
        'Context',
        'Constitutional Identity Constraints',
        'Voice Authenticity Anchors',
        'Operating Principles'
    ];
    
    private array $requiredPKSections = [
        'Voice Anchor',
        'Useful Tension Protocol',
        'Battle-Tested Case Studies',
        'Content Frameworks'
    ];
    
    public function validatePI(string $content, array $advisorData): array
    {
        $issues = [];
        
        // Check for required sections
        foreach ($this->requiredPISections as $section) {
            if (!str_contains($content, "## {$section}")) {
                $issues[] = "Missing required section: {$section}";
            }
        }
        
        // Check for specificity
        if (!str_contains($content, $advisorData['name'])) {
            $issues[] = "PI doesn't mention advisor name";
        }
        
        // Check for evidence-based prompting
        if (!str_contains($content, 'Constitutional') && !str_contains($content, 'Chain-of-Thought')) {
            $issues[] = "Missing evidence-based prompting techniques";
        }
        
        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'score' => $this->calculateScore($content, $issues)
        ];
    }
    
    public function validatePK(string $content, array $advisorData): array
    {
        $issues = [];
        
        // Check for specific examples
        $exampleCount = substr_count($content, '**Example');
        if ($exampleCount < 3) {
            $issues[] = "Insufficient examples (found {$exampleCount}, need at least 3)";
        }
        
        // Check for metrics/numbers
        if (!preg_match('/\d+%|\$\d+|\d+ (years|months|days)/', $content)) {
            $issues[] = "No specific metrics or numbers found";
        }
        
        // Check signature work is mentioned
        foreach ($advisorData['signature_work'] ?? [] as $work) {
            if (!str_contains($content, $work)) {
                $issues[] = "Missing signature work: {$work}";
            }
        }
        
        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'score' => $this->calculateScore($content, $issues)
        ];
    }
    
    private function calculateScore(string $content, array $issues): int
    {
        $baseScore = 100;
        $deduction = count($issues) * 10;
        
        // Bonus for specificity
        if (preg_match_all('/\d{4}|\$[\d,]+|[\d]+%/', $content) > 5) {
            $baseScore += 5;
        }
        
        return max(0, min(100, $baseScore - $deduction));
    }
}
```

**Step 2: Integrate into Generation Service**
```php
// Update AdvisorGenerationService.php
public function generateAdvisor(string $advisorName, array $data): array
{
    $maxAttempts = 3;
    $attempt = 0;
    
    do {
        $attempt++;
        
        // Generate content
        $piContent = $this->llmService->generatePI($piTemplate, $data);
        $pkContent = $this->llmService->generatePK($pkTemplate, $data);
        
        // Validate
        $piValidation = $this->validator->validatePI($piContent, $data);
        $pkValidation = $this->validator->validatePK($pkContent, $data);
        
        if ($piValidation['valid'] && $pkValidation['valid']) {
            break;
        }
        
        // Log issues for debugging
        Log::warning("Generation attempt {$attempt} failed", [
            'pi_issues' => $piValidation['issues'],
            'pk_issues' => $pkValidation['issues']
        ]);
        
        // Enhance prompts with specific requirements
        if ($attempt < $maxAttempts) {
            $piTemplate = $this->enhanceTemplate($piTemplate, $piValidation['issues']);
            $pkTemplate = $this->enhanceTemplate($pkTemplate, $pkValidation['issues']);
        }
        
    } while ($attempt < $maxAttempts);
    
    // Save and return...
}
```

**Commit:**
```bash
git add .
git commit -m "feat: add validation service (PI/PK scoring)"
```

### Milestone 3.2: Create Pest Tests (2 hours)

**Step 1: Create Quality Test Suite**
```php
// tests/Feature/AdvisorQualityTest.php
<?php

use App\Services\AdvisorGeneration\QualityValidationService;
use App\Services\AdvisorGeneration\AdvisorGenerationService;

it('validates PI contains required sections', function () {
    $validator = new ValidationService();
    
    $validPI = file_get_contents(base_path('tests/fixtures/valid_pi.md'));
    $result = $validator->validatePI($validPI, ['name' => 'Alex Bogusky']);
    
    expect($result['valid'])->toBeTrue();
    expect($result['score'])->toBeGreaterThan(80);
});

it('rejects generic PI content', function () {
    $validator = new ValidationService();
    
    $genericPI = "You are a helpful assistant who provides advice.";
    $result = $validator->validatePI($genericPI, ['name' => 'Alex Bogusky']);
    
    expect($result['valid'])->toBeFalse();
    expect($result['issues'])->toContain('Missing required section: Context');
});

it('validates PK contains specific examples', function () {
    $validator = new ValidationService();
    
    $validPK = file_get_contents(base_path('tests/fixtures/valid_pk.md'));
    $result = $validator->validatePK($validPK, [
        'name' => 'Alex Bogusky',
        'signature_work' => ['truth campaign', 'Subservient Chicken']
    ]);
    
    expect($result['valid'])->toBeTrue();
    expect($result['score'])->toBeGreaterThan(85);
});
```

**Step 2: Run Tests**
```bash
# Install Pest
composer require pestphp/pest --dev
php artisan pest:install

# Run tests
php artisan test

# With coverage
php artisan test --coverage
```

**Commit:**
```bash
git add .
git commit -m "test: add comprehensive quality validation tests"
```

### Milestone 3.3: Create Template Enhancement System (1 hour)

**Step 1: Build Template Enhancer**
```php
// app/Services/AdvisorGeneration/TemplateEnhancementService.php
<?php

namespace App\Services\AdvisorGeneration;

class TemplateEnhancementService
{
    public function enhanceTemplate(string $template, array $issues): string
    {
        $enhancements = [];
        
        foreach ($issues as $issue) {
            if (str_contains($issue, 'Missing required section')) {
                $section = str_replace('Missing required section: ', '', $issue);
                $enhancements[] = "CRITICAL: You MUST include a section titled '## {$section}'";
            }
            
            if (str_contains($issue, 'Insufficient examples')) {
                $enhancements[] = "CRITICAL: Include at least 3 specific, real-world examples with metrics";
            }
            
            if (str_contains($issue, 'No specific metrics')) {
                $enhancements[] = "CRITICAL: Include specific numbers, percentages, dollar amounts, or timeframes";
            }
        }
        
        if (!empty($enhancements)) {
            $enhancementBlock = "\n\n## QUALITY REQUIREMENTS (MUST FOLLOW):\n" . 
                               implode("\n", $enhancements) . "\n\n";
            $template = $enhancementBlock . $template;
        }
        
        return $template;
    }
}
```

**Commit:**
```bash
git add .
git commit -m "feat: add template enhancement for failed validations"
```

### Milestone 3.4: Test Multiple Advisors (1 hour)

**Step 1: Generate All Seeded Advisors**
```bash
# Generate each default advisor
php artisan advisor:generate bogusky --verbose  # Creative Strategy
php artisan advisor:generate hormozi --verbose  # Business Growth
php artisan advisor:generate henderson --verbose # Technical Architecture
php artisan advisor:generate halbert --verbose  # Direct Marketing

# Check quality scores
ls -la storage/app/advisor-files/
```

**Step 2: Test in Chat**
```bash
# Copy each to test chat and validate
for advisor in bogusky hormozi henderson halbert; do
echo "Review outputs in storage/app/advisor-files for ${advisor} (PI/PK)."
    echo "Testing $advisor - check distinct voice"
done
```

**Step 3: Document Quality**
```markdown
# quality-report.md
## Generation Results

| Advisor   | Category               | PI Score | PK Score | Voice Distinct | Examples Present |
|-----------|------------------------|----------|----------|----------------|------------------|
| Bogusky   | Creative Strategy      | 92%      | 88%      | ✅             | ✅               |
| Hormozi   | Business Growth        | 90%      | 91%      | ✅             | ✅               |
| Henderson | Technical Architecture | 89%      | 87%      | ✅             | ✅               |
| Halbert   | Direct Marketing       | 91%      | 89%      | ✅             | ✅               |
```

**Commit:**
```bash
git add .
git commit -m "test: validate quality across multiple advisors"
```

**Success Criteria:**
- ✅ Quality validation catches generic content
- ✅ Retry mechanism improves output
- ✅ Each advisor has distinct, authentic voice
- ✅ Examples are specific and measurable

---

## Day 3: Generate Council of Advisors [NOT IMPLEMENTED]
**Status:** ❌ Council features not built yet
**Note:** Individual advisor generation is complete, but council orchestration awaits implementation

### Milestone 4.1: Create Council Generation Command (2 hours)

**Step 1: Build Council Command**
```php
// app/Console/Commands/GenerateCouncilCommand.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AdvisorGeneration\AdvisorGenerationService;

class GenerateCouncilCommand extends Command
{
    protected $signature = 'council:generate {--fresh : Regenerate all advisors}';
    protected $description = 'Generate a full council of 4 default advisors';
    
    public function handle(AdvisorGenerationService $service)
    {
        $this->info('🚀 Generating Council of Advisors...');
        
        // Get default advisors from config
        $advisors = [
            'bogusky' => 'Creative Strategy',
            'hormozi' => 'Business Growth',
            'henderson' => 'Technical Architecture',
            'halbert' => 'Direct Marketing'
        ];
        
        $results = [];
        $progressBar = $this->output->createProgressBar(count($advisors));
        
        foreach ($advisors as $key => $category) {
            $this->line("\n📝 Generating {$key} ({$category})...");
            $progressBar->advance();
            
            try {
                $result = $service->generateAdvisor($key, config("advisors.{$key}"));
                $results[$key] = $result;
                
                $this->info("\n✅ {$key}: PI={$result['pi_score']}%, PK={$result['pk_score']}%");
            } catch (\Exception $e) {
                $this->error("\n❌ Failed to generate {$key}: " . $e->getMessage());
            }
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        // Summary
        $this->table(
            ['Advisor', 'Category', 'PI Score', 'PK Score', 'Status'],
            collect($results)->map(function ($result, $key) use ($advisors) {
                return [
                    $key,
                    $advisors[$key],
                    $result['pi_score'] . '%',
                    $result['pk_score'] . '%',
                    $result['pi_score'] >= 80 && $result['pk_score'] >= 80 ? '✅' : '⚠️'
                ];
            })
        );
        
        return 0;
    }
}
```

**Step 2: Create File Export Service**
```php
// app/Services/AdvisorGeneration/FileGenerationService.php
<?php

namespace App\Services\AdvisorGeneration;

use Illuminate\Support\Facades\Storage;

class FileGenerationService
{
    public function saveCouncil(array $advisors): string
    {
        $timestamp = now()->format('Y-m-d_His');
        $councilPath = "councils/{$timestamp}";
        
        // Create manifest
        $manifest = [
            'generated_at' => now()->toIso8601String(),
            'version' => '1.0.0',
            'advisors' => []
        ];
        
        foreach ($advisors as $name => $data) {
            // Save individual files
            Storage::put("{$councilPath}/{$name}_PI.md", $data['pi_content']);
            Storage::put("{$councilPath}/{$name}_PK.md", $data['pk_content']);
            
            $manifest['advisors'][] = [
                'name' => $name,
                'pi_file' => "{$name}_PI.md",
                'pk_file' => "{$name}_PK.md",
                'scores' => [
                    'pi' => $data['pi_score'],
                    'pk' => $data['pk_score']
                ]
            ];
        }
        
        // Save manifest
        Storage::put("{$councilPath}/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT));
        
        return $councilPath;
    }
}
```

**Commit:**
```bash
git add .
git commit -m "feat: add council generation command"
```

### Milestone 4.2: Test Full Council Generation (2 hours)

**Step 1: Generate Council**
```bash
# Generate all 4 advisors
php artisan council:generate

# Check output
ls -la storage/app/councils/
cat storage/app/councils/*/manifest.json
```

**Step 2: Review Council Outputs via CLI**
```bash
COUNCIL_DIR=$(ls -t storage/app/councils | head -1)
ls -la "storage/app/councils/$COUNCIL_DIR"
cat "storage/app/councils/$COUNCIL_DIR/manifest.json"
```

**Commit:**
```bash
git add .
git commit -m "test: validate full council generation"
```

### Milestone 4.3: (Removed) Next.js/Vercel harness

**Step 1: Push Chat Changes**
```bash
echo "(Deprecated)"
git add .
git commit -m "feat: configure for council testing"
git push origin main
```

**Step 2: (Removed)**
```bash
# Deploy the forked chatbot
vercel --prod

# Get deployment URL
// (Removed) Vercel deploy examples
```

**Step 3: Share with Team**
```markdown
# Council Testing Guide

## Test URL
// (Removed)

## How to Test
1. Upload PI/PK files for each advisor
2. Test with standard questions:
   - "What's your approach to [problem]?"
   - "Give me an example from your experience"
   - "What would you avoid?"

## Quality Checklist
- [ ] Voice is distinct
- [ ] Examples are specific
- [ ] Advice is actionable
- [ ] No generic responses
```

**Commit:**
```bash
git add .
git commit -m "docs: add council testing guide"
```

**Success Criteria:**
- ✅ All 4 advisors generated successfully
- ✅ Each maintains distinct voice and expertise
- ✅ Quality scores all above 80%
- ✅ Test chat deployed and accessible
- ✅ Team can test advisors easily

---

## Day 4: Implement Council Mode [FUTURE WORK]
**Status:** ❌ Not implemented - Architecture documented for future development
**Note:** All council code below represents planned architecture, not current implementation

### Critical Understanding: Council PI Architecture

**IMPORTANT FOR JUNIOR DEVELOPERS:**
- **Council PI is ONE orchestration file** that routes between advisors
- **Individual PK files stay separate** and are loaded dynamically  
- **Meta template generates verbose version** (~150 lines)
- **Production PI needs compression** (~87 lines, see `Advisors - Bog Halbert Homz Cal/PI.md`)

### Milestone 5.1: Build DYNAMIC Council PI Generation Service (2 hours)

**CRITICAL: This service handles N advisors, not hardcoded 4!**

**Step 1: Create Dynamic Council PI Orchestration Service**
```php
// app/Services/AdvisorGeneration/DynamicCouncilPIService.php
<?php

namespace App\Services\AdvisorGeneration;

use Illuminate\Support\Collection;

class DynamicCouncilPIService
{
    public function __construct(
        private TemplateLoaderService $templateLoader,
        private ContentFormattingService $formatter,
        private DynamicRouterService $routerService
    ) {}
    
    public function generateCouncilPI(
        Collection $advisors, // Can be 2, 4, 8, or 12 advisors!
        MetaInstructions $meta,
        PlayerContext $playerContext
    ): GeneratedFile {
        Log::info('Generating DYNAMIC Council PI', [
            'advisor_count' => $advisors->count(),
            'player' => $playerContext->name
        ]);
        
        // Choose template based on advisor count
        $template = $this->selectTemplateForSize($advisors->count());
        
        // Build dynamic data (NO hardcoded names!)
        $templateData = $this->buildDynamicTemplateData($advisors, $playerContext);
        
        // Process template
        $mustache = new \Mustache_Engine;
        $content = $mustache->render($template, $templateData);
        
        // Apply progressive compression based on count
        $content = $this->applyDynamicCompression($content, $advisors->count());
        
        return new GeneratedFile(
            filename: 'Council_PI.md',
            content: $content,
            type: 'council_pi',
            metadata: [
                'version' => 'v2.0',
                'advisor_count' => $advisors->count(),
                'is_dynamic' => true
            ]
        );
    }
    
    private function buildDynamicTemplateData(Collection $advisors, PlayerContext $player): array
    {
        $data = [
            // Dynamic mode tags (not hardcoded!)
            'mode_tags' => $advisors->map(fn($a) => $a->getTag())->join('|'),
            'council_name' => $player->name . "'s Advisory Council",
            
            // Player context (personalized)
            'player_name' => $player->name,
            'player_performance_standards' => $player->getPerformanceMetrics(),
            'player_background_context' => $player->background,
        ];
        
        // Add advisor-specific data dynamically
        $advisors->each(function($advisor, $index) use (&$data) {
            $num = $index + 1;
            $data["advisor_{$num}_tag"] = $advisor->getTag();
            $data["advisor_{$num}_name"] = $advisor->name;
            $data["advisor_{$num}_domain"] = $advisor->domain;
            $data["advisor_{$num}_voice"] = $advisor->getVoiceCharacteristics();
            $data["primary_domain_{$num}"] = $advisor->getPrimaryTriggers();
        });
        
        // Generate dynamic routing rules
        $data['router_rules'] = $this->routerService->generateDynamicRules($advisors);
        $data['multi_domain_rules'] = $this->routerService->generateCrossoverRules($advisors);
        
        return $data;
    }
    
    private function applyDynamicCompression(string $content, int $advisorCount): string 
    {
        // Progressive compression based on advisor count
        $compressionLevel = match(true) {
            $advisorCount <= 2 => 'none',        // Keep full detail
            $advisorCount <= 4 => 'light',       // ~100 lines
            $advisorCount <= 8 => 'moderate',    // ~80 lines
            $advisorCount <= 12 => 'aggressive', // ~60 lines
            default => 'extreme'                 // Maximum compression
        };
        
        return $this->compress($content, $compressionLevel);
    }
    
    private function selectTemplateForSize(int $count): string
    {
        // Different templates optimized for different council sizes
        return match(true) {
            $count <= 2 => 'duo_council_template',    // Detailed interaction
            $count <= 4 => 'standard_council_template', // Balanced
            $count <= 8 => 'large_council_template',   // Compressed
            default => 'mega_council_template'         // Highly compressed
        };
    }
}
```

**Step 2: Create Dynamic Router Service**
```php
// app/Services/AdvisorGeneration/DynamicRouterService.php
class DynamicRouterService
{
    public function generateDynamicRules(Collection $advisors): array
    {
        // Build routing based on actual advisor expertise
        return $advisors->map(function($advisor) use ($advisors) {
            $others = $advisors->reject(fn($a) => $a->id === $advisor->id);
            
            return [
                'trigger' => $advisor->getPrimaryKeywords(),
                'lead' => $advisor->getTag(),
                'support' => $this->findComplementary($advisor, $others)
            ];
        })->toArray();
    }
    
    public function generateCrossoverRules(Collection $advisors): array
    {
        // Find natural advisor combinations
        $rules = [];
        
        // Example: If you have a marketer + developer
        if ($this->hasTypes($advisors, ['marketing', 'technical'])) {
            $rules[] = 'Product launches get marketing lead + technical support';
        }
        
        // Example: If you have sales + creative
        if ($this->hasTypes($advisors, ['sales', 'creative'])) {
            $rules[] = 'Campaign development gets creative lead + sales validation';
        }
        
        return $rules;
    }
}
```

**Step 3: Support User-Generated Advisors**
```php
// app/Console/Commands/GenerateCustomCouncilCommand.php
class GenerateCustomCouncilCommand extends Command
{
    protected $signature = 'council:generate-custom 
        {--advisors=* : Advisor IDs to include}
        {--user= : User ID for personalization}';
    
    public function handle(DynamicCouncilPIService $service)
    {
        // Get user's custom advisors
        $user = User::find($this->option('user'));
        $advisorIds = $this->option('advisors') ?: $user->advisors->pluck('id');
        
        // Load advisors (can be ANY advisors, not just seeds!)
        $advisors = Advisor::whereIn('id', $advisorIds)->get();
        
        $this->info("Generating council with {$advisors->count()} advisors:");
        $this->table(['Name', 'Domain'], 
            $advisors->map(fn($a) => [$a->name, $a->domain])->toArray()
        );
        
        // Generate dynamic council PI
        $councilPI = $service->generateCouncilPI(
            $advisors,
            new MetaInstructions('council'),
            new PlayerContext($user)
        );
        
        $this->info("✅ Council PI generated for {$advisors->count()} advisors");
    }
}

**Commit:**
```bash
echo "(Deprecated)"
git add .
git commit -m "feat: add council mode to chat interface"
git push origin main
```

### Milestone 5.2: Progressive Learning - Seeds to Dynamic (CRITICAL - 1 hour)

**Step 1: Start with Seed Advisors (LEARNING ONLY)**
```php
// Day 1-2: Use 4 seed advisors to LEARN concepts
class AdvisorSeeder extends Seeder
{
    public function run()
    {
        // These are EXAMPLES, not requirements!
        $examples = [
            ['name' => 'Alex Bogusky', 'domain' => 'Creative Strategy'],
            ['name' => 'Gary Halbert', 'domain' => 'Direct Response'],
            ['name' => 'Alex Hormozi', 'domain' => 'Business Growth'],
            ['name' => 'Cal Henderson', 'domain' => 'Technical Architecture']
        ];
        
        $this->command->warn('⚠️ These are EXAMPLE advisors for learning!');
        $this->command->warn('⚠️ Users will create their OWN advisors!');
        
        foreach ($examples as $example) {
            Advisor::create($example);
        }
    }
}
```

**Step 2: Transition to User-Generated Advisors**
```php
// Day 3: Support ANY advisor combination
class GenerateUserAdvisorCommand extends Command
{
    public function handle()
    {
        // User creates their OWN advisor
        $advisor = Advisor::create([
            'name' => $this->ask('Advisor name?'), // "Warren Buffett"
            'domain' => $this->ask('Domain?'),     // "Value Investing"
            'user_id' => auth()->id(),
            // Custom advisor, not from seeds!
        ]);
        
        // Generate PI/PK for this NEW advisor
        $this->generateAdvisorFiles($advisor);
    }
}
```

**Step 3: Build Dynamic Councils from ANY Advisors**
```php
// Mix seed advisors with user-generated ones
$council = collect([
    $user->advisors()->find('custom-warren-buffett-id'),
    $user->advisors()->find('custom-peter-thiel-id'),
    Advisor::seed()->find('bogusky-id'), // Optional seed
    $user->advisors()->find('custom-marie-forleo-id'),
]);

// System handles ANY combination!
$councilPI = $dynamicService->generateCouncilPI($council);
```

### Milestone 5.3: Understanding Meta Template vs Production PI (30 minutes)

**The Gap Between Template and Production:**

**Step 1: Compare the Two Reference Points**
```bash
# Meta Template Output (Verbose ~150 lines)
cat resources/advisor-templates/versions/v1.0.0/meta/meta_council_pi_template.md

# Best-Performing Production PI (Compressed ~87 lines)  
cat "storage/app/advisor-files/versions/Advisors - Bog Halbert Homz Cal/PI.md"
```

**Key Differences to Study:**

| Aspect | Meta Template Output | Production PI |
|--------|---------------------|---------------|
| Length | ~150 lines | ~87 lines |
| Principles | Multi-line explanations | Single line with bullets |
| Router | Verbose descriptions | Simple arrows (→) |
| Advisors | Full names | Abbreviations (B, H, Hz, C) |
| Player MAB | Generic placeholders | Specific metrics (CTR ≥1.0%) |
| Aliases | Not included | Smart name resolution |
| Metadata | None | YAML control trailer |

**Step 2: Implement Compression Pipeline**
```php
// app/Services/AdvisorGeneration/CouncilCompressionService.php
class CouncilCompressionService
{
    public function compressForProduction(string $verboseContent): string
    {
        // Transform verbose meta template output to production-ready format
        $stages = [
            'abbreviateAdvisors',      // Bogusky → (B)
            'compressPrinciples',       // Multi-line → Single line  
            'simplifyRouter',           // Verbose → Arrows
            'addNameResolution',        // Handle "Alex" ambiguity
            'addSpecificMetrics',       // Generic → CTR ≥1.0%
            'addControlTrailer'         // Add YAML metadata
        ];
        
        foreach ($stages as $method) {
            $verboseContent = $this->$method($verboseContent);
        }
        
        return $verboseContent;
    }
    
    private function compressPrinciples(string $content): string
    {
        // Before: "- **Voice Preservation:** Never blend advisor voices..."
        // After: "Find the enemy (B) • Headline=80% (H) • Value>Price (Hz)"
        
        $principles = [
            'Bogusky' => 'Find the enemy',
            'Halbert' => 'Headline=80%',
            'Hormozi' => 'Value>Price',
            'Cal' => 'Ship small'
        ];
        
        $compressed = implode(' • ', array_map(
            fn($advisor, $principle) => "$principle (" . $this->getAbbreviation($advisor) . ")",
            array_keys($principles),
            $principles
        ));
        
        return preg_replace(
            '/## \*\*Operating Principles\*\*.*?(?=##)/s',
            "## Operating Principles\n$compressed\n\n",
            $content
        );
    }
}
```

**Step 3: Add Name Resolution Logic**
```php
private function addNameResolution(string $content): string
{
    $nameResolution = <<<'MARKDOWN'

## Name Resolution (Aliases)
- "Alex" (no last name) → **[Mode: Bogusky]**
- "Hormozi", "Alex H", or words: **offers/pipeline/guarantee** → **[Mode: Hormozi]**
- "Bogusky", "Alex B", or words: **PR/Thing/hooks** → **[Mode: Bogusky]**
- "Cal", "Henderson", or words: **architecture/DX/PRD** → **[Mode: Cal]**
- If unclear, ask once; default to **[Mode: Bogusky]**
MARKDOWN;

    // Insert before Format Options section
    return preg_replace(
        '/## Format Options/',
        $nameResolution . "\n## Format Options",
        $content
    );
}
```

**Commit:**
```bash
git add .
git commit -m "feat: add council PI compression pipeline"
```

### Milestone 5.3: Test Council PI Routing (CRITICAL - 1.5 hours)

**Step 1: Test Mode Tag Routing**
```bash
# Generate test council
php artisan council:generate-pi

# Copy to test environment
echo "Council artifacts ready in storage/app/councils/latest and storage/app/advisor-files"
```

**Step 2: Validate Routing with Test Questions**
```markdown
## Routing Test Cases

### Test 1: Name Resolution
Input: "Alex, what should I do?"
Expected: Routes to Bogusky (default for ambiguous "Alex")

### Test 2: Keyword Routing  
Input: "Need help with pricing and offers"
Expected: Routes to Hormozi (keywords: pricing, offers)

### Test 3: Explicit Mode
Input: "[Mode: Halbert] Write a headline"
Expected: Routes to Halbert explicitly

### Test 4: Council Mode
Input: "[Mode: Council] Launch strategy?"
Expected: Multiple advisors respond with Lead + Support structure
```

**Step 3: Quality Validation Checklist**
```php
// tests/Feature/CouncilPITest.php
it('validates council PI has all critical sections', function () {
    $councilPI = file_get_contents('storage/app/councils/latest/Council_PI.md');
    
    // Critical sections that MUST exist
    expect($councilPI)->toContain('MAB Guardrail');
    expect($councilPI)->toContain('[Mode:');
    expect($councilPI)->toContain('Voice Loading Protocol');
    expect($councilPI)->toContain('Router');
    expect($councilPI)->toContain('Name Resolution');
    
    // Compression achieved
    $lineCount = substr_count($councilPI, "\n");
    expect($lineCount)->toBeLessThan(100);
    
    // Has YAML control trailer
    expect($councilPI)->toContain('```yaml');
});
```

**Commit:**
```bash
git add .
git commit -m "test: validate council PI routing and compression"
```

### Milestone 5.4: Common Pitfalls & Solutions (1 hour)

**CRITICAL PITFALLS JUNIOR DEVELOPERS MUST AVOID:**

**Pitfall #1: Thinking Council PI = Merged Individual PIs**
```php
// ❌ WRONG - This creates voice soup
$councilPI = $boguskyPI . $halbertPI . $hormoziPI . $calPI;

// ✅ RIGHT - This creates an orchestration layer
$councilPI = $this->generateCouncilOrchestrator($advisors);
```

**Pitfall #2: Not Compressing Meta Template Output**
```php
// ❌ WRONG - Shipping verbose 150-line template output
return $mustache->render($template, $data);

// ✅ RIGHT - Compressing to production-ready 87 lines
$content = $mustache->render($template, $data);
return $this->compressForProduction($content);
```

**Pitfall #3: Generic Player Context**
```php
// ❌ WRONG - Generic placeholders
'player_performance_standards' => '{{performance_standards}}'

// ✅ RIGHT - Specific metrics
'player_performance_standards' => 'CTR ≥1.0%, CVR ≥10% lead, Time to value ≤ 3 min'
```

**Pitfall #4: Missing Voice Loading Protocol**
```markdown
❌ WRONG - Vague instruction
"Load the appropriate advisor knowledge"

✅ RIGHT - Explicit loading
"**[Mode: Bogusky]** → Load **AlexBogusky_PK.md** for voice anchor"
```

**Pitfall #5: No Name Resolution**
```markdown
❌ WRONG - Ambiguous routing
User: "Alex, what should I do?"
System: [Confused which Alex]

✅ RIGHT - Smart defaults
User: "Alex, what should I do?"
System: [Routes to Bogusky as default]
```

**Step 2: Create Debugging Guide**
```php
// app/Console/Commands/DebugCouncilCommand.php
class DebugCouncilCommand extends Command
{
    protected $signature = 'council:debug';
    
    public function handle()
    {
        $this->info('Council PI Debug Checklist:');
        
        $checks = [
            'Has MAB Guardrail first' => $this->checkMABGuardrail(),
            'Mode tags present' => $this->checkModeTags(),
            'Voice Loading explicit' => $this->checkVoiceLoading(),
            'Router rules clear' => $this->checkRouterRules(),
            'Name resolution exists' => $this->checkNameResolution(),
            'Under 100 lines' => $this->checkCompression(),
            'Has YAML trailer' => $this->checkControlTrailer()
        ];
        
        $this->table(['Check', 'Status'], 
            collect($checks)->map(fn($status, $check) => [$check, $status ? '✅' : '❌'])->all()
        );
    }
}
```

**Commit:**
```bash
git add .
git commit -m "docs: add council pitfalls and debugging guide"
```

**Success Criteria:**
- ✅ Council PI is ONE orchestration file (not merged PIs)
- ✅ Meta template output gets compressed to <100 lines
- ✅ Player context has specific metrics, not placeholders
- ✅ Voice Loading Protocol explicitly loads PK files
- ✅ Name resolution handles ambiguous references
- ✅ YAML control trailer provides metadata

### Milestone 5.6: Real-World Dynamic Council Examples (30 minutes)

**Example 1: Startup Founder's Custom Council (5 advisors)**
```php
$founderCouncil = collect([
    new Advisor(['name' => 'Paul Graham', 'domain' => 'Startup Strategy']),
    new Advisor(['name' => 'Naval Ravikant', 'domain' => 'Leverage & Wealth']),
    new Advisor(['name' => 'April Dunford', 'domain' => 'Positioning']),
    new Advisor(['name' => 'Rand Fishkin', 'domain' => 'SEO & Content']),
    new Advisor(['name' => 'Katrina Lake', 'domain' => 'Data-Driven Retail'])
]);

// System generates Mode tags: [Mode: Graham|Naval|Dunford|Fishkin|Lake|Council]
$councilPI = $dynamicService->generateCouncilPI($founderCouncil);
```

**Example 2: Creative Agency's Council (3 advisors)**
```php
$agencyCouncil = collect([
    new Advisor(['name' => 'David Droga', 'domain' => 'Creative Excellence']),
    new Advisor(['name' => 'Margaret Johnson', 'domain' => 'Brand Strategy']),
    new Advisor(['name' => 'Stefan Sagmeister', 'domain' => 'Design Philosophy'])
]);

// Fewer advisors = less compression needed
$councilPI = $dynamicService->generateCouncilPI($agencyCouncil);
```

**Example 3: Technical Leader's Council (8 advisors)**
```php
$techCouncil = collect([
    new Advisor(['name' => 'Kent Beck', 'domain' => 'Agile/XP']),
    new Advisor(['name' => 'Martin Fowler', 'domain' => 'Architecture']),
    new Advisor(['name' => 'Jessica Kerr', 'domain' => 'Distributed Systems']),
    new Advisor(['name' => 'Kelsey Hightower', 'domain' => 'Kubernetes']),
    new Advisor(['name' => 'Charity Majors', 'domain' => 'Observability']),
    new Advisor(['name' => 'Julia Evans', 'domain' => 'Systems Debugging']),
    new Advisor(['name' => 'Bryan Cantrill', 'domain' => 'OS & Performance']),
    new Advisor(['name' => 'Jessie Frazelle', 'domain' => 'Containers'])
]);

// 8 advisors triggers moderate compression
$councilPI = $dynamicService->generateCouncilPI($techCouncil);
// Result: ~80 lines with compressed router rules
```

**Example 4: Mixing Seeds with Custom (Hybrid)**
```php
$hybridCouncil = collect([
    // Two from seeds (optional examples)
    Advisor::where('name', 'Alex Bogusky')->first(),
    Advisor::where('name', 'Alex Hormozi')->first(),
    
    // Three custom user-created
    new Advisor(['name' => 'Sara Blakely', 'domain' => 'Product Development']),
    new Advisor(['name' => 'Tim Ferriss', 'domain' => 'Productivity']),
    new Advisor(['name' => 'Brené Brown', 'domain' => 'Leadership'])
]);

// System treats ALL advisors equally - seeds have no special status
$councilPI = $dynamicService->generateCouncilPI($hybridCouncil);
```

**The Key Point:** Every user creates their own unique mix of advisors based on their needs. The 4 seed advisors (Bogusky, Halbert, Hormozi, Henderson) are just examples to teach the system - not requirements!

---

## Temperature Settings & Quality Focus

### Optimal Temperature Configuration
Based on lessons learned, temperature settings are critical for quality:

| Advisor Type | Temperature | Rationale |
|--------------|-------------|-------|
| Technical (Henderson) | 0.7 | Precision and accuracy critical |
| Copywriting (Halbert) | 0.7 | Name accuracy and clean copy essential |
| Business (Hormozi) | 0.8 | Balance of data accuracy and personality |
| Creative (Bogusky) | 0.85 | Can handle higher creativity without breaking |
| **NEVER USE** | 0.9+ | Causes hallucinations, name corruption, token repetition |

### Quality Through Authenticity (Not Compliance)
The real quality metrics that matter:
- **Confrontational Tone**: Challenges user thinking
- **Specific Examples**: Real companies, actual numbers
- **First-Person Voice**: Consistent "I", "my", "I've" usage
- **Uncomfortable Truths**: What users need to hear, not want to hear
- **Template Compliance Scores Are Bullshit**: They measure wrong things

### Analytical Tensions Framework (The Secret Sauce)
This is how we achieve quality WITHOUT deep research models:

```php
// Build analytical tensions for each advisor
private function buildAnalyticalTensionsPrompt($advisorData): string
{
    return "
    Generate {$advisorData['name']}'s expertise using analytical tensions:
    
    1. PARADOX: What everyone believes vs reality
       Example: 'Everyone thinks viral marketing is about luck. 
                It's actually about cultural tension points.'
    
    2. EVIDENCE: Specific companies, real numbers
       Example: 'Burger King's Subservient Chicken: 500M views, 
                $80M sales increase, 14% market share gain'
    
    3. CONSTRAINT: Why problems persist
       Example: 'CMOs last 18 months on average because they 
                optimize for safety, not effectiveness'
    
    4. UNCOMFORTABLE TRUTH: What to do instead
       Example: 'Fire your agency if they show you mood boards. 
                You need enemies, not aesthetics.'
    ";
}
```

## Summary: What We're Building

### Phase 1: Foundation (Days 1-2)
- ✅ Laravel project with Herd, Boost, Horizon
- ✅ PI generation (deterministic template rendering)
- ✅ PK generation (LLM enrichment for 300+ word examples)
- ✅ Quality validation with retry logic
- ✅ Test chat for rapid iteration
- ✅ **4 EXAMPLE advisors for learning** (NOT requirements!)

### Phase 2: Council Orchestration (Days 3-4)
- ✅ **Multi-advisor interaction** built on strong voices
- ✅ **Productive disagreement** (only works with authentic advisors)
- ✅ **Dynamic routing** based on real expertise
- ✅ **Quality requirement:** 90% individual voices or councils fail
- ⚠️ **Critical insight:** Weak advisors compound into weak councils

### Phase 3: Progressive Enhancement (Week 2+)
- 🎯 **Polish what works:** Enhance already-strong voices
- 🎯 **Council dynamics:** Improve interaction patterns
- 🎯 **Edge cases:** Handle complex multi-domain questions
- ✅ **Foundation:** Start with quality, then optimize
- ✅ **Mix seeds with custom** advisors freely

### Critical Architecture Understanding
- **System handles ANY advisors** - Seeds are just examples
- **Council PI = Dynamic Orchestration** for N advisors
- **Compression scales** with advisor count (2→none, 12→aggressive)
- **Every user gets unique councils** based on their needs
- **No hardcoded advisor names** in production code

### What We're NOT Building (Yet)
- ❌ Caching optimization (separate doc)
- ❌ Scaling to 10+ advisors (separate doc)
- ❌ User authentication
- ❌ Payment integration
- ❌ Production deployment

### What Makes This Different
- **Hybrid PI Generation** - Deterministic base + Grok-3 enhancement
- **PK uses Analytical Tensions** - Standard models beat deep research
- **Temperature Optimization** - 0.7-0.85 range, never 0.9+
- **Voice Anchors Essential** - 3-4 sentences establishing identity
- **Council PI is ONE file** - Orchestrates multiple advisors
- **Quality Through Authenticity** - Not template compliance scores
- **Laravel Boost** - Rapid development with MCP integration

## Voice Experimentation Protocol (Day 2 PM)

### Why This Matters
- **Council debugging is complex** - Need solid individual voices first
- **Voice format affects everything** - Test now, not after building councils
- **20-30 minutes saves days** - Quick tests prevent major rework

### Test Matrix (A/B Test Each)
| Variable | Option A | Option B | Test Method |
|----------|----------|----------|-------------|
| Voice Examples | 3 short sentences | 300+ word narratives | Generate both, chat 5 messages |
| Pattern Format | Bullet list | Embedded in examples | Check consistency |
| Anti-patterns | Explicit list | Implicit from examples | Try to break voice |
| Voice DNA | Short tagline | Paragraph explanation | First message quality |

### Quick Test Protocol
1. Generate Bogusky with Option A (5 min)
2. Test 5 exchanges in chat (5 min)
3. Generate Bogusky with Option B (5 min)
4. Test same 5 questions (5 min)
5. Document which felt more authentic
6. Apply learning to all advisors

## Development Workflow

### Strategic Development Progression:
1. **Day 1-2:** Core advisor generation (basic 80% quality)
2. **Day 2 PM:** Voice experimentation sprint (20-30 min tests)
3. **Day 3:** Apply learnings to reach 90%+ quality
4. **Day 4-5:** Council orchestration on proven foundation
5. **Reality:** Must validate voice quality BEFORE councils or debugging becomes impossible

### Required Quality Bar (90% Minimum):
- [ ] Strong, distinctive voice from the first message
- [ ] Rich examples that feel authentic
- [ ] Pushes back with real expertise
- [ ] Specific frameworks and methodologies
- [ ] Would feel valuable even standalone
- [ ] **Truth:** Mediocre advisors = useless council

## Success Metrics

### Phase 1 (Days 1-2): Individual Advisor Foundation
- **Initial target: 80%** to have something testable
- **Experimentation sprint:** Test voice variations (20-30 min)
  - Short vs long voice examples
  - Bullet points vs narrative examples
  - Pattern lists vs embedded patterns
  - Anti-pattern enforcement levels
- **Final target: 90%+** after applying learnings
- **Key insight:** Test voice approaches BEFORE council complexity

### Phase 1.5 (Day 2 PM): Voice Experimentation Sprint
- **Duration:** 2-3 hours focused testing
- **Test variations:**
  - 3 sentence examples vs 300+ word examples
  - Voice DNA statements vs extended narratives
  - Explicit patterns vs learned-by-example
- **Rapid iteration:** Generate → Test → Document impact
- **Output:** Clear data on what drives quality

### Phase 2 (Days 4-5): Council Excellence
- **Built on strong foundation:** 90% advisors minimum
- **Primary metric:** Productive tension and synthesis
- **Key test:** Does it feel like real advisors arguing?
- **Learning:** How do strong voices interact?
- Specific examples in every response
- Consistent voice maintenance
- Clear personality distinction

### Phase 2 (Days 3-4): Reliable Generation
- Success rate > 90%
- Generation time < 30 seconds
- Quality validation working
- 10+ advisors in library

### Phase 3 (Week 2): Council System
- N-advisor generation working
- Complementary expertise
- Distinct voices maintained
- Ready for production use

## The Key Insight

The chat interface is just a test tool - like a unit test for advisor quality. The real work happens in Laravel:
1. Meta template refinement
2. Prompt engineering  
3. Quality validation
4. Generation optimization
5. **Council PI orchestration** (routing layer, not merging)
6. **Compression pipeline** (verbose → production-ready)

Every commit should improve advisor quality, not chat features.

## Critical Learning for Dynamic Council Implementation

**For Junior Developers - The Progressive Path:**

### Day 1-2: Learn with Seeds
- Use 4 example advisors (Bogusky, Halbert, Hormozi, Henderson)
- Understand PI/PK generation
- Learn routing and voice loading
- **These are just teaching examples!**

### Day 3-4: Build Dynamic System
- Support ANY number of advisors (2-12+)
- Handle user-generated advisors (Warren Buffett, Oprah, anyone!)
- Dynamic routing based on actual expertise
- Progressive compression based on count

### Key Architectural Principles
- **Council PI = Dynamic Orchestrator** (not merged PIs)
- **N advisors supported** (not hardcoded 4)
- **Seeds are examples** (not requirements)
- **Every user is unique** (custom advisor mixes)
- **Compression scales** (2 advisors = full detail, 12 = aggressive)

### The Meta Template Gap
The gap between meta template (150 lines) and production PI (87 lines) is intentional:
1. **Verbose template** → Educational, shows all options
2. **Compressed production** → Optimized for performance
3. **Dynamic compression** → Adjusts based on advisor count

### Final Reality Check
```php
// ❌ WRONG: Thinking in terms of fixed advisors
if ($advisor == 'bogusky') { /* special logic */ }

// ✅ RIGHT: Everything is dynamic
foreach ($council->advisors as $advisor) {
    $rules[] = $this->generateDynamicRule($advisor);
}
```

**Remember:** Users will create advisors you've never heard of. The system must handle ANY advisor, not just the ones we used as examples!

## Future Implementation Requirements

### When We Get to Council PI Implementation

The following enhancements need to be implemented when we reach the Council PI generation phase:

#### 1. Progressive Compression System
- **2 advisors:** No compression, full verbose template
- **4 advisors:** Light compression (remove optional sections)
- **8 advisors:** Moderate compression (condense descriptions)
- **12+ advisors:** Aggressive compression (minimal per-advisor content)
- Method: `applyProgressiveCompression($content, $advisorCount)`

#### 2. Dynamic Player Context
- Remove hardcoded "Ben Fisher" from `buildCouncilPromptFromTemplate`
- Accept player context as parameter from database/request
- Support custom player profiles and preferences

#### 3. Template Selection by Size
- Implement `selectTemplateForSize($advisorCount)` method
- Create size-specific template variants if needed
- Fallback to base template with compression

#### 4. Dynamic Router Service
- Build intelligent domain routing based on actual advisor expertise
- No hardcoded routing rules
- Generate routing heuristics from advisor definitions

#### 5. Compression Methods
- `compressRoutingRules($rules, $compressionLevel)`
- `compressAdvisorDescriptions($descriptions, $compressionLevel)`
- `removeOptionalSections($content, $sectionsToRemove)`
- `consolidateSimilarDomains($advisors)`


### Current Implementation Status
✅ **Working:** Dynamic advisor support (2-12), no hardcoded names, template-based generation
❌ **Missing:** Progressive compression, dynamic player context, size-based templates
🔄 **Next Phase:** Will implement these when we reach Council PI milestone

---

## Auth (Sanctum) — Deferred Setup (for UI milestone)
```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\\Sanctum\\SanctumServiceProvider"
php artisan migrate
```
Env:
- `SESSION_DOMAIN=.yourdomain.com`
- `SANCTUM_STATEFUL_DOMAINS=app.yourdomain.com,localhost,127.0.0.1`
Notes:
- Protect routes with `auth:sanctum`; enable CSRF + credentials on client.
- Use PATs or signed HMAC for server‑to‑server integrations.
