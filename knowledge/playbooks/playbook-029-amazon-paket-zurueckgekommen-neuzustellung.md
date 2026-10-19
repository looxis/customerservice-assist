---
id: PLAYBOOK-029
title: "Amazon-Kunde: Paket kam zurück, Neuzustellung gewünscht (Testphase)"
type: playbook
status: active
trial_until: 2027-01-31
trial_goal: "Wir testen, ob Amazon-Kunden die Neuzustellung gegen eine Pauschale von 5 Euro annehmen und ob dadurch Rücksendungen und Nachfragen abnehmen."
customer_types:
  - b2c
sales_channels:
  - amazon
products:
categories:
  - complaint
  - order-process-question
topics:
  - delivery-failure
  - delivery-options
summary: "Ein Amazon-Kunde meldet sich, weil sein Paket nicht zugestellt wurde und an LOOXIS zurückgegangen ist, und möchte eine erneute Zustellung. Anweisung an den Agenten: Voraussetzungen der Zustellung erklären, Neuzustellung gegen aktive Annahme und 5 Euro Pauschale anbieten. Gilt nur testweise bis 31.01.2027."
retrieval: triggered
triggers:
  - unzustellbar
  - nicht zustellbar
  - Zustellung fehlgeschlagen
  - nicht zugestellt
  - Retour an Absender
  - zurück an den Absender
  - Paket zurückgegangen
  - Paket ist zurück
  - erneute Zustellung
  - erneut zustellen
  - neu zustellen
actions:
  - reshipment
related_knowledge:
  - POLICY-045
  - POLICY-048
  - POLICY-015
  - POLICY-012
  - POLICY-044
---

Gilt für Privatkunden im Vertriebskanal Amazon, produktübergreifend. **Testweise gültig bis 31.01.2027.** Danach diese Anweisung nicht mehr anwenden, solange sie nicht bestätigt oder verlängert wurde.

# Fallmuster

Das Paket zu einer Amazon-Bestellung ist vom Paketdienst an LOOXIS zurückgegangen. Der Kunde meldet sich (bei Amazon gibt es keine proaktive Kontaktaufnahme, POLICY-012) und möchte, dass das Paket erneut zugestellt wird.

# Erkennungsmerkmale

- Der Kunde schreibt, das Paket sei nicht angekommen oder nie zugestellt worden.
- Die Sendungsverfolgung oder der Rücksendeeingang zeigt, dass das Paket an LOOXIS zurückging.

# Zu prüfen

1. Ist das Paket nachweislich an LOOXIS zurückgegangen und noch vorhanden (Aufbewahrung nach POLICY-012)?
2. Welcher Grund steht in der Sendungsverfolgung oder im Rücksendegrund? Der Grund wird nur genannt, wenn er belegt ist.
3. Hat der Kunde bereits bestätigt, das Paket aktiv anzunehmen, und in die 5 Euro Pauschale eingewilligt?

# Noch nicht entscheidbar wenn

Die Bestätigung der aktiven Annahme oder die Einwilligung in die 5 Euro fehlt: Beides gezielt beim Kunden erfragen, solange noch nichts davon vorliegt.

# Empfohlene Maßnahme

1. Dem Kunden erklären, wann eine Zustellung möglich ist: korrekte Adresse, Name auf Klingel und Briefkasten wie in der Adresse, Zustellung ermöglichen oder Abholung am hinterlegten Ort. Auf die Benachrichtigungen per E-Mail verweisen (POLICY-045, POLICY-048).
2. Eine Neuzustellung anbieten, wenn der Kunde bestätigt, das Paket aktiv anzunehmen, und einwilligt, die pauschalen Neuversandkosten von 5 Euro zu tragen.
3. Liegen Bestätigung und Einwilligung vor: Neuversand veranlassen, dem Paket die Rechnung beilegen.
4. Eine Übernahme der Kosten für die erneute Zustellung ablehnen; die Rücksendekosten von LOOXIS werden dem Kunden nicht genannt.

# Kommunikation

Erklärung und Angebot: „Eine Zustellung ist nur möglich, wenn die Lieferadresse korrekt ist, auf Klingel und Briefkasten der Name wie in der Adresse angegeben lesbar ist und die Sendung angenommen werden kann; alternativ kann sie an dem hinterlegten Ort abgeholt werden. Über die anstehende Zustellung, die Sendungsnummer und mögliche Änderungen der Zustelloptionen informieren wir Sie per E-Mail; wurde ein Paket zur Abholung bereitgestellt, informieren wir Sie auch darüber. Leider ist Ihr Paket an uns zurückgegangen. Eine erneute Zustellung verursacht erneut Kosten, die wir nicht übernehmen können. Gerne versenden wir das Paket erneut, wenn Sie uns bestätigen, dass Sie es aktiv annehmen, und damit einverstanden sind, die pauschalen Neuversandkosten von 5 Euro zu tragen. Eine Rechnung legen wir dem Paket bei.“

Nach Bestätigung: „Vielen Dank für Ihre Bestätigung. Wir versenden Ihr Paket erneut und legen die Rechnung über die Neuversandkosten von 5 Euro bei.“

# No-Gos

Keine Neuzustellung ohne Bestätigung und Einwilligung. Keine Rücksendekosten nennen. Keine Zahlung über Amazon abwickeln. Dem Kunden nichts unterstellen (zum Beispiel, er sei nicht zuhause gewesen). Keine Kostenübernahme für die erneute Zustellung zusagen.
