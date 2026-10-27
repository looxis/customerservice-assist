# PROJ-64: DHL-Retourenlabel erstellen und versenden

## Status: Planned
**Created:** 2026-10-27
**Last Updated:** 2026-10-27

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

## User Stories
- Als **Mitarbeiter im Kundenservice** will ich zu einer Ticket- oder Bestellnummer ein Retourenlabel erzeugen, ohne die Adresse abzutippen, damit ich nicht mehr ins Geschäftskundenportal wechseln muss.
- Als **Mitarbeiter** will ich die Lieferadresse vor dem Erzeugen sehen und korrigieren können, damit kein Label mit falscher Anschrift entsteht.
- Als **Mitarbeiter** will ich das Label herunterladen können, damit ich es auch außerhalb der App verwenden kann.
- Als **Mitarbeiter** will ich das Label mit einem Anleitungstext per Klick als Antwort auf ein Ticket oder als neues E-Mail-Ticket an den Kunden senden, damit der Kunde sofort alles bekommt.
- Als **Mitarbeiter** will ich schon im Vorschlagsformular angeben, dass ein Retourenlabel beiliegt, damit der Antwortvorschlag das berücksichtigt, die Anleitung enthält und das PDF schon angehängt ist.
- Als **Aushilfe** will ich bei Amazon-Bestellungen (ohne Adresse in EOCS) einen klaren Hinweis, dass ich die Adresse selbst eintragen muss, damit ich nicht raten muss.

## Out of Scope
- Verfolgen der Rücksendung über die DHL-Schnittstelle, Zählung der Retouren und Auswertung (PROJ-65).
- Abholung der Ware beim Kunden (nur Einlieferung durch den Kunden in Filiale oder Paketshop).
- Auslandsretouren und Zolldokumente: nur Absender in Deutschland.
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
- [ ] Angenommen eine Bestellung mit Lieferadresse in Deutschland, wenn sie ausgewählt ist, dann sind Name, Straße, Hausnummer, PLZ und Ort im Formular vorbefüllt und änderbar.
- [ ] Angenommen das Formular, wenn die feste Empfängeradresse (LOOXIS) angezeigt wird, dann kann der Mitarbeiter sie zur Kontrolle sehen, aber nicht ändern.
- [ ] Angenommen eine Amazon-Bestellung oder eine Bestellung ohne Lieferadresse, wenn sie ausgewählt ist, dann bleiben die Adressfelder leer und ein Hinweis erklärt, dass die Adresse aus dem Ticket von Hand einzutragen ist.
- [ ] Angenommen der Mitarbeiter hat den Haken „Adresse geprüft“ nicht gesetzt, wenn er „Label erzeugen“ wählt, dann wird nichts bei DHL beauftragt und ein Hinweis erscheint.
- [ ] Angenommen ein Pflichtfeld fehlt oder ist zu lang (Name über 50, Straße über 50, Hausnummer über 10, Ort über 40 Zeichen) oder die PLZ hat nicht genau fünf Ziffern, wenn der Mitarbeiter „Label erzeugen“ wählt, dann zeigt das Formular die Fehler am Feld und schneidet nichts still ab.
- [ ] Angenommen das Land ist nicht Deutschland, wenn der Mitarbeiter „Label erzeugen“ wählt, dann wird es abgelehnt mit dem Hinweis, dass die App nur Retouren aus Deutschland erzeugt.
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
- Test mit der DHL-Sandbox: Eine Umstellung auf Produktion erfolgt nur über die Konfiguration.

## Technical Requirements (optional)
- Schnittstelle: DHL Parcel DE Returns API 1.1.0 (Pfad `/v1`), Anmeldung per OAuth2 Password Grant; die alte `cig.dhl.de`-Schnittstelle wird nicht benutzt.
- Zugangsdaten, Retourenempfänger-ID und Zammad-Gruppe für neue Tickets stehen in `.env`/Konfiguration, nie in der Datenbank oder im Browser.
- Sicherheit: Alle Aufrufe laufen serverseitig; Throttling auf Erzeugen und Senden; kein POST-Wiederholen bei der Label-Beauftragung.
- Datenschutz: Kundenadressen gehen nur an DHL (für das Label) und Zammad (Mail); nicht an das Sprachmodell, nicht ins Log.
- Aufbewahrung der Label-Dateien: kurze, konfigurierbare Frist (analog Anhangs-Zwischenspeicher).
- Die Umsetzung benötigt kein neues Composer-Paket.

## Open Questions
- [ ] Wie heißt die Zammad-Gruppe, in der neue Retouren-Tickets angelegt werden (`ZAMMAD_RETURN_GROUP`), und dürfen die persönlichen Zammad-Tokens der Mitarbeiter dort Tickets anlegen?
- [ ] Ist der Vertrag „DHL Retoure Online“ für die API freigeschaltet (Systembenutzer, produktive `receiverId` mit Abrechnungsnummer, Freigabe für „nur Deutschland“)?
- [ ] Wortlaut des Anleitungstextes (Verpacken, Label aufkleben, Abgabe in Filiale/Paketshop, keine Abholung); Standardtext schlägt der Entwickler vor, der Auftraggeber gibt frei.
- [ ] Nach dem Erzeugen im Formular: Soll das Ticket nach dem Versand ebenfalls „schließen“ oder „Erinnerung“ als Standard vorschlagen? Vorschlag: wie PROJ-19.

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
| Nur Deutschland | Auslandsretouren brauchen eigene Empfänger und Zolldokumente | 2026-10-27 |
| Referenz im Label: Bestellnummer und Ticketnummer | Der Kunde und das Lager können die Sendung zuordnen; die Ticketnummer sucht sich später auch für PROJ-65 | 2026-10-27 |
| Tracking und Zählung als eigene Funktion PROJ-65 | Eigener Nutzen, eigene Schnittstelle (Sendungsverfolgung) | 2026-10-27 |

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
