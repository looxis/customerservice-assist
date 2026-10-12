---
id: POLICY-022
title: 'Amazon: Rechnungsadresse oder Firmierung auf Kundenwunsch ändern'
type: policy
status: draft
products: []
categories:
- order-process-question
topics:
- invoice-correction
- invoice-recipient
- sales-channel
customer_types: []
sales_channels:
- amazon
related_knowledge: []
summary: "Amazon: Rechnungsadresse oder Firmierung auf Kundenwunsch ändern (Storno und Neuausstellung). Gilt nur auf Amazon bei Änderungswünschen."
retrieval: triggered
triggers:
- Rechnungsadresse
- Rechnungsempfänger
- Rechnungsänderung
- Rechnungskorrektur
- Rechnung ändern
- Rechnung korrigieren
- Rechnung berichtigen
- Rechnung umschreiben
- Rechnung falsch
- Rechnung stimmt nicht
- Rechnung lautet auf
- falsche Rechnung
- neue Rechnung
- Rechnung auf die Firma
- Rechnung auf unsere Firma
- Rechnung auf meine Firma
- Firmierung
- Firmenname
- Rechtsform
- USt-IdNr
- Umsatzsteuer-ID
- Adresse auf der Rechnung
- Anschrift auf der Rechnung
- ändern Sie die Rechnung
- korrigieren Sie die Rechnung
exclude_triggers:
when:
actions:
---

Gilt für alle Produkte und Kundenarten im Vertriebskanal Amazon.

# Regel

LOOXIS erhält über die derzeit implementierte Amazon-Schnittstelle nur die Lieferadresse, nicht die Rechnungsadresse. Deshalb wird die Lieferadresse für die ursprüngliche Rechnung verwendet.

Verlangt der Kunde eine andere Rechnungsadresse oder Firmierung, wird die ursprüngliche Rechnung storniert und mit den vom Kunden gewünschten Angaben neu ausgestellt. Dies umfasst auch die gewünschte Ausstellung auf eine Firma.

# Befugnis und Prüfung

Gewöhnliche Änderungswünsche werden ohne zusätzliche Freigabekette bearbeitet. Der Kundenservice muss weder die Existenz des Unternehmens noch die Berechtigung des Kunden gesondert prüfen.

Nur bei konkreten Hinweisen auf Missbrauch oder offensichtlich falscher Zuordnung wird der Fall ausnahmsweise der Geschäftsführung vorgelegt. Es findet keine routinemäßige Prüfung statt.

# Voraussetzungen

Die Bestellung und ursprüngliche Rechnung müssen zugeordnet werden können. Die gewünschten vollständigen Rechnungsangaben müssen vorliegen; fehlende Angaben werden beim Kunden erfragt.

# Abgrenzung

Es wird die Rechnung storniert, nicht die Bestellung. Die Korrektur allein löst keine Erstattung aus. Die Regeln anderer Kanäle stehen in POLICY-020.
