# Wofür ist die App?

Customer Service Assist hilft dir, Kundenservice-Tickets richtig zu bearbeiten. Zu einem Ticket aus Zammad bereitet die App einen **Vorschlag** vor: wie der Fall zu bewerten ist, was zu tun ist, ob du das selbst entscheiden darfst, welche Informationen noch fehlen und wie die Antwort an den Kunden lauten könnte.

Der Vorschlag stützt sich auf das Wissen unseres Unternehmens: Regeln, Produktwissen, Abläufe und bewährte Formulierungen. Dieses Wissen heißt in der App **Knowledge**. So musst du nicht erst erfahrene Kollegen fragen oder in Dokumenten suchen. Das gilt besonders, wenn du als Aushilfe oder Vertretung neu dabei bist.

# So funktioniert es

1. **Deinen Namen wählen.** Einmal pro Browser, damit nachvollziehbar ist, wer eine Analyse gestartet hat. [in Arbeit]
2. **Ticket laden.** Du gibst die Ticketnummer ein, die App holt das Ticket mit dem ganzen Verlauf aus Zammad. [in Arbeit]
3. **Bestellung laden.** Die App sucht die Bestellnummer im Ticket und holt die Bestelldaten. Fehlt etwas, trägst du es von Hand nach. [in Arbeit]
4. **Kundengruppe und Produkt bestätigen.** Die App schlägt beides aus der Bestellung vor, zum Beispiel „Privatkunde, Amazon" und „Zaubertasse". Du bestätigst oder korrigierst. Ist die Kundengruppe noch unklar, wählst du „Noch unklar". [in Arbeit]
5. **Passendes Wissen auswählen.** Die App sucht aus der Knowledge genau die Dokumente heraus, die für diese Kundengruppe und dieses Produkt gelten. Das geschieht automatisch und nach festen Regeln, nicht durch die KI.
6. **Vorschlag erstellen.** Ticket, Bestellung, deine Hinweise und das ausgewählte Wissen gehen an eine KI. Sie liefert den Vorschlag in einer festen Form. [in Arbeit]
7. **Prüfen und anpassen.** Du liest den Vorschlag, siehst, auf welche Knowledge-Dokumente er sich stützt, passt den Antwortentwurf an und kopierst ihn nach Zammad. [in Arbeit]
8. **Rückmeldung geben.** Du sagst kurz, wie brauchbar der Vorschlag war, und meldest fehlendes Wissen. So wird die Knowledge mit jedem Fall besser. [in Arbeit]

Schritte mit „in Arbeit" gibt es noch nicht; sie kommen nach und nach dazu.

# Ein Beispiel

Eine Kundin hat auf Amazon eine Zaubertasse gekauft. Sie schreibt: „Das Motiv erscheint nicht, wenn ich heißen Kaffee einfülle."

- Die App erkennt die Zaubertasse in der Bestellung und schlägt „Privatkunde, Amazon" vor. Du bestätigst.
- Die App wählt das passende Wissen aus: was die Zaubertasse kann und was nicht, wie man den Thermoeffekt prüft, was bei diesem Fallmuster zu tun ist und welche Regeln für Privatkunden auf Amazon gelten. Regeln für Fachhändler oder für andere Produkte bleiben draußen.
- Die KI schlägt vor: Bevor entschieden wird, soll die Kundin die Tasse mit mindestens 80 °C heißem Wasser füllen und ein Foto schicken. Dazu gibt es einen freundlichen Antwortentwurf.
- Du prüfst den Vorschlag, passt die Antwort bei Bedarf an und schickst sie in Zammad ab.

# Was die App bewusst nicht tut

- Sie **schickt keine Antwort** an Kunden. Das machst immer du, in Zammad.
- Sie **entscheidet nichts**. Sie macht einen Vorschlag; du prüfst ihn und trägst die Verantwortung.
- Sie **ändert keine Bestellungen** und löst keine Erstattungen, Gutschriften oder Ersatzaufträge aus.
- Sie **schaut sich keine Fotos an**. Was auf einem Reklamationsfoto zu sehen ist, beschreibst du selbst im Hinweisfeld.
- Sie ersetzt nicht Zammad und bearbeitet keine Tickets automatisch beim Eingang.

# Gut zu wissen

- **„Unklar" ist ein gutes Ergebnis.** Fehlen Informationen, soll die App nicht raten. Sie sagt dann, was fehlt und welche Rückfrage an den Kunden sinnvoll ist.
- **Entwurfs-Wissen** ist Wissen, das noch nicht endgültig bestätigt ist. Die App verwendet es trotzdem und kennzeichnet es. Schau bei Entwurfs-Wissen ruhig kritisch hin. Wenn dir etwas falsch vorkommt, sag Bescheid; genau dafür ist die Kennzeichnung da.
- **Jede Empfehlung nennt ihre Quellen**, also die Knowledge-Dokumente, auf denen sie beruht. So kannst du nachlesen, warum die App etwas vorschlägt.
- **Die Kundengruppe ist wichtig.** Für Privatkunden im Shop, auf Amazon, für Fachhändler und für LOOXIS-Pro gelten teils unterschiedliche Regeln. Wählst du die falsche Gruppe, bekommt die KI die falschen Regeln.

# Wissen ergänzen

Die Knowledge besteht aus einzelnen Textdateien, eine Datei pro Regel, Produkt oder Ablauf. Neues Wissen entsteht entlang echter Fälle: Fehlt der App etwas, wird es aufgeschrieben, zuerst als Entwurf.

Verfasst wird Wissen mit Hilfe einer Chat-KI nach unserer Schreibanleitung für Wissen (dem „Authoring Guide"); ein Entwickler pflegt die Dateien anschließend ein. Welche Dokumente es gibt und ob sie fehlerfrei sind, zeigt die [Knowledge-Übersicht](/knowledge).

# Für Entwickler

## Tech Stack

| Werkzeug | Wofür |
|---|---|
| Laravel 13 (PHP 8.5) | Anwendungsrahmen: Routen, Controller, Konfiguration, Befehle |
| Blade | Serverseitige Seitenvorlagen und wiederverwendbare Komponenten (`resources/views/components/`) |
| Tailwind CSS v4 | Gestaltung; Design-Tokens des LOOXIS Design Systems im `@theme` von `resources/css/app.css` |
| Alpine.js | Kleine Interaktionen im Browser, z. B. Kopieren und Aufklappen |
| MySQL über Eloquent | Datenbank für Analysen, Protokolle und Feedback (ab PROJ-11) |
| Pest | Tests (`tests/Feature/`, `tests/Unit/`) |
| Laravel Sail | Lokale Entwicklung ausschließlich in Docker; alle Befehle über `./vendor/bin/sail …` |
| Laravel Boost | MCP-Server mit Laravel-Dokumentation, Datenbankschema und Logs für KI-Agenten |

## Bausteine

- **Knowledge-Bibliothek** (`app/Knowledge/`): liest die Markdown-Dateien in `knowledge/`, prüft das Frontmatter, erkennt Status, Wissensstand (Git-Commit) und Fingerabdruck je Dokument. Befehle: `knowledge:check`, `knowledge:overview`.
- **Knowledge-Auswahl** (`app/Knowledge/KnowledgeSelector.php`): wählt Dokumente deterministisch nach Kundengruppe und Produkt aus, mit Begründung je Dokument. Vorschau: `knowledge:select <gruppe> --product=<slug>`.
- **Knowledge-Übersicht** (`/knowledge`): Dokumente, Prüfergebnis und ID-Übersicht in der App.
- **Zammad-, EOCS- und LLM-Anbindung**: folgen mit PROJ-6, PROJ-7 und PROJ-9. Die App bleibt anbieterneutral.

## Arbeitsweise

- Jedes Feature hat eine Spec in `features/PROJ-X-*.md`; Übersicht und Status in `features/INDEX.md`.
- Ablauf mit Skills: `/write-spec` → `/architecture` → `/frontend` → `/backend` → `/qa` → `/deploy`. Jeder Schritt hat eine Freigabe durch einen Menschen.
- Commits direkt auf `main` im Format `feat(PROJ-X): …`, `fix(PROJ-X): …`.
- Wird ein Schritt im Abschnitt „So funktioniert es" nutzbar, entfernt die Abnahme (`/qa`) dort die Kennung `[in Arbeit]` in `docs/ABOUT.md`.

## Wo steht was?

- `docs/PRD.md` – Ziel, Zielgruppen, Roadmap, Rahmenbedingungen
- `features/INDEX.md` – alle Features mit Status und Abhängigkeiten
- `docs/KNOWLEDGE_BASE_DESIGN.md` – feste Struktur der Knowledge Base
- `docs/KNOWLEDGE_AUTHORING_GUIDE.md` – Anleitung zum Verfassen von Wissen, auch für die Chat-KI
- `docs/design-system.md` – LOOXIS Design System
- `README.md` – Einrichtung und Start mit Sail
- `docs/ABOUT.md` – dieser Text
