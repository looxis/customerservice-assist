<?php

namespace App\Analysis;

/**
 * The chosen view, filters and sorting of the ticket list (PROJ-67), already
 * cleaned: values that do not fit are dropped, so a hand-changed address never
 * causes an error. A saved view would only have to store these values.
 */
final readonly class TicketListFilter
{
    public function __construct(
        public string $view,
        public ?string $state,
        public ?string $owner,
        public ?string $channel,
        public ?string $category,
        public ?string $processing,
        public bool $unreadable,
        public string $sort,
        public string $direction,
    ) {}

    public function hasFilters(): bool
    {
        return $this->state !== null || $this->owner !== null || $this->channel !== null || $this->category !== null || $this->processing !== null;
    }

    /**
     * The address parameters of this filter, for paging and for links.
     *
     * @return array<string, string>
     */
    public function parameters(): array
    {
        return array_filter([
            'ansicht' => $this->view === TicketList::DEFAULT_VIEW ? null : $this->view,
            'status' => $this->state,
            'mitarbeiter' => $this->owner,
            'kanal' => $this->channel,
            'klassifikation' => $this->category,
            'csa' => $this->processing,
            'sync' => $this->unreadable ? TicketList::UNREADABLE : null,
            'sort' => $this->sort === TicketList::DEFAULT_SORT ? null : $this->sort,
            'richtung' => $this->direction === TicketList::DEFAULT_DIRECTION ? null : $this->direction,
        ], fn (?string $value): bool => $value !== null);
    }
}
