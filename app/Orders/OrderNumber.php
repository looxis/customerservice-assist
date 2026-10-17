<?php

namespace App\Orders;

/**
 * An order number in its normalised form, with the channel its format points to. When the text wrote it as a
 * claim number ("R1-302-…"), the claim numbers named for it are kept (PROJ-53).
 */
final readonly class OrderNumber
{
    /**
     * @param  list<int>  $claims  Claim numbers written in front of this order number in the text.
     */
    public function __construct(
        public string $value,
        public OrderNumberFormat $format,
        public array $claims = [],
    ) {}

    /**
     * The internal EOCS number: looked up by its id and loaded only on a click.
     */
    public function isEocsId(): bool
    {
        return $this->format->internal;
    }
}
