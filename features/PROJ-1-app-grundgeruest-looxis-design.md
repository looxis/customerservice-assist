# PROJ-1: App-Grundgerüst mit LOOXIS-Design

## Status: In Progress
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
- [x] Woher kommt die Versionsangabe im Sidebar-Fuß? → Git-Stand beim Deployment (Kurz-Hash und Datum), siehe Technical Decisions.
- [x] Kontrast von `slate-500` (#8a8f9a, ca. 3,2:1 auf Weiß)? → Sekundärtext verwendet in dieser App `slate-600` (ca. 4,9:1); die Palette bleibt unverändert.

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
| Reines Frontend-Feature: keine Datenbanktabelle, kein Controller mit Logik | Das Grundgerüst zeigt nur statische Seiten; Daten entstehen erst ab PROJ-5/PROJ-6 | 2026-10-02 |
| Web-Routen, die direkt eine Blade-Ansicht zurückgeben | Beide Seiten sind vollständige HTML-Seiten ohne Eingaben; ein JSON-Client existiert nicht | 2026-10-02 |
| Ein gemeinsames Seitenlayout als Blade-Komponente, Sidebar und Topbar als eigene Teile | Jede spätere Seite bekommt die Shell durch eine einzige Zeile; Änderungen an Navigation oder Topbar passieren an genau einer Stelle | 2026-10-02 |
| Design-Tokens CSS-first im Tailwind-v4-Theme (`resources/css/app.css`), nicht in einer Konfigurationsdatei | Projektvorgabe; die v3-Notation aus `docs/design-system.md` wird einmalig übertragen | 2026-10-02 |
| Tailwinds Standardfarben werden abgeschaltet, nur die LOOXIS-Palette ist verfügbar | Erzwingt „Tokens statt Hex-Werte": Eine Klasse wie `bg-indigo-600` existiert schlicht nicht und kann sich nicht einschleichen | 2026-10-02 |
| Schriften als Fontsource-Pakete, vom Build-Werkzeug mit ausgeliefert | Selbst gehostet ohne Handarbeit mit Schriftdateien; Updates über die normale Paketverwaltung; kein Abruf bei Google | 2026-10-02 |
| Icons als eine einzige Blade-Komponente mit eingebetteten SVG-Pfaden | Wie im Content Studio; keine Icon-Bibliothek, kein zusätzlicher Download, Farbe folgt dem Text | 2026-10-02 |
| Formularfelder teilen sich eine gemeinsame Hülle für Label, Pflichtstern, Hinweis und Fehlermeldung | Input, Textarea und Select verhalten sich garantiert gleich; die Fehlerdarstellung wird einmal gebaut | 2026-10-02 |
| Felder zeigen Validierungsfehler automatisch anhand ihres Feldnamens | Passt zu Laravel Form Requests (Projektkonvention): spätere Formulare brauchen keine eigene Fehlerlogik | 2026-10-02 |
| Lade-Overlay als Alpine.js-Baustein, der über ein Seitenereignis ein- und ausgeschaltet wird | Jedes spätere Formular (Ticket laden, Analyse starten) kann es ohne eigene Kopie auslösen | 2026-10-02 |
| Komponenten-Übersicht wird nur in der lokalen Umgebung als Route registriert | Produktiv existiert die Adresse nicht und liefert von selbst „Nicht gefunden"; kein Schalter, der vergessen werden kann | 2026-10-02 |
| Fehlerseiten nutzen ein eigenes, schlankes Layout ohne Sidebar | Eine Fehlerseite darf nicht von Teilen abhängen, die selbst den Fehler verursacht haben könnten | 2026-10-02 |
| Versionsangabe = Git-Kurz-Hash und Datum, beim Deployment einmal ermittelt und als Konfigurationswert hinterlegt; lokal steht „dev" | Pflegefrei und eindeutig; kein Git-Aufruf bei jedem Seitenaufruf. Das Schreiben des Werts ist Aufgabe von `/deploy` | 2026-10-02 |
| Sekundärtext, Feld-Hinweise und Tabellenköpfe in `slate-600` statt `slate-500` | `slate-500` erreicht nur ca. 3,2:1 Kontrast auf Weiß, `slate-600` ca. 4,9:1; Abweichung ist in `docs/design-system.md` vermerkt | 2026-10-02 |
| App-Sprache auf Deutsch gestellt | Seitenauszeichnung, Fehlerseiten und spätere Validierungsmeldungen erscheinen deutsch | 2026-10-02 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

### Überblick
PROJ-1 ist ein reines Oberflächen-Feature. Es entstehen ein Seitenlayout, neun wiederverwendbare Bausteine, zwei Seiten und zwei Fehlerseiten. Es gibt keine Datenbank, keine Formularverarbeitung und keine Anbindung an andere Systeme. Ein `/backend`-Durchlauf ist für dieses Feature nicht nötig.

### A) Aufbau der Oberfläche

```
Seitenlayout (für alle normalen Seiten)
+-- Sidebar (ab 768 px sichtbar)
|   +-- Kopf: App-Name
|   +-- Navigation
|   |   +-- Navigationspunkt „Ticket analysieren" (aktiv hervorgehoben)
|   +-- Fuß: Versionsangabe
+-- Hauptbereich
    +-- Topbar
    |   +-- Seitentitel (links, wird bei Überlänge gekürzt)
    |   +-- Platz für Nutzeranzeige (rechts, leer bis PROJ-5)
    +-- Inhaltsbereich (scrollt, zentriert, Breite je Seite wählbar)
    |   +-- Meldungsbereich (Alerts nach Aktionen)
    |   +-- Seiteninhalt
    +-- Lade-Overlay (unsichtbar, bis es ausgelöst wird)

Seite „Ticket analysieren" (Startseite)
+-- Karte mit Platzhalterhinweis

Seite „Komponenten-Übersicht" (nur lokal)
+-- Abschnitt je Baustein mit allen Varianten und Zuständen
    +-- Farben und Schriften
    +-- Karte
    +-- Buttons (primary, secondary, danger, deaktiviert, mit Icon)
    +-- Formularfelder (normal, mit Hinweis, Pflichtfeld, Fehler)
    +-- Badges (vier Töne, kompakt)
    +-- Alerts (vier Typen)
    +-- Icon-Set (alle 27)
    +-- Lade-Overlay (Button zum Auslösen)

Fehler-Layout (schlank, ohne Sidebar)
+-- Seite „Nicht gefunden" mit Link zur Startseite
+-- Seite „Fehler auf dem Server"
```

**Wiederverwendbare Bausteine:** Seitenlayout, Navigationspunkt, Karte, Button, Input, Textarea, Select, Feld-Hülle (Label, Pflichtstern, Hinweis, Fehlermeldung), Badge, Alert, Icon, Lade-Overlay.

Im Repository existieren bisher keine Bausteine; nur die Laravel-Willkommensseite, die durch die Startseite ersetzt wird.

### B) Daten
Es wird nichts gespeichert. Die einzigen „Daten" sind zwei Konfigurationswerte:

- **App-Name** – bereits vorhanden („Customer Service Assist"), erscheint in Sidebar und Browser-Titel.
- **Versionsangabe** – Kurz-Hash und Datum des ausgelieferten Standes. Wird beim Deployment einmal gesetzt; in der lokalen Entwicklung steht dort „dev".

### C) Wie die Seiten erreichbar sind

| Adresse | Seite | Verfügbar |
|---|---|---|
| `/` | Ticket analysieren | immer |
| `/styleguide` | Komponenten-Übersicht | nur lokale Entwicklung |
| jede unbekannte Adresse | Nicht gefunden | immer |

### D) Wichtigste Entscheidungen in Kürze
- **Nur LOOXIS-Farben verfügbar.** Tailwinds eigene Farbpalette wird abgeschaltet. Wer versehentlich eine Standardfarbe verwendet, sieht sofort, dass sie nicht wirkt.
- **Schriften kommen aus der App.** Sie werden als Pakete eingebunden und mit den übrigen Dateien ausgeliefert; es gibt keinen Abruf bei Google.
- **Formularfelder kennen ihre Fehler selbst.** Schlägt später eine Validierung fehl, zeigt das Feld Ring und Meldung von allein an.
- **Ein Lade-Overlay für alle.** Es sitzt im Layout und wird per Ereignis ausgelöst, statt in jedem Feature neu gebaut zu werden.
- **Fehlerseiten stehen für sich.** Sie verwenden ein eigenes, minimales Layout, damit sie auch dann funktionieren, wenn im normalen Layout etwas kaputt ist.
- **Sekundärtext ist eine Stufe dunkler** als im Content Studio (`slate-600`), wegen der Lesbarkeit.

### E) Automatische Tests (Umfang für `/qa`)
- Startseite antwortet, zeigt App-Name, Seitentitel und den aktiven Navigationspunkt; die Willkommensseite ist weg.
- Komponenten-Übersicht ist lokal erreichbar und in der Produktivumgebung nicht.
- Unbekannte Adresse liefert die deutsche „Nicht gefunden"-Seite mit Link zur Startseite.
- Bausteine: Button-Varianten und deaktivierter Zustand, Feld mit Fehler (Meldung sichtbar, Hinweis ausgeblendet), Pflichtstern, Badge- und Alert-Töne, unbekannter Icon-Name bricht die Seite nicht ab.
- Ausgelieferte Seite enthält keinen Verweis auf fremde Server.

Darstellung, Schriften, Fokus-Ringe und das Verhalten bei 375/768/1440 px werden in `/qa` im Browser geprüft.

### F) Abhängigkeiten (neue Pakete)
- `@fontsource-variable/bricolage-grotesque` – Display-Schrift
- `@fontsource-variable/manrope` – Fließtext-Schrift
- `@fontsource-variable/jetbrains-mono` – Schrift für technische Werte

Keine neuen PHP-Pakete. Alpine.js und Tailwind v4 sind bereits installiert.

### G) Übergaben an andere Features
- **PROJ-5** füllt den freien Bereich rechts in der Topbar.
- **PROJ-6** ersetzt den Platzhalter der Startseite.
- **`/deploy`** schreibt die Versionsangabe beim Ausliefern.

## Implementation Notes (Frontend)
**Stand:** 2026-10-02 – umgesetzt, Browser-Abnahme durch den Nutzer steht aus.

**Gebaut:**
- Design-Tokens im `@theme`-Block von `resources/css/app.css`; Tailwinds Standardfarben sind abgeschaltet (`--color-*: initial`), dazu die globalen Basis-Styles.
- Schriften über `@fontsource-variable/*` (drei Pakete), im Build als lokale `woff2`-Dateien ausgeliefert.
- Layouts: `components/layouts/app.blade.php` (Sidebar, Topbar, Inhaltsbereich, Meldungsbereich, Lade-Overlay) und `components/layouts/error.blade.php` (schlank, ohne Sidebar).
- Bausteine in `resources/views/components/`: `card`, `button`, `field`, `input`, `textarea`, `select`, `badge`, `alert`, `icon`, `nav-item`, `loading-overlay`.
- Seiten: `tickets/analyze.blade.php` (Startseite, Route `tickets.analyze`), `styleguide.blade.php` (Route `styleguide`, nur lokal registriert), `errors/404.blade.php`, `errors/500.blade.php`.
- `config/app.php`: neuer Wert `app.version` (aus `APP_VERSION`, Standard „dev"), Standard-Locale `de`.
- Die Laravel-Willkommensseite wurde entfernt.

**Verwendung für Folge-Features:**
- Seite: `<x-layouts.app title="…" width="3xl|5xl|6xl">`. Der Bereich rechts in der Topbar ist der benannte Slot `user` (für PROJ-5).
- Meldungen: Session-Werte `success` und `error` erscheinen automatisch als Alert oben im Inhalt.
- Felder: `<x-input name="…" label="…" hint="…" required>`; Fehler kommen automatisch aus der Validierung (`$errors`) oder über das Attribut `error`.
- Lade-Overlay: `$dispatch('loading-start', { title, text })` und `$dispatch('loading-stop')`.

**Abweichungen vom Spec / Design System:**
- Badge hat zusätzlich den Ton `danger` (das Design System nennt nur vier Töne; das Kriterium verlangt „Fehler").
- Buttons, Navigationspunkt und Links zeigen bei Tastaturfokus einen Brand-Umriss (im Design System nicht beschrieben, für das Kriterium „sichtbarer Fokus" nötig).
- Die Sprache der Seite ist fest als `de` ausgezeichnet, unabhängig von `APP_LOCALE`.
- Unbekannter Icon-Name: lokal ein roter Marker „?name", in allen anderen Umgebungen keine Ausgabe.

**Geprüft:** Build läuft durch; `/` und `/styleguide` antworten mit 200, eine unbekannte Adresse mit der deutschen 404-Seite; keine Verweise auf fremde Server; bestehende Tests grün. Nicht geprüft: Darstellung im Browser, Verhalten bei 375/768/1440 px, 500-Seite, Verhalten in der Produktivumgebung.

**Offen für den Nutzer:** `APP_VERSION` in `.env.example` dokumentieren und `APP_LOCALE=de` in `.env`/`.env.example` setzen (beide Dateien sind für den Assistenten gesperrt).

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
