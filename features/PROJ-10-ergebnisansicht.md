# PROJ-10: Ergebnisansicht

## Status: In Progress
**Created:** 2026-10-06
**Last Updated:** 2026-10-06

## Dependencies
- Requires: PROJ-9 (Fallanalyse per LLM) – Ergebnis in fester Struktur, Metadaten, Platzhalter, vorübergehende Speicherung
- Requires: PROJ-24 (Knowledge-Übersicht) – Dokumentseiten, auf die Quellen verlinken
- Berührt: PROJ-32 (Testmodus) – Testläufe bleiben gekennzeichnet und getrennt
- Wird genutzt von: PROJ-11 (Verlauf, dauerhafte Speicherung), PROJ-12 (Feedback, Wissenslücke melden), PROJ-30 (Arbeitsabläufe unter dem Ergebnis)

## Ausgangslage
PROJ-9 zeigt alle Ergebnisteile gleichrangig untereinander, unter einem weiterhin voll aufgeklappten Formular. Für die Arbeit am Ticket braucht vor allem eine Aushilfe auf einen Blick: Was ist zu tun, darf ich das, was fehlt – und dann die Antwort zum Anpassen und Kopieren. Begründung und Quellen sollen nachlesbar sein, ohne zu stören.

## User Stories
- Als Aushilfe möchte ich oben auf einen Blick sehen, was zu tun ist, ob ich das selbst entscheiden darf und was noch fehlt, damit ich nicht das ganze Ergebnis lesen muss.
- Als Mitarbeiter möchte ich den Antwortentwurf direkt in der App anpassen und mit einem Klick kopieren, damit ich ihn in Zammad nur noch einfügen muss.
- Als Mitarbeiter möchte ich, dass meine Änderungen am Entwurf erhalten bleiben, auch wenn ich die Seite neu lade oder eine Kollegin das Ticket öffnet.
- Als Mitarbeiter möchte ich gewarnt werden, wenn im Entwurf noch Stellen offen sind, damit keine Antwort mit „[TRACKINGNUMMER]“ beim Kunden ankommt.
- Als Mitarbeiter möchte ich die Knowledge-Dokumente, auf die sich der Vorschlag stützt, direkt am Ticket aufklappen und lesen, damit ich die Begründung prüfen kann.
- Als Mitarbeiter möchte ich deutlich sehen, wenn der Vorschlag auf Entwurfs-Wissen beruht, damit ich besonders kritisch hinsehe.
- Als Kollegin möchte ich beim Öffnen eines Tickets die letzte Analyse sehen und erfahren, ob seither neue Nachrichten kamen, damit ich nicht unnötig neu analysiere.

## Out of Scope
- Liste früherer Analysen je Ticket, dauerhafte Speicherung, erneutes Öffnen älterer Analysen – PROJ-11
- Feedback (vier Stufen) und „Wissenslücke melden“ per Klick – PROJ-12
- Anzeige der Arbeitsabläufe (Procedures) zum Fall – PROJ-30
- Grußformel oder Signatur beim Kopieren, wählbare Signaturen – PROJ-27 (Signaturen im Team noch nicht einheitlich)
- Antwort direkt nach Zammad übergeben – PROJ-19
- Übersetzung des Entwurfs oder der Kundennachricht – PROJ-28
- Mobiles Menü – PROJ-26 (die Ansicht selbst ist responsiv)

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Aufbau und Anordnung
- [ ] Angenommen eine Analyse liegt vor, wenn die Ticketseite angezeigt wird, dann steht das Ergebnis direkt unter dem Ticketkopf, vor dem Verlauf
- [ ] Angenommen eine Analyse liegt vor, wenn die Ticketseite angezeigt wird, dann ist das Analyse-Formular auf eine Zeile eingeklappt: „Analysiert mit: [Kundengruppe] · [Produkte] · [Variante] – Eingaben ändern und neu analysieren“; ein Klick klappt das Formular mit den damaligen Eingaben auf
- [ ] Angenommen eine Analyse liegt vor, wenn das Ergebnis angezeigt wird, dann steht oben ein Kasten „Was ist zu tun?“ mit Einstufung (bei Reklamationen), Confidence, empfohlener Maßnahme mit Vorgängen, Befugnis in klarer Sprache („Du darfst das selbst entscheiden“ bzw. „Freigabe nötig durch …“), fehlenden Informationen mit Rückfrage und – falls vorhanden – fehlendem Wissen
- [ ] Angenommen eine Analyse liegt vor, wenn das Ergebnis angezeigt wird, dann folgt direkt unter „Was ist zu tun?“ der Antwortentwurf
- [ ] Angenommen eine Analyse liegt vor, wenn das Ergebnis angezeigt wird, dann stehen Begründung, Quellen, Kurzfassung (was ist passiert, was will der Kunde), Kategorie und Fallmuster, Confidence-Gründe, interne To-dos und Prüfhinweise darunter als aufklappbare Abschnitte, anfangs eingeklappt; die Überschrift jedes Abschnitts zeigt, ob er Inhalt hat (z. B. „Quellen (3)“, „Interne To-dos (2)“)
- [ ] Angenommen der Bildschirm ist schmal (Handy), wenn das Ergebnis angezeigt wird, dann stehen alle Teile untereinander ohne waagrechtes Scrollen

### Antwortentwurf
- [ ] Angenommen eine Analyse liegt vor, wenn der Antwortentwurf angezeigt wird, dann steht er in einem bearbeitbaren Textfeld mit der Sprache als Überschrift (z. B. „Antwortentwurf (Italienisch)“), bekannte Platzhalter sind bereits durch die Werte ersetzt
- [ ] Angenommen die App hat Werte eingesetzt (z. B. Lieferadresse), wenn der Entwurf angezeigt wird, dann nennt ein Hinweis über dem Feld, was eingesetzt wurde („Von der App eingesetzt: Lieferadresse“), damit der Mitarbeiter es prüft
- [ ] Angenommen der Mitarbeiter ändert den Entwurf, wenn er tippt, dann wird die Änderung ohne eigenen Klick zur Analyse gespeichert, und eine dezente Anzeige bestätigt „Gespeichert“
- [ ] Angenommen der Entwurf wurde geändert, wenn die Seite neu geladen oder von einer Kollegin geöffnet wird, dann erscheint die geänderte Fassung mit „bearbeitet von [Name] am …“
- [ ] Angenommen der Entwurf wurde geändert, wenn der Mitarbeiter „Original der KI wiederherstellen“ wählt, dann steht nach einer Rückfrage wieder der ursprüngliche Entwurf im Feld und wird gespeichert
- [ ] Angenommen der Mitarbeiter klickt „Kopieren“, wenn keine offenen Platzhalter im Text stehen, dann liegt der Text als reiner Text ohne Grußformel und Signatur in der Zwischenablage, und der Knopf bestätigt „Kopiert“
- [ ] Angenommen der Text enthält noch offene Platzhalter (eckige Klammern, die die App nicht füllen konnte), wenn der Entwurf angezeigt wird, dann steht über dem Feld „N Stellen noch ausfüllen“
- [ ] Angenommen der Text enthält noch offene Platzhalter, wenn der Mitarbeiter „Kopieren“ klickt, dann fragt die App „Noch N offene Stellen – trotzdem kopieren?“ und kopiert nur nach Bestätigung
- [ ] Angenommen das Speichern einer Änderung schlägt fehl (z. B. Verbindung weg), wenn der Mitarbeiter tippt, dann bleibt der Text im Feld, die Anzeige meldet „Nicht gespeichert – wird erneut versucht“, und Kopieren funktioniert weiterhin

### Quellen und Entwurfs-Wissen
- [ ] Angenommen der Vorschlag nennt Knowledge-IDs, wenn der Abschnitt „Quellen“ aufgeklappt wird, dann steht je Quelle ID, Titel, Typ und ggf. „Entwurf“
- [ ] Angenommen eine Quelle wird angeklickt, wenn sie aufklappt, dann erscheint der Text des Dokuments in der Fassung, mit der analysiert wurde, darunter der Link „In der Knowledge-Übersicht öffnen“
- [ ] Angenommen ein Dokument hat sich seit der Analyse geändert oder ist nicht mehr vorhanden, wenn die Quelle aufgeklappt wird, dann steht ein Hinweis „Seit der Analyse geändert“ bzw. „nicht mehr vorhanden“, und der aktuelle Text bzw. nur die ID wird gezeigt
- [ ] Angenommen der Vorschlag stützt sich auf Entwurfs-Wissen, wenn das Ergebnis angezeigt wird, dann steht im Kasten „Was ist zu tun?“ ein Hinweis „Beruht teilweise auf Entwurfs-Wissen – bitte kritisch prüfen“ mit den betroffenen IDs

### Letzte Analyse wiederfinden
- [ ] Angenommen zu einem Ticket gibt es eine Analyse aus den letzten 7 Tagen, wenn das Ticket ohne Analyse-Link geöffnet wird, dann erscheint automatisch die jüngste Analyse mit „Analyse von [Name], [Datum, Uhrzeit]“ samt ggf. bearbeitetem Entwurf
- [ ] Angenommen seit der angezeigten Analyse sind neue Nachrichten eingegangen, wenn das Ergebnis angezeigt wird, dann steht oben „Seit dieser Analyse sind neue Nachrichten eingegangen“ mit dem Knopf „Neu analysieren“, der das Formular mit den bisherigen Eingaben aufklappt
- [ ] Angenommen es gibt keine Analyse zum Ticket, wenn das Ticket geöffnet wird, dann ist das Formular wie bisher aufgeklappt und es gibt keinen Ergebnisbereich
- [ ] Angenommen nach einer Analyse wird erneut analysiert, wenn das neue Ergebnis vorliegt, dann ersetzt es die Anzeige; die frühere Analyse bleibt gespeichert (Liste in PROJ-11)

## Edge Cases
- **Zwei Mitarbeiter bearbeiten denselben Entwurf gleichzeitig:** Die zuletzt gespeicherte Fassung gilt; wer die Seite öffnet, sieht „bearbeitet von …“. Kein Sperren (selten, intern, geringe Folgen).
- **Leerer Entwurf** (Mitarbeiter löscht alles): wird gespeichert; Kopieren ist bei leerem Feld deaktiviert; „Original wiederherstellen“ bleibt möglich.
- **Sehr langer Entwurf:** Textfeld wächst mit, höchstens ca. 20.000 Zeichen werden gespeichert.
- **Analyse abgelaufen** (älter als 7 Tage, vor PROJ-11) oder Link einer fremden/ungültigen Analyse: kein Ergebnis, Formular aufgeklappt; bei einem Link der Hinweis „Diese Analyse ist nicht mehr verfügbar“.
- **Testläufe (PROJ-32):** erscheinen nur im Testmodus mit demselben Stand; beim normalen Öffnen erscheint die jüngste echte Analyse. Kennzeichnung „Testlauf“ bleibt.
- **Platzhalter-ähnlicher Text vom Kunden** (z. B. „[Foto 1]“ im Entwurf zitiert): zählt als offene Stelle; die Nachfrage beim Kopieren lässt sich bestätigen.
- **Kopieren nicht erlaubt** (Browser ohne Zugriff auf die Zwischenablage, z. B. ohne HTTPS): Der Text wird markiert und ein Hinweis „Mit Strg+C kopieren“ erscheint.
- **Ergebnis mit Prüfhinweisen** (verworfene IDs/Vorgänge): Hinweis bleibt im aufklappbaren Abschnitt, im Kasten oben steht nur „Prüfhinweise vorhanden“.

## Technical Requirements (optional)
- Gespeicherte Entwürfe wie die Analyse: vorübergehend, verschlüsselt, 7 Tage; mit PROJ-11 dauerhaft. Für PROJ-12 bleibt der Originalentwurf der KI neben der bearbeiteten Fassung erhalten.
- Keine Entwurfsinhalte im Log.
- Ohne JavaScript bleibt das Ergebnis lesbar (Abschnitte aufklappbar über HTML), Entwurf als Text sichtbar.
- Bearbeiten und Speichern nur mit gewähltem Namen (wie Analyse).

## Open Questions
- [ ] Soll der Unterschied zwischen KI-Entwurf und kopierter Fassung später (PROJ-12) als Maß für „unverändert / leicht / stark angepasst“ automatisch vorgeschlagen werden?
- [ ] Wird „Kopiert“ gezählt (Hinweis, dass die Antwort verwendet wurde) – Grundlage für Auswertungen in PROJ-14?

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Oben „Was ist zu tun?“ (Einstufung, Maßnahme, Befugnis, Fehlendes), direkt darunter der Antwortentwurf; Rest aufklappbar | Aushilfen brauchen die Handlung, nicht die ganze Herleitung; Herleitung bleibt prüfbar | 2026-10-06 |
| Entwurf wird beim Tippen automatisch zur Analyse gespeichert, Original wiederherstellbar | Keine verlorenen Änderungen; Kollegen sehen den Stand; Grundlage für Feedback-Auswertung | 2026-10-06 |
| Kopieren liefert nur den Text, ohne Gruß und Signatur | Signaturen im Team noch uneinheitlich; Zammad bzw. Mitarbeiter ergänzen sie | 2026-10-06 |
| Offene Platzhalter: Hinweis über dem Feld und Nachfrage beim Kopieren, Kopieren bleibt möglich | Schützt vor „[…]“ beim Kunden, ohne Sonderfälle zu blockieren | 2026-10-06 |
| Quellen klappen direkt im Ergebnis auf, mit Link zur Knowledge-Übersicht | Prüfen ohne das Ticket zu verlassen | 2026-10-06 |
| Quellen zeigen die Fassung zum Zeitpunkt der Analyse, Änderungen werden gekennzeichnet | Reproduzierbarkeit (PRD); Fingerabdruck je Dokument liegt bereits vor | 2026-10-06 |
| Ergebnis steht nach der Analyse oben, Formular eingeklappt auf eine Zeile | Ergebnis ist nach der Analyse das Wichtigste; neu analysieren bleibt einen Klick entfernt | 2026-10-06 |
| Letzte Analyse erscheint beim Öffnen des Tickets automatisch, mit Hinweis auf neue Nachrichten | Kolleginnen arbeiten am selben Stand weiter, unnötige KI-Aufrufe entfallen; Liste aller Analysen bleibt PROJ-11 | 2026-10-06 |
| Gleichzeitiges Bearbeiten: zuletzt gespeichert gilt, kein Sperren (von mir entschieden, bitte prüfen) | Selten; Sperren wäre aufwendig | 2026-10-06 |
| Testläufe erscheinen beim normalen Öffnen nicht als „letzte Analyse“ (von mir entschieden, bitte prüfen) | Tests sollen echte Arbeit nicht beeinflussen (PROJ-32) | 2026-10-06 |

### Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Weiter ohne Datenbank: Entwurf, Quellen-Fassungen und „letzte Analyse je Ticket“ im bestehenden verschlüsselten Zwischenspeicher (7 Tage) | Gleiche Mechanik wie PROJ-9; PROJ-11 überträgt alles in die Datenbank, ohne die Oberfläche zu ändern | 2026-10-06 |
| Bearbeiteter Entwurf wird neben dem KI-Original im selben Analyse-Datensatz gespeichert | Original bleibt für „wiederherstellen“ und PROJ-12 erhalten; eine Analyse = ein Datensatz | 2026-10-06 |
| Text der zitierten Knowledge-Dokumente wird bei der Analyse mitgespeichert (Momentaufnahme) | Quellen in der Fassung der Analyse zeigen, auch bei nicht committeten Änderungen; Inhalte sind keine Kundendaten | 2026-10-06 |
| Automatisches Speichern per Hintergrundanfrage (Alpine.js, kurz nach dem letzten Tastendruck), eigene Route nur für den Entwurf | Kein Neuladen, kein Speichern-Knopf; CSRF-geschützt, Name erforderlich, Länge begrenzt | 2026-10-06 |
| „Letzte Analyse“ als Verweis je Ticket; Testläufe mit eigenem Verweis je Schnittpunkt | Automatisches Wiederfinden ohne Datenbank; Tests beeinflussen die echte Anzeige nicht (PROJ-32) | 2026-10-06 |
| „Neue Nachrichten seit der Analyse“ über einen bei der Analyse gemerkten Stand des Verlaufs (letzte Nachricht) | Einfacher, sicherer Vergleich beim Öffnen | 2026-10-06 |
| Damalige Formulareingaben (Gruppe, Produkte, Variante, Kontextfeld, Bestelldaten von Hand) werden mit der Analyse gespeichert | Formular klappt mit den Eingaben der angezeigten Analyse auf, auch nach dem Neuladen | 2026-10-06 |
| Abschnitte als aufklappbare HTML-Elemente, Kopieren mit vorhandenem Muster (Zwischenablage, sonst markieren) | Funktioniert ohne JavaScript; bewährtes Verhalten aus PROJ-24 | 2026-10-06 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

### Überblick
PROJ-10 baut die Ergebnisanzeige aus PROJ-9 um und ergänzt drei Dinge, die gespeichert werden müssen: den bearbeiteten Antwortentwurf, die Fassung der zitierten Quellen und die „letzte Analyse“ je Ticket. Eine Datenbank ist dafür noch nicht nötig. Es gilt derselbe verschlüsselte Zwischenspeicher wie bei PROJ-9 (7 Tage); PROJ-11 überführt alles in die Datenbank.

### A) Bausteine
```
Ticketseite
+-- Ticketkopf (PROJ-6)
+-- Ergebnis (neu aufgebaut)
|   +-- Kopfzeile: „Analyse von Nele, 06.10., 14:05“ · ggf. „Testlauf“
|   +-- Hinweis „Seit dieser Analyse sind neue Nachrichten eingegangen“ + [Neu analysieren]
|   +-- Kasten „Was ist zu tun?“
|   |   +-- Einstufung, Confidence
|   |   +-- Empfohlene Maßnahme + Vorgänge
|   |   +-- Befugnis in klarer Sprache
|   |   +-- Fehlende Informationen mit Rückfrage
|   |   +-- Fehlendes Wissen / kein Wissen / Entwurfs-Wissen / „Prüfhinweise vorhanden“
|   +-- Antwortentwurf
|   |   +-- Überschrift mit Sprache · „bearbeitet von … am …“ · Speicheranzeige
|   |   +-- Hinweise „Von der App eingesetzt: …“ und „N Stellen noch ausfüllen“
|   |   +-- Textfeld (wächst mit)
|   |   +-- [Kopieren] (mit Nachfrage bei offenen Stellen) · [Original der KI wiederherstellen]
|   +-- Aufklappbare Abschnitte (anfangs zu, mit Anzahl)
|       +-- Begründung · Kurzfassung · Kategorie/Fallmuster · Confidence-Gründe
|       +-- Interne To-dos · Prüfhinweise · Metadaten
|       +-- Quellen (N): je Quelle aufklappbar mit Text + Link zur Knowledge-Übersicht
+-- Analyse-Formular (PROJ-9), nach einer Analyse eingeklappt auf eine Zeile
+-- Verlauf (PROJ-6)
```
Wiederverwendet: Karte, Abzeichen, Hinweis-Kasten, Knopf, aufklappbare Abschnitte (`collapsible`), Kopier-Verhalten aus `copy-field`, Knowledge-Textdarstellung.

### B) Daten
Der Analyse-Datensatz aus PROJ-9 bekommt zusätzlich:
- **Bearbeiteter Entwurf:** Text, wer ihn zuletzt bearbeitet hat und wann. Das KI-Original bleibt daneben unverändert.
- **Quellen-Fassungen:** für jede zitierte Knowledge-ID Titel, Typ, Status und Text zum Zeitpunkt der Analyse, dazu der Fingerabdruck (zum Erkennen späterer Änderungen).
- **Stand des Verlaufs:** Kennung und Zeit der letzten Nachricht bei der Analyse.
- **Formulareingaben:** Kundengruppe, Produkte, Variante, Kontextfeld, Bestelldaten von Hand.

Neu je Ticket:
- **Verweis „letzte Analyse“** auf die jüngste echte Analyse; Testläufe haben einen eigenen Verweis je Schnittpunkt.

Alles verschlüsselt im Zwischenspeicher, 7 Tage ab der letzten Änderung. Keine Entwurfsinhalte im Log.

### C) Technische Entscheidungen (für Nicht-Entwickler)
- **Speichern beim Tippen:** Die Seite schickt den Entwurf kurz nach dem letzten Tastendruck im Hintergrund an die App. Das ist eine eigene, kleine Adresse nur für den Entwurf, geschützt wie alle Formulare (Name erforderlich, Schutz gegen fremde Absendungen, höchstens 20.000 Zeichen). Schlägt es fehl, bleibt der Text im Feld und die App versucht es erneut.
- **Quellen als Momentaufnahme:** Würde die App die Quelle erst beim Aufklappen lesen, sähe man evtl. eine spätere Fassung. Deshalb wird der Text der zitierten Dokumente bei der Analyse mitgespeichert; der Fingerabdruck zeigt, ob sich das Dokument seither geändert hat.
- **Letzte Analyse wiederfinden:** Je Ticket merkt sich die App, welche Analyse die jüngste ist. Beim Öffnen des Tickets wird sie angezeigt; ob neue Nachrichten dazugekommen sind, erkennt die App am gemerkten Stand des Verlaufs.
- **Offene Platzhalter:** Die App zählt im Browser die eckigen Klammern, die sie nicht füllen konnte, nach derselben Regel wie beim Einsetzen.
- **Ohne JavaScript** bleibt alles lesbar: Abschnitte klappen über HTML auf, der Entwurf ist als Text sichtbar.

### D) Abhängigkeiten
Keine neuen Pakete.

### E) Hinweise für /frontend und /backend
- Bestehende Ergebnisse aus PROJ-9 ohne die neuen Felder müssen weiter angezeigt werden (Quellen dann nur mit ID, Formular ohne damalige Eingaben).
- „Original wiederherstellen“ setzt den bearbeiteten Entwurf zurück (gespeichert als „nicht bearbeitet“).
- Der Ergebnis-Link `?analyse=…` bleibt gültig; ohne Link gilt der Verweis „letzte Analyse“.
- Tests: Speichern (Name nötig, fremde Analyse/fremdes Ticket abgelehnt, Länge), Wiederfinden inkl. Testläufe, neue Nachrichten, Quellen geändert/entfernt, alte Ergebnisse ohne neue Felder.

## Implementation Notes (Frontend + Backend)
**Gebaut am 2026-10-06**, Frontend und Backend in einem Durchgang.

- **Ergebnis** (`resources/views/components/analysis/result.blade.php`): Kopf mit „Analyse von … am …“, Testlauf-Kennzeichen, Hinweis auf neue Nachrichten mit „Neu analysieren“ (öffnet das Formular); Kasten „Was ist zu tun?“ (Maßnahme, Vorgänge, Befugnis in klarer Sprache, fehlende Informationen, fehlendes Wissen, kein Wissen, Entwurfs-Wissen, „Prüfhinweise vorhanden“); danach Antwortentwurf; aufklappbare Abschnitte (`analysis/section`) für Begründung, Quellen (N), Kurzfassung mit Kategorie und Fallmuster, Confidence-Gründe (N), Interne To-dos (N), Prüfhinweise (N), Details zur Analyse.
- **Antwortentwurf** (`analysis/reply-editor`): Textfeld wächst mit; Speichern im Hintergrund 0,8 s nach dem letzten Tastendruck, bei Fehler alle 5 s erneut; Anzeige „wird gespeichert … / Gespeichert / Nicht gespeichert – wird erneut versucht“; „bearbeitet von … am …“; „Original der KI wiederherstellen“ mit Rückfrage; offene Platzhalter gezählt (gleiche Regel wie `Pseudonymizer::restore()`), Nachfrage beim Kopieren; leeres Feld nicht kopierbar; ohne Zwischenablage wird markiert.
- **Route** `PUT /tickets/{n}/analyse/{uuid}/entwurf` (`tickets.analysis.reply`, `AnalysisController::updateReply`, Form Request `UpdateReplyRequest`, höchstens `analysis.max_reply_length` = 20.000 Zeichen), Name erforderlich (409 als JSON), fremdes Ticket oder unbekannte Analyse 404.
- **Gespeichert mit der Analyse** (`CaseAnalyzer`): `sources` (Titel, Typ, Entwurf, Pfad, Text, Fingerabdruck der zitierten Dokumente), `thread` (letzte Nachricht), `inputs` (Formulareingaben), `reply_edit`. Verweis `analysis.latest.{Ticket::summaryKey()}` – Testläufe damit je Schnittpunkt getrennt.
- **Panel** (`AnalysisPanel`): ohne `?analyse=` die letzte Analyse; Hinweis „Diese Analyse ist nicht mehr verfügbar“ bei ungültigem Link; Quellen mit Zustand gleich/geändert/entfernt (Abgleich mit der aktuellen Knowledge); „Von der App eingesetzt: …“; Formular vorbelegt mit den Eingaben der angezeigten Analyse.
- **Formular** nach einer Analyse unter dem Ergebnis und eingeklappt auf „Analysiert mit: Gruppe · Produkte · Variante – Eingaben ändern und neu analysieren“; bei Fehlern offen.
- **Ältere Ergebnisse** aus PROJ-9 werden weiter angezeigt (Quellen dann ohne Text: „Der Text aus der Zeit der Analyse ist nicht gespeichert.“).
- **Abweichung:** Eingesetzte Werte sind im Textfeld nicht farbig markiert (technisch nicht möglich), stattdessen der Hinweis „Von der App eingesetzt: …“ wie in der Spec. `Pseudonymizer::restore()` (HTML mit Markierungen) wird in der Ansicht nicht mehr verwendet, bleibt aber getestet.
- **Ticketkopf (2026-10-06, Rückmeldung Product Owner):** Das Feld „Gruppe“ zeigte die Zammad-Gruppe (z. B. „allgemeine Kunden“) und wurde als Kundengruppe verstanden. Jetzt zwei Felder: „Kundengruppe“ (gewählt für das Ticket bzw. in der angezeigten Analyse, sonst „noch nicht gewählt“) und „Zammad-Gruppe“.
- **Tests:** `tests/Feature/PROJ-10-ResultViewTest.php` (18 Fälle); drei PROJ-9-Tests an die neue Ansicht angepasst. Gesamte Suite: 727 grün.

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
