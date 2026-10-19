---
id: PLAYBOOK-031
title: Kunde kann sich im Shop looxis.de nicht anmelden
type: playbook
status: active
customer_types:
  - b2c
sales_channels:
  - looxis-de
products:
categories:
  - order-process-question
  - product-question
topics:
  - customer-account
summary: "Ein Endverbraucher kann sich im Shop looxis.de nicht einloggen, hat sein Passwort vergessen oder findet sein altes Konto nicht mehr. Der Agent sucht das Konto per E-Mail-Adresse im Shop-Backend; ist es vorhanden, hilft „Passwort vergessen“; ein Konto von vor dem 03.06.2024 gibt es nicht mehr."
retrieval: triggered
triggers:
  - kann mich nicht einloggen
  - nicht einloggen
  - nicht mehr einloggen
  - nicht mehr anmelden
  - Konto gesperrt
  - Konto ist gesperrt
  - Konto wurde gesperrt
  - kein Zugang mehr
  - Login funktioniert nicht
  - Anmeldung funktioniert nicht
  - Passwort vergessen
  - neues Passwort
  - Passwort zurücksetzen
  - Passwort ändern
  - Zugangsdaten
  - Konto nicht gefunden
  - mein altes Konto
related_knowledge:
  - POLICY-051
  - POLICY-052
---

Gilt für Endverbraucher im Shop looxis.de. Für Fachhändler gilt PLAYBOOK-032.

# Fallmuster

Der Kunde kommt nicht in sein Kundenkonto, weiß sein Passwort nicht mehr oder sucht sein altes Konto.

# Typische Ursache

- Passwort vergessen.
- Das Konto stammt aus der Zeit vor dem 03.06.2024 und wurde bei der Umstellung nicht übernommen (POLICY-051).
- Das Konto wurde nie aktiviert (weder durch eine Bestellung noch durch den Klick auf den Link in der Bestätigungsmail).
- Falsche E-Mail-Adresse.

# Zu prüfen

1. Mit welcher E-Mail-Adresse versucht der Kunde sich anzumelden?
2. Gibt es zu dieser Adresse ein Kundenkonto im Backend des Online-Shops? Der Agent kann es anhand der E-Mail-Adresse dort finden.

# Empfohlene Maßnahme

- **Konto gefunden:** Dem Kunden erklären, wie er sich selbst ein neues Passwort vergibt (siehe Kommunikation). Passwörter können wir weder einsehen noch ändern.
- **Kein Konto gefunden:** Wahrscheinlich stammt das Konto aus der Zeit vor dem 03.06.2024 oder die Adresse stimmt nicht. Die Umstellung erklären und ein neues Konto anbieten (5-Euro-Willkommensgutschein) oder die Bestellung als Gast (POLICY-051).
- **Konto vorhanden, aber nie aktiviert:** Den Kunden auf den Link in der Bestätigungsmail hinweisen.

# Kommunikation

Passwort vergessen: „Sie können sich ein neues Passwort selbst vergeben: Klicken Sie beim Login auf „Passwort vergessen“ oder rufen Sie https://looxis.de/u/password/forgot auf, geben Sie Ihre E-Mail-Adresse ein und klicken Sie auf „neues Passwort anfordern“. Sie erhalten dann eine E-Mail mit einem Link; klicken Sie darauf, vergeben Sie ein neues Passwort, und schon sind Sie fertig.“

Kein Konto: „Am 3. Juni 2024 haben wir unseren Shop auf eine neue Plattform umgestellt. Kundenkonten wurden dabei nicht übernommen. Zu der von Ihnen genannten E-Mail-Adresse finde ich kein Kundenkonto. Sie können sich gerne ein neues Konto anlegen und erhalten dafür einen Willkommensgutschein über 5 Euro, oder Sie bestellen ohne Konto als Gast.“

# No-Gos

Keine Passwörter nennen, einsehen oder zurücksetzen. Nicht behaupten, ein Konto existiere oder sei übernommen, ohne es im Backend geprüft zu haben. Keine Bedingungen des Gutscheins erfinden.
