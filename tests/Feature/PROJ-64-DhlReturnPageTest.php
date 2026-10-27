<?php

use App\Models\ReturnLabel;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

function returnPayload(array $overrides = []): array
{
    return [
        'ticket_number' => '2137942', 'order_number' => '7JQ-18SU-DVA4', 'eocs_order_id' => 700555,
        'name' => 'Erika Beispiel', 'street' => 'Hauptstraße', 'house_number' => '12a', 'postal_code' => '01099', 'city' => 'Dresden',
        'country' => 'DE', 'confirmed' => true, ...$overrides,
    ];
}

function storeLabel(array $overrides = [], string $staff = 'Nele')
{
    return test()->withCredentials()->withCookie('staff_name', $staff)->postJson(route('dhl-return.store'), returnPayload($overrides));
}

beforeEach(function () {
    $this->withoutVite();
    Cache::flush();
    Storage::fake('return-labels');

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'services.dhl' => ['url' => 'https://dhl.test/returns', 'token_url' => 'https://dhl.test/token', 'client_id' => 'key', 'client_secret' => 'secret', 'username' => 'system', 'password' => 'pw', 'receiver_id' => 'deu', 'timeout' => 5, 'retention_days' => 30],
        'services.zammad.return_group' => 'allg. Kunden',
        'staff.zammad_tokens' => ['Nele' => 'nele-secret-token', 'Cara' => null],
    ]);

    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);
    $this->articles = [zammadArticle(['id' => 1, 'subject' => 'Rücksendung', 'message_id' => '<m1@example.org>', 'body' => '<p>Bestellung 7JQ-18SU-DVA4 bitte zurücknehmen.</p>'])];
    $this->puts = [];
    $this->posts = [];

    fakeZammad(extra: [
        'zammad.test/api/v1/ticket_articles/by_ticket/*' => fn () => Http::response($this->articles),
        'zammad.test/api/v1/users/me' => fn (Request $request) => $request->header('Authorization')[0] === 'Token token=nele-secret-token'
            ? Http::response(['id' => 501, 'firstname' => 'Nele', 'lastname' => 'Gorka', 'login' => 'nele'])
            : Http::response(['error' => 'Invalid token'], 401),
        'zammad.test/api/v1/tickets/51234' => function (Request $request) {
            $this->puts[] = $request->data();

            return $this->putResponse ?? Http::response(['id' => 51234, 'number' => '2137942']);
        },
        'zammad.test/api/v1/tickets' => function (Request $request) {
            $this->posts[] = $request->data();

            return Http::response(['id' => 60001, 'number' => '2140099']);
        },
    ]);
    $this->eocsOrders = [returnEocsOrder()];
    $this->dhlOrder = null;
    $this->putResponse = null;
    Http::fake([
        'eocs.test/api/v1/orders*' => function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            [$number] = explode(',', (string) ($query['filter']['external_order_id'] ?? ''));

            return Http::response(['data' => array_values(array_filter($this->eocsOrders, fn (array $order): bool => $order['external_order_id'] === $number))]);
        },
        'dhl.test/token' => Http::response(['access_token' => 'tok-secret', 'expires_in' => 1799]),
        'dhl.test/returns/orders*' => fn () => $this->dhlOrder ?? Http::response(['shipmentNo' => '999991587211', 'label' => ['b64' => base64_encode(miniPdf())]], 201),
        'dhl.test/returns/locations*' => Http::response([['receiverId' => 'deu', 'billingNumber' => '1', 'receiverAddress' => ['name1' => 'LOOXIS Retoure', 'addressStreet' => 'Lagerweg', 'addressHouse' => '5', 'postalCode' => '53113', 'city' => 'Bonn']]]),
    ]);
});

afterEach(function () {
    cleanUpKnowledgeBases();
});

describe('the page', function () {
    test('is in the navigation and shows the empty search', function () {
        $this->withCookie('staff_name', 'Nele')->get(route('dhl-return.index'))
            ->assertOk()->assertSee('DHL Retoure')->assertSee('Bestellung laden');
    });

    test('an order number loads the order and prefills the address and shows the receiver', function () {
        $this->withCookie('staff_name', 'Nele')->get(route('dhl-return.index', ['suche' => '7JQ-18SU-DVA4']))
            ->assertOk()->assertSee('Hauptstraße')->assertSee('12a')->assertSee('01099')->assertSee('Dresden')->assertSee('Erika Beispiel')
            ->assertSee('LOOXIS Retoure, Lagerweg 5, 53113 Bonn')
            ->assertDontSee('geheim@example.org')->assertDontSee('@js(', false);
    });

    test('a ticket number finds the orders named in the ticket', function () {
        $this->withCookie('staff_name', 'Nele')->get(route('dhl-return.index', ['suche' => 'Ticket#2137942']))
            ->assertOk()->assertSee('7JQ-18SU-DVA4')->assertSee('Hauptstraße')->assertSee('Ticket 2137942');
    });

    test('an Amazon order without an address leaves the fields empty and says why', function () {
        $this->eocsOrders = [[...returnEocsOrder(['external_order_id' => '402-4907715-1581912', 'client' => ['name' => 'Amazon.de', 'sales_platform' => ['name' => 'Amazon']]]), 'shipping' => []]];

        $this->withCookie('staff_name', 'Nele')->get(route('dhl-return.index', ['suche' => '402-4907715-1581912']))
            ->assertOk()->assertSee('Bei Amazon-Bestellungen liegt in EOCS keine Kundenadresse vor');
    });

    test('an unknown ticket or order gives a plain message', function () {
        $this->eocsOrders = [];

        $this->withCookie('staff_name', 'Nele')->get(route('dhl-return.index', ['suche' => '7JQ-18SU-DVA4']))
            ->assertOk()->assertSee('keine passende Bestellung');
        $this->withCookie('staff_name', 'Nele')->get(route('dhl-return.index', ['suche' => 'quatsch']))
            ->assertOk()->assertSee('Bitte eine Ticketnummer oder eine Bestellnummer eingeben');
    });

    test('says so when DHL is not set up', function () {
        config(['services.dhl.password' => null]);

        $this->withCookie('staff_name', 'Nele')->get(route('dhl-return.index'))->assertOk()->assertSee('noch nicht eingerichtet');
    });
});

describe('ordering the label', function () {
    test('orders it once with the confirmed address and answers with the download', function () {
        $response = storeLabel()->assertOk()->assertJsonPath('status', 'created')->assertJsonPath('shipment_no', '999991587211')->assertJsonPath('reused', false);

        expect($response->json('download_url'))->toContain('/download')
            ->and($response->json('instructions'))->toContain('999991587211')->toContain('7JQ-18SU-DVA4')->toContain('Eine Abholung ist nicht beauftragt');

        storeLabel()->assertOk()->assertJsonPath('reused', true);

        expect(ReturnLabel::count())->toBe(1)
            ->and(collect(Http::recorded())->filter(fn ($pair) => str_contains($pair[0]->url(), 'dhl.test/returns/orders'))->count())->toBe(1);
    });

    test('an additional label is only ordered on request', function () {
        storeLabel()->assertOk();
        storeLabel(['additional' => true])->assertOk()->assertJsonPath('reused', false);

        expect(ReturnLabel::count())->toBe(2);
    });

    test('invalid entries are rejected per field and nothing is ordered', function (array $override, string $field) {
        storeLabel($override)->assertStatus(422)->assertJsonValidationErrors($field);

        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'dhl.test'));
        expect(ReturnLabel::count())->toBe(0);
    })->with([
        'not confirmed' => [['confirmed' => false], 'confirmed'],
        'postcode too short' => [['postal_code' => '1099'], 'postal_code'],
        'postcode with letters' => [['postal_code' => '0109A'], 'postal_code'],
        'abroad' => [['country' => 'IT'], 'country'],
        'name too long' => [['name' => str_repeat('A', 51)], 'name'],
        'street too long' => [['street' => str_repeat('A', 51)], 'street'],
        'house number too long' => [['house_number' => str_repeat('1', 11)], 'house_number'],
        'city too long' => [['city' => str_repeat('A', 41)], 'city'],
        'street missing' => [['street' => ''], 'street'],
        'bad ticket number' => [['ticket_number' => 'abc'], 'ticket_number'],
    ]);

    test('umlauts, a house number with a suffix and a postcode with a leading zero are kept as typed', function () {
        storeLabel(['name' => 'Jürgen Müller-Lüdenscheidt', 'street' => 'Königsbrücker Straße', 'house_number' => '12a', 'postal_code' => '01099'])->assertOk();

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'dhl.test/returns/orders')
            && $request['shipper']['name1'] === 'Jürgen Müller-Lüdenscheidt' && $request['shipper']['postalCode'] === '01099' && $request['shipper']['addressHouse'] === '12a');
    });

    test('a rejection by DHL shows what is wrong and can be corrected', function () {
        $this->dhlOrder = Http::response(['status' => 400, 'detail' => 'shipper.postalCode is invalid'], 400);

        storeLabel()->assertStatus(422)->assertJsonPath('code', 'rejected')->assertJsonPath('detail', 'shipper.postalCode is invalid');
    });

    test('needs a chosen name', function () {
        $this->postJson(route('dhl-return.store'), returnPayload())->assertStatus(409);

        expect(ReturnLabel::count())->toBe(0);
    });

    test('does not log the address', function () {
        Log::spy();
        $this->dhlOrder = Http::response(['status' => 400, 'detail' => 'Hauptstraße ungültig'], 400);

        storeLabel()->assertStatus(422);

        Log::shouldNotHaveReceived('warning', fn (string $message, array $context = []) => str_contains(json_encode($context), 'Hauptstra'));
    });
});

describe('the download', function () {
    test('hands out the unchanged PDF only with the ticket number of the label', function () {
        $label = storeLabel()->json();

        $this->withCookie('staff_name', 'Nele')->get($label['download_url'])
            ->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('X-Content-Type-Options', 'nosniff');
        expect($this->withCookie('staff_name', 'Nele')->get($label['download_url'])->getContent())->toBe(miniPdf());

        $this->withCookie('staff_name', 'Nele')->get(route('dhl-return.download', ['label' => $label['id']]))->assertNotFound();
        $this->withCookie('staff_name', 'Nele')->get(route('dhl-return.download', ['label' => $label['id'], 'ticket' => '999']))->assertNotFound();
    });

    test('a label without a ticket needs no ticket number', function () {
        $label = storeLabel(['ticket_number' => null])->json();

        $this->withCookie('staff_name', 'Nele')->get($label['download_url'])->assertOk();
    });

    test('is gone after the retention period', function () {
        $label = storeLabel()->json();
        ReturnLabel::query()->update(['created_at' => now()->subDays(40)]);
        $this->artisan('return-labels:purge')->assertSuccessful();

        $this->withCookie('staff_name', 'Nele')->get($label['download_url'])->assertNotFound();
        expect(ReturnLabel::first()->shipment_no)->toBe('999991587211');
    });
});

function sendLabel(string $id, array $fields = [], string $staff = 'Nele')
{
    return test()->withCredentials()->withCookie('staff_name', $staff)->postJson(route('dhl-return.send', ['label' => $id]), [
        'mode' => 'reply', 'ticket_number' => '2137942', 'text' => "Guten Tag,\nanbei das Label.", 'signature' => "Mit freundlichen Grüßen\nNele Gorka", 'after' => 'close', ...$fields,
    ]);
}

describe('sending', function () {
    test('as a reply goes to the customer of the ticket with the PDF attached and is counted as sent', function () {
        $id = storeLabel()->json('id');

        sendLabel($id)->assertOk()->assertJsonPath('sent', true)->assertJsonPath('ticket_number', '2137942')->assertJsonPath('new_ticket', false);

        expect($this->puts)->toHaveCount(1);
        $article = $this->puts[0]['article'];
        expect($article['to'])->toBe('erika@example.org')
            ->and($article['body'])->toContain('anbei das Label.')->toContain('Nele Gorka')
            ->and($article['attachments'])->toHaveCount(1)
            ->and($article['attachments'][0]['mime-type'])->toBe('application/pdf')
            ->and($article['attachments'][0]['filename'])->toBe('DHL-Retoure-7JQ-18SU-DVA4.pdf')
            ->and(base64_decode($article['attachments'][0]['data']))->toBe(miniPdf())
            ->and($this->puts[0]['state'])->toBe('closed');

        $label = ReturnLabel::first();
        expect($label->sent_by)->toBe('Nele')->and($label->sent_ticket_number)->toBe('2137942')->and($label->sent_as_new_ticket)->toBeFalse();
    });

    test('needs a chosen name', function () {
        $label = ReturnLabel::factory()->create(['file_path' => 'x.pdf']);

        $this->postJson(route('dhl-return.send', ['label' => $label->id]), [])->assertStatus(409);
        expect($this->puts)->toBe([]);
    });

    test('a second send is refused', function () {
        $id = storeLabel()->json('id');
        sendLabel($id)->assertOk();

        sendLabel($id)->assertStatus(409)->assertJsonPath('code', 'sent');

        expect($this->puts)->toHaveCount(1);
    });

    test('as a new ticket needs the address, uses the group and builds the subject itself', function () {
        $id = storeLabel(['ticket_number' => null])->json('id');

        sendLabel($id, ['mode' => 'new', 'ticket_number' => null, 'email' => ''])->assertStatus(422)->assertJsonValidationErrors('email');
        sendLabel($id, ['mode' => 'new', 'ticket_number' => null, 'email' => 'kein-mail'])->assertStatus(422)->assertJsonValidationErrors('email');
        expect($this->posts)->toBe([]);

        sendLabel($id, ['mode' => 'new', 'ticket_number' => null, 'email' => 'kundin@example.org', 'after' => 'reminder', 'days' => 3])
            ->assertOk()->assertJsonPath('ticket_number', '2140099')->assertJsonPath('new_ticket', true);

        expect($this->posts)->toHaveCount(1);
        $post = $this->posts[0];
        expect($post['group'])->toBe('allg. Kunden')
            ->and($post['customer_id'])->toBe('guess:kundin@example.org')
            ->and($post['title'])->toBe('DHL Retoure – Bestellung 7JQ-18SU-DVA4')
            ->and($post['article']['subject'])->toBe('DHL Retoure – Bestellung 7JQ-18SU-DVA4')
            ->and($post['article']['to'])->toBe('kundin@example.org')
            ->and($post['article']['sender'])->toBe('Agent')->and($post['article']['internal'])->toBeFalse()
            ->and($post['article']['attachments'])->toHaveCount(1)
            ->and($post['state'])->toBe('pending reminder')->and($post)->toHaveKey('pending_time');

        expect(ReturnLabel::first()->sent_as_new_ticket)->toBeTrue();
    });

    test('nothing is sent without a token, a chosen name or a file', function () {
        $id = storeLabel()->json('id');

        sendLabel($id, staff: 'Cara')->assertStatus(409)->assertJsonPath('code', 'no_token');

        ReturnLabel::query()->update(['file_path' => null]);
        sendLabel($id)->assertStatus(409);

        expect($this->puts)->toBe([])->and($this->posts)->toBe([]);
    });

    test('a newer customer message asks for confirmation first', function () {
        $id = storeLabel()->json('id');
        $this->articles[] = zammadArticle(['id' => 7, 'body' => '<p>Noch eine Frage.</p>', 'created_at' => '2026-10-02T07:12:00.000Z']);

        sendLabel($id, ['seen_article' => 1])->assertStatus(409)->assertJsonPath('code', 'newer');
        expect($this->puts)->toBe([]);

        sendLabel($id, ['seen_article' => 1, 'confirm_newer' => true])->assertOk();
        expect($this->puts)->toHaveCount(1);
    });

    test('a failed send leaves the label usable and does not mark it as sent', function () {
        $id = storeLabel()->json('id');
        $this->putResponse = Http::response(['error' => 'x'], 422);

        sendLabel($id)->assertStatus(502)->assertJsonPath('code', 'rejected');

        expect(ReturnLabel::first()->sent_at)->toBeNull()->and(ReturnLabel::first()->hasFile())->toBeTrue();
    });

    test('the reply validation requires text, a ticket number and the follow-up', function () {
        $id = storeLabel()->json('id');

        sendLabel($id, ['text' => '', 'ticket_number' => '', 'after' => 'reminder', 'days' => null])->assertStatus(422)->assertJsonValidationErrors(['text', 'ticket_number', 'days']);
    });
});

describe('the address for the option on a ticket page', function () {
    test('is fetched on demand and never stored in the ticket page', function () {
        $this->withCredentials()->withCookie('staff_name', 'Nele')->getJson(route('dhl-return.address', ['bestellung' => '7JQ-18SU-DVA4']))
            ->assertOk()->assertJsonPath('found', true)->assertJsonPath('address.street', 'Hauptstraße')->assertJsonPath('address.postal_code', '01099')
            ->assertJsonPath('address.name', 'Erika Beispiel')->assertHeader('Cache-Control', 'no-store, private');

        $this->withCookie('staff_name', 'Nele')->get(route('tickets.show', ['number' => '2137942']))->assertOk()
            ->assertDontSee('Hauptstraße')->assertDontSee('01099')->assertDontSee('Dresden');
    });

    test('an order without an address says so', function () {
        $this->eocsOrders = [[...returnEocsOrder(), 'shipping' => []]];

        $this->withCredentials()->withCookie('staff_name', 'Nele')->getJson(route('dhl-return.address', ['bestellung' => '7JQ-18SU-DVA4']))
            ->assertOk()->assertJsonPath('found', false)->assertJsonPath('address', null);
    });

    test('rejects an invalid number', function () {
        $this->withCredentials()->withCookie('staff_name', 'Nele')->getJson(route('dhl-return.address', ['bestellung' => 'quatsch']))->assertStatus(422);
    });

    test('needs a chosen name', function () {
        $this->getJson(route('dhl-return.address', ['bestellung' => '7JQ-18SU-DVA4']))->assertStatus(409);
    });
});
