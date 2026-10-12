<?php

namespace App\Knowledge\Retrieval;

use App\Eocs\EocsOrder;
use App\Eocs\EocsShipment;
use Carbon\CarbonImmutable;

/**
 * What is known about one order for the conditions of the knowledge (PROJ-36).
 * A value that is null is unknown. Values of different orders are never
 * mixed: every order has its own facts.
 */
final readonly class OrderFacts
{
    /**
     * @param  array<string, mixed>  $values  By `when` key; missing or null means unknown.
     */
    public function __construct(public ?string $number, public array $values = []) {}

    /**
     * An order that is named but not loaded, or no order at all: nothing is known.
     */
    public static function unknown(?string $number = null): self
    {
        return new self($number);
    }

    public static function fromEocs(EocsOrder $order, CarbonImmutable $now): self
    {
        $shipments = $order->shipments;
        $dates = array_values(array_filter(array_map(fn (EocsShipment $shipment): ?CarbonImmutable => $shipment->shippedAt, $shipments)));
        usort($dates, fn (CarbonImmutable $a, CarbonImmutable $b): int => $a <=> $b);

        // Missing proof is never "no": without shipments, dates or marks the value stays unknown.
        $delivered = array_filter($shipments, fn (EocsShipment $shipment): bool => $shipment->delivered);
        $quantity = array_sum(array_map(fn ($item): int => $item->quantity, $order->items));

        return new self($order->externalNumber, [
            'order_channel' => $order->channel,
            'order_age_days' => self::days($order->orderedAt, $now),
            'shipped' => $shipments === [] ? null : true,
            'days_since_shipped' => $dates === [] ? null : self::days($dates[0], $now),
            'days_since_last_shipped' => $dates === [] ? null : self::days($dates[array_key_last($dates)], $now),
            'shipment_count' => $shipments === [] ? null : count($shipments),
            'delivery_confirmed' => $delivered === [] ? null : true,
            'all_shipments_delivered' => $shipments !== [] && count($delivered) === count($shipments) ? true : null,
            'has_claim' => $order->claims === [] ? null : true,
            'claim_count' => $order->claims === [] ? null : count($order->claims),
            'item_quantity_total' => $order->items === [] ? null : $quantity,
        ]);
    }

    public function get(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }

    private static function days(?CarbonImmutable $date, CarbonImmutable $now): ?int
    {
        if ($date === null) {
            return null;
        }

        $from = $date->setTimezone('Europe/Berlin')->startOfDay();
        $to = $now->setTimezone('Europe/Berlin')->startOfDay();

        return max(0, (int) floor($from->diffInDays($to, false)));
    }
}
