<?php

namespace App\Orders;

/**
 * An order number in its normalised form, with the channel its format points to.
 */
final readonly class OrderNumber
{
    public function __construct(
        public string $value,
        public OrderNumberFormat $format,
    ) {}

    public function isEocsId(): bool
    {
        return $this->format === OrderNumberFormat::EocsId;
    }
}
