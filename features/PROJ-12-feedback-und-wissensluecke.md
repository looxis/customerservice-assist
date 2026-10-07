# PROJ-12: Feedback und Wissenslücke

## Status: Architected
**Created:** 2026-10-07
**Last Updated:** 2026-10-07

## Dependencies
- Requires: PROJ-10 (Ergebnisansicht) – Antwortentwurf mit Kopieren, „Fehlendes Wissen“
- Requires: PROJ-11 (Analyse-Protokoll und Verlauf) – dauerhafte Analysen, Kennzahlen, Aufbewahrung
- Requires: PROJ-5 (Nutzerauswahl) – wer bewertet oder meldet; Admin-Rolle (wie PROJ-32)
- Wird genutzt von: PROJ-14 (Analysen-Übersicht mit Filter), PROJ-13 (Evaluation)

## Ausgangslage
Der Erfolg des MVP wird am Klick-Feedback gemessen (PRD: Anteil „unverändert nutzbar“ plus „leicht angepasst“ mindestens 70 % nach vier Wochen). Wissenslücken sollen gesammelt werden, damit die Knowledge entlang echter Fälle wächst. Die KI meldet fehlende Regeln bereits als „Fehlendes Wissen“ (PROJ-9), es fehlt aber der Weg, das zu bewerten und bei Etienne ankommen zu lassen.

## User Stories
- Als Mitarbeiter möchte ich mit einem Klick sagen, wie brauchbar der Vorschlag war, ohne lange nachzudenken, damit Feedback nebenbei entsteht.
- Als Mitarbeiter möchte ich optional kurz notieren, was falsch war oder fehlte.
- Als Mitarbeiter möchte ich eine Wissenslücke melden – vorausgefüllt, wenn die KI sie erkannt hat – und dazuschreiben, wie wir den Fall lösen, damit das Wissen nicht in meinem Kopf bleibt.
- Als Admin (Etienne) möchte ich alle offenen Wissenslücken an einer Stelle sehen und sie als Textblock in den KI-Chat zum Verfassen von Wissen übernehmen, damit ich sie schnell schließen kann.
- Als Verantwortlicher möchte ich sehen, wie viele Vorschläge unverändert oder leicht angepasst genutzt wurden, damit ich den Erfolg des MVP messen kann (Auswertung in PROJ-14; hier nur die Erfassung und eine einfache Zahl).

## Out of Scope
- Gesamtliste aller Analysen und Feedbacks mit Filtern, Auswertungen über Zeit – PROJ-14
- Automatisches Erstellen von Knowledge-Dokumenten aus Meldungen
- Benachrichtigung per E-Mail
- Automatisches Lernen aus Korrekturen (Non-Goal im PRD)
- Bewertung einzelner Ergebnisteile (z. B. nur die Befugnis)

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Feedback
- [ ] Angenommen ein Ergebnis wird angezeigt, wenn der Mitarbeiter auf „Kopieren“ klickt, dann erscheint unter dem Entwurf „Wie brauchbar war der Vorschlag?“ mit den Stufen „unverändert nutzbar“, „leicht angepasst“, „stark angepasst“, „verworfen“
- [ ] Angenommen der Entwurf wurde nicht verändert, wenn die Frage erscheint, dann ist „unverändert nutzbar“ hervorgehoben vorgeschlagen; bei kleinen Änderungen „leicht angepasst“, bei großen „stark angepasst“
- [ ] Angenommen die Frage erscheint, wenn der Mitarbeiter eine Stufe anklickt, dann ist das Feedback gespeichert mit Name und Zeitpunkt, und die Zeile zeigt „Danke – [Stufe]“ mit der Möglichkeit, es zu ändern
- [ ] Angenommen ein Ergebnis wird angezeigt, wenn der Mitarbeiter nicht kopiert (z. B. weil der Vorschlag unbrauchbar ist), dann kann er das Feedback trotzdem jederzeit am Ergebnis abgeben, auch „verworfen“
- [ ] Angenommen der Mitarbeiter gibt Feedback, wenn er „Kommentar hinzufügen“ wählt, dann kann er optional bis zu 2.000 Zeichen notieren („Was war falsch oder fehlte?“), gespeichert mit dem Feedback
- [ ] Angenommen eine Analyse hat schon Feedback, wenn ein Mitarbeiter sie erneut bewertet, dann gilt die neue Bewertung; angezeigt wird „Bewertet von [Name] am …: [Stufe]“
- [ ] Angenommen eine ältere Analyse wird angezeigt (PROJ-11), wenn der Mitarbeiter sie betrachtet, dann kann er sie ebenfalls bewerten
- [ ] Angenommen die Liste „Frühere Analysen“ wird angezeigt, wenn eine Analyse bewertet ist, dann zeigt die Zeile die Stufe
- [ ] Angenommen kein Name ist gewählt, wenn Feedback abgegeben werden soll, dann wird es nicht gespeichert und der Hinweis aus PROJ-5 erscheint

### Wissenslücke melden
- [ ] Angenommen das Ergebnis enthält „Fehlendes Wissen“, wenn es angezeigt wird, dann steht bei jeder Lücke ein Knopf „Lücke melden“, der das Meldeformular mit Thema und offener Frage vorausfüllt
- [ ] Angenommen ein Ergebnis wird angezeigt, wenn der Mitarbeiter „Wissenslücke melden“ wählt, dann öffnet sich das Meldeformular auch ohne erkannte Lücke (leer)
- [ ] Angenommen das Meldeformular ist offen, wenn es angezeigt wird, dann enthält es „Was fehlt?“ (Pflicht, bis 1.000 Zeichen), „So lösen wir das / so habe ich entschieden“ (optional, bis 4.000 Zeichen) und „Kommentar“ (optional, bis 2.000 Zeichen) sowie den Hinweis „Bitte keine Kundendaten eintragen“
- [ ] Angenommen „Was fehlt?“ ist leer, wenn gemeldet wird, dann erscheint eine Fehlermeldung und die Eingaben bleiben
- [ ] Angenommen die Meldung wird abgeschickt, wenn sie gespeichert ist, dann hängen Ticketnummer, Analyse, Kundengruppe, Produkte, Name und Zeitpunkt automatisch daran, und am Ergebnis steht „Wissenslücke gemeldet – danke“
- [ ] Angenommen dieselbe Lücke wurde zur Analyse schon gemeldet, wenn das Ergebnis angezeigt wird, dann steht bei ihr „bereits gemeldet von [Name]“ statt des Knopfs

### Liste der Wissenslücken (Admins)
- [ ] Angenommen der gewählte Name ist Admin, wenn er die Seitenleiste betrachtet, dann gibt es den Eintrag „Wissenslücken“ mit der Anzahl offener Meldungen
- [ ] Angenommen ein Nicht-Admin, wenn er die Seite aufruft, dann wird der Zugriff abgelehnt, und der Eintrag fehlt in der Seitenleiste
- [ ] Angenommen offene Meldungen gibt es, wenn die Seite angezeigt wird, dann stehen sie neuester zuerst mit Datum, Name, Ticket (Link zum Ticket), Kundengruppe, Produkten, „Was fehlt?“, „So lösen wir das“, Kommentar und ggf. „Testlauf“
- [ ] Angenommen eine Meldung wird angezeigt, wenn der Admin „Für den KI-Chat kopieren“ klickt, dann liegt ein fertiger Textblock in der Zwischenablage (Thema, Frage, Lösung, Kundengruppe, Produkte, Ticketnummer), passend zum Authoring Guide
- [ ] Angenommen eine Meldung ist bearbeitet, wenn der Admin „Erledigt“ wählt und optional die ID des neuen oder geänderten Knowledge-Dokuments angibt, dann wandert sie zu „Erledigt“ mit Name, Datum und ID
- [ ] Angenommen eine Meldung ist nicht sinnvoll (Doppelmeldung, keine Lücke), wenn der Admin „Verwerfen“ mit kurzem Grund wählt, dann wandert sie zu „Verworfen“
- [ ] Angenommen es gibt keine offenen Meldungen, wenn die Seite angezeigt wird, dann steht „Keine offenen Wissenslücken“
- [ ] Angenommen die Seite wird angezeigt, wenn der Admin oben schaut, dann sieht er eine einfache Zahl: Anteil „unverändert nutzbar“ + „leicht angepasst“ an allen bewerteten echten Analysen der letzten 28 Tage (ohne Testläufe)

### Seite „Über die App“
- [ ] Angenommen die Funktion ist nutzbar, wenn die Seite „Über die App“ angezeigt wird, dann trägt Schritt 8 „Rückmeldung geben“ kein „in Arbeit“ mehr

## Edge Cases
- **Kopieren mehrfach:** die Frage erscheint nur, solange noch kein Feedback gegeben ist; danach nur „Danke – [Stufe] · ändern“.
- **Entwurf geleert und „verworfen“:** zulässig; Vorschlag wäre „stark angepasst“, der Mitarbeiter wählt selbst.
- **Testläufe (PROJ-32):** Feedback und Meldungen sind möglich und als „Testlauf“ gekennzeichnet; sie zählen nicht in der Erfolgszahl.
- **Analyse inzwischen gelöscht oder bereinigt (PROJ-11):** Feedback-Stufe und Meldung bleiben erhalten; Kommentar und „So lösen wir das“ siehe Aufbewahrung.
- **Zwei Mitarbeiter bewerten gleichzeitig:** die zuletzt gespeicherte Bewertung gilt.
- **Netzwerk weg beim Klick auf eine Stufe:** Hinweis „Nicht gespeichert – bitte erneut klicken“.
- **Sehr viele offene Meldungen:** Liste zeigt alle, neueste zuerst (Filter in PROJ-14).

## Technical Requirements (optional)
- Feedback-Stufe und Meldestatus sind Kennzahlen ohne Kundendaten und bleiben dauerhaft (PROJ-11).
- Freitexte (Kommentar, „Was fehlt?“, „So lösen wir das“) können versehentlich Kundendaten enthalten: verschlüsselt gespeichert; Kommentare zum Feedback nach 12 Monaten gelöscht wie Analyse-Inhalte.
- Keine Inhalte im Log. Admin-Prüfung serverseitig.

## Open Questions
- [ ] Ab wann gilt eine Änderung als „klein“ bzw. „groß“ für den Vorschlag der Stufe? Startwert: bis 20 % geänderte Zeichen „leicht“, darüber „stark“; nach ersten Wochen prüfen.
- [ ] Sollen Wissenslücken-Meldungen auch nach 12 Monaten bleiben (sie beschreiben Regeln, keine Fälle), oder wie Analysen bereinigt werden? Vorschlag: bleiben, solange offen; erledigte nach 12 Monaten leeren.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Vier Stufen nach Projektbeschreibung: unverändert nutzbar, leicht angepasst, stark angepasst, verworfen | Erfolgskennzahl im PRD | 2026-10-07 |
| Frage erscheint beim Kopieren, mit vorgeschlagener Stufe aus der Änderung am Entwurf; jederzeit auch von Hand | Feedback entsteht nebenbei, viele Rückmeldungen; „verworfen“ auch ohne Kopieren | 2026-10-07 |
| Wissenslücke vorausgefüllt aus „Fehlendes Wissen“, zusätzlich von Hand; Feld „So lösen wir das“ | Regeln gehören nicht ins Kontextfeld, sondern über die Meldung in die Knowledge | 2026-10-07 |
| Kleine Liste „Wissenslücken“ für Admins schon in PROJ-12, mit Kopieren für den KI-Chat und Erledigt/Verwerfen | Meldungen müssen ankommen; Gesamtliste mit Filtern bleibt PROJ-14 | 2026-10-07 |
| Eine Bewertung je Analyse, zuletzt gespeicherte gilt (von mir entschieden, bitte prüfen) | Einfach; Erfolgszahl je Analyse eindeutig | 2026-10-07 |
| Testläufe zählen nicht in der Erfolgszahl (von mir entschieden, bitte prüfen) | Tests an alten Tickets würden die Kennzahl verfälschen | 2026-10-07 |
| Erfolgszahl auf der Wissenslücken-Seite: letzte 28 Tage (von mir entschieden, bitte prüfen) | PRD misst „nach vier Wochen Einsatz“; mehr Auswertung in PROJ-14 | 2026-10-07 |
| Hinweis „Bitte keine Kundendaten eintragen“ an Meldungen (von mir entschieden, bitte prüfen) | Meldungen beschreiben Regeln und werden in den KI-Chat kopiert | 2026-10-07 |

### Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Feedback als Felder an der Analyse (eine Bewertung je Analyse), Kommentar verschlüsselt und mit den Analyse-Inhalten bereinigt | Passt zur Entscheidung „eine Bewertung, zuletzt gespeichert gilt“; Stufe ist Kennzahl ohne Kundendaten | 2026-10-07 |
| Vorgeschlagene Stufe wird im Browser aus Original und aktuellem Entwurf berechnet und mit der gewählten Stufe gespeichert | Sofort sichtbar ohne Serveranfrage; gespeicherter Vorschlag erlaubt später, die Schwelle (offene Frage) an echten Daten zu prüfen | 2026-10-07 |
| Eigene Tabelle für Wissenslücken mit Status offen/erledigt/verworfen, Freitexte verschlüsselt | Meldungen haben einen eigenen Lebenszyklus (Bearbeitung durch Admin), mehrere je Analyse möglich | 2026-10-07 |
| Feedback per Hintergrundanfrage (wie Entwurf-Speichern), Meldung und Admin-Aktionen als normale Formulare | Feedback ist ein Klick ohne Seitenwechsel; Meldungen und Admin-Aktionen brauchen Rückmeldung und Fehlerbehandlung wie bisher | 2026-10-07 |
| Seite „Wissenslücken“ nur für Admins, Prüfung serverseitig wie Testmodus und Löschen (PROJ-32/11) | Gleiche Mechanik bis PROJ-15 | 2026-10-07 |
| Erfolgszahl wird bei jedem Aufruf aus den Analysen berechnet (letzte 28 Tage, ohne Testläufe) | Keine zusätzliche Datenhaltung; wenige Analysen je Tag | 2026-10-07 |
| Bereinigung erweitert: Feedback-Kommentare mit den Analyse-Inhalten nach 12 Monaten; erledigte/verworfene Meldungen 12 Monate nach Abschluss leeren, offene bleiben | Datensparsamkeit, offene Lücken gehen nicht verloren | 2026-10-07 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

### Überblick
PROJ-12 ergänzt das Ergebnis (PROJ-10) um Feedback und Meldungen und bringt eine neue Seite „Wissenslücken“ für Admins. Gespeichert wird in der Datenbank aus PROJ-11: das Feedback direkt an der Analyse, Wissenslücken in einer eigenen Tabelle.

### A) Bausteine
```
Ergebnis (PROJ-10)
+-- Antwortentwurf
|   +-- nach „Kopieren“: „Wie brauchbar war der Vorschlag?“
|       +-- [unverändert nutzbar] [leicht angepasst] [stark angepasst] [verworfen]  (Vorschlag hervorgehoben)
|       +-- „Kommentar hinzufügen“ (optional)
|       +-- danach: „Danke – leicht angepasst · ändern“ / „Bewertet von … am …“
+-- Kasten „Was ist zu tun?“
|   +-- Fehlendes Wissen: je Lücke [Lücke melden] bzw. „bereits gemeldet von …“
+-- [Wissenslücke melden] (immer)
    +-- Formular: Was fehlt? · So lösen wir das · Kommentar · Hinweis „Bitte keine Kundendaten“

Frühere Analysen (PROJ-11): Stufe je Zeile

Seitenleiste: „Wissenslücken (N)“ – nur Admins

Seite „Wissenslücken“ (nur Admins)
+-- Erfolgszahl: Anteil unverändert + leicht angepasst, letzte 28 Tage, ohne Testläufe
+-- Reiter: Offen · Erledigt · Verworfen
+-- je Meldung: Datum · Name · Ticket-Link · Kundengruppe · Produkte · Testlauf
|   +-- Was fehlt? · So lösen wir das · Kommentar
|   +-- [Für den KI-Chat kopieren] [Erledigt (Knowledge-ID)] [Verwerfen (Grund)]
+-- leerer Zustand: „Keine offenen Wissenslücken“

Seite „Über die App“: Schritt 8 ohne „in Arbeit“
```

### B) Daten
**An der Analyse (PROJ-11) zusätzlich:**
- Feedback-Stufe, vorgeschlagene Stufe, wer und wann (Kennzahlen, dauerhaft)
- Feedback-Kommentar (verschlüsselt, wird mit den Analyse-Inhalten nach 12 Monaten geleert)

**Wissenslücke (neue Tabelle):**
- Verweis auf Analyse und Ticketnummer, Name, Zeitpunkt, Kundengruppe, Produkte, Testlauf ja/nein
- Thema der von der KI erkannten Lücke (falls vorausgefüllt), um „bereits gemeldet“ zu erkennen
- Was fehlt?, So lösen wir das, Kommentar (verschlüsselt)
- Status offen/erledigt/verworfen, wer und wann bearbeitet, Knowledge-ID bzw. Grund
- Bereinigung: erledigte und verworfene Meldungen 12 Monate nach Abschluss geleert; offene bleiben

### C) Technische Entscheidungen (für Nicht-Entwickler)
- **Ein Klick fürs Feedback:** Die Stufe wird im Hintergrund gespeichert, ohne die Seite neu zu laden – wie das Speichern des Entwurfs. Die vorgeschlagene Stufe rechnet der Browser aus dem Unterschied zwischen KI-Entwurf und aktuellem Text (gleich → unverändert, bis 20 % geändert → leicht, darüber → stark). Gespeichert werden Vorschlag und Wahl, damit sich die Schwelle später an echten Daten prüfen lässt.
- **Meldungen als Formular:** Abschicken lädt die Seite neu und zeigt „Wissenslücke gemeldet – danke“; Fehler (z. B. leeres „Was fehlt?“) erscheinen am Feld, Eingaben bleiben.
- **Admin-Seite:** gleiche Admin-Prüfung wie Testmodus und Löschen; „Für den KI-Chat kopieren“ nutzt das bewährte Kopier-Verhalten (Zwischenablage, sonst markieren). Der Textblock folgt dem Aufbau des Authoring Guides.
- **Erfolgszahl:** wird beim Aufruf aus den Analysen gezählt, keine eigene Speicherung.
- **Bereinigung:** der nächtliche Befehl aus PROJ-11 übernimmt auch Feedback-Kommentare und abgeschlossene Meldungen.

### D) Abhängigkeiten
Keine neuen Pakete.

### E) Hinweise für /frontend und /backend
- Feedback und Meldungen nur mit gewähltem Namen; Admin-Aktionen nur für Admins (403).
- Feedback zu Analysen ohne Inhalte (bereinigt/gelöscht) ist nicht möglich; vorhandene Stufen bleiben.
- „Bereits gemeldet“ je Analyse und Thema der KI-Lücke.
- Seitenleiste: Anzahl offener Meldungen nur für Admins berechnen.
- Tests: Vorschlagslogik (gleich/leicht/stark), Speichern/Ändern, Kommentar, Meldung mit Pflichtfeld, vorausgefüllte Lücke, „bereits gemeldet“, Admin-Liste mit Reitern, Erledigt/Verwerfen, Kopier-Textblock, Erfolgszahl ohne Testläufe, Bereinigung, Zugriff für Nicht-Admins.

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
