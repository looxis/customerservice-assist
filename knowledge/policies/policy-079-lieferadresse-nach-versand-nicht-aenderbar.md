---
id: POLICY-079
title: Lieferadresse nach dem Versand nicht mehr änderbar
type: policy
status: active

customer_types:
sales_channels:

products:
exclude_products:
categories:
- order-process-question
- complaint
topics:
- delivery-options
- clear-communication

summary: "Der Kunde möchte die Lieferadresse ändern, obwohl die Bestellung bereits versandt ist, zum Beispiel auf eine andere Anschrift oder eine DHL-Packstation. Erklärt, dass wir die Adresse nach dem Versand nicht mehr ändern können und der Kunde mögliche Zustelloptionen selbst in der Sendungsverfolgung des Paketdienstes prüft."
retrieval: triggered
triggers:
- Adresse ändern
- Lieferadresse ändern
- Lieferanschrift ändern
- Adressänderung
- Adresse korrigieren
- andere Adresse
- an eine andere Adresse
- falsche Adresse
- falsche Lieferadresse
- umleiten
- Umleitung
- change address
- change the address
- change delivery address
- wrong address
- cambiare indirizzo
- modificare indirizzo
- changer l'adresse
- changer d'adresse
exclude_triggers:
- Rechnungsadresse
when:
actions:
- address-change
priority:
risk_level:

owner:
reviewed_by:
last_reviewed:
trial_until:
trial_goal:

related_knowledge:
- POLICY-015
- PLAYBOOK-062
---

Gilt für alle Produkte, Kundengruppen und Vertriebskanäle.

# Regel

Nach erfolgtem Versand können wir die Lieferadresse nicht mehr ändern. Das gilt auch für eine Änderung auf eine DHL-Packstation.

# Vorgehen

Mit seiner Sendungsnummer kann der Kunde in der Sendungsverfolgung auf der Webseite des Paketdienstes prüfen, ob es Möglichkeiten gibt, auf die Zustellung Einfluss zu nehmen. Das klärt und veranlasst der Kunde selbst.

Ob und welche Einflussmöglichkeiten der Paketdienst anbietet, liegt außerhalb unseres Einflussbereichs. Keine bestimmte Option und keinen Erfolg zusagen (POLICY-015).

# Gilt nicht wenn

Die Sendung ist noch nicht versandt, oder es geht nicht um die Lieferadresse. Für Rechnungsadressen gelten eigene Regeln (zum Beispiel POLICY-022, PLAYBOOK-046). Bei masterpics den Kommunikationsweg aus POLICY-015 beachten.
