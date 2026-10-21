# Frage stellen und Vorschlagsformular

> Zusammenfassung abgeschlossener Features (Status „Approved“). Die vollständigen Specs mit Entscheidungen, Randfällen und QA-Ergebnissen liegen in diesem Ordner.

Neben der Ticketanalyse gibt es die Wissensabfrage ohne Ticket (Frage, Anrufnotiz, eingefügter Kundentext), die im selben Aufbau antwortet (PROJ-40, nimmt PROJ-16 auf). Das gemeinsame Formular verlangt Kundengruppe und eine Produktantwort, bietet eine Produktsuche mit Chips und blendet Technisches hinter „Erweitert“ aus (PROJ-42).

## Features

- **PROJ-16 Tickettext manuell einfügen** (2026-10-02) – Aufgegangen in PROJ-40: Analyse ohne Zammad-Abruf mit eingefügtem Text [Spec](PROJ-40-wissensabfrage-ohne-ticket.md)
- **PROJ-40 Wissensabfrage ohne Ticket** (2026-10-13) – Eine Frage oder Anrufnotiz oder ein eingefügter Kundentext ohne Ticket an die Wissensdatenbank: Eingabe mit Kundengruppe und Produkten, Vorschlag im selben Aufbau wie bei Tickets (Antwortentwurf nur auf Wunsch), Nachfragen über die Fortsetzung aus PROJ-39, Protokoll und persönlicher Verlauf; Oberfläche „Frage stellen“, Ergebnis „Vorschlag“ statt „Analyse“. Nimmt PROJ-16 auf [Spec](PROJ-40-wissensabfrage-ohne-ticket.md)
- **PROJ-42 Formular: Pflichtangaben und Produktsuche** (2026-10-13) – Vorschlagsformular (Ticket und Frage): Kundengruppe und Produktantwort verpflichtend (auch „Produkt ohne hinterlegtes Wissen“ und „Kein Produktbezug“) mit Rückfrage bei „unklar“; Produktsuche mit Chips statt Checkbox-Liste; eine Stelle je Information (Kundengruppe nicht mehr im Ticketkopf, Bestellung als eine Statuszeile); Schalter „Erweitert“ für technische Teile [Spec](PROJ-42-formular-pflichtangaben-und-produktsuche.md)
- **PROJ-59 Hilfe für das Feld „Zusätzliche Informationen“** (2026-10-21) – Beispieltext im Feld hängt von der Lage ab (Folgenachricht, wenn das Ticket schon eine Antwort von uns enthält), aufklappbarer Hinweis „So schreibst du es“; der Prompt unterscheidet Fakten, Entscheidungen und Wünsche an die Antwort und übernimmt Ursachen und Verzögerungsgründe, nie Interna [Spec](PROJ-59-hilfe-fuer-zusaetzliche-informationen.md)
