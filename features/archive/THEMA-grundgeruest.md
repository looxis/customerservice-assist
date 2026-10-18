# Grundgerüst, Nutzerauswahl und Seitenleiste

> Zusammenfassung abgeschlossener Features (Status „Approved“). Die vollständigen Specs mit Entscheidungen, Randfällen und QA-Ergebnissen liegen in diesem Ordner.

Die App-Hülle: Layout mit Seitenleiste im LOOXIS-Design, die Nutzerauswahl (feste Namensliste, einmal pro Browser), die Seite „Über die App“ und die Statistik unten in der Seitenleiste. Es gibt kein Login; Admins sind eine Liste in der Konfiguration (`config/staff.php`). Das mobile Menü (PROJ-26) ist noch offen.

## Features

- **PROJ-1 App-Grundgerüst mit LOOXIS-Design** (2026-10-02) – Layout-Shell, Design-Tokens und Basis-Komponenten nach `docs/design-system.md` [Spec](PROJ-1-app-grundgeruest-looxis-design.md)
- **PROJ-5 Nutzerauswahl** (2026-10-02) – Dropdown mit fester Namensliste, Auswahl einmal pro Browser, Nutzer dauerhaft sichtbar [Spec](PROJ-5-nutzerauswahl.md)
- **PROJ-25 Über die App** (2026-10-05) – Seite „Über die App“ unten in der Seitenleiste: Ziel, Ablauf mit Hinweis „in Arbeit“, Beispiel, Grenzen, Wissen ergänzen, Info-Kasten mit App- und Wissensstand, abgesetzter Entwicklerteil mit Tech Stack [Spec](PROJ-25-ueber-die-app.md)
- **PROJ-41 Statistik in der Seitenleiste** (2026-10-13) – Unten in der Seitenleiste für alle: Wissensstand, Anzahl und Größe der Wissensdateien, bearbeitete Tickets, gemeldete Wissenslücken und davon offen, mit Hinweis, dass gemeldete Lücken bearbeitet werden; Versionsnummer 0.1.0 statt „dev“ [Spec](PROJ-41-statistik-in-der-seitenleiste.md)
