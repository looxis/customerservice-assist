---
id: POLICY-040
title: Bestellfreigabe und eigener Versand bei Amazon
type: policy
status: draft
customer_types:
  - b2c
sales_channels:
  - amazon
products:
categories:
  - complaint
  - order-process-question
topics:
  - order-release
  - delivery-delay
summary: "Ein Amazon-Kunde fragt, ob seine Bestellung bei LOOXIS eingegangen ist, warum sie trotz früherem Bestelldatum noch nicht bearbeitet wird oder ob nach einer Zahlungsaufforderung geliefert wird. Erklärt Amazons Rückhaltephase, die Bearbeitung ab Eingang bei LOOXIS und den Versand durch LOOXIS über eigene Paketdienste."
retrieval: triggered
triggers:
  - Bestellung freigegeben
  - Bestellung zurückgehalten
  - Bestellung ausstehend
  - Bestellung eingegangen
  - Bestellung nicht eingegangen
  - Bestellung nicht gefunden
  - Bestellung nicht auffindbar
  - Bestellung noch nicht
  - Bestellfreigabe
  - Zahlungsproblem
  - Zahlungsaufforderung
  - Zahlungsmethode
  - Zahlungsart
  - Bezahlung
  - bezahlt
  - bezahlen
  - Zahlung
  - wann wird geliefert
  - wird die Bestellung geliefert
  - wann wird versendet
  - wann wird verschickt
  - wer liefert
  - wer versendet
  - Amazon Lieferservice
  - Amazon-Lieferservice
  - pending order
  - payment revision
related_knowledge:
  - POLICY-039
---

Gilt für Privatkunden im Vertriebskanal Amazon und für die von LOOXIS selbst produzierten und versendeten Bestellungen, produktübergreifend.

# Regel

Amazon hält grundsätzlich jede Kundenbestellung mindestens 30 Minuten zurück, bevor sie an LOOXIS übermittelt wird. Während der Rückhaltephase kann der Kunde die Bestellung bei Amazon sofort und ohne Einschränkungen oder Mitwirkung von LOOXIS stornieren.

**Folgerung für Anfragen:** Wird eine Amazon-Bestellnummer mit gültigem Format in EOCS nicht gefunden, liegt die Bestellung LOOXIS nicht vor: möglicherweise ist sie noch nicht freigegeben, möglicherweise hat sie der Kunde innerhalb der 30 Minuten storniert oder Amazon hat sie nach einer Frist (z. B. 14 Tage) wegen einer ungeklärten Zahlung storniert. Mitarbeiter mit Amazon-Zugang können das nachsehen, andere nicht. Es braucht deshalb keine interne Klärung vor der Antwort; sie lautet „liegt uns derzeit nicht zur Bearbeitung vor“ (Vorlage siehe PLAYBOOK-027), ohne einen Grund als Tatsache zu nennen.

LOOXIS kann währenddessen im Amazon-Backend erkennen, dass eine Bestellung existiert, sie aber nicht abrufen und keine Bestelldetails einsehen. Erst nach Freigabe und Eingang bei LOOXIS kann die Bestellung bearbeitet werden.

# Hintergrund

Nach Erfahrung von LOOXIS wird der allergrößte Teil der Bestellungen nach exakt 30 Minuten freigegeben und durch das Warenwirtschaftssystem EOCS automatisiert abgerufen. Einzelne Bestellungen hält Amazon länger zurück; die 30 Minuten sind deshalb keine Zusage für die Freigabe einer konkreten Bestellung.

Bei einer längeren Rückhaltephase bestehen nach Erfahrung von LOOXIS in der Regel Zahlungsprobleme oder Unklarheiten zwischen Amazon und dem Kunden. Welche das konkret sind, weiß LOOXIS nicht und kann es auch nicht in Erfahrung bringen. Eine längere Rückhaltephase belegt für sich allein kein bestimmtes Zahlungsproblem.

Nach Erfahrung von LOOXIS muss der Kunde die Zahlungsunklarheiten mit Amazon beseitigen, bevor Amazon die Bestellung freigibt und übermittelt. Das kann auch die Durchführung einer von Amazon verlangten Vorauszahlung betreffen. Die Zahlungszuständigkeit regelt POLICY-039.

# Produktion und Reihenfolge

Erst nach Freigabe und Eingang bei LOOXIS können Produktion und Versand beginnen. Die Bearbeitung erfolgt in der üblichen zügigen Geschwindigkeit in der Reihenfolge des Eingangs bei LOOXIS.

Das ursprüngliche Bestelldatum bei Amazon verschafft keinen Vorrang. Eine vor einer Woche bei Amazon aufgegebene, aber erst jetzt an LOOXIS übermittelte Bestellung wird nach den bereits zuvor bei LOOXIS eingegangenen Bestellungen abgearbeitet.

# Eigener Versand

Auf dem Amazon-Marktplatz verkaufen auch unabhängige Händler und Hersteller. LOOXIS versendet die hier beschriebenen Bestellungen im eigenen Namen über einen von Amazon unabhängigen Paketdienst wie DHL oder UPS. Der Amazon-Lieferservice ist an diesen Sendungen nicht beteiligt.

# Interner Zahlungshintergrund

Mit der Freigabe ist die Auszahlung an LOOXIS über Amazon nach der beschriebenen Praxis grundsätzlich garantiert, unabhängig davon, ob der Kunde Amazon bezahlt hat. Andere Faktoren wie eine unterlassene Lieferung oder ein späterer A-bis-Z-Garantieantrag können dies berühren. Daraus folgt keine Auskunft über den Zahlungsstatus des Kunden.

# Grenzen

- Eine bei LOOXIS noch nicht abrufbare Bestellung nicht allein deshalb als storniert oder unbezahlt bezeichnen.
- Keine Freigabe nach genau 30 Minuten oder sofortige Produktion nach einer Zahlung zusagen.
- Keine bevorzugte Bearbeitung wegen eines älteren Amazon-Bestelldatums zusagen.
- Den tatsächlichen Paketdienst nur anhand der konkreten Versanddaten nennen.

