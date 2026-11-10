<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ticket Tagging for Training Data (PROJ-78)
    |--------------------------------------------------------------------------
    |
    | The admin page "Ticket-Klassifikation" classifies the first customer message of every Zammad ticket and writes
    | the tag train:<category> (test:<category> for the test set). New incoming tickets are classified at once and
    | confirmed by the staff on the ticket page (PROJ-80). The values under "defaults" are the start values of the
    | settings on that page.
    |
    */

    'model' => env('LABELING_MODEL'),

    // Tickets of Zammad older than this are not looked at.
    'earliest' => env('LABELING_EARLIEST', '2019-01-01'),

    // A period of the retrieval is cut in halves above this many tickets (Zammad's search stops at 10,000).
    'window_limit' => 9000,

    'calls_per_minute' => (int) env('LABELING_CALLS_PER_MINUTE', 30),
    'zammad_per_minute' => (int) env('LABELING_ZAMMAD_PER_MINUTE', 120),

    // Months after the creation of a ticket in Zammad until its message text is emptied (labeling:purge).
    'retention_months' => 36,

    // New incoming tickets (PROJ-80): the share of the test set, and how old a ticket may be when CSA first sees it,
    // so that importing or reconciling old tickets classifies nothing.
    'intake_test_share' => 5,
    'intake_max_age_hours' => 24,

    // Longest message text sent to the model, in characters.
    'max_text_length' => 6000,

    'defaults' => [
        // Whether new incoming tickets are classified at once (PROJ-80).
        'intake' => true,
        'target' => 100,
        'min_cases' => 50,
        'min_rate' => 95,
        'sample_share' => 15,
        'confidence' => 0.85,
        'examples' => 8,
        'test_share' => 20,
        // Decided tickets of the test set per category; once reached, test tickets proposed for it leave the review.
        'test_target' => 50,
        'excluded_groups' => '',
        'excluded_senders' => 'non-rispondere@amazon.it, noreply@amazon.com, donotreply@amazon.com, atoz-guarantee-no-reply@amazon.com, mailer-daemon@eu-central-1.amazonses.com, do-not-reply@amazon.es, mail@wecreateyour.com, auto-communication@amazon.com',
    ],
];
