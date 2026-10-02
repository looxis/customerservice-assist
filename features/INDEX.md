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

The ID order is the recommended build order (exception: PROJ-24 was added later and should be built right after PROJ-3). No authentication in the MVP (see `docs/PRD.md`, Constraints); login arrives with PROJ-15.

| ID | Feature | Description | Priority | Dependencies | Status | Spec | Created |
|----|---------|-------------|----------|--------------|--------|------|---------|
| PROJ-1 | App-Grundgerüst mit LOOXIS-Design | Layout-Shell, Design-Tokens und Basis-Komponenten nach `docs/design-system.md` | P0 | None | Approved | [Spec](PROJ-1-app-grundgeruest-looxis-design.md) | 2026-10-02 |
| PROJ-2 | Knowledge-Authoring-Kit | Ordnerstruktur `knowledge/`, Einstiegsseite, Vorlagen je Dokumenttyp, Anleitung (`docs/KNOWLEDGE_AUTHORING_GUIDE.md`), KI-Skill im Repo als Ausweichweg | P0 | None | Approved | [Spec](PROJ-2-knowledge-authoring-kit.md) | 2026-10-02 |
| PROJ-3 | Knowledge Base einlesen und prüfen | Markdown-Dateien lesen, Frontmatter validieren, Status `draft`/`active`/`deprecated` erkennen, Git-Stand erfassen, Prüfbefehl mit Fehlern und Warnungen, automatische Übersicht vergebener IDs und Schlagwörter, Fingerabdruck je Dokument | P0 | PROJ-2 | Approved | [Spec](PROJ-3-knowledge-base-einlesen-und-pruefen.md) | 2026-10-02 |
| PROJ-4 | Knowledge-Auswahl | Relevante Dokumente deterministisch nach Typ, Produkt, Kategorie, Thema sowie Kundenart und Vertriebskanal (`customer_types`, `sales_channels`) auswählen; `draft`-Dokumente werden mitverwendet und als Entwurfs-Wissen gekennzeichnet | P0 | PROJ-3 | Roadmap | – | 2026-10-02 |
| PROJ-5 | Nutzerauswahl | Dropdown mit fester Namensliste, Auswahl einmal pro Browser, Nutzer dauerhaft sichtbar | P0 | PROJ-1 | Roadmap | – | 2026-10-02 |
| PROJ-6 | Zammad-Ticket laden | Ticketnummer eingeben, Ticket und Verlauf laden und anzeigen, Fehlerzustände behandeln | P0 | PROJ-1 | Roadmap | – | 2026-10-02 |
| PROJ-7 | Bestellung aus EOCS laden | Bestellnummer im Ticket erkennen, EOCS abrufen, manuelle Bestellnummer, Bestelldaten anzeigen | P0 | PROJ-6 | Roadmap | – | 2026-10-02 |
| PROJ-8 | Bestelldaten manuell ergänzen | Fehlende Bestell- und Konfigurationsdaten von Hand eintragen (Fallback) | P0 | PROJ-6 | Roadmap | – | 2026-10-02 |
| PROJ-9 | Fallanalyse per LLM | Ticket, Bestelldaten, Mitarbeiterkontext und Knowledge an das LLM senden, feste Ergebnisstruktur validieren, „unklar" zulassen | P0 | PROJ-4, PROJ-6, PROJ-7, PROJ-8 | Roadmap | – | 2026-10-02 |
| PROJ-10 | Ergebnisansicht | Alle Ergebnisteile anzeigen, Antwortentwurf bearbeiten und kopieren, Knowledge-Quellen einsehen, Hinweis auf verwendetes Entwurfs-Wissen | P0 | PROJ-9 | Roadmap | – | 2026-10-02 |
| PROJ-11 | Analyse-Protokoll und Verlauf | Jede Analyse reproduzierbar speichern, frühere Analysen je Ticket anzeigen, erneut analysieren | P0 | PROJ-5, PROJ-9 | Roadmap | – | 2026-10-02 |
| PROJ-12 | Feedback und Wissenslücke | Vier-Stufen-Feedback mit optionalem Kommentar, Wissenslücke zur Analyse melden | P0 | PROJ-10, PROJ-11 | Roadmap | – | 2026-10-02 |
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
| PROJ-24 | Knowledge-Übersicht | Seite in der App: alle Dokumente mit ID, Typ, Status und Geltungsbereich, Fehler und Warnungen je Datei, Kopier-Button für die ID-Übersicht | P0 | PROJ-1, PROJ-3 | Planned | [Spec](PROJ-24-knowledge-uebersicht.md) | 2026-10-02 |

<!-- Add features above this line -->

## Next Available ID: PROJ-25
