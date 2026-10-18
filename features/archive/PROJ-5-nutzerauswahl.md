# PROJ-5: Nutzerauswahl

## Status: Approved
**Created:** 2026-10-02
**Last Updated:** 2026-10-05

## Dependencies
- Requires: PROJ-1 (App-Grundgerüst mit LOOXIS-Design) – Kopfleiste mit freigehaltenem Bereich rechts, Basis-Komponenten
- Wird genutzt von: PROJ-9 (Fallanalyse), PROJ-11 (Analyse-Protokoll), PROJ-12 (Feedback und Wissenslücke) – sie lesen den gewählten Nutzer und verlangen ihn vor dem Absenden

Im MVP gibt es keine Anmeldung (PRD). Die Nutzerauswahl ist keine Sicherheitsfunktion, sondern ordnet Analysen und Rückmeldungen einer Person zu.

## User Stories
- Als Mitarbeiter möchte ich meinen Namen einmal auswählen und danach nicht mehr gefragt werden, damit ich ohne Umweg arbeiten kann.
- Als Mitarbeiter möchte ich jederzeit sehen, unter welchem Namen ich arbeite, damit ich an einem geteilten Rechner nicht versehentlich unter einem fremden Namen analysiere.
- Als Aushilfe an einem geteilten Rechner möchte ich den Namen mit einem Klick wechseln können, damit meine Analysen mir zugeordnet sind.
- Als Verantwortlicher möchte ich, dass jede Analyse, jedes Feedback und jede gemeldete Wissenslücke einem Namen aus einer festen Liste zugeordnet ist, damit Auswertungen nach Person möglich sind und keine Schreibvarianten entstehen.
- Als Mitarbeiter möchte ich Knowledge und „Über die App" auch ohne Namensauswahl ansehen können, damit ich mich vorab informieren kann.

## Out of Scope
- Anmeldung mit Passwort, Rollen, Admin-Rechte – PROJ-15.
- Pflege der Namensliste in der App – PROJ-15 (bis dahin pflegt ein Entwickler die Liste).
- Festlegen, wer ohne Login als Admin gilt (offene Frage aus PRD zu PROJ-23) – wird in PROJ-23 geklärt.
- Sammeleintrag „Andere / Aushilfe" und freie Namenseingabe.
- Nutzerbezogene Einstellungen (z. B. Sprache, Ansicht).
- Übersicht „Meine Analysen" – PROJ-14.
- Das Speichern des Namens mit einer Analyse, einem Feedback oder einer Wissenslücke – PROJ-11 und PROJ-12; PROJ-5 stellt den gewählten Namen nur bereit.

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

### Namensliste
- [ ] Angenommen die App ist frisch eingerichtet, wenn die Auswahl geöffnet wird, dann enthält sie genau die Namen Cara, Etienne, Johannes, Kerstin, Nele und Thomas, alphabetisch sortiert.
- [ ] Angenommen ein Entwickler ergänzt oder entfernt einen Namen in der Konfiguration, wenn die App neu gestartet oder neu veröffentlicht ist, dann zeigt die Auswahl die geänderte Liste, ohne dass Programmcode geändert wurde.
- [ ] Angenommen die Auswahl ist geöffnet, wenn der Nutzer sie betrachtet, dann gibt es keinen Eintrag „Andere" und kein Freitextfeld.

### Erste Auswahl
- [ ] Angenommen in diesem Browser wurde noch kein Name gewählt, wenn eine beliebige Seite der App geöffnet wird, dann steht rechts in der Kopfleiste „Name wählen" deutlich als Aufforderung erkennbar.
- [ ] Angenommen in diesem Browser wurde noch kein Name gewählt, wenn die Seite „Ticket analysieren" geöffnet wird, dann erscheint oberhalb des Inhalts ein Hinweis „Bitte wähle zuerst deinen Namen" mit der Namensauswahl direkt im Hinweis.
- [ ] Angenommen kein Name ist gewählt, wenn der Nutzer die Knowledge-Übersicht, ein Knowledge-Dokument oder „Über die App" öffnet, dann ist die Seite ohne Einschränkung nutzbar.
- [ ] Angenommen der Nutzer wählt einen Namen, wenn die Wahl erfolgt ist, dann steht der Name sofort rechts in der Kopfleiste, ein eventueller Hinweis verschwindet, und die aktuelle Seite bleibt mit allen Eingaben erhalten.

### Merken und Anzeigen
- [ ] Angenommen ein Name wurde gewählt, wenn der Nutzer die Seite neu lädt, eine andere Seite öffnet oder den Browser schließt und später wieder öffnet, dann ist derselbe Name weiterhin gewählt und wird nicht erneut abgefragt.
- [ ] Angenommen ein Name ist gewählt, wenn eine beliebige Seite der App angezeigt wird, dann steht der Name rechts in der Kopfleiste, auch auf schmalen Bildschirmen.
- [ ] Angenommen ein Name ist in einem Browser gewählt, wenn die App in einem anderen Browser oder auf einem anderen Rechner geöffnet wird, dann ist dort noch kein Name gewählt.

### Wechseln
- [ ] Angenommen ein Name ist gewählt, wenn der Nutzer auf den Namen in der Kopfleiste klickt, dann öffnet sich die Namensauswahl mit dem aktuellen Namen als markiertem Eintrag.
- [ ] Angenommen die Namensauswahl ist geöffnet, wenn der Nutzer einen anderen Namen wählt, dann gilt der neue Name ohne Rückfrage sofort für alle weiteren Aktionen in diesem Browser.
- [ ] Angenommen die Namensauswahl ist geöffnet, wenn der Nutzer daneben klickt oder Escape drückt, dann schließt sie sich, und der bisherige Name bleibt gewählt.
- [ ] Angenommen ein Name ist gewählt, wenn der Nutzer die Auswahl öffnet, dann gibt es keinen Eintrag zum Zurücksetzen auf „kein Name"; gewechselt wird nur zu einem anderen Namen.

### Bereitstellung für andere Features
- [ ] Angenommen ein Name ist gewählt, wenn eine Analyse, ein Feedback oder eine Wissenslücke abgeschickt wird (PROJ-9, PROJ-11, PROJ-12), dann ist dem Server der zu diesem Zeitpunkt gewählte Name bekannt.
- [ ] Angenommen kein Name ist gewählt, wenn eine dieser Aktionen abgeschickt werden soll, dann wird sie nicht ausgeführt, und der Nutzer wird aufgefordert, zuerst seinen Namen zu wählen; seine Eingaben bleiben erhalten.
- [ ] Angenommen ein Name kommt beim Server an, der nicht (mehr) auf der Liste steht, wenn er geprüft wird, dann gilt er als „kein Name gewählt".

### Bedienbarkeit
- [ ] Angenommen ein Tastatur- oder Screenreader-Nutzer erreicht den Namen in der Kopfleiste, wenn er ihn bedient, dann ist die Auswahl per Tastatur öffnen, wählen und schließen möglich und wird als „Name wählen" bzw. „Angemeldet als … – Name wechseln" angesagt.

## Edge Cases
- **Name wird aus der Liste entfernt**, ist aber in einem Browser noch gewählt: Beim nächsten Aufruf gilt dort „kein Name gewählt", die Kopfleiste zeigt wieder „Name wählen". Frühere Analysen behalten ihren Namen (PROJ-11).
- **Browser speichert nichts** (privates Fenster, gelöschte Daten, gesperrte Cookies): Der Name muss in jeder Sitzung neu gewählt werden; die App funktioniert sonst normal.
- **Zwei Tabs offen, Name in einem gewechselt:** Der andere Tab zeigt bis zum Neuladen den alten Namen in der Kopfleiste; eine abgeschickte Aktion gilt aber für den Namen, der beim Absenden im Browser gewählt ist.
- **Geteilter Rechner, falscher Name gewählt:** Der Name ist immer sichtbar; Wechsel mit einem Klick. Bereits abgeschickte Analysen bleiben dem damals gewählten Namen zugeordnet und werden nicht umgeschrieben.
- **Langer Name oder langer Seitentitel:** Der Name wird nicht gekürzt, solange er in die Kopfleiste passt; der Seitentitel weicht mit Auslassungspunkten (PROJ-1).
- **Namensliste in der Konfiguration ist leer:** Die Kopfleiste zeigt „Keine Namen hinterlegt"; Aktionen, die einen Namen verlangen, sind nicht möglich, Lesen bleibt möglich.
- **Gleicher Vorname zweimal** (z. B. zwei Aushilfen namens Nele): Die Liste verlangt eindeutige Einträge; der Entwickler unterscheidet sie beim Eintragen (z. B. „Nele K.").
- **Manipulierter Wert im Browser:** Ein Name, der nicht auf der Liste steht, wird wie „kein Name gewählt" behandelt (siehe Akzeptanzkriterien).

## Technical Requirements (optional)
- Kein Login, keine Datenbank für die Namensliste im MVP; die Liste steht in der Konfiguration.
- Die Auswahl bleibt pro Browser dauerhaft gespeichert (nicht nur für die Sitzung).
- Die Kopfleiste darf durch die Auswahl nicht spürbar langsamer laden.
- Gestaltung nach `docs/design-system.md`.

## Open Questions
- [x] Wie verhält sich die Namensanzeige zusammen mit dem Burger-Menü aus PROJ-26 auf schmalen Bildschirmen? → Name links neben dem Menü-Symbol, bei Platzmangel nur das Initial im Kreis (2026-10-05).

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Name ist erst für Analyse, Feedback und Wissenslücke Pflicht | Genau dort wird er gebraucht (Protokoll, Auswertung); Lesen von Knowledge und „Über die App" soll ohne Hürde möglich sein | 2026-10-05 |
| Hinweis auf „Ticket analysieren" statt sperrendem Dialog beim ersten Besuch | Fordert dort auf, wo gearbeitet wird, ohne die übrigen Seiten zu blockieren | 2026-10-05 |
| Wahl bleibt dauerhaft im Browser, Wechsel jederzeit mit einem Klick ohne Rückfrage | Stammkräfte werden nicht täglich gefragt; an geteilten Rechnern zeigt die dauerhafte Anzeige sofort, wer gewählt ist | 2026-10-05 |
| Kein Zurücksetzen auf „kein Name" | Ein gewählter Name wird nur gewechselt; vermeidet Analysen ohne Zuordnung | 2026-10-05 |
| Feste Liste in der Konfiguration, kein „Andere", keine Freitexteingabe | Jede Analyse ist einer Person zugeordnet, keine Schreibvarianten; neue Aushilfen trägt ein Entwickler ohne Programmänderung ein | 2026-10-05 |
| Listenpflege in der App erst mit PROJ-15 | Laut PRD wandert die Liste mit der Benutzerverwaltung in die Datenbank | 2026-10-05 |
| Server prüft den Namen gegen die Liste | Ein im Browser veränderter oder veralteter Wert darf nicht ins Protokoll gelangen | 2026-10-05 |
| Namensliste um Thomas ergänzt: Cara, Etienne, Johannes, Kerstin, Nele, Thomas | Vorgabe des Product Owners; alphabetische Reihenfolge erleichtert das Finden | 2026-10-05 |
| Auf schmalen Bildschirmen steht der Name links neben dem Menü-Symbol (PROJ-26), bei Platzmangel nur das Initial im Kreis | Name bleibt auch mobil sichtbar, ohne den Seitentitel zu verdrängen | 2026-10-05 |
| Ausdrücklich keine Sicherheitsfunktion | Zugangsschutz im MVP über internes Netz/VPN und gemeinsames Passwort (PRD, `/deploy`) | 2026-10-05 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|
| Dauerhafter, verschlüsselter Cookie (ca. 5 Jahre) statt localStorage | Server kennt den Namen bei jeder Anfrage: Kopfleiste ohne Flackern, Prüfung beim Absenden ohne Extra-Feld | 2026-10-05 |
| Namensliste in eigener `config/staff.php` | Leicht auffindbar und änderbar; wird mit PROJ-15 durch die Datenbank ersetzt | 2026-10-05 |
| Name wird bei jedem Lesen gegen die Liste geprüft | Entfernte oder manipulierte Namen gelten als „kein Name" (Spec) | 2026-10-05 |
| Speichern per POST mit Form Request und `@csrf`; Alpine sendet im Hintergrund, ohne JS normales Absenden | Eingaben auf der Seite bleiben erhalten; Projektregel „Form Requests für jede Eingabe" | 2026-10-05 |
| Gemeinsamer Alpine-Zustand für Kopfleiste und Hinweis | Beide aktualisieren sich nach der Wahl gleichzeitig, ohne Neuladen | 2026-10-05 |
| Middleware „Name erforderlich" jetzt bauen, ab PROJ-9 anwenden | Einheitliches Verhalten für Analyse, Feedback und Wissenslücke; neue Middleware ist laut Projektregel freigabepflichtig; vom Product Owner am 2026-10-05 freigegeben | 2026-10-05 |
| Unter 768 px nur Initial im Kreis | Platz in der Kopfleiste neben dem späteren Menü-Symbol (PROJ-26) | 2026-10-05 |

---
<!-- Sections below are added by subsequent skills -->

## Tech Design (Solution Architect)

### Überblick
Keine Datenbank, kein Login. Die Namensliste steht in einer eigenen kleinen Konfigurationsdatei. Der gewählte Name wird in einem **dauerhaften, verschlüsselten Cookie** des Browsers gespeichert. Dadurch kennt der Server den Namen bei jedem Seitenaufruf: Er kann ihn direkt in die Kopfleiste schreiben und beim Absenden einer Analyse prüfen. Gewählt wird über ein kleines Aufklapp-Menü in der Kopfleiste, das ohne Neuladen der Seite speichert. So bleiben Eingaben auf der Seite erhalten.

### A) Bausteine
```
Kopfleiste (Layout aus PROJ-1, Bereich rechts)
+-- Namensanzeige (neue Blade-Komponente, auf jeder Seite)
    +-- ohne Namen: Knopf „Name wählen" (auffällig)
    +-- mit Namen: Name als Knopf („Angemeldet als … – Name wechseln")
    +-- schmaler Bildschirm: nur Initial im Kreis, später links neben dem Menü-Symbol (PROJ-26)
    +-- Aufklapp-Liste mit allen Namen, aktueller markiert
        +-- schließt bei Auswahl, Klick daneben, Escape

Seite „Ticket analysieren"
+-- Hinweis „Bitte wähle zuerst deinen Namen" mit derselben Namensliste
    (nur ohne gewählten Namen; verschwindet nach der Wahl ohne Neuladen)

Server
+-- Namensliste (config/staff.php)
+-- „Aktueller Mitarbeiter": liest den Cookie, prüft ihn gegen die Liste,
|   liefert Name oder „kein Name" an Kopfleiste und spätere Features
+-- Speichern der Wahl: eigene Adresse (POST), prüft den Namen über eine Form Request,
|   setzt den Cookie; antwortet dem Aufklapp-Menü direkt, ohne Seitenwechsel
+-- Schutz für spätere Aktionen: eine Middleware „Name erforderlich" für die
    Absende-Adressen von PROJ-9, PROJ-11 und PROJ-12 (zurück mit Eingaben und Hinweis)
```

### B) Daten
- **Namensliste:** in `config/staff.php`, eine einfache Liste. Ausgabe immer alphabetisch, doppelte Einträge zählen einmal. Start: Cara, Etienne, Johannes, Kerstin, Nele, Thomas.
- **Cookie:** ein Eintrag mit dem gewählten Namen, verschlüsselt (Laravel-Standard), Laufzeit etwa fünf Jahre, nur für diese App. Er gilt für alle Tabs desselben Browsers.
- Es gibt keine Datenbank und kein Protokoll der Wechsel. Das Speichern des Namens zu einer Analyse übernimmt PROJ-11.

### C) Technische Entscheidungen (für Nicht-Entwickler)
- **Cookie statt Browser-Speicher (localStorage):** Den Browser-Speicher kann nur die Seite im Browser lesen, nicht der Server. Dann würde die Kopfleiste kurz leer erscheinen und erst danach den Namen zeigen. Außerdem müsste jede Analyse den Namen extra mitschicken. Ein Cookie kommt bei jeder Anfrage automatisch mit.
- **Verschlüsselter Cookie:** Laravel verschlüsselt Cookies ohnehin. Ein von Hand veränderter Wert ist damit unlesbar. Trotzdem prüft der Server jeden Namen gegen die Liste, wie es die Spec verlangt: Ein Name, der aus der Liste entfernt wurde, gilt dann als „kein Name".
- **Wählen ohne Neuladen:** Die Spec verlangt, dass Eingaben auf der Seite erhalten bleiben. Deshalb speichert das Aufklapp-Menü (Alpine.js) die Wahl im Hintergrund. Kopfleiste und Hinweis aktualisieren sich sofort über einen gemeinsamen Zustand. Ohne JavaScript funktioniert dasselbe Formular als normales Absenden mit Rückkehr auf die Seite.
- **Form Request für die Wahl:** Die Projektregel verlangt Form Requests für jede Eingabe. Er erlaubt nur Namen aus der Liste. Das Formular trägt `@csrf`.
- **Middleware „Name erforderlich" schon jetzt, angewendet erst ab PROJ-9:** PROJ-9, PROJ-11 und PROJ-12 schützen ihre Absende-Adressen dann mit einer Zeile, und das Verhalten ist überall gleich: zurück zur Seite, Eingaben bleiben, Hinweis „Bitte wähle zuerst deinen Namen". Laut Projektregeln braucht neue Middleware eine ausdrückliche Freigabe; sie wird hiermit zur Freigabe vorgelegt.
- **Eigene Konfigurationsdatei `config/staff.php`:** Die Liste ist leicht zu finden und zu ändern, ohne andere Einstellungen anzufassen. Mit PROJ-15 wird sie durch die Datenbank ersetzt.
- **Breiten-Verhalten:** Unter 768 px zeigt die Kopfleiste nur das Initial im Kreis, mit vollem Namen für Screenreader. Das passt zur Entscheidung für das Burger-Menü (PROJ-26).

### D) Abhängigkeiten
Keine neuen Pakete (Alpine.js und Laravel-Cookies sind vorhanden).

### E) Hinweise für /frontend und /backend
- Frontend: Komponente für die Namensanzeige mit Aufklapp-Liste, gemeinsamer Alpine-Zustand für Kopfleiste und Hinweis, Hinweis auf „Ticket analysieren", Darstellung „Keine Namen hinterlegt" bei leerer Liste.
- Backend: `config/staff.php`, Dienst „Aktueller Mitarbeiter", Route zum Speichern (POST) mit Form Request, Cookie setzen, Middleware „Name erforderlich" samt Tests (an einer Test-Route, solange es PROJ-9 nicht gibt).
- Tests: Anzeige mit und ohne Cookie, Wechsel, unbekannter oder entfernter Name, leere Liste, Ablehnung fremder Namen beim Speichern, CSRF, Middleware-Verhalten mit erhaltenen Eingaben.


## Implementation Notes (Frontend + Backend)
**Gebaut am 2026-10-05.** Frontend und Serverteil in einem Durchgang, weil die Auswahl ohne den Speicher-Endpunkt nicht funktioniert; `/backend` entfällt.

- **Konfiguration:** `config/staff.php` mit `names` (Cara, Etienne, Johannes, Kerstin, Nele, Thomas), Cookie-Name `staff_name`, Laufzeit 5 Jahre.
- **Server:** `App\Staff\StaffDirectory` (Liste alphabetisch und ohne Doppelte, Prüfung, aktueller Name aus dem Cookie), `POST /name` (`staff.select`) mit `StaffSelectionController` und `SelectStaffRequest` (nur Namen aus der Liste, exakte Schreibweise). Antwort JSON für das Aufklapp-Menü, sonst Rückleitung auf die Herkunftsseite. Cookie verschlüsselt, `HttpOnly`, `SameSite=Lax`.
- **Middleware** `EnsureStaffSelected`, Alias `staff.selected` in `bootstrap/app.php` (vom Product Owner freigegeben). Ohne gültigen Namen: zurück mit Eingaben und Hinweis „Bitte wähle zuerst deinen Namen." (Session-Meldung `error`, im Layout als Alert); bei JSON-Anfragen 409. Noch an keiner echten Route angewendet, ab PROJ-9.
- **Frontend:** Alpine-Store `staff` in `resources/js/app.js` (gemeinsamer Zustand, Speichern per `fetch` mit CSRF-Header). Komponenten `x-staff.picker` (Kopfleiste) und `x-staff.hint` („Ticket analysieren"). Ein View-Composer liefert `staffNames` und `currentStaff` an Layout und Analyse-Seite. Der Name wird serverseitig vorgerendert (kein Flackern); unter `md` nur das Initial im Kreis. Tippflächen mindestens 44 px, aktueller Name mit `aria-pressed` und Haken, Escape und Klick daneben schließen.
- **Abweichung / Hinweis:** Der Server setzt 5 Jahre. Aktuelle Browser begrenzen Cookies jedoch auf höchstens 400 Tage. Wer ein gutes Jahr lang den Namen nicht wechselt, wird danach einmal neu gefragt.
- **Geprüft gegen die laufende App:** ohne CSRF-Token 419, mit Token gespeichert; Kopfleiste zeigt danach „Angemeldet als Nele".
- **Nicht im Browser geprüft:** Aufklappen, Fokusführung und das Aktualisieren ohne Neuladen (Alpine) konnten mangels Browser-Werkzeug nur am Markup geprüft werden.
- **Tests:** `tests/Feature/PROJ-5-StaffSelectionTest.php` (21 Tests, mit Datensätzen 30 Fälle, inkl. Middleware an Test-Routen). Gesamte Suite: 427 Tests grün. Assets neu gebaut.

## QA Test Results

**Tested:** 2026-10-05
**App URL:** http://localhost:8081
**Tester:** QA Engineer (AI)

Geprüft über Pest (Feature-Tests inkl. Middleware an Test-Routen) und HTTP-Abrufe gegen die laufende App. Das Verhalten im Browser (Aufklappen, Wechsel ohne Neuladen, Merken nach Browser-Neustart, Initial bei schmalem Fenster) hat der Product Owner am 2026-10-05 im Browser bestätigt; ein eigenes Browser-Werkzeug stand nicht zur Verfügung.

### Acceptance Criteria Status

#### Namensliste
- [x] Genau Cara, Etienne, Johannes, Kerstin, Nele, Thomas, alphabetisch
- [x] Änderung in `config/staff.php` wirkt ohne Codeänderung (sortiert, ohne Doppelte und Leereinträge)
- [x] Kein „Andere", kein Freitextfeld

#### Erste Auswahl
- [x] Ohne Namen „Name wählen" in der Kopfleiste auf allen Seiten
- [x] Hinweis mit Namensauswahl auf „Ticket analysieren"
- [x] Knowledge, Dokumente und „Über die App" ohne Namen uneingeschränkt nutzbar, ohne Hinweis
- [x] Nach der Wahl sofort sichtbar, Hinweis weg, Seite ohne Neuladen (vom Product Owner im Browser bestätigt)

#### Merken und Anzeigen
- [x] Name bleibt über Neuladen, Seitenwechsel und Browser-Neustart (Cookie, Product Owner bestätigt; Grenze 400 Tage siehe Hinweise)
- [x] Name auf jeder Seite in der Kopfleiste, unter 768 px als Initial
- [x] Anderer Browser ohne Cookie: kein Name gewählt

#### Wechseln
- [x] Klick auf den Namen öffnet die Liste, aktueller Name markiert (`aria-pressed`, Haken)
- [x] Anderer Name gilt sofort, ohne Rückfrage
- [x] Klick daneben und Escape schließen, Name bleibt
- [x] Kein Eintrag zum Zurücksetzen

#### Bereitstellung für andere Features
- [x] Mit Namen kennt der Server den Namen beim Absenden (Test-Route)
- [x] Ohne Namen wird die Aktion nicht ausgeführt, zurück mit Eingaben und Hinweis; JSON-Anfragen erhalten 409
- [x] Nicht (mehr) gelisteter Name gilt als „kein Name"

#### Bedienbarkeit
- [x] Per Tastatur bedienbar, Ansage „Name wählen" bzw. „Angemeldet als … – Name wechseln", Tippflächen mindestens 44 px

### Edge Cases Status
- [x] Name aus der Liste entfernt → „Name wählen", Middleware lehnt ab
- [x] Browser speichert nichts → bei jedem Besuch neu wählen, App funktioniert sonst
- [x] Zwei Tabs → gemeinsamer Cookie; Absenden gilt für den aktuell gewählten Namen
- [x] Leere Liste → „Keine Namen hinterlegt", kein Hinweis, Lesen möglich
- [x] Doppelter Name in der Konfiguration → erscheint einmal
- [x] Manipulierter Cookie: unverschlüsselter Wert `staff_name=Nele` wird nicht akzeptiert („Name wählen")
- [x] Langer Seitentitel → Titel gekürzt (PROJ-1), Name bleibt

**Zusätzlich geprüft:**
- [x] Schreibweise muss exakt stimmen (`nele` abgelehnt), Liste als Array, leerer Wert und `<script>` abgelehnt (422), kein Cookie gesetzt
- [x] Nur POST; GET auf `/name` ergibt 405
- [x] Namen werden im Alpine-Ausdruck sicher als JSON eingesetzt (`Js::from`), Anzeige per `x-text`
- [x] Rückleitung nur auf Adressen der App, sonst „Ticket analysieren" (BUG-1, behoben)

### Security Audit Results
- [x] CSRF: ohne Token 419 (gegen die laufende App geprüft); beide Formulare tragen `@csrf`
- [x] Cookie verschlüsselt, `HttpOnly`, `SameSite=Lax`; unverschlüsselte oder fremde Werte werden verworfen
- [x] Server prüft jeden Namen gegen die Liste (beim Speichern und beim Lesen)
- [x] Keine sensiblen Daten in der JSON-Antwort (nur der Name), Sicherheits-Header gesetzt
- [x] Keine Anmeldung im MVP (PRD); die Nutzerauswahl ist ausdrücklich keine Sicherheitsfunktion
- [x] Keine Weiterleitung auf fremde Adressen über den Referer-Header (BUG-1, behoben)
- Hinweis: Kein Rate-Limit auf `POST /name`. Folgenlos, weil nur der eigene Cookie gesetzt wird (30 schnelle Anfragen: alle 200, keine Nebenwirkung).

### Regression
- [x] PROJ-1 (Kopfleiste, Layout, Fehlerseiten), PROJ-24 (Navigation, Knowledge-Seiten), PROJ-25 (About-Seite): Tests grün
- [x] About-Seite: Schritt „Deinen Namen wählen" ist nutzbar, Hinweis „in Arbeit" entfernt (Checklisten-Punkt aus PROJ-25), Test angepasst
- [x] Gesamte Suite: 427 Tests grün

### Hinweise
- Browser begrenzen Cookies auf höchstens 400 Tage (der Server setzt 5 Jahre). Nach gut einem Jahr ohne Wechsel wird einmal neu gefragt. Bei Bedarf: Cookie bei jedem Besuch erneuern.

### Bugs Found

#### BUG-1: Rückleitung folgt einer fremden Herkunftsadresse – BEHOBEN (2026-10-05)
- **Severity:** Low
- **Fix:** `App\Http\LocalRedirect` nutzt die Herkunftsadresse nur, wenn sie zur App gehört (gleiches Schema, Host und Port), sonst „Ticket analysieren". Verwendet in `StaffSelectionController` und `EnsureStaffSelected`; künftige Formulare sollen ebenfalls darüber zurückleiten. Tests für fremde Seite, eigenen Host als Präfix und protokollrelative Adresse; gegen die laufende App geprüft (302 auf die Startseite).
- **Steps to Reproduce:**
  1. Gültige Sitzung mit CSRF-Token
  2. `POST /name` (ohne JavaScript, kein JSON) mit `Referer: https://evil.example/phish`
  3. Expected: Rückleitung nur auf Seiten der App, sonst auf „Ticket analysieren"
  4. Actual: 302 auf `https://evil.example/phish`
- **Hinweis:** Praktisch kaum ausnutzbar, weil die Anfrage ein gültiges CSRF-Token braucht. Dasselbe Muster (`back()`) nutzt die Middleware „Name erforderlich", die ab PROJ-9 an weiteren Formularen hängt. Gleiche Klasse wie PROJ-24 BUG-1.
- **Priority:** Fix before deployment (vor PROJ-9, damit das Muster nicht übernommen wird)

### Summary
- **Acceptance Criteria:** 18/18 passed
- **Bugs Found:** 1 total (0 critical, 0 high, 0 medium, 1 low); am 2026-10-05 behoben
- **Security:** Pass
- **Production Ready:** YES
- **Recommendation:** Freigeben; BUG-1 vor PROJ-9 beheben

## Deployment
_To be added by /deploy_
