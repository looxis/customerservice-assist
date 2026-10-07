# PROJ-6: Zammad-Ticket laden

## Status: Approved
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
- [ ] Angenommen eine Nachricht ist als HTML-E-Mail geschrieben, wenn sie angezeigt wird, dann erscheint ihr Text lesbar mit Absätzen, Zeilen und Listen, aber ohne Skripte, eingebettete Bilder, Schriftarten oder Formatierungen aus der E-Mail.
- [ ] Angenommen eine Nachricht enthält klickbare Links (auch im Zitat), wenn sie angezeigt wird, dann ist keiner anklickbar: ein Link auf eine zugelassene Adresse (vorerst `looxis.de`, `looxis.com`, `dhl.de` samt Subdomains) bleibt als Text sichtbar, jeder andere wird durch „[Link entfernt]" ersetzt, ein beschreibender Linktext bleibt davor stehen. Web-Adressen, die als reiner Text in der Mail stehen, bleiben unverändert und sind nicht anklickbar.
- [ ] Angenommen der Verlauf wird auf einem Bildschirm ab 768 px angezeigt, wenn der Nutzer ihn betrachtet, dann sind alle Nachrichten gleich breit (vier Fünftel); Nachrichten vom Kunden stehen links mit orangefarbener Kante, unsere Nachrichten und interne Notizen rechts eingerückt (unsere mit grauer, interne mit gelber Kante). Darunter nutzen alle die volle Breite.
- [ ] (Geändert mit PROJ-7: Der Amazon-Block geht im Bereich „Bestellungen" auf; ungeladen zeigt er Nummer, Quelle und Produkte mit ASIN sowie „Aus EOCS laden", Rechnungsnummer und Status kommen nach dem Laden aus EOCS.) Angenommen eine Amazon-Käufernachricht wird angezeigt, wenn der Nutzer sie liest, dann fehlen darin „Du hast eine Nachricht erhalten", Bestellnummer, Produkttabelle und „Nachricht:"; stattdessen zeigt der Ticketkopf einmal je Bestellung Bestellnummer, Rechnungsnummer und die Produkte mit Name, ASIN, SKU und Anzahl, unbekannte Angaben als „–".
- [ ] Angenommen eine Mail enthält einen bekannten Textbaustein (z. B. Amazons Hinweis „Dieser Service wird ausschließlich für die Kommunikation mit Käufern angeboten"), wenn sie angezeigt wird, dann ist der Baustein samt allem, was danach folgt, ausgeblendet.
- [ ] Angenommen eine Nachricht enthält sehr lange Zeilen ohne Umbruch, wenn sie angezeigt wird, dann bricht der Text am sichtbaren Rand um, und es ist kein waagrechtes Scrollen nötig.
- [ ] Angenommen eine Nachricht enthält viele Leerzeilen, wenn sie angezeigt wird, dann steht zwischen zwei Textzeilen höchstens eine Leerzeile, und am Anfang und Ende stehen keine.

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
| Klickbare Links werden entfernt (statt in neuem Tab zu öffnen); zugelassene Adressen bleiben als Text, reine Text-Adressen bleiben unverändert | Für die Bearbeitung nicht nötig; Phishing-Mail mit Google-Drive-Link (Ticket#2137941) hat den Spam-Filter überwunden. Eigene und Versand-Adressen (looxis.de, dhl.de) tragen Information. Entfernen in Zammad selbst ist ein eigenes Thema | 2026-10-05 |
| Liste zugelassener Adressen vorerst in `config/services.php`, später auf einer Einstellungsseite (PROJ-27) | Product Owner will die Liste ohne Entwickler pflegen | 2026-10-05 |
| Amazon-Seller-Central-Links bleiben klickbar | „Fall lösen" führt direkt zur Bearbeitung bei Amazon; es ist die echte Amazon-Adresse | 2026-10-05 |
| Textbausteine ab einem Markierungstext ausblenden, Liste in der Konfiguration | Amazon-Mails bestehen überwiegend aus Hinweisen und Signatur, die die Ansicht erschweren | 2026-10-05 |
| Kunde links, wir rechts eingerückt, alle vier Fünftel breit; Kanten getauscht (Kunde orange, wir grau) | Liest sich wie ein Gespräch; die Kundennachricht ist das, worauf es ankommt | 2026-10-05 |
| Höchstens eine Leerzeile in der Anzeige | Lange Threads mit vielen Leerzeilen sind schwer lesbar | 2026-10-05 |

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


## Implementation Notes (Frontend + Backend)
**Gebaut am 2026-10-05**, Frontend und Backend in einem Durchgang. Gegen ein echtes Zammad noch **nicht** geprüft: Zugang und Beispielticket liefert der Product Owner nach.

- **Seiten und Routen:** `/` mit Eingabe (`x-ticket.lookup`); `GET /tickets?ticket=…` (`tickets.lookup`, `TicketLookupRequest` löst die Nummer aus „Ticket#…", 1–20 Ziffern, sonst zurück mit Meldung am Feld); `GET /tickets/{nummer}` (`tickets.show`, nur Ziffern). Lokale Vorschau mit erfundenem Ticket: `/styleguide/ticket` (`?antworten=12` für einen langen Verlauf), nur in der lokalen Umgebung.
- **Komponenten:** `ticket/lookup` (Alpine `ticketLookup`: Einfügen über `navigator.clipboard.readText`, Bereinigung, Sperre gegen doppeltes Absenden, Lade-Overlay; ohne Berechtigung „Bitte mit Strg+V einfügen"), `ticket/header`, `ticket/article` (drei Arten mit farbiger linker Kante und Badge, interne Notizen gelb getönt, neueste mit Ring und Sprung `scrollIntoView`), `ticket/thread` (ab 11 Nachrichten: erste und letzte fünf offen, Mitte als `<details>`).
- **Zammad-Baustein `app/Zammad/`:** `ZammadClient` (Suche `tickets/search?query=number:…&expand=true`, Nachrichten `ticket_articles/by_ticket/{id}?expand=true`, Kunde `users/{id}`, Ziel eines zusammengeführten Tickets über `links`, Token-Header, Zeitlimit aus der Konfiguration), Objekte `Ticket`, `TicketArticle`, `TicketAttachment`, `ArticleKind`, Fehler `ZammadException`/`ZammadProblem` (404/403/503, Texte aus der Spec). Ins Log kommen nur Nummer, Fehlerart und HTTP-Status. Zeiten in Europe/Berlin.
- **`MessageBody`:** Zitat-Erkennung vor der Bereinigung (DOM: `blockquote`, Gmail-/Outlook-/Thunderbird-Marker, deutsche und englische Kopfzeilen; Text: `>` und Kopfzeilen), danach `symfony/html-sanitizer`. Erlaubt sind Text-Struktur und Links (`http`, `https`, `mailto`, neuer Tab, `noopener noreferrer nofollow`). Unbekannte Hüllen-Elemente werden entfernt, ihr Text bleibt. Skripte, Stile, Bilder, Frames und Formulare entfallen komplett. Keine Längenbegrenzung. Reiner Text wird escaped, Absätze und Zeilenumbrüche bleiben.
- **Konfiguration:** `config/services.php` → `zammad.url`, `zammad.token`, `zammad.timeout` (Standard 10 s). **Noch offen:** `.env.example` um `ZAMMAD_URL=`, `ZAMMAD_TOKEN=`, `ZAMMAD_TIMEOUT=10` ergänzen. Die Datei ist für den Assistenten gesperrt, der Product Owner trägt sie ein.
- **Neue Abhängigkeit:** `symfony/html-sanitizer` ^8.1 (freigegeben).
- **Zammad-Zugang (eingerichtet 2026-10-05):** eigener Zammad-Nutzer „Customer Service Assist" (Rolle Agent, Gruppen nur „lesen"), persönlicher Zugriffstoken mit ausschließlich `ticket.agent`. **Der Token läuft am 31.12.2029 ab** und muss vorher im Profil dieses Nutzers neu erstellt und in `ZAMMAD_TOKEN` eingetragen werden. Nach Ablauf meldet die App „Die Verbindung zu Zammad ist nicht eingerichtet oder ungültig".
- **Abweichungen und Hinweise:**
  - Status-Badge zeigt den deutschen Zammad-Status („Offen", „Neu", …); geschlossene und zusammengeführte Tickets gelten als geschlossen.
  - Der Knopf „Aktualisieren" ist ein Link auf dieselbe Adresse (normales Neuladen).
  - Doppeltes Absenden verhindert die Komponente selbst; PROJ-1 BUG-3 (Lade-Overlay sperrt die Tastatur nicht) bleibt für das übrige Overlay offen.
  - Ohne Zammad-Konfiguration zeigt die Ticketseite „Die Verbindung zu Zammad ist nicht eingerichtet oder ungültig" (503).
- **Optimierung nach erstem Test mit echtem Ticket (2026-10-05, Ticket#2132884, niederländischer Kunde, 12 Nachrichten):**
  - Zitat-Erkennung sucht jetzt in Lesereihenfolge in jeder Verschachtelungstiefe und klappt alles ab dem ersten Zitat-Beginn ein, auch was danach in umschließenden Elementen folgt.
  - Neue Muster: Niederländisch („Op … heeft … het volgende geschreven:", „Begin doorgestuurd bericht:", „Van:/Verzonden:", „Oorspronkelijk bericht"), Apple Mail Deutsch/Englisch („Anfang der weitergeleiteten Nachricht:", „Begin forwarded message:"), Französisch („Le … a écrit :"), dazu Zammads eigene Markierung `js-signatureMarker`.
  - Antworten unter dem Zitat: Beginnt eine Mail mit dem Zitat und folgt danach neuer Text, wird der Zitatblock am Anfang eingeklappt und der neue Text gezeigt.
  - Unsere Signatur wird bei Nachrichten „von uns" ausgeblendet: der von Zammad markierte Block (`data-signature`) sowie konfigurierte Text-Signaturen ohne Markierung (`services.zammad.signatures`, vorbelegt mit „Freundliche Grüße / LOOXIS Kundenservice" für Amazon), nur am Ende, unabhängig von Groß-/Kleinschreibung und Zeilenumbrüchen. Die Grußformel davor („Best regards") bleibt stehen. Kundensignaturen bleiben unverändert.
  - `<div>` bleibt als Zeile erhalten (vorher liefen Zeilen aus Apple Mail/Gmail zusammen); Überschriften `h1`–`h6` erlaubt.
  - Ergebnis am echten Ticket: alle 12 Nachrichten zeigen nur den neuen Text, Zitate eingeklappt, keine eigene Signatur mehr.
- **Zweite Optimierung (2026-10-05):**
  - Links: `<a>` wird vor der Bereinigung durch seinen Text plus „[Link entfernt]" ersetzt (nur „[Link entfernt]", wenn der Text selbst eine Adresse ist oder fehlt). Gilt für alle Nachrichten und Zitate. Geprüft an Ticket#2137941 (Phishing, Google-Drive-Link): kein Link mehr auf der Seite.
  - Leerzeilen: leere Blöcke werden zu einem Umbruch, Umbrüche am Blockende entfallen, nach einem Block höchstens eine Leerzeile, im Text höchstens zwei Umbrüche in Folge, nichts Leeres am Anfang und Ende.
  - **Nachgeschärft:** Nur klickbare Links werden entfernt. Links auf zugelassene Hosts (`services.zammad.allowed_link_hosts`: `looxis.de`, `looxis.com`, `dhl.de` samt Subdomains, nur `http`/`https`) bleiben als nicht anklickbarer Text; Adressen als reiner Text bleiben unverändert. Pflege der Liste später über PROJ-27.
  - Layout: Kunde links (orange Kante), wir und interne Notizen rechts eingerückt (grau bzw. gelb), alle `md:w-4/5`; unter 768 px volle Breite.
  - **Dritte Optimierung (Ticket#2137635, Amazon, Kundin schreibt italienisch):**
    - Ausblenden von Textbausteinen: ab dem ersten Text aus `services.zammad.footers` wird der Rest einer Mail entfernt (HTML in Lesereihenfolge in jeder Tiefe, Text zeilenweise; Groß-/Kleinschreibung und Zeilenumbrüche egal). Vorbelegt mit Amazons Hinweis „Dieser Service wird ausschließlich für die Kommunikation mit Käufern angeboten" – damit entfallen Hinweis, Amazon-Signatur, Umfrage- und Abmelde-Links. Am echten Ticket: Kundennachricht von 2.053 auf 429 Zeichen.
    - Klickbare Links: `services.zammad.clickable_link_hosts` (vorbelegt `sellercentral.amazon.*`, `*` = Länderendung wie `it`, `de`, `co.uk`; nur `https`) bleiben klickbar in neuem Tab. Damit sind bei Amazon „Nachricht anzeigen", „Fall lösen" und „Verdächtige Aktivitäten melden" klickbar; Bild-Links ohne Text entfallen ohne Marker. Feinere Regeln je Link später über PROJ-27.
    - Umbruch langer Zeilen: `<pre>` umbricht am sichtbaren Rand (`pre-wrap`), Tabellen sind auf Spaltenbreite begrenzt und Zellen brechen um; kein waagrechtes Scrollen mehr.
  - **Vierte Optimierung (Ticket#2137635):** `AmazonNotice` liest aus Amazons Käufer-Nachricht Bestellnummer und Produkttabelle (ASIN, Produktname; die Spalte „#" ist nur die Position) und entfernt „Du hast eine Nachricht erhalten.", die Bestellnummer-Zeile, die Tabelle und das Label „Nachricht:" aus jeder Amazon-Nachricht. Der Ticketkopf zeigt je Bestellung einmal einen Block „Bestellung · aus Amazon-Nachricht" mit Bestellnummer, Rechnungsnummer und Produkten (Name, ASIN, SKU, Anzahl); was die Mail nicht enthält (Rechnungsnummer, SKU, Anzahl), steht als „–" und wird mit PROJ-7 aus EOCS gefüllt. Gleiche Bestellung in mehreren Nachrichten erscheint einmal, Produkte je ASIN einmal. Englische Varianten („You have received a message", „Order ID", „Product Name", „Message:") werden ebenfalls erkannt.
  - Signaturen: Varianten im Team unterscheiden sich stark (Sprache, Kanal). Einheitliche Form ist organisatorisch zu klären (auf der About-Seite unter „Noch zu klären" vermerkt); Pflege der Varianten später in PROJ-27.
  - Eigene Textformatierung `.mail-text` statt `.knowledge-text`: Zeilen (`<div>`) ohne Zusatzabstand, nur Absätze mit Abstand. Vorher erzeugte der Absatzabstand der Knowledge-Seite zwischen jeder Mail-Zeile eine scheinbare Leerzeile.
- **Gegen das echte Zammad bestätigt:** Suche mit `expand=true` liefert `state`, `group`, `customer_id` als erwartet; Nachrichten liefern `sender` (Customer/Agent), `type`, `internal`, `content_type` text/html. Zammad legt zu jeder eingehenden HTML-Mail eine Kopie `message.html` als Anhang ab (`content-alternative`/`original-format`); sie wird jetzt nicht als Anhang gelistet und nicht mitgezählt.
- **Signatur-Varianten im Team (Stand 2026-10-05, gesammelt aus echten Tickets; Grundlage für die Klärung im Team und für PROJ-27):**

  | Wer | Sprache / Kanal | Von Zammad markiert? | Aufbau (am Ende der Nachricht) | Fundstelle |
  |---|---|---|---|---|
  | Etienne Renaud | Englisch, E-Mail (Gruppe „allgemeine Kunden") | ja (`data-signature`, wird ausgeblendet) | „Best regards" / Etienne Renaud / LOOXIS GmbH / Magdeburger Str. 11 / 32423 Minden / Germany | Ticket#2132884 |
  | n8n-Workflow (Absender Etienne Renaud) | Englisch, Versand-Info zu Amazon-Bestellung | nein (reiner Text) | „Kind regards" / LOOXIS Kundenservice | Ticket#2137635 |
  | Amazon-Vorlage (laut Product Owner) | Deutsch, Amazon | nein | „Freundliche Grüße" / LOOXIS Kundenservice (in der Konfiguration hinterlegt, wird ausgeblendet) | – |
  | Kerstin Schmeckpeper | Italienisch, Amazon | nein | „Cordiali saluti," / Kerstin Schmeckpeper / Looxis GmbH | Ticket#2137635 |
  | Nele Gorka | Französisch, Amazon | nein | „Avec les salutations de l'équipe LOOXIS," / Nele Gorka | Ticket#2137945 |

  Beobachtungen: Nur Etiennes Signatur kommt aus einer Zammad-Signaturvorlage (vermutlich die der Gruppe „allgemeine Kunden") und wird deshalb zuverlässig erkannt. Die übrigen sind von Hand geschrieben, unterscheiden sich in Grußformel, Firmenschreibweise („LOOXIS GmbH" / „Looxis GmbH") und Umfang (mit/ohne Adresse) und werden nicht ausgeblendet. Zu klären im Team: Signaturvorlagen je Gruppe und Sprache in Zammad, einheitliche Firmenschreibweise, ob Amazon-Antworten eine Signatur tragen sollen.
- **Kundensignaturen eingeklappt (2026-10-06, nach dem Test von PROJ-9):** Bei Kundennachrichten wird nach einer Grußformel (Deutsch, Englisch, Niederländisch, Französisch, Italienisch, Spanisch; eigene Zeile, auch mit Namen dahinter) der Rest hinter „Signatur anzeigen" eingeklappt; die zwei Zeilen nach der Grußformel (meist Name und Firma) bleiben sichtbar. Eine Zeile „--" leitet die Signatur direkt ein. Eingeklappt wird nur, wenn davor Nachrichtentext steht und danach noch etwas folgt; nichts wird gelöscht. Die eingeklappte Signatur geht nicht an die KI (PROJ-9). Geprüft an Ticket#2138220 (doppelte Fachhändler-Signatur mit Impressum: 25 Zeilen eingeklappt). Vom Mitarbeiter markierte Signaturen je Absender folgen in PROJ-27.
- **Ticket-Adresse statt Nummer (2026-10-07, nach Ausfall des Zammad-Suchindex):** Das Eingabefeld nimmt auch die Adresse eines Tickets aus der Browserzeile von Zammad an (`…/#ticket/zoom/38698`, auch mit Artikel-ID dahinter). Die App liest die Nummer direkt über die Zammad-ID (ohne Suche), merkt sich das Paar Nummer → ID für 90 Tage und lädt das Ticket danach auch über die Nummer, wenn die Suche von Zammad es noch nicht kennt. Nur Adressen des konfigurierten Zammad-Hosts werden angenommen. Die Meldung „nicht gefunden“ weist auf diesen Weg hin. Anlass: Ticket#2138663 war wegen eines seit 08:21 Uhr hängenden Suchindex in Zammad nicht auffindbar (Ticket selbst vorhanden, Gruppe „allg. Kunden“).
- **Vor der Abnahme gegen das echte Zammad prüfen:** Feldnamen der Suche mit `expand=true` (`state`, `group`, `customer_id`), `sender`/`internal`/`type` der Nachrichten, Kennung eingebetteter Bilder, Ziel bei zusammengeführten Tickets, Länge der Ticketnummern.
- **Tests:** `tests/Feature/PROJ-6-ZammadTicketTest.php` (51 Fälle, Zammad nachgestellt). PROJ-1-Test an den neuen Startseitentext angepasst. Gesamte Suite: 483 Tests grün.

## QA Test Results

**Tested:** 2026-10-05
**App URL:** http://localhost:8081
**Tester:** QA Engineer (AI)

Geprüft mit Pest (Zammad nachgestellt) und gegen das echte Zammad (`hello.loox.is`) mit den Tickets #2132884 (12 Nachrichten, Niederländisch), #2137635 (Amazon, Italienisch), #2137945 (Amazon, Französisch), #2137941 (Phishing), #2137956 (geschlossen) sowie nicht vorhandenen Nummern. Darstellung, Einfügen-Knopf und Lesbarkeit hat der Product Owner während der Optimierungsrunden am 2026-10-05 im Browser geprüft; ein eigenes Browser-Werkzeug stand nicht zur Verfügung.

### Acceptance Criteria Status

#### Eingabe
- [x] Zentrales Feld mit Fokus, „Einfügen" und „Ticket laden"
- [x] `Ticket#…`, `ticket# …`, `#…`, reine Nummer, Leerzeichen und Zeilenumbruch führen zur Ticketadresse
- [x] Ungültige Eingabe (leer, Buchstaben, zwei Nummern, über 20 Ziffern, Liste, Skript) → Meldung am Feld, keine Anfrage an Zammad
- [x] Server prüft selbst (Form Request), unabhängig vom Browser
- [x] Einfügen per Zwischenablage, Hinweis „Bitte mit Strg+V einfügen" ohne Berechtigung (vom Product Owner im Browser genutzt; Fehlerfall nur am Code geprüft)

#### Laden und eigene Adresse
- [x] Eigene Adresse je Ticket, jeder Aufruf frisch aus Zammad (zwei Aufrufe = zwei Abrufe), „Aktualisieren", anderes Ticket über das Feld
- [x] Nichts vom Ticketinhalt gespeichert; Log enthält nur Nummer, Fehlerart, Status
- [x] Ladezeit echter Tickets 0,45–0,9 s (Anforderung < 3 s)

#### Kopfdaten
- [x] Nummer, Betreff, Status, Gruppe, Kunde (Name, E-Mail), erstellt, letzte Nachricht, Anzahl Nachrichten, Anhänge mit Link
- [x] „In Zammad öffnen" in neuem Tab
- [x] Geschlossenes Ticket deutlich markiert und ansehbar (#2137956)
- [x] Zusammengeführtes Ticket mit Link zum Ziel – nur mit nachgestelltem Zammad geprüft; im lesbaren Bereich gibt es kein zusammengeführtes Ticket

#### Verlauf
- [x] Chronologisch, drei Arten deutlich unterschieden, interne Notizen und unsere Antworten vollständig
- [x] Neueste hervorgehoben, Sprung dorthin
- [x] Zitate eingeklappt (Deutsch, Englisch, Niederländisch, Französisch, Apple/Gmail/Outlook/Zammad-Marker, in jeder Tiefe, auch Antwort unter dem Zitat); am echten 12-Nachrichten-Ticket alle korrekt
- [x] Ab 11 Nachrichten Mitte zusammengefasst
- [x] HTML bereinigt; klickbare Links entfernt bzw. als Text (zugelassene Hosts) oder klickbar (Seller Central); Text-Adressen unverändert
- [x] Höchstens eine Leerzeile; lange Zeilen umbrechen ohne waagrechtes Scrollen
- [x] Amazon-Kopf aus jeder Nachricht entfernt, Bestellung einmal im Ticketkopf (Italienisch und Französisch geprüft)
- [x] Amazon-Textbausteine ab „Dieser Service wird ausschließlich …" ausgeblendet
- [x] Kunde links, wir und interne Notizen rechts eingerückt

#### Anhänge
- [x] Name, Art, Größe; eingebettete Bilder und Zammads `message.html`-Kopie ausgelassen

#### Fehler
- [x] Nicht gefunden (404), kein Zugriff (403), nicht erreichbar/Zeitüberschreitung/keine JSON-Antwort (503 mit „Erneut versuchen"), Zugang ungültig oder fehlend (503)
- [x] Keine Zugangsdaten, Adressen oder technischen Texte in Meldungen

### Edge Cases Status
- [x] Lange Nachricht, viele Nachrichten, Nachricht ohne Text, Ticket ohne Nachrichten, Umlaute
- [x] Andere Kanäle (Notiz, Amazon) mit Kanal-Angabe
- [x] Nutzer ohne Namen kann laden, Hinweis aus PROJ-5 bleibt
- [~] Systemnachrichten als „automatisch": nur Absender „System" wird so markiert; die n8n-Versandinfo kommt als Agent-Nachricht und erscheint als „Von uns" (kein Fehler, nur Hinweis)
- [~] Doppeltes Absenden: Sperre in der Komponente, nur am Code geprüft
- [ ] Offene Frage: maximale Länge der Ticketnummern (vorläufig 1–20 Ziffern)

### Security Audit Results
- [x] Ausgabe der echten Tickets ohne `<script>`, Event-Handler, `javascript:`, Stile, Bilder, Frames
- [x] Klickbare Links nur zu Zammad selbst und `sellercentral.amazon.*` (https); Täuschungsadressen werden entfernt
- [x] Token nie auf der Seite, nur serverseitig; Zugriff nur lesend (`ticket.agent`, Gruppen „lesen")
- [x] Eingabe nur Ziffern; Pfad-Varianten (`/tickets/abc`, `../`) ergeben 404
- [x] Fehlerseiten ohne technische Details; Log ohne Inhalte
- [x] Kein Formular mit Schreibwirkung (GET); CSRF für die Namensauswahl unverändert aktiv
- Hinweis: Ohne Login kann jeder im internen Netz jedes Ticket der lesbaren Gruppen ansehen (bewusst, PRD; Login mit PROJ-15)

### Regression
- [x] PROJ-1 (Startseite: Platzhaltertext durch Eingabe ersetzt, Test angepasst), PROJ-5, PROJ-24, PROJ-25: grün
- [x] About-Seite: Schritt „Ticket laden" ist nutzbar, Hinweis „in Arbeit" entfernt, Beschreibung aktualisiert, Test angepasst
- [x] Gesamte Suite grün

### Bugs Found

#### BUG-1: Tests schreiben in das Log der lokalen App
- **Severity:** Low
- **Steps to Reproduce:**
  1. `./vendor/bin/sail artisan test`
  2. `storage/logs/laravel.log` öffnen
  3. Expected: Testläufe hinterlassen keine Einträge im App-Log
  4. Actual: Einträge `testing.WARNING: Zammad request failed …` (nur Nummer und Fehlerart, keine Inhalte)
- **Priority:** Nice to have (Log-Kanal für Tests z. B. auf `null` stellen)

### In diesem Zusammenhang relevante offene Bugs anderer Features
- PROJ-1 BUG-3: Lade-Overlay sperrt die Tastatur nicht (PROJ-6 verhindert doppeltes Laden selbst)
- PROJ-1 BUG-4 / PROJ-24 BUG-5: Fehlerseiten 405 und 419 englisch („Method Not Allowed" bestätigt)
- PROJ-1 BUG-2 (Rest): Kontrast `slate-400`

### Summary
- **Acceptance Criteria:** alle bestanden (zusammengeführtes Ticket nur mit Attrappe)
- **Bugs Found:** 1 total (0 critical, 0 high, 0 medium, 1 low)
- **Security:** Pass
- **Production Ready:** YES
- **Recommendation:** Freigeben; die drei PROJ-1-Altlasten vor dem Produktivstart beheben

## Deployment
_To be added by /deploy_
