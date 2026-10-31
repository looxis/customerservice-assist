# PROJ-66: Automatischer Ticket-Eingang und Datenmodell

## Status: In Progress
**Created:** 2026-10-31
**Last Updated:** 2026-10-31

## Dependencies
- Requires: PROJ-6 (Zammad-Ticket laden) – `ZammadClient`, Aufbereitung der Nachrichten
- Requires: PROJ-5 (Nutzerauswahl) – CSA-Mitarbeiter, Zuordnung zur Zammad-ID
- Requires: PROJ-9, PROJ-11 (Fallanalyse, Protokoll) – Analysen bestimmen den Bearbeitungsstatus
- Vorlage für: PROJ-67 (Ticketübersicht auf der Startseite), PROJ-20 (Automatische Analyse bei Eingang)

## Hintergrund
CSA kennt ein Ticket bisher erst, wenn jemand die Nummer eingibt. Dieses Feature erfasst neue und wieder geöffnete Zammad-Tickets automatisch und legt das Datenmodell für spätere Klassifikation und Automatisierung an. Zammad bleibt führend für Tickets, Status, Zuständigkeit, Kommunikation und Anhänge; CSA ist führend für eigene Analysen, Klassifikationen und Entscheidungen.

## User Stories
- Als **Mitarbeiter im Kundenservice** will ich, dass neue und wieder geöffnete Tickets ohne Eingabe einer Nummer in CSA erscheinen, damit ich nichts suchen muss.
- Als **Mitarbeiter** will ich, dass Status und Zuständigkeit in CSA zu Zammad passen, damit ich mich auf die Liste verlassen kann.
- Als **Mitarbeiter** will ich sehen, ob zur aktuellen Kundennachricht schon ein Vorschlag vorliegt, damit niemand doppelt arbeitet und keine neue Nachricht übersehen wird.
- Als **Admin** will ich, dass CSA Ausfälle und verpasste Webhooks von selbst ausgleicht und ich einen veralteten Stand erkenne, damit die Liste vertrauenswürdig bleibt.
- Als **Entwickler** will ich ein schlankes Datenmodell, das spätere Klassifikation und Automatisierung ohne Umbau aufnehmen kann.

## Out of Scope
- LLM-Klassifikation, Wissensauswahl nach Klassifikation, automatische Antwortentwürfe oder Versand (Folgeprojekte, PROJ-20).
- Tabellen für Klassifikation und Automatisierung: nur dokumentiert, siehe „Spätere Erweiterungen“.
- Ermittlung des Kundenkanals: Die Spalte `channel` bleibt zunächst leer.
- Übernahme bereits geschlossener Tickets aus der Zeit vor dem Start.
- Schreiben nach Zammad: Die Synchronisierung liest nur.
- Benutzerverwaltung (PROJ-15).

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Eingang
- [ ] Angenommen in Zammad entsteht ein neues Ticket, wenn der nächste Abgleich läuft (spätestens nach etwa einer Minute), dann ist es in CSA erfasst, auch ohne Webhook.
- [ ] Angenommen der Webhook ist eingerichtet, wenn Zammad ihn sendet, dann antwortet CSA sofort, und die Arbeit läuft in einem Job.
- [ ] Angenommen ein Ticket wird durch eine neue Kundennachricht wieder geöffnet, wenn es synchronisiert wird, dann ist es wieder offen und `reopened_at` gesetzt.
- [ ] Angenommen Webhooks kommen doppelt, verspätet oder in falscher Reihenfolge, wenn sie verarbeitet werden, dann steht in CSA am Ende der aktuelle Zammad-Stand.

### Absicherung des Webhooks
- [ ] Angenommen ein gültiger Header `X-Hub-Signature: sha1=<hex>` über den unveränderten Rohinhalt, wenn der Webhook eingeht, dann wird er angenommen.
- [ ] Angenommen der Header fehlt, trägt eine andere oder keine Kennung, hat einen falschen Wert, oder es ist kein Secret konfiguriert, wenn der Webhook eingeht, dann lehnt CSA mit 403 ab, ohne Job und ohne Protokolleintrag.
- [ ] Angenommen dieselbe `X-Zammad-Delivery` kommt erneut, wenn der Webhook eingeht, dann wird er quittiert, ohne einen zweiten Job zu starten.

### Vollständigkeit
- [ ] Angenommen ein Webhook ging verloren, wenn der minütliche Abgleich läuft, dann wird das Ticket nachgeholt.
- [ ] Angenommen CSA war Tage nicht erreichbar, wenn der Abgleich wieder läuft, dann holt er die Änderungen in Zeitfenstern vollständig nach; zu volle Fenster werden geteilt.
- [ ] Angenommen ein offenes Ticket fehlt lokal, auch wenn es lange zurückliegt, wenn der stündliche Vollabgleich läuft, dann wird es erfasst.
- [ ] Angenommen ein Lauf bleibt unvollständig (Seite fehlt, 429, 5xx, Zeitüberschreitung, gesperrtes Ticket, Status nicht gespeichert), wenn er endet, dann rückt der Fortschrittsmarker nicht vor.
- [ ] Angenommen ein Ticket liefert 403 oder 404 und der Status wurde gespeichert, wenn der Lauf endet, dann blockiert dieses Ticket den Marker nicht.

### Fehlerbehandlung ohne Datenverlust
- [ ] Angenommen Zammad liefert 403 oder 404, wenn das Ticket synchronisiert wird, dann bleibt die Zeile samt CSA-Daten erhalten, der Status ist `access_denied` bzw. `not_found`.
- [ ] Angenommen ein solches Ticket ist wieder lesbar, wenn der stündliche Abgleich läuft, dann ist der Status wieder `synced`.
- [ ] Angenommen vorübergehende Fehler halten über alle Versuche an, wenn der Job aufgibt, dann ist der Status `sync_failed`, und der nächste Abgleich holt das Ticket nach.
- [ ] Angenommen die Aufbewahrungsfrist ist nicht abgelaufen, wenn `tickets:purge` läuft, dann bleibt die Zeile. Kein anderer Weg löscht Zeilen.

### Bearbeitungsstatus
- [ ] Angenommen eine neue Kundennachricht ist synchronisiert, wenn zu ihr noch keine Analyse existiert, dann ist der Status `not_analyzed`.
- [ ] Angenommen eine Analyse zu einer älteren Nachricht endet, nachdem eine neuere synchronisiert wurde, wenn sie gespeichert wird, dann bleibt der Status unverändert; das Ergebnis der Analyse wird trotzdem gespeichert und angezeigt.
- [ ] Angenommen die Analyse zur neuesten Nachricht ist fertig, bevor die Synchronisierung die Nachricht sieht, wenn die Synchronisierung läuft, dann ist der Status `analyzed`.
- [ ] Angenommen eine Wiederholung schlägt fehl, wenn zur selben Nachricht schon eine erfolgreiche Analyse existiert, dann bleibt der Status `analyzed`.
- [ ] Angenommen ein Testlauf oder eine Frage ohne Ticket, wenn sie gespeichert wird, dann ändert sich der Status nicht.
- [ ] Angenommen die Statusaktualisierung schlägt fehl, wenn eine manuelle Analyse läuft, dann läuft die Analyse normal durch.

### Bestehende Funktionen
- [ ] Angenommen die Tabellen sind leer, wenn jemand ein Ticket per Nummer lädt, analysiert und antwortet, dann funktioniert alles wie bisher.

## Edge Cases
- **Ticket ohne Kundennachricht** (von CSA für den DHL-Versand angelegt): `last_customer_article_id` ist `NULL`, Analysen mit `NULL` zählen als passend.
- **Nachricht in Zammad gelöscht:** Beim nächtlichen `--all` wird die vorherige Nachricht wieder die aktuelle; der Status richtet sich dann nach den Analysen genau zu ihr.
- **Antwort aus CSA oder durch Mitarbeiter:** keine Kundennachricht, der Status bleibt.
- **Agent-Mail vom Kunden:** Zählt wie in der Ticketansicht als Kundennachricht (gleiche Aufbereitung auf beiden Wegen).
- **Nicht zugewiesen:** Zammad `owner_id = 1` wird `NULL`.
- **Mehr offene Tickets als das Suchlimit:** Der Vollabgleich bricht ab und wird als fehlgeschlagen angezeigt, statt Unvollständiges als vollständig zu werten.
- **Doppelte Namen:** In Zammad gibt es denselben Namen mehrfach (Stand 2026-10-31: „Etienne Renaud“ fünfmal). Die Zuordnung läuft ausschließlich über die ID.

## Technical Requirements
- Der Webhook antwortet ohne Zammad-Aufruf; Payload und Kundeninhalte werden nicht protokolliert.
- Betreff verschlüsselt; Kundenname, E-Mail und Nachrichtentexte werden nicht gespeichert.
- Neue Variablen in `.env.example`: `ZAMMAD_WEBHOOK_SECRET`, `ZAMMAD_SYNC_EXCLUDED_GROUPS`.

## Open Questions
- [ ] Kann Zammad CSA per HTTP erreichen? Bis das geklärt ist, tragen die Abgleiche die Liste allein.
- [ ] Headerformat `sha1=` an einer echten Zustellung bestätigen (die gelesene Doku nennt nur „SHA-1-Signatur“).
- [ ] Zammad-Version: Das technische Token darf `GET /version` nicht lesen (403). Felder und Suche sind gegen das echte System geprüft (2026-10-31).

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| PRD-Non-Goal „eigene gemeinsame Inbox“ präzisiert: Liste ja, Zammad bleibt führend | Vorgabe des Auftraggebers; keine Ablösung von Zammad | 2026-10-31 |
| PROJ-6 „nichts dauerhaft speichern“ gilt nur noch für Inhalte | Metadaten sind für die Liste nötig | 2026-10-31 |
| Erstübernahme nur offene Tickets | Entscheidung des Auftraggebers; Geschlossene erscheinen ab Start | 2026-10-31 |
| Kein Löschen bei 403/404 | Vorübergehende Rechteprobleme dürfen keine CSA-Daten kosten | 2026-10-31 |
| Keine Klassifikationstabelle jetzt | Wird später rein ergänzt, kein technischer Vorteil auf Vorrat | 2026-10-31 |

### Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Benachrichtigen, dann lesen | Doppelte, verspätete, vertauschte Webhooks werden unkritisch; ein Codeweg für alle Auslöser | 2026-10-31 |
| Abgleiche führen den Job per `dispatchSync` aus | Nur so steht vor dem Vorrücken des Markers fest, dass jedes Ticket verarbeitet ist; kein Worker nötig | 2026-10-31 |
| Marker in `zammad_sync_runs`, nicht im Cache | Übersteht Neustarts und Cache-Leerung, dient zugleich als Protokoll | 2026-10-31 |
| Verspätete Analysen über Identität (`based_on_article_id = last_customer_article_id`) | Bleibt auch richtig, wenn eine Nachricht gelöscht wird; beide Reihenfolgen führen zum selben Ergebnis | 2026-10-31 |
| Webhook-Route in `routes/api.php`, Signatur statt Sanctum | Zammad kann keine Sanctum-Tokens; Signatur über den Rohinhalt ist der Weg von Zammad | 2026-10-31 |
| Modell `TrackedTicket` | `App\Zammad\Ticket` ist vergeben | 2026-10-31 |

---

## Tech Design (Solution Architect)

### Ablauf
```
Zammad-Trigger → POST /api/webhooks/zammad → Signatur prüfen, Zustellung vermerken ┐ (über die Queue)
zammad:sync-recent      jede Minute: seit dem Marker geänderte Tickets             ├→ SyncZammadTicket → tracked_tickets
zammad:reconcile-open   stündlich: alle offenen IDs mit dem Bestand vergleichen    │ (Befehle führen den Job direkt aus)
zammad:import-tickets   einmalig: Erstübernahme, nur offene                        ┘
```

### Daten
- **`tracked_tickets`:** eine Zeile je Zammad-Ticket, ohne Inhalte. Getrennt: Zammad-Stand (Status, Besitzer, Gruppe, Zeiten, letzte Kundennachricht), Synchronisierung (`sync_status`), Kanal, aktuelle Kategorie, Bearbeitungsstatus.
- **`zammad_sync_runs`:** Protokoll je Lauf, zugleich Fortschrittsmarker (`covered_until`).
- **`zammad_webhook_deliveries`:** Protokoll je Zustellung, ohne Payload.
- **`analyses.based_on_article_id`:** die Nachricht, auf der eine Analyse beruht.

### Bearbeitungsstatus
Jetzt gebaut: `not_analyzed`, `analyzed`, `failed`. Später, ohne Migration (String-Spalte): `analyzing`, `draft_ready`, `approval_required`, `internal_review`, `auto_done`.

### Ressourcen
1. Liste (Suche ohne `expand`, 100 je Seite) nur `id` und `updated_at` auswerten.
2. Ticketdetails nur, wenn neu, `updated_at` abweichend oder Problem-Status.
3. Artikel nur, wenn neu oder `last_contact_customer_at` abweichend. Nur das nächtliche `--all` lädt ohne Vorbedingung.

### Parallele Läufe
`withoutOverlapping` je Befehl; Vollabgleich und Import teilen einen Lock; `sync-recent` läuft weiter; ein Lock je Ticket koordiniert alle Wege, und innerhalb des Locks wird `updated_at` erneut geprüft.

### Endlosschleifen
Die Synchronisierung liest nur. Für die spätere Automatisierung gilt: Nur neue Kundennachrichten lösen etwas aus; eigene Änderungen werden am technischen Nutzer erkannt.

### Spätere Erweiterungen (dokumentiert, nicht gebaut)
Beide kommen als neue Tabellen und ändern den Bestand nicht.
- **`ticket_classifications`:** `tracked_ticket_id`, `zammad_article_id`, `category`, `subcategory`, `confidence`, `is_primary`, `source`, `model`, `prompt_version`. Mehrere Zeilen je Nachricht (mehrere Anliegen, Historie). `tracked_tickets.category` bleibt der aktuelle Schwerpunkt zum Filtern.
- **`automation_decisions`:** je Nachricht Modus, Regelversion, Gründe, Entscheider. Da Regeln und Gründe gespeichert werden, hängt die Freigabe nicht allein am Confidence-Score.
- **Kanal:** `channel` wird später befüllt, eine Herkunftsangabe kommt als Spalte dazu.

### In Zammad einzurichten (sobald die Erreichbarkeit geklärt ist)
1. **Webhook** (Verwaltung > Webhooks): Endpunkt `https://<csa-host>/api/webhooks/zammad`, Signatur-Token gleich `ZAMMAD_WEBHOOK_SECRET`, eigene Payload `{"ticket_id": "#{ticket.id}"}`.
2. **Trigger** (Verwaltung > Trigger): Aktivierung bei Aktion, Bedingung „Ticket erstellt oder aktualisiert“, Ausführung: diesen Webhook aufrufen.
3. **Webserver:** Für genau diesen Pfad eine Ausnahme vom gemeinsamen Passwort, die Absicherung übernimmt die Signatur.

### Geprüft gegen das echte Zammad (2026-10-31)
Suche liefert mit `with_total_count` die Form `{records, total_count}`; `updated_at:[von TO bis]` und `state.name:(…)` funktionieren; alle benötigten Felder vorhanden; Status: `new`, `open`, `pending reminder`, `pending close`, `closed`, `merged`; bei `expand=true` ist `owner` ein Text, kein Name-Objekt. 58 offene Tickets, Suchlimit also weit entfernt.

## Implementation Notes (2026-10-31)
Gebaut: drei Migrationen, Modelle `TrackedTicket`, `ZammadSyncRun`, `ZammadWebhookDelivery`, `TicketSynchronizer`, `TicketReconciler`, Job `SyncZammadTicket`, Webhook (`routes/api.php`, `ZammadWebhookRequest`, `ZammadWebhookController`), Befehle `zammad:sync-recent`, `zammad:reconcile-open [--all]`, `zammad:import-tickets [--dry-run]`, `tickets:purge`, Dienste `queue` und `scheduler` in `compose.yaml`, `TicketProcessingState`, Spalte `analyses.based_on_article_id`. Tests: `tests/Feature/PROJ-66-TicketIntakeTest.php`, ergänzt in `PROJ-9-CaseAnalysisTest.php`.

**Abweichungen vom Plan, jeweils aus der Umsetzung begründet:**
- Der Job ist `ShouldBeUniqueUntilProcessing` statt `ShouldBeUnique`: Sonst ginge eine Änderung verloren, die während eines laufenden Jobs eintrifft.
- Artikel werden auch geladen, wenn sich `last_contact_at` ändert, nicht nur `last_contact_customer_at`: Eine Agent-Mail vom Kunden zählt als Kundennachricht, ändert aber nur `last_contact_at`.
- Der Mitarbeitername kommt aus `users/{id}` (einen Tag im Cache), weil `owner` bei `expand=true` kein Name ist. Er dient nur der Anzeige.
- Die Compose-Dienste nutzen `command` mit `restart: unless-stopped`: Supervisor würde einen Worker nach dem regulären Ende durch `--max-time` nicht neu starten.
- `based_on_article_id` setzt `CaseAnalyzer`, nicht der Controller: Dort liegt das vor dem KI-Aufruf geladene Ticket vor.

**Offen:** `.env.example` ist für den Assistenten gesperrt; `ZAMMAD_WEBHOOK_SECRET` und `ZAMMAD_SYNC_EXCLUDED_GROUPS` müssen dort noch von Hand ergänzt werden.

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
