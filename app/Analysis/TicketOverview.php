<?php

namespace App\Analysis;

use App\Models\Analysis;
use App\Models\MessageTranslation;
use App\Models\TicketVisit;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * The tickets opened in the app with how far the work went (PROJ-35): only
 * opened, translated, analysed, reply edited, reply copied as it was or copied
 * after changes. Test-run analyses (PROJ-32) are left out.
 */
class TicketOverview
{
    public const int PER_PAGE = 50;

    /** @var array<string, string> */
    public const array STAGES = [
        'opened' => 'Nur abgerufen',
        'translated' => 'Übersetzt',
        'analyzed' => 'Vorschlag erstellt',
        'edited' => 'Bearbeitet, nicht kopiert',
        'copied-unchanged' => 'Kopiert (unverändert)',
        'copied-changed' => 'Kopiert (angepasst)',
    ];

    /**
     * @return array{rows: LengthAwarePaginator<int, array<string, mixed>>, counts: array<string, int>, total: int}
     */
    public function page(?string $stage, int $page): array
    {
        $rows = $this->rows();
        $counts = array_fill_keys(array_keys(self::STAGES), 0);

        foreach ($rows as $row) {
            $counts[$row['stage']]++;
        }

        $shown = $stage === null ? $rows : $rows->where('stage', $stage)->values();

        return [
            'rows' => new LengthAwarePaginator($shown->forPage($page, self::PER_PAGE)->values(), $shown->count(), self::PER_PAGE, $page, ['path' => route('ticket-overview.index'), 'query' => array_filter(['stand' => $stage])]),
            'counts' => $counts,
            'total' => $rows->count(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function rows(): Collection
    {
        $latest = Analysis::query()
            ->whereNull('test_until')
            ->where('status', 'completed')
            ->orderBy('id')
            ->get(['id', 'ticket_number', 'feedback_level', 'reply_edited_at', 'reply_copied_at', 'reply_copied_changed'])
            ->groupBy('ticket_number');
        $translated = MessageTranslation::query()->where('status', '!=', 'german')->distinct()->pluck('ticket_number')->flip();

        return TicketVisit::query()->orderByDesc('last_opened_at')->orderByDesc('id')->get()->map(function (TicketVisit $visit) use ($latest, $translated): array {
            $analyses = $latest->get($visit->ticket_number, collect());
            $newest = $analyses->last();

            return [
                'visit' => $visit,
                'stage' => $this->stage($newest, $translated->has($visit->ticket_number)),
                'analyses' => $analyses->count(),
                'translated' => $translated->has($visit->ticket_number),
                'feedback' => $newest?->feedback_level,
            ];
        });
    }

    private function stage(?Analysis $newest, bool $translated): string
    {
        return match (true) {
            $newest?->reply_copied_at !== null => $newest->reply_copied_changed ? 'copied-changed' : 'copied-unchanged',
            $newest?->reply_edited_at !== null => 'edited',
            $newest !== null => 'analyzed',
            $translated => 'translated',
            default => 'opened',
        };
    }
}
