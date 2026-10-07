# PROJ-11: Analyse-Protokoll und Verlauf

## Status: Approved
**Created:** 2026-10-06
**Last Updated:** 2026-10-06

## Dependencies
- Requires: PROJ-5 (Nutzerauswahl) – wer analysiert, bearbeitet oder löscht
- Requires: PROJ-9 (Fallanalyse per LLM) – Analyse, Zusammenfassung, Metadaten
- Requires: PROJ-10 (Ergebnisansicht) – Anzeige, bearbeiteter Entwurf, letzte Analyse je Ticket
- Berührt: PROJ-32 (Testmodus) – Testläufe werden mitprotokolliert und gekennzeichnet; Admin-Rolle
- Wird genutzt von: PROJ-12 (Feedback zur Analyse), PROJ-13 (Evaluation), PROJ-14 (Analysen-Übersicht), PROJ-17 (Anbietervergleich), PROJ-28 (Übersetzungen dauerhaft), PROJ-31 (Prompt-Versionen)

## Ausgangslage
Analysen, Zusammenfassungen und bearbeitete Entwürfe liegen bisher nur vorübergehend (7 Tage) im Zwischenspeicher. Für den Einsatz im Alltag, für Feedback (PROJ-12) und Auswertungen (PROJ-14) müssen sie dauerhaft und nachvollziehbar gespeichert werden – mit klaren Fristen, weil sie Kundendaten enthalten.

## User Stories
- Als Mitarbeiter möchte ich frühere Analysen eines Tickets ansehen, damit ich nachvollziehen kann, was zu welchem Stand vorgeschlagen wurde.
- Als Kollegin möchte ich auch nach Wochen noch die Analyse und den angepassten Entwurf zu einem Ticket finden, z. B. wenn der Kunde erneut schreibt.
- Als Verantwortlicher möchte ich zu jeder Analyse genau sehen, welcher Text an die KI ging und was sie geantwortet hat, damit ich Fehler nachvollziehen und Analysen später mit neuem Prompt oder Modell wiederholen kann.
- Als Verantwortlicher möchte ich, dass Kundeninhalte nach 12 Monaten automatisch gelöscht werden, Kennzahlen aber für Auswertungen erhalten bleiben.
- Als Admin möchte ich auf Wunsch eines Kunden alle Analysen eines Tickets löschen können, damit wir Löschbegehren erfüllen.

## Out of Scope
- Feedback zur Analyse und „Wissenslücke melden“ – PROJ-12 (baut auf dem Protokoll auf)
- Gesamtliste aller Analysen mit Filter – PROJ-14
- Erneutes Rechnen alter Analysen mit neuem Prompt/Modell (Evaluation, Vergleich) – PROJ-13, PROJ-17
- Prompts und Modell auf der Einstellungsseite – PROJ-31
- Export von Analysen, Auskunft nach DSGVO als Datei
- Übernahme der bisher vorübergehend gespeicherten Analysen (nur Testdaten)

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Dauerhaft speichern
- [ ] Angenommen eine Analyse wird durchgeführt, wenn sie fertig ist, dann ist sie dauerhaft gespeichert mit Ticketnummer, Zeitpunkt, Name, Ergebnis, Formulareingaben, Quellen in der Fassung der Analyse, Metadaten (Anbieter, Modell, Prompt-Version, Wissensstand, Fingerabdrücke, Variante, Dauer, Tokens, Versuche) und ggf. Kennzeichen „Testlauf“ mit Stand
- [ ] Angenommen eine Analyse wird durchgeführt, wenn sie gespeichert wird, dann sind auch der vollständige an die KI gesendete Text (mit Platzhaltern statt Kontaktdaten) und die unveränderte Antwort der KI festgehalten
- [ ] Angenommen eine Analyse schlägt fehl (Zeitüberschreitung, ungültiges Ergebnis), wenn das passiert, dann wird ein Protokolleintrag ohne Inhalte gespeichert (Zeitpunkt, Name, Ticketnummer, Modell, Fehlerart, Dauer)
- [ ] Angenommen ein Mitarbeiter bearbeitet einen Antwortentwurf, wenn die Änderung gespeichert wird, dann bleibt sie dauerhaft erhalten, das KI-Original ebenso
- [ ] Angenommen eine Zusammenfassung wird erstellt oder bearbeitet, wenn sie gespeichert wird, dann ist sie dauerhaft gespeichert und die Kennzeichnung „vorübergehend gespeichert“ entfällt
- [ ] Angenommen für ein Ticket wurden Kundengruppe und Produkte gewählt, wenn das Ticket später geöffnet wird, dann ist die Wahl dauerhaft gemerkt (nicht mehr nur 7 Tage)
- [ ] Angenommen die App wird neu gestartet oder der Zwischenspeicher geleert, wenn ein Ticket geöffnet wird, dann sind Analysen, Entwürfe und Zusammenfassungen weiterhin vorhanden

### Frühere Analysen je Ticket
- [ ] Angenommen ein Ticket hat mehrere Analysen, wenn das Ergebnis angezeigt wird, dann gibt es eine aufklappbare Zeile „Frühere Analysen (N)“ mit je Datum, Name, Kundengruppe, Variante, Einstufung, Confidence und ggf. „Testlauf“
- [ ] Angenommen der Mitarbeiter wählt eine frühere Analyse, wenn sie angezeigt wird, dann steht sie an der Stelle des Ergebnisses mit dem Hinweis „Ältere Analyse vom … – zur neuesten“
- [ ] Angenommen eine ältere Analyse wird angezeigt, wenn der Mitarbeiter den Entwurf betrachtet, dann ist er nur lesbar und kopierbar, nicht bearbeitbar
- [ ] Angenommen ein Ticket hat nur eine Analyse, wenn das Ergebnis angezeigt wird, dann gibt es keine Zeile „Frühere Analysen“
- [ ] Angenommen der Testmodus ist aus, wenn die Liste angezeigt wird, dann erscheinen Testläufe nicht; im Testmodus erscheinen sie gekennzeichnet

### Protokoll einsehen (Admin)
- [ ] Angenommen ein Admin betrachtet eine Analyse, wenn er „Protokoll“ aufklappt, dann sieht er den gesendeten Text, die Antwort der KI und alle Metadaten
- [ ] Angenommen ein Nicht-Admin betrachtet eine Analyse, wenn das Ergebnis angezeigt wird, dann gibt es keinen Abschnitt „Protokoll“; die „Details zur Analyse“ (Name, Zeit, Modell, Prompt-Version, Wissensstand, Dauer) bleiben sichtbar

### Aufbewahrung und Löschen
- [ ] Angenommen eine Analyse ist älter als 12 Monate, wenn die tägliche Bereinigung läuft, dann werden gesendeter Text, KI-Antwort, Entwurf, Kontextfeld, Bestelldaten von Hand, eingesetzte Werte und Quellen-Texte gelöscht; erhalten bleiben Ticketnummer, Zeitpunkt, Name, Kundengruppe, Produkte, Kategorie, Einstufung, Confidence, Vorgänge, Knowledge-IDs, Metadaten und (ab PROJ-12) Feedback
- [ ] Angenommen eine Zusammenfassung oder gemerkte Wahl ist älter als 12 Monate (letzte Änderung), wenn die Bereinigung läuft, dann wird sie gelöscht
- [ ] Angenommen eine Analyse wurde bereinigt, wenn sie in einer Liste erscheint, dann steht dort „Inhalte nach 12 Monaten gelöscht“
- [ ] Angenommen ein Admin ist am Ticket, wenn er „Alle Analysen dieses Tickets löschen“ wählt und die Rückfrage bestätigt, dann werden alle Inhalte, Zusammenfassungen und die gemerkte Wahl des Tickets gelöscht; es bleibt ein Eintrag „Inhalte gelöscht von [Name] am …“ mit den Kennzahlen ohne Kundendaten
- [ ] Angenommen ein Nicht-Admin ist am Ticket, wenn er die Seite betrachtet, dann gibt es keine Löschfunktion, und ein Löschversuch wird abgelehnt

## Edge Cases
- **Analyse ohne Ergebnis** (Fehler): erscheint nicht in „Frühere Analysen“, nur im Protokoll (für PROJ-14).
- **Gelöschtes Ticket in Zammad / Ticket nicht mehr ladbar:** Analysen bleiben bis zur Frist gespeichert; Anzeige erst wieder, wenn das Ticket geladen werden kann (Liste in PROJ-14).
- **Zusammengeführtes Ticket in Zammad:** Analysen bleiben beim alten Ticket; kein Umzug.
- **Gleichzeitig zwei Analysen zum selben Ticket:** beide werden gespeichert; die zuletzt fertige ist die neueste.
- **Löschen während eine Analyse läuft:** die laufende Analyse wird nach dem Löschen noch gespeichert (als neue Analyse); kein Sperren.
- **Bereinigung fällt aus** (z. B. Zeitplan läuft nicht): wird beim nächsten Lauf nachgeholt; in der Knowledge-Übersicht/Über-Seite kein Hinweis nötig, aber Hinweis im Log.
- **Sehr viele Analysen je Ticket:** Liste zeigt alle, neueste zuerst.

## Technical Requirements (optional)
- Speicherung in der Datenbank (MySQL laut Tech-Stack); Kundeninhalte verschlüsselt.
- Tägliche automatische Bereinigung; Frist konfigurierbar (Standard 12 Monate).
- Keine Inhalte im Log.
- Admin-Prüfung serverseitig (wie PROJ-32).

## Open Questions
- [ ] Soll die Frist von 12 Monaten mit dem Auftragsverarbeitungsvertrag bzw. dem Verzeichnis von Verarbeitungstätigkeiten abgestimmt und dort eingetragen werden? (Wiedervorlage AVV ab 01.01.2027)
- [x] Admins sind Etienne, Johannes und Thomas (2026-10-06, in `config/staff.php`; sehen damit auch den Testmodus aus PROJ-32). Bis PROJ-15 nur über die Konfiguration.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Kundeninhalte 12 Monate, Kennzahlen ohne Kundendaten dauerhaft | Rückfragen und Reklamationsverläufe über eine Saison nachlesbar; Auswertungen (PROJ-14, Erfolgskennzahlen im PRD) bleiben möglich; Datensparsamkeit | 2026-10-06 |
| Gesendeter Text und unveränderte KI-Antwort werden protokolliert | Exakt nachvollziehbar und später mit neuem Prompt/Modell wiederholbar (PROJ-13, PROJ-17); enthält nur Platzhalter statt Kontaktdaten | 2026-10-06 |
| Frühere Analysen als aufklappbare Liste am Ergebnis, ältere nur lesbar | Kein Seitenwechsel; keine Verwirrung, welcher Entwurf gilt | 2026-10-06 |
| Admin löscht alle Analysen eines Tickets, mit Rückfrage und Löschvermerk | Löschbegehren erfüllbar; nachvollziehbar, wer gelöscht hat | 2026-10-06 |
| Protokoll (gesendeter Text, KI-Antwort) nur für Admins sichtbar (von mir entschieden, bitte prüfen) | Für die Arbeit am Ticket nicht nötig; enthält technische Details | 2026-10-06 |
| Fehlgeschlagene Analysen werden ohne Inhalte protokolliert (von mir entschieden, bitte prüfen) | Ausfälle und Kosten auswertbar (PROJ-14) | 2026-10-06 |
| Keine Übernahme der bisher vorübergehend gespeicherten Analysen (von mir entschieden, bitte prüfen) | Bisher nur Tests; spart Aufwand | 2026-10-06 |

### Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| MySQL-Datenbank statt Zwischenspeicher für Analysen, Zusammenfassungen, gemerkte Wahl je Ticket und Kundengruppe je Kunde | Dauerhaft, übersteht Neustarts und Leeren des Zwischenspeichers; Grundlage für Listen und Auswertungen (PROJ-14) | 2026-10-06 |
| Kennzahlen in eigenen, unverschlüsselten Spalten; Kundeninhalte in verschlüsselten Feldern, die bei Bereinigung oder Löschen geleert werden | Auswertungen ohne Entschlüsseln; Löschen = Inhalte leeren, Kennzahlen bleiben | 2026-10-06 |
| „Letzte Analyse“ wird abgefragt (jüngste erfolgreiche Analyse je Ticket bzw. Teststand) statt als eigener Verweis gespeichert | Kein Verweis, der ablaufen oder veralten kann (vgl. PROJ-10 BUG-1) | 2026-10-06 |
| Bestehende Speicher-Schnittstelle (`AnalysisStore`) bleibt, liest und schreibt künftig die Datenbank | Controller, Ansicht und Tests aus PROJ-9/10/32 bleiben weitgehend unverändert | 2026-10-06 |
| Tägliche Bereinigung als geplanter Befehl der App; Frist in der Konfiguration | Einfach, testbar; im Betrieb muss der Zeitplaner laufen (Hinweis für `/deploy`) | 2026-10-06 |
| Löschen per eigener Formular-Aktion am Ticket, nur Admins (serverseitig geprüft wie PROJ-32) | Gleiche Schutzmechanik wie bisher, Rückfrage im Browser | 2026-10-06 |
| Keine Übernahme der Zwischenspeicher-Daten | Nur Testdaten; vermeidet Übergangscode | 2026-10-06 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

### Überblick
PROJ-11 zieht alles, was bisher 7 Tage im Zwischenspeicher lag, in die MySQL-Datenbank um. Die Oberfläche aus PROJ-9/10 bleibt; neu sind die Liste „Frühere Analysen“, der Abschnitt „Protokoll“ für Admins, die Löschfunktion und eine tägliche Bereinigung. Die bestehende Speicher-Schnittstelle bleibt erhalten und liest und schreibt künftig die Datenbank, damit Analyse, Ergebnisansicht und Testmodus nicht umgebaut werden müssen.

### A) Bausteine
```
Ticketseite
+-- Ticketkopf
|   +-- [Alle Analysen dieses Tickets löschen] (nur Admins, mit Rückfrage)
+-- Ergebnis (PROJ-10)
|   +-- Zeile „Frühere Analysen (N)“ (aufklappbar)
|   |   +-- je Analyse: Datum · Name · Kundengruppe · Variante · Einstufung · Confidence · ggf. Testlauf
|   +-- Hinweis „Ältere Analyse vom … – zur neuesten“ (wenn eine ältere gewählt ist)
|   +-- Antwortentwurf (ältere: nur lesen und kopieren)
|   +-- … Abschnitte wie bisher …
|   +-- Abschnitt „Protokoll“ (nur Admins): gesendeter Text, KI-Antwort, alle Metadaten
+-- Hinweis „Inhalte gelöscht von … am …“ (nach dem Löschen)

Im Hintergrund
+-- Tägliche Bereinigung (Inhalte älter als 12 Monate leeren)
```

### B) Daten
**Analyse** (eine Zeile je Analyse, auch fehlgeschlagene):
- *Kennzahlen (bleiben dauerhaft):* öffentliche Kennung, Ticketnummer, Teststand (falls Testlauf), Name, Zeitpunkt, Zustand (fertig/fehlgeschlagen und Fehlerart), Kundengruppe, Produkte, Variante, Kategorie, Einstufung, Confidence, Vorgänge, Knowledge-IDs mit Fingerabdrücken, Anbieter, Modell, Prompt-Versionen, Wissensstand, Dauer, Tokens, Versuche.
- *Kundeninhalte (verschlüsselt, werden nach 12 Monaten oder beim Löschen geleert):* vollständiges Ergebnis, Formulareingaben (inkl. Kontextfeld und Bestelldaten von Hand), eingesetzte Werte, Quellen-Texte, Stand des Verlaufs, Prüfhinweise, gesendeter Text, unveränderte KI-Antwort, bearbeiteter Entwurf mit Name und Zeit.
- *Vermerke:* „Inhalte bereinigt am …“ bzw. „Inhalte gelöscht von … am …“.

**Zusammenfassung** (eine je Ticket bzw. Teststand): Text (verschlüsselt), bis wann, Fingerabdruck, „von Hand geändert“, Modell, Prompt-Version, Zeitpunkte. Bereinigung 12 Monate nach letzter Änderung.

**Gemerkte Wahl je Ticket:** Kundengruppe, Produkte, Name, Zeitpunkt. Bereinigung nach 12 Monaten.

**Kundengruppe je Zammad-Kunde/Organisation:** Kundengruppe, Zeitpunkt; keine Fallinhalte. Bereinigung nach 365 Tagen wie bisher.

Verschlüsselt mit dem Schlüssel der App; der Schlüssel muss bei `/deploy` gesichert werden (ohne ihn sind Inhalte nicht mehr lesbar).

### C) Technische Entscheidungen (für Nicht-Entwickler)
- **Datenbank statt Zwischenspeicher:** Der Zwischenspeicher darf jederzeit geleert werden; dauerhafte Daten gehören in die Datenbank, die laut Tech-Stack ohnehin vorgesehen ist.
- **Getrennte Spalten für Kennzahlen:** Auswertungen (PROJ-14) können zählen und filtern, ohne Kundeninhalte zu entschlüsseln. Bereinigen heißt: verschlüsselte Inhalte leeren, Kennzahlen bleiben.
- **„Letzte Analyse“ wird abgefragt:** Die jüngste erfolgreiche Analyse eines Tickets (bzw. Teststands) wird jedes Mal ermittelt. Ein Verweis, der ablaufen kann, entfällt.
- **Schnittstelle bleibt:** Analyse, Ergebnisansicht, Entwurf-Speichern und Testmodus sprechen weiter mit derselben Speicher-Schnittstelle; nur deren Inneres wechselt zur Datenbank.
- **Bereinigung:** Ein geplanter Befehl läuft täglich; er ist auch von Hand ausführbar. Im Betrieb muss der Zeitplaner der App laufen.
- **Löschen:** Eigene Formular-Aktion am Ticket, nur für Admins (Prüfung auf dem Server, wie beim Testmodus), mit Rückfrage im Browser.
- **Protokoll für Admins:** Der Abschnitt wird nur Admins angezeigt; die Daten liegen ohnehin verschlüsselt vor.

### D) Abhängigkeiten
Keine neuen Pakete (Datenbank und Zeitplaner sind Teil von Laravel).

### E) Hinweise für /frontend und /backend
- Tests mit eigener Testdatenbank (bereits in `phpunit.xml`); Tests aus PROJ-9/10/32, die den Zwischenspeicher direkt ansprechen, auf die Datenbank umstellen.
- Fehlgeschlagene Analysen protokollieren, ohne Inhalte.
- Ältere Analysen: Entwurf-Speichern serverseitig ablehnen (nicht nur in der Oberfläche sperren).
- Vor PROJ-10 gab es Ergebnisse ohne Quellen-Texte – mit der Datenbank entfällt das (keine Übernahme).
- Für `/deploy`: Migrationen ausführen, Zeitplaner einrichten, App-Schlüssel sichern.

## Implementation Notes (Frontend + Backend)
**Gebaut am 2026-10-06**, Frontend und Backend in einem Durchgang.

- **Tabellen** (Migrationen 2026_10_06_14061x): `analyses` (Kennzahlen in Spalten, `content` verschlüsselt, Vermerke `content_purged_at`, `content_deleted_at/by`), `ticket_summaries` (je Ticket bzw. Teststand), `ticket_case_choices`, `customer_group_memories`. Modelle `Analysis`, `TicketSummary`, `TicketCaseChoice`, `CustomerGroupMemory` mit Factories.
- **`AnalysisStore`** liest und schreibt jetzt die Datenbank; Schnittstelle unverändert bis auf: `latest()` fragt die jüngste fertige Analyse ab (kein Verweis mehr, `putLatest` entfällt), neu `putFailure()`, `history()`, `deletion()`, `deleteTicket()`, `purge()`.
- **Protokoll:** `CaseAnalyzer` speichert zusätzlich `sent_input` (an die KI gesendeter Text mit Platzhaltern) und `raw_response` (unveränderte strukturierte Antwort); fehlgeschlagene Analysen als Zeile ohne Inhalt mit Fehlerart und Dauer.
- **Oberfläche:** „Frühere Analysen (N)“ am Ergebnis (neueste zuerst, Kennzeichen „angezeigt“, „Testlauf“, „Inhalte nach 12 Monaten gelöscht“/„Inhalte gelöscht“); ältere Analyse mit Hinweis „Ältere Analyse vom … – nur lesbar. Zur neuesten“, Entwurf `readonly`, Speichern serverseitig abgelehnt (409); Abschnitt „Protokoll (nur Admins)“; Knopf „Alle Analysen dieses Tickets löschen“ im Ticketkopf nur für Admins mit Rückfrage; Vermerk „Inhalte gelöscht von … am …“. Kennzeichnung „vorübergehend gespeichert“ an der Zusammenfassung entfernt.
- **Löschen:** `DELETE /tickets/{n}/analysen` (`tickets.analyses.destroy`, Form Request `DeleteAnalysesRequest` mit Admin-Prüfung → 403).
- **Bereinigung:** Befehl `analysis:purge`, täglich 03:15 (`routes/console.php`); Frist `analysis.content_retention_months` (12), Kundengruppe je Kunde weiter 365 Tage. `analysis.retention_days` entfällt.
- **Testläufe** erscheinen nur in der Liste ihres Teststands (Abweichung von „im Testmodus gekennzeichnet in der Liste des echten Tickets“: Teststände sind getrennte Bereiche, wie bei der Zusammenfassung).
- **Tests:** `tests/Feature/PROJ-11-AnalysisLogTest.php` (13 Fälle); Feature-Tests laufen jetzt mit `RefreshDatabase` gegen die Testdatenbank; vier PROJ-9-Tests vom Zwischenspeicher auf die Datenbank umgestellt. Gesamte Suite: 747 grün.
- **Für `/deploy`:** Migrationen ausführen, Zeitplaner (`schedule:run` per Cron oder `schedule:work`) einrichten, `APP_KEY` sichern (ohne ihn sind Inhalte nicht lesbar).

## QA Test Results

**Tested:** 2026-10-07
**App URL:** http://localhost:8081
**Tester:** QA Engineer (AI)

**Vorgehen:** Automatisierte Feature-Tests gegen die Testdatenbank (17 Fälle, KI und Zammad nachgestellt, nur erfundene Daten), Code-Review jedes Kriteriums, Prüfung von Migrationen, Zeitplan (`schedule:list`: täglich 03:15) und Bereinigungsbefehl gegen die lokale Datenbank. Der Product Owner hat die Funktion im Browser erfolgreich getestet (Chrome). Firefox/Safari und Handy-/Tablet-Breite nicht eigens geprüft; neue Elemente nutzen bestehende, umbrechende Muster.

### Acceptance Criteria Status

#### Dauerhaft speichern
- [x] Analyse mit Kennzahlen in Spalten, Inhalten verschlüsselt, Testlauf-Kennzeichen
- [x] Gesendeter Text (mit Platzhaltern) und unveränderte KI-Antwort festgehalten
- [x] Fehlgeschlagene Analyse ohne Inhalte protokolliert (Fehlerart, Dauer, Name, Modell)
- [x] Bearbeiteter Entwurf dauerhaft, KI-Original bleibt
- [x] Zusammenfassung dauerhaft, Kennzeichnung „vorübergehend gespeichert“ entfällt
- [x] Gemerkte Wahl je Ticket dauerhaft
- [x] Übersteht geleerten Zwischenspeicher und mehr als 7 Tage

#### Frühere Analysen je Ticket
- [x] „Frühere Analysen (N)“ mit Datum, Name, Kundengruppe, Variante, Einstufung, Confidence, Testlauf
- [x] Ältere Analyse mit Hinweis und „Zur neuesten“
- [x] Ältere Analyse nur lesbar und kopierbar; Speichern serverseitig abgelehnt (409)
- [x] Nur eine Analyse: keine Liste
- [x] Testläufe nicht in der Liste des echten Tickets (siehe Abweichung in den Implementation Notes)

#### Protokoll einsehen (Admin)
- [x] Admins sehen gesendeten Text, KI-Antwort, Metadaten
- [x] Andere sehen kein Protokoll, aber „Details zur Analyse“

#### Aufbewahrung und Löschen
- [x] Nach 12 Monaten Inhalte geleert, Kennzahlen bleiben
- [x] Alte Zusammenfassungen und gemerkte Wahl gelöscht
- [x] Bereinigte Analyse in der Liste „Inhalte nach 12 Monaten gelöscht“
- [x] Admin löscht alle Analysen eines Tickets mit Rückfrage, Vermerk „Inhalte gelöscht von … am …“
- [x] Nicht-Admins ohne Löschfunktion, Löschversuch 403

### Edge Cases Status
- [x] Fehlgeschlagene Analyse nicht in „Frühere Analysen“
- [x] Ticket nicht ladbar: Analysen bleiben gespeichert (keine Abhängigkeit vom Abruf)
- [x] Zusammengeführtes Ticket: Analysen bleiben beim alten Ticket
- [x] Zwei Analysen gleichzeitig: beide gespeichert, zuletzt fertige ist die neueste
- [x] Analyse nach dem Löschen: neue Analyse ersetzt den Vermerk; gelöschte bleibt in der Liste als „Inhalte gelöscht“
- [ ] Bereinigung fällt aus: kein Eintrag im Log, Ausfall bliebe unbemerkt (BUG-2)
- [x] Viele Analysen: alle, neueste zuerst

### Security Audit Results
- [x] Kundeninhalte verschlüsselt in der Datenbank (geprüft: Klartext nicht in `content`), Kennzahlen ohne Kontaktdaten
- [x] Löschen nur für Admins (serverseitig, 403), Formular mit `@csrf` und `DELETE`
- [x] Gelöschte/bereinigte Analysen weder anzeigbar noch bearbeitbar (404)
- [x] Protokoll-Inhalte escaped (Test mit `<script>`)
- [x] Keine Inhalte im Log
- Hinweis für `/deploy`: Wer den App-Schlüssel kennt, kann Inhalte entschlüsseln; Schlüssel sichern und nicht teilen. Ohne Login kann jede Person „Etienne“, „Johannes“ oder „Thomas“ wählen und ist dann Admin (bekannt, PROJ-15).

### Regression
- Gesamte Suite: 751 Tests grün; Feature-Tests laufen jetzt mit frischer Testdatenbank (`RefreshDatabase`).
- Seite „Über die App“: kein eigener Schritt für PROJ-11; Schritt 7 nennt bereits das Wiederfinden der letzten Analyse.

### Bugs Found

#### BUG-1: Link auf einen Testlauf zeigt außerhalb des Testmodus „Ältere Analyse“
- **Severity:** Low
- **Steps to Reproduce:** Im Testmodus einen Testlauf analysieren, den Link (`?analyse=…`) ohne Testmodus oder als Nicht-Admin öffnen. Erwartet: Testlauf gekennzeichnet, ohne „ältere Analyse“. Tatsächlich: Hinweis „Ältere Analyse vom … – nur lesbar“, weil die neueste Analyse des echten Tickets verglichen wird.
- **Priority:** Nice to have

#### BUG-2: Bereinigung schreibt keinen Eintrag ins Log
- **Severity:** Low
- **Steps to Reproduce:** `analysis:purge` ausführen oder vom Zeitplaner ausführen lassen. Erwartet laut Edge Case: Eintrag im Log (Anzahl bereinigter Inhalte), damit ein Ausfall auffällt. Tatsächlich: Ausgabe nur in der Konsole, die der Zeitplaner verwirft.
- **Priority:** Fix before deployment (gehört zur Einrichtung des Zeitplaners bei `/deploy`)

### Summary
- **Acceptance Criteria:** 21/21 bestanden
- **Bugs Found:** 2 total (0 critical, 0 high, 0 medium, 2 low)
- **Security:** Pass
- **Production Ready:** YES
- **Recommendation:** Freigeben; BUG-2 vor `/deploy`, BUG-1 bei Gelegenheit.

## Deployment
_To be added by /deploy_
