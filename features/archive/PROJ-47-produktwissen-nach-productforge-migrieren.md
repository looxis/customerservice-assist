# PROJ-47: Produktwissen aus `/knowledge` nach ProductForge migrieren

## Status: Approved
**Created:** 2026-10-19
**Last Updated:** 2026-10-22

## Dependencies
- Requires: PROJ-38 (Produktinformationen aus ProductForge)
- Verwandt: PROJ-46 (Aufgabenteilung im Formular, offener Rest), PROJ-2 (Knowledge-Authoring-Kit)

## Ausgangslage
Die Produktdateien unter `knowledge/products/` enthielten Produktfakten (Technik, Maße, Pflege, Funktion) und Entscheidungen gemischt. Seit ProductForge gilt: In `knowledge/` steht, was wir tun und wer es darf; in ProductForge steht, wie das Produkt ist (Authoring Guide, Abschnitt „Wohin gehört welches Wissen?“).

## Umsetzung
Je Produktdatei wurden die Aussagen gemeinsam mit dem Auftraggeber sortiert. Die Produktfakten trug der Auftraggeber in ProductForge ein (Beschreibung, Technische Daten, Pflege- und Warnhinweise, Bedienungsanleitung, Kundenservice-Wissen, an den Varianten die Unterschiede). Die Dateien in `knowledge/` blieben als **Wissensbereich**: Geltungsbereich, `order_keywords` (Artikelnummern und Namen aus den Bestellpositionen), `customer_terms`, Router-Text, Verweise auf die Regeln und ein Satz zur Konsequenz. Erst nach der Bestätigung „steht in ProductForge“ wurde eine Datei gekürzt.

| Datei | Ergebnis |
|---|---|
| PRODUCT-001 Essbare Fotos | Wissensbereich, 17 Kundenbegriffe |
| PRODUCT-005 Lunchbox mit Holzdeckel | Wissensbereich (5.317 auf 1.820 Bytes) |
| PRODUCT-002 Zaubertasse | Wissensbereich, drei neue Kundenbegriffe |
| PRODUCT-007 Fototasse | Wissensbereich, Titel „Fototasse: Wissensbereich“, `order_keywords` mit elf Artikelnummern, fünf Kundenbegriffe, Status `active`; Schritt 5 in PLAYBOOK-026 ergänzt |
| PRODUCT-004 2D-Glasfoto | Wissensbereich, `order_keywords` mit 32 Artikelnummern und sechs Namen, Status `active` |
| PRODUCT-003 3D-Glasfoto | Wissensbereich, 25 Artikelnummern, Status `active`; neues PLAYBOOK-037 (Anfrage nach früheren Aura-Namen) |
| PRODUCT-006 3D-Motivgröße | `deprecated`; Inhalt in ProductForge, Bewertung in PLAYBOOK-018 |

Ausnahme: ausgelaufene Produkte (Opera, Leuchtstelen) führen ihre Fakten bewusst in `knowledge/` (PRODUCT-009, befristet bis 31.12.2027); die Ausnahme steht im Authoring Guide.

## Entscheidungen
- Entscheidungen und Antwortformulierungen bleiben in Playbooks, Prozessen und Policies, auch wenn sie nur ein Produkt betreffen (`products` im Frontmatter).
- Gelten Produktfakten für mehrere Masters, hält ein Master den Text als Wissensursprung (zum Beispiel Aura (Quader) bei den 3D-Glasfotos).
- Kundenbegriffe (frühere Namen, Marken, Umgangssprache) bleiben als `customer_terms` in `knowledge/`, auch wenn der Hintergrund in ProductForge steht.
- Produktdateien, deren Inhalt ganz nach ProductForge ging und die kein eigener Wissensbereich sind, werden `deprecated`, nicht gelöscht (PRODUCT-006).

## Offen (nicht Teil dieses Features)
Das Formular, die Hinweise und die Begriffe nach der Aufteilung bereinigen: PROJ-46.
