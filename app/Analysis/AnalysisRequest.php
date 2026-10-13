<?php

namespace App\Analysis;

use App\Eocs\EocsOrder;
use App\Knowledge\CustomerGroup;
use App\Zammad\Ticket;

/**
 * Everything the employee chose for one analysis.
 */
final readonly class AnalysisRequest
{
    /**
     * @param  list<string>  $products
     * @param  list<EocsOrder>  $orders
     * @param  array<string, string>  $manualOrder  Order data typed in by hand when no EOCS order is loaded.
     * @param  array<string, mixed>  $formInput  The form as sent, to reopen it with the same input (PROJ-10).
     * @param  list<array{number: string, channel: string|null, items: list<string>}>  $mentionedOrders  Order numbers known from the ticket but not loaded from EOCS.
     * @param  list<string>  $notes  Hints for the employee about how the input came about, shown with the result.
     * @param  list<string>  $extraKnowledge  IDs of documents the employee switched on by hand (PROJ-36).
     * @param  list<array{question: string, answer: string, by: string, at: string}>  $decisions  Internal clarifications of earlier steps of this case (PROJ-39).
     * @param  string|null  $questionText  The text of a question without a ticket (PROJ-40); null for a ticket.
     * @param  bool  $replyWanted  Whether a reply draft for the customer was asked for (always true for a ticket).
     * @param  string|null  $unknownProduct  Name of a product the employee typed because the knowledge base has none (PROJ-42).
     */
    public function __construct(
        public Ticket $ticket,
        public string $staffName,
        public CustomerGroup $customerGroup,
        public array $products,
        public array $orders,
        public array $manualOrder,
        public string $employeeContext,
        public ContextVariant $variant,
        public ?Summary $summary,
        public array $formInput = [],
        public array $mentionedOrders = [],
        public array $notes = [],
        public array $extraKnowledge = [],
        public array $decisions = [],
        public ?string $questionText = null,
        public bool $replyWanted = true,
        public ?string $unknownProduct = null,
    ) {}

    public function isQuestion(): bool
    {
        return $this->questionText !== null;
    }

    /**
     * The clarifications as lines for the prompt.
     */
    public function decisionsText(): string
    {
        return implode("\n", array_map(fn (array $decision): string => "- {$decision['question']} → {$decision['answer']} (geklärt von {$decision['by']})", $this->decisions));
    }

    /**
     * Everything the employee stated as fact, for the knowledge retrieval.
     */
    public function factsText(): string
    {
        return trim($this->employeeContext."\n".$this->decisionsText());
    }
}
