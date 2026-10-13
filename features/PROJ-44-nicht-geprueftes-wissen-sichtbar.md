# PROJ-44: Nicht geprüftes Wissen sichtbar machen

## Status: In Progress
**Created:** 2026-10-13
**Last Updated:** 2026-10-13

## Dependencies
- Requires: PROJ-4 (Geltungsbereich), PROJ-36 (Wissensabruf und „Geprüft, nicht abgerufen“), PROJ-42 (Pflichtangaben).

## Ausgangslage
Beim Test mit Ticket#2139435 fehlte PRODUCT-006, weil kein Produkt gewählt war. Das Dokument gilt nur für `3d-glass-photo` und lag deshalb außerhalb des Geltungsbereichs; es stand weder unter „Abgerufen“ noch unter „Geprüft, nicht abgerufen“ (die Liste kennt nur Dokumente im Geltungsbereich). Der Mitarbeiter konnte die Ursache nicht erkennen.

## Entscheidungen
- Der Wissensabruf speichert jetzt auch die Dokumente **außerhalb des Geltungsbereichs** (`retrieval.out_of_scope`: ID, Titel, Ursache, Grund, Produkte). Ursachen: `product-missing` (an ein Produkt gebunden, es war keins gewählt), `product` (anderes Produkt gewählt), `group` (andere Kundenart oder anderer Kanal).
- Im Ergebnis, für alle sichtbar: ein gelber Hinweis „Ohne Produktwahl nicht berücksichtigt“ (Anzahl, die ersten vier Produktnamen, Knopf „Produkt wählen und neu erstellen“ bei Tickets) – nur bei `product-missing` und nicht, wenn ein Produkt ohne Wissen genannt wurde (das hat seinen eigenen Hinweis).
- Im Bereich „Wissensabruf“: aufklappbarer Abschnitt „Nicht geprüft, außerhalb des Geltungsbereichs (N)“, nach Ursache gegliedert, mit Produktkurznamen; offen, wenn ein Produkt fehlte; höchstens 30 Einträge je Ursache mit „… und N weitere“.
- Ältere Analysen ohne `out_of_scope` zeigen weder Abschnitt noch Hinweis.

## Acceptance Criteria
- [x] Angenommen kein Produkt gewählt, wenn ein Vorschlag erstellt wird, dann erscheint ein Hinweis mit Anzahl und Produktnamen und der Abschnitt listet die Produktdokumente.
- [x] Angenommen ein Produkt gewählt, dann gibt es keinen Hinweis; andere Produkte stehen ruhig unter „Gelten für andere Produkte als die gewählten“.
- [x] Angenommen Dokumente nur für andere Kundengruppen, dann stehen sie in eigener Gruppe mit Grund.
- [x] Angenommen ein Produkt ohne hinterlegtes Wissen wurde genannt, dann erscheint kein zweiter Hinweis.
- [x] Angenommen eine Analyse von vorher, dann bleibt die Ansicht ohne Fehler und ohne den neuen Abschnitt.
- [x] Angenommen eine Frage ohne Ticket, dann erscheint der Hinweis ohne den Knopf zum Ticketformular.

## Implementation Notes
- `CaseAnalyzer::outOfScope()` nutzt `KnowledgeSelector::scope()`; Anzeige in `analysis/retrieval.blade.php` und `analysis/result.blade.php`.
- **Fehler gefunden und behoben:** Mit gezieltem Wissensabruf und einer Frage ohne Ticket wurde die Prompt-Version (`analysis+retrieval+question`, 68 Zeichen) länger als die Spalte `prompt_version` (60) und das Speichern schlug fehl (500). Die Tests zu PROJ-40 liefen mit ausgeschaltetem Abruf und fanden das nicht. Migration `widen_prompt_version_of_analyses_table` weitet die Spalte auf 160 Zeichen; beim Ausliefern `migrate` ausführen.
- Tests: `PROJ-44-OutOfScopeTest.php` (9).
- Blade-Fallstrick: In `result.blade.php` steht eine einzeilige `@php(...)`-Anweisung; ein späteres `@php … @endphp`-Paar in derselben Datei wird sonst als ein Block gelesen (Syntaxfehler). Dort deshalb nur die einzeilige Form verwenden.
