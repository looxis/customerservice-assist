# PROJ-95: ToDo-Antworten in der Sprache des Kunden

## Status: In Review
**Created:** 2026-11-18
**Last Updated:** 2026-11-18

## Dependencies
- Requires: PROJ-70 (ticketbezogene ToDos mit vorbereiteter Antwort)
- Requires: PROJ-77 (Sendungsnummer automatisch nachreichen)
- Requires: PROJ-28 (Übersetzung, Rückübersetzung des Antwortentwurfs)
- Requires: PROJ-82 (Prüfung automatischer Antworten, `ReplyGuard`)

## Hintergrund
Die vorbereiteten ToDo-Antworten („Sendungsnummer nachreichen“, „(Teil-)Erstattung“) gibt es nur auf Deutsch. Der automatische Versand der Sendungsnummer ließ sich deshalb nur einschalten, wenn die Kundensprache „Deutsch“ bekannt war; bei jeder anderen oder unbekannten Sprache stand das ToDo auf „Bitte prüfen“. Auch die manuelle ToDo-Antwort öffnete immer einen deutschen Entwurf mit deutscher Signatur.

## User Stories
- Als Mitarbeiter will ich den automatischen Versand der Sendungsnummer auch für Kunden einschalten, die nicht Deutsch schreiben.
- Als Mitarbeiter will ich, dass die App die deutsche ToDo-Antwort samt Signatur vor dem Senden in die Sprache des Kunden übersetzt.
- Als Mitarbeiter will ich beim manuellen Abschluss eines ToDos einen Entwurf in der Sprache des Kunden bekommen und ihn über die Rückübersetzung prüfen können.
- Als Admin will ich die ToDo-Texte weiter nur auf Deutsch pflegen.

## Out of Scope
- Mehrsprachige Pflege der ToDo-Texte in den Einstellungen.
- Sprachwahl im ToDo-Dialog: die Sprache erkennt die KI.
- Übersetzung der Signatur bei normalen Antwortvorschlägen (PROJ-9/19): bleibt wie bisher.

## Entscheidungen
- Sprache: Ist am ToDo „Deutsch“ bekannt, wird nicht übersetzt. Sonst übersetzt die KI in die Sprache der letzten Kundennachricht des Tickets und meldet die Sprache; sie wird am ToDo gespeichert.
- Antworttext und Signatur werden übersetzt; Nummern, Namen, Links, Platzhalter und „LOOXIS Kundenservice“ bleiben unverändert.
- Automatik: direkt senden, wenn die Prüfungen bestehen; sonst „Bitte prüfen“ mit dem übersetzten Text.
- Manuell: Übersetzung beim Öffnen des Entwurfs; schlägt sie fehl, öffnet der deutsche Entwurf mit Hinweis.
- Prompt „Übersetzung der ToDo-Antwort“ ist in den Einstellungen bearbeitbar; Modell wie bei der Übersetzung (`model_translation`).

## Acceptance Criteria

### Automatischer Versand
- [ ] Angenommen ein ToDo „Sendungsnummer nachreichen“ an einem Ticket mit unbekannter oder fremder Kundensprache, wenn der Mitarbeiter es anlegt oder bearbeitet, dann lässt sich der automatische Versand einschalten (Ticketseite und Antwortvorschlag).
- [ ] Angenommen die Sendungsnummer liegt vor und der Kunde schreibt Italienisch, wenn die Prüfung läuft, dann sendet die App Antworttext und Signatur auf Italienisch, speichert „Italienisch“ am ToDo und vermerkt die Sprache in der internen Notiz.
- [ ] Angenommen am ToDo ist „Deutsch“ bekannt, wenn die Prüfung läuft, dann wird ohne KI-Aufruf wie bisher gesendet.
- [ ] Angenommen die Sprache ist unbekannt und die KI meldet Deutsch, wenn die Prüfung läuft, dann geht der deutsche Text unverändert hinaus.
- [ ] Angenommen in der Übersetzung fehlt eine Sendungsnummer oder sie enthält einen Link, eine E-Mail-Adresse, einen Betrag, einen Gutschein oder einen offenen Platzhalter, wenn die Prüfung läuft, dann wird nicht gesendet; das ToDo steht auf „Bitte prüfen“ mit dem übersetzten Text und dem Grund.
- [ ] Angenommen die Übersetzung schlägt technisch fehl, wenn die Prüfung läuft, dann zählt das als Fehler; nach dem dritten Fehler in Folge steht das ToDo auf „Bitte prüfen“.

### Manuelle Antwort
- [ ] Angenommen ein ToDo mit Kundenbenachrichtigung an einem Ticket in fremder Sprache, wenn der Mitarbeiter es erledigt, dann öffnet der Entwurf in der Sprache des Kunden, mit übersetzter Signatur und Sprachangabe; die Rückübersetzung ist verfügbar.
- [ ] Angenommen die Übersetzung schlägt fehl, wenn der Mitarbeiter das ToDo erledigt, dann öffnet der deutsche Entwurf mit dem Hinweis, dass nicht übersetzt werden konnte; Senden bleibt möglich.
- [ ] Angenommen der Kunde schreibt Deutsch, wenn der Mitarbeiter das ToDo erledigt, dann bleibt alles wie bisher.

### Einstellungen
- [ ] Die Hinweise zu den ToDo-Texten sagen, dass sie auf Deutsch gepflegt und beim Senden übersetzt werden.
- [ ] Der Prompt „Übersetzung der ToDo-Antwort“ steht unter den Prompts.

## Edge Cases
- Kein Kundenartikel im Ticket: Die KI hat keinen Sprachbezug und meldet Deutsch; der Text bleibt deutsch.
- Der vorbereitete Text ist schon in der Kundensprache (vom Mitarbeiter so geschrieben): Die KI gibt ihn unverändert zurück.
- Kundennachricht enthält Anweisungen an die KI: sie dient nur als Sprachbezug, der Prompt verbietet das Befolgen; `ReplyGuard` prüft das Ergebnis.

## Tech Design
- `resources/prompts/reply-translation.md`, `App\Analysis\Agents\ReplyTranslationAgent`, `App\Analysis\ReplyTranslator` (Pseudonymisierung wie PROJ-28).
- `TrackingNotifier`: übersetzt nach dem Füllen der Platzhalter, neue Prüfungen, Sprache in `customer_language`.
- `TodoService::automation`: Bedingung „Deutsch“ entfällt; `prepareNotification` übersetzt und legt die übersetzte Signatur als `reply_signature` am Entwurf ab, `ReplyDelivery::offer` nimmt sie.
