# PROJ-6: Zammad-Ticket laden

## Status: Architected
**Created:** 2026-10-02
**Last Updated:** 2026-10-05

## Dependencies
- Requires: PROJ-1 (App-Grundgerüst mit LOOXIS-Design) – Layout, Basis-Komponenten, Lade-Overlay, Fehlerseiten
- Berührt: PROJ-5 (Nutzerauswahl) – Laden und Ansehen eines Tickets braucht keinen gewählten Namen; erst die Analyse (PROJ-9)
- Wird genutzt von: PROJ-7 (Bestellnummer im Ticket erkennen), PROJ-9 (Ticketinhalt an die KI), PROJ-11 (Protokoll je Ticket)

## Begriffe
- **Ticketnummer:** die Nummer, die Zammad am Ticket anzeigt (z. B. `2137942`). Der Copy-Knopf in Zammad kopiert sie als `Ticket#2137942`.
- **Nachricht:** ein Eintrag im Ticketverlauf (in Zammad „Artikel"). Drei Arten: **vom Kunden**, **von uns** (versendete Antwort) und **interne Notiz**.

## User Stories
- Als Mitarbeiter möchte ich das Ticket, das ich in Zammad offen habe, mit einem Klick auf „Einfügen" in die App holen, damit ich nichts abtippen muss.
- Als Mitarbeiter möchte ich den ganzen Thread aus Kundennachrichten, unseren Antworten und internen Notizen in der richtigen Reihenfolge sehen, damit ich den Fall vollständig verstehe.
- Als Aushilfe möchte ich auf einen Blick erkennen, was der Kunde geschrieben hat, was wir geantwortet haben und was Kollegen intern notiert haben, damit ich nichts verwechsle.
- Als Mitarbeiter möchte ich sehen, welche Anhänge der Kunde geschickt hat, und das Ticket direkt in Zammad öffnen können, damit ich Fotos dort prüfe.
- Als Mitarbeiter möchte ich bei einem Fehler (Ticket gibt es nicht, Zammad nicht erreichbar) eine verständliche Meldung bekommen, damit ich weiß, was ich tun kann.
- Als Mitarbeiter möchte ich einem Kollegen den Link zur Ticketansicht in der App schicken oder die Seite neu laden können, damit wir über denselben Fall sprechen.

## Out of Scope
- Analyse des Tickets, Kundengruppe und Produkt, Kontextfeld – PROJ-9.
- Erkennen der Bestellnummer im Ticket und Laden der Bestellung – PROJ-7.
- Speichern des Tickets oder eines Verlaufs in der App – PROJ-11.
- Fotos und andere Anhänge in der App anzeigen oder herunterladen – Prüfung in Zammad; Bildanalyse ist PROJ-21.
- Suche nach Tickets über Kunde, E-Mail oder Betreff.
- Ticketliste, Posteingang oder „meine offenen Tickets" (Non-Goal: kein Ersatz für Zammad).
- Schreiben nach Zammad: Antworten, Notizen, Status ändern – PROJ-19 bzw. Non-Goal.
- Tickettext ohne Zammad einfügen – PROJ-16.
- Automatisches Laden bei Ticket-Eingang – PROJ-20.

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Eingabe
- [ ] Angenommen die Seite „Ticket analysieren" ist offen, wenn der Nutzer sie betrachtet, dann steht oben ein zentrales Eingabefeld für das Ticket mit einem Knopf „Einfügen" und einem Knopf „Ticket laden"; das Feld hat den Fokus.
- [ ] Angenommen in der Zwischenablage steht `Ticket#2137942` aus dem Copy-Knopf von Zammad, wenn der Nutzer auf „Einfügen" klickt, dann übernimmt die App den Inhalt und lädt sofort Ticket 2137942.
- [ ] Angenommen der Nutzer fügt mit Strg+V ein oder tippt, wenn er Enter drückt oder „Ticket laden" klickt, dann wird das Ticket geladen.
- [ ] Angenommen die Eingabe lautet `Ticket#2137942`, `ticket# 2137942`, `#2137942`, `2137942` oder enthält Leerzeichen bzw. einen Zeilenumbruch davor oder danach, wenn geladen wird, dann wird in allen Fällen Ticket 2137942 geladen.
- [ ] Angenommen die Eingabe enthält keine Ticketnummer (leer, nur Buchstaben, mehrere Nummern), wenn geladen werden soll, dann erscheint direkt am Feld „Bitte eine Ticketnummer eingeben, z. B. Ticket#2137942", und es wird nichts abgerufen.
- [ ] Angenommen der Browser erlaubt das Lesen der Zwischenablage nicht (Berechtigung verweigert, Browser ohne Unterstützung), wenn der Nutzer auf „Einfügen" klickt, dann erscheint der Hinweis „Bitte mit Strg+V einfügen", und das Feld erhält den Fokus.
- [ ] Angenommen die Zwischenablage enthält keine Ticketnummer, wenn der Nutzer auf „Einfügen" klickt, dann wird der Inhalt nicht geladen und dieselbe Meldung wie bei falscher Eingabe gezeigt.
- [ ] Angenommen eine Eingabe wird an den Server geschickt, wenn er sie verarbeitet, dann prüft auch er, dass es eine reine Ticketnummer ist, unabhängig von der Aufbereitung im Browser.

### Laden und eigene Adresse
- [ ] Angenommen eine gültige Ticketnummer wurde eingegeben, wenn geladen wird, dann zeigt das Lade-Overlay „Ticket wird geladen …", und danach steht das Ticket unter einer eigenen Adresse der App für diese Ticketnummer.
- [ ] Angenommen die Adresse eines Tickets wird aufgerufen (Neuladen, Zurück-Taste, Link von einem Kollegen), wenn die Seite lädt, dann holt die App den aktuellen Stand aus Zammad.
- [ ] Angenommen ein Ticket ist angezeigt, wenn der Nutzer auf „Aktualisieren" klickt, dann wird es neu aus Zammad geladen, und neue Nachrichten erscheinen.
- [ ] Angenommen ein Ticket ist angezeigt, wenn der Nutzer oben im Eingabefeld ein anderes Ticket einfügt, dann wird das andere Ticket geladen.
- [ ] Angenommen ein Ticket wurde geladen, wenn danach in der App nachgesehen wird, dann ist nichts vom Ticketinhalt dauerhaft gespeichert.

### Kopfdaten
- [ ] Angenommen ein Ticket wurde geladen, wenn der Nutzer den Kopf betrachtet, dann sieht er Ticketnummer, Betreff, Status, Gruppe, Kunde (Name und E-Mail-Adresse), Erstellungsdatum, Zeitpunkt der letzten Nachricht und die Anzahl der Nachrichten.
- [ ] Angenommen ein Ticket ist angezeigt, wenn der Nutzer auf „In Zammad öffnen" klickt, dann öffnet sich das Ticket in Zammad in einem neuen Tab.
- [ ] Angenommen das Ticket ist geschlossen, wenn es angezeigt wird, dann steht im Kopf deutlich „Geschlossen"; es lässt sich trotzdem ansehen.
- [ ] Angenommen das Ticket wurde in Zammad mit einem anderen zusammengeführt, wenn es angezeigt wird, dann steht ein Hinweis mit der Nummer des Ziel-Tickets und einem Link, der dieses Ticket in der App lädt.

### Verlauf
- [ ] Angenommen ein Ticket hat mehrere Nachrichten, wenn der Verlauf angezeigt wird, dann stehen sie chronologisch, die älteste oben und die neueste unten.
- [ ] Angenommen der Verlauf wird angezeigt, wenn der Nutzer eine Nachricht betrachtet, dann sieht er die Art (vom Kunden, von uns, interne Notiz), Absendername, Datum und Uhrzeit und den Text; die drei Arten unterscheiden sich deutlich in Farbe und Beschriftung.
- [ ] Angenommen der Verlauf enthält interne Notizen, wenn er angezeigt wird, dann sind sie an ihrer Stelle im Verlauf sichtbar und als „Intern" gekennzeichnet.
- [ ] Angenommen der Verlauf enthält von uns versendete Antworten, wenn er angezeigt wird, dann sind sie vollständig Teil des Verlaufs.
- [ ] Angenommen der Verlauf wird angezeigt, wenn der Nutzer die neueste Nachricht sucht, dann ist sie hervorgehoben, und die Seite springt nach dem Laden zu ihr.
- [ ] Angenommen eine E-Mail enthält am Ende ältere zitierte Nachrichten (z. B. „Am 03.10.2026 schrieb …" oder „> …"), wenn sie angezeigt wird, dann ist das Zitat eingeklappt und per Klick „Zitat anzeigen" aufklappbar.
- [ ] Angenommen ein Ticket hat mehr als 10 Nachrichten, wenn der Verlauf angezeigt wird, dann sind die erste und die letzten fünf Nachrichten offen und die übrigen zu „N weitere Nachrichten anzeigen" zusammengefasst.
- [ ] Angenommen eine Nachricht ist als HTML-E-Mail geschrieben, wenn sie angezeigt wird, dann erscheint ihr Text lesbar mit Absätzen, Listen und Links, aber ohne Skripte, eingebettete Bilder, Schriftarten oder Formatierungen aus der E-Mail.
- [ ] Angenommen eine Nachricht enthält Links, wenn der Nutzer sie anklickt, dann öffnen sie sich in einem neuen Tab.

### Anhänge
- [ ] Angenommen eine Nachricht hat Anhänge, wenn sie angezeigt wird, dann steht darunter je Anhang Dateiname, Art (z. B. Bild, PDF) und Größe.
- [ ] Angenommen ein Ticket hat Anhänge, wenn der Kopf angezeigt wird, dann steht dort „N Anhänge – in Zammad ansehen" mit Link zum Ticket.
- [ ] Angenommen ein Anhang ist ein in die E-Mail eingebettetes Bild (z. B. Logo in der Signatur), wenn die Anhänge gelistet werden, dann erscheint es nicht in der Liste.

### Fehler
- [ ] Angenommen die Ticketnummer gibt es in Zammad nicht, wenn geladen wird, dann erscheint „Ticket#… wurde in Zammad nicht gefunden. Bitte die Nummer prüfen.", und die Eingabe bleibt im Feld.
- [ ] Angenommen die App darf das Ticket in Zammad nicht lesen, wenn geladen wird, dann erscheint „Auf Ticket#… hat die App in Zammad keinen Zugriff." mit dem Hinweis, sich an den Entwickler zu wenden.
- [ ] Angenommen Zammad ist nicht erreichbar oder antwortet nicht innerhalb von 10 Sekunden, wenn geladen wird, dann erscheint „Zammad ist gerade nicht erreichbar. Bitte in einer Minute erneut versuchen." mit einem Knopf „Erneut versuchen".
- [ ] Angenommen die Zugangsdaten der App für Zammad sind ungültig oder fehlen, wenn geladen wird, dann erscheint eine verständliche Meldung ohne technische Details, und der genaue Fehler steht im Log der App.
- [ ] Angenommen ein Fehler tritt auf, wenn die Meldung erscheint, dann enthält sie keine Zugangsdaten, keine Server-Adressen und keine technischen Fehlertexte von Zammad.

## Edge Cases
- **Sehr lange Nachricht** (z. B. weitergeleitete Mail-Kette): Der Text bleibt vollständig erhalten; Zitate sind eingeklappt, und die Seite bleibt ohne waagrechtes Scrollen lesbar.
- **Sehr viele Nachrichten** (z. B. 40): Die Zusammenfassung nach dem Muster oben greift; die Ladezeit bleibt unter dem Grenzwert.
- **Nachricht ohne Text** (nur Anhang): Statt eines Texts erscheint „(kein Text, nur Anhang)".
- **Nachricht über andere Kanäle** (Telefonnotiz, Web-Formular, Amazon-Nachricht): Sie erscheint mit der passenden Art (vom Kunden, von uns oder intern); der Kanal wird klein mit angezeigt, soweit Zammad ihn liefert.
- **Systemnachrichten oder automatische Antworten** (z. B. Eingangsbestätigung): Sie werden wie unsere Antworten angezeigt und als „automatisch" gekennzeichnet, soweit Zammad das erkennbar macht.
- **Zeichensätze und Umlaute:** Umlaute und Sonderzeichen erscheinen korrekt, auch bei E-Mails mit anderem Zeichensatz.
- **Doppeltes Laden** (zweimal schnell „Einfügen" oder Enter): Es wird nur einmal geladen.
- **Ticketnummer mit führenden Nullen oder sehr langer Zahl:** Ungültige Längen werden wie eine falsche Eingabe behandelt; die gültige Länge legt `/architecture` nach den Zammad-Einstellungen fest.
- **Zammad liefert das Ticket, aber keine Nachrichten:** Kopfdaten erscheinen, der Verlauf zeigt „Dieses Ticket enthält noch keine Nachrichten."
- **Nutzer ohne gewählten Namen:** Laden und Ansehen funktionieren; der Hinweis aus PROJ-5 bleibt sichtbar.

## Technical Requirements (optional)
- Ladezeit: ein typisches Ticket (bis 10 Nachrichten) steht in unter 3 Sekunden; Abbruch nach 10 Sekunden ohne Antwort.
- Zugriff auf Zammad nur lesend mit einem eigenen Zugang für die App; die Zugangsdaten stehen in der Umgebungskonfiguration, nie im Code und nie im Browser.
- Ticketinhalte enthalten Kundendaten: keine dauerhafte Speicherung in PROJ-6, keine Ticketinhalte im Log.
- Inhalte aus E-Mails werden vor der Anzeige bereinigt (kein ausführbarer Code, keine extern nachgeladenen Inhalte).
- Gestaltung nach `docs/design-system.md`; Lade-Overlay aus PROJ-1.

## Open Questions
- [ ] Der Knopf „Einfügen" braucht eine verschlüsselte Verbindung (HTTPS), außer auf `localhost`. Läuft die App im internen Netz unter HTTPS? Ohne HTTPS bleibt nur Strg+V. Mit `/deploy` klären.
- [ ] Zammad-Zugang: Ein eigener Nutzer „Customer Service Assist" mit Leserechten auf die Kundenservice-Gruppen und einem Token mit dem Recht `ticket.agent` (Entscheidung `/architecture`). Offen: Wer legt ihn an, und für welche Gruppen genau?
- [ ] Wie lang sind Ticketnummern in eurer Zammad-Installation höchstens? Wird für die Prüfung der Eingabe gebraucht.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Eingabe akzeptiert den Text des Zammad-Copy-Knopfs (`Ticket#2137942`) und bereinigt ihn | So kommt das Ticket ohne Abtippen in die App; Vorgabe des Product Owners | 2026-10-05 |
| Knopf „Einfügen" liest die Zwischenablage und lädt sofort | Ein Klick statt zwei im häufigsten Ablauf | 2026-10-05 |
| Bereinigung im Browser und zusätzlich Prüfung auf dem Server | Der Browser hilft dem Nutzer; der Server verlässt sich nie auf den Browser | 2026-10-05 |
| Ganzer Thread: Kundennachrichten, unsere Antworten, interne Notizen, klar gekennzeichnet | Ein Ticket ist oft ein längerer Wechsel aus Antwort, Rückfrage und Erwiderung; interne Notizen tragen wichtiges Fallwissen | 2026-10-05 |
| Chronologisch, neueste hervorgehoben, Zitate eingeklappt, ab 10 Nachrichten Mitte zusammengefasst | Liest sich wie ein Gespräch; Zitate stehen ohnehin als eigene Nachricht im Verlauf | 2026-10-05 |
| Anhänge als Liste mit Link zu Zammad, keine Fotos in der App | Prüfung wie heute in Zammad (PRD); keine Kundenfotos über die App | 2026-10-05 |
| Eigene Adresse je Ticket, jeder Aufruf frisch aus Zammad, nichts gespeichert | Neuladen und Link an Kollegen funktionieren; keine veralteten Stände, keine Kundendaten in der App bis PROJ-11 | 2026-10-05 |
| Geschlossene Tickets ansehbar, zusammengeführte mit Link zum Ziel-Ticket | Reklamationen kommen oft auf geschlossene Tickets zurück; zusammengeführte Tickets führen sonst ins Leere | 2026-10-05 |
| Laden ohne gewählten Namen erlaubt | Der Name wird erst für Analyse und Protokoll gebraucht (PROJ-5) | 2026-10-05 |
| Fehler in klarer Sprache mit nächstem Schritt, technische Details nur im Log | Aushilfen sollen wissen, was zu tun ist; keine internen Details auf dem Bildschirm | 2026-10-05 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|
| Zammad-REST-Schnittstelle, eigener Nutzer mit Lesezugriff und Token | Offizieller Weg in Zammad 7; nachvollziehbar und sperrbar; Token nur auf dem Server | 2026-10-05 |
| Laravel-HTTP-Client, kein Zammad-Paket | Zeitlimit, Fehlerbehandlung und Test-Attrappen eingebaut; wenige Endpunkte nötig | 2026-10-05 |
| Eigener Baustein `app/Zammad/` mit eigenem Ticket-Objekt | PROJ-7/9/11 bleiben unabhängig von Zammad-Details; Änderungen an einer Stelle | 2026-10-05 |
| Normales Formular mit Weiterleitung auf `/tickets/{nummer}`, Alpine nur für Komfort | Adresse je Ticket, Neuladen und Zurück funktionieren; Server prüft die Eingabe selbst | 2026-10-05 |
| `symfony/html-sanitizer` für E-Mail-HTML | Bewährte, gepflegte Bibliothek statt eigener Regeln; E-Mails von außen sind nicht vertrauenswürdig | 2026-10-05 |
| Zitat-Erkennung nach festen Mustern, alles ab dem ersten Treffer eingeklappt | Nachvollziehbar; im Zweifel bleibt der Text vollständig sichtbar | 2026-10-05 |
| Vier Fehlerarten mit HTTP-Status 404/403/503, Log nur mit Nummer und Fehlerart | Verständliche Meldungen, keine Kundendaten im Log | 2026-10-05 |
| Kein Zwischenspeicher | Spec verlangt frische Ansicht und keine Speicherung; Last für Zammad gering | 2026-10-05 |
| Ticketnummer: 1–20 Ziffern, bis die echte Länge bekannt ist | Schutz vor unsinnigen Abfragen; später enger fassen | 2026-10-05 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

### Überblick
Die App liest Tickets über die **REST-Schnittstelle von Zammad 7**, nur lesend und mit einem eigenen Zugangs-Token. Jeder Aufruf der Ticketseite fragt Zammad direkt. Es gibt keine Datenbank, keinen Zwischenspeicher, und in den Logs stehen keine Ticketinhalte. Die Zammad-Antwort wird auf dem Server in ein eigenes, einfaches Ticket-Objekt übersetzt. Dabei werden E-Mail-HTML bereinigt, Zitate erkannt und Nachrichten eingeordnet. Die Seite zeigt dieses Objekt nur noch an.

### A) Bausteine
```
Seite „Ticket analysieren" (/)                       Ticketseite (/tickets/{nummer})
+-- Eingabe (neue Komponente, auf beiden Seiten)     +-- Eingabe (oben, für das nächste Ticket)
|   +-- Feld (Fokus), „Einfügen", „Ticket laden"     +-- Fehlermeldung (falls Zammad-Fehler)
|   +-- Meldung am Feld bei ungültiger Eingabe       +-- Kopf (Karte)
+-- Hinweis Nutzerauswahl (PROJ-5)                   |   +-- Nummer, Betreff, Status-Badge, Gruppe
                                                     |   +-- Kunde (Name, E-Mail), erstellt, letzte Nachricht
                                                     |   +-- Anzahl Nachrichten, „N Anhänge – in Zammad ansehen"
                                                     |   +-- Hinweis „Geschlossen" / „Zusammengeführt mit …"
                                                     |   +-- „In Zammad öffnen", „Aktualisieren"
                                                     +-- Verlauf
                                                         +-- Nachricht (neue Komponente), drei Arten:
                                                         |   Kunde (weiß) · wir (Brand-Tönung) · intern (Warn-Tönung, „Intern")
                                                         |   +-- Art, Name, Datum/Uhrzeit, Kanal, ggf. „automatisch"
                                                         |   +-- bereinigter Text, Zitat eingeklappt (bestehende Aufklapp-Komponente)
                                                         |   +-- Anhangliste (Name, Art, Größe)
                                                         +-- „N weitere Nachrichten anzeigen" (ab 11 Nachrichten)
                                                         +-- neueste Nachricht hervorgehoben, Sprung dorthin

Server
+-- Zammad-Anbindung (eigener Baustein in app/Zammad/)
|   +-- Ticket über die Nummer suchen, Nachrichten des Tickets holen
|   +-- 10 s Zeitlimit, klare Fehlerarten: nicht gefunden · kein Zugriff · nicht erreichbar · Zugang ungültig
+-- Übersetzung in ein eigenes Ticket-Objekt (Kopf, Nachrichten, Anhänge)
|   +-- HTML-Bereinigung · Zitat-Erkennung · Art der Nachricht · eingebettete Bilder aussortieren
+-- Eingabe-Prüfung (Form Request): Nummer aus „Ticket#…" lösen, nur Ziffern erlauben
+-- Ticket-Controller: Eingabe → Weiterleitung auf /tickets/{nummer}; Ticketseite anzeigen
```

### B) Daten (nichts wird gespeichert)
- **Ticket (im Speicher, je Aufruf):** Nummer, interne Zammad-ID (für den Link), Betreff, Status, ob geschlossen oder zusammengeführt (mit Ziel-Nummer), Gruppe, Kunde (Name, E-Mail), Erstellungszeit, Zeit der letzten Nachricht, Nachrichten.
- **Nachricht:** Art (Kunde, wir, intern), automatisch ja/nein, Kanal (E-Mail, Telefon, Notiz, Web …), Absendername, Zeitpunkt, bereinigter Haupttext, eingeklapptes Zitat (falls erkannt), Anhänge.
- **Anhang:** Dateiname, Art (aus dem Dateityp: Bild, PDF, Dokument, Sonstiges), Größe. Eingebettete Bilder aus dem E-Mail-Text (etwa Signatur-Logos) werden aussortiert.
- **Zuordnung der Arten:** Ist eine Nachricht in Zammad als intern markiert, gilt sie als *intern*. Sonst ist der Absender ein Kunde (*Kunde*) oder ein Mitarbeiter bzw. das System (*wir*). Systemnachrichten gelten als *automatisch*.
- **Konfiguration:** Zammad-Adresse, Token und Zeitlimit in der Umgebung (`.env`), eingelesen über `config/services.php`.

### C) Technische Entscheidungen (für Nicht-Entwickler)
- **REST-Schnittstelle mit eigenem Token:** Das ist der offizielle Weg in Zammad 7. Ein eigener Nutzer „Customer Service Assist" mit reinen Leserechten macht nachvollziehbar, was die App tut, und lässt sich jederzeit sperren. Das Token liegt nur auf dem Server, nie im Browser.
- **Laravels eingebauter HTTP-Client:** Er bringt Zeitlimit und Fehlerbehandlung mit. In Tests lässt sich Zammad vollständig nachstellen, ohne echtes System. Ein zusätzliches Zammad-Paket ist nicht nötig.
- **Eigener Zammad-Baustein mit eigenem Ticket-Objekt:** Die Seite und später PROJ-7, PROJ-9 und PROJ-11 kennen nur dieses Objekt, nicht die Eigenheiten von Zammad. Ändert sich Zammad, wird nur dieser Baustein angepasst.
- **Ticketseite mit eigener Adresse, normales Seitenladen:** Neuladen, die Zurück-Taste und Links an Kollegen funktionieren wie gewohnt. Die Eingabe schickt ein normales Formular. Der Server löst die Nummer aus „Ticket#…" und leitet auf `/tickets/{nummer}` weiter. JavaScript (Alpine.js) bereinigt die Eingabe schon vorher und liefert die Bequemlichkeiten: den Knopf „Einfügen", das Lade-Overlay, die Sperre gegen doppeltes Absenden und den Sprung zur neuesten Nachricht.
- **„Einfügen" über die Zwischenablage-Funktion des Browsers:** Klappt das nicht (keine Berechtigung, kein HTTPS), erscheint der Hinweis „Bitte mit Strg+V einfügen". Ein Fehler entsteht dabei nicht.
- **HTML-Bereinigung mit einer bewährten Bibliothek statt eigener Regeln:** E-Mails von außen können Schadcode enthalten. Erlaubt bleiben nur Absätze, Zeilenumbrüche, Listen, Hervorhebungen, Zitate und Links (in neuem Tab). Bilder, Stile, Schriften und Skripte werden entfernt, nachgeladene Inhalte sind ausgeschlossen. Empfohlen ist **`symfony/html-sanitizer`**: aus dem Symfony-Projekt, auf dem Laravel aufbaut, aktiv gepflegt und genau für diesen Zweck gebaut.
- **Zitat-Erkennung nach festen Mustern:** Erkannt werden E-Mail-Zitatblöcke, Zeilen mit „>", „Am … schrieb …", „On … wrote:", „-----Ursprüngliche Nachricht-----" und Outlook-Köpfe („Von: … Gesendet: …"). Alles ab dem ersten Treffer wird eingeklappt und geht nie verloren. Erkennt die App ein Zitat nicht, steht der Text vollständig da. Das ist der sichere Fehlerfall.
- **Fehlerarten statt technischer Meldungen:** Der Baustein übersetzt Zammad-Antworten in vier Fälle. Die Seite zeigt dazu die Texte aus der Spec, mit passendem HTTP-Status (404, 403, 503). Ins Log kommen nur Ticketnummer, Fehlerart und Status-Code, kein Inhalt.
- **Ohne Zwischenspeicher:** Die Spec verlangt, dass jede Ansicht frisch ist und nichts gespeichert wird. Bei 10–100 Tickets am Tag ist der Aufwand für Zammad unerheblich.
- **Prüfung der Nummer:** 1 bis 20 Ziffern. Genauer lässt es sich festlegen, sobald die Nummernlänge bekannt ist (offene Frage). Alles andere wird abgewiesen, bevor Zammad gefragt wird.

### D) Abhängigkeiten
- **`symfony/html-sanitizer`** (neu, vom Product Owner am 2026-10-05 freigegeben): sichere Bereinigung des E-Mail-HTML.
- Sonst nichts Neues. HTTP-Client, Alpine.js und die bestehenden Komponenten (Karte, Badge, Alert, Aufklappen, Button, Input, Lade-Overlay) sind vorhanden.

### E) Hinweise für /frontend und /backend
- **Frontend:** Komponenten für Eingabe (mit Alpine für Einfügen, Bereinigung und Sperre gegen doppeltes Absenden), Kopf und Nachricht. Die Ticketseite wird mit Beispieldaten gebaut, bis die Anbindung steht.
- **Backend:** `app/Zammad/` (Client, Ticket-, Nachrichten- und Anhang-Objekte, Bereinigung, Zitat-Erkennung), Form Request für die Eingabe, Controller und Routen, Einträge `ZAMMAD_URL`, `ZAMMAD_TOKEN`, `ZAMMAD_TIMEOUT` in `.env.example`.
- **Vor dem Bau gegen das echte Zammad prüfen:** die Feldnamen der Suche nach Nummer, die Kennzeichnung „zusammengeführt" (Status `merged` und Ziel-Ticket) und die Kennung eingebetteter Bilder.
- **Tests:** Zammad wird nachgestellt (Erfolg, 404, 401/403, Zeitüberschreitung, leeres Ticket, zusammengeführtes Ticket). Dazu Eingabe-Varianten, Bereinigung mit Schadcode-Beispielen, Zitat-Muster deutsch und englisch, die Zusammenfassung ab 11 Nachrichten und die Prüfung, dass im Log kein Inhalt steht.


## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
