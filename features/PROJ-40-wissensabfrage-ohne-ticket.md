# PROJ-40: Wissensabfrage ohne Ticket

## Status: Planned
**Created:** 2026-10-13
**Last Updated:** 2026-10-13

## Dependencies
- Requires: PROJ-9 (Fallanalyse, Ergebnisstruktur), PROJ-36/PROJ-37 (gezielter Wissensabruf und Router), PROJ-39 (Fortsetzung, interne Klärungen, Antwort nur wenn sinnvoll), PROJ-11 (Protokoll), PROJ-5 (Nutzerauswahl).
- Verwandt: PROJ-12 (Feedback, Wissenslücke melden), PROJ-14 (Analysen-Übersicht), PROJ-10 (Ergebnisansicht).
- **Nimmt PROJ-16 (Tickettext manuell einfügen) auf:** Wer einen Kundentext von Hand einfügen will, nutzt dieselbe Eingabe. PROJ-16 gilt damit als aufgegangen (wie PROJ-8 in PROJ-9).

## Ausgangslage
Heute gibt es die App nur zu einem Zammad-Ticket. Mitarbeiter haben aber auch Fragen im laufenden Betrieb oder während eines Anrufs („Darf ich …?“, „Was sage ich dem Kunden, der gerade anruft?“), zu denen es kein Ticket gibt oder noch keins gibt. Das Wissen der Knowledge Base soll auch dort direkt ankommen. Außerdem passt der Begriff „Analyse“ nicht zu dem, was die Mitarbeiter eigentlich fragen: „Was ist zu tun?“ und „Was soll ich antworten?“.

## User Stories
- Als Aushilfe möchte ich während eines Anrufs meine Frage eintippen und eine begründete Antwort aus dem Unternehmenswissen bekommen, damit ich dem Kunden sofort richtig weiterhelfen kann.
- Als Stammkraft möchte ich eine Frage zu einer Regel oder Befugnis stellen, ohne erst ein Ticket anzulegen, damit ich keine erfahrenen Kollegen unterbrechen muss.
- Als Mitarbeiter möchte ich einen Kundentext, der nicht (mehr) in Zammad steht, einfügen und dazu einen Vorschlag bekommen, damit ich auch ohne Ticket arbeiten kann.
- Als Mitarbeiter möchte ich nach einem Anruf aus meiner Notiz eine Antwort-E-Mail an den Kunden formulieren lassen, damit ich die Zusage schriftlich nachreichen kann.
- Als Mitarbeiter möchte ich nachfragen oder eine interne Klärung eintragen und damit weitermachen, ohne alles neu zu schreiben.
- Als Entwickler/Admin möchte ich sehen, welche Fragen ohne Ticket gestellt wurden und wo Wissen fehlt, damit die Knowledge Base besser wird.

## Out of Scope
- Abruf von Zammad-Tickets oder Zusammenfassungen in diesem Weg (das Ticket bleibt Sache der Ticketanalyse).
- Bestelldaten aus EOCS (Bestellnummern im Text werden nicht nachgeschlagen); manuelle Bestelldaten wie im Ticketformular sind nicht vorgesehen.
- Freier Chat mit Gesprächsverlauf im Stil eines Chatbots; es gibt Nachfragen über die Fortsetzung, nicht mehr.
- Antwort direkt nach Zammad übergeben oder Ticket anlegen (PROJ-19, PROJ-29).
- Sprachaufnahme oder Transkription von Anrufen.
- Gemeinsamer, für alle sichtbarer Verlauf der Fragen.
- Automatisches Lernen aus den gestellten Fragen.

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Einstieg und Eingabe
- [ ] Angenommen ein Mitarbeiter hat seinen Namen gewählt, wenn er in der Seitenleiste „Frage stellen“ öffnet, dann sieht er ein Formular mit Textfeld (Pflicht), Kundengruppe (Standard „Noch unklar“), Produkten (optional) und der Option „Auch einen Antwortentwurf an den Kunden formulieren“ (Standard aus).
- [ ] Angenommen kein Name ist gewählt, wenn die Seite geöffnet oder abgeschickt wird, dann wird nichts ausgeführt und der Hinweis „Bitte wähle zuerst deinen Namen“ erscheint, die Eingabe bleibt erhalten.
- [ ] Angenommen das Textfeld ist leer oder länger als die zulässige Länge, wenn der Mitarbeiter abschickt, dann erscheint eine Fehlermeldung am Feld und die Eingabe bleibt erhalten.
- [ ] Angenommen neben dem Textfeld steht der Hinweis „Bitte nach Möglichkeit keine Kundendaten eintragen“, wenn der Mitarbeiter trotzdem Kontaktdaten einträgt, dann werden sie vor dem Senden an den KI-Anbieter durch Platzhalter ersetzt wie bei Tickets.
- [ ] Angenommen die Kundengruppe oder ein Produkt ist gewählt, wenn die Frage abgeschickt wird, dann legen beide den Geltungsbereich des Wissens fest wie bei der Ticketanalyse.

### Ergebnis
- [ ] Angenommen eine gültige Frage, wenn sie abgeschickt wird, dann ruft die App das Wissen gezielt ab (PROJ-36/37) und zeigt einen Vorschlag im selben Aufbau wie bei Tickets: Was ist zu tun, Befugnis, Begründung, Quellen, Wissenslücken, interne Schritte.
- [ ] Angenommen die Option „Antwortentwurf“ war nicht gewählt und die Frage richtet sich an den Mitarbeiter selbst, wenn der Vorschlag erscheint, dann gibt es keinen Antwortentwurf und keinen Editor, nur den Hinweis, wie er einen anfordern kann.
- [ ] Angenommen die Option „Antwortentwurf“ war gewählt, wenn der Vorschlag erscheint, dann enthält er einen Antwortentwurf in der Sprache des eingegebenen Kundentextes (sonst Deutsch), bearbeitbar und kopierbar wie bei Tickets.
- [ ] Angenommen eine blockierende interne Klärung, wenn der Vorschlag erscheint, dann zeigt die App wie bei PROJ-39 unter „Interne Abläufe“ die Klärung mit Auswahl oder freier Eingabe und noch keinen Entwurf.
- [ ] Angenommen der Mitarbeiter gibt eine Klärung oder Nachfrage ein, wenn er fortsetzt, dann entsteht ein neuer Vorschlag mit dem bisherigen Text, den Klärungen und der Nachfrage.
- [ ] Angenommen im Wissen fehlt eine Regel, wenn der Vorschlag erscheint, dann steht sie unter „Wissenslücken“ mit „Lücke melden“, wie bisher.
- [ ] Angenommen der Vorschlag ist angezeigt, wenn der Mitarbeiter Feedback gibt oder eine Wissenslücke meldet, dann funktioniert das wie bei Tickets; ohne Ticketnummer wird die Frage als „ohne Ticket“ geführt.

### Verlauf und Protokoll
- [ ] Angenommen ein Mitarbeiter hat Fragen gestellt, wenn er „Frage stellen“ öffnet, dann sieht er seine letzten Fragen (Datum, Anfang des Textes) und kann sie öffnen, aber nicht die der anderen.
- [ ] Angenommen eine Frage wurde beantwortet, wenn ein Admin die Analysen-Übersicht öffnet, dann ist die Zeile als „Frage ohne Ticket“ gekennzeichnet und filterbar; Modell, Prompt-Version und Wissensstand sind protokolliert.
- [ ] Angenommen der Text einer Frage ist gespeichert, wenn jemand die Datenbank liest, dann ist er verschlüsselt wie die Inhalte der Analysen; nach 12 Monaten werden die Inhalte gelöscht, Kennzahlen bleiben.
- [ ] Angenommen eine Frage ohne Ticket, wenn die Ticket-Übersicht (PROJ-35) geöffnet wird, dann erscheint sie dort nicht, bei „Bearbeitete Tickets“ in der Seitenleiste zählt sie nicht.

### Begriffe in der Oberfläche
- [ ] Angenommen die Oberfläche der Ticketanalyse, wenn dieses Feature fertig ist, dann heißt das Ergebnis „Vorschlag“ statt „Analyse“ (Überschriften, Buttons, Verlauf, Hilfetexte) für beide Wege; im Code und im Protokoll bleibt „Analyse“.
- [ ] Angenommen die Seite „Über die App“, wenn dieses Feature fertig ist, dann beschreibt sie den neuen Weg und ersetzt „Analyse“ durch „Vorschlag“, wo es um die Oberfläche geht.

## Edge Cases
- Frage ist offensichtlich ein Ticketfall, der Mitarbeiter trägt eine Ticketnummer im Text ein: die App ruft kein Ticket ab; ein Hinweis verweist auf „Ticket analysieren“.
- Der Text enthält eine Bestellnummer: sie geht als Text an die KI, wird aber nicht in EOCS nachgeschlagen; der Vorschlag nennt sie als internen Schritt („in EOCS nachsehen“), wenn sie wichtig ist.
- Kein passendes Wissen für Kundengruppe und Produkt: wie bei Tickets Hinweis „Kein Wissen für diesen Fall“.
- Der KI-Anbieter ist nicht erreichbar oder liefert Unbrauchbares: Fehlermeldung wie bei Tickets mit „Erneut versuchen“, die Eingabe bleibt erhalten, es entsteht keine Zeile mit Inhalt.
- Doppelter Klick oder zweimaliges Absenden: nur eine Frage wird verarbeitet.
- Sehr kurze oder mehrdeutige Frage („Retoure?“): die App darf „unklar“ als Ergebnis liefern und nennt die nötige Rückfrage an den Mitarbeiter als interne Klärung.
- Frage mit eingefügter Kundennachricht in Fremdsprache: der Entwurf (falls gewünscht) kommt in der Sprache des Textes; der Vorschlag selbst auf Deutsch.
- Eine ältere Frage wird geöffnet, nachdem eine neue gestellt wurde: nur lesbar, Fortsetzung nur an der neuesten (wie bei Tickets).
- Mehrere Mitarbeiter stellen gleichzeitig Fragen: jeder sieht nur seine eigenen, Protokolle sind getrennt.

## Technical Requirements (optional)
- Wissensabruf, Prompt-Regeln, Befugnisse und Validierung bleiben dieselben wie bei Tickets; fachliche Regeln stehen nicht im Prompt.
- Datenschutz: Platzhalter für Kontaktdaten, verschlüsselte Speicherung, Aufbewahrung 12 Monate; für den KI-Anbieter gilt der bestehende Auftragsverarbeitungsvertrag.
- Die Antwortzeit entspricht der Ticketanalyse (ein KI-Aufruf plus Wissensabruf).
- Browser: Chrome, Firefox, Safari; Seite für Desktop und Tablet gedacht, mobil nutzbar.

## Open Questions
- [ ] Wie viele Fragen zeigt der persönliche Verlauf, und wo steht er (Seite „Frage stellen“ oder Seitenleiste)?
- [ ] Soll die Option „Antwortentwurf“ auch nachträglich per Button am Vorschlag angeboten werden, oder reicht die Nachfrage?
- [ ] Wie lang darf der Text sein (gleich wie das Kontextfeld oder länger für eingefügte Kundentexte)?
- [ ] Wie wird die Namensgebung der Navigation endgültig („Ticket analysieren“ bleibt oder wird z. B. „Ticket bearbeiten“)?
- [ ] Zählt die Frage ohne Ticket in einer eigenen Kennzahl (z. B. „Gestellte Fragen“) in der Seitenleiste?

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| PROJ-16 geht in PROJ-40 auf: eine Eingabe für Frage, Anrufnotiz und eingefügten Kundentext | Beides ist „Text statt Ticket“; zwei Features mit demselben Kern wären doppelter Aufwand | 2026-10-13 |
| Eingaben: Text (Pflicht), Kundengruppe und Produkte (optional); keine Bestellung, kein Zammad | Geltungsbereich des Wissens ist wichtig, Bestelldaten würden den Umfang vergrößern | 2026-10-13 |
| Antwortentwurf nur auf Wunsch (Option vor dem Start oder per Nachfrage) | Bei reinen Wissensfragen ist ein Kundenentwurf sinnlos; der Anruf-Fall bleibt abgedeckt | 2026-10-13 |
| Protokoll wie Analysen, persönlicher Verlauf, Admin-Übersicht, 12 Monate | Reproduzierbarkeit und Qualitätsmessung (Feedback, Lücken), ohne Kundendaten für alle sichtbar zu machen | 2026-10-13 |
| Kontaktdaten: Platzhalter wie bisher plus Hinweis, keine Sperre | Gleiches Schutzniveau wie bei Tickets, ohne den Anruf zu stören | 2026-10-13 |
| Nachfragen über die Fortsetzung aus PROJ-39, kein freier Chat | Vorhandener Mechanismus, kleiner Umfang | 2026-10-13 |
| Oberfläche: „Frage stellen“ und Ergebnis „Vorschlag“ für beide Wege, „Analyse“ bleibt in Code und Protokoll | „Analyse“ passt nicht zur Frage „Was ist zu tun?“; Code und Protokoll nicht anfassen | 2026-10-13 |
| Priorität P1 | Nutzen im Betrieb hoch, aber nicht auf dem Weg zum Termin Mitte November | 2026-10-13 |

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
