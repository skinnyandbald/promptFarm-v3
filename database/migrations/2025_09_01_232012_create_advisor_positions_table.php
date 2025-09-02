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
        Schema::create('advisor_positions', function (Blueprint $table) {
            $table->id();
            $table->string('advisor_key')->unique();
            $table->text('researched_positions');
            $table->string('research_model')->nullable();
            $table->float('research_temperature')->nullable();
            $table->json('metadata')->nullable(); // For storing additional context
            $table->timestamps();

            $table->index('advisor_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advisor_positions');
    }
};
