<?php

namespace App\Tracking;

/**
 * Asks one carrier for the state of a shipment (PROJ-82).
 */
interface TrackingClient
{
    /**
     * @throws TrackingException
     */
    public function track(string $trackingNumber, ?string $postalCode): TrackingStatus;
}
