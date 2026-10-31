<?php

use App\Http\Controllers\ZammadWebhookController;
use Illuminate\Support\Facades\Route;

// PROJ-66: Zammad reports ticket changes here, secured by the signature in ZammadWebhookRequest.
Route::post('/webhooks/zammad', ZammadWebhookController::class)
    ->middleware('throttle:600,1')
    ->name('webhooks.zammad');
