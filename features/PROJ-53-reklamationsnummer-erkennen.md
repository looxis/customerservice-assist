# PROJ-53: Reklamationsnummer erkennen (Rn-Präfix)

## Status: Approved
**Created:** 2026-10-17
**Last Updated:** 2026-10-17

## Dependencies
- Requires: PROJ-7 (Bestellnummern, Reklamationsaufträge aus EOCS), PROJ-49 (Erkennungsregeln und Einstellungsseite), PROJ-9 (Analyse).
- Verwandt: PROJ-52 (zusammengehörige Tickets, nutzt die erkannte Originalnummer).

## Ausgangslage
Reklamationsaufträge in EOCS tragen den Präfix `R1`, `R2`, … gefolgt von einem Bindestrich und der Originalbestellnummer (z. B. `R1-302-8780527-8594717`). Antwortet ein Kunde auf eine E-Mail, in deren Betreff diese Nummer steht, erkennt die App heute **keine** Bestellnummer: Der Bindestrich vor der Originalnummer verhindert den Treffer. Die Reklamation erscheint erst, wenn die Originalbestellung aus EOCS geladen ist, und dann nur „zur Information“.

## User Stories
- Als Mitarbeiterin möchte ich, dass die App `R1-<Bestellnummer>` als Reklamationsnummer erkennt und die Originalbestellung findet, auch wenn nur diese Nummer im Ticket steht.
- Als Mitarbeiterin möchte ich in der Bestellung sehen, welche Reklamation das Ticket betrifft.
- Als KI-Analyse möchte ich wissen, dass das Ticket ein Folgevorgang einer Reklamation ist.
- Als Admin möchte ich den Präfix auf der Seite „Erkennungsregeln“ ändern können.

## Out of Scope
- Verwandte Tickets finden (PROJ-52).
- Reklamationsaufträge anlegen oder ändern.
- Eine eigene Fallkategorie für Folgevorgänge; die KI bekommt die Tatsache als Fakt.

## Regeln
- Eine **Reklamationsnummer** besteht aus dem Präfix (Standard `R`), einer Zahl von 1 bis 999, einem Bindestrich und einer Bestellnummer eines bekannten Formats. Sie wird in Titel und Nachrichten erkannt (auch in Zitaten) und beim Eintippen einer Bestellnummer.
- Die **Originalnummer** gilt als gefundene Bestellnummer des Tickets (wie bisher geladen und angeboten); die Reklamationsnummer (z. B. `R1`) wird zusätzlich festgehalten. Steht dieselbe Bestellnummer auch ohne Präfix im Ticket, bleibt die Reklamationsnummer erhalten.
- Mehrere Reklamationen zur selben Bestellung (R1, R2, …) sind möglich; jede genannte wird festgehalten.
- Die Bestellung in der Ticketansicht zeigt die genannte Reklamation hervorgehoben („Das Ticket nennt R1“) vor den übrigen Reklamationsaufträgen der Bestellung.
- Die Analyse bekommt eine Zeile im Fall: „Das Ticket nennt den Reklamationsauftrag R1 zur Bestellung <Nummer> (Folgevorgang einer Reklamation).“ Der Status der Reklamation aus EOCS steht wie bisher unter „Bestellungen“.
- **Einstellung:** Auf der Seite „Einstellungen > Erkennungsregeln“ gibt es die Gruppe „Präfix für Reklamationsnummern“ (1 bis 3 Buchstaben, Standard `R`).

## Acceptance Criteria
- [ ] Angenommen ein Ticket nennt nur `R1-302-8780527-8594717` im Titel, wenn die Ticketseite erscheint, dann ist die Originalnummer `302-8780527-8594717` als Bestellung gefunden (und wird geladen) und `R1` als genannte Reklamation vermerkt.
- [ ] Angenommen im Ticket stehen `R2-<Nummer>` und dieselbe `<Nummer>` ohne Präfix, dann gibt es eine Bestellung und die Reklamation R2 bleibt vermerkt.
- [ ] Angenommen zwei Reklamationsnummern zur selben Bestellung stehen im Ticket, dann sind beide vermerkt.
- [ ] Angenommen ich tippe `R1-302-8780527-8594717` als Bestellnummer ein, dann nimmt die App die Originalnummer und vermerkt R1.
- [ ] Angenommen die Bestellung wurde aus EOCS geladen, wenn das Ticket R1 nennt, dann ist der Reklamationsauftrag `R1-…` hervorgehoben („Das Ticket nennt R1“).
- [ ] Angenommen ein Vorschlag wird erstellt, wenn das Ticket eine Reklamationsnummer nennt, dann enthält der Prompt die Zeile zum Folgevorgang.
- [ ] Angenommen ein Präfix ohne gültiges Zahlenformat (`R-…`, `RX-…`, `R1000-…`, `R1 302-…`), dann ist es keine Reklamationsnummer; eine Originalnummer daneben bleibt unverändert erkannt.
- [ ] Angenommen ich ändere den Präfix auf der Erkennungsseite (z. B. `REK`), dann erkennt die App `REK1-…` und `R1-…` nicht mehr als Reklamationsnummer; ungültige Eingaben (leer, Ziffern, mehr als 3 Zeichen) werden abgelehnt.
- [ ] Ohne Reklamationsnummer im Ticket verhält sich die App wie bisher.

## Edge Cases
- Reklamationsnummer in einem Zitat oder in einer Signatur: wird wie jede Nummer im Text erkannt.
- `R1-` direkt vor einer Nummer, die zu keinem Format passt: keine Reklamationsnummer, nichts erkannt.
- Eine Reklamationsnummer zu einer Bestellung, die EOCS nicht kennt: die Originalnummer wird als „nicht gefunden“ gemeldet wie bisher; R1 bleibt im Fall vermerkt.
- Interne EOCS-Nummern (sechs Ziffern) mit Präfix werden wie ohne Präfix behandelt.

## Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Die Originalnummer zählt als gefundene Bestellung, die Reklamationsnummer wird zusätzlich vermerkt | Alle bisherigen Abläufe (Laden, Analyse) arbeiten mit der Originalnummer | 2026-10-17 |
| Präfix als Einstellung (1 bis 3 Buchstaben) | Falls der Präfix sich ändert, ohne Entwickler anpassbar | 2026-10-17 |
| Die KI bekommt eine Tatsachenzeile statt einer eigenen Kategorie | Kein neuer Fachbegriff; Regeln in der Wissensbasis können darauf aufbauen | 2026-10-17 |

## Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Das Muster jedes Bestellnummern-Formats nimmt optional `R<n>-` vor der Nummer an (benannte Gruppen) | Eine Stelle, die jedes Format gleich behandelt | 2026-10-17 |
| `OrderNumber` trägt die Reklamationsnummer, `OrderFormats` den Präfix aus den Einstellungen | Alle Verbraucher bekommen sie ohne eigene Suche | 2026-10-17 |
| Neuer Gruppentyp „Wort“ im Einstellungsverzeichnis | Ein einzelner kurzer Text braucht weder Liste noch Zahl | 2026-10-17 |

## Implementation Notes
- **Geändert:** `OrderNumberFormat` (das Muster nimmt optional `R<n>-` vor der Nummer an, benannte Gruppen `number` und `claim`, `parse()` für getippte Nummern), `OrderNumber` (trägt `claims`), `OrderNumberDetector` (führt mehrere Nennungen derselben Bestellung zusammen und behält die Reklamationsnummern), `OrderFormats` (Präfix aus den Einstellungen), `SettingGroup` (Typ `WORD`), Gruppe `claim_prefix` auf der Seite „Erkennungsregeln“, `TicketController`/Ansicht (Hervorhebung „Das Ticket nennt diese Reklamation“, Hinweis am noch nicht geladenen Vorschlag), `AnalysisRequest`/`AnalysisController`/`CaseAnalyzer` (Zeile „Das Ticket nennt den Reklamationsauftrag R1 …“), Ausprobieren auf der Erkennungsseite zeigt die Reklamationsnummer.
- **Geprüft** mit dem echten Ticket 2139944: Die Originalnummer 302-8780527-8594717 wird mit Reklamationsnummer R1 erkannt.
- **Tests:** `PROJ-53-ClaimNumberTest` (19).
- **Offen für `/qa`:** Bedienung im Browser, Prüfung mit weiteren echten Tickets.

## QA Test Results (2026-10-17)
**Ergebnis: bereit (keine Critical-/High-Fehler).**

- **Akzeptanzkriterien und Randfälle:** Alle Akzeptanzkriterien durch Tests abgedeckt (20 Tests). Live geprüft am echten Ticket 2139944: die Reklamationsnummer wird erkannt und die Bestellung mit „Das Ticket nennt diese Reklamation“ hervorgehoben.
- **Sicherheit:** Präfix nur 1 bis 3 Buchstaben (Eingabe geprüft, Test), Muster werden nur aus dem geprüften Präfix gebaut.
- **Regression:** Gesamtsuite 1659 Tests, 1657 grün, 2 übersprungen.
- **Nicht durchgeführt:** eigener Browser-Durchgang (Chrome/Firefox/Safari, 375/768/1440 px). Es gab keinen Browser in der QA-Umgebung; ein schneller Sichttest der Oberfläche liegt vom Anwender vor (2026-10-17).
- **Beobachtungen (Low):** Die Ticketseite braucht mit den neuen Zammad-Abfragen für zusammengehörige Tickets rund 4 Sekunden (Ticket 2139944, mehrere Zammad-Aufrufe nacheinander); bei Bedarf später parallelisieren oder Teile nachladen. Ohne gewählten Namen sind Ticketseiten und Einstellungen ansehbar; das entspricht dem bestehenden Verhalten (Auswahl erzwingt nur Aktionen).
