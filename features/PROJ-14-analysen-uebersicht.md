# PROJ-14: Analysen-Übersicht

## Status: In Progress
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
| Decision | Rationale | Date |
|----------|-----------|------|
| Keine neue Tabelle, nur Lesen der vorhandenen Kennzahlspalten (plus Indizes für die Filter) | Alles Nötige liegt seit PROJ-11, PROJ-12 und PROJ-35 vor; keine Kundeninhalte entschlüsseln | 2026-10-12 |
| Filter, Zählung und Seitenwechsel in der Datenbank | Analysen sind zahlreicher als Tickets in PROJ-35 und werden länger als 12 Monate als Kennzahlen aufbewahrt | 2026-10-12 |
| Kennzahlen nach derselben Regel wie die Erfolgszahl der Wissenslücken-Seite, an einer Stelle gepflegt | Beide Zahlen dürfen nie auseinanderlaufen (Spec-Kriterium) | 2026-10-12 |
| Stichtag für den Kopier-Stand: davor „–“ (unbekannt), danach echte Werte | Vor PROJ-35 wurde nicht erfasst; „nicht kopiert“ wäre sonst falsch | 2026-10-12 |
| Produkt-, Modell- und Prompt-Auswahl aus den gespeicherten Analysen | Zeigt auch frühere oder entfernte Werte, solange es Analysen dazu gibt | 2026-10-12 |
| Filterformular mit Auswahl in der Adresse, Server-Aufbau, kein Nachladen | Weitergabe und Zurück-Knopf funktionieren; wie die übrigen Admin-Seiten | 2026-10-12 |

---

## Tech Design (Solution Architect)

### Backend nötig?
Ja, aber **keine neuen Daten**. Alles, was die Liste braucht, liegt schon in der Datenbank: Die Tabelle der Analysen (PROJ-11) hat Kennzahlen in eigenen Spalten, PROJ-12 hat Feedback-Stufe und Wissenslücken ergänzt, PROJ-35 den Kopier-Stand. Es entsteht eine neue Seite mit Abfragen, keine neue Tabelle.

### A) Aufbau der Seite
```
Analysen (nur Admins)
+-- Filterleiste
|   +-- Zeitraum (7 / 28 / 90 Tage / 12 Monate / Von–Bis)
|   +-- Kundengruppe, Produkt, Kategorie
|   +-- Feedback-Stufe, Kopier-Stand
|   +-- Mitarbeiter, Modell, Prompt-Version
|   +-- Schalter "Testläufe" und "Fehlgeschlagene"
|   +-- "Zurücksetzen"
+-- Kennzahlen zur gefilterten Menge
|   +-- Anzahl Analysen
|   +-- Anteil unverändert/leicht angepasst · Anteil verworfen · Anteil ohne Feedback
|   +-- Anteil 1:1 kopiert · gemeldete Wissenslücken
+-- Liste (eine Zeile je Analyse, 50 pro Seite)
|   +-- Zeitpunkt · Mitarbeiter · Ticket (Link zur Analyse)
|   +-- Kundengruppe · Produkte · Kategorie
|   +-- Modell · Prompt-Version
|   +-- Feedback-Stufe · Kopier-Stand
|   +-- Wissenslücken (Anzahl, Link)
|   +-- Kennzeichen "Testlauf" / "Fehlgeschlagen" (mit Fehlergrund)
+-- Seitenwechsel (Filter bleiben erhalten)
+-- Leerer Zustand "Keine Analysen für diese Auswahl"
```
Die Seite wird komplett auf dem Server aufgebaut. Die Filter sind ein normales Formular, das die Auswahl in die Adresse schreibt; dadurch lassen sich Auswahlen weitergeben und der Zurück-Knopf des Browsers funktioniert. Kein Nachladen im Hintergrund nötig.

Wiederverwendet werden die vorhandenen Bausteine: Seitenrahmen, Karte, Abzeichen, Seitenleisten-Eintrag und die Muster der Wissenslücken- und Ticket-Übersicht (Filter-Chips, Tabelle mit horizontalem Scrollen, Seitenwechsel). Neue Komponenten sind nur nötig, wenn die Filterleiste mehrfach gebraucht wird (z. B. ein Auswahlfeld mit Beschriftung, falls es das nicht schon gibt – `select` und `field` existieren).

### B) Welche Daten werden gelesen
Je Analyse (alles aus den Kennzahlspalten, **kein Kundeninhalt wird entschlüsselt**):
- Zeitpunkt, Mitarbeiter, Ticketnummer, Kennung der Analyse (für den Link)
- Kundengruppe, Produkte, Kategorie, Variante
- Modell, Prompt-Version, Status (erfolgreich/fehlgeschlagen) und Fehlergrund
- Testlauf-Kennzeichen
- Feedback-Stufe
- Kopier-Stand (kopiert, ob geändert; bearbeitet) aus PROJ-35
- Anzahl gemeldeter Wissenslücken zur Analyse

Gespeichert wird **nichts Neues**. Zusätzlich braucht die Datenbank passende Suchhilfen (Indizes) für Zeitraum, Kundengruppe, Kategorie, Feedback, Modell und Prompt-Version, damit die Seite auch bei vielen tausend Analysen schnell bleibt.

### C) Technische Entscheidungen (mit Begründung)
- **Normale Web-Seite mit Server-Aufbau**: Die Seite ist eine Ansicht für Admins, kein Datendienst; wie Ticket-Übersicht und Wissenslücken.
- **Filter und Zählung in der Datenbank statt im Speicher**: PROJ-35 filtert im Speicher, weil es dort nur um wenige Tickets geht. Analysen sind mehr (mehrere pro Ticket, jahrelang als Kennzahlen aufbewahrt) und werden gezielt gefiltert; deshalb wird gefiltert, gezählt und geblättert, ohne alle Zeilen zu laden.
- **Kennzahlen mit derselben Regel wie die Erfolgszahl**: Gleiche Definition („bewertete echte Analysen, ohne Testläufe“), damit beide Seiten nie auseinanderlaufen. Die Regel wird an einer Stelle gepflegt und von beiden Seiten genutzt.
- **Kopier-Stand „unbekannt“ für ältere Analysen**: Vor Einführung der Erfassung (PROJ-35, 2026-10-12) ist nicht bekannt, ob kopiert wurde. Eine feste Stichtag-Einstellung legt fest, bis wann „–“ gilt; danach heißt „nicht kopiert“ wirklich nicht kopiert. Der Filter „nicht kopiert“ trifft nur Analysen nach dem Stichtag.
- **Produkt-Filter über die gespeicherte Produktliste**: Die Auswahlwerte kommen aus den vorhandenen Analysen (nicht aus dem Knowledge-Katalog), damit auch Produkte erscheinen, die später umbenannt oder entfernt wurden.
- **Wissenslücken nur als Anzahl und Link**: Die Meldungen bleiben in ihrer eigenen Verwaltung (PROJ-12); die Liste zählt sie je Analyse.
- **Ungültige Filterwerte werden ignoriert**: Eine von Hand veränderte Adresse führt nie zu einem Fehler.
- **Zugriff wie bei den anderen Admin-Seiten**: Prüfung auf dem Server, Eintrag in der Seitenleiste nur für Admins.
- **Kein Export, keine Diagramme**: laut Spec außerhalb.

### D) Abhängigkeiten
Keine neuen Pakete.

### E) Zu beachten für Umsetzung und Tests
- Die Ticket-Übersicht (PROJ-35) und diese Seite zeigen teilweise dieselben Begriffe („kopiert unverändert“ usw.). Die Bezeichnungen sollen aus einer gemeinsamen Quelle kommen, damit beide gleich heißen.
- Anzeige „Testlauf“ und „Fehlgeschlagen“ nur bei zugeschaltetem Schalter; Kennzahlen folgen demselben Schalter.
- Tests: Zugriff (403), jeder Filter einzeln und kombiniert, Standardansicht, Seitenwechsel mit Filtern, Kennzahlen gleich Erfolgszahl, Kopier-Stand „–“ vor dem Stichtag, Maskierung von Texten, Zeile ohne Inhalt nach Bereinigung.

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_

## Implementation Notes – Backend (2026-10-12)
- **Route und Controller:** `GET /analysen` (`analysis-overview.index`), `AnalysisOverviewController` mit `AnalysisOverviewRequest` (Admin-Prüfung in `authorize()`, 403 sonst; nur die Form der Eingaben). Die Ansicht `analysis-overview.index` (Filterleiste, Kennzahlen, Tabelle) und der Seitenleisten-Eintrag folgen mit `/frontend`.
- **Filter:** `AnalysisOverview::filter()` bereinigt die Adresse zu einem `AnalysisFilter`; Werte, die nicht zu den gespeicherten Analysen passen, werden verworfen. Ein ungültiger Zeitraum (Von nach Bis, Von in der Zukunft) fällt auf die 28 Tage zurück und liefert den Hinweis `problem`. Adressparameter: `tage`, `von`, `bis`, `kundengruppe`, `produkt`, `kategorie`, `feedback` (Stufen oder `none`), `kopiert` (`copied-unchanged`, `copied-changed`, `edited`, `not-copied`), `mitarbeiter`, `modell`, `prompt`, `testlaeufe=1`, `fehlgeschlagen=1`.
- **Abfragen:** Filtern, Zählen und Blättern in der Datenbank (`page()`, `figures()`), nur Kennzahlspalten, `content` wird nicht geladen. Wissenslücken je Analyse über `withCount('gaps')`.
- **Gemeinsame Regel:** `Analysis::rated()` (Scope) ist die Regel der Erfolgszahl; `AnalysisStore::successRate()` nutzt sie jetzt ebenfalls (Test: Anteil „unverändert/leicht“ stimmt mit der Erfolgszahl überein). Weitere Scopes: `testRun()`, `realRun()` (Testlauf = `test_until` gesetzt oder `.stand-` im Scope, damit auch fehlgeschlagene Testläufe erkannt werden).
- **Kopier-Stand:** `copyState()` liefert `null` (anzuzeigen als „–“) für fehlgeschlagene Analysen und für Analysen vor `analysis.reply_tracking_since` (2026-10-12), sofern nicht kopiert oder bearbeitet wurde.
- **Link:** `ticketParameters()` baut Ticketnummer, Analyse und bei Testläufen den Stand.
- **Indizes:** `created_at` und `status, created_at` an `analyses`.
- **Konfiguration:** `analysis.overview_periods`, `analysis.overview_default_days`, `analysis.reply_tracking_since`.
- **Tests:** `tests/Feature/PROJ-14-AnalysisOverviewTest.php` (17 Tests, Abfrageebene und 403).
- **Abweichung:** Die Kennzahl „ohne Feedback“ bezieht sich auf abgeschlossene Analysen der Menge (fehlgeschlagene haben nie Feedback).

## Implementation Notes – Frontend (2026-10-12)
- **Ansicht:** `resources/views/analysis-overview/index.blade.php` – Filterformular (GET, Auswahl in der Adresse) mit den vorhandenen Komponenten `x-select`, `x-input`, `x-button`, `x-card`, `x-badge`, `x-alert`; darunter drei Kennzahlen-Karten (Analysen mit Feedback-Anzahl und Ziel 70 %, Anteil unverändert/leicht mit verworfen und ohne Feedback, 1:1 kopiert mit Wissenslücken) und die Tabelle (horizontal scrollbar) mit Zeitpunkt/Mitarbeiter, Ticket (Link zur Analyse), Fall (Kundengruppe, Produkt, Kategorie), Modell/Prompt-Version, Feedback (inkl. „Fehlgeschlagen“ mit Grund und „Testlauf“), Entwurf (Kopier-Stand, „–“ mit Hinweis „Nicht erfasst“) und Wissenslücken (Link zur Wissenslücken-Seite).
- **Seitenleiste:** Eintrag „Analysen“ unter „Ticket-Übersicht“, nur für Admins.
- **Zustände:** leerer Zustand mit Hinweis zum Lockern der Filter; Hinweis bei ungültigem Zeitraum; „Zurücksetzen“ nur bei abweichender Auswahl; Produktnamen aus dem Knowledge-Katalog, sonst der gespeicherte Schlüssel.
- **Tests:** 9 Seitentests ergänzt (insgesamt 26 in `PROJ-14-AnalysisOverviewTest.php`); Gesamtsuite 931 Tests grün; Assets gebaut.
- **Nicht im Browser geprüft:** Aussehen bei 375/768/1440 px; folgt in `/qa`.
