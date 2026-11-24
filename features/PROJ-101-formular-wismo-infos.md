# PROJ-101: Geführtes Formular „Aktualisierte WISMO-Infos“ in „Vorschlag erstellen“

## Status: In Progress
**Created:** 2026-11-24
**Last Updated:** 2026-11-24

## Dependencies
- Requires: PROJ-90 (Formularwahl und Liste der geführten Formulare)
- Requires: PROJ-80 (Kategorie `wismo` aus der Klassifikation neuer Tickets)
- Requires: PROJ-82, PROJ-99 (Sendungsverlauf, Fälle und Antwortregeln der WISMO-Automatisierung)
- Requires: PROJ-97 (Versandbenachrichtigungen aus Lettermint)

## Hintergrund
Tickets der Kategorie `wismo` beantwortet die App automatisch (PROJ-82, PROJ-99): Sie liest den Sendungsverlauf bei DHL/UPS, prüft die Versandbenachrichtigungen bei Lettermint und hält feste Antwortregeln ein. Anders eingeordnete Tickets bekommen das nicht, ebenso wenig Folgenachrichten nach der automatischen Antwort. Anlass ist Ticket 2140683 (2026-11-24): Eine italienische Nachricht klang nach Reklamation und wurde so eingeordnet, es ging aber um den Verbleib des Pakets. Oft muss man dem Kunden auch nur einen neuen Stand zur Sendung mitteilen.

## User Stories
- Als Mitarbeiter will ich mit einem Klick einen Entwurf mit dem aktuellen Stand der Sendung erstellen, auch wenn das Ticket nicht als WISMO eingeordnet ist.
- Als Mitarbeiter will ich, dass dieser Entwurf dieselben Fakten und Regeln nutzt wie die automatische WISMO-Antwort.
- Als Mitarbeiter will ich in einem Freitextfeld ergänzen können, was der Entwurf zusätzlich berücksichtigen soll.

## Acceptance Criteria

### Formularwahl
- [ ] Die Auswahl „Formular“ zeigt „Technischer Support | Reklamation | Zahlung / Rechnung | Aktualisierte WISMO-Infos | Neuversand | (Diskussion beenden) | Freitext“. Bei der Kategorie `wismo` ist „Aktualisierte WISMO-Infos“ vorgewählt, bei jedem anderen Ticket ist es wählbar.
- [ ] Bei einem Kanal ohne Produkte (WeCreateYour, PROJ-94) wird das Formular nicht angeboten.
- [ ] Produktsuche, Wissensbereich, „Kein Produktbezug“, Freitext, „So schreibst du es“ und DHL-Retourenlabel sind ausgeblendet und werden nicht gesendet. Die Zeile „Erstellt mit“ nennt „Aktualisierte WISMO-Infos“ ohne „kein Produktbezug“.

### Felder
- [ ] Liste der Sendungen je geladener Bestellung (Paketdienst und Sendungsnummer, höchstens 3 je Bestellung). Ohne Sendung mit Sendungsnummer erscheint der gelbe Hinweis „Keine Sendung mit Sendungsnummer – bitte die Bestellung laden.“
- [ ] „Zusätzliche Informationen“: optionales Freitextfeld mit höchstens 1.500 Zeichen.

### Wirkung auf den Vorschlag
- [ ] Beim Erstellen fragt die App den Sendungsverlauf beim Paketdienst und die Versandbenachrichtigungen bei Lettermint in Code ab, nicht die KI. Das Werkzeug „Sendungsstatus“ (PROJ-88) wird der KI dann nicht angeboten.
- [ ] Die KI bekommt die Fakten der Sendungen, die belegten Benachrichtigungen und die Antwortregeln der automatischen WISMO-Antwort (`WismoResponder::replyRules`).
- [ ] Der Entwurf behandelt nur Verbleib und Zustellung. Er macht keine Aussagen zu Mängeln, Reklamation, Erstattung oder Ersatz.
- [ ] Empfehlung: senden und schließen, wenn jede Sendung in einem Fall ist, den auch die Automatik schließt. Sonst bleibt das Ticket offen, z. B. bei einem Sonderfall der Zustellung oder wenn der Status nicht abrufbar ist.
- [ ] Ist kein Paketdienst erreichbar, entsteht der Vorschlag trotzdem mit dem Hinweis „Status beim Paketdienst nicht abrufbar“.
- [ ] Ohne geladene Sendung mit Sendungsnummer bricht die App vor dem KI-Aufruf ab: „Keine Sendung mit Sendungsnummer gefunden. Bitte die Bestellung laden oder ein anderes Formular wählen.“
- [ ] Die Belege aus Lettermint erscheinen in der Ergebnisansicht wie bei PROJ-97.
- [ ] Die Antworten werden mit dem Vorschlag gespeichert und belegen das Formular beim nächsten Öffnen vor.

## Edge Cases
- **Mehrere Bestellungen geladen:** alle Sendungen aller geladenen Bestellungen, höchstens 3 je Bestellung.
- **Lettermint nicht eingerichtet:** keine Benachrichtigungen, die Regeln greifen auf die allgemeine Formulierung zurück.
- **„Diskussion beenden“ bei WISMO:** bleibt ab der dritten Kundenantwort wählbar. Vorgewählt ist „Aktualisierte WISMO-Infos“.

## Decision Log
| Decision | Rationale | Date |
|----------|-----------|------|
| Bei Kategorie `wismo` vorgewählt, sonst manuell wählbar | Wunsch des Auftraggebers | 2026-11-24 |
| Paketdienst und Lettermint immer in Code abfragen | Gleiche Qualität wie die Automatik, der Aufruf kann nicht ausgelassen werden | 2026-11-24 |
| `WismoResponder` bleibt unverändert, neue Klasse `ShipmentSnapshot` nutzt dessen Regeln | Die laufende WISMO-Automatik nicht berühren (siehe PROJ-87) | 2026-11-24 |
| Keine Datenbankänderung | Die Antworten liegen in den Formularangaben des Vorschlags | 2026-11-24 |
