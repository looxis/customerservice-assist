<?php

namespace App\Returns;

/**
 * The fixed texts that go with a return label (PROJ-64): the instructions for the customer, the greeting for a
 * mail of its own and its subject. Written by the app, never by the language model. The wording follows DHL's
 * hints for customers; the pick-up is not ordered, so the customer takes the parcel to a branch or parcel shop.
 */
final class ReturnInstructions
{
    /**
     * The steps for the customer, to add to a reply.
     */
    public static function block(string $shipmentNo): string
    {
        return implode("\n", [
            'So senden Sie die Ware zurück:',
            '1. Verpacken Sie die Ware sicher in einem stabilen Karton.',
            '2. Drucken Sie das angehängte DHL-Retourenlabel (PDF) gut lesbar aus und kleben Sie es auf die größte Fläche des Pakets. Alte Versandetiketten und Barcodes müssen entfernt oder vollständig abgedeckt sein.',
            '3. Geben Sie das Paket in einer DHL-Filiale oder einem DHL-Paketshop ab. Eine Abholung ist nicht beauftragt.',
            '4. Bewahren Sie den Einlieferungsbeleg auf.',
            '',
            'Retouren-Sendungsnummer: '.$shipmentNo,
        ]);
    }

    /**
     * The text of a mail that only carries the label.
     */
    public static function mail(string $shipmentNo, string $orderNumber): string
    {
        return "Guten Tag,\n\nanbei erhalten Sie das vorfrankierte DHL-Retourenlabel für die Rücksendung zu Ihrer Bestellung {$orderNumber}.\n\n".self::block($shipmentNo)."\n\nBei Fragen melden Sie sich gern bei uns.";
    }

    public static function subject(string $orderNumber): string
    {
        return 'DHL Retoure – Bestellung '.$orderNumber;
    }

    public static function filename(string $orderNumber): string
    {
        return 'DHL-Retoure-'.trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '_', $orderNumber), '_').'.pdf';
    }
}
