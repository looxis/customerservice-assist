---
id: PROCEDURE-005
title: Neuproduktion mit Ersatzversand als Reklamationsauftrag in EOCS anlegen
type: procedure
status: active
actions:
  - reproduction
  - reshipment
  - goodwill-reproduction
customer_types:
sales_channels:
products:
categories:
  - complaint
topics:
  - replacement
  - reorder
summary: "Interne Anleitung, wie bei einer berechtigten Reklamation (eigener Fehler) oder einer Kulanz-Neuanfertigung ein Reklamationsauftrag in EOCS angelegt wird: Reklamation am Auftrag öffnen, Info eintragen, nur den reklamierten Artikel stehen lassen, Konfiguration prüfen, Zahlung je nach kostenfrei oder Kulanz wählen, ursprüngliche Versandart, speichern. Nicht für Amazon-Kulanz, dort bestellt der Kunde neu."
retrieval: triggered
triggers:
  - Neuproduktion
  - Ersatzversand
  - kostenfrei neu
  - Reklamationsauftrag
related_knowledge:
  - PERMISSION-002
  - PERMISSION-004
  - POLICY-032
  - POLICY-006
  - PROCESS-001
  - TONE-011
---

Gilt für alle Kanäle und Produkte, wenn ein Reklamationsauftrag für eine kostenfreie Neuproduktion oder eine Kulanz-Neuanfertigung angelegt wird. Bei einer Kulanz-Neuanfertigung auf Amazon gilt dieser Ablauf nicht: Der Kunde bestellt neu, und LOOXIS erstattet teilweise auf die neue Bestellung (POLICY-006).

# Zweck

Einen Reklamationsauftrag anlegen, damit der reklamierte Artikel neu produziert und versendet wird, kostenfrei bei einem Fehler von LOOXIS oder als Kulanz mit einem zu zahlenden Betrag.

# Voraussetzungen

- Die Neuproduktion ist beschlossen (PERMISSION-002, POLICY-032, bei Kulanz PERMISSION-004).
- Die Bestellnummer des Originalauftrags und die Ticketnummer sind bekannt.

# Arbeitsschritte

1. In EOCS: Den Auftrag öffnen: [Auftrag in EOCS öffnen](link:eocs-auftrag).
2. In EOCS: In der Übersicht des Auftrags oben rechts auf „Reklamation“ klicken. Es öffnet sich die Seite „Reklamation von Bestellung #…“.
3. In EOCS: Unter „Reklamation Info“ ganz knapp eintragen, was falsch gelaufen ist (zum Beispiel: weiße Tasse statt schwarz geliefert, bitte neu). Zusätzlich die Ticketnummer eintragen.
4. In EOCS: Etwas nach unten scrollen und unter „Bestellartikel“ prüfen, ob die Bestellung mehrere Artikel enthält. Falls ja, alle Artikel, die **nicht** reklamiert wurden, über das rote Mülleimer-Symbol löschen. Nur der reklamierte Artikel bleibt stehen, denn nur er wird neu produziert.
5. In EOCS: Prüfen, ob der Artikel exakt wie zuvor neu produziert werden soll oder ob die Konfiguration je nach Reklamation überarbeitet werden muss. Dann im APC den Customisation Code auf die neue Konfiguration ändern oder die bestehende Konfiguration im APC überarbeiten.
6. In EOCS: Am Artikel unter „Reklamation Info“ kurz eintragen, was falsch war und was zu tun ist. Dazu kann der Text der Reklamation Info von oben kopiert werden.
7. In EOCS: Unter „Abrechnung“ die Zahlung festlegen:
   - **Kostenfreie Neuproduktion:** Zahlungsmethode „Auf Rechnung“ wählen. Da nichts berechnet wird, entsteht eine Nullrechnung, die nicht weiter beachtet wird.
   - **Kulanz-Neuanfertigung mit zu zahlendem Betrag:** Der Auftrag muss berechnet werden. Die beim Kunden hinterlegte Zahlungsart wählen (Mollie, Lastschrift oder Rechnung).
8. In EOCS: Unter „Versand“ die Versandmethode des ursprünglichen Auftrags wählen.
9. In EOCS: Unten auf „Speichern“ klicken.
10. In EOCS: Passiert nach dem Speichern nichts, liegt ein Eingabefehler vor. Nach oben scrollen und prüfen, ob irgendwo Felder rot markiert sind.
11. In EOCS: Nach erfolgreichem Speichern wird die neu angelegte Bestellung angezeigt. Ihre Nummer (R1-… oder R2-…, je nach Zahl der Reklamationen) in die Antwort an den Kunden einsetzen, falls dort der Platzhalter [REKLAMATIONSNUMMER] steht.

# Kritische Hinweise

- Achtung: Alle Artikel löschen, die nicht reklamiert wurden. Sonst werden sie ebenfalls neu produziert und versendet.
- Achtung: Konfiguration prüfen. Eine unveränderte Neuproduktion wiederholt einen Konfigurationsfehler.
- Achtung: Bei einer Kulanz mit Betrag nicht „Auf Rechnung“ mit Nullrechnung wählen, sondern die Zahlungsart des Kunden.

# Abschlusskontrolle

- [ ] Nur der reklamierte Artikel steht im Reklamationsauftrag
- [ ] „Reklamation Info“ (Auftrag und Artikel) ist ausgefüllt, Ticketnummer eingetragen
- [ ] Konfiguration geprüft und im APC angepasst, falls nötig
- [ ] Zahlung passend gewählt (kostenfrei: auf Rechnung; Kulanz: Zahlungsart des Kunden)
- [ ] Ursprüngliche Versandmethode gewählt
- [ ] Neue Bestellung wird nach dem Speichern angezeigt, Nummer in der Antwort ergänzt
- [ ] Interne Notiz im Ticket gesetzt
