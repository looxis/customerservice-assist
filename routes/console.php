<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// PROJ-11: empty customer content after the retention period.
Schedule::command('analysis:purge')->dailyAt('03:15');

// PROJ-60: delete cached ticket attachments after the retention period.
Schedule::command('attachments:purge')->dailyAt('03:30');

// PROJ-64: delete the PDF files of DHL return labels after the retention period.
Schedule::command('return-labels:purge')->dailyAt('03:45');
