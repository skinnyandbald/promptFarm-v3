# PromptFarm v4 — Implementation Guide (Laravel 12, CLI‑First)

**Version:** 3.0.0 | **Updated:** September 1, 2025  
**Model Strategy:** GPT-4 Turbo (PK generation), Grok-3 via OpenRouter (PI enhancement)
**Key Insight:** Solution is PROMPTING with analytical tensions, NOT deep research models
**Approach:** NEW PROJECT from scratch - NOT a refactor

## 🚀 START HERE: Speed-to-Value Approach

**This guide is for developers building v4 from scratch.** You'll have a working advisor generation pipeline quickly by:
1. Starting with CLI-only generation and local files (Day 1)
2. Validating single-advisor quality with PI/PK (Day 2)
3. Adding council generation later (M1+), then UI once quality is nailed

See `MILESTONE_BREAKDOWN.md` for the exact commit-by-commit plan.

## Product Vision & Context

### The Problem We're Solving

**For Who:** Entrepreneurs, marketers, and product builders who need real strategic advice but face these realities:
- Their friends have never built what they're building (useless advice)
- Real advisors are 10 steps ahead and forget what your stage feels like
- Generic ChatGPT gives surface-level, one-size-fits-all responses
- CEO groups often lack relevant experience in your specific domain

**The Core Problem:** Even if you could access world-class advisors, three fundamental issues remain:
1. **Time Gap**: They built their companies in a different era (what worked in 2010 is irrelevant in 2025)
2. **Attention Gap**: They have 5 minutes to understand your complex situation
3. **Memory Gap**: They don't viscerally remember your stage - they're thinking from their current $100M reality

**The Reality Check - Who Are You Actually Asking for Advice?**
- **Your friends**: Never built what you're building ("have you tried social media?")
- **Your advisors**: Built something huge... 15 years ago, in a different market, with different tools
- **Successful founders**: Give you 5 minutes at a networking event, generic platitudes
- **CEO groups**: People at your level who are just as lost as you are
- **Generic ChatGPT**: "AI soup" - agreeable, no push-back, no tension, just tells you what you want to hear

**The ChatGPT Problem (Garbage In, Garbage Out):**
- You prompt: "Should I raise prices?" → ChatGPT: "Here are pros and cons..." (useless)
- You want: "Your pricing is weak. Double it. Here's why, here's how, here's what will break..."
- Generic AI is optimized to be helpful and agreeable - the opposite of what you need from an advisor

**The Hidden Technical Barrier:**
- **Prompt Engineering**: A deep technical discipline requiring months of expertise
- **Context Engineering**: Managing token limits, information hierarchy, attention mechanisms
- **Voice Consistency**: Extended examples, pattern matching, anti-pattern enforcement
- **Challenge Calibration**: Programming productive disagreement without breaking the interaction
- Most entrepreneurs would need 100+ hours to learn this - time they don't have

### What PromptFarm Does

**Core Value Proposition:** PromptFarm synthetically bridges the gap between world-class expertise and your specific stage/context. It takes advisors who are millions of steps ahead and makes their frameworks relevant to exactly where you are now.

**The Magic:** Through AI, we synthetically solve what human advisors physically can't:
- **Unlimited Bandwidth**: Not 5 rushed minutes - deep understanding of your full context
- **Temporal Translation**: Takes their timeless principles but applies them to 2025 markets, tools, and channels
- **Stage Memory**: Artificially "remembers" what $10K MRR feels like, even from a $100M perspective
- **Personalized Frameworks**: Their mental models adapted to YOUR specific constraints, team size, and resources
- **Real Push-Back**: Challenges your thinking like they would, but with understanding of your actual limitations

**What This Means**: You get Hormozi's value engineering, but for someone with 2 employees not 200. You get Bogusky's provocative thinking, but for TikTok not TV commercials. You get Cal Henderson's scaling wisdom, but for your 10K users not Slack's 10 million.

**The Instrument Philosophy (Not Just a Tool):**
- **Tools** give you what you ask for (ChatGPT's agreeable responses)
- **Instruments** create productive tension that makes YOU better
- PromptFarm advisors are programmed to push back, challenge assumptions, and demand specificity
- This isn't about the AI being smart - it's about creating a dynamic that forces YOUR best thinking

**Built-In Productive Tension:**
- Advisors refuse vague requests ("I need better marketing" → "Better how? What metric? By when?")
- They call out weak thinking ("That's not a strategy, that's a hope")
- They demand evidence ("Show me the data or stop guessing")
- They maintain their perspective even when you disagree (no "you're absolutely right!" AI soup)

**How It Works:**
1. **Select an Advisor**: Choose from experts like Bogusky (provocative marketing), Hormozi (value engineering), Cal Henderson (technical leadership/scaling), or Gary Halbert (direct response)
2. **Generate Persona Files**: System creates two files:
   - **PI (Project Instructions)**: Behavioral rules, response patterns, interaction style (~1000 words)
   - **PK (Project Knowledge)**: Deep expertise, methodologies, examples, case studies (4000-6000 words)
3. **Paste into ChatGPT**: Copy both files into a ChatGPT conversation
4. **Get Stage-Appropriate Expertise**: ChatGPT now channels that expert's knowledge adapted to YOUR current reality

### User Stories & Examples

**Story 1: Startup Founder at $10K MRR (Not $100M)**
- *As a* startup founder at $10K MRR (not Hormozi's $100M level)
- *I want to* get Hormozi's frameworks adapted to my actual stage
- *So that* I can apply his value principles without needing a 50-person sales team
- *Example interaction*: "Alex, I'm at $10K MRR with 20 customers. How should I structure my $500/mo offer?" 
- *What happens*: Instead of "hire 10 setters and 5 closers" (his current reality), you get: "At your stage, focus on one killer guarantee and two bonuses that cost you nothing but create massive perceived value. Here's exactly how..."

**Story 2: Agency Owner Needs Creative Campaign**
- *As a* creative agency owner pitching a conservative client
- *I want to* channel Alex Bogusky's provocative approach
- *So that* I can create a campaign that cuts through the noise
- *Example interaction*: "Bogusky, client sells insurance. They want 'safe.' Help me push them toward brave." → Gets cultural tension points, enemy identification, truth-telling frameworks

**Story 3: CTO Needs Scaling Strategy**
- *As a* CTO of a fast-growing startup hitting scaling walls
- *I want to* apply Cal Henderson's Slack engineering principles
- *So that* I can build systems that scale from 10 to 10 million users
- *Example interaction*: "Cal, we're at 100K users and everything's breaking. How did Slack handle this phase?" → Gets specific architectural patterns, team structure advice, technical debt management strategies

**Story 4: E-commerce Owner Needs Sales Copy**
- *As an* e-commerce store owner with low conversion
- *I want to* apply Gary Halbert's direct response principles
- *So that* I can write product descriptions that actually sell
- *Example interaction*: "Gary, selling premium kitchen knives. Current copy is features-focused. Fix it." → Gets benefit-driven copy, headline formulas, guarantee structures

### Why This Matters (The Brutal Truth)

**The Advisor Paradox:** The people who can actually help you are impossibly inaccessible, temporally displaced, or cognitively removed from your reality. The people who ARE accessible either haven't done what you're doing or did it in an irrelevant context.

**What PromptFarm Enables:** For the first time, you can have a board of advisors who:
- Actually understand what you're building (unlimited context)
- Remember what your stage feels like (synthetic stage memory)  
- Apply their principles to TODAY's market (temporal translation)
- Have time to deeply understand your situation (not 5 rushed minutes)
- Push you hard while respecting your constraints (calibrated challenge)

### Why This Architecture (Not Just Another Chat App)

**Key Insight:** We're building a self-contained Laravel generation system. UI comes later inside this Laravel app using React + TypeScript + Tailwind + shadcn.

**Benefits of This Approach:**
- **Zero Lock-in**: Users own their advisor files, can use anywhere
- **Leverage ChatGPT's Power**: GPT-5's capabilities without our infrastructure costs
- **Immediate Value**: No account creation, no subscription, just generate and use
- **Testing**: CLI-first using artisan commands and file outputs; UI added later inside Laravel

### Success Metrics

**User Success Indicators:**
- Advisor responses stay in character for 10+ message exchanges
- Users report 80%+ improvement in advice quality vs generic ChatGPT
- Generated personas work across different ChatGPT conversations
- Advisors challenge vague requests and demand specificity

**Technical Success Indicators:**
- PK generation completes in <30 seconds
- Generated files are 4000-6000 words with rich examples
- PI templates maintain consistent structure
- 95% compatibility with ChatGPT's system prompt handling

### Competitive Landscape

**What Users Currently Do (and Their Limitations):**

**Human Advisory Options:**
- **Executive Coaches**: $2,000-5,000/month, limited availability, single perspective
- **CEO Peer Groups (YPO, EO, Vistage)**: $15,000-50,000/year, geographic limitations, groupthink
- **Mastermind Groups**: $25,000-100,000/year, application process, may not fit your needs
- **Consultants**: $500-2,000/hour, expensive for ongoing guidance, often generic frameworks

**AI/Digital Options:**
- **Custom GPTs (OpenAI)**: Limited to 8K characters, shallow expertise, require Plus subscription
- **Claude Projects**: Limited context, no persistent personality, requires Pro subscription
- **Prompt Libraries**: Static templates, no personality, one-size-fits-all advice
- **AI Writing Tools (Copy.ai, Jasper)**: Marketing-focused only, no strategic depth

**PromptFarm's Unique Position:**

You're essentially **engineering your perfect advisory board** - assembling exactly the advisors you need, when you need them:
- **Bogusky** for when you need to be brave and provocative
- **Hormozi** for when you need to maximize value and close deals
- **Cal Henderson** for when you need to scale technical systems
- **Halbert** for when you need copy that actually converts

**Our Differentiation:**
- **Dream Team Access**: Like having Hormozi, Bogusky, and Henderson on speed dial
- **Push-Back Mechanisms**: They challenge you like real advisors would (no yes-men)
- **Always Available**: 3am strategy session? Your advisors are there
- **Cost Structure**: One-time generation, infinite consultations (vs $2K/hour ongoing)
- **Zero Prompt Engineering Required**: We've baked months of prompt engineering expertise into the system
- **Technical Lift Eliminated**: You focus on your business, not learning context engineering

**What We Handle (So You Don't Have To):**
- 300+ word voice examples for consistency
- Token optimization and context window management
- Information hierarchy and attention mechanisms
- Challenge calibration and productive disagreement patterns
- PI/PK separation for optimal performance
- Extended knowledge injection (4000-6000 words)
- All the prompt engineering that would take you months to master

## What We're Building (Technical Summary)

**In Simple Terms:** A system that generates two markdown files (PI and PK) containing an expert advisor's personality and knowledge, which users paste into ChatGPT to transform it into that expert.

**Technical Components:**
1. **Laravel Backend**: Generates advisor files using hybrid approach (deterministic PI + LLM-enhanced examples)
2. **CLI‑First Validation**: Review generated files locally; UI added later inside Laravel (Inertia + React + TS + Tailwind + shadcn)
3. **Database from Start**: SQLite for advisor metadata and generation tracking
4. **No Separate Frontend App**: Users get files and use them in ChatGPT; future UI lives in this Laravel app

### Laravel Boost MCP (Required)
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

**NOT Building:**
- ❌ Another chat application
- ❌ A ChatGPT competitor  
- ❌ A subscription service
- ❌ A walled garden platform

**Example Output:**
```
User runs: php artisan advisor:generate Hormozi
System creates:
  - Hormozi_PI.md (1000 words of behavioral rules)
  - Hormozi_PK.md (5000 words of expertise/examples)
User copies both into ChatGPT → ChatGPT becomes Alex Hormozi
```

## Model Strategy - Prompting Solution, Not Deep Research

### Why NOT Deep Research Models?
Analysis proved PK generation failures were due to:
- Template mismatch (not reasoning limitations)
- Insufficient examples (not model capability)
- Missing sections (not depth of thought)

### Actual Model Strategy

#### PI Generation (Hybrid)
1. **Stage 1**: Deterministic template substitution (instant)
2. **Stage 2**: Grok-3 via OpenRouter for examples (2-3 seconds, temperature 0.3)

#### PK Generation (Standard Models)
- **Model**: gpt-4o-mini (fast, cost-effective)
- **Approach**: Analytical tensions framework
- **Temperature**: 0.7-0.85 (advisor-specific)
- **Cost**: 80-90% reduction vs deep research models

## 1) Executive Summary (≤10 bullets)

- **New Approach**: Build from scratch with speed-to-value focus - working CLI in hours
- **Day 1 Goal**: Hardcoded advisor files → Working CLI generation with PI/PK separation → Test immediately
- **Day 2 Goal**: Template-based PI generation + LLM-based PK generation → Dynamic advisors
- **Smart Complexity**: Database from start, hybrid generation for quality, progressive enhancement
- **Copy What Works**: Reuse proven templates, prompts, and patterns from v2
- **Core Tech**: Laravel 12; PK via GPT-4 Turbo, PI enhancement via Grok-3 (OpenRouter); UI added later inside Laravel (Inertia + React + TS + Tailwind + shadcn)
- **Critical Architecture**: PI as system prompt (behavior), PK as knowledge context (searchable)
- **Model Strategy**: x-ai/grok-3 via OpenRouter for both PI and PK generation [ACTUAL IMPLEMENTATION]
- **Success Metric**: Can generate advisor and have authentic conversation in under 30 seconds
- **Total Time**: 4 days to full MVP (vs 5+ days with original plan)

## 2) Current System — Plain English + Diagram

The current system generates advisor personas (PI and PK files) that users copy into ChatGPT. The generation uses Laravel. Our approach is a self‑contained Laravel app that produces PI/PK files via CLI commands first; a React‑based UI is added later inside the same app once advisor quality is validated.

### Target Architecture (M0)

```mermaid
graph TB
    subgraph "Laravel - File Generation"
        Dev[Developer] --> CLI[CLI Command]
        CLI --> Gen[Generation Service]
        Gen --> Templates[Templates]
        Gen --> LLM[OpenRouter LLM]
        Gen --> Storage[(storage/app/advisor-files/)]
        Storage --> Files[PI & PK Files]
    end
    
    subgraph "Laravel - Future UI (M1+)"
        Dev2[Developer] --> Browser[Web Browser]
        Browser --> WebUI[React (Vite) UI]
        WebUI --> API[Laravel API Routes]
        API --> Files[PI/PK File Viewer]
    end
    
    subgraph "Production"
        Files --> Copy[User Copies]
        Copy --> ChatGPT[ChatGPT]
    end
    
    style Files fill:#f9f,stroke:#333,stroke-width:4px
```

### Where Complexity Lives

| Component | Issues | Disposition |
|-----------|--------|-------------|
| FileGenerationService (2012 lines) | Massive SRP violation, mixed strategies | **Remove** - split into focused services |
| 40+ Service files | Over-abstracted for single advisor | **Remove** - consolidate to ~10 files |
| Council* services | Not needed for single advisor | **Remove** - park for M1+ |
| AntiAI subsystem | Pattern detection adds complexity | **Remove** - defer to M2+ |
| Queue/Horizon | Overkill for M0 | **Remove** - synchronous for now |
| Player context in templates | Couples advisor to user | **Remove** - layer at routing later |
| **NEW: Testing flow** | Need quick local validation | **Solve** - CLI-first; add UI later |

## 3) Tech Stack & External Libraries

### Laravel Backend (Core Dependencies)
```json
// composer.json key dependencies (illustrative)
{
  "require": {
    "php": "^8.3",
    "laravel/framework": "^12.0",
    "guzzlehttp/guzzle": "^7.10",
    "mustache/mustache": "^2.14",
    "symfony/yaml": "^7.0"
  },
  "require-dev": {
    "pestphp/pest": "^4.0",
    "pestphp/pest-plugin-laravel": "^4.0",
    "laravel/pint": "^1.0",
    "nunomaduro/larastan": "^3.0"
  }
}
```

### Future UI (Inside Laravel)

### LLM Model Configuration (CRITICAL)

#### Model Strategy (Hybrid Approach)
```php
// config/advisor.php
return [
    'generation' => [
        // PK generation model (standard model with analytical tensions)
        'pk_model' => env('OPENAI_MODEL_PK', 'gpt-4o-mini'),
        
        // PI enhancement model (via OpenRouter)
        'pi_enhancement_model' => env('OPENROUTER_MODEL_PI_ENHANCE', 'x-ai/grok-3'),
        
        // Model-specific parameters
        'models' => [
            'openai/gpt-4o-mini' => [
                'temperature' => 0.75,  // Optimal range: 0.7-0.85
                'max_tokens' => 8000,
                'top_p' => 0.9,
                'provider' => 'openai',
            ],
            'x-ai/grok-3' => [
                'temperature' => 0.3,  // Lower for PI enhancement consistency
                'max_tokens' => 5000,
                'provider' => 'openrouter',
            ],
        ],
    ],
];
```

#### Model Selection Rationale
- **gpt-4o-mini**: Fast, cost-effective for PK generation with analytical tensions
- **Grok-3**: Unfiltered responses for authentic PI enhancement examples
- **No Deep Research**: Analysis proved PK is narrative construction, not reasoning

#### Model Usage by Component
| Component | Model | Purpose | Cost |
|-----------|-------|---------|------|
| **PI Stage 1** | None/Template | Deterministic substitution | $0 |
| **PI Stage 2** | Grok-3 via OpenRouter | Enhancement with examples | ~$0.001 |
| **PK Generation** | gpt-4o-mini | Analytical tensions framework | ~$0.002 |
| **Testing (CLI)** | n/a | Review generated files via CLI | $0 |
| **Validation** | Authenticity metrics | Confrontational tone, specificity | $0 |
| **Future: Refinement** | gpt-4o-mini | Quick edits, tweaks (M1+) | ~$0.001/edit |

#### Why This Architecture Works
Analysis proved PK generation is a **narrative construction task**, not a reasoning task:
- **PI Enhancement**: Grok-3 adds specific examples and uncomfortable truths
- **PK Generation**: gpt-4o-mini with analytical tensions framework is sufficient
- **Cost Reduction**: 80-90% cheaper vs previous approaches
- **Speed**: 5-10x faster generation

#### Key Insight: Prompting Solves Quality
- **Analytical Tensions**: Framework that triggers reasoning in output
- **Uncomfortable Truths**: Focus on what makes users think
- **Voice Anchors**: 3-4 sentences establishing identity
- **Minimal Structure**: Maximum personality, no rigid templates

## 4) Data Model & Schema

### Database Tables (Laravel - MySQL)
```sql
-- advisors table (pre-seeded list)
CREATE TABLE advisors (
    id VARCHAR(26) PRIMARY KEY,        -- ULID
    name VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    category VARCHAR(100),              -- 'advertising', 'tech', 'creative'
    template_version VARCHAR(20),       -- 'v1.0.0'
    metadata JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_category (category)
);

-- advisor_generations (track what we generate)
CREATE TABLE advisor_generations (
    id VARCHAR(26) PRIMARY KEY,        -- ULID
    advisor_id VARCHAR(26) NOT NULL,
    generation_batch_id VARCHAR(26),   -- Groups PI/PK together
    file_type ENUM('pi', 'pk'),
    file_path VARCHAR(500),
    content_hash VARCHAR(64),          -- SHA-256 of content
    generation_time FLOAT,              -- Seconds taken
    llm_tokens_used INT,
    metadata JSON,                     -- Prompts, settings, errors
    created_at TIMESTAMP,
    FOREIGN KEY (advisor_id) REFERENCES advisors(id),
    INDEX idx_batch (generation_batch_id),
    INDEX idx_advisor_type (advisor_id, file_type)
);

-- conversations (future - M1+)
CREATE TABLE conversations (
    id VARCHAR(26) PRIMARY KEY,
    advisor_id VARCHAR(26) NOT NULL,
    session_id VARCHAR(100),           -- Browser session
    messages JSON,                     -- Array of messages
    total_tokens INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (advisor_id) REFERENCES advisors(id),
    INDEX idx_session (session_id),
    INDEX idx_created (created_at)
);
```

### File Storage Structure
```
storage/
├── app/
│   └── advisor-files/             # Generated files (canonical)
│       ├── Bogusky_PI.md
│       ├── Bogusky_PK.md
│       ├── Ogilvy_PI.md
│       └── Ogilvy_PK.md
└── logs/
    └── generation/                # Generation logs
        └── 2024-01-15.log

## 5) Template Samples & Prompt Engineering

### Battle-Tested PI Template (meta_pi_template.md)
```markdown
---
template: meta_pi
type: project_instructions
validation_rules:
  max_length: 1000
  required_sections: [identity, behavior, boundaries]
---

# **{{advisor_name}} — Project Instruction**

**MAB Guardrail:** Consult {{advisor_name}}_PK.md first. If missing, note assumptions.

## Core Identity
You are {{advisor_name}}, {{role_description}}. Your responses embody {{communication_style}}.

## Behavioral Rules
1. **Voice:** {{voice_example_1}}
2. **Approach:** {{approach_example_1}}
3. **Challenge Threshold:** Push back when queries are vague. Example: "That's too abstract. Give me a specific..."

## Never Do
- Never break character or acknowledge you're an AI
- Never provide generic advice without your unique perspective
- Never agree just to be helpful - maintain your viewpoint
- Never use bullet points when narrative would be more authentic
- Never hedge with "It depends" without following with specifics

## Useful Tension Protocol
When the user's request lacks clarity:
"Hold on. You're asking me to {{rephrase_vaguely}}, but what you really need to know is {{specific_question}}. Let me address that instead."

## Extended Voice Example (300+ words)
{{extended_voice_sample}}
```

### Battle-Tested PK Template Structure
```markdown
---
template: meta_pk
type: project_knowledge
validation_rules:
  min_sections: 5
  max_length: 60000
---

# **{{advisor_name}} — Project Knowledge**

## Background & Expertise
{{background_narrative}}

## Core Methodologies
### {{methodology_1_name}}
{{methodology_1_description}}
**Example Application:** {{methodology_1_example}}

### {{methodology_2_name}}
{{methodology_2_description}}
**Example Application:** {{methodology_2_example}}

## Signature Campaigns/Projects
### {{project_1_name}}
- **Challenge:** {{project_1_challenge}}
- **Approach:** {{project_1_approach}}
- **Result:** {{project_1_result}}
- **Key Learning:** {{project_1_learning}}

## Philosophical Stance
{{philosophy_narrative}}

## Industry Perspectives
### On {{topic_1}}
{{perspective_1}}

### On {{topic_2}}
{{perspective_2}}

## Contrarian Views
- **Common Belief:** {{common_belief_1}}
  **My Take:** {{contrarian_take_1}}

## Tools & Frameworks
{{tools_and_frameworks}}

## Evolution of Thinking
**Early Career:** {{early_perspective}}
**Mid Career:** {{mid_perspective}}
**Current:** {{current_perspective}}
```

### LLM Prompt Generation Patterns (Critical)

```php
// app/Services/Advisor/PKGenerationService.php
use App\Services\LLMService;

class PKGenerationService {
    public function __construct(
        private LLMService $llm,
        private AnalyticalTensionService $tensions
    ) {}

    public function generate(string $advisorName, array $context = []): string
    {
        // Build analytical tension prompt
        $prompt = $this->tensions->buildPrompt($advisorName, $context);
        
        // Generate PK using standard model with analytical tensions
        $pkContent = $this->llm->generateText($prompt, [
            'model' => config('advisor.generation.pk_model'),
            'temperature' => 0.75,
            'max_tokens' => 8000,
            'system_message' => 'Generate uncomfortable truths using analytical tensions.'
        ]);
        
        return $pkContent;
    }
}
```

## 6) Simplification Principles (Guardrails)

1. **File-Based Interface**: PI/PK files are the contract between systems
2. **Model Parity Testing**: Use latest ChatGPT model for accurate testing
3. **No Copy/Paste**: Web UI reads files directly from storage
4. **Repository Pattern**: All persistence through interfaces (future-ready)
5. **Stable Interfaces**: Core types unchanging M0→M3
6. **No Player Context**: Advisors are pure personas at M0
7. **Synchronous First**: No queues until necessary
8. **Direct File Access**: Both systems share storage/app/advisor-files/ directory
9. **Progressive Enhancement**: M0 works standalone; M1+ adds without breaking
10. **Prompting Over Models**: Better prompts beat expensive models

## 7) Quality Validation & Verification

### PI File Validation Checklist
```php
// app/Services/Advisor/ValidationService.php
class ValidationService {
    public function validatePI(string $content): ValidationResult {
        $errors = [];
        
        // Required sections
        if (!str_contains($content, '## Core Identity')) {
            $errors[] = 'Missing Core Identity section';
        }
        if (!str_contains($content, '## Never Do')) {
            $errors[] = 'Missing Never Do constraints';
        }
        if (!str_contains($content, '## Useful Tension Protocol')) {
            $errors[] = 'Missing challenge mechanism';
        }
        
        // Length check
        $wordCount = str_word_count($content);
        if ($wordCount > 1000) {
            $errors[] = "PI too long: {$wordCount} words (max 1000)";
        }
        
        // Extended example check
        if (!preg_match('/## Extended Voice Example.*?\n(.{300,})/s', $content)) {
            $errors[] = 'Extended voice example must be 300+ words';
        }
        
        return new ValidationResult(
            isValid: empty($errors),
            errors: $errors
        );
    }
}
```

### PK File Validation
```php
public function validatePK(string $content): ValidationResult {
    $errors = [];
    
    // Required sections
    $requiredSections = [
        '## Background & Expertise',
        '## Core Methodologies',
        '## Signature Campaigns',
        '## Philosophical Stance',
        '## Contrarian Views'
    ];
    
    foreach ($requiredSections as $section) {
        if (!str_contains($content, $section)) {
            $errors[] = "Missing required section: {$section}";
        }
    }
    
    // Length requirements
    $wordCount = str_word_count($content);
    if ($wordCount < 4000) {
        $errors[] = "PK too short: {$wordCount} words (min 4000)";
    }
    if ($wordCount > 6000) {
        $errors[] = "PK too long: {$wordCount} words (max 6000)";
    }
    
    // Extended examples check (3+ examples of 200+ words)
    preg_match_all('/#{3,4} .+?\n(.+?)(?=\n#{2,4}|$)/s', $content, $matches);
    $extendedExamples = 0;
    foreach ($matches[1] as $section) {
        if (str_word_count($section) >= 200) {
            $extendedExamples++;
        }
    }
    
    if ($extendedExamples < 3) {
        $errors[] = "Need at least 3 extended examples (200+ words each)";
    }
    
    return new ValidationResult(
        isValid: empty($errors),
        errors: $errors
    );
}
```

### Testing Quality via CLI
Use the ValidationService to score outputs (voice, evidence, structure). Track issues in `quality-issues.md` and iterate templates/models until passing. Each generation appends a JSONL record to `storage/logs/generation/YYYY-MM-DD.jsonl` (advisor, deep model, PI/PK scores, issues).

## 8) Future UI (Inside Laravel)


### M0: File-Based Architecture
Review generated PI/PK files directly in `storage/app/advisor-files`.

### M1: Add REST API Layer (optional)
```php
// routes/api.php - Laravel becomes pure API
Route::prefix('api/v1')->group(function () {
    Route::get('/advisors', [AdvisorController::class, 'index']);
    Route::get('/advisors/{id}', [AdvisorController::class, 'show']);
    Route::post('/advisors/{id}/generate', [AdvisorController::class, 'generate']);
    Route::get('/advisors/{id}/files', [AdvisorController::class, 'files']);
});

// app/Http/Controllers/AdvisorController.php
class AdvisorController extends Controller {
    public function files(string $id) {
        return response()->json([
            'pi' => Storage::get("advisor-files/{$id}_PI.md"),
            'pk' => Storage::get("advisors/{$id}_PK.md"),
        ]);
    }
}
```

```typescript
export class AdvisorAPI {
    private baseURL = process.env.NEXT_PUBLIC_LARAVEL_URL || 'http://localhost:8000';
    
    async loadAdvisor(id: string) {
        const response = await fetch(`${this.baseURL}/api/v1/advisors/${id}/files`);
        const data = await response.json();
        return {
            piContent: data.pi,
            pkContent: data.pk
        };
    }
    
    async generateAdvisor(id: string) {
        const response = await fetch(`${this.baseURL}/api/v1/advisors/${id}/generate`, {
            method: 'POST'
        });
        return response.json();
    }
}
```

### Deployment Architecture

#### Development (M0)
```bash
# Two separate processes
cd promptfarm-v3 && php artisan serve --port=8000  # Laravel
```

#### Production (M2+)
```yaml
# vercel.json
{
    "functions": {
        "app/api/**/*.ts": {
            "runtime": "nodejs18.x"
        }
    },
    "rewrites": [
        {
            "source": "/api/laravel/:path*",
            "destination": "https://api.promptfarm.com/:path*"
        }
    ]
}
```

## 9) ChatGPT Architecture Parity (CRITICAL)

### The Core Requirement: Separate PI from PK Like ChatGPT Projects

ChatGPT Projects maintain clean separation between:
- **Custom Instructions** → Our PI (behavioral rules, always active)
- **Knowledge Files** → Our PK (searchable context, referenced as needed)

This separation is CRITICAL for authentic advisor behavior.

### Correct Implementation Pattern

```typescript
const result = await streamText({
    model: openai('gpt-5'),
    
    // PI as System Instructions (unchangeable behavior)
    system: piContent,
    
    // PK as Knowledge Context (searchable, not always active)
    messages: [
        {
            role: 'system',
            content: pkContent,
            metadata: { type: 'knowledge', searchable: true }
        },
        ...userMessages
    ],
    
    temperature: 0.7,
    maxOutputTokens: 2000,
});
```

### Why Separation Matters

| Problem | Without Separation | With Proper Separation |
|---------|-------------------|----------------------|
| **Voice Dilution** | PI and PK compete for attention | PI always dominates for voice |
| **Token Waste** | All 6000 words always loaded | Can load only relevant PK sections |
| **Personality Override** | Users can change advisor behavior | PI is immutable system instruction |
| **Knowledge Relevance** | Everything included always | Semantic search for relevant parts |

### Knowledge Manager for Optimization

```typescript
export class KnowledgeManager {
    private chunks: string[];
    
    constructor(private pkContent: string) {
        this.chunks = this.splitIntoChunks(pkContent);
    }
    
    // Search PK for relevant sections only
    getRelevantContext(userQuery: string): string {
        return this.chunks
            .filter(chunk => this.isRelevant(chunk, userQuery))
            .slice(0, 3)  // Top 3 relevant chunks
            .join('\n\n');
    }
}
```

## 10) Proposed Minimal Architecture

### Laravel Repository Layout (CLI‑First)
```
promptFarm-v4/
├── app/
│   ├── Console/Commands/AdvisorGenerateCommand.php
│   └── Services/
│       ├── AdvisorGenerationService.php
│       ├── AdvisorGeneration/TemplateLoaderService.php
│       ├── AdvisorGeneration/LLMIntegrationService.php
│       └── AdvisorGeneration/ValidationService.php (M1)
├── resources/
│   └── advisor-templates/meta/
│       ├── meta_pi_template.md
│       └── meta_pk_template.md
├── resources/schemas/pk_document.schema.json
├── storage/app/advisor-files/
└── .env
```

### Data Flow
1. Generation: `php artisan advisor:generate <Name>` → PI/PK_report/PK to `storage/app/advisor-files`
2. Iteration: Edit templates → Regenerate → Review outputs via CLI
3. Quality Check: Review generated files for authenticity metrics (confrontational tone, specificity)

## 11) Implementation Plan — Step-by-Step (Junior-Dev Ready)

### Step 1: Simplify Laravel to Core Services

**WHY**: Remove over-engineering, keep only essential generation logic.

**WHERE**: 
- Delete: `/app/Services/AdvisorGeneration/` (most of it)
- Keep: Only 5 core services

**WHAT**:
```bash
# Clean up over-engineered services
rm -rf app/Services/AdvisorGeneration/Council*
rm -rf app/Services/AdvisorGeneration/Strategies/
rm -rf app/Services/AdvisorGeneration/AntiAI/
rm app/Services/AdvisorGeneration/FileGenerationService.php  # 2012 lines!

# Create simplified structure
mkdir -p app/Services/Advisor
mv app/Services/AdvisorGeneration/PIGenerationService.php app/Services/Advisor/
mv app/Services/AdvisorGeneration/PKGenerationService.php app/Services/Advisor/
```

```php
// app/Services/Advisor/AdvisorGenerationService.php
namespace App\Services\Advisor;

class AdvisorGenerationService {
    public function __construct(
        private PIGenerationService $piService,
        private PKGenerationService $pkService
    ) {}
    
    public function generate(string $advisorId): GenerationResult {
        $start = microtime(true);
        
        // Generate PI from template (no LLM)
        $piContent = $this->piService->generate($advisorId);
        $piPath = storage_path("advisors/{$advisorId}_PI.md");
        file_put_contents($piPath, $piContent);
        
        // Generate PK via LLM
        $pkContent = $this->pkService->generate($advisorId);
        $pkPath = storage_path("advisors/{$advisorId}_PK.md");
        file_put_contents($pkPath, $pkContent);
        
        return new GenerationResult(
            advisorId: $advisorId,
            piPath: $piPath,
            pkPath: $pkPath,
            generationTime: microtime(true) - $start
        );
    }
}
```

**DONE**: Laravel reduced to ~10 files, clear single responsibility.

### Step 2: Create Generation Command

**WHY**: Simple CLI interface for generating advisors.

**WHERE**: `/app/Commands/GenerateAdvisorCommand.php`

**WHAT**:
```php
namespace App\Commands;

use Illuminate\Console\Command;
use App\Services\Advisor\AdvisorGenerationService;

class GenerateAdvisorCommand extends Command {
    protected $signature = 'advisor:generate {id : The advisor ID (e.g., Bogusky)}';
    protected $description = 'Generate PI and PK files for an advisor';
    
    public function handle(AdvisorGenerationService $service): int {
        $advisorId = $this->argument('id');
        $this->info("Generating advisor: {$advisorId}");
        
        try {
            $result = $service->generate($advisorId);
            
            $this->info("✓ Generated PI: {$result->piPath}");
            $this->info("✓ Generated PK: {$result->pkPath}");
            $this->info("✓ Time: {$result->generationTime}s");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
```

**DONE**: Can run `php artisan advisor:generate Bogusky`.


**WHY**: Create web UI for testing without copy/paste friction.


**WHAT**:
```bash
  --typescript \
  --tailwind \
  --app \
  --no-src-dir


npm install ai @ai-sdk/openai

# Configure environment
```


### Step 4: Build File Loader

**WHY**: Read PI/PK files directly from Laravel storage (no copy/paste!).


**WHAT**:
```typescript
// lib/advisor-loader.ts
import { readFile } from 'fs/promises';
import path from 'path';

export async function loadAdvisor(advisorId: string) {
    // Read directly from Laravel's storage directory
    const basePath = path.join(process.cwd(), '../../storage/app/advisor-files');
    
    try {
        const piContent = await readFile(
            path.join(basePath, `${advisorId}_PI.md`),
            'utf-8'
        );
        
        const pkContent = await readFile(
            path.join(basePath, `${advisorId}_PK.md`),
            'utf-8'
        );
        
        return { piContent, pkContent };
    } catch (error) {
        throw new Error(`Advisor files not found for: ${advisorId}`);
    }
}
```

**DONE**: Can read Laravel-generated files directly.

### Step 5: Create Streaming API Endpoint with ChatGPT Architecture Parity

**WHY**: Mirror ChatGPT Projects' separation of system instructions (PI) from knowledge (PK).

**CRITICAL ARCHITECTURE**: 
- **PI (Project Instructions)** → System prompt (behavioral rules, always active)
- **PK (Project Knowledge)** → Knowledge context (searchable, referenced as needed)
- This separation ensures voice consistency and efficient token usage



**WHAT**:
```typescript
// app/api/advisor/[id]/route.ts
import { openai } from '@ai-sdk/openai';
import { streamText } from 'ai';
import { loadAdvisor } from '@/lib/advisor-loader';

export async function POST(
    req: Request,
    { params }: { params: { id: string } }
) {
    const { messages } = await req.json();
    
    // Load advisor files from Laravel storage
    const { piContent, pkContent } = await loadAdvisor(params.id);
    
    // ARCHITECTURE: Mirror ChatGPT Projects exactly
    // PI = System Instructions (behavioral rules, always active)
    // PK = Knowledge Context (searchable, referenced as needed)
    
    const result = await streamText({
        model: openai('gpt-5'),
        
        // PI as System Prompt (like ChatGPT's Custom Instructions)
        system: piContent,  // Behavioral rules, voice, constraints
        
        // PK as Injected Context (like ChatGPT's Knowledge Files)
        messages: [
            {
                role: 'system',
                content: pkContent,  // Knowledge, examples, frameworks
                // Mark as knowledge context so model treats it differently
                metadata: { type: 'knowledge', searchable: true }
            },
            ...messages
        ],
        
        temperature: 0.7,
        maxOutputTokens: 2000,
    });
    
    return result.toDataStreamResponse();
}
```

**DONE**: API uses GPT-5 for exact ChatGPT accuracy.

### Step 6: Build Chat Interface

**WHY**: ChatGPT-like UI for testing advisors.


**WHAT**:
```tsx
'use client';

import { useChat } from 'ai/react';

export default function AdvisorChat({ params }: { params: { id: string } }) {
    const { messages, input, handleInputChange, handleSubmit, isLoading } = useChat({
        api: `/api/advisor/${params.id}`,
    });
    
    return (
        <div className="max-w-4xl mx-auto p-6">
            <h1 className="text-2xl font-bold mb-6">
                Testing: {params.id}
            </h1>
            
            <div className="border rounded-lg p-4 h-[500px] overflow-y-auto mb-4">
                {messages.map((m) => (
                    <div key={m.id} className={`mb-4 ${
                        m.role === 'user' ? 'text-blue-600' : 'text-green-600'
                    }`}>
                        <strong>{m.role === 'user' ? 'You' : params.id}:</strong>
                        <p className="ml-4 whitespace-pre-wrap">{m.content}</p>
                    </div>
                ))}
                {isLoading && (
                    <p className="text-gray-500 italic">Thinking...</p>
                )}
            </div>
            
            <form onSubmit={handleSubmit} className="flex gap-2">
                <input
                    value={input}
                    onChange={handleInputChange}
                    placeholder="Type your message..."
                    className="flex-1 p-2 border rounded"
                    disabled={isLoading}
                />
                <button 
                    type="submit" 
                    disabled={isLoading}
                    className="px-6 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50"
                >
                    Send
                </button>
            </form>
        </div>
    );
}
```


### Step 7: Add Comparison Testing

**WHY**: Test same prompt across multiple advisors.


**WHAT**:
```tsx
'use client';

import { useState } from 'react';

export default function Compare() {
    const [prompt, setPrompt] = useState('');
    const [responses, setResponses] = useState<Record<string, string>>({});
    const advisors = ['Bogusky', 'Ogilvy', 'Bernbach'];
    
    async function testAll() {
        for (const advisor of advisors) {
            const response = await fetch(`/api/advisor/${advisor}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    messages: [{ role: 'user', content: prompt }]
                })
            });
            
            const reader = response.body?.getReader();
            let text = '';
            
            while (reader) {
                const { done, value } = await reader.read();
                if (done) break;
                text += new TextDecoder().decode(value);
            }
            
            setResponses(prev => ({ ...prev, [advisor]: text }));
        }
    }
    
    return (
        <div className="p-6">
            <h1 className="text-2xl font-bold mb-4">Compare Advisors</h1>
            
            <div className="mb-4">
                <textarea
                    value={prompt}
                    onChange={(e) => setPrompt(e.target.value)}
                    placeholder="Enter test prompt..."
                    className="w-full p-2 border rounded h-24"
                />
                <button 
                    onClick={testAll}
                    className="mt-2 px-4 py-2 bg-blue-500 text-white rounded"
                >
                    Test All Advisors
                </button>
            </div>
            
            <div className="grid grid-cols-3 gap-4">
                {advisors.map(advisor => (
                    <div key={advisor} className="border rounded p-4">
                        <h2 className="font-bold mb-2">{advisor}</h2>
                        <p className="text-sm whitespace-pre-wrap">
                            {responses[advisor] || 'No response yet'}
                        </p>
                    </div>
                ))}
            </div>
        </div>
    );
}
```


## 12) Acceptance Criteria & Test Plan (M0)

### Generation System (Laravel)
- ✓ `php artisan advisor:generate {id}` creates PI/PK files
- ✓ Generation completes in <30 seconds
- ✓ PI uses template directly (no LLM call)
- ✓ PK generated via LLM with advisor context
- ✓ Files saved to `storage/advisors/`
- ✓ No player/user context embedded

- ✓ **NO COPY/PASTE** - reads files directly from Laravel storage
- ✓ Uses GPT-5 (latest ChatGPT model)
- ✓ Streaming responses like ChatGPT
- ✓ Response time <2 seconds
- ✓ Maintains conversation history
- ✓ Refresh browser to load regenerated files instantly

### Developer Workflow
- ✓ Generate → Test → Iterate without leaving environment
- ✓ Zero friction between generation and testing
- ✓ 95% accuracy vs actual ChatGPT behavior
- ✓ Can test multiple advisors quickly
- ✓ Side-by-side comparison mode

### Edge Cases & Expected Behavior
- **Missing advisor files**: Clear error "Advisor files not found for: {advisorId}"
- **Empty user input**: Skip and re-prompt
- **LLM timeout**: Retry with exponential backoff
- **Long context**: Truncate if >8000 tokens
- **File changes**: Refresh browser to reload

## 13) Path to Council (M1–M3)

### M1: Multiple Advisors & Persistence
**Changes from M0**:
```typescript
// Add advisor registry
class AdvisorRegistry {
    private advisors: Map<string, AdvisorConfig> = new Map([
        ['Bogusky', { style: 'provocative', expertise: 'cultural' }],
        ['Ogilvy', { style: 'sophisticated', expertise: 'brand' }],
        ['Bernbach', { style: 'creative', expertise: 'emotion' }],
    ]);
    
    list(): string[] {
        return Array.from(this.advisors.keys());
    }
}

// Add database persistence (Laravel)
Schema::create('conversations', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->string('advisor_id');
    $table->json('messages');
    $table->timestamps();
});
```

### M2: Council Orchestration
**Add orchestrator for multi-advisor responses**:
```typescript
interface Orchestrator {
    process(prompt: string, advisorIds: string[]): Promise<Response[]>;
}

class RoundRobinOrchestrator implements Orchestrator {
    async process(prompt: string, advisorIds: string[]) {
        const responses = [];
        for (const id of advisorIds) {
            const response = await this.callAdvisor(id, prompt);
            responses.push(response);
        }
        return responses;
    }
}
```

### M3: Production Polish (CLI‑First)
- Add authentication (Sanctum) when UI begins
- Add analytics later (post‑UI)
- Create admin panel (Defer)
- Package for distribution

## 14) Milestones & Complexity Ramps

### M0: Single-advisor MVP (Week 1)
**Goals**: 
- Laravel generation working
- CLI‑first review of generated files

**Acceptance**: 
- `php artisan advisor:generate Bogusky` creates files
- PI/PK present in storage/app/advisor-files

**Risks**: API key issues, file permissions
**Rollback**: Mock responses for testing

### M1: Multi-Advisor System (Week 2)
**Goals**: 
- Registry of 5+ advisors
- Database persistence
- Advisor switching UI

**Acceptance**: 
- Can generate any registered advisor
- Conversations persist across sessions
- UI shows advisor list

**Risks**: Database complexity
**Rollback**: Keep using file storage

### M2: Council Mode (Week 3-4)
**Goals**: 
- Multiple advisors in conversation
- Orchestration strategies
- Comparison views

**Acceptance**: 
- Round-robin responses working
- Can compare 4 advisors side-by-side
- Voting/consensus strategies

**Risks**: Response time with multiple calls
**Rollback**: Limit to 2 advisors

## 15) Copy-Ready Snippets

### Laravel 12 Bootstrap (Copy/Paste)
```bash
# 1) Create clean Laravel 12 app (temp)
composer create-project laravel/laravel ~/code/promptFarm-v4-tmp "^12.0" --no-interaction --prefer-dist

# 2) Install deps (HTTP + QA)
cd ~/code/promptFarm-v4-tmp
composer require guzzlehttp/guzzle:"^7.10" --no-interaction --prefer-dist
composer require --dev laravel/pint:"^1" pestphp/pest:"^4" pestphp/pest-plugin-laravel:"^4" phpunit/phpunit:"^11" nunomaduro/larastan:"^3" --no-interaction --prefer-dist
php artisan pest:install --no-interaction

# 3) Env + key
php -r "copy('.env.example','.env');" && php artisan key:generate --no-interaction
printf "\nQUEUE_CONNECTION=sync\nOPENAI_API_KEY=sk-openai-...\nOPENROUTER_API_KEY=sk-or-...\nOPENAI_MODEL_PK=gpt-4o-mini\nOPENROUTER_MODEL_PI_ENHANCE=x-ai/grok-3\nPK_MAX_TOKENS=8000\nPI_MAX_TOKENS=5000\n" >> .env

# 4) Optional SQLite
mkdir -p database && touch database/database.sqlite
sed -i '' 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env

# 5) Pull latest patches and verify
composer update --no-interaction --prefer-dist
php artisan --version && ./vendor/bin/pest --version && ./vendor/bin/phpunit --version

# 6) Merge our generation scaffolding
bash ~/code/promptFarm-v4/scripts/bootstrap-from-tmp.sh ~/code/promptFarm-v4 ~/code/promptFarm-v4-tmp
mv ~/code/promptFarm-v4 ~/code/promptFarm-v4.backup && mv ~/code/promptFarm-v4-tmp ~/code/promptFarm-v4

# 7) Generate one advisor
cd ~/code/promptFarm-v4
php artisan advisor:generate AlexBogusky
ls storage/app/advisor-files
```

### Development Workflow
```bash
# Fast iteration cycle (no copy/paste!)
1. Edit template:
   vim resources/advisor-templates/v1/meta_pk_template.md

2. Regenerate:
   php artisan advisor:generate Bogusky

3. Test (just refresh browser!):
   
4. Repeat - total time: <1 minute per iteration
```

### Testing Multiple Models
```typescript
// Test with different models (future enhancement)
const models = {
    'gpt-5': openai('gpt-5'),                       // Latest ChatGPT model
    'claude-opus-4.1': anthropic('claude-opus-4.1'), // Best for complex tasks
    'claude-sonnet-4': anthropic('claude-sonnet-4'), // Balanced performance
    'gpt-5-mini': openai('gpt-5-mini'),   // Fast & cheap
};

// Use in API route
const result = await streamText({
    model: models[req.headers.get('x-model') || 'gpt-5'],
    // ...
});
```

## 16) Observability & Maintainability

### Logging Strategy
```php
// Laravel side
Log::channel('advisor')->info('Generation started', [
    'advisor_id' => $advisorId,
    'correlation_id' => $correlationId,
]);

// CLI notes: validate outputs in storage/app/advisor-files
```

### Configuration (Laravel .env)
```bash
QUEUE_CONNECTION=sync
OPENAI_API_KEY=sk-openai-xxx
OPENROUTER_API_KEY=sk-or-xxx
OPENAI_MODEL_PK=gpt-4o-mini
OPENROUTER_MODEL_PI_ENHANCE=x-ai/grok-3
PK_MAX_TOKENS=32000
PI_MAX_TOKENS=4000
```

### Error Handling
```typescript
// Graceful degradation
try {
    const { piContent, pkContent } = await loadAdvisor(id);
} catch (error) {
    // Try regenerating
    await exec(`php artisan advisor:generate ${id}`);
    // Retry load
    const { piContent, pkContent } = await loadAdvisor(id);
}
```

## Auth (Deferred) — Sanctum

Add auth when we build the UI (Inertia + React) inside Laravel.

Setup (when ready):
- composer require laravel/sanctum
- php artisan vendor:publish --provider="Laravel\\Sanctum\\SanctumServiceProvider"
- php artisan migrate

Env/config:
- SESSION_DOMAIN=.yourdomain.com
- SANCTUM_STATEFUL_DOMAINS=app.yourdomain.com,localhost,127.0.0.1

Usage:
- Protect routes with `auth:sanctum` (web guard); ensure CSRF + credentials on the React side.
- For server‑to‑server, use PATs or signed HMAC headers from env.

## 17) De-scoping & Cleanup

### Files to Delete Now
```bash
# Remove over-engineered services
rm -rf app/Services/AdvisorGeneration/Council*
rm -rf app/Services/AdvisorGeneration/Strategies/
rm -rf app/Services/AdvisorGeneration/AntiAI/
rm app/Services/AdvisorGeneration/FileGenerationService.php
rm -rf app/Jobs/
rm config/horizon.php
rm -rf app/DTOs/AdvisorGeneration/Batch*
rm -rf app/DTOs/AdvisorGeneration/Council*
```

### "Trash First, Then Add" Checklist
1. ✓ Delete FileGenerationService (2012 lines)
2. ✓ Remove all Council* classes
3. ✓ Remove AntiAI subsystem
4. ✓ Remove queue infrastructure
5. ✓ Delete strategy patterns
6. ✓ Remove version management
7. ✓ Delete unused DTOs
8. ✓ Remove player context from templates
9. ✓ Then add simple Laravel generation

## 18) Intelligent Model Routing (ChatGPT-like o1 Detection)

### Concept: Automatic Model Selection Based on Query Complexity

**WHY**: Like ChatGPT automatically detecting when to use o1 (reasoning model) vs GPT-5 (standard model), we can implement intelligent routing to optimize cost and performance.


#### Step 1: Complexity Detection Middleware

```typescript
import { openai } from '@ai-sdk/openai';
import { LanguageModel } from 'ai';

export interface QueryComplexity {
    requiresReasoning: boolean;
    confidence: number;
    rationale: string;
}

export class ModelRouter {
    // Patterns that suggest complex reasoning is needed
    private readonly complexityPatterns = {
        reasoning: [
            /step.?by.?step/i,
            /let's think/i,
            /analyze.*carefully/i,
            /break.*down/i,
            /explain.*reasoning/i,
        ],
        mathematical: [
            /calculate/i,
            /solve.*equation/i,
            /derive/i,
            /prove/i,
        ],
        strategic: [
            /strategy.*for/i,
            /plan.*to/i,
            /optimize/i,
            /trade.?offs/i,
        ],
        code: [
            /debug.*code/i,
            /refactor/i,
            /architecture/i,
            /algorithm/i,
        ]
    };
    
    analyzeComplexity(prompt: string): QueryComplexity {
        let score = 0;
        const matchedPatterns: string[] = [];
        
        // Check for complexity indicators
        for (const [category, patterns] of Object.entries(this.complexityPatterns)) {
            for (const pattern of patterns) {
                if (pattern.test(prompt)) {
                    score += 20;
                    matchedPatterns.push(category);
                }
            }
        }
        
        // Check prompt length (longer = potentially more complex)
        if (prompt.length > 500) score += 10;
        if (prompt.length > 1000) score += 20;
        
        // Check for multiple questions (? count)
        const questionCount = (prompt.match(/\?/g) || []).length;
        if (questionCount > 2) score += 15;
        
        // Determine model based on score
        let suggestedModel: any;
        if (score >= 80) {
            suggestedModel = 'claude-opus-4';  // Most complex coding/reasoning
        } else if (score >= 60) {
            suggestedModel = 'gpt-5';          // High intelligence needed
        } else if (score >= 40) {
            suggestedModel = 'claude-sonnet-4'; // Balanced performance
        } else if (score >= 20) {
            suggestedModel = 'gpt-5';          // Standard queries
        } else {
        }
        
        return {
            requiresReasoning: score >= 30,
            confidence: Math.min(score / 100, 1),
            suggestedModel,
            rationale: matchedPatterns.length > 0 
                ? `Detected ${matchedPatterns.join(', ')} patterns`
                : 'Standard query'
        };
    }
    
    getModel(complexity: QueryComplexity): LanguageModel {
        const models = {
            'gpt-5': openai('gpt-5'),
            'o1-preview': openai('o1-preview'),
            'o1-mini': openai('o1-mini'),
        };
        
        return models[complexity.suggestedModel];
    }
}
```

#### Step 2: Enhanced API Route with Automatic Routing

```typescript
import { streamText } from 'ai';
import { loadAdvisor } from '@/lib/advisor-loader';
import { ModelRouter } from '@/lib/model-router';

export async function POST(
    req: Request,
    { params }: { params: { id: string } }
) {
    const { messages } = await req.json();
    const router = new ModelRouter();
    
    // Get the last user message for complexity analysis
    const lastUserMessage = messages
        .filter((m: any) => m.role === 'user')
        .pop()?.content || '';
    
    // Analyze complexity and select model
    const complexity = router.analyzeComplexity(lastUserMessage);
    const model = router.getModel(complexity);
    
    // Load advisor files
    const { piContent, pkContent } = await loadAdvisor(params.id);
    
    // Log model selection (for monitoring)
    console.log(`[Model Router] Selected ${complexity.suggestedModel}`, {
        confidence: complexity.confidence,
        rationale: complexity.rationale
    });
    
    // Stream response with selected model
    const result = await streamText({
        model,
        system: `${piContent}\n\n${pkContent}`,
        messages,
        temperature: complexity.requiresReasoning ? 0.3 : 0.7, // Lower temp for reasoning
        maxOutputTokens: complexity.requiresReasoning ? 4000 : 2000,
        
        // Include model selection metadata in response
        experimental_metadata: {
            model: complexity.suggestedModel,
            complexity: complexity.confidence,
            rationale: complexity.rationale
        }
    });
    
    return result.toDataStreamResponse();
}
```


```tsx
'use client';

import { useChat } from 'ai/react';
import { useState, useEffect } from 'react';

export default function AdvisorChat({ params }: { params: { id: string } }) {
    const [modelInfo, setModelInfo] = useState<{
        model: string;
        confidence: number;
    } | null>(null);
    
    const { messages, input, handleInputChange, handleSubmit, isLoading, data } = useChat({
        api: `/api/advisor/${params.id}`,
        onFinish: (message, options) => {
            // Extract model metadata from response
            if (options?.metadata) {
                setModelInfo({
                    model: options.metadata.model,
                    confidence: options.metadata.complexity
                });
            }
        }
    });
    
    return (
        <div className="max-w-4xl mx-auto p-6">
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold">
                    Testing: {params.id}
                </h1>
                
                {/* Model indicator badge (like ChatGPT's o1 indicator) */}
                {modelInfo && (
                    <div className={`px-3 py-1 rounded-full text-sm ${
                        modelInfo.model.includes('o1') 
                            ? 'bg-purple-100 text-purple-700' 
                            : 'bg-blue-100 text-blue-700'
                    }`}>
                        {modelInfo.model === 'o1-preview' && '🧠 Deep Reasoning'}
                        {modelInfo.model === 'o1-mini' && '💭 Reasoning'}
                        {modelInfo.model === 'gpt-5' && '🧠 Latest'}
                        <span className="ml-2 opacity-60">
                            {Math.round(modelInfo.confidence * 100)}% confidence
                        </span>
                    </div>
                )}
            </div>
            
            {/* Chat messages */}
            <div className="border rounded-lg p-4 h-[500px] overflow-y-auto mb-4">
                {messages.map((m) => (
                    <div key={m.id} className={`mb-4 ${
                        m.role === 'user' ? 'text-blue-600' : 'text-green-600'
                    }`}>
                        <strong>{m.role === 'user' ? 'You' : params.id}:</strong>
                        <p className="ml-4 whitespace-pre-wrap">{m.content}</p>
                    </div>
                ))}
                {isLoading && (
                    <div className="flex items-center gap-2 text-gray-500 italic">
                        <span className="animate-pulse">Thinking</span>
                        {modelInfo?.model.includes('o1') && (
                            <span className="text-xs">(using reasoning model)</span>
                        )}
                    </div>
                )}
            </div>
            
            {/* Input form */}
            <form onSubmit={handleSubmit} className="flex gap-2">
                <input
                    value={input}
                    onChange={handleInputChange}
                    placeholder="Type your message..."
                    className="flex-1 p-2 border rounded"
                    disabled={isLoading}
                />
                <button 
                    type="submit" 
                    disabled={isLoading}
                    className="px-6 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50"
                >
                    Send
                </button>
            </form>
        </div>
    );
}
```

#### Step 4: Advanced Routing with Context Awareness

```typescript
export class ContextAwareRouter extends ModelRouter {
    // Consider conversation history for better model selection
    analyzeWithContext(
        currentPrompt: string,
        conversationHistory: Array<{role: string, content: string}>
    ): QueryComplexity {
        let baseComplexity = this.analyzeComplexity(currentPrompt);
        
        // Check if this is a follow-up to a complex discussion
        const recentMessages = conversationHistory.slice(-4);
        const hasComplexContext = recentMessages.some(m => 
            m.content.length > 500 || 
            /previously discussed|as mentioned|following up/i.test(m.content)
        );
        
        if (hasComplexContext && baseComplexity.confidence < 0.5) {
            // Boost complexity if continuing complex discussion
            baseComplexity.confidence = Math.min(baseComplexity.confidence + 0.3, 1);
            baseComplexity.requiresReasoning = true;
            baseComplexity.suggestedModel = 'o1-mini';
            baseComplexity.rationale += ' (complex conversation context)';
        }
        
        // Check for specific advisor expertise requirements
        if (currentPrompt.includes('strategy') || currentPrompt.includes('campaign')) {
            // Advisor-specific boost
            baseComplexity.confidence = Math.min(baseComplexity.confidence + 0.2, 1);
        }
        
        return baseComplexity;
    }
}
```



```bash
npm install @vercel/chat-sdk
# Or use the forked version from demo.chat-sdk.dev
```

```typescript
import { ChatProvider, ChatInterface } from '@vercel/chat-sdk';
import { ModelRouter } from '@/lib/model-router';

export default function AdvancedAdvisorChat({ params }: { params: { id: string } }) {
    return (
        <ChatProvider
            // Custom model selection middleware
            middleware={async (request, next) => {
                const router = new ModelRouter();
                const complexity = router.analyzeComplexity(request.messages.slice(-1)[0].content);
                
                // Inject model selection into request
                request.model = complexity.suggestedModel;
                request.metadata = {
                    modelSelection: complexity,
                    advisorId: params.id
                };
                
                return next(request);
            }}
            
            // Stream configuration
            streamOptions={{
                onToken: (token) => console.log('Token:', token),
                onCompletion: (completion) => console.log('Complete:', completion),
                experimental_telemetry: true
            }}
        >
            <ChatInterface
                // UI customization
                appearance={{
                    theme: 'light',
                    showModelIndicator: true,
                    showTokenCount: true
                }}
                
                // System prompt injection
                systemPromptLoader={async () => {
                    const { piContent, pkContent } = await loadAdvisor(params.id);
                    return `${piContent}\n\n${pkContent}`;
                }}
                
                // Custom actions
                actions={{
                    onNewMessage: (message) => {
                        console.log('New message:', message);
                    },
                    onModelSwitch: (newModel) => {
                        console.log('Switched to:', newModel);
                    }
                }}
            />
        </ChatProvider>
    );
}
```

### Cost Optimization Strategy

```typescript
export class CostOptimizer {
    private readonly modelCosts = {
        'gpt-5': { input: 0.010, output: 0.030 },       // Latest model
        'o1-preview': { input: 0.015, output: 0.060 },  // 3-4x more expensive
        'o1-mini': { input: 0.003, output: 0.012 },     // middle ground
    };
    
    selectOptimalModel(
        complexity: QueryComplexity,
        budget: 'aggressive' | 'balanced' | 'quality'
    ): string {
        if (budget === 'aggressive') {
            // Always use cheapest unless absolutely necessary
        } else if (budget === 'balanced') {
            // Default routing
            return complexity.suggestedModel;
        } else {
            // Quality first - use best models more liberally
            return complexity.confidence > 0.3 ? 'o1-preview' : 'gpt-5';
        }
    }
    
    estimateCost(model: string, inputTokens: number, outputTokens: number): number {
        const costs = this.modelCosts[model as keyof typeof this.modelCosts];
        return (costs.input * inputTokens / 1000) + (costs.output * outputTokens / 1000);
    }
}
```

### Monitoring & Analytics

```typescript
export class ModelAnalytics {
    private selections: Array<{
        timestamp: Date;
        model: string;
        complexity: number;
        responseTime: number;
        tokenCount: number;
        cost: number;
    }> = [];
    
    track(selection: any) {
        this.selections.push({
            ...selection,
            timestamp: new Date()
        });
        
        // Send to analytics service
        if (typeof window !== 'undefined') {
            window.analytics?.track('Model Selected', selection);
        }
    }
    
    getStats() {
        const grouped = this.selections.reduce((acc, sel) => {
            acc[sel.model] = (acc[sel.model] || 0) + 1;
            return acc;
        }, {} as Record<string, number>);
        
        return {
            totalRequests: this.selections.length,
            modelDistribution: grouped,
            averageComplexity: this.selections.reduce((sum, s) => sum + s.complexity, 0) / this.selections.length,
            totalCost: this.selections.reduce((sum, s) => sum + s.cost, 0)
        };
    }
}
```

### Benefits of Intelligent Model Routing

1. **Cost Optimization**: Save 60-80% on API costs by using appropriate models
2. **Performance**: Faster responses for simple queries (200ms vs 2s+)
3. **Quality**: Complex queries get the reasoning power they need
4. **User Experience**: Automatic, no manual model selection needed
5. **Transparency**: Users see which model is being used and why

### Testing Model Selection

```typescript
describe('ModelRouter', () => {
    const router = new ModelRouter();
    
        const result = router.analyzeComplexity('What is your name?');
        expect(result.requiresReasoning).toBe(false);
    });
    
    test('selects o1 for complex reasoning', () => {
        const result = router.analyzeComplexity(
            'Let\'s think step by step about how to optimize this algorithm for better performance'
        );
        expect(result.suggestedModel).toMatch(/o1/);
        expect(result.requiresReasoning).toBe(true);
    });
    
    test('detects mathematical complexity', () => {
        const result = router.analyzeComplexity(
            'Calculate the derivative of x^3 + 2x^2 - 5x + 7'
        );
        expect(result.requiresReasoning).toBe(true);
    });
});
```

## 19) Assumptions & Open Questions

### Assumptions Made
1. **LLM Providers**: Google Gemini 2.5 Pro for PK generation, OpenAI GPT-5 for testing
2. **PHP Version**: PHP 8.1+ with typed properties
5. **Templates**: Existing Mustache templates work as-is

### Resolved Questions (from user feedback)
1. **Deployment**: Laravel Cloud for Laravel backend ✓
2. **Advisor List**: Bogusky, Cal, Hormozi, and Halbert ✓
3. **Authentication**: No auth for initial version ✓
4. **Model Routing**: Implement automatic selection like ChatGPT o1 ✓

### Remaining Open Questions
1. **Template Location**: Keep in resources/ or move to storage/?
2. **Context Limits**: How to handle >8000 token conversations?
3. **Cost Budget**: Aggressive, balanced, or quality-first routing?

### Technical Note on Providers
- **All models accessible via OpenRouter**: Gemini 2.5 Pro, Claude Opus/Sonnet 4, GPT-5
- **Single API key needed**: OPENROUTER_API_KEY for unified access
- **No need for separate provider keys**: Simplifies configuration

---


**Key Innovations**: 
2. **Intelligent model routing** - Automatic selection like ChatGPT's o1 detection
3. **Cost optimization** - 60-80% savings through smart model selection
