# PROJ-82: WISMO-Automatisierung (Umzug von n8n nach CSA)

## Status: Planned
**Created:** 2026-11-11
**Last Updated:** 2026-11-11

## Dependencies
- Requires: PROJ-66 (automatischer Ticket-Eingang), PROJ-78 und PROJ-80 (Ticket-Klassifikation, Kategorie „wismo“), PROJ-7 (Bestellung aus EOCS), PROJ-19 (Antwort nach Zammad senden), PROJ-79 (Hinweis im Bestellblock, gleiche Darstellung), PROJ-77 (Vorbild für den automatischen Versand), PROJ-31 (Prompt und Modell in den Einstellungen)
- Folgefunktion: PROJ-83 (Bestellung hängt: Ticket auffällig kennzeichnen und nach oben schieben)

## Hintergrund
Heute beantwortet ein n8n-Workflow (ID `1rySZ2WRGSQtjBMW`) WISMO-Tickets („Wo ist mein Paket?“) automatisch: Zammad meldet das neue Ticket per Webhook, ein LLM erkennt WISMO, ein zweites zieht die Bestellnummer heraus, EOCS liefert Status, Carrier und Sendungsnummer, die DHL- oder UPS-Schnittstelle liefert den Sendungsstatus (bei DHL mit PLZ des Empfängers, damit Zustell- und Abholdetails kommen), ein drittes LLM formuliert die Antwort, die als E-Mail an den Kunden geht, danach steht das Ticket auf „offen“.

CSA ordnet jedes neue Ticket schon ein (PROJ-80, Kategorie `wismo`), liest EOCS und schreibt nach Zammad. Die Funktion zieht deshalb in die App um (angekündigt als „später“, jetzt beauftragt). Dabei sinken die LLM-Aufrufe von drei auf einen: Die WISMO-Erkennung übernimmt die vorhandene Klassifikation, die Bestellnummer kommt aus dem vorhandenen Erkennungscode ohne LLM, nur die Antwort wird vom Modell formuliert. Prompt und Format der Antwort werden gegenüber n8n angepasst.

## User Stories
- Als Kunde, der nach seinem Paket fragt, möchte ich zeitnah eine Antwort mit dem aktuellen Sendungsstand in meiner Sprache bekommen, ohne dass ein Mitarbeiter eingreifen muss.
- Als Kundenservice-Mitarbeiter möchte ich, dass WISMO-Tickets ohne mein Zutun beantwortet werden und ich an der Ticketseite sehen kann, was gesendet wurde und warum, damit ich bei Rückfragen nahtlos übernehme.
- Als Kundenservice-Mitarbeiter möchte ich bei einem Zustellproblem sofort einen auffälligen Hinweis am Ticket sehen, damit ich es genauer prüfe, auch wenn der Kunde schon eine vorsichtige Zwischenantwort hat.
- Als Admin möchte ich die Automatik ein- und ausschalten, erst im Trockenlauf beobachten und Prompt und Modell der Antwort in den Einstellungen ändern können.
- Als Admin möchte ich ein Protokoll je Ticket (Ergebnis, Gründe, Sendungsdaten, gesendeter Text), damit ich Fehlentscheidungen nachvollziehen kann.

## Out of Scope
- Auffälliges Kennzeichnen und „Nach-oben-schieben“ von Tickets, deren Bestellung hängt (kein Versand nach mehr als 3 Werktagen): eigenes Feature PROJ-83. In PROJ-82 gibt es nur den Hinweis im Bestellblock (siehe Kriterien)
- Wieder geöffnete Tickets, Folgenachrichten und spätere Rückfragen des Kunden (nur neue Tickets nach der Einstufung)
- Retourenabwicklung, Erstattungen, Stornierungen: die Antwort bietet sie nicht an (Regel im Prompt)
- Weitere Carrier außer DHL und UPS: andere Carrier bekommen Name und Sendungsnummer mit Hinweis auf die Webseite des Paketdienstes
- Eigenes Passwort- oder Rollensystem; die Antwort läuft unter dem technischen Zammad-Nutzer
- Die Abschaltung des n8n-Workflows (macht der Entwickler nach dem Trockenlauf)
- Antwort in Sprachen mit Übersetzungsschritt: Das Modell schreibt direkt in der Kundensprache; kein eigener Übersetzungsdienst

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Auslöser und Schalter
- [ ] Angenommen ein neues Ticket ist klassifiziert (Quelle „Eingang“), wenn die KI-Einstufung `wismo` lautet, nicht „unklar“ und nicht „mehrere Anliegen“ ist, die Konfidenz mindestens den Schwellenwert der Klassifikation erreicht und das Ticket nicht zur Testmenge gehört, dann startet im Hintergrund die WISMO-Bearbeitung, ohne auf eine Bestätigung durch einen Mitarbeiter zu warten
- [ ] Angenommen eine der Bedingungen trifft nicht zu, dann geschieht nichts (das Ticket bleibt für Mitarbeiter unverändert)
- [ ] Angenommen der Schalter „WISMO automatisch beantworten“ steht auf „aus“ (Standard), dann wird nichts gesendet und nichts geschrieben
- [ ] Angenommen der Schalter steht auf „Trockenlauf“, dann entsteht nur das Protokoll und eine interne Notiz mit dem Antwortentwurf, aber keine Kundenantwort
- [ ] Angenommen das Ticket wurde schon bearbeitet (Protokolleintrag existiert) oder ein Mitarbeiter hat bereits geantwortet oder eine neuere Kundennachricht liegt vor, dann wird nicht erneut oder gar nicht gesendet

### Bestellnummer und Bestellung
- [ ] Angenommen die Nachricht enthält eine Bestellnummer in einem der in den Einstellungen hinterlegten Formate, dann wird sie ohne LLM erkannt und die Bestellung aus EOCS geladen
- [ ] Angenommen keine Bestellnummer wird gefunden, dann schreibt CSA eine interne Notiz „WISMO erkannt, aber keine Bestellnummer gefunden, bitte manuell nachfragen“ und sendet nichts
- [ ] Angenommen die Bestellnummer wird in EOCS nicht gefunden, dann schreibt CSA eine interne Notiz „WISMO erkannt, Bestellnummer … nicht in EOCS gefunden, bitte manuell prüfen“ und sendet nichts
- [ ] Angenommen EOCS ist nicht erreichbar, dann wird bis zu dreimal wiederholt; danach interne Notiz mit dem Grund und keine Kundenantwort

### Sendungsstatus
- [ ] Angenommen die Bestellung hat eine Sendung mit DHL als Carrier, wenn die Bearbeitung läuft, dann fragt CSA die DHL-Sendungsverfolgung mit Sendungsnummer und Empfänger-PLZ ab (Sprache Deutsch)
- [ ] Angenommen der Carrier ist UPS, dann fragt CSA die UPS-Sendungsverfolgung ab; die Anmeldung wird zwischengespeichert statt je Ticket neu geholt
- [ ] Angenommen der Carrier ist weder DHL noch UPS, dann nennt die Antwort Carrier und Sendungsnummer und verweist auf die Webseite des Paketdienstes
- [ ] Angenommen die Sendungsverfolgung liefert einen Abholort mit Name und Adresse, dann nennt die Antwort sie wörtlich wie geliefert; liefert sie keine Adresse, erfindet die Antwort keine und verweist auf die Sendungsverfolgung des Paketdienstes (bei UPS bis zur Klärung der Schnittstelle der Regelfall)
- [ ] Angenommen die Sendung ist zugestellt, dann nennt die Antwort Datum, Uhrzeit und Zustellart, sofern geliefert, und den Namen des Annehmenden nur, wenn er in den Daten steht, ohne Vermutung zur Beziehung
- [ ] Angenommen die Abfrage des Paketdienstes schlägt fehl, dann wird bis zu dreimal wiederholt; danach interne Notiz mit dem Grund und keine Kundenantwort

### Bestellung noch nicht versendet
- [ ] Angenommen die Bestellung ist gefunden, hat aber keine Sendung, dann sendet CSA eine Antwort mit dem Bestellstatus aus EOCS (wie n8n), ohne Liefertermin zuzusagen
- [ ] Angenommen der Bestelleingang liegt mehr als 3 Werktage zurück und es gibt keine Sendung, dann erscheint im Bestellblock der Ticketseite ein Hinweis „Bestellung seit … Werktagen ohne Versand, möglicherweise hängen geblieben“ im selben Stil und an derselben Stelle wie der Versandalter-Hinweis (PROJ-79), und die interne Notiz nennt das ebenfalls; die Kundenantwort nennt weiterhin keinen Liefertermin

### Zustellproblem
- [ ] Angenommen die Trackinghistorie enthält ein Zustellproblem (zum Beispiel „zurück an Absender“, Annahme verweigert, nicht abgeholt, unzustellbar, Adresse nicht auffindbar), dann behandelt CSA die Sendung nicht als zugestellt, auch wenn ein Status „zugestellt“ vorkommt
- [ ] Angenommen ein Zustellproblem wurde erkannt, dann sendet CSA eine vorsichtige Antwort (es scheint Probleme bei der Zustellung zu geben; Details über die Sendungsnummer auf der Webseite des Paketdienstes; ein Mitarbeiter schaut sich den Fall an und meldet sich; Kunde soll Lieferadresse bestätigen und Namen an Klingel und Briefkasten prüfen) ohne Zustelldetails, Zeitpunkte, Abholorte oder Empfängernamen aus den Trackingdaten
- [ ] Angenommen ein Zustellproblem wurde erkannt, dann bleibt das Ticket offen und trägt die interne Notiz „Zustellproblem, bitte prüfen“ mit dem Grund aus den Trackingdaten
- [ ] Angenommen ein Zustellproblem wurde erkannt, wenn die Ticketseite geöffnet wird, dann erscheint im Bestellblock ein auffälliger Hinweis im selben Stil und an derselben Stelle wie der Versandalter-Hinweis (rot, mit Grund und Datum)

### Antwort, Versand, Zammad
- [ ] Angenommen die Antwort entsteht, dann wird sie mit einem einzigen LLM-Aufruf in der Sprache der Kundenanfrage verfasst, mit Anrede (Du/Sie) angepasst, in der Wir-Form, und ohne URL (nur „Webseite des Paketdienstes“)
- [ ] Angenommen der Kunde fragt ausdrücklich nach Erstattung oder Rückgabe, dann erklärt die Antwort freundlich, dass bei personalisierten Produkten keine Erstattung möglich ist; ohne ausdrückliche Frage schlägt sie nie Rücksendung, Stornierung oder Erstattung vor
- [ ] Angenommen Carrier und Sendungsnummer liegen vor, dann nennt die Antwort beide und weist darauf hin, dass aktuelle Informationen auf der Webseite des Paketdienstes verfügbar sind
- [ ] Angenommen die Antwort wird gesendet, dann geht sie als E-Mail an den Kunden (Empfänger wie bei Antworten aus der App), unter dem technischen Zammad-Nutzer customerserviceassist, mit der Signatur „LOOXIS Kundenservice“ und einem Hinweis „Diese Antwort wurde mit Infos des Paketdienstes automatisch verfasst“ (Wortlaut in den Einstellungen änderbar), und das Ticket steht danach auf „offen“
- [ ] Angenommen die Antwort ist nicht Deutsch, dann hängt CSA keine deutsche Fassung an die Kundenantwort an; die deutsche Fassung steht in der internen Notiz
- [ ] Angenommen eine Antwort wurde gesendet, dann steht am Ticket eine interne Notiz „Automatisch beantwortet (WISMO)“ mit Sendungsdaten und deutscher Fassung
- [ ] Angenommen kein Antworttext lässt sich sicher bilden (Modellfehler, leere Antwort, Platzhalter im Text), dann geht keine Kundenantwort raus, sondern eine interne Notiz

### Schutz vor Doppelantworten
- [ ] Angenommen der Trigger feuert zweimal für dasselbe Ticket (Webhook und Abgleich), dann wird nur einmal geantwortet
- [ ] Angenommen der Versand an Zammad endet unklar (Zeitüberschreitung), dann prüft CSA vor einem neuen Versuch, ob die Antwort schon im Ticket steht
- [ ] Angenommen die eigene Antwort löst wieder einen Zammad-Webhook aus, dann startet daraus keine weitere Bearbeitung
- [ ] Angenommen der n8n-Workflow läuft parallel, dann ist der Betrieb im Trockenlauf unkritisch; vor dem Scharfschalten wird n8n deaktiviert

### Einstellungen und Protokoll
- [ ] Angenommen ein Admin öffnet die Einstellungen, dann kann er Schalter (aus / Trockenlauf / an), Prompt und Modell der WISMO-Antwort sowie Signatur und Hinweiszeile ändern; Änderungen erscheinen im Verlauf wie bei anderen Einstellungen; Mitarbeiter sehen sie nur
- [ ] Angenommen eine WISMO-Bearbeitung lief, dann steht im Protokoll: Ticket, Zeit, Ergebnis (gesendet, Trockenlauf, Notiz, Fehler, übersprungen), Grund, Bestellnummer, Carrier, Sendungsnummer, Zustellproblem ja/nein, Prompt-Version, Modell, Tokens und Dauer; der gesendete Text ist 90 Tage lesbar (verschlüsselt gespeichert) und wird dann geleert
- [ ] Angenommen ein Admin ruft die WISMO-Übersicht auf, dann sieht er die Läufe mit Filter nach Ergebnis und kann einen Lauf öffnen

## Edge Cases
- Kunde nennt mehrere Bestellnummern: es wird die erste erkannte verwendet; weitere stehen in der internen Notiz
- Bestellung hat mehrere Sendungen oder Teillieferungen: Antwort nennt jede Sendung mit Status, mindestens aber die erste
- Marketplace-Tickets (Amazon-Relay-Adresse): Antwort geht an die Adresse, die auch Antworten aus der App nutzen
- Ticket wurde zusammengeführt oder ist schon geschlossen: keine Antwort
- Mitarbeiter öffnet das Ticket und antwortet in dem Moment, in dem die Automatik schreibt: Claim und Prüfung „neuere Nachricht“ verhindern Doppelversand; im Zweifel interne Notiz statt Antwort
- Kundentext enthält die Bestellnummer nur im Betreff oder in einer zitierten Nachricht: wie bei der vorhandenen Erkennung
- Kunde schreibt Deutsch und Englisch gemischt: Sprache der überwiegenden Nachricht
- Sendungsverfolgung kennt die Nummer noch nicht (frisch erzeugtes Label): Antwort sagt, dass die Sendung gerade vorbereitet wird, ohne Datum
- Der Kunde fragt zusätzlich etwas anderes (z. B. Rechnung): Klassifikation „mehrere Anliegen“ blockiert die Automatik

## Technical Requirements
- Security: Zugangsdaten (EOCS, DHL-Tracking, UPS, technischer Zammad-Token) nur in `.env` und `config/services.php`; keine Kundendaten (Adresse, Name) an das Modell, nur Sendungs- und Statusdaten; gesendeter Text verschlüsselt und befristet gespeichert
- Performance: Antwort innerhalb weniger Minuten nach Eingang; Aufrufe im Hintergrund
- Kosten: ein LLM-Aufruf je Ticket, Tokens im Protokoll

## Open Questions
- [ ] UPS-Abholort: Welche UPS-Schnittstelle liefert die Adresse des Access Points (Tracking-Felder, Access-Point-Locator)? Bis zur Klärung verweist die Antwort auf die Webseite von UPS
- [ ] Hinweiszeile „automatisch verfasst“: beibehalten wie in n8n oder weglassen (Transparenz gegenüber dem Kunden)?
- [ ] Werktage: nur Montag bis Freitag oder auch Feiertage berücksichtigen (PROJ-77 hat einen Zeitplan mit Samstag)? Empfehlung: Mo–Fr ohne Feiertagskalender
- [ ] Soll die Antwort bei einer Bestellung ohne Versand nach mehr als 3 Werktagen weiter automatisch gesendet werden oder nur als interne Notiz? Empfehlung: weiter senden ohne Termin, Hinweis intern (siehe Kriterien)
- [ ] `.env.example`: neue Variablen (technischer Zammad-Token, DHL-Tracking-Key, UPS-Zugang) legt Etienne an

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Sofort automatisch senden statt erst Entwurf | n8n sendet heute schon automatisch, Ziel ist der Umzug ohne Qualitätsverlust; zweite Ausnahme neben PROJ-77 vom Ziel „Versand nur nach Klick“, mit Schalter und Trockenlauf als Absicherung | 2026-11-11 |
| Technischer Nutzer customerserviceassist als Absender | Nutzer existiert bereits, Antwort ist klar als automatisch erkennbar, kein Mitarbeitername | 2026-11-11 |
| Nur neue Tickets nach KI-Einstufung `wismo` | kein Warten auf Bestätigung durch Mitarbeiter, kein Risiko durch Wiederholungen bei wieder geöffneten Tickets | 2026-11-11 |
| DHL und UPS wie n8n, UPS-Abholort später | Abdeckung der heutigen Carrier; UPS liefert die Access-Point-Adresse bisher nicht, Recherche nötig | 2026-11-11 |
| Bei „nicht versendet“ Antwort mit Bestellstatus wie n8n | Kunden bekommen Auskunft; keine Terminzusage | 2026-11-11 |
| Hängende Bestellung (mehr als 3 Werktage ohne Versand): Hinweis im Bestellblock jetzt, Kennzeichnung und Nach-oben-Schieben als PROJ-83 | Vorgabe des Auftraggebers: erst Grundfunktion, dann Priorisierung planen | 2026-11-11 |
| Zustellproblem: vorsichtige Antwort plus auffälliger Hinweis im Stil des 30-Tage-Hinweises (PROJ-79) | Wie n8n, aber Mitarbeiter sehen das Problem am Ticket statt nur in der Notiz | 2026-11-11 |
| Ein LLM-Aufruf statt drei | Klassifikation und Bestellnummern-Erkennung existieren bereits in CSA; weniger Kosten und Fehlerquellen | 2026-11-11 |
| Kein deutscher Zweitabsatz an den Kunden | n8n hängt die deutsche Fassung an die Kundenantwort; für Kunden verwirrend, die Fassung steht in der internen Notiz | 2026-11-11 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|

---

## Tech Design (Solution Architect)
_To be added by /architecture_

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
