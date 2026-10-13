# Wofür ist die App?

Customer Service Assist hilft dir, Kundenservice-Tickets richtig zu bearbeiten. Zu einem Ticket aus Zammad bereitet die App einen **Vorschlag** vor: wie der Fall zu bewerten ist, was zu tun ist, ob du das selbst entscheiden darfst, was du intern erledigen oder klären musst und wie die Antwort an den Kunden lauten könnte. Hängt die Antwort von einer schnellen internen Klärung ab, etwa einer Auskunft der Produktionsleitung, erstellt die App zuerst keinen Entwurf, sondern fragt diese Klärung ab und macht danach weiter.

Der Vorschlag stützt sich auf das Wissen unseres Unternehmens: Regeln, Produktwissen, Abläufe und bewährte Formulierungen. Dieses Wissen heißt in der App **Knowledge**. So musst du nicht erst erfahrene Kollegen fragen oder in Dokumenten suchen. Das gilt besonders, wenn du als Aushilfe oder Vertretung neu dabei bist.

Neben Reklamationen und Fragen hilft die App auch bei **Angebots- und Auftragsanfragen**, die per E-Mail kommen: Ist die Anfrage noch nicht eindeutig, schlägt sie die Rückfragen vor; danach formuliert sie das Angebot oder die Auftragsbestätigung. Preise und Liefertermine rechnet oder rät sie nicht – sie stehen im Wissen, kommen von dir oder bleiben als Stelle zum Ausfüllen im Entwurf (z. B. „[PREIS]“).

# So funktioniert es

1. **Deinen Namen wählen.** Einmal pro Browser, damit nachvollziehbar ist, wer eine Analyse gestartet hat. Dein Name steht danach immer rechts oben; ein Klick darauf wechselt ihn.
2. **Ticket laden.** In Zammad am Ticket auf „Kopieren" klicken, in der App auf „Einfügen". Die App holt das Ticket mit dem ganzen Verlauf aus Zammad: Kundennachrichten links, unsere Antworten und interne Notizen rechts, Zitate und Signaturen eingeklappt, klickbare Links entfernt, Amazon-Bestellungen oben im Kopf. Sind Nachrichten nicht auf Deutsch, übersetzt ein Klick auf „Übersetzen“ den Verlauf; die Übersetzung wird gespeichert und steht danach allen sofort zur Verfügung, das Original bleibt einen Klick entfernt.
3. **Bestellung laden.** Die App findet Bestellnummern im Ticket und zeigt sie an. Die Bestelldetails (Status, Rechnungsnummer, Versand, frühere Reklamationsaufträge) holt sie beim Analysieren von selbst aus EOCS; mit „Bestelldetails aus EOCS abrufen“ siehst du sie schon vorher. Die Bestellnummer selbst geht in jedem Fall an die Analyse. Findet die App keine Nummer, trägst du sie von Hand ein; gibt es in EOCS keine Bestellung dazu, ergänzt du die wichtigsten Bestelldaten im Analyse-Formular.
4. **Kundengruppe und Produkt bestätigen.** Die App schlägt beides aus der Bestellung vor, zum Beispiel „Privatkunde, Amazon" und „Zaubertasse". Du bestätigst oder korrigierst. Ist die Kundengruppe noch unklar, wählst du „Noch unklar". Die App merkt sich deine Wahl für das Ticket und schlägt die Kundengruppe auch bei weiteren Tickets desselben Kunden vor.
5. **Passendes Wissen auswählen.** Die App schickt der KI nicht alles Wissen, sondern ruft es **gezielt nach dem Anliegen** ab, automatisch und nach festen Regeln, nicht durch die KI. Zuerst legen Kundengruppe und Produkt fest, welche Dokumente überhaupt in Frage kommen. Dann kommen davon nur die mit, die der Fall braucht: das kurze **Grundwissen** immer, **Wissen bei Bedarf**, wenn Wörter aus deiner aktuellen Kundennachricht (oder deinem Hinweis) darauf passen, und **verbindliche Regeln**, die nie übersehen werden dürfen (z. B. Befugnisgrenzen). Schlägt die KI eine Maßnahme vor, zu der eine verbindliche Regel gehört, die noch fehlte, lädt die App sie nach und rechnet den Vorschlag einmal neu. Zusätzlich kann eine **erweiterte Wissenssuche** (ein kleiner KI-Aufruf vorab) Dokumente ergänzen, die nach den Auslösern nicht gefunden wurden, weil der Kunde sein Anliegen ganz anders beschreibt; sie ergänzt nur und umgeht keine Regeln. Im Bereich „Wissensabruf“ steht bei jedem Dokument, ob es als Grundwissen, durch Auslöser, vom KI-Router oder von Hand dabei ist; die Begründung des Routers ist eine KI-Einschätzung. Fällt die erweiterte Suche aus, steht ein Hinweis im Ergebnis, und es gilt die reguläre Auswahl.
6. **Vorschlag erstellen.** Ticket, Bestellung, deine Hinweise und das ausgewählte Wissen gehen an eine KI. Sie liefert den Vorschlag in einer festen Form. Vorher siehst du unter „Was an die KI geht", welcher Teil des Tickets übertragen wird; E-Mail-Adressen, Telefonnummern und Anschriften sind dabei ersetzt. Bei langen Verläufen erstellt die App auf Wunsch zuerst eine Zusammenfassung, die du prüfen und korrigieren kannst.
7. **Prüfen und anpassen.** Oben steht, was zu tun ist und ob du das selbst entscheiden darfst, darunter der Antwortentwurf. Du passt ihn direkt in der App an – Änderungen werden automatisch gespeichert – und kopierst ihn mit einem Klick nach Zammad. Auf welche Knowledge-Dokumente sich der Vorschlag stützt, klappst du unter „Quellen“ auf; dort steht je Dokument, **warum** es abgerufen wurde, welche verbindlichen Dokumente „geprüft, aber nicht abgerufen“ wurden und was wegen Umfang weggelassen wurde. Dort kannst du ein Dokument auch von Hand zuschalten und neu analysieren. Steht über dem Vorschlag „Regelprüfung unvollständig“, konnte die App die verbindlichen Regeln zu einer Maßnahme nicht vollständig berücksichtigen: Prüfe sie selbst und bestätige es, erst dann lässt sich der Entwurf kopieren. Öffnest du das Ticket später wieder, ist die letzte Analyse noch da. Darunter steht „Interne Abläufe“, nur für dich und nicht für den Kunden: zuerst, falls nötig, eine **Klärung** mit Auswahloptionen oder einem Feld für das Ergebnis, dann die **Schritte für diesen Fall**, die die KI aus dem Ticket ableitet, und eingeklappt die **passenden Arbeitsanleitungen** aus dem Wissen, z. B. wie eine Retoure in EOCS erfasst wird – mit Kästchen zum Abhaken und hervorgehobenen kritischen Hinweisen. Das Abhaken wird nicht gespeichert. Ist der Antwortentwurf in einer Fremdsprache, zeigt dir „Auf Deutsch gegenlesen“, was du verschickst.
8. **Rückmeldung geben.** Nach dem Kopieren fragt die App kurz, wie brauchbar der Vorschlag war – unverändert nutzbar, leicht angepasst, stark angepasst oder verworfen. Ein Klick genügt; einen Kommentar kannst du dazuschreiben. Fehlt der App eine Regel, meldest du mit „Wissenslücke melden“, was fehlt und wie wir den Fall lösen. So wird die Knowledge mit jedem Fall besser.


# Wenn zuerst etwas intern zu klären ist

Nicht jede Frage lässt sich sofort beantworten. Ein Foto-Fachhändler schreibt, dass er sein Geschäft aufgibt, und fragt, ob sein „Aufsteller“ zurückgenommen wird. Ob die Produktion Display oder Glasfoto-Muster überhaupt zurückbraucht, weiß erst die Produktionsleitung. Die App erstellt dann **noch keinen Antwortentwurf**. Unter „Interne Abläufe“ steht stattdessen die Frage mit den möglichen Antworten, zum Beispiel „Nur Display zurück“, „Beides zurück“ oder „Kein Bedarf“. Du klärst es und wählst die passende Antwort; oder du trägst im Feld darunter das Ergebnis selbst ein. Danach setzt die App die Analyse mit dieser Klärung fort und liefert den Entwurf, etwa eine Rückfrage zum Zustand der Gegenstände oder ein kurzer Abschied mit Hinweis auf die Entsorgung. Die App tut das nicht bei jeder offenen Frage, sondern nur, wenn die Klärung schnell möglich ist, die Antwort deutlich verändert und eine Zwischenantwort wenig bringen würde. Die Auswahl ist ein Vorschlag aus Fall und Wissen; prüfe sie, bevor du dich darauf verlässt.

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

- **„Unklar" ist ein gutes Ergebnis.** Fehlen Informationen, soll die App nicht raten. Sie sagt dann, was fehlt und was zuerst zu klären ist, oder welche Rückfrage sinnvoll ist (mehr dazu im nächsten Abschnitt).
- **Entwurfs-Wissen** ist Wissen, das noch nicht endgültig bestätigt ist. Die App verwendet es trotzdem und kennzeichnet es. Schau bei Entwurfs-Wissen ruhig kritisch hin. Wenn dir etwas falsch vorkommt, sag Bescheid; genau dafür ist die Kennzeichnung da.
- **Jede Empfehlung nennt ihre Quellen**, also die Knowledge-Dokumente, auf denen sie beruht. So kannst du nachlesen, warum die App etwas vorschlägt.
- **Die Kundengruppe ist wichtig.** Für Privatkunden im Shop, auf Amazon, für Fachhändler, für LOOXIS-Pro und für White-Label-Kunden wie masterpics gelten teils unterschiedliche Regeln. Wählst du die falsche Gruppe, bekommt die KI die falschen Regeln.

# Wenn etwas fehlt

Die App soll nie raten oder etwas erfinden. Fehlt etwas, sagt sie es offen. Dabei unterscheidet sie zwei Fälle:

- **Fehlende Angaben – uns fehlt etwas zum Fall.** Zum Beispiel ein Foto, die Bestellnummer oder eine Auskunft der Produktion. Fehlt etwas beim Kunden, steht die **Rückfrage** im Antwortentwurf. Hängt die Antwort von einer internen Klärung ab, erstellt die App zuerst keinen Entwurf, sondern zeigt unter „Interne Abläufe“ die Klärung, wenn möglich mit Auswahloptionen; du trägst das Ergebnis ein, und die Analyse geht damit weiter. Bei Reklamationen lautet die Bewertung dann „unklar“. Das ist kein Fehler, sondern genau richtig: erst klären, dann entscheiden.
- **Wissenslücke – uns fehlt eine Regel.** Zum Beispiel fragt ein Kunde: „Können meine zwei Bestellungen zusammengeführt und mit einer Rechnung berechnet werden?" Steht dazu nichts in der Knowledge, zeigt die App oben im Vorschlag den Hinweis **„Wissenslücken"** mit dem Thema und der offenen Frage. Der Vorschlag sagt dazu bewusst nichts zu. Entscheide dann nicht nach Gefühl, sondern frag nach und melde die Lücke mit „Lücke melden“ am Vorschlag. Dann wird das Wissen ergänzt (siehe „Wissen ergänzen").

**Das Feld „Zusätzliche Informationen / eigene Einschätzung"** ist für **Fakten zum Fall** da, die nicht im Ticket stehen: „Foto geprüft: Motiv ist verschoben gedruckt", „Kundin am Telefon: braucht Ersatz bis zum 20.12.", „Produktion bestätigt Fehldruck". Die KI behandelt diese Angaben als geprüft und richtet sich danach.

**Regeln gehören nicht in dieses Feld**, also nichts wie „Bestellungen kann man nicht zusammenführen, nur der Kunde bestimmt, was zu einer Bestellung gehört". Die KI würde die Regel zwar richtig anwenden. Aber dann merkt niemand, dass sie in der Knowledge fehlt, und beim nächsten Mal fehlt sie wieder. Solche Regeln bitte als Wissenslücke melden.

# Wissen ergänzen

Die Knowledge besteht aus einzelnen Textdateien, eine Datei pro Regel, Produkt oder Ablauf. Neues Wissen entsteht entlang echter Fälle: Fehlt der App etwas, wird es aufgeschrieben, zuerst als Entwurf.

Neues Wissen wird an echten Fällen geprüft. Der Kreislauf:

1. **Testen:** Ein Ticket analysieren, ohne die fehlende Regel ins Feld „Zusätzliche Informationen" zu schreiben.
2. **Lücke erkennen:** Die App meldet „Wissenslücken", oder der Vorschlag ist erkennbar falsch oder zu allgemein.
3. **Wissen ergänzen:** Die Regel als neues Dokument aufschreiben, zuerst als Entwurf. Wichtig ist der Geltungsbereich (Kundengruppe, Produkt), denn nur passendes Wissen geht an die KI.
4. **Erneut testen:** Dasselbe Ticket noch einmal analysieren. Das neue Wissen wirkt sofort. Unter „Verwendetes Wissen" siehst du, ob die KI das neue Dokument genutzt hat.

**Abruf prüfen (nach jedem neuen Dokument).** Weil die App nur abruft, was zum Anliegen passt, kann ein gutes Dokument unbemerkt nie geladen werden, wenn seine Auslöser („Triggers“) nicht zu dem passen, wie Kunden schreiben. Deshalb prüfst du nach dem Einpflegen, ob die App das Dokument bei einem typischen Fall wirklich abruft, und zwar ohne ein echtes Ticket und ohne KI-Aufruf. Ein Entwickler führt dazu aus:

`./vendor/bin/sail artisan knowledge:select private-looxis-de --text="Ihr Kundentext, so wie ein Kunde ihn schreiben würde"`

Optional mit `--product=<produkt>` und `--title="Betreff"`. Die Vorschau zeigt die Auswahl nach Auslösern; der Router ist darin nicht enthalten, deshalb zählt für neue Dokumente auch eine gute `summary`. Die Ausgabe nennt je Dokument den Grund des Abrufs („Auslöser ‚Schatten‘ im aktuellen Anliegen“), danach „geprüft, nicht abgerufen“ und den Gesamtumfang. Prüfe drei bis vier Fälle: einen, in dem das neue Dokument **kommen** muss, einen ähnlichen, in dem es **nicht** kommen soll, und einen typischen Alltagsfall, bei dem sich der Umfang nicht unnötig aufbläht. Kommt etwas Falsches oder fehlt etwas Richtiges, gehst du mit der Ausgabe zurück in den Chat und lässt die Auslöser und das Feld `summary` korrigieren. Wie die Felder wirken, steht im Handbuch `docs/KNOWLEDGE_CHAT_HANDBUCH.md`.

Damit sich auch bereits beantwortete Tickets zum Testen eignen, kann ein Admin ein Ticket auf den Stand einer früheren Kundennachricht zurückspulen. Spätere Antworten gehen dann nicht an die KI und lassen sich zum Vergleich aufklappen. Dafür schaltet der Admin oben rechts den „Testmodus" ein und wählt an einer Kundennachricht „Bis hierher testen". Testläufe sind gekennzeichnet und beeinflussen keine echten Vorschläge.

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
- **Knowledge-Auswahl** (`app/Knowledge/KnowledgeSelector.php`): legt deterministisch nach Kundengruppe und Produkt den Geltungsbereich fest, mit Begründung je Dokument. Vorschau: `knowledge:select <gruppe> --product=<slug>`.
- **Gezielter Abruf** (`app/Knowledge/Retrieval/`): wählt aus dem Geltungsbereich nach Klasse (`core`, `triggered`, `binding`), Auslösern und Bedingungen, mit Budget, Maßnahmensicherung und Protokoll je Analyse. Vorschau für einen Text: `knowledge:select <gruppe> --text="…"`. Schalter: `KNOWLEDGE_RETRIEVAL_ACTIVE` und `KNOWLEDGE_RETRIEVAL_REQUIRED` in `.env`.
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
- `docs/KNOWLEDGE_CHAT_HANDBUCH.md` – eine Seite für die Chat-KI: wie die App Wissen auswählt und welche Felder dafür nötig sind
- `docs/design-system.md` – LOOXIS Design System
- `README.md` – Einrichtung und Start mit Sail
- `docs/ABOUT.md` – dieser Text
