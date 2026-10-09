# PROJ-28: Übersetzung von Nachrichten

## Status: In Progress
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
| Eigene Tabelle für Übersetzungen je Zammad-Nachricht, Text verschlüsselt, mit Fingerabdruck des Originaltexts | Einmal übersetzt, für alle und in jedem Stand des Tickets (auch Testmodus) sichtbar; geänderte Nachricht wird am Fingerabdruck erkannt | 2026-10-08 |
| Rückübersetzung des Antwortentwurfs wird an der Analyse gespeichert, mit Fingerabdruck des Entwurfstexts | Gehört zur Analyse, wird mit ihr bereinigt; „seither geändert“ am Fingerabdruck erkennbar | 2026-10-08 |
| Spracherkennung ohne KI über häufige Wörter (Deutsch gegen die übrigen Sprachen), nur für den Hinweis | Kostet nichts; die endgültige Sprache nennt das Sprachmodell bei der Übersetzung | 2026-10-08 |
| Ein strukturierter KI-Aufruf für mehrere Nachrichten, bei langen Verläufen in Teilen; günstiges Modell wie bei der Zusammenfassung, eigener versionierter Prompt | Wenige Aufrufe, klare Zuordnung je Nachricht, austauschbar über die Konfiguration | 2026-10-08 |
| Übersetzt wird der bereinigte Text (wie er an die Analyse ginge), Kontaktdaten über dieselbe Ersetzung wie PROJ-9 | Gleicher Datenschutz, keine Zitate/Signaturen/Textbausteine im Aufruf | 2026-10-08 |
| „Übersetzen“ als normales Formular mit Lade-Overlay; „Auf Deutsch gegenlesen“ als Hintergrundanfrage | Verlauf wird nach dem Übersetzen ohnehin neu aufgebaut; Gegenlesen soll den Entwurf nicht verlieren | 2026-10-08 |
| „Original zuerst“ merkt sich der Browser | Persönliche Vorliebe, keine Serverdaten nötig | 2026-10-08 |
| Bereinigung und Löschen über die Wege aus PROJ-11 | Eine Stelle für Fristen und Löschbegehren | 2026-10-08 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

### Überblick
PROJ-28 ergänzt den Verlauf (PROJ-6) um gespeicherte Übersetzungen und den Antwortentwurf (PROJ-10) um eine Rückübersetzung. Es nutzt die vorhandene Anbindung an das Sprachmodell samt Ersetzung der Kontaktdaten (PROJ-9) und die Datenbank mit Fristen und Löschen (PROJ-11). Keine neuen Pakete.

### A) Bausteine
```
Ticketseite
+-- Verlauf (PROJ-6)
|   +-- Hinweis „N Nachrichten sind nicht auf Deutsch“ + [Übersetzen]   (nur wenn nötig)
|   +-- Schalter „Deutsch zuerst / Original zuerst“                     (nur wenn Übersetzungen da sind)
|   +-- Nachricht mit Übersetzung
|       +-- Kennzeichen „Übersetzt aus Italienisch · KI-Übersetzung“
|       +-- deutscher Text
|       +-- „Original anzeigen“ (aufklappbar)   bzw. umgekehrt bei „Original zuerst“
|       +-- [Neu übersetzen] (nur Admins)
+-- Ergebnis (PROJ-10)
    +-- Antwortentwurf (nicht deutsch)
        +-- [Auf Deutsch gegenlesen]
        +-- Kasten „Rückübersetzung zur Kontrolle – verschickt wird das Original“
            +-- Hinweis „Entwurf seither geändert – erneut gegenlesen“

Im Hintergrund
+-- Spracherkennung ohne KI (für den Hinweis)
+-- Übersetzer: bereinigter Text → Kontaktdaten ersetzen → Sprachmodell → einsetzen → speichern
+-- Bereinigung und Löschen wie PROJ-11
```

### B) Daten
**Übersetzung einer Nachricht** (eine je Zammad-Nachricht):
- Ticketnummer, Kennung der Nachricht in Zammad, Fingerabdruck des bereinigten Originaltexts
- erkannte Sprache, Zustand (übersetzt / bereits deutsch)
- deutscher Text (verschlüsselt)
- wer, wann, Modell, Prompt-Version
- wird nach 12 Monaten und beim Löschen aller Analysen des Tickets entfernt

**Rückübersetzung des Antwortentwurfs** (an der Analyse, verschlüsselt mit ihren Inhalten):
- deutscher Text, Fingerabdruck des Entwurfstexts, wer, wann, Modell

**Im Browser:** die Wahl „Deutsch zuerst / Original zuerst“.

### C) Technische Entscheidungen (für Nicht-Entwickler)
- **Einmal je Nachricht:** Die Übersetzung hängt an der Nachricht aus Zammad, nicht am Aufruf. Wer das Ticket später öffnet – auch im Testmodus – sieht sie sofort.
- **Fingerabdruck:** Ändert sich der Text einer Nachricht in Zammad, passt der Fingerabdruck nicht mehr; die Nachricht wird erneut zum Übersetzen angeboten. Beim Antwortentwurf zeigt derselbe Vergleich „seither geändert“.
- **Erkennen ohne KI:** Die App zählt häufige deutsche Wörter gegen häufige Wörter anderer Sprachen. Das reicht für den Hinweis; die genaue Sprache nennt das Sprachmodell beim Übersetzen. Stuft das Modell eine Nachricht als deutsch ein, wird das gemerkt und nicht erneut gefragt.
- **Ein Aufruf für viele Nachrichten:** Alle offenen Nachrichten gehen gemeinsam an das Modell und kommen einzeln zugeordnet zurück; bei sehr langen Verläufen in mehreren Teilen. Gelingt ein Teil nicht, bleiben die anderen gespeichert.
- **Gleicher Datenschutz wie die Analyse:** bereinigter Text, Kontaktdaten als Platzhalter, in der App wieder eingesetzt; gleiche Begrenzung der KI-Aufrufe.
- **Analyse unverändert:** Sie arbeitet weiter mit dem Original.

### D) Abhängigkeiten
Keine neuen Pakete.

### E) Hinweise für /frontend und /backend
- Übersetzen und Gegenlesen nur mit gewähltem Namen; „Neu übersetzen“ nur für Admins.
- Übersetzungen werden als reiner Text mit Zeilenumbrüchen angezeigt (kein HTML aus dem Modell).
- Fehlgeschlagene Aufrufe wie bei der Analyse behandeln (verständliche Meldung, Log ohne Inhalte).
- Seite „Über die App“ um die Übersetzung ergänzen.
- Tests: Erkennung (deutsch/fremd/kurz/unbestimmbar), Hinweis und Knopf, Speichern und Wiederverwenden, „bereits deutsch“, geänderte Nachricht, Teilfehler, Platzhalter, Anzeige mit Original, Gegenlesen mit „seither geändert“ und Wiederverwendung, Bereinigung/Löschen, Name und Admin-Prüfung.

## Implementation Notes (Frontend + Backend)
**Gebaut am 2026-10-09**, Frontend und Backend in einem Durchgang.

- **Tabelle** `message_translations` (Migration 2026_10_09_061844): eine Zeile je Zammad-Nachricht mit Fingerabdruck des bereinigten Originaltexts, Zustand `translated`/`german`, Sprache, deutschem Text (verschlüsselt), Name, Modell, Prompt-Version. Modell `MessageTranslation` mit Factory.
- **`App\Analysis\LanguageDetector`:** zählt häufige deutsche Wörter gegen häufige Wörter in Englisch, Französisch, Italienisch, Niederländisch, Spanisch; unter 20 Zeichen (`analysis.translation.min_length`) oder ohne erkennbare Wörter „unbestimmt“.
- **`App\Analysis\Translator`:** `translations()` (nur gültige, Fingerabdruck passt), `pending()` (als fremd erkannt, ohne gültige Übersetzung), `translate()` (alle nicht als deutsch erkannten Nachrichten ohne Übersetzung, in Teilen zu höchstens `analysis.translation.batch_characters` = 12.000 Zeichen; Teilfehler lassen die übrigen gespeichert; „bereits deutsch“ wird gemerkt), `backTranslate()` (Rückübersetzung des Entwurfs, an der Analyse gespeichert, Wiederverwendung bei gleichem Text). Kontaktdaten über `Pseudonymizer` ersetzt und wieder eingesetzt.
- **Agent und Prompt:** `Agents\TranslationAgent` (strukturierte Antwort je Nachricht-ID), `resources/prompts/translation.md` (`translation-2026-10-09.1`), Modell `analysis.models.translation` (`TRANSLATION_MODEL`, Standard wie die Zusammenfassung).
- **Routen** (Name erforderlich, gleiche Begrenzung der KI-Aufrufe): `POST /tickets/{n}/uebersetzung` (`TranslateTicketRequest`; mit `nachricht` nur für Admins: einzelne Nachricht neu übersetzen), `POST /tickets/{n}/analyse/{uuid}/gegenlesen` (`BackTranslateReplyRequest`, JSON).
- **Oberfläche:** Hinweis mit „Übersetzen“ und Lade-Overlay über dem Verlauf; Umschalter „Deutsch zuerst / Original zuerst“ (im Browser gemerkt); Nachricht mit „Übersetzt aus … · KI-Übersetzung“, Text als reiner Text, „Original anzeigen“; „Neu übersetzen“ für Admins. Originaltext ausgelagert in `ticket/article-body`. Am Entwurf „Auf Deutsch gegenlesen“ mit Kasten „Rückübersetzung zur Kontrolle – verschickt wird das Original“ und „Entwurf seither geändert – erneut gegenlesen“.
- **Bereinigung/Löschen:** `analysis:purge` löscht Übersetzungen nach 12 Monaten; das Löschen aller Analysen eines Tickets löscht auch dessen Übersetzungen. Rückübersetzungen liegen im Analyse-Inhalt und werden mit ihm bereinigt.
- **Seite „Über die App“:** Schritte 2 und 7 nennen Übersetzen und Gegenlesen.
- **Gegenprobe der Erkennung an echten Tickets (ohne KI):** #2137635 (Italienisch, Amazon) 5 von 5 fremd, #2132884 13 von 15 fremd, zwei deutsche Tickets 0; von 40 Tickets der letzten fünf Tage 10 mit fremdsprachiger Nachricht.
- **Tests:** `tests/Feature/PROJ-28-TranslationTest.php` (27 Fälle). Gesamte Suite: 848 grün. Eine echte Übersetzung gegen OpenAI wurde noch nicht ausgeführt.

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
