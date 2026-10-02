# PROJ-3: Knowledge Base einlesen und prüfen

## Status: In Review
**Created:** 2026-10-02
**Last Updated:** 2026-10-02

## Dependencies
- Requires: PROJ-2 (Knowledge-Authoring-Kit) – Ordnerstruktur, Vorlagen und Regeln, gegen die geprüft wird

Grundlagen: `docs/KNOWLEDGE_BASE_DESIGN.md`, `docs/KNOWLEDGE_AUTHORING_GUIDE.md`. PROJ-3 hat keine eigene Oberfläche; die Anzeige in der App ist PROJ-24.

## User Stories
- Als Autor der Knowledge Base möchte ich nach dem Einfügen einer Datei mit einem Befehl sehen, ob sie korrekt aufgebaut ist, damit Formatfehler aus dem Browser-Chat auffallen, bevor sie eine Analyse verfälschen.
- Als Autor möchte ich zu jedem Problem die Datei und eine verständliche deutsche Meldung, damit ich es ohne Suchen beheben kann.
- Als Autor möchte ich eine automatisch erzeugte Übersicht der vergebenen IDs, der nächsten freien ID je Typ und aller verwendeten Schlagwörter, damit ich sie dem Browser-Chat geben kann und keine doppelten IDs oder abweichenden Schreibweisen entstehen.
- Als Kundenservice-Mitarbeiter möchte ich, dass eine einzelne fehlerhafte Datei die App nicht blockiert, damit ich auch dann Tickets bearbeiten kann, wenn der Autor nicht erreichbar ist.
- Als Verantwortlicher möchte ich, dass der verwendete Wissensstand eindeutig bestimmbar ist, auch wenn Dateien noch nicht committet sind, damit eine Fehlentscheidung später nachvollzogen werden kann.
- Als Entwickler der Folge-Features möchte ich eine einzige, verlässliche Quelle für alle gültigen Knowledge-Dokumente samt Status und Metadaten, damit Auswahl, Analyse und Anzeige nicht jeweils selbst Dateien lesen.

## Out of Scope
- Auswahl der für einen Fall relevanten Dokumente – PROJ-4.
- Seite in der App mit Dokumentliste, Prüfergebnis und Kopier-Button – PROJ-24.
- Anzeige von Quellen und Entwurfs-Hinweis im Analyseergebnis – PROJ-10.
- Speichern des Wissensstands bei einer Analyse – PROJ-11 (PROJ-3 liefert nur die Angaben).
- Bestätigen von Entwürfen per Klick – PROJ-23.
- Inhaltliche Prüfung (stimmt die Regel fachlich, widersprechen sich zwei Dokumente) – Aufgabe des Autors.
- Automatisches Korrigieren von Dateien. PROJ-3 meldet, ändert aber nichts.
- Bearbeiten von Knowledge-Dateien in der App.
- Semantische Suche, Embeddings – PROJ-22.
- Gold-Testfälle unter `evaluation/` – PROJ-13.

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Einlesen
- [ ] Angenommen im Ordner `knowledge/` liegen Markdown-Dateien in den Typ-Ordnern, wenn die Knowledge Base eingelesen wird, dann wird jede dieser Dateien als Dokument mit Frontmatter-Feldern und Textteil bereitgestellt.
- [ ] Angenommen es existieren `knowledge/README.md`, Dateien in `knowledge/templates/` und Nicht-Markdown-Dateien (z. B. `.gitkeep`), wenn eingelesen wird, dann werden sie nicht als Dokumente behandelt und erzeugen keine Meldung.
- [ ] Angenommen ein Dokument hat den Status `draft`, `active` oder `deprecated`, wenn eingelesen wird, dann ist der Status am Dokument erkennbar, und Abnehmer können gezielt „verwendbar" (`draft` und `active`) von `deprecated` unterscheiden.
- [ ] Angenommen ein Dokument hat leere Listenfelder (`products`, `categories`, `topics`, `customer_types`, `sales_channels`, `related_knowledge`), wenn eingelesen wird, dann werden sie als leere Liste bereitgestellt, was „gilt für alle" bedeutet.
- [ ] Angenommen ein Listenfeld enthält nur einen einzelnen Wert statt einer Liste, wenn eingelesen wird, dann wird er als Liste mit einem Eintrag behandelt.
- [ ] Angenommen eine Datei wurde gerade geändert oder neu eingefügt, wenn danach eingelesen wird, dann ist der neue Stand sichtbar, ohne dass die App neu gestartet werden muss.

### Prüfung: Fehler (Dokument wird nicht verwendet)
- [ ] Angenommen eine Datei hat kein Frontmatter oder das Frontmatter ist kein gültiges YAML, wenn geprüft wird, dann wird ein Fehler mit Dateipfad gemeldet und das Dokument nicht verwendet.
- [ ] Angenommen eines der Pflichtfelder `id`, `title`, `type`, `status` fehlt oder ist leer, wenn geprüft wird, dann wird je Feld ein Fehler gemeldet.
- [ ] Angenommen `type` ist keiner der neun erlaubten Typen oder `status` keiner der drei erlaubten Werte, wenn geprüft wird, dann wird ein Fehler mit den erlaubten Werten gemeldet.
- [ ] Angenommen die `id` passt nicht zum Schema ihres Typs (z. B. `POLICY-001`, dreistellig) oder trägt die Nummer 000, wenn geprüft wird, dann wird ein Fehler gemeldet.
- [ ] Angenommen zwei Dateien tragen dieselbe `id`, wenn geprüft wird, dann wird für beide ein Fehler gemeldet, der die jeweils andere Datei nennt, und keines der beiden Dokumente wird verwendet.
- [ ] Angenommen der Typ eines Dokuments passt nicht zu dem Ordner, in dem es liegt, wenn geprüft wird, dann wird ein Fehler gemeldet.
- [ ] Angenommen `customer_types`, `sales_channels` oder `categories` enthalten einen Wert außerhalb der festen Wertelisten, wenn geprüft wird, dann wird ein Fehler mit den erlaubten Werten gemeldet.
- [ ] Angenommen ein Dokument vom Typ `permission` hat kein `action` oder `agent_allowed` ist weder `true` noch `false`, oder `max_value_eur` ist gesetzt, aber keine Zahl, wenn geprüft wird, dann wird ein Fehler gemeldet.
- [ ] Angenommen der Textteil eines Dokuments ist leer, wenn geprüft wird, dann wird ein Fehler gemeldet.

### Prüfung: Warnungen (Dokument wird verwendet)
- [ ] Angenommen `related_knowledge` oder der Text verweist auf eine ID, die es nicht gibt oder die `deprecated` ist, wenn geprüft wird, dann wird eine Warnung mit der betroffenen ID gemeldet.
- [ ] Angenommen ein Dokument hat den Status `active` und enthält einen Abschnitt „Noch zu klären", wenn geprüft wird, dann wird eine Warnung gemeldet.
- [ ] Angenommen ein Playbook oder Process hat keine `categories`, wenn geprüft wird, dann wird eine Warnung gemeldet.
- [ ] Angenommen der Dateiname entspricht nicht dem Schema des Guides oder passt nicht zur `id`, wenn geprüft wird, dann wird eine Warnung gemeldet.
- [ ] Angenommen ein Wert in `products` oder `topics` ist kein Slug (Großbuchstaben, Leerzeichen, Umlaute), wenn geprüft wird, dann wird eine Warnung gemeldet.
- [ ] Angenommen ein `products`-Wert hat keine Produktdatei in `knowledge/products/`, wenn geprüft wird, dann wird eine Warnung gemeldet.
- [ ] Angenommen das Frontmatter enthält ein unbekanntes Feld (z. B. Tippfehler `catagories`), wenn geprüft wird, dann wird eine Warnung mit dem Feldnamen gemeldet.
- [ ] Angenommen ein Dokument enthält Muster, die auf personenbezogene Daten hindeuten (E-Mail-Adresse, lange Ziffernfolge wie eine Bestell- oder Telefonnummer), wenn geprüft wird, dann wird eine Warnung gemeldet.
- [ ] Angenommen eine Permission hat `agent_allowed: true`, aber weder `max_value_eur` noch einen Abschnitt, der die Grenze nennt, wenn geprüft wird, dann wird keine Warnung gemeldet (eine Befugnis ohne Wertgrenze ist zulässig).

### Verhalten für die App
- [ ] Angenommen eine von mehreren Dateien ist fehlerhaft, wenn die App die Knowledge Base verwendet, dann stehen alle fehlerfreien Dokumente zur Verfügung und die fehlerhafte fehlt, ohne dass ein Fehler die Nutzung abbricht.
- [ ] Angenommen der Ordner `knowledge/` fehlt oder ist leer, wenn die App die Knowledge Base verwendet, dann erhält sie eine leere Dokumentliste und eine eindeutige Meldung, kein Absturz.
- [ ] Angenommen es gibt nur Warnungen, wenn die App die Knowledge Base verwendet, dann werden alle betroffenen Dokumente normal verwendet.

### Prüfbefehl
- [ ] Angenommen der Autor führt den Prüfbefehl aus, wenn alle Dateien fehlerfrei sind, dann sieht er die Zahl der Dokumente je Typ und Status sowie „keine Fehler" und der Befehl endet erfolgreich.
- [ ] Angenommen es gibt Fehler oder Warnungen, wenn der Autor den Prüfbefehl ausführt, dann sieht er sie nach Datei gruppiert, Fehler vor Warnungen, jeweils mit deutscher Meldung, und am Ende die Summen.
- [ ] Angenommen es gibt mindestens einen Fehler, wenn der Prüfbefehl endet, dann meldet er einen Fehlschlag an das aufrufende Programm; bei reinen Warnungen meldet er Erfolg.
- [ ] Angenommen der Autor möchte Warnungen wie Fehler behandeln (z. B. vor einem Deployment), wenn er den Befehl mit einer strengen Option aufruft, dann meldet der Befehl auch bei Warnungen einen Fehlschlag.

### ID-Übersicht
- [ ] Angenommen der Autor ruft die Übersicht ab, wenn Dokumente vorhanden sind, dann enthält sie je Typ die vergebenen IDs mit Titel und die nächste freie ID sowie alle verwendeten Werte von `products`, `topics`, `categories`, `customer_types` und `sales_channels`.
- [ ] Angenommen ein Typ hat noch keine Dokumente, wenn die Übersicht erzeugt wird, dann wird für ihn „noch keine" und als nächste freie ID die Nummer 001 genannt.
- [ ] Angenommen eine ID ist `deprecated` oder ihre Datei fehlerhaft, wenn die nächste freie ID bestimmt wird, dann zählt die ID trotzdem als vergeben.
- [ ] Angenommen die Übersicht wird ausgegeben, wenn der Autor sie kopiert, dann hat sie genau das Format des Sitzungsstart-Blocks aus dem Guide und kann unverändert in den Browser-Chat eingefügt werden.

### Wissensstand
- [ ] Angenommen das Repository hat einen Git-Stand, wenn der Wissensstand abgefragt wird, dann enthält er den Kurz-Hash und das Datum des letzten Commits.
- [ ] Angenommen im Ordner `knowledge/` gibt es geänderte oder neue Dateien, die nicht committet sind, wenn der Wissensstand abgefragt wird, dann trägt er das Kennzeichen „mit uncommitteten Änderungen".
- [ ] Angenommen ein Dokument wird bereitgestellt, wenn sein Fingerabdruck abgefragt wird, dann ist er für gleichen Inhalt immer gleich und ändert sich bei jeder Änderung an Frontmatter oder Text.
- [ ] Angenommen Git ist nicht verfügbar oder das Verzeichnis ist kein Repository (z. B. auf dem Produktivserver ohne `.git`), wenn der Wissensstand abgefragt wird, dann wird „unbekannt" geliefert, die Fingerabdrücke je Dokument stehen trotzdem zur Verfügung, und nichts bricht ab.

## Edge Cases
- **Datei mit Windows-Zeilenenden oder BOM** (Copy and Paste aus dem Browser): wird korrekt gelesen; keine Meldung.
- **Frontmatter ohne schließende `---`-Zeile:** Fehler „Frontmatter nicht abgeschlossen", kein Absturz.
- **`---` im Textteil** (Markdown-Trennlinie): gehört zum Text, beendet das Dokument nicht.
- **Datumsfeld oder Zahl im Frontmatter** (`last_reviewed: 2026-10-01`, `max_value_eur: 35`): werden als Datum bzw. Zahl akzeptiert.
- **Titel mit Doppelpunkt ohne Anführungszeichen:** ergibt ungültiges YAML; die Fehlermeldung nennt die Zeile.
- **Markdown-Datei direkt in `knowledge/`** oder in einem unbekannten Unterordner: Fehler „liegt in keinem Typ-Ordner".
- **Unterordner innerhalb eines Typ-Ordners** (im Design-Dokument für große Produkte vorgesehen): Dateien werden gelesen und dem Typ des übergeordneten Ordners zugeordnet.
- **Zwei Dateien mit gleicher ID, eine davon `deprecated`:** trotzdem Fehler für beide; IDs werden nie wiederverwendet.
- **Sehr große Datei:** wird gelesen; überschreitet der Text eine festgelegte Länge, gibt es eine Warnung („behandelt vermutlich mehr als ein Thema").
- **Datei ohne Leserechte oder mit ungültiger Zeichenkodierung:** Fehler für diese Datei, die übrigen werden gelesen.
- **`:Zone.Identifier`-Dateien** (Windows-Download-Reste): werden ignoriert.
- **Gleichzeitiges Einfügen einer Datei während des Einlesens:** Im ungünstigsten Fall wird die halbe Datei als fehlerhaft gemeldet; beim nächsten Einlesen stimmt der Stand.
- **Verweis auf eine ID im Fließtext, die zufällig wie eine ID aussieht** (z. B. in einem Beispieltext): nur Warnung, nie Fehler.

## Technical Requirements (optional)
- Geschwindigkeit: Einlesen und Prüfen von 200 Dokumenten dauert unter einer Sekunde; die Analyse eines Tickets darf dadurch nicht spürbar langsamer werden.
- Meldungen deutsch, mit Dateipfad relativ zum Repository.
- Die festen Wertelisten (Typen, Status, Kundenarten, Kanäle, Kategorien) sind an genau einer Stelle definiert und ohne Codeänderung an mehreren Orten erweiterbar.
- Lesen und Prüfen ändern keine Datei.

## Open Questions
- [ ] Reicht `b2b` als Kundenart, oder brauchen Foto-Fachhändler/Reseller und LOOXIS-Pro eigene Werte? (übernommen aus PROJ-2; PROJ-3 prüft gegen die Werteliste, die Entscheidung fällt im Spec von PROJ-4)
- [x] Ab welcher Textlänge gilt ein Dokument als „zu groß"? → Warnung ab 8.000 Zeichen, als Konfigurationswert.
- [x] Liegt auf dem Produktivserver ein Git-Repository vor? → Die App kommt mit beidem zurecht: Git wird gefragt, sonst gilt ein beim Deployment hinterlegter Wert, sonst „unbekannt". Welcher Fall zutrifft, klärt `/deploy`.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Eine fehlerhafte Datei wird ausgelassen, alle übrigen Dokumente bleiben nutzbar | Ein Tippfehler im Frontmatter darf den Kundenservice nicht lahmlegen, auch wenn der Autor nicht erreichbar ist | 2026-10-02 |
| Trennung in Fehler (Dokument wird nicht verwendet) und Warnungen (Dokument wird verwendet) | Fehler sind Mängel, bei denen die App das Dokument nicht sicher einordnen kann; Warnungen sind Qualitätsmängel, die den Betrieb nicht stoppen sollen | 2026-10-02 |
| `draft` und `active` werden beide bereitgestellt, `deprecated` nie verwendet | Entwurfs-Wissen soll an Fällen getestet werden können (Entscheidung im PRD, Abweichung vom Design-Dokument) | 2026-10-02 |
| Doppelte IDs schließen beide Dokumente aus | Die App kann nicht entscheiden, welches das richtige ist; eine falsche Regel wäre schlimmer als eine fehlende | 2026-10-02 |
| Werte außerhalb der festen Listen für Kundenart, Kanal und Kategorie sind Fehler, bei `products` und `topics` nur Warnungen | Die festen Listen steuern die Auswahl hart; ein Tippfehler würde ein Dokument unsichtbar machen. Produkte und Themen wachsen dagegen laufend | 2026-10-02 |
| Wissensstand = letzter Commit + Kennzeichen für uncommittete Änderungen + Fingerabdruck je Dokument | Der Autor fügt Dateien per Copy and Paste ein und testet vor dem Commit; nur der Fingerabdruck macht solche Analysen exakt nachvollziehbar | 2026-10-02 |
| Prüfergebnis und ID-Übersicht gibt es als Befehl (PROJ-3) und als Seite in der App (PROJ-24) | Der Befehl dient Tests und Deployment, die Seite dem schnellen Blick nach dem Einfügen; die Seite ist ein eigener Bildschirm und damit ein eigenes Feature | 2026-10-02 |
| Die ID-Übersicht hat das Format des Sitzungsstart-Blocks aus dem Guide | Sie soll ohne Nacharbeit in den Browser-Chat kopiert werden können | 2026-10-02 |
| Vergebene IDs zählen auch dann, wenn die Datei fehlerhaft oder `deprecated` ist | IDs dürfen nie wiederverwendet werden | 2026-10-02 |
| Hinweis auf mögliche personenbezogene Daten nur als Warnung | Mustererkennung ist unscharf (auch Artikelnummern sind lange Ziffernfolgen); der Autor entscheidet | 2026-10-02 |
| PROJ-3 ändert nie eine Datei | Git und der Autor bleiben die einzigen, die Knowledge verändern | 2026-10-02 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|
| Reines Backend-Feature ohne Datenbank: Die Dateien werden bei Bedarf direkt von der Platte gelesen | Git und die Dateien sind die Quelle; eine Kopie in der Datenbank könnte veralten und bräuchte einen Abgleich. So ist eine eingefügte Datei sofort sichtbar | 2026-10-02 |
| Gelesen wird einmal pro Seitenaufruf bzw. Befehl, ohne dauerhaften Zwischenspeicher | Bei wenigen hundert kleinen Dateien dauert das Lesen Millisekunden; ein Cache brächte nur das Risiko veralteter Stände | 2026-10-02 |
| Eine zentrale Stelle („Knowledge-Bibliothek") stellt die Dokumente bereit; Auswahl, Analyse, Anzeige und Befehle fragen nur sie | Verhindert, dass mehrere Features Dateien unterschiedlich lesen oder prüfen | 2026-10-02 |
| Eigener Bereich `app/Knowledge/` für diese Logik | Fachlich zusammengehörig und von mehreren Features genutzt; neuer Ordner unter `app/`, daher ausdrücklich freizugeben | 2026-10-02 |
| Alle Wertelisten und Grenzwerte in einer Konfigurationsdatei (`config/knowledge.php`) | Typen mit Ordner und ID-Präfix, Status, Kundenarten, Kanäle, Kategorien, Pfad und Längengrenze stehen an genau einer Stelle; ein neuer Wert (z. B. weitere B2B-Gruppe) ist eine Zeile | 2026-10-02 |
| YAML wird mit `symfony/yaml` gelesen, als direkte Abhängigkeit | Ausgereifter Standard im Laravel-Umfeld und bereits indirekt installiert, bisher aber nur für die Entwicklung; für den Produktivbetrieb muss das Paket ausdrücklich aufgenommen werden | 2026-10-02 |
| Jede Prüfregel ist eine eigene kleine Einheit mit Schweregrad (Fehler oder Warnung) und deutscher Meldung | Regeln lassen sich einzeln testen, ergänzen und im Schweregrad ändern | 2026-10-02 |
| Fingerabdruck = SHA-256 über den normalisierten Dateiinhalt (ohne BOM, einheitliche Zeilenenden) | Gleicher Inhalt ergibt denselben Wert, egal ob die Datei unter Windows oder Linux gespeichert wurde | 2026-10-02 |
| Git-Stand wird zur Laufzeit bei Git erfragt; ist Git nicht verfügbar, gilt ein beim Deployment hinterlegter Wert, sonst „unbekannt" | Lokal und auf einem Server mit Repository stimmt der Stand automatisch; ohne Repository bleibt die App lauffähig (Antwort auf die offene Frage) | 2026-10-02 |
| Zwei Befehle: `knowledge:check` (mit Option `--strict`) und `knowledge:overview` | Prüfen und Übersicht sind zwei Aufgaben mit unterschiedlicher Ausgabe; die Übersicht soll ohne Prüfmeldungen kopierbar sein | 2026-10-02 |
| Warnung „Dokument zu groß" ab 8.000 Zeichen Text, als Konfigurationswert | Entspricht grob zwei Bildschirmseiten aus dem Guide; anpassbar, falls zu streng (Antwort auf die offene Frage) | 2026-10-02 |
| Tests arbeiten mit eigenen Beispieldateien; zusätzlich prüft ein Test, dass die echte Knowledge Base fehlerfrei ist | Jede Prüfregel bekommt ein fehlerhaftes Beispiel; der Zusatztest schlägt an, wenn eine eingefügte Datei einen Fehler hat | 2026-10-02 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

### Überblick
PROJ-3 ist ein reines Backend-Feature ohne Oberfläche und ohne Datenbank. Es entsteht eine zentrale „Knowledge-Bibliothek", die die Markdown-Dateien liest, prüft und allen anderen Features bereitstellt, dazu zwei Terminal-Befehle. `/frontend` entfällt; gebaut wird mit `/backend`.

### A) Bausteine

```
Knowledge-Bibliothek (zentrale Anlaufstelle)
+-- Datei-Leser
|   +-- findet alle Markdown-Dateien in den Typ-Ordnern
|   +-- überspringt README, templates/, Nicht-Markdown-Dateien
|   +-- trennt Frontmatter und Text, liest das YAML
+-- Dokument (ein Objekt je Datei)
|   +-- ID, Titel, Typ, Status
|   +-- Listen: Produkte, Kategorien, Themen, Kundenarten, Kanäle, Verweise
|   +-- Typ-Zusatzfelder (bei Permissions: Aktion, Erlaubnis, Wertgrenze, Rolle)
|   +-- Text, Dateipfad, Fingerabdruck
+-- Prüfer
|   +-- Regeln je Dokument (Pflichtfelder, Typ, Status, ID-Schema, Ordner, Wertelisten ...)
|   +-- Regeln über alle Dokumente (doppelte IDs, Verweise, fehlende Produktdateien)
|   +-- Ergebnis: Liste von Meldungen (Datei, Schweregrad, Text)
+-- Wissensstand
|   +-- letzter Commit (Kurz-Hash, Datum)
|   +-- Kennzeichen „mit uncommitteten Änderungen"
+-- Übersicht
    +-- vergebene IDs und nächste freie ID je Typ
    +-- verwendete Schlagwörter

Befehle
+-- knowledge:check       Meldungen nach Datei, Summen, Erfolg/Fehlschlag (--strict)
+-- knowledge:overview    Sitzungsstart-Block für den Browser-Chat

Konfiguration
+-- Pfad zur Knowledge Base, Typen mit Ordner und ID-Präfix, Status,
    Kundenarten, Kanäle, Kategorien, Längengrenze, hinterlegter Commit-Stand
```

### B) Was die Bibliothek anderen Features anbietet

| Frage | Antwort | Nutzer |
|---|---|---|
| Welche Dokumente sind verwendbar? | alle fehlerfreien mit Status `draft` oder `active` | PROJ-4, PROJ-9 |
| Welche Dokumente gibt es überhaupt? | alle gelesenen, auch fehlerhafte und `deprecated` | PROJ-24 |
| Welches Dokument hat die ID X? | Dokument mit Titel, Text, Status | PROJ-10 (Quellen einsehen) |
| Welche Fehler und Warnungen gibt es? | Meldungen je Datei | Befehl, PROJ-24 |
| Wie ist der Wissensstand? | Commit, Kennzeichen, Fingerabdruck je Dokument | PROJ-11 (Protokoll) |
| Wie lautet die ID-Übersicht? | Text im Format des Guides | Befehl, PROJ-24 |

### C) Daten
Es wird nichts gespeichert. Die Bibliothek liest bei jedem Seitenaufruf bzw. Befehl frisch von der Platte; innerhalb eines Aufrufs nur einmal.

Ein Dokument besteht aus:
- Kennung: ID, Titel, Typ, Status
- Geltungsbereich und Schlagwörter: sechs Listen (leer heißt „gilt für alle")
- Zusatzfelder des Typs
- Text
- Herkunft: Dateipfad, Fingerabdruck des Inhalts

Eine Meldung besteht aus: Dateipfad, Schweregrad (Fehler oder Warnung), deutscher Text.

### D) Ablauf beim Einlesen

```
Dateien finden
-> je Datei: lesen, Frontmatter abtrennen, YAML lesen
   -> nicht lesbar: Fehler-Meldung, Datei bleibt in der Gesamtliste als „fehlerhaft"
-> Regeln je Dokument anwenden
-> Regeln über alle Dokumente anwenden (doppelte IDs, Verweise)
-> Ergebnis: Dokumente + Meldungen
   verwendbar = ohne Fehler und nicht deprecated
```

### E) Wichtigste Entscheidungen in Kürze
- **Keine Datenbank, kein Zwischenspeicher.** Die Dateien sind klein und wenige; frisches Lesen ist schnell und kann nicht veralten.
- **Eine Anlaufstelle für alle.** Kein anderes Feature liest selbst Knowledge-Dateien.
- **Wertelisten an einer Stelle.** Eine weitere Kundenart oder Kategorie ist eine Zeile in der Konfiguration.
- **Git-Stand mit Rückfallebene.** Erst Git fragen, sonst den beim Deployment hinterlegten Wert nehmen, sonst „unbekannt". Die Fingerabdrücke funktionieren immer.
- **Die echte Knowledge Base wird mitgetestet.** Ein fehlerhaft eingefügtes Dokument lässt die Test-Suite fehlschlagen.

### F) Automatische Tests (Umfang für `/qa`)
- Je Prüfregel ein fehlerhaftes und ein korrektes Beispiel (eigene Beispieldateien nur für Tests).
- Randfälle: Windows-Zeilenenden, BOM, fehlendes Frontmatter-Ende, `---` im Text, einzelner Wert statt Liste, Unterordner, leerer oder fehlender Knowledge-Ordner.
- Fingerabdruck: gleich bei gleichem Inhalt, anders bei Änderung, unabhängig von Zeilenenden.
- Wissensstand: mit Git, mit uncommitteten Änderungen, ohne Git.
- Befehle: Ausgabe und Rückgabewert bei fehlerfrei, Warnungen, Fehlern, `--strict`; Übersicht mit und ohne Dokumente.
- Die echte Knowledge Base ergibt keine Fehler.

### G) Abhängigkeiten
- `symfony/yaml` – liest das YAML-Frontmatter (neu als direkte Abhängigkeit; bereits indirekt installiert)

### H) Übergaben an andere Features
- **PROJ-4** wählt aus den verwendbaren Dokumenten aus und entscheidet über die Werte für Kundenarten.
- **PROJ-24** zeigt Dokumente, Meldungen und Übersicht in der App.
- **PROJ-11** speichert Commit, Kennzeichen und Fingerabdrücke zur Analyse.
- **`/deploy`** hinterlegt den Commit-Stand, falls auf dem Server kein Git-Repository liegt, und kann `knowledge:check --strict` vor der Auslieferung laufen lassen.

## Implementation Notes (Backend)
**Stand:** 2026-10-02 – umgesetzt.

**Gebaut:**
- `config/knowledge.php`: Pfad, ausgeschlossene Dateien und Ordner, Typen mit Ordner und ID-Präfix, Status, Kundenarten, Kanäle, Kategorien, Längengrenze, hinterlegter Commit.
- `app/Knowledge/`: `KnowledgeLibrary` (zentrale Anlaufstelle, pro Request einmal gebunden), `KnowledgeReader`, `KnowledgeDocument`, `KnowledgeValidator`, `KnowledgeIssue`, `Severity`, `KnowledgeState`, `KnowledgeOverview`.
- Befehle: `knowledge:check` (Option `--strict`) und `knowledge:overview`.
- Neue direkte Abhängigkeit `symfony/yaml` (freigegeben).
- Guide und `knowledge/README.md` verweisen auf die beiden Befehle.

**Verwendung für Folge-Features:** `app(KnowledgeLibrary::class)` bzw. per Typ-Hinweis einfügen lassen. `usable()` liefert die verwendbaren Dokumente, `all()` alle gelesenen, `find($id)` ein fehlerfreies Dokument, `issues()`/`errors()`/`warnings()`/`issuesFor()` die Meldungen, `state()` den Wissensstand, `overview()` den Text für den Chat. Jedes Dokument trägt `fingerprint`.

**Abweichungen vom Spec:**
- Fehlender Knowledge-Ordner wird als Fehler gemeldet (Pfad `.`), ein leerer Ordner als Warnung. Das Spec verlangt nur „eine eindeutige Meldung".
- Die ID-Übersicht nennt die nächste freie ID in der Zeile des jeweiligen Typs und ergänzt den Sitzungsstart-Block des Guides um drei Zeilen für Kategorien, Kundenarten und Kanäle.
- Zusätzlicher Fehler „Feld muss eine einfache Liste sein" für verschachtelte Listenfelder.
- Hinweis auf personenbezogene Daten: E-Mail-Muster oder Ziffernfolge ab sieben Stellen.

**Geprüft:** 71 Pest-Tests (`tests/Feature/PROJ-3-KnowledgeLibraryTest.php`), gesamte Suite 218 grün. Beide Befehle gegen die echte Knowledge Base ausgeführt: 8 Dokumente, 8 verwendbar, keine Fehler, keine Warnungen. 200 Dokumente werden in unter einer Sekunde gelesen und geprüft.

**Offen für den Nutzer:** `KNOWLEDGE_COMMIT=` in `.env.example` dokumentieren (Datei ist für den Assistenten gesperrt). Der Wert bleibt leer, solange ein Git-Repository vorhanden ist.

## QA Test Results

**Tested:** 2026-10-02
**App URL:** nicht anwendbar (PROJ-3 hat keine Oberfläche; geprüft über die Befehle und die Bibliothek)
**Tester:** QA Engineer (AI)

**Prüfmethode und Grenzen:** 79 Pest-Tests (71 aus der Umsetzung, 8 neue aus der QA), ein Erkundungslauf mit 22 absichtlich schiefen Eingaben und beide Befehle gegen die echte Knowledge Base. Browser-, Responsive- und Cross-Browser-Tests entfallen. Nicht geprüft: Datei ohne Leserechte und gleichzeitiges Einfügen während des Lesens (beide im Container nicht verlässlich herstellbar).

### Acceptance Criteria Status

#### Einlesen (6/6)
- [x] Jede Markdown-Datei in einem Typ-Ordner wird als Dokument bereitgestellt
- [x] README, `templates/` und Nicht-Markdown-Dateien werden ohne Meldung übergangen
- [x] Status erkennbar; `draft` und `active` verwendbar, `deprecated` nicht
- [x] Leere Listenfelder sind leere Listen
- [x] Einzelner Wert zählt als Liste mit einem Eintrag
- [x] Geänderte Dateien sind beim nächsten Lesen sichtbar

#### Fehler (9/9)
- [x] Fehlendes oder ungültiges Frontmatter
- [x] Fehlende Pflichtfelder, je Feld eine Meldung
- [x] Unbekannter Typ oder Status mit erlaubten Werten
- [x] ID-Schema und Nummer 000
- [x] Doppelte ID schließt beide Dokumente aus, Meldung nennt die andere Datei
- [x] Typ passt nicht zum Ordner
- [x] Werte außerhalb der festen Listen
- [x] Permission-Felder
- [x] Leerer Textteil

#### Warnungen (9/9)
- [x] Verweis auf fehlende oder `deprecated` ID (Frontmatter und Text)
- [x] „Noch zu klären" in aktivem Dokument
- [x] Playbook oder Process ohne Kategorie
- [x] Dateiname passt nicht zu Schema oder ID
- [x] Kein Slug in `products` oder `topics`
- [x] Produktwert ohne Produktdatei
- [x] Unbekanntes Feld
- [x] Muster für personenbezogene Daten (mit Einschränkungen, siehe BUG-5)
- [x] Befugnis ohne Wertgrenze erzeugt keine Warnung

#### Verhalten für die App (3/3)
- [x] Eine fehlerhafte Datei beeinträchtigt die übrigen nicht
- [x] Fehlender oder leerer Ordner: leere Liste, eindeutige Meldung, kein Absturz
- [x] Dokumente mit Warnungen bleiben verwendbar

#### Prüfbefehl (4/4)
- [x] Fehlerfrei: Zahlen je Typ und Status, „keine Fehler", Erfolg
- [x] Meldungen nach Datei gruppiert, Fehler vor Warnungen, Summen
- [x] Fehlschlag bei Fehlern, Erfolg bei reinen Warnungen
- [x] `--strict` wertet Warnungen als Fehlschlag

#### ID-Übersicht (3/4)
- [x] Vergebene IDs mit Titel, nächste freie ID, alle Schlagwörter
- [x] Typ ohne Dokumente: „noch keine", Nummer 001
- [ ] BUG-1: Eine ID zählt nicht als vergeben, wenn das Frontmatter ihrer Datei nicht lesbar ist (bei `deprecated` und bei inhaltlichen Fehlern zählt sie korrekt)
- [x] Format des Sitzungsstart-Blocks aus dem Guide

#### Wissensstand (4/4)
- [x] Kurz-Hash und Datum des letzten Commits (auch live gegen das Repository geprüft)
- [x] Kennzeichen bei uncommitteten Änderungen im Knowledge-Ordner (live geprüft mit einer Probedatei)
- [x] Fingerabdruck stabil und unabhängig von Zeilenenden und BOM, ändert sich bei jeder Änderung
- [x] Ohne Git: hinterlegter Wert bzw. „unbekannt", nichts bricht ab

### Edge Cases Status
- [x] Windows-Zeilenenden und BOM
- [x] Frontmatter ohne schließende Zeile
- [x] `---` im Textteil
- [x] Datum und Zahl im Frontmatter
- [x] Titel mit Doppelpunkt: Fehler nennt die Zeile
- [x] Datei außerhalb eines Typ-Ordners oder in unbekanntem Ordner
- [x] Unterordner in einem Typ-Ordner (mit Ausnahme, siehe BUG-2)
- [x] Gleiche ID, eine davon `deprecated`: Fehler für beide
- [x] Sehr langes Dokument: Warnung
- [x] Ungültige Zeichenkodierung: Fehler für diese Datei
- [x] `:Zone.Identifier`-Dateien werden ignoriert
- [x] Verweis im Fließtext ist nur eine Warnung
- [ ] Datei ohne Leserechte: nicht geprüft
- [ ] Gleichzeitiges Einfügen während des Lesens: nicht geprüft

### Security Audit Results
- [x] Keine neue Angriffsfläche von außen: keine Route, kein Formular, keine Datenbank
- [x] YAML kann keine PHP-Objekte erzeugen und keine Konstanten auslesen (`!php/object`, `!php/const` getestet)
- [x] Fehlermeldungen enthalten nie Dateiinhalt
- [x] Die Befehle ändern keine Datei
- [x] Git wird mit festen Argumenten aufgerufen; Dateinamen oder Inhalte fließen nicht in den Aufruf
- [x] Keine Geheimnisse im Code; `KNOWLEDGE_COMMIT` ist kein Geheimnis
- [ ] BUG-6: Verknüpfung auf eine Datei außerhalb des Knowledge-Ordners wird gelesen
- Hinweis für PROJ-24 und PROJ-10: Titel und Text der Dokumente stammen aus Dateien und müssen bei der Anzeige maskiert werden.

### Bugs Found

#### BUG-1: ID einer Datei mit kaputtem Frontmatter gilt als frei
- **Severity:** Medium
- **Steps to Reproduce:**
  1. `knowledge/policies/policy-007-x.md` mit ungültigem YAML anlegen (z. B. Titel mit Doppelpunkt ohne Anführungszeichen), daneben eine gültige `POLICY-001`
  2. `knowledge:overview` ausführen
  3. Expected: `POLICY-007` gilt als vergeben, nächste freie ID ist `POLICY-008`
  4. Actual: nächste freie ID ist `POLICY-002`; die kaputte Datei taucht in der Übersicht nicht auf
- **Folge:** Der Chat vergibt eine ID, die nach der Reparatur der Datei doppelt ist. Dann fallen beide Dokumente aus.
- **Priority:** Fix before deployment

#### BUG-2: Manche Dateien werden kommentarlos übergangen
- **Severity:** Medium
- **Steps to Reproduce:**
  1. Datei als `Policy-001-a.MD` oder `policy-001-a.markdown` speichern, oder in einen Unterordner namens `templates` legen (z. B. `products/lunchbox/templates/x.md`)
  2. `knowledge:check` ausführen
  3. Expected: Datei wird gelesen oder als Problem gemeldet
  4. Actual: keine Meldung; die Datei fehlt einfach. Der Autor glaubt, die Regel sei im System.
- **Priority:** Fix before deployment

#### BUG-3: Leerzeile oder Code-Zaun vor dem Frontmatter schließt die Datei aus
- **Severity:** Low
- **Steps to Reproduce:**
  1. Datei aus dem Chat einfügen, dabei eine Leerzeile am Anfang oder die Zeile mit drei Backticks mitkopieren
  2. Expected: Leerzeilen am Anfang werden toleriert; bei einem Code-Zaun nennt die Meldung die Ursache
  3. Actual: Fehler „Das Frontmatter fehlt", Dokument wird nicht verwendet. Die Meldung ist verständlich, der Fehler bei Copy and Paste aber naheliegend.
- **Priority:** Fix in next sprint

#### BUG-4: Irreführende Meldungen bei einigen YAML-Problemen
- **Severity:** Low
- **Steps to Reproduce:**
  1. Feld doppelt angeben oder mit Tabulator einrücken → Meldung nennt als häufige Ursache den Doppelpunkt im Titel
  2. Titel, der wie ein Datum aussieht (`title: 2026-10-01`) → Meldung „Pflichtfeld `title` fehlt oder ist leer"
  3. Expected: Meldung nennt die tatsächliche Ursache
- **Priority:** Nice to have

#### BUG-5: Erkennung personenbezogener Daten ist lückenhaft
- **Severity:** Low
- **Steps to Reproduce:**
  1. Text mit „0171 123 456 78" oder mit Name und Anschrift → keine Warnung
  2. Text mit einer EAN (13 Ziffern) → Warnung, obwohl keine Personendaten
- **Hinweis:** Im Spec als unscharfe Mustererkennung beschrieben. Die Warnung ersetzt keine Durchsicht.
- **Priority:** Nice to have

#### BUG-6: Verknüpfung nach außen wird gelesen
- **Severity:** Low
- **Steps to Reproduce:**
  1. Im Knowledge-Ordner eine symbolische Verknüpfung `policies/x.md` auf eine Datei außerhalb anlegen
  2. Expected: wird übergangen oder gemeldet
  3. Actual: Die Zieldatei wird gelesen (und als fehlerhaft gemeldet, solange sie kein Frontmatter hat)
- **Hinweis:** Setzt Schreibzugriff auf das Repository voraus; wer den hat, kann die App ohnehin steuern.
- **Priority:** Nice to have

#### BUG-7: Negative Wertgrenze und Zählwort
- **Severity:** Low
- **Steps to Reproduce:**
  1. `max_value_eur: -5` wird akzeptiert
  2. Summenzeile lautet „1 Warnungen" statt „1 Warnung"
- **Priority:** Nice to have

### Automatisierte Tests
- `tests/Feature/PROJ-3-KnowledgeLibraryTest.php`: 79 Tests. Gesamte Suite: 226 bestanden, 0 fehlgeschlagen.
- Keine eigenen Unit-Tests: Die Logik ist über die Bibliothek vollständig in den Feature-Tests abgedeckt.
- Regression: Die Tests von PROJ-1 und PROJ-2 laufen weiter grün.

### Summary
- **Acceptance Criteria:** 38/39 bestanden, 1 teilweise fehlgeschlagen (BUG-1)
- **Bugs Found:** 7 total (0 critical, 0 high, 2 medium, 5 low)
- **Security:** Pass mit einem niedrigen Befund (BUG-6)
- **Production Ready:** Nach der Regel „keine Critical/High-Bugs" ja; BUG-1 und BUG-2 sollten vor der Nutzung mit dem Browser-Chat behoben werden
- **Recommendation:** BUG-1 und BUG-2 jetzt beheben, BUG-3 mitnehmen, den Rest zurückstellen

## Deployment
_To be added by /deploy_
