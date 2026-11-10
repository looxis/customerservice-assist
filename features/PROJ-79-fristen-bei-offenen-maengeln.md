# PROJ-79: Fristen bei offenen Mängeln (Versandalter-Hinweis und Regel im Wissen)

## Status: In Progress
**Created:** 2026-11-10
**Last Updated:** 2026-11-10

## Dependencies
- Requires: PROJ-7 (Bestellung aus EOCS), PROJ-9 (Fallanalyse), PROJ-10 (Ergebnisansicht), PROJ-36 (gezielter Wissensabruf, Bedingung `order_age_days`), PROJ-2 (Knowledge Base)
- Später: PROJ-78 (Ticket-Klassifikation) kann den Hinweis auf Reklamationen einschränken

## Anlass
Ticket 2140331, Bestellung 303-0977429-2012311: bestellt am 9. Oktober, geliefert wenige Tage später, die Kundin meldet am 8. November eine beschädigte Ecke („erst am 8. November gesehen“). Der Vorschlag war im Prinzip richtig, hat aber nicht berücksichtigt, dass eine sichtbare Beschädigung ein offener Mangel ist, der unverzüglich zu melden gewesen wäre. Das Alter der Bestellung schaut im Alltag kaum jemand an.

## User Stories
- Als Kundenservice-Mitarbeiter möchte ich ohne KI-Aufruf deutlich sehen, wenn der Versand einer Bestellung schon länger zurückliegt, damit mir bei Beschädigungsmeldungen das Alter nicht entgeht.
- Als Kundenservice-Mitarbeiter möchte ich, dass der Vorschlag bei einer sichtbaren Beschädigung, die erst lange nach der Lieferung gemeldet wird, die Reklamation als Mangel ablehnt und mir ein Kulanzangebot offen lässt, damit ich nicht selbst die Fristen abwägen muss.
- Als Kundenservice-Mitarbeiter möchte ich bei Fehlern, die schon in den Druck- oder Produktionsdaten sichtbar sind, den Hinweis auf die Kulanz bis sechs Wochen bekommen, damit ich diese Fälle nicht fälschlich ablehne.
- Als Aushilfe möchte ich nicht wissen müssen, was ein „offener Mangel“ ist, damit die Regel in Vorschlag und Antwort ankommt, ohne dass ich sie kenne.

## Out of Scope
- Einschränkung des Hinweises auf Reklamationen: erst mit der Ticket-Klassifikation (PROJ-78); bis dahin erscheint er bei jedem Ticket mit versendeter Bestellung
- Zustelldatum aus der Sendungsverfolgung (EOCS liefert nur Versanddatum und „zugestellt“)
- Zuordnung, welche Sendung das beschädigte Produkt enthielt (Teillieferungen sind selten; Ersatzsendungen sind eigene Aufträge mit eigener Reklamationsnummer)
- Einsicht der App in Druck- und Produktionsdaten: der Mitarbeiter prüft den Datensatz
- Änderung der Kulanzregeln und Rabattgrenzen (POLICY-033, PERMISSION-004 bleiben unverändert)
- Eigene Fristen je Kundengruppe; gruppenspezifische Dokumente bleiben unverändert
- Blockieren von Kopieren oder Senden

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Hinweis im Code (ohne KI-Aufruf)
- [ ] Angenommen eine Bestellung mit Sendung ist geladen und die erste Sendung liegt 14 bis 30 Tage zurück, wenn die Ticketseite erscheint, dann zeigt ein gelber Hinweis das Versanddatum und die Tage seit dem Versand und erinnert daran, dass sichtbare Beschädigungen unverzüglich zu melden sind.
- [ ] Angenommen die erste Sendung liegt mehr als 30 Tage zurück, wenn die Ticketseite erscheint, dann zeigt ein roter, deutlicherer Hinweis dasselbe mit der Aussage, dass eine Meldung von Beschädigungen nach so langer Zeit kaum noch als Mangel anzuerkennen ist.
- [ ] Angenommen die erste Sendung liegt weniger als 14 Tage zurück, wenn die Ticketseite erscheint, dann erscheint kein Hinweis.
- [ ] Angenommen die Bestellung hat keine Sendung, wenn die Ticketseite erscheint, dann erscheint kein Hinweis und das Bestelldatum wird nicht ersatzweise verwendet.
- [ ] Angenommen die Bestellung hat mehrere Sendungen, wenn der Hinweis berechnet wird, dann zählt das Datum der ersten Sendung.
- [ ] Angenommen der Hinweis erscheint, wenn der Mitarbeiter den Bestellblock und das Formular bzw. Ergebnis ansieht, dann steht er an beiden Stellen, ohne dass dafür ein KI-Aufruf nötig ist.
- [ ] Angenommen mehrere Bestellungen sind am Ticket geladen, wenn sie unterschiedlich alt sind, dann erscheint der Hinweis je Bestellung mit Bestellnummer.
- [ ] Angenommen der Hinweistext erscheint, wenn er gelesen wird, dann nennt er keine festen Fristen der Kundenantwort, sondern nur Versanddatum, Tage und den Grundsatz „sichtbare Beschädigung sofort melden“.

### Daten an die KI
- [ ] Angenommen eine Bestellung mit Sendung ist am Vorschlag beteiligt, wenn der Prompt gebaut wird, dann stehen Bestelldatum, Versanddatum der ersten Sendung und die Tage seit dem Versand in den Bestelldaten.
- [ ] Angenommen die Bestellung hat keinen Versand, wenn der Prompt gebaut wird, dann steht dort „noch kein Versand erfasst“ und keine Tageszahl.
- [ ] Angenommen ein Wissensdokument hat eine Bedingung auf das Alter der Bestellung, wenn der Abruf läuft, dann ist klar geregelt, ob sie ab Bestell- oder ab Versanddatum zählt; bestehende Dokumente ändern ihr Verhalten nicht (Details in `/architecture`).

### Regel im Wissen und Verhalten des Vorschlags
- [ ] Angenommen eine neue Regel (Policy) in `knowledge/policies/` gilt für alle Kundengruppen, Kanäle und Produkte, wenn `knowledge:check` läuft, dann besteht sie die Prüfung mit null Fehlern.
- [ ] Angenommen die Regel regelt: Sichtbare Beschädigungen (auch Kratzer und Beschädigungen innen) sind offene Mängel und unverzüglich zu melden; sind sie sichtbar und störend, hätten sie schon bei Erhalt sichtbar sein müssen; nicht sichtbare stören nicht; Funktionsmängel und Produktionsfehler sind ausgenommen.
- [ ] Angenommen eine sichtbare Beschädigung wird 14 Tage oder länger nach dem Versand gemeldet, wenn der Vorschlag entsteht, dann lehnt er die Reklamation als Mangel ab, begründet kurz, dass solche Schäden sofort zu erkennen sind und daher unverzüglich gemeldet werden müssen, und nennt keine Tagesfrist in der Antwort.
- [ ] Angenommen die Reklamation wird als Mangel abgelehnt, wenn der Antwortentwurf entsteht, dann steht dort kein Anspruch auf kostenlosen Ersatz oder Erstattung; ein Kulanzangebot nach POLICY-033 und PERMISSION-004 bleibt möglich und wird wie bisher genannt.
- [ ] Angenommen nach vier Wochen wird eine Beschädigung gemeldet, wenn der Vorschlag begründet, dann geht er davon aus, dass der Schaden vom Kunden verursacht wurde.
- [ ] Angenommen die Meldung kommt vor Ablauf von 14 Tagen und der Kunde schreibt, er habe es sofort bemerkt, wenn der Vorschlag entsteht, dann gilt die Regel „zu spät gemeldet“ nicht; die übrigen Regeln gelten wie bisher.
- [ ] Angenommen der Kunde nennt selbst einen späten Zeitpunkt der Entdeckung („erst am 8. November gesehen“), wenn der Vorschlag entsteht, dann wertet er das als Beleg für die verspätete Meldung.
- [ ] Angenommen ein Fehler ist in den Druck- oder Produktionsdaten sichtbar (Motiv, Farbe, Beschnitt, Text), wenn die Meldung später als 14 Tage nach dem Versand kommt, dann lehnt der Vorschlag nicht ab, sondern nennt in der Empfehlung „Mangel im Produktionsdatensatz prüfen; dort sichtbar, dann Kulanz bis 6 Wochen nach Lieferung“; der Mitarbeiter prüft den Datensatz.
- [ ] Angenommen die Prüfung des Datensatzes steht in der Empfehlung, wenn das Ergebnis angezeigt wird, dann erscheint dafür keine blockierende Klärung („Zuerst zu klären“).
- [ ] Angenommen die Meldung kommt nach mehr als sechs Wochen, wenn es um einen in den Produktionsdaten sichtbaren Fehler geht, dann bietet der Vorschlag keine Kulanz aus dieser Regel an (Kulanz nach POLICY-033 bleibt möglich).
- [ ] Angenommen das Ticket ist keine Reklamation (zum Beispiel WISMO oder Frage), wenn ein Vorschlag entsteht, dann spielt die neue Regel keine Rolle; sie greift nur bei Beanstandung von Beschädigungen oder sichtbaren Mängeln.

## Edge Cases
- Bestellung noch nicht versendet, Kunde reklamiert „nicht geliefert“: kein Hinweis, die Regel offener Mängel greift nicht.
- Reklamationsauftrag (Ersatzsendung) am Ticket: Der Hinweis zählt je Auftrag ab dessen erster Sendung; die Ersatzsendung hat ein eigenes Datum.
- EOCS liefert ein Versanddatum in der Zukunft oder gar kein gültiges Datum: kein Hinweis, keine Fehlermeldung.
- Versand am selben Tag: kein Hinweis.
- Die Bestellung ist aus dem Ticket nicht erkennbar oder wurde manuell nicht geladen: kein Hinweis; der bestehende Hinweis auf fehlende Bestellung bleibt.
- Mehrere Bestellungen am Ticket, nur eine ist alt: Hinweis nur für diese.
- Kunde nennt Beschädigung der Innenseite oder Kratzer: gilt wie jede andere sichtbare Beschädigung.
- Unklar, ob der Fehler beim Druck oder durch Beschädigung entstand: Mitarbeiter entscheidet; der Vorschlag benennt beide Möglichkeiten (Datensatz prüfen).
- Mangel betrifft die Funktion (zum Beispiel Beleuchtung) oder einen Produktionsfehler, der zur Beschädigung geführt hat: Die Frist für offene Mängel gilt nicht.

## Technical Requirements
- Der Hinweis wird im Code berechnet, nicht von der KI; kein zusätzlicher Aufruf bei Zammad oder EOCS.
- Schwellen 14 und 30 Tage fest im Code, nicht in den Einstellungen (spätere Konfigurierbarkeit offen).
- Die Regeln stehen im Wissen (Markdown mit Frontmatter), nicht im Prompt (PRD: „Fachliche Regeln stehen nicht im Prompt“).
- Datenschutz unverändert: Versanddatum und Tageszahl enthalten keine zusätzlichen Kundendaten.

## Open Questions
- [x] Eigene Policy oder Anhang an bestehende? Entschieden in `/architecture`: eigene Policy mit Verweisen auf POLICY-032, 033 und 005; Überschneidungen werden beim Schreiben geprüft.
- [x] Neues Feld oder Umstellung von `order_age_days`? Entschieden: weder noch, `days_since_shipped` gibt es schon und zählt ab der ersten Sendung.
- [ ] Soll der Hinweis später je Kundengruppe oder Kanal (Amazon) anders aussehen? Derzeit nein.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Hinweis zählt ab dem Versanddatum, nicht ab dem Bestelldatum | Die Fristen beziehen sich auf die Lieferung; Bestelldatum wäre bei langer Produktion irreführend | 2026-11-10 |
| Ohne Versand kein Hinweis | Ohne Versand kann es keinen offenen Mangel geben; „nicht geliefert“ ist ein anderer Fall | 2026-11-10 |
| Bei mehreren Sendungen zählt die erste | Teillieferungen kommen fast nie vor; Ersatzsendungen sind eigene Aufträge mit eigener Reklamationsnummer | 2026-11-10 |
| Hinweis bei jedem Ticket mit versendeter Bestellung, neutral formuliert | Eine Ticket-Klassifikation gibt es noch nicht (PROJ-78); später einschränkbar | 2026-11-10 |
| Zwei Stufen: ab 14 Tagen gelb, ab 30 Tagen rot | Vorgabe des Auftraggebers | 2026-11-10 |
| Eine Regel für alle Kundengruppen | Vorgabe des Auftraggebers; gruppenspezifische Dokumente bleiben unberührt | 2026-11-10 |
| Sichtbare Beschädigung nach Ablauf: Ablehnung als Mangel, Begründung „sofort erkennbar, daher unverzüglich melden“, keine Tagesfrist in der Antwort | Eine genannte Frist hilft dem Kunden nicht, der sich nach 15 Tagen meldet | 2026-11-10 |
| Kulanzangebot bleibt immer möglich | Bestehende Regel (POLICY-033, PERMISSION-004); kein neues Zugeständnis | 2026-11-10 |
| Jede sichtbare Beschädigung ist ein offener Mangel, auch Kratzer und Innenseiten; nicht sichtbar heißt nicht störend | Definition des Auftraggebers; Funktionsmängel und Produktionsfehler sind ausgenommen | 2026-11-10 |
| Fehler in Druck- und Produktionsdaten: Kulanz bis 6 Wochen, Kunde als Verursacher ausgeschlossen | Vorgabe des Auftraggebers; bei Beschädigungen ist nach 4 Wochen von Kundenverursachung auszugehen | 2026-11-10 |
| Prüfung des Produktionsdatensatzes steht als Hinweis in der Empfehlung, nicht als blockierende Klärung | Die App sieht die Datensätze nicht; weniger interne Schritte (PROJ-73, PROJ-75) | 2026-11-10 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|
| Bestehende Bedingung `days_since_shipped` (erste Sendung) wiederverwenden, `order_age_days` unverändert lassen | Zählt schon ab der ersten Sendung wie verlangt; Hinweis und Wissen rechnen gleich; bestehende Dokumente ändern sich nicht | 2026-11-10 |
| Hinweis als eine wiederverwendbare Komponente im Bestellblock und im Formular/Ergebnis | Ein Ort für Schwellen und Text; gleiche Anzeige überall; kein KI-Aufruf | 2026-11-10 |
| Tageszahl und heutiges Datum werden in der App gerechnet und in den Prompt geschrieben | Die KI kennt das heutige Datum nicht und soll nicht rechnen | 2026-11-10 |
| Schwellen 14 und 30 Tage fest im Code | Vorgabe; später über Einstellungen möglich, jetzt nicht nötig | 2026-11-10 |
| Eigene verbindliche Policy mit Auslösern und Maßnahmen, ohne Bedingung auf Versandalter | Die Regel muss bei Beschädigungsmeldungen sicher greifen; ohne Bedingung kommt sie auch bei fehlenden Versanddaten mit; die Tageszahl kommt aus dem Prompt | 2026-11-10 |
| Keine Datenbankänderung, keine neuen Pakete, keine neue Seite | Alle Daten sind in EOCS-Bestellung und Wissen schon vorhanden | 2026-11-10 |

---

## Tech Design (Solution Architect)

### Was schon da ist
- **Wissensabruf:** Die Bedingung „Tage seit Versand“ (`days_since_shipped`) gibt es schon und sie zählt ab der **ersten** Sendung. Genau das verlangt die Spezifikation. Das Bestelldatum-Feld (`order_age_days`) bleibt unverändert.
- **Prompt:** Bestelldatum und Versanddatum jeder Sendung stehen bereits in den Bestelldaten. **Es fehlt** das heutige Datum und die Zahl „Tage seit Versand“; die KI kennt heute den Stichtag nicht und könnte das Alter nur raten.
- **Anzeige:** Der Bestellblock zeigt Bestelldatum und Versand, aber keinen Hinweis zum Alter.

Backend-Bedarf: keine Datenbank, keine neue Seite, keine neuen Pakete. Es sind drei kleine Änderungen (Hinweis, Prompt, Wissen).

### A) Bausteine (Struktur)
```
Ticketseite
+-- Bestellungen (vorhanden)
|   +-- Bestellblock je Bestellung (vorhanden)
|       +-- NEU: Versandalter-Hinweis (gelb ab 14, rot ab 30 Tagen)
+-- Vorschlagsformular / Ergebnis (vorhanden)
    +-- NEU: derselbe Versandalter-Hinweis je Bestellung, oben im Formular bzw. Ergebnis
```
Der Hinweis ist **ein** wiederverwendbarer Baustein (Blade-Komponente), der an beiden Stellen steht. Er bekommt die Bestellung und rechnet selbst; zusätzliche Daten braucht er nicht.

### B) Regeln des Hinweises (ohne Datenbank)
- Grundlage ist die **erste Sendung mit Versanddatum** der Bestellung (gleiche Rechnung wie im Wissensabruf, damit Hinweis und Wissen nie auseinanderlaufen).
- Tage werden in deutscher Zeit in ganzen Kalendertagen gezählt; Versand am selben Tag oder in der Zukunft zählt als 0 bzw. zeigt nichts.
- Unter 14 Tagen oder ohne Versand: kein Hinweis. 14 bis 30 Tage: gelb. Über 30 Tage: rot, deutlicher.
- Der Text nennt Versanddatum und Tage und den Grundsatz „sichtbare Beschädigungen sind sofort zu melden“; keine Fristen für die Kundenantwort.
- Mehrere geladene Bestellungen: ein Hinweis je betroffener Bestellung mit Bestellnummer.

### C) Daten an die KI
In den Bestelldaten des Prompts kommen dazu: **heutiges Datum** (einmal im Kopf der Bestelldaten) und je Bestellung **„erste Sendung vor N Tagen versandt“**; ohne Versand der Satz „noch kein Versand erfasst“ (steht dort heute schon als „noch nicht versandt“ bzw. „keine Sendung in EOCS gefunden“, das bleibt). Die Zahl wird in der App gerechnet, nicht von der KI.

### D) Regel im Wissen (eine neue Policy)
- **Eigene Policy** in `knowledge/policies/`, für alle Kundengruppen, Kanäle und Produkte, mit Verweisen auf POLICY-032, POLICY-033 und POLICY-005. Sie enthält: Definition offener Mangel, Folge bei verspäteter Meldung (Ablehnung als Mangel, ohne Tagesfrist in der Kundenantwort, Kulanz möglich), Kundenverursachung ab vier Wochen, Ausnahmen (Funktionsmängel, Produktionsfehler) und den Fall „Fehler in den Produktionsdaten“ mit Kulanz bis sechs Wochen und dem Hinweis „Datensatz prüfen“ in der Empfehlung.
- **Abruf: verbindlich (`binding`)**, weil die Regel bei einer Beschädigungsmeldung sicher greifen muss und nie wegen Platzmangel wegfallen darf. Sie kommt über **Auslöser** (Kundenwörter wie beschädigt, zerkratzt, Kratzer, Riss, Bruch, Macke, Ecke, in den Sprachen der Kunden) und über die **Maßnahmen** „kostenloser Ersatz/Neuproduktion“ ins Spiel: Empfiehlt die KI so etwas, lädt die Regelprüfung (PROJ-72) die Policy nach.
- **Keine Bedingung „Tage seit Versand“ am Dokument.** Der Grundsatz „unverzüglich“ gilt immer; die Tageszahl liefert der Prompt. Mit einer Bedingung würde die Policy bei fehlenden Versanddaten oder bei einer Meldung kurz nach Lieferung gerade nicht mitkommen.
- Der Text sagt der KI ausdrücklich: Alter aus den Bestelldaten nehmen; nennt der Kunde selbst einen späten Entdeckungszeitpunkt, zählt auch das.
- Das Budget für Verbindliches bleibt klein: kurze Policy, vor dem Schreiben mit `knowledge:check` prüfen; Überschneidungen mit POLICY-032 und POLICY-005 vorher gelesen und im Text nicht wiederholt, sondern verwiesen.

### E) Tests
- Hinweis: Schwellenwerte (13, 14, 30, 31 Tage), ohne Versand, mehrere Sendungen, mehrere Bestellungen.
- Prompt: heutiges Datum und Tageszahl stehen drin; ohne Versand keine Zahl.
- Wissen: `knowledge:check` mit null Fehlern; Auslöser holen die Policy bei einer Beschädigungsmeldung.
- Fachlicher Gegentest mit dem Ticket 2140331 (Ergebnis soll jetzt als Mangel ablehnen, Kulanz anbieten).

### Reihenfolge
1. Hinweis-Baustein im Bestellblock und im Formular (`/frontend`)
2. Prompt-Ergänzung und Tests (`/backend`)
3. Policy im Wissen (`/knowledge`), danach Gegentest am echten Ticket

### Abhängigkeiten (Pakete)
Keine.

## Implementation Notes (Frontend)
**2026-11-10:** Hinweis im Code umgesetzt (Teil 1 der Reihenfolge); Prompt-Ergänzung und Policy im Wissen stehen noch aus.
- `EocsOrder::daysSinceFirstShipment()` und `shippingAgeLevel()` (14 und 30 Tage als Konstanten); `OrderFacts` nutzt dieselbe Rechnung für `days_since_shipped`, damit Hinweis und Wissensabruf nicht auseinanderlaufen.
- Neue Komponente `x-ticket.shipping-age-notice` (gelb 14 bis 30 Tage, rot darüber), eingebunden im Bestellblock (ohne Bestellnummer) und in `tickets/show` direkt über Vorschlag und Formular (Bestellnummer nur bei mehreren Bestellungen).
- Abweichung: Der Hinweis über dem Vorschlag steht auch bei eingeklapptem Formular, weil er über beiden steht und nicht im Formular selbst.
- Tests: `tests/Feature/PROJ-79-ShippingAgeNoticeTest.php` (Schwellen 13/14/30/31, ohne Sendung, mehrere Sendungen, Darstellung, keine Tagesfrist im Text).
- Noch offen für `/backend`: heutiges Datum und „Tage seit Versand“ im Prompt; für `/knowledge`: die Policy.

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
