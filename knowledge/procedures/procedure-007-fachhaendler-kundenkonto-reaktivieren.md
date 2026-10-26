---
id: PROCEDURE-007
title: Deaktiviertes Fachhändler-Kundenkonto reaktivieren und Zahlungsart ändern
type: procedure
status: draft
actions:
  - account-reactivation
customer_types:
  - b2b-reseller
sales_channels:
  - fachhaendler
products: []
categories:
  - order-process-question
topics:
  - customer-account
  - payment-processing
summary: "Interne Anleitung, wie das deaktivierte Kundenkonto eines Fachhändlers nach Rücksprache wieder aktiviert und die Zahlungsart in EOCS und Vanilo korrekt gesetzt wird: Konto in Vanilo suchen, Schalter „active“ aktivieren, Zahlungsart in EOCS bei „Standard“ und beim Vertriebskanal fachhaendler.looxis.de ändern, mit Vanilo synchronisieren, in Vanilo kontrollieren. Gilt nicht für nicht übernommene Konten oder neue Händler."
retrieval: triggered
triggers:
  - Konto reaktivieren
  - Konto wieder aktivieren
  - Konto freischalten
  - Konto deaktiviert
  - Konto ist deaktiviert
related_knowledge:
  - PLAYBOOK-032
  - POLICY-052
  - POLICY-042
  - POLICY-041
  - PROCESS-015
  - PROCEDURE-008
---

Gilt nur für Foto-Fachhändler im Vertriebskanal `fachhaendler`, deren Konto im Shop deaktiviert ist.

# Zweck

Ein deaktiviertes Fachhändlerkonto nach Rücksprache wieder nutzbar machen, ohne dass eine veraltete Zahlungsvereinbarung unbemerkt weiterläuft.

# Voraussetzungen

- Der Kunde hat bestätigt, dass er das Konto weiter nutzen möchte.
- Die Zahlungsart ist nach POLICY-052 geklärt: Standard Mollie; Lastschrift nur auf ausdrücklichen Wunsch des Kunden und mit Freigabe der Geschäftsleitung; Rechnung wird nicht mehr angeboten.
- Die E-Mail-Adresse des Kunden ist bekannt.

# Arbeitsschritte

1. [Vanilo-Shopper öffnen](link:vanilo-kunden): im Vanilo-Backend unter CRM → Customers den Kunden suchen (Filter-Symbol mit den drei Schiebereglern oben rechts, zum Beispiel nach der E-Mail-Adresse) und das Ergebnis anklicken.
2. Oben rechts auf „Edit“ klicken. In der Mitte des Bildschirms beim Schalter „active“ den Schalter anklicken, er schiebt sich nach rechts. Speichern.
3. [Kundenliste in EOCS öffnen](link:eocs-kunden), den Kunden aufrufen und im Bereich „Standard“ die gewünschte Zahlungsart einstellen.
4. In EOCS nach unten scrollen und beim Vertriebskanal „fachhaendler.looxis.de“ dieselbe Zahlungsart einstellen. Speichern.
5. In EOCS den Vertriebskanal „fachhaendler.looxis.de“ über die Checkbox auswählen, oberhalb der Auswahl auf die drei Punkte (⋮) klicken, „Kunden aktualisieren“ wählen und bestätigen. Die Daten gehen damit an Vanilo.
6. Das Konto in Vanilo erneut aufrufen und prüfen, dass die Zahlungsart übernommen wurde und „active“ eingeschaltet ist.
7. Den Kunden informieren: Das Konto ist wieder aktiv, welche Zahlungsart ab der nächsten Bestellung gilt und dass frühere Zahlungsvereinbarungen nicht automatisch gelten. Interne Notiz im Ticket setzen.

# Kritische Hinweise

- Achtung: Die Zahlungsart in EOCS an **beiden** Stellen ändern (Standard und Vertriebskanal fachhaendler.looxis.de); nur „Standard“ genügt nicht.
- Achtung: Eine frühere Zahlung auf Rechnung oder per Lastschrift nicht ungeprüft übernehmen; Rechnung wird nicht mehr angeboten.
- Achtung: Lastschrift nur nach Freigabe der Geschäftsleitung.
- Achtung: Der Vorgang ist erst abgeschlossen, wenn die Zahlungsart in Vanilo kontrolliert wurde.

# Abschlusskontrolle

- [ ] Schalter „active“ im Vanilo-Konto ist eingeschaltet
- [ ] Zahlungsart in EOCS bei „Standard“ und beim Vertriebskanal „fachhaendler.looxis.de“ gesetzt
- [ ] „Kunden aktualisieren“ ausgeführt
- [ ] Zahlungsart in Vanilo kontrolliert
- [ ] Kunde informiert
- [ ] Interne Notiz im Ticket gesetzt
