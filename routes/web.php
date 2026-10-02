<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'tickets.analyze')->name('tickets.analyze');

if (app()->isLocal()) {
    Route::view('/styleguide', 'styleguide')->name('styleguide');
}
