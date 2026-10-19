---
id: POLICY-049
title: Produkte auch ohne Druck oder Gravur bestellen
type: policy
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
summary: "Ein Kunde möchte ein sonst personalisiertes Produkt ohne Druck, Gravur oder Aufdruck bestellen, einzeln oder in Menge. Produkte können grundsätzlich auch ohne Personalisierung verkauft werden, zum gleichen Preis und bei Mengenanfragen mit dem gleichen Rabatt; der Kundenservice legt den Auftrag manuell an, bezahlt wird vorab über einen Mollie-Link. Über Amazon-Nachrichten wird das nicht angeboten."
retrieval: triggered
triggers:
  - ohne Gravur
  - ohne Druck
  - ohne Personalisierung
  - ohne Aufdruck
  - ohne Bedruckung
  - ohne Motiv
  - unbedruckt
  - unbedruckte
  - unpersonalisiert
  - nicht personalisiert
  - blanko
  - Rohling
  - neutrales Produkt
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
  - PLAYBOOK-030
  - POLICY-024
  - POLICY-026
  - POLICY-043
  - PROCESS-010
  - POLICY-001
---

Gilt für alle Kundenarten und Vertriebskanäle; für Amazon-Nachrichten gilt der Abschnitt „Amazon“.

# Regel

Produkte können grundsätzlich auch ohne Gravur oder Druck verkauft werden. Es gilt derselbe Preis wie mit Gravur oder Druck, bei Mengenanfragen derselbe Rabatt wie bei der personalisierten Variante. Beträge nennt der Kundenservice nur, wenn sie vorliegen oder ein Mitarbeiter sie vorgibt (POLICY-026).

# Benötigte Angaben

Wie bei Mengenanfragen (PROCESS-010):

- die vollständige Rechnungsadresse und, falls abweichend, die Lieferadresse,
- die genaue Artikelnummer oder Bezeichnung des gewünschten Produkts,
- die Stückzahl.

Was schon mitgeteilt wurde, wird nicht erneut erfragt.

# Bestellweg und Zahlung

Der Kundenservice legt den Auftrag manuell an. Bezahlt wird im Voraus über Mollie: Der Kunde erhält einen Mollie-Zahlungslink, die Hinweise zu Mollie in POLICY-043 und POLICY-024 gelten. Nach der Zahlung wird der Auftrag freigegeben. Zahlung auf Rechnung nur nach Freigabe der Geschäftsleitung (POLICY-024).

# Amazon

Fragt ein Kunde über das Amazon-Nachrichtensystem nach einem Produkt ohne Druck oder Gravur, das auf Amazon nicht angeboten wird, lautet die Antwort, dass wir das auf Amazon aktuell nicht anbieten. Der Kunde wird nicht zum eigenen Shop oder zu einer Bestellung außerhalb von Amazon geleitet. Fragt der Kunde direkt per E-Mail an, also nicht über das Amazon-Nachrichtensystem, ist alles wie oben möglich.

# Widerruf

Bei nicht personalisierten Produkten ist ein Widerruf möglich. Das wird nicht von sich aus mitgeteilt, sondern nur auf Nachfrage; die Einzelheiten ergeben sich aus den gesetzlichen Regeln und der Widerrufsbelehrung. POLICY-001 (keine Rücknahme personalisierter Ware) und die Aussage „kein Widerrufsrecht“ gelten für Bestellungen ohne Personalisierung nicht.

# Kommunikation

Anfrage per E-Mail (nicht über Amazon): „Gerne liefern wir Ihnen [PRODUKT] auch ohne Gravur beziehungsweise Druck, zum gleichen Preis wie die personalisierte Variante; bei Mengen gilt auch derselbe Rabatt. Dafür benötigen wir Ihre vollständige Rechnungsadresse und, falls abweichend, die Lieferadresse, die genaue Artikelnummer oder Bezeichnung des gewünschten Produkts und die Stückzahl. Die Bezahlung erfolgt im Voraus über Mollie: Wir legen den Auftrag für Sie an und senden Ihnen einen Zahlungslink. Nach der Zahlung geben wir den Auftrag frei.“

Auf Nachfrage zum Widerruf: „Bei nicht personalisierten Produkten ist ein Widerruf möglich. Die Einzelheiten entnehmen Sie bitte unserer Widerrufsbelehrung.“

Amazon-Nachricht: „Vielen Dank für Ihre Anfrage. Dieses Produkt ohne Gravur beziehungsweise Druck bieten wir auf Amazon aktuell nicht an.“

# Grenzen

- Keine Preise oder Rabatte nennen oder festsetzen, die nicht vorliegen.
- Bei Amazon-Nachrichten nicht zum eigenen Shop leiten.
- Den Widerruf nicht ungefragt erwähnen; keine Fristen nennen.
- Keine Lieferzeit zusagen (POLICY-025).
- Gesetzliche Ansprüche bleiben unberührt; interne Regeln dürfen ihre Erfüllung nicht vereiteln.
