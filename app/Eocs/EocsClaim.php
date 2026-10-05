<?php

namespace App\Eocs;

use Carbon\CarbonImmutable;

/**
 * A follow-up order created in EOCS for a complaint (e.g. "R1-<original>").
 */
final readonly class EocsClaim
{
    public function __construct(
        public int $id,
        public string $externalNumber,
        public ?CarbonImmutable $orderedAt,
        public ?string $statusName,
        public ?string $statusColor,
        public string $url,
    ) {}
}
