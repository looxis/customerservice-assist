<?php

namespace App\Jobs;

use App\Analysis\AnalysisException;
use App\Analysis\AnalysisProblem;
use App\Jobs\Middleware\SkipInactiveLabelRun;
use App\Labeling\LabelClassifier;
use App\Labeling\LabelRunner;
use App\Models\LabelRun;
use App\Models\LabelTicket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;

/**
 * Has the language model propose the category of one ticket (PROJ-78), within a run or, for a new incoming
 * ticket, on its own (PROJ-80).
 */
class ClassifyLabelTicket implements ShouldQueue
{
    use Queueable;

    /**
     * Waiting for the rate limit releases a job and counts as an attempt, so only the exceptions are limited.
     */
    public int $tries = 0;

    public int $maxExceptions = 4;

    public function __construct(public ?int $runId, public int $labelTicketId) {}

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
        return [new SkipInactiveLabelRun, new RateLimited('labeling-model')];
    }

    public function handle(LabelClassifier $classifier, LabelRunner $runner): void
    {
        $run = $this->runId === null ? null : LabelRun::query()->find($this->runId);

        if ($this->runId !== null && ($run === null || ! $run->isActive())) {
            return;
        }

        $ticket = LabelTicket::query()->find($this->labelTicketId);

        if ($ticket === null || blank($ticket->text)) {
            $this->count($runner, $run, false);

            return;
        }

        try {
            $classifier->classify($ticket, $run?->id);
        } catch (AnalysisException $exception) {
            if ($exception->problem === AnalysisProblem::Misconfigured) {
                $ticket->update(['state' => 'failed', 'problem' => $exception->problem->value]);
                $this->count($runner, $run, false);

                return;
            }

            throw $exception;
        }

        $this->count($runner, $run, true);
    }

    /**
     * A ticket removed in the meantime stays removed.
     */
    public function failed(): void
    {
        LabelTicket::query()->whereKey($this->labelTicketId)->where('state', '!=', LabelTicket::EXCLUDED)->update(['state' => 'failed', 'problem' => 'Klassifikation fehlgeschlagen']);
        $run = $this->runId === null ? null : LabelRun::query()->find($this->runId);

        $this->count(app(LabelRunner::class), $run, false);
    }

    private function count(LabelRunner $runner, ?LabelRun $run, bool $succeeded): void
    {
        if ($run !== null) {
            $runner->count($run, $succeeded);
        }
    }
}
