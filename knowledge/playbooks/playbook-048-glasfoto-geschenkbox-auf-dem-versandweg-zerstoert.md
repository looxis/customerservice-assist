---
id: PLAYBOOK-048
title: "Glasfoto: Geschenkbox auf dem Versandweg zerstört, Glas heil"
type: playbook
status: active

customer_types:
sales_channels:

products:
categories:
  - complaint
topics:
  - replacement
  - transport-damage

summary: "Ein Kunde meldet, dass die Geschenkbox seines Glasfotos (Glasfoto in Farbe, 2D- oder 3D-Glasfoto) auf dem Transport zerstört oder eingedrückt wurde, das Glas selbst ist aber heil. Anweisung an den Agenten: nur die Geschenkbox kostenlos neu senden, im Reklamationsauftrag den Artikel „Leerboxen Glas“ (SKU 12014) statt des Glasfotos verwenden. Gilt nicht, wenn auch das Glas beschädigt ist."
retrieval: triggered
triggers:
  - Geschenkbox
  - Geschenkverpackung
  - Verpackung zerstört
  - Box zerstört
  - Box kaputt
  - Box beschädigt
  - Box eingedrückt
  - coffret cadeau
  - boîte cadeau
  - boîte écrasée
  - boîte abîmée
  - gift box
  - box damaged
  - box crushed
exclude_triggers:
when:
actions:

related_knowledge:
  - PERMISSION-002
  - POLICY-002
  - POLICY-010
  - PROCEDURE-005
---

Gilt für alle Kundenarten und Vertriebskanäle, für alle Glasfotos: Glasfoto in Farbe, 2D-Glasfoto und 3D-Glasfoto.

# Fallmuster

Die Geschenkbox eines Glasfotos kommt auf dem Versandweg zerstört an. Das Glas selbst ist unbeschädigt. Solche Meldungen kommen häufiger vor, vor allem aus Frankreich.

# Erkennungsmerkmale

- Der Kunde schreibt, die Geschenkbox oder Verpackung sei zerstört, eingedrückt oder kaputt angekommen.
- Das Glasfoto selbst wird nicht als beschädigt beschrieben.

# Zu prüfen

1. Ist ausschließlich die Geschenkbox defekt? Ist auch das Glas beschädigt, gilt dieses Playbook nicht, sondern die üblichen Regeln für Reklamationen (POLICY-002).
2. Beschreibt der Kunde die Box klar als zerstört, ist kein Foto nötig. Bei unklarer Schilderung ein Foto der Box anfordern.

# Berechtigt wenn

- Die Geschenkbox eines Glasfotos (Glasfoto in Farbe, 2D oder 3D) ist auf dem Versandweg zerstört worden und das Glas ist heil.

# Nicht berechtigt wenn

- Auch das Glas ist beschädigt. Dann gelten die üblichen Reklamationsregeln (POLICY-002).

# Noch nicht entscheidbar wenn

- Unklar ist, ob nur die Box oder auch das Glas betroffen ist. Beim Kunden nachfragen, ob das Glas unbeschädigt ist, und bei unklarer Schilderung ein Foto anfordern.

# Empfohlene Maßnahme

Nur die Geschenkbox wird kostenlos neu versendet. Im Reklamationsauftrag steht dazu nicht der ursprünglich bestellte Artikel (zum Beispiel ein 3D-Glasfoto in einer bestimmten Größe), sondern der Artikel „Leerboxen Glas“ mit der SKU 12014. Würde der Ursprungsartikel in die Reklamation aufgenommen, würde auch das Glas neu produziert. Das Anlegen des Reklamationsauftrags richtet sich nach PROCEDURE-005.

Die Maßnahme ist ein Neuversand (`reshipment`), keine Neuproduktion (`reproduction`): Die Leerbox ist ein Lagerartikel, es wird nichts produziert und keine Konfiguration geändert. Die Regeln für Neuproduktion (POLICY-032, PROCESS-007) gelten deshalb nicht.

Die zerstörte Geschenkbox muss nicht zurückgesendet werden.

# Befugnis

Der Kundenservice darf den kostenlosen Ersatzversand selbst zusagen (PERMISSION-002).

# Kommunikation

Dem Kunden mitteilen, dass die Geschenkbox kostenlos neu versendet wird und er die zerstörte Box nicht zurückschicken muss. Reklamationsnummer und Versand nennen.

# No-Gos

- Den Ursprungsartikel in den Reklamationsauftrag aufnehmen, wenn nur die Box defekt ist.
- Dem Kunden eine Neuproduktion des Glases zusagen, wenn das Glas heil ist.
- Die Maßnahme „Neuproduktion“ empfehlen: Es ist ein Neuversand.
- Eine Rücksendung der zerstörten Box verlangen.
