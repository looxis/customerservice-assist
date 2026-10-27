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
        // Seconds for loading one attachment (PROJ-60); attachments are larger than the JSON answers.
        'attachment_timeout' => 30,
        // Seconds for sending a reply with attachments: Zammad decodes and stores them before it answers.
        'reply_attachment_timeout' => 120,
        'timezone' => 'Europe/Berlin',
        // Group of new tickets that carry a return label (PROJ-64): the group "allgemeine Kunden".
        'return_group' => env('ZAMMAD_RETURN_GROUP', 'allg. Kunden'),

        // Our own signatures without Zammad's signature marker (e.g. on Amazon),
        // hidden at the end of our messages. Line breaks and case do not matter.
        'signatures' => [
            "Freundliche Grüße\nLOOXIS Kundenservice",
        ],

        // Clickable links in mails are removed. Links to these hosts (and their
        // subdomains) stay visible as plain text. These four lists are the defaults of the
        // page "Einstellungen > Mail-Anzeige" (PROJ-27), where admins change them.
        'allowed_link_hosts' => ['looxis.de', 'looxis.com', 'dhl.de'],

        // Links to these hosts stay clickable (https only). "*" stands for a
        // country ending such as .de, .it or .co.uk.
        'clickable_link_hosts' => ['sellercentral.amazon.*'],

        // From the first of these texts on, the rest of a mail is boilerplate
        // and hidden (case and line breaks do not matter).
        'footers' => [
            'Dieser Service wird ausschließlich für die Kommunikation mit Käufern angeboten',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | EOCS (PROJ-7)
    |--------------------------------------------------------------------------
    |
    | Read-only access to orders (docs/orders-api.md), only from the internal
    | network. The token contains "|" and must be quoted in .env.
    |
    */

    'eocs' => [
        'url' => env('EOCS_URL'),
        'token' => env('EOCS_TOKEN'),
        'timeout' => env('EOCS_TIMEOUT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | ProductForge / LOOXIS Content Studio (PROJ-38)
    |--------------------------------------------------------------------------
    |
    | Read-only product information API v1 (docs/productforge-product-api.md).
    | "url" is the host root; /api/v1 is appended. The bearer token is only
    | used server-side. "order_lookup" switches the automatic assignment of
    | order positions (EOCS item_id = ProductForge SKU) on; products not yet
    | maintained in ProductForge simply stay unknown. "timeout" is in seconds
    | (capped at 30).
    |
    */

    'productforge' => [
        'url' => env('PRODUCTFORGE_URL'),
        'token' => env('PRODUCTFORGE_TOKEN'),
        'timeout' => env('PRODUCTFORGE_TIMEOUT', 10),
        'retries' => env('PRODUCTFORGE_RETRIES', 2),
        'order_lookup' => env('PRODUCTFORGE_ORDER_LOOKUP', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | DHL Parcel DE Returns (PROJ-64)
    |--------------------------------------------------------------------------
    |
    | Return labels over the Returns API v1 (OAuth2 password grant of a business
    | customer system user). The defaults point to the sandbox; production URLs:
    | https://api-eu.dhl.com/parcel/de/shipping/returns/v1 and
    | https://api-eu.dhl.com/parcel/de/account/auth/ropc/v1/token. "receiver_id"
    | is the return receiver set up in the business customer portal (see
    | `dhl:check`). Label files are deleted after "retention_days"; the shipment
    | numbers stay. "timeout" is in seconds.
    |
    */

    'dhl' => [
        'url' => env('DHL_URL', 'https://api-sandbox.dhl.com/parcel/de/shipping/returns/v1'),
        'token_url' => env('DHL_TOKEN_URL', 'https://api-sandbox.dhl.com/parcel/de/account/auth/ropc/v1/token'),
        'client_id' => env('DHL_CLIENT_ID'),
        'client_secret' => env('DHL_CLIENT_SECRET'),
        'username' => env('DHL_USERNAME'),
        'password' => env('DHL_PASSWORD'),
        'receiver_id' => env('DHL_RECEIVER_ID'),
        'timeout' => env('DHL_TIMEOUT', 15),
        'retention_days' => env('DHL_RETENTION_DAYS', 30),
    ],

];
