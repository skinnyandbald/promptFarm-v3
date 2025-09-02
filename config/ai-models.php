<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Model Configuration
    |--------------------------------------------------------------------------
    |
    | Centralized configuration for all AI models used in the application.
    | Models are organized by purpose and provider for clarity.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Primary Models (Via OpenRouter)
    |--------------------------------------------------------------------------
    |
    | Using Grok-3 as primary model for optimal speed/quality balance.
    | Grok-3 is 10-100x faster than Grok-4 with similar quality.
    |
    */

    'primary' => [
        'provider' => 'openrouter',
        'model' => env('AI_PRIMARY_MODEL', 'x-ai/grok-3'),
        'description' => 'Primary model for advisor generation (PI enhancement, PK generation)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Purpose-Specific Models
    |--------------------------------------------------------------------------
    |
    | Models optimized for specific tasks. Can override primary model.
    |
    */

    'purposes' => [
        // Advisor PI enhancement (lightweight, fast)
        'pi_enhancement' => env('AI_PI_ENHANCEMENT_MODEL', env('AI_PRIMARY_MODEL', 'x-ai/grok-3')),

        // Advisor PK generation (needs good reasoning)
        'pk_generation' => env('AI_PK_GENERATION_MODEL', env('AI_PRIMARY_MODEL', 'x-ai/grok-3')),

        // Player context personalization (can be lighter weight)
        'player_context' => env('AI_PLAYER_CONTEXT_MODEL', 'gpt-4o-mini'),

        // Fact-checking and position research (low temperature, high accuracy)
        'fact_checking' => env('AI_FACT_CHECKING_MODEL', 'x-ai/grok-3'),

        // General text generation fallback
        'fallback' => env('AI_FALLBACK_MODEL', 'gpt-4o-mini'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider-Specific Settings
    |--------------------------------------------------------------------------
    |
    | Temperature and token settings per use case.
    |
    */

    'settings' => [
        'pi_enhancement' => [
            'temperature' => 0.3,  // Lower for consistency
            'max_tokens' => 5000,
        ],
        'pk_generation' => [
            'temperature' => 0.8,  // Higher for creativity
            'max_tokens' => 4000,
        ],
        'player_context' => [
            'temperature' => 0.5,
            'max_tokens' => 2000,
        ],
        'fact_checking' => [
            'temperature' => 0.1,  // Very low for maximum accuracy
            'max_tokens' => 1500,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy Model Mapping (For Backward Compatibility)
    |--------------------------------------------------------------------------
    |
    | Maps old config keys to new system. Will be removed in v4.
    |
    */

    'legacy_mapping' => [
        'services.openai.model' => 'purposes.fallback',
        'advisors.stage1.model' => 'purposes.pk_generation',
        'advisors.stage2.pi_model' => 'purposes.player_context',
        'advisors.stage2.pk_model' => 'purposes.player_context',
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Capabilities
    |--------------------------------------------------------------------------
    |
    | Track which models support which features.
    |
    */

    'capabilities' => [
        'x-ai/grok-3' => [
            'json_mode' => true,
            'system_messages' => true,
            'max_context' => 100000,
            'supports_reasoning' => true,
        ],
        'x-ai/grok-4' => [
            'json_mode' => true,
            'system_messages' => true,
            'max_context' => 128000,
            'supports_reasoning' => true,
        ],
        'gpt-4o-mini' => [
            'json_mode' => true,
            'system_messages' => true,
            'max_context' => 128000,
            'supports_reasoning' => false,
        ],
        'gpt-4o' => [
            'json_mode' => true,
            'system_messages' => true,
            'max_context' => 128000,
            'supports_reasoning' => false,
        ],
    ],

];
