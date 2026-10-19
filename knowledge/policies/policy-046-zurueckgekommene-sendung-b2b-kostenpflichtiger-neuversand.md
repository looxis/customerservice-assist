---
id: POLICY-046
title: Zurückgekommene Sendung bei B2B-Kunden – Information und kostenpflichtiger Neuversand
type: policy
status: active
customer_types:
  - b2b-reseller
  - b2b-pro
  - b2b-whitelabel
sales_channels:
  - fachhaendler
  - looxis-pro
  - masterpics
products:
categories:
  - complaint
  - order-process-question
topics:
  - delivery-failure
summary: "Eine Sendung eines Fachhändlers, von LOOXIS-Pro oder von masterpics ist vom Paketdienst an LOOXIS zurückgekommen oder der Kunde fragt nach erneuter Zustellung. Der Kunde (bei masterpics der Auftraggeber) wird über die Rücksendung und den Grund informiert; danach wird ein kostenpflichtiger Neuversand der vorhandenen Sendung angeboten, ohne kostenlosen Neuversand und ohne Erstattung."
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
  - falsche Adresse
actions:
  - reshipment
related_knowledge:
  - POLICY-011
  - POLICY-012
  - POLICY-015
  - POLICY-045
---

Gilt für Foto-Fachhändler und andere B2B-Kunden in den Kanälen fachhaendler, looxis-pro und masterpics, bei allen Produkten.

# Regel

Kommt eine Sendung vom Paketdienst an LOOXIS zurück, wird der Kunde über die Rücksendung und über den Grund informiert (zum Beispiel falsche oder unvollständige Adresse, fehlender Name). Anschließend wird ihm ein kostenpflichtiger Neuversand angeboten. Kostenlosen Neuversand und eine Erstattung gibt es für diesen Sachverhalt nicht.

Bei masterpics ist der Ansprechpartner masterpics als Auftraggeber, nicht der Endkunde (POLICY-011, POLICY-015).

# Neuversand

Die zurückgekommene Sendung (Aufbewahrung nach POLICY-012) wird unverändert erneut versendet, es wird nichts neu produziert. Der Kunde trägt die Versandkosten für den erneuten Versand. Einen Betrag nennt dieses Dokument nicht; im Entwurf steht der Platzhalter `[VERSANDKOSTEN]`. Versendet wird, nachdem der Kunde eine vollständige, zustellfähige Adresse bestätigt und die Kosten übernommen hat.

# Kommunikation

„Ihre Sendung ist vom Paketdienst an uns zurückgekommen. Grund: [GRUND]. Wir versenden sie gerne erneut. Dafür fallen die Versandkosten für den erneuten Versand an ([VERSANDKOSTEN]). Bitte bestätigen Sie uns die vollständige und korrekte Lieferadresse und dass Sie die Kosten übernehmen; danach versenden wir die Sendung erneut.“

# Grenzen

- Keinen kostenlosen Neuversand und keine Erstattung zusagen; Kulanz anderer Kundengruppen nicht übertragen (POLICY-011).
- Keine Kosten und keinen Grund erfinden; ist der Grund nicht belegt, ihn offenlassen.
- Gesetzliche Ansprüche bleiben unberührt; interne Regeln dürfen ihre Erfüllung nicht vereiteln.
