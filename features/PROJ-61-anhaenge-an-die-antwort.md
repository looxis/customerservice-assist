# PROJ-61: Anhänge an die Antwort anhängen

## Status: Planned
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
_To be added by /architecture_

## QA Test Results
_To be added by /qa_
