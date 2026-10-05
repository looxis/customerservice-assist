# PROJ-4: Knowledge-Auswahl

## Status: Approved
**Created:** 2026-10-02
**Last Updated:** 2026-10-05

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
- [x] Welche Obergrenze für den Gesamtumfang ist sinnvoll? → 60.000 Zeichen Dokumenttext für den Start, in der Konfiguration; mit PROJ-9 überprüfen (2026-10-05).
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
| Eigener Auswahl-Baustein neben der Knowledge-Bibliothek | Trennung Einlesen/Prüfen von Auswählen; PROJ-9, PROJ-13 und der Vorschau-Befehl nutzen dieselbe Auswahl | 2026-10-05 |
| Kundengruppen, Kanal-Zuordnung und Obergrenzen in `config/knowledge.php` | Vorgabe der Spec; änderbar ohne Programmänderung, passt zu den bestehenden Wertelisten | 2026-10-05 |
| Eine Kundengruppen-Regel für Auswahl und Prüfwarnung „passt zu keiner Gruppe" | Auswahl und Prüfung können nicht auseinanderlaufen | 2026-10-05 |
| Auswahlergebnis als festes Objekt im Speicher, keine Datenbank | Die Auswahl speichert nichts (Spec); PROJ-10/11 übernehmen Gründe, Fingerabdrücke und Wissensstand unverändert | 2026-10-05 |
| Obergrenze 60.000 Zeichen Dokumenttext ohne Frontmatter | ca. 15.000 Tokens, viel Reserve für aktuelle Modelle; ohne Modellwissen messbar; anpassbar mit PROJ-9 | 2026-10-05 |
| Produkt-Slug = Dateiname, bei aufgeteilten Produkten Name des Unterordners | Fortsetzung der PROJ-3-Regel; deckt den im Design-Dokument erlaubten Produktordner ab | 2026-10-05 |
| Ausschlussgrund ist der erste zutreffende in fester Reihenfolge (Kundenart, Kanal, Produkt, Obergrenze) | Eindeutige, wiederholbare Begründung je Dokument | 2026-10-05 |
| Kanal `shop` heißt jetzt `looxis-de`, Kundengruppe „Privatkunde, looxis.de" (Schlüssel `private-looxis-de`); `shop` ist ein Fehler mit Hinweis; Kanal-Zuordnung kennt die EOCS-Namen (`looxis.de Vanilo`, `fachhaendler.looxis.de`, `reseller.looxis.fr`, `looxis-pro.com`, `Masterpics White Label DE`) | Wunsch des Product Owners: einheitliche Benennung nach Shop-Domain; fünf Knowledge-Dateien umgestellt | 2026-10-05 |
| Erweiterung: Kundenart `b2b-whitelabel`, Kanal `masterpics`, Kundengruppe „White-Label-Kunde, masterpics" (Schlüssel `whitelabel-masterpics`); Kanal-Zuordnung `looxis.fr` → `fachhaendler`, `masterpics` → `masterpics` | Neuer White-Label-Kunde mit strengeren Regeln (z. B. keine Kulanz bei Unzustellbarkeit, weil der Auftraggeber die Adresse liefert); aufgekommen in PROJ-7. Sechs statt fünf Kundengruppen | 2026-10-05 |
| `policy-008` wird auf `b2b-reseller` + `fachhaendler` umgestellt | Das Dokument nutzt entgegen der Spec-Annahme `b2b` und würde sonst ein Prüffehler | 2026-10-05 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

### Überblick
PROJ-4 läuft ganz im Backend und hat keine eigene Oberfläche. Es setzt auf der Lese- und Prüfschicht aus PROJ-3 auf: Alle Dokumente kommen weiterhin nur über die zentrale Knowledge-Bibliothek, und die Auswahl arbeitet nur mit den dort als verwendbar markierten Dokumenten. Neu sind ein Auswahl-Baustein, ein Vorschlags-Baustein, Erweiterungen der Prüfung, ein Terminal-Befehl und Einträge in der Knowledge-Konfiguration. Es gibt keine Datenbanktabelle; die Auswahl speichert nichts (das übernimmt PROJ-11).

### A) Bausteine
```
Knowledge-Konfiguration (config/knowledge.php)
+-- Wertelisten: Kundenarten (b2c, b2b-reseller, b2b-pro), Kanäle (shop, amazon, fachhaendler, looxis-pro)
+-- Kundengruppen: fünf Einträge mit Schlüssel, deutscher Bezeichnung, Kundenart, Kanal (Reihenfolge = Anzeige)
+-- Kanal-Zuordnung: EOCS-Bezeichnung -> Kanal (vorerst leer bzw. mit den bekannten Werten, Füllung mit PROJ-7)
+-- Obergrenzen: 3 gute Beispiele, 2 schlechte Beispiele, 60.000 Zeichen Gesamtumfang

Knowledge-Bibliothek (PROJ-3, unverändert in der Rolle)
+-- liefert verwendbare Dokumente, Wissensstand, Fingerabdrücke

Kundengruppe (neu)
+-- kennt Kundenart und Kanal einer Gruppe
+-- beantwortet „gilt dieses Dokument für mich?" – die einzige Stelle mit dieser Regel

Fallkontext (neu)
+-- gewählte Kundengruppe
+-- gewählte Produkte (leer = „kein Produktbezug / unklar")

Knowledge-Auswahl (neu)
+-- Wertelisten für das Formular: Kundengruppen, wählbare Produkte
+-- Auswahl für einen Fallkontext -> Auswahlergebnis

Auswahlergebnis (neu, nur im Speicher)
+-- Fallkontext und Wissensstand
+-- ausgewählte Dokumente in Rangfolge, je mit Grund, Entwurfs-Kennzeichen, Fingerabdruck
+-- nicht ausgewählte Dokumente mit Grund
+-- unbekannte Produkte, Produkte ohne Produktwissen
+-- Anzahl Entwürfe, Gesamtumfang in Zeichen, Warnungen

Vorschläge (neu)
+-- Produkte aus Bestellpositionen (über order_keywords der Produktdateien)
+-- Kundengruppe aus dem Kanal der Bestellung (über die Kanal-Zuordnung)

Prüfung (Erweiterung PROJ-3)
+-- b2b als Fehler mit Hinweis auf die neuen Werte
+-- order_keywords: nur in Produktdateien, einfache Liste, doppelte und zu kurze Schlüsselwörter warnen
+-- Warnung, wenn ein Dokument zu keiner Kundengruppe passt (nutzt dieselbe Regel wie die Auswahl)

Terminal-Befehl knowledge:select (neu)
+-- Kundengruppe als Pflichtangabe, Produkte optional
+-- gibt ausgewählte und nicht ausgewählte Dokumente, Umfang und Warnungen aus
```

### B) Daten (ohne Datenbank)
- **Kundengruppe:** Schlüssel (z. B. `private-shop`, `private-amazon`, `reseller`, `looxis-pro`, `unclear`), Bezeichnung, Kundenart, Kanal. „Noch unklar" hat weder Kundenart noch Kanal.
- **Produkt-Slug:** Bei einer einzelnen Produktdatei ist es der Dateiname (wie bisher geprüft). Bei einem aufgeteilten Produkt ist es der Name des Unterordners; alle Dateien darin gehören zu diesem Produkt. Wählbar ist ein Produkt, sobald es mindestens eine verwendbare Datei hat. Die Bezeichnung in der Liste ist der Titel der Datei mit dem Slug als Namen, sonst der Titel der Datei mit der kleinsten ID.
- **order_keywords:** neue Liste im Frontmatter von Produktdateien (Artikelnummern, Bezeichnungen). Der Vergleich ignoriert Groß- und Kleinschreibung und sucht das Schlüsselwort als Teil von Artikelnummer oder Bezeichnung.
- **Umfang:** gezählt werden die Zeichen des Dokumenttexts ohne Frontmatter. Das ist der Teil, der in PROJ-9 im Prompt landet, und lässt sich ohne Modellwissen bestimmen.

### C) Auswahlregel in einem Satz
Ein verwendbares Dokument wird ausgewählt, wenn (1) seine Kundenart leer ist oder die Kundenart der Gruppe enthält, (2) sein Kanal leer ist oder den Kanal der Gruppe enthält, und (3) seine Produkte leer sind oder eines der gewählten Produkte enthalten (Produktdateien zählen als zu ihrem eigenen Produkt gehörig). Bei „Noch unklar" gelten (1) und (2) nur bei leeren Feldern. Danach werden die Beispiele begrenzt und erst bei Überschreitung des Umfangs ganz weggelassen. Sortiert wird nach der Typ-Reihenfolge der Konfiguration, innerhalb des Typs nach ID. Jeder Ausschluss bekommt den ersten Grund, der zutrifft (Kundenart, Kanal, Produkt, Obergrenze), damit die Begründung eindeutig ist.

### D) Technische Entscheidungen (für Nicht-Entwickler)
- **Eigener Baustein statt Logik in der Bibliothek:** Die Bibliothek bleibt fürs Einlesen und Prüfen zuständig, die Auswahl ist eine eigene Aufgabe. PROJ-9 (Analyse), PROJ-13 (Evaluation) und der Terminal-Befehl nutzen denselben Baustein, deshalb wählen alle gleich aus.
- **Kundengruppen und Obergrenzen in der Konfiguration:** So verlangt es die Spec. Die Werte lassen sich ändern, ohne Code an mehreren Stellen anzupassen.
- **Eine Regel für Auswahl und Prüfung:** Die Warnung „passt zu keiner Kundengruppe" fragt dieselbe Kundengruppen-Logik wie die Auswahl. Die beiden können deshalb nie auseinanderlaufen.
- **Ergebnis als festes Objekt statt loser Liste:** PROJ-10 und PROJ-11 brauchen später genau diese Angaben (Gründe, Fingerabdrücke, Wissensstand, Entwürfe). Ein festes Ergebnis-Objekt sorgt dafür, dass nichts davon verloren geht.
- **Obergrenze 60.000 Zeichen:** Das sind etwa 15.000 Tokens und passt mit viel Reserve zu aktuellen OpenAI-Modellen. Heute umfasst die ganze Knowledge Base deutlich weniger. Die Grenze schützt vor Ausreißern und ist in der Konfiguration änderbar, sobald PROJ-9 das Modell festlegt.
- **Kein Zwischenspeicher:** Das Einlesen ist pro Anfrage schon zwischengespeichert, die Auswahl selbst ist bei 200 Dokumenten reine Listenarbeit und bleibt weit unter 100 ms.
- **Umstellung der bestehenden Dokumente gehört dazu:** `policy-008` nutzt heute `b2b` und `shop` und würde mit den neuen Wertelisten fehlschlagen. Sie wird auf `b2b-reseller` und `fachhaendler` umgestellt, Guide und Produkt-Vorlage werden ergänzt.

### E) Abhängigkeiten
Keine neuen Pakete.

### F) Hinweise für /backend
- Es gibt keine Oberfläche; `/frontend` entfällt für PROJ-4.
- Tests: Unit-Tests für Auswahlregel, Reihenfolge, Obergrenzen und Vorschläge mit Test-Knowledge-Ordnern (wie bei PROJ-3); Feature-Tests für den Terminal-Befehl.


## Implementation Notes (Backend)
**Gebaut am 2026-10-05.** Keine Oberfläche, keine Datenbank; `/frontend` entfällt.

- **Konfiguration** (`config/knowledge.php`): neue Wertelisten, `retired_values` (Hinweis bei `b2b`), `customer_groups` (Schlüssel `private-shop`, `private-amazon`, `reseller`, `looxis-pro`, `unclear`), `order_channels` (EOCS-Bezeichnung → Kanal, ohne Beachtung der Groß- und Kleinschreibung), `selection` (3/2 Beispiele, 60.000 Zeichen), `min_order_keyword_length` (3).
- **Bausteine** in `app/Knowledge/`: `CustomerGroup` (einzige Stelle der Kundengruppen-Regel), `CaseContext`, `KnowledgeSelector` (Wertelisten und Auswahl), `KnowledgeSelection` und `KnowledgeSelectionEntry` (Ergebnis), `KnowledgeSuggester` (Vorschläge aus Bestellpositionen und Kanal). `KnowledgeDocument` kennt jetzt `productSlug()`, `boundProducts()`, `orderKeywords()`, `isDraft()`, `length()`.
- **Prüfung** (`KnowledgeValidator`): Hinweis bei `b2b`; `order_keywords` nur in Produktdateien bekannt; Warnungen bei Schlüsselwörtern unter 3 Zeichen, bei gleichem Schlüsselwort für verschiedene Produkte (nicht innerhalb eines aufgeteilten Produkts) und bei Geltungsbereichen ohne passende Kundengruppe. Die bestehende Warnung „keine Produktdatei" erkennt jetzt auch Unterordner-Produkte am Ordnernamen.
- **Befehl** `knowledge:select {gruppe} {--product=*}`: Tabellen für ausgewählte und nicht ausgewählte Dokumente, Umfang, Warnungen. Unbekannte Gruppe oder unbekanntes Produkt → Liste der erlaubten Werte, Fehlschlag. Erlaubt sind nur Produkte mit verwendbarer Produktdatei.
- **Präzisierungen gegenüber der Spec:**
  - Ausschlussgründe lauten „nur für Kundenart …", „nur für Kanal …", „nur für Produkt …" (kein Produkt gewählt) bzw. „anderes Produkt: …", „Obergrenze für gute/schlechte Beispiele erreicht (n)" und „weggelassen, weil der Gesamtumfang die Obergrenze überschreitet".
  - Bei Überschreitung des Umfangs werden Referenzfälle einzeln vom Ende der Rangfolge her weggelassen (schlechte vor guten), bis der Umfang passt – nicht alle auf einmal.
  - Unbekannte Produkte (keine Produktdatei) werden für die Auswahl ignoriert; Produkte, deren Produktdateien alle unbrauchbar sind, gelten weiter für andere Dokumente und erscheinen als „ohne Produktwissen".
  - Die Meldung „Knowledge-Ordner fehlt" aus PROJ-3 erscheint als Warnung im Auswahlergebnis.
- **Knowledge-Inhalte:** `policy-008` auf `b2b-reseller` + `fachhaendler` umgestellt (Frontmatter und Geltungssatz). Guide (Wertelisten, Kundengruppen-Tabelle, `order_keywords`, Vorschau-Befehl) und Produkt-Vorlage ergänzt.
- **Tests:** `tests/Feature/PROJ-4-KnowledgeSelectionTest.php` (44 Tests); PROJ-3-Test an die neue Werteliste angepasst. Gesamte Suite: 367 Tests grün.

## QA Test Results

**Tested:** 2026-10-05
**App URL:** keine Oberfläche; geprüft über Pest, `knowledge:select`, `knowledge:check` und die echte Knowledge Base (27 Dokumente, Stand `d2a59c3` + Zaubertassen-Dateien)
**Tester:** QA Engineer (AI)

Browser- und Responsive-Tests entfallen: PROJ-4 hat keine Seite und keine Route.

### Acceptance Criteria Status

#### Wertelisten
- [x] Neue Kundenarten gültig; `b2b` ist ein Fehler mit Hinweis auf `b2b-reseller` / `b2b-pro`
- [x] Vier Kanäle gültig
- [x] Fünf Kundengruppen in Tabellenreihenfolge mit deutscher Bezeichnung
- [x] Produktliste alphabetisch nach Titel, ohne Beachtung der Groß-/Kleinschreibung, Umlaute beim Grundbuchstaben (BUG-1, behoben)

#### Auswahl nach Kundengruppe
- [x] Leere Felder gelten für jede Gruppe
- [x] Privatkunde Shop, Privatkunde Amazon, Fachhändler, LOOXIS-Pro jeweils korrekt
- [x] „Noch unklar" nur mit beiden Feldern leer (echte KB: 13 von 27 Dokumenten)

#### Auswahl nach Produkt
- [x] Leere `products` gelten immer
- [x] Gewähltes Produkt bringt Produktdatei und gebundene Dokumente
- [x] Kein Produkt → keine Produktdatei, keine produktgebundenen Dokumente (Begründungstext siehe BUG-2)
- [x] Produkt mit unbrauchbarer Produktdatei → „ohne Produktwissen", Auswahl läuft weiter

#### Welche Dokumente grundsätzlich in Frage kommen
- [x] Fehlerhafte und `deprecated` Dokumente nie verwendet
- [x] `draft` verwendet und gekennzeichnet
- [x] `categories`/`topics` ohne Einfluss
- [x] Beispiele auf 3 gute / 2 schlechte begrenzt, Produktbezug zuerst, dann ID

#### Ergebnis der Auswahl
- [x] Rangfolge der Knowledge Base, innerhalb des Typs nach ID
- [x] Grund je ausgewähltem Dokument
- [x] Grund je nicht ausgewähltem Dokument
- [x] Fallkontext, Wissensstand, Fingerabdrücke, Anzahl Entwürfe, Gesamtumfang
- [x] Wiederholbar: gleiche Eingabe → gleiche Dokumente und Reihenfolge (auch bei doppelter oder umsortierter Produktangabe)

#### Umfang
- [x] Über der Grenze fallen zuerst Referenzfälle weg, Warnung mit Umfang und Grenze
- [x] Verbindliches Wissen wird nie gekürzt, Warnung bleibt

#### Vorschläge aus der Bestellung
- [x] Produktvorschlag über `order_keywords`, ohne Beachtung der Groß-/Kleinschreibung, auch mehrere Produkte
- [x] Keine Treffer oder keine Bestellung → leer, kein Fehler
- [x] Kundengruppe aus dem Kanal; unbekannt oder leer → „Noch unklar"
- [x] Kanal-Zuordnung in der Konfiguration (`order_channels`), z. B. `fachhaendler.looxis.de`
- [x] Die Wahl des Mitarbeiters gilt allein (die Auswahl nimmt nur den Fallkontext entgegen, Vorschläge fließen nicht ein)

#### Prüfung der Knowledge-Dateien
- [x] `order_keywords` nur in Produktdateien bekannt, muss einfache Liste sein
- [x] Doppeltes Schlüsselwort bei zwei Produkten → Warnung an beiden Dateien
- [x] Unerreichbarer Geltungsbereich → Warnung (auch `b2c` + `fachhaendler`)

#### Vorschau-Befehl
- [x] Listet ausgewählte und nicht ausgewählte Dokumente mit ID, Titel, Status, Grund, dazu Umfang und Warnungen
- [x] Unbekannte Kundengruppe oder unbekanntes Produkt → erlaubte Werte, Exit-Code 1

#### Guide und Vorlagen
- [x] Guide beschreibt neue Kundenarten, Kanäle, Kundengruppen und `order_keywords` inkl. Interviewfrage
- [x] Produkt-Vorlage enthält `order_keywords`

### Edge Cases Status
- [x] Keine passenden Dokumente → leere Liste, Warnung „Kein Wissen für diesen Fall."
- [x] Knowledge-Ordner fehlt → leeres Ergebnis mit der Meldung aus PROJ-3
- [x] Unbekanntes Produkt → ignoriert, als unbekannt genannt
- [x] Dokument für mehrere Kundenarten/Produkte → einmal ausgewählt
- [x] `b2c` mit leerem Kanal → Shop und Amazon
- [x] Kanal `shop` mit leerer Kundenart → nur Privatkunde Shop
- [x] `b2b-reseller` + `b2b-pro` → beide B2B-Gruppen
- [x] Produkt im Unterordner → alle Dateien geladen, Produkt einmal in der Liste
- [x] Schlüsselwort unter 3 Zeichen → Warnung
- [x] Bestellung mit mehreren Produkten → alle vorgeschlagen
- [x] Wissensstand ändert sich → Ergebnis trägt Fingerabdrücke und Commit
- [x] Sehr viele Referenzfälle → Obergrenze, Rest bei den nicht ausgewählten

**Zusätzlich geprüft:**
- [x] Geschwindigkeit: Auswahl aus 200 Dokumenten in ca. 16 ms (Anforderung < 100 ms)
- [x] `order_keywords` als einzelner Text statt Liste, mit leeren Einträgen oder als Zahl → robust verarbeitet
- [x] Artikelnummer als Zahl in der Bestellposition → wird erkannt
- [x] Deprecated Produktdatei → kein Vorschlag
- [ ] BUG-3: Produktdatei mit eingeschränktem Geltungsbereich (z. B. nur `amazon`) bei Shop-Fall → Produktdatei fehlt ohne Hinweis „ohne Produktwissen"
- [x] Befehl schreibt „1 Dokument"; `--product=` ohne Wert wird ignoriert (BUG-4, behoben)

### Security Audit Results
- [x] Keine neue Route, kein Controller, keine Eingabe aus dem Web; `route:list` unverändert (4 Routen)
- [x] Keine Datenbank, keine Schreibzugriffe; die Auswahl ändert keine Datei
- [x] Eingaben des Befehls werden nur gegen feste Listen verglichen, nie als Pfad verwendet
- [x] Keine Geheimnisse in Konfiguration oder Ausgabe
- Hinweis für PROJ-10: Gründe und Produkt-Slugs stammen aus Frontmatter und müssen in Blade escaped ausgegeben werden (Standard `{{ }}`)
- Hinweis für PROJ-7: Bestellpositionen als Texte übergeben; Arrays in `article_number`/`description` würden einen Fehler auslösen

### Inhaltliche Hinweise (keine Bugs)
- Numerische Schlüsselwörter wie `11282` passen als Teilstring auch auf `112820`. So in der Spec gewollt („kommt vor"); bei kurzen Nummern ggf. eindeutiger formulieren.
- POLICY-008 verweist auf PROCESS-001, das nur für `shop` gilt; Fachhändler-Fälle erhalten den Prozess nicht. Ebenso gelten TONE-001, PLAYBOOK-002 und GLOSSARY-002 nur für `shop`.
- Umfang mit einem Produkt bereits 40.750 von 60.000 Zeichen (Privatkunde Amazon + Zaubertasse); Grenze mit PROJ-9 neu bewerten.

### Bugs Found

#### BUG-1: Produktliste nicht alphabetisch im deutschen Sinn – BEHOBEN (2026-10-05)
- **Severity:** Low
- **Fix:** Sortierschlüssel in Kleinbuchstaben, ä/ö/ü/ß wie a/o/u/ss; ohne `intl`-Erweiterung. Test mit „acrylglas, Bild, Ofenkachel, Ölbild, Zaubertasse".
- **Steps to Reproduce:**
  1. Produktdateien mit Titeln „Zaubertasse", „Ölbild", „acrylglas", „Bild"
  2. `KnowledgeSelector::products()` abfragen
  3. Expected: acrylglas, Bild, Ölbild, Zaubertasse
  4. Actual: Bild, Zaubertasse, acrylglas, Ölbild
- **Priority:** Fix before deployment (klein, betrifft das Formular in PROJ-9)

#### BUG-2: Irreführender Grund „anderes Produkt", wenn kein Produkt gewählt ist – BEHOBEN (2026-10-05)
- **Severity:** Low
- **Fix:** Ohne gewähltes Produkt lautet der Grund „nur für Produkt …"; mit gewähltem, aber anderem Produkt weiter „anderes Produkt: …".
- **Steps to Reproduce:**
  1. `knowledge:select private-shop` (ohne `--product`)
  2. Expected: z. B. „nur für Produkt magic-mug"
  3. Actual: „anderes Produkt: magic-mug", obwohl kein Produkt gewählt war
- **Priority:** Fix before deployment

#### BUG-3: Kein Hinweis, wenn die Produktdatei für die Kundengruppe nicht gilt
- **Severity:** Low
- **Steps to Reproduce:**
  1. Produktdatei mit `sales_channels: [amazon]`
  2. Auswahl für „Privatkunde, eigener Shop" mit diesem Produkt
  3. Expected: Produkt wird als „ohne Produktwissen" genannt
  4. Actual: Produktdatei steht nur bei den nicht ausgewählten; keine Warnung zum Produkt
- **Priority:** Nice to have (Produktdateien sind in der Regel nicht eingeschränkt)

#### BUG-4: Kleinigkeiten in der Befehlsausgabe – BEHOBEN (2026-10-05)
- **Severity:** Low
- **Fix:** Einzahl „1 Dokument"; leere `--product=`-Werte werden ignoriert.
- **Steps to Reproduce:**
  1. Auswahl mit genau einem (nicht) ausgewählten Dokument → „1 Dokumente"
  2. `knowledge:select private-shop --product=` → „Unbekanntes Produkt: ."
- **Priority:** Nice to have

### Summary
- **Acceptance Criteria:** 36/37 passed (1 Low-Bug)
- **Bugs Found:** 4 total (0 critical, 0 high, 0 medium, 4 low); BUG-1, BUG-2 und BUG-4 am 2026-10-05 behoben, BUG-3 bewusst offen
- **Security:** Pass
- **Production Ready:** YES
- **Recommendation:** BUG-1 und BUG-2 vor PROJ-9 beheben (je wenige Zeilen); BUG-3 und BUG-4 optional

## Deployment
_To be added by /deploy_
