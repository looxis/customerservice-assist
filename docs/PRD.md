# Product Requirements Document

> Ausführlicher Projektkontext: `docs/INIT_PROJECT_DESCRIPTION.md`. Feste Vorgabe für die Knowledge Base: `docs/KNOWLEDGE_BASE_DESIGN.md`.

## Vision
Customer Service Assist ist eine interne Web-App neben Zammad. Sie bereitet für ein Kundenservice-Ticket einen fachlich begründeten Vorschlag vor: Bewertung, empfohlene Maßnahme, Befugnis, nötige interne Klärungen und einen Antwortentwurf (der bei einer blockierenden internen Klärung erst nach deren Ergebnis entsteht). Grundlage sind das Ticket aus Zammad, die Bestelldaten aus EOCS und eine versionierte Knowledge Base im selben Repository.

Die App entscheidet und sendet nichts selbst. Ein Mensch prüft jeden Vorschlag und gibt jede Kundenantwort frei. Ziel ist, dass das Unternehmenswissen direkt in der Bearbeitung ankommt, statt in den Köpfen einzelner Personen zu stecken.

## Target Users
- **Stammkraft im Kundenservice:** bearbeitet den Großteil der Tickets. Braucht weniger Rückfragen bei erfahrenen Kollegen und weniger Folge-Mails bei Reklamationen.
- **Aushilfen und Vertretungen** (Peaks, Wochenende, Urlaub, Krankheit): kennen Produkte, Produktionsprozesse und Befugnisse nicht ausreichend und sollen nicht aktiv in Dokumentation suchen müssen.
- **Erfahrene Mitarbeiter und Geschäftsführung:** werden heute unnötig für Eskalationen gebraucht. Ihr Wissen soll in der Knowledge Base stehen.

Seit 2026-10-09 unterstützt die App auch Angebots- und Auftragsanfragen, die per E-Mail über Zammad eingehen: Rückfragen bei unklaren Anfragen und das Formulieren von Angebot oder Auftragsbestätigung nach den Regeln der Knowledge Base.

Der größte Zeitaufwand entsteht bei Reklamationen (ca. 40 % der Tickets), vor allem bei unberechtigten Reklamationen, falschen Kundenerwartungen und Fällen, die Produkt- oder Prozesswissen zur Erklärung brauchen. WISMO und Spam sind bereits über n8n automatisiert und nicht Teil dieses Produkts.

## Core Features (Roadmap)

| Feature | Status |
|---------|--------|
| App-Grundgerüst mit LOOXIS-Design (Layout, Basis-Komponenten) | Approved |
| Knowledge-Authoring-Kit (Ordnerstruktur, Vorlagen, Anleitung, KI-Skill) | Approved |
| Knowledge Base einlesen und prüfen (Frontmatter validieren, Prüfbefehl, ID-Übersicht) | Approved |
| Knowledge-Übersicht (Seite mit Dokumenten, Prüfergebnis, ID-Übersicht zum Kopieren) | Approved |
| Deterministische Knowledge-Auswahl | Approved |
| Seite „Über die App“ (Ziel, Ablauf, Grenzen, Stand, Entwicklerteil) | Approved |
| Nutzerauswahl (Dropdown, feste Namensliste, einmal pro Browser, dauerhaft sichtbar) | Approved |
| Zammad-Ticket per Ticketnummer laden und Verlauf anzeigen | Approved |
| Bestellung aus EOCS laden (automatische Erkennung, manuelle Bestellnummer) | Approved |
| Bestelldaten manuell ergänzen (Fallback) – aufgegangen in der Fallanalyse | Approved |
| Fallanalyse per LLM mit Mitarbeiterkontext und fester Ergebnisstruktur | Approved |
| Ergebnisansicht mit bearbeitbarem Antwortentwurf, Kopieren und einsehbaren Quellen | Approved |
| Analyse-Protokoll und Verlauf je Ticket | Approved |
| Feedback und Wissenslücke melden | Approved |
| Übersetzung fremdsprachiger Nachrichten ins Deutsche, einmal erstellt und gespeichert | Approved |
| Interne Arbeitsabläufe zum Fall (Dokumenttyp `procedure`, Vorschläge nach der Analyse) | Approved |
| Testmodus für Admins: beantwortete Tickets auf einen früheren Stand zurückspulen | Approved |
| Produktvorschlag aus Kundenbegriffen (frühere Namen, Synonyme im Tickettext erkennen) | Approved |
| Evaluation: Gold-Testset automatisch gegen die App laufen lassen | Roadmap |
| Mobiles Menü (Burger-Menü in der Kopfleiste) | Zurückgestellt |
| Einstellungen: KI-Modell und Prompts (Modell wählbar, Prompts änderbar und versioniert) | Approved |
| Einstellungsseite: Grundlage und Mail-Anzeige (PROJ-27), danach Prompts und Modell (PROJ-31), Erkennungsregeln (PROJ-49), Kundengruppen/Kategorien/Vorgänge (PROJ-50, Spec geschrieben); ansehen für alle, ändern nur Admins, mit Verlauf | Approved |
| Ticketbearbeitung in der App (Antworten, Notizen, Status direkt nach Zammad) | Roadmap |
| Analysen-Übersicht für Admins: eine Zeile je Analyse mit Feedback, Kopier-Stand, Filtern und Kennzahlen | Approved |
| Benutzerverwaltung und Anmeldung (kein eigenes Passwortsystem, später SSO) | Roadmap |
| Tickettext manuell einfügen (Fallback ohne Zammad) – aufgegangen in der Wissensabfrage ohne Ticket | Approved |
| Zweiter LLM-Anbieter zum Vergleich | Zurückgestellt |
| Entwurfs-Wissen per Klick bestätigen (`draft` → `active`) | Approved |
| Auswertung der Zammad-Historie (Fallmuster, Testset-Kandidaten) | Zurückgestellt |
| Antwort direkt aus der App nach Zammad übergeben (Versand nur nach Klick, im Namen des Mitarbeiters) | Approved |
| Automatische Analyse bei Ticket-Eingang | Roadmap |
| Bildanalyse von Reklamationsfotos | Roadmap |
| Semantisches Retrieval (RAG / Vektordatenbank), erst ab mehreren Hundert Wissensdokumenten sinnvoll | Zurückgestellt |
| Angebotskalkulation (Preislisten, Staffeln, Berechnung durch die App) | Roadmap |
| Ticket-Übersicht für Admins: abgerufene Tickets mit Stand (übersetzt, analysiert, Entwurf kopiert) | Approved |
| Gezielter Wissensabruf: Wissen vor der Analyse nach Anliegen und Bedingungen abrufen (statt alles Passende mitzusenden) | Approved |
| Router für den Wissensabruf (KI-Router ergänzt den gezielten Abruf um sinngleiche Formulierungen; Vorauswahl aus Katalogzeilen, abschaltbar) | Approved |
| Produktinformationen aus ProductForge (LOOXIS Content Studio) abrufen: Datenblattfelder und Kundenservice-Wissen je Produkt, Produktsuche, Zuordnung zur Bestellposition (statt Markdown-Dateien für alle Produktdetails) | Approved |
| Aufgabenteilung Produktfakten (ProductForge) und Regeln (`/knowledge`) im Formular, bei Hinweisen und Wissenslücken | Approved |
| Produktvarianten anbieten (weitere Größen/Varianten eines Produkts aus EOCS, später Content Studio) | Approved |
| Diagnose zum Vorschlag (Admin): warum dieses Ergebnis, Vorschlag zur Wissensänderung, später Änderung und Commit aus der App | Roadmap |
| Vorläufiges Wissen (befristete Testphase mit Überprüfung) und Meldung veralteten Wissens durch Mitarbeiter für alle Wissensdokumente | Approved |
| Produktwissen aus `knowledge/products/` nach ProductForge migrieren (Produktfakten raus, Regeln bleiben) | Approved |
| Schrittweise Fallbearbeitung: Analyse erkennt, wann zuerst eine interne Klärung nötig ist (kein Antwortentwurf, Auswahl oder freies Ergebnis, Fortsetzung der Analyse); Interne To-dos und Interne Abläufe zusammengeführt; „Wissenslücken“ nur für fehlende Regeln | In Progress |
| Wissensabfrage ohne Ticket: Frage, Anrufnotiz oder eingefügter Kundentext, Vorschlag im selben Aufbau wie die Ticketanalyse (Antwortentwurf nur auf Wunsch); Oberfläche „Frage stellen“ und „Vorschlag“ statt „Analyse“ | Approved |
| Formular „Vorschlag erstellen“: Pflichtangaben (Kundengruppe, Produkt, auch „Produkt ohne hinterlegtes Wissen“), Produktsuche, Erweiterter Modus | Approved |
| Ticketseite: letzte Kundennachricht ganz oben, Verlauf unten | Approved |
| Sichtbar machen, welches Wissen wegen Produkt oder Kundengruppe nicht geprüft wurde | Approved |
| Produkt ohne hinterlegtes Wissen automatisch als Wissenslücke melden (mit Angaben des Mitarbeiters, nach Häufigkeit für Admins) | Approved |
| Häufige Produktfragen auswerten: Detailfragen ohne Produktangabe still zählen statt als Wissenslücke melden, eigene Admin-Seite ab drei Tickets in drei Monaten | Approved |
| Direkte Links zu internen Tools in Arbeitsabläufen (EOCS-Auftrag, Zammad-Ticket, Vanilo, Mailcoach, EOCS-Kundenliste) | Approved |
| Hilfe für das Feld „Zusätzliche Informationen“: lageabhängige Beispiele, Hinweis zum Formulieren, Prompt-Regel für Fakten, Entscheidungen und Wünsche an die Antwort | Approved |
| Bildanhänge eines Tickets in der App ansehen (Zwischenspeicher 30 Tage, Overlay mit Zoom) | Approved |
| Anhänge (PDF, Bilder, Screenshot per Strg+V) an die Antwort anhängen | In Review |

Feature-IDs, Abhängigkeiten und Baureihenfolge stehen in `features/INDEX.md`.

## Success Metrics
- **Langfristiges Hauptziel:** Eine neue Aushilfe bearbeitet nach 1–2 Stunden Einführung rund 95 % der typischen Fälle fachlich korrekt.
- **MVP, gemessen am Klick-Feedback:** Anteil „unverändert nutzbar" plus „leicht angepasst" mindestens 70 % nach vier Wochen Einsatz; Anteil „verworfen" als Gegenwert.
- **Gegen die Baseline** (mindestens 50 historische Reklamationstickets, nach festem Raster bewertet): fachliche Fehlerquote bei Reklamationen, unnötige Eskalationen, Anzahl E-Mails pro Reklamationsfall.
- **Entlastung:** Zeitaufwand erfahrener Mitarbeiter und der Geschäftsführung für Rückfragen.
- **Wissenslücken:** Anzahl gemeldeter Lücken und ihre Entwicklung über die Zeit.

## Constraints
- **Termin:** nutzbarer Stand bis Mitte November 2026, vor dem Weihnachts-Peak. P0 ist deshalb hart auf den Kernablauf begrenzt.
- **Team:** ein Entwickler mit KI-Unterstützung, der auch die Knowledge-Inhalte selbst verfasst.
- **Knowledge-Aufbau:** parallel zur Entwicklung und entlang echter Fälle, nicht flächendeckend: zuerst wenige allgemeine Regeln, dann Produkt- und Reklamationsregeln zu ausgewählten echten Fällen, die direkt in der App gegengeprüft werden. Das Verfassen muss auch außerhalb dieses Repos mit einem KI-Chat möglich sein; Grundlage ist `docs/KNOWLEDGE_AUTHORING_GUIDE.md`.
- **Tech-Stack:** unverändert aus dem Starter Kit (Laravel 13, Blade, Tailwind v4, Alpine.js, MySQL über Eloquent, Pest).
- **Dev: Laravel Sail (Docker).** Alle Befehle über `./vendor/bin/sail ...`.
- **No authentication im MVP.** Die App ist nur im internen Netz/VPN erreichbar, zusätzlich schützt ein gemeinsames Passwort vor der App (Webserver-Konfiguration, Umsetzung in `/deploy`). Eine Benutzerverwaltung folgt; ein eigenes Passwortsystem gibt es nicht, die Anmeldung soll später per SSO erfolgen (Details offen).
- **Nutzererfassung im MVP:** fest hinterlegte Namensliste (Cara, Etienne, Johannes, Kerstin, Nele, Thomas), Auswahl einmal pro Browser per Dropdown, gewählter Nutzer dauerhaft in der UI sichtbar. Die Liste wandert mit der Benutzerverwaltung in die Datenbank.
- **LLM:** Start mit OpenAI per API. Die App bleibt anbieterneutral, Modell und Anbieter müssen austauschbar und vergleichbar sein. Ticketinhalte mit Kundendaten gehen an den Anbieter; ein Auftragsverarbeitungsvertrag ist Voraussetzung.
- **Knowledge Base:** Struktur fest nach `docs/KNOWLEDGE_BASE_DESIGN.md`, Ablage in `knowledge/` als Markdown mit YAML-Frontmatter, Git als Versionshistorie. Gold-Testfälle liegen getrennt in `evaluation/` und dienen nie als Retrieval-Wissen. Fachliche Regeln stehen nicht im Prompt.
- **Geltungsbereich von Knowledge:** Dokumente können über die Frontmatter-Felder `customer_types` (`b2c`, `b2b-reseller`, `b2b-pro`, `b2b-whitelabel`) und `sales_channels` (`looxis-de`, `amazon`, `fachhaendler`, `looxis-pro`, `masterpics`) auf Kundenart und Vertriebskanal eingeschränkt sein; leer heißt „gilt für alle". Der Mitarbeiter wählt vor der Analyse eine von sechs Kundengruppen (Privatkunde looxis.de, Privatkunde Amazon, Foto-Fachhändler/Reseller, LOOXIS-Pro, White-Label-Kunde masterpics, noch unklar) und das betroffene Produkt; die App schlägt beides aus der Bestellung vor, soweit möglich. Kundengruppe und Produkt legen den Geltungsbereich fest (PROJ-4); welche Dokumente des Geltungsbereichs in den Prompt gelangen, entscheidet der gezielte Wissensabruf nach Anliegen, Auslösern und Bedingungen (PROJ-36), damit der Prompt unabhängig von der Größe der Knowledge Base klein bleibt und verbindliche Regeln dennoch zuverlässig greifen. Eine automatische Fachhändler-Erkennung bleibt Non-Goal.
- **Entwurfs-Wissen:** Abweichend vom Design-Dokument („nur `active` wird produktiv verwendet") verwendet die App auch `draft`-Dokumente und kennzeichnet sie im Ergebnis als Entwurfs-Wissen. So lässt sich neues Wissen an fiktiven oder echten Fällen testen; was gute Ergebnisse liefert, bestätigt ein Admin per Klick als `active`. `deprecated` wird nie verwendet. Entwurfs-Wissen ist bewusst für alle sichtbar, auch für Aushilfen: Ihr unvoreingenommener Blick soll Schwächen in Entwürfen aufdecken. Offen und in den Specs zu klären: wie der Klick die Datei im Git-Repository ändert und wer ohne Login als Admin gilt.
- **Design system: see `docs/design-system.md`** (LOOXIS Design System). Die Tokens sind für Tailwind v3 notiert und werden auf Tailwind v4 (`@theme` in `resources/css/app.css`) übertragen.
- **Sprache:** Oberfläche deutsch, Antwortentwurf in der Sprache der Kundenanfrage.
- **Offener Punkt:** Der Umfang der EOCS-API ist ungeklärt. Die Anbindung bleibt P0; das manuelle Ergänzen von Bestelldaten sichert den Termin ab.
- **Qualitätsprinzipien:** kein Erfinden fehlender Fakten, „unklar / noch nicht entscheidbar" ist ein gewünschtes Ergebnis, jede Empfehlung verweist auf Knowledge-IDs, jede Analyse ist reproduzierbar protokolliert (Knowledge-Commit, Prompt-Version, Modell).

## Non-Goals
- automatisches Versenden von Kundenantworten (Versand nur durch ausdrücklichen Klick des Mitarbeiters, PROJ-19)
- Ersatz von Zammad oder eine eigene gemeinsame Inbox
- automatische Bearbeitung beim E-Mail-Eingang
- automatische Änderungen an Bestellungen, Gutschriften, Erstattungen oder Ersatzaufträge
- visuelle KI-Analyse von Reklamationsfotos (der Mitarbeiter beschreibt das Ergebnis im Kontextfeld)
- Fachhändler-Erkennung, Customer Memory, Angebotskalkulation im MVP (das Formulieren von Angeboten und Auftragsbestätigungen mit Rückfragen ist seit 2026-10-09 enthalten; Preise nennt die App nur aus dem Wissen oder aus Angaben des Mitarbeiters, sonst als Platzhalter; die Kalkulation ist als P2 eingeplant)
- semantische Vektorsuche als Voraussetzung
- automatisches Lernen aus Mitarbeiterkorrekturen
- komplexe Analytics-Dashboards
- WISMO-Automatisierung, Spam-Erkennung, Telefonie (bestehende n8n-Prozesse bleiben unberührt)

---

Use `/write-spec` to create detailed feature specifications for each item in the roadmap above.
