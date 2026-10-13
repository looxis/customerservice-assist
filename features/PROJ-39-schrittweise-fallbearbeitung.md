# PROJ-39: Schrittweise Fallbearbeitung

## Status: In Progress
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
