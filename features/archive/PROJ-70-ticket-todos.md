# PROJ-70: Ticketbezogene ToDos und interne Folgeaufgaben (Phase 1)

## Status: Approved
**Created:** 2026-11-04
**Last Updated:** 2026-11-04 (offene Fragen entschieden)

## Dependencies
- Requires: PROJ-19 (Antwort aus der App über Zammad senden), PROJ-61 (Anhänge), PROJ-66/67 (Ticket-Eingang und Startseite), PROJ-68 (Ticket ohne Antwort schließen), PROJ-69 (Direkt antworten)
- Vorbereitet für: KI-Vorschläge aus `internal_steps` (Phase 2) und eine spätere Ereignisanbindung an EOCS (Phase 3); beide haben noch keine Feature-ID

## Anlass
Ticket 2140201: Die Kundin hat die Bestellnummer geschickt. Die Gutschrift kann erst übermittelt werden, wenn die neue Bestellung verschickt wurde (ein bis zwei Tage). Der Mitarbeiter muss jetzt antworten und sich das Ticket für später merken. Eine Kundenanfrage kann kommunikativ abgeschlossen sein, obwohl intern noch Aufgaben offen sind. Die Zammad-Wiedervorlage hängt am Ticketstatus und trägt keine Zuständigkeit oder Beschreibung.

## User Stories
- Als Mitarbeiter im Kundenservice will ich beim Senden einer Antwort ein oder mehrere ToDos zum Ticket anlegen und das Ticket schließen, damit die interne Folgeaufgabe (z. B. Teilerstattung nach Versand) nicht verloren geht.
- Als Mitarbeiter will ich auf der Startseite sehen, welche ToDos mir zugewiesen und welche überfällig sind, damit ich Zusagen an Kunden einhalte.
- Als Mitarbeiter will ich ein ToDo anlegen, das auf ein Ereignis wartet (z. B. Versand der Ersatzbestellung), und von direkt bearbeitbaren ToDos unterscheiden, damit ich weiß, was ich jetzt tun kann.
- Als Mitarbeiter will ich beim Abschluss eines ToDos den Kunden erneut informieren können (vorbereitete Antwort, die ich prüfe und sende), damit Zusagen („wir melden uns mit der Sendungsnummer“) eingehalten werden.
- Als Mitarbeiter will ich eine Kundenantwort mit interner Notiz senden, ohne das Ticket zu schließen.
- Als Mitarbeiter in Vertretung will ich offene ToDos von Kollegen sehen und übernehmen können, damit nichts bei Urlaub oder Krankheit liegen bleibt.

## Out of Scope
- KI-Vorschläge für ToDos aus `internal_steps`, Formulierung der Kundenbenachrichtigung durch die KI (Phase 2, eigenes Feature)
- Anbindung an EOCS, automatische Erkennung von Sendungsnummern, ereignisabhängige Aktivierung oder Erledigung, automatische Kundenbenachrichtigung (Phase 3, eigenes Feature)
- Schnittstellen oder Automatismen für externe Ereignisse; Phase 1 legt nur Felder im Datenmodell an
- Projektmanagement-Funktionen: Unteraufgaben, Kommentare, Prioritäten, Anhänge, Wiederholungen, Erinnerungs-Mails
- Ersatz der Zammad-Wiedervorlage; sie bleibt unabhängig nutzbar
- Anmeldung und Rechte je Benutzer (PROJ-15); Zuweisung nur aus der festen Namensliste
- Synchronisation der ToDos nach Zammad

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### ToDo-Bereich und Übersicht
- [ ] Angenommen ein Mitarbeiter ist gewählt, wenn er die Startseite öffnet, dann sieht er oberhalb der Ticketliste einen ToDo-Bereich mit den Ansichten „Meine“, „Alle offen“ und „Überfällig“.
- [ ] Angenommen ToDos mit Fälligkeit vor heute sind offen, wenn die Startseite geladen wird, dann sind sie als überfällig hervorgehoben und erscheinen in „Überfällig“.
- [ ] Angenommen ein ToDo gehört zu einem geschlossenen oder aus der Ticketliste bereinigten Ticket, wenn die Startseite geladen wird, dann erscheint das ToDo weiterhin, solange es offen ist.
- [ ] Angenommen ToDos sind erledigt, wenn die Startseite geladen wird, dann erscheinen sie dort nicht; die Startseite zeigt nur offene ToDos (auch „Benachrichtigung offen“).
- [ ] Angenommen ein Mitarbeiter öffnet die Seite „ToDos“, dann sieht er alle offenen und erledigten ToDos der letzten 12 Monate mit 100 ToDos je Seite und Blättern auf weitere Seiten.
- [ ] Angenommen ein ToDo ist erledigt und älter als 12 Monate, wenn die Seite „ToDos“ geladen wird, dann erscheint es dort nicht mehr.
- [ ] Angenommen ein Ticket hat offene ToDos, wenn die Ticketliste auf der Startseite geladen wird, dann trägt die Zeile ein kleines Kennzeichen für offene ToDos.
- [ ] Angenommen ein Mitarbeiter legt ein ToDo an, ohne den Zuständigen zu ändern, dann ist er selbst zuständig; der Zuständige kann jederzeit gewechselt werden.
- [ ] Angenommen es gibt keine offenen ToDos, wenn die Startseite geladen wird, dann zeigt der Bereich einen Leerzustand mit kurzem Hinweis statt einer leeren Tabelle.
- [ ] Angenommen ein ToDo in der Liste, wenn der Mitarbeiter auf das Ticket klickt, dann öffnet sich die Ticketseite mit der vorhandenen Bearbeitung.
- [ ] Angenommen ein Ticket hat ToDos, wenn der Mitarbeiter die Ticketseite öffnet, dann sieht er die ToDos dieses Tickets (offene zuerst, erledigte eingeklappt).

### Anlegen, Bearbeiten, Abschließen
- [ ] Angenommen der Mitarbeiter öffnet die Ticketseite, wenn er ein ToDo mit Beschreibung, Zuständigem, optionaler Fälligkeit (Auswahl: in 1, 3 oder 7 Tagen, oder ohne) und Art (direkt oder wartet auf Ereignis) anlegt, dann erscheint es in den Ansichten und ist dem Ticket zugeordnet.
- [ ] Angenommen ein ToDo der Art „wartet auf Ereignis“, wenn es angelegt wird, dann ist ein Freitext „Worauf wird gewartet?“ Pflicht, und die Liste zeigt es getrennt von direkt bearbeitbaren ToDos.
- [ ] Angenommen ein ToDo ist offen, wenn der Mitarbeiter Beschreibung, Zuständigen, Fälligkeit oder Art ändert, dann wird die Änderung gespeichert und der Bearbeiter vermerkt.
- [ ] Angenommen ein offenes ToDo ohne Kundenbenachrichtigung, wenn der Mitarbeiter es abschließt, dann ist es erledigt, mit Name und Zeitpunkt, und verschwindet aus den offenen Ansichten.
- [ ] Angenommen ein erledigtes ToDo, wenn der Mitarbeiter es wieder öffnet, dann ist es wieder offen.
- [ ] Angenommen der Zuständige ist leer oder kein Name der festen Liste, wenn gespeichert wird, dann wird eine Validierungsfehlermeldung angezeigt und die Eingabe bleibt erhalten.
- [ ] Angenommen die Beschreibung ist leer, wenn gespeichert wird, dann wird eine Validierungsfehlermeldung angezeigt.

### Anlegen beim Antwortversand
- [ ] Angenommen der Mitarbeiter bereitet eine Antwort vor, wenn er im Antwort-Editor ein oder mehrere ToDos erfasst und sendet, dann wird die Antwort gesendet und danach genau diese ToDos angelegt.
- [ ] Angenommen der Mitarbeiter wählt im Antwort-Editor zusätzlich „Ticket schließen“, wenn er sendet, dann wird die Antwort gesendet, das Ticket geschlossen und die ToDos angelegt.
- [ ] Angenommen der Versand an Zammad schlägt fehl, wenn der Mitarbeiter sendet, dann werden keine ToDos angelegt, die Eingaben (Antwort und ToDos) bleiben erhalten und eine Fehlermeldung erscheint.
- [ ] Angenommen der Versand wurde bereits ausgeführt (Doppelklick, Wiederholung, Zeitüberschreitung mit anschließender Prüfung), wenn die Anfrage erneut eintrifft, dann entstehen weder eine zweite Antwort noch doppelte ToDos.
- [ ] Angenommen die Antwort wurde gesendet, aber das Anlegen der ToDos scheitert, wenn die Fehlermeldung erscheint, dann ist erkennbar, dass die Antwort gesendet wurde, und die ToDos können ohne erneuten Versand nachgetragen werden.

### Antwort mit interner Notiz ohne Schließen
- [ ] Angenommen der Mitarbeiter gibt im Antwort-Editor eine interne Notiz ein und wählt „Ticket offen lassen“, wenn er sendet, dann wird die Antwort an den Kunden gesendet, die interne Notiz als nicht öffentlicher Artikel am Ticket gespeichert und der Status nicht auf geschlossen gesetzt.
- [ ] Angenommen die Antwort wurde gesendet, aber die interne Notiz konnte nicht gespeichert werden, wenn der Versand abgeschlossen ist, dann erscheint ein Hinweis zur fehlenden Notiz, und die Antwort wird nicht erneut gesendet.
- [ ] Angenommen der Mitarbeiter wählt „Wiedervorlage“ (vorhanden), wenn er sendet, dann setzt sich Zammad weiterhin auf „wartend“ mit der gewählten Frist, unabhängig von angelegten ToDos.

### Kundenbenachrichtigung nach Erledigung
- [ ] Angenommen ein ToDo ist mit „Kunde nach Erledigung informieren“ angelegt (mit Hinweistext), wenn der Mitarbeiter es abschließt, dann wird im zugehörigen Ticket eine Antwort vorbereitet (Entwurf mit dem Hinweistext), und das ToDo steht auf „Benachrichtigung offen“.
- [ ] Angenommen der Entwurf ist vorbereitet, wenn der Mitarbeiter ihn prüft, anpasst und sendet, dann wird das ToDo erst nach erfolgreichem Versand vollständig erledigt.
- [ ] Angenommen der Versand schlägt fehl oder der Mitarbeiter sendet nicht, dann bleibt das ToDo auf „Benachrichtigung offen“ und taucht weiter in den offenen Ansichten auf.
- [ ] Angenommen der Mitarbeiter will dem Kunden doch nicht schreiben, wenn er das ToDo ohne Benachrichtigung abschließt, dann ist es erledigt, und das Übergehen wird vermerkt.
- [ ] Angenommen für dasselbe ToDo existiert schon ein vorbereiteter Entwurf, wenn der Mitarbeiter erneut „Abschließen“ wählt, dann wird kein zweiter Entwurf angelegt, sondern der vorhandene geöffnet.

### Berechtigung und Schutz
- [ ] Angenommen kein Name ist gewählt, wenn eine ToDo-Aktion ausgelöst wird, dann erscheint der übliche Hinweis „Bitte wähle zuerst deinen Namen.“
- [ ] Angenommen ein ToDo ist einem anderen Mitarbeiter zugewiesen, wenn ein beliebiger Mitarbeiter der Liste es bearbeitet, übernimmt oder abschließt, dann ist das erlaubt und der Bearbeiter wird vermerkt.

## Edge Cases
- Das Ticket wird in Zammad wieder geöffnet oder geschlossen: ToDos bleiben unverändert, es gibt keinen Abgleich des Status.
- Das Ticket verschwindet aus der Ticketliste (Bereinigung): ToDos bleiben sichtbar und verweisen weiter auf die Ticketnummer.
- Zwei Mitarbeiter schließen dasselbe ToDo gleichzeitig ab: Das zweite Abschließen ist wirkungslos und meldet „bereits erledigt von …“; es entsteht nur ein Entwurf.
- Ein Mitarbeiter legt versehentlich dasselbe ToDo doppelt an (Doppelklick): Es entsteht genau eines.
- Der zugewiesene Name wird später aus der Namensliste entfernt: Das ToDo bleibt sichtbar, ist als „Zuständiger nicht mehr in der Liste“ markiert und kann neu zugewiesen werden.
- Fälligkeit fehlt: erlaubt (nie überfällig, aber in „Alle offen“). Beim Anlegen wählt man nur 1, 3 oder 7 Tage; überfällig wird ein ToDo durch Zeitablauf.
- ToDo der Art „wartet auf Ereignis“ ohne Fälligkeit: erlaubt, bleibt offen, bis ein Mitarbeiter es auf „direkt bearbeitbar“ umstellt oder abschließt. In Phase 1 stellt niemand automatisch um.
- Sehr viele offene ToDos: Die Startseite zeigt sie begrenzt, nach Fälligkeit sortiert, überfällige zuerst; die Seite „ToDos“ zeigt 100 je Seite mit Blättern.
- Die Ticketnummer ist unbekannt oder das Ticket in Zammad nicht abrufbar: Das ToDo wird trotzdem angelegt und angezeigt; der Link zur Ticketseite meldet den Ladefehler wie üblich.

## Technical Requirements (optional)
- Datenschutz: ToDo-Beschreibungen können Kundendaten enthalten; Speicherung nur in der App-Datenbank, keine Weitergabe an Dritte. Erledigte ToDos bleiben 12 Monate sichtbar (Seite „ToDos“).
- Zuverlässigkeit: Anlegen von ToDos zusammen mit dem Versand ist idempotent (keine Duplikate bei Wiederholung).
- Datenmodell muss für Phase 2/3 Herkunft (manuell, aus Antwort, später KI), Verweis auf die erzeugende Analyse und einen externen Ereignisverweis tragen können; diese Felder bleiben in Phase 1 ohne Funktion.
- Keine Authentifizierung im MVP; der Mitarbeitername kommt wie bisher aus der Auswahl (PROJ-5).

## Open Questions
- [x] Aufbewahrung: 12 Monate, nur auf der Seite „ToDos“; Startseite nur offene (2026-11-04)
- [x] Standard-Zuständiger ist der Anlegende, später wechselbar (2026-11-04)
- [x] Kennzeichen für offene ToDos in der Ticketliste: ja (2026-11-04)
- [x] Fälligkeit: Auswahl 1, 3 oder 7 Tage oder keine, Anlegen bewusst einfach (2026-11-04)

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| ToDos sind eigenständig und hängen an der Zammad-Ticketnummer, nicht am Ticketstatus | Ticket kann geschlossen sein, während intern noch etwas offen ist; Bereinigung der Ticketliste darf nichts verlieren | 2026-11-04 |
| Zammad-Wiedervorlage bleibt unabhängig nutzbar | Vorgabe des Auftraggebers; kein Abgleich in Phase 1 | 2026-11-04 |
| Kein Rechtekonzept: Jeder Name der Liste darf alle ToDos bearbeiten, Bearbeiter wird vermerkt | Vertretung bei Urlaub und Krankheit; es gibt vor PROJ-15 keine Anmeldung | 2026-11-04 |
| Zwei Arten: „direkt bearbeitbar“ und „wartet auf Ereignis“, in Phase 1 rein manuell | Trennt Handlungsbedarf von Wartezustand; Grundlage für Phase 3 ohne Automatismen | 2026-11-04 |
| Kundenbenachrichtigung bereitet nur einen Entwurf vor; ToDo ist erst nach erfolgreichem Versand ganz erledigt | Mensch prüft und sendet jede Kundenantwort (PRD); keine Zusage geht verloren | 2026-11-04 |
| Anlegen der ToDos erst nach erfolgreichem Versand, idempotent | Keine verwaisten ToDos bei Fehler, keine Dubletten bei Wiederholung | 2026-11-04 |
| Antwort mit interner Notiz ohne Schließen gehört zu diesem Feature | Gleiche Stelle im Antwort-Editor, im Anlassfall nötig | 2026-11-04 |
| Eigene Seite „ToDos“ mit offenen und erledigten ToDos der letzten 12 Monate, 100 je Seite; Startseite nur offene | Startseite bleibt auf Handlungsbedarf fokussiert, Nachvollziehbarkeit auf eigener Seite | 2026-11-04 |
| Fälligkeit nur als Auswahl 1, 3 oder 7 Tage | Anlegen soll schnell gehen, kein Datumsfeld | 2026-11-04 |
| Standard-Zuständiger ist der Anlegende, wechselbar | Klare Verantwortlichkeit ohne Zusatzaufwand | 2026-11-04 |
| Kennzeichen offener ToDos in der Ticketliste | Sofort sichtbar, dass Folgeaufgaben offen sind | 2026-11-04 |
| KI-Vorschläge und EOCS-Anbindung nicht in Phase 1, Datenmodell nur vorbereitet | Vorgabe des Auftraggebers: nicht implementieren, aber spätere Anlage ermöglichen | 2026-11-04 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|
| ToDos in eigener Tabelle, verknüpft über die Zammad-ID, nicht über die Ticketliste | Bleiben bei Schließen und Bereinigen erhalten | 2026-11-04 |
| Datenbank statt Browser-Speicher | Von allen Mitarbeitern gemeinsam genutzt | 2026-11-04 |
| Normale Webseiten (Blade) mit kleinen Alpine-Dialogen, JSON nur für den Antwortversand wie bisher | Entspricht dem Rest der App | 2026-11-04 |
| Anlegen beim Versand: Schutzschlüssel aus Analyse und laufender Nummer | Wiederholung (Doppelklick, Prüfung nach Zeitüberschreitung) erzeugt keine Dubletten | 2026-11-04 |
| Interne Notiz als zweiter Aufruf an Zammad nach der Antwort | Ein Aufruf trägt nur einen Artikel; Notizfehler gefährden die gesendete Antwort nicht | 2026-11-04 |
| Kundenbenachrichtigung über den vorhandenen Entwurf-Weg (leerer Entwurf mit Hinweistext) | Gleicher Prüf- und Sendeweg wie jede Antwort; ToDo erst nach erfolgreichem Versand erledigt | 2026-11-04 |
| Keine Policy, nur Namensliste und Name-gewählt-Prüfung | Es gibt vor PROJ-15 keine Anmeldung | 2026-11-04 |

---
<!-- Sections below are added by subsequent skills -->

## Implementation Notes (Frontend + Backend)
**Gebaut (2026-11-04):**
- Tabelle `ticket_todos` (Migration `2026_11_04_100000`), Modell `TicketTodo` (Beschreibung und Hinweistext verschlüsselt), Enums `TodoStatus`, `TodoKind` in `app/Todo/`. Dienste: `TodoService` (Anlegen, Ändern, atomares Abschließen, Entwurf für die Benachrichtigung), `TodoBoard` (Listen), `TodoRules` (gemeinsame Regeln für Formular und Antwort-Editor).
- Routen: `GET /todos`, `POST /tickets/{number}/todos`, `PATCH /todos/{todo}`, `POST /todos/{todo}/abschliessen`, `POST /todos/{todo}/oeffnen`. Controller `TicketTodoController`, Requests `StoreTicketTodoRequest`, `UpdateTicketTodoRequest`, `CompleteTicketTodoRequest`, `TodoListRequest`.
- Oberfläche: Blade-Komponenten `todo/panel` (Startseite), `todo/row`, `todo/dialog`, `todo/ticket-block` (Ticketseite), Seite `todos/index`, Seitenleisten-Eintrag „ToDos“ mit Zähler der eigenen offenen ToDos, Kennzeichen „ToDo“ in der Ticketliste.
- Antwortversand (`AnalysisController::sendReply`): neue Nachbehandlung „Ticket offen lassen“ (Status `open`), optionale interne Notiz (`ZammadReplySender::addNote`, zweiter Aufruf nach der Antwort), ToDo-Zeilen (`todos[]`, Schlüssel `reply:{Analyse}:{Index}`). ToDos und Notiz entstehen erst nach erfolgreichem Versand; scheitert eines davon, meldet die Antwort `note_failed` bzw. `todos_failed`, die gesendete Antwort bleibt gültig und wird nicht wiederholt.
- Kundenbenachrichtigung: Abschließen legt einen leeren Entwurf (Muster Direkt antworten) mit dem Hinweistext an und setzt „Benachrichtigung offen“; nach erfolgreichem Versand dieses Entwurfs (auch im Pfad „Antwort war bereits angekommen“) wird das ToDo `done`. Ein zweites Abschließen öffnet denselben Entwurf. „Ohne Benachrichtigung abschließen“ wird vermerkt (`notify_skipped`).
- Tests: `tests/Feature/PROJ-70-TicketTodosTest.php`, `tests/Feature/PROJ-70-ReplyTodosTest.php` (33 Tests grün); PROJ-19, 60, 66, 67, 68, 69, 5 weiterhin grün.

**Abweichungen / Hinweise:**
- Die Startseite zeigt höchstens 15 offene ToDos je Ansicht, weitere stehen auf der Seite „ToDos“.
- Für die Benachrichtigung wird der Entwurf auch bei bereits geschlossenem Ticket angelegt (anders als „Direkt antworten“); der Versand setzt den Status wie gewählt.
- Oberfläche (Alpine-Dialog, ToDo-Zeilen im Antwort-Editor) ist noch nicht im Browser geprüft; das gehört in `/qa`.

## Tech Design (Solution Architect)

### Grundentscheidung
Backend nötig: Datenbank der App (MySQL), kein Browser-Speicher, weil ToDos von allen Mitarbeitern gesehen werden. Keine neuen Pakete. Zammad bleibt unberührt, außer beim Antwortversand (bestehende Wege).

### A) Seitenstruktur
```
Seitenleiste
+-- neuer Eintrag "ToDos" (Zähler offener eigener/überfälliger ToDos)

Startseite (Tickets)
+-- Ticketsuche (vorhanden)
+-- ToDo-Bereich (neu)
|   +-- Reiter: Meine | Alle offen | Überfällig
|   +-- ToDo-Zeile: Beschreibung, Ticket-Link, Zuständiger, Fälligkeit, Art
|   |     (überfällig rot, "wartet auf ..." getrennt)
|   +-- Schnellaktionen: Abschließen, Zuständigen wechseln
|   +-- "Alle ToDos ansehen" -> Seite ToDos
|   +-- Leerzustand
+-- Ticketliste (vorhanden, Zeile bekommt Kennzeichen "offene ToDos")

Seite "ToDos" (neu)
+-- Filter: offen/erledigt/alle, Zuständiger
+-- Liste, 100 je Seite, Blättern, 12 Monate
+-- Anlegen/Bearbeiten-Dialog

Ticketseite (vorhanden)
+-- Block "ToDos zu diesem Ticket" (neu; offene zuerst, erledigte eingeklappt)
+-- Dialog "ToDo anlegen" (Beschreibung, Zuständiger, Frist 1/3/7 Tage/ohne, Art, Kunde informieren)
+-- Antwort-Editor (vorhanden)
    +-- Abschnitt "ToDos zu dieser Antwort" (0..n schnelle Zeilen)
    +-- Nachbehandlung: Ticket schließen | Wiedervorlage | offen lassen
    +-- Feld "Interne Notiz" (neu)
```

### B) Daten (Klartext)
Neue Tabelle für ToDos. Jedes ToDo hat:
- Ticket (Zammad-ID als stabiler Schlüssel, dazu Ticketnummer für Anzeige und Link)
- Beschreibung, Zuständiger (Name aus der festen Liste), Fälligkeit (Datum, optional)
- Art: direkt oder wartet auf Ereignis, mit Freitext "Worauf wird gewartet?"
- Status: offen, Benachrichtigung offen, erledigt
- Kunde nach Erledigung informieren (ja/nein) und Hinweistext für den Entwurf; Verweis auf den vorbereiteten Entwurf
- Herkunft (manuell, aus Antwort; später KI), Verweis auf die erzeugende Analyse, externer Ereignisverweis (beides in Phase 1 ohne Funktion)
- Angelegt von / erledigt von mit Zeitpunkten
- Schutzschlüssel gegen doppeltes Anlegen

Die ToDos hängen nicht an der Ticketliste der App, sondern nur an der Zammad-ID: Bereinigung oder Schließen eines Tickets lässt sie unberührt. Die Ticketliste fragt nur ab, ob offene ToDos zu einem Ticket existieren (für das Kennzeichen).

### C) Wiederverwendete Bausteine
- Nutzerauswahl (Cookie, `StaffDirectory`) für Anlegende und Zuständige, Hinweis "Bitte wähle zuerst deinen Namen."
- Antwortversand `AnalysisController::sendReply` samt Schutz gegen Doppelversand; ToDos werden erst nach erfolgreichem Versand angelegt.
- Direkt-Antwort-Mechanismus (`ManualReplyController`, leerer Entwurf) für den vorbereiteten Entwurf bei Kundenbenachrichtigung.
- Schließen-Dialog und Muster der Startseiten-Liste für Dialog und Liste.
- Aktualisierung der Ticketliste nach Schreibaktionen wie beim Schließen.

### D) Tech-Entscheidungen
Siehe Technical Decisions unten.

### E) Reihenfolge
1. Datenbank und Modell, Anlegen/Ändern/Abschließen mit Tests
2. Startseiten-Bereich, Seite "ToDos", Block am Ticket, Seitenleiste
3. Antwort-Editor: ToDos beim Versand, interne Notiz ohne Schließen
4. Kundenbenachrichtigung nach Erledigung
5. Kennzeichen in der Ticketliste

### F) Dependencies
Keine neuen Pakete.

## QA Test Results

**Getestet:** 2026-11-04 · Stand: Code-Review gegen alle Abnahmekriterien, automatisierte Tests, Sicherheitsprüfung. Die Bedienung im Browser hat der Auftraggeber selbst geprüft („funktioniert sehr gut“); Cross-Browser- und Responsive-Tests (375/768/1440 px) habe ich nicht durchgeführt.

### Abnahmekriterien
- ToDo-Bereich und Übersicht (7 Kriterien): bestanden. Belegt durch `PROJ-70-TicketTodosTest` (Ansichten, überfällig, Ticket geschlossen oder nicht in der Liste, Leerzustand, 12 Monate, 100 je Seite, Kennzeichen in der Ticketliste, ToDos am Ticket).
- Anlegen, Bearbeiten, Abschließen (7 Kriterien): bestanden, mit einer Einschränkung: Die Liste zeigt „wartet auf Ereignis“-ToDos mit eigenem Hinweis (Badge), aber nicht in einem getrennten Abschnitt.
- Anlegen beim Antwortversand (5 Kriterien): bestanden (`PROJ-70-ReplyTodosTest`: Versand mit Schließen, Fehler ohne ToDos, Wiederholung ohne Dubletten, Fehler beim Anlegen lässt die Antwort gültig).
- Antwort mit interner Notiz ohne Schließen (3 Kriterien): bestanden.
- Kundenbenachrichtigung nach Erledigung (5 Kriterien): bestanden.
- Berechtigung und Schutz (2 Kriterien): bestanden.
- Edge Cases: bestanden bis auf „Zuständiger nicht mehr in der Liste“ (siehe BUG-1).

### Bugs
| ID | Schwere | Beschreibung | Schritte | Workaround |
|----|---------|--------------|----------|------------|
| BUG-1 | Medium | Ein ToDo, dessen Zuständiger aus der Namensliste entfernt wurde, wird nicht als „Zuständiger nicht mehr in der Liste“ markiert. Beim Bearbeiten ist das Auswahlfeld leer; wird gespeichert, ohne neu zu wählen, übernimmt stillschweigend der Bearbeitende. | Name aus `staff.names` entfernen, ToDo öffnen und bearbeiten | Beim Bearbeiten bewusst einen Namen wählen |
| BUG-2 | Medium | Ein ToDo mit „Benachrichtigung offen“ bleibt hängen, wenn der vorbereitete Entwurf nicht mehr der neueste Vorschlag des Tickets ist (z. B. danach wurde eine KI-Analyse erstellt) oder die Vorschläge gelöscht wurden: „Antwort öffnen“ führt zu einem Entwurf, der nicht gesendet werden kann. | Bei einem ToDo mit Kundenbenachrichtigung „Erledigt, Kunde informieren“ wählen, dann am Ticket „Vorschlag erstellen“ | „Ohne Benachrichtigung abschließen“ und die Antwort normal senden |

Kein Critical- oder High-Bug. **Behoben (2026-11-04):** BUG-1 (Markierung „Zuständiger nicht mehr in der Liste“, Bearbeiten ohne neue Wahl behält den Zuständigen, Platzhalter „– Name wählen –“ im Dialog) und BUG-2 („Antwort öffnen“ geht über das ToDo: ein nicht mehr sendbarer Entwurf wird durch einen neuen ersetzt, ein sendbarer wieder geöffnet).

### Regression
- Gesamte Suite: 2201 Tests, 2196 bestanden. Fehlgeschlagen: zwei bekannte PROJ-56-Tests (Sichtbarkeit der Prüfmeldungen, offene Entscheidung, nicht Teil von PROJ-70) und `PROJ-1-AppShellTest` (zählte 6 statt 7 Einträge der Seitenleiste durch den neuen Eintrag „ToDos“). Die Erwartung wurde auf 7 Einträge samt „ToDos“ angepasst; danach grün.
- Die Tests zu Senden (PROJ-19), Anhängen, Ticketliste (PROJ-67), Schließen (PROJ-68), Direkt antworten (PROJ-69) und Ticket-Eingang (PROJ-66) sind grün.

### Sicherheitsprüfung
- CSRF: alle Formulare mit `@csrf`, die Fetch-Aufrufe senden den Token; getestet.
- Schreibende Routen hinter `staff.selected`, Namen nur aus der festen Liste (Zuweisung und Filter validiert); `GET /todos` ist wie die anderen Übersichten ohne Login lesbar (kein Login im MVP, PROJ-15).
- Eingaben: Form Requests für alle Eingaben, `$fillable` gesetzt; Ausgaben maskiert (Test mit Skript-Text); Beschreibung und Hinweistext in der Datenbank verschlüsselt (getestet).
- Mehrfachaufrufe: Anlegen per Dialog-Token und beim Versand per Schlüssel idempotent, Abschließen atomar; Rate Limits auf Anlegen und Abschließen.
- Keine Geheimnisse in Antworten oder Seiten; der Zammad-Token bleibt wie bisher im Header des Servers.

### Tests
- `tests/Feature/PROJ-70-TicketTodosTest.php` und `tests/Feature/PROJ-70-ReplyTodosTest.php`: 44 Tests, alle grün. Unit-Tests nicht nötig: die Logik ist über die Feature-Tests abgedeckt.

### Entscheidung
**Bereit für den Einsatz** (keine Critical/High-Bugs). BUG-1 und BUG-2 wurden behoben und mit Tests abgesichert (2026-11-04). Status **Approved**.

## Nachträge (2026-11-06)
- **Häufige ToDos:** Auswahl „Sendungsnummer nachreichen“, „(Teil-)Erstattung vornehmen“, „Nachforschungsauftrag Versand“ im Sendeformular und im ToDo-Dialog; setzt „Wartet auf Ereignis“, „Kunde informieren“ und den Entwurf der späteren Antwort (Texte unter Einstellungen > Mail-Anzeige, Platzhalter in eckigen Klammern). Zuständig „Alle“ ist Standard; „Wartet auf Ereignis“ ist eine Auswahl im Feld „Fällig“.
- **Reklamationsnummer:** Hat der Fall eine Reklamation, gilt deren Nummer (`R1-…`) statt der Originalbestellung: zuerst die Nummer aus dem Antworttext, sonst die neueste Reklamation der Bestellung in EOCS, sonst die des Tickets (`App\Todo\TodoCaseContext`). Nennt der Antworttext eine Reklamation, ist „Sendungsnummer nachreichen“ im Sendeformular vorbelegt (nichts gespeichert vor dem Senden). Die Nummer steht am ToDo in `external_ref` (Grundlage für PROJ-77).
- **Versand der Reklamation:** `EocsClient::claims()` lädt die Sendungen mit; Kopfbereich und KI-Kontext zeigen Versanddienstleister und Sendungsnummer der Reklamation.

## Deployment
_To be added by /deploy_
