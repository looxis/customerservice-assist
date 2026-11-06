---
id: POLICY-043
title: Offene Bestellung mit Mollie-Zahlung – SEPA-Überweisung, Zahlungsfrist und Stornierung
type: policy
status: active
customer_types:
sales_channels:
  - looxis-de
  - fachhaendler
  - looxis-pro
products:
categories:
  - order-process-question
topics:
  - payment-processing
  - order-release
summary: "Ein Kunde kann eine Bestellung mit Zahlungsabwicklung über Mollie nicht bezahlen, zum Beispiel weil PayPal nicht funktioniert oder ihm Kontodaten fehlen. Klärt: Die klassische SEPA-Überweisung über Mollie ist eine zulässige Option und kein Wechsel der Zahlungsabwicklung; Kontodaten, Betrag und Zahlungsreferenz stammen nur aus der Mollie-Zahlungsanweisung; je Shop gibt es eine automatische Zahlungserinnerung nach 15 Minuten (looxis.de), keine (Fachhändler) oder einen wiederverwendbaren Zahlungslink (looxis-pro.com, manuelle Bestellungen); eine nicht rechtzeitig bezahlte Bestellung gilt als nicht freigegeben, kann weder manuell freigegeben noch in der Zahlungsart geändert werden und muss neu ausgelöst werden; Fristen allgemein einige Tage, nach Fristablauf eingehende Zahlungen retourniert Mollie; die Frage nach einer Rückkehr zur Lastschrift wird getrennt beantwortet. Gilt auch, wenn der Kunde schon überwiesen hat und fragt, wo seine Zahlung bleibt oder ob die Frist schon abgelaufen ist: Dann ist der Zahlungseingang bei Mollie maßgeblich, den LOOXIS im Mollie-Dashboard prüft."
retrieval: binding
triggers:
  - Paypal
  - Zahlung funktioniert nicht
  - Zahlung nicht möglich
  - kann die Bestellung nicht bezahlen
  - kann nicht bezahlen
  - keine Kontodaten
  - Kontodaten
  - Bankverbindung
  - Überweisung
  - SEPA-Überweisung
  - Sofortüberweisung
  - Mollie
  - Zahlungsanweisung
  - Zahlungsreferenz
  - Verwendungszweck
  - Zahlungsfrist
  - Zahlung fehlgeschlagen
  - Zahlung abgebrochen
  - Bestellung freigeben
  - Zahlungseingang
  - bereits überwiesen
  - Überweisung veranlasst
  - Zahlung nicht angekommen
  - Referenznummer
related_knowledge:
  - POLICY-041
  - POLICY-042
  - POLICY-044
  - PLAYBOOK-028
  - POLICY-057
  - PLAYBOOK-041
  - PROCEDURE-011
---

Gilt für Bestellungen im Online-Shop (looxis-de, fachhaendler, looxis-pro), deren Zahlung über Mollie abgewickelt wird.

# Regel

Die Zahlungsabwicklung über Mollie bietet mehrere Zahlungsoptionen an. PayPal ist nur eine davon. Kann ein Kunde mit PayPal nicht zahlen, kann er die Bestellung (bei verstrichener Frist die neu ausgelöste Bestellung, siehe unten) auch mit der klassischen SEPA-Überweisung über Mollie bezahlen.

Die SEPA-Überweisung über Mollie ist kein Wechsel der am Kundenkonto hinterlegten Zahlungsabwicklung. Sie widerspricht POLICY-042 nicht.

Kontodaten, genauer Betrag und Zahlungsreferenz müssen aus der Mollie-Zahlungsanweisung für genau diese Bestellung stammen und vom Kunden exakt eingehalten werden.

# Zahlungserinnerung, Frist und Stornierung

Was nach einer nicht abgeschlossenen Mollie-Zahlung geschieht, hängt vom Shop ab:

- **looxis.de (Endverbraucher):** Schlägt die Zahlung fehl oder wird sie nicht durchgeführt, wird nach 15 Minuten automatisch eine Zahlungserinnerung verschickt.
- **fachhaendler.looxis.de (Fachhändler / Reseller):** Aus technischen Gründen gibt es derzeit keine Zahlungserinnerung. Wurde nicht gezahlt, gilt die Bestellung als storniert bzw. wird in Kürze automatisiert storniert.
- **looxis-pro.com und manuell angelegte Bestellungen** (B2B oder Endverbraucher): Der Kunde erhält eine E-Mail mit einem Zahlungslink zu Mollie. Der Link kann immer wieder aufgerufen werden, bis die Zahlung abgeschlossen ist oder die Frist verstrichen ist.

**Frist:** Allgemein gilt „einige Tage“, es sei denn, Mollie nennt eine andere Frist. Die Frist nicht genauer beziffern. Wählt der Kunde zum Beispiel die SEPA-Überweisung, registriert Mollie das, zeigt die Zahlungsanweisung an und wartet auf die Zahlung. Die Frist steht mit Datum oder Anzahl Tage in der Zahlungsanweisung und wird nochmals per E-Mail zugeschickt. Erfolgt die Zahlung nicht in dieser Zeit, verfällt die Zahlungsoption, und die Bestellung wird storniert. Zahlt der Kunde nach Ablauf der Frist doch noch, retourniert Mollie den Betrag an den Zahlenden, in der Regel innerhalb von 1 bis 3 Werktagen. Das sind Regeln von Mollie, auf die LOOXIS wenig bis keinen Einfluss hat.

**Nicht freigegebene Bestellung:** Eine wegen ausgebliebener Zahlung nicht freigegebene oder stornierte Bestellung können Mitarbeiter von LOOXIS weder manuell freigeben noch kann die Zahlungsart für diese Bestellung nachträglich geändert werden (siehe POLICY-042). Dem Kunden bleibt, die Bestellung neu auszulösen. Dafür steht weiterhin die bisherige Zahlungsart Mollie mit ihren verschiedenen Optionen zur Verfügung, darunter die klassische SEPA-Überweisung. Lastschrift steht erst für Bestellungen zur Verfügung, die nach der Umstellung eingehen, also nachdem ein neues Mandat vorliegt, hinterlegt, umgestellt und mit dem Shop synchronisiert ist (POLICY-041, POLICY-042).

Bei Rechnung und Lastschrift ist die Zahlungsanweisung eindeutig festgelegt (POLICY-044). Bei Mollie hält die Zahlungsart selbst alle Zahlungsdetails und Informationen bereit, unmissverständlich und detailliert; Mollie ist ein renommierter, auf Online-Zahlungen spezialisierter Abwickler. Der Kunde bekommt sie von Mollie, auch für die SEPA-Überweisung. Der Kundenservice stellt dafür keine eigenen Zahlungsdetails bereit, nennt keine und verweist auf die Zahlungsanweisung von Mollie zur Bestellung.

# Überweisung angekündigt, Zahlungseingang noch nicht registriert

Hat der Kunde per SEPA-Überweisung über Mollie gezahlt und ist der Zahlungseingang noch nicht registriert:

- Der Kunde hat für die SEPA-Überweisung mehrere Tage Zeit; maßgeblich ist die Frist in der Mollie-Zahlungsanweisung. Nennt der Kunde eine andere, kürzere Frist, wird sie nicht bestätigt. Die Zahl der Tage wird nicht genannt.
- LOOXIS hat keinen Einblick in den Banktransfer selbst. Sichtbar ist nur, ob Mollie den Zahlungseingang registriert hat. Der Kundenservice sagt nicht, die Überweisung sei „unterwegs“ oder „angekommen“.
- Ob Mollie die Zahlung registriert hat und ob die vom Kunden genannte Referenznummer zur Bestellung gehört, prüfen Mitarbeiter im Mollie-Dashboard (PROCEDURE-011). Die Referenznummer wird dem Kunden nur bestätigt, wenn sie dort zur Bestellung passt.
- Der Kunde prüft in seiner Überweisung, ob IBAN, Betrag und Zahlungsreferenz genau so übernommen wurden, wie Mollie sie in der Zahlungsanweisung zu dieser Bestellung nennt.
- Sobald Mollie die Zahlung bestätigt, wird die Bestellung freigegeben (POLICY-057). Bis dahin wird sie nicht produziert und nicht verschickt.
- Dass die Zahlung weiterhin möglich ist, sagt die Antwort nur, wenn Verlauf, Bestelldaten oder Mollie-Dashboard zeigen, dass die Bestellung offen und nicht storniert ist; sonst als interne Klärung vorsehen.

# Getrennte Anliegen

Die Zahlung der bestehenden Bestellung und ein Wunsch nach Rückkehr zur Lastschrift sind getrennte Anliegen und werden getrennt beantwortet. Die Zahlung (bei Fristablauf: die Neubestellung) kann unabhängig von einer möglichen späteren Lastschrift erfolgen. Eine offene Entscheidung zur Lastschrift (siehe POLICY-041) darf eine hilfreiche Antwort zur aktuellen Zahlung nicht blockieren. Die Antwort erklärt zuerst die sofort nutzbare Zahlung und behandelt danach die Lastschrift für künftige Bestellungen.

# Kommunikation

Nicht bezahlte Bestellung eines Fachhändlers (keine Zahlungserinnerung):

„Ihre Bestellung wurde leider nicht bezahlt und gilt deshalb als nicht freigegeben bzw. wird in Kürze automatisch storniert. Wir können die Bestellung nicht manuell freigeben und auch die Zahlungsart für diese Bestellung nicht nachträglich ändern. Bitte lösen Sie die Bestellung neu aus. Dafür steht Ihnen weiterhin die bisherige Zahlungsart Mollie mit ihren verschiedenen Optionen zur Verfügung, darunter auch die klassische SEPA-Überweisung; PayPal ist nur eine davon. Kontodaten, Betrag und Zahlungsreferenz zeigt Ihnen Mollie in der Zahlungsanweisung an. Bitte übernehmen Sie sie genau und schließen Sie die Zahlung innerhalb der genannten Frist ab.“

Bestellung über looxis-pro.com oder manuell angelegt (Zahlungslink):

„Sie haben von uns eine E-Mail mit einem Zahlungslink zu Mollie erhalten. Diesen Link können Sie so lange immer wieder aufrufen, bis die Zahlung abgeschlossen ist oder die Frist verstrichen ist. Sie können dort auch die klassische SEPA-Überweisung wählen; PayPal ist nur eine der Optionen. Kontodaten, Betrag und Zahlungsreferenz zeigt Ihnen Mollie in der Zahlungsanweisung an.“

Bestellung im Shop looxis.de, Zahlung fehlgeschlagen oder nicht durchgeführt:

„Wenn eine Zahlung fehlschlägt oder nicht durchgeführt wird, verschickt das System nach 15 Minuten automatisch eine Zahlungserinnerung. Sie können die Zahlung dann abschließen; zur Auswahl stehen mehrere Optionen, auch die klassische SEPA-Überweisung. Kontodaten, Betrag und Zahlungsreferenz zeigt Ihnen Mollie in der Zahlungsanweisung an.“

Kunde hat nach Ablauf der Frist überwiesen:

„Erfolgt eine Zahlung nach Ablauf der Frist, wird sie von Mollie an den Zahlenden zurückgezahlt, in der Regel innerhalb von 1 bis 3 Werktagen. Bitte lösen Sie die Bestellung dann neu aus.“

Zahlungseingang noch nicht registriert (Referenznummer geprüft und passend):

„Ihre Bestellung ist bei uns registriert, der Zahlungseingang wurde bisher allerdings noch nicht registriert. Die von Ihnen genannte Referenznummer ist korrekt. Für eine SEPA-Überweisung über Mollie haben Sie mehrere Tage Zeit; maßgeblich ist die Frist in der Zahlungsanweisung von Mollie. Bitte prüfen Sie noch einmal in Ihrer Überweisung, ob IBAN, Betrag und Zahlungsreferenz genau so übernommen wurden, wie Mollie sie in der Zahlungsanweisung zu Ihrer Bestellung angibt. Wir sehen den Banktransfer selbst nicht, sondern erst, wenn Mollie den Zahlungseingang registriert hat. Sobald Mollie die Zahlung bestätigt, wird die Bestellung freigegeben; bis dahin wird sie nicht produziert und nicht verschickt. Geht die Zahlung nicht innerhalb der Frist ein, verfällt die Zahlungsoption und die Bestellung wird automatisch storniert.“

# Grenzen

- Keine Bankdaten, Beträge oder Zahlungsreferenzen nennen, die nicht aus der Mollie-Zahlungsanweisung der Bestellung stammen. Bei einer Mollie-Bestellung nicht auf das Geschäftskonto von LOOXIS verweisen (die Geschäftskonten gelten für die Zahlung auf Rechnung, POLICY-044).
- Nicht pauschal sagen, der Kunde müsse PayPal verwenden.
- Keine manuelle Freigabe einer wegen ausgebliebener Zahlung nicht freigegebenen Bestellung zusagen und keine nachträgliche Änderung der Zahlungsart für diese Bestellung.
- Nicht behaupten, eine Zahlungserinnerung sei verschickt worden oder werde verschickt.
- Nicht bestätigen, dass eine Überweisung bei der Bank des Kunden oder bei Mollie „unterwegs“ oder „angekommen“ ist; nur den Stand der Registrierung durch Mollie nennen.
- Eine vom Kunden genannte Referenznummer nicht bestätigen, solange sie nicht im Mollie-Dashboard zur Bestellung passt.
- Die Länge der Zahlungsfrist nicht beziffern: „einige Tage“, es sei denn, Mollie hat in der Zahlungsanweisung eine andere Frist genannt.
