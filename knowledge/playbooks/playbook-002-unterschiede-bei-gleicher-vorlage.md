---
id: PLAYBOOK-002
title: Unterschiedliche Ergebnisse trotz gleicher Vorlage
type: playbook
status: draft
customer_types:
sales_channels:
- looxis-de
products:
categories:
- complaint
- order-process-question
topics:
- interpretation
- customer-configuration
- reorder
related_knowledge:
- GLOSSARY-002
- PROCESS-001
- TONE-001
---

Gilt für alle Kundenarten und Kanäle außer Amazon; im aktuellen Kanalschema ist dies `looxis-de`. Erfasst werden Produkte mit interpretationsabhängigen Produktionsdateien.

# Fallmuster

Ein Kunde beanstandet unterschiedliche Ergebnisse trotz gleicher Vorlage oder möchte ein früheres Ergebnis unverändert nachbestellen. Beispiele sind eine andere Platzierung von Logo oder Schrift in einem 3D-Glasfoto.

# Zu prüfen

1. Die betroffenen Bestellungen, Bestellpositionen und Konfigurations-IDs intern vergleichen.
2. Prüfen, ob einmal konfiguriert und mehrfach bestellt oder derselbe Artikel separat neu konfiguriert wurde.
3. Bei einer späteren Bestellung prüfen, ob der Originalvorgang ausdrücklich referenziert wurde oder eine eigenständige Neubestellung vorliegt.
4. Die tatsächlich beanstandeten Unterschiede und die für den Fall verwendete Produktionsgrundlage feststellen.

# Bewertung

Die technische Zuordnung richtet sich nach GLOSSARY-002.

Bei unterschiedlichen Konfigurations-IDs oder einer eigenständigen späteren Bestellung ohne Originalbezug können unabhängige Interpretationen die Unterschiede erklären. Allein daraus folgt noch keine abschließende Bewertung der Reklamation.

Bei derselben Konfigurations-ID darf die Erklärung „zwei eigenständige Interpretationen“ nicht verwendet werden. Kopien mit `_1`, `_2` oder `_3` ändern daran nichts. Die Ursache der Abweichung bleibt zu prüfen.

# Noch nicht entscheidbar wenn

Fehlt die Zuordnung der betroffenen Artikel, Bestellungen oder Konfigurationen, diese intern anhand der Bestelldaten feststellen. Kann ein früherer Auftrag nicht zugeordnet werden, den Kunden um Angaben zum Originalauftrag bitten. Fehlen Belege für die behaupteten Unterschiede, den Kunden bitten, die betroffenen Exemplare beziehungsweise das frühere und das neue Ergebnis zu zeigen und die Abweichung zu benennen.

Ohne geklärte Zuordnung keine unabhängigen Interpretationen als feststehende Ursache behaupten. Sind die Konfigurations-IDs gleich, aber die Ursache unklar, den Fall nicht allein anhand der Interpretationslogik abschließend bewerten.

# Empfohlene Maßnahme

Den tatsächlich festgestellten Unterschied zwischen gemeinsamer Produktionsgrundlage und getrennten Ausarbeitungen verständlich erklären. Die Kommunikationshilfe steht in TONE-001.

Soll das frühere Ergebnis wiederholt werden, PROCESS-001 anwenden. Kulanz, Preis und Mängelbewertung richten sich separat nach den für Kundenart und Kanal geltenden Regeln. Dieses Playbook enthält keine eigenständige Erstattungs- oder Rabattzusage.

# Hinweise für künftige Bestellungen

Mehrere Exemplare eines neuen Ergebnisses: einmal konfigurieren und die Menge entsprechend wählen.

Wiederholung eines vorhandenen Ergebnisses: per E-Mail mit Bezug zum ursprünglichen Auftrag nachbestellen, nach PROCESS-001.

# No-Gos

- Gleiche Vorlage oder Artikelnummer mit gleicher Konfigurations-ID gleichsetzen.
- Dateiduplikate als eigenständige Interpretationen darstellen.
- Die Reklamation allein wegen unterschiedlicher Konfigurations-IDs ablehnen.
- Eine eigenständige Neubestellung als verlässliche identische Wiederholung des früheren Ergebnisses empfehlen.
