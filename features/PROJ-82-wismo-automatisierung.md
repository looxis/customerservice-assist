# PROJ-82: WISMO-Automatisierung (Umzug von n8n nach CSA)

## Status: In Progress
**Created:** 2026-11-11
**Last Updated:** 2026-11-11 (Backend)

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
- UPS Quantum View und UPS Locator (Abholadresse über Ereignisabonnement und Standortsuche): erst nach Machbarkeitsnachweis mit UPS (Kontoberechtigung, ID-Zuordnung), dann als eigenes Feature; PROJ-82 kommt ohne beides aus
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
- [ ] Angenommen der Carrier ist weder DHL noch UPS, dann nennt die Antwort Carrier und Sendungsnummer und verweist auf die Webseite des Paketdienstes (ohne Statusangaben)
- [ ] Angenommen die Sendungsverfolgung zeigt, dass die Sendung zur Abholung bereitliegt oder irgendwo hinterlegt wurde (Abholort, Filiale, Packstation, Paketshop, Ablageort, Nachbar), und liefert die Angaben dazu, dann stehen Abholort und Adresse bzw. Ablageort direkt im Nachrichtentext, wörtlich wie geliefert
- [ ] Angenommen die Sendungsverfolgung liefert keine belegte Adresse, dann erfindet die Antwort keine; sie sagt ehrlich, dass der genaue Abholort über die Schnittstelle nicht übermittelt wurde, und verweist auf Tracking des Paketdienstes und den Link
- [ ] Angenommen eine UPS-Sendung liegt zur Abholung bereit, dann entscheidet der Code (nicht das Sprachmodell) anhand der UPS-Tracking-Antwort (Status, Ereignisadresse, Zeitbezug, `accessPointInformation`), ob eine Abholadresse gesichert ist; nur ein Status, der Abholbereitschaft belegt, plus vollständige Anschrift (Straße/Hausnummer, PLZ, Ort, Land) aus dem aktuellsten Ereignis zählt, nicht Stadt/PLZ allein, nicht Abholfrist allein, nicht Empfänger- oder Depotadresse
- [ ] Angenommen ein neueres Ereignis (abgeholt, Rücksendung, Umleitung, nicht abholbereit) widerspricht einer Abholadresse, dann wird die alte Adresse nicht genannt
- [ ] Angenommen die Sendung hat mehrere Pakete, dann wird je Paket getrennt entschieden und keine Adresse eines Pakets auf ein anderes übertragen
- [ ] Angenommen der Code hat die Adress- und Statusentscheidung getroffen, dann erhält das Sprachmodell nur die freigegebenen Angaben (Status, Adresse, Frist, Ort); es ergänzt keine Filiale, Frist, Öffnungszeiten oder Abholvoraussetzungen
- [ ] Angenommen die Sendung ist zugestellt, dann nennt die Antwort Datum, Uhrzeit und Zustellart, sofern geliefert, und den Namen des Annehmenden nur, wenn er in den Daten steht, ohne Vermutung zur Beziehung
- [ ] Angenommen die Abfrage des Paketdienstes schlägt fehl, dann wird bis zu dreimal wiederholt; danach interne Notiz mit dem Grund und keine Kundenantwort

### Bestellung noch nicht versendet
- [ ] Angenommen die Bestellung ist gefunden, hat aber keine Sendung, dann sendet CSA eine Antwort mit dem Bestellstatus aus EOCS (wie n8n), ohne Liefertermin zuzusagen
- [ ] Angenommen der Bestelleingang liegt mehr als 3 Werktage (Montag bis Freitag, ohne Feiertagskalender) zurück und es gibt keine Sendung, dann erscheint im Bestellblock der Ticketseite ein Hinweis „Bestellung seit … Werktagen ohne Versand, möglicherweise hängen geblieben“ im selben Stil und an derselben Stelle wie der Versandalter-Hinweis (PROJ-79), und die interne Notiz nennt das ebenfalls; die Kundenantwort nennt weiterhin keinen Liefertermin

### Zustellproblem
- [ ] Angenommen die Trackinghistorie enthält ein Zustellproblem (zum Beispiel „zurück an Absender“, Annahme verweigert, nicht abgeholt, unzustellbar, Adresse nicht auffindbar), dann behandelt CSA die Sendung nicht als zugestellt, auch wenn ein Status „zugestellt“ vorkommt
- [ ] Angenommen ein Zustellproblem wurde erkannt, dann sendet CSA eine vorsichtige Antwort (es scheint Probleme bei der Zustellung zu geben; Details über die Sendungsnummer auf der Webseite des Paketdienstes; ein Mitarbeiter schaut sich den Fall an und meldet sich; Kunde soll Lieferadresse bestätigen und Namen an Klingel und Briefkasten prüfen) ohne Zustelldetails, Zeitpunkte, Abholorte oder Empfängernamen aus den Trackingdaten
- [ ] Angenommen ein Zustellproblem wurde erkannt, dann bleibt das Ticket offen und trägt die interne Notiz „Zustellproblem, bitte prüfen“ mit dem Grund aus den Trackingdaten
- [ ] Angenommen ein Zustellproblem wurde erkannt, wenn die Ticketseite geöffnet wird, dann erscheint im Bestellblock ein auffälliger Hinweis im selben Stil und an derselben Stelle wie der Versandalter-Hinweis (rot, mit Grund und Datum)

### Antwort, Versand, Zammad
- [ ] Angenommen die Antwort entsteht, dann wird sie mit einem einzigen LLM-Aufruf in der Sprache der Kundenanfrage verfasst, mit Anrede (Du/Sie) angepasst und in der Wir-Form
- [ ] Angenommen der Kunde fragt ausdrücklich nach Erstattung oder Rückgabe, dann erklärt die Antwort freundlich, dass bei personalisierten Produkten keine Erstattung möglich ist; ohne ausdrückliche Frage schlägt sie nie Rücksendung, Stornierung oder Erstattung vor
- [ ] Angenommen eine Sendungsnummer liegt vor, dann steht sie immer im Nachrichtentext („Ihre Sendungsnummer lautet: …“), zusammen mit dem Namen des Carriers
- [ ] Angenommen eine Sendungsnummer liegt vor, dann weist die Antwort je nach Trackingstatus darauf hin, dass weitere Informationen auf der Webseite des Paketdienstes [Carrier] unter „Tracking“ bzw. „Sendungsverfolgung“ durch Eingabe der Sendungsnummer verfügbar sind, und, solange die Sendung nicht zugestellt ist, dort teils auch Zustelloptionen angepasst oder geändert werden können
- [ ] Angenommen eine Sendungsnummer und der Carrier DHL oder UPS liegen vor, dann steht zusätzlich als ergänzender Service ein https-Trackinglink im Klartext (kein HTML) mit fester Domain und URL-kodierter Sendungsnummer im Text, dazu der Hinweis, dass er kopiert und im Browser eingefügt werden kann oder alternativ die Webseite des Paketdienstes aufgerufen und die Sendungsnummer eingegeben werden kann; der Link ist nur Zusatz, alle Angaben der Antwort stehen auch ohne ihn im Text
- [ ] Angenommen das Ticket stammt aus dem Amazon-Kanal oder einem anderen Kanal, dann gilt dieselbe Gestaltung (Sendungsnummer und Status im Text, Hinweis auf die Webseite des Paketdienstes, Klartext-Trackinglink als Zusatz), weil nicht garantiert ist, dass Amazon Links weiterleitet
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
- Marketplace-Tickets (Amazon-Relay-Adresse über Amazon Messaging): Antwort geht an die Adresse, die auch Antworten aus der App nutzen; Links können von Amazon entfernt werden, deshalb stehen alle Angaben auch im Text
- UPS-Abholort widersprüchlich oder unvollständig (nur Stadt, nur Frist, Depot): keine Adresse, ehrlicher Satz plus Tracking-Hinweis und Link
- Ticket wurde zusammengeführt oder ist schon geschlossen: keine Antwort
- Mitarbeiter öffnet das Ticket und antwortet in dem Moment, in dem die Automatik schreibt: Claim und Prüfung „neuere Nachricht“ verhindern Doppelversand; im Zweifel interne Notiz statt Antwort
- Kundentext enthält die Bestellnummer nur im Betreff oder in einer zitierten Nachricht: wie bei der vorhandenen Erkennung
- Kunde schreibt Deutsch und Englisch gemischt: Sprache der überwiegenden Nachricht
- Sendungsverfolgung kennt die Nummer noch nicht (frisch erzeugtes Label): Antwort sagt, dass die Sendung gerade vorbereitet wird, ohne Datum
- Der Kunde fragt zusätzlich etwas anderes (z. B. Rechnung): Klassifikation „mehrere Anliegen“ blockiert die Automatik

## Technical Requirements
- Security: Zugangsdaten (EOCS, DHL-Tracking, UPS, technischer Zammad-Token) nur in `.env` und `config/services.php`; keine Kundendaten (Adresse, Name) an das Modell, nur Sendungs- und Statusdaten; gesendeter Text verschlüsselt und befristet gespeichert
- Links: Domains der Trackinglinks (`dhl.de`, `ups.com`) müssen in den erlaubten Link-Hosts der App stehen (`ups.com` fehlt bisher); Linkform für UPS `https://www.ups.com/track?loc=de_DE&tracknum=<kodiert>`, vor Freigabe mit einem Testfall im Browser prüfen; keine Inhalte der Tracking-Webseite übernehmen
- UPS-Daten: Antwortfelder aus der offiziellen UPS-Tracking-Spezifikation (Revision in `/architecture` festhalten); Zeitstempel ohne bekannte Zeitzone nicht stillschweigend als UTC lesen; Zugangsdaten nur in `.env`, keine Rohantworten, Trackingnummern oder Kundenadressen in Logs
- Performance: Antwort innerhalb weniger Minuten nach Eingang; Aufrufe im Hintergrund
- Kosten: ein LLM-Aufruf je Ticket, Tokens im Protokoll

## Open Questions
- [x] UPS-Abholort: welche Schnittstelle? → Recherche liegt vor (2026-11-11): Tracking-API `track/v1/details` liefert Ereignisadressen, aber `accessPointInformation` nur `pickupByDate`; Quantum View (`AccessPointLocationID`, `UAPAddress`) und Locator sind bedingte Wege ohne belegte ID-Zuordnung. Entscheidung: PROJ-82 nutzt nur die Tracking-API mit strenger Prüfung, sonst ehrlicher Fallback mit Link
- [x] UPS: Liefert das Versenderkonto bei umgeleiteten Sendungen eine Abholadresse in der Tracking-API? → Ja (2026-11-11, Referenzfall Ticket 2140267): `accessPointInformation.address` liefert Straße, PLZ, Ort und Land des Access Points (Firmenname leer, `pickupByDate` fehlte); kein Quantum View und kein Locator nötig. Der Status des Falls war „Letzter Zustellversuch unternommen“ (Empfänger nicht anwesend), eine Abholbereitschaft sagt UPS dort nicht ausdrücklich. Deshalb nennt die Antwort die Adresse neutral („laut UPS als Abholort angegeben“) und behauptet „liegt zur Abholung bereit“ nur bei passendem Statustext. Offen bleibt: der Abholort wird am echten Fall unabhängig bestätigt (siehe nächste Frage). Ergebnis bestimmt, ob eine Adressregel aktiviert wird
- [ ] UPS: Abholort von Ticket 2140267 unabhängig bestätigen (Kundenkontakt oder UPS-Seite), bevor die Automatik scharf geschaltet wird
- [ ] UPS Quantum View und Locator (Verfügbarkeit, Berechtigung, ID-Zuordnung `AccessPointLocationID` ↔ `LocationID`/`PublicAccessPointID`): Anfrage an UPS; nur bei positivem Ergebnis eigenes Feature
- [ ] Wo steht der verbindliche Status, der „am Access Point zugestellt“ von „vom Empfänger abgeholt“ unterscheidet (UPS-Statuscodes aus Referenzfällen)?
- [x] Hinweiszeile „automatisch verfasst“ → bleibt, Wortlaut in den Einstellungen änderbar (2026-11-11)
- [x] Werktage → Montag bis Freitag, ohne Feiertagskalender (2026-11-11)
- [x] Antwort bei Bestellung ohne Versand nach mehr als 3 Werktagen → wird weiter automatisch gesendet, ohne Termin; der Hinweis steht nur intern (Bestellblock und Notiz) (2026-11-11)
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
| Hinweiszeile „automatisch verfasst“ bleibt | Transparenz gegenüber dem Kunden, wie in n8n; Wortlaut in den Einstellungen änderbar | 2026-11-11 |
| Werktage = Montag bis Freitag ohne Feiertagskalender | einfach und nachvollziehbar; der Hinweis ist unkritisch, ein Tag zu früh schadet nicht | 2026-11-11 |
| Bei hängender Bestellung weiter automatisch antworten, ohne Termin | Kunde bekommt zeitnah eine Auskunft; Mitarbeiter sieht das Problem intern (Bestellblock, Notiz), Priorisierung folgt in PROJ-83 | 2026-11-11 |
| Sendungsnummer und Status immer im Nachrichtentext, Trackinglink nur als Klartext-Zusatz (alle Kanäle) | Amazon Messaging verbietet externe Links grundsätzlich und kann sie entfernen; Trackinglinks von Paketdiensten sind die Ausnahme, aber ohne Garantie; deshalb muss die Antwort ohne Link vollständig sein (Vorgabe des Auftraggebers, 2026-11-11) | 2026-11-11 |
| UPS-Abholadresse nur über die Tracking-API und nur bei belegter, vollständiger, aktueller Adresse; sonst ehrlicher Fallback mit Link | Eine falsche Filiale darf nie genannt werden; Quantum View und Locator sind unbelegte Zusatzwege und blockieren PROJ-82 nicht | 2026-11-11 |
| Adress- und Statusentscheidung im Code, Sprachmodell erhält nur freigegebene Daten | Verhindert erfundene Filialen, Fristen und Öffnungszeiten | 2026-11-11 |
| Ein LLM-Aufruf statt drei | Klassifikation und Bestellnummern-Erkennung existieren bereits in CSA; weniger Kosten und Fehlerquellen | 2026-11-11 |
| Kein deutscher Zweitabsatz an den Kunden | n8n hängt die deutsche Fassung an die Kundenantwort; für Kunden verwirrend, die Fassung steht in der internen Notiz | 2026-11-11 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|
| Weiche direkt an der Klassifikation, kein eigener Webhook | Einstufung `wismo` liegt dort schon vor; kein zweites Erkennen, keine Wartezeit auf Mitarbeiter | 2026-11-11 |
| Ein Protokolleintrag je Ticket (einmalig) als Sperre gegen Doppelantworten, Prüfung in Zammad bei unklarem Versand | wie PROJ-77; Doppelte Kundenantworten sind das größte Risiko | 2026-11-11 |
| Entscheidungen (Zustellproblem, Abholadresse, hängende Bestellung) im Code, Modell erhält nur freigegebene Daten | keine erfundenen Filialen, Fristen, Zusagen | 2026-11-11 |
| Einheitliches Sendungsstatus-Format für DHL und UPS | Antwort-Baustein bleibt unabhängig vom Carrier, weitere Carrier später ergänzbar | 2026-11-11 |
| Hinweise am Ticket aus gespeichertem Lauf und Bestelldaten, kein Carrier-Abruf beim Seitenaufruf | schnelle Seite, keine Last und Kosten bei jedem Öffnen | 2026-11-11 |
| Technischer Zammad-Nutzer mit eigenem Token; eigene Artikel werden beim Abgleich erkannt | klar erkennbarer Absender, Schleifenschutz | 2026-11-11 |
| Zammad-Schreibstelle um Status "offen" erweitern | wie n8n; bisher nur "geschlossen" und "Erinnerung" | 2026-11-11 |
| Schalter in drei Stufen (aus, Trockenlauf, an), Standard aus | sicherer Vergleich mit n8n vor dem Scharfschalten | 2026-11-11 |
| Prompt und Modell über die vorhandenen Einstellungen mit Verlauf und Versionsnummer | gleicher Weg wie alle anderen Prompts; Modelltest vorhanden | 2026-11-11 |
| Gesendeter Text verschlüsselt und nach 90 Tagen geleert | enthält Kundenbezug; wie bei Sendungsnummer-Automatik | 2026-11-11 |
| Nur UPS-Tracking-API, kein Quantum View, kein Locator | Wege laut Recherche unbelegt; Fallback mit Link genügt | 2026-11-11 |
| Werktage zentral (Mo-Fr, ohne Feiertage), wiederverwendbar für PROJ-83 | eine Regel, ein Ort | 2026-11-11 |
| Keine neuen Pakete | alle Bausteine vorhanden | 2026-11-11 |

---

## Tech Design (Solution Architect)

### Backend nötig? Ja
Die Automatik läuft im Hintergrund, ohne dass jemand eine Seite öffnet; dazu braucht es Datenbank, Warteschlange und Zugriff auf Zammad, EOCS, DHL, UPS und das Sprachmodell. Eine Oberfläche gibt es nur als kleine Ergänzung (Hinweise am Ticket, Admin-Übersicht, Einstellungen).

### Ablauf (Bausteine)
```
Neues Ticket in Zammad
  -> vorhandener Ticket-Eingang (PROJ-66) -> vorhandene Klassifikation (PROJ-80, "wismo")
       -> NEU: Weiche "WISMO-Bearbeitung starten?"  (Schalter, Konfidenz, Testmenge, nicht schon bearbeitet)
            -> NEU: WISMO-Bearbeitung (im Hintergrund, je Ticket einmal)
                 1. Ticket prüfen        (kein Mitarbeiter hat geantwortet, keine neuere Kundennachricht, nicht zusammengeführt)
                 2. Bestellnummer finden (vorhandene Erkennung, ohne KI)
                 3. Bestellung laden     (vorhandene EOCS-Anbindung)
                 4. Sendungsstatus holen (NEU: DHL- und UPS-Abfrage)
                 5. Entscheiden im Code  (Zustellproblem? Abholadresse gesichert? hängende Bestellung?)
                 6. Antwort formulieren  (NEU: ein KI-Aufruf, nur mit freigegebenen Daten)
                 7. Senden oder Notiz    (vorhandene Zammad-Schreibstelle, technischer Nutzer)
                 8. Protokoll schreiben
```

### Komponenten (PM-Sicht)
```
Hintergrund
+-- Weiche nach der Klassifikation        (neu, kleine Erweiterung der Klassifikation)
+-- WISMO-Bearbeitung                     (neu, Steuerung der acht Schritte)
+-- Sendungsstatus-Abfrage                (neu)
|   +-- DHL-Abfrage (mit Empfänger-PLZ)
|   +-- UPS-Abfrage (Anmeldung wird zwischengespeichert)
|   +-- Auswertung: einheitlicher Sendungsstatus, Zustellproblem, Abholort
+-- Antwort-Baustein                      (neu: Prompt + Modell, Ausgabe: Antwort in Kundensprache + deutsche Fassung)
+-- Versand an Zammad                     (vorhanden, kleine Erweiterung: Status "offen" nach dem Senden)

Oberfläche
+-- Ticketseite, Bestellblock             (vorhanden, erweitert)
|   +-- Hinweis "Versand liegt X Tage zurück"      (vorhanden, PROJ-79)
|   +-- NEU: Hinweis "Zustellproblem" (aus dem gespeicherten Lauf, kein neuer Abruf beim Öffnen)
|   +-- NEU: Hinweis "Bestellung seit X Werktagen ohne Versand" (aus Bestelldaten, ohne Abruf)
+-- Einstellungen                         (vorhanden, erweitert)
|   +-- Schalter aus / Trockenlauf / an
|   +-- Prompt und Modell der WISMO-Antwort
|   +-- Signatur und Hinweiszeile "automatisch verfasst"
+-- Admin: WISMO-Übersicht                (neu, Liste der Läufe mit Filter nach Ergebnis, Lauf öffnen)
```

### Daten (in Alltagssprache)
Neu: **WISMO-Lauf** (ein Eintrag je Ticket, nur einmal möglich). Er enthält:
- Ticket (Zammad-Nummer) und Zeitpunkt
- Ergebnis: gesendet, Trockenlauf, Notiz geschrieben, Fehler, übersprungen, mit Grund
- Bestellnummer, Carrier, Sendungsnummer
- Zustellproblem ja/nein mit Grund aus den Trackingdaten; hängende Bestellung ja/nein
- Prompt-Version, Modell, Tokens, Dauer
- gesendeter Text und deutsche Fassung, verschlüsselt, nach 90 Tagen geleert
- Zustand der Bearbeitung (wartet, sendet, fertig, Fehler) und Zahl der Versuche, damit nie doppelt gesendet wird

Einstellungen (vorhandene Ablage mit Verlauf): Schalter, Prompt, Modell, Signatur, Hinweiszeile.
Zugangsdaten (DHL-Tracking-Key, UPS-Anmeldung, Token des technischen Zammad-Nutzers) liegen nur in der Serverkonfiguration, nie in der Datenbank.

### Tech-Entscheidungen (begründet)
- **Auslöser direkt an der Klassifikation, nicht über Webhook-Kopie wie n8n.** Die Klassifikation sieht jedes neue Ticket ohnehin und weiß schon "wismo"; so entfällt die separate Erkennung, und die Weiche nutzt die Roh-Einstufung, ohne auf Mitarbeiter zu warten.
- **Ein Eintrag je Ticket als Sperre gegen Doppelantworten.** Das Ticket wird zuerst "reserviert", erst dann gesendet. Bei unklarem Ausgang (Zeitüberschreitung) wird vor einem neuen Versuch in Zammad nachgesehen, ob die Antwort schon da ist (gleiche Idee wie bei der Sendungsnummer-Automatik PROJ-77).
- **Entscheidungen im Code, nicht im Sprachmodell.** Zustellproblem, gesicherte Abholadresse und Werktage-Prüfung berechnet der Code; das Modell formuliert nur und bekommt nur freigegebene Angaben. So kann es keine Filiale oder Frist erfinden.
- **Einheitlicher Sendungsstatus für DHL und UPS.** Beide Abfragen liefern dasselbe interne Format (Status, Ort, Zeit, voraussichtliche Lieferung, Zustellart, Abholort, Zustellproblem); weitere Carrier lassen sich später ergänzen, ohne Antwort-Baustein und Prompt anzufassen.
- **Hinweise am Ticket aus dem gespeicherten Lauf.** Die Ticketseite ruft Carrier nie beim Öffnen ab; das "Zustellproblem" kommt aus dem Protokolleintrag, die "hängende Bestellung" aus den schon geladenen Bestelldaten, im selben Stil wie der Versandalter-Hinweis.
- **Technischer Zammad-Nutzer mit eigenem Token.** Alle bisherigen Schreibzugriffe laufen unter Mitarbeitern; die Automatik braucht einen eigenen, klar erkennbaren Absender. Eigene Artikel dieses Nutzers werden beim Abgleich erkannt, damit die Antwort keine neue WISMO-Bearbeitung auslöst.
- **Status "offen" nach dem Senden.** Bisher kennt die Schreibstelle nur "geschlossen" und "Erinnerung"; sie wird um "offen" erweitert (wie n8n), damit Mitarbeiter bei Rückfragen sofort sehen, dass das Ticket weiter bearbeitet werden kann.
- **Drei Stufen für den Schalter (aus, Trockenlauf, an), Standard aus.** Trockenlauf schreibt nur Notiz und Protokoll, so lässt sich die Qualität gegen die n8n-Antworten vergleichen, bevor scharf geschaltet und n8n abgestellt wird.
- **Nur UPS-Tracking, kein Quantum View, kein Locator.** Beides ist laut Recherche unbelegt (Berechtigung, Kennungen); die Abholadresse wird nur genannt, wenn der Code sie aus den Ereignissen belegen kann.
- **Prompt und Modell über die vorhandenen Einstellungen.** Admins können beides ändern, mit Verlauf und Versionsnummer im Protokoll (wie bei den anderen Prompts); ein Modelltest vor dem Speichern gibt es schon.
- **Wiederholungen begrenzt (drei Versuche) bei technischen Fehlern.** Danach interne Notiz statt Kundenantwort: Im Zweifel macht ein Mensch weiter.
- **Zeitrechnung Montag bis Freitag ohne Feiertage** für die 3-Werktage-Prüfung, im Code zentral, damit PROJ-83 sie wiederverwendet.
- **Trackinglinks:** Die Hosts `dhl.de` und `ups.com` müssen in den erlaubten Link-Hosts stehen (`ups.com` fehlt bisher); der Link wird nur aus fester Domain und kodierter Sendungsnummer gebaut.

### Wiederverwendung (nichts doppelt bauen)
Klassifikation und Ticket-Eingang, Bestellnummern-Erkennung, EOCS-Anbindung mit Sendungsdaten und PLZ, Zammad-Schreibstelle (Antwort, Notiz, Doppelversand-Prüfung), Aufruf des Sprachmodells mit Protokoll der Tokens, Prompt- und Modell-Einstellungen mit Verlauf, Hinweis-Baustein des Versandalters (PROJ-79), Muster der Sendungsnummer-Automatik (PROJ-77).

### Dependencies (Pakete)
Keine neuen Pakete: HTTP-Abfragen, Warteschlange, Verschlüsselung und Sprachmodell-Anbindung sind vorhanden.

### Neue Zugangsdaten (Serverkonfiguration, legt Etienne in `.env` an)
Token des technischen Zammad-Nutzers, DHL-Tracking-API-Key (getrennt vom Retouren-Zugang), UPS-Zugang (Client-Kennung und Geheimnis).

### Teststrategie (Überblick)
Alle externen Systeme werden im Test nachgestellt (Zammad, EOCS, DHL, UPS, Sprachmodell). Abgedeckt: zugestellt, unterwegs, Abholort mit und ohne Adresse, UPS-Adresse unvollständig oder überholt, Zustellproblem, keine Bestellnummer, Bestellung nicht gefunden, nicht versendet, hängende Bestellung, Fremdsprache, Doppelauslösung, Schalter aus und Trockenlauf, Mitarbeiter hat schon geantwortet, Link-Form und Klartext-Ausgabe.

### Reihenfolge der Umsetzung (Vorschlag)
1. Grundlagen: Protokolltabelle, Einstellungen, Zugangsdaten-Konfiguration, "offen" in der Schreibstelle
2. Sendungsstatus-Abfrage (DHL, UPS) mit einheitlichem Format und Tests
3. Entscheidungslogik im Code (Zustellproblem, Abholadresse, Werktage)
4. Antwort-Baustein: Prompt, Modell, Klartext-Link, Signatur
5. WISMO-Bearbeitung und Weiche an der Klassifikation, zuerst nur Trockenlauf
6. Hinweise im Bestellblock und Admin-Übersicht
7. Vergleichslauf gegen n8n, dann scharf schalten, n8n abschalten

## Implementation Notes (Frontend, 2026-11-11)
Gebaut:
- Einstellungsseite „WISMO-Automatik“ (`/einstellungen/wismo-automatik`) mit den Gruppen `wismo_mode` (neue Gruppenart `CHOICE`: aus, Trockenlauf, an; Standard aus), `wismo_signature` und `wismo_notice` über die vorhandene Einstellungs-Registry (Verlauf, Wiederherstellen, Zurücksetzen, nur Admins speichern). Controller `SettingsController::wismo/updateWismo`, `WismoSettingsRequest`, Routen.
- Hinweis „Seit X Werktagen ohne Versand, möglicherweise hängen geblieben“ im Bestellblock (`ticket/stuck-order-notice`, aus Bestelldaten, Berechnung `EocsOrder::workdaysSinceOrdered` und `stuckWorkdays`, Montag bis Freitag, ohne Feiertage).
- Hinweis „Zustellproblem, bitte prüfen“ im Bestellblock (`ticket/delivery-problem-notice`, Prop `deliveryProblem` am `order-block`); wird erst mit Daten gefüllt, wenn das Protokoll (Backend) existiert.
- Tests: `PROJ-82-WismoSettingsTest`, `PROJ-82-OrderNoticesTest`, `PROJ-82-StuckOrderTest`.

Noch offen für `/backend`:
- Prompt „WISMO-Antwort“ und Modell `model_wismo` in „Prompts und Modell“ (braucht `resources/prompts/wismo.md`, `config/analysis.php`, Registry-Einträge; der Text der Seite spricht dann von acht Prompts).
- Datenmodell WISMO-Lauf, Füllen der Prop `deliveryProblem` aus dem Lauf (in `ticket/orders` und der Ticketseite), Admin-Übersicht der Läufe (Liste, Filter, Lauf öffnen) samt Seitenleiste und Route.

## Implementation Notes (Backend, 2026-11-11)
Gebaut:
- **Protokoll:** Tabelle `wismo_runs` (ein Eintrag je Ticket, `zammad_id` eindeutig), Modell `WismoRun` mit Factory; die Antworttexte sind verschlüsselt, `wismo:purge` (täglich 04:30) leert sie nach 90 Tagen.
- **Sendungsstatus:** `app/Tracking/` mit `DhlTrackingClient` (DHL-Key, PLZ des Empfängers), `UpsTrackingClient` (OAuth, Token im Zwischenspeicher, `locale=de_DE`), `TrackingStatus`, `Carriers` (Erkennung und Links), `DeliveryText` (Zustellproblem- und Abholort-Regeln aus n8n), `ShipmentTracker`.
- **Entscheidung im Code:** Der UPS-Abholort stammt aus `accessPointInformation` und zählt nur bei vollständiger Anschrift (Straße, PLZ, Ort); bei Zustellproblem oder zugestellter Sendung wird kein Abholort genannt.
- **Antwort:** `WismoAgent` und Prompt `resources/prompts/wismo.md` (Einstellungen: Prompt „WISMO-Antwort“, Modell `model_wismo`, Standard `WISMO_MODEL` oder `SUMMARY_MODEL`). Die Antwort wird geprüft: Sendungsnummer und Trackinglink müssen im Text stehen, sonst keine Kundenantwort, sondern Notiz.
- **Ablauf:** `WismoResponder` (Prüfung des Tickets, Bestellnummer ohne KI, EOCS, Abfrage, KI, Versand mit Status `offen`, Notiz), `AnswerWismoTicket` (Wiederholung nach 2 und 4 Minuten, nach drei Versuchen Notiz), Weiche in `LabelClassifier` (nur Quelle „Eingang“, Kategorie wismo, nicht unklar, nicht mehrere Anliegen, Konfidenz ab Schwellenwert, nicht Testmenge, Schalter nicht aus).
- **Sicherheit gegen Doppelantworten:** Der Protokolleintrag wird zuerst angelegt; vor dem Senden steht er auf „sending“; bei unklarem Ausgang (Zeitüberschreitung) sendet die App nie erneut, es bleibt „Versand unklar“ mit interner Notiz.
- **Oberfläche:** Admin-Übersicht `/wismo-antworten` (Filter, Details), Seitenleiste „WISMO-Antworten“, Zustellproblem-Hinweis am Ticket aus dem gespeicherten Lauf.
- Tests: `PROJ-82-TrackingClientTest`, `PROJ-82-WismoResponderTest`, `PROJ-82-WismoOverviewTest`.

Abweichungen von der Spec:
- Eigene Artikel des technischen Nutzers lösen keine neue Bearbeitung aus, weil der Eintrag je Ticket nur einmal entsteht und die Weiche nur an der Klassifikation hängt (kein Webhook-Pfad); ein eigener Nutzer-Filter im Ticket-Sync war deshalb nicht nötig.
- Die Kunden-Antwort wird gesendet, auch wenn der Bestelleingang mehr als 3 Werktage zurückliegt; der Hinweis steht intern (Bestellblock, Notiz), wie entschieden.

Noch nicht live geprüft: DHL-Tracking (`DHL_TRACKING_API_KEY`) und der Versand/Notizen unter dem technischen Zammad-Nutzer (Token `ZAMMAD_TOKEN`; `ZAMMAD_REPLY_TOKEN` ist nur für ein getrenntes Token nötig, Entscheidung 2026-11-11). Beides ist per HTTP-Nachbildung getestet, nicht live.

## QA Test Results

**Geprüft am:** 2026-11-11 · **Art der Prüfung:** automatisierte Tests, Code-Durchsicht gegen die Kriterien, ein echter UPS-Abruf (nur lesend, Ticket 2140267). **Nicht möglich in dieser Sitzung:** Prüfung im Browser (Chrome, Firefox, Safari, 375/768/1440 px), echter DHL-Abruf (Key war zum Prüfzeitpunkt nicht eingetragen), echter Versand unter dem technischen Zammad-Nutzer.

### Automatisierte Tests
- Neu: `PROJ-82-TrackingClientTest` (12), `PROJ-82-WismoResponderTest` (18), `PROJ-82-WismoOverviewTest` (6), `PROJ-82-WismoSettingsTest` (6), `PROJ-82-OrderNoticesTest` (4), `PROJ-82-StuckOrderTest` (3): alle grün.
- Gesamte Suite: 2509 Tests, 2506 grün, 2 übersprungen, 1 rot: `PROJ-36-AnalysisIntegrationTest`, Fall „Eisberg“ (PLAYBOOK-019 erreicht die Analyse nicht). Der Test liest die echte Knowledge Base und fälscht den Router; PROJ-82 ändert weder Wissen noch Router. Die Ursache liegt sehr wahrscheinlich in einer Wissensänderung (z. B. PLAYBOOK-019), ist aber nicht gegen den Stand vor PROJ-82 gegengeprüft. Bitte getrennt klären.

### Kriterien (Auswahl nach Abschnitt)
| Abschnitt | Ergebnis | Beleg |
|-----------|----------|-------|
| Auslöser und Schalter | bestanden | Weiche in `LabelClassifier` getestet (klar, andere Kategorie, unklar, mehrere Anliegen, Konfidenz, alte Quelle, Testmenge, Schalter aus); Trockenlauf und „aus“ getestet |
| Bestellnummer und Bestellung | bestanden | keine Nummer, EOCS leer, EOCS-Ausfall mit drei Versuchen und Notiz |
| Sendungsstatus (DHL, UPS) | bestanden (UPS live, DHL nachgestellt) | UPS-Aufbau am echten Fall geprüft; DHL nur mit nachgestellter Antwort |
| Bestellung noch nicht versendet | bestanden | Antwort mit Status, Hinweis nach drei Werktagen im Bestellblock (Unit- und Komponententests) und in der Notiz |
| Zustellproblem | bestanden | Hinweis am Ticket, vorsichtige Antwort (Modell erhält keine Details), Notiz |
| Antwort, Versand, Zammad | bestanden (Versand nachgestellt) | Status `open`, Signatur, Hinweiszeile, Sendungsnummer und Link im Text, deutsche Fassung nur in der Notiz |
| Schutz vor Doppelantworten | bestanden | einmal je Ticket, unklarer Versand wird nicht wiederholt, schon beantwortet oder neuere Nachricht stoppt |
| Einstellungen und Protokoll | bestanden | Seite, Admin-Rechte, Prüfungen, Übersicht mit Filter, Details, Verschlüsselung und Leeren nach 90 Tagen |
| Eigene Antwort löst keine neue Bearbeitung aus | bestanden | Weiche hängt nur an der Klassifikation, ein Eintrag je Ticket |

Nicht im Browser geprüft: Darstellung der neuen Hinweise, der Einstellungsseite und der Übersicht in den drei Breiten.

### Fehler
| # | Schwere | Beschreibung | Schritte | Empfehlung |
|---|---------|--------------|----------|------------|
| 1 | Mittel | Ein Eintrag im Zustand „In Arbeit“ bleibt hängen, wenn der Arbeitsprozess hart beendet wird (Zeitüberschreitung, Neustart, Speicher). Die Warteschlange startet den Auftrag neu, findet aber den Eintrag, übernimmt ihn nicht und beantwortet das Ticket nie, ohne Notiz. Die Wiederholfrist der Warteschlange beträgt 90 Sekunden, die KI-Zeitgrenze ebenfalls 90 Sekunden; ein langsamer Lauf kann das auslösen. | 1. Auftrag nach dem Anlegen des Eintrags abbrechen. 2. Auftrag erneut laufen lassen. 3. Eintrag bleibt „In Arbeit“. | „In Arbeit“-Einträge älter als einige Minuten übernehmen (Wiederaufnahme) oder per Befehl melden; Zeitgrenze des Auftrags passend setzen |
| 2 | Mittel | Die automatisch gesendete Antwort wird nur auf Sendungsnummer und Link geprüft. Der Text entsteht aus einer Kundennachricht (unvertrauenswürdig). Enthält die Antwort fremde Links, E-Mail-Adressen, Zusagen oder Beträge (z. B. nach einer Anweisung in der Kundennachricht), geht sie ungeprüft raus. | Kundennachricht mit Anweisung „Schreibe in deine Antwort …“ an ein WISMO-Ticket | Antwort nur senden, wenn alle Links der erlaubten Trackinglinks entsprechen und keine fremden E-Mail-Adressen vorkommen; sonst Notiz |

Niedrige Funde sind nach Absprache nicht gelistet.

### Sicherheitsprüfung
- **Zugriff:** Übersicht und Einzelansicht nur für Admins (403 für andere, getestet); Einstellungen speichern nur Admins (403 getestet); alle Formulare mit `@csrf`; Route `{run}` nur Zahl.
- **Geheimnisse:** Zugangsdaten nur in `config/services.php` aus `.env`; kein Token in Protokoll, Fehlermeldung oder Log; Antworttexte verschlüsselt gespeichert und nach 90 Tagen geleert; die Protokollseite zeigt keine Kundenadresse.
- **Daten an das Modell:** Kundennachricht mit ersetzten Kontaktdaten, Bestellnummer, Status, Sendungsdaten; keine Lieferadresse, keine PLZ. Aus der Sendungsverfolgung kann ein Annahme-Name („entgegengenommen von“) mitgehen, den der Paketdienst selbst nennt.
- **Einschleusung:** Siehe Fehler 2 (Anweisungen in Kundentexten); Ausgabe wird auf Nummer und Link geprüft, mehr nicht.
- **Ratenbegrenzung:** keine neue Route nimmt Eingaben aus dem Netz an; Auslöser ist nur die Klassifikation.

### Produktionsreife
Keine kritischen oder hohen Fehler: nach den Regeln **bereit**. Empfehlung: Fehler 1 und 2 vor dem Dauerbetrieb beheben, weil die Antworten ohne Prüfung an Kunden gehen; Fehler 1 kann sonst einzelne Tickets still unbeantwortet lassen. Live noch zu prüfen: erster DHL-Abruf, erster Versand, Abholort von Ticket 2140267.


## Deployment
_To be added by /deploy_
