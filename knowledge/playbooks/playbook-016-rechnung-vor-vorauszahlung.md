---
id: PLAYBOOK-016
title: Kunde verlangt vor Vorauszahlung eine Rechnung
type: playbook
status: draft
products: []
categories:
- order-process-question
topics:
- invoice-request
- prepayment
customer_types:
- b2b-reseller
sales_channels:
- fachhaendler
related_knowledge:
- POLICY-019
- PROCESS-008
- TONE-002
---

Gilt für Foto-Fachhändler / Reseller im Kanal fachhaendler und für alle Produkte.

# Fallmuster

Ein Händler muss beim Bestellabschluss über Mollie bezahlen und verlangt eine Rechnung, um die Vorauszahlung leisten zu können.

# Typische Ursache

Der Kunde möchte für seinen internen Zahlungsablauf bereits vor Versand eine Rechnung.

# Zu prüfen

1. Ist die Bestellung zugeordnet?
2. Erfolgt die Zahlung für diese Bestellung über Mollie?
3. Ist die Bestellung noch nicht versandt?

# Noch nicht entscheidbar wenn

Die Bestellnummer fehlt: beim Kunden erfragen. Ist die gewählte Zahlungsart nicht bekannt, zunächst anhand der Bestellung prüfen; wenn sie dort nicht feststellbar ist, den Kunden nach seiner ausgewählten Zahlungsart fragen.

# Empfohlene Maßnahme

Den Ablauf gemäß POLICY-019 erklären. Die im Bestell- und Bezahlvorgang bereitgestellten Zahlungsinformationen verwenden lassen.

# Kommunikation

Bei passender Du-Ansprache nach TONE-002:

„Die Zahlungsinformationen für Deine Vorauszahlung erhältst Du direkt über die im Bestellprozess ausgewählte Zahlungsmethode. Sobald die Zahlung bei Mollie bestätigt ist, wird Deine Bestellung automatisch zur Bearbeitung freigegeben. Deine Rechnung erhältst Du wie gewohnt automatisch per E-Mail, sobald Deine Bestellung versendet wurde.“

# No-Gos

- Eine gesonderte Rechnung vor Versand zusagen.
- Für Mollie eine eigene Bankverbindung oder einen neuen Zahlungsweg erfinden.
