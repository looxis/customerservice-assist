# PROJ-69: Direkt antworten ohne KI-Aufruf

## Status: Approved
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

---

## QA Test Results

**Tested:** 2026-11-04
**Tester:** QA Engineer (AI) und Auftraggeber (manueller Test im Browser: „funktioniert gut“)

### Acceptance Criteria
- [x] Knopf im Ticketkopf und im Formular, nur mit Token, offenes Ticket, nicht zurückgespult
- [x] Klick legt leeren manuellen Entwurf an (kein KI-Aufruf) und öffnet die Versandansicht
- [x] Erneuter Klick öffnet denselben unbenutzten Entwurf
- [x] Zählt nicht als Analyse (Erfolgsquote, Analysen-Übersicht, Ticket-Stand)

### Security
- CSRF: beide Formulare mit `@csrf`. Autorisierung wie „Ohne Antwort schließen“ (Zammad-Token, kein `stand`), 403 getestet. Rate-Limit `throttle:20,1`. Senden unverändert über die vorhandene, abgesicherte Route (Doppelversand-Sperre).

### Tests
- `tests/Feature/PROJ-69-DirectReplyTest.php`: 9 Tests, alle grün. Gesamtlauf: 2162 von 2166 grün; die 2 Fehler sind die bekannten PROJ-56-Tests (Sichtbarkeit der Meldungen offen), unabhängig von PROJ-69.

### Bugs
Keine Critical/High/Medium.

### Entscheidung: READY
