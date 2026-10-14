# PROJ-23: Entwurfs-Wissen bestätigen

## Status: Planned
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
- [ ] Ist `knowledge/` in der Umgebung, in der die App im Testbetrieb läuft, für den Webserver beschreibbar (Docker-Volume, Rechte)? Ist es das nicht, braucht die Funktion dort eine andere Lösung (nur Entwicklungsumgebung).
- [ ] Zählt ein Dokument als „verwendet“, wenn es nur abgerufen wurde, oder erst, wenn die KI es in `knowledge_ids` nennt? Vorschlag: beides getrennt zeigen.
- [ ] Soll der Hinweis zu verwandten Entwürfen (`related_knowledge`) sperren oder nur warnen? Vorschlag: nur warnen.
