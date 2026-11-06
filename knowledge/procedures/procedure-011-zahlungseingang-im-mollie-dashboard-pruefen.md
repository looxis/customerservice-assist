---
id: PROCEDURE-011
title: Zahlungseingang einer Mollie-Bestellung im Mollie-Dashboard prüfen
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
summary: "Interne Anleitung, wie im Mollie-Dashboard geprüft wird, ob Mollie den Zahlungseingang zu einer Bestellung registriert hat und ob die vom Kunden genannte Referenznummer zur Bestellung passt: einloggen, unter „Umsatz“ die Bestellnummer suchen, Bestellung anklicken, Details ansehen. Gebraucht, wenn ein Kunde sagt, er habe schon überwiesen oder bezahlt, und kein Zahlungseingang sichtbar ist. Gilt nicht für Rechnung, Lastschrift und Amazon."
retrieval: triggered
triggers:
  - Zahlungseingang
  - bereits überwiesen
  - Überweisung veranlasst
  - Zahlung nicht angekommen
  - Referenznummer
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

1. Mollie: [Mollie-Dashboard öffnen](link:mollie-dashboard) und einloggen.
2. Mollie: Oben links unter „Umsatz“ die Bestellnummer in die Suche eingeben.
3. Mollie: Die gefundene Bestellung anklicken. Die Details werden angezeigt.
4. Mollie: Prüfen, ob der Zahlungseingang registriert ist und ob die vom Kunden genannte Referenznummer zur Bestellung passt.
5. Das Ergebnis in der Antwort an den Kunden verwenden (POLICY-043) und im Ticket als interne Notiz festhalten.

# Kritische Hinweise

- Achtung: Wird keine Bestellung gefunden, die Bestellnummer auf Tippfehler prüfen, bevor dem Kunden etwas gesagt wird.
- Achtung: Das Dashboard zeigt nur, was Mollie registriert hat, nicht den Banktransfer selbst. Dem Kunden nicht bestätigen, die Überweisung sei „unterwegs“ oder „angekommen“.

# Abschlusskontrolle

- [ ] Bestellung im Mollie-Dashboard gefunden
- [ ] Stand des Zahlungseingangs geprüft
- [ ] Referenznummer des Kunden mit der Bestellung verglichen
- [ ] Interne Notiz im Ticket gesetzt
