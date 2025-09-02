# PromptFarm v3 Simplification Plan

## The Problem
The codebase is **40% larger than necessary** with significant over-engineering:
- **12 commands** when 7 would suffice  
- **11 services** that could be 5
- **2,000+ lines** of unused experimental code
- **5 different ways** to test the same thing

## Quick Wins (Do Today)

### 1. Delete Unused Experimental Services (Saves 1,153 lines)
```bash
rm app/Services/BreakthroughPromptArchitecture.php  # 394 lines - never used
rm app/Services/ReasoningModelActivation.php        # 274 lines - research only  
rm app/Services/ChatGPTEffectivenessTest.php       # 319 lines - unused framework
rm app/Services/ImprovedPKPrompt.php               # 166 lines - duplicate validation
```

### 2. Remove Redundant Testing Commands (Saves 500+ lines)
```bash
rm app/Console/Commands/Testing/Approaches/TestControversialGeneration.php
rm app/Console/Commands/Testing/Approaches/TestAnalyticalTension.php
rm app/Console/Commands/MigrateToReasoningArchitecture.php
rm app/Console/Commands/AnalyzeHistoricalPIs.php
rm app/Console/Commands/DebugPromptStructure.php
```

### 3. Simplify GenerateAdvisor Command Flags
Current (6 flags):
```php
{--all} {--template-version=v1} {--show-validation} {--background} {--poll}
```

Simplified (1 optional flag):
```php
{--validation}  // Show validation details (make default behavior)
```

## Core Refactoring (This Week)

### 1. Break Up the God Class
**AdvisorGenerationService.php** (1,024 lines) → 3 focused services:
- `AdvisorOrchestrator.php` (~100 lines) - Flow coordination
- `ContentGenerator.php` (~300 lines) - PI/PK generation  
- `FileManager.php` (~100 lines) - Storage operations

### 2. Consolidate Services
From 11 → 5 services:
- **Delete**: SimpleQualityService (merge into AdvisorQualityService)
- **Delete**: AdvisorMetadataService (merge 2 methods into TemplateService)
- **Fix**: PlayerContextService (remove duplicate generation logic)
- **Delete**: All experimental services (already removed above)

### 3. Unify Testing
From 5 testing approaches → 1 command:
```bash
php artisan advisor:test {advisor} {--approach=standard}
```

## What to Keep (Core Functionality)

### Essential Commands (7)
1. `advisor:generate` - Core generation
2. `advisor:research` - Position research  
3. `advisor:test` - Unified testing
4. `testing:compare` - Version comparison
5. `advisor:analyze-conversation` - Quality analysis
6. `horizon:status` - Queue monitoring
7. Existing Tinker for debugging

### Essential Services (5)
1. **AdvisorOrchestrator** - Main flow
2. **LLMService** - AI communication
3. **TemplateService** - Template & metadata
4. **QualityValidator** - Unified validation
5. **PlayerContextService** - Player data only

### Essential Config (2 files)
1. `config/advisors.php` - All advisor config (merge tensions & flavors)
2. `config/ai-models.php` - Model settings (simplified)

## Remove All Council References
The council system was never built but adds confusion:
- Remove Stage 3 config from `advisors.php`
- Remove council references from comments
- Remove council documentation sections

## Expected Results

### Before
- 12 commands, 11 services
- 5,000+ lines of service code
- Complex flag combinations
- 5 ways to test advisors
- Unclear core flow

### After  
- 7 commands, 5 services
- ~2,000 lines of service code
- Simple, clear interfaces
- 1 unified testing approach
- Obvious 5-step generation flow

### Benefits
- **60% faster** to understand the codebase
- **40% less code** to maintain
- **Clearer architecture** for new features
- **Easier debugging** with focused services
- **Faster tests** without redundancy

## Implementation Order

1. **Day 1**: Delete unused files (1 hour)
2. **Day 2**: Simplify command flags (2 hours)
3. **Day 3**: Consolidate services (4 hours)
4. **Day 4**: Break up god class (4 hours)
5. **Day 5**: Clean up config and docs (2 hours)

Total: ~13 hours to clean up months of technical debt

## The Core Flow (What Actually Matters)

```
User runs: php artisan advisor:generate bogusky
    ↓
1. Load template & config
2. Generate PI with LLM
3. Generate PK with LLM  
4. Validate quality
5. Save files
    ↓
Done! (Everything else is noise)
```

This simple 5-step process should be obvious in the code, not buried under layers of abstraction.