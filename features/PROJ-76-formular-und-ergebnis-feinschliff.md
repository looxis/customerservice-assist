# PROJ-76: Vorschlagsformular und Ergebnis weiter entschlacken

## Status: In Progress
**Created:** 2026-11-05
**Last Updated:** 2026-11-05

## Dependencies
- Requires: PROJ-73 (Ergebnisansicht entschlacken), PROJ-74 (Einführungsmodus), PROJ-52 (zusammengehörige Tickets), PROJ-40 (Formular „Vorschlag erstellen“)

## User Stories
- Als Kundenservice-Mitarbeiter möchte ich nach neuen Nachrichten direkt das Formular für einen neuen Vorschlag sehen, damit ich keinen Hinweis wegklicken und keinen Knopf drücken muss.
- Als Kundenservice-Mitarbeiter möchte ich das Feld „Zusätzliche Informationen“ leer vorfinden, damit ich keinen alten Text löschen muss; was ich beim letzten Vorschlag angegeben habe, hat die Gegenseite schon gelesen und beantwortet.
- Als Kundenservice-Mitarbeiter möchte ich keine Hinweise lesen, die nur wiederholen, was schon zu sehen ist (verwendete Regeln, geladene Bestellung).
- Als Kundenservice-Mitarbeiter möchte ich die zusammengehörigen Tickets eingeklappt sehen und nur bei Bedarf aufklappen.

## Out of Scope
- Löschen früherer Vorschläge; sie bleiben erreichbar (Verlauf und eingeklappter früherer Vorschlag)
- Änderungen am Inhalt der Vorschläge und am Prompt
- Das Verhalten bei Fortsetzung einer internen Klärung (PROJ-39)
- Erklärtexte auf Startseite und Einstellungen

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

- [ ] Angenommen seit dem letzten Vorschlag sind neue Nachrichten eingegangen, wenn der Mitarbeiter die Ticketseite öffnet, dann gibt es weder den Hinweis „Seit diesem Vorschlag sind neue Nachrichten eingegangen“ noch den Knopf „Neuen Vorschlag erstellen“; das Formular „Vorschlag erstellen“ ist aufgeklappt und der frühere Vorschlag steht darüber eingeklappt als „Früherer Vorschlag – seit dann sind neue Nachrichten eingegangen“.
- [ ] Angenommen der frühere Vorschlag ist eingeklappt, wenn der Mitarbeiter ihn aufklappt, dann ist er vollständig nutzbar (Antwortentwurf, Kopieren, Senden) wie bisher.
- [ ] Angenommen keine neuen Nachrichten seit dem Vorschlag, wenn die Seite öffnet, dann bleibt das Formular eingeklappt wie bisher.
- [ ] Angenommen es gibt einen früheren Vorschlag mit ausgefülltem Feld „Zusätzliche Informationen“, wenn das Formular erscheint, dann ist das Feld leer.
- [ ] Angenommen das Formular wurde mit Fehlern abgeschickt, wenn die Seite neu erscheint, dann bleibt der eingegebene Text im Feld erhalten.
- [ ] Angenommen im Formular wählt der Mitarbeiter ein Produkt, wenn der Wissensbereich bestimmt ist, dann erscheint darunter keine Zeile „Regeln für: …“.
- [ ] Angenommen die Bestellung wurde aus EOCS geladen, wenn das Formular erscheint, dann steht dort kein Hinweis „Bestellung: aus EOCS geladen und berücksichtigt. Auswahl und Laden stehen oben unter „Bestellungen“.“; Hinweise zu nicht geladenen oder fehlenden Bestellungen bleiben.
- [ ] Angenommen der Einführungsmodus ist aus, wenn der Bereich „Zusammengehörige Tickets“ im Formular erscheint, dann steht dort der Absatz „Der Verlauf dieser Tickets geht als früherer Verlauf mit in den Vorschlag …“ nicht; im Einführungsmodus steht er dort.
- [ ] Angenommen es gibt zusammengehörige Tickets, wenn das Formular erscheint, dann ist der Bereich eingeklappt und die Überschrift nennt, wie viele Tickets gefunden und wie viele vorgewählt sind.
- [ ] Angenommen der Bereich ist eingeklappt, wenn der Mitarbeiter den Vorschlag erstellt, dann gehen die vorgewählten Tickets trotzdem mit.
- [ ] Angenommen eine Eingabe im Bereich ist fehlerhaft (zum Beispiel eine ungültige Ticketnummer), wenn das Formular neu erscheint, dann ist der Bereich aufgeklappt.

## Edge Cases
- Der frühere Vorschlag hat einen bearbeiteten, nicht gesendeten Antwortentwurf: Der Entwurf bleibt im eingeklappten Vorschlag erhalten und geht nicht verloren.
- Die neue Nachricht ist unwichtig („Danke“): Der Mitarbeiter öffnet den früheren Vorschlag und nutzt ihn weiter.
- Ein Vorschlag ist im Testmodus zurückgespult (`stand`): Verhalten wie bisher.
- Frage-stellen-Seite ohne Ticket: nicht betroffen.
- Das Formular wird abgeschickt, ohne dass das Feld „Zusätzliche Informationen“ gefüllt ist: der Vorschlag entsteht ohne Zusatzinformationen, wie schon heute möglich.

## Technical Requirements (optional)
- Tests: bestehende Tests zum Hinweis (PROJ-10, PROJ-48) und zu den Hinweiszeilen im Formular anpassen.

## Open Questions
- [ ] Soll der Mitarbeiter die bisherigen Zusatzinformationen auf Wunsch wieder einfügen können (kleiner Knopf „Angaben vom letzten Vorschlag übernehmen“)? Vorerst nicht, nur auf Nachfrage.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Formular direkt öffnen statt Hinweis und Knopf | Der Hinweis führt nur zu einem Klick auf denselben Weg; der frühere Vorschlag bleibt eingeklappt erreichbar | 2026-11-05 |
| Kein automatisches Scrollen zum Formular | Die neue Nachricht steht oben und soll zuerst gelesen werden | 2026-11-05 |
| „Zusätzliche Informationen“ standardmäßig leer | Die Angaben sind im Verlauf verarbeitet; alter Text muss sonst jedes Mal gelöscht werden | 2026-11-05 |
| Hinweise „Regeln für“ und „Bestellung geladen“ entfallen | Wiederholen, was schon zu sehen ist | 2026-11-05 |
| Zusammengehörige Tickets eingeklappt, Erklärung nur im Einführungsmodus | Wird nur gelegentlich gebraucht | 2026-11-05 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|

---
<!-- Sections below are added by subsequent skills -->

## Implementation Notes (2026-11-05)
- Hinweis und Knopf „Neuen Vorschlag erstellen“ entfernt. Bei neuen Nachrichten steht der frühere Vorschlag eingeklappt unter „Früherer Vorschlag – seit dann sind neue Nachrichten eingegangen“ (`tickets/show.blade.php`), das Formular ist aufgeklappt. Kein automatisches Scrollen.
- Feld „Zusätzliche Informationen“ ist leer: Die Eingabe wird nach einem erfolgreichen Vorschlag nicht mehr an die nächste Seite weitergegeben (`AnalysisController`, `withInput($request->except('kontext'))`) und nicht mehr aus den gespeicherten Eingaben des letzten Vorschlags vorbelegt. Bei Validierungsfehlern bleibt der Text erhalten. Die übrigen Eingaben (Kundengruppe, Produkte, Variante) werden weiter vorbelegt.
- Entfernt: Zeile „Regeln für: …“ (samt `areaLine` in `scope-fields.js`; die Assets müssen neu gebaut werden, `sail npm run build`), Hinweis „Bestellung: aus EOCS geladen und berücksichtigt. Auswahl und Laden stehen oben …“. Die Zeilen für nicht geladene, ausgewählte oder fehlende Bestellungen bleiben (ohne den Zusatz).
- „Zusammengehörige Tickets“ im Formular: eingeklappt, Überschrift mit „N gefunden, M vorgewählt“, offen bei Fehlern in dem Bereich; der Erklärungsabsatz steht nur im Einführungsmodus. Die Auswahl wird auch eingeklappt mitgesendet.
- Tests: neuer `PROJ-76-FormTrimTest`; PROJ-9, PROJ-10, PROJ-46, PROJ-48, PROJ-59 angepasst. Alle Tests zu Ticket-, Frage- und Analyseseiten grün bis auf den bekannten PROJ-56-Fehler.


## Tech Design (Solution Architect)
_To be added by /architecture_

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
