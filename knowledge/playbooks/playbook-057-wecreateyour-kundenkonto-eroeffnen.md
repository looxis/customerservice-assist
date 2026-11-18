---
id: PLAYBOOK-057
title: "WeCreateYour: Kunde möchte ein Kundenkonto eröffnen"
type: playbook
status: active

customer_types:
  - b2b-digital
sales_channels:
  - wecreateyour

products:
exclude_products:
categories:
  - order-process-question
topics:
  - customer-account

summary: "Ein Geschäftskunde möchte bei WeCreateYour ein Kundenkonto eröffnen oder sich registrieren. Bis 30.06.2027 legt LOOXIS das Konto für den Kunden an: Bestandskunden (z. B. aus LOOXIS Pro) nennen ihre Kundennummer, Neukunden Firmenname mit Rechtsform, Adresse des Geschäftssitzes, Login-E-Mail und bei Sitz in der EU außerhalb Deutschlands die USt-IdNr. Der Agent erfragt fehlende Angaben, legt das Konto an und teilt das Passwort mit."
retrieval: triggered
triggers:
  - Konto eröffnen
  - Konto anlegen
  - Konto erstellen
  - Kundenkonto
  - Account anlegen
  - Account erstellen
  - registrieren
  - Registrierung
  - anmelden möchte
  - Zugang einrichten
  - open an account
  - create an account
  - sign up
  - register
exclude_triggers:
when:
actions:
priority:
risk_level:

owner:
reviewed_by:
last_reviewed:
trial_until: 2027-06-30
trial_goal: "Befristete Regelung: Bis 30.06.2027 legt LOOXIS die Konten an; danach entscheidet der Autor, ob sie verlängert oder durch eine Selbstregistrierung ersetzt wird."

related_knowledge:
  - POLICY-077
  - PLAYBOOK-056
---

Gilt nur für Geschäftskunden der Serviceplattform WeCreateYour (Kanal WeCreateYour). Gültig bis 30.06.2027.

# Fallmuster

Ein Kunde möchte bei WeCreateYour ein Kundenkonto eröffnen. Aktuell (gültig bis 30.06.2027) kann er das nicht selbst tun; LOOXIS legt das Kundenkonto für den Kunden an.

# Erkennungsmerkmale

- Der Kunde möchte ein Konto eröffnen, sich registrieren oder einen Zugang erhalten.

# Typische Ursache

Eine Selbstregistrierung gibt es bis 30.06.2027 nicht.

# Zu prüfen

1. Ist der Kunde Bestandskunde (z. B. aus LOOXIS Pro)? Dann wird nur seine Kundennummer benötigt.
2. Ist der Kunde Neukunde? Dann werden benötigt:
   - Firmenname (inklusive Rechtsform),
   - bei Geschäftssitz innerhalb der EU und außerhalb Deutschlands: gültige Umsatzsteuer-Identifikationsnummer,
   - vollständige Adresse des Geschäftssitzes,
   - E-Mail-Adresse für den Login.
3. Fehlende Angaben beim Kunden erfragen.

# Berechtigt wenn

- Bestandskunde: Die Kundennummer liegt vor.
- Neukunde: Alle Angaben unter „Zu prüfen“ liegen vor.

# Nicht berechtigt wenn

- Entfällt; liegen die Angaben vor, wird das Konto angelegt.

# Noch nicht entscheidbar wenn

- Angaben fehlen (Bestandskunde: Kundennummer; Neukunde: eine der Angaben unter „Zu prüfen“). Dann die fehlenden Angaben beim Kunden erfragen.

# Fehlende Informationen

Bestandskunde: Kundennummer. Neukunde: Firmenname mit Rechtsform, vollständige Adresse des Geschäftssitzes, E-Mail-Adresse für den Login und bei Geschäftssitz in der EU außerhalb Deutschlands eine gültige USt-IdNr.

# Empfohlene Maßnahme

1. Fehlende Angaben beim Kunden erfragen.
2. Liegen alle Angaben vor, legt der Kundenservice das Kundenkonto an und vergibt dabei ein Passwort.
3. Dem Kunden in der Antwort mitteilen, dass das Konto angelegt ist, und ihm die Login-E-Mail-Adresse und das Passwort im Klartext nennen. Das Passwort wird nirgends gespeichert.
4. Dem Kunden empfehlen, sich nach dem ersten Login selbst ein neues Passwort zu vergeben. Das ist keine Pflicht.
5. Bei einem Neukunden die laufende Aktion erwähnen (POLICY-077).

# Befugnis

Der Kundenservice legt das Konto selbst an, sobald alle Angaben vorliegen.

# Kommunikation

Angaben erfragen (Neukunde): „Ihr Kundenkonto bei WeCreateYour legen wir sehr gerne für Sie an. Bitte nennen Sie uns dazu Ihren Firmennamen inklusive Rechtsform, die vollständige Adresse Ihres Geschäftssitzes und die E-Mail-Adresse, mit der Sie sich anmelden möchten. Hat Ihr Unternehmen seinen Sitz innerhalb der EU, aber außerhalb Deutschlands, benötigen wir zusätzlich Ihre gültige Umsatzsteuer-Identifikationsnummer.“

Angaben erfragen (Bestandskunde): „Ihr Kundenkonto bei WeCreateYour legen wir sehr gerne für Sie an. Bitte nennen Sie uns dazu Ihre Kundennummer.“

Nach Anlage des Kontos: „Ihr Kundenkonto ist angelegt. Sie können sich ab sofort mit der E-Mail-Adresse [E-Mail] und dem Passwort [PASSWORT] anmelden. Wir empfehlen Ihnen, nach dem ersten Login selbst ein neues Passwort zu vergeben.“

# No-Gos

- Ein Passwort erfinden; im Entwurf steht der Platzhalter [PASSWORT], das Passwort setzt der Mitarbeiter ein.
- Das Passwort an anderer Stelle speichern oder notieren.
- Den Passwortwechsel als Pflicht darstellen.
- Auf eine Selbstregistrierung verweisen.

# Beispiel

Ein Unternehmen mit Sitz in Österreich möchte ein Konto eröffnen und nennt Firmenname und E-Mail-Adresse. Der Kundenservice erfragt Rechtsform, vollständige Adresse des Geschäftssitzes und die USt-IdNr. und legt das Konto an, sobald alles vorliegt.
