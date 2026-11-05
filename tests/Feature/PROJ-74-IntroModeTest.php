<?php

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
    ]);

    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function introPage(?string $cookie = null): string
{
    fakeZammad([zammadArticle(['id' => 1])]);

    $request = test()->withCookie('staff_name', 'Nele');

    if ($cookie !== null) {
        $request = $request->withCookie('intro_mode', $cookie);
    }

    return $request->get('/tickets/2137942')->assertOk()->getContent();
}

describe('the switch', function () {
    test('it is off by default and offered in the header next to the advanced mode', function () {
        $html = introPage();

        expect($html)->toContain('Einführung')->toContain('action="'.route('intro-mode.switch').'"')
            ->and($html)->toMatch('/role="switch" aria-checked="false"[^>]*>\s*<span[^>]*>\s*<span[^>]*><\/span>\s*<\/span>\s*Einführung/');
    });

    test('switching on sets a cookie for this browser, switching off removes it', function () {
        $this->withCookie('staff_name', 'Nele')->post(route('intro-mode.switch'), ['aktiv' => '1'])->assertRedirect()->assertCookie('intro_mode', '1');
        $this->withCookie('staff_name', 'Nele')->post(route('intro-mode.switch'), ['aktiv' => '0'])->assertRedirect()->assertCookieExpired('intro_mode');
    });

    test('the switch needs a value', function () {
        $this->withCookie('staff_name', 'Nele')->post(route('intro-mode.switch'))->assertSessionHasErrors('aktiv');
    });

    test('with the cookie the switch is shown as on', function () {
        expect(introPage('1'))->toMatch('/role="switch" aria-checked="true"[^>]*>\s*<span[^>]*>\s*<span[^>]*><\/span>\s*<\/span>\s*Einführung/');
    });
});

describe('the explanatory texts', function () {
    test('without the introduction they are not shown', function () {
        $html = introPage();

        expect($html)->not->toContain('So schreibst du es')
            ->and($html)->not->toContain('Fakten, Entscheidungen und Wünsche an die Antwort gelten für die KI als geprüft')
            ->and($html)->not->toContain('Um welches Produkt geht es?');
    });

    test('with the introduction they are shown', function () {
        $html = introPage('1');

        expect($html)->toContain('So schreibst du es')->and($html)->toContain('Fakten, Entscheidungen und Wünsche an die Antwort gelten für die KI als geprüft');
    });

    test('the advanced mode and the introduction work independently', function () {
        fakeZammad([zammadArticle(['id' => 1])]);

        $html = $this->withCookie('staff_name', 'Nele')->withCookie('advanced_mode', '1')->get('/tickets/2137942')->assertOk()->getContent();

        $both = $this->withCookie('staff_name', 'Nele')->withCookie('advanced_mode', '1')->withCookie('intro_mode', '1')->get('/tickets/2137942')->assertOk()->getContent();

        expect($html)->not->toContain('So schreibst du es')->and($both)->toContain('So schreibst du es');
    });

    test('the question page shows its introduction only in the introduction mode', function () {
        $without = $this->withCookie('staff_name', 'Nele')->get(route('questions.create'))->assertOk()->getContent();
        $with = $this->withCookie('staff_name', 'Nele')->withCookie('intro_mode', '1')->get(route('questions.create'))->assertOk()->getContent();

        expect($without)->not->toContain('die App antwortet aus dem Unternehmenswissen')
            ->and($with)->toContain('die App antwortet aus dem Unternehmenswissen');
    });

    test('a privacy hint at the question field is a warning and stays in both modes', function () {
        $without = $this->withCookie('staff_name', 'Nele')->get(route('questions.create'))->assertOk()->getContent();

        expect($without)->toContain('keine Kundendaten eintragen');
    });
});
