# PROJ-64: DHL-Retourenlabel erstellen und versenden

## Status: Approved
**Created:** 2026-10-27
**Last Updated:** 2026-11-16

> Wieder geöffnet am 2026-11-16: Erweiterung um Auslandsretouren aus Frankreich, Österreich, Belgien, den Niederlanden, Luxemburg und Italien (Anlass Ticket 2140387). Der Stand für Deutschland ist gebaut und freigegeben (QA vom 2026-10-27, siehe unten). Neue und geänderte Punkte sind mit „(2026-11-16)“ gekennzeichnet.

## Dependencies
- Requires: PROJ-5 (Nutzerauswahl) – Name des Mitarbeiters für Protokoll und Versand
- Requires: PROJ-6 (Zammad-Ticket laden) – Ticketnummer, Kundenmail, Ticketsuche
- Requires: PROJ-7 (Bestellung aus EOCS laden) – Lieferadresse und Bestellnummer
- Requires: PROJ-9 / PROJ-10 (Fallanalyse, Ergebnisansicht) – Antwortentwurf, Formular, „Interne Abläufe“
- Requires: PROJ-19 (Antwort nach Zammad übergeben) – Versand im Namen des Mitarbeiters, Sperre gegen Doppelsenden
- Requires: PROJ-61 (Anhänge an die Antwort) – das Label-PDF reist als Anhang
- Folgefunktion: PROJ-65 (Retouren-Tracking und Zählung, Roadmap)

## Hintergrund
Manche Reklamationen lösen sich nur, wenn die Ware an uns zurückgeht. Dafür bekommt der Kunde ein vorfrankiertes DHL-Retourenlabel. Heute erstellt der Mitarbeiter es von Hand im Geschäftskundenportal und tippt dort Adresse und Referenzen ab. Die App soll das Label über die offizielle DHL-Schnittstelle (Parcel DE Returns, Produkt „DHL Retoure Online“) erzeugen. Die Daten kommen aus der Bestellung, der Mitarbeiter prüft die Adresse nur. Zwei Einstiege: eine eigene Seite „DHL Retoure“ und das Vorschlagsformular mit dem Antwortvorschlag.

**Erweiterung Auslandsretouren (2026-11-16):** Bisher erzeugt die App nur Label für Absender in Deutschland. Ticket 2140387 (französischer Kunde, falsche Ware erhalten) braucht ein Label aus Frankreich. Der DHL-Vertrag hat für Deutschland, Frankreich, Österreich, Belgien, die Niederlande, Luxemburg und Italien je einen eigenen Retourenempfänger (lesend an der produktiven Schnittstelle geprüft am 2026-11-16); alle führen zur LOOXIS GmbH in Minden. Die App soll Label aus diesen sieben Ländern erzeugen.

## User Stories
- Als **Mitarbeiter im Kundenservice** will ich zu einer Ticket- oder Bestellnummer ein Retourenlabel erzeugen, ohne die Adresse abzutippen, damit ich nicht mehr ins Geschäftskundenportal wechseln muss.
- Als **Mitarbeiter** will ich die Lieferadresse vor dem Erzeugen sehen und korrigieren können, damit kein Label mit falscher Anschrift entsteht.
- Als **Mitarbeiter** will ich das Label herunterladen können, damit ich es auch außerhalb der App verwenden kann.
- Als **Mitarbeiter** will ich das Label mit einem Anleitungstext per Klick als Antwort auf ein Ticket oder als neues E-Mail-Ticket an den Kunden senden, damit der Kunde sofort alles bekommt.
- Als **Mitarbeiter** will ich schon im Vorschlagsformular angeben, dass ein Retourenlabel beiliegt, damit der Antwortvorschlag das berücksichtigt, die Anleitung enthält und das PDF schon angehängt ist.
- Als **Mitarbeiter** will ich auch für Kunden in Frankreich, Österreich, Belgien, den Niederlanden, Luxemburg und Italien ein Retourenlabel erzeugen, damit ich dafür nicht ins Geschäftskundenportal wechseln muss. (2026-11-16)
- Als **Kunde im Ausland** will ich die Schritte zur Rücksendung in meiner Sprache und mit einem für mein Land zutreffenden Abgabeort lesen, damit ich das Paket richtig aufgebe. (2026-11-16)
- Als **Aushilfe** will ich bei Amazon-Bestellungen (ohne Adresse in EOCS) einen klaren Hinweis, dass ich die Adresse selbst eintragen muss, damit ich nicht raten muss.

## Out of Scope
- Verfolgen der Rücksendung über die DHL-Schnittstelle, Zählung der Retouren und Auswertung (PROJ-65).
- Abholung der Ware beim Kunden (nur Einlieferung durch den Kunden in Filiale oder Paketshop).
- Retouren aus Ländern außerhalb der sieben unterstützten (Deutschland, Frankreich, Österreich, Belgien, Niederlande, Luxemburg, Italien), insbesondere aus Nicht-EU-Ländern wie der Schweiz oder Großbritannien, und Zolldokumente. (2026-11-16, vorher: nur Deutschland)
- Nennung des Postpartners je Land in der Kundenanleitung (der Abgabeort ist für das Ausland neutral formuliert). (2026-11-16)
- Übersetzung der Kundenschritte durch die KI (es gibt feste, freigegebene Fassungen). (2026-11-16)
- Mailversand durch DHL: Die E-Mail-Adresse des Kunden wird DHL nie übergeben; das Label geht nur mit unserer Mail raus.
- Zusammenführen von Anleitungsseite und Label in einem PDF (kein PDF-Paket): Das Original-PDF wird unverändert angehängt, die Anleitung steht im Mailtext.
- Automatische Erkennung, dass ein Label nötig sein könnte, und automatische Labelerzeugung bei Ticket-Eingang (PROJ-20).
- Erstattung, Gutschrift oder Ersatzauftrag zur Retoure (Non-Goal laut PRD).
- Rücknahme oder Stornierung eines erzeugten Labels (die Schnittstelle bietet das nicht).
- Wahl des Retourenempfängers in der Oberfläche: Die Empfängeradresse ist fest (Konfiguration).
- Label-PDF, Adresse oder Sendungsdaten an das Sprachmodell.

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Seite „DHL Retoure“ – Suche
- [ ] Angenommen die Seitenleiste, wenn ein Mitarbeiter sie öffnet, dann gibt es den Eintrag „DHL Retoure“, der die Seite öffnet.
- [ ] Angenommen die Seite ist geöffnet, wenn der Mitarbeiter eine Ticketnummer eingibt, dann werden die zum Ticket gehörenden Bestellungen aus EOCS geladen (wie in der Ticketansicht).
- [ ] Angenommen die Seite ist geöffnet, wenn der Mitarbeiter eine Bestellnummer eingibt, dann wird die Bestellung direkt aus EOCS geladen.
- [ ] Angenommen mehrere Bestellungen wurden gefunden, wenn die Ergebnisse erscheinen, dann wählt der Mitarbeiter eine Bestellung aus, bevor ein Label möglich ist.
- [ ] Angenommen keine Bestellung wurde gefunden oder EOCS ist nicht erreichbar, wenn die Suche endet, dann erscheint eine verständliche Meldung und die Eingabe bleibt erhalten.
- [ ] Angenommen kein Name ist gewählt, wenn eine Aktion der Seite ausgelöst wird, dann wird wie bei den übrigen Seiten zur Namenswahl aufgefordert.

### Adresse prüfen und Label erzeugen
- [ ] Angenommen eine Bestellung mit Lieferadresse in einem der sieben unterstützten Länder, wenn sie ausgewählt ist, dann sind Name, Straße, Hausnummer, PLZ, Ort und Land im Formular vorbefüllt und änderbar. (2026-11-16, vorher: nur Deutschland)
- [ ] Angenommen das Formular, wenn die feste Empfängeradresse (LOOXIS) angezeigt wird, dann kann der Mitarbeiter sie zur Kontrolle sehen, aber nicht ändern.
- [ ] Angenommen eine Amazon-Bestellung oder eine Bestellung ohne Lieferadresse, wenn sie ausgewählt ist, dann bleiben die Adressfelder leer und ein Hinweis erklärt, dass die Adresse aus dem Ticket von Hand einzutragen ist.
- [ ] Angenommen der Mitarbeiter hat den Haken „Adresse geprüft“ nicht gesetzt, wenn er „Label erzeugen“ wählt, dann wird nichts bei DHL beauftragt und ein Hinweis erscheint.
- [ ] Angenommen ein Pflichtfeld fehlt oder ist zu lang (Name über 50, Straße über 50, Hausnummer über 10, Ort über 40 Zeichen) oder die PLZ passt nicht zum gewählten Land (Deutschland, Frankreich, Italien: fünf Ziffern; Österreich, Belgien, Luxemburg: vier Ziffern; Niederlande: vier Ziffern und zwei Buchstaben), wenn der Mitarbeiter „Label erzeugen“ wählt, dann zeigt das Formular die Fehler am Feld und schneidet nichts still ab.
- [ ] Angenommen das Land ist keines der sieben unterstützten, wenn der Mitarbeiter „Label erzeugen“ wählt, dann wird es abgelehnt mit einem Hinweis, der die unterstützten Länder nennt und für alle anderen auf das Geschäftskundenportal verweist. (2026-11-16, vorher: nur Deutschland)
- [ ] Angenommen der Adressbereich, wenn der Mitarbeiter das Land ändern will (z. B. Amazon-Bestellung ohne Adresse oder falsches Land in EOCS), dann wählt er es aus einer Liste der sieben Länder; ein anderes Land lässt sich nicht eintragen. (2026-11-16)
- [ ] Angenommen ein Absender im Ausland, wenn das Label bestellt wird, dann wird es für den Retourenempfänger dieses Landes bestellt (darüber erkennt DHL das Land des Absenders; ein eigenes Länderfeld hat die Schnittstelle nicht); für Deutschland bleibt der konfigurierte Empfänger. (2026-11-16)
- [ ] Angenommen DHL nennt für das Land keinen oder mehr als einen Retourenempfänger, wenn der Mitarbeiter „Label erzeugen“ wählt, dann wird nichts bestellt und eine Meldung nennt den Grund und verweist auf das Geschäftskundenportal. (2026-11-16)
- [ ] Angenommen ein Label aus dem Ausland wurde erzeugt, wenn die Seite es zeigt (auch unter „Bisherige Labels“), dann ist das Absenderland erkennbar. (2026-11-16)
- [ ] Angenommen ein Land außerhalb Deutschlands ist gewählt, wenn der Adressbereich angezeigt wird, dann steht dort statt „nur für Rücksendungen aus Deutschland“ ein Hinweis, dass es eine Auslandsretoure ist. (2026-11-16)
- [ ] Angenommen gültige Angaben, wenn das Label erzeugt wird, dann stehen im Label die Bestellnummer (bei Reklamationsaufträgen die Original-Bestellnummer) und, sofern bekannt, die Ticketnummer als Referenz; die Länge der gedruckten Referenz bleibt innerhalb der DHL-Grenze.
- [ ] Angenommen DHL liefert das Label, wenn die Erstellung abgeschlossen ist, dann zeigt die Seite Sendungsnummer und „Herunterladen“; das PDF ist unverändert das von DHL.
- [ ] Angenommen das Label wurde erzeugt, wenn der Mitarbeiter es herunterlädt, dann erhält er eine PDF-Datei mit sprechendem Namen (Bestellnummer, Retoure).
- [ ] Angenommen für Ticket und Bestellung existiert schon ein Label, wenn der Mitarbeiter erneut „Label erzeugen“ wählt oder doppelt klickt, dann wird nicht noch einmal bei DHL beauftragt, sondern das vorhandene Label gezeigt; ein weiteres gibt es nur über „Weiteres Label erstellen“.
- [ ] Angenommen zwei Mitarbeiter lösen gleichzeitig dasselbe Label aus, wenn beide Anfragen eintreffen, dann entsteht nur eine DHL-Beauftragung.

### Versand an den Kunden (Seite „DHL Retoure“)
- [ ] Angenommen ein Label liegt vor, wenn der Mitarbeiter „Antwort auf Ticket“ wählt, dann ist die Ticketnummer vorbelegt (falls bekannt), der Anleitungstext ist editierbar und das Label hängt als PDF an.
- [ ] Angenommen eine Antwort auf ein Ticket, wenn der Mitarbeiter ausdrücklich „Senden“ wählt, dann geht die Mail im Namen des Mitarbeiters über Zammad an den Kunden (wie PROJ-19), mit Status „schließen“ oder Erinnerung nach Wahl.
- [ ] Angenommen das Label soll nicht an ein vorhandenes Ticket gehen, wenn der Mitarbeiter ausdrücklich „Neues Ticket erstellen“ wählt, dann muss er die E-Mail-Adresse des Kunden eingeben (Pflicht, gültiges Format).
- [ ] Angenommen „Neues Ticket erstellen“ mit gültiger Adresse, wenn der Mitarbeiter sendet, dann legt die App in Zammad ein neues E-Mail-Ticket in der konfigurierten Gruppe an, mit Anleitungstext und Label-PDF; der Betreff enthält automatisch die Bestellnummer und den Begriff „DHL Retoure“.
- [ ] Angenommen ein neues Ticket wurde angelegt, wenn der Versand gelingt, dann zeigt die Seite die neue Ticketnummer mit Link.
- [ ] Angenommen weder Ticket noch neues Ticket wurde gewählt, wenn der Mitarbeiter sendet, dann wird nicht gesendet und die Wahl wird verlangt.
- [ ] Angenommen der Mitarbeiter hat keinen Zammad-Zugang, wenn er den Versand wählt, dann sieht er den Hinweis wie bei PROJ-19; Herunterladen bleibt möglich.
- [ ] Angenommen der Versand schlägt fehl oder der Ausgang ist unklar, wenn die Meldung erscheint, dann bleiben Label und Text erhalten und ein erneuter Versand wird nicht blind doppelt ausgelöst.
- [ ] Angenommen ein Label wurde schon gesendet, wenn die Seite es zeigt, dann steht dort, wann, durch wen und an welches Ticket.

### Einstieg über den Antwortvorschlag
- [ ] Angenommen das Vorschlagsformular zu einem Ticket mit Bestellung, wenn der Mitarbeiter die Option „DHL-Retourenlabel erzeugen und im Antwortvorschlag berücksichtigen“ wählt, dann erscheint der Adressbereich (vorbefüllt, prüfbar) mit dem Knopf „Label erzeugen“.
- [ ] Angenommen die Option ist gewählt und es gibt noch kein Label, wenn der Mitarbeiter den Vorschlag erstellen will, dann wird er aufgefordert, zuerst das Label zu erzeugen oder die Option abzuwählen.
- [ ] Angenommen ein Label wurde im Formular erzeugt, wenn der Vorschlag erstellt wird, dann erfährt die KI nur, dass ein Retourenlabel beiliegt; Adresse, Sendungsnummer und PDF erhält sie nicht.
- [ ] Angenommen der Vorschlag mit Label ist fertig, wenn der Mitarbeiter den Antwortentwurf sieht, dann steht am Ende des Entwurfs der feste Anleitungstext und das Label-PDF ist bereits als Anhang vorgemerkt.
- [ ] Angenommen der Entwurf mit Label, wenn der Mitarbeiter ihn nach dem Anhängen bearbeitet und über „Antwort versenden“ sendet, dann geht das PDF mit (bestehender Versandweg, Grenzen von PROJ-61 gelten).
- [ ] Angenommen ein Vorschlag ohne Label, wenn der Mitarbeiter bei „Interne Abläufe“ den Knopf „DHL-Retourenlabel erstellen“ wählt, dann öffnet sich derselbe Adressbereich; nach dem Erzeugen wird der Anleitungstext an den Entwurf angehängt und das PDF als Anhang vorgemerkt.
- [ ] Angenommen der Anleitungstext steht schon im Entwurf, wenn das Label erneut angehängt wird, dann wird er nicht doppelt eingefügt.
- [ ] Angenommen eine `return_label_id` gehört nicht zum Ticket, wenn der Vorschlag erstellt wird, dann wird sie abgelehnt.

### Kundenanleitung nach Sprache und Land (2026-11-16)
- [ ] Angenommen ein Label und eine bekannte Sprache des Kunden (Deutsch, Französisch, Italienisch, Niederländisch oder Englisch), wenn die Schritte für den Kunden in den Entwurf oder in die Mail der Seite „DHL Retoure“ eingefügt werden, dann stehen sie in dieser Sprache als fester, hinterlegter Text.
- [ ] Angenommen die Sprache des Kunden ist unbekannt (Seite „DHL Retoure“) oder keine der fünf, wenn die Schritte eingefügt werden, dann richtet sich die Vorbelegung nach dem Absenderland (Deutschland und Österreich Deutsch, Frankreich Französisch, Italien Italienisch, Niederlande Niederländisch, Belgien und Luxemburg Englisch); auf der Seite „DHL Retoure“ kann der Mitarbeiter die Sprache umschalten.
- [ ] Angenommen ein Absender in Deutschland, wenn die Schritte eingefügt werden, dann nennt Schritt 3 wie bisher die DHL-Filiale oder den DHL-Paketshop.
- [ ] Angenommen ein Absender im Ausland, wenn die Schritte eingefügt werden, dann nennt Schritt 3 neutral den auf dem Label genannten Versanddienst (Postfiliale oder Paketshop) und keine DHL-Filiale.
- [ ] Angenommen die Mail der Seite „DHL Retoure“ (Anrede, Einleitung, Betreff bei neuem Ticket), wenn sie vorbereitet wird, dann steht sie in derselben Sprache wie die Schritte und bleibt vor dem Senden änderbar.
- [ ] Angenommen die Schritte stehen schon im Entwurf (in irgendeiner der fünf Sprachen), wenn das Label erneut angehängt wird, dann werden sie nicht doppelt eingefügt.
- [ ] Angenommen ein Vorschlag mit Label, wenn die KI den Entwurf schreibt, dann erfährt sie weiterhin nur, dass ein Label beiliegt; Land und Adresse erhält sie nicht.

### Fehler und Sicherheit
- [ ] Angenommen DHL lehnt die Anfrage ab (z. B. ungültige Adresse), wenn die Antwort eintrifft, dann zeigt die App eine verständliche deutsche Meldung und der Mitarbeiter kann korrigieren und erneut senden.
- [ ] Angenommen die Zugangsdaten für DHL fehlen oder sind falsch, wenn ein Label verlangt wird, dann erscheint ein Hinweis auf die Konfiguration, ohne Geheimnisse zu nennen.
- [ ] Angenommen DHL antwortet nicht rechtzeitig, nachdem die Beauftragung abgeschickt wurde, wenn das Zeitlimit ablief, dann meldet die App „Ausgang unklar“, beauftragt nichts automatisch neu und verweist auf das Geschäftskundenportal.
- [ ] Angenommen DHL liefert ein Label, das kein gültiges PDF ist, wenn die Antwort geprüft wird, dann wird es nicht an den Kunden gegeben, die Sendungsnummer bleibt gespeichert und der Fehler wird gemeldet.
- [ ] Angenommen ein erzeugtes Label, wenn die App es speichert, dann liegt es nicht öffentlich zugänglich; der Download geht nur mit gewähltem Namen und, falls zum Ticket gehörend, mit passender Ticketnummer.
- [ ] Angenommen ein Label, wenn die Aufbewahrungsfrist abläuft, dann wird die Datei automatisch gelöscht; die Angaben zu Sendungsnummer und Versand bleiben für PROJ-65 erhalten.
- [ ] Angenommen irgendein Vorgang, wenn Protokolle geschrieben werden, dann enthalten sie weder Zugangsdaten noch Adressen noch Labeldaten.

## Edge Cases
- Mehrere Pakete: Jedes Paket ist ein eigenes Label über „Weiteres Label erstellen“.
- Adresse mit Zusatz in der Hausnummer (12a), Umlauten oder führender Null in der PLZ: bleibt unverändert und gültig.
- Firmenadresse: Der Firmenname steht im Namensfeld, wenn kein Personenname vorliegt; Fehlt beides, ist es ein Pflichtfehler.
- Ticket ohne Bestellung, Bestellung ohne Ticket: Beides ist zulässig; ohne Ticketnummer steht nur die Bestellnummer im Label und es bleibt nur „Neues Ticket erstellen“ als Versand.
- Ticketnummer, die es in Zammad nicht gibt: verständliche Meldung, nichts wird gesendet.
- Das Ticket hat inzwischen eine neuere Kundennachricht: Der Hinweis und die Bestätigung aus PROJ-19 gelten auch hier.
- Mehrere Mitarbeiter bearbeiten dasselbe Ticket: Die Sperre gegen Doppelsenden verhindert eine doppelte Mail.
- Label ist erzeugt, aber die App bricht vor dem Speichern ab: wird als „Ausgang unklar“ behandelt, nicht neu beauftragt.
- Großer Anhang: Das Label ist deutlich kleiner als die Anhangsgrenze (PROJ-61); Zammad-Probleme mit sehr großen Anhängen (PROJ-62) betreffen es kaum.
- Der Anleitungstext enthält Platzhalter (Sendungsnummer, Bestellnummer): Sie werden beim Einfügen ersetzt.
- Adresse im Ausland, bei der die Hausnummer vor der Straße steht (Frankreich: „12 rue de la Paix“) oder die EOCS nicht getrennt liefert: Der Mitarbeiter trägt Straße und Hausnummer in die beiden Felder ein; fehlt eines, ist es ein Pflichtfehler. (2026-11-16)
- Niederländische PLZ mit oder ohne Leerzeichen („1234 AB“, „1234AB“): beides gültig. (2026-11-16)
- Die Bestellung nennt ein Land außerhalb der sieben (z. B. Schweiz): Die Adresse wird gezeigt, „Label erzeugen“ ist gesperrt, der Hinweis verweist auf das Geschäftskundenportal. (2026-11-16)
- Kunde in Frankreich schreibt Deutsch: Die Schritte sind deutsch (Sprache des Kunden), der Abgabeort ist neutral (Land des Absenders). (2026-11-16)
- Seite „DHL Retoure“ ohne Ticket: Die Sprache des Kunden ist unbekannt, es gilt die Vorbelegung nach Land, und der Mitarbeiter kann die Sprache umschalten; der Text bleibt änderbar. (2026-11-16)
- DHL verlangt für eine Auslandsretoure ein Feld, das die App nicht sendet: Die Ablehnung erscheint wie bisher mit dem Wortlaut von DHL; es entsteht kein Label. (2026-11-16)
- Dieselbe Adresse mit anderem Land gilt als andere Anfrage (kein vorhandenes Label wird wiederverwendet). (2026-11-16)
- Test mit der DHL-Sandbox: Eine Umstellung auf Produktion erfolgt nur über die Konfiguration.

## Technical Requirements (optional)
- Schnittstelle: DHL Parcel DE Returns API 1.1.0 (Pfad `/v1`), Anmeldung per OAuth2 Password Grant; die alte `cig.dhl.de`-Schnittstelle wird nicht benutzt.
- Zugangsdaten, Retourenempfänger-ID und Zammad-Gruppe für neue Tickets stehen in `.env`/Konfiguration, nie in der Datenbank oder im Browser.
- Sicherheit: Alle Aufrufe laufen serverseitig; Throttling auf Erzeugen und Senden; kein POST-Wiederholen bei der Label-Beauftragung.
- Datenschutz: Kundenadressen gehen nur an DHL (für das Label) und Zammad (Mail); nicht an das Sprachmodell, nicht ins Log.
- Aufbewahrung der Label-Dateien: kurze, konfigurierbare Frist (analog Anhangs-Zwischenspeicher).
- Die Umsetzung benötigt kein neues Composer-Paket.

## Open Questions
- [x] Zammad-Gruppe für neue Retouren-Tickets: „allg. Kunden“ (allgemeine Kunden; die zweite Gruppe ist „amazon“). Die persönlichen Zammad-Tokens der Mitarbeiter dürfen dort Tickets anlegen (geklärt 2026-10-27). Der genaue Gruppenname wird beim Bau gegen Zammad geprüft.
- [x] Ist der Vertrag „DHL Retoure Online“ für die API freigeschaltet? → Ja, produktiv im Einsatz; Empfänger gibt es für Deutschland, Frankreich, Österreich, Belgien, die Niederlande, Luxemburg und Italien (geprüft 2026-11-16). Ursprüngliche Frage: Benötigt: Systembenutzer, API-Key und -Secret, produktive Empfänger-ID mit Abrechnungsnummer, Freigabe für „nur Deutschland“. Der Auftraggeber sucht die Angaben im Geschäftskundenportal (Liste im Antwortbericht vom 2026-10-27).
- [ ] Wortlaut des Anleitungstextes (Verpacken, Label aufkleben, Abgabe in Filiale/Paketshop, keine Abholung): Der Entwickler schlägt einen Standardtext vor, der Auftraggeber sucht die DHL-Textvorschläge heraus und gibt frei.
- [ ] Nach dem Erzeugen im Formular: Soll das Ticket nach dem Versand ebenfalls „schließen“ oder „Erinnerung“ als Standard vorschlagen? Vorschlag: wie PROJ-19.
- [x] Die Schritte für den Kunden sind deutsch. Schreibt ein Kunde in einer anderen Sprache, bleibt der Block deutsch. Soll er übersetzt oder mehrsprachig vorgehalten werden? → Feste Fassungen in Deutsch, Französisch, Italienisch, Niederländisch und Englisch, gewählt nach der Sprache des Kunden (2026-11-16)
- [x] Verlangt DHL für Auslandsretouren weitere Pflichtangaben (z. B. Gewicht)? → Nein: Das erste echte Auslandslabel (Frankreich, Ticket 2140387) wurde ohne Gewicht und Zollangaben erzeugt (bestätigt vom Auftraggeber, 2026-11-16). Ursprüngliche Frage: Laut der Schnittstellenbeschreibung (öffentliche SDKs, geprüft 2026-11-16) sind Gewicht, Warenwert und Zollangaben freiwillig und das Land steckt allein im Empfänger; der Beweis ist die erste echte Bestellung (Ticket 2140387). Jede Testbestellung erzeugt ein echtes Label. (2026-11-16)
- [ ] Wortlaut der französischen, italienischen, niederländischen und englischen Fassung: Der Entwickler schlägt sie vor, der Auftraggeber gibt sie frei. (2026-11-16)

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Zwei Einstiege: eigene Seite und Vorschlagsformular | Die Seite deckt Fälle ohne Analyse und ohne Ticket ab, das Formular bringt Label, Anleitung und Antwort in einem Schritt | 2026-10-27 |
| Option im Formular statt automatischer Erkennung | Ein Label kostet beim Einliefern Geld; der Mitarbeiter soll es bewusst auslösen. Zudem ist das Label vor der Analyse bekannt und die KI kann es berücksichtigen | 2026-10-27 |
| Zusätzlicher Knopf bei „Interne Abläufe“ für das nachträgliche Label | Gleiche Mechanik, deckt den Fall ab, dass das Label erst nach dem Vorschlag nötig wird | 2026-10-27 |
| KI erfährt nur „Label liegt bei“, Anleitung als fester Textbaustein | Keine Kundendaten ans Modell, einheitlicher und verlässlicher Anleitungstext | 2026-10-27 |
| Nur Original-PDF als Anhang, Anleitung im Mailtext | Kein neues Paket nötig, Barcode bleibt unverändert | 2026-10-27 |
| DHL bekommt keine E-Mail-Adresse | Das Label geht nur mit unserer Mail an den Kunden; gleiches Verhalten für Amazon und alle anderen | 2026-10-27 |
| Neues Ticket nur auf ausdrücklichen Klick, mit manuell eingegebener Kundenmail | Kein ungewollter Versand an eine falsche Adresse; Betreff automatisch mit Bestellnummer und „DHL Retoure“ | 2026-10-27 |
| Nur Deutschland (abgelöst am 2026-11-16) | Auslandsretouren brauchen eigene Empfänger und Zolldokumente | 2026-10-27 |
| Label auch aus Frankreich, Österreich, Belgien, den Niederlanden, Luxemburg und Italien | Anlass Ticket 2140387; der Vertrag hat für diese Länder bereits Empfänger, alle in der EU, daher keine Zolldokumente. Andere Länder bleiben beim Geschäftskundenportal | 2026-11-16 |
| Kundenschritte als feste Texte in fünf Sprachen (Deutsch, Französisch, Italienisch, Niederländisch, Englisch), Auswahl nach Sprache des Kunden, sonst nach Land | Verlässlicher Wortlaut ohne KI-Aufruf; deckt die Sprachen der sieben Länder ab, Englisch als Rückfall | 2026-11-16 |
| Abgabeort im Ausland neutral formuliert („auf dem Label genannter Versanddienst“), Deutschland unverändert | Stimmt für jedes Land, auch wenn DHL den Partner wechselt; keine Liste zu pflegen | 2026-11-16 |
| Land im Adressbereich als Auswahlliste der sieben Länder | Bei Amazon-Bestellungen und falschem Land in EOCS trägt der Mitarbeiter die Adresse selbst ein; freie Eingabe würde nicht unterstützte Länder zulassen | 2026-11-16 |
| Referenz im Label: Bestellnummer und Ticketnummer | Der Kunde und das Lager können die Sendung zuordnen; die Ticketnummer sucht sich später auch für PROJ-65 | 2026-10-27 |
| Tracking und Zählung als eigene Funktion PROJ-65 | Eigener Nutzen, eigene Schnittstelle (Sendungsverfolgung) | 2026-10-27 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|
| Eigener DHL-Baustein im Stil der bestehenden Anbindungen (EOCS, ProductForge, Zammad) | Gleiche Struktur für Konfiguration, Fehler und Protokoll; leicht zu testen mit gefälschten Antworten | 2026-10-27 |
| Zugangsdaten, Empfänger-ID und Zammad-Gruppe in `.env`/Konfiguration, nicht in den Einstellungen | Geheimnisse gehören nicht in die Datenbank; die Empfänger-ID ändert sich praktisch nie. Bei Bedarf später als Einstellung nachrüstbar | 2026-10-27 |
| DHL-Anmeldetoken wird kurz zwischengespeichert (rund 30 Minuten) | Nicht bei jedem Label neu anmelden; Sperre verhindert parallele Anmeldungen | 2026-10-27 |
| Eigene Tabelle `return_labels` statt Spalten an `analyses` | Ein Label gehört zu Bestellung und Ticket, nicht zwingend zu einer Analyse; Grundlage für PROJ-65 (Tracking, Zählung) | 2026-10-27 |
| Label-PDF auf eigener privater Ablage mit Aufbewahrungsfrist, Sendungsnummer bleibt dauerhaft | DHL liefert das PDF nicht erneut aus; die Sendungsnummer braucht PROJ-65 für das Tracking | 2026-10-27 |
| Label wird zuerst gespeichert, dann erst weiterverarbeitet | Fehler bei Anhang oder Versand führen nie zu einer zweiten (kostenpflichtigen) Beauftragung | 2026-10-27 |
| Sperre je Ticket/Bestellung/Adresse plus Prüfung auf vorhandenes Label | Doppelklick und Parallelzugriff beauftragen nur einmal; DHL bietet keinen eigenen Schutz | 2026-10-27 |
| Label-Beauftragung wird nie automatisch wiederholt | Bei unklarem Ausgang könnte DHL bereits ein Label angelegt haben | 2026-10-27 |
| Neue Zammad-Funktion „Ticket anlegen“ im bestehenden Versandbaustein | Gleicher Zugang (persönlicher Token), gleiche Anhangsbehandlung wie bei Antworten (PROJ-19) | 2026-10-27 |
| Strukturierte Adresse als zusätzliches Feld der Bestellung, bisherige Adresszeilen bleiben | Analyse und Pseudonymisierung bleiben unverändert; die Adresse geht nur an die Retouren-Funktion | 2026-10-27 |
| Einstieg im Formular: Label wird vor der Analyse erzeugt, nur die ID wandert mit | Die KI sieht nur den Fakt „Label liegt bei“; der Anleitungstext wird von der App angehängt, nicht von der KI geschrieben | 2026-10-27 |
| Nutzung des vorhandenen Anhangs-Versandwegs im Antwortvorschlag | Das PDF wird im Browser als Anhang vorgemerkt; der Server-Versand (PROJ-19, PROJ-61) bleibt unverändert | 2026-10-27 |
| Web-Routen mit Blade, kleine Abrufe über JSON | Seite und Formular sind Blade; Erzeugen und Versenden brauchen Rückmeldung ohne Neuladen (wie das Senden in PROJ-19) | 2026-10-27 |
| Keine neuen Pakete | Das PDF wird unverändert angehängt, es muss nur auf Gültigkeit geprüft werden | 2026-10-27 |
| Retourenempfänger für das Ausland wird je Land bei DHL nachgeschlagen (zwischengespeichert), Deutschland bleibt bei `DHL_RECEIVER_ID` | Keine neuen Einträge in der `.env`; je Land gibt es genau einen Empfänger (LOOXIS, Minden). Bei keinem oder mehreren Empfängern wird nichts bestellt. Die Empfänger-ID kommt weiterhin nie aus dem Formular | 2026-11-16 |
| Gemeinsame Länderliste (Kürzel, Name, DHL-Kennung, PLZ-Muster) für Prüfung, Auswahl und DHL-Aufruf | Eine Stelle statt verstreuter Länderregeln; ein weiteres EU-Land ist später ein Eintrag | 2026-11-16 |
| Anfrage an DHL für Deutschland bleibt unverändert, nur das Ausland bekommt den nachgeschlagenen Empfänger seines Landes (die Adresse hat bei DHL kein Länderfeld) | Der freigegebene, täglich genutzte Weg wird nicht angefasst | 2026-11-16 |
| Sprache: im Vorschlag die des Entwurfs, auf der Seite „DHL Retoure“ Vorbelegung nach Land mit Umschalter | Die Seite kennt die Kundensprache nicht zuverlässig; ein Umschalter ist einfacher und sicherer als eine Erkennung | 2026-11-16 |
| Kundentexte als feste Texte im Programm, nicht in den Einstellungen | Fünf Sprachen mal zwei Abgabeorte ändern sich selten; Freigabe einmalig durch den Auftraggeber. Später als Einstellung nachrüstbar | 2026-11-16 |
| Das Absenderland wird am Label gespeichert (zweistelliges Kürzel, keine Adresse) | Anzeige unter „Bisherige Labels“, Wahl des Abgabeorts im Text und Grundlage für PROJ-65 | 2026-11-16 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

### Backend und Datenhaltung
Ja, Backend und Datenbank sind nötig: Die DHL-Zugangsdaten dürfen nie in den Browser, und ein erzeugtes Label muss gespeichert werden, weil DHL es nicht ein zweites Mal ausgibt.

### A) Bausteine der Oberfläche
```
Seitenleiste
+-- Eintrag „DHL Retoure“ (neu)

Seite „DHL Retoure“ (neu)
+-- 1. Suche (Ticketnummer oder Bestellnummer)
|   +-- Auswahl der Bestellung, falls mehrere gefunden
+-- 2. Adressbereich (wiederverwendbare Komponente)
|   +-- Felder Name, Straße, Hausnummer, PLZ, Ort (vorbefüllt, änderbar)
|   +-- Feste Empfängeradresse (nur Anzeige)
|   +-- Hinweis bei Amazon / fehlender Adresse
|   +-- Haken „Adresse geprüft“ und Knopf „Label erzeugen“
|   +-- Ergebnis: Sendungsnummer, „Herunterladen“, „Weiteres Label erstellen“
+-- 3. Versand an den Kunden (optional)
    +-- Wahl: „Antwort auf Ticket“ oder „Neues Ticket erstellen“
    +-- Kundenmail (nur bei neuem Ticket), Betreff (nur Anzeige bei neuem Ticket)
    +-- Anleitungstext (änderbar), Label als Anhang
    +-- Status nach Versand (schließen / Erinnerung), Knopf „Senden“
    +-- Ergebnis: Ticketnummer mit Link

Vorschlagsformular (besteht)
+-- Option „DHL-Retourenlabel erzeugen und im Antwortvorschlag berücksichtigen“ (neu)
    +-- Adressbereich (dieselbe Komponente wie oben)

Antwortvorschlag (besteht)
+-- „Interne Abläufe“: Knopf „DHL-Retourenlabel erstellen“ (neu)
|   +-- Adressbereich (dieselbe Komponente)
+-- Antwortentwurf: Anleitungstext am Ende, Label als vorgemerkter Anhang
```
Die bestehenden Bausteine (Knöpfe, Karten, Hinweise, Einklapp-Bereiche, Kopierfelder) werden wiederverwendet. Neu sind der Adressbereich, die Seite und kleine Erweiterungen in Formular, Abläufen und Antwortfeld.

### B) Datenmodell (in einfachen Worten)
Jedes Retourenlabel speichert:
- eine eindeutige Kennung
- Ticketnummer (kann fehlen, wenn die Seite nur mit Bestellnummer genutzt wird)
- Bestellnummer, EOCS-Auftragsnummer
- Sendungsnummer von DHL (für das spätere Tracking, PROJ-65)
- Stand: wird erzeugt, erzeugt, abgelehnt, Ausgang unklar
- eine Kurzfassung der Anfrage (Fingerprint, ohne Klartext), um doppeltes Beauftragen zu erkennen
- wer es erzeugt hat und wann
- die PDF-Datei (Ablage, Prüfsumme, Löschdatum)
- Versandstand: wann, durch wen, an welches Ticket (oder neues Ticket) gesendet
- eine bereinigte Fehlermeldung, falls etwas schiefging

Nicht gespeichert: Zugangsdaten, Klartext der Adresse, Mailadresse des Kunden.

Das Vorschlagsformular merkt sich zusätzlich, welches Label zu einem Antwortvorschlag gehört, damit die Anleitung nach dem Neuladen noch angezeigt wird.

Gespeichert wird in: MySQL (Label-Angaben) und einer privaten Dateiablage ohne Web-Zugang (PDF). Das PDF wird nach der Aufbewahrungsfrist gelöscht, die Angaben bleiben.

### C) Technische Entscheidungen
- **Eigene DHL-Anbindung** nach dem Vorbild der bestehenden: Konfiguration, verständliche Fehlerarten (nicht konfiguriert, abgelehnt, nicht angemeldet, keine Berechtigung, zu viele Anfragen, nicht erreichbar, Ausgang unklar) und ein Protokoll ohne Kundendaten.
- **Anmeldung bei DHL**: Mit Benutzername und Passwort des Systembenutzers wird ein kurzlebiges Token geholt und für etwa 30 Minuten zwischengespeichert. Ist es abgelaufen, wird einmal neu angemeldet.
- **Schutz vor doppelten Labels**: Vor jedem Aufruf prüft die App, ob es für Ticket, Bestellung und Adresse schon ein Label gibt, und sperrt parallele Aufrufe. Erst danach geht eine Anfrage an DHL. Ein Timeout wird nie blind wiederholt.
- **Reihenfolge nach dem Erzeugen**: Zuerst wird das PDF gesichert und geprüft, dann folgen Anhang und Versand. Schlägt ein späterer Schritt fehl, wird das gespeicherte Label wiederverwendet.
- **Referenzen im Label**: Bestellnummer plus Ticketnummer in der gedruckten Referenz (30 Zeichen), die Ticketnummer zusätzlich in der nicht gedruckten Sendungsreferenz.
- **Versand an Zammad**: Antworten laufen über den bestehenden Weg (PROJ-19). Für „Neues Ticket“ kommt eine neue Funktion hinzu: Ticket in der Gruppe „allg. Kunden“ mit dem Kunden per Mailadresse, E-Mail-Artikel im Namen des Mitarbeiters, Anhang wie bei Antworten.
- **Antwortvorschlag**: Das PDF wird im Browser als Anhang vorgemerkt und geht über den vorhandenen Versandknopf mit. Der Anleitungstext wird von der App eingefügt, nicht von der KI. Der Prompt erhält nur den Hinweis, dass ein Label beiliegt.
- **Berechtigung**: Wie die übrigen Funktionen für alle Mitarbeiter mit gewähltem Namen; kein Admin nötig. Aufrufe haben ein Limit pro Minute.
- **Routen**: Seite und Download als Web-Routen, Suchen, Erzeugen und Senden als kleine JSON-Aufrufe aus der Seite (wie das Senden in PROJ-19).
- **Prüfungen**: Alle Eingaben werden serverseitig mit eigenen Prüfklassen kontrolliert (Länge, Postleitzahl, Land Deutschland, E-Mail-Format, Bestätigungshaken). Die Empfänger-ID kommt nie aus dem Formular.
- **Zusatz**: Ein Prüfbefehl testet die DHL-Verbindung (Anmeldung und Empfängerliste) und ein Aufräumbefehl löscht abgelaufene PDF-Dateien.

### D) Abhängigkeiten
Keine neuen Pakete.

Neue Konfiguration (`.env`): DHL-Adresse für Anmeldung und Labels, API-Key, API-Secret, Benutzername und Passwort des Systembenutzers, Retourenempfänger-ID, Zeitlimit und der Gruppenname für neue Tickets. Die Sandbox ist die Voreinstellung.

### E) Erweiterung Auslandsretouren (2026-11-16)

Es entstehen keine neuen Seiten und keine neuen Abläufe. Die vorhandenen Bausteine bekommen das Land und die Sprache als zusätzliche Angabe.

#### Bausteine der Oberfläche (nur Änderungen)
```
Adressbereich (besteht, auf Seite „DHL Retoure“ und im Vorschlagsformular)
+-- Feld „Land“: Auswahlliste der sieben Länder (bisher nur Anzeige „DE“)
+-- PLZ-Prüfung und Fehlertext richten sich nach dem gewählten Land
+-- Hinweis „Auslandsretoure“ statt „nur aus Deutschland“, sobald das Land nicht Deutschland ist
+-- Sperre mit Verweis aufs Geschäftskundenportal, wenn die Bestellung ein anderes Land nennt

Seite „DHL Retoure“ (besteht)
+-- Empfängeranzeige: zeigt den Empfänger des gewählten Landes
+-- „Bisherige Labels“ und Ergebnis: Absenderland sichtbar
+-- Versand an den Kunden
    +-- Auswahl „Sprache der Mail“ (neu): vorbelegt, umschaltbar; tauscht Text und Betreff,
        solange der Mitarbeiter den Text nicht selbst geändert hat

Antwortvorschlag (besteht)
+-- Schritte für den Kunden am Ende des Entwurfs: Sprache wie der Entwurf, Abgabeort nach Land
```

#### Datenmodell
Jedes Retourenlabel speichert zusätzlich das Absenderland als zweistelliges Kürzel. Bestehende Labels gelten als Deutschland. Die Adresse selbst wird weiterhin nicht gespeichert. Das Land fließt in die Kurzfassung der Anfrage ein, damit dieselbe Anschrift in einem anderen Land als eigene Anfrage gilt.

Die fünf Fassungen der Kundentexte (Schritte, Mailtext, Betreff, je einmal mit Abgabeort Deutschland und Ausland) liegen als feste Texte im Programm, nicht in der Datenbank.

#### Technische Entscheidungen
- **Eine Länderliste an einer Stelle:** Kürzel, Name, DHL-Länderkennung und PLZ-Muster der sieben Länder stehen in einer gemeinsamen Liste. Formularprüfung, Auswahlliste und DHL-Aufruf lesen daraus; ein weiteres EU-Land wäre später ein Eintrag.
- **Empfänger je Land bei DHL nachschlagen:** Für das Ausland fragt die App die Empfängerliste des Landes ab und speichert das Ergebnis eine Stunde zwischen. Genau ein Empfänger wird verwendet, sonst gibt es eine Meldung und keine Bestellung. Deutschland bleibt beim konfigurierten Empfänger; damit ändert sich am laufenden Betrieb nichts.
- **Absenderland in der Bestellung an DHL:** DHL erkennt das Land am Retourenempfänger; die Adresse selbst hat an der Schnittstelle kein Länderfeld (beim Bau geklärt). Für Deutschland bleibt die Anfrage wie heute, damit der freigegebene Weg unverändert ist.
- **Sprache der Kundentexte:** Im Antwortvorschlag gilt die Sprache des Entwurfs (sie ist dort schon bekannt). Auf der Seite „DHL Retoure“ kennt die App die Sprache nicht zuverlässig; sie belegt nach Land vor (Deutschland und Österreich Deutsch, Frankreich Französisch, Italien Italienisch, Niederlande Niederländisch, Belgien und Luxemburg Englisch) und der Mitarbeiter kann umschalten. Das weicht leicht vom Kriterium „sonst Englisch“ ab und trifft die Sprache des Kunden häufiger.
- **Abgabeort nach Land, nicht nach Sprache:** Ob „DHL-Filiale“ oder „auf dem Label genannter Versanddienst“ steht, entscheidet das gespeicherte Absenderland.
- **Kein doppeltes Einfügen:** Die App erkennt die Schritte an der Sendungsnummer im Text, unabhängig von der Sprache.
- **Prüfbefehl:** `dhl:check` zeigt zusätzlich je Land, ob genau ein Empfänger vorhanden ist.
- **Tests:** Die vorhandenen Tests für Deutschland bleiben unverändert grün; neu kommen Fälle je Land (PLZ, Empfänger, Anfrage an DHL) und je Sprache hinzu. DHL wird in den Tests nachgestellt; eine echte Auslandsbestellung macht der Auftraggeber (Ticket 2140387).

#### Abhängigkeiten
Keine neuen Pakete, keine neuen Einträge in der `.env`. Eine Migration (Spalte Absenderland).

## Implementation Notes (Backend/Frontend)
**Gebaut (2026-10-27):**
- DHL-Baustein `app/Dhl/` (Anmeldung per Password Grant mit Token-Cache, Label bestellen, Empfängerliste, Fehlerarten), `dhl:check` zum Prüfen der Verbindung und der Empfänger-ID, `return-labels:purge` (täglich 03:45).
- Tabelle `return_labels`, Modell `ReturnLabel`, private Ablage `return-labels`, `ReturnLabelService` (Sperre je Anfrage, vorhandenes Label wird zurückgegeben, Ausgang unklar wird nie automatisch wiederholt, PDF wird vor allem anderen gespeichert).
- Strukturierte Lieferadresse in `EocsOrder::$shippingAddress` (`deliveryAddress` unverändert).
- Seite „DHL Retoure“ (`/dhl-retoure`, Seitenleiste): Suche per Ticket- oder Bestellnummer, Adressbereich, Download, Versand als Antwort (`ZammadReplySender::send`) oder neues Ticket (neu: `ZammadReplySender::createTicket`, Gruppe aus `ZAMMAD_RETURN_GROUP`).
- Vorschlagsformular: Option „DHL-Retourenlabel erzeugen und im Antwortvorschlag berücksichtigen“ (Feld `return_label` im Formular, in den Formulareingaben des Vorschlags gespeichert). Die KI erhält nur den Satz aus `ReturnInstructions::fact()`. Die Schritte für den Kunden hängt `AnalysisPanel` fest an den Entwurf, das PDF wird im Antwortfeld als Anhang vorgemerkt. Knopf „DHL-Retourenlabel erstellen“ bei „Interne Abläufe“ für ein Label nach der Analyse.
- Tests: `tests/Feature/PROJ-64-*` (Dienst, Seite, Vorschlag).

**Abweichungen von Spec und Entwurf:**
- Die feste Empfängeradresse (Rücksendeziel) wird nur auf der Seite „DHL Retoure“ angezeigt (aus der DHL-Empfängerliste, eine Stunde zwischengespeichert), nicht im Vorschlagsformular; so löst die Ticketseite keinen DHL-Aufruf aus.
- Auf der Ticketseite steht die Lieferadresse nicht im HTML (bestehende Regel aus PROJ-7: Kundendaten aus EOCS erscheinen dort nie). Sie wird erst geladen, wenn die Option geöffnet wird (`GET /dhl-retoure/adresse`).
- Beim Erzeugen wird nicht geprüft, ob die Bestellung wirklich zum Ticket gehört; geprüft werden Format und Eingaben. Die Adresse bestätigt der Mitarbeiter.
- Der Hinweis „neue Nachricht seit dem Laden“ gilt auf der Seite „DHL Retoure“ über die zuletzt gesehene Nachricht (`seen_article`); beim Vorschlag gilt unverändert die Prüfung aus PROJ-19.
- Löscht der Mitarbeiter die Schritte aus dem Entwurf und speichert, erscheinen sie beim nächsten Laden des Vorschlags wieder.

**Noch nicht geprüft:** Die Oberfläche (Alpine-Teil) wurde in keinem Browser ausprobiert, nur die Server-Seite per Test. Gegen die echte DHL-Sandbox lief noch kein Aufruf.

### Erweiterung Auslandsretouren (gebaut 2026-11-16)
- `App\Returns\ReturnCountry` (sieben Länder: Name, DHL-Kennung, PLZ-Muster, übliche Sprache) und `App\Returns\ReturnLanguage` (fünf Sprachen, liest den deutschen Sprachnamen der KI).
- `ReturnInstructions`: Schritte, Mailtext und Betreff in fünf Sprachen; der Abgabeort und die Bezeichnung des Labels richten sich nach dem Land (Deutschland: DHL, Ausland: „auf dem Label genannter Versanddienst“, ohne „DHL“). Der deutsche Text für Deutschland ist wortgleich geblieben.
- `DhlClient`: Für das Ausland wird der Empfänger über die Empfängerliste des Landes nachgeschlagen (eine Stunde zwischengespeichert); kein oder mehrere Empfänger ergeben die neue Fehlerart „kein eindeutiger Empfänger“ ohne Bestellung. Deutschland nutzt weiter `DHL_RECEIVER_ID`.
- Spalte `return_labels.country` (Standard `DE`); das Land fließt nur im Ausland in die Kurzfassung der Anfrage ein, damit vorhandene deutsche Labels weiter erkannt werden.
- Formularprüfung: Land aus der Liste, PLZ je Land, Fehlertexte nennen die Regel des Landes bzw. die unterstützten Länder und das Geschäftskundenportal.
- Adressbereich: Land als Auswahlliste, Hinweis „Auslandsretoure“, Sperre bei einem Land ohne Label (bis der Mitarbeiter ein Land wählt), Empfängeranzeige je Land (Seite „DHL Retoure“, für die Länder der gefundenen Bestellungen und Deutschland).
- Seite „DHL Retoure“: Auswahl „Sprache der Mail“ (vorbelegt nach Land); ein Wechsel tauscht Text und Betreff, ein von Hand geänderter Text bleibt. Absenderland am Ergebnis und unter „Bisherige Labels“.
- Antwortvorschlag: Die Schritte folgen der Sprache des Entwurfs, sonst der üblichen Sprache des Landes (auch bei „Direkt antworten“, dessen Sprache nicht bekannt ist). Ob die Schritte schon im Entwurf stehen, erkennt die App an der Sendungsnummer.
- `dhl:check` zeigt je Land Empfänger und Stand.
- Tests: `tests/Feature/PROJ-64-DhlReturnAbroadTest.php` (71 Fälle); die bisherigen PROJ-64-Tests laufen unverändert, bis auf einen Datensatz, der die Ablehnung von Italien erwartete (jetzt Schweiz).

**Abweichungen von Spec und Entwurf:**
- Der Betreff eines neuen Tickets enthält auch im Ausland „DHL“ (in der gewählten Sprache), damit die Suche in Zammad nach einem unklaren Versand einheitlich bleibt.
- Die Empfängeranzeige gibt es nur für die Länder der gefundenen Bestellungen und Deutschland; wählt der Mitarbeiter von Hand ein anderes Land, fehlt die Zeile (das Label wird trotzdem für den richtigen Empfänger bestellt).
- Der Satz an die KI („vorfrankiertes DHL-Retourenlabel“) ist unverändert, auch im Ausland.

**Noch nicht geprüft:** Die geänderte Oberfläche (Länderauswahl, Sprachwechsel) wurde in keinem Browser bedient. Die Seite „DHL Retoure“ wurde für Ticket 2140387 serverseitig geladen: Land Frankreich und der Empfänger werden erkannt; EOCS liefert dort Hausnummer und Straße zusammen im Straßenfeld, der Mitarbeiter muss sie trennen. Eine echte Auslandsbestellung bei DHL steht aus.

## QA Test Results

> Zuerst der Stand vor der Erweiterung (2026-10-27). Die Prüfung der Auslandsretouren steht darunter im Abschnitt „QA Auslandsretouren (2026-11-16)“.

**Tested:** 2026-10-27
**App URL:** http://localhost:8081 (Sail), DHL-Produktion durch den Auftraggeber
**Tester:** QA Engineer (AI)

**Wie getestet wurde:** Server-Seite vollständig per Pest (Dienst, Seite, Vorschlag, Sicherheit: 70 Tests in `tests/Feature/PROJ-64-*`) und Prüfung des Codes gegen jedes Kriterium. Die Oberfläche (Alpine) habe ich nicht selbst in einem Browser bedient, auch keine Browser- und Breitentests (Chrome, Firefox, Safari, 375/768/1440 px). Der Auftraggeber hat den Ablauf auf der Seite „DHL Retoure“ in der Produktion von Hand ausprobiert: Label erzeugt und als neues Ticket versendet, ohne Beanstandung. Das Vorschlagsformular mit der Option und den Knopf bei „Interne Abläufe“ hat noch niemand im Browser bedient.

### Acceptance Criteria Status

#### Seite „DHL Retoure“ – Suche (6)
- [x] Seitenleisten-Eintrag, Suche per Ticket- und Bestellnummer, Auswahl bei mehreren Bestellungen, verständliche Meldung bei nichts gefunden oder EOCS nicht erreichbar, Aufforderung zur Namenswahl

#### Adresse prüfen und Label erzeugen (12)
- [x] Vorbefüllung, feste Empfängeradresse nur Anzeige, Amazon-Hinweis, Haken „Adresse geprüft“, Feldprüfungen ohne stilles Kürzen, Ausland abgelehnt, Referenzen (Bestell- und Ticketnummer, Original-Nummer bei Reklamationen), Download des unveränderten PDF, nur ein DHL-Aufruf bei Doppelklick und bei gleichzeitigem Zugriff

#### Versand an den Kunden (9)
- [x] Antwort auf Ticket, neues Ticket (Gruppe, Betreff, Pflicht-E-Mail), Ticketnummer mit Link, keine Wahl ergibt keinen Versand, fehlender Zammad-Zugang, gesendeter Stand
- [x] (BUG-1 behoben) „Der Versand schlägt fehl oder der Ausgang ist unklar … ein erneuter Versand wird nicht blind doppelt ausgelöst“: Nach einer Zeitüberschreitung ist ein zweiter Versand möglich (siehe BUG-1)

#### Einstieg über den Antwortvorschlag (8)
- [x] Option mit Adressbereich, KI erfährt nur „Label liegt bei“, feste Schritte im Entwurf, Anhang vorgemerkt, Versand über den bestehenden Weg, Knopf bei „Interne Abläufe“, Schritte nicht doppelt, fremdes Label wird abgelehnt
- [x] (BUG-2 behoben) „Option ohne Label … wird aufgefordert, zuerst das Label zu erzeugen oder die Option abzuwählen“ ist nicht umgesetzt

#### Fehler und Sicherheit (7)
- [x] DHL lehnt ab, fehlende Zugangsdaten, unklarer Ausgang, ungültiges PDF, Download nur mit Namen und Ticketnummer, Löschen nach Frist, keine Geheimnisse, Adressen oder Labeldaten im Log

### Edge Cases Status
- [x] Zusatz in der Hausnummer, Umlaute, PLZ mit führender Null, Firmenadresse, Ticket ohne Bestellung und Bestellung ohne Ticket, unbekannte Ticketnummer, neuere Kundennachricht (Hinweis auf der Seite), Label gespeichert aber Dateifehler, Sandbox und Produktion nur über die Konfiguration, Anleitungsplatzhalter ersetzt
- [x] (BUG-3 behoben) „Mehrere Pakete: jedes Paket ist ein eigenes Label über ‚Weiteres Label erstellen‘“ geht nach dem Versand nicht mehr

### Security Audit Results
- [x] Zugriff: Alle Aktionen laufen hinter der Namenswahl (wie die übrige App, ohne Login). Das Label lässt sich nur mit der UUID (nicht erratbar) und der passenden Ticketnummer herunterladen
- [x] Autorisierung: `receiverId` kommt nur aus der Konfiguration, die Empfängeradresse nie aus dem Formular; der Versand liest die Antwortadresse aus dem Ticket
- [x] Eingaben: Prüfklassen für alle Felder; Betreff wird serverseitig aus der geprüften Bestellnummer gebaut (kein Header-Einschleusen); Ausgaben der Oberfläche über `x-text` und `@js`, kein ungeprüftes HTML
- [x] CSRF: Alle POST-Aufrufe mit Token (Test-Middleware wie im übrigen Projekt)
- [x] Rate-Limits: Erzeugen und Senden 10/min, Adresse 30/min
- [x] Datenschutz: Adresse nicht im HTML der Ticketseite (Prüfung aus PROJ-7 bleibt grün), nicht im Prompt, nicht im Log, keine Kunden-E-Mail an DHL
- [x] Geheimnisse: Zugangsdaten nur serverseitig; keine Token in Antworten der App

### Bugs Found

#### BUG-1: Nach einer Zeitüberschreitung kann das Label doppelt gesendet werden (behoben 2026-10-27)
- **Severity:** Medium
- **Steps to Reproduce:**
  1. Seite „DHL Retoure“, Label erzeugen, „Senden“ (Antwort oder neues Ticket).
  2. Zammad antwortet nicht rechtzeitig (im Test: Zeitüberschreitung).
  3. Erwartet: Die App prüft vor einem erneuten Versand, ob die Mail schon da ist (wie PROJ-19), oder verweigert ihn.
  4. Tatsächlich: Die Meldung verspricht „Beim nächsten Versuch prüft die App zuerst das Ticket“, tut es aber nicht. Ein zweiter Klick sendet erneut (im Test: zwei Zammad-Aufrufe, `sent_at` leer). Der Kunde bekommt die Mail doppelt oder es entstehen zwei Tickets.
- **Priority:** Fix before deployment

#### BUG-2: Option im Vorschlagsformular ohne Label und Wirkung beim Abwählen (behoben 2026-10-27)
- **Severity:** Medium
- **Steps to Reproduce:**
  1. Ticket mit Bestellung öffnen, im Vorschlagsformular die Option anhaken, aber „Label erzeugen“ nicht drücken, „Vorschlag erstellen“.
  2. Erwartet: Aufforderung, erst das Label zu erzeugen oder die Option abzuwählen.
  3. Tatsächlich: Der Vorschlag entsteht ohne Label, ohne Hinweis.
  4. Zusätzlich: Wer nach dem Erzeugen die Option wieder abwählt, schickt das Label trotzdem mit (verstecktes Feld bleibt im Formular).
- **Priority:** Fix before deployment

#### BUG-3: Zweites Paket nach dem Versand nicht möglich (behoben 2026-10-27)
- **Severity:** Medium
- **Steps to Reproduce:**
  1. Label erzeugen und senden.
  2. Erwartet: „Weiteres Label erstellen“ (zweites Paket, verlorenes Label) bleibt möglich.
  3. Tatsächlich: Der Knopf ist nach dem Versand ausgeblendet, auch nach Neuladen der Seite (das gesendete Label wird als aktuelles gezeigt). Nur der Umweg über eine andere Adresse (anderer Fingerabdruck) bestellt ein neues Label.
- **Priority:** Fix before deployment

#### BUG-4: Im Antwortvorschlag fehlt ein Hinweis oder Link zum PDF, wenn der Anhang nicht vorgemerkt werden kann (behoben 2026-10-27)
- **Severity:** Medium
- **Steps to Reproduce:**
  1. Mitarbeiter ohne Zammad-Token (oder nach Neuladen bei einem Label, das erst nach der Analyse erzeugt wurde) öffnet einen Vorschlag mit Label.
  2. Erwartet: ein Hinweis und ein „Herunterladen“-Link, denn der Text spricht vom „angehängten“ Label.
  3. Tatsächlich: Die Schritte stehen im Entwurf, das PDF ist nicht vorgemerkt und nirgends verlinkt. Es liegt nur unter „Bisherige Labels“ auf der Seite „DHL Retoure“.
- **Priority:** Fix in next sprint

### Summary
- **Acceptance Criteria:** 42/42 bestanden (nach Behebung von BUG-1 und BUG-2; zuerst 40/42)
- **Bugs Found:** 4 gesamt (0 critical, 0 high, 4 medium, 0 low gezählt; kleinere Schönheitsfehler sind nicht aufgeführt)
- **Security:** Pass
- **Regression:** Gesamtlauf 1992 Tests, 1988 grün. Die 4 Fehlschläge (Version 0.2.1 gegen 0.2.4 aus der `.env`, Admin-Berichte PROJ-56) hängen an der lokalen `.env` und nicht an PROJ-64.
- **Production Ready:** YES
- **Recommendation:** Alle vier mittleren Fehler sind behoben und mit Tests abgesichert; Freigabe erteilt.

### Behebung der QA-Fehler (2026-10-27)
- **BUG-1:** Vor dem Senden wird „Versand gestartet“ (mit Ziel) gespeichert. Nach einem unklaren Ausgang prüft die App bei einer Antwort auf dasselbe Ticket zuerst in Zammad, ob die Nachricht angekommen ist (dann gilt das Label als gesendet, nichts geht doppelt raus). Bei einem neuen Ticket oder einem anderen Ziel muss der Mitarbeiter den erneuten Versand ausdrücklich bestätigen („Trotzdem erneut senden“); die Meldung nennt, wonach er in Zammad suchen soll. Innerhalb von 90 Sekunden gilt „läuft gerade“. Neue Spalten `send_started_at` und `send_started_target`.
- **BUG-2:** Ist die Option an, aber kein Label erzeugt, verhindert das Formular das Absenden mit Hinweis. Das Label wird nur mitgeschickt, solange die Option an ist.
- **BUG-3:** „Weiteres Label erstellen“ bleibt nach dem Versand sichtbar und öffnet wieder die Adresse; das neue Label wird bewusst bestellt (auch nach einem unklaren Ausgang). Zugleich ist behoben, dass ein im Formular erzeugtes Label sonst an den Entwurf eines früheren Vorschlags auf derselben Seite gehangen hätte.
- **BUG-4:** Im Antwortfeld steht immer „DHL-Retourenlabel (Sendungsnummer …): Herunterladen“, mit Hinweis, ob das PDF vorgemerkt ist oder von Hand angehängt werden muss. Ein bei „Interne Abläufe“ erzeugtes Label wird dem Vorschlag zugeordnet (`PUT …/retourenlabel`) und kommt nach dem Neuladen wieder.
- Tests dazu in `tests/Feature/PROJ-64-*`; Gesamtlauf 2008 Tests, 2004 grün (die 4 anderen hängen an der lokalen `.env`, siehe oben). Die Oberfläche wurde auch nach den Korrekturen nicht in einem Browser bedient.

## QA Auslandsretouren (2026-11-16)

**Tested:** 2026-11-16
**App URL:** http://localhost:8081 (Sail)
**Tester:** QA Engineer (AI)

**Wie getestet wurde:** Server-Seite per Pest (`tests/Feature/PROJ-64-DhlReturnAbroadTest.php`, jetzt 77 Fälle, davon 8 neu aus dieser Prüfung) und Durchsicht des Codes gegen jedes neue Kriterium. DHL ist in den Tests nachgestellt. Nicht getestet: die Oberfläche im Browser (Länderauswahl, Sperre bei anderem Land, Sprachwechsel; auch keine Browser- und Breitentests) und eine echte Bestellung eines Auslandslabels bei DHL.

### Acceptance Criteria Status (neue und geänderte Kriterien)

#### Adresse prüfen und Label erzeugen (7)
- [x] Vorbefüllung mit Land für die sieben Länder (EOCS-Kürzel wird groß geschrieben übernommen)
- [x] PLZ je Land (DE, FR, IT fünf Ziffern; AT, BE, LU vier Ziffern; NL vier Ziffern und zwei Buchstaben, mit und ohne Leerzeichen), Fehlertext nennt die Regel des Landes
- [x] Anderes Land (Schweiz, klein geschrieben, dreistellig, leer) wird auf dem Server abgelehnt, Hinweis nennt die Länder und das Geschäftskundenportal; es entsteht kein Label und kein DHL-Aufruf
- [x] Land nur aus der Liste (Server prüft unabhängig von der Oberfläche)
- [x] Label für den Empfänger des Landes (alle sechs Länder), Deutschland mit dem konfigurierten Empfänger und ohne Abfrage der Empfängerliste
- [x] Kein oder mehrere Empfänger: nichts wird bestellt, Meldung mit Grund und Geschäftskundenportal
- [x] Absenderland am Label und unter „Bisherige Labels“; Hinweis „Auslandsretoure“ statt „nur aus Deutschland“ (Text vorhanden; das Ein- und Ausblenden im Browser ist nicht geprüft)

#### Kundenanleitung nach Sprache und Land (7)
- [x] Feste Texte in fünf Sprachen, Vorbelegung nach Land, Sprache des Entwurfs geht vor (auch bei nachträglich zugeordnetem Label und bei „Direkt antworten“)
- [x] Deutschland nennt DHL-Filiale und DHL-Paketshop (Text wortgleich wie vorher), Ausland nennt neutral den Versanddienst auf dem Label, in keiner Sprache „DHL“ im Mailtext
- [x] Mailtext und Betreff in der gewählten Sprache; unbekannte Sprache wird abgelehnt
- [x] Schritte nicht doppelt, auch wenn sie in einer anderen Sprache im Entwurf stehen
- [x] Die KI erfährt weder Land noch Adresse noch Sendungsnummer

### Edge Cases Status
- [x] Niederländische PLZ mit und ohne Leerzeichen; Land außerhalb der sieben (Adresse wird gezeigt, Server lehnt ab); Kunde in Frankreich schreibt Deutsch (deutsche Schritte, neutraler Abgabeort); gleiche Adresse mit anderem Land ist eine andere Anfrage; dieselbe Auslandsanfrage zweimal ergibt nur eine Bestellung; Label mit einem nicht mehr angebotenen Land zählt als deutsch
- [x] (vom Auftraggeber mit einem echten Label aus Frankreich bestätigt, 2026-11-16) Ob DHL eine Auslandsretoure ohne Gewicht und Zollangaben annimmt. Lehnt DHL ab, zeigt die App den Wortlaut von DHL (dieser Weg ist getestet)

### Security Audit Results
- [x] Empfänger kommt nie aus dem Formular (für das Ausland aus der DHL-Liste, für Deutschland aus der Konfiguration); Land und Sprache nur aus festen Listen
- [x] Betreff wird auf dem Server aus der geprüften Bestellnummer und der Sprache gebaut
- [x] Adresse weder im HTML der Ticketseite noch im Prompt noch im Log (auch beim neuen Fehler „kein eindeutiger Empfänger“)
- [x] Keine neuen Routen, keine neuen Umgebungsvariablen, Rate-Limits unverändert

### Bugs Found
Keine (Schönheitsfehler der Stufe „Low“ sind nicht aufgeführt).

### Summary
- **Acceptance Criteria:** 14/14 neue und geänderte Kriterien auf dem Server bestanden
- **Bugs Found:** 0 (critical, high, medium)
- **Security:** Pass
- **Regression:** Gesamtlauf 2720 Tests, 2717 grün, 2 übersprungen, 1 Fehlschlag: `PROJ-36-AnalysisIntegrationTest` („a free reproduction … does not load the goodwill rules“, POLICY-007 wird geladen). Der Test läuft gegen die echte Wissensbasis; PROJ-64 hat weder Wissen noch Wissensabruf geändert. Die Ursache ist nicht untersucht.
- **Production Ready:** YES
- **Freigabe (2026-11-16):** Der Auftraggeber hat das erste echte Auslandslabel in der App erzeugt (Frankreich, Ticket 2140387): DHL nimmt die Bestellung ohne Gewicht an, die Texte kamen in der richtigen Sprache. Damit ist auch die Oberfläche einmal im Browser bedient; die übrigen Länder und Browser- und Breitentests wurden nicht einzeln geprüft.

## Deployment
_To be added by /deploy_
