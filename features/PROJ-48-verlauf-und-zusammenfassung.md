# PROJ-48: Verlauf für die Analyse und Angebot der Zusammenfassung

## Status: Approved
**Created:** 2026-10-17
**Last Updated:** 2026-10-17

## Dependencies
- Requires: PROJ-6 (Ticketverlauf), PROJ-9 (Analyse, Zusammenfassung), PROJ-32 (Testmodus).

## Ausgangslage
Auf beantworteten Tickets ging unsere Antwort mit in die Analyse; um sie wegzulassen, musste ein Admin den Testmodus einschalten und an der ersten Kundennachricht „bis hierhin testen“ wählen. Außerdem war der Hinweis „Zusammenfassung des bisherigen Verlaufs“ mit dem Knopf „Zusammenfassung erstellen“ bei jedem Ticket zu sehen, auch bei einer einzigen Nachricht.

## Regeln
- **Unsere Antworten nach der letzten Kundennachricht gehen nicht in die Analyse** (und nicht in die Zusammenfassung). Der Vorschlag beantwortet die letzte Kundennachricht. Kommt später eine neue Kundennachricht dazu, gehören die früheren Antworten wieder zum Verlauf. Interne Notizen bleiben (sie können Ergebnisse von Kollegen enthalten). Ein Hinweis im Formular nennt, wie viele Antworten außen vor bleiben.
- **Der Testmodus bleibt** für das Zurückspulen auf eine frühere Kundennachricht (Admin, „bis hierhin testen“).
- **Zusammenfassung:** Das Angebot erscheint erst ab 5 Nachrichten (Kunde und wir) bis zur letzten Kundennachricht: „Mindestens 5 Nachrichten im Verlauf (n vorhanden)“ mit „Zusammenfassung erstellen“. Eine vorhandene Zusammenfassung wird immer angezeigt; kommen weitere Nachrichten, steht „Weitere Nachrichten sind vorhanden, die Zusammenfassung ist veraltet.“ mit „Zusammenfassung neu erstellen“. Die Empfehlung „Letzte Kundennachricht + Zusammenfassung“ gilt ab 5 Nachrichten (die Längenregel entfällt).
- Der Hinweis „Seit diesem Vorschlag sind neue Nachrichten eingegangen“ zählt unsere Antworten nicht.

## Acceptance Criteria
- [x] Eine Anfrage und eine Antwort: Analyse und neue Analyse enthalten nur die Anfrage, ohne Testmodus.
- [x] Anfrage, Antwort, neue Kundennachricht: alle drei gehen mit.
- [x] Nur Antworten nach der letzten Kundennachricht entfallen; interne Notizen bleiben.
- [x] Unter 5 Nachrichten gibt es keinen Zusammenfassungshinweis; ab 5 mit Zahl und Knopf.
- [x] Vorhandene Zusammenfassung sichtbar; veraltet mit Hinweis und „Zusammenfassung neu erstellen“.
- [x] Eine Antwort von uns nach dem Vorschlag löst keinen „neue Nachrichten“-Hinweis aus.

## Implementation Notes
- `TicketContext` bestimmt den berücksichtigten Verlauf (`considered`), `ignoredAfterLastCustomerMessage()`, `messageCount()`, `lastConsidered()`; `config('analysis.summary_threshold.messages')` = 5 (die Längenregel wurde entfernt).
- `AnalysisPanel` (`summaryOffered`, `ignoredReplies`, `messageCount`, Prüfung auf neue Nachrichten), `CaseAnalyzer` (letzte berücksichtigte Nachricht im Protokoll), Blade `analysis/summary` und `analysis/form`.
- Tests: `PROJ-48-ThreadForAnalysisTest` (14); angepasst: PROJ-9 (Schwellen, veraltete Zusammenfassung), PROJ-32 (Schnittstelle ohne Testmodus).
