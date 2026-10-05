# PROJ-26: Mobiles Menü (Burger-Menü)

## Status: Planned
**Created:** 2026-10-05
**Last Updated:** 2026-10-05

## Dependencies
- Requires: PROJ-1 (App-Grundgerüst mit LOOXIS-Design) – Layout, Kopfleiste, Seitenleiste, Icon-Set

## User Stories
- Als Mitarbeiter am Handy oder schmalen Tablet möchte ich zwischen „Ticket analysieren", „Knowledge" und „Über die App" wechseln können, damit ich die App auch ohne Seitenleiste bedienen kann.
- Als Aushilfe möchte ich das Menü am bekannten Symbol mit drei Strichen erkennen, damit ich nicht suchen muss.
- Als Nutzer möchte ich das Menü auf die übliche Weise wieder schließen können (Schließen-Symbol, Tippen daneben, Escape-Taste), damit es mich nicht blockiert.
- Als Tastatur- oder Screenreader-Nutzer möchte ich das Menü vollständig bedienen können, damit die App barrierearm bleibt.
- Als Entwickler möchte ich, dass Seitenleiste und Menü immer dieselben Einträge zeigen, damit ein neuer Eintrag nicht an zwei Stellen gepflegt werden muss.

## Out of Scope
- Änderungen an der Seitenleiste auf dem Desktop; sie bleibt wie in PROJ-1.
- Weitere Einträge oder Unterpunkte; das Menü zeigt die Einträge, die es in der Seitenleiste gibt.
- Nutzerauswahl im Menü – PROJ-5 entscheidet, wo der gewählte Nutzer erscheint.
- Allgemeine Überarbeitung der Seiten für schmale Bildschirme (Tabellen, Formulare); jede Seite verantwortet ihre eigene Darstellung.
- Wischgesten zum Öffnen oder Schließen.
- Eigene App für Mobilgeräte oder Installation als Web-App.

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Sichtbarkeit
- [ ] Angenommen das Fenster ist schmaler als 768 px (dort blendet PROJ-1 die Seitenleiste aus), wenn eine Seite angezeigt wird, dann steht rechts in der Kopfleiste ein Menü-Symbol mit drei waagrechten Strichen.
- [ ] Angenommen das Fenster ist 768 px breit oder breiter, wenn eine Seite angezeigt wird, dann ist das Menü-Symbol nicht zu sehen und die Seitenleiste bleibt unverändert.
- [ ] Angenommen die Kopfleiste zeigt zusätzlich Inhalte rechts (z. B. später den gewählten Nutzer), wenn das Menü-Symbol erscheint, dann steht es ganz rechts und verdrängt den Seitentitel nicht; ein langer Titel wird gekürzt.

### Öffnen und Inhalt
- [ ] Angenommen das Menü ist geschlossen, wenn der Nutzer auf das Menü-Symbol tippt, dann öffnet sich eine Menüfläche über der Seite, und der Seiteninhalt dahinter wird abgedunkelt.
- [ ] Angenommen das Menü ist offen, wenn der Nutzer es betrachtet, dann zeigt es den App-Namen, dieselben Einträge wie die Seitenleiste in derselben Reihenfolge und Gruppierung („Ticket analysieren", „Knowledge", abgesetzt „Über die App") mit Icons sowie die Versionsangabe.
- [ ] Angenommen der Nutzer ist auf einer Seite, wenn er das Menü öffnet, dann ist der Eintrag dieser Seite wie in der Seitenleiste als aktiv markiert.
- [ ] Angenommen ein neuer Eintrag kommt in die Seitenleiste, wenn das Menü geöffnet wird, dann erscheint er dort ebenfalls, ohne dass das Menü eigens angepasst wird.
- [ ] Angenommen das Menü ist offen, wenn der Nutzer auf der Seite dahinter scrollen will, dann bewegt sich der Seiteninhalt nicht.

### Schließen
- [ ] Angenommen das Menü ist offen, wenn der Nutzer auf einen Eintrag tippt, dann öffnet sich die Seite, und das Menü ist geschlossen.
- [ ] Angenommen das Menü ist offen, wenn der Nutzer auf das Schließen-Symbol tippt, neben die Menüfläche tippt oder die Escape-Taste drückt, dann schließt sich das Menü, und der Nutzer bleibt auf der aktuellen Seite.
- [ ] Angenommen das Menü ist offen, wenn das Fenster auf 768 px oder breiter vergrößert wird, dann schließt sich das Menü, und die Seitenleiste ist sichtbar.

### Bedienbarkeit
- [ ] Angenommen ein Tastaturnutzer öffnet das Menü, wenn es erscheint, dann liegt der Fokus im Menü, die Tab-Taste bleibt innerhalb des Menüs, und nach dem Schließen liegt der Fokus wieder auf dem Menü-Symbol.
- [ ] Angenommen ein Screenreader liest die Kopfleiste, wenn er das Menü-Symbol erreicht, dann wird es als „Menü öffnen" angesagt, einschließlich des Zustands geöffnet oder geschlossen.
- [ ] Angenommen das Menü-Symbol oder ein Eintrag wird angetippt, wenn der Nutzer einen Finger verwendet, dann ist die Tippfläche mindestens 44 × 44 px groß.

## Edge Cases
- **JavaScript lädt nicht:** Das Menü-Symbol funktioniert nicht; die Seite bleibt lesbar. Die Startseite ist über den App-Namen in der Kopfleiste oder die Adresse erreichbar. Kein weiterer Ausweichweg im MVP.
- **Querformat am Handy mit mehr als 768 px Breite:** Es gilt die Desktop-Darstellung mit Seitenleiste.
- **Doppeltes Antippen des Menü-Symbols:** Das Menü öffnet und schließt sich, es entstehen nicht zwei Menüflächen.
- **Lade-Overlay aktiv** (z. B. künftig während einer Analyse): Das Lade-Overlay liegt über dem Menü; das Menü lässt sich währenddessen nicht öffnen.
- **Seite mit Fehlermeldung (404, 500):** Diese Seiten nutzen ein eigenes Layout ohne Navigation (PROJ-1) und bekommen kein Menü.
- **Sehr kleine Höhe:** Passen die Einträge nicht auf den Bildschirm, lässt sich die Menüfläche selbst scrollen.

## Technical Requirements (optional)
- Breakpoint identisch mit dem Ausblenden der Seitenleiste in PROJ-1 (768 px, Tailwind `md`).
- Gestaltung nach `docs/design-system.md` (Farben der aktiven Zeile, Icons, Abstände wie in der Seitenleiste).
- Browser: aktuelle Versionen von Chrome und Safari auf Mobilgeräten, zusätzlich die Desktop-Browser aus PROJ-1 im schmalen Fenster.

## Open Questions
- [ ] Soll die Menüfläche von rechts hereingleiten (zum Symbol passend) oder den ganzen Bildschirm füllen? Vorschlag: von rechts, maximal so breit wie die Seitenleiste (250 px), auf sehr schmalen Geräten fast volle Breite. In `/architecture` oder `/frontend` festlegen.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Priorität P1 | Die Bearbeitung läuft am Desktop neben Zammad; am Handy wird vor allem nachgelesen. Klein und unabhängig, kann jederzeit dazwischen gebaut werden | 2026-10-05 |
| Burger-Symbol rechts in der Kopfleiste | Allgemein bekanntes Muster, vom Nutzer so gewünscht | 2026-10-05 |
| Menü nur unter 768 px | Genau dort fehlt heute jede Navigation; der Desktop bleibt unverändert | 2026-10-05 |
| Gleiche Einträge, Reihenfolge und Gruppierung wie die Seitenleiste | Kein Umlernen zwischen Geräten; neue Einträge erscheinen automatisch an beiden Stellen | 2026-10-05 |
| Schließen per Eintrag, Schließen-Symbol, Tippen daneben und Escape | Übliche Erwartung an ein Menü-Overlay | 2026-10-05 |
| Fokusführung und Ansage für Screenreader als Akzeptanzkriterium | Barrierearme Bedienung wie im übrigen Grundgerüst | 2026-10-05 |

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
