<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Language Model
    |--------------------------------------------------------------------------
    |
    | Provider and model per call. Changeable in .env until the settings page
    | (PROJ-31) exists. Provider keys live in config/ai.php.
    |
    */

    'provider' => env('ANALYSIS_PROVIDER', 'openai'),

    'models' => [
        'analysis' => env('ANALYSIS_MODEL', 'gpt-5.5'),
        'summary' => env('SUMMARY_MODEL', 'gpt-5.4-mini'),
    ],

    'timeout' => (int) env('ANALYSIS_TIMEOUT', 90),

    /*
    |--------------------------------------------------------------------------
    | Prompts
    |--------------------------------------------------------------------------
    |
    | Versioned files in the repository. Business rules never go here; they
    | live in the knowledge base.
    |
    */

    'prompts' => [
        'analysis' => resource_path('prompts/analysis.md'),
        'summary' => resource_path('prompts/summary.md'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ticket Context
    |--------------------------------------------------------------------------
    |
    | Above these limits the earlier thread is long enough that a summary is
    | suggested first (5 messages, or 3 messages with more than 6,000
    | characters of cleaned text).
    |
    */

    'summary_threshold' => [
        'messages' => 5,
        'long_messages' => 3,
        'characters' => 6000,
    ],

    'max_context_length' => 4000,

    /*
    |--------------------------------------------------------------------------
    | Temporary Storage
    |--------------------------------------------------------------------------
    |
    | Summaries and results are kept encrypted in the cache until the
    | analysis log (PROJ-11) stores them for good.
    |
    */

    'retention_days' => 7,

    // The customer group chosen for a Zammad customer or organization is
    // remembered for later tickets; it holds no case content.
    'customer_group_retention_days' => 365,

];
