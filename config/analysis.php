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

    // Customer content of analyses, summaries and remembered choices is
    // emptied after this many months; the figures of an analysis stay.
    'content_retention_months' => 12,

    // Feedback levels (PROJ-12). "Usable" counts towards the success figure
    // of the PRD (unchanged plus slightly adapted, at least 70 %).
    'feedback_levels' => [
        'unchanged' => 'unverändert nutzbar',
        'slight' => 'leicht angepasst',
        'major' => 'stark angepasst',
        'discarded' => 'verworfen',
    ],

    'feedback_usable_levels' => ['unchanged', 'slight'],

    'feedback_success_days' => 28,

    // Longest reply draft that is saved after editing (PROJ-10).
    'max_reply_length' => 20_000,

    // Language model calls (analysis and summary) per name and browser
    // address per minute; protects against costs from repeated sending.
    'calls_per_minute' => 6,

    // Upper bound for the ticket part sent in one call (about 100,000
    // tokens). A longer full thread must go through the summary; for the
    // summary itself the oldest messages are shortened, never the newest.
    'max_input_characters' => 400_000,

    // The customer group chosen for a Zammad customer or organization is
    // remembered for later tickets; it holds no case content.
    'customer_group_retention_days' => 365,

];
