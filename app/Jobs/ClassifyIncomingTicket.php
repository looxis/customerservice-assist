<?php

namespace App\Jobs;

use App\Labeling\LabelCapture;
use App\Labeling\LabelingSettings;
use App\Zammad\ZammadException;
use App\Zammad\ZammadProblem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;

/**
 * Captures the first customer message of a new incoming ticket into the ticket tagging and has it classified
 * without a run, so that the staff can confirm the category on the ticket page (PROJ-80).
 */
class ClassifyIncomingTicket implements ShouldQueue
{
    use Queueable;

    /**
     * Waiting for the rate limit releases a job and counts as an attempt, so only the exceptions are limited.
     */
    public int $tries = 0;

    public int $maxExceptions = 5;

    public function __construct(public int $zammadId) {}

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
        return [new RateLimited('labeling-zammad')];
    }

    public function handle(LabelCapture $capture, LabelingSettings $settings): void
    {
        if (! $settings->all()['intake']) {
            return;
        }

        try {
            $ticket = $capture->capture($this->zammadId, (int) config('labeling.intake_test_share'), 'eingang');
        } catch (ZammadException $exception) {
            if ($exception->problem === ZammadProblem::NotFound) {
                return;
            }

            throw $exception;
        }

        if ($ticket?->state === 'captured') {
            ClassifyLabelTicket::dispatch(null, $ticket->id);
        }
    }
}
