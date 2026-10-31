# PROJ-67: Ticketübersicht auf der Startseite

## Status: In Progress
**Created:** 2026-10-31
**Last Updated:** 2026-10-31

## Dependencies
- Requires: PROJ-66 (Automatischer Ticket-Eingang und Datenmodell) – liefert die Daten
- Requires: PROJ-6 (Zammad-Ticket laden) – die vorhandene Ticketseite, die per Klick öffnet

## Hintergrund
Unterhalb der manuellen Ticketnummer-Eingabe erscheint eine einfache Liste der erfassten Tickets. Die Gestaltung soll bewusst einfach bleiben und sich im Betrieb weiterentwickeln können. Es entsteht keine zweite Bearbeitungsoberfläche.

## User Stories
- Als **Mitarbeiter** will ich sehen, welche offenen Tickets noch niemandem zugewiesen sind, damit ich mir das nächste nehmen kann.
- Als **Mitarbeiter** will ich meine offenen Tickets auf einen Blick sehen.
- Als **Mitarbeiter** will ich nach Status, Mitarbeiter, Kanal, Klassifikation und CSA-Status kombiniert filtern und sortieren, damit ich auch bei vielen Tickets schnell finde, was ich suche.
- Als **Mitarbeiter** will ich per Klick auf ein Ticket in die gewohnte Bearbeitung kommen.
- Als **Mitarbeiter** will ich erkennen, wenn der Stand der Liste veraltet ist, damit ich mich nicht auf alte Daten verlasse.

## Out of Scope
- Oberfläche für eigene gespeicherte Ansichten (Filtersätze sind dafür vorbereitet).
- Suche in Betreff oder nach Kunde: Der Betreff ist verschlüsselt, der Kundenname wird nicht gespeichert.
- Dashboards und Statistiken.
- Änderungen an der Ticketseite oder am manuellen Eingabefeld.

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Liste und Ansichten
- [ ] Angenommen die Startseite, wenn sie geöffnet wird, dann erscheint die Liste unterhalb des Eingabefelds mit Ticketnummer, Betreff, Zammad-Status, Mitarbeiter, Kanal, Klassifikation, CSA-Status und letzter Aktivität.
- [ ] Angenommen keine Auswahl, wenn die Seite öffnet, dann gilt die Ansicht „Allgemein offen“ (offen, ohne Zuweisung).
- [ ] Angenommen die Ansicht „Meine“, wenn für den gewählten Namen eine Zammad-ID hinterlegt ist, dann erscheinen die offenen Tickets genau dieser ID, auch bei gleichlautenden oder geänderten Namen.
- [ ] Angenommen die Ansicht „Meine“, wenn kein Name gewählt oder keine ID hinterlegt ist, dann erscheint ein Hinweis statt einer Liste.
- [ ] Angenommen „Alle offenen“ oder „Geschlossen“, wenn gewählt, dann erscheinen die entsprechenden Tickets. „Offen“ heißt: nicht `closed` oder `merged`; wartende Tickets zählen dazu.

### Filter und Sortierung
- [ ] Angenommen eine Ansicht, wenn zusätzlich Filter gewählt werden, dann wirken sie kombiniert.
- [ ] Angenommen Filter und Sortierung, wenn weitergeblättert wird, dann bleiben sie erhalten.
- [ ] Angenommen eine von Hand veränderte Adresse (unbekannte Werte, falsche Form, Seite außerhalb), wenn sie aufgerufen wird, dann erscheint die Liste ohne Fehler.
- [ ] Angenommen eine Spalte aus der festen Liste (letzte Aktivität, Nummer, Status, Erstellt), wenn danach sortiert wird, dann stimmt die Reihenfolge; Standard ist letzte Aktivität, neueste zuerst.

### Klick und Bestand
- [ ] Angenommen ein Ticket in der Liste, wenn es angeklickt wird, dann öffnet die vorhandene Ticketseite.
- [ ] Angenommen Zammad ist nicht erreichbar, wenn die Startseite aufgerufen wird, dann erscheint sie normal, denn sie liest nur aus der Datenbank.
- [ ] Angenommen das Eingabefeld, wenn eine Nummer eingegeben wird, dann funktioniert es wie bisher.

### Synchronisierungsstand
- [ ] Angenommen `not_found` oder `access_denied`, wenn eine der vier Ansichten geöffnet wird, dann fehlt das Ticket dort; ein Hinweis nennt die Anzahl und verlinkt auf die Filterung `sync=problem`.
- [ ] Angenommen `sync_failed`, wenn das Ticket in der Liste erscheint, dann trägt es den Hinweis „Stand evtl. veraltet“.
- [ ] Angenommen der Marker liegt mehr als 15 Minuten zurück oder der letzte Vollabgleich schlug fehl, wenn die Seite öffnet, dann erscheint ein Warnhinweis; sonst nur „Stand: vor x Minuten“.

## Edge Cases
- **Leere Ansicht:** eigener Leerzustand, passend zur Lage (noch keine Daten oder keine Treffer).
- **Noch kein Lauf:** Statt eines Stands steht, dass noch kein Abgleich gelaufen ist.
- **Filter ohne Daten:** Kanal und Klassifikation sind zunächst leer; die Werte stammen aus den Daten, sodass das Filterfeld nur „nicht klassifiziert“ bzw. „unbekannt“ anbietet.
- **Betreff mit HTML:** wird escaped.
- **Handybreite:** Die Tabelle scrollt seitlich wie die übrigen Übersichten.

## Technical Requirements
- SQL-Paginierung, 25 je Seite; keine Zammad-Aufrufe beim Seitenaufruf.
- Sichtbar für alle mit Namensauswahl, Schutz bleibt VPN plus gemeinsames Passwort (PROJ-15 bringt SSO).

## Open Questions
- [ ] Die optimale Gestaltung zeigt sich im Betrieb.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Sichtbar für alle, nicht nur Admins | „Meine offenen Tickets“ wäre sonst sinnlos; Kundenname wird nicht gespeichert | 2026-10-31 |
| „Offen“ schließt Wartende ein | Status ist sichtbar und filterbar; im Betrieb leicht zu ändern | 2026-10-31 |
| Ausgeblendete Tickets werden gezählt | Nichts soll still verschwinden | 2026-10-31 |

### Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Ansichten als benannte Filtersätze | Keine fest programmierten Listen; lassen sich später speichern | 2026-10-31 |
| Muster aus `AnalysisOverview`, nicht aus `TicketOverview` | SQL-Paginierung statt alle Zeilen in PHP | 2026-10-31 |
| Startseite bekommt einen Controller, Routenname bleibt | `tickets.analyze` wird an vielen Stellen verwendet | 2026-10-31 |

---

## Tech Design (Solution Architect)

```
Startseite
├── Änderungshinweis, Eingabefeld (schmal, unverändert)
└── Ticketliste
    ├── Ansichten: Allgemein offen, Meine, Alle offenen, Geschlossen
    ├── Filter: Status, Mitarbeiter, Kanal, Klassifikation, CSA-Status
    ├── Stand des Abgleichs, ggf. Warnung und Hinweis auf nicht lesbare Tickets
    └── Tabelle mit Sortierung und Seiten
```

**Daten:** kommen ausschließlich aus `tracked_tickets` und `zammad_sync_runs` (PROJ-66). Filter und Ansicht stecken in der Adresse, sodass sie teilbar sind und sich später als gespeicherte Ansicht ablegen lassen.

## Implementation Notes (2026-10-31)
Gebaut: `StartPageController` (Routenname `tickets.analyze` unverändert), `TicketListRequest`, `TicketListFilter`, `TicketList`, Komponente `x-ticket.list`, Zuordnung `staff.zammad_user_ids` mit `StaffDirectory::zammadUserId()`. Tests: `tests/Feature/PROJ-67-TicketListTest.php`.

Die Zammad-IDs sind gegen das echte System ermittelt (2026-10-31; je Name genau ein Konto mit Agent-Rolle): Cara 7247, Etienne 3, Johannes 5, Kerstin 59, Nele 99, Thomas 101. Offene Tickets gehören zusätzlich den Besitzern 4 und 1592, die nicht in der CSA-Namensliste stehen; sie erscheinen im Mitarbeiter-Filter.

Ein gewählter Mitarbeiter ersetzt den Besitzer der Ansicht, damit sich Ansicht und Filter frei kombinieren lassen.

Nachtrag auf Wunsch des Auftraggebers: Der Navigationspunkt der Startseite heißt jetzt „Tickets“; der veraltete Hinweisbereich „So geht es weiter“ ist entfernt.

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
