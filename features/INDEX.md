# Feature Index

> Central tracking for all features. Updated by skills automatically.

## Status Legend
- **Roadmap** - `/init` done, feature identified in feature map, no spec file yet
- **Planned** - `/write-spec` done, full spec written, architecture not yet designed
- **Architected** - `/architecture` done, tech design approved, ready to build
- **In Progress** - `/frontend` or `/backend` active or completed, not yet in QA
- **In Review** - `/qa` active, testing in progress
- **Approved** - `/qa` passed, no critical/high bugs, ready to deploy
- **Deployed** - `/deploy` done, live in production

## Features

The ID order is the recommended build order (exceptions: PROJ-24 was added later and should be built right after PROJ-3; PROJ-25 and PROJ-26 are independent and can be built at any time; PROJ-28 is P0 and should be built right after PROJ-11; PROJ-30 is P0 and is built after PROJ-10, its document type was built ahead; PROJ-32 is built right after PROJ-9 to test it on answered tickets; PROJ-33 is P0 and built after PROJ-11). No authentication in the MVP (see `docs/PRD.md`, Constraints); login arrives with PROJ-15.

| ID | Feature | Description | Priority | Dependencies | Status | Spec | Created |
|----|---------|-------------|----------|--------------|--------|------|---------|
| PROJ-1 | App-Grundgerüst mit LOOXIS-Design | Layout-Shell, Design-Tokens und Basis-Komponenten nach `docs/design-system.md` | P0 | None | Approved | [Spec](PROJ-1-app-grundgeruest-looxis-design.md) | 2026-10-02 |
| PROJ-2 | Knowledge-Authoring-Kit | Ordnerstruktur `knowledge/`, Einstiegsseite, Vorlagen je Dokumenttyp, Anleitung (`docs/KNOWLEDGE_AUTHORING_GUIDE.md`), KI-Skill im Repo als Ausweichweg | P0 | None | Approved | [Spec](PROJ-2-knowledge-authoring-kit.md) | 2026-10-02 |
| PROJ-3 | Knowledge Base einlesen und prüfen | Markdown-Dateien lesen, Frontmatter validieren, Status `draft`/`active`/`deprecated` erkennen, Git-Stand erfassen, Prüfbefehl mit Fehlern und Warnungen, automatische Übersicht vergebener IDs und Schlagwörter, Fingerabdruck je Dokument | P0 | PROJ-2 | Approved | [Spec](PROJ-3-knowledge-base-einlesen-und-pruefen.md) | 2026-10-02 |
| PROJ-4 | Knowledge-Auswahl | Dokumente deterministisch nach Kundengruppe (Kundenart und Kanal) und Produkt auswählen; kein Filter nach Anfrageart; `draft`-Dokumente werden mitverwendet und gekennzeichnet; Vorschläge aus der Bestellung; Vorschau-Befehl | P0 | PROJ-3 | Approved | [Spec](PROJ-4-knowledge-auswahl.md) | 2026-10-02 |
| PROJ-5 | Nutzerauswahl | Dropdown mit fester Namensliste, Auswahl einmal pro Browser, Nutzer dauerhaft sichtbar | P0 | PROJ-1 | Approved | [Spec](PROJ-5-nutzerauswahl.md) | 2026-10-02 |
| PROJ-6 | Zammad-Ticket laden | Ticketnummer eingeben, Ticket und Verlauf laden und anzeigen, Fehlerzustände behandeln | P0 | PROJ-1 | Approved | [Spec](PROJ-6-zammad-ticket-laden.md) | 2026-10-02 |
| PROJ-7 | Bestellung aus EOCS laden | Bestellnummer im Ticket erkennen, EOCS abrufen, manuelle Bestellnummer, Bestelldaten anzeigen | P0 | PROJ-6 | Approved | [Spec](PROJ-7-bestellung-aus-eocs-laden.md) | 2026-10-02 |
| PROJ-8 | Bestelldaten manuell ergänzen | Aufgegangen in PROJ-9: Bestelldaten von Hand im Analyse-Formular, wenn keine EOCS-Bestellung geladen ist | P0 | PROJ-6 | Approved | [Spec](PROJ-9-fallanalyse-per-llm.md) | 2026-10-02 |
| PROJ-9 | Fallanalyse per LLM | Ticket, Bestelldaten, Mitarbeiterkontext und Knowledge an das LLM senden, feste Ergebnisstruktur validieren (inkl. Fallkategorie und empfohlener Vorgänge aus der Vorgangsliste für PROJ-30), „unklar" zulassen | P0 | PROJ-4, PROJ-5, PROJ-6, PROJ-7 | Approved | [Spec](PROJ-9-fallanalyse-per-llm.md) | 2026-10-02 |
| PROJ-10 | Ergebnisansicht | Alle Ergebnisteile anzeigen, Antwortentwurf bearbeiten und kopieren, Knowledge-Quellen einsehen, Hinweis auf verwendetes Entwurfs-Wissen | P0 | PROJ-9 | Approved | [Spec](PROJ-10-ergebnisansicht.md) | 2026-10-02 |
| PROJ-11 | Analyse-Protokoll und Verlauf | Jede Analyse reproduzierbar speichern, frühere Analysen je Ticket anzeigen, erneut analysieren | P0 | PROJ-5, PROJ-9 | Approved | [Spec](PROJ-11-analyse-protokoll-und-verlauf.md) | 2026-10-02 |
| PROJ-12 | Feedback und Wissenslücke | Vier-Stufen-Feedback mit optionalem Kommentar, Wissenslücke zur Analyse melden | P0 | PROJ-10, PROJ-11 | Approved | [Spec](PROJ-12-feedback-und-wissensluecke.md) | 2026-10-02 |
| PROJ-13 | Evaluation-Testlauf | Gold-Testfälle aus `evaluation/` durchrechnen und mit der erwarteten Lösung vergleichen | P1 | PROJ-9 | Roadmap | – | 2026-10-02 |
| PROJ-14 | Analysen-Übersicht | Liste aller Analysen, Feedbacks und Wissenslücken mit Filter | P1 | PROJ-11, PROJ-12 | Roadmap | – | 2026-10-02 |
| PROJ-15 | Benutzerverwaltung mit Login | Login über Laravel Fortify, Admin legt Nutzer an, Namensliste in der Datenbank | P1 | PROJ-5 | Roadmap | – | 2026-10-02 |
| PROJ-16 | Tickettext manuell einfügen | Analyse ohne Zammad-Abruf mit eingefügtem Tickettext | P1 | PROJ-9 | Roadmap | – | 2026-10-02 |
| PROJ-17 | Zweiter LLM-Anbieter | Weiteren Anbieter anbinden und per Konfiguration umschalten | P1 | PROJ-9 | Roadmap | – | 2026-10-02 |
| PROJ-18 | Historien-Auswertung | Zwei Jahre Zammad-Tickets nach Fallmustern und Testset-Kandidaten auswerten | P2 | PROJ-6 | Roadmap | – | 2026-10-02 |
| PROJ-19 | Antwort nach Zammad übergeben | Antwortentwurf direkt als Entwurf oder Antwort ins Ticket schreiben | P2 | PROJ-10, PROJ-15 | Roadmap | – | 2026-10-02 |
| PROJ-20 | Automatische Analyse bei Eingang | Neue Tickets ohne manuellen Anstoß vorab analysieren | P2 | PROJ-11 | Roadmap | – | 2026-10-02 |
| PROJ-21 | Bildanalyse | Reklamationsfotos vom LLM mitbewerten lassen | P2 | PROJ-9 | Roadmap | – | 2026-10-02 |
| PROJ-22 | Semantisches Retrieval | Knowledge-Auswahl um Embeddings ergänzen | P2 | PROJ-4 | Roadmap | – | 2026-10-02 |
| PROJ-23 | Entwurfs-Wissen bestätigen | Ein Admin stellt ein `draft`-Dokument nach guten Testergebnissen per Klick auf `active` | P1 | PROJ-3, PROJ-10 | Roadmap | – | 2026-10-02 |
| PROJ-24 | Knowledge-Übersicht | Seite in der App: alle Dokumente mit ID, Typ, Status und Geltungsbereich, Fehler und Warnungen je Datei, Kopier-Button für die ID-Übersicht | P0 | PROJ-1, PROJ-3 | Approved | [Spec](PROJ-24-knowledge-uebersicht.md) | 2026-10-02 |
| PROJ-25 | Über die App | Seite „Über die App“ unten in der Seitenleiste: Ziel, Ablauf mit Hinweis „in Arbeit“, Beispiel, Grenzen, Wissen ergänzen, Info-Kasten mit App- und Wissensstand, abgesetzter Entwicklerteil mit Tech Stack | P0 | PROJ-1, PROJ-3 | Approved | [Spec](PROJ-25-ueber-die-app.md) | 2026-10-05 |
| PROJ-26 | Mobiles Menü | Burger-Symbol rechts in der Kopfleiste unter 768 px, Menüfläche mit denselben Einträgen wie die Seitenleiste, Schließen per Eintrag, Symbol, Tippen daneben oder Escape, Fokusführung | P1 | PROJ-1 | Planned | [Spec](PROJ-26-mobiles-menue.md) | 2026-10-05 |
| PROJ-27 | Einstellungen für die Mail-Anzeige | Seite „Einstellungen" in der App mit vier Listen, die die Ticketansicht (PROJ-6) statt der Konfiguration liest: zugelassene Adressen (je Eintrag „als Text anzeigen" oder „klickbar lassen"), unsere Signaturen in allen Varianten und Sprachen samt Syntax (werden bei unseren Nachrichten ausgeblendet; bisher gesammelte Varianten mit Namen in der PROJ-6-Spec) Textbausteine, ab denen der Rest einer Mail ausgeblendet wird, Kundensignaturen je Absender (Mitarbeiter markiert in der Ticketansicht „das ist die Signatur dieses Absenders", sie wird künftig eingeklappt), sowie die Formate der Bestellnummern je Kanal (Aufbau, Beispiel, Kanal; heute fest in `app/Orders/OrderNumberFormat.php`), mit Prüfung jedes Formats gegen Beispiele vor dem Speichern |  P1 | PROJ-6, PROJ-7 | Roadmap | – | 2026-10-05 |
| PROJ-28 | Übersetzung von Nachrichten | Fremdsprachige Nachrichten (z. B. Italienisch, Französisch über Amazon) zusätzlich auf Deutsch anzeigen, Original bleibt; jede Übersetzung wird einmal erstellt und gespeichert, damit nicht bei jedem Aufruf neu KI-Kosten entstehen; Antwortentwurf weiter in der Sprache des Kunden; wie die Übersetzung in der Oberfläche erscheint, ist noch zu klären | P0 | PROJ-6, PROJ-9, PROJ-11 | Approved | [Spec](PROJ-28-uebersetzung-von-nachrichten.md) | 2026-10-05 |
| PROJ-29 | Ticketbearbeitung in der App | Die App wird zur bevorzugten Arbeitsoberfläche neben Zammad: lesen, antworten, interne Notiz, Status und Zuständigkeit direkt aus der App nach Zammad, damit nur noch selten in Zammad gewechselt und nicht mehr hin und her kopiert werden muss; Zammad bleibt das führende System; berührt PROJ-19 und die Non-Goals im PRD | P2 | PROJ-6, PROJ-15, PROJ-19 | Roadmap | – | 2026-10-05 |
| PROJ-30 | Interne Arbeitsabläufe zum Fall | Neuer Dokumenttyp `procedure` (Schritt-für-Schritt-Anleitung für Menschen); nach der Analyse passende Abläufe vorschlagen (Kundengruppe, Kanal, Produkt, Fallkategorie, Vorgang), manuell wählbar, abhakbar, kritische Hinweise hervorgehoben, getrennt vom Antwortentwurf; Dokumenttyp und Prüfung vorgezogen | P0 | PROJ-3, PROJ-4, PROJ-9, PROJ-10 | Approved | [Spec](PROJ-30-interne-arbeitsablaeufe.md) | 2026-10-05 |
| PROJ-31 | Einstellungen: KI-Modell und Prompts | Auf der Seite „Einstellungen": Modell je Aufruf (Analyse, Zusammenfassung) per Freitextfeld wählbar, darüber die aktuell beim Anbieter verfügbaren Modelle mit genauer Bezeichnung; Prompts für Zusammenfassung und Analyse ansehen und ändern, jede Änderung als neue Prompt-Version nachvollziehbar | P1 | PROJ-9, PROJ-11 | Roadmap | – | 2026-10-06 |
| PROJ-32 | Testmodus – Ticket zurückspulen | Admin-Werkzeug (Admin-Liste in der Konfiguration, Schalter je Browser): beantwortete oder geschlossene Tickets auf den Stand einer Kundennachricht zurückspulen; nur Nachrichten bis dahin gehen in Analyse und Zusammenfassung, spätere eingeklappt zum Vergleich; Testläufe gekennzeichnet und von echten Daten getrennt | P0 | PROJ-5, PROJ-6, PROJ-9 | Approved | [Spec](PROJ-32-testmodus-ticket-zurueckspulen.md) | 2026-10-06 |
| PROJ-33 | Produktvorschlag aus Kundenbegriffen | Neues Frontmatter-Feld `customer_terms` in Produktdateien (frühere Produktnamen, Synonyme, Kundenbegriffe wie „Viamant“); die App erkennt sie im Tickettext und schlägt das Produkt im Analyse-Formular vor, getrennt von `order_keywords` (nur Bestellpositionen) | P0 | PROJ-4, PROJ-6, PROJ-9 | Approved | [Spec](PROJ-33-produktvorschlag-aus-kundenbegriffen.md) | 2026-10-07 |

<!-- Add features above this line -->

## Next Available ID: PROJ-34
