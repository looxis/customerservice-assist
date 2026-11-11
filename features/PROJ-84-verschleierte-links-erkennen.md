# PROJ-84: Verschleierte Links erkennen (Phishing-Warnzeichen)

## Status: In Review
**Created:** 2026-11-11
**Last Updated:** 2026-11-11

## Dependencies
- Requires: PROJ-6 (Mail-Bereinigung, Links werden entfernt), PROJ-78 und PROJ-80 (Klassifikation mit Prompt `labeling`)
- Berührt: PROJ-82 (WISMO-Automatik antwortet nur auf als `wismo` eingeordnete Tickets; ein als `spam` erkanntes Ticket bekommt keine Antwort)

## Anlass
Ticket 2140356: Die Mail fragt harmlos nach 9 Stück eines Produkts, der Linktext lautet „looxis.com“ (nicht die Domain des Shops), das Linkziel ist ein verschleierter Tracking-Link. Die App entfernte den Link korrekt, die KI sah nur „[Link entfernt]“ und ordnete die Mail als `angebotsanfrage` (Konfidenz 0,97) ein. Der Widerspruch zwischen Linktext und Linkziel ist ein Phishing-Warnzeichen, ging aber vor dem Entfernen verloren.

## Entscheidungen (2026-11-11)
| Entscheidung | Begründung |
|---|---|
| Erkennung im Code (`MessageBody`), nicht im LLM | Die KI sieht nie ein Linkziel; Host-Vergleich ist deterministisch prüfbar |
| Der Vermerk steht als Text im bereinigten Mailtext: „[Link entfernt – WARNUNG: angezeigt wird „looxis.com“, das Linkziel ist eine andere Adresse]“ | Erreicht so überall, wo der Mailtext verwendet wird: Ticketseite, Klassifikation, Fallanalyse, Übersetzung |
| Das Linkziel selbst wird weder angezeigt noch an die KI gegeben | Bleibt ein Risiko, das nicht angeklickt werden soll |
| Prompt `labeling-8`: Warnung ist ein deutliches Zeichen für `spam`, außer die Nachricht nennt einen Vorgang, den nur ein echter Kunde kennt; ein gewöhnliches „[Link entfernt]“ ist kein Hinweis | Regel für die KI, keine harte Zuordnung im Code |
| Nur der Prompt der Klassifikation wird angepasst, nicht `analysis.md` | Die Fallanalyse sieht den Vermerk im Text; Spam-Einstufung ist Sache der Klassifikation |
| Bereits klassifizierte Tickets werden nicht neu klassifiziert | Entscheidung Etienne; Ticket 2140356 korrigiert er selbst |

## Acceptance Criteria
- [x] Angenommen ein Link hat als Text eine Adresse („looxis.com“, „https://www.looxis.de/x“) und das Ziel liegt auf einem anderen Host, wenn der Mailtext bereinigt wird, dann steht dort der Warnvermerk mit dem angezeigten Host statt „[Link entfernt]“.
- [x] Angenommen Linktext und Ziel haben denselben Host (auch mit/ohne `www.`) oder das Ziel ist eine Subdomain des angezeigten Hosts, dann bleibt es beim gewöhnlichen „[Link entfernt]“.
- [x] Angenommen der angezeigte Host endet auf den Ziel-Host („paypal.com.evil.de“ → „evil.de“), dann gilt das als Widerspruch.
- [x] Angenommen der Linktext ist keine Adresse („Hier klicken“) oder das Ziel ist `mailto:`/`tel:`, dann erscheint keine Warnung.
- [x] Angenommen die Mail enthält die Warnung, wenn die Klassifikation läuft, dann steht der Vermerk im Text an die KI und der Prompt `labeling-8` weist sie auf Spam hin.
- [x] Angenommen ein Link auf einem zugelassenen Host zeigt wie angezeigt, dann ändert sich nichts.

## Out of Scope
- Erneutes Klassifizieren vorhandener Tickets
- Eigene Warnanzeige (Farbe, Symbol) auf der Ticketseite: der Vermerk steht im Mailtext
- Prüfung von Textadressen ohne Link und von Absenderadressen
- Anpassung von `analysis.md` und des WISMO-Prompts

## Implementation Notes
- `app/Zammad/MessageBody.php`: `withoutLinks()` ruft `shownAddressHost()` und `pointsElsewhere()` auf; Konstante `LINK_MISMATCH`.
- `resources/prompts/labeling.md`: Version `labeling-7` → `labeling-8`, neue Regel zu Links.
- Tests: `tests/Feature/PROJ-6-ZammadTicketTest.php` (Block „PROJ-84“), Prompt-Version in PROJ-78/PROJ-80-Tests angepasst.
- Nicht automatisch neu klassifiziert: neue Tickets nutzen `labeling-8`, vorhandene Vorschläge behalten ihre Version.
