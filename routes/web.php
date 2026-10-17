<?php

use App\Analysis\QuestionNumber;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\AdvancedModeController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\AnalysisOverviewController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\KnowledgeController;
use App\Http\Controllers\KnowledgeGapController;
use App\Http\Controllers\ProcedureController;
use App\Http\Controllers\ProductSearchController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StaffSelectionController;
use App\Http\Controllers\TestModeController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketOverviewController;
use App\Http\Controllers\TranslationController;
use App\Zammad\SampleTicket;
use Illuminate\Support\Facades\Route;

Route::view('/', 'tickets.analyze')->name('tickets.analyze');

Route::get('/knowledge', [KnowledgeController::class, 'index'])->name('knowledge.index');
Route::post('/knowledge/dokument/{path}/status', [KnowledgeController::class, 'changeStatus'])
    ->where('path', '.*')
    ->name('knowledge.status');
Route::get('/knowledge/dokument/{path}', [KnowledgeController::class, 'show'])
    ->where('path', '.*')
    ->name('knowledge.show');

Route::get('/ueber-die-app', AboutController::class)->name('about');

Route::get('/einstellungen', [SettingsController::class, 'index'])->name('settings.index');
Route::get('/einstellungen/mail-anzeige', [SettingsController::class, 'mail'])->name('settings.mail');
Route::get('/einstellungen/prompts-und-modell', [SettingsController::class, 'prompts'])->name('settings.prompts');
Route::get('/einstellungen/erkennungsregeln', [SettingsController::class, 'detection'])->name('settings.detection');
Route::middleware('staff.selected')->group(function (): void {
    Route::put('/einstellungen/mail-anzeige', [SettingsController::class, 'updateMail'])->name('settings.mail.update');
    Route::put('/einstellungen/prompts-und-modell', [SettingsController::class, 'updatePrompts'])->name('settings.prompts.update');
    Route::put('/einstellungen/erkennungsregeln', [SettingsController::class, 'updateDetection'])->name('settings.detection.update');
    Route::match(['put', 'post'], '/einstellungen/erkennungsregeln/ausprobieren', [SettingsController::class, 'tryDetection'])->middleware('throttle:30,1')->name('settings.detection.try');
    Route::match(['put', 'post'], '/einstellungen/mail-anzeige/ausprobieren', [SettingsController::class, 'tryMail'])->middleware('throttle:20,1')->name('settings.mail.try');
    Route::post('/einstellungen/{group}/standard', [SettingsController::class, 'reset'])->where('group', '[a-z_]+')->name('settings.reset');
    Route::post('/einstellungen/{group}/version/{version}', [SettingsController::class, 'restore'])->where('group', '[a-z_]+')->whereNumber('version')->name('settings.restore');
});

Route::get('/produktsuche', ProductSearchController::class)->middleware(['staff.selected', 'throttle:60,1'])->name('products.search');

Route::post('/name', StaffSelectionController::class)->name('staff.select');
Route::post('/testmodus', TestModeController::class)->name('test-mode.switch');
Route::post('/erweitert', AdvancedModeController::class)->name('advanced-mode.switch');

Route::get('/ablaeufe/{id}', [ProcedureController::class, 'show'])->where('id', '[A-Z]+-[0-9]{3}')->name('procedures.show');

Route::get('/wissensluecken', [KnowledgeGapController::class, 'index'])->name('knowledge-gaps.index');
Route::patch('/wissensluecken/{gap}', [KnowledgeGapController::class, 'update'])->middleware('staff.selected')->whereNumber('gap')->name('knowledge-gaps.update');

Route::get('/analysen', [AnalysisOverviewController::class, 'index'])->name('analysis-overview.index');
Route::get('/ticket-uebersicht', [TicketOverviewController::class, 'index'])->name('ticket-overview.index');

Route::get('/frage', [QuestionController::class, 'create'])->name('questions.create');
Route::get('/frage/{number}', [QuestionController::class, 'show'])->where('number', 'F[0-9]{8}')->name('questions.show');
Route::middleware('staff.selected')->group(function (): void {
    Route::post('/frage', [QuestionController::class, 'store'])->middleware('throttle:language-model')->name('questions.store');
    Route::post('/frage/{number}/nachfrage', [QuestionController::class, 'followUp'])->where('number', 'F[0-9]{8}')->middleware('throttle:language-model')->name('questions.follow-up');
});

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
    Route::post('/tickets/{number}/uebersetzung', [TranslationController::class, 'store'])->middleware('throttle:language-model')->name('tickets.translation.store');
});

// Routes that work with a ticket number and with the number of a question (PROJ-40).
Route::middleware('staff.selected')->where(['number' => QuestionNumber::TICKET_OR_QUESTION])->group(function (): void {
    Route::post('/tickets/{number}/analyse/{analysis}/gegenlesen', [TranslationController::class, 'backTranslate'])->middleware('throttle:language-model')->whereUuid('analysis')->name('tickets.analysis.back-translation');
    Route::put('/tickets/{number}/analyse/{analysis}/feedback', FeedbackController::class)->whereUuid('analysis')->name('tickets.analysis.feedback');
    Route::post('/tickets/{number}/analyse/{analysis}/wissensluecken', [KnowledgeGapController::class, 'store'])->whereUuid('analysis')->name('tickets.analysis.gaps.store');
    Route::put('/tickets/{number}/analyse/{analysis}/entwurf', [AnalysisController::class, 'updateReply'])->whereUuid('analysis')->name('tickets.analysis.reply');
    Route::post('/tickets/{number}/analyse/{analysis}/regelpruefung', [AnalysisController::class, 'acknowledgeRuleCheck'])->whereUuid('analysis')->name('tickets.analysis.rule-check');
    Route::post('/tickets/{number}/analyse/{analysis}/kopiert', [AnalysisController::class, 'markCopied'])->whereUuid('analysis')->name('tickets.analysis.copied');
});

if (app()->isLocal()) {
    Route::view('/styleguide', 'styleguide')->name('styleguide');
    Route::get('/styleguide/ticket', fn () => view('tickets.show', [
        'ticket' => SampleTicket::make(min(40, max(0, (int) request('antworten', 6)))),
        'number' => '2137942',
    ]))->name('styleguide.ticket');
}
