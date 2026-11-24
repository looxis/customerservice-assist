---
id: PLAYBOOK-010
title: Hintergrund oder Bildbereich unerwartet entfernt oder beibehalten
type: playbook
status: active
customer_types:
sales_channels:
products:
- 2d-glass-photo
- 3d-glass-photo
- whisky-glass-base-engraving
categories:
- complaint
topics:
- interpretation
- customer-configuration
- production-error
- replacement
related_knowledge:
- PRODUCT-003
- PRODUCT-004
- PRODUCT-016
- GLOSSARY-002
- PLAYBOOK-002
- PERMISSION-002
- PROCESS-005
- PROCESS-007
- POLICY-074
- TONE-002
- TONE-006
summary: "Der Kunde beanstandet, dass beim Motiv im Glas ein Teil des Hintergrunds entfernt oder stehen gelassen wurde (zum Beispiel Fell, Decke, Gegenstand). Gilt für 2D- und 3D-Glasfotos und das Whiskyglas Bodengravur; regelt Prüfung, Interpretationsspielraum und Neufertigung."
retrieval: triggered
triggers:
- Hintergrund
- freigestellt
- Freistellung
- Hauptmotiv
- grüne Markierung
- Markierung
- Bildbereich
- entfernt
exclude_triggers:
when:
actions:
---

Gilt für 2D- und 3D-Glasfotos und das Whiskyglas Bodengravur bei allen Kundenarten und Vertriebskanälen, wenn die Beanstandung die Zuordnung von Hauptmotiv und Hintergrund betrifft.

# Typisches Fallmuster

Der Kunde beanstandet, dass bei der Freistellung beziehungsweise 3D-Aufbereitung ein Bildbereich entfernt oder beibehalten wurde.

Typische Beispiele:

- Bei einem Baby auf einem Fell wurde das Fell entfernt, obwohl der Kunde es als Teil des Motivs verstanden hat.
- Bei einer anderen Bestellung wurde Fell erhalten, während es bei einer neuen Interpretation fehlt.
- Bei einer Person vor dem Eiffelturm wurde der Eiffelturm als Hintergrund nicht berücksichtigt.
- Ein Bildbereich wie eine Bank, Decke, ein Gegenstand oder ein Teil der Umgebung blieb erhalten, obwohl der Kunde ihn als Hintergrund verstanden hat.

# Produktabhängige Ausgangslage

Beim 3D-Glasfoto wird das Hauptmotiv in 3D modelliert; der Hintergrund bleibt im 2D-zu-3D-Prozess grundsätzlich unberücksichtigt.

Beim 2D-Glasfoto wird der Hintergrund nur dann gezielt freigestellt, wenn der Kunde „Hintergrund entfernen“ gewählt hat.

Beim 2D-Glasfoto ohne gewählte Hintergrundentfernung ist eine fehlende Gravur transparenter oder vollständig schwarzer Bildbereiche keine Hintergrund-Freistellung und muss von diesem Fallmuster getrennt werden.

Beim 3D-Glasfoto wird nach und nach, Glasgröße für Glasgröße, ein neuer visueller Konfigurator zum Einpassen des Fotos eingeführt. Er hat keine grüne Markierung mehr. Im neuen Konfigurator wird nur das Hauptmotiv verarbeitet; alles, was hinter oder neben dem Hauptmotiv liegt, wird als Hintergrund entfernt. Der Kunde hat darauf keinen Einfluss. Ob und ab wann eine Variante umgestellt ist, steht im Produktwissen („Neuer Konfigurator seit [Datum] – keine grüne Markierung mehr“). Ohne diesen Eintrag gilt der bisherige Konfigurator mit grüner Markierung. Beim 2D-Glasfoto gilt die grüne Markierung unverändert.

Beim Whiskyglas Bodengravur gibt es weder im Online-Shop noch auf Amazon eine grüne Markierung. Der Hintergrund wird standardmäßig nach bestem Wissen entfernt. Es gilt der Abschnitt „Whiskyglas Bodengravur“.

# Prüfung

1. Das Originalbild und die Bestellkonfiguration in EOCS prüfen.
2. Feststellen, ob beim 2D-Glasfoto „Hintergrund entfernen“ gewählt wurde.
3. Beim Whiskyglas Bodengravur gab es keine Markierung: Abschnitt „Whiskyglas Bodengravur“ anwenden.
4. Beim 3D-Glasfoto das Bestelldatum mit dem Umstellungsdatum im Produktwissen vergleichen. Bei einer Bestellung ab diesem Datum gab es keine Markierung: Abschnitt „Bestellung im neuen Konfigurator“ anwenden.
5. Sonst (2D-Glasfoto oder 3D-Glasfoto vor der Umstellung beziehungsweise ohne Eintrag) prüfen, ob der beanstandete Bildbereich mit dem grünen Pinsel markiert wurde.
6. Prüfen, ob die Markierung eindeutig den jetzt beanstandeten Bereich umfasst.
7. Falls der Kunde sich auf eine frühere Bestellung beruft, feststellen, ob es sich um denselben Produktionsvorgang beziehungsweise dieselbe Konfigurations-ID oder um einen neuen, unabhängigen Interpretationsvorgang handelt.
8. Bleibt die Zuordnung oder Markierung unklar, PROCESS-005 anwenden.

# Fall A: Kein klar markierter Bereich

War der beanstandete Bereich nicht klar grün markiert und lässt sich objektiv sowohl als Teil des Hauptmotivs als auch als Hintergrund verstehen, liegt Interpretationsspielraum vor.

Das gilt sowohl für einen entfernten als auch für einen beibehaltenen Bildbereich.

Eine fachlich vertretbare Interpretation innerhalb dieses Spielraums ist für sich genommen kein Produktionsfehler.

Dass derselbe oder ein ähnlicher Bereich bei einer früheren, separat konfigurierten Bestellung anders interpretiert wurde, macht die neue Interpretation nicht automatisch fehlerhaft. Für getrennte Interpretationsvorgänge zusätzlich PLAYBOOK-002 beachten.

# Lösung bei unmarkiertem Interpretationsspielraum

Ist der Kunde mit der Interpretation unzufrieden, kann eine kostenpflichtige Kulanz-Neuanfertigung angeboten werden.

Für die neue Interpretation soll der Kunde die Bereiche, die im Glas unbedingt erhalten bleiben sollen, eindeutig mit dem grünen Pinsel markieren (2D-Glasfoto und 3D-Glasfoto im bisherigen Konfigurator; im neuen Konfigurator siehe unten).

Bei einem 3D-Glasfoto entsteht dadurch eine neue räumliche Interpretation. Vor der Produktion PROCESS-007 anwenden.

Die konkrete Kulanzhöhe beziehungsweise Preisentscheidung richtet sich nach den für Kundenart und Vertriebskanal geltenden Kulanzregeln.

# Fall B: Klar markierter Bereich wurde nicht berücksichtigt

War der betreffende Bildbereich klar grün markiert und wurde er trotzdem entfernt oder nicht im Motiv berücksichtigt, wurde eine eindeutige Kundenanweisung nicht umgesetzt.

Dies ist eine berechtigte Reklamation.

Die Reklamation wird durch eine kostenfreie korrekte Neuproduktion und kostenfreien Versand behoben.

Der Kundenservice darf die regelkonforme Neuproduktion und den Ersatzversand nach PERMISSION-002 zusagen.

Bei einem 3D-Glasfoto wird für die korrigierte Neuproduktion eine neue Interpretation erstellt; vor der Produktion PROCESS-007 anwenden.

# Bestellung im neuen Konfigurator (nur 3D-Glasfoto)

Gilt, wenn das Produktwissen ein Umstellungsdatum nennt und die Bestellung an oder nach diesem Datum aufgegeben wurde.

Es gab keine Markierung, Fall B scheidet aus. Es wird nur das Hauptmotiv verarbeitet; Bildbereiche hinter oder neben dem Hauptmotiv (zum Beispiel Fell, Decke, Bank, Gebäude) werden als Hintergrund entfernt. Das ist kein Produktionsfehler.

Der Kunde hat keinen Einfluss darauf, solche Bereiche in das Glasfoto zu übernehmen, weder über Angaben bei einer Neufertigung noch über ein selbst freigestelltes Foto. Eine Neufertigung, bei der zusätzliche Bereiche aus dem Hintergrund übernommen werden, wird nicht angeboten. Der grüne Pinsel wird nicht erwähnt.

Bleibt unklar, ob der beanstandete Bereich zum Hauptmotiv gehört, PROCESS-005 anwenden.

# Whiskyglas Bodengravur

Es gab keine Markierung, Fall B scheidet aus. Der Hintergrund wird nach bestem Wissen entfernt; ein beanstandeter Grenzbereich ist Interpretationsspielraum wie in Fall A.

Lösung: Der Auftrag kann neu eingestellt werden, gewünschte Änderungen am Hintergrund fließen in die neue Fertigung ein. Dafür nennt der Kunde, welche Bereiche erhalten bleiben oder entfallen sollen. Alternativ entfernt er den Hintergrund selbst und liefert das Motiv bevorzugt als PNG mit transparentem Hintergrund oder mit vollständig schwarzem Hintergrund. Der grüne Pinsel wird nicht erwähnt.

Ob die neue Fertigung kostenpflichtig oder kostenfrei ist, entscheidet der Mitarbeiter im Reklamationsformular; ohne Formular gilt Fall A (kostenpflichtige Kulanz-Neuanfertigung).

# Frühere Bestellung als Vergleich

Eine frühere Interpretation ist nur dann als identische Produktionsgrundlage zu behandeln, wenn tatsächlich dieselbe Interpretation beziehungsweise dieselbe Konfigurations-ID wiederverwendet wird.

Eine neue eigenständige Bestellung kann bei nicht markierten Grenzbereichen anders interpretiert werden. Allein die Aussage „Beim letzten Mal war das Fell dabei“ belegt deshalb bei einer neuen Interpretation keinen Produktionsfehler.

# Abgrenzung

Dieses Playbook betrifft die Zuordnung von Hauptmotiv und Hintergrund.

Nicht hierunter fallen:

- Schattenwurf oder verdeckte Motivbereiche innerhalb der 3D-Darstellung; dafür PLAYBOOK-008 verwenden.
- Zu dunkle oder schwach sichtbare Bildbereiche; dafür PLAYBOOK-009 verwenden.
- Transparente oder vollständig schwarze Bereiche eines 2D-Layouts oder eines Motivs für das Whiskyglas Bodengravur, die aufgrund der Laserlogik nicht gelasert werden.
