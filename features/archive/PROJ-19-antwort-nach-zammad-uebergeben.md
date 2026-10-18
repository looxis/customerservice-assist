# PROJ-19: Antwort nach Zammad übergeben

## Status: Approved
**Created:** 2026-10-02
**Last Updated:** 2026-10-18 (QA)

## Dependencies
- Requires: PROJ-5 (Nutzerauswahl), PROJ-6 (Ticket und Verlauf aus Zammad), PROJ-9/10/11 (Vorschlag, Ergebnisansicht, Protokoll), PROJ-27 (Einstellungsseite für die Signatur-Vorlage).
- Berührt: PROJ-12 (Bewertung), PROJ-14 (Analysen-Übersicht) und PROJ-35 (Ticket-Übersicht): Senden wird wie Kopieren erfasst; PROJ-32 (Testmodus: dort kein Versand), PROJ-39 (Fortsetzung nach Klärung), PROJ-29 (Ticketbearbeitung; PROJ-19 ist der erste, kleine Schritt dorthin).
- Nicht mehr abhängig von PROJ-15 (Benutzerverwaltung). Entscheidung 2026-10-18: Die App bekommt kein eigenes Login-/Passwortsystem; kommt eine Anmeldung, dann per SSO (Details später). Der Versand läuft bis dahin über Zugriffstokens in der Konfiguration.

## Ausgangslage
Heute klickt der Mitarbeiter unter dem Antwortentwurf auf „Kopieren“, wechselt zu Zammad, fügt den Text ein, ergänzt die Signatur und sendet dort. Das kostet Zeit und ist fehleranfällig. Der Mitarbeiter soll die Antwort aus der App heraus senden können; sie erscheint in Zammad im Ticketverlauf, gesendet im Namen des Mitarbeiters.

## User Stories
- Als Mitarbeiter im Kundenservice möchte ich den geprüften und bei Bedarf angepassten Antwortentwurf mit einem Klick aus der App senden, damit ich nicht zu Zammad wechseln und den Text kopieren muss.
- Als Mitarbeiter möchte ich vor dem Senden Empfänger, Betreff, Text und Signatur sehen und Betreff, Text und Signatur ändern können, damit nichts ungeprüft rausgeht.
- Als Mitarbeiter möchte ich beim Senden entscheiden, ob das Ticket geschlossen wird oder eine Erinnerung in 1, 3 oder 7 Tagen bekommt, damit ich danach nichts mehr in Zammad erledigen muss.
- Als Mitarbeiter ohne hinterlegtes Zugriffstoken möchte ich weiter „Kopieren“ nutzen und einen Hinweis sehen, wie ich den Direktversand bekomme.
- Als Admin möchte ich die Signatur-Vorlage mit dem Platzhalter {Name} auf der Einstellungsseite ändern, damit alle dieselbe Signatur nutzen.
- Als Mitarbeiter möchte ich nachvollziehen können, wer eine Antwort wann gesendet hat, und vor einem Versand gewarnt werden, wenn sich das Ticket seit dem Vorschlag verändert hat.

## Out of Scope
- Eigenes Login-/Passwortsystem und Benutzerverwaltung (PROJ-15, künftig SSO). Bis dahin kann ein Mitarbeiter durch Wahl eines anderen Namens im Namen eines Kollegen senden; das ist für wenige Tage bewusst akzeptiert und schließt sich mit der Benutzerverwaltung.
- Verwaltung der Tokens in der Oberfläche (Eingabe, Prüfung, Ablauf): Tokens stehen in der `.env`.
- Interne Notizen, Statuswechsel ohne Antwort, Zuständigkeit, Anhänge, CC/BCC, Entwürfe in Zammad speichern (PROJ-29).
- Änderung des Empfängers in der App.
- Signatur in der Sprache des Kunden (siehe Offene Fragen).
- Versand bei Vorschlägen ohne Ticket („Frage stellen“) und im Testmodus.
- Automatisches Senden; es wird nur nach ausdrücklichem Klick auf „Antwort versenden“ gesendet.

## Ablauf
1. Der Vorschlag enthält einen Antwortentwurf. Darunter steht ein Block „Antwort versenden“.
2. Der Block zeigt: **An** (schreibgeschützt), **Betreff** (änderbar, vorbelegt wie die Zammad-Antwort „Re: …“), den **Text** (der Antwortentwurf, änderbar, mit der **Signatur** unten angehängt, ebenfalls änderbar), die Wahl **Danach** (siehe unten) und den Knopf „Antwort versenden“.
3. Unter dem Knopf steht der Hinweis, dass die Antwort über Zammad versendet wird und dort im Ticketverlauf erscheint.
4. Vor dem Senden liest die App das Ticket neu aus Zammad (Prüfung auf neuere Nachrichten). Danach wird die Antwort als Mail-Antwort im Ticket angelegt, und der Status wird gesetzt.

### Empfänger und Betreff
- Die App übernimmt Antwortadresse und Betreff von der letzten Kundennachricht im Ticket, so wie Zammad bei „Antworten“. Das gilt auch für Amazon (Relay-Adresse). Der Empfänger ist nicht änderbar.
- Gibt es keine eindeutige Antwortadresse (z. B. Ticket ohne Kundennachricht, Telefonnotiz), ist der Knopf gesperrt, und die App nennt den Grund.

### Signatur
- Die Vorlage steht auf der Einstellungsseite (PROJ-27, Verlauf und Zurücksetzen wie dort); Standard: „Mit freundlichen Grüßen / {Name} / LOOXIS GmbH“. {Name} ist der volle Name des Mitarbeiters aus der Namensliste.
- Sie wird beim Öffnen des Versandblocks an den Text angehängt (durch eine Leerzeile getrennt) und kann dort wie der Text geändert werden. Die Änderung gilt nur für diesen Versand.
- Enthält der Entwurf schon eine Grußformel am Ende, sieht der Mitarbeiter das und kann sie entfernen (die App erkennt das nicht selbst).

### Status danach
- Zwei Optionen: **Ticket schließen** (vorausgewählt) und **Erinnerung in X Tagen** mit der Auswahl 1, 3 oder 7 Tagen. „Unverändert“ gibt es nicht; der Status wird immer gesetzt.
- Die Erinnerung nutzt den Zammad-Status „pending reminder“ mit dem Datum.

## Zugang und Absender
- Für jeden Mitarbeiter der Namensliste kann ein Zammad-Zugriffstoken in der `.env` stehen (Platzhalter z. B. `ZAMMAD_TOKEN_NELE`, in der Spec und der Konfiguration nur als Name dokumentiert; Werte trägt der Verantwortliche selbst ein). Das Token gehört dem eigenen Zammad-Benutzer des Mitarbeiters und braucht nur das Recht, Tickets zu ändern.
- Die Antwort wird mit dem Token des in der App gewählten Mitarbeiters gesendet und erscheint in Zammad mit dessen Namen. Nicht über den technischen Benutzer `customerserviceassist@looxis.com`.
- Hat der gewählte Mitarbeiter kein Token, fehlt der Knopf „Antwort versenden“; „Kopieren“ bleibt. Es erscheint der Hinweis „Bitte Token hinterlegen, dann ist ein Direktversand möglich“.
- Tokens erscheinen nie in der Oberfläche, im Protokoll, in Fehlermeldungen oder in Logs.
- Die Namensliste erhält für jeden Mitarbeiter Vor- und Nachnamen: Cara Barkhausen, Etienne Renaud, Johannes Renaud, Kerstin Schmeckpeper, Nele Gorka, Thomas Junski. Das Dropdown zeigt weiter nur den Vornamen; die Signatur nutzt den vollen Namen.

## Wann der Knopf angeboten wird
- Angeboten: Vorschlag zu einem echten Zammad-Ticket, mit Antwortentwurf, der gewählte Mitarbeiter hat ein Token.
- Nicht angeboten: zurückgespulte Testlauf-Tickets (PROJ-32), „Frage stellen“ ohne Ticket (PROJ-40), Vorschläge ohne Antwortentwurf (Klärung zuerst, PROJ-39). Nach einer Klärung gilt der Knopf wie bei jedem Vorschlag mit Entwurf.
- Der Entwurf wird in der Sprache gesendet, in der er angezeigt wird (die Sprache des Kunden), nie die deutsche Übersetzung.

## Sicherheit gegen Fehlversand
- **Veralteter Vorschlag:** Gibt es seit der Analyse eine neuere Nachricht im Ticket (Kunde oder Kollege), wird nicht gesendet. Ein gelber Hinweis nennt Absender und Zeit; der Mitarbeiter kann die Nachricht lesen und bewusst „Trotzdem senden“ wählen oder den Vorschlag neu erstellen.
- **Doppelter Versand:** Der Knopf sperrt sich nach dem ersten Klick; je Vorschlag wird nur einmal gesendet (auch bei Neuladen, Doppelklick oder zwei Mitarbeitern).
- **Nach dem Senden:** Statt des Knopfes steht „Gesendet von Nele am 18.10. um 14:32 Uhr“ mit Link zum Ticket in Zammad.
- **Fehler:** Zammad nicht erreichbar, Token ungültig oder abgelaufen, fehlende Rechte: Es wird nichts gesendet, der Text bleibt erhalten, die Meldung nennt den Grund (ohne Tokenwert), „Kopieren“ bleibt möglich. Bleibt unklar, ob die Antwort gesendet wurde (Zeitüberschreitung), wird erst das Ticket auf eine neue Nachricht des Mitarbeiters geprüft und erst dann ein erneuter Versand erlaubt.
- Schlägt nur das Setzen des Status fehl, obwohl die Antwort gesendet ist, meldet die App das ausdrücklich („Antwort gesendet, Status konnte nicht gesetzt werden“) und sendet nicht noch einmal.

## Protokoll und Übersichten
- Je Vorschlag: wer, wann, Betreff, Status danach, Artikel-ID aus Zammad. Der gesendete Text wird nicht ein zweites Mal gespeichert.
- Die bestehende Kopieren-Erfassung (PROJ-12/35) bleibt unverändert.

### Statistik: Senden zählt wie Kopieren
Das Senden wird für die Statistik genauso erfasst wie bisher der Klick auf „Kopieren“:
- **Unverändert oder angepasst:** Beim Senden vergleicht die App den gesendeten Text (ohne die Signatur) serverseitig mit dem Original der KI, wie beim Kopieren: „unverändert“ oder „angepasst“. Eine geänderte Signatur oder ein geänderter Betreff macht den Text nicht zu „angepasst“.
- **Bewertung durch den Mitarbeiter:** Nach dem Senden erscheint dieselbe Frage „Wie brauchbar war der Vorschlag?“ mit den vier Stufen und der Vorbelegung aus der Änderung am Entwurf (PROJ-12). Sie ist auch hier jederzeit nachträglich änderbar.
- **Zählung:** Ein Versand zählt in der Analysen-Übersicht (PROJ-14, Kennzahlen „unverändert“/„angepasst“, Filter) und in der Ticket-Übersicht (PROJ-35) wie ein Kopieren mit demselben Zustand. Dort wird zusätzlich kenntlich, dass die Antwort **gesendet** und nicht nur kopiert wurde (z. B. „Gesendet, unverändert“ / „Gesendet, angepasst“); die Kennzahlen zum Kopier-Stand enthalten gesendete Antworten, damit die Reihe vergleichbar bleibt.
- **Mehrfach:** Hat der Mitarbeiter erst kopiert und dann gesendet, zählt der Versand (der spätere Vorgang überschreibt, wie beim mehrfachen Kopieren). Nur die neueste Analyse eines Tickets zählt.
- Schlägt der Versand fehl, wird nichts erfasst.

## Acceptance Criteria
- [ ] Angenommen ein Vorschlag mit Antwortentwurf zu einem echten Ticket und der gewählte Mitarbeiter hat ein Token, wenn die Ergebnisansicht erscheint, dann steht unter dem Entwurf der Block „Antwort versenden“ mit Empfänger (schreibgeschützt), Betreff, Text mit Signatur, Auswahl „Danach“ und dem Hinweis auf Zammad und den Ticketverlauf.
- [ ] Angenommen der Block ist offen, dann steht im Text unter dem Entwurf die Signatur aus der Einstellung mit dem vollen Namen des Mitarbeiters statt {Name}, und Betreff, Text und Signatur sind änderbar.
- [ ] Angenommen ich klicke auf „Antwort versenden“, wenn keine neuere Nachricht im Ticket ist, dann legt die App die Antwort als E-Mail im Ticket an den Empfänger der letzten Kundennachricht an, im Namen des Mitarbeiters (Token), mit Betreff und Text wie im Formular.
- [ ] Angenommen „Ticket schließen“ ist gewählt (Standard), wenn gesendet wurde, dann ist das Ticket in Zammad geschlossen; bei „Erinnerung in 1/3/7 Tagen“ hat es den Status „pending reminder“ mit Datum in so vielen Tagen.
- [ ] Angenommen der Mitarbeiter hat kein Token, dann gibt es keinen Knopf „Antwort versenden“, „Kopieren“ funktioniert, und ein Hinweis erklärt, dass mit hinterlegtem Token ein Direktversand möglich ist.
- [ ] Angenommen das Ticket ist ein Testlauf (zurückgespult), eine Frage ohne Ticket oder ein Vorschlag ohne Antwortentwurf, dann wird kein Versandblock angeboten.
- [ ] Angenommen seit der Analyse gibt es eine neuere Nachricht im Ticket, wenn ich senden will, dann wird nichts gesendet, ein Hinweis nennt Absender und Zeit, und ich kann „Trotzdem senden“ oder den Vorschlag neu erstellen wählen.
- [ ] Angenommen ich klicke zweimal oder lade die Seite neu oder ein Kollege sendet denselben Vorschlag, dann wird die Antwort nur einmal gesendet.
- [ ] Angenommen gesendet wurde, wenn ich die Ergebnisansicht öffne, dann steht dort „Gesendet von <Name> am <Zeit>“ mit Link zum Ticket; das Protokoll nennt wer, wann, Betreff, Status danach und Artikel-ID.
- [ ] Angenommen Zammad lehnt das Token ab oder ist nicht erreichbar, wenn ich sende, dann steht der Grund da (ohne Tokenwert), der Text bleibt erhalten, nichts wurde gesendet, und „Kopieren“ geht weiter.
- [ ] Angenommen die Antwort ging raus, aber der Status konnte nicht gesetzt werden, dann steht das ausdrücklich da, und die App sendet nicht erneut.
- [ ] Angenommen das Ticket hat keine eindeutige Antwortadresse, dann ist der Knopf gesperrt, und der Grund steht da.
- [ ] Angenommen die Signatur-Vorlage wird als Admin geändert, dann gilt sie ab dem nächsten Öffnen des Blocks für alle; Verlauf, Wiederherstellen und Zurücksetzen funktionieren wie bei PROJ-27.
- [ ] Angenommen ein Token steht in der Konfiguration, dann erscheint sein Wert nirgends (Seite, Protokoll, Log, Fehlermeldung).
- [ ] Angenommen ich sende den Antwortentwurf, wenn der Text (ohne Signatur) vom Original der KI abweicht, dann wird der Versand mit Zeit, Name und „angepasst“ festgehalten, sonst mit „unverändert“; eine geänderte Signatur oder ein geänderter Betreff macht den Text nicht zu „angepasst“.
- [ ] Angenommen gesendet wurde, dann erscheint die Frage „Wie brauchbar war der Vorschlag?“ mit den vier Stufen und der Vorbelegung aus der Änderung am Entwurf, und die Antwort wird wie bisher beim Kopieren gespeichert (nachträglich änderbar).
- [ ] Angenommen es wurde gesendet, wenn ich die Analysen-Übersicht und die Ticket-Übersicht öffne, dann zählt der Versand in den Kennzahlen und Filtern wie ein Kopieren mit demselben Zustand und ist zusätzlich als „gesendet“ kenntlich.
- [ ] Angenommen nur die neueste Analyse eines Tickets zählt, wenn bei einer älteren Analyse gesendet wird, dann wird sie nicht erfasst, und es wird auch nichts erfasst, wenn der Versand scheitert.
- [ ] Angenommen die Namensliste hat jetzt Vor- und Nachnamen, dann zeigt das Dropdown weiter die Vornamen, und bestehende Cookies mit einem Vornamen funktionieren weiter.

## Edge Cases
- Antwort an ein Amazon-Ticket: Empfänger ist die Relay-Adresse aus der letzten Kundennachricht; Amazon-Regeln bleiben bei Zammad.
- Ticket ist schon geschlossen: Die Antwort öffnet es nicht von selbst; der Status wird nach der Wahl „Danach“ gesetzt.
- Der Entwurf ist leer oder nur Signatur: Senden ist gesperrt.
- Sehr langer Text, Sonderzeichen, Zeilenumbrüche, Markup im Text: wird als reiner Text gesendet, nie als HTML gedeutet.
- Das Token gehört einem Zammad-Benutzer ohne Zugriff auf die Gruppe des Tickets: Zammad lehnt ab, die Meldung nennt fehlende Rechte.
- Zwei Mitarbeiter öffnen denselben Vorschlag: Der erste Versand gewinnt, der zweite sieht „bereits gesendet“.
- Der Mitarbeiter wechselt im Browser den Namen: Absender ist dann der neu gewählte Mitarbeiter (bewusste Lücke bis zur Benutzerverwaltung).
- Das Ticket wurde nach der Analyse zusammengeführt oder gelöscht: Zammad lehnt ab; Meldung wie bei Fehlern.
- Der Entwurf wurde nach dem Vorschlag bearbeitet (PROJ-10): Der bearbeitete Text ist der Ausgangstext im Block.

## Technical Requirements
- Security: Tokens nur in der Konfiguration (`config()`), nie in Ausgaben; Anfrage über Form Request; Versand nur per POST mit CSRF; Eingaben (Betreff, Text) als Text; Empfänger kommt nie aus dem Formular, sondern wird serverseitig aus dem Ticket gelesen.
- Zammad: Schreibzugriff nur für das Anlegen der Antwort und das Setzen des Status, jeweils mit dem Token des Mitarbeiters.
- Browser Support: Chrome, Firefox, Safari; Phone-Breite 375 px für den Block.

## Open Questions
- [ ] Soll die Signatur bei fremdsprachigen Antworten (Italienisch, Französisch usw.) eine Fassung in der Sprache des Kunden haben? Vorschlag: zunächst nur Deutsch, später je Sprache.
- [x] Wo steht die Signatur-Vorlage auf der Einstellungsseite? Entschieden im Architekturschritt: neue Gruppe „Signatur für Antworten“ auf der Unterseite „Mail-Anzeige“.
- [x] Wie wird die Antwort in Zammad technisch angelegt? Entschieden im Architekturschritt (siehe Tech Design): ein einziger Aufruf, der das Ticket ändert und die Antwort als E-Mail-Nachricht anlegt. Die Bestätigung an einem Testticket steht als Aufgabe am Beginn der Umsetzung.
- [ ] Welche Rechte braucht das Token mindestens? Erwartung: das Recht „Tickets ändern“ (`ticket.agent`) plus Zugriff auf die Gruppe des Tickets; wird beim ersten Test mit einem echten Token bestätigt und hier eingetragen.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Versand nur nach Klick auf „Antwort versenden“, mit Hinweis auf Zammad und den Ticketverlauf | Ein Mensch gibt jede Kundenantwort frei (PRD); kein automatisches Senden | 2026-10-18 |
| Absender ist der eigene Zammad-Benutzer des Mitarbeiters (Token je Mitarbeiter), nicht der technische Benutzer | Die Antwort erscheint im Namen des Mitarbeiters; der technische Benutzer braucht keine Admin-Rechte | 2026-10-18 |
| Tokens in der `.env` je Mitarbeiter; kein Browser-Cookie, keine Tokenverwaltung in der Oberfläche | Für wenige Tage bis zur Benutzerverwaltung; die Mitarbeiter sind vertrauenswürdig, Wahl eines fremden Namens ist bewusst akzeptiert | 2026-10-18 |
| Kein eigenes Login/Passwort, später SSO | Entscheidung des Auftraggebers; PROJ-19 ist unabhängig von PROJ-15 | 2026-10-18 |
| Ohne Token bleibt „Kopieren“ mit Hinweis auf den Direktversand | Kein Mitarbeiter wird ausgesperrt; sanfte Einführung | 2026-10-18 |
| Empfänger aus der letzten Kundennachricht, nicht änderbar; Betreff änderbar; keine CC/BCC/Anhänge | Weniger Fehlversand, Amazon-Relay bleibt korrekt; Sonderfälle weiter in Zammad | 2026-10-18 |
| Signatur aus Einstellung mit {Name}, unten an den Entwurf angehängt und änderbar | Einheitlich, vor dem Senden prüfbar; Zammad fügt über die API keine Signatur an | 2026-10-18 |
| Namensliste mit Vor- und Nachnamen; Dropdown zeigt Vornamen | Die Signatur braucht den vollen Namen | 2026-10-18 |
| Status danach: „Ticket schließen“ (Standard) oder „Erinnerung in 1/3/7 Tagen“; kein „unverändert“ | Üblicher Ablauf des Teams: Antwort und Schließen in einem Schritt, selten offen lassen | 2026-10-18 |
| Sperre bei neuerer Nachricht im Ticket mit „Trotzdem senden“ | Verhindert veraltete Antworten, lässt dem Mitarbeiter die Entscheidung | 2026-10-18 |
| Einmal-Versand je Vorschlag und Sperre des Knopfes | Schutz vor Doppelklick und Kollegenüberschneidung | 2026-10-18 |
| Senden wird für die Statistik wie Kopieren erfasst (unverändert/angepasst, Bewertung durch den Mitarbeiter) und zusätzlich als „gesendet“ gekennzeichnet | Die Auswertung der Vorschlagsqualität soll weitergehen; Vergleich auf den Text ohne Signatur und Betreff, damit Standardänderungen nicht zählen | 2026-10-18 |
| Kein Versand im Testmodus, bei Fragen ohne Ticket und ohne Antwortentwurf | Verhindert Versand an echte Kunden aus Testläufen und ohne Ticket | 2026-10-18 |
| Antwort in der Sprache des Kunden, nie die Übersetzung | Wie der Entwurf angezeigt wird | 2026-10-18 |

### Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Antwort und Status in einem Zammad-Aufruf (Ticket ändern mit angehängter Nachricht) | Kein Zwischenzustand „gesendet, Status fehlt“; ein Fehlerfall weniger | 2026-10-18 |
| Nachricht als E-Mail, reiner Text, auf die letzte Kundennachricht bezogen | Zammad sendet über den Kanal des Tickets (auch Amazon); Markup wird nie gedeutet | 2026-10-18 |
| Empfänger und Bezug nur serverseitig aus dem Ticket | Das Formular kann den Empfänger nicht manipulieren | 2026-10-18 |
| Eigener Versandbaustein mit dem Token des Mitarbeiters; lesender Zugang bleibt ohne Schreibrechte | Schreibzugriff klar abgegrenzt, Token nur beim Versand verwendet | 2026-10-18 |
| Namensliste mit vollem Namen und Tokenschlüssel in der Konfiguration, Token nur in der `.env` | Entscheidung des Auftraggebers; Übergang bis zur Benutzerverwaltung | 2026-10-18 |
| Neueste bekannte Nachricht am Vorschlag festhalten und beim Senden vergleichen | Erkennt veraltete Vorschläge ohne zusätzliche Nutzereingabe | 2026-10-18 |
| Atomarer „Versand gestartet“-Merker; unklarer Ausgang wird vor neuem Versuch am Ticket geprüft | Schutz vor Doppelversand bei Doppelklick, Neuladen, zwei Mitarbeitern und Zeitüberschreitung | 2026-10-18 |
| Versand folgt denselben Sperren wie Kopieren (neueste Analyse, Regelprüfung) und serverseitig dem Testmodus | Keine Umgehung über die Oberfläche | 2026-10-18 |
| Statistik über dieselben Felder wie Kopieren plus „gesendet am“ | Kennzahlen bleiben vergleichbar, Versand ist trotzdem erkennbar | 2026-10-18 |
| Signatur als Einstellungswert auf „Mail-Anzeige“, nur Platzhalter {Name} | Wiederverwendung von Verlauf, Zurücksetzen und Berechtigung aus PROJ-27 | 2026-10-18 |

---

## Tech Design (Solution Architect)

### Backend nötig?
Ja. Die App schreibt erstmals nach Zammad, und der Versand wird gespeichert (Protokoll, Statistik, Schutz vor Doppelversand). Neue Pakete sind nicht nötig. Es gibt zwei Zammad-Zugänge: den lesenden technischen Benutzer wie bisher und, nur beim Versand, das Token des jeweiligen Mitarbeiters.

### A) Bausteine der Oberfläche
```
Ergebnisansicht (Vorschlag mit Antwortentwurf)
+-- Antwortentwurf (wie bisher) mit „Kopieren“
+-- Block „Antwort versenden“ (neu, nur wenn alle Bedingungen erfüllt sind)
|   +-- An (schreibgeschützt)
|   +-- Betreff (änderbar)
|   +-- Text mit angehängter Signatur (änderbar, ein Feld)
|   +-- Danach: (•) Ticket schließen  ( ) Erinnerung in [1|3|7] Tagen
|   +-- Knopf „Antwort versenden“ (sperrt sich nach dem Klick)
|   +-- Hinweis: wird über Zammad versendet und erscheint im Ticketverlauf
|   +-- Warnung (gelb, nur bei neuerer Nachricht): Absender, Zeit, „Trotzdem senden“ / „Vorschlag neu erstellen“
|   +-- Fehlerhinweis (rot, mit Grund; Text bleibt erhalten)
+-- Alternativ ohne Token: Hinweis „Bitte Token hinterlegen, dann ist ein Direktversand möglich“
+-- Nach dem Senden: „Gesendet von Nele am 18.10. um 14:32 Uhr“ mit Link zum Ticket in Zammad
+-- Frage „Wie brauchbar war der Vorschlag?“ (wie nach dem Kopieren)

Einstellungen > Mail-Anzeige
+-- Gruppe „Signatur für Antworten“ (mehrzeiliger Text mit {Name}, Verlauf, Zurücksetzen)

Analysen-Übersicht und Ticket-Übersicht
+-- Stand „Gesendet, unverändert“ / „Gesendet, angepasst“ neben den Kopier-Ständen
```
Wiederverwendet werden die Feedback-Frage und der Entwurfseditor (PROJ-10/12), die Hinweis- und Knopf-Bausteine sowie die Einstellungsgruppen (PROJ-27). Neu ist nur der Versandblock.

### B) Datenmodell (in Alltagssprache)
```
Am Vorschlag (Analyse) kommen dazu:
- gesendet am, gesendet von (Name in der App)
- Zammad-Benutzer, dessen Token verwendet wurde
- verwendeter Betreff, gewählter Status danach (schließen / Erinnerung + Tage)
- Nummer der in Zammad angelegten Nachricht (Artikel-ID)
- „Versand gestartet am“ (Merker gegen Doppelversand und für unklare Ausgänge)
- ID der neuesten Nachricht, die der Vorschlag kannte (für die Prüfung auf neuere Nachrichten)

Die vorhandenen Felder „kopiert am / von / unverändert“ werden beim Senden ebenfalls gesetzt,
damit Kennzahlen und Filter weiterlaufen. „Gesendet am“ unterscheidet Versand von Kopieren.

Namensliste (Konfiguration): je Mitarbeiter Vorname (Auswahl), vollständiger Name (Signatur)
und das Zammad-Token (aus der .env, nur dort).

Einstellung „Signatur für Antworten“: ein Textwert in den bestehenden Einstellungen
(Version, Verlauf, Standard in der App).
```
Der gesendete Text wird nicht erneut gespeichert. Das Token wird nie gespeichert, nur in der `.env` gehalten und beim Senden gelesen.

### C) Technische Entscheidungen (begründet)
- **Ein Zammad-Aufruf für Antwort und Status:** Statt erst die Nachricht anzulegen und dann den Status zu setzen, wird das Ticket mit angehängter Nachricht in einem einzigen Aufruf geändert (Zammad unterstützt eine Nachricht im Ticket-Update). Dadurch gibt es kaum den Fall „Antwort gesendet, Status fehlt“. Bestätigung an einem Testticket als erster Schritt der Umsetzung.
- **Nachricht als E-Mail, reiner Text, nicht intern:** Die Art „E-Mail“ lässt Zammad über den Kanal des Tickets senden (auch Amazon-Relay). Der Text geht als Plain-Text; Markup im Text wird nie gedeutet. Um die Antwort dem Verlauf zuzuordnen, wird die Nachricht auf die letzte Kundennachricht bezogen.
- **Empfänger nur serverseitig:** Die Antwortadresse (Antwort-an, sonst Absender) und die Bezugsnachricht liest die App beim Senden frisch aus dem Ticket. Sie kommen nie aus dem Formular, ein Manipulationsversuch kann den Empfänger nicht ändern. Die Ticket-Abfrage der App liefert dazu zwei neue Felder an der Kundennachricht.
- **Eigener Versandbaustein mit dem Token des Mitarbeiters:** Ein neuer, kleiner Baustein führt die einzigen Schreibzugriffe aus. Der lesende Zammad-Zugang bleibt unverändert und hat weiterhin keine Schreibrechte. Fehlerarten (kein Token, Token abgelehnt, keine Rechte, nicht erreichbar, Zeitüberschreitung, Ticket nicht gefunden) werden unterschieden und mit Klartext gemeldet, nie mit dem Tokenwert.
- **Token je Mitarbeiter in der Konfiguration:** Die Namensliste wird um vollständigen Namen und Tokenschlüssel erweitert (Umgebungsvariable je Mitarbeiter, nur als Platzhalter dokumentiert). Das Cookie mit dem Vornamen bleibt gültig. Bei fehlendem Token gibt es keinen Versandblock.
- **Prüfung auf neuere Nachrichten:** Beim Analysieren wird die ID der neuesten Nachricht am Vorschlag festgehalten. Beim Senden wird das Ticket neu gelesen und verglichen. Bei Abweichung kommt die Warnung; „Trotzdem senden“ wiederholt die Anfrage mit einer ausdrücklichen Bestätigung.
- **Schutz vor Doppelversand:** Zuerst wird „Versand gestartet“ in einem einzigen atomaren Schritt gesetzt (nur wenn noch nicht gestartet). Wer das nicht schafft, sieht „bereits gesendet bzw. in Arbeit“. Bleibt der Ausgang unklar (Zeitüberschreitung), erlaubt die App einen neuen Versuch erst, nachdem das Ticket auf eine neue Nachricht des Zammad-Benutzers seit dem Start geprüft wurde.
- **Gleiche Sperren wie beim Kopieren:** Der Versand ist nur für die neueste Analyse eines Tickets möglich, nicht bei unvollständiger und unbestätigter Regelprüfung (PROJ-36) und nicht bei zurückgespulten Testlauf-Tickets; Letzteres prüft der Server, nicht nur die Oberfläche.
- **Statistik wiederverwendet:** Beim erfolgreichen Senden werden dieselben Felder wie beim Kopieren gesetzt (Vergleich des Textes ohne Signatur mit dem KI-Original, serverseitig) plus „gesendet am“. Übersicht und Ticket-Übersicht leiten daraus die Stände „Gesendet, unverändert/angepasst“ ab; die Kennzahlen zählen beide Arten.
- **Signatur als Einstellungswert:** Neue Gruppe auf der Unterseite „Mail-Anzeige“ mit Standard „Mit freundlichen Grüßen / {Name} / LOOXIS GmbH“. Erlaubt ist nur der Platzhalter {Name}; andere Platzhalter in geschweiften Klammern werden beim Speichern abgelehnt. Die Signatur wird im Browser beim Öffnen an den Text gehängt; dort ist sie änderbar.
- **Anfrage über Form Request, Endpunkt mit Drosselung:** Der Versand ist ein POST mit CSRF-Schutz und Begrenzung der Anfragen je Minute. Betreff (nicht leer, begrenzte Länge), Text (nicht leer, begrenzte Länge), Status danach (zwei erlaubte Werte, Tage 1/3/7) und die Bestätigung „Trotzdem senden“ werden serverseitig geprüft. Die Antwort kommt als JSON, die Oberfläche (Alpine) zeigt Erfolg, Warnung oder Fehler im Block.
- **Keine Tokens in Logs und Ausgaben:** Im Log stehen nur Statuscodes und Fehlerarten.
- **Tests:** Zammad wird in den Tests vollständig simuliert (keine Strays). Die Tests decken Empfänger aus dem Ticket, Betreff und Text, beide Statusarten, Token fehlt/abgelehnt/keine Rechte/Zeitüberschreitung, neuere Nachricht, Doppelversand und Parallelversand, Testmodus, Frage ohne Ticket, ältere Analyse, Statistik und Übersichten, Signatur-Einstellung mit Platzhalterprüfung und die Namensliste ab. Ein Test an einem echten Testticket in Zammad kommt erst nach deiner Freigabe.

### D) Abhängigkeiten
Keine neuen Pakete.

## Implementation Notes (2026-10-18)
- **Neu:** `ZammadReplySender` (einziger Schreibzugriff: `users/me` zur Tokenprüfung, dann ein `PUT tickets/{id}` mit Status und angehängter E-Mail-Nachricht, danach Lesen der neuen Nachricht für die Artikel-ID), `ReplyProblem`/`ReplyException`/`OutgoingReply`/`SentReply`, `ReplyTarget` (Antwortadresse, Betreff, Bezugsnachricht und „neuere Nachrichten“ aus dem Ticket), `ReplyDelivery` (Angebot für die Oberfläche, Signatur mit vollem Namen, Erinnerungsdatum 8 Uhr), `SendReplyRequest`, `AnalysisController::sendReply` mit Route `tickets.analysis.send` (POST, Drosselung 10 je Minute), Migration mit `reply_send_started_at`, `reply_sent_at`, `reply_sent_by`, `reply_sent_zammad_user`, `reply_sent_subject`, `reply_sent_state`, `reply_sent_article_id`, Einstellungsart `SIGNATURE` und Gruppe `reply_signature` auf „Mail-Anzeige“ (nur der Platzhalter `{Name}`).
- **Namensliste:** `config/staff.php` hat `full_names` und `zammad_tokens` (Umgebungsvariablen `ZAMMAD_TOKEN_CARA`, `_ETIENNE`, `_JOHANNES`, `_KERSTIN`, `_NELE`, `_THOMAS`; Werte trägst du selbst in der `.env` ein). Das Dropdown zeigt weiter die Vornamen, Cookies bleiben gültig.
- **Oberfläche:** Der Block „Antwort versenden“ steht im Antwort-Editor (`reply-editor.blade.php`) unter den Knöpfen: An (schreibgeschützt), Betreff, Signatur (änderbar), Vorschau, Danach (Ticket schließen / Erinnerung in 1, 3 oder 7 Tagen), Warnung bei neuerer Nachricht mit „Trotzdem senden“, Fehlerhinweis, Hinweis auf Zammad. **Abweichung von der Spec:** Der Text wird nicht in einem zweiten Feld bearbeitet, sondern im Antwortentwurf darüber; der Block zeigt ihn mit der Signatur in der Vorschau. So gibt es genau eine Stelle für den Text, und die Signatur bleibt vom Textvergleich getrennt. Ohne Token steht nur der Hinweis „Bitte Token hinterlegen, dann ist ein Direktversand möglich“.
- **Prüfung auf neuere Nachrichten:** Beim Analysieren wird jetzt `thread.newest_article_id` (neueste öffentliche Nachricht) gespeichert; ältere Vorschläge fallen auf `last_article_id` zurück. So löst eine Antwort, die ein Kollege nach der letzten Kundennachricht gegeben hat, die Warnung nur aus, wenn sie nach dem Vorschlag kam.
- **Doppelversand:** `claimReplySend` setzt `reply_send_started_at` atomar. Läuft der Merker länger als 90 Sekunden ohne Ergebnis (unklarer Ausgang), prüft die App vor einem neuen Versuch, ob der Zammad-Benutzer seitdem eine öffentliche Antwort angelegt hat (`sentSince`): ja → als gesendet vermerkt, nein → Merker gelöst und Versand erlaubt.
- **Statistik:** Das Senden setzt dieselben Felder wie das Kopieren (`reply_copied_*`, Vergleich ohne Signatur mit dem KI-Original) plus `reply_sent_*`. Die Analysen-Übersicht zeigt den Stand „Gesendet (unverändert/angepasst)“ mit eigenem Filter; die Filter „Kopiert …“ zeigen nur nicht gesendete Antworten; die Kennzahlen zählen beide. Die Ticket-Übersicht zeigt dieselben Stände.
- **Tests:** `PROJ-19-SendReplyTest` (27 Tests: Angebot, Empfänger und Betreff, Senden mit Token, Status, Erinnerung, Prüfung der Eingaben, Markup, neuere Nachricht, Doppelversand, laufender Versand, Token abgelehnt, fehlende Rechte, unklarer Ausgang mit und ohne Antwort in Zammad, Token nirgends sichtbar, Statistik, Feedback-Frage, Signatur und Namensliste). Gesamtsuite grün.
- **Offen für `/qa`:** Der echte Test gegen Zammad steht noch aus: ein Ein-Aufruf-Versand (`PUT tickets/{id}` mit angehängter Nachricht und Status) an einem Testticket mit einem echten Token, einmal mit „Ticket schließen“ und einmal mit „Erinnerung“, sowie ein Amazon-Ticket (Relay-Adresse). Erst danach ist bestätigt, dass Zammad die Nachricht als E-Mail im Verlauf zeigt und dass `ticket.agent` als Tokenrecht genügt. Browser-Sichttest des Blocks (Phone-Breite 375 px).

## QA Test Results (2026-10-18)
**Ergebnis: bereit (keine Critical-/High-Fehler). Die zwei gefundenen Fehler (B1, B2) sind behoben.**

**Akzeptanzkriterien: 19 von 19 bestanden** (Tests `PROJ-19-SendReplyTest`, 32 Tests, dazu der echte Versand an einem Zammad-Ticket durch den Anwender).
- Versandblock mit Empfänger, Betreff, Signatur mit vollem Namen, Auswahl „Danach“ und Hinweis auf Zammad: bestanden.
- Senden im Namen des Mitarbeiters mit dessen Token, Status „geschlossen“ und „Erinnerung in 1/3/7 Tagen“ (Test, Variante „Erinnerung“ noch nicht am echten Ticket): bestanden.
- Ohne Token nur „Kopieren“ mit Hinweis; kein Block bei Testlauf, Frage ohne Ticket, ohne Entwurf, älterem Vorschlag, fehlender Antwortadresse und unbestätigter Regelprüfung: bestanden.
- Sperre bei neuerer Nachricht mit „Trotzdem senden“, Einmal-Versand, laufender Versand, unklarer Ausgang mit und ohne Antwort in Zammad, Token abgelehnt, fehlende Rechte, Zammad nicht erreichbar: bestanden.
- Statistik wie Kopieren (unverändert/angepasst ohne Signatur und Betreff), Bewertungsfrage nach dem Senden, Stände „Gesendet (…)“ und Filter in Analysen- und Ticket-Übersicht: bestanden.
- Signatur-Vorlage mit nur `{Name}`, Namensliste mit vollen Namen, Cookies mit Vornamen bleiben gültig: bestanden.
- Der Tokenwert erscheint nirgends (Seite, Antworten, Fehlermeldungen): bestanden.

**Echter Versand (Anwender, 2026-10-18):** Ein Ticket wurde aus der App über Zammad gesendet, Antwort und Signatur stimmen. Noch nicht am echten System geprüft: Variante „Erinnerung“, ein Amazon-Ticket (Relay-Adresse), Tokens von Cara, Johannes, Nele, Thomas (noch nicht hinterlegt).

**Sicherheit (Red Team)**
- Anfrage ohne CSRF-Token wird mit 419 abgelehnt (live geprüft). Die Route liegt unter der Namensauswahl, mit Drosselung (11. Versuch in einer Minute: 429, Test).
- Der Empfänger kommt nie aus dem Formular (Test mit untergeschobenem Empfänger), Markup im Text wird als reiner Text gesendet, Ticketnummer und Vorschlag müssen zusammenpassen (404), ein fremdes Ticket kann nicht über einen Vorschlag angesprochen werden.
- Ohne gewählten Namen wird nichts gesendet (409). Bekannte, bewusst akzeptierte Grenze: Wer einen anderen Namen wählt, kann in dessen Namen senden, bis die Benutzerverwaltung kommt.
- Tokens liegen nur in der `.env`, erscheinen nicht in Ausgaben und Logs (Log nur Statuscode).

**Regression:** Gesamtsuite 1691 Tests, 1688 grün, 3 übersprungen (2 bekannte, 1 vorgemerkter Fehler-Test).

**Fehler**
| ID | Schwere | Beschreibung | Schritte |
|----|---------|--------------|----------|
| PROJ-19-B1 (behoben 2026-10-18) | Low–Medium | Zeilenumbrüche im Betreff werden unverändert an Zammad gegeben (mögliche Kopfzeilen-Einschleusung; ob Zammad sie bereinigt, ist nicht geprüft). Der Betreff sollte einzeilig sein. | Im Versandblock einen Betreff mit Zeilenumbruch senden (Test `a line break in the subject …`, als „todo“ vorgemerkt) |
| PROJ-19-B2 (behoben 2026-10-18) | Medium | Die Seite „Über die App“ (`docs/ABOUT.md`) sagt weiter „Sie schickt keine Antwort an Kunden. Das machst immer du, in Zammad.“, im Ablauf „kopierst ihn mit einem Klick nach Zammad“ und im Beispiel „schickst sie in Zammad ab“. Das stimmt mit dem Direktversand nicht mehr. | Seite „Über die App“ öffnen, Abschnitte „Prüfen und anpassen“, „Ein Beispiel“, „Was die App bewusst nicht tut“ |

**Behebung:** B1: Der Betreff darf keine Zeilenumbrüche oder Steuerzeichen enthalten (Prüfung in `SendReplyRequest`, Fehlermeldung an der Stelle, Test mit vier Fällen und einem gültigen Betreff mit Umlauten). B2: `docs/ABOUT.md` beschreibt den Versand aus der App (Ablauf Schritt 7 und 8, Beispiel, „Was die App bewusst nicht tut“: sie sendet nie von selbst, nur nach Klick); der Test der Seite (PROJ-25) erwartet den neuen Satz.

**Nicht durchgeführt:** eigener Browser-Durchgang (Chrome/Firefox/Safari, 375/768/1440 px). Die Oberfläche hat der Anwender am echten Ticket benutzt.

## Deployment
_To be added by /deploy_
