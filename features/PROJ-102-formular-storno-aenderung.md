# PROJ-102: Geführtes Formular „Storno / Änderung“ in „Vorschlag erstellen“

## Status: In Progress
**Created:** 2026-11-26
**Last Updated:** 2026-11-26

## Dependencies
- Requires: PROJ-90 (Formularwahl und Liste der geführten Formulare)
- Requires: PROJ-80 (Kategorien `storno` und `adressaenderung` aus der Klassifikation neuer Tickets)
- Requires: PROJ-93 (neue Adresse nur als Platzhalter an die KI, Laden der Adresse aus EOCS)
- Requires: PROJ-7 (Bestellung und Sendungen aus EOCS)
- Wissen: POLICY-057, POLICY-079, POLICY-039

## Hintergrund
Anlass ist Ticket 2140719 vom 2026-11-26. Ein Amazon-Kunde wollte stornieren, weil er „nicht für beide bezahlen“ will. Die Bestellung war schon verschickt. Das Wort „bezahlen“ hat POLICY-039 (Zahlungsabwicklung bei Amazon) als verbindliches Wissen ausgelöst. Der Entwurf hängte deshalb einen Absatz zur Zahlungsabwicklung an, der für den Fall nicht relevant war. Dafür gibt es zwei Gegenmittel:
- **Allgemein:** Der Analyse-Prompt wendet ein Dokument, das über ein Stichwort gefunden wurde, nur noch an, wenn es ein eigenes Anliegen des Kunden betrifft (`analysis-retrieval.md` und `analysis.md`, Stand 2026-11-26). POLICY-039 grenzt sich zusätzlich gegen Zahlung als bloße Begründung ab.
- **Geführt:** Bei Storno- und Adressänderungswünschen klickt der Mitarbeiter das Ergebnis an. Die App gibt der KI daraus feste Vorgaben.

## User Stories
- Als Mitarbeiter will ich bei einem Storno- oder Änderungswunsch per Klick angeben, ob es geklappt hat, damit der Entwurf das Ergebnis richtig und ohne Nebenthemen mitteilt.
- Als Mitarbeiter will ich eine vorgenommene Adressänderung bestätigen, mit der neuen Adresse aus EOCS.
- Als Mitarbeiter will ich bei einer schon verschickten Bestellung die Sendungsnummer samt Tracking-Link nennen, ohne sie herauszusuchen.

## Acceptance Criteria

### Formularwahl
- [ ] Die Auswahl „Formular“ enthält „Storno / Änderung“ hinter „Aktualisierte WISMO-Infos“. Vorgewählt ist es bei den Kategorien `storno` und `adressaenderung`, bei allen anderen Tickets ist es wählbar. Bei einem Kanal ohne Produkte (PROJ-94) wird es nicht angeboten.
- [ ] Produktsuche, Wissensbereich, Freitext und DHL-Retourenlabel sind ausgeblendet und werden nicht gesendet. Die Zeile „Erstellt mit“ nennt „Storno / Änderung“ ohne „kein Produktbezug“.

### Felder
- [ ] Pflichtfrage „Ergebnis“, nichts vorgewählt: Storno / Änderung erledigt (Ausnahme) | Storno / Änderung nicht möglich, Bestellung bereits in Bearbeitung | Adressänderung vorgenommen | Adressänderung nicht mehr möglich, bereits verschickt. Ohne Antwort erscheint „Bitte das Ergebnis angeben.“
- [ ] Bei „erledigt“ und „nicht möglich“ ist „Storno oder Änderung?“ Pflicht. Bei einer erledigten Änderung ist auch „Was wurde geändert?“ Pflicht (höchstens 300 Zeichen).
- [ ] Bei „Adressänderung vorgenommen“ ist „Neue Adresse“ Pflicht (höchstens 500 Zeichen). Das Feld wird bei der Wahl aus der geladenen Bestellung in EOCS gefüllt, und der Knopf „Adresse aus EOCS laden“ lädt sie erneut.
- [ ] Bei „bereits verschickt“ stehen die Sendungen der geladenen Bestellungen im Formular. Ohne Sendung erscheint ein gelber Hinweis.
- [ ] „Zusätzliche Informationen“ (höchstens 1.500 Zeichen) gilt für alle Ergebnisse.

### Wirkung auf den Vorschlag
- [ ] **Storno erledigt:** Der Entwurf bestätigt die Stornierung als Ausnahme. Einen Grund nennt er nur aus den Zusätzlichen Informationen, eine Erstattung oder Frist nur, wenn sie dort steht. Empfehlung: senden und schließen.
- [ ] **Änderung erledigt:** Der Entwurf bestätigt die genannte Änderung. Empfehlung: senden und schließen.
- [ ] **Nicht möglich:** Die Bestellung ist freigegeben und in Bearbeitung. Ist sie schon verschickt, sagt der Entwurf das. Die Kanalregeln aus dem Wissen gelten weiter (Amazon, Prüfung ab 150 €).
- [ ] **Adresse geändert:** Der Entwurf bestätigt die neue Adresse über den Platzhalter `[NEUE-LIEFERADRESSE]`. Die Adresse geht nicht an die KI.
- [ ] **Bereits verschickt:** Der Entwurf nennt Sendungsnummer und Tracking-Link (DHL/UPS) aus EOCS. Er weist darauf hin, dass der Kunde über den Link beim Paketdienst eventuell noch Zustelloptionen anpassen kann und dass das nicht in unserer Hand liegt. Ohne Sendung steht `[SENDUNGSNUMMER]` im Entwurf.
- [ ] Bei jedem Ergebnis geht der Entwurf nicht auf Zahlungsabwicklung ein, es sei denn, der Kunde fragt ausdrücklich danach oder die Zusätzlichen Informationen sagen etwas dazu.
- [ ] Die Antworten werden mit dem Vorschlag gespeichert und belegen das Formular beim nächsten Öffnen vor.

## Edge Cases
- **Mehrere Bestellungen oder Sendungen:** Alle Sendungen mit Sendungsnummer gehen an die KI, je mit Bestellnummer.
- **Keine Bestellung geladen:** Die Adresse wird von Hand eingetragen. Bei „bereits verschickt“ steht ein Platzhalter im Entwurf.
- **Formularwechsel:** Eingaben bleiben im Browser erhalten. Gesendet werden nur die des gewählten Formulars und Ergebnisses.

## Decision Log
| Decision | Rationale | Date |
|----------|-----------|------|
| Vorgewählt bei `storno` und `adressaenderung` | Das Formular deckt beide Anliegen ab (Vorgabe des Auftraggebers) | 2026-11-26 |
| „Erledigt“ und „nicht möglich“ auch für Änderungen an Motiv, Artikel und Menge | Vorgabe des Auftraggebers | 2026-11-26 |
| Tracking-Link aus EOCS, nicht über das Werkzeug zum Sendungsstatus | Es braucht nur Nummer und Link, keinen Live-Status | 2026-11-26 |
| Keine Datenbankänderung | Die Antworten liegen wie bei den anderen Formularen in den Formularangaben des Vorschlags | 2026-11-26 |
