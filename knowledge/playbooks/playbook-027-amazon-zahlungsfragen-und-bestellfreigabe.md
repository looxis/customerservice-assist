---
id: PLAYBOOK-027
title: Amazon-Kunde fragt zu Zahlung, Bestellfreigabe oder Erstattungseingang
type: playbook
status: draft
customer_types:
  - b2c
sales_channels:
  - amazon
products:
categories:
  - complaint
  - order-process-question
topics:
  - payment-processing
  - order-release
summary: "Ein Amazon-Kunde fragt zu Zahlung, Zahlungsart oder Zahlungsmethode (z. B. wiederholte Aufforderung von Amazon, sie zu erneuern), fragt nach Lieferung trotz Zahlungsproblem oder wartet nach einer Erstattung auf sein Geld. Ordnet die Anfrage Amazon oder den eigenen Bearbeitungsschritten von LOOXIS zu und liefert passende Formulierungen. Wird die Amazon-Bestellnummer in EOCS nicht gefunden, gilt die Bestellung als noch nicht freigegeben: ohne interne Klärung direkt antworten."
retrieval: triggered
triggers:
  - Zahlung
  - Zahlungsmethode
  - Zahlungsart
  - Bezahlung
  - bezahlt
  - bezahlen
  - Zahlungsaufforderung
  - Zahlungsproblem
  - Zahlung fehlgeschlagen
  - Zahlung abgelehnt
  - Zahlung ausstehend
  - Geld abgebucht
  - Erstattung nicht angekommen
  - Rückerstattung nicht erhalten
  - Geld noch nicht
  - wann bekomme ich mein Geld
  - wann wird geliefert
  - payment revision
  - update payment
  - payment
  - paiement
  - pagamento
  - refund not received
related_knowledge:
  - POLICY-039
  - POLICY-040
---

Gilt für Privatkunden im Vertriebskanal Amazon, produktübergreifend.

# Fallmuster

Der Kunde erhält wiederholt eine Aufforderung von Amazon, die Zahlungsmethode zu erneuern, und möchte wissen, was er tun soll oder ob LOOXIS die Bestellung liefert. Weitere Fälle sind Fragen zur Zahlung oder zum Kontoeingang einer von LOOXIS genehmigten oder veranlassten Erstattung.

# Zu prüfen

1. Welches Anliegen liegt vor: Zahlungsabwicklung, Bestelleingang, Produktion/Versand oder Erstattungseingang?
2. Ist die Bestellung bereits bei LOOXIS eingegangen? Welcher eigene Bearbeitungs- oder Versandstand ist belegt? Die App fragt die Bestellung in EOCS selbst ab; eine manuelle Prüfung ist nur nötig, wenn sich der Eingang daraus nicht ableiten lässt.
3. Bei einer Erstattung: Ist nur die Genehmigung oder bereits die Veranlassung durch LOOXIS belegt?

# Bestellung in EOCS nicht gefunden

Wird die Amazon-Bestellnummer des Kunden (gültiges Amazon-Format wie 306-8770924-4339565) in EOCS nicht gefunden, hat Amazon die Bestellung noch nicht freigegeben und nicht an LOOXIS übermittelt (siehe POLICY-040). Das ist bei Zahlungsproblemen der Normalfall und kein offener Punkt.

Dann ist **keine interne Klärung nötig**: Direkt antworten, mit der Vorlage „Nach Prüfung: Bestellung noch nicht bei LOOXIS eingegangen“ und dem Verweis auf Amazon für die Zahlungsaufforderung. Auch eine automatische Notiz im Ticket wie „WISMO erkannt. Bestellnummer … wurde in EOCS nicht gefunden. Bitte manuell prüfen.“ ist in diesem Fall kein Grund für eine Klärung. Die Bestellung dabei nicht als storniert oder unbezahlt bezeichnen (siehe POLICY-040).

Ist die Bestellnummer nicht erkennbar oder kein gültiges Amazon-Format (möglicher Tippfehler), nach der Amazon-Bestellnummer fragen.

# Noch nicht entscheidbar wenn

Ein Erstattungsvorgang noch nicht geprüft ist, oder eine in EOCS gefundene Bestellung ohne belegten Bearbeitungsstand vorliegt. Diese Information wird intern anhand der vorhandenen Bestell- und Vorgangsdaten geprüft. Fehlt für die Zuordnung die Amazon-Bestellnummer, kommt sie vom Kunden; gezielt fragen: „Bitte teilen Sie uns Ihre Amazon-Bestellnummer mit.“

Die konkrete Ursache einer Amazon-Zahlungsaufforderung kann LOOXIS auch nach Zuordnung der Bestellung nicht feststellen. Für diese Klärung ist Amazon zuständig; siehe POLICY-039.

# Empfohlene Maßnahme

Die Zahlungsfrage nach POLICY-039 an Amazon verweisen und den belegten eigenen Bearbeitungsstand ergänzen. Bei einer noch nicht eingegangenen Bestellung den Freigabeablauf nach POLICY-040 erklären. Eine bereits eingegangene Bestellung anhand ihres tatsächlichen Bearbeitungsstands beantworten.

Bei Erstattungen nur den nachgewiesenen eigenen Stand nennen. Der Eingang auf dem Kundenkonto bleibt eine Frage der Zahlungsabwicklung nach POLICY-039.

# Kommunikation

Wiederholte Zahlungsaufforderung:

„Bitte wenden Sie sich zur wiederholten Aufforderung, Ihre Zahlungsmethode zu erneuern, direkt an den Amazon-Kundenservice. Amazon wickelt die Zahlung Ihrer Amazon-Bestellung ab. Wir können Ihre Zahlungsmethode und Ihren Zahlungsstatus nicht einsehen oder ändern. Amazon kann mit Ihnen klären, welche Schritte erforderlich sind.“

Nach Prüfung: Bestellung noch nicht bei LOOXIS eingegangen:

„Ihre Bestellung liegt uns derzeit noch nicht zur Bearbeitung vor. Amazon hält Bestellungen zunächst mindestens 30 Minuten zurück; einzelne Bestellungen werden erst später freigegeben. Den konkreten Grund können wir nicht einsehen. Bitte klären Sie eine Zahlungsaufforderung direkt mit Amazon. Sobald die Bestellung freigegeben und bei uns eingegangen ist, können wir sie bearbeiten. Die Reihenfolge richtet sich nach dem Eingang bei uns.“

Nach Prüfung: Bestellung bei LOOXIS eingegangen:

„Ihre Bestellung ist bei uns zur Bearbeitung eingegangen.“ Anschließend den belegten Bearbeitungs- oder Versandstand nennen. „Die Zahlungsaufforderung klären Sie bitte direkt mit Amazon. Wir können Ihren Zahlungsstatus bei Amazon nicht einsehen oder verändern.“

Erstattung nur genehmigt:

„Wir haben die Erstattung genehmigt. Damit ist der Eingang auf Ihrem Konto noch nicht bestätigt.“ Den belegten weiteren eigenen Bearbeitungsstand ergänzen. „Die Zahlungsabwicklung erfolgt über Amazon. Für Fragen zum Eingang auf Ihrem Konto wenden Sie sich bitte an den Amazon-Kundenservice.“

Erstattung nachweislich veranlasst:

„Wir haben die Erstattung veranlasst. Die Auszahlung erfolgt über Amazon. Wir können nicht einsehen oder beeinflussen, wann der Betrag auf Ihrem Konto eingeht. Bitte wenden Sie sich zur Klärung des Zahlungseingangs an den Amazon-Kundenservice.“

# No-Gos

Keine Ursache einer Zahlungsaufforderung erfinden und keine bereits erfolgreiche Zahlung oder einen Kontoeingang aus internen Bestell- oder Erstattungsdaten ableiten. Die Grenzen für Zahlungsangaben stehen in POLICY-039; die Grenzen für Freigabe und Bearbeitungsreihenfolge in POLICY-040.

