> **Vertragsreferenz (PROJ-38).** Unveränderte Kopie von `looxis-productforge/docs/product-information-api.md` (Stand Commit e5e537f vom 2026-10-16). Maßgeblich bleibt das ProductForge-Repository; bei Änderungen dort diese Kopie erneuern. Einrichtung in der Service-App: `PRODUCTFORGE_URL`, `PRODUCTFORGE_TOKEN`, `PRODUCTFORGE_TIMEOUT` (siehe Spec PROJ-38).

# Produktinformations-API (v1)

Lesende, geschützte JSON-Schnittstelle von LOOXIS Content Studio (ProductForge) für die Customer Service Assist App.
Spezifikation: [features/archive/PROJ-38-produktinformationen-aus-productforge.md](../features/archive/PROJ-38-produktinformationen-aus-productforge.md).

ProductForge **liefert Daten**. Welche Inhalte für ein Anliegen relevant sind und in einen Prompt gelangen, entscheidet der Konsument. Es gibt keine KI-Auswahl, Zusammenfassung oder Analyse und keinen Bestellnummern-Endpunkt (Bestellnummern → SKUs löst die Service-App selbst über EOCS auf).

**Wichtig – Aktualität:** Die API liefert immer den **aktuell gepflegten** Stand. Es gibt keinen historischen Stand zum Bestelldatum (weder für Texte noch für Kundenservice-Wissen). Nur SKUs sind historisiert (siehe „SKU-Auflösung").

## Basis

| | |
|---|---|
| Version / Basis-Pfad | `v1` — `/api/v1` (z. B. `https://<productforge-host>/api/v1/products`) |
| Methoden | nur `GET` (alles andere → `405`) |
| Format | `application/json; charset=utf-8`; Antworten tragen `Cache-Control: no-store, private` |
| Header (empfohlen) | `Accept: application/json` |
| Schlüssel | englisch, `snake_case`; Zeitangaben ISO-8601 |

## Authentifizierung & Einrichtung

Jeder Aufruf benötigt `Authorization: Bearer <token>` — auch im internen Netzwerk. Token in URL, Query oder Body werden **nie** akzeptiert (→ `401`). Ein Token gewährt ausschließlich Zugriff auf diese lesende API.

**Einrichtung (einmalig, durch den ProductForge-Betreiber)**
1. Migration ausführen (neue Tabelle `api_tokens`, additiv): `./vendor/bin/sail artisan migrate`
2. Token erzeugen: `./vendor/bin/sail artisan api:token-erstellen "Customer Service Assist"`
   Der Klartext (`pfk_…`) wird **nur dieses eine Mal** ausgegeben und danach weder angezeigt noch protokolliert; gespeichert wird nur der SHA-256-Hash. Sicher ablegen (Secret-Store der Service-App, nicht ins Repository).
3. Token auflisten (ohne Klartext): `./vendor/bin/sail artisan api:token-liste`
4. Token widerrufen (sofort wirksam, danach `401`): `./vendor/bin/sail artisan api:token-widerrufen <id>`
   Rotation: neuen Token erzeugen, in der Service-App umstellen, alten widerrufen.

Hinweis Betrieb: Ein vorgeschalteter Reverse-Proxy muss den `Authorization`-Header unverändert weiterreichen. Es sind keine neuen `.env`-Variablen nötig.

**Rate-Limit:** 120 Anfragen pro Minute je Token; darüber `429` mit `Retry-After` (Sekunden).

## Endpunkte

### 1. Exakter Abruf per SKU — `GET /api/v1/products/by-sku?sku=<SKU>`

| Parameter | Pflicht | Beschreibung |
|---|---|---|
| `sku` | ja | 1–100 Zeichen, URL-kodiert (z. B. `/` → `%2F`) |

**SKU-Auflösung:** aktuelle **und** historische SKUs; inaktive Produkte, Varianten, Master und eigenständige Produkte sind abrufbar. Die SKU wird als **unveränderte Zeichenfolge** behandelt: kein Trimmen, kein Normalisieren — führende Nullen (`007` ≠ `7`), Groß-/Kleinschreibung, Leerzeichen und Sonderzeichen zählen. Es gibt **keinen Ersatztreffer**: unbekannt → `404 product_not_found`. Wurde eine historische SKU getroffen, steht das in `resolution` (`resolved_via: "historical_sku"`).

### 2. Abruf per Produkt-ID — `GET /api/v1/products/{id}`

`{id}`: stabile interne Produkt-ID (Ganzzahl), z. B. aus einem Suchergebnis. Liefert dieselbe Detailstruktur wie der SKU-Abruf — für Master, eigenständige Produkte, Varianten und inaktive Produkte. Unbekannt/nicht numerisch → `404`.

### 3. Produktsuche — `GET /api/v1/products?q=<Begriff>`

| Parameter | Pflicht | Standard | Beschreibung |
|---|---|---|---|
| `q` | ja | – | 2–200 Zeichen (getrimmt), mehrere Begriffe durch Leerzeichen |
| `status` | nein | `active` | `active`, `inactive` oder `all` |
| `type` | nein | alle | `standalone`, `master` oder `variant` |
| `page` | nein | `1` | ≥ 1 |
| `per_page` | nein | `20` | 1–50 (größere Werte → `422`, kein stilles Kappen) |

**Suchverhalten:** Jeder Begriff muss (UND) im **Namen**, in der **aktuellen SKU** oder in den **Keywords** (`keywords`, `keywords_ergaenzung`) des Produkts **oder** im Namen/in den Keywords seines **Masters** vorkommen (Teilstring, ohne Beachtung der Groß-/Kleinschreibung gemäß Datenbank-Collation). `%`, `_` und `\` in `q` sind normale Zeichen. Historische SKUs werden **nicht** durchsucht (dafür `by-sku`). Treffer sind die Produkte selbst — Varianten werden **nicht** zum Master zusammengefasst. Sortierung: `name`, dann `id`. Die Suche liefert nur Kandidaten (keine Inhalte); mehrdeutige Treffer werden nicht automatisch aufgelöst — nach Auswahl den vollständigen Datensatz per ID abrufen.

**Antwort:**
```json
{ "data": [ { "id": 0, "name": "", "sku": "", "type": "", "status": "", "master": { "id": 0, "name": "", "sku": "" } } ],
  "meta": { "page": 1, "per_page": 20, "total": 0, "last_page": 1 } }
```

| Feld | Typ | Bedeutung |
|---|---|---|
| `data[].id` | integer | stabile interne Produkt-ID |
| `data[].name` | string | Produktname |
| `data[].sku` | string\|null | aktuelle SKU (Master können ohne SKU existieren) |
| `data[].type` | string | `standalone` (eigenständig), `master`, `variant` |
| `data[].status` | string | `active` \| `inactive` |
| `data[].master` | object\|null | nur bei Varianten: `id`, `name`, `sku` des Masters |
| `meta.total` | integer | Gesamtzahl der Treffer; `last_page` mindestens 1 |

## Detail-Antwort (`by-sku` und `{id}`)

```
{ "data": { product, resolution, content, data_version, generated_at } }
```

| Feld | Typ | Bedeutung |
|---|---|---|
| `product.id` | integer | stabile interne Produkt-ID |
| `product.name` | string | Name |
| `product.sku` | string\|null | **aktuelle** SKU |
| `product.type` | string | `standalone` \| `master` \| `variant` |
| `product.status` | string | `active` \| `inactive` |
| `product.master` | object\|null | bei Variante: `id`, `name`, `sku`, `type`, `status` des Masters; sonst `null` (auch bei Varianten ohne zugeordneten Master) |
| `resolution.resolved_via` | string | `current_sku` \| `historical_sku` \| `id` |
| `resolution.requested_sku` | string | nur bei SKU-Abruf: die angefragte SKU, unverändert |
| `resolution.current_sku` | string\|null | nur bei SKU-Abruf: aktuelle SKU des Produkts |
| `resolution.historical_sku_valid_until` | string\|null | nur bei SKU-Abruf: Ende der Gültigkeit der historischen SKU (ISO-8601), `null` bei aktueller SKU |
| `content` | object | Inhalte (siehe unten) |
| `data_version` | string | Aktualitätskennung `sha256:<hex>` |
| `generated_at` | string | Zeitpunkt der Erzeugung der Antwort (ISO-8601) |

`product`, `content` und `data_version` sind bei SKU- und ID-Abruf **identisch**. Die gesamte Antwort ist wegen `resolution` und `generated_at` ausdrücklich **nicht** identisch.

### Aktualitätskennung `data_version`
Deterministischer SHA-256-Fingerprint über `product` **und** `content` (rekursiv sortierte Schlüssel, JSON). Er erfasst jede Änderung an Identität (ID, Name, aktueller SKU, Typ, Status, Master) und Inhalten — einschließlich eigener Texte, Master-Inhalten, Amazon→Shop-Sync, Bullet Points, Kundenservice-Wissen und dessen Wissensursprung — und bleibt bei unveränderten Daten stabil (`resolution` und `generated_at` zählen nicht mit). Geeignet zum Cache-Vergleich; keine Inhalts-Historie.

### Inhalte (`content`)

**Textfelder mit Vererbung — Objekt `{ own, from_master, combined }`** (alle `string|null`):

| Schlüssel | Quelle (Produktinhalte) |
|---|---|
| `technical_data` | Technische Daten |
| `included_in_delivery` | Im Lieferumfang |
| `care_instructions` | Pflegehinweise |
| `warnings` | Warnhinweise |

- **Master / eigenständiges Produkt:** `own` = eigener Wert, `from_master` = **immer** `null`, `combined` = `own`.
- **Variante:** `from_master` = Wert des Masters (schreibgeschützt geerbt), `own` = **Ergänzung** der Variante (nicht deren „Ersetzung" — Master-Inhalte bleiben immer erhalten), `combined` = `from_master`, Leerzeile (`"\n\n"`), `own` (leere Teile entfallen; beide leer → `null`). Das ist dieselbe Konvention wie beim automatischen Zusammenführen in ProductForge.
- Es gilt **nicht** für alle Felder dieselbe Vererbung — jede Feldgruppe unten beschreibt ihre eigene Regel.
- Format: **Klartext** mit Zeilenumbrüchen (`\n`), kein HTML; Technische Daten sind per Konvention eine semikolon-getrennte Liste. Der Konsument soll die Texte als Text behandeln und nicht als HTML rendern.

**`description`** — Beschreibung (Klartext). Nur Deutsch (ProductForge ist einsprachig; keine Sprachspalte), je Kanal getrennt:
```
description: { language: "de", format: "plain_text",
  shop:   { own, from_master, combined, mirrors_amazon: bool },
  amazon: { own, from_master, combined } }
```
- `amazon` folgt der Textfeld-Regel oben (Master: eigener Amazon-Text; Variante: Master-Amazon-Text + Ergänzung).
- `shop`: **Amazon→Shop-Synchronisierung.** Ist am Master/eigenständigen Produkt der Schalter „Beschreibung von Amazon übernehmen" aktiv, ist der wirksame Shop-Text der Amazon-Text. Das gilt einheitlich: beim direkten Abruf eines Masters/eigenständigen Produkts (`own` ist dann der Amazon-Text) und bei Varianten (`from_master` ist der wirksame Shop-Text des Masters, `own` die Shop-Ergänzung der Variante). `mirrors_amazon` weist den Schalter explizit aus (bei Varianten: Schalter des Masters).
- **Kein stiller Kanalwechsel:** Fehlt ein Text, bleibt er `null` — der API-Konsument bekommt nie ungefragt den Text des anderen Kanals, außer die Synchronisierung ist ausdrücklich aktiv (`mirrors_amazon: true`).

**`bullet_points`**
```
bullet_points: { format: { shop: "text; only <b></b> markup allowed", amazon: "plain_text" },
  positions: [ { position, variable, shop, amazon, shop_source, amazon_source } ] }
```
- Geordnete Liste (`position` ab 1, maximal 8); beide Kanäle teilen sich die Position (Shop und Amazon bleiben je Position getrennte Felder). Shop erlaubt nur `<b>…</b>`, Amazon ist HTML-frei.
- `variable` (boolean): Position darf bei Varianten überschrieben werden.
- `shop` / `amazon` (`string|null`): **wirksamer** Text. Bei Varianten: Mastertext, an `variable`-Positionen überlagert durch den Text der Variante.
- `shop_source` / `amazon_source`: `own` (Master/eigenständig), `master` (Variante nutzt Mastertext) oder `variant` (Variante überschreibt). Keine Liste vorhanden → `positions: []`.

**`customer_service_knowledge`** — wirksames Kundenservice-Wissen (PROJ-35, zentrale Auflösung, nicht neu implementiert):

| Feld | Typ | Bedeutung |
|---|---|---|
| `combined` | string\|null | Ursprungstext, Leerzeile, eigener Text (leere Teile entfallen) |
| `origin_text` | string\|null | vollständig übernommener Text des Wissensursprungs (nur wenn der Halter von einem anderen Produkt übernimmt) |
| `own_text` | string\|null | eigener Text bzw. **Ergänzung** des Halters (ersetzt den Ursprungstext nie) |
| `origin` | object\|null | Ursprungsprodukt `{id, name, sku, type, status}` |
| `holder` | object\|null | Produkt, an dem das Wissen gepflegt wird `{id, name, sku, type, status}` |
| `inherited_from_master` | boolean | `true` bei Varianten (erben das gesamte Wissen ihres Masters) |
| `holder_is_successor` | boolean | `true`, wenn der Master aufgelöst wurde (letzte aktive Variante wurde eigenständig): das Wissen liegt dann bei diesem **Nachfolger** — auch für den inaktiven Master und seine inaktiven Varianten (ältere Bestellungen per SKU) |

Regeln: Varianten haben kein eigenes Wissen. Ein Master/eigenständiges Produkt hat optional eigenen Text und höchstens einen Ursprung; Ursprungsketten gibt es nicht. Der Verweis wird bei jedem Abruf live aufgelöst (keine Kopien) — Änderungen am Ursprung sind im nächsten Abruf sichtbar. Fehlendes Wissen: alle Texte `null`, `origin` `null` — die übrigen Produktdaten bleiben vollständig nutzbar.

### Leere Werte
Textfelder sind **`null`**, nie `""` (nur Leerzeichen zählt als leer). Listen (`positions`) sind `[]`. Objekte (`master`, `origin`, `holder`) sind `null`.

### Inaktive Varianten nach Master-Rückstufung
Wird ein Master aufgelöst (nur noch eine aktive Variante → eigenständig), behalten die inaktiven früheren Varianten ihre eigenen Ergänzungen und lösen die Master-Inhalte weiterhin vom (inaktiven) Master auf — Stand zum Zeitpunkt der Auflösung, ohne spätere Änderungen am Nachfolger. Ausnahme ist das Kundenservice-Wissen: dort folgt die Auflösung dem Nachfolger und liefert den aktuellen Stand. Inhalte anderer Varianten werden nie übernommen.

### Nicht enthalten
Preise, Einkaufspreise, GTIN, EOCS-/Amazon-Daten, Fotos/Dateien, Plus-/A+-Content, Zugangsdaten.

## Fehler

Einheitliche Form: `{ "error": { "code": "…", "message": "…", "details": { … } } }` (`details` nur bei `422`, je Parameter eine Liste von Meldungen).

| Status | `code` | Wann |
|---|---|---|
| 401 | `unauthenticated` | Token fehlt, ungültig, widerrufen oder nicht per `Authorization: Bearer` übermittelt (Header `WWW-Authenticate: Bearer`) |
| 404 | `product_not_found` | unbekannte/nicht numerische ID, unbekannte SKU (auch ohne Ersatztreffer) |
| 404 | `not_found` | unbekannter Endpunkt |
| 405 | `method_not_allowed` | andere Methode als `GET` |
| 422 | `validation_failed` | fehlender/ungültiger Parameter |
| 429 | `rate_limited` | Limit überschritten (`Retry-After`) |
| 500 | `server_error` | interner Fehler (ohne Details) |

## Beispielaufrufe

```bash
BASE=https://<productforge-host>/api/v1
TOKEN=<TOKEN>   # nie in die URL!

# exakter SKU-Abruf (SKU URL-kodiert)
curl -s -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" \
  "$BASE/products/by-sku?sku=DEMO-ROUND-16"

# Abruf per ID
curl -s -H "Authorization: Bearer $TOKEN" "$BASE/products/1002"

# Suche, auch inaktive, zweite Seite à 10
curl -s -H "Authorization: Bearer $TOKEN" \
  "$BASE/products?q=Demo%20Tortenaufleger&status=all&per_page=10&page=2"
```

## Beispiele (ausdrücklich **fiktiv**, keine echten Produktdaten)

### A) Variante mit Master-Inhalten und externem Wissensursprung
`GET /api/v1/products/by-sku?sku=DEMO-SQUARE-16`
```json
{
  "data": {
    "product": {
      "id": 2002, "name": "Demo Tortenaufleger eckig 16 cm", "sku": "DEMO-SQUARE-16",
      "type": "variant", "status": "active",
      "master": { "id": 2001, "name": "Demo Tortenaufleger eckig", "sku": "DEMO-SQUARE", "type": "master", "status": "active" }
    },
    "resolution": { "resolved_via": "current_sku", "requested_sku": "DEMO-SQUARE-16", "current_sku": "DEMO-SQUARE-16", "historical_sku_valid_until": null },
    "content": {
      "technical_data": { "own": "Format: 16 x 16 cm", "from_master": "Material: Beispiel-Zuckerpapier; Dicke: 0,3 mm", "combined": "Material: Beispiel-Zuckerpapier; Dicke: 0,3 mm\n\nFormat: 16 x 16 cm" },
      "included_in_delivery": { "own": null, "from_master": "1 Aufleger", "combined": "1 Aufleger" },
      "care_instructions": { "own": null, "from_master": null, "combined": null },
      "warnings": { "own": null, "from_master": "Trocken und lichtgeschützt lagern.", "combined": "Trocken und lichtgeschützt lagern." },
      "description": {
        "language": "de", "format": "plain_text",
        "shop": { "own": "Jetzt in 16 cm.", "from_master": "Beispielbeschreibung für den Shop.", "combined": "Beispielbeschreibung für den Shop.\n\nJetzt in 16 cm.", "mirrors_amazon": false },
        "amazon": { "own": null, "from_master": "Beispielbeschreibung für Amazon.", "combined": "Beispielbeschreibung für Amazon." }
      },
      "bullet_points": {
        "format": { "shop": "text; only <b></b> markup allowed", "amazon": "plain_text" },
        "positions": [
          { "position": 1, "variable": false, "shop": "<b>Beispiel</b>-Vorteil", "amazon": "Beispiel-Vorteil", "shop_source": "master", "amazon_source": "master" },
          { "position": 2, "variable": true, "shop": "Passend für 16 cm", "amazon": "Passend für 16 cm", "shop_source": "variant", "amazon_source": "variant" }
        ]
      },
      "customer_service_knowledge": {
        "combined": "Beispieltext des Ursprungs (fiktiv).\n\nErgänzung zum eckigen Produkt (fiktiv).",
        "origin_text": "Beispieltext des Ursprungs (fiktiv).",
        "own_text": "Ergänzung zum eckigen Produkt (fiktiv).",
        "origin": { "id": 2010, "name": "Demo Tortenaufleger rund", "sku": "DEMO-ROUND", "type": "master", "status": "active" },
        "holder": { "id": 2001, "name": "Demo Tortenaufleger eckig", "sku": "DEMO-SQUARE", "type": "master", "status": "active" },
        "inherited_from_master": true,
        "holder_is_successor": false
      }
    },
    "data_version": "sha256:3f1c…(fiktiv gekürzt)",
    "generated_at": "2026-10-17T09:30:00+00:00"
  }
}
```

### B) Produkt mit leeren Feldern (kein Kundenservice-Wissen)
`GET /api/v1/products/3001`
```json
{
  "data": {
    "product": { "id": 3001, "name": "Demo Artikel leer", "sku": "DEMO-EMPTY", "type": "standalone", "status": "active", "master": null },
    "resolution": { "resolved_via": "id" },
    "content": {
      "technical_data": { "own": null, "from_master": null, "combined": null },
      "included_in_delivery": { "own": null, "from_master": null, "combined": null },
      "care_instructions": { "own": null, "from_master": null, "combined": null },
      "warnings": { "own": null, "from_master": null, "combined": null },
      "description": {
        "language": "de", "format": "plain_text",
        "shop": { "own": null, "from_master": null, "combined": null, "mirrors_amazon": false },
        "amazon": { "own": null, "from_master": null, "combined": null }
      },
      "bullet_points": { "format": { "shop": "text; only <b></b> markup allowed", "amazon": "plain_text" }, "positions": [] },
      "customer_service_knowledge": {
        "combined": null, "origin_text": null, "own_text": null, "origin": null,
        "holder": { "id": 3001, "name": "Demo Artikel leer", "sku": "DEMO-EMPTY", "type": "standalone", "status": "active" },
        "inherited_from_master": false, "holder_is_successor": false
      }
    },
    "data_version": "sha256:9b7e…(fiktiv gekürzt)",
    "generated_at": "2026-10-17T09:31:00+00:00"
  }
}
```

### C) Suchergebnis
`GET /api/v1/products?q=Demo%20Tortenaufleger&per_page=2`
```json
{
  "data": [
    { "id": 2001, "name": "Demo Tortenaufleger eckig", "sku": "DEMO-SQUARE", "type": "master", "status": "active", "master": null },
    { "id": 2002, "name": "Demo Tortenaufleger eckig 16 cm", "sku": "DEMO-SQUARE-16", "type": "variant", "status": "active",
      "master": { "id": 2001, "name": "Demo Tortenaufleger eckig", "sku": "DEMO-SQUARE" } }
  ],
  "meta": { "page": 1, "per_page": 2, "total": 5, "last_page": 3 }
}
```

### D) Historische SKU eines inaktiven Produkts
`GET /api/v1/products/by-sku?sku=DEMO-OLD-1` (Auszug; `content` wie oben)
```json
{
  "data": {
    "product": { "id": 4001, "name": "Demo Altprodukt", "sku": "DEMO-NEW-1", "type": "standalone", "status": "inactive", "master": null },
    "resolution": { "resolved_via": "historical_sku", "requested_sku": "DEMO-OLD-1", "current_sku": "DEMO-NEW-1", "historical_sku_valid_until": "2026-03-01T08:00:00+00:00" },
    "content": { "…": "siehe Beispiel A/B" },
    "data_version": "sha256:c0de…(fiktiv gekürzt)",
    "generated_at": "2026-10-17T09:32:00+00:00"
  }
}
```

### Fehlerbeispiele
```json
{ "error": { "code": "unauthenticated", "message": "Fehlender oder ungültiger Access Token (Authorization: Bearer <token>)." } }
{ "error": { "code": "product_not_found", "message": "Kein Produkt mit dieser SKU (aktuell oder historisch) gefunden." } }
{ "error": { "code": "validation_failed", "message": "Ungültige Parameter.", "details": { "per_page": ["The per page field must not be greater than 50."] } } }
```
(Die Texte in `message`/`details` sind für Menschen gedacht — für Programmlogik nur `error.code` und den HTTP-Status verwenden.)

## Empfohlener Ablauf für die Service-App
1. Bestellposition → SKU (über EOCS) → `GET /products/by-sku` (auch ältere/inaktive/umbenannte SKUs).
2. Ohne SKU: `GET /products?q=…` → Kandidaten anzeigen/auswählen → `GET /products/{id}`.
3. `data_version` zum Wiedererkennen unveränderter Daten verwenden; Daten spiegeln stets den aktuellen Stand.
