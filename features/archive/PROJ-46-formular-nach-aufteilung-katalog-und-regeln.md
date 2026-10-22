# PROJ-46: Formular und Hinweise nach der Aufteilung von Produktkatalog und Wissensdatenbank

## Status: Approved
**Created:** 2026-10-22
**Last Updated:** 2026-10-22

## Dependencies
- Requires: PROJ-38 (Produktinformationen aus ProductForge), PROJ-42 (Pflichtangaben im Formular), PROJ-45 (Produkt ohne Wissen melden), PROJ-47 (Produktwissen migriert)
- Verwandt: PROJ-54 (Produktvarianten), PROJ-57 (Detailfragen), PROJ-32 (Expertenmodus des Formulars, PROJ-42)

## Ausgangslage
Seit ProductForge (PROJ-38) und der Migration (PROJ-47) gilt: Der **Produktkatalog** (ProductForge/Content Studio und EOCS) hält Produktangaben und Kundenservice-Wissen, die **Wissensdatenbank** unter `/knowledge` hält Regeln, Befugnisse und Abläufe. Das Formular „Vorschlag erstellen“ zeigt aber weiter zwei Felder zum Produkt: „Produkt“ (Katalogsuche) und „Wissensbereich“ (der Schlüssel, an dem Regeln hängen). Der Wissensbereich wird aus dem Katalogprodukt vorbelegt, und der Mitarbeiter muss „Produkt“ und „Bereich“ unterscheiden. Mehrere Texte sprechen noch von „Produktwissen“ oder „Wissen“, ohne zu sagen, ob Katalog oder Wissensdatenbank gemeint ist. Ein Katalogprodukt ohne passenden Wissensbereich wird mit einem orangen Hinweis versehen, obwohl das nach der Migration normal ist.

## User Stories
- Als Mitarbeiterin möchte ich nur das Produkt wählen und nichts zum „Wissensbereich“ entscheiden müssen, damit das Formular kürzer und eindeutiger ist.
- Als Mitarbeiterin möchte ich sehen, nach welchen Regeln die App sucht (zum Beispiel „Regeln für: Fototasse“), damit ich eine falsche Vorbelegung erkenne.
- Als Mitarbeiterin möchte ich Hinweise lesen, die eindeutig sagen, ob etwas im Produktkatalog oder in der Wissensdatenbank fehlt, damit ich weiß, was zu tun ist.
- Als Admin möchte ich auf der Seite „Wissenslücken“ unterscheiden, welche Produkte im Katalog fehlen, damit ich sie in den Produktkatalog aufnehmen kann.

## Out of Scope
- Änderungen an der Ableitung des Wissensbereichs aus dem Katalogprodukt (`KnowledgeAreaSuggester`): Er bleibt, wie er ist.
- Neue Produkte in den Produktkatalog aufnehmen oder Wissensbereich-Dateien anlegen (Aufgabe des Auftraggebers).
- Umbenennen von Dateien, IDs oder Frontmatter-Feldern in `knowledge/`.
- Das Verhalten von „Kein Produktbezug“ und die Pflicht, Kundengruppe und Produktangabe zu machen (PROJ-42).
- Änderungen an der KI-Logik außer dem erklärenden Zusatz im Fallabschnitt des Prompts.

## Wortwahl
**Produktkatalog (ProductForge/Content Studio & EOCS)**: Produktangaben und Kundenservice-Wissen des Produkts. **Wissensdatenbank** (auch „Knowledge“): Regeln, Befugnisse, Abläufe. Das allein stehende „Produktwissen“ oder „Wissen“ wird vermieden, wo es sich auf beides beziehen könnte.

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

**Formular, normal**
- [x] Angenommen ein Katalogprodukt ist gewählt und der Expertenmodus ist aus, wenn das Formular angezeigt wird, dann gibt es kein Feld „Wissensbereich“; stattdessen steht eine Zeile „Regeln für: <Bereich>“ mit dem vorbelegten Bereich.
- [x] Angenommen ein Katalogprodukt ist gewählt und es wurde kein Wissensbereich gefunden, wenn das Formular angezeigt wird, dann steht die neutrale Zeile „Es gelten die allgemeinen Regeln.“ ohne orangen Hinweis und ohne Satz zu einer Lücke.
- [x] Angenommen der vorbelegte Wissensbereich ist unsichtbar, wenn der Vorschlag erstellt wird, dann wird er wie bisher mit gesendet und bestimmt die Regeln.
- [x] Angenommen kein Katalogprodukt ist gewählt, wenn das Formular angezeigt wird, dann bleibt der Wissensbereich wie bisher im normalen Formular wählbar, ebenso „Produkt ohne hinterlegtes Wissen“ (neu beschriftet) und „Kein Produktbezug“.

**Formular, Expertenmodus**
- [x] Angenommen der Expertenmodus ist an, wenn ein Katalogprodukt gewählt ist, dann ist das Feld „Wissensbereich“ sichtbar, vorbelegt und änderbar wie heute.

**Begriffe im Formular und im Ergebnis**
- [x] Angenommen der Mitarbeiter tippt ein Produkt ein, das weder im Produktkatalog noch in der Wissensdatenbank steht, wenn der Eintrag in der Suche erscheint, dann lautet er „…“ – dazu ist weder im Produktkatalog (ProductForge/Content Studio & EOCS) noch in der Wissensdatenbank etwas hinterlegt. Du kannst die Bezeichnung trotzdem verwenden.
- [x] Angenommen ein so eingetipptes Produkt ist gewählt, wenn es als Marke erscheint, dann steht dort „(nicht im Produktkatalog)“, und der Hilfetext lautet „Zu diesem Produkt gibt es weder Katalogangaben noch Regeln …“.
- [x] Angenommen „Kein Produktbezug“ ist gewählt, wenn der Hinweis erscheint, dann nennt er, dass weder Produktangaben aus dem Produktkatalog noch produktbezogene Regeln berücksichtigt werden.
- [x] Angenommen ein Vorschlag nennt ein Produkt, das weder im Katalog noch in der Wissensdatenbank steht, wenn das Ergebnis angezeigt wird, dann lautet der Hinweis „Weder Katalogangaben noch Regeln zu „…““; die beiden anderen Fälle („Noch nicht in ProductForge gepflegt“, „Kein Kundenservice-Wissen im Produktkatalog zu …“) bleiben.
- [x] Angenommen das Ergebnis zeigt „❗ Kein Produktwissen berücksichtigt“ (ohne Produktwahl), wenn es angezeigt wird, dann bleibt diese Zeile unverändert.

**Lücken**
- [x] Angenommen die automatische Meldung für ein Produkt ohne Eintrag entsteht, wenn sie auf der Seite „Wissenslücken“ steht, dann lautet der Titel „Produkt „…“: weder Katalogangaben noch Regeln“, und der Kasten heißt „Produkte ohne Katalogangaben und ohne Regeln, nach Häufigkeit“.
- [x] Angenommen es gibt bereits automatische Meldungen mit dem alten Titel, wenn gleiche Produkte neu gemeldet werden, dann werden sie weiter mit den alten zusammengezählt (der interne Schlüssel bleibt unverändert).
- [x] Angenommen ein Katalogprodukt hat Daten, aber kein Kundenservice-Wissen, wenn ein Vorschlag erstellt wird, dann entsteht die Meldung für den Produktkatalog wie bisher und nennt „Produktkatalog“.

**Prompt**
- [x] Angenommen der Fall hat ein Produkt ohne Eintrag, wenn der Prompt entsteht, dann steht im Fallabschnitt „Produkt ohne hinterlegtes Wissen: … (weder Produktkatalog noch Regeln …)“.

## Edge Cases
- Mehrere Katalogprodukte mit verschiedenen Wissensbereichen: Die Zeile nennt alle Bereiche („Regeln für: A, B“).
- Ein Katalogprodukt hat einen Bereich, der Mitarbeiter wechselt in den Expertenmodus und ändert ihn: Die Änderung gilt, die Zeile im normalen Modus zeigt den gewählten Bereich.
- Frage ohne Ticket: Das Formular verhält sich wie das Ticketformular (gemeinsame Komponente).
- Die Katalogsuche ist nicht eingerichtet (ProductForge aus): Das Formular zeigt wie heute „Produkt(e)“ mit dem Wissensbereich als Auswahl.
- Ältere Vorschläge: Gespeicherte Ergebnisse mit den alten Texten werden mit den neuen Texten angezeigt (die Texte stehen in den Ansichten, nicht in den gespeicherten Daten).
- Der Titel „Produktwissen zu „…““ in bereits gespeicherten Lücken bleibt im Verlauf unverändert; neue Meldungen verwenden den neuen Titel.

## Technical Requirements (optional)
- Keine neuen Pakete, keine neue Datenhaltung.
- Der interne Schlüssel der automatischen Meldung (aus „Produktwissen: <Name>“) bleibt unverändert; nur der angezeigte Titel ändert sich.

## Open Questions
- [x] Wissensbereich im Formular: nur im Expertenmodus sichtbar, sonst als Zeile „Regeln für: …“ (2026-10-22)
- [x] Wortwahl: „Produktkatalog (ProductForge/Content Studio & EOCS)“ und „Wissensdatenbank“ (2026-10-22)
- [x] Katalogprodukt ohne Bereich ist neutral (kein oranger Hinweis); Meldung nur, wenn der Katalog kein Kundenservice-Wissen hat; eingetipptes Produkt: „(nicht im Produktkatalog)“ (2026-10-22)

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Wissensbereich nur im Expertenmodus sichtbar | Der Mitarbeiter wählt das Produkt, die App leitet die Regeln ab; das Formular wird kürzer | 2026-10-22 |
| Zeile „Regeln für: …“ im normalen Modus | Eine falsche Vorbelegung bleibt erkennbar | 2026-10-22 |
| „Produktkatalog (ProductForge/Content Studio & EOCS)“ und „Wissensdatenbank“ als feste Begriffe | Hinweise sagen eindeutig, was fehlt | 2026-10-22 |
| Katalogprodukt ohne Wissensbereich ist normal | Nach PROJ-47 braucht nicht jedes Produkt eigene Regeln | 2026-10-22 |
| Marke „(nicht im Produktkatalog)“ für ein eingetipptes Produkt | Sagt, wo es fehlt | 2026-10-22 |
| „❗ Kein Produktwissen berücksichtigt“ bleibt | Wortlaut vom Auftraggeber so gewünscht | 2026-10-22 |

### Technical Decisions
<!-- Added by /architecture -->

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)
_Nicht nötig: reine Änderung an Formular und Texten, keine Daten, keine neue Route._

## Implementation Notes (2026-10-22)
- **Formular:** `scope-fields.blade.php` und `resources/js/scope-fields.js`: neues Merkmal `advanced` (aus `form.blade.php` und `question/form.blade.php`), Getter `areaVisible` (ohne Katalogprodukt oder im Expertenmodus sichtbar) und `areaLine` („Regeln für: A, B“ oder „Es gelten die allgemeinen Regeln.“). Überschrift, Hilfetext, gewählte Bereiche, Suchfeld und „Kein Produktbezug“ sind bei einem Katalogprodukt im normalen Modus ausgeblendet; die versteckten Felder `produkte[]` werden weiter gesendet. Der orange Hinweis „Kein passender Wissensbereich gefunden …“ ist entfernt.
- **Wortwahl:** Eintrag in der Suche, Marke „(nicht im Produktkatalog)“, Hilfetexte und Hinweis bei „Kein Produktbezug“ (auch im Skript); Ergebnishinweis „Weder Katalogangaben noch Regeln zu …“; Seite „Wissenslücken“ (Titel und Kasten); Fehlermeldung bei fehlender Produktangabe.
- **Meldung:** Anzeigetitel „Produkt „…“: weder Katalogangaben noch Regeln“ (`KnowledgeGapLog`); der interne Schlüssel („Produktwissen: <Name>“) bleibt unverändert, ältere Meldungen zählen weiter mit.
- **Prompt:** Der Fallabschnitt nennt bei einem Produkt ohne Eintrag „weder Produktkatalog noch Regeln der Wissensdatenbank kennen es; erfinde nichts dazu“.
- **Tests:** `PROJ-46-FormAreaTest` (5); angepasst: `PROJ-36` (Eisberg-Fall ohne PRODUCT-006, das seit PROJ-47 veraltet ist), `PROJ-42`, `PROJ-44`, `PROJ-45` (neue Texte). Komplette Suite grün.
- **Nicht geprüft:** das Formular im Browser (Sichtbarkeit von Feld und Zeile, Wechsel in den Expertenmodus, Absenden mit unsichtbarem Bereich) in den Breiten 375/768/1440 px.

## QA Test Results

**Tested:** 2026-10-22
**Methode:** 5 Pest-Tests (`PROJ-46-FormAreaTest`) für den Expertenmodus-Merker, die neuen Texte, den Hinweis ohne Katalogprodukt-Bereich, die Prompt-Zeile und die Meldung mit altem Schlüssel; komplette Suite 1879 Tests, 1877 bestanden, 2 übersprungen wie zuvor. Der Auftraggeber hat das Formular im Browser geprüft und die Freigabe erteilt („passt so“).

### Acceptance Criteria Status
- [x] 13 von 13 Kriterien bestanden (Formular normal und Expertenmodus, Begriffe im Formular und im Ergebnis, Lücken, Prompt)

### Bugs Found
Keine.

### Summary
- **Acceptance Criteria:** 13 von 13 bestanden
- **Bugs Found:** 0
- **Production Ready:** JA

## Deployment
_To be added by /deploy_
