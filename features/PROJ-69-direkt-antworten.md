# PROJ-69: Direkt antworten ohne KI-Aufruf

## Status: In Review
**Created:** 2026-11-04
**Last Updated:** 2026-11-04

## Dependencies
- Requires: PROJ-19 (Antwort direkt aus der App nach Zammad übergeben) – Editor, Anhänge, Versand, Doppelversand-Sperre
- Requires: PROJ-68 (Ticket ohne Antwort schließen) – Knöpfe, Token-Bedingung
- Berührt: PROJ-29 (Ticketbearbeitung in der App), kleiner Schritt in diese Richtung

## Hintergrund
Manchmal braucht es keinen Vorschlag: der Mitarbeiter weiß die Antwort und will sie direkt schreiben und senden. Bisher ging das nur über einen KI-Vorschlag.

## User Stories
- Als **Mitarbeiter mit persönlichem Zammad-Token** will ich neben „Ohne Antwort schließen“ „Direkt antworten“ wählen, damit ich ohne KI-Aufruf auf das Ticket antworten kann.

## Acceptance Criteria
- Knopf „Direkt antworten“ im Ticketkopf und im Formular „Vorschlag erstellen“, nur sichtbar wie „Ohne Antwort schließen“ (Token, Ticket offen, nicht zurückgespult).
- Klick legt einen leeren, manuellen Antwortentwurf an (kein KI-Aufruf) und öffnet die vorhandene Ansicht mit Editor und Block „Antwort versenden“ (Empfänger, Betreff, Signatur, Anhänge, danach schließen oder Erinnerung).
- Ein weiterer Klick öffnet denselben, noch unbenutzten Entwurf wieder.
- Der manuelle Entwurf zählt nicht als Analyse: nicht in Erfolgsquote, Analysen-Übersicht, Ticket-Stand „analysiert“.

## Umsetzung
- Datensatz `analyses` mit Status `manual` und `meta.manual = true` (`AnalysisStore::putManualReply`); `finished()` (neuester Vorschlag) berücksichtigt ihn, `AnalysisOverview` blendet ihn aus; alle anderen Zählungen filtern auf `completed`.
- Route `tickets.manual-reply` → `ManualReplyController`, `StartManualReplyRequest`.
- Schlanker Zweig in `components/analysis/result.blade.php` (nur Editor und Versand).
- Test: `tests/Feature/PROJ-69-DirectReplyTest.php`.
