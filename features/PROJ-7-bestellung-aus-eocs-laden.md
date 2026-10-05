# PROJ-7: Bestellung aus EOCS laden

## Status: Architected
**Created:** 2026-10-02
**Last Updated:** 2026-10-05

## Dependencies
- Requires: PROJ-6 (Zammad-Ticket laden) – Ticketseite, Ticketkopf, Verlauf als Quelle für Bestellnummern, erkannte Amazon-Bestellungen
- Wird genutzt von: PROJ-4 (Vorschlag der Kundengruppe aus dem Kanal der Bestellung, Zuordnung `order_channels`), PROJ-8 (Bestelldaten von Hand ergänzen), PROJ-9 (Bestelldaten an die Analyse)

## Begriffe
- **Externe Bestellnummer:** die Nummer des Kanals, über den bestellt wurde. EOCS findet Bestellungen über diese Nummer.
- **EOCS-Nummer:** die interne, sechsstellige Nummer von EOCS (steht z. B. auf Barcode-Labels).

| Kanal | Format der externen Bestellnummer | Beispiel |
|---|---|---|
| Amazon | 3 – 7 – 7 Ziffern | `402-4907715-1581912` |
| looxis.de, fachhaendler.looxis.de | 3 – 4 – 4 Zeichen, Großbuchstaben und Ziffern | `7JI-0WC1-6M49` |
| LOOXIS-Pro | 8 Ziffern, beginnt mit `300`, optional mit Präfix `BEST-PRO` | `30019578`, `BEST-PRO30019578` |
| looxis.fr (Fachhändler) | 9 Ziffern, beginnt mit `700` | `700411247` |
| masterpics (White-Label-Kunde) | 10 Zeichen, Groß- und Kleinbuchstaben und Ziffern | `nG3tnd9sVY` |
| EOCS intern | 6 Ziffern | – |

## User Stories
- Als Mitarbeiter möchte ich, dass die App mir die Bestellnummern aus dem Ticket als Vorschläge anbietet, damit ich sie nicht im Verlauf suchen und abtippen muss.
- Als Mitarbeiter möchte ich eine Bestellnummer auch von Hand eintragen können, wenn die App keine findet oder der Kunde sie nur am Telefon genannt hat.
- Als Mitarbeiter möchte ich zu einer Bestellung auf einen Blick Kanal, Datum und Status sehen und mit einem Klick die vollständige Bestellung in EOCS öffnen.
- Als Mitarbeiter möchte ich mehrere Bestellungen zu einem Ticket laden können, wenn der Kunde mehrere Bestellungen reklamiert.
- Als Mitarbeiter möchte ich bei einem Fehler (Bestellung nicht gefunden, EOCS nicht erreichbar) eine verständliche Meldung bekommen und trotzdem weiterarbeiten können.

## Out of Scope
- Ausführliche Bestelldaten im Ticket (Produkte, Personalisierung, Produktion, Versand, Adressen) – vorerst nur über den Link in EOCS; was kompakt angezeigt wird, wird später festgelegt (siehe Open Questions).
- Erkennung von Bestellnummern per KI (n8n-Workflow) – später; im MVP feste Muster und Eingabe von Hand.
- Bestelldaten von Hand ergänzen, wenn EOCS nichts liefert – PROJ-8.
- Übergabe der Bestelldaten an die Analyse – PROJ-9.
- Speichern der gewählten Bestellungen zum Ticket – PROJ-11 (bis dahin steht die Auswahl in der Adresse der Ticketseite).
- Item-API (Produktdetails) – für PROJ-7 nicht nötig (siehe `docs/eocs-items-api.md`); API-Beschreibung der Bestellungen in `docs/orders-api.md`.
- Änderungen an Bestellungen in EOCS (Non-Goal).

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Vorschläge aus dem Ticket
- [ ] Angenommen ein Ticket ist geladen, wenn der Nutzer den Ticketkopf betrachtet, dann steht dort ein Bereich „Bestellung" mit den im Ticket gefundenen Bestellnummern als anklickbare Vorschläge und einem Feld zum Eintragen von Hand.
- [ ] Angenommen der Verlauf enthält Bestellnummern in einem der Formate aus der Tabelle oben, wenn das Ticket angezeigt wird, dann erscheint jede gefundene Nummer genau einmal als Vorschlag, mit dem vermuteten Kanal.
- [ ] Angenommen eine Nummer steht nur in einem eingeklappten Zitat oder in einer unserer Antworten, wenn das Ticket angezeigt wird, dann wird sie trotzdem vorgeschlagen.
- [ ] Angenommen PROJ-6 hat eine Amazon-Bestellung aus der Amazon-Nachricht erkannt, wenn das Ticket angezeigt wird, dann ist diese Nummer als erster Vorschlag aufgeführt.
- [ ] Angenommen ein Vorschlag wird angeklickt, wenn die Bestellung geladen ist, dann erscheint sie als eigener Block im Ticketkopf, und der Vorschlag ist als geladen markiert.
- [ ] Angenommen das Ticket enthält keine erkennbare Nummer, wenn es angezeigt wird, dann steht dort „Keine Bestellnummer gefunden – bitte von Hand eintragen", und das Feld ist sichtbar.
- [ ] Angenommen das Ticket wird angezeigt, wenn noch nichts gewählt ist, dann wird keine Bestellung automatisch aus EOCS geladen.

### Eingabe von Hand
- [ ] Angenommen der Nutzer trägt eine Nummer ein, wenn er „Laden" klickt oder Enter drückt, dann wird die Bestellung aus EOCS geladen.
- [ ] Angenommen die Eingabe enthält Leerzeichen, Zeilenumbrüche oder das Präfix `BEST-PRO` bzw. `#`, wenn geladen wird, dann wird sie wie die bereinigte Nummer behandelt; Groß- und Kleinschreibung bleibt erhalten, wo das Format sie unterscheidet (masterpics).
- [ ] Angenommen die Eingabe passt zu keinem bekannten Format, wenn geladen werden soll, dann erscheint direkt am Feld „Unbekanntes Format. Erwartet z. B. 402-4907715-1581912 oder 7JI-0WC1-6M49.", und EOCS wird nicht gefragt.
- [ ] Angenommen eine Bestellung wurde geladen, wenn der Link „In EOCS öffnen" betrachtet wird, dann zeigt er auf `https://eocs.loox.is/orders/{ID}` mit der internen ID der Bestellung.
- [ ] Angenommen eine sechsstellige EOCS-Nummer wird eingetragen, wenn geladen wird, dann sucht die App über die EOCS-Nummer, sofern EOCS das unterstützt; sonst erscheint ein Hinweis, die externe Nummer zu verwenden (siehe Open Questions).

### Anzeige einer geladenen Bestellung
- [ ] Angenommen eine Bestellung wurde geladen, wenn der Block betrachtet wird, dann zeigt er externe Bestellnummer, EOCS-ID, Kanal (`client.name`), Bestelldatum, Bestellstatus (`status_name`, farbig nach `state.color`), Rechnungsnummer (`invoice.document_number`) und je Sendung Dienstleister, Sendungsnummer (als Text, nicht klickbar), Versanddatum und ob zugestellt.
- [ ] Angenommen EOCS liefert Name, E-Mail, Adresse, Telefon und Zahlungsdaten, wenn der Block angezeigt wird, dann erscheinen diese nicht im Ticketkopf (nur über „In EOCS öffnen").
- [ ] Angenommen eine Bestellung wurde geladen, wenn der Nutzer auf „In EOCS öffnen" klickt, dann öffnet sich die Bestellung in EOCS in einem neuen Tab.
- [ ] Angenommen zu einer geladenen Bestellung gibt es in EOCS Reklamationsaufträge (externe Nummer `R1-…`, `R2-…` mit Verweis auf die Original-Bestellung), wenn der Block betrachtet wird, dann steht darunter je Reklamationsauftrag ein Hinweis mit Nummer, Datum, Status und „In EOCS öffnen".
- [ ] Angenommen eine Bestellung wurde geladen, wenn sie für spätere Schritte bereitsteht, dann sind auch ihre Positionen (Artikel, Artikelnummer, Anzahl, Konfigurations-ID, Personalisierungsdaten) geladen, auch wenn sie im Kopf noch nicht angezeigt werden (für PROJ-4 und PROJ-9).
- [ ] Angenommen dieselbe Bestellung wurde von PROJ-6 aus einer Amazon-Nachricht erkannt, wenn sie aus EOCS geladen wird, dann erscheint sie als ein Block mit den Angaben aus beiden Quellen, nicht doppelt.
- [ ] Angenommen mehrere Bestellungen wurden geladen, wenn der Kopf betrachtet wird, dann steht jede in einem eigenen Block, und jeder Block lässt sich einzeln wieder entfernen.
- [ ] Angenommen Bestellungen wurden geladen, wenn der Nutzer die Seite neu lädt oder den Link an einen Kollegen schickt, dann sind dieselben Bestellungen wieder geladen, frisch aus EOCS.
- [ ] Angenommen eine Bestellung wurde geladen, wenn danach in der App nachgesehen wird, dann sind keine Bestelldaten dauerhaft gespeichert.

### Fehler
- [ ] Angenommen EOCS kennt die Nummer nicht, wenn geladen wird, dann erscheint „Bestellung … wurde in EOCS nicht gefunden.", und die Eingabe bleibt im Feld.
- [ ] Angenommen EOCS liefert zu einer Nummer mehrere Bestellungen, wenn geladen wird, dann werden alle als eigene Blöcke gezeigt, mit Hinweis „Mehrere Bestellungen zu dieser Nummer".
- [ ] Angenommen EOCS ist nicht erreichbar oder antwortet nicht innerhalb von 10 Sekunden, wenn geladen wird, dann erscheint „EOCS ist gerade nicht erreichbar. Bitte erneut versuchen." mit „Erneut versuchen"; das Ticket bleibt vollständig sichtbar.
- [ ] Angenommen der Zugang der App zu EOCS fehlt oder ist ungültig, wenn geladen wird, dann erscheint eine verständliche Meldung ohne technische Details; der genaue Fehler steht im Log ohne Bestelldaten.
- [ ] Angenommen ein EOCS-Fehler tritt auf, wenn das Ticket angezeigt wird, dann funktionieren Ticketansicht und Vorschläge weiter.

## Edge Cases
- **Nummer kommt mehrfach im Verlauf vor** (Zitate, unsere Antworten): ein Vorschlag.
- **Mehrere verschiedene Nummern im Ticket:** alle als Vorschläge, in der Reihenfolge ihres ersten Auftretens; Amazon-Bestellung aus PROJ-6 zuerst.
- **Fehltreffer des masterpics-Musters** (zufällige 10-Zeichen-Folge, z. B. Sendungsnummer oder Code): nur Wörter mit Groß-, Kleinbuchstaben und Ziffern zählen; ein Fehltreffer ist nur ein Vorschlag, geladen wird erst nach Klick.
- **Sendungsnummern und Telefonnummern** (z. B. `CM983471129DE`, `0031626176737`): werden nicht als Bestellnummer vorgeschlagen.
- **Kanal laut EOCS weicht vom vermuteten Kanal ab:** Es gilt der Kanal aus EOCS.
- **Bestellung ohne Versand** (noch in Produktion): Versandstatus „noch nicht versandt".
- **Sehr viele Treffer** (z. B. Fachhändler mit zehn Bestellungen in einer Mail): höchstens zehn Vorschläge, Hinweis „weitere von Hand eintragen".
- **Bestellnummer mit Kleinbuchstaben eingetippt** (z. B. `7ji-0wc1-6m49`): für Shop-Nummern in Großbuchstaben umgewandelt.

## Technical Requirements (optional)
- Ladezeit einer Bestellung unter 2 Sekunden; Abbruch nach 10 Sekunden.
- Zugriff auf EOCS nur lesend mit einem Sanctum-Bearer-Token in der Umgebungskonfiguration (`EOCS_URL` ohne `/api/v1`, `EOCS_TOKEN`, `EOCS_TIMEOUT`), nie im Browser; eigenes Device mit reinen Leserechten.
- Testdaten nur erfunden, keine echten Bestellungen oder Kundendaten im Repository.
- Keine dauerhafte Speicherung von Bestelldaten in PROJ-7; im Log keine Bestelldaten.
- Muster für Bestellnummern zentral an einer Stelle, damit sie später durch den KI-Workflow ersetzt oder ergänzt werden können.

## Open Questions
- [x] looxis.fr: 9-stellig, beginnt mit `700` (Klärung 2026-10-05).
- [x] Suche über die EOCS-ID: `filter[id]=…` funktioniert; `/api/v1/orders/{id}` antwortet mit 401 und wird nicht genutzt (Test 2026-10-05).
- [x] Positionen: `include=order_items` liefert je Position `quantity`, `position`, `status_name`, `custom_code` (Konfigurations-ID) mit `custom_code_url` (Konfigurator), `shop`, `item{item_id, name, product_type, configurations, individual}` und `data` (bei Amazon u. a. `asin`, `customizationData`). Die Artikelnummer `item.item_id` entspricht den `order_keywords` der Produktdateien (Test 2026-10-05).
- [x] Kanalnamen in EOCS (`client.name`): `looxis.de Vanilo` → shop, `fachhaendler.looxis.de` → fachhaendler, `reseller.looxis.fr` → fachhaendler, `looxis-pro.com` → looxis-pro, `Masterpics White Label DE` → masterpics, `Amazon.*` (Plattform `Amazon`) → amazon. Vollständig in `docs/orders-api.md` (2026-10-05).
- [ ] Soll der Kanal „shop" künftig „looxis.de" heißen – nur als Bezeichnung in der App oder auch als Wert in der Knowledge Base?
- [x] Authentifizierung über `Authorization: Bearer …` funktioniert; falscher Token → 401, unbekannte Nummer → leere Liste (Test 2026-10-05). Offen bleibt nur, ob ein eigenes Device mit reinen Leserechten angelegt wird.
- [ ] Der Token enthält `|` und muss in der `.env` in Anführungszeichen stehen; ein Teil wurde in einer Fehlerausgabe sichtbar – Token nach Abschluss neu erstellen.
- [x] `ticket_number` wird von Agents manchmal bei Reklamationen eingetragen (Reklamation als neuer Auftrag mit Verweis auf Original und Ticket). Filtern danach erlaubt die API nicht (erlaubte Filter: `external_order_id, id, claim, statuses, tags, pro, individual, too_late, stocked, name, merchant, ordered_at, created_at, producible`).
- [x] Reklamationsaufträge (Folgeaufträge wie `R1-<externe Nummer>` mit `origin_order_id`) erscheinen als Hinweis im Block der Original-Bestellung (Entscheidung 2026-10-05).
- [x] Felder von `GET /api/v1/orders?filter[external_order_id]=…&include=shipments`: `data[]` mit `id`, `external_order_id`, `order_date`, `production_release`, `status`, `status_name`, `state{name,color}`, `client{name, sales_platform{name}}`, `invoice{document_number}`, `shipping{…Adresse}`, `billing{…}`, `shipments.data[]{carrier{name}, tracking_no, shipping_method{name}, created_at, delivered}` (Beispiel vom Product Owner, 2026-10-05).
- [x] Adresse in EOCS: `https://eocs.loox.is/orders/{ID}`, die ID ist die interne Bestellnummer (z. B. looxis.fr-Bestellung `700411247` → `https://eocs.loox.is/orders/756743`) (Klärung 2026-10-05).
- [x] masterpics: neue Kundengruppe „White-Label-Kunde, masterpics" mit Kundenart `b2b-whitelabel` und Kanal `masterpics`; in PROJ-4 und im Authoring Guide ergänzt (2026-10-05).
- [x] Item-API (`docs/eocs-items-api.md`) geprüft: liefert Produkt-Stammdaten (Name, Artikelnummer, GTIN, Produktgruppe/-art, individualisiert), keine Bestellpositionen. Für PROJ-7 nicht nötig; später nützlich, um das Knowledge-Produkt aus Produktgruppe/-art vorzuschlagen (PROJ-4). Auth: Sanctum-Bearer-Token.
- [ ] Was soll später kompakt im Ticketkopf stehen (Produkte, Personalisierung, Versand, Adressen)? Festlegen, wenn die echten EOCS-Daten bekannt sind.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Vorschläge nach festen Mustern plus Eingabe von Hand; geladen wird erst nach Auswahl | Kostet keine KI und ist nachvollziehbar; der Mitarbeiter bestätigt; später ersetzt der n8n-KI-Workflow die Muster | 2026-10-05 |
| Amazon-Bestellung aus PROJ-6 ist erster Vorschlag und wird beim Laden mit den EOCS-Daten zusammengeführt | Kein doppelter Block, die zuverlässigste Quelle zuerst | 2026-10-05 |
| Kanal looxis.fr wird der Kundengruppe Fachhändler zugeordnet | Französischer Fachhändler-Shop |
| Mehrere Bestellungen je Ticket, jede als eigener Block, einzeln entfernbar | Kunden reklamieren mitunter mehrere Bestellungen in einer Mail | 2026-10-05 |
| Vorerst nur Bestellung und Status kompakt, Details über „In EOCS öffnen" | Was im Kopf gebraucht wird, zeigt sich erst mit echten Daten; EOCS zeigt bereits alles | 2026-10-05 |
| Auswahl der Bestellungen in der Adresse der Ticketseite, Daten jedes Mal frisch | Neuladen und Link an Kollegen funktionieren; nichts gespeichert bis PROJ-11 | 2026-10-05 |
| masterpics als eigener Kanal aufnehmen | Neuer White-Label-Kunde mit eigenem Nummernformat | 2026-10-05 |
| EOCS-Fehler blockieren die Ticketansicht nicht | Das Ticket bleibt bearbeitbar, PROJ-8 ist der Ausweichweg | 2026-10-05 |
| Reklamationsaufträge als Hinweis im Block der Original-Bestellung | Zeigt sofort, ob schon reklamiert oder nachproduziert wurde – wichtig für wiederholte Reklamationen und für die Analyse | 2026-10-05 |
| Positionen werden mitgeladen, aber noch nicht angezeigt | Produktvorschlag (PROJ-4, über Artikelnummer) und Analyse (PROJ-9) brauchen sie; die Anzeige wird später festgelegt | 2026-10-05 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|
| Eigener Baustein `app/Eocs/` nach dem Muster von `app/Zammad/`, Laravel-HTTP-Client | Einheitliche Struktur, Tests mit Attrappen, keine neuen Pakete | 2026-10-05 |
| Kundendaten aus EOCS nicht ins Bestellobjekt übernehmen | Datensparsamkeit: werden weder angezeigt noch (bisher) für die Analyse gebraucht | 2026-10-05 |
| Bestellnummern-Erkennung als eigener Baustein `app/Orders/` mit festen Mustern | Einzeln testbar, später durch den KI-Workflow ersetzbar | 2026-10-05 |
| Auswahl der Bestellungen als Liste in der Adresse, höchstens 10 | Neuladen, Zurück, Link an Kollegen; keine Speicherung bis PROJ-11 | 2026-10-05 |
| Reklamationsaufträge über Suche ohne `,exact`, gefiltert auf `R<n>-<Original>` und `origin_order_id` | EOCS hat keinen Filter für Folgeaufträge; doppelte Bedingung verhindert Fremdtreffer | 2026-10-05 |
| Fehler „nicht gefunden" je Nummer, „nicht erreichbar/Zugang ungültig" einmal; Ticket bleibt sichtbar | Spec; EOCS-Probleme dürfen die Bearbeitung nicht blockieren | 2026-10-05 |
| EOCS-Statusfarbe auf Badge-Töne des Design Systems abbilden | Gewohnte Farben aus EOCS, aber im LOOXIS-Design | 2026-10-05 |
| Abfragen mehrerer Bestellungen parallel | Bleibt auch bei zehn Bestellungen unter 2 s | 2026-10-05 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

### Überblick
PROJ-7 folgt dem Muster der Zammad-Anbindung (PROJ-6): ein eigener EOCS-Baustein liest Bestellungen nur lesend über die Orders-API (`docs/orders-api.md`) und übersetzt sie in eigene, einfache Bestellobjekte. Die Ticketseite zeigt diese an. Es gibt weder Datenbank noch Zwischenspeicher; welche Bestellungen zu einem Ticket geladen sind, steht in der Adresse der Ticketseite. Die Erkennung von Bestellnummern ist ein eigener kleiner Baustein, damit ihn später der KI-Workflow ersetzen kann.

### A) Bausteine
```
Ticketseite (/tickets/{nummer}?bestellungen[]=…)       (PROJ-6, erweitert)
+-- Ticketkopf
    +-- Bereich „Bestellungen" (neue Komponente)
        +-- Vorschläge: gefundene Nummern als Knöpfe mit vermutetem Kanal
        |   (Amazon aus PROJ-6 zuerst; geladene markiert; höchstens 10)
        +-- Feld „Bestellnummer von Hand" + „Laden"
        +-- Meldung bei EOCS-Fehler (einmal, mit „Erneut versuchen")
        +-- je geladener Bestellung ein Block (neue Komponente)
            +-- externe Nummer, EOCS-ID, Kanal, Datum, Status-Badge (Farbe aus EOCS)
            +-- Rechnungsnummer
            +-- Sendungen: Dienstleister, Sendungsnummer (Text), Datum, zugestellt
            +-- Reklamationsaufträge: Nummer, Datum, Status, „In EOCS öffnen"
            +-- Produkte aus der Amazon-Nachricht (falls vorhanden, wie bisher)
            +-- „In EOCS öffnen", „Entfernen"
        +-- Hinweis „nicht gefunden" je Nummer, die EOCS nicht kennt

Server
+-- Bestellnummern-Erkennung (app/Orders/)
|   +-- feste Muster je Kanal an einer Stelle (Amazon, looxis.de/Fachhändler,
|   |   LOOXIS-Pro mit/ohne BEST-PRO, looxis.fr, masterpics, EOCS-ID)
|   +-- Bereinigung einer Eingabe (Leerzeichen, #, BEST-PRO, Großschreibung bei Shop-Nummern)
|   +-- Suche im ganzen Verlauf inkl. Zitate, Reihenfolge des ersten Auftretens
+-- EOCS-Baustein (app/Eocs/)
|   +-- Suche über externe Nummer (exakt) oder EOCS-ID, mit Sendungen und Positionen
|   +-- Reklamationsaufträge: zweite Suche ohne „exakt", nur Treffer mit Verweis auf die Bestellung
|   +-- Fehlerarten: nicht gefunden · nicht erreichbar · Zugang ungültig
|   +-- Bestellobjekt (Kopf, Status, Sendungen, Positionen, Reklamationsaufträge)
+-- Ticket-Controller (erweitert): liest die Auswahl aus der Adresse, lädt die Bestellungen,
|   führt sie mit den Amazon-Erkennungen aus PROJ-6 zusammen
+-- Eingabe-Prüfung (Form Request) für „von Hand laden" und „entfernen":
    bereinigt, prüft das Format, leitet auf die Ticketadresse mit neuer Auswahl weiter
```

### B) Daten (nichts wird gespeichert)
- **Auswahl:** Liste der Bestellnummern in der Adresse der Ticketseite, höchstens 10.
- **Bestellung (je Aufruf frisch aus EOCS):** EOCS-ID, externe Nummer, Kanalname (`client.name`) und daraus der Kanal der Knowledge Base über die bestehende Zuordnung `knowledge.order_channels`, Bestelldatum, Status (Text und Farbe), Rechnungsnummer, Sendungen, Positionen, Reklamationsaufträge.
- **Position:** Artikelnummer (`item.item_id`), Name, Produktart, Anzahl, Status, Konfigurations-ID, Personalisierungsdaten. Wird geladen, aber noch nicht angezeigt (für PROJ-4 und PROJ-9).
- **Kundendaten** (Name, E-Mail, Adressen, Telefon, Zahlung) werden gelesen, aber nicht ins Bestellobjekt übernommen – sie verlassen den EOCS-Baustein nicht.
- **Konfiguration:** `EOCS_URL`, `EOCS_TOKEN`, `EOCS_TIMEOUT` über `config/services.php`; Bestellungen in der EOCS-Oberfläche unter `{EOCS_URL}/orders/{ID}`.

### C) Technische Entscheidungen (für Nicht-Entwickler)
- **Eigener EOCS-Baustein nach dem Zammad-Muster:** Die Seite und später PROJ-8/9/11 kennen nur das eigene Bestellobjekt. Ändert sich die EOCS-API, wird nur dieser Baustein angepasst. Laravels HTTP-Client bringt Zeitlimit und Test-Attrappen mit; kein neues Paket.
- **Kundendaten nicht ins Bestellobjekt:** Was nicht angezeigt wird und für die Analyse nicht gebraucht wird, soll gar nicht erst durch die App wandern (Datensparsamkeit). Ob Lieferland o. Ä. später für die Analyse gebraucht wird, entscheidet PROJ-9.
- **Erkennung als eigener Baustein mit festen Mustern:** Die Muster stehen an einer Stelle und sind einzeln getestet. Der KI-Workflow (n8n) kann den Baustein später ersetzen, ohne dass sich an Seite oder EOCS-Anbindung etwas ändert. Shop und Fachhändler DE haben dasselbe Format; der Vorschlag nennt dann „looxis.de / Fachhändler", den genauen Kanal liefert EOCS.
- **Auswahl in der Adresse statt Sitzung:** Neuladen, Zurück und Link an Kollegen funktionieren; jeder Aufruf holt frische Daten (Spec). Hinzufügen und Entfernen sind normale Links bzw. ein kleines Formular, das auf die neue Adresse weiterleitet – kein JavaScript nötig, Alpine.js nur für das Lade-Overlay.
- **Reklamationsaufträge über eine zweite, unscharfe Suche:** EOCS bietet keinen Filter „Folgeaufträge zu ID x". Die Suche ohne „exakt" findet sie; behalten werden nur Treffer, deren Nummer `R<Zahl>-<Original>` lautet **und** die auf die Bestellung verweisen. So rutscht nichts Fremdes hinein.
- **EOCS-Fehler je Bestellung, nicht für die Seite:** Ticket und Vorschläge bleiben immer sichtbar (Spec). „Nicht erreichbar" und „Zugang ungültig" betreffen alle Bestellungen und erscheinen einmal; „nicht gefunden" je Nummer.
- **Statusfarbe aus EOCS:** EOCS liefert eine Farbe je Status (z. B. `green`); sie wird auf die Badge-Töne des Design Systems übersetzt (grün → Erfolg, gelb/orange → Warnung, rot → Fehler, blau → Info, sonst neutral).
- **Ladezeit:** Je Bestellung zwei Abfragen (Bestellung, Reklamationsaufträge), je unter 0,3 s. Bei mehreren Bestellungen werden die Abfragen parallel geschickt, damit auch zehn Bestellungen unter 2 s bleiben.
- **Zusammenführen mit der Amazon-Erkennung aus PROJ-6:** Gleiche externe Nummer → ein Block; Produkte aus der Amazon-Nachricht bleiben sichtbar, Status und Rechnungsnummer kommen aus EOCS.

### D) Abhängigkeiten
Keine neuen Pakete.

### E) Hinweise für /frontend und /backend
- Frontend: Komponenten für den Bereich „Bestellungen" und den Bestellblock; der bisherige Amazon-Block aus PROJ-6 geht im Bestellblock auf.
- Backend: `app/Orders/` (Erkennung), `app/Eocs/` (Client, Bestellung, Sendung, Position, Reklamationsauftrag, Fehler), Form Request, Controller-Erweiterung, `services.eocs`.
- `.env.example`: `EOCS_URL=`, `EOCS_TOKEN=`, `EOCS_TIMEOUT=10` (trägt der Product Owner ein).
- Tests nur mit erfundenen Daten: Muster je Kanal inkl. Fehltreffer (Sendungs-, Telefonnummern), Bereinigung, EOCS nachgestellt (Treffer, mehrere Treffer, nicht gefunden, 401, Zeitüberschreitung), Reklamationsaufträge (nur echte Folgeaufträge), Zusammenführen mit Amazon, Auswahl in der Adresse (Hinzufügen, Entfernen, höchstens 10), keine Kundendaten auf der Seite und im Log.


## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
