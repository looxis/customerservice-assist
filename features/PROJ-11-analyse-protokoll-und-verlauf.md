# PROJ-11: Analyse-Protokoll und Verlauf

## Status: Planned
**Created:** 2026-10-06
**Last Updated:** 2026-10-06

## Dependencies
- Requires: PROJ-5 (Nutzerauswahl) – wer analysiert, bearbeitet oder löscht
- Requires: PROJ-9 (Fallanalyse per LLM) – Analyse, Zusammenfassung, Metadaten
- Requires: PROJ-10 (Ergebnisansicht) – Anzeige, bearbeiteter Entwurf, letzte Analyse je Ticket
- Berührt: PROJ-32 (Testmodus) – Testläufe werden mitprotokolliert und gekennzeichnet; Admin-Rolle
- Wird genutzt von: PROJ-12 (Feedback zur Analyse), PROJ-13 (Evaluation), PROJ-14 (Analysen-Übersicht), PROJ-17 (Anbietervergleich), PROJ-28 (Übersetzungen dauerhaft), PROJ-31 (Prompt-Versionen)

## Ausgangslage
Analysen, Zusammenfassungen und bearbeitete Entwürfe liegen bisher nur vorübergehend (7 Tage) im Zwischenspeicher. Für den Einsatz im Alltag, für Feedback (PROJ-12) und Auswertungen (PROJ-14) müssen sie dauerhaft und nachvollziehbar gespeichert werden – mit klaren Fristen, weil sie Kundendaten enthalten.

## User Stories
- Als Mitarbeiter möchte ich frühere Analysen eines Tickets ansehen, damit ich nachvollziehen kann, was zu welchem Stand vorgeschlagen wurde.
- Als Kollegin möchte ich auch nach Wochen noch die Analyse und den angepassten Entwurf zu einem Ticket finden, z. B. wenn der Kunde erneut schreibt.
- Als Verantwortlicher möchte ich zu jeder Analyse genau sehen, welcher Text an die KI ging und was sie geantwortet hat, damit ich Fehler nachvollziehen und Analysen später mit neuem Prompt oder Modell wiederholen kann.
- Als Verantwortlicher möchte ich, dass Kundeninhalte nach 12 Monaten automatisch gelöscht werden, Kennzahlen aber für Auswertungen erhalten bleiben.
- Als Admin möchte ich auf Wunsch eines Kunden alle Analysen eines Tickets löschen können, damit wir Löschbegehren erfüllen.

## Out of Scope
- Feedback zur Analyse und „Wissenslücke melden“ – PROJ-12 (baut auf dem Protokoll auf)
- Gesamtliste aller Analysen mit Filter – PROJ-14
- Erneutes Rechnen alter Analysen mit neuem Prompt/Modell (Evaluation, Vergleich) – PROJ-13, PROJ-17
- Prompts und Modell auf der Einstellungsseite – PROJ-31
- Export von Analysen, Auskunft nach DSGVO als Datei
- Übernahme der bisher vorübergehend gespeicherten Analysen (nur Testdaten)

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Dauerhaft speichern
- [ ] Angenommen eine Analyse wird durchgeführt, wenn sie fertig ist, dann ist sie dauerhaft gespeichert mit Ticketnummer, Zeitpunkt, Name, Ergebnis, Formulareingaben, Quellen in der Fassung der Analyse, Metadaten (Anbieter, Modell, Prompt-Version, Wissensstand, Fingerabdrücke, Variante, Dauer, Tokens, Versuche) und ggf. Kennzeichen „Testlauf“ mit Stand
- [ ] Angenommen eine Analyse wird durchgeführt, wenn sie gespeichert wird, dann sind auch der vollständige an die KI gesendete Text (mit Platzhaltern statt Kontaktdaten) und die unveränderte Antwort der KI festgehalten
- [ ] Angenommen eine Analyse schlägt fehl (Zeitüberschreitung, ungültiges Ergebnis), wenn das passiert, dann wird ein Protokolleintrag ohne Inhalte gespeichert (Zeitpunkt, Name, Ticketnummer, Modell, Fehlerart, Dauer)
- [ ] Angenommen ein Mitarbeiter bearbeitet einen Antwortentwurf, wenn die Änderung gespeichert wird, dann bleibt sie dauerhaft erhalten, das KI-Original ebenso
- [ ] Angenommen eine Zusammenfassung wird erstellt oder bearbeitet, wenn sie gespeichert wird, dann ist sie dauerhaft gespeichert und die Kennzeichnung „vorübergehend gespeichert“ entfällt
- [ ] Angenommen für ein Ticket wurden Kundengruppe und Produkte gewählt, wenn das Ticket später geöffnet wird, dann ist die Wahl dauerhaft gemerkt (nicht mehr nur 7 Tage)
- [ ] Angenommen die App wird neu gestartet oder der Zwischenspeicher geleert, wenn ein Ticket geöffnet wird, dann sind Analysen, Entwürfe und Zusammenfassungen weiterhin vorhanden

### Frühere Analysen je Ticket
- [ ] Angenommen ein Ticket hat mehrere Analysen, wenn das Ergebnis angezeigt wird, dann gibt es eine aufklappbare Zeile „Frühere Analysen (N)“ mit je Datum, Name, Kundengruppe, Variante, Einstufung, Confidence und ggf. „Testlauf“
- [ ] Angenommen der Mitarbeiter wählt eine frühere Analyse, wenn sie angezeigt wird, dann steht sie an der Stelle des Ergebnisses mit dem Hinweis „Ältere Analyse vom … – zur neuesten“
- [ ] Angenommen eine ältere Analyse wird angezeigt, wenn der Mitarbeiter den Entwurf betrachtet, dann ist er nur lesbar und kopierbar, nicht bearbeitbar
- [ ] Angenommen ein Ticket hat nur eine Analyse, wenn das Ergebnis angezeigt wird, dann gibt es keine Zeile „Frühere Analysen“
- [ ] Angenommen der Testmodus ist aus, wenn die Liste angezeigt wird, dann erscheinen Testläufe nicht; im Testmodus erscheinen sie gekennzeichnet

### Protokoll einsehen (Admin)
- [ ] Angenommen ein Admin betrachtet eine Analyse, wenn er „Protokoll“ aufklappt, dann sieht er den gesendeten Text, die Antwort der KI und alle Metadaten
- [ ] Angenommen ein Nicht-Admin betrachtet eine Analyse, wenn das Ergebnis angezeigt wird, dann gibt es keinen Abschnitt „Protokoll“; die „Details zur Analyse“ (Name, Zeit, Modell, Prompt-Version, Wissensstand, Dauer) bleiben sichtbar

### Aufbewahrung und Löschen
- [ ] Angenommen eine Analyse ist älter als 12 Monate, wenn die tägliche Bereinigung läuft, dann werden gesendeter Text, KI-Antwort, Entwurf, Kontextfeld, Bestelldaten von Hand, eingesetzte Werte und Quellen-Texte gelöscht; erhalten bleiben Ticketnummer, Zeitpunkt, Name, Kundengruppe, Produkte, Kategorie, Einstufung, Confidence, Vorgänge, Knowledge-IDs, Metadaten und (ab PROJ-12) Feedback
- [ ] Angenommen eine Zusammenfassung oder gemerkte Wahl ist älter als 12 Monate (letzte Änderung), wenn die Bereinigung läuft, dann wird sie gelöscht
- [ ] Angenommen eine Analyse wurde bereinigt, wenn sie in einer Liste erscheint, dann steht dort „Inhalte nach 12 Monaten gelöscht“
- [ ] Angenommen ein Admin ist am Ticket, wenn er „Alle Analysen dieses Tickets löschen“ wählt und die Rückfrage bestätigt, dann werden alle Inhalte, Zusammenfassungen und die gemerkte Wahl des Tickets gelöscht; es bleibt ein Eintrag „Inhalte gelöscht von [Name] am …“ mit den Kennzahlen ohne Kundendaten
- [ ] Angenommen ein Nicht-Admin ist am Ticket, wenn er die Seite betrachtet, dann gibt es keine Löschfunktion, und ein Löschversuch wird abgelehnt

## Edge Cases
- **Analyse ohne Ergebnis** (Fehler): erscheint nicht in „Frühere Analysen“, nur im Protokoll (für PROJ-14).
- **Gelöschtes Ticket in Zammad / Ticket nicht mehr ladbar:** Analysen bleiben bis zur Frist gespeichert; Anzeige erst wieder, wenn das Ticket geladen werden kann (Liste in PROJ-14).
- **Zusammengeführtes Ticket in Zammad:** Analysen bleiben beim alten Ticket; kein Umzug.
- **Gleichzeitig zwei Analysen zum selben Ticket:** beide werden gespeichert; die zuletzt fertige ist die neueste.
- **Löschen während eine Analyse läuft:** die laufende Analyse wird nach dem Löschen noch gespeichert (als neue Analyse); kein Sperren.
- **Bereinigung fällt aus** (z. B. Zeitplan läuft nicht): wird beim nächsten Lauf nachgeholt; in der Knowledge-Übersicht/Über-Seite kein Hinweis nötig, aber Hinweis im Log.
- **Sehr viele Analysen je Ticket:** Liste zeigt alle, neueste zuerst.

## Technical Requirements (optional)
- Speicherung in der Datenbank (MySQL laut Tech-Stack); Kundeninhalte verschlüsselt.
- Tägliche automatische Bereinigung; Frist konfigurierbar (Standard 12 Monate).
- Keine Inhalte im Log.
- Admin-Prüfung serverseitig (wie PROJ-32).

## Open Questions
- [ ] Soll die Frist von 12 Monaten mit dem Auftragsverarbeitungsvertrag bzw. dem Verzeichnis von Verarbeitungstätigkeiten abgestimmt und dort eingetragen werden? (Wiedervorlage AVV ab 01.01.2027)
- [x] Admins sind Etienne, Johannes und Thomas (2026-10-06, in `config/staff.php`; sehen damit auch den Testmodus aus PROJ-32). Bis PROJ-15 nur über die Konfiguration.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Kundeninhalte 12 Monate, Kennzahlen ohne Kundendaten dauerhaft | Rückfragen und Reklamationsverläufe über eine Saison nachlesbar; Auswertungen (PROJ-14, Erfolgskennzahlen im PRD) bleiben möglich; Datensparsamkeit | 2026-10-06 |
| Gesendeter Text und unveränderte KI-Antwort werden protokolliert | Exakt nachvollziehbar und später mit neuem Prompt/Modell wiederholbar (PROJ-13, PROJ-17); enthält nur Platzhalter statt Kontaktdaten | 2026-10-06 |
| Frühere Analysen als aufklappbare Liste am Ergebnis, ältere nur lesbar | Kein Seitenwechsel; keine Verwirrung, welcher Entwurf gilt | 2026-10-06 |
| Admin löscht alle Analysen eines Tickets, mit Rückfrage und Löschvermerk | Löschbegehren erfüllbar; nachvollziehbar, wer gelöscht hat | 2026-10-06 |
| Protokoll (gesendeter Text, KI-Antwort) nur für Admins sichtbar (von mir entschieden, bitte prüfen) | Für die Arbeit am Ticket nicht nötig; enthält technische Details | 2026-10-06 |
| Fehlgeschlagene Analysen werden ohne Inhalte protokolliert (von mir entschieden, bitte prüfen) | Ausfälle und Kosten auswertbar (PROJ-14) | 2026-10-06 |
| Keine Übernahme der bisher vorübergehend gespeicherten Analysen (von mir entschieden, bitte prüfen) | Bisher nur Tests; spart Aufwand | 2026-10-06 |

### Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)
_To be added by /architecture_

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
