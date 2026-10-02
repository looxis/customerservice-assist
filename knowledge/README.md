# Knowledge Base

Die Knowledge Base ist die fachliche Source of Truth der Customer Service Assist App. Sie beschreibt, was fachlich gilt: Regeln, Befugnisse, Produkt- und Prozesswissen, typische Fallmuster und den Ton gegenüber Kunden. Die App wendet diese Inhalte bei der Analyse eines Tickets an.

Jede Datei ist Markdown mit einem YAML-Kopf (Frontmatter). Git ist die Versionshistorie.

## Ordner

| Ordner | Inhalt | Beantwortet |
|---|---|---|
| `policies/` | verbindliche allgemeine Regeln | Welche Regel gilt? |
| `permissions/` | Befugnisse und Wertgrenzen | Darf der Kundenservice das selbst entscheiden? |
| `products/` | Produktwissen, eine Datei pro Produkt | Wie entsteht das Produkt, wo liegen seine Grenzen? |
| `processes/` | interne Abläufe | Was tun wir intern, in welcher Reihenfolge? |
| `playbooks/` | wiederkehrende Fallmuster | Wie beurteilen wir diesen Falltyp? |
| `tone/` | Tonalitätsregeln | Wie sagen wir es dem Kunden? |
| `glossary/` | Begriffe | Was bedeutet das? |
| `examples/good/` | geprüfte gute Referenzfälle | Wie sieht eine gute Bearbeitung aus? |
| `examples/bad/` | geprüfte schlechte Referenzfälle | Was ging schief, und warum? |
| `templates/` | Vorlagen je Dokumenttyp | kein Wissen, wird von der App nicht gelesen |

## Rangfolge bei Widersprüchen

1. Policies
2. Permissions
3. Produkt- und Prozesswissen
4. Playbooks
5. Beispiele

Ein Beispiel überschreibt nie eine Policy.

## Status

| Status | Bedeutung |
|---|---|
| `draft` | Entwurf. Wird von der App mitverwendet und im Ergebnis als Entwurfs-Wissen gekennzeichnet, damit es an Fällen getestet werden kann. |
| `active` | fachlich geprüft und freigegeben |
| `deprecated` | gilt nicht mehr, wird nie verwendet; die Datei bleibt wegen ihrer ID erhalten |

Neue Dateien entstehen immer als `draft`. Auf `active` stellt nur der Autor um.

## So entsteht eine Datei

1. Thema oder echten Fall wählen. Nicht alles dokumentieren, sondern entlang echter Fälle vorgehen.
2. Mit KI-Hilfe verfassen, auf einem von zwei Wegen:
   - **Browser-Chat:** dem Chat `docs/KNOWLEDGE_AUTHORING_GUIDE.md` und `docs/KNOWLEDGE_BASE_DESIGN.md` geben, dazu die Ausgabe von `./vendor/bin/sail artisan knowledge:overview` (vergebene IDs und Schlagwörter). Ergebnis unter dem genannten Pfad speichern.
   - **Im Repository:** `/knowledge <Thema>` in Claude Code aufrufen. Der Skill befragt dich, vergibt die nächste freie ID und legt die Datei ab.
3. Datei lesen und fachlich prüfen; mit `./vendor/bin/sail artisan knowledge:check` den Aufbau prüfen lassen.
4. Committen und pushen.
5. Am Fall in der App testen, nachschärfen, danach `status: active` setzen.

Ohne KI: die passende Datei aus `templates/` in den Zielordner kopieren, umbenennen, die Nummer 000 durch die nächste freie ersetzen und ausfüllen.

## Regeln und Vorgaben

- Verfassensregeln (IDs, Dateinamen, Schlagwörter, Geltungsbereich, Abschlussprüfung): [`docs/KNOWLEDGE_AUTHORING_GUIDE.md`](../docs/KNOWLEDGE_AUTHORING_GUIDE.md)
- Struktur und Dokumenttypen: [`docs/KNOWLEDGE_BASE_DESIGN.md`](../docs/KNOWLEDGE_BASE_DESIGN.md)
- Vorlagen: [`templates/`](templates/)

## Was nicht hierher gehört

Aktuelle Bestelldaten, rohe Ticketverläufe, Kundendaten, Anweisungen an das Sprachmodell, Zugangsdaten und Testfälle mit erwarteter Lösung (die liegen getrennt unter `evaluation/`).
