<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Advisor Generation Configuration - Two-Stage Implementation
    |--------------------------------------------------------------------------
    |
    | Configuration for Stage 1 (Standalone) and Stage 2 (PlayerContext).
    | Stage 3 (Council) configuration will be added in future iterations.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Stage 1: Standalone Advisor Configuration
    |--------------------------------------------------------------------------
    |
    | Core PK generation improvements with enforced specificity and voice.
    |
    */
    
    'stage1' => [
        // Optimized model configuration for quality + speed
        'model' => env('ADVISOR_STAGE1_MODEL', 'gpt-4o'),  // Using gpt-4o for better quality
        'temperature' => 0.4,  // Lower temperature for consistency
        'max_tokens' => 4000,  // Safe limit for GPT-4 models
        
        // Prompt engineering settings
        'specificity' => [
            'enforce_real_companies' => true,  // Require real company names
            'enforce_exact_metrics' => true,   // Require specific percentages
            'enforce_campaign_dates' => true,  // Require actual dates
            'max_placeholder_tolerance' => 0,  // Zero tolerance for placeholders
        ],
        
        // Voice calibration
        'voice' => [
            'enforce_first_person' => true,    // Require "I", "my", "I've"
            'max_sentence_length' => 15,       // Average words per sentence
            'min_contrarian_positions' => 2,   // Minimum contrarian stances
            'voice_consistency_threshold' => 0.8,
        ],
        
        // Quality thresholds
        'quality' => [
            'minimum_acceptable_score' => 80,  // Don't accept below this
            'target_score' => 85,              // Target quality level
            'max_generation_attempts' => 3,    // Retry up to 3 times
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Stage 2: PlayerContext Integration Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for personalizing advisors with player context.
    | Primary focus on PI-level, secondary on PK-level.
    |
    */
    
    'stage2' => [
        // Models for personalization (lighter weight for speed)
        'pi_model' => env('ADVISOR_STAGE2_PI_MODEL', 'gpt-4o-mini'),
        'pk_model' => env('ADVISOR_STAGE2_PK_MODEL', 'gpt-4o-mini'),
        
        // PI-level personalization (PRIMARY)
        'pi_personalization' => [
            'adapt_communication_style' => true,
            'customize_response_format' => true,
            'inject_player_context' => true,
            'personalize_examples' => true,
        ],
        
        // PK-level personalization (SECONDARY)
        'pk_personalization' => [
            'filter_by_relevance' => true,     // Don't remove, just reorder
            'emphasize_industry' => true,      // Highlight relevant examples
            'maintain_all_content' => true,    // Never remove expertise
            'add_relevance_notes' => true,     // Add context connections
        ],
        
        // Context integration preferences
        'context_injection' => [
            'include_in_pi' => true,
            'include_in_pk' => false,  // Only when explicitly requested
            'max_context_length' => 500,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for exporting advisors to ChatGPT.
    |
    */
    
    'export' => [
        // Format options
        'formats' => [
            'full' => [
                'include_pi' => true,
                'include_pk' => true,
                'max_size' => 100000,  // ~25k tokens
            ],
            'condensed' => [
                'include_essential_only' => true,
                'max_size' => 60000,   // ~15k tokens
            ],
        ],
        
        // ChatGPT compatibility
        'chatgpt' => [
            'optimize_for_gpt4' => true,
            'include_setup_instructions' => true,
            'format_as_markdown' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Quality Framework Configuration
    |--------------------------------------------------------------------------
    |
    | Simple quality measurement for external deployment.
    |
    */
    
    'quality' => [
        // Thresholds
        'thresholds' => [
            'minimum_acceptable' => 60,
            'target' => 80,
            'excellent' => 85,
            'alert_below' => 50,
        ],
        
        // Scoring weights
        'weights' => [
            'pi_weight' => 0.4,
            'pk_weight' => 0.6,  // PK weighted higher based on research
        ],
        
        // Periodic sampling
        'sampling' => [
            'enabled' => true,
            'frequency' => 'weekly',
            'sample_size' => 3,
            'advisor_types' => ['strategic', 'contrarian', 'analytical'],
        ],
        
        // Alerts
        'alerts' => [
            'enabled' => true,
            'regression_threshold' => 10,  // Alert if quality drops by 10%
            'channels' => ['log', 'cache'],
        ],
    ],


];