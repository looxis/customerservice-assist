# PROJ-75: Durchsicht der Wissensdateien auf überflüssige interne Schritte

## Status: Planned
**Created:** 2026-11-05
**Last Updated:** 2026-11-05

## Dependencies
- Requires: PROJ-73 (Ergebnisansicht entschlacken, „Schritte für diesen Fall“ entfallen)
- Berührt: PROJ-2 (Knowledge-Authoring-Kit), PROJ-30 (interne Abläufe), PROJ-58 (Links zu Werkzeugen)

## User Stories
- Als Wissenspfleger (Etienne) möchte ich alle Wissensdateien einmal gemeinsam mit Claude durchgehen, damit keine Regel mehr interne Schritte vorschreibt, die längst automatisch laufen oder schon erledigt sind.
- Als Wissenspfleger möchte ich offene Punkte („Noch zu klären“) und widersprüchliche Texte dabei entscheiden, damit die App sie nicht als geltenden Inhalt mitliest.
- Als Kundenservice-Mitarbeiter möchte ich kürzere, eindeutige Regeln, damit der Vorschlag und die Quellen weniger Text enthalten.
- Als Wissenspfleger möchte ich eine Liste der Dateien mit Handlungsbedarf, damit ich in Ruhe und in eigenem Tempo entscheiden kann, nicht an einem Stück.

## Out of Scope
- Änderungen an App-Code, Prompt oder Oberfläche (PROJ-71 bis PROJ-74, PROJ-76)
- Neue Wissensinhalte oder neue Regeln (nur Kürzen, Klären, Entfernen bestehender Schritte)
- Produktfakten in ProductForge (eigene Pflege)
- Automatische Massenänderung ohne Entscheidung je Datei

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

- [ ] Angenommen die Durchsicht beginnt, wenn Claude die Dateien in `knowledge/` durchsucht, dann entsteht eine Trefferliste mit Datei, ID, Fundstelle und Art des Treffers (Schritt läuft automatisch, bereits erledigt, doppelt zu einer anderen Regel, unklar).
- [ ] Angenommen ein Treffer, wenn Etienne ihn bewertet, dann entscheidet er je Treffer: streichen, kürzen, behalten oder zurückstellen; Claude ändert nichts ohne diese Entscheidung.
- [ ] Angenommen eine Datei enthält „Noch zu klären“, wenn sie durchgesehen wird, dann werden die Punkte entschieden oder der Abschnitt entfernt.
- [ ] Angenommen Änderungen wurden vereinbart, wenn sie umgesetzt sind, dann läuft `knowledge:check` ohne Fehler und die Dateien sind committet (getrennter Commit je Themengruppe).
- [ ] Angenommen der Skill `knowledge`, wenn er neues Wissen erfasst, dann fragt er ab sofort: „Folgt der Schritt automatisch aus dem Reklamationsauftrag oder einem anderen System?“

## Edge Cases
- Ein Schritt ist nur für Aushilfen sinnvoll, nicht für Stammkräfte: eher in eine Arbeitsanleitung (`procedure`) verschieben als streichen.
- Ein Schritt steht in mehreren Dateien: einmal behalten, sonst auf die ID verweisen.
- Eine Regel nennt einen Schritt, der Voraussetzung einer anderen Regel ist: erst die abhängige Datei klären.
- Ein Treffer betrifft ein Dokument mit `trial_until`: Testphase und Ziel nicht verändern.

## Technical Requirements (optional)
- Vorgehen: grep nach typischen Formulierungen (Produktion informieren, Versand anstoßen, Auftrag bestätigen, Neuversand in EOCS anlegen, in Zammad notieren); Treffer in einer Liste unter `docs/` oder im Gespräch, keine Dateien in `knowledge/` dafür anlegen.

## Open Questions
- [ ] Welche Schritte laufen automatisch (EOCS, Produktion, Versand) und welche nicht? Etienne klärt das vorab in Ruhe.
- [ ] Sollen die Rückfragen („läuft automatisch?“) im Skill `knowledge` fest eingebaut werden?
- [ ] Gibt es Dokumente, die bei der Gelegenheit zusammengelegt oder gestrichen werden sollen?

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Eigene Spec, später und in Ruhe | Es gibt viele Klärpunkte; Etienne braucht Zeit und will nicht unter Druck entscheiden | 2026-11-05 |
| Keine Massenänderung ohne Einzelentscheidung | Falsche Streichungen führen zu falschen Kundenantworten | 2026-11-05 |

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
