<?php

use App\Http\Middleware\EnsureStaffSelected;
use App\Staff\StaffDirectory;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->withoutVite();
});

/**
 * The header part of a page.
 */
function headerOf(string $html): string
{
    preg_match('/<header\b.*?<\/header>/s', $html, $header);

    return $header[0];
}

describe('name list', function () {
    test('the list holds exactly the six names in alphabetical order', function () {
        expect(app(StaffDirectory::class)->names())->toBe(['Cara', 'Etienne', 'Johannes', 'Kerstin', 'Nele', 'Thomas']);
    });

    test('the picker offers the configured names in alphabetical order, without "Andere" or a text field', function () {
        $header = headerOf($this->get('/')->getContent());

        expect($header)->toMatch('/value="Cara".*value="Etienne".*value="Johannes".*value="Kerstin".*value="Nele".*value="Thomas"/s')
            ->not->toContain('Andere')
            ->not->toContain('type="text"');
    });

    test('a changed configuration changes the list without code changes, sorted and without duplicates', function () {
        config(['staff.names' => ['Zoe', 'Ömer', 'anna', 'Zoe', ' ', 'Bea']]);

        expect(app(StaffDirectory::class)->names())->toBe(['anna', 'Bea', 'Ömer', 'Zoe']);
    });

    test('an empty list shows "Keine Namen hinterlegt" and no picker', function () {
        config(['staff.names' => []]);

        $header = headerOf($this->get('/')->getContent());

        expect($header)->toContain('Keine Namen hinterlegt')
            ->not->toContain('Name wählen');
    });
});

describe('first choice', function () {
    test('without a chosen name every page shows "Name wählen" in the header', function (string $url) {
        $header = headerOf($this->get($url)->assertOk()->getContent());

        expect($header)->toContain('aria-label="Name wählen"')
            ->toContain('>Name wählen</span>');
    })->with(['/', '/knowledge', '/ueber-die-app']);

    test('"Ticket analysieren" shows the hint with the name list', function () {
        $this->get('/')
            ->assertSeeText('Bitte wähle zuerst deinen Namen.')
            ->assertSeeInOrder(['Bitte wähle zuerst deinen Namen.', 'value="Cara"', 'value="Thomas"'], false);
    });

    test('knowledge and about are usable without a name and show no hint', function (string $url) {
        $this->get($url)->assertOk()->assertDontSeeText('Bitte wähle zuerst deinen Namen.');
    })->with(['/knowledge', '/ueber-die-app']);
});

describe('choosing and remembering', function () {
    test('choosing a name in the background answers with the name and sets a long-lived cookie', function () {
        $response = $this->postJson(route('staff.select'), ['name' => 'Nele'])
            ->assertOk()
            ->assertExactJson(['name' => 'Nele'])
            ->assertCookie('staff_name', 'Nele');

        $cookie = collect($response->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === 'staff_name');

        expect($cookie->getExpiresTime())->toBeGreaterThan(now()->addYears(4)->getTimestamp())
            ->and($cookie->isHttpOnly())->toBeTrue();
    });

    test('without javascript the form returns to the page it came from', function () {
        $this->from('/knowledge')
            ->post(route('staff.select'), ['name' => 'Cara'])
            ->assertRedirect('/knowledge')
            ->assertCookie('staff_name', 'Cara');
    });

    test('a foreign origin is never used as the way back', function (string $referer) {
        $this->post(route('staff.select'), ['name' => 'Cara'], ['Referer' => str_replace('{app}', url('/'), $referer)])
            ->assertRedirect(route('tickets.analyze'));
    })->with(['other site' => ['https://evil.example/phish'], 'own host as prefix' => ['{app}.evil.example/'], 'protocol relative' => ['//evil.example/']]);

    test('an address of the app is used as the way back', function () {
        $this->post(route('staff.select'), ['name' => 'Cara'], ['Referer' => route('knowledge.index').'?type=policy'])
            ->assertRedirect(route('knowledge.index').'?type=policy');
    });

    test('the cookie is encrypted', function () {
        $this->postJson(route('staff.select'), ['name' => 'Nele'])
            ->assertCookieNotExpired('staff_name')
            ->assertCookie('staff_name', 'Nele', encrypted: true);
    });

    test('with a chosen name every page shows it in the header and the hint is gone', function (string $url) {
        $html = $this->withCookie('staff_name', 'Kerstin')->get($url)->assertOk()->getContent();
        $header = headerOf($html);

        expect($header)->toContain('aria-label="Angemeldet als Kerstin – Name wechseln"')
            ->toContain('>Kerstin</span>')
            ->toContain('>K</span>')
            ->toContain('$store.staff.name = \'Kerstin\'')
            ->and($html)->not->toContain('Bitte wähle zuerst deinen Namen.');
    })->with(['/', '/knowledge', '/ueber-die-app']);

    test('the current name is marked in the list', function () {
        $header = headerOf($this->withCookie('staff_name', 'Kerstin')->get('/')->getContent());

        expect($header)->toMatch('/value="Kerstin"\s+x-bind:aria-pressed="[^"]+"\s+aria-pressed="true"/')
            ->toMatch('/value="Nele"\s+x-bind:aria-pressed="[^"]+"\s+aria-pressed="false"/');
    });

    test('a name that is no longer listed counts as no name', function () {
        config(['staff.names' => ['Cara', 'Etienne']]);

        $html = $this->withCookie('staff_name', 'Kerstin')->get('/')->getContent();

        expect(headerOf($html))->toContain('aria-label="Name wählen"')
            ->and($html)->toContain('Bitte wähle zuerst deinen Namen.');
    });

    test('a name outside the list is rejected and changes nothing', function (mixed $name) {
        $this->postJson(route('staff.select'), ['name' => $name])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name')
            ->assertCookieMissing('staff_name');
    })->with(['unknown' => ['Mallory'], 'empty' => [''], 'case differs' => ['nele'], 'array' => [['Nele']], 'script' => ['<script>alert(1)</script>']]);

    test('both forms carry a csrf token', function () {
        $html = $this->get('/')->getContent();

        preg_match_all('/<form\b[^>]*action="[^"]*\/name"[^>]*>\s*<input type="hidden" name="_token"/s', $html, $forms);

        expect($forms[0])->toHaveCount(2);
    });

    test('the choice only answers to POST', function () {
        $this->get('/name')->assertMethodNotAllowed();
    });
});

describe('guard for later actions', function () {
    beforeEach(function () {
        Route::middleware(['web', 'staff.selected'])->post('/_test/analysis', fn () => 'analysiert von '.app(StaffDirectory::class)->current(request()));
        Route::middleware(['web'])->get('/_test/form', fn () => 'Formular');
    });

    test('with a chosen name the action runs and knows the name', function () {
        $this->withCookie('staff_name', 'Thomas')
            ->post('/_test/analysis', ['ticket' => '123'])
            ->assertOk()
            ->assertSeeText('analysiert von Thomas');
    });

    test('without a name the action is not run, the user goes back with input and the hint', function () {
        $this->from('/_test/form')
            ->post('/_test/analysis', ['ticket' => '123', 'context' => 'Kundin schreibt …'])
            ->assertRedirect('/_test/form')
            ->assertSessionHas('error', EnsureStaffSelected::MESSAGE)
            ->assertSessionHasInput('context', 'Kundin schreibt …');
    });

    test('without a name a foreign origin is not used as the way back', function () {
        $this->post('/_test/analysis', ['ticket' => '123'], ['Referer' => 'https://evil.example/phish'])
            ->assertRedirect(route('tickets.analyze'))
            ->assertSessionHas('error', EnsureStaffSelected::MESSAGE)
            ->assertSessionHasInput('ticket', '123');
    });

    test('a name that is no longer listed is treated as no name', function () {
        config(['staff.names' => ['Cara']]);

        $this->withCookie('staff_name', 'Thomas')
            ->from('/_test/form')
            ->post('/_test/analysis')
            ->assertRedirect('/_test/form')
            ->assertSessionHas('error', EnsureStaffSelected::MESSAGE);
    });

    test('a background request without a name gets a clear answer', function () {
        $this->postJson('/_test/analysis')
            ->assertStatus(409)
            ->assertExactJson(['message' => EnsureStaffSelected::MESSAGE]);
    });

    test('the hint after redirect is shown on the page', function () {
        $this->withSession(['error' => EnsureStaffSelected::MESSAGE])
            ->get('/knowledge')
            ->assertSeeText(EnsureStaffSelected::MESSAGE);
    });
});
