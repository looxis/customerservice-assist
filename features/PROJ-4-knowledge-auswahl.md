# PROJ-4: Knowledge-Auswahl

## Status: Planned
**Created:** 2026-10-02
**Last Updated:** 2026-10-02

## Dependencies
- Requires: PROJ-3 (Knowledge Base einlesen und prüfen) – liefert die verwendbaren Dokumente, den Wissensstand und die Fingerabdrücke

PROJ-4 hat keine eigene Seite. Es liefert die Auswahl-Logik, die Wertelisten für die Eingabefelder und Vorschläge aus Bestelldaten. Die Felder „Kundengruppe" und „Produkt" selbst erscheinen im Analyse-Formular (PROJ-9).

## Begriffe
- **Fallkontext:** die Angaben, nach denen ausgewählt wird: Kundengruppe und betroffene Produkte.
- **Kundengruppe:** eine von fünf Möglichkeiten, die jeweils Kundenart und Kanal festlegen.

| Kundengruppe | Kundenart | Kanal |
|---|---|---|
| Privatkunde, eigener Shop | `b2c` | `shop` |
| Privatkunde, Amazon | `b2c` | `amazon` |
| Foto-Fachhändler / Reseller | `b2b-reseller` | `fachhaendler` (fachhaendler.looxis.de) |
| LOOXIS-Pro (Rohware in größeren Mengen) | `b2b-pro` | `looxis-pro` |
| Noch unklar | unbekannt | unbekannt |

## User Stories
- Als Kundenservice-Mitarbeiter möchte ich, dass die App zu meinem Fall genau das Wissen heranzieht, das für diese Kundengruppe und dieses Produkt gilt, damit der Vorschlag nicht auf Regeln einer anderen Gruppe beruht.
- Als Aushilfe möchte ich Kundengruppe und Produkt nur bestätigen müssen, wenn die Bestellung sie schon verrät, damit ich nichts nachschlagen muss.
- Als Mitarbeiter möchte ich bei unklarer Kundengruppe trotzdem eine Analyse starten können, damit ich die richtige Rückfrage an den Kunden bekomme, statt blockiert zu sein.
- Als Autor der Knowledge Base möchte ich für einen Fallkontext nachsehen können, welche Dokumente geladen würden und welche aus welchem Grund nicht, damit ich Lücken und falsch gesetzte Geltungsbereiche finde.
- Als Autor möchte ich, dass Entwürfe mitverwendet und als Entwurfs-Wissen gekennzeichnet werden, damit ich neues Wissen an Fällen testen kann.
- Als Verantwortlicher möchte ich, dass die Auswahl nachvollziehbar und wiederholbar ist, damit dieselben Angaben beim selben Wissensstand immer dieselben Dokumente ergeben.

## Out of Scope
- Eingabefelder „Kundengruppe" und „Produkt" im Analyse-Formular, der Aufruf des Sprachmodells und das Zusammenstellen des Prompts – PROJ-9.
- Abruf der Bestelldaten aus EOCS – PROJ-7. PROJ-4 arbeitet mit den Bestellpositionen, die es übergeben bekommt.
- Anzeige der verwendeten Quellen und des Entwurfs-Hinweises im Ergebnis – PROJ-10.
- Speichern der Auswahl zur Analyse – PROJ-11.
- Auswahl nach Art der Anfrage (Reklamation, Produktfrage, Frage zum Ablauf) oder nach Themen. Die Felder `categories` und `topics` bleiben in den Dokumenten und werden dem Sprachmodell mitgegeben, schließen aber nichts aus.
- Semantische Suche, Embeddings, Gewichtung nach Ähnlichkeit – PROJ-22.
- Vorstufe mit dem Sprachmodell zur Bestimmung der Anfrageart; kommt erst, wenn die Knowledge Base zu groß wird.
- Automatische Erkennung von Fachhändlern (PRD, Non-Goal). Die Kundengruppe wählt der Mitarbeiter.
- Seite in der App für die Vorschau der Auswahl. Im MVP gibt es dafür einen Terminal-Befehl.

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Wertelisten
- [ ] Angenommen die Kundenarten werden geprüft, wenn ein Dokument `b2c`, `b2b-reseller` oder `b2b-pro` in `customer_types` trägt, dann ist es gültig; der bisherige Wert `b2b` ist ein Fehler mit Hinweis auf die beiden neuen Werte.
- [ ] Angenommen die Kanäle werden geprüft, wenn ein Dokument `shop`, `amazon`, `fachhaendler` oder `looxis-pro` in `sales_channels` trägt, dann ist es gültig.
- [ ] Angenommen das Analyse-Formular fragt die Kundengruppen ab, wenn es die Liste erhält, dann enthält sie die fünf Gruppen in der Reihenfolge der Tabelle oben mit deutscher Bezeichnung.
- [ ] Angenommen das Analyse-Formular fragt die wählbaren Produkte ab, wenn es die Liste erhält, dann enthält sie je verwendbarer Produktdatei einen Eintrag mit Slug und Titel, alphabetisch nach Titel.

### Auswahl nach Kundengruppe
- [ ] Angenommen ein Dokument hat leere `customer_types` und leere `sales_channels`, wenn für irgendeine Kundengruppe ausgewählt wird, dann erfüllt es die Bedingung der Kundengruppe.
- [ ] Angenommen die Kundengruppe ist „Privatkunde, eigener Shop", wenn ausgewählt wird, dann werden Dokumente verwendet, deren `customer_types` leer ist oder `b2c` enthält und deren `sales_channels` leer ist oder `shop` enthält; Dokumente nur für `amazon` oder nur für eine B2B-Kundenart werden nicht verwendet.
- [ ] Angenommen die Kundengruppe ist „Privatkunde, Amazon", wenn ausgewählt wird, dann gilt dasselbe mit `amazon` statt `shop`.
- [ ] Angenommen die Kundengruppe ist „Foto-Fachhändler / Reseller", wenn ausgewählt wird, dann werden Dokumente verwendet, deren `customer_types` leer ist oder `b2b-reseller` enthält und deren `sales_channels` leer ist oder `fachhaendler` enthält.
- [ ] Angenommen die Kundengruppe ist „LOOXIS-Pro", wenn ausgewählt wird, dann werden Dokumente verwendet, deren `customer_types` leer ist oder `b2b-pro` enthält und deren `sales_channels` leer ist oder `looxis-pro` enthält.
- [ ] Angenommen die Kundengruppe ist „Noch unklar", wenn ausgewählt wird, dann werden nur Dokumente verwendet, deren `customer_types` und `sales_channels` beide leer sind.

### Auswahl nach Produkt
- [ ] Angenommen ein Dokument hat leere `products`, wenn für beliebige Produkte ausgewählt wird, dann erfüllt es die Produktbedingung.
- [ ] Angenommen der Mitarbeiter hat ein oder mehrere Produkte gewählt, wenn ausgewählt wird, dann werden zusätzlich die Dokumente verwendet, deren `products` mindestens eines der gewählten Produkte enthält, einschließlich der Produktdateien selbst.
- [ ] Angenommen der Mitarbeiter hat „kein Produktbezug / unklar" gewählt, wenn ausgewählt wird, dann werden nur Dokumente mit leeren `products` verwendet und keine Produktdatei.
- [ ] Angenommen ein gewähltes Produkt hat keine verwendbare Produktdatei mehr (z. B. inzwischen fehlerhaft), wenn ausgewählt wird, dann läuft die Auswahl weiter und das Ergebnis nennt das Produkt als „ohne Produktwissen".

### Welche Dokumente grundsätzlich in Frage kommen
- [ ] Angenommen ein Dokument hat Fehler oder den Status `deprecated`, wenn ausgewählt wird, dann wird es nie verwendet.
- [ ] Angenommen ein Dokument hat den Status `draft` und erfüllt die Bedingungen, wenn ausgewählt wird, dann wird es verwendet und im Ergebnis als Entwurfs-Wissen gekennzeichnet.
- [ ] Angenommen Dokumente unterscheiden sich in `categories` oder `topics`, wenn ausgewählt wird, dann beeinflusst das die Auswahl nicht.
- [ ] Angenommen es gibt gute und schlechte Referenzfälle, die die Bedingungen erfüllen, wenn ausgewählt wird, dann werden höchstens eine festgelegte Anzahl verwendet (Vorschlag: drei gute, zwei schlechte), bevorzugt solche mit Produktbezug zum Fall, danach nach ID.

### Ergebnis der Auswahl
- [ ] Angenommen die Auswahl ist abgeschlossen, wenn das Ergebnis betrachtet wird, dann sind die Dokumente in der Rangfolge der Knowledge Base geordnet (Policies, Permissions, Produkte, Prozesse, Playbooks, Ton, Glossar, gute Beispiele, schlechte Beispiele), innerhalb eines Typs nach ID.
- [ ] Angenommen ein Dokument wurde ausgewählt, wenn das Ergebnis betrachtet wird, dann trägt es den Grund der Auswahl in verständlicher Form (z. B. „gilt für alle", „Kundenart b2c, Kanal shop", „Produkt 3d-glass-photo").
- [ ] Angenommen ein verwendbares Dokument wurde nicht ausgewählt, wenn das Ergebnis betrachtet wird, dann ist es mit dem Grund aufgeführt (z. B. „nur für Kanal amazon", „anderes Produkt", „Obergrenze für Beispiele erreicht").
- [ ] Angenommen die Auswahl ist abgeschlossen, wenn das Ergebnis betrachtet wird, dann enthält es den Fallkontext, den Wissensstand, den Fingerabdruck jedes ausgewählten Dokuments, die Anzahl der Entwürfe und den Gesamtumfang des ausgewählten Textes in Zeichen.
- [ ] Angenommen dieselben Angaben werden bei unverändertem Wissensstand zweimal ausgewählt, wenn die Ergebnisse verglichen werden, dann sind Dokumente und Reihenfolge identisch.

### Umfang
- [ ] Angenommen der Gesamtumfang der ausgewählten Dokumente überschreitet die festgelegte Obergrenze, wenn ausgewählt wird, dann werden zuerst die Referenzfälle weggelassen, und das Ergebnis trägt eine Warnung mit Umfang und Obergrenze.
- [ ] Angenommen der Umfang liegt auch ohne Referenzfälle über der Obergrenze, wenn ausgewählt wird, dann wird kein weiteres Dokument weggelassen und die Warnung bleibt bestehen; Policies, Permissions, Produkt-, Prozess- und Playbook-Wissen werden nie stillschweigend gekürzt.

### Vorschläge aus der Bestellung
- [ ] Angenommen eine Produktdatei nennt im Feld `order_keywords` Artikelnummern oder Bezeichnungen, wenn die Positionen einer Bestellung übergeben werden, dann werden alle Produkte vorgeschlagen, bei denen ein Schlüsselwort in Artikelnummer oder Bezeichnung einer Position vorkommt, unabhängig von Groß- und Kleinschreibung.
- [ ] Angenommen keine Position passt oder es gibt keine Bestellung, wenn Vorschläge angefragt werden, dann ist der Produktvorschlag leer und es entsteht kein Fehler.
- [ ] Angenommen die Bestelldaten enthalten den Kanal, wenn ein Vorschlag für die Kundengruppe angefragt wird, dann wird die Kundengruppe dieses Kanals vorgeschlagen (Shop, Amazon, Fachhändler-Shop, LOOXIS-Pro); ohne Kanal oder bei unbekanntem Wert wird „Noch unklar" vorgeschlagen.
- [ ] Angenommen EOCS benennt einen Kanal anders als die Knowledge Base (z. B. `fachhaendler.looxis.de` statt `fachhaendler`), wenn der Vorschlag ermittelt wird, dann übersetzt eine Zuordnung in der Konfiguration die EOCS-Bezeichnung in den Kanal; die Zuordnung ist ohne Programmänderung anpassbar.
- [ ] Angenommen ein Vorschlag liegt vor, wenn der Mitarbeiter etwas anderes wählt, dann gilt ausschließlich seine Wahl.

### Prüfung der Knowledge-Dateien (Erweiterung von PROJ-3)
- [ ] Angenommen eine Produktdatei hat das Feld `order_keywords`, wenn geprüft wird, dann ist das Feld bekannt und muss eine einfache Liste sein; in anderen Dokumenttypen ist es ein unbekanntes Feld.
- [ ] Angenommen zwei Produktdateien nennen dasselbe Schlüsselwort, wenn geprüft wird, dann wird eine Warnung für beide gemeldet.
- [ ] Angenommen Kundenart und Kanal eines Dokuments passen zu keiner Kundengruppe (z. B. `b2c` zusammen mit nur `looxis-pro`), wenn geprüft wird, dann wird eine Warnung gemeldet, weil es nie ausgewählt werden kann.

### Vorschau-Befehl
- [ ] Angenommen der Autor ruft den Vorschau-Befehl mit einer Kundengruppe und optional Produkten auf, wenn er läuft, dann listet er die ausgewählten Dokumente in Reihenfolge mit ID, Titel, Status und Grund, danach die nicht ausgewählten mit Grund, den Gesamtumfang und etwaige Warnungen.
- [ ] Angenommen der Autor gibt eine unbekannte Kundengruppe oder ein unbekanntes Produkt an, wenn der Befehl läuft, dann nennt er die erlaubten Werte und endet mit einem Fehlschlag.

### Guide und Vorlagen
- [ ] Angenommen der Authoring Guide wird gelesen, wenn er Kundenarten und Kanäle beschreibt, dann nennt er `b2c`, `b2b-reseller`, `b2b-pro` sowie `shop`, `amazon`, `fachhaendler`, `looxis-pro` mit Bedeutung und beschreibt `order_keywords` für Produktdateien.
- [ ] Angenommen die Produkt-Vorlage wird geöffnet, wenn das Frontmatter betrachtet wird, dann enthält es `order_keywords`.

## Edge Cases
- **Keine Dokumente erfüllen die Bedingungen:** Ergebnis mit leerer Liste und Warnung „kein Wissen für diesen Fall"; kein Absturz.
- **Knowledge-Ordner fehlt:** leeres Ergebnis mit der Meldung aus PROJ-3.
- **Gewähltes Produkt gibt es nicht (mehr):** Die Auswahl ignoriert es und nennt es als „unbekanntes Produkt".
- **Dokument gilt für mehrere Kundenarten oder mehrere Produkte:** wird ausgewählt, sobald eine passt; erscheint nur einmal.
- **Dokument mit `customer_types: [b2c]` und leerem Kanal:** gilt für Privatkunden in Shop und Amazon.
- **Dokument mit Kanal `shop` und leerer Kundenart:** gilt nur für „Privatkunde, eigener Shop", nicht für die B2B-Gruppen, weil diese eigene Kanäle haben.
- **Dokument mit `customer_types: [b2b-reseller, b2b-pro]` und leerem Kanal:** gilt für beide B2B-Gruppen.
- **Produktdatei im Unterordner** (großes Produkt, mehrere Dateien): Alle Dateien mit dem Produkt in `products` werden geladen.
- **Schlüsselwort ist sehr kurz** (z. B. zwei Zeichen): passt auf viele Positionen; die Prüfung warnt bei Schlüsselwörtern unter drei Zeichen.
- **Bestellung mit mehreren Produkten:** Alle erkannten Produkte werden vorgeschlagen; der Mitarbeiter kann abwählen.
- **Wissensstand ändert sich zwischen Vorschlag und Analyse:** Maßgeblich ist der Stand beim Auswählen; das Ergebnis trägt die Fingerabdrücke.
- **Sehr viele Referenzfälle:** Die Obergrenze greift; die übrigen stehen bei den nicht ausgewählten.

## Technical Requirements (optional)
- Geschwindigkeit: Auswahl aus 200 Dokumenten in unter 100 Millisekunden (zusätzlich zum Einlesen).
- Obergrenzen (Anzahl Beispiele, Gesamtumfang) und Kundengruppen stehen in der Konfiguration, nicht im Code verteilt.
- Die Auswahl ändert keine Datei und speichert nichts.

## Open Questions
- [ ] Liefert EOCS den Kanal einer Bestellung, und mit welchem Wortlaut? Bekannt sind die Kanäle selbst (eigener Shop, Amazon, `fachhaendler.looxis.de`, LOOXIS-Pro), nicht aber, ob und wie EOCS sie übergibt. Die Zuordnung EOCS-Bezeichnung → Kanal ist deshalb konfigurierbar und wird mit PROJ-7 gefüllt. Übergibt EOCS nichts, wählt der Mitarbeiter ohne Vorschlag.
- [x] Bestellen Fachhändler und LOOXIS-Pro über einen eigenen Weg? → Ja: Fachhändler über `fachhaendler.looxis.de` (Kanal `fachhaendler`), LOOXIS-Pro über den Kanal `looxis-pro` (Auskunft vom 2026-10-02).
- [ ] Welche Obergrenze für den Gesamtumfang ist sinnvoll? Hängt vom Modell in PROJ-9 ab; Vorschlag für den Start: 60.000 Zeichen. In `/architecture` festlegen.
- [ ] Das Feld `limit_basis` in zwei Permissions ist der Prüfung unbekannt. Behalten und offiziell aufnehmen, oder entfernen? Gehört nicht zu PROJ-4, sollte aber vor dem ersten Test entschieden werden.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Kundenarten `b2c`, `b2b-reseller`, `b2b-pro` statt `b2c`, `b2b` | Fachhändler und LOOXIS-Pro (Rohware, größere Mengen, Auslands- und Speditionsversand) brauchen absehbar eigene Regeln; bisher nutzt kein Dokument `b2b`, die Umstellung kostet nichts | 2026-10-02 |
| Der Mitarbeiter wählt eine von fünf Kundengruppen; die App schlägt nur vor | Die Zuordnung ist fachlich entscheidend und in EOCS möglicherweise nicht vorhanden; der Mensch trägt die Verantwortung, eine automatische Fachhändler-Erkennung bleibt Non-Goal | 2026-10-02 |
| Kundengruppe bündelt Kundenart und Kanal in einer Auswahl | Ein Klick statt zwei; die vier realen Gruppen des Unternehmens sind dem Kundenservice geläufig | 2026-10-02 |
| Bei „Noch unklar" nur allgemeingültige Dokumente | Verhindert, dass eine Verbraucher-Regel auf einen Händler angewendet wird; das Ergebnis ist dann die richtige Rückfrage (POLICY-004) | 2026-10-02 |
| Vier Kanäle: `shop`, `amazon`, `fachhaendler`, `looxis-pro`; jede Kundengruppe hat genau einen | Fachhändler bestellen über fachhaendler.looxis.de, LOOXIS-Pro über einen eigenen Kanal; damit gilt für alle Gruppen dieselbe Auswahlregel | 2026-10-02 |
| Die Übersetzung der EOCS-Kanalbezeichnung in den Kanal steht in der Konfiguration | Der genaue Wortlaut aus EOCS ist noch unbekannt und kann sich ändern | 2026-10-02 |
| Kein Filter nach Art der Anfrage oder Themen | Die Anfrageart steht erst nach der Analyse fest; eine Vorauswahl durch eine Aushilfe könnte das richtige Playbook ausblenden. Tragbar, solange die Knowledge Base überschaubar ist | 2026-10-02 |
| Produkt wird vom Mitarbeiter gewählt, vorbelegt über Schlüsselwörter in der Produktdatei | Gleiches Muster wie bei der Kundengruppe; funktioniert vor der EOCS-Anbindung und bei neuen Artikeln | 2026-10-02 |
| Die Zuordnung Bestellposition → Produkt steht in der Produktdatei (`order_keywords`), nicht im Code | Der Autor pflegt sie zusammen mit dem Produktwissen; ein neues Produkt braucht keine Programmänderung | 2026-10-02 |
| Referenzfälle sind in der Anzahl begrenzt und fallen bei Überschreitung des Umfangs zuerst weg | Beispiele haben den niedrigsten Rang und sind laut Design-Dokument „wenige"; verbindliches Wissen wird nie stillschweigend gekürzt | 2026-10-02 |
| Das Ergebnis nennt auch die nicht ausgewählten Dokumente mit Grund | Der Autor erkennt falsch gesetzte Geltungsbereiche nur, wenn er sieht, was warum fehlt | 2026-10-02 |
| Vorschau als Terminal-Befehl statt als Seite | Macht PROJ-4 ohne Oberfläche abnehmbar; eine Seite kann später folgen | 2026-10-02 |

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
