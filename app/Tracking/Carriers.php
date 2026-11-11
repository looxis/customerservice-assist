<?php

namespace App\Tracking;

/**
 * Which carrier a shipment belongs to and where its public tracking page is (PROJ-82).
 */
final class Carriers
{
    /**
     * "DHL", "UPS" or null for any other carrier. The tracking number decides when the carrier name is missing,
     * like in the n8n workflow (DHL parcels start with 0034043, UPS with 1Z).
     */
    public static function detect(?string $carrier, string $trackingNumber): ?string
    {
        $name = mb_strtolower((string) $carrier);

        return match (true) {
            str_contains($name, 'dhl') => 'DHL',
            str_contains($name, 'ups') => 'UPS',
            $name === '' && preg_match('/^0034043/', $trackingNumber) === 1 => 'DHL',
            $name === '' && preg_match('/^1Z/i', $trackingNumber) === 1 => 'UPS',
            default => null,
        };
    }

    /**
     * Link to the public tracking page, only for DHL and UPS; a fixed host and the number encoded.
     */
    public static function link(?string $carrier, string $trackingNumber): ?string
    {
        return match (self::detect($carrier, $trackingNumber)) {
            'DHL' => 'https://www.dhl.de/de/privatkunden/pakete-empfangen/verfolgen.html?piececode='.rawurlencode($trackingNumber),
            'UPS' => 'https://www.ups.com/track?loc=de_DE&tracknum='.rawurlencode($trackingNumber),
            default => null,
        };
    }
}
