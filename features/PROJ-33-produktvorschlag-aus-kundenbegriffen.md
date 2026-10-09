# PROJ-33: Produktvorschlag aus Kundenbegriffen

## Status: Approved
**Created:** 2026-10-07
**Last Updated:** 2026-10-07

## Dependencies
- Requires: PROJ-3 (Knowledge Base einlesen und prüfen) – Frontmatter-Prüfung, Übersicht vergebener Werte
- Requires: PROJ-4 (Knowledge-Auswahl) – Produktvorschläge, Auswahl nach Produkt
- Requires: PROJ-6 (Zammad-Ticket laden) – bereinigte Kundennachrichten, Titel
- Requires: PROJ-9 (Fallanalyse per LLM) – Analyse-Formular mit Produktauswahl
- Berührt: PROJ-2 (Authoring-Kit) – Anleitung und KI-Skill um das neue Feld ergänzen; PROJ-32 (Testmodus) – Suche nur bis zum Schnittpunkt

## Ausgangslage
Produkte werden heute nur aus Bestellpositionen vorgeschlagen (`order_keywords`) oder von Hand gewählt. Kunden benennen Produkte aber oft anders: mit früheren Produktnamen („Viamant“), Umgangssprache („Glassteine“) oder Begriffen wie „Hologramm“. Ohne Bestellung – z. B. bei einer Anfrage vor dem Kauf oder einer Reklamation ohne Bestellnummer – muss der Mitarbeiter selbst wissen, welches Produkt gemeint ist. Aushilfen wissen das oft nicht; dann fehlt der KI das Produktwissen. Kundenbegriffe in `order_keywords` einzutragen wäre falsch: Dort lösen weit gefasste Wörter falsche Vorschläge bei Bestellpositionen aus.

## User Stories
- Als Aushilfe möchte ich, dass die App das gemeinte Produkt erkennt, wenn der Kunde einen alten Namen oder ein Synonym schreibt, damit die Analyse das richtige Produktwissen bekommt, ohne dass ich die Produkthistorie kenne.
- Als Mitarbeiter möchte ich sehen, warum ein Produkt vorgeschlagen ist („erkannt im Ticket: ‚Viamant‘“), damit ich einen Fehltreffer schnell erkenne und den Haken entferne.
- Als Autor von Produktwissen möchte ich Kundenbegriffe getrennt von Bestellbezeichnungen pflegen, damit Bestellvorschläge präzise bleiben und Textvorschläge breit sein dürfen.
- Als Autor möchte ich gewarnt werden, wenn ein Kundenbegriff bei zwei Produkten steht oder zu kurz ist, damit Vorschläge eindeutig bleiben.
- Als Autor im KI-Chat möchte ich, dass die Anleitung nach Kundenbegriffen fragt, damit sie beim Erfassen eines Produkts nicht vergessen werden.

## Out of Scope
- Suchfeld statt der vollständigen Produktliste im Formular (offene Frage aus PROJ-9, eigenes Feature bei vielen Produkten)
- Erkennen per KI oder semantischer Ähnlichkeit – PROJ-22 (bleibt deterministisch)
- Erkennen in Anhängen oder Bildern – PROJ-21
- Vorschlag der Kundengruppe aus dem Tickettext (Fachhändler-Erkennung bleibt Non-Goal)
- Automatisches Ableiten von Kundenbegriffen aus der Zammad-Historie – PROJ-18

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Neues Feld in Produktdateien
- [ ] Angenommen eine Produktdatei enthält `customer_terms` als Liste, wenn die Knowledge Base geprüft wird, dann ist das Feld gültig und erscheint in der Prüfung ohne Warnung
- [ ] Angenommen ein Dokument, das keine Produktdatei ist, enthält `customer_terms`, wenn geprüft wird, dann meldet die Prüfung das wie bei `order_keywords` (Feld nur in Produktdateien)
- [ ] Angenommen ein Kundenbegriff ist kürzer als vier Zeichen, wenn geprüft wird, dann warnt die Prüfung („passt vermutlich auf viele Texte“)
- [ ] Angenommen derselbe Kundenbegriff steht bei zwei Produkten, wenn geprüft wird, dann warnt die Prüfung bei beiden („Vorschlag nicht eindeutig“)
- [ ] Angenommen ein Kundenbegriff steht bei einem anderen Produkt in `order_keywords`, wenn geprüft wird, dann warnt die Prüfung
- [ ] Angenommen Kundenbegriffe sind vergeben, wenn die ID-Übersicht (Prüfbefehl, Knowledge-Übersicht, Sitzungsstart-Block für den KI-Chat) erzeugt wird, dann listet sie die vergebenen Kundenbegriffe je Produkt

### Erkennen im Ticket
- [ ] Angenommen der Titel oder eine Kundennachricht enthält einen Kundenbegriff eines Produkts, wenn das Analyse-Formular erscheint, dann ist dieses Produkt vorausgewählt und daneben steht „erkannt im Ticket: ‚[Begriff]‘“
- [ ] Angenommen der Titel oder eine Kundennachricht enthält den Titel einer Produktdatei als Ganzes, wenn das Formular erscheint, dann gilt das wie ein Kundenbegriff
- [ ] Angenommen ein Begriff steht nur in unserer Antwort, einer internen Notiz, einem eingeklappten Zitat oder einer eingeklappten Signatur, wenn das Formular erscheint, dann wird das Produkt deswegen nicht vorgeschlagen
- [ ] Angenommen ein Kundenbegriff steht innerhalb eines längeren Wortes, das nicht mit ihm beginnt (z. B. „Holo“ in „Kaholo“), wenn das Formular erscheint, dann gilt das nicht als Treffer; beginnt ein Wort mit dem Begriff (z. B. „Glassteine“ für „Glasstein“), ist es ein Treffer
- [ ] Angenommen Groß- und Kleinschreibung weichen ab, wenn gesucht wird, dann spielt das keine Rolle
- [ ] Angenommen eine EOCS-Bestellung schlägt ein Produkt vor und der Text ein anderes, wenn das Formular erscheint, dann sind beide vorausgewählt, jeweils mit ihrem Grund („aus der Bestellung“ bzw. „erkannt im Ticket: ‚…‘“)
- [ ] Angenommen für das Ticket wurden Produkte schon gewählt (gemerkte Wahl oder angezeigte Analyse), wenn das Formular erscheint, dann gilt diese Wahl; erkannte Produkte, die nicht gewählt sind, stehen als Hinweis „im Ticket erwähnt: … – nicht ausgewählt“ dabei
- [ ] Angenommen vorgeschlagene Produkte gibt es, wenn die Produktliste angezeigt wird, dann stehen die vorgeschlagenen oben
- [ ] Angenommen ein Ticket ist im Testmodus zurückgespult, wenn gesucht wird, dann zählen nur Kundennachrichten bis zum Schnittpunkt
- [ ] Angenommen ein Produktdokument ist `deprecated` oder fehlerhaft, wenn gesucht wird, dann wird es nicht vorgeschlagen

### Anleitung
- [ ] Angenommen ein Autor erfasst eine Produktdatei mit der Anleitung bzw. dem KI-Skill, wenn das Frontmatter besprochen wird, dann fragt die Anleitung nach früheren Produktnamen, Synonymen und Kundenbegriffen und trägt nur Bestätigtes ein; sie erklärt den Unterschied zu `order_keywords`
- [ ] Angenommen die Vorlage für Produktdateien wird verwendet, wenn sie geöffnet wird, dann enthält sie das Feld `customer_terms` (leer)

## Edge Cases
- **Mehrere Produkte im selben Ticket erwähnt:** alle werden vorgeschlagen; der Mitarbeiter entfernt, was nicht passt.
- **Begriff mehrfach im Text:** ein Vorschlag, der Grund nennt den Begriff einmal.
- **Mehrere Begriffe desselben Produkts:** ein Vorschlag, der Grund nennt bis zu zwei Begriffe („‚Viamant‘, ‚Glasstein‘“).
- **Umlaute und Schreibweisen** („Glasfoto“ vs. „Glas-Foto“, „Hologramm“ vs. „Holo-gramm“): nur die eingetragene Schreibweise zählt; Varianten trägt der Autor zusätzlich ein. Umlaute werden nicht umgeschrieben („ae“ ≠ „ä“).
- **Verneinter Bezug** („kein Viamant, sondern eine Tasse“): wird trotzdem vorgeschlagen; der Mitarbeiter entfernt den Haken (deterministisch, kein Sprachverständnis).
- **Sehr lange Tickets:** Suche bleibt schnell (nur Textvergleich); keine spürbare Verzögerung beim Laden.
- **Keine Kundenbegriffe gepflegt:** Verhalten wie heute (nur Bestellung und Titel).

## Technical Requirements (optional)
- Deterministisch, ohne KI-Aufruf, keine Kosten.
- Keine Ticketinhalte im Log.
- Ladezeit der Ticketseite nicht spürbar länger.

## Open Questions
- [ ] Soll bei sehr vielen Produkten (Hunderte) später ein Suchfeld die Kästchenliste ersetzen? (aus PROJ-9, eigenes Feature)
- [ ] Sollen erkannte Kundenbegriffe auch dem Sprachmodell als Hinweis mitgegeben werden („Kunde nennt das Produkt ‚Viamant‘“)? Vermutlich unnötig, weil das Produktdokument die Begriffe erklärt.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| P0 | Vor dem Weihnachts-Peak arbeiten Aushilfen, die alte Produktnamen nicht kennen; ohne Vorschlag fehlt der KI das Produktwissen | 2026-10-07 |
| Eigenes Feld `customer_terms`, getrennt von `order_keywords` | Bestellvorschläge bleiben präzise (Teilwort-Suche in Bestellpositionen), Kundenbegriffe dürfen breiter sein | 2026-10-07 |
| Im Text gesucht werden `customer_terms` und der Produkttitel, nicht `order_keywords` | Artikelnummern/ASINs und kurze Bezeichnungen würden im Freitext Fehltreffer erzeugen; heutige Kurznamen trägt der Autor zusätzlich in `customer_terms` ein | 2026-10-07 |
| Suchbereich: Titel und bereinigte Kundennachrichten; nicht unsere Antworten, Notizen, Zitate, Signaturen | Dort erwähnen wir evtl. andere Produkte; Vorschlag soll widerspiegeln, was der Kunde meint | 2026-10-07 |
| Erkanntes Produkt ist angehakt, mit Grund; zusammengeführt mit dem Bestellvorschlag; gemerkte Wahl hat Vorrang | Aushilfen übersehen reine Hinweise leicht; der Grund macht Fehltreffer sofort sichtbar | 2026-10-07 |
| Treffer am Wortanfang, ohne Groß-/Kleinschreibung, keine Umschreibung von Umlauten (von mir entschieden, bitte prüfen) | Deckt Pluralformen ab („Glassteine“), vermeidet Treffer mitten im Wort; einfach und vorhersehbar für Autoren | 2026-10-07 |
| Mindestlänge vier Zeichen als Warnung (von mir entschieden, bitte prüfen) | Kurze Begriffe passen auf zu viele Texte; `order_keywords` verwendet drei Zeichen, Freitext braucht etwas mehr | 2026-10-07 |
| Vorgeschlagene Produkte stehen oben in der Liste (von mir entschieden, bitte prüfen) | Bei wachsender Produktliste bleibt der Vorschlag sichtbar | 2026-10-07 |

### Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Keine Speicherung, keine Datenbank: Vorschläge werden bei jedem Laden der Ticketseite aus dem bereinigten Verlauf und der Knowledge berechnet | Immer aktuell zum Wissensstand und zum Verlauf; Textvergleich ist schnell | 2026-10-07 |
| Erkennen im vorhandenen Vorschlags-Baustein der Knowledge (neben dem Bestellvorschlag), Text liefert der vorhandene Ticketkontext | Eine Stelle für alle Produktvorschläge; nutzt die Bereinigung aus PROJ-6 und den Schnittpunkt aus PROJ-32 automatisch | 2026-10-07 |
| Treffer: Begriff am Wortanfang, ohne Groß-/Kleinschreibung; Produkttitel als ganze Wortfolge | Vorhersehbar für Autoren, deckt Pluralformen ab | 2026-10-07 |
| Vorschläge tragen ihren Grund (Bestellung bzw. erkannter Begriff) bis ins Formular | Mitarbeiter sieht, warum ein Produkt angehakt ist | 2026-10-07 |
| Prüfregeln für `customer_terms` analog zu `order_keywords` im vorhandenen Validator | Gleiche Logik und Meldungen, eine Stelle für Frontmatter-Regeln | 2026-10-07 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

### Überblick
Kein neuer Bildschirm, keine Datenbank. PROJ-33 erweitert drei vorhandene Bausteine: die Prüfung der Knowledge (neues Feld), den Produktvorschlag (zusätzlich aus dem Tickettext) und das Analyse-Formular (Grund je Vorschlag, Vorschläge oben). Dazu kommen Anleitung, KI-Skill und Vorlage.

### A) Bausteine
```
Knowledge-Prüfung (PROJ-3)
+-- Feld customer_terms: nur Produktdateien, Liste
+-- Warnungen: < 4 Zeichen · doppelt bei zwei Produkten · Überschneidung mit order_keywords
+-- ID-Übersicht / Sitzungsstart-Block: „Vergebene Kundenbegriffe je Produkt“

Produktvorschlag (PROJ-4)
+-- aus Bestellpositionen (order_keywords) – wie bisher
+-- NEU aus dem Tickettext (customer_terms + Produkttitel)
|   +-- Text: Ticket-Titel + bereinigte Kundennachrichten (bei Testlauf nur bis zum Schnittpunkt)
+-- Ergebnis je Produkt: Grund(e) „aus der Bestellung“ / „erkannt im Ticket: ‚…‘“

Analyse-Formular (PROJ-9)
+-- Produktliste: vorgeschlagene oben, angehakt, mit Grund
+-- gemerkte Wahl / angezeigte Analyse hat Vorrang
|   +-- zusätzlich erkannte: „im Ticket erwähnt: … – nicht ausgewählt“

Authoring-Kit (PROJ-2)
+-- Anleitung: Frage nach früheren Namen, Synonymen, Kundenbegriffen; Unterschied zu order_keywords
+-- KI-Skill: gleiche Frage
+-- Produktvorlage: Feld customer_terms (leer)
```

### B) Daten
Nichts wird gespeichert. Neu ist nur ein Frontmatter-Feld in Produktdateien:
- **`customer_terms`**: Liste von Begriffen, mit denen Kunden das Produkt beschreiben (frühere Namen, Synonyme, Umgangssprache).

Vorschläge werden bei jedem Laden der Ticketseite neu berechnet.

### C) Technische Entscheidungen (für Nicht-Entwickler)
- **Berechnen statt speichern:** Ändert sich das Wissen oder kommt eine neue Kundennachricht, stimmt der Vorschlag sofort. Ein reiner Textvergleich kostet keine merkliche Zeit und keine KI-Aufrufe.
- **Eine Stelle für Vorschläge:** Bestell- und Textvorschlag liegen im selben Baustein; das Formular bekommt eine Liste „Produkt + Grund“.
- **Gleicher Text wie die Ansicht:** Durchsucht wird die bereinigte Fassung (ohne eingeklappte Zitate und Signaturen); ein zurückgespultes Ticket ist automatisch nur bis zum Schnittpunkt sichtbar.
- **Prüfregeln wie bei `order_keywords`:** gleiche Art Meldungen, Autoren kennen sie schon.

### D) Abhängigkeiten
Keine neuen Pakete.

### E) Hinweise für /frontend und /backend
- Wortanfang heißt: vor dem Begriff steht kein Buchstabe oder keine Ziffer; danach darf das Wort weitergehen.
- Analyse-Metadaten (PROJ-11) bekommen die Gründe der Vorschläge nicht; gespeichert wird wie bisher nur die tatsächliche Wahl.
- Tests: Treffer im Titel, in Kundennachrichten; kein Treffer in Antworten, Notizen, Zitaten, Signaturen, mitten im Wort; Zusammenführen mit Bestellvorschlag; Vorrang der gemerkten Wahl mit Hinweis; Testmodus-Schnittpunkt; Prüfwarnungen; Übersicht.

## Implementation Notes (Frontend + Backend)
**Gebaut am 2026-10-07**, Frontend und Backend in einem Durchgang.

- **Feld:** `KnowledgeDocument::customerTerms()`; `customer_terms` als Listenfeld nur für Produktdateien (sonst „Unbekanntes Feld“ wie bei `order_keywords`).
- **Prüfung** (`KnowledgeValidator`): Warnung unter `knowledge.min_customer_term_length` (4) Zeichen, bei gleichem Begriff an zwei Produkten und bei Überschneidung mit den `order_keywords` eines anderen Produkts (Vergleich ohne Groß-/Kleinschreibung).
- **Übersicht** (`KnowledgeOverview`): neuer Block „Vergebene customer_terms je Produkt“ im Prüfbefehl, in der Knowledge-Übersicht und im Sitzungsstart-Block.
- **Erkennen** (`KnowledgeSuggester::productsFromText()`): Begriff am Wortanfang (davor kein Buchstabe/keine Ziffer), Produkttitel als ganze Wortfolge, ohne Groß-/Kleinschreibung; nur verwendbare Dokumente. Text aus `TicketContext::customerTexts()` (Titel + bereinigte Kundennachrichten; zurückgespulte Tickets automatisch nur bis zum Schnittpunkt).
- **Formular** (`AnalysisPanel::productSuggestions()`, `analysis/form`): Vorschläge aus Bestellung und Text zusammengeführt, vorausgewählt, oben in der Liste, Grund in Klammern („aus der Bestellung“, „erkannt im Ticket: ‚Glasstein‘“, höchstens zwei Begriffe); bei gemerkter Wahl bzw. angezeigter Analyse Hinweis „Im Ticket erwähnt: … – nicht ausgewählt“.
- **Authoring-Kit:** `docs/KNOWLEDGE_AUTHORING_GUIDE.md` (Abschnitt zu `customer_terms`, Abgrenzung zu `order_keywords`, Frage beim Erfassen, Sitzungsstart-Block, Abschlussprüfung), `.claude/skills/knowledge/SKILL.md`, `knowledge/templates/product.md`.
- **Tests:** `tests/Feature/PROJ-33-CustomerTermsTest.php` (16 Fälle). Gesamte Suite: 767 grün.
- **Hinweis für den Product Owner:** Für das Verfassen im KI-Chat außerhalb des Repos die aktualisierte Fassung von `docs/KNOWLEDGE_AUTHORING_GUIDE.md` verwenden.

## QA Test Results

**Tested:** 2026-10-07
**App URL:** http://localhost:8081
**Tester:** QA Engineer (AI)

**Vorgehen:** Automatisierte Feature-Tests (20 Fälle, nur erfundene Daten), Code-Review, Prüfung der echten Knowledge Base (`knowledge:check` 0 Fehler, `knowledge:overview` listet 14 Kundenbegriffe für `3d-glass-photo`) und Gegenprobe an echten Tickets: In 56 Tickets der Gruppe „allg. Kunden“ der letzten 10 Tage gab es keinen Vorschlag – also auch keine Fehltreffer durch kurze Begriffe wie „Mega“, „Nano“, „Giga“. Test des Product Owners an Ticket#2138663 (siehe BUG-1); der Text der Testmail allein ergibt den erwarteten Treffer („Viamant“, „Nano“). Ein zweiter Lauf gegen Zammad war wegen einer Zeitüberschreitung der Verbindung nicht möglich. Firefox/Safari und Handy-/Tablet-Breite nicht eigens geprüft.

### Acceptance Criteria Status

#### Neues Feld in Produktdateien
- [x] `customer_terms` als gültiges Listenfeld in Produktdateien
- [x] Außerhalb von Produktdateien gemeldet („Unbekanntes Feld“)
- [x] Warnung unter vier Zeichen
- [x] Warnung bei gleichem Begriff an zwei Produkten
- [x] Warnung bei Überschneidung mit `order_keywords` eines anderen Produkts
- [x] Übersicht listet vergebene Kundenbegriffe je Produkt

#### Erkennen im Ticket
- [x] Begriff in Kundennachricht: vorausgewählt mit Grund
- [x] Produkttitel als Ganzes zählt
- [x] Antworten, Notizen, Zitate, Signaturen zählen nicht
- [x] Wortanfang ja, mitten im Wort nein
- [x] Groß-/Kleinschreibung egal
- [x] Bestell- und Textvorschlag zusammengeführt, je mit Grund
- [x] Gemerkte Wahl hat Vorrang, Hinweis „im Ticket erwähnt – nicht ausgewählt“
- [x] Vorgeschlagene Produkte oben
- [x] Testmodus: nur bis zum Schnittpunkt
- [x] `deprecated`/fehlerhafte Produktdateien nicht vorgeschlagen

#### Anleitung
- [x] Anleitung und KI-Skill fragen nach Kundenbegriffen und erklären den Unterschied zu `order_keywords`
- [x] Produktvorlage enthält `customer_terms`

### Edge Cases Status
- [x] Mehrere Produkte im selben Ticket: alle vorgeschlagen
- [x] Begriff mehrfach im Text: ein Vorschlag
- [x] Mehrere Begriffe desselben Produkts: höchstens zwei im Grund genannt
- [x] Schreibweisen/Umlaute: nur die eingetragene Schreibweise; Sonderzeichen werden wörtlich verglichen
- [x] Verneinter Bezug: wird vorgeschlagen (wie spezifiziert)
- [x] Lange Tickets: reiner Textvergleich, Ladezeit der echten Tickets unverändert (0,6–1,0 s)
- [x] Keine Kundenbegriffe: Verhalten wie bisher; ohne Kundennachricht zählt nur der Titel

### Security Audit Results
- [x] Gründe und Begriffe werden escaped ausgegeben (Test mit `<b>`)
- [x] Begriffe werden wörtlich verglichen (kein Ausdruck aus der Knowledge wird als Muster ausgeführt)
- [x] Keine Inhalte im Log, keine KI-Aufrufe, keine neuen Eingaben von außen

### Regression
- Gesamte Suite: 779 Tests grün (u. a. PROJ-3 Prüfung, PROJ-4 Auswahl, PROJ-9 Formular, PROJ-32 Testmodus).

### Bugs Found

#### BUG-1: Mails von Adressen unserer Mitarbeiter gelten nicht als Kundennachricht
- **Severity:** Low
- **Status:** Behoben 2026-10-09 (in PROJ-6) – eine nicht interne Nachricht, die der Kunde des Tickets von seiner eigenen Adresse geschrieben hat, gilt als Kundennachricht, auch wenn Zammad sie wegen der Agenten-Rolle des Absenders unter „Agent“ führt. Geprüft an Ticket#2138663.
- **Steps to Reproduce:** Eine Testmail von der eigenen (in Zammad als Agent bekannten) Adresse an den Kundenservice schicken, Ticket in der App öffnen. Erwartet (aus Sicht des Testers): Produkt aus dem Text erkannt. Tatsächlich: Zammad führt die Nachricht als „Agent“, die App zeigt sie als unsere Nachricht, durchsucht sie nicht und kennt keine „letzte Kundennachricht“. Betrifft auch Kundenmails, die ein Mitarbeiter in Zammad weiterleitet. Bei echten Kundenmails tritt das nicht auf.
- **Workaround:** Testmails von einer Adresse senden, die in Zammad kein Agent ist.
- **Priority:** Nice to have (ggf. eigenes kleines Feature: eingehende E-Mails immer als Kundennachricht behandeln)

**Hinweis (kein Bug):** Der Titel von `3d-glass-photo.md` („3D-Glasfotos: Produktfamilie, Formen und frühere Bezeichnungen“) ist für die Produktliste lang und passt als Wortfolge praktisch nie in einen Kundentext. Der Product Owner lässt ihn bewusst so.

### Summary
- **Acceptance Criteria:** 18/18 bestanden
- **Bugs Found:** 1 total (0 critical, 0 high, 0 medium, 1 low)
- **Security:** Pass
- **Production Ready:** YES
- **Recommendation:** Freigeben.

## Deployment
_To be added by /deploy_
