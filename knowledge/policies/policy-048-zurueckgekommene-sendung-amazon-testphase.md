---
id: POLICY-048
title: Zurückgekommene Sendung bei Amazon – Neuzustellung gegen Pauschale (Testphase)
type: policy
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
summary: "Ein Paket zu einer Amazon-Bestellung ist an LOOXIS zurückgegangen, oder der Kunde fragt nach erneuter Zustellung. Neuzustellung nur, wenn der Kunde bestätigt, das Paket aktiv anzunehmen, und die pauschalen Neuversandkosten von 5 Euro trägt; die Rechnung liegt dem Paket bei. Gilt nur testweise bis 31.01.2027."
retrieval: binding
triggers:
  - unzustellbar
  - nicht zustellbar
  - Zustellung fehlgeschlagen
  - nicht zugestellt
  - Zustellversuch
  - nicht angetroffen
  - Retour an Absender
  - zurück an den Absender
  - Paket zurückgegangen
  - Paket ist zurück
  - erneute Zustellung
  - erneut zustellen
  - neu zustellen
  - nie geklingelt
  - falsche Adresse
actions:
  - reshipment
related_knowledge:
  - POLICY-012
  - POLICY-015
  - POLICY-044
  - POLICY-045
  - PLAYBOOK-029
---

Gilt für Privatkunden im Vertriebskanal Amazon, bei allen Produkten. **Testweise gültig bis 31.01.2027.** Danach gilt diese Vorgehensweise nicht mehr, solange sie nicht ausdrücklich bestätigt oder verlängert wurde.

# Regel

Eine Zustellung ist nur möglich, wenn die Lieferadresse korrekt ist, auf Klingel und Briefkasten der Name wie in der Adresse angegeben lesbar ist und die Zustellung ermöglicht wird; alternativ erfolgt eine Abholung am hinterlegten Ort.

Der Kunde wird per E-Mail von LOOXIS über die anstehende Zustellung, die Sendungsnummer und gegebenenfalls geänderte Zustelloptionen informiert. Wurde ein Paket zur Abholung an einem Pickup Point bereitgestellt, wird er auch darüber informiert (POLICY-015).

# Neuzustellung

Eine erneute Zustellung verursacht erneut Kosten, die LOOXIS nicht übernimmt. LOOXIS nimmt gerne eine Neuzustellung vor, nachdem der Kunde

1. bestätigt hat, das Paket aktiv anzunehmen, und
2. eingewilligt hat, die pauschalen Neuversandkosten von 5 Euro zu tragen.

Dem erneut versendeten Paket wird eine Rechnung beigelegt. Der Kunde zahlt nach Erhalt gemäß den Angaben auf der Rechnung (POLICY-044).

# Interner Hintergrund

Geht das Paket trotzdem an LOOXIS zurück, verursacht das Rücksendekosten, die LOOXIS tragen muss. Diese Information ist intern und wird dem Kunden nicht genannt.

# Grenzen

- Keine Neuzustellung ohne Bestätigung der aktiven Annahme und Einwilligung in die 5 Euro.
- Keine Rücksendekosten nennen; die Zahlung nicht über Amazon abwickeln (POLICY-039).
- Gesetzliche Ansprüche bleiben unberührt; interne Regeln dürfen ihre Erfüllung nicht vereiteln.
