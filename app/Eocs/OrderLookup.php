<?php

namespace App\Eocs;

use App\Orders\OrderNumber;

/**
 * Result of looking up one order number: the orders EOCS knows for it
 * (usually one, empty when not found).
 */
final readonly class OrderLookup
{
    /**
     * @param  list<EocsOrder>  $orders
     */
    public function __construct(
        public OrderNumber $number,
        public array $orders,
    ) {}

    public function found(): bool
    {
        return $this->orders !== [];
    }
}
