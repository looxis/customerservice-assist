# PROJ-62: Große Anhänge beim Senden zuverlässig übergeben

- **Status:** Approved (2026-11-12)
- **Abhängigkeiten:** PROJ-19, PROJ-61

## Problem
Antworten mit Anhängen über etwa 800 KB (Anlass: Ticket 2140026, 2026-10-26; Ticket 2140369 mit 2,1 und 2 MB) endeten mit „Zammad hat nicht rechtzeitig geantwortet“ (Log: Status 408 nach ca. 40 s).

## Ursache
Guzzle, der Unterbau des Laravel-HTTP-Clients, sendet bei Bodies über 1 MB automatisch `Expect: 100-continue`. Ein Anhang von etwa 800 KB ist als Base64 rund 1 MB groß. Auf dem Weg zu Zammad bleibt die Verbindung dann hängen, bis nach ca. 40 s der Status 408 kommt. Netz, Proxy und Zammad selbst sind in Ordnung.

## Diagnose (2026-11-12, Testticket 2140377, interne Notizen)
| Weg | Ergebnis |
|---|---|
| curl, Base64 im JSON, 2 MB | 201 in 1,8 s |
| curl, Vorab-Upload `upload_caches` + `form_id` | 200 in 1,2 s |
| curl aus dem Sail-Container, `PUT tickets/{id}` | 200 in 1,8 s |
| Laravel-HTTP-Client, Standard | 408 nach 39 s |
| Laravel-HTTP-Client mit `expect => false` | 200 in 1,4 s |

## Umsetzung
- `ZammadReplySender::call()` setzt für alle Aufrufe die Guzzle-Option `expect => false`.
- Der zunächst geplante Umbau auf Vorab-Upload (`upload_caches`, undokumentierte Zammad-Schnittstelle) entfällt, der Base64-Weg bleibt.
- Die geplante Checkbox „Dateigröße verkleinern“ wird nicht gebaut; bei Bedarf als eigenes Feature.

## QA Test Results (2026-11-12)
- **Echtversand:** Antwort mit zwei Dateien (2,1 und 2 MB) über CSA versendet, kommt an (vom Auftraggeber bestätigt).
- **Automatisch:** Neuer Test in `tests/Feature/PROJ-19-SendReplyTest.php` prüft, dass `expect => false` gesetzt ist. Der Lauf der Zammad-, Antwort-, DHL-, ToDo-, Schließen-, Zuweisen- und Notiz-Tests ergab 470 von 471 bestanden.
- **Fehlschlag außerhalb des Features:** `PROJ-36-AnalysisIntegrationTest`, „Eisberg case (PLAYBOOK-018/019)“ schlägt auch mit dem Stand vor der Änderung fehl (geprüft); gehört nicht zu PROJ-62.
- **Sicherheit:** Keine neue Eingabe, keine neue Schnittstelle, Token weiter nur im Header. Keine Funde.
- **Bugs:** keine (Critical/High: 0).
- **Production-ready:** JA
