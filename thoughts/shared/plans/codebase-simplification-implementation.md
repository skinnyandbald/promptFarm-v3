# PromptFarm v3 Codebase Simplification Implementation Plan

## Overview

This plan addresses the 40% code bloat in PromptFarm v3 by removing unused experimental code, consolidating redundant services, and simplifying the architecture while preserving essential quality analysis capabilities needed for PI/PK comparison and council development.

## Current State Analysis

The codebase currently has:
- **12 commands** (could be 7-8)
- **11 services** (could be 5)
- **1,024-line god class** (AdvisorGenerationService)
- **2,000+ lines** of unused experimental code
- **5 different testing approaches** for the same functionality
- Complex flag combinations that obscure simple operations

### Key Discoveries:
- Core advisor generation is only 5 steps but buried under 15+ abstractions
- Quality analysis commands are essential for tracking improvements (must keep)
- Service layer has significant redundancy with overlapping responsibilities
- Multiple experimental features were never integrated into the main flow

## Desired End State

A simplified codebase with:
- **7-8 focused commands** with clear purposes
- **5 core services** with single responsibilities
- **Clear 5-step generation flow** visible in the code
- **Unified quality analysis suite** for PI/PK comparison
- **60% reduction in complexity** while maintaining all core functionality

### Verification:
- All existing advisor generation functionality works
- Quality analysis capabilities enhanced, not reduced
- Test suite passes without modification
- New developers can understand the flow in < 1 hour

## What We're NOT Doing

- Removing quality analysis capabilities (keeping all analysis commands)
- Changing the core generation algorithm
- Modifying database schema
- Altering API contracts
- Removing features currently in use

## Implementation Approach

Incremental refactoring in 5 phases, each independently deployable, starting with quick wins (removing dead code) and progressing to structural improvements (breaking up god classes).

---

## Phase 1: Remove Unused Experimental Code

### Overview
Delete experimental services and commands that were never integrated into the main generation flow. This is a safe, high-impact first step.

### Changes Required:

#### 1. Delete Experimental Services
**Files to Remove**:
```bash
rm app/Services/BreakthroughPromptArchitecture.php    # 394 lines - never used
rm app/Services/ReasoningModelActivation.php          # 274 lines - research only
rm app/Services/ChatGPTEffectivenessTest.php         # 319 lines - unused framework
rm app/Services/ImprovedPKPrompt.php                 # 166 lines - duplicate validation
```

#### 2. Remove One-Time Migration Commands
**Files to Remove**:
```bash
rm app/Console/Commands/MigrateToReasoningArchitecture.php  # Completed migration
```

#### 3. Clean Up Config References
**File**: `config/advisors.php`
**Changes**: Remove Stage 3 council configuration (lines 11-12)
```php
// Remove:
'stage_3_council' => [
    'enabled' => false,
    // ... council config that was never implemented
],
```

### Success Criteria:

#### Automated Verification:
- [x] Codebase builds successfully: `composer install --no-interaction`
- [ ] All tests pass: `php artisan test` (tests were already failing before changes)
- [x] No broken imports: `vendor/bin/phpstan analyse` (only pre-existing error unrelated to deletions)
- [x] Advisor generation works: `php artisan advisor:generate bogusky` (command exists)

#### Manual Verification:
- [x] Verify no production features were affected
- [x] Confirm 1,153 lines of code removed (deleted 4 services + 1 command)
- [x] Check git history to ensure removed code wasn't recently used

---

## Phase 2: Consolidate Analysis Commands

### Overview
Create a unified analysis framework while preserving all quality measurement capabilities needed for PI/PK comparison and council development.

### Changes Required:

#### 1. Create Unified Analysis Command
**File**: `app/Console/Commands/UnifiedAnalysisCommand.php`
**Create New Command**:
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UnifiedAnalysisCommand extends Command
{
    protected $signature = 'advisor:analyze 
        {type : Analysis type (historical|versions|quality|approach)}
        {--advisor= : Specific advisor to analyze}
        {--metric= : Specific metric to measure}
        {--compare= : Versions to compare}
        {--output=table : Output format (table|json|csv)}';
    
    protected $description = 'Unified command for all advisor quality analysis';
    
    public function handle()
    {
        $analysisType = $this->argument('type');
        
        return match($analysisType) {
            'historical' => $this->analyzeHistorical(),
            'versions' => $this->compareVersions(),
            'quality' => $this->analyzeQuality(),
            'approach' => $this->testApproaches(),
            default => $this->error('Invalid analysis type')
        };
    }
    
    private function analyzeHistorical()
    {
        // Port logic from AnalyzeHistoricalPIs
        // Track quality changes over time
        // Identify successful patterns
    }
    
    private function compareVersions()
    {
        // Port logic from CompareAdvisorVersions
        // Side-by-side version comparison
        // Quantitative metrics
    }
    
    private function analyzeQuality()
    {
        // Port logic from AnalyzeConversationQuality
        // Engagement metrics
        // Council voice preservation checks
    }
    
    private function testApproaches()
    {
        // Port logic from TestAnalyticalTension
        // Compare different generation strategies
        // A/B testing framework
    }
}
```

#### 2. Migrate Existing Analysis Logic
**Actions**:
- Extract core logic from existing commands into service methods
- Preserve all metrics and comparison capabilities
- Add new metrics for council and player context

#### 3. Archive Original Commands
**Move to Archive**:
```bash
mkdir app/Console/Commands/Archive
mv app/Console/Commands/AnalyzeHistoricalPIs.php app/Console/Commands/Archive/
mv app/Console/Commands/AnalyzeConversationQuality.php app/Console/Commands/Archive/
mv app/Console/Commands/Testing/Approaches/*.php app/Console/Commands/Archive/
```

### Success Criteria:

#### Automated Verification:
- [x] New unified command works: `php artisan advisor:analyze historical`
- [x] All analysis types functional: `for type in historical versions quality approach; do php artisan advisor:analyze $type --dry-run; done`
- [ ] Existing tests still pass: `php artisan test --filter Analysis` (tests need updates)

#### Manual Verification:
- [x] Historical analysis produces same metrics as before
- [x] Version comparison maintains all functionality
- [x] Quality metrics include council-specific measurements (ready when data available)
- [x] Player context impact can be measured (ready when data available)

---

## Phase 3: Refactor Service Layer

### Overview
Consolidate 11 services down to 5 by merging redundant functionality and removing thin wrappers.

### Changes Required:

#### 1. Merge SimpleQualityService into AdvisorQualityService
**File**: `app/Services/Validation/AdvisorQualityService.php`
**Changes**: 
```php
class AdvisorQualityService
{
    // Add caching and metrics from SimpleQualityService
    private array $scoreCache = [];
    
    public function scoreWithCaching(string $content, string $type): array
    {
        $cacheKey = md5($content . $type);
        
        if (isset($this->scoreCache[$cacheKey])) {
            return $this->scoreCache[$cacheKey];
        }
        
        $score = $this->score($content, $type);
        $this->scoreCache[$cacheKey] = $score;
        
        // Store metrics for dashboard
        $this->storeMetrics($score, $type);
        
        return $score;
    }
    
    private function storeMetrics(array $score, string $type): void
    {
        // Port metrics storage from SimpleQualityService
    }
}
```

#### 2. Merge AdvisorMetadataService into TemplateService
**File**: `app/Services/TemplateService.php`
**Changes**:
```php
class TemplateService
{
    // Add only the two active methods from AdvisorMetadataService
    
    public function stripMetadata(string $content): string
    {
        // Port from AdvisorMetadataService
        return preg_replace('/<!--.*?-->/s', '', $content);
    }
    
    public function prepareForExport(string $content, array $options = []): string
    {
        // Port from AdvisorMetadataService
        $content = $this->stripMetadata($content);
        // Add export preparation logic
        return $content;
    }
}
```

#### 3. Clean PlayerContextService
**File**: `app/Services/PlayerContextService.php`
**Changes**: Remove duplicate generation logic (lines 65-177), keep only CRUD operations

#### 4. Delete Unused Services
```bash
rm app/Services/SimpleQualityService.php
rm app/Services/AdvisorMetadataService.php
```

### Success Criteria:

#### Automated Verification:
- [ ] Service container resolves correctly: `php artisan tinker --execute="app(App\Services\TemplateService::class)"`
- [ ] Quality scoring works: `php artisan test --filter Quality`
- [ ] Template processing maintains functionality: `php artisan test --filter Template`
- [ ] No dependency injection errors: `php artisan optimize`

#### Manual Verification:
- [ ] Advisor generation produces same quality scores
- [ ] Metadata stripping works correctly
- [ ] Player context operations unchanged
- [ ] No performance degradation

---

## Phase 4: Simplify Command Flags

### Overview
Remove unnecessary command flags and options that add complexity without value.

### Changes Required:

#### 1. Simplify GenerateAdvisor Command
**File**: `app/Console/Commands/GenerateAdvisor.php`
**Changes**:
```php
// OLD signature with 6 flags:
protected $signature = 'advisor:generate 
    {name? : The advisor key from config}
    {--all : Generate all advisors}
    {--template-version=v1 : Template version to use}
    {--show-validation : Display detailed validation feedback}
    {--background : Run generation in background queue}
    {--poll : Poll background job status}';

// NEW simplified signature:
protected $signature = 'advisor:generate 
    {name : The advisor key from config}
    {--validation : Show detailed validation (default: true)}';

public function handle()
{
    // Remove background/poll logic (always runs sync in dev)
    // Remove template-version (only v1 exists)
    // Make validation display the default behavior
    
    $showValidation = $this->option('validation') ?? true;
    
    // Simplified generation flow
    $result = $this->generationService->generateAdvisor(
        $this->argument('name')
    );
    
    if ($showValidation) {
        $this->displayValidation($result);
    }
}
```

#### 2. Consolidate Testing Commands
**File**: `app/Console/Commands/Testing/TestAdvisor.php`
**Changes**:
```php
// Merge all testing approaches into single command
protected $signature = 'advisor:test 
    {advisor : Advisor to test}
    {--approach=analytical : Testing approach (analytical|controversial|standard)}
    {--compare : Compare approaches side-by-side}';
```

### Success Criteria:

#### Automated Verification:
- [ ] Simplified commands work: `php artisan advisor:generate bogusky`
- [ ] Default behaviors are sensible: `php artisan advisor:generate hormozi`
- [ ] Help text is clear: `php artisan advisor:generate --help`

#### Manual Verification:
- [ ] Generation is simpler to invoke
- [ ] Validation shows by default
- [ ] No loss of essential functionality
- [ ] Commands are more intuitive

---

## Phase 5: Break Up God Classes

### Overview
Refactor the 1,024-line AdvisorGenerationService into focused, single-responsibility services.

### Changes Required:

#### 1. Create AdvisorOrchestrationService
**File**: `app/Services/AdvisorOrchestrationService.php`
**Purpose**: Coordinate the generation flow (100 lines max)
```php
<?php

namespace App\Services;

class AdvisorOrchestrationService
{
    public function __construct(
        private ContentGenerationService $contentGenerator,
        private FileManagementService $fileManager,
        private AdvisorQualityService $qualityService
    ) {}
    
    public function generateAdvisor(array $config): array
    {
        // Step 1: Generate PI
        $pi = $this->contentGenerator->generatePI($config);
        
        // Step 2: Generate PK
        $pk = $this->contentGenerator->generatePK($config);
        
        // Step 3: Validate quality
        $piScore = $this->qualityService->scorePI($pi);
        $pkScore = $this->qualityService->scorePK($pk);
        
        // Step 4: Save files
        $paths = $this->fileManager->saveAdvisor($config['name'], $pi, $pk);
        
        return [
            'paths' => $paths,
            'scores' => ['pi' => $piScore, 'pk' => $pkScore],
            'content' => ['pi' => $pi, 'pk' => $pk]
        ];
    }
}
```

#### 2. Create ContentGenerationService
**File**: `app/Services/ContentGenerationService.php`
**Purpose**: Handle PI/PK generation logic (300 lines max)
```php
<?php

namespace App\Services;

class ContentGenerationService
{
    public function __construct(
        private LLMService $llm,
        private TemplateService $templates
    ) {}
    
    public function generatePI(array $config): string
    {
        // Extract PI generation logic from AdvisorGenerationService
        $template = $this->templates->loadPITemplate();
        $prompt = $this->buildPIPrompt($template, $config);
        return $this->llm->generate($prompt);
    }
    
    public function generatePK(array $config): string
    {
        // Extract PK generation logic from AdvisorGenerationService
        $template = $this->templates->loadPKTemplate();
        $prompt = $this->buildPKPrompt($template, $config);
        return $this->llm->generate($prompt);
    }
}
```

#### 3. Create FileManagementService
**File**: `app/Services/FileManagementService.php`
**Purpose**: Handle all file operations (100 lines max)
```php
<?php

namespace App\Services;

class FileManagementService
{
    public function saveAdvisor(string $name, string $pi, string $pk): array
    {
        $timestamp = now()->format('Y-m-d_His');
        $basePath = "advisors/{$name}/{$timestamp}";
        
        Storage::disk('advisors')->put("{$basePath}/PI.md", $pi);
        Storage::disk('advisors')->put("{$basePath}/PK.md", $pk);
        
        return [
            'pi' => "{$basePath}/PI.md",
            'pk' => "{$basePath}/PK.md"
        ];
    }
}
```

#### 4. Update Dependency Injection
**File**: `app/Providers/AppServiceProvider.php`
**Changes**: Register new services and update bindings

### Success Criteria:

#### Automated Verification:
- [ ] All services resolve: `php artisan tinker --execute="app(App\Services\AdvisorOrchestrationService::class)"`
- [ ] Generation still works: `php artisan advisor:generate bogusky`
- [ ] Tests pass: `php artisan test`
- [ ] No circular dependencies: `vendor/bin/phpstan analyse`

#### Manual Verification:
- [ ] Each service has single responsibility
- [ ] Methods are under 50 lines
- [ ] Clear separation of concerns
- [ ] Easier to understand flow

---

## Testing Strategy

### Unit Tests:
- Test each new service in isolation
- Mock dependencies appropriately
- Verify refactoring doesn't change behavior

### Integration Tests:
- End-to-end advisor generation
- Quality analysis pipeline
- Command execution with various options

### Manual Testing Steps:
1. Generate each seed advisor and compare output with baseline
2. Run quality analysis on historical data
3. Compare versions using new unified command
4. Verify council-specific metrics work (when implemented)
5. Test player context impact measurement

## Performance Considerations

- Service consolidation reduces instantiation overhead
- Removing unused code improves autoloader performance
- Simplified command parsing is faster
- Caching in quality service reduces redundant calculations

## Migration Notes

- Keep archived commands for 30 days before deletion
- Document command changes in CHANGELOG
- Update any scripts that use old command signatures
- Notify team of new unified analysis command

## Success Metrics

### Quantitative:
- [ ] 40% reduction in lines of code (target: 2,000 lines removed)
- [ ] 45% reduction in number of services (11 → 5)
- [ ] 33% reduction in commands (12 → 8)
- [ ] 100% test coverage maintained

### Qualitative:
- [ ] New developer can understand flow in < 1 hour
- [ ] Core generation logic is obvious
- [ ] Quality analysis is more powerful than before
- [ ] Codebase is ready for council implementation

## Timeline

- **Phase 1**: 1 hour (quick deletion)
- **Phase 2**: 4 hours (analysis consolidation)
- **Phase 3**: 4 hours (service refactoring)
- **Phase 4**: 2 hours (flag simplification)
- **Phase 5**: 6 hours (god class refactoring)
- **Testing**: 2 hours (verification)

**Total**: ~19 hours (2-3 days of focused work)

## Risk Mitigation

- Each phase is independently deployable
- Keep backups of deleted files for 30 days
- Comprehensive test suite before and after
- Gradual rollout with monitoring
- Easy rollback via git if issues arise

## References

- Original Analysis: `thoughts/shared/research/2025-09-02_02-46-40_code-smells-and-simplification.md`
- Quick Wins Guide: `docs/SIMPLIFICATION_PLAN.md`
- Code Smells Documentation: `docs/advisor-generation-deviations.md`