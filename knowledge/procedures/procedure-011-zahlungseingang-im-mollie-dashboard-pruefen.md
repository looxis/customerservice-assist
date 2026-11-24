---
id: PROCEDURE-011
title: Zahlungseingang einer Mollie-Bestellung in EOCS und im Mollie-Dashboard prüfen
type: procedure
status: active
actions:
  - payment-check
customer_types:
sales_channels:
  - looxis-de
  - fachhaendler
  - looxis-pro
products:
categories:
  - order-process-question
topics:
  - payment-processing
summary: "Interne Anleitung, wie geprüft wird, ob Mollie den Zahlungseingang zu einer Bestellung registriert hat: zuerst in EOCS auf der Rechnungsseite der Bestellung (Adresse der Bestellung plus „/invoice/“, Zahlungsstand unten auf der Seite); für die Prüfung einer vom Kunden genannten Referenznummer im Mollie-Dashboard unter „Umsatz“ die Bestellnummer suchen. Gebraucht, wenn ein Kunde sagt, er habe schon überwiesen oder bezahlt, oder einen Zahlungsnachweis schickt. Gilt nicht für Rechnung, Lastschrift und Amazon."
retrieval: triggered
triggers:
  - Zahlungseingang
  - bereits überwiesen
  - Überweisung veranlasst
  - Zahlung nicht angekommen
  - Referenznummer
  - Zahlungsnachweis
  - Überweisungsbeleg
  - Zahlungsbestätigung
related_knowledge:
  - POLICY-043
  - POLICY-057
---

Gilt für Bestellungen in den Vertriebskanälen looxis-de, fachhaendler und looxis-pro, deren Zahlung über Mollie abgewickelt wird.

# Zweck

Klären, ob Mollie die Zahlung zu einer Bestellung registriert hat und ob die vom Kunden genannte Referenznummer zur Bestellung gehört, damit die Antwort an den Kunden stimmt (POLICY-043).

# Voraussetzungen

- Die Bestellnummer ist bekannt.
- Zugang zum Mollie-Dashboard (Login).

# Arbeitsschritte

1. EOCS: Die Bestellung öffnen und an ihre Adresse „/invoice/“ anhängen (z. B. `https://eocs.loox.is/orders/768261/invoice/`). Unten auf der Seite steht der Zahlungsstand. Im Formular „Zahlung / Rechnung“ öffnet der Knopf „Zahlungsstand in EOCS prüfen“ diese Seite direkt.
2. Reicht der Zahlungsstand aus EOCS (keine Referenznummer zu prüfen), weiter mit Schritt 6.
3. Mollie: [Mollie-Dashboard öffnen](link:mollie-dashboard) und einloggen.
4. Mollie: Oben links unter „Umsatz“ die Bestellnummer in die Suche eingeben.
5. Mollie: Die gefundene Bestellung anklicken. Die Details werden angezeigt.
6. Prüfen, ob der Zahlungseingang registriert ist und (im Mollie-Dashboard) ob die vom Kunden genannte Referenznummer zur Bestellung passt.
7. Das Ergebnis in der Antwort an den Kunden verwenden (POLICY-043) und im Ticket als interne Notiz festhalten.

# Kritische Hinweise

- Achtung: Wird keine Bestellung gefunden, die Bestellnummer auf Tippfehler prüfen, bevor dem Kunden etwas gesagt wird.
- Achtung: Das Dashboard zeigt nur, was Mollie registriert hat, nicht den Banktransfer selbst. Dem Kunden nicht bestätigen, die Überweisung sei „unterwegs“ oder „angekommen“.

# Abschlusskontrolle

- [ ] Zahlungsstand in EOCS bzw. Bestellung im Mollie-Dashboard gefunden
- [ ] Stand des Zahlungseingangs geprüft
- [ ] Referenznummer des Kunden mit der Bestellung verglichen
- [ ] Interne Notiz im Ticket gesetzt
