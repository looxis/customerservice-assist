---
id: POLICY-056
title: Amazon – nur gelistete Produkte, Nachbestellung von Zubehör und kein Wechsel zu eigenen Kanälen
type: policy
status: active
last_reviewed: 2026-10-23
customer_types:
sales_channels:
products:
categories:
  - product-question
  - order-process-question
topics:
  - sales-channel
  - addon
  - delivery-options
summary: "Ein Kunde, der bei Amazon bestellt hat oder dort schreibt, möchte etwas nachbestellen oder nachträglich dazu kaufen (zum Beispiel Zubehör zur Erinnerungsbox, das nur als Addon bei der Konfiguration bestellbar war) oder fragt nach einem Produkt, das auf Amazon nicht gelistet ist. Regelt: Was auf Amazon nicht als eigenes Produkt gelistet ist, ist dort nicht erhältlich; im Amazon-Nachrichtensystem darf der Kunde nicht zu eigenen Verkaufskanälen geleitet werden; schreibt er direkt per E-Mail, ist Shop oder Verkauf per E-Mail möglich."
retrieval: binding
triggers:
  - nachbestellen
  - nachkaufen
  - nachträglich bestellen
  - nachliefern
  - Zubehör
  - Addon
  - Add-on
  - einzeln kaufen
  - einzeln bestellen
  - nicht auf Amazon
  - bei Ihnen bestellen
  - direkt bestellen
exclude_triggers:
when:
actions:
related_knowledge:
  - POLICY-049
  - POLICY-043
  - POLICY-030
  - POLICY-024
  - POLICY-026
  - PROCESS-010
---

Gilt produktübergreifend für Kunden, die über Amazon bestellt haben oder bei Amazon schreiben. Die Antwort hängt davon ab, **über welchen Weg der Kunde schreibt**: Amazon-Nachrichtensystem oder direkte E-Mail.

# Regel

**Was nicht auf Amazon als eigenständiges Produkt gelistet ist, ist dort nicht erhältlich und kann über Amazon nicht nachbestellt werden.** Das gilt für alles und jeden, nicht nur für ein bestimmtes Produkt. Ist etwas auf Amazon tatsächlich als eigenes Produkt gelistet, gilt diese Einschränkung dafür nicht.

Beispiel: Optionales Zubehör ist bei uns oft nur ein **Addon**, das sich ausschließlich während der Konfiguration kostenpflichtig hinzufügen lässt (bei der Erinnerungsbox zum Beispiel Holz-Inlay mit Sortierung, Reagenzglas mit Korkstopfen, Magnetfolie). Hat ein Amazon-Kunde nur die Box ohne diese Optionen bestellt, können wir sie über Amazon nicht nachliefern, weil sie kostenpflichtig und dort nicht als eigenes Produkt gelistet sind.

# Amazon-Nachrichtensystem

**Der Kunde darf nicht von Amazon weg zu unseren eigenen Verkaufskanälen geleitet werden.** Das verstößt gegen die Nutzungsbedingungen von Amazon und gefährdet unser Verkäuferkonto. Es ist unbedingt zu vermeiden.

- Nur mitteilen, dass das Produkt auf Amazon nicht gelistet ist und wir es dort deshalb nicht anbieten können.
- Keinen Hinweis auf looxis.de, den Shop, eine E-Mail-Adresse, Telefon, Mollie, Vorkasse oder eine Bestellung außerhalb von Amazon geben, auch nicht indirekt oder auf Nachfrage des Kunden.
- Die Absage darf so formuliert sein, dass sie das Wort **AUF AMAZON** in Großbuchstaben hervorhebt. Das ist ein dezenter Hinweis, nie eine Aufforderung.

# Direkte E-Mail (nicht über Amazon)

Schreibt der Kunde uns direkt per E-Mail, also nicht über das Amazon-Nachrichtensystem, gilt die Einschränkung nicht mehr:

- Ist das Produkt in unserem eigenen Shop gelistet, auf looxis.de verweisen.
- Ist es auch dort nicht gelistet, kann der Verkauf per E-Mail angeboten werden, Bezahlung im Voraus über Mollie (Vorgehen und Angaben: POLICY-049, POLICY-043, PROCESS-010). Preise nennt der Kundenservice nur, wenn sie vorliegen oder ein Mitarbeiter sie vorgibt (POLICY-026).

# Kommunikation

Amazon-Nachricht: „Da das Produkt AUF AMAZON nicht gelistet ist, können wir Ihnen das Produkt leider auch nicht AUF AMAZON anbieten.“

# Gilt nicht wenn

- Es geht um eine Reklamation oder einen Ersatz zu einer bestehenden Amazon-Bestellung (POLICY-002, POLICY-006).
- Es geht um Selbstabholung (POLICY-030).

# No-Gos

- Im Amazon-Nachrichtensystem zu looxis.de, zu einer E-Mail, einem Anruf, Mollie oder zu einer Bestellung außerhalb von Amazon leiten.
- Eine Nachlieferung von Zubehör oder Addons über Amazon zusagen, wenn sie dort nicht gelistet sind.
- Den Hinweis „AUF AMAZON“ als Aufforderung formulieren („wenden Sie sich direkt an uns“).
