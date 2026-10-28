---
id: POLICY-057
title: Storno – Freigabe durch Zahlung und Stornierbarkeit
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
summary: "Ein Kunde möchte eine Bestellung stornieren, fragt, ob er für eine nicht bezahlte Bestellung zahlen muss, bekommt eine Zahlungserinnerung für eine Bestellung, die er nicht mehr will, oder hat doppelt bestellt. Klärt: Eine nicht bezahlte Mollie-Bestellung wird nie freigegeben, nicht produziert und automatisch storniert, ohne Kosten für den Kunden; eine bezahlte Bestellung oder eine Bestellung auf Rechnung oder Lastschrift gilt als freigegeben und kann in der Regel nicht mehr storniert oder geändert werden; ab 150 € Auftragswert kann die Produktionsleitung auf Wunsch prüfen, ob die Bearbeitung noch abgebrochen werden kann. Gilt nicht für Amazon und masterpics."
retrieval: binding
triggers:
  - Storno
  - stornieren
  - Stornierung
  - storniert werden
  - Bestellung stornieren
  - Auftrag stornieren
  - Bestellung abbrechen
  - Bestellung rückgängig
  - doppelt bestellt
  - zweimal bestellt
  - versehentlich bestellt
  - Zahlungserinnerung
  - Zahlungsaufforderung
  - Bestellung nicht bezahlt
  - nicht mehr haben
actions:
  - cancellation
related_knowledge:
  - POLICY-001
  - POLICY-042
  - POLICY-043
  - POLICY-044
  - PLAYBOOK-041
---

Gilt für alle Aufträge in den Vertriebskanälen looxis-de, fachhaendler und looxis-pro, auch für manuell angelegte Bestellungen. Gilt nicht für Amazon (POLICY-040) und nicht für masterpics.

# Regel

Ob eine Bestellung storniert werden kann, hängt davon ab, ob sie freigegeben ist. Freigegeben wird sie durch die Zahlung oder, bei Rechnung und Lastschrift, mit dem Abschluss der Bestellung.

## Nicht bezahlte Bestellung (Zahlungsabwicklung über Mollie)

Das gilt für looxis.de und für fachhaendler.looxis.de, wenn der Kunde auf „Online-Zahlung per Mollie“ eingestellt ist, und unabhängig von der Zahlungsoption, die der Kunde wählt.

- Wird die Bestellung nicht bezahlt, wird sie **nie für die Produktion freigegeben**: Sie wird nicht bearbeitet, nicht produziert und nicht verschickt.
- Sie wird zeitnah **automatisch storniert**, egal ob der Kunde um Stornierung bittet oder darum bittet, sie nicht zu stornieren. Der Kunde muss nichts tun.
- Für den Kunden entstehen dafür **keine Kosten**.
- Das System weiß nicht, dass der Kunde die Bestellung nicht mehr will. Die Zahlungserinnerung (POLICY-043) geht deshalb für den offenen, unbezahlten Auftrag normal raus. Das ist zum Beispiel der Fall, wenn die erste Zahlung fehlschlug und der Kunde danach eine zweite Bestellung erfolgreich bezahlt hat. Der Kunde kann die Erinnerung ignorieren.

## Bestellung auf Rechnung oder Lastschrift

Registrierte Fachhändler haben teils die Zahlungsart „auf Rechnung“ oder „Lastschrift“. Eine solche Bestellung gilt **sofort als freigegeben** und wird umgehend produziert. Sie kann **in der Regel nicht mehr storniert** werden.

## Bezahlte Mollie-Bestellung

Eine mit Mollie erfolgreich bezahlte Bestellung gilt als bestätigt und freigegeben. Sie kann **in der Regel nicht mehr storniert oder geändert** werden. Das gilt für Endverbraucher und Fachhändler.

# Ausnahme: Auftrag ab 150 €

Hat eine freigegebene Bestellung mehrere Produkte oder ein teures Produkt und beträgt der Auftragswert 150 € oder mehr (Gesamtbetrag der Bestellung laut EOCS), kann im Sinne des Kunden geprüft werden, ob die Bearbeitung noch abgebrochen werden kann und nur die bis dahin entstandenen Kosten berechnet werden. Das kann für den Kunden günstiger sein als die volle Zahlung für Ware, mit der er nichts anfangen kann.

- Die Prüfung erfolgt nur nach Rücksprache mit der Produktionsleitung (Thomas). Der Kundenservice sagt vorher nichts zu, und es gibt keinen Anspruch darauf. Die Prüfung kann negativ ausgehen.
- Die Möglichkeit wird dem Kunden nur genannt, wenn der Auftragswert 150 € oder mehr beträgt und der Kunde nach der Absage der Stornierung dabei bleibt oder sichtlich Ware bekäme, mit der er nichts anfangen kann. Die Antwort sagt zuerst die Regel („in der Regel nicht stornierbar“) und bietet dann die Prüfung an.
- Unter 150 € gibt es diese Ausnahme nicht.

# Kommunikation

Nicht bezahlte Bestellung: „Für die nicht bezahlte Bestellung entstehen Ihnen keine Kosten. Sie wird nicht produziert und nicht verschickt und automatisch storniert. Die Zahlungserinnerung dazu können Sie ignorieren. Sie müssen die Bestellung nicht selbst stornieren.“

Bezahlte oder auf Rechnung oder Lastschrift freigegebene Bestellung: „Ihre Bestellung ist bestätigt und für die Produktion freigegeben. Eine Stornierung oder Änderung ist deshalb in der Regel nicht mehr möglich.“

Antwortet ein Endverbraucher auf die Zahlungserinnerung nur mit der Bitte um Stornierung und ohne weiteren Hinweis, gilt für die Antwort PLAYBOOK-041: Es wird nach dem Grund gefragt.

Bei Auftrag ab 150 €, wenn der Kunde bei der Stornierung bleibt (zusätzlich zum vorigen Text): „Da Ihre Bestellung einen größeren Umfang hat, prüfen wir gerne intern, ob ein Abbruch der Bearbeitung noch möglich ist und nur die bis dahin entstandenen Kosten berechnet werden. Das ist keine Zusage; wir melden uns mit dem Ergebnis.“

# Grenzen

- Keine Stornierung einer freigegebenen Bestellung zusagen.
- Nicht behaupten, eine Bestellung sei bezahlt, unbezahlt oder freigegeben, ohne es im Verlauf oder in den Bestelldaten zu sehen; sonst als interne Klärung vorsehen.
- Den Zeitpunkt der automatischen Stornierung nicht beziffern („zeitnah“).
- Die Prüfung ab 150 € nicht anbieten, wenn der Auftragswert darunter liegt oder nicht bekannt ist.
- Bei einem Mangel, einer Abweichung von der Bestellung oder einer Lieferverzögerung gelten POLICY-002 und POLICY-003, nicht diese Policy. Der Rückgabe- und Widerrufswunsch nach Fertigstellung steht in POLICY-001.
