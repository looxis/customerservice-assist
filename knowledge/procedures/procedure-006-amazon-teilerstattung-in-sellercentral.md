---
id: PROCEDURE-006
title: Teilerstattung einer Amazon-Bestellung in der Sellercentral veranlassen
type: procedure
status: draft
actions:
  - partial-refund
customer_types:
sales_channels:
  - amazon
products:
categories:
  - complaint
topics:
  - refund
  - goodwill
summary: "Interne Anleitung, wie bei einer Amazon-Bestellung eine beschlossene Teilerstattung in der Amazon Sellercentral veranlasst wird: Bestellung öffnen, „Bestellung erstatten“, „Teilweise Erstattung“, Grund und Betrag eintragen, bestätigen. Nicht für Bestellungen aus anderen Kanälen."
retrieval: triggered
triggers:
  - Teilerstattung
  - teilweise Erstattung
  - Sellercentral
related_knowledge:
  - POLICY-009
  - PERMISSION-003
  - PERMISSION-008
  - PLAYBOOK-036
---

Gilt nur für Bestellungen im Vertriebskanal `amazon`.

# Zweck

Eine beschlossene Teilerstattung für eine Amazon-Bestellung über die Amazon Sellercentral auslösen.

# Voraussetzungen

- Die Teilerstattung ist beschlossen und freigegeben (zum Beispiel PERMISSION-003 oder PERMISSION-008), und der Betrag steht fest.
- Der Kunde ist, wo das vorgesehen ist, einverstanden (zum Beispiel PLAYBOOK-036).
- Die Amazon-Bestellnummer ist bekannt. Bei einer Teilerstattung auf eine neue Bestellung gilt POLICY-009.

# Arbeitsschritte

1. In der Amazon Sellercentral: Die Bestellung öffnen: [Bestellung in der Sellercentral öffnen](link:amazon-bestellung). Die Adresse lautet `https://sellercentral.amazon.de/orders-v3/order/<Bestellnummer>`; die Nummer am Ende entspricht genau der Amazon-Bestellnummer.
2. In der Sellercentral: Oben rechts auf „Bestellung erstatten“ klicken.
3. In der Sellercentral: Auf „Teilweise Erstattung“ klicken.
4. In der Sellercentral: Den Grund der Erstattung angeben, zum Beispiel „Ware nicht wie beschrieben“, und den Erstattungsbetrag eintippen.
5. In der Sellercentral: Die Erstattung bestätigen.

# Kritische Hinweise

- Achtung: Nur den beschlossenen Teilbetrag eintragen, nicht den Gesamtpreis der Bestellung.
- Achtung: „Teilweise Erstattung“ wählen, nicht die volle Erstattung.

# Abschlusskontrolle

- [ ] Erstattung ist in der Sellercentral bestätigt
- [ ] Betrag entspricht dem beschlossenen Teilbetrag
- [ ] Interne Notiz im Ticket gesetzt
