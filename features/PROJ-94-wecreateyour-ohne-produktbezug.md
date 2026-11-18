# PROJ-94: WeCreateYour-Fälle ohne Produktbezug

## Status: In Review
**Created:** 2026-11-18
**Last Updated:** 2026-11-18

## Dependencies
- Requires: PROJ-89 (Verkaufskanal im Vorschlagsformular; Kanal und Kundengruppe „WeCreateYour“ mit Kundenart `b2b-digital`, Erkennung am Betreff)
- Requires: PROJ-42 (Pflichtangaben und Produktsuche, „Kein Produktbezug“)
- Requires: PROJ-90, PROJ-86, PROJ-93, PROJ-64 (Formularwahl und geführte Formulare, DHL-Retoure)
- Requires: PROJ-7, PROJ-38 (Bestellung aus EOCS, Produktinformationen aus ProductForge)
- Requires: PROJ-82, PROJ-85 (automatische Antworten)

## Hintergrund
WeCreateYour.com ist eine digitale Serviceplattform: Wir erbringen dort digitale Dienstleistungen für Geschäftskunden in Deutschland, der EU und international. Es gibt keine physischen Produkte und keinen Versand. In EOCS stehen zwar Kundendaten und Rechnungen, die Geschäftsvorgänge aber nicht. Kanal, Kundengruppe und Kundenart (`b2b-digital`) gibt es seit PROJ-89; die App erkennt den Kanal am Betreff (z. B. „Contact Form @ WeCreateYour.com“, Ticket 2140510).

Kein bisheriges Wissensdokument ist auf WeCreateYour eingeschränkt. Dokumente, die für alle gelten, bleiben gültig; Dokumente, die auf andere Kundenarten oder Kanäle eingeschränkt sind, gelten nicht. Heute würde CSA trotzdem Bestellnummern suchen, EOCS abfragen, ein Produkt verlangen, Produkte aus Kundenbegriffen vorschlagen und Versandformulare anbieten. Das passt nicht und soll entfallen.

„WeCreateYour-Fall“ heißt in dieser Spec: Im Formular ist der Verkaufskanal „WeCreateYour“ gewählt oder vorbelegt.

## User Stories
- Als Mitarbeiter will ich bei einem WeCreateYour-Ticket kein Produkt auswählen müssen, weil es dort keine Produkte gibt.
- Als Mitarbeiter will ich bei WeCreateYour keine Bestellungen, Sendungen oder Versandhinweise aus EOCS sehen, damit mich nichts Unpassendes in die Irre führt.
- Als Mitarbeiter will ich nur die Formulare angeboten bekommen, die für digitale Dienstleistungen passen (Freitext, Technischer Support).
- Als Mitarbeiter will ich, dass die KI weiß, dass es sich um einen Geschäftskunden einer digitalen Serviceplattform handelt, damit sie Regeln zu Ware und Versand nicht blind anwendet.
- Als Admin will ich den Kontext-Hinweis für die KI in den Einstellungen anpassen können, wenn sich das Angebot von WeCreateYour ändert.
- Als Admin will ich, dass die automatischen Antworten WeCreateYour-Tickets nicht anschreiben, weil ihre Texte auf Bestellungen und Versand zugeschnitten sind.

## Out of Scope
- Wissensdokumente für WeCreateYour schreiben: erfolgt über den üblichen Weg (`/knowledge`); bis dahin gilt nur das Wissen für alle.
- Rechnungen oder Kundendaten von WeCreateYour aus EOCS anzeigen: bewusst ausgeschlossen (EOCS ganz aus); bei Bedarf eigene Spec.
- Weitere Erkennungsmerkmale außer dem Betreff (z. B. Absenderadresse, Zammad-Gruppe): kann über `subject_channels` oder eine Folgespec ergänzt werden.
- Änderungen an der Ticket-Klassifikation (PROJ-80): läuft für WeCreateYour-Tickets weiter wie bisher.
- Eigene Antwortsprache oder Übersetzungslogik: die vorhandene Übersetzung (PROJ-28) und die Antwort in Kundensprache gelten unverändert.
- Andere Kanäle ohne Produktbezug: die Regel gilt nur für WeCreateYour; eine allgemeine Kanal-Eigenschaft „ohne Produkte“ ist eine Entscheidung für `/architecture`, aber kein Muss.

## Acceptance Criteria

### Produktbereich
- [ ] Angenommen im Formular ist der Verkaufskanal „WeCreateYour“ gewählt, wenn der Mitarbeiter das Formular sieht, dann sind Produktsuche, Produktchips, „Produkt ohne hinterlegtes Wissen“ und „Kein Produktbezug“ ausgeblendet und der Vorschlag gilt als Fall ohne Produktbezug.
- [ ] Angenommen der Kanal „WeCreateYour“ ist gewählt, wenn der Mitarbeiter „Vorschlag erstellen“ klickt, dann verlangt die App keine Produktantwort und meldet keinen Fehler zum Produkt.
- [ ] Angenommen der Ticket- oder Fragetext enthält Kundenbegriffe eines Produkts (z. B. „Glasfoto“), wenn der Kanal „WeCreateYour“ gewählt ist, dann schlägt die App kein Produkt vor.
- [ ] Angenommen der Kanal „WeCreateYour“ ist gewählt, wenn der Vorschlag erstellt wird, dann gehen keine Produktinformationen aus ProductForge in den Prompt und das Ergebnis zeigt keine Hinweise zu fehlenden, unbekannten oder anderen Produkten (PROJ-44, PROJ-45) und zählt keine Produktfragen (PROJ-57).
- [ ] Angenommen der Mitarbeiter wechselt den Kanal von „WeCreateYour“ auf einen anderen Kanal, wenn er das Formular weiter ausfüllt, dann erscheint der Produktbereich wieder mit der Pflicht zur Produktantwort; umgekehrt verschwindet er, und schon gewählte Produkte werden nicht gesendet.
- [ ] Angenommen „Frage stellen“ ohne Ticket, wenn der Kanal „WeCreateYour“ gewählt ist, dann gilt für den Produktbereich dasselbe wie am Ticket.

### EOCS
- [ ] Angenommen ein Ticket ist ein WeCreateYour-Fall, wenn die Ticketseite lädt, dann sucht die App keine Bestellnummern im Text, fragt EOCS nicht ab und zeigt keine Bestell-Statuszeile und kein Feld für eine Bestellnummer.
- [ ] Angenommen ein WeCreateYour-Fall, wenn der Vorschlag erstellt wird, dann gehen keine Bestell-, Positions-, Sendungs- oder Versandalter-Angaben (PROJ-79) an die KI.
- [ ] Angenommen der Mitarbeiter wählt den Kanal „WeCreateYour“ erst im Formular (nicht am Betreff erkannt), wenn er den Vorschlag erstellt, dann werden schon geladene Bestellungen nicht an die KI gesendet; der Bestellbereich ist ausgeblendet, solange „WeCreateYour“ gewählt ist.
- [ ] Angenommen der Mitarbeiter wechselt bei einem am Betreff erkannten WeCreateYour-Ticket auf einen anderen Kanal und speichert die Wahl, wenn er die Seite neu lädt, dann arbeitet die App wieder mit Bestellnummern und EOCS wie bei jedem anderen Kanal.

### Formulare
- [ ] Angenommen der Kanal „WeCreateYour“ ist gewählt, wenn der Mitarbeiter die Formularwahl öffnet, dann stehen nur „Technischer Support“ und „Freitext“ zur Wahl; „Reklamation“ und „Neuversand“ fehlen.
- [ ] Angenommen ein WeCreateYour-Fall mit der Kategorie Reklamation, wenn das Formular vorbelegt wird, dann ist „Freitext“ gewählt statt „Reklamation“.
- [ ] Angenommen ein WeCreateYour-Fall, wenn der Mitarbeiter die Ergebnisansicht sieht, dann wird kein DHL-Retourenlabel angeboten.
- [ ] Angenommen der Mitarbeiter hatte „Reklamation“ oder „Neuversand“ gewählt und wechselt dann auf „WeCreateYour“, wenn das Formular sich anpasst, dann springt die Formularwahl auf „Freitext“ und die Felder des alten Formulars werden nicht gesendet.

### Hinweis an die KI
- [ ] Angenommen ein WeCreateYour-Fall, wenn der Vorschlag erstellt wird, dann enthält der Prompt einen Kontext-Hinweis: Geschäftskunde der Serviceplattform WeCreateYour.com (Deutschland, EU, international), digitale Dienstleistungen, keine physischen Produkte, kein Versand; Regeln zu Ware, Versand, Rücksendung oder Reklamation einer Ware nur anwenden, wenn sie sinngemäß auf eine digitale Dienstleistung passen.
- [ ] Angenommen ein Admin öffnet die Einstellungen, wenn er den Text des WeCreateYour-Hinweises ändert und speichert, dann verwendet der nächste Vorschlag den neuen Text; ohne gespeicherten Text gilt der Standardtext.
- [ ] Angenommen ein Admin leert den Hinweis-Text und speichert, wenn ein Vorschlag erstellt wird, dann geht kein Kontext-Hinweis an die KI (bewusstes Abschalten ist möglich).

### Automatische Antworten
- [ ] Angenommen ein neues Ticket wird am Betreff als WeCreateYour erkannt, wenn die WISMO-Automatik (PROJ-82) oder die Vorab-Antwort bei Reklamation (PROJ-85) das Ticket prüft, dann sendet sie nichts und vermerkt im Protokoll „übersprungen: WeCreateYour“.
- [ ] Angenommen ein WeCreateYour-Ticket, wenn die Ticket-Klassifikation (PROJ-80) läuft, dann ordnet sie das Ticket wie bisher ein.

### Anzeige
- [ ] Angenommen ein WeCreateYour-Fall, wenn der Mitarbeiter das Ergebnis sieht, dann nennt die Zeile „Erstellt mit“ den Kanal „WeCreateYour“ und kein Produkt.

## Edge Cases
- **Betreff nennt WeCreateYour, aber der Kunde bezieht sich auf eine LOOXIS-Bestellung:** Der Mitarbeiter stellt den Kanal um; nach dem Speichern gilt die normale Bearbeitung mit EOCS (siehe Akzeptanzkriterium zum Kanalwechsel).
- **Bestellnummer-ähnliche Zeichenfolge im WeCreateYour-Ticket:** wird nicht gesucht und nicht abgefragt, damit keine falsche Bestellung aus EOCS auftaucht.
- **Älterer Vorschlag mit Produkt, danach Kanal auf WeCreateYour gestellt:** der alte Vorschlag bleibt unverändert sichtbar; ein neuer Vorschlag geht ohne Produkt.
- **Gruppe „WeCreateYour“ in den Einstellungen deaktiviert:** der Kanal ist nicht wählbar (PROJ-89); Erkennung am Betreff belegt nichts vor, die App arbeitet wie bei jedem anderen Ticket.
- **Wissensdokument nennt `b2b-digital` oder `wecreateyour` künftig:** wird normal ausgewählt; der Kontext-Hinweis bleibt zusätzlich.
- **Fremdsprachiges WeCreateYour-Ticket:** Übersetzung und Antwortsprache wie bisher (international ist der Normalfall).
- **Hinweis-Text sehr lang:** Obergrenze wie bei anderen Texten der Einstellungen; zu lange Eingaben werden mit Fehlermeldung abgewiesen.

## Technical Requirements (optional)
- Keine zusätzlichen externen Abrufe; bei WeCreateYour entfallen EOCS- und ProductForge-Abrufe ganz.
- Bestehende Tests für die anderen Kanäle bleiben unverändert grün.

## Open Questions
- [ ] Sollen Rechnungen von WeCreateYour-Kunden aus EOCS später doch angezeigt werden (z. B. für Rechnungsfragen)? Vorerst nicht (EOCS ganz aus).
- [ ] Gibt es außer dem Betreff verlässliche Merkmale (Absenderadresse, Zammad-Gruppe), an denen WeCreateYour-Tickets erkannt werden?

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Eigene Kundenart `b2b-digital` statt `b2b-reseller`/`b2b-whitelabel` | WeCreateYour sind ausschließlich Geschäftskunden, aber kein vorhandenes Dokument gilt für sie; mit eigener Kundenart und eigenem Kanal greift nur Wissen für alle | 2026-11-18 |
| Produktbereich bei WeCreateYour ausblenden statt nur „Kein Produktbezug“ vorzubelegen | Es gibt dort nie ein Produkt; jede Produktfrage wäre Fehlbedienung | 2026-11-18 |
| EOCS ganz aus, auch für Kundendaten und Rechnungen | Geschäftsvorgänge fehlen in EOCS; gefundene Bestellnummern würden eher in die Irre führen | 2026-11-18 |
| Fester, in den Einstellungen änderbarer Kontext-Hinweis an die KI | Allgemeines Wissen handelt oft von Ware und Versand; der Hinweis verhindert, dass die KI es blind anwendet | 2026-11-18 |
| Automatische Antworten (PROJ-82, PROJ-85) überspringen WeCreateYour | Ihre Texte setzen Bestellung und Versand voraus | 2026-11-18 |
| Nur Freitext und Technischer Support als Formulare | Reklamation, Neuversand und DHL-Retoure setzen Ware und Versand voraus | 2026-11-18 |

### Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Kanal-Eigenschaften („ohne Produkte“, Betreff-Stichwörter, KI-Hinweis) statt Abfragen auf `wecreateyour` | Eine Stelle entscheidet; ein weiterer Kanal dieser Art ist nur eine Einstellung | 2026-11-18 |
| Speicherung in der vorhandenen Kanal-Liste der Einstellungen (PROJ-50), keine Migration | Kanäle liegen schon dort; Voreinstellung aus der Konfiguration wie bei allen Werten | 2026-11-18 |
| KI-Hinweis als Kanal-Text statt als weitere Prompt-Datei | Prompts fallen bei leerem Text auf die Datei zurück; der Hinweis soll leer = aus sein und gehört inhaltlich zum Kanal | 2026-11-18 |
| `subject_channels` aus PROJ-89 wandert in die Kanal-Eigenschaft „Erkennung im Betreff“ | Eine Quelle für die Erkennung, in den Einstellungen änderbar (beantwortet teilweise die offene Frage nach weiteren Merkmalen) | 2026-11-18 |
| Fall läuft intern als „Kein Produktbezug“ ohne Bestellungen | Nutzt die vorhandenen Wege von PROJ-42/44/45/57/79 statt neuer Sonderfälle | 2026-11-18 |
| Ticketseite bestimmt den Kanal vor dem EOCS-Abruf (Mitarbeiter-Wahl, sonst Betreff) | Nur so kann EOCS ganz entfallen; Bestellungen als Quelle scheiden hier naturgemäß aus | 2026-11-18 |
| Server prüft unabhängig von der Oberfläche | Ausgeblendete Felder dürfen nicht durch alte Formularstände oder Manipulation doch ankommen | 2026-11-18 |

---

## Tech Design (Solution Architect)

### Grundidee: Eigenschaften des Verkaufskanals statt Sonderfall „WeCreateYour“
Statt an vielen Stellen „wenn Kanal = wecreateyour“ abzufragen, bekommt jeder Verkaufskanal in den Einstellungen (Werte → Vertriebskanäle) drei zusätzliche Angaben. Für WeCreateYour sind sie vorbelegt, für alle anderen Kanäle leer. Alle betroffenen Stellen fragen nur noch: „Ist der Kanal ohne Produkte und Bestellungen?“ Kommt später ein weiterer solcher Kanal dazu, ist das eine Einstellung, keine Programmierung.

### A) Wo sich etwas ändert (Überblick)
```
Einstellungen → Werte → Vertriebskanäle
+-- je Kanal: Schlüssel, Name (wie bisher)
+-- NEU: „Ohne Produkte und Bestellungen“ (Haken)
+-- NEU: „Erkennung im Betreff“ (Stichwörter, z. B. wecreateyour)
+-- NEU: „Hinweis an die KI“ (Text, leer = kein Hinweis)

Ticketseite
+-- Kopf: Bestell-Statuszeile / Bestellnummer-Feld → entfällt bei Kanal ohne Produkte
+-- Formular „Vorschlag erstellen“
|   +-- Verkaufskanal (wie bisher, vorbelegt aus dem Betreff)
|   +-- Formularwahl → nur „Technischer Support | Freitext“
|   +-- Produktbereich → ausgeblendet, nicht gesendet
|   +-- Bestellbereich → ausgeblendet, nicht gesendet
+-- Ergebnis: kein DHL-Retourenlabel, keine Produkt-Hinweise

Frage stellen → Produktbereich wie am Ticket

Hintergrund (neue Tickets)
+-- WISMO-Automatik (PROJ-82) → überspringt mit Grund
+-- Vorab-Antwort Reklamation (PROJ-85) → überspringt mit Grund
```

### B) Daten (in Alltagssprache)
Keine neue Tabelle, keine Migration. Die Kanäle liegen schon heute als Liste in den gespeicherten Einstellungen (PROJ-50). Jeder Eintrag bekommt drei Felder:
- **Ohne Produkte und Bestellungen:** ja/nein (Standard nein)
- **Erkennung im Betreff:** Liste von Stichwörtern, ohne Groß/Klein (ersetzt die Liste `subject_channels` aus PROJ-89, die in die Kanal-Voreinstellungen wandert)
- **Hinweis an die KI:** Text, höchstens 2.000 Zeichen

Die Voreinstellung für WeCreateYour steht in der App-Konfiguration (ja / `wecreateyour` / Standardtext unten). Solange nichts gespeichert ist, gilt sie; wird die Liste gespeichert, gelten die gespeicherten Werte wie bei allen anderen Werten.

Standardtext „Hinweis an die KI“ für WeCreateYour:
> Der Kunde ist ein Geschäftskunde der Serviceplattform WeCreateYour.com (Deutschland, EU oder international). Wir erbringen dort digitale Dienstleistungen; es gibt keine physischen Produkte, keine Produktion von Ware und keinen Versand. Regeln zu Ware, Versand, Rücksendung oder Reklamation einer Ware wendest du nur an, wenn sie sinngemäß auf eine digitale Dienstleistung passen. Frage nicht nach Bestellnummern, Sendungen oder Fotos eines Produkts.

### C) Ablauf und Zuständigkeiten
1. **Kanal beim Laden der Ticketseite:** Die bestehende Kanal-Logik (PROJ-89) bekommt eine Vorab-Bestimmung ohne Bestellungen: zuerst die gespeicherte Wahl eines Mitarbeiters, sonst ein Betreff-Stichwort. Ist dieser Kanal „ohne Produkte“, überspringt die Ticketseite Bestellnummer-Erkennung und EOCS-Abruf ganz. Hat ein Mitarbeiter einen anderen Kanal gespeichert, läuft alles wie bisher.
2. **Formular (Alpine.js, keine neue Seite):** Jede Kanal-Option trägt das Merkmal „ohne Produkte“. Das Formular blendet danach Produktbereich, Bestellbereich und die Formulare Reklamation/Neuversand sofort aus oder wieder ein; ausgeblendete Felder werden abgeschaltet und daher nicht gesendet. War Reklamation oder Neuversand gewählt, springt die Wahl auf „Freitext“.
3. **Prüfung beim Absenden (Form Requests):** Ticket- und Frage-Formular verlassen sich nicht auf die Oberfläche: Bei einem Kanal ohne Produkte gilt der Fall als „Kein Produktbezug“, Produkt- und Bestellangaben werden verworfen, und ein Formular, das Ware voraussetzt, wird abgewiesen.
4. **Formularliste (GuidedForms):** Reklamation und Neuversand werden als „setzt Ware und Versand voraus“ gekennzeichnet; die Liste der Formulare kann so je Kanal gefiltert werden. Die Vorbelegung nach Kategorie fällt auf „Freitext“ zurück.
5. **Analyse:** Weil der Fall intern als „Kein Produktbezug“ ohne Bestellungen läuft, entfallen ProductForge-Infos, Versandalter-Hinweise (PROJ-79) und die Produkt-Hinweise im Ergebnis (PROJ-44, PROJ-45, PROJ-57) über die vorhandenen Wege. Neu ist nur: Hat der Kanal einen „Hinweis an die KI“, wird er als eigener Abschnitt „Verkaufskanal“ in den Prompt gesetzt. Der Hinweis wird im Protokoll der Analyse mit seiner Fassung festgehalten, damit nachvollziehbar bleibt, was die KI bekommen hat.
6. **DHL-Retourenlabel:** wird bei einem Kanal ohne Produkte nicht angeboten (ohnehin ohne Bestellung, wird aber ausdrücklich ausgeschlossen).
7. **Automatische Antworten:** Die Prüfung „warum übersprungen“ von PROJ-82 und PROJ-85 bekommt einen weiteren Grund: Betreff-Stichwort eines Kanals ohne Produkte → „Übersprungen: Kanal WeCreateYour ohne Produkte und Versand“. Neue Tickets haben noch keine Mitarbeiter-Wahl, daher zählt nur der Betreff.

### D) Abhängigkeiten
Keine neuen Pakete.

### E) Tests
- Ticketseite: WeCreateYour-Betreff → kein EOCS-Aufruf (gefälschte EOCS-Antwort wird nie angefragt), kein Produktbereich, Formularwahl nur zwei Einträge.
- Gespeicherte Mitarbeiter-Wahl eines anderen Kanals → EOCS läuft wieder.
- Absenden mit Kanal WeCreateYour ohne Produkt → kein Fehler; mit mitgeschicktem Produkt/Reklamation → Produkt verworfen bzw. Formular abgewiesen.
- Prompt enthält den Hinweis; leerer Hinweis → kein Abschnitt; geänderter Hinweis wird verwendet.
- PROJ-82 und PROJ-85 überspringen mit Grund; Klassifikation unverändert.
- Einstellungsseite: neue Felder speichern, Längen prüfen, Voreinstellung ohne Speichern.
- Bestehende Tests der anderen Kanäle bleiben grün.

## Implementation Notes (Frontend + Backend, 2026-11-18)
- Kanal-Profil in den Einstellungen (Werte → Vertriebskanäle): Spalten „Ohne Produkte“ (Haken), „Erkennung im Betreff“ (Stichwörter, je mindestens 3 Zeichen, zusammen höchstens 200 Zeichen), „Hinweis an die KI“ (höchstens 2.000 Zeichen). Voreinstellung in `config/knowledge.php` → `channel_profiles` (ersetzt `subject_channels`). Gespeicherte Zeilen ohne Profilfelder bekommen beim Lesen das Standardprofil.
- `SalesChannels`: `withoutProducts()`, `aiNote()`, `fromSubject()`, `beforeOrders()` (Mitarbeiter-Wahl, sonst Betreff); die Kanal-Optionen tragen `without_products`.
- Ticketseite: bei Kanal ohne Produkte weder Bestellnummer-Erkennung noch EOCS; der Bereich „Bestellungen“ nennt den Grund. Das Formular reagiert auf den Kanal (Alpine-Ereignis `channel-changed`): Produkt- und Bestellbereich, Reklamation/Neuversand und DHL-Option ausgeblendet und abgeschaltet; Hinweiszeile unter den Kanalfeldern.
- Absenden (Ticket und Frage): Produkte, Bestellungen und Bestelldaten werden verworfen, der Fall gilt als „Kein Produktbezug“; Reklamation/Neuversand werden mit Fehlermeldung abgewiesen. `AnalysisController` sucht und lädt keine Bestellungen.
- Prompt: Abschnitt „# Verkaufskanal“ mit dem Hinweis; im Protokoll als `meta.channel_note`.
- WISMO (PROJ-82) und Vorab-Antwort Reklamation (PROJ-85) überspringen Tickets mit Betreff-Stichwort eines Kanals ohne Produkte („Übersprungen: Verkaufskanal „WeCreateYour“ ohne Produkte und Versand.“).
- Tests: `tests/Feature/PROJ-94-ChannelWithoutProductsTest.php` (11), je ein Test in PROJ-82/PROJ-85; ein PROJ-89-Test („Bestellung vor Betreff“) entfiel, weil EOCS bei WeCreateYour nicht mehr gefragt wird. Gesamtlauf: 2.826 von 2.830 grün; die 2 Fehlschläge (PROJ-1 Versionsanzeige, PROJ-56 Seitenleiste) bestehen auch ohne diese Änderung.

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
