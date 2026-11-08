<?php

namespace App\Labeling;

/**
 * The fixed categories of the ticket tagging (PROJ-78). The value is the part of the
 * Zammad tag after "train:" or "test:"; spam is tagged plainly "spam".
 */
enum LabelCategory: string
{
    case Wismo = 'wismo';
    case Reklamation = 'reklamation';
    case Angebotsanfrage = 'angebotsanfrage';
    case Bestellung = 'bestellung';
    case Storno = 'storno';
    case Adressaenderung = 'adressaenderung';
    case Zahlung = 'zahlung';
    case TechnischerSupport = 'technischer-support';
    case ProduktFrage = 'produkt-frage';
    case Spam = 'spam';
    case Sonstiges = 'sonstiges';

    public function label(): string
    {
        return match ($this) {
            self::Wismo => 'WISMO (Lieferstatus)',
            self::Reklamation => 'Reklamation',
            self::Angebotsanfrage => 'Angebotsanfrage',
            self::Bestellung => 'Bestellung',
            self::Storno => 'Storno / Änderung der Bestellung',
            self::Adressaenderung => 'Adressänderung',
            self::Zahlung => 'Zahlung / Rechnung',
            self::TechnischerSupport => 'Technischer Support',
            self::ProduktFrage => 'Produktfrage',
            self::Spam => 'Spam',
            self::Sonstiges => 'Sonstiges',
        };
    }

    /**
     * Short rule of thumb for the review, the full rules are in the prompt.
     */
    public function description(): string
    {
        return match ($this) {
            self::Wismo => 'Lieferstatus, auch „stornieren, weil nicht geliefert“',
            self::Reklamation => 'Mangel oder Beschwerde zu gelieferter Ware',
            self::Angebotsanfrage => 'Angebot, Preis oder Menge',
            self::Bestellung => 'Nur eindeutige Bestellung („bitte fertigen“)',
            self::Storno => 'Artikel, Menge, Motiv oder Konfiguration ändern oder stornieren, ohne Lieferproblem',
            self::Adressaenderung => 'Lieferadresse ändern oder korrigieren, auch Sendung auf dem Rückweg',
            self::Zahlung => 'Zahlung, Zahlungsart, Mahnung, Erstattungsstand, Rechnung; keine Erstattungsforderung',
            self::TechnischerSupport => 'Online-Shop: Login, Konfigurator, Upload',
            self::ProduktFrage => 'Funktion oder Eigenschaft eines Produkts',
            self::Spam => 'Unerwünschte Werbung, Phishing, Massenmails',
            self::Sonstiges => 'Passt in keine andere Kategorie (Bewerbung, Abwesenheitsnotiz, reiner Dank)',
        };
    }

    /**
     * The key that picks the category in the review: its position for the first nine, "s" for spam, "0" for the rest.
     */
    public function key(): string
    {
        return match ($this) {
            self::Spam => 's',
            self::Sonstiges => '0',
            default => (string) (array_search($this, self::cases(), true) + 1),
        };
    }

    public function tag(bool $test = false): string
    {
        if ($this === self::Spam) {
            return $test ? 'test:spam' : 'spam';
        }

        return ($test ? 'test:' : 'train:').$this->value;
    }
}
