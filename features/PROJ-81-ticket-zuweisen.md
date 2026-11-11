# PROJ-81: Ticket zuweisen und Zuweisung zurücknehmen

## Status: In Progress
**Created:** 2026-11-11
**Last Updated:** 2026-11-11

## Dependencies
- Requires: PROJ-6 (Zammad-Ticket laden), PROJ-19 (persönliche Zammad-Tokens), PROJ-67 (Ticketübersicht, Owner-ID), PROJ-68 (Muster: Schreiben nach Zammad mit interner Notiz)
- Weiterer Baustein von PROJ-29 (Ticketbearbeitung in der App)

## Hintergrund
Ein Ticket einem Kollegen zu übergeben oder die eigene Zuweisung wieder freizugeben geht bisher nur in Zammad. Die Zuweisung soll aus CSA heraus erfolgen und in Zammad als Owner festgehalten werden. CSA liest den Owner schon (Ansichten „Meine“ und „Allgemein offen“) und versteht die neue Zuweisung daher auf dem bisherigen Weg.

## User Stories
- Als **Mitarbeiter** will ich ein offenes Ticket aus CSA einem Kollegen (oder mir) zuweisen, damit ich dafür nicht nach Zammad wechseln muss.
- Als **Mitarbeiter** will ich bei der Zuweisung eine kurze Notiz mitgeben, damit der Kollege weiß, worum es geht.
- Als **Mitarbeiter** will ich eine Zuweisung zurücknehmen, damit das Ticket wieder allgemein offen ist.
- Als **Mitarbeiter** will ich bei der Rücknahme optional eine Notiz hinterlassen, damit Kollegen den Grund sehen.

## Out of Scope
- Gruppenwechsel, Statuswechsel (warten, wieder öffnen), Prioritäten.
- Benachrichtigung des Zugewiesenen über Zammad hinaus.
- Zuweisung mehrerer Tickets auf einmal.

## Acceptance Criteria
- [ ] Angenommen ein offenes Ticket und ein Name mit Zammad-Token, wenn die Ticketseite geöffnet wird, dann gibt es im Kopfbereich einen Knopf „Zuweisen“; ist das Ticket zugewiesen, zeigt der Kopf den Zugewiesenen und zusätzlich „Zuweisung zurücknehmen“.
- [ ] Angenommen „Zuweisen“ wird geklickt, dann erscheint ein Dialog mit Auswahl aller Mitarbeiter, die in CSA eine Zammad-User-ID haben (auch man selbst), und einem optionalen Notizfeld; „Abbrechen“ ändert nichts.
- [ ] Angenommen die Zuweisung wird bestätigt, dann setzt Zammad den Owner des Tickets im Namen des Mitarbeiters; eine eingegebene Notiz steht im selben Aufruf als interne Notiz am Ticket.
- [ ] Angenommen die Zuweisung wird zurückgenommen (jeder Mitarbeiter mit Token darf das), dann wird der Owner in Zammad geleert, der Status bleibt unverändert, eine optionale Notiz steht als interne Notiz am Ticket.
- [ ] Angenommen eine Zuweisung oder Rücknahme war erfolgreich, dann zeigt die Ticketseite und die Ticketliste sofort den neuen Stand (Zuweisung: Ticket in „Meine“ des Zugewiesenen; Rücknahme: in „Allgemein offen“).
- [ ] Angenommen das Ticket ist geschlossen, es ist kein Token hinterlegt, oder ein zurückgespultes Ticket (Testmodus) wird angezeigt, dann gibt es keine Knöpfe, und ein direkter Aufruf ändert nichts.
- [ ] Angenommen ein nicht gelisteter Name oder einer ohne Zammad-User-ID wird als Ziel geschickt, dann wird die Zuweisung abgelehnt.
- [ ] Angenommen Zammad lehnt ab oder antwortet nicht, dann steht eine klare Meldung auf der Ticketseite, bei Zeitüberschreitung mit dem Hinweis, dass der Ausgang unklar ist.

## Tech Design (Solution Architect)

### Backend nötig?
Ja, aber keine eigene Datenhaltung: Zammad bleibt führend, CSA schreibt nur dorthin und übernimmt den neuen Stand über den bestehenden Sync (PROJ-66). Keine neue Tabelle, kein neues Paket.

### Aufbau der Oberfläche
```
Ticketseite (Kopfbereich, bestehende Kopfkarte)
+-- Anzeige „Zugewiesen an: Name“ (oder „nicht zugewiesen“)
+-- Knopf „Zuweisen“ (nur offenes Ticket, nur mit Token, nicht im Testmodus)
+-- Knopf „Zuweisung zurücknehmen“ (nur wenn zugewiesen)
+-- Dialog „Zuweisen“ (eine Seite, ein Dialog, wie „Ohne Antwort schließen“)
|   +-- Auswahl der Mitarbeiter mit Zammad-User-ID (auch man selbst)
|   +-- Notizfeld (optional)
|   +-- Abbrechen / Zuweisen
+-- Dialog „Zuweisung zurücknehmen“ (kurze Rückfrage + optionale Notiz)
```
Beide Dialoge folgen dem bestehenden Schließen-Dialog (PROJ-68), also keine neue Bedienung. Der Kopf zeigt den Zugewiesenen, den die Ticketseite beim Laden aus Zammad liest.

### Datenmodell (Klartext)
- Neu gespeichert wird nichts. Der Owner liegt in Zammad und im bestehenden Ticketspiegel (`owner_zammad_id`, `owner_name`).
- Beim Laden eines Tickets liest CSA zusätzlich den aktuellen Owner aus Zammad, damit der Kopf nie veraltet ist.
- Die Auswahlliste kommt aus der festen Namensliste mit hinterlegter Zammad-User-ID.

### Ablauf
1. Mitarbeiter wählt Ziel und optional Notiz, CSA prüft Name (gelistet, mit User-ID), Ticket (offen, kein Testmodus) und Token des Mitarbeiters.
2. CSA sendet **einen** Zammad-Aufruf im Namen des Mitarbeiters: Owner setzen (bzw. bei Rücknahme auf „kein Owner“) plus optionale interne Notiz.
3. Nach Erfolg synchronisiert CSA das Ticket sofort, damit die Ticketliste den Stand zeigt (Zuweisung: „Meine“ des Zugewiesenen; Rücknahme: „Allgemein offen“). Das ist dasselbe Vorgehen wie beim Schließen.
4. Fehler (abgelehnt, nicht erreichbar, Zeitüberschreitung) erscheinen als Meldung auf der Ticketseite; bei Zeitüberschreitung mit Hinweis, dass der Ausgang unklar ist.

### Tech-Entscheidungen
- **Web-Routen mit Formular-Post und Weiterleitung** (zwei: zuweisen, zurücknehmen), wie beim Schließen. Kein JSON, kein Alpine-State außer dem Dialog.
- **Eigene Form Request** für Ziel und Notiz; Berechtigung dort (Token vorhanden, nicht Testmodus), wie `CloseTicketRequest`.
- **Erweiterung des bestehenden Zammad-Schreibwegs** (`ZammadReplySender`) um eine Methode „Owner setzen“ statt eines neuen Dienstes: Token-Handling, Fehlerabbildung und Zeitüberschreitung sind dort schon gelöst.
- **Neue Fehlertexte** in der bestehenden Fehlerklasse (`ReplyProblem`), analog zu den Schließen-Texten.
- **Owner im Ticket-Objekt ergänzen**, damit der Kopf Zugewiesenen und Knopfzustand kennt.

### Abhängigkeiten
Keine neuen Pakete.

## Implementation Notes

### Frontend (2026-11-11)
- Neu: `components/ticket/assign-dialog.blade.php` (ein Dialog, zwei Formulare: Zuweisen mit Auswahl und Notiz, Zurücknehmen mit Notiz; Ereignisse `assign-ticket` / `unassign-ticket`).
- `ticket/header.blade.php`: Anzeige „Zugewiesen an“, Knöpfe „Zuweisen“ und „Zuweisung zurücknehmen“ (nur bei `canAssign`, Rücknahme nur mit Owner); Icon `user` ergänzt.
- `tickets/show.blade.php`: erwartet `$canAssignTickets`, `$assignees`, `$assignee`, `$currentStaffName`; Dialog nur bei offenem, nicht zurückgespultem Ticket.
- `Ticket` hat `ownerId`, `ZammadClient::ticket` füllt es.

### Backend (2026-11-11)
- Routen `tickets.assign` (Feld `mitarbeiter`, `notiz`) und `tickets.unassign` (`notiz`), je 20 Aufrufe pro Minute, im Controller `TicketAssignController`.
- `AssignTicketRequest`: Token nötig, kein Testmodus (`stand`), Ziel nur aus `StaffDirectory::assignableNames()` (gelistet und mit Zammad-User-ID), Notiz höchstens 2000 Zeichen.
- `ZammadReplySender::assign`: ein PUT mit `owner_id` (Rücknahme = 1) und optionaler interner Notiz; Fehlertexte in `ReplyProblem::assignMessage`.
- Geschlossene Tickets werden nicht geändert. Nach Erfolg läuft sofort ein Sync, die Ticketseite zeigt den neuen Stand.
- Der View-Composer liefert `canAssignTickets`, `assignees`, `currentStaffName`; den Zugewiesenen ermittelt `show.blade.php` über `nameForZammadUserId`. Ein Owner, der nicht in der Namensliste steht, erscheint als „Mitarbeiter in Zammad“.
- Tests: `tests/Feature/PROJ-81-AssignTicketTest.php` (11 Tests, grün, zusammen mit den PROJ-68-Tests). Die vollständige Suite ist noch nicht gelaufen.
- Hinweis: Das Rücknehmen kann ein Mitarbeiter mit Token auch bei einem Owner außerhalb der Namensliste.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Zuweisbar sind alle Mitarbeiter mit Zammad-User-ID, auch man selbst | Übergabe an Kollegen und Selbstübernahme aus einem Dialog | 2026-11-11 |
| Jeder Mitarbeiter mit Token darf eine Zuweisung zurücknehmen | Kein Ticket bleibt bei Abwesenheit hängen | 2026-11-11 |
| Rücknahme leert den Owner, Status bleibt | Ticket wird wieder „Allgemein offen“, keine Nebenwirkung auf den Status | 2026-11-11 |
| Notiz optional, als interne Notiz, bei Zuweisung und Rücknahme | Wie PROJ-68, nachvollziehbar ohne Pflichtfeld | 2026-11-11 |

### Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Ein PUT mit Owner und optionaler interner Notiz | Owner und Notiz können nicht auseinanderfallen (wie PROJ-68) | 2026-11-11 |
| Schreiben mit persönlichem Token des gewählten Namens | Änderung erscheint in Zammad im Namen des Mitarbeiters | 2026-11-11 |
| Ziel über Zammad-User-ID, nie über den Namen | Namen kommen in Zammad mehrfach vor und können sich ändern (wie `StaffDirectory::zammadUserId`) | 2026-11-11 |
| Rücknahme = Owner-ID 1 | Zammads Wert für „kein Owner“; CSA behandelt ID ≤ 1 bereits als leer (`ZammadClient::ticketHead`) | 2026-11-11 |
| Eigene Form Request und Erweiterung von `ZammadReplySender` statt neuem Dienst | Token, Fehlerabbildung und Zeitüberschreitung sind dort gelöst; gleiches Muster wie Schließen | 2026-11-11 |
| Owner beim Laden des Tickets aus Zammad lesen, nichts neu speichern | Kopf zeigt immer den Zammad-Stand; Ticketspiegel wird nach Erfolg sofort synchronisiert | 2026-11-11 |
