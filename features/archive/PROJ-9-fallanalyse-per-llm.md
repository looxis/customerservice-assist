# PROJ-9: Fallanalyse per LLM

## Status: Approved
**Created:** 2026-10-02
**Last Updated:** 2026-10-06

## Dependencies
- Requires: PROJ-4 (Knowledge-Auswahl) – Wissen für Kundengruppe und Produkt, Vorschläge aus der Bestellung
- Requires: PROJ-5 (Nutzerauswahl) – Analyse nur mit gewähltem Namen (Middleware „Name erforderlich")
- Requires: PROJ-6 (Zammad-Ticket laden) – bereinigter Verlauf, interne Notizen, Kundennachrichten
- Requires: PROJ-7 (Bestellung aus EOCS laden) – Bestellung, Status, Versand, Positionen mit Personalisierung
- Übernimmt: PROJ-8 (Bestelldaten manuell ergänzen) – ist in das Analyse-Formular dieses Features aufgegangen
- Wird genutzt von: PROJ-10 (Ergebnisansicht), PROJ-11 (Protokoll), PROJ-12 (Feedback), PROJ-28 (Übersetzung), PROJ-30 (Arbeitsabläufe: Fallkategorie und Vorgänge)

## Begriffe
- **Analyse:** ein Aufruf des Sprachmodells mit Ticketkontext, Bestelldaten, Mitarbeiterkontext und ausgewähltem Wissen; liefert ein Ergebnis in fester Struktur.
- **Ticketkontext:** der Teil des Tickets, der an das Sprachmodell geht. Drei Varianten (siehe unten).
- **Zusammenfassung:** ein vorgeschalteter, eigener KI-Aufruf, der den Verlauf vor der letzten Kundennachricht verdichtet.
- **Bereinigung:** dieselbe Aufbereitung wie in der Ticketansicht (ohne eingeklappte Zitate, unsere Signaturen, Amazon-Textbausteine und klickbare Links), dazu Ersetzung personenbezogener Kontaktdaten.

## User Stories
- Als Aushilfe möchte ich zu einem Ticket mit einem Klick einen fachlich begründeten Vorschlag bekommen, damit ich ohne Rückfrage bei erfahrenen Kollegen arbeiten kann.
- Als Mitarbeiter möchte ich eigene Beobachtungen ergänzen (z. B. „Foto geprüft, kein Produktionsfehler"), damit die Analyse sie berücksichtigt.
- Als Mitarbeiter möchte ich vor der Analyse sehen und wählen, welcher Teil des Tickets an die KI geht, damit ich Kosten, Datenschutz und Qualität abwägen kann.
- Als Mitarbeiter möchte ich bei langen Tickets zuerst eine Zusammenfassung prüfen und korrigieren können, damit die Analyse auf einer richtigen Fallgeschichte aufbaut.
- Als Mitarbeiter möchte ich, dass die App „unklar" sagen darf und mir dann die richtige Rückfrage vorschlägt, statt Fakten zu erfinden.
- Als Mitarbeiter möchte ich bei fehlender EOCS-Bestellung die wichtigsten Bestelldaten von Hand eintragen können.
- Als Verantwortlicher möchte ich, dass jede Analyse festhält, mit welchem Wissensstand, Prompt und Modell sie entstand, damit sie reproduzierbar ist.

## Out of Scope
- Fertige Ergebnisansicht mit bearbeitbarem und kopierbarem Antwortentwurf, aufklappbaren Quellen und Entwurfs-Hinweis – PROJ-10 (PROJ-9 zeigt alle Teile schlicht an).
- Dauerhaftes Speichern von Analysen und Zusammenfassungen, Verlauf je Ticket – PROJ-11.
- Feedback und Wissenslücke – PROJ-12.
- Übersetzung von Nachrichten – PROJ-28.
- Anzeige der Arbeitsabläufe – PROJ-30 (PROJ-9 liefert Fallkategorie und Vorgänge dafür).
- Bildanalyse von Anhängen – PROJ-21.
- Zweiter Anbieter, Modellvergleich – PROJ-17 (PROJ-9 bleibt anbieterneutral).
- Automatische Analyse bei Ticket-Eingang – PROJ-20.
- Antwort nach Zammad übergeben – PROJ-19.
- Ausführen von Maßnahmen (Erstattung, Neuversand) – Non-Goal.

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Analyse-Formular
- [ ] Angenommen ein Ticket ist geladen, wenn der Nutzer die Ticketseite betrachtet, dann steht unter dem Ticketkopf ein Bereich „Analyse" mit Kundengruppe, Produkt(en), Ticketkontext-Variante, dem Feld „Zusätzliche Informationen / eigene Einschätzung" und dem Knopf „Analysieren".
- [ ] Angenommen eine EOCS-Bestellung ist geladen, wenn das Formular erscheint, dann ist die Kundengruppe aus dem Kanal der Bestellung vorgeschlagen (PROJ-4) und die Produkte aus den Artikelnummern der Positionen (`order_keywords`); ohne Bestellung ist „Noch unklar" bzw. „kein Produktbezug" vorbelegt.
- [ ] Angenommen der Nutzer ändert Kundengruppe oder Produkt, wenn er analysiert, dann gilt ausschließlich seine Wahl.
- [ ] Angenommen keine EOCS-Bestellung ist geladen, wenn das Formular erscheint, dann gibt es einen aufklappbaren Bereich „Bestelldaten von Hand" mit Bestellnummer, Kanal, Bestelldatum, Produkt und Personalisierung (Freitext); die Angaben gehen als „vom Mitarbeiter eingetragen" an die Analyse.
- [ ] Angenommen das Kontextfeld ist leer, wenn analysiert wird, dann läuft die Analyse trotzdem; das Feld ist optional und fasst höchstens 4.000 Zeichen.
- [ ] Angenommen kein Name ist gewählt, wenn „Analysieren" geklickt wird, dann wird nicht analysiert, und der Hinweis aus PROJ-5 erscheint; alle Eingaben bleiben erhalten.

### Ticketkontext: drei Varianten
- [ ] Angenommen das Formular ist sichtbar, wenn der Nutzer die Variante wählt, dann stehen zur Wahl: „Ganzer Verlauf", „Nur letzte Kundennachricht" und „Letzte Kundennachricht + Zusammenfassung".
- [ ] Angenommen der Verlauf vor der letzten Kundennachricht hat weniger als 5 Nachrichten und zusammen höchstens 6.000 Zeichen bereinigten Text, wenn das Formular erscheint, dann ist „Ganzer Verlauf" vorausgewählt.
- [ ] Angenommen der Verlauf vor der letzten Kundennachricht hat 5 oder mehr Nachrichten, oder mindestens 3 Nachrichten mit zusammen mehr als 6.000 Zeichen, wenn das Formular erscheint, dann ist „Letzte Kundennachricht + Zusammenfassung" vorausgewählt und die App schlägt vor, zuerst die Zusammenfassung zu erstellen.
- [ ] Angenommen das Ticket hat nur eine Nachricht, wenn das Formular erscheint, dann wird keine Variante angeboten.
- [ ] Angenommen der Nutzer öffnet „Was an die KI geht", wenn er die Vorschau betrachtet, dann sieht er genau den Ticketkontext der gewählten Variante nach Bereinigung und Ersetzung, dazu die Bestelldaten und den Mitarbeiterkontext.
- [ ] Angenommen eine Variante wird verwendet, wenn der Ticketkontext entsteht, dann gilt für alle drei Varianten dieselbe Bereinigung; interne Notizen sind als „intern" gekennzeichnet; E-Mail-Adressen, Telefonnummern und Postanschriften sind durch benannte Platzhalter ersetzt (z. B. `[E-MAIL_1]`, `[TELEFON_1]`, `[ADRESSE_1]`); der Vorname des Kunden bleibt für die Anrede.

### Umkehrbare Platzhalter
- [ ] Angenommen Kontaktdaten wurden vor dem Senden ersetzt, wenn die App sich die Zuordnung Platzhalter → Originalwert merkt, dann nur für diese Analyse und nie beim Anbieter.
- [ ] Angenommen eine EOCS-Bestellung ist geladen, wenn der Ticketkontext entsteht, dann ist ihre Lieferadresse als Platzhalter `[LIEFERADRESSE]` bekannt und wird dem Sprachmodell als vorhanden genannt, aber nicht übertragen.
- [ ] Angenommen der Antwortentwurf enthält einen bekannten Platzhalter (z. B. „Ist diese Adresse korrekt: [LIEFERADRESSE]?"), wenn das Ergebnis angezeigt wird, dann setzt die App den Originalwert ein und markiert die Stelle als „von der App eingesetzt"; beim Kopieren (PROJ-10) wird der eingesetzte Text übernommen.
- [ ] Angenommen der Antwortentwurf enthält einen unbekannten Platzhalter, wenn das Ergebnis angezeigt wird, dann bleibt er stehen und ist deutlich als „bitte ausfüllen" markiert.

### Zusammenfassung (Stufe 1)
- [ ] Angenommen die Variante „Letzte Kundennachricht + Zusammenfassung" ist gewählt und es gibt keine aktuelle Zusammenfassung, wenn der Nutzer „Zusammenfassung erstellen" klickt, dann erstellt ein eigener KI-Aufruf eine Zusammenfassung aller Nachrichten vor der letzten Kundennachricht einschließlich relevanter interner Notizen.
- [ ] Angenommen eine Zusammenfassung wurde erstellt, wenn sie angezeigt wird, dann steht sie oberhalb der letzten Kundennachricht mit den Abschnitten Sachverhalt, Kundenwunsch, bisherige Maßnahmen und Zusagen, Entscheidungen, Korrekturen und offene Fragen, dazu „zusammengefasst bis Nachricht vom …".
- [ ] Angenommen eine Zusammenfassung wird angezeigt, wenn der Nutzer sie korrigiert und übernimmt, dann wird die Analyse mit der korrigierten Fassung ausgeführt, und die Zusammenfassung ist als „von Hand geändert" gekennzeichnet.
- [ ] Angenommen eine Zusammenfassung existiert und das Ticket hat seither neue oder geänderte Nachrichten vor der letzten Kundennachricht, wenn das Ticket erneut geladen wird, dann ist sie als „veraltet" gekennzeichnet und wird vor erneuter Verwendung aktualisiert; eine von Hand geänderte Fassung wird dabei nicht stillschweigend überschrieben, sondern der Nutzer wird gefragt.
- [ ] Angenommen eine aktuelle Zusammenfassung existiert, wenn erneut analysiert wird, dann wird sie wiederverwendet, ohne einen weiteren KI-Aufruf.
- [ ] Angenommen eine Zusammenfassung existiert, wenn sie angezeigt wird, dann bleibt der Originalverlauf vollständig sichtbar.
- [ ] Angenommen noch keine Datenbank existiert (vor PROJ-11), wenn eine Zusammenfassung erstellt wird, dann wird sie vorübergehend für 7 Tage je Ticket aufbewahrt und ist als „vorübergehend gespeichert" gekennzeichnet; mit PROJ-11 wird sie dauerhaft gespeichert.

### Analyse (Stufe 2)
- [ ] Angenommen der Nutzer klickt „Analysieren", wenn die Analyse läuft, dann zeigt das Lade-Overlay „Ticket wird analysiert …", und ein zweiter Klick löst keine zweite Analyse aus.
- [ ] Angenommen die Analyse läuft, wenn das Sprachmodell aufgerufen wird, dann erhält es Ticketkontext, Bestelldaten (EOCS oder von Hand, ohne Adressen und Zahlungsdaten), Kundengruppe, Produkte, Mitarbeiterkontext und das von PROJ-4 ausgewählte Wissen mit IDs und Entwurfskennzeichen; fachliche Regeln stehen nur im Wissen, nicht im Prompt.
- [ ] Angenommen die Analyse ist fertig, wenn das Ergebnis vorliegt, dann enthält es: Kurzfassung (was ist passiert, was will der Kunde), Fallkategorie aus der Kategorienliste, Fallmuster soweit bestimmbar, bei Reklamationen die Einstufung „berechtigt", „unberechtigt" oder „unklar / noch nicht entscheidbar", empfohlene Maßnahme in Worten und als Vorgänge aus der Vorgangsliste (PROJ-30), Befugnis (darf der Kundenservice selbst entscheiden, ggf. welche Freigabe durch wen), Begründung, fehlende Informationen mit konkreter Rückfrage, verwendete Knowledge-IDs, Confidence (HOCH, MITTEL, NIEDRIG) mit Gründen, interne To-dos und Antwortentwurf.
- [ ] Angenommen Informationen fehlen für eine Entscheidung, wenn das Ergebnis vorliegt, dann darf es „unklar / noch nicht entscheidbar" lauten und nennt, was fehlt, von wem es kommen muss und welche Rückfrage zu stellen ist.
- [ ] Angenommen das Ergebnis nennt Knowledge-IDs, wenn es geprüft wird, dann sind nur IDs aus dem mitgegebenen Wissen gültig; andere werden entfernt und als Hinweis „unbekannte Quelle entfernt" angezeigt.
- [ ] Angenommen das Ergebnis nennt Vorgänge oder eine Kategorie außerhalb der Listen, wenn es geprüft wird, dann werden diese verworfen und als Hinweis angezeigt; das übrige Ergebnis bleibt gültig.
- [ ] Angenommen das Ergebnis stützt sich auf Entwurfs-Wissen, wenn es angezeigt wird, dann ist das erkennbar (Kennzeichnung je ID).
- [ ] Angenommen die Kundennachricht ist nicht auf Deutsch, wenn das Ergebnis vorliegt, dann ist der Antwortentwurf in der Sprache der Kundennachricht und alle übrigen Teile auf Deutsch.
- [ ] Angenommen die Analyse ist fertig, wenn das Ergebnis angezeigt wird, dann stehen alle Teile schlicht untereinander unter dem Ticket (die Arbeitsansicht folgt mit PROJ-10), und das Formular bleibt mit allen Eingaben für eine erneute Analyse erhalten.
- [ ] Angenommen eine Analyse wurde durchgeführt, wenn ihre Metadaten betrachtet werden, dann sind Nutzer, Zeitpunkt, Modell, Prompt-Version, Wissensstand, Fingerabdrücke des Wissens, Ticketkontext-Variante und Dauer bekannt (für PROJ-11).

### Fehler
- [ ] Angenommen das Sprachmodell antwortet nicht innerhalb von 90 Sekunden oder ist nicht erreichbar, wenn analysiert wird, dann erscheint „Die Analyse ist gerade nicht möglich. Bitte erneut versuchen." mit „Erneut versuchen"; alle Eingaben bleiben erhalten.
- [ ] Angenommen das Sprachmodell liefert keine gültige Struktur, wenn das Ergebnis geprüft wird, dann versucht die App es einmal erneut; scheitert auch das, erscheint eine verständliche Meldung, und der Fehler steht ohne Ticketinhalte im Log.
- [ ] Angenommen der Zugang zum Sprachmodell fehlt oder ist ungültig, wenn analysiert wird, dann erscheint eine Meldung ohne technische Details.
- [ ] Angenommen die Zusammenfassung schlägt fehl, wenn sie erstellt werden soll, dann erscheint ein Hinweis, und der Nutzer kann eine andere Variante wählen.
- [ ] Angenommen ein Fehler tritt auf, wenn er angezeigt wird, dann enthält die Meldung keine Zugangsdaten, keine Ticketinhalte und keine technischen Texte des Anbieters.

## Edge Cases
- **Letzte Nachricht ist von uns** (Kunde hat noch nicht geantwortet): Als „letzte Kundennachricht" gilt die jüngste Nachricht vom Kunden; spätere eigene Nachrichten gehören zum Verlauf bzw. zur Zusammenfassung.
- **Kein Kundennachricht im Ticket** (nur interne Notizen): Analyse ist möglich, Varianten 2 und 3 entfallen.
- **Sehr lange letzte Kundennachricht** (z. B. 20.000 Zeichen): wird vollständig übertragen; überschreitet der Gesamtumfang die Grenze des Modells, wird zuerst der Verlauf verkürzt (Zusammenfassung vorgeschlagen), nie die letzte Kundennachricht.
- **Ticket in Zammad geschlossen:** Analyse möglich.
- **Mehrere Bestellungen geladen:** alle gehen an die Analyse.
- **Kundengruppe „Noch unklar":** Analyse mit allgemeingültigem Wissen; die Rückfrage nach Kundenart/Kanal (POLICY-004) gehört dann typischerweise zu den fehlenden Informationen.
- **Wissen leer** (kein Dokument passt): Analyse läuft, Confidence NIEDRIG, Hinweis „kein Wissen für diesen Fall".
- **Gleichzeitige Analysen** zweier Mitarbeiter zum selben Ticket: beide laufen unabhängig.
- **Zusammenfassung veraltet und von Hand geändert:** Nutzer wählt „neu erstellen" oder „meine Fassung weiter verwenden".
- **Platzhalter im Antwortentwurf** (z. B. `[E-Mail]`): Der Entwurf verwendet keine ersetzten Kontaktdaten; wo nötig, steht ein Hinweis für den Mitarbeiter.

## Technical Requirements (optional)
- Anbieter und Modell austauschbar (PRD); Start mit OpenAI per API (`OPENAI_TOKEN` in der `.env`; `ANTHROPIC_TOKEN` liegt für PROJ-17 bereits bereit); Zugang nur serverseitig.
- Modell und Prompts (Zusammenfassung, Analyse) bis zur Einstellungsseite (PROJ-31) in der Konfiguration bzw. als Dateien im Repository, mit derselben Struktur, die PROJ-31 später bearbeitbar macht.
- Voraussetzung vor Produktivbetrieb: Auftragsverarbeitungsvertrag mit dem Anbieter.
- Antwortzeit der Analyse typisch unter 30 Sekunden, Abbruch nach 90 Sekunden; Zusammenfassung typisch unter 15 Sekunden.
- Prompt versioniert im Repository; jede Analyse kennt Prompt-Version, Modell und Wissensstand.
- Keine Ticketinhalte, Kundendaten oder Modellantworten im Log.

## Open Questions
- [x] Zugang: `OPENAI_TOKEN` (und `ANTHROPIC_TOKEN` für später) in der `.env`, Start mit OpenAI (2026-10-06).
- [x] Auftragsverarbeitungsvertrag mit OpenAI ist abgeschlossen (2026-10-06). **Erneut zu überprüfen ab 01.01.2027.** Für Anthropic (PROJ-17) ist ein eigener Vertrag nötig.
- [ ] Voreingestelltes Modell für Analyse und Zusammenfassung (ggf. günstigeres für die Zusammenfassung) – in `/architecture`; später auf der Einstellungsseite wählbar (PROJ-31).
- [ ] Frei formulierte Anschriften ohne Postleitzahl im Ticketverlauf werden von den Mustern nicht immer erkannt. Reicht das für den Start (Lieferadresse aus EOCS wird sicher erkannt)?
- [ ] `OPENAI_TOKEN` ist in der laufenden App noch leer (Stand 2026-10-06, `.env` vermutlich nicht gespeichert); danach verfügbare Modelle abfragen und Voreinstellung festlegen.
- [ ] Ist die Schwelle „ab 5 Nachrichten oder ab 3 Nachrichten mit über 6.000 Zeichen" passend? Nach den ersten echten Tickets nachschärfen.
- [ ] Produktauswahl skaliert nicht: Bei Hunderten Produkt-Dateien wird die Liste der Kästchen unbrauchbar. Idee: Produkte aus Ticketverlauf und Bestellung vorschlagen und ein Suchfeld statt der ganzen Liste anbieten. Bewusst offen gelassen, bis es mehr Produkt-Dateien gibt (Product Owner, 2026-10-06).
- [x] Postanschriften werden nie übertragen; über umkehrbare Platzhalter kann der Antwortentwurf sie trotzdem enthalten (z. B. zur Bestätigung der Lieferadresse), eingesetzt erst in der App (2026-10-06).

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| PROJ-8 geht im Analyse-Formular auf: Bestelldaten von Hand nur, wenn keine EOCS-Bestellung geladen ist | EOCS liefert zuverlässig; ein eigenes Erfassungsformular wäre doppelter Aufwand | 2026-10-06 |
| PROJ-9 mit schlichter Anzeige aller Ergebnisteile; Arbeitsansicht in PROJ-10 | Sofort an echten Fällen testbar, ohne PROJ-10 vorwegzunehmen | 2026-10-06 |
| Drei Varianten für den Ticketkontext, Wahl durch den Mitarbeiter mit Vorschau | Mitarbeiter wägt Kontext, Kosten und Datenschutz ab und sieht, was übertragen wird | 2026-10-06 |
| Zweistufig bei langen Verläufen: zuerst Zusammenfassung vorschlagen, dann Analyse | Fehler in der Fallgeschichte werden vor der Analyse sichtbar; Zusammenfassung wird wiederverwendet und spart Tokens | 2026-10-06 |
| Schwelle: ab 5 Nachrichten oder ab 3 Nachrichten mit zusammen über 6.000 Zeichen | Vorschlag des Product Owners, nach ersten Fällen nachschärfen | 2026-10-06 |
| Zusammenfassung bearbeitbar, „von Hand geändert" gekennzeichnet, bei neuen Nachrichten „veraltet" | Mensch korrigiert die Fallgeschichte; Korrekturen gehen nicht stillschweigend verloren | 2026-10-06 |
| Zusammenfassung vor PROJ-11 vorübergehend 7 Tage je Ticket, danach dauerhaft in der Datenbank | Wiederverwendung ohne Datenbank; Tickets werden meist innerhalb weniger Tage abgeschlossen | 2026-10-06 |
| Einheitliche Bereinigung und Ersetzung von E-Mail, Telefon und Anschrift für alle Varianten; Vorname bleibt | Datensparsamkeit beim Anbieter, Anrede im Antwortentwurf bleibt möglich | 2026-10-06 |
| Adressen und Zahlungsdaten aus EOCS werden nie übertragen | Für die fachliche Bewertung nicht nötig | 2026-10-06 |
| Ergebnis liefert Vorgänge aus der gemeinsamen Vorgangsliste und eine Kategorie aus der Kategorienliste | Grundlage für Arbeitsabläufe (PROJ-30) und Befugnisse | 2026-10-06 |
| Unbekannte Knowledge-IDs, Vorgänge oder Kategorien werden verworfen und angezeigt, nicht stillschweigend | Kein erfundenes Wissen; Fehler des Modells werden sichtbar | 2026-10-06 |
| Antwortentwurf in Kundensprache, alles andere Deutsch | PRD | 2026-10-06 |
| Umkehrbare Platzhalter für Kontaktdaten und Lieferadresse; Einsetzen erst in der App | Anbieter sieht keine Kontaktdaten, Antwortentwurf kann sie trotzdem enthalten (z. B. Adressbestätigung) – kein Hin- und Herkopieren | 2026-10-06 |
| Lieferadresse aus EOCS bleibt in der App (nicht angezeigt, nicht übertragen), nur für Platzhalter | Ändert die PROJ-7-Entscheidung „Kundendaten nicht übernehmen" gezielt für diesen Zweck | 2026-10-06 |
| Start mit OpenAI; Modell und Prompts zunächst in Konfiguration/Repository, später auf der Einstellungsseite (PROJ-31) | Wunsch des Product Owners; bis dahin gleiche Struktur, damit nichts umgebaut werden muss | 2026-10-06 |
| Auftragsverarbeitungsvertrag mit OpenAI abgeschlossen, Wiedervorlage ab 01.01.2027 | Voraussetzung laut PRD; Vorgabe des Product Owners | 2026-10-06 |
| KI meldet fehlende Regeln ausdrücklich als „Fehlendes Wissen“, getrennt von fehlenden Fall-Informationen | Wissenslücken sollen sichtbar werden statt mit Allgemeinwissen überdeckt; Kreislauf testen → Wissen ergänzen → erneut testen | 2026-10-06 |
| Kontextfeld nur für Fakten zum Fall, nicht für Regeln (Platzhalter A, Hilfezeile) | Regeln im Feld verdecken Lücken; Regeln gehören in die Wissensdatenbank | 2026-10-06 |
| Kundengruppe und Produkte werden je Ticket gemerkt und beim nächsten Öffnen vorbelegt („zuletzt gewählt von … am …"); bis PROJ-11 vorübergehend 7 Tage, danach dauerhaft | Die Kundengruppe ändert sich bis zum Abschluss eines Tickets nicht; erneutes Auswählen kostet Zeit und führt zu Fehlern | 2026-10-06 |
| Die Kundengruppe wird außerdem je Zammad-Kunde bzw. -Organisation gemerkt (365 Tage, ohne Fallinhalte) und bei weiteren Tickets vorgeschlagen; „noch unklar" wird nie gemerkt | Ein Fachhändler bleibt ein Fachhändler; ist keine EOCS-Bestellung geladen, gibt es sonst keinen Vorschlag. Reihenfolge: Wahl für dieses Ticket, Kanal der Bestellung, Wahl für diesen Kunden | 2026-10-06 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|
| Offizielles Laravel AI SDK `laravel/ai` (v1.1, Laravel 13) | Anbieterneutral (OpenAI, Anthropic), strukturierte Ausgabe, Test-Attrappen; offiziell gepflegt | 2026-10-06 |
| Feste Ergebnisstruktur als Schema plus eigene Prüfung danach | Modell antwortet in fester Form; Wertelisten aus Konfiguration und Wissensauswahl; nichts Erfundenes rutscht durch | 2026-10-06 |
| Prompts als versionierte Dateien im Repository | Reproduzierbar; fachliche Regeln bleiben im Wissen; Bearbeitung in der App später mit PROJ-31 | 2026-10-06 |
| Platzhalter vor dem Senden, Einsetzen in der App; Lieferadresse per Abgleich mit EOCS | Kontaktdaten verlassen die App nie; Antwortentwurf kann sie trotzdem enthalten | 2026-10-06 |
| Synchroner Aufruf mit Lade-Overlay, danach Weiterleitung mit Ergebnis-Kennung | Einfach, keine Warteschlange; Neuladen löst keine zweite Analyse aus; Webserver-Limit ≥ 120 s nötig | 2026-10-06 |
| Zusammenfassung und letztes Ergebnis verschlüsselt im Laravel-Cache (Tabelle `cache`), 7 Tage, bis PROJ-11 | Vorübergehende Ablage laut Spec; Ablaufzeit eingebaut; Fallinhalte nicht im Klartext | 2026-10-06 |
| Eigene Konfiguration `config/analysis.php` für Modelle, Schwellen, Zeitlimit, Aufbewahrung | Eine Stelle; später von PROJ-31 überschreibbar | 2026-10-06 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

### Überblick
Die Analyse läuft in zwei klar getrennten Teilen: **Aufbereitung** (deterministisch, ohne KI: Ticketkontext, Platzhalter, Wissen, Bestelldaten) und **KI-Aufruf** (Zusammenfassung bzw. Analyse mit fester Ergebnisstruktur). Die KI-Anbindung läuft über das **offizielle Laravel AI SDK**, das OpenAI und Anthropic gleich behandelt – so bleibt die App anbieterneutral (PRD, PROJ-17). Bis zur Datenbank (PROJ-11) werden Zusammenfassung und letztes Ergebnis verschlüsselt im Laravel-Cache gehalten.

### A) Bausteine
```
Ticketseite (PROJ-6/7, erweitert)
+-- Bereich „Analyse" (neue Komponente, unter dem Ticketkopf)
|   +-- Kundengruppe (Auswahl, vorgeschlagen aus EOCS-Kanal)
|   +-- Produkt(e) (Mehrfachauswahl, vorgeschlagen aus EOCS-Artikelnummern)
|   +-- Bestelldaten von Hand (aufklappbar, nur ohne EOCS-Bestellung)
|   +-- Ticketkontext: drei Varianten (Auswahl, Vorauswahl nach Länge)
|   |   +-- Hinweis „zuerst Zusammenfassung erstellen" bei langen Verläufen
|   +-- „Was an die KI geht" (aufklappbare Vorschau je Variante)
|   +-- Zusätzliche Informationen / eigene Einschätzung (Textfeld, max. 4.000 Zeichen)
|   +-- „Analysieren" (Lade-Overlay, Sperre gegen Doppelklick)
+-- Zusammenfassung (oberhalb der letzten Kundennachricht im Verlauf)
|   +-- feste Abschnitte, Stand „zusammengefasst bis …", Kennzeichen veraltet / von Hand geändert
|   +-- „Zusammenfassung erstellen" / „Neu erstellen" / „Bearbeiten" + „Übernehmen"
+-- Ergebnis (schlicht, alle Teile untereinander; Arbeitsansicht folgt mit PROJ-10)
    +-- Kurzfassung, Kategorie, Fallmuster, Einstufung, Maßnahme + Vorgänge, Befugnis,
    |   Begründung, fehlende Informationen/Rückfrage, Confidence + Gründe, To-dos,
    |   Antwortentwurf (Platzhalter eingesetzt und markiert), Knowledge-IDs (Entwurf gekennzeichnet)
    +-- Hinweise zur Prüfung (verworfene IDs, Vorgänge, Kategorie)
    +-- Metadaten klein: Modell, Prompt-Version, Wissensstand, Dauer, Nutzer

Server (app/Analysis/)
+-- Ticketkontext-Aufbereitung: drei Varianten aus dem bereinigten Ticket (PROJ-6),
|   letzte Kundennachricht bestimmen, Längen-Schwelle, Vorschau
+-- Platzhalter: E-Mail, Telefon, Anschrift (Muster) und Lieferadresse (aus EOCS) ersetzen;
|   Zuordnung bleibt in der App
+-- Zusammenfassung: eigener KI-Aufruf, feste Struktur, Fingerabdruck der zusammengefassten
|   Nachrichten (erkennt „veraltet"), Bearbeitung, Ablage 7 Tage
+-- Analyse: Eingaben zusammenstellen (Kontext, Bestelldaten ohne Adresse/Zahlung, Kundengruppe,
|   Produkte, Mitarbeiterkontext, Wissen aus PROJ-4), KI-Aufruf mit fester Ergebnisstruktur,
|   Prüfung des Ergebnisses, ein zweiter Versuch bei ungültiger Struktur
+-- Prompts: zwei versionierte Dateien im Repository (Zusammenfassung, Analyse)
+-- Form Requests für Analyse und Zusammenfassung; Middleware „Name erforderlich" (PROJ-5)
```

### B) Daten
- **Ergebnis (feste Struktur):** Kurzfassung (Vorgang, Kundenwunsch), Kategorie (aus `knowledge.categories`), Fallmuster (Text), Einstufung (`berechtigt` / `unberechtigt` / `unklar`, nur bei Reklamation), Maßnahme (Text) und Vorgänge (aus `knowledge.actions`), Befugnis (selbst entscheiden ja/nein, Freigabe durch, Bezug auf Permission-ID), Begründung, fehlende Informationen (was, von wem, Rückfrage), Knowledge-IDs, Confidence (`HOCH`/`MITTEL`/`NIEDRIG`) mit Gründen, interne To-dos, Antwortentwurf (Sprache, Text mit Platzhaltern).
- **Metadaten je Analyse:** Nutzer, Zeitpunkt, Anbieter und Modell, Prompt-Version, Wissensstand und Fingerabdrücke, Ticketnummer, Variante, Kundengruppe, Produkte, Bestellnummern, Dauer, Tokenverbrauch, Prüfhinweise. Grundlage für PROJ-11.
- **Zusammenfassung:** Abschnitte, „bis Nachricht vom …", Fingerabdruck der Nachrichten, von Hand geändert ja/nein, Modell, Prompt-Version.
- **Ablage bis PROJ-11:** im vorhandenen Laravel-Cache (Datenbank-Tabelle `cache`), **verschlüsselt**, 7 Tage: Zusammenfassung je Ticket; letztes Ergebnis je Analyse (mit Kennung in der Adresse, damit Neuladen das Ergebnis zeigt). Platzhalter-Zuordnung nur verschlüsselt beim Ergebnis.
- **Konfiguration:** `OPENAI_TOKEN` (und `ANTHROPIC_TOKEN`) in der `.env`; Modell je Aufruf, Schwellen (5 Nachrichten / 3 Nachrichten mit 6.000 Zeichen), Zeitlimit 90 s, Aufbewahrung 7 Tage in einer eigenen Konfiguration `config/analysis.php`.

### C) Technische Entscheidungen (für Nicht-Entwickler)
- **Offizielles Laravel AI SDK (`laravel/ai`) statt direktem OpenAI-Client:** Es ist das Werkzeug der Laravel-Macher, unterstützt OpenAI und Anthropic mit derselben Schreibweise, liefert strukturierte Antworten nach festem Schema und bringt Test-Attrappen mit. Ein Anbieterwechsel (PROJ-17) wird zur Einstellung statt zum Umbau.
- **Feste Ergebnisstruktur als Schema:** Das Modell muss in genau dieser Form antworten; die Wertelisten (Kategorien, Vorgänge, erlaubte Knowledge-IDs) kommen aus Konfiguration bzw. Wissensauswahl. Danach prüft die App selbst noch einmal – was nicht passt, wird verworfen und angezeigt.
- **Zwei Prompt-Dateien im Repository mit Versionsnummer:** Fachliche Regeln stehen nie im Prompt (PRD), nur Rolle, Arbeitsweise und Ausgabeform. Jede Analyse speichert die Version. PROJ-31 macht die Prompts später in der App bearbeitbar (mit eigener Versionierung).
- **Platzhalter vor dem Senden, Einsetzen erst in der App:** Kontaktdaten und Lieferadresse verlassen die App nie. E-Mail und Telefon werden über Muster erkannt, die Lieferadresse über die EOCS-Daten (genauer Abgleich), sonstige Anschriften über Muster mit Postleitzahl und Straße. Grenze: frei formulierte Adressen ohne Postleitzahl werden nicht immer erkannt (siehe Open Questions).
- **Synchroner Aufruf statt Hintergrund-Job:** Die Analyse dauert typisch 10–30 s. Ein normaler Seitenaufruf mit Lade-Overlay ist einfacher und braucht keine Warteschlange; nach dem Ergebnis leitet die App auf die Ticketadresse mit Ergebnis-Kennung weiter (Neuladen löst keine zweite Analyse aus). Für den Betrieb muss der Webserver Anfragen bis 120 s zulassen (`/deploy`).
- **Cache statt Datenbanktabelle bis PROJ-11:** Die Spec verlangt „vorübergehend gespeichert"; der Cache hat Ablaufzeiten eingebaut. Verschlüsselt, weil Zusammenfassungen und Ergebnisse Fallinhalte enthalten.
- **Wissen und Produkte über bestehende Bausteine:** Kundengruppe aus dem EOCS-Kanal und Produkte aus den Artikelnummern (PROJ-4 `KnowledgeSuggester`), Wissen aus `KnowledgeSelector` – Arbeitsabläufe (PROJ-30) bleiben ausgeschlossen.
- **Log ohne Inhalte:** Ticketnummer, Modell, Dauer, Tokens, Fehlerart.

### D) Abhängigkeiten
- **`laravel/ai`** (neu, vom Product Owner am 2026-10-06 freigegeben): offizielles Laravel AI SDK – Anbindung an OpenAI (und später Anthropic), strukturierte Ausgabe, Test-Attrappen.

### E) Hinweise für /frontend und /backend
- Frontend: Komponenten Analyse-Formular, Zusammenfassung, Ergebnis (schlicht); Alpine für Variantenwahl, Vorschau, Bearbeiten der Zusammenfassung, Lade-Overlay.
- Backend: `app/Analysis/` (Kontext, Platzhalter, Zusammenfassung, Analyse, Ergebnis, Prüfung, Prompts), `config/analysis.php`, Prompt-Dateien, Routen (POST Analyse, POST/PUT Zusammenfassung) mit `staff.selected`, Form Requests, Anpassung PROJ-7 (Lieferadresse intern behalten).
- `.env.example`: `OPENAI_TOKEN=`, `ANTHROPIC_TOKEN=` (trägt der Product Owner ein).
- Tests mit den Attrappen des SDK, nur erfundene Tickets: Varianten und Schwellen, Platzhalter hin und zurück, Zusammenfassung (veraltet, bearbeitet, wiederverwendet), Ergebnisprüfung (unbekannte IDs/Vorgänge/Kategorie), zweiter Versuch, Zeitüberschreitung, kein Name, keine Kontaktdaten im übertragenen Text und im Log.
- Vor dem ersten echten Aufruf: verfügbare Modelle mit dem Token abfragen und Voreinstellung festlegen.


## Implementation Notes (Frontend + Backend)
**Gebaut am 2026-10-06**, Frontend und Backend in einem Durchgang; gegen OpenAI mit echten Tickets geprüft (AVV abgeschlossen).

- **Paket:** `laravel/ai` ^1.1 (offizielles Laravel AI SDK). Konfiguration `config/ai.php` veröffentlicht: Schlüssel aus `OPENAI_TOKEN` bzw. `ANTHROPIC_TOKEN`; **`store` für OpenAI fest auf `false`** (Standard des SDK war `true` – OpenAI hätte Anfragen samt Ticketinhalt aufbewahrt).
- **Konfiguration** `config/analysis.php`: Anbieter `openai`, Modelle `ANALYSIS_MODEL` (Standard `gpt-5.5`) und `SUMMARY_MODEL` (Standard `gpt-5.4-mini`), Zeitlimit 90 s, Schwellen 5 / 3 / 6.000, Kontextfeld 4.000 Zeichen, Aufbewahrung 7 Tage.
- **Prompts** `resources/prompts/analysis.md` und `summary.md` mit Versionszeile (`analysis-2026-10-06.1`, `summary-2026-10-06.1`); keine fachlichen Regeln.
- **Bausteine** `app/Analysis/`: `TicketContext` (drei Varianten, letzte Kundennachricht, Schwelle, Fingerabdruck), `Pseudonymizer` (E-Mail, Telefon, Anschriften in deutscher, niederländischer, französischer und italienischer Schreibweise, Lieferadresse aus EOCS als `[LIEFERADRESSE]`; Einsetzen markiert, unbekannte Platzhalter „bitte ausfüllen"), `Summary`/`Summarizer`, `CaseAnalyzer` (Eingabe, ein zweiter Versuch bei unbrauchbarer Antwort), `ResultValidator`, `LanguageModel` (Fehlerarten, Log ohne Inhalte mit Dauer und Tokens), `AnalysisStore` (verschlüsselt im Cache, 7 Tage), `AnalysisPanel` (Daten für die Ticketseite), Agenten `SummaryAgent` und `CaseAgent` mit festem Schema (Kategorien, Vorgänge, Knowledge-IDs als Auswahllisten).
- **Routen** (alle mit `staff.selected`): `POST /tickets/{n}/analyse`, `POST /tickets/{n}/zusammenfassung`, `PUT /tickets/{n}/zusammenfassung`; Ergebnis über `?analyse={id}` auf der Ticketseite. Form Requests `AnalyzeTicketRequest`, `UpdateSummaryRequest`.
- **Oberfläche:** `analysis/form` (Kundengruppe und Produkte aus EOCS vorgeschlagen, Bestelldaten von Hand ohne EOCS-Bestellung, Varianten mit Empfehlung, Vorschau „Was an die KI geht", Kontextfeld mit Zähler, Lade-Overlay), `analysis/summary` (über der letzten Kundennachricht; erstellen, neu erstellen, bearbeiten, „weiter verwenden" bei veralteter eigener Fassung), `analysis/result` (alle Teile schlicht, Prüfhinweise, Metadaten).
- **PROJ-7 angepasst:** `EocsOrder::$deliveryAddress` (nur intern für den Platzhalter).
- **Arbeitsabläufe** (PROJ-30) gehen nicht an die KI.
- **Gemessen mit echten Tickets:** Ticket#2137635 (Amazon, Italienisch, ganzer Verlauf): 25 s, 14.283 Eingabe-/1.955 Ausgabe-Tokens, gültig im ersten Versuch, Antwort auf Italienisch, keine Zusage außerhalb der Regeln. Ticket#2132884 (12 Nachrichten): Zusammenfassung 5 s mit `gpt-5.4-mini` (2.057/715 Tokens), Analyse mit Zusammenfassung 19 s (10.322/1.509 Tokens).
- **Gefunden und behoben beim echten Test:** Telefonnummern am Satzende und niederländische Anschriften („Zonnedauwlaan 8, 1433WB Kudelstaart") wurden zunächst nicht ersetzt; Muster erweitert und getestet.
- **Nachgebessert nach dem ersten Test (2026-10-06):** Kundengruppe und Produkte werden je Ticket gemerkt und vorbelegt, mit Hinweis unter dem Feld („Vorbelegt: zuletzt gewählt von Nele am 06.10.2026, 14:05 Uhr" / „aus dem Kanal der Bestellung" / „bei früheren Tickets dieses Kunden gewählt"). Die Kundengruppe wird zusätzlich je Zammad-Organisation (oder Kunde ohne Organisation) gemerkt (`AnalysisStore::caseChoice`/`customerGroup`, `Ticket::customerKey()`, `config('analysis.customer_group_retention_days')`). Gemerkt wird beim Absenden der Analyse.
- **Fehlendes Wissen (2026-10-06, Wunsch des Product Owners):** Neues Ergebnisfeld `knowledge_gaps` (Thema + offene Frage). Die KI soll fehlende Regeln nennen statt mit Allgemeinwissen aufzufüllen; getrennt von `missing_information` (Fakten zum Fall). Anzeige als Hinweis „Fehlendes Wissen“ oben im Ergebnis und Kennzeichen „Wissenslücke“. Prompt-Version `analysis-2026-10-06.2`. Grundlage für PROJ-12 (Meldung per Klick vorausfüllen).
- **Tonalität (2026-10-06, Wunsch des Product Owners):** Antworten wirkten bei Absagen zu trocken. Prompt `analysis-2026-10-06.3`: „empathisch und sachlich richtig“ statt „sachlich“, Ton richtet sich nach den Ton-Dokumenten im Wissen. Konkrete Formulierungen für Absagen stehen in TONE-002 (neuer Abschnitt „Absagen und nicht erfüllbare Wünsche“), nicht im Prompt.
- **Angebote und Auftragsbestätigungen (2026-10-09, Wunsch des Product Owners):** Neue Fallkategorie `quote-request` (Angebots- oder Auftragsanfrage) und Vorgänge `quote` (Angebot erstellen), `order-confirmation` (Auftrag bestätigen) in `config/knowledge.php`; Bezeichnungen der Kategorien (`category_labels`) gehen an die KI und stehen im Ergebnis statt des Schlüssels. Prompt `analysis-2026-10-09.2`: erst Rückfragen, wenn die Anfrage nicht eindeutig ist, sonst Angebot bzw. Auftragsbestätigung nach dem Wissen; nie Preise, Rabatte, Versandkosten oder Termine erfinden oder ausrechnen, stattdessen Platzhalter `[PREIS]`, `[VERSANDKOSTEN]`, `[LIEFERTERMIN]`, `[GUELTIG-BIS]` (erscheinen als „Stellen noch ausfüllen“); keine Angebotsnummer, Zuordnung über das Ticket. Authoring Guide um den Abschnitt „Angebote und Auftragsbestätigungen“ ergänzt. Kalkulation bleibt außerhalb: PROJ-34 (P2).
- **Bekannte Bestellnummern gehen immer an die Analyse (2026-10-09, Ticket#2138702):** Die Analyse fragte den Kunden nach der Bestellnummer, obwohl sie im Ticket stand. Ursache: Die Nummer aus der Amazon-Benachrichtigung wird aus dem Nachrichtentext herausgenommen und oben angezeigt; an die KI gingen nur aus EOCS geladene Bestellungen. Jetzt: (1) Beim Analysieren lädt die App alle im Ticket gefundenen Bestellnummern selbst aus EOCS (außer EOCS-IDs, deren Abruf mehrere Sekunden dauert); Häkchen „Bestelldetails nicht abrufen“ im Formular. (2) Nummern, die nicht geladen sind oder die EOCS nicht kennt, stehen im Abschnitt „Bestellungen“ als „im Ticket genannt, nicht aus EOCS geladen“ mit Kanal laut Nummernformat und Produkt aus der Amazon-Benachrichtigung. (3) Ist EOCS nicht erreichbar, läuft die Analyse trotzdem; ein Prüfhinweis im Ergebnis nennt das. (4) Prompt `analysis-2026-10-09.1`: nie nach einer genannten Bestellnummer fragen; fehlende Bestelldetails sind intern in EOCS nachzusehen. Nach der Analyse zeigt die Seite die geladenen Bestellungen.
- **Adress-Erkennung geschärft (2026-10-09, gefunden beim Test von PROJ-28):** Fünfstellige Nummern mit folgendem großgeschriebenem Wort wurden als Postleitzahl und Ort ersetzt, auch „Artikel 11282 Zaubertasse“, „Menge 12345 Stück“ oder eine Nummer am Zeilenende vor einem Satzanfang. Die KI sah dann weder Artikelnummer noch Produktname. Jetzt: nur auf einer Zeile, nicht nach Bezeichnungen wie Artikel, Nr., Menge, Bestellung, Rechnung, Nachricht, und nicht vor Mengenwörtern (Stück, Tassen, Euro …). Echte Anschriften (deutsch, niederländisch, französisch, italienisch) werden weiter ersetzt.
- **Kontextfeld (2026-10-06):** Platzhalter mit Beispielen für Fakten zum Fall und Hilfezeile „Gilt für die KI als geprüfter Fakt. Allgemeine Regeln … gehören … in die Wissensdatenbank – bitte als Wissenslücke an Etienne melden.“ Hintergrund: Eine Regel im Kontextfeld verdeckt eine Wissenslücke, weil die KI das Feld als geprüften Fakt mit Vorrang behandelt. Im Testmodus zusätzlich ein fester Hinweis (PROJ-32).
- **Abweichung:** Die Vorschau „Was an die KI geht" zeigt den Ticketteil und nennt die übrigen Bestandteile; der Text des Kontextfelds wird vor dem Absenden nicht in die Vorschau übernommen.
- **Offen für `/deploy`:** Webserver-Zeitlimit mindestens 120 s.
- **`.env.example`:** `OPENAI_TOKEN=`, `ANTHROPIC_TOKEN=`, optional `ANALYSIS_MODEL=`, `SUMMARY_MODEL=` ergänzen (trägt der Product Owner ein).
- **Tests:** `tests/Feature/PROJ-9-CaseAnalysisTest.php` (48 Fälle, Attrappen des SDK, nur erfundene Daten). Gesamte Suite grün.

## QA Test Results

**Tested:** 2026-10-06
**App URL:** http://localhost:8081
**Tester:** QA Engineer (AI)

**Vorgehen:** Automatisierte Feature-Tests (Laravel AI SDK mit Attrappen, Zammad/EOCS nachgestellt, nur erfundene Daten), Code-Review jedes Kriteriums, Rauchtest gegen die laufende App mit echten Tickets (#2138220, #2132884, #2137635: Seite lädt in 0,5–0,7 s; Vorschau „Was an die KI geht" ohne E-Mail-Adressen oder Telefonnummern, nur Platzhalter). Echte Analysen gegen OpenAI hat der Product Owner im Browser durchgeführt (Chrome, Desktop), dabei gefundene Punkte sind bereits behoben (Bestellnummer im Titel, Kundengruppe merken, Kundensignaturen). Andere Browser und Handy-/Tablet-Breiten nicht eigens geprüft; die Oberfläche nutzt nur bestehende, responsiv geprüfte Komponenten (PROJ-1).

### Acceptance Criteria Status

#### Analyse-Formular
- [x] Bereich „Analyse" mit Kundengruppe, Produkten, Variante, Kontextfeld, „Analysieren"
- [x] Vorschlag aus der EOCS-Bestellung; ohne Bestellung „Noch unklar" (zusätzlich: Wahl je Ticket und je Kunde gemerkt)
- [x] Wahl des Nutzers entscheidet
- [x] „Bestelldaten von Hand" ohne EOCS-Bestellung, geht als „vom Mitarbeiter eingetragen" mit
- [x] Kontextfeld optional, höchstens 4.000 Zeichen
- [x] Ohne Namen keine Analyse, Eingaben bleiben

#### Ticketkontext: drei Varianten
- [x] Drei Varianten zur Wahl
- [x] Vorauswahl nach Schwelle (5 Nachrichten bzw. 3 mit über 6.000 Zeichen), Zusammenfassung wird vorgeschlagen
- [x] Nur eine Nachricht: keine Varianten
- [x] Vorschau zeigt den Ticketteil der gewählten Variante nach Ersetzung (dokumentierte Abweichung: Bestelldaten und Kontextfeld werden nur benannt, nicht angezeigt)
- [x] Gleiche Bereinigung, interne Notizen als „INTERN" gekennzeichnet, Platzhalter für E-Mail/Telefon/Anschrift, Vorname bleibt

#### Umkehrbare Platzhalter
- [x] Zuordnung nur in der App (verschlüsselt mit dem Ergebnis), nie beim Anbieter
- [x] `[LIEFERADRESSE]` bekannt, nicht übertragen
- [x] Bekannte Platzhalter eingesetzt und markiert
- [x] Unbekannte Platzhalter markiert „bitte ausfüllen"

#### Zusammenfassung (Stufe 1)
- [x] Eigener Aufruf über den Verlauf vor der letzten Kundennachricht inkl. interner Notizen
- [x] Anzeige oberhalb der letzten Kundennachricht mit Abschnitten und „zusammengefasst bis …"
- [x] Korrigieren und Übernehmen, Kennzeichnung „von Hand geändert"
- [x] „Veraltet" bei neuen Nachrichten, von Hand geänderte Fassung wird nicht überschrieben
- [x] Wiederverwendung ohne weiteren Aufruf
- [x] Originalverlauf bleibt sichtbar
- [x] 7 Tage vorübergehend, Kennzeichnung „vorübergehend gespeichert"

#### Analyse (Stufe 2)
- [x] Lade-Overlay „Ticket wird analysiert …", zweiter Klick gesperrt (im Browser)
- [x] Eingabe vollständig, ohne Adressen und Zahlungsdaten, Wissen mit IDs und Entwurfskennzeichen; keine fachlichen Regeln im Prompt
- [x] Alle Ergebnisteile vorhanden (zusätzlich „Fehlendes Wissen")
- [x] „Unklar" mit fehlenden Informationen und Rückfrage
- [x] Unbekannte Knowledge-IDs entfernt und angezeigt
- [x] Unbekannte Vorgänge/Kategorie verworfen und angezeigt
- [x] Entwurfs-Wissen je ID gekennzeichnet
- [x] Antwortentwurf in Kundensprache, Rest Deutsch (echter Test: Italienisch, #2137635)
- [x] Ergebnis schlicht unter dem Ticket, Formular bleibt mit Eingaben
- [x] Metadaten: Nutzer, Zeitpunkt, Modell, Prompt-Version, Wissensstand, Fingerabdrücke, Variante, Dauer

#### Fehler
- [x] Zeitüberschreitung/nicht erreichbar: Meldung, Eingaben bleiben (siehe BUG-1: ohne eigenen Knopf „Erneut versuchen")
- [x] Ungültige Struktur: ein zweiter Versuch, dann Meldung; Log ohne Inhalte
- [x] Fehlender/ungültiger Zugang: Meldung ohne technische Details
- [x] Zusammenfassung schlägt fehl: Hinweis mit Verweis auf andere Variante
- [x] Meldungen ohne Zugangsdaten, Ticketinhalte oder Anbietertexte

### Edge Cases Status
- [x] Letzte Nachricht von uns: jüngste Kundennachricht gilt als letzte
- [x] Keine Kundennachricht: Analyse möglich, nur „Ganzer Verlauf"
- [x] Sehr lange Tickets über der Modellgrenze: behoben (BUG-3)
- [x] Geschlossenes Ticket: Analyse möglich
- [x] Mehrere Bestellungen: alle gehen mit
- [x] „Noch unklar": Analyse mit allgemeingültigem Wissen
- [x] Wissen leer: Hinweis „Kein Wissen für diesen Fall" (BUG-2 behoben)
- [x] Gleichzeitige Analysen: unabhängig (je Ergebnis eigene ID)
- [x] Veraltete, von Hand geänderte Zusammenfassung: „neu erstellen" oder „weiter verwenden"
- [x] Platzhalter im Antwortentwurf: nur bekannte werden eingesetzt

### Security Audit Results
- [x] Name erforderlich für Analyse und Zusammenfassung (Middleware `staff.selected`); kein Login im MVP laut PRD
- [x] CSRF: alle POST/PUT-Formulare mit `@csrf`, übrige Formulare GET
- [x] Fremde Ergebnisse: Ergebnis-ID ist eine UUID und wird nur zum passenden Ticket angezeigt
- [x] XSS: Modellausgaben, Platzhalterwerte und Kontextfeld werden escaped (Test mit `<script>`/`<img onerror>`)
- [x] Datenschutz: Kontaktdaten ersetzt, Lieferadresse und Zahlungsdaten nie übertragen, OpenAI `store: false`, Ergebnisse und Zusammenfassungen verschlüsselt, Log ohne Inhalte
- [x] Zugangsdaten nur serverseitig, nicht in Seiten oder Meldungen
- [x] Eingaben validiert (Kundengruppe, Produkte, Variante aus festen Listen; Kontext begrenzt)
- [x] Rate Limiting: 6 KI-Aufrufe pro Minute je Name und Adresse (BUG-4 behoben)
- Hinweis (kein Bug): Prompt-Injection über Kundentext ist grundsätzlich möglich. Abgemildert durch feste Wertelisten, Prüfung des Ergebnisses, keine Aktionen der App und Prüfung durch einen Menschen.

### Regression
- Gesamte Suite: 695 Tests grün (PROJ-1 bis PROJ-7, PROJ-24, PROJ-25, PROJ-30, PROJ-32).
- Seite „Über die App": Hinweis „in Arbeit" bei den Schritten 3 (Bestelldaten von Hand), 4 und 6 entfernt und Texte an den Stand angepasst; Schritte 7 und 8 bleiben „in Arbeit" (PROJ-10, PROJ-12).

### Bugs Found

#### BUG-1: Kein eigener Knopf „Erneut versuchen" bei Fehlern
- **Severity:** Low
- **Status:** Behoben 2026-10-06 – Knopf „Erneut versuchen" in der Fehlermeldung bei vorübergehenden Fehlern (nicht bei fehlendem Zugang); schickt das erhaltene Formular erneut ab.
- **Steps to Reproduce:** Analyse starten, während der Anbieter nicht erreichbar ist. Erwartet: Meldung mit „Erneut versuchen". Tatsächlich: Meldung ohne Knopf; das Formular mit allen Eingaben bleibt, ein erneuter Klick auf „Analysieren" funktioniert.
- **Priority:** Nice to have (mit PROJ-10)

#### BUG-2: Kein Hinweis in der Oberfläche, wenn kein Wissen passt
- **Severity:** Low
- **Status:** Behoben 2026-10-06 – Hinweis „Kein Wissen für diesen Fall" im Ergebnis; Warnungen der Wissensauswahl werden angezeigt.
- **Steps to Reproduce:** Kundengruppe/Produkt wählen, für die kein Dokument gilt, analysieren. Erwartet: Hinweis „kein Wissen für diesen Fall". Tatsächlich: nur „Verwendetes Wissen: keines" und die Begründung des Modells; Warnungen der Wissensauswahl stehen nur in den Metadaten.
- **Priority:** Fix in next sprint (mit PROJ-10)

#### BUG-3: Keine Kürzung bei Überschreiten der Modellgrenze
- **Severity:** Low
- **Status:** Behoben 2026-10-06 – Grenze `analysis.max_input_characters` (400.000 Zeichen, ca. 100.000 Tokens): „Ganzer Verlauf" darüber wird mit Verweis auf „Letzte Kundennachricht + Zusammenfassung" abgelehnt; für die Zusammenfassung werden die ältesten Nachrichten gekürzt, die letzte Kundennachricht nie.
- **Steps to Reproduce:** Ticket mit sehr langem Verlauf und „Ganzer Verlauf" analysieren. Erwartet laut Edge Case: zuerst Verlauf kürzen, nie die letzte Kundennachricht. Tatsächlich: keine Prüfung; bei Überschreitung käme „Die Analyse ist gerade nicht möglich". Bei der Kontextgröße aktueller Modelle praktisch nicht zu erwarten; die Zusammenfassung ist ab der Schwelle vorausgewählt.
- **Priority:** Nice to have

#### BUG-4: Kein serverseitiges Rate Limiting für KI-Aufrufe
- **Severity:** Low
- **Status:** Behoben 2026-10-06 – Begrenzung `throttle:language-model` auf Analyse und Zusammenfassung, 6 Aufrufe pro Minute je Name und Adresse (`analysis.calls_per_minute`), verständliche Meldung statt Fehlerseite.
- **Steps to Reproduce:** Analyse-Formular mehrfach schnell per Skript absenden. Erwartet: Begrenzung. Tatsächlich: jeder Aufruf geht an den Anbieter (Kosten). Im Browser verhindert das Formular Doppelklicks; die App ist nur intern erreichbar.
- **Priority:** Bei `/deploy` mit einplanen (z. B. Begrenzung je Name/Browser)

### Summary
- **Acceptance Criteria:** 39/39 bestanden (eins mit dokumentierter Abweichung, eins mit BUG-1 als Low)
- **Bugs Found:** 4 total (0 critical, 0 high, 0 medium, 4 low) – alle behoben 2026-10-06
- **Security:** Pass (Rate Limiting als Low für `/deploy`)
- **Production Ready:** YES
- **Recommendation:** Freigeben; BUG-1/2 mit PROJ-10 erledigen, BUG-4 bei `/deploy`; Webserver-Zeitlimit ≥ 120 s bei `/deploy` beachten.

## Deployment
_To be added by /deploy_
