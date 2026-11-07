<?php

namespace App\Labeling;

use App\Models\LabelTicket;
use App\Zammad\ReplyException;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadException;
use Illuminate\Support\Collection;

/**
 * Brings the tags in Zammad in line with the decisions (PROJ-78): confirmed tickets and, in released categories,
 * clear proposals. Never overwrites a tag it did not set itself.
 */
class LabelTagging
{
    public const string PATTERN = '/^((train|test):|spam$)/';

    public function __construct(
        private readonly ZammadClient $zammad,
        private readonly LabelTagWriter $writer,
        private readonly LabelStats $stats,
    ) {}

    /**
     * The tickets whose tag has to be set, changed or removed.
     *
     * @return Collection<int, LabelTicket>
     */
    public function pending(): Collection
    {
        return LabelTicket::query()
            ->where(fn ($query) => $query->whereNotNull('decision')->orWhere(fn ($open) => $open->whereNull('decision')->where('state', 'classified')->where('split', 'training')))
            ->with('latestPrediction')
            ->get()
            ->filter(fn (LabelTicket $ticket): bool => $ticket->decision !== null
                ? $ticket->decision_origin !== 'vorhanden' && $ticket->tagPending()
                : $this->stats->mayTagAutomatically($ticket))
            ->values();
    }

    /**
     * The category and kind of a tag of the tagging that a ticket already carries.
     *
     * @param  list<string>  $tags
     * @return array{tag: string, category: LabelCategory, test: bool}|null
     */
    public static function existing(array $tags): ?array
    {
        foreach ($tags as $tag) {
            if ($tag === LabelCategory::Spam->tag()) {
                return ['tag' => $tag, 'category' => LabelCategory::Spam, 'test' => false];
            }

            if (preg_match('/^(train|test):(.+)$/', $tag, $match) && ($category = LabelCategory::tryFrom($match[2])) !== null) {
                return ['tag' => $tag, 'category' => $category, 'test' => $match[1] === 'test'];
            }
        }

        return null;
    }

    /**
     * @return 'written'|'skipped'|'conflict'
     *
     * @throws ReplyException
     * @throws ZammadException
     */
    public function push(LabelTicket $ticket, string $admin, string $token): string
    {
        if ($ticket->decision === null) {
            if (! $this->stats->mayTagAutomatically($ticket)) {
                return 'skipped';
            }

            $ticket->update(['decision' => $ticket->latestPrediction->category, 'decision_origin' => 'automatisch', 'decided_by' => $admin, 'decided_at' => now(), 'tag_kind' => 'automatisch']);
        }

        $desired = $ticket->desiredTag();
        $current = $this->zammad->ticketTags($ticket->zammad_id);
        $foreign = array_diff(array_filter($current, fn (string $tag): bool => preg_match(self::PATTERN, $tag) === 1), array_filter([$ticket->tag_value, $desired]));

        if ($foreign !== []) {
            $ticket->update(['tag_problem' => 'Konflikt: '.implode(', ', $foreign)]);

            return 'conflict';
        }

        if ($ticket->tag_value !== null && $ticket->tag_value !== $desired && in_array($ticket->tag_value, $current, true)) {
            $this->writer->remove($token, $ticket->zammad_id, $ticket->tag_value);
        }

        if ($desired !== null && ! in_array($desired, $current, true)) {
            $this->writer->add($token, $ticket->zammad_id, $desired);
        }

        $ticket->update(['tag_value' => $desired, 'tag_written_at' => now(), 'tag_problem' => null]);

        return 'written';
    }
}
