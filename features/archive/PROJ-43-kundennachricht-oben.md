# PROJ-43: Kundennachricht oben auf der Ticketseite

## Status: Approved
**Created:** 2026-10-13
**Last Updated:** 2026-10-14

## Dependencies
- Requires: PROJ-6 (Ticketseite und Verlauf), PROJ-42 (Pflichtangaben im Formular).
- Verwandt: PROJ-28 (Übersetzung), PROJ-32 (Testmodus).

## Ausgangslage
Für die Pflichtangaben Kundengruppe und Produkt muss der Mitarbeiter zuerst lesen, worum es geht. Die Kundennachricht stand aber nur im Verlauf ganz unten, oben das Eingabefeld „Ticket aus Zammad“, und die Seite sprang beim Laden automatisch zur neuesten Nachricht im Verlauf.

## Entscheidungen
- Ganz oben steht die **letzte Kundennachricht** als Kopie der Nachricht im Verlauf (bei einer einzigen Nachricht die erste; ohne Kundennachricht die letzte Nachricht des Tickets). Bei mehreren Nachrichten verlinkt ein Hinweis zum Verlauf unten („Der ganze Verlauf (N Nachrichten) steht unten“). Unsere Antworten und interne Notizen erscheinen nur unten.
- Das Eingabefeld „Ticket aus Zammad“ entfällt auf der Ticketseite; ein weiteres Ticket ruft man über „Ticket analysieren“ ab. Konnte das Ticket nicht geladen werden, bleibt das Feld (für „Erneut versuchen“).
- Das automatische Springen zur neuesten Nachricht im Verlauf entfällt; Rahmen und Beschriftung „Neueste Nachricht“ bleiben.
- Gleiche Anzeige wie im Verlauf (bereinigter Text, Zitate eingeklappt, Übersetzung, Anhangshinweis); ohne „Bis hierher testen“ und ohne Neu-Übersetzen. Lange Nachrichten scrollen in der Karte (max. 28 rem).

## Acceptance Criteria
- [x] Angenommen ein Ticket mit einer Nachricht, wenn die Seite erscheint, dann steht die Nachricht ganz oben unter „Kundennachricht“, vor Ticketkopf, Formular und Verlauf.
- [x] Angenommen mehrere Nachrichten, wenn die Seite erscheint, dann steht oben die letzte Kundennachricht („Letzte Kundennachricht“) mit Hinweis auf den Verlauf unten; unsere Antwort und interne Notizen stehen nicht oben.
- [x] Angenommen ein geladenes Ticket, wenn die Seite erscheint, dann gibt es kein Feld „Ticket aus Zammad“; bei einem nicht ladbaren Ticket weiter.
- [x] Angenommen ein Testlauf (zurückgespultes Ticket), wenn die Seite erscheint, dann steht oben die Kundennachricht des Stands.
- [x] Angenommen die Seite lädt, dann springt sie nicht zur neuesten Nachricht im Verlauf.

## Implementation Notes
- `Ticket::focusArticle()`, Komponente `ticket/focus-message`, Schalter `full` in `ticket/article`, `tickets/show.blade.php`.
- Tests: `PROJ-43-FocusMessageTest.php` (8) und angepasste Tests in PROJ-6.
- Bitte im Browser prüfen: Nachricht oben, kein Sprung, Anker „Verlauf“, 375/768 px.

---

## QA Test Results

**Tested:** 2026-10-14 (zusammen mit PROJ-45)
**Tester:** QA Engineer (AI)
**Methode:** komplette Pest-Suite (1363 Tests, alle bestanden), `PROJ-43-FocusMessageTest.php` (8 Tests), Suche im Code nach automatischem Scrollen (`scrollIntoView`, `scrollTo`: keine Treffer in den Ticket-Ansichten; nur `autofocus` im Abruffeld der Startseite). **Nicht getestet:** Browser und Breiten 375/768/1440 px, Anker „Verlauf“ und das tatsächliche Scrollverhalten.

### Acceptance Criteria Status
- [x] AC-1 Eine Nachricht steht ganz oben als „Kundennachricht“ vor Kopf, Formular und Verlauf (Test)
- [x] AC-2 Mehrere Nachrichten: oben die letzte Kundennachricht, Hinweis auf den Verlauf; unsere Antwort und interne Notizen nicht oben (Test)
- [x] AC-3 Kein Feld „Ticket aus Zammad“ bei geladenem Ticket; bei nicht ladbarem Ticket bleibt es (Test)
- [x] AC-4 Testlauf: oben die Kundennachricht des Stands (Test)
- [x] AC-5 Kein automatisches Springen zur neuesten Nachricht (Code-Prüfung; Browser offen)

### Edge Cases Status
- [x] Ticket ohne Kundennachricht: letzte Nachricht, bei leerem Verlauf nichts (Test)
- [x] Markup in der Nachricht wird wie im Verlauf maskiert (Test)
- [x] Abruffeld bleibt auf der Startseite für ein weiteres Ticket (Test)
- [x] Interne Notiz nach der Kundennachricht erscheint nicht oben (Test)

### Security Audit Results
- [x] XSS: Nachrichtentext wird maskiert (Test)
- [x] Keine interne Notiz und keine eigene Antwort im oberen Bereich (kein Datenleck an der Kundenstelle)
- [x] Keine neuen Routen oder Formulare

### Bugs Found
Keine.

### Summary
- **Acceptance Criteria:** 5/5 bestanden (das Scrollverhalten im Browser noch zu bestätigen)
- **Bugs Found:** 0
- **Security:** Pass
- **Production Ready:** YES
