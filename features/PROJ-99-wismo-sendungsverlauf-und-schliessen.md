# PROJ-99: WISMO liest den ganzen Sendungsverlauf und schließt das Ticket

## Status: In Progress
**Created:** 2026-11-20
**Last Updated:** 2026-11-20

## Dependencies
- Requires: PROJ-82 (WISMO automatisch beantworten), PROJ-97 (Lettermint-Benachrichtigungen), PROJ-88 (Sendungsstatus im Vorschlag, nutzt dieselben Fakten)

## Anlass
- Ticket 2140588: Die automatische Antwort (zugestellt, Datum, Empfänger, Link) war vollständig und abschließend. Trotzdem blieb das Ticket offen und wartete auf einen Agenten.
- 12 von 40 WISMO-Läufen hatten ein Zustellproblem. Der Kunde bekam nur Sendungsnummer, Link und „ein Mitarbeiter meldet sich“, während die Ticketansicht intern den Grund zeigte. Beispiele: 2140425 (Lagerfrist), 2140372 und 2140513 (Adresse bzw. Klingel).
- Bei einer Rücksendung steht am Ende des Verlaufs „zugestellt“, und zwar bei LOOXIS. Mit Postleitzahl liefert der Paketdienst dazu den Namen eines Mitarbeiters als Empfänger („Etienne“, bei UPS „KOCH“). Nur am letzten Status ist das nicht zu erkennen.

## User Stories
- Als Teamleitung möchte ich, dass vollständig beantwortete WISMO-Tickets mit dem Versand geschlossen werden, damit niemand sie noch einmal anfassen muss. Meldet sich der Kunde wieder, öffnet sich das Ticket von selbst.
- Als Kunde möchte ich bei einer Rücksendung erfahren, was passiert ist (Zustellversuche, Abholstelle, Benachrichtigung, Lagerfrist, Adresse) und was ich jetzt tun soll.
- Als Teamleitung möchte ich in `/wismo-antworten` je Lauf den Fall und „automatisiert geschlossen“ sehen und danach filtern, für spätere Auswertungen.

## Acceptance Criteria
- Der Code ordnet jede Sendung anhand des **gesamten** Verlaufs einem Fall zu (`ShipmentHistory`), nie das Modell. Die Fälle sind: `delivered`, `in_transit`, `unknown`, `pickup_ready`, `returned_not_collected`, `returned_address`, `returned_refused`, `address_hold` und `special`. Für den Lauf kommen `not_shipped` und `stuck` hinzu. Bei mehreren Sendungen zählt der schwerste Fall.
- Rücksendung: Ein „zugestellt“ nach dem Beginn der Rücksendung gilt als „wieder bei LOOXIS eingegangen“. Empfänger, Zustellart und Abholort gehen dann nicht an das Modell. „Lagerfrist nicht abgeholt“ und „Annahme verweigert“ gelten auch ohne eigenen Rücksendungstext als Rücksendung.
- Der Grund einer Rücksendung kommt aus dem Rücksendungsereignis oder einem Ereignis derselben Minute, sonst aus dem nächsten früheren Grund. Die Reihenfolge ist Sonderfall > Adresse > verweigert > nicht abgeholt/nicht angetroffen.
- Ein früheres Problem, auf das eine Abholstelle oder Zustellung folgt, gilt nicht mehr als Problem (zum Beispiel Nachentgelt und dann zugestellt, oder ein Zustellversuch und dann Filiale).
- Das Modell bekommt `case`, `history` (wichtige Stationen mit Datum, ohne Scans, Sortierung und Verladung; DHL-Bemerkungen bleiben draußen), `returned_to_us`, `returned_on` und `storage_days`.
- Die Antwort fasst bei Rücksendung, Adressproblem und Sonderfall den Verlauf zusammen. Bei Rücksendung und Adressproblem endet sie mit der Bitte, die Sendungsverfolgung sowie Adresse und Namen an Klingel/Briefkasten zu prüfen bzw. zu bestätigen, dass das Paket entgegengenommen werden kann, und sich bei uns zu melden. Die Lagerfrist steht aus dem Text, sonst „mindestens 5 Tage“. Es gibt keine Zusage für einen Neuversand. Bei jeder Rücksendung an uns steht zusätzlich: Ein erneuter Versand verursacht Kosten, die wir nicht übernehmen, und auch nach Eingang des Pakets ist keine Erstattung möglich, weil das Produkt personalisiert ist (ohne Betrag).
- Benachrichtigung: Die Antwort sagt bei Zustellversuch, Abholstelle und Rücksendung immer „Sie wurden benachrichtigt“. Ist die n8n-Mail („Paket kommt heute“, „Abholhinweis“) laut Lettermint **von uns versendet**, kommen Datum und Uhrzeit des Versands dazu. Maßgeblich ist das Ereignis `processed`, ersatzweise `delivered` oder ein Bounce. Was beim Empfänger passiert, ist egal: Server nicht erreichbar, Mail abgewiesen oder im Spam. Eine Mail, die nur in der Warteschlange stand oder unterdrückt wurde, gilt nicht als versendet. Ein Fehler bei Lettermint bricht die Antwort nicht ab.
- **Schließen:** Die Antwort setzt das Ticket auf `closed`, außer bei `special` und `stuck`. Die interne Notiz sagt „Ticket automatisch geschlossen“. Im Trockenlauf steht in der Notiz, ob geschlossen würde.
- Protokoll: `wismo_runs.shipment_case`, `wismo_runs.shipment_history` (Stationen je Sendung) und `wismo_runs.auto_closed`.
- `/wismo-antworten`: Es gibt die Spalte „Fall“ mit Badge, Filter nach Fall mit Anzahl, das Badge „automatisiert geschlossen“ und den Filter „Automatisiert geschlossen (n)“. Die Detailseite zeigt Fall und Stationen.
- Ticketansicht: Der rote Hinweis „Zustellproblem, bitte prüfen“ erscheint nur noch bei `special` und bei Läufen vor PROJ-99. Rücksendung und Adressproblem bekommen einen gelben Hinweis („Kunde wurde gebeten, sich zu melden“).

## Out of Scope
- Neuversand oder Kosten bei Rücksendungen; das klärt der Mitarbeiter, wenn der Kunde sich meldet.
- Nachträgliche Einordnung alter Läufe; sie bleiben ohne Fall („–“).
- Abholerinnerung per Mail aus CSA.

## Implementation Notes
- `app/Tracking/ShipmentHistory.php` (Fall, Grund, Stationen, Lagerfrist), `TrackingEvent.php`, `DeliveryText::kinds()` / `storageDays()`. `DhlTrackingClient` und `UpsTrackingClient` geben alle Ereignisse weiter. Bei UPS zählt Typ D am Access Point nicht als Zustellung, und ein Rücksendungsstatus nur im `currentStatus` wird ergänzt.
- `TrackingStatus` hat neu `shipmentCase`, `history` und `case()`. `facts()` blendet nach einer Rücksendung Zustellung und Empfänger aus und ersetzt den Status („wieder beim Absender (LOOXIS) eingegangen“ bzw. „auf dem Rückweg“).
- `DeliveryNotices::evaluate()` liefert als viertes Element `sentAt`, `DeliveryNotice::sent()`. `proven()` (PROJ-97) bleibt unverändert.
- `WismoResponder`: Fall, Schließen und Lettermint-Abfrage (nur wenn nicht schlicht zugestellt). `OutgoingReply` geht mit `closed` bzw. `open` raus.
- Prompt `wismo-2026-11-20.3`. Dazu gibt `WismoResponder::replyRules()` dem Modell je Fall wenige verbindliche Regeln mit: Benachrichtigung nennen, Lagerfrist, keine Zustelloptionen nach einer Rücksendung, letzter Absatz mit der passenden Bitte. Mit gpt-5.4-mini allein über den langen Prompt fehlten diese Punkte in Probeläufen. Analyse-Prompt `analysis-2026-11-20.1` mit einem Satz zu `case`, `history` und `returned_to_us`.
- Am 20.11.2026 mit 13 echten Verläufen (DHL/UPS) der bisherigen Läufe geprüft: alle richtig eingeordnet.
- Tests: `tests/Unit/PROJ-99-ShipmentHistoryTest.php` (echte Verläufe), Ergänzungen in `PROJ-82-WismoResponderTest`, `PROJ-82-TrackingClientTest`, `PROJ-82-WismoOverviewTest`, `PROJ-82-OrderNoticesTest` und `PROJ-97-DeliveryNoticesTest`.
