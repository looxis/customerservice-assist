<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Staff Names
    |--------------------------------------------------------------------------
    |
    | The fixed list an employee picks their name from, once per browser.
    | Shown alphabetically. Replaced by the user database with PROJ-15.
    |
    */

    'names' => ['Cara', 'Etienne', 'Johannes', 'Kerstin', 'Nele', 'Thomas'],

    /*
    |--------------------------------------------------------------------------
    | Full Names (PROJ-19)
    |--------------------------------------------------------------------------
    |
    | The dropdown shows the first name; the signature of a sent reply uses the
    | full name.
    |
    */

    'full_names' => [
        'Cara' => 'Cara Barkhausen',
        'Etienne' => 'Etienne Renaud',
        'Johannes' => 'Johannes Renaud',
        'Kerstin' => 'Kerstin Schmeckpeper',
        'Nele' => 'Nele Gorka',
        'Thomas' => 'Thomas Junski',
    ],

    /*
    |--------------------------------------------------------------------------
    | Zammad Tokens (PROJ-19)
    |--------------------------------------------------------------------------
    |
    | Each employee's personal Zammad access token (profile > token access, right
    | "ticket.agent"), so that a reply is sent in their own name. Without a token
    | there is no "Antwort versenden", only "Kopieren". Replaced by the user
    | administration (SSO) later.
    |
    */

    'zammad_tokens' => [
        'Cara' => env('ZAMMAD_TOKEN_CARA'),
        'Etienne' => env('ZAMMAD_TOKEN_ETIENNE'),
        'Johannes' => env('ZAMMAD_TOKEN_JOHANNES'),
        'Kerstin' => env('ZAMMAD_TOKEN_KERSTIN'),
        'Nele' => env('ZAMMAD_TOKEN_NELE'),
        'Thomas' => env('ZAMMAD_TOKEN_THOMAS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Remembered Choice
    |--------------------------------------------------------------------------
    |
    | The chosen name lives in an encrypted cookie for about five years, so
    | the server knows it on every request.
    |
    */

    'cookie' => 'staff_name',

    'cookie_minutes' => 60 * 24 * 365 * 5,

    /*
    |--------------------------------------------------------------------------
    | Admins
    |--------------------------------------------------------------------------
    |
    | Names that may use admin tools such as the test mode (PROJ-32). Without
    | a login this is no protection, only a tidy interface; PROJ-15 replaces
    | it with a real role. The test mode is switched on per browser.
    |
    */

    'admins' => ['Etienne', 'Johannes', 'Thomas'],

    'test_mode_cookie' => 'test_mode',

    // Advanced mode (PROJ-42): a switch per browser for everybody, not only for admins.
    'advanced_mode_cookie' => 'advanced_mode',

];
