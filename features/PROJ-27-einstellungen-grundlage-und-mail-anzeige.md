# PROJ-27: Einstellungen – Grundlage und Mail-Anzeige

## Status: Planned
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

## Open Questions
- [ ] Sollen die Standardwerte der Grußformeln in der Liste vollständig sichtbar sein (sind heute fast 30 Einträge mit Mustern wie „(?:best|kind) regards“, die in einzelne Einträge aufzulösen sind)?
- [ ] Muss „Mit Ticket ausprobieren“ auch Testmodus-Tickets (zurückgespult) unterstützen? Vorschlag: nein, nur der ganze Verlauf.
- [ ] Wer wird bei einer Änderung benachrichtigt? Vorschlag: niemand, der Verlauf genügt.
