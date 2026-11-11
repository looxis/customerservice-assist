<?php

namespace App\Tracking;

/**
 * Words in tracking texts that tell of a delivery problem or a pickup (PROJ-82), the same rules the n8n workflow used.
 */
final class DeliveryText
{
    public static function strip(?string $text): string
    {
        return trim(html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * A text that says the shipment goes back, was refused, cannot be delivered or was not collected.
     */
    public static function isProblem(?string $text): bool
    {
        $t = mb_strtolower((string) $text);

        if ($t === '') {
            return false;
        }

        return (str_contains($t, 'nicht möglich') && str_contains($t, 'zustellung'))
            || (str_contains($t, 'zurück') && str_contains($t, 'absender'))
            || (str_contains($t, 'nicht gefunden') && (str_contains($t, 'empfänger') || str_contains($t, 'adresse')))
            || str_contains($t, 'abholfrist')
            || str_contains($t, 'nicht abgeholt')
            || str_contains($t, 'annahme verweigert')
            || str_contains($t, 'unzustellbar') || str_contains($t, 'nicht zustellbar')
            || (str_contains($t, 'adressat') && (str_contains($t, 'unbekannt') || str_contains($t, 'nicht ermitt')))
            || preg_match('/return(ed)? to sender|unable to deliver|delivery attempt failed|not collected|refused/', $t) === 1;
    }

    /**
     * A text that says the shipment waits at a pickup point.
     */
    public static function isPickupReady(?string $text): bool
    {
        return preg_match('/abholung|abholstation|abholbereit|paketshop|packstation|filiale|retail.?outlet|ready for pickup|available for pickup|hold for pickup|ready for collection/i', (string) $text) === 1;
    }
}
