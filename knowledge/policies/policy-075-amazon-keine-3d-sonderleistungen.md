---
id: POLICY-075
title: "Amazon-Ausschluss für Vorschau und 3D-Sonderleistungen"
type: policy
status: active
customer_types:
sales_channels:
  - amazon
products:
  - 3d-glass-photo
categories:
  - product-question
  - quote-request
  - order-process-question
topics:
  - interpretation
  - customer-configuration
  - product-expectation
summary: "Ein Amazon-Kunde wünscht eine Vorschau vor Gravur, eine vollständige 360°-Modellierung oder die Übernahme eigener 3D-Daten. Diese Zusatzleistungen sind für Amazon-Kunden ausgeschlossen; keine Umleitung zur E-Mail-Bestellung oder zu eigenen Verkaufskanälen anbieten."
retrieval: binding
triggers:
  - "Vorschau"
  - "Voransicht"
  - "Freigabe"
  - "360°"
  - "360 Grad"
  - "Modellierung"
  - "3D-Datei"
  - "3D Datei"
  - "STL-Datei"
  - "OBJ-Datei"
  - "preview"
  - "anteprima"
  - "aperçu"
exclude_triggers:
when:
actions:
related_knowledge:
  - POLICY-056
  - POLICY-071
  - POLICY-072
  - POLICY-073
  - PROCESS-007
---

Gilt für alle Kundenarten im Vertriebskanal Amazon.

# Regel

Folgende Leistungen werden für Amazon-Kunden nicht angeboten:

- eine kostenpflichtige Vorschau zur Freigabe bei einer Erstbestellung,
- die individuelle vollständige 360°-3D-Modellierung,
- die Übernahme fertig angelieferter 3D-Kundendaten.

# Vorgehen

Die angefragte Sonderleistung kurz und eindeutig als nicht verfügbar erklären. Keine Umleitung zur E-Mail-Bestellung, zu Mollie oder in eigene Verkaufskanäle anbieten.

# Abgrenzung

Dieser ausdrückliche Ausschluss der 3D-Sonderleistungen gilt auch dann, wenn POLICY-056 für andere, direkt per E-Mail angefragte Produkte einen Verkauf außerhalb von Amazon erlaubt.

Der Freigabeprozess nach einer Reklamation gemäß PROCESS-007 ist keine kostenpflichtige Vorschau bei einer Erstbestellung und wird durch diese Regel nicht aufgehoben.

