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
    case TechnischerSupport = 'technischer-support';
    case ProduktFrage = 'produkt-frage';
    case Spam = 'spam';

    public function label(): string
    {
        return match ($this) {
            self::Wismo => 'WISMO (Lieferstatus)',
            self::Reklamation => 'Reklamation',
            self::Angebotsanfrage => 'Angebotsanfrage',
            self::Bestellung => 'Bestellung',
            self::Storno => 'Storno / Änderung',
            self::TechnischerSupport => 'Technischer Support',
            self::ProduktFrage => 'Produktfrage',
            self::Spam => 'Spam',
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
            self::Storno => 'Storno oder Änderung ohne Lieferproblem',
            self::TechnischerSupport => 'Online-Shop: Login, Konfigurator, Upload',
            self::ProduktFrage => 'Funktion oder Eigenschaft eines Produkts',
            self::Spam => 'Unerwünschte Werbung, Phishing, Massenmails',
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
