# PROJ-45: Produkt ohne hinterlegtes Wissen automatisch melden

## Status: Approved
**Created:** 2026-10-14
**Last Updated:** 2026-10-14

## Dependencies
- Requires: PROJ-42 (Produkt ohne hinterlegtes Wissen im Formular), PROJ-12 (Wissenslücke melden), PROJ-14/„Wissenslücken“-Seite für Admins, PROJ-40 (Fragen ohne Ticket).
- Verwandt: PROJ-38 (Produktinformationen aus ProductForge; liefert der Katalog Kundenservice-Wissen zum Produkt, entfällt die automatische Meldung, sonst bleibt sie mit Vermerk „Produktdaten vorhanden“).

## Ausgangslage
Nennt ein Mitarbeiter im Formular ein Produkt, zu dem es in der Wissensbasis nichts gibt (z. B. „LED-Leuchtsockel“), sagt der Vorschlag dazu bewusst nichts zu. Die Lücke wurde bisher nur gemeldet, wenn die Mitarbeiterin selbst auf „Wissenslücke melden“ klickte – das tun Aushilfen selten. Der Admin sah nicht, wie oft ein Produkt ohne Wissen nachgefragt wird, und das, was der Mitarbeiter über das Produkt wusste (Freitext „Zusätzliche Informationen“), ging verloren.

## User Stories
- Als Mitarbeiterin möchte ich nichts weiter tun müssen, wenn zu einem Produkt Wissen fehlt, damit die Lücke trotzdem beim Wissensautor ankommt.
- Als Mitarbeiterin möchte ich Informationen, die ich zum Produkt herausgefunden habe, im Formular eintragen und wissen, dass sie auch dem Wissensaufbau helfen.
- Als Admin möchte ich sehen, zu welchen Produkten am häufigsten Wissen fehlt, damit ich das Wissen in der sinnvollen Reihenfolge ergänze.
- Als Wissensautor möchte ich zu einer Lücke die Angaben des Mitarbeiters sehen, damit ich nicht bei null anfange.

## Out of Scope
- Abruf des Produktwissens aus dem Produktkatalog (umgesetzt in PROJ-38).
- Automatisches Anlegen oder Ändern eines Produktdokuments in `knowledge/`.
- Automatische Zusammenführung abweichender Schreibweisen desselben Produkts („LED Leuchtsockel“ und „LED-Leuchtsockel“): derselbe Name in anderer Groß- und Kleinschreibung zählt zusammen, Varianten bleiben getrennte Einträge.
- Meldung für Dokumente, die nur wegen fehlender Produktwahl außerhalb des Geltungsbereichs liegen (PROJ-44).

## Acceptance Criteria
- [x] Angenommen ein Vorschlag wird für ein Produkt ohne hinterlegtes Wissen erstellt, wenn er fertig ist, dann entsteht ohne Klick eine offene Wissenslücke „Produktwissen zu „X“ mit Mitarbeiter, Kundengruppe, Ticket oder Frage und dem Hinweis „automatisch erfasst“.
- [x] Angenommen im Feld „Zusätzliche Informationen“ (bei einer Frage: im Fragetext) steht etwas, wenn die Lücke entsteht, dann wird der Text (bis 2000 Zeichen) verschlüsselt an der Lücke gespeichert und dem Admin als „Angaben des Mitarbeiters zum Fall“ gezeigt.
- [x] Angenommen für dasselbe Ticket oder dieselbe Frage und dasselbe Produkt gibt es schon eine offene Lücke, wenn der Vorschlag erneut erstellt wird, dann entsteht keine zweite Zeile; die Angaben werden aktualisiert.
- [x] Angenommen der Vorschlag ist angezeigt, wenn die Lücke automatisch erfasst wurde, dann steht im gelben Hinweis, dass sie automatisch gemeldet wurde und nichts weiter zu tun ist; der Knopf „Wissenslücke melden“ entfällt.
- [x] Angenommen ein Admin öffnet die Seite „Wissenslücken“ mit dem Status „Offen“, wenn es automatisch erfasste Lücken gibt, dann steht oben „Produkte ohne Wissen, nach Häufigkeit“ mit Produktname, Zahl der Anfragen (Tickets und Fragen, ohne Testläufe), letztem Datum und den Angaben der Mitarbeiter.
- [x] Angenommen eine Lücke wird für den KI-Chat kopiert, wenn Angaben des Mitarbeiters vorhanden sind, dann stehen sie im Text („bitte als Wissen prüfen“).
- [x] Angenommen kein Produkt ohne Wissen genannt (Produkt aus der Wissensbasis oder „Kein Produktbezug“), wenn ein Vorschlag erstellt wird, dann entsteht keine automatische Lücke.
- [x] Angenommen die Lücke gehört zu einer Frage ohne Ticket, wenn der Admin sie öffnet, dann führt der Link zur Frage, nicht zu einem Ticket.

## Edge Cases
- Testlauf (zurückgespultes Ticket): die Lücke ist als Testlauf gekennzeichnet und zählt nicht in der Häufigkeitsliste.
- Die Lücke wurde schon erledigt oder verworfen: für dasselbe Ticket entsteht keine neue; für ein anderes Ticket schon.
- Inhalte werden wie bei allen Lücken nach 12 Monaten gelöscht; Häufigkeiten aus der Liste verschwinden dann mit dem Produktnamen (der Name steht im verschlüsselten Inhalt).
- Zusatzangaben können Kundendaten enthalten: gespeichert wird nur verschlüsselt; Kontaktdaten werden hier nicht entfernt (wie beim Freitext der Lücke), die Hinweise zum Eintragen gelten unverändert.
- Der Mitarbeiter hat „Wissenslücke melden“ schon vor dem Update geklickt: der Hinweis zeigt „bereits gemeldet“, es entsteht keine zweite Meldung (gleiches Thema).

## Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Automatisch erfassen statt Klick | Aushilfen melden Lücken selten; die Information soll nicht von der Mitarbeiterin abhängen | 2026-10-14 |
| Zusatzangaben und Fragetext mitnehmen | Das Wissen, das ein Kollege schon herausgefunden hat, ist der eigentliche Hebel für den Wissensaufbau | 2026-10-14 |
| Eine Lücke je Ticket/Frage und Produkt | Wiederholtes Erstellen eines Vorschlags darf die Häufigkeit nicht aufblähen | 2026-10-14 |
| Häufigkeit aus offenen, automatischen Lücken ohne Testläufe | Zeigt, was noch fehlt; erledigtes Wissen soll nicht mehr zählen | 2026-10-14 |
| Keine Schemaänderung | Tabelle `knowledge_gaps` genügt (Thema-Hash, verschlüsselter Inhalt) | 2026-10-14 |

## Technical Decisions
- `KnowledgeGapLog::reportUnknownProduct()` wird vom `CaseAnalyzer` nach dem Speichern des Ergebnisses aufgerufen (Tickets und Fragen, auch bei Fortsetzungen).
- Thema und Hash sind dieselben wie beim Knopf „Wissenslücke melden“ („Produktwissen: <Name>“), damit es nie eine Doppelmeldung gibt.
- `KnowledgeGapLog::unknownProducts()` gruppiert die offenen automatischen Lücken nach Thema-Hash.

## Implementation Notes
- Geändert: `app/Analysis/KnowledgeGapLog.php`, `CaseAnalyzer.php`, `KnowledgeGapController.php`, Blade `analysis/result.blade.php` und `knowledge-gaps/index.blade.php`.
- Test: `tests/Feature/PROJ-45-UnknownProductGapTest.php` (7); der PROJ-42-Test zum Hinweis im Ergebnis wurde angepasst.
- Hinweise im Formular (PROJ-42): Meldung „hierfür ist noch kein Produktwissen hinterlegt“ und der Hinweis, Wissen unter „Zusätzliche Informationen“ einzutragen.

## QA Test Results

**Tested:** 2026-10-14 (zusammen mit PROJ-43)
**App URL:** http://localhost:8081
**Tester:** QA Engineer (AI)
**Methode:** komplette Pest-Suite (1363 Tests, alle bestanden), Abgleich jedes Kriteriums mit `PROJ-45-UnknownProductGapTest.php` (11 Tests, 4 davon QA-Proben). **Nicht getestet:** Browser und Breiten 375/768/1440 px (Admin-Seite und gelber Hinweis nur per Render-Test), Live-Lauf mit dem echten Modell.

### Acceptance Criteria Status
- [x] AC-1 Automatische offene Lücke ohne Klick, mit Mitarbeiter, Gruppe, Ticket/Frage und Kennzeichnung (Test)
- [x] AC-2 Freitext (bzw. Fragetext) verschlüsselt an der Lücke, auf der Admin-Seite sichtbar (Test, Rohwert enthält den Text nicht)
- [x] AC-3 Wiederholter Vorschlag legt keine zweite Zeile an, aktualisiert die Angaben (Test)
- [x] AC-4 Hinweis „automatisch gemeldet“, kein Knopf (Test)
- [x] AC-5 Admin-Seite: „Produkte ohne Wissen, nach Häufigkeit“ mit Anfragen, Datum, Angaben (Test)
- [x] AC-6 Chat-Text enthält die Angaben des Mitarbeiters (Test)
- [x] AC-7 Ohne Produkt ohne Wissen keine automatische Lücke (Test)
- [x] AC-8 Frage ohne Ticket: Lücke mit F-Kennung, Link zur Frage (Test)

### Edge Cases Status
- [x] Testlauf zählt nicht in der Häufigkeitsliste (Test)
- [x] Erledigte oder bereits gelöschte Lücke zählt nicht und bricht die Liste nicht (Test)
- [x] Offene Lücken in der Seitenleiste zählen die automatischen mit (Test)
- [x] Gleicher Name in anderer Groß-/Kleinschreibung zählt zusammen (gleicher Hash)

### Security Audit Results
- [x] Autorisierung: Admin-Seite für Nicht-Admins 403 (Test)
- [x] XSS: Produktname und Angaben mit Markup werden maskiert (Test)
- [x] Datenschutz: Angaben nur verschlüsselt gespeichert, Löschung nach 12 Monaten wie bei allen Lücken
- [x] Keine neuen Routen oder Formulare, CSRF unverändert
- Hinweis: Die Zusatzangaben werden nicht von Kontaktdaten bereinigt (wie beim Freitext einer manuell gemeldeten Lücke); der Hinweis am Eingabefeld bleibt die einzige Schutzmaßnahme.

### Bugs Found
Keine.

#### Hinweise (keine Fehler)
- Abweichende Schreibweisen desselben Produkts zählen getrennt (in der Spec als „Out of Scope“ geführt).
- Der Aufruf, der die Lücke anlegt, läuft nach dem Speichern des Vorschlags; ein Fehler dort würde die Anzeige des Vorschlags unterbrechen. Bisher nicht beobachtet.

### Summary
- **Acceptance Criteria:** 8/8 bestanden
- **Bugs Found:** 0
- **Security:** Pass
- **Production Ready:** YES, mit Browser-Durchgang der Admin-Seite
