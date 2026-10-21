# Wie die App Wissen auswählt – Handbuch für den KI-Chat

> Eine Seite, zusammen mit `KNOWLEDGE_AUTHORING_GUIDE.md` im Chat hinterlegen. Der Chat schreibt den fachlichen Inhalt; die App entscheidet, **welches Wissen im Prompt landet**. Damit sich Inhalte nicht vermischen, braucht jede Datei Angaben zum Abruf.

## Die Auswahl in zwei Stufen
1. **Geltungsbereich:** Kundenart (`customer_types`), Kanal (`sales_channels`) und Produkt (`products`) legen fest, wo ein Dokument in Frage kommt. Leer heißt „gilt für alle“.
2. **Abruf nach Anliegen:** Aus dem Geltungsbereich kommen nur die Dokumente mit, die der Fall braucht. Das entscheiden `retrieval`, `triggers`, `when` und `actions`.

## Die drei Klassen (`retrieval`)
| Klasse | Heißt | Wann im Prompt |
|---|---|---|
| `core` | Grundwissen | immer – sehr sparsam, rund 15.000 Zeichen insgesamt, Produktgrundlagen je Produkt rund 9.000 (vorläufig) |
| `triggered` | bei Bedarf | wenn ein Auslöser im aktuellen Anliegen vorkommt oder die Bedingung erfüllt ist |
| `binding` | verbindlich | immer im Geltungsbereich, wenn es keine Auslöser, Bedingung und Maßnahme gibt; sonst bei Treffer, erfüllter oder ungeklärter Bedingung oder wenn die KI die zugehörige Maßnahme empfiehlt; wird nie wegen Platzmangel weggelassen |

**Ein leeres oder fehlendes Feld ist nie „egal“:** Ohne `retrieval` zählt ein Dokument vorläufig als Grundwissen, steht also in jedem Prompt. Es gilt, was im Prompt nicht steht, kennt die KI nicht.

## Was zu jeder Datei gehört
- `summary`: ein bis zwei Sätze – wann gebraucht, wofür, und **nur falls es sie gibt** eine Abgrenzung. **Sie ist auch das, was der KI-Router sieht** (ID, Titel, summary; keine Auslöser): Das typische Kundenanliegen in Alltagssprache beschreiben, Abgrenzungen genau fassen („nicht für **ausschließlich** Helligkeitsreklamationen“, nicht „nicht für Helligkeit“) und nie erfinden. Unter 60 Zeichen warnt `knowledge:check`.
- `triggers`: Wörter, wie Kunden schreiben (am Wortanfang gesucht; „Logo“ findet „Firmenlogo“ nicht, also Zusammensetzungen einzeln; mindestens vier Zeichen; keine Allerweltswörter; auch Italienisch/Französisch/Englisch).
- `exclude_triggers`: Wendungen, die einen Treffer im selben Satz unterdrücken („unser Logo“); wirken nie in Fragen; nicht bei `binding`.
- `when`: Bedingung aus Fall- und Bestelldaten (Schlüssel im Sitzungsstart-Block). Mehrere Bestellbedingungen gelten für dieselbe Bestellung; fehlende Daten sind „unbekannt“.
- `actions`: bei verbindlichen Regeln zu einer Maßnahme. Genau passend wählen: eine Kulanzregel für Kundenfehler gehört zu `goodwill-reproduction` (kostenpflichtige Kulanz-Neuanfertigung), **nie** zu `reproduction` (kostenfreier Ersatz bei berechtigter Reklamation); Teilerstattung ohne Neuanfertigung ist `partial-refund`.
- `exclude_products` (Geltungsbereich, optional): Produkte, für die das Dokument ausdrücklich nicht gilt. Es entfällt nur, wenn alle gewählten Produkte darin stehen; bei unbekanntem Produkt bleibt es.

## Arbeitsabläufe (`procedure`) gehen nicht an die KI
Ein Arbeitsablauf ist eine Anleitung für Menschen. Die KI bekommt ihn nie; die App zeigt ihn dem Mitarbeiter nach dem Vorschlag, passend zum Vorgang (`actions`), zur Kundengruppe und zum Produkt. Verweist ein anderes Dokument auf ihn (PROCEDURE-004), meldet die KI deshalb keine Wissenslücke.

Im Ablauf stehen Links zu Werkzeugen als `[Text](link:kürzel)`: `eocs-auftrag`, `zammad-ticket`, `amazon-bestellung`, `eocs-kunden`, `vanilo-kunden`, `mailcoach-listen`. Die App macht daraus Knöpfe, ohne Ziel bleibt nur der Text. Adressen schreibst du nicht hinein. Einzelheiten: Authoring Guide, Abschnitt „Arbeitsabläufe“.

## Der KI-Router (zweite Stufe des Abrufs)
Zusätzlich zu den Auslösern ergänzt ein Router Dokumente, die zum Anliegen **sinngemäß** passen, auch wenn der Kunde andere Wörter nutzt. Er wählt nur aus Dokumenten, die der Abruf nicht genommen hat, ersetzt nichts und umgeht keine verbindlichen Regeln. Deshalb gilt: `summary` sorgfältig schreiben; Auslöser bleiben wichtig, müssen aber nicht jede Formulierung abdecken.

## Fragen, die du stellst
0. Vor allem anderen: Was davon ist eine Regel oder Entscheidung (Markdown) und was ein Produktfakt (ProductForge)? Sortiere zuerst, siehe Authoring Guide, Abschnitt „Wohin gehört welches Wissen?“.
1. Wann wird das gebraucht, und welche Wörter schreibt der Kunde dann?
2. Gilt es immer oder nur unter Bedingungen?
3. Muss es unbedingt greifen?
4. Welcher ähnliche Fall soll es ausdrücklich **nicht** auslösen?
5. Ist das wirklich Grundwissen?

## Nach jeder Charge neuer Dateien
Der Autor führt `knowledge:select <Kundengruppe> --text="…"` für drei bis vier typische Fälle aus (Reklamation, Produktfrage, Angebot mit Logo) und gibt dir die Liste der abgerufenen Dokumente. So siehst du, was bei welchem Fall im Prompt landet, und bemerkst Regeln, die überall stehen oder nie greifen.
