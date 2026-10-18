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

- Der Kunde nennt Probleme mit PayPal oder fehlende Kontodaten, oder eine Bestellung wurde nicht bezahlt.
- Der Kunde wünscht Lastschrift oder Bankeinzug.
- Früher wurde ihm bereits eine Mandatsvorlage zugesandt (verknüpftes oder früheres Ticket).

# Zu prüfen

1. Zwei Anliegen trennen: die Zahlung der bestehenden Bestellung und die Rückkehr zur Lastschrift.
2. Vor der Antwort zur Lastschrift den früheren Verlauf lesen (zusammengehörige Tickets, unsere früheren Mails an den Kunden): Wurde ihm die Umstellung auf Lastschrift bereits zugesagt? Wurde die Mandatsvorlage schon gesendet, wann? Ist eine Rücksendung dokumentiert? Fehlt der frühere Verlauf, nichts annehmen und im Vorschlag darauf hinweisen.
3. Ist der Grund der bisherigen Umstellung belegt (ungültiges Altmandat nach Shopumstellung) oder offen?

# Empfohlene Maßnahme

**Teil 1 – nicht bezahlte Bestellung (immer zuerst und ohne Aufschub beantworten):** Bei Fachhändlern wird keine Zahlungserinnerung verschickt. Die Bestellung gilt als nicht freigegeben, die Frist ist verstrichen oder verstreicht kurzfristig. Sie kann weder manuell freigegeben noch in der Zahlungsart geändert werden. Dem Kunden empfehlen, die Bestellung neu auszulösen; weiterhin steht Mollie mit allen Optionen zur Verfügung, auch der klassischen SEPA-Überweisung (POLICY-043). Kontodaten, Betrag und Zahlungsreferenz nur aus der Zahlungsanweisung der neuen Bestellung.

**Teil 2 – Lastschrift für künftige Bestellungen (getrennt, danach):**

- Dem Kunden wurde die Umstellung bereits zugesagt und die Mandatsvorlage gesendet, und im vorliegenden Verlauf ist keine Rücksendung dokumentiert: Die Zusage besteht, es ist keine erneute interne Prüfung nötig. Es fehlt nur das ausgefüllte und unterschriebene Mandat. An die Rücksendung erinnern (PROCESS-015) und erklären, wann Lastschrift wieder zur Verfügung steht: erst nachdem das Mandat zurückgesandt, durch Kerstin hinterlegt, durch Etienne oder Christian umgestellt und mit dem Shop synchronisiert ist, und nur für danach eingehende Bestellungen.
- Mandatsvorlage wurde noch nicht gesendet und der Grund ist ein bei der Shopumstellung ungültig gewordenes Altmandat: Vorlage senden (POLICY-041, PROCESS-015).
- Grund der Umstellung offen oder Zahlungsprobleme und keine Zusage im Verlauf: weder zusagen noch ablehnen; für diesen Fall gibt es noch keine abschließenden Freigabekriterien. Den Grund nicht unterstellen.

# Noch nicht entscheidbar wenn

Nur der Teil 2 (Lastschrift) ist offen, solange der Grund der bisherigen Umstellung nicht belegt ist. Teil 1 wird davon nicht aufgeschoben.

# Kommunikation

Teil 1 siehe POLICY-043. Teil 2, Zusage und Vorlage bereits erfolgt:

„Für Ihren Wunsch, wieder per Lastschrift zu zahlen, haben wir Ihnen bereits das Lastschriftmandat zugesendet. Falls Sie uns das ausgefüllte und unterschriebene Mandat noch nicht zurückgesandt haben, holen Sie dies bitte nach (Scan oder gut lesbares Foto). Sobald wir es hinterlegt und Ihr Konto umgestellt und mit dem Shop synchronisiert haben, steht Ihnen für neue Bestellungen wieder Lastschrift zur Verfügung. Für die jetzige Bestellung gilt das noch nicht.“

Teil 2, Lastschrift noch offen:

„Ihren Wunsch, künftig wieder per Lastschrift zu zahlen, prüfen wir intern, ob und unter welchen Voraussetzungen das möglich ist. Unabhängig davon können Sie Ihre Bestellung wie oben beschrieben neu auslösen und bezahlen.“

# No-Gos

Keine Zusage einer Rückkehr zur Lastschrift und keine Ablehnung, solange die Kriterien nicht festgelegt sind. Keinen Grund der Umstellung unterstellen. Nicht pauschal sagen, der Kunde müsse PayPal verwenden. Keine Bankdaten nennen, die nicht aus der Mollie-Zahlungsanweisung stammen.
