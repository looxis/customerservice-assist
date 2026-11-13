# PROJ-88: Live-Sendungsstatus im Antwortvorschlag

## Status: In Progress
**Created:** 2026-11-13
**Last Updated:** 2026-11-13

## Dependencies
- Requires: PROJ-9 (Fallanalyse), PROJ-82 (Sendungsverfolgung DHL/UPS, `ShipmentTracker`), PROJ-79 (Versandangaben im Prompt)

## Anlass
Ticket 2140364: Die automatische WISMO-Antwort war korrekt (Sendungsnummer vorhanden, Paket beim Paketdienst noch nicht bekannt). Die Kundin fragte zweimal nach. Beim Klick auf „Vorschlag erstellen“ lautete der Entwurf, wir könnten den Versand nicht beschleunigen und sie solle das Tracking prüfen. Das Paket war aber inzwischen zugestellt; der Mitarbeiter bemerkte es nur durch eigenes Nachsehen im Tracking-Link. Die Analyse kannte nur Carrier und Sendungsnummer aus EOCS, nicht den aktuellen Status.

## User Stories
- Als Kundenservice-Mitarbeiter möchte ich, dass „Vorschlag erstellen“ den aktuellen Sendungsstatus beim Paketdienst abfragt, damit der Entwurf nicht auf einem veralteten Stand beruht.
- Als Kundenservice-Mitarbeiter möchte ich, dass der Entwurf eine bereits erfolgte Zustellung nennt (mit Datum und Zustellart) und den Tracking-Link noch einmal angibt, damit ich nicht selbst nachsehen muss.

## Acceptance Criteria
- Für jede Sendung mit Sendungsnummer (DHL, UPS; höchstens drei je Bestellung) fragt die Analyse beim Erstellen den Status über `ShipmentTracker` ab, mit der Postleitzahl der Lieferadresse.
- Der Status geht als Zeile „Aktueller Status laut Paketdienst“ (Fakten aus `TrackingStatus::facts()`, inklusive `tracking_link`) in den Abschnitt „Bestellungen“ des Prompts.
- Der Prompt `analysis` hat eine Regel „Sendungsstatus“: der Live-Stand hat Vorrang vor älteren Aussagen im Verlauf; bei Zustellung nennt der Entwurf Datum und Zustellart, verspricht keine Beschleunigung, nennt Sendungsnummer und Link; bei Zustellproblem keine Zustelldetails, interne Klärung.
- Schlägt die Abfrage fehl, ist der Carrier ein anderer oder fehlt die Sendungsnummer, läuft die Analyse wie bisher weiter (keine Statuszeile).
- Pro Analyse wird der Paketdienst einmal je Sendung abgefragt (Nachladerunden verwenden die bereits erzeugten Prompt-Teile).

## Out of Scope
- Live-Abfrage für Sendungen von Reklamationsaufträgen
- Code-seitige Absicherung des Entwurfs (Nummer/Link muss im Text stehen) wie bei WISMO
- Anzeige des Live-Status in der Ticketansicht

## Implementation Notes
- `CaseAnalyzer::carrierStatusLines()`, Aufruf in `orders()`; `ShipmentTracker` per Konstruktor.
- Prompt-Version `analysis-2026-11-13.1`.
- Tests: `tests/Feature/PROJ-88-LiveShipmentStatusTest.php`; `PROJ-36-AnalysisIntegrationTest` bindet einen neutralen Tracker, weil dort Stray-Requests verboten sind.
- Datenschutz: wie bei WISMO gehen Statusfelder (inkl. `received_by`) an das Modell; Adressen nicht.
