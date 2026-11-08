<?php

namespace App\Jobs;

use App\Jobs\Middleware\SkipInactiveLabelRun;
use App\Labeling\LabelRunner;
use App\Labeling\LabelTagging;
use App\Models\LabelRun;
use App\Models\LabelTicket;
use App\Staff\StaffDirectory;
use App\Zammad\ReplyException;
use App\Zammad\ReplyProblem;
use App\Zammad\ZammadException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;

/**
 * Writes the tag of one ticket to Zammad with the token of the admin who started the run (PROJ-78).
 */
class PushLabelTag implements ShouldQueue
{
    use Queueable;

    /**
     * Waiting for the rate limit releases a job and counts as an attempt, so only the exceptions are limited.
     */
    public int $tries = 0;

    public int $maxExceptions = 3;

    public function __construct(public int $runId, public int $labelTicketId) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 300];
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [new SkipInactiveLabelRun, new RateLimited('labeling-zammad')];
    }

    public function handle(LabelTagging $tagging, StaffDirectory $staff, LabelRunner $runner): void
    {
        $run = LabelRun::query()->find($this->runId);

        if ($run === null || ! $run->isActive()) {
            return;
        }

        $ticket = LabelTicket::query()->with('latestPrediction')->find($this->labelTicketId);
        $token = $staff->zammadToken($run->admin);

        if ($ticket === null || $token === null) {
            $runner->count($run, false);

            return;
        }

        try {
            $result = $tagging->push($ticket, $run->admin, $token);
        } catch (ReplyException $exception) {
            if ($exception->problem === ReplyProblem::Unavailable) {
                throw $exception;
            }

            $ticket->update(['tag_problem' => $exception->problem->value]);
            $runner->count($run, false);

            return;
        } catch (ZammadException $exception) {
            throw $exception;
        }

        $runner->count($run, $result !== 'conflict');
    }

    public function failed(): void
    {
        LabelTicket::query()->whereKey($this->labelTicketId)->update(['tag_problem' => 'Zammad nicht erreichbar']);
        $run = LabelRun::query()->find($this->runId);

        if ($run !== null) {
            app(LabelRunner::class)->count($run, false);
        }
    }
}
