---
id: PROCEDURE-002
title: Rechnung aus EOCS herunterladen und in Zammad versenden
type: procedure
status: draft
products: []
categories:
- order-process-question
topics:
- invoice-request
- invoice-delivery
customer_types: []
sales_channels: []
related_knowledge:
- PROCESS-008
- PROCESS-009
actions:
- invoice-send
---

Gilt für alle Produkte, Kundenarten und Vertriebskanäle.

# Zweck

Eine vorhandene Rechnung als PDF aus EOCS herunterladen und einer Kundenantwort in Zammad beifügen.

# Voraussetzungen

- Die Bestellnummer liegt vor und die Bestellung ist zugeordnet.
- Die zu versendende Rechnung ist bereits erstellt.
- Es ist entschieden, dass die vorhandene Rechnung erneut beziehungsweise die neu ausgestellte Rechnung versendet wird.

# Arbeitsschritte

1. Auf „in EOCS öffnen“ klicken oder EOCS aufrufen und die Bestellnummer einfügen.
2. In EOCS die Bestellung öffnen.
3. Links in der Übersicht auf „Buchhaltungsdokumente (1)“ klicken. Die Zahl in Klammern zeigt die Anzahl der Dokumente; bei mehreren Dokumenten steht dort eine andere Zahl.
4. In der Übersicht die Rechnungsnummer der zu versendenden Rechnung anklicken.
5. Auf der geöffneten Seite mit den Rechnungspositionen oben rechts auf „Download“ klicken und die Rechnung herunterladen.
6. In Zammad das Antwort-Fenster des zugehörigen Tickets öffnen.
7. Die soeben heruntergeladene Rechnung in das Antwort-Fenster ziehen.
8. Den PDF-Anhang und die vorbereitete Antwort kontrollieren und die Antwort an den Kunden senden.

# Kritische Hinweise

Achtung: Die Dokumentenzahl sagt nicht, welches Dokument die richtige Rechnung ist. Bei mehreren Dokumenten die passende Rechnungsnummer auswählen.
Achtung: Nach einer Rechnungskorrektur die neue Rechnung auswählen, nicht die stornierte ursprüngliche Rechnung.
Achtung: Vor dem Senden prüfen, dass der PDF-Anhang zur zugeordneten Bestellung gehört.

# Abschlusskontrolle

- [ ] Die richtige Rechnung wurde als PDF heruntergeladen.
- [ ] Das PDF ist in der Antwort an den Kunden als Anhang sichtbar.
- [ ] Die Antwort mit Rechnung wurde an den Kunden gesendet.
