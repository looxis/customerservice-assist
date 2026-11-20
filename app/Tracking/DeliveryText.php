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

        if ($t === '' || self::isPickupReminder($t)) {
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
     * A text that asks the customer to collect the shipment soon and only warns that it may go back otherwise ("Holen
     * Sie Ihr Paket bald ab … eventuell an den Absender zurückgesendet"). The shipment still waits at the pickup point,
     * so this is no delivery problem. A text that says the return is under way or the time is over is not a reminder.
     */
    public static function isPickupReminder(?string $text): bool
    {
        $t = mb_strtolower((string) $text);

        $asksToCollect = preg_match('/holen sie .+ ab\b|abholen|please (pick|collect)|(pick|collect) (it |your \w+ )?up|collect (it|your)/u', $t) === 1;
        $onlyWarns = preg_match('/eventuell|möglicherweise|andernfalls|otherwise|may be (returned|sent back)|might be (returned|sent back)/u', $t) === 1;
        $overOrOnTheWay = preg_match('/abgelaufen|expired|wird an (den )?absender zurück|wurde an (den )?absender zurück/u', $t) === 1;

        return $asksToCollect && $onlyWarns && ! $overOrOnTheWay;
    }

    /**
     * A text that says the shipment waits at a pickup point.
     */
    public static function isPickupReady(?string $text): bool
    {
        return self::isPickupReminder($text) || preg_match('/abholung|abholstation|abholbereit|paketshop|packstation|filiale|retail.?outlet|ready for pickup|available for pickup|hold for pickup|ready for collection/i', (string) $text) === 1;
    }

    /**
     * The kinds of a tracking text that matter for the course of a shipment (PROJ-99); scans, sorting and loading say
     * nothing and get none. One text can have several kinds ("Adressfehler … wird an den Absender zurückgesandt").
     *
     * @return list<string> Of: return, not_collected, address, refused, absent, attempt, special, pickup, notified, reminder.
     */
    public static function kinds(?string $text): array
    {
        $t = mb_strtolower(self::strip($text));

        if ($t === '') {
            return [];
        }

        if (self::isPickupReminder($t)) {
            return ['reminder', 'pickup'];
        }

        $kinds = [];
        $checks = [
            'return' => '/zurück\w*.{0,30}absender|absender.{0,40}zurück|zurück(gesendet|gesandt|geschickt)\b|return(ed)? to (the )?sender|erhält das paket zurück/u',
            'not_collected' => '/(lagerfrist|abholfrist|aufbewahrungs\w*).{0,60}(abgelaufen|überschritten|nicht)|nicht.{0,40}abgeholt|ablaufdatum nicht|not (been )?(collected|picked up)/u',
            'address' => '/adressfehler|adresse.{0,40}(nicht|fehlerhaft|unvollständig|falsch)|(empfänger|adressat).{0,60}nicht (gefunden|ermittelt|ermittelbar)|adressat.{0,20}unbekannt|beschriftung der klingel|verzogen|(incorrect|incomplete|wrong) address|address (is )?(incorrect|incomplete|unknown)|receiver moved/u',
            'refused' => '/verweiger|refused/u',
            'absent' => '/nicht anwesend|nicht angetroffen|niemand angetroffen|geschäft war.{0,30}geschlossen|not (at )?home|receiver (was )?not (available|present)/u',
            'attempt' => '/zustellung.{0,40}nicht (möglich|erfolgreich)|zustellung nicht mögl|nicht zustellen|zustellversuch|delivery attempt|unable to deliver/u',
            'special' => '/nachentgelt|versandbedingungen|beschädig|verlust|verloren|übersteigt den maximalen|zollanmeldung fehlt|\bdamaged\b|\blost\b/u',
            'pickup' => '/abholung|abholstation|abholbereit|paketshop|packstation|filiale|access point|abholinfos|retail.?outlet|ready for pickup|available for pickup/u',
            'notified' => '/benachrichtig|abholinfos|notice left|notified/u',
        ];

        foreach ($checks as $kind => $pattern) {
            if (preg_match($pattern, $t) === 1) {
                $kinds[] = $kind;
            }
        }

        // A delay on DHL's side ("wegen unerwarteter Umstände … nicht zugestellt") is no attempt at the customer's door.
        if (str_contains($t, 'unerwartete')) {
            $kinds = array_values(array_diff($kinds, ['attempt']));
        }

        return $kinds;
    }

    /**
     * The days of the storage period named in a text ("innerhalb der siebentägigen Lagerfrist", "nach 7 Tagen"), or null.
     */
    public static function storageDays(?string $text): ?int
    {
        $t = mb_strtolower((string) $text);
        $words = ['drei' => 3, 'vier' => 4, 'fünf' => 5, 'sechs' => 6, 'sieben' => 7, 'acht' => 8, 'neun' => 9, 'zehn' => 10, 'vierzehn' => 14];

        if (preg_match('/(drei|vier|fünf|sechs|sieben|acht|neun|zehn|vierzehn)tägig/u', $t, $match) === 1) {
            return $words[$match[1]];
        }

        if (preg_match('/(\d{1,2})[\s-]*(tägig|tage|werktag|days)/u', $t, $match) === 1 && preg_match('/lager|aufbewahr|abhol|storage|hold/u', $t) === 1) {
            return (int) $match[1];
        }

        return null;
    }
}
