# PROJ-80: Klassifikation neuer Tickets im Betrieb

## Status: In Progress
**Created:** 2026-11-10
**Last Updated:** 2026-11-10

## Dependencies
- Requires: PROJ-78 (Ticket-Klassifikation: Kategorien, Prompt, Beispiele, Prüfung, Tags), PROJ-66 (automatischer Ticket-Eingang), PROJ-6 (Ticketseite), PROJ-67 (Ticketübersicht auf der Startseite)

## Hintergrund
PROJ-78 war als vorübergehendes Admin-Modul gedacht, das einmalig Trainingsdaten für das Decision-Modell Laya erzeugt. Sprache, Kundenverhalten und die Vorgaben von Amazon ändern sich aber, und künftige Open-Weights-Modelle sollen mit aktuellen Daten neu trainiert werden. Die Klassifikation ist deshalb ein laufender Prozess: Das Modul bleibt dauerhaft, und jedes neu eingehende Ticket wird ab sofort mit demselben Prompt, denselben Beispielen und dem eingestellten Frontier-Modell (`LABELING_MODEL`, derzeit gpt-5.4-mini) eingeordnet. Laya kann diesen Schritt später ersetzen.

Die Mitarbeiter bestätigen oder korrigieren die Einordnung im Arbeitsalltag auf der Ticketseite. So entstehen laufend geprüfte Trainingsdaten, ohne dass ein Admin jedes Ticket prüft. Die Ticketübersicht auf der Startseite zeigt die Einordnung jedes Tickets.

## User Stories
- Als Mitarbeiter möchte ich auf der Ticketseite sehen, wie die KI die erste Kundennachricht eingeordnet hat, und sie mit einem Klick bestätigen oder korrigieren, damit die Trainingsdaten aus der täglichen Arbeit entstehen.
- Als Mitarbeiter möchte ich zu jeder Kategorie die Regeln in Stichpunkten sehen, wenn ich mit der Maus darüber fahre, damit ich ohne Nachschlagen richtig einordne.
- Als Mitarbeiter möchte ich „Unsicher“ wählen können, damit ich nichts Falsches bestätigen muss und ein Admin den Fall entscheidet.
- Als Mitarbeiter möchte ich in der Ticketübersicht auf der Startseite sehen und filtern, wie jedes Ticket eingeordnet ist.
- Als Admin möchte ich, dass jede Abweichung zwischen Mitarbeiter und KI bei mir landet, bevor ein Tag nach Zammad geht, damit nur übereinstimmende oder von mir entschiedene Labels in den Trainingsdaten stehen.
- Als Admin möchte ich die automatische Klassifikation neuer Tickets abschalten können, etwa wegen der Kosten oder beim Wechsel zu Laya.

## Out of Scope
- Folgeaktionen aus der Kategorie: keine automatische Zuweisung, Weiterleitung oder Antwort anhand der Einordnung
- Automatisches Schreiben von Tags nach Zammad (bleibt der Admin-Lauf „Tags schreiben“ aus PROJ-78)
- Anbindung von Laya, Export, Training
- Klassifikation späterer Nachrichten oder wieder geöffneter Tickets: nur die erste Kundennachricht neuer Tickets zählt

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Klassifikation bei Eingang
- [ ] Angenommen ein neues Ticket mit einer Nachricht des Kunden geht in Zammad ein, wenn CSA es zum ersten Mal erfasst, dann wird seine erste Kundennachricht im Hintergrund mit Prompt, Beispielen und Modell der Ticket-Klassifikation eingeordnet (Quelle „Eingang“)
- [ ] Angenommen CSA erfasst ein Ticket, das älter als 24 Stunden ist (Import, Abgleich, wieder geöffnet), dann wird es nicht automatisch klassifiziert
- [ ] Angenommen wir haben das Ticket zuerst angeschrieben und der Kunde hat noch nicht geantwortet, dann wird es nicht erfasst
- [ ] Angenommen der Absenderfilter oder der Gruppenausschluss der Ticket-Klassifikation trifft zu, dann wird das Ticket wie im Abruf ausgeschlossen und nicht klassifiziert
- [ ] Angenommen ein neues Ticket wird erfasst, dann gehört es nach fester Regel (stabil je Ticket-ID) mit 5 % Wahrscheinlichkeit zur Testmenge
- [ ] Angenommen ein Admin schaltet „Neue Tickets automatisch klassifizieren“ aus, dann werden neue Tickets weder erfasst noch klassifiziert
- [ ] Angenommen die Klassifikation schlägt fehl, dann steht das Ticket auf „Fehler“ und der Admin-Lauf „Neu klassifizieren“ holt es nach

### Leiste auf der Ticketseite
- [ ] Angenommen ein Ticket ist klassifiziert und noch nicht entschieden (Training), wenn ein Mitarbeiter die Ticketseite öffnet, dann sieht er „Worum geht es?“ mit dem KI-Vorschlag, je Kategorie einen Knopf (Vorschlag hervorgehoben) und „Unsicher“
- [ ] Angenommen ein Ticket gehört zur Testmenge oder hat keinen gültigen Vorschlag, dann zeigt die Leiste nur die Knöpfe ohne Vorauswahl und ohne KI-Vorschlag
- [ ] Angenommen der Mitarbeiter fährt mit der Maus über einen Kategorie-Knopf oder erreicht ihn per Tastatur, dann erscheinen die Regeln der Kategorie in Stichpunkten
- [ ] Angenommen der Mitarbeiter wählt eine Kategorie, dann wird sie mit Name und Zeit gespeichert, ohne dass die Seite neu lädt (ein angefangener Antwortentwurf bleibt erhalten)
- [ ] Angenommen der Mitarbeiter hat selbst entschieden und es ist noch kein Tag geschrieben, dann kann er die Wahl über „Ändern“ korrigieren
- [ ] Angenommen ein Admin hat entschieden, das Ticket trug schon ein Tag oder das Tag ist geschrieben, dann zeigt die Leiste die Einordnung nur an
- [ ] Angenommen das Ticket ist nicht erfasst, ausgeschlossen oder noch nicht klassifiziert, dann erscheint keine Leiste

### Entscheidung des Mitarbeiters
- [ ] Angenommen die Wahl stimmt mit dem KI-Vorschlag überein, dann gilt sie (Herkunft „Mitarbeiter“) und erhält beim nächsten Admin-Lauf „Tags schreiben“ ihr Tag (`train:` bzw. `test:`)
- [ ] Angenommen die Wahl weicht vom KI-Vorschlag ab (Training wie Test, auch ohne gültigen Vorschlag), dann bleibt sie gespeichert, ihr Tag wartet aber, bis ein Admin das Ticket in der Warteschlange „Abweichung“ entschieden hat
- [ ] Angenommen der Mitarbeiter wählt „Unsicher“, dann wird nur ein Vermerk mit Name und Zeit gespeichert, keine Entscheidung; das Ticket kommt in die Warteschlange „Unklar“ (Testmenge: bleibt in „Testmenge“) und wird nie automatisch getaggt
- [ ] Angenommen eine Entscheidung stammt von einem Mitarbeiter, dann geht sie nie als Beispiel in den Prompt und zählt nicht als Stichprobe für Freigabe oder Sperre
- [ ] Angenommen ein Admin entscheidet ein Ticket mit Vermerk oder Abweichung, dann gilt seine Entscheidung wie in PROJ-78

### Admin-Seiten
- [ ] Angenommen ein Ticket steht wegen eines Mitarbeiters in „Abweichung“, dann zeigt die Prüfkarte dessen Wahl mit Namen
- [ ] Angenommen ein Mitarbeiter war unsicher, dann zeigt die Prüfkarte „Mitarbeiter unsicher“ mit Name und Datum
- [ ] Angenommen der Admin öffnet die Ticketliste der Klassifikation, dann kann er nach Entscheidungen von Mitarbeitern, nach dem Vermerk „Mitarbeiter unsicher“ und nach der Quelle „Eingang“ filtern

### Ticketübersicht auf der Startseite
- [ ] Angenommen ein Ticket ist klassifiziert, dann zeigt die Spalte „Klassifikation“ die Kategorie; ein KI-Vorschlag trägt den Zusatz „KI“, eine bestätigte Einordnung keinen
- [ ] Angenommen ein Testticket ist noch nicht entschieden, dann zeigt die Übersicht keine Kategorie
- [ ] Angenommen der Mitarbeiter filtert nach einer Kategorie oder nach „Noch nicht eingeordnet“, dann zeigt die Liste die passenden Tickets
- [ ] Angenommen ein bereits erfasstes Ticket wird später wieder geöffnet, dann übernimmt die Übersicht dessen Einordnung

## Edge Cases
- Zwei Mitarbeiter entscheiden dasselbe Ticket kurz nacheinander: die spätere Wahl gilt, beide stehen im Verlauf
- Ein Mitarbeiter wählt erst eine Kategorie, dann „Unsicher“: seine Entscheidung wird zurückgenommen, nur der Vermerk bleibt
- „Neu klassifizieren“ liefert bei einer Mitarbeiter-Entscheidung einen abweichenden Vorschlag: das Ticket kommt wie bei Admin-Entscheidungen in „Abweichung“
- Das Ticket wird entfernt (Absenderfilter, Admin): Leiste verschwindet, die Übersicht zeigt keine Kategorie
- Zammad liefert beim Eingang noch keine Kundennachricht (wir haben zuerst geschrieben): kein Eintrag; antwortet der Kunde später, bleibt es so (Erstkontakt durch uns)

## Technical Requirements (optional)
- Kosten: rund 18 verwertbare neue Tickets pro Tag, etwa 5 800 Eingabe-Tokens je Ticket mit 3 Beispielen je Kategorie
- Zugriff: Leiste für alle ausgewählten Mitarbeiter, Admin-Seiten weiter nur für Admins
- Datenschutz: Aufbewahrung wie PROJ-78 (Kundentexte werden 36 Monate nach dem Erstelldatum geleert)

## Open Questions
- [ ] Endgültiger Wortlaut der Regel-Stichpunkte je Kategorie (Entwurf in `App\Labeling\LabelCategory::rules()`)

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Eigenes Feature statt Erweiterung von PROJ-78 | PROJ-78 schloss die Vorklassifikation im Betrieb und den Zugriff für Nicht-Admins ausdrücklich aus | 2026-11-10 |
| Stimmt die Wahl des Mitarbeiters mit der KI überein, gilt sie; weicht sie ab, entscheidet ein Admin, bevor ein Tag geschrieben wird (Training wie Test) | Nur Labels, bei denen zwei Seiten übereinstimmen oder ein Admin entschieden hat, landen in den Trainingsdaten | 2026-11-10 |
| Entscheidungen von Mitarbeitern werden getaggt, aber nie Prompt-Beispiel und nie Stichprobe | Die Beispiele und die Trefferquote sollen auf Admin-Entscheidungen beruhen; die Mitarbeiter sehen den Vorschlag und könnten ihm folgen | 2026-11-10 |
| „Unsicher“ ist nur ein Vermerk ohne Entscheidung; das Ticket bleibt für den Admin offen | Eine Unsicherheit des Mitarbeiters heißt nicht, dass das Ticket unbrauchbar ist | 2026-11-10 |
| Testmenge für neue Tickets 5 % statt 20 %; die Leiste zeigt bei Testtickets keinen Vorschlag | Die Testmenge des Bestands ist groß; die Wahl soll bei Testtickets unbeeinflusst sein | 2026-11-10 |
| Die Startseiten-Übersicht zeigt die Einordnung in der vorhandenen Spalte „Klassifikation“, unentschiedene Testtickets ohne Kategorie | Die Spalte war in PROJ-66/67 dafür vorgesehen; bei Testtickets darf die Übersicht den verdeckten Vorschlag nicht verraten | 2026-11-10 |
| Die automatische Klassifikation ist eine Ausnahme vom Non-Goal „automatische Bearbeitung beim E-Mail-Eingang“ | Sie ordnet nur ein und löst keine Folgeaktion aus | 2026-11-10 |

### Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Auslöser beim ersten Erfassen in `TicketSynchronizer::write()`, nur für Tickets bis 24 Stunden alt und mit Kundennachricht | Webhook und Abgleich laufen beide dort durch; Importe alter Tickets lösen nichts aus | 2026-11-10 |
| Erfassen als Service `LabelCapture`, den Abruf-Lauf und Eingang gemeinsam nutzen; `ClassifyLabelTicket` läuft auch ohne Lauf | Gleiche Regeln (Absenderfilter, Erstkontakt, Testmenge) ohne doppelten Code | 2026-11-10 |
| Abweichungen nutzen die vorhandene Warteschlange „Abweichung“; Prüfungen von Mitarbeitern schließen sie nicht | Kein neuer Prüfweg; der Admin kennt die Warteschlange | 2026-11-10 |
| Entscheidung per JSON-Aufruf statt Formular | Ein Neuladen der Ticketseite würde einen angefangenen Antwortentwurf verwerfen | 2026-11-10 |
| Die Einordnung wird nach `tracked_tickets.category` (mit `category_confirmed`) übertragen | Filter und Spalte der Übersicht bleiben einfache Abfragen auf einer Tabelle | 2026-11-10 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)
Plan vom 2026-11-10 (mit dem Auftraggeber abgestimmt), kurz:
1. **Erfassen ohne Lauf:** `App\Labeling\LabelCapture` übernimmt die Logik aus `CaptureLabelTicket` (vorhandene Tags, Absenderfilter, Erstkontakt durch uns, Testregel, neu auch Gruppenausschluss). `ClassifyLabelTicket` nimmt `?int $runId`.
2. **Eingang:** `TicketSynchronizer::write()` stellt für neue, junge Tickets mit Kundennachricht `ClassifyIncomingTicket` in die Queue; der Job erfasst mit Quelle `eingang` und 5 % Testanteil und stellt die Klassifikation ohne Lauf an. Schalter `intake` in den Einstellungen der Ticket-Klassifikation.
3. **Entscheidung:** `ReviewRecorder::recordFromTicket()` speichert Herkunft `mitarbeiter` (`hit = null`); bei Abweichung erhält der neueste Vorschlag die Warteschlange `abweichung`. „Unsicher“ setzt `staff_unsure_by/at` und die Warteschlange `zweifel`. Offene Abweichungen ignorieren Prüfungen mit Herkunft `mitarbeiter`.
4. **Ticketseite:** Komponente `x-labeling.ticket-bar` unter dem Ticketkopf, Route `POST /tickets/{number}/klassifikation` (JSON). Generische Komponente `x-tooltip` mit den Stichpunkten aus `LabelCategory::rules()`, auch auf „Prüfen“.
5. **Übersicht:** `LabelTicket::shareCategory()` schreibt Kategorie und `category_confirmed` nach `tracked_tickets`; Bestand per `labeling:share-categories`.

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
