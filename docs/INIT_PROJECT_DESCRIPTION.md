# Customer Service Assist App – Projektbeschreibung für `/init`

> Diese Datei ist als ausführlicher Startkontext für den `/init`-Workflow des vorhandenen AI Coding Starter Kits gedacht.
>
> **Wichtig:** Der bestehende Starter-Kit definiert bereits Tech-Stack, lokale Entwicklungsumgebung, Coding-Regeln, Entwicklungsworkflow, Testing und Deployment-Konventionen. Diese Entscheidungen sollen im `/init` **nicht erneut diskutiert oder ersetzt** werden, sofern aus den Produktanforderungen kein echter Konflikt entsteht.
>
> Der `/init`-Workflow darf und soll offene Produktfragen weiterhin nach dem vorhandenen „Grill Me“-Prinzip klären und Empfehlungen abgeben.

---

## 1. Projektname

**Customer Service Assist App**

Arbeitstitel. Ein späterer Produktname ist für den MVP nicht relevant.

## 2. Ausgangslage

Das Unternehmen verkauft personalisierte Geschenkartikel über einen eigenen Online-Shop und über Amazon.

Kundenservice-Anfragen erreichen das Unternehmen per E-Mail und werden aktuell in **Zammad 7** als Tickets organisiert und beantwortet.

Das Ticketaufkommen ist saisonal stark schwankend:

- ruhige Tage: etwa **10–15 Tickets pro Tag**
- starke Peak-Tage: etwa **50–100 Tickets pro Tag**
- große Peaks: insbesondere Dezember/Januar
- weitere Peaks: Valentinstag, Muttertag, Ferienende / Back-to-School

Die grobe Verteilung der Anfragen ist:

- ca. **40 % WISMO** („Where is my order?“)
- ca. **40 % Reklamationen**
  - ungefähr die Hälfte berechtigt
  - ungefähr die Hälfte nicht berechtigt, z. B. wegen Bestellfehlern oder falschen Kundenerwartungen
- ca. **10 % Produkt- und Bestellfragen**
- ca. **3 % Angebotsanfragen**
- Rest: sonstige Kundenservicefälle

WISMO-Anfragen werden bereits über einen bestehenden n8n-Workflow weitgehend automatisiert bearbeitet und sind **nicht Teil des ersten MVP**.

Der größte Zeitaufwand entsteht bei Reklamationen, insbesondere wenn:

- der Kunde eine andere Erwartung an das Produkt hatte,
- das Produkt korrekt entsprechend der Kundenkonfiguration gefertigt wurde,
- ein Produktions- oder Gestaltungsprozess erklärt werden muss,
- der Kundenservice relevante Produkt- oder Prozesszusammenhänge nicht ausreichend versteht,
- eine falsche oder unvollständige Erstantwort zu mehreren weiteren E-Mails führt.

## 3. Organisatorisches Problem

Der Kundenservice wird regulär von einer Vollzeitkraft bearbeitet.

Bei Urlaub oder Krankheit müssen andere Personen einspringen. Zusätzlich werden in Peaks und am Wochenende Aushilfen benötigt.

Amazon verlangt eine Beantwortung von Kundenanfragen innerhalb von 24 Stunden.

Das heutige Problem ist weniger die reine Formulierung einer Antwort, sondern vor allem:

1. den Fall korrekt verstehen,
2. relevante Bestellinformationen berücksichtigen,
3. die richtige fachliche Regel anwenden,
4. Produkt- und Prozesswissen korrekt einbeziehen,
5. entscheiden, ob eine Reklamation berechtigt, unberechtigt oder noch nicht entscheidbar ist,
6. erkennen, welche Informationen fehlen,
7. die richtige Maßnahme empfehlen,
8. wissen, ob der Kundenservice selbst entscheiden darf oder eine Freigabe erforderlich ist,
9. dies dem Kunden verständlich und empathisch erklären.

Ein großer Teil dieses Wissens steckt heute in einzelnen Personen und ist nicht ausreichend strukturiert dokumentiert.

## 4. Projektziel

Die Customer Service Assist App soll Kundenservice-Mitarbeiter und Aushilfen bei der fachlichen Bearbeitung von Tickets unterstützen.

Sie soll **nicht autonom entscheiden oder senden**, sondern einen qualifizierten Vorschlag vorbereiten.

Das langfristige Ziel ist:

> Eine neue Aushilfe soll nach etwa 1–2 Stunden Einführung ungefähr 95 % der typischen Kundenservicefälle fachlich korrekt bearbeiten können.

Zusätzlich soll die Fehlerquote bei spezialisierten Reklamationsfällen deutlich sinken.

Die Anwendung soll das notwendige Unternehmenswissen direkt in die Bearbeitung bringen, sodass ein Mitarbeiter nicht aktiv in Dokumentationen suchen muss.

## 5. Grundprinzip

Die Anwendung kombiniert drei Arten von Kontext.

### 5.1 Falldaten

Aus dem konkreten Ticket:

- Betreff
- Ticketverlauf
- Kundenanfrage
- vorhandene Metadaten
- ggf. erkannte Bestellnummer

### 5.2 Bestelldaten

Aus dem internen Warenwirtschaftssystem **EOCS**:

- Bestellnummer
- Verkaufsquelle / Kanal, soweit verfügbar
- bestellte Produkte
- Varianten
- Personalisierungs- und Konfigurationsdaten
- Bestellstatus
- Produktionsstatus, soweit verfügbar
- Versandinformationen, soweit verfügbar

EOCS besitzt bereits eine rudimentäre API, die bei Bedarf erweitert werden kann.

### 5.3 Unternehmenswissen

Aus einer versionierten Knowledge Base im selben Git-Repository:

- Policies / verbindliche Regeln
- Befugnisse
- Produktwissen
- Prozesswissen
- Playbooks für typische Fallmuster
- Tonalitätsregeln
- Glossar
- kuratierte gute und schlechte Referenzfälle

Zusätzlich kann der Mitarbeiter zu jedem Fall einen eigenen Kontext ergänzen.

Beispiel:

> „Foto geprüft. Das fertige Produkt entspricht der Konfiguration. Der Kunde scheint die Vorschau anders verstanden zu haben.“

## 6. Kernidee des MVP

Der MVP ist eine **eigenständige interne Web-App neben Zammad**.

Der Mitarbeiter arbeitet weiterhin in Zammad, nutzt die Assist App aber für Analyse und Antwortvorschlag.

### Hauptablauf

1. Mitarbeiter öffnet die Assist App.
2. Mitarbeiter gibt eine **Zammad-Ticketnummer** ein.
3. Die App lädt das Ticket und den bisherigen Ticketverlauf aus Zammad.
4. Die App versucht, die zugehörige Bestellung zu erkennen.
5. Die App lädt passende Bestell- und Konfigurationsdaten aus EOCS.
6. Falls die Bestellung nicht automatisch erkannt werden kann, kann der Mitarbeiter eine Bestellnummer manuell ergänzen.
7. Der Mitarbeiter kann optional zusätzlichen Kontext in einem Freitextfeld eingeben.
8. Die App ermittelt den Falltyp und den relevanten Wissenskontext.
9. Die App sendet Fall, Bestelldaten, Mitarbeiterkontext und relevantes Unternehmenswissen an ein LLM.
10. Das LLM liefert ein strukturiertes Ergebnis.
11. Die App zeigt Bewertung, Empfehlung, Begründung, verwendetes Wissen und Antwortentwurf an.
12. Der Mitarbeiter prüft den Vorschlag.
13. Der Mitarbeiter kopiert den fertigen Antworttext manuell nach Zammad und sendet dort.
14. Der Mitarbeiter bewertet mit einem Klick, wie brauchbar der Vorschlag war.
15. Bei Bedarf meldet der Mitarbeiter eine Wissenslücke.

## 7. Fokus des MVP

### P0-Fallgruppen

- Reklamationen
- Produktfragen
- Fragen zum Bestell- oder Produktionsablauf

Besonderer Fokus:

- unberechtigte Reklamationen
- falsche Kundenerwartungen
- Bestell- oder Konfigurationsfehler des Kunden
- Fälle, bei denen Produkt- oder Prozesswissen zur Erklärung benötigt wird
- Fälle, bei denen heute unnötig an erfahrene Personen eskaliert wird

## 8. Explizit nicht Bestandteil des MVP

- automatisches Versenden von Kundenantworten
- vollständiger Ersatz von Zammad
- eigene gemeinsame Inbox für alle Tickets
- automatische Bearbeitung beim E-Mail-Eingang
- automatische Änderungen an Bestellungen
- automatische Gutschriften oder Erstattungen
- automatisches Anlegen von Ersatzaufträgen
- visuelle KI-Analyse von Reklamationsfotos
- Fachhändler-Erkennung
- langfristiges Customer Memory
- Angebotskalkulation
- semantische Vektorsuche als Voraussetzung
- automatisches Lernen aus Mitarbeiterkorrekturen
- komplexe Analytics-Dashboards
- WISMO-Automatisierung
- Spam-Erkennung
- Telefonie

Bereits funktionierende n8n-Prozesse für WISMO und Spam sollen nicht neu gebaut werden.

## 9. Umgang mit Bildern und Anhängen im MVP

Reklamationstickets können Bilder enthalten.

Im MVP soll das LLM Bilder **nicht automatisch fachlich bewerten**.

Der Mitarbeiter kann Anhänge selbst prüfen und das Ergebnis im zusätzlichen Kontextfeld beschreiben.

Beispiel:

> „Auf dem Foto ist kein Produktionsfehler erkennbar. Die Gravur entspricht der Bestellung.“

Die Architektur soll eine spätere Bildanalyse nicht verhindern, sie ist aber kein MVP-Ziel.

## 10. Erwartete Ausgabe der Assist App

Jede Analyse soll möglichst dieselbe strukturierte Ausgabe liefern.

### 10.1 Kurzfassung

- Was ist passiert?
- Was möchte der Kunde?

### 10.2 Klassifikation

Mindestens:

- Kategorie
- Unterkategorie / Fallmuster, sofern bestimmbar

Bei Reklamationen zusätzlich:

- **berechtigt**
- **unberechtigt**
- **unklar / noch nicht entscheidbar**

### 10.3 Empfohlene Maßnahme

Beispiele:

- Reklamation anerkennen
- kostenlose Nachbesserung
- Neuanfertigung
- Reklamation freundlich zurückweisen
- Produkt- oder Produktionsprozess erklären
- Foto anfordern
- Bestellinformationen anfordern
- Rückfrage stellen
- intern eskalieren

### 10.4 Befugnis

Die App soll darstellen:

- darf der Kundenservice selbst entscheiden?
- ist eine Freigabe erforderlich?
- wenn ja: welche Rolle / Stelle?

Befugnisse sind Teil der Knowledge Base.

### 10.5 Begründung

Kurze, verständliche fachliche Erklärung, warum diese Empfehlung gilt.

Keine versteckte Chain-of-Thought-Ausgabe ist erforderlich.

Es geht um eine nachvollziehbare **Begründungszusammenfassung**, die für den Mitarbeiter verständlich ist.

### 10.6 Fehlende Informationen

Wenn eine Entscheidung noch nicht möglich ist:

- welche Informationen fehlen?
- von wem müssen sie kommen?
- welche konkrete Rückfrage soll gestellt werden?

„Noch nicht entscheidbar“ ist ein gewünschtes und legitimes Ergebnis.

### 10.7 Verwendetes Wissen

Anzeige der relevanten Knowledge-IDs, z. B.:

- POLICY-003
- PRODUCT-3D-014
- PLAYBOOK-007
- PERMISSION-004

Die Quellen sollen im UI bei Bedarf einsehbar sein.

### 10.8 Confidence

Keine künstliche Prozentzahl.

Stattdessen:

- HOCH
- MITTEL
- NIEDRIG

mit nachvollziehbaren Gründen, z. B.:

- Bestellung gefunden
- Produkt erkannt
- Konfiguration vorhanden
- klare Policy vorhanden
- passendes Playbook vorhanden
- widersprüchliche Informationen
- zentrale Fakten fehlen

### 10.9 Interne To-dos

Falls erforderlich, z. B.:

- Produktionsleitung informieren
- Nachbesserung vorbereiten
- Freigabe einholen
- Daten nachtragen

Im MVP werden diese To-dos nur angezeigt und nicht automatisch ausgeführt.

### 10.10 Antwortentwurf

Ein vollständiger, direkt nutzbarer Kundenservice-Antworttext.

Anforderungen:

- freundlich
- verständlich
- empathisch
- sachlich
- keine unnötig langen Texte
- keine erfundenen Tatsachen
- keine Zusagen außerhalb definierter Regeln und Befugnisse
- Sprache passend zur Kundenanfrage, soweit dies sinnvoll umsetzbar ist

Der Mitarbeiter muss den Text bearbeiten können oder alternativ per Copy & Paste übernehmen.

## 11. Mitarbeiterkontext

Die App enthält ein gut sichtbares Freitextfeld:

**„Zusätzliche Informationen / eigene Einschätzung“**

Dieses Feld dient für Fakten, die:

- nicht in Zammad stehen,
- nicht in EOCS stehen,
- noch nicht in der Knowledge Base enthalten sind,
- vom Mitarbeiter manuell geprüft wurden.

Beispiele:

- „Foto geprüft, Produkt entspricht Bestellung.“
- „Kunde hat heute zusätzlich angerufen.“
- „Produktionsleiter bestätigt einen Produktionsfehler.“
- „Originalfoto war extrem unscharf.“
- „Hoher Bestellwert, bitte Kulanz prüfen.“

Das Feld ist optional.

## 12. Feedback nach jeder Analyse

Der MVP soll bereits eine einfache Feedbackfunktion besitzen.

Mindestens:

- **unverändert nutzbar**
- **leicht angepasst**
- **stark angepasst**
- **verworfen**

Optional:

- kurzer Kommentar: „Was war falsch oder fehlte?“

Zusätzlich:

- **Wissenslücke melden**

Eine Wissenslücke soll mit Ticket-ID, Analyse-ID und optionalem Kommentar gespeichert werden.

## 13. Logging und Nachvollziehbarkeit

Für jede Analyse soll nachvollziehbar gespeichert werden:

- Ticket-ID
- ggf. Bestell-ID
- Zeitpunkt
- Nutzer
- verwendeter Mitarbeiterkontext
- Klassifikation
- Empfehlung
- Antwortentwurf
- verwendete Knowledge-IDs
- verwendeter Knowledge-Stand / Git-Commit
- verwendete Prompt-Version
- verwendetes Modell / Provider
- Confidence
- Feedback des Mitarbeiters
- Wissenslückenmeldung, falls vorhanden

Ziel:

Eine Fehlentscheidung muss später reproduzierbar und untersuchbar sein.

## 14. LLM-Anforderung

Die Produktanforderung soll nicht hart auf einen einzelnen Anbieter festgelegt werden.

Die Anwendung soll so konzipiert werden, dass unterschiedliche LLM-Anbieter bzw. Modelle vergleichbar und austauschbar bleiben.

Wichtig ist:

- strukturierte Ausgabe
- zuverlässige Regelanwendung
- kein Erfinden fehlender Fakten
- „unklar“ als zulässige Antwort
- nachvollziehbare Quellenreferenzen auf Knowledge-IDs

## 15. Knowledge Retrieval im MVP

Der MVP soll **nicht von einer komplexen Vektordatenbank oder RAG-Infrastruktur abhängen**.

Die erste Version darf Wissen deterministisch auswählen.

Beispiel:

1. globale aktive Policies
2. globale aktive Befugnisse
3. allgemeine Tonalitätsregeln
4. Produktwissen zum erkannten Produkt
5. passende Prozesse
6. passende Playbooks anhand Kategorie / Themen / Tags
7. wenige kuratierte Referenzbeispiele, falls sinnvoll

Später kann semantische Suche ergänzt werden.

## 16. Knowledge Base als fachliche Source of Truth

Die Knowledge Base liegt im **gleichen Git-Repository wie die Assist App**.

Sie wird als Markdown-Dateien mit strukturiertem YAML-Frontmatter gepflegt.

Git ist die fachliche Versionshistorie.

Die Knowledge Base enthält nicht:

- aktuelle Bestelldaten
- Ticket-Historien als unkuratierte Rohdaten
- Kundendaten
- Promptlogik
- API-Credentials
- produktive Laufzeitdaten

Historische Tickets dürfen als Quelle für neue Regeln und Beispiele dienen, werden aber nicht ungeprüft zur fachlichen Wahrheit.

## 17. Wissenshierarchie

Bei Konflikten gilt grundsätzlich folgende fachliche Priorität:

1. **Policies**
2. **Permissions / Befugnisse**
3. **Produkt- und Prozesswissen**
4. **Playbooks**
5. **kuratierte Referenzfälle**

Ein alter Referenzfall darf keine aktuelle Policy überschreiben.

## 18. Baseline vor produktivem Einsatz

Vor oder parallel zur ersten Entwicklung soll eine fachliche Baseline erstellt werden.

Empfehlung:

Mindestens **50 historische Reklamationstickets** aus den letzten Monaten anhand eines festen Rasters bewerten.

Mögliche Fehlerarten:

- fachliche Entscheidung falsch
- falsche Zusage
- Produkt oder Prozess falsch erklärt
- notwendige Rückfrage vergessen
- unnötig eskaliert
- unnötigen Mailwechsel verursacht
- Tonalität problematisch

## 19. Evaluation / Gold-Testset

Zusätzlich soll ein separates Testset aufgebaut werden.

Startgröße:

- zunächst 30–50 gut verstandene echte Fälle
- später 100–300 Fälle

Jeder Fall enthält eine erwartete fachliche Lösung.

Dieses Testset darf nicht automatisch als Retrieval-Wissen verwendet werden.

Es dient dazu, Änderungen an:

- Knowledge Base
- Prompts
- Retrieval
- Modell

gegen bekannte Fälle zu prüfen.

## 20. Historische Ticket-Auswertung

Zwei Jahre Zammad-Historie sollen später bzw. parallel automatisiert ausgewertet werden.

Ziel:

- häufigste Fallmuster finden
- wiederkehrende Reklamationsursachen erkennen
- 200–300 interessante Kandidaten für manuelle Bewertung vorschlagen
- gute Beispiele finden
- schlechte Beispiele finden
- mögliche Wissenslücken erkennen
- Kandidaten für das Gold-Testset identifizieren

Historische Antworten gelten nicht automatisch als korrekt.

## 21. Erfolgskriterien

Langfristiges Hauptziel:

> Neue Aushilfen können nach 1–2 Stunden Einführung ungefähr 95 % typischer Anfragen fachlich korrekt bearbeiten.

Weitere wichtige Messgrößen:

- Anteil unverändert nutzbarer KI-Entwürfe
- Anteil leicht / stark korrigierter Entwürfe
- Anteil verworfener Entwürfe
- fachliche Fehlerquote bei Reklamationen
- unnötige Eskalationen
- Anzahl E-Mails pro Reklamationsfall
- Zeitaufwand erfahrener Mitarbeiter / Geschäftsführung
- Anzahl gemeldeter Wissenslücken
- Entwicklung der Wissenslücken über die Zeit

## 22. Sicherheits- und Qualitätsprinzipien

1. Ein Mensch gibt im MVP jede Kundenantwort frei.
2. Das Modell darf keine fehlenden Fakten erfinden.
3. „Unklar“ ist besser als eine erfundene Entscheidung.
4. Fehlende Informationen werden explizit benannt.
5. Empfehlungen müssen auf nachvollziehbaren Knowledge-Quellen basieren.
6. Kritische Befugnisse und Grenzen sollen möglichst explizit und strukturiert beschrieben sein.
7. Alte Beispieltickets sind keine verbindlichen Regeln.
8. Das System soll keine fachliche Regel ausschließlich in Prompttext verstecken.
9. Die Knowledge Base ist die fachliche Source of Truth.
10. Bestehende funktionierende Automatisierungen werden im MVP nicht unnötig neu gebaut.

## 23. Erwartete erste P0-Features

Die genaue Feature-Zerlegung soll `/init` erstellen. Aus Produktsicht sollten mindestens folgende Bereiche als P0 geprüft werden:

### Ticket laden
- Ticketnummer eingeben
- Zammad-Ticket laden
- Ticketverlauf anzeigen
- Fehlerzustände sauber behandeln

### Bestellung laden
- Bestellnummer automatisch erkennen, soweit möglich
- EOCS-Daten abrufen
- manuelle Bestellnummer als Fallback
- Bestelldaten strukturiert anzeigen

### Mitarbeiterkontext
- Freitext ergänzen
- Kontext zusammen mit Analyse verwenden

### Knowledge Base
- Markdown-Dateien einlesen
- YAML-Frontmatter validieren
- nur aktive Dokumente verwenden
- relevante Dokumente deterministisch auswählen
- Knowledge-IDs anzeigen

### Fallanalyse
- strukturierten LLM-Aufruf ausführen
- feste Ergebnisstruktur validieren
- „unklar“ unterstützen

### Ergebnisansicht
- Zusammenfassung
- Klassifikation
- Empfehlung
- Befugnis
- Begründung
- fehlende Informationen
- interne To-dos
- verwendete Knowledge-Quellen
- Confidence
- Antwortentwurf

### Antwort übernehmen
- Antworttext bequem kopieren
- noch kein automatisches Senden an Zammad

### Feedback
- unverändert / leicht angepasst / stark angepasst / verworfen
- optionaler Kommentar
- Wissenslücke melden

### Logging
- Analyse reproduzierbar protokollieren
- Knowledge-Commit
- Prompt-Version
- Modell / Provider
- Feedback

## 24. Manuelle Fallbacks sind erwünscht

Der MVP soll nicht daran scheitern, dass eine Schnittstelle noch unvollständig ist.

Temporäre Fallbacks dürfen existieren, z. B.:

- Tickettext manuell einfügen
- Bestellnummer manuell eingeben
- Bestelldaten manuell ergänzen

Das Ziel des MVP ist zuerst die Validierung der fachlichen Assistenz.

## 25. UX-Prinzip

Die Anwendung soll intern und funktional sein.

Prioritäten:

1. schnelle Bearbeitung
2. gute Lesbarkeit
3. wenig Klicks
4. fachliche Transparenz
5. keine unnötigen Funktionen

Kein Anspruch auf vollständige Helpdesk-Funktionalität im MVP.

## 26. Spätere Ausbaustufen – nicht für den ersten Build

Mögliche spätere Schritte:

- automatische Analyse bei Ticket-Eingang
- eigene Agenten-Inbox
- direkte Antwort aus der Assist App über Zammad
- automatische Anzeige bereits vorbereiteter Analysen
- semantisches Retrieval / Embeddings
- Vergleich und Lernen aus tatsächlichen Mitarbeiterkorrekturen
- Bildanalyse
- Customer Memory
- Fachhändler-Erkennung
- Angebotsanfragen
- automatische interne Aktionen
- Teilautomatisierung besonders sicherer Falltypen
- Ersatz von Teilen der Zammad-Oberfläche
- ggf. langfristig vollständige eigene Kundenservice-Oberfläche

Diese Punkte sollen die MVP-Architektur nicht unnötig verkomplizieren.

## 27. Was `/init` aus dieser Beschreibung erzeugen soll

Bitte aus diesem Projektkontext:

1. ein vollständiges PRD erstellen,
2. die Anforderungen kritisch hinterfragen,
3. noch offene fachliche Produktentscheidungen im „Grill Me“-Verfahren klären,
4. eine priorisierte P0/P1/P2-Feature-Map erstellen,
5. den MVP so klein wie sinnvoll halten,
6. bestehende Starter-Kit-Entscheidungen zu Tech-Stack, Entwicklungsworkflow und lokaler Umgebung nicht unnötig neu diskutieren,
7. die separat dokumentierte Knowledge-Base-Struktur als feste Ausgangsbasis berücksichtigen,
8. ein sinnvolles erstes Feature zum Spezifizieren und Bauen empfehlen.
