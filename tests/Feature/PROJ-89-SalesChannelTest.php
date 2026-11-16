<?php

use App\Analysis\Agents\CaseAgent;
use App\Knowledge\SalesChannels;
use App\Models\Analysis;
use App\Models\Setting;
use App\Models\TrackedTicket;
use App\Settings\KnowledgeValues;
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
        'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel']),
        'policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002', 'title' => 'Shop abgeschaltet', 'sales_channels' => ['qobos']], "Gilt für den früheren Shop.\n\n# Regel\n\nQOBOS-HINWEIS: Bestellung per E-Mail."),
        'policies/policy-003-c.md' => knowledgeDoc(['id' => 'POLICY-003', 'title' => 'Fachhändler-Login', 'sales_channels' => ['fachhaendler']], "Gilt für den Fachhändler-Shop.\n\n# Regel\n\nFACHHAENDLER-HINWEIS: Login unter fachhaendler.looxis.de."),
    ]);

    $GLOBALS['channelTicketFaked'] = false;
});

afterEach(fn () => cleanUpKnowledgeBases());

/**
 * The ticket of the tests: without an order number unless the text names one; the EOCS order is a looxis.de order.
 * Faked once per test (the first fake wins): a test that needs another ticket calls this first.
 */
function channelTicket(string $body = '<p>Ich kann mich nicht anmelden.</p>', string $email = 'erika@example.org'): void
{
    if ($GLOBALS['channelTicketFaked']) {
        return;
    }

    $GLOBALS['channelTicketFaked'] = true;

    fakeZammad([zammadArticle(['id' => 1, 'subject' => 'Login', 'message_id' => '<m1@example.org>', 'body' => $body])], extra: [
        'zammad.test/api/v1/users/77' => Http::response(['firstname' => 'Erika', 'lastname' => 'Beispiel', 'email' => $email]),
    ]);
    Http::fake([
        'eocs.test/api/v1/orders*' => function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            [$number] = explode(',', (string) ($query['filter']['external_order_id'] ?? ''));

            return Http::response(['data' => $number === '7JQ-18SU-DVA4' ? [returnEocsOrder()] : []]);
        },
    ]);
}

function channelAnswer(): array
{
    return [
        'summary' => ['incident' => 'Login', 'customer_wish' => 'Zugang'],
        'category' => 'order-process-question', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Passwort zurücksetzen.', 'actions' => [],
        'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'Regel.', 'knowledge_gaps' => [],
        'knowledge_ids' => [], 'confidence' => ['level' => 'NIEDRIG', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [],
        'reply' => ['language' => 'Deutsch', 'text' => 'Hallo, bitte das Passwort zurücksetzen.'],
    ];
}

function channelRun(array $fields = [])
{
    channelTicket();

    return test()->withCookie('staff_name', 'Nele')->from('/tickets/2137942')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kein_produktbezug' => '1', 'kundengruppe' => 'reseller', 'variante' => 'verlauf', ...$fields]);
}

function ticketPage()
{
    channelTicket();

    return test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942');
}

describe('channels and groups', function () {
    test('the channels of the form follow the active customer groups, "unclear" comes last', function () {
        expect(app(SalesChannels::class)->options())->toBe([
            ['key' => 'looxis-de', 'label' => 'looxis.de', 'group' => 'private-looxis-de'],
            ['key' => 'amazon', 'label' => 'Amazon', 'group' => 'private-amazon'],
            ['key' => 'fachhaendler', 'label' => 'fachhaendler.looxis.de', 'group' => 'reseller'],
            ['key' => 'qobos', 'label' => 'ehemals qobos', 'group' => 'globus'],
            ['key' => 'looxis-pro', 'label' => 'LOOXIS Pro', 'group' => 'looxis-pro'],
            ['key' => 'masterpics', 'label' => 'API (masterpics)', 'group' => 'whitelabel-masterpics'],
            ['key' => 'unclear', 'label' => 'Noch unklar', 'group' => 'unclear'],
        ]);
    });

    test('a channel whose group is switched off can no longer be chosen but keeps its name', function () {
        $groups = array_map(fn (array $group): array => $group['key'] === 'globus' ? [...$group, 'status' => 'inactive'] : $group, app(SettingsStore::class)->value('kn_groups'));
        app(SettingsStore::class)->save('kn_groups', $groups, 'Etienne');
        app(KnowledgeValues::class)->apply();

        expect(app(SalesChannels::class)->keys())->not->toContain('qobos')
            ->and(app(SalesChannels::class)->label('qobos'))->toBe('ehemals qobos');

        channelRun(['verkaufskanal' => 'qobos'])->assertSessionHasErrors('verkaufskanal');
    });

    test('saved lists of channels and groups get the new channel and group', function () {
        Setting::query()->create(['group_key' => 'kn_sales_channels', 'version' => 1, 'changed_by' => 'Etienne', 'value' => [['key' => 'looxis-de', 'label' => 'Unser Shop'], ['key' => 'fachhaendler', 'label' => 'Fachhändler']]]);
        Setting::query()->create(['group_key' => 'kn_groups', 'version' => 1, 'changed_by' => 'Etienne', 'value' => [
            ['key' => 'reseller', 'label' => 'Reseller', 'customer_type' => 'b2b-reseller', 'sales_channel' => 'fachhaendler', 'status' => 'active'],
            ['key' => 'unclear', 'label' => 'Noch unklar', 'customer_type' => null, 'sales_channel' => null, 'status' => 'active'],
        ]]);

        (require database_path('migrations/2026_11_16_143406_add_globus_to_saved_knowledge_values.php'))->up();

        expect(Setting::query()->where('group_key', 'kn_sales_channels')->value('value'))->toBe([['key' => 'looxis-de', 'label' => 'Unser Shop'], ['key' => 'fachhaendler', 'label' => 'fachhaendler.looxis.de'], ['key' => 'qobos', 'label' => 'ehemals qobos']])
            ->and(array_column(Setting::query()->where('group_key', 'kn_groups')->value('value'), 'key'))->toBe(['reseller', 'globus', 'unclear']);
    });
});

describe('the form', function () {
    test('the ticket form asks for the sales channel before the customer group', function () {
        ticketPage()->assertOk()
            ->assertSeeInOrder(['name="verkaufskanal"', 'ehemals qobos', 'API (masterpics)', 'name="kundengruppe"'], false)
            ->assertSee('Globus');
    });

    test('the question form asks for the sales channel too', function () {
        $this->withCookie('staff_name', 'Nele')->get(route('questions.create'))->assertOk()
            ->assertSeeInOrder(['name="verkaufskanal"', 'ehemals qobos', 'name="kundengruppe"'], false);
    });

    test('an empty or unknown channel is rejected and the inputs stay', function (?string $channel, string $message) {
        channelRun(['verkaufskanal' => $channel, 'kontext' => 'Meine Notiz'])
            ->assertRedirect()->assertSessionHasErrors(['verkaufskanal' => $message])->assertSessionHasInput('kontext', 'Meine Notiz');

        expect(Analysis::query()->count())->toBe(0);
    })->with([[null, 'Bitte den Verkaufskanal wählen.'], ['', 'Bitte den Verkaufskanal wählen.'], ['ebay', 'Diesen Verkaufskanal gibt es nicht (mehr). Bitte einen anderen wählen.']]);

    test('a question without a channel is rejected', function () {
        $this->withCookie('staff_name', 'Nele')->post(route('questions.store'), ['text' => 'Frage', 'kein_produktbezug' => '1', 'kundengruppe' => 'reseller', 'verkaufskanal' => ''])
            ->assertSessionHasErrors(['verkaufskanal' => 'Bitte den Verkaufskanal wählen.']);
    });
});

describe('preselection', function () {
    test('without an order and with a neutral sender nothing is preselected', function () {
        ticketPage()->assertOk()->assertSee('<option value="" selected>Bitte wählen</option>', false)->assertDontSee('Vorbelegt: aus');
    });

    test('the channel of the loaded order is preselected together with its group', function () {
        channelTicket('<p>Bestellung 7JQ-18SU-DVA4: Ich kann nicht bezahlen.</p>');

        ticketPage()->assertOk()
            ->assertSee('<option value="looxis-de" selected>looxis.de</option>', false)
            ->assertSee('<option value="private-looxis-de" selected>', false)
            ->assertSee('Vorbelegt: aus dem Kanal der Bestellung');
    });

    test('an amazon marketplace sender preselects amazon', function () {
        channelTicket(email: 'abc123@marketplace.amazon.de');

        ticketPage()->assertOk()
            ->assertSee('<option value="amazon" selected>Amazon</option>', false)
            ->assertSee('<option value="private-amazon" selected>', false)
            ->assertSee('Vorbelegt: aus der Absenderadresse');
    });

    test('the order counts before the sender address', function () {
        channelTicket('<p>Bestellung 7JQ-18SU-DVA4</p>', 'abc123@marketplace.amazon.de');

        ticketPage()->assertOk()->assertSee('<option value="looxis-de" selected>looxis.de</option>', false);
    });

    test('the channel an employee chose for the ticket is preselected again', function () {
        TrackedTicket::factory()->create(['number' => '2137942', 'channel' => 'qobos', 'channel_source' => 'staff']);
        channelTicket('<p>Bestellung 7JQ-18SU-DVA4</p>');

        ticketPage()->assertOk()->assertSee('<option value="qobos" selected>ehemals qobos</option>', false);
    });
});

describe('knowledge', function () {
    test('the chosen channel decides which channel knowledge applies, the customer type comes from the group', function () {
        CaseAgent::fake([channelAnswer()]);

        channelRun(['kundengruppe' => 'globus', 'verkaufskanal' => 'qobos'])->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('QOBOS-HINWEIS') && ! $prompt->contains('FACHHAENDLER-HINWEIS'));
    });

    test('a group that does not belong to the channel is allowed, the channel still decides', function () {
        CaseAgent::fake([channelAnswer()]);

        channelRun(['kundengruppe' => 'reseller', 'verkaufskanal' => 'qobos'])->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('QOBOS-HINWEIS') && ! $prompt->contains('FACHHAENDLER-HINWEIS'));
    });

    test('with the channel "unclear" no channel knowledge applies', function () {
        CaseAgent::fake([channelAnswer()]);

        channelRun(['kundengruppe' => 'reseller', 'verkaufskanal' => 'unclear'])->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => ! $prompt->contains('QOBOS-HINWEIS') && ! $prompt->contains('FACHHAENDLER-HINWEIS'));
    });

    test('a request from before the channel was asked for takes the channel of the group', function () {
        CaseAgent::fake([channelAnswer()]);

        channelRun()->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('FACHHAENDLER-HINWEIS') && ! $prompt->contains('QOBOS-HINWEIS'));
        expect(Analysis::query()->latest()->first()->content['inputs']['verkaufskanal'])->toBe('fachhaendler');
    });

    test('the channel is stored with the proposal and named in the summary of the form', function () {
        CaseAgent::fake([channelAnswer()]);

        channelRun(['kundengruppe' => 'globus', 'verkaufskanal' => 'qobos'])->assertSessionHasNoErrors();

        $content = Analysis::query()->latest()->first()->content;
        expect($content['inputs']['verkaufskanal'])->toBe('qobos')->and($content['meta']['sales_channel'])->toBe('qobos');

        ticketPage()->assertSeeText('Erstellt mit: ehemals qobos · Globus');
    });

    test('a question uses the chosen channel', function () {
        CaseAgent::fake([channelAnswer()]);

        $this->withCookie('staff_name', 'Nele')->post(route('questions.store'), ['text' => 'Wie bestellt der Kunde jetzt?', 'kein_produktbezug' => '1', 'kundengruppe' => 'globus', 'verkaufskanal' => 'qobos'])->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('QOBOS-HINWEIS'));
    });

    test('the knowledge check accepts the new channel in the head of a document', function () {
        $this->artisan('knowledge:check')->assertExitCode(0);
    });
});

describe('the channel with the ticket', function () {
    test('a proposal keeps the chosen channel with the ticket, "unclear" as none', function (string $channel, ?string $stored) {
        TrackedTicket::factory()->create(['number' => '2137942', 'channel' => 'amazon', 'channel_source' => 'sender']);
        CaseAgent::fake([channelAnswer()]);

        channelRun(['verkaufskanal' => $channel])->assertSessionHasNoErrors();

        expect(TrackedTicket::query()->where('number', '2137942')->first())->channel->toBe($stored)->channel_source->toBe('staff');
    })->with([['qobos', 'qobos'], ['unclear', null]]);

    test('opening the ticket keeps the recognised channel', function () {
        TrackedTicket::factory()->create(['number' => '2137942']);
        channelTicket('<p>Bestellung 7JQ-18SU-DVA4</p>');

        ticketPage()->assertOk();

        expect(TrackedTicket::query()->where('number', '2137942')->first())->channel->toBe('looxis-de')->channel_source->toBe('order');
    });

    test('a recognised channel never replaces the choice of an employee', function () {
        TrackedTicket::factory()->create(['number' => '2137942', 'channel' => 'qobos', 'channel_source' => 'staff']);
        channelTicket('<p>Bestellung 7JQ-18SU-DVA4</p>');

        ticketPage()->assertOk();

        expect(TrackedTicket::query()->where('number', '2137942')->first())->channel->toBe('qobos')->channel_source->toBe('staff');
    });

    test('an employee who chose "unclear" is not overruled by the sender address', function () {
        TrackedTicket::factory()->create(['number' => '2137942', 'channel' => null, 'channel_source' => 'staff']);
        channelTicket(email: 'abc123@marketplace.amazon.de');

        ticketPage()->assertOk();

        expect(TrackedTicket::query()->where('number', '2137942')->value('channel'))->toBeNull();
    });

    test('the overview shows the name of the channel and filters by it', function () {
        TrackedTicket::factory()->create(['number' => '2140500', 'channel' => 'qobos', 'channel_source' => 'staff']);
        TrackedTicket::factory()->create(['number' => '2140501']);

        $this->withCookie('staff_name', 'Nele')->get('/?ansicht=alle')->assertOk()
            ->assertSee('<option value="qobos" >ehemals qobos</option>', false)
            ->assertSee('>ehemals qobos</td>', false);

        $this->withCookie('staff_name', 'Nele')->get('/?ansicht=alle&kanal=qobos')->assertOk()->assertSee('2140500')->assertDontSee('2140501');
    });
});
