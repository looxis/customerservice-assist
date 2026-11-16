<?php

use App\Analysis\Agents\CaseAgent;
use App\Dhl\DhlException;
use App\Dhl\DhlProblem;
use App\Models\ReturnLabel;
use App\Returns\ReturnCountry;
use App\Returns\ReturnInstructions;
use App\Returns\ReturnLabelInput;
use App\Returns\ReturnLabelService;
use App\Returns\ReturnLanguage;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Returns from abroad (PROJ-64, 2026-11-16): labels from France, Austria, Belgium, the Netherlands, Luxembourg and
 * Italy, the receiver per country, the postal code per country and the texts for the customer in five languages.
 */
function abroadInput(ReturnCountry $country = ReturnCountry::France, array $override = []): ReturnLabelInput
{
    return new ReturnLabelInput(...['ticketNumber' => '2137942', 'orderNumber' => '7JQ-18SU-DVA4', 'eocsOrderId' => 700555, 'name' => 'Camille Exemple', 'street' => 'rue de la Paix', 'houseNumber' => '12', 'postalCode' => '75002', 'city' => 'Paris', 'country' => $country, ...$override]);
}

function abroadPayload(array $overrides = []): array
{
    return [
        'ticket_number' => '2137942', 'order_number' => '7JQ-18SU-DVA4', 'eocs_order_id' => 700555,
        'name' => 'Camille Exemple', 'street' => 'rue de la Paix', 'house_number' => '12', 'postal_code' => '75002', 'city' => 'Paris',
        'country' => 'FR', 'confirmed' => true, ...$overrides,
    ];
}

function abroadStore(array $overrides = [])
{
    return test()->withCredentials()->withCookie('staff_name', 'Nele')->postJson(route('dhl-return.store'), abroadPayload($overrides));
}

function abroadLabel(array $overrides = []): ReturnLabel
{
    Storage::disk('return-labels')->put('abroad.pdf', miniPdf());

    return ReturnLabel::factory()->create(['ticket_number' => '2137942', 'order_number' => '7JQ-18SU-DVA4', 'shipment_no' => '513007200012', 'file_path' => 'abroad.pdf', 'country' => 'FR', ...$overrides]);
}

function abroadAnswer(string $language, string $text): array
{
    return [
        'summary' => ['incident' => 'Falsche Ware', 'customer_wish' => 'Umtausch'],
        'category' => 'order-process-question', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Ware zurückholen.', 'actions' => [],
        'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'Rückgabe vereinbart.', 'knowledge_gaps' => [],
        'knowledge_ids' => [], 'confidence' => ['level' => 'NIEDRIG', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [],
        'reply' => ['language' => $language, 'text' => $text],
    ];
}

function abroadDraft(ReturnLabel $label, string $language, string $text): string
{
    CaseAgent::fake([abroadAnswer($language, $text)]);

    $response = test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kein_produktbezug' => '1', 'kundengruppe' => 'reseller', 'variante' => 'verlauf', 'return_label' => $label->id]);
    preg_match('#<textarea[^>]*x-ref="text"[^>]*>(.*?)</textarea>#s', test()->withCookie('staff_name', 'Nele')->get($response->headers->get('Location'))->assertOk()->getContent(), $draft);

    return html_entity_decode($draft[1] ?? '', ENT_QUOTES);
}

beforeEach(function () {
    $this->withoutVite();
    Cache::flush();
    Storage::fake('return-labels');

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
        'services.dhl' => ['url' => 'https://dhl.test/returns', 'token_url' => 'https://dhl.test/token', 'client_id' => 'key', 'client_secret' => 'secret', 'username' => 'system', 'password' => 'pw', 'receiver_id' => 'RetourenLager01', 'timeout' => 5, 'retention_days' => 30],
        'services.zammad.return_group' => 'allg. Kunden',
        'staff.zammad_tokens' => ['Nele' => 'nele-secret-token'],
    ]);

    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);

    $this->orders = [];
    $this->locationCalls = [];
    $this->posts = [];
    $this->receivers = ['deu' => ['RetourenLager01'], 'fra' => ['Lager_FR_CB02'], 'aut' => ['Lager_AT_CB02'], 'bel' => ['Lager_BE_CB02'], 'nld' => ['Lager_NL_CB02'], 'lux' => ['Lager_LU_CB02'], 'ita' => ['Lager_IT_CB02']];
    $this->eocsOrder = returnEocsOrder(['shipping' => ['first_name' => 'Camille', 'last_name' => 'Exemple', 'street' => 'rue de la Paix', 'street_no' => '12', 'zipcode' => '75002', 'city' => 'Paris', 'country_code' => 'FR']]);

    fakeZammad([zammadArticle(['id' => 1, 'subject' => 'Retour', 'message_id' => '<m1@example.org>', 'body' => '<p>Commande 7JQ-18SU-DVA4 : mauvais article reçu.</p>'])], extra: [
        'zammad.test/api/v1/users/me' => Http::response(['id' => 501, 'firstname' => 'Nele', 'lastname' => 'Gorka', 'login' => 'nele']),
        'zammad.test/api/v1/tickets' => function (Request $request) {
            $this->posts[] = $request->data();

            return Http::response(['id' => 60001, 'number' => '2140099']);
        },
    ]);
    Http::fake([
        'eocs.test/api/v1/orders*' => fn () => Http::response(['data' => [$this->eocsOrder]]),
        'dhl.test/token' => Http::response(['access_token' => 'tok-secret', 'expires_in' => 1799]),
        'dhl.test/returns/orders*' => function (Request $request) {
            $this->orders[] = $request->data();

            return Http::response(['shipmentNo' => '513007200012', 'label' => ['b64' => base64_encode(miniPdf())]], 201);
        },
        'dhl.test/returns/locations*' => function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $this->locationCalls[] = $query['countryCode'];

            return Http::response(array_map(fn (string $id): array => ['receiverId' => $id, 'shipperCountry' => $query['countryCode'], 'receiverAddress' => ['name1' => 'LOOXIS GmbH', 'addressStreet' => 'Magdeburger Str.', 'addressHouse' => '11', 'postalCode' => '32423', 'city' => 'Minden']], $this->receivers[$query['countryCode']] ?? []));
        },
    ]);
});

afterEach(fn () => cleanUpKnowledgeBases());

describe('ordering the label', function () {
    test('a label from abroad is ordered for the receiver DHL lists for that country', function (ReturnCountry $country, string $dhlCode, string $receiver) {
        $label = app(ReturnLabelService::class)->create(abroadInput($country), 'Nele');

        expect($label->status)->toBe(ReturnLabel::CREATED)
            ->and($label->country)->toBe($country->value)
            ->and($this->locationCalls)->toBe([$dhlCode])
            ->and($this->orders)->toHaveCount(1)
            ->and($this->orders[0]['receiverId'])->toBe($receiver)
            ->and($this->orders[0]['shipper'])->toBe(['name1' => 'Camille Exemple', 'addressStreet' => 'rue de la Paix', 'addressHouse' => '12', 'postalCode' => '75002', 'city' => 'Paris']);
    })->with([
        'France' => [ReturnCountry::France, 'fra', 'Lager_FR_CB02'],
        'Austria' => [ReturnCountry::Austria, 'aut', 'Lager_AT_CB02'],
        'Belgium' => [ReturnCountry::Belgium, 'bel', 'Lager_BE_CB02'],
        'Netherlands' => [ReturnCountry::Netherlands, 'nld', 'Lager_NL_CB02'],
        'Luxembourg' => [ReturnCountry::Luxembourg, 'lux', 'Lager_LU_CB02'],
        'Italy' => [ReturnCountry::Italy, 'ita', 'Lager_IT_CB02'],
    ]);

    test('a German label keeps the configured receiver and asks DHL for no list', function () {
        $label = app(ReturnLabelService::class)->create(abroadInput(ReturnCountry::Germany, ['postalCode' => '01099', 'city' => 'Dresden']), 'Nele');

        expect($label->country)->toBe('DE')
            ->and($this->locationCalls)->toBe([])
            ->and($this->orders[0]['receiverId'])->toBe('RetourenLager01');
    });

    test('the receiver of a country is looked up once for several labels', function () {
        app(ReturnLabelService::class)->create(abroadInput(), 'Nele');
        app(ReturnLabelService::class)->create(abroadInput(override: ['eocsOrderId' => 700556]), 'Nele');

        expect($this->orders)->toHaveCount(2)->and($this->locationCalls)->toBe(['fra']);
    });

    test('without exactly one receiver for the country nothing is ordered', function (array $receivers) {
        $this->receivers['fra'] = $receivers;

        expect(fn () => app(ReturnLabelService::class)->create(abroadInput(), 'Nele'))
            ->toThrow(fn (DhlException $exception) => expect($exception->problem)->toBe(DhlProblem::NoReceiver));

        expect($this->orders)->toBe([])
            ->and(ReturnLabel::query()->sole()->status)->toBe(ReturnLabel::FAILED);
    })->with(['none' => [[]], 'two' => [['Lager_FR_A', 'Lager_FR_B']]]);

    test('the same address in another country is another request, a German request keeps its old fingerprint', function () {
        $german = abroadInput(ReturnCountry::Germany);

        expect($german->fingerprint())->toBe(hash('sha256', mb_strtolower('2137942|700555|Camille Exemple|rue de la Paix|12|75002|Paris')))
            ->and(abroadInput(ReturnCountry::France)->fingerprint())->not->toBe($german->fingerprint())
            ->and(abroadInput(ReturnCountry::France)->fingerprint())->not->toBe(abroadInput(ReturnCountry::Italy)->fingerprint());
    });
});

describe('the form', function () {
    test('a French address gives a label with the country and the texts in every language', function () {
        abroadStore()->assertOk()
            ->assertJsonPath('status', 'created')
            ->assertJsonPath('country', 'FR')
            ->assertJsonPath('country_name', 'Frankreich')
            ->assertJsonPath('language', 'fr')
            ->assertJsonPath('texts.fr.subject', 'Retour DHL – commande 7JQ-18SU-DVA4')
            ->assertJsonPath('texts.de.subject', 'DHL Retoure – Bestellung 7JQ-18SU-DVA4')
            ->assertJsonStructure(['texts' => ['de' => ['block', 'mail', 'subject'], 'en', 'fr', 'it', 'nl']]);

        expect($this->orders[0]['receiverId'])->toBe('Lager_FR_CB02');
    });

    test('the postal code has to fit the country', function (string $country, string $postalCode, bool $valid) {
        $response = abroadStore(['country' => $country, 'postal_code' => $postalCode]);

        $valid ? $response->assertOk() : $response->assertStatus(422)->assertJsonValidationErrors('postal_code');
        expect($this->orders)->toHaveCount($valid ? 1 : 0);
    })->with([
        ['DE', '01099', true], ['DE', '1099', false],
        ['FR', '75002', true], ['FR', '7500', false],
        ['IT', '00184', true], ['IT', '184', false],
        ['AT', '1010', true], ['AT', '10100', false],
        ['BE', '1000', true], ['BE', 'B-1000', false],
        ['LU', '1009', true], ['LU', 'L-1009', false],
        ['NL', '1012 AB', true], ['NL', '1012ab', true], ['NL', '1012', false], ['NL', '1012 ABC', false],
    ]);

    test('the message of a wrong postal code names the rule of the country', function () {
        abroadStore(['country' => 'NL', 'postal_code' => '1012'])->assertStatus(422)->assertJsonPath('errors.postal_code.0', 'Die Postleitzahl muss in den Niederlanden aus vier Ziffern und zwei Buchstaben bestehen (z. B. 1234 AB).');
        abroadStore(['country' => 'AT', 'postal_code' => '75002'])->assertStatus(422)->assertJsonPath('errors.postal_code.0', 'Die Postleitzahl muss in Österreich aus genau vier Ziffern bestehen.');
    });

    test('another country is refused and the message names the countries and the portal', function (?string $country) {
        $response = abroadStore(['country' => $country])->assertStatus(422);

        expect($response->json('errors.country.0'))->toContain('Frankreich')->toContain('Italien')->toContain('Geschäftskundenportal')
            ->and($this->orders)->toBe([])
            ->and(ReturnLabel::query()->count())->toBe(0);
    })->with(['Switzerland' => ['CH'], 'lower case' => ['fr'], 'missing' => [null], 'three letters' => ['FRA']]);

    test('DHL without a receiver for the country gives a message and no label', function () {
        $this->receivers['fra'] = [];

        abroadStore()->assertStatus(502)->assertJsonPath('code', 'no_receiver');

        expect($this->orders)->toBe([]);
    });
});

describe('the page', function () {
    test('offers the countries, shows the country of the order and where the return goes', function () {
        $page = $this->withCookie('staff_name', 'Nele')->get(route('dhl-return.index', ['suche' => '7JQ-18SU-DVA4']))->assertOk();

        $page->assertSee('<option value="FR">Frankreich</option>', false)
            ->assertSee('<option value="NL">Niederlande</option>', false)
            ->assertSee('Sprache der Mail')
            ->assertSee('LOOXIS GmbH, Magdeburger Str. 11, 32423 Minden')
            ->assertDontSee('nur für Rücksendungen aus Deutschland');

        expect($this->locationCalls)->toEqualCanonicalizing(['fra', 'deu']);
    });

    test('names the country of an earlier label from abroad', function () {
        abroadLabel();

        $this->withCookie('staff_name', 'Nele')->get(route('dhl-return.index', ['suche' => '7JQ-18SU-DVA4']))
            ->assertOk()->assertSee('aus Frankreich');
    });

    test('a new ticket gets the subject in the chosen language', function (?string $language, string $subject) {
        $label = abroadLabel(['ticket_number' => null]);

        $this->withCredentials()->withCookie('staff_name', 'Nele')->postJson(route('dhl-return.send', ['label' => $label->id]), [
            'mode' => 'new', 'email' => 'camille@example.org', 'text' => 'Bonjour', 'after' => 'close', 'language' => $language,
        ])->assertOk()->assertJsonPath('sent', true);

        expect($this->posts)->toHaveCount(1)->and($this->posts[0]['title'])->toBe($subject);
    })->with([
        'chosen' => ['it', 'Reso DHL – ordine 7JQ-18SU-DVA4'],
        'not given: the language usual for the country' => [null, 'Retour DHL – commande 7JQ-18SU-DVA4'],
    ]);

    test('an unknown language is refused', function () {
        $label = abroadLabel(['ticket_number' => null]);

        $this->withCredentials()->withCookie('staff_name', 'Nele')->postJson(route('dhl-return.send', ['label' => $label->id]), [
            'mode' => 'new', 'email' => 'camille@example.org', 'text' => 'Hola', 'after' => 'close', 'language' => 'es',
        ])->assertStatus(422)->assertJsonValidationErrors('language');

        expect($this->posts)->toBe([]);
    });
});

describe('the texts for the customer', function () {
    test('the German text for Germany is unchanged', function () {
        expect(ReturnInstructions::block('123'))->toBe(implode("\n", [
            'So senden Sie die Ware zurück:',
            '1. Verpacken Sie die Ware sicher in einem stabilen Karton.',
            '2. Drucken Sie das angehängte DHL-Retourenlabel (PDF) gut lesbar aus und kleben Sie es auf die größte Fläche des Pakets. Alte Versandetiketten und Barcodes müssen entfernt oder vollständig abgedeckt sein.',
            '3. Geben Sie das Paket in einer DHL-Filiale oder einem DHL-Paketshop ab. Eine Abholung ist nicht beauftragt.',
            '4. Bewahren Sie den Einlieferungsbeleg auf.',
            '',
            'Retouren-Sendungsnummer: 123',
        ]))->and(ReturnInstructions::mail('123', 'A-1'))->toStartWith("Guten Tag,\n\nanbei erhalten Sie das vorfrankierte DHL-Retourenlabel für die Rücksendung zu Ihrer Bestellung A-1.")
            ->and(ReturnInstructions::subject('A-1'))->toBe('DHL Retoure – Bestellung A-1');
    });

    test('every language has the steps, and abroad no step sends the customer to DHL', function (ReturnLanguage $language) {
        $home = ReturnInstructions::block('513007200012', $language, ReturnCountry::Germany);
        $abroad = ReturnInstructions::block('513007200012', $language, ReturnCountry::France);

        expect($home)->toContain('513007200012')->toContain('DHL')
            ->and(substr_count($home, "\n"))->toBe(6)
            ->and($abroad)->toContain('513007200012')->not->toContain('DHL')->not->toContain('{')
            ->and(ReturnInstructions::mail('513007200012', 'A-1', $language, ReturnCountry::France))->toContain('A-1')->toContain($abroad)->not->toContain('{')->not->toContain('DHL')
            ->and(ReturnInstructions::subject('A-1', $language))->toContain('A-1')->toContain('DHL');
    })->with(ReturnLanguage::cases());

    test('the place to hand in the parcel follows the country, not the language', function () {
        expect(ReturnInstructions::block('1', ReturnLanguage::German, ReturnCountry::Austria))->toContain('bei dem Versanddienst ab, der auf dem Label genannt ist')
            ->and(ReturnInstructions::block('1', ReturnLanguage::French, ReturnCountry::Germany))->toContain('agence DHL');
    });

    test('the language is read from its German name', function (?string $name, ?ReturnLanguage $language) {
        expect(ReturnLanguage::fromName($name))->toBe($language);
    })->with([
        ['Französisch', ReturnLanguage::French], ['französisch ', ReturnLanguage::French], ['Deutsch', ReturnLanguage::German],
        ['Englisch (US)', ReturnLanguage::English], ['Italienisch', ReturnLanguage::Italian], ['Niederländisch', ReturnLanguage::Dutch],
        ['Flämisch', ReturnLanguage::Dutch], ['Spanisch', null], ['', null], [null, null],
    ]);

    test('without a known language the country decides', function (ReturnCountry $country, ReturnLanguage $language) {
        expect($country->language())->toBe($language);
    })->with([
        [ReturnCountry::Germany, ReturnLanguage::German], [ReturnCountry::Austria, ReturnLanguage::German], [ReturnCountry::France, ReturnLanguage::French],
        [ReturnCountry::Italy, ReturnLanguage::Italian], [ReturnCountry::Netherlands, ReturnLanguage::Dutch], [ReturnCountry::Belgium, ReturnLanguage::English], [ReturnCountry::Luxembourg, ReturnLanguage::English],
    ]);
});

describe('the proposal', function () {
    test('a French draft gets the French steps with the neutral place', function () {
        $draft = abroadDraft(abroadLabel(), 'Französisch', "Bonjour,\nvous trouverez l’étiquette de retour en pièce jointe.");

        expect($draft)->toContain('Voici comment nous retourner la marchandise')
            ->toContain('auprès du transporteur indiqué sur l’étiquette')
            ->toContain('Numéro de suivi du retour : 513007200012')
            ->not->toContain('So senden Sie die Ware zurück')
            ->not->toContain('agence DHL');
    });

    test('a German draft to a customer in France stays German, with the neutral place', function () {
        $draft = abroadDraft(abroadLabel(), 'Deutsch', "Guten Tag,\ndas Retourenlabel hängt an.");

        expect($draft)->toContain('So senden Sie die Ware zurück')
            ->toContain('bei dem Versanddienst ab, der auf dem Label genannt ist')
            ->not->toContain('DHL-Filiale');
    });

    test('a draft in a language without a fixed text gets the language usual for the country', function () {
        $draft = abroadDraft(abroadLabel(['country' => 'BE']), 'Spanisch', "Hola,\nadjuntamos la etiqueta.");

        expect($draft)->toContain('How to return the goods')->toContain('carrier named on the label');
    });

    test('the steps are not added again when the number already stands in a draft of another language', function () {
        $label = abroadLabel();
        CaseAgent::fake([abroadAnswer('Französisch', 'Bonjour')]);
        $response = $this->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kein_produktbezug' => '1', 'kundengruppe' => 'reseller', 'variante' => 'verlauf', 'return_label' => $label->id]);
        parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

        $this->withCredentials()->withCookie('staff_name', 'Nele')->putJson(route('tickets.analysis.reply', ['number' => '2137942', 'analysis' => $query['analyse']]), ['text' => "Hello,\n\nReturn tracking number: 513007200012"])->assertOk();

        preg_match('#<textarea[^>]*x-ref="text"[^>]*>(.*?)</textarea>#s', $this->withCookie('staff_name', 'Nele')->get($response->headers->get('Location'))->getContent(), $draft);

        expect(substr_count($draft[1], '513007200012'))->toBe(1)->and($draft[1])->not->toContain('Voici comment');
    });

    test('the model learns neither the country nor the address', function () {
        abroadDraft(abroadLabel(), 'Französisch', 'Bonjour');

        CaseAgent::assertPrompted(fn ($prompt): bool => $prompt->contains('vorfrankiertes DHL-Retourenlabel') && ! $prompt->contains('513007200012') && ! $prompt->contains('rue de la Paix') && ! $prompt->contains('Frankreich'));
    });
});

test('dhl:check shows per country whether there is exactly one receiver', function () {
    $this->receivers['ita'] = [];

    $this->artisan('dhl:check')
        ->expectsOutputToContain('Anmeldung bei DHL erfolgreich')
        ->expectsOutputToContain('Lager_FR_CB02')
        ->expectsOutputToContain('genau ein Empfänger nötig')
        ->assertSuccessful();
});

describe('QA', function () {
    test('the same request from abroad orders one label only', function () {
        abroadStore()->assertOk()->assertJsonPath('reused', false);
        abroadStore()->assertOk()->assertJsonPath('reused', true)->assertJsonPath('country', 'FR');

        expect($this->orders)->toHaveCount(1)->and(ReturnLabel::query()->count())->toBe(1);
    });

    test('a failed order abroad leaves no texts and logs neither address nor receiver', function () {
        Log::spy();
        $this->receivers['fra'] = [];

        abroadStore()->assertStatus(502)->assertJsonPath('code', 'no_receiver')->assertJsonMissingPath('texts');

        expect(ReturnLabel::query()->sole()->payload()['texts'])->toBe([]);
        Log::shouldNotHaveReceived('warning', fn (string $message, array $context = []) => str_contains(json_encode($context), 'Paix') || str_contains(json_encode($context), 'Camille'));
    });

    test('the message without a receiver names the reason and the portal', function () {
        expect(DhlProblem::NoReceiver->message())->toContain('Geschäftskundenportal');
    });

    test('a label with a country that is no longer offered counts as German', function () {
        $label = abroadLabel(['country' => 'CH']);

        expect($label->returnCountry())->toBe(ReturnCountry::Germany)
            ->and($label->payload()['language'])->toBe('de')
            ->and($label->payload()['block'])->toContain('DHL-Filiale');
    });

    test('an order from a country without labels is shown with its country, and the label is refused on the server', function () {
        $this->eocsOrder = returnEocsOrder(['shipping' => ['first_name' => 'Urs', 'last_name' => 'Beispiel', 'street' => 'Bahnhofstrasse', 'street_no' => '1', 'zipcode' => '8001', 'city' => 'Zürich', 'country_code' => 'ch']]);

        $this->withCookie('staff_name', 'Nele')->get(route('dhl-return.index', ['suche' => '7JQ-18SU-DVA4']))
            ->assertOk()->assertSee('\u0022country\u0022:\u0022CH\u0022', false)->assertSee('Geschäftskundenportal');

        abroadStore(['country' => 'CH', 'postal_code' => '8001'])->assertStatus(422)->assertJsonValidationErrors('country');
        expect($this->orders)->toBe([]);
    });

    test('a label tied to a proposal afterwards gets the steps in the language of the draft', function () {
        CaseAgent::fake([abroadAnswer('Italienisch', 'Buongiorno')]);
        $response = $this->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kein_produktbezug' => '1', 'kundengruppe' => 'reseller', 'variante' => 'verlauf']);
        parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $label = abroadLabel(['country' => 'IT']);

        $this->withCredentials()->withCookie('staff_name', 'Nele')
            ->putJson(route('tickets.analysis.return-label', ['number' => '2137942', 'analysis' => $query['analyse']]), ['label' => $label->id])->assertOk();

        $this->withCookie('staff_name', 'Nele')->get($response->headers->get('Location'))->assertOk()
            ->assertSee('Ecco come restituire la merce')->assertSee('corriere indicato')->assertDontSee('filiale DHL');
    });

    test('a direct reply without the model gets the steps in the language usual for the country', function () {
        $response = $this->withCredentials()->withCookie('staff_name', 'Nele')->post(route('tickets.manual-reply', ['number' => '2137942']));
        parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $label = abroadLabel();

        $this->withCredentials()->withCookie('staff_name', 'Nele')
            ->putJson(route('tickets.analysis.return-label', ['number' => '2137942', 'analysis' => $query['analyse']]), ['label' => $label->id])->assertOk();

        preg_match('#<textarea[^>]*x-ref="text"[^>]*>(.*?)</textarea>#s', $this->withCookie('staff_name', 'Nele')->get($response->headers->get('Location'))->assertOk()->getContent(), $draft);

        expect($draft[1] ?? '')->toContain('Voici comment nous retourner la marchandise')->not->toContain('So senden Sie die Ware zurück');
    });

    test('the page of a ticket with a label from abroad does not carry the address', function () {
        abroadDraft(abroadLabel(), 'Französisch', 'Bonjour');

        $this->withCookie('staff_name', 'Nele')->get(route('tickets.show', ['number' => '2137942']))->assertOk()->assertDontSee('rue de la Paix');
    });
});
