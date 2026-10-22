<?php

namespace App\Analysis;

use App\Models\Analysis;
use App\Models\KnowledgeGap;
use Illuminate\Support\Carbon;
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
     * The gap of a product without knowledge (PROJ-45): created by the app when a proposal names such a
     * product, once per ticket or question; a repeated proposal only refreshes the employee's notes.
     * The topic is the one of the button "Wissenslücke melden", so a click afterwards shows "bereits gemeldet".
     */
    public function reportUnknownProduct(string $analysisId, string $name, string $notes, string $staff): ?KnowledgeGap
    {
        $analysis = Analysis::query()->where('uuid', $analysisId)->whereNotNull('content')->first();

        if ($analysis === null) {
            return null;
        }

        $hash = self::topicHash(self::unknownProductTopic($name));
        $notes = trim($notes) === '' ? null : mb_substr(trim($notes), 0, 2000);
        $existing = KnowledgeGap::query()->where('ticket_number', $analysis->ticket_number)->where('gap_topic_hash', $hash)->where('test_run', $analysis->test_until !== null)->latest('id')->first();

        if ($existing !== null) {
            if ($existing->status === 'open' && $existing->content !== null && $notes !== null && ($existing->content['notes'] ?? null) !== $notes) {
                $existing->update(['analysis_id' => $analysis->id, 'content' => [...$existing->content, 'notes' => $notes]]);
            }

            return $existing;
        }

        return KnowledgeGap::query()->create([
            'analysis_id' => $analysis->id,
            'ticket_number' => $analysis->ticket_number,
            'staff_name' => $staff,
            'customer_group' => $analysis->customer_group,
            'products' => $analysis->products ?? [],
            'test_run' => $analysis->test_until !== null,
            'gap_topic_hash' => $hash,
            'content' => [
                'missing' => 'Produkt „'.$name.'“: weder Katalogangaben noch Regeln',
                'solution' => null,
                'comment' => null,
                'source' => 'auto',
                'product' => $name,
                'notes' => $notes,
            ],
        ]);
    }

    /**
     * The partial gap of a product that ProductForge does not have (PROJ-38): created by the app when
     * a proposal uses an EOCS item as fallback, once per ticket or question and SKU. Counted per SKU for the
     * admin page so the team sees which products to maintain in ProductForge next.
     */
    public function reportNotInProductForge(string $analysisId, string $name, string $sku, string $staff): ?KnowledgeGap
    {
        $analysis = Analysis::query()->where('uuid', $analysisId)->whereNotNull('content')->first();

        if ($analysis === null) {
            return null;
        }

        $hash = self::topicHash(self::notInProductForgeTopic($sku));
        $existing = KnowledgeGap::query()->where('ticket_number', $analysis->ticket_number)->where('gap_topic_hash', $hash)->where('test_run', $analysis->test_until !== null)->latest('id')->first();

        if ($existing !== null) {
            return $existing;
        }

        return KnowledgeGap::query()->create([
            'analysis_id' => $analysis->id,
            'ticket_number' => $analysis->ticket_number,
            'staff_name' => $staff,
            'customer_group' => $analysis->customer_group,
            'products' => $analysis->products ?? [],
            'test_run' => $analysis->test_until !== null,
            'gap_topic_hash' => $hash,
            'content' => [
                'missing' => "Produkt „{$name}“ (SKU {$sku}) ist nicht in ProductForge gepflegt",
                'solution' => null,
                'comment' => null,
                'source' => 'auto',
                'kind' => 'not-in-productforge',
                'product' => $name,
                'sku' => $sku,
                'notes' => null,
            ],
        ]);
    }

    public static function notInProductForgeTopic(string $sku): string
    {
        return 'Nicht in ProductForge: SKU '.$sku;
    }

    /**
     * The products that ProductForge did not have, most often first: one entry per SKU over the open automatic reports.
     *
     * @return Collection<int, array{product: string, sku: string, cases: int, last: Carbon}>
     */
    public function notInProductForge(): Collection
    {
        return KnowledgeGap::query()->where('status', 'open')->where('test_run', false)->whereNotNull('content')->whereNotNull('gap_topic_hash')->get()
            ->filter(fn (KnowledgeGap $gap): bool => ($gap->content['kind'] ?? null) === 'not-in-productforge')
            ->groupBy('gap_topic_hash')
            ->map(fn (Collection $gaps): array => [
                'product' => (string) ($gaps->first()->content['product'] ?? ''),
                'sku' => (string) ($gaps->first()->content['sku'] ?? ''),
                'cases' => $gaps->count(),
                'last' => $gaps->max('created_at'),
            ])
            ->sortByDesc(fn (array $row): int => $row['cases'])
            ->values();
    }

    public static function unknownProductTopic(string $name): string
    {
        return 'Produktwissen: '.$name;
    }

    /**
     * The products without knowledge that were asked for, most often first: one entry per name
     * over the open automatic reports, with how many cases asked and the employees' notes.
     *
     * @return Collection<int, array{product: string, cases: int, last: Carbon, notes: list<string>}>
     */
    public function unknownProducts(): Collection
    {
        return KnowledgeGap::query()->where('status', 'open')->where('test_run', false)->whereNotNull('content')->whereNotNull('gap_topic_hash')->get()
            ->filter(fn (KnowledgeGap $gap): bool => ($gap->content['source'] ?? null) === 'auto' && ($gap->content['kind'] ?? null) === null)
            ->groupBy('gap_topic_hash')
            ->map(fn (Collection $gaps): array => [
                'product' => (string) ($gaps->first()->content['product'] ?? ''),
                'cases' => $gaps->count(),
                'last' => $gaps->max('created_at'),
                'notes' => $gaps->map(fn (KnowledgeGap $gap): ?string => $gap->content['notes'] ?? null)->filter()->unique()->take(5)->values()->all(),
            ])
            ->sortByDesc(fn (array $row): int => $row['cases'])
            ->values();
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
            ($content['notes'] ?? null) ? 'Angaben des Mitarbeiters zum Fall (bitte als Wissen prüfen): '.$content['notes'] : null,
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
