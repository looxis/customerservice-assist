# Produkte und Produktinformationen

> Zusammenfassung abgeschlossener Features (Status „Approved“). Die vollständigen Specs mit Entscheidungen, Randfällen und QA-Ergebnissen liegen in diesem Ordner.

Das Produkt wird aus Bestellung und Kundenbegriffen (`customer_terms`) vorgeschlagen (PROJ-33). Produktinhalte (Technik, Lieferumfang, Pflege, Warnhinweise, Beschreibungen, Kundenservice-Wissen) kommen aus der ProductForge-API als eigener Prompt-Abschnitt; Markdown-Wissen behält Budgetvorrang, ein Snapshot wird mit der Analyse gespeichert (PROJ-38). Offen: Aufgabenteilung Produktfakten und Regeln (PROJ-46) und Migration des Produktwissens (PROJ-47).

## Features

- **PROJ-33 Produktvorschlag aus Kundenbegriffen** (2026-10-07) – Neues Frontmatter-Feld `customer_terms` in Produktdateien (frühere Produktnamen, Synonyme, Kundenbegriffe wie „Viamant“); die App erkennt sie im Tickettext und schlägt das Produkt im Analyse-Formular vor, getrennt von `order_keywords` (nur Bestellpositionen) [Spec](PROJ-33-produktvorschlag-aus-kundenbegriffen.md)
- **PROJ-38 Produktinformationen aus ProductForge** (2026-10-12) – Produktinhalte (Technische Daten, Lieferumfang, Pflege, Warnhinweise, Beschreibung, Bullet Points, Kundenservice-Wissen) aus der ProductForge-API (LOOXIS Content Studio) abrufen und bei Ticketanalyse und „Frage stellen“ als eigenen Prompt-Abschnitt nutzen; Produktsuche und Zuordnung zur Bestellposition (SKU-Zuordnung hinter Schalter, bis `item_id` ↔ SKU geprüft ist); Markdown-Wissen bleibt für Regeln und Befugnisse zuständig und hat Budgetvorrang; Snapshot des abgerufenen Datenpakets und des gesendeten Anteils, Fortsetzungen ohne Neuabruf. Ersetzt den früheren breiten Entwurf (EOCS-Produktabruf entfällt) [Spec](PROJ-38-produktinformationen-aus-productforge.md)
