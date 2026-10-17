<?php

use App\Analysis\Agents\CaseAgent;
use App\Models\Analysis;
use App\Models\Setting;
use App\Orders\OrderFormats;
use App\Orders\OrderNumberDetector;
use App\Settings\SettingsRegistry;
use App\Settings\SettingsStore;
use App\Zammad\OrderMention;
use App\Zammad\Ticket;
use App\Zammad\ZammadClient;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();
    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test', 'cache.default' => 'array', 'staff.admins' => ['Etienne'],
    ]);
});

function claimOrders(string $text): array
{
    return array_map(fn ($number) => [$number->value, $number->format->key, $number->claims], app(OrderNumberDetector::class)->inText($text));
}

describe('recognising claim numbers', function () {
    test('R1- in front of an order number gives the original number and the claim', function () {
        expect(claimOrders('Bestellung R1-302-8780527-8594717'))->toBe([['302-8780527-8594717', 'amazon', [1]]]);
    });

    test('the same order with and without a prefix is one order and keeps the claim', function () {
        expect(claimOrders('R2-302-8780527-8594717 und 302-8780527-8594717'))->toBe([['302-8780527-8594717', 'amazon', [2]]])
            ->and(claimOrders('302-8780527-8594717 … später R3-302-8780527-8594717'))->toBe([['302-8780527-8594717', 'amazon', [3]]]);
    });

    test('several claims of one order are kept, in order', function () {
        expect(claimOrders('R2-302-8780527-8594717 und R1-302-8780527-8594717'))->toBe([['302-8780527-8594717', 'amazon', [1, 2]]]);
    });

    test('claims work with the other formats too, in lower case and with a typed number', function () {
        expect(claimOrders('r1-7JI-0WC1-6M49 und R12-30019578'))->toBe([['7JI-0WC1-6M49', 'vanilo', [1]], ['30019578', 'looxis-pro', [12]]]);

        $typed = app(OrderNumberDetector::class)->normalize(' R1-302-8780527-8594717 ');
        expect($typed->value)->toBe('302-8780527-8594717')->and($typed->claims)->toBe([1])->and(app(OrderNumberDetector::class)->normalize('302-8780527-8594717')->claims)->toBe([]);
    });

    test('things that are no claim number stay as they were', function (string $text, array $expected) {
        expect(claimOrders($text))->toBe($expected);
    })->with([
        'no digit' => ['R-302-8780527-8594717', []],
        'too many digits' => ['R1000-302-8780527-8594717', []],
        'letters after R' => ['RX-302-8780527-8594717', []],
        'a space instead of the hyphen' => ['R1 302-8780527-8594717', [['302-8780527-8594717', 'amazon', []]]],
        'another prefix' => ['Q1-302-8780527-8594717', []],
        'a claim in front of nothing' => ['R1-12', []],
    ]);

    test('the prefix can be changed on the settings page', function () {
        test()->withCookie('staff_name', 'Etienne')->put(route('settings.detection.update'), [
            'settings' => ['claim_prefix' => 'REK', ...collect(SettingsRegistry::forPage('detection'))->except('claim_prefix')->map(fn ($group) => app(SettingsStore::class)->value($group->key))->all()],
            'versions' => collect(SettingsRegistry::forPage('detection'))->map(fn ($group) => app(SettingsStore::class)->version($group->key))->all(),
        ])->assertSessionHas('saved', 1);

        expect(app(OrderFormats::class)->claimPrefix())->toBe('REK')->and(claimOrders('REK1-302-8780527-8594717'))->toBe([['302-8780527-8594717', 'amazon', [1]]])
            ->and(claimOrders('R1-302-8780527-8594717'))->toBe([]);
    });

    test('an invalid prefix is refused', function (string $prefix) {
        test()->withCookie('staff_name', 'Etienne')->put(route('settings.detection.update'), [
            'settings' => ['claim_prefix' => $prefix, ...collect(SettingsRegistry::forPage('detection'))->except('claim_prefix')->map(fn ($group) => app(SettingsStore::class)->value($group->key))->all()],
            'versions' => collect(SettingsRegistry::forPage('detection'))->map(fn ($group) => app(SettingsStore::class)->version($group->key))->all(),
        ])->assertSessionHasErrors('settings.claim_prefix');

        expect(Setting::query()->count())->toBe(0);
    })->with(['empty' => '', 'digit' => 'R1', 'too long' => 'REKL', 'hyphen' => 'R-']);
});

describe('in the ticket and in the analysis', function () {
    beforeEach(function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);
        fakeZammad([zammadArticle(['id' => 1, 'body' => '<p>Ich brauche mein Geld zurück.</p>'])], ['title' => '[WICHTIG] Ihre Bestellung kommt heute Bestellung R1-302-8780527-8594717']);
        Http::fake(['eocs.test/api/v1/orders*' => fn ($request) => Http::response(['data' => str_contains(urldecode($request->url()), '302-8780527-8594717') ? [[
            'id' => 700001, 'external_order_id' => '302-8780527-8594717', 'order_date' => '2026-10-01T10:00:00.000000Z', 'status_name' => 'Vollständig und verschickt', 'state' => ['name' => 'Complete', 'color' => 'green'],
            'ordered_at' => 'Amazon.de', 'client' => ['id' => 1, 'name' => 'Amazon.de', 'sales_platform' => ['id' => 7, 'name' => 'Amazon']], 'shipping' => [], 'invoice' => ['document_number' => 'X'],
            'shipments' => ['data' => []], 'order_items' => ['data' => []],
        ]] : []]), 'eocs.test/*' => Http::response(['data' => []])]);
    });

    test('a ticket that only names R1- gets its original order and the claim is highlighted', function () {
        $html = test()->get('/tickets/2137942')->assertOk()->assertSeeText('302-8780527-8594717')->getContent();

        expect($html)->not->toContain('keine Bestellnummer gefunden');
    });

    test('a number from Amazon\'s notice and a claim number in the title are one order with the claim', function () {
        $ticket = app(ZammadClient::class)->ticket('2137942');
        $withNotice = new Ticket(...[...get_object_vars($ticket), 'orders' => [new OrderMention('302-8780527-8594717', 'Amazon-Nachricht', [])]]);

        $found = app(OrderNumberDetector::class)->detect($withNotice);

        expect($found)->toHaveCount(1)->and($found[0]->value)->toBe('302-8780527-8594717')->and($found[0]->claims)->toBe([1]);
    });

    test('an order not loaded yet names the claim in its label', function () {
        test()->get('/tickets/2137942?bestellungen[0]=')->assertOk()->assertSeeText('das Ticket nennt R1');
    });

    test('the analysis is told that this is a follow-up of a claim', function () {
        CaseAgent::fake([['summary' => ['incident' => 'x', 'customer_wish' => 'y'], 'category' => 'complaint', 'case_pattern' => null, 'assessment' => null, 'recommendation' => 'a', 'actions' => [], 'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null], 'reasoning' => 'x', 'knowledge_gaps' => [], 'knowledge_ids' => ['POLICY-001'], 'confidence' => ['level' => 'MITTEL', 'reasons' => []], 'next_step' => 'reply', 'decision' => null, 'internal_steps' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag']]]);

        test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['variante' => 'verlauf', 'kundengruppe' => 'unclear', 'kein_produktbezug' => '1'])->assertRedirect();

        expect(Analysis::query()->latest('id')->firstOrFail()->content['sent_input'])->toContain('Das Ticket nennt den Reklamationsauftrag R1 zur Bestellung 302-8780527-8594717 (Folgevorgang einer Reklamation).');
    });
});

describe('without a claim number', function () {
    beforeEach(function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);
        fakeZammad([zammadArticle(['id' => 1, 'body' => '<p>Frage zur Bestellung 302-8780527-8594717.</p>'])]);
        Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
    });

    test('without a claim number nothing is added', function () {
        CaseAgent::fake([['summary' => ['incident' => 'x', 'customer_wish' => 'y'], 'category' => 'complaint', 'case_pattern' => null, 'assessment' => null, 'recommendation' => 'a', 'actions' => [], 'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null], 'reasoning' => 'x', 'knowledge_gaps' => [], 'knowledge_ids' => ['POLICY-001'], 'confidence' => ['level' => 'MITTEL', 'reasons' => []], 'next_step' => 'reply', 'decision' => null, 'internal_steps' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag']]]);

        test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['variante' => 'verlauf', 'kundengruppe' => 'unclear', 'kein_produktbezug' => '1'])->assertRedirect();

        expect(Analysis::query()->latest('id')->firstOrFail()->content['sent_input'])->not->toContain('Folgevorgang einer Reklamation');
    });
});
