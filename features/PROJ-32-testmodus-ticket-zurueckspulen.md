# PROJ-32: Testmodus – Ticket zurückspulen

## Status: In Progress
**Created:** 2026-10-06
**Last Updated:** 2026-10-06

## Dependencies
- Requires: PROJ-5 (Nutzerauswahl) – gewählter Name entscheidet, ob jemand Admin ist
- Requires: PROJ-6 (Zammad-Ticket laden) – Verlauf und Nachrichten
- Requires: PROJ-9 (Fallanalyse per LLM) – Analyse und Zusammenfassung, die getestet werden

## Ausgangslage
Im Moment kommen wenige Tickets herein, und alle werden zügig beantwortet. Frische, unbearbeitete Tickets zum Testen der Analyse gibt es kaum. Geschlossene Tickets lassen sich zwar schon laden und analysieren. Die KI sieht dann aber den ganzen Verlauf samt unserer Antworten, und der Fall ist für sie schon gelöst. Der Testmodus spult ein Ticket auf einen früheren Stand zurück: Analyse und Zusammenfassung sehen nur, was bis zu einer gewählten Kundennachricht geschah.

## User Stories
- Als Admin möchte ich ein bereits beantwortetes oder geschlossenes Ticket auf den Stand einer früheren Kundennachricht zurückspulen, damit ich die Analyse an echten Fällen testen kann, ohne auf neue Tickets zu warten.
- Als Admin möchte ich den Vorschlag der KI direkt mit der Antwort vergleichen, die wir damals tatsächlich geschrieben haben, damit ich Knowledge und Prompts gezielt verbessern kann.
- Als Admin möchte ich auch die Zusammenfassung auf einem früheren Stand testen, damit ich die zweistufige Analyse langer Verläufe prüfen kann.
- Als Admin möchte ich den Testmodus in meinem Browser ein- und ausschalten, damit er mich bei der normalen Arbeit nicht stört.
- Als Mitarbeiter ohne Admin-Rolle möchte ich vom Testmodus nichts sehen, damit ich nicht versehentlich mit einem zurückgespulten Ticket arbeite.
- Als Product Owner möchte ich, dass Testläufe gekennzeichnet sind und echte Daten nicht beeinflussen, damit Vorschläge für echte Fälle sauber bleiben.

## Out of Scope
- Echte Benutzerrollen mit Login – kommt mit PROJ-15; bis dahin Admin-Liste in der Konfiguration
- Automatischer Vergleich KI-Vorschlag gegen tatsächliche Antwort mit Bewertung – gehört zur Evaluation (PROJ-13)
- Testläufe dauerhaft speichern und filtern – mit PROJ-11 / PROJ-14
- Zurückspulen auf eine eigene Antwort oder interne Notiz (nur Kundennachrichten sind Schnittpunkte)
- Änderungen in Zammad (Status, Antworten) – die App liest nur

## Acceptance Criteria

**Admin und Schalter**
- [ ] Angenommen der gewählte Name steht in der Admin-Liste der Konfiguration (zunächst „Etienne"), wenn er eine Seite öffnet, dann sieht er in der Kopfleiste einen Schalter „Testmodus"
- [ ] Angenommen der gewählte Name ist kein Admin, wenn er eine Seite öffnet, dann gibt es keinen Schalter und keine Testmodus-Elemente
- [ ] Angenommen ein Admin schaltet den Testmodus ein, wenn er später wiederkommt, dann ist der Testmodus in diesem Browser weiter eingeschaltet, bis er ihn ausschaltet
- [ ] Angenommen der Testmodus ist eingeschaltet, wenn ein Admin irgendeine Seite ansieht, dann zeigt ein deutlicher Hinweis „Testmodus aktiv"
- [ ] Angenommen der Testmodus ist in einem Browser eingeschaltet, wenn dort ein Name ohne Admin-Rolle gewählt wird, dann ist der Testmodus wirkungslos und unsichtbar

**Zurückspulen**
- [ ] Angenommen der Testmodus ist eingeschaltet, wenn ein Admin ein Ticket öffnet, dann trägt jede Kundennachricht, auch die erste, eine Schaltfläche „Bis hierher testen"
- [ ] Angenommen ein Admin klickt bei einer Kundennachricht auf „Bis hierher testen", wenn die Seite neu lädt, dann ist diese Nachricht die letzte Kundennachricht und alle späteren Nachrichten (unsere Antworten, Notizen, weitere Kundennachrichten) stehen eingeklappt und ausgegraut darunter als „N spätere Nachrichten (nicht an die KI)"
- [ ] Angenommen ein Ticket ist zurückgespult, wenn der Admin die späteren Nachrichten aufklappt, dann sieht er sie vollständig zum Vergleich
- [ ] Angenommen ein Ticket ist zurückgespult, wenn der Admin die Vorschau „Was an die KI geht" ansieht, dann enthält sie nur Nachrichten bis zum Schnittpunkt
- [ ] Angenommen ein Ticket ist zurückgespult, wenn der Admin analysiert, dann gehen nur Nachrichten bis zum Schnittpunkt an die KI; Varianten und Schwelle für die Zusammenfassung richten sich nach dem gekürzten Verlauf
- [ ] Angenommen ein Ticket ist zurückgespult, wenn der Admin eine Zusammenfassung erstellt, dann umfasst sie nur die Nachrichten vor der gewählten Kundennachricht
- [ ] Angenommen ein Ticket ist zurückgespult, wenn der Admin den Schnittpunkt aufhebt („Ganzen Verlauf zeigen"), dann sieht er das Ticket wieder normal
- [ ] Angenommen ein Ticket ist geschlossen, wenn es zurückgespult ist, dann steht neben dem Status „Testlauf – Status und spätere Antworten werden ignoriert"
- [ ] Angenommen der Stand ist in der Adresse der Seite enthalten, wenn der Admin die Seite neu lädt oder die Adresse weitergibt, dann bleibt der Schnittpunkt erhalten; für Nicht-Admins wird er ignoriert

**Kennzeichnung und Trennung**
- [ ] Angenommen eine Analyse lief auf einem zurückgespulten Ticket, wenn das Ergebnis angezeigt wird, dann ist es als „Testlauf (Stand bis Nachricht vom TT.MM.JJJJ, HH:MM)" gekennzeichnet
- [ ] Angenommen eine Zusammenfassung wurde im Testlauf erstellt, wenn das Ticket ohne Schnittpunkt oder mit anderem Schnittpunkt geöffnet wird, dann erscheint sie dort nicht; die echte Zusammenfassung bleibt unberührt
- [ ] Angenommen eine Analyse lief als Testlauf, wenn danach ein anderes Ticket desselben Kunden geöffnet wird, dann wurde die Kundengruppe aus dem Testlauf nicht für diesen Kunden gemerkt
- [ ] Angenommen ein Nicht-Admin schickt eine Analyse oder Zusammenfassung mit Schnittpunkt ab (z. B. per manipuliertem Formular), wenn der Server sie verarbeitet, dann wird der Schnittpunkt ignoriert und der ganze Verlauf verwendet

## Edge Cases
- Schnittpunkt-Nachricht existiert nicht (mehr) oder gehört zu einem anderen Ticket → Hinweis „Stand nicht gefunden", ganzer Verlauf
- Schnittpunkt ist keine Kundennachricht (z. B. per Adresse auf eine eigene Antwort gesetzt) → wie nicht gefunden
- Zurückgespult auf die allererste Nachricht → keine Varianten, nur diese Nachricht geht an die KI
- Schnittpunkt ist bereits die letzte Kundennachricht und danach folgt nichts → kein eingeklappter Block, Kennzeichnung „Testlauf" trotzdem
- Kundenwahl (Kundengruppe, Produkte) je Ticket wird auch im Testlauf gemerkt (hilft bei wiederholten Tests desselben Tickets), nur die Merkung je Kunde entfällt
- Bestellnummern werden weiterhin aus dem ganzen Ticket vorgeschlagen? → Nein: nur aus Titel und Nachrichten bis zum Schnittpunkt, damit der Test dem damaligen Stand entspricht
- Admin wechselt den Namen auf einen Nicht-Admin mitten im Testlauf → Testmodus-Elemente verschwinden beim nächsten Laden

## Technical Requirements (optional)
- Sicherheit: Admin-Prüfung serverseitig bei Anzeige und bei jedem Abschicken; ohne Login nur schwacher Schutz (jeder kann „Etienne" wählen) – im internen Netz akzeptiert, echte Rolle mit PROJ-15
- Datenschutz: unverändert zu PROJ-9 (Platzhalter, keine Adressen); Testläufe nutzen dieselbe Bereinigung

## Open Questions
- [ ] Sollen Testläufe mit PROJ-11 dauerhaft gespeichert werden (Grundlage für PROJ-13 Evaluation), oder nur vorübergehend wie heute?
- [ ] Gute Testläufe als Gold-Testfall nach `evaluation/` übernehmen („Als Testfall speichern") – eigenes Feature im Umfeld von PROJ-13?

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Zurückspulen per Schaltfläche an jeder Kundennachricht | Auch spätere Stufen eines Falls (2. Reklamationsmail) testbar, nicht nur der Anfang | 2026-10-06 |
| Spätere Nachrichten eingeklappt und aufklappbar statt ganz ausgeblendet | Direkter Vergleich mit der damals tatsächlich gesendeten Antwort | 2026-10-06 |
| Nur Admins; Admin-Liste in der Konfiguration (zunächst Etienne), Testmodus zusätzlich je Browser ein-/ausschaltbar | Kein Login im MVP; Schalter verhindert Verwechslung bei echter Arbeit; bleibt dauerhaft als Admin-Werkzeug | 2026-10-06 |
| Testläufe gekennzeichnet, Zusammenfassung je Schnittpunkt getrennt, keine Merkung der Kundengruppe je Kunde | Tests dürfen echte Vorschläge und Zusammenfassungen nicht verfälschen | 2026-10-06 |
| Bestellnummern-Vorschläge nur aus dem Stand bis zum Schnittpunkt | Test entspricht dem damaligen Wissensstand | 2026-10-06 |
| Feature bleibt dauerhaft (nicht nur vorübergehend) | Wunsch des Product Owners; nützlich zum Prüfen neuen Wissens an alten Fällen | 2026-10-06 |

### Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Zurückgespultes Ticket = dasselbe Ticket mit gekürztem Verlauf; alle PROJ-9-Bausteine bleiben unverändert | Analyse, Zusammenfassung, Vorschau, Schwelle und Bestellnummern-Erkennung arbeiten automatisch auf dem Stand; kein zweiter Codepfad, der auseinanderlaufen kann | 2026-10-06 |
| Schnittpunkt als Zammad-Nachrichten-ID in der Adresse (`?stand=…`) und als verstecktes Formularfeld | Neu laden und Weitergeben behalten den Stand; der Server prüft ID, Ticket und Nachrichtenart bei jedem Aufruf | 2026-10-06 |
| Admin-Liste `admins` in `config/staff.php`; Testmodus-Schalter als verschlüsseltes Cookie wie der Name | Gleiche Mechanik wie PROJ-5, keine Datenbank; wird mit PROJ-15 durch eine echte Rolle ersetzt | 2026-10-06 |
| Zusammenfassung im Testlauf unter eigenem Schlüssel je Schnittpunkt | Echte Zusammenfassung bleibt unberührt; wiederholte Tests desselben Stands sparen Tokens | 2026-10-06 |
| Keine Datenbank, keine neuen Pakete | Speicherung wie PROJ-9 (vorübergehend, verschlüsselt); dauerhaft erst mit PROJ-11 | 2026-10-06 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

### Überblick
Der Testmodus baut auf PROJ-9 auf. Die Idee: Ein zurückgespultes Ticket ist für die App einfach dasselbe Ticket mit einem kürzeren Verlauf. Alles, was heute schon mit dem Verlauf arbeitet (Vorschau, Varianten, Schwelle für die Zusammenfassung, Bestellnummern-Vorschläge, Analyse), funktioniert damit ohne Änderung auf dem früheren Stand. Neu sind nur: wer den Testmodus sehen darf, der Schalter, das Kürzen und die Kennzeichnung.

### A) Bausteine
```
Kopfleiste (alle Seiten)
+-- Name (PROJ-5)
+-- Schalter „Testmodus" (nur Admins)
+-- Hinweisband „Testmodus aktiv" (nur wenn eingeschaltet)

Ticketseite (PROJ-6)
+-- Kopf: Status + Hinweis „Testlauf – Status und spätere Antworten werden ignoriert"
|        + „Ganzen Verlauf zeigen" (wenn zurückgespult)
+-- Verlauf bis zum Schnittpunkt
|   +-- jede Kundennachricht: Schaltfläche „Bis hierher testen" (nur Testmodus)
+-- Block „N spätere Nachrichten (nicht an die KI)" – ausgegraut, aufklappbar
+-- Analyse-Formular (PROJ-9), trägt den Schnittpunkt verdeckt mit
+-- Zusammenfassung (PROJ-9), je Schnittpunkt getrennt
+-- Ergebnis (PROJ-9) mit Kennzeichnung „Testlauf (Stand bis Nachricht vom …)"
```

### B) Daten
- **Admin-Liste:** in der Konfiguration neben der Namensliste, zunächst nur „Etienne".
- **Schalter:** je Browser in einem verschlüsselten Cookie, wie der gewählte Name. Wirkt nur, wenn der gewählte Name Admin ist.
- **Schnittpunkt:** die Zammad-Nummer der gewählten Kundennachricht, in der Adresse der Seite und im Formular. Wird nirgends dauerhaft gespeichert.
- **Zusammenfassung im Testlauf:** wie bisher vorübergehend (7 Tage, verschlüsselt), aber unter einem eigenen Schlüssel je Ticket und Schnittpunkt.
- **Ergebnis:** wie bisher, zusätzlich mit dem Vermerk „Testlauf" und Datum/Uhrzeit der Schnittpunkt-Nachricht.
- **Kundengruppe:** je Ticket weiter gemerkt, je Kunde im Testlauf nicht.
- Jede Nachricht bekommt intern ihre Zammad-Nummer mit, damit der Schnittpunkt eindeutig ist.

### C) Technische Entscheidungen (für Nicht-Entwickler)
- **Kürzen statt Sonderweg:** Würde jeder Baustein selbst „Testmodus" kennen, könnte z. B. die Vorschau etwas anderes zeigen als das, was an die KI geht. Mit einem gekürzten Ticket ist das ausgeschlossen.
- **Prüfung auf dem Server:** Ob jemand Admin ist und ob der Schnittpunkt gültig ist (gehört zu diesem Ticket, ist eine Kundennachricht), prüft der Server bei jeder Anzeige und jedem Abschicken. Ein ungültiger Schnittpunkt führt zum Hinweis „Stand nicht gefunden" und zum ganzen Verlauf.
- **Ohne Login nur schwacher Schutz:** Wer „Etienne" wählt, ist Admin. Im internen Netz vertretbar; PROJ-15 ersetzt die Liste durch eine echte Rolle.

### D) Abhängigkeiten
Keine neuen Pakete.

### E) Hinweise für /frontend und /backend
- Schalter in der Kopfleiste neben dem Namen; Umschalten per Formular (POST mit CSRF), danach zurück auf dieselbe Seite (sichere Weiterleitung wie bei der Namenswahl).
- „Bis hierher testen" ist ein einfacher Link mit `?stand=…` (keine Datenänderung).
- Eingeklappter Block nutzt dieselbe Aufklapp-Darstellung wie Zitate/Signatur.
- Tests: Nicht-Admin mit `stand` in Adresse und Formular, fremde/ungültige Nachrichten-ID, Zusammenfassung getrennt, Kundengruppe je Kunde nicht gemerkt, Vorschau = gesendeter Text.

## Implementation Notes (Frontend + Backend)
**Gebaut am 2026-10-06**, Frontend und Backend in einem Durchgang.

- **Admin und Schalter:** `config/staff.php` → `admins` (`['Etienne']`), Cookie `test_mode` (verschlüsselt, wie der Name). `StaffDirectory::isAdmin()`, `App\Staff\TestMode` (`isAvailable`, `isActive`, `rewind`). Route `POST /testmodus` (`test-mode.switch`, `TestModeController`, Form Request `SwitchTestModeRequest` mit Admin-Prüfung → 403 für andere). Schalter in der Kopfleiste links neben dem Namen, Hinweisband „Testmodus aktiv" unter der Kopfleiste.
- **Zurückspulen:** `Ticket::rewoundTo(articleId)` liefert das gekürzte Ticket (bis einschließlich der Kundennachricht, Amazon-Bestellangaben nur aus diesen Nachrichten) und die späteren Nachrichten. `TicketArticle` trägt dafür `id` und `order`. Alle PROJ-9-Bausteine arbeiten unverändert auf dem gekürzten Ticket. `?stand=` in der Adresse; Analyse- und Zusammenfassungsformulare tragen `stand` verdeckt mit; Weiterleitungen behalten ihn.
- **Trennung:** Zusammenfassung unter `Ticket::summaryKey()` (`{nummer}.stand-{id}`); Ergebnis-Metadaten `test_until`; Kundengruppe je Kunde wird im Testlauf nicht gemerkt, je Ticket schon.
- **Oberfläche:** „Bis hierher testen" im Kopf jeder Kundennachricht; Testlauf-Hinweis im Ticketkopf mit „Ganzen Verlauf zeigen"; spätere Nachrichten eingeklappt und ausgegraut; Ergebnis mit „Testlauf (Stand bis Nachricht vom …)"; fester Testmodus-Hinweis über dem Kontextfeld (Text vom Product Owner bestätigt).
- **Tests:** `tests/Feature/PROJ-32-TestModeTest.php` (14 Fälle, nur erfundene Daten). Gesamte Suite: 691 grün.

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
