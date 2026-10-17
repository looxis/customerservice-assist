<?php

use App\Models\Setting;
use App\Models\SettingVersion;
use App\Settings\MailDefaults;
use App\Settings\MailRules;
use App\Settings\PatternBuilder;
use App\Settings\SettingsRegistry;
use App\Settings\SettingsStore;
use App\Zammad\AmazonNotice;
use Dom\HTMLDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'cache.default' => 'array',
        'staff.admins' => ['Etienne'],
    ]);
});

function asAdmin()
{
    return test()->withCookie('staff_name', 'Etienne');
}

function asStaff()
{
    return test()->withCookie('staff_name', 'Nele');
}

/**
 * The values of every group as the form sends them, with changes on top.
 *
 * @param  array<string, mixed>  $changes
 * @return array<string, mixed>
 */
function settingsInput(array $changes = []): array
{
    $settings = collect(SettingsRegistry::forPage('mail'))->map(fn ($group) => app(SettingsStore::class)->value($group->key))->all();
    $versions = collect(SettingsRegistry::forPage('mail'))->map(fn ($group) => app(SettingsStore::class)->version($group->key))->all();

    return ['settings' => [...$settings, ...$changes], 'versions' => $versions];
}

describe('the pages', function () {
    test('the sidebar has the entry and the overview lists the mail display', function () {
        asStaff()->get(route('tickets.analyze'))->assertSee(route('settings.index'), false)->assertSeeText('Einstellungen');
        asStaff()->get(route('settings.index'))->assertOk()->assertSeeText('Mail-Anzeige')->assertSee(route('settings.mail'), false);
    });

    test('everyone can look at the rules with their current values, but not change them', function () {
        $html = asStaff()->get(route('settings.mail'))->assertOk()->assertSeeText('Zugelassene Adressen')->assertSeeText('Grußformeln')->assertSeeText('Standard der App')
            ->assertSeeText('Du kannst die Regeln ansehen; ändern dürfen nur Admins.')->assertDontSeeText('Mit Ticket ausprobieren')->getContent();

        expect($html)->not->toContain('>Speichern<')->toContain('looxis.de')->toContain('Dieser Service wird ausschließlich');
    });

    test('an admin sees the save button and the try-out box', function () {
        asAdmin()->get(route('settings.mail'))->assertOk()->assertSeeText('Speichern')->assertSeeText('Mit Ticket ausprobieren')->assertSeeText('Du bist Admin und kannst ändern');
    });

    test('someone who is no admin cannot save, restore, reset or try out', function () {
        asStaff()->put(route('settings.mail.update'), settingsInput())->assertForbidden();
        asStaff()->post(route('settings.reset', ['group' => 'mail_footers']))->assertForbidden();
        asStaff()->post(route('settings.restore', ['group' => 'mail_footers', 'version' => 1]))->assertForbidden();
        asStaff()->post(route('settings.mail.try'), [...settingsInput(), 'ticket' => '1'])->assertForbidden();

        expect(Setting::query()->count())->toBe(0);
    });
});

describe('saving', function () {
    test('without a change nothing is saved and the defaults are the former values', function () {
        asAdmin()->put(route('settings.mail.update'), settingsInput())->assertRedirect(route('settings.mail'))->assertSessionHas('saved', 0);

        expect(Setting::query()->count())->toBe(0)->and(SettingVersion::query()->count())->toBe(0)
            ->and(app(SettingsStore::class)->value('mail_footers'))->toBe(config('services.zammad.footers'))
            ->and(app(SettingsStore::class)->value('mail_hosts'))->toContain(['host' => 'looxis.de', 'mode' => 'text'], ['host' => 'sellercentral.amazon.*', 'mode' => 'clickable']);
    });

    test('a change is saved as a new version with name and times and applies at once', function () {
        $footers = [...MailDefaults::footers(), 'Dieser Hinweis steht in jeder Mail ganz unten'];

        asAdmin()->put(route('settings.mail.update'), settingsInput(['mail_footers' => $footers]))->assertRedirect(route('settings.mail'))->assertSessionHas('saved', 1);

        $version = SettingVersion::query()->sole();
        expect($version->group_key)->toBe('mail_footers')->and($version->version)->toBe(1)->and($version->kind)->toBe('gespeichert')->and($version->changed_by)->toBe('Etienne')->and($version->value)->toBe($footers)
            ->and(Setting::query()->sole()->value)->toBe($footers)
            ->and(app(SettingsStore::class)->value('mail_footers'))->toBe($footers);

        asStaff()->get(route('settings.mail'))->assertSeeText('geändert von Etienne')->assertSee('Dieser Hinweis steht in jeder Mail ganz unten');
    });

    test('a saved footer hides the rest of a mail in the ticket view', function () {
        fakeZammad([zammadArticle(['id' => 1, 'body' => '<p>Meine Frage zur Tasse.</p><p>Hinweistext des Shops ganz unten: nicht lesen</p><p>Rest des Fußes</p>'])]);
        asStaff()->get('/tickets/2137942')->assertSeeText('Rest des Fußes');

        asAdmin()->put(route('settings.mail.update'), settingsInput(['mail_footers' => [...MailDefaults::footers(), 'Hinweistext des Shops ganz unten']]));

        asStaff()->get('/tickets/2137942')->assertSeeText('Meine Frage zur Tasse.')->assertDontSeeText('Rest des Fußes');
    });

    test('a value equal to the default removes the stored value and the history keeps counting', function () {
        asAdmin()->put(route('settings.mail.update'), settingsInput(['mail_signature_lines' => 4]));
        expect(Setting::query()->count())->toBe(1);

        asAdmin()->put(route('settings.mail.update'), settingsInput(['mail_signature_lines' => 2]));

        expect(Setting::query()->count())->toBe(0)->and(SettingVersion::query()->count())->toBe(2)->and(SettingVersion::query()->orderByDesc('version')->first()->kind)->toBe('standard')
            ->and(app(SettingsStore::class)->value('mail_signature_lines'))->toBe(2);
    });

    test('entries are cleaned: blanks and duplicates go and the user is told', function () {
        asAdmin()->put(route('settings.mail.update'), settingsInput(['mail_footers' => ['  Ein langer Fußtext der passt  ', '', 'ein LANGER Fußtext der passt', 'Noch ein ganz anderer Fußtext']]))->assertSessionHas('notes');

        expect(app(SettingsStore::class)->value('mail_footers'))->toBe(['Ein langer Fußtext der passt', 'Noch ein ganz anderer Fußtext']);
    });

    test('invalid input saves nothing, marks the group and keeps what was typed', function (array $changes, string $key) {
        asAdmin()->from(route('settings.mail'))->put(route('settings.mail.update'), settingsInput(['mail_signature_lines' => 4, ...$changes]))->assertRedirect(route('settings.mail'))->assertSessionHasErrors("settings.{$key}");

        expect(Setting::query()->count())->toBe(0);
    })->with([
        'footer too short' => [['mail_footers' => ['kurz']], 'mail_footers'],
        'closing too short' => [['mail_closings' => ['x']], 'mail_closings'],
        'quote header without fixed text' => [['mail_quote_headers' => ['… …:']], 'mail_quote_headers'],
        'empty amazon intros' => [['mail_amazon_intros' => []], 'mail_amazon_intros'],
        'host with a path' => [['mail_hosts' => [['host' => 'looxis.de/pfad', 'mode' => 'text']]], 'mail_hosts'],
        'wildcard in the middle' => [['mail_hosts' => [['host' => 'a.*.de', 'mode' => 'text']]], 'mail_hosts'],
        'host without a mode' => [['mail_hosts' => [['host' => 'looxis.de', 'mode' => '']]], 'mail_hosts'],
        'too many lines' => [['mail_signature_lines' => 9], 'mail_signature_lines'],
        'marker with markup' => [['mail_quote_markers' => ['<script>']], 'mail_quote_markers'],
        'entry too long' => [['mail_footers' => [str_repeat('x', 301)]], 'mail_footers'],
    ]);

    test('a group someone else saved meanwhile is not overwritten', function () {
        $opened = settingsInput(['mail_signature_lines' => 3]);

        asAdmin()->put(route('settings.mail.update'), settingsInput(['mail_signature_lines' => 5]));
        $response = asAdmin()->from(route('settings.mail'))->put(route('settings.mail.update'), $opened);

        $response->assertSessionHasErrors('settings.mail_signature_lines');
        expect(session('errors')->first('settings.mail_signature_lines'))->toContain('inzwischen geändert von Etienne')
            ->and(app(SettingsStore::class)->value('mail_signature_lines'))->toBe(5);
    });

    test('the page without the database falls back on the defaults', function () {
        Schema::drop('settings');

        expect(app(SettingsStore::class)->value('mail_footers'))->toBe(MailDefaults::footers());
    });
});

describe('history, restore and reset', function () {
    test('an earlier version can be restored as a new one and the default can be reset to', function () {
        asAdmin()->put(route('settings.mail.update'), settingsInput(['mail_signature_lines' => 4]));
        asAdmin()->put(route('settings.mail.update'), settingsInput(['mail_signature_lines' => 1]));

        asAdmin()->post(route('settings.restore', ['group' => 'mail_signature_lines', 'version' => 1]))->assertRedirect(route('settings.mail'))->assertSessionHas('restored');
        expect(app(SettingsStore::class)->value('mail_signature_lines'))->toBe(4)->and(SettingVersion::query()->count())->toBe(3)->and(SettingVersion::query()->orderByDesc('version')->first()->kind)->toBe('wiederhergestellt');

        asAdmin()->post(route('settings.reset', ['group' => 'mail_signature_lines']))->assertRedirect(route('settings.mail'));
        expect(app(SettingsStore::class)->value('mail_signature_lines'))->toBe(2)->and(Setting::query()->count())->toBe(0)->and(SettingVersion::query()->count())->toBe(4);

        asStaff()->get(route('settings.mail'))->assertSeeText('Verlauf (4)')->assertSeeText('Version 1')->assertSeeText('auf Standard zurückgesetzt');
    });

    test('an unknown group or version is not found', function () {
        asAdmin()->post(route('settings.reset', ['group' => 'nope']))->assertNotFound();
        asAdmin()->post(route('settings.restore', ['group' => 'mail_footers', 'version' => 7]))->assertNotFound();
    });
});

describe('trying out', function () {
    test('it shows the ticket with saved and typed-in rules next to each other and saves nothing', function () {
        fakeZammad([zammadArticle(['id' => 1, 'body' => '<p>Meine Frage.</p><p>Hinweistext ganz unten im Fuß</p><p>Nach dem Fuß</p>'])]);

        $response = asAdmin()->post(route('settings.mail.try'), [...settingsInput(['mail_footers' => [...MailDefaults::footers(), 'Hinweistext ganz unten im Fuß']]), 'ticket' => '2137942'])->assertRedirect(route('settings.mail'));

        $trial = session('trial');
        expect($trial['messages'])->toHaveCount(1)->and($trial['messages'][0]['same'])->toBeFalse()
            ->and($trial['messages'][0]['saved']['body'])->toContain('Nach dem Fuß')->and($trial['messages'][0]['draft']['body'])->not->toContain('Nach dem Fuß')->and($trial['messages'][0]['draft']['body'])->toContain('Meine Frage.');
        expect(Setting::query()->count())->toBe(0)->and(SettingVersion::query()->count())->toBe(0);

        asAdmin()->get(route('settings.mail'))->assertOk();
    });

    test('an unchanged rule set shows no difference', function () {
        fakeZammad([zammadArticle(['id' => 1, 'body' => '<p>Nur eine Frage.</p>'])]);

        asAdmin()->post(route('settings.mail.try'), [...settingsInput(), 'ticket' => '2137942']);

        expect(session('trial')['messages'][0]['same'])->toBeTrue();
    });

    test('a ticket that does not exist, a wrong number and an invalid rule are reported', function () {
        Http::preventStrayRequests();
        Http::fake(['zammad.test/api/v1/tickets/search*' => Http::response([])]);

        asAdmin()->post(route('settings.mail.try'), [...settingsInput(), 'ticket' => '99999999'])->assertRedirect(route('settings.mail'));
        expect(session('trial')['error'])->toContain('99999999');

        asAdmin()->from(route('settings.mail'))->post(route('settings.mail.try'), [...settingsInput(), 'ticket' => 'abc'])->assertSessionHasErrors('ticket');
        asAdmin()->from(route('settings.mail'))->post(route('settings.mail.try'), [...settingsInput(['mail_footers' => ['kurz']]), 'ticket' => '1'])->assertSessionHasErrors('settings.mail_footers');
    });
});

describe('the patterns made from the lists', function () {
    test('closing phrases match with umlaut spellings, case and a trailing name', function () {
        $pattern = '/^(?:'.implode('|', PatternBuilder::phrases(['Viele Grüße', 'mit freundlichem Gruß'])).')[\s,.!]*(?:[\p{L}][\p{L} .\'-]{0,30})?$/iu';

        foreach (['Viele Grüße', 'viele gruesse', 'VIELE GRUESSE,', 'Viele Grüße Anna', 'mit freundlichem Gruss'] as $line) {
            expect(preg_match($pattern, $line))->toBe(1, $line);
        }

        expect(preg_match($pattern, 'Viele Dank'))->toBe(0);
    });

    test('quote headers use the wildcard and ignore the spacing around it', function () {
        $pattern = PatternBuilder::quoteHeaders(['Am … schrieb …:', '-- Original Message --', 'Von: … Gesendet:']);

        foreach (['Am 12.10.2026 um 10:15 schrieb Anna <a@example.org>:', 'am 1.1. schrieb:', '----- Original Message -----', '--Original Message--', 'Von: Anna Gesendet: Montag'] as $line) {
            expect(preg_match($pattern, $line))->toBe(1, $line);
        }

        foreach (['Ich schrieb gestern an Sie', 'Am Montag war ich krank'] as $line) {
            expect(preg_match($pattern, $line))->toBe(0, $line);
        }
    });

    test('an entry is text and never a pattern', function () {
        $pattern = PatternBuilder::quoteHeaders(['(.*) wrote: [x]']);

        expect(preg_match($pattern, 'foo wrote: [x]'))->toBe(0)->and(preg_match($pattern, '(.*) wrote: [x]'))->toBe(1);
        expect(preg_match(PatternBuilder::quoteHeaders([]), 'irgendwas'))->toBe(0);
    });

    test('the amazon words can be changed', function () {
        $html = '<html><body><p>Neue Nachricht von Käufer</p><p>Bestellreferenz: 402-1234567-1234567</p><p>Text des Käufers</p></body></html>';
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);

        expect((new AmazonNotice)->extract($document))->toBeNull();

        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        $order = (new AmazonNotice(['Neue Nachricht von Käufer'], ['Bestellreferenz']))->extract($document);

        expect($order?->number)->toBe('402-1234567-1234567');
    });

    test('the rules from the saved settings reach the cleaning', function () {
        fakeZammad([zammadArticle(['id' => 1, 'body' => '<p>Meine Frage.</p><p>Beste Wünsche</p><p>Anna Muster</p><p>Telefon 0123 456</p><p>Firma GmbH</p>'])]);
        asStaff()->get('/tickets/2137942')->assertSeeText('Telefon 0123 456');

        asAdmin()->put(route('settings.mail.update'), settingsInput(['mail_closings' => [...MailDefaults::closings(), 'Beste Wünsche'], 'mail_signature_lines' => 1]));

        $html = asStaff()->get('/tickets/2137942')->assertOk()->getContent();
        expect($html)->toContain('Anna Muster')->and(app(MailRules::class)->messageBody()->parse('<p>Frage</p><p>Beste Wünsche</p><p>Anna</p><p>Telefon 1</p>', 'text/html')['signature'])->not->toBeNull();
    });
});
