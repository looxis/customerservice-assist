<?php

namespace App\Labeling;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Invented example data for the labeling pages, so the screens can be judged before the
 * backend exists (PROJ-78). The backend replaces this class with the real figures; none
 * of the texts is a real customer message.
 */
class PreviewData
{
    /**
     * @return Collection<int, array{category: LabelCategory, confirmed: int, corrected: int, automatic: int, open: int, sampled: int, hits: int, released: bool, locked: bool}>
     */
    public function categories(): Collection
    {
        $figures = [
            [LabelCategory::Wismo, 96, 8, 140, 410, 62, 60, true, false],
            [LabelCategory::Reklamation, 41, 12, 0, 220, 38, 33, false, false],
            [LabelCategory::Angebotsanfrage, 22, 5, 0, 70, 14, 13, false, false],
            [LabelCategory::Bestellung, 9, 3, 0, 18, 6, 6, false, false],
            [LabelCategory::Storno, 7, 4, 0, 11, 0, 0, false, false],
            [LabelCategory::TechnischerSupport, 30, 2, 55, 95, 52, 46, true, true],
            [LabelCategory::ProduktFrage, 18, 6, 0, 130, 21, 20, false, false],
        ];

        return collect($figures)->map(fn (array $row): array => [
            'category' => $row[0], 'confirmed' => $row[1], 'corrected' => $row[2], 'automatic' => $row[3],
            'open' => $row[4], 'sampled' => $row[5], 'hits' => $row[6], 'released' => $row[7], 'locked' => $row[8],
        ]);
    }

    /**
     * @return list<array{kind: string, started: string, state: string, counter: string, admin: string}>
     */
    public function runs(): array
    {
        return [
            ['kind' => 'Klassifikation', 'started' => '06.11.2026, 09:12 Uhr', 'state' => 'läuft', 'counter' => '1.482 von 3.904', 'admin' => 'Etienne'],
            ['kind' => 'Abruf', 'started' => '05.11.2026, 17:40 Uhr', 'state' => 'fertig', 'counter' => '3.904 Tickets, 2 Fehler', 'admin' => 'Etienne'],
            ['kind' => 'Tags schreiben (Trockenlauf)', 'started' => '05.11.2026, 18:20 Uhr', 'state' => 'fertig', 'counter' => '212 geplant', 'admin' => 'Etienne'],
        ];
    }

    /**
     * The queues of the review, in the order in which they are worked through.
     *
     * @return array<string, array{label: string, count: int}>
     */
    public function queues(): array
    {
        return [
            'zweifel' => ['label' => 'Unklar / mehrere Anliegen', 'count' => 3],
            'konfidenz' => ['label' => 'Niedrige Konfidenz', 'count' => 41],
            'storno' => ['label' => 'Vorschläge „Storno“', 'count' => 11],
            'storno-wort' => ['label' => 'Wort „Storno“, nicht WISMO', 'count' => 6],
            'stichprobe' => ['label' => 'Stichprobe', 'count' => 24],
            'test' => ['label' => 'Testmenge', 'count' => 18],
        ];
    }

    /**
     * The ticket the review shows now.
     *
     * @return array<string, mixed>
     */
    public function reviewTicket(string $queue): array
    {
        $isTest = $queue === 'test';

        return [
            'id' => 4711,
            'number' => '2141234',
            'created' => '04.11.2026, 14:03 Uhr',
            'group' => 'Kundenservice',
            'test' => $isTest,
            'text' => "Guten Tag,\n\nich möchte meine Bestellung stornieren, weil das Paket auch nach zwei Wochen nicht angekommen ist. Laut Sendungsverfolgung steht es seit Tagen in der Zustellbasis. Bitte kümmern Sie sich darum.\n\nMit freundlichen Grüßen",
            'evidence' => 'weil das Paket auch nach zwei Wochen nicht angekommen ist',
            'suggestion' => LabelCategory::Wismo,
            'confidence' => 0.62,
            'unclear' => false,
            'multiple' => true,
            'reason' => 'Der Storno-Wunsch folgt aus dem Lieferproblem, der Anlass ist der Lieferstatus.',
            'remaining' => $queue === 'test' ? 18 : 3,
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function tickets(array $filters, int $page): LengthAwarePaginator
    {
        $samples = [
            ['Wo bleibt mein Paket? Bestellung vom 20.10., noch nichts angekommen.', LabelCategory::Wismo, 0.97, LabelCategory::Wismo, 'automatisch'],
            ['Der Konfigurator lädt mein Bild nicht hoch, es kommt nur ein Fehler.', LabelCategory::TechnischerSupport, 0.94, LabelCategory::TechnischerSupport, 'automatisch'],
            ['Hiermit bestelle ich wie besprochen 12 Stück in 40 x 60.', LabelCategory::Bestellung, 0.88, LabelCategory::Bestellung, 'bestätigt'],
            ['Bitte Lieferadresse ändern, ich habe mich vertippt.', LabelCategory::Storno, 0.71, LabelCategory::Storno, 'bestätigt'],
            ['Die Ecke des Rahmens ist beim Auspacken abgebrochen.', LabelCategory::Reklamation, 0.9, null, null],
            ['Ist das Material für den Außenbereich geeignet?', LabelCategory::ProduktFrage, 0.66, LabelCategory::ProduktFrage, 'bestätigt'],
        ];

        $rows = collect(range(0, 24))->map(function (int $i) use ($samples): array {
            [$text, $suggestion, $confidence, $decision, $tag] = $samples[$i % count($samples)];

            return [
                'id' => 4000 + $i,
                'number' => (string) (2141000 + $i),
                'group' => $i % 4 === 0 ? 'Amazon' : 'Kundenservice',
                'excerpt' => $text,
                'suggestion' => $suggestion,
                'confidence' => $confidence,
                'decision' => $decision,
                'tag' => $tag,
                'test' => $i % 5 === 0,
                'source' => $i % 9 === 0 ? 'vorhanden' : 'Pool',
            ];
        });

        if (($filters['kategorie'] ?? '') !== '') {
            $rows = $rows->filter(fn (array $row): bool => $row['suggestion']->value === $filters['kategorie']);
        }

        if (($filters['zuordnung'] ?? '') !== '') {
            $rows = $rows->filter(fn (array $row): bool => $row['test'] === ($filters['zuordnung'] === 'test'));
        }

        $perPage = 10;

        return new LengthAwarePaginator($rows->forPage($page, $perPage)->values(), $rows->count(), $perPage, $page, ['path' => route('labeling.tickets')]);
    }
}
