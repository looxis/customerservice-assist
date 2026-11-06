<?php

namespace App\Jobs;

use App\Analysis\AnalysisException;
use App\Analysis\AnalysisProblem;
use App\Labeling\LabelClassifier;
use App\Labeling\LabelRunner;
use App\Models\LabelRun;
use App\Models\LabelTicket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;

/**
 * Has the language model propose the category of one ticket (PROJ-78).
 */
class ClassifyLabelTicket implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public function __construct(public int $runId, public int $labelTicketId) {}

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
        return [new RateLimited('labeling-model')];
    }

    public function handle(LabelClassifier $classifier, LabelRunner $runner): void
    {
        $run = LabelRun::query()->find($this->runId);

        if ($run === null || ! $run->isActive()) {
            return;
        }

        $ticket = LabelTicket::query()->find($this->labelTicketId);

        if ($ticket === null || blank($ticket->text)) {
            $runner->count($run, false);

            return;
        }

        try {
            $classifier->classify($ticket, $run->id);
        } catch (AnalysisException $exception) {
            if ($exception->problem === AnalysisProblem::Misconfigured) {
                $ticket->update(['state' => 'failed', 'problem' => $exception->problem->value]);
                $runner->count($run, false);

                return;
            }

            throw $exception;
        }

        $runner->count($run, true);
    }

    public function failed(): void
    {
        LabelTicket::query()->whereKey($this->labelTicketId)->update(['state' => 'failed', 'problem' => 'Klassifikation fehlgeschlagen']);
        $run = LabelRun::query()->find($this->runId);

        if ($run !== null) {
            app(LabelRunner::class)->count($run, false);
        }
    }
}
