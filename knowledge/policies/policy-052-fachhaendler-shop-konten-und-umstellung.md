---
id: POLICY-052
title: Fachhändler-Shop fachhaendler.looxis.de – Konten und Umstellung
type: policy
status: active
customer_types:
  - b2b-reseller
sales_channels:
  - fachhaendler
products:
categories:
  - order-process-question
  - product-question
topics:
  - customer-account
summary: "Ein Fachhändler oder Reseller fragt zum Login im Fachhändler-Shop, zum alten Shop reseller.looxis.de, zu seinem Kundenkonto oder zur Umstellung im Mai 2026. Erklärt: Anmeldung nur unter fachhaendler.looxis.de, aktive Konten (Bestellung in den letzten 12 Monaten) wurden samt Passwort übernommen, nicht aktivierte Konten wurden am 2. November 2026 gesperrt, inaktive und unbekannte Händler laufen über LOOXIS."
retrieval: triggered
triggers:
  - einloggen
  - einzuloggen
  - eingeloggt
  - Login
  - anmelden
  - anzumelden
  - Anmeldung
  - fachhaendler.looxis.de
  - reseller.looxis.de
  - Fachhändler-Shop
  - Händlershop
  - B2B-Shop
  - neuer Shop
  - alter Shop
  - Konto aktivieren
  - Konto übernommen
  - Konto freischalten
  - Konto gesperrt
  - Konto ist gesperrt
  - Konto wurde gesperrt
  - Kontosperrung
  - Konto gelöscht
  - Konto deaktiviert
  - Konto reaktivieren
  - Zugangsdaten
related_knowledge:
  - PLAYBOOK-032
  - POLICY-051
  - POLICY-043
  - POLICY-041
  - POLICY-042
  - PROCEDURE-007
---

Gilt für Foto-Fachhändler und Reseller im Vertriebskanal fachhaendler.

# Regel

Der Fachhändler-Shop läuft auf derselben Software-Plattform wie der Endverbraucher-Shop, mit diesem Unterschied: Fachhändler melden sich unter **fachhaendler.looxis.de** an, nicht unter www.looxis.de. Das ist ein häufiger Fehler, zum Beispiel wenn über eine Suchmaschine nach „looxis“ gesucht wurde. Besser die Adresse direkt in die Adresszeile eingeben und als Lesezeichen speichern.

# Zeitlicher Ablauf

- fachhaendler.looxis.de ist seit Mai 2026 aktiv. Die Kundendaten wurden am 16. Mai 2026 aus dem alten Shop übernommen, am 19. Mai wurden die Händler per E-Mail informiert.
- Der alte Shop war unter reseller.looxis.de erreichbar und ist seit dem 31. Mai 2026 nach einer einmonatigen Übergangsphase deaktiviert.

# Konten

- Übernommen wurden alle zu diesem Zeitpunkt aktiven Kundenkonten. Aktiv bedeutet: mindestens eine Bestellung in den letzten 12 Monaten. Die E-Mail-Adresse und das Passwort (verschlüsselt übernommen) gelten weiter.
- Inaktive Kunden wurden per Newsletter informiert, dass ihr Konto ebenfalls übernommen wird, wenn sie das durch eine Antwort auf den Newsletter wünschen.
- Wurde ein Konto nicht übernommen, legt LOOXIS es neu an (siehe unten). Benötigt werden die Kundennummer oder die zuvor verwendete E-Mail-Adresse; als Zahlungsart steht ausschließlich Mollie zur Verfügung, eine Rückkehr zur Lastschrift braucht ein neues Mandat.
- Ein gesperrtes (deaktiviertes) Konto schaltet LOOXIS nach Rücksprache mit dem Kunden wieder frei, es wird nicht neu angelegt (siehe „Reaktivierung deaktivierter Konten und Zahlungsarten“).
- Ein uns bisher unbekannter Fachhändler kann sich kein Kundenkonto selbst anlegen. Das läuft über LOOXIS.

# Kontostatus im Shop

Im Vanilo-Backend ist ein Kundenkonto aktiv oder deaktiviert:

| Status | Anmeldung | Bestellung | „Passwort vergessen“ |
|---|---|---|---|
| Aktiv | ja | ja | ja |
| Deaktiviert | nein | nein | nein |

„Deaktiviert“ entsteht durch einen Mitarbeiter des Kundenservice oder durch die Umstellung: Am 2. November 2026 wurden alle noch unverifizierten (nicht aktivierten) Konten auf „deaktiviert“ gesetzt.

# Aktivierung

Bis zum 2. November 2026 mussten übernommene Konten per E-Mail-Bestätigung aktiviert werden; nicht aktivierte Konten wurden am 2. November 2026 gesperrt. „Passwort vergessen“ ist über den Link auf der Login-Seite erreichbar.

# Kontosperrung am 2. November 2026

Am 19. Mai 2026 wurden die Händler über den neuen Shop informiert. Händler, deren übernommenes Konto noch nicht aktiviert war, haben am 19. Oktober 2026 eine zweite Mail bekommen. Darin stand:

- Wer weiterhin als Fachhändler aktiv ist, soll das Konto aktivieren und Rechnungs- und Lieferadresse prüfen. Änderungen der Rechnungsadresse per E-Mail mitteilen.
- Login-Probleme melden an hello@looxis.com. Während des Weihnachtsgeschäfts können solche Probleme weiterhin geklärt werden, die Bearbeitung kann dann aber deutlich länger dauern.
- Wer nicht mehr als Fachhändler aktiv ist, meldet das kurz per E-Mail, damit das nicht mehr benötigte Konto aus Sicherheitsgründen gelöscht werden kann.

Alle bis zum 2. November 2026 nicht aktivierten Konten wurden an diesem Tag aus Sicherheitsgründen gesperrt. Damit sind auch bisher vereinbarte Zahlungsmodalitäten verfallen. Eine Freischaltung ist ausschließlich über den Kundenservice möglich.

Seit der Sperrung gilt: Ein Login-Problem allein ist kein Grund, von einem gesperrten oder nicht übernommenen Konto zu sprechen. Zuerst gilt die Shop-Prüfung aus PLAYBOOK-032. Nicht übernommene oder gesperrte Konten betreffen nur Händler, die zuvor lange nicht aktiv waren, und das wird dem Händler nur gesagt, wenn die Prüfung in Vanilo es bestätigt (PLAYBOOK-032):

- **Nicht übernommen:** Er hat zuletzt vor Mai 2025 bestellt und auf Rückfragen zum Status (ob er noch aktiv ist) nicht reagiert.
- **Gesperrt:** Er hat sein übernommenes Konto trotz zweifacher Bitte per E-Mail nicht aktiviert.

Ein nicht übernommenes Konto legt LOOXIS neu an. Dafür werden die Kundennummer oder die zuvor verwendete E-Mail-Adresse benötigt. Als Zahlungsart steht danach ausschließlich Mollie zur Verfügung. Eine Rückkehr zur Lastschrift braucht nach der Neuanlage ein neues Lastschriftmandat (POLICY-041, PROCESS-015). Ein gesperrtes (deaktiviertes) Konto wird nicht neu angelegt, sondern nach Rücksprache mit dem Kunden wieder freigeschaltet (nächster Abschnitt).

# Reaktivierung deaktivierter Konten und Zahlungsarten

Ein deaktiviertes Konto wird nur nach Rücksprache mit dem Kunden wieder aktiviert (Arbeitsschritte: PROCEDURE-007). Die Reaktivierung bedeutet nicht, dass frühere Zahlungsvereinbarungen weitergelten.

- Standard nach der Reaktivierung: ausschließlich Mollie.
- Lastschrift nur, wenn der Kunde sie ausdrücklich verlangt, und dann nur nach Freigabe durch die Geschäftsleitung (POLICY-041, PROCESS-015).
- Rechnung wird reaktivierten Kunden nicht mehr angeboten.
- Eine Zahlungsart gilt erst, wenn sie in EOCS (Standard und Vertriebskanal fachhaendler.looxis.de) geändert und mit dem Shop synchronisiert ist (POLICY-042).
- Der Kunde wird informiert, dass das Konto wieder aktiv ist, welche Zahlungsart ab der nächsten Bestellung gilt und dass frühere Vereinbarungen nicht automatisch gelten.

# Hinweise aus der Mail vom 19. Mai 2026

- Zugangsdaten und Zahlungsbedingungen bleiben grundsätzlich unverändert.
- Die übernommene Liefer- und Rechnungsadresse vor der nächsten Bestellung prüfen. Für nicht zustellbare Pakete wegen fehlerhafter oder veralteter Adressen übernimmt LOOXIS keine Haftung.
- Die Rechnungsadresse kann der Kunde nicht selbst ändern; Änderungen per E-Mail an hello@looxis.com.
- 3D-Glasfotos im Quader: Hochformat und Querformat sind jeweils eigene Artikel in der Kategorie 3D Glasfotos.
- Bereits eingegangene Bestellungen können nicht geändert oder storniert werden. Fragen bitte vor der Bestellung per E-Mail klären.
- Ab dem 1. Juni 2026 gelten neue Vorzugspreise mit vereinfachter Margenstruktur und neuen unverbindlichen Preisempfehlungen.

# Grenzen

- Keine Dauer und keine Voraussetzungen für die Anlage oder Übernahme eines Kontos zusagen (die Schritte stehen unten als offen).
- Passwörter weder einsehen noch ändern.
- Keine Preise der neuen Preisliste nennen, die nicht vorliegen.

# Noch zu klären

- Die internen Systemschritte, mit denen LOOXIS ein Fachhändler-Konto neu anlegt oder löscht (die Reaktivierung steht in PROCEDURE-007). Welche Angaben ein bisher unbekannter Händler zusätzlich braucht, ist nicht festgelegt.
