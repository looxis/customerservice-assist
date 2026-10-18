---
id: PLAYBOOK-028
title: Fachhändler kann offene Bestellung nicht bezahlen und wünscht Lastschrift
type: playbook
status: draft
customer_types:
  - b2b-reseller
sales_channels:
  - fachhaendler
products:
categories:
  - order-process-question
topics:
  - payment-processing
summary: "Ein Foto-Fachhändler kann eine offene Bestellung mit Zahlungsabwicklung über Mollie nicht bezahlen (PayPal funktioniert nicht, keine Kontodaten) und möchte wieder per Lastschrift zahlen. Die Antwort erklärt zuerst die sofort nutzbare SEPA-Überweisung über Mollie und behandelt danach getrennt das Lastschriftmandat für künftige Bestellungen, ohne eine offene Lastschriftentscheidung zur aktuellen Zahlung zu blockieren."
retrieval: triggered
triggers:
  - Bankeinzug
  - Lastschrift
  - Einzugsermächtigung
  - wieder auf Bankeinzug
  - wieder per Lastschrift
  - Paypal funktioniert nicht
  - Zahlung mit Paypal
  - keine Kontodaten
  - kann die Bestellung nicht bezahlen
  - kann nicht bezahlen
related_knowledge:
  - POLICY-041
  - POLICY-042
  - POLICY-043
  - PROCESS-015
---

Gilt für Foto-Fachhändler / Reseller im Vertriebskanal fachhaendler.

# Fallmuster

Ein Fachhändler hat eine Bestellung aufgegeben, deren Zahlung über Mollie abgewickelt wird. Er kann sie nicht bezahlen, weil PayPal nicht funktioniert oder ihm Kontodaten fehlen, und bittet darum, sein Konto wieder auf Bankeinzug (Lastschrift) umzustellen.

# Erkennungsmerkmale

- Der Kunde nennt Probleme mit PayPal oder fehlende Kontodaten.
- Der Kunde wünscht Lastschrift oder Bankeinzug.
- Früher wurde ihm bereits eine Mandatsvorlage zugesandt (verknüpftes oder früheres Ticket).

# Zu prüfen

1. Zwei Anliegen trennen: die Zahlung der bestehenden Bestellung und die Rückkehr zur Lastschrift.
2. Zur Lastschrift: Wurde die Mandatsvorlage bereits gesendet? Ist im vorliegenden Verlauf eine Rücksendung dokumentiert? Ist der Grund der bisherigen Umstellung belegt (ungültiges Altmandat nach Shopumstellung) oder offen?

# Empfohlene Maßnahme

**Teil 1 – bestehende Bestellung (immer zuerst und ohne Aufschub beantworten):** Die klassische SEPA-Überweisung über Mollie erklären (POLICY-043). Kontodaten, Betrag und Zahlungsreferenz nur aus der Zahlungsanweisung der Bestellung; auf Frist und Stornierung hinweisen. Fehlt dem Kunden die Zahlungsanweisung oder der Zugang, muss sie bereitgestellt werden; der Weg ist noch nicht beschrieben.

**Teil 2 – Lastschrift für künftige Bestellungen (getrennt, danach):**

- Mandatsvorlage wurde bereits gesendet und im vorliegenden Verlauf ist keine Rücksendung dokumentiert: erinnern, ohne einen ungeprüften Sachverhalt zu behaupten, und die Vorlage erneut anbieten (PROCESS-015).
- Mandatsvorlage wurde noch nicht gesendet und der Grund ist ein bei der Shopumstellung ungültig gewordenes Altmandat: Vorlage senden (POLICY-041, PROCESS-015).
- Grund der Umstellung offen oder Zahlungsprobleme: weder zusagen noch ablehnen; für diesen Fall gibt es noch keine abschließenden Freigabekriterien. Den Grund nicht unterstellen.

Die Umstellung gilt immer erst nach Hinterlegung des Mandats durch Kerstin in EOCS, Umstellung durch Etienne oder Christian und Synchronisierung mit dem Shop, und nur für neue Bestellungen (POLICY-042).

# Noch nicht entscheidbar wenn

Nur der Teil 2 (Lastschrift) ist offen, solange der Grund der bisherigen Umstellung nicht belegt ist. Teil 1 wird davon nicht aufgeschoben.

# Kommunikation

Teil 1 siehe POLICY-043. Teil 2, Erinnerung an das Mandat:

„Falls Sie uns das ausgefüllte und unterschriebene Lastschriftmandat noch nicht zurückgesandt haben, holen Sie dies bitte nach. Die Umstellung auf Lastschrift gilt erst, nachdem wir das Mandat hinterlegt und die Umstellung mit dem Shop synchronisiert haben, und nur für neue Bestellungen. Für die bereits aufgegebene Bestellung nutzen Sie bitte die oben beschriebene Zahlung.“

Teil 2, Lastschrift noch offen:

„Ihren Wunsch, künftig wieder per Lastschrift zu zahlen, prüfen wir intern, ob und unter welchen Voraussetzungen das möglich ist. Unabhängig davon können Sie Ihre aktuelle Bestellung wie oben beschrieben bezahlen.“

# No-Gos

Keine Zusage einer Rückkehr zur Lastschrift und keine Ablehnung, solange die Kriterien nicht festgelegt sind. Keinen Grund der Umstellung unterstellen. Nicht pauschal sagen, der Kunde müsse PayPal verwenden. Keine Bankdaten nennen, die nicht aus der Mollie-Zahlungsanweisung stammen.
