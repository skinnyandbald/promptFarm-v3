<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Advisor>
 */
class AdvisorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'full_name' => fake()->name(),
            'known_for' => fake()->sentence(),
            'era' => fake()->randomElement(['1980s', '1990s', '2000s', '2010s']),
            'style' => fake()->randomElement(['analytical', 'creative', 'strategic']),
            'industry' => fake()->randomElement(['technology', 'marketing', 'finance']),
            'advisor_type' => fake()->randomElement(['strategic', 'contrarian', 'analytical']),
            'primary_objective' => fake()->sentence(),
            'core_expertise_area' => fake()->randomElement(['Marketing Strategy', 'Business Development', 'Technology Innovation']),
            'related_expertise_areas' => [fake()->word(), fake()->word()],
            'communication_style_description' => fake()->paragraph(),
            'decision_making_approach' => fake()->paragraph(),
            'key_phrases_or_terminology' => [fake()->word(), fake()->word(), fake()->word()],
            'emotional_characteristics' => fake()->sentence(),
            'unique_perspectives_or_contrarian_stances' => fake()->paragraph(),
        ];
    }
}
