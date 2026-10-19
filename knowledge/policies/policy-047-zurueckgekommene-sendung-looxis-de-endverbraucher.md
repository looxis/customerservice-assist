---
id: POLICY-047
title: Zurückgekommene Sendung bei Endverbrauchern auf looxis.de
type: policy
status: active
customer_types:
  - b2c
sales_channels:
  - looxis-de
products:
categories:
  - complaint
  - order-process-question
topics:
  - delivery-failure
  - goodwill
summary: "Eine Sendung eines Endverbrauchers aus dem LOOXIS-Shop ist vom Paketdienst zurückgekommen oder der Kunde möchte erneute Zustellung. Grundsätzlich kostenpflichtiger Neuversand; ab 100 Euro Bestellwert und mit nachvollziehbarer Erklärung, warum der Kunde die Zustellung unverschuldet nicht erhalten konnte, kann der Agent einen kostenfreien Neuversand anbieten."
retrieval: binding
triggers:
  - unzustellbar
  - nicht zustellbar
  - Zustellung fehlgeschlagen
  - nicht zugestellt
  - Zustellversuch
  - nicht angetroffen
  - Retour an Absender
  - zurück an den Absender
  - Paket zurückgegangen
  - Paket ist zurück
  - erneute Zustellung
  - erneut zustellen
  - neu zustellen
  - nie geklingelt
  - falsche Adresse
actions:
  - reshipment
related_knowledge:
  - POLICY-012
  - POLICY-015
  - POLICY-045
  - POLICY-046
---

Gilt für Endverbraucher im Vertriebskanal looxis-de, bei allen Produkten.

# Regel

Kommt eine Sendung vom Paketdienst zurück, wird der Kunde über die Rücksendung und, soweit belegt, den Grund informiert. Grundsätzlich wird ein kostenpflichtiger Neuversand angeboten: Die vorhandene Sendung (POLICY-012) wird unverändert erneut versendet, der Kunde trägt die Versandkosten (Platzhalter `[VERSANDKOSTEN]`), nach Bestätigung einer vollständigen, zustellfähigen Adresse.

# Kulanz ab 100 Euro Bestellwert

Bei einem Bestellwert ab 100 Euro besteht eine ausreichende Marge. Liefert der Kunde eine nachvollziehbare Erklärung, warum er die Zustellung unverschuldet nicht erhalten konnte, kann der Agent einen kostenfreien Neuversand anbieten. Der Agent entscheidet selbst im Einzelfall nach gesundem Menschenverstand; es besteht kein Anspruch darauf. Bei Zweifel Rücksprache. Unter 100 Euro Bestellwert und ohne nachvollziehbare Erklärung bleibt es beim kostenpflichtigen Neuversand.

Ob die 100 Euro mit oder ohne Versandkosten gerechnet werden und welche Erklärungen als nachvollziehbar gelten, ist nicht festgelegt.

# Kommunikation

Kostenpflichtig: „Ihre Sendung ist vom Paketdienst an uns zurückgekommen. Wir versenden sie gerne erneut. Dafür fallen die Versandkosten für den erneuten Versand an ([VERSANDKOSTEN]). Bitte bestätigen Sie uns Ihre vollständige und korrekte Lieferadresse und dass Sie die Kosten übernehmen.“

Kostenfrei (nur nach Entscheidung des Agenten): „Wir haben Ihre Erklärung geprüft und versenden Ihre Sendung ausnahmsweise kostenfrei erneut. Bitte bestätigen Sie uns Ihre vollständige und korrekte Lieferadresse.“

# Grenzen

- Kein kostenloser Neuversand unter 100 Euro Bestellwert oder ohne nachvollziehbare Erklärung; keine Erstattung zusagen.
- Keine Beträge oder Gründe erfinden.
- Gesetzliche Ansprüche bleiben unberührt; interne Regeln dürfen ihre Erfüllung nicht vereiteln.
