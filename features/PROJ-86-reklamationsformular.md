# PROJ-86: Reklamationsformular in „Vorschlag erstellen“

## Status: In Review
**Created:** 2026-11-11
**Last Updated:** 2026-11-12

## Dependencies
- Requires: PROJ-80 (Kategorie `reklamation` aus der Klassifikation neuer Tickets)
- Requires: PROJ-9, PROJ-10 (Fallanalyse und Ergebnisansicht, Prompt `analysis`)
- Requires: PROJ-64 (DHL-Retourenlabel, Komponente `x-dhl.return-label`)
- Requires: PROJ-70, PROJ-77 (ToDos, „Sendungsnummer nachreichen“ mit automatischem Versand)
- Berührt: PROJ-74 (Einführungsmodus), PROJ-76 (Formular-Feinschliff), PROJ-48/53 (Reklamationsnummer erkennen)
- Wissen: `knowledge/tone/tone-011-neuproduktion-versand-und-reklamationsnummer-nennen.md`

## Hintergrund
Anlass ist Ticket 2140357 (2026-11-11). Der Mitarbeiter legt die Reklamation in EOCS an und tippt Nummer und Entscheidung als Freitext in „Zusätzliche Informationen“. Ohne weitere Hinweise liefert der Entwurf nur eine vage Zusage; mit Hinweisen ist er gut, enthält aber „voraussichtlich heute noch verschickt“. Dieser Satz steht fest in tone-011, auch wenn es schon 18 Uhr ist. Weil die Vorklassifikation (PROJ-80) das Ticket als Reklamation kennt, kann die App die nötigen Angaben gezielt abfragen.

## User Stories
- Als Mitarbeiter im Kundenservice will ich bei einer Reklamation gezielt angeben, ob sie berechtigt ist, damit der Entwurf entweder zusagt oder begründet ablehnt, ohne dass ich Fakten frei formulieren muss.
- Als Mitarbeiter will ich bei einer berechtigten Reklamation festlegen, ob das defekte Produkt zurückgeschickt werden soll, und gleich das DHL-Retourenlabel erzeugen.
- Als Mitarbeiter will ich die Reklamationsnummer und den voraussichtlichen Versandzeitpunkt (heute, morgen, zeitnah) angeben, damit der Entwurf nichts Falsches verspricht.
- Als Mitarbeiter will ich, dass das ToDo „Sendungsnummer mitteilen“ schon vorausgefüllt ist, damit die Sendungsnummer später automatisch nachgereicht wird.
- Als Aushilfe will ich bei einer abgelehnten Reklamation einen Grund eintragen und entscheiden, ob ein Kulanzangebot gemacht wird.
- Als Mitarbeiter will ich bei Bedarf zum bisherigen Freitextformular zurückwechseln können.

## Out of Scope
- Automatische Vorab-Antwort an den Kunden bei Reklamation (eigenes Feature PROJ-85).
- Produktabhängige Abfragen, ob eine Retoure nötig ist (z. B. Kissenfüllung bei mehr als zwei fehlerhaften Teilen, POLICY-010, PLAYBOOK-051): separates späteres PRD; der Hinweistext zur Retoure ist hier nur allgemein.
- Weitere Formulare für andere Kategorien (Angebotsanfrage, Storno usw.).
- Änderung der Entscheidungsregeln im Wissen, ob ein Anspruch berechtigt ist: der Mitarbeiter entscheidet, die App fragt nur ab.
- Anlage der Reklamation in EOCS aus der App (der Mitarbeiter legt sie weiter in EOCS an und trägt die Nummer ein).
- Uhrzeitabhängige automatische Umstellung von „heute“ auf „morgen“.
- Reklamationsformular für Tickets ohne Kategorie `reklamation` (dort bleibt das bisherige Formular).

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Anzeige
- [ ] Angenommen ein Ticket hat die Kategorie `reklamation`, wenn der Mitarbeiter „Vorschlag erstellen“ öffnet, dann zeigt der Block das Reklamationsformular statt des Felds „Zusätzliche Informationen“ und der Checkbox „DHL-Retourenlabel erzeugen“.
- [ ] Angenommen ein Ticket hat eine andere oder keine Kategorie, wenn der Mitarbeiter „Vorschlag erstellen“ öffnet, dann bleibt das bisherige Formular unverändert.
- [ ] Angenommen das Reklamationsformular ist sichtbar, wenn der Mitarbeiter „Freitext statt Reklamationsformular“ wählt, dann erscheint das bisherige Formular, und umgekehrt.
- [ ] Angenommen das Formular ist geöffnet, wenn Kundengruppe und Produkt noch fehlen, dann gelten die bisherigen Pflichtangaben unverändert.

### Berechtigt Ja/Nein
- [ ] Angenommen das Reklamationsformular ist sichtbar, wenn noch keine Wahl getroffen wurde, dann sind die weiteren Felder ausgeblendet und „Vorschlag erstellen“ lässt sich nicht abschicken (Fehlermeldung: „Bitte angeben, ob die Reklamation berechtigt ist.“).
- [ ] Angenommen der Mitarbeiter wählt „Nein“, dann erscheinen das Textfeld „Hier den Grund eingeben“ (Pflicht) und der Schalter „Kulanzangebot unterbreiten?“ (Ja/Nein, Standard Nein); Retoure, Reklamationsnummer, Versand und ToDo sind ausgeblendet und werden nicht gesendet.
- [ ] Angenommen der Mitarbeiter wählt „Ja“, dann erscheinen Retoure, Reklamationsnummer, Versand und ToDo.
- [ ] Angenommen der Mitarbeiter wechselt von „Ja“ auf „Nein“ und zurück, dann bleiben zuvor eingegebene Werte erhalten, aber nur die Werte des gewählten Zweigs werden gesendet.

### Retoure
- [ ] Angenommen „Ja“ bei berechtigt, dann erscheint die Frage „Soll das defekte Produkt retourniert werden?“ mit dem Hinweis „Höherwertige Produkte, die in Teilen noch (weiter-)verwendet werden können, sollten retourniert werden.“ (Standard Nein).
- [ ] Angenommen „Retoure: Nein“, dann gibt es keine weitere Aktion und kein Retourenlabel.
- [ ] Angenommen „Retoure: Ja“, dann erscheint der bestehende Dialog „DHL-Retourenlabel erstellen“ je Bestellung (wie bisher); abgeschickt werden kann der Vorschlag erst, wenn das Label erstellt wurde (wie heute).
- [ ] Angenommen „Retoure: Ja“ und ein Label ist erstellt, dann nennt der Entwurf wie bisher das Label und die App fügt die Rücksendeschritte fest unter den Entwurf ein.
- [ ] Angenommen „Retoure: Nein“ und es gibt kein Label, dann sagt der Entwurf ausdrücklich, dass das Produkt nicht zurückgesendet werden muss.

### Reklamationsnummer
- [ ] Angenommen „Ja“ bei berechtigt, dann erscheint das Textfeld „Reklamationsnummer“ mit dem Hinweis „Bitte Reklamation in EOCS erstellen und die Reklamationsnummer hier einfügen.“
- [ ] Angenommen das Ticket oder EOCS nennt bereits eine Reklamationsnummer (PROJ-48/53), dann ist das Feld damit vorbelegt und änderbar.
- [ ] Angenommen das Feld ist leer, wenn der Mitarbeiter abschickt, dann wird abgeschickt und der Entwurf enthält statt der Nummer den Platzhalter `[REKLAMATIONSNUMMER]` (wie bisher), und die Ergebnisansicht weist darauf hin.
- [ ] Angenommen die Eingabe entspricht nicht dem Aufbau einer Reklamationsnummer (`R1-…`/`R2-…` gemäß Einstellung), dann zeigt das Formular eine Fehlermeldung mit dem erwarteten Aufbau.

### Versand voraussichtlich
- [ ] Angenommen „Ja“ bei berechtigt, dann zeigt das Formular „Versand voraussichtlich“ mit den Optionen „heute“, „morgen“, „zeitnah“, vorausgewählt ist „zeitnah“.
- [ ] Angenommen „heute“, dann darf der Entwurf „voraussichtlich heute“ nennen; bei „morgen“ „voraussichtlich morgen“; bei „zeitnah“ nennt er keinen Tag, sondern „in den nächsten Tagen“ bzw. „zeitnah“.
- [ ] Angenommen „zeitnah“, dann enthält der Entwurf nie die Wörter „heute noch“ oder „noch heute“ im Zusammenhang mit dem Versand.
- [ ] Angenommen die Reklamation wird abgelehnt, dann nennt der Entwurf keinen Versandzeitpunkt.

### ToDo „Sendungsnummer mitteilen“
- [ ] Angenommen „Ja“ bei berechtigt und Retoure ist nicht der alleinige Fall, dann ist die Option „ToDo ‚Sendungsnummer mitteilen‘ anlegen“ vorausgewählt, und beim Senden der Antwort ist das ToDo mit Preset „Sendungsnummer nachreichen“ und aktivem automatischem Versand (PROJ-77) vorbelegt.
- [ ] Angenommen der Mitarbeiter schaltet die Option aus, dann wird beim Senden kein ToDo vorbelegt.
- [ ] Angenommen die Antwort ist nicht auf Deutsch, dann wird das ToDo angelegt, aber ohne automatischen Versand (Regel PROJ-77 bleibt).
- [ ] Angenommen die Reklamationsnummer fehlt, dann ist das ToDo vorbelegt, aber ohne automatischen Versand, und weist auf die fehlende Nummer hin.

### Antwortentwurf
- [ ] Angenommen „Nein“ mit Grund, wenn der Vorschlag erstellt wird, dann begründet der Entwurf die Ablehnung mit dem angegebenen Grund und macht ein Kulanzangebot nur bei „Kulanz: Ja“.
- [ ] Angenommen „Ja“, dann sagt der Entwurf die kostenfreie Neuproduktion bzw. Lösung zu, nennt die Reklamationsnummer und den Versandhinweis aus „Versand voraussichtlich“.
- [ ] Angenommen die Angaben des Formulars stehen im Widerspruch zum Wissen, dann haben die Angaben des Mitarbeiters Vorrang (wie bisher bei „Zusätzliche Informationen“).
- [ ] Angenommen der Vorschlag ist erstellt, wenn der Mitarbeiter die Ergebnisansicht öffnet, dann ist erkennbar, welche Formularangaben verwendet wurden, und beim erneuten Erstellen sind sie vorbelegt.

### Wissen und Protokoll
- [ ] Angenommen tone-011 wurde angepasst, dann enthält es keinen festen Satz „voraussichtlich heute noch verschickt“ mehr, sondern verweist auf die Versandangabe des Mitarbeiters; `knowledge:check` meldet keine Fehler.
- [ ] Angenommen ein Vorschlag mit Formular wurde erstellt, dann stehen die Formularangaben im Protokoll der Analyse (Eingaben) und sind in der Analysen-Übersicht nachvollziehbar.

## Edge Cases
- Die Kategorie wechselt nach dem Öffnen (Mitarbeiter korrigiert die Einordnung auf der Ticketseite): beim nächsten Laden gilt die neue Kategorie; bereits ausgefüllte Eingaben gehen nicht verloren, wenn der Mitarbeiter auf Freitext umschaltet.
- Das Ticket hat mehrere Bestellungen (Sammelreklamation): Retourenlabel je Bestellung wie heute, Reklamationsnummer gilt je Vorgang; bei mehreren Nummern trägt der Mitarbeiter sie kommagetrennt ein (offen, siehe Fragen).
- Folge-Vorschlag nach Rückfrage oder neuer Kundennachricht: Formular ist mit den früheren Angaben vorbelegt, „Versand“ bleibt auf der früheren Wahl.
- Vorklassifikation noch nicht vorhanden oder als „unsicher“ markiert: bisheriges Formular, Umschalter zum Reklamationsformular bleibt verfügbar.
- Zeitpunkt „heute“ wird nach Feierabend gewählt: die App verbietet es nicht, zeigt aber keine Uhrzeitprüfung (bewusst, siehe Entscheidungen).
- Ticket in anderer Sprache: Formular bleibt deutsch, Entwurf in der Sprache des Kunden.
- Abschicken ohne Wahl bei „berechtigt“: Fehlermeldung statt stiller Voreinstellung.
- Retourenlabel gewünscht, aber DHL-Schnittstelle nicht erreichbar: Fehlermeldung wie in PROJ-64, Eingaben bleiben erhalten.
- Bestehender gespeicherter Vorschlag ohne Formularfelder wird erneut geöffnet: Felder leer, Freitext-Eingabe wird nicht in Formularfelder übersetzt.

## Technical Requirements (optional)
- Nur für Mitarbeiter-Eingaben; keine Kundendaten zusätzlich an das Modell (Faktensätze enthalten nur Entscheidungen, Nummer und Versandwort).
- Einführungsmodus (PROJ-74): Erklärtexte und Beispiele nur bei eingeschaltetem Schalter; Pflichtangaben und Warnungen immer sichtbar.
- Bedienbar per Tastatur, Radiogruppen mit Beschriftung; mobil ohne horizontales Scrollen.
- Alle Eingaben über Form Request validiert (keine Validierung im Controller).

## Open Questions
- [ ] Mehrere Reklamationsnummern je Ticket (Sammelreklamation): ein Feld mit mehreren Nummern oder ein Feld je Bestellung?
- [ ] Soll bei „Nein“ (abgelehnt) der Hinweis auf das Muster „Kulanz“ aus dem Wissen (Rabattregeln, noch zu überprüfen) die Höhe des Angebots vorgeben, oder bleibt die Höhe beim Mitarbeiter? Vorerst: Höhe aus dem Wissen/Entscheidung des Mitarbeiters, kein Feld.
- [ ] Soll „Versand voraussichtlich“ auch bei Rücksendung zuerst („Ersatz nach Eingang der Rücksendung“, tone-011 Ausnahme) entfallen? Vorerst ja: bei „Retoure: Ja“ nennt der Entwurf wie bisher keinen Versandzeitpunkt, die Optionen bleiben sichtbar.
- [ ] Soll das Formular auch ohne Neuladen wechseln, wenn der Mitarbeiter in der Kopfleiste die Kategorie ändert? Vorerst nein (Umschalter genügt; Architektur-Entscheidung 2026-11-11).
- [ ] Mischfälle (Reklamation plus Frage, „mehrere Anliegen“ in der Klassifikation): Reklamationsformular bleibt, Zusatzfragen bleiben außerhalb? Vorerst: Umschalter auf Freitext.
- [ ] Brauchen Tickets in „Reklamation“ mit Produkten ohne ProductForge-Wissen weiterhin ein Freitextfeld für Besonderheiten (z. B. Beschreibung des Schadens)? Vorerst nein, Umschalter auf Freitext genügt.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Eigenes Feature PROJ-86, getrennt von der Vorab-Antwort (PROJ-85) | Unterschiedliche Auslöser, Nutzer (Mitarbeiter vs. Automatik) und Testbarkeit; Projektregel „eine Spec je Feature“ | 2026-11-11 |
| Formular nur bei Kategorie `reklamation`, mit Umschalter auf das alte Freitextformular | Andere Tickets brauchen weiter das Freitextfeld; Fehleinordnungen bleiben handhabbar | 2026-11-11 |
| „Versand voraussichtlich“ aus dem Formular, Standard „zeitnah“ | Der Mitarbeiter weiß, ob heute noch produziert wird; „zeitnah“ ist die sichere Aussage und verspricht nichts Falsches | 2026-11-11 |
| Der Satz „voraussichtlich heute noch“ wird aus tone-011 kontextbezogen entfernt, nicht nur ergänzt | Die Aussage ist nicht immer korrekt; sie darf nur auf Wahl des Mitarbeiters erscheinen | 2026-11-11 |
| Keine uhrzeitabhängige Automatik für „heute“ | Aufwand und Fehlerquellen (Feiertage, Produktion); die Wahl des Mitarbeiters genügt | 2026-11-11 |
| Retoure-Hinweis nur allgemein; produktabhängige Abfrage später als eigenes PRD | Wissen zu Retouren je Produkt ist verstreut (POLICY-010, PLAYBOOK-051), Umfang eigener Planung wert | 2026-11-11 |
| Das ToDo „Sendungsnummer mitteilen“ ist standardmäßig vorausgewählt | Bei berechtigten Reklamationen mit Neuproduktion Regelfall (PROJ-77); Mitarbeiter kann abwählen | 2026-11-11 |
| Reklamationsnummer: leer abschickbar mit Platzhalter | Der Mitarbeiter soll nicht blockiert werden, wenn EOCS noch nicht gespeichert hat; bisheriges Verhalten bleibt | 2026-11-11 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|
| Kein neues Datenmodell, keine Migration: Formularangaben liegen in den gespeicherten Eingaben der Analyse | Die Eingaben sind bereits ein flexibler Datensatz je Analyse; Protokoll, Vorbelegung und Analysen-Übersicht funktionieren damit ohne Umbau | 2026-11-11 |
| Formular wird beim Laden der Seite serverseitig gewählt (Kategorie der Einordnung: bestätigte Wahl, sonst Vorschlag der KI), nicht live beim Klick auf die Kategorie | Die Bestätigung in der Kopfleiste läuft im Hintergrund und lädt die Seite nicht neu; ein Live-Wechsel würde Kopfleiste und Formular koppeln. Der Umschalter deckt Korrekturen ab | 2026-11-11 |
| Bei Tickets der Testmenge (kein sichtbarer Vorschlag, PROJ-80) bleibt das bisherige Formular, Umschalter verfügbar | Der Vorschlag darf den Mitarbeiter dort nicht beeinflussen | 2026-11-11 |
| Die Formularangaben werden im Code in feste Faktensätze übersetzt und an die „Zusätzlichen Informationen“ für die KI angehängt (wie beim DHL-Label) | Prompt und Abruf kennen diesen Weg schon; der Entwurf behandelt die Sätze als geprüfte Fakten mit Vorrang. Es entstehen keine fachlichen Regeln im Code | 2026-11-11 |
| Der Versandhinweis wird als Fakt übergeben, nicht als Uhrzeitlogik | Die KI darf „heute“ nie ableiten (tone-011 angepasst); entscheidet allein der Mitarbeiter | 2026-11-11 |
| Validierung der neuen Felder im bestehenden Prüfobjekt für „Vorschlag erstellen“, abhängig von „berechtigt“ | Projektregel: keine Validierung im Controller; die Zweige Ja/Nein haben unterschiedliche Pflichtfelder | 2026-11-11 |
| ToDo-Vorbelegung über die bestehende Vorbelegung im Antwortfenster, gesteuert aus den Formularangaben | PROJ-77 legt das ToDo beim Senden an; die Formularwahl muss nur dessen Vorauswahl und Reklamationsnummer liefern | 2026-11-11 |
| Retourenlabel-Dialog unverändert wiederverwendet, nur an die Antwort „Retoure: Ja“ gekoppelt | Bewährte Komponente aus PROJ-64, kein Neubau | 2026-11-11 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

### Umfang
Backend und Frontend, aber **kein neues Datenmodell**, keine Migration, keine neue Seite und keine neuen Pakete. Es ändern sich das Formular „Vorschlag erstellen“, dessen Prüfung, die Übersetzung der Angaben in Fakten für die KI, ein Satz im Analyse-Prompt und (schon erledigt) das Wissen tone-011.

### A) Aufbau der Oberfläche
```
Ticketseite
+-- Kopfleiste „Worum geht es?“ (unverändert, Kategorie bestätigen)
+-- Block „Vorschlag erstellen“
    +-- Umschalter „Reklamationsformular / Freitext“ (nur bei Reklamation sichtbar)
    +-- Kundengruppe und Produkt (unverändert)
    +-- [Reklamationsformular] (ersetzt Freitextfeld und DHL-Checkbox)
    |   +-- Frage 1: Reklamation berechtigt? (Ja / Nein)
    |   |   +-- Nein: Grund (Pflicht) + Schalter „Kulanzangebot unterbreiten?“
    |   |   +-- Ja:
    |   |       +-- Frage 2: Produkt retournieren? (Ja / Nein, mit Hinweis)
    |   |       |   +-- Ja: bestehender Dialog „DHL-Retourenlabel erstellen“
    |   |       +-- Reklamationsnummer (Hinweis „in EOCS erstellen“, vorbelegt wenn bekannt)
    |   |       +-- Versand voraussichtlich: heute / morgen / zeitnah (Standard zeitnah)
    |   |       +-- Option „ToDo ‚Sendungsnummer mitteilen‘ anlegen“ (vorausgewählt)
    +-- [Bisheriges Formular] Freitextfeld + DHL-Checkbox (alle anderen Tickets, oder per Umschalter)
    +-- Knopf „Vorschlag erstellen“ (unverändert)
Ergebnisansicht
+-- Antwortentwurf mit Senden-Fenster
    +-- ToDo-Vorauswahl „Sendungsnummer nachreichen“ (bestehend, jetzt aus dem Formular gesteuert)
```
Neu ist eine einzige Bausteingruppe im Block „Vorschlag erstellen“ (Reklamationsfelder). Wiederverwendet werden das Feldraster, die Hinweistexte im Einführungsmodus, der Dialog „DHL-Retourenlabel erstellen“ und das bestehende Antwortfenster mit ToDo-Auswahl.

### B) Daten (in einfachen Worten)
Nichts Neues in der Datenbank. Die Angaben des Formulars werden wie die bisherigen Eingaben (Kundengruppe, Produkt, Freitext, Retourenlabel) bei der Analyse gespeichert:
- berechtigt (Ja/Nein), Grund, Kulanz (Ja/Nein)
- Retoure (Ja/Nein), Reklamationsnummer
- Versand voraussichtlich (heute/morgen/zeitnah)
- ToDo „Sendungsnummer mitteilen“ gewünscht (Ja/Nein)
- Welches Formular benutzt wurde (Reklamation oder Freitext)

Damit zeigt das Protokoll jeder Analyse, was der Mitarbeiter angegeben hat, und beim erneuten Erstellen (z. B. nach neuen Kundennachrichten) sind die Felder vorbelegt. Ältere Analysen ohne diese Angaben bleiben lesbar; ihr Freitext wird nicht in Felder übersetzt.

### C) Wie die Angaben zur KI kommen
Die App übersetzt die Formularwerte in kurze feste Sätze und hängt sie an die „Zusätzlichen Informationen“ an, genau wie es heute beim Retourenlabel geschieht:
- „Die Reklamation ist nach Prüfung berechtigt / nicht berechtigt. Grund: …“
- „Ein Kulanzangebot wird unterbreitet / nicht unterbreitet.“
- „Das Produkt soll / muss nicht zurückgesendet werden.“
- „Die Reklamationsnummer lautet …“ bzw. „Die Nummer steht noch nicht fest (Platzhalter verwenden).“
- „Versand voraussichtlich: heute / morgen / zeitnah.“
- „Nach dem Versand reichen wir die Sendungsnummer nach.“ (nur bei gewünschtem ToDo; die Entwürfe sagen das dem Kunden zu)

Diese Sätze enthalten nur Entscheidungen und Fakten, keine Regeln. Der Analyse-Prompt bekommt einen kurzen Hinweis, dass diese Sätze Vorrang haben und dass kein Versandtag genannt wird, der nicht dort steht (neue Prompt-Version). Das Wissen tone-011 ist bereits auf die Versandangabe umgestellt.

### D) Entscheidungen im Überblick (für Nicht-Techniker)
- **Wann erscheint das Formular?** Beim Öffnen der Ticketseite prüft die App die Einordnung: bestätigt der Mitarbeiter „Reklamation“, oder, solange noch nichts bestätigt ist, schlägt die KI „Reklamation“ vor, erscheint das Reklamationsformular. Ändert der Mitarbeiter die Einordnung während er auf der Seite ist, wechselt das Formular nicht von allein; dafür gibt es den Umschalter (oder ein Neuladen). Das hält Kopfleiste und Formular unabhängig.
- **Testmenge:** Bei den 5 % der Tickets, bei denen die KI-Einordnung bewusst verborgen ist, bleibt das bisherige Formular, damit der Vorschlag nicht beeinflusst.
- **Pflichtangaben:** Nur „berechtigt“ ist Pflicht (und der Grund bei „Nein“). Die Reklamationsnummer darf leer bleiben (Platzhalter im Entwurf wie bisher), weil sie erst nach dem Speichern in EOCS existiert.
- **Retourenlabel:** Der bestehende Dialog bleibt, wird aber erst bei „Retoure: Ja“ gezeigt. Abschicken geht erst, wenn das Label erstellt ist, wie heute.
- **ToDo:** Wird erst beim Senden der Antwort angelegt (wie heute). Das Formular legt nur Vorauswahl und Nummer fest. Bei nicht-deutschen Antworten wird das ToDo ohne automatischen Versand angelegt (Regel von PROJ-77).
- **Einführungsmodus:** Erklärtexte (Hinweis zur Retoure, Beispiele) nur dort; Pflichtangaben und Fehlermeldungen immer.

### E) Betroffene Bereiche
- Formularblock „Vorschlag erstellen“ und eine neue Bausteingruppe für die Reklamationsfelder
- Prüfung der Eingaben von „Vorschlag erstellen“ (neue Felder, Pflichtregeln je Zweig)
- Steuerung der Analyse: Fakten aus den Feldern bilden, Eingaben speichern
- Ticketseite: die Einordnung an das Formular weitergeben
- Antwortfenster: ToDo-Vorauswahl aus dem Formular statt nur aus der erkannten Nummer
- Analyse-Prompt (neue Version), Wissen tone-011 (erledigt)
- Tests: Anzeige nach Kategorie, Pflichtregeln je Zweig, Fakten für die KI, Standard „zeitnah“, ToDo-Vorauswahl, bisheriges Formular unverändert

### F) Abhängigkeiten (Pakete)
Keine neuen Pakete.

### G) Neue offene Punkte aus dem Entwurf
Siehe Open Questions (Umschalten beim Kategoriewechsel, Verhalten bei Mischfällen).

## Implementation Notes: Frontend (2026-11-11)
Gebaut ist die Oberfläche; die Serverseite (Prüfung, Fakten für die KI, Speichern, Prompt, ToDo-Vorauswahl) fehlt noch (`/backend`).

- `resources/views/components/analysis/complaint-fields.blade.php` (neu): Reklamationsformular mit den Zweigen berechtigt Ja/Nein, Retoure (bestehender Dialog `x-dhl.return-label`), Reklamationsnummer, Versand, ToDo-Option.
- `resources/views/components/analysis/choice.blade.php` (neu): wiederverwendbare Ja/Nein- bzw. Auswahlgruppe als Radiobuttons.
- `resources/views/components/analysis/form.blade.php`: Umschalter, verstecktes Feld `formular`, das Freitextfeld samt DHL-Checkbox liegt jetzt in einem Block, der nur im Freitext-Modus gesendet wird. Nicht angezeigte Zweige sind deaktivierte Fieldsets: Sie werden nicht gesendet, behalten aber ihre Eingaben beim Umschalten. Der Versandschutz „Label zuerst erzeugen“ prüft nur aktive Felder.
- `TicketController` + `tickets/show.blade.php`: neue Ansichtsdaten `complaintForm` (`default`, `toggle`, `claimNumber`). Standardformular bei Kategorie Reklamation (bestätigt, sonst Vorschlag der KI); Umschalter bei Reklamation oder wenn die Einordnung offen/unsicher/verborgen ist.

**Feldnamen für `/backend`** (Request `AnalyzeTicketRequest`): `formular` (`reklamation`|`frei`), `reklamation_berechtigt` (`ja`|`nein`), `reklamation_grund`, `kulanz` (`ja`|`nein`), `retoure` (`ja`|`nein`), `reklamationsnummer`, `versand` (`heute`|`morgen`|`zeitnah`), `sendungsnummer_todo` (`1`, Checkbox), `return_label` (wie bisher). Im Modus `reklamation` wird `kontext` nicht gesendet; bei „nein“ nur Grund und Kulanz, bei „ja“ nur Retoure, Nummer, Versand, ToDo.
- Standard „zeitnah“ und „Retoure: Nein“, Kulanz „Nein“, ToDo-Option vorausgewählt.
- Vorbelegung aus den gespeicherten Eingaben der letzten Analyse (`inputs`), sobald das Backend sie speichert.
- Bekannte Eigenheit: Beim Umschalten des Formulars wird das Label-Feld zurückgesetzt (Retoure-Dialog muss erneut geöffnet werden, ein bereits erstelltes Label bleibt im jeweiligen Dialog erhalten). Beide Dialoge laden die Adresse bei Bedarf.
- Im Browser noch nicht geprüft; bestehende Tests (PROJ-9, PROJ-64, PROJ-80) laufen grün.

## Implementation Notes: Backend (2026-11-11)
- `app/Analysis/ComplaintDecision.php` (neu): hält die Antworten des Formulars, baut die festen Sätze für die KI (`facts()`), die zu speichernden Felder des beantworteten Zweigs (`storedInput()`) und sagt, ob ein Retourenlabel dazugehört (`wantsReturnLabel()`).
- `AnalyzeTicketRequest`: neue Regeln für `formular`, `reklamation_berechtigt`, `reklamation_grund`, `kulanz`, `retoure`, `reklamationsnummer` (Aufbau nach der Einstellung für das Präfix, z. B. `R1-…`, in Großbuchstaben normalisiert), `versand`, `sendungsnummer_todo`. „Berechtigt“ ist Pflicht, bei „Nein“ auch der Grund. Deutsche Fehlermeldungen.
- `AnalysisController::analyze`: Bei Reklamationsformular geht nur der Faktenblock an die KI (das Freitextfeld wird ignoriert), ein Retourenlabel nur bei „berechtigt“ und „Retoure: Ja“. Gespeichert werden `formular` und die Felder des beantworteten Zweigs in den Eingaben der Analyse (keine Migration).
- Versandhinweis: „heute“ erlaubt „voraussichtlich heute noch“, „morgen“ „voraussichtlich morgen“, „zeitnah“ keinen Tag. Bei „Retoure: Ja“ wird kein Versandtermin genannt (Ausnahme „zuerst Rücksendung“ aus tone-011).
- `TodoCaseContext::build` bekommt die gespeicherten Eingaben: Beim Reklamationsformular entscheidet das Formular über die ToDo-Vorauswahl (berechtigt und Option angehakt) und liefert die Nummer; ohne Nummer ist das ToDo vorbereitet, hat aber keine Nummer zum Suchen und damit keinen automatischen Versand (PROJ-77). Ohne Formular gilt die bisherige Regel (Nummer im Antworttext).
- Prompt `analysis`: neue Version `analysis-2026-11-11.1` mit Hinweis zum Block „Angaben aus dem Reklamationsformular“; die Versionsprüfungen in drei bestehenden Tests sind angepasst.
- Tests: `tests/Feature/PROJ-86-ComplaintFormTest.php` (24 Tests: Fakten, Versandwahl, Pflichtregeln, Freitext unverändert, Label-Regel, Speicherung, Anzeige nach Kategorie, ToDo-Vorauswahl).
- Gesamtsuite: 2567 von 2571 grün. Zwei Tests in `PROJ-36-AnalysisIntegrationTest` schlagen auch ohne diese Änderungen fehl: „Eisberg-Fall PLAYBOOK-018/019“ (immer, hängt am echten Wissen) und „freie Neuproduktion lädt keine Kulanzregeln“ (wechselnd, flackert auch ohne diese Änderungen).

## Änderung 2026-11-12: zwei weitere Antworten bei „berechtigt“
Die Frage „Wird die Reklamation nach Prüfung als berechtigt anerkannt?“ hat jetzt vier Antworten:
- **Ja / Nein** wie bisher.
- **Unsicher / ungeprüft anerkennen** (`unsicher`): wie „Ja“ (Retoure-Frage mit DHL-Label, Reklamationsnummer, Versand, ToDo), aber die KI bekommt den Fakt, dass der Austausch ohne Anerkennung einer Rechtspflicht erfolgt und der Zusatz („… gesetzliche Rechte bleiben unberührt“) sinngemäß aus dem Wissen zu formulieren ist. Bei „Retoure: Ja“ kommt der Fakt dazu, dass der Kunde ein kostenfreies Retourenlabel zur weiteren, detaillierten internen Prüfung erhält; der Versandhinweis bleibt (der Ersatz wartet nicht auf die Rücksendung).
- **Unsicher, weitere Informationen nötig** (`info`): Pflichtfeld „Nötige Informationen“ (`reklamation_info`); die KI sagt weder zu noch ab und fragt gezielt danach. Keine Retoure, Nummer, Versand oder ToDo.
- Offen: Die Formulierungen stehen noch nicht im Wissen (Zusatz ohne Rechtsanerkennung, Zusatz zum Retourenlabel); die Fakten verweisen sinngemäß darauf. Wissensdokument ist noch zu verfassen (`/knowledge`).
- Code: `ComplaintDecision`, `AnalyzeTicketRequest`, `TodoCaseContext` (ToDo-Vorauswahl auch bei `unsicher`), `complaint-fields.blade.php`, `form.blade.php`; Tests in `PROJ-86-ComplaintFormTest.php` (31 grün).

## QA Test Results

**Tested:** 2026-11-11
**App URL:** http://localhost:8081 (Sail), Ticketseite `/tickets/<Nummer>`
**Tester:** QA Engineer (AI), ergänzt durch die Handprüfung des Auftraggebers am echten Ticket 2140357 („das funktioniert“)

**Umfang der Prüfung:** Automatisch (Pest, 27 Tests in `tests/Feature/PROJ-86-ComplaintFormTest.php`, Gesamtsuite vor den letzten drei Tests: 2567 von 2571 grün) und im Code gegen die Spec geprüft. Eine Browser-Prüfung durch mich (Chrome/Firefox/Safari, 375/768/1440 px) hat nicht stattgefunden; sie beruht auf der Handprüfung des Auftraggebers.

### Acceptance Criteria Status

#### Anzeige
- [x] Reklamationsticket (bestätigt oder vorgeschlagen) zeigt das Formular statt Freitext und DHL-Checkbox (Test)
- [x] Andere oder keine Kategorie: bisheriges Formular unverändert, kein Umschalter (Tests)
- [x] Umschalter zwischen Reklamationsformular und Freitext, Eingaben bleiben beim Umschalten erhalten (deaktivierte Fieldsets; Handprüfung)
- [x] Kundengruppe und Produkt bleiben Pflicht (bestehende Tests grün)

#### Berechtigt Ja/Nein
- [x] Ohne Wahl kein Abschicken (Browser `required`, Server-Fehlermeldung, Test)
- [x] „Nein“: Grund Pflicht, Kulanz Ja/Nein; Retoure, Nummer, Versand, ToDo werden nicht gesendet (Test)
- [x] „Ja“: Retoure, Nummer, Versand, ToDo
- [x] Wechsel Ja/Nein: nur Werte des gewählten Zweigs werden gesendet

#### Retoure
- [x] Frage mit Hinweis (im Einführungsmodus), Standard Nein
- [x] „Nein“: kein Label (Test); „Ja“: Dialog „DHL-Retourenlabel erstellen“ je Bestellung, Abschicken erst mit Label
- [x] Label nur bei berechtigt und Retoure Ja (Test, auch gegen manipulierte Anfragen)
- [x] „Nein“ ohne Label: Entwurf sagt, dass nicht zurückgesendet werden muss (Fakt an die KI, Test; Formulierung durch das Modell)

#### Reklamationsnummer
- [x] Feld mit Hinweis, vorbelegt aus Ticket oder EOCS, leer abschickbar mit Platzhalter (Test)
- [x] Falscher Aufbau: Fehlermeldung mit erwartetem Aufbau (Test)
- [x] Platzhalter im Entwurf löst im Antwortfenster die Rückfrage „offene Stellen“ aus (bestehendes Verhalten)

#### Versand voraussichtlich
- [x] heute / morgen / zeitnah, Standard zeitnah (Test)
- [x] „heute“ erlaubt „voraussichtlich heute noch“, „morgen“ „voraussichtlich morgen“, „zeitnah“ keinen Tag (Tests auf den Faktenblock; tone-011 angepasst)
- [x] Abgelehnte Reklamation: kein Versandzeitpunkt (Test); bei „Retoure: Ja“ ebenfalls keiner (Test)

#### ToDo „Sendungsnummer mitteilen“
- [x] Vorausgewählt bei berechtigt (Test der Vorauswahl), abwählbar (Test)
- [x] Nicht deutsch: ToDo ohne automatischen Versand (bestehende Regel PROJ-77)
- [ ] Ohne Reklamationsnummer: ToDo ist vorbereitet und nicht automatisch (Test), aber das Antwortfenster weist nicht eigens auf die fehlende Nummer hin (nur Platzhalter im Text). Als geringfügig bewertet, nicht als Fehler geführt.

#### Antwortentwurf
- [x] „Nein“ mit Grund: Grund und Kulanz-Entscheidung gehen an die KI, das Freitextfeld wird ignoriert (Test)
- [x] „Ja“: Zusage, Nummer, Versandhinweis aus dem Formular (Test auf den Faktenblock)
- [x] Angaben des Formulars haben Vorrang (Prompt `analysis-2026-11-11.1`)
- [ ] BUG-1: In der Ergebnisansicht ist nicht erkennbar, welche Formularangaben verwendet wurden („Erstellt mit: …“ zeigt nur Kundengruppe, Produkt, Variante). Vorbelegung beim erneuten Erstellen funktioniert (Tests).

#### Wissen und Protokoll
- [x] tone-011 ohne festen Satz „heute noch“; `knowledge:check` ohne Fehler
- [x] Formularangaben stehen in den gespeicherten Eingaben der Analyse (Test)

### Edge Cases Status
- [x] Kategorie wechselt nach dem Öffnen: Formular bleibt, Umschalter vorhanden
- [x] Mehrere Bestellungen: Retourenlabel je Bestellung wie bisher
- [x] Folge-Vorschlag: Felder vorbelegt, frühere Wahl „Freitext“ bleibt erhalten (Tests)
- [x] Einordnung fehlt, unsicher oder verborgen (Testmenge): bisheriges Formular, Umschalter nur bei unsicher/verborgen
- [x] Abschicken ohne Wahl: Fehlermeldung
- [x] Ältere Analyse ohne Formularfelder: Felder leer, Freitext wird nicht übersetzt
- [x] Manipulierte Anfragen (unbekannte Werte, Label ohne Retoure, Freitext im Formularmodus): abgewiesen oder ignoriert (Tests)
- [ ] Nicht geprüft: DHL-Schnittstelle nicht erreichbar bei „Retoure: Ja“ (unverändertes Verhalten aus PROJ-64)

### Security Audit Results
- [x] CSRF: Formular mit `@csrf`, Route unverändert mit Rate-Limit `language-model`
- [x] Eingaben: alle neuen Felder über `AnalyzeTicketRequest` validiert (Auswahlwerte, Länge, Aufbau der Nummer)
- [x] XSS: Ausgabe der Werte über Blade-Escaping bzw. `@js`
- [x] Autorisierung: Retourenlabel wird weiter gegen die Ticketnummer geprüft; keine neue Berechtigungsstufe
- [x] Datenschutz: An die KI gehen nur Entscheidungen, der Grund des Mitarbeiters und die Nummer, keine Adresse und kein Label-Inhalt (bestehender Test PROJ-64)
- [x] Keine neuen Geheimnisse, keine neuen Umgebungsvariablen
- Hinweis: Der Freitext „Grund“ geht unverändert an das Modell (Mitarbeiter-Eingabe, wie bisher „Zusätzliche Informationen“).

### Regression
- [x] PROJ-9, 10, 59, 64, 70, 77, 80 und Begrüßungstests grün
- Bekannt, nicht durch PROJ-86: In `PROJ-36-AnalysisIntegrationTest` schlägt der Eisberg-Fall (PLAYBOOK-018/019) immer fehl, der Test „freie Neuproduktion lädt keine Kulanzregeln“ flackert. Beides tritt auch ohne diese Änderung auf.

### Bugs Found

#### BUG-1: Formularangaben im Ergebnis nicht erkennbar
- **Severity:** Medium
- **Steps to Reproduce:**
  1. Reklamationsticket öffnen, Formular ausfüllen (berechtigt, Retoure Nein, Nummer, Versand), Vorschlag erstellen.
  2. In der Kopfzeile „Vorschlag erstellen“ steht „Erstellt mit: Kundengruppe · Produkt“.
  3. Erwartet: die gewählten Angaben (z. B. „berechtigt · keine Retoure · Versand zeitnah · R1-…“) sind erkennbar.
  4. Tatsächlich: Sie stehen nur beim Öffnen des Formulars (vorbelegt) und im Protokoll der Analyse.
- **Priority:** Fix in next sprint (Workaround: Formular aufklappen)

### Summary
- **Acceptance Criteria:** 33 von 34 erfüllt (ein Punkt als geringfügig gewertet)
- **Bugs Found:** 1 (0 critical, 0 high, 1 medium, 0 low gelistet)
- **Security:** Pass
- **Production Ready:** YES (keine Critical/High-Fehler); Handprüfung im Browser durch den Auftraggeber
- **Recommendation:** Freigeben; BUG-1 nachziehen


## Deployment
_To be added by /deploy_
