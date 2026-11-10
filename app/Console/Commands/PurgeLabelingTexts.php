<?php

namespace App\Console\Commands;

use App\Models\LabelPrediction;
use App\Models\LabelTicket;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('labeling:purge')]
#[Description('Empty the customer texts of the ticket tagging once a ticket is older than the retention period (PROJ-78)')]
class PurgeLabelingTexts extends Command
{
    /**
     * Counted from the creation of the ticket in Zammad. Decisions, categories and tags stay; the customer text goes,
     * with the reason and the quoted passage of the proposals.
     */
    public function handle(): int
    {
        $before = now()->subMonths((int) config('labeling.retention_months'));
        $old = fn ($query) => $query->whereRaw('coalesce(zammad_created_at, created_at) < ?', [$before]);

        $emptied = LabelTicket::query()
            ->whereNotNull('text')
            ->where($old)
            ->update(['text' => null, 'subject' => null, 'sender' => null]);

        $proposals = LabelPrediction::query()
            ->whereIn('label_ticket_id', LabelTicket::query()->where($old)->select('id'))
            ->where(fn ($query) => $query->whereNotNull('reason')->orWhereNotNull('evidence'))
            ->update(['reason' => null, 'evidence' => null]);

        Log::info('Ticket tagging texts purged', compact('emptied', 'proposals'));
        $this->info("Geleert: {$emptied} Nachrichtentexte, {$proposals} Begründungen der Vorschläge.");

        return self::SUCCESS;
    }
}
