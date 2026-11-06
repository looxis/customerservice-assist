# PROJ-77: Sendungsnummer automatisch nachreichen

## Status: Planned
**Created:** 2026-11-06
**Last Updated:** 2026-11-06

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
1. Der Mitarbeiter legt das ToDo „Sendungsnummer nachreichen“ an (PROJ-70, auf der Ergebnisseite oder im Dialog). Der Haken „Sendungsnummer automatisch senden“ ist vorab gesetzt. Die Reklamationsnummer steht am ToDo.
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
- [ ] Angenommen der Mitarbeiter wählt beim Anlegen „Sendungsnummer nachreichen“, wenn das ToDo angelegt wird, dann ist der Haken „Sendungsnummer automatisch senden“ vorab gesetzt und er kann ihn abwählen.
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
- [ ] Welche Zeiten gelten, wenn ein ToDo am Sonntag angelegt wird? Annahme: wie „ab 16:00“, erste Prüfung am Montag um 11:26.
- [ ] Wo steht der Haken „automatisch senden“ im Dialog „ToDo anlegen“ der ToDo-Übersicht, wenn dort kein Fall bekannt ist (keine Sprache, keine Reklamationsnummer)? Annahme: Haken nur sichtbar, wenn eine Nummer eingetragen ist; der Dialog bekommt dafür ein Feld „Reklamations- oder Bestellnummer“.
- [ ] Wann endet die Prüfung bei Wochenenden genau (5 Kalendertage ohne Sonntage ab dem Anlegen)? Annahme wie beschrieben; bei Bedarf im Architekturschritt konkretisieren.
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

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)
_To be added by /architecture_

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
