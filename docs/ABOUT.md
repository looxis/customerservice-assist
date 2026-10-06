# Wofür ist die App?

Customer Service Assist hilft dir, Kundenservice-Tickets richtig zu bearbeiten. Zu einem Ticket aus Zammad bereitet die App einen **Vorschlag** vor: wie der Fall zu bewerten ist, was zu tun ist, ob du das selbst entscheiden darfst, welche Informationen noch fehlen und wie die Antwort an den Kunden lauten könnte.

Der Vorschlag stützt sich auf das Wissen unseres Unternehmens: Regeln, Produktwissen, Abläufe und bewährte Formulierungen. Dieses Wissen heißt in der App **Knowledge**. So musst du nicht erst erfahrene Kollegen fragen oder in Dokumenten suchen. Das gilt besonders, wenn du als Aushilfe oder Vertretung neu dabei bist.

# So funktioniert es

1. **Deinen Namen wählen.** Einmal pro Browser, damit nachvollziehbar ist, wer eine Analyse gestartet hat. Dein Name steht danach immer rechts oben; ein Klick darauf wechselt ihn.
2. **Ticket laden.** In Zammad am Ticket auf „Kopieren" klicken, in der App auf „Einfügen". Die App holt das Ticket mit dem ganzen Verlauf aus Zammad: Kundennachrichten links, unsere Antworten und interne Notizen rechts, Zitate und Signaturen eingeklappt, klickbare Links entfernt, Amazon-Bestellungen oben im Kopf.
3. **Bestellung laden.** Die App findet Bestellnummern im Ticket und bietet sie als Vorschläge an; ein Klick lädt die Bestellung aus EOCS mit Status, Rechnungsnummer, Versand und früheren Reklamationsaufträgen. Eine Nummer kannst du auch von Hand eintragen. Fehlende Bestelldaten von Hand ergänzen: [in Arbeit]
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

- **„Unklar" ist ein gutes Ergebnis.** Fehlen Informationen, soll die App nicht raten. Sie sagt dann, was fehlt und welche Rückfrage sinnvoll ist (mehr dazu im nächsten Abschnitt).
- **Entwurfs-Wissen** ist Wissen, das noch nicht endgültig bestätigt ist. Die App verwendet es trotzdem und kennzeichnet es. Schau bei Entwurfs-Wissen ruhig kritisch hin. Wenn dir etwas falsch vorkommt, sag Bescheid; genau dafür ist die Kennzeichnung da.
- **Jede Empfehlung nennt ihre Quellen**, also die Knowledge-Dokumente, auf denen sie beruht. So kannst du nachlesen, warum die App etwas vorschlägt.
- **Die Kundengruppe ist wichtig.** Für Privatkunden im Shop, auf Amazon, für Fachhändler, für LOOXIS-Pro und für White-Label-Kunden wie masterpics gelten teils unterschiedliche Regeln. Wählst du die falsche Gruppe, bekommt die KI die falschen Regeln.

# Wenn etwas fehlt

Die App soll nie raten oder etwas erfinden. Fehlt etwas, sagt sie es offen. Dabei unterscheidet sie zwei Fälle:

- **Fehlende Informationen – uns fehlt etwas zum Fall.** Zum Beispiel ein Foto, die Bestellnummer oder eine Auskunft der Produktion. Die App nennt im Vorschlag unter „Fehlende Informationen", *was* fehlt, *von wem* es kommen muss (Kunde oder intern) und formuliert die passende **Rückfrage**. Bei Reklamationen lautet die Bewertung dann „unklar". Das ist kein Fehler, sondern genau richtig: erst nachfragen, dann entscheiden.
- **Fehlendes Wissen – uns fehlt eine Regel.** Zum Beispiel fragt ein Kunde: „Können meine zwei Bestellungen zusammengeführt und mit einer Rechnung berechnet werden?" Steht dazu nichts in der Knowledge, zeigt die App oben im Vorschlag den Hinweis **„Fehlendes Wissen"** mit dem Thema und der offenen Frage. Der Vorschlag sagt dazu bewusst nichts zu. Entscheide dann nicht nach Gefühl, sondern frag nach und melde die Lücke an Etienne. Dann wird das Wissen ergänzt (siehe „Wissen ergänzen").

**Das Feld „Zusätzliche Informationen / eigene Einschätzung"** ist für **Fakten zum Fall** da, die nicht im Ticket stehen: „Foto geprüft: Motiv ist verschoben gedruckt", „Kundin am Telefon: braucht Ersatz bis zum 20.12.", „Produktion bestätigt Fehldruck". Die KI behandelt diese Angaben als geprüft und richtet sich danach.

**Regeln gehören nicht in dieses Feld**, also nichts wie „Bestellungen kann man nicht zusammenführen, nur der Kunde bestimmt, was zu einer Bestellung gehört". Die KI würde die Regel zwar richtig anwenden. Aber dann merkt niemand, dass sie in der Knowledge fehlt, und beim nächsten Mal fehlt sie wieder. Solche Regeln bitte als Wissenslücke melden.

# Wissen ergänzen

Die Knowledge besteht aus einzelnen Textdateien, eine Datei pro Regel, Produkt oder Ablauf. Neues Wissen entsteht entlang echter Fälle: Fehlt der App etwas, wird es aufgeschrieben, zuerst als Entwurf.

Neues Wissen wird an echten Fällen geprüft. Der Kreislauf:

1. **Testen:** Ein Ticket analysieren, ohne die fehlende Regel ins Feld „Zusätzliche Informationen" zu schreiben.
2. **Lücke erkennen:** Die App meldet „Fehlendes Wissen", oder der Vorschlag ist erkennbar falsch oder zu allgemein.
3. **Wissen ergänzen:** Die Regel als neues Dokument aufschreiben, zuerst als Entwurf. Wichtig ist der Geltungsbereich (Kundengruppe, Produkt), denn nur passendes Wissen geht an die KI.
4. **Erneut testen:** Dasselbe Ticket noch einmal analysieren. Das neue Wissen wirkt sofort. Unter „Verwendetes Wissen" siehst du, ob die KI das neue Dokument genutzt hat.

Damit sich auch bereits beantwortete Tickets zum Testen eignen, kann ein Admin ein Ticket auf den Stand einer früheren Kundennachricht zurückspulen. Spätere Antworten gehen dann nicht an die KI und lassen sich zum Vergleich aufklappen. [in Arbeit]

Verfasst wird Wissen mit Hilfe einer Chat-KI nach unserer Schreibanleitung für Wissen (dem „Authoring Guide"); ein Entwickler pflegt die Dateien anschließend ein. Welche Dokumente es gibt und ob sie fehlerfrei sind, zeigt die [Knowledge-Übersicht](/knowledge).

# Noch zu klären

- **Einheitliche Signaturen:** Bei unseren eigenen Antworten blendet die App die Signatur aus, damit der Verlauf lesbar bleibt. Das klappt, wenn Zammad die Signatur markiert hat oder wenn sie in der Liste der bekannten Signaturen steht. Heute nutzt fast jede Kollegin und jeder Kollege eine eigene Variante, je nach Sprache und Kanal (z. B. auf Italienisch für Amazon). Im Team ist noch zu klären, welche Signaturen es gibt und welche einheitliche Form sie haben sollen. Danach werden sie in den geplanten Einstellungen der App gepflegt.

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
