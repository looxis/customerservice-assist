# PROJ-89: Verkaufskanal im Vorschlagsformular

## Status: Planned
**Created:** 2026-11-16
**Last Updated:** 2026-11-16

## Dependencies
- Requires: PROJ-4, PROJ-36 (Geltungsbereich des Wissens nach Kundenart und Vertriebskanal)
- Requires: PROJ-7 (Bestellung aus EOCS, Kanal der Bestellung)
- Requires: PROJ-42 (Pflichtangaben Kundengruppe und Produkt), PROJ-16 („Frage stellen“ nutzt denselben Formularteil)
- Requires: PROJ-50 (Einstellungen für Vertriebskanäle, Kundengruppen und EOCS-Kanalnamen)
- Requires: PROJ-66, PROJ-67 (Ticket-Eingang und Startseiten-Übersicht mit der vorbereiteten Spalte „Kanal“)
- Berührt: PROJ-86 (Reklamationsformular, gemeinsamer oberer Formularteil)
- Voraussetzung für: PROJ-90 (Geführtes Formular „Technischer Support“)

## Hintergrund
Tickets werden vorab klassifiziert (PROJ-80). „Vorschlag erstellen“ soll je Kategorie geführte Eingaben bekommen, beginnend mit „Technischer Support“ (PROJ-90). Dort hängt die richtige Antwort vor allem vom Shop ab, über den der Kunde bestellt oder sich anmeldet: looxis.de, der Fachhändler-Shop, LOOXIS Pro und der abgeschaltete Shop „ehemals qobos“ haben verschiedene Zugänge und Störungen.

Heute wählt der Mitarbeiter nur die Kundengruppe; jede Gruppe hat genau einen fest hinterlegten Vertriebskanal. Die Spalte „Kanal“ der Startseiten-Übersicht (PROJ-67) ist vorbereitet, wird aber nie befüllt. Diese Spec macht den Verkaufskanal zur ersten Angabe im Formular, aus der sich die Kundengruppe ergibt.

## User Stories
- Als Mitarbeiter im Kundenservice will ich den Verkaufskanal eines Falls wählen, damit der Vorschlag das Wissen für genau diesen Shop verwendet.
- Als Mitarbeiter will ich, dass der Kanal vorbelegt ist, wenn er sich aus der Bestellung oder dem Absender ergibt, damit ich ihn nur bestätigen muss.
- Als Aushilfe will ich, dass die Kundengruppe aus dem Kanal folgt, damit ich nicht wissen muss, welche Gruppe zu welchem Shop gehört.
- Als Mitarbeiter will ich die Kundengruppe trotzdem ändern können, wenn ein Fall vom Üblichen abweicht.
- Als Mitarbeiter will ich in der Ticketübersicht der Startseite den Kanal sehen und danach filtern.
- Als Admin will ich Kanäle und Kundengruppen in den Einstellungen ergänzen und abschalten, damit ein wegfallender Kanal („ehemals qobos“) ohne Programmierung verschwindet.

## Out of Scope
- Geführtes Formular „Technischer Support“ (PROJ-90) und Formulare für weitere Kategorien.
- Überarbeitung der Wissensdateien nach Kanal (Fachhändler-Login, LOOXIS-Pro-Passwortabfrage, Hinweis zu „ehemals qobos“, Wissen für Globus): eigener späterer Schritt (PROJ-91).
- Kanal ohne Bestellung für Fachhändler und LOOXIS Pro erkennen, z. B. über eine EOCS-Abfrage (PROJ-92, Details folgen).
- Rückwirkendes Befüllen des Kanals für Tickets, die niemand öffnet.
- Änderungen an der Klassifikation (PROJ-78, PROJ-80) oder an den automatischen Antworten (PROJ-77, PROJ-82, PROJ-85).

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Kanäle und Kundengruppen
- [ ] Angenommen die App ist eingerichtet, wenn ein Mitarbeiter das Dropdown „Verkaufskanal“ öffnet, dann stehen zur Wahl: looxis.de, Amazon, fachhaendler.looxis.de, ehemals qobos, LOOXIS Pro, API (masterpics) und „Noch unklar“.
- [ ] Angenommen die Einstellungen „Kundengruppen“, wenn ein Admin sie öffnet, dann gibt es die neue Gruppe „Globus“ mit der Kundenart der Fachhändler (`b2b-reseller`) und dem Kanal „ehemals qobos“.
- [ ] Angenommen ein Admin schaltet einen Kanal oder dessen Gruppe in den Einstellungen ab, wenn ein Mitarbeiter das Formular öffnet, dann ist der Kanal nicht mehr wählbar; gespeicherte Vorschläge und Tickets zeigen ihn weiter mit Namen.
- [ ] Angenommen ein Admin legt in den Einstellungen einen neuen Kanal mit Gruppe an und gibt sie frei, wenn ein Mitarbeiter das Formular öffnet, dann ist der Kanal wählbar.

### Formular
- [ ] Angenommen ein Ticket ist geöffnet, wenn der Mitarbeiter „Vorschlag erstellen“ sieht, dann steht „Verkaufskanal“ als Pflichtfeld vor der Kundengruppe, unabhängig von der Kategorie und auch im Reklamationsformular.
- [ ] Angenommen „Frage stellen“ ist geöffnet, wenn der Mitarbeiter das Formular sieht, dann steht „Verkaufskanal“ dort ebenso als Pflichtfeld vor der Kundengruppe.
- [ ] Angenommen kein Kanal ist gewählt, wenn der Mitarbeiter das Formular abschickt, dann erscheint die Fehlermeldung „Bitte den Verkaufskanal wählen.“ und alle Eingaben bleiben erhalten.
- [ ] Angenommen der Mitarbeiter wählt einen Kanal, dann wird die Kundengruppe sofort auf die zugehörige Gruppe gesetzt: looxis.de → Privatkunde looxis.de, Amazon → Privatkunde Amazon, fachhaendler.looxis.de → Foto-Fachhändler/Reseller, ehemals qobos → Globus, LOOXIS Pro → LOOXIS-Pro, API → White-Label-Kunde masterpics, Noch unklar → Noch unklar.
- [ ] Angenommen ein Kanal ist gewählt, wenn der Mitarbeiter die Kundengruppe von Hand ändert, dann bleibt der Kanal unverändert, die Abweichung wird mit einem gelben Hinweis angezeigt und das Abschicken bleibt möglich.
- [ ] Angenommen der Kanal „Noch unklar“ ist gewählt, dann erscheint der bisherige Hinweis, dass nur Regeln für alle Kundengruppen gelten.

### Vorbelegung
- [ ] Angenommen für das Ticket wurde schon ein Kanal gewählt oder gespeichert, wenn das Formular geöffnet wird, dann ist dieser Kanal vorbelegt.
- [ ] Angenommen es gibt keine frühere Wahl und eine Bestellung aus EOCS ist geladen, deren Kanalname in den Einstellungen einem Verkaufskanal zugeordnet ist, dann ist dieser Kanal vorbelegt, mit dem Vermerk „aus dem Kanal der Bestellung“.
- [ ] Angenommen es gibt keine frühere Wahl und keine Bestellung, und die Absenderadresse des Kunden endet auf eine Amazon-Marketplace-Domain (`@marketplace.amazon.*`), dann ist „Amazon“ vorbelegt, mit dem Vermerk „aus der Absenderadresse“.
- [ ] Angenommen keine dieser Quellen trifft zu, dann steht das Feld auf „Bitte wählen“.
- [ ] Angenommen ein Kanal ist vorbelegt, dann ist auch die Kundengruppe nach derselben Zuordnung vorbelegt; eine für dieses Ticket früher von Hand gewählte Gruppe hat Vorrang.

### Wissensauswahl
- [ ] Angenommen ein Wissensdokument ist auf bestimmte Vertriebskanäle beschränkt, wenn ein Vorschlag erstellt wird, dann entscheidet der im Formular gewählte Kanal, ob es gilt; die Kundenart kommt weiter aus der Kundengruppe.
- [ ] Angenommen der Kanal „ehemals qobos“ mit der Gruppe „Globus“ ist gewählt, dann gilt Wissen für alle, Wissen für die Kundenart Fachhändler ohne Kanalbeschränkung und Wissen für den Kanal „ehemals qobos“; Wissen, das auf den Kanal fachhaendler.looxis.de beschränkt ist, gilt nicht.
- [ ] Angenommen der Kanal „Noch unklar“ ist gewählt, dann gilt kanalbeschränktes Wissen nicht.
- [ ] Angenommen ein Vorschlag wurde erstellt, dann steht der verwendete Kanal im Protokoll der Analyse und in der Zeile „Erstellt mit“.
- [ ] Angenommen ein Wissensdokument nennt den Kanal „ehemals qobos“ im Kopf, wenn die Wissensprüfung läuft, dann meldet sie dafür keinen Fehler.

### Kanal am Ticket und Startseite
- [ ] Angenommen ein Vorschlag wird für ein Ticket erstellt, dann wird der gewählte Kanal am Ticket gespeichert; „Noch unklar“ wird als leer gespeichert.
- [ ] Angenommen am Ticket ist noch kein Kanal gespeichert, wenn die Ticketseite eine Bestellung mit zugeordnetem Kanal lädt oder der Absender eine Amazon-Marketplace-Adresse ist, dann wird der Kanal ohne Zutun des Mitarbeiters am Ticket gespeichert.
- [ ] Angenommen am Ticket ist ein Kanal gespeichert, den ein Mitarbeiter gewählt hat, wenn später eine Bestellung mit anderem Kanal geladen wird, dann bleibt die Wahl des Mitarbeiters bestehen.
- [ ] Angenommen Tickets haben einen gespeicherten Kanal, wenn die Startseite geöffnet wird, dann zeigt die Spalte „Kanal“ den Namen des Kanals und der Filter „Kanal“ bietet die vorhandenen Werte sowie „unbekannt“ an.

## Edge Cases
- **Mehrere Bestellungen mit verschiedenen Kanälen:** Es zählt die erste geladene Bestellung mit zugeordnetem Kanal, wie heute bei der Kundengruppe.
- **Unbekannter EOCS-Kanalname:** keine Vorbelegung aus der Bestellung; der Admin kann den Namen in den Einstellungen zuordnen.
- **Amazon-Absender, aber Bestellung aus einem anderen Kanal:** Die Bestellung hat Vorrang vor der Absenderadresse.
- **Kanal abgeschaltet, am Ticket aber gespeichert:** Das Formular zeigt „Bitte wählen“, die Startseite zeigt den alten Kanal weiter.
- **Älterer Vorschlag ohne Kanal:** Beim Öffnen wird der Kanal aus der damals gewählten Kundengruppe abgeleitet; der alte Vorschlag selbst bleibt unverändert.
- **Fortsetzen nach interner Klärung (PROJ-39):** Der Kanal kommt wie die übrigen Angaben aus dem vorigen Vorschlag.
- **Zwei Mitarbeiter wählen für dasselbe Ticket verschiedene Kanäle:** Der zuletzt erstellte Vorschlag bestimmt den gespeicherten Kanal.
- **Ticket ohne Eintrag in der Übersicht** (älter als der Ticket-Eingang, Testticket): Der Vorschlag funktioniert, gespeichert wird nichts.
- **Kanal und Kundengruppe passen nicht zusammen** (z. B. Kanal Amazon, Gruppe LOOXIS-Pro): erlaubt, mit Hinweis; es gelten der gewählte Kanal und die Kundenart der gewählten Gruppe.

## Technical Requirements (optional)
- Die Ticketseite stellt für die automatische Speicherung keine zusätzliche Anfrage an EOCS oder Zammad; sie nutzt, was ohnehin geladen ist.
- Kanal und Kundengruppe sind keine Kundendaten; am Ticket wird nur der Kanalschlüssel gespeichert.
- Serverseitige Prüfung: nur wählbare Kanäle werden angenommen.

## Open Questions
- [ ] Wann genau entfällt „ehemals qobos“, und sollen Kanal und Gruppe „Globus“ dann nur abgeschaltet oder Globus einem anderen Kanal zugeordnet werden?
- [ ] Wie nennt EOCS den Kanal von Globus-Bestellungen (für die Zuordnung in den Einstellungen „EOCS-Kanalnamen“)?

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Eigene Spec für den Kanal, getrennt vom Formular „Technischer Support“ (PROJ-90) | Der Kanal gilt für alle Kategorien und ist allein nutzbar und prüfbar | 2026-11-16 |
| Der Kanal setzt die Kundengruppe; die Gruppe bleibt sichtbar und änderbar | Kanal und Gruppe gehören fast immer fest zusammen; Ausnahmen bleiben möglich | 2026-11-16 |
| Kanal im gemeinsamen Formularteil für alle Kategorien und für „Frage stellen“ | Einmal gebaut, gilt er überall; spätere Kategorie-Formulare müssen ihn nicht erneut einführen | 2026-11-16 |
| Kanal heißt „ehemals qobos“ und bekommt die neue Kundengruppe „Globus“ mit der Kundenart der Fachhändler | Vorgabe des Auftraggebers; Fachhändler-Wissen gilt so automatisch auch für Globus | 2026-11-16 |
| „Noch unklar“ ist als Kanal wählbar | Ohne Bestellung und mit neutralem Absender ist der Kanal oft nicht erkennbar; ein erzwungener Kanal würde falsches Wissen laden | 2026-11-16 |
| Amazon wird ohne Bestellung an der Absenderadresse `@marketplace.amazon.*` erkannt | Eindeutiges Merkmal, kein zusätzlicher Abruf nötig | 2026-11-16 |
| Kanal wird am Ticket gespeichert: beim Vorschlag und automatisch aus Bestellung oder Absender | Die Spalte der Startseite ist vorbereitet, aber leer; so füllt sie sich ohne Mehrarbeit | 2026-11-16 |
| Die Wahl des Mitarbeiters hat Vorrang vor der automatischen Erkennung | Der Mitarbeiter kennt den Fall; eine spätere Bestellung soll seine Entscheidung nicht überschreiben | 2026-11-16 |
| Abweichung zwischen Kanal und Gruppe nur als Hinweis, keine Sperre | Linie der Entschlackung (PROJ-72): Hinweise statt Blockaden | 2026-11-16 |

### Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|

---

## Tech Design (Solution Architect)
_To be added by /architecture_

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
