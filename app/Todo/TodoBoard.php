<?php

namespace App\Todo;

use App\Models\TicketTodo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The lists of ticket ToDos (PROJ-70): the open ones on the start page, everything of the last 12 months on the ToDo page.
 */
class TodoBoard
{
    public const array VIEWS = ['meine' => 'Meine', 'alle' => 'Alle offen', 'ueberfaellig' => 'Überfällig'];

    public const string DEFAULT_VIEW = 'meine';

    public const int START_LIMIT = 15;

    public const int PAGE_SIZE = 100;

    public const int MONTHS = 12;

    public static function view(mixed $value): string
    {
        return is_string($value) && array_key_exists($value, self::VIEWS) ? $value : self::DEFAULT_VIEW;
    }

    /**
     * @param  Builder<TicketTodo>  $query
     * @return Builder<TicketTodo>
     */
    private function ordered(Builder $query): Builder
    {
        return $query->orderByRaw('due_on is null')->orderBy('due_on')->orderBy('id');
    }

    /**
     * @return array{meine: int, alle: int, ueberfaellig: int}
     */
    public function counts(?string $name): array
    {
        return [
            'meine' => $name === null ? 0 : TicketTodo::query()->unfinished()->whereIn('assignee', [$name, TodoRules::EVERYONE])->count(),
            'alle' => TicketTodo::query()->unfinished()->count(),
            'ueberfaellig' => TicketTodo::query()->overdue()->count(),
        ];
    }

    /**
     * @return array{rows: Collection<int, TicketTodo>, more: bool}
     */
    public function start(string $view, ?string $name): array
    {
        $query = TicketTodo::query()->unfinished();

        match ($view) {
            'meine' => $query->whereIn('assignee', [$name ?? '', TodoRules::EVERYONE]),
            'ueberfaellig' => $query->overdue(),
            default => null,
        };

        $rows = $this->ordered($query)->limit(self::START_LIMIT + 1)->get();

        return ['rows' => $rows->take(self::START_LIMIT), 'more' => $rows->count() > self::START_LIMIT];
    }

    /**
     * Open ToDos and those finished within the last 12 months.
     *
     * @return LengthAwarePaginator<int, TicketTodo>
     */
    public function page(string $show, ?string $assignee): LengthAwarePaginator
    {
        $query = TicketTodo::query()->where(fn (Builder $inner) => $inner->unfinished()->orWhere('completed_at', '>=', now()->subMonths(self::MONTHS)));

        match ($show) {
            'offen' => $query->unfinished(),
            'erledigt' => $query->where('status', TodoStatus::Done->value),
            default => null,
        };

        if ($assignee !== null && $assignee !== '') {
            $query->whereIn('assignee', [$assignee, TodoRules::EVERYONE]);
        }

        return $this->ordered($query->orderByRaw("status = 'done'"))->paginate(self::PAGE_SIZE)->withQueryString();
    }

    /**
     * @return Collection<int, TicketTodo>
     */
    public function forTicket(int $zammadId): Collection
    {
        return TicketTodo::query()->where('zammad_id', $zammadId)->orderByRaw("status = 'done'")->orderByRaw('due_on is null')->orderBy('due_on')->orderBy('id')->get();
    }

    /**
     * Which of the tickets have unfinished ToDos, for the marker in the ticket list.
     *
     * @param  iterable<int>  $zammadIds
     * @return list<int>
     */
    public function ticketsWithOpen(iterable $zammadIds): array
    {
        return TicketTodo::query()->unfinished()->whereIn('zammad_id', collect($zammadIds)->all())->distinct()->pluck('zammad_id')->map(fn (mixed $id): int => (int) $id)->all();
    }
}
