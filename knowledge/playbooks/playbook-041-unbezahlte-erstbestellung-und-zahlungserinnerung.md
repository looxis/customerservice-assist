---
id: PLAYBOOK-041
title: Zahlungserinnerung oder Stornowunsch nach fehlgeschlagener Erstbestellung
type: playbook
status: draft
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
  - order-release
summary: "Ein Kunde bekommt eine Zahlungserinnerung oder Mahnung für eine Bestellung, die er nicht mehr will, weil die erste Zahlung fehlschlug und er danach neu bestellt und bezahlt hat, oder er möchte eine Bestellung stornieren. Die Antwort beantwortet zuerst seine Frage, sagt dann verbindlich, dass die unbezahlte Bestellung nicht produziert und automatisch storniert wird, ohne Kosten, und dass er die Erinnerung ignorieren kann; sind beide Bestellungen bezahlt, gilt die Storno-Regel für freigegebene Bestellungen."
retrieval: triggered
triggers:
  - Zahlungserinnerung
  - Zahlungsaufforderung
  - zweite Bestellung
  - zweimal bestellt
  - doppelt bestellt
  - erste Bestellung stornieren
  - Bestellung stornieren
  - Bestellung nicht bezahlt
  - Zahlung fehlgeschlagen
  - Zahlung abgebrochen
related_knowledge:
  - POLICY-057
  - POLICY-043
  - POLICY-001
---

Gilt für alle Aufträge in den Vertriebskanälen looxis-de, fachhaendler und looxis-pro. Nicht für Amazon und masterpics.

# Fallmuster

Der Kunde wollte bestellen, die Zahlung schlug fehl oder wurde abgebrochen, und er löste eine zweite Bestellung aus, die bezahlt wurde. Für die erste, unbezahlte Bestellung bekommt er eine Zahlungserinnerung und schreibt uns an, oder er bittet um Stornierung.

# Erkennungsmerkmale

- Der Kunde nennt eine Zahlungserinnerung, Zahlungsaufforderung oder Mahnung zu einer Bestellung, die er nicht mehr will.
- Zwei Bestellungen desselben Kunden kurz hintereinander, eine bezahlt, eine offen.
- Der Kunde bittet um Stornierung einer Bestellung.

# Zu prüfen

1. Welche Bestellung ist bezahlt (freigegeben), welche offen? Nur aus den Bestelldaten oder dem Verlauf; sonst als interne Klärung vorsehen.
2. Welche Bestellung will der Kunde behalten? Ist das unklar, danach fragen.
3. Sind beide Bestellungen bezahlt (versehentlich doppelt)? Dann gilt für die Stornierung POLICY-057 (freigegebene Bestellung), auch die Ausnahme ab 150 €.

# Empfohlene Maßnahme

1. Die Frage des Kunden zuerst beantworten.
2. **Offene, unbezahlte Bestellung:** Dem Kunden verbindlich sagen, dass keine Kosten entstehen, die Bestellung nicht produziert und nicht verschickt und automatisch storniert wird und er die Zahlungserinnerung ignorieren kann (POLICY-057). Der Kunde muss nichts stornieren.
3. **Bezahlte Bestellung:** Sie läuft normal weiter.
4. **Beide Bestellungen bezahlt:** Regel für freigegebene Bestellungen nach POLICY-057 anwenden: in der Regel nicht stornierbar; bei Auftragswert ab 150 € auf Wunsch eine Prüfung mit der Produktionsleitung anbieten.

# Befugnis

Der Kundenservice sagt die automatische Stornierung der unbezahlten Bestellung zu. Eine Stornierung freigegebener Bestellungen sagt er nicht zu; die Prüfung ab 150 € läuft nur nach Rücksprache mit der Produktionsleitung (POLICY-057).

# Kommunikation

Siehe POLICY-057: Text für die nicht bezahlte Bestellung, für die freigegebene Bestellung und für die Prüfung ab 150 €.

# No-Gos

Nicht behaupten, die unbezahlte Bestellung werde produziert oder müsse bezahlt werden. Keine Stornierung einer bezahlten oder freigegebenen Bestellung zusagen. Den Zeitpunkt der automatischen Stornierung nicht beziffern.
