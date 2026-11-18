# PROJ-93: Geführtes Formular „Neuversand“ in „Vorschlag erstellen“

## Status: In Review
**Created:** 2026-11-18
**Last Updated:** 2026-11-18

## Dependencies
- Requires: PROJ-90 (Formularwahl und Liste der geführten Formulare)
- Requires: PROJ-86 (Reklamationsformular: Vorbild für Aufbau und ToDo „Sendungsnummer mitteilen“)
- Requires: PROJ-70, PROJ-77 (ticketbezogene ToDos, Sendungsnummer automatisch nachreichen)
- Requires: PROJ-64 (Adresse einer Bestellung aus EOCS laden)

## Hintergrund
Wie bei der Reklamation sollen wenige Klick-Entscheidungen einen kurzen, verlässlichen Entwurf liefern. Ein Neuversand ist keine Ticket-Kategorie: Das Formular wird nie vorgewählt, sondern bei Bedarf von Hand gewählt.

## User Stories
- Als Mitarbeiter will ich einen bereits in EOCS angelegten Neuversand per Klick mitteilen (kostenfrei, bezahlt, mit Rechnung im Paket), damit der Entwurf genau den passenden Hinweis enthält.
- Als Mitarbeiter will ich die Auftragsnummer des Neuversands angeben, damit der Entwurf sie nennt und die App die Sendungsnummer später nachreicht.
- Als Mitarbeiter will ich die aktualisierte Lieferadresse aus EOCS übernehmen und prüfen, damit der Kunde sie bestätigt bekommt.
- Als Mitarbeiter will ich keine Produkte auswählen müssen.

## Acceptance Criteria

### Formularwahl
- [ ] Die Auswahl „Formular“ zeigt „Technischer Support | Reklamation | Neuversand | Freitext“; „Neuversand“ ist bei jedem Ticket wählbar und wird nie anhand der Kategorie vorgewählt.
- [ ] Ist „Neuversand“ gewählt, sind Produktsuche, Wissensbereich und „Kein Produktbezug“ ausgeblendet und werden nicht gesendet; der Vorschlag gilt als Fall ohne Produktbezug. Die Zeile „Erstellt mit“ nennt „Neuversand“ ohne „kein Produktbezug“.
- [ ] Freitext, Hilfe „So schreibst du es“ und DHL-Retourenlabel entfallen in diesem Formular.

### Felder
- [ ] Pflichtfrage „Ist der Neuversand kostenpflichtig?“ (Ja / Nein), nichts vorgewählt; ohne Antwort: „Bitte angeben, ob der Neuversand kostenpflichtig ist.“
- [ ] Bei „Ja“ Pflichtfrage „Bereits bezahlt?“ (Ja / Nein, Rechnung wird dem Paket beigelegt); ohne Antwort: „Bitte angeben, ob der Neuversand bereits bezahlt ist.“
- [ ] Freitextfeld „Auftragsnummer des Neuversands“ (höchstens 60 Zeichen): meist eine Reklamationsnummer (R1-…, der Neuversand wird bequem als ggf. kostenpflichtige Reklamation in EOCS angelegt), sonst eine Bestellnummer aus EOCS; eine Reklamationsnummer wird in Großbuchstaben gespeichert. Ohne Nummer steht im Entwurf der Platzhalter [AUFTRAGSNUMMER].
- [ ] Freitextfeld „Aktualisierte Adresse“ (höchstens 500 Zeichen). Nach Eingabe der Auftragsnummer übernimmt die App die Lieferadresse aus EOCS, wenn das Feld leer ist (bei einer Reklamationsnummer die Adresse der Reklamation selbst, nicht die der Originalbestellung); „Adresse aus EOCS laden“ überschreibt sie auf Wunsch. Findet EOCS keine Adresse, steht ein Hinweis am Feld.
- [ ] Freitextfeld „Zusätzliche Informationen“ (höchstens 1.500 Zeichen).
- [ ] Haken „ToDo „Sendungsnummer mitteilen“ anlegen“, vorab angehakt.

### Wirkung auf den Vorschlag
- [ ] Kostenfrei: Der Entwurf teilt mit, dass wir den Neuversand auf Kulanz kostenfrei veranlasst haben, er unter der Nummer [Auftragsnummer] läuft und die Sendungsnummer nach dem Versand separat folgt.
- [ ] Kostenpflichtig und bezahlt: nur, dass wir den Neuversand veranlasst haben, die Nummer und die spätere Sendungsnummer; keine Kosten, Zahlung oder Rechnung.
- [ ] Kostenpflichtig mit Rechnung: Der Neuversand wird jetzt unter der Nummer veranlasst, dem Paket liegt eine Rechnung zur Zahlung des Neuversands bei, die Sendungsnummer folgt separat.
- [ ] Zusätzliche Informationen gehen als vorrangige Angabe des Mitarbeiters an die KI.
- [ ] Die Adresse geht nie an die KI: Die KI erhält nur den Platzhalter [NEUE-LIEFERADRESSE], die App setzt die Adresse im Entwurf ein.
- [ ] Die Antworten werden mit dem Vorschlag gespeichert und belegen das Formular beim nächsten Öffnen vor.

### ToDo
- [ ] Mit Haken und Auftragsnummer ist unter dem Entwurf das ToDo „Sendungsnummer nachreichen“ mit der Nummer des Neuversands vorausgewählt; ohne Haken oder ohne Nummer nicht. Bei einer Reklamationsnummer nennt der vorbereitete Text „Reklamation“, sonst „Bestellung“.
- [ ] Bei einer Reklamationsnummer findet die automatische Prüfung (PROJ-77) die Sendung der Reklamation wie bei jeder Reklamation. Bei einer Bestellnummer findet sie die Sendung am Neuversand-Auftrag selbst, aber nur Sendungen ab dem Tag, an dem das ToDo angelegt wurde. Für alle anderen ToDos mit Bestellnummer bleibt es dabei: nie die Sendung der Originalbestellung.

## Edge Cases
- **Auftragsnummer, die EOCS nicht kennt:** Die Adresse wird nicht übernommen, der Hinweis erscheint; der Vorschlag lässt sich trotzdem erstellen.
- **Neuversand-Auftrag hat Reklamationen in EOCS:** Die Prüfung nutzt wie bisher die neueste Reklamation.
- **Formularwechsel:** Eingaben bleiben im Browser erhalten, gesendet werden nur die des gewählten Formulars.

## Decision Log
| Decision | Rationale | Date |
|----------|-----------|------|
| Formular ohne Kategorie, nur manuell wählbar | Vorgabe des Auftraggebers; „Neuversand“ ist keine Klassifikations-Kategorie | 2026-11-18 |
| Adresse nur als Platzhalter an die KI | Adressen aus EOCS gehen grundsätzlich nicht an die KI (PROJ-64); die App setzt sie wie [LIEFERADRESSE] ein | 2026-11-18 |
| Adresse über die vorhandene Adressabfrage des DHL-Retourenlabels | Keine neue Schnittstelle nötig | 2026-11-18 |
| Eigene Sendungen des Auftrags nur für ToDos aus dem Neuversand-Formular, nur ab dem ToDo-Tag | Bisherige ToDos sollen nie die Sendung der Originalbestellung verschicken | 2026-11-18 |
| Reklamationsnummer als Normalfall der Neuversand-Nummer | Klarstellung des Auftraggebers: Neuversand wird meist als (kostenpflichtige) Reklamation in EOCS angelegt | 2026-11-18 |
| Keine Datenbankänderung | Antworten liegen wie bei den anderen Formularen in den Formularangaben des Vorschlags | 2026-11-18 |
