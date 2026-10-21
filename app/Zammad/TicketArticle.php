<?php

namespace App\Zammad;

use Carbon\CarbonImmutable;
use Illuminate\Support\HtmlString;

/**
 * One message of a ticket thread. The body is already sanitized HTML; a
 * quoted earlier message at its end and a customer's signature are split off
 * so they can be collapsed.
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
        public ?HtmlString $signature = null,
        public ?int $id = null,
        public ?OrderMention $order = null,
        public ?string $type = null,
        public ?string $from = null,
        public ?string $replyTo = null,
        public ?string $subject = null,
        public ?string $messageId = null,
    ) {}

    public function hasText(): bool
    {
        return trim(strip_tags($this->body->toHtml())) !== '';
    }

    /**
     * The same message with the visible text and the collapsed quote exchanged (the signature part belonged to
     * the former text and is dropped).
     */
    public function withBodyAndQuoteSwapped(): self
    {
        return new self($this->kind, $this->senderName, $this->createdAt, $this->quote ?? $this->body, $this->quote === null ? null : $this->body, $this->attachments, $this->channel, $this->automatic, null, $this->id, $this->order, $this->type, $this->from, $this->replyTo, $this->subject, $this->messageId);
    }

    /**
     * Visible text in lower case without markup, spacing and punctuation, to compare messages.
     */
    public static function comparableText(?HtmlString $html): string
    {
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return (string) preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower($text));
    }
}
