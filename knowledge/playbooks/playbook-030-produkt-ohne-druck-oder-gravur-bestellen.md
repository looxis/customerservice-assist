---
id: PLAYBOOK-030
title: Kunde möchte ein Produkt ohne Druck oder Gravur bestellen
type: playbook
status: active
customer_types:
sales_channels:
products:
categories:
  - quote-request
  - order-process-question
  - product-question
topics:
  - personalized-products
  - prepayment
summary: "Ein Kunde fragt, ob er ein sonst personalisierbares Produkt ohne Druck, Gravur oder Aufdruck bekommen kann, einzeln oder in Menge. Anweisung an den Agenten: zum gleichen Preis möglich, benötigte Angaben erfragen, Auftrag manuell anlegen, Zahlung vorab über Mollie-Link; über Amazon-Nachrichten wird es nicht angeboten."
retrieval: triggered
triggers:
  - ohne Gravur
  - ohne Druck
  - ohne Personalisierung
  - ohne Aufdruck
  - ohne Bedruckung
  - unbedruckt
  - unpersonalisiert
  - blanko
  - Rohling
exclude_triggers:
  - unbedruckter Bereich
  - unbedruckte Bereich
  - unbedruckten Bereich
  - unbedruckte Stelle
  - unbedruckten Stelle
  - unbedruckte Stellen
  - unbedruckte Fläche
  - unbedruckten Fläche
  - unbedruckte Flächen
  - unbedruckt am Henkel
related_knowledge:
  - POLICY-049
  - POLICY-050
  - POLICY-024
  - POLICY-043
  - PROCESS-010
---

Gilt für alle Kundenarten und Vertriebskanäle; für Amazon-Nachrichten siehe unten.

# Fallmuster

Ein Kunde möchte ein Produkt, das LOOXIS sonst mit Druck oder Gravur fertigt, ohne Personalisierung kaufen, einzeln oder in Menge.

# Erkennungsmerkmale

- Der Kunde schreibt „ohne Gravur“, „ohne Druck“, „unbedruckt“, „blanko“ oder ähnlich.
- Es geht um den Kauf, nicht um eine Beanstandung (ein unbedruckter Bereich an einer bestellten Tasse ist ein anderer Fall, PLAYBOOK-026).

# Zu prüfen

1. Kommt die Anfrage über das Amazon-Nachrichtensystem? Dann gilt der Abschnitt „Amazon“ unten.
2. Welches Produkt ist gemeint (Artikelnummer oder Bezeichnung), und welche Stückzahl?
3. Liegen Rechnungsadresse und, falls abweichend, Lieferadresse schon vor?

# Noch nicht entscheidbar wenn

Produkt, Stückzahl oder Adressen fehlen: gezielt und gesammelt in einer Antwort erfragen (POLICY-049).

# Empfohlene Maßnahme

1. Bestätigen, dass das Produkt auch ohne Gravur oder Druck möglich ist, zum gleichen Preis; bei Mengen mit dem gleichen Rabatt wie bei der personalisierten Variante (POLICY-049).
2. Fehlende Angaben erfragen: Rechnungsadresse, ggf. Lieferadresse, genaue Artikelnummer oder Bezeichnung, Stückzahl.
3. Wenn alles vorliegt: Den Auftrag manuell anlegen und dem Kunden einen Mollie-Zahlungslink senden (POLICY-024, POLICY-043).
4. Den Auftrag nach der Zahlung freigeben.
5. Zum Widerruf nur auf Nachfrage Auskunft geben (bei nicht personalisierten Produkten möglich).

# Amazon

Über das Amazon-Nachrichtensystem: „Dieses Produkt ohne Gravur beziehungsweise Druck bieten wir auf Amazon aktuell nicht an.“ Nicht zum eigenen Shop leiten, keinen Auftrag anlegen. Per direkter E-Mail außerhalb von Amazon gilt der normale Ablauf.

# Kommunikation

Siehe POLICY-049 (Anfrage per E-Mail, Widerruf auf Nachfrage, Amazon-Nachricht). Fragt der Kunde bei einer Mengenanfrage nach einer Rückgabe, gilt POLICY-050.

# No-Gos

Keine Preise oder Rabatte erfinden, bei Amazon-Nachrichten nicht zum Shop leiten, den Widerruf nicht ungefragt erwähnen, keine Lieferzeit zusagen.

# Noch zu klären

- Die genauen Systemschritte in EOCS zum manuellen Anlegen des Auftrags und zum Erstellen des Mollie-Links sind nicht beschrieben.
