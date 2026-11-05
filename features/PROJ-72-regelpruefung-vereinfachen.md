# PROJ-72: Regelprüfung vereinfachen

## Status: In Progress
**Created:** 2026-11-05
**Last Updated:** 2026-11-05

## Dependencies
- Requires: PROJ-36 (gezielter Wissensabruf, Nachladen verbindlicher Regeln)
- Requires: PROJ-19 (Antwort senden), PROJ-10 (Ergebnisansicht) – Blockade wird entfernt

## User Stories
- Als Kundenservice-Mitarbeiter möchte ich nicht mehr durch einen roten Banner „Regelprüfung unvollständig“ aufgehalten werden, den ich nicht verstehe.
- Als Kundenservice-Mitarbeiter möchte ich, dass die App verbindliche Regeln auch dann berücksichtigt, wenn die KI die empfohlene Maßnahme zwischendurch wechselt (Anlass: Ticket 2140246, Wechsel von Neuversand zu Neuproduktion).
- Als Kundenservice-Mitarbeiter möchte ich bei einer wirklich offenen Regel nur einen kurzen Hinweis sehen, der sagt, welche Regel ich nachlesen soll, ohne dass Kopieren oder Senden gesperrt wird.

## Out of Scope
- Änderungen am gezielten Wissensabruf, an Auslösern oder am KI-Router (PROJ-36, PROJ-37)
- Löschen der Spalten `rule_check`, `rule_check_by`, `rule_check_at` (bleiben für bestehende Daten)
- Eine Prüfung, ob der Entwurf die nachgeladenen Regeln tatsächlich einhält
- Ganz auf die Prüfung zu verzichten (verbindliche Regeln könnten sonst unbemerkt fehlen)

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

- [ ] Angenommen die KI empfiehlt im ersten Durchgang Maßnahme A und im zweiten Maßnahme B, wenn zu B verbindliche Regeln gehören, die noch nicht im Prompt sind, dann lädt die App sie ebenfalls nach (bis zu drei Nachladerunden insgesamt).
- [ ] Angenommen nach drei Runden fehlen weiter verbindliche Regeln oder der Platz reicht nicht, wenn das Ergebnis angezeigt wird, dann steht ein kleiner gelber Hinweis mit den Regel-IDs („Nicht berücksichtigt: … Bitte in der Knowledge-Übersicht nachlesen“), kein roter Banner.
- [ ] Angenommen der Hinweis erscheint, wenn der Mitarbeiter den Entwurf kopiert oder über Zammad sendet, dann ist beides ohne Bestätigung möglich.
- [ ] Angenommen eine Regel ist offen, wenn der Hinweis erscheint, dann nennt er die endgültig empfohlene Maßnahme (nicht die der ersten Runde).
- [ ] Angenommen eine Nachladerunde schlägt fehl (KI-Fehler), wenn das Ergebnis angezeigt wird, dann bleibt das Ergebnis der letzten gültigen Runde stehen und der gelbe Hinweis nennt die nicht berücksichtigten Regeln.
- [ ] Angenommen es gibt die Analysen-Übersicht, wenn eine Analyse offene Regeln hatte, dann zeigt sie ein Hinweis-Badge statt „unvollständig/gegengeprüft“.
- [ ] Angenommen der Mitarbeiter hat früher „Regeln selbst geprüft“ bestätigt, wenn er diese Analyse öffnet, dann erscheint kein Fehler und kein Knopf mehr.

## Edge Cases
- Maßnahmenwechsel in Runde 2 (Fall Ticket 2140246): zusätzlich benötigte Regeln werden in Runde 3 nachgeladen.
- Platzmangel: Produktabschnitt gibt zuerst nach (bestehendes Verhalten), reicht das nicht, kommen die Regeln in den Hinweis.
- Die KI wechselt die Maßnahme in jeder Runde: nach drei Runden Abbruch mit Hinweis.
- Ältere Analysen mit `rule_check = incomplete` bleiben ohne Fehler anzeigbar.
- Kosten und Dauer: höchstens drei zusätzliche KI-Durchgänge, nur wenn tatsächlich neue verbindliche Regeln fehlen.

## Technical Requirements (optional)
- Performance: Ohne fehlende Regeln unverändert ein Durchgang.

## Open Questions
- [x] Soll die Zahl der Runden in den Einstellungen (PROJ-31) einstellbar sein? Entschieden 2026-11-05: Nein. Jede Runde ist ein voller KI-Durchgang (rund 40 Sekunden und 14.000 Eingabe-Tokens mehr), die Einstellung wäre ein Risiko (0 hebt die Absicherung auf, hohe Werte kosten). Die Zahl bleibt in `config/analysis.php` und wird nur bei Bedarf geändert.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Bis zu drei Nachladerunden | Löst den Maßnahmenwechsel, begrenzt Kosten und Laufzeit | 2026-11-05 |
| Nur Hinweis, keine Blockade | Der Mitarbeiter versteht die Sperre nicht; verbindliche Regeln sind mit dem Nachladen weitgehend abgesichert, der Rest wird sichtbar gemacht | 2026-11-05 |
| Prüfung nicht ganz entfernen | Sonst fehlen verbindliche Regeln unbemerkt | 2026-11-05 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|

---
<!-- Sections below are added by subsequent skills -->

## Implementation Notes (2026-11-05)
- `CaseAnalyzer::safeguard()` ist eine Schleife über höchstens `analysis.limits.safeguard_rounds` (Standard 3, in `config/analysis.php`, keine Umgebungsvariable) Nachladerunden. Jede Runde berechnet die fehlenden verbindlichen Regeln aus der zuletzt empfohlenen Maßnahme; geladene Regeln sammeln sich. Fällt eine Runde aus, bleibt das Ergebnis der letzten gültigen Runde stehen. `passes` = 1 + Anzahl Runden.
- Der gespeicherte Datensatz `retrieval.safeguard` hat neu `final_actions` und `rounds`; ältere Analysen ohne diese Felder werden mit `first_actions` angezeigt.
- Entfernt: roter Banner, Bestätigungsknopf und Route `tickets.analysis.rule-check`, `AnalysisStore::acknowledgeRuleCheck()` und `ruleCheckBlocksCopy()`, die beiden 409 beim Kopieren und Senden, Sperre im Antwort-Editor, Zweig „blocked“ in `ReplyDelivery`. Spalten `rule_check*` bleiben.
- Neu: gelber Hinweis „Zur Maßnahme (…) konnten verbindliche Regeln nicht berücksichtigt werden: … Bitte in der Knowledge-Übersicht nachlesen“ und Kopf-Badge „Regel nicht berücksichtigt“; dasselbe Badge in der Analysen-Übersicht. Die Prüfhinweis-Zeile „Regelprüfung unvollständig“ entfällt.
- Tests: PROJ-36 (neuer Test für den Maßnahmenwechsel in mehreren Runden, bisherige Tests auf eine Runde begrenzt) und PROJ-19 angepasst; alle Analyse-Tests grün bis auf den bekannten PROJ-56-Fehler.


## Tech Design (Solution Architect)
_To be added by /architecture_

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
