---
id: POLICY-044
title: Zahlungsanweisung je Zahlungsart – Rechnung und Lastschrift
type: policy
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
  - invoice-delivery
summary: "Ein Kunde fragt, wie, wohin oder wann er zahlen soll oder sagt, ihm fehlten Kontodaten oder Zahlungsangaben. Die Zahlungsanweisung ist je Zahlungsart klar festgelegt: Bei Zahlung auf Rechnung stehen Zahlungsziel und die beiden Geschäftskonten auf der Rechnung; bei Lastschrift steht das voraussichtliche Einzugsdatum auf der Rechnung und alles Weitere im vom Kunden unterschriebenen Lastschriftmandat, ohne gültiges Mandat gibt es keine Lastschrift."
retrieval: triggered
triggers:
  - Zahlungsanweisung
  - Zahlungsinformationen
  - Zahlungsziel
  - Kontodaten
  - Bankverbindung
  - IBAN
  - Verwendungszweck
  - wohin überweisen
  - wohin soll ich überweisen
  - Einzugsdatum
  - wann wird abgebucht
  - wann wird eingezogen
  - Rechnung bezahlen
related_knowledge:
  - POLICY-041
  - POLICY-043
---

Gilt für die Vertriebskanäle looxis-de, fachhaendler und looxis-pro.

# Regel

Wie ein Kunde zahlt, ist je Zahlungsart eindeutig festgelegt:

- **Rechnung:** Alle Angaben stehen auf der Rechnung, auch das Zahlungsziel und die beiden Geschäftskonten von LOOXIS bei der Commerzbank und der HypoVereinsbank. Den Kunden auf die Rechnung verweisen.
- **Lastschrift:** Das voraussichtliche Einzugsdatum steht auf der Rechnung. Alle weiteren Einzelheiten ergeben sich aus dem vom Kunden unterschriebenen Lastschriftmandat. Ohne erteiltes, ausgefülltes, unterschriebenes und damit gültiges Lastschriftmandat gibt es keine Zahlung per Lastschrift.
- **Mollie:** Mollie hält alle Zahlungsdetails und Informationen unmissverständlich und detailliert bereit. Kontodaten, Betrag und Zahlungsreferenz stammen aus der Mollie-Zahlungsanweisung der Bestellung, nicht vom Kundenservice; siehe POLICY-043.

# Kommunikation

Rechnung: „Alle Angaben zur Zahlung, einschließlich Zahlungsziel und unserer Bankverbindung, finden Sie auf Ihrer Rechnung.“

Lastschrift: „Das voraussichtliche Einzugsdatum finden Sie auf Ihrer Rechnung; weitere Einzelheiten ergeben sich aus dem von Ihnen unterschriebenen Lastschriftmandat. Ohne gültiges Mandat ist keine Zahlung per Lastschrift möglich.“

# Grenzen

- Kontodaten nicht aus dem Gedächtnis oder frei formuliert nennen, sondern auf die Rechnung verweisen.
- Die Geschäftskonten gelten für die Zahlung auf Rechnung. Bei einer Mollie-Bestellung nicht auf sie verweisen (siehe POLICY-043).
- Keine Lastschrift zusagen oder einziehen lassen, solange kein gültiges Mandat hinterlegt ist.
