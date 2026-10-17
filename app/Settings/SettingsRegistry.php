<?php

namespace App\Settings;

/**
 * All settings groups and pages of the app (PROJ-27). A later spec adds its groups here; the settings
 * page, the storage, the history and the reset then work for them without further code.
 */
final class SettingsRegistry
{
    /**
     * @var array<string, SettingGroup>|null
     */
    private static ?array $groups = null;

    /**
     * The pages under "Einstellungen" (the overview lists them).
     *
     * @return array<string, array{title: string, description: string, route: string}>
     */
    public static function pages(): array
    {
        return [
            'mail' => [
                'title' => 'Mail-Anzeige',
                'description' => 'Wie Nachrichten im Ticketverlauf bereinigt werden: Links, Signaturen, Fußtexte, Zitate und die Amazon-Nachricht.',
                'route' => 'settings.mail',
            ],
        ];
    }

    /**
     * @return array<string, SettingGroup>
     */
    public static function all(): array
    {
        return self::$groups ??= collect([
            new SettingGroup('mail_hosts', 'mail', 'Zugelassene Adressen', 'Links zu diesen Adressen (samt Unterseiten wie www.) bleiben als Text sichtbar oder klickbar. Jeder andere Link wird durch „[Link entfernt]“ ersetzt. „*“ steht am Ende für eine Länderendung (.de, .it, .co.uk); klickbar gilt nur für https.', SettingGroup::HOSTS, fn (): array => MailDefaults::hosts()),
            new SettingGroup('mail_signatures', 'mail', 'Unsere Signaturen', 'Unsere eigenen Signaturen ohne Zammad-Markierung (z. B. bei Amazon), die am Ende unserer Nachrichten ausgeblendet werden. Eine Variante je Feld, mehrere Zeilen erlaubt.', SettingGroup::LINES, fn (): array => MailDefaults::signatures(), maxLength: 600),
            new SettingGroup('mail_footers', 'mail', 'Fußtexte', 'Ab diesem Text wird der Rest einer Mail als Fußbereich ausgeblendet (Groß-/Kleinschreibung und Zeilenumbrüche egal), z. B. der Hinweistext von Amazon.', SettingGroup::TEXTS, fn (): array => MailDefaults::footers(), minLength: 15),
            new SettingGroup('mail_closings', 'mail', 'Grußformeln', 'Steht eine dieser Formeln allein in einer Zeile (optional mit Name), beginnt danach die Signatur des Kunden und wird eingeklappt. Umlaute und „ss“/„ß“ gelten in beiden Schreibweisen.', SettingGroup::TEXTS, fn (): array => MailDefaults::closings(), minLength: 2, maxLength: 80),
            new SettingGroup('mail_signature_lines', 'mail', 'Sichtbare Zeilen nach der Grußformel', 'So viele Zeilen nach der Grußformel (meist Name und Firma) bleiben sichtbar, der Rest der Signatur wird eingeklappt.', SettingGroup::NUMBER, fn (): int => MailDefaults::signatureLines(), max: 5),
            new SettingGroup('mail_quote_headers', 'mail', 'Zitat-Köpfe', 'Beginnt eine Zeile so, startet ab dort ein zitierter früherer Verlauf. „…“ steht für beliebigen Text, z. B. „Am … schrieb …:“.', SettingGroup::TEXTS, fn (): array => MailDefaults::quoteHeaders(), minLength: 4, maxLength: 150, wildcard: true),
            new SettingGroup('mail_quote_markers', 'mail', 'Technische Zitat-Markierungen', 'Namen (CSS-Klassen) in der Mail, an denen Mail-Programme zitierte Blöcke kennzeichnen. Nur für Fortgeschrittene.', SettingGroup::TEXTS, fn (): array => MailDefaults::quoteMarkers(), maxLength: 60, marker: true),
            new SettingGroup('mail_amazon_intros', 'mail', 'Amazon-Nachricht: Einleitungssätze', 'Der Satz, mit dem Amazon eine Käufernachricht einleitet („Du hast eine Nachricht erhalten“), in allen Sprachen. Fehlt er, erkennt die App die Amazon-Nachricht nicht mehr.', SettingGroup::TEXTS, fn (): array => MailDefaults::amazonIntros(), minLength: 5, maxLength: 120, allowEmpty: false),
            new SettingGroup('mail_amazon_order_labels', 'mail', 'Amazon-Nachricht: Bezeichnung der Bestellnummer', 'Das Wort vor der Bestellnummer („Bestellnummer“, „Order ID“) in allen Sprachen.', SettingGroup::TEXTS, fn (): array => MailDefaults::amazonOrderLabels(), minLength: 3, maxLength: 60, allowEmpty: false),
            new SettingGroup('mail_amazon_message_labels', 'mail', 'Amazon-Nachricht: Bezeichnung der Nachricht', 'Das Wort vor dem Text des Käufers („Nachricht:“, „Message:“) in allen Sprachen, ohne den Doppelpunkt.', SettingGroup::TEXTS, fn (): array => MailDefaults::amazonMessageLabels(), minLength: 3, maxLength: 60, allowEmpty: false),
        ])->keyBy('key')->all();
    }

    public static function group(string $key): ?SettingGroup
    {
        return self::all()[$key] ?? null;
    }

    /**
     * @return array<string, SettingGroup>
     */
    public static function forPage(string $page): array
    {
        return array_filter(self::all(), fn (SettingGroup $group): bool => $group->page === $page);
    }

    public static function forget(): void
    {
        self::$groups = null;
    }
}
