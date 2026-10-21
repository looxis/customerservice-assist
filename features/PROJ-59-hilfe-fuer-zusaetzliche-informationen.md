# PROJ-59: Hilfe für das Feld „Zusätzliche Informationen“

## Status: Planned
**Created:** 2026-10-21
**Last Updated:** 2026-10-21

## Dependencies
- Requires: PROJ-9 (Fallanalyse), PROJ-39 (schrittweise Fallbearbeitung), PROJ-31 (Prompts)
- Verwandt: PROJ-57 (Detailfragen), PROJ-48 (Verlauf für die Analyse)

## Ausgangslage
Das Feld „Zusätzliche Informationen / eigene Einschätzung“ am Analyse-Formular nennt als Beispiele nur Fakten („Foto geprüft …“) und gilt für die KI als geprüfter Fakt. Anlass: Ticket 2139949, eine Folgenachricht des Kunden nach einer ersten Zusage von uns. Die Angaben des Mitarbeiters enthielten Fakten, eine Entscheidung (Preisdifferenz erstatten, Versandkosten nicht) und die Ursache der Verzögerung. Die KI setzte Fakten und Entscheidung um, ließ aber die Erklärung der Verzögerung weg, und die Antwort klang knapp. Ein Mitarbeiter, der nicht weiß, **was** er in welcher Form eintragen soll, bekommt noch eher eine unvollständige Antwort. Es fehlt Hilfe im Formular und eine klare Regel im Prompt, die Fakten, Entscheidungen und Wünsche an die Antwort unterscheidet.

## User Stories
- Als Mitarbeiterin bei einer Folgenachricht möchte ich Beispiele sehen, die zu dieser Lage passen (Stand, Entscheidung, Wunsch an die Antwort), damit ich weiß, was ich eintragen kann.
- Als Aushilfe möchte ich in zwei, drei Sätzen erklärt bekommen, wie ich Fakten, Entscheidungen und Wünsche formuliere, damit die KI sie richtig versteht.
- Als Mitarbeiterin möchte ich, dass die KI Ursachen und Erklärungen aus meinen Angaben in die Antwort an den Kunden übernimmt, damit die Antwort nicht knapp und förmlich bleibt.

## Out of Scope
- Ein zweites Eingabefeld: bewusst nicht, ein Feld mit passenden Beispielen genügt zunächst (Entscheidung 2026-10-21).
- Felder für Fragen ohne Ticket (dort gibt es nur den Fragetext).
- Strukturierte Eingabe (Auswahlfelder, Pflichtangaben) und das Speichern von Vorlagen je Mitarbeiter.
- Änderungen an „Interne Klärungen“ (PROJ-39).
- Ein eigenes Ton-Dokument für Fehler und Verzögerungen (kann später aus dem Wissen kommen).

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

- [ ] Angenommen das Ticket enthält schon eine Antwort von uns (öffentliche Nachricht eines Mitarbeiters, nicht automatisch, keine interne Notiz), wenn das Analyse-Formular angezeigt wird, dann zeigt das Feld „Zusätzliche Informationen“ im grauen Beispieltext Beispiele für eine Folgenachricht: bisherige Entscheidung, Stand, Wunsch an die Antwort.
- [ ] Angenommen das Ticket enthält noch keine Antwort von uns, wenn das Formular angezeigt wird, dann bleiben die bisherigen Beispiele (Fakten zum Fall) stehen.
- [ ] Angenommen das Formular ist sichtbar, wenn der Mitarbeiter den Hinweis „So schreibst du es“ aufklappt, dann stehen dort kurze Hinweise: Fakten in einfachen Sätzen, Entscheidungen mit „Wir …“, Wünsche an die Antwort mit „Bitte erkläre …“, keine allgemeinen Regeln.
- [ ] Angenommen das Feld enthält schon Text (früherer Vorschlag), wenn das Formular angezeigt wird, dann bleibt der Text unverändert; nur der graue Beispieltext hängt von der Lage ab.
- [ ] Angenommen der Mitarbeiter schreibt eine Entscheidung („Wir erstatten die Differenz“), wenn die Analyse läuft, dann setzt der Antwortentwurf sie um, und die Angabe gilt wie bisher mit Vorrang vor Vermutungen.
- [ ] Angenommen die Angaben nennen eine Ursache oder den Grund einer Verzögerung, die nicht intern ist, wenn der Antwortentwurf entsteht, dann steht die Erklärung kurz in der Antwort.
- [ ] Angenommen die Angaben enthalten einen Wunsch an die Antwort („bitte für die verspätete Rückmeldung entschuldigen“), wenn der Antwortentwurf entsteht, dann erfüllt der Entwurf den Wunsch, soweit er nicht gegen Regeln im Wissen verstößt.
- [ ] Angenommen die Angaben enthalten Interna (Namen von Kollegen, interne Kosten), wenn der Antwortentwurf entsteht, dann stehen sie nicht wörtlich im Entwurf.
- [ ] Angenommen die Prompts werden geändert, wenn die Analyse läuft, dann ist die Prompt-Version erhöht.

## Edge Cases
- Eine Nachricht von uns, die ein Kollege als „Kunde“ in Zammad geschrieben hat: zählt nach der Regel der App als Kundennachricht (wie bisher), nicht als Antwort von uns.
- Nur eine interne Notiz oder eine automatische Eingangsbestätigung: zählt nicht als Antwort von uns.
- Rückspulen im Testmodus: Maßgeblich ist der zurückgespulte Stand des Tickets.
- Die KI soll Wünsche nicht als Fakten behandeln: Ein Wunsch an die Antwort ändert keine Bewertung (zum Beispiel „berechtigt“), nur die Formulierung.
- Widerspricht ein Wunsch einer Regel im Wissen (zum Beispiel „erstatte alles“ bei fehlender Befugnis): Die Regel gilt; die Begründung nennt den Widerspruch.

## Technical Requirements (optional)
- Keine neue Datenhaltung, keine neuen Pakete.
- Der Beispieltext ist reiner Text (Platzhalter), kein Eingabewert; er wird nicht gesendet.

## Open Questions
- [ ] Soll der Hinweis „So schreibst du es“ auch auf der Seite „Über die App“ stehen? (Vorschlag: ja, ein kurzer Absatz.)

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Ein Feld mit lageabhängigen Beispielen statt zweitem Feld | Weniger Reibung, keine Verwechslung zwischen zwei Feldern; vieles steht schon im Verlauf | 2026-10-21 |
| Lageabhängig: Folgenachricht, wenn das Ticket schon eine Antwort von uns enthält | Dann ist Stand und Entscheidung der wichtigste Zusatz | 2026-10-21 |
| Kurzer aufklappbarer Hinweis „So schreibst du es“ | Aushilfen brauchen eine Anleitung, ohne dass die Maske voller wird | 2026-10-21 |
| Prompt unterscheidet Fakten, Entscheidungen, Wünsche an die Antwort | Anlass Ticket 2139949: Erklärung der Verzögerung fehlte | 2026-10-21 |
| Erklärungen aus den Angaben gehören in die Antwort, Interna nicht | Antwort soll nicht knapp wirken, aber nichts Internes verraten | 2026-10-21 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|
| Keine Architekturphase, direkt umgesetzt | Kleine Änderung an Maske und Prompt, keine Daten und keine neue Route | 2026-10-21 |
| Die Lage („schon eine Antwort von uns“) wird beim Aufbau des Formulars aus dem geladenen Ticket bestimmt | Das Ticket liegt dort schon vor, keine zusätzliche Abfrage | 2026-10-21 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)
_Nicht nötig, siehe Technical Decisions._

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
