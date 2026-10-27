---
id: PROCEDURE-009
title: Mengenanfrage im Angebotsrechner berechnen
type: procedure
status: draft
products: []
categories:
  - quote-request
topics:
  - case-assessment
customer_types: []
sales_channels: []
related_knowledge:
  - PROCESS-010
  - POLICY-026
  - TONE-009
summary: "Interne Anleitung: Bei Mengen- und Angebotsanfragen Rabatt, Marge und Rabattgrenze im Angebotsrechner der App prüfen und daraus ein Angebot erstellen. Nur intern, die Kosten gehören nicht in die Kundenantwort."
retrieval: triggered
triggers:
  - Mengenanfrage
  - Mengenrabatt
  - Mengenpreis
  - Staffelpreis
  - Angebot
  - Angebotsanfrage
  - Preisanfrage
  - Stückzahl
  - Stück für
  - Kostenvoranschlag
actions:
  - quote
---

Gilt für Mengenanfragen und Bitten um ein Angebot, bei denen ein Preis oder Rabatt für eine bestimmte Menge genannt werden soll.

# Zweck

Den Preis für eine Menge anhand des Shop-Preises und der hinterlegten Kosten prüfen, damit ein vom Mitarbeiter gewählter Rabatt die Zielmarge nicht unterschreitet, und daraus ein Angebot erzeugen.

# Voraussetzungen

- Der Artikel ist im Angebotsrechner vorhanden. Fehlt er, den Admin bitten, ihn anzulegen.
- Menge und Artikel der Anfrage sind klar (sonst zuerst nachfragen, siehe PROCESS-010).

# Arbeitsschritte

1. Den [Angebotsrechner öffnen](link:angebotsrechner). Das Ticket ist dort bereits verknüpft.
2. Den angefragten Artikel wählen und die Menge eintragen.
3. Rabatt in Prozent oder den Kundenpreis eintragen. Die Rabatthöhe legt der Mitarbeiter fest; der Rechner zeigt Marge, Deckungsbeitrag und die Rabattgrenzen bei Zielmarge und bis Break-even.
4. Zeigt der Rechner „Kosten unvollständig“, fehlen Einstandspreis oder Maschinenzeit des Artikels. Einen Admin bitten, sie unter „Kosten“ zu ergänzen. Keine Beträge schätzen.
5. Liegt die Marge unter der Zielmarge oder entsteht ein Verlust, den Rabatt nicht ohne Rücksprache mit der Geschäftsleitung zusagen.
6. Mit „+ Artikel zum Angebot“ in das Angebot übernehmen, Kundendaten eintragen, speichern und über „Drucken / PDF“ ausgeben.
7. Den berechneten Preis in der Antwort an den Kunden nennen. Weitere Bedingungen wie Versandkosten, Zahlungsweg und Lieferzeit nach PROCESS-010 klären.

# Abschlusskontrolle

- Das Angebot ist gespeichert und hat eine Angebotsnummer.
- Der genannte Preis stimmt mit dem Angebot überein.
- Es steht kein interner Kostenwert in der Kundenantwort.

# Kritische Hinweise

Achtung: Kosten, Marge und Rabattgrenzen sind intern und gehören nie in die Kundenantwort.

Achtung: Ohne Rabattangabe des Mitarbeiters keinen Rabatt festsetzen. Der Rechner liefert nur Grenzen, keine Zusage.
