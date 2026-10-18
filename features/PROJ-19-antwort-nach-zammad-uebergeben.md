# PROJ-19: Antwort nach Zammad übergeben

## Status: Planned
**Created:** 2026-10-02
**Last Updated:** 2026-10-18

## Dependencies
- Requires: PROJ-5 (Nutzerauswahl), PROJ-6 (Ticket und Verlauf aus Zammad), PROJ-9/10/11 (Vorschlag, Ergebnisansicht, Protokoll), PROJ-27 (Einstellungsseite für die Signatur-Vorlage).
- Berührt: PROJ-32 (Testmodus: dort kein Versand), PROJ-35 (Ticket-Übersicht: Stand „gesendet“), PROJ-39 (Fortsetzung nach Klärung), PROJ-29 (Ticketbearbeitung; PROJ-19 ist der erste, kleine Schritt dorthin).
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
- Je Vorschlag: wer, wann, Betreff, Status danach, Artikel-ID aus Zammad. Der gesendete Text wird nicht ein zweites Mal gespeichert; die Ticket-Aktivität (PROJ-35) kennt den Stand „über die App gesendet“.
- Die bestehende Kopieren-Erfassung (PROJ-12/35) bleibt unverändert.

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
- [ ] Wo steht die Signatur-Vorlage auf der Einstellungsseite? Vorschlag: neue Gruppe „Signatur für Antworten“ auf der Unterseite „Mail-Anzeige“ (oder eigene Unterseite „Antworten“); im Architekturschritt festlegen.
- [ ] Wie wird die Antwort in Zammad technisch angelegt (Artikeltyp E-Mail, Kopfzeilen für die Zuordnung zum Verlauf, Text als Plain-Text)? Das klärt `/architecture` an der Zammad-Schnittstelle, ohne zu senden; ein echter Test erst nach Freigabe in einem Testticket.
- [ ] Welche Rechte braucht das Token mindestens? Vorschlag: nur Tickets ändern; im Architekturschritt gegen Zammad prüfen.

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
| Kein Versand im Testmodus, bei Fragen ohne Ticket und ohne Antwortentwurf | Verhindert Versand an echte Kunden aus Testläufen und ohne Ticket | 2026-10-18 |
| Antwort in der Sprache des Kunden, nie die Übersetzung | Wie der Entwurf angezeigt wird | 2026-10-18 |

### Technical Decisions
<!-- Added by /architecture -->

---

## Tech Design (Solution Architect)
_To be added by /architecture_

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
