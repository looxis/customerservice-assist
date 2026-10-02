# PROJ-24: Knowledge-Übersicht

## Status: Approved
**Created:** 2026-10-02
**Last Updated:** 2026-10-02

## Dependencies
- Requires: PROJ-1 (App-Grundgerüst mit LOOXIS-Design) – Layout, Navigation, Basis-Komponenten
- Requires: PROJ-3 (Knowledge Base einlesen und prüfen) – liefert Dokumente, Meldungen, Wissensstand und ID-Übersicht

Die Seite zeigt nur an, was PROJ-3 bereitstellt. Sie liest selbst keine Dateien und ändert nichts.

## User Stories
- Als Autor der Knowledge Base möchte ich nach dem Einfügen einer Datei die Seite neu laden und sofort sehen, ob das Dokument erkannt wurde und ob es Fehler oder Warnungen gibt, damit ich nicht ins Terminal wechseln muss.
- Als Autor möchte ich die ID-Übersicht mit einem Klick kopieren, damit ich sie dem Browser-Chat zu Sitzungsbeginn geben kann.
- Als Autor möchte ich ein Dokument formatiert lesen, damit ich prüfen kann, ob Inhalt und Aufbau so angekommen sind, wie ich sie geschrieben habe.
- Als Kundenservice-Mitarbeiter oder Aushilfe möchte ich Regeln, Befugnisse und Produktwissen nachlesen können, damit ich einen Vorschlag der App besser verstehe.
- Als Mitarbeiter möchte ich erkennen, welche Dokumente geprüft und welche noch Entwurf sind, damit ich weiß, wie verlässlich eine Regel ist.
- Als Autor möchte ich sehen, auf welchem Wissensstand die App gerade arbeitet, damit ich weiß, ob meine letzte Änderung schon berücksichtigt ist.

## Out of Scope
- Bearbeiten, Anlegen oder Löschen von Knowledge-Dateien in der App.
- Entwurf per Klick auf `active` stellen – PROJ-23. PROJ-24 zeigt den Status nur an.
- Anzeige, welche Dokumente in einer bestimmten Analyse verwendet wurden – PROJ-10. PROJ-10 kann auf die Dokumentansicht dieser Seite verlinken.
- Auswahl von Dokumenten für einen Fall – PROJ-4.
- Volltextsuche im Inhalt der Dokumente. Gesucht wird nur in ID und Titel.
- Versionshistorie oder Vergleich zweier Fassungen eines Dokuments; dafür ist Git zuständig.
- Vorlagen (`knowledge/templates/`) und die Einstiegsseite `knowledge/README.md` anzeigen.
- Zugriffsbeschränkung nach Rolle. Im MVP gibt es keinen Login; mit PROJ-15 kann der technische Bereich (Prüfergebnis, ID-Übersicht) auf Admins beschränkt werden. Die Dokumente selbst, auch Entwürfe, bleiben für alle sichtbar.
- Mobile Navigation (wie in PROJ-1).

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Navigation
- [ ] Angenommen die App läuft, wenn ein Nutzer die Sidebar betrachtet, dann gibt es unter „Ticket analysieren" einen zweiten Navigationspunkt „Knowledge" mit Icon.
- [ ] Angenommen der Nutzer ist auf der Knowledge-Übersicht oder in einer Dokumentansicht, wenn er die Sidebar betrachtet, dann ist „Knowledge" als aktiv hervorgehoben und „Ticket analysieren" nicht.

### Dokumentliste
- [ ] Angenommen es gibt Knowledge-Dokumente, wenn der Nutzer die Seite öffnet, dann sieht er alle gelesenen Dokumente gruppiert nach Typ in der Rangfolge der Knowledge Base (Policies, Permissions, Produkte, Prozesse, Playbooks, Ton, Glossar, gute Beispiele, schlechte Beispiele), innerhalb eines Typs nach ID sortiert.
- [ ] Angenommen ein Dokument wird in der Liste gezeigt, wenn der Nutzer die Zeile betrachtet, dann sieht er ID, Titel, Status und den Geltungsbereich (Kundenart, Kanal, Kategorie, Produkt); ein leerer Geltungsbereich wird als „alle" dargestellt.
- [ ] Angenommen ein Dokument hat den Status `draft`, `active` oder `deprecated`, wenn es in der Liste erscheint, dann trägt es ein Badge „Entwurf", „Aktiv" bzw. „Veraltet" in unterscheidbaren Tönen.
- [ ] Angenommen ein Dokument ist `deprecated`, wenn es in der Liste erscheint, dann ist es abgeschwächt dargestellt und als „wird nicht verwendet" erkennbar.
- [ ] Angenommen ein Dokument hat mindestens einen Fehler, wenn es in der Liste erscheint, dann ist es mit einem Fehler-Badge „Wird nicht verwendet" gekennzeichnet; hat es nur Warnungen, trägt es ein Warn-Badge mit der Anzahl.
- [ ] Angenommen eine Datei hat ein nicht lesbares Frontmatter, wenn sie in der Liste erscheint, dann wird sie mit ihrem Dateipfad statt eines Titels und mit dem Fehler-Badge angezeigt.
- [ ] Angenommen ein Typ hat keine Dokumente, wenn die Liste angezeigt wird, dann erscheint für diesen Typ kein leerer Abschnitt.
- [ ] Angenommen der Nutzer klickt auf ein Dokument in der Liste, wenn die Seite wechselt, dann öffnet sich die Dokumentansicht dieses Dokuments.

### Kennzahlen und Wissensstand
- [ ] Angenommen die Seite ist geöffnet, wenn der Nutzer den Kopfbereich betrachtet, dann sieht er die Anzahl der Dokumente gesamt, verwendbar, als Entwurf und aktiv.
- [ ] Angenommen die Seite ist geöffnet, wenn der Nutzer den Kopfbereich betrachtet, dann sieht er den Wissensstand (Kurz-Hash und Datum des letzten Commits) und, falls zutreffend, den Hinweis „mit uncommitteten Änderungen"; ist der Stand unbekannt, steht dort „unbekannt".

### Filter
- [ ] Angenommen der Nutzer wählt einen Typ, einen Status oder „nur mit Meldungen", wenn der Filter angewendet wird, dann zeigt die Liste nur passende Dokumente, und der gewählte Filter bleibt beim Neuladen der Seite erhalten.
- [ ] Angenommen der Nutzer gibt einen Suchbegriff ein, wenn die Liste gefiltert wird, dann bleiben nur Dokumente, deren ID oder Titel den Begriff enthalten, unabhängig von Groß- und Kleinschreibung.
- [ ] Angenommen kein Dokument passt zu Filter oder Suche, wenn die Liste angezeigt wird, dann erscheint der Hinweis „Keine Dokumente gefunden" mit einer Möglichkeit, die Filter zurückzusetzen.

### Technischer Bereich: Prüfergebnis
- [ ] Angenommen es gibt weder Fehler noch Warnungen, wenn der Nutzer die Seite öffnet, dann sieht er einen Erfolgshinweis „Keine Fehler, keine Warnungen" und der Bereich ist eingeklappt.
- [ ] Angenommen es gibt Fehler, wenn der Nutzer die Seite öffnet, dann ist der Bereich aufgeklappt, zeigt die Anzahl der Fehler und Warnungen und listet die Meldungen nach Datei gruppiert, Fehler vor Warnungen.
- [ ] Angenommen es gibt nur Warnungen, wenn der Nutzer die Seite öffnet, dann ist der Bereich eingeklappt und zeigt die Anzahl der Warnungen; aufgeklappt listet er sie nach Datei gruppiert.
- [ ] Angenommen eine Meldung gehört zu einem lesbaren Dokument, wenn der Nutzer sie anklickt, dann gelangt er zur Dokumentansicht.
- [ ] Angenommen der Knowledge-Ordner fehlt oder ist leer, wenn der Nutzer die Seite öffnet, dann sieht er statt der Liste einen deutlichen Hinweis, dass kein Unternehmenswissen verfügbar ist, und die Seite bleibt bedienbar.

### Technischer Bereich: ID-Übersicht
- [ ] Angenommen der Nutzer klappt den Bereich „Für den KI-Chat" auf, wenn er ihn betrachtet, dann sieht er die ID-Übersicht als Text, genau so, wie `knowledge:overview` sie ausgibt.
- [ ] Angenommen die ID-Übersicht wird angezeigt, wenn der Nutzer auf „Kopieren" klickt, dann liegt der vollständige Text in der Zwischenablage und eine kurze Bestätigung „Kopiert" erscheint.
- [ ] Angenommen der Browser erlaubt das Kopieren nicht (z. B. Aufruf ohne HTTPS), wenn der Nutzer auf „Kopieren" klickt, dann wird der Text markiert und ein Hinweis fordert zum Kopieren per Tastatur auf.

### Dokumentansicht
- [ ] Angenommen ein fehlerfreies Dokument wird geöffnet, wenn der Nutzer die Ansicht betrachtet, dann sieht er ID, Titel, Typ, Status-Badge, Geltungsbereich, Themen, Dateipfad und den Fingerabdruck in Kurzform.
- [ ] Angenommen das Dokument ist eine Permission, wenn die Ansicht geöffnet wird, dann zeigt sie zusätzlich Maßnahme, ob der Kundenservice selbst entscheiden darf, Wertgrenze und Freigabe-Rolle; eine leere Wertgrenze erscheint als „keine Wertgrenze".
- [ ] Angenommen das Dokument hat Text mit Überschriften, Listen, Hervorhebungen und Tabellen, wenn die Ansicht geöffnet wird, dann wird der Text formatiert und gut lesbar dargestellt, nicht als Markdown-Quelltext.
- [ ] Angenommen das Dokument verweist in `related_knowledge` oder im Text auf andere IDs, wenn die Ansicht geöffnet wird, dann sind vorhandene IDs anklickbar und führen zum jeweiligen Dokument; nicht vorhandene IDs bleiben einfacher Text.
- [ ] Angenommen das Dokument ist ein Entwurf, wenn die Ansicht geöffnet wird, dann steht oben ein Hinweis, dass es sich um Entwurfs-Wissen handelt, das noch nicht fachlich bestätigt ist.
- [ ] Angenommen das Dokument ist `deprecated`, wenn die Ansicht geöffnet wird, dann steht oben ein Hinweis, dass es nicht mehr gilt und nicht verwendet wird.
- [ ] Angenommen das Dokument hat Fehler oder Warnungen, wenn die Ansicht geöffnet wird, dann stehen die Meldungen über dem Text, Fehler vor Warnungen.
- [ ] Angenommen eine Datei hat ein nicht lesbares Frontmatter, wenn ihre Ansicht geöffnet wird, dann zeigt sie Dateipfad und Fehlermeldung und keinen Text.
- [ ] Angenommen der Nutzer ruft die Ansicht für eine ID oder einen Pfad auf, den es nicht gibt, wenn die Seite lädt, dann sieht er die „Seite nicht gefunden"-Seite.
- [ ] Angenommen der Nutzer ist in der Dokumentansicht, wenn er zurück möchte, dann führt ein Link zur Übersicht und die zuvor gesetzten Filter sind erhalten.

### Sicherheit der Darstellung
- [ ] Angenommen Titel, Text oder Frontmatter eines Dokuments enthalten HTML oder Skript-Code, wenn Liste oder Dokumentansicht angezeigt werden, dann wird der Code als Text dargestellt und nicht ausgeführt.
- [ ] Angenommen der Text enthält einen Link, wenn der Nutzer ihn anklickt, dann öffnet er sich in einem neuen Tab, ohne der Zielseite Zugriff auf die App zu geben; Links mit `javascript:` werden nicht als Link dargestellt.

## Edge Cases
- **Zwei Dateien mit derselben ID:** Beide erscheinen in der Liste mit Fehler-Badge. Die Dokumentansicht ist über den Dateipfad eindeutig, nicht über die ID.
- **Sehr langer Titel oder langer Dateipfad:** wird in der Liste gekürzt, in der Dokumentansicht vollständig umbrochen.
- **Dokument ohne Titel oder ohne ID** (Pflichtfeld fehlt): erscheint mit Dateipfad und Fehler-Badge.
- **Sehr viele Dokumente (200 und mehr):** Die Liste bleibt ohne Blättern bedienbar; Filter und Suche reagieren ohne spürbare Verzögerung.
- **Datei wird zwischen Laden der Liste und Klick gelöscht:** Die Dokumentansicht zeigt „Seite nicht gefunden".
- **Sehr langes Dokument:** vollständig lesbar, die Seite scrollt; die Warnung „sehr lang" steht über dem Text.
- **Text mit Bildverweis:** Bilder werden nicht geladen (keine Abrufe von fremden Servern); stattdessen erscheint der Alternativtext.
- **Verweis auf sich selbst oder auf ein Dokument mit Fehlern:** kein Link bzw. Link zur Datei mit Fehleranzeige.
- **Wissensstand unbekannt** (Server ohne Git): Anzeige „unbekannt", kein Fehler.
- **Suchbegriff mit Sonderzeichen:** wird als Text gesucht, löst keinen Fehler aus.
- **JavaScript abgeschaltet:** Liste, Filter und Dokumentansicht funktionieren; nur „Kopieren" und das Ein-/Ausklappen sind nicht verfügbar, die Bereiche sind dann aufgeklappt.

## Technical Requirements (optional)
- Seitenaufbau der Übersicht mit 200 Dokumenten unter einer Sekunde.
- Keine Abrufe von fremden Servern (wie PROJ-1), auch nicht durch Inhalte der Dokumente.
- Darstellung ausschließlich mit den Bausteinen und Tokens aus PROJ-1; die in PROJ-1 zurückgestellte Tabellen-Darstellung entsteht hier, falls sie gebraucht wird.
- Der Test aus PROJ-1 „genau ein Navigationspunkt" muss an den zweiten Navigationspunkt angepasst werden.

## Open Questions
- [x] Sollen Aushilfen im Alltag Entwurfs-Wissen überhaupt sehen? → Ja, dauerhaft, mit Hinweis (Entscheidung vom 2026-10-02).

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Die Seite ist für alle sichtbar und rein lesend | Im MVP gibt es keinen Login; Aushilfen profitieren vom Nachlesen der Regeln, ändern kann niemand etwas | 2026-10-02 |
| Prüfergebnis und ID-Übersicht stehen in einem eigenen, einklappbaren Bereich | Es sind technische Angaben für den Autor; Aushilfen sollen sie nicht für ihre Aufgabe halten | 2026-10-02 |
| Der Prüfbereich klappt nur bei Fehlern von selbst auf | Fehler bedeuten, dass eine Regel in der App fehlt; Warnungen sind weniger dringend | 2026-10-02 |
| Dokumente lassen sich formatiert lesen | Der Autor prüft eingefügte Dateien direkt in der App, und PROJ-10 kann für „Quelle einsehen" hierher verlinken | 2026-10-02 |
| Liste gruppiert nach Typ in der Rangfolge der Knowledge Base | Die Rangfolge (Policies vor Permissions vor …) ist fachlich bedeutsam und soll sich in der Ansicht wiederfinden | 2026-10-02 |
| Fehlerhafte und veraltete Dokumente werden angezeigt, aber gekennzeichnet | Der Autor muss sehen, was nicht verwendet wird; ein fehlendes Dokument würde nicht auffallen | 2026-10-02 |
| Filter nach Typ, Status und Meldungen, Suche nur in ID und Titel | Reicht für die erwartete Größe; Volltextsuche wäre ein eigenes Thema | 2026-10-02 |
| Dokumentansicht wird über den Dateipfad angesprochen, nicht über die ID | Bei doppelten IDs oder fehlender ID wäre die ID nicht eindeutig | 2026-10-02 |
| Bilder in Dokumenten werden nicht geladen | Vorgabe aus PROJ-1: keine Abrufe von fremden Servern | 2026-10-02 |
| Entwurfs-Wissen ist für alle sichtbar, auch für Aushilfen, und mit einem Hinweis gekennzeichnet | Der Autor ist nicht allwissend; eine Aushilfe mit gesundem Menschenverstand und frischem Blick ist nicht betriebsblind und kann Schwächen in einem Entwurf erkennen. Verstecken würde diese Rückmeldung verhindern | 2026-10-02 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|
| Zwei Web-Routen mit einem Controller: Übersicht und Dokumentansicht | Beide liefern vollständige Seiten; ein Controller statt Closure-Routen, damit der Routen-Cache im Produktivbetrieb funktioniert | 2026-10-02 |
| Keine Datenbank; der Controller fragt ausschließlich die Knowledge-Bibliothek aus PROJ-3 | Die Seite ist eine reine Anzeige; es gibt nur eine Stelle, die Dateien liest | 2026-10-02 |
| Filter und Suche laufen über die Adresszeile (Abfrage-Parameter) und werden auf dem Server angewendet | Der Filter bleibt beim Neuladen erhalten, lässt sich als Link weitergeben und funktioniert ohne JavaScript | 2026-10-02 |
| Filterwerte werden über eine Form-Request-Klasse geprüft | Projektregel: keine Validierung im Controller; unbekannte Werte werden abgewiesen statt durchgereicht | 2026-10-02 |
| Die Dokumentansicht sucht den angefragten Pfad in der Liste der gelesenen Dokumente, nie direkt im Dateisystem | Ein manipulierter Pfad in der Adresse kann so keine Datei außerhalb der Knowledge Base erreichen | 2026-10-02 |
| Markdown wird mit der in Laravel enthaltenen Bibliothek (`league/commonmark`) umgewandelt, im sicheren Modus | Kein neues Paket; HTML im Text wird maskiert, unsichere Links werden nicht erzeugt, Tabellen werden unterstützt | 2026-10-02 |
| Bilder im Text werden durch ihren Alternativtext ersetzt; Links öffnen in neuem Tab ohne Rückbezug | Keine Abrufe von fremden Servern (PROJ-1); die Zielseite erhält keinen Zugriff auf die App | 2026-10-02 |
| Eigene Textstile für den umgewandelten Dokumenttext mit den LOOXIS-Tokens, kein Typografie-Zusatzpaket | Wenige Regeln genügen (Überschriften, Listen, Tabellen, Zitate, Code); ein Zusatzpaket brächte eigene Farben mit, die abgeschaltet sind | 2026-10-02 |
| Einklappbare Bereiche mit dem eingebauten Aufklapp-Element des Browsers | Funktioniert ohne JavaScript und ist für Tastatur und Screenreader bereits zugänglich; besser als im Spec angenommen (dort: ohne JavaScript dauerhaft aufgeklappt) | 2026-10-02 |
| „Kopieren" als kleiner Alpine.js-Baustein mit Rückfall auf Markieren | Die Zwischenablage ist ohne HTTPS gesperrt; im internen Netz ist das wahrscheinlich, daher muss der Rückfall immer funktionieren | 2026-10-02 |
| Neue wiederverwendbare Bausteine: Aufklapp-Bereich, Kopier-Feld, Status-Badge für Knowledge, Dokumenttext | PROJ-10 braucht Status-Badge und Dokumenttext für „Quelle einsehen" erneut | 2026-10-02 |
| Zweiter Navigationspunkt wird im Layout aus PROJ-1 ergänzt; der dortige Test wird angepasst | Die Navigation ist an genau einer Stelle definiert | 2026-10-02 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

### Überblick
PROJ-24 ist eine reine Anzeige ohne Datenbank. Es entstehen zwei Seiten, ein Controller, eine Prüfklasse für die Filterwerte und vier neue Bausteine. Alle Daten kommen aus der Knowledge-Bibliothek von PROJ-3. Gebaut wird mit `/frontend` und einem kleinen `/backend`-Anteil (Controller, Routen, Markdown-Umwandlung); beides lässt sich in einem Durchgang erledigen.

### A) Aufbau der Oberfläche

```
Seitenlayout (aus PROJ-1)
+-- Sidebar
|   +-- „Ticket analysieren"
|   +-- „Knowledge" (neu)

Seite „Knowledge" (Übersicht)
+-- Kopfkarte
|   +-- Kennzahlen: gesamt, verwendbar, Entwurf, aktiv
|   +-- Wissensstand (Commit, Datum, ggf. „mit uncommitteten Änderungen")
+-- Hinweis, falls der Knowledge-Ordner fehlt oder leer ist
+-- Filterleiste (Formular)
|   +-- Suche (ID oder Titel)
|   +-- Typ, Status
|   +-- „nur mit Meldungen"
|   +-- Zurücksetzen
+-- Dokumentliste, je Typ ein Abschnitt in Rangfolge
|   +-- Zeile: ID, Titel, Status-Badge, Geltungsbereich, Meldungs-Badge
|   +-- Leerzustand „Keine Dokumente gefunden" mit Zurücksetzen
+-- Aufklapp-Bereich „Prüfergebnis" (bei Fehlern offen)
|   +-- Summen, Meldungen je Datei (Fehler vor Warnungen), Link zum Dokument
+-- Aufklapp-Bereich „Für den KI-Chat"
    +-- ID-Übersicht als Text
    +-- Button „Kopieren" mit Bestätigung bzw. Rückfall

Seite „Dokument"
+-- Link zurück zur Übersicht (Filter bleiben erhalten)
+-- Hinweis bei Entwurf bzw. veraltetem Dokument
+-- Meldungen zu dieser Datei
+-- Kopfkarte
|   +-- ID, Titel, Typ, Status-Badge
|   +-- Geltungsbereich, Themen, Verweise (anklickbar)
|   +-- bei Permissions: Maßnahme, Erlaubnis, Wertgrenze, Rolle
|   +-- Dateipfad, Fingerabdruck (kurz)
+-- Dokumenttext, formatiert
```

**Neue wiederverwendbare Bausteine:** Aufklapp-Bereich, Kopier-Feld, Knowledge-Status-Badge, Dokumenttext. Wiederverwendet aus PROJ-1: Layout, Navigationspunkt, Karte, Button, Input, Select, Badge, Alert, Icon.

### B) Wie die Seiten erreichbar sind

| Adresse | Seite |
|---|---|
| `/knowledge` | Übersicht, Filter als Abfrage-Parameter |
| `/knowledge/dokument/<Dateipfad>` | Dokumentansicht |

Der Dateipfad ist der Pfad innerhalb der Knowledge Base (z. B. `policies/policy-005-…md`). Er ist auch bei doppelter oder fehlender ID eindeutig.

### C) Daten
Es wird nichts gespeichert. Pro Seitenaufruf liest die Bibliothek die Dateien einmal; daraus entstehen Kennzahlen, Liste, Meldungen, Wissensstand und ID-Übersicht.

Filterwerte: Suchbegriff (Text, begrenzte Länge), Typ (einer der neun), Status (einer der drei), „nur mit Meldungen" (ja/nein). Andere Werte werden abgewiesen.

### D) Vom Markdown zum lesbaren Text

```
Text des Dokuments
-> Umwandlung in HTML im sicheren Modus (HTML im Text wird maskiert)
-> Bilder durch Alternativtext ersetzt
-> Links: neuer Tab, kein Rückbezug; unsichere Links entfallen
-> vorhandene Knowledge-IDs im Text werden zu Links auf das Dokument
-> Darstellung mit eigenen Textstilen (LOOXIS-Tokens)
```

### E) Wichtigste Entscheidungen in Kürze
- **Filter über die Adresszeile.** Sie überleben das Neuladen und funktionieren ohne JavaScript.
- **Pfad wird nie direkt geöffnet.** Die Dokumentansicht sucht den Pfad in der Liste der gelesenen Dokumente; alles andere ergibt „Seite nicht gefunden".
- **Kein neues Paket.** Die Markdown-Bibliothek bringt Laravel mit; die Textstile entstehen selbst.
- **Aufklappen mit Bordmitteln des Browsers.** Dadurch funktioniert das Ein- und Ausklappen auch ohne JavaScript. Das ist besser, als das Spec annimmt; nur „Kopieren" braucht JavaScript.
- **Kopieren mit Rückfall.** Ohne HTTPS sperrt der Browser die Zwischenablage; dann wird der Text markiert.

### F) Automatische Tests (Umfang für `/qa`)
- Übersicht: Gruppierung und Reihenfolge, Badges je Status, Fehler- und Warn-Badge, Datei mit kaputtem Frontmatter, Kennzahlen, Wissensstand, leerer und fehlender Ordner.
- Filter: Typ, Status, Meldungen, Suche, Kombination, Leerzustand, ungültige Filterwerte.
- Prüfbereich: offen bei Fehlern, zu bei Warnungen, Erfolgshinweis ohne Meldungen.
- ID-Übersicht: Text entspricht dem Befehl.
- Dokumentansicht: Kopfdaten, Permission-Felder, formatierter Text, anklickbare Verweise, Hinweise bei Entwurf und veraltet, Meldungen, kaputte Datei, unbekannter Pfad, Pfad mit `..`.
- Sicherheit: Skript-Code in Titel, Text und Frontmatter wird maskiert; `javascript:`-Link wird kein Link; Bild wird nicht geladen.
- Navigation: zweiter Punkt, aktiv auf beiden Seiten; angepasster PROJ-1-Test.

Im Browser zu prüfen: Lesbarkeit des Dokumenttexts, Verhalten des Kopier-Buttons mit und ohne HTTPS, Darstellung bei 768 und 1440 px.

### G) Abhängigkeiten
Keine neuen Pakete. `league/commonmark` ist Bestandteil von Laravel.

### H) Übergaben an andere Features
- **PROJ-10** verlinkt für „Quelle einsehen" auf die Dokumentansicht und nutzt Status-Badge und Dokumenttext.
- **PROJ-23** ergänzt in der Dokumentansicht den Klick zum Bestätigen eines Entwurfs.
- **PROJ-15** kann den technischen Bereich auf Admins beschränken.

## Implementation Notes
**Stand:** 2026-10-02 – Frontend und Backend-Anteil umgesetzt; Browser-Abnahme durch den Nutzer steht aus.

**Gebaut:**
- Routen `knowledge.index` (`/knowledge`) und `knowledge.show` (`/knowledge/dokument/{path}`), `KnowledgeController`, `KnowledgeIndexRequest` für die Filterwerte.
- `App\Knowledge\KnowledgeMarkdown`: wandelt den Dokumenttext sicher in HTML um (HTML maskiert, unsichere Links entfernt, Bilder durch Alternativtext ersetzt, externe Links in neuem Tab ohne Rückbezug, Überschriften eine Ebene tiefer, vorhandene Knowledge-IDs als Links).
- Seiten `knowledge/index.blade.php` und `knowledge/show.blade.php`.
- Neue Bausteine: `collapsible`, `copy-field`, `knowledge/status-badge`, `knowledge/scope`, `knowledge/text`; Textstile `.knowledge-text` in `resources/css/app.css`.
- Zweiter Navigationspunkt „Knowledge" im Layout; der PROJ-1-Test zur Navigation ist angepasst.
- `config/knowledge.php`: deutsche Bezeichnung je Typ (`label`).
- Die gemeinsamen Test-Helfer (`knowledgeBase`, `knowledgeDoc`, `messagesOf`, `cleanUpKnowledgeBases`) liegen jetzt in `tests/Pest.php`.

**Abweichungen vom Spec:**
- Einklappbare Bereiche funktionieren auch ohne JavaScript (eingebautes Aufklapp-Element), wie im Tech Design festgehalten.
- Ungültige Filterwerte führen zurück zur ungefilterten Übersicht mit einem Hinweis.
- Das Filterformular hat kein `@csrf`: Es ist ein GET-Formular ohne Datenänderung; ein Token würde in der Adresszeile landen.
- Dokumente, deren Typ fehlt oder unbekannt ist, erscheinen im Abschnitt ihres Ordners; liegt die Datei in keinem Typ-Ordner, unter „Nicht zugeordnet".
- Die Meldung zu einem Dokument verlinkt auch dann auf seine Ansicht, wenn das Frontmatter nicht lesbar ist (die Ansicht zeigt dann Pfad und Fehler).

**Geprüft:** 57 Pest-Tests (`tests/Feature/PROJ-24-KnowledgeOverviewTest.php`), gesamte Suite 305 grün. Seiten per Abruf gegen die echte Knowledge Base geprüft (Übersicht, Filter, Dokumentansichten, unbekannter Pfad, Pfad mit `..`). Übersicht mit 200 Dokumenten rendert unter einer Sekunde.

**Nicht geprüft:** Darstellung im Browser, Lesbarkeit des Dokumenttexts, Kopier-Button mit und ohne HTTPS, Verhalten bei 768 und 1440 px.

## QA Test Results

**Tested:** 2026-10-02
**App URL:** http://localhost:8081/knowledge
**Tester:** QA Engineer (AI)

**Prüfmethode und Grenzen:** 68 Pest-Tests (57 aus der Umsetzung, 11 neue aus der QA), ein Erkundungslauf mit präparierten Links, Dateinamen, Abfrage-Parametern und überlangem Markdown, dazu Abrufe gegen die echte Knowledge Base (15 Dokumente). Der Tester hatte **keinen Browser**. Der Nutzer hat bestätigt, dass die Seiten gut aussehen. Nicht vom Tester geprüft: das tatsächliche Verhalten des Kopier-Buttons (mit und ohne HTTPS), Cross-Browser und die Breiten 375/768/1440 px.

Legende: [x] bestanden · [~] umgesetzt laut Code, Verhalten nicht im Browser geprüft

### Acceptance Criteria Status

#### Navigation (2/2)
- [x] Zweiter Navigationspunkt „Knowledge" mit Icon
- [x] Auf Übersicht und Dokumentansicht aktiv, „Ticket analysieren" nicht

#### Dokumentliste (8/8)
- [x] Gruppiert nach Typ in Rangfolge, innerhalb nach ID
- [x] Zeile mit ID, Titel, Status, Geltungsbereich; leerer Geltungsbereich als „alle"
- [x] Badges „Entwurf", „Aktiv", „Veraltet"
- [x] Veraltete Dokumente abgeschwächt und als „Wird nicht verwendet" gekennzeichnet
- [x] Fehler-Badge bzw. Warn-Badge mit Anzahl
- [x] Datei mit nicht lesbarem Frontmatter erscheint mit Dateipfad und Fehler-Badge
- [x] Kein leerer Abschnitt für Typen ohne Dokumente
- [x] Klick öffnet die Dokumentansicht

#### Kennzahlen und Wissensstand (2/2)
- [x] Anzahl gesamt, verwendbar, Entwurf, aktiv
- [x] Wissensstand mit Hinweis auf uncommittete Änderungen bzw. „unbekannt"

#### Filter (3/3)
- [x] Typ, Status, „nur mit Meldungen"; Auswahl bleibt erhalten
- [x] Suche in ID und Titel, unabhängig von Groß- und Kleinschreibung
- [x] Leerzustand mit Zurücksetzen

#### Prüfergebnis (5/5)
- [x] Ohne Meldungen: Erfolgshinweis, Bereich eingeklappt
- [x] Mit Fehlern: aufgeklappt, Summen, Meldungen je Datei, Fehler vor Warnungen
- [x] Nur Warnungen: eingeklappt mit Anzahl
- [x] Meldung verlinkt auf das Dokument
- [x] Fehlender oder leerer Ordner: deutlicher Hinweis, Seite bleibt bedienbar

#### ID-Übersicht (1/3)
- [x] Text entspricht der Ausgabe von `knowledge:overview`
- [~] „Kopieren" legt den Text in die Zwischenablage und bestätigt mit „Kopiert"
- [~] Ohne Zugriff auf die Zwischenablage wird der Text markiert und ein Hinweis erscheint

#### Dokumentansicht (10/10)
- [x] Kopfdaten, Geltungsbereich, Themen, Dateipfad, Fingerabdruck
- [x] Permission-Felder; leere Wertgrenze als „keine Wertgrenze"
- [x] Formatierter Text statt Markdown-Quelltext
- [x] Vorhandene IDs anklickbar, fehlende bleiben Text
- [x] Hinweis bei Entwurf
- [x] Hinweis bei veraltetem Dokument
- [x] Meldungen über dem Text, Fehler vor Warnungen
- [x] Nicht lesbares Frontmatter: Pfad und Fehler, kein Text
- [x] Unbekannte ID oder Pfad: „Seite nicht gefunden"
- [x] Link zurück zur Übersicht mit erhaltenen Filtern

#### Sicherheit der Darstellung (2/2)
- [x] HTML und Skript-Code in Titel, Text und Frontmatter werden als Text gezeigt
- [x] Links öffnen in neuem Tab ohne Rückbezug; `javascript:`, `vbscript:` und `data:` werden nie zu Links

### Edge Cases Status
- [x] Zwei Dateien mit derselben ID: beide gelistet, beide über den Pfad erreichbar
- [x] Dokument ohne Titel oder ID: erscheint mit Dateipfad
- [x] 200 Dokumente: Übersicht rendert unter einer Sekunde
- [x] Gelöschte Datei: „Seite nicht gefunden"
- [x] Sehr langes oder tief verschachteltes Markdown rendert schnell
- [x] Bildverweis: Alternativtext, kein Abruf
- [x] Verweis auf sich selbst oder auf ein Dokument mit Fehlern: kein Link
- [x] Wissensstand unbekannt
- [x] Suchbegriff mit Sonderzeichen
- [x] Dateinamen mit Leerzeichen, Umlauten und `&`
- [~] Sehr langer Titel oder Pfad: Kürzungs- und Umbruchklassen vorhanden
- [~] JavaScript abgeschaltet: Liste, Filter, Aufklappen und Dokumentansicht sind serverseitig gerendert

### Security Audit Results
- [x] Pfad-Manipulation: `..`, kodierte Schrägstriche, absolute Pfade, README und Vorlagen ergeben „nicht gefunden"; der Pfad wird nie zum Öffnen einer Datei verwendet
- [x] XSS: Titel, Frontmatter, Text, Suchbegriff und ID-Übersicht werden maskiert; kein Ausbruch aus dem Kopier-Feld
- [x] Unsichere Links und Attribut-Tricks im Markdown werden nicht ausgeführt
- [x] Keine Abrufe von fremden Servern, auch nicht durch Bilder in Dokumenten
- [x] Filterwerte werden geprüft; Listen statt Text werden abgewiesen
- [x] Die Seiten ändern nichts; `POST` auf die Übersicht wird abgewiesen
- [x] Sicherheits-Header vorhanden
- [x] CSRF: kein datenänderndes Formular; das GET-Filterformular hat bewusst kein Token
- [x] Der Zurück-Link übernimmt keine fremde Herkunftsadresse (BUG-1, behoben)
- Authentifizierung: bewusst keine (PRD). Jeder im Netz sieht Regeln, Dateipfade und den Commit-Stand.

### Bugs Found

#### BUG-1: Zurück-Link kann auf eine fremde Seite zeigen – BEHOBEN (2026-10-02)
- **Fix:** Als Rückweg gilt nur noch die eigene Übersicht der App samt Filtern (vollständige Adresse inklusive Server wird verglichen); alles andere führt zur ungefilterten Übersicht.
- **Severity:** Medium
- **Steps to Reproduce:**
  1. Von einer fremden Seite mit der Adresse `https://evil.example/knowledge` aus eine Dokumentansicht der App öffnen
  2. In der Dokumentansicht auf „Zur Übersicht" klicken
  3. Expected: Link führt zur Übersicht der App
  4. Actual: Link führt zurück auf `https://evil.example/knowledge`. Geprüft wird nur der Pfad der Herkunftsadresse, nicht der Server.
- **Folge:** Ein Angreifer kann eine täuschend echte Kopie der Übersicht unterschieben. Setzt voraus, dass ein Mitarbeiter über einen präparierten Link in die App kommt.
- **Priority:** Fix before deployment

#### BUG-2: Filter gehen verloren, wenn man von Dokument zu Dokument springt
- **Severity:** Low
- **Steps to Reproduce:**
  1. Übersicht filtern, ein Dokument öffnen, dort auf einen Verweis zu einem anderen Dokument klicken
  2. „Zur Übersicht" klicken
  3. Expected: gefilterte Übersicht
  4. Actual: ungefilterte Übersicht
- **Priority:** Nice to have

#### BUG-3: HTML-Kommentare im Dokument erscheinen als Text
- **Severity:** Low
- **Steps to Reproduce:**
  1. In einem Dokument `<!-- Notiz -->` schreiben
  2. Expected: wird nicht angezeigt
  3. Actual: erscheint wörtlich im Text. Folge der sicheren Umwandlung (alles HTML wird maskiert); die Vorlagen sind nicht betroffen, weil ihre Hinweise im Frontmatter stehen.
- **Priority:** Nice to have

#### BUG-4: Wertgrenze braucht die PHP-Erweiterung `intl` – BEHOBEN (2026-10-02)
- **Fix:** Die Wertgrenze wird ohne `intl` deutsch formatiert (35 €, 1.250 €, 49,90 €).
- **Severity:** Low
- **Steps to Reproduce:**
  1. App auf einem Server ohne `intl` betreiben und eine Permission mit Wertgrenze öffnen
  2. Expected: Seite wird angezeigt
  3. Actual (aus dem Code abgeleitet, lokal ist `intl` vorhanden): Fehlerseite
- **Priority:** Fix before deployment (oder als Voraussetzung in `/deploy` festhalten)

#### BUG-5: Abgewiesene Methoden zeigen eine englische Standardseite
- **Severity:** Low
- **Steps to Reproduce:** `POST /knowledge` → 405 mit Laravels englischer Seite. Bekannt aus PROJ-1 (dort BUG-4: weitere Fehlerseiten).
- **Priority:** mit PROJ-1 BUG-4 erledigen

### Beobachtungen (keine Bugs)
- Die echte Knowledge Base meldet sechs Warnungen: unbekanntes Feld `limit_basis` in zwei Permissions, Verweise auf noch nicht vorhandene `GLOSSARY-001` und `PRODUCT-001`.
- Ein Dokument mit ungültigem Status zeigt das Badge „Ohne Status"; der ungültige Wert steht in der Fehlermeldung.

### Automatisierte Tests
- `tests/Feature/PROJ-24-KnowledgeOverviewTest.php`: 75 Tests (57 aus der Umsetzung, 11 aus der QA, 7 zu den Bugfixes). Gesamte Suite: 323 bestanden, 0 fehlgeschlagen.
- Keine Unit-Tests: Die Markdown-Umwandlung ist über die Dokumentansicht vollständig abgedeckt.
- Regression: PROJ-1 (Navigationstest angepasst), PROJ-2 und PROJ-3 laufen grün.

### Summary
- **Acceptance Criteria:** 33/35 bestanden, 2 (Kopier-Button) nur im Code geprüft, 0 fehlgeschlagen
- **Bugs Found:** 5 total (0 critical, 0 high, 1 medium, 4 low); BUG-1 und BUG-4 am 2026-10-02 behoben, BUG-5 für PROJ-6 vorgemerkt, BUG-2 und BUG-3 bewusst offen
- **Security:** Pass nach Behebung von BUG-1
- **Production Ready:** YES
- **Recommendation:** Freigegeben. Der Kopier-Button sollte einmal im Browser ausprobiert werden.

## Deployment
_To be added by /deploy_
