# Feature Index

> Central tracking for all features. Updated by skills automatically.

## Status Legend
- **Roadmap** - `/init` done, feature identified in feature map, no spec file yet
- **Planned** - `/write-spec` done, full spec written, architecture not yet designed
- **Architected** - `/architecture` done, tech design approved, ready to build
- **In Progress** - `/frontend` or `/backend` active or completed, not yet in QA
- **In Review** - `/qa` active, testing in progress
- **Approved** - `/qa` passed, no critical/high bugs; the feature is part of the app
- **Deployed** - `/deploy` done, live in production
- **Zurückgestellt** - consciously put on hold, not planned for now; the reason stays in the description

## Open features

Only features that are not yet Approved are listed here, each with its spec in `features/`. When `/qa` approves a feature, its spec moves to `features/archive/`, its row leaves this table, and its ID is added to the matching theme summary below (see "Archive"). No authentication in the MVP (see `docs/PRD.md`, Constraints); login arrives with PROJ-15.

| ID | Feature | Description | Dependencies | Status | Spec | Created |
|----|---------|-------------|--------------|--------|------|---------|
| PROJ-13 | Evaluation-Testlauf | Gold-Testfälle aus `evaluation/` durchrechnen und mit der erwarteten Lösung vergleichen. Vormerkung (2026-10-14, Umsetzung später): `pestphp/pest-plugin-evals` prüfen (Packagist, v5.1.0 vom 2026-09-23, verlangt PHP ^8.4 und Pest ^5.2, passt zum Projekt): fachliche Qualität der KI-Antworten messen, auch mit einem zweiten Modell als Prüfer (LLM-as-a-Judge), Fälle schrittweise aus echten Tickets ablegen; Ziel ist, Verschlechterungen nach Änderungen an Prompt, Modell oder Wissensbasis frühzeitig zu erkennen | PROJ-9 | Roadmap | – | 2026-10-02 |
| PROJ-15 | Benutzerverwaltung mit Login | Anmeldung und Benutzerverwaltung, Namensliste in der Datenbank. Entscheidung 2026-10-18: kein eigenes Passwortsystem, Anmeldung später per SSO (Details offen) | PROJ-5 | Roadmap | – | 2026-10-02 |
| PROJ-17 | Zweiter LLM-Anbieter | Weiteren Anbieter anbinden und per Konfiguration umschalten | PROJ-9 | Roadmap | – | 2026-10-02 |
| PROJ-18 | Historien-Auswertung | Zwei Jahre Zammad-Tickets nach Fallmustern und Testset-Kandidaten auswerten. Zurückgestellt (2026-10-14): Zugang zur Zammad-Datenbank und Auswertung stehen in keinem Verhältnis zum Nutzen; Wissenslücken werden stattdessen an eingehenden Anfragen geschlossen (PROJ-12, PROJ-45), Testfälle für PROJ-13 können aus echten Fällen nach und nach entstehen | PROJ-6 | Zurückgestellt | – | 2026-10-02 |
| PROJ-19 | Antwort nach Zammad übergeben | Antwortentwurf mit Signatur (Vorlage mit {Name} in den Einstellungen) nach Klick auf „Antwort versenden“ im Namen des Mitarbeiters über dessen Zammad-Token (je Mitarbeiter in der `.env`) als E-Mail im Ticket senden, Betreff und Text änderbar, Status danach „schließen“ oder „Erinnerung in 1/3/7 Tagen“; Senden zählt für die Statistik wie Kopieren (unverändert/angepasst, Bewertung) und ist als „gesendet“ kenntlich; Sperre bei neuerer Nachricht und gegen Doppelversand; ohne Token bleibt „Kopieren“; kein Versand im Testmodus und ohne Ticket; Namensliste mit Nachnamen. Kein eigenes Login (künftig SSO) | PROJ-5, PROJ-6, PROJ-10, PROJ-27 | Architected | [Spec](PROJ-19-antwort-nach-zammad-uebergeben.md) | 2026-10-02 |
| PROJ-20 | Automatische Analyse bei Eingang | Neue Tickets ohne manuellen Anstoß vorab analysieren | PROJ-11 | Roadmap | – | 2026-10-02 |
| PROJ-21 | Bildanalyse | Reklamationsfotos vom LLM mitbewerten lassen | PROJ-9 | Roadmap | – | 2026-10-02 |
| PROJ-22 | Semantisches Retrieval | Knowledge-Auswahl um Embeddings ergänzen | PROJ-4 | Roadmap | – | 2026-10-02 |
| PROJ-26 | Mobiles Menü | Burger-Symbol rechts in der Kopfleiste unter 768 px, Menüfläche mit denselben Einträgen wie die Seitenleiste, Schließen per Eintrag, Symbol, Tippen daneben oder Escape, Fokusführung | PROJ-1 | Planned | [Spec](PROJ-26-mobiles-menue.md) | 2026-10-05 |
| PROJ-29 | Ticketbearbeitung in der App | Die App wird zur bevorzugten Arbeitsoberfläche neben Zammad: lesen, antworten, interne Notiz, Status und Zuständigkeit direkt aus der App nach Zammad, damit nur noch selten in Zammad gewechselt und nicht mehr hin und her kopiert werden muss; Zammad bleibt das führende System; berührt PROJ-19 und die Non-Goals im PRD | PROJ-6, PROJ-15, PROJ-19 | Roadmap | – | 2026-10-05 |
| PROJ-34 | Angebotskalkulation | Preise für Angebote in der App berechnen: gepflegte Preislisten, Staffeln, Rabatte je Kundengruppe und Versandkosten als Daten, Berechnung durch die App (nicht durch die KI), Ergebnis als geprüfter Fakt für den Angebotsentwurf. Das Formulieren von Angeboten ohne Kalkulation ist seit 2026-10-09 Teil von PROJ-9 (Kategorie `quote-request`) | PROJ-9, PROJ-11 | Roadmap | – | 2026-10-09 |
| PROJ-46 | Aufgabenteilung Produktfakten und Regeln im Formular | Nach der Anbindung von ProductForge (PROJ-38) neu festlegen, wie das Formular und die Hinweise mit der Aufteilung umgehen: Produktfakten kommen aus ProductForge, die Wissensdatenbank unter `/knowledge` hält nur allgemeine Regeln, Befugnisse, Kulanz und Abläufe (auch produktgruppenübergreifend). Zu klären: was bleibt Pflichtangabe (heute: Wissensbereich, „Produkt ohne hinterlegtes Wissen“ oder „Kein Produktbezug“), wie sich Wissensbereich und Katalogprodukt zueinander verhalten, wann eine Wissenslücke gemeldet wird und wie Hinweise und Lückenmeldungen eindeutig benennen, welches Wissen gemeint ist (Wissensdatenbank oder Produktkatalog) | PROJ-38, PROJ-42, PROJ-45 | Roadmap | – | 2026-10-17 |
| PROJ-47 | Produktwissen aus `/knowledge` nach ProductForge migrieren | Das vorhandene Produktwissen unter `knowledge/products/` (2D-/3D-Glasfoto, Zaubertasse, Fototasse, Lunchbox, Essbare Fotos) je Datei prüfen und trennen: Produktfakten (Technik, Maße, Lieferumfang, Pflege, Warnhinweise, produktspezifisches Servicewissen) wandern in ProductForge, Regeln und Entscheidungen bleiben in `knowledge/` ohne Produktfakten; Übersicht je Datei, Reihenfolge der Migration, Umgang mit `order_keywords` und `customer_terms` (Wissensbereich-Vorschlag) und mit Dokumenten, die auf Produkt-Slugs verweisen; Abgleich mit PROJ-46 | PROJ-38, PROJ-46 | Roadmap | – | 2026-10-17 |
| PROJ-51 | Kundensignatur je Absender | Der Mitarbeiter markiert in der Ticketansicht „das ist die Signatur dieses Absenders“; sie wird künftig eingeklappt. Ausgelagert aus PROJ-27: eigene Ablage je Absender, Datenschutz, Aufbewahrung, Löschen | PROJ-6, PROJ-27 | Roadmap | – | 2026-10-17 |
<!-- Add features above this line -->

## Archive: completed features

Approved features are part of the app. Their detailed specs (user stories, decisions, edge cases, QA results) are in `features/archive/`, grouped by theme in one summary each. Read the summary first; open a single spec only when working on that feature (`/refine`, bug fix).

| Theme | Features | Content |
|-------|----------|---------|
| [Grundgerüst, Nutzerauswahl, Seitenleiste](archive/THEMA-grundgeruest.md) | PROJ-1, 5, 25, 41 | Layout im LOOXIS-Design, Nutzerauswahl, „Über die App“, Statistik in der Seitenleiste |
| [Knowledge Base und Wissensabruf](archive/THEMA-knowledge-base.md) | PROJ-2, 3, 4, 23, 24, 36, 37 | Markdown-Wissen einlesen und prüfen, Auswahl nach Geltungsbereich, gezielter Abruf, KI-Router, Entwurfs-Wissen bestätigen, Knowledge-Übersicht |
| [Tickets, Bestellungen, Verlauf](archive/THEMA-tickets-bestellungen.md) | PROJ-6, 7, 8, 43, 48, 52, 53 | Zammad-Ticket laden, Bestellung aus EOCS, Kundennachricht oben, Verlauf für die Analyse, Reklamationsnummer, zusammengehörige Tickets |
| [Analyse, Ergebnis, Rückmeldung](archive/THEMA-analyse.md) | PROJ-9, 10, 11, 12, 30, 39, 44, 45 | Fallanalyse per LLM, Ergebnisansicht, Protokoll, Feedback und Wissenslücken, interne Abläufe, schrittweise Fallbearbeitung |
| [Frage stellen und Formular](archive/THEMA-frage-stellen-formular.md) | PROJ-16, 40, 42 | Wissensabfrage ohne Ticket, Pflichtangaben und Produktsuche im Vorschlagsformular |
| [Produkte und Produktinformationen](archive/THEMA-produkte.md) | PROJ-33, 38 | Produktvorschlag aus Kundenbegriffen, Produktinformationen aus ProductForge |
| [Übersetzung, Testmodus, Admin-Übersichten](archive/THEMA-uebersetzung-testmodus-uebersichten.md) | PROJ-14, 28, 32, 35 | Übersetzung, Ticket zurückspulen, Analysen- und Ticket-Übersicht |
| [Einstellungen](archive/THEMA-einstellungen.md) | PROJ-27, 31, 49, 50 | Mail-Anzeige, Prompts und Modell, Erkennungsregeln, Kundengruppen und Werte |

Build order: the ID order. Exceptions that were built out of order: PROJ-24 right after PROJ-3; PROJ-25 and PROJ-26 are independent; PROJ-28 right after PROJ-11; PROJ-30 after PROJ-10; PROJ-32 right after PROJ-9; PROJ-33 after PROJ-11.

## Next Available ID: PROJ-54
