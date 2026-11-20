<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\AnalysisStore;
use App\Knowledge\SalesChannels;
use App\Models\Analysis;
use App\Models\TrackedTicket;
use App\Settings\KnowledgeValues;
use App\Settings\SettingsRegistry;
use App\Settings\SettingsStore;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Prompts\AgentPrompt;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
    ]);

    knowledgeBase([
        'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'], "Gilt für alle.\n\n# Regel\n\nALLGEMEIN-HINWEIS: freundlich bleiben."),
        'policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002', 'title' => 'Nur Privatkunden', 'customer_types' => ['b2c']], "Gilt für Privatkunden.\n\n# Regel\n\nPRIVAT-HINWEIS: Widerruf 14 Tage."),
    ]);
});

afterEach(fn () => cleanUpKnowledgeBases());

/**
 * A ticket from the contact form of WeCreateYour.com (as ticket 2140510); the text names a number that looks like an order.
 */
function wcyTicket(string $title = 'Contact Form @ WeCreateYour.com'): void
{
    fakeZammad([zammadArticle(['id' => 1, 'subject' => $title, 'message_id' => '<m1@example.org>', 'body' => '<p>Bestellung 7JQ-18SU-DVA4: Die Dienstleistung ist noch nicht fertig.</p>'])], ['title' => $title]);
    Http::fake(['eocs.test/api/v1/orders*' => Http::response(['data' => [returnEocsOrder()]])]);
}

function wcyEocsCalls(): int
{
    return Http::recorded(fn (Request $request): bool => str_contains($request->url(), 'eocs.test'))->count();
}

function wcyRun(array $fields = [])
{
    return test()->withCookie('staff_name', 'Nele')->from('/tickets/2137942')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kundengruppe' => 'wecreateyour', 'verkaufskanal' => 'wecreateyour', 'variante' => 'verlauf', ...$fields]);
}

function wcyAnswer(): array
{
    return [
        'summary' => ['incident' => 'Dienstleistung', 'customer_wish' => 'Fertigstellung'],
        'category' => 'order-process-question', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Stand mitteilen.', 'actions' => [],
        'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'Regel.', 'knowledge_gaps' => [],
        'knowledge_ids' => [], 'confidence' => ['level' => 'NIEDRIG', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [],
        'reply' => ['language' => 'Deutsch', 'text' => 'Hallo, wir melden uns mit dem Stand.'],
    ];
}

function wcySaveChannels(array $rows)
{
    $store = app(SettingsStore::class);
    $settings = [];

    foreach (SettingsRegistry::forPage('values') as $key => $group) {
        $settings[$key] = $store->value($key);
    }

    return test()->withCookie('staff_name', 'Etienne')->put(route('settings.values.update'), [
        'settings' => [...$settings, 'kn_sales_channels' => $rows],
        'versions' => collect(SettingsRegistry::forPage('values'))->map(fn ($group) => $store->version($group->key))->all(),
    ]);
}

function wcyChannelRows(callable $change): array
{
    return array_map(fn (array $row): array => $row['key'] === 'wecreateyour' ? $change($row) : $row, app(SettingsStore::class)->value('kn_sales_channels'));
}

describe('the channel profile', function () {
    test('WeCreateYour is a channel without products with a subject keyword and a note for the model', function () {
        $channels = app(SalesChannels::class);

        expect($channels->withoutProducts('wecreateyour'))->toBeTrue()
            ->and($channels->withoutProducts('looxis-de'))->toBeFalse()
            ->and($channels->fromSubject('Contact Form @ WeCreateYour.com'))->toBe('wecreateyour')
            ->and($channels->fromSubject('Login geht nicht'))->toBeNull()
            ->and($channels->aiNote('wecreateyour'))->toContain('WeCreateYour.com')->toContain('digitale Dienstleistungen')
            ->and($channels->aiNote('looxis-de'))->toBe('');
    });

    test('the settings keep the fields and check their length', function () {
        wcySaveChannels(wcyChannelRows(fn (array $row): array => [...$row, 'ai_note' => str_repeat('x', 2001)]))->assertSessionHasErrors('settings.kn_sales_channels');
        wcySaveChannels(wcyChannelRows(fn (array $row): array => [...$row, 'subject_keywords' => 'wecreateyour, ab']))->assertSessionHasErrors('settings.kn_sales_channels');

        wcySaveChannels(wcyChannelRows(fn (array $row): array => [...$row, 'subject_keywords' => 'wecreateyour, wcy-portal', 'ai_note' => 'Neuer Hinweis.']))->assertSessionHasNoErrors();
        app(KnowledgeValues::class)->apply();

        expect(app(SalesChannels::class)->aiNote('wecreateyour'))->toBe('Neuer Hinweis.')
            ->and(app(SalesChannels::class)->fromSubject('Anfrage aus dem WCY-Portal'))->toBe('wecreateyour');
    });

    test('a channel saved before the profile existed keeps its default profile', function () {
        $rows = array_map(fn (array $row): array => ['key' => $row['key'], 'label' => $row['label']], app(SettingsStore::class)->value('kn_sales_channels'));
        app(SettingsStore::class)->save('kn_sales_channels', $rows, 'Etienne');
        app(KnowledgeValues::class)->apply();

        expect(app(SalesChannels::class)->withoutProducts('wecreateyour'))->toBeTrue();
    });
});

describe('the ticket page', function () {
    test('a WeCreateYour subject asks EOCS nothing, preselects the channel and offers no forms for goods', function () {
        wcyTicket();

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()
            ->assertSee('<option value="wecreateyour" selected>WeCreateYour</option>', false)
            ->assertSeeText('Verkaufskanal „WeCreateYour“: keine Bestellungen in EOCS')
            ->assertDontSeeText('Im Ticket wurde keine Bestellnummer gefunden')
            ->assertSee('digital: true', false)
            ->assertSee('Dieser Verkaufskanal hat keine Produkte und keine Bestellungen');

        expect(wcyEocsCalls())->toBe(0);
    });

    test('an employee who chose another channel gets the orders again', function () {
        TrackedTicket::factory()->create(['number' => '2137942', 'channel' => 'looxis-de', 'channel_source' => 'staff']);
        wcyTicket();

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()
            ->assertDontSeeText('keine Bestellungen in EOCS')
            ->assertSee('digital: false', false);

        expect(wcyEocsCalls())->toBeGreaterThan(0);
    });

    test('a deactivated WeCreateYour group makes the ticket an ordinary one again, even with a stored choice', function () {
        TrackedTicket::factory()->create(['number' => '2137942', 'channel' => 'wecreateyour', 'channel_source' => 'staff']);
        $groups = array_map(fn (array $group): array => $group['key'] === 'wecreateyour' ? [...$group, 'status' => 'inactive'] : $group, app(SettingsStore::class)->value('kn_groups'));
        app(SettingsStore::class)->save('kn_groups', $groups, 'Etienne');
        app(KnowledgeValues::class)->apply();
        wcyTicket();

        expect(app(SalesChannels::class)->withoutProducts('wecreateyour'))->toBeFalse();
        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()
            ->assertDontSeeText('keine Bestellungen in EOCS')
            ->assertSee('digital: false', false);

        expect(wcyEocsCalls())->toBeGreaterThan(0);
    });

    test('an ordinary ticket is unchanged', function () {
        wcyTicket('Login geht nicht');

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()->assertDontSeeText('keine Bestellungen in EOCS');

        expect(wcyEocsCalls())->toBeGreaterThan(0);
    });
});

describe('the proposal', function () {
    test('needs no product, loads no order and tells the model the context of the channel', function () {
        wcyTicket();
        CaseAgent::fake([wcyAnswer()]);

        wcyRun(['bestellungen' => ['7JQ-18SU-DVA4'], 'produkte' => ['magic-mug'], 'bestellung_nummer' => '123'])->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('# Verkaufskanal')
            && $prompt->contains('Serviceplattform WeCreateYour.com')
            && $prompt->contains('ALLGEMEIN-HINWEIS')
            && ! $prompt->contains('PRIVAT-HINWEIS')
            && ! $prompt->contains('Bestellung 7JQ-18SU-DVA4 (aus EOCS)'));
        $content = Analysis::query()->latest()->first()->content;
        expect(wcyEocsCalls())->toBe(0)
            ->and($content['inputs']['produkte'] ?? [])->toBe([])
            ->and($content['inputs']['bestellung_nummer'] ?? null)->toBeNull()
            ->and($content['meta']['channel_note'])->toContain('WeCreateYour.com');
    });

    test('a form for goods is refused, technical support and free text are allowed', function () {
        wcyTicket();
        CaseAgent::fake([wcyAnswer(), wcyAnswer()]);

        wcyRun(['formular' => 'reklamation', 'reklamation_berechtigt' => 'ja'])->assertSessionHasErrors('formular');
        wcyRun(['formular' => 'neuversand', 'neuversand_kostenpflichtig' => 'nein'])->assertSessionHasErrors('formular');
        wcyRun(['formular' => 'support', 'support_loesung' => 'unklar'])->assertSessionHasNoErrors();
        wcyRun(['formular' => 'frei'])->assertSessionHasNoErrors();
    });

    test('an empty note sends no section about the channel', function () {
        wcySaveChannels(wcyChannelRows(fn (array $row): array => [...$row, 'ai_note' => '']))->assertSessionHasNoErrors();
        app(KnowledgeValues::class)->apply();
        wcyTicket();
        CaseAgent::fake([wcyAnswer()]);

        wcyRun()->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => ! $prompt->contains('# Verkaufskanal'));
    });

    test('another channel still needs a product answer', function () {
        wcyTicket();

        wcyRun(['kundengruppe' => 'private-looxis-de', 'verkaufskanal' => 'looxis-de'])->assertSessionHasErrors('produkte');
    });

    test('documents bound to a product count as out of the channel, not as a missing product', function () {
        config(['knowledge.retrieval.active' => true, 'knowledge.router.active' => false, 'cache.default' => 'array']);
        knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel']),
            'products/magic-mug.md' => knowledgeDoc(['id' => 'PRODUCT-002', 'type' => 'product', 'title' => 'Zaubertasse', 'products' => ['magic-mug']]),
        ]);
        wcyTicket();
        CaseAgent::fake([wcyAnswer()]);

        $location = (string) wcyRun()->assertSessionHasNoErrors()->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $scope = collect(app(AnalysisStore::class)->result($query['analyse'])['retrieval']['out_of_scope'])->keyBy('id');

        expect($scope['PRODUCT-002']['cause'])->toBe('channel');
        $this->withCookie('staff_name', 'Nele')->get($location)->assertOk()
            ->assertDontSeeText('Kein Produktwissen berücksichtigt');
    });

    test('a question with the channel WeCreateYour needs no product', function () {
        CaseAgent::fake([wcyAnswer()]);

        $this->withCookie('staff_name', 'Nele')->post(route('questions.store'), ['text' => 'Wie lange dauert die Dienstleistung?', 'kundengruppe' => 'wecreateyour', 'verkaufskanal' => 'wecreateyour'])->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Serviceplattform WeCreateYour.com'));
    });
});
