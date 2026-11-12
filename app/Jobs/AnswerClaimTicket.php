<?php

namespace App\Jobs;

use App\ClaimReply\ClaimReplyResponder;
use App\Models\LabelTicket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Answers a new complaint ticket automatically with a first reply (PROJ-85). The responder says whether to come back later.
 */
class AnswerClaimTicket implements ShouldQueue
{
    use Queueable;

    public int $tries = 6;

    /** Shorter than the retry time of the queue (90 s), so the same job never runs twice at once. */
    public int $timeout = 80;

    public bool $failOnTimeout = true;

    public function __construct(public int $labelTicketId) {}

    public function handle(ClaimReplyResponder $responder): void
    {
        $label = LabelTicket::query()->find($this->labelTicketId);

        if ($label === null) {
            return;
        }

        $delay = $responder->run($label);

        if ($delay !== null) {
            $this->release($delay);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $label = LabelTicket::query()->find($this->labelTicketId);

        if ($label !== null) {
            app(ClaimReplyResponder::class)->giveUp($label, 'Technischer Fehler: '.($exception === null ? 'unbekannt' : $exception::class));
        }
    }
}
