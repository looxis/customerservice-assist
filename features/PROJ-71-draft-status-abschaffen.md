# PROJ-71: Draft-Status für Wissen abschaffen

## Status: Planned
**Created:** 2026-11-05
**Last Updated:** 2026-11-05

## Dependencies
- Requires: PROJ-23 (Entwurfs-Wissen bestätigen) – wird in der heutigen Form abgelöst
- Requires: PROJ-56 (vorläufiges Wissen) – bleibt unverändert und ist die einzige Form von „noch nicht endgültig“

## User Stories
- Als Wissenspfleger (Etienne) möchte ich neue Wissensdateien sofort als gültig anlegen, damit ich sie nicht später einzeln per Klick bestätigen muss.
- Als Kundenservice-Mitarbeiter möchte ich im Ergebnis keinen Hinweis „beruht teilweise auf Entwurfs-Wissen“ sehen, damit ich nicht von einem Status verunsichert werde, der keine Rolle mehr spielt.
- Als Wissenspfleger möchte ich ein zeitlich begrenztes Vorgehen weiter mit „gültig bis“ (`trial_until`) kennzeichnen und veraltetes Wissen auf `deprecated` setzen können.
- Als Admin möchte ich in der Knowledge-Übersicht keine Entwurfs-Filter und -Kacheln mehr sehen.

## Out of Scope
- Änderungen an `trial_until`, `trial_goal`, „in Erprobung“ und der Fristenwarnung (PROJ-56 bleibt unverändert)
- Der Übergang `active` → `deprecated` und das Markieren als veraltet bleiben
- Neue Prüfregeln für die Qualität von Wissen
- Inhaltliche Durchsicht der 118 bisherigen Entwürfe (sie werden unverändert auf `active` gesetzt)

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

- [ ] Angenommen in `knowledge/` gibt es Dateien mit `status: draft`, wenn die Umstellung durchgeführt ist, dann steht in keiner Datei (auch nicht in `knowledge/templates/`) mehr `status: draft`; alle früheren Entwürfe sind `active`.
- [ ] Angenommen eine Datei trägt `status: draft`, wenn `knowledge:check` läuft, dann meldet die Prüfung einen Fehler („Status draft gibt es nicht mehr“).
- [ ] Angenommen ein Dokument hat `trial_until` in der Zukunft, wenn es im Ergebnis verwendet wird, dann erscheint weiterhin „Vorläufig bis …“ bzw. „in Erprobung“ wie bisher.
- [ ] Angenommen die Analyse nutzt Wissen, wenn das Ergebnis angezeigt wird, dann erscheint kein Hinweis „Beruht teilweise auf Entwurfs-Wissen“ und kein „Entwurf“-Badge an Quellen, Abläufen oder Arbeitsanleitungen.
- [ ] Angenommen ein Admin öffnet ein Wissensdokument, wenn die Seite lädt, dann gibt es keinen Knopf „Als gültig bestätigen“ und keinen Hinweis „Entwurfs-Wissen“; „Als veraltet markieren“ bleibt für Admins.
- [ ] Angenommen die Knowledge-Übersicht und „Über die App“ werden geöffnet, wenn die Seiten laden, dann gibt es keine Entwurf-Kachel, keinen Entwurf-Filter und keine Entwurfs-Zahl.
- [ ] Angenommen der Prompt für die Analyse wird erzeugt, wenn Wissen aufgelistet wird, dann steht dort weder das Kennzeichen „Entwurf“ noch die Anweisung, Entwurfs-Wissen in der Begründung zu erwähnen.
- [ ] Angenommen eine neue Datei wird mit dem Skill `knowledge` oder der Vorlage angelegt, wenn sie gespeichert wird, dann ist `status: active` gesetzt; Authoring Guide, Design-Dokument, README, PRD und „Über die App“ beschreiben den neuen Ablauf.
- [ ] Angenommen eine aktive Datei enthält den Abschnitt „Noch zu klären“, wenn `knowledge:check` läuft, dann warnt die Prüfung weiterhin (offene Fragen würden als geltender Inhalt mitgelesen).

## Edge Cases
- Bereits gespeicherte Analysen haben `draft_ids` und `draft` an Quellen: Ältere Vorschläge müssen weiter anzeigbar sein, ohne Fehler und ohne Hinweis.
- Dateien mit `status: draft` und einem Abschnitt „Noch zu klären“ werden durch die Umstellung `active` und lösen die Warnung aus: Warnungen prüfen, Abschnitte entscheiden oder entfernen.
- `TrialChanger` benutzt die Zeilenänderung von `status:` und `deprecated`: Beides darf durch das Entfernen des Bestätigungsklicks nicht kaputtgehen.
- Offene Meldungen „bitte prüfen“ (PROJ-56) an früheren Entwürfen bleiben unverändert.

## Technical Requirements (optional)
- Security: Statuswechsel auf `deprecated` bleibt Admin-only.

## Open Questions
- [ ] Gibt es unter den 118 Entwürfen welche, die bewusst noch nicht live sein sollen (dann vorab auf `deprecated` oder mit `trial_until` versehen)?

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| `draft` ersatzlos streichen, alle Entwürfe auf `active` | Bestätigen per Klick ist großer manueller Aufwand ohne Nutzen; Auffälligkeiten fallen im Betrieb auf und das Wissen wird dann angepasst | 2026-11-05 |
| `trial_until` und `deprecated` bleiben | Zeitliche Begrenzung und Außerbetriebnahme sind weiter sinnvoll | 2026-11-05 |
| Abweichung des PRD („auch `draft` wird verwendet“) entfällt | Die Ausnahme zum Design-Dokument wird gegenstandslos | 2026-11-05 |

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
