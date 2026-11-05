# PROJ-74: Einführungsmodus für Infotexte

## Status: In Progress
**Created:** 2026-11-05
**Last Updated:** 2026-11-05

## Dependencies
- Requires: PROJ-40 (Formular „Vorschlag erstellen“, Erweitert-Modus), PROJ-10 (Ergebnisansicht), PROJ-42 (Hilfe für „Zusätzliche Informationen“)

## User Stories
- Als Stammkraft möchte ich Hilfetexte und Erklärungen ausblenden, damit die Seite im Alltag nur das Nötige zeigt.
- Als Aushilfe oder neue Kollegin möchte ich einen Schalter „Einführung“ einschalten, damit ich die Hilfetexte und Beispiele sehe.
- Als Kundenservice-Mitarbeiter möchte ich, dass wichtige Warnungen immer sichtbar bleiben, auch wenn die Einführung aus ist.

## Out of Scope
- Tooltips mit Fragezeichen (Variante e2, bewusst verworfen)
- Texte ganz löschen (Variante e1, bewusst verworfen, Aushilfen brauchen die Hilfe)
- Änderungen am Erweitert-Modus; beide Schalter sind unabhängig
- Speichern pro Benutzer in der Datenbank (Einstellung gilt pro Browser, wie „Erweitert“)
- Inhalte der Hilfetexte neu schreiben

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

- [ ] Angenommen ein Mitarbeiter öffnet die App zum ersten Mal, wenn er eine Seite öffnet, dann ist „Einführung“ aus und es erscheinen keine reinen Infotexte.
- [ ] Angenommen „Einführung“ ist aus, wenn eine Warnung mit Handlungsbedarf anfällt (fehlendes Produkt, Wissenslücke, Hinweis zur Regelprüfung, Hinweis auf neue Nachrichten), dann bleibt sie sichtbar.
- [ ] Angenommen der Mitarbeiter schaltet „Einführung“ in der Kopfleiste ein, wenn die Seite neu lädt, dann sind die Infotexte sichtbar: Hilfe und Beispiele zu „Zusätzliche Informationen“ (inklusive „So schreibst du es“), Hinweise zu Kundengruppe und Produkt, die Erklärung zu Wissenslücken, die Hinweise „Kein Antwortentwurf angefordert“ und „Noch keine Antwort“, Erklärtexte auf Frage-stellen- und Ticketseiten.
- [ ] Angenommen der Mitarbeiter hat „Einführung“ eingeschaltet, wenn er den Browser schließt und neu öffnet, dann bleibt die Einstellung erhalten (wie „Erweitert“).
- [ ] Angenommen „Einführung“ und „Erweitert“ sind unterschiedlich gesetzt, wenn eine Seite lädt, dann wirken beide unabhängig: Einführung blendet Infotexte ein, Erweitert die technischen Blöcke.
- [ ] Angenommen der Schalter „Einführung“ steht in der Kopfleiste, wenn er per Tastatur bedient wird, dann ist er wie „Erweitert“ ein beschrifteter Schalter (`role="switch"`) mit Fokusanzeige.

## Edge Cases
- Texte, die zugleich Warnung und Erklärung sind (Wissenslücken-Alert): Die Warnung bleibt, die lange Erklärung erscheint nur in der Einführung.
- Leere Zustände ohne Daten (zum Beispiel keine Bestellung): Der Satz, der den nächsten Schritt nennt, bleibt sichtbar, wenn ohne ihn nicht klar wäre, wie es weitergeht.
- Cookies blockiert: Die Seite bleibt benutzbar, „Einführung“ ist aus.
- Mobile Breite: Der Schalter darf die Kopfleiste nicht brechen.

## Technical Requirements (optional)
- Wiederverwendung: Mechanik wie beim Erweitert-Modus (Cookie, Schalter in der Kopfleiste).

## Open Questions
- [ ] Soll „Einführung“ für neue Browser ein paar Tage automatisch an sein?

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Eigener Schalter „Einführung“ statt Tooltips oder Löschen | Aushilfen sind Zielgruppe und brauchen die Hilfe; Stammkräfte sollen sie nicht sehen | 2026-11-05 |
| Standard aus | Im Alltag ist weniger besser; Einsteiger schalten bewusst ein | 2026-11-05 |
| Warnungen immer sichtbar | Handlungsrelevante Hinweise dürfen nicht vom Modus abhängen | 2026-11-05 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|

---
<!-- Sections below are added by subsequent skills -->

## Implementation Notes (2026-11-05)
- Neuer Schalter „Einführung“ in der Kopfleiste neben „Erweitert“ (Cookie `intro_mode`, Standard aus, Name in `config/staff.php`). Mechanik wie beim Erweitert-Modus: `App\Staff\IntroMode`, `IntroModeController`, `SwitchIntroModeRequest`, Route `POST /einfuehrung` (`intro-mode.switch`), `$introModeActive` für das Layout.
- Neue Komponente `<x-intro>`: zeigt ihren Inhalt nur im Einführungsmodus (fragt den Modus selbst ab, braucht keine durchgereichte Eigenschaft).
- Nur in der Einführung: Hilfetext unter „Zusätzliche Informationen“ und „So schreibst du es“, Erklärung zur DHL-Option, Hinweis „Um welches Produkt geht es?“ und Erklärung zum Wissensbereich im Formular, die Klammererklärung im Block „Wissenslücken“, Einleitung und Hinweis zum Antwortentwurf auf der Seite „Frage stellen“.
- Bleibt immer sichtbar: die Beispiele im Textfeld selbst (Platzhalter), der Datenschutzhinweis am Feld „Deine Frage oder Notiz“, alle Warnungen mit Handlungsbedarf (fehlendes Produkt, Wissenslücke, Hinweis zu nicht berücksichtigten Regeln, neue Nachrichten), „Kein Antwortentwurf angefordert“ und „Noch keine Antwort“ (sie nennen den nächsten Schritt).
- Tests: neuer `PROJ-74-IntroModeTest`; PROJ-9, PROJ-38, PROJ-59 auf den Einführungsmodus angepasst. Alle Tests zu Ticket-, Frage- und Analyseseiten grün bis auf den bekannten PROJ-56-Fehler.
- Nicht umgesetzt: Erklärtexte auf der Startseite und in den Einstellungen (dort stehen sie als Teil der Bedienung); bei Bedarf in einer Folgeänderung.


## Tech Design (Solution Architect)
_To be added by /architecture_

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
