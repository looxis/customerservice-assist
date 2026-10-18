---
id: POLICY-042
title: Wirksamkeit von Zahlungsartenänderungen am Kundenkonto
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
summary: "Ein Kunde möchte seine Zahlungsart ändern oder eine schon aufgegebene Bestellung nach einer Kundenkontoumstellung anders bezahlen. Änderungen gelten erst nach Eintragung in EOCS und Synchronisierung mit dem Online-Shop und ausschließlich für ab diesem Zeitpunkt neu eingehende Bestellungen."
retrieval: binding
triggers:
  - Zahlungsart ändern
  - Zahlungsart umstellen
  - Zahlungsart geändert
  - Zahlungsart umgestellt
  - Zahlungsmethode ändern
  - Zahlungsweise ändern
  - wieder auf Lastschrift
  - wieder per Lastschrift
  - Lastschriftmandat
  - Bankeinzug
  - auf Rechnung umstellen
  - auf Mollie umstellen
  - bisherige Zahlungsart
  - alte Zahlungsart
  - neue Zahlungsart
  - bereits bestellt
  - offene Bestellung
related_knowledge:
  - POLICY-041
  - POLICY-043
---

Gilt produktübergreifend für die Vertriebskanäle looxis-de, fachhaendler und looxis-pro.

# Regel

Eine Änderung der am Kundenkonto angebotenen Zahlungsart wird erst wirksam, nachdem sie in EOCS vorgenommen und mit dem Online-Shop synchronisiert wurde. Eine Zusage, ein Änderungswunsch oder die Änderung ausschließlich in EOCS genügt noch nicht.

Die Änderung gilt ausschließlich für Bestellungen, die ab diesem wirksamen Änderungszeitpunkt neu eingehen. Sie wirkt nicht rückwirkend auf bereits zuvor aufgegebene Bestellungen.

# Bereits aufgegebene Bestellungen

Frühere Bestellungen werden über die Zahlungsart abgewickelt, die zum Zeitpunkt der jeweiligen Bestellung im Shop am Kundenkonto eingestellt war. Dies ist die für die betriebliche Abwicklung maßgebliche vereinbarte Zahlungsart, auch wenn der Kunde mit ihr nicht einverstanden war.

Eine spätere Umstellung auf Lastschrift, Rechnung oder Mollie ändert die Zahlungsart einer früheren Bestellung nicht. Daraus folgt auch keine automatische Freigabe einer bereits offenen Bestellung. Eine offene Mollie-Bestellung kann unabhängig davon bezahlt werden, auch per klassischer SEPA-Überweisung über Mollie; das ist kein Wechsel der Zahlungsabwicklung (siehe POLICY-043).

# Vorgehen

1. Prüfen, welche Zahlungsart für die konkrete Bestellung zum Bestellzeitpunkt eingestellt war.
2. Bei einer Änderung am Kundenkonto sowohl die Änderung in EOCS als auch die abgeschlossene Synchronisierung mit dem Shop berücksichtigen.
3. Zwischen der Zahlungsart bereits bestehender Bestellungen und den Zahlungsarten für künftige Bestellungen unterscheiden.
4. Dem Kunden bei offenen Bestellungen die dafür weiterhin maßgebliche bisherige Zahlungsart erklären.

Diese Policy regelt die zeitliche Wirkung einer zulässigen Änderung. Sie erteilt keine zusätzliche Befugnis zur Änderung und ersetzt keine kundengruppenspezifischen Voraussetzungen.

# Kommunikation

„Die Änderung Ihrer Zahlungsart gilt für neue Bestellungen, sobald die Umstellung in unserem System vorgenommen und mit dem Online-Shop synchronisiert ist. Für Ihre bereits zuvor aufgegebene Bestellung bleibt die zum Bestellzeitpunkt hinterlegte Zahlungsart maßgeblich.“

# Grenzen

Keine rückwirkende Änderung oder Freigabe einer alten Bestellung allein aufgrund einer Änderung am Kundenkonto zusagen. Einen noch nicht belegten Synchronisierungsabschluss nicht als erfolgt darstellen.
