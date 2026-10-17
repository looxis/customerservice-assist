<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\Prompt;
use App\Models\Analysis;
use App\Orders\OrderNumberDetector;
use App\Zammad\RelatedTicketFinder;
use App\Zammad\ZammadClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();
    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test', 'cache.default' => 'array',
    ]);
    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
});

afterEach(fn () => cleanUpKnowledgeBases());

/**
 * A small Zammad with several tickets and links. Search understands "number:", "customer_id:" and any other text
 * (found in the title or a message).
 *
 * @param  array<string, array<string, mixed>>  $tickets  Number => id, title, state, customer_id, created_at, articles, (links: list of ids)
 */
function zammadWorld(array $tickets, array $options = []): void
{
    $row = fn (array $ticket, string $number): array => [
        'id' => $ticket['id'], 'number' => $number, 'title' => $ticket['title'], 'state' => $ticket['state'] ?? 'closed', 'group' => 'Kundenservice', 'customer_id' => $ticket['customer_id'] ?? 77,
        'created_at' => $ticket['created_at'] ?? now()->subDays(5)->toIso8601String(), 'article_count' => count($ticket['articles']),
    ];
    $byId = collect($tickets)->mapWithKeys(fn (array $ticket, string $number): array => [$ticket['id'] => [$number, $ticket]]);

    Http::preventStrayRequests();
    Http::fake([
        'zammad.test/api/v1/tickets/search*' => function (Request $request) use ($tickets, $row) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $q = (string) ($query['query'] ?? '');
            $hits = collect($tickets)->filter(fn (array $ticket, string $number): bool => match (true) {
                str_starts_with($q, 'number:') => $number === substr($q, 7),
                str_starts_with($q, 'customer_id:') => ($ticket['customer_id'] ?? 77) === (int) substr($q, 12),
                default => str_contains($ticket['title'], $q) || collect($ticket['articles'])->contains(fn (array $article): bool => str_contains($article['body'], $q)),
            })->map(fn (array $ticket, string $number): array => $row($ticket, $number))->values()->all();

            return Http::response($hits);
        },
        'zammad.test/api/v1/tickets/*' => function (Request $request) use ($byId, $row) {
            $id = (int) basename((string) parse_url($request->url(), PHP_URL_PATH));
            $found = $byId->get($id);

            return $found === null ? Http::response([], 404) : Http::response($row($found[1], $found[0]));
        },
        'zammad.test/api/v1/ticket_articles/by_ticket/*' => function (Request $request) use ($byId) {
            $found = $byId->get((int) basename((string) parse_url($request->url(), PHP_URL_PATH)));

            return Http::response($found === null ? [] : $found[1]['articles']);
        },
        'zammad.test/api/v1/links*' => function (Request $request) use ($byId, $options) {
            if ($options['links_down'] ?? false) {
                return Http::response([], 500);
            }

            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $found = $byId->get((int) ($query['link_object_value'] ?? 0));

            return Http::response(['links' => array_map(fn (int $id): array => ['link_type' => 'parent', 'link_object' => 'Ticket', 'link_object_value' => $id], $found[1]['links'] ?? []), 'assets' => []]);
        },
        'zammad.test/api/v1/users/77' => Http::response(['firstname' => 'Erika', 'lastname' => 'Beispiel', 'email' => 'erika@example.org']),
    ]);
}

function relatedAnswer(array $overrides = []): array
{
    return array_replace(['summary' => ['incident' => 'x', 'customer_wish' => 'y'], 'category' => 'complaint', 'case_pattern' => null, 'assessment' => null, 'recommendation' => 'a', 'actions' => [], 'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null], 'reasoning' => 'x', 'knowledge_gaps' => [], 'knowledge_ids' => ['POLICY-001'], 'confidence' => ['level' => 'MITTEL', 'reasons' => []], 'next_step' => 'reply', 'decision' => null, 'internal_steps' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag']], $overrides);
}

function theCase(array $current = []): array
{
    return [
        '2139944' => ['id' => 39979, 'title' => '[WICHTIG] Bestellung R1-302-8780527-8594717', 'state' => 'new', 'links' => [38780], 'articles' => [zammadArticle(['id' => 900, 'body' => '<p>Ich brauche mein Geld zurück.</p>'])], ...$current],
        '2138745' => ['id' => 38780, 'title' => 'Damaged or defective item (Order: 302-8780527-8594717)', 'links' => [39979], 'articles' => [
            zammadArticle(['id' => 1, 'body' => '<p>Kaputt, ich möchte Ersatz.</p>', 'created_at' => '2026-10-07T07:00:00.000Z']),
            zammadArticle(['id' => 2, 'sender' => 'Agent', 'from' => 'Kerstin', 'body' => '<p>Wir senden Ihnen kostenlos Ersatz zu.</p>', 'created_at' => '2026-10-08T07:00:00.000Z']),
        ]],
        '2138529' => ['id' => 38500, 'title' => 'Ihre Bestellung kommt heute 302-8780527-8594717', 'articles' => [zammadArticle(['id' => 3, 'body' => '<p>Ihre Bestellung 302-8780527-8594717 kommt heute.</p>'])]],
        '2130001' => ['id' => 30001, 'title' => 'Etwas ganz anderes', 'created_at' => now()->subDays(200)->toIso8601String(), 'articles' => [zammadArticle(['id' => 4, 'body' => '<p>Alte Frage</p>'])]],
        '2139900' => ['id' => 39900, 'title' => 'Eine Woche alte Frage', 'created_at' => now()->subDays(7)->toIso8601String(), 'articles' => [zammadArticle(['id' => 5, 'body' => '<p>Frage zu etwas</p>'])]],
    ];
}

function analyzeCase(array $fields = [], string $number = '2139944')
{
    return test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => $number]), ['variante' => 'verlauf', 'kundengruppe' => 'unclear', 'kein_produktbezug' => '1', ...$fields]);
}

describe('the offers on the ticket page', function () {
    test('linked tickets, tickets of the order and of the customer come in three groups, each ticket once', function () {
        zammadWorld(theCase());

        $found = app(RelatedTicketFinder::class)->find(app(ZammadClient::class)->ticket('2139944'), app(OrderNumberDetector::class)->detect(app(ZammadClient::class)->ticket('2139944')));

        $bySource = collect($found['tickets'])->groupBy('source')->map(fn ($tickets) => $tickets->pluck('number')->sort()->values()->all())->all();
        expect($bySource['linked'])->toBe(['2138745'])->and($bySource['order'])->toBe(['2138529'])->and($bySource['customer'])->toBe(['2139900'])
            ->and(collect($found['tickets'])->pluck('number')->all())->not->toContain('2139944')->not->toContain('2130001');
    });

    test('the page shows the groups and ticks the linked ones and, with a claim number, those of the order', function () {
        zammadWorld(theCase());
        $html = test()->get('/tickets/2139944')->assertOk()->assertSeeText('Zusammengehörige Tickets')->assertSeeText('In Zammad verknüpft')->assertSeeText('Tickets zur Bestellung')->assertSeeText('Weitere Tickets dieses Kunden')->getContent();

        expect($html)->toMatch('/value="2138745"[^>]*checked/')->toMatch('/value="2138529"[^>]*checked/')->not->toMatch('/value="2139900"[^>]*checked/');
    });

    test('without a claim number the tickets of the order are only offered', function () {
        zammadWorld(theCase(['title' => 'Frage zu Bestellung 302-8780527-8594717']));
        $html = test()->get('/tickets/2139944')->assertOk()->getContent();

        expect($html)->toMatch('/value="2138745"[^>]*checked/')->not->toMatch('/value="2138529"[^>]*checked/')->toContain('value="2138529"');
    });

    test('at most ten tickets of the customer, only the last 90 days', function () {
        $tickets = theCase();

        foreach (range(1, 14) as $i) {
            $tickets[(string) (2140000 + $i)] = ['id' => 40000 + $i, 'title' => "Frage {$i}", 'created_at' => now()->subDays($i)->toIso8601String(), 'articles' => [zammadArticle(['id' => 100 + $i])]];
        }

        zammadWorld($tickets);
        $found = app(RelatedTicketFinder::class)->find(app(ZammadClient::class)->ticket('2139944'), []);

        expect(collect($found['tickets'])->where('source', 'customer')->count())->toBe(10)->and(collect($found['tickets'])->pluck('number')->all())->not->toContain('2130001');
    });

    test('a problem with Zammad shows a hint and the form still works', function () {
        zammadWorld(theCase(), ['links_down' => true]);

        test()->get('/tickets/2139944')->assertOk()->assertSeeText('Die in Zammad verknüpften Tickets konnten nicht gelesen werden.')->assertSeeText('Vorschlag erstellen');
    });

    test('nothing is offered for a ticket without relations', function () {
        zammadWorld(['2139944' => ['id' => 39979, 'title' => 'Allein', 'articles' => [zammadArticle(['id' => 900])]]]);

        test()->get('/tickets/2139944')->assertOk()->assertSeeText('Es wurden keine zusammengehörigen Tickets gefunden.');
    });
});

describe('the earlier course in the analysis', function () {
    test('the chosen tickets go in with all messages, our replies included, with their source', function () {
        zammadWorld(theCase());
        CaseAgent::fake([relatedAnswer()]);

        analyzeCase(['related_set' => '1', 'related' => ['2138745'], 'related_source' => ['2138745' => 'linked']])->assertRedirect();

        $content = Analysis::query()->latest('id')->firstOrFail()->content;
        expect($content['sent_input'])->toContain('# Früherer Verlauf (zusammengehörige Tickets)')->toContain('## Ticket 2138745')->toContain('in Zammad verknüpft')->toContain('Kaputt, ich möchte Ersatz.')->toContain('Wir senden Ihnen kostenlos Ersatz zu.')->toContain('Ich brauche mein Geld zurück.')
            ->and(strpos($content['sent_input'], 'Früherer Verlauf'))->toBeLessThan(strpos($content['sent_input'], '# Ticket'))
            ->and($content['related']['tickets'][0])->toMatchArray(['number' => '2138745', 'source' => 'linked', 'articles' => 2, 'included' => 2, 'omitted' => 0])
            ->and($content['inputs']['related'])->toBe(['2138745'])
            ->and(Analysis::query()->latest('id')->firstOrFail()->prompt_version)->toContain('related-2026-10-17.1');
        expect(Prompt::load('analysis-related')->text)->toContain('Widersprich bereits Zugesagtem');
        expect(implode(' ', $content['notes']))->toContain('Früherer Verlauf aus 1 Ticket');
    });

    test('the ticket page shows the history of the used tickets collapsed below the thread, and nothing without any', function () {
        zammadWorld(theCase());
        CaseAgent::fake([relatedAnswer(), relatedAnswer()]);

        analyzeCase(['related_set' => '1'])->assertRedirect();
        test()->get('/tickets/2139944')->assertOk()->assertDontSee('id="zusammengehoerige-tickets"', false);

        analyzeCase(['related_set' => '1', 'related' => ['2138745'], 'related_source' => ['2138745' => 'linked']])->assertRedirect();
        $html = test()->get('/tickets/2139944')->assertOk()->getContent();

        expect($html)->toContain('id="zusammengehoerige-tickets"')->toContain('Damaged or defective item')->toContain('Kaputt, ich möchte Ersatz.')->toContain('Wir senden Ihnen kostenlos Ersatz zu.')
            ->and(strpos($html, 'id="zusammengehoerige-tickets"'))->toBeGreaterThan(strpos($html, 'id="verlauf"'))
            ->and(preg_match('/<details class="group rounded-lg border[^>]*>/', $html, $details))->toBe(1)->and($details[0])->not->toContain(' open');
    });

    test('without a choice nothing is added and the version stays as before', function () {
        zammadWorld(theCase());
        CaseAgent::fake([relatedAnswer()]);

        analyzeCase(['related_set' => '1'])->assertRedirect();

        $stored = Analysis::query()->latest('id')->firstOrFail();
        expect($stored->content['sent_input'])->not->toContain('Früherer Verlauf')->and($stored->content)->not->toHaveKey('related')->and($stored->prompt_version)->not->toContain('related-');
    });

    test('a number typed in is taken as a manual one, an unknown one is skipped with a hint', function () {
        zammadWorld(theCase());
        CaseAgent::fake([relatedAnswer(), relatedAnswer()]);

        analyzeCase(['related_manual' => '2138529'])->assertRedirect();
        expect(Analysis::query()->latest('id')->firstOrFail()->content['related']['tickets'][0])->toMatchArray(['number' => '2138529', 'source' => 'manual']);

        analyzeCase(['related_manual' => '1234567'])->assertRedirect();
        $stored = Analysis::query()->latest('id')->firstOrFail();
        expect($stored->status)->toBe('completed')->and($stored->content)->not->toHaveKey('related')->and(implode(' ', $stored->content['notes']))->toContain('Ticket 1234567 konnte nicht gelesen werden');
    });

    test('this ticket and a source that was not offered cannot be smuggled in', function () {
        zammadWorld(theCase());
        CaseAgent::fake([relatedAnswer()]);

        analyzeCase(['related' => ['2139944', '2138745'], 'related_source' => ['2138745' => 'etwas-anderes']])->assertRedirect();

        $related = Analysis::query()->latest('id')->firstOrFail()->content['related'];
        expect(array_column($related['tickets'], 'number'))->toBe(['2138745'])->and($related['tickets'][0]['source'])->toBe('manual');
    });

    test('more than five tickets are refused and nothing is analysed', function () {
        zammadWorld(theCase());

        analyzeCase(['related' => ['1', '2', '3', '4', '5', '6']])->assertSessionHasErrors('related');
        analyzeCase(['related' => ['1', '2', '3', '4', '5'], 'related_manual' => '7'])->assertSessionHasErrors('related');

        expect(Analysis::query()->count())->toBe(0);
    });

    test('invalid numbers are refused', function (array $fields) {
        zammadWorld(theCase());

        analyzeCase($fields)->assertSessionHasErrors();
    })->with(['letters' => [['related' => ['abc']]], 'manual with letters' => [['related_manual' => '21-38']], 'too long' => [['related_manual' => str_repeat('1', 21)]]]);

    test('a long course is shortened from the old end, the newest message stays and the result says so', function () {
        $tickets = theCase();
        $tickets['2138745']['articles'] = array_map(fn (int $i): array => zammadArticle(['id' => 200 + $i, 'body' => '<p>Nachricht Nummer '.$i.' '.str_repeat('x', 400).'</p>', 'created_at' => '2026-10-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT).'T08:00:00.000Z']), range(1, 12));
        zammadWorld($tickets);
        config(['analysis.related_tickets.budget' => 1500]);
        CaseAgent::fake([relatedAnswer()]);

        analyzeCase(['related' => ['2138745'], 'related_source' => ['2138745' => 'linked']])->assertRedirect();

        $content = Analysis::query()->latest('id')->firstOrFail()->content;
        $meta = $content['related']['tickets'][0];
        expect($meta['omitted'])->toBeGreaterThan(0)->and($meta['included'] + $meta['omitted'])->toBe(12)
            ->and($content['sent_input'])->toContain('Nachricht Nummer 12')->not->toContain('Nachricht Nummer 1 x')->toContain('ältere Nachrichten dieses Tickets ausgelassen')
            ->and(implode(' ', $content['notes']))->toContain('Aus Ticket 2138745 wurden');
    });

    test('the result names the tickets and what was left out', function () {
        zammadWorld(theCase());
        CaseAgent::fake([relatedAnswer()]);

        $location = analyzeCase(['related' => ['2138745'], 'related_source' => ['2138745' => 'linked']])->headers->get('Location');

        test()->withCookie('staff_name', 'Nele')->get($location)->assertOk()->assertSeeText('Zusammengehörige Tickets')->assertSeeText('2138745')->assertSeeText('in Zammad verknüpft')->assertSeeText('2 von 2 Nachrichten');
    });

    test('a follow-up keeps the same course without asking Zammad for the related tickets again', function () {
        zammadWorld(theCase());
        CaseAgent::fake([relatedAnswer(['next_step' => 'internal_first', 'reply' => null, 'internal_steps' => ['Klären'], 'decision' => ['question' => 'Weiter?', 'why' => 'x', 'options' => [['key' => 'a', 'label' => 'A', 'consequence' => 'a'], ['key' => 'b', 'label' => 'B', 'consequence' => 'b']], 'knowledge_ids' => ['POLICY-001']]]), relatedAnswer()]);

        parse_str((string) parse_url((string) analyzeCase(['related' => ['2138745'], 'related_source' => ['2138745' => 'linked']])->headers->get('Location'), PHP_URL_QUERY), $query);
        $asked = fn (): int => Http::recorded(fn (Request $request): bool => str_contains($request->url(), 'by_ticket/38780'))->count();
        $before = $asked();

        analyzeCase(['klaerung_analyse' => $query['analyse'], 'klaerung_option' => 'a'])->assertRedirect();

        $second = Analysis::query()->latest('id')->firstOrFail();
        expect($asked())->toBe($before)->and($second->content['sent_input'])->toContain('Wir senden Ihnen kostenlos Ersatz zu.')->and($second->content['related']['tickets'][0]['number'])->toBe('2138745');
    });

    test('a ticket from the test mode is not offered related tickets', function () {
        zammadWorld(theCase());
        config(['staff.admins' => ['Etienne']]);

        $html = test()->withCookie('staff_name', 'Etienne')->withCookie('test_mode', '1')->get('/tickets/2139944?stand=900')->assertOk()->getContent();

        expect($html)->not->toContain('In Zammad verknüpft');
    });
});
