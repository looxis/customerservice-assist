---
id: PROCEDURE-004
title: Kundenkonto eines Fachhändlers schließen
type: procedure
status: active
last_reviewed: 2026-10-21
actions:
  - account-closure
customer_types:
  - b2b-reseller
sales_channels:
  - fachhaendler
products: []
categories:
  - order-process-question
topics:
  - customer-account
summary: "Interne Anleitung, wie das Kundenkonto eines Fachhändlers nach dessen schriftlicher Kündigung der Geschäftsbeziehung geschlossen wird: E-Mail-Adresse in Mailcoach aus der Liste löschen, Konto in Vanilo löschen, Kunden in EOCS deaktivieren. Gilt nicht für Konten von Privatkunden."
retrieval: triggered
triggers:
  - Kundenkonto schließen
  - Kundenkonto löschen
  - Konto deaktivieren
  - Konto löschen
  - Geschäftsbeziehung beenden
  - Zusammenarbeit beenden
  - Geschäftsaufgabe
  - Geschäftsschließung
exclude_triggers:
when:
related_knowledge:
  - PLAYBOOK-035
  - POLICY-027
---

Gilt nur für Foto-Fachhändler im Vertriebskanal `fachhaendler`, die die Geschäftsbeziehung von sich aus und schriftlich beendet haben.

# Zweck

Das Kundenkonto eines Fachhändlers schließen, der die Zusammenarbeit beendet hat, damit er keine weiteren E-Mails erhält und sich nicht mehr anmelden kann.

# Voraussetzungen

- Der Fachhändler hat die Geschäftsbeziehung schriftlich beendet (PLAYBOOK-035).
- Die E-Mail-Adresse des Händlers ist bekannt.

# Arbeitsschritte

1. **Mailcoach:** https://looxis.mailcoach.app/email-lists öffnen und die E-Mail-Adresse des Kunden aus der Liste löschen. Der Kunde erhält dadurch keine weiteren E-Mails.
2. **Vanilo:** https://looxis.de/manage/shopper öffnen, nach der E-Mail-Adresse suchen, auf „Edit“ klicken und anschließend „löschen“. Das Backend ist dasselbe für looxis.de und fachhaendler.looxis.de.
3. **EOCS:** https://eocs.loox.is/customers öffnen, den Kunden suchen, auf „Edit“ klicken, „deaktiviert“ anhaken und speichern.

# Kritische Hinweise

- Achtung: In EOCS wird der Kunde nur deaktiviert, nicht gelöscht.
- Achtung: Alle drei Systeme bearbeiten, sonst erhält der Kunde weiter E-Mails oder kann sich noch anmelden.

# Abschlusskontrolle

- [ ] E-Mail-Adresse steht nicht mehr in der Mailcoach-Liste
- [ ] Konto in Vanilo ist gelöscht
- [ ] Kunde in EOCS ist als „deaktiviert“ gespeichert
- [ ] Interne Notiz im Ticket gesetzt
