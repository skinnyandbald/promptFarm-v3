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
        Schema::create('advisors', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique()->index();
            $table->string('name');
            $table->string('full_name');
            $table->text('known_for');
            $table->string('era');
            $table->text('style');
            $table->string('industry');
            $table->text('primary_objective');
            $table->string('core_expertise_area');
            $table->json('related_expertise_areas');
            $table->text('communication_style_description');
            $table->text('decision_making_approach');
            $table->json('key_phrases_or_terminology');
            $table->text('emotional_characteristics');
            $table->text('unique_perspectives_or_contrarian_stances');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advisors');
    }
};