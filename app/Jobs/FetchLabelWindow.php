<?php

namespace App\Jobs;

use App\Jobs\Middleware\SkipInactiveLabelRun;
use App\Labeling\LabelingSettings;
use App\Labeling\LabelRunner;
use App\Models\LabelRun;
use App\Models\LabelTicket;
use App\Zammad\ZammadClient;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;

/**
 * One page of the retrieval of all Zammad tickets by period (PROJ-78). A period with too many tickets is cut in
 * halves; the next page or period follows as a new job, so an interrupted run goes on where it stopped.
 */
class FetchLabelWindow implements ShouldQueue
{
    use Queueable;

    /**
     * Waiting for the rate limit releases a job and counts as an attempt, so only the exceptions are limited.
     */
    public int $tries = 0;

    public int $maxExceptions = 5;

    public int $timeout = 120;

    public function __construct(public int $runId) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120, 600];
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [new SkipInactiveLabelRun, new RateLimited('labeling-zammad')];
    }

    public function handle(ZammadClient $zammad, LabelingSettings $settings, LabelRunner $runner): void
    {
        $run = LabelRun::query()->find($this->runId);

        if ($run === null || ! $run->isActive()) {
            return;
        }

        $cursor = $run->cursor ?? ['windows' => [], 'page' => 1];
        $windows = $cursor['windows'];
        $page = (int) $cursor['page'];

        if ($windows === []) {
            $runner->finishIfDone($run);

            return;
        }

        $from = CarbonImmutable::parse($windows[0][0]);
        $to = CarbonImmutable::parse($windows[0][1]);
        $result = $zammad->createdTicketsPage($from, $to, $page, $settings->excludedGroups());

        if ($page === 1 && $result['total'] > (int) config('labeling.window_limit') && $to->diffInSeconds($from, true) > 3600) {
            $middle = $from->addSeconds((int) ($to->diffInSeconds($from, true) / 2));
            array_splice($windows, 0, 1, [[$from->toIso8601ZuluString(), $middle->toIso8601ZuluString()], [$middle->toIso8601ZuluString(), $to->toIso8601ZuluString()]]);
            $run->update(['cursor' => ['windows' => $windows, 'page' => 1]]);
            self::dispatch($run->id);

            return;
        }

        $known = LabelTicket::query()->whereIn('zammad_id', $result['ids'])->pluck('zammad_id')->all();
        $new = array_values(array_diff($result['ids'], $known));

        $run->increment('total', count($new));

        foreach ($new as $id) {
            CaptureLabelTicket::dispatch($run->id, $id);
        }

        if (count($result['ids']) < 100 || $page * 100 >= $result['total']) {
            array_shift($windows);
            $page = 1;
        } else {
            $page++;
        }

        $run->update(['cursor' => ['windows' => $windows, 'page' => $page]]);

        if ($windows === []) {
            $runner->finishIfDone($run->refresh());
        } else {
            self::dispatch($run->id);
        }
    }

    public function failed(): void
    {
        LabelRun::query()->whereKey($this->runId)->whereIn('state', ['queued', 'running'])->update(['state' => 'failed', 'finished_at' => now()]);
    }
}
