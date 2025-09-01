<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Advisor Generation Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration settings for the advisor generation
    | system, including queue settings, timeouts, and retry policies.
    |
    */

    'queue' => [
        'name' => 'advisor-generation',
        'timeout' => 600,
        'tries' => 3,
        'backoff' => [60, 120, 300],
        'memory' => 512,
    ],

    'polling' => [
        'interval' => 5,
        'max_wait' => 3600,
    ],

    'generation' => [
        'pi_timeout' => 300,
        'pk_timeout' => 300,
        'quality_check_timeout' => 60,
    ],

    'cleanup' => [
        'completed_after_days' => 30,
        'failed_after_days' => 7,
    ],

    'monitoring' => [
        'alert_on_failure' => true,
        'alert_on_long_wait' => true,
        'long_wait_threshold' => 120,
    ],

];