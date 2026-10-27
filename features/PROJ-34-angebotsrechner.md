# PROJ-34: Angebotsrechner („Angebot berechnen“)

**Status:** In Progress · **Zuletzt geändert:** 2026-10-27 · **Abhängigkeiten:** PROJ-9, PROJ-11, PROJ-58

## Anlass
Ein vibe-codierter HTML-Rabattrechner (Daten nur im Browser, Austausch per Backup-JSON) soll fester Teil der App werden. Er ersetzt den Roadmap-Punkt „Angebotskalkulation“ in kleinerem Zuschnitt: Berechnung durch die App, aber noch ohne Staffeln und Preislisten je Kundengruppe.

## User Stories
- Als Mitarbeiter öffne ich in der Navigation „Angebot berechnen“, wähle einen Artikel, gebe Menge und Rabatt (oder Kundenpreis) ein und sehe Marge, Deckungsbeitrag und die Rabattgrenzen bei Zielmarge und bis Break-even.
- Als Mitarbeiter stelle ich aus den berechneten Artikeln ein Angebot zusammen (Kundendaten, Bemerkung), speichere es mit fortlaufender Nummer `AN-YYYYMMDD-NNN` (sieben Tage gültig) und drucke es als PDF.
- Als Admin pflege ich die internen Kosten je Artikel (Grundartikel × Preis, Maschinenzeit, Arbeitszeit, Verpackung, Versand, Sonstiges, Zielmarge, MwSt.). Alle anderen sehen die Kosten, ändern sie aber nicht.
- Bei einer Mengen- oder Angebotsanfrage weist der Ablauf „Mengenanfrage im Angebotsrechner berechnen“ (PROCEDURE-009) per Knopf auf den Rechner hin; die Ticketnummer wird am Angebot vermerkt.

## Entscheidungen
- Speicherung in der Datenbank (`quote_articles`, `quote_offers`), gemeinsam für alle. Kein Browser-Speicher, kein Backup-Knopf.
- Zugriff: ansehen alle, Kosten ändern nur Admins (`StaffDirectory::isAdmin`, serverseitig im Form Request geprüft).
- Rechenlogik maßgeblich in `App\Quotes\QuoteCalculator`; der Browser spiegelt die Formeln nur für die Live-Anzeige. Angebotssummen werden beim Anzeigen/Drucken immer neu berechnet.
- Artikel ohne Einstandspreis oder Maschinenzeit zeigen „Kosten unvollständig“ statt einer Marge.
- Import der alten Sicherung einmalig mit `sail artisan quotes:import <datei.json>` (wiederholbar). Automatische Vorschläge der alten Oberfläche (nicht bestätigte Zuordnungen) werden nicht übernommen. Die Sicherungsdatei gehört nicht ins Repository (enthält Einstandspreise).
- Nicht übernommen: Shop-Import über fremde Proxys, den Grundartikel-Katalog mit fehlerhaften Preisen, das Fenster „Artikel nacheinander prüfen“.

## Implementierung
Migrationen/Modelle `QuoteArticle`, `QuoteOffer`; `QuoteController` mit Form Requests `SaveQuoteOfferRequest`, `UpdateQuoteArticleCostsRequest`; Seite `resources/views/quote/`, Alpine-Komponente `resources/js/quote.js`; Link-Schlüssel `angebotsrechner` in `CaseLinks`; Arbeitsablauf `knowledge/procedures/procedure-009-…` (Status `draft`, Vorgang `quote`); Tests `PROJ-34-QuotePageTest`, `PROJ-34-QuoteCalculatorTest`.

## Offen
- Hinweis: PROCESS-010 sagt „Es wird keine eigene Angebotsnummer vergeben“; der Rechner vergibt eine interne Nummer. Wissenstext bewusst nicht geändert, bitte entscheiden.
- Folgeschritte: Preise/Preislisten aus EOCS (`items:prices`), Staffeln und Rabatte je Kundengruppe, Prüfmodus für fehlende Kosten, Zugriff auf Angebote nach Login (PROJ-15).
