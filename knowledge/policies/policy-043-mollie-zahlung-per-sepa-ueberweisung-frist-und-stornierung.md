---
id: POLICY-043
title: Offene Bestellung mit Mollie-Zahlung – SEPA-Überweisung, Zahlungsfrist und Stornierung
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
  - order-release
summary: "Ein Kunde kann eine offene Bestellung mit Zahlungsabwicklung über Mollie nicht bezahlen, zum Beispiel weil PayPal nicht funktioniert oder ihm Kontodaten fehlen. Klärt: Die klassische SEPA-Überweisung über Mollie ist eine zulässige Option und kein Wechsel der Zahlungsabwicklung; Kontodaten, Betrag und Zahlungsreferenz stammen nur aus der Mollie-Zahlungsanweisung der Bestellung; die Zahlung ist nur innerhalb einer Frist möglich, sonst wird die Bestellung storniert und kann nicht manuell freigegeben werden; die Frage nach einer Rückkehr zur Lastschrift wird getrennt beantwortet."
retrieval: binding
triggers:
  - Paypal
  - Paypal funktioniert nicht
  - Paypal geht nicht
  - Paypal klappt nicht
  - Zahlung funktioniert nicht
  - Zahlung nicht möglich
  - kann die Bestellung nicht bezahlen
  - kann nicht bezahlen
  - keine Kontodaten
  - Kontodaten
  - Bankverbindung
  - Überweisung
  - SEPA-Überweisung
  - Sofortüberweisung
  - Mollie
  - Zahlungsanweisung
  - Zahlungsreferenz
  - Verwendungszweck
  - Zahlungsfrist
  - Zahlung fehlgeschlagen
  - Zahlung abgebrochen
  - Bestellung freigeben
related_knowledge:
  - POLICY-041
  - POLICY-042
  - PLAYBOOK-028
---

Gilt für Bestellungen im Online-Shop (looxis-de, fachhaendler, looxis-pro), deren Zahlung über Mollie abgewickelt wird.

# Regel

Die Zahlungsabwicklung über Mollie bietet mehrere Zahlungsoptionen an. PayPal ist nur eine davon. Kann ein Kunde mit PayPal nicht zahlen, kann er die bestehende Bestellung auch mit der klassischen SEPA-Überweisung über Mollie bezahlen.

Die SEPA-Überweisung über Mollie ist kein Wechsel der am Kundenkonto hinterlegten Zahlungsabwicklung. Sie widerspricht POLICY-042 nicht.

Kontodaten, genauer Betrag und Zahlungsreferenz müssen aus der Mollie-Zahlungsanweisung für genau diese Bestellung stammen und vom Kunden exakt eingehalten werden.

# Zahlungsanweisung fehlt

Fehlt dem Kunden die Zahlungsanweisung oder der Zugang dazu, muss sie ihm bereitgestellt werden. Der konkrete Weg dafür ist noch nicht beschrieben. Bis dahin keinen Weg erfinden und nicht behaupten, die Anweisung sei bereits gesendet worden, wenn das nicht belegt ist.

# Zahlungsfrist und Stornierung

Eine Mollie-Zahlung kann nur innerhalb einer bestimmten Frist abgeschlossen werden. Erfolgt die Zahlung nicht in dieser Zeit, wird die Bestellung storniert. Auch Mitarbeiter von LOOXIS können eine Bestellung, die wegen ausgebliebener Zahlung nicht freigegeben wurde, nicht manuell freigeben. Es kann jedoch eine neue Bestellung aufgegeben werden.

# Getrennte Anliegen

Die Zahlung der bestehenden Bestellung und ein Wunsch nach Rückkehr zur Lastschrift sind getrennte Anliegen und werden getrennt beantwortet. Die bestehende Bestellung kann unabhängig von einer möglichen späteren Lastschrift bezahlt werden. Eine offene Entscheidung zur Lastschrift (siehe POLICY-041) darf eine hilfreiche Antwort zur aktuellen Zahlung nicht blockieren. Die Antwort erklärt zuerst die sofort nutzbare Zahlung und behandelt danach die Lastschrift für künftige Bestellungen.

# Kommunikation

„Sie können Ihre Bestellung auch per klassischer SEPA-Überweisung bezahlen; PayPal ist nur eine der angebotenen Zahlungsoptionen. Kontodaten, Betrag und Zahlungsreferenz entnehmen Sie bitte genau der Zahlungsanweisung zu Ihrer Bestellung und übernehmen sie unverändert. Bitte beachten Sie, dass die Zahlung innerhalb der vorgegebenen Frist erfolgen muss; andernfalls wird die Bestellung storniert und kann nicht manuell freigegeben werden. In diesem Fall können Sie jedoch eine neue Bestellung aufgeben.“

# Grenzen

- Keine Bankdaten, Beträge oder Zahlungsreferenzen nennen, die nicht aus der Mollie-Zahlungsanweisung der Bestellung stammen. Nicht auf das Geschäftskonto von LOOXIS verweisen.
- Nicht pauschal sagen, der Kunde müsse PayPal verwenden.
- Keine manuelle Freigabe einer wegen ausgebliebener Zahlung nicht freigegebenen Bestellung zusagen.
- Keine Länge der Zahlungsfrist nennen, solange sie nicht belegt ist.

# Noch zu klären

- Der Weg, auf dem einem Kunden die Mollie-Zahlungsanweisung oder der Zugang dazu bereitgestellt wird.
- Die Länge der Zahlungsfrist.
