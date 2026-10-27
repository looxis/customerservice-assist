<?php

namespace App\Zammad;

use Carbon\CarbonImmutable;

/**
 * A new ticket with one outgoing e-mail to the customer (PROJ-64), for example a return label for a customer who
 * has no ticket yet. The recipient is the address the employee typed in.
 */
final readonly class OutgoingTicket
{
    /**
     * @param  string  $state  "closed" or "pending reminder".
     * @param  list<OutgoingAttachment>  $attachments
     */
    public function __construct(
        public string $group,
        public string $to,
        public string $subject,
        public string $body,
        public string $state,
        public ?CarbonImmutable $pendingUntil,
        public array $attachments = [],
    ) {}
}
