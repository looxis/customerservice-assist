# PROJ-58: Direkte Links zu internen Tools in Arbeitsabläufen

## Status: In Progress
**Created:** 2026-10-21
**Last Updated:** 2026-10-21

## Dependencies
- Requires: PROJ-30 (Interne Arbeitsabläufe zum Fall), PROJ-7 (Bestellung aus EOCS laden), PROJ-6 (Zammad-Ticket laden)
- Verwandt: PROJ-27 (Einstellungen), PROJ-53 (Reklamationsnummer, zusammengehörige Bestellungen)

## Ausgangslage
Ein Arbeitsablauf (PROJ-30) ist heute reiner Text: „In EOCS: Den Auftrag öffnen“, „In Vanilo das Konto suchen“. Der Mitarbeiter muss das Werkzeug und oft auch den richtigen Auftrag selbst suchen. Auf der Ticketseite gibt es oben schon den Knopf „In EOCS öffnen“ am Auftrag, im Ablauf fehlt er. Anlass ist der neue Ablauf PROCEDURE-005 (Reklamationsauftrag in EOCS anlegen), dessen erster Schritt „Auftrag in EOCS öffnen“ direkt dort ein Knopf sein soll, wo der Schritt steht. Dasselbe gilt für andere interne Werkzeuge: Vanilo-Backend, Mailcoach, EOCS-Kundenliste, das Ticket in Zammad.

## User Stories
- Als Mitarbeiterin möchte ich im Arbeitsschritt „Auftrag in EOCS öffnen“ direkt auf den Auftrag des Falls klicken können, damit ich ihn nicht suchen muss.
- Als Mitarbeiterin möchte ich bei Schritten in Vanilo, Mailcoach oder der EOCS-Kundenliste direkt zum Werkzeug springen, damit ich die Adresse nicht heraussuchen muss.
- Als Wissensautor möchte ich in einem Ablauf an der passenden Stelle einen Link auf ein Werkzeug schreiben, ohne Adressen von Aufträgen zu kennen, damit Abläufe lesbar bleiben und auch außerhalb der App (KI-Chat, Git) verständlich sind.
- Als Admin möchte ich die Adressen der Werkzeuge an einer Stelle pflegen, damit ein Umzug eines Werkzeugs nicht alle Abläufe berührt.

## Out of Scope
- Eingaben vorbelegen oder Aktionen in den Werkzeugen ausführen (Auftrag anlegen, Konto löschen): Die App öffnet nur das Werkzeug.
- Anmeldung an den Werkzeugen: Der Mitarbeiter ist dort selbst angemeldet.
- Suche in EOCS (die EOCS-Kundenliste nimmt keine Parameter entgegen) und in Mailcoach (mehrere passende Listen, der Listenname müsste mitgegeben werden): dort öffnet die App nur die Seite ohne Suchbegriff. Nur Vanilo bekommt die E-Mail-Adresse mit.
- Neue Werkzeuge einbinden, die nicht in der Tabelle unten stehen (später ergänzbar).
- Links in Antwortentwürfen oder in der Knowledge-Übersicht.
- Die Platzhalter `[REKLAMATIONSNUMMER]` und die Reklamationsnummer als Fakt für die KI: der Platzhalter in der Antwort steht schon im Wissen (TONE-011); eine Berechnung der Nummer durch die App ist nicht Teil dieser Spec.

## Schreibweise im Ablauf (Vorschlag)
Der Autor schreibt einen normalen Markdown-Link mit einem Kürzel statt einer Adresse, zum Beispiel `[Auftrag in EOCS öffnen](link:eocs-auftrag)`. In der App wird daraus ein Knopf zum richtigen Ziel. Außerhalb der App (Git, KI-Chat) ist es ein lesbarer Link mit Beschriftung. Kann das Ziel im Fall nicht bestimmt werden, erscheint nur der Text ohne Knopf.

## Ziele (Kürzel)
| Kürzel | Ziel | Art |
|---|---|---|
| `eocs-auftrag` | der Auftrag des Falls in EOCS | hängt vom Fall ab; bei mehreren Bestellungen ein Knopf je Bestellung mit der Bestellnummer |
| `zammad-ticket` | das Ticket des Falls in Zammad | hängt vom Fall ab |
| `eocs-kunden` | Kundenliste in EOCS (https://eocs.loox.is/customers) | fest |
| `vanilo-kunden` | Vanilo-Backend, Seite „Shopper“ (https://looxis.de/manage/shopper). Ist die E-Mail-Adresse des Kunden aus dem Ticket bekannt, hängt die App sie an: `?email=<Adresse>&order_by=newest_first` (Adresse URL-kodiert). Sonst die Seite ohne Suchbegriff | fest, mit E-Mail des Falls ergänzt |
| `mailcoach-listen` | Mailcoach, E-Mail-Listen (https://looxis.mailcoach.app/email-lists) | fest |

## Prüfung der vorhandenen Abläufe (Vorschlag, wo verlinkt werden könnte)
| Ablauf | Schritt | Vorgeschlagenes Ziel |
|---|---|---|
| PROCEDURE-001 Retoure erfassen | 1 „In EOCS … die Übersicht der Bestellung öffnen“ | `eocs-auftrag` |
| PROCEDURE-001 | 11 „In Zammad: Manuell ein Ticket erstellen“ | `zammad-ticket` (das Ticket des Falls; ein neues Ticket erstellt man in Zammad selbst) |
| PROCEDURE-002 Rechnung aus EOCS in Zammad senden | 2 „In EOCS die Bestellung öffnen“ | `eocs-auftrag` |
| PROCEDURE-002 | 6 „In Zammad das Antwort-Fenster des zugehörigen Tickets öffnen“ | `zammad-ticket` |
| PROCEDURE-003 Selbstabholung in der Abholbox | Schritte mit „in EOCS kontrollieren“ | `eocs-auftrag` |
| PROCEDURE-004 Kundenkonto eines Fachhändlers schließen | 1 Mailcoach | `mailcoach-listen` |
| PROCEDURE-004 | 2 Vanilo | `vanilo-kunden` |
| PROCEDURE-004 | 3 EOCS | `eocs-kunden` |
| PROCEDURE-005 Neuproduktion als Reklamationsauftrag | 1 „In EOCS: Den Auftrag öffnen“ | `eocs-auftrag` |

Die Adressen stehen in PROCEDURE-004 heute als Klartext im Text. Mit der Umstellung ersetzt der Autor sie durch die Kürzel.

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

- [ ] Angenommen ein Ablauf enthält `[Auftrag in EOCS öffnen](link:eocs-auftrag)` und der Fall hat eine Bestellung aus EOCS, wenn der Ablauf angezeigt wird, dann erscheint an dieser Stelle ein Knopf „Auftrag in EOCS öffnen“, der den Auftrag in einem neuen Tab öffnet.
- [ ] Angenommen der Fall hat mehrere Bestellungen aus EOCS, wenn der Ablauf angezeigt wird, dann erscheint je Bestellung ein Knopf mit der Bestellnummer.
- [ ] Angenommen der Fall hat keine Bestellung aus EOCS, wenn der Ablauf angezeigt wird, dann steht an der Stelle nur der Text ohne Knopf, und ein Hinweis nennt, dass keine Bestellung geladen ist.
- [ ] Angenommen ein Ablauf enthält `link:zammad-ticket`, wenn er zu einem Ticket angezeigt wird, dann führt der Knopf zum Ticket in Zammad; bei einer Frage ohne Ticket steht nur der Text.
- [ ] Angenommen ein Ablauf enthält ein festes Kürzel (`eocs-kunden`, `vanilo-kunden`, `mailcoach-listen`), wenn er angezeigt wird, dann führt der Knopf zur konfigurierten Adresse, auch ohne Ticket oder Bestellung.
- [ ] Angenommen ein Ablauf enthält `link:vanilo-kunden` und der Fall hat eine bekannte E-Mail-Adresse des Kunden, wenn der Ablauf angezeigt wird, dann führt der Knopf zur Vanilo-Seite „Shopper“ mit der Adresse als Suchbegriff (`email=`, URL-kodiert) und der Sortierung „neueste zuerst“ (`order_by=newest_first`).
- [ ] Angenommen die E-Mail-Adresse des Kunden ist nicht bekannt (Frage ohne Ticket, keine Kundennachricht), wenn der Ablauf mit `link:vanilo-kunden` angezeigt wird, dann führt der Knopf zur Vanilo-Seite ohne Suchbegriff.
- [ ] Angenommen `link:eocs-kunden` oder `link:mailcoach-listen`, wenn der Ablauf angezeigt wird, dann hängt die App keinen Suchbegriff und keine Kundendaten an.
- [ ] Angenommen ein Ablauf enthält ein unbekanntes Kürzel, wenn er angezeigt wird, dann steht nur der Text ohne Knopf, und `knowledge:check` meldet das Kürzel als Fehler.
- [ ] Angenommen ein normaler Link mit `https://…` steht in einem Ablauf, wenn er angezeigt wird, dann verhält er sich wie bisher.
- [ ] Angenommen ein Knopf zeigt auf ein Werkzeug, wenn er geklickt wird, dann öffnet er in einem neuen Tab mit `noopener`; die App sendet keine Daten an das Werkzeug.
- [ ] Angenommen ein Admin ändert die Adresse eines festen Ziels, wenn ein Ablauf angezeigt wird, dann führt der Knopf zur neuen Adresse, ohne dass ein Ablauf geändert werden muss.
- [ ] Angenommen die Abläufe PROCEDURE-001 bis PROCEDURE-005 sind auf Kürzel umgestellt, wenn sie angezeigt werden, dann stehen die Knöpfe an den Stellen der Tabelle oben.
- [ ] Angenommen der Ablauf wird im KI-Chat oder in Git gelesen, wenn der Link `link:…` dort steht, dann ist die Beschriftung lesbar und der Satz bleibt verständlich.

## Edge Cases
- Der Auftrag des Falls ist in EOCS nicht auffindbar (Bestellnummer unbekannt): kein Knopf, Hinweis „Bestellung nicht geladen“.
- Der Ablauf wird für einen älteren Vorschlag angezeigt: Die Knöpfe nutzen die Bestellungen, die zum Vorschlag gespeichert sind.
- Dasselbe Kürzel steht mehrmals im Ablauf: überall ein Knopf.
- Ein Ablauf steht noch im Entwurf: Knöpfe funktionieren wie sonst, der Hinweis „Entwurf“ bleibt.
- Die E-Mail-Adresse enthält Sonderzeichen (zum Beispiel `+` oder `@`): Sie wird URL-kodiert, damit Vanilo sie richtig liest.
- Die Ticket-Adresse ist nicht die des Shop-Kontos (der Händler schreibt von einer anderen Adresse): Vanilo zeigt dann keinen Treffer; der Mitarbeiter ändert den Suchbegriff dort selbst.
- Das Werkzeug ist nicht erreichbar: Das merkt die App nicht; der Tab zeigt die Fehlerseite des Werkzeugs.
- Reklamationsaufträge (R1-…): Hat der Fall zusammengehörige Reklamationsaufträge (PROJ-53), zeigt der Knopf `eocs-auftrag` den Originalauftrag und die Reklamationsaufträge, jeweils beschriftet.

## Technical Requirements (optional)
- Security: Ziele kommen nur aus der Konfiguration und aus den geladenen Bestellungen. Die einzige Eingabe aus dem Ticket ist die E-Mail-Adresse des Kunden für Vanilo; sie wird vollständig URL-kodiert und nur als Wert von `email` angehängt. Nur `https`-Ziele. Die Adresse geht an das interne Vanilo, nicht an Dritte.
- Der Text bleibt für die KI-Auswahl unverändert; Abläufe gehen nicht an die KI.

## Open Questions
- [x] Suchbegriff in den Werkzeugen: Vanilo ja (`…/shopper?email=<Adresse>&order_by=newest_first`), EOCS nein, Mailcoach nicht gewünscht (2026-10-21).
- [x] E-Mail für Vanilo: Kunde im Zammad-Ticket, ersatzweise Antwortadresse (Architekturentscheidung 2026-10-21, auf Wunsch änderbar).
- [x] Feste Adressen: in der Konfiguration der App (Architekturentscheidung 2026-10-21).
- [ ] Gibt es weitere Werkzeuge, die verlinkt werden sollen (APC, Versanddienstleister, Mollie)?
- [x] Mehrere Bestellungen: ein Knopf je Bestellung (Architekturentscheidung 2026-10-21).

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Normaler Markdown-Link mit Kürzel statt eigener Platzhalter-Syntax | Der Ablauf bleibt in Git und im KI-Chat lesbar; der Autor muss keine Sonderschreibweise lernen | 2026-10-21 |
| Fest hinterlegte Ziele für Vanilo, Mailcoach und EOCS-Kunden, fallabhängige für Auftrag und Ticket | Die Auftragsadresse kennt die App schon, die festen Adressen ändern sich selten | 2026-10-21 |
| Ohne Ziel nur Text, kein toter Knopf | Kein Klick ins Leere, wenn kein Auftrag geladen ist | 2026-10-21 |
| Suche nur in Vanilo vorbelegen | Vanilo nimmt `email` und `order_by` entgegen, EOCS nimmt keine Parameter, Mailcoach bräuchte den Listennamen und mehrere Listen passen | 2026-10-21 |
| Vorhandene Abläufe in der Spec geprüft und Verlinkungsstellen vorgeschlagen | Auftrag des Auftraggebers; der Autor stellt die Abläufe mit der Umsetzung um | 2026-10-21 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|
| Keine Datenbank, keine neuen Pakete | Fall-Links werden bei jeder Anzeige aus Ticket und Bestellungen gebildet, feste Ziele stehen in der Konfiguration | 2026-10-21 |
| Verweise auf dem Server ersetzen, nicht im Browser | Testbar, kein zusätzliches Skript, Ziele nur vom Server | 2026-10-21 |
| Von Hand gewählter Ablauf lädt Ticket und Bestellungen mit der Ticketnummer neu | Der Ablauf wird nachgeladen und kennt sonst den Fall nicht | 2026-10-21 |
| Dokumentansicht zeigt nur feste Ziele als Knöpfe | Dort gibt es keinen Fall; trotzdem keine kaputten Verweise | 2026-10-21 |
| Vanilo-E-Mail vom Zammad-Kunden, ersatzweise Antwortadresse der letzten Kundennachricht | Der Zammad-Kunde entspricht meist dem Shop-Konto | 2026-10-21 |
| Ein Knopf je Bestellung bei mehreren Aufträgen | Einfacher als eine Auswahl; Bestellnummer als Beschriftung | 2026-10-21 |
| `knowledge:check` meldet unbekannte Kürzel als Fehler | Tippfehler fallen vor dem Aktivieren auf | 2026-10-21 |
| Feste Adressen in der Konfiguration mit Umgebungsvariablen | Ändern sich selten; keine Einstellungsseite nötig | 2026-10-21 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

### Überblick
Es wird **keine Datenbank** gebraucht und kein neues Paket. Die Funktion arbeitet auf dem, was die Ticketseite schon weiß (Ticket, Bestellungen aus EOCS), und auf einer kleinen Liste fester Adressen in der Konfiguration. Sie ändert, wie ein Arbeitsablauf aus dem Markdown-Text in die Anzeige umgesetzt wird.

### A) Bausteine
```
Arbeitsablauf (Markdown in knowledge/)
+-- Autor schreibt: [Auftrag in EOCS öffnen](link:eocs-auftrag)

Fall-Links (NEU, "was kenne ich zu diesem Fall?")
+-- Auftrag/Aufträge in EOCS (Adresse + Bestellnummer je Bestellung)
+-- Ticket in Zammad (Adresse)
+-- E-Mail-Adresse des Kunden (für Vanilo)
+-- leer bei Frage ohne Ticket und ohne Bestellung

Feste Ziele (NEU, Konfiguration)
+-- EOCS-Kundenliste, Vanilo-Shopper, Mailcoach-Listen

Anzeige des Ablaufs (bestehender Renderer, erweitert)
+-- Link mit "link:" + Kürzel gefunden
|   +-- Ziel bekannt -> Knopf (neuer Tab), bei mehreren Bestellungen ein Knopf je Bestellung
|   +-- Ziel unbekannt -> nur der Text, kein Knopf
+-- normaler https-Link -> wie bisher

Wo der Ablauf erscheint
+-- Vorgeschlagene Abläufe auf der Ticketseite (kennen den Fall schon)
+-- Von Hand gewählter Ablauf (wird nachgeladen, bekommt Ticketnummer und Bestellnummern mit)
+-- Dokumentansicht in der Knowledge-Übersicht (kein Fall: nur feste Ziele sind Knöpfe, alle anderen Kürzel stehen als Text)

Prüfung "knowledge:check" (erweitert)
+-- unbekanntes Kürzel hinter "link:" = Fehler
```

### B) Datenmodell
Nichts wird gespeichert.

- **Feste Ziele** stehen in der Konfiguration der App (mit Umgebungsvariablen zum Überschreiben, dokumentiert in `.env.example`): EOCS-Kundenliste, Vanilo-Shopper, Mailcoach-Listen.
- **Fall-Links** werden bei jeder Anzeige aus dem frisch geladenen Ticket und den Bestellungen gebildet, nicht aus gespeicherten Daten.
- **Bestehende Abläufe** (PROCEDURE-001 bis 005) werden von Hand auf die Kürzel umgestellt, wie in der Tabelle oben.

### C) Entscheidungen, erklärt
1. **Umsetzung auf dem Server statt im Browser.** Die App ersetzt die `link:`-Verweise beim Erzeugen der Seite. So ist es mit den vorhandenen Tests prüfbar, es braucht kein zusätzliches Skript, und die Adressen kommen nur vom Server (kein Ziel, das ein Browser vorgibt).
2. **Von Hand gewählte Abläufe bekommen den Fall mit.** Der Ablauf wird heute nachgeladen, ohne dass die Seite weiß, zu welchem Ticket er gehört. Der Aufruf bekommt künftig Ticketnummer und Bestellnummern mit; die App lädt das Ticket und die Bestellungen erneut (das ist eine Einzelaktion, kein Dauerbetrieb) und bildet dieselben Fall-Links.
3. **Die Dokumentansicht zeigt feste Ziele als Knöpfe,** alles andere als Text. Dort gibt es keinen Fall, aber der Autor sieht dasselbe Dokument ohne kaputte Verweise.
4. **E-Mail für Vanilo vom Kunden im Zammad-Ticket,** ersatzweise die Antwortadresse der letzten Kundennachricht. Der Kunde im Ticket entspricht meist dem Shop-Konto, die Antwortadresse kann abweichen (anderer Absender). Ohne Adresse öffnet sich die Vanilo-Seite ohne Suchbegriff.
5. **Vanilo-Adresse mit URL-kodierter E-Mail** und der Sortierung „neueste zuerst“. Die E-Mail ist die einzige Eingabe aus dem Ticket; sie wird vollständig kodiert und nur als Wert von `email` angehängt.
6. **Mehrere Bestellungen: ein Knopf je Bestellung** mit der Bestellnummer, ohne vorherige Auswahl. Das ist einfacher, und der Mitarbeiter sieht gleich, welche Aufträge es gibt. Reklamationsaufträge (R1-…) erscheinen mit ihrer Nummer.
7. **Unbekanntes Ziel heißt nur Text.** Kein Knopf ins Leere. Die Prüfung von `knowledge:check` verhindert Tippfehler im Kürzel, bevor das Dokument aktiv wird.
8. **Gleiche Schreibweise wie ein normaler Markdown-Link.** Das Dokument bleibt in Git und im KI-Chat lesbar. Das Kürzel steht im Link-Ziel, die Beschriftung ist frei.
9. **Konfiguration statt Einstellungsseite.** Die festen Adressen ändern sich selten; eine Einstellungsseite wäre mehr Oberfläche, als nötig ist. Später möglich, wenn Admins sie ohne Entwickler pflegen sollen.
10. **Knopf-Aussehen über eine kleine Stilregel.** Der Link wird als Knopf neben dem Schritt dargestellt, ohne die Haken-Kästchen der Schritte zu stören.

### D) Zugriff und Sicherheit
- Nur `https`-Ziele aus der Konfiguration oder aus den geladenen Bestellungen und dem Ticket.
- Links öffnen in einem neuen Tab mit `noopener noreferrer`.
- Die E-Mail-Adresse des Kunden steht in der Adresse des Vanilo-Knopfs im Seitentext, geht aber nur beim Klick an das interne Vanilo. Sie wird nicht protokolliert.
- Das Markdown darf weiter kein rohes HTML enthalten; unbekannte Verweisarten bleiben verworfen.

### E) Dependencies
Keine neuen Pakete.

### F) Betroffene Bereiche (für die Umsetzung)
- Umsetzung der Abläufe in der Anzeige (`ProcedureRenderer`, Markdown-Darstellung der Dokumentansicht)
- Neuer Baustein für die Fall-Links; Aufruf auf der Ticketseite und beim Nachladen eines Ablaufs
- Konfiguration und `.env.example`
- Prüfung `knowledge:check` (Kürzel)
- Stilregel für den Knopf im Ablauf
- Wissen: PROCEDURE-001 bis 005 auf Kürzel umstellen; Authoring Guide und Skill um die Kürzel ergänzen


## Implementation Notes (2026-10-21)
- **Neu:** `app/Knowledge/CaseLinks.php` (Ziele eines Falls: Aufträge und Reklamationsaufträge aus EOCS, Ticket in Zammad, E-Mail des Kunden für Vanilo, feste Werkzeugadressen; nur `https` für feste Adressen), `app/Knowledge/CaseLinksBuilder.php` (liest Ticket und Bestellungen für einen von Hand gewählten Ablauf neu), Stilregel `.procedure-link` in `resources/css/app.css`, Konfigurationsblock `tool_links` in `config/knowledge.php` (Überschreiben mit `LINK_EOCS_CUSTOMERS`, `LINK_VANILO_SHOPPERS`, `LINK_MAILCOACH_LISTS`).
- **Geändert:** `KnowledgeMarkdown` (macht aus `[Text](link:Kürzel)` einen Knopf, mehrere Aufträge ergeben einen Knopf je Auftrag mit Nummer, ohne Ziel bleibt der Text), `ProcedureRenderer`, `AnalysisPanel` (Fall-Links an die vorgeschlagenen Abläufe), `ProcedureController` und die Komponente `analysis/procedures` (Ticketnummer und Bestellnummern gehen beim Nachladen mit), `KnowledgeValidator` (unbekanntes Kürzel ist ein Fehler).
- **Kürzel:** `eocs-auftrag`, `zammad-ticket`, `eocs-kunden`, `vanilo-kunden`, `mailcoach-listen`. Vanilo bekommt `?email=<URL-kodiert>&order_by=newest_first`, wenn eine Adresse bekannt ist (Kunde im Zammad-Ticket, ersatzweise Antwortadresse der letzten Kundennachricht), sonst die Seite ohne Suchbegriff.
- **Wissen:** PROCEDURE-001 bis 005 auf Kürzel umgestellt (Schritt 11 in PROCEDURE-001 „Ticket in Zammad erstellen“ bewusst nicht verlinkt: dort entsteht ein neues Ticket, nicht das des Falls).
- **Tests:** `PROJ-58-ToolLinksTest` (14); komplette Suite 1850 Tests grün. Mit echten Daten geprüft (Ticket 2139982): PROCEDURE-005 zeigt je einen Knopf für den Originalauftrag und den Reklamationsauftrag R1-…, PROCEDURE-004 die drei festen Ziele mit Vanilo-Suchbegriff.
- **Nicht getestet:** Browser und Breiten 375/768/1440 px.
- **Nicht angefasst:** `.env.example` (der Zugriff war gesperrt). Die drei `LINK_*`-Variablen sind optional; ohne sie gelten die Standardadressen aus der Konfiguration.

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
