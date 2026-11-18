# PROJ-97: Versandbenachrichtigungen aus Lettermint im Antwortvorschlag

## Status: In Review
**Created:** 2026-11-18
**Last Updated:** 2026-11-18

## Dependencies
- Requires: PROJ-88 (Live-Sendungsstatus im Antwortvorschlag, Werkzeug `ShipmentStatusTool`)
- Requires: PROJ-7 (Bestellung aus EOCS, Bestellnummer)
- Requires: PROJ-9/10 (Fallanalyse und Ergebnisansicht)
- Bezug: PROJ-82 (WISMO-Automatik), POLICY-045 und POLICY-047 (Wissen)

## Hintergrund
Zwei n8n-Workflows arbeiten unabhängig von CSA und verschicken über den Mailversand-Dienst Lettermint proaktiv Mails an jeden Kunden. Die Paketdienste lösen sie per Webhook aus.
- „Ihr Paket kommt heute“: mit Tracking-Link und dem Hinweis, dass sich Zustelloptionen über den Tracking-Link ändern lassen.
- Abholhinweis: Das Paket wurde wegen Abwesenheit und ohne Abstellgenehmigung an einen Abholort gebracht und kann dort abgeholt werden.

Die Mails haben die WISMO-Anfragen reduziert. Manche Kunden ignorieren sie trotzdem und verlangen später ihr Geld zurück, weil das Paket „nie angekommen“ ist. Eine Erstattung gibt es bei personalisierten Produkten nicht. Ein Neuversand ist möglich und darf berechnet werden, wenn die Zustellung am Kunden scheiterte. In der Antwort wollen wir um Verständnis werben: Der Kunde muss die Zustellung ermöglichen, und wir unterstützen ihn dabei mit diesen Benachrichtigungen. Bisher prüft der Mitarbeiter von Hand, ob die Mail wirklich rausging. Kürzlich war der n8n-Workflow ausgefallen.

## User Stories
- Als Mitarbeiter möchte ich bei einer Liefer- oder Verbleibsanfrage sehen, ob und wann die Mails „Paket kommt heute“ und „Abholhinweis“ zu dieser Bestellung verschickt und angenommen wurden. So muss ich nicht selbst bei Lettermint nachsehen.
- Als Mitarbeiter möchte ich, dass der Antwortentwurf eine belegte Benachrichtigung mit Datum und Uhrzeit nennt und freundlich um Verständnis für die Mitwirkung bei der Zustellung wirbt.
- Als Mitarbeiter möchte ich einen deutlichen Hinweis bekommen, wenn keine Benachrichtigung belegt ist. Dann behaupte ich dem Kunden gegenüber nichts und prüfe den n8n-Workflow.
- Als Admin möchte ich, dass ohne hinterlegten Lettermint-Zugang alles wie bisher funktioniert.

## Out of Scope
- Webhook-Empfang und eigene Speicherung aller verschickten Mails (Option a). Bewusst nicht gewählt, die Abfrage läuft je Ticket.
- Eigene Überwachung der n8n-Workflows (Alarm bei Ausfall), unabhängig von einem Ticket.
- Abfrage ohne Liefer- oder Verbleibsthema oder ohne Bestellnummer.
- Absendezeitpunkt `message.sent` (gibt es nur per Webhook). Belegt wird die Annahme beim Empfänger-Mailserver.
- Änderung von Beträgen im Wissen (5 € Neuversand), siehe offene Fragen.
- Die WISMO-Automatik (PROJ-82) nutzt die Benachrichtigungen nicht.

## Acceptance Criteria

### Abfrage
- [ ] Angenommen ein Ticket mit Bestellung und Sendungsnummer, bei dem es um Verbleib oder Zustellung geht, wenn „Vorschlag erstellen“ läuft und die KI den Sendungsstatus abfragt, dann fragt CSA bei Lettermint die ausgehenden Live-Mails ab, deren Betreff die Bestellnummer enthält.
- [ ] Angenommen es geht nicht um Verbleib oder Zustellung (die KI ruft das Werkzeug nicht auf), wenn der Vorschlag erstellt wird, dann fragt CSA Lettermint nicht ab.
- [ ] Angenommen der Betreff enthält die Bestellnummer nur als Teil einer längeren Nummer (12345 in 123456), wenn ausgewertet wird, dann zählt die Mail nicht.
- [ ] Angenommen zu einer Bestellung gibt es mehrere passende Mails, auch mit gleichem Betreff, wenn ausgewertet wird, dann erscheint jede einzeln, die neueste zuerst, höchstens fünf je Bestellung.
- [ ] Angenommen die Treffer verteilen sich auf mehrere Seiten, wenn abgefragt wird, dann werden auch die Folgeseiten gelesen (begrenzt).

### Auswertung
- [ ] Angenommen eine Mail hat das Ereignis `delivered`, wenn ausgewertet wird, dann lautet der Befund „vom Empfänger-Mailserver angenommen“ mit Datum und Uhrzeit dieses Ereignisses in deutscher Zeit (Europe/Berlin). Spätere Ereignisse wie Öffnen oder Klick ändern den Zeitpunkt nicht.
- [ ] Angenommen eine Mail hat nur `processed`, wenn ausgewertet wird, dann lautet der Befund „an den Versanddienst übergeben“, die Annahme beim Empfänger bleibt offen.
- [ ] Angenommen eine Mail hat `soft_bounced`, `hard_bounced`, `failed` oder `suppressed`, auch nach einem `delivered`, wenn ausgewertet wird, dann ist der Fehler samt Zeitpunkt sichtbar und die Mail gilt nicht als belegt.
- [ ] Angenommen eine Mail steht nur auf `queued` oder `scheduled`, wenn ausgewertet wird, dann gilt sie als „noch nicht versendet“.
- [ ] Angenommen der Befund wird an die KI übergeben, dann enthält er die Mailart („Paket kommt heute“, „Abholhinweis“, sonstige), den Befund, Datum und Uhrzeit und ob der Empfänger die Absenderadresse des Tickets ist, aber keine E-Mail-Adresse.

### Antwortentwurf
- [ ] Angenommen die Mail „Paket kommt heute“ oder der Abholhinweis ist belegt, wenn der Entwurf entsteht, dann nennt er die Benachrichtigung mit Datum und Uhrzeit. Bei einem nicht zugestellten oder zurückgekommenen Paket erklärt er freundlich die Mitwirkung bei der Zustellung (POLICY-045).
- [ ] Angenommen keine Benachrichtigung ist belegt (keine Mail, Fehler, nicht abrufbar), wenn der Entwurf entsteht, dann behauptet er keine Mail und die Empfehlung nennt intern „keine Benachrichtigung belegt, n8n-Workflow prüfen“.
- [ ] Angenommen die Mail ging an eine andere Adresse als die Absenderadresse des Tickets, wenn der Entwurf entsteht, dann spricht er von der „bei der Bestellung angegebenen E-Mail-Adresse“ und nennt keine Adresse.

### Anzeige
- [ ] Angenommen das Werkzeug wurde aufgerufen, wenn das Ergebnis angezeigt wird, dann zeigt ein Block „Versandbenachrichtigungen“ je Mail Mailart, Befund, Datum und Uhrzeit.
- [ ] Angenommen keine Benachrichtigung ist belegt, wenn das Ergebnis angezeigt wird, dann erscheint ein gelber Hinweis.
- [ ] Angenommen Lettermint war nicht abrufbar, wenn das Ergebnis angezeigt wird, dann steht „Benachrichtigungen nicht abrufbar“ im Block.

### Betrieb
- [ ] Angenommen kein Lettermint-Token ist hinterlegt, wenn ein Vorschlag erstellt wird, dann geht keine Anfrage an Lettermint und alles läuft wie bisher.
- [ ] Angenommen Lettermint antwortet mit einem Fehler (401/403, 422, 429, 5xx, Timeout), wenn abgefragt wird, dann läuft die Analyse weiter, der Befund lautet „nicht abrufbar“, und im Log steht der Grund.

## Edge Cases
- Das Paket ist an der Bestellung zurückgekommen, eine Benachrichtigung ist aber nicht belegt (Ausfall von n8n): Der Entwurf behauptet nichts, der Hinweis an den Mitarbeiter ist gelb.
- Die Aufbewahrung bei Lettermint ist abgelaufen (`retained_until`): Es gibt keine Treffer, der Befund ist „keine Benachrichtigung gefunden“ und nicht „nicht versendet“.
- Mehrere Bestellungen im Ticket: Jede Bestellung wird getrennt abgefragt.
- Neuversand mit eigener Bestellnummer: Abgefragt werden die im Ticket geladenen Bestellungen.
- Andere Lettermint-Mails mit der Bestellnummer, zum Beispiel eine Sendungsnummer-Mail: Sie erscheinen als „sonstige“, wenn die Mailart sich nicht erkennen lässt.
- Ein UTC-Zeitpunkt kurz vor Mitternacht ergibt in Berlin den Folgetag. Angezeigt wird der Berliner Tag.

## Open Questions
- [ ] Wie lauten die genauen Betreffzeilen beider n8n-Mails, auch in anderen Sprachen? Sie bestimmen die Erkennung der Mailart und werden beim ersten Live-Abruf geprüft.
- [ ] POLICY-047 nennt für den Neuversand bei looxis.de nur `[VERSANDKOSTEN]` und eine Kulanz ab 100 € Bestellwert. Etienne berechnet heute 5 €. Soll der Betrag ins Wissen? Das entscheidet Etienne, bis dahin bleibt die Policy unverändert.
- [ ] Ist ein Lettermint-Projekt einzugrenzen (`LETTERMINT_PROJECT_ID`), oder laufen alle Kundenmails über ein Projekt?

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Abfrage je Ticket per Lettermint-API statt Webhook-Speicher | Keine eigene Datensammlung aller Kundenmails nötig, aktueller Stand bei Bedarf | 2026-11-18 |
| Suchbegriff ist die Bestellnummer im Betreff | Die Bestellnummer ist CSA in der Regel bekannt. Die Kunden-Mail aus dem Ticket kann von der Bestelladresse abweichen | 2026-11-18 |
| Nur bei Liefer- oder Verbleibsthema, gekoppelt an das Sendungsstatus-Werkzeug (PROJ-88) | Gleicher Auslöser wie der Paketstatus, keine Erwähnung bei Reklamationen | 2026-11-18 |
| Beleg ist `delivered` (Annahme beim Empfänger-Mailserver) | Eindeutiger Nachweis im REST-Verlauf. `created_at`/`status_changed_at` sind keine Versandzeitpunkte | 2026-11-18 |
| Keine E-Mail-Adresse an die KI, nur „an Ticket-Absender ja/nein“ | Datensparsamkeit, wie die Pseudonymisierung | 2026-11-18 |
| Wissen: POLICY-045 ergänzen statt neuer Datei | Die Regel zur Mitwirkung steht dort bereits samt Hinweis auf die Mail „Paket kommt heute“ | 2026-11-18 |

### Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Erweiterung von `ShipmentStatusTool` statt eigenem Werkzeug | Ein Werkzeugaufruf liefert Paketstatus und Benachrichtigungen. Der Auslöser bleibt einheitlich | 2026-11-18 |

---

## Tech Design (Solution Architect)
- `config/services.php` → `lettermint` (`LETTERMINT_URL`, `LETTERMINT_TEAM_TOKEN`, `LETTERMINT_TIMEOUT`, `LETTERMINT_PROJECT_ID`, Betreff-Stichwörter je Mailart).
- `app/Lettermint/LettermintClient.php` (Nachrichten- und Ereignisabruf mit Cursor), `LettermintException`.
- `app/Lettermint/DeliveryNotices.php` (Treffer filtern, Mailart, Befund aus Ereignissen) und das DTO `DeliveryNotice`.
- `ShipmentStatusTool` hängt je Bestellung die Benachrichtigungen an und merkt sie für die Ergebnisansicht.
- Prompt `analysis` mit der Regel „Benachrichtigungen“, Ergebnisansicht mit dem Block „Versandbenachrichtigungen“.

## Implementation Notes
- `app/Lettermint/LettermintClient.php` (Bearer-Token, `filter[type]=outbound`, `filter[delivery_mode]=live`, `filter[subject]`, optional `filter[project]`, Cursor bis 3 Seiten, zwei Versuche bei 429/5xx/Verbindungsfehler, Timeout 5 s), `LettermintException`.
- `app/Lettermint/DeliveryNotices.php`: ganze Bestellnummer im Betreff, höchstens 5 je Bestellung, `evaluate()` mit Rangfolge Fehler nach `delivered` → `delivered` → Status `delivered` ohne Ereignis → `processed` → `queued`/`scheduled`. Zeit in Europe/Berlin mit Sekunden. `DeliveryNotice::facts()` geht ohne Betreff und ohne Adresse an die KI.
- Mailart über `services.lettermint.subjects` (Abholhinweis zuerst geprüft). Die Stichwörter sind vorläufig und werden beim ersten Live-Abruf an die echten Betreffzeilen angepasst.
- `ShipmentStatusTool` hängt die Zeilen nur an, wenn mindestens eine Sendung abgefragt wurde und ein Token gesetzt ist. `noticeRecord()` landet als `delivery_notices` im gespeicherten Ergebnis. `<x-analysis.delivery-notices>` steht in „Was ist zu tun?“.
- Prompt `analysis-2026-11-18.1`, Regel „Benachrichtigungen“. POLICY-045 ist ergänzt (Abholhinweis, Nennung mit Datum nur bei Beleg, Trigger „nie angekommen“, „nie erhalten“).
- Env (trägt Etienne ein): `LETTERMINT_TEAM_TOKEN`, optional `LETTERMINT_PROJECT_ID`, `LETTERMINT_URL`, `LETTERMINT_TIMEOUT`.

## QA Test Results

**Tested:** 2026-11-18
**Tester:** QA Engineer (AI). Der Live-Abruf gegen das echte Lettermint-Konto steht noch aus, weil noch kein Token hinterlegt ist.

### Acceptance Criteria Status
- [x] Abfrage (5/5): nur über den Werkzeugaufruf, Filter outbound/live/Betreff, ganze Bestellnummer (12345 ≠ 123456 und 12345-2), gleiche Betreffe einzeln und die neueste zuerst, Folgeseite mit allen Filtern und Cursor
- [x] Auswertung (5/5): `delivered` mit Zeit, `opened` danach ändert nichts, nur `processed`, Bounce nach `delivered` sichtbar, nur `queued`, keine Adresse und kein Betreff an die KI
- [x] Antwortentwurf (3/3): Prompt-Regel vorhanden (Nennung mit Datum, „n8n-Workflow prüfen“, keine Adresse). Die Wirkung im echten Entwurf ist modellabhängig und wird am Live-Ticket geprüft.
- [x] Anzeige (3/3): Block mit Mailart, Befund und Zeit, gelber Hinweis ohne Beleg, „nicht abrufbar“
- [x] Betrieb (2/2): ohne Token keine Anfrage und unveränderte Antwort, 401/500 ergibt „nicht abrufbar“ und die Analyse läuft weiter

### Edge Cases Status
- [x] UTC 23:30 → Berliner Folgetag, Sommerzeit (UTC+2)
- [x] Soft Bounce mit späterem `delivered` gilt als angenommen
- [x] Status `delivered` ohne Ereignis: angenommen, Zeitpunkt unbekannt
- [x] Mehrere Bestellungen: getrennte Abfrage und Anzeige je Bestellung
- [x] Aufbewahrung abgelaufen: „keine gefunden“, nicht „nicht versendet“

### Security Audit Results
- [x] Token nur serverseitig (Config/Env), nicht im Log und nicht in der Ansicht
- [x] XSS: Betreff aus Lettermint nur über `{{ }}` ausgegeben
- [x] Prompt Injection: Der Betreff geht nicht an die KI, nur Mailart, Befund und Zeit
- [x] Datensparsamkeit: keine E-Mail-Adresse an die KI, nur „an Ticket-Absender ja/nein“
- [x] Nur lesende GET-Aufrufe, Token-Berechtigung `read:messages`

### Regression
- [x] PROJ-88 (Sendungsstatus) grün. Die Prompt-Version ist in den Tests zu PROJ-36, 59 und Anrede nachgezogen.
- Volle Suite seriell: 2883/2887. Die 2 Fehler (Versionsanzeige der Seitenleiste, Zähler „Wissen zur Überprüfung“) treten auch ohne PROJ-97 auf. Im Parallellauf gibt es wechselnde Wissensbasis-Fehler wie schon zuvor.

### Bugs Found
- Behoben (Medium): Fiel Lettermint aus, konnte die Analyse bis zu 2 × 10 s warten. Das Timeout ist jetzt 5 s.

### Summary
- **Acceptance Criteria:** 18/18 passed (automatisiert)
- **Bugs Found:** 0 offen (Critical/High/Medium)
- **Security:** Pass
- **Production Ready:** YES, nach Hinterlegen des Tokens und Prüfung der Betreff-Stichwörter am Live-Abruf

## Deployment
_Kein eigener Schritt._
