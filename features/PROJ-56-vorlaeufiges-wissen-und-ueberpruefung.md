# PROJ-56: Vorläufiges Wissen und Überprüfung

## Status: In Progress
**Created:** 2026-10-19
**Last Updated:** 2026-10-19

## Dependencies
- Requires: PROJ-3 (Wissen einlesen und prüfen), PROJ-12 (Meldungen und Wissenslücken), PROJ-23 (Status per Klick), PROJ-36 (Abruf), PROJ-41 (Seitenleiste).

## Ausgangslage
Manche Vorgehensweisen werden bewusst nur eine Zeit lang getestet („das machen wir mal einige Wochen so“). Ohne Befristung laufen sie jahrelang weiter, auch wenn sie nachweislich nichts bringen. Heute kennt die Wissensbasis nur `draft` (unbestätigt), `active` und `deprecated`. Dazu soll jeder Mitarbeiter jedes Wissen als „möglicherweise veraltet / bitte prüfen“ melden können.

## Umfang in vier Schritten
1. **Befristung im Dokument** (`trial_until`, `trial_goal`), Kennzeichnung, Prompt-Hinweis, Prüfung. **Gebaut am 2026-10-19.**
2. **Meldung „bitte prüfen“ für alle Wissensdokumente** durch Mitarbeiter, Reiter „Zur Prüfung“ in der Admin-Seite der Wissenslücken, Zähler je Dokument, Hinweis „mehrfach gemeldet“ ab 3 Meldungen von 2 Mitarbeitern, nie automatische Abschaltung; Admin-Aktionen „Geprüft, bleibt“, „Für den KI-Chat kopieren“, „Als veraltet markieren“.
3. **Ablauf der Testphase:** Das Dokument bleibt im Einsatz mit der Warnung „Prüfung überfällig seit X Tagen“ (Ergebnis, Wissensübersicht, Seitenleiste, Admins); nach 30 Tagen ohne Entscheidung wird es nicht mehr abgerufen; Entscheidung per Klick (bewährt und dauerhaft gültig, Testphase verlängern, beenden), Zahl „zur Überprüfung“ in der Seitenleiste.
4. **Belege je Dokument** aus vorhandenen Daten: wie oft abgerufen, Entwürfe unverändert/angepasst/gesendet, Bewertungen, Meldungen; für befristete hervorgehoben.

## Out of Scope
- Automatisches Abschalten von gemeldetem Wissen.
- Ändern von Wissensdateien und Committen aus der App (siehe PROJ-55).
- Eine eigene Statusart „vorläufig“: Die Befristung ist unabhängig vom Status.

## Acceptance Criteria – Schritt 1
- [x] Angenommen ein Dokument hat `trial_until: 2027-01-31` (als Text oder als YAML-Datum), wenn die App es liest, dann ist es als Testphase erkannt; letzter Tag der Testphase ist der 31.01., danach gilt sie als abgelaufen.
- [x] Angenommen `trial_until` ist kein gültiges Datum, dann meldet `knowledge:check` einen Fehler an dieser Stelle.
- [x] Angenommen `trial_goal` steht ohne `trial_until` oder die Testphase hat kein Ziel, dann gibt es eine Warnung.
- [x] Angenommen die Testphase endet in 14 Tagen oder weniger oder ist vorbei, dann warnt `knowledge:check` mit Datum.
- [x] Angenommen ein Vorschlag nutzt ein Dokument in der Testphase, dann steht im Wissensabschnitt für die KI „in Erprobung“ mit Datum und Ziel und der Auftrag, das kurz zu erwähnen; nach Ablauf zusätzlich, dass die Überprüfung überfällig ist.
- [x] Angenommen ein Vorschlag stützt sich auf ein Dokument in der Testphase, dann nennt das Ergebnis es („Beruht teilweise auf vorläufigem Wissen“), die Quelle trägt „Vorläufig bis TT.MM.JJJJ“, nach Ablauf „Testphase abgelaufen, Überprüfung überfällig“.
- [x] Angenommen ein Dokument ist in der Testphase, dann zeigt die Wissensübersicht „Vorläufig bis …“ (nach Ablauf „abgelaufen“, rot) und die Dokumentseite einen Hinweis mit Ziel.
- [x] Angenommen ein Dokument hat keine Befristung, dann ändert sich nichts.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Befristung unabhängig vom Status (`active` oder `draft`) | `draft` meint „unbestätigt“, nicht „bewusst befristeter Test“ | 2026-10-19 |
| Nach Ablauf weiter nutzen, aber laut warnen; nach 30 Tagen ohne Entscheidung nicht mehr abrufen | Entscheidung des Auftraggebers: Ein Verfahren soll nicht mitten im Fall verschwinden, aber auch nicht jahrelang unbemerkt weiterlaufen | 2026-10-19 |
| Meldung „bitte prüfen“ gilt für alle Wissensdokumente | Entscheidung des Auftraggebers | 2026-10-19 |
| Meldungen schalten nie automatisch ab | Ein einzelner Mitarbeiter soll Wissen nicht allein außer Kraft setzen | 2026-10-19 |

### Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Felder im Frontmatter (`trial_until`, `trial_goal`), Datum als `JJJJ-MM-TT`, letzter Tag in Berliner Zeit | Dokumente bleiben Markdown mit Git-Historie; kein neuer Speicher nötig | 2026-10-19 |
| Hinweis an die KI über den Wissensabschnitt (wie die Hinweise zu Dokumenten ohne geprüfte Bedingung), nicht über den Prompt | Der Prompt bleibt unverändert und behält seine Version | 2026-10-19 |
| Die Befristung wird mit der Quelle im Protokoll gespeichert (`trial_until`, `trial_goal`) | Ein alter Vorschlag zeigt den Stand von damals | 2026-10-19 |

## Implementation Notes (Schritt 1, 2026-10-19)
- **Gelesen und geprüft:** `KnowledgeDocument::trialUntil()/trialGoal()/isTrial()/trialExpired()`, `KnowledgeValidator::trialFields()` (Fehler bei ungültigem Datum; Warnung bei Ziel ohne Datum, ohne Ziel, 14 Tage vor Ende und nach Ende); die zwei Felder sind bekannte Felder.
- **Prompt:** `CaseAnalyzer::trialNote()` setzt vor den Text des Dokuments einen Hinweis (Datum, Ziel, Auftrag zur Erwähnung); die Überschrift trägt „in Erprobung“.
- **Anzeige:** Quellen im Ergebnis (Badge und Hinweiszeile), Wissensübersicht (Badge), Dokumentseite (Hinweis), `AnalysisPanel::sources()` liest die Felder aus dem Protokoll.
- **Autoren:** Authoring Guide, Skill `knowledge` und die Vorlagen Policy und Playbook kennen die Felder.
- **Tests:** `PROJ-56-TrialKnowledgeTest` (12 Tests).
- **Schritte 2 bis 4:** noch offen.

## QA Test Results
_To be added by /qa_
