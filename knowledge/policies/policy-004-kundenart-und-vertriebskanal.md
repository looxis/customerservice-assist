---
id: POLICY-004
title: Kundenart und Vertriebskanal vor der Entscheidung prüfen
type: policy
status: active
customer_types:
sales_channels:
products:
categories:
topics:
  - sales-channel
  - customer-type
summary: "Gilt bei jedem Fall: Zuerst Kundenart und Vertriebskanal prüfen, bevor eine Regel angewendet wird."
related_knowledge:
- POLICY-034
- POLICY-035
- POLICY-036
- PERMISSION-005
- PERMISSION-006
retrieval: core
triggers:
exclude_triggers:
when:
actions:
---

Gilt für alle Kundenarten und Vertriebskanäle.

# Regel

Vor der Anwendung einer fachlichen Regel müssen Kundenart und Vertriebskanal feststehen. Regeln für Verbraucherbestellungen dürfen nicht automatisch auf B2B-Bestellungen übertragen werden.

# Zu unterscheidende Kundengruppen

- Verbraucher im LOOXIS-Onlineshop.
- Verbraucher bei Amazon. Kanalbedingungen können von denen des eigenen Shops abweichen; eine Abweichung ist nicht allein wegen des Kanals anzunehmen.
- Foto-Fachhändler/Reseller als B2B-Kunden.
- LOOXIS-Pro-Kunden als B2B-Kunden, die Rohware bestellen. Hierzu gehören auch Kunden im Ausland.

# Vorgehen

1. Kundenart und Vertriebskanal anhand der Bestellung und der verfügbaren Kundenstammdaten bestimmen.
2. Den Geltungsbereich der einschlägigen Policies prüfen.
3. Bei Verbraucherbestellungen im eigenen Shop die dafür freigegebenen Regeln anwenden.
4. Bei Amazon zusätzlich die tatsächlich für den Auftrag geltenden Kanalbedingungen berücksichtigen. Keine Amazon-Sonderregel erfinden. Bestätigte Amazon-Regeln stehen in eigenen Dokumenten, zum Beispiel POLICY-006, POLICY-009, POLICY-039, POLICY-040 und PERMISSION-003.
5. Bei B2B-Bestellungen nur dafür bestätigte Regeln anwenden. Die Verbraucher-Policies POLICY-001 bis POLICY-003 sind hierfür noch nicht freigegeben.

# B2B-Besonderheiten

Bei B2B können andere Bedingungen gelten. Bei LOOXIS-Pro sind insbesondere Rohware, Auslandsversand, höhere Versandkosten und möglicher Speditionsversand zu berücksichtigen. Daraus folgt noch keine pauschale Zuordnung der Kosten oder Zusage eines kostenfreien Ersatzversands. Rücknahme und Rückkauf regelt POLICY-064.

# Bestätigte Regeln für Foto-Fachhändler

Für berechtigte Reklamationen gilt POLICY-034 mit PERMISSION-002 (Ersatz) und PERMISSION-005 (Erstattung). Rücksendungen regelt POLICY-010, Änderungen POLICY-032 und Kulanz bei Kundenfehlern POLICY-008. Rückgabewünsche ohne Mangel regelt POLICY-063. Kleine optische Beanstandungen richten sich nach POLICY-035, freiwillige nachträgliche Nachlässe nach POLICY-036 und PERMISSION-006. Diese Fragen sind nicht mehr pauschal offen; andere B2B-Gruppen bleiben gesondert zu prüfen.

# Noch nicht entscheidbar

Ist Kundenart oder Kanal nicht zuverlässig bekannt, zuerst die Bestell- und Kundenstammdaten intern prüfen. Sind sie dort nicht ermittelbar, den Kunden gezielt fragen, wo und in welcher Eigenschaft er bestellt hat.

Fehlen die für den Kanal oder B2B-Fall benötigten Regeln, ist eine Wissenslücke zu benennen. Es darf keine uneingeschränkte Erstattungs- oder Ersatzempfehlung aus einer Verbraucher-Policy abgeleitet werden.

# Noch zu klären

- Reklamation, Ersatz und Erstattung bei LOOXIS-Pro, einschließlich Auslands- und Speditionsversand.
