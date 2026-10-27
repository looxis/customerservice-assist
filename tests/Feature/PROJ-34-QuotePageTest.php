<?php

use App\Knowledge\CaseLinks;
use App\Models\QuoteArticle;
use App\Models\QuoteOffer;
use App\Quotes\QuoteBackupImporter;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->withoutVite();
    config(['staff.names' => ['Etienne', 'Nele'], 'staff.admins' => ['Etienne']]);
});

function offerPayload(array $override = []): array
{
    return [
        'company' => 'Muster GmbH', 'contact' => 'Max Muster', 'email' => 'max@example.org',
        'items' => [['sku' => '11244', 'name' => '3D Glasfoto im Herz (M)', 'qty' => 10, 'discount_pct' => 10, 'unit_gross' => 71.1, 'vat' => 19]],
        ...$override,
    ];
}

function costsPayload(array $override = []): array
{
    return [
        'raw_articles' => [['id' => '10485', 'qty' => 1, 'unit_cost' => 5.76], ['id' => '10715', 'qty' => 2, 'unit_cost' => 0.18]],
        'machine_minutes' => 5, 'labor_minutes' => 3, 'hour_rate' => 60, 'machine_rate' => 60,
        'pack_cost' => 0, 'ship_cost' => 0, 'other_cost' => 0, 'target_margin' => 30, 'vat_rate' => 19,
        ...$override,
    ];
}

describe('the page', function () {
    test('everyone sees articles and costs, the navigation links to the page', function () {
        QuoteArticle::factory()->withCosts()->create(['sku' => '11244', 'name' => '3D Glasfoto im Herz (M)']);
        QuoteArticle::factory()->create(['sku' => '99999', 'name' => 'Inaktiver Artikel', 'active' => false]);

        $this->get(route('quote.index'))
            ->assertOk()
            ->assertSee('Angebot berechnen')
            ->assertSee('3D Glasfoto im Herz (M)', false)
            ->assertDontSee('Inaktiver Artikel');
    });

    test('a ticket number in the address is kept, anything else is dropped', function () {
        $this->get(route('quote.index', ['ticket' => '2140026']))->assertSee('Ticket 2140026');
        $this->get(route('quote.index', ['ticket' => '<script>']))->assertDontSee('<script>', false);
    });
});

describe('offers', function () {
    test('saving needs a chosen name', function () {
        $this->postJson(route('quote.offers.store'), offerPayload())->assertStatus(409);
    });

    test('a saved offer gets the next number of the day and is valid for seven days', function () {
        $first = $this->withCredentials()->withCookie('staff_name', 'Nele')->postJson(route('quote.offers.store'), offerPayload())->assertCreated();
        $second = $this->withCredentials()->withCookie('staff_name', 'Nele')->postJson(route('quote.offers.store'), offerPayload())->assertCreated();

        $prefix = 'AN-'.now()->format('Ymd').'-';

        expect($first->json('number'))->toBe($prefix.'001')
            ->and($second->json('number'))->toBe($prefix.'002')
            ->and($first->json('valid_until'))->toBe(now()->addDays(7)->toDateString())
            ->and($first->json('gross'))->toEqual(711)
            ->and(QuoteOffer::first()->created_by)->toBe('Nele');
    });

    test('an offer needs items and sensible numbers', function () {
        $this->withCredentials()->withCookie('staff_name', 'Nele')->postJson(route('quote.offers.store'), offerPayload(['items' => []]))->assertUnprocessable();
        $this->withCredentials()->withCookie('staff_name', 'Nele')->postJson(route('quote.offers.store'), offerPayload(['items' => [['sku' => 'x', 'name' => 'x', 'qty' => 0, 'discount_pct' => 120, 'unit_gross' => -1, 'vat' => 19]]]))->assertUnprocessable();
    });

    test('an offer can be changed, printed and deleted', function () {
        $offer = QuoteOffer::factory()->create();

        $this->withCredentials()->withCookie('staff_name', 'Nele')->putJson(route('quote.offers.update', $offer), offerPayload(['company' => 'Neue GmbH']))->assertOk()->assertJsonPath('company', 'Neue GmbH');
        $this->get(route('quote.offers.print', $offer))->assertOk()->assertSee($offer->number)->assertSee('Neue GmbH');
        $this->withCredentials()->withCookie('staff_name', 'Nele')->deleteJson(route('quote.offers.destroy', $offer))->assertOk();

        expect(QuoteOffer::count())->toBe(0);
    });
});

describe('costs', function () {
    test('an admin changes the costs, the purchase cost is added up on the server', function () {
        $article = QuoteArticle::factory()->create();

        $this->withCredentials()->withCookie('staff_name', 'Etienne')->putJson(route('quote.articles.costs', $article), costsPayload())
            ->assertOk()->assertJsonPath('complete', true)->assertJsonPath('purchase_cost', 6.12);

        expect($article->fresh()->costs_updated_by)->toBe('Etienne');
    });

    test('someone who is not an admin is refused, and so is a missing name', function () {
        $article = QuoteArticle::factory()->create();

        $this->putJson(route('quote.articles.costs', $article), costsPayload())->assertStatus(409);
        $this->withCredentials()->withCookie('staff_name', 'Nele')->putJson(route('quote.articles.costs', $article), costsPayload())->assertForbidden();

        expect($article->fresh()->purchase_cost)->toBeNull();
    });
});

describe('the import of the old backup', function () {
    function backupJson(): string
    {
        return json_encode([
            'app' => 'looxis-rabattrechner', 'version' => 1,
            'products' => [
                ['id' => '11244', 'sku' => '11244', 'name' => '3D Glasfoto im Herz (M)', 'category' => 'belaserte Fotos', 'vk' => 79, 'exact' => true, 'active' => true],
                ['id' => '11892', 'sku' => '11892', 'name' => 'Kochmesser Buche', 'category' => 'Gravur', 'vk' => 37.9, 'exact' => true, 'active' => false],
            ],
            'storage' => [
                'looxis_chatbot_shop_cost_11244' => json_encode(['costBase' => 6.01, 'purchaseSet' => true, 'machineMin' => 5, 'machineSet' => true, 'laborMin' => 3, 'hourRate' => 60, 'machineRate' => 60, 'packCost' => 0, 'shipCost' => 0, 'otherCost' => 0, 'targetMargin' => 30, 'vatRate' => 19, 'rawArticles' => [['id' => '10485', 'qty' => '1', 'unitCost' => '5.76']]]),
                'looxis_chatbot_shop_cost_11892' => json_encode(['costBase' => null, 'purchaseSet' => false, 'machineMin' => 3, 'machineSet' => false, 'rawArticles' => [['id' => '11892', 'qty' => '1', 'unitCost' => '', 'suggested' => true]]]),
            ],
        ]);
    }

    test('articles and confirmed costs are read, automatic suggestions are left out', function () {
        $counts = app(QuoteBackupImporter::class)->import(backupJson());

        $costed = QuoteArticle::where('sku', '11244')->first();
        $open = QuoteArticle::where('sku', '11892')->first();

        expect($counts)->toBe(['articles' => 2, 'with_costs' => 1, 'offers' => 0])
            ->and($costed->hasCompleteCosts())->toBeTrue()
            ->and($costed->purchase_cost)->toBe(6.01)
            ->and($open->hasCompleteCosts())->toBeFalse()
            ->and($open->active)->toBeFalse()
            ->and($open->raw_articles)->toBeNull();
    });

    test('importing twice changes nothing, a foreign file is refused', function () {
        app(QuoteBackupImporter::class)->import(backupJson());
        app(QuoteBackupImporter::class)->import(backupJson());

        expect(QuoteArticle::count())->toBe(2)
            ->and(fn () => app(QuoteBackupImporter::class)->import('{"app":"other"}'))->toThrow(InvalidArgumentException::class);
    });
});

describe('the link in internal procedures', function () {
    test('the key leads to the calculator, with the ticket number when there is one', function () {
        expect(CaseLinks::isKnown('angebotsrechner'))->toBeTrue()
            ->and((new CaseLinks)->targets('angebotsrechner')[0]['url'])->toBe(route('quote.index'))
            ->and((new CaseLinks(ticketNumber: '2140026'))->targets('angebotsrechner')[0]['url'])->toBe(route('quote.index', ['ticket' => '2140026']));
    });
});

describe('the upload of the backup', function () {
    function backupFile(?string $content = null, string $name = 'rabattdata.json'): File
    {
        return UploadedFile::fake()->createWithContent($name, $content ?? backupJson());
    }

    test('only admins see the upload area', function () {
        $this->withCredentials()->withCookie('staff_name', 'Etienne')->get(route('quote.index'))->assertSee('Sicherung einspielen');
        $this->withCredentials()->withCookie('staff_name', 'Nele')->get(route('quote.index'))->assertDontSee('Sicherung einspielen');
        $this->get(route('quote.index'))->assertDontSee('Sicherung einspielen');
    });

    test('an admin uploads the file and the articles are imported', function () {
        $this->withCredentials()->withCookie('staff_name', 'Etienne')->post(route('quote.import'), ['backup' => backupFile()])
            ->assertRedirect(route('quote.index'))->assertSessionHas('success');

        expect(QuoteArticle::count())->toBe(2)
            ->and(QuoteArticle::where('sku', '11244')->first()->costs_updated_by)->toBe('Etienne');
    });

    test('others are refused and nothing is imported', function () {
        $this->withCredentials()->withCookie('staff_name', 'Nele')->post(route('quote.import'), ['backup' => backupFile()])->assertForbidden();

        expect(QuoteArticle::count())->toBe(0);
    });

    test('without a chosen name nothing is imported either', function () {
        $this->post(route('quote.import'), ['backup' => backupFile()])->assertRedirect();

        expect(QuoteArticle::count())->toBe(0);
    });

    test('a wrong file gives a message and imports nothing', function () {
        $admin = fn () => $this->withCredentials()->withCookie('staff_name', 'Etienne');

        $admin()->post(route('quote.import'), ['backup' => backupFile('{"app":"other"}')])->assertRedirect(route('quote.index'))->assertSessionHas('error');
        $admin()->post(route('quote.import'), ['backup' => backupFile('x', 'daten.txt')])->assertSessionHasErrors('backup');
        $admin()->post(route('quote.import'), [])->assertSessionHasErrors('backup');

        expect(QuoteArticle::count())->toBe(0);
    });
});
