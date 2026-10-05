# PROJ-6: Zammad-Ticket laden

## Status: Planned
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
- [ ] Welcher Zammad-Zugang wird für die App angelegt (eigener Nutzer mit Lesezugriff auf welche Gruppen)? Mit `/architecture` klären.
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

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)
_To be added by /architecture_

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
