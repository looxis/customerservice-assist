<?php

use App\Analysis\Pseudonymizer;
use App\Models\Setting;
use App\Models\SettingVersion;
use App\Orders\OrderFormats;
use App\Orders\OrderNumberDetector;
use App\ProductForge\SkuDetector;
use App\Settings\MailRules;
use App\Settings\SettingsRegistry;
use App\Settings\SettingsStore;
use App\Zammad\AmazonNotice;
use Dom\HTMLDocument;

beforeEach(function () {
    $this->withoutVite();
    config(['cache.default' => 'array', 'staff.admins' => ['Etienne']]);
});

function detectInput(array $changes = []): array
{
    $store = app(SettingsStore::class);
    $settings = [];

    foreach (SettingsRegistry::forPage('detection') as $key => $group) {
        $value = $store->value($key);
        $settings[$key] = $key === 'order_formats'
            ? array_map(fn (array $format): array => [...$format, 'letters' => $format['upper'] ? 'upper' : 'both', 'examples' => implode("\n", $format['examples']), 'counter_examples' => implode("\n", $format['counter_examples']), 'internal' => $format['internal'] ? '1' : null, 'amazon' => $format['amazon'] ? '1' : null], $value)
            : $value;
    }

    return ['settings' => [...$settings, ...$changes], 'versions' => collect(SettingsRegistry::forPage('detection'))->map(fn ($group) => $store->version($group->key))->all()];
}

/**
 * The default formats as the form sends them, with one more format at the end.
 *
 * @param  array<string, mixed>  $format
 */
function withFormat(array $format): array
{
    return [...detectInput()['settings']['order_formats'], [...['key' => '', 'name' => 'Kaufland', 'layout' => 'K##-######', 'prefix' => '', 'letters' => 'upper', 'needs' => [], 'examples' => "K12-345678\nk99-000001", 'counter_examples' => 'K12-34567'], ...$format]];
}

function saveDetection(array $changes = [], string $as = 'Etienne')
{
    return test()->withCookie('staff_name', $as)->put(route('settings.detection.update'), detectInput($changes));
}

describe('the page', function () {
    test('everyone sees the six formats and the article number rules, only admins can change them', function () {
        $html = test()->withCookie('staff_name', 'Nele')->get(route('settings.detection'))->assertOk()
            ->assertSeeText('Bestellnummern-Formate')->assertSeeText('Artikelnummern: Stichwörter')->assertSeeText('Artikelnummern: Anzahl Ziffern')->assertSeeText('ändern dürfen nur Admins')->getContent();

        expect($html)->toContain('###-#######-#######')->toContain('Fachhändler')->toContain('Artikelnummer')->not->toContain('>Speichern<')->not->toContain('Erkennung ausprobieren');
        expect(test()->withCookie('staff_name', 'Etienne')->get(route('settings.detection'))->assertOk()->assertSeeText('Erkennung ausprobieren')->assertSeeText('Format hinzufügen'));
        test()->withCookie('staff_name', 'Nele')->get(route('settings.index'))->assertSeeText('Erkennungsregeln');
    });

    test('someone who is no admin cannot save or try out', function () {
        saveDetection([], 'Nele')->assertForbidden();
        test()->withCookie('staff_name', 'Nele')->post(route('settings.detection.try'), [...detectInput(), 'sample' => 'x'])->assertForbidden();

        expect(Setting::query()->count())->toBe(0);
    });
});

describe('the defaults behave like the former fixed patterns', function () {
    test('each default format recognises its numbers in text and refuses its counter examples', function () {
        $detector = app(OrderNumberDetector::class);
        $text = 'A 402-4907715-1581912, B 7JI-0WC1-6M49, C 30019578, D BEST-PRO30019579, E 700411247, F LmagQ9PV25, G 753194 ende.';

        expect(array_map(fn ($number) => [$number->value, $number->format->key], $detector->inText($text)))->toBe([
            ['402-4907715-1581912', 'amazon'], ['7JI-0WC1-6M49', 'vanilo'], ['30019578', 'looxis-pro'], ['30019579', 'looxis-pro'], ['700411247', 'looxis-fr'], ['LmagQ9PV25', 'masterpics'], ['753194', 'eocs-id'],
        ]);

        foreach (['402-4907715-158191', '123-4567-8901', '30119578', '70041124', 'lmagq9pv25', '75319'] as $counter) {
            expect($detector->normalize($counter))->toBeNull($counter);
        }
    });

    test('only the internal EOCS number is not loaded at once, and amounts and dates stay out of it', function () {
        $detector = app(OrderNumberDetector::class);

        expect($detector->normalize('753194')->isEocsId())->toBeTrue()->and($detector->normalize('402-4907715-1581912')->isEocsId())->toBeFalse()
            ->and($detector->inText('Betrag 1.753194,00 und 12.753194'))->toBe([]);
    });

    test('the stored value of the defaults is none: nothing is saved until something changes', function () {
        saveDetection()->assertSessionHas('saved', 0);

        expect(Setting::query()->count())->toBe(0);
    });
});

describe('formats', function () {
    test('a new format is saved, recognised in text and typed in, and shown with its name', function () {
        saveDetection(['order_formats' => withFormat([])])->assertSessionHas('saved', 1);

        $detector = app(OrderNumberDetector::class);
        $number = $detector->normalize(' #k12-345678 ');
        expect($number->value)->toBe('K12-345678')->and($number->format->name)->toBe('Kaufland')->and($number->isEocsId())->toBeFalse()
            ->and($detector->inText('Meine Nummer K55-123456 bitte')[0]->format->name)->toBe('Kaufland')
            ->and(SettingVersion::query()->sole()->changed_by)->toBe('Etienne');

        test()->withCookie('staff_name', 'Nele')->get(route('settings.detection'))->assertSeeText('Kaufland')->assertSee('K##-######', false);
    });

    test('the order of the formats decides which one counts', function () {
        saveDetection(['order_formats' => withFormat(['name' => 'Interne Nummer', 'layout' => '######', 'examples' => '123456', 'counter_examples' => '', 'internal' => '1'])])->assertSessionHasErrors('settings.order_formats');

        expect(Setting::query()->count())->toBe(0);
    });

    test('a format that is only wildcards, has bad characters or a long prefix is refused', function (array $format) {
        saveDetection(['order_formats' => withFormat($format)])->assertSessionHasErrors('settings.order_formats');

        expect(Setting::query()->count())->toBe(0);
    })->with([
        'only wildcards' => [['layout' => '*****', 'examples' => 'AB123']],
        'bad characters' => [['layout' => 'K##_######', 'examples' => 'K12_345678']],
        'too short' => [['layout' => 'K##', 'examples' => 'K12']],
        'prefix too long' => [['prefix' => str_repeat('A', 21)]],
        'no name' => [['name' => '']],
        'no example' => [['examples' => '']],
        'six examples' => [['examples' => "K11-111111\nK22-222222\nK33-333333\nK44-444444\nK55-555555\nK66-666666"]],
    ]);

    test('examples are checked: one that does not fit, a counter example that does fit, and an overlap with an earlier format', function () {
        saveDetection(['order_formats' => withFormat(['examples' => 'X99-999999'])])->assertSessionHasErrors('settings.order_formats');
        expect(session('errors')->first('settings.order_formats'))->toContain('passt nicht zum Aufbau');

        saveDetection(['order_formats' => withFormat(['counter_examples' => 'K12-345678'])])->assertSessionHasErrors('settings.order_formats');
        expect(session('errors')->first('settings.order_formats'))->toContain('darf nicht passen');

        saveDetection(['order_formats' => withFormat(['name' => 'Doppelt', 'layout' => '###-#######-#######', 'examples' => '402-4907715-1581912', 'counter_examples' => ''])])->assertSessionHasErrors('settings.order_formats');
        expect(collect(session('errors')->get('settings.order_formats'))->implode(' '))->toContain('Überschneidung')->toContain('Amazon');

        expect(Setting::query()->count())->toBe(0);
    });

    test('exactly one format is Amazon, there is at least one format and names are unique', function () {
        $formats = detectInput()['settings']['order_formats'];
        $noAmazon = array_map(fn ($format) => [...$format, 'amazon' => null], $formats);
        $twoAmazon = array_map(fn ($format) => [...$format, 'amazon' => '1'], $formats);

        saveDetection(['order_formats' => $noAmazon])->assertSessionHasErrors('settings.order_formats');
        saveDetection(['order_formats' => $twoAmazon])->assertSessionHasErrors('settings.order_formats');
        saveDetection(['order_formats' => []])->assertSessionHasErrors('settings.order_formats');
        saveDetection(['order_formats' => withFormat(['name' => 'amazon'])])->assertSessionHasErrors('settings.order_formats');

        expect(Setting::query()->count())->toBe(0);
    });

    test('an internal format is looked up by id and loaded only on a click', function () {
        saveDetection(['order_formats' => withFormat(['name' => 'Interne Kaufland-ID', 'layout' => '9#######', 'examples' => '91234567', 'counter_examples' => '', 'internal' => '1'])])->assertSessionHas('saved', 1);

        expect(app(OrderNumberDetector::class)->normalize('91234567')->isEocsId())->toBeTrue();
    });

    test('removing a format works and the history can bring it back', function () {
        saveDetection(['order_formats' => withFormat([])]);
        saveDetection(['order_formats' => array_slice(withFormat([]), 0, 6)])->assertSessionHas('saved', 1);

        expect(app(OrderNumberDetector::class)->normalize('K12-345678'))->toBeNull();

        test()->withCookie('staff_name', 'Etienne')->post(route('settings.restore', ['group' => 'order_formats', 'version' => 1]))->assertRedirect(route('settings.detection'));
        expect(app(OrderNumberDetector::class)->normalize('K12-345678')?->format->name)->toBe('Kaufland');

        test()->withCookie('staff_name', 'Etienne')->post(route('settings.reset', ['group' => 'order_formats']))->assertRedirect(route('settings.detection'));
        expect(app(OrderNumberDetector::class)->normalize('K12-345678'))->toBeNull()->and(Setting::query()->count())->toBe(0);
    });

    test('a stored value that is no longer usable falls back on the defaults', function () {
        Setting::query()->create(['group_key' => 'order_formats', 'value' => [['key' => 'x', 'name' => '', 'layout' => '?', 'examples' => []]], 'version' => 1, 'changed_by' => 'Etienne']);

        expect(array_map(fn ($format) => $format->key, app(OrderFormats::class)->all()))->toBe(['amazon', 'vanilo', 'looxis-pro', 'looxis-fr', 'masterpics', 'eocs-id']);
    });

    test('the Amazon notice uses the layout of the Amazon format', function () {
        $html = '<html><body><p>Du hast eine Nachricht erhalten.</p><p>Bestellnummer: A12-345678</p><p>Text des Käufers</p></body></html>';

        expect((new AmazonNotice)->extract(HTMLDocument::createFromString($html, LIBXML_NOERROR)))->toBeNull();

        saveDetection(['order_formats' => array_map(fn ($format) => $format['key'] === 'amazon' ? [...$format, 'layout' => '?##-######', 'examples' => 'A12-345678', 'counter_examples' => ''] : $format, detectInput()['settings']['order_formats'])])->assertSessionHas('saved', 1);

        $order = app(MailRules::class)->messageBody()->parse('<p>Du hast eine Nachricht erhalten.</p><p>Bestellnummer: A12-345678</p><p>Nachricht:</p><p>Text des Käufers</p>', 'text/html');
        expect($order['order']?->number)->toBe('A12-345678');
    });
});

describe('article numbers', function () {
    test('keywords, digits and the highest number can be changed and apply at once', function () {
        $skus = app(SkuDetector::class);
        expect($skus->in(['Teilenummer 123456']))->toBe([]);

        saveDetection(['sku_keywords' => [...app(SettingsStore::class)->value('sku_keywords'), 'Teilenummer'], 'sku_digits' => 6, 'sku_max' => 2])->assertSessionHas('saved', 3);

        expect($skus->in(['Teilenummer 123456, 654321 und 111111']))->toBe(['123456', '654321'])->and($skus->in(['SKU 12006']))->toBe([]);
    });

    test('a number without a keyword is never an article number', function () {
        expect(app(SkuDetector::class)->in(['Lieferadresse 12006 Berlin, Betrag 12006']))->toBe([]);
    });

    test('invalid rules are refused', function (array $changes) {
        saveDetection($changes)->assertSessionHasErrors();

        expect(Setting::query()->count())->toBe(0);
    })->with([
        'no keywords' => [['sku_keywords' => []]],
        'keyword too short' => [['sku_keywords' => ['S']]],
        'two digits' => [['sku_digits' => 2]],
        'eleven digits' => [['sku_digits' => 11]],
        'highest number zero' => [['sku_max' => 0]],
        'highest number eleven' => [['sku_max' => 11]],
    ]);

    test('keywords are text, never a pattern', function () {
        saveDetection(['sku_keywords' => ['SKU', '(.*)']])->assertSessionHas('saved', 1);

        expect(app(SkuDetector::class)->in(['(.*) 12006', 'etwas 12007']))->toBe(['12006']);
    });
});

describe('trying out', function () {
    test('it shows what saved and typed-in rules recognise and saves nothing', function () {
        $sample = 'Bestellung K12-345678 zu Artikelnummer 12006 und Teilenummer 65432';

        test()->withCookie('staff_name', 'Etienne')->post(route('settings.detection.try'), [...detectInput(['order_formats' => withFormat([]), 'sku_keywords' => [...app(SettingsStore::class)->value('sku_keywords'), 'Teilenummer'], 'sku_digits' => 5]), 'sample' => $sample])->assertRedirect(route('settings.detection'));

        $trial = session('trial');
        expect($trial['saved']['orders'])->toBe([])->and($trial['draft']['orders'][0])->toBe(['value' => 'K12-345678', 'format' => 'Kaufland', 'automatic' => true, 'claims' => []])
            ->and($trial['saved']['skus'])->toBe(['12006'])->and($trial['draft']['skus'])->toBe(['12006', '65432']);
        expect(Setting::query()->count())->toBe(0)->and(SettingVersion::query()->count())->toBe(0);
    });

    test('a missing, too long or invalid input is reported', function () {
        test()->withCookie('staff_name', 'Etienne')->from(route('settings.detection'))->post(route('settings.detection.try'), [...detectInput(), 'sample' => ''])->assertSessionHasErrors('sample');
        test()->withCookie('staff_name', 'Etienne')->from(route('settings.detection'))->post(route('settings.detection.try'), [...detectInput(), 'sample' => str_repeat('a', 10001)])->assertSessionHasErrors('sample');
        test()->withCookie('staff_name', 'Etienne')->from(route('settings.detection'))->post(route('settings.detection.try'), [...detectInput(['sku_digits' => 99]), 'sample' => 'x'])->assertSessionHasErrors('settings.sku_digits');
    });
});

describe('the pseudonymisation', function () {
    test('a number that fits a format counts as an order number, not as a phone number', function () {
        $text = 'Meine Nummer 0441122334 bitte prüfen.';

        expect(app(Pseudonymizer::class)->apply($text))->toContain('[TELEFON_1]');

        saveDetection(['order_formats' => withFormat(['name' => 'Zehnstellig', 'layout' => '##########', 'examples' => '0441122334', 'counter_examples' => '044112233'])])->assertRedirect();

        expect(app(Pseudonymizer::class)->apply($text))->toContain('0441122334')->not->toContain('[TELEFON_');
    });
});
