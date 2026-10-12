<?php

namespace App\Analysis;

use App\Models\Analysis;
use App\Models\KnowledgeGap;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Reads the analyses for the overview (PROJ-14): filter, figures and rows from the
 * figure columns only; no customer content is decrypted.
 */
class AnalysisOverview
{
    public const int PER_PAGE = 50;

    /** @var array<string, string> */
    public const array COPY_STATES = [
        'copied-unchanged' => 'Kopiert (unverändert)',
        'copied-changed' => 'Kopiert (angepasst)',
        'edited' => 'Bearbeitet, nicht kopiert',
        'not-copied' => 'Nicht kopiert',
    ];

    /**
     * Clean the raw query input into a filter; anything that does not fit is dropped.
     *
     * @param  array<string, mixed>  $input
     * @param  array{groups: list<string>, products: list<string>, categories: list<string>, staff: list<string>, models: list<string>, prompts: list<string>}|null  $options
     */
    public function filter(array $input, ?array $options = null): AnalysisFilter
    {
        $options ??= $this->options();
        $text = fn (string $key, array $allowed): ?string => is_string($input[$key] ?? null) && in_array($input[$key], $allowed, true) ? $input[$key] : null;
        $days = is_string($input['tage'] ?? null) && array_key_exists((int) $input['tage'], config('analysis.overview_periods')) ? (int) $input['tage'] : null;
        $from = $days === null ? $this->date($input['von'] ?? null) : null;
        $to = $days === null ? $this->date($input['bis'] ?? null) : null;
        $problem = null;

        if ($from !== null && $to !== null && $from->greaterThan($to)) {
            $problem = 'Der Zeitraum ist ungültig: „Von“ liegt nach „Bis“. Es gilt der Standardzeitraum.';
            $from = $to = null;
        } elseif ($from !== null && $from->isFuture()) {
            $problem = 'Der Zeitraum ist ungültig: „Von“ liegt in der Zukunft. Es gilt der Standardzeitraum.';
            $from = $to = null;
        }

        $custom = $from !== null || $to !== null;

        return new AnalysisFilter(
            days: $custom ? null : ($days ?? (int) config('analysis.overview_default_days')),
            from: $from,
            to: $to,
            group: $text('kundengruppe', $options['groups']),
            product: $text('produkt', $options['products']),
            category: $text('kategorie', $options['categories']),
            feedback: $text('feedback', [...array_keys(config('analysis.feedback_levels')), 'none']),
            copy: $text('kopiert', array_keys(self::COPY_STATES)),
            staff: $text('mitarbeiter', $options['staff']),
            model: $text('modell', $options['models']),
            prompt: $text('prompt', $options['prompts']),
            tests: ($input['testlaeufe'] ?? null) === '1',
            failed: ($input['fehlgeschlagen'] ?? null) === '1',
            problem: $problem,
        );
    }

    /**
     * Values that occur in the stored analyses, for the selection lists.
     *
     * @return array{groups: list<string>, products: list<string>, categories: list<string>, staff: list<string>, models: list<string>, prompts: list<string>}
     */
    public function options(): array
    {
        $values = fn (string $column): array => Analysis::query()->whereNotNull($column)->where($column, '!=', '')->distinct()->orderBy($column)->pluck($column)->all();

        $products = Analysis::query()->whereNotNull('products')->distinct()->pluck('products')->flatten()->filter(fn ($product): bool => is_string($product) && $product !== '')->unique()->sort()->values()->all();

        return [
            'groups' => $values('customer_group'),
            'products' => $products,
            'categories' => $values('category'),
            'staff' => $values('staff_name'),
            'models' => $values('model'),
            'prompts' => $values('prompt_version'),
        ];
    }

    /**
     * @return LengthAwarePaginator<int, Analysis>
     */
    public function page(AnalysisFilter $filter, int $page = 1): LengthAwarePaginator
    {
        $rows = $this->query($filter)
            ->select(['id', 'uuid', 'ticket_number', 'scope_key', 'status', 'error', 'staff_name', 'test_until', 'customer_group', 'products', 'category', 'model', 'prompt_version', 'feedback_level', 'reply_edited_at', 'reply_copied_at', 'reply_copied_changed', 'content_purged_at', 'content_deleted_at', 'rule_check', 'rule_check_at', 'created_at'])
            ->withCount('gaps')
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(self::PER_PAGE, page: max(1, $page));

        return $page > $rows->lastPage() && $rows->lastPage() > 0 ? $this->page($filter, $rows->lastPage()) : $rows;
    }

    /**
     * The figures of the whole filtered set. Shares are null without a base.
     *
     * @return array{count: int, rated: int, usable: int|null, discarded: int|null, without_feedback: int|null, copied: int, copied_unchanged: int|null, gaps: int}
     */
    public function figures(AnalysisFilter $filter): array
    {
        $set = fn (): Builder => $this->query($filter);
        $count = $set()->count();
        $finished = $set()->where('status', 'completed')->count();
        $rated = $set()->whereNotNull('feedback_level')->count();
        $copied = $set()->whereNotNull('reply_copied_at')->count();
        $share = fn (int $part, int $base): ?int => $base === 0 ? null : (int) round($part / $base * 100);

        return [
            'count' => $count,
            'rated' => $rated,
            'usable' => $share($set()->whereIn('feedback_level', config('analysis.feedback_usable_levels'))->count(), $rated),
            'discarded' => $share($set()->where('feedback_level', 'discarded')->count(), $rated),
            'without_feedback' => $share($finished - $rated, $finished),
            'copied' => $copied,
            'copied_unchanged' => $share($set()->whereNotNull('reply_copied_at')->where('reply_copied_changed', false)->count(), $copied),
            'gaps' => KnowledgeGap::query()->whereIn('analysis_id', $set()->select('analyses.id'))->count(),
        ];
    }

    /**
     * The copy state of one row: null when unknown because it was not recorded yet.
     */
    public function copyState(Analysis $analysis): ?string
    {
        return match (true) {
            $analysis->status !== 'completed' => null,
            $analysis->reply_copied_at !== null => $analysis->reply_copied_changed ? 'copied-changed' : 'copied-unchanged',
            $analysis->reply_edited_at !== null => 'edited',
            $analysis->created_at < CarbonImmutable::parse(config('analysis.reply_tracking_since')) => null,
            default => 'not-copied',
        };
    }

    /**
     * Address of the analysis in its ticket; a test run keeps its cut point.
     *
     * @return array<string, string>
     */
    public function ticketParameters(Analysis $analysis): array
    {
        $stand = Str::contains($analysis->scope_key, '.stand-') ? Str::after($analysis->scope_key, '.stand-') : null;

        return array_filter(['number' => $analysis->ticket_number, 'analyse' => $analysis->status === 'completed' ? $analysis->uuid : null, 'stand' => $stand]);
    }

    /**
     * @return Builder<Analysis>
     */
    private function query(AnalysisFilter $filter): Builder
    {
        $query = Analysis::query();

        if ($filter->from !== null || $filter->to !== null) {
            $filter->from !== null && $query->where('created_at', '>=', $filter->from->startOfDay()->utc());
            $filter->to !== null && $query->where('created_at', '<=', $filter->to->endOfDay()->utc());
        } else {
            $query->where('created_at', '>=', now()->subDays($filter->days ?? (int) config('analysis.overview_default_days')));
        }

        $filter->tests || $query->realRun();
        $filter->failed || $query->where('status', 'completed');

        $filter->group === null || $query->where('customer_group', $filter->group);
        $filter->product === null || $query->whereJsonContains('products', $filter->product);
        $filter->category === null || $query->where('category', $filter->category);
        $filter->staff === null || $query->where('staff_name', $filter->staff);
        $filter->model === null || $query->where('model', $filter->model);
        $filter->prompt === null || $query->where('prompt_version', $filter->prompt);

        if ($filter->feedback === 'none') {
            $query->where('status', 'completed')->whereNull('feedback_level');
        } elseif ($filter->feedback !== null) {
            $query->where('feedback_level', $filter->feedback);
        }

        match ($filter->copy) {
            'copied-unchanged' => $query->whereNotNull('reply_copied_at')->where('reply_copied_changed', false),
            'copied-changed' => $query->whereNotNull('reply_copied_at')->where('reply_copied_changed', true),
            'edited' => $query->whereNull('reply_copied_at')->whereNotNull('reply_edited_at'),
            'not-copied' => $query->where('status', 'completed')->whereNull('reply_copied_at')->whereNull('reply_edited_at')->where('created_at', '>=', config('analysis.reply_tracking_since')),
            default => null,
        };

        return $query;
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('!Y-m-d', $value, 'Europe/Berlin') ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}
