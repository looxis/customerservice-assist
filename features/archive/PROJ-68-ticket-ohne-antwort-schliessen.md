# PROJ-68: Ticket ohne Antwort schließen

## Status: Approved
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
- [x] Angenommen ein offenes Ticket und ein Name mit Zammad-Token, wenn die Ticketseite geöffnet wird, dann gibt es einen roten Knopf „Ohne Antwort schließen“ neben „In Zammad öffnen“ und „Aktualisieren“ und unten „oder Ticket ohne Antwort schließen“ neben „Vorschlag erstellen“.
- [x] Angenommen der Knopf wird geklickt, dann erscheint eine Rückfrage mit optionalem Grund; „Abbrechen“ ändert nichts.
- [x] Angenommen „Ohne Antwort schließen“ wird bestätigt, dann setzt Zammad das Ticket im Namen des Mitarbeiters auf „geschlossen“, der Kunde erhält nichts, und es geht zurück zur Ticketliste mit Bestätigung; dort ist das Ticket sofort geschlossen.
- [x] Angenommen die letzte Kundennachricht ist eine Amazon-Nachricht mit „Fall lösen“-Link, dann zeigt die Rückfrage neben dem Knopf die angehakte Checkbox „Fall auch bei Amazon lösen“; bleibt sie angehakt, öffnet sich die „Fall lösen“-Seite in einem neuen Tab, während CSA zur Ticketliste geht. Ohne Haken öffnet sich nichts. (2026-11-18)
- [x] Angenommen ein Grund wurde eingegeben, dann steht er im selben Aufruf als interne Notiz am Ticket.
- [x] Angenommen das Ticket ist schon geschlossen, kein Token hinterlegt oder ein zurückgespultes Ticket (Testmodus) wird angezeigt, dann gibt es keinen Knopf, und ein direkter Aufruf ändert nichts.
- [x] Angenommen Zammad lehnt ab oder antwortet nicht, dann steht auf der Ticketseite eine klare Meldung, bei Zeitüberschreitung mit dem Hinweis, dass der Ausgang unklar ist.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Rückfrage vor dem Schließen | Die Änderung geht sofort nach Zammad | 2026-11-02 |
| Grund optional, als interne Notiz | Nachvollziehbar ohne Pflichtfeld | 2026-11-02 |
| Danach zurück zur Ticketliste | Weiterarbeiten mit dem nächsten Ticket | 2026-11-02 |
| Alle mit persönlichem Zammad-Token | Wie beim Antwortversand, im eigenen Namen | 2026-11-02 |
| Amazon „Fall lösen“ abwählbar, in neuem Tab statt im selben Fenster | Im selben Fenster führte „Zurück“ wieder auf die Rückfrage; manche Tickets waren nur zur Erinnerung offen und brauchen bei Amazon nichts | 2026-11-18 |

### Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Ein PUT mit Status und optionaler Notiz | Status und Notiz können nicht auseinanderfallen | 2026-11-02 |
| Ein Dialog je Seite, beide Knöpfe öffnen ihn per Ereignis | Der untere Knopf steht im Vorschlagsformular, verschachtelte Formulare sind nicht möglich | 2026-11-02 |
| Kein iframe für Seller Central, neuer Tab beim Absenden | Seller Central verbietet das Einbetten in fremde Seiten (und braucht die Amazon-Anmeldung, deren Cookies im fremden Frame blockiert werden); der Tab öffnet direkt beim Klick, damit kein Popup-Blocker greift | 2026-11-18 |

## Implementation Notes (2026-11-02)
`ZammadReplySender::close()`, `ReplyProblem::closeMessage()`, `CloseTicketRequest`, `TicketCloseController`, Route `tickets.close` (POST, Name gewählt, Rate-Limit), Komponente `x-ticket.close-dialog`, Knöpfe in `x-ticket.header` und `x-analysis.form`. Nach dem Schließen aktualisiert die App die Zeile der Ticketliste sofort (PROJ-66). Tests: `tests/Feature/PROJ-68-CloseTicketTest.php`.

## QA Test Results

**Tested:** 2026-11-02 · **App:** Live-Installation (Port 8081) · **Tester:** QA Engineer (AI)

### Acceptance Criteria
- [x] Knöpfe im Kopfbereich und neben „Vorschlag erstellen“, ein Dialog je Seite: live an Ticket 2140190 und per Test belegt.
- [x] Rückfrage mit optionalem Grund, „Abbrechen“ ändert nichts.
- [x] Schließen im Namen des Mitarbeiters, zurück zur Ticketliste: vom Auftraggeber live an Ticket 2140190 ausgeführt und freigegeben; Protokoll „Ticket closed without reply“, die Ticketliste stand eine Sekunde später auf „geschlossen“.
- [x] Grund als interne Notiz im selben Aufruf: per Test belegt.
- [x] Kein Knopf und keine Änderung bei geschlossenem Ticket, ohne Token, ohne Namen und im Testmodus: per Test belegt.
- [x] Meldungen bei Ablehnung und Zeitüberschreitung (Ausgang unklar): per Test belegt.

### Security Audit
- [x] CSRF aktiv (live 419 ohne Token), GET wird abgewiesen (405), Rate-Limit 20 je Minute.
- [x] Schreibt nur mit dem persönlichen Token des gewählten Namens; serverseitig im Form Request geprüft.
- [x] Protokoll ohne Kundendaten (nur Ticketnummer und Name).

### Bugs Found
Keine Critical, High oder Medium.

### Summary
- **Production Ready:** YES
