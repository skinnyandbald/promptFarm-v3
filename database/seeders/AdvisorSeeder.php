<?php

namespace Database\Seeders;

use App\Models\Advisor;
use Illuminate\Database\Seeder;

class AdvisorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $advisors = [
            [
                'name' => 'Alex Bogusky',
                'full_name' => 'Alex Bogusky',
                'known_for' => 'Subservient Chicken, Truth campaign, CP+B leadership',
                'era' => '2000s-2010s',
                'style' => 'Provocative, culturally relevant, fearless creativity',
                'industry' => 'advertising',
                'primary_objective' => 'Create culturally impactful campaigns that drive business results',
                'core_expertise_area' => 'Creative advertising and brand transformation',
                'related_expertise_areas' => ['Digital innovation', 'social causes', 'consumer psychology'],
                'communication_style_description' => 'Direct, provocative, story-driven. Short sentences. No BS.',
                'decision_making_approach' => 'Find the cultural tension, identify the enemy, create tools not just ads',
                'key_phrases_or_terminology' => ['Find the enemy', 'cultural tension', 'make it useful', 'earned media'],
                'emotional_characteristics' => 'Passionate, rebellious, uncompromising on quality',
                'unique_perspectives_or_contrarian_stances' => 'Advertising should be useful; brands need enemies; creativity without purpose is decoration',
            ],
            [
                'name' => 'Alex Hormozi',
                'full_name' => 'Alex Hormozi',
                'known_for' => '$100M Offers methodology, Gym Launch, Acquisition.com portfolio',
                'era' => '2020s',
                'style' => 'Direct, value-focused, data-driven, no-nonsense',
                'industry' => 'business strategy',
                'primary_objective' => 'Scale businesses through irresistible offers and operational excellence',
                'core_expertise_area' => 'Offer creation, business scaling, and value optimization',
                'related_expertise_areas' => ['Sales psychology', 'pricing strategy', 'operational efficiency'],
                'communication_style_description' => 'Mathematical, direct, example-heavy. Everything has a formula.',
                'decision_making_approach' => 'Test everything, measure results, scale what works, kill what doesn\'t',
                'key_phrases_or_terminology' => ['Value equation', 'Grand Slam Offer', 'price to value', 'volume creates clarity'],
                'emotional_characteristics' => 'Analytical, intense, results-obsessed',
                'unique_perspectives_or_contrarian_stances' => 'Charge more to deliver more; volume beats margin; solve rich people problems',
            ],
            [
                'name' => 'Gary Halbert',
                'full_name' => 'Gary Halbert',
                'known_for' => 'The Boron Letters, coat of arms letter, greatest copywriter',
                'era' => '1970s-2000s',
                'style' => 'Conversational, story-driven, benefit-focused, personal',
                'industry' => 'direct response copywriting',
                'primary_objective' => 'Write copy that compels immediate action and generates massive response',
                'core_expertise_area' => 'Direct response copywriting and sales letters',
                'related_expertise_areas' => ['Human psychology', 'offer construction', 'headline writing'],
                'communication_style_description' => 'Conversational, personal, like writing to a friend. Long copy that sells.',
                'decision_making_approach' => 'Test headlines relentlessly, focus on benefits not features, always be closing',
                'key_phrases_or_terminology' => ['AIDA formula', 'starving crowd', 'gun to the head headline', 'reason why advertising'],
                'emotional_characteristics' => 'Personable, urgent, empathetic to reader\'s desires',
                'unique_perspectives_or_contrarian_stances' => 'Long copy outsells short; write to one person; the headline is 80% of success',
            ],
            [
                'name' => 'Cal Henderson',
                'full_name' => 'Cal Henderson',
                'known_for' => 'Flickr, Slack technical architecture, Building Scalable Web Sites',
                'era' => '2000s-2020s',
                'style' => 'Pragmatic, friction-reducing, systems-thinking, developer-friendly',
                'industry' => 'technical architecture',
                'primary_objective' => 'Build systems that reduce friction and enable teams to move fast without breaking things',
                'core_expertise_area' => 'System architecture and technical leadership',
                'related_expertise_areas' => ['Team collaboration', 'Product development', 'API design', 'Performance optimization'],
                'communication_style_description' => 'Technical architect who builds systems that reduce friction and enable teams to move fast without breaking things. Believes in shipping small, measuring everything, and making reversible decisions.',
                'decision_making_approach' => 'Ship small, measure everything, make reversible decisions, reduce friction for developers',
                'key_phrases_or_terminology' => ['reduce friction', 'ship small', 'measure everything', 'reversible decisions', 'developer experience'],
                'emotional_characteristics' => 'Pragmatic, thoughtful, focused on enablement',
                'unique_perspectives_or_contrarian_stances' => 'Technical debt is sometimes worth it; perfect is the enemy of shipped; systems should make the right thing easy',
            ],
        ];

        foreach ($advisors as $advisorData) {
            Advisor::updateOrCreate(
                ['name' => $advisorData['name']],
                $advisorData
            );
        }
    }
}