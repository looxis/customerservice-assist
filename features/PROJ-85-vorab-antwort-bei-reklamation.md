# PROJ-85: Automatische Vorab-Antwort bei Reklamation

## Status: Architected
**Created:** 2026-11-11
**Last Updated:** 2026-11-12

## Dependencies
- Requires: PROJ-80 (Klassifikation neuer Tickets, Kategorie `reklamation`)
- Requires: PROJ-82 (WISMO-Automatisierung): Vorbild für Schalter, Trockenlauf, Protokoll, technischen Zammad-Zugang, Antwort in der Kundensprache
- Requires: PROJ-60 (Bildanhänge eines Tickets)
- Requires: PROJ-79 (Versanddatum der Bestellung, Tage seit Versand)
- Requires: PROJ-66 (automatischer Ticket-Eingang), PROJ-7 (Bestellung aus EOCS)
- Berührt: PROJ-86 (Reklamationsformular), PROJ-19 (Versand nach Zammad)

## Hintergrund
Bei Reklamationen ohne Foto fragt der Kundenservice heute immer dasselbe nach: ein oder zwei aussagekräftige Fotos in niedriger Auflösung und eine kurze Beschreibung. Das kostet Zeit und verzögert die Bearbeitung um einen Tag. Weil die Vorklassifikation (PROJ-80) neue Tickets sofort als Reklamation erkennt, kann die App direkt antworten. Bei vollständigen Angaben bekommt der Kunde eine Eingangsbestätigung mit der Zusage, dass sich ein Mitarbeiter in den nächsten Stunden darum kümmert. Das Ticket bleibt in jedem Fall offen und wird weiter von einem Mitarbeiter bearbeitet.

Dies ist nach PROJ-77 und PROJ-82 die dritte Ausnahme vom Ziel „Versand nur nach Klick“.

## User Stories
- Als Kunde mit einer Reklamation will ich sofort eine Antwort erhalten, die mir sagt, was zur Bearbeitung fehlt oder dass sich jemand zeitnah darum kümmert.
- Als Mitarbeiter im Kundenservice will ich, dass Reklamationen ohne Foto automatisch um Fotos und eine kurze Beschreibung ergänzt werden, damit ich nicht selbst nachfragen muss.
- Als Mitarbeiter will ich, dass das Ticket nach der automatischen Antwort offen bleibt, damit ich es wie gewohnt bearbeiten kann.
- Als Admin will ich die Funktion mit Schalter (aus, Trockenlauf, an) steuern und im Protokoll nachvollziehen, was gesendet wurde, damit ich der Automatik schrittweise vertrauen kann.
- Als Mitarbeiter will ich am Ticket erkennen, dass die Vorab-Antwort gesendet wurde und welcher Fall vorlag (interne Notiz).

## Out of Scope
- Bewertung, ob die Reklamation berechtigt ist, Zusage von Ersatz, Erstattung oder Kulanz: bleibt beim Mitarbeiter (PROJ-86).
- Bildanalyse der Fotos durch die KI (PROJ-21): Es zählt nur, dass Bilder vorhanden sind, nicht was sie zeigen.
- Vorab-Antworten für andere Kategorien als `reklamation` (WISMO läuft über PROJ-82).
- Reklamationen älter als 30 Tage nach Versand, Tickets ohne auffindbare Bestellung: keine Antwort, Bearbeitung wie bisher.
- Erneute Vorab-Antwort, wenn der Kunde Fotos nachreicht: Der Mitarbeiter bearbeitet das Ticket weiter.
- Automatisches Schließen, Zuweisen oder Statuswechsel des Tickets.
- Tickets, die eine zweite oder spätere Nachricht in einem bestehenden Verlauf sind (nur neue Tickets bei Eingang, wie PROJ-80/82).

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Auslöser und Voraussetzungen
- [ ] Angenommen ein neues Ticket wird mit Kategorie `reklamation` klassifiziert (Quelle Eingang, nicht unsicher, nicht mehrere Anliegen, Konfidenz mindestens Schwellwert) und der Schalter steht nicht auf „aus“, wenn die Klassifikation gespeichert ist, dann wird die Vorab-Antwort angestoßen.
- [ ] Angenommen der Schalter steht auf „aus“, wenn ein Reklamationsticket eingeht, dann geschieht nichts und es entsteht kein Protokolleintrag.
- [ ] Angenommen die Bestellnummer wird im Ticket erkannt und die Bestellung in EOCS gefunden, wenn der erste Versand höchstens 30 Tage zurückliegt, dann geht die Prüfung weiter.
- [ ] Angenommen der erste Versand liegt mehr als 30 Tage zurück, die Bestellung ist noch nicht versendet, keine Bestellnummer wird erkannt oder die Bestellung wird nicht gefunden, wenn die Prüfung läuft, dann wird nichts gesendet und das Protokoll nennt den Grund; am Ticket steht eine interne Notiz „Keine Vorab-Antwort: <Grund>“.
- [ ] Angenommen das Ticket ist zusammengeführt, geschlossen, bereits von einem Mitarbeiter beantwortet oder es liegt eine neuere Kundennachricht vor, wenn die Prüfung läuft, dann wird übersprungen und der Grund protokolliert.
- [ ] Angenommen für das Ticket gibt es bereits eine Vorab-Antwort oder einen Lauf, wenn die Klassifikation erneut läuft, dann wird nie ein zweites Mal geantwortet.

### Szenario ohne Foto
- [ ] Angenommen kein betrachtbarer Bildanhang (jpg, png, webp, ohne Logos und Signaturbilder) in der Kundennachricht, wenn die Antwort entsteht, dann bittet sie höflich um ein oder maximal zwei aussagekräftige Fotos in niedriger Auflösung, auf denen das Problem zu erkennen ist.
- [ ] Angenommen Szenario ohne Foto, dann nennt die Antwort, dass ein automatisierter Vorab-Check die Nachricht als Reklamation erkannt hat und ein Mitarbeiter sie schnellstmöglich bearbeitet.
- [ ] Angenommen Szenario ohne Foto, dann bittet die Antwort, zu prüfen, ob das Problem in zwei bis drei Sätzen beschrieben ist, und das sonst nachzuholen.
- [ ] Angenommen ein Foto ist vorhanden, aber die Nachricht enthält keine Beschreibung des Problems (Urteil der KI), dann bittet die Antwort sinngemäß: „Beschreiben Sie den Fehler bitte kurz in ein oder zwei Sätzen, möglichst verständlich“, und fragt nicht erneut nach Fotos.
- [ ] Angenommen die Antwort bittet um Fotos, dann nennt sie sinngemäß: niedrig aufgelöst, keine zu großen Dateien, der Fehler muss erkennbar sein (ohne Zahlenangabe).
- [ ] Angenommen die Antwort sichert Bearbeitung zu, dann lautet die Zusage immer „in den nächsten Stunden“, unabhängig von Uhrzeit und Wochentag.

### Szenario mit Foto und Beschreibung
- [ ] Angenommen ein oder zwei betrachtbare Fotos und eine kurze Beschreibung, wenn die Antwort entsteht, dann sagt sie, dass der automatisierte Vorab-Check eine Reklamation erkannt hat, und gibt das Problem in einem Satz mit anderen Worten wieder.
- [ ] Angenommen Szenario mit Foto, dann entschuldigt sich die Antwort vorab und sichert zu, dass sich ein Mitarbeiter in den nächsten Stunden darum kümmert.
- [ ] Angenommen mehr als zwei Fotos sind angehängt und eine Beschreibung ist vorhanden, dann gilt das gleiche Szenario (mehr Fotos sind kein Grund zum Nachfragen).

### Inhalt und Sicherheit der Antwort
- [ ] Angenommen eine Antwort wird erzeugt, dann enthält sie keine Zusage von Ersatz, Erstattung, Gutschrift, Rabatt oder Kosten und keine Beträge, Links oder E-Mail-Adressen (Prüfung durch den ReplyGuard wie bei PROJ-82).
- [ ] Angenommen der Kunde schreibt in einer anderen Sprache als Deutsch, dann ist die Antwort in seiner Sprache, und die interne Notiz enthält zusätzlich die deutsche Fassung.
- [ ] Angenommen die Antwort enthält unausgefüllte Platzhalter oder besteht den ReplyGuard nicht, dann wird nicht gesendet; am Ticket steht eine interne Notiz, der Lauf endet als Fehler oder Notiz.
- [ ] Angenommen die Antwort entsteht, dann hängt die App wie bei WISMO die konfigurierte Signatur an.

### Ticketstatus und Versand
- [ ] Angenommen die Antwort wird gesendet, dann bleibt das Ticket offen, es wird weder geschlossen noch zugewiesen.
- [ ] Angenommen die Antwort wurde gesendet, dann steht am Ticket eine interne Notiz „Vorab-Antwort automatisch gesendet (<Szenario>)“ mit der deutschen Fassung.
- [ ] Angenommen der Zammad-Versand schlägt vorübergehend fehl, dann wird bis zu dreimal mit wachsender Wartezeit wiederholt; bleibt der Ausgang unklar (Zeitüberschreitung beim Senden), wird nie erneut gesendet.
- [ ] Angenommen der Lauf hängt länger als fünf Minuten, dann nimmt ihn der Wiederaufnahme-Befehl auf, ohne doppelt zu senden.

### Schalter, Trockenlauf, Protokoll
- [ ] Angenommen der Schalter steht auf „Trockenlauf“, dann sendet die App nichts und schreibt am Ticket eine interne Notiz „Vorab-Antwort Trockenlauf“ mit Entwurf und deutscher Fassung; der Lauf endet als `dry_run`.
- [ ] Angenommen der Schalter steht auf „an“, dann wird gesendet, wenn alle Prüfungen bestanden sind.
- [ ] Angenommen ein Admin öffnet die Protokoll-Seite, dann sieht er je Ticket Lauf, Szenario, Ergebnis (gesendet, Trockenlauf, übersprungen, Notiz, Fehler), Grund, Tage seit Versand, Anzahl Fotos, Prompt-Version und Modell; Texte sind 90 Tage nach dem Lauf geleert.
- [ ] Angenommen ein Admin ändert Schalter, Signatur, Modell oder Prompt, dann sind die Änderungen versioniert und rückgängig machbar (Einstellungsseite wie PROJ-27/31); Nicht-Admins können nur ansehen.
- [ ] Angenommen der Standard, dann steht der Schalter auf „aus“.

## Edge Cases
- Kundennachricht hat nur eingebettete Bilder (Logo, Signatur): zählt nicht als Foto; wie bei PROJ-60 werden Bilder im Fließtext ab zehn KB und nach der Grußformel ab 100 KB gezählt.
- Anhang ist ein PDF oder eine Videodatei: kein Foto im Sinne dieser Funktion; die Antwort bittet um Fotos.
- Mehrere Bestellungen im Ticket: Es gilt die erste erkannte Bestellung; ist deren Versand älter als 30 Tage, wird nicht geantwortet.
- Bestellung noch nicht versendet (Reklamation vor Versand oder Verwechslung mit WISMO): kein Versanddatum, daher keine Vorab-Antwort.
- Klassifikation „reklamation“, aber Text ist eigentlich Spam oder Werbung: ReplyGuard und Konfidenzschwelle schützen; Antwort geht nur bei sicherer Einstufung.
- Kunde antwortet mehrmals schnell hintereinander, bevor die Antwort gesendet ist: neuere Kundennachricht führt zum Überspringen, der Mitarbeiter bearbeitet.
- Ticket wurde in der Testmenge (PROJ-78/80) eingeordnet: wie bei WISMO wechselt es in die Trainingsmenge, wenn geantwortet wird.
- KI-Modell nicht erreichbar: Wiederholung wie bei WISMO, danach Fehler und interne Notiz.
- Der Mitarbeiter beantwortet das Ticket gleichzeitig manuell: Prüfung „bereits beantwortet“ direkt vor dem Senden verhindert die Doppelantwort.

## Technical Requirements (optional)
- Antwort in der Regel innerhalb weniger Minuten nach Ticket-Eingang.
- Datenschutz: Kundentext wird vor dem Modellaufruf pseudonymisiert (wie PROJ-82); nur Auftragsverarbeiter mit AVV.
- Wiederverwendung der WISMO-Bausteine (Lauf je Ticket eindeutig, technischer Zammad-Zugang, Wiederaufnahme, Löschfristen).
- Alle Texte und Einstellungen über die Einstellungsseite, keine fachlichen Regeln im Code.

## Open Questions
_Alle bisherigen Fragen sind beantwortet (siehe Product Decisions)._

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Eigenes Feature, getrennt vom Reklamationsformular (PROJ-86) | Unterschiedliche Nutzer (Kunde/Automatik vs. Mitarbeiter) und Testbarkeit | 2026-11-11 |
| Umsetzung wie PROJ-82 (KI-Antwort mit Schalter aus/Trockenlauf/an, Protokoll, ReplyGuard), keine festen Textvorlagen | Zusammenfassung des Problems in eigenen Worten und Kundensprache brauchen das Modell; bewährtes Muster | 2026-11-11 |
| Foto = betrachtbarer Bildanhang; Beschreibung beurteilt die KI | Anhang ist objektiv prüfbar; ob die Beschreibung reicht, ist Sprachverständnis | 2026-11-11 |
| Nur bis 30 Tage nach erstem Versand | Danach sind Reklamationen ohnehin Einzelfallprüfung (PROJ-79); keine automatische Zusage bei alten Fällen | 2026-11-11 |
| Ticket bleibt immer offen | Der Mitarbeiter muss die Reklamation weiter bearbeiten; Automatik löst nichts ab | 2026-11-11 |
| Keine Zusagen zu Ersatz, Erstattung oder Kulanz | Berechtigung entscheidet der Mitarbeiter (PROJ-86); Vorab-Antwort ist nur Eingangsbestätigung bzw. Nachfrage | 2026-11-11 |
| Standard „aus“, erst Trockenlauf | Neue Ausnahme vom Ziel „Versand nur nach Klick“ wird schrittweise freigegeben | 2026-11-11 |
| Nur ein Versuch je Ticket | Verhindert Doppelantworten und Schleifen | 2026-11-11 |
| Fotohinweis ohne Zahlenangabe: „niedrig aufgelöst, keine zu großen Dateien, der Fehler muss erkennbar sein“ | Entscheidung des Auftraggebers; eine feste Größe wäre für Kunden schwer einzuhalten | 2026-11-11 |
| Foto ohne Beschreibung: Rückfrage „Beschreiben Sie den Fehler kurz in ein oder zwei Sätzen, möglichst verständlich“ | Ohne Beschreibung kann der Mitarbeiter das Foto oft nicht einordnen | 2026-11-11 |
| Einstellungen unter `/einstellungen` im neuen Bereich „Reklamations-Automatik“ (Schalter, Signatur, Modell, Prompt) | Entscheidung des Auftraggebers; WISMO behält seine eigene Seite | 2026-11-11 |
| „In den nächsten Stunden“ gilt immer, auch nachts und am Wochenende | Entscheidung des Auftraggebers, auch wenn es einmal 20 Stunden dauert; keine Servicezeiten-Logik | 2026-11-11 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|
| Neuer Dienst und eigene Protokolltabelle nach Vorbild WISMO, kein Umbau des laufenden WISMO-Dienstes | WISMO läuft produktiv; ein gemeinsamer Unterbau wäre später ein eigener Aufräumauftrag | 2026-11-12 |
| Fotozahl bestimmt der Code, ob die Beschreibung reicht, die KI; passt das KI-Szenario nicht zur Fotozahl, wird nicht gesendet | Ein Modellfehler darf nie die falsche Mail auslösen | 2026-11-12 |
| ReplyGuard ohne erlaubte Links wiederverwenden, zusätzlich Wortprüfung auf Zusagen zu Ersatz, Erstattung, Kulanz | Antwort geht ohne menschliche Prüfung an den Kunden | 2026-11-12 |
| Fotos aus der vorhandenen Anhangsliste (PROJ-60), Versandalter aus der PROJ-79-Berechnung, Grenze 30 Tage als Konstante im Code | Keine zweite Erkennung, gleiche Regeln überall | 2026-11-12 |
| Auslöser im Klassifikationsschritt (neben WISMO), nur Quelle „Eingang“ | Antwort innerhalb weniger Minuten, nie bei alten oder erneut klassifizierten Tickets | 2026-11-12 |
| Schalter, Signatur, Modell als versionierte Einstellungen, Prompt als Datei (zehnter Prompt), eigene Seite „Reklamations-Automatik“ und Protokoll-Seite | Bestehendes Muster aus PROJ-27/31/82, Rückgängig-Funktion inklusive | 2026-11-12 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

### Kurzfassung
PROJ-85 ist ein Zwilling von PROJ-82 (WISMO-Automatik): gleicher Auslöser (die Klassifikation neuer Tickets), gleiche Sicherheitsnetze (Schalter, Trockenlauf, Protokoll, ReplyGuard, technischer Zammad-Nutzer), anderer Inhalt. Neu sind nur die Bedingungen („Versand höchstens 30 Tage her“, „Fotos vorhanden?“) und der Antworttext. Es wird nichts an der Oberfläche der Ticketseite umgebaut. Ein reiner Backend-Auftrag plus zwei Admin-Seiten, die den vorhandenen Seiten gleichen.

### A) Ablauf (Entscheidungsbaum)
```
Neues Ticket wird klassifiziert (PROJ-80)
+-- Kategorie "reklamation", sicher (nicht unklar, nur ein Anliegen, Konfidenz über Schwelle), Quelle Eingang?
|   +-- nein -> nichts geschieht, kein Protokoll
|   +-- ja, Schalter "aus" -> nichts geschieht, kein Protokoll
|   +-- ja -> Lauf wird angelegt (einer je Ticket, nie ein zweiter)
+-- Prüfungen (jede Absage = Lauf "übersprungen" bzw. "Notiz" mit Grund)
|   +-- Ticket zusammengeführt / geschlossen / schon von Mitarbeiter beantwortet / neuere Kundennachricht -> übersprungen
|   +-- keine Bestellnummer, Bestellung nicht in EOCS, kein Versanddatum, Versand > 30 Tage -> interne Notiz "Keine Vorab-Antwort: <Grund>"
|   +-- keine eindeutige Antwortadresse -> interne Notiz
+-- Szenario wird bestimmt
|   +-- 0 betrachtbare Fotos -> "Fotos bitte" (Fotohinweis ohne Zahlen, kurze Beschreibung prüfen, Zusage "in den nächsten Stunden")
|   +-- Fotos, aber keine Beschreibung (Urteil KI) -> "Beschreibung bitte" (fragt nicht erneut nach Fotos)
|   +-- Fotos und Beschreibung -> "Eingangsbestätigung" (Problem in einem Satz, Entschuldigung, Zusage)
+-- KI schreibt Antwort in Kundensprache + deutsche Fassung (Kundentext vorher pseudonymisiert)
+-- Kontrollen: ReplyGuard, keine Platzhalter, Szenario passt zur Fotozahl
+-- Schalter "Trockenlauf" -> interne Notiz mit Entwurf, Ende "Trockenlauf"
+-- Schalter "an" -> Antwort an Zammad (Ticket bleibt offen), interne Notiz "Vorab-Antwort automatisch gesendet"
```

### B) Bausteine (was neu ist, was bleibt)
```
Klassifikation neuer Tickets (vorhanden)
+-- stößt zusätzlich zu WISMO die Vorab-Antwort an   <- kleine Ergänzung
Vorab-Antwort-Dienst (NEU, nach Vorbild WISMO-Antwortdienst)
+-- Prüfungen und Fotozählung (Fotozählung nutzt PROJ-60-Regeln, nichts Neues)
+-- Versandalter aus der Bestellung (PROJ-79, vorhanden)
+-- KI-Aufruf mit eigenem Prompt und eigenem Modell
+-- Wiederverwendet: ReplyGuard, Pseudonymisierung, Zammad-Versand, Notizen, Bestellnummern-Erkennung, EOCS-Abruf
Hintergrundauftrag (NEU): wiederholt bis zu 3x bei vorübergehenden Fehlern
Zeitplan (NEU, 2 Einträge): Wiederaufnahme hängender Läufe alle 5 Min, Texte nach 90 Tagen leeren
Einstellungsseite "Reklamations-Automatik" (NEU, Muster der WISMO-Seite)
+-- Schalter (aus / Trockenlauf / an, Standard aus), Signatur
+-- Prompt und Modell erscheinen auf "Prompts und Modell" (dort entsteht ein zehnter Prompt)
Protokoll-Seite (NEU, Muster "WISMO-Antworten", nur Admins)
+-- Liste und Detail je Ticket
```

### C) Datenmodell (Klartext)
Neue Protokolltabelle, eine Zeile je Ticket (die Zeile selbst verhindert die Doppelantwort):
- Ticket (Zammad-ID eindeutig, Ticketnummer), Schalterstand beim Lauf, Ergebnis (in Arbeit, wird wiederholt, Versand unklar, gesendet, Trockenlauf, Notiz, Fehler, übersprungen), Grund, Anzahl Versuche
- Bestellnummer, Tage seit erstem Versand, Anzahl Fotos, Szenario (Fotos bitte / Beschreibung bitte / Eingangsbestätigung)
- Prompt-Version, Modell, Tokens, Dauer
- Antworttext und deutsche Fassung (verschlüsselt, nach 90 Tagen geleert)
- Zeitpunkte für Versandbeginn und Abschluss
Neue Einstellungen (versioniert, Verlauf, rückgängig machbar wie die übrigen): Schalter, Signatur, Modell. Der Prompt liegt als Datei mit Versionskennung wie die anderen.
Gespeichert in: MySQL. Es gibt keine neue Spalte an bestehenden Tabellen.

### D) Technische Entscheidungen (begründet)
- **Neuer Dienst nach Vorbild WISMO statt Umbau von WISMO.** Der WISMO-Dienst läuft produktiv. Ein gemeinsamer Unterbau (Lauf anlegen, Wiederaufnahme, Versand ohne Doppelsendung) wäre sauberer, würde aber den Live-Betrieb von WISMO berühren. Wir kopieren daher das bewährte Muster und gleichen nur die kleinen Unterschiede an; ein späteres Zusammenführen steht als Aufräumpunkt im Spec.
- **Eigene Protokolltabelle statt Mitnutzung der WISMO-Tabelle.** Andere Spalten (Fotos, Szenario, Tage), anderer Zweck, eigene Löschfrist; eine gemeinsame Tabelle würde beide Seiten verwässern.
- **Schalter, Signatur und Modell in der Einstellungsseite, Prompt als Datei.** Wie bei WISMO: Änderungen sind versioniert und von Admins rückgängig zu machen; keine fachlichen Regeln im Code.
- **Szenario entscheidet der Code, wo es objektiv ist (Fotozahl), die KI nur, wo es Sprachverständnis braucht (reicht die Beschreibung?).** Die KI nennt zusätzlich ihr Szenario; passt es nicht zur Fotozahl (z. B. „Eingangsbestätigung“ bei null Fotos), wird nicht gesendet, sondern eine Notiz geschrieben. So kann ein Modellfehler nie die falsche Mail auslösen.
- **ReplyGuard unverändert wiederverwenden, ohne erlaubte Links.** Die Vorab-Antwort darf nie Links, Adressen, Beträge oder Rabatte nennen; „kostenlos“, „Gutschrift“ usw. fallen ebenso durch. Dazu kommt eine Wortprüfung auf Zusagen zu Ersatz/Erstattung/Kulanz (die Zusage „in den nächsten Stunden“ ist erlaubt).
- **Foto = betrachtbares Bild (jpg, png, webp) in der aktuellen Kundennachricht, gezählt über die vorhandene Anhangsliste (PROJ-60).** Logos und Signaturbilder sind dort schon ausgefiltert; PDF und Video zählen nicht.
- **Alter aus dem ersten Versand der Bestellung (PROJ-79-Berechnung), Grenze 30 Tage im Code als Konstante.** Ohne Versanddatum keine Antwort (verhindert Verwechslung mit WISMO).
- **Auslöser im Klassifikationsschritt, nicht per Zeitplan.** Antwort kommt so innerhalb von Minuten; nur neue Tickets der Quelle „Eingang“, nie alte oder erneut klassifizierte.
- **Pseudonymisierung des Kundentexts vor dem KI-Aufruf, Platzhalter-Kontrolle danach** (wie WISMO; Datenschutz).
- **Ticket bleibt offen, kein Zuweisen, kein Schließen.** Versand mit Status „offen“, wie WISMO.
- **Nur ein Versuch je Ticket.** Eine zweite Klassifikation oder ein neuer Aufruf antwortet nie ein zweites Mal.
- **Reihenfolge mit WISMO:** Beide laufen nur je nach Kategorie; ein Ticket hat eine Kategorie, es gibt keine Überschneidung. Zusätzlich ist der Lauf je Ticket eindeutig.
- **Testmenge:** Wird geantwortet, wechselt das Ticket wie bei WISMO in die Trainingsmenge.

### E) Dependencies
Keine neuen Pakete.

### F) Teststrategie (für /backend und /qa)
Pest-Tests mit simuliertem Zammad, EOCS und Sprachmodell, nach Vorbild der WISMO-Tests: Schalter aus/Trockenlauf/an, jedes Szenario, 30-Tage-Grenze, ohne Bestellung/Versand, Doppelanstoß, neuere Kundennachricht, ReplyGuard-Treffer, Szenario passt nicht zur Fotozahl, Wiederholung und unklarer Versand, Wiederaufnahme, Löschung nach 90 Tagen, Rechte auf der Einstellungsseite.

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
