<?php

namespace App\Analysis;

use App\Models\Analysis;
use App\Models\KnowledgeGap;
use Illuminate\Support\Collection;

/**
 * Reported gaps in the knowledge base (PROJ-12): reported at an analysis,
 * worked through by an admin on the page „Wissenslücken“.
 */
class KnowledgeGapLog
{
    public const array STATUSES = ['open' => 'Offen', 'done' => 'Erledigt', 'discarded' => 'Verworfen'];

    /**
     * @param  array{missing: string, solution?: string|null, comment?: string|null}  $texts
     */
    public function report(string $ticketNumber, string $analysisId, array $texts, ?string $topic, string $staff): ?KnowledgeGap
    {
        $analysis = Analysis::query()->where('uuid', $analysisId)->where('ticket_number', $ticketNumber)->whereNotNull('content')->first();

        if ($analysis === null) {
            return null;
        }

        return KnowledgeGap::query()->create([
            'analysis_id' => $analysis->id,
            'ticket_number' => $analysis->ticket_number,
            'staff_name' => $staff,
            'customer_group' => $analysis->customer_group,
            'products' => $analysis->products ?? [],
            'test_run' => $analysis->test_until !== null,
            'gap_topic_hash' => $topic === null || trim($topic) === '' ? null : self::topicHash($topic),
            'content' => [
                'missing' => $texts['missing'],
                'solution' => $this->filled($texts['solution'] ?? null),
                'comment' => $this->filled($texts['comment'] ?? null),
            ],
        ]);
    }

    /**
     * Who already reported which topic of an analysis, keyed by topic hash;
     * the count of all reports for it under "all".
     *
     * @return array{topics: array<string, string>, all: int}
     */
    public function reportedFor(string $analysisId): array
    {
        $gaps = KnowledgeGap::query()->whereHas('analysis', fn ($query) => $query->where('uuid', $analysisId))->get(['gap_topic_hash', 'staff_name']);

        return [
            'topics' => $gaps->whereNotNull('gap_topic_hash')->mapWithKeys(fn (KnowledgeGap $gap): array => [$gap->gap_topic_hash => $gap->staff_name])->all(),
            'all' => $gaps->count(),
        ];
    }

    /**
     * @return Collection<int, KnowledgeGap>
     */
    public function list(string $status): Collection
    {
        return KnowledgeGap::query()->where('status', $status)->latest()->latest('id')->get();
    }

    public function openCount(): int
    {
        return KnowledgeGap::query()->where('status', 'open')->count();
    }

    /**
     * Mark a gap as done or discarded, or open it again: one click, who and
     * when are kept. Nothing else is asked for.
     */
    public function resolve(KnowledgeGap $gap, string $status, string $staff): void
    {
        $gap->update([
            'status' => $status,
            'resolved_by' => $status === 'open' ? null : $staff,
            'resolved_at' => $status === 'open' ? null : now(),
        ]);
    }

    /**
     * A text block for the authoring chat, following the authoring guide.
     */
    public function chatText(KnowledgeGap $gap, array $groupLabels): string
    {
        $content = $gap->content ?? [];

        return implode("\n", array_filter([
            'Wissenslücke aus der Customer Service Assist App – bitte daraus ein Knowledge-Dokument nach dem Authoring Guide erstellen.',
            '',
            'Was fehlt: '.($content['missing'] ?? ''),
            ($content['solution'] ?? null) ? 'So lösen wir das: '.$content['solution'] : 'So lösen wir das: (noch offen – bitte beim Autor erfragen)',
            ($content['comment'] ?? null) ? 'Kommentar: '.$content['comment'] : null,
            'Kundengruppe: '.($groupLabels[$gap->customer_group] ?? $gap->customer_group ?? 'unbekannt'),
            'Produkte: '.(($gap->products ?? []) === [] ? 'kein Produktbezug' : implode(', ', $gap->products)),
            'Ticket: '.$gap->ticket_number,
        ], fn (?string $line): bool => $line !== null));
    }

    public static function topicHash(string $topic): string
    {
        return hash('sha256', mb_strtolower(trim($topic)));
    }

    private function filled(?string $value): ?string
    {
        return $value === null || trim($value) === '' ? null : trim($value);
    }
}
