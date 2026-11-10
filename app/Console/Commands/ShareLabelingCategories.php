<?php

namespace App\Console\Commands;

use App\Models\LabelTicket;
use App\Models\TrackedTicket;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('labeling:share-categories')]
#[Description('Write the category of the ticket tagging into the ticket list on the start page (PROJ-80)')]
class ShareLabelingCategories extends Command
{
    /**
     * For the tickets CSA already tracks; new ones get their category on their own. Can be started again at any time.
     */
    public function handle(): int
    {
        $shared = 0;

        LabelTicket::query()
            ->whereIn('zammad_id', TrackedTicket::query()->select('zammad_id'))
            ->chunkById(500, function ($tickets) use (&$shared): void {
                foreach ($tickets as $ticket) {
                    $ticket->shareCategory();
                    $shared++;
                }
            });

        $this->info("Übertragen: {$shared} Tickets.");

        return self::SUCCESS;
    }
}
