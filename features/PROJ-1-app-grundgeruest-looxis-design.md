# PROJ-1: App-Grundgerüst mit LOOXIS-Design

## Status: Planned
**Created:** 2026-10-02
**Last Updated:** 2026-10-02

## Dependencies
- None

Grundlage ist `docs/design-system.md` (LOOXIS Design System). Alle Farb-, Schrift-, Radius-, Schatten- und Abstandswerte sowie die Klassenangaben der Komponenten gelten von dort und werden hier nicht wiederholt.

## User Stories
- Als Kundenservice-Mitarbeiter möchte ich eine aufgeräumte, gut lesbare Oberfläche im vertrauten LOOXIS-Look, damit ich mich ohne Einarbeitung zurechtfinde.
- Als Aushilfe möchte ich nach dem Öffnen der App direkt auf dem Arbeitsbildschirm „Ticket analysieren" landen, damit ich nicht erst suchen muss, wo ich anfange.
- Als Mitarbeiter möchte ich bei länger laufenden Vorgängen eine eindeutige Wartemeldung sehen, damit ich weiß, dass die App arbeitet und nicht hängt.
- Als Mitarbeiter möchte ich Erfolgs-, Warn- und Fehlermeldungen immer an derselben Stelle und im selben Stil sehen, damit ich sie nicht übersehe.
- Als Entwickler möchte ich fertige Basis-Komponenten und eine Übersicht aller Varianten, damit die folgenden Features einheitlich und ohne eigene Stildefinitionen gebaut werden.
- Als Mitarbeiter an einem Arbeitsplatz ohne Internetzugang möchte ich, dass die App vollständig mit ihren Schriften und Icons dargestellt wird, damit sie im internen Netz überall gleich aussieht.

## Out of Scope
- Nutzerauswahl und Anzeige des gewählten Nutzers – PROJ-5. PROJ-1 hält dafür nur einen Platz rechts in der Topbar frei.
- Ticket-Eingabe, Ticketanzeige und alle fachlichen Inhalte der Startseite – PROJ-6 und folgende. Die Startseite zeigt nur einen Platzhalter.
- Komponenten Modal, Toggle, Tabelle und Unternavigation – entstehen mit dem ersten Feature, das sie braucht (voraussichtlich PROJ-14 bzw. PROJ-15).
- Login, Registrierung, Benutzerverwaltung – PROJ-15.
- Gemeinsames Passwort vor der App – Webserver-Konfiguration in `/deploy`.
- Mobile Navigation (Menü unter 768 px) – die App ist ein Desktop-Werkzeug; solange es nur einen Navigationspunkt gibt, wird sie nicht gebraucht.
- Dark Mode – das Design System ist ausschließlich hell definiert.
- Mehrsprachige Oberfläche – die Oberfläche ist deutsch.
- Komponenten-Übersicht auf dem Produktivserver.

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Layout-Shell
- [ ] Angenommen die App läuft, wenn ein Nutzer die Startadresse `/` aufruft, dann sieht er die Seite „Ticket analysieren" im Layout mit Sidebar links und Topbar oben statt der Laravel-Willkommensseite.
- [ ] Angenommen das Browserfenster ist mindestens 768 px breit, wenn eine Seite angezeigt wird, dann ist die Sidebar 250 px breit, weiß, mit Trennlinie rechts, und zeigt oben den App-Namen „Customer Service Assist" in der Display-Schrift.
- [ ] Angenommen die Sidebar ist sichtbar, wenn die Startseite angezeigt wird, dann enthält die Navigation genau einen Punkt „Ticket analysieren" mit Icon, der als aktiv hervorgehoben ist (getönter Brand-Hintergrund, dunkler Brand-Text).
- [ ] Angenommen die Sidebar ist sichtbar, wenn eine Seite angezeigt wird, dann steht im Sidebar-Fuß die Versionsangabe der App.
- [ ] Angenommen eine Seite wird angezeigt, wenn der Nutzer die Topbar betrachtet, dann ist sie 60 px hoch, zeigt links den Seitentitel und hält rechts einen Bereich für die spätere Nutzeranzeige frei.
- [ ] Angenommen eine Seite wird angezeigt, wenn der Inhalt länger als der Bildschirm ist, dann scrollt nur der Inhaltsbereich und Sidebar und Topbar bleiben sichtbar.
- [ ] Angenommen das Browserfenster ist schmaler als 768 px, wenn eine Seite angezeigt wird, dann ist die Sidebar ausgeblendet, der Inhalt nutzt die volle Breite und es entsteht kein horizontales Scrollen.
- [ ] Angenommen eine Seite wird angezeigt, wenn der Nutzer den Browser-Tab betrachtet, dann lautet der Titel „<Seitentitel> – Customer Service Assist" und die Seite ist als deutschsprachig ausgezeichnet.

### Design-Tokens und Schriften
- [ ] Angenommen die Tokens sind eingerichtet, wenn eine Komponente eine Token-Klasse aus dem Design System verwendet (z. B. `bg-brand`, `text-slate-500`, `rounded-pill`, `shadow-1`), dann wird genau der im Design System angegebene Wert dargestellt.
- [ ] Angenommen eine Seite wird angezeigt, wenn der Nutzer Überschriften, Fließtext und technische Werte betrachtet, dann erscheinen sie in Bricolage Grotesque, Manrope bzw. JetBrains Mono.
- [ ] Angenommen der Arbeitsplatz hat keinen Internetzugang, wenn eine Seite geladen wird, dann werden Schriften, Icons und Styles vollständig dargestellt und es erfolgt kein Abruf von einem fremden Server.
- [ ] Angenommen eine Seite wird angezeigt, wenn der Nutzer Text markiert oder ein Formularfeld fokussiert, dann erscheinen die Markierung im hellen Brand-Ton und am Feld ein einzelner 2-px-Ring in Brand-Farbe ohne zusätzlichen Browser-Rahmen.

### Basis-Komponenten
- [ ] Angenommen die Komponenten-Übersicht ist geöffnet, wenn der Nutzer sie durchsieht, dann findet er Karte, Button, Input, Textarea, Select, Badge, Alert, Icon-Set und Lade-Overlay jeweils mit allen Varianten.
- [ ] Angenommen ein Button wird angezeigt, wenn er als `primary`, `secondary` oder `danger` verwendet wird, dann entspricht er der jeweiligen Variante des Design Systems inklusive Hover-Zustand.
- [ ] Angenommen ein Button ist deaktiviert, wenn der Nutzer darauf klickt, dann wird keine Aktion ausgelöst und der Button ist abgeschwächt mit „nicht erlaubt"-Mauszeiger dargestellt.
- [ ] Angenommen ein Formularfeld hat Label und Hinweistext, wenn es ohne Fehler angezeigt wird, dann steht das Label darüber und der Hinweis darunter in Sekundärfarbe.
- [ ] Angenommen ein Formularfeld hat einen Validierungsfehler, wenn es angezeigt wird, dann hat es einen roten Ring, die Fehlermeldung steht darunter und der Hinweistext ist ausgeblendet.
- [ ] Angenommen ein Formularfeld ist als Pflichtfeld markiert, wenn es angezeigt wird, dann trägt das Label einen roten Stern.
- [ ] Angenommen ein Badge oder Alert wird angezeigt, wenn er in den Tönen neutral/info, Erfolg, Warnung oder Fehler verwendet wird, dann nutzt er das getönte Statusmuster (10 % Fläche, 20 % Ring, volle Textfarbe).
- [ ] Angenommen ein Icon wird über seinen Namen eingebunden, wenn es angezeigt wird, dann erscheint das passende der 27 Icons in 18 px, übernimmt die Textfarbe seiner Umgebung und lässt sich in Größe und Strichstärke anpassen.
- [ ] Angenommen das Lade-Overlay ist aktiv, wenn der Nutzer auf die abgedunkelte Fläche klickt oder Escape drückt, dann bleibt es geöffnet, zeigt Spinner, Titel und optionalen Text und wird Screenreadern als Meldung angekündigt.

### Seiten
- [ ] Angenommen die Startseite ist geöffnet, wenn noch keine Ticket-Funktion existiert, dann zeigt sie eine Karte mit einem kurzen Hinweis, dass hier künftig Tickets analysiert werden.
- [ ] Angenommen die App läuft in der lokalen Entwicklungsumgebung, wenn der Nutzer die Adresse der Komponenten-Übersicht aufruft, dann wird sie im Layout angezeigt.
- [ ] Angenommen die App läuft produktiv, wenn jemand die Adresse der Komponenten-Übersicht aufruft, dann erhält er die Seite „Nicht gefunden".
- [ ] Angenommen eine Adresse existiert nicht, wenn der Nutzer sie aufruft, dann sieht er eine deutschsprachige „Seite nicht gefunden"-Meldung im LOOXIS-Design mit einem Link zur Startseite.
- [ ] Angenommen auf dem Server tritt ein unerwarteter Fehler auf, wenn der Nutzer die Seite lädt, dann sieht er eine deutschsprachige Fehlermeldung im LOOXIS-Design ohne technische Details.

## Edge Cases
- **Unbekannter Icon-Name:** Die Seite bricht nicht ab. In der lokalen Entwicklung ist der Fehler erkennbar, produktiv wird an der Stelle nichts angezeigt.
- **Sehr langer Seitentitel:** Der Titel in der Topbar wird mit Auslassungspunkten gekürzt und schiebt den Nutzerbereich rechts nicht aus dem Bild.
- **Schriftdatei lädt nicht:** Text bleibt in der Ersatzschrift (`system-ui` bzw. `monospace`) lesbar, das Layout verschiebt sich nicht wesentlich.
- **Sehr langer Text in Badge, Button oder Alert:** Badges und Buttons bleiben einzeilig, Alerts brechen um; nichts ragt aus der Karte heraus.
- **Langer Inhalt ohne Leerzeichen** (z. B. eine URL in einer Karte): bricht um, statt horizontales Scrollen auszulösen.
- **Fensterbreite genau an der Grenze (768 px):** Die Sidebar ist sichtbar; darunter ausgeblendet.
- **Tastaturbedienung:** Navigationspunkt, Buttons und Felder sind per Tab erreichbar und zeigen einen sichtbaren Fokus.
- **Abgeschaltetes JavaScript:** Layout, Navigation und statische Komponenten werden trotzdem korrekt dargestellt.

## Technical Requirements (optional)
- Browser: aktuelle Versionen von Chrome, Edge und Firefox auf dem Desktop.
- Zielbreiten: 1440 px (Hauptfall), 768 px (Tablet), 375 px (keine Darstellungsfehler, aber kein optimierter Arbeitsablauf).
- Keine Abrufe von Drittservern beim Seitenaufbau.
- Barrierefreiheit: Textkontrast und Fokus-Sichtbarkeit wie im Design System; Lade-Overlay mit `role="alert"`.
- Die Tokens des Design Systems sind für Tailwind v3 notiert und werden auf Tailwind v4 übertragen (Projektvorgabe aus dem PRD).

## Open Questions
- [ ] Woher kommt die Versionsangabe im Sidebar-Fuß (feste Nummer, Git-Stand, Datum des Deployments)? Zu klären in `/architecture`.
- [ ] `slate-500` (#8a8f9a) auf Weiß erreicht für kleinen Sekundärtext nicht den üblichen Mindestkontrast. Wert unverändert aus dem Content Studio übernehmen oder für diese App nachschärfen?

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Sidebar-Layout wie im Design System, obwohl der MVP nur einen Navigationspunkt hat | Einheitlich mit dem Content Studio; spätere Bereiche (Analysen-Übersicht, Admin) brauchen keinen Umbau | 2026-10-02 |
| Komponentenumfang auf den MVP-Satz begrenzt (Karte, Button, Input, Textarea, Select, Badge, Alert, Icons, Lade-Overlay) | Termin Mitte November; Modal, Toggle, Tabelle und Unternavigation kommen im MVP nicht vor | 2026-10-02 |
| Schriften werden von der App selbst ausgeliefert, nicht von Google Fonts | Funktioniert im internen Netz ohne Internetzugang; keine Übertragung von Mitarbeiter-IP-Adressen an Google | 2026-10-02 |
| Komponenten-Übersicht als eigene Seite, nur in der lokalen Entwicklung erreichbar | Macht das Grundgerüst abnehmbar und dient als Nachschlagewerk, ohne dem Kundenservice einen nutzlosen Menüpunkt zu zeigen | 2026-10-02 |
| Startseite ist bereits „Ticket analysieren" mit Platzhalter | Der Arbeitsbildschirm bekommt von Anfang an seine endgültige Adresse und seinen Navigationspunkt | 2026-10-02 |
| Keine mobile Navigation | Desktop-Werkzeug mit einem einzigen Navigationspunkt; das Design System blendet die Sidebar unter 768 px aus | 2026-10-02 |
| Eigene deutschsprachige Fehlerseiten (nicht gefunden, Serverfehler) im Grundgerüst | Aushilfen sollen nie auf eine englische Framework-Fehlerseite stoßen | 2026-10-02 |
| Oberfläche ausschließlich deutsch | Vorgabe aus dem PRD | 2026-10-02 |

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
