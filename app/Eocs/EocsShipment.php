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

    /**
     * Link to the carrier's tracking page (DHL or UPS), null for other carriers or without a tracking number.
     */
    public function trackingUrl(): ?string
    {
        $number = trim((string) $this->trackingNumber);
        if ($number === '') {
            return null;
        }

        $carrier = mb_strtolower((string) $this->carrier);

        return match (true) {
            str_contains($carrier, 'dhl') => 'https://www.dhl.de/de/privatkunden/pakete-empfangen/verfolgen.html?piececode='.rawurlencode($number),
            str_contains($carrier, 'ups') => 'https://www.ups.com/track?tracknum='.rawurlencode($number),
            default => null,
        };
    }

    public function trackingLabel(): string
    {
        return str_contains(mb_strtolower((string) $this->carrier), 'ups') ? 'Bei UPS verfolgen' : 'Bei DHL verfolgen';
    }
}
