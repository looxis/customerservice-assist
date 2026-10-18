# Tickets, Bestellungen und Verlauf

> Zusammenfassung abgeschlossener Features (Status „Approved“). Die vollständigen Specs mit Entscheidungen, Randfällen und QA-Ergebnissen liegen in diesem Ordner.

Zammad-Tickets werden per Nummer lesend geladen und angezeigt (PROJ-6), Bestellnummern im Text erkannt und aus EOCS geladen (PROJ-7); die letzte Kundennachricht steht oben (PROJ-43). Antworten von uns nach der letzten Kundennachricht gehen nicht in die Analyse (PROJ-48). Reklamationsnummern `R<n>-<Bestellnummer>` werden erkannt (PROJ-53), und zusammengehörige Tickets lassen sich mit ihrem Verlauf in den Vorschlag einbeziehen und unter dem Verlauf einsehen (PROJ-52). Die App schreibt nie nach Zammad oder EOCS.

## Features

- **PROJ-6 Zammad-Ticket laden** (2026-10-02) – Ticketnummer eingeben, Ticket und Verlauf laden und anzeigen, Fehlerzustände behandeln [Spec](PROJ-6-zammad-ticket-laden.md)
- **PROJ-7 Bestellung aus EOCS laden** (2026-10-02) – Bestellnummer im Ticket erkennen, EOCS abrufen, manuelle Bestellnummer, Bestelldaten anzeigen [Spec](PROJ-7-bestellung-aus-eocs-laden.md)
- **PROJ-8 Bestelldaten manuell ergänzen** (2026-10-02) – Aufgegangen in PROJ-9: Bestelldaten von Hand im Analyse-Formular, wenn keine EOCS-Bestellung geladen ist [Spec](PROJ-9-fallanalyse-per-llm.md)
- **PROJ-43 Kundennachricht oben auf der Ticketseite** (2026-10-13) – Die letzte Kundennachricht (bei neuen Tickets die erste) steht ganz oben und ersetzt das Eingabefeld „Ticket aus Zammad“; Verlauf bleibt unten; kein automatisches Springen zur neuesten Nachricht [Spec](PROJ-43-kundennachricht-oben.md)
- **PROJ-48 Verlauf für die Analyse und Angebot der Zusammenfassung** (2026-10-17) – Unsere Antworten nach der letzten Kundennachricht gehen nicht in Analyse und Zusammenfassung (ohne Testmodus), kommt eine neue Kundennachricht, zählen sie wieder mit; Zusammenfassung erst ab 5 Nachrichten angeboten, veraltete mit „neu erstellen“; Testmodus bleibt fürs Zurückspulen [Spec](PROJ-48-verlauf-und-zusammenfassung.md)
- **PROJ-53 Reklamationsnummer erkennen** (2026-10-17) – `R<n>-<Bestellnummer>` (Präfix einstellbar) in Titel und Text erkennen: Originalnummer gilt als Bestellung, Reklamationsnummer wird vermerkt, in der Bestellung hervorgehoben und der Analyse als Fakt „Folgevorgang einer Reklamation“ mitgegeben [Spec](PROJ-53-reklamationsnummer-erkennen.md)
- **PROJ-52 Zusammengehörige Tickets** (2026-10-17) – Verknüpfte Zammad-Tickets (vorausgewählt), Tickets zur Originalbestellnummer (vorausgewählt bei Reklamationsnummer) und weitere Tickets desselben Kunden (letzte 90 Tage) als Vorschläge im Formular, dazu Ticketnummer von Hand; ihr vollständiger Verlauf geht als „Früherer Verlauf“ mit eigener Prompt-Regel in die Analyse, begrenzt auf 16.000 Zeichen mit sichtbarer Auslassung; Snapshot im Protokoll, Fortsetzung ohne Neuabruf; kein Schreibzugriff auf Zammad [Spec](PROJ-52-zusammengehoerige-tickets.md)
