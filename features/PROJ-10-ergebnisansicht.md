# PROJ-10: Ergebnisansicht

## Status: Planned
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

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)
_To be added by /architecture_

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
