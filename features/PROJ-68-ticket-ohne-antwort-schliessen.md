# PROJ-68: Ticket ohne Antwort schließen

## Status: In Review
**Created:** 2026-11-02
**Last Updated:** 2026-11-02

## Dependencies
- Requires: PROJ-6 (Zammad-Ticket laden), PROJ-19 (Antwort direkt aus der App, persönliche Zammad-Tokens)
- Erster Baustein von PROJ-29 (Ticketbearbeitung in der App)

## Hintergrund
Manche Tickets brauchen keine Antwort mehr, zum Beispiel wenn der Kunde schreibt, dass er schon in einem anderen Ticket geschrieben hat und dort beantwortet wurde (Anlass: Ticket 2140190). Bisher musste man dafür nach Zammad wechseln.

## User Stories
- Als **Mitarbeiter** will ich ein offenes Ticket aus CSA heraus ohne Antwort schließen, damit ich dafür nicht nach Zammad wechseln muss.
- Als **Mitarbeiter** will ich optional einen Grund angeben, der als interne Notiz in Zammad steht, damit Kollegen nachvollziehen, warum es keine Antwort gab.
- Als **Mitarbeiter** will ich vor dem Schließen eine Rückfrage, damit ein Fehlklick nichts auslöst.

## Out of Scope
- Andere Statuswechsel (warten, zuweisen) und interne Notizen ohne Schließen: PROJ-29.
- Wieder öffnen aus CSA.

## Acceptance Criteria
- [ ] Angenommen ein offenes Ticket und ein Name mit Zammad-Token, wenn die Ticketseite geöffnet wird, dann gibt es einen roten Knopf „Ohne Antwort schließen“ neben „In Zammad öffnen“ und „Aktualisieren“ und unten „oder Ticket ohne Antwort schließen“ neben „Vorschlag erstellen“.
- [ ] Angenommen der Knopf wird geklickt, dann erscheint eine Rückfrage mit optionalem Grund; „Abbrechen“ ändert nichts.
- [ ] Angenommen „Ohne Antwort schließen“ wird bestätigt, dann setzt Zammad das Ticket im Namen des Mitarbeiters auf „geschlossen“, der Kunde erhält nichts, und es geht zurück zur Ticketliste mit Bestätigung; dort ist das Ticket sofort geschlossen.
- [ ] Angenommen ein Grund wurde eingegeben, dann steht er im selben Aufruf als interne Notiz am Ticket.
- [ ] Angenommen das Ticket ist schon geschlossen, kein Token hinterlegt oder ein zurückgespultes Ticket (Testmodus) wird angezeigt, dann gibt es keinen Knopf, und ein direkter Aufruf ändert nichts.
- [ ] Angenommen Zammad lehnt ab oder antwortet nicht, dann steht auf der Ticketseite eine klare Meldung, bei Zeitüberschreitung mit dem Hinweis, dass der Ausgang unklar ist.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Rückfrage vor dem Schließen | Die Änderung geht sofort nach Zammad | 2026-11-02 |
| Grund optional, als interne Notiz | Nachvollziehbar ohne Pflichtfeld | 2026-11-02 |
| Danach zurück zur Ticketliste | Weiterarbeiten mit dem nächsten Ticket | 2026-11-02 |
| Alle mit persönlichem Zammad-Token | Wie beim Antwortversand, im eigenen Namen | 2026-11-02 |

### Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Ein PUT mit Status und optionaler Notiz | Status und Notiz können nicht auseinanderfallen | 2026-11-02 |
| Ein Dialog je Seite, beide Knöpfe öffnen ihn per Ereignis | Der untere Knopf steht im Vorschlagsformular, verschachtelte Formulare sind nicht möglich | 2026-11-02 |

## Implementation Notes (2026-11-02)
`ZammadReplySender::close()`, `ReplyProblem::closeMessage()`, `CloseTicketRequest`, `TicketCloseController`, Route `tickets.close` (POST, Name gewählt, Rate-Limit), Komponente `x-ticket.close-dialog`, Knöpfe in `x-ticket.header` und `x-analysis.form`. Nach dem Schließen aktualisiert die App die Zeile der Ticketliste sofort (PROJ-66). Tests: `tests/Feature/PROJ-68-CloseTicketTest.php`.

## QA Test Results
_To be added by /qa_
