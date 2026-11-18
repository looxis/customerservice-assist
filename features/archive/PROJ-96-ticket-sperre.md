# PROJ-96: Ticket-Sperre bei gleichzeitiger Bearbeitung

## Status: Approved
**Created:** 2026-11-18
**Last Updated:** 2026-11-18

## Dependencies
- Requires: PROJ-5 (Name des Mitarbeiters im Cookie `staff_name`, keine Anmeldung)
- Requires: PROJ-6 (Ticketseite)
- Requires: PROJ-67 (Ticketübersicht auf der Startseite, Zeilenfarben)

## Hintergrund
Zwei Mitarbeiter haben schon dasselbe Ticket gleichzeitig in CSA bearbeitet. Wer ein Ticket gerade aktiv bearbeitet, sperrt es künftig für die anderen. Ein vergessener offener Tab darf das Ticket nicht dauerhaft sperren: Die Sperre hängt an Aktivität, nicht am offenen Tab.

„Aktivität“ heißt: Der Tab mit der Ticketseite ist sichtbar und der Mitarbeiter bewegt die Maus, tippt, scrollt oder klickt. Das Öffnen des Tickets zählt ebenfalls als Aktivität.

## User Stories
- Als Mitarbeiter will ich auf der Startseite sehen, welche Tickets ein Kollege gerade bearbeitet, damit ich sie nicht doppelt anfange.
- Als Mitarbeiter will ich ein gesperrtes Ticket trotzdem lesen können, aber nichts daran ändern, damit keine doppelte Antwort entsteht.
- Als Mitarbeiter will ich, dass ein Ticket wieder frei wird, sobald der Kollege es nicht mehr bearbeitet, auch wenn sein Tab offen geblieben ist.
- Als Mitarbeiter will ich kurz in den Shop oder nach Zammad wechseln können, ohne dass mir ein Kollege das Ticket sofort wegnimmt.

## Out of Scope
- Sperre bewusst übernehmen („Bearbeitung übernehmen“): bewusst nicht vorgesehen, man wartet bis zur Freigabe.
- Automatisches Neuladen der Startseite: die Markierung erscheint beim nächsten Laden.
- Sperre in Zammad selbst: die Sperre gilt nur in CSA.
- Sperre gegen denselben Namen in einem zweiten Tab oder Browser: derselbe Name sperrt sich nicht selbst (ohne Login ist der Name die einzige Kennung).
- „Frage stellen“ ohne Ticket (PROJ-40): kein Ticket, keine Sperre.

## Acceptance Criteria

### Sperre setzen und halten
- [x] Angenommen ein Ticket ist frei, wenn ein Mitarbeiter mit gewähltem Namen es öffnet, dann ist es für alle anderen Namen gesperrt.
- [x] Angenommen der Bearbeiter ist auf der Ticketseite aktiv (Tab sichtbar, Maus, Tastatur, Scrollen, Klick), wenn Zeit vergeht, dann bleibt die Sperre bestehen.
- [x] Angenommen der Bearbeiter ist 5 Minuten lang nicht aktiv (Tab im Hintergrund oder keine Eingabe), wenn diese Zeit abgelaufen ist, dann ist das Ticket wieder frei.
- [x] Angenommen der Bearbeiter schließt den Tab oder verlässt die Ticketseite, wenn das geschieht, dann ist das Ticket sofort frei.
- [x] Angenommen der Bearbeiter kommt nach Ablauf zurück und das Ticket ist weiterhin frei, wenn er wieder aktiv wird, dann gehört die Sperre wieder ihm.
- [x] Angenommen ohne gewählten Namen, wenn jemand ein Ticket öffnet, dann setzt das keine Sperre.

### Startseite
- [x] Angenommen ein Ticket ist von einem anderen Mitarbeiter gesperrt, wenn ich die Startseite lade, dann ist die Zeile ausgegraut (blass, ohne Farbe) und zeigt „in Bearbeitung: {Name}“; das Ticket lässt sich trotzdem öffnen.
- [x] Angenommen ich selbst halte die Sperre, wenn ich die Startseite lade, dann ist meine Zeile normal dargestellt.

### Gesperrte Ticketseite
- [x] Angenommen ein Ticket ist von einem anderen Mitarbeiter gesperrt, wenn ich es öffne, dann ist die Seite hellrot hinterlegt, oben steht „{Name} bearbeitet dieses Ticket gerade – nur Ansicht“, und Schließen, Zuweisen, Vorschlag erstellen, Antworten, Klassifizieren, Übersetzen, ToDos und Zusammenfassung sind nicht bedienbar; Nachrichten und Vorschlag bleiben lesbar.
- [x] Angenommen ich habe ein gesperrtes Ticket offen, wenn es frei wird, dann erscheint innerhalb von etwa 30 Sekunden der Hinweis „Das Ticket ist wieder frei“ mit „Neu laden“.
- [x] Angenommen ein Ticket ist gesperrt, wenn ein anderer Mitarbeiter trotzdem eine ändernde Aktion abschickt (z. B. über einen alten Tab), dann lehnt die App sie mit „Ticket wird gerade von {Name} bearbeitet.“ ab.
- [x] Angenommen meine Sperre ist abgelaufen und ein Kollege hat das Ticket übernommen, wenn ich wieder aktiv werde, dann zeigt meine Seite den Hinweis, dass der Kollege es bearbeitet, mit „Neu laden“.

## Edge Cases
- **Zwei öffnen fast gleichzeitig:** Die Sperre wird atomar gesetzt, nur einer bekommt sie.
- **Seite neu laden oder Formular absenden:** Kurze Freigabe beim Verlassen, beim Laden der neuen Seite wird sofort neu gesperrt.
- **Langer Vorschlag (KI-Aufruf):** Die Seite bleibt während des Wartens offen; Aktivität hält die Sperre.
- **Browser stürzt ab / Rechner geht in Ruhezustand:** Keine Freigabe-Meldung, das Ticket wird nach 5 Minuten frei.
- **Fragenummern (F…):** keine Sperre.

## Decision Log
### Product Decisions
- 2026-11-18: Tab im Hintergrund gilt nicht als Aktivität, die Sperre läuft 5 Minuten nach der letzten Aktivität ab; Schließen gibt sofort frei.
- 2026-11-18: Kein Übernehmen-Button, nur warten mit Hinweis bei Freigabe.

### Technical Decisions
- Eigene Tabelle `ticket_locks` statt Cache: die Startseite fragt alle Sperren einer Seite mit einer Abfrage ab.
- Abgelaufene Sperren werden nicht aufgeräumt, sie gelten als frei und werden beim nächsten Sperren überschrieben.
- Die Sperre wird zusätzlich auf dem Server geprüft (Middleware), weil viele Aktionen per `fetch()` laufen.

## Tech Design (Solution Architect)

### A) Überblick
- Tabelle `ticket_locks` (`ticket_number` unique, `staff_name`, `last_activity_at`), Model `TicketLock`, Dienst `App\Tickets\TicketLocks` (holen/verlängern, freigeben, Halter, Sperren für eine Liste).
- `TicketController::show` sperrt beim Öffnen oder gibt den Halter an die Ansicht.
- Middleware `ticket.unlocked` auf allen ändernden `/tickets/{number}/…`-Routen: 409 bei JSON, sonst zurück mit Fehlermeldung.
- `TicketLockController`: `POST /tickets/{number}/sperre` (Herzschlag; mit `claim=1` holen/verlängern, sonst nur Status) und `POST /tickets/{number}/sperre/freigeben` (per `sendBeacon`).
- Alpine-Komponente `ticketLock` auf der Ticketseite.

### B) Daten
Eine Zeile je Ticket; frei, wenn `last_activity_at` älter als 5 Minuten ist. Keine Kundendaten.

### C) Ablauf
Bearbeiter: Eingaben im sichtbaren Tab markieren Aktivität, höchstens alle 30 s geht ein Herzschlag. Beim Verlassen (`pagehide`) Freigabe per `sendBeacon`. Gesperrte Seite: alle 30 s Statusabfrage bei sichtbarem Tab.

### D) Abhängigkeiten
Keine neuen Pakete.

### E) Tests
`tests/Feature/PROJ-96-TicketLockTest.php`.

## QA Test Results

**Tested:** 2026-11-18
**Tester:** QA Engineer (AI); Bedienung im Browser mit zwei Namen durch Etienne (2026-11-18, „funktioniert super“)

### Acceptance Criteria Status
- [x] Sperre setzen und halten (6/6): Öffnen sperrt, Herzschlag verlängert, Ablauf nach 5 Minuten, Freigabe beim Verlassen, Rückkehr holt die Sperre zurück, ohne Namen keine Sperre
- [x] Startseite (2/2): fremde Sperre ausgegraut mit „in Bearbeitung: Name“, eigene und abgelaufene Sperren normal
- [x] Gesperrte Ticketseite (4/4): hellrot, Hinweis „nur Ansicht“, Bearbeitungselemente `inert` bzw. ausgeblendet; Freigabe-Hinweis; Server lehnt ändernde Routen mit 409 bzw. Fehlermeldung ab; Hinweis bei verlorener Sperre

### Edge Cases Status
- [x] Gleichzeitiges Öffnen: Transaktion mit `lockForUpdate`, eindeutiger Index als Rückfall
- [x] Neu laden / Formular absenden: die neue Seite holt die Sperre 2 s nach dem Laden erneut, falls die Freigabe der alten Seite später ankommt
- [x] Absturz / Ruhezustand: Sperre läuft nach 5 Minuten aus (Test mit Zeitsprung)
- [x] Fragenummern (F…): Middleware greift nicht, Sperr-Routen nehmen nur Ticketnummern

### Security Audit Results
- [x] Kein Login (PROJ-15): Sperre hängt am Namen aus der Liste; Namen außerhalb der Liste zählen als „kein Name“ (Test)
- [x] CSRF: Herzschlag mit `X-CSRF-TOKEN`, Freigabe per `sendBeacon` mit `_token`
- [x] Freigabe löscht nur die eigene Sperre
- [x] XSS: Name nur über `{{ }}` und `@js` ausgegeben
- [x] Rate Limit: 120 Anfragen je Minute auf beiden Sperr-Routen
- [x] Keine Kundendaten in `ticket_locks` oder den Antworten

### Regression
- [x] Tests zu PROJ-5, 19, 28, 35, 67 und PROJ-96 grün (PROJ-28 gibt die Sperre zwischen zwei Namen frei)
- Volle Suite: Fehler nur in Wissensbasis-Tests beim Parallellauf (gemeinsames Wissensverzeichnis), einzeln grün; unabhängig von PROJ-96 (gleiche Fehler bei abgeschalteter Sperre)

### Bugs Found
Keine kritischen, hohen oder mittleren Fehler.

### Summary
- **Acceptance Criteria:** 12/12 passed
- **Bugs Found:** 0 (Critical/High/Medium)
- **Security:** Pass
- **Production Ready:** YES

## Deployment
_Kein eigener Schritt._
