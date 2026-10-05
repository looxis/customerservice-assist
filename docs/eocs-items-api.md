# Items API

Die Items API stellt Items fuer interne Integrationen wie n8n bereit. Die API verwendet Laravel `JsonResource` und Sanctum-Personal-Access-Tokens.

## Authentifizierung

Die API erwartet einen Sanctum-Bearer-Token. Fuer n8n sollte ein eigenes `Device`-Model verwendet werden, damit die bestehenden Passport-Tokens der User unveraendert bleiben.

In Tinker:

```bash
php artisan tinker
```

```php
$device = App\Models\Device::create([
    'name' => 'n8n Inhouse Server',
    'type' => App\Models\Device::class,
    'description' => 'Sanctum API client for n8n',
]);

$token = $device->createToken('n8n-items-api', [
    'items:read',
    'items:inventory',
    'items:write',
]);

$token->plainTextToken;
```

Der Token wird nur bei der Erstellung im Klartext ausgegeben. In Requests wird er als Bearer-Token verwendet:

```http
Authorization: Bearer DEIN_TOKEN
Accept: application/json
```

## Items auflisten

```http
GET /api/v1/items
```

Beispiel:

```bash
curl -H "Accept: application/json" \
  -H "Authorization: Bearer DEIN_TOKEN" \
  "https://deine-domain.tld/api/v1/items?paginate=25&sort=name"
```

Die Liste ist paginiert. Standardmaessig werden aktive und veroeffentlichte Items geliefert. Die Antwort verwendet Laravels native Pagination mit `data`, `links` und `meta`.

## Einzelnes Item laden

```http
GET /api/v1/items/{id}
```

`id` ist die interne Datenbank-ID des Items.

```bash
curl -H "Accept: application/json" \
  -H "Authorization: Bearer DEIN_TOKEN" \
  "https://deine-domain.tld/api/v1/items/123"
```

## Filter und Sortierung

Die Filter werden als `filter[...]` Query-Parameter angegeben:

```http
GET /api/v1/items?filter[item_id]=12345
GET /api/v1/items?filter[gtin]=04012345678901
GET /api/v1/items?filter[name]=Laser
GET /api/v1/items?filter[group]=laser_photos
GET /api/v1/items?filter[type]=3D%20Laser%20Fotos
GET /api/v1/items?filter[individual]=1
GET /api/v1/items?filter[conversionable]=1
GET /api/v1/items?filter[automatic_conversion]=1
```

Erlaubte Sortierfelder sind `name`, `item_id`, `created_at` und `updated_at`. Ein vorangestelltes Minus sortiert absteigend:

```http
GET /api/v1/items?sort=-name&paginate=50
```

`paginate` darf zwischen 1 und 100 liegen.

## Optionale Includes

Produktart und Produktgruppe werden standardmaessig ausgegeben. Weitere Relationen koennen mit `include` angefordert werden:

```http
GET /api/v1/items?include=tags
```

Fuer sensible oder groessere Relationen sind zusaetzliche Abilities erforderlich:

| Include | Erforderliche Ability |
| --- | --- |
| `tags` | `items:read` |
| `depots` | `items:inventory` |
| `inventoryItems` | `items:inventory` |
| `priceListItems` | `items:prices` |
| `suppliers` | `items:suppliers` |
| `parts` | `items:relations` |
| `parents` | `items:relations` |
| `master_products` | `items:relations` |
| `salesChannels` | `items:relations` |
| `orderItems` | `items:relations` |

Mehrere Includes werden kommasepariert angegeben:

```http
GET /api/v1/items?include=tags,depots,master_products
```

## Item aktualisieren

Zum Schreiben wird die Ability `items:write` benoetigt:

```http
PATCH /api/v1/items/{id}
```

Aktuell duerfen ausschliesslich `description` und `short_description` geaendert werden:

```bash
curl -X PATCH \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer DEIN_TOKEN" \
  -d '{
    "description": "Neue Beschreibung aus n8n",
    "short_description": "Neue Kurzbeschreibung aus n8n"
  }' \
  "https://deine-domain.tld/api/v1/items/123"
```

Ein einzelnes Feld kann aktualisiert werden. `null` leert das Feld:

```json
{
  "description": null
}
```

Ein leerer Request-Body wird mit `422 Unprocessable Entity` abgelehnt.

## Abilities

| Ability | Zweck |
| --- | --- |
| `items:read` | Items auflisten und einzelne Items laden |
| `items:write` | `description` und `short_description` aktualisieren |
| `items:inventory` | `depots` und `inventoryItems` laden |
| `items:prices` | `priceListItems` laden |
| `items:suppliers` | `suppliers` laden |
| `items:relations` | Teile, Eltern, Sales Channels und Order Items laden |

Ohne Authentifizierung antwortet die API mit `401 Unauthorized`. Fehlt eine erforderliche Ability, antwortet sie mit `403 Forbidden`.
