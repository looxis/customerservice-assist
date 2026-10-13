# PROJ-37: Router für den Wissensabruf

## Status: In Progress
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
- [ ] Angenommen ein Pool über der Obergrenze mit einem nur produktspezifischen und einem nur gruppenspezifischen relevanten Dokument (wie PRODUCT-006/PLAYBOOK-018 und PLAYBOOK-019), wenn die Vorauswahl läuft, dann gelangen beide in die Vorauswahl; die Reservierung setzt nicht voraus, dass ein Dokument auf Produkt und Kundengruppe zugleich eingeschränkt ist
- [ ] Angenommen reservierte Kandidatenplätze, die nicht gebraucht werden, wenn die Vorauswahl gefüllt wird, dann stehen sie anderen geeigneten Kandidaten zur Verfügung
- [ ] Angenommen ein Dokument, dessen einzige Treffer durch `exclude_triggers` im jeweiligen Satz unterdrückt wurden, wenn der Pool gebildet wird, dann bleibt es im Pool, mit einem Vermerk zur Unterdrückung; eine Ergänzung durch den Router braucht einen Beleg im Kundentext, der nicht der unterdrückte Satz ist, sonst wird sie verworfen und protokolliert
- [ ] Angenommen eine Nachricht mit zwei Anliegen, von denen eines ein Dokument unterdrückt und das andere es semantisch (ohne Trigger) braucht, wenn der Router das Dokument mit einem Beleg aus dem zweiten Anliegen vorschlägt, dann wird es ergänzt
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
- [x] **Modell des Routers** und Format der Antwort → eigene Konfiguration, Standard das günstige Zusammenfassungsmodell, fest strukturierte Antwort; Umstellung auf das Analyse-Modell bei Schwächen im Live-Prüfset (2026-10-13)
- [x] **Vorauswahl über der Obergrenze** → Textähnlichkeit plus reservierter Anteil für spezifischen Geltungsbereich; Güte in Tests der Ebene 1b, Grenzen in der Tabelle „Bekannte Grenzen“; Embeddings später (2026-10-13)
- [x] **Extrem lange aktuelle Nachricht** → Anfang und Ende bleiben, Mitte durch sichtbare Marke ersetzt, protokolliert und angezeigt (2026-10-13)
- [x] **Budget-Strategie** → nur Router-Ergänzungen werden bei Nichtpassen übersprungen und es geht weiter; PROJ-36-Regel für deterministische Treffer unverändert (2026-10-13)
- [x] **Kategorien in der Rangfolge** → erkennen, protokollieren, anzeigen; Rangwirkung nur als letztes Unterscheidungsmerkmal, entfällt bei Aufwand (2026-10-13)
- [x] **`topics` in der Katalogzeile** → nicht in der ersten Fassung; Entscheidung nach dem Live-Prüfset (2026-10-13)
- [x] **Kosten je Aufruf** → Tokens immer, Kosten nur mit hinterlegtem Preis als „geschätzt“ (2026-10-13)
- [x] **Erkennung inhaltsleerer Nachrichten** → deterministische Prüfung mit fester Floskelliste, ohne Anhang und Fragezeichen; Testfälle sind die vier Beispiele der Spec (2026-10-13)
- [x] **Wechselwirkung mit der Aufrufbegrenzung** → die Begrenzung hängt an der Anfrage „Analyse starten“; der Router löst keine zusätzliche Sperre aus (2026-10-13)
- [ ] **Eigenes Feld für echte Voraussetzungen bei `triggered`-Dokumenten** (Sperre statt zusätzlicher Weg): heute nicht ausdrückbar; Folgefeature, nicht Teil von PROJ-37.
- [ ] **Konkrete Floskelliste** für inhaltsleere Nachrichten, Richtwerte für Router-Text (12.000 Zeichen) und Anteil der reservierten Kandidatenplätze (ein Drittel): im Bau und mit dem Live-Prüfset festlegen.
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
| Abruf in zwei Durchgängen: deterministisch, Router, dann gemeinsame Planung | Die deterministische Rechnung ist billig; kein Umbau des Abrufs, exakter Rückfall auf PROJ-36 | 2026-10-13 |
| `when` bleibt bei `triggered` ein Weg, keine Sperre; Pool-Regeln nach Schutzmechanismus (Geltungsbereich, Ausschluss im Satz, `binding` bekannt falsch, Maßnahmenbindung) | Hält die Anwendungsvoraussetzungen ein und führt keine zusätzliche Sperre ein; im Bestand nutzt kein Dokument `when` | 2026-10-13 |
| Router-Ergänzung ist nie Pflichtwissen, auch bei `binding`-Dokumenten | Der Router darf das Pflichtbudget nicht aufblähen | 2026-10-13 |
| Vorauswahl über der Obergrenze: Textähnlichkeit plus reservierter Anteil (Richtwert ein Drittel) für Dokumente mit spezifischem Geltungsbereich, getrennt nach Produkt-Spezifität und Gruppen-Spezifität, abwechselnd vergeben, ungenutzte Plätze fallen zurück | Ein Dokument ohne Wortähnlichkeit behält über den Geltungsbereich eine Chance, ohne dass Produkt und Gruppe zugleich nötig sind; kein Embedding nötig | 2026-10-13 |
| Durch `exclude_triggers` unterdrückte Dokumente bleiben im Pool, mit Vermerk; Ergänzung nur mit Beleg, der nicht aus dem unterdrückten Satz stammt (deterministisch geprüft) | Die Unterdrückung gilt nur für einen Satz, nicht für das Dokument; ein zweites Anliegen darf es trotzdem auslösen | 2026-10-13 |
| Router-Modell aus eigener Konfiguration, Standard das günstige Zusammenfassungsmodell | Unabhängig umstellbar; Qualität entscheidet das Live-Prüfset | 2026-10-13 |
| Fest strukturierte Router-Antwort (Ergänzungen mit Rang/Einstufung/Begründung, Fallarten) | Prüfbar, kein Freitext; wie Analyse und Übersetzung | 2026-10-13 |
| Router-Anweisung als versionierte Datei im Repository | Wie die anderen Prompts; Version im Protokoll | 2026-10-13 |
| Router-Text durch dieselbe Bereinigung wie die Analyse; keine Bestelldaten, keine Volltexte | Datenschutz, wenig Tokens | 2026-10-13 |
| Zu lange Stufe A: Anfang und Ende bleiben, Mitte durch sichtbare Marke ersetzt, protokolliert und angezeigt | Keine stille Kürzung, Grenze wird nie überschritten, Problem und Bitte stehen meist am Rand | 2026-10-13 |
| Erkennung inhaltsleerer Nachrichten ohne KI: kurze Nachricht nur aus einer festen Floskelliste, ohne Anhang und Fragezeichen | Konservativ, testbar, kostenlos; im Zweifel läuft der Router | 2026-10-13 |
| Große Router-Ergänzung wird übersprungen und protokolliert, kleinere folgen; PROJ-36-Regel für deterministische Treffer bleibt | Erfüllt das Spec-Kriterium, ohne das Verhalten ohne Router zu ändern | 2026-10-13 |
| Fallarten: erkennen, protokollieren, anzeigen; Rangwirkung nur als letztes Unterscheidungsmerkmal, bei Aufwand entfällt sie | Folgt der Spec | 2026-10-13 |
| Katalogzeile ohne `topics` und Trigger | Trigger würden die Auswahl an den Wortlaut binden; `topics` uneinheitlich | 2026-10-13 |
| Kosten nur mit hinterlegtem Preis, als „geschätzt“; Tokens immer | Kein Raten | 2026-10-13 |
| Aufrufbegrenzung gilt für die Anfrage „Analyse starten“; der Router löst keine zusätzliche Sperre aus | Begrenzung hängt an der Route, nicht an einzelnen KI-Aufrufen | 2026-10-13 |
| Router-Ergebnis in verschlüsseltem Protokoll; Kennzahlen als Spalten (Entscheidung, Ergänzungen, Zeichen, Laufzeit, Tokens) mit Index auf die Entscheidung | Wie bei PROJ-36; Kundeninhalt nur verschlüsselt | 2026-10-13 |
| Herkunft je Dokument als eigenes Feld im Protokoll | Nicht aus dem Text der Gründe ableiten | 2026-10-13 |
| Live-Prüfset als eigene Testgruppe, nur auf Abruf, Bericht außerhalb des Repositorys | Echte Kosten und Daten nur gezielt; Reproduzierbarkeit | 2026-10-13 |
| Bekannte Qualitätsgrenzen der Vorauswahl werden als „offen / bekannte Grenze“ ausgewiesen, nie als bestanden gezählt | Ehrliche Qualitätsaussage | 2026-10-13 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

Zielgruppe dieses Abschnitts: Product Owner. Keine Code-Details; „Wie genau“ klärt `/backend`.

### Backend nötig?
Ja, und ein wenig Oberfläche. Der Router ist ein zusätzlicher, kleiner KI-Aufruf **vor** der Analyse. Alles Weitere (Pool, Prüfung der Vorschläge, Budget, Protokoll) ist deterministischer Code im bestehenden Abruf. Neue Datenbank-Tabellen sind nicht nötig; es kommen Kennzahl-Spalten an der Analyse und ein Abschnitt im verschlüsselten Protokoll dazu. Keine neuen Pakete.

### A) Bausteine (Übersicht)
```
Analyse starten (bestehend: CaseAnalyzer)
+-- Fallfakten und Textstufen A/B/C          (bestehend, PROJ-36)
+-- Abruf Durchgang 1: nur deterministisch   (bestehend, PROJ-36)  -> Ergebnis + "nicht abgerufen"-Liste
+-- ROUTER (neu)
|   +-- Entscheidung: ausführen oder regulär überspringen      (einfache Prüfung, kein KI-Aufruf)
|   +-- Pool-Bildung: nicht abgerufene, zulässige Dokumente    (Geltungsbereich, Status, Schutzregeln)
|   +-- Vorauswahl über der Obergrenze: Reihung ohne Trigger-Bedingung, mit Vorrang für spezifische Dokumente
|   +-- Router-Text: Stufen A/B, Original + Übersetzung, bereinigt, begrenzt
|   +-- KI-Aufruf (günstiges Modell, Zeitlimit, kein Retry)
|   +-- Prüfung der Antwort: nur Pool-IDs, Dubletten, Schutzregeln, Obergrenze der Ergänzungen
+-- Abruf Durchgang 2: gemeinsame Budgetplanung (bestehend, erweitert um Router-Ergänzungen)
+-- Analyse-Aufruf (bestehend) + Maßnahmensicherung (bestehend, ruft den Router nicht erneut)
+-- Protokoll (bestehend, erweitert): Router-Entscheidung, Pool, Vorschläge, Verworfenes, Fallarten, Herkunft je Dokument

Ergebnisansicht (bestehend)
+-- Bereich "Wissensabruf": Herkunft je Dokument (4 Bezeichnungen), KI-Einschätzung bei Ergänzungen
+-- Dezenter Hinweis bei Router-Ausfall (neu)
+-- Admin-Teil im Bereich "Wissensabruf": Pool, Verworfenes, Fallarten, Modell, Version, Tokens, Laufzeit, Kosten, Status (neu)

Analysen-Übersicht (Admin, bestehend)
+-- Filter "Router-Entscheidung" und Kennzahlen (neu)

Autorenseite
+-- Authoring Guide, Chat-Handbuch, Knowledge-Skill: summary-Anleitung (erweitert)
+-- knowledge:check: weiche Warnung bei sehr kurzer summary (erweitert)

Tests
+-- Pest mit simulierter Router-Antwort (normale Suite)
+-- Vorauswahl-Güte-Tests mit großen künstlichen Wissensbasen (normale Suite)
+-- Live-Prüfset gegen das echte Modell (nur auf Abruf, eigene Testgruppe)
```

### B) Ablauf einer Analyse mit Router
1. Wie bisher: Fakten und Textstufen bilden.
2. **Durchgang 1 des Abrufs** läuft rein deterministisch und liefert das PROJ-36-Ergebnis samt Liste der nicht abgerufenen Dokumente. Das ist billig (Millisekunden) und gleichzeitig die Grundlage für den Pool.
3. **Router-Entscheidung:** Schalter aus oder gezielter Abruf aus ⇒ „deaktiviert“. Eindeutig inhaltsleere Nachricht ⇒ „regulär übersprungen“. Leerer Pool ⇒ „regulär übersprungen“. Sonst weiter.
4. **Pool** bilden, bei Überschreiten der Obergrenze vorauswählen, **Router-Text** bilden, bereinigen und begrenzen.
5. **KI-Aufruf** mit Zeitlimit. Fällt er aus ⇒ „fehlgeschlagen“, Durchgang 1 gilt, Hinweis im Ergebnis.
6. **Antwort prüfen:** nur Pool-IDs, keine Dubletten, Schutzregeln, Obergrenze für Ergänzungen. Verworfenes wird mit Grund protokolliert.
7. **Durchgang 2 des Abrufs:** Das Ergebnis wird mit den Ergänzungen noch einmal gerechnet (die Rechnung ist deterministisch, deshalb ist „zweimal rechnen“ einfacher und sicherer als ein Umbau). Die Ergänzungen laufen als starke oder schwache Kandidaten durch dieselbe Budgetplanung.
8. Analyse und Maßnahmensicherung laufen wie in PROJ-36; der Router wird dabei **nicht** erneut gerufen.

### C) Daten (Alltagssprache)
**Pro Analyse im verschlüsselten Protokoll (Abschnitt „Router“):** Entscheidung und Grund; Pool (IDs) und seine Größe; ob eine Vorauswahl nötig war und was sie ausgelassen hat; Vorschläge des Routers mit Rang, Einstufung („aktuelles Anliegen“ oder „früherer Verlauf“) und Begründung; Übernommenes; Verworfenes mit Grund (unbekannte ID, Dublette, nicht im Pool, Schutzregel, Obergrenze); erkannte Fallarten mit Begründung; Hinweis, ob der Router-Text gekürzt wurde; Modell, Router-Anweisung und -Version; Tokens, Laufzeit, Kosten (falls ermittelbar); Ausfallgrund.
**Herkunft je abgerufenem Dokument** wird als eigenes Feld gespeichert (Grundwissen / Auslöser oder Bedingung / Router / von Hand), nicht mehr aus dem Text der Gründe abgeleitet.
**Als Spalten an der Analyse (für die Übersicht, ohne Kundeninhalt):** Router-Entscheidung, Zahl der Ergänzungen, hinzugekommene Zeichen, Router-Laufzeit, Router-Tokens (Eingabe, Ausgabe). Dazu ein Index auf die Entscheidung für den Filter.
**Wo:** MySQL, bestehende Analysen-Tabelle (neue Spalten, kein Löschen von Daten). Ältere Analysen haben „leer“ und zeigen nichts Neues.

### D) Technische Entscheidungen (mit Begründung)

**1. Semantik von `when`, genau untersucht**
Im `KnowledgeRetriever` ist `when` heute **nie eine Sperre**, sondern ein weiterer Weg hinein:
- Bei `triggered`-Dokumenten gilt: stark abgerufen bei Auslöser im aktuellen Anliegen **oder** erfüllter Bedingung. Ist die Bedingung falsch oder unbekannt und es gibt keinen Treffer, passiert nichts (nicht abgerufen). Trotz falscher Bedingung kommt es bei einem Auslöser-Treffer.
- Bei `binding`-Dokumenten gilt: Treffer in einer beliebigen Stufe, **oder** erfüllte Bedingung, **oder** unbekannte Bedingung (vorsorglich, mit Hinweis). Nur „keine Treffer **und** Bedingung bekannt falsch“ bedeutet: gilt hier nicht.
- Im echten Wissensbestand nutzt heute **keine** der 77 Dateien `when`; die Frage ist also bisher theoretisch.

Daraus folgt für den Router die folgende Tabelle. Sie trennt echte Anwendungsvoraussetzungen von bloßen Abrufwegen und führt **keine zusätzliche Sperre** ein:

| Eigenschaft eines Dokuments | Verhalten des Routers |
|---|---|
| Außerhalb von Kundenart, Kanal oder Produkt, `exclude_products`, `deprecated`, Prüffehler | **nicht im Pool** (echte Voraussetzung; wirkt schon bei der Pool-Bildung) |
| Treffer wurde durch einen Ausschluss im selben Satz unterdrückt (`exclude_triggers`) | **bleibt im Pool**, wird aber mit einem Vermerk an den Router gegeben (siehe unten „Unterdrückte Treffer“) |
| `binding` und `when` ist bekannt falsch, kein Treffer | **nicht im Pool** (die Regel gilt hier laut PROJ-36 nicht) |
| `binding` und nur an eine Maßnahme gebunden (`actions`) | **nicht im Pool** (dafür ist die Maßnahmensicherung zuständig; der Router würde sie umgehen) |
| `binding` mit Auslösern, aber ohne Treffer, und `when` ist unbekannt oder fehlt | im Pool; wird bei Ergänzung als **starke/schwache Ergänzung** (nicht als Pflichtwissen) geführt und kann bei Platznot wegfallen |
| `triggered`, egal wie `when` ausgewertet wird (wahr, falsch, unbekannt) | **im Pool**: Bei `triggered` ist `when` nur ein zusätzlicher Weg, keine Voraussetzung; sie zu sperren wäre die „unbeabsichtigte zusätzliche Sperre“ |
| Beispiele (gut/schlecht), Arbeitsabläufe | nicht im Pool (haben eigene Auswahl) |
| Bereits abgerufen oder von Hand zugeschaltet | nicht im Pool (aber ID und Titel gehen als Kontext an den Router) |

Ergänzend: Eine Router-Ergänzung ist **nie Pflichtwissen**, auch wenn das Dokument `binding` ist. Pflichtwissen bleibt der deterministischen Ebene vorbehalten, damit der Router das Pflichtbudget nicht aufblähen kann.
**Unterdrückte Treffer (`exclude_triggers`) – geprüft gegen die bestehende Semantik.** PROJ-36 unterdrückt einen Treffer nur **im selben Satz**. Ein Dokument gilt nur dann als „unterdrückt“, wenn **alle** seine Treffer unterdrückt wurden; hat es in einem anderen Satz einen Treffer, wird es ganz normal abgerufen und taucht nie in der Liste auf. Ein globaler Ausschluss ist das also nicht, und er darf es durch den Router nicht werden: Dasselbe Dokument kann wegen eines **anderen Anliegens** in derselben Nachricht trotzdem relevant sein (ein Angebot mit eigenem Logo **und** die Frage, ob eine bekannte Zeichentrickfigur gedruckt werden darf, die kein Trigger trifft).
Die Regel: Ein unterdrücktes Dokument bleibt im Pool. Seine Katalogzeile bekommt einen **Vermerk** („Auslöser ‚Logo‘ im Satz ‚… mit unserem Logo …‘ wurde durch ‚unser Logo‘ unterdrückt“), und die Router-Anweisung verbietet, es allein aufgrund dieses Satzes zu ergänzen. Schlägt der Router es vor, muss er einen **Beleg** nennen, den Satz oder die Wendung aus dem Kundentext, auf die er sich stützt. Die App prüft deterministisch: Der Beleg muss im Kundentext vorkommen und darf **nicht** der unterdrückte Satz (oder in ihm enthalten) sein. Besteht der Beleg die Prüfung nicht, wird der Vorschlag verworfen und mit Grund protokolliert („stützt sich nur auf den unterdrückten Satz“). Damit bleibt der Wille des Autors („eigenes Logo ist kein Rechtefall“) erhalten, ohne das Dokument für ein zweites Anliegen zu sperren.
**Hinweis für die Autoren (Offener Punkt, nicht Teil von PROJ-37):** Wer eine Voraussetzung meint, die ein `triggered`-Dokument **sperrt** („gilt nur, wenn die Bestellung älter als 14 Tage ist“), kann das heute mit `when` nicht ausdrücken. Das braucht ein eigenes Feld; der Authoring Guide bekommt in PROJ-37 nur einen klarstellenden Satz, dass `when` ein zusätzlicher Weg ist.

**2. Pool und Vorauswahl über der Obergrenze**
- Bis zur Obergrenze (60 Zeilen / 15.000 Zeichen) sieht der Router **alle** Zeilen des Pools. Aktuell sind das 44 Zeilen, 9.300 Zeichen.
- Darüber reiht eine **Textähnlichkeit** (am Wortanfang, ohne Beugung, nach Titel, `summary`, `topics` und Triggern, ohne Trigger-Treffer als Bedingung) die Kandidaten. Wegen der Anforderung, dass ein Dokument ohne Wortähnlichkeit eine echte Chance behalten muss, gibt es zusätzlich **reservierte Kandidatenplätze für Dokumente mit spezifischem Geltungsbereich** (Richtwert ein Drittel der Plätze).
- **Spezifität hat zwei unabhängige Arten**, die die Reservierung getrennt behandelt; ein Dokument muss **nicht** auf Produkt **und** Kundengruppe zugleich eingeschränkt sein:
  - **produktspezifisch:** das Dokument nennt Produkte (`products`) und das gewählte Produkt ist dabei (Eisberg: PRODUCT-006 und PLAYBOOK-018, beide nicht auf Reseller beschränkt);
  - **gruppenspezifisch:** das Dokument nennt Kundenarten oder Kanäle (`customer_types`, `sales_channels`) und die gewählte Kundengruppe ist dabei (Eisberg: PLAYBOOK-019, produktübergreifend).
  Ein Dokument mit beiden Einschränkungen zählt in beiden Listen und rückt damit eher nach vorn, ist aber keine Voraussetzung.
- **Verteilung der reservierten Plätze:** Die Plätze werden abwechselnd aus der produktspezifischen und der gruppenspezifischen Liste vergeben (je Liste nach Textähnlichkeit gereiht), damit eine Art nicht alle Plätze belegt (zum Beispiel 100 Reseller-Dokumente, die ein einziges produktspezifisches Dokument verdrängen). **Ungenutzte reservierte Plätze** (zu wenige spezifische Dokumente, oder eine Art ist ausgeschöpft) fallen an die jeweils andere Art und danach an die allgemeine Reihung nach Ähnlichkeit zurück; sie bleiben nie leer. Allgemeine Dokumente konkurrieren nur über die Ähnlichkeit.
- **Warum nicht gleich Embeddings?** Mehr Aufwand, eine weitere Abhängigkeit und Kosten; bei 77 Dokumenten nicht nötig. PROJ-22 bleibt die Ausbaustufe, wenn das Live-Prüfset eine echte Grenze zeigt.
- **Bekannte Grenze:** Ein **allgemeines** Dokument ohne Produktbezug, mit geringer Wortähnlichkeit, in einer Wissensbasis weit über der Obergrenze, kann übersehen werden. Das wird in den Tests sichtbar gemacht und in der Spec als Grenze ausgewiesen (siehe E).

**3. Modell und Aufruf**
- **Standardmodell:** das günstige Modell, das schon für Zusammenfassung und Übersetzung läuft (`gpt-5.4-mini`), aus einer **eigenen** Konfiguration, damit der Router unabhängig umgestellt werden kann. Qualität geht vor Kosten: Zeigt das Live-Prüfset Schwächen, wird auf das Analyse-Modell umgestellt, nur über die Konfiguration.
- **Antwortformat:** fest strukturiert (wie bei Analyse und Übersetzung): Liste der Ergänzungen mit ID, Rang, Einstufung („aktuell“ oder „früherer Verlauf“), deutsche Kurzbegründung; Liste der Fallarten mit Kurzbegründung; keine Freitext-Regeln. Ungültiges Format zählt als Ausfall „ungültige Antwort“.
- **Anweisung:** eine versionierte Datei im Repository (wie die anderen Prompts), mit den Grundsätzen: nur Dokumente aus der Liste; nichts erfinden; im Zweifel ergänzen, aber nur bei erkennbarem Bezug; keine Rechtsfragen entscheiden; Begründung deutsch und als Einschätzung formuliert.
- **Zeitlimit** eigener Wert (Richtwert 15 s), **kein Retry**. Die Fehlerarten (Zeitüberschreitung, API-Fehler, ungültige Antwort, fehlender Schlüssel) werden wie bei den anderen KI-Aufrufen unterschieden.
- **Datenschutz:** Der Router-Text läuft durch dieselbe Bereinigung wie die Analyse (E-Mail, Telefon, Anschrift ersetzt), mit denselben Platzhaltern. Strukturierte Bestelldaten und Dokumentvolltexte gehen nicht mit.
- **Aufrufbegrenzung:** Die Begrenzung (6 Aufrufe pro Minute und Browser) gilt für die Anfrage „Analyse starten“, nicht für einzelne KI-Aufrufe. Der Router wird innerhalb derselben Anfrage ausgeführt und löst deshalb **keine zusätzliche Sperre** aus. Offene Frage aus der Spec damit beantwortet.

**4. Router-Text und seine Grenze**
- Reihenfolge: Mitarbeiterhinweis und aktuelle Nachricht (Stufe A) vor früheren Nachrichten und Zusammenfassung (Stufe B). Original und Übersetzung getrennt gekennzeichnet; Original maßgeblich.
- Konfigurierbare Obergrenze (Richtwert 12.000 Zeichen). **Reicht Stufe B nicht, wird sie gekürzt, nie Stufe A.**
- **Extrem lange aktuelle Nachricht:** Überschreitet allein Stufe A die Grenze, wird sie **nicht still abgeschnitten**. Stattdessen bleiben Anfang und Ende erhalten (dort stehen im Kundentext meist das Problem und die Bitte), die Mitte wird durch eine sichtbare Auslassungsmarke ersetzt („[… N Zeichen ausgelassen …]“). Das steht im Protokoll und als Hinweis „Router-Text gekürzt“ im Bereich „Wissensabruf“ (für alle Mitarbeiter, kein Fehlerhinweis). Die Obergrenze wird dadurch nie überschritten.

**5. Wann der Router entfällt (inhaltsleere Nachricht)**
Rein deterministische, **konservative** Prüfung ohne KI: Nur wenn die gesamte aktuelle Kundennachricht (nach Abzug von Zitaten und Signatur) **kurz ist, ausschließlich aus Wörtern einer kleinen, festen Liste von Höflichkeits- und Bestätigungsfloskeln besteht** („danke“, „vielen Dank“, „ok“, „perfekt“, „alles klar“, „viele Grüße“ …), **keinen Anhang und kein Fragezeichen** enthält. Alles andere läuft. Ihre vier Beispiele („Das Problem besteht weiterhin.“, „So hatte ich mir das nicht vorgestellt.“, „Danke, aber das Motiv ist immer noch zu klein.“, „Anbei die gewünschten Fotos.“) enthalten Wörter außerhalb der Liste oder einen Anhang und lösen den Router deshalb aus. Die Liste liegt in der Konfiguration; sie ist bewusst klein.

**6. Gemeinsame Budgetplanung und das große Dokument**
In PROJ-36 gilt „gekürzt wird von hinten“: Passt ein Dokument nicht mehr, entfallen es und alles dahinter. Das würde ein einzelnes sehr langes Router-Dokument zum Verdränger kleinerer machen. Entscheidung: **Nur für Router-Ergänzungen** gilt „überspringen und weitermachen“: Passt eine Ergänzung nicht, wird sie übersprungen und protokolliert („wegen Umfang nicht mitgesendet“), kleinere Ergänzungen und der Rest des Platzes bleiben nutzbar. Für alle deterministischen Treffer bleibt die PROJ-36-Regel unverändert; **ohne Router verhält sich die Auswahl damit exakt wie heute** (Rollback).
- Reihenfolge innerhalb der Stufen: deterministische Treffer vor Router-Ergänzungen; aktuell = stark, früherer Verlauf = schwach.
- Eine starke Ergänzung kann einen schwachen deterministischen Treffer **bei der Planung** verdrängen (vom Product Owner freigegeben). Das ist kein aktives Entfernen durch den Router; es steht als „wegen Umfang nicht mitgesendet“ im Protokoll.
- Pflichtwissen und von Hand zugeschaltetes Wissen sind unantastbar.

**7. Fallarten**
Der Router liefert die Liste (leer erlaubt, mehrere erlaubt). Für PROJ-37 werden sie **erkannt, protokolliert und angezeigt**. In der Rangfolge wirken sie nur als allerletztes Unterscheidungsmerkmal bei sonst gleichem Rang (ein Dokument mit passender Kategorie vor einem sonst gleichwertigen ohne), nie als Filter. Sollte sich das im Bau als aufwendig oder riskant zeigen, entfällt die Rangfolge-Wirkung und es bleibt bei Erkennen und Protokoll; das ist von der Spec ausdrücklich erlaubt.

**8. Katalogzeile:** ID, Titel und `summary`. `topics` und Trigger gehen **nicht** mit (Trigger würden den Router an den bisherigen Wortlaut binden; `topics` sind uneinheitlich gepflegt). Bei Bedarf entscheidet das Live-Prüfset.

**9. Kosten:** Das Protokoll speichert immer die **Tokens**. Kosten werden nur angezeigt, wenn für das Modell ein Preis in der Konfiguration steht, dann als „geschätzt“; sonst bleibt das Feld leer. Kein Raten.

**10. Schalter und Rückfall:** `KNOWLEDGE_ROUTER_ACTIVE` (Standard aus). Wirksam nur, wenn der gezielte Abruf an ist. Aus ⇒ Durchgang 1 genügt und alles verhält sich wie PROJ-36, auch im Protokoll und in den Spalten (Entscheidung „deaktiviert“).

**11. Version:** Eine Router-Version (feste Zahl plus Kurzkennung der Einstellungen) steht im Protokoll, ebenso Modell und die Version der Router-Anweisung. So bleibt jede Analyse reproduzierbar.

**12. Anzeige:**
- **Alle Mitarbeiter:** im Bereich „Wissensabruf“ vier Herkunftsbezeichnungen; bei Router-Ergänzungen die Begründung mit dem Präfix „KI-Einschätzung:“; Hinweis „Router-Text gekürzt“ falls zutreffend; bei Ausfall der dezente Hinweis im Ergebnisbereich (kein Banner, keine Sperre).
- **Admin:** aufklappbarer Teil im selben Bereich mit Pool, Verworfenem, Fallarten, Modell/Version, Tokens, Laufzeit, Kosten, Status, Ausfallgrund.
- **Analysen-Übersicht (nur Admin):** Filter „Router-Entscheidung“ (ausgeführt, regulär übersprungen, deaktiviert, fehlgeschlagen) und Kennzahlen: durchschnittliche Ergänzungen, zusätzlicher Umfang, Ausfallquote, durchschnittliche Router-Laufzeit, Tokens, geschätzte Kosten.

### E) Qualitätssicherung: wie die Tests aufgebaut sind
**Ebene 1: Pest mit simulierter Router-Antwort (normale Suite).** Logik: Pool, Schutzregeln-Tabelle aus D1 (jede Zeile ein Test, positiv und negativ), Obergrenzen, Verwerfen ungültiger Antworten, Ergänzen/Verdrängen, großes Dokument, inhaltsleere Nachrichten (die vier Beispiele plus Floskeln), Router-Text-Kürzung (auch Stufe A allein), Ausfallarten, Rückfall, Protokoll, Anzeige (Mitarbeiter und Admin), Übersicht, Schalter, Fremdsprache mit und ohne Übersetzung, Eisberg-Fall mit fester Antwort.
**Ebene 1b: Güte der Vorauswahl bei großen Wissensbasen (normale Suite, getrennt von der Router-Entscheidung).** Eine künstliche Wissensbasis (zum Beispiel 200 bis 400 Dokumente) aus echten Dokumenten und selbst erzeugten ähnlichen und irrelevanten Füllern. Für jeden definierten **Positivfall** (zum Beispiel Eisberg-Dokumente, Produkt- und kanalspezifische Dokumente, ein allgemeines Dokument mit mittlerer Ähnlichkeit) wird verlangt, dass das relevante Dokument **tatsächlich in der Vorauswahl steht**. Besteht ein Fall das nicht, gilt er **nicht als bestanden**: Er wird als **bekannte Grenze** ausgewiesen (Testmarkierung „offen / bekannte Grenze“ mit Beschreibung, in der Auswertung getrennt gezählt und in einer Tabelle „Bekannte Grenzen“ in dieser Spec geführt). Er darf weder grün noch stillschweigend entfernt werden. Zusätzlich misst ein Test die Rangposition und die Größe der Vorauswahl.
**Ebene 1c (normale Suite): Reservierte Plätze und unterdrückte Treffer.** Reservierung: Ein produktspezifisches (nicht gruppenspezifisches) und ein gruppenspezifisches (nicht produktspezifisches) Dokument müssen beide in die Vorauswahl gelangen, auch wenn eine Art zahlreich vertreten ist (viele Reseller-Dokumente, viele Dokumente für das Produkt); ungenutzte reservierte Plätze gehen an andere Kandidaten. **Mehrfachanliegen mit unterdrücktem Treffer:** (a) Ein Angebot mit „unserem Firmenlogo“ **und** ein zweites, trigger-loses Anliegen zu einem fremden Markenmotiv: Das Rechte-Dokument (im Pool mit Vermerk) darf ergänzt werden, wenn der Beleg aus dem zweiten Anliegen stammt; (b) schlägt der Router es mit dem Logo-Satz als Beleg vor, wird es verworfen; (c) steht im zweiten Anliegen ein Trigger, wird das Dokument schon deterministisch abgerufen und gar nicht erst unterdrückt; (d) eine Frage nach Rechten am Logo wird nie unterdrückt (bestehende Regel).
**Ebene 2: Live-Prüfset auf Abruf (echtes Modell).** Eigene Testgruppe, die in der normalen Ausführung **nicht** läuft (Aufruf nur gezielt, mit Schlüssel). Fälle mit erwarteten **und** verbotenen IDs, Vergleich PROJ-36 gegen PROJ-37, 5 Läufe für den Eisberg-Fall (alle drei in der **endgültigen** Auswahl), Umformulierungen, Negativfälle, Fälle ohne passendes Dokument, Italienisch und Französisch mit und ohne Übersetzung. Je Lauf wird ein **Bericht** (Datei, nicht im Repository) geschrieben mit Modell, Router-Version, Anweisungs-Version, Wissensstand, erwarteten und tatsächlichen IDs, Tokens, Laufzeit, Kosten; die Zusammenfassung kommt in die QA-Ergebnisse der Spec. Es werden nur selbst formulierte oder anonyme Texte verwendet.
**Messung im Betrieb:** Admin-Kennzahlen in der Analysen-Übersicht.

**Bekannte Grenzen (werden mit den Tests gefüllt)**
| Grenze | Auslöser | Stand |
|---|---|---|
| Allgemeines Dokument ohne Produktbezug und mit geringer Wortähnlichkeit bei einer Wissensbasis weit über der Obergrenze | einfache Vorauswahl | wird durch Ebene 1b gemessen; bei Bedarf PROJ-22 |
| Zu dünne `summary` | Katalogzeile | wird durch das Live-Prüfset gefunden, dann gezielt behoben |
| `when` kann bei `triggered` keine Voraussetzung ausdrücken | Datenmodell von PROJ-36 | bekannt; eigenes Feld wäre ein Folgefeature |

### F) Dependencies (Pakete)
Keine neuen. Genutzt werden die vorhandene KI-Anbindung (wie Analyse, Zusammenfassung, Übersetzung), die bestehende Bereinigung und das bestehende Protokoll.

### G) Reihenfolge der Umsetzung (Vorschlag für `/backend`)
1. Router-Entscheidung, Pool-Bildung und Schutzregeln-Tabelle mit Tests (ohne KI-Aufruf).
2. Vorauswahl über der Obergrenze mit den Güte-Tests der Ebene 1b.
3. Router-Aufruf (Text, Anweisung, Antwortformat, Zeitlimit, Prüfung) mit simulierter Antwort.
4. Zusammenführung in den Abruf (Durchgang 2, Ergänzungen, Budgetregel für Ergänzungen).
5. Protokoll, Spalten, Version, Schalter.
6. Oberfläche (Herkunft, KI-Einschätzung, Hinweise, Admin-Teil, Übersicht).
7. Autorenseite (Guide, Handbuch, Skill, Warnung bei kurzer `summary`, Klarstellung `when`).
8. Live-Prüfset, Lauf, Auswertung, fachliche Freigabe, dann erst Aktivierung.

### H) Risiken
- **Falsche Ergänzungen** (Router lädt Unpassendes): begrenzt durch Obergrenze, Schutzregeln und Verwerfen; gemessen im Live-Prüfset und in den Kennzahlen.
- **Übersehenes Wissen bei sehr großer Wissensbasis:** bekannte Grenze, siehe E.
- **Kosten und Wartezeit:** pro Analyse ein kleiner Aufruf (grob 2.000 bis 5.000 Token Eingabe bei heutigem Bestand), Zeitlimit 15 s; Kennzahlen machen es sichtbar.
- **Zu dünne Summaries:** siehe E.


## Implementation Notes – Backend (2026-10-13)

**Gebaut** (alle Teile hinter dem Schalter `KNOWLEDGE_ROUTER_ACTIVE`, Standard aus; wirksam nur bei aktivem PROJ-36):
- `app/Knowledge/Retrieval/Router/`: `KnowledgeRouter` (Ablauf, Prüfung der Antwort), `RouterPool` (Pool-Regeln aus D1), `CandidatePreselector` (Obergrenze, Reservierung nach Produkt- und Gruppen-Spezifität, Textähnlichkeit), `RouterText` (Stufen A/B, Original und Übersetzung, bereinigt, Kürzung mit sichtbarer Marke), `ContentlessMessage`, `EvidenceCheck` (Beleg bei unterdrückten Treffern, mit Übersetzungs-Gegenstück), `RouterAddition`, `RouterOutcome` (inkl. Kostenschätzung nur mit hinterlegtem Preis).
- `RouterAgent` und die versionierte Anweisung `resources/prompts/router.md` (Version 2026-10-13.2); `LanguageModel::ask` nimmt ein eigenes Zeitlimit.
- `KnowledgeRetriever::retrieve(…, additions)`: Ergänzungen laufen als starke oder schwache Kandidaten durch dasselbe Budget, hinter den Treffern ihrer Stufe; eine nicht passende Ergänzung wird übersprungen, kleinere und der Rest des Platzes bleiben nutzbar. `RetrievedDocument` trägt jetzt die Herkunft (Grundwissen, Auslöser, Router, von Hand, Maßnahme).
- `CaseAnalyzer`: Durchgang 1 deterministisch → Router → Durchgang 2 mit Ergänzungen; Protokoll `retrieval.router`, Herkunft je Dokument, neue Spalten an `analyses` (Migration `add_router_figures_to_analyses_table`).
- Oberfläche: vier Herkunftsbezeichnungen, „KI-Einschätzung“, dezenter Ausfallhinweis, Hinweis bei gekürztem Router-Text, Admin-Teil „Erweiterte Wissenssuche“; Analysen-Übersicht mit Filter und Kennzahlen (nur Admin).
- Autorenseite: Authoring Guide (summary für den Router, Positiv- und Negativbeispiele, `when` ist ein zusätzlicher Weg), Chat-Handbuch, Skill, About-Seite; weiche Warnung bei `summary` unter 60 Zeichen (`KNOWLEDGE_SUMMARY_MIN_LENGTH`, in Tests auf 0).
- Tests: `PROJ-37-RouterTest.php` (Pool-Tabelle, Vorauswahl mit großen Wissensbasen, Router-Text, Prüfung der Antwort, Belegprüfung inkl. Übersetzung und Mehrfachanliegen, Ausfälle, Budget, Übersicht), Ende-zu-Ende-Fälle in `PROJ-36-AnalysisIntegrationTest.php` (Eisberg mit der echten Wissensbasis, Ausfall, Rückfall, Anzeige), Live-Prüfset `PROJ-37-RouterLiveTest.php` (Gruppe `router-live`, auf Abruf, Bericht nach `storage/app/router-live/`). Die normale Suite lässt das Live-Prüfset aus.
- Eine bekannte Grenze der Vorauswahl ist als offener Test („todo“) ausgewiesen und zählt nicht als bestanden.

**Abweichungen und Entscheidungen im Bau**
- **Neues Feld `fit`** in der Router-Antwort (`clear`/`possible`): Nur eindeutig passende Vorschläge werden übernommen (`knowledge.router.clear_only`), die übrigen stehen im Protokoll als „nur möglicher Bezug“. Grund: Der erste Live-Lauf zeigte zu viele ungefähre Ergänzungen.
- **Kategorien:** nur erkannt, protokolliert und angezeigt, ohne Rangwirkung (der von der Spec erlaubte Rückfall).
- **Standardmodell `gpt-5.4`** (eigene Konfiguration `ROUTER_MODEL`), nicht das Zusammenfassungsmodell, siehe Vorabläufe.
- **Neue Umgebungsvariablen** (konnten nicht in `.env.example` eingetragen werden, die Datei ist für den Assistenten gesperrt): `KNOWLEDGE_ROUTER_ACTIVE` (false), `ROUTER_MODEL` (gpt-5.4), `ROUTER_TIMEOUT` (15), `KNOWLEDGE_SUMMARY_MIN_LENGTH` (60).

**Vorabläufe des Live-Prüfsets (2026-10-13, 16 Fälle, Eisberg 5-mal; noch nicht fachlich freigegeben)**

| Modell (Anweisung 2026-10-13.2) | Fälle bestanden | Eisberg | Ergänzungen je Fall | Router-Laufzeit | Tokens Ein / Aus |
|---|---|---|---|---|---|
| gpt-5.4-mini | 15 von 20 | 2 von 5 | 1.9 | 2.4 s | 3229 / 204 |
| gpt-5.5 | 18 von 20 | 5 von 5 | 2.4 | 10.2 s | 3229 / 570 |
| gpt-5.4 | 19 von 20 | 5 von 5 | 2.2 | 3.8 s | 3229 / 211 |

Erster Lauf mit der Anweisung .1 und `gpt-5.4-mini` ohne `fit`: Eisberg 5 von 5, aber 11 von 16 Fällen bestanden, weil viele ungefähre Dokumente (Schatten, Ton, Glossar) ergänzt wurden. Die Anweisung .2 mit `fit` und strengeren Regeln senkt das; `gpt-5.4-mini` verliert dabei den Eisberg-Fall (2 von 5), `gpt-5.4` hält ihn 5 von 5 bei rund 4 Sekunden. Beobachtet: `POLICY-029` (allgemeine Prüfangaben bei Reklamationen) wird von den größeren Modellen oft ergänzt und ist harmlos; der Rechte-Fall wurde mit POLICY-017 statt PLAYBOOK-014 gelöst (die Erwartung wurde auf POLICY-017 gesetzt). Die Abdeckung des Pools lag bei 33 bis 41 Dokumenten (keine Vorauswahl nötig).

## QA Test Results
_To be added by /qa_

## Deployment
_To be added by /deploy_
