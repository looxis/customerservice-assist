# PROJ-28: Übersetzung von Nachrichten

## Status: Planned
**Created:** 2026-10-08
**Last Updated:** 2026-10-08

## Dependencies
- Requires: PROJ-6 (Zammad-Ticket laden) – bereinigte Nachrichten des Verlaufs
- Requires: PROJ-9 (Fallanalyse per LLM) – Anbindung an das Sprachmodell, Ersetzung von Kontaktdaten, Begrenzung der KI-Aufrufe
- Requires: PROJ-10 (Ergebnisansicht) – Antwortentwurf, an dem gegengelesen wird
- Requires: PROJ-11 (Analyse-Protokoll und Verlauf) – dauerhafte, verschlüsselte Speicherung, Aufbewahrungsfrist, Löschen je Ticket
- Requires: PROJ-5 (Nutzerauswahl) – Übersetzen nur mit gewähltem Namen

## Ausgangslage
Über Amazon und looxis.fr kommen Nachrichten auf Italienisch, Französisch, Niederländisch, Englisch und in weiteren Sprachen. Aushilfen und Vertretungen können sie oft nicht lesen. Die Analyse und der Antwortentwurf funktionieren bereits in der Sprache des Kunden – aber wer den Verlauf nicht versteht, kann den Vorschlag nicht prüfen und weiß nicht, was er verschickt. Die Übersetzung soll deshalb in der App stehen, einmal erstellt und gespeichert, damit nicht jeder Aufruf KI-Kosten verursacht.

## User Stories
- Als Aushilfe möchte ich fremdsprachige Nachrichten eines Tickets mit einem Klick auf Deutsch lesen, damit ich den Fall verstehe, ohne ein Übersetzungsprogramm zu bemühen.
- Als Mitarbeiter möchte ich das Original jederzeit sehen können, damit ich bei Zweifeln an der Übersetzung nachlesen kann.
- Als Aushilfe möchte ich den Antwortentwurf in der Kundensprache auf Deutsch gegenlesen, damit ich weiß, was ich verschicke.
- Als Verantwortlicher möchte ich, dass jede Nachricht nur einmal übersetzt wird und Kontaktdaten dabei nicht an den Anbieter gehen.
- Als Kollegin möchte ich die Übersetzungen sehen, die schon jemand erstellt hat, ohne selbst noch einmal zu übersetzen.

## Out of Scope
- Automatisches Übersetzen beim Öffnen oder beim Ticket-Eingang – bewusst per Klick; ggf. mit PROJ-20
- Antwort auf Deutsch schreiben und in die Kundensprache übersetzen lassen (der Entwurf entsteht bereits in der Kundensprache; Bearbeiten bleibt im Original)
- Übersetzung von Anhängen, Bildern oder eingeklappten Zitaten und Signaturen
- Übersetzung der Oberfläche oder der Knowledge
- Zweiter Übersetzungsanbieter oder Vergleich – PROJ-17
- Die Analyse verwendet weiterhin die Originalnachrichten, nicht die Übersetzung

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Erkennen und Auslösen
- [ ] Angenommen ein Ticket enthält Nachrichten, die nicht auf Deutsch sind und noch keine Übersetzung haben, wenn der Verlauf angezeigt wird, dann steht über dem Verlauf „N Nachrichten sind nicht auf Deutsch“ mit dem Knopf „Übersetzen“; die Erkennung geschieht ohne KI-Aufruf
- [ ] Angenommen alle Nachrichten sind auf Deutsch oder bereits übersetzt, wenn der Verlauf angezeigt wird, dann gibt es keinen Hinweis und keinen Knopf
- [ ] Angenommen der Mitarbeiter klickt „Übersetzen“, wenn die Übersetzung läuft, dann zeigt das Lade-Overlay „Nachrichten werden übersetzt …“, und ein zweiter Klick löst keinen zweiten Aufruf aus
- [ ] Angenommen der Mitarbeiter klickt „Übersetzen“, wenn der Aufruf fertig ist, dann sind alle noch nicht übersetzten fremdsprachigen Nachrichten des Tickets übersetzt und gespeichert – Kundennachrichten, unsere Antworten und interne Notizen
- [ ] Angenommen eine Nachricht wurde als fremdsprachig eingestuft, ist aber tatsächlich deutsch, wenn übersetzt wird, dann wird sie als „bereits deutsch“ vermerkt, unverändert angezeigt und nicht erneut vorgeschlagen
- [ ] Angenommen kein Name ist gewählt, wenn „Übersetzen“ geklickt wird, dann wird nicht übersetzt und der Hinweis aus PROJ-5 erscheint
- [ ] Angenommen nach einer Übersetzung kommt eine neue fremdsprachige Nachricht, wenn das Ticket geöffnet wird, dann erscheint der Hinweis nur für die neue Nachricht

### Anzeige im Verlauf
- [ ] Angenommen eine Nachricht hat eine Übersetzung, wenn sie angezeigt wird, dann steht der deutsche Text mit dem Kennzeichen „Übersetzt aus [Sprache] · KI-Übersetzung“ und darunter „Original anzeigen“ zum Aufklappen
- [ ] Angenommen eine Nachricht hat eine Übersetzung, wenn der Mitarbeiter „Original anzeigen“ öffnet, dann sieht er den unveränderten Originaltext wie bisher
- [ ] Angenommen übersetzte Nachrichten gibt es, wenn der Mitarbeiter am Verlauf „Original zuerst“ wählt, dann zeigen alle Nachrichten das Original und die Übersetzung ist aufklappbar; die Wahl gilt für diesen Browser, bis er sie ändert
- [ ] Angenommen ein Ticket wird später oder von einer Kollegin geöffnet, wenn es Übersetzungen gibt, dann erscheinen sie sofort ohne neuen KI-Aufruf
- [ ] Angenommen eine Übersetzung wird angezeigt, wenn der Mitarbeiter sie liest, dann sind Kontaktdaten (E-Mail, Telefon, Anschrift) wie im Original lesbar, obwohl sie nicht an den Anbieter gegangen sind

### Antwortentwurf gegenlesen
- [ ] Angenommen der Antwortentwurf ist nicht auf Deutsch, wenn das Ergebnis angezeigt wird, dann gibt es am Entwurf den Knopf „Auf Deutsch gegenlesen“
- [ ] Angenommen der Mitarbeiter klickt „Auf Deutsch gegenlesen“, wenn die Übersetzung fertig ist, dann erscheint unter dem Entwurf der deutsche Text, gekennzeichnet als „Rückübersetzung zur Kontrolle – verschickt wird das Original“
- [ ] Angenommen der Entwurf wurde nach dem Gegenlesen geändert, wenn die Rückübersetzung angezeigt wird, dann steht dort „Entwurf seither geändert – erneut gegenlesen“
- [ ] Angenommen derselbe Entwurfstext wurde schon gegengelesen, wenn erneut geklickt wird, dann erscheint die gespeicherte Rückübersetzung ohne neuen Aufruf
- [ ] Angenommen der Antwortentwurf ist auf Deutsch, wenn das Ergebnis angezeigt wird, dann gibt es den Knopf nicht

### Speicherung und Schutz
- [ ] Angenommen eine Nachricht wird übersetzt, wenn der Text an das Sprachmodell geht, dann sind Kontaktdaten wie bei der Analyse durch Platzhalter ersetzt und werden erst in der App wieder eingesetzt
- [ ] Angenommen eine Übersetzung ist gespeichert, wenn sich der Text der Nachricht in Zammad ändert, dann gilt die Übersetzung als veraltet und die Nachricht wird erneut zum Übersetzen vorgeschlagen
- [ ] Angenommen Übersetzungen sind gespeichert, wenn die Aufbewahrungsfrist (12 Monate) abläuft oder ein Admin alle Analysen des Tickets löscht (PROJ-11), dann werden auch die Übersetzungen gelöscht
- [ ] Angenommen eine Übersetzung wurde erstellt, wenn sie betrachtet wird, dann sind Zeitpunkt, Name, Modell und Prompt-Version bekannt

### Fehler
- [ ] Angenommen das Sprachmodell antwortet nicht oder liefert kein verwertbares Ergebnis, wenn übersetzt wird, dann erscheint „Die Übersetzung ist gerade nicht möglich. Bitte erneut versuchen.“; bereits gespeicherte Übersetzungen bleiben
- [ ] Angenommen einzelne Nachrichten konnten nicht übersetzt werden, wenn das Ergebnis vorliegt, dann sind die übrigen gespeichert und für die fehlenden bleibt der Hinweis mit „Übersetzen“
- [ ] Angenommen zu viele KI-Aufrufe in kurzer Zeit, wenn übersetzt wird, dann greift dieselbe Begrenzung wie bei der Analyse mit verständlicher Meldung

## Edge Cases
- **Gemischte Sprachen in einer Nachricht** (deutscher Gruß, italienischer Text): gilt als fremdsprachig; die Übersetzung gibt den ganzen Text auf Deutsch wieder.
- **Sehr kurze Nachrichten** („Ok, grazie“): werden unter 20 Zeichen nicht als fremdsprachig erkannt; sie werden mitübersetzt, wenn das Ticket ohnehin übersetzt wird.
- **Sehr langer Verlauf:** Übersetzung in mehreren Teilen innerhalb eines Klicks; die Lade-Anzeige bleibt, bis alles fertig ist oder ein Teil scheitert.
- **Amazon-Hinweise und automatische Nachrichten:** nur der sichtbare, bereinigte Text wird übersetzt; ausgeblendete Textbausteine nicht.
- **Zwei Mitarbeiter klicken gleichzeitig „Übersetzen“:** jede Nachricht hat am Ende genau eine gespeicherte Übersetzung.
- **Testmodus (PROJ-32):** Übersetzungen gelten je Nachricht und sind im zurückgespulten Ticket genauso sichtbar.
- **Sprache nicht bestimmbar** (z. B. nur eine Bestellnummer): keine Übersetzung, kein Hinweis.
- **Übersetzung erkennbar falsch:** Original bleibt einen Klick entfernt; „Neu übersetzen“ je Nachricht für Admins.

## Technical Requirements (optional)
- Übersetzung typisch unter 15 Sekunden für ein Ticket mit bis zu zehn Nachrichten.
- Günstiges Modell (wie die Zusammenfassung), austauschbar über die Konfiguration; Prompt versioniert im Repository.
- Übersetzungen verschlüsselt gespeichert; keine Inhalte im Log.
- Voraussetzung wie bei PROJ-9: Auftragsverarbeitungsvertrag mit dem Anbieter (OpenAI vorhanden).

## Open Questions
- [ ] Reicht die Erkennung ohne KI (Wortlisten für Deutsch und die häufigsten Fremdsprachen) im Alltag aus, oder gibt es zu viele falsche Hinweise? Nach den ersten Wochen prüfen.
- [ ] Soll die Übersetzung später auch der Analyse mitgegeben werden (z. B. wenn ein günstigeres Modell die Fremdsprache schlechter versteht)? Derzeit nein.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Übersetzen per Klick für das ganze Ticket, nicht automatisch | Kosten und Wartezeit nur, wenn jemand die Übersetzung braucht; ein Klick statt vieler | 2026-10-08 |
| Deutsch zuerst, Original aufklappbar; Schalter „Original zuerst“ je Browser | Aushilfen lesen deutsch; wer die Sprache kann, stellt um | 2026-10-08 |
| Alle fremdsprachigen Nachrichten (Kunde, eigene Antworten, Notizen) | Auch frühere eigene Antworten müssen verstanden werden | 2026-10-08 |
| Antwortentwurf auf Deutsch gegenlesen (Rückübersetzung), Bearbeiten bleibt im Original | Aushilfe weiß, was sie verschickt; keine zweite Fassung, die auseinanderläuft | 2026-10-08 |
| Jede Übersetzung einmal erstellt und dauerhaft gespeichert, für alle sichtbar | Wunsch des Product Owners: keine KI-Kosten bei jedem Aufruf | 2026-10-08 |
| Erkennung der Sprache ohne KI (von mir entschieden, bitte prüfen) | Kein Aufruf nur zum Erkennen; falsche Hinweise sind harmlos | 2026-10-08 |
| Kontaktdaten werden wie bei der Analyse ersetzt und in der App wieder eingesetzt (von mir entschieden, bitte prüfen) | Gleicher Datenschutz wie PROJ-9 | 2026-10-08 |
| Aufbewahrung und Löschen wie Analyse-Inhalte (12 Monate, Löschen je Ticket) (von mir entschieden, bitte prüfen) | Übersetzungen enthalten dieselben Kundeninhalte | 2026-10-08 |
| Kennzeichen „KI-Übersetzung“ an jeder Übersetzung (von mir entschieden, bitte prüfen) | Übersetzungen können Fehler enthalten; das Original entscheidet | 2026-10-08 |
| Die Analyse nutzt weiter das Original (von mir entschieden, bitte prüfen) | Keine Übersetzungsfehler in der fachlichen Bewertung | 2026-10-08 |

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
