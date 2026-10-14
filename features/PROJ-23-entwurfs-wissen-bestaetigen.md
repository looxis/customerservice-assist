# PROJ-23: Entwurfs-Wissen bestätigen

## Status: In Progress
**Created:** 2026-10-02
**Last Updated:** 2026-10-14

## Dependencies
- Requires: PROJ-3 (Knowledge einlesen und prüfen), PROJ-10 (Ergebnisansicht, Kennzeichnung von Entwurfs-Wissen), PROJ-24 (Knowledge-Übersicht und Dokumentseite).
- Nutzt: PROJ-32 (Admin-Liste in der Konfiguration), PROJ-11/PROJ-12 (Verwendung und Bewertung der Vorschläge als Belege).
- Verwandt: PROJ-15 (Benutzerverwaltung mit Login; löst später die Admin-Liste ab).

## Ausgangslage
Fast alle Wissensdateien stehen auf `draft` (110 Dateien, eine `active`). Die App verwendet `draft` mit und kennzeichnet es als Entwurfs-Wissen; was gute Ergebnisse liefert, soll ein Admin nach Prüfung als gültig bestätigen. Bisher geht das nur durch Bearbeiten der Datei von Hand. Es gibt keinen Weg in der App und keine Belege, auf deren Grundlage man entscheidet.

## User Stories
- Als Admin möchte ich ein Entwurfs-Dokument, das sich bewährt hat, per Klick als gültig bestätigen, damit es nicht mehr als Entwurf gekennzeichnet wird.
- Als Admin möchte ich vor dem Bestätigen sehen, wie oft das Dokument verwendet und wie die Vorschläge bewertet wurden, damit ich nicht blind entscheide.
- Als Admin möchte ich ein Dokument, das sich als falsch erweist, zurück auf Entwurf setzen oder als veraltet markieren, damit es nicht mehr oder nicht mehr als gültig verwendet wird.
- Als Wissensautor möchte ich, dass die App unvollständige Dokumente nicht als gültig durchgehen lässt, damit offene Punkte nicht als geltender Inhalt in Vorschläge geraten.

## Out of Scope
- Das Bearbeiten des Inhalts von Wissensdokumenten in der App (bleibt Handarbeit außerhalb der App, siehe `docs/KNOWLEDGE_AUTHORING_GUIDE.md`).
- Git-Commit oder Push durch die App: Die App ändert nur die Datei im Arbeitsordner, du committest und pushst selbst (z. B. über VSCode).
- Ein eigenes Protokoll der Statuswechsel und eine Erinnerung an das Committen in der App: Wer und wann steht später in der Git-Historie.
- Feste Schwelle für Verwendungen oder Bewertungen vor dem Bestätigen.
- Mehrere Dokumente auf einmal bestätigen (Sammelaktion).
- Eigene Berechtigung je Admin oder Login (PROJ-15); bis dahin gilt die Admin-Liste.

## Acceptance Criteria

### Knopf und Berechtigung
- [ ] Angenommen ein Admin öffnet die Dokumentseite eines `draft`-Dokuments, wenn die Seite erscheint, dann sieht er „Als gültig bestätigen“; ein Dokument mit `active` bietet „Zurück auf Entwurf“ und „Als veraltet markieren“, ein `deprecated`-Dokument „Zurück auf Entwurf“.
- [ ] Angenommen ein Mitarbeiter, der kein Admin ist, wenn er die Dokumentseite öffnet oder die Aktion direkt aufruft, dann sieht er keinen Knopf und die Aktion wird abgelehnt (403).
- [ ] Angenommen der Ordner `knowledge/` ist nicht beschreibbar, wenn der Admin die Seite öffnet, dann ist der Knopf gesperrt und ein Hinweis nennt den Grund.

### Voraussetzungen und Belege
- [ ] Angenommen das Dokument hat Prüfungsfehler oder enthält einen Abschnitt „Noch zu klären“ oder eine offene Frage, wenn der Admin „Als gültig bestätigen“ sucht, dann ist der Knopf gesperrt und nennt die Gründe.
- [ ] Angenommen ein Dokument wird bestätigt, wenn der Admin den Knopf wählt, dann zeigt die App vorher die Belege: in wie vielen Vorschlägen das Dokument verwendet wurde (ohne Testläufe), wie sie bewertet wurden (unverändert, leicht angepasst, stark verändert, verworfen, nicht bewertet) und bei dünner Datenlage den Hinweis „Bisher nur in N Vorschlägen verwendet“.
- [ ] Angenommen der Admin bestätigt die Aktion im Dialog, wenn die Belege dünn oder negativ sind, dann ist das trotzdem möglich; es gibt keine feste Schwelle.

### Wirkung
- [ ] Angenommen der Admin bestätigt „Als gültig bestätigen“, wenn die Aktion ausgeführt wird, dann steht in der Datei `status: active` und der Rest der Datei ist unverändert (Reihenfolge, Formatierung, Inhalt).
- [ ] Angenommen die Datei wurde geändert, wenn die App den Wissensstand neu liest, dann erscheint das Dokument ohne Kennzeichnung „Entwurf“ in neuen Vorschlägen; bereits gespeicherte Vorschläge bleiben unverändert.
- [ ] Angenommen „Als veraltet markieren“, wenn die Aktion ausgeführt wird, dann steht `status: deprecated` in der Datei und das Dokument wird in neuen Vorschlägen nicht mehr verwendet.
- [ ] Angenommen die Aktion ist ausgeführt, wenn die Seite wieder erscheint, dann steht dort eine Meldung, dass die Datei geändert, aber noch nicht committet ist.

### Fehler und Konflikte
- [ ] Angenommen die Datei wurde seit dem Öffnen der Seite verändert (z. B. in VSCode), wenn der Admin die Aktion ausführt, dann wird sie nicht ausgeführt, und die Seite erklärt, dass die Datei neu geladen werden muss.
- [ ] Angenommen das Schreiben schlägt fehl, wenn die Aktion ausgeführt wird, dann bleibt die Datei unverändert, und die Seite nennt den Fehler.
- [ ] Angenommen die Datei hat keinen oder einen ungültigen `status`-Eintrag, wenn die Seite erscheint, dann bietet sie keine Aktion an und weist auf den Prüfungsfehler hin.

## Edge Cases
- Zwei Admins öffnen dasselbe Dokument, einer bestätigt zuerst: der zweite sieht beim Ausführen die Meldung, dass der Status schon geändert ist (die Datei weicht vom geöffneten Stand ab).
- Die Datei hat Windows-Zeilenenden oder eine andere Reihenfolge der Kopfzeilen: nur die Zeile `status:` ändert sich, alles andere bleibt byte-genau.
- Das Dokument ist in Vorlagen oder Beispielordnern (`templates/`, `examples/`): keine Aktion (kein produktives Wissen).
- Ein bestätigtes Dokument hat viele Verwendungen, aber alle ohne Bewertung: die Belege zeigen „nicht bewertet“ statt einer Erfolgsquote.
- Das Dokument verweist in `related_knowledge` auf Entwürfe: die Seite zeigt einen Hinweis, wenn verwandtes Wissen noch `draft` ist, sperrt aber nicht.

## Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Die App ändert nur die Datei, kein Git-Commit | Die Produktionsumgebung kann nicht nach GitHub pushen; der Testbetrieb läuft aus der Entwicklungsumgebung, committet wird von Hand über VSCode | 2026-10-14 |
| Formale Sperre (Prüfungsfehler, „Noch zu klären“) plus Belege, keine feste Schwelle | Seltene Regeln kommen vielleicht nur einmal im Jahr vor; Belege helfen, ohne zu blockieren | 2026-10-14 |
| Drei Wechsel: bestätigen, zurück auf Entwurf, veraltet | Gleiche Mechanik, und „veraltet“ wird beim Pflegen der Wissensbasis gebraucht | 2026-10-14 |
| Kein eigenes Protokoll, keine Erinnerung zum Committen | Wer und wann steht in der Git-Historie; weniger Daten und weniger Umfang | 2026-10-14 |
| Nur Admins (Liste aus PROJ-32) | Es gibt noch keinen Login; die Liste ist die vorhandene Rolle | 2026-10-14 |

## Open Questions
- [x] Ist `knowledge/` im Testbetrieb für den Webserver beschreibbar? In der Sail-Umgebung ja (geprüft: der Container-Nutzer kann in `knowledge/` schreiben). Für eine andere Produktionsumgebung ist es offen; dort sperrt die App den Knopf mit Begründung (Prüfung beim Anzeigen).
- [x] Zählt ein Dokument als „verwendet“? Beides getrennt zeigen: „abgerufen“ (kam in den Prompt) und „von der KI genannt“ (stützt die Empfehlung). Beide stehen als Kennzahlen je Vorschlag vor.
- [x] Soll ein verwandter Entwurf sperren? Nein, nur warnen.

## Tech Design (Solution Architect)

### Grundidee
Die Funktion ist klein: Die vorhandene Dokumentseite der Knowledge-Übersicht bekommt einen Bereich „Status“, und eine neue Aktion ändert genau eine Zeile in der Datei. Es entstehen **keine neue Tabelle und keine neue Seite**. Alle Belege kommen aus Daten, die die App ohnehin speichert.

### A) Aufbau der Oberfläche
```
Dokumentseite (bestehend, nur für Admins erweitert)
+-- Kopf mit Titel, ID, Statusplakette (bestehend)
+-- Bereich „Status“ (neu, nur Admins)
|   +-- Aktueller Status in Klartext
|   +-- Voraussetzungen als Liste
|   |   +-- „Keine Prüfungsfehler“            (erfüllt / nicht erfüllt)
|   |   +-- „Keine offenen Punkte in der Datei“ (erfüllt / nicht erfüllt)
|   |   +-- „Datei ist beschreibbar“          (erfüllt / nicht erfüllt)
|   +-- Belege
|   |   +-- In wie vielen Vorschlägen abgerufen / von der KI genannt
|   |   +-- Bewertungen (unverändert, leicht angepasst, stark verändert, verworfen, nicht bewertet)
|   |   +-- Hinweis bei dünner Datenlage („Bisher nur in 2 Vorschlägen verwendet“)
|   +-- Hinweis, wenn verwandtes Wissen noch Entwurf ist
|   +-- Aktionen je nach Status
|       +-- Entwurf:   „Als gültig bestätigen“
|       +-- Gültig:    „Zurück auf Entwurf“ · „Als veraltet markieren“
|       +-- Veraltet:  „Zurück auf Entwurf“
+-- Bestätigungsdialog (neu): fasst Aktion und Belege zusammen, Knopf „Ja, ändern“
+-- Meldung nach der Aktion (neu): „Datei geändert, bitte committen“
```
Mitarbeiter, die keine Admins sind, sehen den Bereich nicht. Für Vorlagen und Beispiele (`templates/`, `examples/`) erscheint keine Aktion.

### B) Daten (in Klartext)
- **Keine neuen Daten.** Der Status steht weiterhin nur in der Wissensdatei.
- **Gelesen werden:** der Status und der Abschnitt „Noch zu klären“ aus der Datei, die Prüfergebnisse der Knowledge Base, und aus den Analysen die schon gespeicherten Kennzahlen: welche Dokumente abgerufen und welche von der KI genannt wurden, die Bewertung (Feedback) und ob es ein Testlauf war.
- **Geändert wird:** ausschließlich die Zeile `status:` im Kopf der Datei.
- **Ohne Inhalte:** Die Belege sind Zahlen. Kundeninhalte der Vorschläge werden nicht gelesen, damit funktioniert die Anzeige auch nach der Löschung der Inhalte nach 12 Monaten.

### C) Tech-Entscheidungen (begründet)
- **Gewöhnliche Seitenaktion statt eigener Schnittstelle:** Die Dokumentseite ist eine Blade-Seite; die drei Aktionen sind ein Formular mit Bestätigungsdialog (Alpine). Das passt zu den anderen Admin-Aktionen („Erledigt“ bei Wissenslücken).
- **Admin-Prüfung wie bei den bestehenden Admin-Funktionen:** Dieselbe Admin-Liste wie bei Testmodus und Wissenslücken; die Prüfung steht auf dem Server (Anzeigen und Ausführen), nicht nur in der Oberfläche. Das ist später durch PROJ-15 (Login) ersetzbar.
- **Nur die Statuszeile ändern, nicht die Datei neu schreiben:** Die Datei wird nicht eingelesen und neu ausgegeben, sondern nur die eine Zeile ersetzt. So bleiben Reihenfolge, Einrückung, Kommentare und Zeilenenden byte-genau; der Commit zeigt eine Zeile Änderung.
- **Sicher schreiben:** Zuerst in eine Zwischendatei im selben Ordner, dann die Datei auf einmal ersetzen; schlägt etwas fehl, bleibt das Original unverändert. Rechte der Datei bleiben erhalten.
- **Schutz vor veraltetem Stand:** Die Seite merkt sich den Fingerabdruck der Datei (gibt es seit PROJ-3). Beim Ausführen wird er verglichen; weicht die Datei ab (z. B. in VSCode geändert oder von einem anderen Admin bestätigt), passiert nichts und die Seite verlangt ein Neuladen.
- **Voraussetzungen auf dem Server erneut prüfen:** Prüfungsfehler, offene Punkte, Ordnerart und Beschreibbarkeit werden beim Ausführen noch einmal geprüft, nicht nur beim Anzeigen. Der Abschnitt „Noch zu klären“ wird mit derselben Regel erkannt wie die bestehende Warnung der Prüfung.
- **Belege aus den vorhandenen Kennzahlen:** Je Vorschlag sind die abgerufenen Dokumente und die von der KI genannten IDs als eigene Felder gespeichert, ebenso die Bewertung. Die Belege werden daraus zusammengezählt (ohne Testläufe und ohne fehlgeschlagene Vorschläge). Das ist schnell und braucht keine Inhalte.
- **Keine Sperre durch Belege:** Dünne oder negative Belege zeigen nur einen Hinweis; die feste Schwelle ist ausdrücklich nicht gewollt.
- **Wirkung auf neue Vorschläge:** Die App liest die Wissensdateien bei jeder Anfrage; nach der Änderung gilt der neue Status sofort. Kennzahlen in der Seitenleiste (60 Sekunden Zwischenspeicher) ziehen kurz danach nach. Bereits gespeicherte Vorschläge bleiben unverändert.
- **Kein Git aus der App:** Die App ruft kein Git auf. Der Wissensstand in neuen Protokollen zeigt wie nach jeder Handänderung den Stand der Entwicklungsumgebung, bis du committest.
- **Beschreibbarkeit prüfen:** Die Seite prüft Datei und Ordner auf Schreibrechte und sperrt den Knopf mit Begründung, damit die Funktion in einer schreibgeschützten Umgebung sauber ausfällt.

### D) Reihenfolge der Umsetzung
1. **Prüfen und Belege:** Voraussetzungen, Belege und Hinweise als Daten (ohne Oberfläche), mit Tests.
2. **Aktion:** Statuszeile ändern mit Fingerabdruckprüfung, sicherem Schreiben und allen Ablehnungsfällen, mit Tests.
3. **Oberfläche:** Bereich „Status“, Dialog und Meldungen auf der Dokumentseite.
4. **Dokumentation:** Authoring Guide („Auf `active` setzt der Admin per Klick“), Seite „Über die App“ und Spec-Notizen.

### E) Abhängigkeiten
- Keine neuen Pakete.
- Keine Datenbankänderung.
- Keine neue Konfiguration (die Admin-Liste existiert).

### F) Risiken und Grenzen
- **Rechte im Dateisystem:** In Sail ist der Ordner beschreibbar; in einer anderen Umgebung kann er es nicht sein, dann ist der Knopf gesperrt.
- **Doppelte Wahrheit:** Zwischen Klick und Commit liegt die Änderung nur im Arbeitsordner; wer dort `git checkout` ausführt, verliert sie. Das ist bewusst (kein Protokoll, keine Erinnerung).
- **Geändertes Dokument, alter Bewertungsstand:** Die Belege beziehen sich auf alle bisherigen Versionen des Dokuments, nicht nur die aktuelle; der Admin sollte sie als Tendenz lesen.

### Technische Entscheidungen
| Decision | Rationale | Date |
|----------|-----------|------|
| Nur die Zeile `status:` ändern, Datei nicht neu ausgeben | Der Commit zeigt eine Zeile; Formatierung und Zeilenenden bleiben | 2026-10-14 |
| Sicheres Schreiben über Zwischendatei und Ersetzen | Bei Fehlern bleibt das Original; kein halbes Schreiben | 2026-10-14 |
| Fingerabdruck der Datei als Schutz vor veraltetem Stand | Verhindert Überschreiben paralleler Änderungen aus VSCode oder von anderen Admins | 2026-10-14 |
| Voraussetzungen auf dem Server beim Ausführen erneut prüfen | Oberfläche allein ist kein Schutz | 2026-10-14 |
| Belege aus gespeicherten Kennzahlen, keine Inhalte | Schnell, datensparsam, auch nach der 12-Monats-Löschung | 2026-10-14 |
| Keine Datenbankänderung | Der Status gehört in die Datei (Git bleibt die Wahrheit) | 2026-10-14 |
| Admin-Prüfung der vorhandenen Liste, serverseitig | Keine neue Rolle; später durch PROJ-15 ersetzbar | 2026-10-14 |

## Implementation Notes
- **Backend:** `app/Knowledge/DocumentStatusChanger.php` (erlaubte Wechsel, Sperren, Zeilenersetzung, sicheres Schreiben, Fingerabdruckprüfung), `DocumentEvidence.php` (Belege aus `knowledge_ids` und `knowledge_fingerprints`, ohne Testläufe und Fehlgeschlagene), `StatusChangeException`, `ChangeDocumentStatusRequest` (Admin-Prüfung über `TestMode::isAvailable`), Route `POST /knowledge/dokument/{path}/status`, `KnowledgeController::changeStatus()`.
- **Oberfläche:** Bereich „Status“ in `knowledge/show.blade.php` (nur Admins), Bestätigung als Alpine-Formular unter den Knöpfen, Meldungen `status_changed` und `status_error`.
- **Schreiben:** Zwischendatei im selben Ordner, Rechte der Datei übernommen, dann Ersetzen. Eine Datei ohne eindeutige Zeile `status:` im Kopf wird abgelehnt.
- **Ausgeschlossen:** `templates/` und `examples/` (keine Aktion); Dokumente ohne ID oder mit unlesbarem Kopf.
- **Doku:** Authoring Guide beschreibt den Klick.
- **Tests:** `tests/Feature/PROJ-23-DocumentStatusTest.php` (12): Anzeige je Status, Belege, Sperre bei offenem Abschnitt, Änderung genau einer Zeile (auch mit CRLF), Wechsel in alle Richtungen, 403 für andere, veraltete Datei, direkte Anfrage trotz Sperre, ungültige Wechsel/Pfade, nicht schreibbare Datei, Zeilenersetzung.
- **Bitte im Browser prüfen:** Knöpfe, Bestätigungsfeld, Meldungen, 375/768 px; ein echter Klick auf ein Dokument im Arbeitsordner (danach in VSCode die eine geänderte Zeile ansehen und committen oder verwerfen).
- **Beobachtet:** Die Rechte der Datei auf dem Host (`etienne`, 644) und im Sail-Container müssen für den Webserver-Nutzer passen; die Seite sperrt den Knopf sonst mit Begründung.
