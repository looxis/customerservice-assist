---
id: POLICY-021
title: 'Amazon: automatische Rechnungsbereitstellung und erneuter Versand'
type: policy
status: draft
products: []
categories:
- order-process-question
topics:
- invoice-request
- invoice-delivery
customer_types: []
sales_channels:
- amazon
related_knowledge: []
summary: "Amazon: Rechnung wird automatisch bereitgestellt; erneuter Versand. Gilt, wenn ein Amazon-Kunde eine Rechnung anfordert oder nicht findet; gilt nicht für Änderungen (POLICY-022)."
retrieval: triggered
triggers:
- Rechnung
- Rechnungskopie
- Rechnungsbeleg
- invoice
- fattura
- facture
- factura
exclude_triggers:
- änder
- korrigier
- berichtig
- Adresse
- Rechnungsadresse
- Anschrift
- Empfänger
- Rechnungsempfänger
- Firma
- Firmierung
- Firmenname
- Rechtsform
- umschreib
- stornier
- falsch
- USt-IdNr
- Umsatzsteuer
- neu ausstellen
- neue Rechnung
when:
actions:
---

Gilt für alle Produkte und Kundenarten im Vertriebskanal Amazon.

# Regel

LOOXIS lädt die Rechnung nach Versand der Ware automatisch bei Amazon hoch. Kunden können sie als PDF bei „Meine Bestellungen“ an der betreffenden Bestellung über „Rechnung“ herunterladen.

# Vorgehen

Für die Bearbeitung muss die Bestellnummer vorliegen. Fehlt sie, wird sie zuerst beim Kunden erfragt.

Bei einer ersten Rechnungsanforderung wird der Downloadweg freundlich erklärt und angeboten, bei Schwierigkeiten die Rechnung herauszusuchen und zu senden.

Meldet der Kunde anschließend, dass er die Rechnung nicht findet oder der Download nicht klappt, wird die vorhandene Rechnung anhand der Bestellnummer aus EOCS abgerufen und der Antwort als PDF beigefügt.

Bestätigt der Kunde, die Rechnung gelöscht zu haben, wird die vorhandene Rechnung direkt erneut als PDF gesendet.

# Abgrenzung

Eine konkrete Uploadfrist wird nicht zugesagt, weil ihr genauer Zeitraum nicht bestätigt wurde. Rechnungsänderungen stehen in POLICY-022.
