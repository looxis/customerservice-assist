# PROJ-50: Einstellungen – Kundengruppen, Kategorien, Vorgänge

## Status: In Progress
**Created:** 2026-10-17
**Last Updated:** 2026-10-17

## Dependencies
- Requires: PROJ-27 (Einstellungsseite: Speicherung, Verlauf, Rechte), PROJ-3 (Prüfung der Wissensdokumente), PROJ-4 (Auswahl nach Kundengruppe), PROJ-36 (verbindliche Regeln, Klassen).
- Verwandt: PROJ-30 (Vorgänge in Arbeitsabläufen), PROJ-37 (Router nutzt die Kategorien), PROJ-7 (EOCS-Kanalnamen), PROJ-31 und PROJ-49 (weitere Unterseiten).

## Ausgangslage
Die Werte, die den Kern der fachlichen Zuordnung bilden, stehen in `config/knowledge.php`: vier Kundenarten, fünf Vertriebskanäle, sechs Kundengruppen, vierzehn Namen von EOCS-Kanälen, vier Fallkategorien und vierzehn Vorgänge samt Unterscheidungshinweisen. Eine neue Kundengruppe oder ein neuer Vorgang braucht einen Entwickler. Die Prüfung der Wissensdokumente nimmt diese Listen als erlaubte Werte, Wissensdokumente nennen sie im Kopf (`customer_types`, `sales_channels`, `categories`, `actions`), und eine verbindliche Regel kann ohne die passende Einschränkung stillschweigend nicht mehr greifen.

Diese Spec ergänzt die Einstellungsseite (PROJ-27) um die Unterseite **„Kundengruppen und Werte“**: die Werte sind sichtbar und änderbar, aber mit Schutz davor, dass Dokumente oder Regeln unbemerkt wirkungslos werden.

## User Stories
- Als Admin möchte ich eine neue Kundengruppe anlegen und sicher sein, dass alle Wissensdokumente dafür geprüft sind, bevor Mitarbeiter sie wählen.
- Als Admin möchte ich einen neuen Vorgang oder eine neue Fallkategorie anlegen oder beschriften, ohne einen Entwickler.
- Als Admin möchte ich einen EOCS-Kanalnamen einer Kundengruppe zuordnen, damit die App sie aus der Bestellung vorschlägt.
- Als Wissensautor möchte ich die aktuell erlaubten Werte kopieren, damit ein KI-Chat sie beim Schreiben von Dokumenten verwendet.
- Als Stammkraft oder Aushilfe möchte ich nachlesen können, welche Kundengruppen, Kategorien und Vorgänge es gibt.

## Out of Scope
- Die Wissensdokumente selbst zu ändern (die Seite zeigt, was zu prüfen ist; die Änderung geschieht in den Dokumenten).
- Der Stand der Dokumente und ihre Klassen (`retrieval`), die Abrufregeln (`when_keys`, `unavailable_for_groups`) und die veralteten Werte (`retired_values`): bleiben in der Konfiguration.
- Das Umbenennen von Schlüsseln (technischen Namen) bestehender Werte.
- Automatisches Ändern der Wissensdokumente.
- Anmeldung und Benutzerverwaltung (PROJ-15).

## Die Unterseite „Kundengruppen und Werte“
Sechs Gruppen. Jeder Eintrag hat einen **Schlüssel** (technischer Name, z. B. `gift-card`; steht fest, sobald der Eintrag gespeichert ist) und einen **Namen** (deutsche Beschriftung, jederzeit änderbar).

| Gruppe | Felder je Eintrag | Standard |
|---|---|---|
| Kundenarten | Schlüssel, Name | b2c, b2b-reseller, b2b-pro, b2b-whitelabel |
| Vertriebskanäle | Schlüssel, Name | looxis-de, amazon, fachhaendler, looxis-pro, masterpics |
| Kundengruppen (Reihenfolge = Reihenfolge im Formular) | Schlüssel, Name, Kundenart, Vertriebskanal, Status | die heutigen sechs, „Noch unklar“ ohne Kundenart und Kanal |
| EOCS-Kanalnamen | Name aus EOCS, Vertriebskanal | die heutigen vierzehn Zuordnungen |
| Fallkategorien | Schlüssel, Name | complaint, product-question, order-process-question, quote-request |
| Vorgänge | Schlüssel, Name, Unterscheidungshinweis für die KI (optional) | die heutigen vierzehn mit ihren drei Hinweisen |

Der Status einer Kundengruppe ist **aktiv**, **in Vorbereitung** (neu angelegt, noch nicht wählbar) oder **deaktiviert** (nicht mehr wählbar, Altdaten bleiben lesbar). „Noch unklar“ bleibt als Systemgruppe: Name änderbar, nicht entfernbar und nicht deaktivierbar.

### Was man ändern darf
1. **Immer:** Namen, Unterscheidungshinweise, die Reihenfolge der Kundengruppen, EOCS-Zuordnungen und neue Einträge hinzufügen.
2. **Schlüssel umbenennen: nicht möglich.** Er steht in den Wissensdokumenten und in gespeicherten Analysen; wer einen anderen Schlüssel möchte, legt einen neuen Eintrag an.
3. **Entfernen** eines Kundenart-, Kanal-, Kategorie- oder Vorgangs-Eintrags nur, wenn ihn **kein Wissensdokument** mehr nennt. Sonst sagt die Seite „Wird noch von n Dokumenten verwendet“ und nennt die IDs. Eine Kundengruppe wird nicht entfernt, sondern deaktiviert. Gespeicherte Analysen und Wissenslücken behalten den Schlüssel und zeigen ihn im Zweifel unübersetzt.

### Neue Kundengruppe, neue Kundenart, neuer Kanal: die Prüfliste
Ein Dokument ohne `customer_types`/`sales_channels` gilt für alle, ein Dokument mit Einschränkung nur für die genannten. Damit eine neue Gruppe nicht unbemerkt ohne wichtige Regeln arbeitet:
1. Eine neue Kundengruppe (und eine Gruppe, deren Kundenart oder Kanal neu ist) hat den Status **in Vorbereitung** und erscheint nicht im Formular.
2. Die Seite zeigt zur Gruppe die **Prüfliste**: alle Dokumente, die sie wegen ihrer Einschränkung **nicht** bekommen, mit ID, Titel, Typ, Klasse und ihrer Einschränkung, jeweils mit Link zur Knowledge-Übersicht, geordnet mit den verbindlichen Regeln zuerst. Die Liste ist immer aktuell (nach jeder Änderung an den Dokumenten).
3. Für jedes Dokument gilt: Soll es auch für die neue Gruppe gelten, ergänzt der Autor die Kundenart oder den Kanal im Kopf des Dokuments; dann verschwindet es aus der Liste.
4. Der Admin gibt die Gruppe frei: „Prüfung abgeschlossen, Gruppe freigeben“. Verbindliche Regeln (Klasse `binding`), die für die Gruppe ausgeschlossen bleiben, muss er vorher einzeln bestätigen („bewusst nicht gültig für diese Gruppe“). Die Freigabe wird mit Name, Zeitpunkt und den bestätigten Regeln festgehalten; erst danach ist die Gruppe aktiv.

### Werte für Wissensautoren kopieren
Die Seite bietet einen Kopier-Text mit den erlaubten Werten (Kundenarten, Vertriebskanäle, Kategorien, Vorgänge, jeweils Schlüssel und Name), den man einem KI-Chat beim Schreiben von Dokumenten mitgibt. Er steht auch in der Knowledge-Übersicht neben der ID-Übersicht.

### Prüfung vor dem Speichern
- Schlüssel: Kleinbuchstaben, Ziffern und `-`, 2 bis 40 Zeichen, eindeutig innerhalb der Gruppe; Namen nicht leer, höchstens 80 Zeichen; Hinweise höchstens 300 Zeichen.
- Eine Kundengruppe nennt eine vorhandene Kundenart und einen vorhandenen Kanal (oder keines von beiden nur bei „Noch unklar“); keine zwei Gruppen mit derselben Kombination.
- Ein EOCS-Kanalname ist nicht leer, höchstens 80 Zeichen, eindeutig (ohne Beachtung der Groß-/Kleinschreibung) und verweist auf einen vorhandenen Kanal.
- Ein Entfernen oder Deaktivieren, das gegen die Regeln oben verstößt, wird abgelehnt; es wird nichts gespeichert, die Eingabe bleibt.

## Acceptance Criteria
- [ ] Angenommen ich öffne „Einstellungen > Kundengruppen und Werte“, dann sehe ich die sechs Gruppen mit ihren heutigen Werten; ohne Admin-Recht sind alle Felder schreibgeschützt.
- [ ] Angenommen ich habe nichts geändert, dann verhalten sich Formular, Wissensauswahl, Analyse und Dokumentenprüfung genau wie bisher.
- [ ] Angenommen ich lege eine neue Kundengruppe an, dann hat sie den Status „in Vorbereitung“ und ist im Formular nicht wählbar; die Prüfliste nennt alle Dokumente, die sie nicht bekommt.
- [ ] Angenommen ich ergänze in einem Dokument die neue Kundenart, dann verschwindet es aus der Prüfliste der Gruppe.
- [ ] Angenommen die Prüfliste enthält verbindliche Regeln, wenn ich die Gruppe freigeben will, dann muss ich jede einzeln als „bewusst nicht gültig“ bestätigen; ohne Bestätigung wird nicht freigegeben.
- [ ] Angenommen ich gebe eine Gruppe frei, dann ist sie danach im Formular wählbar, und die Freigabe steht mit Name und Zeitpunkt im Verlauf.
- [ ] Angenommen ich lege einen neuen Vorgang oder eine neue Kategorie an, dann ist er in der Dokumentenprüfung erlaubt, wird der KI als Möglichkeit genannt und im Ergebnis mit seinem Namen angezeigt.
- [ ] Angenommen ein Vorgang, eine Kategorie, eine Kundenart oder ein Kanal wird noch von einem Dokument genannt, wenn ich ihn entferne, dann wird abgelehnt mit den Dokument-IDs.
- [ ] Angenommen ich deaktiviere eine Kundengruppe, dann ist sie im Formular nicht mehr wählbar, alte Analysen zeigen sie weiter mit ihrem Namen.
- [ ] Angenommen ich ordne einen EOCS-Kanalnamen einem Kanal zu, dann schlägt die App für eine Bestellung mit diesem Namen die passende Kundengruppe vor.
- [ ] Angenommen ich öffne die Knowledge-Übersicht oder diese Seite, dann kann ich den Text mit den erlaubten Werten kopieren, und er entspricht dem Stand der Einstellungen.
- [ ] Angenommen ich versuche, einen Schlüssel zu ändern oder „Noch unklar“ zu entfernen, dann geht das nicht.
- [ ] Verlauf, Wiederherstellen, Zurücksetzen auf Standard und der Schutz vor gleichzeitigem Speichern funktionieren wie bei PROJ-27; ein Wiederherstellen, das gegen die Schutzregeln verstößt, wird abgelehnt.
- [ ] Angenommen die Datenbank ist nicht erreichbar oder ein Wert unbrauchbar, dann gelten die Standardwerte.

## Edge Cases
- Zwei neue Gruppen gleichzeitig in Vorbereitung: jede hat ihre eigene Prüfliste und Freigabe.
- Ein Dokument wird nach der Freigabe so eingeschränkt, dass es die Gruppe ausschließt: die Prüfliste zeigt es wieder (ohne die Gruppe zurückzusetzen); die Seite weist darauf hin.
- Eine Kundengruppe mit einer neuen Kundenart, die noch kein Dokument nennt: alle eingeschränkten Dokumente stehen in der Prüfliste.
- Die Kundenart oder der Kanal einer aktiven Gruppe soll entfernt werden: abgelehnt, solange eine Gruppe ihn nutzt.
- Eine deaktivierte Gruppe steht noch im Kundengedächtnis eines Kunden oder in einem Ticket: das Formular schlägt sie nicht mehr vor („Noch unklar“), die Altdaten bleiben.
- Eine Fortsetzung eines Falls, dessen Kundengruppe inzwischen deaktiviert wurde: die Seite sagt, dass die Gruppe nicht mehr wählbar ist, und bietet die Wahl einer anderen an.
- Ein Eintrag mit demselben Namen wie ein vorhandener: erlaubt (der Schlüssel unterscheidet).
- Zwei Admins gleichzeitig: wie bei PROJ-27; eine Freigabe, während die Gruppe geändert wurde, wird abgelehnt.

## Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Neue Kundengruppen starten „in Vorbereitung“ mit Prüfliste und Freigabe | Eine neue Gruppe soll nicht unbemerkt ohne wichtige Regeln arbeiten | 2026-10-17 |
| Verbindliche Regeln müssen vor der Freigabe einzeln bestätigt werden | Das größte Risiko sind Regeln, die für die neue Gruppe still wegfallen | 2026-10-17 |
| Schlüssel stehen fest, Namen und Hinweise sind änderbar | Schlüssel stehen in Dokumenten und Analysen | 2026-10-17 |
| Entfernen nur ohne Verwendung in Dokumenten, Gruppen werden deaktiviert | Dokumente bleiben gültig; Altdaten verlieren nichts | 2026-10-17 |
| „Noch unklar“ ist fest | Die App braucht eine Gruppe ohne Einschränkung | 2026-10-17 |
| Kopier-Text der erlaubten Werte für den KI-Chat der Autoren | Der Leitfaden darf nicht von den Einstellungen abweichen | 2026-10-17 |
| Abrufregeln und veraltete Werte bleiben in der Konfiguration | Eigene fachliche Entscheidung; nicht Teil dieser Seite | 2026-10-17 |

## Tech Design (Solution Architect)

### Backend nötig?
Ja, mit der Datenbank und dem Verzeichnis der Einstellungsgruppen aus PROJ-27. Keine neuen Pakete.

### A) Bausteine der Oberfläche
```
Seite „Einstellungen“ (Übersicht)
+-- Karte „Kundengruppen und Werte“ (neu)

Seite „Einstellungen > Kundengruppen und Werte“
+-- Kopier-Text „Erlaubte Werte für Autoren“
+-- Gruppe „Kundenarten“   (Liste: Schlüssel, Name)
+-- Gruppe „Vertriebskanäle“ (Liste: Schlüssel, Name)
+-- Gruppe „Kundengruppen“ (Karten: Schlüssel, Name, Kundenart, Kanal, Status, verschiebbar)
|   +-- bei „in Vorbereitung“: Prüfliste (verbindliche Regeln zuerst) + Freigabe
+-- Gruppe „EOCS-Kanalnamen“ (Liste: Name → Kanal)
+-- Gruppe „Fallkategorien“ (Liste: Schlüssel, Name)
+-- Gruppe „Vorgänge“ (Liste: Schlüssel, Name, Hinweis)
+-- je Gruppe: Verlauf, „Auf Standard zurücksetzen“; Speichern (nur Admins)
```
Wiederverwendet werden Karte, Hinweis, Knopf, Listen- und Gruppenbausteine aus PROJ-27/49; neu sind „Liste mit Schlüssel und Namen“, die Gruppenkarte und die Prüfliste.

### B) Datenmodell
Keine neuen Tabellen: sechs Gruppen in den Tabellen für Einstellungen und Versionen. Eine Kundengruppe trägt zusätzlich den Status und, nach der Freigabe, wer sie wann freigegeben hat und welche verbindlichen Regeln bewusst ausgeschlossen blieben.

### C) Technische Entscheidungen
- **Einstellungen überlagern die Konfiguration beim Start jeder Anfrage:** Die gespeicherten Werte werden über die Konfiguration von `knowledge` gelegt (nur wo etwas gespeichert ist). Dadurch sehen alle bisherigen Stellen (Prüfung der Dokumente, Auswahl, Analyse, Router, Ansichten, Prüfbefehle auf der Konsole) dieselben Werte ohne Umbau. Bei Datenbank- oder Wertfehler bleibt die Konfiguration unverändert.
- **Prüfliste aus der vorhandenen Dokumentenbibliothek:** Die App fragt für jedes Dokument, ob es für die Gruppe ausgeschlossen wäre (dieselbe Regel wie die Auswahl), liest also nie eine eigene Kopie; sie ist deshalb immer aktuell.
- **Verwendungsprüfung beim Entfernen:** Die Bibliothek liefert, welche Dokumente einen Wert nennen; ohne Treffer ist das Entfernen erlaubt. Für Kundengruppen gibt es kein Entfernen, nur Deaktivieren.
- **Wählbare Gruppen:** Das Formular und die Prüfung der Eingabe kennen nur aktive Gruppen; gespeicherte Analysen lösen auch inaktive auf.
- **Kopier-Text:** Ein einziger Baustein erzeugt den Text aus den Einstellungen; die Knowledge-Übersicht und diese Seite nutzen ihn.
- **Berechtigung** wie bei PROJ-27 über die bestehende Admin-Prüfung.

### D) Abhängigkeiten
Keine neuen Pakete.

## Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Gespeicherte Werte werden beim Start einer Anfrage über die Konfiguration gelegt | Alle bisherigen Stellen sehen die Werte ohne Umbau; Rückfall auf die Konfiguration bei Fehlern | 2026-10-17 |
| Die Prüfliste nutzt dieselbe Ausschlussregel wie die Wissensauswahl | Eine einzige Wahrheit, immer aktuell | 2026-10-17 |
| Verwendungsprüfung über die Dokumentenbibliothek | Entfernen darf kein Dokument ungültig machen | 2026-10-17 |
| Status der Kundengruppe (aktiv, in Vorbereitung, deaktiviert) im Wert der Gruppe | Wählbarkeit und Freigabe stehen im Verlauf der Einstellung | 2026-10-17 |
| Ein Baustein für den Kopier-Text | Seite und Knowledge-Übersicht zeigen dasselbe | 2026-10-17 |

## Open Questions
- [ ] Soll die Seite zusätzlich EOCS-Kanalnamen zeigen, die in Bestellungen vorkamen, aber noch keinem Kanal zugeordnet sind? Vorschlag: später, braucht eine Erfassung der gesehenen Namen.
- [x] Soll der Leitfaden (`docs/KNOWLEDGE_AUTHORING_GUIDE.md`) die festen Werte entfernen und auf den Kopier-Text verweisen? Vorschlag: ja, in dieser Spec.

## Implementation Notes
- **Neu:** `KnowledgeValues` (Standardwerte aus der Konfiguration, Überlagerung der Konfiguration, Verwendung in Dokumenten, Prüfliste, Kopier-Text), `KnowledgeValueRules` (Bereinigen und Prüfen der sechs Gruppen, Schutz der Kundengruppen, Prüfungen über mehrere Gruppen), `ValuesSettingsRequest`, `ReleaseGroupRequest`, Seite `settings/values`, Bausteine `components/settings/records`, Alpine-Komponente `recordList`, sechs Gruppen (`kn_customer_types`, `kn_sales_channels`, `kn_groups`, `kn_order_channels`, `kn_categories`, `kn_actions`), Routen `settings.values*` und `settings.groups.release`.
- **Geändert:** `CustomerGroup` (Status aktiv/in Vorbereitung/deaktiviert), `KnowledgeSelector::selectableGroups()` (nur aktive Gruppen im Formular und in der Prüfung der Eingabe; `customerGroup()` löst weiter alle auf), `AnalysisPanel`, beide Anfragen und `QuestionController` (wählbare Gruppen), `SettingGroup` (Typ `RECORDS`, `normalizeInput()`), `SettingsController` (`values`, `updateValues`, `release`, geschütztes Wiederherstellen und Zurücksetzen), `AppServiceProvider` (Überlagerung beim Start), Knowledge-Übersicht (zweiter Kopier-Text), Leitfaden (Hinweis „maßgeblich sind die aktuellen Werte der App“).
- **Überlagerung:** Beim Start der App (und nach jedem Speichern) werden gespeicherte Werte über `config('knowledge.…')` gelegt, nur wo etwas gespeichert ist; die Dokumentenprüfung, die Auswahl, die Analyse, der Router und die Konsolenbefehle (`knowledge:check` geprüft) sehen dieselben Werte.
- **Schutz:** Neue Gruppen und Gruppen mit geänderter Kundenart/geändertem Kanal sind „in Vorbereitung“ (auch beim Wiederherstellen); die Freigabe verlangt die einzelne Bestätigung jeder ausgeschlossenen verbindlichen Regel. Entfernen von Kundenarten, Kanälen, Kategorien und Vorgängen ist nur ohne Verwendung in Dokumenten (und Gruppen) möglich; Wiederherstellen und Zurücksetzen prüfen genauso.
- **Tests:** `PROJ-50-ValuesSettingsTest` (28).
- **Offen für `/qa`:** Bedienung im Browser (Zeilen, Pfeile, Status, Prüfliste, Freigabe mit Häkchen), 375/768/1440 px, Prüfung mit den echten Dokumenten und einer echten neuen Kundengruppe.
