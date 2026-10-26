---
id: PROCEDURE-008
title: Unverifiziertes Fachhändler-Kundenkonto für den Kunden verifizieren
type: procedure
status: draft
trial_until: 2026-11-02
trial_goal: "Nach dem 2. November 2026 prüfen, ob der Ablauf noch gebraucht wird; unverifizierte Konten werden dann deaktiviert, der Ablauf kann entfallen."
actions:
  - account-verification
customer_types:
  - b2b-reseller
sales_channels:
  - fachhaendler
products: []
categories:
  - order-process-question
topics:
  - customer-account
summary: "Interne Anleitung, wie der Kundenservice das unverifizierte Kundenkonto eines Fachhändlers für ihn bestätigt, damit „Passwort vergessen“ im Shop wieder funktioniert: Konto in Vanilo suchen und „Mark as verified“ klicken. Gilt nur bis zur Umstellung am 2. November 2026; nicht für deaktivierte Konten."
retrieval: triggered
triggers:
  - Konto verifizieren
  - Konto bestätigen
  - Bestätigungsmail
  - Bestätigungslink
related_knowledge:
  - PLAYBOOK-032
  - POLICY-052
  - PROCEDURE-007
---

Gilt nur für Foto-Fachhändler im Vertriebskanal `fachhaendler`, deren Konto im Shop den Status „unverifiziert“ hat, bis zur Umstellung am 2. November 2026.

# Zweck

Das Konto eines Händlers bestätigen, der den Link der Bestätigungsmail nicht angeklickt hat, damit er sich über „Passwort vergessen“ ein neues Passwort vergeben kann.

# Voraussetzungen

- Der Händler hat Screenshot und E-Mail-Adresse geschickt, die Shop-Adresse und das Logo „looxis reseller“ sind geklärt (PLAYBOOK-032).
- Der Kontostatus in Vanilo ist „unverifiziert“.

# Arbeitsschritte

1. [Vanilo-Shopper öffnen](link:vanilo-kunden): im Vanilo-Backend unter CRM → Customers den Kunden suchen (Filter-Symbol mit den drei Schiebereglern oben rechts, zum Beispiel nach der E-Mail-Adresse) und das Ergebnis anklicken.
2. Oben rechts auf „Mark as verified“ klicken und bestätigen.
3. Dem Händler schreiben, dass er sich jetzt über „Passwort vergessen“ ein neues Passwort vergeben kann. Interne Notiz im Ticket setzen.

# Kritische Hinweise

- Achtung: Nur unverifizierte Konten verifizieren. Ein deaktiviertes Konto wird nach PROCEDURE-007 wieder aktiviert.

# Abschlusskontrolle

- [ ] Konto in Vanilo ist als verifiziert markiert
- [ ] Händler informiert
- [ ] Interne Notiz im Ticket gesetzt
