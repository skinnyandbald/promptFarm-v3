<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('advisors', function (Blueprint $table) {
            $table->text('secondary_perspectives')->nullable()->after('unique_perspectives_or_contrarian_stances')
                ->comment('Cross-functional perspectives that make this advisor multi-dimensional. Affects PI generation.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advisors', function (Blueprint $table) {
            $table->dropColumn('secondary_perspectives');
        });
    }
};