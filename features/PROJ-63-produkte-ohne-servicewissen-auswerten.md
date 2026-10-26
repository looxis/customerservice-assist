# PROJ-63: Produkte ohne Kundenservice-Wissen auswerten

## Status: Architected
**Created:** 2026-10-26
**Last Updated:** 2026-10-26

## Dependencies
- Requires: PROJ-9 (Fallanalyse per LLM) – Kategorie „Reklamation“ und Ergebnisstruktur
- Requires: PROJ-12 (Feedback und Wissenslücke melden) – Abgrenzung: Regeln und Befugnisse bleiben dort
- Requires: PROJ-38 (Produktinformationen aus ProductForge) – Produkte und ihr Kundenservice-Wissen
- Requires: PROJ-57 (Häufige Produktfragen) – Zählung, Seite und Status werden erweitert

## Hintergrund
Neue Produkte (Beispiel: Erinnerungsbox) bekommen Kundenservice-Wissen erst, wenn echte Fälle auftreten: Ein Kunde meldet etwa, die Bohrung im Deckel für das Scharnier sei ausgeleiert. Heute sieht der Admin davon nur etwas, wenn die KI zufällig eine Wissenslücke nennt und der Mitarbeiter sie meldet. Die Seite „Häufige Produktfragen“ zählt nur Detailfragen, nicht Reklamationen, und zeigt ein Thema erst ab 3 Tickets in 3 Monaten.

## User Stories
- Als **Admin** will ich sehen, zu welchen ProductForge-Produkten Reklamationen eingehen, für die das Kundenservice-Wissen nicht ausreicht, damit ich das Wissen gezielt ergänze.
- Als **Admin** will ich eine Reklamation schon beim ersten Fall in der Liste sehen, damit ich bei einem neuen Produkt nicht auf mehrere Fälle warten muss.
- Als **Admin** will ich zu jedem Eintrag ein Beispiel und die Tickets sehen, damit ich den Fall verstehe, ohne zu raten.
- Als **Admin** will ich einen Eintrag als erledigt oder ignoriert markieren und merken, wenn nach „erledigt“ derselbe Fall erneut auftritt, damit ich sehe, ob das neue Wissen gegriffen hat.
- Als **Mitarbeiter im Kundenservice** will ich keinen zusätzlichen Schritt haben: Die Zählung läuft still mit, ich muss nichts melden.

## Out of Scope
- Produkte, die nur in EOCS stehen, und von Hand eingetippte Produkte ohne Wissen: Sie werden bereits als Wissenslücke gemeldet (PROJ-38, PROJ-45) und bleiben dort.
- Fehlende Regeln, Befugnisse und Abläufe: bleiben im Bereich „Wissenslücken“ (PROJ-12).
- Wissen aus der App heraus schreiben oder einen Entwurf für ProductForge erzeugen (Richtung PROJ-55).
- Automatische Benachrichtigung (Mail, Seitenleiste) bei neuen Einträgen.
- Fälle aus dem Testmodus der Admins (PROJ-28) zählen nicht.
- Auswertung alter Tickets rückwirkend (PROJ-18, zurückgestellt). Gezählt wird ab Einführung.
- Eine eigene neue Seite: Die Auswertung kommt in die bestehende Seite „Häufige Produktfragen“.

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Erkennen und Zählen
- [ ] Angenommen ein Vorschlag hat die Kategorie „Reklamation“ und ein betroffenes Produkt aus ProductForge, wenn die KI angibt, dass produktspezifische Fakten zur Beurteilung fehlten, dann wird ein Fall für dieses Produkt und ein Thema gespeichert.
- [ ] Angenommen die KI konnte die Reklamation mit vorhandenem Produktwissen und den allgemeinen Regeln beurteilen, wenn der Vorschlag gespeichert wird, dann wird kein Fall gezählt.
- [ ] Angenommen dem Produkt fehlt jedes Kundenservice-Wissen, wenn die KI die Reklamation nicht beurteilen kann, dann wird der Fall gezählt. Dasselbe gilt, wenn das Produkt etwas Servicewissen hat, das die Reklamation aber nicht beantwortet.
- [ ] Angenommen der Vorschlag hat eine andere Kategorie als „Reklamation“, wenn die KI eine Detailfrage nennt, dann gilt weiter die bestehende Zählung der Detailfragen (PROJ-57).
- [ ] Angenommen der Fall betrifft eine Variante, wenn er gezählt wird, dann zählt er unter dem Master-Produkt, eine Reklamation zu irgendeiner Größe zählt also einmal für das Produkt.
- [ ] Angenommen für dasselbe Ticket, dasselbe Produkt und dasselbe Thema wird mehrmals ein Vorschlag erstellt, wenn der Fall gespeichert wird, dann zählt er einmal; ein erneuter Vorschlag aktualisiert nur Beispiel und Formulierung.
- [ ] Angenommen die KI nennt für denselben Punkt ein fehlendes Produktwissen und eine Wissenslücke, wenn der Vorschlag gespeichert wird, dann zählt er nur als fehlendes Produktwissen, nicht zusätzlich als Wissenslücke.
- [ ] Angenommen ein fehlendes Produktwissen betrifft eine fehlende Regel, Befugnis oder einen fehlenden Ablauf, wenn der Vorschlag gespeichert wird, dann erscheint das weiter unter „Wissenslücken“ und nicht in der neuen Auswertung.
- [ ] Angenommen das Produkt steht nur in EOCS oder wurde von Hand eingetippt, wenn der Vorschlag gespeichert wird, dann entsteht kein Fall in der neuen Auswertung.
- [ ] Angenommen ein Vorschlag stammt aus dem Testmodus der Admins, wenn er gespeichert wird, dann wird kein Fall gezählt.
- [ ] Angenommen für das Produkt gibt es bereits Themen, wenn die KI ein Thema nennt, dann bekommt sie die bisher verwendeten Stichwörter des Produkts mit, damit ein sinngleiches Thema nicht als neues zählt.

### Anzeige auf der Seite „Häufige Produktfragen“
- [ ] Angenommen es gibt gezählte Reklamationen, wenn der Admin die Seite öffnet, dann erscheinen sie schon ab dem ersten Fall, Detailfragen weiter erst ab 3 Tickets in 3 Monaten.
- [ ] Angenommen die Seite zeigt Einträge, wenn der Admin sie liest, dann zeigt jeder Eintrag Produkt, Thema, Art des Falls („Reklamation“ oder „Detailfrage“), Anzahl der Fälle, Datum des letzten Falls und Status.
- [ ] Angenommen ein Eintrag hat Fälle, wenn der Admin ihn ansieht, dann sieht er einen kurzen Beispielsatz ohne Namen, E-Mail-Adressen und Anschriften und die Ticketnummern als Links zu den Tickets in der App.
- [ ] Angenommen die Seite zeigt beide Arten, wenn sie sortiert wird, dann stehen Reklamationen vor Detailfragen und innerhalb davon die meisten Fälle zuerst.
- [ ] Angenommen das Produkt ist in ProductForge bekannt und die Adresse ist verfügbar, wenn der Admin den Eintrag öffnet, dann gibt es einen Link zum Produkt dort, sonst werden Name und Artikelnummer gezeigt.
- [ ] Angenommen die Seite zeigt Einträge, wenn der Admin nach Art des Falls oder Status filtert, dann zeigt die Liste nur die passenden Einträge.

### Status
- [ ] Angenommen ein Eintrag ist offen, wenn der Admin „Erledigt“ oder „Ignorieren“ wählt, dann ändert sich der Status wie bei den Produktfragen und wird mit Name und Zeitpunkt festgehalten.
- [ ] Angenommen ein Eintrag ist erledigt, wenn danach ein neuer Fall zum selben Produkt und Thema eintrifft, dann wird er wieder offen und zählt neu.
- [ ] Angenommen ein Eintrag ist ignoriert, wenn weitere Fälle eintreffen, dann bleibt er ignoriert, wird aber weitergezählt.
- [ ] Angenommen ein Nutzer ist kein Admin, wenn er Status ändern will, dann ist das nicht möglich; ansehen dürfen alle wie bisher.

## Edge Cases
- Die KI nennt kein Thema oder ein leeres: Der Fall wird nicht gezählt, der Vorschlag bleibt unverändert nutzbar.
- Die KI-Antwort enthält die neue Angabe nicht (älterer gespeicherter Vorschlag, ungültige Struktur): Der Vorschlag funktioniert wie bisher, es wird nichts gezählt.
- Mehrere Produkte im Fall: Die KI ordnet das Thema einem Produkt aus der Liste zu; ist das unklar und nur ein Produkt betroffen, zählt dieses; bei mehreren Produkten ohne Zuordnung wird nicht geraten, der Fall wird ohne Produkt gespeichert und nicht in der Liste gezeigt.
- Das Produkt bekommt später Servicewissen: Bereits gezählte Fälle bleiben sichtbar, der Admin setzt sie auf erledigt.
- Ein Ticket wird wegen des Testmodus zurückgespult: Dessen Fälle zählen nicht mit (wie bei den Produktfragen).
- Der Beispielsatz der KI enthält doch personenbezogene Angaben: Er wird wie bei den Detailfragen bereinigt und gekürzt gespeichert.
- Aufbewahrung: Erledigte Themen werden nach der Frist der Produktfragen gelöscht (bestehende Regel, 12 Monate).
- Viele Themen zu einem Produkt: Die Seite bleibt nutzbar durch Filter nach Produkt (wie heute).

## Technical Requirements (optional)
- Die Zählung läuft beim Speichern des Vorschlags mit und verlängert die Antwortzeit nicht spürbar.
- Keine neuen Aufrufe an den KI-Anbieter: Die Angabe kommt im selben Aufruf wie das übrige Ergebnis.
- Datenschutz: Im Beispielsatz keine Namen, E-Mail-Adressen und Anschriften.
- Prompt-Änderung (Ergebnisstruktur und Regel zur Abgrenzung) über die versionierten Prompts (PROJ-31).

## Open Questions
- [ ] Heißt die Art des Falls in der Oberfläche „Reklamation“ und „Detailfrage“, oder gibt es bessere Wörter für die Mitarbeiter?
- [ ] Adresse zum Produkt in ProductForge: bleibt vorerst offen (2026-10-26). Beispiel der Oberfläche: SKU 11763 liegt unter `…/produkte/137`, die Zahl ist also vermutlich die interne Produkt-ID und nicht die SKU; sie ist in der Anbindung bekannt. Bis dahin zeigt die Seite Name und Artikelnummer ohne Link; die Adresse lässt sich später über die optionale Einstellung ergänzen.
- [ ] Wie zuverlässig trennt die KI „Produktwissen fehlt“ von „Regel fehlt“? Nach den ersten Wochen mit echten Fällen prüfen und den Prompt nachschärfen.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Auswertung in die bestehende Seite „Häufige Produktfragen“ statt eigener Seite | Eine Stelle für Produkte mit Wissensbedarf; Zählung, Status und Filter sind schon da | 2026-10-26 |
| Reklamationen erscheinen schon beim ersten Fall, Detailfragen weiter erst ab 3 Tickets in 3 Monaten | Eine berechtigte Reklamation bei einem neuen Produkt soll sofort sichtbar sein; bei Detailfragen hilft die Schwelle gegen Einzelfälle | 2026-10-26 |
| Ein Fall zählt auch, wenn das Produkt etwas Servicewissen hat, das die Reklamation aber nicht beantwortet | Neue Produkte haben meist nur Pflege- und Warnhinweise; die Lücken zeigen sich erst an echten Fällen | 2026-10-26 |
| Die KI beurteilt „Produktwissen reicht nicht“, nicht eine feste Regel in der App | Ob vorhandenes Wissen eine Reklamation beantwortet, lässt sich nicht aus Daten ablesen | 2026-10-26 |
| Produktspezifische Fakten gehören in diese Liste, Regeln und Befugnisse bleiben in den „Wissenslücken“; nie doppelt gezählt | Zwei klar getrennte Aufgaben: Produktdaten in ProductForge ergänzen, Regeln in `knowledge/` | 2026-10-26 |
| Produkte nur in EOCS und eingetippte Produkte bleiben außerhalb | Sie werden schon automatisch gemeldet; zuerst muss das Produkt in ProductForge angelegt werden | 2026-10-26 |
| Zählung unter dem Master-Produkt, Testläufe zählen nicht | Wie bei PROJ-57: eine Reklamation zu irgendeiner Größe zählt einmal für das Produkt | 2026-10-26 |
| Beispielsatz und Ticket-Links je Eintrag | Der Admin versteht den Fall, ohne das Ticket zu suchen | 2026-10-26 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|
| Bestehende Ablage und Seite der Produktfragen erweitern, Spalte „Art“ ergänzen | Zählung, Status, Aufbewahrung und Seite sind vorhanden und getestet; keine zweite Ablage für denselben Zweck | 2026-10-26 |
| Eine Liste im KI-Ergebnis mit „Art“ je Eintrag statt zweiter Liste | Die KI kann einen Punkt nur einmal nennen; keine Doppelzählung | 2026-10-26 |
| Eindeutigkeit je Ticket, Produkt, Thema und Art; Status weiter je Produkt und Thema | Ein Thema soll nicht in zwei Status zerfallen | 2026-10-26 |
| Kategorie „Reklamation“ und Herkunft ProductForge prüft die App aus gespeicherten Daten | Nur das Urteil „Wissen reicht nicht“ ist KI-Sache | 2026-10-26 |
| Kein zusätzlicher KI-Aufruf | Keine Mehrkosten, keine längere Wartezeit | 2026-10-26 |
| Link zum Produkt in ProductForge über optionale Einstellung | Die Adresse ist in der App nicht bekannt; ohne sie bleiben Name und Artikelnummer | 2026-10-26 |

---

## Tech Design (Solution Architect)

### Backend nötig?
Ja, aber klein: Es gibt weder eine neue Seite noch einen neuen Aufruf an den KI-Anbieter. PROJ-63 erweitert drei vorhandene Teile: das Ergebnis der Analyse (eine zusätzliche Angabe der KI), die Zählung der Produktfragen (PROJ-57) und die Seite „Häufige Produktfragen“. Keine Anmeldung nötig (wie im ganzen MVP).

### A) Aufbau der Seite (bestehende Seite „Häufige Produktfragen“)
```
Häufige Produktfragen
+-- Reiter nach Status: Offen | Ignoriert | Erledigt (wie heute)
+-- Filter: Produkt (wie heute) + NEU: Art des Falls (Alle | Reklamation | Detailfrage)
+-- Liste der Themen, Reklamationen zuerst
|   +-- Eintrag (je Produkt + Thema)
|       +-- Produktname (Link zu ProductForge, wenn Adresse bekannt) + Marke „Reklamation“ / „Detailfrage“
|       +-- Thema, Anzahl der Fälle, Datum des letzten Falls
|       +-- Beispielsätze der Kunden (ohne Personendaten) + Ticketnummern als Links zur App
|       +-- Knöpfe: Erledigt | Ignorieren | Text für KI-Chat kopieren (gibt es schon)
+-- Leerzustand: Hinweis, dass bisher nichts gezählt wurde
```
Bestehende Bausteine werden wiederverwendet (Reiter, Eintragskarte, Statusknöpfe, Kopieren für den KI-Chat). Neu ist nur die Marke und der Filter nach Art des Falls.

### B) Datenmodell (in Alltagssprache)
Die vorhandene Ablage der Produktfragen wird erweitert, es entsteht keine neue Tabelle.

Jeder gezählte Fall hat wie bisher: Ticketnummer, Produkt (Schlüssel und Name), Thema, Beispielsatz (verschlüsselt), Kundengruppe, Kennzeichen „Testlauf“.
**Neu:** die **Art des Falls** – „Detailfrage“ (bisheriger Stand, alle vorhandenen Fälle) oder „Reklamation“.

- Pro Ticket, Produkt, Thema **und Art** höchstens ein Fall (ein erneuter Vorschlag aktualisiert nur den Satz).
- Der Status „offen / erledigt / ignoriert“ gilt weiter je Produkt und Thema. Ein Thema, das als Detailfrage und als Reklamation auftritt, hat also einen gemeinsamen Status.
- Aufbewahrung und Löschen erledigter Themen bleiben wie bei PROJ-57 (12 Monate).

### C) Ergebnis der KI
Die vorhandene Liste „Detailfragen“ im Ergebnis bekommt je Eintrag eine **Art** (Detailfrage oder Reklamation). Es bleibt eine Liste, damit die KI denselben Punkt nicht zweimal nennen kann. Gespeichert wird dieselbe Ergebnisstruktur wie bisher, ältere Vorschläge ohne diese Angabe gelten als Detailfragen.

Die App prüft nach der Antwort der KI, ohne der KI zu vertrauen:
- „Reklamation“ zählt nur, wenn die Kategorie des Vorschlags „Reklamation“ ist und das Produkt aus ProductForge stammt (nicht EOCS, nicht eingetippt).
- Steht dasselbe Thema auch bei den Wissenslücken, zählt der Fall nicht als Produktfall (die vorhandene Regel gilt auch für Reklamationen).
- Zuordnung zum Produkt wie bisher: ein betroffenes Produkt wird automatisch genommen, bei mehreren muss die KI das Produkt aus der Liste nennen, sonst wird nicht geraten.
- Varianten zählen unter dem Master-Produkt.
- Testläufe zählen nicht.

### D) Anzeige-Regel
- Ein Thema mit mindestens einem Fall der Art „Reklamation“ steht ab dem ersten Fall in der offenen Liste.
- Ein Thema, das nur aus Detailfragen besteht, erscheint weiter erst ab 3 Tickets in 3 Monaten.
- Sortierung: Themen mit Reklamationen zuerst, dann nach Anzahl der Fälle in den letzten 3 Monaten, dann nach dem letzten Fall.
- Nach „Erledigt“ zählt das Thema bei einem neuen Fall neu und ist wieder offen (wie bisher).

### E) Prompt (versioniert, PROJ-31)
Die Regel zu Detailfragen in den Prompts bekommt einen zweiten Teil: Bei einer Reklamation, die sich ohne produktspezifische Fakten nicht beurteilen lässt (Bauweise, normales Verhalten, übliche Toleranz, Schadensursache), nennt die KI einen Eintrag der Art „Reklamation“ mit Stichwort und Satz. Fehlt eine **Regel, Befugnis oder ein Ablauf**, bleibt es eine Wissenslücke. Die bisher verwendeten Stichwörter des Produkts bekommt die KI wie heute mit, damit ein sinngleiches Thema nicht neu zählt.

### F) Tech-Entscheidungen (begründet für das Produktteam)
- **Erweitern statt neu bauen:** Die Zählung, der Status und die Seite für Produktfragen gibt es und sie sind getestet. Eine zweite, fast gleiche Ablage würde Doppelarbeit und zwei Listen für denselben Zweck bedeuten.
- **Eine Liste im KI-Ergebnis:** Die KI kann einen Punkt nur einmal nennen. Zwei getrennte Listen würden dieselbe Sache doppelt zählen lassen.
- **Prüfung in der App:** Ob eine Reklamation vorliegt und ob das Produkt aus ProductForge kommt, entscheidet die App aus gespeicherten Daten. Nur „das Produktwissen reicht nicht“ ist das Urteil der KI.
- **Kein zusätzlicher KI-Aufruf:** Die Angabe kommt im selben Aufruf mit, es entstehen keine Mehrkosten und keine längere Wartezeit.
- **Link zu ProductForge nur, wenn die Adresse eingestellt ist:** Die Adresse wird als optionale Einstellung hinterlegt; ohne sie zeigt die Seite Name und Artikelnummer (bisher ist die Adresse in der App nicht bekannt, siehe Offene Fragen).

### G) Dependencies
Keine neuen Pakete.

### H) Geänderte und neue Teile (Überblick für die Umsetzung)
- Ergebnisstruktur der KI und Prüfung des Ergebnisses (Art je Eintrag)
- Zählung der Produktfragen (Art speichern, Reklamation nur mit Kategorie und ProductForge-Produkt)
- Abruf der Themen für die Seite (Sichtbarkeit ab dem ersten Fall, Sortierung, Filter)
- Seite „Häufige Produktfragen“ (Marke, Filter, Ticket-Links, Produktlink)
- Prompts für Analyse und Produktteil
- Eine Änderung an der Ablage (neue Spalte „Art“ mit Voreinstellung „Detailfrage“, angepasste Eindeutigkeit)
- Tests: Zählen (alle Bedingungen), Anzeige ab dem ersten Fall, Abgrenzung zu den Wissenslücken, Status-Neustart nach „Erledigt“

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
