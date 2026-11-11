---
id: TONE-011
title: Bei zugesagter Neuproduktion Versand und Reklamationsnummer nennen
type: tone
status: active
customer_types:
sales_channels:
categories:
  - complaint
topics:
  - replacement
  - clear-communication
summary: "Sagt der Kundenservice einem Kunden eine kostenfreie Neuproduktion mit Ersatzversand zu, nennt die Antwort zusätzlich, unter welcher Nummer der Auftrag bei LOOXIS läuft, und den voraussichtlichen Versand nur so, wie der Mitarbeiter ihn angegeben hat (heute, morgen oder zeitnah); ohne Angabe wird kein Tag genannt. Gilt nicht, wenn keine Neuproduktion zugesagt wird, und nicht, wenn der Kunde das Produkt zuerst zurücksenden muss: Dann stehen Versandtermin und Nummer noch nicht fest."
retrieval: binding
triggers:
exclude_triggers:
when:
actions:
  - reproduction
related_knowledge:
  - PROCEDURE-005
  - POLICY-032
  - TONE-002
---

Gilt für alle Kundenarten und Vertriebskanäle.

# Ziel

Der Kunde erfährt, dass die Neuproduktion läuft und unter welcher Nummer er sie bei Rückfragen nennen kann. Ein Versandzeitpunkt steht nur in der Antwort, wenn der Mitarbeiter ihn angegeben hat.

# Pflichtinhalt der Antwort

Wird dem Kunden eine kostenfreie Neuproduktion mit Ersatzversand zugesagt und ohne vorherige Rücksendung produziert, steht in der Antwort ein Satz nach diesem Muster:

„Die neue Tasse wird [VERSANDANGABE] verschickt, der Auftrag läuft bei uns unter der Nummer [REKLAMATIONSNUMMER].“

Statt „Die neue Tasse“ den passenden Ausdruck für das Produkt einsetzen, zum Beispiel „Das neu produzierte Produkt“.

Für [VERSANDANGABE] gilt ausschließlich die Angabe des Mitarbeiters („Versand voraussichtlich“):

- heute: „voraussichtlich heute noch“
- morgen: „voraussichtlich morgen“
- zeitnah oder keine Angabe: „zeitnah“, ohne Tag und ohne Uhrzeit

Ob heute noch produziert und versendet wird, kann die Antwort nicht wissen. „Heute“ wird deshalb nie aus der Uhrzeit, dem Wochentag oder dem Wissen abgeleitet.

Die Nummer des Reklamationsauftrags (Aufbau R1-… oder R2-… vor der Bestellnummer) steht erst fest, wenn der Mitarbeiter den Auftrag in EOCS gespeichert hat (PROCEDURE-005). Deshalb steht im Entwurf der Platzhalter [REKLAMATIONSNUMMER], den der Mitarbeiter vor dem Kopieren oder Senden ersetzt. Die Nummer wird nicht geraten.

# Ausnahme: zuerst Rücksendung

Muss der Kunde das Produkt zuerst zurücksenden und LOOXIS produziert oder repariert erst nach dem Eingang, entfällt der Satz mit Versandtermin und Reklamationsnummer, auch als Platzhalter. Stattdessen:

„Sobald Ihre Rücksendung bei uns eingegangen ist, kümmern wir uns um den Ersatz und melden uns dann bei Ihnen.“

# Vermeiden

- Eine Nummer erfinden oder aus der Bestellnummer ableiten.
- „Heute noch“ oder „noch heute“ schreiben, ohne dass der Mitarbeiter „heute“ angegeben hat.
- Den Satz verwenden, wenn keine Neuproduktion zugesagt wird, zum Beispiel bei einer reinen Erstattung.
- Bei vorheriger Rücksendung einen Versandtermin, [LIEFERTERMIN] oder [REKLAMATIONSNUMMER] nennen.
