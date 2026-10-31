<?php

namespace App\Zammad;

use App\Jobs\SyncZammadTicket;
use App\Models\TrackedTicket;
use App\Models\ZammadSyncRun;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The sync runs that keep CSA complete without any webhook (PROJ-66). Lists are
 * read first and compared with what is stored; only tickets that really changed
 * cost further calls to Zammad.
 */
class TicketReconciler
{
    private const int OVERLAP_MINUTES = 60;

    private const int FIRST_RUN_HOURS = 24;

    private const int WINDOW_HOURS = 24;

    private const int SMALLEST_WINDOW_SECONDS = 60;

    public function __construct(private readonly ZammadClient $zammad) {}

    /**
     * Everything changed since the marker, oldest first. The marker moves window by
     * window and only past a window that was read completely and processed without a
     * temporary problem, so after an outage the next run resumes exactly there.
     */
    public function syncRecent(): ZammadSyncRun
    {
        $run = ZammadSyncRun::begin(ZammadSyncRun::RECENT);
        $until = CarbonImmutable::now();
        $start = (ZammadSyncRun::marker() ?? $until->subHours(self::FIRST_RUN_HOURS))->subMinutes(self::OVERLAP_MINUTES);

        return $this->finishing($run, function () use ($run, $start, $until): void {
            $from = $start;

            while ($from->lessThan($until)) {
                $to = $this->window($from, $from->addHours(self::WINDOW_HOURS)->min($until), $run);
                $run->update(['covered_until' => $to]);
                $from = $to;
            }
        });
    }

    /**
     * Compares the complete list of open tickets with what is stored: finds tickets
     * that are missing, however old, re-reads the changed ones, those no longer listed
     * and those with a read problem. Independent of the marker.
     *
     * @param  bool  $everything  Also re-read every open ticket with its thread, past the search index.
     */
    public function reconcileOpen(string $type = ZammadSyncRun::RECONCILE, bool $everything = false): ZammadSyncRun
    {
        $run = ZammadSyncRun::begin($type);

        return $this->finishing($run, function () use ($run, $everything): void {
            $listed = $this->openTickets();
            $this->process($this->outdated($listed), $run);

            $notListed = TrackedTicket::query()->where('is_closed', false)->whereNotIn('zammad_id', array_keys($listed))->pluck('zammad_id');
            $problems = TrackedTicket::query()->where('sync_status', '!=', SyncStatus::Synced)->pluck('zammad_id');
            $this->process($notListed->merge($problems)->unique()->map(fn (int $id): array => ['id' => $id, 'listed' => null, 'articles' => false])->all(), $run);

            if ($everything) {
                $this->process(TrackedTicket::query()->where('is_closed', false)->pluck('zammad_id')->map(fn (int $id): array => ['id' => $id, 'listed' => null, 'articles' => true])->all(), $run);
            }

            if ($run->tickets_failed > 0) {
                throw new IncompleteSync('tickets_failed');
            }
        });
    }

    /**
     * Process one window and return where it really ended: a window with more
     * results than the search can deliver is cut in half first.
     */
    private function window(CarbonImmutable $from, CarbonImmutable $to, ZammadSyncRun $run): CarbonImmutable
    {
        $first = $this->zammad->updatedTicketsPage($from, $to, 1);

        while ($first['total'] > ZammadClient::SEARCH_RESULT_LIMIT) {
            $span = (int) $from->diffInSeconds($to);

            if ($span <= self::SMALLEST_WINDOW_SECONDS) {
                throw new IncompleteSync('window_too_full');
            }

            $to = $from->addSeconds(intdiv($span, 2));
            $first = $this->zammad->updatedTicketsPage($from, $to, 1);
        }

        $failedBefore = $run->tickets_failed;
        $page = $first;
        $number = 1;
        $seen = [];

        while (true) {
            $seen += array_column($page['records'], 'updated_at', 'id');
            $this->process($this->outdated(array_column($page['records'], 'updated_at', 'id')), $run);

            if (count($seen) >= $page['total'] || $page['records'] === []) {
                break;
            }

            $page = $this->zammad->updatedTicketsPage($from, $to, ++$number);
        }

        if (count($seen) < $page['total']) {
            throw new IncompleteSync('list_incomplete');
        }

        if ($run->tickets_failed > $failedBefore) {
            throw new IncompleteSync('tickets_failed');
        }

        return $to;
    }

    /**
     * All open tickets, or no answer at all: an incomplete list must not count as complete.
     *
     * @return array<int, ?string> Change time by Zammad ID.
     *
     * @throws IncompleteSync
     */
    private function openTickets(): array
    {
        $listed = [];
        $number = 1;

        do {
            $page = $this->zammad->openTicketsPage($number++);

            if ($page['total'] > ZammadClient::SEARCH_RESULT_LIMIT) {
                throw new IncompleteSync('too_many_open');
            }

            $listed += array_column($page['records'], 'updated_at', 'id');
        } while (count($listed) < $page['total'] && $page['records'] !== []);

        if (count($listed) !== $page['total']) {
            throw new IncompleteSync('list_incomplete');
        }

        return $listed;
    }

    /**
     * The listed tickets that CSA does not have yet or has in an older state; one query per list.
     *
     * @param  array<int, ?string>  $listed
     * @return list<array{id: int, listed: ?string, articles: bool}>
     */
    private function outdated(array $listed): array
    {
        $outdated = [];

        foreach (array_chunk($listed, 500, true) as $chunk) {
            $rows = TrackedTicket::query()->whereIn('zammad_id', array_keys($chunk))->get(['id', 'zammad_id', 'zammad_updated_at', 'sync_status'])->keyBy('zammad_id');

            foreach ($chunk as $id => $updatedAt) {
                $row = $rows->get($id);

                if ($row === null || $updatedAt === null || ! TicketSynchronizer::isCurrent($row, CarbonImmutable::parse($updatedAt))) {
                    $outdated[] = ['id' => $id, 'listed' => $updatedAt, 'articles' => false];
                }
            }
        }

        return $outdated;
    }

    /**
     * @param  array<int, array{id: int, listed: ?string, articles: bool}>  $tickets
     */
    private function process(array $tickets, ZammadSyncRun $run): void
    {
        foreach ($tickets as $ticket) {
            $run->tickets_seen++;

            try {
                SyncZammadTicket::dispatchSync($ticket['id'], $ticket['listed'], $ticket['articles'], true);
            } catch (Throwable $exception) {
                $run->tickets_failed++;
                Log::warning('Zammad ticket sync failed', ['zammad_id' => $ticket['id'], 'problem' => $exception instanceof ZammadException ? $exception->problem->value : class_basename($exception), 'status' => $exception instanceof ZammadException ? $exception->httpStatus : null]);

                if (! $exception instanceof ZammadException && ! $exception instanceof LockTimeoutException) {
                    report($exception);
                }

                if ($exception instanceof ZammadException && $exception->httpStatus === 429) {
                    $run->save();

                    throw new IncompleteSync('rate_limited');
                }
            }
        }

        $run->save();
    }

    /**
     * @param  callable(): void  $work
     */
    private function finishing(ZammadSyncRun $run, callable $work): ZammadSyncRun
    {
        try {
            $work();
            $run->finish(ZammadSyncRun::SUCCEEDED);
        } catch (IncompleteSync $exception) {
            Log::warning('Zammad sync run incomplete', ['run' => $run->id, 'type' => $run->type, 'reason' => $exception->errorCode]);
            $run->finish(ZammadSyncRun::FAILED, $exception->errorCode);
        } catch (ZammadException $exception) {
            Log::warning('Zammad sync run failed', ['run' => $run->id, 'type' => $run->type, 'problem' => $exception->problem->value, 'status' => $exception->httpStatus]);
            $run->finish(ZammadSyncRun::FAILED, $exception->problem->value);
        }

        return $run;
    }
}
