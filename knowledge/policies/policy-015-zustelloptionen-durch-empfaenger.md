---
id: POLICY-015
title: Zustellanweisungen und Zustelloptionen durch den Empfänger
type: policy
status: draft
customer_types:
sales_channels:
products:
categories:
- order-process-question
- complaint
topics:
- delivery-options
- sales-channel
related_knowledge:
- PLAYBOOK-007
- PLAYBOOK-025
- TONE-002
summary: "Gilt bei Wünschen zur Zustellart und Beschwerden, dass DHL ein Paket an einem anderen Ort oder in einer Packstation bereitstellt. LOOXIS steuert die konkrete Zustellung vor Ort nicht; Empfängeroptionen und Abholprobleme getrennt klären."
retrieval: triggered
triggers:
- Zustellanweisung
- Zustelloption
- Paketshop
- Nachbarn
- Ablageort
- Abstellgenehmigung
- Ablagegenehmigung
- vor die Tür
- Wunschort
- Packstation
- Paketstation
- Abholcode
- Abhol-Code
- Abhol Code
- Benachrichtigungskarte
- nicht geklingelt
exclude_triggers:
when:
actions:
---

Gilt für alle Produkte, Kundengruppen und Vertriebskanäle, einschließlich Auslandssendungen.

# Regel

LOOXIS kann für den Empfänger keine individuellen Zustellanweisungen beim Paketdienst hinterlegen oder ändern. Dazu gehören Abstellgenehmigungen und Wünsche zur Abgabe bei Nachbarn oder Geschäften. Auch eine Antwort auf eine von LOOXIS versandte Zustellankündigung bewirkt keine solche Anweisung an den Paketdienst.

Der Empfänger muss selbst in der Sendungsverfolgung beziehungsweise auf der Webseite des zuständigen Paketdienstes prüfen, ob sich Zustelloptionen auswählen oder ändern lassen.

# Zustellung vor Ort und automatische Benachrichtigung

LOOXIS steuert nicht, wie DHL den konkreten Zustellversuch vor Ort durchführt oder in welcher Packstation eine Sendung nach einem erfolglosen Zustellversuch bereitgestellt wird. Die tatsächliche Abwicklung kann unter anderem von der Möglichkeit der Übergabe an den Empfänger und von dessen bei DHL hinterlegten Empfangsoptionen abhängen. Eine Abstellgenehmigung, eine Paketumleitung und eine dauerhafte Lieferung an einen Abholort sind unterschiedliche Optionen.

Diese möglichen Einflussfaktoren nicht als feststehende Ursache eines Einzelfalls darstellen. Aus einer Packstationszustellung folgt insbesondere nicht, dass der Kunde sicher abwesend war, nicht geöffnet hat oder selbst eine Umleitung beauftragt hat.

Die automatisierte LOOXIS-Mitteilung zur Abholbereitschaft informiert über die Sendung. Sie ist keine von LOOXIS veranlasste Änderung des Zustellorts und ersetzt nicht die von DHL bereitgestellte Abholberechtigung. Bei fehlendem Abholcode, fehlender Benachrichtigung oder einer nicht öffnenden Packstation gilt PLAYBOOK-025.

# Keine Übermittlung einer Kunden-E-Mail-Adresse an DHL

LOOXIS übermittelt im hier beschriebenen Versandablauf keine E-Mail-Adresse des Empfängers an DHL. Das gilt auch für LOOXIS-Sendungen zu Amazon-Bestellungen. Die automatische LOOXIS-Abholnachricht bedeutet daher nicht, dass DHL dieselbe E-Mail-Adresse erhalten hat oder eine eigene E-Mail versenden konnte.

Ein eigener digitaler Kontakt durch DHL kann unabhängig davon über ein bestehendes DHL-Kundenkonto des Empfängers möglich sein, wenn DHL die Sendung diesem Konto zuordnen kann und die entsprechenden Dienste nutzbar sind. Eine solche Zuordnung nicht ungeprüft voraussetzen. Aus einer Amazon-Bestellung allein ergibt sich kein digitaler DHL-Kontaktweg.

# Grenzen

Welche Optionen verfügbar sind und ob eine Änderung noch möglich ist, hängt vom Paketdienst und den Bedingungen der konkreten Sendung ab. LOOXIS sagt weder die Verfügbarkeit einer bestimmten Option noch deren Umsetzung zu.

Es ist kein allgemeingültiger Ablauf zur Legitimation des Empfängers bestätigt. Insbesondere wird die Eingabe einer Empfänger-Postleitzahl nicht als bei jedem Paketdienst erforderlicher oder ausreichender Schritt vorgegeben.

# Kommunikationsweg bei masterpics

Bei White-Label-Aufträgen von masterpics wird die Information an masterpics als Ansprechpartner gegeben, damit sie den Endkunden erreicht. Spricht ein Endkunde LOOXIS direkt an, wird er höflich an masterpics als Verkäufer und Vertragspartner verwiesen.

# Abgrenzung

Die fehlende Möglichkeit, individuelle Zustellanweisungen zu hinterlegen, ist keine pauschale Aussage über Verantwortung oder Ansprüche bei sonstigen Versandproblemen.

