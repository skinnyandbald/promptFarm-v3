<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Anti-AI Style Guide Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for detecting and avoiding AI-generated language patterns.
    | Based on comprehensive style guide to maintain authentic human voice.
    |
    */

    'enabled' => env('STYLE_GUIDE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Forbidden Language Patterns
    |--------------------------------------------------------------------------
    |
    | Patterns that indicate artificial/robotic language to avoid.
    |
    */

    'forbidden_patterns' => [
        // Promotional language
        'cutting-edge',
        'state-of-the-art', 
        'next-level',
        'game-changing',
        'revolutionary',
        'innovative solutions',
        'best practices',
        'proven track record',
        'industry-leading',
        'world-class',

        // Editorial framing
        'it\'s worth noting',
        'importantly',
        'significantly', 
        'notably',
        'interestingly',
        'remarkably',
        'crucially',
        'essentially',
        'fundamentally',
        'ultimately',

        // Hedging language
        'arguably',
        'potentially',
        'seemingly',
        'appears to be',
        'tends to',
        'generally speaking',
        'in many cases',
        'oftentimes',
        'typically',
        'usually',

        // Corporate speak
        'leverage',
        'utilize',
        'optimize',
        'streamline',
        'synergy',
        'paradigm',
        'ecosystem',
        'scalable',
        'actionable insights',
        'value proposition',

        // AI assistant phrases
        'I\'d be happy to',
        'I\'m here to help',
        'feel free to',
        'don\'t hesitate to',
        'I understand',
        'I appreciate',
        'thank you for',
        'as an AI',
        'I\'m an AI',
    ],

    /*
    |--------------------------------------------------------------------------
    | Warning Phrases
    |--------------------------------------------------------------------------
    |
    | Phrases that should trigger warnings but aren't forbidden.
    |
    */

    'warning_patterns' => [
        // Overused transitions
        'furthermore',
        'moreover',
        'additionally',
        'subsequently',
        'consequently',
        'nevertheless',
        'nonetheless',
        'however',
        'therefore',
        'thus',

        // Filler phrases
        'it goes without saying',
        'needless to say',
        'obviously',
        'of course',
        'clearly',
        'without a doubt',
        'undoubtedly',
        'certainly',
        'definitely',
        'absolutely',

        // Generic advice
        'consider implementing',
        'you might want to',
        'it would be beneficial',
        'I would recommend',
        'you should consider',
        'it\'s important to',
        'make sure to',
        'don\'t forget to',
        'remember to',
        'be sure to',
    ],

    /*
    |--------------------------------------------------------------------------
    | Structure Constraints  
    |--------------------------------------------------------------------------
    |
    | Rules about response structure and formatting.
    |
    */

    'structure_rules' => [
        'avoid_numbered_lists' => true,
        'avoid_bullet_points' => true,
        'avoid_section_headers' => true,
        'max_paragraph_length' => 150, // words
        'prefer_short_sentences' => true,
        'max_sentence_length' => 25, // words
    ],

    /*
    |--------------------------------------------------------------------------
    | Voice Authenticity Requirements
    |--------------------------------------------------------------------------
    |
    | Requirements for maintaining authentic voice.
    |
    */

    'authenticity_rules' => [
        'require_contractions' => true,
        'allow_incomplete_sentences' => true,
        'encourage_personal_pronouns' => true,
        'require_specific_examples' => true,
        'avoid_abstract_concepts' => true,
        'prefer_active_voice' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Scoring Weights
    |--------------------------------------------------------------------------
    |
    | How heavily to weight different violations.
    |
    */

    'scoring' => [
        'forbidden_pattern_penalty' => -10,
        'warning_pattern_penalty' => -3,
        'structure_violation_penalty' => -5,
        'authenticity_bonus' => +5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | How the style guide integrates with other systems.
    |
    */

    'integration' => [
        'inject_into_system_prompt' => true,
        'validate_responses' => true,
        'provide_feedback' => true,
        'auto_reject_high_violations' => false,
        'violation_threshold' => 30, // Max penalty before rejection
    ],

];