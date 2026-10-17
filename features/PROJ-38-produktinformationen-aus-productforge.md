# PROJ-38: Produktinformationen aus ProductForge

## Status: In Progress
**Created:** 2026-10-12
**Last Updated:** 2026-10-16

## Dependencies
- Requires: PROJ-7 (Bestellung aus EOCS), PROJ-36 (Gezielter Wissensabruf), PROJ-40 (Frage stellen), PROJ-42 (Formular: Pflichtangaben), PROJ-45 (Produkt ohne Wissen melden), PROJ-39 (Fortsetzung).
- Vertrag: [docs/productforge-product-api.md](../docs/productforge-product-api.md) (Kopie der ProductForge-Produktinformations-API v1, maßgeblich).

## Ausgangslage
PROJ-38 war als breiter „Dynamischer Abruf von Produktinformationen aus EOCS und später dem Content Studio“ geplant. Der Umfang wurde bewusst auf diesen Umsetzungsschritt verengt: **ProductForge (LOOXIS Content Studio) ist die Quelle der Produktinhalte**; EOCS liefert weiterhin Bestellungen und Positionen. Es gibt keine zusätzliche EOCS-Produktschnittstelle.

ProductForge verwaltet konkrete Produktdaten, produktspezifisches Kundenservice-Wissen (auch häufige Beanstandungen), die Master-/Varianten-Vererbung und gemeinsame Wissensursprünge und liefert sie **bereits aufgelöst**. Die Markdown-Wissensbasis bleibt zuständig für allgemeine Regeln, Befugnisse, Kulanz, kanalabhängige Entscheidungen und interne Abläufe. Diese App entscheidet, welche Produktinhalte für den Fall in den Prompt gehören; sie vererbt und kombiniert nichts erneut.

## User Stories
- Als Mitarbeiterin möchte ich, dass die App die Produktdaten (Technik, Lieferumfang, Pflege, Warnhinweise, Beschreibung, Servicewissen) kennt, damit Vorschläge zu Eigenschaften nicht erfunden sind.
- Als Aushilfe möchte ich das Produkt über eine Suche auswählen können, auch ohne Bestellung, auch als Master ohne Größenvariante.
- Als Mitarbeiterin möchte ich nicht zweimal dieselbe Produktfrage beantworten müssen.
- Als Admin möchte ich nachvollziehen, welche Produktdaten (Stand, Quelle) in einem Vorschlag verwendet wurden.

## Out of Scope
- Neue Einstellungsoberfläche für die Anbindung; KI-Suche, Embeddings oder lokaler Katalogspiegel.
- Pflege von Triggern oder Klassifizierungen in ProductForge; anliegensabhängige Relevanzprüfung der Produktfelder (Auswahl erfolgt nach Feldpriorität und Budget).
- SKU-Zuordnungstabelle zu Knowledge-Produkten; EOCS-Items-API; Preise, GTIN, Fotos (nicht Teil der API).
- Historischer Stand zum Bestelldatum (die API liefert nur den aktuellen Pflegestand).

## Entscheidungen
| Entscheidung | Begründung | Datum |
|---|---|---|
| ProductForge statt EOCS/Content Studio als Produktquelle | Inhalte, Vererbung und Servicewissen liegen dort; kein zweiter Weg | 2026-10-16 |
| Produktdaten sind ein eigener Prompt-Abschnitt, kein Markdown-Dokument | Regeln bleiben im Wissensabruf; Produkttexte sind Fachquellen, keine Anweisungen | 2026-10-16 |
| Pflichtwissen hat Budgetvorrang; Produktfelder ganz oder gar nicht | Bedingungen/Ausnahmen am Textende dürfen nicht abgeschnitten werden | 2026-10-16 |
| Kanal: Shop-Kanäle → Shop-Text, `amazon` → Amazon-Text, unklar → Verkaufstexte weglassen mit Hinweis | Kein stiller Kanalwechsel | 2026-10-16 |
| Wissenslücke „Produktwissen“ entfällt nur bei ProductForge-Kundenservice-Wissen | Datenblattfelder allein ersetzen kein Servicewissen | 2026-10-16 |
| Mehrere Bestellpositionen: „betroffen“-Auswahl | Keine Vermischung von Produkten, keine willkürliche Auswahl | 2026-10-16 |
| Zuordnung Bestellposition → SKU über `item.item_id`, abschaltbar (`PRODUCTFORGE_ORDER_LOOKUP`, Standard an) | ProductForge übernimmt die SKUs aus EOCS (Aussage des Produktverantwortlichen, 2026-10-16); noch nicht alle Produkte sind in ProductForge gepflegt, diese bleiben „unbekannt“ | 2026-10-16 |
| Zwei Speicherteile: abgerufenes Datenpaket und gesendeter Anteil | Fortsetzungen können weitere Felder nutzen, ohne neu abzurufen | 2026-10-16 |
| Feldauswahl nach Priorität und Budget, kein zusätzlicher KI-Aufruf | Überschaubare erste Ausbaustufe | 2026-10-16 |

## Acceptance Criteria
- [x] AC-1 Angenommen die Konfiguration fehlt oder der Token ist ungültig, wenn eine Analyse läuft, dann läuft sie mit der Markdown-Wissensbasis weiter und das Ergebnis zeigt „Produktquelle nicht verfügbar“; der Token erscheint nirgends (Browser, Prompt, Protokoll, Fehlermeldung, Log).
- [x] AC-2 Angenommen eine SKU wird abgerufen, wenn sie gesendet wird, dann unverändert (keine Normalisierung, führende Nullen bleiben); 404 bleibt „Produkt unbekannt“ ohne Ersatztreffer; historische SKUs und inaktive Produkte werden unterstützt und gekennzeichnet.
- [x] AC-3 Angenommen Bestellpositionen (bei aktivem Schalter), wenn dieselbe SKU mehrfach vorkommt, dann wird sie je Vorgang einmal abgerufen; Inhalte bleiben der jeweiligen Position zugeordnet.
- [ ] AC-4 (Server getestet, Rückfrage „Sicher?“ und Vorauswahl im Browser noch nicht geprüft) Angenommen mehrere Positionen mit Treffern, wenn der Mitarbeiter absendet, dann senden nur als „betroffen“ markierte Produkte Inhalte (bei genau einem vorgewählt); ohne Auswahl erscheint die Rückfrage.
- [x] AC-5 Angenommen keine Bestellposition passt, wenn der Mitarbeiter sucht, dann sieht er Kandidaten (Name, SKU, Typ, Master), wählt einen (auch Master) und die Details werden über die ID geladen; Mehrdeutiges wird nie automatisch gewählt.
- [ ] AC-6 (Serverseite getestet, Alpine-Oberfläche im Browser noch nicht geprüft) Angenommen ein Produkt wurde gewählt, wenn das Formular erscheint, dann gibt es eine Produktwahl und einen vorgeschlagenen, vorausgefüllten „Wissensbereich“; ohne passenden Bereich steht „Kein passender Markdown-Wissensbereich“ und getrennt, was ProductForge hat.
- [x] AC-7 Angenommen ein Produkt ist gewählt, wenn Regeln abgerufen werden, dann gelten die bisherigen Pflichtregeln für Kundengruppe und Wissensbereich unverändert (nie abhängig von SKU oder ID).
- [x] AC-8 Angenommen Beschreibung und Bullet Points, wenn der Kanal Shop oder Amazon ist, dann wird nur der Kanaltext gesendet, ein fehlender Text bleibt fehlend; bei unklarem Kanal werden beide weggelassen und im Ergebnis vermerkt.
- [x] AC-9 Angenommen die Felder Technische Daten, Lieferumfang, Pflege, Warnhinweise, Beschreibung, Bullet Points, Kundenservice-Wissen, wenn sie gesendet werden, dann in der wirksamen (`combined`/Kanal-)Form ohne erneute Vererbung; leere Werte entfallen; gleiche Wissensursprünge mehrerer Produkte stehen einmal.
- [x] AC-10 Angenommen das Budget ist knapp, wenn Produktfelder nicht passen, dann gehen sie ganz oder gar nicht (nie gekürzt) und zuletzt weg; Pflichtwissen wird nie verdrängt; ist schon der Pflichtumfang zu groß, greift `KnowledgeTooLarge`; Auslassungen sind sichtbar.
- [x] AC-11 Angenommen eine Analyse wird gespeichert, dann enthält sie getrennt das abgerufene Datenpaket (je Produkt Quelle, Abrufzeit, `data_version`) und den gesendeten Anteil mit Auslassungen und Abrufproblemen.
- [x] AC-12 Angenommen eine Fortsetzung, wenn das Anliegen weiter Produktfelder braucht, dann wählt die Analyse aus dem gespeicherten Paket neu aus, ohne Abruf; ein hinzukommendes Produkt wird einzeln ergänzt; bestehende Pakete werden nie still aktualisiert.
- [x] AC-13 Angenommen „Erneut analysieren“, dann wird frisch abgerufen und eine geänderte `data_version` gekennzeichnet.
- [x] AC-14 Angenommen ein Vorschlag nennt ein Produkt ohne Markdown-Wissen, wenn ProductForge Kundenservice-Wissen liefert, dann entsteht keine automatische Wissenslücke; liefert es nur Datenblattfelder oder schlägt der Abruf fehl, dann bleibt die Lücke (mit Vermerk).
- [x] AC-15 Angenommen die Quellen werden angezeigt, dann erscheinen ProductForge-Produkte verständlich („Produktinformation: Name (SKU)“, abgerufen am …); technische Felder nur in den Details.
- [x] AC-16 Fehler 401, 404, 429, 5xx, Timeout und unerwartete Struktur werden unterschieden; Wiederholungen sind begrenzt; veraltete Daten werden nicht heimlich verwendet.
- [x] AC-18 Angenommen der Kunde nennt im Ticket (Titel oder Nachricht) eine Artikelnummer hinter einem Stichwort („SKU“, „Artikelnummer“, „Art.-Nr.“, „Artnr“, „Produktnummer“), wenn die Ticketseite erscheint, dann wird das Produkt per SKU gesucht und im Feld „Produkt“ vorgeschlagen (bei genau einem Treffer vorgewählt); ohne Stichwort, mit anderer Länge als fünf Ziffern oder unbekannter Nummer entsteht kein Vorschlag, eine unbekannte Nummer wird genannt.
- [x] AC-17 Produkttexte gelten als Fachquelle: Der Prompt verbietet Anweisungsbefolgung, automatische Ablehnung ohne belegte Voraussetzungen und Quellenwechsel ohne Hinweis auf Widersprüche.

## Edge Cases
- Produkt ohne Kundenservice-Wissen: alle Texte `null`, übrige Daten nutzbar.
- Variante ohne Master; inaktives Produkt; Master aufgelöst (Servicewissen folgt dem Nachfolger, ältere Inhalte bleiben): keine Ausgabe als Beleg für die damalige Verkaufsbeschreibung.
- Suche ohne Treffer / unter 2 Zeichen / zu viele Treffer (Seiten).
- Spiegelung `mirrors_amazon`: nur wie von der API geliefert.
- Testmodus (PROJ-32): aktueller Pflegestand, mit Hinweis.

## Technical Decisions
- `app/ProductForge/` mit Client, DTOs, Problem-Enum und Exception nach dem Muster von `app/Eocs/`; Konfiguration `services.productforge` (`url`, `token`, `timeout`, `retries`, `order_lookup`).
- Platzhalter: `PRODUCTFORGE_URL`, `PRODUCTFORGE_TOKEN`, `PRODUCTFORGE_TIMEOUT`, `PRODUCTFORGE_ORDER_LOOKUP`. Echte Werte trägt der Betreiber selbst ein.
- Offen vor Freigabe der automatischen Bestellzuordnung: Vergleich von 3–5 realen EOCS-Positionen (`item.item_id`) mit den SKUs in ProductForge; Smoke-Test aus der Sail-Laufzeit.

## Implementation Notes
- **Neu:** `app/ProductForge/` (Client, DTOs `ForgeProduct`/`ForgeSearchHit`/`ForgeSearchPage`, `ProductForgeProblem`/`-Exception`, `ProductPackage`, `ProductResolver`, `ProductPromptBuilder`/`-State`/`ProductPrompt`, `KnowledgeAreaSuggester`), `ProductSearchController` + `ProductSearchRequest` (Route `products.search`, hinter `staff.selected`), Prompt `resources/prompts/analysis-products.md` (`products-2026-10-17.1`, wird nur angehängt, wenn Produktdaten vorliegen), Vertragsreferenz `docs/productforge-product-api.md`.
- **Geändert:** `config/services.php` (Block `productforge`), `config/analysis.php` (`product_info`, Prompt-Pfad), `CaseAnalyzer` (Budget: Retrieval zuerst, Produktfelder im Rest nach Pflichtumfang und Reserve, danach Retrieval im Restraum; Safeguard baut zuerst den Produktanteil ab), `AnalysisRequest`, `AnalysisPanel` (Katalogkandidaten aus Bestellpositionen), beide Controller, `ChecksProductChoice`/Requests (`forge_produkte[]`, `forge_sku[id]`), `scope-fields` (Blade + Alpine), Ergebnisansicht (Abschnitt „Produktinformationen“).
- **Gespeichert** (`content` der Analyse): `product_data` (vollständiges Datenpaket je Produkt mit Abrufzeit, `data_version`, `resolved_via`) getrennt von `product_sent` (gesendete Felder, Auslassungen mit Grund, Abrufprobleme); `meta.product_info_status` (`ok`/`partial`/`failed`/`none`). Keine Schemaänderung.
- **Fortsetzung:** übernimmt `product_data` ohne Abruf, wählt die Felder mit neuem Anliegen/Budget neu; neue Produkte werden einzeln ergänzt; „Erneut analysieren“ ruft frisch ab und meldet geänderte `data_version`.
- **Artikelnummer im Kundentext:** `SkuDetector` findet fünfstellige Nummern nur hinter einem Stichwort (max. 3); `AnalysisPanel::forgeChoice()` schlägt das Produkt wie eine Bestellposition vor (unabhängig vom Schalter für Bestellpositionen). Für „Frage stellen“ gibt es das nicht (dort tippt der Mitarbeiter selbst; Suche nach der Nummer funktioniert). Test: `PROJ-38-SkuInTicketTextTest` (20).
- **Konfiguration (Platzhalter, echte Werte trägt der Betreiber ein):** `PRODUCTFORGE_URL` (Host ohne `/api/v1`; im Sail-Container lokal `http://host.docker.internal`, nicht `localhost`), `PRODUCTFORGE_TOKEN`, `PRODUCTFORGE_TIMEOUT` (Standard 10), `PRODUCTFORGE_RETRIES` (Standard 2), `PRODUCTFORGE_ORDER_LOOKUP` (Standard aus), optional `ANALYSIS_PRODUCT_INFO_CHARACTERS` (20000) und `ANALYSIS_PRODUCT_INFO_RESERVE` (8000). `.env` und `.env.example` wurden bewusst nicht angefasst.
- **Abweichungen vom ersten Entwurf:** `product_info_status` steht nur im Protokoll (`meta`), nicht als Spalte der Analysen-Übersicht; der Testmodus-Hinweis steckt im allgemeinen Satz „aktueller Pflegestand“ im Prompt.
- **Tests:** `PROJ-38-ProductForgeClientTest` (23), `PROJ-38-ProductInfoAnalysisTest` (18), `PROJ-38-ProductSearchFormTest` (12); Fixtures `tests/Fixtures/productforge/` nach den Vertragsbeispielen A–D. Nicht durch einen Test abgedeckt: das Verkleinern des Produktanteils beim Safeguard-Nachladen und das Verhalten der Alpine-Oberfläche im Browser.
- **Bestellzuordnung:** ProductForge führt dieselben SKUs wie EOCS; deshalb ist `PRODUCTFORGE_ORDER_LOOKUP` standardmäßig an. Produkte, die in ProductForge noch fehlen, erscheinen als „nicht verfügbar“. Ein Stichproben-Abgleich mit echten Bestellungen gehört in den Smoke-Test. Offen: echter Smoke-Test gegen ProductForge aus der Sail-Laufzeit. `PRODUCTFORGE_TIMEOUT` ist in Sekunden (Obergrenze 30).
- Der Test `PROJ-36-AnalysisIntegrationTest` erwartete noch Prompt-Version `.1`; auf `.2` angepasst (Prompt-Datei steht seit Commit „updated prompt“ auf `.2`).
