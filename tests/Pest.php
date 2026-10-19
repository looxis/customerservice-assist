<?php

use App\Knowledge\KnowledgeIssue;
use App\Knowledge\KnowledgeLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Build a throwaway knowledge base and return the library reading it.
 *
 * @param  array<string, string>  $files  Relative path => file content.
 */
function knowledgeBase(array $files): KnowledgeLibrary
{
    $root = sys_get_temp_dir().'/knowledge-test-'.bin2hex(random_bytes(6));

    File::ensureDirectoryExists($root);

    foreach ($files as $path => $content) {
        File::ensureDirectoryExists(dirname("{$root}/{$path}"));
        File::put("{$root}/{$path}", $content);
    }

    config(['knowledge.path' => $root]);
    app()->forgetScopedInstances();

    return app(KnowledgeLibrary::class);
}

/**
 * A valid knowledge file; overrides change or remove (null) frontmatter fields.
 *
 * @param  array<string, mixed>  $overrides
 */
function knowledgeDoc(array $overrides = [], string $body = "Gilt für alle Kundenarten.\n\n# Regel\n\nEin Satz."): string
{
    $frontmatter = array_merge(['id' => 'POLICY-001', 'title' => 'Titel', 'type' => 'policy', 'status' => 'draft'], $overrides);

    return "---\n".Yaml::dump($frontmatter)."---\n\n".$body."\n";
}

function messagesOf(KnowledgeLibrary $library, string $severity): string
{
    return ($severity === 'error' ? $library->errors() : $library->warnings())
        ->map(fn (KnowledgeIssue $issue): string => "{$issue->path}: {$issue->message}")
        ->implode("\n");
}

/**
 * Remove the throwaway knowledge bases created by knowledgeBase().
 */
function cleanUpKnowledgeBases(): void
{
    foreach (File::glob(sys_get_temp_dir().'/knowledge-test-*') as $directory) {
        File::deleteDirectory($directory);
    }
}

/**
 * @return array<string, mixed>
 */
function zammadArticle(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'sender' => 'Customer',
        'type' => 'email',
        'internal' => false,
        'from' => 'Erika Beispiel <erika@example.org>',
        'content_type' => 'text/html',
        'body' => '<p>Das Motiv erscheint nicht.</p>',
        'created_at' => '2026-10-01T07:12:00.000Z',
        'attachments' => [],
    ], $overrides);
}

/**
 * Fake Zammad with one ticket.
 *
 * @param  list<array<string, mixed>>  $articles
 */
function fakeZammad(array $articles = [], array $ticket = [], array $extra = []): void
{
    $ticket = array_merge([
        'id' => 51234,
        'number' => '2137942',
        'title' => 'Zaubertasse – Motiv erscheint nicht',
        'state' => 'open',
        'group' => 'Kundenservice',
        'customer_id' => 77,
        'created_at' => '2026-10-01T07:12:00.000Z',
    ], $ticket);

    Http::preventStrayRequests();
    Http::fake(array_merge([
        'zammad.test/api/v1/tickets/search*' => Http::response([$ticket]),
        'zammad.test/api/v1/ticket_articles/by_ticket/*' => Http::response($articles === [] ? [zammadArticle()] : $articles),
        'zammad.test/api/v1/links*' => Http::response(['links' => [], 'assets' => []]),
        'zammad.test/api/v1/users/77' => Http::response(['firstname' => 'Erika', 'lastname' => 'Beispiel', 'email' => 'erika@example.org']),
    ], $extra));
}

/**
 * A fictitious Amazon buyer-message notice in Amazon's table layout.
 */
function amazonNotice(string $message, string $order = '402-0000000-0000001', array $products = [['B000TEST01', 'Zaubertasse schwarz']]): string
{
    $rows = implode('', array_map(fn (array $product, int $index): string => '<tr><td> '.($index + 1).' </td><td> '.$product[0].' </td><td> '.$product[1].' </td></tr>', $products, array_keys($products)));

    return '<center><table><tr><td><table><tr><td>'
        .'<p>Du hast eine Nachricht erhalten.</p>'
        .'<p>Bestellnummer '.$order.':</p>'
        .'<table><tbody><tr><td># </td><td>ASIN </td><td>Produktname </td></tr>'.$rows.'</tbody></table>'
        .'<h4><strong>Nachricht:</strong></h4>'
        .'<table><tr><th><pre>'.$message.'</pre></th></tr></table>'
        .'<table><tr><td><a href="https://sellercentral.amazon.it/nms/redirect/x">Fall lösen</a></td></tr></table>'
        .'<p>Dieser Service wird ausschließlich für die Kommunikation mit Käufern angeboten.</p>'
        .'</td></tr></table></td></tr></table></center>';
}

/**
 * The product answer of the proposal form (PROJ-42): a test that passes a product or a typed name does not
 * also say "no product" (the default of the helpers).
 *
 * @param  array<string, mixed>  $fields
 * @return array<string, mixed>
 */
function withProductAnswer(array $fields): array
{
    if (! empty($fields['produkte']) || ! empty($fields['produkt_ohne_wissen'])) {
        unset($fields['kein_produktbezug']);
    }

    return $fields;
}

/**
 * The configuration of the product and group fields of the proposal form (PROJ-42) as the page hands it to Alpine.
 *
 * @return array{products: list<array<string, mixed>>, selected: list<string>, reasons: array<string, list<string>>, mentioned: list<string>, unknown: string, none: bool, group: string}
 */
function scopeConfig(string $html): array
{
    preg_match('/x-data="scopeFields\((.*?)\)"\s+class=/s', $html, $match);

    $expression = html_entity_decode($match[1] ?? '', ENT_QUOTES | ENT_HTML5);

    if (preg_match("/^JSON\.parse\('(.*)'\)$/s", $expression, $json) === 1) {
        $expression = str_replace(['\\u0022', '\\u0027'], ['"', "'"], $json[1]);
    }

    return json_decode($expression, true) ?? [];
}

/**
 * The page plus a plain-text rendering of the preselected product chips (the chips themselves are drawn
 * by Alpine in the browser): `value="slug" checked (reason · reason)` for each selected product.
 */
function withChips(string $html): string
{
    $config = scopeConfig($html);

    foreach ($config['selected'] ?? [] as $slug) {
        $reasons = $config['reasons'][$slug] ?? [];
        $html .= "\n".'<input value="'.$slug.'" checked>'.($reasons === [] ? '' : ' ('.implode(' · ', $reasons).')');
    }

    return $html;
}

/**
 * A stored response of the ProductForge product API (PROJ-38), built after docs/productforge-product-api.md.
 *
 * @return array<string, mixed>
 */
function forgeFixture(string $name): array
{
    return json_decode((string) file_get_contents(base_path("tests/Fixtures/productforge/{$name}.json")), true, flags: JSON_THROW_ON_ERROR);
}

/**
 * Switches the ProductForge connection on and fakes its API: details by SKU and by id (both keyed by the
 * request value), and the search answer. Anything else is "not found" like the real API.
 *
 * @param  array<string, array<string, mixed>>  $bySku
 * @param  array<int, array<string, mixed>>  $byId
 * @param  array<string, mixed>|null  $search
 */
function fakeForge(array $bySku = [], array $byId = [], ?array $search = null): void
{
    static $state = null;
    static $registeredOn = null;

    $state ??= new ArrayObject;
    $state->exchangeArray(['bySku' => $bySku, 'byId' => $byId, 'search' => $search]);

    config(['services.productforge' => ['url' => 'https://forge.test', 'token' => 'pf-secret-token', 'timeout' => 5, 'retries' => 2, 'order_lookup' => false]]);

    // The stub is registered once per test; later calls only change what it answers.
    $http = Http::getFacadeRoot();

    if ($registeredOn === $http) {
        return;
    }

    $registeredOn = $http;

    Http::fake(['forge.test/*' => function (Request $request) use ($state) {
        $path = (string) parse_url($request->url(), PHP_URL_PATH);
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
        $missing = Http::response(['error' => ['code' => 'product_not_found', 'message' => 'nicht gefunden']], 404);

        if ($path === '/api/v1/products/by-sku') {
            $found = $state['bySku'][(string) ($query['sku'] ?? '')] ?? null;

            return $found === null ? $missing : Http::response($found);
        }

        if (preg_match('#^/api/v1/products/(\d+)$#', $path, $match) === 1) {
            $found = $state['byId'][(int) $match[1]] ?? null;

            return $found === null ? $missing : Http::response($found);
        }

        if ($path === '/api/v1/products') {
            return Http::response($state['search'] ?? ['data' => [], 'meta' => ['page' => 1, 'per_page' => 20, 'total' => 0, 'last_page' => 1]]);
        }

        return Http::response(['error' => ['code' => 'not_found', 'message' => 'x']], 404);
    }]);
}

/**
 * The number of requests that went to the (faked) ProductForge host.
 */
function forgeRequests(): int
{
    return Http::recorded(fn (Request $request): bool => str_contains($request->url(), 'forge.test'))->count();
}

/**
 * An item of the EOCS items API (docs/items-api.md) as far as the app reads it.
 *
 * @return array<string, mixed>
 */
function eocsItem(int $number, string $name = 'Demo Leuchtsockel', ?string $description = "Lange Beschreibung\nzweite Zeile", ?string $short = '💡 <b>Magische Beleuchtung</b> – 8 Farben'): array
{
    return ['id' => $number + 1000, 'item_id' => $number, 'name' => $name, 'description' => $description, 'short_description' => $short, 'deactivated_at' => null, 'product_type' => ['id' => 1, 'name' => 'Leuchtsockel']];
}

/**
 * A variant row as EOCS delivers it inside "variants".
 *
 * @return array<string, mixed>
 */
function variantRow(int $sku, string $name, bool $active = true): array
{
    return ['id' => $sku + 1000, 'item_id' => $sku, 'name' => $name, 'deactivated_at' => $active ? null : '2026-01-01 00:00:00'];
}

/**
 * An item of EOCS that is a variant of the master "Essbares Foto, Rund" (id 1625) with the given siblings.
 *
 * @param  list<array<string, mixed>>|null  $siblings
 * @return array<string, mixed>
 */
function variantItem(int $sku = 11235, string $name = 'Essbares Foto, Rund 16 cm', ?array $siblings = null): array
{
    $siblings ??= [variantRow(11235, 'Essbares Foto, Rund 16 cm'), variantRow(11747, 'Essbares Foto, Rund 18 cm'), variantRow(11748, 'Essbares Foto, Rund 20 cm')];

    return [...eocsItem($sku, $name), 'is_master' => false, 'master_products' => [['id' => 1625, 'name' => 'Essbares Foto, Rund', 'is_master' => true, 'variants' => $siblings]]];
}

/**
 * Fakes the EOCS items API: items keyed by number; "filter[item_id]" is exact, "filter[name]" a part of the name.
 *
 * @param  array<int, array<string, mixed>>  $items
 */
function fakeEocsItems(array $items = [], int $status = 200): void
{
    static $state = null;
    static $registeredOn = null;

    $state ??= new ArrayObject;
    $state->exchangeArray(['items' => $items, 'status' => $status]);
    $http = Http::getFacadeRoot();

    if ($registeredOn === $http) {
        return;
    }

    $registeredOn = $http;

    Http::fake(['eocs.test/api/v1/items*' => function (Request $request) use ($state) {
        if ($state['status'] !== 200) {
            return Http::response(['message' => 'x'], $state['status']);
        }

        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
        $filter = (array) ($query['filter'] ?? []);
        $found = array_values(array_filter($state['items'], fn (array $item): bool => match (true) {
            isset($filter['item_id']) => (string) $item['item_id'] === (string) $filter['item_id'],
            isset($filter['name']) => str_contains(mb_strtolower($item['name']), mb_strtolower((string) $filter['name'])),
            default => true,
        }));

        return Http::response(['data' => $found]);
    }]);
}

/**
 * The number of requests to the (faked) EOCS items API.
 */
function eocsItemRequests(): int
{
    return Http::recorded(fn (Request $request): bool => str_contains($request->url(), 'eocs.test/api/v1/items'))->count();
}
