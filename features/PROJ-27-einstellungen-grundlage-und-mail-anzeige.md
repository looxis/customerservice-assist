# PROJ-27: Einstellungen – Grundlage und Mail-Anzeige

## Status: Approved
**Created:** 2026-10-05
**Last Updated:** 2026-10-17

## Dependencies
- Requires: PROJ-1 (Layout, Seitenleiste), PROJ-5 (Nutzerauswahl), PROJ-6 (Ticketansicht und Mail-Bereinigung), PROJ-32 (Admin-Liste).
- Verwandt (eigene Unterseiten auf derselben Einstellungsseite): PROJ-31 (Prompts und KI-Modell), PROJ-49 (Erkennungsregeln), PROJ-50 (Kundengruppen, Kategorien, Vorgänge). Herausgenommen: PROJ-51 (Kundensignaturen je Absender).

## Ausgangslage
Wie die App Mails anzeigt, steckt fest im Code oder in der Konfiguration: welche Links als Text erscheinen, welche klickbar bleiben, welche Signaturen und Fußtexte ausgeblendet werden, woran ein Zitat oder eine Signatur erkannt wird und woran die Amazon-Nachricht samt Bestellnummer. Ändert Amazon sein Format oder kommt ein neuer Mail-Client dazu, braucht es einen Entwickler. Ob eine Regel stimmt, kann niemand außer im Code nachsehen.

Diese Spec legt die **Grundlage der Einstellungsseite** (Navigation, Speicherung, Verlauf, Zurücksetzen, Ausprobieren) und füllt sie mit der ersten Unterseite **„Mail-Anzeige“**. Weitere Themen kommen als eigene Unterseiten späterer Specs.

## User Stories
- Als Admin möchte ich festlegen, welche Links sichtbar bleiben, welche klickbar sind und welche Texte ausgeblendet werden, damit die Ticketansicht lesbar bleibt, ohne einen Entwickler zu brauchen.
- Als Admin möchte ich bei einer Formatänderung von Amazon die Erkennungstexte selbst anpassen und vor dem Speichern an einem echten Ticket ausprobieren, damit die Amazon-Mails weiterhin Bestellnummer und Nachricht zeigen.
- Als Admin möchte ich jede Änderung nachvollziehen (wer, wann, was) und zurücknehmen können, damit ein Fehler nicht lange wirkt.
- Als Stammkraft oder Aushilfe möchte ich sehen, nach welchen Regeln die App Mails bereinigt, damit ich verstehe, warum etwas fehlt oder ausgeblendet ist.

## Out of Scope
- Prompts und KI-Modell: PROJ-31. Erkennungsregeln für Bestellnummern und Artikelnummern: PROJ-49. Kundengruppen, Kategorien und Vorgänge: PROJ-50.
- Kundensignaturen je Absender (Mitarbeiter markiert in der Ticketansicht „das ist die Signatur dieses Absenders“): PROJ-51.
- Freie reguläre Ausdrücke. Die Eingabe erfolgt über Listen von Texten; die App baut daraus die Muster.
- Anmeldung und Benutzerverwaltung (PROJ-15): Wer Admin ist, bleibt die Liste in der Konfiguration.
- Export und Import der Einstellungen, Einstellungen je Nutzer, mehrere Umgebungen.

## Navigation und Zugriff
- „Einstellungen“ erscheint in der Seitenleiste. Die Seite zeigt eine Übersicht der Unterseiten (zunächst „Mail-Anzeige“); jede spätere Spec ergänzt eine Unterseite.
- **Ansehen** darf jeder mit gewähltem Namen, **ändern** nur Admins. Für andere sind die Felder schreibgeschützt, Speichern und Wiederherstellen fehlen.

## Die Unterseite „Mail-Anzeige“
Jede Gruppe ist eine Liste von Texten (ein Eintrag je Zeile). Umlaut-Varianten („ü“/„ue“, „ß“/„ss“), Groß-/Kleinschreibung und Zeilenumbrüche beachtet die App selbst; `…` in einem Eintrag steht für beliebigen Text.

| Gruppe | Eingabe | Standard (heutiger Stand) |
|---|---|---|
| Zugelassene Adressen | Host je Eintrag mit Wahl „als Text anzeigen“ oder „klickbar lassen“; `*` nur als Länderendung am Ende | looxis.de, looxis.com, dhl.de (Text); sellercentral.amazon.* (klickbar, nur https) |
| Unsere Signaturen | mehrzeiliger Text je Variante | „Freundliche Grüße / LOOXIS Kundenservice“ |
| Fußtexte (ab hier wird der Rest ausgeblendet) | Liste | „Dieser Service wird ausschließlich für die Kommunikation mit Käufern angeboten“ |
| Grußformeln (danach folgt die Signatur des Kunden) | Liste | die heutigen deutschen, englischen, niederländischen, französischen, italienischen und spanischen Formeln |
| Sichtbare Zeilen nach der Grußformel | Zahl 0 bis 5 | 2 |
| Zitat-Köpfe (ab hier beginnt ein zitierter Verlauf) | Liste mit `…` | „Am … schrieb …:“, „On … wrote:“, „Von: … Gesendet:“ und die heutigen weiteren |
| Technische Zitat-Markierungen (eingeklappt unter „Erweitert“) | Liste | gmail_quote, moz-cite-prefix, … |
| Amazon-Nachricht: Einleitungssätze | Liste | „Du hast eine Nachricht erhalten“, „You have received a message“ |
| Amazon-Nachricht: Bezeichnung der Bestellnummer | Liste | „Bestellnummer“, „Order ID“, „Order number“ |
| Amazon-Nachricht: Bezeichnung der Nachricht | Liste | „Nachricht“, „Message“ |

**Prüfung vor dem Speichern (Eingabe wird abgelehnt, mit Hinweis an der Stelle):**
- Je Eintrag höchstens 300 Zeichen, je Liste höchstens 100 Einträge; Einträge werden getrimmt, Leerzeilen und Doppelte entfallen (mit Hinweis).
- Fußtexte mindestens 15 Zeichen, Grußformeln mindestens 4 Zeichen, Zitat-Köpfe mindestens 4 feste Zeichen außerhalb von `…` (sonst würden harmlose Mails ausgeblendet).
- Adressen: gültiger Hostname; `*` nur am Ende als Länderendung; „klickbar“ nur mit https.
- Die drei Amazon-Listen dürfen nicht leer sein (sonst erkennt die App keine Amazon-Nachricht mehr); die Signaturvarianten dürfen leer sein.

## Mit Ticket ausprobieren
- Über den Gruppen gibt es ein Feld für eine Ticketnummer. Die App ruft das Ticket lesend aus Zammad ab und zeigt den bereinigten Verlauf **mit den noch nicht gespeicherten Werten** neben dem Verlauf mit den gespeicherten Werten, je Nachricht. Gibt es keinen Unterschied, steht das dort.
- Das Ausprobieren speichert nichts und ändert nichts für andere. Erst „Speichern“ macht die Änderung für alle wirksam.

## Speichern, Verlauf, Zurücksetzen
- Ein Formular für die ganze Seite. Gespeichert wird je geänderter Gruppe eine neue **Version** (Zeitpunkt, Name, alter und neuer Wert).
- Verlauf je Gruppe: Liste der Versionen mit Vergleich zur vorherigen; „Diese Version wiederherstellen“ legt eine neue Version an. „Auf Standard der App zurücksetzen“ gilt je Gruppe.
- Die Standardwerte bleiben in der App; die Seite speichert nur Abweichungen. Neue Standardwerte aus einer späteren Version der App erreichen eine Gruppe nur, solange sie nicht geändert wurde.
- Zwei Admins gleichzeitig: Wurde eine Gruppe seit dem Öffnen der Seite von jemand anderem gespeichert, wird nichts überschrieben; es erscheint „<Name> hat diese Gruppe um <Zeit> geändert“, die Eingabe bleibt erhalten.
- Wirkung: ab dem Speichern für alle neuen Seitenaufrufe, ohne Neustart. Gespeicherte Analysen und Übersetzungen ändern sich nicht.

## Acceptance Criteria
- [ ] Angenommen ich habe meinen Namen gewählt, wenn ich in der Seitenleiste auf „Einstellungen“ klicke, dann sehe ich eine Übersicht der Unterseiten und öffne „Mail-Anzeige“ mit allen Gruppen und ihren aktuellen Werten.
- [ ] Angenommen ich bin kein Admin, wenn ich die Seite öffne, dann sind alle Felder schreibgeschützt, es gibt kein Speichern, und ein direkter Speichern-Aufruf wird mit 403 abgelehnt.
- [ ] Angenommen ich bin Admin, wenn ich einen Eintrag ändere und speichere, dann gilt er sofort in der Ticketansicht für alle, und im Verlauf der Gruppe steht eine Version mit meinem Namen, dem Zeitpunkt, altem und neuem Wert.
- [ ] Angenommen es gibt keine Änderung, wenn die App startet, dann entspricht die Anzeige genau dem bisherigen Verhalten (die Standardwerte sind die heutigen Werte aus Konfiguration und Code).
- [ ] Angenommen ein Link zeigt auf einen Host der Liste mit „als Text anzeigen“, dann erscheint er als Text; bei „klickbar lassen“ bleibt er klickbar (nur https, neuer Tab); jeder andere Link wird durch „[Link entfernt]“ ersetzt wie bisher.
- [ ] Angenommen ich trage eine unserer Signaturen oder einen Fußtext ein, dann wird er in unseren Nachrichten bzw. ab dem Fußtext in allen Nachrichten ausgeblendet (Groß-/Kleinschreibung, Umlaute und Zeilenumbrüche egal).
- [ ] Angenommen ich ergänze eine Grußformel oder einen Zitat-Kopf mit `…`, dann erkennt die Ticketansicht die Signatur bzw. das Zitat danach entsprechend.
- [ ] Angenommen Amazon ändert seinen Einleitungssatz, wenn ich den neuen Satz in „Amazon-Nachricht: Einleitungssätze“ eintrage und speichere, dann werden Nachricht und Bestellnummer in Amazon-Mails wieder erkannt.
- [ ] Angenommen ich habe Werte geändert und nicht gespeichert, wenn ich eine Ticketnummer eingebe und „Ausprobieren“ wähle, dann sehe ich den bereinigten Verlauf mit meinen ungespeicherten Werten neben dem mit den gespeicherten, und nichts wurde gespeichert.
- [ ] Angenommen ich gebe einen ungültigen Wert ein (z. B. Fußtext mit 5 Zeichen, `*` mitten im Host, leere Amazon-Liste), wenn ich speichere, dann wird nichts gespeichert, die Stelle ist markiert und meine Eingabe bleibt erhalten.
- [ ] Angenommen es gibt frühere Versionen, wenn ich eine wiederherstelle, dann gilt ihr Wert wieder und es entsteht eine neue Version (die alte bleibt im Verlauf).
- [ ] Angenommen ich wähle „Auf Standard der App zurücksetzen“, dann gilt der Standard der Gruppe wieder und der Vorgang steht im Verlauf.
- [ ] Angenommen ein anderer Admin hat die Gruppe seit dem Öffnen der Seite geändert, wenn ich speichere, dann wird nichts überschrieben und ich sehe, wer wann geändert hat; meine Eingabe bleibt.
- [ ] Angenommen eine Einstellung wurde geändert, dann ändern sich bereits gespeicherte Analysen und Übersetzungen nicht.

## Edge Cases
- Ein Eintrag mit nur Leerzeichen, ein sehr langer Eintrag, Sonderzeichen und Markup (wird nur als Text behandelt, nie als Muster oder HTML).
- Doppelte Einträge in einer Liste oder in zwei Gruppen (z. B. derselbe Host als „Text“ und „klickbar“): die strengere Wahl „als Text anzeigen“ gilt, mit Hinweis.
- Ein Host mit Großbuchstaben, Umlauten (Punycode) oder Port.
- Eine Liste ohne Einträge, wo leer erlaubt ist (Signaturen): es werden dann keine Signaturen ausgeblendet.
- Das Ticket beim Ausprobieren existiert nicht oder Zammad ist nicht erreichbar: Hinweis, die Eingaben bleiben.
- Ein Ticket mit sehr vielen Nachrichten beim Ausprobieren: die Vorschau zeigt höchstens die letzten 10 Nachrichten.
- Die Datenbank ist beim Seitenaufruf nicht erreichbar: die Ticketansicht fällt auf die Standardwerte zurück (nie auf leere Regeln).

## Product Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Eigene Spec je Thema, Unterseiten unter „Einstellungen“ | Jede Gruppe ist einzeln testbar und abnehmbar; die Seite wächst mit | 2026-10-17 |
| Ansehen für alle, ändern nur Admins | Regeln sollen überprüfbar sein; Änderungen betreffen sofort alle | 2026-10-17 |
| Versionen mit Namen, Zeitpunkt, altem und neuem Wert; Wiederherstellen und Standard | Fehler lassen sich schnell zurücknehmen; Nachvollziehbarkeit wie bei Analysen | 2026-10-17 |
| Mit Ticket ausprobieren vor dem Speichern | Eine Formatänderung (Amazon) lässt sich ohne Risiko prüfen | 2026-10-17 |
| Listen von Texten statt regulärer Ausdrücke | Ein Tippfehler im Muster könnte die Anzeige aller Tickets stören; Fachanwender können Listen sicher pflegen | 2026-10-17 |
| Kundensignaturen je Absender nicht Teil dieser Spec (PROJ-51) | Aktion in der Ticketansicht mit eigener Datenablage, kein Einstellungswert | 2026-10-17 |
| Standardwerte bleiben in der App, gespeichert wird die Abweichung | Neue Standards der App erreichen unveränderte Gruppen | 2026-10-17 |
| Schutzgrenzen (Mindestlängen, keine leeren Amazon-Listen) | Verhindern, dass harmlose Mails ausgeblendet oder Amazon nicht mehr erkannt wird | 2026-10-17 |

## Tech Design (Solution Architect)

### Backend nötig?
Ja, mit Datenbank. Die Werte müssen für alle gelten, Änderungen müssen sofort wirken und ihren Verlauf behalten. Browser-Speicher oder Konfigurationsdateien reichen dafür nicht. Neue Pakete sind nicht nötig.

### A) Bausteine der Oberfläche
```
Seitenleiste
+-- Eintrag „Einstellungen“ (neu, über „Über die App“)

Seite „Einstellungen“ (Übersicht)
+-- Karte „Mail-Anzeige“ (heute einzige Unterseite, spätere Specs ergänzen Karten)

Seite „Einstellungen > Mail-Anzeige“
+-- Hinweis „Du kannst ansehen“ / „Du kannst ändern“ (je nach Admin)
+-- Ausprobieren: Feld Ticketnummer + Knopf, Ergebnis zwei Spalten (gespeichert | mit Änderung)
+-- Gruppe „Zugelassene Adressen“ (Liste, je Eintrag Wahl Text/klickbar)
+-- Gruppe „Unsere Signaturen“ (mehrzeilige Felder)
+-- Gruppe „Fußtexte“ (Liste)
+-- Gruppe „Grußformeln“ (Liste) + Zahl „Sichtbare Zeilen danach“
+-- Gruppe „Zitat-Köpfe“ (Liste mit …)
+-- Gruppe „Amazon-Nachricht“ (drei Listen)
+-- „Erweitert“ (eingeklappt): Technische Zitat-Markierungen
+-- je Gruppe: Verlauf (eingeklappt), „Auf Standard zurücksetzen“
+-- Speichern (nur Admins)
```
Wiederverwendet werden die vorhandenen Bausteine Karte, Hinweis, Knopf, Eingabefeld, Textbereich, Kennzeichen und Navigationseintrag. Neu ist ein Baustein „Liste von Texten“ (Zeilen hinzufügen, entfernen, Fehler an der Zeile), den die späteren Einstellungs-Specs ebenfalls nutzen.

### B) Datenmodell (in Alltagssprache)
```
Einstellungsgruppe (eine Zeile je Gruppe, die von der Vorgabe abweicht)
- Schlüssel der Gruppe (z. B. „Zugelassene Adressen“)
- aktueller Wert (die Liste)
- Versionsnummer
- geändert von, geändert am

Einstellungsversion (Verlauf, wird nur ergänzt, nie geändert)
- Schlüssel der Gruppe, Versionsnummer
- Wert dieser Version
- Art: gespeichert / wiederhergestellt / auf Standard zurückgesetzt
- geändert von, geändert am

Die Vorgaben (Standardwerte) liegen nicht in der Datenbank, sondern in der App.
Gespeichert in: MySQL (wie Analysen und Wissenslücken).
```
Es gibt keine personenbezogenen Kundendaten; gespeichert werden nur Regeltexte und der Name des Admins.

### C) Technische Entscheidungen (begründet)
- **Normale Webseiten mit Formular, keine eigene Schnittstelle:** Die Seite ist reine Pflege, kein Fremdsystem braucht die Werte. Ein Formular, ein Speichern-Knopf; Alpine nur für „Zeile hinzufügen/entfernen“ und das Aufklappen.
- **Ein Verzeichnis aller Einstellungsgruppen in der App:** Jede Gruppe beschreibt sich selbst (Name, Vorgabe, Prüfregeln, wie aus der Liste ein Muster wird). Die Seite, die Speicherung, der Verlauf und das Zurücksetzen arbeiten für jede Gruppe gleich. Spätere Specs (Prompts, Erkennungsregeln, Kundengruppen) tragen nur neue Gruppen ein und bekommen die Funktionen geschenkt.
- **Vorgaben bleiben im Code, Abweichungen in der Datenbank:** So erreichen neue App-Standards unveränderte Gruppen, und „Auf Standard“ ist immer möglich. Die heutigen Werte aus `config/services.php` und `MessageBody`/`AmazonNotice` werden zu den Vorgaben; das Verhalten ohne Änderung bleibt identisch.
- **Die Mail-Bereinigung bekommt ihre Regeln als ein Regelsatz:** Statt fester Muster im Code liest `MessageBody` und die Amazon-Erkennung einen Regelsatz, den ein eigener Baustein aus den Listen baut (Umlaut-Varianten, Groß-/Kleinschreibung, `…` als Platzhalter, Texte werden immer als Text behandelt und nie als Muster eingeschleust). Der Baustein ist einzeln testbar; ein ungültiger Eintrag kann dadurch nicht die ganze Ansicht zerstören.
- **Wert lesen:** Pro Seitenaufruf einmal aus der Datenbank. Ist die Datenbank nicht erreichbar oder ein Wert unbrauchbar, gelten die Vorgaben, nie leere Regeln.
- **Berechtigung über die vorhandene Admin-Prüfung** (wie beim Testmodus und bei den Admin-Seiten), in der Prüfung des Formulars und serverseitig; Ansehen für jeden mit gewähltem Namen. Keine neue Policy.
- **Verlauf nur anhängend, Gleichzeitigkeit über die Versionsnummer:** Das Formular merkt sich die geöffnete Version je Gruppe; stimmt sie beim Speichern nicht mehr, wird abgelehnt statt überschrieben.
- **Ausprobieren:** Ein Aufruf holt das Ticket einmal lesend aus Zammad und bereinigt den Verlauf zweimal (mit gespeicherten und mit eingegebenen Werten), höchstens 10 Nachrichten, ohne etwas zu speichern. Dafür muss die Ticketabfrage die rohen Nachrichten getrennt von der Bereinigung liefern können.
- **Wirkung ohne Neustart:** Kein Zwischenspeicher über Seitenaufrufe hinaus; die Werte sind klein.
- **Navigation:** „Einstellungen“ steht über „Über die App“; die Übersicht listet nur vorhandene Unterseiten.

### D) Abhängigkeiten
Keine neuen Pakete.

## Technical Decisions
| Decision | Rationale | Date |
|----------|-----------|------|
| Datenbank statt Konfiguration oder Browser-Speicher | Werte gelten für alle, wirken sofort, brauchen Verlauf | 2026-10-17 |
| Verzeichnis der Einstellungsgruppen in der App mit Vorgabe, Prüfregeln und Mustererzeugung je Gruppe | Seite, Speicherung, Verlauf und Zurücksetzen sind für alle späteren Gruppen gleich (PROJ-31, 49, 50) | 2026-10-17 |
| Vorgaben im Code, Abweichungen in der Datenbank | Neue App-Standards erreichen unveränderte Gruppen; Zurücksetzen jederzeit möglich | 2026-10-17 |
| Regelsatz aus Textlisten statt fester Muster in `MessageBody` und der Amazon-Erkennung | Kein freies Muster von Anwendern, einzeln testbar, Texte können nie als Muster wirken | 2026-10-17 |
| Verlauf nur anhängend; Versionsnummer gegen gleichzeitiges Überschreiben | Nachvollziehbar und sicher bei zwei Admins | 2026-10-17 |
| Rückfall auf Vorgaben bei Datenbank- oder Wertfehler | Die Ticketansicht darf nie mit leeren Regeln arbeiten | 2026-10-17 |
| Admin-Prüfung der bestehenden Admin-Liste, keine neue Policy | Gleiche Mechanik wie Testmodus und Admin-Seiten; Policy-Änderungen brauchen Freigabe | 2026-10-17 |
| Ausprobieren holt das Ticket einmal und bereinigt zweimal; höchstens 10 Nachrichten | Ein Zammad-Abruf, kein Speichern, begrenzte Dauer | 2026-10-17 |

## Open Questions
- [x] Die Standardwerte der Grußformeln werden beim Umstellen in einzelne lesbare Einträge aufgelöst und vollständig angezeigt (Entscheidung 2026-10-17).
- [x] „Mit Ticket ausprobieren“ zeigt nur den ganzen Verlauf, keine zurückgespulten Testmodus-Stände (Entscheidung 2026-10-17).
- [x] Keine Benachrichtigung bei Änderungen; der Verlauf genügt (Entscheidung 2026-10-17).

## Implementation Notes
- **Neu:** `app/Settings/` (`SettingGroup`, `SettingsRegistry`, `SettingsStore`, `MailRules`, `MailDefaults`, `PatternBuilder`), Modelle `Setting` und `SettingVersion` mit den Tabellen `settings` und `setting_versions`, `SettingsController`, Anfragen `MailSettingsRequest`/`TryMailSettingsRequest`/`ChangeSettingVersionRequest`, Seiten `settings/index` und `settings/mail`, Baustein `components/settings/group`, Alpine-Komponente `settingsList`.
- **Geändert:** `MessageBody` und `AmazonNotice` nehmen ihre Regeln als Parameter (ohne Angabe gelten die bisherigen festen Muster, bestehende Tests blieben unverändert); `AppServiceProvider` baut `MessageBody` aus den Einstellungen; Seitenleiste (Eintrag „Einstellungen“ über „Über die App“); Routen `settings.*`.
- **Standardwerte:** Adressen, Signaturen und Fußtexte kommen weiter aus `config/services.php` (jetzt als Standard der Seite), die übrigen Gruppen aus `MailDefaults`. Die heutigen Grußformeln und Zitat-Köpfe wurden in einzelne lesbare Einträge aufgelöst; die gesamte bestehende Testsuite (u. a. PROJ-6) läuft mit den aus den Listen erzeugten Mustern unverändert grün.
- **Abweichung vom Entwurf:** „Mit Ticket ausprobieren“ ruft das Ticket zweimal ab (einmal je Regelsatz) statt einmal; der Aufwand ist gering und der Abruf bleibt rein lesend. Die Ergebnisse stehen in der Sitzung (nicht in der Datenbank).
- **Tests:** `PROJ-27-SettingsTest` (31): Seiten und Rechte, Speichern, Version, Bereinigung, Prüfregeln, gleichzeitige Änderung, Datenbankausfall, Verlauf/Wiederherstellen/Zurücksetzen, Ausprobieren, Muster aus den Listen (Umlaute, Platzhalter, Text statt Muster), Amazon-Wörter, Wirkung in der Ticketansicht.
- **Offen für `/qa`:** Bedienung im Browser (Zeilen hinzufügen/entfernen, Aufklappen, Ausprobieren-Ansicht), 375/768/1440 px, Prüfung mit echten Amazon-Tickets.

## QA Test Results (2026-10-17)
**Ergebnis: bereit (keine Critical-/High-Fehler).**

- **Akzeptanzkriterien und Randfälle:** Alle Akzeptanzkriterien und dokumentierten Randfälle sind durch Tests abgedeckt (31 Tests: Seiten und Rechte, Speichern mit Version, Bereinigung, Prüfregeln, gleichzeitige Änderung, Datenbankausfall, Verlauf/Wiederherstellen/Zurücksetzen, Ausprobieren, Muster aus Listen, Amazon-Wörter). Live (Sail, Port 8081) geprüft: alle Einstellungsseiten liefern 200, Admin sieht das Formular, Nicht-Admin nicht, PUT ohne Token 419.
- **Sicherheit:** Änderungen nur für Admins (Formular-Anfrage prüft zuerst, nicht Admin → 403, durch Test belegt); Anfragen ohne CSRF-Token werden mit 419 abgelehnt (live geprüft); Ausgaben der Einstellungsseiten sind mit `{{ }}` escaped, einzige HTML-Ausgabe ist der bereinigte Verlauf der Ausprobieren-Ansicht (derselbe Sanitizer wie die Ticketansicht); der Anbieterschlüssel erscheint nirgends (Test).
- **Regression:** Gesamtsuite 1659 Tests, 1657 grün, 2 übersprungen.
- **Nicht durchgeführt:** eigener Browser-Durchgang (Chrome/Firefox/Safari, 375/768/1440 px). Es gab keinen Browser in der QA-Umgebung; ein schneller Sichttest der Oberfläche liegt vom Anwender vor (2026-10-17).
- **Beobachtungen (Low):** Die Ticketseite braucht mit den neuen Zammad-Abfragen für zusammengehörige Tickets rund 4 Sekunden (Ticket 2139944, mehrere Zammad-Aufrufe nacheinander); bei Bedarf später parallelisieren oder Teile nachladen. Ohne gewählten Namen sind Ticketseiten und Einstellungen ansehbar; das entspricht dem bestehenden Verhalten (Auswahl erzwingt nur Aktionen).
