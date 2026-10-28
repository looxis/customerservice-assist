---
id: POLICY-061
title: USt-IdNr von Firmen im EU-Ausland vor der Änderung prüfen
type: policy
status: draft
customer_types:
sales_channels:
  - looxis-de
  - fachhaendler
  - looxis-pro
  - masterpics
products:
categories:
  - order-process-question
topics:
  - vat-id
  - customer-account
summary: "Eine Firma im EU-Ausland mit Umsatzsteuer-Identifikationsnummer möchte, dass ein Konto für sie angelegt oder ihre Rechnungsadresse geändert wird. Die Nummer muss vorher als gültig geprüft werden, denn bei mehrwertsteuerfreier Abrechnung mit ungültiger Nummer müssen wir die nicht eingenommene Mehrwertsteuer selbst an das Finanzamt abführen. Gilt nicht für Firmen in Deutschland und nicht auf Amazon."
retrieval: triggered
triggers:
  - USt-IdNr
  - USt-ID
  - Umsatzsteuer-ID
  - Umsatzsteuer-Identifikationsnummer
  - Umsatzsteueridentifikationsnummer
  - VAT number
  - VAT ID
  - partita IVA
  - EU-Ausland
exclude_triggers:
when:
actions:
related_knowledge:
  - PLAYBOOK-046
  - POLICY-022
---

Gilt für alle Kundenarten in den Vertriebskanälen looxis-de, fachhaendler, looxis-pro und masterpics. Auf Amazon gilt sie nicht (siehe POLICY-022).

# Regel

Für eine Firma im EU-Ausland mit Umsatzsteuer-Identifikationsnummer (USt-IdNr) wird die Nummer als gültig geprüft, bevor der Kundenservice

- ein Konto für die Firma anlegt oder
- die Rechnungsadresse der Firma ändert.

Die Prüfung erfolgt mit der amtlichen EU-Abfrage (VIES).

# Hintergrund

Ist die USt-IdNr nicht gültig und bestellt der Kunde trotzdem mehrwertsteuerfrei, müssen wir die nicht eingenommene Mehrwertsteuer später trotzdem an das Finanzamt abführen. Bei einer Bestellung im Shop wird die Nummer nicht geprüft; der Kunde kann sie ohne unser Zutun aufgeben. Deshalb ist die Prüfung bei Kontoanlage und Adressänderung wichtig.

# Gilt wenn

- Es geht um eine Firma im EU-Ausland mit USt-IdNr, nicht um eine deutsche Firma.
- Der Kundenservice soll ein Konto anlegen oder die Rechnungsadresse ändern.

# Gilt nicht wenn

- Die Firma ihren Sitz in Deutschland hat. Dann ist kein Nachweis nötig.
- Die Bestellung über Amazon läuft.

# Vorgehen

1. Die USt-IdNr in der EU-Abfrage (VIES) prüfen.
2. Ist die Nummer **gültig**, die Änderung oder die Kontoanlage übernehmen.
3. Ist sie **ungültig oder nicht prüfbar**, die Änderung nicht übernehmen und den Kunden um die richtige Nummer bitten.

# No-Gos

- Eine Änderung für eine EU-Firma übernehmen, ohne die Nummer geprüft zu haben.
- Eine nicht geprüfte Nummer als gültig bezeichnen.

# Noch zu klären

- Wie wird mit bereits aufgegebenen Bestellungen einer Firma verfahren, deren Nummer sich nachträglich als ungültig erweist?
- Wird das Prüfergebnis im Ticket oder in EOCS vermerkt?
