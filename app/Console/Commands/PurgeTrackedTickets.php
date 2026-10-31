<?php

namespace App\Console\Commands;

use App\Models\TrackedTicket;
use App\Models\ZammadSyncRun;
use App\Models\ZammadWebhookDelivery;
use App\Zammad\SyncStatus;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('tickets:purge')]
#[Description('Delete tracked tickets and sync logs after their retention period (PROJ-66)')]
class PurgeTrackedTickets extends Command
{
    /**
     * The only place that deletes tracked tickets.
     */
    public function handle(): int
    {
        $ticketsBefore = now()->subDays((int) config('services.zammad.ticket_retention_days'));
        $logsBefore = now()->subDays((int) config('services.zammad.sync_log_retention_days'));

        $closed = TrackedTicket::query()->where('is_closed', true)->where('zammad_updated_at', '<', $ticketsBefore)->delete();
        $unreadable = TrackedTicket::query()->whereIn('sync_status', SyncStatus::unreadable())->where('sync_problem_since', '<', $ticketsBefore)->delete();

        // The run holding the marker stays, however old: without it a long outage could not be resumed.
        $marker = ZammadSyncRun::query()->where('type', ZammadSyncRun::RECENT)->whereNotNull('covered_until')->orderByDesc('covered_until')->value('id');
        $runs = ZammadSyncRun::query()->where('started_at', '<', $logsBefore)->when($marker !== null, fn ($query) => $query->where('id', '!=', $marker))->delete();
        $deliveries = ZammadWebhookDelivery::query()->where('received_at', '<', $logsBefore)->delete();

        Log::info('Tracked tickets purged', compact('closed', 'unreadable', 'runs', 'deliveries'));
        $this->info("Gelöscht: {$closed} geschlossene und {$unreadable} nicht lesbare Tickets, {$runs} Läufe, {$deliveries} Zustellungen.");

        return self::SUCCESS;
    }
}
