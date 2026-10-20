# PROJ-57: Häufige Produktfragen auswerten

## Status: Planned
**Created:** 2026-10-20
**Last Updated:** 2026-10-20

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
- [ ] Angenommen ein Kunde fragt nach einer Einzelheit zum Produkt, die nicht in den Produktinformationen oder im Wissen steht, wenn der Vorschlag erstellt wird, dann erscheint dazu keine Wissenslücke, kein gelber Hinweis und kein Knopf „Wissenslücke melden“.
- [ ] Angenommen eine Detailfrage liegt vor, wenn der Antwortentwurf entsteht, dann sagt er in lockerem, nicht förmlichem Ton offen, dass wir uns nicht sicher sind oder es nicht mit Garantie sagen können, bietet eine erneute Rückmeldung an und nennt, dass eine Antwort einige Tage dauern kann.
- [ ] Angenommen eine Detailfrage liegt vor, wenn der Entwurf entsteht, dann behauptet er nichts, was nicht in den Produktinformationen steht, und leitet keine Aussage aus Allgemeinwissen ab; was dort wörtlich steht, darf er wiedergeben.
- [ ] Angenommen eine Detailfrage liegt vor, wenn die Begründung angezeigt wird, dann nennt sie in einem Halbsatz, dass zu dieser Frage keine Produktangabe vorliegt und der Entwurf das offen sagt.
- [ ] Angenommen es ist unklar, ob eine Frage eine Detailfrage oder eine echte Lücke ist, wenn der Vorschlag erstellt wird, dann gilt sie als echte Wissenslücke.
- [ ] Angenommen im selben Ticket gibt es eine Detailfrage und eine echte Wissenslücke, wenn der Vorschlag erstellt wird, dann wird nur die echte Lücke wie bisher gezeigt, und die Detailfrage wird still gezählt.
- [ ] Angenommen eine Wissensabfrage ohne Antwortentwurf, wenn eine Detailfrage vorliegt, dann wird sie trotzdem gezählt, und die Begründung sagt, dass keine Angabe vorliegt.

**Erfassung und Zählung**
- [ ] Angenommen eine Detailfrage wurde erkannt, wenn der Vorschlag gespeichert wird, dann entsteht ein Eintrag mit Produkt, Stichwort des Themas, kurzer Kundenformulierung, Ticketnummer oder Frage-Kennung, Datum und Kundengruppe.
- [ ] Angenommen die Kundenformulierung wird gespeichert, wenn sie keine persönlichen Daten enthalten darf, dann ist sie so zusammengefasst, dass Name, E-Mail-Adresse und Anschrift nicht darin stehen, und sie wird verschlüsselt gespeichert; das Stichwort steht unverschlüsselt.
- [ ] Angenommen es gibt zu diesem Produkt schon Stichwörter, wenn die Analyse läuft, dann bekommt die KI die bisherigen Stichwörter dieses Produkts und soll ein passendes wiederverwenden.
- [ ] Angenommen derselbe Vorschlag wird erneut erstellt oder fortgesetzt, wenn dieselbe Detailfrage wieder erkannt wird, dann entsteht kein zweiter Eintrag je Ticket (oder Frage), Produkt und Thema.
- [ ] Angenommen ein Testlauf (zurückgespultes Ticket), wenn eine Detailfrage erkannt wird, dann wird sie als Testlauf gekennzeichnet und nicht gezählt.
- [ ] Angenommen die Frage gehört zu einer Wissensabfrage ohne Ticket, wenn eine Detailfrage erkannt wird, dann zählt sie wie ein Ticket.
- [ ] Angenommen ein Fall hat kein Produkt, wenn eine Detailfrage erkannt wird, dann wird das Thema ohne Produkt erfasst und gezählt.

**Admin-Seite**
- [ ] Angenommen ein Nutzer ist kein Admin, wenn er die Seite „Häufige Produktfragen“ aufruft, dann sieht er sie nicht (kein Eintrag in der Seitenleiste, Aufruf verweigert).
- [ ] Angenommen ein Admin öffnet die Seite, wenn Themen mit mindestens drei Tickets in den letzten drei Monaten existieren, dann stehen sie in einer Liste mit Produkt, Thema, Zahl der Tickets in den letzten drei Monaten, Gesamtzahl und Datum der letzten Frage.
- [ ] Angenommen die Liste ist sichtbar, wenn mehrere Themen die Schwelle erreichen, dann sind sie nach der Zahl der letzten drei Monate absteigend sortiert, bei Gleichstand nach der Gesamtzahl.
- [ ] Angenommen ein Thema hat die Schwelle erreicht, wenn weitere Tickets zur gleichen Frage kommen, dann zählt es weiter und steigt in der Liste.
- [ ] Angenommen ein Thema hat weniger als drei Tickets in den letzten drei Monaten, wenn der Admin „alle Themen anzeigen“ wählt, dann erscheint es ebenfalls; ohne diese Auswahl bleibt es verborgen.
- [ ] Angenommen ein Admin klappt ein Thema auf, wenn Formulierungen vorliegen, dann sieht er die Kundenformulierungen mit Link zum Ticket oder zur Frage.
- [ ] Angenommen ein Admin markiert ein Thema als „Erledigt“, wenn die Seite neu lädt, dann erscheint es nicht mehr unter „Offen“; bis dahin gestellte Fragen zählen nicht mehr mit, und eine spätere Frage zum selben Thema beginnt die Zählung neu.
- [ ] Angenommen ein Admin markiert ein Thema als „Ignoriert“, wenn die Seite neu lädt, dann erscheint es nicht mehr unter „Offen“, wird aber weiter gezählt, und der Admin kann es wieder öffnen.
- [ ] Angenommen ein Admin filtert, wenn er Status (offen, erledigt, ignoriert) oder Produkt wählt, dann zeigt die Liste nur dazu passende Themen.
- [ ] Angenommen ein Admin wählt „Kopieren“ bei einem Thema, wenn der Text kopiert wird, dann enthält er Produkt, Thema, Zahl der Tickets und die Kundenformulierungen mit der Bitte, einen ProductForge-Eintrag zu prüfen.

**Aufbewahrung und Altbestand**
- [ ] Angenommen ein Eintrag ist offen oder ignoriert, wenn Zeit vergeht, dann wird er nicht gelöscht.
- [ ] Angenommen ein Thema ist seit mehr als 12 Monaten erledigt, wenn die Bereinigung läuft, dann werden Einträge und Formulierungen dazu gelöscht.
- [ ] Angenommen auf der Seite „Wissenslücken“ stehen bereits Lücken, die Detailfragen waren, wenn PROJ-57 im Einsatz ist, dann bleiben sie unverändert und werden nicht umgewandelt.

**Prompt**
- [ ] Angenommen die Analyse läuft, wenn der Prompt die Grenze zwischen Detailfrage und echter Lücke enthält, dann ersetzt diese Regel die bisherige Anweisung, eine Lücke zu melden, wenn eine für die Antwort nötige Produktangabe fehlt; die Prompt-Version wird erhöht.

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

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)
_To be added by /architecture_

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
