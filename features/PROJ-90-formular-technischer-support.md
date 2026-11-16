# PROJ-90: Geführtes Formular „Technischer Support“ in „Vorschlag erstellen“

## Status: Planned
**Created:** 2026-11-16
**Last Updated:** 2026-11-16

## Dependencies
- Requires: PROJ-89 (Verkaufskanal im Vorschlagsformular)
- Requires: PROJ-80 (Kategorie `technischer-support` aus der Klassifikation neuer Tickets)
- Requires: PROJ-86 (Reklamationsformular: Vorbild und gemeinsame Formularwahl)
- Requires: PROJ-9, PROJ-10 (Fallanalyse und Ergebnisansicht)
- Requires: PROJ-42 (Pflichtangabe Produkt, wird in diesem Formular gelockert)
- Berührt: PROJ-74 (Einführungsmodus), PROJ-76 (Formular-Feinschliff), PROJ-64 (DHL-Retourenlabel im Freitextformular)

## Hintergrund
Das Reklamationsformular (PROJ-86) hat gezeigt, dass wenige Klick-Entscheidungen des Mitarbeiters bessere Entwürfe liefern als Freitext und als ein Wissen, das jede Variante in Textform vorwegnehmen muss. Deshalb bekommt jede Klassifikations-Kategorie nach und nach ein eigenes geführtes Formular, je in einer eigenen Spec. Diese Spec ist die erste nach der Reklamation.

„Technischer Support“ umfasst Zahlungsprobleme, vergessene Passwörter, Login- und Bestellprobleme, meist in einem der Online-Shops (Beispiel: Ticket 2140446). Entscheidend sind hier der Verkaufskanal (PROJ-89) und die Frage, ob eine Lösung schon bekannt ist oder der Kunde zuerst weitere Angaben liefern muss.

## User Stories
- Als Mitarbeiter im Kundenservice will ich bei einem Ticket der Kategorie „Technischer Support“ nur die Angaben sehen, die für diese Fälle nötig sind, damit ich schneller zum Vorschlag komme.
- Als Mitarbeiter will ich per Klick angeben, ob eine Lösung bekannt ist, damit der Entwurf entweder die Lösung beschreibt oder gezielt nachfragt.
- Als Mitarbeiter will ich einen kurzen Hinweis zur Lösung ergänzen können (z. B. „Passwort-vergessen-Funktion nutzen“), ohne einen langen Freitext zu schreiben.
- Als Aushilfe will ich bei einem unklaren Problem eintragen, welche Informationen fehlen (z. B. ein Screenshot), damit der Entwurf den Kunden genau danach fragt und nichts Falsches verspricht.
- Als Mitarbeiter will ich kein Produkt angeben und auch nicht „Kein Produktbezug“ ankreuzen müssen, weil die meisten Supportfälle kein Produkt betreffen.
- Als Mitarbeiter will ich jederzeit ein anderes Formular (Reklamation, Freitext) wählen können, wenn die Vorklassifikation nicht passt.

## Out of Scope
- Verkaufskanal, Kundengruppe „Globus“ und Kanal-Vorbelegung (PROJ-89).
- Anpassung des Wissens zu technischem Support je Kanal (PROJ-91).
- Geführte Formulare für die übrigen Kategorien (WISMO, Angebotsanfrage, Bestellung, Storno, Adressänderung, Zahlung, Produktfrage, Sonstiges): je eine eigene spätere Spec.
- Feste Auswahl typischer Probleme (Passwort, Login, Zahlung …) als Klick-Optionen: erst sinnvoll, wenn sich im Betrieb zeigt, welche Fälle sich wiederholen.
- Aktionen in den Shops aus der App heraus (Passwort zurücksetzen, Konto freischalten, Zahlung prüfen).
- Änderungen am Reklamationsformular außer der gemeinsamen Formularwahl.
- „Frage stellen“ ohne Ticket: bleibt beim bisherigen Formular.

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Formularwahl
- [ ] Angenommen ein Ticket hat die Kategorie `technischer-support` (bestätigt, sonst Vorschlag der KI), wenn der Mitarbeiter „Vorschlag erstellen“ öffnet, dann ist das Formular „Technischer Support“ vorgewählt.
- [ ] Angenommen ein Ticket ist geöffnet, wenn der Mitarbeiter „Vorschlag erstellen“ sieht, dann gibt es statt des Knopfs „Reklamationsformular verwenden“ eine Auswahl „Formular“ mit den Einträgen „Technischer Support“, „Reklamation“ und „Freitext“; jedes Formular ist bei jedem Ticket wählbar.
- [ ] Angenommen ein Ticket hat die Kategorie `reklamation`, dann ist wie bisher „Reklamation“ vorgewählt; bei jeder anderen oder fehlenden Kategorie „Freitext“.
- [ ] Angenommen der Mitarbeiter wechselt das Formular, dann bleiben die Eingaben der anderen Formulare im Browser erhalten, gesendet werden nur die des gewählten Formulars.
- [ ] Angenommen ein früherer Vorschlag des Tickets wurde mit einem bestimmten Formular erstellt, wenn das Formular erneut geöffnet wird, dann ist dieses Formular gewählt und mit den damaligen Antworten vorbelegt.

### Felder des Formulars
- [ ] Angenommen das Formular „Technischer Support“ ist gewählt, dann zeigt es in dieser Reihenfolge: Verkaufskanal und Kundengruppe (PROJ-89), Produkt (optional), die Bestellangaben wie bisher, die Frage „Ist eine mögliche Lösung bereits bekannt?“ und das dazugehörige Textfeld.
- [ ] Angenommen das Formular „Technischer Support“ ist gewählt, dann sind das Feld „Zusätzliche Informationen / eigene Einschätzung“, die Hilfe „So schreibst du es“ und die Option „DHL-Retourenlabel erzeugen“ ausgeblendet und werden nicht gesendet.
- [ ] Angenommen das Formular „Technischer Support“ ist gewählt, dann bleiben Bestellerkennung, „Bestelldaten von Hand“ und „Zusammengehörige Tickets“ unverändert nutzbar.

### Produkt
- [ ] Angenommen das Formular „Technischer Support“ ist gewählt, dann ist die Produktsuche mit „optional“ beschriftet und der Haken „Kein Produktbezug“ wird nicht angezeigt.
- [ ] Angenommen kein Produkt ist gewählt, wenn der Mitarbeiter den Vorschlag erstellt, dann wird er ohne Fehlermeldung als Fall ohne Produktbezug erstellt.
- [ ] Angenommen ein Produkt ist gewählt (Konfigurations- oder Bestellproblem zu einem konkreten Produkt), dann fließen Produktdaten und Produktwissen wie bisher in den Vorschlag ein.
- [ ] Angenommen der Mitarbeiter wechselt zu „Freitext“ oder „Reklamation“, dann gilt dort wieder die bisherige Pflichtangabe zum Produkt.

### Lösung bekannt?
- [ ] Angenommen das Formular „Technischer Support“ ist gewählt, dann erscheint die Pflichtfrage „Ist eine mögliche Lösung bereits bekannt?“ mit den Antworten „Ja“ und „Unklar, bitte weitere Informationen erfragen“; nichts ist vorgewählt.
- [ ] Angenommen keine Antwort ist gewählt, wenn der Mitarbeiter abschickt, dann erscheint die Fehlermeldung „Bitte angeben, ob eine Lösung bereits bekannt ist.“ und die Eingaben bleiben erhalten.
- [ ] Angenommen der Mitarbeiter wählt „Ja“, dann erscheint ein Textfeld mit der Überschrift „Zusätzliche Hinweise zur Lösung – optional“ und dem Platzhalter „z. B. Passwort-vergessen-Funktion nutzen“; es darf leer bleiben.
- [ ] Angenommen der Mitarbeiter wählt „Unklar, bitte weitere Informationen erfragen“, dann erscheint ein Textfeld mit der Überschrift „Welche Informationen werden benötigt?“ und dem Platzhalter „z. B. bitte Screenshot senden“; es ist Pflicht (Fehlermeldung: „Bitte angeben, welche Informationen benötigt werden.“).
- [ ] Angenommen der Mitarbeiter wechselt zwischen „Ja“ und „Unklar“, dann bleiben beide Texte im Browser erhalten, gesendet wird nur der Text der gewählten Antwort.
- [ ] Angenommen eines der beiden Textfelder, dann nimmt es höchstens 1.000 Zeichen an.

### Wirkung auf den Vorschlag
- [ ] Angenommen „Ja“ ohne Hinweis, wenn der Vorschlag erstellt wird, dann beschreibt der Entwurf die Lösung aus dem Wissen für den gewählten Kanal.
- [ ] Angenommen „Ja“ mit Hinweis, dann gilt der Hinweis für die KI als geprüfte Angabe des Mitarbeiters und der Entwurf beschreibt diese Lösung, ergänzt um passendes Wissen (z. B. die richtige Shop-Adresse).
- [ ] Angenommen „Ja“ ohne Hinweis und das Wissen enthält keine passende Lösung, dann erfindet der Entwurf keine: Das Ergebnis weist wie bisher auf fehlendes Wissen hin.
- [ ] Angenommen „Unklar“, dann sagt der Entwurf keine Lösung zu, nennt keine vermutete Ursache als Tatsache und bittet den Kunden gezielt um die eingetragenen Informationen.
- [ ] Angenommen ein Vorschlag wurde mit dem Formular erstellt, dann stehen die Antworten im Protokoll der Analyse und die Zeile „Erstellt mit“ nennt das Formular „Technischer Support“.
- [ ] Angenommen das Formular „Technischer Support“ ist gewählt, dann gehen nur die Antworten als feste Sätze an die KI; Regeln stehen weiter im Wissen, nicht im Formular.

## Edge Cases
- **Ticket falsch klassifiziert** (z. B. eigentlich eine Reklamation): Der Mitarbeiter wählt in der Formularwahl ein anderes Formular; die Kategorie des Tickets ändert sich dadurch nicht.
- **Kategorie wird nach dem Öffnen korrigiert:** Beim nächsten Laden der Seite ist das Formular der neuen Kategorie vorgewählt, solange es noch keinen Vorschlag gibt.
- **Formular „Technischer Support“ bei einem Ticket mit Bestellung und DHL-Label-Bedarf:** nicht vorgesehen; dafür wechselt der Mitarbeiter zu „Reklamation“ oder „Freitext“.
- **Fortsetzen nach interner Klärung (PROJ-39):** Die Antworten des Formulars kommen aus dem vorigen Vorschlag.
- **Neuer Vorschlag nach einer Kundenantwort:** Das Formular ist mit den letzten Antworten vorbelegt; liefert der Kunde die erbetenen Informationen, stellt der Mitarbeiter auf „Ja“ um.
- **Validierungsfehler in einem anderen Feld** (z. B. Kanal fehlt): Antwort und Text des Supportformulars bleiben erhalten.
- **Testmodus:** Der Warnhinweis des Testmodus gilt auch für das Hinweisfeld zur Lösung.
- **Ältere Vorschläge ohne Formularangabe:** öffnen wie bisher mit dem Formular der Kategorie bzw. Freitext.

## Technical Requirements (optional)
- Serverseitige Prüfung der Antworten; ausgeblendete Felder werden nicht ausgewertet, auch wenn sie gesendet werden.
- Kein zusätzlicher KI-Aufruf: Das Formular ändert nur, was in den bestehenden Aufruf eingeht.
- Bedienbar per Tastatur, Fehlermeldungen am jeweiligen Feld.

## Open Questions
- [ ] Reicht bei „Ja“ ohne Hinweis das vorhandene Wissen je Kanal aus, oder muss PROJ-91 vor dem Einsatz fertig sein? (Im Betrieb an echten Tickets prüfen.)

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Eigene Spec je Kategorie, Start mit „Technischer Support“ | Vorgabe des Auftraggebers; jedes Formular ist einzeln prüfbar und einführbar | 2026-11-16 |
| Formularwahl als Auswahl „Technischer Support / Reklamation / Freitext“ statt Umschaltknopf, jedes Formular bei jedem Ticket wählbar | Mit jeder weiteren Kategorie kommt ein Eintrag dazu; die Vorklassifikation kann falsch liegen | 2026-11-16 |
| Produkt optional, Haken „Kein Produktbezug“ entfällt in diesem Formular | Supportfälle haben meist keinen Produktbezug; der Haken wäre ein überflüssiger Klick | 2026-11-16 |
| Nur zwei Antworten: „Ja“ und „Unklar, bitte weitere Informationen erfragen“ | Entspricht den zwei tatsächlichen Wegen: Lösung nennen oder nachfragen | 2026-11-16 |
| Hinweis zur Lösung optional, benötigte Informationen Pflicht | Die Lösung kann aus dem Wissen kommen; wonach gefragt werden soll, weiß nur der Mitarbeiter | 2026-11-16 |
| Die Textfelder ersetzen „Zusätzliche Informationen / eigene Einschätzung“ | Vorgabe des Auftraggebers; ein Feld mit klarer Aufgabe statt eines offenen Freitexts | 2026-11-16 |
| Option „DHL-Retourenlabel erzeugen“ entfällt in diesem Formular | Bei Zahlungs-, Login- und Bestellproblemen gibt es keine Rücksendung | 2026-11-16 |
| Keine feste Liste typischer Probleme als Klick-Optionen | Erst im Betrieb zeigt sich, welche Fälle sich wiederholen | 2026-11-16 |

### Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|

---

## Tech Design (Solution Architect)
_To be added by /architecture_

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
