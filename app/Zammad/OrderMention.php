<?php

namespace App\Zammad;

/**
 * An order named in the ticket thread (e.g. in Amazon's message notice).
 * Fields the mail does not carry stay null; EOCS fills them later (PROJ-7).
 */
final readonly class OrderMention
{
    /**
     * @param  list<OrderMentionItem>  $items
     */
    public function __construct(
        public string $number,
        public string $source,
        public array $items = [],
        public ?string $invoiceNumber = null,
    ) {}

    /**
     * Combine two mentions of the same order; each product appears once.
     */
    public function merge(self $other): self
    {
        $items = $this->items;

        foreach ($other->items as $item) {
            if (! array_filter($items, fn (OrderMentionItem $known): bool => $known->key() === $item->key())) {
                $items[] = $item;
            }
        }

        return new self($this->number, $this->source, $items, $this->invoiceNumber ?? $other->invoiceNumber);
    }
}
