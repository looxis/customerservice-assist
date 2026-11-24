# PROJ-100: Geführtes Formular „Zahlung / Rechnung“ in „Vorschlag erstellen“

## Status: In Progress
**Created:** 2026-11-24
**Last Updated:** 2026-11-24

## Dependencies
- Requires: PROJ-90 (Formularwahl und Liste der geführten Formulare)
- Requires: PROJ-80 (Kategorie `zahlung` aus der Klassifikation neuer Tickets)
- Requires: PROJ-7 (Bestellung aus EOCS, Link zur Bestellung)
- Wissen: PLAYBOOK-060, POLICY-043, POLICY-044, PROCEDURE-011

## Hintergrund
Anlass ist Ticket 2140663 (2026-11-24). Ein Kunde schickte nur „here the payment … order #…“ mit einem Überweisungsbeleg als PDF. Der Entwurf lautete „Vielen Dank für die Zahlungsbestätigung, wir werden das der Bestellung hinzufügen“. Das war falsch, denn die Überweisung ging an Mollie. LOOXIS hat keinen Zugriff auf das Mollie-Konto. Mollie erkennt Zahlungen automatisch, wenn die Überweisungsdetails stimmen, und gibt die Bestellung dann selbst frei. Ob die Zahlung an Mollie ging, ist oft nur im Anhang erkennbar. Deshalb geben wenige Klicks des Mitarbeiters den Fall vor, wie bei Reklamation und Neuversand. Den Zahlungsstand liest der Mitarbeiter heute von Hand in EOCS ab, unten auf der Rechnungsseite der Bestellung (`…/orders/{id}/invoice/`).

## User Stories
- Als Mitarbeiter will ich bei einem Zahlungsnachweis per Klick Zahlungsart und Zahlungsstand angeben, damit der Entwurf richtig erklärt, dass Mollie die Zahlung automatisch erkennt, und den passenden Stand nennt.
- Als Mitarbeiter will ich den Zahlungsstand mit einem Klick in EOCS öffnen.
- Als Mitarbeiter will ich Rechnungswünsche, Fragen zu Erstattungen und Mahnungen von Lieferanten mit demselben Formular schnell beantworten.

## Acceptance Criteria

### Formularwahl
- [ ] Die Auswahl „Formular“ zeigt „Technischer Support | Reklamation | Zahlung / Rechnung | Neuversand | (Diskussion beenden) | Freitext“. Bei der Kategorie `zahlung` ist „Zahlung / Rechnung“ vorgewählt, bei jedem anderen Ticket ist es wählbar, auch bei WeCreateYour.
- [ ] Produktsuche, Wissensbereich, „Kein Produktbezug“, Freitext, „So schreibst du es“ und DHL-Retourenlabel sind ausgeblendet und werden nicht gesendet. Der Vorschlag gilt als Fall ohne Produktbezug. Die Zeile „Erstellt mit“ nennt „Zahlung / Rechnung“ ohne „kein Produktbezug“.

### Felder
- [ ] Pflichtfrage „Anliegen“, nichts vorgewählt. Die Optionen sind: Zahlungsnachweis / Überweisungsbestätigung | Frage zur Zahlung | Rechnung anfordern oder korrigieren | Stand einer Erstattung | Mahnung eines Lieferanten an LOOXIS. Ohne Antwort erscheint „Bitte das Anliegen angeben.“
- [ ] Beim Zahlungsnachweis sind „Zahlungsart der Bestellung“ (Mollie | Rechnung) und „Zahlungsstand laut EOCS“ (bezahlt und freigegeben | noch nicht registriert, Frist läuft | nicht bezahlt, Frist abgelaufen bzw. storniert) Pflicht. Bei einer Frage zur Zahlung sind beide Angaben optional.
- [ ] Unter dem Zahlungsstand steht je geladener Bestellung der Link „Zahlungsstand in EOCS prüfen“ auf `<EOCS-Bestellung>/invoice/` (neuer Tab). Ohne geladene Bestellung steht dort ein Hinweis.
- [ ] Bei „Rechnung“ ist die Frage „Liegt die Rechnung der Antwort bei?“ Pflicht (Ja, als Anhang | Nein, Korrektur wird intern geklärt). Bei „Erstattung“ ist „Ist die Erstattung veranlasst?“ Pflicht (Ja | Nein, noch offen).
- [ ] „Zusätzliche Informationen“ ist ein Freitextfeld mit höchstens 1.500 Zeichen und gilt für alle Anliegen, z. B. „doppelt bezahlt“ oder „an das LOOXIS-Konto statt an Mollie überwiesen“.

### Wirkung auf den Vorschlag
- [ ] **Zahlungsnachweis mit Mollie:** Der Entwurf dankt. Er erklärt, dass ein Nachweis nicht nötig ist, weil Mollie den Eingang automatisch erkennt, wenn IBAN, Betrag und Referenz stimmen, und die Bestellung automatisch freigibt. Je nach Stand sagt er dann:
  - bezahlt: Zahlung registriert, Bestellung freigegeben. Empfehlung ist senden und schließen.
  - offen: Angaben mit der Zahlungsanweisung abgleichen, die Frist dort ist maßgeblich, ohne Zahl der Tage.
  - storniert: Mollie zahlt zurück, in der Regel in 1 bis 3 Werktagen. Der Kunde soll die Bestellung neu auslösen.
  - Der Entwurf sagt nie, der Beleg werde hinzugefügt, weitergeleitet oder verbucht.
- [ ] **Zahlungsnachweis mit Rechnung:** Der Entwurf dankt und sagt, dass wir die Zahlung zuordnen, nennt den Stand und erwähnt Mollie nicht. Bei „storniert“ kündigt er nur eine interne Prüfung an.
- [ ] **Frage zur Zahlung:** Das Wissen regelt die Antwort. Zahlungsart und Stand gehen als Fakten an die KI, falls sie angegeben sind.
- [ ] **Rechnung und Erstattung:** Der Entwurf folgt der Antwort im Formular. Er nennt keine Anzahl Tage.
- [ ] **Mahnung eines Lieferanten:** kurze Eingangsbestätigung „erhalten und an die Buchhaltung weitergegeben“, ohne Bestellung, Produkte und Platzhalter. Empfehlung: weitergeben, senden und schließen.
- [ ] Die Antworten werden mit dem Vorschlag gespeichert und belegen das Formular beim nächsten Öffnen vor.

## Edge Cases
- **Stornowunsch:** gehört nicht in dieses Formular, sondern zu „Storno / Änderung der Bestellung“. Darauf weist der Einführungsmodus hin.
- **Amazon:** Amazon-Kunden zahlen nie an uns (POLICY-039). Das Formular hat keinen eigenen Zweig dafür, das Wissen deckt den Fall ab.
- **Mehrere Bestellungen geladen:** ein Link je Bestellung, jeweils mit der Bestellnummer.
- **Formularwechsel:** Eingaben bleiben im Browser erhalten, gesendet werden nur die des gewählten Formulars und des gewählten Anliegens.

## Decision Log
| Decision | Rationale | Date |
|----------|-----------|------|
| Formular gehört zur Kategorie `zahlung` | Der Auftraggeber wünscht die geführte Bearbeitung wie bei Reklamation und Neuversand | 2026-11-24 |
| Zahlungsstand per Klick, abgelesen in EOCS `…/invoice/` | Der Stand steht heute nur dort; der Knopf öffnet die Seite zur manuellen Prüfung | 2026-11-24 |
| Zahlungsart beim Nachweis abfragen | Auch Kunden mit Zahlung auf Rechnung schicken Nachweise, die eine eigene Antwort brauchen | 2026-11-24 |
| Mahnung eines Lieferanten bekommt eine kurze Eingangsbestätigung | Vorgabe des Auftraggebers | 2026-11-24 |
| Keine Datenbankänderung | Die Antworten liegen wie bei den anderen Formularen in den Formularangaben des Vorschlags | 2026-11-24 |
