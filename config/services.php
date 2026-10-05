<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    /*
    |--------------------------------------------------------------------------
    | Zammad (PROJ-6)
    |--------------------------------------------------------------------------
    |
    | Read-only access with the app's own Zammad user and token. Times are
    | shown in the local time zone of the customer service team.
    |
    */

    'zammad' => [
        'url' => env('ZAMMAD_URL'),
        'token' => env('ZAMMAD_TOKEN'),
        'timeout' => env('ZAMMAD_TIMEOUT', 10),
        'timezone' => 'Europe/Berlin',

        // Our own signatures without Zammad's signature marker (e.g. on Amazon),
        // hidden at the end of our messages. Line breaks and case do not matter.
        'signatures' => [
            "Freundliche Grüße\nLOOXIS Kundenservice",
        ],
    ],

];
