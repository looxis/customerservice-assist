# Knowledge Base Design – Customer Service Assist App

## 1. Zweck

Die Knowledge Base ist die fachliche Source of Truth der Customer Service Assist App.

Sie beschreibt:

- was fachlich gilt,
- wie Produkte funktionieren,
- wie interne Prozesse ablaufen,
- welche Entscheidungen der Kundenservice treffen darf,
- wie typische Fälle beurteilt werden,
- wie mit Kunden kommuniziert werden soll.

Die Knowledge Base soll sowohl für Menschen als auch für die Anwendung gut nutzbar sein.

## 2. Ablage

Die Knowledge Base liegt im **gleichen Git-Repository wie die Assist App**.

Empfohlener Pfad:

```text
knowledge/
```

Bearbeitung:

- VS Code
- Markdown
- YAML-Frontmatter
- Git
- GitHub

Git ist die Versionshistorie.

## 3. Grundprinzipien

### 3.1 Menschenlesbar
Jede Datei muss auch ohne Spezialsoftware verständlich sein.

### 3.2 Versioniert
Jede fachliche Änderung ist über Git nachvollziehbar.

### 3.3 Strukturiert
Dokumente besitzen feste IDs und maschinenlesbare Metadaten.

### 3.4 Kuratiert
Historische Tickets werden nicht ungeprüft übernommen.

### 3.5 Kleine Wissenseinheiten
Ein Dokument soll ein klar umrissenes Thema behandeln.

### 3.6 Autorität ist explizit
Nicht jede Wissensart besitzt denselben Stellenwert.

### 3.7 Keine fachlichen Regeln im Prompt verstecken
Prompts definieren, **wie** mit Wissen gearbeitet wird. Knowledge definiert, **was fachlich gilt**.

## 4. Ordnerstruktur

```text
knowledge/
│
├── README.md
├── policies/
├── permissions/
├── products/
├── processes/
├── playbooks/
├── tone/
├── glossary/
├── examples/
│   ├── good/
│   └── bad/
└── templates/
```

## 5. Dokumenttypen

### 5.1 `policies/`

Verbindliche allgemeine Regeln.

Beispiele:
- Umgang mit personalisierten Produkten
- Erstattungen
- Nachbesserung
- Widerruf / Storno
- Kulanzgrundsätze
- Eskalationsgrundsätze

Policies beantworten:

> Welche allgemeine Regel gilt?

Beispiel-ID: `POLICY-001`

### 5.2 `permissions/`

Befugnisse und Entscheidungskompetenzen.

Beispiele:
- Wer darf eine kostenlose Neuanfertigung freigeben?
- Bis zu welchem Wert darf Kundenservice selbst entscheiden?
- Wann ist Produktionsleitung erforderlich?
- Wann ist Geschäftsführung erforderlich?

Permissions beantworten:

> Darf der aktuelle Mitarbeiter die fachlich empfohlene Maßnahme selbst entscheiden?

Beispiel-ID: `PERMISSION-001`

Kritische Grenzen sollen soweit sinnvoll maschinenlesbar im Frontmatter stehen.

### 5.3 `products/`

Produktwissen.

Für den Start bevorzugt **eine Datei pro Produkt**.

```text
products/
├── 3d-glass-photo.md
├── lunchbox.md
└── ...
```

Wenn eine Datei später zu groß wird, darf sie in einen Produktordner aufgeteilt werden.

Produktwissen umfasst typischerweise:
- Überblick
- Kundeneingaben
- Herstellungsprozess
- technische Grenzen
- Qualitätsmerkmale
- typische Kundenerwartungen
- typische Missverständnisse
- typische Reklamationen
- Prüfmöglichkeiten
- Besonderheiten

Beispiel-ID: `PRODUCT-001`

### 5.4 `processes/`

Interne Abläufe.

Beispiele:
- Reklamation prüfen
- Foto beim Kunden anfordern
- Nachbesserung vorbereiten
- Ersatzauftrag intern auslösen
- Produktionsfehler prüfen

Processes beantworten:

> Was tun wir intern und in welcher Reihenfolge?

Beispiel-ID: `PROCESS-001`

### 5.5 `playbooks/`

Konkrete wiederkehrende Fallmuster.

Beispiele:
- Produkt entspricht der Kundenkonfiguration
- Kunde erwartet eine andere Darstellung
- tatsächlicher Produktionsfehler
- Tippfehler des Kunden
- beschädigte Ware
- unzureichendes Ausgangsfoto
- Kunde fordert Erstattung statt Nachbesserung

Playbooks beantworten:

> Wie beurteilen und bearbeiten wir diesen konkreten Falltyp?

Beispiel-ID: `PLAYBOOK-001`

### 5.6 `tone/`

Kommunikations- und Tonalitätsregeln.

Beispiele:
- allgemeiner Kundenservice-Ton
- Reklamationen
- Ablehnungen
- Eskalationen
- Empathie
- No-Gos
- bevorzugte Formulierungen

Tone beantwortet:

> Wie kommunizieren wir die fachlich richtige Entscheidung?

Beispiel-ID: `TONE-001`

### 5.7 `glossary/`

Interne und produktspezifische Begriffe.

Beispiele:
- 3D-Modell
- Nachbesserung
- Personalisierung
- Kulanz
- Vorschau

Beispiel-ID: `GLOSSARY-001`

### 5.8 `examples/good/`

Kuratierte positive Referenzfälle.

Sie dienen als Beispiele für:
- richtige Entscheidung
- gute Erklärung
- gute Tonalität
- sinnvolle Rückfragen

Sie sind **keine verbindlichen Regeln**.

Beispiel-ID: `EXAMPLE-GOOD-001`

### 5.9 `examples/bad/`

Kuratierte negative Referenzfälle.

Sie dokumentieren:
- was falsch gemacht wurde,
- warum es falsch war,
- wie der Fall korrekt hätte bearbeitet werden sollen.

Beispiel-ID: `EXAMPLE-BAD-001`

## 6. Wissenshierarchie

Bei Widersprüchen gilt grundsätzlich:

1. Policies
2. Permissions
3. Produkt- und Prozesswissen
4. Playbooks
5. Beispiele

Ein Beispiel darf niemals eine aktuelle Policy überschreiben.

## 7. Allgemeines YAML-Frontmatter

```yaml
---
id: PLAYBOOK-001
title: Produkt entspricht der Kundenkonfiguration
type: playbook
status: active

products:
  - example-product

categories:
  - complaint

topics:
  - customer-configuration

priority: high
risk_level: medium

owner: customer-service
reviewed_by:

last_reviewed: 2026-10-01
---
```

## 8. Pflichtfelder

Mindestens:

```yaml
id:
title:
type:
status:
```

Empfohlene zusätzliche Felder:

```yaml
products:
categories:
topics:
priority:
risk_level:
owner:
reviewed_by:
last_reviewed:
```

## 9. Status

Erlaubte Startwerte:

```text
draft
active
deprecated
```

Nur `status: active` wird produktiv verwendet.

## 10. IDs

IDs dürfen sich nach Erstellung nicht mehr ändern.

Empfohlenes Schema:

```text
POLICY-001
PERMISSION-001
PRODUCT-001
PROCESS-001
PLAYBOOK-001
TONE-001
GLOSSARY-001
EXAMPLE-GOOD-001
EXAMPLE-BAD-001
```

## 11. Produktdatei – empfohlene Struktur

```markdown
---
id: PRODUCT-001
title: Produktname
type: product
status: draft
products:
  - product-slug
topics:
  - product
owner:
last_reviewed:
---

# Überblick

# Kundeneingaben und Konfiguration

# Interne Entscheidungen

# Herstellungsprozess

# Technische Grenzen

# Qualitätsmerkmale

# Häufige Kundenerwartungen

# Häufige Missverständnisse

# Typische Reklamationen

# Prüfung

# Konsequenz für den Kundenservice
```

## 12. Policy – empfohlene Struktur

```markdown
---
id: POLICY-001
title:
type: policy
status: draft
products:
categories:
topics:
priority: high
owner:
last_reviewed:
---

# Regel

# Hintergrund

# Gilt wenn

- ...

# Gilt nicht wenn

- ...

# Vorgehen

# Ausnahmen

# Eskalieren wenn

# Beispiele
```

## 13. Permission – empfohlene Struktur

```markdown
---
id: PERMISSION-001
title: Kostenlose Neuanfertigung
type: permission
status: draft

action: replacement
agent_allowed: true
max_value_eur:
approval_role:

topics:
  - replacement

owner:
last_reviewed:
---

# Befugnis

# Grenzen

# Freigabe erforderlich wenn

- ...

# Zuständige Rolle

# Hinweise
```

## 14. Process – empfohlene Struktur

```markdown
---
id: PROCESS-001
title:
type: process
status: draft
products:
categories:
topics:
owner:
last_reviewed:
---

# Ziel

# Auslöser

# Voraussetzungen

# Ablauf

1. ...

# Ergebnis

# Sonderfälle

# Eskalation
```

## 15. Playbook – empfohlene Struktur

```markdown
---
id: PLAYBOOK-001
title:
type: playbook
status: draft

products:
categories:
topics:

priority:
risk_level:

owner:
last_reviewed:
---

# Fallmuster

# Erkennungsmerkmale

- ...

# Typische Ursache

# Zu prüfen

1. ...

# Berechtigt wenn

- ...

# Nicht berechtigt wenn

- ...

# Noch nicht entscheidbar wenn

- ...

# Fehlende Informationen

# Empfohlene Maßnahme

# Befugnis

# Kommunikation

# No-Gos

# Beispiel
```

## 16. Tone – empfohlene Struktur

```markdown
---
id: TONE-001
title:
type: tone
status: draft
categories:
topics:
owner:
last_reviewed:
---

# Ziel

# Grundton

# Bevorzugte Formulierungen

- ...

# Vermeiden

- ...

# Reklamationen

# Ablehnungen

# Empathie
```

## 17. Gute Referenzfälle

```markdown
---
id: EXAMPLE-GOOD-001
title:
type: example-good
status: active

products:
categories:
topics:

related_knowledge:
  - POLICY-001
  - PLAYBOOK-001
---

# Kundensituation

# Relevante Fakten

- ...

# Richtige Bewertung

# Richtige Maßnahme

# Warum?

# Gute Kommunikationsstrategie

# Lernpunkt
```

Keine personenbezogenen Daten speichern.

## 18. Schlechte Referenzfälle

```markdown
---
id: EXAMPLE-BAD-001
title:
type: example-bad
status: active

products:
categories:
topics:

related_knowledge:
  - POLICY-001
  - PLAYBOOK-001
---

# Kundensituation

# Was wurde gemacht?

# Warum war das falsch?

# Richtige Bewertung

# Richtige Maßnahme

# Ursache der Fehlentscheidung

# Lernpunkt
```

## 19. Glossar

```markdown
---
id: GLOSSARY-001
title: Begriff
type: glossary
status: active
topics:
owner:
last_reviewed:
---

# Begriff

# Bedeutung

# Abgrenzung

# Relevanz für den Kundenservice
```

## 20. Retrieval im MVP

Die erste Version soll kein komplexes semantisches Retrieval benötigen.

Empfohlene Auswahl:

### Immer / Core Knowledge
- relevante globale Policies
- relevante Permissions
- allgemeine Tonalitätsregeln

### Fallabhängig
- Produktwissen des erkannten Produkts
- passende Prozesse
- passende Playbooks

### Optional
- wenige passende kuratierte Beispiele

Auswahl zunächst anhand strukturierter Metadaten:
- `type`
- `status`
- `products`
- `categories`
- `topics`

Später können Embeddings oder semantische Suche hinzukommen.

## 21. Keine komplette Roh-Ticket-Historie als Knowledge

Nicht empfohlen:

```text
knowledge/tickets/123456.md
knowledge/tickets/123457.md
```

Historische Tickets enthalten möglicherweise:
- alte Entscheidungen
- Fehler
- personenbezogene Daten
- inkonsistente Kommunikation
- nicht mehr gültige Regeln

Stattdessen werden nur fachlich geprüfte Erkenntnisse übernommen.

## 22. Knowledge und Evaluation strikt trennen

Gold-Testfälle gehören **nicht** in `knowledge/`.

Empfohlener eigener Projektbereich:

```text
evaluation/
├── cases/
└── expected-results/
```

Knowledge darf vom System verwendet werden. Evaluation darf nicht automatisch als Retrieval-Wissen dienen.

## 23. Bearbeitungsworkflow

Einfacher Start:

1. Datei in VS Code bearbeiten.
2. Frontmatter und Inhalt prüfen.
3. Git Commit.
4. Push nach GitHub.
5. Assist App synchronisiert / lädt neuen Knowledge-Stand.
6. Produktiv verwendet werden nur `active`-Dokumente.

Später möglich:
1. Branch erstellen.
2. Knowledge ändern.
3. Pull Request.
4. Fachlicher Review.
5. Merge.
6. automatischer Sync.

## 24. Wissenslücken-Workflow

Wenn ein Mitarbeiter relevantes Wissen vermisst:

```text
[ Wissenslücke melden ]
```

Gespeichert werden:
- Ticket-ID
- Analyse-ID
- Knowledge-Commit
- Kommentar
- ggf. betroffene Knowledge-ID

Mögliche Ergebnisse:
- neue Policy
- bestehende Policy präzisieren
- Produktwissen ergänzen
- neues Playbook
- Permission ergänzen
- Tone-Regel ergänzen
- kein Knowledge-Problem, sondern Prompt-/Retrieval-/Modellproblem

## 25. Qualitätsregel für neues Wissen

Bei jeder neuen Regel sollte gefragt werden:

1. Ist das wirklich allgemeines Wissen oder nur ein Einzelfall?
2. Ist die Aussage eindeutig?
3. Sind Ausnahmen dokumentiert?
4. Ist klar, wann die Regel gilt?
5. Ist klar, wann sie nicht gilt?
6. Gibt es widersprüchliche bestehende Knowledge?
7. Muss eine Permission ergänzt werden?
8. Muss ein Playbook aktualisiert werden?
9. Sollte ein Evaluation-Case ergänzt werden?

## 26. Knowledge entlang echter Fallmuster aufbauen

Nicht sofort das gesamte Unternehmen dokumentieren.

Empfohlen:

1. häufiges schwieriges Fallmuster auswählen,
2. Playbook schreiben,
3. dafür notwendiges Produktwissen ergänzen,
4. relevante Policy ergänzen,
5. Permission dokumentieren,
6. an echten historischen Fällen testen,
7. Knowledge verbessern,
8. nächstes Fallmuster.

## 27. Empfohlener Startbestand

Vor dem ersten produktiven Test reichen ungefähr:

- 5–10 zentrale Policies
- erste Permissions
- 1 wichtiges Produkt gut dokumentiert
- 3–5 häufige Playbooks
- allgemeine Tone-Regeln
- 5–10 gute Referenzfälle
- 3–5 schlechte Referenzfälle
- kleines Glossar

Danach iterativ ausbauen.
