# PROJ-43: Kundennachricht oben auf der Ticketseite

## Status: In Progress
**Created:** 2026-10-13
**Last Updated:** 2026-10-13

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
