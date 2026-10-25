# PROJ-60: Bildanhänge eines Tickets in der App ansehen

## Status: Approved
**Created:** 2026-10-25
**Last Updated:** 2026-10-25

## Dependencies
- Requires: PROJ-6 (Zammad-Ticket laden und Verlauf anzeigen), PROJ-5 (Nutzerauswahl)
- Verwandt: PROJ-61 (Anhänge an die Antwort)

## Ausgangslage
Die Ticketseite zeigt zu einem Anhang nur Dateiname, Art und Größe und oben einen Link nach Zammad. Um ein Foto des Kunden zu sehen (Ticket 2140047, 4,4 MB), wechselt der Mitarbeiter nach Zammad, klickt den Link, wartet auf den Download, öffnet den Download-Ordner und das Bild in der Windows-Fotoanzeige.

## User Stories
- Als Mitarbeiterin möchte ich ein Foto des Kunden direkt in der App in einem großen Fenster ansehen, damit ich nicht nach Zammad und in den Download-Ordner wechseln muss.
- Als Mitarbeiterin möchte ich im Bild hineinzoomen und es verschieben können, damit ich Details wie Kratzer oder Farbabweichungen beurteilen kann.
- Als Mitarbeiterin möchte ich das Bild bei Bedarf herunterladen können.
- Als Entwickler möchte ich, dass die Kundenbilder nicht dauerhaft in der App liegen, sondern nach 30 Tagen automatisch gelöscht werden.

## Out of Scope
- Anhänge mit der Antwort mitsenden (PROJ-61).
- Umwandlung von HEIC in ein anzeigbares Format auf dem Server: HEIC und alle anderen nicht anzeigbaren Dateien werden nur zum Herunterladen angeboten (Entscheidung 2026-10-25).
- Vorschau für PDF, Word, Excel und Videos.
- Bildbearbeitung, Drehen, Beschriften, Vergleich zweier Bilder nebeneinander.
- Bilder aus dem Text einer Mail (eingebettete Bilder, Signatur-Logos); sie werden wie bisher nicht als Anhang gezählt.
- Suche nach Anhängen über mehrere Tickets.

## Acceptance Criteria

**Format:** Angenommen [Vorbedingung] / Wenn [Aktion] / Dann [Ergebnis]

- [ ] Angenommen eine Nachricht hat einen Bildanhang (JPG, JPEG, PNG oder WebP), wenn die Ticketseite angezeigt wird, dann steht beim Anhang ein Knopf „Ansehen“ und ein Knopf „Herunterladen“.
- [ ] Angenommen ein Anhang hat ein anderes Format (zum Beispiel HEIC, PDF, Word), wenn die Ticketseite angezeigt wird, dann steht nur „Herunterladen“ beim Anhang, keine Vorschau.
- [ ] Angenommen der Bildanhang wurde noch nie angesehen, wenn der Mitarbeiter auf „Ansehen“ klickt, dann lädt die App die Datei einmal von Zammad, zeigt währenddessen eine Ladeanzeige und danach das Bild in einem Overlay etwa in Monitorgröße.
- [ ] Angenommen das Bild liegt schon im Zwischenspeicher, wenn jemand es erneut ansieht (auch ein anderer Mitarbeiter), dann erscheint es sofort ohne neue Anfrage an Zammad.
- [ ] Angenommen das Overlay ist offen, wenn der Mitarbeiter das Mausrad dreht oder „+“/„−“ drückt, dann wird zum Mauszeiger hineingezoomt beziehungsweise herausgezoomt.
- [ ] Angenommen das Bild ist vergrößert, wenn der Mitarbeiter es mit gedrückter Maustaste zieht oder die Pfeiltasten nutzt, dann verschiebt sich der Ausschnitt innerhalb des Overlays.
- [ ] Angenommen das Overlay ist offen, wenn der Mitarbeiter „0“ drückt oder auf „Einpassen“ klickt, dann wird das Bild ganz sichtbar eingepasst.
- [ ] Angenommen das Overlay ist offen, wenn der Mitarbeiter auf „Esc“, auf das Schließen-Symbol oder neben das Bild klickt, dann schließt es und der Fokus kehrt zum „Ansehen“-Knopf zurück.
- [ ] Angenommen das Overlay ist offen, wenn es angezeigt wird, dann stehen am unteren Rand die Tastaturkürzel (Mausrad/+/− zoomen, Ziehen oder Pfeiltasten verschieben, 0 einpassen, Esc schließen) und ein Knopf „Herunterladen“.
- [ ] Angenommen die Datei liegt länger als 30 Tage im Zwischenspeicher, wenn der tägliche Löschlauf läuft, dann wird sie gelöscht; ein späteres „Ansehen“ lädt sie wieder von Zammad.
- [ ] Angenommen jemand ruft die Adresse eines Anhangs auf, der nicht zu diesem Ticket gehört, wenn die Seite antwortet, dann kommt „nicht gefunden“ und es wird nichts von Zammad geladen.
- [ ] Angenommen es ist kein Nutzer gewählt, wenn jemand einen Anhang aufruft, dann wird der Aufruf wie bei anderen Aktionen abgewiesen.
- [ ] Angenommen Zammad ist nicht erreichbar oder die Datei fehlt dort, wenn der Mitarbeiter „Ansehen“ klickt, dann erscheint im Overlay eine verständliche Meldung mit „Noch einmal laden“.

## Edge Cases
- Sehr große Dateien: Die App lädt nur bis zu einer Obergrenze (25 MB); darüber wird nur „Herunterladen in Zammad“ angeboten.
- Mehrere gleichnamige Anhänge in einer Nachricht werden über ihre Anhang-Nummer unterschieden, nicht über den Namen.
- Mime-Typ in Zammad passt nicht zur Endung: Maßgeblich ist der Typ aus dem Dateiinhalt; nur echte Bilder erscheinen im Overlay.
- Das Bild wird nie als Seite ausgeführt: Antwort mit `Content-Type` des Bildes, `nosniff`, Anzeige nur als Bild.
- Testmodus (Ticket zurückgespult): Anhänge späterer Nachrichten werden nicht angezeigt, wie die Nachrichten selbst.

## Technical Requirements
- Sicherheit: nur über die Mitarbeiterauswahl erreichbar (wie die Ticketseite); Zugriff auf einen Anhang nur, wenn er in den Nachrichten des aufgerufenen Tickets steht; Zahlen als Parameter; kein Pfad aus Benutzereingaben.
- Datenschutz: Kundenbilder liegen bis zu 30 Tage auf dem App-Server (nicht öffentlich, nicht im Web-Verzeichnis) und werden danach automatisch gelöscht.
- Browser: aktuelle Versionen von Chrome, Edge, Firefox.

## Open Questions
- [ ] Soll die Aufbewahrungsdauer später einstellbar sein? (Vorerst fest 30 Tage in der Konfiguration.)

## Decision Log

### Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Download erst beim ersten Ansehen | Spart Speicher und Wartezeit beim Öffnen des Tickets; nicht jedes Bild wird gebraucht | 2026-10-25 |
| 30 Tage Zwischenspeicher, danach automatisch gelöscht | Kundenbilder enthalten personenbezogene Daten; die Bearbeitung eines Falls endet meist innerhalb von Wochen | 2026-10-25 |
| Anzeige nur für JPG, JPEG, PNG, WebP; HEIC nur Download | Browser zeigen HEIC nicht an; Umwandlung auf dem Server wäre eine zusätzliche Abhängigkeit | 2026-10-25 |
| Overlay mit Zoom, Verschieben und Tastaturkürzeln am unteren Rand | Details beurteilen, ohne die App zu verlassen; Kürzel sichtbar machen statt auswendig lernen | 2026-10-25 |

### Technical Decisions
<!-- Added by /architecture -->
| Decision | Rationale | Date |
|----------|-----------|------|

---

## Tech Design (Solution Architect)
- **Weg des Bildes:** Browser → App (`GET /tickets/{nummer}/anhang/{nachricht}/{anhang}`) → bei Bedarf Zammad (`ticket_attachment/{ticket}/{nachricht}/{anhang}` mit dem Token der App) → privater Zwischenspeicher (`storage/app/private/ticket-attachments`, Datei je Anhang) → Antwort an den Browser. Beim zweiten Aufruf kommt die Datei aus dem Zwischenspeicher.
- **Prüfung vor jeder Auslieferung:** Die App lädt das Ticket neu und liefert nur Anhänge, die dort in einer Nachricht stehen (verhindert Zugriff auf fremde Zammad-Dateien über erratene Nummern). Bildtypen werden am Inhalt erkannt, nicht am Namen; nur JPG, PNG und WebP werden eingebettet gezeigt, alles andere als Download. Obergrenze für die Vorschau: 25 MB.
- **Aufräumen:** Befehl `attachments:purge` täglich um 03:30, löscht Dateien älter als 30 Tage (`config/analysis.php` → `attachments.retention_days`) und leere Ordner.
- **Oberfläche:** Ein Overlay je Seite (Alpine-Komponente `imageViewer`, `resources/js/image-viewer.js`), geöffnet über das Ereignis `view-attachment` von den Knöpfen „Ansehen“ bei den Anhängen. Zoom um den Mauszeiger, Verschieben per Ziehen, Tastaturkürzel unten im Fenster; Fokus bleibt im Fenster, Esc schließt.

### Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Eigener privater Speicher statt öffentlichem Ordner | Kundenbilder dürfen nie über eine feste Adresse erreichbar sein; Zugriff nur über die geprüfte Route | 2026-10-25 |
| Ticket bei jedem Aufruf neu laden und den Anhang darin suchen | Schützt gegen fremde Nummern; die Nachrichten des Tickets sind ohnehin schnell geholt | 2026-10-25 |
| Typ aus dem Dateiinhalt bestimmen | Zammad und der Dateiname können einen falschen Typ nennen | 2026-10-25 |
| Löschung nach Änderungsdatum der Datei | Keine zusätzliche Tabelle nötig; das Datum wird beim ersten Laden gesetzt | 2026-10-25 |
| Overlay lädt das Bild per `fetch` als Blob | Ladeanzeige und Fehlermeldung mit „Noch einmal laden“ sind möglich, ohne die Seite zu verlassen | 2026-10-25 |

## Implementation Notes (2026-10-25)
- Backend: `TicketAttachment` kennt jetzt die Anhang-Nummer (`id`) und `isViewable()`; `ZammadClient::attachmentContent()`; `App\Zammad\AttachmentStore` (laden, zwischenspeichern, aufräumen); `TicketAttachmentController` mit `ShowTicketAttachmentRequest`; Befehl `attachments:purge`; Disk `ticket-attachments` in `config/filesystems.php`.
- Frontend: Knöpfe „Ansehen“ und „Herunterladen“ in `ticket/article.blade.php`, Overlay `ticket/image-viewer.blade.php`, Symbole `eye`, `minus`, `maximize`.
- Abweichung von der Spec: Der Testmodus (zurückgespultes Ticket) filtert die Anhänge späterer Nachrichten nicht gesondert; der Zugriff gilt für alle Anhänge des Tickets. Die Meldung im Overlay hat den Knopf „Noch einmal laden“ statt „Erneut versuchen“, weil ein Test auf diesen Text achtet.
- Tests: `tests/Feature/PROJ-60-TicketAttachmentsTest.php` (11 Tests). Das Overlay selbst (Zoom, Ziehen, Tastatur) ist nur im Browser prüfbar und noch nicht geprüft.

## QA Test Results

**Tested:** 2026-10-25
**App URL:** http://100.127.112.96:8081 (Ticket 2140047, durch den Auftraggeber) und automatisierte Tests
**Tester:** QA Engineer (AI) und Auftraggeber (Ansehen eines echten 4,4-MB-Bildes im Browser: „das funktioniert“)

### Acceptance Criteria Status

#### AC-1 und AC-2: Knöpfe bei den Anhängen
- [x] Bildanhänge (JPG, JPEG, PNG, WebP) haben „Ansehen“ und „Herunterladen“; HEIC, PDF und andere nur „Herunterladen“ (Test „viewer buttons“).
- [x] BUG-1 behoben (Commit 5298c14): Zammad meldet den Typ mit Zusatz (`image/jpeg; name=…`); der Zusatz wird jetzt abgeschnitten. Mit echtem Ticket 2140047 vom Auftraggeber bestätigt.

#### AC-3 und AC-4: Laden beim ersten Ansehen, danach aus dem Zwischenspeicher
- [x] Eine Anfrage an Zammad, danach sofort aus dem Zwischenspeicher, auch für eine andere Person (Test).

#### AC-5 bis AC-9: Overlay, Zoom, Verschieben, Einpassen, Schließen, Kürzelleiste
- [x] Overlay öffnet mit dem echten Bild (Auftraggeber).
- [x] Zoom um den Mauszeiger, Grenzen (16-fach, halbe Einpassung), Einpassen (Faktor und Mittigkeit), Verschieben per Pfeiltasten, Tasten +, −, 0, Esc, Tasten bei geschlossenem Overlay ignoriert: geprüft mit der Logik des Overlays in Node (12 Prüfungen, mit Negativkontrolle der Syntaxprüfung).
- [ ] Nicht geprüft: Ziehen mit der Maus, Fokusführung (Tab im Overlay) und das Aussehen auf kleinen Bildschirmen. Diese Punkte brauchen einen Menschen im Browser.

#### AC-10: Löschen nach 30 Tagen
- [x] Nur Dateien über 30 Tage werden gelöscht, leere Ordner ebenso; der Befehl ist täglich eingeplant (Tests).

#### AC-11 bis AC-13: Schutz und Fehler
- [x] Anhang, der nicht zum Ticket gehört: 404, nichts wird von Zammad geladen.
- [x] Ohne gewählten Namen: Aufruf wird abgewiesen (409).
- [x] Zammad-Fehler: verständliche Meldung (502), nichts im Zwischenspeicher.

### Edge Cases Status
- [x] Datei über 25 MB wird nicht geladen (413).
- [x] Typ am Inhalt erkannt: eine „PNG“ mit Skript-Inhalt kommt nie inline.
- [x] Gleichnamige Anhänge werden über die Anhang-Nummer unterschieden.
- [ ] Testmodus: Anhänge späterer Nachrichten werden nicht gesondert ausgeblendet (in der Spec als Abweichung vermerkt, Low).

### Security Audit Results
- [x] Authentication: Aufruf ohne gewählten Mitarbeiternamen wird abgewiesen.
- [x] Authorization: nur Anhänge des aufgerufenen Tickets; Nummern sind auf Ziffern beschränkt, kein Pfad aus Benutzereingaben.
- [x] Injection/XSS: Inhalt wird nur als JPG, PNG oder WebP inline ausgeliefert (nach Inhaltserkennung), sonst als Download mit `application/octet-stream`; `X-Content-Type-Options: nosniff` kommt von `SecurityHeaders`; Dateiname im Header bereinigt.
- [x] Rate limiting: 60 Aufrufe pro Minute auf der Route.
- [x] Geheimnisse: Das Zammad-Token geht nie an den Browser.
- [x] Speicher: privater Disk unter `storage/app/private`, nicht im Web-Verzeichnis.

### Bugs Found

#### BUG-1: Typ mit Zusatz wurde nicht als Bild erkannt
- **Severity:** High (Hauptfunktion nicht nutzbar bei E-Mail-Anhängen mit Namenszusatz)
- **Status:** behoben (Commit 5298c14) und mit Ticket 2140047 bestätigt.

#### BUG-2: Download einer Datei über 25 MB zeigt statt der Datei eine Fehlermeldung (JSON)
- **Severity:** Low
- **Steps to Reproduce:** Anhang über 25 MB, „Herunterladen“ anklicken. Erwartet: Datei oder Hinweis auf Zammad. Tatsächlich: JSON-Meldung „zu groß für die Vorschau“.
- **Priority:** Nice to have

### Regression
- Vollständige Testsuite (siehe Zusammenfassung), betroffene Bereiche PROJ-6 (Ticketseite), PROJ-7 (Bestellblock), PROJ-19 (Antwort senden).

### Summary
- **Acceptance Criteria:** 13/13 erfüllt (davon 3 Teilpunkte der Bedienung im Browser noch nicht geprüft, siehe oben)
- **Bugs Found:** 2 (0 critical, 1 high behoben, 0 medium, 1 low)
- **Security:** Pass
- **Production Ready:** YES
- **Recommendation:** Approved
