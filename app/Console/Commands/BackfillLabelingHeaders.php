<?php

namespace App\Console\Commands;

use App\Labeling\SenderFilter;
use App\Models\LabelTicket;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('labeling:backfill-headers {--pause=1500 : Pause between two tickets in milliseconds}')]
#[Description('Load subject and sender for tickets of the ticket tagging that were captured without them (PROJ-78)')]
class BackfillLabelingHeaders extends Command
{
    /**
     * Calls Zammad once per ticket and can be started again at any time: it only looks at tickets without both fields.
     */
    public function handle(ZammadClient $zammad, SenderFilter $senders): int
    {
        $query = LabelTicket::query()->whereNull('subject')->whereNull('sender')->whereNotNull('text');
        $total = (clone $query)->count();
        $done = 0;
        $failed = 0;

        $this->info("Tickets ohne Betreff und Absender: {$total}");

        foreach ($query->lazyById(100) as $ticket) {
            try {
                $head = $zammad->ticketHead($ticket->zammad_id);
                $article = $zammad->firstCustomerArticle($ticket->zammad_id, $head->customerId);

                $ticket->update([
                    'subject' => $head->title === '(ohne Betreff)' ? null : $head->title,
                    'sender' => $article?->from,
                ]);

                if ($ticket->source !== 'vorhanden' && $senders->matches($ticket->sender)) {
                    $ticket->exclude('Absenderfilter');
                }

                $done++;
            } catch (ZammadException) {
                $failed++;
            }

            if (($done + $failed) % 100 === 0) {
                $this->line(($done + $failed).' bearbeitet, '.$failed.' fehlgeschlagen');
            }

            usleep((int) $this->option('pause') * 1000);
        }

        $this->info("Fertig: {$done} geladen, {$failed} fehlgeschlagen.");

        return self::SUCCESS;
    }
}
