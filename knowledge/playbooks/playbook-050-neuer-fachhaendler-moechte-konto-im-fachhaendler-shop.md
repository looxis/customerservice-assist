---
id: PLAYBOOK-050
title: "Neuer Fachhändler möchte ein Konto im Fachhändler-Shop"
type: playbook
status: draft

customer_types:
  - b2b-reseller
sales_channels:
  - fachhaendler

products:
categories:
  - order-process-question
topics:
  - customer-account

summary: "Ein bisher unbekannter gewerblicher Kunde möchte ein Konto im Fachhändler-Shop fachhaendler.looxis.de eröffnen oder als Fachhändler bei LOOXIS bestellen. Anweisung an den Agenten: Angaben erfragen, bei einem gewerblichen Kunden mit ernsthaftem Interesse das Konto anlegen, bei Zweifel entscheidet die Geschäftsleitung, und dem Kunden den Zugang per „Passwort vergessen“ erklären. Gilt nicht für bestehende Händler mit Login-Problem oder deaktiviertem Konto (PLAYBOOK-032)."
retrieval: triggered
triggers:
  - Konto eröffnen
  - Konto anlegen
  - Kundenkonto anlegen
  - Kundenkonto eröffnen
  - Händlerkonto
  - Fachhändler werden
  - als Händler registrieren
  - Registrierung
  - registrieren
  - Konto im Fachhändler-Shop
  - open an account
  - create an account
  - become a reseller
  - dealer account
exclude_triggers:
when:
actions:

related_knowledge:
  - POLICY-052
  - PLAYBOOK-032
  - PROCEDURE-010
  - POLICY-041
---

Gilt für Foto-Fachhändler und Reseller im Vertriebskanal fachhaendler.

# Fallmuster

Ein bisher unbekannter Fachhändler möchte ein Konto im Fachhändler-Shop https://fachhaendler.looxis.de eröffnen. Ein Konto kann er nicht selbst anlegen, LOOXIS legt es an.

# Erkennungsmerkmale

- Der Absender ist kein bestehender Kunde (kein Konto, keine frühere Bestellung).
- Er schreibt, er sei Händler oder gewerblich tätig und möchte bei LOOXIS einkaufen oder ein Konto eröffnen.

# Zu prüfen

1. Ist es ein gewerblicher Kunde mit ernsthaftem Interesse? Das ergibt sich aus der Kommunikation mit dem Kunden.
2. Liegen alle Angaben vor: Firmenname, Rechnungsadresse, Lieferadresse, E-Mail-Adresse, USt-IdNr.? Fehlende Angaben beim Kunden erfragen.
3. Ist der Absender doch ein bestehender Händler (Konto vorhanden, deaktiviert oder nicht übernommen)? Dann gilt PLAYBOOK-032.

# Berechtigt wenn

- Der Kunde ist gewerblich und hat ernsthaftes Interesse an einer Zusammenarbeit. LOOXIS akzeptiert das in der Regel und legt ein neues Konto an.

# Nicht berechtigt wenn

- Es handelt sich nicht um einen gewerblichen Kunden oder das Interesse ist nicht ernsthaft erkennbar. Das Konto wird dann nicht angelegt.

# Noch nicht entscheidbar wenn

- Gewerblichkeit oder Ernsthaftigkeit sind aus der Kommunikation nicht sicher erkennbar. Dann entscheidet die Geschäftsleitung; dem Kunden bis dahin nichts zusagen.

# Empfohlene Maßnahme

1. Fehlende Angaben beim Kunden erfragen (siehe „Zu prüfen“). Keine Dauer für die Anlage zusagen.
2. Bei einem erkennbar gewerblichen Kunden mit ernsthaftem Interesse das Konto nach PROCEDURE-010 in EOCS anlegen. Bei Zweifel entscheidet die Geschäftsleitung.
3. Als Zahlungsart wird die Online-Zahlung per Mollie gewählt. Rechnung ist bei Neukunden immer ausgeschlossen. Lastschrift gibt es nur auf Anfrage des Kunden (POLICY-041).
4. Dem Kunden das neue Konto per E-Mail bestätigen und den Zugang erklären (siehe „Kommunikation“).

# Befugnis

Der Kundenservice legt das Konto bei einem erkennbar gewerblichen Kunden mit ernsthaftem Interesse selbst an. Bei Zweifel entscheidet die Geschäftsleitung.

# Kommunikation

Angaben erfragen: „Ein Kundenkonto im Fachhändler-Shop legen wir für Sie gerne an. Bitte nennen Sie uns dazu Ihren Firmennamen, Ihre Rechnungs- und Lieferadresse, die E-Mail-Adresse, mit der Sie sich anmelden möchten, und Ihre USt-IdNr.“

Nach Anlage des Kontos, per E-Mail: Das Konto ist angelegt. Bitte unter https://fachhaendler.looxis.de im Bereich „Login“ die Funktion „Passwort vergessen“ nutzen, dort die bei uns hinterlegte E-Mail-Adresse [E-Mail] eingeben und den Anweisungen in der E-Mail zum Zurücksetzen des Passworts folgen.

# No-Gos

- Das Konto zusagen, wenn Gewerblichkeit oder ernsthaftes Interesse unklar sind und die Geschäftsleitung noch nicht entschieden hat.
- Einem Neukunden Rechnung als Zahlungsart anbieten.
- Ein Passwort nennen oder setzen.
- Eine Dauer für die Anlage zusagen.
