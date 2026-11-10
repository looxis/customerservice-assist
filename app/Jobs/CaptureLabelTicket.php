<?php

namespace App\Jobs;

use App\Jobs\Middleware\SkipInactiveLabelRun;
use App\Labeling\LabelCapture;
use App\Labeling\LabelingSettings;
use App\Labeling\LabelRunner;
use App\Models\LabelRun;
use App\Zammad\ZammadException;
use App\Zammad\ZammadProblem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;

/**
 * Reads the first customer message and the tags of one Zammad ticket into the ticket tagging (PROJ-78).
 */
class CaptureLabelTicket implements ShouldQueue
{
    use Queueable;

    /**
     * Waiting for the rate limit releases a job and counts as an attempt, so only the exceptions are limited.
     */
    public int $tries = 0;

    public int $maxExceptions = 5;

    public function __construct(public int $runId, public int $zammadId) {}

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

    public function handle(LabelCapture $capture, LabelingSettings $settings, LabelRunner $runner): void
    {
        $run = LabelRun::query()->find($this->runId);

        if ($run === null || ! $run->isActive()) {
            return;
        }

        try {
            $capture->capture($this->zammadId, $settings->all()['test_share']);
        } catch (ZammadException $exception) {
            if ($exception->problem === ZammadProblem::NotFound) {
                $runner->count($run, false);

                return;
            }

            throw $exception;
        }

        $runner->count($run, true);
    }

    public function failed(): void
    {
        $run = LabelRun::query()->find($this->runId);

        if ($run !== null) {
            app(LabelRunner::class)->count($run, false);
        }
    }
}
