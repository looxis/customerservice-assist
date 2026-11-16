# PROJ-90: Geführtes Formular „Technischer Support“ in „Vorschlag erstellen“

## Status: Approved
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
- [ ] Angenommen der Mitarbeiter wählt „Unklar, bitte weitere Informationen erfragen“, dann erscheint ein Textfeld mit der Überschrift „Welche Informationen werden benötigt? – optional“; es darf leer bleiben (geändert 2026-11-16, vorher Pflicht mit dem Platzhalter „z. B. bitte Screenshot senden“): Um Screenshot, Betriebssystem und Browser bittet der Entwurf immer (TONE-014), die Angabe des Mitarbeiters hat Vorrang und steht an erster Stelle.
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
- [x] Reicht bei „Ja“ ohne Hinweis das vorhandene Wissen je Kanal aus? Am Fachhändler-Ticket 2140446 lieferte es gute Ergebnisse (2026-11-16); weiteres Wissen je Kanal folgt mit PROJ-91.

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
| Aufbau wie beim Reklamationsformular: ein eigener Baustein für die Antworten, der sie prüft, speichert und in feste Sätze für die KI übersetzt | Bewährtes Muster aus PROJ-86; die Regeln bleiben im Wissen, das Formular liefert nur Entscheidungen | 2026-11-16 |
| Eine kleine Liste der geführten Formulare (Schlüssel, Name, zugehörige Kategorie) statt verstreuter Einzelprüfungen | Jede weitere Kategorie wird ein Eintrag plus ein Feld-Baustein; Vorwahl, Formularwahl und Prüfung lesen dieselbe Liste | 2026-11-16 |
| Das vorhandene Feld „formular“ bekommt den dritten Wert für den technischen Support | Gespeicherte Vorschläge und das Reklamationsformular bleiben unverändert lesbar | 2026-11-16 |
| Keine Datenbankänderung: Die Antworten liegen wie bei der Reklamation in den Formularangaben des Vorschlags | Dort stehen sie im Protokoll und dienen der Vorbelegung | 2026-11-16 |
| Produkt optional über eine Ausnahme in der bestehenden Produktprüfung, nur für dieses Formular | Für Freitext, Reklamation und „Frage stellen“ bleibt die Pflichtangabe aus PROJ-42 unverändert | 2026-11-16 |
| Umschalten der Formulare im Browser mit Alpine.js, ohne Neuladen | Wie beim Reklamationsformular; ausgeblendete Zweige werden nicht gesendet, getippte Werte bleiben erhalten | 2026-11-16 |

---

## Tech Design (Solution Architect)

### Überblick
Oberfläche und Backend, keine Datenbankänderung, keine neuen Pakete. Das Formular folgt dem Aufbau des Reklamationsformulars (PROJ-86) und nutzt den Verkaufskanal aus PROJ-89.

### Aufbau der Oberfläche
```
Vorschlag erstellen (Ticket)
+-- Zusammengehörige Tickets (unverändert)
+-- Verkaufskanal, Kundengruppe (PROJ-89)
+-- Produkt
|   +-- bei „Technischer Support“: als optional beschriftet, ohne Haken „Kein Produktbezug“
+-- Bestellangaben (unverändert)
+-- NEU: Formularwahl „Technischer Support | Reklamation | Freitext“
|   (ersetzt den Knopf „Reklamationsformular verwenden“; vorgewählt nach Kategorie)
+-- je nach Wahl genau einer der drei Bereiche:
|   +-- NEU: Technischer Support
|   |   +-- Frage „Ist eine mögliche Lösung bereits bekannt?“ (Ja / Unklar)
|   |   +-- bei Ja: „Zusätzliche Hinweise zur Lösung – optional“
|   |   +-- bei Unklar: „Welche Informationen werden benötigt?“ (Pflicht)
|   +-- Reklamation (vorhanden, unverändert)
|   +-- Freitext mit DHL-Label-Option (vorhanden, unverändert)
+-- Knöpfe (unverändert)
+-- Zeile „Erstellt mit“: zusätzlich der Name des Formulars
```
Der Knopf „Als Reklamation abwickeln“ in der eingeklappten Kopfzeile bleibt.

### Daten
Mit dem Vorschlag gespeichert (kein neuer Speicherort):
- welches Formular verwendet wurde
- die Antwort auf „Lösung bekannt?“ (Ja oder Unklar)
- nur der Text des gewählten Zweigs: Hinweis zur Lösung oder benötigte Informationen (je höchstens 1.000 Zeichen)

Daraus entstehen Protokoll, Vorbelegung beim nächsten Vorschlag und die Zeile „Erstellt mit“.

### Wie die Angaben zur KI kommen
Die App übersetzt die Antworten in wenige feste Sätze, die an derselben Stelle stehen wie bisher die „Zusätzlichen Informationen“:
- **Ja ohne Hinweis:** Es handelt sich um eine technische Anfrage zum gewählten Verkaufskanal; die Lösung ist aus dem Wissen zu beschreiben, nichts erfinden.
- **Ja mit Hinweis:** zusätzlich der Hinweis als geprüfte Angabe des Mitarbeiters, an die sich der Entwurf hält.
- **Unklar:** keine Lösung zusagen, keine vermutete Ursache als Tatsache nennen, den Kunden gezielt um die genannten Informationen bitten.

Der Prompt selbst und das Wissen bleiben unverändert.

### Technische Entscheidungen in Kürze
- **Liste der geführten Formulare:** Technischer Support und Reklamation stehen mit Namen und Kategorie in einer gemeinsamen Liste. Vorwahl, Formularwahl und Serverprüfung lesen sie; spätere Kategorien kommen als weiterer Eintrag dazu.
- **Serverprüfung:** Antwort Pflicht, bei „Unklar“ der Text Pflicht; Felder der nicht gewählten Formulare und Zweige werden ignoriert.
- **Produkt:** Nur bei diesem Formular gilt „kein Produkt gewählt“ als Fall ohne Produktbezug.
- **„Frage stellen“** bleibt unberührt; der gemeinsame obere Formularteil zeigt dort weiter den Haken „Kein Produktbezug“.

### Betroffene Bereiche
- Vorschlagsformular am Ticket (Formularwahl, neuer Feld-Baustein, Zeile „Erstellt mit“)
- gemeinsamer Baustein „Kanal, Kundengruppe, Produkt“ (Produkt optional je Formular)
- Eingabeprüfung des Vorschlags und Produktprüfung
- Ticketseite (Vorwahl des Formulars aus der Kategorie)
- Übergabe an die Analyse (feste Sätze, gespeicherte Angaben)

### Abhängigkeiten
Keine neuen Pakete.

### Risiken
- Die Formularwahl ersetzt den Umschaltknopf des getesteten Reklamationsformulars; dessen Tests werden angepasst und laufen mit.
- Ob „Ja ohne Hinweis“ gute Entwürfe liefert, hängt vom Wissen je Kanal ab (PROJ-91).

## Implementation Notes (2026-11-16)
Oberfläche und Backend in einem Schritt umgesetzt.

- **Antworten:** neue Klasse `App\Analysis\SupportDecision` (Formularschlüssel `support`, Felder `support_loesung` mit `ja`/`unklar`, `support_hinweis`, `support_info`): prüft, speichert nur den gewählten Zweig und liefert die festen Sätze für die KI.
- **Liste der Formulare:** `App\Analysis\GuidedForms` (Technischer Support, Reklamation, Freitext) mit Vorwahl je Kategorie; `TicketController::complaintForm()` liefert das vorgewählte Formular.
- **Formular:** Auswahl „Formular“ (Radioknöpfe, an die Alpine-Eigenschaft `mode` gebunden) ersetzt den Umschaltknopf; neuer Baustein `x-analysis.support-fields`. Die Zeile „Erstellt mit“ nennt ein geführtes Formular beim Namen.
- **Produkt optional:** `x-analysis.scope-fields` blendet im Supportformular Pflichtstern, Haken „Kein Produktbezug“ und die zugehörigen Hinweise aus. Der Server wertet „kein Produkt gewählt“ als Fall ohne Produktbezug (`AnalyzeTicketRequest::relaxProductChoice()`); die Rückfrage vor dem Abschicken entfällt dort.
- **DHL-Label:** wird im Supportformular nie angehängt, auch wenn eine Label-ID mitgesendet wird.
- **Tests:** `tests/Feature/PROJ-90-SupportFormTest.php` (23 Tests); drei Tests des Reklamationsformulars an die Formularwahl angepasst.

Abweichung von der Spec: keine.

## Änderung 2026-11-16: „Unklar“ geht dem Wissen vor
Anlass: Test an Ticket 2140446 (Fachhändler kann sich nicht anmelden). Mit „Unklar“ und „bitte screenshot“ lieferte der Entwurf dieselbe Anleitung wie mit „Ja“ und bat erst am Ende um den Screenshot. Ursache war nicht der Wissensabruf: PLAYBOOK-032 schreibt für die erste Antwort „immer“ Shop-Adresse, Logo und Anmeldung vor, und die Sätze des Formulars widersprachen dem nicht deutlich genug. Die festen Sätze für „Unklar“ sagen jetzt ausdrücklich, dass die Entscheidung des Mitarbeiters dem Wissen vorgeht, der Entwurf nur aus der Rückfrage besteht und keine Lösungsschritte beschreibt. Die Wirkung auf den Entwurf ist an einem echten Ticket zu prüfen.

## Änderung 2026-11-16 (2): Standard-Rückfrage bei „Unklar“, Feld optional
Vorgabe des Auftraggebers nach dem Test: Bei „Unklar“ bittet die Antwort auch ohne Eintrag immer um einen Screenshot (mit sichtbarer Internetadresse), Betriebssystem und Browser jeweils mit Version; was der Mitarbeiter einträgt, hat Vorrang.
- Die Standard-Rückfrage steht als Wissen in `TONE-014` (alle Kanäle). Die festen Sätze für „Unklar“ enthalten die Wendung „unklaren technischen Problem“, die dieses Dokument abruft; bei „Ja“ wird es nicht geladen (Test mit eingeschaltetem gezieltem Abruf).
- Das Feld „Welche Informationen werden benötigt?“ ist optional, mit dem Hinweis, dass Screenshot, Betriebssystem und Browser immer erfragt werden. Neuer Platzhalter: „z. B. bitte die Fehlermeldung im Wortlaut nennen“.
- Die festen Sätze nennen keine Beispielthemen mehr (Zahlung, Passwort, Anmeldung): Diese Wörter hätten bei jedem Supportticket fremdes Wissen abgerufen, weil die Angaben des Mitarbeiters für den Abruf mitzählen.
- PLAYBOOK-032: „immer“ bei der ersten Antwort entfernt; die erste Antwort entfällt, wenn der Mitarbeiter zuerst Informationen erfragen will.

## QA Test Results

**Tested:** 2026-11-16
**App URL:** http://192.168.1.109:8081
**Tester:** QA Engineer (AI)

Geprüft mit der Testsuite (`tests/Feature/PROJ-90-SupportFormTest.php`, 23 Tests) und einem Abruf der echten Seite von Ticket 2140446. Der Auftraggeber hat beide Zweige an diesem Ticket ausprobiert. Nicht geprüft: Bedienung im Browser durch die QA, verschiedene Browser, schmale Bildschirme.

### Acceptance Criteria Status

#### Formularwahl
- [x] Kategorie Technischer Support wählt das Formular vor (Test und Ticket 2140446)
- [x] Auswahl „Formular“ mit drei Einträgen ersetzt den Umschaltknopf, bei jedem Ticket
- [x] Reklamation wählt „Reklamation“ vor, alles andere „Freitext“
- [x] Gesendet werden nur die Angaben des gewählten Formulars (Server ignoriert die übrigen)
- [x] Früherer Vorschlag: Formular und Antworten vorbelegt

#### Felder
- [x] Reihenfolge und Inhalte wie beschrieben; Freitextfeld, Schreibhilfe und DHL-Label-Option nur im Freitext
- [x] Bestellangaben und zusammengehörige Tickets unverändert

#### Produkt
- [x] „(optional)“, kein Haken „Kein Produktbezug“ im Supportformular
- [x] Ohne Produkt wird der Vorschlag als Fall ohne Produktbezug erstellt
- [x] Mit Produkt fließt es wie bisher ein
- [x] Freitext und Reklamation verlangen das Produkt weiter

#### Lösung bekannt?
- [x] Pflichtfrage, nichts vorgewählt, Fehlermeldung ohne Antwort
- [x] „Ja“: optionaler Hinweis; „Unklar“: optionales Feld, Standard-Rückfrage aus TONE-014 (27 Tests nach Änderung 2)
- [x] Nur der Text der gewählten Antwort wird gesendet und gespeichert
- [x] Höchstens 1.000 Zeichen

#### Wirkung auf den Vorschlag
- [x] „Ja“ ohne Hinweis: Lösung aus dem Wissen des Kanals (vom Auftraggeber am Fachhändler-Ticket bestätigt)
- [x] „Ja“ mit Hinweis: Hinweis geht als geprüfte Angabe an die KI
- [x] Kein Erfinden ohne passendes Wissen (als Anweisung an die KI; nicht an einem echten Fall geprüft)
- [x] „Unklar“ sagt keine Lösung zu und fragt gezielt nach (BUG-1 behoben und vom Auftraggeber nachgeprüft)
- [x] Antworten im Protokoll, „Erstellt mit“ nennt das Formular
- [x] Nur feste Sätze gehen an die KI

### Security Audit Results
- [x] Eingabe: Formularschlüssel und Antworten nur aus festen Listen, Texte längenbegrenzt
- [x] Ausgabe: Texte werden von Blade maskiert
- [x] CSRF: bestehendes Formular mit `@csrf`
- [x] Ein mitgesendetes Retourenlabel wird im Supportformular nie angehängt

### Regression
- [x] Reklamationsformular (PROJ-86): 32 Tests grün, drei an die Formularwahl angepasst
- Gesamtsuite: 2774 von 2778 grün; die zwei Fehlschläge betreffen die Seitenleiste (Version, „Wissen zur Überprüfung“) und stammen aus dem Commit zu PROJ-41, nicht aus diesem Feature.

### Bugs Found

#### BUG-1: „Unklar“ lieferte dieselbe Anleitung wie „Ja“
- **Severity:** High
- **Steps to Reproduce:**
  1. Ticket 2140446 öffnen, Formular „Technischer Support“, Kanal fachhaendler.looxis.de.
  2. „Unklar, bitte weitere Informationen erfragen“ wählen, „bitte screenshot“ eintragen, Vorschlag erstellen.
  3. Erwartet: Der Entwurf sagt, dass das Problem noch unklar ist, und bittet um einen Screenshot.
  4. Tatsächlich: Der Entwurf beschreibt die Anmeldung im Fachhändler-Shop wie bei „Ja“ und bittet erst am Ende um den Screenshot.
- **Status:** behoben am 2026-11-16; vom Auftraggeber an Ticket 2140446 nachgeprüft, die Rückfrage kommt wie erwartet.

### Summary
- **Acceptance Criteria:** 21 von 21 erfüllt
- **Bugs Found:** 1 (High, behoben und nachgeprüft)
- **Security:** Pass
- **Production Ready:** YES; vom Auftraggeber an Ticket 2140446 geprüft und am 2026-11-16 freigegeben (beide Zweige, Standard-Rückfrage bei „Unklar“)

## Deployment
_To be added by /deploy_
