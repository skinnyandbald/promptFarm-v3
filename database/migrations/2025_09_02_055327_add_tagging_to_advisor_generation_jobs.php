<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
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
                // For PostgreSQL: Convert to JSONB and add GIN index
                DB::statement('ALTER TABLE advisor_generation_jobs ALTER COLUMN tags TYPE JSONB USING tags::JSONB');
                DB::statement('CREATE INDEX idx_advisor_generation_jobs_tags ON advisor_generation_jobs USING GIN (tags)');
                break;

            case 'mysql':
            case 'mariadb':
                // For MySQL/MariaDB: Create a generated column for specific JSON keys if needed
                // Option 1: If we're searching for specific tags, create a generated column
                // Uncomment and adjust if you need to index specific JSON paths:
                /*
                DB::statement("ALTER TABLE advisor_generation_jobs ADD COLUMN tags_searchable VARCHAR(255)
                    GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(tags, '$[0]'))) STORED");
                DB::statement("CREATE INDEX idx_advisor_generation_jobs_tags_searchable
                    ON advisor_generation_jobs (tags_searchable)");
                */

                // Option 2: No index for now - JSON columns in MySQL don't support direct indexing
                // If searching is needed, consider using JSON_CONTAINS or JSON_SEARCH functions
                // or implement full-text search on the JSON content
                break;

            case 'sqlite':
                // SQLite doesn't support JSON indexing
                // Queries will use JSON functions like json_extract()
                break;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        // Drop database-specific indexes first
        switch ($driver) {
            case 'pgsql':
                DB::statement('DROP INDEX IF EXISTS idx_advisor_generation_jobs_tags');
                break;

            case 'mysql':
            case 'mariadb':
                // If we created a generated column, drop it
                /*
                DB::statement("DROP INDEX IF EXISTS idx_advisor_generation_jobs_tags_searchable");
                Schema::table('advisor_generation_jobs', function (Blueprint $table) {
                    $table->dropColumn('tags_searchable');
                });
                */
                break;
        }

        Schema::table('advisor_generation_jobs', function (Blueprint $table) {
            $table->dropColumn(['tags', 'memo']);
        });
    }
};
