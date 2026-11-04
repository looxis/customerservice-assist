---
id: PLAYBOOK-049
title: "LOOXIS-Pro: Shop nur mit vorgeschalteter Passwortabfrage erreichbar (Newsletter vom 26.10.2026)"
type: playbook
status: active
trial_until: 2026-12-15
trial_goal: "Wir erkennen, ob der Verweis auf den Newsletter und die Zugangsdaten die Rückfragen zum Shopzugang beantwortet, bis der Shop auf das neue System umgestellt ist."

customer_types:
  - b2b-pro
sales_channels:
  - looxis-pro

products:
categories:
  - order-process-question
topics:
  - customer-account
  - shop-outage

summary: "Ein LOOXIS-Pro-Kunde kann den Shop www.looxis-pro.com nicht aufrufen, wird nach Benutzername und Passwort gefragt, kann nicht bestellen oder fragt nach dem Newsletter zur Sicherheitslücke. Anweisung an den Agenten: auf den Newsletter vom 26.10.2026 verweisen, die Zugangsdaten der vorgeschalteten Passwortabfrage nennen und erklären, dass danach die gewohnte Anmeldung folgt. Gilt nur befristet bis 15.12.2026."
retrieval: triggered
triggers:
  - Passwortabfrage
  - Passwortschutz
  - Benutzername und Passwort
  - Zugangsdaten
  - Shop offline
  - Shop nicht erreichbar
  - Seite nicht erreichbar
  - Webseite nicht erreichbar
  - nicht bestellen
  - Sicherheitslücke
  - Newsletter
  - looxis-pro.com
exclude_triggers:
when:
actions:

related_knowledge:
  - POLICY-064
  - POLICY-065
---

Gilt nur für LOOXIS-Pro-Kunden (Bestellweg www.looxis-pro.com). **Testweise gültig bis 15.12.2026.** Danach diese Anweisung nicht mehr anwenden, solange sie nicht bestätigt oder verlängert wurde.

# Fallmuster

Der LOOXIS-Pro-Shop war wegen einer kritischen Sicherheitslücke in PHP, die am 22. Oktober 2026 weltweit bekannt wurde, vorübergehend offline. Seit dem Newsletter vom 26. Oktober 2026, 9:30 Uhr, ist er wieder erreichbar, aber nur über eine vorgeschaltete Passwortabfrage für die gesamte Webseite. Das ist eine Übergangslösung und beschränkt den Zugang auf Bestandskunden. Der Shop wird auf ein neues System umgestellt. Die Passwortabfrage soll nur für einige Wochen nötig sein.

# Erkennungsmerkmale

- Der Kunde schreibt, er könne den LOOXIS-Pro-Shop nicht aufrufen oder nicht bestellen.
- Der Kunde wird nach einem Benutzernamen und Passwort gefragt, kennt diese aber nicht.
- Der Kunde fragt nach dem Newsletter oder der Sicherheitslücke.

# Zu prüfen

1. Handelt es sich um einen LOOXIS-Pro-Kunden und den Shop www.looxis-pro.com?
2. Schildert der Kunde ein anderes Problem als die Passwortabfrage (zum Beispiel eine Fehlermeldung nach der eigenen Anmeldung)? Dann nachfragen, was genau er sieht.

# Empfohlene Maßnahme

Auf den Newsletter vom 26. Oktober 2026 (9:30 Uhr) hinweisen, erklären, dass der zusätzliche Passwortschutz wegen der am 22. Oktober 2026 weltweit bekannt gewordenen PHP-Sicherheitslücke notwendig wurde, und dem Kunden den Zugang direkt erklären:

1. www.looxis-pro.com wie gewohnt aufrufen.
2. Bei der vorgeschalteten Passwortabfrage eingeben: Benutzername `customer`, Passwort `94e_VQ3bonL3t#F`.
3. Danach erscheint die gewohnte Webseite. Für das Kundenkonto und Bestellungen meldet sich der Kunde dort noch einmal mit seinen eigenen, bisherigen Zugangsdaten an.

Die Passwortabfrage ist eine Übergangslösung. Wir stellen den Shop auf ein neues System um und gehen davon aus, dass sie nur für einige Wochen nötig ist. Ein genaues Datum nennen wir nicht.

# Kommunikation

Kurz für die Unannehmlichkeiten entschuldigen. Hat der Kunde weiterhin Schwierigkeiten beim Zugriff oder bei der Bestellung, soll er uns kontaktieren oder uns ansprechen.

# No-Gos

- Die Zugangsdaten der Passwortabfrage an Kunden anderer Kundengruppen oder Kanäle weitergeben.
- Ein Datum für das Ende der Passwortabfrage oder die Umstellung auf das neue System nennen.
