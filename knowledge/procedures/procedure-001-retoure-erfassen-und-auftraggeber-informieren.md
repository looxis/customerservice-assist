---
id: PROCEDURE-001
title: Retoure erfassen, einlagern und Auftraggeber informieren
type: procedure
status: draft
actions:
- return
customer_types:
sales_channels:
- looxis-de
- fachhaendler
- looxis-pro
- masterpics
products:
categories:
- complaint
- order-process-question
topics:
- return-storage
- product-label
related_knowledge:
- POLICY-012
- GLOSSARY-003
---

# Zweck

Für alle Produkte auf looxis.de, im Fachhändlerkanal, bei LOOXIS-Pro und masterpics eine eingegangene Retoure in EOCS der richtigen Sendung zuordnen, einlagern und den Kunden beziehungsweise Auftraggeber informieren; dieser Ablauf gilt nicht für Amazon.

# Voraussetzungen

- Das zurückgekommene Paket liegt vor.
- Der Originalauftrag ist über seine externe Bestellnummer oder interne Auftragsnummer identifizierbar; der Barcode des Produktlabels kann gescannt werden. Zum Nummernaufbau siehe GLOSSARY-003.
- Die betroffene Sendung ist identifizierbar, etwa anhand der Sendungsnummer auf dem Paket.
- Der Rücksendungsgrund ist bekannt und ein Retourenlagerplatz ist bestimmt.
- Für Aufbewahrung und kanalabhängige Benachrichtigung gilt POLICY-012.

# Arbeitsschritte

1. In EOCS: Den Barcode des Produktlabels scannen oder die interne Auftragsnummer manuell eingeben, um die „Übersicht“ der Bestellung zu öffnen. Alternativ den Auftrag anhand der externen Bestellnummer aufrufen.
2. In EOCS: In der Bestellübersicht auf „Sendungen (1)“ klicken. Die Zahl in Klammern kann je nach Anzahl der Sendungen abweichen.
3. In EOCS: Die tatsächlich zurückgekommene Sendung anhand der Sendungsnummer zuordnen. Bei mehreren Paketen die entsprechende Zeile auswählen.
4. In EOCS: Die sechsstellige ID dieser Sendung anklicken, um deren Sendungsdetailseite zu öffnen.
5. In EOCS: Oben auf „Problem melden“ klicken.
6. In EOCS: Im Feld „Überschrift“ den Text „Retoure“ eintragen.
7. In EOCS: Im Feld „Beschreibung“ den tatsächlichen Rücksendungsgrund manuell eintragen, etwa nicht abgeholt oder Empfänger unbekannt. Dies sind Beispiele, keine festen Auswahlwerte.
8. In EOCS: Im Feld „Lagerort“ den vorgesehenen Lagerplatz eintragen, beispielsweise R1, R2 oder R10.
9. In EOCS: Die Eingaben durch Klicken auf den Knopf „Problem melden“ speichern.
10. Im Retourenlager: Das Paket am eingetragenen Lagerort ablegen.
11. In Zammad: Manuell ein Ticket erstellen und den Kunden beziehungsweise Auftraggeber über die Retoure und den Rücksendungsgrund informieren sowie um Mitteilung bitten, wie weiter verfahren werden soll. Die konkrete Bedienung der Zammad-Ticketmaske ist nicht Bestandteil dieses Ablaufs.

# Kritische Hinweise

Achtung: Bei mehreren Sendungen muss die zurückgekommene Sendung ausgewählt werden. Die Zuordnung zur richtigen Bestellung allein genügt nicht.

Achtung: Das Feld „Läuft ab am“ ist für diesen Ablauf nicht mehr relevant. Die geltende Aufbewahrung richtet sich nach POLICY-012.

Achtung: Eingetragener Lagerort und tatsächlicher Ablageort müssen übereinstimmen.

Achtung: Das Speichern in EOCS erstellt keine Benachrichtigung. Das Ticket muss anschließend manuell erstellt werden.

# Abschlusskontrolle

- [ ] Der richtige Originalauftrag und die tatsächlich zurückgekommene Sendung wurden ausgewählt.
- [ ] Das Problem „Retoure“ mit Rücksendungsgrund und Lagerort wurde in EOCS gespeichert.
- [ ] Das Paket liegt am in EOCS eingetragenen Lagerort.
- [ ] Das Ticket wurde manuell in Zammad erstellt und die Nachricht an den Kunden beziehungsweise Auftraggeber versendet.
