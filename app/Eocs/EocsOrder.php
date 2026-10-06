<?php

namespace App\Eocs;

use Carbon\CarbonImmutable;

/**
 * An order as the app sees it. Built per request, never stored. Customer
 * data (e-mail, billing, payment) is deliberately not taken over; only the
 * delivery address is kept, never shown and never sent to the language
 * model – it fills the [LIEFERADRESSE] placeholder of a reply (PROJ-9).
 */
final readonly class EocsOrder
{
    /**
     * @param  list<EocsShipment>  $shipments
     * @param  list<EocsOrderItem>  $items
     * @param  list<EocsClaim>  $claims
     * @param  list<string>  $deliveryAddress  Lines of the delivery address (name, street, postcode and city, country).
     */
    public function __construct(
        public int $id,
        public string $externalNumber,
        public ?string $channelName,
        public ?string $channel,
        public ?CarbonImmutable $orderedAt,
        public ?string $statusName,
        public ?string $statusColor,
        public ?string $invoiceNumber,
        public array $shipments,
        public array $items,
        public array $claims,
        public string $url,
        public array $deliveryAddress = [],
    ) {}

    public function withClaims(array $claims): self
    {
        return new self($this->id, $this->externalNumber, $this->channelName, $this->channel, $this->orderedAt, $this->statusName, $this->statusColor, $this->invoiceNumber, $this->shipments, $this->items, $claims, $this->url, $this->deliveryAddress);
    }

    /**
     * Badge tone of the design system for EOCS' status colour.
     */
    public static function tone(?string $color): string
    {
        return match (strtolower((string) $color)) {
            'green', 'success' => 'success',
            'yellow', 'orange', 'warning' => 'warning',
            'red', 'danger' => 'danger',
            'blue', 'info', 'primary' => 'info',
            default => 'neutral',
        };
    }
}
