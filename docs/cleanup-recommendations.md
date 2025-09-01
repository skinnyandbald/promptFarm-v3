# Cleanup Recommendations

## Files and Folders to Clean Up

### 1. Test Folders in storage/app/advisors/

These folders contain test outputs and experiments that have been documented in lessons-learned.md:

#### **Can Delete** (Temporary test outputs, already documented)
- `test-conversations/` - One-off conversation tests
- `testing/` - Contains single test prompt file
- `test-debug/` - A/B/C test files (results documented in lessons-learned)
- `tension-test/` - Early tension experiments
- `bogusky-tension-test/` - Bogusky-specific tests
- `bogusky-tension-v2/` - V2 tension tests (approach integrated into main)

#### **Move to advisor-backups/** (Historical reference)
- `baseline-v2/` - Original V2 implementation for comparison
- `alex-bogusky-baseline/` - V3 original files
- `alex-bogusky-improved/` - Improved versions worth keeping

#### **Review Before Deleting**
- `analysis/` - Check if contains valuable analysis
- `historical/` - May contain useful version history

### 2. Recommended Cleanup Commands

```bash
# 1. Move historical baselines to backups
mv storage/app/advisors/baseline-v2 storage/app/advisor-backups/
mv storage/app/advisors/alex-bogusky-baseline storage/app/advisor-backups/bogusky-v3-original
mv storage/app/advisors/alex-bogusky-improved storage/app/advisor-backups/bogusky-improved

# 2. Move any valuable analysis
mv storage/app/advisors/analysis storage/app/advisor-tests/analysis-archive

# 3. Delete documented test outputs
rm -rf storage/app/advisors/test-conversations
rm -rf storage/app/advisors/testing
rm -rf storage/app/advisors/test-debug
rm -rf storage/app/advisors/tension-test
rm -rf storage/app/advisors/bogusky-tension-test
rm -rf storage/app/advisors/bogusky-tension-v2

# 4. Clean up empty directories
find storage/app/advisors -type d -empty -delete
```

### 3. Files to Keep

#### Production Advisor Files
All files in:
- `storage/app/advisors/alex-bogusky/`
- `storage/app/advisors/alex-hormozi/`
- `storage/app/advisors/cal-henderson/`
- `storage/app/advisors/gary-halbert/`

These contain the current production PI.md and PK.md files.

### 4. Private Folder Cleanup

Check and clean:
- `storage/app/private/advisors/comparison/` → Move valuable comparisons to `advisor-tests/comparisons/`
- `storage/app/private/advisors/grok-comparison/` → Move to `advisor-tests/model-tests/grok/`

### 5. Important Files to Preserve

Before deleting anything, ensure these are saved:
1. Any unique prompt experiments not in lessons-learned.md
2. Quality comparison data showing improvements
3. Temperature test results for each advisor
4. Model comparison outputs

## Summary

### Safe to Delete Now
- One-off test files
- A/B/C test prompts (documented)
- Temporary debug outputs
- Old Bogusky-specific tests

### Move First, Then Delete Source
- Historical baselines → advisor-backups/
- Valuable comparisons → advisor-tests/comparisons/
- Model tests → advisor-tests/model-tests/

### Keep Forever
- Production advisor files (4 main folders)
- Documented lessons and analysis
- Command reorganization docs

## Verification Before Cleanup

Run this to see what would be deleted:
```bash
# Dry run - just list files
find storage/app/advisors -type f \
  ! -path "*/alex-bogusky/*" \
  ! -path "*/alex-hormozi/*" \
  ! -path "*/cal-henderson/*" \
  ! -path "*/gary-halbert/*" \
  -name "*.md" | wc -l
```

This will show count of non-production .md files that could be cleaned up.