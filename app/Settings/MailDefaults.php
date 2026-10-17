<?php

namespace App\Settings;

/**
 * The defaults of the group "Mail-Anzeige" (PROJ-27): the values that applied before the settings page existed.
 * Hosts, signatures and footers still come from `config/services.php`, so nothing changes without a stored value.
 */
final class MailDefaults
{
    /**
     * @return list<array{host: string, mode: string}>
     */
    public static function hosts(): array
    {
        return [
            ...array_map(fn (string $host): array => ['host' => $host, 'mode' => 'text'], (array) config('services.zammad.allowed_link_hosts', [])),
            ...array_map(fn (string $host): array => ['host' => $host, 'mode' => 'clickable'], (array) config('services.zammad.clickable_link_hosts', [])),
        ];
    }

    /**
     * @return list<string>
     */
    public static function signatures(): array
    {
        return array_values((array) config('services.zammad.signatures', []));
    }

    /**
     * @return list<string>
     */
    public static function footers(): array
    {
        return array_values((array) config('services.zammad.footers', []));
    }

    /**
     * Closing phrases after which a customer's signature follows.
     *
     * @return list<string>
     */
    public static function closings(): array
    {
        return [
            'mit freundlichen Grüßen', 'mit freundlichem Gruß', 'freundliche Grüße', 'viele Grüße', 'liebe Grüße', 'beste Grüße', 'herzliche Grüße', 'schöne Grüße', 'Grüße', 'Gruß', 'mfg', 'lg', 'vg',
            'best regards', 'kind regards', 'warm regards', 'kindest regards', 'regards', 'best wishes', 'yours sincerely', 'sincerely', 'thanks and regards', 'cheers',
            'met vriendelijke groet', 'met vriendelijke groeten', 'vriendelijke groet', 'vriendelijke groeten', 'hartelijke groet', 'hartelijke groeten', 'groet', 'groeten', 'groetjes',
            'bien cordialement', 'cordialement', 'bien à vous', 'bien a vous', 'meilleures salutations', 'sincères salutations', 'sinceres salutations', 'salutations',
            'cordiali saluti', 'distinti saluti', 'saluti', 'un saluto',
            'saludos', 'atentamente', 'un saludo',
        ];
    }

    /**
     * @return list<string>
     */
    public static function quoteHeaders(): array
    {
        return [
            'Am … schrieb …:', 'On … wrote:', 'Op … heeft … geschreven:', 'Op … schreef …:', 'Le … a écrit :',
            '-- Ursprüngliche Nachricht --', '-- Original Message --', '-- Weitergeleitete Nachricht --', '-- Forwarded message --', '-- Oorspronkelijk bericht --', '-- Doorgestuurd bericht --', "-- Message d'origine --",
            'Anfang der weitergeleiteten Nachricht:', 'Begin forwarded message:', 'Begin doorgestuurd bericht:', 'Début du message réexpédié:',
            'Von: … Gesendet:', 'Von: … Datum:', 'From: … Sent:', 'From: … Date:', 'Van: … Verzonden:', 'Van: … Datum:', 'De: … Envoyé:', 'De: … Date:',
        ];
    }

    /**
     * @return list<string>
     */
    public static function quoteMarkers(): array
    {
        return ['js-signatureMarker', 'gmail_quote', 'moz-cite-prefix', 'divRplyFwdMsg', 'appendonsend', 'OutlookMessageHeader', 'yahoo_quoted'];
    }

    public static function signatureLines(): int
    {
        return 2;
    }

    /**
     * @return list<string>
     */
    public static function amazonIntros(): array
    {
        return ['Du hast eine Nachricht erhalten', 'You have received a message'];
    }

    /**
     * @return list<string>
     */
    public static function amazonOrderLabels(): array
    {
        return ['Bestellnummer', 'Order ID', 'Order number'];
    }

    /**
     * @return list<string>
     */
    public static function amazonMessageLabels(): array
    {
        return ['Nachricht', 'Message'];
    }
}
