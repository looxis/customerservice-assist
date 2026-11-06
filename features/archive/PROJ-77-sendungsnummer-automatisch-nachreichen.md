# PROJ-77: Sendungsnummer automatisch nachreichen

## Status: Approved
**Created:** 2026-11-06
**Last Updated:** 2026-11-06 (Tech Design)

## Dependencies
- Requires: PROJ-70 (ToDos; häufige ToDos, Reklamationsnummer am ToDo in `external_ref`, vorbereitete Antwort), PROJ-7 (Bestellung und Reklamationsaufträge samt Sendungen aus EOCS), PROJ-19 (Antwort über Zammad senden, Token und Signatur je Mitarbeiter), PROJ-27 (Einstellungen mit Verlauf: Vorlagentext), PROJ-67/68 (Ticket-Eingang, Zusammenführung und Schließen)
- Berührt: PRD-Ziel „Versand nur durch ausdrücklichen Klick“ (PROJ-19); dieses Feature ist die erste bewusste Ausnahme (siehe Product Decisions)

## Anlass
Bei Reklamationen sagt die Antwort an den Kunden zu, dass die neu gefertigte Ware bald verschickt wird. Der Mitarbeiter legt dafür das ToDo „Sendungsnummer nachreichen“ an (PROJ-70), wartet auf den Versand, schlägt die Sendungsnummer in EOCS nach und schreibt dem Kunden. Das ist der häufigste Fall bei den ToDos und rein mechanisch. Die App kennt die Reklamationsnummer (z. B. `R1-7KA-0QH8-6AF8`) und lädt die Sendungen der Reklamation schon aus EOCS. Sie soll das Nachschlagen und die Antwort selbst übernehmen.

## User Stories
- Als Mitarbeiter will ich, dass die App nach dem Versand einer Reklamation dem Kunden die Sendungsnummer automatisch mitteilt, damit ich nicht nachschlagen und nicht erneut schreiben muss.
- Als Mitarbeiter will ich beim Anlegen des ToDos mit einem Haken bestimmen, ob automatisch gesendet wird, damit ich bei Sonderfällen selbst antworten kann.
- Als Mitarbeiter will ich in der ToDo-Liste sehen, ob und wann die App geprüft oder gesendet hat und was ich prüfen muss, damit ich die automatisch gesendeten Antworten kontrollieren kann.
- Als Mitarbeiter will ich, dass die App bei Zweifeln (andere Sprache, fehlender Versanddienstleister, technischer Fehler) nicht sendet, sondern die Antwort vorbereitet, damit dem Kunden nichts Falsches geschickt wird.
- Als Kunde will ich nach dem Versand meiner Reklamationsware zeitnah die Sendungsnummer erhalten, damit ich das Paket verfolgen kann.
- Als Admin will ich den Vorlagentext für die Antwort in den Einstellungen ändern, damit er zu unserem Ton passt.

## Out of Scope
- Retouren-Tracking und Zählung (PROJ-65)
- Übersetzte Vorlagen für andere Sprachen; bei anderer oder unbekannter Sprache wird nie automatisch gesendet
- Eigene Vorlage für mehrere Sendungen; es gilt die eine Vorlage, die Nummern werden mit Komma und Leerzeichen eingesetzt
- Automatische Erledigung oder Antwort bei anderen ToDos („(Teil-)Erstattung vornehmen“, „Nachforschungsauftrag Versand“)
- Hauptschalter für alle ToDos in den Einstellungen (bewusst nicht; Abwahl nur je ToDo, ein Hauptschalter kann später ergänzt werden)
- Prüfung der Originalbestellung: deren Sendungen zählen nie
- Feiertagskalender
- Benachrichtigungen außerhalb der App (E-Mail, Push); alles bleibt in der ToDo-Liste sichtbar
- Statusverfolgung der Sendung (zugestellt, verzögert), siehe PROJ-65
- Anmeldung und Rechte je Benutzer (PROJ-15)

## Ablauf in Kürze
1. Der Mitarbeiter legt das ToDo „Sendungsnummer nachreichen“ an (PROJ-70, auf der Ergebnisseite oder im Dialog). Der Haken „Mail bei Verfügbarkeit der Sendungsnummer automatisiert versenden“ ist vorab gesetzt. Die Reklamationsnummer steht am ToDo.
2. Die App prüft zu festen Zeiten in EOCS, ob die Reklamation versendet wurde.
3. Liegt eine Sendungsnummer samt Versanddienstleister vor, sendet die App die Antwort aus der Vorlage im Namen des Mitarbeiters, setzt eine interne Notiz, schließt das Ticket und erledigt das ToDo mit dem Kennzeichen „automatisch“.
4. Wo sie nicht senden darf, bereitet sie die Antwort vor und markiert das ToDo mit „Bitte prüfen“ und dem Grund.

## Prüfzeitplan (Berliner Zeit)
Geprüft wird Montag bis Samstag, nie am Sonntag. Feste Uhrzeiten, bewusst ungerade, damit EOCS nicht zu runden Zeiten belastet wird.

| ToDo angelegt | Prüfungen am selben Tag | Danach an jedem Prüftag |
|---|---|---|
| vor 14:00 | 16:07 und 18:34 | 11:26 und 16:16 |
| 14:00 bis 16:00 | nur 18:34 | 11:26 und 16:16 |
| ab 16:00 | keine | 11:26 und 16:16 ab dem nächsten Prüftag |

Obergrenze: 5 Kalendertage ab dem Anlegen, Sonntage zählen nicht mit. Danach endet die automatische Prüfung, das ToDo bleibt offen und zeigt „Sendungsnummer fehlt seit 5 Tagen“.

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### ToDo und Haken
- [ ] Angenommen der Mitarbeiter wählt beim Anlegen „Sendungsnummer nachreichen“, wenn das ToDo angelegt wird, dann ist der Haken „Mail bei Verfügbarkeit der Sendungsnummer automatisiert versenden“ vorab gesetzt und er kann ihn abwählen.
- [ ] Angenommen der Antwortvorschlag ist in einer anderen Sprache als Deutsch oder die Sprache ist unbekannt, wenn das ToDo angelegt wird, dann ist der Haken abgewählt und ein Hinweis nennt den Grund.
- [ ] Angenommen der Haken ist abgewählt, wenn die Prüfzeit kommt, dann prüft die App dieses ToDo nicht; es verhält sich wie in PROJ-70.
- [ ] Angenommen ein ToDo hat keine Reklamationsnummer oder Bestellnummer am ToDo (z. B. vor PROJ-77 angelegt oder von Hand), wenn die Prüfzeit kommt, dann wird es nicht automatisch behandelt.
- [ ] Angenommen ein ToDo wird beim Senden einer Antwort angelegt und die Antwort wurde nicht gesendet, wenn die Prüfzeit kommt, dann gibt es kein ToDo und nichts zu prüfen.

### Prüfung
- [ ] Angenommen ein ToDo wurde vor 14:00 angelegt, wenn der Prüfzeitplan läuft, dann wird um 16:07 und 18:34 desselben Tages und ab dem nächsten Prüftag um 11:26 und 16:16 geprüft.
- [ ] Angenommen ein ToDo wurde zwischen 14:00 und 16:00 angelegt, wenn der Prüfzeitplan läuft, dann wird zuerst um 18:34 geprüft.
- [ ] Angenommen ein ToDo wurde ab 16:00 angelegt, wenn der Prüfzeitplan läuft, dann wird zuerst am nächsten Prüftag um 11:26 geprüft.
- [ ] Angenommen es ist Sonntag, wenn eine Prüfzeit ansteht, dann wird nicht geprüft; der Samstag zählt als Prüftag.
- [ ] Angenommen am ToDo steht die Reklamationsnummer, wenn geprüft wird, dann liest die App nur die Sendungen dieser Reklamation aus EOCS; die Sendungen der Originalbestellung werden nie benutzt.
- [ ] Angenommen am ToDo steht nur die Originalbestellnummer, wenn geprüft wird, dann sucht die App bei jeder Prüfung die neueste Reklamation dazu und benutzt deren Sendungen; gibt es keine, gilt das als „noch keine Sendungsnummer“.
- [ ] Angenommen 5 Tage seit dem Anlegen sind ohne Sendungsnummer vergangen, wenn die nächste Prüfzeit kommt, dann endet die automatische Prüfung, das ToDo bleibt offen und zeigt „Sendungsnummer fehlt seit 5 Tagen“.
- [ ] Angenommen ein ToDo wurde in der Zwischenzeit von Hand erledigt oder gelöscht, wenn die Prüfzeit kommt, dann wird es nicht mehr geprüft und nichts gesendet.

### Senden
- [ ] Angenommen die Reklamation hat eine Sendung mit Sendungsnummer und Versanddienstleister, wenn geprüft wird, dann sendet die App die Antwort aus der Vorlage mit gefüllten Platzhaltern (`[Sendungsnummer]`, `[Carrier]`) im Namen des Mitarbeiters, der das ToDo angelegt hat, mit dessen Signatur.
- [ ] Angenommen die Reklamation hat mehrere Sendungsnummern oder mehrere Versanddienstleister, wenn geprüft wird, dann setzt die App alle verschiedenen Nummern bzw. Dienstleister, durch Komma und Leerzeichen getrennt, ohne Doppelte in die Vorlage ein und sendet.
- [ ] Angenommen der Kunde hat nach dem Anlegen des ToDos neu geschrieben oder ein anderer Mitarbeiter hat geantwortet, wenn geprüft wird und eine Sendungsnummer vorliegt, dann sendet die App trotzdem.
- [ ] Angenommen das Ticket wurde zusammengeführt, wenn die App sendet, dann geht die Antwort in das Ticket, in das es zusammengeführt wurde.
- [ ] Angenommen die Antwort ging bei Zammad ein, wenn die App fertig ist, dann steht eine interne Notiz am Ticket („Automatisch gesendet am … um …, Sendungsnummer … laut EOCS, ToDo #…“), das Ticket ist geschlossen und das ToDo ist erledigt mit dem Kennzeichen „automatisch“.
- [ ] Angenommen eine Antwort wurde automatisch gesendet, wenn die Prüfung erneut läuft oder der Vorgang wiederholt wird, dann wird nie ein zweites Mal an den Kunden gesendet.
- [ ] Angenommen eine Antwort wurde automatisch gesendet, wenn der Admin die Liste der erledigten ToDos öffnet, dann sind diese ToDos als „automatisch“ erkennbar und zeigen Zeitpunkt, Sendungsnummer(n) und den gesendeten Text.

### Nicht senden, „Bitte prüfen“
- [ ] Angenommen eine Sendungsnummer liegt ohne Versanddienstleister vor, wenn geprüft wird, dann sendet die App nicht; das ToDo zeigt „Bitte prüfen: Sendungsnummer ohne Versanddienstleister“, die Antwort ist vorbereitet.
- [ ] Angenommen der Text enthält nach dem Füllen noch Platzhalter, wenn geprüft wird, dann sendet die App nicht; das ToDo zeigt „Bitte prüfen“ mit dem Grund.
- [ ] Angenommen der Mitarbeiter, der das ToDo angelegt hat, hat keinen gültigen Zammad-Token mehr, wenn die App senden will, dann sendet sie nicht und zeigt „Bitte prüfen: kein Zugang des Mitarbeiters“.
- [ ] Angenommen die Sprache des Kunden ist nicht Deutsch oder unbekannt, wenn eine Sendungsnummer vorliegt, dann sendet die App nicht; das ToDo zeigt „Bitte prüfen“ mit der Sprache, die Antwort ist auf Deutsch vorbereitet.
- [ ] Angenommen ein ToDo steht auf „Bitte prüfen“, wenn der Mitarbeiter es öffnet, dann gelangt er wie beim bisherigen „Erledigen mit Kundenbenachrichtigung“ zur vorbereiteten Antwort im Ticket.

### Technische Fehler
- [ ] Angenommen EOCS oder Zammad ist nicht erreichbar oder Zammad lehnt die Antwort ab, wenn geprüft wird, dann versucht die App es zur nächsten Prüfzeit erneut und zählt den Fehler am ToDo.
- [ ] Angenommen drei technische Fehler in Folge, wenn der dritte auftritt, dann zeigt das ToDo „Bitte prüfen: technischer Fehler“ mit der Meldung; die automatische Prüfung wird beendet.
- [ ] Angenommen die Antwort ist in Zammad angekommen, danach schlägt ein weiterer Schritt (Notiz, Schließen) fehl, wenn die App fertig ist, dann ist das ToDo trotzdem erledigt; der fehlgeschlagene Schritt steht am ToDo.

### Sichtbarkeit
- [ ] Angenommen ein ToDo wird automatisch geprüft, wenn der Mitarbeiter es in Liste, Startseite oder am Ticket sieht, dann zeigt es „Automatische Prüfung: nächste Prüfung heute 18:34“ (oder den nächsten Zeitpunkt).
- [ ] Angenommen jede Prüfung hat stattgefunden, wenn der Admin den Verlauf des ToDos ansieht, dann sind Zeitpunkt, Ergebnis (noch nichts, gesendet, bitte prüfen, Fehler) und die Zahl der Prüfungen festgehalten.

### Vorlage
- [ ] Angenommen ein Admin ändert den Vorlagentext „ToDo-Antwort: Sendungsnummer nachreichen“ (Einstellungen > Mail-Anzeige, PROJ-70), wenn er speichert, dann gilt der neue Text ab der nächsten automatischen Antwort; bereits erledigte ToDos ändern sich nicht.
- [ ] Angenommen die Vorlage nennt `[Anrede]`, `[Name]`, `[Bestellung/Reklamation]` und `[Nummer]`, wenn die App sendet, dann sind sie wie beim Anlegen gefüllt („Guten Tag“, Kundenname laut Ticket, „Reklamation“, Reklamationsnummer).

## Edge Cases
- Die Reklamation ist beim ersten Prüfen noch nicht in EOCS angelegt (Mitarbeiter legt sie erst später an): Die App sucht mit der Originalbestellnummer jedes Mal neu; sie sendet nie Sendungen der Originalbestellung.
- Zu einer Bestellung gibt es mehrere Reklamationen (R1, R2): Es gilt die Nummer am ToDo; steht nur die Originalbestellung am ToDo, die neueste Reklamation. Entscheiden kann der Mitarbeiter über die Reklamationsnummer, die er beim Anlegen angibt.
- Sendung ohne Sendungsnummer (z. B. Abholung): zählt als „noch keine Sendungsnummer“, Prüfung geht weiter.
- Sendungsnummer taucht in zwei Sendungen doppelt auf: wird nur einmal genannt.
- Das Ticket ist inzwischen geschlossen: Die Antwort wird trotzdem gesendet (Zammad öffnet das Ticket, die App schließt es wieder).
- Das Ticket wurde mehrfach zusammengeführt: Die App folgt der Kette bis zum aktuellen Ticket; ist es nicht mehr erreichbar, gilt das als technischer Fehler.
- Der Kunde schreibt, während die App sendet: Die Antwort geht trotzdem; sie ist die erwartete Information, der Verlauf zeigt die Reihenfolge.
- Der Server war zur Prüfzeit nicht erreichbar: Die ausgefallene Prüfung wird nicht nachgeholt, die nächste Prüfzeit gilt; der Ausfall zählt nicht als technischer Fehler, solange keine Anfrage gestellt wurde.
- Zwei Prüfläufe gleichzeitig: Pro ToDo wird nur einmal gesendet (atomar, wie beim Abschließen in PROJ-70).
- Der Mitarbeiter wählt den Haken ab, nachdem das ToDo angelegt wurde: wird beim Bearbeiten des ToDos möglich, wirkt ab der nächsten Prüfung.
- Der Kunde schreibt in einer Sprache, die die Spracherkennung nicht sicher bestimmt: gilt als unbekannt, kein automatischer Versand.
- ToDos, die vor PROJ-77 angelegt wurden: bleiben ohne automatische Prüfung (keine Rückwirkung).

## Technical Requirements (optional)
- Security: Kundendaten (Name, Sendungsnummer, gesendeter Text) bleiben verschlüsselt wie die ToDo-Texte (PROJ-70). Der Versand verwendet den Zammad-Token des Mitarbeiters, der das ToDo angelegt hat; der Token wird nirgends angezeigt oder gespeichert, wo er nicht schon liegt.
- Last: Alle Prüfungen eines Zeitpunkts laufen nacheinander und gebündelt; Zahl der EOCS-Anfragen je ToDo gering halten (die Sendungen kommen mit der Reklamationsabfrage, PROJ-70).
- Protokoll: Jede Prüfung und jeder Versand wird mit Zeitpunkt, Ergebnis und ToDo festgehalten (Verlauf des ToDos).
- Zeitzone: Europe/Berlin für Zeitplan, Anzeige und Obergrenze.

## Open Questions
- [x] (entschieden) Welche Zeiten gelten, wenn ein ToDo am Sonntag angelegt wird? Annahme: wie „ab 16:00“, erste Prüfung am Montag um 11:26.
- [x] (im Architekturschritt entschieden) Wo steht der Haken „automatisch senden“ im Dialog „ToDo anlegen“ der ToDo-Übersicht, wenn dort kein Fall bekannt ist (keine Sprache, keine Reklamationsnummer)? Annahme: Haken nur sichtbar, wenn eine Nummer eingetragen ist; der Dialog bekommt dafür ein Feld „Reklamations- oder Bestellnummer“.
- [x] (entschieden) Wann endet die Prüfung bei Wochenenden genau (5 Kalendertage ohne Sonntage ab dem Anlegen)? Annahme wie beschrieben; bei Bedarf im Architekturschritt konkretisieren.
- [ ] Ein Hauptschalter für den Notfall wurde bewusst nicht aufgenommen; nach den ersten Stichproben neu bewerten.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Automatischer Versand ohne Klick, als enge Ausnahme vom Ziel „Versand nur nach Klick“ | Der Fall ist rein mechanisch (Sendungsnummer aus EOCS in eine feste Vorlage) und der häufigste ToDo-Fall; der Auftraggeber kontrolliert stichprobenartig über die erledigten ToDos | 2026-11-06 |
| Schutzregeln nur: Sprache, Versanddienstleister vorhanden, Platzhalter gefüllt, Zugang des Mitarbeiters, technische Fehler | Neue Kundennachricht, fremde Antwort und Zusammenführung sollen den Versand nicht stoppen; die Information ist weiterhin nötig | 2026-11-06 |
| Feste, ungerade Prüfzeiten (16:07, 18:34, 11:26, 16:16) statt „X Stunden später“ | Verteilt die Last auf EOCS, Kunden bekommen nichts nachts; Versandzeiten bei LOOXIS sind tagsüber | 2026-11-06 |
| Montag bis Samstag prüfen, Sonntag nicht; keine Feiertage | Samstag wird versendet; ein Feiertagskalender lohnt den Aufwand nicht | 2026-11-06 |
| Obergrenze 5 Tage | Danach braucht der Fall einen Menschen (Nachforschung) | 2026-11-06 |
| Absender ist der Mitarbeiter, der das ToDo angelegt hat | Er kennt den Fall, hat Token und Signatur; kein Versand unter fremdem Namen | 2026-11-06 |
| Danach interne Notiz und Ticket schließen | Nachvollziehbarkeit und Ticket erledigt; Zammad öffnet es bei einer Antwort des Kunden wieder | 2026-11-06 |
| Eine Vorlage; mehrere Nummern mit Komma und Leerzeichen | Einfachheit; die Singular-Formulierung wird in Kauf genommen | 2026-11-06 |
| Haken je ToDo, vorab gesetzt, kein Hauptschalter | Auftraggeber wünscht die Steuerung am ToDo; Stoppen durch Abwählen | 2026-11-06 |
| Nur Deutsch; unbekannte Sprache sendet nicht | Vorlagen sind deutsch; ein falsches Sprachschreiben wäre schlimmer als eine Verzögerung | 2026-11-06 |
| Drei technische Fehler in Folge führen zu „Bitte prüfen“ | Verhindert stilles Scheitern ohne ständigen Alarm bei kurzen Ausfällen | 2026-11-06 |
| Nur Sendungen der Reklamation, nie der Originalbestellung | Die Nummer der Originalbestellung gehört zum ersten, beschädigten Paket | 2026-11-06 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|
| Geplante Läufe mit vier festen Zeiten (Mo–Sa) statt Termin je ToDo | Zeitplaner existiert; feste Zeiten verteilen die Last auf EOCS und sind zentral änderbar | 2026-11-06 |
| Zeitplanregel in einer eigenen, testbaren Klasse (Anlegezeit + Zeitpunkt → dran oder nicht) | Zeitfälle (vor 14, 14–16, ab 16, Sonntag, 5 Tage) sind fehleranfällig; mit vorgegebener Uhrzeit sauber testbar | 2026-11-06 |
| Versand über die bestehenden Zammad-Versandteile (Token des Anlegers, Signatur, Nachfrage nach Abbruch) | Gleiches Verhalten und gleiche Sicherungen wie PROJ-19; keine zweite Versandlogik | 2026-11-06 |
| Antwort direkt aus der Vorlage ohne Antwortvorschlag | Es gibt keine Analyse dahinter; wie „Direkt antworten“ (PROJ-69) | 2026-11-06 |
| Spalten am ToDo für Haken, Sprache, Stand, Zähler und Versanddaten; Prüfprotokoll als eigene Tabelle | Die Anzeige und die Stichprobe brauchen den Verlauf in der App; der Inhalt eines Vorschlags wird nach der Aufbewahrungsfrist gelöscht | 2026-11-06 |
| Gesendeter Text und Sendungsnummern am ToDo verschlüsselt | Enthalten Kundendaten, wie die übrigen ToDo-Texte (PROJ-70) | 2026-11-06 |
| Atomare Übernahme je ToDo vor dem Senden | Zwei Läufe oder Neustarts dürfen nie zweimal an den Kunden senden | 2026-11-06 |
| Keine Änderung an Policies, Middleware oder neuen Paketen | Kein neuer Zugriffsweg: Läufe starten im Zeitplaner, die Oberfläche bleibt wie PROJ-70 | 2026-11-06 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

Backend nötig: ja (Datenbank, geplante Läufe, Zugriff auf EOCS und Zammad). Keine neuen Pakete.

### A) Bausteine (Übersicht)
```
Anlegen des ToDos (vorhanden, PROJ-70)
+-- Ergebnisseite: Sendeformular, ToDo-Zeile „Sendungsnummer nachreichen“
|   +-- NEU: Haken „Mail bei Verfügbarkeit der Sendungsnummer automatisiert versenden“ (vorab gesetzt; abgewählt + Hinweis bei Sprache nicht Deutsch / unbekannt)
+-- Dialog „ToDo anlegen / bearbeiten“ (ToDo-Übersicht)
|   +-- NEU: Feld „Reklamations- oder Bestellnummer“ und derselbe Haken (nur mit Nummer sichtbar)

Geplanter Lauf (NEU, Mo–Sa, 4 feste Zeiten)
+-- Auswahl: welche ToDos sind zu diesem Zeitpunkt dran (Zeitplan, 5-Tage-Grenze)
+-- Prüfung je ToDo
|   +-- Sendungen der Reklamation aus EOCS holen (vorhanden, PROJ-70)
|   +-- Entscheidung: senden / „Bitte prüfen“ / weiter warten / Fehler zählen
|   +-- Senden: Antwort aus Vorlage, im Namen des Mitarbeiters, interne Notiz, Ticket schließen, ToDo erledigen
+-- Protokoll je Prüfung (Verlauf des ToDos)

Anzeige (vorhanden, erweitert)
+-- ToDo-Zeile: Stand „nächste Prüfung …“, „automatisch gesendet …“, „Bitte prüfen: Grund“, „fehlt seit 5 Tagen“
+-- Verlauf je ToDo (Prüfungen, Ergebnis)
+-- „Bitte prüfen“ öffnet die vorbereitete Antwort (vorhandener Weg aus PROJ-70)
```

### B) Daten (einfach beschrieben)
Am vorhandenen ToDo kommen Angaben dazu:
- **Automatisch senden:** ja/nein (Haken).
- **Sprache des Kunden** zum Zeitpunkt des Anlegens (aus dem Antwortvorschlag; gespeichert, weil der Inhalt des Vorschlags nach der Aufbewahrungsfrist geleert wird).
- **Stand der Automatik:** wartet / gesendet / bitte prüfen / abgelaufen (5 Tage) / beendet (von Hand erledigt).
- **Grund** bei „Bitte prüfen“ (Text), Zahl der Prüfungen, Zahl der technischen Fehler in Folge, Zeitpunkt der letzten Prüfung.
- **Versand:** Zeitpunkt, die gesendeten Sendungsnummern und der gesendete Text (verschlüsselt, wie die anderen ToDo-Texte).
- Die **Reklamations- oder Bestellnummer** liegt schon am ToDo (PROJ-70).

Neu ist ein **Prüfprotokoll**: je Prüfung eine Zeile mit ToDo, Zeitpunkt, Ergebnis (noch nichts / gesendet / bitte prüfen / Fehler) und kurzer Notiz. Es wird zusammen mit dem ToDo gelöscht und dient dem Verlauf in der Anzeige.

Die Einstellung (Vorlagentext) existiert bereits (PROJ-70, Einstellungen > Mail-Anzeige). Alles Neue liegt in der Datenbank der App; in Zammad und EOCS wird nichts gespeichert außer der gesendeten Antwort samt interner Notiz.

### C) Ablauf der Prüfung (Entscheidungsfolge)
1. Der Lauf startet zu 11:26, 16:07, 16:16 und 18:34 (nur Mo–Sa, Zeitzone Berlin).
2. Dran ist ein ToDo nur, wenn: Haken gesetzt, noch offen, Stand „wartet“, Nummer vorhanden, und der Zeitplan diesen Zeitpunkt für sein Anlegedatum vorsieht (16:07 nur für ToDos von vor 14:00, 18:34 nur für ToDos von vor 16:00, 11:26 und 16:16 erst ab dem Folgetag). Sind seit dem Anlegen 5 Tage ohne Sonntage vergangen, wird nicht mehr geprüft und der Stand auf „abgelaufen“ gesetzt.
3. Sendungen der Reklamation aus EOCS holen. Steht am ToDo nur die Originalbestellung, wird die neueste Reklamation gesucht (nie die Sendungen der Originalbestellung).
4. Keine Sendungsnummer → Protokoll „noch nichts“, nächster Zeitpunkt.
5. Sendungsnummer da → Schutzregeln in dieser Reihenfolge: Sprache Deutsch, jede Nummer mit Dienstleister, Zugang des Mitarbeiters gültig, Text ohne offene Platzhalter. Fehlt etwas → „Bitte prüfen“ mit Grund, die Antwort wird wie beim bisherigen „Erledigen mit Kundenbenachrichtigung“ vorbereitet.
6. Sonst senden: ToDo wird atomar „in Versand“ gesetzt (nur ein Lauf gewinnt), Antwort an die Kundenadresse des Tickets (bei Zusammenführung das Zielticket), interne Notiz, Ticket schließen, ToDo erledigt mit Kennzeichen „automatisch“.
7. Technischer Fehler (EOCS, Zammad) → Fehler zählen, beim dritten in Folge „Bitte prüfen: technischer Fehler“. Kam die Antwort in Zammad schon an, gilt das ToDo als erledigt, auch wenn Notiz oder Schließen scheitern (der Schritt wird am ToDo vermerkt).

Doppelversand ist ausgeschlossen: atomare Übernahme je ToDo plus Nachfrage in Zammad, ob die Antwort nach einem Abbruch schon angekommen ist (wie beim Senden in PROJ-19).

### D) Technische Entscheidungen (für das Produktteam)
- **Geplante Läufe statt einzelner Termine je ToDo:** Die App hat schon einen Zeitplaner (PROJ-11, 60, 64, 66). Vier feste Läufe passen zum Wunsch nach festen, ungeraden Zeiten und erzeugen keine zusätzliche Infrastruktur.
- **Zeitplan als kleine, getestete Regel**, nicht als Termin im ToDo: Der Lauf entscheidet aus Anlegezeit, heutigem Datum und Uhrzeit, ob ein ToDo dran ist. Das ist leicht zu testen (feste Uhrzeiten vorgeben) und ändert sich zentral.
- **Versand über den vorhandenen Weg zu Zammad** (PROJ-19), nicht über einen neuen: gleicher Zugang je Mitarbeiter, gleiche Signatur, gleiche Nachfrage bei Abbruch. Der Unterschied: kein Antwortvorschlag dahinter, daher wird die Antwort direkt aus der Vorlage zusammengesetzt (ein „Direkt antworten“ wie PROJ-69).
- **Sprache am ToDo speichern**, weil sie später nicht mehr sicher ermittelbar ist (Aufbewahrungsfrist der Vorschläge).
- **Reklamation über die vorhandene EOCS-Abfrage** (PROJ-70, Sendungen mit der Reklamationssuche): kein neuer Zugriff.
- **Eigenes Prüfprotokoll** statt Logdatei: Die Anzeige und die Stichprobe des Auftraggebers brauchen Verlauf je ToDo in der App.
- **Autorisierung:** wie PROJ-70 (feste Namensliste, kein Login); der Versand verwendet den Token des Anlegers. Keine Änderung an Policies oder Middleware.

### E) Abhängigkeiten (Pakete)
Keine neuen. Genutzt werden vorhandene Teile: Zeitplaner, EOCS-Zugriff, Zammad-Versand, Einstellungen mit Verlauf, ToDo-Verwaltung.

### F) Offene Punkte aus dem Spec, hier entschieden
- Dialog „ToDo anlegen“ der Übersicht bekommt ein Feld „Reklamations- oder Bestellnummer“; der Haken erscheint nur, wenn eine Nummer eingetragen ist. Eine dort angelegte Bestellung ohne Sprache gilt als „Sprache unbekannt“ und sendet nicht automatisch (Haken abgewählt, Hinweis).
- Sonntags angelegte ToDos: erste Prüfung Montag 11:26.
- Die 5 Tage zählen Kalendertage ohne Sonntage ab dem Anlegetag; am sechsten Prüftag wird nicht mehr geprüft.


## Implementation Notes (Frontend, 2026-11-06)
- **Sendeformular** (`components/analysis/reply-editor.blade.php`): Bei „Sendungsnummer nachreichen“ erscheint der Haken „Mail bei Verfügbarkeit der Sendungsnummer automatisiert versenden“ mit der Nummer, vorab gesetzt, wenn der Antwortentwurf Deutsch ist; sonst abgewählt, gesperrt und mit Hinweis. Das Formular schickt `reference` und `auto_send` je ToDo-Zeile.
- **Dialog** (`components/todo/dialog.blade.php`): Feld „Reklamations- oder Bestellnummer“ und derselbe Haken (nur mit Nummer sichtbar). Ohne bekannte Sprache ist der Haken gesperrt (Dialog kennt die Sprache nur beim Bearbeiten, sobald das Backend sie liefert).
- **Anzeige** (`components/todo/row.blade.php`): Markierungen „Automatische Prüfung: nächste …“, „Bitte prüfen: Grund“, „Sendungsnummer fehlt seit 5 Tagen“, „Automatisch gesendet am … · Nummer“. Sie lesen Felder, die das Backend liefert (`auto_state`, `auto_reason`, `auto_next_label`, `auto_sent_at`, `auto_tracking`, `auto_send`, `customer_language`); bis dahin bleiben sie unsichtbar.

## Implementation Notes (Backend, 2026-11-06)
- **Daten:** Migrationen `add_tracking_automation_to_ticket_todos_table` (Spalten `auto_*`, `customer_language`; Sendungsnummern und gesendeter Text verschlüsselt) und `create_todo_auto_checks_table` (Prüfprotokoll, Modell `TodoAutoCheck`). `TicketTodo` lädt die Prüfungen mit und liefert `auto_next_label`.
- **Eingaben:** `TodoRules` nimmt `reference` und `auto_send` an. `TodoService::create` setzt die Automatik nur bei gesetztem Haken, vorhandener Nummer und deutscher Sprache; die Sprache kommt aus dem Antwortvorschlag (`createFromReply`), im Dialog ist sie unbekannt, dort wird nie automatisch gesendet. Beim Bearbeiten startet bzw. stoppt der Haken die Prüfung; „Bitte prüfen“ bleibt bei gleichbleibendem Haken erhalten.
- **Zeitplan:** `App\Todo\TrackingSchedule` (feste Zeiten, Mo–Sa, 5 Tage ohne Sonntage, Anzeige „heute 18:34“). Befehl `todos:check-tracking` (optional `--slot=`), geplant in `routes/console.php` zu 11:26, 16:07, 16:16 und 18:34 (Berliner Zeit, `withoutOverlapping`).
- **Prüfung und Versand:** `App\Todo\TrackingNotifier`: Sendungen der Reklamation aus EOCS (bei nur genannter Bestellung die neueste Reklamation, nie die der Originalbestellung); Schutzregeln (Sprache, Dienstleister, Zugang des Mitarbeiters, Text, offene Platzhalter, Antwortadresse) führen zu „Bitte prüfen“ mit Grund und eingesetzter Sendungsnummer im Entwurf; sonst Versand über `ZammadReplySender` im Namen des Anlegers mit Signatur, Status „closed“, interne Notiz, ToDo erledigt von „Automatik“. Zusammengeführte Tickets werden bis zum Zielticket verfolgt. Atomare Übernahme (`auto_state = sending`) und Wiederaufnahme nach Abbruch mit Nachfrage in Zammad. Drei technische Fehler in Folge ergeben „Bitte prüfen“.
- **Anzeige:** `components/todo/row.blade.php` zeigt Stand, Grund, Verlauf der Prüfungen und den gesendeten Text.
- **Tests:** `tests/Feature/PROJ-77-TrackingNumberTest.php` (Zeitplan, Versand, mehrere Sendungen, Bitte-prüfen-Fälle, Fehlerzähler, Doppelversand, Befehl, Anzeige) sowie Ergänzungen in `PROJ-70-TicketTodosTest.php` und `PROJ-70-ReplyTodosTest.php`.
- **Nicht getestet:** Zusammenführung eines Tickets (Verfolgen der Kette) und der Ablauf gegen das echte EOCS/Zammad; beides gehört in die Abnahme (`/qa`).

## QA Test Results

**Geprüft am:** 2026-11-06 · **Ergebnis:** bereit für den Einsatz (keine Critical/High-Bugs)

### Wie geprüft
Automatisierte Tests gegen simulierte Zammad- und EOCS-Antworten; Prüfung des Codes gegen jedes Kriterium und jeden Randfall des Specs. **Nicht möglich in dieser Umgebung:** Test im Browser (Chrome, Firefox, Safari, 375/768/1440 px) und gegen das echte EOCS/Zammad; beides bleibt für die ersten echten Fälle.

### Akzeptanzkriterien
| Bereich | Ergebnis |
|---|---|
| ToDo und Haken (Standard gesetzt, bei fremder/unbekannter Sprache abgewählt und gesperrt, ohne Nummer keine Automatik, kein ToDo ohne gesendete Antwort) | bestanden (Tests in `PROJ-70-ReplyTodosTest`, `PROJ-70-TicketTodosTest`, Ansicht im Frontend) |
| Prüfzeitplan (vor 14:00, 14–16 Uhr, ab 16 Uhr, Sonntag, Samstag, 5 Tage, nächste Prüfung) | bestanden (`PROJ-77-TrackingNumberTest`, Block „the schedule“) |
| Prüfung (nur Reklamation, Bestellung → neueste Reklamation, noch keine Nummer, abgelaufen, von Hand erledigt) | bestanden |
| Senden (Nummer und Dienstleister, mehrere Sendungen ohne Doppelte, trotz neuer Kundennachricht, Notiz, Ticket geschlossen, ToDo „Automatik“, nie doppelt) | bestanden |
| Bitte prüfen (Sprache, Dienstleister, offene Platzhalter, leerer Text, fehlender Zugang, Wiederaufnahme der Bearbeitung) | bestanden |
| Technische Fehler (Zähler, dritter Fehler → Bitte prüfen) | bestanden |
| Sichtbarkeit (Stand, Grund, Verlauf, gesendeter Text) und Vorlage (Platzhalter) | bestanden |
| Zusammengeführtes Ticket folgt dem Zielticket | **im Code umgesetzt, nicht automatisch getestet** (offen für die Abnahme mit einem echten Fall) |
| Nur Notiz/Schließen schlägt nach dem Versand fehl → ToDo bleibt erledigt | im Code umgesetzt (Protokoll „note_failed“), nicht gesondert getestet |

### Sicherheit (Red Team)
- Ausgaben maskiert (Test mit Skript-Text im gesendeten Text), Sendungsnummern und gesendeter Text verschlüsselt in der Datenbank (getestet).
- Keine neuen Routen und Formulare; Eingaben laufen über die vorhandenen Form Requests (`reference` max. 120, `auto_send` boolesch). Kein neues Geheimnis; der Token des Mitarbeiters bleibt im Header der Anfrage.
- Doppelversand: atomare Übernahme je ToDo plus Nachfrage in Zammad (getestet: zweiter Lauf sendet nicht noch einmal).
- **Hinweis (Entwurfsentscheidung, keine Neuerung):** Ohne Login kann jeder im Netz einen Namen wählen und ToDos bearbeiten. Mit der Automatik kann so auch der Antworttext eines fremden ToDos vor dem Versand geändert werden. Das gilt schon für das Senden aus der App; erst PROJ-15 (Anmeldung) schließt es.

### Regression
Gesamtlauf der Tests: 2263 Tests, 6 rot. Die sechs betreffen nicht PROJ-77: zwei Tests zu PROJ-56 (Sichtbarkeit der Meldungen, bekannter offener Punkt), je einer zu PROJ-10 (Hinweis „bearbeitet von …“), PROJ-34 (Skript auf der Angebotsseite) und zwei zur Wissensauswahl (`POLICY-009`). Sie gehen vermutlich auf frühere offene Änderungen zurück (Entschlackung PROJ-74/76, Wissensdurchsicht PROJ-75); nicht gegen einen älteren Stand geprüft.

### Bugs
Keine Critical, High oder Medium gefunden.

### Offen für die ersten echten Fälle
- Liefert EOCS bei der Reklamationssuche mit `include=shipments` die Sendungen? (Bestellabfrage nutzt dasselbe; sonst zeigt der Kopfbereich „Noch nicht versandt“ und die Automatik findet nichts.)
- Läuft der Zeitplaner auf dem Server jede Minute (bestehende Einrichtung für PROJ-66)? Die vier neuen Läufe sind in `routes/console.php`.
- Stichprobe über die erledigten ToDos (Verlauf und gesendeter Text sind sichtbar).

### Entscheidung
**Bereit für den Einsatz.** Status **Approved**.

## Deployment
_To be added by /deploy_
