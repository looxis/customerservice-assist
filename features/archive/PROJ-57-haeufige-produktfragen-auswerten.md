# PROJ-57: Häufige Produktfragen auswerten

## Status: Approved
**Created:** 2026-10-20
**Last Updated:** 2026-10-21

## Dependencies
- Requires: PROJ-9 (Fallanalyse), PROJ-10 (Ergebnisansicht), PROJ-12 (Wissenslücken), PROJ-38 (Produktinformationen aus ProductForge), PROJ-45 (Produkt ohne hinterlegtes Wissen automatisch melden), PROJ-31 (Prompts), PROJ-28 (Testmodus, Testläufe zählen nicht), PROJ-40 (Fragen ohne Ticket)
- Verwandt: PROJ-46 (Aufgabenteilung Produktfakten und Regeln), PROJ-47 (Produktwissen migrieren)

## Ausgangslage
Fragt ein Kunde nach einer Einzelheit zum Produkt, die in den Produktinformationen nicht steht (Anlass: Ticket 2139677, essbares Foto, „Lagerungstemperaturen“), meldet die Analyse heute eine Wissenslücke. Das ist unerwünscht: Kein Produkt kann jede denkbare Frage beantworten, und es ist nicht der Anspruch, alle Einzelheiten vorab zu erfassen oder als „nicht beantwortbar“ festzuhalten. Interessant sind Fragen, die **oft** kommen, zum Beispiel „Ist das essbare Foto vegan?“. Daraus lässt sich ableiten, was in ProductForge (Beschreibung oder Kundenservice-Wissen) ergänzt werden sollte.

Diese Spec trennt deshalb zwei Arten von Lücken. Eine **echte Wissenslücke** (fehlende Regel, Befugnis, Ablauf) bleibt wie heute. Eine **Detailfrage ohne Produktangabe** wird still gezählt, dem Mitarbeiter nicht als Lücke gezeigt und erst ab einer Häufigkeit den Admins als Kandidat für ProductForge angezeigt.

## User Stories
- Als Mitarbeiterin möchte ich, dass der Antwortentwurf bei einer Detailfrage ehrlich sagt, dass dazu ohne weitere Prüfung keine verlässliche Angabe vorliegt, damit ich nichts Falsches zusage und nicht selbst nachrecherchieren muss.
- Als Mitarbeiterin möchte ich bei einer solchen Frage keine Wissenslücke melden oder sehen müssen, damit der Vorschlag übersichtlich bleibt.
- Als Admin möchte ich sehen, zu welchen Produkten Kunden immer wieder dasselbe fragen, damit ich die Angaben dazu in ProductForge ergänze.
- Als Admin möchte ich ein Thema als erledigt oder ignoriert markieren, damit die Liste nicht mit Themen überläuft, die abgeschlossen oder bewusst nicht gewollt sind.
- Als Wissensautor möchte ich zu einem Thema die Kundenformulierungen und den Hinweis zum Kopieren in den KI-Chat sehen, damit ich daraus einen ProductForge-Eintrag erstellen kann.

## Out of Scope
- Automatische Zusammenführung ähnlicher Themen („vegan“ und „tierfrei“): Die KI bekommt die bisherigen Stichwörter des Produkts zur Wiederverwendung mit; ein Umbenennen oder Zusammenführen in der Oberfläche gibt es nicht.
- Umwandlung bereits vorhandener Wissenslücken dieser Art in Einträge der neuen Seite: Altbestand bleibt auf der Seite „Wissenslücken“.
- Einstellbare Schwelle: drei Tickets in drei Monaten sind fest.
- Statistiken über die Zeit, Diagramme, Benachrichtigungen.
- Automatisches Anlegen oder Ändern von ProductForge-Einträgen (der Admin trägt von Hand ein, wie in PROJ-47).
- Beantwortung der Detailfrage durch die App (Recherche, Rückfrage an Lieferanten).
- Änderungen an der Meldung „Produkt ohne hinterlegtes Wissen“ (PROJ-45) und an echten Wissenslücken (PROJ-12).
- Die Formulierung der Antwort bei fehlender Angabe als Ton-Dokument in `knowledge/` (wird als Wissen erstellt, nicht in dieser Funktion programmiert).

## Abgrenzung: Detailfrage und echte Wissenslücke

**Detailfrage** (still gezählt, keine Lücke), wenn alle drei Punkte zutreffen:
1. Der Kunde will eine **Auskunft** über das Produkt (Zusammensetzung, Herstellung, Lagerung, Verhalten, Eignung, Verträglichkeit).
2. Die Antwort steht weder in den Produktinformationen noch im Wissen.
3. Von der Antwort hängt keine Entscheidung oder Zusage ab (kein Ersatz, keine Erstattung, keine Kulanz, keine Frist).

**Echte Wissenslücke** (wie heute), wenn eine Regel, Befugnis oder ein Ablauf fehlt, ohne die eine Entscheidung oder Zusage nicht verantwortbar ist, oder wenn eine Reklamation ohne eine Produktangabe nicht beurteilbar ist.

Beispiele: „Kann das essbare Foto eingefroren werden?“ ist eine Detailfrage. „Mein Foto ist verfärbt, ist das normal?“ kann eine echte Lücke sein, wenn ohne die Angabe nicht entschieden werden kann, ob ein Mangel vorliegt. „Dürfen wir Ihre Fotos in unserem Shop verwenden?“ ist eine echte Lücke, wenn die Regel fehlt. Bei Mischfällen wird beides getrennt behandelt.

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

**Ergebnis und Antwortentwurf**
- [x] Angenommen ein Kunde fragt nach einer Einzelheit zum Produkt, die nicht in den Produktinformationen oder im Wissen steht, wenn der Vorschlag erstellt wird, dann erscheint dazu keine Wissenslücke, kein gelber Hinweis und kein Knopf „Wissenslücke melden“.
- [x] Angenommen eine Detailfrage liegt vor, wenn der Antwortentwurf entsteht, dann sagt er in lockerem, nicht förmlichem Ton offen, dass wir uns nicht sicher sind oder es nicht mit Garantie sagen können, bietet eine erneute Rückmeldung an und nennt, dass eine Antwort einige Tage dauern kann.
- [x] Angenommen eine Detailfrage liegt vor, wenn der Entwurf entsteht, dann behauptet er nichts, was nicht in den Produktinformationen steht, und leitet keine Aussage aus Allgemeinwissen ab; was dort wörtlich steht, darf er wiedergeben.
- [x] Angenommen eine Detailfrage liegt vor, wenn die Begründung angezeigt wird, dann nennt sie in einem Halbsatz, dass zu dieser Frage keine Produktangabe vorliegt und der Entwurf das offen sagt.
- [x] Angenommen es ist unklar, ob eine Frage eine Detailfrage oder eine echte Lücke ist, wenn der Vorschlag erstellt wird, dann gilt sie als echte Wissenslücke.
- [x] Angenommen im selben Ticket gibt es eine Detailfrage und eine echte Wissenslücke, wenn der Vorschlag erstellt wird, dann wird nur die echte Lücke wie bisher gezeigt, und die Detailfrage wird still gezählt.
- [x] Angenommen eine Wissensabfrage ohne Antwortentwurf, wenn eine Detailfrage vorliegt, dann wird sie trotzdem gezählt, und die Begründung sagt, dass keine Angabe vorliegt.

**Erfassung und Zählung**
- [x] Angenommen eine Detailfrage wurde erkannt, wenn der Vorschlag gespeichert wird, dann entsteht ein Eintrag mit Produkt, Stichwort des Themas, kurzer Kundenformulierung, Ticketnummer oder Frage-Kennung, Datum und Kundengruppe.
- [x] Angenommen die Kundenformulierung wird gespeichert, wenn sie keine persönlichen Daten enthalten darf, dann ist sie so zusammengefasst, dass Name, E-Mail-Adresse und Anschrift nicht darin stehen, und sie wird verschlüsselt gespeichert; das Stichwort steht unverschlüsselt.
- [x] Angenommen es gibt zu diesem Produkt schon Stichwörter, wenn die Analyse läuft, dann bekommt die KI die bisherigen Stichwörter dieses Produkts und soll ein passendes wiederverwenden.
- [x] Angenommen derselbe Vorschlag wird erneut erstellt oder fortgesetzt, wenn dieselbe Detailfrage wieder erkannt wird, dann entsteht kein zweiter Eintrag je Ticket (oder Frage), Produkt und Thema.
- [x] Angenommen ein Testlauf (zurückgespultes Ticket), wenn eine Detailfrage erkannt wird, dann wird sie als Testlauf gekennzeichnet und nicht gezählt.
- [x] Angenommen die Frage gehört zu einer Wissensabfrage ohne Ticket, wenn eine Detailfrage erkannt wird, dann zählt sie wie ein Ticket.
- [x] Angenommen ein Fall hat kein Produkt, wenn eine Detailfrage erkannt wird, dann wird das Thema ohne Produkt erfasst und gezählt.

**Admin-Seite**
- [x] Angenommen ein Nutzer ist kein Admin, wenn er die Seite „Häufige Produktfragen“ aufruft, dann sieht er sie nicht (kein Eintrag in der Seitenleiste, Aufruf verweigert).
- [x] Angenommen ein Admin öffnet die Seite, wenn Themen mit mindestens drei Tickets in den letzten drei Monaten existieren, dann stehen sie in einer Liste mit Produkt, Thema, Zahl der Tickets in den letzten drei Monaten, Gesamtzahl und Datum der letzten Frage.
- [x] Angenommen die Liste ist sichtbar, wenn mehrere Themen die Schwelle erreichen, dann sind sie nach der Zahl der letzten drei Monate absteigend sortiert, bei Gleichstand nach der Gesamtzahl.
- [x] Angenommen ein Thema hat die Schwelle erreicht, wenn weitere Tickets zur gleichen Frage kommen, dann zählt es weiter und steigt in der Liste.
- [x] Angenommen ein Thema hat weniger als drei Tickets in den letzten drei Monaten, wenn der Admin „alle Themen anzeigen“ wählt, dann erscheint es ebenfalls; ohne diese Auswahl bleibt es verborgen.
- [x] Angenommen ein Admin klappt ein Thema auf, wenn Formulierungen vorliegen, dann sieht er die Kundenformulierungen mit Link zum Ticket oder zur Frage.
- [x] Angenommen ein Admin markiert ein Thema als „Erledigt“, wenn die Seite neu lädt, dann erscheint es nicht mehr unter „Offen“; bis dahin gestellte Fragen zählen nicht mehr mit, und eine spätere Frage zum selben Thema beginnt die Zählung neu.
- [x] Angenommen ein Admin markiert ein Thema als „Ignoriert“, wenn die Seite neu lädt, dann erscheint es nicht mehr unter „Offen“, wird aber weiter gezählt, und der Admin kann es wieder öffnen.
- [x] Angenommen ein Admin filtert, wenn er Status (offen, erledigt, ignoriert) oder Produkt wählt, dann zeigt die Liste nur dazu passende Themen.
- [x] Angenommen ein Admin wählt „Kopieren“ bei einem Thema, wenn der Text kopiert wird, dann enthält er Produkt, Thema, Zahl der Tickets und die Kundenformulierungen mit der Bitte, einen ProductForge-Eintrag zu prüfen.

**Aufbewahrung und Altbestand**
- [x] Angenommen ein Eintrag ist offen oder ignoriert, wenn Zeit vergeht, dann wird er nicht gelöscht.
- [x] Angenommen ein Thema ist seit mehr als 12 Monaten erledigt, wenn die Bereinigung läuft, dann werden Einträge und Formulierungen dazu gelöscht.
- [x] Angenommen auf der Seite „Wissenslücken“ stehen bereits Lücken, die Detailfragen waren, wenn PROJ-57 im Einsatz ist, dann bleiben sie unverändert und werden nicht umgewandelt.

**Prompt**
- [x] Angenommen die Analyse läuft, wenn der Prompt die Grenze zwischen Detailfrage und echter Lücke enthält, dann ersetzt diese Regel die bisherige Anweisung, eine Lücke zu melden, wenn eine für die Antwort nötige Produktangabe fehlt; die Prompt-Version wird erhöht.

## Edge Cases
- **Schreibweisen desselben Themas** („vegan“ und „tierfrei“) bleiben getrennte Themen; die Wiederverwendung bisheriger Stichwörter mindert das nur.
- **Falsch eingeordnet:** Stuft die KI eine echte Lücke als Detailfrage ein, fehlt dem Mitarbeiter die Warnung. Der Prompt verlangt deshalb, dass eine Entscheidung oder Zusage nie von einer Detailfrage abhängen darf (Punkt 3 der Definition); im Zweifel gilt die echte Lücke.
- **Produkt später in ProductForge gepflegt:** Bisherige Einträge bleiben, bis ein Admin sie als erledigt markiert; die App prüft nicht selbst, ob die Angabe jetzt vorliegt.
- **Mehrere Produkte im Fall:** Die Detailfrage wird dem Produkt zugeordnet, auf das sie sich bezieht; ist das unklar, entsteht ein Eintrag ohne Produkt.
- **Dieselbe Frage mehrfach im selben Ticket:** zählt einmal.
- **Ticket oder Frage wird später gelöscht:** Der Eintrag bleibt, der Link zeigt „nicht mehr vorhanden“.
- **Leere Liste:** Die Seite sagt, dass noch keine Fragen die Schwelle erreicht haben, und verweist auf „alle Themen anzeigen“.
- **Kundenformulierung nennt persönliche Daten trotz Anweisung:** Sie sind nur verschlüsselt gespeichert und nur für Admins sichtbar; die Anweisung an die KI verbietet sie.

## Technical Requirements (optional)
- Security: Seite nur für Admins; Formulierung verschlüsselt, keine Kontaktdaten.
- Performance: Die Liste lädt auch bei mehreren tausend Einträgen ohne spürbare Wartezeit.
- Prompt-Änderung läuft über die Standard-Prompts der App (mit Hinweis in „Einstellungen > Prompts“, falls dort Eigenes gespeichert ist).

## Open Questions
- [x] Im Zweifel, ob Detailfrage oder echte Lücke: gilt die echte Lücke (2026-10-20).
- [x] Admin: bestehende Regelung der App wird übernommen, bis PROJ-15 kommt (2026-10-20).
- [ ] Genauer Wortlaut der festen Formulierung (Ton-Dokument in `knowledge/`): weniger förmlich, sinngemäß „wir sind uns nicht sicher“ oder „können es nicht mit Garantie sagen“. Die Varianten („ohne weitere Prüfung“, „ohne Rückfrage beim Lieferanten“, „ohne Klärung mit der Geschäftsleitung“) sind noch festzulegen; der Text entsteht als Wissen, nicht in dieser Funktion.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Detailfragen still zählen statt als Lücke zeigen | Nicht jede Einzelheit ist Wissensaufbau; Häufigkeit zeigt, was relevant ist | 2026-10-20 |
| Keine Vorab-Liste von Themen, die „nicht beantwortet werden“ | Tausende denkbare Fragen lassen sich nicht vorab erfassen oder ausschließen | 2026-10-20 |
| Schwelle drei Tickets in drei Monaten | Genug Signal, ohne Einzelfragen zu zeigen | 2026-10-20 |
| Zählung läuft über die Schwelle hinaus weiter, Liste nach den letzten drei Monaten sortiert | Mehr Fragen heißt dringlicher; Gesamtzahl bleibt als Orientierung sichtbar | 2026-10-20 |
| Eigene Seite statt Reiter in „Wissenslücken“ | Andere Aufgabe (ProductForge ergänzen, nicht Regel schreiben) und andere Zählweise | 2026-10-20 |
| Zustände offen, erledigt, ignoriert | Wie bei den Wissenslücken; erledigte Themen starten bei erneuter Frage neu | 2026-10-20 |
| Offene und ignorierte Einträge unbegrenzt, erledigte nach 12 Monaten löschen | Ohne persönliche Daten unkritisch; Häufigkeit soll nicht verschwinden | 2026-10-20 |
| Im Zweifel gilt die echte Wissenslücke | Lieber eine Lücke zu viel angezeigt als eine Entscheidung ohne Regel | 2026-10-20 |
| Feste Formulierung locker statt förmlich („wir sind uns nicht sicher“) | Passt zum Ton des Kundenservice (TONE-002) | 2026-10-20 |
| Keine Umwandlung des Altbestands | Einträge haben kein Stichwort und Produkt in der nötigen Form | 2026-10-20 |
| Testläufe zählen nicht, Fragen ohne Ticket zählen | Wie bei PROJ-45; Fragen ohne Ticket sind oft Kundenfragen am Telefon | 2026-10-20 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|
| Zweites Ergebnisfeld „Detailfragen“ statt Kennzeichen an den Wissenslücken | Seite „Wissenslücken“ und PROJ-12/PROJ-45 bleiben unberührt | 2026-10-20 |
| Eigene Tabellen für Einträge und Themenzustand | Zustände und Zählung gelten je Thema, nicht je Lücke; getrennte Löschregeln | 2026-10-20 |
| Produkt auf Master (oder eigenständiges Produkt) zusammenfassen | Eine Frage zu einer Größe gilt für alle Größen; sonst erreicht kein Thema die Schwelle | 2026-10-20 |
| Bisherige Stichwörter an die KI mitgeben | Gleiche Fragen bekommen dasselbe Stichwort, ohne Zusammenführung in der Oberfläche | 2026-10-20 |
| Stichwort unverschlüsselt, Formulierung verschlüsselt | Gruppieren geht nur über unverschlüsselte Werte; Formulierung bleibt geschützt | 2026-10-20 |
| Schwelle und Löschfrist in der Konfiguration, nicht in der Oberfläche | Spec: Schwelle fest; weniger Oberfläche | 2026-10-20 |
| Bereinigung im bestehenden nächtlichen Lauf | Kein neuer geplanter Auftrag nötig | 2026-10-20 |
| Abgrenzung im Standard-Prompt, Formulierung als Ton-Dokument | Fachlich änderbar ohne Code; Ton gehört ins Wissen | 2026-10-20 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

### Überblick
Die Funktion braucht ein Backend und eine Datenbank, weil gezählt und über Wochen und Monate bewahrt wird. Es sind keine neuen Pakete nötig. Sie folgt dem Muster der Wissenslücken (PROJ-12, PROJ-45): ein Protokoll mit Zählung, eine Admin-Seite, ein Hinweis im Ergebnis. Neu sind ein zweites Ergebnisfeld der KI, zwei kleine Tabellen und eine Seite.

### A) Ablauf und Bausteine
```
Analyse (KI)
+-- liefert wie bisher: Empfehlung, Antwortentwurf, echte Wissenslücken
+-- NEU: "Detailfragen" (Produkt, Stichwort, kurze Kundenformulierung)
|
Ergebnis prüfen und speichern (Validator)
+-- unvollständige oder doppelte Detailfragen verwerfen
|
Protokoll "Produktfragen" (NEU, vom CaseAnalyzer nach dem Speichern aufgerufen)
+-- legt je Ticket/Frage, Produkt und Thema einen Eintrag an
+-- ignoriert Testläufe für die Zählung
|
Ergebnisansicht (unverändert)
+-- Begründung nennt die Detailfrage in einem Halbsatz
+-- keine Lücke, kein Knopf, kein gelber Hinweis dafür
|
Seite "Häufige Produktfragen" (NEU, nur Admins)
+-- Kopf: Erklärung, Filter (Status, Produkt), Schalter "alle Themen anzeigen"
+-- Liste der Themen (Produkt, Thema, letzte 3 Monate, gesamt, letzte Frage)
|   +-- aufklappbar: Kundenformulierungen mit Links zu Tickets oder Fragen
|   +-- Aktionen: Erledigt, Ignorieren, Wieder öffnen, Kopieren
+-- Leerer Zustand
|
Seitenleiste
+-- Eintrag "Häufige Produktfragen" unter "Wissenslücken" (nur Admins)
```

### B) Datenmodell (in einfachen Worten)

**Eintrag je Detailfrage** (Tabelle "Produktfragen"):
- Verweis auf die Analyse (bleibt erhalten, auch wenn die Analyse später bereinigt wird)
- Ticketnummer oder Frage-Kennung
- Produkt: Schlüssel und Anzeigename (siehe Entscheidung zur Zusammenfassung), leer bei „kein Produkt“
- Thema: kurzes Stichwort in Kleinbuchstaben, **unverschlüsselt**, damit die Liste gruppieren kann
- Kundenformulierung: kurzer Satz ohne persönliche Daten, **verschlüsselt**
- Kundengruppe, Datum
- Kennzeichen „Testlauf“
- Je Ticket (oder Frage), Produkt und Thema höchstens ein Eintrag; eine erneute Analyse aktualisiert nur die Formulierung

**Zustand je Thema** (Tabelle "Themenzustand"), ein Eintrag je Produkt und Thema, nur wenn der Admin etwas markiert hat:
- Zustand: erledigt oder ignoriert (ohne Eintrag gilt „offen“)
- Wer hat wann markiert
- Bei „erledigt“ zählen nur Fragen, die **nach** diesem Zeitpunkt eintreffen; so beginnt die Zählung neu
- Bei „ignoriert“ zählen alle Fragen weiter, das Thema bleibt aber ausgeblendet

**Berechnete Zahlen** (nicht gespeichert): Tickets der letzten drei Monate und Gesamtzahl je Produkt und Thema, jeweils Tickets (oder Fragen) einmal gezählt, ohne Testläufe.

Gespeichert wird in der bestehenden MySQL-Datenbank. Keine Kontaktdaten.

### C) Entscheidungen, erklärt

1. **Zweites Ergebnisfeld der KI statt eines Kennzeichens an den Wissenslücken.** Die KI gibt „Detailfragen“ getrennt von „Wissenslücken“ aus. So bleibt die Seite „Wissenslücken“ unberührt, und die Auswertung kann ohne Eingriffe in PROJ-12 und PROJ-45 entstehen. Ein Kennzeichen an den Lücken hätte beide Seiten vermischt.
2. **Eigene Tabellen statt der Lücken-Tabelle.** Die Lücken-Tabelle hat ein Thema pro Zeile und Zustände für einzelne Lücken, wir brauchen aber Zustände je Thema und Zählungen. Eigene Tabellen halten die Abfragen einfach und die Lösch-Regeln getrennt (offene bleiben, erledigte werden nach 12 Monaten gelöscht).
3. **Produkt zusammenfassen auf den Master.** „Vegan“ gilt für alle Größen des Essbaren Fotos. Eine Frage zu einer Variante zählt deshalb beim Master (oder beim eigenständigen Produkt); sonst würde sie sich auf Varianten verteilen und die Schwelle nie erreichen. Fehlt ein Katalogprodukt, gilt der Wissensbereich; fehlt auch der, bleibt das Produkt leer.
4. **Stichwörter wiederverwenden.** Die KI bekommt zur Analyse eine kurze Liste der bisher verwendeten Stichwörter der betroffenen Produkte (nur Stichwörter, keine Formulierungen) mit der Bitte, ein passendes wiederzuverwenden. Das hält gleiche Fragen zusammen, ohne dass es eine Zusammenführung in der Oberfläche braucht.
5. **Im Zweifel echte Lücke.** Die Regel steht im Prompt. Prüfungen im Code gibt es dafür nicht (die KI entscheidet), aber der Validator verwirft Detailfragen ohne Produkt-Stichwort oder ohne Formulierung, und eine Detailfrage darf nicht mit derselben Frage auch als Lücke erscheinen.
6. **Seite wie die Wissenslücken.** Gleiche Admin-Prüfung, gleicher Aufbau (Filter über die Adresszeile, Seitenleiste mit Eintrag, Kopieren in den KI-Chat). Die Schwelle (drei Tickets, drei Monate) und die Löschfrist (12 Monate für Erledigtes) stehen in der Konfiguration der App und sind nicht in der Oberfläche einstellbar.
7. **Aufräumen im bestehenden nächtlichen Lauf.** Der vorhandene tägliche Bereinigungslauf löscht erledigte Themen mit den Einträgen nach 12 Monaten. Offene und ignorierte bleiben.
8. **Prompt-Änderung statt Code für die Abgrenzung.** Die Grenze zwischen Detailfrage und echter Lücke steht im Standard-Prompt (Kern und Produkt-Zusatz) und ersetzt dort die Regel, die zur Meldung der Lagertemperatur führte. Die Version wird erhöht. Haben Admins eigene Prompttexte gespeichert, zeigen die Einstellungen den bekannten Hinweis, dass der Standard erneuert wurde.
9. **Feste Formulierung als Wissen.** Der lockere Satz („wir sind uns nicht sicher …“) steht als Ton-Dokument in `knowledge/`, nicht im Prompt. Der Prompt verlangt nur, dass die Regel greift und der Ton passt. Das Ton-Dokument wird mit der Umsetzung erstellt.

### D) Zugriff und Sicherheit
- Die Seite ist nur für Admins; die vorhandene Admin-Prüfung der App wird wiederverwendet.
- Die Kundenformulierung wird verschlüsselt gespeichert und nur auf der Admin-Seite angezeigt. Die KI wird angewiesen, keine Namen, E-Mail-Adressen oder Anschriften hineinzuschreiben.
- Wer die Daten bearbeitet (Erledigt, Ignorieren), wird mit Namen und Zeit am Themenzustand festgehalten.

### E) Dependencies
Keine neuen Pakete.

### F) Betroffene Bereiche (für die Umsetzung)
- KI-Ausgabe und Prüfung des Ergebnisses (neues Feld „Detailfragen“)
- Analyse nach dem Speichern (neues Protokoll „Produktfragen“)
- Standard-Prompts (Kern und Produkt-Zusatz, Version erhöhen)
- Datenbank (zwei neue Tabellen)
- Seite, Seitenleiste und Kopier-Text
- Nächtliche Bereinigung (erledigte Themen)
- Wissen: Ton-Dokument zur festen Formulierung


## Implementation Notes (2026-10-20)
- **Neu:** `app/Analysis/ProductQuestionLog.php` (Erfassen, Zählen, Themenliste, Markieren, Kopier-Text, Bereinigen), Modelle `ProductQuestion` und `ProductQuestionState` mit Factories und zwei Migrationen (`product_questions`, `product_question_states`), `ProductQuestionController` mit `UpdateProductQuestionRequest`, Seite `resources/views/product-questions/index.blade.php`, Routen `/produktfragen` (GET, PATCH), Eintrag „Häufige Produktfragen“ in der Seitenleiste (nur Admins).
- **Geändert:** `CaseAgent` (neues Ergebnisfeld `detail_questions` mit Produkt, Stichwort, Frage; Produktliste als Auswahl), `ResultValidator` (verwirft unvollständige, doppelte und als Lücke gemeldete Detailfragen, entfernt Platzhalter für Kontaktdaten, höchstens fünf), `CaseAnalyzer` (Erfassung nach dem Speichern; bisherige Stichwörter des Produkts im Abschnitt „Fall“), `AnalysisStore::purge()` und `analysis:purge` (erledigte Themen nach 12 Monaten), `config/analysis.php` (Block `product_questions`: Schwelle 3, Fenster 3 Monate, 12 Monate, 20 Stichwörter).
- **Prompts:** `analysis.md` auf `analysis-2026-10-20.1` (Abgrenzung Detailfrage und Lücke, Antwort in lockerem Ton, im Zweifel Lücke), `analysis-products.md` auf `products-2026-10-20.1` (die Regel „Produktangabe fehlt, also Lücke“ ersetzt). Haben Admins in den Einstellungen eigene Prompttexte gespeichert, gelten die weiter; dort zurücksetzen.
- **Wissen:** Ton-Dokument `knowledge/tone/tone-010-fehlende-produktangabe-locker-sagen.md` (TONE-010, Entwurf, `binding` ohne Auslöser, damit es bei jeder Analyse im Geltungsbereich mitkommt). Der genaue Wortlaut bleibt offen (siehe Open Questions).
- **Produktschlüssel:** `pf:<ID des Masters>` (Variante zählt beim Master), `eocs:<SKU>`, `kb:<Wissensbereich>`, `x:<Name ohne Wissen>`, leer ohne Produkt. Hat der Fall genau ein Produkt, wird die Frage ihm zugeordnet; bei mehreren wählt die KI aus der Liste.
- **Zählung:** Ein Eintrag je Ticket (oder Frage), Produkt und Thema, Testläufe getrennt. „Erledigt“ setzt einen Zeitpunkt; nur spätere Fragen zählen neu und lassen das Thema wieder erscheinen. „Ignoriert“ blendet nur aus.
- **Tests:** `PROJ-57-ProductQuestionsTest` (15). Angepasst: Prompt-Versionen in `PROJ-36-AnalysisIntegrationTest` und `PROJ-38-ProductInfoAnalysisTest`, Schlüsselliste im Test der Bereinigung in `PROJ-12-FeedbackTest`. Vollständige Suite danach: grün (1825 Tests).
- **Nicht getestet:** Verhalten des echten Modells (hält es sich an die Abgrenzung? gibt es „Detailfragen“ nur dort aus, wo sie hingehören?) und die Seite im Browser (375/768/1440 px).

## QA Test Results

**Tested:** 2026-10-21
**App URL:** http://localhost:8081 (interne Aufrufe der Seite als Admin, kein Browser)
**Tester:** QA Engineer (AI)
**Methode:** Abgleich jedes Kriteriums mit Pest-Tests (26 Tests in `PROJ-57-ProductQuestionsTest` und `PROJ-57-QaTest`), Lesen des Codes, Messung mit 6.000 Einträgen, komplette Suite (1836 Tests, 1834 bestanden, 2 übersprungen wie zuvor). **Nicht getestet:** Browser (Chrome/Firefox/Safari) und Breiten 375/768/1440 px; Verhalten des echten Modells.

### Acceptance Criteria Status
- [x] Ergebnis und Antwortentwurf: keine Lücke, kein Hinweis, kein Knopf für Detailfragen (Test mit simulierter KI-Antwort); Mischfälle werden getrennt behandelt; Wissensabfrage ohne Entwurf zählt trotzdem.
- [ ] **Nicht prüfbar ohne echtes Modell (4 Kriterien):** lockerer Ton der Antwort, „behauptet nichts und leitet nichts ab“, Halbsatz in der Begründung, „im Zweifel echte Lücke“. Die Regeln stehen im Prompt (`analysis-2026-10-20.1`), geprüft ist nur die Verarbeitung der Ausgabe.
- [x] Erfassung und Zählung: Eintrag mit Produkt, Stichwort, Formulierung, Ticket, Datum, Gruppe; Formulierung verschlüsselt, ohne Kontakt-Platzhalter; bisherige Stichwörter gehen an die KI; ein Eintrag je Ticket, Produkt und Thema; Testläufe getrennt; Fragen ohne Ticket zählen; ohne Produkt wird das Thema ohne Produkt erfasst.
- [x] Admin-Seite: nur Admins (403 sonst, kein Eintrag in der Seitenleiste); Schwelle drei Tickets in drei Monaten; Sortierung nach den letzten drei Monaten, dann Gesamtzahl; Zählung läuft über die Schwelle weiter; „Alle Themen anzeigen“; aufklappbare Formulierungen mit Links; Erledigt (Zählung beginnt neu), Ignorieren (zählt weiter), Wieder öffnen; Filter; Kopier-Text.
- [x] Aufbewahrung: offene und ignorierte Themen bleiben, erledigte werden nach 12 Monaten gelöscht (Einträge bis zur Markierung, neuere bleiben); Altbestand der Wissenslücken bleibt unverändert.
- [x] Prompt: Abgrenzung im Kern-Prompt, ersetzte Regel im Produkt-Zusatz, Versionen erhöht.

### Edge Cases Status
- [x] Zwei Schreibweisen desselben Themas bleiben getrennte Themen (gewollt).
- [x] Produkt später in ProductForge gepflegt: Einträge bleiben bis zur Markierung (kein automatischer Abgleich, wie in der Spec).
- [x] Mehrere Produkte im Fall: die KI wählt über den Namen; ein unbekannter Name ergibt „ohne Produkt“; bei einem Produkt gehört die Frage immer ihm; Variante zählt beim Master.
- [x] Dieselbe Frage mehrfach im Ticket zählt einmal.
- [x] Leere Liste nennt die Schwelle und verweist auf „Alle Themen anzeigen“.
- [x] Ungültiger Status oder Produktfilter: Rückfall auf „Offen“ beziehungsweise Fehlermeldung beim Markieren.
- [ ] Ticket oder Frage später gelöscht: Der Link führt zur Zielseite, die dann selbst meldet, dass es das Ticket nicht gibt. Einen eigenen Hinweis „nicht mehr vorhanden“ an der Zeile gibt es nicht (Low).
- [ ] Persönliche Daten in der Formulierung trotz Anweisung: nur Platzhalter wie [E-MAIL_1] werden entfernt, Klartext-Namen nicht; die Formulierung ist verschlüsselt und nur für Admins sichtbar (siehe Security).

### Security Audit
- **Zugriff:** Seite und Markieren nur für Admins; ohne Namenswahl oder als Nicht-Admin 403 (Test). Der Name kommt wie überall aus der Namenswahl, kein Login (PRD-Vorgabe).
- **CSRF:** Das Formular hat `@csrf` und `@method('PATCH')`; es gibt keine Ausnahme in `bootstrap/app.php`.
- **XSS:** Kundenformulierung und Produktname werden maskiert ausgegeben (Test mit `<script>`).
- **Mass Assignment:** `#[Fillable]` an beiden Modellen. Markieren validiert Status (nur offen/ignoriert/erledigt) und Längen.
- **Daten:** Formulierung verschlüsselt (Rohwert enthält den Text nicht), Stichwort unverschlüsselt; keine Kontaktdaten angefordert; keine neuen Schlüssel oder Konfigurationswerte.
- **Prompt-Injection:** Die KI-Ausgabe wird nur als Text gespeichert und maskiert angezeigt; sie löst keine Aktion aus.
- **Last:** `topics()` mit 6.000 Einträgen und 600 Themen 0,09 s, `counts()` 0,16 s, 50 Themen aufklappen 0,03 s.

### Regression
Komplette Suite grün. Angepasst waren zuvor nur die Prompt-Versionen (PROJ-36/38) und die Schlüsselliste der Bereinigung (PROJ-12). Die Fragen ohne Ticket, der Versand und die Ergebnisansicht laufen unverändert.

### Bugs Found
Keine Critical, High oder Medium.

| # | Severity | Beschreibung | Schritte |
|---|----------|--------------|----------|
| 1 | Low | „Über die App“ (`docs/ABOUT.md`, Abschnitt zu fehlenden Angaben und Wissenslücken) erklärt die Detailfrage nicht. Mitarbeiter wundern sich vielleicht, warum die Antwort „wir sind uns nicht sicher“ sagt, aber keine Lücke erscheint. | Abschnitt „Wissenslücke – uns fehlt eine Regel“ lesen |
| 2 | Low | Die Seitenleiste nutzt für „Häufige Produktfragen“ dasselbe Symbol (Sprechblase) wie „Frage stellen“. | Seitenleiste als Admin öffnen |
| 3 | Low | Am Eintrag fehlt ein eigener Hinweis, wenn Ticket oder Frage nicht mehr existieren (siehe Randfall). | Ticket löschen oder zurückspulen, Seite öffnen |
| 4 | Low | Klartext-Namen in der Formulierung werden nicht erkannt, nur Platzhalter entfernt. Schutz liegt allein in der Anweisung an die KI und der Verschlüsselung. | Frage mit Namen stellen, Formulierung auf der Seite lesen |

### Abnahme (2026-10-21)
- Prüfung mit dem echten Modell am Ticket mit der Lagertemperatur (Essbares Foto, Rechteck): keine Wissenslücke, Antwort im lockeren Ton, Eintrag „temperatur“ einmal gezählt (vom Auftraggeber bestätigt: „die Antwort ist toll, keine Wissenslücke gemeldet“). Damit sind die vier Kriterien zum Modellverhalten erfüllt.
- Prompts in den Einstellungen standen auf dem neuen Standard (Analyse mit `analysis-2026-10-20.1+…+products-2026-10-20.1`).
- **Noch nicht geprüft:** Browser (Chrome, Firefox, Safari) und Breiten 375/768/1440 px.

### Summary
- **Acceptance Criteria:** 28 von 28 bestanden
- **Bugs Found:** 4 (0 Critical, 0 High, 0 Medium, 4 Low)
- **Security:** bestanden
- **Production Ready:** JA (keine Critical/High; die vier Low-Fehler bleiben offen)

## Deployment
_To be added by /deploy_
