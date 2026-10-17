# PROJ-49: Einstellungen – Erkennungsregeln

## Status: Approved
**Created:** 2026-10-17
**Last Updated:** 2026-10-17

## Dependencies
- Requires: PROJ-27 (Einstellungsseite: Speicherung, Verlauf, Rechte), PROJ-7 (Bestellnummern, Abruf aus EOCS), PROJ-38 (Artikelnummern im Kundentext).
- Verwandt: PROJ-31, PROJ-50 (weitere Unterseiten der Einstellungen), PROJ-9 (Pseudonymisierung erkennt Bestellnummern).

## Ausgangslage
Woran die App Bestellnummern und Artikelnummern in einem Ticket erkennt, steht fest im Code: sechs Bestellnummern-Formate (Amazon, looxis.de/Fachhändler, LOOXIS-Pro, looxis.fr, masterpics, EOCS-ID) mit ihren Besonderheiten und die Artikelnummer-Regel („fünf Ziffern hinter einem Stichwort“). Ein neuer Kanal oder ein geändertes Nummernformat braucht einen Entwickler, und niemand kann nachsehen, was erkannt wird und was nicht.

Diese Spec ergänzt die Einstellungsseite (PROJ-27) um die Unterseite **„Erkennungsregeln“**: die Formate sind sichtbar, änderbar und mit Beispielen geprüft; die Erkennung lässt sich vor dem Speichern an einem Text ausprobieren.

## User Stories
- Als Admin möchte ich ein neues Bestellnummern-Format (neuer Kanal) selbst ergänzen, ohne einen Entwickler.
- Als Admin möchte ich sehen und prüfen, woran jedes Format erkannt wird, und sicher sein, dass sich zwei Formate nicht überschneiden.
- Als Admin möchte ich festlegen, an welchen Stichwörtern und mit wie vielen Ziffern eine Artikelnummer im Kundentext erkannt wird.
- Als Admin möchte ich einen Text einfügen und sehen, was die App darin erkennt, bevor ich eine Änderung speichere.
- Als Stammkraft oder Aushilfe möchte ich nachlesen, was die App als Bestellnummer erkennt.

## Out of Scope
- Reguläre Ausdrücke als Eingabe; es gibt die Kurzschreibweise unten.
- Die Zuordnung der Kanalnamen aus EOCS zu den Kundengruppen und die Kundengruppen selbst: PROJ-50.
- Die Erkennung von Telefonnummern und Kontaktdaten der Pseudonymisierung; sie nutzt die Bestellnummern-Formate nur, um Bestellnummern nicht zu ersetzen.
- Die Regeln, welche Bestellung ein Ticket betrifft, und das Abrufen aus EOCS.

## Die Unterseite „Erkennungsregeln“

### Bestellnummern-Formate
Eine Liste von Formaten, von oben nach unten angewendet (das erste passende zählt); die Reihenfolge ist verschiebbar. Je Format:
| Feld | Bedeutung | Beispiel (Standard) |
|---|---|---|
| Name | erscheint bei der Bestellung und in der Analyse | „Amazon“, „looxis.de / Fachhändler“ |
| Aufbau | Kurzschreibweise: `#` Ziffer, `?` Buchstabe, `*` Buchstabe oder Ziffer, alles andere fester Text | Amazon `###-#######-#######`, Pro `300#####`, looxis.fr `700######`, Shop `***-****-****`, masterpics `**********`, EOCS-ID `######` |
| Vorsilbe (optional) | darf vor der Nummer stehen und gehört nicht zur Nummer | `BEST-PRO` (Pro) |
| Buchstaben | nur Großbuchstaben (kleine werden umgewandelt) oder Groß- und Kleinbuchstaben | Shop: nur Großbuchstaben; masterpics: beide |
| Muss enthalten | Haken: eine Ziffer, ein Buchstabe, ein Kleinbuchstabe, ein Großbuchstabe | Shop: Ziffer und Buchstabe; masterpics: alle vier |
| Interne EOCS-Nummer | wird erst auf Klick geladen und über die EOCS-ID abgefragt | nur die EOCS-ID |
| Beispiele, die passen müssen | 1 bis 5 | `402-1234567-1234567` |
| Beispiele, die nicht passen dürfen | optional | `402-1234567-123456` |
Mindestens ein Format; das Format, das auch die Amazon-Nachricht für die Bestellnummer nutzt, ist als „Amazon“ gekennzeichnet (genau eines).

### Artikelnummern (SKU) im Kundentext
| Feld | Standard |
|---|---|
| Stichwörter (vor der Nummer) | SKU, Artikelnummer, Artikel-Nr., Art.-Nr., Art. Nr., Artnr, Produktnummer, Produkt-Nr., item no, article no |
| Anzahl Ziffern | 5 |
| Höchstzahl erkannter Nummern je Ticket | 3 |
Eine Zahl ohne Stichwort wird nie als Artikelnummer erkannt.

### Erkennung ausprobieren
Ein Textfeld: Text einfügen, „Ausprobieren“ zeigt die erkannten Bestellnummern (mit Name des Formats und ob sie automatisch geladen würden) und die erkannten Artikelnummern, **mit den noch nicht gespeicherten Werten** neben den gespeicherten. Es wird nichts gespeichert.

### Prüfung vor dem Speichern
- Der Aufbau besteht nur aus `#`, `?`, `*` und festem Text (Buchstaben, Ziffern, `-`, `/`, `.`), 4 bis 40 Zeichen lang und enthält mindestens 4 Stellen mit Zeichen, die keine reinen Platzhalter `*` sind, oder mindestens 6 Zeichen insgesamt (ein Format aus wenigen Platzhaltern würde fast jede Zahl erfassen).
- Jedes „muss passen“-Beispiel passt zum Aufbau und zu den Haken; jedes „darf nicht passen“-Beispiel passt nicht; ein Beispiel passt zu keinem früheren Format (sonst Überschneidung, mit Hinweis auf das andere Format).
- Namen sind eindeutig und nicht leer; genau ein Format ist „Amazon“; die Anzahl Ziffern liegt zwischen 3 und 10, die Höchstzahl zwischen 1 und 10; Stichwörter mindestens 2 Zeichen.
- Bei jedem Fehler wird nichts gespeichert, die Stelle ist markiert, die Eingabe bleibt.

## Acceptance Criteria
- [ ] Angenommen ich öffne „Einstellungen > Erkennungsregeln“, dann sehe ich die sechs Formate im Aufbau mit Namen, Haken, Beispielen und den Artikelnummer-Einstellungen; ohne Admin-Recht sind alle Felder schreibgeschützt.
- [ ] Angenommen ich habe nichts geändert, dann erkennt die App Bestellnummern und Artikelnummern genau wie bisher (die Standardformate sind die heutigen Muster).
- [ ] Angenommen ich ergänze ein neues Format mit Beispielen und speichere, dann wird eine solche Nummer im Ticket erkannt, mit meinem Namen angezeigt und (je nach Haken) automatisch geladen.
- [ ] Angenommen ich ändere die Reihenfolge, dann gilt bei Nummern, die zu zwei Formaten passen könnten, das erste.
- [ ] Angenommen ein „muss passen“-Beispiel passt nicht zum Aufbau oder ein „darf nicht passen“-Beispiel passt doch, wenn ich speichere, dann wird abgelehnt und die Stelle genannt.
- [ ] Angenommen ein Beispiel passt zu zwei Formaten, dann wird abgelehnt mit dem Hinweis, welche Formate sich überschneiden.
- [ ] Angenommen ich ändere Stichwörter, Ziffernanzahl oder Höchstzahl, dann gilt es sofort für die Erkennung im Kundentext (z. B. Stichwort „Teilenummer“ und 6 Ziffern).
- [ ] Angenommen ich füge einen Text in „Ausprobieren“ ein, dann sehe ich die erkannten Bestellnummern (Format, automatisch laden ja/nein) und Artikelnummern mit den ungespeicherten Werten neben den gespeicherten, und nichts wird gespeichert.
- [ ] Angenommen ich lösche das Format „Amazon“ oder alle Formate, dann wird abgelehnt.
- [ ] Angenommen ein Format ist als „Interne EOCS-Nummer“ gekennzeichnet, dann wird eine solche Nummer nicht automatisch geladen und über die EOCS-ID abgefragt.
- [ ] Angenommen die Amazon-Nachricht nennt die Bestellnummer, dann erkennt die App sie mit dem Aufbau des Formats „Amazon“.
- [ ] Angenommen die Pseudonymisierung prüft eine Zahl, dann behandelt sie jede Nummer, die zu einem Format passt, als Bestellnummer (nicht als Telefonnummer).
- [ ] Angenommen die Datenbank ist nicht erreichbar oder ein Wert unbrauchbar, dann gelten die Standardformate.
- [ ] Verlauf, Wiederherstellen, Zurücksetzen auf Standard und der Schutz vor gleichzeitigem Speichern funktionieren wie bei PROJ-27.

## Edge Cases
- Ein Aufbau aus nur Platzhaltern (`******`) erfasst fast jede Zahl: wird abgelehnt.
- Ein Format, das ein anderes ganz einschließt (z. B. 6 Ziffern und 7 Ziffern): erlaubt, solange Beispiele sich nicht überschneiden; die Erkennung verlangt Grenzen (keine Treffer mitten in längeren Zahlen).
- Sonderzeichen im festen Text werden wie Text behandelt, nie als Muster.
- Eine Nummer mit Leerzeichen, `#` oder Vorsilbe beim Eintippen (Normalisierung bleibt wie bisher: Leerzeichen und `#` entfallen, die Vorsilbe wird abgeschnitten).
- Ein Beispieltext mit sehr vielen Zahlen oder sehr lang beim Ausprobieren: begrenzt auf 10.000 Zeichen.
- Zwei Admins ändern gleichzeitig: wie bei PROJ-27.
- Ein später gelöschtes Format steht noch in einem Verlauf oder einer Analyse: die Analyse behält den gespeicherten Namen.

## Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Kurzschreibweise `#`, `?`, `*` mit Beispielen statt regulärer Ausdrücke | Fachanwender können sie sicher pflegen; feste Ziffern wie „300“ bleiben möglich | 2026-10-17 |
| Beispiele (müssen passen / dürfen nicht passen) prüfen jedes Format vor dem Speichern | Fehlerhafte Formate werden beim Eintragen erkannt, nicht im Betrieb | 2026-10-17 |
| Überschneidungen zwischen Formaten werden abgelehnt | Eine Nummer soll genau einem Kanal zugeordnet sein | 2026-10-17 |
| Reihenfolge verschiebbar, das erste passende Format zählt | Entspricht dem bisherigen Verhalten | 2026-10-17 |
| Ein Haken „Interne EOCS-Nummer“ statt zweier getrennter Schalter | Auf Klick laden und Abfrage über die ID gehören zusammen | 2026-10-17 |
| Genau ein Format ist „Amazon“ | Die Amazon-Nachricht braucht den Aufbau der Amazon-Nummer | 2026-10-17 |
| Artikelnummer nur mit Stichwort; Ziffernanzahl und Höchstzahl einstellbar | Eine nackte Zahl wäre zu unsicher (PLZ, Beträge) | 2026-10-17 |
| „Erkennung ausprobieren“ an eingefügtem Text | Wirkt sofort, braucht keinen Zammad-Abruf | 2026-10-17 |

## Tech Design (Solution Architect)

### Backend nötig?
Ja, mit der Datenbank und dem Verzeichnis der Einstellungsgruppen aus PROJ-27. Keine neuen Pakete.

### A) Bausteine der Oberfläche
```
Seite „Einstellungen“ (Übersicht)
+-- Karte „Erkennungsregeln“ (neu)

Seite „Einstellungen > Erkennungsregeln“
+-- Ausprobieren: Textfeld + Knopf, Ergebnis zwei Spalten (gespeichert | mit Änderung)
+-- Abschnitt „Bestellnummern-Formate“
|   +-- je Format eine Karte: Name, Aufbau, Vorsilbe, Buchstaben, Haken, Beispiele
|       (Pfeile „nach oben/unten“, „Format entfernen“), „Format hinzufügen“
+-- Abschnitt „Artikelnummern im Kundentext“ (Stichwörter-Liste, zwei Zahlen)
+-- je Gruppe: Verlauf, „Auf Standard zurücksetzen“; Speichern (nur Admins)
```
Wiederverwendet werden Karte, Hinweis, Knopf, Listenbaustein (Stichwörter) und der Gruppenbaustein aus PROJ-27; neu ist die Formatkarte.

### B) Datenmodell
Keine neuen Tabellen: Die Gruppen `order_formats`, `sku_keywords`, `sku_digits`, `sku_max` liegen in den vorhandenen Einstellungstabellen. Ein Format besteht aus Name, Aufbau, Vorsilbe, Buchstabenart, vier Haken, dem Kennzeichen „Interne EOCS-Nummer“, „Amazon“ und den beiden Beispiellisten.

### C) Technische Entscheidungen
- **Der feste Katalog der Formate im Code wird ein Wert mit Formaten aus den Einstellungen:** `OrderNumberFormat` ist heute eine feste Aufzählung; sie wird ein Objekt (Schlüssel, Name, Muster, Kennzeichen), das ein Baustein aus den Einstellungen erzeugt. Die Erkennung (`OrderNumberDetector`), der EOCS-Abruf, die Pseudonymisierung und die Amazon-Nachricht fragen diesen Baustein statt der Aufzählung.
- **Aus Aufbau wird ein Muster:** Ein einzelner Baustein baut aus der Kurzschreibweise, der Vorsilbe und den Haken das Muster (mit Grenzen, damit nichts mitten in längeren Zahlen trifft). Texte werden immer gequotet; ein Eintrag kann nie als Muster wirken.
- **Prüfung über Beispiele und Überschneidung** in der Prüfregel der Gruppe, serverseitig, vor jedem Speichern.
- **Artikelnummern:** `SkuDetector` liest Stichwörter, Ziffernanzahl und Höchstzahl aus den Einstellungen und baut daraus sein Muster.
- **Standardwerte** bleiben im Code und entsprechen den heutigen Mustern; Tests gegen die bestehenden Beispiele sichern die Gleichheit.
- **Rückfall:** Bei Datenbank- oder Wertfehler gelten die Standardformate.

### D) Abhängigkeiten
Keine neuen Pakete.

## Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Aufzählung der Formate im Code wird zu einem Objekt, das ein Baustein aus den Einstellungen erzeugt | Neue Formate ohne Entwickler; alle Stellen fragen denselben Baustein | 2026-10-17 |
| Mustererzeugung aus Kurzschreibweise in einem eigenen, einzeln testbaren Baustein | Texte werden gequotet, nichts kann als Muster wirken | 2026-10-17 |
| Beispiel- und Überschneidungsprüfung als Prüfregel der Gruppe | Fehlerhafte Formate erreichen nie den Betrieb | 2026-10-17 |
| Standardformate im Code, Rückfall bei Fehlern | Die Erkennung arbeitet nie ohne Formate | 2026-10-17 |

## Open Questions
- [ ] Soll der Verlauf eines Formats auch festhalten, wer es angelegt hat (nicht nur wer die Gruppe zuletzt gespeichert hat)? Vorschlag: nein, der Verlauf der Gruppe genügt.
- [ ] Sollen Aufbau-Beispiele schon beim Tippen geprüft werden (ohne Speichern)? Vorschlag: nein, geprüft wird beim Speichern und im „Ausprobieren“.

## Implementation Notes
- **Neu:** `OrderNumberFormat` (jetzt ein Objekt statt einer Aufzählung: Muster, Haken, Aufbau → Muster), `OrderFormats` (Formate aus den Einstellungen, Standardformate), `OrderFormatRules` (Bereinigen und Prüfen der Formatliste samt Beispiel- und Überschneidungsprüfung), `DetectionSettingsRequest`/`TryDetectionRequest`, Seite `settings/detection`, Baustein `components/settings/formats`, Alpine-Komponente `formatList`, vier Gruppen (`order_formats`, `sku_keywords`, `sku_digits`, `sku_max`), Routen `settings.detection*`.
- **Geändert:** `OrderNumberDetector` (nimmt die Formate aus den Einstellungen, `using()` für ungespeicherte Werte), `OrderNumber`, `SkuDetector` (Stichwörter, Ziffernanzahl und Höchstzahl aus den Einstellungen, `with()` zum Ausprobieren), `AmazonNotice`/`MailRules` (Aufbau der Bestellnummer aus dem Format „Amazon“), `EocsClient` (Format für Reklamationsaufträge), `SettingGroup` (Typ `FORMATS`, Mindestwert bei Zahlen).
- **Abweichung vom Gespräch:** Die Kurzschreibweise nutzt `#` (Ziffer), `?` (Buchstabe), `*` (Buchstabe oder Ziffer) statt `9`/`A`/`X`, damit feste Ziffern wie „300“ eindeutig bleiben.
- **Verhalten wie bisher:** Die Haken („muss enthalten“) werden nach dem Muster geprüft (nicht im Muster), die interne EOCS-Nummer darf nicht an „.“ oder „,“ grenzen (Beträge, Daten), alle anderen Formate wie bisher nur nicht an Buchstaben, Ziffern oder „-“. Die bestehenden Tests (PROJ-6, 7, 36, 37, 38) laufen mit den Standardformaten unverändert grün, nur der Test der getippten Nummern in PROJ-7 vergleicht jetzt den Schlüssel des Formats statt der Aufzählung.
- **Tests:** `PROJ-49-DetectionSettingsTest` (31).
- **Offen für `/qa`:** Bedienung im Browser (Format hinzufügen, verschieben, entfernen, Haken, Ausprobieren), 375/768/1440 px.

## QA Test Results (2026-10-17)
**Ergebnis: bereit (keine Critical-/High-Fehler).**

- **Akzeptanzkriterien und Randfälle:** Alle Akzeptanzkriterien durch Tests abgedeckt (20 Tests). Beim QA ergänzt: Test, dass eine Zahl, die zu einem (neuen) Format passt, nicht als Telefonnummer pseudonymisiert wird (Kriterium Pseudonymisierung war bisher nicht belegt).
- **Sicherheit:** Änderungen nur für Admins (Formular-Anfrage prüft zuerst, nicht Admin → 403, durch Test belegt); Anfragen ohne CSRF-Token werden mit 419 abgelehnt (live geprüft); Ausgaben der Einstellungsseiten sind mit `{{ }}` escaped, einzige HTML-Ausgabe ist der bereinigte Verlauf der Ausprobieren-Ansicht (derselbe Sanitizer wie die Ticketansicht); der Anbieterschlüssel erscheint nirgends (Test).
- **Regression:** Gesamtsuite 1659 Tests, 1657 grün, 2 übersprungen.
- **Nicht durchgeführt:** eigener Browser-Durchgang (Chrome/Firefox/Safari, 375/768/1440 px). Es gab keinen Browser in der QA-Umgebung; ein schneller Sichttest der Oberfläche liegt vom Anwender vor (2026-10-17).
- **Beobachtungen (Low):** Die Ticketseite braucht mit den neuen Zammad-Abfragen für zusammengehörige Tickets rund 4 Sekunden (Ticket 2139944, mehrere Zammad-Aufrufe nacheinander); bei Bedarf später parallelisieren oder Teile nachladen. Ohne gewählten Namen sind Ticketseiten und Einstellungen ansehbar; das entspricht dem bestehenden Verhalten (Auswahl erzwingt nur Aktionen).
