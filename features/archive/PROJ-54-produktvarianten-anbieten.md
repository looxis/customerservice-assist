# PROJ-54: Produktvarianten anbieten (EOCS)

## Status: Approved
**Created:** 2026-10-19
**Last Updated:** 2026-10-19

## Dependencies
- Requires: PROJ-38 (Produktinformationen aus ProductForge: Produktwahl, Snapshot), PROJ-42 (Formular, Produktsuche und Chips), PROJ-7 (EOCS-Anbindung).
- Verwandt: PROJ-46 und PROJ-47 (Aufgabenteilung Produktfakten und Regeln), PROJ-34 (Angebotskalkulation, liefert später Preise), PROJ-9 (Prompt, Quellen).
- Vertrag: [docs/items-api.md](../../docs/items-api.md), Abschnitt „Master-Produkte und Varianten“ (am 2026-10-18 gegen die echte API geprüft).

## Ausgangslage
Fragt ein Kunde, welche Größen oder Varianten es gibt („Essbares Foto, Rund“ in 16, 18 und 20 cm), kennt die App heute nur das eine gewählte Produkt und kann die Alternativen nicht nennen. EOCS liefert die Geschwister seit Kurzem über die Includes `variants` und `master_products.variants` (ein Master hat keine eigene SKU). Die ProductForge-API liefert an einer Variante nur ihren Master, nicht die Geschwister, und ist deshalb in dieser Stufe nicht die Quelle der Variantenliste.

Der zweite Anlass, die Wahl der Variante bei allgemeinen Anfragen („Essbare Fotos“ ohne Größe), ist durch die Produktsuche weitgehend abgedeckt: ProductForge-Treffer enthalten Master und Varianten. Das wird hier als Randfall geprüft, nicht als eigene Funktion gebaut.

## User Stories
- Als Mitarbeiterin möchte ich, dass die App die weiteren Varianten des Produkts kennt, damit der Antwortentwurf auf die Frage „Gibt es das auch in anderen Größen?“ die tatsächlich vorhandenen Varianten nennt und nichts erfindet.
- Als Aushilfe möchte ich nach der Produktwahl sofort sehen, welche weiteren Varianten es gibt, damit ich einem Kunden Alternativen anbieten kann, ohne in EOCS nachzusehen.
- Als Mitarbeiterin möchte ich bei Angebots- und Auftragsanfragen ohne Größenangabe nicht versehentlich die erstbeste Variante nehmen, sondern erkennen, dass es mehrere gibt.
- Als Admin möchte ich im Protokoll nachvollziehen, welche Variantenliste (Quelle, Abrufzeit) ein Vorschlag verwendet hat.

## Out of Scope
- Variantenliste aus ProductForge / dem LOOXIS Content Studio (die API liefert heute nur Variante → Master; eine gemeinsame Sicht über beide Quellen ist ein späterer Schritt, die Schnittstelle der App soll die Quelle austauschbar halten).
- Preise, Staffeln, Rabatte der Varianten (PROJ-34 Angebotskalkulation).
- Lagerbestand, Lieferzeit, Liefer- oder Verfügbarkeitszusagen; „verfügbar“ heißt hier nur „aktiv und veröffentlicht“ (der Filter der EOCS-API).
- Eigene Auswahl einer „vom Kunden gewünschten Variante“ im Formular; die betroffene Variante bestimmen Bestellposition (SKU) und Produktsuche wie bisher.
- Eigene Produktinhalte (Technik, Pflege, Servicewissen) der anderen Varianten; die App holt nur Name und SKU.
- Änderungen an der ProductForge-API oder an Markdown-Wissensdokumenten.
- Ersetzen der Produktsuche durch eine Variantensuche in EOCS.

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Abruf und Anzeige
- [ ] Angenommen ein gewähltes Produkt hat eine SKU, wenn es im Formular gewählt (oder aus Bestellung/Ticket vorgemerkt) ist, dann ruft die App die Varianten aus EOCS ab und zeigt unter dem Chip „Weitere Varianten: 18 cm (SKU 11747), 20 cm (SKU 11748)“.
- [ ] Angenommen das gewählte Produkt ist eine einzelne Variante, wenn die Liste erscheint, dann enthält sie die Geschwister über deren Master (`master_products.variants`), und das gewählte Produkt selbst steht nicht unter „weitere“.
- [ ] Angenommen das gewählte Produkt ist ein Master, wenn die Liste erscheint, dann enthält sie alle sichtbaren Varianten des Masters.
- [ ] Angenommen ein eigenständiges Produkt ohne Varianten, wenn es gewählt wird, dann erscheint keine Zeile, kein Prompt-Abschnitt und kein Hinweis „Varianten nicht geprüft“.
- [ ] Angenommen mehr als 30 Varianten, wenn die Liste erscheint, dann zeigt die Oberfläche 30 und „und N weitere“, und der Prompt vermerkt „Liste gekürzt“.
- [ ] Angenommen der Mitarbeiter entfernt oder wechselt das Produkt, wenn der Chip sich ändert, dann verschwindet die Zeile bzw. wird neu geladen; das Formular lässt sich währenddessen absenden (der Abruf blockiert nichts).

### Prompt und Ergebnis
- [ ] Angenommen eine Variantenliste liegt vor, wenn der Vorschlag erstellt wird (Ticketanalyse und „Frage stellen“), dann enthält der Prompt einen eigenen Abschnitt „Weitere Varianten des Produkts“ mit Name und SKU, das gewählte Produkt ist markiert, und der Abschnitt sagt der KI, dass sie Varianten nur nennt, wenn der Kunde danach fragt, und keine Preise, Lieferzeiten oder Lieferfähigkeit zusagt.
- [ ] Angenommen der Kunde fragt nach Größen, wenn der Antwortentwurf entsteht, dann nennt er ausschließlich Varianten aus der Liste.
- [ ] Angenommen Pflichtwissen und Variantenliste konkurrieren um das Budget, wenn es knapp wird, dann bleibt das Pflichtwissen, die Variantenliste geht vor den Produktfeldern aus ProductForge und wird ganz oder gar nicht gesendet; Auslassungen sind sichtbar.
- [ ] Angenommen ein Vorschlag nutzte eine Variantenliste, wenn die Quellen angezeigt werden, dann steht dort „Varianten aus EOCS“ mit Abrufzeit; die Analyse speichert einen Snapshot der Liste (Produkt, Varianten, Abrufzeit) wie das Datenpaket aus PROJ-38.
- [ ] Angenommen eine Fortsetzung der Analyse (PROJ-39), wenn sie weiterläuft, dann nutzt sie den gespeicherten Snapshot ohne Neuabruf; „Erneut analysieren“ ruft frisch ab.

### Mehrere Produkte und Sonderfälle
- [ ] Angenommen mehrere gewählte Produkte, wenn die Varianten abgerufen werden, dann wird je gewähltem Produkt abgefragt und die Listen werden je Master zusammengeführt; gehören zwei gewählte Produkte zum selben Master, steht die Liste einmal im Prompt.
- [ ] Angenommen das gewählte Produkt ist in EOCS deaktiviert, wenn EOCS es liefert, dann steht es als „nicht mehr aktiv“ in der Liste; die Geschwister sind die aktiven, veröffentlichten Varianten.
- [ ] Angenommen „Kein Produktbezug“ oder „Produkt ohne hinterlegtes Wissen“ (nur Freitext), wenn der Vorschlag entsteht, dann gibt es keinen Variantenabruf.

### Fehler
- [ ] Angenommen das gewählte Produkt hat keine SKU (ein Master in ProductForge) oder EOCS kennt die SKU nicht, wenn der Vorschlag entsteht, dann läuft er ohne Variantenliste weiter, und das Ergebnis vermerkt „Varianten nicht geprüft“.
- [ ] Angenommen EOCS ist nicht erreichbar, der Zugang fehlt oder lässt `items:relations` nicht zu, wenn die Varianten abgerufen werden, dann läuft der Vorschlag ohne Liste weiter, der Fehlergrund steht verständlich im Ergebnis („Varianten nicht geprüft: EOCS nicht erreichbar“), und der Prompt sagt der KI, dass sie über weitere Varianten nichts aussagen darf; Zugangsdaten erscheinen nirgends.
- [ ] Angenommen der Abruf im Formular scheitert, wenn der Chip gewählt ist, dann zeigt das Formular einen kleinen Hinweis „Varianten konnten nicht geladen werden“, nicht blockierend.

## Edge Cases
- Produkt aus dem EOCS-Rückfall der Produktsuche (`origin: eocs`): die SKU ist die EOCS-Artikelnummer, der Abruf läuft wie gewohnt.
- Historische oder geänderte SKU in ProductForge: die aktuelle SKU des Treffers wird verwendet; ist sie in EOCS unbekannt, gilt „Varianten nicht geprüft“.
- SKU ist nicht numerisch: kein Abruf, „Varianten nicht geprüft“ (EOCS-Artikelnummern sind ganze Zahlen).
- Eine Variante gehört zu mehreren Mastern (`master_products[*]`): die Listen werden zusammengeführt, doppelte SKUs entfallen; im Zweifel nennt der Prompt beide Master getrennt.
- Variantenname ohne erkennbare Größe: die Liste zeigt den Namen unverändert; die App deutet nichts hinein.
- Variante mit leerem Namen oder ohne `item_id`: wird übersprungen.
- Langsamer EOCS-Abruf: Zeitlimit wie bei den übrigen EOCS-Aufrufen; kein Wiederholen im Formular, ein Wiederholen beim Erstellen des Vorschlags nur einmal.
- Allgemeine Kundenanfrage („Essbare Fotos“) ohne Größe: die Produktsuche nach „essbar“ liefert Master und Varianten; wählt der Mitarbeiter eine Variante, zeigt die Zeile die anderen. Das wird als Prüfpunkt getestet.
- Kundentext oder Variantenname enthält Anweisungen (Prompt-Einschleusung): Variantennamen werden als einzeilige Daten gesendet (Zeilenumbrüche zusammengefasst), nie als Anweisung.

## Technical Requirements (optional)
- Performance: ein EOCS-Aufruf je Produkt und Seite; Ergebnis bei der Produktwahl nicht mehrfach abrufen (kurzer Zwischenspeicher je SKU genügt).
- Security: Der Browser spricht nicht mit EOCS; die App ruft mit ihrem Zugang ab und gibt nur Name, SKU und Status weiter. Der Zugang braucht zusätzlich zu `items:read` die Ability `items:relations`.
- Quelle austauschbar: Abruf hinter einer Schnittstelle, damit ProductForge später ergänzt werden kann (siehe Out of Scope).
- Browser Support: wie die übrige App (Chips und Nachladen in Alpine).

## Open Questions
- [ ] Hat der EOCS-Zugang der App die Ability `items:relations`? Ohne sie schlägt der Include fehl (vor der Umsetzung klären; sonst muss sie in EOCS freigegeben werden).
- [ ] Wie finden wir den EOCS-Artikel eines ProductForge-Masters ohne SKU? Vorschlag: gar nicht (Hinweis „Varianten nicht geprüft“), solange keine eindeutige Zuordnung existiert; später über eine seiner Varianten oder eine Master-ID aus ProductForge.
- [ ] Soll eine Wissenslücke gemeldet werden, wenn die Variantenfrage nicht beantwortbar war (keine SKU, EOCS-Fehler)? Vorschlag: nein, der Vermerk im Ergebnis genügt; Abgleich mit PROJ-46.
- [ ] Gleicht EOCS die Master-/Variantenstruktur mit dem Content Studio ab, oder können die Zuordnungen abweichen? Relevant für die spätere gemeinsame Sicht.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Varianten gehen in den Prompt, nicht nur in die Oberfläche | Kern des Anliegens: der Antwortentwurf soll die verfügbaren Größen nennen, wenn der Kunde fragt | 2026-10-19 |
| Je Variante nur Name und SKU | Reicht für „gibt es in 16, 18, 20 cm“; Preise gehören zu PROJ-34, Bestand/Lieferzeit würden Zusagen auslösen | 2026-10-19 |
| „Verfügbar“ = aktiv und veröffentlicht (EOCS-Filter) | Kein eigener Verfügbarkeitsbegriff; die API blendet Deaktiviertes ohnehin aus | 2026-10-19 |
| Keine Liefer-, Preis- oder Verfügbarkeitszusage durch die KI | Ob lieferbar, klärt der Mitarbeiter | 2026-10-19 |
| Nur EOCS als Quelle in dieser Stufe | ProductForge liefert nur Variante → Master; EOCS liefert Geschwister in einem Aufruf; keine API-Änderung nötig | 2026-10-19 |
| Quelle hinter einer Schnittstelle austauschbar halten | Das Content Studio soll später ergänzt werden können | 2026-10-19 |
| Bei fehlender Liste „Varianten nicht geprüft“ statt Raten | Qualitätsprinzip: nichts Fehlendes erfinden | 2026-10-19 |
| Reines Anzeigen, keine Variantenauswahl im Formular | Bestellposition und Produktsuche bestimmen die betroffene Variante schon; ein Zusatzfeld würde das Formular überladen | 2026-10-19 |
| Obergrenze 30 Varianten je Master, gekürzte Liste wird gekennzeichnet | Schützt Oberfläche und Prompt; die KI soll bei Kürzung nicht „das sind alle“ behaupten | 2026-10-19 |
| Variantenliste vor Produktfeldern, nach Pflichtwissen | Sehr klein, aber für die Frage nach Größen entscheidend | 2026-10-19 |
| Wahl der Variante bei allgemeinen Anfragen über die Produktsuche gelöst, nur als Prüfpunkt | Die Suche liefert Master und Varianten; keine eigene Funktion nötig | 2026-10-19 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|
| Eigene kleine Datenklasse „Variantenliste“ plus ein Abruf im bestehenden EOCS-Baustein; keine eigene Quelle für ProductForge | Die Quelle bleibt austauschbar, ohne dass die App heute eine zweite Quelle bauen muss | 2026-10-19 |
| Ein EOCS-Aufruf je Produkt, der beide Richtungen auf einmal anfragt (Varianten des Artikels und Varianten seines Masters) | Die App weiß nicht, ob die SKU zu einem Master oder zu einer Variante gehört; ein Aufruf deckt beides ab | 2026-10-19 |
| Eigene Web-Route für das Nachladen im Formular, neben der Produktsuche, mit Form Request | Das Formular lädt nach der Wahl nach; der Browser spricht nie mit EOCS (wie bei der Produktsuche); Eingaben werden im Form Request geprüft | 2026-10-19 |
| Kurzer Zwischenspeicher je SKU nur für das Nachladen im Formular, nicht für den Vorschlag | Wiederholtes Wählen und Abwählen belastet EOCS nicht; der Vorschlag und „Erneut analysieren“ rechnen immer mit frischen Daten | 2026-10-19 |
| Variantenliste wird Teil des bestehenden Produktpakets der Analyse (gespeichert mit der Analyse) | Snapshot, Fortsetzung ohne Neuabruf und Protokoll funktionieren dann wie bei PROJ-38, ohne neue Tabelle und ohne Migration | 2026-10-19 |
| Eigener Block im Prompt-Baustein für Produktinformationen, vor den Produktfeldern eingeordnet | Gleiches Budgetverfahren wie die Produktfelder (ganz oder gar nicht), aber höhere Priorität als sie, niedrigere als das Pflichtwissen | 2026-10-19 |
| Der Variantenblock entsteht auch dann, wenn das Produktpaket sonst leer wäre (z. B. nur EOCS-Artikel) | Sonst gäbe es bei Produkten ohne ProductForge-Pflege keine Varianten | 2026-10-19 |
| Variantennamen werden als einzeilige Daten gesendet (Zeilenumbrüche und Steuerzeichen zusammengefasst) | Schutz vor Prompt-Einschleusung, wie beim Namen „Produkt ohne Wissen“ (PROJ-42, BUG-1) | 2026-10-19 |
| Keine neuen Pakete, keine neue Datenbanktabelle | Alles geht mit vorhandenem EOCS-Zugang, Cache und Analyse-Speicher | 2026-10-19 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

Backend nötig: ja (EOCS-Abruf, Prompt, Speicherung). Datenbankänderung: nein. Neue Pakete: keine. Frontend: kleine Ergänzung im Vorschlagsformular.

### A) Bausteine (Komponentenbaum)
```
Vorschlagsformular (Ticket und „Frage stellen“, gemeinsamer Baustein „Produkt wählen“)
+-- Produktsuche und Chips (vorhanden)
|   +-- NEU: Zeile unter jedem gewählten Chip
|       „Weitere Varianten: 18 cm (SKU 11747), 20 cm (SKU 11748)“
|       oder „Varianten konnten nicht geladen werden“ (klein, blockiert nichts)
|
Server: Nachladen für das Formular (neue Route neben der Produktsuche)
+-- Eingabe prüfen (Form Request)
+-- Varianten aus EOCS holen (Zwischenspeicher je SKU)
+-- nur Name, SKU, Status zurückgeben

Server: Vorschlag erstellen
+-- Produktpaket zusammenstellen (vorhanden)
|   +-- NEU: Variantenliste je gewähltem Produkt abrufen (frisch)
|   +-- NEU: Liste einmal je Master, höchstens 30
+-- Prompt-Baustein für Produktinformationen (vorhanden)
|   +-- NEU: Block „Weitere Varianten des Produkts“
|   +-- NEU: Vermerk „Varianten nicht geprüft“ bei Fehler
+-- Ergebnis
    +-- NEU: Quelle „Varianten aus EOCS“ mit Abrufzeit (bei den Quellen, wie die Produktinformationen)
```

### B) Daten (in Klartext)
Eine **Variantenliste** gehört zu einem gewählten Produkt und enthält:
- das Produkt, nach dem gefragt wurde (SKU), und ob es ein Master oder eine Variante ist,
- den Master (Name), zu dem die Liste gehört,
- je Variante: Name, SKU, aktiv oder nicht mehr aktiv,
- ob die Liste gekürzt wurde und wie viele Varianten fehlen,
- Abrufzeit.

Dazu kommt der Grund, wenn keine Liste da ist (keine SKU, EOCS kennt die SKU nicht, EOCS nicht erreichbar, Zugang ohne Berechtigung).

Gespeichert wird die Liste **zusammen mit dem Produktpaket der Analyse** (dort stehen heute schon die ProductForge-Produkte und EOCS-Artikel, jeweils mit Abrufzeit). Daher: keine neue Tabelle, keine Migration. Fortsetzungen übernehmen den Stand ohne Neuabruf, „Erneut analysieren“ holt neu. Im Formular lebt die Liste nur im Browser, solange die Seite offen ist.

### C) Technische Entscheidungen (und warum)
- **Quelle EOCS, ein Aufruf je Produkt.** Die App kennt die SKU des gewählten Produkts, aber nicht, ob sie zu einem Master oder zu einer Variante gehört. Der Aufruf fragt deshalb beide Richtungen auf einmal an: Varianten des Artikels (falls Master) und Varianten seines Masters (falls Variante). Daraus entsteht eine Liste ohne das gewählte Produkt selbst.
- **Der Browser spricht nie mit EOCS.** Wie bei der Produktsuche ruft die Seite eine eigene Route der App auf; die App fragt mit ihrem Zugang und gibt nur Name, SKU und Status weiter. Die Route steht hinter der Nutzerauswahl und dem Aufruflimit wie die Produktsuche; die Eingabe prüft ein Form Request.
- **Zwei Wege, zwei Frischegrade.** Das Nachladen im Formular nutzt einen kurzen Zwischenspeicher je SKU (wiederholtes Wählen belastet EOCS nicht). Der Vorschlag selbst ruft immer frisch ab, damit der Prompt keinen alten Stand nutzt.
- **Der Vorschlag verlässt sich nicht auf das Formular.** Der Server holt die Liste beim Erstellen selbst; was das Formular angezeigt hat, ist nur Hilfe für den Mitarbeiter. So stimmen Anzeige und Prompt nicht zufällig, sondern der Prompt ist immer korrekt abgesichert.
- **Prompt:** Eigener Block im bestehenden Baustein für Produktinformationen, mit festem Hinweis: Varianten nur nennen, wenn der Kunde danach fragt; keine Preise, Lieferzeiten oder Lieferzusagen; Liste ist vollständig oder ausdrücklich „gekürzt“. Beim Budget geht Pflichtwissen vor, dann die Variantenliste (ganz oder gar nicht), dann die Produktfelder.
- **Ohne Liste sagt der Prompt es offen:** „Varianten nicht geprüft, mache keine Aussagen über weitere Größen oder Varianten“, und das Ergebnis zeigt den Grund. Bei „keine Varianten vorhanden“ passiert nichts (gültiges Ergebnis).
- **Schnittstelle statt Festverdrahtung:** Das Produktpaket spricht nur mit einer „Variantenliste“, nicht mit EOCS selbst. Die spätere ProductForge-Quelle ersetzt oder ergänzt nur den Abruf.
- **Sicherheit:** Variantennamen sind Fremdtext und werden einzeilig und als Daten gesendet; Zugangsdaten tauchen in Fehlermeldungen und Logs nirgends auf (wie bisher bei EOCS).

### D) Betroffene Stellen (zum Verständnis, nicht zum Nachbauen)
- EOCS-Baustein: ein zusätzlicher Abruf und die Datenklasse für die Liste.
- Produktpaket und Zusammensteller (`ProductPackage`, `ProductResolver`): Liste mitführen, speichern, bei Fortsetzung übernehmen.
- Prompt-Baustein für Produktinformationen (`ProductPromptBuilder`): neuer Block und Vermerk; das Paket gilt auch als „nicht leer“, wenn nur eine Liste da ist.
- Produktsuche-Bereich: neue Route plus Form Request für das Nachladen.
- Formular: Zeile unter dem Chip (`scope-fields` Blade und Alpine-Datei); gilt automatisch für Ticket und „Frage stellen“, weil beide denselben Baustein nutzen.
- Ergebnis, Quellen und Protokoll: Eintrag „Varianten aus EOCS“ mit Abrufzeit.
- Tests (Pest): Abruf mit gestellter Antwort (Master, Variante, ohne Varianten, mehr als 30, deaktiviert, Fehler), Prompt-Inhalt und Budget, Route (Eingaben, Fehler), Formularkonfiguration, Snapshot und Fortsetzung.

### E) Pakete
Keine. Alles läuft mit vorhandenem Laravel (HTTP-Client, Cache), Alpine und Pest.

### F) Zu Beginn der Umsetzung zu prüfen
- Der EOCS-Zugang der App braucht `items:relations` (offene Frage 1 in der Spec). Das ist der erste Schritt im Backend: ein echter Abruf mit `SKU 11747` und `SKU 1625` bzw. 11235 aus `docs/items-api.md` gegen die echte API, bevor weitergebaut wird.


## Implementation Notes (Backend und Frontend, 2026-10-19)
- **EOCS-Abruf:** `EocsClient::variantsOf()` (ein Aufruf mit `include=variants,master_products.variants`, Abruf nur für ganze Zahlen); Datenklasse `App\Eocs\EocsVariantList` (Master, Varianten mit Name/SKU/aktiv, Anzahl Ausgelassener, gewählte SKUs, Abrufzeit; Namen einzeilig, höchstens 200 Zeichen). Obergrenze `analysis.product_info.max_variants` (30), Zwischenspeicher `variants_cache_minutes` (10).
- **Produktpaket:** `ProductPackage` führt `variantLists`, `variantProblems` und `variantChecked` (erfolgreich geprüfte SKUs, auch ohne Varianten); wird mit der Analyse als `product_data` gespeichert, keine Migration. `ProductResolver::variants()` ruft je gewähltem Produkt (ProductForge-Produkt mit SKU, EOCS-Artikel) ab, führt Listen je Master zusammen und übernimmt bei Fortsetzungen den Stand ohne Neuabruf (nur erfolgreich geprüfte SKUs; Fehler werden erneut versucht).
- **Prompt:** `ProductPromptBuilder::variantItems()` – Block „Weitere Varianten (EOCS)“ vor den Produktfeldern, ganz oder gar nicht; Fehler als Zeile „Varianten … nicht geprüft“ mit Verbot, über weitere Varianten zu sprechen, und als Hinweis im Ergebnis. Ein Paket nur mit Variantenliste gilt nicht als leer.
- **Formular:** Route `GET /produktvarianten` (`products.variants`, hinter Nutzerauswahl und Aufruflimit), `ProductVariantsController` + `ProductVariantsRequest`; Antwort `{checked, variants, omitted, error}`, nie ein Fehlerstatus für nicht prüfbare SKUs. Alpine (`scope-fields.js`): `variantsLoad`/`variantText`, Zeile unter dem gewählten Chip.
- **Ergebnis:** Unter „Produktinformationen“ erscheint „Varianten aus EOCS: …“ (aufklappbar).
- **Tests:** `tests/Feature/PROJ-54-ProductVariantsTest.php` (26). Gesamte Suite: 1795 Tests bestanden vor den letzten Ergänzungen (2 übersprungen, vorher vorhanden).
- **Abweichung:** Das gewählte, in EOCS deaktivierte Produkt wird nicht gesondert aufgeführt, sondern steht im Prompt-Block mit „gewähltes Produkt“ und „nicht mehr aktiv“, sofern EOCS es liefert; die Oberfläche zeigt nur die anderen Varianten.
- **Noch offen:** Echter Abruf gegen EOCS (Ability `items:relations`), Browserprüfung der Zeile unter dem Chip (375/768/1440 px) und Prüfpunkt „Essbare Fotos“ ohne Größe.

## QA Test Results

**Tested:** 2026-10-19
**Tester:** QA Engineer (AI)
**Methode:** komplette Pest-Suite (1804 Tests, 1802 bestanden, 2 übersprungen wie zuvor), Abgleich jedes Kriteriums mit Tests in `PROJ-54-ProductVariantsTest.php` (26) und `PROJ-54-QaTest.php` (8), Code-Durchsicht. **Nicht getestet:** echte EOCS-API (Ability `items:relations`), Browser (Chrome, Firefox, Safari) und die Breiten 375/768/1440 px; die Zeile unter dem Chip läuft in Alpine und ist nur über die Seitenkonfiguration und den Build (`npm run build` ohne Fehler) geprüft.

### Acceptance Criteria Status
- [x] AC-1 Abruf und Zeile unter dem Chip: Route liefert Name/SKU/Status der anderen Varianten (Test); Anzeige im Browser offen
- [x] AC-2 Einzelne Variante: Geschwister über den Master, das gewählte Produkt nicht unter „weitere“ (Test)
- [x] AC-3 Master: alle sichtbaren Varianten (Test)
- [x] AC-4 Eigenständiges Produkt: keine Zeile, kein Block, kein Hinweis „nicht geprüft“ (Test)
- [x] AC-5 Mehr als 30: gekürzt, „und N weitere“, Prompt „Liste gekürzt“ (Test)
- [x] AC-6 Wechsel/Entfernen des Chips, Absenden nicht blockiert (Code: Abruf läuft asynchron, Zeile hängt am gewählten Chip; Browser offen)
- [x] AC-7 Prompt-Block mit markiertem Produkt und Verhaltensregeln, Ticketanalyse und „Frage stellen“ (Tests)
- [x] AC-8 Antwortentwurf nennt nur Varianten aus der Liste (Prompt-Seite geprüft; das Verhalten der KI selbst ist nicht automatisch prüfbar)
- [x] AC-9 Budget: Pflichtwissen bleibt, Liste ganz oder gar nicht, Auslassung sichtbar (Test mit kleinem Platz)
- [x] AC-10 Quellen „Varianten aus EOCS“ mit Abrufzeit, Snapshot gespeichert (Test)
- [x] AC-11 Fortsetzung ohne Neuabruf, „Erneut analysieren“ frisch (Test für Fortsetzung; Neuanalyse über den Code-Pfad ohne übernommenes Paket)
- [x] AC-12 Mehrere Produkte desselben Masters: eine Liste (Test); siehe BUG-1 zur Zahl der Abrufe
- [x] AC-13 Deaktiviertes Produkt als „nicht mehr aktiv“ (Test)
- [x] AC-14 „Kein Produktbezug“ / Produkt ohne Wissen: kein Abruf (Test)
- [x] AC-15 Keine SKU / EOCS kennt SKU nicht: Vorschlag läuft, Vermerk „Varianten nicht geprüft“ (Tests)
- [x] AC-16 EOCS nicht erreichbar / ohne Berechtigung: Vorschlag läuft, Grund im Ergebnis, Prompt verbietet Aussagen, keine Zugangsdaten (Tests)
- [x] AC-17 Abruf im Formular scheitert: kleiner nicht blockierender Hinweis (Route liefert `checked: false` mit Text; Anzeige im Browser offen)

### Edge Cases Status
- [x] Produkt aus dem EOCS-Rückfall (`origin: eocs`): SKU = Artikelnummer, Abruf wie gewohnt (Test)
- [x] Nicht numerische SKU: kein Abruf (Test)
- [x] Mehrere Master: Listen je Master zusammengeführt, doppelte SKUs entfallen (Code; im Test nur ein Master)
- [x] Variante ohne Namen oder ohne `item_id`: übersprungen (Test)
- [x] Zeilenumbrüche, Steuerzeichen und Markdown-Überschriften in Namen (auch im Master-Namen und mit `\r\n`, U+2028): bleiben einzeilig, keine eigene Zeile im Prompt (Test)
- [x] Allgemeine Anfrage „Essbare Fotos“ ohne Größe: nur im Browser prüfbar (Produktsuche liefert Master und Varianten, siehe ProductForge-API); **offen**

### Security Audit Results
- [x] Zugang: Route nur mit gewähltem Namen (409 ohne), Aufruflimit 60/min (429 getestet)
- [x] Eingabe: Form Request; Arrays und Texte über 40 Zeichen werden abgelehnt; `11235&include=orderItems,depots` wird nicht als Nummer akzeptiert, es geht keine Anfrage an EOCS (Test)
- [x] Der Browser spricht nie mit EOCS; die Antwort enthält nur Name, SKU, Status; Token und URL erscheinen nicht (Test)
- [x] XSS: Namen mit `<script>` bleiben in JSON Daten und werden im Ergebnis maskiert (Test); die Zeile im Formular nutzt `x-text`
- [x] Prompt-Einschleusung über Variantennamen: siehe Edge Cases, abgesichert
- [x] CSRF: nur GET-Route ohne Zustandsänderung; das Formular bleibt unverändert
- [x] Datenschutz: nur Produktdaten, keine Kundendaten, in der Analyse gespeichert wie die übrigen Produktdaten

### Regression
- Alle PROJ-38-, PROJ-42-, PROJ-45- und PROJ-40-Tests sowie die Gesamtsuite bestehen. Der Test „Fortsetzung behält den EOCS-Artikel ohne neue Anfrage“ (PROJ-38) schlug anfangs fehl, weil die Variantenprüfung eine Anfrage mehr machte; behoben, indem erfolgreich geprüfte SKUs (auch ohne Varianten) mitgespeichert werden (`variant_checked`).
- Die Seite „Über die App“ nennt keinen Schritt, der durch PROJ-54 nutzbar wird; keine Änderung nötig.

### Bugs Found

#### BUG-1: Abruf je Produkt statt je Master (Low)
- **Steps:** Zwei Produkte desselben Masters wählen und Vorschlag erstellen.
- **Erwartet (Spec):** Varianten „einmal je Master abgerufen“. **Tatsächlich:** ein EOCS-Aufruf je gewählter SKU; die Listen werden danach zu einer zusammengeführt (Prompt und Speicher stimmen).
- **Auswirkung:** nur ein zusätzlicher Aufruf, keine falsche Ausgabe. Vorschlag: Spec an das Verhalten anpassen („Liste einmal je Master“) oder nach dem ersten Aufruf die SKUs des Masters überspringen.

#### BUG-2: Unbekannte SKU wird 10 Minuten zwischengespeichert (Low)
- **Steps:** SKU abfragen, die EOCS noch nicht kennt; kurz danach in EOCS anlegen; erneut wählen.
- **Tatsächlich:** das Formular zeigt bis zu 10 Minuten „SKU in EOCS nicht gefunden“. Der Vorschlag selbst fragt frisch und ist nicht betroffen.
- **Vorschlag:** „unbekannt“ nicht zwischenspeichern.

#### BUG-3: Gewähltes Produkt jenseits der 30 Varianten wird nicht markiert (Low)
- **Auswirkung:** nur bei Mastern mit mehr als 30 Varianten, in der Oberfläche zeigt die Zeile das gewählte Produkt dann in der Liste der „weiteren“. Nur kosmetisch.

### Summary
- **Acceptance Criteria:** 17/17 per Test oder Code bestanden; für AC-1, AC-6, AC-17 und die Anzeige der Zeile fehlt die Browserprüfung
- **Bugs:** 3 (0 critical, 0 high, 0 medium, 3 low)
- **Security:** keine Funde
- **Production Ready:** JA (Bedingungen am 2026-10-19 vom Auftraggeber bestätigt: echter EOCS-Abruf und Browserprüfung). Ursprünglich unter der Bedingung, dass (1) ein echter EOCS-Abruf funktioniert (`items:relations`) und (2) die Zeile im Browser einmal geprüft wurde. Ohne (1) liefert die App nur „Varianten nicht geprüft“, bricht aber nirgends ab.


## Deployment
_To be added by /deploy_

### Nachtrag 2026-10-19 (Bugfixes und Freigabe)
- Der Auftraggeber hat den echten EOCS-Abruf und die Zeile im Browser bestätigt.
- **BUG-1** durch Angleichen der Spec behoben (Abruf je gewähltem Produkt, Listen je Master zusammengeführt; Verhalten unverändert, ein zusätzlicher Aufruf).
- **BUG-2** behoben: eine unbekannte SKU wird nicht mehr zwischengespeichert (`ProductVariantsController`, Test „an unknown SKU is not remembered …“).
- **BUG-3** bewusst nicht behoben (nur kosmetisch, nur bei Mastern mit mehr als 30 Varianten).
- Tests: `PROJ-54-ProductVariantsTest.php` (27) und `PROJ-54-QaTest.php` (8) bestehen.
