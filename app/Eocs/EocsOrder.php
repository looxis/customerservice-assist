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
    public const SHIPPING_NOTICE_DAYS = 14;

    public const SHIPPING_STRONG_NOTICE_DAYS = 30;

    /** Working days (Monday to Friday, no holiday calendar) after which an order without shipment is suspicious (PROJ-82). */
    public const STUCK_WORKDAYS = 3;

    /**
     * @param  list<EocsShipment>  $shipments
     * @param  list<EocsOrderItem>  $items
     * @param  list<EocsClaim>  $claims
     * @param  list<string>  $deliveryAddress  Lines of the delivery address (name, street, postcode and city, country).
     * @param  ?EocsShippingAddress  $shippingAddress  The same address in separate fields, only for the return label (PROJ-64).
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
        public ?EocsShippingAddress $shippingAddress = null,
    ) {}

    public function withClaims(array $claims): self
    {
        return new self($this->id, $this->externalNumber, $this->channelName, $this->channel, $this->orderedAt, $this->statusName, $this->statusColor, $this->invoiceNumber, $this->shipments, $this->items, $claims, $this->url, $this->deliveryAddress, $this->shippingAddress);
    }

    /**
     * Whole calendar days (Berlin time) since the first shipment; null without a shipment date (PROJ-79).
     * The knowledge condition `days_since_shipped` counts the same way.
     */
    public function daysSinceFirstShipment(CarbonImmutable $now): ?int
    {
        $dates = array_values(array_filter(array_map(fn (EocsShipment $shipment): ?CarbonImmutable => $shipment->shippedAt, $this->shipments)));

        if ($dates === []) {
            return null;
        }

        usort($dates, fn (CarbonImmutable $a, CarbonImmutable $b): int => $a <=> $b);

        $from = $dates[0]->setTimezone('Europe/Berlin')->startOfDay();
        $to = $now->setTimezone('Europe/Berlin')->startOfDay();

        return max(0, (int) floor($from->diffInDays($to, false)));
    }

    /**
     * Level of the shipping-age notice (PROJ-79): null below 14 days or without a shipment,
     * "notice" from 14 days, "strong" above 30 days.
     */
    public function shippingAgeLevel(CarbonImmutable $now): ?string
    {
        $days = $this->daysSinceFirstShipment($now);

        return match (true) {
            $days === null || $days < self::SHIPPING_NOTICE_DAYS => null,
            $days > self::SHIPPING_STRONG_NOTICE_DAYS => 'strong',
            default => 'notice',
        };
    }

    /**
     * Working days (Monday to Friday, Berlin time, no holiday calendar) between the order date and today; null
     * without an order date (PROJ-82).
     */
    public function workdaysSinceOrdered(CarbonImmutable $now): ?int
    {
        if ($this->orderedAt === null) {
            return null;
        }

        $from = $this->orderedAt->setTimezone('Europe/Berlin')->startOfDay();
        $to = $now->setTimezone('Europe/Berlin')->startOfDay();
        $days = 0;

        for ($day = $from->addDay(); $day <= $to; $day = $day->addDay()) {
            if ($day->isWeekday()) {
                $days++;
            }
        }

        return $days;
    }

    /**
     * Working days since the order date when the order has no shipment and is older than three working days:
     * it may be stuck (PROJ-82); otherwise null.
     */
    public function stuckWorkdays(CarbonImmutable $now): ?int
    {
        $days = $this->workdaysSinceOrdered($now);

        return $this->shipments === [] && $days !== null && $days > self::STUCK_WORKDAYS ? $days : null;
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
