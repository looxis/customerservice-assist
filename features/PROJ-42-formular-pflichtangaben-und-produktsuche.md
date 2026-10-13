# PROJ-42: Formular „Vorschlag erstellen“ – Pflichtangaben, Produktsuche, Erweiterter Modus

## Status: In Progress
**Created:** 2026-10-13
**Last Updated:** 2026-10-13

## Dependencies
- Requires: PROJ-4 (Kundengruppe und Produkt als Geltungsbereich), PROJ-9 (Formular), PROJ-33 (Produktvorschlag aus Kundenbegriffen), PROJ-40 (Frage ohne Ticket, nutzt denselben Baustein).
- Verwandt: PROJ-36 (gezielter Wissensabruf), PROJ-38 (Produktdaten aus EOCS/Content Studio, später).

## Ausgangslage
Ein Test (Ticket#2139435) lieferte ein schlechtes Ergebnis, weil Kundengruppe und Produkt nicht gewählt waren: PRODUCT-006 und PLAYBOOK-018 sind an das Produkt `3d-glass-photo` gebunden und liegen ohne Produkt außerhalb des Geltungsbereichs. Das Formular erlaubte das stillschweigend („Noch unklar“ vorausgewählt, „Nichts angehakt = kein Produktbezug“). Außerdem standen Kundengruppe und Bestellung mehrfach und widersprüchlich auf der Seite, der Bereich „Was an die KI geht“ ist für die meisten Mitarbeiter unnötig, und die Checkbox-Liste der Produkte wächst mit 50 bis 100 Produktgruppen ins Unbrauchbare.

## Entscheidungen
- **Pflichtangaben:** Kundengruppe ohne Vorauswahl „Bitte wählen“, außer die App leitet sie ab (Ticketwahl, Bestellkanal, Kundengedächtnis). „Noch unklar“ bleibt eine bewusste Wahl. Beim Produkt drei ausdrückliche Antworten: Produkt(e) aus der Wissensbasis, **Produkt ohne hinterlegtes Wissen** (freier Name, bis 120 Zeichen) oder „Kein Produktbezug“; „Kein Produktbezug“ schließt die anderen aus. Der Server lehnt ein Formular ohne diese Entscheidungen ab (Ticket und Frage).
- **Rückfrage:** Bei „Noch unklar“ oder „Kein Produktbezug“ steht ein gelber Hinweis am Feld, das Absenden fragt noch einmal („Sicher?“); der Hinweis nennt ein im Ticket erkanntes, nicht gewähltes Produkt.
- **Produkt ohne Wissen:** Der Name geht als Zeile „Produkt ohne hinterlegtes Wissen: …“ in den Prompt (keine Erfindungen, fehlendes Wissen als `knowledge_gaps`, Prompt `analysis-2026-10-13.3`), steht in `inputs` und `meta.unknown_product`, und das Ergebnis zeigt „Kein Produktwissen zu …“ mit „Wissenslücke melden“ (vorbelegt). Später (PROJ-38) kann hier ein Abruf aus EOCS/Content Studio per Name oder SKU ansetzen.
- **Produktsuche:** Suchfeld mit Auswahlchips statt Checkbox-Liste (Alpine, `scopeFields`); gesucht wird in Kurzname, Titel, Slug und Kundenbegriffen (`customer_terms`). `KnowledgeSelector::products()` liefert zusätzlich `name` (Titel bis zum Doppelpunkt) und `terms`; Wissensdateien bleiben unverändert. Vorschläge aus Bestellung und Ticket sind vorgemerkt, mit Grund am Chip.
- **Eine Stelle je Information:** Die Zelle „Kundengruppe“ im Ticketkopf entfällt. Die Bestellung wird oben unter „Bestellungen“ gewählt und geladen; das Formular hat nur eine Statuszeile („Bestellung … wird beim Erstellen aus EOCS geladen“). „Bestelldaten von Hand“ erscheint nur, wenn keine Bestellnummer bekannt ist.
- **Erweiterter Modus:** Schalter „Erweitert“ in der Kopfleiste für alle (Cookie je Browser, `app/Staff/AdvancedMode.php`, `POST /erweitert`). Nur dort: Wahl des Ticketkontexts, Vorschau „Was an die KI geht“, „Bestelldetails nicht abrufen“ und die Zammad-Gruppe im Ticketkopf. Ohne Modus gilt die empfohlene Variante (verstecktes Feld).

## Acceptance Criteria
- [x] Angenommen die App kann keine Kundengruppe ableiten, wenn das Formular erscheint, dann steht dort „Bitte wählen“ und ein leeres Absenden wird (auch vom Server) abgelehnt.
- [x] Angenommen kein Produkt, kein Name und kein „Kein Produktbezug“, wenn abgeschickt wird, dann erscheint eine Fehlermeldung am Produktfeld und es läuft kein KI-Aufruf.
- [x] Angenommen „Kein Produktbezug“ und ein Produkt oder Name, wenn abgeschickt wird, dann wird die Kombination abgelehnt.
- [x] Angenommen ein Produktname ohne hinterlegtes Wissen, wenn der Vorschlag erstellt wird, dann nennt ihn der Prompt, das Ergebnis weist auf das fehlende Wissen hin und bietet „Wissenslücke melden“ an.
- [x] Angenommen „Noch unklar“ oder „Kein Produktbezug“, wenn abgeschickt wird, dann fragt die Seite noch einmal nach (Bestätigung im Browser).
- [x] Angenommen 50 oder mehr Produkte, wenn im Suchfeld getippt wird, dann erscheinen passende Treffer mit Kurzname (auch über Kundenbegriffe); gewählte Produkte stehen als Chips mit Entfernen-Knopf.
- [x] Angenommen der Ticketkopf, wenn die Seite erscheint, dann enthält er keine Kundengruppe; die Zammad-Gruppe nur im Erweiterten Modus.
- [x] Angenommen eine Bestellnummer ist bekannt, wenn das Formular erscheint, dann gibt es eine Statuszeile statt „Bestelldaten von Hand“ und statt des Kastens zum Nicht-Abrufen (der nur im Erweiterten Modus steht).
- [x] Angenommen der Erweiterte Modus ist aus, wenn das Formular erscheint, dann fehlen Kontextwahl und Vorschau, und die empfohlene Variante wird gesendet; der Schalter in der Kopfleiste setzt und löscht das Cookie.
- [x] Angenommen „Frage stellen“, wenn die Seite erscheint, dann gelten dieselben Pflichtangaben und dieselbe Produktsuche.

## Implementation Notes
- `scope-fields` (Blade) + `resources/js/scope-fields.js` (Alpine), `ChecksProductChoice` (Requests), `AdvancedMode`, `AdvancedModeController`, Änderungen in `AnalysisPanel::suggestedGroup()`, `KnowledgeSelector::products()`, `CaseAnalyzer`, `AnalysisRequest::$unknownProduct`.
- Tests: `PROJ-42-FormRequirementsTest.php` (15) und angepasste Tests (PROJ-4, -6, -9, -10, -32, -33, -40); Hilfen in `tests/Pest.php` (`withProductAnswer`, `scopeConfig`, `withChips`).
- Die Rückfrage („Sicher?“) und die Chips laufen im Browser; im Test sind sie nur über die Seitenkonfiguration geprüft. Bitte im Browser prüfen: Suche („Viamant“, „Glas“), Rückfrage, Schalter, 375/768 px.
- Offen (nicht Teil dieser Spec): im Wissensabruf anzeigen, welche Dokumente wegen fehlender Produktwahl nicht berücksichtigt wurden (PROJ-36).
