<?php

namespace App\Tracking;

use Carbon\CarbonImmutable;

/**
 * One event of the tracking history of DHL or UPS (PROJ-99), reduced to what the course of the shipment needs.
 * `delivered` is the carrier's own mark of a delivery (DHL statusCode, UPS type D outside an access point); `failure`
 * is DHL's mark of a failed delivery, whatever the text says.
 */
final readonly class TrackingEvent
{
    public function __construct(
        public ?CarbonImmutable $at,
        public string $text,
        public bool $delivered = false,
        public bool $failure = false,
    ) {}
}
