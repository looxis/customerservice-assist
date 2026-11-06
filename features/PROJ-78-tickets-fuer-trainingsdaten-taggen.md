# PROJ-78: Tickets für Trainingsdaten klassifizieren und taggen

## Status: In Progress
**Created:** 2026-11-06
**Last Updated:** 2026-11-06

## Dependencies
- Requires: PROJ-6 (Zammad-Ticket laden), PROJ-9 (Fallanalyse per LLM, LLM-Aufruf), PROJ-27/PROJ-31 (Einstellungen, Prompt- und Modellverwaltung, falls dort versioniert), PROJ-19 (Zammad-Token je Mitarbeiter zum Schreiben)
- Unabhängig von allen Analyse-Funktionen: keine Verwendung von `tracked_tickets.category`, Analysen, Knowledge oder ToDos

## Hintergrund
Für das Training des Decision-Modells Laya (Vorklassifikation eingehender Mails, bereits vorgesehen und teilweise integriert) braucht es je Kategorie mehrere hundert beschriftete Tickets. Etienne hat rund 100 Tickets von Hand mit `train:wismo` getaggt; ein separates Python-Skript exportiert später Tickets anhand der `train:*`-Tags als `.eml`. Export, Laya-Format und Training sind **nicht** Teil dieses Features. Dieses Feature liefert ausschließlich die Tags in Zammad.

Die App durchsucht alle Zammad-Tickets, lässt ein Frontier-LLM die **erste Kundennachricht** jedes Tickets einer Kategorie zuordnen und schreibt nach Freigabe das Tag `train:<kategorie>` ins Ticket. Etienne prüft nur uneindeutige Fälle und Stichproben.

## Kategorien (fest)
| Tag | Abgrenzung |
|-----|------------|
| `train:wismo` | Lieferstatus: Wo ist meine Bestellung, Paket nicht angekommen, auch Amazon-Mails mit dem Wort „Storno“. Entscheidend ist der Anlass: „Ich will stornieren, weil nicht geliefert wurde“ ist WISMO (Storno ist nach Bestätigung grundsätzlich nicht möglich, personalisierte Ware ohne Widerrufsrecht) |
| `train:reklamation` | Mangel oder Beschwerde zu gelieferter Ware |
| `train:angebotsanfrage` | Anfrage nach Angebot, Preis oder Menge; auch Produktfragen mit Preis-/Mengen-/Angebotsbezug |
| `train:bestellung` | Nur eindeutige Bestellungen: „hiermit bestelle ich“, „danke für das Angebot, bitte fertigen“, „bitte wie besprochen liefern“ |
| `train:storno` | Storno oder Änderung einer aufgegebenen Bestellung, **ohne** dass der Lieferstatus der Grund ist („falsch bestellt, bitte stornieren“, „Lieferadresse ändern“) |
| `train:technischer-support` | Funktion des Online-Shops: Passwort vergessen, Login, Konfigurator, Bild-Upload |
| `train:produkt-frage` | Funktionsweise oder Eigenschaften eines Produkts, vor oder nach dem Kauf, ohne Preis-/Mengen-/Angebotsbezug |

Passt keine Kategorie, erhält das Ticket **kein** Tag („keine“ ist ein gültiges Ergebnis).

## User Stories
- Als Admin (Etienne) möchte ich alle Zammad-Tickets automatisch auf ihre erste Kundennachricht klassifizieren lassen, damit ich nicht Hunderte Tickets von Hand heraussuchen muss.
- Als Admin möchte ich unklare Fälle und Tickets mit mehreren Anliegen zuerst sehen und mit einem Klick bestätigen oder korrigieren, damit meine Prüfzeit auf die schwierigen Fälle fällt.
- Als Admin möchte ich je Kategorie sehen, wie viele Tickets bestätigt, automatisch getaggt und noch offen sind und wie hoch die Trefferquote der Stichproben ist, damit ich weiß, wann genug Trainingsdaten da sind.
- Als Admin möchte ich, dass eindeutige Tickets einer Kategorie erst automatisch getaggt werden, nachdem eine Stichprobe die Qualität belegt hat, damit keine unsichere Zuordnung in Zammad landet.
- Als Admin möchte ich, dass meine Bestätigungen und Korrekturen als Beispiele in die weitere Klassifikation einfließen, damit das LLM aus meinen Entscheidungen weitere Tickets selbstständig findet.
- Als Admin möchte ich eine separat und vollständig geprüfte Testmenge, damit die Qualität später unabhängig vom Training messbar ist.

## Out of Scope
- Export der Tickets (`.eml`, JSONL), Pseudonymisierung, Laya-Format und Training (separat, später, anhand der Tags)
- Vorklassifikation eingehender Tickets im Betrieb (vorgesehene Laya-Funktion; Verbindung zu `tracked_tickets.category` bewusst nicht hergestellt)
- Klassifikation späterer Nachrichten, Antworten und Rückmeldungen im Ticket: nur die erste Kundennachricht zählt
- Bearbeiten, Beantworten oder Schließen von Tickets; geschrieben werden ausschließlich Tags
- Eigener lokal trainierter Klassifikator (das wäre praktisch das Laya-Training)
- Zweiter LLM-Anbieter (PROJ-17 zurückgestellt)
- Zugriff für Nicht-Admins
- Export, der Test- und Trainingstickets trennt (liest später die Tags `train:*` und `test:*`, nicht Teil dieses Features)

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Zugriff
- [ ] Angenommen ein Nutzer ist kein Admin, wenn er `/beschriftung` aufruft, dann erhält er eine 403-Antwort und der Sidebar-Eintrag ist für ihn nicht sichtbar
- [ ] Angenommen ein Admin ist ausgewählt, wenn er die Seite öffnet, dann sieht er Kennzahlen je Kategorie, Warteschlangen und die Läufe

### Abrufen
- [ ] Angenommen ein Lauf wird gestartet, wenn die App Tickets abruft, dann werden alle erreichbaren Zammad-Tickets erfasst, ohne an die Suchgrenze von 10 000 Treffern zu stoßen (Abruf in Abschnitten)
- [ ] Angenommen ein Ticket hat mehrere Kundenartikel, wenn es erfasst wird, dann wird nur die erste eingehende Kundennachricht gespeichert und klassifiziert; Antworten der Mitarbeiter und spätere Kundenmails werden ignoriert
- [ ] Angenommen ein Ticket hat bereits ein `train:*`-Tag, wenn es erfasst wird, dann wird es als Referenz übernommen (Quelle `vorhanden`) und nicht erneut zur Prüfung vorgeschlagen
- [ ] Angenommen ein Ticket wurde bereits erfasst, wenn der Lauf erneut startet, dann entsteht kein Duplikat und der Abruf setzt nach einem Abbruch an der letzten Stelle fort
- [ ] Angenommen der Text enthält Signatur oder zitierte Mail, wenn er gespeichert wird, dann wird er wie in der Ticketansicht bereinigt

### Klassifizieren
- [ ] Angenommen ein Ticket ist erfasst, wenn es klassifiziert wird, dann speichert die App genau eine Kategorie (oder „keine“), die Kennzeichen „unklar“ und „mehrere Anliegen“, eine Konfidenz, eine Begründung in einem Satz und die maßgebliche Textstelle
- [ ] Angenommen ein Ticket ist in einer fremden Sprache verfasst, wenn es klassifiziert wird, dann wird es ohne Übersetzung wie jedes andere klassifiziert
- [ ] Angenommen die Mail enthält das Wort „Storno“, wenn der Anlass der Lieferstatus ist, dann lautet die Kategorie `wismo` und nicht `storno`
- [ ] Angenommen eine Klassifikation ist erfolgt, wenn sie gespeichert wird, dann sind Modell, Prompt-Version, Beispielmenge und Token protokolliert
- [ ] Angenommen der LLM-Aufruf schlägt fehl, wenn der Job läuft, dann wird das Ticket mit Fehlerstand vermerkt, später erneut versucht und der Lauf nicht abgebrochen

### Prüfen
- [ ] Angenommen es gibt Vorschläge, wenn der Admin die Prüfansicht öffnet, dann sieht er in dieser Reihenfolge: unklar/mehrere Anliegen, niedrige Konfidenz, alle Vorschläge `storno`, alle Mails mit dem Wort „Storno“ mit anderer Einstufung als `wismo`, Stichprobe der übrigen
- [ ] Angenommen ein Ticket ist in der Prüfansicht, wenn der Admin es ansieht, dann erscheinen bereinigter Text, Vorschlag mit Begründung, Textstelle und ein Link nach Zammad
- [ ] Angenommen der Admin prüft ein Ticket, wenn er „bestätigen“, eine andere Kategorie oder „keine“ wählt, dann wird die Entscheidung mit Name und Zeit gespeichert und das nächste Ticket erscheint
- [ ] Angenommen ein Ticket gehört zur Testmenge, wenn es geprüft wird, dann muss der Admin es vollständig selbst entscheiden; es wird nie als Beispiel für den Prompt genutzt und nie automatisch getaggt
- [ ] Angenommen der Admin hat gerade eine Entscheidung getroffen, wenn er sich geirrt hat, dann kann er sie in der Prüfansicht nachträglich ändern

### Qualität und Freigabe
- [ ] Angenommen es gibt geprüfte Tickets, wenn der Admin die Kennzahlen ansieht, dann sieht er je Kategorie: bestätigt, korrigiert, offen, Trefferquote der Stichproben, Zahl der Tags in Zammad und Zielwert
- [ ] Angenommen die Stichprobe einer Kategorie hat mindestens die eingestellte Zahl geprüfter Fälle (Standard 50) und die eingestellte Trefferquote (Standard 95 %) erreicht, wenn der Admin die Kategorie freigibt, dann darf die App eindeutige Tickets (nicht unklar, kein Mehrfachanliegen, Konfidenz über Schwelle) dieser Kategorie ohne Einzelprüfung taggen
- [ ] Angenommen eine Kategorie ist noch nicht freigegeben, wenn Tickets klassifiziert sind, dann wird kein Tag geschrieben, außer für vom Admin bestätigte Tickets
- [ ] Angenommen die Trefferquote einer freigegebenen Kategorie fällt in neuen Stichproben unter die Schwelle, wenn die Kennzahlen aktualisiert werden, dann wird die Freigabe gesperrt und es werden keine weiteren automatischen Tags geschrieben
- [ ] Angenommen die Wiederholung einer Klassifikation mit anderen Beispielen, wenn sie bereits getaggte Tickets betrifft, dann ändert sie dort keine Tags von selbst

### Tags schreiben
- [ ] Angenommen ein Trainingsticket ist bestätigt oder automatisch freigegeben, wenn die App taggt, dann wird in Zammad genau das Tag `train:<kategorie>` mit dem Zammad-Token des angemeldeten Admins hinzugefügt; bei einem Testticket genau das Tag `test:<kategorie>` (nur nach Bestätigung durch den Admin)
- [ ] Angenommen ein Ticket hat Ergebnis „keine“, wenn der Lauf taggt, dann erhält es kein `train:*`-Tag
- [ ] Angenommen der Admin korrigiert ein bereits getaggtes Ticket, wenn er die Kategorie ändert, dann wird das alte `train:*`-Tag entfernt und das neue gesetzt
- [ ] Angenommen jeder Tag ist in der App als „bestätigt“ oder „automatisch“ vermerkt, wenn der Admin die Liste ansieht, dann kann er danach filtern
- [ ] Angenommen der Admin startet einen Trockenlauf, wenn er ihn auslöst, dann zeigt die App, welche Tags geschrieben würden, schreibt aber nichts
- [ ] Angenommen Zammad ist nicht erreichbar oder lehnt das Schreiben ab, wenn getaggt werden soll, dann bleibt das Ticket im Zustand „Tag offen“ und wird beim nächsten Lauf erneut versucht

### Beispiele und Testmenge
- [ ] Angenommen es gibt geprüfte Tickets, wenn ein neuer Klassifikationslauf startet, dann nimmt die App je Kategorie eine begrenzte Zahl Beispiele aus den geprüften Tickets in den Prompt (bevorzugt Korrekturen und Grenzfälle) und protokolliert die Auswahl mit der Prompt-Version
- [ ] Angenommen ein Ticket wird erfasst, wenn es der Testmenge zugeordnet wird, dann geschieht dies beim Erfassen nach einer festen Regel (Standard 20 %, stabil je Ticket-ID), die Zuordnung ändert sich danach nie und kein Ticket liegt in Training und Test
- [ ] Angenommen ein Ticket trägt bereits ein von Hand gesetztes `train:wismo`-Tag, wenn die Quote geprüft wird, dann dient es zur Messung der WISMO-Trefferquote und nicht zugleich als Beispiel

## Edge Cases
- Erste Kundennachricht ist leer, nur ein Anhang oder nur eine Signatur: Ticket wird als „nicht klassifizierbar“ vermerkt und bekommt kein Tag
- Ticket entstand per Telefonnotiz oder interner Weiterleitung, nicht per Kundenmail: kein „erster Kundenartikel“ gefunden, Ticket wird übersprungen
- Zwei Kategorien passen („Angebot erhalten, bitte fertigen, aber Lieferadresse ändern“): „mehrere Anliegen“ gesetzt, Vorschlag mit der Hauptkategorie, Ticket kommt in die Prüfung
- Amazon- oder Marktplatz-Benachrichtigungen („Storno“ im Text) werden als WISMO erkannt und nie als `storno`
- Ticket wurde in Zammad zusammengeführt oder gelöscht, bevor getaggt wird: Fehler wird vermerkt, Ticket übersprungen
- Ein Mitarbeiter ohne gültigen Zammad-Token startet das Schreiben: Hinweis statt Fehlermeldung, nichts wird geschrieben
- Zwei Admins prüfen dasselbe Ticket gleichzeitig: die später gespeicherte Entscheidung gilt, die frühere bleibt im Verlauf sichtbar
- Sehr seltene Kategorie (z. B. `storno`) erreicht den Zielwert auch nach dem ganzen Pool nicht: Kennzahl zeigt es an, kein Auffüllen durch zweifelhafte Fälle
- Sehr viele Tickets: Abruf und Klassifikation laufen im Hintergrund in Abschnitten, mit Anzeige des Fortschritts und Abbruchmöglichkeit

## Technical Requirements (optional)
- Security: nur Admins; geschrieben werden nur Tags; Zammad-Token je Mitarbeiter aus `config/staff.php`
- Datenschutz: Kundentexte liegen lokal in der Datenbank und gehen an den LLM-Anbieter (OpenAI, AVV liegt vor); Aufbewahrung begrenzen und Löschbefehl bereitstellen
- Kosten: LLM-Verbrauch je Lauf sichtbar (Token), Obergrenze je Lauf einstellbar
- Reproduzierbarkeit: Prompt-Version, Beispielmenge und Modell je Klassifikation protokolliert
- Eigener Namensraum, eigene Tabellen und eigene Routen; Rückbau durch Löschen des Moduls möglich

## Open Questions
- [x] Wie genau wird „alle Tickets“ in Zammad abgerufen? Gelöst in `/architecture`: Abruf nach Erstellungszeiträumen, siehe Tech Design
- [x] Pool-Gruppen: alle Gruppen, Ausschluss auf der Seite einstellbar (Entscheidung 2026-11-06)
- [ ] Standardwerte für Konfidenz-Schwelle, Stichprobenanteil, Zielwert je Kategorie (Vorschlag: 100 bestätigte, mehrere hundert insgesamt je Kategorie) und Beispielzahl im Prompt: Vorschläge stehen im Tech Design, endgültig nach dem Pilotlauf
- [x] Testmenge vor dem ersten Auto-Tagging festlegen: ja, Zuordnung beim Erfassen des Tickets (siehe Tech Design)
- [x] Aufbewahrung der Kundentexte: 6 Monate nach der letzten Aktivität am Ticket-Eintrag, dann wird der Text geleert (Entscheidung 2026-11-06)
- [x] Seitenleiste: Admin-Block neben der Ticket-Übersicht
- [x] Test- und Trainingstickets im Export unterscheidbar: Testtickets erhalten `test:<kategorie>` statt `train:<kategorie>` (Entscheidung 2026-11-06)

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Modul in CSA, aber abgeschottet (`app/Labeling/`, eigene Tabellen und Routen) | Zammad-Client, LLM-Aufruf, Admin-Prüfung und Layout sind vorhanden; Rückbau bleibt einfach | 2026-11-06 |
| Nur die erste eingehende Kundennachricht wird klassifiziert | Antworten und Rückmeldungen enthalten kein neues Anliegen; ein Tag je Ticket genügt | 2026-11-06 |
| Tag `train:<kategorie>`, höchstens eines je Ticket, kein Tag bei „keine“ | Das externe Export-Skript filtert nach `train:*`; „keine“ ist ein gültiges Ergebnis | 2026-11-06 |
| Sieben Kategorien jetzt fest | Prompt, Prüfoberfläche und Tags hängen daran; spätere Umbenennung kostet Tag-Änderungen in Zammad | 2026-11-06 |
| Entscheidend ist der Anlass, nicht das Wort „Storno“ | Viele WISMO-Mails (Amazon) enthalten „Storno“; Storno ist nach Bestätigung grundsätzlich nicht möglich, die spätere Antwort betrifft den Lieferstatus | 2026-11-06 |
| `bestellung` nur für eindeutige Bestellungen; Änderung und Storno als `storno` | Trennt die tatsächliche Bestellung von Folgevorgängen und Rückfragen | 2026-11-06 |
| `technischer-support` nur für Funktion des Online-Shops, Produktfragen als `produkt-frage` | Klare Trennung zwischen Shop und Produkt | 2026-11-06 |
| Alle Zammad-Tickets werden durchsucht, kein Zeitfenster | Zammad existiert noch nicht lange; nur so reicht der Pool für mehrere hundert Tickets je Kategorie | 2026-11-06 |
| Auto-Tagging je Kategorie erst nach Stichprobe mit Trefferquote | Etienne will nur Unklares und Stichproben prüfen; die Quote schützt vor falschen Tags | 2026-11-06 |
| Bestätigte und korrigierte Tickets werden automatisch als Prompt-Beispiele nachgezogen | Das Verfahren soll aus Etiennes Entscheidungen weitere Tickets selbstständig finden | 2026-11-06 |
| Testmenge wird in Zammad als `test:<kategorie>` gekennzeichnet, Trainingstickets als `train:<kategorie>`; Aufteilung nach Ticket | Der spätere Export kann Test und Training anhand der Tags trennen; kein Leck zwischen beiden | 2026-11-06 |
| Tags werden mit dem Zammad-Token des angemeldeten Admins geschrieben (Etienne) | Token je Mitarbeiter ist bereits vorhanden; Zugriff nur für Admins | 2026-11-06 |
| Fremdsprachige Erstnachrichten werden mitklassifiziert | Das LLM versteht sie; sonst fehlen Trainingsdaten | 2026-11-06 |
| Export, Pseudonymisierung und Laya-Format nicht Teil des Features | Ausdrückliche Vorgabe von Etienne; erfolgt später separat anhand der Tags | 2026-11-06 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|
| Eigene Weboberfläche im selben Projekt, normale Seiten mit Blade | Wie Ticket-Übersicht und Analysen-Übersicht; kein eigener Dienst nötig | 2026-11-06 |
| Alle Tickets werden nach Erstellungszeiträumen abgerufen, jeder Zeitraum unter der Zammad-Suchgrenze | Die Suche liefert höchstens 10 000 Treffer; Zeiträume umgehen die Grenze und machen den Abruf nach Abbruch fortsetzbar | 2026-11-06 |
| Abruf und Klassifikation als Hintergrundaufgaben je Ticket auf der vorhandenen Warteschlange | Der Queue-Dienst läuft bereits; eine Aufgabe je Ticket ist wiederholbar und blockiert nichts | 2026-11-06 |
| Zammad wird mit eigenen, kleinen Bausteinen angesprochen: Lesen als Erweiterung des bestehenden Zammad-Clients, Schreiben von Tags in einer eigenen Klasse | Bestehende Funktionen bleiben unverändert; das Schreiben braucht den Token eines Mitarbeiters | 2026-11-06 |
| Hintergrundläufe taggen mit dem Token des Admins, der den Lauf gestartet hat | Token je Mitarbeiter liegt vor; ohne Token startet der Lauf nicht | 2026-11-06 |
| Die Trefferquote einer Kategorie wird nur aus einer Zufallsstichprobe der eindeutigen Vorschläge berechnet | Nur geprüfte Zweifelsfälle würden die Quote verzerren; Freigabe soll die Qualität der automatisch getaggten Tickets belegen | 2026-11-06 |
| Testmenge wird beim Erfassen des Tickets zugeordnet (ca. 20 %, stabil je Ticket-ID) | Steht vor dem ersten Auto-Tagging fest, ändert sich nie; kein Ticket landet in Train und Test | 2026-11-06 |
| Testtickets werden nie automatisch getaggt und nie als Prompt-Beispiel genutzt | Sie dienen der unabhängigen Qualitätsmessung | 2026-11-06 |
| Prompt als versionierte Datei, Modell und Schwellen über Einstellungen | Gleiches Muster wie die Fallanalyse; jede Klassifikation protokolliert Version, Modell, Beispielmenge | 2026-11-06 |
| Kundentexte werden 6 Monate nach der letzten Aktivität geleert, Entscheidungen bleiben | Datenschutz; Entscheidung des Auftraggebers; täglicher Löschlauf wie bei den vorhandenen Löschbefehlen | 2026-11-06 |
| Keine neuen Pakete | Alles ist im Projekt vorhanden | 2026-11-06 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

### Backend nötig?
Ja. Es gibt eine Datenbank (Tickets, Vorschläge, Entscheidungen, Freigaben), Hintergrundaufgaben und Zugriffe auf Zammad und das LLM. Keine neue Anmeldung: Admin sind die Nutzer aus der vorhandenen Admin-Liste.

### A) Oberfläche (Seite „Beschriftung“, nur Admins)
```
Beschriftung (/beschriftung)  [Admin-Block der Seitenleiste, neben Ticket-Übersicht]
+-- Kennzahlen je Kategorie (sieben Karten)
|   +-- bestätigt / korrigiert / automatisch getaggt / offen
|   +-- Trefferquote der Stichprobe, Zielwert, Stand der Freigabe
|   +-- Knopf „Freigeben“ bzw. Hinweis „gesperrt“
+-- Läufe
|   +-- Abruf starten (Pool: alle Gruppen, Ausschluss einstellbar)
|   +-- Klassifikation starten / Neu klassifizieren (noch ohne Tag)
|   +-- Fortschritt, Fehler, Abbrechen
|   +-- Tags schreiben (Trockenlauf oder echt)
+-- Prüfansicht
|   +-- Warteschlange in fester Reihenfolge (siehe Spec)
|   +-- Karte: bereinigter Text, Vorschlag mit Begründung, Textstelle, Zammad-Link
|   +-- Knöpfe: bestätigen / andere Kategorie / keine
+-- Ticket-Liste mit Filtern (Kategorie, Quelle, bestätigt/automatisch, Test/Training)
+-- Einstellungen der Seite
    +-- ausgeschlossene Gruppen, Konfidenz-Schwelle, Stichprobenanteil, Zielwert
```
Vorhandene Bausteine: Layout, Karte, Badge, Knopf, Auswahlfeld, einklappbare Bereiche, Ticketlisten-Muster der Ticket-Übersicht. Neue Bausteine: Kategorie-Karte mit Kennzahlen, Prüfkarte. Alpine.js nur für Tastatur und schnelles Weiterklicken in der Prüfansicht.

### B) Datenmodell (einfach beschrieben)
```
Ticket-Eintrag (ein Eintrag je Zammad-Ticket)
- Zammad-Ticket-ID, Ticketnummer, Gruppe, Erstelldatum
- Erste Kundennachricht: Artikel-ID und bereinigter Text (wird nach 6 Monaten geleert)
- Quelle: „vorhanden“ (hat schon ein train:-Tag, z. B. die ~100 WISMO) oder „Pool“
- Zuordnung: Training oder Test (beim Erfassen festgelegt)
- Stand: erfasst / klassifiziert / geprüft / getaggt / nicht klassifizierbar / Fehler

Vorschlag (mehrere je Ticket möglich, der letzte zählt)
- Kategorie oder „keine“, unklar, mehrere Anliegen, Konfidenz, Begründung, Textstelle
- Modell, Prompt-Version, verwendete Beispiele, Token, Zeitpunkt, Lauf

Prüfentscheidung (höchstens eine gültige je Ticket, Verlauf bleibt)
- Endgültige Kategorie oder „keine“, Admin, Zeitpunkt
- Herkunft: Zweifelsfall, Stichprobe, Testmenge, Korrektur
- Tag in Zammad: gesetzt am / Art „bestätigt“ oder „automatisch“ / offen / Fehler

Kategorie-Freigabe (eine je Kategorie)
- Freigegeben ja/nein, von wem, wann, Trefferquote und Zahl geprüfter Fälle zum Zeitpunkt

Lauf
- Art (Abruf, Klassifikation, Tags schreiben), Start/Ende, Zähler, Admin (dessen Token schreibt)
- Fortschrittsmarke je Zeitraum, damit ein Abbruch fortgesetzt werden kann

Beispielauswahl
- Welche geprüften Tickets je Kategorie als Beispiel im Prompt standen (je Prompt-Version)
```
Gespeichert in der vorhandenen MySQL-Datenbank, eigene Tabellen mit dem Präfix `labeling_`. Keine Änderung an bestehenden Tabellen.

### C) Ablauf und Technik (begründet)
1. **Abruf aller Tickets.** Die Zammad-Suche liefert höchstens 10 000 Treffer. Deshalb wird nach Erstellungszeiträumen (z. B. je Monat, bei Überschreitung geteilt) in Seiten zu 100 abgerufen; jeder Zeitraum wird als erledigt vermerkt. Je Ticket holt eine Hintergrundaufgabe die erste Kundennachricht. Bei einem Ticket mit vorhandenem `train:*`-Tag wird die Quelle „vorhanden“ gesetzt. Tickets ohne Kundenmail werden übersprungen.
2. **Klassifikation.** Je Ticket eine Hintergrundaufgabe, die das LLM mit Regeln und Beispielen fragt und die feste Antwortstruktur speichert. Die Aufrufe laufen über die vorhandene Drosselung für Sprachmodelle. Fehler werden vermerkt und später wiederholt.
3. **Beispiele.** Vor jedem Lauf stellt die App aus den geprüften Tickets je Kategorie eine begrenzte Zahl Beispiele zusammen (Vorschlag: bis 8, Korrekturen und Grenzfälle zuerst, nie Testmenge). Die Auswahl gehört zur Prompt-Version.
4. **Prüfung.** Die Warteschlange wird aus den Kennzeichen der Vorschläge aufgebaut. Die Stichprobe zieht zufällig aus allen eindeutigen Vorschlägen einer Kategorie. **Nur diese Stichprobenentscheidungen gehen in die Trefferquote ein**, weil Zweifelsfälle sie sonst zu schlecht, nachträgliche Korrekturen sie zu gut aussehen ließen.
5. **Freigabe.** Eine Kategorie gilt als freigegeben, wenn Admin und Schwellen (Zahl der Stichprobenfälle, Trefferquote) zusammenpassen. Neue Stichproben werden laufend ausgewertet; fällt die Quote darunter, sperrt die App die Kategorie.
6. **Tags schreiben.** Ein Lauf schreibt für bestätigte Tickets und, bei freigegebener Kategorie, für eindeutige Trainingstickets das Tag `train:<kategorie>`; Testtickets erhalten nach Bestätigung durch den Admin `test:<kategorie>`. Zuständig ist eine eigene kleine Klasse mit dem Token des Admins, der den Lauf startet. Zammad hat dafür eigene Schnittstellen zum Setzen und Entfernen. Vorher wird der aktuelle Tag-Stand des Tickets gelesen: vorhandene Tags bleiben, ein Ticket mit abweichendem `train:*`-Tag wird nicht überschrieben, sondern als Konflikt gemeldet. Trockenlauf zeigt nur die geplanten Änderungen.
7. **Löschung.** Ein täglicher Löschlauf leert Texte, die älter als 6 Monate sind, und lässt Kategorien und Entscheidungen stehen (wie die vorhandenen Löschbefehle).

### D) Zugriff
Wie die Ticket-Übersicht: Nur Admins (bestehende Admin-Liste, ausgewählter Nutzer). Alle Seiten und Aktionen prüfen dies serverseitig; der Seitenleisten-Eintrag erscheint nur für Admins. Aktionen, die Zammad verändern, laufen nur mit gültigem Token des Admins.

### E) Wiederverwendung und neue Teile
- **Wiederverwendet:** Lesezugriff auf Zammad (Paging, Artikel), Textbereinigung der Ticketansicht, LLM-Aufruf mit fester Antwortstruktur, versionierte Prompts und Modellauswahl, Warteschlange, Löschbefehle als Muster, Layout und Komponenten, Admin-Prüfung, Testhilfen für Zammad.
- **Neu:** eigene Bereiche für Abruf nach Zeiträumen, Klassifikation, Tag-Schreiben, Prüfansicht, Kennzahlen und Freigabe, alles im eigenen Namensraum und mit eigenen Tabellen.
- **Nicht verwendet:** `tracked_tickets` und dessen Kategorie, Analysen, Knowledge, ToDos, Zammad-Webhook.

### F) Offene technische Hinweise
- Eine Zammad-Anfrage je Ticket für die erste Kundennachricht. Bei einigen tausend Tickets läuft der Abruf als Hintergrundlauf mit gleichmäßigem Tempo über Stunden; Zammad ist nicht gedrosselt, wir bremsen selbst.
- Die erste Kundennachricht ist die erste Mail mit Absender Kunde; interne Notizen und Telefonnotizen zählen nicht (siehe Edge Cases).
- Der schreibende Hintergrundlauf braucht den Token des Admins im Hintergrund. Er steht in der Umgebung des Servers, wird nur zum Schreiben benutzt und nie angezeigt.

### G) Pakete
Keine neuen Pakete.

## Implementation Notes (Frontend, 2026-11-06)
- **Seiten (nur Admins, 403 für andere):** `/beschriftung` (Kategorie-Karten mit Kennzahlen und Freigabe, Läufe, Einstellungen), `/beschriftung/pruefen` (Warteschlangen, Prüfkarte, Tastenkürzel 1–7, 0 = keine, Enter = bestätigen), `/beschriftung/tickets` (Liste mit Filter Vorschlag und Zuordnung). Seitenleisten-Eintrag „Beschriftung“ im Admin-Block.
- **Testmenge in der Prüfansicht:** Vorschlag und Textstellen-Markierung sind verborgen, es gibt kein „Bestätigen“ (Spec: vollständig selbst entscheiden).
- **Neue Komponenten:** `x-labeling.nav` (Unternavigation, Vorschau-Hinweis), `x-labeling.category-card`.
- **Kategorien:** Enum `App\Labeling\LabelCategory` (Label, Kurzregel, Tag `train:`/`test:`), vom Backend wiederzuverwenden.
- **Vorschau-Stand:** Zahlen, Tickets und Läufe kommen aus `App\Labeling\PreviewData` (erfundene Daten) und sind im Seitenkopf als Vorschau gekennzeichnet. Alle Aktionen (Lauf starten, Freigabe, Entscheidung, Einstellungen speichern) haben Routen und Formulare mit CSRF, antworten aber über `LabelingController::pending()` mit „noch nicht angeschlossen“. Das Backend ersetzt `PreviewData` und `pending()`.
- **Abweichung:** Fortschrittsbalken als `<progress>`-Element, weil Inline-Styles verboten sind.
- **Tests:** `tests/Feature/PROJ-78-LabelingPagesTest.php` (Zugriff, Seiten, Testmenge, Filter, Platzhalteraktion).
- **Noch offen fürs Backend:** Standardwerte der Einstellungen sind Platzhalter (Ziel 100, 50 Fälle, 95 %, 15 %, Konfidenz 0,85, 8 Beispiele); Zähler „Fortschritt“ und Warteschlangenlängen kommen aus echten Daten; Ladezustand der Läufe (Fortschritt aktualisieren) ist noch statisch.

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
