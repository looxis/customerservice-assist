# PROJ-89: Verkaufskanal im Vorschlagsformular

## Status: In Progress
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
| Kein neues Stammdaten-Modell: Die wählbaren Kanäle ergeben sich aus den aktiven Kundengruppen und ihrem Kanal | Kanäle, Gruppen und ihre Zuordnung sind seit PROJ-50 in den Einstellungen pflegbar; ein Kanal ohne aktive Gruppe verschwindet von selbst aus dem Dropdown | 2026-11-16 |
| Der gewählte Kanal überschreibt für diesen einen Vorschlag den Kanal der Kundengruppe, an der einen Stelle, die heute schon über den Geltungsbereich entscheidet | Wissensauswahl, Router, Arbeitsabläufe und ProductForge arbeiten unverändert weiter; kein zweiter Prüfweg | 2026-11-16 |
| Neues Formularfeld und Prüfung in einem gemeinsamen Baustein für Ticket und „Frage stellen“ | Beide Formulare nutzen schon denselben oberen Teil; so bleibt das Verhalten gleich | 2026-11-16 |
| Kanal am Ticket in der vorhandenen Spalte, dazu eine neue Spalte für die Herkunft (Mitarbeiter, Bestellung, Absender) | Nur so kann die Wahl des Mitarbeiters Vorrang vor der automatischen Erkennung behalten; PROJ-66 hat die Herkunftsangabe bereits vorgesehen | 2026-11-16 |
| Automatisches Speichern beim Öffnen der Ticketseite, nicht im Hintergrund-Abgleich | Bestellung und Absender liegen dort ohnehin vor; keine zusätzlichen Abrufe bei EOCS oder Zammad | 2026-11-16 |
| „Globus“ und „ehemals qobos“ kommen als Standardwerte mit; sind die Einstellungen schon von Hand gespeichert, ergänzt sie ein einmaliger Datenschritt, Gruppe direkt aktiv | Sonst erschiene die Gruppe auf dem Produktivsystem nicht bzw. erst nach der Freigabe-Prüfung „in Vorbereitung“ | 2026-11-16 |
| Schlüssel des Kanals `qobos`, der Gruppe `globus`; nur die Namen lauten „ehemals qobos“ und „Globus“ | Schlüssel stehen im Kopf der Wissensdateien und sind nach dem Speichern fest; Namen bleiben änderbar | 2026-11-16 |

---

## Tech Design (Solution Architect)

### Überblick
Es braucht Oberfläche und Backend, aber kein neues Datenmodell für Kanäle: Kanäle, Kundengruppen und die Zuordnung „Gruppe gehört zu Kanal“ gibt es seit PROJ-50 in den Einstellungen. Neu sind das Formularfeld, die Vorbelegung, die Wirkung auf die Wissensauswahl und das Speichern am Ticket.

### Aufbau der Oberfläche
```
Vorschlag erstellen (Ticket)  /  Frage stellen
+-- Gemeinsamer oberer Teil (vorhandener Baustein „Kundengruppe und Produkt“)
|   +-- NEU: Verkaufskanal (Pflicht-Dropdown, Vermerk zur Vorbelegung)
|   +-- Kundengruppe (wie bisher, wird vom Kanal gesetzt, bleibt änderbar)
|   |   +-- NEU: gelber Hinweis, wenn Gruppe und Kanal nicht zusammenpassen
|   +-- Produkt (unverändert)
+-- Rest des Formulars (unverändert, auch Reklamation)
+-- Zeile „Erstellt mit“: zusätzlich der Kanal

Startseite, Ticketübersicht
+-- Spalte „Kanal“: zeigt jetzt den Namen des gespeicherten Kanals
+-- Filter „Kanal“: vorhandene Werte und „unbekannt“ (Filter existiert bereits)

Einstellungen (vorhandene Seiten, keine neue Oberfläche)
+-- Vertriebskanäle: neuer Eintrag „ehemals qobos“, Namen wie im Dropdown
+-- Kundengruppen: neue Gruppe „Globus“
```

### Daten
- **Kanäle im Dropdown:** jeder Kanal, zu dem es eine aktive Kundengruppe gibt, mit dem Namen aus den Einstellungen; dazu „Noch unklar“. Die Gruppe eines Kanals ist die erste aktive Gruppe mit diesem Kanal.
- **Am Vorschlag gespeichert:** der gewählte Kanal, zusammen mit den übrigen Formularangaben (Protokoll, Vorbelegung beim nächsten Vorschlag, „Erstellt mit“).
- **Am Ticket gespeichert** (Tabelle der Ticketübersicht aus PROJ-66): der Kanalschlüssel in der vorhandenen Spalte und neu seine Herkunft: vom Mitarbeiter gewählt, aus der Bestellung oder aus dem Absender. Automatisch erkannte Werte überschreiben nie eine Wahl des Mitarbeiters.
- **Keine Kundendaten:** Die Absenderadresse wird nur geprüft, nicht gespeichert.

### Ablauf
1. Ticketseite öffnet: Die App bestimmt den Kanal in der Reihenfolge frühere Wahl, Bestellung, Amazon-Absender. Ist am Ticket noch keiner gespeichert, wird der erkannte Kanal dort abgelegt.
2. Formular: Der Kanal ist vorbelegt oder steht auf „Bitte wählen“. Die Wahl setzt die Gruppe im Browser sofort um, ohne Neuladen.
3. Abschicken: Der Server prüft, dass der Kanal wählbar ist. Für die Wissensauswahl gilt die Kundenart der Gruppe und der gewählte Kanal.
4. Nach dem Vorschlag: Kanal am Ticket als „vom Mitarbeiter gewählt“ gespeichert.

### Technische Entscheidungen in Kürze
- **Eine Stelle für den Geltungsbereich:** Der gewählte Kanal wird dort eingesetzt, wo heute die Kundengruppe über Kundenart und Kanal entscheidet. Alles Nachgelagerte (gezielter Abruf, Router, Arbeitsabläufe, Wissenslücken, ProductForge) bleibt unverändert.
- **Wissensprüfung:** Der neue Kanal gehört zur Liste der gültigen Kanäle; die bestehende Prüfung, dass jedes Dokument für mindestens eine Gruppe erreichbar ist, bleibt gültig.
- **Reaktion im Formular mit Alpine.js** im vorhandenen Baustein, keine neue Seite, kein zusätzlicher Serveraufruf.
- **Eine Datenbankänderung:** die Herkunftsspalte am Ticket. Keine neuen Tabellen.

### Betroffene Bereiche
- Formularbaustein „Kundengruppe und Produkt“, Formulare für Ticket und „Frage stellen“ samt ihrer Eingabeprüfung
- Vorbelegung im Analyse-Panel (heute: Gruppe aus dem Bestellkanal)
- Wissensauswahl (Kundengruppe mit gewähltem Kanal)
- Ticketseite (automatisches Speichern), Ticketübersicht (Kanalname)
- Standardwerte und Einstellungen für Kanäle und Gruppen

### Abhängigkeiten
Keine neuen Pakete.

### Risiken
- Das getestete Reklamationsformular teilt den oberen Formularteil; seine Tests laufen bei der Umsetzung mit.
- Bestehende Tests schicken keinen Kanal mit. Für ältere gespeicherte Vorschläge und beim Fortsetzen nach interner Klärung wird der Kanal aus der Gruppe abgeleitet.

## Implementation Notes (2026-11-16)
Oberfläche und Backend in einem Schritt umgesetzt.

- **Kanäle und Gruppe:** `config/knowledge.php` kennt den Kanal `qobos` („ehemals qobos“) und die Gruppe `globus` („Globus“, Kundenart `b2b-reseller`). Die Kanalnamen lauten jetzt looxis.de, Amazon, fachhaendler.looxis.de, ehemals qobos, LOOXIS Pro, API (masterpics). Eine Migration ergänzt beides in bereits gespeicherten Einstellungen und benennt Kanäle um, die noch ihren alten Standardnamen tragen.
- **Kanal-Logik:** neue Klasse `App\Knowledge\SalesChannels` (wählbare Kanäle aus den aktiven Gruppen, Gruppe je Kanal, Erkennung aus Bestellung und Amazon-Absender, Speichern am Ticket). `CustomerGroup::withChannel()` setzt den gewählten Kanal für den einen Vorschlag ein.
- **Formular:** Feld `verkaufskanal` im gemeinsamen Baustein `x-analysis.scope-fields` (Ticket und „Frage stellen“), Alpine setzt die Gruppe, gelber Hinweis bei Abweichung. Prüfung im gemeinsamen Trait `ChoosesSalesChannel`.
- **Ticket:** neue Spalte `tracked_tickets.channel_source` (`staff`, `order`, `sender`). Die Ticketseite speichert den erkannten Kanal, „Vorschlag erstellen“ die Wahl des Mitarbeiters. Die Startseite zeigt den Kanalnamen in Spalte und Filter.
- **Protokoll:** Kanal in den Formularangaben (`inputs.verkaufskanal`) und in den Kenndaten (`meta.sales_channel`) der Analyse; die Zeile „Erstellt mit“ nennt ihn.
- **Tests:** `tests/Feature/PROJ-89-SalesChannelTest.php` (27 Tests). Vier bestehende Tests an sieben Kundengruppen bzw. die neue Zeile „Erstellt mit“ angepasst.

Abweichungen von der Spec:
- Das Feld ist im Formular Pflicht (leer oder unbekannt wird abgelehnt). Eine Anfrage ganz ohne das Feld (Fortsetzen eines Vorschlags aus der Zeit vor dem Kanal) übernimmt den Kanal der Kundengruppe.
- Im Testmodus mit zurückgespultem Ticket wird kein Kanal am Ticket gespeichert, wie bei der gemerkten Kundengruppe.
- Ein automatisch erkannter Kanal wird durch eine später geladene Bestellung mit anderem Kanal ersetzt; nur die Wahl eines Mitarbeiters ist geschützt.

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
