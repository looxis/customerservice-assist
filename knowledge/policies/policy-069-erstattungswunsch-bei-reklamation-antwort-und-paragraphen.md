---
id: POLICY-069
title: "Erstattungswunsch bei Reklamationen: Antwort, Nachweis und Paragraphen"
type: policy
status: active
customer_types:
  - b2c
sales_channels:
products:
categories:
  - complaint
topics:
  - refund
  - replacement
  - withdrawal
summary: "Privatkunde verlangt bei einer Reklamation eine Erstattung (Geld zurück, Rückerstattung, Anwalt, Klage, A-bis-Z-Garantieantrag). Regelt, wie kurz auf den Erstattungswunsch eingegangen wird (Fehler wird behoben, nicht erstattet; bei Amazon auch den Amazon-Regeln entsprechend), was nach einem später gelieferten Nachweis geschieht (Fehler anerkennen, Neuproduktion ausdrücklich anbieten, Lieferadresse bestätigen lassen) und ab wann Paragraphen genannt werden. Gilt nicht für Geschäftskunden und nicht für Kundenfehler."
retrieval: triggered
triggers:
  - Erstattung
  - erstatten
  - Rückerstattung
  - Geld zurück
  - Kaufpreis zurück
  - Rückzahlung
  - zurückerstattet
  - Anwalt
  - Rechtsanwalt
  - Klage
  - klagen
  - A bis Z
  - A-z-Garantie
  - A-bis-Z
  - Garantieantrag
  - Garantie-Antrag
exclude_triggers:
when:
actions:
  - refund
related_knowledge:
  - POLICY-001
  - POLICY-002
  - POLICY-045
  - PERMISSION-002
---

Gilt für Privatkunden auf allen Vertriebskanälen, wenn sie bei einer Reklamation eine Erstattung verlangen. Die Grundregel steht in POLICY-002: Bei berechtigter Reklamation wird der Fehler behoben, Erstattung ist ein seltener Sonderfall.

# Kurz auf den Erstattungswunsch eingehen

Verlangt der Kunde eine Erstattung, geht der Antwortentwurf kurz darauf ein: Eine Erstattung ist bei personalisierten Produkten nicht der Weg. Bei einer berechtigten Reklamation wird der Fehler behoben. Wie, wird noch nicht angekündigt, also keine Neuproduktion und kein Ersatz zugesagt, solange das Problem nicht bestätigt ist. Ein Erstattungsweg wird nicht erklärt (auch nicht über Amazon).

Bei Amazon-Bestellungen nennt der Entwurf schon in dieser ersten Erklärung, dass das auch den Amazon-Regeln entspricht.

# Nachweis nach einem Erstattungswunsch

Hat der Kunde zuerst eine Erstattung verlangt und bestätigt erst sein Nachweis (zum Beispiel das Foto) den Fehler, wird nicht stillschweigend neu produziert. Die Antwort:

- erkennt den Produktionsfehler an,
- bietet die Neuproduktion ausdrücklich an („wir liefern gerne ein neues Produkt“),
- bittet um Bestätigung der Lieferadresse. So kann der Kunde die Neuproduktion noch einmal bewusst annehmen oder ablehnen; manchem fällt dabei auf, dass der Mangel gar nicht mehr so schwer wiegt.

Hat der Kunde selbst Ersatz oder Neuproduktion verlangt, muss die Neuproduktion nicht zusätzlich angeboten werden; die Bitte um Bestätigung der Lieferadresse bleibt sinnvoll. Die Befugnis richtet sich nach PERMISSION-002.

# Kunde besteht auf Erstattung

Besteht der Kunde trotz Erklärung auf einer Erstattung, erläutert die Antwort kurz, warum wir nachbessern statt zu erstatten: Bei einem Mangel sind wir berechtigt, nachzubessern. Bei personalisierten Produkten gibt es kein Widerrufsrecht (POLICY-001), also kann der Kunde die Erstattung auch nicht über einen Widerruf statt der Reklamation erreichen. Sachlich und kurz, ohne Rechtsbelehrung. Gesetzliche Mängelrechte bleiben unberührt (POLICY-002, „Ausnahmen und gesetzliche Grenzen“).

# Wann Paragraphen genannt werden

Gezählt werden alle Nachrichten im Ticketverlauf (Kunde und LOOXIS, ohne interne Notizen):

1. Bis zur zweiten Kundennachricht (bis zum vierten Dialogteil): keine Paragraphen, nur die Begründung in einfachen Worten.
2. Ab der dritten Kundennachricht (fünfter Dialogteil), wenn der Kunde weiter auf der Erstattung besteht, und sofort in jeder Nachricht, in der Schlüsselwörter wie „Anwalt“, „Klage“, „A bis Z“, „A-z“, „Garantieantrag“ oder Ähnliches fallen: Die Antwort nennt § 439 BGB (Nacherfüllung) und § 312g Abs. 2 BGB (kein Widerrufsrecht bei personalisierten Produkten).
3. Ab der vierten Kundennachricht (siebter Dialogteil): Die Antwort darf zusätzlich Gesetzestexte zitieren und, wenn es thematisch passt, die Paragraphen zu Rücksendekosten und Zustellung nennen (POLICY-045). Der Vorschlag führt sie als Möglichkeit auf, der Mitarbeiter entscheidet.

Droht der Kunde mit Anwalt oder Klage, entscheidet der Mitarbeiter, ob eskaliert wird.

# Rechtsgrundlagen

- https://www.gesetze-im-internet.de/bgb/__312g.html
- https://www.gesetze-im-internet.de/bgb/__439.html
