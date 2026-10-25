<?php

namespace App\Zammad;

use Carbon\CarbonImmutable;

/**
 * The reply to hand to Zammad (PROJ-19). The recipient comes from the ticket on the server, never from the form.
 */
final readonly class OutgoingReply
{
    /**
     * @param  string  $state  "closed" or "pending reminder".
     * @param  list<OutgoingAttachment>  $attachments  Files that go with the reply (PROJ-61).
     */
    public function __construct(
        public int $ticketId,
        public string $to,
        public string $subject,
        public string $body,
        public ?string $inReplyTo,
        public string $state,
        public ?CarbonImmutable $pendingUntil,
        public array $attachments = [],
    ) {}
}
