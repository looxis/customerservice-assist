# PROJ-61: Anhänge an die Antwort anhängen

## Status: In Review
**Created:** 2026-10-25
**Last Updated:** 2026-10-25

## Dependencies
- Requires: PROJ-19 (Antwort direkt aus der App über Zammad senden), PROJ-10 (Ergebnisansicht mit bearbeitbarem Antwortentwurf)
- Verwandt: PROJ-60 (Bildanhänge ansehen)

## Ausgangslage
Eine Antwort, die aus der App an Zammad übergeben wird, kann keinen Anhang tragen. Wer dem Kunden eine PDF-Datei oder einen Screenshot mitschicken will, muss die Antwort in Zammad neu schreiben oder dort nachträglich ergänzen.

## User Stories
- Als Mitarbeiterin möchte ich der Antwort vor dem Senden eine PDF-Datei oder ein Bild anhängen, damit der Kunde alles in einer Mail bekommt.
- Als Mitarbeiterin möchte ich einen Screenshot mit Strg+V in die Antwort einfügen können, damit ich ihn nicht erst speichern und auswählen muss.
- Als Mitarbeiterin möchte ich vor dem Senden sehen, welche Anhänge mitgehen, und sie wieder entfernen können.

## Out of Scope
- Anhänge, die der Kunde geschickt hat, automatisch weiterleiten.
- Weitere Dateiformate (Word, Excel, ZIP, Videos), mehr als 5 MB.
- Dauerhaftes Speichern der gesendeten Anhänge in der App: Sie gehen direkt an Zammad und werden nicht aufbewahrt.
- Anhänge bei internen Notizen und bei anderen Aktionen als dem Senden der Antwort.
- Ziehen und Ablegen von Dateien (Drag and Drop) und Bildbearbeitung im Editor.

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

- [ ] Angenommen der Versandbereich der Antwort ist sichtbar, wenn der Mitarbeiter auf „Datei anhängen“ klickt und eine PDF-, PNG-, JPG-, JPEG- oder WebP-Datei wählt, dann erscheint sie in der Liste der Anhänge mit Name und Größe.
- [ ] Angenommen der Mitarbeiter hat einen Screenshot in der Zwischenablage, wenn er im Antworttext oder im Versandbereich Strg+V drückt, dann wird das Bild als Anhang mit dem Namen „Screenshot-JJJJ-MM-TT-HHMM.png“ hinzugefügt und der Text bleibt unverändert.
- [ ] Angenommen die Zwischenablage enthält nur Text, wenn der Mitarbeiter Strg+V drückt, dann wird der Text wie bisher eingefügt.
- [ ] Angenommen ein Anhang ist in der Liste, wenn der Mitarbeiter auf „Entfernen“ klickt, dann verschwindet er und wird nicht gesendet.
- [ ] Angenommen die Anhänge zusammen sind größer als 5 MB, wenn der Mitarbeiter eine weitere Datei hinzufügt, dann wird sie abgelehnt und eine verständliche Meldung nennt die Grenze.
- [ ] Angenommen es sind schon 5 Anhänge hinzugefügt, wenn eine sechste hinzukommt, dann wird sie abgelehnt.
- [ ] Angenommen eine Datei hat ein anderes Format, wenn sie gewählt wird, dann wird sie abgelehnt und die erlaubten Formate werden genannt.
- [ ] Angenommen die Vorschau vor dem Senden ist geöffnet, wenn Anhänge vorhanden sind, dann nennt die Vorschau die Namen der Anhänge.
- [ ] Angenommen Anhänge sind vorhanden, wenn der Mitarbeiter auf „Senden“ klickt, dann geht die Antwort mit den Anhängen als Nachricht an den Kunden in Zammad, und in Zammad stehen die Dateien an der Nachricht.
- [ ] Angenommen das Senden gelingt, wenn die Seite aktualisiert wird, dann steht im Protokoll der Antwort, welche Dateien (Name, Größe) mitgegangen sind; die Dateien selbst werden nicht gespeichert.
- [ ] Angenommen eine Datei ist laut Inhalt kein erlaubter Typ (zum Beispiel eine umbenannte Programmdatei), wenn der Server sie prüft, dann wird die Antwort nicht gesendet und die Fehlermeldung nennt die Datei.
- [ ] Angenommen das Senden schlägt fehl (zum Beispiel Zeitüberschreitung), wenn die Meldung erscheint, dann bleiben Text und Anhänge im Editor erhalten.

## Edge Cases
- Zwei Dateien mit gleichem Namen: Beide gehen mit, die zweite bekommt eine Nummer.
- Dateinamen mit Sonderzeichen oder Pfadbestandteilen werden bereinigt.
- Der Schutz vor Doppelversand (PROJ-19) gilt unverändert, auch wenn die Mail wegen der Anhänge länger dauert.
- Das Zeitlimit zu Zammad wird bei Anhängen erhöht, damit ein 5-MB-Anhang nicht als „unklar“ endet.
- Der Mitarbeiter fügt dasselbe Bild zweimal ein: Es wird zweimal angehängt, bis er eines entfernt.

## Technical Requirements
- Sicherheit: Typ und Größe werden auf dem Server geprüft, nicht nur im Browser; Dateiname bereinigt; keine Speicherung auf dem App-Server.
- Datenschutz: Anhänge enthalten Kundendaten und gehen nur an Zammad.

## Open Questions
- [ ] Gilt die Grenze von 5 MB insgesamt oder je Datei? (Annahme: insgesamt, 2026-10-25; bei Bedarf in /refine ändern.)

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| PDF, PNG, JPG, JPEG, WebP, höchstens 5 MB, bis zu 5 Dateien | Reicht für Screenshots und Unterlagen; große Mails werden von Empfängern abgewiesen und belasten die Zammad-Schnittstelle | 2026-10-25 |
| Strg+V fügt Screenshots ein | Der Screenshot liegt bereits in der Zwischenablage; spart Speichern und Auswählen | 2026-10-25 |
| Anhänge werden nicht in der App gespeichert | Sie gehen direkt an Zammad, so entsteht keine zweite Kopie von Kundendaten | 2026-10-25 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|

---

## Tech Design (Solution Architect)
- **Eingabe:** Der Editor schickt die Antwort jetzt als Formular mit Dateien (`multipart/form-data`) statt als reines JSON. Die Dateien liegen nur im Browser, bis der Mitarbeiter auf „Antwort versenden“ klickt.
- **Prüfung auf dem Server** (`SendReplyRequest`): höchstens 5 Dateien, zusammen höchstens 5 MB, Typ nach Dateiinhalt (PDF, PNG, JPG, JPEG, WebP). Namen werden bereinigt und bei Doppelungen nummeriert.
- **Versand:** Die Dateien gehen als Base64 im bestehenden Aufruf an Zammad (`article.attachments`), in einem Zug mit Antwort und Status. Das Zeitlimit für diesen Aufruf ist bei Anhängen länger (30 statt 10 Sekunden). Der Schutz vor Doppelversand bleibt unverändert.
- **Protokoll:** Namen und Größen stehen in der neuen Spalte `reply_sent_attachments` der Analyse; die Dateien selbst werden nicht gespeichert.
- **Oberfläche:** Anhangsliste mit „Entfernen“, Knopf „Datei anhängen“, Strg+V im Antwortbereich (nur Bilder, wenn kein Text in der Zwischenablage ist), Hinweis in der Vorschau.

### Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Formular mit Dateien statt Base64 im JSON | Weniger Speicher im Browser, normale Dateiprüfung von Laravel | 2026-10-25 |
| Typ aus dem Inhalt prüfen (`mimetypes`) zusätzlich zur Endung | Eine umbenannte Programmdatei darf nicht als Bild durchgehen | 2026-10-25 |
| Keine Speicherung der Dateien | Kein zweiter Ort für Kundendaten; Zammad ist die Ablage | 2026-10-25 |
| Längeres Zeitlimit nur bei Anhängen | Große Base64-Aufrufe brauchen länger, normale Antworten bleiben schnell | 2026-10-25 |

## Implementation Notes (2026-10-25)
- Backend: `OutgoingAttachment`, `OutgoingReply::$attachments`, `ZammadReplySender::send()` (Nutzlast und Zeitlimit), `SendReplyRequest` (Regeln, Gesamtgröße), `AnalysisController::outgoingFiles()`, Migration `reply_sent_attachments`.
- Frontend: `analysis/reply-editor.blade.php` (Alpine: `addFiles`, `removeFile`, `paste`, Versand als `FormData`).
- Tests: Block „attachments (PROJ-61)“ in `tests/Feature/PROJ-19-SendReplyTest.php` (8 Tests). Strg+V und die Dateiauswahl im Browser sind noch nicht geprüft.
- Nebenbei: `phpunit.xml` setzt die Zammad-Token der Mitarbeiter (`ZAMMAD_TOKEN_*`) für Tests auf leer, damit die lokale `.env` die Tests nicht beeinflusst (zwei Tests schlugen deswegen fehl).

## QA Test Results

**Tested:** 2026-10-25
**App URL:** automatisierte Tests, Logik des Editors in Node; kein Versand mit Anhang im echten Zammad
**Tester:** QA Engineer (AI)

### Acceptance Criteria Status

#### AC-1 bis AC-4: Dateien hinzufügen, Strg+V, Entfernen
- [x] Datei wird mit Name und Größe angezeigt, ein nicht erlaubtes Format wird mit Meldung abgelehnt, Entfernen leert Liste und Meldung (Logik des Editors in Node geprüft).
- [x] Strg+V mit Bild in der Zwischenablage: Anhang „Screenshot-JJJJ-MM-TT-HHMM.png“, Standardverhalten unterdrückt; mit Text oder Text und Bild bleibt der Text, nach dem Senden wird nichts mehr angehängt (Node).
- [ ] Nicht im echten Browser mit einer echten Zwischenablage geprüft (Bildschirmfoto mit Windows+Umschalt+S, dann Strg+V im Antwortfeld).

#### AC-5 bis AC-7: Grenzen
- [x] Zusammen über 5 MB: abgelehnt, im Browser vorab und auf dem Server (422, Meldung).
- [x] Sechste Datei: abgelehnt, im Browser vorab und auf dem Server.
- [x] Andere Formate und umbenannte Programmdateien werden am Inhalt erkannt und abgelehnt (Server).

#### AC-8: Vorschau
- [x] Die Vorschau nennt die Namen der Anhänge (Oberfläche, nur im Browser sichtbar).

#### AC-9 bis AC-11: Versand und Protokoll
- [x] Dateien gehen als `article.attachments` (Name, Base64, Typ) im selben Aufruf an Zammad.
- [x] Name und Größe stehen im Protokoll der Antwort; die Dateien werden nicht gespeichert.
- [x] Ohne Dateien enthält die Nutzlast kein Feld `attachments`.

#### AC-12: Fehler beim Senden
- [x] Nach einem Fehler können dieselben Dateien erneut gesendet werden (Test; im Editor bleiben Liste und Text erhalten).

### Edge Cases Status
- [x] Gleichnamige Dateien: die zweite bekommt eine Nummer.
- [x] Pfadteile und Steuerzeichen im Namen werden bereinigt.
- [x] Schutz vor Doppelversand unverändert (alle bisherigen PROJ-19-Tests grün).
- [ ] Zeitlimit 30 Sekunden bei Anhängen: im Code gesetzt, aber nicht mit einem echten 5-MB-Versand gegen Zammad geprüft (Grenze des Zammad-Servers für die Größe unbekannt).

### Security Audit Results
- [x] CSRF: Der Versand läuft über `fetch` mit dem CSRF-Header.
- [x] Typ und Größe werden auf dem Server geprüft, nicht nur im Browser.
- [x] Der Empfänger kommt weiter vom Server, nicht aus dem Formular.
- [x] Rate limiting: Route auf 10 Aufrufe pro Minute begrenzt (unverändert).
- [x] Dateien liegen nur während des Aufrufs im Arbeitsspeicher beziehungsweise in PHPs temporärem Upload-Ordner.

### Bugs Found
Keine neuen Fehler in PROJ-61 gefunden. Zwei Tests schlugen anfangs fehl, weil die lokale `.env` Zammad-Token enthielt; `phpunit.xml` setzt diese für Tests auf leer.

### Summary
- **Acceptance Criteria:** 12/12 erfüllt in Tests und Logikprüfung; echter Versand mit Anhang und echtes Strg+V im Browser noch offen
- **Bugs Found:** 0
- **Security:** Pass
- **Production Ready:** Bedingt: nach einem Versand mit PDF und Screenshot an ein Test-Ticket in Zammad
- **Recommendation:** In Review, bis der Auftraggeber einen echten Versand bestätigt
