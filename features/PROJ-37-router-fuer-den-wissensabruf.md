# PROJ-37: Router für den Wissensabruf

## Status: Planned
**Created:** 2026-10-13
**Last Updated:** 2026-10-13

## Dependencies
- Requires: PROJ-36 (Gezielter Wissensabruf) – der deterministische Abruf bleibt die Basis; der Router ergänzt sein Ergebnis. Genutzt werden `KnowledgeSelector::scope()`, `KnowledgeRetriever`, `TriggerMatcher`, die Textstufen A/B/C (`CaseFacts`), das Budget, die Maßnahmensicherung und das Protokoll `retrieval`.
- Requires: PROJ-9, PROJ-11 (Fallanalyse, Protokoll) – der Router läuft vor dem ersten Durchgang der Analyse; seine Entscheidungen werden im Analyse-Protokoll gespeichert.
- Requires: PROJ-10 (Ergebnisansicht) – Anzeige der Herkunft und der Begründungen im Bereich „Wissensabruf“.
- Requires: PROJ-14 (Analysen-Übersicht) – Kennzahlen und Filter für Admins.
- Requires: PROJ-28 (Übersetzung) – vorhandene deutsche Übersetzungen gehen zusätzlich zum Original an den Router; es gibt keinen eigenen Übersetzungsaufruf.
- Verwandt: PROJ-32 (Testmodus) – der Router sieht nur den zurückgespulten Verlauf; PROJ-22 (semantisches Retrieval mit Embeddings, spätere Ausbaustufe der Vorauswahl); PROJ-13 (automatische Evaluation, übernimmt später das Live-Prüfset); PROJ-2 (Authoring Guide, Handbuch, Skill).

## Ausgangslage
PROJ-36 ruft Wissen über Auslöser (`triggers`), Bedingungen (`when`) und Maßnahmen ab. Das funktioniert nur, wenn Kunden so schreiben, wie die Auslöser es vorsehen. Der **Eisberg-Fall** zeigt die Lücke:

> „leider ist das Motiv, welches eingelasert wurde, sehr klein und der Eisberg ist auch etwas anders als unser Muster. Das Bild sieht irgendwie sehr verloren darin aus. Das Motiv in unserem aktuellen Eisberg L (vom Kunden) ist sogar etwas kleiner als unser Musterbild im Eisberg M. Bilder im Anhang - der Auftrag ist natürlich wieder einmal eilig“

Kundengruppe Foto-Fachhändler / Reseller, Produkt `3d-glass-photo`. Die Dateien PRODUCT-006 (Motivgröße, Platzierung, räumliche Tiefe), PLAYBOOK-018 (Motiv wirkt zu klein oder verloren) und PLAYBOOK-019 (subjektive Beanstandung durch Foto-Fachhändler) lagen vor und sind im Repository. Gemessen am 2026-10-13 mit `knowledge:select`: Es kam nur Grundwissen (POLICY-004, PRODUCT-003, TONE-002); die drei Dateien fehlten, weil „sehr klein“ und „verloren darin“ nicht zu „zu klein“ und „verloren im Glas“ passen. Mit den drei von Hand zugeschalteten Dateien wurde der Antwortvorschlag deutlich besser.

Die Kundensprache ist vielfältig und nicht vorhersehbar. Es darf nicht nötig sein, jede Formulierung als Auslöser in die Wissensdateien zu schreiben. PROJ-37 ergänzt den deterministischen Abruf um einen **KI-Router**, der sinngleiche Umschreibungen, indirekte Beanstandungen und umgangssprachliche Ausdrücke erkennt, ohne wahllos Wissen in den Prompt zu laden.

**Qualitätsziel:** Möglichst wenig relevantes Wissen übersehen, ohne dafür große Mengen irrelevanten Wissens mitzusenden. Grundsatz: Lieber ein gelegentlich unnötiger, günstiger Router-Aufruf als regelmäßig übersehenes relevantes Wissen.

## User Stories
- Als **Aushilfe oder Vertretung** möchte ich, dass die App auch dann das passende Wissen heranzieht, wenn ein Kunde sein Anliegen ungewöhnlich beschreibt, damit mein Antwortvorschlag nicht an fehlenden Stichwörtern scheitert.
- Als **Stammkraft** möchte ich bei jedem Dokument sehen, ob es nach Auslösern oder vom KI-Router gewählt wurde und warum, damit ich den Vorschlag einordnen und bei Bedarf Wissen von Hand zuschalten kann.
- Als **Stammkraft** möchte ich bei einem Ausfall des Routers einen dezenten Hinweis bekommen, damit ich den Vorschlag besonders sorgfältig prüfe, ohne in meiner Arbeit gesperrt zu sein.
- Als **Administrator** möchte ich sehen, welche Dokumente PROJ-36 und welche der Router gewählt hat, mit Kandidatenpool, verworfenen Vorschlägen, erkannten Fallarten, Modell, Version, Tokens, Laufzeit und Kosten, damit ich Nutzen, Kosten und Fehlgriffe beurteilen kann.
- Als **Administrator** möchte ich in der Analysen-Übersicht Router-Ausführungen und -Ausfälle zählen und filtern, damit ich Ausfallquote und zusätzliche Wartezeit im Betrieb sehe.
- Als **Autor von Wissen** (mit dem KI-Chat) möchte ich eine klare Anleitung, wie eine `summary` zu schreiben ist, damit der Router mein Dokument auch bei ungewöhnlichen Kundenformulierungen findet.
- Als **Geschäftsführung/Entwickler** möchte ich den Router mit einem Schalter jederzeit abschalten können und dann exakt das Verhalten von PROJ-36 haben, damit ich Qualität und Kosten sicher steuere.

## Out of Scope
- **Admin-Funktion „Wissensauswahl mit KI prüfen“**, die nachträglich Ursachen analysiert und Änderungen an Wissensdateien vorschlägt – bewusst nicht Teil von PROJ-37.
- **Router, der Dateien ändert, neue Regeln erfindet oder Unstimmigkeiten im Wissen eigenständig korrigiert.** Er wählt nur aus vorhandenen Dokumenten aus.
- **Embeddings und semantische Vektorsuche** – PROJ-22. Die Grenze der einfachen Vorauswahl bei sehr großer Wissensbasis wird dokumentiert.
- **Automatische, umfangreiche Evaluation** (Gold-Testfälle) – PROJ-13. PROJ-37 liefert nur das kleine Live-Prüfset auf Abruf.
- **Eigener Übersetzungsaufruf für den Router** – es werden nur vorhandene Übersetzungen (PROJ-28) genutzt.
- **Kategorien als Ausschlussfilter** – `categories` schließen nie ein Dokument aus.
- **Zwischenspeichern von Router-Ergebnissen** – „Erneut analysieren“ führt den Router neu aus.
- **Pauschales Umschreiben der 77 vorhandenen `summary`-Texte** – es wird nur gezielt nachgebessert, wo das Live-Prüfset ein Dokument als „unsichtbar“ zeigt.
- **Wiederholungsversuche (Retry) bei Router-Ausfall.**
- **Ein eigenes Router-Teilbudget** – das Gesamtbudget von PROJ-36 gilt.
- **Konkrete Wahl von Modell, Algorithmus der Vorauswahl, Format der Router-Antwort und Budget-Strategie** – `/architecture`.

## Begriffe
- **Pool:** die Dokumente, die der Router prüfen darf: alle verwendbaren Dokumente im Geltungsbereich (Kundengruppe, Produkt, `draft`/`active`, ohne Prüffehler), die PROJ-36 **nicht** abgerufen hat und die nicht von Hand zugeschaltet sind.
- **Katalogzeile:** ID, Titel und `summary` eines Dokuments; die einzigen Dokumentangaben, die der Router sieht.
- **Router-Ergänzung:** ein Dokument, das der Router aus dem Pool vorschlägt und das übernommen wird.
- **Herkunft:** „Grundwissen“, „Durch Auslöser oder Bedingung ausgewählt“, „Vom KI-Router ergänzt“, „Manuell zugeschaltet“.
- **Entscheidung des Routers:** ausgeführt, regulär übersprungen (mit Grund), deaktiviert oder fehlgeschlagen (mit Grund).

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Eisberg-Regressionstest (verbindlich)
- [ ] Angenommen die Originalnachricht des Foto-Fachhändlers (Kundengruppe Reseller, Produkt `3d-glass-photo`) ohne Mitarbeiterkontext und ohne Mitarbeiterhinweis, wenn die Analyse mit aktivem Router läuft, dann stehen PRODUCT-006, PLAYBOOK-018 und PLAYBOOK-019 in der **endgültigen** Wissensauswahl der Analyse, ohne dass sie von Hand zugeschaltet wurden
- [ ] Angenommen der Eisberg-Fall im Live-Prüfset, wenn er fünfmal nacheinander läuft, dann enthält die endgültige Auswahl in jedem der fünf Läufe alle drei Dokumente (Mindestprüfung vor der Aktivierung, keine Garantie allgemeiner Fehlerfreiheit)
- [ ] Angenommen der Eisberg-Fall, wenn die Auswahl mit PROJ-36 allein berechnet wird, dann fehlen die drei Dokumente (Nachweis, dass der Router den Unterschied macht)

### Pool und Vorauswahl
- [ ] Angenommen ein verwendbares Dokument im Geltungsbereich ohne jeden Trigger-Treffer, wenn der Pool gebildet wird, dann ist es im Pool
- [ ] Angenommen der Pool ist kleiner oder gleich der konfigurierten Obergrenze (Richtwert 60 Katalogzeilen bzw. 15.000 Zeichen), wenn der Router läuft, dann erhält er alle Katalogzeilen des Pools
- [ ] Angenommen der Pool überschreitet die Obergrenze, wenn die Vorauswahl läuft, dann werden die Kandidaten nach einfacher Textähnlichkeit zwischen Kundentext und Katalogzeile (Titel, `summary`, `topics`, Trigger) gereiht und gekürzt; ein **Trigger-Treffer ist dafür keine Bedingung**
- [ ] Angenommen ein Pool über der Obergrenze mit vielen thematisch ähnlichen und vielen irrelevanten Zusatzdokumenten, wenn die Vorauswahl läuft, dann wird **getrennt von der Router-Entscheidung** geprüft, ob das relevante Dokument in die begrenzte Vorauswahl gelangt; die Tests enthalten Fälle mit geringer Wortähnlichkeit
- [ ] Angenommen die Grenzen der einfachen Vorauswahl bei stark wachsender Wissensbasis, wenn sie in den Tests sichtbar werden, dann sind sie als bekannte Grenze in der Spec dokumentiert; die spätere Verbesserung durch PROJ-22 bleibt möglich
- [ ] Angenommen Obergrenze für Zeilen und Zeichen, wenn sie in der Konfiguration geändert werden, dann gelten die neuen Werte ohne Codeänderung
- [ ] Angenommen ein Dokument mit Status `deprecated` oder mit Prüffehlern oder außerhalb von Kundengruppe oder Produkt, wenn der Pool gebildet wird, dann ist es nicht im Pool
- [ ] Angenommen ein bereits von PROJ-36 abgerufenes oder von Hand zugeschaltetes Dokument, wenn der Pool gebildet wird, dann ist es nicht im Pool, aber der Router erfährt seine ID und seinen Titel, damit er nichts doppelt ergänzt

### Text für den Router
- [ ] Angenommen ein Ticket, wenn der Router-Text gebildet wird, dann enthält er die Stufen A und B von PROJ-36 (Original, bei fremdsprachigen Nachrichten zusätzlich die vorhandene deutsche Übersetzung, klar als „Original“ und „Übersetzung“ gekennzeichnet), Kundengruppe und Produkt; Stufe C, strukturierte Bestelldaten und Dokumentvolltexte gehen nicht mit
- [ ] Angenommen Personalisierungstexte aus EOCS in Stufe B, wenn der Router-Text gebildet wird, dann sind nur Textinformationen enthalten, die für die Wissensauswahl relevant sein können; keine zusätzlichen personenbezogenen Daten
- [ ] Angenommen der Router-Text, wenn er gebildet wird, dann sind E-Mail-Adressen, Telefonnummern und Anschriften in Original **und** Übersetzung ersetzt, wie bei der Analyse
- [ ] Angenommen ein Mitarbeiterhinweis liegt vor, wenn der Router-Text gebildet wird, dann zählt er zur Stufe A; der Eisberg-Test läuft aber ohne ihn
- [ ] Angenommen der Router-Text überschreitet die konfigurierte Obergrenze, wenn gekürzt wird, dann geht Stufe A vor Stufe B, und jede Kürzung steht im Protokoll und im Hinweis; nichts geht stillschweigend verloren
- [ ] Angenommen die aktuelle Nachricht verweist ausdrücklich zurück („wie bereits beschrieben“, „das Problem besteht weiterhin“), wenn der Router ein Thema aus dem früheren Verlauf ergänzt, dann darf er es als „aktuelles Anliegen“ einstufen; ohne Rückbezug gilt ein Thema aus Stufe B als „früherer Verlauf“; die Einstufung steht im Protokoll

### Wann der Router läuft
- [ ] Angenommen der Router ist aktiv und der gezielte Abruf (PROJ-36) ist aktiv, wenn eine Analyse startet, dann läuft der Router **einmal vor dem ersten Durchgang**, unabhängig davon, wie viele Dokumente PROJ-36 gefunden hat
- [ ] Angenommen der Schalter `KNOWLEDGE_ROUTER_ACTIVE` ist aus oder der gezielte Abruf ist aus, wenn eine Analyse läuft, dann gilt exakt das Verhalten von PROJ-36 (Rollback); die Entscheidung „deaktiviert“ wird protokolliert
- [ ] Angenommen eine eindeutig inhaltsleere Nachricht („Vielen Dank!“), wenn eine Analyse startet, dann entfällt der Router, die Entscheidung „regulär übersprungen: ohne Sachinhalt“ wird protokolliert und es erscheint kein Hinweis im Ergebnisbereich
- [ ] Angenommen eine kurze Nachricht mit Sach- oder Rückbezug („Das Problem besteht weiterhin.“, „So hatte ich mir das nicht vorgestellt.“, „Danke, aber das Motiv ist immer noch zu klein.“, „Anbei die gewünschten Fotos.“ bei einem offenen Anliegen), wenn eine Analyse startet, dann läuft der Router; im Zweifel läuft er
- [ ] Angenommen der Pool ist leer, wenn eine Analyse startet, dann entfällt der Router mit dem Protokolleintrag „regulär übersprungen: kein zusätzliches Dokument im Geltungsbereich“, ohne Hinweis im Ergebnisbereich
- [ ] Angenommen „Erneut analysieren“, wenn der Mitarbeiter es auslöst, dann läuft der Router neu (kein Zwischenspeichern)
- [ ] Angenommen der Testmodus (PROJ-32) ist aktiv, wenn der Router läuft, dann sieht er nur den zurückgespulten Verlauf
- [ ] Angenommen die Maßnahmensicherung von PROJ-36 lädt nach und rechnet einmal neu, wenn der zweite Durchgang läuft, dann behält er die Router-Ergänzungen und ruft den Router nicht erneut auf

### Ergänzen und Verdrängen
- [ ] Angenommen der Router hat Dokumente vorgeschlagen, wenn das Ergebnis zusammengeführt wird, dann entfernt der Router **kein** Dokument, das PROJ-36 abgerufen hat
- [ ] Angenommen Router-Ergänzungen und PROJ-36-Treffer, wenn die gemeinsame Budgetplanung läuft, dann gelten dieselben Stufen wie in PROJ-36: Ergänzung auf Grund des aktuellen Anliegens = stark, früherer Verlauf ohne Rückbezug = schwach; deterministische Treffer stehen innerhalb derselben Stufe vor Router-Ergänzungen
- [ ] Angenommen eine starke Router-Ergänzung und ein schwacher deterministischer Treffer, wenn das Budget nicht für beide reicht, dann darf die Ergänzung den schwachen Treffer **bei der Budgetplanung** verdrängen; dieses Verdrängen ist vom aktiven Entfernen durch den Router zu unterscheiden und im Protokoll als „wegen Umfang nicht mitgesendet“ sichtbar
- [ ] Angenommen Pflichtwissen (`core`, verbindliche Regeln) und von Hand zugeschaltetes Wissen, wenn das Budget geplant wird, dann bleiben beide geschützt und werden nie von Router-Ergänzungen verdrängt
- [ ] Angenommen das Gesamtbudget von PROJ-36, wenn Router-Ergänzungen hinzukommen, dann gibt es kein zweites, eigenes Router-Budget
- [ ] Angenommen ein einzelnes sehr umfangreiches Router-Dokument, wenn das Restbudget nicht für es reicht, dann darf es kleinere relevante Dokumente nicht verdrängen, die noch ins Restbudget passen; die Reihenfolge kann dafür sinnvoll angepasst werden, jede Auslassung steht im Protokoll

### Obergrenze und Sicherheitsgrenzen
- [ ] Angenommen eine konfigurierte Obergrenze für Router-Ergänzungen je Analyse (Richtwert 6), wenn der Router mehr Dokumente vorschlägt, dann bleiben die wichtigsten nach der Rangfolge des Routers, die übrigen stehen im Protokoll als „vorgeschlagen, wegen Obergrenze nicht übernommen“
- [ ] Angenommen der Router nennt eine unbekannte ID, ein Duplikat, ein bereits abgerufenes Dokument oder ein Dokument außerhalb des Pools, wenn das Ergebnis geprüft wird, dann wird es verworfen und mit Grund protokolliert; die Analyse läuft weiter
- [ ] Angenommen ein semantisch passendes Dokument, dessen Geltungsbereich, `when`-Bedingung, Maßnahmenbindung oder ein anderer verbindlicher Schutzmechanismus im konkreten Fall nicht erfüllt ist, wenn der Router es vorschlägt, dann wird es **nicht** ergänzt; semantische Ähnlichkeit allein genügt nie, um eine verbindliche Anwendungsvoraussetzung zu umgehen; die Ablehnung steht im Protokoll
- [ ] Angenommen verbindliche Regeln und Maßnahmenbindung, wenn der Router läuft, dann umgeht er sie nicht: die Maßnahmensicherung, der Vorrang von `core` und `binding`, das Budget und die Regelprüfung von PROJ-36 bleiben unverändert
- [ ] Angenommen der Router, wenn er arbeitet, dann ändert er keine Wissensdatei und erfindet keine Regel; er liefert nur IDs aus dem Pool, eine Begründung und Fallarten

### Fallarten (`categories`)
- [ ] Angenommen der Router erkennt Fallarten, wenn er antwortet, dann liefert er eine Liste aus den bekannten Kategorien; bei mehreren Anliegen mehrere, bei uneindeutiger Anfrage darf die Liste leer sein
- [ ] Angenommen erkannte Fallarten, wenn die Rangfolge bei sonst ähnlich relevanten Dokumenten entschieden wird, dann hat eine passende Kategorie allenfalls nachrangigen Einfluss; ein inhaltlich klar passendes Dokument wird nie wegen seiner Kategorie benachteiligt, und kein Dokument wird wegen seiner Kategorie ausgeschlossen
- [ ] Angenommen die Verwendung der Kategorien in der Rangfolge erzeugt Aufwand oder Qualitätsrisiko, wenn `/architecture` das bewertet, dann dürfen die Kategorien zunächst nur erkannt und protokolliert werden
- [ ] Angenommen die Fallkategorie der Analyse (PROJ-9), wenn der Router Fallarten liefert, dann bleibt sie davon unberührt
- [ ] Angenommen ein Dokument mit anderer Kategorie als die erkannte Fallart, ein Dokument ohne `categories` und eine falsch oder unvollständig erkannte Fallart, wenn die Auswahl läuft, dann werden fachlich relevante Dokumente weiterhin ausgewählt

### Ausfall und Rückfall
- [ ] Angenommen ein Zeitlimit (Richtwert 15 Sekunden, konfigurierbar) ohne automatischen Wiederholungsversuch, wenn der Router länger braucht, dann läuft die Analyse mit der PROJ-36-Auswahl weiter
- [ ] Angenommen Zeitüberschreitung, API-Fehler oder ungültige Router-Antwort, wenn das Ergebnis angezeigt wird, dann steht im Ergebnisbereich ein dezenter Hinweis „Erweiterte Wissenssuche nicht verfügbar. Der Antwortvorschlag wurde mit der regulären Wissensauswahl erstellt. Möglicherweise wurde weiteres relevantes Wissen nicht berücksichtigt. Bitte den Vorschlag besonders sorgfältig prüfen.“ – kein roter Warnbanner, keine Kopiersperre
- [ ] Angenommen ein Ausfall, wenn der Bereich „Wissensabruf“ geöffnet wird, dann nennt er den Ausfallgrund (Zeitüberschreitung, API-Fehler, ungültige Antwort)
- [ ] Angenommen der Router wurde regulär übersprungen oder ist deaktiviert, wenn das Ergebnis angezeigt wird, dann erscheint kein Ausfallhinweis
- [ ] Angenommen der Router fällt aus, wenn die Analyse weiterläuft, dann bleiben alle Sicherungen und Kopiersperren von PROJ-36 unverändert wirksam
- [ ] Angenommen eine Analyse mit Router, wenn sie gemessen wird, dann ist die zusätzliche Wartezeit im Protokoll nachvollziehbar (Laufzeit des Routers getrennt von der Analyse)

### Nachvollziehbarkeit und Anzeige
- [ ] Angenommen das Protokoll einer Analyse, wenn sie gespeichert wird, dann enthält es die Entscheidung des Routers (ausgeführt / regulär übersprungen mit Grund / deaktiviert / fehlgeschlagen mit Grund), Pool und Obergrenze, Vorschläge, Übernahmen, Verworfenes mit Grund, erkannte Fallarten mit Begründung, Rückbezug-Einstufung, Router-Version, Modell, Tokens, Laufzeit und – soweit zuverlässig ermittelbar – Kosten
- [ ] Angenommen das Ergebnis, wenn der Bereich „Wissensabruf“ geöffnet wird, dann zeigt jedes Dokument seine Herkunft: „Grundwissen“, „Durch Auslöser oder Bedingung ausgewählt“, „Vom KI-Router ergänzt“ oder „Manuell zugeschaltet“; die Darstellung baut auf dem bestehenden PROJ-36-Protokoll auf
- [ ] Angenommen eine Router-Ergänzung, wenn sie angezeigt wird, dann steht eine kurze, fachlich verständliche Begründung, ausdrücklich als „KI-Einschätzung“ gekennzeichnet („KI-Einschätzung: Dieses Dokument wurde ergänzt, weil die Kundenbeschreibung auf eine Beanstandung der Motivgröße hindeutet.“), nie als technisch nachgewiesene Ursache; die Begründung ist unabhängig von der Kundensprache deutsch
- [ ] Angenommen ein Admin, wenn er das Ergebnis öffnet, dann sieht er zusätzlich Kandidatenpool, verworfene Vorschläge mit Gründen, erkannte Fallarten mit Begründung, Modell, Router-Version, Tokens, Laufzeit, Kosten (soweit ermittelbar), Ausführungsstatus und Ausfallgrund; andere Mitarbeiter sehen diese technischen Details nicht
- [ ] Angenommen die Analysen-Übersicht (Admin), wenn sie geöffnet wird, dann zeigt sie Kennzahlen und einen Filter für die Router-Entscheidung (ausgeführt, regulär übersprungen, deaktiviert, fehlgeschlagen) sowie durchschnittliche Ergänzungen je Analyse, zusätzlichen Umfang, Ausfallquote, Laufzeit und Kosten; diese Kennzahlen erscheinen nur dort
- [ ] Angenommen das manuelle Zuschalten unter „Geprüft, nicht abgerufen“ mit neuer Analyse, wenn der Router aktiv ist, dann funktioniert es für Mitarbeiter unverändert; zugeschaltetes Wissen trägt die Herkunft „Manuell zugeschaltet“
- [ ] Angenommen eine ältere Analyse ohne Router, wenn sie angezeigt wird, dann zeigt sie ihre Quellen wie bisher, ohne neue Angaben

### Sprachen
- [ ] Angenommen ein fremdsprachiger Fall (mindestens Italienisch und Französisch), wenn der Router mit und ohne vorhandene deutsche Übersetzung läuft, dann sind die erwarteten Dokumente in beiden Varianten dieselben; eine Übersetzung ist keine Voraussetzung
- [ ] Angenommen Original und Übersetzung weichen inhaltlich voneinander ab, wenn der Router entscheidet, dann ist das Original maßgeblich
- [ ] Angenommen ein fremdsprachiger Fall, wenn die Analyse eine Kundenantwort erzeugt, dann bleibt sie in der Sprache des Kunden; nur die Router-Begründung ist deutsch

### Qualitätssicherung (Pest, simulierte Router-Antworten, in der normalen Suite)
- [ ] Angenommen der Eisberg-Fall mit fester Router-Antwort, wenn die Auswahl berechnet wird, dann stehen die drei Dokumente in der endgültigen Auswahl, und der Umfang bleibt unter dem Zielbudget
- [ ] Angenommen unterschiedliche Formulierungen desselben Anliegens, ein Dokument ohne einzigen Trigger-Treffer, thematisch ähnliche aber nicht einschlägige Dokumente, ein Fall ohne passendes Zusatzwissen, unklare Kundengruppe, fehlende Produktzuordnung, ein fremdsprachiger Fall und ein API-Ausfall, wenn die Tests laufen, dann werden jeweils die erwarteten und die verbotenen IDs geprüft, und der Rückfall auf PROJ-36 funktioniert
- [ ] Angenommen eine unklare Kundengruppe oder ein fehlendes Produkt, wenn der Router läuft, dann umfasst der Pool nur Dokumente ohne Einschränkung beziehungsweise des gewählten Bereichs, wie in PROJ-36
- [ ] Angenommen die Auswahl mit Router, wenn sie gemessen wird, dann werden durchschnittliche Ergänzungen je Analyse, zusätzlicher Umfang, der Anteil starker Ergänzungen, die einen schwachen Treffer ersetzen, und Tokenverbrauch, Kosten und Laufzeit erfasst

### Live-Prüfset gegen das echte Modell (auf Abruf, nicht in der normalen Suite)
- [ ] Angenommen das Live-Prüfset, wenn es läuft, dann enthält es für jeden Fall erwartete **und** verbotene Knowledge-IDs; der Eisberg-Originalfall, mehrere sinngleiche Umformulierungen, thematisch ähnliche aber nicht einschlägige Fälle, ein Fall ohne passendes Dokument (richtige Antwort: keine Ergänzung) und fremdsprachige Fälle (Italienisch und Französisch, je mit und ohne Übersetzung)
- [ ] Angenommen die Positivfälle, wenn sie laufen, dann sind die fachlich erwarteten Dokumente in der endgültigen Auswahl; bei den Umformulierungen genügt die Erwartung der tatsächlich relevanten Dateien
- [ ] Angenommen die Negativfälle, wenn sie laufen, dann kommt keine verbotene ID in die Auswahl (zum Beispiel ergänzt eine reine Helligkeitsreklamation nicht PLAYBOOK-018); eine niedrige durchschnittliche Zahl von Ergänzungen allein reicht als Erfolgskriterium nicht
- [ ] Angenommen dieselben Fälle, wenn sie laufen, dann werden die PROJ-36-Auswahl und die endgültige PROJ-37-Auswahl gegenübergestellt: zusätzlich gefundene relevante und hinzugekommene irrelevante Dokumente
- [ ] Angenommen jeder Live-Lauf, wenn er endet, dann sind Modell, Router-Anweisung beziehungsweise -Version, Wissensstand, erwartete und tatsächliche IDs, Tokens, Laufzeit und Kosten (soweit ermittelbar) protokolliert
- [ ] Angenommen das Live-Prüfset, wenn es Testtexte enthält, dann sind es ausschließlich anonymisierte oder selbst formulierte Anfragen (der Eisberg-Text enthält keine personenbezogenen Daten)
- [ ] Angenommen ein Dokument wird trotz korrekten Inhalts wegen einer unzureichenden `summary` nicht gefunden, wenn das Live-Prüfset es zeigt, dann wird diese `summary` gezielt verbessert, nicht der ganze Bestand
- [ ] Angenommen die Aktivierung von PROJ-37 im laufenden Kundenservice, wenn sie ansteht, dann liegen die Ergebnisse des Live-Prüfsets vor und sind vom Product Owner fachlich freigegeben

### Autorenseite und Prüfung
- [ ] Angenommen der Authoring Guide, das Chat-Handbuch und der Knowledge-Skill, wenn ein Autor eine `summary` schreibt, dann verlangen sie: das typische Kundenanliegen in Alltagssprache, wofür das Dokument fachlich gebraucht wird, Abgrenzungen **nur** wo sinnvoll und vorhanden (nie Pflicht), und eine Formulierung, die auch ungewöhnliche Kundenformulierungen semantisch zuordenbar macht
- [ ] Angenommen eine Abgrenzung in einer `summary`, wenn der Guide sie erklärt, dann führt er aus, dass sie bei Mehrfachanliegen kein relevantes Dokument ausschließen darf („nicht für ausschließlich Helligkeitsreklamationen“ statt „nicht für Helligkeit“), mit Positiv- und Negativbeispielen aus dem Bestand
- [ ] Angenommen eine `summary` unter dem vorläufigen Richtwert von 60 Zeichen, wenn `knowledge:check` läuft, dann erscheint eine Warnung (kein Fehler)
- [ ] Angenommen eine `summary` ohne sprachlich erkennbare Abgrenzung, wenn `knowledge:check` läuft, dann erscheint **keine** Warnung (verhindert künstliche Ausschlüsse)
- [ ] Angenommen die Katalogzeile des Routers, wenn sie gebildet wird, dann enthält sie ID, Titel und `summary`, keine Trigger; ob `topics` hinzukommen, bewertet `/architecture`, die Zeile bleibt kompakt

### Schalter, Konfiguration und Version
- [ ] Angenommen `KNOWLEDGE_ROUTER_ACTIVE` (Standard aus, nur wirksam bei aktivem PROJ-36), wenn er geändert wird, dann wirkt er ohne Codeänderung und ist der schnelle Rückfall auf PROJ-36
- [ ] Angenommen eine Analyse mit Router, wenn sie gespeichert wird, dann tragen Prompt-Version beziehungsweise Router-Version den Router-Stand, damit spätere Analysen reproduzierbar bleiben
- [ ] Angenommen die Aufrufbegrenzung für KI-Aufrufe (PROJ-9), wenn der Router aufgerufen wird, dann bleibt sie nachvollziehbar und der Router-Aufruf löst keine zusätzliche Sperre für den Mitarbeiter aus, die die Analyse unnötig blockiert (Details `/architecture`)

## Edge Cases
- **Extrem lange aktuelle Nachricht:** Überschreitet bereits Stufe A allein die konfigurierte Textgrenze des Routers, darf es weder eine stille Kürzung wichtiger Inhalte noch ein unkontrolliertes Überschreiten der Grenzen geben. Das konkrete Verhalten legt `/architecture` fest und protokolliert es nachvollziehbar (denkbar: begrenzt und gekennzeichnet kürzen, oder Router mit Protokollgrund überspringen).
- **Router schlägt ein Dokument vor, dessen `when`-Bedingung falsch ist:** wird nicht ergänzt, Ablehnung protokolliert.
- **Router schlägt ein Dokument vor, das nur über eine Maßnahme nachgeladen würde (`binding` mit `actions`):** die Maßnahmensicherung bleibt zuständig; der Router umgeht sie nicht.
- **Router schlägt ein `binding`-Dokument ohne erfüllte Voraussetzung vor:** gilt wie oben, verbindliche Voraussetzungen bleiben maßgeblich.
- **Router ergänzt nichts:** richtige Antwort, wenn nichts passt; kein Fehler und kein Hinweis.
- **Router liefert ungültiges Format oder leere Antwort:** Ausfall „ungültige Antwort“, Rückfall auf PROJ-36.
- **Gleiches Thema in mehreren Nachrichten (Stufe B) ohne Rückbezug:** Ergänzung gilt als schwach.
- **Fehlende Übersetzung bei fremdsprachigem Text:** Router arbeitet mit dem Original; keine Verzögerung durch Übersetzen.
- **Original und Übersetzung widersprechen sich:** Original gilt.
- **Kundengruppe „noch unklar“, kein Produkt:** kleiner Pool; der Router sieht nur, was auch PROJ-36 sehen würde.
- **Pool über der Obergrenze, relevantes Dokument mit geringer Wortähnlichkeit:** gehört zu den bekannten Grenzen der einfachen Vorauswahl; die Tests machen sie sichtbar.
- **Neue oder geänderte Wissensdatei zwischen zwei Analysen:** der Router liest den aktuellen Wissensstand, der Wissensstand steht im Protokoll.
- **Gleichzeitiger Ausfall von Router und Zweitdurchgang (Maßnahmensicherung):** beide Fälle werden unabhängig behandelt und angezeigt; ein Router-Ausfall bewirkt keine Kopiersperre.
- **Testmodus-Läufe:** Router sieht nur den zurückgespulten Verlauf; Läufe sind wie bisher gekennzeichnet.
- **Router-Kosten bei sehr vielen Analysen:** Die Admin-Kennzahlen machen sie sichtbar; die Aufrufbegrenzung pro Minute bleibt bestehen.

## Technical Requirements (optional)
- **Rollback:** `KNOWLEDGE_ROUTER_ACTIVE=false` ergibt exakt PROJ-36.
- **Laufzeit:** Zeitlimit des Routers konfigurierbar (Richtwert 15 Sekunden), kein automatischer Retry; ob die zusätzliche Wartezeit im normalen Betrieb angemessen ist, prüft die QA.
- **Datenschutz:** An den Router gehen nur bereinigte Texte (Original und Übersetzung), keine strukturierten Bestelldaten, keine Dokumentvolltexte; Katalogzeilen enthalten keine Trigger. Ticketinhalte gehen wie bei der Analyse an den Anbieter (Auftragsverarbeitungsvertrag gilt).
- **Tests:** Pest mit simulierten Router-Antworten in der normalen Suite; das Live-Prüfset läuft nur auf Abruf (eigene Testgruppe) und protokolliert Modell, Version, Wissensstand, Tokens, Laufzeit und Kosten.
- **Konfigurierbar:** Obergrenze des Pools (Zeilen und Zeichen), Obergrenze des Router-Textes, Obergrenze der Ergänzungen (Richtwert 6), Zeitlimit, Schalter.

## Open Questions
- [ ] **Endgültige Werte** der Obergrenzen (Pool 60 Zeilen/15.000 Zeichen, Ergänzungen 6, Zeitlimit 15 Sekunden, Router-Text) nach dem Live-Prüfset anhand von Qualität, Laufzeit und Kosten festlegen.
- [ ] **Modell des Routers** und Format der Antwort: `/architecture` (günstiges Modell, ohne die Qualität des Abrufs zu verschlechtern).
- [ ] **Vorauswahl über der Obergrenze:** Verfahren und seine Güte; Grenze für sehr große Wissensbasis als bekannte Grenze festhalten, Embeddings später (PROJ-22).
- [ ] **Verhalten bei extrem langer aktueller Nachricht** (Stufe A überschreitet das Router-Textlimit): `/architecture`.
- [ ] **Budget-Strategie** bei einem sehr großen Router-Dokument (kleinere passende nicht verdrängen): `/architecture`.
- [ ] **Kategorien in der Rangfolge:** nutzen oder zunächst nur erkennen und protokollieren: `/architecture` nach Aufwand und Risiko.
- [ ] **`topics` in der Katalogzeile:** `/architecture` anhand der bestehenden Struktur.
- [ ] **Kosten je Aufruf:** zuverlässige Ermittlung (Preise je Modell) oder nur Tokens; `/architecture`.
- [ ] **Konservative Erkennung inhaltsleerer Nachrichten:** konkrete Regel (einfache Prüfung oder Modell) und Testfälle; `/architecture`.
- [ ] **Wechselwirkung mit der Aufrufbegrenzung** (6 Aufrufe pro Minute und Browser): zählt der Router mit; `/architecture`.
- [ ] Welche vorhandenen `summary`-Texte durch das Live-Prüfset als unzureichend auffallen und gezielt nachgebessert werden müssen.

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Router ergänzt nur, er entfernt und überschreibt nichts aus PROJ-36 | Die deterministische Auswahl und ihre Sicherungen bleiben vertrauenswürdig; Rückfall ist trivial | 2026-10-13 |
| Pool: alle verwendbaren Dokumente im Geltungsbereich ohne Trigger-Treffer, Katalogzeile ID/Titel/summary | Sonst bleibt das Ausgangsproblem eine Stufe früher bestehen; bei 44 Dokumenten rund 9.300 Zeichen, also günstig | 2026-10-13 |
| Konfigurierbare Obergrenze des Pools (Richtwert 60 Zeilen / 15.000 Zeichen), darüber Vorauswahl nach Textähnlichkeit ohne Trigger-Bedingung | Kosten wachsen nicht mit der Wissensbasis; Güte der Vorauswahl ist testbar; Grenze der einfachen Verfahren wird dokumentiert, Embeddings später | 2026-10-13 |
| Router sieht Stufen A und B, bereinigt, Original plus vorhandene Übersetzung; keine Stufe C, keine strukturierten Bestelldaten, keine Volltexte | Viele Anliegen ergeben sich aus dem Gespräch; wenig personenbezogene Daten; Kosten klein | 2026-10-13 |
| Stufe-B-Themen schwach, Ausnahme bei ausdrücklichem Rückbezug der aktuellen Nachricht | Ein erneut genanntes Problem ist kein historisches Nebenthema | 2026-10-13 |
| Konfigurierbare Obergrenze des Router-Textes; Stufe A vor Stufe B; nichts stillschweigend | Kosten und Latenz begrenzen, aktuelles Anliegen schützen | 2026-10-13 |
| Router läuft bei jeder Analyse (wenn aktiv); Ausnahmen nur bei eindeutig inhaltsleerer Nachricht (konservativ, im Zweifel ausführen) und leerem Pool | Auch bei Treffern kann das entscheidende Dokument fehlen; Qualität vor Kosten | 2026-10-13 |
| Entscheidung wird protokolliert: ausgeführt, regulär übersprungen (Grund), deaktiviert, fehlgeschlagen (Grund) | Nachvollziehbarkeit und Messbarkeit | 2026-10-13 |
| Router einmal vor dem ersten Durchgang; Zweitdurchgang der Maßnahmensicherung behält die Ergänzungen; „Erneut analysieren“ führt neu aus | Einfach, vorhersagbar; Wissen kann sich zwischen Analysen ändern | 2026-10-13 |
| Router-Ergänzungen nutzen die Stufen von PROJ-36 (aktuell = stark, früherer Verlauf = schwach); deterministische Treffer vor Router-Ergänzungen derselben Stufe | Keine zweite konkurrierende Rangordnung | 2026-10-13 |
| Starke Router-Ergänzung darf bei der Budgetplanung einen schwachen deterministischen Treffer verdrängen; der Router entfernt aber nie aktiv; Pflichtwissen und von Hand zugeschaltetes Wissen bleiben geschützt | Ein eindeutig relevantes Dokument soll nicht gegen einen schwachen Keyword-Treffer verlieren; Unterschied „Entfernen“ gegen „Budgetverdrängung“ ausdrücklich dokumentiert | 2026-10-13 |
| Kein eigenes Router-Teilbudget; ein großes Router-Dokument darf kleinere passende nicht verdrängen | Zwei Budgetgrenzen würden relevantes Wissen ausschließen | 2026-10-13 |
| Konfigurierbare Obergrenze für Ergänzungen je Analyse (Richtwert 6), Rangfolge durch den Router, Überzähliges im Protokoll | „Lieber mehr“ darf nicht zu „wahllos viel“ werden | 2026-10-13 |
| Nur Dokumente aus dem Pool; ungültige IDs, Duplikate, unzulässige Dokumente verworfen und protokolliert | Geltungsbereich, Status und Validierung bleiben unumgehbar | 2026-10-13 |
| Semantische Ähnlichkeit umgeht keine Anwendungsvoraussetzung (`when`, Maßnahmenbindung, Geltungsbereich) | Verbindliche Regeln bleiben verbindlich | 2026-10-13 |
| Fallarten: mehrere oder keine, nur nachrangiges Signal, nie ein Filter; bei Aufwand zunächst nur erkennen und protokollieren | Eine falsche Einordnung darf nichts ausschließen; Hauptziel ist die semantische Auswahl | 2026-10-13 |
| Router-Ausfall: Zeitlimit 15 Sekunden, kein Retry, Rückfall auf PROJ-36, dezenter Hinweis im Ergebnisbereich, keine Sperre; Ausfallgrund im Bereich „Wissensabruf“ | Der Eisberg-Fall zeigt, dass PROJ-36 Wissen übersehen kann; der Mitarbeiter soll es merken, ohne blockiert zu sein | 2026-10-13 |
| Reguläres Überspringen und Deaktivierung sind kein Fehler und erzeugen keinen Hinweis | Klare Trennung von Ausfall und Normalfall | 2026-10-13 |
| Schalter `KNOWLEDGE_ROUTER_ACTIVE`, Standard aus, nur wirksam bei aktivem PROJ-36 | Schneller Rückfall; der Router baut auf PROJ-36 auf | 2026-10-13 |
| Sichtbarkeit: Mitarbeiter sehen vier Herkunftsbezeichnungen und die KI-Einschätzung; Admins zusätzlich technische Details und Kennzahlen in der Analysen-Übersicht | Fachlich Hilfreiches für alle, Diagnose ohne Rauschen in der Hauptansicht | 2026-10-13 |
| Begründung des Routers ist als KI-Einschätzung gekennzeichnet und immer deutsch | KI-Texte dürfen nicht wie bewiesene Ursachen wirken; Mitarbeiter lesen deutsch | 2026-10-13 |
| Manuelles Zuschalten bleibt unverändert | Bestehende Mitarbeiterfunktion, Hilfe bei verbleibenden Fehlstellen | 2026-10-13 |
| Fremdsprache: Original plus vorhandene Übersetzung, Original maßgeblich, kein eigener Übersetzungsaufruf, Übersetzung keine Voraussetzung | Keine Zusatzkosten, keine Abhängigkeit von früherem Übersetzen | 2026-10-13 |
| Zwei Teststufen: Pest mit simulierter Router-Antwort in der Suite, Live-Prüfset gegen das echte Modell auf Abruf | Logik prüfbar ohne Kosten; semantische Qualität nur mit echtem Modell prüfbar | 2026-10-13 |
| Live-Prüfset mit erwarteten und verbotenen IDs, Vergleich mit PROJ-36, 5 von 5 für den Eisberg-Fall, protokolliert Modell/Version/Wissensstand/Kosten, nur anonyme oder selbst formulierte Texte | Nutzen statt nur Anzahl der Ergänzungen messen; Reproduzierbarkeit; Datenschutz | 2026-10-13 |
| Vor der Aktivierung fachliche Freigabe der Live-Ergebnisse durch den Product Owner | Aktivierung im Kundenservice ist eine Entscheidung mit Qualitätsrisiko | 2026-10-13 |
| Autorenseite gehört zu PROJ-37: bessere `summary`-Anleitung, weiche Warnung unter 60 Zeichen, keine Warnung wegen fehlender Abgrenzung, kein pauschales Umschreiben | Die `summary` ist Grundlage der Auswahl; erzwungene Abgrenzungen würden künstliche Ausschlüsse fördern | 2026-10-13 |
| Katalogzeile ohne Trigger | Semantische Auswahl soll sich nicht an den bisherigen Schlüsselwörtern orientieren | 2026-10-13 |
| Bekannte Risiken: formal gültige, aber inhaltlich dürftige Summaries; Grenze der einfachen Vorauswahl bei großer Wissensbasis | Ehrliche Dokumentation; Live-Tests und PROJ-22 sind der Weg | 2026-10-13 |
| Admin-Funktion „Wissensauswahl mit KI prüfen“ bleibt außerhalb des Umfangs | Eigenes Feature; PROJ-37 verbessert die automatische Auswahl | 2026-10-13 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|
| _folgt in /architecture_ | | |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)
_To be added by /architecture_

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
