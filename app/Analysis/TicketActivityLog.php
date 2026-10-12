<?php

namespace App\Analysis;

use App\Models\TicketVisit;
use App\Zammad\Ticket;
use Illuminate\Support\Facades\DB;

/**
 * Which tickets were opened in the app and by whom (PROJ-35). Rewound test-run
 * views (PROJ-32) are not recorded, so tests stay apart from real work.
 */
class TicketActivityLog
{
    public function recordVisit(Ticket $ticket, ?string $staff): void
    {
        if ($ticket->rewoundTo !== null) {
            return;
        }

        $now = now();
        $values = ['customer_name' => $ticket->customerName, 'title' => $ticket->title, 'last_opened_at' => $now, 'last_opened_by' => $staff];

        DB::transaction(function () use ($ticket, $staff, $now, $values): void {
            $visit = TicketVisit::query()->where('ticket_number', $ticket->number)->lockForUpdate()->first();

            if ($visit === null) {
                TicketVisit::query()->create([...$values, 'ticket_number' => $ticket->number, 'first_opened_at' => $now, 'first_opened_by' => $staff, 'opened_count' => 1]);

                return;
            }

            $visit->update([...$values, 'opened_count' => $visit->opened_count + 1]);
        });
    }

    /**
     * Drop visits not renewed within the retention period.
     */
    public function purge(): int
    {
        return TicketVisit::query()->where('last_opened_at', '<', now()->subMonths((int) config('analysis.content_retention_months')))->delete();
    }
}
