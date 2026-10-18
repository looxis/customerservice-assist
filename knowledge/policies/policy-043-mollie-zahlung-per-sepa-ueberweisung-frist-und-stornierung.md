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
summary: "Ein Kunde kann eine Bestellung mit Zahlungsabwicklung über Mollie nicht bezahlen, zum Beispiel weil PayPal nicht funktioniert oder ihm Kontodaten fehlen. Klärt: Die klassische SEPA-Überweisung über Mollie ist eine zulässige Option und kein Wechsel der Zahlungsabwicklung; Kontodaten, Betrag und Zahlungsreferenz stammen nur aus der Mollie-Zahlungsanweisung; eine nicht rechtzeitig bezahlte Bestellung gilt als nicht freigegeben (bei Fachhändlern ohne Zahlungserinnerung), kann weder manuell freigegeben noch in der Zahlungsart geändert werden und muss neu ausgelöst werden; die Frage nach einer Rückkehr zur Lastschrift wird getrennt beantwortet."
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
  - POLICY-044
  - PLAYBOOK-028
---

Gilt für Bestellungen im Online-Shop (looxis-de, fachhaendler, looxis-pro), deren Zahlung über Mollie abgewickelt wird.

# Regel

Die Zahlungsabwicklung über Mollie bietet mehrere Zahlungsoptionen an. PayPal ist nur eine davon. Kann ein Kunde mit PayPal nicht zahlen, kann er die Bestellung (bei verstrichener Frist die neu ausgelöste Bestellung, siehe unten) auch mit der klassischen SEPA-Überweisung über Mollie bezahlen.

Die SEPA-Überweisung über Mollie ist kein Wechsel der am Kundenkonto hinterlegten Zahlungsabwicklung. Sie widerspricht POLICY-042 nicht.

Kontodaten, genauer Betrag und Zahlungsreferenz müssen aus der Mollie-Zahlungsanweisung für genau diese Bestellung stammen und vom Kunden exakt eingehalten werden.

# Zahlungsfrist, Stornierung und Neubestellung

Eine Mollie-Zahlung kann nur innerhalb einer bestimmten Frist abgeschlossen werden. Erfolgt die Zahlung nicht in dieser Zeit, gilt die Bestellung als nicht freigegeben und wird storniert.

Bei Fachhändlern wird keine Zahlungserinnerung verschickt. Meldet sich ein Fachhändler wegen einer nicht bezahlten Bestellung, ist die Frist deshalb in der Regel bereits verstrichen oder verstreicht kurzfristig. Die Bestellung ist dann als nicht freigegeben zu behandeln. LOOXIS kann sie weder manuell freigeben noch die Zahlungsart für diese Bestellung nachträglich ändern (siehe POLICY-042).

Dem Kunden bleibt, die Bestellung neu auszulösen. Dafür steht weiterhin die bisherige Zahlungsart Mollie mit ihren verschiedenen Optionen zur Verfügung, darunter die klassische SEPA-Überweisung. Lastschrift steht erst für Bestellungen zur Verfügung, die nach der Umstellung eingehen, also nachdem ein neues Mandat vorliegt, hinterlegt, umgestellt und mit dem Shop synchronisiert ist (POLICY-041, POLICY-042).

Bei Rechnung und Lastschrift ist die Zahlungsanweisung eindeutig festgelegt (POLICY-044). Fehlt dem Kunden bei einer Mollie-Bestellung die Zahlungsanweisung oder der Zugang dazu, muss sie ihm bereitgestellt werden. Der konkrete Weg dafür ist noch nicht beschrieben; keinen Weg erfinden und nicht behaupten, die Anweisung sei gesendet worden, wenn das nicht belegt ist.

# Getrennte Anliegen

Die Zahlung der bestehenden Bestellung und ein Wunsch nach Rückkehr zur Lastschrift sind getrennte Anliegen und werden getrennt beantwortet. Die Zahlung (bei Fristablauf: die Neubestellung) kann unabhängig von einer möglichen späteren Lastschrift erfolgen. Eine offene Entscheidung zur Lastschrift (siehe POLICY-041) darf eine hilfreiche Antwort zur aktuellen Zahlung nicht blockieren. Die Antwort erklärt zuerst die sofort nutzbare Zahlung und behandelt danach die Lastschrift für künftige Bestellungen.

# Kommunikation

Bei einer nicht bezahlten Bestellung eines Fachhändlers:

„Ihre Bestellung wurde leider nicht bezahlt und gilt deshalb als nicht freigegeben. Die Zahlungsfrist ist bereits verstrichen oder läuft in Kürze ab. Wir können die Bestellung nicht manuell freigeben und auch die Zahlungsart für diese Bestellung nicht nachträglich ändern. Bitte lösen Sie die Bestellung neu aus. Dafür steht Ihnen weiterhin die bisherige Zahlungsart Mollie mit ihren verschiedenen Optionen zur Verfügung, darunter auch die klassische SEPA-Überweisung; PayPal ist nur eine davon. Bitte übernehmen Sie Kontodaten, Betrag und Zahlungsreferenz genau aus der Zahlungsanweisung der neuen Bestellung und schließen Sie die Zahlung innerhalb der vorgegebenen Frist ab.“

Läuft die Zahlungsfrist einer Bestellung nachweislich noch:

„Sie können Ihre Bestellung auch per klassischer SEPA-Überweisung bezahlen; PayPal ist nur eine der angebotenen Zahlungsoptionen. Kontodaten, Betrag und Zahlungsreferenz entnehmen Sie bitte genau der Zahlungsanweisung zu Ihrer Bestellung und übernehmen sie unverändert. Bitte beachten Sie, dass die Zahlung innerhalb der vorgegebenen Frist erfolgen muss; andernfalls wird die Bestellung storniert.“

# Grenzen

- Keine Bankdaten, Beträge oder Zahlungsreferenzen nennen, die nicht aus der Mollie-Zahlungsanweisung der Bestellung stammen. Bei einer Mollie-Bestellung nicht auf das Geschäftskonto von LOOXIS verweisen (die Geschäftskonten gelten für die Zahlung auf Rechnung, POLICY-044).
- Nicht pauschal sagen, der Kunde müsse PayPal verwenden.
- Keine manuelle Freigabe einer wegen ausgebliebener Zahlung nicht freigegebenen Bestellung zusagen und keine nachträgliche Änderung der Zahlungsart für diese Bestellung.
- Nicht behaupten, eine Zahlungserinnerung sei verschickt worden oder werde verschickt.
- Keine Länge der Zahlungsfrist nennen, solange sie nicht belegt ist.

# Noch zu klären

- Der Weg, auf dem einem Kunden bei einer Mollie-Bestellung die Zahlungsanweisung oder der Zugang dazu bereitgestellt wird (bei Rechnung und Lastschrift ist er festgelegt, POLICY-044).
- Die Länge der Zahlungsfrist.
- Ob die fehlende Zahlungserinnerung auch für andere Kundengruppen als Fachhändler gilt.
