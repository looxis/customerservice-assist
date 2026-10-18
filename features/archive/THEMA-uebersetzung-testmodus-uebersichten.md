# Übersetzung, Testmodus und Admin-Übersichten

> Zusammenfassung abgeschlossener Features (Status „Approved“). Die vollständigen Specs mit Entscheidungen, Randfällen und QA-Ergebnissen liegen in diesem Ordner.

Fremdsprachige Nachrichten werden einmal ins Deutsche übersetzt und gespeichert (PROJ-28). Admins können beantwortete Tickets auf einen früheren Stand zurückspulen und als Testlauf analysieren (PROJ-32). Die Analysen-Übersicht (PROJ-14) und die Ticket-Übersicht (PROJ-35) zeigen Admins, was bearbeitet wurde, mit Feedback und Kopier-Stand.

## Features

- **PROJ-28 Übersetzung von Nachrichten** (2026-10-05) – Fremdsprachige Nachrichten (z. B. Italienisch, Französisch über Amazon) zusätzlich auf Deutsch anzeigen, Original bleibt; jede Übersetzung wird einmal erstellt und gespeichert, damit nicht bei jedem Aufruf neu KI-Kosten entstehen; Antwortentwurf weiter in der Sprache des Kunden; wie die Übersetzung in der Oberfläche erscheint, ist noch zu klären [Spec](PROJ-28-uebersetzung-von-nachrichten.md)
- **PROJ-32 Testmodus – Ticket zurückspulen** (2026-10-06) – Admin-Werkzeug (Admin-Liste in der Konfiguration, Schalter je Browser): beantwortete oder geschlossene Tickets auf den Stand einer Kundennachricht zurückspulen; nur Nachrichten bis dahin gehen in Analyse und Zusammenfassung, spätere eingeklappt zum Vergleich; Testläufe gekennzeichnet und von echten Daten getrennt [Spec](PROJ-32-testmodus-ticket-zurueckspulen.md)
- **PROJ-14 Analysen-Übersicht** (2026-10-02) – Admin-Seite: eine Zeile je Analyse (Kundengruppe, Produkt, Kategorie, Modell, Prompt-Version, Feedback, Kopier-Stand, gemeldete Wissenslücken) mit Filtern, Seiten und wenigen Kennzahlen zur gefilterten Menge; Testläufe und Fehlgeschlagene zuschaltbar [Spec](PROJ-14-analysen-uebersicht.md)
- **PROJ-35 Ticket-Übersicht** (2026-10-12) – Admin-Seite in der Seitenleiste: zeitlich sortierte Liste aller in der App abgerufenen Tickets (Ticketnummer, Kundenname, Betreff) mit Stand je Ticket – nur abgerufen, übersetzt, analysiert, Entwurf bearbeitet, 1:1 kopiert oder angepasst kopiert; erfasst Abrufe und den Kopieren-Klick; Aufbewahrung 12 Monate; erster Schritt Richtung PROJ-29 [Spec](PROJ-35-ticket-uebersicht.md)
