---
id: POLICY-054
title: "Leuchtsockel: Gewährleistung, Kulanz und Widerruf bei Direktkäufen"
type: policy
status: draft
customer_types:
  - b2c
sales_channels:
products:
  - led-base
  - led-base-retired
categories:
  - complaint
topics:
  - warranty
  - replacement
  - goodwill
  - withdrawal
summary: "Ein Privatkunde meldet einen defekten oder nicht funktionierenden Leuchtsockel (LEDs, Fernbedienung, Elektronik, Batterien, Netzteil), fragt nach Garantie oder Widerruf oder will ihn zurückgeben. Regelt Ersatz, Kulanz und Kundenfehler nach Kaufdatum. Gilt nicht für Sockel, die bei einem Fachhändler gekauft wurden (POLICY-055)."
retrieval: binding
triggers:
  - Leuchtsockel defekt
  - Sockel defekt
  - leuchtet nicht
  - LED ausgefallen
  - LEDs ausgefallen
  - Fernbedienung funktioniert
  - Batterien ausgelaufen
  - flackert
  - Netzteil
  - Garantie
  - Widerruf
  - zurückgeben
exclude_triggers:
when:
actions:
  - reshipment
  - reproduction
  - goodwill-reproduction
  - refund
  - partial-refund
  - return
related_knowledge:
  - POLICY-002
  - POLICY-004
  - POLICY-049
  - POLICY-055
  - PERMISSION-007
  - PLAYBOOK-034
  - PROCESS-005
---

Gilt für Privatkunden, die einen Leuchtsockel direkt bei LOOXIS gekauft haben (looxis.de, Amazon). Gilt nicht für Sockel, die bei einem Fachhändler gekauft wurden (POLICY-055).

# Regel

Leuchtsockel und Netzteile sind keine personalisierten Produkte. POLICY-001 gilt für sie nicht. Es gelten die gesetzliche Gewährleistung und das Widerrufsrecht für Verbraucher.

LOOXIS gewährt keine freiwillige Garantie. Gegenüber dem Kunden wird nicht von „Garantie“ gesprochen, sondern von Gewährleistung oder Kulanz.

Bei einem Defekt bieten wir **Ersatz** an, keine Erstattung. Ist das Modell nicht mehr lieferbar, wird eine verfügbare Alternative angeboten (bei Opera ein modularer Leuchtsockel in passender Größe, gegebenenfalls im Paket mit Rückwand).

# Staffel nach Kaufdatum und Defekt

| Lage | Maßnahme |
|---|---|
| Kaufdatum bekannt oder auffindbar, bis 12 Monate, Defekt per Foto oder Video bestätigt | Kostenloser Ersatzsockel |
| Kaufdatum bekannt, 12 bis 24 Monate, deutlicher Defekt (mehrere LEDs, Elektronik, Fernbedienung bei korrekter Bedienung) | Kostenloser Ersatzsockel, sorgfältige Prüfung |
| Kaufdatum bekannt, 12 bis 24 Monate, geringfügiger Defekt (zum Beispiel eine von vielen LEDs) | Ersatzsockel mit 50 Prozent Nachlass (PERMISSION-007) |
| Kein Nachweis, Kaufzeitpunkt nicht feststellbar | Kein Anspruch unterstellen; Ersatzsockel mit 50 Prozent Nachlass (PERMISSION-007) |
| Kunde nennt einen Kaufzeitraum über 24 Monate, oder das Modell ist sicher älter als 24 Monate | Keine Kulanz |
| Ausgelaufene Batterien, vom Kunden eingesetzt | Kundenfehler: kein kostenloser Ersatz; neuer Sockel zum regulären Preis |
| Falsches Netzteil verwendet | Kundenfehler: Reklamation nicht anerkannt; neuer Sockel zum regulären Preis |

**Nach 24 Monaten** gibt es keine Kulanz, auch nicht für Endverbraucher. Leuchtsockel mit LEDs verschleißen, und die Nutzung (zum Beispiel im Dauerbetrieb) ist unbekannt.

Ein neuer Sockel zum regulären Preis darf in jedem Fall angeboten werden.

# Kaufdatum ermitteln

1. In EOCS und bei Amazon nach Name, Adresse oder E-Mail-Adresse des Kunden suchen.
2. Gelingt das nicht, den Kunden nach dem ungefähren Kaufzeitpunkt fragen (Quittung oder Bestellbestätigung, falls vorhanden).
3. Das Ergebnis in einer internen Notiz im Ticket festhalten.

# Erstattung

Keine Erstattung bei defekten Sockeln. Ist ein Ersatz nicht möglich oder schlägt er wiederholt fehl, nicht ablehnen, sondern intern klären (PROCESS-005).

# Widerruf

Bei Leuchtsockeln und Netzteilen besteht für Verbraucher das gesetzliche Widerrufsrecht von **14 Tagen**. Die Frist beginnt an dem Tag, an dem der Verbraucher die Ware erhalten hat. Ein Defekt ist dafür nicht nötig. Die Frist darf genannt werden. Das Widerrufsrecht wird nicht von sich aus erwähnt, sondern bei einer Rückgabefrage oder auf Nachfrage; Einzelheiten stehen in der Widerrufsbelehrung (vgl. POLICY-049).

Außerhalb des Widerrufs und ohne Mangel besteht kein Anspruch auf Umtausch oder Rücknahme wegen Nichtgefallens.

# Gilt nicht wenn

- Der Sockel wurde bei einem Fachhändler gekauft (POLICY-055).
- Es geht um ein personalisiertes Produkt (POLICY-002, POLICY-001).

# Eskalieren wenn

- Ausgelaufene Batterien mit Beschädigung oder Sicherheitsrisiko.
- Die Batterien wurden von LOOXIS mitgeliefert.
- Ersatz ist nicht möglich oder fehlgeschlagen.
- Der Kunde widerspricht der Einordnung und beruft sich auf Gesetzesrechte.
