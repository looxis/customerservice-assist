---
id: PERMISSION-006
title: Nachträglicher Preisnachlass auf beanstandete Ware nur mit Freigabe
type: permission
status: draft
customer_types:
- b2c
- b2b-reseller
sales_channels: []
action: partial-refund
agent_allowed: false
max_value_eur:
approval_role: managing-director
products: []
categories: []
topics:
- refund
- goodwill
related_knowledge:
- POLICY-036
- PERMISSION-001
- PERMISSION-003
- PERMISSION-004
summary: "Ein Privatkunde oder Foto-Fachhändler behält die beanstandete gelieferte Ware und soll einen freiwilligen nachträglichen Preisnachlass erhalten. Jede solche Ausnahme braucht Geschäftsführerfreigabe, auch bei kleinen Beträgen. Gilt nicht für die Rabattabwicklung einer neuen Kulanzbestellung."
retrieval: binding
triggers:
- Preisnachlass
- Nachlass
- Teilerstattung
- Gutschrift
- Minderung
- nachträglicher Rabatt
- partial refund
actions:
- partial-refund
---

Gilt für Privatkunden auf allen Vertriebskanälen sowie Foto-Fachhändler/Reseller im Fachhändlerkanal bei einem freiwilligen nachträglichen Preisnachlass auf beanstandete Ware, die behalten wird.

# Befugnis

Vor jeder verbindlichen Zusage dieser freiwilligen Ausnahme ist die Freigabe der Geschäftsführung erforderlich. Der Kundenservice hat hierfür keine eigene Betrags- oder Prozentgrenze.

Die fachlichen Voraussetzungen regelt POLICY-036. Eine Freigabe wird nicht allein deshalb angefordert, weil ein Kunde Rabatt verlangt; zunächst die Qualität und die sachgerechte Lösung prüfen.

# Grenzen

Die Freigabe ist unabhängig vom ursprünglichen Produktpreis und von der Höhe der vorgesehenen Teilgutschrift notwendig. Die allgemeine 35-Euro-Befugnis aus PERMISSION-001 erteilt keine Freigabe für diesen freiwilligen Nachlass.

Ein gesetzlicher Minderungsanspruch wird nach POLICY-036 gesondert geprüft. Die interne Zustimmung ist keine Voraussetzung für das Entstehen eines solchen Anspruchs und darf dessen Erfüllung nicht vereiteln.

# Andere Rabattvorgänge

Diese Permission regelt keinen Rabatt auf eine neue kostenpflichtige Kulanz-Anfertigung. Deren bestehende Preisregeln und Befugnisse, insbesondere PERMISSION-004, bleiben unverändert.

Die vereinbarte Amazon-Kulanz-Teilerstattung auf eine neue Bestellung bleibt nach PERMISSION-003 zu beurteilen. Allein die Bezeichnung „Teilerstattung“ macht sie nicht zu einem nachträglichen Nachlass auf die ursprünglich beanstandete Ware.

# Wertgrenze

Das leere Feld `max_value_eur` steht zusammen mit `agent_allowed: false` für eine Freigabe unabhängig vom Betrag, nicht für eine unbegrenzte eigene Befugnis.
