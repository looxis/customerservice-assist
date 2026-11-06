<?php

namespace App\Console\Commands;

use App\Todo\TrackingNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('todos:check-tracking {--slot= : Check time to run for (HH:MM), default: the one belonging to now}')]
#[Description('Look in EOCS for the tracking numbers of the ToDos and send the prepared replies (PROJ-77)')]
class CheckTrackingNumbers extends Command
{
    public function handle(TrackingNotifier $notifier): int
    {
        $slot = $this->option('slot');
        $figures = $notifier->run(CarbonImmutable::now(), is_string($slot) && $slot !== '' ? $slot : null);

        $this->info("{$figures['checked']} ToDos geprüft, {$figures['sent']} Antworten gesendet.");

        return self::SUCCESS;
    }
}
