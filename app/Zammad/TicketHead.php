<?php

namespace App\Zammad;

use Carbon\CarbonImmutable;

/**
 * The header data of a Zammad ticket for the sync (PROJ-66), without the thread.
 */
final readonly class TicketHead
{
    public function __construct(
        public int $id,
        public string $number,
        public string $title,
        public string $state,
        public bool $closed,
        public ?CarbonImmutable $pendingUntil,
        public ?string $group,
        public ?int $ownerId,
        public ?int $customerId,
        public ?int $organizationId,
        public ?CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public CarbonImmutable $lastActivityAt,
        public ?CarbonImmutable $lastContactCustomerAt,
    ) {}
}
