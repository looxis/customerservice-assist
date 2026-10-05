# Orders API (EOCS)

Lesender Zugriff auf Bestellungen in EOCS, wie ihn Customer Service Assist nutzt (PROJ-7). Stand: Tests gegen `https://eocs.loox.is` am 2026-10-05. Ergänzt die [Items API](eocs-items-api.md).

Die API ist nur aus dem internen Netz erreichbar.

## Authentifizierung

Jede Anfrage trägt einen Bearer-Token im Header:

```http
Authorization: Bearer DEIN_TOKEN
Accept: application/json
```

- Ohne oder mit falschem Token: `401 Unauthorized`.
- In der App: `EOCS_URL` (ohne `/api/v1`), `EOCS_TOKEN`, `EOCS_TIMEOUT` in der `.env`. Der Token enthält ein `|` und muss deshalb in geraden Anführungszeichen stehen: `EOCS_TOKEN="7|…"`.

## Bestellungen suchen

```http
GET /api/v1/orders?filter[external_order_id]={nummer},exact&include=shipments,order_items
```

Beispiel:

```bash
curl --globoff \
  -H "Accept: application/json" \
  -H "Authorization: Bearer DEIN_TOKEN" \
  "https://eocs.loox.is/api/v1/orders?filter[external_order_id]=402-0000000-0000001%2Cexact&include=shipments,order_items"
```

- Die Antwort ist immer eine Liste unter `data`, auch bei genau einem Treffer. Eine unbekannte Nummer liefert `200` mit leerer Liste.
- **`,exact`** sucht genau diese Nummer. **Ohne `,exact`** findet die Suche zusätzlich Folgeaufträge, deren Nummer die gesuchte enthält, z. B. Reklamationsaufträge `R1-402-0000000-0000001` (siehe unten).
- Präfixe wie `BEST-PRO` gehören nicht zur Nummer: `BEST-PRO30019578` wird nicht gefunden, `30019578` schon.

### Erlaubte Filter

`external_order_id`, `id`, `claim`, `statuses`, `tags`, `pro`, `individual`, `too_late`, `stocked`, `name`, `merchant`, `ordered_at`, `created_at`, `producible`.

Ein nicht erlaubter Filter liefert `400` mit dieser Liste. Nach `ticket_number` lässt sich **nicht** filtern.

### Suche über die EOCS-ID

```http
GET /api/v1/orders?filter[id]=753194
```

Die EOCS-ID ist die interne, sechsstellige Nummer (z. B. auf Barcode-Labels). `GET /api/v1/orders/{id}` antwortet mit `401` und wird nicht genutzt.

### Includes

| Include | Inhalt |
|---|---|
| `shipments` | Sendungen mit Dienstleister und Sendungsnummer |
| `order_items` | Positionen mit Artikel, Anzahl, Status, Konfiguration, Personalisierung |
| `notes` | Notizen zur Bestellung |

Unbekannte Includes werden ohne Fehler ignoriert.

## Aufbau einer Bestellung

Gekürzt, erfundene Werte:

```json
{
  "id": 700001,
  "external_order_id": "402-0000000-0000001",
  "order_date": "2026-09-09T13:06:53.000000Z",
  "production_release": "2026-09-09T13:30:00.000000Z",
  "status": 5,
  "status_name": "Vollständig und verschickt",
  "state": { "name": "Complete", "color": "green", "icon": "far fa-check-circle" },
  "ordered_at": "Amazon.it",
  "client": { "id": 106, "name": "Amazon.it", "sales_platform": { "id": 7, "name": "Amazon" } },
  "locale": "en",
  "origin_order_id": null,
  "ticket_number": null,
  "claim_description": null,
  "name": "…",
  "customer_email": "…",
  "shipping": { "first_name": "…", "last_name": "…", "street": "…", "zipcode": "…", "city": "…", "country_code": "IT", "telephone": "…" },
  "billing": { "paid": true, "currency": "EUR", "total": 24.9, "payment_method": "amazon" },
  "invoice": { "id": 500001, "document_number": "LX26-00001" },
  "shipments": { "data": [ {
    "id": 600001,
    "carrier": { "name": "DHL", "short_name": "DHL" },
    "shipping_method": { "name": "DHL PAKET" },
    "tracking_no": "00340000000000000000",
    "created_at": "10.09.2026 11:54",
    "delivered": null
  } ] },
  "order_items": { "data": [ {
    "id": 800001,
    "position": 1,
    "quantity": 1,
    "status_name": "Vollständig und verschickt",
    "shop": "Amazon_it",
    "custom_code": "…",
    "custom_code_url": "https://v5.cnfgrtr.com/…",
    "external_order_item_id": "…",
    "item": { "item_id": 11281, "name": "Fototasse Schwarz", "product_type": "…", "individual": true, "configurations": "…" },
    "data": { "asin": "B000000000", "customizationData": "…", "customizationInfo": "…" }
  } ] }
}
```

Wichtige Felder für die App:

| Feld | Bedeutung |
|---|---|
| `id` | EOCS-ID; Bestellung in der Oberfläche: `https://eocs.loox.is/orders/{id}` |
| `external_order_id` | Bestellnummer des Kanals |
| `client.name`, `client.sales_platform.name` | Kanal (siehe unten) |
| `status_name`, `state.color` | Status in Worten und als Farbe |
| `invoice.document_number` | Rechnungsnummer |
| `shipments.data[]` | Dienstleister, Sendungsnummer, Versanddatum, zugestellt |
| `order_items.data[].item.item_id` | Artikelnummer; entspricht den `order_keywords` der Produktdateien in der Knowledge Base |
| `order_items.data[].custom_code`, `custom_code_url` | Konfigurations-ID und Link zum Konfigurator |
| `origin_order_id` | Bei Folgeaufträgen (Reklamation) die EOCS-ID der Original-Bestellung |
| `ticket_number` | Wird von Agents manchmal bei Reklamationen eingetragen |

`name`, `customer_email`, `shipping`, `billing` enthalten Kundendaten. Die App zeigt sie nicht im Ticketkopf an und schreibt sie nie ins Log.

## Kanäle

| Kanal | `client.name` | `client.sales_platform.name` | Format der externen Nummer |
|---|---|---|---|
| Shop Endverbraucher (looxis.de) | `looxis.de Vanilo` | `Looxis Vanilo Cloud` | `7JI-0WC1-6M49` (3-4-4, Großbuchstaben und Ziffern) |
| Fachhändler DE | `fachhaendler.looxis.de` | `Looxis Vanilo Cloud` | `7JI-0YFS-C1M2` (wie Shop) |
| Fachhändler FR | `reseller.looxis.fr` | `Looxis.fr Magento` | `700411247` (9 Ziffern, beginnt mit 700) |
| LOOXIS-Pro | `looxis-pro.com` | `Looxis-Pro.com` | `30019578` (8 Ziffern, beginnt mit 300) |
| masterpics (White Label) | `Masterpics White Label DE` | `Looxis Vanilo Cloud` | `LmagQ9PV25` (10 Zeichen, Groß-/Kleinbuchstaben, Ziffern) |
| Amazon | `Amazon.de`, `Amazon.it`, … | `Amazon` | `402-4907715-1581912` (3-7-7 Ziffern) |

Shop und Fachhändler DE haben dasselbe Nummernformat; unterscheiden lassen sie sich erst über `client.name`.

## Reklamationsaufträge

Eine Reklamation wird in EOCS als neuer Auftrag angelegt:

- externe Nummer mit Präfix `R1-`, `R2-`, … vor der Original-Nummer, z. B. `R1-402-0000000-0000001`;
- `origin_order_id` verweist auf die EOCS-ID der Original-Bestellung;
- `ticket_number` enthält manchmal die Zammad-Ticketnummer.

Finden lassen sie sich über die Suche **ohne** `,exact` mit der Original-Nummer.

## Fehler und Antwortzeiten

| Fall | Antwort |
|---|---|
| Treffer | `200`, `data` mit Bestellungen |
| Unbekannte Nummer | `200`, leere `data` |
| Token fehlt oder falsch | `401` |
| Nicht erlaubter Filter | `400` mit Liste der erlaubten Filter |
| Antwortzeit (Suche über externe Nummer) | unter 0,3 s |
