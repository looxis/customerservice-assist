---
id: PLAYBOOK-052
title: Amazon-Kunde behauptet, etwas anderes bestellt zu haben als in der Bestellung steht
type: playbook
status: active

customer_types:
  - b2c
sales_channels:
  - amazon

products:
exclude_products:
categories:
  - complaint
topics:
  - product-expectation
  - case-assessment
  - complaint-assessment

summary: "Ein Amazon-Kunde schreibt, er habe etwas anderes bestellt, als er bekommen hat (anderes Produkt, andere Farbe, anderer Artikel als auf der Produktseite angezeigt), obwohl die Bestellung in EOCS das gelieferte Produkt ausweist. Klärt die Antwort: Wir verweisen auf die Bestellung unter „Meine Bestellungen“ bei Amazon, ohne nachzuprüfen, was der Kunde damals auf der Produktseite gesehen hat, und ohne Screenshot zu verlangen. Gilt nicht, wenn die gelieferte Ware von der Bestellung abweicht (dann Verwechslung, PLAYBOOK-006)."
retrieval: triggered
triggers:
  - etwas anderes bestellt
  - anderes bestellt
  - nicht das bestellt
  - nicht das, was ich bestellt
  - nicht bestellt
  - ganz anderes Produkt
  - anderes Produkt bestellt
  - anderen Artikel bestellt
  - andere Farbe bestellt
  - war angezeigt
  - stand dort
  - stand in der Beschreibung
  - Différent de ce que j'ai commandé
  - pas ce que j'ai commandé
  - pas ce que je voulais
  - lorsque j'ai commandé
  - quand j'ai commandé
  - Diverso da quello che ho ordinato
  - non è quello che ho ordinato
  - Diferente de lo que pedí
  - no es lo que pedí
  - not what I ordered
  - different from what I ordered
  - not as described
exclude_triggers:
when:
actions:
priority:
risk_level:

owner:
reviewed_by:
last_reviewed:
trial_until:
trial_goal:

related_knowledge:
  - POLICY-033
  - POLICY-029
  - POLICY-001
  - POLICY-002
  - PLAYBOOK-006
  - PLAYBOOK-023
  - TONE-002
---

Gilt für Privatkunden auf Amazon.

# Fallmuster

Ein Kunde meldet, er habe etwas anderes bestellt, als geliefert wurde: „Auf der Seite stand ein Untersetzer, bekommen habe ich ein essbares Foto“, „ich habe die blaue Tasse bestellt, geliefert wurde die rote“. Auf Amazon kommt das regelmäßig vor. Viele Kunden lesen weder Produkttitel noch Bulletpoints noch Produktfotos, klicken sich durch die Angebote und legen unbemerkt ein anderes Produkt in den Warenkorb. Danach entsteht die Behauptung, es sei etwas anderes angezeigt oder bestellt worden.

# Erkennungsmerkmale

- Der Kunde sagt, er habe etwas anderes bestellt oder angezeigt bekommen, als er erhalten hat.
- Er beanstandet kein Qualitätsproblem, sondern dass das Produkt nicht seiner Vorstellung entspricht.
- Die Bestellung in EOCS zeigt das gelieferte Produkt, und die Ware wurde entsprechend der Bestellung geliefert.

# Zu prüfen

1. Bestellposition und Konfiguration in EOCS mit dem gelieferten Produkt abgleichen. Der Kunde bestätigt oft selbst, was er erhalten hat; das genügt als Beschreibung der gelieferten Ware.
2. Stimmt die gelieferte Ware mit der Bestellung überein, ist der Fall hier abgeschlossen. Weicht sie ab (anderes Produkt, falsches Motiv), liegt eine Verwechslung vor und PLAYBOOK-006 gilt.

# Nicht prüfen

- Nicht nachforschen, was zum Zeitpunkt der Bestellung auf der Amazon-Produktseite zu sehen war.
- Keinen Screenshot der angeblichen Angabe verlangen und nicht danach fragen, wo der Kunde sie gesehen haben will. Wer einen Screenshot gemacht hätte, hätte sich zuvor auch die Produktdetails angesehen.
- Das Versprechen auf der Produktseite (POLICY-033) wird hier nicht als Beweisfrage gegenüber dem Kunden aufgerollt. Maßgeblich ist die Bestellung.

# Berechtigt wenn

- Die gelieferte Ware weicht von der Bestellung in EOCS ab (Verwechslung, falsches Produkt, falsche Konfiguration). Dann nach PLAYBOOK-006 und POLICY-002 vorgehen.

# Nicht berechtigt wenn

- Die gelieferte Ware entspricht der Bestellung. Das reine Gefühl, etwas anderes bestellt zu haben, begründet keinen Ersatz, keine kostenlose Neuproduktion und keine Erstattung.

# Noch nicht entscheidbar wenn

- Die Bestellung ist nicht auffindbar oder die Position nicht eindeutig. Dann zuerst die Bestellnummer klären (POLICY-029).
- Der Kunde nennt neben dem „falschen Produkt“ einen weiteren, eigenständigen Mangel (zum Beispiel einen Schaden). Diesen Punkt getrennt prüfen (PLAYBOOK-023).

# Empfohlene Maßnahme

Dem Kunden freundlich nennen, was in der Bestellung steht, und ihn bitten, bei Amazon unter „Meine Bestellungen“ selbst nachzusehen. Dort sieht er, was er bestellt hat. Keine Rückfrage nach einem Screenshot, keine Ersatz- oder Erstattungszusage, keine Beweisführung über die damalige Produktseite.

# Befugnis

Eine Ablehnung ohne Zusage braucht keine Freigabe. Ersatz, Erstattung oder Kulanz sind keine Folge dieses Falls; sie richten sich nur dann nach den bekannten Regeln, wenn der Kunde zusätzlich einen echten Mangel oder eine Abweichung von der Bestellung belegt.

# Kommunikation

1. Die Enttäuschung des Kunden kurz und ohne Vorwurf aufgreifen (TONE-002).
2. Sagen, was bestellt wurde, und bestätigen, dass genau dieses Produkt geliefert wurde. Bestätigt der Kunde selbst, was er erhalten hat, dieses mit der Bestellung zusammenführen.
3. Den Kunden bitten, bei Amazon unter „Meine Bestellungen“ noch einmal nachzusehen; dort sieht er die Bestellung mit dem bestellten Produkt.
4. Keine Zusage von Ersatz, Erstattung oder Rücknahme und keine Frage nach einem Screenshot oder dem Ort der angeblichen Angabe.
5. Die Antwort in der Sprache des Kunden schreiben.

Beispiel (Sinn, nicht Wortlaut): „Hallo <Vorname>, in Ihrer Bestellung <Nummer> ist <Produkt> aufgeführt, und genau dieses Produkt haben wir Ihnen geliefert. Wenn Sie bei Amazon unter ‚Meine Bestellungen‘ nachsehen, finden Sie dort, was Sie bestellt haben.“

# No-Gos

- Einen Screenshot oder Beleg der damaligen Produktseite verlangen.
- Fragen, wo genau der Kunde die behauptete Angabe gesehen hat, oder anbieten, die „Anfrage auf Ersatz“ danach zu prüfen.
- Aus der bloßen Behauptung Ersatz oder Erstattung anbieten.
- Dem Kunden vorwerfen, er habe nicht richtig gelesen.
