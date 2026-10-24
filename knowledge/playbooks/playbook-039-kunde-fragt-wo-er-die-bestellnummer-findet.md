---
id: PLAYBOOK-039
title: Kunde fragt, wo er die Bestellnummer findet
type: playbook
status: active
last_reviewed: 2026-10-24
customer_types:
sales_channels:
products:
categories:
  - order-process-question
topics:
  - order-number
  - sales-channel
summary: "Ein Kunde oder Fachhändler fragt, wo er seine Bestellnummer findet, oder hat sie nicht zur Hand (zum Beispiel für eine Rückfrage, Reklamation oder Rechnung). Beschreibt, wo die Nummer bei Amazon, auf looxis.de und bei Fachhändlern steht und was zu tun ist, wenn der Kunde sie nicht findet."
retrieval: triggered
triggers:
  - Bestellnummer
  - Auftragsnummer
  - Bestellnummer finden
  - wo finde ich
  - habe die Bestellnummer nicht
  - Bestellnummer vergessen
  - Bestellnummer verloren
exclude_triggers:
when:
actions:
related_knowledge:
  - POLICY-054
  - POLICY-056
  - GLOSSARY-003
---

Gilt für alle Kundenarten. Die Antwort hängt vom Bestellweg ab: Amazon oder eigener Shop (looxis.de, fachhaendler.looxis.de).

# Fallmuster

Ein Kunde fragt, wo er seine Bestellnummer findet, oder nennt sie nicht, obwohl wir sie für die Bearbeitung brauchen.

# Empfohlene Maßnahme

**Bestellung bei Amazon:** Die Bestellnummer steht bei Amazon unter „Meine Bestellungen“.

**Bestellung auf looxis.de (Endverbraucher):** Die Bestellnummer steht

- im Kundenkonto unter „Bestellungen“, sofern der Kunde ein Kundenkonto angelegt hat,
- in der Bestellbestätigung, die per E-Mail verschickt wurde,
- auf der Rechnung, die ebenfalls per E-Mail verschickt wurde.

Dabei auch den **Spam-Ordner** prüfen lassen.

**Fachhändler und Reseller:** Es gilt dasselbe wie für Endverbraucher (Kundenkonto, Bestellbestätigung, Rechnung per E-Mail).

# Findet der Kunde die Nummer nicht

- **looxis.de, Fachhändler und direkte E-Mail:** Die Bestellung in EOCS anhand von Name, Adresse oder E-Mail-Adresse des Kunden suchen und die Nummer nennen.
- **Amazon:** Keine Bestellnummer aus EOCS herausgeben, sondern bei „Meine Bestellungen“ bleiben. Im Amazon-Nachrichtensystem nicht zu eigenen Kanälen leiten (POLICY-056).

# Kommunikation

Die Fundstellen in der Reihenfolge der Wahrscheinlichkeit nennen, kurz und ohne Vorwurf. Am Ende darauf hinweisen, dass bei E-Mails auch der Spam-Ordner geprüft werden sollte.

# No-Gos

- Bei Amazon-Bestellungen auf looxis.de, eine E-Mail-Adresse oder andere eigene Kanäle verweisen.
- Eine Bestellnummer nennen, ohne sicher zu sein, dass sie zu diesem Kunden gehört.
