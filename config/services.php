<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o'),
        'pk_model' => env('PK_GENERATION_MODEL', 'gpt-4o'),
        'pi_enhancement_model' => env('PI_ENHANCEMENT_MODEL', 'gpt-4o-mini'),
        'max_tokens' => env('OPENAI_MAX_TOKENS', 8000),
        'timeout' => env('OPENAI_TIMEOUT', 300),
        'temperature' => env('OPENAI_TEMPERATURE', 0.7),
        'organization' => env('OPENAI_ORGANIZATION'),
    ],
    
    'xai' => [
        'api_key' => env('XAI_API_KEY'),
    ],
    
    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
    ],

    'advisor' => [
        'pi_quality_threshold' => env('ADVISOR_PI_QUALITY_THRESHOLD', 75),
        'pk_quality_threshold' => env('ADVISOR_PK_QUALITY_THRESHOLD', 80),
        'storage_disk' => env('ADVISOR_STORAGE_DISK', 'advisors'),
        'enable_quality_validation' => env('ADVISOR_ENABLE_QUALITY_VALIDATION', true),
        'fail_on_low_quality' => env('ADVISOR_FAIL_ON_LOW_QUALITY', false),
    ],

];
