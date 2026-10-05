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
    ) {}

    public function lastArticleAt(): ?CarbonImmutable
    {
        return $this->articles === [] ? null : $this->articles[array_key_last($this->articles)]->createdAt;
    }

    public function attachmentCount(): int
    {
        return array_sum(array_map(fn (TicketArticle $article): int => count($article->attachments), $this->articles));
    }
}
