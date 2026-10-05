# PROJ-25: Über die App

## Status: Approved
**Created:** 2026-10-05
**Last Updated:** 2026-10-05

## Dependencies
- Requires: PROJ-1 (App-Grundgerüst mit LOOXIS-Design) – Layout, Seitenleiste, Basis-Komponenten, Versionsangabe
- Requires: PROJ-3 (Knowledge Base einlesen und prüfen) – Wissensstand und Anzahl der Dokumente für den Info-Kasten

## User Stories
- Als Aushilfe möchte ich in wenigen Minuten verstehen, wofür die App da ist und was sie für mich tut, damit ich ihren Vorschlägen richtig begegne: prüfen statt blind übernehmen.
- Als Aushilfe möchte ich wissen, was die App bewusst nicht tut (nichts senden, nichts entscheiden, nichts an Bestellungen ändern), damit ich weiß, welche Schritte bei mir bleiben.
- Als Mitarbeiter möchte ich verstehen, was „Entwurfs-Wissen" und ein Ergebnis „unklar" bedeuten, damit ich beides richtig einordne.
- Als Mitarbeiter möchte ich bei einer Rückfrage sehen, auf welchem App- und Wissensstand ich gearbeitet habe, damit Fehler nachvollziehbar sind.
- Als Knowledge-Autor möchte ich wissen, wie neues Wissen in die App kommt, damit ich weiß, wo ich anfange.
- Als neuer Entwickler möchte ich auf einer Seite Tech Stack, Bausteine und Arbeitsweise sehen, damit ich mich schnell zurechtfinde.

## Out of Scope
- Bearbeiten des Textes in der App; der Text wird im Repository gepflegt.
- Automatisch erzeugte Liste aller Features mit Status aus `features/INDEX.md`; die Hinweise „in Arbeit" werden von Hand gepflegt.
- Ausführliche Auswahlregeln (Aufkleber, Obergrenzen, Rangfolge); die stehen im Authoring Guide und in PROJ-4.
- Kurzfassung des Authoring Guides in der App.
- Melden einer Wissenslücke von dieser Seite aus – PROJ-12.
- Navigation auf schmalen Bildschirmen; die Seitenleiste ist laut PROJ-1 nur auf dem Desktop sichtbar. Die Seite ist per Adresse trotzdem auch mobil lesbar.
- Mehrsprachigkeit; die Seite ist deutsch.
- Änderungsprotokoll oder Versionshistorie der App.

## Gliederung der Seite
1. **Wofür ist die App?** – Ziel in zwei bis drei Sätzen: ein fachlich begründeter Vorschlag zu einem Kundenservice-Ticket, damit Unternehmenswissen direkt in der Bearbeitung ankommt.
2. **So funktioniert es** – der geplante Ablauf in Schritten: Ticket laden, Bestellung laden, Kundengruppe und Produkt bestätigen, passendes Wissen auswählen, KI-Vorschlag, prüfen und anpassen, Feedback geben. Noch nicht verfügbare Schritte tragen den Hinweis „in Arbeit".
3. **Ein Beispiel** – ein durchgehender, fiktiver Fall (z. B. Zaubertasse auf Amazon, Thermoeffekt funktioniert angeblich nicht) in einfacher Sprache, ohne echte Kundendaten.
4. **Was die App bewusst nicht tut** – nichts senden, nichts entscheiden, keine Änderungen an Bestellungen, Erstattungen oder Gutschriften, keine Bildanalyse.
5. **Gut zu wissen** – „unklar" ist ein gewünschtes Ergebnis; Entwurfs-Wissen ist gekennzeichnet und darf kritisch betrachtet werden; jede Empfehlung nennt ihre Quellen.
6. **Wissen ergänzen** – kurz: Wissen liegt als Dateien im Repository, wird mit einer Chat-KI nach dem Authoring Guide verfasst und von einem Entwickler eingepflegt; Verweis auf die Knowledge-Seite.
7. **Aktueller Stand** (Info-Kasten) – App-Version, Wissensstand, Anzahl verwendbarer Knowledge-Dokumente, davon Entwürfe.
8. **Für Entwickler** – deutlich abgesetzt: Tech Stack, Bausteine der App, Arbeitsweise (Sail, Skills-Workflow, Feature-Specs) und wo im Repository was liegt.

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Navigation
- [ ] Angenommen eine beliebige Seite der App ist auf dem Desktop geöffnet, wenn der Nutzer die Seitenleiste betrachtet, dann steht unten, abgesetzt von den Arbeitsseiten, der Eintrag „Über die App" mit Info-Icon.
- [ ] Angenommen der Nutzer klickt auf „Über die App", wenn die Seite geladen ist, dann trägt sie den Titel „Über die App", und der Eintrag in der Seitenleiste ist als aktiv markiert.
- [ ] Angenommen der Nutzer ruft die Seite direkt über ihre Adresse auf, wenn sie lädt, dann wird sie ohne Anmeldung angezeigt, wie alle Seiten im MVP.

### Inhalt für alle
- [ ] Angenommen die Seite ist geöffnet, wenn der Nutzer von oben liest, dann folgen die Abschnitte der Gliederung oben in dieser Reihenfolge, jeweils mit Überschrift.
- [ ] Angenommen der Abschnitt „So funktioniert es" wird gelesen, wenn ein Schritt noch nicht nutzbar ist, dann trägt er sichtbar den Hinweis „in Arbeit"; nutzbare Schritte tragen keinen Hinweis.
- [ ] Angenommen der Abschnitt „Was die App bewusst nicht tut" wird gelesen, wenn der Nutzer ihn betrachtet, dann nennt er mindestens: kein Versand von Antworten, keine Entscheidung ohne Menschen, keine Änderungen an Bestellungen, Erstattungen oder Gutschriften.
- [ ] Angenommen der Abschnitt „Gut zu wissen" wird gelesen, wenn der Nutzer ihn betrachtet, dann erklärt er „unklar" als gewünschtes Ergebnis, die Kennzeichnung von Entwurfs-Wissen und den Verweis auf Quellen.
- [ ] Angenommen das Beispiel wird gelesen, wenn der Nutzer es betrachtet, dann ist es durchgehend fiktiv und enthält keine Namen, Adressen, Bestell- oder Ticketnummern.
- [ ] Angenommen der Abschnitt „Wissen ergänzen" wird gelesen, wenn der Nutzer auf den Verweis zur Knowledge-Seite klickt, dann öffnet sich die Knowledge-Übersicht (PROJ-24).
- [ ] Angenommen eine Aushilfe ohne Vorwissen liest den Teil für alle, wenn sie fertig ist, dann ist er in einfacher Sprache ohne Fachbegriffe aus der Entwicklung geschrieben; nötige Begriffe (Knowledge, Entwurfs-Wissen, Kundengruppe) werden beim ersten Vorkommen erklärt.

### Info-Kasten „Aktueller Stand"
- [ ] Angenommen die Seite ist geöffnet, wenn der Info-Kasten betrachtet wird, dann zeigt er die App-Version wie im Fuß der Seitenleiste.
- [ ] Angenommen die Knowledge Base ist lesbar, wenn der Info-Kasten betrachtet wird, dann zeigt er den Wissensstand (Commit und Datum) sowie die Anzahl verwendbarer Dokumente und davon die Anzahl der Entwürfe.
- [ ] Angenommen eine Knowledge-Datei wurde geändert, wenn die Seite neu geladen wird, dann zeigt der Info-Kasten die neuen Angaben ohne Neustart.

### Für Entwickler
- [ ] Angenommen die Seite ist geöffnet, wenn der Nutzer zum Teil „Für Entwickler" kommt, dann ist er optisch klar vom Teil für alle abgesetzt und als Entwicklerteil überschrieben.
- [ ] Angenommen der Entwicklerteil wird gelesen, wenn er betrachtet wird, dann nennt er den Tech Stack (Laravel, PHP, Blade, Tailwind CSS, Alpine.js, MySQL, Pest, Laravel Sail, Laravel Boost) mit je einem Satz zum Zweck.
- [ ] Angenommen der Entwicklerteil wird gelesen, wenn er betrachtet wird, dann beschreibt er die Bausteine der App (Knowledge-Bibliothek, Knowledge-Auswahl, später Zammad-, EOCS- und LLM-Anbindung) und den Arbeitsablauf mit Specs und Skills (`/write-spec` bis `/deploy`) sowie den Betrieb ausschließlich über Sail.
- [ ] Angenommen der Entwicklerteil wird gelesen, wenn er Fundorte nennt, dann verweist er auf `docs/PRD.md`, `features/INDEX.md`, `docs/KNOWLEDGE_AUTHORING_GUIDE.md`, `docs/KNOWLEDGE_BASE_DESIGN.md`, `docs/design-system.md` und das README.
- [ ] Angenommen der Entwicklerteil wird gelesen, wenn er betrachtet wird, dann enthält er keine Zugangsdaten, Server-Adressen, Ports oder Inhalte aus `.env`.

### Pflege
- [ ] Angenommen ein Feature macht einen Schritt des Ablaufs nutzbar, wenn dieses Feature abgenommen wird, dann ist der Hinweis „in Arbeit" bei diesem Schritt entfernt.

## Edge Cases
- **Knowledge-Ordner fehlt oder ist leer:** Der Info-Kasten zeigt „0 verwendbare Dokumente" und den Hinweis aus PROJ-3; die Seite lädt trotzdem vollständig.
- **Wissensstand unbekannt** (kein Git, kein hinterlegter Commit): Der Info-Kasten zeigt „unbekannt", wie die Knowledge-Seite.
- **Uncommittete Änderungen an der Knowledge Base:** Der Info-Kasten zeigt den Zusatz „mit uncommitteten Änderungen", wie die Knowledge-Seite.
- **Lokale Entwicklung:** Die App-Version lautet „dev".
- **Fehlerhafte Knowledge-Dateien:** Sie zählen nicht zu den verwendbaren Dokumenten; der Info-Kasten nennt keine Fehler, die stehen auf der Knowledge-Seite.
- **Schmaler Bildschirm:** Ohne Seitenleiste ist die Seite über ihre Adresse erreichbar und ohne waagrechtes Scrollen lesbar.
- **Text veraltet:** Ein Schritt ist nutzbar, trägt aber noch „in Arbeit" – das fällt bei der Abnahme des Features auf (siehe Pflege).

## Technical Requirements (optional)
- Ladezeit wie die übrigen Seiten; das Einlesen der Knowledge Base geschieht über die bestehende Bibliothek und nur einmal pro Aufruf.
- Browser: aktuelle Versionen von Chrome, Edge und Firefox auf dem Desktop (wie PROJ-1).
- Der Text wird im Repository gepflegt und ist ohne Programmierkenntnisse änderbar.

## Open Questions
- [x] Soll das Entfernen der Hinweise „in Arbeit" als fester Punkt in die Checkliste von `/qa` oder `/deploy` aufgenommen werden? → Ja, fester Punkt in der `/qa`-Checkliste (2026-10-05).

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Priorität P0 | Aushilfen brauchen zum Start vor dem Weihnachts-Peak eine Erklärung, was die App tut und was nicht; das Feature ist klein und unabhängig | 2026-10-05 |
| Eine Seite mit zwei Teilen (für alle, für Entwickler) statt Reitern | Ein Link, ein Ort; Aushilfen hören nach dem ersten Teil auf, Entwickler scrollen weiter | 2026-10-05 |
| Eintrag „Über die App" unten in der Seitenleiste | Oberfläche ist deutsch; abgesetzt von den Arbeitsseiten, weil man die Seite selten braucht | 2026-10-05 |
| Info-Kasten mit App-Version, Wissensstand und Dokumentanzahl | Hilft bei Rückfragen zum Stand, ohne Pflegeaufwand | 2026-10-05 |
| Ablauf als Zielbild mit Hinweis „in Arbeit" bei fehlenden Schritten | Zeigt von Anfang an, wohin die App geht, ohne bis November irreführend zu sein | 2026-10-05 |
| Hinweise „in Arbeit" von Hand gepflegt statt aus `features/INDEX.md` erzeugt | Ein Ablaufschritt entspricht nicht immer genau einem Feature; Text bleibt verständlich | 2026-10-05 |
| Erklärung in einfacher Sprache mit einem durchgehenden Beispiel | Ziel des PRD: Aushilfen arbeiten nach 1–2 Stunden Einführung; die „Mappe"-Erklärung kam gut an | 2026-10-05 |
| Kurzer Abschnitt für Knowledge-Autoren mit Verweisen statt Kurzfassung des Guides | Der Guide bleibt die einzige Quelle; keine doppelte Pflege | 2026-10-05 |
| Keine Zugangsdaten, Adressen oder Ports im Entwicklerteil | Die Seite ist für alle Nutzer sichtbar | 2026-10-05 |
| Entfernen der Hinweise „in Arbeit" ist fester Punkt der `/qa`-Checkliste | Die Abnahme eines Features ist der Moment, in dem ein Schritt nutzbar wird; so veraltet der Text nicht | 2026-10-05 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|
| Text in `docs/ABOUT.md` (Markdown), eine Datei für beide Teile | Ohne Programmierkenntnisse änderbar, auch im Repository lesbar, eine Quelle | 2026-10-05 |
| Trennung der Teile an der Überschrift „Für Entwickler" | Einfache, sichtbare Konvention; Info-Kasten sitzt dazwischen | 2026-10-05 |
| Bestehende sichere Markdown-Darstellung aus PROJ-24 wiederverwenden | Schutz gegen eingeschleustes HTML schon vorhanden und getestet | 2026-10-05 |
| Kennung `[in Arbeit]` wird als Badge dargestellt | Eine merkbare Kennung; im Rohtext verständlich | 2026-10-05 |
| Web-Route `/ueber-die-app`, Blade ohne Alpine.js | Reine Leseseite ohne Interaktion | 2026-10-05 |
| Fehlender oder leerer Text → Hinweis statt Fehlerseite | Ein Dokumentationsfehler soll die App nicht blockieren | 2026-10-05 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

### Überblick
Die Seite liest Text, schreibt nichts und braucht keine Datenbank. Der Text steht als **eine Markdown-Datei im Repository** (`docs/ABOUT.md`). Die App zeigt ihn mit derselben sicheren Markdown-Darstellung wie die Knowledge-Dokumente (PROJ-24). Den Info-Kasten füllt die App bei jedem Aufruf aus der bestehenden Knowledge-Bibliothek und der Versionsangabe aus PROJ-1.

### A) Bausteine
```
Seitenleiste (Layout aus PROJ-1)
+-- Arbeitsseiten: Ticket analysieren, Knowledge (unverändert)
+-- unten, abgesetzt: „Über die App" mit neuem Info-Icon (aktiv markiert auf der Seite)
+-- Fuß: Versionsangabe (unverändert)

Seite „Über die App" (/ueber-die-app)
+-- Teil für alle            <- docs/ABOUT.md bis zur Überschrift „Für Entwickler"
|   +-- Abschnitte 1–6 der Gliederung
|   +-- Hinweis „in Arbeit" als Badge bei noch fehlenden Schritten
+-- Info-Kasten „Aktueller Stand" (Karte)
|   +-- App-Version
|   +-- Wissensstand (Commit, Datum, ggf. „mit uncommitteten Änderungen" oder „unbekannt")
|   +-- verwendbare Dokumente, davon Entwürfe
|   +-- Link zur Knowledge-Übersicht
+-- Teil „Für Entwickler"   <- docs/ABOUT.md ab der Überschrift „Für Entwickler", in eigener, abgesetzter Karte
```

### B) Daten
- **Text:** `docs/ABOUT.md`, normales Markdown. Eine Überschrift „Für Entwickler" trennt die beiden Teile; die App setzt dort den Info-Kasten dazwischen.
- **Markierung „in Arbeit":** Im Text steht an einem Schritt die Kennung `[in Arbeit]`. Die App zeigt sie als Badge an. Liest man die Datei direkt im Repository, bleibt die Kennung als normaler Text verständlich.
- **Info-Kasten:** Es wird nichts gespeichert. App-Version aus der Konfiguration (PROJ-1), Wissensstand und Zahlen aus der Knowledge-Bibliothek (PROJ-3), wie auf der Knowledge-Seite.

### C) Technische Entscheidungen (für Nicht-Entwickler)
- **Markdown-Datei statt Text im Seitenbaustein:** Der Text ist ohne Programmierkenntnisse änderbar (Spec). Entwickler lesen ihn auch direkt im Repository oder auf GitHub. Eine Quelle, keine doppelte Pflege.
- **Eine Datei statt zwei:** Beide Teile bleiben zusammen und lesen sich auch außerhalb der App als ein Dokument. Die feste Überschrift „Für Entwickler" reicht als Trennstelle.
- **Ablage in `docs/`:** Dort liegt die übrige Projektdokumentation. So entsteht kein neuer Ordner.
- **Bestehende Markdown-Darstellung wiederverwenden:** Sie ist schon gegen eingeschleusten Code abgesichert (PROJ-24): rohes HTML wird nicht ausgeführt, Bilder werden nicht geladen. Auf diese Seite kommt dieselbe Absicherung ohne neuen Code.
- **Kennung `[in Arbeit]` statt eigener Syntax:** Eine einzige, leicht zu merkende Kennung. Das Entfernen beim Abnehmen eines Features ist eine Textänderung (Punkt der `/qa`-Checkliste).
- **Eigene, schlichte Seite (Web-Route, Blade):** Keine Interaktion nötig, also kein Alpine.js. Deutsche Adresse `/ueber-die-app`, passend zu `/knowledge/dokument/…`.
- **Text fehlt oder ist leer:** Die Seite zeigt trotzdem Info-Kasten und einen Hinweis „Beschreibung fehlt" statt eines Fehlers. Ein fehlender Text darf die App nicht stören.

### D) Abhängigkeiten
Keine neuen Pakete.

### E) Hinweise für /frontend und /backend
- Neues Icon `info` im bestehenden Icon-Baustein.
- Die Seitenleiste bekommt unter der Hauptnavigation einen abgesetzten Bereich für den neuen Eintrag.
- Den ersten Text für `docs/ABOUT.md` entwirft `/frontend` aus PRD, CLAUDE.md und den Specs. Der Autor gibt ihn frei.
- Tests: Feature-Tests für Navigation, Reihenfolge der Abschnitte, Badge, Info-Kasten (inkl. fehlender Knowledge-Ordner und unbekannter Wissensstand), fehlender Text und keine Inhalte aus `.env`.


## Implementation Notes (Frontend + Backend)
**Gebaut am 2026-10-05.** Die Serverseite ist so klein, dass sie mit dem Frontend gebaut wurde; `/backend` entfällt.

- **Text:** `docs/ABOUT.md`, Abschnitte als `#`-Überschriften (auf der Seite `h2`). Der erste Entwurf stammt aus PRD, CLAUDE.md und den Specs. Das Beispiel (Zaubertasse auf Amazon) folgt PLAYBOOK-004. Sieben von acht Ablaufschritten tragen `[in Arbeit]`; „Passendes Wissen auswählen" (PROJ-4, Approved) nicht.
- **Seite:** Route `/ueber-die-app` (Name `about`), `AboutController` (invokable), View `resources/views/about.blade.php`. Der Text wird an der Überschrift „Für Entwickler" geteilt. Der Teil für alle steht in einer Karte, danach folgt der Info-Kasten, dann der Entwicklerteil unter gestrichelter Trennlinie mit eigener Überschrift und Zahnrad-Icon.
- **Darstellung:** `KnowledgeMarkdown` aus PROJ-24 (rohes HTML wird escaped). `[in Arbeit]` wird zum Badge (CSS-Klasse `.about-wip` in `resources/css/app.css`); innerhalb von Code bleibt die Kennung stehen.
- **Info-Kasten:** App-Version (`config('app.version')`), Wissensstand (`KnowledgeState::label()`), verwendbare Dokumente und Entwürfe. Fehlt der Knowledge-Ordner oder ist er leer, erscheint dazu die Meldung aus PROJ-3. Link zur Knowledge-Übersicht.
- **Navigation:** Zweiter, abgesetzter `<nav>`-Bereich „Weitere Seiten" unter der Hauptnavigation in der Seitenleiste.
- **Konfiguration:** `app.about_path` (Standard `docs/ABOUT.md`), damit Tests einen anderen Text verwenden können.
- **Abweichungen vom Tech Design:**
  - Icon `help` aus dem bestehenden Set statt eines neuen `info`; das Design System führt `help` ausdrücklich für „Hilfe, Info".
  - Der Link zur Knowledge-Übersicht steht im Text als relative Adresse `/knowledge`.
- **Tests:** `tests/Feature/PROJ-25-AboutPageTest.php` (23 Tests). Gesamte Suite: 393 Tests grün. Assets neu gebaut.

## QA Test Results

**Tested:** 2026-10-05
**App URL:** http://localhost:8081/ueber-die-app
**Tester:** QA Engineer (AI)

Geprüft über Pest (Feature-Tests), HTTP-Abrufe gegen die laufende App und Proben mit abweichenden Texten. Ein Browser-Werkzeug stand nicht zur Verfügung: Darstellung in Chrome, Firefox, Safari und bei 375/768/1440 px wurde nur am Markup geprüft (siehe unten). Den Text hat der Autor am 2026-10-05 gelesen und bestätigt.

### Acceptance Criteria Status

#### Navigation
- [x] „Über die App" mit Icon unten in der Seitenleiste, eigener Bereich „Weitere Seiten", getrennt von den Arbeitsseiten
- [x] Seitentitel „Über die App", Eintrag als aktiv markiert (genau ein aktiver Eintrag)
- [x] Direkter Aufruf ohne Anmeldung (HTTP 200)

#### Inhalt für alle
- [x] Abschnitte in der Reihenfolge der Gliederung, jeweils mit Überschrift
- [x] „in Arbeit" als Badge bei 7 noch nicht nutzbaren Schritten; „Passendes Wissen auswählen" ohne Hinweis
- [x] Grenzen: kein Versand, keine Entscheidung, keine Änderungen an Bestellungen, Erstattungen, Gutschriften
- [x] „Gut zu wissen": „unklar", Entwurfs-Wissen, Quellen
- [x] Beispiel fiktiv, ohne Namen, Adressen, Bestell- oder Ticketnummern
- [x] Verweis auf die Knowledge-Übersicht öffnet `/knowledge` im selben Tab
- [x] Einfache Sprache; Knowledge, Entwurfs-Wissen und Kundengruppe beim ersten Vorkommen erklärt (Kleinigkeit siehe BUG-2)

#### Info-Kasten „Aktueller Stand"
- [x] App-Version wie im Sidebar-Fuß
- [x] Wissensstand, verwendbare Dokumente, davon Entwürfe (fehlerhafte und `deprecated` nicht mitgezählt)
- [x] Geänderte Knowledge-Datei erscheint beim nächsten Laden

#### Für Entwickler
- [x] Optisch abgesetzt (gestrichelte Linie, eigene Überschrift mit Icon)
- [x] Tech Stack mit je einem Satz zum Zweck
- [x] Bausteine, Skills-Ablauf, Betrieb über Sail
- [x] Fundorte: PRD, INDEX, Authoring Guide, Design-Dokument, Design System, README
- [x] Keine Zugangsdaten, Adressen, Ports, `.env`-Inhalte (Test prüft URLs, IPs, Ports, Schlüsselwörter)

#### Pflege
- [x] Entfernen der Hinweise „in Arbeit" steht als fester Punkt in der `/qa`-Checkliste

### Edge Cases Status
- [x] Knowledge-Ordner fehlt → 0 Dokumente plus Meldung aus PROJ-3, Seite vollständig
- [x] Wissensstand unbekannt → „unbekannt"
- [x] Uncommittete Änderungen → Zusatz „mit uncommitteten Änderungen"
- [x] Lokal → Version „dev"
- [x] Fehlerhafte Knowledge-Dateien zählen nicht mit, keine Fehlerliste auf der Seite
- [x] Text fehlt oder leer → „Beschreibung fehlt", Info-Kasten bleibt
- [~] Schmaler Bildschirm: nur am Markup geprüft (Seitenleiste unter `md` ausgeblendet, Tabelle mit waagrechtem Scrollen im eigenen Kasten, `wrap-anywhere` für lange Pfade). Sichtprüfung bei 375 px steht aus.

**Zusätzlich geprüft:**
- [x] Text ohne Entwicklerteil → nur Teil für alle, keine Meldung
- [x] Nur Entwicklerteil → Info-Kasten und Entwicklerteil
- [x] Überschrift „Für Entwickler" doppelt → Trennung an der ersten, zweite bleibt als normale Überschrift
- [x] Mehrere Kennungen in einer Zeile und in Fettschrift → je ein Badge; Kennung als Linktext bleibt Link
- [x] Kennung in Code bleibt stehen (Entwicklerteil erklärt sie so)
- [x] Genau eine `h1` auf der Seite
- [x] Überschrift „## Für Entwickler:" (mit Doppelpunkt) oder „für entwickler" wird als Trennstelle erkannt (BUG-1, behoben)

### Security Audit Results
- [x] Rohes HTML im Text wird escaped (`<script>` erscheint nicht)
- [x] Abfrageparameter werden nicht ausgegeben (Reflected-XSS-Probe ohne Treffer)
- [x] Nur GET; POST ergibt 405
- [x] Kein Pfad aus der Anfrage wird zum Lesen von Dateien verwendet (Textpfad nur aus der Konfiguration); `../.env` ergibt 404
- [x] Sicherheits-Header aktiv (`X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`)
- [x] Keine Geheimnisse in der Ausgabe
- [x] Keine Formulare, daher kein CSRF-Risiko; keine Anmeldung im MVP (laut PRD)

### Regression
- [x] PROJ-1 (Shell, Version im Fuß), PROJ-24 (aktiver Eintrag „Knowledge", Markdown-Darstellung): Tests grün
- [x] Gesamte Suite: 393 Tests grün

### Bugs Found

#### BUG-1: Trennstelle „Für Entwickler" ist sehr streng – BEHOBEN (2026-10-05)
- **Severity:** Low
- **Fix:** Erkennung ohne Beachtung der Groß-/Kleinschreibung, Doppelpunkt am Ende erlaubt; Test für beide Schreibweisen.
- **Steps to Reproduce:**
  1. In `docs/ABOUT.md` die Überschrift als „## Für Entwickler:" oder „# für entwickler" schreiben
  2. Seite öffnen
  3. Expected: Entwicklerteil abgesetzt unter dem Info-Kasten
  4. Actual: Entwicklerteil erscheint im Teil für alle, über dem Info-Kasten, ohne Absetzung
- **Priority:** Nice to have (heute korrekt; betrifft nur künftige Textänderungen)

#### BUG-2: „Authoring Guide" im Teil für alle unerklärt – BEHOBEN (2026-10-05)
- **Severity:** Low
- **Fix:** „nach unserer Schreibanleitung für Wissen (dem „Authoring Guide")".
- **Steps to Reproduce:**
  1. Abschnitt „Wissen ergänzen" lesen
  2. Expected: einfache Sprache ohne unerklärte Fachbegriffe
  3. Actual: „nach dem Authoring Guide" ohne Erklärung, was das ist und wo er liegt
- **Priority:** Nice to have (z. B. „nach unserer Schreibanleitung für Wissen (Authoring Guide)")

### Summary
- **Acceptance Criteria:** 23/23 passed
- **Bugs Found:** 2 total (0 critical, 0 high, 0 medium, 2 low); beide am 2026-10-05 behoben
- **Security:** Pass
- **Production Ready:** YES
- **Recommendation:** Freigeben; Sichtprüfung bei 375 px beim nächsten Browserbesuch nachholen

## Deployment
_To be added by /deploy_
