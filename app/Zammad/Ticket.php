<?php

namespace App\Zammad;

use Carbon\CarbonImmutable;

/**
 * A Zammad ticket as the app sees it: header data and the thread in
 * chronological order. Built per request, never stored.
 */
final readonly class Ticket
{
    /**
     * @param  list<TicketArticle>  $articles  Oldest first.
     * @param  list<OrderMention>  $orders  Orders named in the thread, each once.
     */
    public function __construct(
        public string $number,
        public int $id,
        public string $title,
        public string $state,
        public bool $closed,
        public ?string $mergedIntoNumber,
        public ?string $group,
        public ?string $customerName,
        public ?string $customerEmail,
        public CarbonImmutable $createdAt,
        public array $articles,
        public string $zammadUrl,
        public array $orders = [],
        public ?int $customerId = null,
        public ?int $organizationId = null,
        public ?int $rewoundTo = null,
    ) {}

    /**
     * The ticket as it was up to and including one customer message (PROJ-32
     * test mode), with the later messages. Null when the message is not a
     * customer message of this ticket.
     *
     * @return array{0: self, 1: list<TicketArticle>}|null
     */
    public function rewoundTo(int $articleId): ?array
    {
        foreach ($this->articles as $index => $article) {
            if ($article->id === $articleId && $article->kind === ArticleKind::Customer) {
                $kept = array_slice($this->articles, 0, $index + 1);
                $orders = [];

                foreach ($kept as $earlier) {
                    if ($earlier->order !== null) {
                        $number = $earlier->order->number;
                        $orders[$number] = isset($orders[$number]) ? $orders[$number]->merge($earlier->order) : $earlier->order;
                    }
                }

                return [
                    new self($this->number, $this->id, $this->title, $this->state, $this->closed, $this->mergedIntoNumber, $this->group, $this->customerName, $this->customerEmail, $this->createdAt, $kept, $this->zammadUrl, array_values($orders), $this->customerId, $this->organizationId, $articleId),
                    array_slice($this->articles, $index + 1),
                ];
            }
        }

        return null;
    }

    /**
     * Time of the customer message a rewound ticket ends with.
     */
    public function rewoundAt(): ?CarbonImmutable
    {
        return $this->rewoundTo === null || $this->articles === [] ? null : $this->articles[array_key_last($this->articles)]->createdAt;
    }

    /**
     * Key under which the summary is kept: a rewound ticket has its own per
     * cut point, so tests never touch the real summary.
     */
    public function summaryKey(): string
    {
        return $this->rewoundTo === null ? $this->number : "{$this->number}.stand-{$this->rewoundTo}";
    }

    /**
     * Who the ticket is from, for remembering the customer group: the
     * organization when there is one, otherwise the customer.
     */
    public function customerKey(): ?string
    {
        return match (true) {
            $this->organizationId !== null => "organization-{$this->organizationId}",
            $this->customerId !== null => "customer-{$this->customerId}",
            default => null,
        };
    }

    public function lastArticleAt(): ?CarbonImmutable
    {
        return $this->articles === [] ? null : $this->articles[array_key_last($this->articles)]->createdAt;
    }

    public function attachmentCount(): int
    {
        return array_sum(array_map(fn (TicketArticle $article): int => count($article->attachments), $this->articles));
    }
}
