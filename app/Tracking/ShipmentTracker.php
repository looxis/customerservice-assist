<?php

namespace App\Tracking;

use App\Eocs\EocsShipment;

/**
 * Chooses the client for the carrier of a shipment (PROJ-82). Other carriers get no status, only their name and number.
 */
class ShipmentTracker
{
    public function __construct(private readonly DhlTrackingClient $dhl, private readonly UpsTrackingClient $ups) {}

    /**
     * @throws TrackingException
     */
    public function track(EocsShipment $shipment, ?string $postalCode): ?TrackingStatus
    {
        $number = trim((string) $shipment->trackingNumber);

        return match (Carriers::detect($shipment->carrier, $number)) {
            'DHL' => $this->dhl->track($number, $postalCode),
            'UPS' => $this->ups->track($number, $postalCode),
            default => null,
        };
    }
}
