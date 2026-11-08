<?php

namespace App\Console\Commands;

use App\Models\LabelTicket;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('labeling:remove-outreach {--pause=1000 : Pause between two tickets in milliseconds} {--after=0 : Only tickets with a higher ID} {--dry-run : Only count, remove nothing}')]
#[Description('Remove tickets of the ticket tagging whose first customer message answers a mail of ours (PROJ-78)')]
class RemoveLabelingOutreachTickets extends Command
{
    /**
     * Calls Zammad once per ticket. Removed tickets stay as a marker and are skipped, so the command can be started
     * again; --after continues behind a known ID.
     */
    public function handle(ZammadClient $zammad): int
    {
        $query = LabelTicket::query()
            ->where('state', '!=', LabelTicket::EXCLUDED)
            ->where('source', '!=', 'vorhanden')
            ->where('id', '>', (int) $this->option('after'));
        $total = (clone $query)->count();
        $checked = 0;
        $removed = 0;
        $tagged = 0;
        $failed = 0;

        $this->info("Tickets zu prüfen: {$total}");

        foreach ($query->lazyById(100) as $ticket) {
            try {
                $head = $zammad->ticketHead($ticket->zammad_id);

                if ($zammad->firstCustomerContact($ticket->zammad_id, $head->customerId)['contacted_first']) {
                    $removed++;
                    $tagged += $ticket->tag_written_at !== null ? 1 : 0;

                    if (! $this->option('dry-run')) {
                        $ticket->exclude('Erstkontakt durch uns');
                    }
                }
            } catch (ZammadException) {
                $failed++;
            }

            $checked++;

            if ($checked % 50 === 0) {
                $this->line("{$checked} geprüft, {$removed} entfernt, {$failed} fehlgeschlagen (letzte ID {$ticket->id})");
            }

            usleep((int) $this->option('pause') * 1000);
        }

        $this->info("Fertig: {$checked} geprüft, {$removed} ".($this->option('dry-run') ? 'würden entfernt' : 'entfernt').", {$failed} fehlgeschlagen.");

        if ($tagged > 0) {
            $this->warn("{$tagged} davon haben schon einen Tag in Zammad; der Tag bleibt dort bestehen.");
        }

        return self::SUCCESS;
    }
}
