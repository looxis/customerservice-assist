# PROJ-56: Vorläufiges Wissen und Überprüfung

## Status: In Progress
**Created:** 2026-10-19
**Last Updated:** 2026-10-19

## Dependencies
- Requires: PROJ-3 (Wissen einlesen und prüfen), PROJ-12 (Meldungen und Wissenslücken), PROJ-23 (Status per Klick), PROJ-36 (Abruf), PROJ-41 (Seitenleiste).

## Ausgangslage
Manche Vorgehensweisen werden bewusst nur eine Zeit lang getestet („das machen wir mal einige Wochen so“). Ohne Befristung laufen sie jahrelang weiter, auch wenn sie nachweislich nichts bringen. Heute kennt die Wissensbasis nur `draft` (unbestätigt), `active` und `deprecated`. Dazu soll jeder Mitarbeiter jedes Wissen als „möglicherweise veraltet / bitte prüfen“ melden können.

## Umfang in vier Schritten
1. **Befristung im Dokument** (`trial_until`, `trial_goal`), Kennzeichnung, Prompt-Hinweis, Prüfung. **Gebaut am 2026-10-19.**
2. **Meldung „bitte prüfen“ für alle Wissensdokumente** (gebaut 2026-10-19) durch Mitarbeiter, Reiter „Zur Prüfung“ in der Admin-Seite der Wissenslücken, Zähler je Dokument, Hinweis „mehrfach gemeldet“ ab 3 Meldungen von 2 Mitarbeitern, nie automatische Abschaltung; Admin-Aktionen „Geprüft, bleibt“, „Für den KI-Chat kopieren“, „Als veraltet markieren“.
3. **Ablauf der Testphase** (gebaut 2026-10-19): Das Dokument bleibt im Einsatz mit der Warnung „Prüfung überfällig seit X Tagen“ (Ergebnis, Wissensübersicht, Seitenleiste, Admins); nach 30 Tagen ohne Entscheidung wird es nicht mehr abgerufen; Entscheidung per Klick (bewährt und dauerhaft gültig, Testphase verlängern, beenden), Zahl „zur Überprüfung“ in der Seitenleiste.
4. **Belege je Dokument** (gebaut 2026-10-19) aus vorhandenen Daten: wie oft abgerufen, Entwürfe unverändert/angepasst/gesendet, Bewertungen, Meldungen; für befristete hervorgehoben.

## Out of Scope
- Automatisches Abschalten von gemeldetem Wissen.
- Ändern von Wissensdateien und Committen aus der App (siehe PROJ-55).
- Eine eigene Statusart „vorläufig“: Die Befristung ist unabhängig vom Status.

## Acceptance Criteria – Schritt 1
- [x] Angenommen ein Dokument hat `trial_until: 2027-01-31` (als Text oder als YAML-Datum), wenn die App es liest, dann ist es als Testphase erkannt; letzter Tag der Testphase ist der 31.01., danach gilt sie als abgelaufen.
- [x] Angenommen `trial_until` ist kein gültiges Datum, dann meldet `knowledge:check` einen Fehler an dieser Stelle.
- [x] Angenommen `trial_goal` steht ohne `trial_until` oder die Testphase hat kein Ziel, dann gibt es eine Warnung.
- [x] Angenommen die Testphase endet in 14 Tagen oder weniger oder ist vorbei, dann warnt `knowledge:check` mit Datum.
- [x] Angenommen ein Vorschlag nutzt ein Dokument in der Testphase, dann steht im Wissensabschnitt für die KI „in Erprobung“ mit Datum und Ziel und der Auftrag, das kurz zu erwähnen; nach Ablauf zusätzlich, dass die Überprüfung überfällig ist.
- [x] Angenommen ein Vorschlag stützt sich auf ein Dokument in der Testphase, dann nennt das Ergebnis es („Beruht teilweise auf vorläufigem Wissen“), die Quelle trägt „Vorläufig bis TT.MM.JJJJ“, nach Ablauf „Testphase abgelaufen, Überprüfung überfällig“.
- [x] Angenommen ein Dokument ist in der Testphase, dann zeigt die Wissensübersicht „Vorläufig bis …“ (nach Ablauf „abgelaufen“, rot) und die Dokumentseite einen Hinweis mit Ziel.
- [x] Angenommen ein Dokument hat keine Befristung, dann ändert sich nichts.

## Acceptance Criteria – Schritt 2
- [x] Angenommen ich habe meinen Namen gewählt, wenn ich unter einer Quelle im Ergebnis oder auf der Dokumentseite „Möglicherweise veraltet? Bitte prüfen lassen“ mit Grund (hilft nicht, Kunden reagieren schlecht, Regel stimmt nicht mehr, Sonstiges) und optionalem Kommentar absende, dann ist die Meldung gespeichert und ich sehe eine Bestätigung.
- [x] Angenommen ich melde dasselbe Dokument ein zweites Mal, dann ersetzt die neue Meldung meine offene; eine Person zählt je Dokument nur einmal.
- [x] Angenommen ein Dokument hat mindestens 3 offene Meldungen von mindestens 2 Personen, dann trägt es „Mehrfach zur Prüfung gemeldet“ (Quelle im Ergebnis, Wissensübersicht, Dokumentseite); geschlossene Meldungen zählen nicht; abgeschaltet wird nichts.
- [x] Angenommen ich bin Admin, wenn ich bei den Wissenslücken den Reiter „Zur Prüfung“ öffne, dann sehe ich je Dokument die Meldungen mit Person, Datum, Grund, Kommentar und Ticket, den Hinweis „seit einer Meldung geändert“, und kann „Geprüft, bleibt“ wählen (schließt nur die Meldungen dieses Dokuments), „Für den KI-Chat kopieren“ (Dokument, Meldungen und Inhalt) und zum Dokument wechseln (dort „Als veraltet markieren“).
- [x] Angenommen ich bin kein Admin, dann ist der Reiter und das Schließen verboten (403); eine unbekannte oder ungültige Dokument-ID, ein ungültiger Grund oder ein zu langer Kommentar wird abgelehnt; mehr als 20 Meldungen pro Minute werden gebremst.
- [x] Angenommen der Kommentar enthält Fallangaben, dann ist er verschlüsselt gespeichert und wird mit den übrigen Inhalten nach der Aufbewahrungsfrist gelöscht.

## Acceptance Criteria – Schritt 3
- [x] Angenommen die Testphase ist vorbei, dann bleibt das Dokument 30 Tage weiter in Verwendung und trägt überall „Testphase abgelaufen, Überprüfung überfällig seit X Tagen“ (Ergebnis, Wissensübersicht, Dokumentseite mit Restlaufzeit, Hinweis an die KI, `knowledge:check`).
- [x] Angenommen seit dem Ende sind mehr als 30 Tage vergangen und niemand hat entschieden, dann wird das Dokument nicht mehr abgerufen (Auswahl und Abruf), Wissensübersicht und Dokumentseite sagen es, `knowledge:check` warnt.
- [x] Angenommen ich bin Admin, wenn ich ein befristetes Dokument öffne, dann sehe ich „Entscheidung zur Testphase“ mit „Bewährt, dauerhaft gültig“ (entfernt `trial_until` und `trial_goal`), „Testphase verlängern“ mit neuem Enddatum (heute bis höchstens ein Jahr; ein abgelaufenes Dokument wird wieder verwendet) und „Beenden (veraltet)“ (setzt den Status auf `deprecated`).
- [x] Angenommen die Datei hat sich seit dem Öffnen der Seite geändert, ist nicht schreibbar oder das Datum ist ungültig, dann wird nichts geändert und der Grund genannt; sonst bleiben alle anderen Zeilen der Datei unverändert (auch mit Windows-Zeilenenden und mehrzeiligem Ziel). Committet wird von Hand.
- [x] Angenommen ein Nicht-Admin versucht zu entscheiden, dann 403; bei einem Dokument ohne Testphase gibt es kein Feld und keine Entscheidung.
- [x] Angenommen Wissen hat eine abgelaufene Testphase oder offene Meldungen, dann zeigt die Seitenleiste für alle „Wissen zur Überprüfung: n“ (je Dokument einmal); ohne solche Dokumente fehlt die Zeile.

## Acceptance Criteria – Schritt 4
- [x] Angenommen ich bin Admin und öffne ein Dokument in der Testphase, dann sehe ich unter „Entscheidung zur Testphase“ das Ziel und die Belege: wie oft die KI es genannt hat (davon in den letzten 30 Tagen), den Anteil „unverändert oder leicht angepasst“ unter den bewerteten Vorschlägen im Vergleich zu allen Vorschlägen, was aus den Antwortentwürfen wurde (gesendet oder kopiert, jeweils unverändert oder angepasst, nur bearbeitet, nicht übernommen) und die Meldungen „bitte prüfen“ nach Grund.
- [x] Angenommen das Dokument ist nur in wenigen Vorschlägen genannt worden, dann weist die Ansicht auf die dünne Grundlage hin.
- [x] Angenommen ein Dokument hat Meldungen, dann bietet der Reiter „Zur Prüfung“ je Dokument die Belege eingeklappt an.
- [x] Angenommen es gibt Testläufe oder fehlgeschlagene Vorschläge, dann zählen sie nicht; für Nicht-Admins und Dokumente ohne Testphase erscheinen die Belege nicht.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Befristung unabhängig vom Status (`active` oder `draft`) | `draft` meint „unbestätigt“, nicht „bewusst befristeter Test“ | 2026-10-19 |
| Nach Ablauf weiter nutzen, aber laut warnen; nach 30 Tagen ohne Entscheidung nicht mehr abrufen | Entscheidung des Auftraggebers: Ein Verfahren soll nicht mitten im Fall verschwinden, aber auch nicht jahrelang unbemerkt weiterlaufen | 2026-10-19 |
| Meldung „bitte prüfen“ gilt für alle Wissensdokumente | Entscheidung des Auftraggebers | 2026-10-19 |
| Meldungen schalten nie automatisch ab | Ein einzelner Mitarbeiter soll Wissen nicht allein außer Kraft setzen | 2026-10-19 |

### Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Felder im Frontmatter (`trial_until`, `trial_goal`), Datum als `JJJJ-MM-TT`, letzter Tag in Berliner Zeit | Dokumente bleiben Markdown mit Git-Historie; kein neuer Speicher nötig | 2026-10-19 |
| Hinweis an die KI über den Wissensabschnitt (wie die Hinweise zu Dokumenten ohne geprüfte Bedingung), nicht über den Prompt | Der Prompt bleibt unverändert und behält seine Version | 2026-10-19 |
| Die Befristung wird mit der Quelle im Protokoll gespeichert (`trial_until`, `trial_goal`) | Ein alter Vorschlag zeigt den Stand von damals | 2026-10-19 |

## Implementation Notes (Schritt 1, 2026-10-19)
- **Gelesen und geprüft:** `KnowledgeDocument::trialUntil()/trialGoal()/isTrial()/trialExpired()`, `KnowledgeValidator::trialFields()` (Fehler bei ungültigem Datum; Warnung bei Ziel ohne Datum, ohne Ziel, 14 Tage vor Ende und nach Ende); die zwei Felder sind bekannte Felder.
- **Prompt:** `CaseAnalyzer::trialNote()` setzt vor den Text des Dokuments einen Hinweis (Datum, Ziel, Auftrag zur Erwähnung); die Überschrift trägt „in Erprobung“.
- **Anzeige:** Quellen im Ergebnis (Badge und Hinweiszeile), Wissensübersicht (Badge), Dokumentseite (Hinweis), `AnalysisPanel::sources()` liest die Felder aus dem Protokoll.
- **Autoren:** Authoring Guide, Skill `knowledge` und die Vorlagen Policy und Playbook kennen die Felder.
- **Tests:** `PROJ-56-TrialKnowledgeTest` (12 Tests).

## Implementation Notes (Schritt 2, 2026-10-19)
- **Neu:** Tabelle `knowledge_review_flags` (Modell `KnowledgeReviewFlag`, Kommentar verschlüsselt, Stand des Dokuments als Fingerabdruck), `KnowledgeReviewLog` (melden, zählen, Liste, „Geprüft, bleibt“, Text für den KI-Chat, Aufbewahrung), `KnowledgeReviewController` mit Routen `knowledge.review.store` (POST, Drosselung 20 je Minute) und `knowledge.review.resolve` (nur Admins), Anfragen `StoreKnowledgeReviewRequest` und `ResolveKnowledgeReviewRequest`, Baustein `components/knowledge/review-form`, Konfiguration `knowledge.review` (3 Meldungen, 2 Personen).
- **Anzeige:** Formular unter jeder Quelle im Ergebnis, auf der Dokumentseite und Hinweis „Mehrfach zur Prüfung gemeldet“ in Ergebnis, Liste und Dokumentseite; Reiter „Zur Prüfung (n)“ bei den Wissenslücken.
- **Tests:** `PROJ-56-ReviewFlagsTest` (20 Tests).
- **Abweichung vom Plan:** Eigene Tabelle statt Erweiterung der Wissenslücken, weil Meldungen zu einem Dokument und Lücken (fehlendes Wissen) unterschiedlich ausgewertet werden; sichtbar liegen sie in derselben Admin-Seite.

## Implementation Notes (Schritt 3, 2026-10-19)
- **Neu:** `KnowledgeDocument::trialOverdueDays()/trialLapsed()`, `KnowledgeLibrary::usable()` ohne abgelaufene Dokumente (Auswahl, Abruf und alle Stellen, die `usable()` nutzen), `TrialChanger` (ändert nur die betroffenen Zeilen des Dateikopfs, prüft den Fingerabdruck, schreibt atomar), `ChangeDocumentTrialRequest`, Route `knowledge.trial` (POST, Admins), Panel „Entscheidung zur Testphase“ auf der Dokumentseite, `AppStats::review()` und die Zeile in der Seitenleiste; Konfiguration `knowledge.trial` (Nachfrist 30 Tage, Verlängerung höchstens 366 Tage).
- **Tests:** `PROJ-56-TrialLapseTest` (17 Tests).
- **Schritt 4** (Belege je Dokument) ist noch offen.

## Implementation Notes (Schritt 4, 2026-10-19)
- **Neu:** `DocumentEvidence::usage()` (nur gespeicherte Spalten, ohne Inhalte), Baustein `components/knowledge/usage`, Einbau in das Panel „Entscheidung zur Testphase“ und in den Reiter „Zur Prüfung“.
- **Abweichung vom Plan:** „Wie oft abgerufen“ steht hier nicht, weil die Liste der abgerufenen Dokumente nur im verschlüsselten Inhalt liegt; die schon vorhandene Statusansicht (PROJ-23) zeigt „abgerufen“ weiter. Neu sind genannt, zuletzt 30 Tage, Verbleib der Entwürfe, Bewertung im Vergleich und Meldungen.
- **Tests:** `PROJ-56-TrialUsageTest` (8 Tests).
- Alle vier Schritte sind gebaut; offen ist `/qa`.

## QA Test Results
_To be added by /qa_
