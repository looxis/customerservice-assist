# PROJ-32: Testmodus – Ticket zurückspulen

## Status: Planned
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

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)
_To be added by /architecture_

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
