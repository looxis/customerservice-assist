# PROJ-73: Ergebnisansicht entschlacken

## Status: Planned
**Created:** 2026-11-05
**Last Updated:** 2026-11-05

## Dependencies
- Requires: PROJ-10 (Ergebnisansicht), PROJ-30 (interne Abläufe), PROJ-39 (schrittweise Fallbearbeitung)
- Berührt: PROJ-72 (Regelprüfung-Banner) und PROJ-71 (Entwurfs-Hinweis) – diese Hinweise entfallen dort, nicht hier

## User Stories
- Als Kundenservice-Mitarbeiter möchte ich nach dem Vorschlag den Antwortentwurf sehen, ohne dass dieselbe Information vorher als „Was ist zu tun?“ und „Schritte für diesen Fall“ wiederholt wird.
- Als Kundenservice-Mitarbeiter möchte ich keine Anweisungen lesen, die längst erledigt sind oder automatisch laufen (zum Beispiel „Reklamationsauftrag bestätigen“, „Ersatzteil versenden“, nachdem ich den Reklamationsauftrag angelegt habe).
- Als Aushilfe möchte ich die Arbeitsanleitungen („in EOCS oben rechts auf … klicken, dann …“) unverändert behalten, damit ich Vorgänge Schritt für Schritt umsetzen kann.
- Als Kundenservice-Mitarbeiter möchte ich bei einer echten Rückfrage („Zuerst zu klären“) weiter die Auswahl sehen.

## Out of Scope
- Arbeitsanleitungen (Dokumenttyp `procedure`), ihr Inhalt, Abhaken, Links zu Werkzeugen und kritische Hinweise: bleiben unverändert (PROJ-30, PROJ-58)
- „Zuerst zu klären“ mit Auswahl und freiem Ergebnis (PROJ-39) bleibt
- Der Einführungsmodus für Infotexte (PROJ-74)
- Änderungen im Erweitert-Modus (Begründung, Quellen, Wissensabruf, Protokoll)
- Inhaltliche Durchsicht der Wissensdateien auf überflüssige interne Schritte (eigene Aufgabe im Anschluss, nicht Teil dieser Spec)

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

- [ ] Angenommen ein Vorschlag wurde erstellt, wenn das Ergebnis angezeigt wird, dann enthält „Was ist zu tun?“ nur die kurze Empfehlung, die Befugnis („Du darfst das selbst entscheiden“ oder „Freigabe nötig“) sowie Wissenslücken und Warnungen, die Handlung erfordern (fehlendes oder unbekanntes Produkt, kein Wissen verwendet).
- [ ] Angenommen ein Vorschlag wurde erstellt, wenn die Antwort der KI geprüft wird, dann gibt es kein Feld „Schritte für diesen Fall“ mehr (weder im Ergebnis noch im Block „Interne Abläufe“); ältere Vorschläge mit gespeicherten Schritten werden ohne Fehler angezeigt, die Schritte erscheinen nicht.
- [ ] Angenommen der Prompt wird erzeugt, wenn die KI den Vorschlag liefert, dann soll sie keine Schritte nennen, die das Anlegen des Reklamationsauftrags in EOCS bereits auslöst; Produktion und Versand laufen automatisch.
- [ ] Angenommen der Mitarbeiter hat im Feld „Zusätzliche Informationen“ eine Reklamationsnummer genannt, wenn der Vorschlag erstellt wird, dann behandelt der Vorschlag den Auftrag als angelegt und verlangt keine Bestätigung.
- [ ] Angenommen zum Fall gibt es passende Arbeitsanleitungen, wenn das Ergebnis angezeigt wird, dann stehen sie weiter unter „Interne Abläufe“ (eingeklappt), mit allen Schritten zum Abhaken, Links zu den Werkzeugen und kritischen Hinweisen, unverändert im Inhalt.
- [ ] Angenommen die Analyse erkennt, dass zuerst eine Klärung nötig ist, wenn das Ergebnis angezeigt wird, dann erscheint „Zuerst zu klären“ mit den Optionen oder dem freien Feld und danach wird der Vorschlag fortgesetzt (PROJ-39).
- [ ] Angenommen es liegt keine Warnung vor, wenn das Ergebnis angezeigt wird, dann erscheinen weder „Prüfhinweise vorhanden“ noch „Bereits intern geklärt“; im Erweitert-Modus stehen sie weiter in „Prüfhinweise“.
- [ ] Angenommen der Antwortentwurf wurde erstellt, wenn der Mitarbeiter die Seite öffnet, dann steht der Antwortentwurf direkt nach „Was ist zu tun?“ und den Arbeitsanleitungen.
- [ ] Angenommen der Vorschlag verlangt eine Rückfrage an den Kunden, wenn der Entwurf die Rückfrage enthält, dann bleibt das unverändert (kein Wegfall von Inhalten im Entwurf).

## Edge Cases
- Die KI liefert noch `internal_steps` (alter Prompt-Stand oder Zwischenstand): Validierung darf nicht fehlschlagen; Feld wird ignoriert.
- Preis-Platzhalter, die bisher in den Schritten erklärt wurden: Hinweis steht in der Empfehlung und im Entwurf.
- Fall mit `next_step = internal_first` ohne Schritte: gültig, solange eine Klärung vorliegt.
- Keine Arbeitsanleitung passt: Hinweis „Kein passender Ablauf hinterlegt“ bleibt, aber nur im Erweitert- bzw. Einführungsmodus.
- Admin-Auswertungen und Protokolle, die bisher die Schritte lesen, brauchen eine Anpassung ohne Fehler.

## Technical Requirements (optional)
- Tests: Reihenfolge-Assertions in `PROJ-10-ResultViewTest`, `ResultLayoutExpertModeTest`, `PROJ-9`, `PROJ-30`, `PROJ-39` an die neue Struktur anpassen.

## Open Questions
- [ ] Sollen Wissensdateien, die interne Schritte vorschreiben (Produktion informieren, Versand anstoßen, Auftrag bestätigen), in einer eigenen Durchsicht gekürzt werden? (Vorschlag: nach der Umsetzung, Trefferliste gemeinsam durchgehen)

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| „Schritte für diesen Fall“ ersatzlos streichen | Wiederholen den Antwortentwurf oder sind längst erledigt (Ticket 2140246: Reklamationsauftrag war schon angelegt) | 2026-11-05 |
| Arbeitsanleitungen bleiben unverändert | Etienne: Klick-für-Klick-Anleitungen sind für Aushilfen unverzichtbar | 2026-11-05 |
| „Zuerst zu klären“ bleibt | Echte Rückfragen ändern die Antwort (PROJ-39) | 2026-11-05 |
| Allgemeine Prompt-Regel statt Prüfung jeder einzelnen Datei | Wenig Aufwand; die Durchsicht der Dateien folgt getrennt | 2026-11-05 |

### Technical Decisions
<!-- Added by /architecture -->
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
