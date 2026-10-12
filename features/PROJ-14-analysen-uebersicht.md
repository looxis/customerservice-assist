# PROJ-14: Analysen-Übersicht

## Status: Planned
**Created:** 2026-10-02
**Last Updated:** 2026-10-12

## Dependencies
- Requires: PROJ-11 (Analyse-Protokoll) – dauerhaft gespeicherte Analysen mit Kennzahlen in eigenen Spalten
- Requires: PROJ-12 (Feedback und Wissenslücke) – Feedback-Stufe, gemeldete Wissenslücken, Erfolgszahl
- Requires: PROJ-35 (Ticket-Übersicht) – Kopier-Stand der Entwürfe (unverändert/angepasst kopiert, bearbeitet) je Analyse; gleiche Admin-Regel
- Requires: PROJ-32 (Testmodus) – Kennzeichnung von Testläufen; Admin-Rolle
- Verwandt: PROJ-17 (zweiter LLM-Anbieter) und PROJ-31 (Modell und Prompts) – Vergleich nach Modell und Prompt-Version

## Ausgangslage
PROJ-35 zeigt je **Ticket**, wie weit die Arbeit mit der App ging. Es fehlt die Sicht je **Analyse**: Wie gut sind die Vorschläge, und wovon hängt es ab (Kundengruppe, Produkt, Kategorie, Modell, Prompt-Version, Mitarbeiter)? Heute gibt es nur die eine Erfolgszahl der letzten 28 Tage auf der Wissenslücken-Seite. Der Erfolg des MVP wird am Feedback und am Verhalten (kopiert, angepasst, verworfen) gemessen; dafür braucht der Admin eine filterbare Liste mit wenigen Kennzahlen.

## User Stories
- Als Admin möchte ich alle Analysen als Liste sehen, damit ich nachvollziehe, was die App geliefert hat und wie es genutzt wurde.
- Als Admin möchte ich nach Zeitraum, Kundengruppe, Produkt, Kategorie, Feedback-Stufe, Kopier-Stand, Mitarbeiter, Modell und Prompt-Version filtern, damit ich Schwächen eingrenzen kann (z. B. „Reklamationen zu Produkt X, die verworfen wurden“).
- Als Admin möchte ich über der Liste wenige Kennzahlen zur gefilterten Menge sehen, damit ich die Erfolgsquote (Ziel laut PRD: mindestens 70 % unverändert oder leicht angepasst) pro Gruppe vergleichen kann.
- Als Admin möchte ich von einer Zeile direkt zur Analyse im Ticket springen, damit ich einzelne Fälle prüfen kann.
- Als Admin möchte ich sehen, zu welchen Analysen Wissenslücken gemeldet wurden, damit ich Schwachstellen im Wissen finde.
- Als Admin möchte ich Testläufe und fehlgeschlagene Analysen bei Bedarf zuschalten, damit ich Ausfälle erkenne, ohne dass sie die Erfolgszahlen verfälschen.

## Out of Scope
- Eigene Liste für Feedbacks oder Wissenslücken, gemischte Zeitleiste – Feedback ist Teil der Analysezeile, Wissenslücken behalten ihre Seite (PROJ-12) und sind von der Analysezeile aus erreichbar
- CSV-Export oder andere Dateiausgabe – bei Bedarf als Ergänzung (z. B. für die Baseline-Auswertung)
- Zugriff für Nicht-Admins und „Meine Analysen“ – kommt, wenn es Anmeldung gibt (PROJ-15)
- Diagramme, Dashboards, Auswertungen über Zeit als Verlauf (Non-Goal im PRD)
- Kosten und Dauer als Kennzahlen oben – die Spalten sind gespeichert, werden hier aber nicht ausgewertet
- Kundeninhalte (Ticketinhalt, Antwortentwurf, Kommentar) in der Liste – nur Ticketnummer und Link
- Löschen oder Ändern von Analysen aus der Liste (bleibt am Ticket, PROJ-11)
- Evaluation gegen das Gold-Testset – PROJ-13
- Zusammenführen mit der Ticket-Übersicht (PROJ-35); beide Seiten bleiben getrennt

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Zugriff und Aufruf
- [ ] Angenommen der gewählte Name ist Admin, wenn er die Seitenleiste betrachtet, dann gibt es den Eintrag „Analysen“ (neben „Ticket-Übersicht“)
- [ ] Angenommen ein Nicht-Admin oder kein gewählter Name, wenn die Seite aufgerufen wird, dann wird der Zugriff abgelehnt (403) und der Eintrag fehlt in der Seitenleiste

### Liste
- [ ] Angenommen es gibt Analysen, wenn die Seite angezeigt wird, dann steht je Analyse eine Zeile, neueste zuerst, mit Zeitpunkt, Mitarbeiter, Ticketnummer (Link zur Analyse im Ticket), Kundengruppe, Produkten, Kategorie, Modell und Prompt-Version, Feedback-Stufe, Kopier-Stand und Anzahl gemeldeter Wissenslücken
- [ ] Angenommen die Standardansicht, wenn die Seite zum ersten Mal geöffnet wird, dann zeigt sie nur erfolgreiche, echte Analysen der letzten 28 Tage
- [ ] Angenommen mehr als 50 Treffer, wenn die Liste angezeigt wird, dann ist sie in Seiten geteilt, und die Filter bleiben beim Blättern erhalten
- [ ] Angenommen keine Treffer, wenn die Liste angezeigt wird, dann steht „Keine Analysen für diese Auswahl“ mit dem Hinweis, Filter zu lockern
- [ ] Angenommen eine Analyse, deren Kundeninhalte bereinigt oder gelöscht wurden (PROJ-11), wenn die Zeile angezeigt wird, dann bleiben Kennzahlen, Feedback-Stufe und Kopier-Stand sichtbar, und der Link führt zum Ticket mit dem Hinweis aus PROJ-11
- [ ] Angenommen eine Analyse, deren Kopier-Stand unbekannt ist (vor PROJ-35 erzeugt), wenn die Zeile angezeigt wird, dann steht dort „–“ statt „nicht kopiert“
- [ ] Angenommen kein Kundeninhalt, wenn die Liste angezeigt wird, dann enthält sie weder Ticketinhalt, Antwortentwurf noch Kommentar

### Filter
- [ ] Angenommen die Filter, wenn der Admin den Zeitraum wählt, dann stehen „7 Tage“, „28 Tage“, „90 Tage“, „12 Monate“ und ein frei wählbares Von–Bis zur Auswahl
- [ ] Angenommen die Filter, wenn der Admin Kundengruppe, Produkt, Kategorie, Feedback-Stufe (unverändert, leicht, stark, verworfen, ohne Feedback), Kopier-Stand (kopiert unverändert, kopiert angepasst, bearbeitet nicht kopiert, nicht kopiert), Mitarbeiter, Modell oder Prompt-Version wählt, dann zeigt die Liste nur Treffer, die alle gewählten Filter erfüllen
- [ ] Angenommen die Auswahlen für Produkt, Modell und Prompt-Version, wenn sie angezeigt werden, dann bieten sie nur Werte, die in den gespeicherten Analysen vorkommen
- [ ] Angenommen gewählte Filter, wenn die Seite neu geladen oder die Adresse weitergegeben wird, dann bleibt die Auswahl erhalten (Filter stehen in der Adresse)
- [ ] Angenommen gewählte Filter, wenn der Admin „Zurücksetzen“ wählt, dann gilt wieder die Standardansicht
- [ ] Angenommen die Schalter „Testläufe anzeigen“ und „Fehlgeschlagene anzeigen“, wenn sie ausgeschaltet sind (Standard), dann fehlen diese Zeilen in Liste und Kennzahlen; eingeschaltet erscheinen sie mit Kennzeichen „Testlauf“ bzw. „Fehlgeschlagen“ (mit Fehlergrund, ohne Inhalt)
- [ ] Angenommen ein ungültiger Filterwert in der Adresse, wenn die Seite aufgerufen wird, dann wird er ignoriert und die Seite zeigt ohne ihn eine Liste

### Kennzahlen
- [ ] Angenommen eine gefilterte Menge, wenn die Seite angezeigt wird, dann stehen darüber: Anzahl Analysen, Anteil „unverändert nutzbar“ oder „leicht angepasst“ an den bewerteten, Anteil „verworfen“ an den bewerteten, Anteil ohne Feedback, Anteil 1:1 kopiert an den kopierten, Anzahl gemeldeter Wissenslücken
- [ ] Angenommen keine bewerteten Analysen in der Menge, wenn die Kennzahlen angezeigt werden, dann steht bei den Feedback-Anteilen „–“ statt einer Zahl
- [ ] Angenommen derselbe Zeitraum wie die Erfolgszahl der Wissenslücken-Seite, wenn beide ausgewertet werden, dann stimmen die Werte überein (gleiche Regel: bewertete echte Analysen, ohne Testläufe)

### Wissenslücken
- [ ] Angenommen zu einer Analyse wurden Wissenslücken gemeldet, wenn die Zeile angezeigt wird, dann steht dort die Anzahl als Link zur Wissenslücken-Seite

## Edge Cases
- **Sehr viele Analysen:** Seiten zu 50, Kennzahlen bleiben die der ganzen gefilterten Menge, nicht nur der Seite.
- **Mehrere Produkte je Analyse:** die Zeile zeigt alle, der Produkt-Filter trifft, wenn mindestens eins passt.
- **Analyse ohne Kundengruppe oder Produkt:** wird als „unklar“ bzw. „kein Produktbezug“ angezeigt und ist filterbar.
- **Prompt-Version oder Modell später entfernt:** Wert bleibt in der Liste und im Filter, solange es Analysen dazu gibt.
- **Kein Admin mehr nach Namenswechsel:** der nächste Aufruf liefert 403, bereits geladene Seite bleibt unverändert.
- **Zeitraum von nach bis oder in der Zukunft:** Fehlerhinweis am Filter, die Liste bleibt unverändert.
- **Aufbewahrung:** Kennzahlen ohne Kundendaten bleiben dauerhaft, deshalb zeigt die Liste auch Analysen, deren Inhalte nach 12 Monaten bereinigt sind.
- **Ticket in Zammad gelöscht:** der Link führt auf die Fehlerseite des Tickets (PROJ-6), die Zeile bleibt.

## Technical Requirements (optional)
- Auswertung nur aus den Kennzahlspalten, ohne Kundeninhalte zu entschlüsseln; Filterung und Zählung in der Datenbank
- Seitenaufbau bei einigen zehntausend Analysen zügig (Richtwert unter 1 Sekunde)
- Nur serverseitig geprüfter Admin-Zugriff; alle Texte maskiert ausgegeben

## Open Questions
- [ ] Soll die Erfolgszahl der Wissenslücken-Seite später hierher umziehen, oder bleiben beide? (Vorschlag: vorerst beide, gleiche Regel)
- [ ] Reicht der Kopier-Stand aus PROJ-35 für Analysen davor („–“), oder soll dafür ein Nachtrag aus dem Feedback abgeleitet werden? (Vorschlag: nein)
- [ ] Braucht die Baseline-Auswertung (50 historische Reklamationen) später einen Export? (Vorschlag: bei Bedarf als eigene Ergänzung)

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Eine Zeile je Analyse; Feedback ist Teil der Zeile, Wissenslücken bleiben auf eigener Seite | Eine Analyse ist die Einheit, an der Qualität gemessen wird; Wissenslücken haben bereits einen Bearbeitungsablauf | 2026-10-12 |
| Filter: Zeitraum, Kundengruppe, Produkt, Kategorie, Feedback-Stufe, Kopier-Stand, Mitarbeiter, Modell, Prompt-Version | Alle vorhandenen Kennzahlspalten; Modell und Prompt-Version für spätere Vergleiche (PROJ-17, PROJ-31) | 2026-10-12 |
| Testläufe und Fehlgeschlagene standardmäßig ausgeblendet, per Schalter zuschaltbar | Saubere Erfolgszahlen; Ausfälle trotzdem auffindbar | 2026-10-12 |
| Nur Admins | Zeigt Verhalten einzelner Mitarbeiter; wie Ticket-Übersicht und Wissenslücken; „Meine Analysen“ erst mit Anmeldung | 2026-10-12 |
| Wenige Kennzahlen zur gefilterten Menge, keine Kosten/Dauer, keine Diagramme | Dashboards sind Non-Goal; Zahlen genügen für den Vergleich gegen das 70-%-Ziel | 2026-10-12 |
| Kein Export in dieser Spec | Erst Liste und Filter; Export bei konkretem Bedarf | 2026-10-12 |
| Standardzeitraum 28 Tage | Passt zur Erfolgszahl (PRD: vier Wochen Einsatz) | 2026-10-12 |
| Priorität P0/P1 wird nicht mehr geführt | Entscheidung des Product Owners am 2026-10-12 | 2026-10-12 |

### Technical Decisions
<!-- Added by /architecture -->

---

## Tech Design (Solution Architect)
_To be added by /architecture_

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
