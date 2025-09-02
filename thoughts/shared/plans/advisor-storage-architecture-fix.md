# Advisor Storage Architecture Fix - V1 Implementation Plan

## Overview
Fix the hybrid storage anti-pattern by making database the single source of truth, while providing simple file exports for local development testing. Enable version tagging system for tracking stable/baseline versions.

## Current State Analysis

**Problems with current implementation:**
- Content stored in 3 places: filesystem, advisor_generation_jobs table, and proposed advisors table
- Filesystem writes will fail on Laravel Cloud (ephemeral containers)
- API already serves from database, filesystem storage is redundant
- No version tagging or easy comparison system

**Key Discoveries:**
- API serves from `advisor_generation_jobs.pi_content/pk_content` (`AdvisorGenerationController.php:98-99`)
- Filesystem writes in `AdvisorGenerationService.php:71,90` are redundant
- Developer was adding third storage location in advisors table

## Desired End State

**Database as single source of truth:**
- All advisor content stored in `advisor_generation_jobs` table
- Version tagging system with memo field for tracking stable versions
- File exports only for local development testing
- S3 exports only for user downloads (temporary, auto-delete)

**Verification:**
- `php artisan advisor:generate bogusky` creates database record only
- `php artisan advisor:generate bogusky --export-files` creates database record + local files
- API responses work without filesystem dependency
- Tagged versions can be re-exported without re-running LLM

## What We're NOT Doing
- Complex S3 versioning or tagging systems
- Symlink management for local files
- Permanent file storage for production
- Breaking changes to existing API endpoints

## Implementation Approach
Database-first with optional local file exports for development, simplified S3 for user downloads

## Phase 1: Remove Filesystem Dependency

### Overview
Stop writing to filesystem by default, making database the sole source of truth for production.

### Changes Required:

#### 1. AdvisorGenerationService 
**File**: `app/Services/AdvisorGenerationService.php`
**Changes**: Remove filesystem writes, add optional file export flag

```php
public function generateAdvisor($advisorData, $version = 'v1', ?callable $progressCallback = null, bool $exportFiles = false): array
{
    // ... existing generation logic ...
    
    // Remove these lines:
    // Storage::disk('advisors')->put($piPath, $piContent);
    // Storage::disk('advisors')->put($pkPath, $pkContent);
    // Storage::disk('advisors')->put($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));
    
    // Add optional file export:
    if ($exportFiles) {
        $this->exportToFiles($advisorData, $piContent, $pkContent, $metadata);
    }
    
    return [
        'success' => true,
        'advisor_name' => $advisorData['name'],
        'pi_content' => $piContent,
        'pk_content' => $pkContent,
        'exported_files' => $exportFiles ? $this->getExportedFilePaths($advisorData) : null,
        'quality' => $qualityReport,
        'generated_at' => now()->toIso8601String(),
    ];
}

private function exportToFiles($advisorData, $piContent, $pkContent, $metadata): void
{
    $advisorName = $advisorData['full_name'] ?? $advisorData['name'] ?? 'Unknown';
    $pascalName = str_replace(' ', '', ucwords(str_replace('-', ' ', $advisorName)));
    $timestamp = now()->format('Y-m-d');
    $jobId = $metadata['job_id'] ?? 'unknown';
    
    $basePath = "advisors/{$advisorName}/{$timestamp}-job-{$jobId}";
    Storage::disk('advisors')->makeDirectory($basePath);
    
    // Use correct naming: AlexBogusky_PI.md, AlexBogusky_PK.md
    Storage::disk('advisors')->put("{$basePath}/{$pascalName}_PI.md", $piContent);
    Storage::disk('advisors')->put("{$basePath}/{$pascalName}_PK.md", $pkContent);
    Storage::disk('advisors')->put("{$basePath}/metadata.json", json_encode($metadata, JSON_PRETTY_PRINT));
}
```

#### 2. GenerateAdvisor Command
**File**: `app/Console/Commands/GenerateAdvisor.php`  
**Changes**: Add --export-files flag

```php
protected $signature = 'advisor:generate 
                       {advisor : The advisor key or name to generate}
                       {--export-files : Export generated files to local storage for testing}
                       {--version=v1 : Template version to use}';

public function handle(): int
{
    // ... existing logic ...
    
    $result = $this->generationService->generateAdvisor(
        $advisor,
        $this->option('version'),
        $progressCallback,
        $this->option('export-files') // Pass export flag
    );
    
    if ($this->option('export-files')) {
        $this->info("📁 Files exported to: {$result['exported_files']['base_path']}");
        $this->info("📄 Files: {$result['exported_files']['pi']}, {$result['exported_files']['pk']}");
    }
}
```

#### 3. Update GenerateAdvisorJob
**File**: `app/Jobs/GenerateAdvisorJob.php`
**Changes**: Pass exportFiles=false (database only for queued jobs)

```php
public function handle(AdvisorGenerationService $service): void
{
    // ... existing logic ...
    
    $result = $service->generateAdvisor(
        $advisor,
        'v1',
        function (int $progress, string $step) {
            $this->generationJob->updateProgress($progress, $step);
        },
        false // Never export files for queued jobs
    );
    
    // ... rest unchanged
}
```

### Success Criteria:

#### Automated Verification:
- [ ] `php artisan advisor:generate bogusky` creates database record only: `AdvisorGenerationJob::where('advisor_key', 'bogusky')->latest()->first()`
- [ ] `php artisan advisor:generate bogusky --export-files` creates database record + files: `Storage::disk('advisors')->exists('alex-bogusky/.../AlexBogusky_PI.md')`
- [ ] API endpoints still return advisor content: `curl /api/advisors/jobs/{id}/result`
- [ ] All tests pass: `php artisan test`
- [ ] No filesystem writes in production mode: `grep -r "Storage::disk('advisors')" app/Services/AdvisorGenerationService.php` returns only conditional exports

#### Manual Verification:  
- [ ] Generated files use correct naming: AlexBogusky_PI.md, AlexBogusky_PK.md
- [ ] API responses contain complete PI/PK content
- [ ] No broken file references in generated content
- [ ] Local development workflow works for testing

---

## Phase 2: Add Version Tagging System

### Overview
Add tagging system to advisor_generation_jobs table for tracking stable/baseline versions.

### Changes Required:

#### 1. Database Migration
**File**: `database/migrations/2025_09_02_add_tagging_to_advisor_generation_jobs.php`
**Changes**: Add tags and memo columns with DB-specific JSON indexing

```php
public function up(): void
{
    Schema::table('advisor_generation_jobs', function (Blueprint $table) {
        $table->json('tags')->nullable()->after('quality_report');
        $table->text('memo')->nullable()->after('tags');
    });

    // Database-specific JSON indexing strategies
    $driver = DB::connection()->getDriverName();
    
    switch ($driver) {
        case 'pgsql':
            // PostgreSQL: Convert to JSONB and add GIN index
            DB::statement('ALTER TABLE advisor_generation_jobs ALTER COLUMN tags TYPE JSONB USING tags::JSONB');
            DB::statement('CREATE INDEX idx_advisor_generation_jobs_tags ON advisor_generation_jobs USING GIN (tags)');
            break;
            
        case 'mysql':
        case 'mariadb':
            // MySQL/MariaDB: JSON columns don't support direct indexing
            // Use JSON_CONTAINS or JSON_SEARCH functions for queries
            // Or create generated columns for specific paths if needed
            break;
            
        case 'sqlite':
            // SQLite: No JSON indexing, use json_extract() in queries
            break;
    }
}
```

#### 2. AdvisorGenerationJob Model
**File**: `app/Models/AdvisorGenerationJob.php`
**Changes**: Add tagging methods

```php
protected $fillable = [
    // ... existing fields ...
    'tags',
    'memo',
];

protected function casts(): array
{
    return [
        // ... existing casts ...
        'tags' => 'array',
    ];
}

public function addTag(string $tag, ?string $memo = null): void
{
    $tags = $this->tags ?? [];
    if (!in_array($tag, $tags)) {
        $tags[] = $tag;
        $this->update(['tags' => $tags, 'memo' => $memo]);
    }
}

public function removeTag(string $tag): void
{
    $tags = array_filter($this->tags ?? [], fn($t) => $t !== $tag);
    $this->update(['tags' => array_values($tags)]);
}

public function hasTag(string $tag): bool
{
    return in_array($tag, $this->tags ?? []);
}

public function scopeWithTag($query, string $tag)
{
    return $query->whereJsonContains('tags', $tag);
}
```

#### 3. Tag Management Command
**File**: `app/Console/Commands/TagAdvisorVersion.php`
**Changes**: Create new command for tagging

```php
protected $signature = 'advisor:tag 
                       {advisor : The advisor key}
                       {job-id : The generation job ID to tag}
                       {tag : The tag name (e.g., stable, baseline)}
                       {--memo= : Optional memo describing this version}';

public function handle(): int
{
    $advisorKey = $this->argument('advisor');
    $jobId = $this->argument('job-id');
    $tag = $this->argument('tag');
    $memo = $this->option('memo');

    $job = AdvisorGenerationJob::where('advisor_key', $advisorKey)
        ->where('id', $jobId)
        ->where('status', AdvisorGenerationJob::STATUS_COMPLETED)
        ->first();

    if (!$job) {
        $this->error("Completed generation job #{$jobId} not found for advisor '{$advisorKey}'");
        return 1;
    }

    $job->addTag($tag, $memo);

    $this->info("Tagged job #{$jobId} for '{$advisorKey}' with '{$tag}'");
    if ($memo) {
        $this->info("Memo: {$memo}");
    }

    return 0;
}
```

#### 4. List Tags Command  
**File**: `app/Console/Commands/ListAdvisorTags.php`
**Changes**: Create command to view tagged versions

```php
protected $signature = 'advisor:list-tags {advisor : The advisor key}';

public function handle(): int
{
    $advisorKey = $this->argument('advisor');
    
    $taggedJobs = AdvisorGenerationJob::where('advisor_key', $advisorKey)
        ->whereNotNull('tags')
        ->where('status', AdvisorGenerationJob::STATUS_COMPLETED)
        ->orderBy('created_at', 'desc')
        ->get();

    if ($taggedJobs->isEmpty()) {
        $this->info("No tagged versions found for '{$advisorKey}'");
        return 0;
    }

    $headers = ['Job ID', 'Tags', 'Created', 'Memo'];
    $rows = $taggedJobs->map(function ($job) {
        return [
            $job->id,
            implode(', ', $job->tags ?? []),
            $job->created_at->format('Y-m-d H:i'),
            substr($job->memo ?? '', 0, 50) . (strlen($job->memo ?? '') > 50 ? '...' : '')
        ];
    });

    $this->table($headers, $rows);
    return 0;
}
```

### Success Criteria:

#### Automated Verification:
- [ ] Migration runs successfully: `php artisan migrate`
- [ ] Tag command works: `php artisan advisor:tag bogusky 15 stable --memo "test"`
- [ ] List command shows tagged versions: `php artisan advisor:list-tags bogusky`
- [ ] Database stores tags correctly: `AdvisorGenerationJob::find(15)->hasTag('stable')`

#### Manual Verification:
- [ ] Can tag completed generation jobs
- [ ] Can list all tagged versions for an advisor
- [ ] Memo field stores and displays notes correctly
- [ ] Multiple tags per job work correctly

---

## Phase 3: Export Tagged Versions

### Overview
Add ability to export any tagged version to files without re-running LLM.

### Changes Required:

#### 1. Export Command
**File**: `app/Console/Commands/ExportAdvisorVersion.php`
**Changes**: Create command to export from database

```php
protected $signature = 'advisor:export 
                       {advisor : The advisor key}
                       {--tag= : Export specific tagged version}
                       {--job-id= : Export specific job ID}
                       {--latest : Export latest completed version}';

public function handle(): int
{
    $advisorKey = $this->argument('advisor');
    
    $job = null;
    if ($this->option('tag')) {
        $job = AdvisorGenerationJob::where('advisor_key', $advisorKey)
            ->withTag($this->option('tag'))
            ->where('status', AdvisorGenerationJob::STATUS_COMPLETED)
            ->latest()
            ->first();
    } elseif ($this->option('job-id')) {
        $job = AdvisorGenerationJob::find($this->option('job-id'));
    } elseif ($this->option('latest')) {
        $job = AdvisorGenerationJob::where('advisor_key', $advisorKey)
            ->where('status', AdvisorGenerationJob::STATUS_COMPLETED)
            ->latest()
            ->first();
    }

    if (!$job) {
        $this->error("No matching generation job found");
        return 1;
    }

    $advisor = $job->advisor;
    $advisorName = $advisor->full_name ?? $advisor->name ?? 'Unknown';
    $pascalName = str_replace(' ', '', ucwords(str_replace('-', ' ', $advisorName)));
    
    $timestamp = now()->format('Y-m-d-H-i-s');
    $basePath = "advisors/{$advisorName}/exports/{$timestamp}";
    
    Storage::disk('advisors')->makeDirectory($basePath);
    Storage::disk('advisors')->put("{$basePath}/{$pascalName}_PI.md", $job->pi_content);
    Storage::disk('advisors')->put("{$basePath}/{$pascalName}_PK.md", $job->pk_content);
    
    $this->info("Exported job #{$job->id} to: {$basePath}");
    $this->info("Files: {$pascalName}_PI.md, {$pascalName}_PK.md");
    
    return 0;
}
```

### Success Criteria:

#### Automated Verification:
- [ ] Export command works: `php artisan advisor:export bogusky --latest`
- [ ] Tagged export works: `php artisan advisor:export bogusky --tag=stable`
- [ ] Files created with correct naming: `Storage::disk('advisors')->exists('alex-bogusky/exports/.../AlexBogusky_PI.md')`
- [ ] Content matches database: `file_get_contents() === $job->pi_content`

#### Manual Verification:
- [ ] Exported files contain complete, correct content
- [ ] File naming follows AlexBogusky_PI.md pattern
- [ ] Can export any tagged version without LLM calls
- [ ] Export timestamps prevent overwrites

---

## Phase 4: Cleanup and Production Config

### Overview
Clean up old filesystem references and configure for Laravel Cloud deployment.

### Changes Required:

#### 1. Update Tests
**Files**: `tests/Feature/AdvisorGenerationTest.php`, `tests/Feature/UnifiedAnalysisCommandTest.php`
**Changes**: Remove filesystem assertions, focus on database content

```php
// Remove these assertions:
// Storage::disk('advisors')->assertExists('test-expert-advisor/PI.md');

// Replace with database assertions:
$this->assertDatabaseHas('advisor_generation_jobs', [
    'advisor_key' => 'test-expert-advisor',
    'status' => AdvisorGenerationJob::STATUS_COMPLETED
]);

$job = AdvisorGenerationJob::where('advisor_key', 'test-expert-advisor')->latest()->first();
$this->assertNotNull($job->pi_content);
$this->assertNotNull($job->pk_content);
```

#### 2. Remove Unused Analysis Commands  
**Files**: Commands that read from filesystem
**Changes**: Update to read from database or remove if redundant

#### 3. Configure File Cleanup
**File**: `app/Console/Commands/CleanupExportedFiles.php`
**Changes**: Create cleanup command for local development

```php
protected $signature = 'advisor:cleanup-files 
                       {--days=30 : Delete non-tagged files older than X days}
                       {--dry-run : Show what would be deleted without deleting}';

public function handle(): int
{
    $days = $this->option('days');
    $dryRun = $this->option('dry-run');
    
    $cutoff = now()->subDays($days);
    
    // Find directories older than cutoff that don't contain tagged versions
    $directories = Storage::disk('advisors')->directories('advisors');
    
    foreach ($directories as $advisorDir) {
        $this->cleanupAdvisorDirectory($advisorDir, $cutoff, $dryRun);
    }
    
    return 0;
}
```

#### 4. Add to Scheduler
**File**: `routes/console.php`
**Changes**: Schedule cleanup

```php
Schedule::command('advisor:cleanup-files --days=30')->weekly();
```

### Success Criteria:

#### Automated Verification:
- [ ] All tests pass: `php artisan test`
- [ ] No filesystem dependencies in production code: `grep -r "Storage::disk('advisors')" app/ | grep -v "exportFiles\|cleanup"`
- [ ] Cleanup command works: `php artisan advisor:cleanup-files --dry-run`
- [ ] Laravel Cloud deployment succeeds (no filesystem writes)

#### Manual Verification:
- [ ] API works without any local files
- [ ] Export functionality preserved for development
- [ ] Old files cleaned up appropriately
- [ ] No broken references in existing code

---

## Testing Strategy

### Unit Tests:
- Test AdvisorGenerationService with exportFiles=false (database only)
- Test AdvisorGenerationService with exportFiles=true (database + files) 
- Test tagging methods on AdvisorGenerationJob model
- Test export command with various options

### Integration Tests:
- Full generation → tagging → export workflow
- API responses with database-only storage
- Cleanup command with various scenarios

### Manual Testing Steps:
1. Generate advisor without --export-files flag, verify database content
2. Generate advisor with --export-files flag, verify files created
3. Tag a completed generation job
4. Export tagged version, verify content matches
5. Test API endpoints return correct content
6. Deploy to Laravel Cloud, verify no filesystem errors

## Performance Considerations
- Database storage is more efficient than filesystem I/O
- S3 exports only created on-demand (user requests)
- Local file cleanup prevents disk space issues
- Tagged versions enable quick comparisons without LLM costs

## Migration Notes
- Existing advisor_generation_jobs already contain pi_content/pk_content
- No data migration needed, only schema additions
- Can run new system alongside old filesystem temporarily
- Tests updated to match new architecture

## References
- Current API: `app/Http/Controllers/AdvisorGenerationController.php:98-99`
- Generation service: `app/Services/AdvisorGenerationService.php:71,90`  
- Job processor: `app/Jobs/GenerateAdvisorJob.php:54-58`
- Laravel Cloud documentation: https://cloud.laravel.com/docs/storage