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

];
