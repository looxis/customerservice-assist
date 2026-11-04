---
id: PROCEDURE-010
title: Neues Fachhändler-Kundenkonto in EOCS anlegen und synchronisieren
type: procedure
status: draft
actions:
  - account-creation
customer_types:
  - b2b-reseller
sales_channels:
  - fachhaendler
products:
categories:
  - order-process-question
topics:
  - customer-account
summary: "Interne Anleitung, wie das Kundenkonto eines neuen Fachhändlers in EOCS angelegt wird: Kunde erstellen, Rechnungs- und Lieferadresse, Vertriebskanal fachhaendler.looxis.de und Zahlungsart wählen, speichern, Kanal und Zahlungsart im Konto nochmals setzen, Kunden aktualisieren und den Kunden per E-Mail informieren. Gilt nicht für bestehende oder deaktivierte Konten (PROCEDURE-007)."
retrieval: triggered
triggers:
  - Konto anlegen
  - Kundenkonto anlegen
  - Konto eröffnen
related_knowledge:
  - PLAYBOOK-050
  - POLICY-052
  - PROCEDURE-007
---

Gilt nur für neue Foto-Fachhändler im Vertriebskanal fachhaendler.

# Zweck

Für einen neuen Fachhändler ein Kundenkonto anlegen, damit er im Fachhändler-Shop bestellen kann.

# Voraussetzungen

- Der Kunde ist gewerblich tätig und hat ernsthaftes Interesse; bei Zweifel hat die Geschäftsleitung zugestimmt (PLAYBOOK-050).
- Firmenname, Rechnungs- und Lieferadresse, E-Mail-Adresse und USt-IdNr. des Kunden liegen vor.

# Arbeitsschritte

1. In EOCS: [Neues Kundenkonto erstellen](link:eocs-kunde-anlegen).
2. In EOCS: Rechnungs- und Lieferadresse eintragen.
3. In EOCS: Den Vertriebskanal wählen, zum Beispiel „fachhaendler.looxis.de“.
4. In EOCS: Die Zahlungsart wählen, zum Beispiel „online“ und „zahlbar sofort“. Rechnung ist bei Neukunden ausgeschlossen, Lastschrift nur auf Anfrage des Kunden (POLICY-041).
5. In EOCS: Speichern.
6. In EOCS: Im gespeicherten Kundenkonto weiter unten den Vertriebskanal nochmals hinzufügen und die Zahlungsart nochmals einfügen.
7. In EOCS: Die Checkbox beim Vertriebskanal anhaken, auf die drei Punkte (⋮) klicken und „Kunden aktualisieren“ wählen.
8. Dem Kunden das neue Konto per E-Mail bestätigen und ihn bitten, unter https://fachhaendler.looxis.de im Bereich „Login“ die Funktion „Passwort vergessen“ zu nutzen (PLAYBOOK-050). Interne Notiz im Ticket setzen.

# Kritische Hinweise

- Achtung: Vertriebskanal und Zahlungsart werden zweimal gesetzt, beim Anlegen und danach im gespeicherten Konto.
- Achtung: Einem Neukunden keine Rechnung als Zahlungsart einstellen.

# Abschlusskontrolle

- [ ] Rechnungs- und Lieferadresse eingetragen
- [ ] Vertriebskanal und Zahlungsart beim Anlegen und im gespeicherten Konto gesetzt
- [ ] „Kunden aktualisieren“ ausgeführt
- [ ] Kunde per E-Mail informiert, wie er sein Passwort setzt
- [ ] Interne Notiz im Ticket gesetzt
