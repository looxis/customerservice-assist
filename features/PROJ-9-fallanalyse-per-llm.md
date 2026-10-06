# PROJ-9: Fallanalyse per LLM

## Status: Planned
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
- [ ] Ist die Schwelle „ab 5 Nachrichten oder ab 3 Nachrichten mit über 6.000 Zeichen" passend? Nach den ersten echten Tickets nachschärfen.
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
