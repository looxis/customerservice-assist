<?php

use App\Http\Controllers\KnowledgeController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'tickets.analyze')->name('tickets.analyze');

Route::get('/knowledge', [KnowledgeController::class, 'index'])->name('knowledge.index');
Route::get('/knowledge/dokument/{path}', [KnowledgeController::class, 'show'])
    ->where('path', '.*')
    ->name('knowledge.show');

if (app()->isLocal()) {
    Route::view('/styleguide', 'styleguide')->name('styleguide');
}
