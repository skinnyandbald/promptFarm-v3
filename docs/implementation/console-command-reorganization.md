# Command Organization Plan

## Current Command Analysis

### Production Commands (Keep in main Commands folder)
- **GenerateAdvisor.php** - Core production command for generating advisors
- **HorizonStatus.php** - Production monitoring command

### Testing/Development Commands (Need organization)

#### Advisor-Specific Test Commands (Can be abstracted)
- **TestBoguskyGeneration.php** - Tests Bogusky generation specifically
- **TestHalbertGeneration.php** - Tests Halbert generation specifically
- **CompareBoguskyQuality.php** - Compares Bogusky versions
- **AnalyzeAllBoguskyVersions.php** - Analyzes all Bogusky iterations
- **ShowBoguskyFolders.php** - Lists Bogusky test folders

#### General Test Commands (Keep but organize)
- **TestAdvisorGeneration.php** - Generic advisor testing
- **TestAnalyticalTension.php** - Tests tension approach
- **TestControversialGeneration.php** - Tests controversial content
- **TestGrokGeneration.php** - Tests Grok model integration
- **TestGrokViaVibeTools.php** - Tests vibe-tools integration
- **TestOpenRouterModels.php** - Tests OpenRouter models

## Proposed New Structure

```
app/Console/Commands/
├── GenerateAdvisor.php                    # Production command
├── HorizonStatus.php                      # Production command
│
├── Testing/                                # New folder for test commands
│   ├── CompareAdvisorVersions.php        # Generalized from CompareBoguskyQuality
│   ├── TestAdvisorGeneration.php         # Generic advisor testing (existing)
│   ├── TestSpecificAdvisor.php           # Generalized from TestBoguskyGeneration
│   │
│   ├── Models/                           # Model-specific tests
│   │   ├── TestGrokGeneration.php
│   │   ├── TestOpenRouterModels.php
│   │   └── TestGrokViaVibeTools.php
│   │
│   └── Approaches/                       # Approach-specific tests
│       ├── TestAnalyticalTension.php
│       └── TestControversialGeneration.php
│
└── Utilities/                             # Utility commands
    ├── AnalyzeAdvisorVersions.php        # Generalized from AnalyzeAllBoguskyVersions
    └── ShowAdvisorTestFolders.php        # Generalized from ShowBoguskyFolders
```

## Storage Organization

```
storage/app/
├── advisors/                              # Production advisor files
│   ├── alex-bogusky/
│   ├── alex-hormozi/
│   ├── cal-henderson/
│   └── gary-halbert/
│
├── advisor-tests/                         # All test outputs (new)
│   ├── comparisons/                      # Version comparisons
│   │   ├── bogusky/
│   │   ├── hormozi/
│   │   └── [advisor]/
│   │
│   ├── experiments/                      # Experimental approaches
│   │   ├── analytical-tension/
│   │   ├── controversial/
│   │   └── hybrid-approach/
│   │
│   ├── model-tests/                      # Model-specific tests
│   │   ├── grok/
│   │   ├── openrouter/
│   │   └── temperature-tests/
│   │
│   └── debug/                            # Debug outputs
│       ├── prompts/
│       └── responses/
│
└── advisor-backups/                      # Historical versions
    ├── baseline-v2/
    └── baseline-v3/
```

## Commands to Abstract

### 1. TestBoguskyGeneration → TestSpecificAdvisor
**Current**: Hard-coded for Bogusky
**New**: Accept advisor key as argument
```bash
# Old
php artisan advisor:test-bogusky

# New
php artisan testing:advisor bogusky --compare --save
php artisan testing:advisor henderson --compare
```

### 2. CompareBoguskyQuality → CompareAdvisorVersions
**Current**: Bogusky-specific paths
**New**: Dynamic advisor selection
```bash
# Old
php artisan advisor:compare-bogusky

# New
php artisan testing:compare bogusky --all
php artisan testing:compare henderson --baseline=v2
```

### 3. AnalyzeAllBoguskyVersions → AnalyzeAdvisorVersions
**Current**: Analyzes only Bogusky
**New**: Analyze any advisor's versions
```bash
# Old
php artisan advisor:analyze-bogusky-versions

# New
php artisan utility:analyze-versions bogusky
php artisan utility:analyze-versions --all
```

## Files to Move/Clean

### Can Delete (Temporary test outputs)
- `storage/app/advisors/test-conversations/` - Old test conversations
- `storage/app/advisors/testing/bogusky-bullshit-filter-test-prompt.md` - One-off test
- `storage/app/advisors/test-debug/prompt-test-*.md` - A/B/C test prompts (documented in lessons-learned)

### Should Move to advisor-tests/
- `storage/app/advisors/test-debug/` → `storage/app/advisor-tests/debug/`
- `storage/app/private/advisors/comparison/` → `storage/app/advisor-tests/comparisons/`
- `storage/app/private/advisors/grok-comparison/` → `storage/app/advisor-tests/model-tests/grok/`

### Keep for Historical Reference
- `storage/app/advisors/baseline-v2/` → `storage/app/advisor-backups/baseline-v2/`
- `storage/app/advisors/alex-bogusky-baseline/` → `storage/app/advisor-backups/bogusky-v3-original/`

## Implementation Priority

1. **Create generalized CompareAdvisorVersions command** - Most useful for ongoing testing
2. **Create TestSpecificAdvisor command** - Replaces multiple advisor-specific commands
3. **Organize storage folders** - Clean up test outputs
4. **Move commands to new folder structure** - Better organization
5. **Delete truly temporary files** - After confirming they're documented

## Commands That Stay As-Is
- **GenerateAdvisor** - Production command, well-designed
- **TestAnalyticalTension** - Specific approach test, useful as-is
- **TestOpenRouterModels** - Model testing utility, good as-is