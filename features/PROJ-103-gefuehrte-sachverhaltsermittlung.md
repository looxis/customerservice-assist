# PROJ-103: Sachverhaltsermittlung

## Status: In Progress
**Created:** 2026-11-27
**Last Updated:** 2026-11-27

## Anlass und Ziel

Ticket 2140698 (2026-11-27): Ein Kunde reklamiert bei einer Zaubertasse „abgeschnittene Köpfe“ und ein nicht lesbares Datum. Tatsächlich war die Tasse oben und unten noch nicht durchgewärmt. Ein erfahrener Mitarbeiter sieht das auf dem Foto, eine Aushilfe nicht, und sie kann es deshalb weder im Freitext noch im Reklamationsformular angeben.

Die App kennt bisher nur den Weg „der Mitarbeiter kennt den Sachverhalt, die KI kennt die Regeln und formuliert“. Für „der Mitarbeiter weiß nicht, was zu prüfen ist“ gibt es keinen Weg. Welche Angaben zählen, steht im Wissen, aber das Wissen kommt erst nach den Angaben.

PROJ-103 gibt dem Wissen einen zweiten Adressaten: den Mitarbeiter, vor der Analyse. Die App wird damit vom Antwortassistenten zum Bearbeitungsassistenten mit Antwortassistent. Grundsatz: **erst verstehen und prüfen, dann entscheiden und antworten.**

Die Stufe 1 (besserer Wissensabruf, überarbeitetes Wissen zur Zaubertasse) ist seit 2026-11-27 umgesetzt, siehe Nachtrag in `features/archive/PROJ-36-gezielter-wissensabruf.md`. Sie ist nicht Teil dieser Spec.

## Die drei Funktionen im Bereich „Ticket bearbeiten“

Die Sachverhaltsermittlung ist eine optionale Hilfe oberhalb der Bearbeitungswege und steht beiden Wegen zur Verfügung. Bearbeitet wird entweder geführt (mit einem Formular) oder direkt; das ist die bisherige Formularwahl, nur anders dargestellt.

| Funktion | Beantwortet | Icon (Lucide) | Stand |
|---|---|---|---|
| **Sachverhaltsermittlung** | Welche Fallmuster kommen infrage? Was kann ich beobachten? Was ist schon bekannt? Was muss ich prüfen? | `list-checks` | neu, optional, eingeklappt, oberhalb der Bearbeitungswege |
| **Geführte Bearbeitung** | Welche Entscheidung, welche Lösung, welche Maßnahme? | `list-todo` | die kategoriespezifischen Formulare, unverändert, immer sichtbar; ohne eigene Beschriftung „Formular“ |
| **Direkte Analyse** | Analyse und Antwortvorschlag ohne geführtes Formular | `zap` | bisher Formularoption „Freitext“ (technischer Wert `frei` unverändert), eigener kleiner Bereich unter der geführten Bearbeitung, mit dem optionalen Feld „Zusätzliche Informationen / eigene Einschätzung“, gleich ob es ausgefüllt wird oder leer bleibt |

Beide Bearbeitungswege münden in dieselbe Analyse und dieselbe Antworterstellung.

**Korrektur 2026-11-27 (nach der ersten Umsetzung):** „Direkte Analyse“ ist keine übergeordnete Zwischenüberschrift über den anderen Funktionen, sondern die umbenannte Formularoption „Freitext“. Die Zwischenüberschrift mit Blitz-Icon entfällt; Formularwahl, technische Werte, Validierung und Analyse bleiben unverändert.

## Begriffe

- **Fallmuster:** ein Wissensdokument, das zusätzlich ein Erkennungsmerkmal trägt. Es beschreibt eine wiederkehrende Lage aus Sicht eines unerfahrenen Mitarbeiters.
- **Erkennungsmerkmal:** ein bis zwei Sätze, was man beobachten kann, ohne die Ursache zu kennen. Beispiel: „Teile des Motivs fehlen am oberen oder unteren Rand; auf dem Foto sind dort noch dunkle Bereiche zu sehen.“ Nicht: „Thermoeffekt funktioniert nicht ordnungsgemäß.“
- **Prüfkriterium:** eine kurze Frage zum Fallmuster, Antwort „ja“, „nein“ oder „nicht beurteilbar“. Beispiel: „Ist die Tasse auf dem Foto bis knapp unter den Rand gefüllt?“
- **Prüfung durch den Kunden:** Kennzeichnung an einem Fallmuster, dass das Wissen eine definierte Prüfung für den Kunden enthält (Beispiele: Funktionstest der Zaubertasse nach PROCESS-004, erneuter Upload, Test mit einem anderen Browser). Sie unterscheidet sich von der bloßen Bitte um ein Foto oder um weitere Informationen.
- **Übernommene Prüfung:** eine Prüfung durch den Kunden, die der Mitarbeiter mit „übernehmen“ ausdrücklich als nächsten Schritt festgelegt hat. Erst sie ist eine Entscheidung des Mitarbeiters; die bloße Auswahl eines Fallmusters ist es nicht.

## Zuordnung Ticketkategorie → Wissenskategorie

Alle elf Ticketkategorien sind zugeordnet. Fallmuster ohne Kategorie erscheinen bei jeder Ticketkategorie.

| Ticketkategorie | Fallmuster der Wissenskategorie |
|---|---|
| `reklamation` | `complaint` |
| `produkt-frage` | `product-question` |
| `wismo` | `order-process-question` |
| `bestellung` | `order-process-question` |
| `storno` | `order-process-question` |
| `adressaenderung` | `order-process-question` |
| `zahlung` | `order-process-question` |
| `technischer-support` | `order-process-question` (Shop-Login, Konfigurator und Upload gehören zum Bestellablauf) |
| `angebotsanfrage` | `quote-request` |
| `sonstiges` | alle Wissenskategorien |
| `spam` | keine; nur Fallmuster ohne Kategorie |

Bewusst in Kauf genommen: Über die gemeinsame Kategorie `order-process-question` können z. B. bei einem Stornowunsch auch Fallmuster zu Zahlungsproblemen erscheinen. Eine feinere Einteilung wird nach praktischer Erfahrung beurteilt.

## Dependencies
- Requires: PROJ-36, PROJ-37 (Wissensabruf, Router) – das gewählte Fallmuster erzwingt den Abruf seines Wissens
- Requires: PROJ-4 (Geltungsbereich) – welche Fallmuster für Produkt, Kundengruppe und Kanal gelten
- Requires: PROJ-39, PROJ-58 (Entscheidungsfrage und Fortsetzung) – Klärung nach der Analyse
- Requires: PROJ-90, PROJ-86, PROJ-100, PROJ-101, PROJ-102, PROJ-93, PROJ-98 (geführte Formulare) – werden zur „Geführten Bearbeitung“
- Requires: PROJ-80 (Einordnung des Tickets) – Kategorie, wenn kein Formular sie vorgibt
- Requires: PROJ-2, PROJ-3 (Knowledge-Kit, Prüfung) – neue Frontmatter-Felder
- Requires: PROJ-74 (Einführungsmodus) – Erklärtexte nur dort
- Vorbereitet für: PROJ-21 (Bildanalyse) als weitere Informationsquelle
- Vorbereitet für: Seite „Frage stellen“ (PROJ-40); die Bausteine sollen dort später ohne zweite Umsetzung nutzbar sein

## User Stories
- Als Aushilfe möchte ich zu einem Ticket sehen, welche bekannten Fallmuster es für dieses Produkt und diese Art von Anliegen gibt und woran ich sie erkenne, damit ich weiß, worauf ich achten muss, ohne das Fachwissen schon zu haben.
- Als Aushilfe möchte ich die Prüffragen eines Fallmusters per Klick beantworten oder als „nicht beurteilbar“ markieren, damit meine Beobachtungen in den Antwortvorschlag eingehen, ohne dass ich sie selbst formulieren muss.
- Als Stammkraft möchte ich wie bisher ohne zusätzlichen Klick einen Antwortvorschlag erstellen, damit die neue Funktion meinen eingespielten Ablauf nicht verlangsamt.
- Als Mitarbeiter möchte ich nach einem unbefriedigenden Vorschlag in die Sachverhaltsermittlung wechseln, ohne meine Angaben neu einzugeben, damit ich den Fall gezielt nachschärfen kann.
- Als Mitarbeiter möchte ich bei der geführten Bearbeitung nicht noch einmal eintippen, was ich in der Sachverhaltsermittlung schon geklärt habe, und die Anleitung für eine Prüfung durch den Kunden aus dem Wissen bekommen.
- Als Autor des Wissens möchte ich ein neues Fallmuster allein durch Pflege einer Wissensdatei bereitstellen, für jede Ticketkategorie, ohne dass dafür etwas programmiert werden muss.
- Als Geschäftsführung möchte ich, dass ein von einer Aushilfe falsch gewähltes Fallmuster nicht zu einer falschen Zusage oder Ablehnung führt.

## Out of Scope
- Umfassende Neugestaltung der Benutzeroberfläche (visuelle Hierarchie, Farben, eingeklappte Teilbereiche überall): eigenes späteres Projekt. PROJ-103 ändert nur den bisherigen Bereich „Vorschlag erstellen“.
- Bildanalyse von Kundenfotos (PROJ-21). Sie soll sich später einfügen (Fallmuster vorschlagen, Prüfkriterien als „KI-Beobachtung“ vorbelegen), ersetzt die Sachverhaltsermittlung aber nicht.
- Universelle Workflow- oder Entscheidungsbaum-Engine, grafischer Editor für Fallmuster.
- Zweite, unabhängige Analyse-Pipeline.
- Neue oder neu angebundene Schnittstellen; vorab abgefragter Sendungsverlauf oder Lettermint-Status beim Öffnen der Ticketseite.
- Änderungen an Logik, Feldern, Folgefragen oder Validierung der geführten Formulare, mit einer Ausnahme (Pflicht-Freitext, siehe Akzeptanzkriterien).
- Automatische Vorbelegung weiterer Formularfelder aus Prüfkriterien (nur vorbereitet).
- Überarbeitung aller Wissensdokumente; die erste Fassung bringt sieben Fallmuster.
- Auswertung, welche Fallmuster wie oft gewählt werden.
- Automatisches Nachziehen von `related_knowledge` in der direkten Analyse (bleibt wie in Stufe 1 entschieden).
- Fallmuster auf der Seite „Frage stellen“ (PROJ-40): nicht Teil von PROJ-103; die Seite behält Bezeichnung und Aufbau. Die Bausteine werden so angelegt, dass sie dort später ohne zweite Umsetzung nutzbar sind.
- Feinere Kategorien für Fallmuster als die Zuordnungstabelle (später nach praktischer Erfahrung).

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Bezeichnungen und Aufbau
- [ ] Angenommen ein Ticket ist geöffnet, wenn der Mitarbeiter den bisherigen Bereich „Vorschlag erstellen“ sieht, dann heißt er „Ticket bearbeiten“, auch in der eingeklappten Kopfzeile nach einem Vorschlag.
- [ ] Angenommen der Bereich ist offen, wenn der Mitarbeiter ihn von oben nach unten liest, dann stehen dort in dieser Reihenfolge: gemeinsame Angaben (zusammengehörige Tickets, Verkaufskanal, Kundengruppe, Produkt), eingeklappt „Sachverhaltsermittlung“ mit Checklisten-Icon, abgegrenzt „Geführte Bearbeitung“ mit Aufgabenlisten-Icon, getrennt davon (Trenner „oder“) ein eigener kleiner Bereich „Direkte Analyse“ mit Blitz-Icon, die Knöpfe. Eine übergeordnete Zwischenüberschrift „Direkte Analyse“ gibt es nicht.
- [ ] Angenommen der Bereich ist offen, wenn der Mitarbeiter den Knopf zum Erstellen sucht, dann heißt er „Antwortvorschlag erstellen“; die Ladeanzeige nennt denselben Begriff.
- [ ] Angenommen ein Ticket mit Formularwahl, wenn der Mitarbeiter den Bereich „Geführte Bearbeitung“ ansieht, dann enthält er die kategoriespezifischen Formulare zur Wahl (ohne eigene Beschriftung „Formular“) und die Felder des gewählten Formulars.
- [ ] Angenommen ein Ticket mit Formularwahl, wenn der Mitarbeiter „Direkte Analyse“ wählt (bisher „Freitext“, Wert `frei`), dann steht diese Wahl im eigenen Bereich unter der geführten Bearbeitung, und darin erscheint das optionale Feld „Zusätzliche Informationen / eigene Einschätzung“ wie bisher beim Freitext.
- [ ] Angenommen ein Ticket ohne Formularwahl, dann zeigt der Bereich „Direkte Analyse“ das optionale Feld ohne Auswahlknopf.
- [ ] Angenommen die Seite „Frage stellen“, wenn sie geöffnet wird, dann sind ihre Bezeichnungen unverändert.

### Bestehende Arbeitsweise bleibt
- [ ] Angenommen ein Mitarbeiter öffnet die Sachverhaltsermittlung nicht, wenn er wie bisher ein Formular ausfüllt oder die direkte Analyse (bisher „Freitext“) nutzt und den Antwortvorschlag erstellt, dann braucht er keinen zusätzlichen Klick und keine zusätzliche Angabe, und an die KI geht derselbe Eingang wie vor PROJ-103.
- [ ] Angenommen eines der sieben geführten Formulare ist gewählt, wenn der Mitarbeiter es ausfüllt, dann verhalten sich alle Felder, Folgefragen und Fehlermeldungen wie bisher.
- [ ] Angenommen „Direkt antworten“ oder „Ticket ohne Antwort schließen“, wenn der Mitarbeiter sie nutzt, dann funktionieren sie unverändert.

### Fallmuster anzeigen
- [ ] Angenommen Produkt, Kundengruppe und Kanal sind gewählt, wenn die Seite geladen ist, dann zeigt die eingeklappte Zeile der Sachverhaltsermittlung, wie viele Fallmuster es zum Produkt und wie viele allgemeine es gibt, ohne dass dafür die KI aufgerufen wird.
- [ ] Angenommen es gibt im Geltungsbereich kein Fallmuster, wenn die Seite geladen ist, dann steht die Zeile ausgegraut mit „keine Fallmuster hinterlegt“ an derselben Stelle.
- [ ] Angenommen der Mitarbeiter klappt den Bereich auf, dann sieht er zuerst die Fallmuster zum gewählten Produkt, darunter die produktunabhängigen, je mit Titel und Erkennungsmerkmal.
- [ ] Angenommen ein Fallmuster gilt laut Wissen nicht für die gewählte Kundengruppe oder den Kanal, wenn die Liste erscheint, dann ist es nicht wählbar.
- [ ] Angenommen ein geführtes Formular mit Kategorie ist gewählt (z. B. „Aktualisierte WISMO-Infos“), wenn die Liste erscheint, dann zeigt sie die Fallmuster der zugeordneten Wissenskategorie und die ohne Kategorie; bei der direkten Analyse, „Neuversand“ und „Diskussion beenden“ gilt die Einordnung des Tickets.
- [ ] Angenommen ein Ticket jeder der elf Kategorien, wenn die Liste erscheint, dann folgt sie der Zuordnungstabelle; bei „Sonstiges“ erscheinen alle Fallmuster, bei „Spam“ nur die ohne Kategorie, bei einem Ticket ohne Einordnung ebenfalls alle.
- [ ] Angenommen der Mitarbeiter wechselt das Formular, das Produkt, den Kanal oder die Kundengruppe, dann passt sich die Liste an; ein bereits gewähltes Fallmuster, das nicht mehr gilt, wird sichtbar abgewählt mit Angabe des Grunds.
- [ ] Angenommen kein Produkt ist gewählt oder der Fall hat keinen Produktbezug, wenn der Bereich aufgeklappt wird, dann zeigt er die produktunabhängigen Fallmuster.
- [ ] Angenommen der Bereich ist offen, wenn noch keine Frage der geführten Bearbeitung beantwortet ist, dann lässt sich die Sachverhaltsermittlung trotzdem vollständig nutzen.

### Fallmuster wählen und Prüfkriterien beantworten
- [ ] Angenommen der Bereich ist aufgeklappt, wenn der Mitarbeiter ein Fallmuster anhakt, dann erscheinen dessen Prüfkriterien, jeweils mit „ja“, „nein“, „nicht beurteilbar“; keine Antwort ist Pflicht.
- [ ] Angenommen mehrere Fallmuster passen, wenn der Mitarbeiter sie anhakt, dann bleiben alle gewählt.
- [ ] Angenommen keines passt, wenn der Mitarbeiter „Kein Fallmuster passt“ wählt, dann geht das als Angabe in die Analyse und erzeugt keine Wissenslücken-Meldung.
- [ ] Angenommen ein Prüfkriterium bezieht sich auf einen Wert, den die App schon kennt (z. B. versandt, Tage seit Versand, Zustellung, Anhang vorhanden), und der Wert ist eindeutig dem Sachverhalt zugeordnet, wenn das Fallmuster gewählt wird, dann steht dort „bereits bekannt“ mit Wert und Quelle statt einer Frage.
- [ ] Angenommen dieser Wert ist nicht verfügbar (z. B. keine Bestellung geladen, EOCS nicht erreichbar), dann bleibt das Kriterium als Frage stehen mit dem Hinweis, dass die Information fehlt; es gilt nie stillschweigend als beantwortet.
- [ ] Angenommen das Ticket betrifft mehrere Bestellungen, Positionen oder Sendungen mit unterschiedlichem Stand, oder unklar ist, auf welche sich das Anliegen bezieht, wenn das Fallmuster gewählt wird, dann wird das Kriterium nicht automatisch beantwortet; die Frage bleibt stehen, die vorhandenen Werte werden als Hinweis mit ihrem Bezug angezeigt.
- [ ] Angenommen ein Systemwert widerspricht einem anderen Systemwert oder ist erkennbar veraltet, dann gilt er nicht als bestätigte Antwort; die Frage bleibt stehen.
- [ ] Angenommen ein Prüfkriterium betrifft eine Information auf Ticketebene (z. B. ob die aktuelle Kundennachricht Fotos enthält), dann braucht es keinen Bestellbezug und wird beantwortet, sobald der Wert bekannt ist, auch bei mehreren Bestellungen.
- [ ] Angenommen ein Kriterium betrifft Informationen, die erst bei der Analyse abgefragt werden (Sendungsverlauf, Benachrichtigungen), dann steht dort „wird bei der Analyse automatisch geprüft“.

### Übergabe an die Analyse
- [ ] Angenommen ein Fallmuster ist gewählt, wenn der Antwortvorschlag erstellt wird, dann ist das Wissen dieses Fallmusters und der dort genannten verwandten Dokumente (eine Ebene) im Abruf enthalten, und die Quellen nennen als Grund die Auswahl in der Sachverhaltsermittlung.
- [ ] Angenommen ein verwandtes Dokument eines gewählten Fallmusters gilt nicht für die gewählte Kundengruppe, den Kanal oder das Produkt, wenn der Antwortvorschlag erstellt wird, dann wird es nicht abgerufen; die Verknüpfung allein macht es nicht anwendbar.
- [ ] Angenommen ein verwandtes Dokument hat eine Bedingung (`when`), die nicht erfüllt ist, oder ist abgelaufen, dann wird es nicht abgerufen.
- [ ] Angenommen gewählte Fallmuster und verwandte Dokumente überschreiten mit dem übrigen Pflichtwissen den verfügbaren Umfang, wenn der Antwortvorschlag erstellt wird, dann greifen die bestehenden Größengrenzen und die bestehende Meldung zum Wissensumfang.
- [ ] Angenommen Fallmuster und Antworten liegen vor, wenn der Antwortvorschlag erstellt wird, dann erhält die KI sie getrennt von den geprüften Fakten: Fallmuster als in Betracht gezogene Hypothese, Antworten als Beobachtung des Mitarbeiters, „nicht beurteilbar“ und Unbeantwortetes als offen.
- [ ] Angenommen der Mitarbeiter hat ein Fallmuster gewählt, das dem Ticket oder den Systemdaten widerspricht, wenn der Vorschlag erstellt wird, dann benennt der Vorschlag den Widerspruch und folgt dem Fallmuster nicht blind.
- [ ] Angenommen ein Fallmuster ist gewählt, aber keine Beobachtung bestätigt die Ursache, wenn der Vorschlag erstellt wird, dann stellt er die Ursache nicht als festgestellt dar.
- [ ] Angenommen ein Vorschlag wurde mit Sachverhaltsermittlung erstellt, wenn der Mitarbeiter das Ergebnis ansieht, dann sind gewählte Fallmuster und Beobachtungen dort mit Name und Zeit nachlesbar, getrennt von den Systemdaten.
- [ ] Angenommen die KI stellt nach der Analyse eine Entscheidungsfrage, dann fragt sie nichts erneut, was in der Sachverhaltsermittlung schon beantwortet wurde.

### Zusammenspiel mit der geführten Bearbeitung
- [ ] Angenommen ein gewähltes Fallmuster kennzeichnet eine Prüfung durch den Kunden, wenn der Mitarbeiter zur geführten Bearbeitung kommt, dann steht dort ein Hinweis mit dem Namen der Prüfung und der passenden Wahl im Formular sowie ein Knopf „übernehmen“.
- [ ] Angenommen mehrere gewählte Fallmuster kennzeichnen unterschiedliche Prüfungen durch den Kunden, dann steht jede Prüfung mit eigenem Knopf „übernehmen“ da; der Mitarbeiter kann eine, mehrere oder keine übernehmen, und jede übernommene lässt sich wieder zurücknehmen.
- [ ] Angenommen der Mitarbeiter klickt „übernehmen“, dann ist die Prüfung als gewünschter nächster Schritt festgehalten und im Hinweis als übernommen erkennbar.
- [ ] Angenommen im Formular ist noch keine Bewertung gewählt, wenn der Mitarbeiter „übernehmen“ klickt, dann wird zusätzlich die passende Option gesetzt (Reklamation: „Unsicher, weitere Informationen nötig“; Technischer Support: „Unklar, bitte weitere Informationen erfragen“).
- [ ] Angenommen im Formular ist bereits eine andere Option gewählt, wenn der Mitarbeiter „übernehmen“ klickt, dann bleibt die gewählte Option unverändert, und der Hinweis sagt, dass die Prüfung zur gewählten Option nicht passt.
- [ ] Angenommen ein Fallmuster ist gewählt, aber keine Prüfung übernommen, dann wird im Formular nichts gesetzt, anerkannt, abgelehnt oder sonst entschieden.
- [ ] Angenommen eine Prüfung ist übernommen, wenn der Mitarbeiter danach Produkt, Kundengruppe, Verkaufskanal, Formular (Kategorie) oder die Fallmusterauswahl so ändert, dass die Prüfung nicht mehr gilt, dann wird die Übernahme sichtbar entfernt (mit Grund), und das Pflichtfeld „Nötige Informationen“ ist wieder Pflicht.
- [ ] Angenommen im Reklamationsformular ist „Unsicher, weitere Informationen nötig“ gewählt, mindestens eine Prüfung ist übernommen und ihre Anleitung liegt im anwendbaren, abgerufenen Wissen vor, wenn das Pflichtfeld „Nötige Informationen“ leer bleibt, dann wird das Formular angenommen, und der Entwurf enthält die Anleitung aus dem Wissen.
- [ ] Angenommen im Formular „Technischer Support“ ist „Unklar, bitte weitere Informationen erfragen“ gewählt und eine Prüfung ist übernommen, wenn das Feld „Welche Informationen werden benötigt?“ leer bleibt, dann enthält der Entwurf die Anleitung aus dem Wissen; das Feld war und bleibt optional.
- [ ] Angenommen ein Fallmuster mit Prüfung durch den Kunden ist nur gewählt, aber die Prüfung nicht übernommen, wenn das Pflicht-Freitextfeld leer ist, dann erscheint die bisherige Pflichtfeld-Meldung.
- [ ] Angenommen eine Prüfung ist übernommen, aber das Dokument mit ihrer Anleitung gilt nicht für den Fall (anderer Geltungsbereich) oder wird nicht abgerufen, wenn das Pflicht-Freitextfeld leer ist, dann erscheint die Pflichtfeld-Meldung mit dem Hinweis, dass die Anleitung im Wissen fehlt.
- [ ] Angenommen der Mitarbeiter hat zusätzlich Text in das Freitextfeld geschrieben, dann gehen Text und übernommene Prüfung beide an die KI; der Text des Mitarbeiters hat Vorrang.
- [ ] Angenommen ein Formularfeld ist heute optional, dann bleibt es optional; andere Pflichtfelder bleiben Pflicht.
- [ ] Angenommen der Mitarbeiter entscheidet im Formular anders, als Fallmuster oder Beobachtungen nahelegen (z. B. „berechtigt“ trotz offener Prüfung), wenn der Vorschlag erstellt wird, dann folgt der Vorschlag der Entscheidung des Mitarbeiters und weist auf den Widerspruch hin.
- [ ] Angenommen eine Entscheidung oder Angabe des Mitarbeiters weicht von einer allgemeinen Regel im Wissen ab, wenn der Vorschlag erstellt wird, dann ersetzt die KI die Entscheidung nicht durch die Regel; sie macht die Abweichung sichtbar.

### Maßnahme „Kunde um Prüfung bitten“
- [ ] Angenommen die Maßnahmenliste, dann gibt es eine eigene Maßnahme „Kunde um Prüfung bitten“ (`customer-check`), getrennt von „Foto anfordern“.
- [ ] Angenommen der Vorschlag bittet den Kunden um eine definierte Prüfung (Funktionstest, erneuter Upload, anderer Browser), dann trägt er die Maßnahme `customer-check`; soll das Ergebnis per Foto belegt werden, kann zusätzlich `photo-request` stehen.
- [ ] Angenommen ein Autor bindet Wissen mit `actions` an `customer-check`, dann prüft `knowledge:check` die Maßnahme als gültig, und Arbeitsabläufe können sie wie jede andere Maßnahme nutzen.

### Wechsel nach einer Analyse
- [ ] Angenommen ein Vorschlag liegt vor, wenn der Mitarbeiter am Ergebnis „Sachverhaltsermittlung“ wählt, dann öffnet sich „Ticket bearbeiten“ mit aufgeklapptem Bereich, und Kanal, Kundengruppe, Produkt und die Formularangaben des Vorschlags sind noch eingetragen.
- [ ] Angenommen ein Vorschlag wurde mit Fallmustern erstellt, wenn der Mitarbeiter das Formular erneut öffnet, dann sind Fallmuster und Antworten vorbelegt.

### Wissenspflege
- [ ] Angenommen ein Autor ergänzt in einem Playbook das Erkennungsmerkmal, wenn `knowledge:check` ohne Fehler läuft, dann erscheint das Fallmuster in der Liste aller passenden Tickets, ohne Änderung am Programm.
- [ ] Angenommen ein Dokument hat Prüfkriterien oder die Kennzeichnung „Prüfung durch den Kunden“, aber kein Erkennungsmerkmal, wenn `knowledge:check` läuft, dann meldet es einen Fehler.
- [ ] Angenommen ein Prüfkriterium verweist auf einen Wert, den die App nicht kennt, wenn `knowledge:check` läuft, dann meldet es einen Fehler.
- [ ] Angenommen die Knowledge-Übersicht, wenn ein Admin sie öffnet, dann ist erkennbar, welche Dokumente Fallmuster sind.
- [ ] Angenommen der Authoring Guide, die Vorlage für Playbooks und der Skill `knowledge`, wenn ein Autor ein Fallmuster anlegt, dann beschreiben sie die neuen Angaben mit dem Grundsatz „nur Beobachtungen und Fragen, keine Regeln“.

### Erste Fallmuster
- [ ] Angenommen die erste Fassung ist ausgeliefert, dann gibt es sieben fachlich bestätigte Fallmuster: Zaubertasse (PLAYBOOK-003, -004, -005), Fototasse „eine Seite weiß“ (PLAYBOOK-026), 3D-Glasfoto „unterschiedliche Ergebnisse trotz gleicher Vorlage“ (PLAYBOOK-002) und zwei produktunabhängige aus den Bereichen Lieferung und Zahlung.
- [ ] Angenommen die Erkennungsmerkmale und Prüffragen der sieben Fallmuster, dann beschreiben sie, was ein fachlich unerfahrener Mitarbeiter beobachten kann, ohne die Ursache zu kennen; der Wortlaut ist mit dem Autor über den Skill `knowledge` abgestimmt.

## Edge Cases
- **Falsches Fallmuster gewählt** (z. B. „Beschichtung löst sich“ bei einem Druckfehler): Der Vorschlag prüft andere Ursachen weiter und nennt den Widerspruch.
- **Fallmuster gewählt, alle Prüfkriterien „nicht beurteilbar“:** gilt als offen; der Vorschlag kann daraus eine Rückfrage oder Prüfung ableiten, aber keine Bewertung.
- **Mehrere Fallmuster mit gegensätzlicher Aussage:** beide gehen als Hypothesen ein; die KI benennt, welche Beobachtung wozu passt.
- **Geltungsbereich ändert sich nach der Auswahl** (anderes Produkt, anderer Kanal): nicht mehr geltende Fallmuster werden sichtbar abgewählt, ihre Antworten verworfen.
- **Kanal ohne Produktbezug (WeCreateYour, PROJ-94):** nur produktunabhängige Fallmuster.
- **Produkt ohne hinterlegtes Wissen (PROJ-42):** keine Produkt-Fallmuster; die Zeile nennt nur die allgemeinen.
- **Systemwert widerspricht der Beobachtung** (EOCS „versandt“, Mitarbeiter „nicht versandt“): beide Angaben gehen getrennt an die KI und bleiben im Ergebnis sichtbar.
- **Neue Kundennachricht seit dem letzten Vorschlag (PROJ-76):** Formularantworten beginnen leer; gewählte Fallmuster und Antworten ebenfalls, der Geltungsbereich bleibt.
- **Fortsetzung über eine Entscheidungsfrage (PROJ-39):** Fallmuster und Beobachtungen des Ausgangsvorschlags gelten weiter.
- **Wissen ändert sich zwischen zwei Vorschlägen** (Fallmuster entfernt): ein gespeichertes, nicht mehr vorhandenes Fallmuster wird beim Wiederöffnen ohne Fehler weggelassen.
- **Sehr viele Fallmuster im Geltungsbereich:** produktunabhängige bleiben zunächst eingeklappt; die Zahl steht in der Zeile.
- **Umfang:** gewählte Fallmuster zählen als Pflichtwissen. Wird der Prompt dadurch zu groß, greifen die bestehenden Grenzen und die bestehende Meldung zum Wissensumfang.
- **Mehrere Fallmuster mit unterschiedlichen Prüfungen durch den Kunden:** jede einzeln übernehmbar; übernommene Prüfungen gehen gemeinsam an die KI, der Entwurf fasst sie verständlich zusammen.
- **Übernommene Prüfung, danach Fallmuster abgewählt oder Geltungsbereich/Kategorie geändert:** die Übernahme wird sichtbar entfernt, das Pflichtfeld greift wieder; eine dadurch gesetzte Formularoption bleibt stehen, weil sie im Formular sichtbar ist und der Mitarbeiter sie bewusst ändern kann.
- **Mehrere Bestellungen oder Sendungen mit unterschiedlichem Stand:** kein Prüfkriterium wird automatisch beantwortet; die Werte erscheinen als Hinweis mit Bezug.
- **Verwandtes Dokument außerhalb des Geltungsbereichs:** wird trotz Verknüpfung nicht abgerufen.

## Technical Requirements (optional)
- Die Liste der Fallmuster entsteht ohne KI-Aufruf; Anpassung nach Wechsel von Produkt, Kanal oder Formular ohne spürbare Wartezeit.
- Eine Analyse-Pipeline: Abruf, Prompt, Ergebnisansicht und Fortsetzung werden weiterverwendet.
- Kein neues Datenmodell erwartet: Auswahl und Antworten liegen bei den Eingaben der gespeicherten Analyse (Entscheidung in `/architecture`).
- Vorhandene Bausteine laut Vorplanung: Handzuschaltung von Wissen (`zusatzwissen`), Klärungen (`decisions`), Fallfakten und `when`-Schlüssel für „bereits bekannt“, Ereignis `open-analysis-form`, Icon-Komponente (drei neue Einträge, keine neue Bibliothek).
- Prompt-Änderung erhält eine neue Prompt-Version.
- Mehrverbrauch: je gewähltem Fallmuster mit verwandten Dokumenten etwa 3.000 bis 10.000 Zeichen Wissen.
- Changelog-Eintrag erforderlich (neue Bezeichnungen, neuer Bereich).
- Neue Maßnahme `customer-check` in der Maßnahmenliste (`config/knowledge.php`); Authoring Guide und Skill `knowledge` nennen sie.
- Bausteine der Sachverhaltsermittlung so anlegen, dass die Seite „Frage stellen“ sie später ohne zweite Umsetzung nutzen kann.

## Open Questions
- [x] UI-Entwurf: Screenshot nicht mehr nötig; Anordnung, Bezeichnungen und Icons sind in dieser Spec festgelegt (2026-11-27).
- [x] Zuordnungstabelle: wie oben unter „Zuordnung Ticketkategorie → Wissenskategorie“, alle elf Kategorien (2026-11-27).
- [x] Eigene Maßnahme für eine Prüfung durch den Kunden: ja, `customer-check` (2026-11-27).
- [x] Fallmuster auf „Frage stellen“: nicht in PROJ-103; Bausteine wiederverwendbar anlegen (2026-11-27).
- [x] Wortlaut der Erkennungsmerkmale und Prüffragen: gemeinsam über den Skill `knowledge`, aus Sicht eines fachlich unerfahrenen Mitarbeiters; sieben Fallmuster (2026-11-27).
- [x] Gelockerter Pflicht-Freitext auch im Formular „Technischer Support“: Prinzip gilt ja; das Feld „Welche Informationen werden benötigt?“ ist dort bereits optional und bleibt es, die übernommene Prüfung liefert die Anleitung aus dem Wissen (2026-11-27).

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Drei Funktionen im Bereich „Ticket bearbeiten“, keine neuen Modi und keine Auswahlkacheln; gewählt wird nur wie bisher das Formular | Mitarbeiter sind an den Ablauf gewöhnt; die Sachverhaltsermittlung braucht keine eigene Wahl | 2026-11-27 |
| Sachverhaltsermittlung ist optional, eingeklappt und rein additiv | Der schnelle Weg zum Antwortvorschlag bleibt ohne Mehrklick erhalten | 2026-11-27 |
| „Formular“ heißt „Geführte Bearbeitung“ und bleibt immer sichtbar; die Beschriftung „Formular“ entfällt | Die Formulare sind bereits eine geführte Funktion (Entscheidung, Lösung, Maßnahme) und ergänzen die Sachverhaltsermittlung; die Bereichsüberschrift erklärt die Wahl | 2026-11-27 |
| „Direkte Analyse“ ist die umbenannte Formularoption „Freitext“ (`zap`, Wert `frei`), in einem eigenen Bereich unter der geführten Bearbeitung, keine übergeordnete Zwischenüberschrift | Korrektur des Auftraggebers nach der ersten Umsetzung: die direkte Analyse ist ein Bearbeitungsweg neben der geführten Bearbeitung, gleich ob das Freitextfeld ausgefüllt wird; die Sachverhaltsermittlung dient beiden Wegen | 2026-11-27 |
| Bezeichnungen „Ticket bearbeiten“, „Direkte Analyse“, „Antwortvorschlag erstellen“; Icons `zap`, `list-checks`, `list-todo` | Vorgabe des Auftraggebers | 2026-11-27 |
| „Geführte Sachverhaltsermittlung“ heißt nur „Sachverhaltsermittlung“ | Vorgabe des Auftraggebers nach der ersten Umsetzung; kürzer und deutlicher von der geführten Bearbeitung getrennt | 2026-11-27 |
| Kategorieübergreifend: Fallmuster entstehen durch Wissenspflege, nicht als Teil eines Formulars | Die Zaubertasse ist nur der Anlass; das Dilemma betrifft jede Ticketkategorie | 2026-11-27 |
| Fallmuster sind Playbooks mit Erkennungsmerkmal; kein neuer Dokumenttyp | Playbooks sind laut Definition „woran man den Fall erkennt, wie man ihn prüft“; Prozesse kommen als verwandtes Wissen mit | 2026-11-27 |
| Erkennungsmerkmal und Prüfkriterien enthalten nur Beobachtungen und Fragen | Regeln und Anleitungen bleiben an einer Stelle im Text des Dokuments, keine doppelte Pflege | 2026-11-27 |
| Ein gewähltes Fallmuster ist eine Hypothese, keine Diagnose | Eine Aushilfe kann falsch wählen; die KI muss andere Ursachen prüfen und widersprechen können | 2026-11-27 |
| Auswahl erzwingt den Wissensabruf, nie eine Entscheidung | Bewertung und Maßnahme bleiben beim Mitarbeiter und in der geführten Bearbeitung | 2026-11-27 |
| „nicht beurteilbar“ und Unbeantwortetes gelten als offen, nicht als widerlegt | Fehlende Beobachtung ist kein Gegenbeweis | 2026-11-27 |
| „Kunde soll prüfen“ ist ein nächster Schritt, keine vierte Bewertung der Reklamation | Bewertung (berechtigt, unberechtigt, offen) und Handlung sind getrennt | 2026-11-27 |
| **Explizite Eingaben und bewusst getroffene Entscheidungen des Mitarbeiters haben Vorrang vor den allgemeinen Regeln und Handlungsanweisungen der Wissensbasis** (bestehendes Architekturprinzip, hier festgehalten) | Das Wissen kann nicht jeden Einzelfall abdecken; der Mitarbeiter muss bewusst abweichen können. Die KI macht Widersprüche sichtbar, ersetzt die Entscheidung aber nicht durch eine Regel | 2026-11-27 |
| Die Auswahl eines Fallmusters ist keine Mitarbeiterentscheidung, sondern eine Hypothese; erst das „Übernehmen“ einer Prüfung ist eine Entscheidung | Klare Grenze, was Vorrang hat und was die KI prüfen und verwerfen darf | 2026-11-27 |
| Pflicht-Freitext entfällt nur, wenn eine Prüfung durch den Kunden ausdrücklich übernommen ist und ihre Anleitung im anwendbaren, abgerufenen Wissen steht | Die Auswahl eines Fallmusters allein genügt nicht; die Anweisung muss im Wissen wirklich vorhanden sein | 2026-11-27 |
| Lockerung gilt für das Pflichtfeld „Nötige Informationen“ bei „Unsicher, weitere Informationen nötig“ (Reklamation); im Formular „Technischer Support“ ist das entsprechende Feld schon optional, dort liefert die übernommene Prüfung nur die Anleitung. Keine pauschale Aufhebung anderer Pflichtfelder, optionale Felder bleiben optional | Was aus Sachverhaltsermittlung und Wissen hervorgeht, soll niemand erneut formulieren müssen | 2026-11-27 |
| Mehrere Prüfungen durch den Kunden werden einzeln übernommen | Mehrere Fallmuster können zugleich zutreffen; der Mitarbeiter entscheidet je Prüfung | 2026-11-27 |
| Eigene Maßnahme `customer-check` | Eine definierte Prüfung durch den Kunden ist mehr als die Bitte um ein Foto oder um Informationen | 2026-11-27 |
| Systemwerte beantworten ein Prüfkriterium nur, wenn sie eindeutig dem Sachverhalt zugeordnet, aktuell und widerspruchsfrei sind | Mehrere Bestellungen, Positionen oder Sendungen können unterschiedlichen Stand haben | 2026-11-27 |
| Verwandte Dokumente gewählter Fallmuster unterliegen ihrem eigenen Geltungsbereich, ihren Bedingungen und den bestehenden Größengrenzen | Eine Verknüpfung macht ein Dokument nicht anwendbar | 2026-11-27 |
| Hinweis mit „übernehmen“ statt automatischer Vorbelegung; bestehende Wahl wird nie überschrieben | Bewusste Entscheidungen des Mitarbeiters bleiben erhalten | 2026-11-27 |
| Bekannte Systemwerte werden angezeigt statt abgefragt; nur vorhandene Werte, keine neue Beschaffung | Mitarbeiter sollen nichts ermitteln, was die App schon weiß; keine parallele Infrastruktur | 2026-11-27 |
| Kategorien über eine Zuordnungstabelle Ticketkategorie → Wissenskategorie für alle elf Kategorien; Fallmuster ohne Kategorie gelten überall; Überschneidungen in `order-process-question` werden in Kauf genommen | Kein Umbau der bestehenden Verschlagwortung (34 von 62 Playbooks ohne Kategorie); feinere Einteilung nach praktischer Erfahrung | 2026-11-27 |
| Maßgebliche Kategorie ist das gewählte Formular, sonst die Einordnung des Tickets | Bei falscher Einordnung (Anlass Ticket 2140683) folgt die Liste der Arbeit des Mitarbeiters | 2026-11-27 |
| Start mit sieben gemischten Fallmustern, formuliert aus Sicht eines unerfahrenen Mitarbeiters | Zeigt die kategorieübergreifende Nutzung, bei überschaubarem Abstimmungsaufwand | 2026-11-27 |
| Keine Fallmuster auf „Frage stellen“ in PROJ-103, aber wiederverwendbare Bausteine | Umfang klein halten, ohne eine spätere zweite Umsetzung zu erzwingen | 2026-11-27 |
| Kein Screenshot als Vorgabe; Anordnung und Bezeichnungen stehen in der Spec | Die Beschreibung ist hinreichend konkret | 2026-11-27 |
| Entscheidungsfrage der KI: die Prüfung mit dem größten Erkenntnisgewinn bei geringstem Aufwand; kein Verbot des Abgleichs mit der Konfiguration | Auch der interne Abgleich kann nötig sein; das Prinzip ist nicht auf Fotos beschränkt | 2026-11-27 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|
| Fallmuster-Liste über einen kleinen GET-Endpunkt nach dem Muster der Produktsuche, nicht im Browser berechnet | Eine Quelle der Wahrheit für den Geltungsbereich; keine Nachbildung der Scope-Regeln in JavaScript | 2026-11-27 |
| Neuer „Fallmuster-Finder“ in gleicher Bauart wie der Finder für Arbeitsabläufe | Bewährtes Muster, nutzt `KnowledgeSelector` für den Geltungsbereich | 2026-11-27 |
| Zuordnung Ticketkategorie → Wissenskategorie als Tabelle in der Wissens-Konfiguration | Änderbar ohne Code; alle elf Kategorien an einer Stelle | 2026-11-27 |
| Kategorie: Kategorien des gewählten Formulars, sonst Einordnung des Tickets | Umsetzung der Produktentscheidung; nutzt die vorhandene Formularliste | 2026-11-27 |
| Außerhalb des Geltungsbereichs werden Fallmuster ausgeblendet statt ausgegraut | Kürzere Liste; nicht wählbare Einträge helfen einer Aushilfe nicht | 2026-11-27 |
| Bekannte Werte einmal beim Laden der Ticketseite aus den vorhandenen Fallfakten; beim Absenden neu berechnet | Keine neue Beschaffung; Browserwerte gelten nie als Systemwert | 2026-11-27 |
| Nur Ja/Nein-Werte beantworten Fragen, Zahlen nur als Hinweis; Bestellwerte nur bei genau einer bezogenen Bestellung | Eindeutigkeit vor Bequemlichkeit (Spec: mehrere Bestellungen, Widersprüche) | 2026-11-27 |
| Keine neue Tabelle: Auswahl und Antworten bei den gespeicherten Eingaben der Analyse | Vorbelegung, Leeren bei neuen Nachrichten und Fortsetzung funktionieren damit schon; gleiche Verschlüsselung und Löschfristen | 2026-11-27 |
| Gewähltes Fallmuster und Anleitung einer übernommenen Prüfung als Pflichtwissen, verwandte Dokumente als starkes Wissen | Die Anleitung muss sicher im Prompt stehen; verwandtes Wissen soll nie Pflichtwissen verdrängen | 2026-11-27 |
| `customer_check` verweist auf ein Anleitungsdokument ohne `when`, dessen Geltungsbereich das Fallmuster abdeckt (Prüfung in `knowledge:check`) | Dann reicht beim Absenden die Geltungsbereich-Prüfung, um „Anleitung liegt im anwendbaren Wissen vor“ sicherzustellen | 2026-11-27 |
| Übernommene Prüfung geht als Mitarbeiterentscheidung zu den Zusatzinformationen, Fallmuster und Beobachtungen in einen eigenen Abschnitt | Setzt den Vorrang expliziter Entscheidungen und „Fallmuster sind Hypothesen“ im Prompt um | 2026-11-27 |
| Ohne Nutzung ist der Eingang an die KI wortgleich wie bisher | Akzeptanzkriterium „derselbe Eingang wie vor PROJ-103“, per Test absicherbar | 2026-11-27 |
| Gemeinsamer Zustand für Fallmuster-Bereich und Hinweis „übernehmen“; Fallmuster-Bereich ohne Ticketbezug | Hinweis in der geführten Bearbeitung kennt die Auswahl; spätere Nutzung auf „Frage stellen“ ohne zweite Umsetzung | 2026-11-27 |
| Keine neuen Pakete, Migrationen oder Umgebungsvariablen | Alles mit vorhandenen Bausteinen umsetzbar | 2026-11-27 |
| Ungültig gewordene Übernahmen entfernt der Browser sichtbar; der Server prüft beim Absenden erneut | Anzeige und Pflichtfeld bleiben stimmig; der Browser ist nie die einzige Absicherung | 2026-11-27 |
| Bekannte Werte nach Ebene: Ticketwerte ohne Bestellbezug, Bestellwerte nur bei genau einer bezogenen Bestellung | Nicht jedes Kriterium hat einen Bestellbezug (z. B. Fotos in der aktuellen Nachricht) | 2026-11-27 |
| Änderung an `docs/KNOWLEDGE_BASE_DESIGN.md` freigegeben, beschränkt auf PROJ-103 | Ausdrückliche Freigabe des Auftraggebers am 2026-11-27 | 2026-11-27 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

### Überblick

PROJ-103 baut keine zweite Analyse. Es kommen drei Dinge hinzu, alles andere wird weiterverwendet:

1. **Fallmuster im Wissen:** drei optionale Angaben im Frontmatter von Playbooks (Erkennungsmerkmal, Prüfkriterien, Prüfung durch den Kunden), geprüft von `knowledge:check`.
2. **Ein Baustein im Formular** „Ticket bearbeiten“, der die passenden Fallmuster ohne KI-Aufruf anzeigt und Auswahl, Antworten und übernommene Prüfungen mit dem Formular absendet.
3. **Ein eigener Abschnitt im Eingang an die KI** („Sachverhaltsermittlung des Mitarbeiters“), getrennt von den geprüften Fakten, plus erzwungener Abruf des Wissens der gewählten Fallmuster.

### A) Aufbau der Oberfläche

```
Ticket bearbeiten                                   (bisher „Vorschlag erstellen“)
+-- Gemeinsame Angaben (unverändert)
|   +-- Zusammengehörige Tickets
|   +-- Verkaufskanal · Kundengruppe · Produkt
+-- Sachverhaltsermittlung  [Checkliste]    (eingeklappt, für beide Bearbeitungswege)
|   +-- Kopfzeile: „Unsicher, was zu prüfen ist? 3 Fallmuster zur Zaubertasse, 2 allgemeine“
|   +-- Fallmuster zum Produkt
|   |   +-- [ ] Titel · Erkennungsmerkmal
|   |       +-- Prüfkriterien: ja / nein / nicht beurteilbar   (nach dem Anhaken)
|   |       +-- „bereits bekannt: …“ / „wird bei der Analyse geprüft“
|   +-- Allgemeine Fallmuster (eingeklappt, wenn viele)
|   +-- [ ] Kein Fallmuster passt
|   +-- Hinweis „nicht mehr gültig: …“ (nach Wechsel von Produkt/Kanal/Formular)
+-- Geführte Bearbeitung  [Aufgabenliste]            (Rahmen, immer sichtbar)
|   +-- Formularwahl (Technischer Support · Reklamation · …, ohne Beschriftung „Formular“)
|   +-- Hinweis aus der Sachverhaltsermittlung       (nur bei Prüfung durch den Kunden)
|   |   +-- „Funktionstest mit heißem Wasser (PROCESS-004)“ [übernehmen] / [zurücknehmen]
|   +-- Felder des gewählten Formulars (unverändert)
+-- ——— oder ———
+-- ( ) Direkte Analyse  [Blitz]                     (eigener kleiner Bereich; bisher „Freitext“, Wert `frei`)
|   +-- Zusätzliche Informationen / eigene Einschätzung (optional, nur bei gewählter direkter Analyse)
|   +-- DHL-Retourenlabel (wie bisher beim Freitext)
+-- Knöpfe: Antwortvorschlag erstellen · Direkt antworten · Ticket ohne Antwort schließen

Ergebnis (unter dem Vorschlag)
+-- Block „Sachverhaltsermittlung“: Fallmuster, Beobachtungen, übernommene Prüfungen (Name, Zeit)
+-- Link „Sachverhaltsermittlung“ → öffnet das Formular mit aufgeklapptem Bereich
```

- Umbau nur in `resources/views/components/analysis/form.blade.php`; die sieben Formular-Komponenten (`*-fields`) bleiben unverändert und wandern nur in den Rahmen „Geführte Bearbeitung“.
- Drei Icons (`zap`, `list-checks`, `list-todo`) als neue Einträge in der vorhandenen Icon-Komponente.
- Neue Komponenten: **Fallmuster-Bereich** und **Hinweis „übernehmen“**. Beide teilen einen gemeinsamen Zustand (Auswahl, Antworten, übernommene Prüfungen), damit der Hinweis in der geführten Bearbeitung sieht, was oben gewählt wurde. Der Fallmuster-Bereich bekommt alles, was er braucht, als Eingabe (Geltungsbereich, bekannte Werte, Vorbelegung) und kennt kein Ticket. So kann ihn „Frage stellen“ später ohne zweite Umsetzung verwenden.
- Erklärtexte (was ein Fallmuster ist, warum es eine Hypothese ist) nur im Einführungsmodus (PROJ-74).

### B) Woher die Liste kommt

- **Ein kleiner Abruf-Endpunkt** liefert die Fallmuster zu Kundengruppe, Kanal, Produkten und Kategorie, nach dem Vorbild der vorhandenen Produktsuche (gleicher Zugangsschutz, gleiche Drosselung). Die Seite ruft ihn beim Laden und nach jedem Wechsel von Produkt, Kanal, Kundengruppe oder Formular auf. Antwortzeit: nur Dateien aus dem bereits geladenen Wissen, kein KI-Aufruf, keine externe Schnittstelle.
- Der Endpunkt nutzt dieselbe Geltungsbereich-Prüfung wie der Wissensabruf (eine Quelle der Wahrheit, keine Nachbildung im Browser). Fallmuster außerhalb des Geltungsbereichs werden nicht angezeigt. Für bereits gewählte Fallmuster, die nicht mehr gelten, liefert er den Grund mit („gilt nur für …“, „gilt nicht für diese Kundengruppe“).
- **Kategorie:** Der Endpunkt erhält das gewählte Formular und die Einordnung des Tickets. Hat das Formular Kategorien (aus der vorhandenen Formularliste), gelten diese, sonst die Einordnung. Die Zuordnung Ticketkategorie → Wissenskategorie steht als Tabelle in der Konfiguration des Wissens (alle elf Kategorien, Tabelle oben). Fallmuster ohne Kategorie erscheinen immer.
- Neuer Baustein **„Fallmuster-Finder“** neben dem vorhandenen Finder für Arbeitsabläufe, gleiche Bauart: liest das nutzbare Wissen (gültig, nicht abgelaufen), filtert nach Geltungsbereich und Kategorie, sortiert Produkt-Fallmuster vor allgemeine.

### C) Bereits bekannte Werte

- Beim Öffnen der Ticketseite sind Ticket und Bestellungen geladen. Daraus berechnet die App einmal die Fallfakten, die der Wissensabruf ohnehin kennt (Bausteine „Fallfakten“ und `when`-Schlüssel), und gibt sie dem Fallmuster-Bereich mit: je Schlüssel Wert, Quelle und ob er **eindeutig** ist.
- **Eindeutig** heißt: Der Wert ist bekannt und gehört zu der Ebene, auf die sich die Frage bezieht. **Ticketwerte** (z. B. Anhang in der aktuellen Kundennachricht, Anzahl der Kundennachrichten) brauchen keinen Bestellbezug und gelten unabhängig von der Zahl der Bestellungen. **Bestellwerte** (versandt, zugestellt, Tage seit Versand) gelten nur für genau eine Bestellung, auf die sich das Anliegen bezieht (Bezug „genannt“ oder „einzige Bestellung“).
- **Bedeutung muss passen:** `known_from` darf nur gesetzt werden, wenn die Frage genau das meint, was der Wert aussagt (z. B. „aktuelle Nachricht hat einen Anhang“, nicht „der Kunde hat irgendwann Fotos geschickt“). `knowledge:check` prüft, dass der Wert existiert; die Bedeutung stellt der Authoring Guide mit Beispielen sicher. Bei mehreren Bestellkandidaten, unklarem Bezug oder fehlendem Wert ist er nicht eindeutig: Die Frage bleibt, die Werte erscheinen als Hinweis mit Bezug.
- **Widersprüche:** Liefern die Kandidaten unterschiedliche Werte, oder widersprechen sich zusammenhängende Werte derselben Bestellung (z. B. nicht versandt, aber zugestellt), gilt der Wert nicht als Antwort.
- **Nur Ja/Nein-Werte beantworten eine Frage** (z. B. versandt, zugestellt, Anhang vorhanden). Zahlen (Tage seit Versand) erscheinen als Hinweis neben der Frage.
- **Erst bei der Analyse geholt** (Sendungsverlauf, Lettermint): Kriterien ohne passenden Schlüssel, die der Autor als „automatisch“ kennzeichnet, zeigen „wird bei der Analyse automatisch geprüft“. Keine neue Abfrage beim Laden.
- **Aktualität:** Die Werte stammen vom Laden der Seite. Beim Absenden berechnet die App sie neu und verwendet nur die neuen Werte; was der Browser schickt, gilt nie als Systemwert.

### D) Daten (keine neue Tabelle)

Mit jeder Analyse werden bei den gespeicherten Eingaben zusätzlich abgelegt (im bereits verschlüsselten Inhalt, gleiche Aufbewahrung und Löschung):

- gewählte Fallmuster (IDs)
- Antworten je Prüfkriterium (ja / nein / nicht beurteilbar; Unbeantwortetes fehlt)
- übernommene Prüfungen durch den Kunden (IDs)
- „Kein Fallmuster passt“ (ja/nein)
- wer und wann (wie bei den Klärungen)

Damit funktionieren ohne weitere Arbeit: Vorbelegung beim erneuten Öffnen, Leeren bei neuen Kundennachrichten (Formularantworten beginnen dann leer, PROJ-76), Fortsetzung über eine Entscheidungsfrage (die Eingaben des Ausgangsvorschlags werden übernommen). IDs, die es im Wissen nicht mehr gibt, werden beim Wiederöffnen still weggelassen.

### E) Wissensschema

Drei optionale Frontmatter-Angaben, nur für Playbooks:

| Angabe | Inhalt | Prüfung durch `knowledge:check` |
|---|---|---|
| `recognize` | ein bis zwei Sätze, was man beobachten kann | Pflicht, sobald eine der anderen Angaben steht; Länge begrenzt; nur bei `playbook` |
| `checks` | bis zu fünf Fragen; je Frage Schlüssel, Fragetext, optional `known_from` (Ja/Nein- oder Zahlenwert der App) oder Kennzeichen „automatisch bei der Analyse“ | Schlüssel eindeutig, `known_from` muss ein bekannter Wert sein, sonst **Fehler** |
| `customer_check` | Name der Prüfung und die ID des Dokuments mit der Anleitung | ID muss existieren; das Anleitungsdokument darf keine `when`-Bedingung haben und muss mindestens den Geltungsbereich des Fallmusters abdecken, sonst **Fehler** |

- Die Bedingung „Anleitungsdokument ohne `when`, Geltungsbereich deckt das Fallmuster ab“ macht die spätere Prüfung beim Absenden einfach und sicher: Gilt das Fallmuster für den Fall, gilt auch die Anleitung.
- Neue Maßnahme `customer-check` („Kunde um Prüfung bitten“) in der Maßnahmenliste; sie steht dadurch automatisch der KI, den Arbeitsabläufen und der Prüfung zur Verfügung.
- Knowledge-Übersicht: Kennzeichnung „Fallmuster“ je Dokument.
- Dokumentation: Authoring Guide (Abschnitt Fallmuster, Grundsatz „nur Beobachtungen und Fragen“), Vorlage für Playbooks, Skill `knowledge`, Knowledge Base Design (nur nach Freigabe, die Datei ist sonst geschützt).

### F) Abruf des Wissens

- **Gewähltes Fallmuster:** Pflichtwissen, Grund „in der Sachverhaltsermittlung gewählt“. Gilt es nicht (mehr) für den Fall, wird es nicht abgerufen.
- **Anleitung einer übernommenen Prüfung:** ebenfalls Pflichtwissen (sonst stünde die Anleitung nicht sicher im Prompt).
- **Verwandte Dokumente** (`related_knowledge` des Fallmusters, eine Ebene): nur, wenn sie im Geltungsbereich liegen, nicht abgelaufen sind und ihre Bedingung nicht „nicht erfüllt“ ergibt; dann als **starkes** Wissen, vor den Auslösertreffern. Sie verdrängen also nie Pflichtwissen und fallen bei Platzmangel wie anderes starkes Wissen heraus.
- Bestehende Grenzen und die Meldung bei zu viel Pflichtwissen bleiben unverändert.
- Der Router (PROJ-37) sieht die gewählten Fallmuster als bereits abgerufen und schlägt sie nicht noch einmal vor.

### G) Eingang an die KI und Vorrang

Die Teile des Eingangs werden um einen Abschnitt ergänzt, der nur erscheint, wenn die Sachverhaltsermittlung genutzt wurde (ohne Nutzung ist der Eingang **wortgleich** wie bisher):

- **Sachverhaltsermittlung des Mitarbeiters (Hypothesen und Beobachtungen, nicht geprüft):** gewählte Fallmuster als „in Betracht gezogen“, Antworten als Beobachtungen, „nicht beurteilbar“ und Unbeantwortetes als offen, automatisch bekannte Werte mit Quelle, „Kein Fallmuster passt“.
- **Übernommene Prüfung** ist eine Entscheidung des Mitarbeiters und steht deshalb bei den „Zusätzlichen Informationen vom Mitarbeiter“: „Der Kunde soll die Prüfung ‚…‘ nach PROCESS-004 durchführen.“ Bleibt dabei das Pflichtfeld „Nötige Informationen“ leer, ersetzt dieser Satz im Reklamationsformular die bisherige Zeile mit den nötigen Informationen; im Formular „Technischer Support“ kommt er zu den vorhandenen Zeilen hinzu. Hat der Mitarbeiter etwas eingetragen, stehen beide da, sein Text zuerst.
- **Prompt-Regeln** (neue Prompt-Version der Analyse): Fallmuster sind Hypothesen; andere Ursachen prüfen; Widersprüche zu Ticket, Systemdaten oder Formularangaben benennen; offen heißt nicht widerlegt; Entscheidungen und Angaben des Mitarbeiters gehen allgemeinen Regeln des Wissens vor, Abweichungen werden sichtbar gemacht, nicht korrigiert; bereits Beantwortetes nicht erneut fragen; die Entscheidungsfrage wählt die Prüfung mit dem größten Erkenntnisgewinn bei geringstem Aufwand und greift, wo vorhanden, die Prüfkriterien der abgerufenen Fallmuster auf; `customer-check` für definierte Prüfungen durch den Kunden.
- Der Abschnitt geht auch in die Auslöser-Suche ein (wie heute die Zusatzinformationen), damit z. B. eine Beobachtung „Rand dunkel“ passendes Wissen findet.

### H) Prüfung beim Absenden

In der vorhandenen Formular-Prüfung der Analyse:

- Fallmuster-IDs, Prüfkriterien und übernommene Prüfungen werden gegen das Wissen geprüft: nur bekannte IDs, nur Kriterien des jeweiligen Fallmusters, nur die drei Antworten, übernommene Prüfungen nur von gewählten Fallmustern, die im Geltungsbereich liegen.
- **Pflichtfeld „Nötige Informationen“ (Reklamation, „Unsicher, weitere Informationen nötig“):** darf leer bleiben, wenn mindestens eine gültige Prüfung übernommen ist. Sonst bisherige Meldung; ist eine übernommene Prüfung ungültig (Geltungsbereich), Meldung mit dem Zusatz, dass die Anleitung im Wissen für diesen Fall fehlt.
- **Ungültig gewordene Übernahme:** Ändert der Mitarbeiter danach Produkt, Kundengruppe, Kanal, Formular oder die Fallmusterauswahl, prüft der Fallmuster-Bereich mit der Antwort des Endpunkts, ob jede übernommene Prüfung noch gilt, entfernt ungültige sichtbar und nennt den Grund. Die Prüfung beim Absenden wiederholt das auf dem Server; nur gültige Übernahmen lockern das Pflichtfeld.
- Alle übrigen Regeln der sieben Formulare bleiben unverändert.

### I) Ergebnisansicht

- Block „Sachverhaltsermittlung“ beim Ergebnis, getrennt von Systemdaten und Klärungen: gewählte Fallmuster, Beobachtungen, übernommene Prüfungen, Name und Zeit.
- Quellen: neue Herkunft „In der Sachverhaltsermittlung gewählt“ bzw. „verwandt mit gewähltem Fallmuster“.
- Link „Sachverhaltsermittlung“ beim Ergebnis nutzt das vorhandene Ereignis zum Öffnen des Formulars und klappt den Bereich auf.

### J) Abhängigkeiten

Keine neuen Pakete. Keine neue Tabelle, keine Migration, keine neue Umgebungsvariable.

### K) Tests (für `/backend` und `/qa`)

- Fallmuster-Finder: Geltungsbereich, alle elf Kategorien der Zuordnung, Formular vor Einordnung, ohne Produkt, Kanal ohne Produktbezug, Gründe für weggefallene Auswahl.
- Bekannte Werte: eine Bestellung, mehrere Bestellungen, unklarer Bezug, fehlender Wert, Widerspruch, Zahlenwert nur als Hinweis.
- Abruf: gewähltes Fallmuster Pflicht, Anleitung Pflicht, verwandte Dokumente mit Geltungsbereich, Bedingung und Platzgrenze.
- Eingang an die KI: ohne Nutzung wortgleich wie bisher (Vergleich mit dem bisherigen Eingang); mit Nutzung getrennte Abschnitte.
- Formularprüfung: Pflichtfeld mit und ohne übernommene Prüfung, Prüfung außerhalb des Geltungsbereichs, manipulierte IDs.
- `knowledge:check`: alle neuen Fehlerfälle.
- Vorhandene Tests der sieben Formulare laufen unverändert (nur geänderte Bezeichnungen).


## Implementation Notes (Frontend + Backend, 2026-11-27)

**Gebaut**
- Oberfläche (`resources/views/components/analysis/form.blade.php`): „Ticket bearbeiten“, eingeklappter Bereich „Sachverhaltsermittlung“ (`list-checks`), Rahmen „Geführte Bearbeitung“ (`list-todo`) um die Formularwahl ohne Beschriftung „Formular“ und die unveränderten `*-fields`, Trenner „oder“, eigener Bereich „Direkte Analyse“ (`zap`) mit dem Auswahlknopf für den Wert `frei` (Bezeichnung in `GuidedForms::options()`, bisher „Freitext“), dem optionalen Feld „Zusätzliche Informationen / eigene Einschätzung“, der DHL-Option und der Schreibhilfe; Knopf und Ladeanzeige „Antwortvorschlag erstellen“. Ohne Formularwahl entfallen Rahmen und Trenner wie bisher, der Bereich „Direkte Analyse“ zeigt dann nur Überschrift und Feld. Korrektur 2026-11-27: die zunächst gebaute übergeordnete Zwischenüberschrift „Direkte Analyse“ ist entfallen. Jede der drei Funktionen hat ein Fragezeichen mit Entscheidungshilfe (`x-help-tip`, immer sichtbar, nicht nur im Einführungsmodus): Sachverhaltsermittlung bei unklarem Sachverhalt oder Lösungsweg, geführte Bearbeitung bei klarem Sachverhalt, der zu einem Formular passt, direkte Analyse bei bekanntem Sachverhalt ohne passendes Formular.
- Neue Komponenten `analysis/case-patterns` (Liste, Prüffragen, „Kein Fallmuster passt“, Hinweise bei weggefallener Auswahl, Fehler- und Ladezustand) und `analysis/customer-check-hint` („übernehmen“/„zurücknehmen“, setzt nur eine noch leere Option, Hinweis bei Widerspruch). Gemeinsamer Zustand im Alpine-Store `caseFindings` (`resources/js/case-patterns.js`); die Geltungsbereich-Felder melden Wechsel per `scope-changed`. Der Fallmuster-Bereich kennt kein Ticket (wiederverwendbar für „Frage stellen“).
- Endpunkt `GET /fallmuster` (`CasePatternController`, `CasePatternRequest`, Route `case-patterns`, Zugang wie die Produktsuche) mit `CasePatternFinder` (Geltungsbereich über `KnowledgeSelector`, Kategorie über `GuidedForms::categoriesOf()` und `knowledge.pattern_categories`).
- Bekannte Werte: `KnownCaseValues` aus den Fallfakten; beim Laden der Ticketseite und beim Absenden neu berechnet.
- Analyse: `CaseFindings` (Hypothesen, Beobachtungen, Systemwerte, übernommene Prüfungen), `AnalyzeTicketRequest::findings()` und `hasValidCustomerCheck()`, Abschnitt „Sachverhaltsermittlung des Mitarbeiters“ im Prompt nach den Zusatzinformationen (nur bei Nutzung), Entscheidungssatz der übernommenen Prüfung bei den Zusatzinformationen, Speicherung unter `findings` und bei den Eingaben (`fallmuster`, `pruefung`, `kundenpruefung`, `kein_fallmuster`).
- Abruf: neue Parameter in `KnowledgeRetriever::retrieve()`; gewählte Fallmuster und Anleitungen Pflichtwissen (Herkunft `chosen`), verwandte Dokumente starkes Wissen (Herkunft `related`) nur im Geltungsbereich und bei erfüllter Bedingung.
- Prompt `analysis-2026-11-27.1`: Fallmuster als Hypothesen, Vorrang der Mitarbeiterentscheidungen, `customer-check`, Entscheidungsfrage nach Erkenntnisgewinn.
- Wissen: Leser in `KnowledgeDocument` (`recognize()`, `checks()`, `customerCheck()`, `isCasePattern()`), Prüfung in `KnowledgeValidator` (`casePatternFields`, `customerCheckTargets`), Maßnahme `customer-check`, Kennzeichen „Fallmuster“ in der Knowledge-Übersicht, Authoring Guide, Knowledge Base Design (freigegeben), Vorlage, Skill `knowledge`.
- Tests: `tests/Feature/PROJ-103-GuidedFactFindingTest.php` (29 Tests); Bezeichnungen in bestehenden Tests nachgezogen.

**Abweichungen**
- Pflichtfeld bei „Unsicher, weitere Informationen nötig“: Im Browser wird `required` an die Zahl übernommener Prüfungen gebunden; der Server prüft gültige Übernahme (Fallmuster gewählt, im Geltungsbereich, mit `customer_check`). Die Kategorie wirkt nur auf die angezeigte Liste, nicht auf die Prüfung beim Absenden.
- Maßnahme `customer-check` steht am Ende der Maßnahmenliste (die Reihenfolge der übrigen ist durch Tests festgeschrieben).
- Auch ohne Formularwahl wird der Hinweis „übernehmen“ angezeigt (unter dem Fallmuster-Bereich).
- Der Bereich öffnet sich nach einem Vorschlag mit Fallmustern von selbst; nach neuen Kundennachrichten beginnt er leer.

**Noch offen vor `/qa`**
- Die sieben Fallmuster im Wissen (Erkennungsmerkmale, Prüffragen, Kundenprüfung) gemeinsam mit dem Autor über den Skill `knowledge`.
- Changelog-Eintrag, sobald die Fallmuster vorhanden sind.

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
