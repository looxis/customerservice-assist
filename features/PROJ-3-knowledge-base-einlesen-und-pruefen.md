# PROJ-3: Knowledge Base einlesen und prüfen

## Status: Planned
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
- [ ] Ab welcher Textlänge gilt ein Dokument als „zu groß"? Vorschlag: Warnung ab etwa 8.000 Zeichen; in `/architecture` festlegen.
- [ ] Liegt auf dem Produktivserver ein Git-Repository vor? Wenn nicht, muss der Commit-Stand beim Deployment mitgegeben werden (wie die Versionsangabe aus PROJ-1). Zu klären in `/architecture` bzw. `/deploy`.

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

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)
_To be added by /architecture_

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
