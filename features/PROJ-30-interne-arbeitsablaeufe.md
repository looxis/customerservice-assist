# PROJ-30: Interne Arbeitsabläufe zum Fall

## Status: Planned
**Created:** 2026-10-05
**Last Updated:** 2026-10-05

## Dependencies
- Requires: PROJ-3 (Knowledge Base einlesen und prüfen) – neuer Dokumenttyp, Prüfung, Fingerabdruck, Wissensstand
- Requires: PROJ-4 (Knowledge-Auswahl) – Regeln für Kundengruppe, Kanal und Produkt werden wiederverwendet
- Requires: PROJ-9 (Fallanalyse per LLM) – liefert Fallkategorie und empfohlene Vorgänge als Grundlage der Vorschläge
- Requires: PROJ-10 (Ergebnisansicht) – der Bereich „Interne Abläufe" steht dort, getrennt vom Antwortentwurf
- Berührt: PROJ-24 (Knowledge-Übersicht zeigt den neuen Typ), PROJ-2 (Vorlage, Authoring Guide, KI-Skill)

## Begriffe
- **Arbeitsablauf (Procedure):** eine Schritt-für-Schritt-Anleitung für Menschen, wie ein Vorgang praktisch umgesetzt wird (z. B. „Neuversand in EOCS anlegen"). Eigener Dokumenttyp `procedure` im Ordner `knowledge/procedures/`, IDs `PROCEDURE-001` usw.
- **Abgrenzung zu `process`:** `process` beschreibt, was fachlich zu prüfen und zu entscheiden ist, und geht an die KI. `procedure` beschreibt, wie es in EOCS, Zammad oder Amazon umgesetzt wird, und ist für den Mitarbeiter.
- **Vorgang:** die konkrete Maßnahme, um die es geht. Feste, erweiterbare Werteliste, gemeinsam für Arbeitsabläufe, Befugnisse (`permission.action`) und das Analyseergebnis:

| Wert | Bedeutung |
|---|---|
| `return` | Retoure |
| `reshipment` | Neuversand |
| `reproduction` | Neuproduktion |
| `refund` | Erstattung |
| `partial-refund` | Teilerstattung / Kulanz |
| `cancellation` | Storno |
| `address-change` | Adressänderung |
| `photo-request` | Foto anfordern |
| `escalation` | Eskalation / Vier-Augen-Prüfung |

## User Stories
- Als Aushilfe möchte ich nach der Analyse sofort die passende interne Anleitung sehen (z. B. wie ein Neuversand angelegt wird), damit ich die empfohlene Maßnahme ohne Rückfrage richtig umsetze.
- Als Mitarbeiter möchte ich eine Anleitung auch selbst auswählen können, wenn die App keine oder eine unpassende vorschlägt.
- Als Mitarbeiter möchte ich kritische Hinweise in einer Anleitung nicht übersehen können, damit teure Fehler (falsche Adresse, doppelter Versand) vermieden werden.
- Als Mitarbeiter möchte ich Schritte und Abschlusskontrolle beim Abarbeiten abhaken können, damit ich nichts vergesse.
- Als Mitarbeiter möchte ich sehen, ob eine Anleitung noch Entwurf ist und welche ID sie hat, damit ich Fehler melden kann.
- Als Knowledge-Autor möchte ich neue Abläufe als Markdown-Datei ergänzen, ohne dass jemand die App ändern muss.

## Out of Scope
- Abgehakte Schritte speichern oder für Kollegen sichtbar machen – ggf. mit PROJ-11.
- Abläufe automatisch ausführen (z. B. Neuversand in EOCS anlegen) – Non-Goal.
- Abläufe an die KI geben – sie sind Anleitungen für Menschen; die KI bekommt weiterhin Policies, Permissions, Processes und Playbooks (PROJ-4/PROJ-9).
- Auswahl von Fallkategorie und Vorgang durch den Mitarbeiter vor der Analyse.
- Eigene Seite für Abläufe außerhalb eines Tickets – über die Knowledge-Übersicht (PROJ-24) bereits lesbar.
- Pflege der Vorgangsliste in der App – später über die Einstellungsseite (PROJ-27).
- Bilder oder Screenshots in Anleitungen (die Markdown-Darstellung lädt keine Bilder).

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Dokumenttyp und Prüfung
- [ ] Angenommen eine Datei liegt in `knowledge/procedures/` mit `type: procedure` und ID `PROCEDURE-001`, wenn die Knowledge Base gelesen wird, dann ist sie ein gültiges Dokument dieses Typs und erscheint in der Knowledge-Übersicht unter „Arbeitsabläufe".
- [ ] Angenommen ein Arbeitsablauf wird geprüft, wenn das Feld `actions` fehlt, leer ist oder Werte außerhalb der Vorgangsliste enthält, dann ist das ein Fehler mit Hinweis auf die erlaubten Werte.
- [ ] Angenommen ein Arbeitsablauf wird geprüft, wenn einer der Abschnitte „Voraussetzungen", „Arbeitsschritte" oder „Abschlusskontrolle" fehlt, dann wird eine Warnung gemeldet; „Kritische Hinweise" ist optional.
- [ ] Angenommen eine Permission nennt in `action` einen Wert außerhalb der Vorgangsliste, wenn geprüft wird, dann wird eine Warnung gemeldet (bisher war `action` frei).
- [ ] Angenommen ein neuer Vorgang wird in der Konfiguration ergänzt, wenn danach geprüft wird, dann ist er ohne Programmänderung gültig.
- [ ] Angenommen der Authoring Guide, die Vorlage und der KI-Skill werden gelesen, wenn sie Dokumenttypen beschreiben, dann enthalten sie `procedure` mit Abgrenzung zu `process`, den festen Abschnitten und der Vorgangsliste.

### Vorschläge nach der Analyse
- [ ] Angenommen eine Analyse liegt vor, wenn sie Fallkategorie und empfohlene Vorgänge nennt, dann schlägt die App alle Arbeitsabläufe vor, deren `actions` mindestens einen empfohlenen Vorgang enthält und die nach den Regeln aus PROJ-4 zu Kundengruppe, Kanal und Produkt des Falls passen und deren `categories` leer ist oder die Fallkategorie enthält.
- [ ] Angenommen mehrere Abläufe passen, wenn sie angezeigt werden, dann stehen sie in der Reihenfolge der empfohlenen Vorgänge, innerhalb eines Vorgangs nach ID.
- [ ] Angenommen kein Ablauf passt, wenn die Analyse vorliegt, dann steht im Bereich „Interne Abläufe" „Kein passender Ablauf hinterlegt" mit der Möglichkeit, selbst einen auszuwählen und eine Wissenslücke zu melden (PROJ-12).
- [ ] Angenommen dieselben Angaben und derselbe Wissensstand, wenn zweimal vorgeschlagen wird, dann sind Vorschläge und Reihenfolge identisch.

### Manuelle Auswahl
- [ ] Angenommen der Bereich „Interne Abläufe" ist sichtbar, wenn der Nutzer „Ablauf auswählen" öffnet, dann sieht er alle verwendbaren Abläufe, gruppiert nach Vorgang, mit ID, Titel und Entwurfskennzeichen; Abläufe, die nicht zu Kundengruppe oder Kanal des Falls passen, sind als „gilt nicht für diese Kundengruppe" gekennzeichnet, aber wählbar.
- [ ] Angenommen der Nutzer wählt einen Ablauf, wenn er ihn auswählt, dann wird er zusätzlich zu den Vorschlägen angezeigt.
- [ ] Angenommen der Nutzer schließt einen Ablauf, wenn er ihn schließt, dann verschwindet er aus dem Bereich, ohne dass sich die Vorschläge ändern.

### Anzeige eines Ablaufs
- [ ] Angenommen ein Ablauf wird angezeigt, wenn der Nutzer ihn betrachtet, dann sieht er ID, Titel, Status (Entwurf deutlich gekennzeichnet), Vorgänge und den vollständigen Originaltext mit Voraussetzungen, Arbeitsschritten, kritischen Hinweisen und Abschlusskontrolle.
- [ ] Angenommen ein Ablauf enthält den Abschnitt „Kritische Hinweise" oder Zeilen, die mit „Achtung:" beginnen, wenn er angezeigt wird, dann sind diese deutlich als Warnung hervorgehoben.
- [ ] Angenommen ein Ablauf hat nummerierte Arbeitsschritte und eine Abschlusskontrolle als Liste, wenn er angezeigt wird, dann hat jeder Schritt und jeder Kontrollpunkt ein Kästchen zum Abhaken; nach Neuladen ist alles wieder offen.
- [ ] Angenommen der Bereich „Interne Abläufe" wird angezeigt, wenn der Nutzer die Ergebnisansicht betrachtet, dann ist er optisch klar vom Kundenantwortentwurf getrennt und als „Intern – nicht an den Kunden" überschrieben.
- [ ] Angenommen ein Ablauf verweist auf andere Knowledge-IDs, wenn er angezeigt wird, dann sind diese wie in PROJ-24 verlinkt.
- [ ] Angenommen ein Ablauf ist Entwurf, wenn er angezeigt wird, dann steht ein Hinweis wie bei Entwurfs-Wissen (PROJ-24).

### Erweiterbarkeit
- [ ] Angenommen ein Autor legt eine neue gültige Procedure-Datei an, wenn die App danach einen passenden Fall analysiert, dann wird der Ablauf ohne Programmänderung vorgeschlagen und kann manuell gewählt werden.

## Edge Cases
- **Ablauf mit Fehlern oder `deprecated`:** wird nie vorgeschlagen und ist nicht wählbar.
- **Ablauf ohne Abschnitt „Abschlusskontrolle":** wird angezeigt (Warnung nur in der Prüfung), ohne Kontroll-Kästchen.
- **Sehr viele passende Abläufe** (z. B. fünf für Neuversand): alle werden vorgeschlagen; ab drei sind alle außer dem ersten eingeklappt.
- **Analyse empfiehlt einen Vorgang, der nicht in der Liste steht:** wird ignoriert und im Protokoll vermerkt (PROJ-11); kein Fehler für den Nutzer.
- **Kundengruppe „Noch unklar":** Es werden nur Abläufe ohne Einschränkung auf Kundenart und Kanal vorgeschlagen (wie PROJ-4); alle übrigen bleiben manuell wählbar mit Hinweis.
- **Ablauf gilt für mehrere Vorgänge:** erscheint nur einmal, beim ersten passenden Vorgang.
- **Neuer Analyselauf:** Vorschläge werden aus dem neuen Ergebnis neu bestimmt; manuell gewählte Abläufe bleiben angezeigt.

## Technical Requirements (optional)
- Auswahl deterministisch und ohne KI; Abläufe gehen nicht an das Sprachmodell.
- Vorgangsliste in der Konfiguration, gemeinsam für Procedures, Permissions und das Analyseergebnis.
- Darstellung mit der sicheren Markdown-Darstellung aus PROJ-24.

## Open Questions
- [x] Erste Abläufe definiert und verfasst der Product Owner mit dem KI-Chat nach dem Authoring Guide (Abschnitt „Arbeitsabläufe"); ein erster ist in Arbeit (2026-10-05).
- [ ] Umzug von Teilen aus `processes/` nach `procedures/`: nur, wo es organisatorisch gegeben ist; Entscheidung im Einzelfall beim Erfassen, nicht vorab (2026-10-05).
- [x] PROJ-9 liefert im Analyseergebnis Fallkategorie und empfohlene Vorgänge aus der Vorgangsliste (bestätigt 2026-10-05; in der PROJ-9-Spec festzuschreiben).
- [ ] PERMISSION-002 nutzt `action: replacement` (Neuproduktion und Ersatzversand), PERMISSION-003 `refund` für eine Kulanz-Teilerstattung. Werte an die Vorgangsliste anpassen oder Liste erweitern?

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Eigener Dokumenttyp `procedure` in `knowledge/procedures/` | Klare Trennung: `process` = fachlich prüfen und entscheiden (für die KI), `procedure` = praktisch umsetzen (für Menschen); bestehende Dateien bleiben unverändert | 2026-10-05 |
| Vorschläge erst nach der Analyse, auf Basis von Fallkategorie und empfohlenen Vorgängen | Kategorie und Maßnahme stehen erst nach der Analyse fest; keine zusätzlichen Eingaben für den Mitarbeiter | 2026-10-05 |
| Manuelle Auswahl jederzeit, auch von Abläufen außerhalb der Kundengruppe (mit Hinweis) | Der Mensch entscheidet; die Vorschläge können fehlen oder danebenliegen | 2026-10-05 |
| Gemeinsame, erweiterbare Vorgangsliste für Procedures, Permissions und Analyseergebnis | Gleiche Begriffe überall; Befugnis und Anleitung zum selben Vorgang lassen sich verknüpfen | 2026-10-05 |
| Arbeitsschritte und Abschlusskontrolle abhakbar, nicht gespeichert | Hilfe beim Abarbeiten ohne Datenbank; Speichern ggf. mit PROJ-11 | 2026-10-05 |
| Abläufe gehen nicht an die KI | Anleitungen für Menschen; hält den Prompt klein und trennt Entscheidung von Umsetzung | 2026-10-05 |
| Kritische Hinweise hervorgehoben (eigener Abschnitt und Zeilen mit „Achtung:") | Teure Fehler vermeiden; einfache, merkbare Konvention für Autoren | 2026-10-05 |
| Priorität P0, gebaut nach PROJ-10 | Wichtig für Aushilfen, setzt Analyseergebnis und Ergebnisansicht voraus | 2026-10-05 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)
_To be added by /architecture_

## Implementation Notes (vorgezogener Teil: Dokumenttyp und Prüfung)
**Gebaut am 2026-10-05**, damit der Product Owner erste Arbeitsabläufe schreiben und prüfen kann, bevor PROJ-9/10 stehen. Vorschläge, manuelle Auswahl und Anzeige folgen mit dem Bau nach PROJ-10.

- `config/knowledge.php`: Typ `procedure` (Ordner `procedures/`, Präfix `PROCEDURE`, Bezeichnung „Arbeitsabläufe"), Vorgangsliste `actions` (Schlüssel → Bezeichnung), Pflichtabschnitte `procedure_sections`, `selection.excluded_types = ['procedure']`.
- Prüfung: `actions` Pflicht und nur bekannte Werte (Fehler), fehlende Abschnitte Voraussetzungen/Arbeitsschritte/Abschlusskontrolle (Warnung), `actions` außerhalb von Procedures unbekanntes Feld, `permission.action` außerhalb der Liste (Warnung).
- Knowledge-Auswahl (PROJ-4) lässt Procedures aus, sie erscheinen auch nicht bei den nicht ausgewählten.
- ID-Übersicht (`knowledge:overview`) nennt den Typ und die erlaubten Vorgänge; Knowledge-Übersicht zeigt „Arbeitsabläufe".
- Vorlage `knowledge/templates/procedure.md`, Ordner `knowledge/procedures/`, Abschnitt „Arbeitsabläufe (`procedure`)" im Authoring Guide (Abgrenzung, Vorgangsliste, Abschnitte, Interviewfragen), README und KI-Skill ergänzt.
- Tests: `tests/Feature/PROJ-30-ProcedureTest.php`; PROJ-2/PROJ-3-Tests an den neuen Typ angepasst.

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
