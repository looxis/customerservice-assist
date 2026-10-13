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
- [x] Persönlicher Verlauf: Empfehlung der Architektur: 10 Einträge auf der Seite „Frage stellen“ (zu bestätigen).
- [x] Nachträglicher Entwurf: über die Nachfrage mit Kontrollkästchen, kein eigener Knopf (Architektur-Empfehlung, zu bestätigen).
- [x] Textlänge: Empfehlung 6000 Zeichen (länger als das Kontextfeld wegen eingefügter Kundentexte), zu bestätigen.
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
| Frage = Fall mit F-Kennung in der bestehenden Analyse-Tabelle, keine neue Tabelle | Protokoll, Feedback, Lücken, Fortsetzen, Aufbewahrung und Verschlüsselung gelten sofort; eine Datenbankänderung ist nicht nötig | 2026-10-13 |
| Zulässige Kennung in den Adressen für Feedback, Lücke, Entwurf, Kopieren, Rückübersetzung wird auf F-Kennung erweitert; die Ticketseite bleibt nur für Ziffern | Wiederverwendung statt doppelter Wege; ein Zammad-Abruf mit F-Kennung ist ausgeschlossen | 2026-10-13 |
| Eigener Eingang und Controller, künstliches (nie gespeichertes) Ticket mit einer Nachricht für den Analysekern | Wissensabruf, Platzhalter und Validierung bleiben unverändert | 2026-10-13 |
| Dritter Zustand „nur Auskunft“ im Analysevertrag | Antwortentwurf nur auf Wunsch, ohne ihn als „zuerst intern klären“ auszugeben | 2026-10-13 |
| Prompt-Zusatz nur zur Form (kein Ticket, Text vom Mitarbeiter, Entwurf gewünscht ja/nein) | Fachliche Regeln bleiben in der Wissensdatenbank | 2026-10-13 |
| Keine Übersetzung und keine Zusammenfassung für Fragen | Beides hängt an Zammad-Nachrichten; die KI versteht Fremdsprachen direkt | 2026-10-13 |
| Nachfrage = Fortsetzen aus PROJ-39 mit Kontrollkästchen für den Entwurf | Ein Mechanismus für Klärung, Nachfrage und nachträglichen Entwurf | 2026-10-13 |
| Umbenennung „Analyse“ → „Vorschlag“ als eigener Schritt vor dem neuen Weg | Viele Texte und Tests; Trennung hält die Änderungen prüfbar | 2026-10-13 |
| Ticket-Übersicht und „Bearbeitete Tickets“ zählen keine Fragen, Analysen-Übersicht bekommt Filter | Fragen sind keine Tickets; Auswertung bleibt vergleichbar | 2026-10-13 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

### Grundidee
Eine Frage ohne Ticket wird intern wie ein **Fall mit einer einzigen Nachricht** behandelt. Der Text des Mitarbeiters übernimmt die Rolle des Tickettextes; alles Weitere (gezielter Wissensabruf mit Router, Kundengruppe und Produkt, Platzhalter für Kontaktdaten, feste Ergebnisstruktur, Quellen, Protokoll, Feedback, Wissenslücken, Fortsetzung aus PROJ-39) läuft über denselben Weg wie die Ticketanalyse. Es entsteht keine zweite Analyse-Maschine, sondern nur ein anderer Eingang und eine andere Seite.

Backend ist nötig (Speicherung, KI-Aufruf), es kommen **keine neuen Pakete** und **keine neue Datenbanktabelle** dazu.

### A) Seitenstruktur
```
Seitenleiste
+-- Ticket analysieren (bleibt, Umbenennung offen)
+-- Frage stellen (neu)

Seite „Frage stellen“
+-- Hinweis: keine Kundendaten eintragen
+-- Formular
|   +-- Textfeld (Frage, Anrufnotiz oder eingefügter Kundentext)
|   +-- Kundengruppe (Standard „Noch unklar“)
|   +-- Produkte (optional)
|   +-- Kontrollkästchen „Auch einen Antwortentwurf an den Kunden formulieren“
+-- Meine letzten Fragen (nur eigene, Datum + Textanfang, öffnet die Frage)

Seite „Frage“ (nach dem Abschicken, auch später aus dem Verlauf)
+-- Ergebnis „Vorschlag“ (dieselbe Komponente wie beim Ticket)
|   +-- Was ist zu tun? / Befugnis / Wissenslücken
|   +-- Antwortentwurf (nur wenn gewünscht) ODER Hinweis „Kein Antwortentwurf angefordert“
|   +-- Feedback, Wissenslücke melden, Begründung, Quellen, Wissensabruf
+-- Interne Abläufe (Klärung, Schritte, Arbeitsanleitungen) wie bei PROJ-39
+-- Nachfrage / Ergänzung (Textfeld + Kontrollkästchen „Antwortentwurf formulieren“)
```
Wiederverwendet werden die vorhandenen Komponenten (`analysis/result`, `analysis/procedures`, `analysis/form`-Bausteine für Kundengruppe und Produkte, `reply-editor`, `feedback`, `gap-form`). Neu sind nur die Seiten „Frage stellen“ und „Frage“ und ein kleiner Block für die Nachfrage.

### B) Datenmodell (in Worten)
Gespeichert wird in der **bestehenden Analyse-Tabelle**; es gibt keine neue Tabelle und keine Änderung an Spalten.
```
Jede Frage ist ein Fall mit einer eigenen Kennung statt einer Ticketnummer:
- Kennung: der Buchstabe F plus acht Ziffern (z. B. F48201736), zufällig vergeben, nur für die App
- Nachfragen derselben Frage behalten die Kennung; jede Nachfrage ist wie bei Tickets eine neue Zeile
- Gespeichert wie jede Analyse: Mitarbeiter, Kundengruppe, Produkte, Kategorie, Modell, Prompt-Version,
  Wissensstand, Kennzahlen (offen lesbar) sowie der Text der Frage und der ganze Vorschlag (verschlüsselt)
- Die Frage steht im verschlüsselten Inhalt als „Anfrage“; ein Ticket (Zammad) gibt es nicht
- Nachfragen werden wie die Klärungen aus PROJ-39 gespeichert (Frage + Antwort + Name + Zeit)
- Aufbewahrung 12 Monate wie bisher, danach werden die Inhalte gelöscht
```
Der Verlauf je Mitarbeiter ist eine Abfrage: „alle Fälle mit Kennung F…, Mitarbeiter = ich, neueste zuerst, höchstens 10, je Frage nur die neueste Zeile“. Feedback und gemeldete Wissenslücken nutzen ihre bestehenden Tabellen mit der F-Kennung an der Stelle der Ticketnummer.

### C) Tech-Entscheidungen (begründet)
- **Fall mit F-Kennung statt eigener Tabelle:** Alles, was Analysen schon können (Protokoll, Feedback, Wissenslücken, Entwurf bearbeiten, Kopieren, Fortsetzen, Aufbewahrung, Verschlüsselung, Admin-Übersicht), funktioniert damit sofort, und es gibt nur einen Weg zu pflegen. Die Kennzeichnung „Frage ohne Ticket“ ergibt sich aus der Kennung; dafür braucht es keine Datenbankänderung.
- **Zulässige Kennungen erweitern:** Die bestehenden Adressen für Feedback, Lücke melden, Entwurf speichern und Kopieren erlauben bisher nur Ziffern. Sie erlauben künftig zusätzlich die F-Kennung; die Seite „Ticket“ selbst bleibt auf echte Ticketnummern beschränkt. Die Ticketanalyse darf mit einer F-Kennung nie ein Zammad-Ticket abrufen.
- **Eigene Eingangsseite und eigener Controller für die Frage:** „Frage stellen“ und „Frage“ sind eigene Seiten, weil das Ticket-Formular am Zammad-Abruf hängt. Der Kern (`CaseAnalyzer`) bekommt den Text als einzige Nachricht eines künstlichen, nie gespeicherten Tickets; dadurch ändern sich Wissensabruf und Validierung nicht.
- **Neuer Zustand im Analysevertrag: „nur Auskunft“:** Neben „Antwort“ und „erst intern klären“ (PROJ-39) kommt der dritte Fall „Auskunft für den Mitarbeiter, kein Antwortentwurf“. Wird der Entwurf ausdrücklich verlangt, gilt wie bisher „Antwort“. Das Kontrollkästchen geht als Angabe in den Prompt; entscheiden über Form und Inhalt tut weiterhin die Wissensdatenbank, nicht der Prompt.
- **Kleiner Prompt-Zusatz, keine fachlichen Regeln:** Ein kurzer Abschnitt sagt der KI, dass es kein Ticket gibt, der Text vom Mitarbeiter stammt (Frage, Notiz oder eingefügte Kundennachricht), und ob ein Entwurf gewünscht ist. Er wird versioniert wie die anderen Prompts.
- **Kontaktdaten:** Der vorhandene Platzhalter-Mechanismus wird auf den Text angewendet, bevor er an den Anbieter geht; der Hinweis steht am Textfeld. Bestellnummern im Text werden nicht in EOCS nachgeschlagen.
- **Keine Übersetzung der Frage, kein Zusammenfassen:** Beides hängt an Zammad-Nachrichten. Ein fremdsprachiger Kundentext wird von der KI direkt verstanden; der Entwurf kommt in der Sprache des Textes.
- **Nachfragen über die Fortsetzung aus PROJ-39:** Die Nachfrage mit dem Kontrollkästchen „Antwortentwurf formulieren“ ist dasselbe Fortsetzen wie die interne Klärung. Dadurch ist der nachträgliche Entwurf ohne eigenen Knopf abgedeckt.
- **Schutz:** Wie bei Tickets Namensauswahl Pflicht, Begrenzung der KI-Aufrufe pro Minute, Prüfung aller Eingaben in eigenen Prüfklassen, Text wird beim Anzeigen maskiert. Nachfragen sind nur an der neuesten Zeile der Frage und nur für den Mitarbeiter möglich, dem sie gehört; fremde Fragen lassen sich nicht öffnen.
- **Auswirkungen auf Zählungen:** „Bearbeitete Tickets“ (Seitenleiste, PROJ-41) und die Ticket-Übersicht (PROJ-35) zählen keine Fragen. Die Analysen-Übersicht (PROJ-14) bekommt eine Kennzeichnung „Frage“ und einen Filter „Tickets / Fragen“, die Kennzahlen gelten für die gefilterte Menge.
- **Begriffe in der Oberfläche zuerst und für beide Wege:** Als eigener Schritt wird „Analyse“ in sichtbaren Texten durch „Vorschlag“ ersetzt (Überschriften, Knöpfe, Verlauf, Hilfetexte, Seite „Über die App“). Code, Datenbank und Protokolle behalten „Analyse“. Dieser Schritt berührt viele Texte und Tests und wird deshalb getrennt vom neuen Weg umgesetzt.

### D) Reihenfolge der Umsetzung
1. **Begriffe:** „Analyse“ → „Vorschlag“ in der Oberfläche beider Wege, Tests und Hilfetexte anpassen.
2. **Vertrag:** Zustand „nur Auskunft“, Prompt-Zusatz, Prüfung, Anzeige ohne Entwurf.
3. **Eingang:** Seite „Frage stellen“, künstliches Ticket, Speichern, Anzeige der Frage, Verlauf je Mitarbeiter.
4. **Fortsetzen:** Nachfrage mit Kontrollkästchen für den Entwurf.
5. **Auswirkungen:** Zählungen, Analysen-Übersicht, Über die App.

### E) Abhängigkeiten
- Keine neuen Pakete.
- Konfiguration: eine Obergrenze für die Länge des Fragetextes (Vorschlag 6000 Zeichen, damit eingefügte Kundentexte Platz haben) und die Zahl der Einträge im Verlauf (Vorschlag 10).


## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
