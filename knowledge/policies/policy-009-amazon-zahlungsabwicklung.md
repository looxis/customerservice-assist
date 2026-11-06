---
id: POLICY-009
title: Zahlungsabwicklung und Teilerstattung auf Amazon
type: policy
status: active
trial_until: 2027-03-31
trial_goal: "Ob die Rechnung beim kostenpflichtigen Neuversand auf Vertrauensbasis funktioniert und hilft, entscheiden wir im Gespräch mit den Kollegen."
sales_channels:
- amazon
categories:
- complaint
- order-process-question
topics:
- reorder
- refund
- sales-channel
related_knowledge:
- POLICY-006
- PERMISSION-003
customer_types:
products:
summary: "Zahlungsabwicklung und Teilerstattung auf Amazon. Gilt, sobald auf Amazon eine Teilerstattung vereinbart oder empfohlen wird."
retrieval: binding
triggers:
- Teilerstattung
- Kulanz
exclude_triggers:
when:
actions:
- goodwill-reproduction
- partial-refund
---

Gilt für die LOOXIS-Abwicklung auf Amazon bei allen Kundenarten.

# Regel

LOOXIS fordert über Amazon keine zusätzlichen Zahlungen an und versendet dort keine externen Zahlungslinks, beispielsweise für Mollie.

Für eine kostenpflichtige Kulanzlösung bestellt der Kunde neu. Ein vereinbarter Rabatt wird als Teilerstattung auf dieser neuen Bestellung abgewickelt.

# Zeitpunkt

Die Teilerstattung ist erst möglich, nachdem die Ware der neuen Bestellung verschickt wurde. Vor Versand wird keine Teilerstattung vorgenommen.

# Grenzen

Eine rein kostenfreie Ersatzlieferung wird durch diese Zahlungsregel nicht automatisch zu einer kostenpflichtigen Neubestellung.

# Rechnung beim kostenpflichtigen Neuversand (Testphase)

Testweise gültig bis 31.03.2027. Bei einem kostenpflichtigen Neuversand nach Rücksendung eines nicht zustellbaren oder nicht abgeholten Pakets wird dem Paket eine Rechnung beigelegt, und der Kunde bezahlt auf Vertrauensbasis. Dieser Weg darf in der Testphase angeboten werden. Er ersetzt keinen Zahlungslink und keine zusätzliche Zahlung über Amazon.
