# PROJ-98: Geführtes Formular „Diskussion beenden“ in „Vorschlag erstellen“

## Status: Approved
**Created:** 2026-11-19
**Last Updated:** 2026-11-19

## Dependencies
- Requires: PROJ-90 (Formularwahl und Liste der geführten Formulare)
- Requires: PROJ-93 (Formular ohne Kategorie, Produktbereich ausgeblendet)
- Requires: PROJ-80 (Kategorie des Tickets aus der Klassifikation)
- Requires: PROJ-70 (interne Notiz beim Senden der Antwort)

## Hintergrund
Ticket 2140381: Die Kundin wiederholte nach drei ausführlichen Antworten (Rückfrage, Ablehnung mit Kulanz, zwei Optionen) dieselben Argumente. Trotz des Freitexts „Wir haben unseren bisherigen Erklärungen nichts mehr hinzuzufügen“ kam ein langer Entwurf mit Verständnisfloskel, erneuter Erklärung, neu formuliertem Angebot und Rückfrage. Ursache: Der Freitext wird als Fakten gelesen, die der Entwurf konkret wiedergeben soll, und die allgemeinen Vorgaben (Empathie, Anknüpfen an frühere Antworten, Pflichtinhalte aus dem Wissen) bleiben wirksam. Ein geführtes Formular setzt die Entscheidung in feste Sätze um, die allem anderen vorgehen.

## User Stories
- Als Mitarbeiter will ich eine sich im Kreis drehende Diskussion per Klick mit einem kurzen, bestimmten Abschluss beenden, ohne dass der Entwurf erneut erklärt oder Verständnis bekundet.
- Als Mitarbeiter will ich angeben, ob unser bisheriges Angebot weiter gilt, damit der Entwurf es in einem Satz erwähnt oder gar nicht.
- Als Team wollen wir im Ticket festhalten, dass weitere unveränderte Nachrichten nur noch geschlossen werden.

## Acceptance Criteria

### Formularwahl
- [x] „Diskussion beenden“ erscheint in der Auswahl „Formular“ nur, wenn die Kategorie des Tickets (bestätigt, sonst vorgeschlagen) WISMO, Reklamation, Adressänderung oder Storno / Änderung der Bestellung ist **und** mindestens 3 Kundenantworten vorliegen (Nachrichten des Kunden nach unserer ersten Antwort im Ticket, automatische Nachrichten zählen nicht).
- [x] Das Formular wird nie vorgewählt. Ein Vorschlag mit diesem Formular, obwohl es nicht angeboten wird, wird mit einem Hinweis abgelehnt.
- [x] Produktbereich, Freitext und DHL-Retourenlabel entfallen; der Vorschlag gilt als Fall ohne Produktbezug. „Erstellt mit“ nennt „Diskussion beenden“ ohne „kein Produktbezug“.

### Felder
- [x] Pflichtfrage „Bleibt unser bisheriges Angebot bestehen?“ (Ja, die angebotenen Optionen gelten weiterhin / Nein, es ist kein Angebot offen); ohne Antwort: „Bitte angeben, ob das bisherige Angebot bestehen bleibt.“
- [x] Optional „Kernaussage“ (höchstens 300 Zeichen): ein Satz zum maßgeblichen Grund. Leer: Der Entwurf nennt keinen Grund.

### Wirkung auf den Vorschlag
- [x] Der Entwurf sagt sinngemäß: intensiv geprüft, im Verlauf mehrerer Nachrichten ausführlich erklärt, auf alle Fragen und Argumente eingegangen, (Optionen angeboten), nichts hinzuzufügen, Diskussion beendet.
- [x] Höchstens fünf Sätze; kein Verständnis, Bedauern oder Entschuldigen; kein Eingehen auf einzelne Argumente; keine neue Zusage, Rückfrage, Ersatzproduktion, Erstattung oder Platzhalter.
- [x] Gilt das Angebot weiter: genau ein Satz dazu, ohne Aufzählung. Sonst kein Angebot.
- [x] Der Block geht dem Wissen und allen allgemeinen Vorgaben des Analyse-Prompts vor (neue Regel im Prompt, Version analysis-2026-11-19.1).

### Interne Notiz
- [x] Beim Senden ist das Feld „Interne Notiz“ mit einem festen Text der App vorbefüllt (änderbar): „Diskussion abschließend beendet. Weitere Nachrichten zum unveränderten Sachverhalt, ohne neue substantielle Hinweise, die nicht schon zuvor hätten eingebracht werden können, werden nicht mehr kommentiert, sondern nur noch geschlossen.“ Gilt für alle Kanäle.

### Wissen
- [x] TONE-003 beschreibt den Abschluss „Diskussion beenden“ und das Schließen weiterer unveränderter Nachrichten.

## Edge Cases
- Zusammengehörige Tickets (PROJ-52) zählen bei den Kundenantworten nicht mit: gezählt wird nur das geöffnete Ticket, damit Anzeige und Prüfung beim Absenden gleich rechnen.
- Ein zurückgespultes Ticket (Testmodus) zählt nur die Nachrichten bis zum gewählten Stand.
- Bringt der Kunde echte neue Tatsachen, wählt der Mitarbeiter ein anderes Formular; der Hinweis im Formular sagt das.
- Ein früherer Vorschlag mit diesem Formular füllt das Formular wieder vor, sofern es noch angeboten wird; sonst startet das Standardformular der Kategorie.

## Decision Log
- 2026-11-19: Geführtes Formular statt nur Prompt-Anpassung: Feste Sätze der App sind verlässlicher als ein Freitext, der als Fakten wiedergegeben wird.
- 2026-11-19: Nur für WISMO, Reklamation, Adressänderung, Storno und ab der 3. Kundenantwort (Vorgabe des Auftraggebers).
- 2026-11-19: Die interne Notiz ist ein fester Text der App, nicht vom Modell, und gilt für alle Kanäle.
