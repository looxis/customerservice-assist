<?php

namespace App\Eocs;

use Carbon\CarbonImmutable;

final readonly class EocsShipment
{
    public function __construct(
        public ?string $carrier,
        public ?string $method,
        public ?string $trackingNumber,
        public ?CarbonImmutable $shippedAt,
        public bool $delivered,
    ) {}
}
