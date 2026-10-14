# PROJ-44: Nicht geprüftes Wissen sichtbar machen

## Status: Approved
**Created:** 2026-10-13
**Last Updated:** 2026-10-14

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

---

## QA Test Results

**Tested:** 2026-10-14 (zusammen mit PROJ-42)
**Tester:** QA Engineer (AI)
**Methode:** komplette Pest-Suite (1344 Tests, alle bestanden), `PROJ-44-OutOfScopeTest.php` (9 Tests), Prüfung der Migration (Spalte `prompt_version` hat 160 Zeichen in der laufenden Datenbank), Render-Tests. **Nicht getestet:** Browser und Breiten 375/768/1440 px.

### Acceptance Criteria Status
- [x] AC-1 Ohne Produkt: gelber Hinweis mit Anzahl und Produktnamen, Abschnitt listet die Produktdokumente (Test)
- [x] AC-2 Mit Produkt: kein Hinweis, andere Produkte ruhig unter „Gelten für andere Produkte“ (Test)
- [x] AC-3 Dokumente für andere Kundengruppen: eigene Gruppe mit Grund (Test)
- [x] AC-4 Produkt ohne hinterlegtes Wissen: kein zweiter Hinweis (Test)
- [x] AC-5 Ältere Analyse ohne `out_of_scope`: Ansicht ohne Fehler und ohne neuen Abschnitt (Test)
- [x] AC-6 Frage ohne Ticket: Hinweis ohne Knopf zum Ticketformular (Test)

### Edge Cases Status
- [x] Lange Listen werden bei 30 je Ursache abgeschnitten, „… und N weitere“ (Test)
- [x] Frage mit aktivem Wissensabruf wird gespeichert (die Prompt-Version ist bis 68 Zeichen lang, Spalte 160); wird zum Ausliefern `migrate` vergessen, schlägt das Speichern mit 500 fehl, siehe Hinweis
- [x] Eine Analyse zu einem Produkt im Geltungsbereich meldet nichts als fehlend

### Security Audit Results
- [x] Produktnamen und Gründe werden als Text ausgegeben (Blade-Maskierung), Namen stammen aus der Wissensbasis
- [x] Keine neuen Routen und Formulare; der Knopf „neu erstellen“ öffnet nur das vorhandene Formular
- [x] Keine Kundendaten in den neuen Feldern (IDs, Titel, Gründe)

### Bugs Found
Keine.

#### Hinweis (kein Fehler)
- Beim Ausliefern muss `./vendor/bin/sail artisan migrate` laufen (Migration `widen_prompt_version_of_analyses_table`); ohne sie schlagen Fragen mit aktivem Wissensabruf beim Speichern fehl.

### Summary
- **Acceptance Criteria:** 6/6 bestanden
- **Bugs Found:** 0
- **Security:** Pass
- **Production Ready:** YES
