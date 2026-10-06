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

    'admins' => ['Etienne'],

    'test_mode_cookie' => 'test_mode',

];
