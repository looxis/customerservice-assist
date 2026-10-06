<?php

namespace App\Analysis;

use App\Analysis\Agents\SummaryAgent;
use App\Zammad\Ticket;
use Carbon\CarbonImmutable;

/**
 * Stage 1: condenses the thread before the last customer message. Contact
 * data is replaced before sending and put back for the stored text.
 */
class Summarizer
{
    public function __construct(
        private readonly LanguageModel $model,
        private readonly AnalysisStore $store,
    ) {}

    /**
     * @throws AnalysisException
     */
    public function create(Ticket $ticket, TicketContext $context): Summary
    {
        $prompt = Prompt::load('summary');
        $pseudonymizer = app(Pseudonymizer::class);
        $model = (string) config('analysis.models.summary');

        $answer = $this->model->ask(new SummaryAgent($prompt->text), $pseudonymizer->apply($this->limited($context->earlierText())), $model, 'summary', $ticket->number);

        $summary = new Summary(
            text: $pseudonymizer->restorePlain(Summary::textFrom($answer['data'])),
            until: $context->earlierUntil(),
            fingerprint: $context->earlierFingerprint(),
            edited: false,
            model: $model,
            promptVersion: $prompt->version,
            createdAt: CarbonImmutable::now(),
        );

        $this->store->putSummary($ticket->summaryKey(), $summary);

        return $summary;
    }

    /**
     * Keep the newest part of a very long earlier thread.
     */
    private function limited(string $text): string
    {
        $limit = (int) config('analysis.max_input_characters');

        return mb_strlen($text) <= $limit ? $text : "[Ältere Nachrichten wegen der Länge gekürzt]\n…".mb_substr($text, -$limit);
    }
}
