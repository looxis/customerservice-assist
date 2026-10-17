# PROJ-52: Zusammengehörige Tickets

## Status: Approved
**Created:** 2026-10-17
**Last Updated:** 2026-10-17

## Dependencies
- Requires: PROJ-6 (Zammad-Ticket laden), PROJ-9 (Analyse), PROJ-11 (Protokoll), PROJ-39 (Fortsetzung), PROJ-53 (Reklamationsnummer, Originalnummer).
- Verwandt: PROJ-29/PROJ-19 (Ticketbearbeitung: später Verknüpfen aus der App), PROJ-48 (Verlauf).

## Ausgangslage
Ein Ticket steht oft nicht für sich: Ein Kunde antwortet auf eine E-Mail zu einer Reklamation und schreibt, er wolle sein Geld zurück, obwohl er vorher Ersatz verlangt hat. Ohne den Vorverlauf wäre eine Analyse wenig sinnvoll. Die Mitarbeiterin sucht das Ticket heute in Zammad und verknüpft beide dort. Die App kennt die Verknüpfung nicht und zeigt keine anderen Tickets desselben Falls.

## User Stories
- Als Mitarbeiterin möchte ich, dass die App in Zammad verknüpfte Tickets automatisch als Vorverlauf berücksichtigt.
- Als Mitarbeiterin möchte ich Tickets zur selben Bestellung oder vom selben Kunden vorgeschlagen bekommen und mit einem Klick dazunehmen, ohne in Zammad zu suchen.
- Als Mitarbeiterin möchte ich eine Ticketnummer eintippen können, wenn die App ein Ticket nicht findet.
- Als KI-Analyse möchte ich den Vorverlauf sehen, damit Zusagen, Wünsche und Fakten früherer Tickets in den Vorschlag einfließen.
- Als Admin möchte ich nachvollziehen, welche Tickets in welchem Stand in einen Vorschlag einflossen.

## Out of Scope
- Tickets in Zammad verknüpfen oder zusammenführen (Schreibzugriff; später PROJ-29/19).
- Prüfen, ob die Kunden gleich sind (bei Amazon laufen die Mails über eine Weiterleitung).
- Automatisches Zusammenfassen des Vorverlaufs per KI; lange Verläufe werden sichtbar begrenzt.
- Zurückgespulte Testmodus-Tickets (keine Vorschläge im Testmodus).
- Fragen ohne Ticket (kein Zammad-Ticket, nichts zu suchen).

## Quellen und Auswahl
Im Formular „Vorschlag erstellen“ erscheint der Block **„Zusammengehörige Tickets“** mit drei Gruppen. Jedes Ticket zeigt Nummer (Link zur App), Titel, Status, Datum und Zahl der Nachrichten.
| Gruppe | Quelle | vorausgewählt |
|---|---|---|
| In Zammad verknüpft | Verknüpfungen des Tickets (jede Richtung) | ja |
| Tickets zur Bestellung | Zammad-Suche nach der Originalnummer der Bestellung(en) des Tickets (auch aus `Rn-`-Nummern) | nur wenn das Ticket eine Reklamationsnummer (PROJ-53) nennt |
| Weitere Tickets dieses Kunden | Zammad-Suche nach dem Kunden, letzte 90 Tage, höchstens die zehn neuesten | nein |
Dazu ein Feld **„Ticketnummer ergänzen“** (Rückfall). Ein Ticket erscheint nur einmal, in der stärksten Gruppe. Das aktuelle Ticket und ein bereits gewähltes Ticket werden nicht vorgeschlagen. Die Auswahl gilt für diesen Vorschlag; eine frühere Wahl des Tickets ist vorbelegt. Höchstens 5 Tickets.

## Was die KI bekommt
- Ein Abschnitt **„Früherer Verlauf (zusammengehörige Tickets)“** vor dem aktuellen Ticket: je Ticket Kopf (Nummer, Titel, Status, Zeitraum, Quelle) und die Nachrichten mit Absender (Kunde/wir/intern) und Datum, vollständig inklusive unserer Antworten.
- Eine Regel für die KI (eigener Prompt, auf der Seite „Prompts und Modell“ änderbar): Der Kundenwunsch im aktuellen Ticket zählt; bereits Zugesagtes und Gesendetes nicht ohne Grund widersprechen; im Ergebnis nennen, dass Vorverlauf einfloss; widersprechen sich Vorverlauf und aktuelle Angabe, den Widerspruch nennen.
- **Begrenzung:** höchstens 16.000 Zeichen insgesamt; sonst werden die ältesten Nachrichten je Ticket ausgelassen (die neueste bleibt immer), mit sichtbarem Hinweis „n ältere Nachrichten aus Ticket X ausgelassen“ im Ergebnis. Kontaktdaten werden wie überall ersetzt.
- Der Vorverlauf ist ein Snapshot: Eine Fortsetzung des Falls (PROJ-39) übernimmt ihn ohne neuen Abruf; „Erneut analysieren“ holt ihn neu.

## Acceptance Criteria
- [ ] Angenommen das Ticket ist in Zammad mit einem anderen verknüpft, wenn die Ticketseite erscheint, dann steht das verknüpfte Ticket unter „In Zammad verknüpft“ und ist vorausgewählt.
- [ ] Angenommen das Ticket nennt eine Bestellnummer, wenn die Seite erscheint, dann stehen andere Tickets mit dieser Nummer unter „Tickets zur Bestellung“; bei einer Reklamationsnummer sind sie vorausgewählt, sonst nicht.
- [ ] Angenommen das Ticket hat einen Kunden, dann stehen andere Tickets dieses Kunden der letzten 90 Tage (höchstens zehn) unter „Weitere Tickets dieses Kunden“, nicht vorausgewählt.
- [ ] Angenommen ein Ticket kommt in mehreren Quellen vor, dann steht es einmal, in der stärksten Gruppe.
- [ ] Angenommen ich trage eine Ticketnummer ein und erstelle den Vorschlag, dann fließt das Ticket ein; eine unbekannte Nummer wird mit einem Hinweis übersprungen.
- [ ] Angenommen Tickets sind ausgewählt, wenn der Vorschlag entsteht, dann enthält der Prompt den Abschnitt „Früherer Verlauf“ mit allen Nachrichten (auch unseren Antworten) und der Zusatzregel, und das Ergebnis zeigt, welche Tickets einflossen.
- [ ] Angenommen der Vorverlauf ist zu lang, dann werden die ältesten Nachrichten ausgelassen, die neueste je Ticket bleibt, und das Ergebnis nennt die Auslassung.
- [ ] Angenommen ich setze einen Fall fort, dann gilt derselbe Vorverlauf ohne neuen Zammad-Abruf; bei „Erneut analysieren“ wird neu gelesen.
- [ ] Angenommen Zammad antwortet nicht oder die Suche scheitert, dann bleibt das Formular benutzbar, es steht ein Hinweis, und der Vorschlag entsteht ohne Vorverlauf.
- [ ] Angenommen die Auswahl ist leer, dann verhält sich die App wie bisher.
- [ ] Angenommen der Testmodus ist aktiv und das Ticket zurückgespult, dann gibt es keine Vorschläge.
- [ ] Angenommen mehr als 5 Tickets, dann wird abgelehnt.

## Edge Cases
- Ein verknüpftes Ticket ist gelöscht oder nicht lesbar: übersprungen mit Hinweis.
- Das verknüpfte Ticket ist das aktuelle (Selbstverweis): nicht vorgeschlagen.
- Ticket mit hunderten Nachrichten: die Begrenzung greift, die neuesten bleiben.
- Das Ticket hat keinen Kunden oder keine Bestellnummer: die jeweilige Gruppe entfällt.
- Eine Verknüpfung in Zammad kommt nach dem Vorschlag dazu: gilt erst beim erneuten Erstellen.
- Dasselbe Ticket in „verknüpft“ und „zur Bestellung“: einmal, vorausgewählt.

## Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Drei Quellen (verknüpft, Bestellnummer, Kunde) plus Handeingabe | Verknüpfung ist sicher, Bestellnummer sehr gut, Kunde nur ein Hinweis; Handeingabe für den Rest | 2026-10-17 |
| Vorauswahl nur für verknüpfte und, bei Reklamationsnummer, für Treffer zur Bestellung | `Rn-` heißt Folgevorgang einer Reklamation; sonst entscheidet der Mensch | 2026-10-17 |
| Kein Schreibzugriff auf Zammad | Verknüpfen aus der App gehört zur Ticketbearbeitung (PROJ-29) | 2026-10-17 |
| Begrenzung mit sichtbarer Auslassung statt stillem Abschneiden | Die Mitarbeiterin soll wissen, was die KI nicht gesehen hat | 2026-10-17 |
| Eigener Prompt-Zusatz, änderbar auf der Prompt-Seite | Wie die anderen Zusätze (PROJ-31) | 2026-10-17 |

## Tech Design (Solution Architect)

### Backend nötig?
Ja, ohne neue Tabellen und Pakete: lesende Zammad-Abfragen (Verknüpfungen, Suche), Ablage des Vorverlaufs im Protokoll der Analyse.

### A) Bausteine
```
Formular „Vorschlag erstellen“
+-- Block „Zusammengehörige Tickets“ (neu, vor Kundengruppe und Produkt)
    +-- Gruppe „In Zammad verknüpft“ (Haken je Ticket)
    +-- Gruppe „Tickets zur Bestellung“
    +-- Gruppe „Weitere Tickets dieses Kunden“
    +-- Feld „Ticketnummer ergänzen“
Ergebnis
+-- Abschnitt „Zusammengehörige Tickets“ (Quellen: welche Tickets, Stand, Auslassungen)
```
### B) Datenmodell
`content.related`: je Ticket Nummer, Titel, Status, Quelle, Zeitraum, Zahl der Nachrichten, Zahl der ausgelassenen; dazu der gesendete Abschnitt als Text. Im Formular-Protokoll (`inputs`) stehen die gewählten Nummern.
### C) Technische Entscheidungen
- Ein Baustein `RelatedTicketFinder` fragt Zammad lesend (Verknüpfungen, Suche nach Nummer, Suche nach Kunde) und liefert Kandidaten mit Quelle; Fehler führen nur zu einem Hinweis.
- Ein Baustein baut den Abschnitt aus den Tickets (vollständiger Verlauf mit Absender und Datum), mit Zeichenbudget und Auslassungsliste.
- Neuer Prompt-Zusatz `analysis-related` (Teil der Seite „Prompts und Modell“).
- Fortsetzungen übernehmen den gespeicherten Abschnitt.
### D) Abhängigkeiten
Keine neuen Pakete.

## Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Abfragen über die Zammad-Schnittstelle `links` und `tickets/search` | Mit dem vorhandenen Zugang geprüft (2026-10-17) | 2026-10-17 |
| Abschnitt als Snapshot im Protokoll, Fortsetzung ohne Neuabruf | Gleicher Datenstand innerhalb eines Falls | 2026-10-17 |
| Zeichenbudget 16.000 mit Auslassung der ältesten Nachrichten | Prompt bleibt begrenzt; die neueste Nachricht je Ticket zählt am meisten | 2026-10-17 |

## Implementation Notes (2026-10-17)
- **Gebaut:** `ZammadClient::linkedTickets()` und `searchTickets()` (nur lesend), `RelatedTicketFinder` (verknüpft, zur Bestellung, weitere des Kunden der letzten 90 Tage; höchstens 10 je Quelle; Fehler führen nur zu einem Hinweis), `RelatedContextBuilder` und `RelatedSection` (Abschnitt „Früherer Verlauf (zusammengehörige Tickets)“, Budget je Ticket, älteste Nachrichten sichtbar ausgelassen, neueste je Ticket bleibt), Formularblock `components/analysis/related-tickets.blade.php`, Abschnitt im Ergebnis, Prompt-Zusatz `analysis-related` (Seite „Prompts und Modell“, acht Prompts).
- **Vorauswahl:** verknüpfte Tickets immer; Tickets zur Bestellung nur, wenn das Ticket eine Reklamationsnummer (PROJ-53) nennt; weitere Tickets des Kunden nie. Nicht angeboten bei zurückgespulten Tickets (Testmodus) und Tickets ohne Zammad-ID.
- **Fortsetzung:** übernimmt den gespeicherten Abschnitt aus der Vorgängeranalyse (`content.related`), kein neuer Abruf.
- **Konfiguration:** `config('analysis.related_tickets')` mit `max` 5 und Zeichenbudget 16.000 (Umgebungsvariable `ANALYSIS_RELATED_TICKETS_CHARACTERS`, nur hier dokumentiert; `.env.example` bewusst nicht angefasst).
- **Tests:** `tests/Feature/PROJ-52-RelatedTicketsTest.php` (18 Tests); `fakeZammad` in `tests/Pest.php` beantwortet `links` standardmäßig leer. Gesamtsuite: 1656 Tests, 1654 grün, 2 übersprungen.
- **Geprüft am echten Ticket 2139944 (nur lesend):** verknüpft 2138745 (vorausgewählt), zur Bestellung 2138529 (vorausgewählt wegen Reklamationsnummer). Browser-Test und `/qa` stehen noch aus.
- **Nachbesserung (2026-10-17): Verlauf der Tickets auf der Ticketseite.** Unter dem Verlauf zeigt der Abschnitt „Zusammengehörige Tickets“ (`components/ticket/related-threads.blade.php`) die Tickets, die der angezeigte Vorschlag verwendet hat: je Ticket eingeklappt mit Nummer, Titel, Status, Datum, Zahl der Nachrichten und Link „Ticket öffnen“, aufgeklappt alle Nachrichten wie im Verlauf. Die Tickets werden beim Öffnen der Seite frisch und nur lesend aus Zammad gelesen (`TicketController::relatedThreads`); nicht lesbare werden weggelassen. Ohne Auswahl in der Analyse erscheint der Abschnitt nicht.

## QA Test Results (2026-10-17)
**Ergebnis: bereit (keine Critical-/High-Fehler).**

- **Akzeptanzkriterien und Randfälle:** Alle Akzeptanzkriterien durch Tests abgedeckt (20 Tests). Beim QA ergänzt: persönliche Daten (E-Mail, Telefon) in den früheren Tickets werden wie der übrige Text pseudonymisiert. Live gegen Ticket 2139944 geprüft: Seite 200, Block mit Haken vorhanden, Reklamation hervorgehoben, ungültige Nummern 404.
- **Sicherheit:** Nur lesende Zammad-Zugriffe; Ticketnummern kommen validiert (nur Ziffern, höchstens 5), eine nicht angebotene Quelle kann nicht eingeschleust werden (Test); Titel und Nachrichten fremder Tickets werden escaped bzw. durch den Nachrichten-Sanitizer geführt.
- **Regression:** Gesamtsuite 1659 Tests, 1657 grün, 2 übersprungen.
- **Nicht durchgeführt:** eigener Browser-Durchgang (Chrome/Firefox/Safari, 375/768/1440 px). Es gab keinen Browser in der QA-Umgebung; ein schneller Sichttest der Oberfläche liegt vom Anwender vor (2026-10-17).
- **Beobachtungen (Low):** Die Ticketseite braucht mit den neuen Zammad-Abfragen für zusammengehörige Tickets rund 4 Sekunden (Ticket 2139944, mehrere Zammad-Aufrufe nacheinander); bei Bedarf später parallelisieren oder Teile nachladen. Ohne gewählten Namen sind Ticketseiten und Einstellungen ansehbar; das entspricht dem bestehenden Verhalten (Auswahl erzwingt nur Aktionen).
