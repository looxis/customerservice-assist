---
id: POLICY-045
title: Nicht zustellbare Sendungen – Mitwirkung des Empfängers
type: policy
status: active
customer_types:
sales_channels:
products:
categories:
  - complaint
  - order-process-question
topics:
  - delivery-failure
  - delivery-options
summary: "Ein Paket kommt vom Paketdienst an LOOXIS zurück, oder ein Kunde sagt, es sei nicht zugestellt worden, es habe niemand geklingelt oder er sei zuhause gewesen. Erklärt, dass die Zustellung die Mitwirkung des Empfängers voraussetzt (zustellfähige Adresse, Zustellung ermöglichen oder abholen), dass er vorab informiert wird und Zustelloptionen selbst ändern kann, und dass im Einzelfall nichts unterstellt wird. Das Vorgehen je Kundengruppe regeln POLICY-046, POLICY-047 und POLICY-048."
retrieval: triggered
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
related_knowledge:
  - POLICY-011
  - POLICY-012
  - POLICY-015
  - POLICY-046
  - POLICY-047
  - POLICY-048
  - PLAYBOOK-029
---

Gilt für alle Kundenarten und Vertriebskanäle.

# Regel

Eine Zustellung gelingt nur, wenn der Empfänger sie ermöglicht. Dazu gehören eine zustellfähige, vollständige Adresse, die der Kunde bereits bei der Bestellung mitteilt (bei Zustellung an Privatadressen der Name wie in der Adresse auf Klingel und Briefkasten), und die Möglichkeit, die Sendung anzunehmen: durch Anwesenheit oder durch Abholung an einer Abholstation. Das sind Pflichten des Kunden.

Bei LOOXIS-Bestellungen besteht in aller Regel kein Widerrufsrecht, weil sie von Geschäftskunden stammen oder personalisierte Produkte betreffen. Die Pflicht, die Zustellung zu ermöglichen, entfällt daher nicht durch einen Widerruf.

# Hintergrund

- Der Kunde erhält automatisch eine E-Mail von LOOXIS, sobald das Paket in die Zustellung geht („Ihr Paket kommt heute“), und kennt seine Sendungsnummer. Er kann die Lieferung aktiv verfolgen.
- Kommt eine Zustellung trotzdem nicht zustande, kann der Kunde das mit der Sendungsnummer direkt mit dem Paketdienst klären.
- Vor der Zustellung lassen sich teilweise Zustelloptionen ändern und Abstellgenehmigungen erteilen. LOOXIS kann solche Anweisungen nicht für den Empfänger hinterlegen (POLICY-015).

# Wenn ein Paket zurückkommt

Der Grund liegt höchstens anteilig beim Paketdienst und immer auch im Verantwortungsbereich des Empfängers, zum Beispiel bei einer falschen oder unvollständigen Adresse, einem fehlenden Namen oder einer nicht möglichen Annahme oder Abholung. Dem Kunden wird im Einzelfall nichts unterstellt (zum Beispiel „Sie waren nicht zuhause“). Der Grund wird nur genannt, soweit er aus der Sendungsverfolgung oder dem Rücksendegrund belegt ist.

Das Vorgehen je Kundengruppe: POLICY-046 (B2B und Fachhändler), POLICY-047 (Endverbraucher looxis.de), POLICY-048 und PLAYBOOK-029 (Amazon, Testphase).

# Grenzen

- Dem Paketdienst nicht pauschal die Alleinschuld geben und dem Kunden keine konkrete Handlung unterstellen.
- Keine Zustelloptionen oder Abstellgenehmigungen zusagen oder hinterlegen (POLICY-015).
- Gesetzliche Ansprüche bleiben unberührt; interne Regeln dürfen ihre Erfüllung nicht vereiteln.
