<?php

namespace App\Console\Commands;

use App\Models\ZammadSyncRun;
use App\Zammad\TicketReconciler;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('zammad:import-tickets {--dry-run : Only show how many open tickets Zammad has}')]
#[Description('Take over all open Zammad tickets once; can be repeated (PROJ-66)')]
class ImportOpenTickets extends Command
{
    public function handle(TicketReconciler $reconciler, ZammadClient $zammad): int
    {
        if ($this->option('dry-run')) {
            try {
                $this->info("Offene Tickets in Zammad: {$zammad->openTicketsPage(1)['total']}");
            } catch (ZammadException $exception) {
                $this->error($exception->problem->message(''));

                return self::FAILURE;
            }

            return self::SUCCESS;
        }

        $lock = Cache::lock(ReconcileOpenTickets::BULK_LOCK, ReconcileOpenTickets::BULK_LOCK_SECONDS);

        if (! $lock->get()) {
            $this->info('Es läuft bereits ein Vollabgleich oder Import.');

            return self::SUCCESS;
        }

        try {
            $run = $reconciler->reconcileOpen(ZammadSyncRun::IMPORT);
        } finally {
            $lock->release();
        }

        $this->info("{$run->tickets_seen} Tickets verarbeitet, {$run->tickets_failed} fehlgeschlagen.");

        return $run->status === ZammadSyncRun::SUCCEEDED ? self::SUCCESS : self::FAILURE;
    }
}
