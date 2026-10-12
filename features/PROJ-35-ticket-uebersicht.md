# PROJ-35: Ticket-Übersicht

## Status: Approved
**Created:** 2026-10-12
**Last Updated:** 2026-10-12

## Dependencies
- Requires: PROJ-6 (Zammad-Ticket laden) – jeder Abruf eines Tickets wird festgehalten
- Requires: PROJ-9, PROJ-11 (Analyse, Protokoll) – Stand „analysiert“ und Übernahme des Entwurfs
- Requires: PROJ-10 (Ergebnisansicht) – Kopieren-Knopf des Antwortentwurfs
- Requires: PROJ-28 (Übersetzung) – Stand „übersetzt“
- Requires: PROJ-5 (Nutzerauswahl), PROJ-32 (Admin-Rolle) – wer ruft ab; Seite nur für Admins
- Vorbereitung für: PROJ-29 (Ticketbearbeitung in der App – die App wird zur Arbeitsoberfläche über Zammad)

## Ausgangslage
Ein Admin sieht heute nicht, welche Tickets mit der App bearbeitet wurden. Gespeichert sind nur Analysen, Übersetzungen und Zusammenfassungen. Das bloße Abrufen eines Tickets wird nirgends festgehalten, und ob ein Antwortentwurf kopiert wurde (unverändert oder angepasst), ebenfalls nicht; nur die Bearbeitung des Entwurfs ist gespeichert. Die Seite ist der erste Schritt dahin, dass die App künftig vollständig auf Zammad aufsetzt und Mitarbeiter nicht mehr direkt in Zammad arbeiten.

## User Stories
- Als Admin möchte ich eine zeitlich sortierte Liste aller in der App abgerufenen Tickets sehen, damit ich weiß, womit die App tatsächlich gearbeitet wird.
- Als Admin möchte ich je Ticket erkennen, ob es nur abgerufen, übersetzt oder analysiert wurde, damit ich den Nutzen der einzelnen Schritte einschätzen kann.
- Als Admin möchte ich sehen, ob der Antwortentwurf 1:1 kopiert, angepasst kopiert oder nur bearbeitet wurde, damit ich die Qualität der Vorschläge an echtem Verhalten messe, nicht nur am Klick-Feedback.

## Out of Scope
- Inhalte des Tickets, der Analyse oder des Entwurfs in der Liste (nur Link zum Ticket)
- Gesamtliste aller Analysen und Feedbacks mit Filtern nach Produkt, Kundengruppe usw. – PROJ-14
- Bearbeiten, Antworten oder Statuswechsel in der App – PROJ-29
- Tickets, die nur in Zammad bearbeitet wurden (die App kennt nur, was sie selbst abgerufen hat)
- Auswertungen über Zeit oder Personen (Dashboards sind Non-Goal)

## Acceptance Criteria

### Erfassung
- [x] Angenommen ein Ticket wird erfolgreich aus Zammad geladen, wenn die Ticketseite angezeigt wird, dann ist das Ticket mit erstem und letztem Abruf, Anzahl der Abrufe, abrufendem Namen, Kundenname und Betreff festgehalten
- [x] Angenommen das Ticket wird erneut geöffnet, wenn es geladen wird, dann steigt die Anzahl, der letzte Abruf und Name werden aktualisiert, Kundenname und Betreff folgen dem Stand in Zammad
- [x] Angenommen ein Abruf schlägt fehl (Ticket nicht gefunden, Zammad nicht erreichbar), wenn die Fehlerseite erscheint, dann wird nichts festgehalten
- [x] Angenommen der Testmodus ist aktiv und ein Ticket wird zurückgespult (PROJ-32), wenn es geladen wird, dann wird der Abruf nicht festgehalten; Testläufe bleiben von echten Daten getrennt
- [x] Angenommen ein Antwortentwurf wird kopiert, wenn der Kopieren-Knopf geklickt wird, dann wird an der Analyse festgehalten: Zeitpunkt, Name und ob der Text vom Original der KI abweicht; ein Fehler dabei verhindert das Kopieren nicht
- [x] Angenommen der Entwurf wird bearbeitet, wenn er gespeichert wird, dann ist der Zeitpunkt der Bearbeitung auch ohne Entschlüsselung des Inhalts auswertbar; „Original wiederherstellen“ nimmt ihn zurück
- [x] Angenommen eine ältere Analyse eines Tickets, wenn dort kopiert wird, dann wird nichts festgehalten (nur die neueste Analyse zählt, wie beim Bearbeiten)

### Liste
- [x] Angenommen der gewählte Name ist Admin, wenn er die Seitenleiste betrachtet, dann gibt es den Eintrag „Ticket-Übersicht“; für Nicht-Admins fehlt er, und die Seite lehnt den Zugriff ab (403)
- [x] Angenommen Tickets wurden abgerufen, wenn die Seite angezeigt wird, dann stehen sie nach letztem Abruf sortiert, neueste zuerst, mit den Spalten Ticketnummer (Link zum Ticket), Kundenname, Betreff, Stand, letzter Abruf (Datum, Uhrzeit, Name), Anzahl Abrufe
- [x] Angenommen ein Ticket, wenn der Stand bestimmt wird, dann gilt die höchste zutreffende Stufe: „Nur abgerufen“ · „Übersetzt“ · „Analysiert“ · „Bearbeitet, nicht kopiert“ · „Kopiert (unverändert)“ · „Kopiert (angepasst)“; übersetzt gilt, sobald mindestens eine Nachricht tatsächlich übersetzt wurde (nicht bei deutschen Nachrichten)
- [x] Angenommen ein Ticket ist analysiert, wenn die Zeile angezeigt wird, dann stehen daneben die Anzahl der Analysen und, falls vorhanden, die Feedback-Stufe der neuesten Analyse
- [x] Angenommen die Seite, wenn der Admin einen Stand als Filter wählt, dann zeigt die Liste nur diese Tickets; neben jedem Filter steht die Anzahl
- [x] Angenommen mehr als 50 Tickets, wenn die Seite angezeigt wird, dann ist sie in Seiten geteilt
- [x] Angenommen es gibt noch keine abgerufenen Tickets, wenn die Seite angezeigt wird, dann steht „Noch keine Tickets abgerufen“
- [x] Angenommen Analysen aus Testläufen, wenn der Stand bestimmt wird, dann bleiben sie unberücksichtigt

### Aufbewahrung und Nachtrag
- [x] Angenommen ein Ticket wurde seit mehr als 12 Monaten nicht abgerufen, wenn die tägliche Bereinigung läuft, dann wird sein Eintrag gelöscht (zusammen mit den übrigen Kundeninhalten)
- [x] Angenommen Analysen und Übersetzungen bestanden schon vor der Funktion, wenn diese eingeführt wird, dann entstehen dafür Einträge mit dem frühesten Zeitpunkt als erstem Abruf; „nur abgerufen“ ist für frühere Tickets nicht bekannt

## Edge Cases
- **Ticket nur in Zammad bearbeitet:** erscheint nicht; die Liste zeigt nur, was die App abgerufen hat.
- **Mehrfach kopiert:** der letzte Kopier-Vorgang zählt.
- **Entwurf nach dem Kopieren bearbeitet:** Stand bleibt „Kopiert“ (mit dem Zustand beim Kopieren); die Bearbeitung ist nicht gleich der Kopie.
- **Analyse gelöscht oder bereinigt (PROJ-11):** die Zahlen an der Analyse bleiben erhalten, der Stand bleibt.
- **Ticketnummer aus mehreren Browsern gleichzeitig:** ein Eintrag je Ticket, der letzte Abruf gewinnt.
- **Kein Name gewählt:** der Abruf wird trotzdem festgehalten, der Name bleibt leer.

## Technical Requirements
- Kundenname und Betreff verschlüsselt gespeichert (wie Inhalte der Analysen); Aufbewahrung 12 Monate
- Keine Abfragen je Zeile (kein N+1); Seitenaufbau auch bei einigen tausend Tickets zügig
- Vergleich „unverändert/angepasst“ auf dem Server gegen das Original der KI, nicht auf dem Stand des Browsers

## Implementation Notes (2026-10-12)
- **Erfassung der Abrufe:** `App\Analysis\TicketActivityLog::recordVisit()` wird in `TicketController::show` nach erfolgreichem Zammad-Abruf aufgerufen; Tabelle `ticket_visits` (ein Eintrag je Ticket, Kundenname und Betreff verschlüsselt). Zurückgespulte Testmodus-Ansichten (`rewoundTo`) werden bewusst gar nicht erfasst, statt sie zu kennzeichnen.
- **Kopieren-Klick:** `POST /tickets/{number}/analyse/{analysis}/kopiert` (`AnalysisController::markCopied`), der Vergleich mit dem KI-Original läuft serverseitig über `AnalysisPanel::originalReply()`. Neue Spalten an `analyses`: `reply_edited_at`, `reply_copied_at`, `reply_copied_by`, `reply_copied_changed`. Der Editor ruft den Endpunkt nach dem Kopieren im Hintergrund auf (`noteCopy()`), Fehler werden still ignoriert.
- **Übersicht:** `App\Analysis\TicketOverview` (Stand je Ticket aus der neuesten echten Analyse, Übersetzungen mit Status ≠ `german`), `TicketOverviewController`, Route `ticket-overview.index` (`/ticket-uebersicht`), Ansicht `resources/views/ticket-overview/index.blade.php`, Eintrag in der Seitenleiste nur für Admins. Filter und Paginierung (50) laufen im Speicher über alle Einträge, weil die Daten auf 12 Monate begrenzt sind.
- **Aufbewahrung:** `AnalysisStore::purge()` löscht Besuche über `TicketActivityLog::purge()`; das Log von `analysis:purge` enthält nun auch `visits`.
- **Nachtrag:** Die Migrationen legen Besuchseinträge für bereits analysierte oder übersetzte Tickets an und tragen `reply_edited_at` aus früheren Bearbeitungen nach.
- **Tests:** `tests/Feature/PROJ-35-TicketOverviewTest.php` (16 Tests); `PROJ-12`-Test zum Bereinigungs-Log um den Schlüssel `visits` ergänzt.
- Offen: `/qa`, Test mit echten Daten im Browser, `npm run build` für neue Tailwind-Klassen.

## QA Test Results

**Tested:** 2026-10-12 · **Methode:** automatisierte Tests, Code-Review, Prüfung der Nachtrags-Migration an der Entwicklungsdatenbank. **Nicht getestet:** Browser (Chrome, Firefox, Safari) und die Breiten 375/768/1440 px; die Tabelle ist in einem horizontal scrollbaren Rahmen (`min-w-[56rem]`) gebaut, das Aussehen ist aber nicht am Bildschirm geprüft.

### Acceptance Criteria Status
#### Erfassung
- [x] Abruf wird mit Zeiten, Name, Kundenname und Betreff festgehalten (Test)
- [x] Erneuter Abruf zählt hoch, Kundenname und Betreff folgen Zammad (Test)
- [x] Fehlgeschlagener Abruf erfasst nichts (Test)
- [x] Zurückgespulte Testmodus-Ansicht wird nicht erfasst, die Ansicht des ganzen Verlaufs schon (Test, im QA ergänzt)
- [x] Kopieren wird mit Zeit, Name und „unverändert/angepasst“ festgehalten, serverseitig verglichen (Test)
- [x] Bearbeiten setzt den Zeitpunkt, „Original wiederherstellen“ nimmt ihn zurück (Test)
- [x] Ältere Analyse, falsche Ticketnummer, unbekannte Analyse, fehlender Name: abgelehnt (409/404/409, Test)

#### Liste
- [x] Eintrag und Seite nur für Admins, sonst 403, Eintrag fehlt (Test)
- [x] Reihenfolge und Spalten: Ticketnummer, Kundenname, Betreff, dann Stand und Abruf (Test)
- [x] Höchste zutreffende Stufe; deutsche Nachrichten zählen nicht als übersetzt (Test)
- [x] Anzahl der Analysen und Feedback der neuesten Analyse (Test)
- [x] Filter mit Zählern, Seiten à 50, leerer Zustand (Test)
- [x] Testlauf-Analysen bleiben unberücksichtigt (Test)

#### Aufbewahrung und Nachtrag
- [x] Bereinigung löscht Besuche nach 12 Monaten, jüngere bleiben (Test, `analysis:purge` gibt die Zahl aus)
- [x] Nachtrag: an der Entwicklungsdatenbank geprüft (18 Einträge für 17 analysierte und 1 übersetztes Ticket, 3 frühere Bearbeitungen nachgetragen); kein automatischer Test, weil die Migration nur einmal läuft

### Edge Cases Status
- [x] Nur in Zammad bearbeitete Tickets erscheinen nicht (Eigenschaft der Erfassung)
- [x] Mehrfach kopiert: letzter Vorgang zählt (Test)
- [x] Kein Name gewählt: Abruf mit leerem Namen (Test)
- [x] Neueste Analyse bestimmt den Stand (Test)
- [ ] Gleichzeitige erste Abrufe desselben Tickets: siehe BUG-1

### Security Audit Results
- [x] Autorisierung: Seite serverseitig auf Admins beschränkt (403 für Nicht-Admin und ohne Namen); Endpunkt „kopiert“ prüft Ticketnummer, Analyse und Aktualität
- [x] CSRF: Hintergrundanfrage sendet das Token im Kopf, Route liegt in der Web-Gruppe
- [x] XSS: Kundenname und Betreff werden maskiert (Test mit `<script>` und `<img onerror>`)
- [x] Datenschutz: Kundenname und Betreff verschlüsselt in der Datenbank (Test), 12 Monate Aufbewahrung; Admin-Seite ist die einzige Ausgabe
- [x] Eingaben: `stand` nur aus fester Liste, `page` mindestens 1, Text des Endpunkts auf die Länge des Entwurfs begrenzt
- Hinweis: Die Admin-Rolle hängt wie bei PROJ-32 am gewählten Namen (Cookie), also ohne Anmeldung; das ist die bekannte MVP-Grenze bis PROJ-15. Kein Rate Limiting für den Endpunkt „kopiert“ (intern, ohne Kosten).

### Regression
- Gesamte Suite: 902 Tests grün (vor dem ergänzten Test); PROJ-10, 11, 12, 6, 28, 32, 1, 25 einzeln geprüft. Ein bestehender Test (PROJ-12, Bereinigungs-Log) musste um den Schlüssel `visits` ergänzt werden.
- „Über die App“: kein Schritt von „So funktioniert es“ wird durch dieses Feature nutzbar, nichts zu ändern.

### Bugs Found

#### BUG-1: Gleichzeitige erste Abrufe desselben Tickets können die Ticketseite mit einem Fehler beenden
- **Severity:** Low
- **Status:** Offen
- **Beschreibung:** `TicketActivityLog::recordVisit()` sperrt mit `lockForUpdate()` nur eine vorhandene Zeile. Öffnen zwei Browser dasselbe, noch nie abgerufene Ticket im selben Moment, fügen beide ein; der zweite Einfügeversuch verletzt den Unique-Index auf `ticket_number` und die Ticketseite endet mit einem Serverfehler. Ein erneutes Laden hilft. Allgemein gilt: jeder Datenbankfehler beim Erfassen kann die Ticketansicht verhindern, obwohl das Erfassen nur Nebensache ist.
- **Behebung (Vorschlag):** `upsert`/`firstOrCreate` mit Wiederholung bei Unique-Fehler, und das Erfassen so kapseln, dass ein Fehler nur protokolliert wird.
- **Priority:** Nice to have

#### Hinweis (kein Bug)
- Filter und Seiten laufen im Speicher über alle Einträge der letzten 12 Monate; bei einigen tausend Tickets unproblematisch, bei deutlich mehr wäre eine Abfrage mit Datenbankfilter nötig.

### Summary
- **Acceptance Criteria:** 15/15 bestanden
- **Bugs Found:** 1 total (0 critical, 0 high, 0 medium, 1 low)
- **Security:** Pass
- **Production Ready:** YES (Browser- und Responsive-Test steht aus und sollte beim ersten Einsatz nachgeholt werden)
- **Recommendation:** Freigeben; BUG-1 bei Gelegenheit beheben.
