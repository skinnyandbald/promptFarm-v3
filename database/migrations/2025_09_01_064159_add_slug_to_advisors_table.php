<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Advisor;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('advisors', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name')->unique();
        });
        
        // Generate slugs for existing advisors
        Advisor::all()->each(function ($advisor) {
            $advisor->slug = Str::slug($advisor->name);
            $advisor->save();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advisors', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
