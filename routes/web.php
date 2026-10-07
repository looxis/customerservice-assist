<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\KnowledgeController;
use App\Http\Controllers\KnowledgeGapController;
use App\Http\Controllers\StaffSelectionController;
use App\Http\Controllers\TestModeController;
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
Route::post('/testmodus', TestModeController::class)->name('test-mode.switch');

Route::get('/wissensluecken', [KnowledgeGapController::class, 'index'])->name('knowledge-gaps.index');
Route::patch('/wissensluecken/{gap}', [KnowledgeGapController::class, 'update'])->middleware('staff.selected')->whereNumber('gap')->name('knowledge-gaps.update');

Route::get('/tickets', [TicketController::class, 'lookup'])->name('tickets.lookup');
Route::get('/tickets/{number}', [TicketController::class, 'show'])
    ->where('number', '[0-9]{1,20}')
    ->name('tickets.show');
Route::get('/tickets/{number}/bestellungen', [TicketController::class, 'addOrder'])
    ->where('number', '[0-9]{1,20}')
    ->name('tickets.orders.add');

Route::middleware('staff.selected')->whereNumber('number')->group(function (): void {
    Route::post('/tickets/{number}/analyse', [AnalysisController::class, 'analyze'])->middleware('throttle:language-model')->name('tickets.analysis.run');
    Route::post('/tickets/{number}/zusammenfassung', [AnalysisController::class, 'createSummary'])->middleware('throttle:language-model')->name('tickets.summary.create');
    Route::put('/tickets/{number}/zusammenfassung', [AnalysisController::class, 'updateSummary'])->name('tickets.summary.update');
    Route::delete('/tickets/{number}/analysen', [AnalysisController::class, 'destroyAll'])->name('tickets.analyses.destroy');
    Route::put('/tickets/{number}/analyse/{analysis}/feedback', FeedbackController::class)->whereUuid('analysis')->name('tickets.analysis.feedback');
    Route::post('/tickets/{number}/analyse/{analysis}/wissensluecken', [KnowledgeGapController::class, 'store'])->whereUuid('analysis')->name('tickets.analysis.gaps.store');
    Route::put('/tickets/{number}/analyse/{analysis}/entwurf', [AnalysisController::class, 'updateReply'])->whereUuid('analysis')->name('tickets.analysis.reply');
});

if (app()->isLocal()) {
    Route::view('/styleguide', 'styleguide')->name('styleguide');
    Route::get('/styleguide/ticket', fn () => view('tickets.show', [
        'ticket' => SampleTicket::make(min(40, max(0, (int) request('antworten', 6)))),
        'number' => '2137942',
    ]))->name('styleguide.ticket');
}
