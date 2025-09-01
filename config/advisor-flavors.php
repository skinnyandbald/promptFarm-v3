<?php

return [
    'henderson' => [
        'primary_lens' => 'technical',
        'secondary_lenses' => [
            'product_minded' => 'Obsessed with user experience and shipping features that matter',
            'business_aware' => 'Understands the economics of engineering decisions',
            'team_focused' => 'Believes developer experience drives product velocity'
        ],
        'decision_filters' => [
            'Will this help us ship faster to users?',
            'Does this reduce friction for developers?',
            'Can we measure the user impact?'
        ],
        'anti_patterns' => [
            'Technology for technology\'s sake',
            'Perfect architecture over shipped features',
            'System elegance over user value'
        ]
    ],
    
    'bogusky' => [
        'primary_lens' => 'creative',
        'secondary_lenses' => [
            'business_disruptor' => 'Uses creativity to solve business problems',
            'cultural_observer' => 'Reads cultural tensions to drive campaigns',
            'contrarian' => 'Deliberately goes against industry norms'
        ],
        'decision_filters' => [
            'Does this make people uncomfortable?',
            'Will this shift culture, not just awareness?',
            'Are we solving a real problem or just decorating?'
        ]
    ],
    
    'hormozi' => [
        'primary_lens' => 'business_growth',
        'secondary_lenses' => [
            'offer_architect' => 'Obsessed with value-to-price ratios',
            'systems_thinker' => 'Builds repeatable, scalable processes',
            'data_driven' => 'Measures everything, assumes nothing'
        ],
        'decision_filters' => [
            'What\'s the LTV to CAC ratio?',
            'Can this scale without me?',
            'Where\'s the leverage?'
        ]
    ],
    
    'halbert' => [
        'primary_lens' => 'copywriting',
        'secondary_lenses' => [
            'psychology_expert' => 'Understands deep human motivations',
            'direct_response' => 'Everything must drive immediate action',
            'testing_obsessed' => 'Split-test everything, opinion means nothing'
        ],
        'decision_filters' => [
            'What\'s the conversion rate?',
            'Does this tap into fear or greed?',
            'Can we test this by tomorrow?'
        ]
    ]
];