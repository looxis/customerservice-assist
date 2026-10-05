<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\KnowledgeController;
use App\Http\Controllers\StaffSelectionController;
use App\Http\Controllers\TicketController;
use App\Zammad\SampleTicket;
use Illuminate\Support\Facades\Route;

Route::view('/', 'tickets.analyze')->name('tickets.analyze');

Route::get('/knowledge', [KnowledgeController::class, 'index'])->name('knowledge.index');
Route::get('/knowledge/dokument/{path}', [KnowledgeController::class, 'show'])
    ->where('path', '.*')
    ->name('knowledge.show');

Route::get('/ueber-die-app', AboutController::class)->name('about');

Route::post('/name', StaffSelectionController::class)->name('staff.select');

Route::get('/tickets', [TicketController::class, 'lookup'])->name('tickets.lookup');
Route::get('/tickets/{number}', [TicketController::class, 'show'])
    ->where('number', '[0-9]{1,20}')
    ->name('tickets.show');
Route::get('/tickets/{number}/bestellungen', [TicketController::class, 'addOrder'])
    ->where('number', '[0-9]{1,20}')
    ->name('tickets.orders.add');

if (app()->isLocal()) {
    Route::view('/styleguide', 'styleguide')->name('styleguide');
    Route::get('/styleguide/ticket', fn () => view('tickets.show', [
        'ticket' => SampleTicket::make(min(40, max(0, (int) request('antworten', 6)))),
        'number' => '2137942',
    ]))->name('styleguide.ticket');
}
