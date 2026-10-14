---
id: PERMISSION-005
title: Erstattungen an Foto-Fachhändler nur mit Geschäftsführerfreigabe
type: permission
status: draft
customer_types:
- b2b-reseller
sales_channels:
- fachhaendler
action: refund
agent_allowed: false
max_value_eur:
approval_role: managing-director
products: []
categories: []
topics:
- refund
- customer-type
related_knowledge:
- POLICY-034
- PERMISSION-001
- PERMISSION-002
- PERMISSION-006
summary: "Regelt die Erstattungsbefugnis bei Reklamationen von Foto-Fachhändlern. Eine Erstattung benötigt unabhängig vom Betrag Geschäftsführerfreigabe; die allgemeine 35-Euro-Befugnis gilt hier nicht. Regelkonformer Ersatz bleibt ohne Wertgrenze möglich."
retrieval: binding
---

Gilt für Foto-Fachhändler/Reseller im Vertriebskanal `fachhaendler` bei Erstattungen im Zusammenhang mit Reklamationen.

# Befugnis

Der Kundenservice darf eine Erstattung nur nach Geschäftsführerfreigabe verbindlich zusagen, unabhängig von Produktpreis und Erstattungsbetrag. Die 35-Euro-Befugnis aus PERMISSION-001 gilt hier nicht.

# Grenzen

Die fachlichen Voraussetzungen und die freiwillige Erstattungsausnahme regelt POLICY-034. Bestehende gesetzliche Ansprüche bleiben unberührt; interne Freigaben dürfen ihre Erfüllung nicht vereiteln.

Regelkonforme Neuproduktion und Ersatzversand bleiben nach PERMISSION-002 ohne Wertgrenze erlaubt. Für freiwillige nachträgliche Nachlässe auf behaltene Ware gilt PERMISSION-006.

# Wertgrenze

Das leere Feld `max_value_eur` bedeutet zusammen mit `agent_allowed: false`: Freigabe unabhängig vom Betrag, keine unbegrenzte eigene Befugnis.
