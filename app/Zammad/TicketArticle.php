<?php

namespace App\Zammad;

use Carbon\CarbonImmutable;
use Illuminate\Support\HtmlString;

/**
 * One message of a ticket thread. The body is already sanitized HTML; a
 * quoted earlier message at its end is split off so it can be collapsed.
 */
final readonly class TicketArticle
{
    /**
     * @param  list<TicketAttachment>  $attachments
     */
    public function __construct(
        public ArticleKind $kind,
        public string $senderName,
        public CarbonImmutable $createdAt,
        public HtmlString $body,
        public ?HtmlString $quote = null,
        public array $attachments = [],
        public ?string $channel = null,
        public bool $automatic = false,
    ) {}

    public function hasText(): bool
    {
        return trim(strip_tags($this->body->toHtml())) !== '';
    }
}
