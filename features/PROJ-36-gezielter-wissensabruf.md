# PROJ-36: Gezielter Wissensabruf

## Status: In Progress
**Created:** 2026-10-13
**Last Updated:** 2026-10-13

## Dependencies
- Requires: PROJ-3 (Knowledge Base einlesen und prüfen) – Frontmatter, Prüfbefehl, Validierung
- Requires: PROJ-4 (Knowledge-Auswahl) – wird erweitert; die Aussage „kein Filter nach Art der Anfrage“ wird dabei durch `/refine PROJ-4` angepasst
- Requires: PROJ-9 (Fallanalyse) – Aufruf der Auswahl, Protokoll der Analyse
- Requires: PROJ-6, PROJ-7 (Ticket, Bestellung) – Texte und Bestelldaten als Grundlage des Abrufs
- Requires: PROJ-10, PROJ-11 (Ergebnisansicht, Protokoll) – Anzeige und Speicherung der Abrufgründe
- Requires: PROJ-2 (Authoring-Kit) – Authoring Guide, Vorlagen, KI-Skill
- Verwandt: PROJ-33 (Kundenbegriffe) – gleiches Suchverfahren im Ticket-Text; PROJ-22 (Semantisches Retrieval, später); PROJ-37 (Router, Folgefeature)

## Ausgangslage
Die App wählt Wissen heute nur nach Kundengruppe und Produkt. Alles, was dort passt, geht in jeden Prompt. Gemessen am 2026-10-12: 44 Dokumente und 90.523 Zeichen für einen Privatkunden von looxis.de ohne Produkt, bei einer Obergrenze von 60.000, die nur Beispiele kürzt. Mit Hunderten Dokumenten wächst der Prompt ungebremst; Regeln, die selten gebraucht werden (z. B. Nutzungsrechte an Motiven), führen zu ungefragten Hinweisen in Antworten, die dazu gar keinen Anlass haben. `categories`, `topics` und `related_knowledge` filtern heute nicht. Ziel ist eine Wissensbasis mit Hunderten Dokumenten, bei der der Prompt nur das enthält, was der konkrete Fall braucht, und verbindliche Regeln dennoch zuverlässig greifen. Die häufigsten Fälle sind Reklamationen (auch unberechtigte) und WISMO (je etwa 40 %); WISMO läuft über n8n, der Abruf wird deshalb zuerst für Reklamationen optimiert.

## User Stories
- Als Mitarbeiter im Kundenservice möchte ich, dass die Analyse nur das Wissen nutzt, das zu meinem Fall passt, damit der Antwortentwurf keine ungefragten Hinweise enthält.
- Als Admin möchte ich sehen, welches Wissen abgerufen wurde, aus welchem Grund, und was nicht abgerufen oder wegen Umfang ausgelassen wurde, damit ich fehlende und falsche Treffer finde und behebe.
- Als Autor von Wissen (Admin, mit dem KI-Chat) möchte ich bei jedem Dokument festlegen, wann es gebraucht wird, damit die Wissensbasis wachsen kann, ohne dass sich Inhalte vermischen.
- Als Verantwortlicher möchte ich, dass verbindliche Regeln auch dann greifen, wenn ein Stichwort fehlt, damit nichts Wichtiges übersehen wird.
- Als Mitarbeiter möchte ich ein nicht abgerufenes, aber relevantes Dokument von Hand zuschalten und neu analysieren können, damit ich bei einem Fehlgriff nicht blockiert bin.
- Als Admin möchte ich vor jeder Übernahme neuer Dateien prüfen können, welches Wissen bei typischen Fällen im Prompt landet, und dass die Prüfung Fehler bei zu großem Pflichtumfang meldet.

## Out of Scope
- KI-Router, Fallarten als Liste, Vorauswahl-Katalog – PROJ-37
- Semantische Suche mit Embeddings – PROJ-22
- Pest Evals, umfangreiche Gold-Testfälle, Testläufe mit echten Zammad-Tickets – später
- Original-gegen-Änderung-Ansicht für Admins und Zuordnung von Feedback zu Dokumenten – späteres Folgefeature
- Automatisches Lernen aus Korrekturen (Non-Goal im PRD)
- Fachliche Umformulierung bestehender Wissensinhalte; geändert wird nur das Frontmatter
- Eine Oberfläche zum Bearbeiten der Metadaten in der App (weiterhin Dateien im Repository)
- WISMO-Optimierung (läuft über n8n)

## Begriffe
- **Klasse (`retrieval`):** `core` (Grundwissen, immer im Geltungsbereich), `triggered` (bei Treffer oder erfüllter Bedingung), `binding` (verbindlich, darf nicht übersehen werden).
- **Geltungsbereich:** Kundengruppe (Kundenart, Kanal) und Produkt; legt nur fest, wo ein Dokument in Frage kommt, nicht ob es abgerufen wird.
- **Auslöser (`triggers`):** Wörter und Wendungen, mit denen Kunden das Anliegen schreiben.
- **Bedingung (`when`):** strukturierte Voraussetzungen aus Fall- und Bestelldaten.
- **Pflichtumfang:** `core` (Grundwissen und Produktgrundlagen) plus erforderliches `binding`.
- **Bezugsbestellung:** die Bestellung, auf die sich das aktuelle Kundenanliegen bezieht (siehe Abschnitt „Bestellbezug“).
- **Fallbedingung / Bestellbedingung:** Eine Fallbedingung gilt für den ganzen Fall (Kundengruppe, Produkt, Ticket); eine Bestellbedingung gilt für genau eine Bestellung (Kanal, Alter, Versand usw.).

## Startliste der `when`-Schlüssel (Empfehlung, zur fachlichen Freigabe)

Grundlage: Auswertung der bestehenden EOCS-Anbindung (`EocsClient`, `EocsOrder`, `EocsShipment`, `EocsOrderItem`, `EocsClaim`) und der Ticket-Integration (`Ticket`, `TicketArticle`, `LanguageDetector`, `OrderNumberDetector`). **Nicht verfügbar** und deshalb nicht Teil der Startliste: Bestellwert und Zahlart (bewusst nicht aus EOCS übernommen, Datenschutz), Lieferland als eigenes Feld (nur Teil der Lieferadresse, nie an die KI), Rechnungsbetrag.

**Grundregel für fehlende Werte:** Ein Schlüssel hat entweder einen **bekannten Wert** oder ist **unbekannt**. Ist das Fehlen mehrdeutig (z. B. „keine Sendung in EOCS“ kann „noch nicht versendet“ oder „Daten fehlen“ heißen), liefert der Schlüssel **unbekannt**, nie ein stilles „nein“. Eine ausgewertete Bedingung ist **wahr, falsch oder unbekannt** (dreiwertige Logik): UND ist falsch, sobald ein Teil falsch ist, sonst unbekannt, sobald ein Teil unbekannt ist, sonst wahr; ODER ist wahr, sobald ein Teil wahr ist, sonst unbekannt, sobald ein Teil unbekannt ist, sonst falsch; NICHT lässt unbekannt unbekannt. Ein unbekanntes Ergebnis bedeutet bei `binding` nur den **vorsorglichen Abruf** („Voraussetzung ungeklärt“), niemals die Annahme, die Voraussetzung sei erfüllt; bei `triggered` führt es nicht zum Abruf.

| Schlüssel | Ebene | Quelle | Zuverlässigkeit | Fehlt / unbekannt, wenn |
|---|---|---|---|---|
| `customer_type`, `sales_channel` | Fall | Auswahl des Mitarbeiters (Kundengruppe) | hoch | Gruppe „noch unklar“ |
| `products` | Fall | Auswahl des Mitarbeiters | hoch | kein Produkt gewählt (bekannt: leer) |
| `ticket_closed` | Fall | Zammad-Status | hoch | – |
| `ticket_age_days` | Fall | Erstellzeit des Tickets | hoch | – |
| `customer_message_count` | Fall | Zählung der Kundennachrichten (ohne Zitate, Signaturen, Notizen) | hoch | – |
| `current_message_has_attachment` | Fall | Anhänge der aktuellen Kundennachricht | hoch | – |
| `message_foreign` | Fall | `LanguageDetector` (Heuristik, nicht deutsch ja/nein) | mittel | Text zu kurz oder nicht bestimmbar |
| `order_channel` | Bestellung | EOCS-Kanal, über die Zuordnungstabelle auf Vertriebskanal abgebildet | mittel bis hoch | Kanalname nicht in der Tabelle |
| `order_age_days` | Bestellung | EOCS-Bestelldatum | hoch | Datum fehlt oder nicht lesbar |
| `shipped` | Bestellung | mindestens eine Sendung aus EOCS (**auch Teillieferung**) | mittel | nie „nein“: ohne Sendung unbekannt |
| `days_since_shipped` | Bestellung | Tage seit der **ersten** Sendung (frühestes Versanddatum) | mittel | kein lesbares Versanddatum |
| `days_since_last_shipped` | Bestellung | Tage seit der **letzten** Sendung (spätestes Versanddatum); bei Teillieferungen maßgeblich für „alles unterwegs seit“ | mittel | kein lesbares Versanddatum |
| `shipment_count` | Bestellung | Anzahl der Sendungen | mittel | keine Sendung geladen (unbekannt, nicht 0) |
| `delivery_confirmed` | Bestellung | mindestens **eine** Sendung als zugestellt gekennzeichnet | mittel | nie „nein“: ohne Bestätigung unbekannt |
| `all_shipments_delivered` | Bestellung | **alle** geladenen Sendungen als zugestellt gekennzeichnet | mittel | sobald eine Sendung ohne Zustellkennzeichen ist (unbekannt, nicht „nein“) |
| `has_claim`, `claim_count` | Bestellung | Folgebestellung(en) nach dem Schema „R1-…“ bis „R20-…“, die auf die Bestellung verweisen; **Mindestanzahl**, nie „nein“ | mittel | keine gefunden (unbekannt, nicht „nein“): das Schema ist nur für bestimmte Kanäle gesichert, die Suche reicht bis R20 |
| `item_quantity_total` | Bestellung | Summe der Positionsmengen | hoch | keine Positionen geladen |

**Aussagekraft der Schlüssel (bindend für die Autoren):**
- Die Sendungen aus EOCS enthalten **keine Zuordnung zu Positionen**. Teillieferungen sind daher nicht sicher erkennbar: `shipped` heißt „mindestens eine Sendung“, nicht „vollständig versandt“; `all_shipments_delivered` bezieht sich nur auf die geladenen Sendungen und sagt nicht, dass alle Positionen versandt wurden. Regeln, die eine vollständige Lieferung voraussetzen, dürfen sich nicht allein auf diese Schlüssel stützen (Prüfung warnt im Authoring Guide).
- `days_since_shipped` (erste Sendung) und `days_since_last_shipped` (letzte Sendung) werden für Fristen bewusst getrennt angeboten; bei einer einzigen Sendung sind sie gleich.
- `has_claim` bedeutet nur „es existiert eine Folgebestellung nach dem Schema R<n>-…“ (z. B. ein Ersatz- oder Reklamationsauftrag), **nicht**, dass eine Reklamation berechtigt war oder abgeschlossen ist. Fehlende Funde heißen „unbekannt“, nicht „keine Reklamation“.

Zurückgestellt, bis die Werte gesammelt oder abgestimmt sind: `order_status` (freier EOCS-Text wie „Vollständig und verschickt“; zuerst die tatsächlich vorkommenden Statusnamen sammeln, `knowledge:overview` listet sie), `has_invoice` (Rechnungsnummer fehlt auch bei frühen Bestellungen), `case_category` (PROJ-37).

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Metadaten und Prüfung
- [ ] Angenommen ein Dokument, wenn es die Felder `summary`, `retrieval`, `triggers`, `exclude_triggers` und `when` trägt, dann liest und prüft die App sie, und `knowledge:check` meldet falsche Werte als Fehler
- [ ] Angenommen ein Dokument mit `retrieval: triggered` ohne `triggers` und ohne `when`, wenn die Prüfung im Übergangsmodus läuft, dann erscheint eine Warnung; nach dem Umschalten auf Pflichtfeld (Migration abgeschlossen) ist das ein **Fehler**, weil das Dokument nie abgerufen werden könnte
- [ ] Angenommen ein Dokument mit `retrieval: binding`, wenn es weder Auslöser noch Bedingung hat, dann gilt es im Geltungsbereich immer; hat es nur Auslöser, dann warnt die Prüfung („kann übersehen werden“)
- [ ] Angenommen ein `binding`-Dokument nur mit `actions` (ohne Auslöser und Bedingung), wenn die Prüfung läuft, dann ist das zulässig, da die Maßnahmensicherung es erreicht; ein `binding`-Dokument ohne Auslöser, Bedingung und `actions` gilt immer im Geltungsbereich
- [ ] Angenommen `exclude_triggers` bei einem `binding`-Dokument, wenn die Prüfung läuft, dann ist das ein Fehler
- [ ] Angenommen ein Auslöser mit weniger als vier Zeichen, wenn die Prüfung läuft, dann erscheint eine Warnung
- [ ] Angenommen ein Dokument mit mehr als 25 Auslösern, wenn die Prüfung läuft, dann erscheint eine Warnung
- [ ] Angenommen derselbe Auslöser steht in zwei Dokumenten, wenn die Prüfung läuft, dann ist das erlaubt und erscheint nur als Hinweis in der Übersicht
- [ ] Angenommen ein `when` mit unbekanntem Schlüssel, falschem Typ, widersprüchlichen Grenzen oder leerem Inhalt, wenn die Prüfung läuft, dann ist das ein Fehler
- [ ] Angenommen ein Schlüssel in `when`, der für eine Kundengruppe nie verfügbar ist, wenn die Prüfung läuft, dann erscheint eine Warnung
- [ ] Angenommen der Pflichtumfang einer Kundengruppe (mit jedem Produkt) überschreitet das Budget, wenn die Prüfung läuft, dann ist das ein Fehler

### Abruf
- [ ] Angenommen ein `core`-Dokument im Geltungsbereich, wenn eine Analyse startet, dann ist es im Prompt
- [ ] Angenommen ein `triggered`-Dokument, wenn ein Auslöser in der letzten Kundennachricht oder im Titel eines jungen Tickets vorkommt, dann wird es abgerufen; ohne Treffer und ohne erfüllte Bedingung nicht
- [ ] Angenommen ein Auslöser, wenn er in Groß- oder Kleinschreibung oder am Wortanfang („Glasstein“ in „Glassteinen“) steht, dann trifft er; mitten im Wort nicht
- [ ] Angenommen mehrere Anliegen in einer Nachricht, wenn Auslöser verschiedener Dokumente treffen, dann werden alle abgerufen
- [ ] Angenommen ein Treffer nur in einer älteren Kundennachricht oder im Rest des Tickets, wenn der Abruf läuft, dann gilt er als schwach und fällt bei Platznot vor starken Treffern weg
- [ ] Angenommen Zitate, Signaturen, interne Notizen und eigene Antworten, wenn der Abruf Texte durchsucht, dann zählen sie nicht
- [ ] Angenommen mehrere Dokumente mit demselben Auslöser, wenn er trifft, dann werden alle passenden abgerufen
- [ ] Angenommen ein Produkt ist gewählt, wenn die Analyse startet, dann gelten nur Produktgrundlagen (`core`) automatisch; produktgebundene Spezialdokumente nur bei passendem Anliegen
- [ ] Angenommen ein Dokument ohne `products`, wenn ein Produkt gewählt ist, dann gilt es produktunabhängig im Geltungsbereich

### Verbindliche Regeln
- [ ] Angenommen ein `binding`-Dokument ohne Auslöser und ohne Bedingung, wenn eine Analyse im Geltungsbereich läuft, dann wird es immer abgerufen
- [ ] Angenommen ein `binding`-Dokument mit Bedingung, wenn alle Voraussetzungen zutreffen, dann wird es abgerufen, auch ohne Stichwort
- [ ] Angenommen eine Bedingung, deren Daten fehlen (z. B. EOCS nicht erreichbar, keine Bestellung geladen, Bestelldaten nur von Hand erfasst), wenn sie ausgewertet wird, dann ist ihr Ergebnis **unbekannt**; ein `binding`-Dokument wird dann **vorsorglich abgerufen** und im Prompt mit dem Hinweis versehen „Voraussetzung nicht geprüft: nicht als erfüllt behandeln, Regel nur anwenden, wenn der Fall sie bestätigt, sonst als fehlende Information nennen“; ein `triggered`-Dokument wird nicht allein deshalb abgerufen
- [ ] Angenommen ein vorsorglich abgerufenes Dokument mit ungeklärter Voraussetzung, wenn das Ergebnis angezeigt wird, dann steht es mit „Voraussetzung ungeklärt“ in den Quellen, und das Protokoll nennt die fehlenden Daten
- [ ] Angenommen mehrere Teilbedingungen mit unbekannten und bekannten Werten, wenn UND, ODER und NICHT ausgewertet werden, dann gilt die dreiwertige Logik (z. B. falsch UND unbekannt = falsch; wahr ODER unbekannt = wahr; NICHT unbekannt = unbekannt)
- [ ] Angenommen mehrere Voraussetzungen in `when`, wenn nicht alle zutreffen, dann gilt die Bedingung als nicht erfüllt; mehrere Werte eines Schlüssels gelten als „einer davon“; `any_of` bildet Alternativen, `not` verneint; Zahlenwerte unterstützen Mindest- und Höchstwert
- [ ] Angenommen ein `binding`-Dokument mit `actions` (z. B. Regel oder Befugnis zu Erstattung, Kulanz, Neuproduktion) ohne Auslöser und ohne Bedingung, wenn die Analyse zum ersten Mal läuft, dann ist es **maßnahmengebunden**: Es gehört nicht zum Pflichtumfang des ersten Durchgangs, wird aber über die Maßnahmensicherung (nächste Punkte) zuverlässig nachgeladen
- [ ] Angenommen die Analyse empfiehlt eine Maßnahme aus der Vorgangsliste (z. B. Erstattung, Kulanz), wenn dazu verbindliche Dokumente im Geltungsbereich existieren, die nicht im Prompt waren (der Kunde hatte die Maßnahme nicht verlangt, die KI hat sie selbst vorgeschlagen), dann ergänzt die App diese Dokumente und führt die Analyse **genau einmal** erneut aus; das Ergebnis des ersten Durchgangs wird nicht als endgültig gezeigt
- [ ] Angenommen die Maßnahmensicherung hat nachgeladen, wenn das Ergebnis angezeigt wird, dann steht im Protokoll und in den Quellen „nachgeladen wegen Maßnahme [Vorgang]: [IDs]“; die endgültige Empfehlung und der Entwurf stammen aus dem zweiten Durchgang
- [ ] Angenommen die Maßnahmensicherung ist nicht möglich (Wiederholung schlägt fehl oder die nachzuladenden Dokumente passen nicht mehr in den Prompt), wenn das Ergebnis angezeigt wird, dann steht ein deutlicher Warnhinweis „Verbindliche Regeln zur Maßnahme [Vorgang] wurden nicht berücksichtigt: [IDs]“, und der Vorschlag gilt als nicht abgesichert
- [ ] Angenommen im zweiten Durchgang wird eine weitere, andere Maßnahme empfohlen, wenn dazu weitere verbindliche Dokumente fehlen, dann wird nicht erneut wiederholt, sondern der Hinweis aus dem vorigen Punkt angezeigt (höchstens eine Wiederholung je Analyse)
- [ ] Angenommen ein verbindliches Dokument im Geltungsbereich wurde nicht ausgelöst, wenn das Ergebnis angezeigt wird, dann steht es unter „Geprüft, nicht abgerufen“

### Ausschlüsse
- [ ] Angenommen ein Ausschlusswort steht im selben Satz wie der Auslöser, wenn der Abruf läuft, dann wird dieser Treffer unterdrückt; Treffer in anderen Sätzen bleiben bestehen
- [ ] Angenommen ein Fragesatz (Fragezeichen oder Frageform), wenn er einen Auslöser und ein Ausschlusswort enthält, dann wird der Treffer nicht unterdrückt
- [ ] Angenommen ein unterdrückter Treffer, wenn das Ergebnis angezeigt wird, dann steht er mit Satz und Ausschlusswort unter „Geprüft, nicht abgerufen“

### Bestellbezug und Bestellbedingungen
- [ ] Angenommen ein `when` mit mehreren Bestellbedingungen, wenn es ausgewertet wird, dann müssen **alle** Bestellbedingungen für **dieselbe** Bestellung zutreffen; Werte verschiedener Bestellungen werden nie vermischt (auch nicht innerhalb von `any_of` oder `not`)
- [ ] Angenommen das Kundenanliegen nennt eine Bestellnummer (aktuelle Nachricht, Stufe A), wenn der Abruf läuft, dann ist diese Bestellung die Bezugsbestellung, und nur sie wird für Bestellbedingungen herangezogen
- [ ] Angenommen genau eine Bestellung ist gewählt und keine andere wird genannt, wenn der Abruf läuft, dann ist diese die Bezugsbestellung
- [ ] Angenommen mehrere Bestellungen sind gewählt und das Anliegen nennt keine, wenn der Abruf läuft, dann werden alle gewählten als Kandidaten betrachtet; das gesamte `when` wird für jede Kandidatin getrennt ausgewertet: wahr, wenn **eine** Kandidatin es vollständig erfüllt; unbekannt, wenn keine wahr ist, aber mindestens eine unbekannt; falsch, wenn alle falsch sind; das Protokoll nennt die Bestellung und vermerkt „Bestellbezug unklar“
- [ ] Angenommen eine genannte Bestellnummer konnte nicht aus EOCS geladen werden, wenn die Bedingung ausgewertet wird, dann sind die Bestellbedingungen **unbekannt** (Folge wie bei jeder unbekannten Bedingung: bei `binding` vorsorglicher Abruf, bei `triggered` kein Abruf allein deshalb); eine andere gewählte Bestellung wird nicht stellvertretend herangezogen
- [ ] Angenommen eine Fallbedingung (Kundengruppe, Produkt, Ticket) in einem `when`, wenn es ausgewertet wird, dann wird sie einmal je Fall ausgewertet und gilt für jede Kandidatin gleich
- [ ] Angenommen eine Bedingung wurde bewertet, wenn das Ergebnis angezeigt wird, dann steht im Protokoll je Bedingung der Wert, die Bezugsbestellung und das Ergebnis (erfüllt, nicht erfüllt, unbekannt)

### Budget
- [ ] Angenommen die vorläufigen Budgetwerte (Zielumfang 40.000 Zeichen; Pflichtumfang bis 15.000 Zeichen Grundwissen plus bis 6.000 Zeichen je Produkt), wenn die Prüfung und der Abruf laufen, dann gelten diese Werte aus der Konfiguration; die endgültigen werden nach der Migration anhand echter Zahlen festgelegt
- [ ] Angenommen der Pflichtumfang ist reserviert, wenn der Restplatz verteilt wird, dann gilt die Reihenfolge stark ausgelöst, schwach ausgelöst, Beispiele; gekürzt wird nur von hinten
- [ ] Angenommen Wissen muss wegen Umfang ausgelassen werden, wenn das Ergebnis angezeigt wird, dann steht ein Hinweis „wegen Umfang nicht mitgesendet“ mit den IDs, und das Protokoll enthält sie
- [ ] Angenommen `core` und erforderliches `binding` überschreiten das Zielbudget, wenn die Analyse läuft, dann werden sie nicht gekürzt, und es erscheint eine deutliche Warnung
- [ ] Angenommen die harte Grenze, wenn sie berechnet wird, dann bezieht sie sich auf den **gesamten Prompt** der API-Anfrage: Kontextgrenze des Modells abzüglich Antwortreserve; der verfügbare Platz für Wissen ist der Rest nach Abzug der Systemanweisung, des Ticketverlaufs oder der Zusammenfassung, der Bestelldaten, des Mitarbeiterkontexts und der Platzhalter-Struktur (jeweils tatsächlich aufgebaut)
- [ ] Angenommen ein sehr langer Ticketverlauf, wenn der verfügbare Platz kleiner als das Zielbudget ist, dann verkleinert sich nur der Anteil des ausgelösten Wissens (schwach, dann stark); Pflichtumfang bleibt unangetastet
- [ ] Angenommen der Pflichtumfang passt zusammen mit den übrigen Teilen nicht in den Prompt, wenn die Analyse startet, dann bricht sie mit einer klaren Meldung ab und kürzt nichts („Wissen und Ticketverlauf zu umfangreich: Zusammenfassung verwenden oder Admin informieren“)
- [ ] Angenommen die Konfiguration, wenn Grenze und Antwortreserve eingestellt sind, dann gelten sie für das gewählte Modell und werden in Zeichen mit einem vorsichtigen Umrechnungsfaktor gerechnet; die Prüfung `knowledge:check` rechnet den Pflichtumfang gegen Grenze minus Antwortreserve minus Richtwert für Systemanweisung und Ticketdaten und meldet bei Überschreitung einen Fehler

### Protokoll und Ansicht
- [ ] Angenommen eine abgeschlossene Analyse, wenn sie gespeichert wird, dann enthält das Protokoll je abgerufenem Dokument Grund und Stufe, die Liste „geprüft, nicht abgerufen“, Unterdrückungen, Auslassungen, bewertete Bedingungen und den Umfang
- [ ] Angenommen das Ergebnis, wenn der Bereich Quellen geöffnet wird, dann zeigt er Abrufgrund je Dokument, „Geprüft, nicht abgerufen“ und „wegen Umfang nicht mitgesendet“
- [ ] Angenommen ein nicht abgerufenes Dokument, wenn der Mitarbeiter es zuschaltet und neu analysiert, dann ist es im Prompt, gekennzeichnet als von Hand zugeschaltet
- [ ] Angenommen eine ältere Analyse (vor dieser Funktion), wenn sie angezeigt wird, dann zeigt sie weiterhin ihre Quellen ohne neuen Bereich

### Befehle und Hilfen
- [ ] Angenommen `knowledge:select` mit `--text`, wenn er läuft, dann zeigt er je Dokument den Grund des Abrufs, die Stufe, Ausgelassenes, Unterdrücktes und den Gesamtumfang
- [ ] Angenommen `knowledge:overview`, wenn er läuft, dann listet er Grundwissen mit Zeichenzahl, vergebene Auslöser mit Mehrfachvergabe, die Schlüssel für `when` mit Datenquelle und Verfügbarkeit sowie die Zahl noch nicht klassifizierter Dokumente
- [ ] Angenommen der Authoring Guide und die Vorlagen, wenn ein Autor neues Wissen erfasst, dann verlangen sie die neuen Felder und die zugehörigen Fragen; ein Handbuch für den KI-Chat erklärt die Klassen in einer Seite

### Migration
- [ ] Angenommen ein Dokument ohne `retrieval` im Übergangsmodus, wenn eine Analyse läuft, dann zählt es vorläufig als `core`, und die Prüfung listet es als „noch nicht klassifiziert“ mit Zeichenzahl
- [ ] Angenommen alle Dokumente sind klassifiziert, wenn der Schalter „Pflichtfeld“ aktiviert wird, dann ist ein fehlendes `retrieval` ein Fehler und nicht mehr stillschweigend `core`
- [ ] Angenommen die Migration, wenn sie abgeschlossen ist, dann sind nur Frontmatter-Felder geändert, die Texte sind unverändert, `knowledge:check` hat keine Fehler, und der Umfang eines typischen Reklamationsfalls liegt unter 40.000 Zeichen
- [ ] Angenommen das Beispiel Logo-Angebot, wenn POLICY-017 klassifiziert ist, dann erscheint sie nicht im Prompt; bei der Frage nach einem geschützten Motiv erscheint sie zusammen mit PLAYBOOK-014

## Edge Cases
- **Sehr kurze Kundennachricht („ok danke“):** zusätzlich zählt die davorliegende Kundennachricht als aktuelles Anliegen.
- **Fremdsprachige Nachricht:** Auslöser sind in der Sprache des Dokuments gepflegt; liegt eine deutsche Übersetzung vor, wird sie zusätzlich durchsucht. Treffer in der Zusammenfassung zählen als frühere Stufe.
- **Kein Treffer, kein Pflichtumfang außer Grundwissen:** Die Analyse läuft; fehlt eine Regel, meldet die KI wie bisher eine Wissenslücke.
- **Kundengruppe „noch unklar“:** nur Dokumente ohne Einschränkung, wie bisher.
- **Mehrere Produkte:** Produktgrundlagen aller gewählten Produkte; Spezialdokumente jedes Produkts bei passendem Anliegen.
- **Testmodus-Läufe (PROJ-32):** Der Abruf berücksichtigt nur den zurückgespulten Verlauf.
- **Dokument im Status `draft`:** wird wie heute mitverwendet und gekennzeichnet.
- **Neue Datei fehlt noch im Repository-Stand der Analyse:** gilt wie bisher der Wissensstand der Analyse (Commit im Protokoll).
- **Gleiche Auslöser in `binding` und `triggered`:** beide werden abgerufen; bei Platznot gilt die Reihenfolge der Priorität.
- **Mehrere Bestellungen im Ticket:** Bestelldaten werden nie vermischt; maßgeblich ist die Bezugsbestellung oder, wenn der Bezug unklar ist, eine einzelne Kandidatin, die alle Bestellbedingungen gemeinsam erfüllt (siehe Bestellbezug).
- **Folgebestellung (Reklamationsauftrag „R1-…“) wird genannt:** sie wird wie jede andere Bestellung behandelt; das Vorhandensein von Folgebestellungen steht als eigene Bestellbedingung (`has_claim`, `claim_count`) zur Verfügung.

## Technical Requirements (optional)
- Der Abruf selbst ist deterministisch und reproduzierbar (gleicher Fall, gleicher Wissensstand, gleiches Ergebnis) und braucht keinen KI-Aufruf. Zusätzliche KI-Aufrufe entstehen nur durch die Maßnahmensicherung: höchstens ein weiterer Analyse-Aufruf je Analyse, und nur wenn für eine empfohlene Maßnahme verbindliche Dokumente fehlten.
- Abruf einer Analyse mit einigen hundert Dokumenten in unter 200 ms.
- Das Protokoll enthält keine Kundendaten außer den Sätzen unterdrückter Treffer, diese verschlüsselt im Analyseinhalt.
- Bestelldaten werden pro Bestellung ausgewertet und nie mit Daten anderer Bestellungen vermischt.
- Rückwärtsverträglich im Übergangsmodus; kein Datenverlust bei bestehenden Analysen.

## Open Questions
- [ ] Fachliche Freigabe der Startliste der `when`-Schlüssel (siehe Tabelle oben), insbesondere `shipped`, `days_since_shipped`, `delivery_confirmed` (Zuverlässigkeit der Sendungsdaten in EOCS) und `has_claim`.
- [ ] Endgültige Budgetwerte (Zielumfang, Pflichtumfang, harte Grenze) nach der Migration anhand echter Zahlen.
- [ ] Eignet sich die Satzgrenze für Ausschlüsse auch bei kurzen, unvollständigen Kundensätzen? Anhand der Tests beurteilen.
- [ ] Statusnamen aus EOCS sammeln, um `order_status` später sicher anbieten zu können.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Abruf vor dem KI-Aufruf statt Prompt-Anweisungen gegen ungefragte Hinweise | Das Problem ist die Menge im Prompt; eine Anweisung verdeckt es nur und wächst nicht mit | 2026-10-13 |
| Drei Klassen `core`, `triggered`, `binding` | Trennt immer nötiges, anlassbezogenes und verbindliches Wissen; passt zu den vorhandenen Dokumenttypen | 2026-10-13 |
| Produkt ist Geltungsbereich, kein Auslöser | Bei einem Produkt sollen nicht alle Dateien geladen werden, sondern nur Grundlagen und Passendes | 2026-10-13 |
| `binding` greift über Geltungsbereich, Bedingungen oder Auslöser, nie nur über Stichworte | Eine wichtige Regel darf nicht an einem fehlenden Stichwort scheitern | 2026-10-13 |
| Dreiwertige Logik (wahr/falsch/unbekannt); unbekannt bedeutet bei `binding` nur vorsorglichen Abruf mit Hinweis „Voraussetzung ungeklärt“, bei `triggered` keinen Abruf | Unklare Voraussetzungen dürfen keine verbindliche Entscheidung auslösen; die Regel soll aber verfügbar sein | 2026-10-13 |
| Maßnahmensicherung: empfiehlt die KI eine Maßnahme, deren verbindliche Regeln fehlten, wird einmal wiederholt | Verbindliche Regeln müssen auch greifen, wenn nicht der Kunde, sondern die KI die Maßnahme vorschlägt | 2026-10-13 |
| `triggered` ohne Auslöser und Bedingung nach der Migration ein Fehler | Ein solches Dokument könnte nie abgerufen werden | 2026-10-13 |
| Teillieferungen: Schlüssel sagen nur „mindestens eine Sendung“; `has_claim` nur „Folgebestellung gefunden“ | Die EOCS-Daten haben keine Positionszuordnung und das R-Schema ist nicht für alle Kanäle gesichert | 2026-10-13 |
| Harte Grenze gilt für den gesamten API-Prompt inklusive Ticketdaten und Antwortreserve | Nur so ist die Modellgrenze tatsächlich eingehalten | 2026-10-13 |
| Aktuelles Anliegen vor älteren Nachrichten | Alte Nachrichten sollen keinen Abruf auslösen, der zum jetzigen Fall nicht passt | 2026-10-13 |
| Gleiche Auslöser in mehreren Dokumenten erlaubt | Ein Begriff gehört oft zu mehreren Regeln; die Rangfolge entscheidet nur bei Platznot | 2026-10-13 |
| Ausschlüsse nur im selben Satz, nie in Fragesätzen, nie bei `binding` | Ausdrückliche Kundenfragen und Pflichtregeln dürfen nicht unterdrückt werden | 2026-10-13 |
| Pflichtumfang hat Vorrang vor ausgelöstem Wissen und wird nie gekürzt | Notwendige Regeln dürfen nicht stillschweigend fehlen | 2026-10-13 |
| Harte Grenze bricht ab statt zu kürzen | Besser eine klare Meldung als eine unvollständige Antwort | 2026-10-13 |
| Migration der 77 Dokumente schon in Phase 1, danach Pflichtfeld | Ein dauerhaftes „ohne Feld = Grundwissen“ würde die Entlastung verhindern | 2026-10-13 |
| Router als eigenes Feature PROJ-37 | Eigenständig testbar und deploybar; Phase 1 liefert den größten Hebel ohne KI-Aufruf | 2026-10-13 |
| Pest-Tests statt Evals; Abruftests gegen die echte Wissensbasis ohne KI | Entscheidung des Product Owners; Evals und Gold-Testfälle später | 2026-10-13 |
| Bestellbedingungen gelten für dieselbe Bestellung; Bezugsbestellung aus dem Anliegen, sonst einzelne Kandidatin | Keine Vermischung von Bestelldaten; ein Anliegen bezieht sich auf eine Bestellung | 2026-10-13 |
| Fehlende oder mehrdeutige Werte sind „unbekannt“, nie ein stilles „nein“ | Verhindert, dass eine Regel wegen fehlender Daten unbemerkt nicht greift | 2026-10-13 |
| Budgetwerte (40.000 / 15.000 + 6.000 je Produkt) und Warnung ab 25 Auslösern vorläufig akzeptiert | Endgültig nach der Migration an echten Zahlen | 2026-10-13 |
| Bestellwert und Zahlart nicht in der Startliste | Bewusst nicht aus EOCS übernommen (Datenschutz); Erweiterung nur nach eigener Prüfung | 2026-10-13 |
| Kein P0/P1 mehr | Entscheidung des Product Owners am 2026-10-12 | 2026-10-13 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|
| Metadaten in den Wissensdateien, keine neue Tabelle für Regeln | Autor pflegt Inhalt und Regeln gemeinsam, Git versioniert, der Fingerabdruck deckt die Felder ab | 2026-10-13 |
| Abruf deterministisch ohne KI (zweite Stufe auf dem Ergebnis von PROJ-4) | Reproduzierbar, billig, nachvollziehbar; PROJ-4 bleibt unverändert gültig | 2026-10-13 |
| Auslöser-Suche und Textbereinigung wie bei den Kundenbegriffen (PROJ-33) | Erprobtes Verfahren wiederverwenden, kein zweites Suchverfahren | 2026-10-13 |
| Bedingungsauswertung als eigener rechnender Baustein mit dreiwertiger Logik; Schlüsselliste in der Konfiguration | Eine Stelle für die Logik, vollständig testbar; Prüfung und Übersicht lesen dieselbe Liste | 2026-10-13 |
| Fallfakten und Bezugsbestellung einmal je Analyse festlegen | Gleiche Fakten für Abruf, Protokoll und Wiederholung; keine Vermischung | 2026-10-13 |
| Prompt-Teile ohne Wissen zuerst messen, Wissen zuletzt einpassen | Einzige Möglichkeit, die Modellgrenze bei langem Verlauf einzuhalten | 2026-10-13 |
| Grenzen in Zeichen mit Sicherheitsfaktor, Kalibrierung über gespeicherte Tokenzahlen | Einfach und robust; exakte Tokenzählung wäre ein neues Paket | 2026-10-13 |
| Maßnahmensicherung im bestehenden Analyse-Ablauf, höchstens eine Wiederholung | Nutzt die vorhandene Schleife; Kosten nur im Bedarfsfall | 2026-10-13 |
| Protokoll im verschlüsselten Analyseinhalt, Kennzahlen in Spalten | Unterdrückte Sätze enthalten Kundentext; Zahlen bleiben für PROJ-14 filterbar | 2026-10-13 |
| Manuelles Zuschalten im vorhandenen Neu-Analysieren-Formular | Kein neuer Ablauf; zugeschaltete Dokumente zählen zum Pflichtumfang des Durchgangs | 2026-10-13 |
| Prompt-Text „noch nicht versandt“ bei fehlenden Sendungen wird neutral gefasst, Prompt-Version steigt | Fehlende Sendungsdaten sind „unbekannt“, nicht „nicht versandt“ | 2026-10-13 |
| Migration: Lesebefehl, Vorschlagstabelle, reine Frontmatter-Änderung, dann Pflichtfeld | Kein Eingriff in fachliche Texte; Umschalten erst nach grüner Prüfung | 2026-10-13 |

---

## Tech Design (Solution Architect)

### Backend nötig?
Ja, überwiegend. Es gibt **keine neue Tabelle für Inhalte**: Die Regeln (Klasse, Auslöser, Bedingungen) stehen als Metadaten in den Wissensdateien im Repository, die Abrufentscheidung wird mit der Analyse gespeichert. Die Oberfläche wächst nur im Quellen-Bereich des Ergebnisses und im Formular „Neu analysieren“.

### A) Bausteine (Übersicht)
```
Gezielter Wissensabruf
+-- Wissensdateien (Repository, Markdown + Frontmatter)
|   +-- neue Felder: summary, retrieval, triggers, exclude_triggers, when, actions
+-- Prüfung (knowledge:check) und Werkzeuge für den Autor
|   +-- Fehler/Warnungen/Infos zu den neuen Feldern
|   +-- Pflichtumfang je Kundengruppe und Produkt gegen Budget und Modellgrenze
|   +-- knowledge:select --text, knowledge:overview (Grundwissen, Auslöser, Schlüssel, noch nicht klassifiziert)
+-- Fallfakten (je Analyse neu gesammelt)
|   +-- Texte in Stufen A (aktuelles Anliegen), B (jüngere Nachrichten, Zusammenfassung), C (Rest)
|   +-- Fallebene: Kundengruppe, Produkt, Ticketstatus und -alter, Anzahl Kundennachrichten, Anhang, Sprache
|   +-- Bestellebene: je Bestellung Kanal, Alter, Sendungen, Zustellung, Folgebestellungen, Mengen
|   +-- Bezugsbestellung (genannte Nummer, einzige Bestellung oder mehrere Kandidatinnen)
+-- Bedingungsauswertung (wahr / falsch / unbekannt)
+-- Abruf
|   +-- Stufe 1: Geltungsbereich (wie PROJ-4)
|   +-- Stufe 2: Klasse core / triggered / binding; Auslöser, Ausschlüsse, Bedingungen
|   +-- Rangfolge und Budget: Pflichtumfang zuerst, dann stark, schwach, Beispiele
|   +-- Prüfung gegen die Modellgrenze mit allen übrigen Prompt-Teilen
+-- Analyse-Ablauf
|   +-- Prompt mit Hinweisen "Voraussetzung nicht geprüft"
|   +-- Maßnahmensicherung: fehlende verbindliche Regeln zur empfohlenen Maßnahme nachladen, einmal wiederholen
+-- Protokoll (gespeichert mit der Analyse)
+-- Ergebnisansicht
    +-- Quellen: Abrufgrund je Dokument
    +-- "Geprüft, nicht abgerufen" mit Knopf "Zuschalten und neu analysieren"
    +-- Hinweise: wegen Umfang ausgelassen, Voraussetzung ungeklärt, Maßnahmensicherung
```

### B) Datenmodell (in Alltagssprache)
**Je Wissensdatei (Frontmatter):**
- `summary` – ein bis zwei Sätze; `retrieval` – Grundwissen, bei Bedarf oder verbindlich; `triggers` – Kundenwörter; `exclude_triggers` – Ausschlusswörter (nicht bei verbindlich); `when` – Bedingungen mit den Schlüsseln der Startliste; `actions` – Vorgänge, an die eine verbindliche Regel gebunden ist (gleiche Liste wie bei Arbeitsabläufen).
- Alles bleibt in Git versioniert; der Fingerabdruck je Dokument (PROJ-3) deckt die neuen Felder mit ab.

**Je Analyse (gespeichert verschlüsselt im bestehenden Analyseinhalt, wie die Quellen):**
- Abrufprotokoll: je Dokument Klasse, Grund (Auslöser mit Fundstelle und Stufe, Bedingung, Geltungsbereich, Maßnahme, von Hand zugeschaltet), Stufe stark/schwach.
- „Geprüft, nicht abgerufen“: Dokument, Grund (kein Auslöser, Bedingung falsch, unterdrückt durch Ausschluss mit Satz und Ausschlusswort).
- Ausgelassen wegen Umfang: IDs.
- Bewertete Bedingungen: Schlüssel, Wert, Bezugsbestellung, Ergebnis wahr/falsch/unbekannt; Vermerk „Bestellbezug unklar“.
- Maßnahmensicherung: ausgelöst ja/nein, Maßnahme, nachgeladene IDs, Ergebnis des ersten Durchgangs in Kurzform (empfohlene Maßnahmen).
- Umfang in Zeichen je Prompt-Teil (Wissen, Ticket, Bestellung, Anweisung) und die angewandten Grenzen.
- Neue Kennzahlen in eigenen Spalten, ohne Kundendaten, damit die Analysen-Übersicht (PROJ-14) später filtern kann: Zeichen des Wissens, Zahl abgerufener und ausgelassener Dokumente, Zahl der Durchgänge (1 oder 2).

**Je Neu-Analyse:** die von Hand zugeschalteten Dokument-IDs im gespeicherten Formular (wie die übrigen Eingaben).

**Konfiguration:** Klassen und Schlüssel der `when`-Liste mit Datenquelle und Verfügbarkeit, Budgets (Zielumfang, Pflichtumfang für Grundwissen und je Produkt), Modellgrenze, Antwortreserve, Umrechnungsfaktor, Mindestlänge der Auslöser, Warnschwelle für Auslöser, Schalter „Pflichtfeld“ für `retrieval` (Übergangsmodus).

### C) Ablauf einer Analyse
1. Fallfakten sammeln (Texte in Stufen, Fall- und Bestellfakten, Bezugsbestellung).
2. Geltungsbereich bestimmen (Kundengruppe, Produkt) – unverändert aus PROJ-4.
3. Je Dokument die Klasse auswerten: Grundwissen, Treffer der Auslöser (mit Ausschlüssen und Stufe), Bedingungen (dreiwertig); verbindliche Dokumente über Geltungsbereich, Bedingung, Auslöser oder Maßnahme.
4. Alle Teile des Prompts **außer dem ausgelösten Wissen** aufbauen und messen; daraus den verfügbaren Platz berechnen (Modellgrenze minus Antwortreserve minus übrige Teile). Pflichtumfang reservieren, danach stark, schwach, Beispiele einfügen; Ausgelassenes festhalten. Passt der Pflichtumfang nicht, mit klarer Meldung abbrechen.
5. Prompt zusammenstellen (verbindliche Dokumente mit ungeklärter Voraussetzung mit Hinweis) und die KI fragen (bestehender Ablauf einschließlich der Wiederholung bei ungültigem Ergebnis).
6. Maßnahmensicherung: empfohlene Maßnahmen mit den maßnahmengebundenen verbindlichen Dokumenten abgleichen; fehlen welche, nachladen und **einmal** wiederholen (Pflichtumfang des zweiten Durchgangs prüft dieselbe Grenze).
7. Ergebnis und Protokoll speichern; Kennzahlen in Spalten.

### D) Technische Entscheidungen (mit Begründung)
- **Metadaten in den Dateien statt in der Datenbank:** Der Autor pflegt Wissen und Regeln zusammen, Git zeigt jede Änderung, kein zweiter Ort zum Synchronisieren.
- **Abruf deterministisch, ohne KI:** reproduzierbar, billig, nachvollziehbar und unabhängig von Modelländerungen; das Sicherheitsnetz mit KI kommt als PROJ-37.
- **Zwei Stufen statt einer:** Die bestehende Auswahl nach Kundengruppe und Produkt (PROJ-4) bleibt unverändert die erste Stufe; die zweite Stufe arbeitet nur auf deren Ergebnis. Dadurch bleibt alles Bestehende gültig, und die Erweiterung ist abschaltbar (Übergangsmodus).
- **Auslöser-Suche wie bei den Kundenbegriffen (PROJ-33):** gleiches, erprobtes Verfahren (Wortanfang, Groß/Klein egal), gleiche Bereinigung von Zitaten und Signaturen; die Satzgrenze für Ausschlüsse ist eine einfache Zerlegung nach Satzzeichen.
- **Textstufen aus vorhandenen Bausteinen:** Kundennachrichten und Zusammenfassung liefern bereits `TicketContext` und `Summary`; es werden keine neuen Texte gespeichert.
- **Bedingungsauswertung als eigener, rein rechnender Baustein:** keine Datenbank, keine KI; dreiwertige Logik an einer Stelle und vollständig durch Tests abgedeckt. Die Liste der Schlüssel liegt in der Konfiguration (Quelle, Verfügbarkeit); Prüfung und Übersicht lesen dieselbe Liste.
- **Fallfakten einmal je Analyse gesammelt und unverändert benutzt:** dieselben Fakten für Abruf, Protokoll und Wiederholung; die Bezugsbestellung steht damit eindeutig fest und wird nie neu geraten.
- **Prompt-Teile zuerst messen, Wissen zuletzt einpassen:** Nur so lässt sich die Modellgrenze mit langem Ticketverlauf einhalten. Dafür muss das Zusammenstellen des Prompts aufgeteilt werden (Teile ohne Wissen, dann Wissen). Gerechnet wird in Zeichen mit vorsichtigem Faktor, nicht in exakten Tokens; die gemessenen Tokenzahlen der Analysen (bereits gespeichert) dienen zur Kalibrierung.
- **Pflichtumfang vorbeugend in der Prüfung:** Ein zu großes Grundwissen fällt beim Speichern der Datei auf, nicht erst beim Kunden.
- **Maßnahmensicherung im Analyse-Ablauf, höchstens eine Wiederholung:** nutzt die vorhandene Wiederholungsschleife; kostet nur dann einen weiteren Aufruf, wenn für die empfohlene Maßnahme verbindliche Regeln fehlten; die Tokenzahlen beider Durchgänge werden summiert, das gespeicherte Ergebnis ist das des letzten Durchgangs.
- **Protokoll im verschlüsselten Analyseinhalt, Kennzahlen in Spalten:** Sätze unterdrückter Treffer enthalten Kundentext und gehören verschlüsselt gespeichert; Zahlen ohne Kundenbezug bleiben für Auswertungen (PROJ-14) filterbar. Aufbewahrung wie bei den Analysen (12 Monate für Inhalte).
- **Manuelles Zuschalten über das Neu-Analysieren-Formular:** Es gibt bereits ein Formular, das die Eingaben der früheren Analyse übernimmt; dort kommen nur die ausgewählten Dokumente dazu. Kein neuer Ablauf, die zugeschalteten Dokumente zählen als Pflichtumfang des Durchgangs.
- **Korrektur einer bestehenden Aussage im Prompt:** Heute schreibt die Bestellübersicht bei fehlenden Sendungen „Versand: noch nicht versandt“. Das widerspricht der Regel „fehlende Sendungen sind unbekannt“; der Text wird neutral („keine Sendung in EOCS gefunden“) gefasst. Prompt-Version wird dabei angehoben, damit Analysen vergleichbar bleiben.
- **Migration als Vorschlagstabelle plus reine Frontmatter-Änderung:** Ein Lesebefehl liefert Größe, Typ und bisherige Metadaten aller Dokumente; die Klassifizierung wird als Tabelle geprüft und danach ohne Textänderung in die Dateien geschrieben. Der Schalter „Pflichtfeld“ wird erst nach einer grünen Prüfung aktiviert.
- **Bestehende Obergrenze von 60.000 Zeichen wird ersetzt:** Die neuen Budgets gelten statt ihrer; im Übergangsmodus gilt sie weiter.

### E) Dependencies
Keine neuen Pakete.

### F) Reihenfolge der Umsetzung
1. Metadaten lesen und prüfen (neue Felder, Fehler/Warnungen, Übergangsmodus, Konfiguration).
2. Fallfakten, Bezugsbestellung und Bedingungsauswertung (reine Rechenbausteine, einzeln testbar).
3. Abruf, Rangfolge, Budget, Prompt-Messung und Modellgrenze; Befehle `knowledge:select` und `knowledge:overview`.
4. Protokoll, Prompt-Hinweise, Maßnahmensicherung, Kennzahl-Spalten.
5. Oberfläche: Quellen-Bereich, „Geprüft, nicht abgerufen“, Zuschalten.
6. Authoring Guide, Vorlagen, Chat-Handbuch.
7. Klassifizierungslauf, Umschalten auf Pflichtfeld, Messung vorher/nachher.

### G) Risiken und offene Punkte
- Die Zeichen-Schätzung der Modellgrenze ist grob; sie wird anhand der gespeicherten Tokenzahlen geprüft und mit Sicherheitsabstand eingestellt.
- Die Satzzerlegung kann bei Mails ohne Satzzeichen zu lange „Sätze“ bilden; der Fall wird im Test beobachtet (offene Frage in der Spec).
- Die Maßnahmensicherung erhöht Kosten und Dauer einzelner Analysen; Häufigkeit wird über die neue Kennzahl „Durchgänge“ sichtbar.
- Das Aufteilen des Prompt-Aufbaus berührt `CaseAnalyzer` und damit PROJ-9; bestehende PROJ-9-Tests sind der Schutz.


## Implementation Notes – Backend

### Schritt 1: Metadaten lesen und prüfen (2026-10-13)
- **Felder:** `KnowledgeDocument` liest `summary`, `retrieval`, `triggers`, `exclude_triggers`, `when` und gibt sie über `summary()`, `retrieval()`, `triggers()`, `excludeTriggers()`, `when()` zurück; `actions` ist jetzt für alle Dokumenttypen bekannt (bisher nur Arbeitsabläufe).
- **Konfiguration:** `config/knowledge.php` → `retrieval` mit Klassen, Schalter `required` (Übergangsmodus, `.env`: `KNOWLEDGE_RETRIEVAL_REQUIRED`), Mindestlänge 4, Warnschwelle 25 und der Schlüsselliste `when_keys` (Ebene Fall/Bestellung, Typ, Quelle, Verfügbarkeit, Gruppen, für die der Wert nie bekannt ist). Die Liste ist die gemeinsame Quelle für Prüfung und später Übersicht und Auswertung.
- **Bedingungen:** neue Klasse `App\Knowledge\WhenSchema` prüft die Form von `when` (UND über alle Schlüssel, Werteliste = „einer davon“, `any_of`, `not`, Zahl oder `min`/`max`) und nennt die verwendeten Schlüssel. Die Auswertung selbst folgt in Schritt 2.
- **Prüfung (`KnowledgeValidator::retrieval()`):** Fehler bei unbekannter oder falsch geformter `retrieval`-Angabe, fehlendem Pflichtfeld (nur wenn `required`), falschem `when`, unbekannten `actions` und `exclude_triggers` bei `binding`; Warnungen bei fehlender `summary` (nur bei klassifizierten Dokumenten), kurzen Einträgen, mehr als 25 Auslösern, `triggered` ohne `triggers`/`when`/`actions` (nach Umschalten Fehler), `binding` nur mit `triggers`, Auslösern bei `core`, `exclude_triggers` ohne `triggers` und Schlüsseln, die für alle Kundengruppen des Dokuments nie bekannt sind. Gleiche Auslöser in mehreren Dokumenten bleiben ohne Meldung.
- **Noch offen aus den Kriterien dieses Bereichs:** Pflichtumfang gegen Budget (Schritt 3), „noch nicht klassifiziert“ und Mehrfachauslöser in Befehlen (Schritt 3).
- **Geänderter Test:** In `PROJ-30-ProcedureTest` galt `actions` als unbekanntes Feld außerhalb von Arbeitsabläufen; das ist durch PROJ-36 überholt und ersetzt.
- **Tests:** `tests/Feature/PROJ-36-RetrievalMetadataTest.php` (38 Tests); komplette Suite 974 grün; die echte Wissensbasis bleibt fehlerfrei.

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
