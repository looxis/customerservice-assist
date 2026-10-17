<?php

namespace App\Settings;

use App\Analysis\Prompt;
use App\Orders\OrderFormats;
use App\ProductForge\SkuDetector;
use Closure;

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
            'values' => [
                'title' => 'Kundengruppen und Werte',
                'description' => 'Kundengruppen, Kundenarten, Vertriebskanäle, EOCS-Kanalnamen, Fallkategorien und Vorgänge.',
                'route' => 'settings.values',
            ],
            'detection' => [
                'title' => 'Erkennungsregeln',
                'description' => 'Woran die App Bestellnummern und Artikelnummern in einem Ticket erkennt.',
                'route' => 'settings.detection',
            ],
            'prompts' => [
                'title' => 'Prompts und Modell',
                'description' => 'Die Anweisungen an die KI (sieben Prompts) und das Modell je KI-Aufruf.',
                'route' => 'settings.prompts',
            ],
        ];
    }

    /**
     * The prompts of the app: name of the file, title, when it is used.
     *
     * @return array<string, array{title: string, hint: string}>
     */
    public static function prompts(): array
    {
        return [
            'analysis' => ['title' => 'Analyse (Kern)', 'hint' => 'Bei jedem Vorschlag, zu einem Ticket und zu einer Frage. Die Zusätze unten werden bei Bedarf angehängt.'],
            'analysis-retrieval' => ['title' => 'Zusatz: gezielter Wissensabruf', 'hint' => 'Wird dem Kern angehängt, wenn der gezielte Wissensabruf aktiv ist.'],
            'analysis-question' => ['title' => 'Zusatz: Frage ohne Ticket', 'hint' => 'Wird dem Kern angehängt, wenn ein Vorschlag zu einer Frage („Frage stellen“) entsteht.'],
            'analysis-products' => ['title' => 'Zusatz: Produktinformationen', 'hint' => 'Wird dem Kern angehängt, wenn Produktinformationen aus dem Katalog vorliegen.'],
            'router' => ['title' => 'Router', 'hint' => 'Beim zusätzlichen Wissensvorschlag nach dem gezielten Abruf.'],
            'summary' => ['title' => 'Zusammenfassung', 'hint' => 'Bei der Zusammenfassung des bisherigen Verlaufs.'],
            'translation' => ['title' => 'Übersetzung', 'hint' => 'Bei jeder Übersetzung einer Nachricht und bei der Rückübersetzung des Antwortentwurfs.'],
        ];
    }

    /**
     * @return array<string, SettingGroup>
     */
    public static function all(): array
    {
        $models = [
            'model_analysis' => ['Analyse', 'Das Modell für den Vorschlag zu Ticket oder Frage.', fn (): string => (string) config('analysis.models.analysis')],
            'model_summary' => ['Zusammenfassung', 'Das Modell für die Zusammenfassung des bisherigen Verlaufs.', fn (): string => (string) config('analysis.models.summary')],
            'model_translation' => ['Übersetzung', 'Das Modell für Übersetzungen und die Rückübersetzung des Antwortentwurfs.', fn (): string => (string) config('analysis.models.translation')],
            'model_router' => ['Router', 'Das Modell für den zusätzlichen Wissensvorschlag.', fn (): string => (string) config('knowledge.router.model')],
        ];
        $groups = [];

        foreach ($models as $key => [$title, $hint, $default]) {
            $groups[] = new SettingGroup($key, 'prompts', 'Modell: '.$title, $hint.' Vor dem Speichern wird das Modell mit einem winzigen Testaufruf geprüft.', SettingGroup::MODEL, $default);
        }

        foreach (self::prompts() as $name => $prompt) {
            $groups[] = new SettingGroup(Prompt::settingKey($name), 'prompts', 'Prompt: '.$prompt['title'], $prompt['hint'], SettingGroup::PROMPT, fn (): array => ['text' => Prompt::fromFile($name)->text, 'base' => Prompt::fromFile($name)->version], base: fn (): string => Prompt::fromFile($name)->version);
        }

        $values = fn (string $key): Closure => fn (): array => app(KnowledgeValues::class)->defaults($key);
        $keyLabel = [['field' => 'key', 'label' => 'Schlüssel', 'type' => 'key'], ['field' => 'label', 'label' => 'Name', 'type' => 'text']];
        $groups[] = new SettingGroup('kn_customer_types', 'values', 'Kundenarten', 'Die Kundenart im Kopf der Wissensdokumente (`customer_types`). Der Schlüssel steht fest, sobald der Eintrag gespeichert ist; der Name ist jederzeit änderbar. Entfernen geht nur, wenn kein Dokument und keine Kundengruppe sie noch nennt.', SettingGroup::RECORDS, $values('kn_customer_types'), columns: $keyLabel);
        $groups[] = new SettingGroup('kn_sales_channels', 'values', 'Vertriebskanäle', 'Der Vertriebskanal im Kopf der Wissensdokumente (`sales_channels`). Entfernen geht nur, wenn kein Dokument, keine Kundengruppe und keine EOCS-Zuordnung ihn noch nennt.', SettingGroup::RECORDS, $values('kn_sales_channels'), columns: $keyLabel);
        $groups[] = new SettingGroup('kn_groups', 'values', 'Kundengruppen', 'Die Gruppen, die Mitarbeiter im Formular wählen (Reihenfolge wie hier). Eine neue Gruppe oder eine geänderte Kundenart/ein geänderter Kanal startet „in Vorbereitung“: Erst nach der Prüfung der Dokumente und der Freigabe ist sie wählbar. Gruppen werden deaktiviert, nicht entfernt.', SettingGroup::RECORDS, $values('kn_groups'), columns: [
            ['field' => 'key', 'label' => 'Schlüssel', 'type' => 'key'], ['field' => 'label', 'label' => 'Name', 'type' => 'text'],
            ['field' => 'customer_type', 'label' => 'Kundenart', 'type' => 'select', 'options' => 'types'], ['field' => 'sales_channel', 'label' => 'Vertriebskanal', 'type' => 'select', 'options' => 'channels'],
            ['field' => 'status', 'label' => 'Status', 'type' => 'status'],
        ]);
        $groups[] = new SettingGroup('kn_order_channels', 'values', 'EOCS-Kanalnamen', 'Wie EOCS den Kanal einer Bestellung nennt, und zu welchem Vertriebskanal er gehört. Daraus schlägt die App die Kundengruppe vor (Groß-/Kleinschreibung egal; unbekannte Namen schlagen „Noch unklar“ vor).', SettingGroup::RECORDS, $values('kn_order_channels'), columns: [
            ['field' => 'name', 'label' => 'Name in EOCS', 'type' => 'text'], ['field' => 'channel', 'label' => 'Vertriebskanal', 'type' => 'select', 'options' => 'channels'],
        ]);
        $groups[] = new SettingGroup('kn_categories', 'values', 'Fallkategorien', 'Die Art des Falls (`categories` in Dokumenten, Ergebnis der Analyse, Router). Entfernen geht nur, wenn kein Dokument sie noch nennt.', SettingGroup::RECORDS, $values('kn_categories'), columns: $keyLabel);
        $groups[] = new SettingGroup('kn_actions', 'values', 'Vorgänge', 'Die konkrete Maßnahme eines Falls (`actions`, `action` in Dokumenten und Arbeitsabläufen). Der Hinweis sagt der KI, wann sie den Vorgang nennt (nur bei Verwechslungsgefahr nötig). Entfernen geht nur, wenn kein Dokument ihn noch nennt.', SettingGroup::RECORDS, $values('kn_actions'), columns: [
            ['field' => 'key', 'label' => 'Schlüssel', 'type' => 'key'], ['field' => 'label', 'label' => 'Name', 'type' => 'text'], ['field' => 'hint', 'label' => 'Hinweis für die KI', 'type' => 'text'],
        ]);
        $groups[] = new SettingGroup('order_formats', 'detection', 'Bestellnummern-Formate', 'Von oben nach unten angewendet; das erste passende Format zählt. Aufbau: „#“ Ziffer, „?“ Buchstabe, „*“ Buchstabe oder Ziffer, alles andere fester Text. Die Beispiele werden vor dem Speichern geprüft.', SettingGroup::FORMATS, fn (): array => OrderFormats::defaults());
        $groups[] = new SettingGroup('claim_prefix', 'detection', 'Präfix für Reklamationsnummern', 'Reklamationsaufträge in EOCS heißen „R1-<Bestellnummer>“, „R2-…“ usw. Hier steht der Buchstabe vor der Zahl (1 bis 3 Buchstaben). Daraus erkennt die App die Originalbestellung und dass es ein Folgevorgang einer Reklamation ist.', SettingGroup::WORD, fn (): string => 'R');
        $groups[] = new SettingGroup('sku_keywords', 'detection', 'Artikelnummern: Stichwörter', 'Eine Zahl im Kundentext gilt nur dann als Artikelnummer (SKU), wenn direkt davor eines dieser Stichwörter steht. Groß-/Kleinschreibung egal.', SettingGroup::TEXTS, fn (): array => SkuDetector::defaultKeywords(), minLength: 2, maxLength: 40, allowEmpty: false);
        $groups[] = new SettingGroup('sku_digits', 'detection', 'Artikelnummern: Anzahl Ziffern', 'So viele Ziffern hat eine Artikelnummer bei LOOXIS.', SettingGroup::NUMBER, fn (): int => 5, max: 10, min: 3);
        $groups[] = new SettingGroup('sku_max', 'detection', 'Artikelnummern: Höchstzahl je Ticket', 'So viele verschiedene Artikelnummern aus dem Kundentext schlägt die App höchstens vor.', SettingGroup::NUMBER, fn (): int => 3, max: 10, min: 1);

        return self::$groups ??= collect([
            ...$groups,
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
