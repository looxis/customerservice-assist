# PROJ-88: Live-Sendungsstatus im Antwortvorschlag

## Status: In Progress
**Created:** 2026-11-13
**Last Updated:** 2026-11-13

## Dependencies
- Requires: PROJ-9 (Fallanalyse), PROJ-82 (Sendungsverfolgung DHL/UPS, `ShipmentTracker`), PROJ-79 (Versandangaben im Prompt)

## Anlass
Ticket 2140364: Die automatische WISMO-Antwort war korrekt (Sendungsnummer vorhanden, Paket beim Paketdienst noch nicht bekannt). Die Kundin fragte zweimal nach. Beim Klick auf „Vorschlag erstellen“ lautete der Entwurf, wir könnten den Versand nicht beschleunigen und sie solle das Tracking prüfen. Das Paket war aber inzwischen zugestellt; der Mitarbeiter bemerkte es nur durch eigenes Nachsehen im Tracking-Link. Die Analyse kannte nur Carrier und Sendungsnummer aus EOCS, nicht den aktuellen Status.

## Anlass 2 (2026-11-13)
Ticket 2140387: eine Reklamation, bei der es nicht um den Versand geht. Die Dauer-Abfrage aus der ersten Fassung brachte trotzdem „zugestellt“ in Prompt und Entwurf. Deshalb fragt die KI den Status nur noch bei Bedarf ab (Werkzeugaufruf), mit der Einordnung der ersten Kundennachricht (PROJ-78/80) als Hinweis.

## User Stories
- Als Kundenservice-Mitarbeiter möchte ich, dass „Vorschlag erstellen“ den aktuellen Sendungsstatus beim Paketdienst abfragt, damit der Entwurf nicht auf einem veralteten Stand beruht.
- Als Kundenservice-Mitarbeiter möchte ich, dass der Entwurf eine bereits erfolgte Zustellung nennt (mit Datum und Zustellart) und den Tracking-Link noch einmal angibt, damit ich nicht selbst nachsehen muss.

## Acceptance Criteria
- Der Prompt enthält keinen Sendungsstatus. Die Analyse bietet der KI ein Werkzeug „Sendungsstatus abfragen“ an (nur bei Tickets und nur, wenn eine Bestellung eine Sendung mit Sendungsnummer hat).
- Die KI ruft es nur auf, wenn die aktuelle Anfrage Verbleib, Lieferzeit, Liefertermin oder Zustellung betrifft; nicht bei Reklamationen von Mängeln oder Beschädigungen, Produktfragen, Angeboten, Änderungen, Stornos, Zahlungsfragen und Fragen ohne Ticket.
- Der Abschnitt „Fall“ nennt die Einordnung der ersten Kundennachricht (Entscheidung vor Vorhersage; nicht bei Testtickets, „unsicher“ oder ohne Einordnung). Ist sie WISMO, gehen vermutlich auch die Folgenachrichten um die Lieferung.
- Das Werkzeug fragt über `ShipmentTracker` je Sendung (höchstens drei je Bestellung) mit der Postleitzahl der Lieferadresse und antwortet mit `TrackingStatus::facts()` inklusive `tracking_link`. Fehler und andere Paketdienste ergeben „nicht abrufbar“; die Analyse läuft immer weiter.
- Die Antwort wird je Analyse gemerkt: Nachladerunden fragen den Paketdienst nicht erneut.
- Prompt `analysis` (Regel „Sendungsstatus“): Bei Zustellung nennt der Entwurf Datum und Zustellart, verspricht keine Beschleunigung, nennt Sendungsnummer und Link; bei Zustellproblem intern klären; geht es nicht um Verbleib, erwähnt der Entwurf Versand und Zustellung nicht.

- Abholort: Hat der Status einen Abholort (`pickup_location`), nennt der Entwurf ihn mit Adresse; eine Abholerinnerung des Paketdienstes („Holen Sie Ihr Paket bald ab … eventuell zurückgesendet“) gilt nicht als Zustellproblem (`DeliveryText::isPickupReminder`), sonst würde der Abholort ausgeblendet (Anlass Ticket 2140398). Eine laufende Rücksendung, eine abgelaufene Aufbewahrungszeit und „nicht abgeholt“ bleiben Probleme.

## Out of Scope
- Live-Abfrage für Sendungen von Reklamationsaufträgen
- Code-seitige Absicherung des Entwurfs (Nummer/Link muss im Text stehen) wie bei WISMO
- Anzeige des Live-Status in der Ticketansicht

## Implementation Notes
- `app/Analysis/Tools/ShipmentStatusTool.php` (Werkzeug, Zwischenspeicher je Instanz), `CaseAgent` implementiert `HasTools`, `CaseAnalyzer::analyze()` erzeugt eine Instanz je Analyse, `CaseAnalyzer::classificationLine()` liefert den Einordnungshinweis.
- Prompt-Version `analysis-2026-11-13.3`. UPS liefert den Abholort in `accessPointInformation` (Firma, Straße, PLZ, Ort, Land); am echten Abruf von 2140398 geprüft.
- Tests: `tests/Feature/PROJ-88-LiveShipmentStatusTest.php`; `PROJ-36-AnalysisIntegrationTest` bindet einen neutralen Tracker.
- Ob die KI das Werkzeug richtig einsetzt, ist modellabhängig; im Log steht „Sendungsstatus abgefragt“ je Abfrage.
- Datenschutz: wie bei WISMO gehen Statusfelder (inkl. `received_by`) an das Modell, sofern abgefragt; Adressen nicht.
