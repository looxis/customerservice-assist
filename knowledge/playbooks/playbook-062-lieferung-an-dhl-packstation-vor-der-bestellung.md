---
id: PLAYBOOK-062
title: "Lieferung an eine DHL-Packstation: Frage vor der Bestellung"
type: playbook
status: active

customer_types:
sales_channels:

products:
exclude_products:
categories:
- order-process-question
topics:
- delivery-options
- clear-communication

summary: "Der Kunde fragt vor seiner Bestellung, ob wir an eine DHL-Packstation liefern können. Bestätigen, dass das bei DHL-Versand innerhalb Deutschlands möglich ist, und erklären, wie die Packstation-Adresse mit persönlicher Postnummer richtig eingegeben wird."
retrieval: triggered
triggers:
- Packstation
- Paketstation
- Postnummer
- Post-Nummer
- Packstationsnummer
- Packstation-Nummer
- an eine Packstation
- an die Packstation
- in die Packstation
- parcel locker
exclude_triggers:
when:
actions:
priority:
risk_level:

owner:
reviewed_by:
last_reviewed:
trial_until:
trial_goal:

related_knowledge:
- POLICY-079
- POLICY-015
- PLAYBOOK-025
---

Gilt für alle Kundenarten und Vertriebskanäle, nur für Lieferadressen in Deutschland.

# Fallmuster

Ein Kunde hat noch nicht bestellt und fragt, ob wir seine Bestellung an eine DHL-Packstation liefern können.

# Erkennungsmerkmale

- Die Frage kommt vor der Bestellung; es gibt noch keine Bestellung oder Sendung.
- Der Kunde nennt eine Packstation als gewünschten Lieferort.

# Typische Ursache

Keine bekannt.

# Zu prüfen

1. Liegt die Lieferadresse in Deutschland? DHL-Packstationen gibt es nur in Deutschland.
2. Ist die Bestellung bereits versandt? Dann gilt nicht dieses Playbook, sondern POLICY-079.

# Berechtigt wenn

- Die Sendung geht mit DHL an eine Packstation in Deutschland.

Eine DHL-Packstation kann nur von DHL beliefert werden. Kann der Kunde den Versanddienst bei der Bestellung selbst wählen, muss er DHL wählen. Hat der Kunde keine Auswahl (zum Beispiel bei Amazon), versenden wir mit DHL, sofern eine DHL-Packstation als Lieferadresse angegeben ist.

# Nicht berechtigt wenn

- Die Lieferadresse liegt außerhalb Deutschlands.
- DHL steht für die Sendung nicht zur Verfügung. Dann ist eine Lieferung an eine Packstation nicht möglich.

# Noch nicht entscheidbar wenn

- Das Lieferland ist unklar. Dann die Zusage auf Deutschland beschränken.

# Fehlende Informationen

Keine bekannt.

# Empfohlene Maßnahme

Bestätigen, dass wir an eine DHL-Packstation liefern können, und erklären, wie die Adresse eingegeben wird:

- Im Feld für die Straße steht „Packstation“.
- Im Feld für die Hausnummer steht die Nummer der Packstation.
- Im Adresszusatzfeld steht die persönliche Postnummer des Empfängers.

Die persönliche Postnummer ist zwingend. Ohne sie ist keine Zustellung an die Packstation möglich. Diesen Punkt in der Antwort deutlich hervorheben.

# Befugnis

Der Kundenservice beantwortet die Frage selbst.

# Kommunikation

Kurz und klar antworten; die drei Adressfelder als Aufzählung nennen. Bei masterpics den Kommunikationsweg aus POLICY-015 beachten.

# No-Gos

- Keine Zusage für Packstationen außerhalb Deutschlands oder für einen anderen Paketdienst als DHL.
- Nicht verschweigen, dass ohne persönliche Postnummer keine Zustellung möglich ist.
- Keine Zusage, eine nach dem Versand falsch eingegebene Adresse noch zu korrigieren (POLICY-079).

# Beispiel

„Vielen Dank für Ihre Anfrage. Ja, wir können Ihre Bestellung an eine DHL-Packstation liefern. Bitte geben Sie die Lieferadresse dafür so ein:

- Straße: Packstation
- Hausnummer: die Nummer der Packstation
- Adresszusatz: Ihre persönliche Postnummer

Ganz wichtig ist Ihre persönliche Postnummer im Adresszusatz. Ohne sie kann DHL das Paket nicht in die Packstation zustellen.“
