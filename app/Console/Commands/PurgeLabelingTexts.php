<?php

namespace App\Console\Commands;

use App\Models\LabelTicket;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('labeling:purge')]
#[Description('Empty the message texts of the ticket tagging after the retention period (PROJ-78)')]
class PurgeLabelingTexts extends Command
{
    /**
     * Decisions and categories stay; only the customer text goes.
     */
    public function handle(): int
    {
        $before = now()->subMonths((int) config('labeling.retention_months'));

        $emptied = LabelTicket::query()
            ->whereNotNull('text')
            ->whereRaw('coalesce(last_activity_at, updated_at) < ?', [$before])
            ->update(['text' => null]);

        Log::info('Ticket tagging texts purged', compact('emptied'));
        $this->info("Geleert: {$emptied} Nachrichtentexte.");

        return self::SUCCESS;
    }
}
