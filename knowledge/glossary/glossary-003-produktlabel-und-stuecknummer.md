---
id: GLOSSARY-003
title: Produktlabel, interne Auftragsnummer und Stücknummer
type: glossary
status: draft
customer_types:
sales_channels:
products:
categories:
topics:
- product-label
- customer-configuration
related_knowledge:
- GLOSSARY-002
- PLAYBOOK-006
---

Gilt für alle Kundenarten und Vertriebskanäle bei Produkten mit dem beschriebenen Produktlabel.

# Produktlabel

Ein etwa 6 × 3 cm großer Aufkleber auf der Verpackung enthält einen Barcode und darunter dessen menschenlesbaren Zahlencode. Außerdem sind Produktbezeichnung, Marke und eine Routing-Angabe wie „Grün“, „Blau“ oder „Rot“ enthalten. Das Produktlabel ist nicht das Versandlabel mit Anschrift und Sendungsnummer.

# Nummernaufbau

Das schematische Beispiel `123456-1_1` besteht aus:

| Teil | Bedeutung |
|---|---|
| `123456` | Sechsstellige interne Auftragsnummer; identifiziert den Auftrag eindeutig in EOCS |
| `-1` | Position des Produkts innerhalb der Bestellung beziehungsweise des Lieferscheins |
| `_1` | Einzelnes Stück innerhalb dieser Position |

Externe Bestellnummern unterscheiden sich nach Vertriebskanal. Die interne Auftragsnummer vereinheitlicht die Zuordnung in EOCS. Die vollständige Labelnummer identifiziert zusätzlich Position und Stück.

# Position und Stück unterscheiden

Bei einer Position mit drei Stück heißen die Stücknummern beispielsweise `123456-1_1`, `123456-1_2` und `123456-1_3`. Die Stücke einer Position verwenden exakt dieselben Produktionsdaten. Die Nummerierung dient der Vollständigkeitskontrolle.

`123456-1_1` und `123456-4_1` gehören dagegen zu unterschiedlichen Positionen desselben Auftrags. Separate Konfigurationen können unterschiedliche Produktionsdaten ergeben, auch bei gleicher Vorlage; dazu GLOSSARY-002.

# Bedeutung bei einer Verwechslung

Das Label steuert nach dem Scannen die Zuordnung bei Kommissionierung und Versand. Ein korrekt lesbares Label beweist deshalb nicht, dass es auf dem richtigen physischen Produkt angebracht wurde. Bei Verdacht auf eine Verwechslung müssen Labelnummer und tatsächliche Personalisierung gemeinsam geprüft werden.
