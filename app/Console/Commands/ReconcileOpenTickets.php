<?php

namespace App\Console\Commands;

use App\Models\ZammadSyncRun;
use App\Zammad\TicketReconciler;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('zammad:reconcile-open {--all : Also re-read every open ticket with its thread}')]
#[Description('Compare all open Zammad tickets with the tracked tickets (PROJ-66)')]
class ReconcileOpenTickets extends Command
{
    public const string BULK_LOCK = 'zammad-bulk-sync';

    public const int BULK_LOCK_SECONDS = 3600;

    public function handle(TicketReconciler $reconciler): int
    {
        $lock = Cache::lock(self::BULK_LOCK, self::BULK_LOCK_SECONDS);

        if (! $lock->get()) {
            $this->info('Es läuft bereits ein Vollabgleich oder Import.');

            return self::SUCCESS;
        }

        try {
            $run = $reconciler->reconcileOpen(ZammadSyncRun::RECONCILE, (bool) $this->option('all'));
        } finally {
            $lock->release();
        }

        $this->info("{$run->tickets_seen} Tickets verarbeitet, {$run->tickets_failed} fehlgeschlagen.");

        return $run->status === ZammadSyncRun::SUCCEEDED ? self::SUCCESS : self::FAILURE;
    }
}
