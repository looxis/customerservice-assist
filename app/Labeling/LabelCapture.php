<?php

namespace App\Labeling;

use App\Analysis\TicketContext;
use App\Models\LabelTicket;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadException;

/**
 * Reads the first customer message and the tags of one Zammad ticket into the ticket tagging (PROJ-78), for the
 * retrieval run as for new incoming tickets (PROJ-80).
 */
class LabelCapture
{
    public function __construct(
        private readonly ZammadClient $zammad,
        private readonly LabelingSettings $settings,
        private readonly SenderFilter $senders,
    ) {}

    /**
     * The new entry, or null when the ticket was captured before, was merged or belongs to an excluded group.
     * A ticket from a filtered sender or one we wrote to first is kept only as a marker, so that it is skipped later.
     * The test set takes the given share of tickets, stable per ticket.
     *
     * @throws ZammadException
     */
    public function capture(int $zammadId, int $testShare, string $source = 'pool'): ?LabelTicket
    {
        if (LabelTicket::query()->where('zammad_id', $zammadId)->exists()) {
            return null;
        }

        $head = $this->zammad->ticketHead($zammadId);

        if ($head->state === 'merged' || in_array($head->group, $this->settings->excludedGroups(), true)) {
            return null;
        }

        $tags = $this->zammad->ticketTags($zammadId);
        ['article' => $article, 'contacted_first' => $contactedFirst] = $this->zammad->firstCustomerContact($zammadId, $head->customerId);

        $existing = LabelTagging::existing($tags);

        $filteredBySender = $this->senders->matches($article?->from);

        if ($existing === null && ($filteredBySender || $contactedFirst)) {
            return LabelTicket::query()->create([
                'zammad_id' => $zammadId,
                'number' => $head->number,
                'zammad_created_at' => $head->createdAt,
                'source' => $source,
                'split' => 'training',
                'state' => LabelTicket::EXCLUDED,
                'problem' => $filteredBySender ? 'Entfernt (Absenderfilter)' : 'Entfernt (zuerst von uns kontaktiert)',
                'decided_by' => $filteredBySender ? 'Absenderfilter' : 'Erstkontakt durch uns',
                'last_activity_at' => now(),
            ]);
        }

        $test = $existing !== null ? $existing['test'] : abs(crc32('labeling-'.$zammadId)) % 100 < $testShare;

        $ticket = LabelTicket::query()->create([
            'zammad_id' => $zammadId,
            'number' => $head->number,
            'group_name' => $head->group,
            'article_id' => $article?->id,
            'text' => $article === null ? null : TicketContext::plainText($article),
            'subject' => $head->title === '(ohne Betreff)' ? null : $head->title,
            'sender' => $article?->from,
            'zammad_created_at' => $head->createdAt,
            'source' => $existing === null ? $source : 'vorhanden',
            'split' => $test ? 'test' : 'training',
            'state' => $article === null ? 'unusable' : 'captured',
            'problem' => $article === null ? 'Keine Kundennachricht gefunden' : null,
            'existing_tag' => $existing['tag'] ?? null,
            'decision' => $existing['category']->value ?? null,
            'decision_origin' => $existing === null ? null : 'vorhanden',
            'tag_kind' => $existing === null ? null : 'vorhanden',
            'tag_value' => $existing['tag'] ?? null,
            'last_activity_at' => now(),
        ]);

        if ($ticket->decision !== null) {
            $ticket->shareCategory();
        }

        return $ticket;
    }
}
