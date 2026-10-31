<?php

namespace App\Console\Commands;

use App\Models\ZammadSyncRun;
use App\Zammad\TicketReconciler;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('zammad:sync-recent')]
#[Description('Read the Zammad tickets changed since the progress marker (PROJ-66)')]
class SyncRecentTickets extends Command
{
    public function handle(TicketReconciler $reconciler): int
    {
        $run = $reconciler->syncRecent();

        $this->info("{$run->tickets_seen} Tickets verarbeitet, {$run->tickets_failed} fehlgeschlagen.");

        return $run->status === ZammadSyncRun::SUCCEEDED ? self::SUCCESS : self::FAILURE;
    }
}
