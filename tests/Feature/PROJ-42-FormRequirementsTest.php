<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\AnalysisStore;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Laravel\Ai\Prompts\AgentPrompt;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
        'cache.default' => 'array',
    ]);

    knowledgeBase([
        'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel']),
        'products/3d-glass-photo.md' => knowledgeDoc(['id' => 'PRODUCT-001', 'type' => 'product', 'title' => '3D-Glasfotos: Produktfamilie, 3D-Aufbereitung und Bezeichnungen', 'products' => ['3d-glass-photo'], 'customer_terms' => ['Viamant']]),
        'products/magic-mug.md' => knowledgeDoc(['id' => 'PRODUCT-002', 'type' => 'product', 'title' => 'Zaubertasse mit Thermoeffekt', 'products' => ['magic-mug']]),
    ]);

    $this->articles = [zammadArticle(['id' => 1, 'body' => '<p>Wo ist mein Paket?</p>'])];
    fakeZammad(extra: ['zammad.test/api/v1/ticket_articles/by_ticket/*' => fn () => Http::response($this->articles)]);
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function frAnswer(): array
{
    return [
        'summary' => ['incident' => 'Frage', 'customer_wish' => 'Auskunft'], 'category' => 'order-process-question', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Antworten.', 'actions' => [], 'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'x', 'knowledge_gaps' => [], 'knowledge_ids' => [], 'confidence' => ['level' => 'MITTEL', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag'],
    ];
}

/**
 * @param  array<string, mixed>  $fields
 */
function frPost(array $fields): TestResponse
{
    return test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['variante' => 'verlauf', ...$fields]);
}

describe('required answers', function () {
    test('the group has no preselection when nothing is known, the user must choose', function () {
        $html = $this->get('/tickets/2137942')->getContent();

        expect($html)->toMatch('/<option value=""\s+selected>Bitte wählen/')->and(scopeConfig($html)['group'])->toBe('')->and($html)->toContain('required');
    });

    test('an empty group is refused by the server', function () {
        CaseAgent::fake([frAnswer()]);

        frPost(['kundengruppe' => '', 'kein_produktbezug' => '1'])->assertSessionHasErrors('kundengruppe');
        CaseAgent::assertNeverPrompted();
    });

    test('a product answer is required: no product, no name and no "no product" is refused', function () {
        CaseAgent::fake([frAnswer()]);

        frPost(['kundengruppe' => 'reseller'])->assertSessionHasErrors('produkte');
        CaseAgent::assertNeverPrompted();
    });

    test('"no product" excludes a product and a typed name, a product and a name may come together', function () {
        CaseAgent::fake([frAnswer()]);

        frPost(['kundengruppe' => 'reseller', 'kein_produktbezug' => '1', 'produkte' => ['magic-mug']])->assertSessionHasErrors('kein_produktbezug');
        frPost(['kundengruppe' => 'reseller', 'kein_produktbezug' => '1', 'produkt_ohne_wissen' => 'Fotokalender'])->assertSessionHasErrors('kein_produktbezug');
        CaseAgent::assertNeverPrompted();

        frPost(['kundengruppe' => 'reseller', 'produkte' => ['magic-mug'], 'produkt_ohne_wissen' => 'Fotokalender'])->assertSessionHasNoErrors();
    });

    test('the unclear group and "no product" stay possible as a deliberate answer', function () {
        CaseAgent::fake([frAnswer()]);

        frPost(['kundengruppe' => 'unclear', 'kein_produktbezug' => '1'])->assertSessionHasNoErrors();
        CaseAgent::assertPromptedTimes(1);
    });

    test('a name of a product without knowledge goes to the prompt, is stored and named in the result with the offer to report the gap', function () {
        CaseAgent::fake([frAnswer()]);

        $location = frPost(['kundengruppe' => 'reseller', 'produkt_ohne_wissen' => ' Fotokalender '])->headers->get('Location');
        parse_str((string) parse_url((string) $location, PHP_URL_QUERY), $query);

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Produkt ohne hinterlegtes Wissen: Fotokalender') && $prompt->contains('kein Produkt aus der Wissensbasis'));
        $stored = app(AnalysisStore::class)->result($query['analyse']);
        expect($stored['inputs']['produkt_ohne_wissen'])->toBe('Fotokalender')->and($stored['meta']['unknown_product'])->toBe('Fotokalender');

        $this->withCookie('staff_name', 'Nele')->get($location)->assertSeeText('Kein Produktwissen zu „Fotokalender“')->assertSeeText('Wissenslücke melden')->assertSee('Produktwissen: Fotokalender', false);
    });

    test('a too long name is refused', function () {
        CaseAgent::fake([frAnswer()]);

        frPost(['kundengruppe' => 'reseller', 'produkt_ohne_wissen' => str_repeat('x', 121)])->assertSessionHasErrors('produkt_ohne_wissen');
        CaseAgent::assertNeverPrompted();
    });

    test('the question form asks for the same answers', function () {
        CaseAgent::fake([frAnswer()]);

        $this->withCookie('staff_name', 'Nele')->post(route('questions.store'), ['text' => 'Frage', 'kundengruppe' => 'reseller'])->assertSessionHasErrors('produkte');
        $this->withCookie('staff_name', 'Nele')->post(route('questions.store'), ['text' => 'Frage', 'kundengruppe' => '', 'kein_produktbezug' => '1'])->assertSessionHasErrors('kundengruppe');
        CaseAgent::assertNeverPrompted();

        $this->withCookie('staff_name', 'Nele')->post(route('questions.store'), ['text' => 'Frage', 'kundengruppe' => 'reseller', 'produkt_ohne_wissen' => 'Fotokalender'])->assertSessionHasNoErrors();
        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Produkt ohne hinterlegtes Wissen: Fotokalender'));
    });

    test('a follow-up of an older analysis without the new fields still runs', function () {
        CaseAgent::fake([frAnswer(), frAnswer()]);
        $location = frPost(['kundengruppe' => 'reseller', 'kein_produktbezug' => '1'])->headers->get('Location');
        parse_str((string) parse_url((string) $location, PHP_URL_QUERY), $query);

        app(AnalysisStore::class)->changeResult($query['analyse'], function (array $content): array {
            unset($content['inputs']['kein_produktbezug'], $content['inputs']['produkt_ohne_wissen']);
            $content['result']['next_step'] = 'internal_first';
            $content['result']['reply'] = null;
            $content['result']['internal_steps'] = ['Prüfen'];

            return $content;
        });

        frPost(['klaerung_analyse' => $query['analyse'], 'klaerung_text' => 'Geprüft'])->assertSessionHasNoErrors();
        CaseAgent::assertPromptedTimes(2);
    });
});

describe('product search', function () {
    test('the picker gets short names and customer terms of all products and no checkbox list', function () {
        $html = $this->get('/tickets/2137942')->getContent();
        $config = scopeConfig($html);
        $products = collect($config['products'])->keyBy('slug');

        expect($products['3d-glass-photo']['name'])->toBe('3D-Glasfotos')->and($products['3d-glass-photo']['terms'])->toBe(['Viamant'])->and($products['magic-mug']['name'])->toBe('Zaubertasse mit Thermoeffekt')
            ->and($html)->not->toContain('name="produkte[]" value=')->toContain('role="combobox"')->toContain('name="kein_produktbezug"');
    });

    test('the same picker serves the question page', function () {
        $html = $this->get(route('questions.create'))->getContent();

        expect(scopeConfig($html)['products'])->toHaveCount(2)->and($html)->toContain('role="combobox"');
    });

    test('a former answer without product is shown as "no product" when the form opens again', function () {
        CaseAgent::fake([frAnswer()]);
        $location = frPost(['kundengruppe' => 'reseller', 'kein_produktbezug' => '1'])->headers->get('Location');

        expect(scopeConfig($this->withCookie('staff_name', 'Nele')->get($location)->getContent())['none'])->toBeTrue();
    });
});

describe('one place for each piece of information', function () {
    test('the form shows one line about the order, manual order data only when no order number is known', function () {
        $this->get('/tickets/2137942')->assertSeeText('Keine Bestellung bekannt – der Vorschlag nutzt nur das Ticket.')->assertSeeText('Bestelldaten von Hand (keine Bestellnummer bekannt)');

        $this->get(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000001']]))->assertDontSeeText('Bestelldaten von Hand')->assertSeeText('Bestellung: 402-0000000-0000001 ausgewählt.');
    });
});

describe('advanced mode', function () {
    test('the switch sets and removes the cookie for everybody and is shown in the header', function () {
        $this->get('/')->assertSeeText('Erweitert');

        $this->from('/tickets/2137942')->post(route('advanced-mode.switch'), ['aktiv' => '1'])->assertRedirect('/tickets/2137942')->assertCookie('advanced_mode', '1');
        $this->from('/tickets/2137942')->post(route('advanced-mode.switch'), ['aktiv' => '0'])->assertCookieExpired('advanced_mode');
        $this->post(route('advanced-mode.switch'), [])->assertSessionHasErrors('aktiv');
    });

    test('without the mode the form hides context choice, preview, loading opt-out and the zammad group, with the mode they are there', function () {
        $articles = [zammadArticle(['id' => 1, 'body' => '<p>Erste</p>']), zammadArticle(['id' => 2, 'body' => '<p>Zweite Nachricht</p>', 'created_at' => '2026-10-02T08:00:00.000Z'])];
        $this->articles = $articles;

        $this->get('/tickets/2137942')->assertDontSeeText('Was soll an die KI gehen?')->assertDontSeeText('Was an die KI geht')->assertDontSeeText('Zammad-Gruppe')->assertSee('name="variante" value="verlauf"', false);
        $this->withCookie('advanced_mode', '1')->get('/tickets/2137942')->assertSeeText('Was soll an die KI gehen?')->assertSeeText('Was an die KI geht')->assertSeeText('Zammad-Gruppe');
    });
});
