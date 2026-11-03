---
id: PLAYBOOK-047
title: Kunde erwähnt Fotos, die nicht angekommen sind
type: playbook
status: draft

customer_types:
sales_channels:

products:
exclude_products:
categories:
  - complaint
topics:
  - photo-evidence
  - complaint-intake

summary: "Der Kunde schreibt, er habe Fotos oder Bilder geschickt („anbei“, „hier ist ein Bild“, „auf den Fotos sehen Sie“), oder er könne keine Fotos senden, aber seine Nachricht hat keinen Anhang; häufig mit dem Betreff „[Unzustellbar] Nachricht zu groß“. Klärt, ob die Fotos anderswo angekommen sind, und wie der Kunde sie verkleinert erneut sendet. Gilt nicht, wenn die erwähnten Fotos als Anhang vorliegen."
retrieval: triggered
triggers:
  - Nachricht zu groß
  - im Anhang
  - angehängt
  - anhängen
  - anzuhängen
  - Foto anbei
  - Fotos anbei
  - Bild anbei
  - Bilder anbei
  - anbei die Fotos
  - anbei ein Foto
  - anbei die Bilder
  - anbei ein Bild
  - hier ist ein Bild
  - hier sind die Bilder
  - hier die Fotos
  - auf den Fotos
  - auf dem Foto
  - auf den Bildern
  - auf dem Bild
  - siehe Foto
  - siehe Bild
  - kein Bild senden
  - keine Fotos senden
  - attached
exclude_triggers:
when:
actions:
priority:
risk_level:

owner:
reviewed_by:
last_reviewed:
trial_until:
trial_goal:

related_knowledge:
  - POLICY-029
---

Gilt für alle Kundenarten und Vertriebskanäle. Für Amazon gilt beim Weg, über den die Fotos gesendet werden, eine Besonderheit (Abschnitt „Kommunikation“).

# Fallmuster

Der Kunde bezieht sich auf Fotos oder Bilder, die er geschickt haben will, oder schreibt, dass er keine Fotos senden oder anhängen kann. Bei uns ist mit dieser Nachricht kein Bild angekommen.

# Erkennungsmerkmale

- Die Nachricht nennt Fotos oder Bilder („anbei“, „im Anhang“, „hier ist ein Bild“, „auf den Fotos sehen Sie …“, „Bild 2“), hat aber keinen Anhang.
- Der Kunde schreibt, er könne kein Bild senden oder anhängen oder es sei zu groß.
- Der Betreff lautet „[Unzustellbar] Nachricht zu groß“, auch mit „Re:“ davor.
- Bei Amazon kommen mehrere kurze Nachrichten kurz hintereinander, jede als eigenes Ticket.

# Typische Ursache

Smartphones nehmen Fotos in sehr hoher Auflösung und Dateigröße auf, die für ein Poster reichen würde; um ein Problem zu erkennen, genügt etwa ein Zehntel davon. Mehrere solcher Fotos überschreiten schnell die Größengrenze: Unser Postfach nimmt E-Mails über 20 MB nicht an. Der Absender erhält dann automatisch eine Ablehnung mit dem Betreff „[Unzustellbar] Nachricht zu groß“. Antwortet er darauf, entsteht ein Ticket mit diesem Betreff; die ursprüngliche Nachricht mit Text und Fotos liegt uns nicht vor.

Kunden, die über das Amazon-Nachrichtensystem schreiben, senden oft mehrere Nachrichten hintereinander wie in einem Chat. Jede abgeschickte Nachricht wird ein eigenes Ticket. Fotos können deshalb in einer anderen Nachricht oder einem anderen Ticket desselben Kunden stehen.

# Zu prüfen

1. Liegen die Fotos in einer anderen Nachricht dieses Tickets oder in einem anderen Ticket desselben Kunden aus derselben Zeit? Wenn ja, damit weiterarbeiten und die Fotos nicht erneut anfordern.
2. Ist der Sachverhalt ohne die Fotos verständlich (welches Produkt, welches Problem)? Beim Betreff „Nachricht zu groß“ fehlt oft auch die ursprüngliche Beschreibung; dann zusätzlich die Angaben nach POLICY-029 erfragen.
3. Werden Fotos für die Prüfung gebraucht (POLICY-029)? Wenn nicht, ohne Fotos weiterbearbeiten.

# Noch nicht entscheidbar wenn

- Die Fotos fehlen und das Problem ist ohne sie nicht zu beurteilen. Rückfrage an den Kunden: Fotos erneut senden (Abschnitt „Kommunikation“).

# Empfohlene Maßnahme

Den Kunden freundlich darauf hinweisen, dass keine Fotos angekommen sind, wahrscheinlich weil sie zu groß waren, und ihn bitten, sie verkleinert erneut zu senden.

# Befugnis

Der Mitarbeiter fordert die Fotos selbst an; eine Freigabe ist nicht nötig.

# Kommunikation

- Sagen, dass bei uns leider keine Fotos angekommen sind, vermutlich weil die Dateien zu groß waren. Keine Schuldzuweisung.
- Erklären, dass Fotos zur Dokumentation eines Problems keine hohe Auflösung brauchen; sie sollen nur zeigen, was nicht stimmt. In der Regel reichen ein bis zwei Fotos.
- Bitten, die Fotos verkleinert oder in geringerer Auflösung zu senden, jedes Foto einzeln in einer eigenen Nachricht.
- Kurzer Hinweis: Vor einer neuen Aufnahme lässt sich in den Einstellungen der Kamera-App die Bildgröße oder Auflösung verringern.
- Amazon: Die Fotos erneut über das Amazon-Nachrichtensystem senden lassen. Keine E-Mail-Adresse und keinen anderen Weg nennen.
- Andere Kanäle: Die Fotos als Antwort auf unsere E-Mail senden lassen.

# No-Gos

- Nicht für Fotos danken oder auf den Inhalt von Fotos eingehen, die nicht vorliegen.
- Die Reklamation nicht allein deshalb ablehnen, weil die Fotos fehlen.
- Bei Amazon keine E-Mail-Adresse als Weg für die Fotos nennen.
- Keine Menüpunkte bestimmter Handys oder Kamera-Apps nennen.
