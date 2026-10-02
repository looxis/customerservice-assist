# Product Requirements Document

> Ausführlicher Projektkontext: `docs/INIT_PROJECT_DESCRIPTION.md`. Feste Vorgabe für die Knowledge Base: `docs/KNOWLEDGE_BASE_DESIGN.md`.

## Vision
Customer Service Assist ist eine interne Web-App neben Zammad. Sie bereitet für ein Kundenservice-Ticket einen fachlich begründeten Vorschlag vor: Bewertung, empfohlene Maßnahme, Befugnis, fehlende Informationen und einen Antwortentwurf. Grundlage sind das Ticket aus Zammad, die Bestelldaten aus EOCS und eine versionierte Knowledge Base im selben Repository.

Die App entscheidet und sendet nichts selbst. Ein Mensch prüft jeden Vorschlag und gibt jede Kundenantwort frei. Ziel ist, dass das Unternehmenswissen direkt in der Bearbeitung ankommt, statt in den Köpfen einzelner Personen zu stecken.

## Target Users
- **Stammkraft im Kundenservice:** bearbeitet den Großteil der Tickets. Braucht weniger Rückfragen bei erfahrenen Kollegen und weniger Folge-Mails bei Reklamationen.
- **Aushilfen und Vertretungen** (Peaks, Wochenende, Urlaub, Krankheit): kennen Produkte, Produktionsprozesse und Befugnisse nicht ausreichend und sollen nicht aktiv in Dokumentation suchen müssen.
- **Erfahrene Mitarbeiter und Geschäftsführung:** werden heute unnötig für Eskalationen gebraucht. Ihr Wissen soll in der Knowledge Base stehen.

Der größte Zeitaufwand entsteht bei Reklamationen (ca. 40 % der Tickets), vor allem bei unberechtigten Reklamationen, falschen Kundenerwartungen und Fällen, die Produkt- oder Prozesswissen zur Erklärung brauchen. WISMO und Spam sind bereits über n8n automatisiert und nicht Teil dieses Produkts.

## Core Features (Roadmap)

| Priority | Feature | Status |
|----------|---------|--------|
| P0 (MVP) | App-Grundgerüst mit LOOXIS-Design (Layout, Basis-Komponenten) | Roadmap |
| P0 (MVP) | Knowledge-Authoring-Kit (Ordnerstruktur, Vorlagen, Anleitung, KI-Skill) | Roadmap |
| P0 (MVP) | Knowledge Base einlesen und prüfen (Frontmatter validieren, Prüfbefehl) | Roadmap |
| P0 (MVP) | Deterministische Knowledge-Auswahl | Roadmap |
| P0 (MVP) | Nutzerauswahl (Dropdown, feste Namensliste, einmal pro Browser, dauerhaft sichtbar) | Roadmap |
| P0 (MVP) | Zammad-Ticket per Ticketnummer laden und Verlauf anzeigen | Roadmap |
| P0 (MVP) | Bestellung aus EOCS laden (automatische Erkennung, manuelle Bestellnummer) | Roadmap |
| P0 (MVP) | Bestelldaten manuell ergänzen (Fallback) | Roadmap |
| P0 (MVP) | Fallanalyse per LLM mit Mitarbeiterkontext und fester Ergebnisstruktur | Roadmap |
| P0 (MVP) | Ergebnisansicht mit bearbeitbarem Antwortentwurf, Kopieren und einsehbaren Quellen | Roadmap |
| P0 (MVP) | Analyse-Protokoll und Verlauf je Ticket | Roadmap |
| P0 (MVP) | Feedback und Wissenslücke melden | Roadmap |
| P1 | Evaluation: Gold-Testset automatisch gegen die App laufen lassen | Roadmap |
| P1 | Gesamtliste aller Analysen, Feedbacks und Wissenslücken mit Filter | Roadmap |
| P1 | Benutzerverwaltung mit Login (vom Admin gesteuert) | Roadmap |
| P1 | Tickettext manuell einfügen (Fallback ohne Zammad) | Roadmap |
| P1 | Zweiter LLM-Anbieter zum Vergleich | Roadmap |
| P2 | Auswertung der Zammad-Historie (Fallmuster, Testset-Kandidaten) | Roadmap |
| P2 | Antwort direkt aus der App nach Zammad übergeben | Roadmap |
| P2 | Automatische Analyse bei Ticket-Eingang | Roadmap |
| P2 | Bildanalyse von Reklamationsfotos | Roadmap |
| P2 | Semantisches Retrieval | Roadmap |

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
- **No authentication im MVP.** Die App ist nur im internen Netz/VPN erreichbar, zusätzlich schützt ein gemeinsames Passwort vor der App (Webserver-Konfiguration, Umsetzung in `/deploy`). Eine vom Admin gesteuerte Benutzerverwaltung mit Benutzername und Passwort folgt als P1.
- **Nutzererfassung im MVP:** fest hinterlegte Namensliste (Kerstin, Etienne, Nele, Cara, Johannes), Auswahl einmal pro Browser per Dropdown, gewählter Nutzer dauerhaft in der UI sichtbar. Die Liste wandert mit der Benutzerverwaltung in die Datenbank.
- **LLM:** Start mit OpenAI per API. Die App bleibt anbieterneutral, Modell und Anbieter müssen austauschbar und vergleichbar sein. Ticketinhalte mit Kundendaten gehen an den Anbieter; ein Auftragsverarbeitungsvertrag ist Voraussetzung.
- **Knowledge Base:** Struktur fest nach `docs/KNOWLEDGE_BASE_DESIGN.md`, Ablage in `knowledge/` als Markdown mit YAML-Frontmatter, Git als Versionshistorie. Gold-Testfälle liegen getrennt in `evaluation/` und dienen nie als Retrieval-Wissen. Fachliche Regeln stehen nicht im Prompt.
- **Design system: see `docs/design-system.md`** (LOOXIS Design System). Die Tokens sind für Tailwind v3 notiert und werden auf Tailwind v4 (`@theme` in `resources/css/app.css`) übertragen.
- **Sprache:** Oberfläche deutsch, Antwortentwurf in der Sprache der Kundenanfrage.
- **Offener Punkt:** Der Umfang der EOCS-API ist ungeklärt. Die Anbindung bleibt P0; das manuelle Ergänzen von Bestelldaten sichert den Termin ab.
- **Qualitätsprinzipien:** kein Erfinden fehlender Fakten, „unklar / noch nicht entscheidbar" ist ein gewünschtes Ergebnis, jede Empfehlung verweist auf Knowledge-IDs, jede Analyse ist reproduzierbar protokolliert (Knowledge-Commit, Prompt-Version, Modell).

## Non-Goals
- automatisches Versenden von Kundenantworten
- Ersatz von Zammad oder eine eigene gemeinsame Inbox
- automatische Bearbeitung beim E-Mail-Eingang
- automatische Änderungen an Bestellungen, Gutschriften, Erstattungen oder Ersatzaufträge
- visuelle KI-Analyse von Reklamationsfotos (der Mitarbeiter beschreibt das Ergebnis im Kontextfeld)
- Fachhändler-Erkennung, Customer Memory, Angebotskalkulation
- semantische Vektorsuche als Voraussetzung
- automatisches Lernen aus Mitarbeiterkorrekturen
- komplexe Analytics-Dashboards
- WISMO-Automatisierung, Spam-Erkennung, Telefonie (bestehende n8n-Prozesse bleiben unberührt)

---

Use `/write-spec` to create detailed feature specifications for each item in the roadmap above.
