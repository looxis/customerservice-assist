# PROJ-31: Einstellungen – Prompts und KI-Modell

## Status: Planned
**Created:** 2026-10-06
**Last Updated:** 2026-10-17

## Dependencies
- Requires: PROJ-27 (Einstellungsseite: Speicherung, Verlauf, Zurücksetzen, Rechte), PROJ-9 (Analyse), PROJ-11 (Protokoll mit Prompt-Version und Modell), PROJ-28 (Übersetzung), PROJ-37 (Router).
- Verwandt: PROJ-13 (Evaluation, später Probelauf für Prompts), PROJ-17 (zweiter Anbieter).

## Ausgangslage
Die sieben Prompts liegen als Dateien im Repository, die vier Modelle in der `.env`. Jede Änderung braucht einen Entwickler, einen Commit und einen Neustart des Betriebs. Niemand außer dem Entwickler sieht, was die KI als Anweisung bekommt.

Diese Spec ergänzt die Einstellungsseite (PROJ-27) um die Unterseite **„Prompts und Modell“**: alle Prompts als Textfelder und das Modell je KI-Aufruf, mit Verlauf, Wiederherstellen und Standard wie bei der Mail-Anzeige.

## User Stories
- Als Admin möchte ich lesen, welche Anweisungen die KI bekommt, und sie anpassen, ohne einen Entwickler zu brauchen.
- Als Admin möchte ich das Modell je Aufruf wählen und sicher sein, dass ein Tippfehler keine Analyse zum Absturz bringt.
- Als Admin möchte ich zu jedem Vorschlag wissen, mit welcher Prompt-Version er entstand, und eine schlechte Änderung schnell zurücknehmen.
- Als Stammkraft oder Aushilfe möchte ich nachlesen können, wie die App „denkt“.

## Out of Scope
- Probelauf mit einem ungespeicherten Prompt gegen einen Fall: mit der Evaluation (PROJ-13).
- Zweiter KI-Anbieter und Anbieterwechsel (PROJ-17); die Seite arbeitet mit OpenAI.
- Zeilenvergleich zwischen Prompt-Versionen (der Verlauf zeigt den Wert jeder Version).
- Fachliche Regeln: Sie gehören in die Wissensbasis, nicht in den Prompt (PRD, Qualitätsprinzipien); die Seite sagt das.
- Weitere KI-Parameter (Temperatur, Zeitlimits, Zeichenbudgets): bleiben in der Konfiguration.

## Die Unterseite „Prompts und Modell“
**Modelle** (vier Felder, Standard: die heutigen Werte `gpt-5.5`, `gpt-5.4-mini`, `gpt-5.4`):
| Aufruf | Wofür |
|---|---|
| Analyse | der Vorschlag zu Ticket oder Frage |
| Zusammenfassung | Zusammenfassung des bisherigen Verlaufs |
| Übersetzung | Nachrichten ins Deutsche, Rückübersetzung |
| Router | zusätzliche Wissensdokumente vorschlagen |

Jedes Feld ist frei beschreibbar; darüber steht die Liste der beim Anbieter verfügbaren Modelle mit genauer Bezeichnung (serverseitig abgefragt, eine Stunde gemerkt; ist sie nicht erreichbar, bleibt das Freitextfeld). **Vor dem Speichern** schickt die App dem gewählten Modell einen winzigen Testaufruf; schlägt er fehl, wird nicht gespeichert, und der Grund steht da.

**Prompts** (sieben Textfelder, Standard: die Dateien der App), je mit Hinweis, wann sie zum Einsatz kommen:
| Prompt | Wann |
|---|---|
| Analyse (Kern) | bei jedem Vorschlag |
| Zusatz: gezielter Wissensabruf | wenn der gezielte Wissensabruf aktiv ist |
| Zusatz: Frage ohne Ticket | bei „Frage stellen“ |
| Zusatz: Produktinformationen | wenn Produktdaten aus dem Katalog vorliegen |
| Router | beim zusätzlichen Wissensvorschlag |
| Zusammenfassung | bei der Zusammenfassung des Verlaufs |
| Übersetzung | bei jeder Übersetzung |

**Prompt-Version:** Eine gespeicherte Änderung bekommt die Version `<Version der Standard-Datei>~E<Nummer>` (z. B. `analysis-2026-10-14.2~E3`), die mit jedem Vorschlag gespeichert und in der Analysen-Übersicht angezeigt wird. Wird der Standard der App später erneuert, während eine eigene Fassung gilt, bleibt die eigene Fassung in Kraft, und die Seite weist hin: „Der Standard der App wurde seit deiner Änderung erneuert.“

**Prüfung vor dem Speichern:** Ein Prompt darf nicht leer sein und höchstens 30.000 Zeichen haben; ein Modellname besteht aus Buchstaben, Ziffern und `.`, `-`, `_`, `:` (höchstens 100 Zeichen). Vor dem Speichern fragt die Seite nach: „Gilt sofort für alle neuen Vorschläge.“

## Acceptance Criteria
- [ ] Angenommen ich öffne „Einstellungen“, wenn ich „Prompts und Modell“ wähle, dann sehe ich vier Modellfelder und sieben Prompts mit ihrem aktuellen Text und dem Hinweis, wann sie benutzt werden; ohne Admin-Recht sind alle Felder schreibgeschützt.
- [ ] Angenommen ich bin Admin, wenn ich einen Prompt ändere und nach der Bestätigung speichere, dann gilt er ab dem nächsten Vorschlag für alle, und im Verlauf steht eine Version mit meinem Namen und Zeitpunkt.
- [ ] Angenommen ein Prompt wurde geändert, wenn ein Vorschlag erstellt wird, dann nennt das Protokoll die Version `<Standard>~E<n>`, und die Analysen-Übersicht zeigt sie und filtert danach.
- [ ] Angenommen ein Zusatz-Prompt gilt nur unter einer Bedingung (z. B. Produktinformationen), dann wird er nur dann angehängt, und die Version nennt ihn wie bisher.
- [ ] Angenommen ich wähle ein Modell, das beim Anbieter existiert, wenn ich speichere, dann besteht der Testaufruf, und das Modell gilt ab dem nächsten Aufruf des jeweiligen Typs.
- [ ] Angenommen ich trage ein unbekanntes Modell ein oder der Testaufruf scheitert, wenn ich speichere, dann wird nichts gespeichert, der Grund steht an der Stelle, und meine Eingabe bleibt.
- [ ] Angenommen die Modellliste des Anbieters ist nicht erreichbar, dann kann ich das Modell trotzdem von Hand eintragen, und der Hinweis sagt, dass die Liste fehlt.
- [ ] Angenommen ich lasse einen Prompt leer oder überschreite 30.000 Zeichen, wenn ich speichere, dann wird abgelehnt, und mein Text bleibt erhalten.
- [ ] Angenommen eine frühere Version, wenn ich sie wiederherstelle, dann gilt sie als neue Version; „Auf Standard der App zurücksetzen“ gilt je Prompt und je Modell.
- [ ] Angenommen der Standard der App wurde erneuert, während eine eigene Fassung gilt, dann bleibt die eigene gültig, und die Seite weist darauf hin.
- [ ] Angenommen eine Analyse ist schon gespeichert, wenn Prompt oder Modell sich ändern, dann bleibt sie unverändert; eine Fortsetzung eines Falls benutzt den aktuellen Prompt und das aktuelle Modell und nennt sie im Protokoll.
- [ ] Angenommen die Datenbank ist nicht erreichbar oder ein gespeicherter Wert unbrauchbar, dann gelten Standard-Prompt und Standard-Modell.

## Edge Cases
- Ein Prompt nur aus Leerzeichen; sehr langer Prompt; Prompt mit Markdown, HTML-Kommentaren oder dem Text einer Versionszeile (wird als normaler Text behandelt).
- Zwei Admins ändern denselben Prompt gleichzeitig: wie bei der Mail-Anzeige wird nichts überschrieben.
- Der Testaufruf läuft in ein Zeitlimit oder in ein Kontingentlimit des Anbieters: Hinweis mit Grund, nichts wird gespeichert.
- Ein Modell steht in der Liste, ist aber für Textaufgaben ungeeignet (z. B. Bildmodell): der Testaufruf scheitert, das Modell wird nicht gespeichert.
- Das Modell ändert sich, während ein Vorschlag läuft: der laufende Aufruf behält sein Modell.
- Ein Standard-Prompt (Datei) fehlt: die App bleibt bei dem Fehler wie bisher, die Seite zeigt den Mangel.

## Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Eigene Unterseite, gleiche Mechanik wie die Mail-Anzeige | Rechte, Verlauf, Zurücksetzen und Gleichzeitigkeit sind schon gelöst (PROJ-27) | 2026-10-17 |
| Kein Probelauf in dieser Spec; Bestätigungsabfrage statt dessen | Der Probelauf braucht eine Analyse ohne Speichern und kostet KI-Geld; er gehört zur Evaluation (PROJ-13) | 2026-10-17 |
| Modell: Freitext mit Vorschlagsliste und Testaufruf vor dem Speichern | Ein Tippfehler würde alle Analysen stoppen; der Testaufruf kostet praktisch nichts | 2026-10-17 |
| Version `<Standard>~E<n>` für eigene Fassungen | Die Herkunft bleibt sichtbar, Vorschläge sind vergleichbar | 2026-10-17 |
| Eigene Fassung bleibt bei erneuertem Standard bestehen, mit Hinweis | Keine stille Überschreibung der Arbeit des Admins | 2026-10-17 |
| Hinweis „Fachliche Regeln gehören in die Wissensbasis“ | PRD: Fachliche Regeln stehen nicht im Prompt | 2026-10-17 |

## Tech Design (Solution Architect)

### Backend nötig?
Ja, mit der Datenbank der Einstellungsseite (PROJ-27). Neue Pakete sind nicht nötig; die Abfrage der Modelle und der Testaufruf laufen über die vorhandene KI-Anbindung.

### A) Bausteine der Oberfläche
```
Seite „Einstellungen“ (Übersicht)
+-- Karte „Prompts und Modell“ (neu)

Seite „Einstellungen > Prompts und Modell“
+-- Hinweis „Fachliche Regeln gehören in die Wissensbasis“
+-- Abschnitt „KI-Modelle“
|   +-- je Aufruf: Feld mit Vorschlagsliste, Erklärung, Standard, Verlauf
+-- Abschnitt „Prompts“
|   +-- je Prompt: großes Textfeld, Hinweis „wann benutzt“, Zeichenzähler,
|       Hinweis „Standard der App wurde erneuert“, Verlauf, „Auf Standard“
+-- Speichern (Bestätigungsabfrage), nur Admins
```
Wiederverwendet werden Karte, Hinweis, Knopf, Textbereich und der Gruppenbaustein der Mail-Anzeige (Verlauf, Wiederherstellen, Standard); neu ist der Gruppentyp „langer Text“ und „Modell“.

### B) Datenmodell
Keine neuen Tabellen: Die Gruppen `prompt_<name>` und `model_<aufruf>` werden wie die der Mail-Anzeige in den vorhandenen Tabellen für Einstellungen und Versionen abgelegt. Ein Prompt-Wert besteht aus dem Text und der Version der Standard-Datei, auf der er beruht.

### C) Technische Entscheidungen
- **Einstellungsverzeichnis erweitern:** Zwei neue Gruppentypen („langer Text“ mit Länge und Pflicht, „Modell“ mit Namensprüfung und Testaufruf). Seite, Verlauf, Wiederherstellen, Rechte und Gleichzeitigkeitsschutz kommen unverändert aus PROJ-27.
- **Prompt laden:** Der Baustein, der heute die Datei liest, fragt zuerst die Einstellung; ohne gespeicherten Wert gilt die Datei. Die Versionszeichenkette entsteht dort (`<Datei-Version>~E<n>`), so ändert sich am Protokoll nichts.
- **Modell lesen:** Die vier Stellen, die heute die Konfiguration lesen, fragen den Einstellungsspeicher (mit Rückfall auf die Konfiguration).
- **Modellliste:** Eine Abfrage beim Anbieter, serverseitig, eine Stunde im Zwischenspeicher; Fehler führen nur zum Hinweis.
- **Testaufruf:** Ein Aufruf mit einem Wort und knapper Antwortlänge, mit kurzem Zeitlimit; er läuft nur beim Speichern einer Modelländerung, nie bei Seitenaufrufen.
- **Sicherheit:** Der Schlüssel des Anbieters bleibt serverseitig; Prompts sind für alle lesbar (Absicht), enthalten aber keine Zugangsdaten und keine Kundendaten.

### D) Abhängigkeiten
Keine neuen Pakete.

## Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Gruppentypen „langer Text“ und „Modell“ im Einstellungsverzeichnis | Alle Funktionen der Seite gelten sofort auch für Prompts und Modelle | 2026-10-17 |
| Prompt-Version `<Datei-Version>~E<n>` am Ladepunkt der Prompts | Das Protokoll bleibt unverändert, die Herkunft ist erkennbar | 2026-10-17 |
| Rückfall auf Datei und Konfiguration bei Fehlern | Die KI-Aufrufe dürfen nie mit leerem Prompt oder ohne Modell laufen | 2026-10-17 |
| Modellliste serverseitig, eine Stunde zwischengespeichert | Der Schlüssel bleibt geheim; keine Abfrage bei jedem Seitenaufruf | 2026-10-17 |

## Open Questions
- [ ] Soll die Seite die Länge des gesamten Prompts (Kern plus Zusätze) in Zeichen oder Tokens anzeigen, damit das Budget sichtbar bleibt? Vorschlag: nur Zeichen je Prompt.
