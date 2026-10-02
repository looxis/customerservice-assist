# PROJ-2: Knowledge-Authoring-Kit

## Status: Architected
**Created:** 2026-10-02
**Last Updated:** 2026-10-02

## Dependencies
- None

Grundlagen: `docs/KNOWLEDGE_BASE_DESIGN.md` (feste Vorgabe für Struktur und Dokumenttypen) und `docs/KNOWLEDGE_AUTHORING_GUIDE.md` (Regeln für das Verfassen, bereits vorhanden). Im Ordner `knowledge/` liegen schon acht Dateien (zwei Permissions, sechs Policies), die mit einem KI-Chat im Browser entstanden sind.

## User Stories
- Als Autor der Knowledge Base möchte ich eine vollständige Ordnerstruktur vorfinden, damit jede neue Datei einen eindeutigen Platz hat.
- Als Autor möchte ich für jeden Dokumenttyp eine Vorlage mit allen Frontmatter-Feldern und Abschnitten, damit ich oder ein KI-Chat nichts vergessen und alle Dateien gleich aufgebaut sind.
- Als Autor möchte ich im Knowledge-Ordner eine kurze Einstiegsseite, damit auch jemand ohne Vorwissen versteht, was wo liegt, was Vorrang hat und wie eine Datei entsteht.
- Als Autor möchte ich weiterhin mit meinem Browser-Chat schreiben können, damit ich nicht an dieses Repository gebunden bin.
- Als Autor möchte ich als Ausweichweg einen KI-Skill direkt im Repository, der mich befragt und die Datei korrekt formatiert am richtigen Ort ablegt, damit ich auch ohne den Browser-Chat (neuer Chat, verlorener Verlauf) fehlerfrei weiterarbeiten kann.
- Als Autor möchte ich, dass der Skill die nächste freie ID und vorhandene Schlagwörter aus den Dateien selbst ermittelt, damit keine doppelten IDs oder abweichenden Schreibweisen entstehen.

## Out of Scope
- Einlesen, Validieren und der Prüfbefehl für Knowledge-Dateien – PROJ-3.
- Automatisch erzeugte Übersicht der vergebenen IDs und Schlagwörter (zum Einfügen in den Browser-Chat) – PROJ-3.
- Auswahl von Dokumenten für eine Analyse – PROJ-4.
- Verwendung von Entwurfs-Wissen in der App und Bestätigen per Klick – PROJ-4, PROJ-10 und PROJ-23.
- Die fachlichen Inhalte selbst (Policies, Playbooks, Produktwissen). Sie entstehen parallel durch den Autor.
- Der Ordner `evaluation/` für Gold-Testfälle – PROJ-13.
- Eine Oberfläche in der App zum Bearbeiten von Knowledge-Dateien.
- Änderungen an `docs/KNOWLEDGE_BASE_DESIGN.md`.

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Ordnerstruktur
- [ ] Angenommen das Repository ist frisch ausgecheckt, wenn der Autor `knowledge/` öffnet, dann existieren die Ordner `policies`, `permissions`, `products`, `processes`, `playbooks`, `tone`, `glossary`, `examples/good`, `examples/bad` und `templates`, auch wenn sie noch keine Inhalte haben.
- [ ] Angenommen im Ordner liegen bereits Knowledge-Dateien, wenn die Struktur angelegt wird, dann bleiben diese Dateien inhaltlich unverändert.

### Einstiegsseite
- [ ] Angenommen der Autor öffnet `knowledge/README.md`, wenn er sie liest, dann findet er: Zweck der Knowledge Base, die Ordner mit je einem Satz, die Rangfolge bei Widersprüchen (Policies vor Permissions vor Produkt-/Prozesswissen vor Playbooks vor Beispielen), die Bedeutung von `draft`, `active` und `deprecated`, den Ablauf vom Schreiben bis zum Commit und Verweise auf Guide, Design-Dokument und Vorlagen.
- [ ] Angenommen die Einstiegsseite wird gelesen, wenn der Autor nach den Verfassensregeln sucht, dann verweist sie auf `docs/KNOWLEDGE_AUTHORING_GUIDE.md`, statt die Regeln zu wiederholen.

### Vorlagen
- [ ] Angenommen der Autor öffnet `knowledge/templates/`, wenn er die Dateien durchsieht, dann findet er genau eine Vorlage je Dokumenttyp: `policy`, `permission`, `product`, `process`, `playbook`, `tone`, `glossary`, `example-good`, `example-bad`.
- [ ] Angenommen eine Vorlage wird geöffnet, wenn der Autor das Frontmatter betrachtet, dann enthält es die Pflichtfelder (`id`, `title`, `type`, `status`), alle für den Typ vorgesehenen Felder aus dem Design-Dokument sowie `customer_types`, `sales_channels` und `related_knowledge`, und `status` steht auf `draft`.
- [ ] Angenommen eine Vorlage wird geöffnet, wenn der Autor den Textteil betrachtet, dann enthält er die Abschnittsüberschriften des jeweiligen Typs aus dem Design-Dokument und beginnt mit einer Zeile für den Geltungsbereich.
- [ ] Angenommen die Permission-Vorlage wird geöffnet, wenn der Autor das Frontmatter betrachtet, dann enthält es zusätzlich `action`, `agent_allowed`, `max_value_eur` und `approval_role`.
- [ ] Angenommen eine Vorlage wird unverändert in ihren Zielordner kopiert, wenn sie als YAML mit Markdown gelesen wird, dann ist sie syntaktisch gültig (leere Felder sind leer, nicht mit Platzhaltertext gefüllt, der als Wert missverstanden werden kann).
- [ ] Angenommen die Vorlagen liegen in `knowledge/templates/`, wenn jemand sie für echtes Wissen halten könnte, dann sind sie durch eine erkennbare Platzhalter-ID und einen Hinweis am Dateianfang eindeutig als Vorlage gekennzeichnet.

### KI-Skill im Repository
- [ ] Angenommen der Autor startet den Skill mit einem Thema oder einem Fall, wenn der Skill beginnt, dann liest er zuerst Guide, Design-Dokument und die vorhandenen Knowledge-Dateien.
- [ ] Angenommen der Autor beschreibt ein Thema, wenn der Skill den Dokumenttyp nicht eindeutig bestimmen kann oder mehrere Themen vermischt sind, dann schlägt er Typ bzw. Aufteilung vor und lässt den Autor entscheiden, bevor er schreibt.
- [ ] Angenommen der Skill erfasst ein Dokument, wenn Angaben fehlen (Wertgrenze, Ausnahme, zuständige Rolle, Geltungsbereich), dann fragt er einzeln nach und setzt keine Annahme ein.
- [ ] Angenommen es existieren bereits Dokumente eines Typs, wenn der Skill ein neues anlegt, dann vergibt er die nächste freie ID dieses Typs anhand der höchsten vorhandenen Nummer und verwendet keine frühere ID erneut.
- [ ] Angenommen es existieren bereits Werte für `products`, `categories` oder `topics`, wenn der Skill ein Schlagwort braucht, dann verwendet er einen vorhandenen Wert oder nennt einen neuen Wert ausdrücklich als neu.
- [ ] Angenommen der Autor hat den Inhalt bestätigt, wenn der Skill die Datei schreibt, dann liegt sie im Ordner ihres Typs, trägt einen Dateinamen nach dem Schema des Guides und hat `status: draft`.
- [ ] Angenommen der Skill schreibt eine Datei, wenn der Autor sie öffnet, dann entspricht ihr Aufbau der Vorlage des Typs und sie enthält keine personenbezogenen Daten aus dem Gespräch.
- [ ] Angenommen für ein Produkt existiert bereits eine Produktdatei, wenn der Autor weiteres Wissen zu diesem Produkt erfasst, dann ergänzt der Skill die bestehende Datei, statt eine zweite anzulegen, und zeigt die Änderung vor dem Speichern.
- [ ] Angenommen der Skill hat eine Datei geschrieben, wenn er fertig ist, dann nennt er Pfad, ID und neu eingeführte Schlagwörter und setzt weder den Status auf `active` noch committet er ohne ausdrückliche Aufforderung.
- [ ] Angenommen der Autor hat parallel Dateien aus dem Browser-Chat ins Repository gelegt, wenn der Skill startet, dann berücksichtigt er sie bei IDs und Schlagwörtern, weil er den Stand von der Platte liest.

### Guide
- [ ] Angenommen ein KI-Chat außerhalb des Repositorys erhält `docs/KNOWLEDGE_AUTHORING_GUIDE.md` und `docs/KNOWLEDGE_BASE_DESIGN.md`, wenn er eine Datei verfasst, dann entsteht dieselbe Struktur wie aus den Vorlagen (Guide und Vorlagen widersprechen sich nicht).

## Edge Cases
- **Lücken in der ID-Folge** (z. B. POLICY-001, POLICY-003): Der Skill vergibt die Nummer nach der höchsten vorhandenen, er füllt keine Lücken.
- **Doppelte ID auf der Platte** (zwei Dateien mit derselben ID): Der Skill meldet den Konflikt und schreibt nicht, bis der Autor entschieden hat.
- **Dokument eines `deprecated`-Themas wird neu gebraucht:** neues Dokument mit neuer ID; das alte bleibt unverändert.
- **Regel unterscheidet sich je Kundenart oder Kanal:** Der Skill schlägt zwei Dokumente mit jeweils eigenem Geltungsbereich vor.
- **Autor will eine Regel, die schon in einem anderen Dokument steht:** Der Skill verweist auf die vorhandene ID und schlägt einen Verweis statt einer Wiederholung vor.
- **Autor schildert einen echten Fall mit Namen oder Bestellnummer:** Die Datei enthält den Sachverhalt ohne diese Angaben.
- **Leerer Ordner im Git-Repository:** Die Ordner bleiben auch ohne Inhalte erhalten.
- **Vorlagen im Knowledge-Ordner:** Sie dürfen später nicht als Wissen eingelesen werden (Anforderung an PROJ-3: `templates/` und `README.md` ausschließen).

## Technical Requirements (optional)
- Keine Änderung an der laufenden App; PROJ-2 liefert nur Dateien und einen Skill.
- Alle Dateien UTF-8, Zeilenenden LF.

## Open Questions
- [ ] Reicht `b2b` als Kundenart, oder brauchen Foto-Fachhändler/Reseller und LOOXIS-Pro eigene Werte? (POLICY-004 unterscheidet beide.) Zu klären spätestens im Spec von PROJ-4; betrifft Guide und Vorlagen.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Der Browser-Chat bleibt der Hauptweg zum Verfassen; der Skill im Repository ist der Ausweichweg | Der Chat kennt den Projektkontext und liefert bereits saubere Dateien; der Skill sichert ab, falls Chat oder Verlauf nicht mehr verfügbar sind | 2026-10-02 |
| Der Skill ermittelt IDs und Schlagwörter aus den Dateien auf der Platte | Einzige Quelle, die immer stimmt, egal auf welchem Weg Dateien entstanden sind | 2026-10-02 |
| Neue Dateien entstehen immer als `draft`; weder Chat noch Skill setzen `active` | Die fachliche Freigabe bleibt beim Autor; wie Entwürfe getestet und bestätigt werden, regelt PROJ-23 | 2026-10-02 |
| Vorlagen liegen in `knowledge/templates/` | Vorgabe aus dem Design-Dokument; sie sind dort für Mensch und Chat auffindbar | 2026-10-02 |
| Die automatische ID-Übersicht gehört zu PROJ-3, nicht zu PROJ-2 | Sie braucht denselben Datei-Leser wie die Validierung; bis dahin führt der Browser-Chat die IDs aus seinem Verlauf fort | 2026-10-02 |
| Die Einstiegsseite verweist auf den Guide, statt Regeln zu wiederholen | Eine einzige Stelle für die Verfassensregeln; sonst laufen zwei Texte auseinander | 2026-10-02 |
| Der Ordner `evaluation/` entsteht nicht in PROJ-2 | Er wird erst mit dem Testlauf (PROJ-13) gebraucht und soll strikt von `knowledge/` getrennt bleiben | 2026-10-02 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|
| Kein App-Code: PROJ-2 besteht nur aus Dateien (Ordner, Markdown, Skill-Definition) | Das Feature liefert Arbeitsmaterial für den Autor; die App liest Knowledge erst ab PROJ-3 | 2026-10-02 |
| Leere Ordner bleiben über eine Platzhalterdatei im Repository erhalten | Git speichert keine leeren Ordner; ohne Platzhalter fehlte die Struktur nach dem Auschecken | 2026-10-02 |
| Vorlagen heißen wie ihr Typ (`policy.md`, `example-good.md` …) | Eindeutig auffindbar, für Mensch, Chat und Skill | 2026-10-02 |
| Vorlagen tragen die reservierte Nummer 000 (z. B. `POLICY-000`) und einen Vorlagen-Hinweis als Kommentar im Frontmatter | Die Nummer 000 wird nie für echtes Wissen vergeben, so ist eine versehentlich kopierte, unbearbeitete Vorlage sofort erkennbar; der Hinweis im Frontmatter hält die Datei syntaktisch gültig | 2026-10-02 |
| Der Skill heißt `/knowledge` und liegt bei den übrigen Projekt-Skills | Gleicher Aufruf und gleiche Ablage wie `/write-spec`, `/qa` usw. | 2026-10-02 |
| Der Skill enthält keine eigenen Verfassensregeln, sondern liest Guide, Design-Dokument und Vorlagen bei jedem Start | Eine einzige Quelle für die Regeln; Browser-Chat und Skill können nicht auseinanderlaufen | 2026-10-02 |
| Der Skill ermittelt IDs und Schlagwörter durch Lesen der Dateien, ohne eigenes Programm | Reicht für den Ausweichweg und braucht nichts aus PROJ-3; sobald die automatische Übersicht aus PROJ-3 existiert, kann der Skill sie nutzen | 2026-10-02 |
| Der Skill schreibt nur in `knowledge/`, nie in `knowledge/templates/`, und committet nicht selbst | Vorlagen und Git-Historie bleiben in der Hand des Autors | 2026-10-02 |
| Der Guide bleibt in `docs/`, die Einstiegsseite im Knowledge-Ordner verlinkt ihn | Der Browser-Chat bekommt weiter genau zwei Dateien aus `docs/`; im Knowledge-Ordner liegt nichts, was später versehentlich als Wissen gelesen wird | 2026-10-02 |
| Ein kleiner automatischer Strukturtest sichert Ordner und Vorlagen ab | Verhindert, dass eine Vorlage oder ein Ordner unbemerkt verschwindet oder ein Pflichtfeld verliert | 2026-10-02 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

### Überblick
PROJ-2 ändert nichts an der laufenden App. Es entstehen Ordner, eine Einstiegsseite, neun Vorlagen und eine Skill-Definition. Weder `/frontend` noch `/backend` sind nötig; die Dateien werden direkt angelegt und anschließend mit `/qa` geprüft.

### A) Was entsteht

```
knowledge/
+-- README.md                 Einstiegsseite (neu)
+-- policies/                 6 Dateien vorhanden, bleiben unverändert
+-- permissions/              2 Dateien vorhanden, bleiben unverändert
+-- products/                 neu, leer
+-- processes/                neu, leer
+-- playbooks/                neu, leer
+-- tone/                     neu, leer
+-- glossary/                 neu, leer
+-- examples/
|   +-- good/                 neu, leer
|   +-- bad/                  neu, leer
+-- templates/                neu
    +-- policy.md
    +-- permission.md
    +-- product.md
    +-- process.md
    +-- playbook.md
    +-- tone.md
    +-- glossary.md
    +-- example-good.md
    +-- example-bad.md

Skill „/knowledge" (bei den übrigen Projekt-Skills)
+-- Ablauf: lesen -> Typ klären -> befragen -> Entwurf zeigen -> schreiben -> berichten

docs/KNOWLEDGE_AUTHORING_GUIDE.md   vorhanden, wird um den Verweis auf die Vorlagen ergänzt
```

### B) Aufbau einer Vorlage
Jede Vorlage hat zwei Teile:

- **Kopf (Frontmatter):** Vorlagen-Hinweis, dann die Felder des Typs. Pflichtfelder sind vorbelegt, soweit sie feststehen (Typ, Status `draft`, Platzhalter-ID mit der Nummer 000); alle übrigen Felder sind leer. Jede Vorlage enthält die Felder für Kundenart, Vertriebskanal und Verweise auf andere Dokumente. Die Permission-Vorlage enthält zusätzlich Aktion, Erlaubnis, Wertgrenze und Freigabe-Rolle.
- **Text:** eine Zeile für den Geltungsbereich, danach die Abschnittsüberschriften des Typs aus dem Design-Dokument, ohne Fülltext.

### C) Ablauf des Skills

```
Start mit Thema oder Fall
+-- 1. Lesen: Guide, Design-Dokument, Vorlagen, alle vorhandenen Knowledge-Dateien
+-- 2. Bestand ermitteln: vergebene IDs je Typ, vorhandene Produkt-, Kategorie- und Themenwerte
|       +-- doppelte ID gefunden -> melden und anhalten
+-- 3. Typ bestimmen
|       +-- unklar oder mehrere Themen -> Aufteilung vorschlagen, Autor entscheidet
|       +-- Regel steht schon in anderem Dokument -> Verweis statt Wiederholung vorschlagen
+-- 4. Befragen, eine Frage nach der anderen, entlang der Abschnitte der Vorlage
|       +-- immer: Geltungsbereich (Kundenart, Kanal)
|       +-- fehlende Angabe -> nachfragen, nie annehmen
+-- 5. Entwurf im Gespräch zeigen, Autor bestätigt oder korrigiert
+-- 6. Schreiben: Zielordner des Typs, Dateiname nach Guide, nächste freie ID, Status draft
|       +-- Produktdatei existiert schon -> bestehende Datei ergänzen, Änderung vorher zeigen
+-- 7. Berichten: Pfad, ID, neu eingeführte Schlagwörter; kein Commit, kein „active"
```

### D) Daten
Es wird nichts in der Datenbank gespeichert. Der einzige „Datenbestand" sind die Markdown-Dateien im Repository; Git ist ihre Historie.

### E) Prüfung (Umfang für `/qa`)
- Automatischer Strukturtest: alle Ordner vorhanden; genau neun Vorlagen; jede Vorlage hat die Pflichtfelder, Status `draft`, die Nummer 000, die Felder für Kundenart, Kanal und Verweise sowie die Abschnitte ihres Typs; die Permission-Vorlage hat ihre vier Zusatzfelder; die acht vorhandenen Knowledge-Dateien sind unverändert.
- Von Hand: Einstiegsseite lesen; den Skill an einem Beispiel durchspielen (neue Policy, zweites Dokument desselben Typs, vorhandenes Produkt ergänzen) und prüfen, dass ID, Ordner, Dateiname und Status stimmen.

Ob die Vorlagen fehlerfrei als YAML gelesen werden, kann erst der Leser aus PROJ-3 endgültig beweisen; bis dahin prüft der Strukturtest den Aufbau.

### F) Abhängigkeiten
Keine neuen Pakete.

### G) Übergaben an andere Features
- **PROJ-3** muss `knowledge/README.md` und `knowledge/templates/` vom Einlesen ausschließen und die Nummer 000 als ungültig für echtes Wissen behandeln.
- **PROJ-3** liefert später die automatische Übersicht; der Skill kann dann darauf umgestellt werden.

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
