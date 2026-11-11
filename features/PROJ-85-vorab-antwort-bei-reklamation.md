# PROJ-85: Automatische Vorab-Antwort bei Reklamation

## Status: Planned
**Created:** 2026-11-11
**Last Updated:** 2026-11-11

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
- [ ] Angenommen ein Foto ist vorhanden, aber die Nachricht enthält keine Beschreibung des Problems (Urteil der KI), dann bittet die Antwort um die kurze Beschreibung, nicht erneut um Fotos.

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
- [ ] Soll der Hinweis „Fotos in niedriger Auflösung“ eine Größenangabe nennen (z. B. unter 1 MB)? Vorerst ohne Zahl.
- [ ] Sollen Reklamationen mit Foto, aber ohne Beschreibung beim ersten Mal nach der Beschreibung gefragt werden? Vorerst ja (siehe Kriterien); ggf. nach den ersten Trockenläufen anpassen.
- [ ] Auf welcher Einstellungsseite liegt der Schalter (Seite „WISMO“ umbenennen in „Automatische Antworten“ oder eigene Seite)? Entscheidung in `/architecture`.
- [ ] Darf die Eingangsbestätigung eine konkrete Zeitspanne nennen („in den nächsten Stunden“) auch außerhalb der Geschäftszeiten (Nacht, Wochenende)? Vorerst „zeitnah“ statt „in den nächsten Stunden“, wenn der Eingang außerhalb der Servicezeiten liegt; Servicezeiten klärt der Auftraggeber.

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
