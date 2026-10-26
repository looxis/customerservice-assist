---
id: PLAYBOOK-032
title: Fachhändler kann sich im neuen Shop nicht anmelden
type: playbook
status: active
trial_until: 2026-11-02
trial_goal: "Nach dem 2. November 2026 die Hinweise zur Aktivierung und zur Frist bereinigen und prüfen, wie viele Konten gesperrt, neu angelegt oder gelöscht wurden."
customer_types:
  - b2b-reseller
sales_channels:
  - fachhaendler
products:
categories:
  - order-process-question
topics:
  - customer-account
summary: "Ein Fachhändler oder Reseller kann sich nicht im Fachhändler-Shop anmelden, sein Passwort wird nicht akzeptiert, beim Zurücksetzen erscheint „E-Mail nicht bekannt“ oder er findet sein Konto nicht. Die Antwort bittet zuerst zu prüfen, ob fachhaendler.looxis.de mit dem Logo „looxis reseller“ geöffnet ist, erklärt die Anmeldung und bittet bei weiterem Fehler um Screenshot und E-Mail-Adresse; den Kontostatus (aktiv, unverifiziert, deaktiviert) prüft der Kundenservice danach in Vanilo. Nicht übernommene Konten und neue Händler laufen über LOOXIS."
retrieval: triggered
triggers:
  - kann mich nicht einloggen
  - nicht einloggen
  - nicht mehr einloggen
  - nicht mehr anmelden
  - Konto gesperrt
  - Konto ist gesperrt
  - Konto wurde gesperrt
  - Konto deaktiviert
  - kein Zugang mehr
  - Login funktioniert nicht
  - Anmeldung funktioniert nicht
  - Passwort vergessen
  - Passwort nicht akzeptiert
  - Passwort wird nicht akzeptiert
  - E-Mail nicht bekannt
  - E-Mail-Adresse nicht bekannt
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
  - PROCEDURE-007
  - PROCEDURE-008
---

Gilt für Foto-Fachhändler und Reseller im Vertriebskanal fachhaendler. Für Endverbraucher gilt PLAYBOOK-031.

# Fallmuster

Ein Fachhändler kommt nicht in den Fachhändler-Shop: Anmeldung oder Passwort werden nicht akzeptiert, „Passwort vergessen“ meldet „E-Mail nicht bekannt“, das alte Kundenkonto ist nicht zu finden, oder er möchte erstmals ein Konto.

# Typische Ursache

- Falscher Shop: Anmeldung auf www.looxis.de oder im alten Shop reseller.looxis.de statt auf fachhaendler.looxis.de.
- Passwort vergessen oder falsche E-Mail-Adresse.
- Konto unverifiziert (der Link der Bestätigungsmail wurde nie angeklickt): Anmeldung und Bestellung sind möglich, „Passwort vergessen“ funktioniert nicht. Gilt bis zum 2. November 2026, danach werden unverifizierte Konten deaktiviert.
- Konto deaktiviert (nur durch einen Mitarbeiter des Kundenservice, bei der Umstellung ab dem 3. November 2026 auch für nicht aktivierte Konten): Weder Anmeldung noch Bestellung noch „Passwort vergessen“ sind möglich. Auf „Passwort vergessen“ kann dann „E-Mail nicht bekannt“ erscheinen.
- Konto war inaktiv (keine Bestellung in den letzten 12 Monaten) und wurde nicht übernommen.
- Der Händler war bisher nicht Kunde.

# Zu prüfen

1. Ruft der Händler wirklich fachhaendler.looxis.de auf, und sieht er oben links das Logo „looxis reseller“? Das ist **immer** die erste Frage, bevor am Konto etwas geprüft wird.
2. Mit welcher E-Mail-Adresse meldet er sich an?
3. Hat er schon im neuen Shop bestellt (Bestellung im Kanal fachhaendler.looxis.de, im Fall sichtbar oder vom Mitarbeiter genannt)? Dann ist die Aktivierung nicht die Ursache.
4. Erst wenn der Händler Screenshot und E-Mail-Adresse geschickt hat: Kontostatus in Vanilo prüfen (aktiv, unverifiziert, deaktiviert, kein Konto). Das macht der Kundenservice intern.

# Empfohlene Maßnahme

1. **Erste Antwort, immer:** Shop-Adresse und Logo nennen, die Anmeldung mit E-Mail-Adresse und Passwort oder „Passwort vergessen“ erklären und bei Fehlern um Screenshot und die verwendete E-Mail-Adresse bitten. Keine Aussage zum Kontostatus, solange er nicht geprüft ist.
2. **Hat der Händler den neuen Shop noch nie benutzt** (keine Bestellung im neuen Shop, oder der Mitarbeiter nennt das): zusätzlich erklären, dass beim ersten Login die E-Mail-Adresse per Link bestätigt werden muss. **Sonst entfällt dieser Absatz**, auch wenn unklar ist, ob der Händler den Shop schon benutzt hat.
3. **Kontostatus nach Screenshot und E-Mail-Adresse:**
   - **Aktiv:** Ursache beim Händler suchen (Adresse, Tippfehler, Passwort); „Passwort vergessen“ erklären.
   - **Unverifiziert (bis 2. November 2026):** Der Kundenservice verifiziert das Konto für den Händler (PROCEDURE-008). Danach funktioniert „Passwort vergessen“, und der Händler kann sich ein neues Passwort vergeben.
   - **Deaktiviert:** Dem Händler sagen, dass das Konto deaktiviert ist, und klären, ob er es weiter nutzen möchte. Wenn ja, nach PROCEDURE-007 wieder aktivieren. Zahlungsarten nach POLICY-052: ab der nächsten Bestellung steht Mollie zur Verfügung, frühere Zahlungsvereinbarungen gelten nicht automatisch weiter; Rechnung wird nicht mehr angeboten; Lastschrift nur, wenn der Händler sie ausdrücklich verlangt, und dann nur nach Freigabe der Geschäftsleitung (POLICY-041).
   - **Kein Konto gefunden oder nicht übernommen (ab 3. November 2026):** Neuanlage anbieten. Dafür die Kundennummer oder die zuvor verwendete E-Mail-Adresse erfragen. Als Zahlungsart steht danach ausschließlich Mollie zur Verfügung (POLICY-041, PROCESS-015). Den Grund nur nennen, wenn er feststeht.
4. **Neuer Fachhändler:** Ein Konto kann er nicht selbst anlegen, LOOXIS legt es an. Firmenname und E-Mail-Adresse erfragen. Keine Dauer zusagen.
5. **Fachhändler ist nicht mehr aktiv:** Auf Rückmeldung kann sein nicht mehr benötigtes Konto aus Sicherheitsgründen gelöscht werden.

# Kommunikation

Erste Antwort: „Für Fachhändler gibt es einen eigenen Shop: https://fachhaendler.looxis.de. Bitte geben Sie die Adresse direkt in die Adresszeile Ihres Browsers ein (nicht über eine Suchmaschine) und speichern Sie sie am besten als Lesezeichen. Prüfen Sie bitte, dass oben links das Logo „looxis reseller“ zu sehen ist. Dort melden Sie sich mit Ihrer E-Mail-Adresse und Ihrem Passwort an; wenn Sie Ihr Passwort nicht mehr wissen, nutzen Sie auf der Login-Seite „Passwort vergessen“. Sollte es nicht klappen, schicken Sie uns bitte einen Screenshot der Fehlermeldung und teilen Sie uns die E-Mail-Adresse mit, mit der Sie sich anmelden. Wir prüfen dann Ihr Konto.“

Zusätzlich, nur wenn der Händler den neuen Shop noch nie benutzt hat: „Beim ersten Login im neuen Shop bestätigen Sie bitte Ihre E-Mail-Adresse. Sie erhalten eine E-Mail mit einem Link, den Sie anklicken müssen.“

Konto unverifiziert, nach der Verifizierung durch den Kundenservice: „Wir haben Ihr Kundenkonto bestätigt. Sie können sich jetzt über „Passwort vergessen“ auf der Login-Seite ein neues Passwort vergeben.“

Konto deaktiviert: „Ihr Kundenkonto ist derzeit deaktiviert. Deshalb sind Anmeldung und „Passwort vergessen“ nicht möglich. Möchten Sie Ihr Konto weiter nutzen? Dann schalten wir es gerne wieder frei. Bitte beachten Sie: Frühere Zahlungsvereinbarungen gelten dann nicht automatisch weiter. Ab Ihrer nächsten Bestellung steht Ihnen Mollie als Zahlungsart zur Verfügung.“

Nicht übernommen oder kein Konto, ab 3. November 2026: „Ihr Kundenkonto wurde bei der Umstellung auf den neuen Shop nicht übernommen. Wir legen es gerne neu für Sie an. Bitte nennen Sie uns dazu Ihre Kundennummer oder die zuvor verwendete E-Mail-Adresse. Als Zahlungsart steht Ihnen danach ausschließlich Mollie zur Verfügung.“

Neuer Händler: „Ein Kundenkonto im Fachhändler-Shop legen wir für Sie an. Bitte nennen Sie uns dazu Ihren Firmennamen und die E-Mail-Adresse, mit der Sie sich anmelden möchten.“

# No-Gos

Keine Passwörter nennen oder zurücksetzen. Keine Dauer für die Anlage oder Übernahme zusagen. Nicht behaupten, ein Konto sei übernommen, aktiv oder deaktiviert, ohne es zu wissen. Den Aktivierungsabsatz („erster Login“) nicht schreiben, wenn der Händler den neuen Shop schon benutzt hat oder das unklar ist. Keine Lastschrift anbieten und keine Rechnung anbieten.
