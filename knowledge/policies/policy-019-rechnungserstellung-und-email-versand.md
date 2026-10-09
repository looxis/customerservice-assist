---
id: POLICY-019
title: Rechnungserstellung und automatischer Rechnungsversand außerhalb von Amazon
type: policy
status: draft
products: []
categories:
- order-process-question
topics:
- invoice-request
- invoice-delivery
- prepayment
customer_types: []
sales_channels:
- looxis-de
- fachhaendler
- looxis-pro
- masterpics
related_knowledge: []
---

Gilt für alle Produkte und Kundenarten in den Kanälen looxis-de, fachhaendler, looxis-pro und masterpics.

# Regel

Mit Versand der Bestellung wird die Rechnung automatisch erzeugt und per E-Mail verschickt. Vor Versand wird für die Vorauszahlung keine gesonderte Rechnung erstellt.

Bei Endverbrauchern, die einmalig oder erstmals bestellen, wird die E-Mail-Adresse aus der Bestellung verwendet. Bei Kunden mit Kundennummer wird eine hinterlegte „alternative Rechnungs Email“ verwendet; fehlt diese, gilt die hinterlegte Kunden- beziehungsweise Bestell-E-Mail-Adresse.

# Hintergrund

Wiederkehrende Kunden können eine alternative Adresse für den Rechnungsempfang hinterlegen, etwa für ihre Buchhaltung.

Kunden mit Online-Zahlung über Mollie erhalten die Zahlungsinformationen im Bestell- und Bezahlvorgang. Dazu gehören SEPA-Überweisung an das zugeordnete Mollie-Konto, PayPal, Kreditkarte und angebotene Klarna-Zahlarten. Bei Vorauszahlung wird die Bestellung nach Zahlungsbestätigung freigegeben. Ein Rechnungswunsch vor der Zahlung ändert diesen Ablauf nicht.

# Vorgehen

- Vor Versand: den automatischen Rechnungsversand kurz nach Versand ankündigen.
- Nach Versand: den tatsächlichen Rechnungsempfang und die verwendete Adresse anhand der verfügbaren Daten prüfen. Auf den Versand an diese Adresse und den Spam-Ordner hinweisen. Die vorhandene Rechnung kann direkt erneut als PDF mitgeschickt werden.
- Rechnung gelöscht: die vorhandene Rechnung direkt erneut als PDF senden.
- Der erneute Versand einer vorhandenen Rechnung ist keine Rechnungsänderung.

# Ausnahmen

Amazon ist in POLICY-021 geregelt. Nachträgliche Rechnungsänderungen sind in POLICY-020 geregelt.
