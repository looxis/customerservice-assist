<?php

namespace App\Jobs;

use App\Models\LabelTicket;
use App\Wismo\WismoResponder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Answers a new WISMO ticket automatically (PROJ-82). The responder says whether to come back later.
 */
class AnswerWismoTicket implements ShouldQueue
{
    use Queueable;

    public int $tries = 6;

    public function __construct(public int $labelTicketId) {}

    public function handle(WismoResponder $responder): void
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
            app(WismoResponder::class)->giveUp($label, 'Technischer Fehler: '.($exception === null ? 'unbekannt' : $exception::class));
        }
    }
}
