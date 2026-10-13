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
        'translation' => env('TRANSLATION_MODEL', env('SUMMARY_MODEL', 'gpt-5.4-mini')),
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
        'analysis-retrieval' => resource_path('prompts/analysis-retrieval.md'),
        'analysis-question' => resource_path('prompts/analysis-question.md'),
        'summary' => resource_path('prompts/summary.md'),
        'translation' => resource_path('prompts/translation.md'),
        'router' => resource_path('prompts/router.md'),
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

    // Question without a ticket (PROJ-40): longest text, and how many own questions the page lists.
    'max_question_length' => 6000,
    'question_history' => 10,

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

    // Translation of messages (PROJ-28): texts shorter than min_length are
    // not flagged as foreign; one call carries at most batch_characters.
    'translation' => [
        'min_length' => 20,
        'batch_characters' => 12_000,
    ],

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

    /*
    |--------------------------------------------------------------------------
    | Prompt Limits (PROJ-36)
    |--------------------------------------------------------------------------
    |
    | The whole API prompt must fit the model: context limit minus the reserve
    | for the answer, in characters (a cautious conversion, calibrated with the
    | token figures stored with every analysis). Calibration 2026-10-12 with 28
    | completed analyses of gpt-5.5: 3.93 to 4.26 characters per input token
    | (German text), answers (with reasoning) at most 3,509 tokens, about 14,000
    | characters. 300,000 characters therefore stay below about 76,000 tokens; the
    | answer reserve of 30,000 characters is about twice the largest answer seen.
    | The retrieval measures the
    | prompt without knowledge first and gives the knowledge what is left.
    |
    */

    'limits' => [
        'context_characters' => (int) env('ANALYSIS_CONTEXT_CHARACTERS', 300000),
        'answer_reserve_characters' => (int) env('ANALYSIS_ANSWER_RESERVE_CHARACTERS', 30000),
    ],

    /*
    | From this moment on it is recorded whether a reply draft was copied or
    | edited (PROJ-35). Earlier analyses show "unknown" in the overview (PROJ-14).
    */
    'reply_tracking_since' => '2026-10-12 00:00:00',

    'overview_periods' => [7 => '7 Tage', 28 => '28 Tage', 90 => '90 Tage', 365 => '12 Monate'],

    'overview_default_days' => 28,

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
