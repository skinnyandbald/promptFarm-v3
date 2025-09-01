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
        Schema::create('player_contexts', function (Blueprint $table) {
            $table->id();
            
            // User relationship
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade')
                ->comment('User who owns this player context');
            
            // Core player data (Stage 2)
            $table->text('background_story')->nullable()
                ->comment('Player defining origin story and background');
            $table->string('industry')->nullable()
                ->comment('Primary business domain/industry');
            $table->string('business_type')->nullable()
                ->comment('Type of business (startup, enterprise, agency, etc)');
            $table->json('current_challenges')->nullable()
                ->comment('Array of current pain points and challenges');
            $table->json('goals')->nullable()
                ->comment('Short and long-term objectives');
            
            // Preferences for advisor interaction
            $table->enum('communication_style', ['direct', 'collaborative', 'analytical', 'inspirational'])
                ->default('direct')
                ->comment('Preferred communication style');
            $table->enum('detail_level', ['high', 'medium', 'low'])
                ->default('medium')
                ->comment('Preferred level of detail in responses');
            $table->enum('example_preference', ['industry_specific', 'general', 'mixed'])
                ->default('mixed')
                ->comment('Type of examples preferred');
            $table->json('framework_preferences')->nullable()
                ->comment('Preferred methodologies and frameworks');
            
            // Export tracking for external deployment
            $table->timestamp('last_advisor_export_at')->nullable()
                ->comment('Last time an advisor was exported with this context');
            $table->unsignedInteger('exported_advisors_count')->default(0)
                ->comment('Number of advisors exported with this context');
            $table->text('feedback_notes')->nullable()
                ->comment('Manual feedback on advisor effectiveness');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index('user_id');
            $table->index('industry');
            $table->index('last_advisor_export_at');
            $table->index('exported_advisors_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_contexts');
    }
};