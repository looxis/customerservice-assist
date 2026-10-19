---
id: PLAYBOOK-032
title: Fachhändler kann sich im neuen Shop nicht anmelden
type: playbook
status: active
customer_types:
  - b2b-reseller
sales_channels:
  - fachhaendler
products:
categories:
  - order-process-question
topics:
  - customer-account
summary: "Ein Fachhändler oder Reseller kann sich nicht im neuen Fachhändler-Shop einloggen, findet sein Konto nicht oder hat ein neues Konto erwartet. Der Agent prüft zuerst, ob er den richtigen Shop (fachhaendler.looxis.de) nutzt, dann ob das Konto übernommen und aktiviert ist; nicht übernommene Konten und neue Händler laufen über LOOXIS."
retrieval: triggered
triggers:
  - kann mich nicht einloggen
  - nicht einloggen
  - nicht mehr einloggen
  - nicht mehr anmelden
  - Konto gesperrt
  - kein Zugang mehr
  - Login funktioniert nicht
  - Anmeldung funktioniert nicht
  - Passwort vergessen
  - Zugangsdaten
  - fachhaendler.looxis.de
  - reseller.looxis.de
  - Konto aktivieren
  - Konto übernommen
  - neuer Shop
related_knowledge:
  - POLICY-052
  - POLICY-051
  - POLICY-041
  - PROCESS-015
---

Gilt für Foto-Fachhändler und Reseller im Vertriebskanal fachhaendler. Für Endverbraucher gilt PLAYBOOK-031.

# Fallmuster

Ein Fachhändler kommt nicht in den neuen Fachhändler-Shop, findet das alte Kundenkonto nicht oder möchte erstmals ein Konto.

# Typische Ursache

- Falscher Shop: Anmeldung auf www.looxis.de oder im alten Shop reseller.looxis.de statt auf fachhaendler.looxis.de.
- Konto noch nicht aktiviert (E-Mail-Verifizierung beim ersten Login fehlt).
- Passwort vergessen.
- Konto war inaktiv (keine Bestellung in den letzten 12 Monaten) und wurde nicht übernommen.
- Ab dem 3. November 2026: Das Konto wurde nicht übernommen (letzte Bestellung vor Mai 2025, keine Reaktion auf Rückfragen zum Status) oder gesperrt (übernommenes Konto trotz zweifacher Bitte per E-Mail nicht aktiviert).
- Der Händler war bisher nicht Kunde.

# Zu prüfen

1. Welchen Shop und welche Adresse nutzt der Händler?
2. Mit welcher E-Mail-Adresse meldet er sich an, ist es die bisherige?
3. War er in den letzten 12 Monaten Kunde, ist er also ein aktives, übernommenes Konto, oder war er inaktiv oder neu?

# Empfohlene Maßnahme

1. **Den richtigen Shop nennen:** fachhaendler.looxis.de, die Adresse direkt eintippen, nicht über Google suchen, als Lesezeichen speichern.
2. **Aktives, übernommenes Konto:** Login mit der bisherigen E-Mail-Adresse und dem bisherigen Passwort, beim ersten Login die E-Mail-Adresse per Link bestätigen (Aktivierung). Bei vergessenem Passwort die Funktion „Passwort vergessen“ auf der Login-Seite.
3. **Konto nicht übernommen oder gesperrt, ab dem 3. November 2026:** Dem Kunden sagen, dass sein Konto entweder nicht übernommen oder gesperrt wurde, und die Neuanlage anbieten. Dafür die Kundennummer oder die zuvor verwendete E-Mail-Adresse erfragen. Als Zahlungsart steht danach ausschließlich Mollie zur Verfügung; eine Rückkehr zur Lastschrift braucht ein neues Mandat (POLICY-041, PROCESS-015). Den Grund nur als Tatsache nennen, wenn er feststeht (letzte Bestellung, Rückfragen); sonst beide Möglichkeiten nennen.
4. **Neuer Fachhändler:** Ein Konto kann er nicht selbst anlegen, LOOXIS legt es an. Firmenname und E-Mail-Adresse erfragen. Keine Dauer zusagen.
5. **Fachhändler ist nicht mehr aktiv:** Auf Rückmeldung kann sein nicht mehr benötigtes Konto aus Sicherheitsgründen gelöscht werden.

# Kommunikation

Falscher Shop: „Für Fachhändler gibt es einen eigenen Shop: fachhaendler.looxis.de. Bitte geben Sie die Adresse direkt in die Adresszeile Ihres Browsers ein (nicht über eine Suchmaschine) und speichern Sie sie am besten als Lesezeichen. Dort melden Sie sich mit Ihrer bisherigen E-Mail-Adresse und Ihrem bisherigen Passwort an.“

Aktivierung: „Beim ersten Login im neuen Shop bestätigen Sie bitte Ihre E-Mail-Adresse. Sie erhalten eine E-Mail mit einem Link, den Sie anklicken müssen; der Shop leitet Sie Schritt für Schritt durch den Vorgang. Sollten Sie Ihr Passwort nicht mehr wissen, nutzen Sie auf der Login-Seite „Passwort vergessen“.“

Ab dem 3. November 2026, Grund offen: „Ihr Kundenkonto wurde entweder bei der Umstellung auf den neuen Shop nicht übernommen oder inzwischen gesperrt, weil es nicht aktiviert wurde. Wir legen es gerne neu für Sie an. Bitte nennen Sie uns dazu Ihre Kundennummer oder die zuvor verwendete E-Mail-Adresse. Als Zahlungsart steht Ihnen danach ausschließlich Mollie zur Verfügung.“

Nicht übernommen (Grund bekannt): „Ihr Kundenkonto wurde bei der Umstellung nicht übernommen, weil Ihre letzte Bestellung vor Mai 2025 lag und wir auf unsere Rückfragen zum Status keine Rückmeldung erhalten haben. Wir legen es gerne neu für Sie an. Bitte nennen Sie uns dazu Ihre Kundennummer oder die zuvor verwendete E-Mail-Adresse. Als Zahlungsart steht Ihnen danach ausschließlich Mollie zur Verfügung.“

Gesperrt (Grund bekannt): „Ihr übernommenes Kundenkonto wurde gesperrt, weil es trotz unserer zwei E-Mails nicht aktiviert wurde. Eine Freischaltung ist über den Kundenservice möglich: Wir legen Ihr Konto neu an. Bitte nennen Sie uns dazu Ihre Kundennummer oder die zuvor verwendete E-Mail-Adresse. Als Zahlungsart steht Ihnen danach ausschließlich Mollie zur Verfügung.“

Neuer Händler: „Ein Kundenkonto im Fachhändler-Shop legen wir für Sie an. Bitte nennen Sie uns dazu Ihren Firmennamen und die E-Mail-Adresse, mit der Sie sich anmelden möchten.“

# No-Gos

Keine Passwörter nennen oder zurücksetzen. Keine Dauer für die Anlage oder Übernahme zusagen. Nicht behaupten, ein Konto sei übernommen, ohne es zu wissen.

# Noch zu klären

- Die internen Systemschritte für Anlage, Freischaltung und Löschung eines Fachhändler-Kontos (siehe POLICY-052).
