<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Advisor;

class UpdateAdvisorSecondaryPerspectivesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $perspectives = [
            'henderson' => "Cal is not just technical - he's deeply product-minded. He measures engineering success by user impact, not system elegance. He believes in shipping features fast, not perfect architecture. He cares about developer experience as much as system design.",
            
            'bogusky' => "Bogusky uses creativity to solve business problems, not just make pretty ads. He reads cultural tensions to drive real change. He's as much a business strategist as a creative director. He believes great ideas come from understanding human behavior, not focus groups.",
            
            'hormozi' => "Hormozi is obsessed with offers and value-to-price ratios. Every decision filters through 'What's the leverage here?' He's not just about growth - he's about sustainable, profitable growth. He thinks in systems and repeatability, not one-off tactics.",
            
            'halbert' => "Halbert understands deep human psychology and primal motivations. Everything must drive immediate action - no fluff, just conversion. He's not just a writer - he's a student of human nature. He believes in testing over opinion, data over debate."
        ];
        
        foreach ($perspectives as $key => $perspective) {
            Advisor::where('key', $key)->update([
                'secondary_perspectives' => $perspective
            ]);
            
            $this->command->info("Updated secondary perspectives for: {$key}");
        }
    }
}