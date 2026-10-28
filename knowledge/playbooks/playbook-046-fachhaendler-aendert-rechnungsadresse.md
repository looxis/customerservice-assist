---
id: PLAYBOOK-046
title: Fachhändler möchte die Rechnungsadresse seines Kontos ändern
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
  - invoice-recipient
summary: "Ein Foto-Fachhändler möchte die Rechnungsadresse in seinem Shop-Konto ändern (Firmenname, Anrede, Name, Straße, Hausnummer, Postleitzahl), zum Beispiel nach einem Umzug oder einer Umfirmierung. Erklärt, dass der Händler sie im Shop nicht selbst ändern kann, dass wir die Änderung vornehmen, dass sie ab der nächsten Bestellung gilt und er sie im Shop prüfen muss. Gilt nicht für die Korrektur einer bereits ausgestellten Rechnung (PLAYBOOK-017)."
retrieval: triggered
triggers:
  - Rechnungsadresse
  - Rechnungsanschrift
  - Firmenname ändern
  - Firmennamen ändern
  - Umfirmierung
  - Firmensitz
  - Rechtsform geändert
  - neue Firmenadresse
  - umgezogen
exclude_triggers:
when:
actions:
related_knowledge:
  - PLAYBOOK-017
  - POLICY-020
  - POLICY-052
  - POLICY-061
---

Gilt für Foto-Fachhändler im Vertriebskanal fachhaendler. Es geht um die Rechnungsadresse im Konto für künftige Bestellungen. Soll eine bereits ausgestellte Rechnung geändert werden, gelten PLAYBOOK-017 und POLICY-020.

# Fallmuster

Ein Händler möchte seine Rechnungsadresse ändern (Firmenname oder Geschäftsbezeichnung, Anrede, Name, Straße, Hausnummer oder Postleitzahl) oder stellt fest, dass er sie im Online-Shop nicht ändern kann.

# Zu prüfen

1. Kommt die Anfrage von der im Konto hinterlegten E-Mail-Adresse? Wenn nicht, zuerst nachfragen, bevor etwas geändert wird.
2. Welche Angaben sollen sich ändern? Die vollständige neue Rechnungsadresse erfragen, falls sie fehlt.
3. Liegt die neue Firma im EU-Ausland und hat der Händler eine Umsatzsteuer-Identifikationsnummer? Dann zuerst die Nummer nach POLICY-061 prüfen.
4. Ist die Firma eine deutsche Firma, ist kein Nachweis nötig.

# Empfohlene Maßnahme

1. Der Händler kann die Rechnungsadresse im Online-Shop nicht selbst ändern. Der Kundenservice nimmt die Änderung vor.
2. Die Änderung wird in EOCS vorgenommen und mit dem Online-Shop (Vanilo) synchronisiert.
3. Die geänderte Rechnungsadresse gilt ab der nächsten Bestellung. Eine rückwirkende Änderung ist nicht möglich: Bereits ausgestellte Rechnungen und aufgegebene Bestellungen werden nicht geändert.
4. Dem Händler die Änderung bestätigen und ihn bitten, die Rechnungsadresse im Online-Shop noch einmal zu prüfen, spätestens bevor er die nächste Bestellung abgibt oder bestätigt.

# Kommunikation

Die Bestätigung enthält immer den Hinweis, dass die Änderung ab der nächsten Bestellung gilt, und die Bitte, im Shop zu prüfen. Beispiel:

„Wir haben Ihre Rechnungsadresse wie gewünscht geändert: [neue Angaben]. Im Online-Shop können Sie die Rechnungsadresse nicht selbst ändern, dafür sind wir zuständig. Die neue Adresse gilt ab Ihrer nächsten Bestellung; bereits ausgestellte Rechnungen und aufgegebene Bestellungen ändern wir nicht. Bitte prüfen Sie im Online-Shop noch einmal, ob die Rechnungsadresse stimmt, spätestens bevor Sie Ihre nächste Bestellung abgeben oder bestätigen.“

# No-Gos

- Die Änderung vornehmen, ohne die Absenderadresse mit dem Konto abzugleichen.
- Bei einer deutschen Firma einen Nachweis verlangen.
- Eine rückwirkende Änderung bestehender Rechnungen oder Bestellungen zusagen.
- Dem Händler sagen, er könne die Rechnungsadresse im Shop selbst ändern.
- Die Änderung bestätigen, ohne ihn zur Prüfung im Shop aufzufordern.
