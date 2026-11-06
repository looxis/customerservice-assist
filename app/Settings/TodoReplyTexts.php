<?php

namespace App\Settings;

/**
 * The defaults of the prepared replies of the frequent ToDos (PROJ-70) and the filling of their placeholders.
 * The texts themselves are settings; the placeholders for what is known later stay in the text.
 */
class TodoReplyTexts
{
    public const array SHIPPING_PLACEHOLDERS = ['Anrede', 'Name', 'Bestellung/Reklamation', 'Nummer', 'Sendungsnummer', 'Carrier'];

    public const array REFUND_PLACEHOLDERS = ['Anrede', 'Name', 'Bestellung/Reklamation', 'Nummer', 'Betrag'];

    public static function shipping(): string
    {
        return "[Anrede] [Name],\n\nwie besprochen wurde Ihre [Bestellung/Reklamation] mit der Nummer [Nummer] verschickt. Die Sendungsnummer zur Sendungsverfolgung lautet: [Sendungsnummer]\n\nMit dieser Nummer können Sie den Versand auf der Webseite des Versanddienstleisters [Carrier] verfolgen.";
    }

    public static function refund(): string
    {
        return "[Anrede] [Name],\n\nwir haben die Erstattung in Höhe von [Betrag] zu Ihrer [Bestellung/Reklamation] mit der Nummer [Nummer] veranlasst.\n\nBei Fragen melden Sie sich gerne.";
    }

    /**
     * Puts what is known into the text: the greeting, the customer's name and the order or claim. Unknown values
     * leave their placeholder in the text; only a missing name takes its placeholder away together with the space.
     *
     * @param  array{name?: string|null, kind?: string|null, number?: string|null}  $context
     */
    public static function fill(string $template, array $context): string
    {
        $name = trim((string) ($context['name'] ?? ''));
        $number = trim((string) ($context['number'] ?? ''));
        $pairs = ['[Anrede]' => 'Guten Tag'];

        $text = $name === '' ? str_replace(' [Name]', '', $template) : str_replace('[Name]', $name, $template);

        if ($number !== '') {
            $pairs['[Nummer]'] = $number;
            $pairs['[Bestellung/Reklamation]'] = ($context['kind'] ?? 'order') === 'claim' ? 'Reklamation' : 'Bestellung';
        }

        return strtr($text, $pairs);
    }
}
