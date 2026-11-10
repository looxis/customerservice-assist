<?php

namespace App\Analysis;

use App\Models\TrackedTicket;
use App\Models\ZammadSyncRun;
use App\Zammad\SyncStatus;
use App\Zammad\ZammadClient;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Reads the tracked tickets for the list on the start page (PROJ-67), from the
 * database only: the page stays usable while Zammad is down.
 */
class TicketList
{
    public const int PER_PAGE = 25;

    public const string DEFAULT_VIEW = 'ohne-zuweisung';

    public const string DEFAULT_SORT = 'aktivitaet';

    public const string DEFAULT_DIRECTION = 'desc';

    public const string UNREADABLE = 'problem';

    /** The value of a filter for "not set yet": no owner, no channel, no category. */
    public const string NONE = 'keine';

    /** The views are named filter sets, not lists of their own: owner is empty, "me" or not set. */
    public const array VIEWS = [
        'ohne-zuweisung' => ['label' => 'Allgemein offen', 'closed' => false, 'owner' => self::NONE],
        'meine' => ['label' => 'Meine', 'closed' => false, 'owner' => 'me'],
        'offen' => ['label' => 'Alle offenen', 'closed' => false, 'owner' => null],
        'geschlossen' => ['label' => 'Geschlossen', 'closed' => true, 'owner' => null],
    ];

    /** Sortable columns by their address value. */
    public const array SORTS = [
        'aktivitaet' => 'last_activity_at',
        'nummer' => 'zammad_id',
        'status' => 'state',
        'erstellt' => 'zammad_created_at',
    ];

    /**
     * Clean the raw query input into a filter; anything that does not fit is dropped.
     *
     * @param  array<string, mixed>  $input
     */
    public function filter(array $input): TicketListFilter
    {
        $text = fn (string $key): ?string => is_string($input[$key] ?? null) && $input[$key] !== '' ? $input[$key] : null;
        $allowed = fn (string $key, array $values): ?string => in_array($text($key), $values, true) ? $text($key) : null;
        $owner = $text('mitarbeiter');

        return new TicketListFilter(
            view: $allowed('ansicht', array_keys(self::VIEWS)) ?? self::DEFAULT_VIEW,
            state: $allowed('status', array_keys(ZammadClient::STATE_LABELS)),
            owner: $owner !== null && ($owner === self::NONE || preg_match('/^[1-9]\d{0,18}$/', $owner) === 1) ? $owner : null,
            channel: $text('kanal') === null ? null : mb_substr($text('kanal'), 0, 40),
            category: $text('klassifikation') === null ? null : mb_substr($text('klassifikation'), 0, 60),
            processing: $allowed('csa', array_column(ProcessingState::cases(), 'value')),
            unreadable: $text('sync') === self::UNREADABLE,
            sort: $allowed('sort', array_keys(self::SORTS)) ?? self::DEFAULT_SORT,
            direction: $allowed('richtung', ['asc', 'desc']) ?? self::DEFAULT_DIRECTION,
        );
    }

    /**
     * @return LengthAwarePaginator<int, TrackedTicket>
     */
    public function page(TicketListFilter $filter, ?int $staffZammadId, int $page = 1): LengthAwarePaginator
    {
        $rows = $this->query($filter, $staffZammadId)
            ->select(['id', 'zammad_id', 'number', 'title', 'state', 'owner_zammad_id', 'owner_name', 'channel', 'category', 'category_confirmed', 'processing_state', 'sync_status', 'last_activity_at', 'zammad_created_at'])
            ->orderBy(self::SORTS[$filter->sort], $filter->direction)->orderBy('id', $filter->direction)
            ->paginate(self::PER_PAGE, page: max(1, $page));

        return $page > $rows->lastPage() && $rows->lastPage() > 0 ? $this->page($filter, $staffZammadId, $rows->lastPage()) : $rows;
    }

    /**
     * Whether "my tickets" cannot be shown because the chosen name has no Zammad user.
     */
    public function lacksStaffUser(TicketListFilter $filter, ?int $staffZammadId): bool
    {
        return ! $filter->unreadable && $filter->owner === null && self::VIEWS[$filter->view]['owner'] === 'me' && $staffZammadId === null;
    }

    /**
     * The values for the selection lists; the owner is identified by the ID, the name is only shown.
     *
     * @return array{owners: array<int, string>, channels: list<string>, categories: list<string>}
     */
    public function options(): array
    {
        $owners = TrackedTicket::query()->whereNotNull('owner_zammad_id')
            ->selectRaw('owner_zammad_id, max(owner_name) as name')->groupBy('owner_zammad_id')->orderBy('name')
            ->pluck('name', 'owner_zammad_id')
            ->map(fn (?string $name, int $id): string => $name ?? "Zammad-Benutzer {$id}");
        $values = fn (string $column): array => TrackedTicket::query()->whereNotNull($column)->distinct()->orderBy($column)->pluck($column)->all();

        return ['owners' => $owners->all(), 'channels' => $values('channel'), 'categories' => $values('category')];
    }

    /**
     * How current the list is.
     *
     * @return array{marker: ?CarbonImmutable, stale: bool, reconcile_failed: bool, unreadable: int}
     */
    public function syncState(): array
    {
        $marker = ZammadSyncRun::marker();
        $lastReconcile = ZammadSyncRun::query()->where('type', ZammadSyncRun::RECONCILE)->whereNotNull('finished_at')->orderByDesc('finished_at')->orderByDesc('id')->value('status');

        return [
            'marker' => $marker,
            'stale' => $marker !== null && $marker->lessThan(now()->subMinutes((int) config('services.zammad.sync_stale_minutes'))),
            'reconcile_failed' => $lastReconcile === ZammadSyncRun::FAILED,
            'unreadable' => TrackedTicket::query()->whereIn('sync_status', SyncStatus::unreadable())->count(),
        ];
    }

    /**
     * @return Builder<TrackedTicket>
     */
    private function query(TicketListFilter $filter, ?int $staffZammadId): Builder
    {
        $query = TrackedTicket::query();
        $optional = fn (Builder $builder, string $column, ?string $value): Builder => match ($value) {
            null => $builder,
            self::NONE => $builder->whereNull($column),
            default => $builder->where($column, $value),
        };

        if ($filter->unreadable) {
            $query->whereIn('sync_status', SyncStatus::unreadable());
        } else {
            $view = self::VIEWS[$filter->view];
            $query->whereNotIn('sync_status', SyncStatus::unreadable())->where('is_closed', $view['closed']);

            // A chosen employee replaces the owner of the view, so the filters can be combined freely.
            if ($filter->owner === null && $view['owner'] === 'me') {
                $staffZammadId === null ? $query->whereRaw('1 = 0') : $query->where('owner_zammad_id', $staffZammadId);
            } else {
                $optional($query, 'owner_zammad_id', $filter->owner ?? $view['owner']);
            }
        }

        if ($filter->state !== null) {
            $query->where('state', $filter->state);
        }

        if ($filter->processing !== null) {
            $query->where('processing_state', $filter->processing);
        }

        $optional($query, 'channel', $filter->channel);
        $optional($query, 'category', $filter->category);

        return $query;
    }
}
