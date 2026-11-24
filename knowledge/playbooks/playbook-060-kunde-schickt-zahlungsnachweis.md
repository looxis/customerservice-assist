---
id: PLAYBOOK-060
title: 'Kunde schickt Zahlungsnachweis oder Überweisungsbeleg'
type: playbook
status: active
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
summary: "Ein Kunde schickt einen Zahlungsnachweis, Überweisungsbeleg, eine Überweisungsbestätigung seiner Bank oder einen Kontoauszug, oft nur als PDF-Anhang mit kurzem Text („here the payment“, „virement“), weil er glaubt, damit die Bestellung zu beschleunigen. Bei einer Mollie-Bestellung ist der Nachweis nicht nötig: Die Zahlung geht an Mollie, LOOXIS hat keinen Zugriff auf dieses Konto, Mollie erkennt den Eingang automatisch und gibt die Bestellung automatisch frei. LOOXIS fügt den Beleg nicht der Bestellung hinzu. Die Antwort nennt den in EOCS geprüften Zahlungsstand. Bei Zahlung auf Rechnung wird die Zahlung dagegen von LOOXIS zugeordnet."
retrieval: triggered
triggers:
- Zahlungsnachweis
- Zahlungsbestätigung
- Zahlungsbeleg
- Überweisungsbeleg
- Überweisungsbestätigung
- Überweisungsnachweis
- Überweisung anbei
- Beleg anbei
- Kontoauszug
- proof of payment
- payment confirmation
- payment receipt
- here the payment
- virement
- preuve de paiement
- bonifico
- betalingsbewijs
exclude_triggers:
when:
actions:
related_knowledge:
- POLICY-043
- POLICY-044
- POLICY-057
- PROCEDURE-011
---

Gilt für Bestellungen in den Vertriebskanälen looxis-de, fachhaendler und looxis-pro. Amazon-Bestellungen bezahlt der Kunde bei Amazon (POLICY-039).

# Fallmuster

Ein Kunde schickt einen Beleg über eine Zahlung, die er für seine Bestellung geleistet hat, oft ohne weitere Frage.

# Erkennungsmerkmale

- Anhang wie „Virement_….pdf“, „Überweisung.pdf“, ein Screenshot aus dem Online-Banking oder ein Kontoauszug.
- Kurzer Text wie „here the payment“, „anbei die Überweisung“, „Zahlung ist erfolgt“, meist mit Bestellnummer.
- Empfänger im Beleg ist Mollie (z. B. „Stichting Mollie Payments“): Mollie-Bestellung. Empfänger ist ein Geschäftskonto von LOOXIS: Zahlung auf Rechnung. Ob die Zahlung an Mollie ging, ist oft nur im Anhang erkennbar.

# Typische Ursache

Der Kunde glaubt, wir müssten die Zahlung von Hand der Bestellung zuordnen oder der Nachweis beschleunige die Freigabe.

# Zu prüfen

1. Zahlungsart der Bestellung: Mollie oder Zahlung auf Rechnung (Empfänger im Beleg, EOCS).
2. Zahlungsstand in EOCS auf der Rechnungsseite der Bestellung (PROCEDURE-011): registriert und Bestellung freigegeben, noch nicht registriert oder Frist abgelaufen bzw. storniert.

# Berechtigt wenn

Keine Entscheidung nötig; der Beleg wird freundlich angenommen und der geprüfte Stand mitgeteilt.

# Nicht berechtigt wenn

- Eine manuelle Freigabe oder Zuordnung einer Mollie-Zahlung: Das macht Mollie automatisch, LOOXIS hat darauf keinen Zugriff.

# Noch nicht entscheidbar wenn

- Der Zahlungsstand ist noch nicht in EOCS geprüft: Den Stand nicht nennen, sondern als interne Prüfung vorsehen.

# Fehlende Informationen

- Die Bestellnummer, wenn sie weder im Text noch im Beleg steht.

# Empfohlene Maßnahme

**Mollie-Bestellung:**

1. Für die Nachricht danken und erklären, dass ein Zahlungsnachweis nicht erforderlich ist: Die Zahlung läuft über unseren Zahlungsdienstleister Mollie. Mollie erkennt den Zahlungseingang automatisch, sofern IBAN, Betrag und Zahlungsreferenz genau der Zahlungsanweisung entsprechen, und gibt die Bestellung dann automatisch frei. Wir haben keinen Einblick in den Banktransfer selbst.
2. Den geprüften Stand nennen:
   - Registriert: Die Zahlung ist registriert, die Bestellung freigegeben. Ticket danach schließen.
   - Noch nicht registriert: Den Kunden bitten, IBAN, Betrag und Zahlungsreferenz mit der Mollie-Zahlungsanweisung abzugleichen; maßgeblich ist die Frist dort (POLICY-043, keine Tage nennen).
   - Frist abgelaufen oder storniert: Mollie zahlt nach Fristablauf eingegangene Beträge an den Zahlenden zurück, in der Regel innerhalb von 1 bis 3 Werktagen; die Bestellung bitte neu auslösen (POLICY-043).

**Zahlung auf Rechnung:**

1. Für den Nachweis danken und mitteilen, dass wir die Zahlung zuordnen (POLICY-044).
2. Ist sie bereits verbucht, das sagen; sonst, dass die Zuordnung einige Werktage dauern kann. Keine schnellere Bearbeitung versprechen.

# Befugnis

Keine Freigabe nötig.

# Kommunikation

Kurz und freundlich, ohne Vorwurf: Der Kunde wollte helfen. Die automatische Erkennung durch Mollie als Vorteil erklären („Sie müssen sich um nichts weiter kümmern“).

# No-Gos

- Bei einer Mollie-Bestellung sagen, der Beleg werde der Bestellung hinzugefügt, weitergeleitet, verbucht oder beschleunige die Freigabe.
- Sagen, LOOXIS habe die Zahlung erhalten oder sehe sie auf dem eigenen Konto, wenn sie an Mollie ging.
- Einen Zahlungsstand nennen, der nicht in EOCS oder im Mollie-Dashboard geprüft ist.
- Bei einer Mollie-Bestellung auf die Geschäftskonten von LOOXIS verweisen.

# Beispiel

Anlass (2026-11-24): Ein Kunde schickt nur „here the payment … order #…“ mit dem Anhang „Virement_….pdf“; Empfänger im Beleg ist Mollie. Falsch: „Vielen Dank für die Zahlungsbestätigung, wir werden das der Bestellung hinzufügen.“ Richtig: „Vielen Dank für Ihre Nachricht. Ein Zahlungsnachweis ist nicht erforderlich: Ihre Zahlung läuft über unseren Zahlungsdienstleister Mollie, der den Zahlungseingang automatisch erkennt und die Bestellung anschließend automatisch freigibt. Ihre Zahlung ist bereits registriert und Ihre Bestellung freigegeben.“
