# PROJ-39: Schrittweise Fallbearbeitung

## Status: In Review
**Created:** 2026-10-13
**Last Updated:** 2026-10-13

## Dependencies
- Requires: PROJ-9 (Fallanalyse, Ergebnisstruktur), PROJ-10 (Ergebnisansicht), PROJ-11 (Protokoll, jede Analyse eine eigene Zeile), PROJ-30 (Interne Abläufe).

## Ausgangslage
Der Analysevertrag erzwang in jeder Analyse einen Antwortentwurf und führte dieselbe Information in vier Feldern (`recommendation`, `missing_information`, `internal_todos`, Interne Abläufe). Es gab keinen Weg, „erst intern klären“ auszudrücken, und keine Fortsetzung mit einem Ergebnis des Mitarbeiters.

## Entscheidungen
- **Neuer Vertrag:** `next_step` (`reply` | `internal_first`), `reply` nullable, `internal_steps` (ersetzt `internal_todos`), `decision` (nullable: Frage, Begründung, 2–6 Optionen, optionale `knowledge_ids`). `missing_information` entfällt: Kundenangaben werden Rückfrage im Entwurf, interne Angaben Schritt oder Entscheidung. `knowledge_gaps` bleiben ausschließlich fehlende Regeln der Wissensbasis.
- **Wann intern zuerst:** nur wenn die Klärung intern kurzfristig möglich ist, die Antwort wesentlich verändert und eine Zwischenantwort wenig Mehrwert hat (Prompt `analysis-2026-10-13.1`). Sonst bleibt der Ablauf unverändert.
- **Optionen:** nicht frei erfunden, sondern aus Fall und Wissen; `knowledge_ids` sind nur nötig, wenn eine Wissensregel die Entscheidung trägt. Der Validator prüft nur formal (unbekannte IDs entfernen, mindestens zwei brauchbare Optionen, sonst wird die Frage ein interner Schritt); die inhaltliche Deckung sichert der Prompt.
- **Fortsetzung:** keine neue Route. `POST tickets.analysis.run` mit `klaerung_analyse` und `klaerung_option` und/oder `klaerung_text`. Der Text der Option wird serverseitig aus der gespeicherten Analyse gebildet; nur die neueste Analyse ist fortsetzbar. Eingaben der Vorgängeranalyse werden übernommen, die Klärungen stehen als „Interne Klärungen (geprüfte Fakten)“ im Prompt und in den Fakten für den Wissensabruf (`content.decisions`). Freie Eingabe gibt es immer, auch ohne `decision`. Keine Begrenzung der Folgen.
- **Anzeige:** „Was ist zu tun?“ nur Kurzempfehlung; „Fehlende Informationen“ entfällt, „Fehlendes Wissen“ heißt „Wissenslücken“; „Interne To-dos“ sind in „Interne Abläufe“ aufgegangen: Entscheidung, Fallschritte („Für diesen Fall“, von der KI abgeleitet), darunter eingeklappt „Ergänzend aus der Wissensbasis“ (Procedures, nicht dem LLM bekannt, keine Dublettenerkennung). Ohne `reply` entfallen Editor, Feedback und Kopieren; Kopieren/Speichern liefert 409.
- **Rückwärtskompatibilität:** alte Analysen ohne die neuen Felder rendern weiter (`internal_todos` als Fallschritte, `missing_information` eingeklappt als „Fehlende Angaben (ältere Analyse)“).

## Akzeptanztest (Fotohändler, „Aufsteller“)
1. Analyse liefert `internal_first` mit vier Optionen (Display, Glasfoto-Muster, beides, kein Bedarf), ohne Entwurf und ohne Wissenslücke.
2. Auswahl setzt den Fall fort; bei „kein Bedarf“ entsteht ein kurzer Abschluss mit Entsorgungshinweis, sonst eine Rückfrage zum Zustand samt Rückversand.
Die Automatiktests prüfen den Ablauf mit gefaketen Antworten (`tests/Feature/PROJ-39-StepwiseAnalysisTest.php`); die Qualität der Optionen zeigt nur ein Lauf mit dem echten Modell.

## Implementation Notes
- Schema `CaseAgent`, Prüfung `ResultValidator`, Fortsetzung `AnalyzeTicketRequest` (`klaerung_*`, `decisions()`), `AnalysisRequest::decisions`, Prompt-Abschnitt in `CaseAnalyzer::parts()`.
- UI in `components/analysis/procedures.blade.php` und `result.blade.php`.
- Offen: Lauf mit dem echten Modell gegen das Fotohändler-Ticket (QA).

## QA Test Results

**Tested:** 2026-10-13 · Live gegen Ticket#2139826 (Fotohändler, „Aufsteller“, Testmodus-Stand der ersten Kundennachricht), Modell `gpt-5.5`, Prompt `analysis-2026-10-13.1+retrieval-2026-10-13.2`, bestehende Analysen vorher gelöscht · Pest-Suite 1263 Tests (PROJ-39: 20).

### Akzeptanzkriterien
| # | Kriterium | Ergebnis |
|---|-----------|----------|
| 1 | „Interne To-dos“ und „Interne Abläufe“ in einer Oberfläche, Herkunft erkennbar | Bestanden („Für diesen Fall – von der KI abgeleitet“ vs. „Ergänzend aus der Wissensbasis“, eingeklappt) |
| 2 | „Was ist zu tun?“ kurz, Schritte nur in „Interne Abläufe“ | Bestanden (live: 2–3 Sätze) |
| 3 | „Wissenslücken“ nur fehlende Regeln, interne Entscheidung nie als Lücke | Bestanden (live: 5 Läufe, keine Lücke zur Entscheidung) |
| 4 | Blockierende interne Klärung → kein Antwortentwurf | Bestanden (live 4 von 4 Läufen auf dem zurückgespulten Ticket) |
| 5 | Auswahloptionen aus Fall und Wissen | Bestanden mit Einschränkung, siehe QA-2 |
| 6 | Fortsetzung mit Entscheidung, Kontext bleibt erhalten, danach Entwurf | Bestanden (Option, Option + Notiz, freies Ergebnis; „Bereits intern geklärt“ sichtbar) |
| 7 | Greift nicht pauschal bei jeder offenen Frage | Nur teilweise live prüfbar (kein zweites Ticket), siehe QA-5 |
| 8 | Einfache Tickets nicht verkompliziert, alte Analysen kompatibel | Alte Analysen: bestanden (Test). Einfaches Ticket live: nicht geprüft |
| Akzeptanztest | „Kein Bedarf“ → kurze Antwort mit Entsorgungshinweis; „Bedarf“ → Rückfrage zum Zustand mit Rückversand | Bestanden (live: Entsorgungshinweis; Bedarf: Zustandsfragen zu Display und Mustern, bezahltes Retourenlabel, Verpackungsmaterial) |

### Bereits während der Prüfung behoben
- Klick auf eine Option wählte nichts aus (deaktivierter Submit-Button sendet den Wert nicht): Optionen sind jetzt Radiobuttons mit Bestätigen-Button (Commit ee4bba0).
- Überschrift „Zuerst zu klären“ lag auf der Rahmenlinie; Hand-Cursor fehlte.

### Offene Befunde
- **QA-1 (Low, akzeptiert; Prompt verlangt jetzt die Kombination bei unabhängig betroffenen Gegenständen, im Live-Lauf kam sie nicht, weil die Optionen die Einordnung des „Aufstellers“ betreffen und sich ausschließen):** Die Optionen schwanken von Lauf zu Lauf (Schlüssel, Reihenfolge, Zuschnitt). „Beides zurück“ aus dem Akzeptanztest kommt nicht als Option, sondern nur über das Freitextfeld (live geprüft: Option + „Display und Glasfoto-Muster sind beide noch da“ ergibt einen Entwurf zu beiden). Erwartbar bei KI-Ableitung, kein Fehler im Vertrag.
- **QA-2 (Low):** Die Optionen ordnen zuerst den „Aufsteller“ ein (Leihmaterial oder gekauftes Zubehör) statt Display/Muster. Fachlich begründet durch POLICY-027/PROCESS-012, weicht aber vom Wortlaut des Akzeptanztests ab.
- **QA-3 (Medium, behoben mit Prompt `analysis-2026-10-13.2`, live bestätigt: beide Läufe „Du darfst das selbst entscheiden“):** Nach der Klärung steht in der Befugnis teils weiter „Freigabe nötig durch Produktionsleitung“, obwohl die Klärung vom Mitarbeiter eingetragen wurde („bereits geklärt von …“). Widersprüchlich für den Leser. Vorschlag: Prompt-Regel, dass `authority` den Stand nach den „Internen Klärungen“ beschreibt.
- **QA-4 (Low/Medium, behoben mit Prompt `analysis-2026-10-13.2`, live bestätigt: keine Entschuldigung mehr, Anrede „Guten Tag Herr/B. Rutkiewicz“, Schlusssatz „alles Gute“ zur Geschäftsaufgabe in beiden Läufen):** Tonfall der Entwürfe: wiederholte Entschuldigung („keine positivere Nachricht“) bei „kein Bedarf“; kein freundlicher Abschied zur Geschäftsaufgabe; einmal Anrede „Sehr geehrte/r Herr/Frau …“. Das ist Prompt- und Wissensthema (TONE-002), die Wissensbasis blieb unverändert.
- **QA-5 (offen):** Kein Live-Lauf mit einem einfachen Standardticket (kein weiteres Ticket vorgegeben, keine fremden Kundendaten an die KI senden). Der Fall „soll weiter sofort einen Entwurf bekommen“ ist nur durch Fake-Tests belegt.
- **QA-6 (offen):** Manuelle Browser-/Responsive-Prüfung (Firefox, Safari, 375/768 px) nicht durchgeführt; geprüft wurde im Chrome des Entwicklers nur der Klickpfad nach den Korrekturen.

### Sicherheit
CSRF (Standard-Middleware, `@csrf` im Formular), Namensauswahl Pflicht, Throttle `language-model` unverändert. Pest-Tests: Escaping von Frage, Optionen und Schritten (XSS), gefälschte Option abgelehnt, nicht-neueste und fremde Analyse nicht fortsetzbar, Längenlimit, Optionstext nie aus dem Request. Keine Befunde. Freitext gilt gewollt als geprüfter Fakt für die KI (wie das bestehende Kontextfeld).

### Regression
Gesamte Pest-Suite grün (1263 Tests). Alte Analysen mit `internal_todos` und `missing_information` rendern weiter. Hinweis: PROJ-9/10/11/12/25/28/30/32/35/36 mussten wegen des Vertrags angepasst werden (Fixtures, Textfolgen), keine Tests gelöscht.

### Empfehlung
Keine Critical- oder High-Befunde. **Produktionsreif: JA**, mit QA-3 als wichtigstem Nachbesserungskandidat (Prompt) und QA-5/QA-6 als offene Prüfungen.

**Nachprüfung 2026-10-13:** Prompt `analysis-2026-10-13.2`, zwei weitere Live-Läufe auf Ticket#2139826 (Zubehör und Leihmaterial ohne Bedarf), Suite grün. QA-3 und QA-4 geschlossen, QA-1 akzeptiert, QA-2, QA-5 und QA-6 offen.
