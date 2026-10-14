# PROJ-41: Statistik in der Seitenleiste und Versionsnummer

## Status: Approved
**Created:** 2026-10-13
**Last Updated:** 2026-10-14

## Dependencies
- Requires: PROJ-1 (Layout, Seitenleiste), PROJ-3 (Wissensstand, Dokumente), PROJ-11 (Analysen), PROJ-12 (Wissenslücken), PROJ-25 (Aktueller Stand auf der Über-die-App-Seite).

## Ausgangslage
Meldet ein Mitarbeiter eine Wissenslücke, sieht er danach nichts mehr davon; nur Admins haben die Liste. Die Mitarbeiter sollen sehen, dass gemeldete Lücken abgearbeitet werden. Unten in der Seitenleiste stand nur „Version: dev“, was niemand verstand.

## Entscheidungen
- **Block „Aktueller Stand“** unten in der Seitenleiste für alle (kein Admin nötig), nur Zahlen, nie Inhalte: Wissensstand, Wissen (Anzahl verwendbarer Dateien · Gesamtgröße dieser Dateien), Bearbeitete Tickets, Gemeldete Wissenslücken, davon noch offen. Bei mindestens einer Meldung der Satz „Gemeldete Lücken werden bearbeitet.“
- **Bearbeitete Tickets:** Tickets mit mindestens einer abgeschlossenen Analyse, jedes einmal, ohne fehlgeschlagene Analysen und ohne Testläufe.
- **Wissenslücken:** gemeldete Meldungen ohne Testläufe; „offen“ ist Status `open`.
- **Wissen:** verwendbare Dokumente (fehlerfrei, `draft` oder `active`), Größe = Summe der Dateigrößen. Die Wissenszahlen werden 60 Sekunden zwischengespeichert, damit nicht jede Seite alle Dateien liest; die Datenbankzahlen nicht.
- **Version:** Standard von `app.version` ist jetzt `0.1.0` (vorher „dev“). Beim Ausliefern kann `APP_VERSION` weiter gesetzt werden und überschreibt den Standard. Die Zählweise folgt Major.Minor.Patch und wird von Hand erhöht.
- Die Seite „Über die App“ behält ihren Kasten „Aktueller Stand“ unverändert.

## Implementation Notes
- `app/Analysis/AppStats.php`, Komponente `resources/views/components/sidebar-stats.blade.php`, Daten über den View-Composer in `AppServiceProvider`.
- `.env` und `.env.example` unverändert.
- Tests: `tests/Feature/PROJ-41-SidebarStatsTest.php`; mehrere ältere Tests prüften „kein Text Wissenslücken“ für Nicht-Admins und prüfen jetzt den Link zur Liste bzw. den Hinweistext im Ergebnis.
- Offen: kein mobiles Menü (PROJ-26), die Seitenleiste ist unter 768 px ausgeblendet; Prüfung im Browser durch den Nutzer.

---

## QA Test Results

**Abgenommen:** 2026-10-14 durch den Product Owner („passt so“). Tests: `PROJ-41-SidebarStatsTest.php`, komplette Suite grün (1344). Browser- und Mobilprüfung durch den Nutzer; die Seitenleiste ist unter 768 px ausgeblendet (PROJ-26).
