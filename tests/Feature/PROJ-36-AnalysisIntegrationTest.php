<?php

use App\Analysis\Agents\CaseAgent;
use App\Models\Analysis;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Prompts\AgentPrompt;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
        'cache.default' => 'array',
    ]);
});

afterEach(fn () => cleanUpKnowledgeBases());

/**
 * @return array<string, mixed>
 */
function retrAnswer(array $overrides = []): array
{
    return array_replace([
        'summary' => ['incident' => 'Zwei Bestellungen', 'customer_wish' => 'Eine Rechnung'],
        'category' => 'complaint', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Nachfragen.', 'actions' => [],
        'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'Keine Regel.', 'missing_information' => [], 'knowledge_gaps' => [],
        'knowledge_ids' => [], 'confidence' => ['level' => 'NIEDRIG', 'reasons' => []],
        'internal_todos' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag, wir prüfen das.'],
    ], $overrides);
}

/**
 * Fake Zammad with one customer message and EOCS with the given orders (keyed by external number).
 *
 * @param  list<array<string, mixed>>  $orders
 */
function retrTicket(string $text, array $orders = []): void
{
    fakeZammad([zammadArticle(['id' => 1, 'body' => '<p>'.e($text).'</p>'])]);

    Http::fake([
        'eocs.test/api/v1/orders*' => function (Request $request) use ($orders) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            [$number] = explode(',', (string) ($query['filter']['external_order_id'] ?? ''));

            return Http::response(['data' => array_values(array_filter($orders, fn (array $order): bool => $order['external_order_id'] === $number))]);
        },
    ]);
}

/**
 * An EOCS order as the API returns it, with one shipment unless overridden.
 *
 * @return array<string, mixed>
 */
function retrOrder(array $overrides = []): array
{
    $order = array_replace_recursive([
        'id' => 700001, 'external_order_id' => '402-0000000-0000001', 'order_date' => '2026-09-09T13:06:53.000000Z', 'status_name' => 'Vollständig und verschickt',
        'state' => ['name' => 'Complete', 'color' => 'green'], 'ordered_at' => 'Amazon.it', 'client' => ['id' => 106, 'name' => 'Amazon.it', 'sales_platform' => ['id' => 7, 'name' => 'Amazon']],
        'origin_order_id' => null, 'shipping' => ['street' => 'Weg 1', 'city' => 'Stadt'], 'invoice' => ['document_number' => 'LX26-00001'],
        'shipments' => ['data' => [['carrier' => ['name' => 'DHL'], 'shipping_method' => ['name' => 'DHL PAKET'], 'tracking_no' => 'T1', 'created_at' => '10.09.2026 11:54', 'delivered' => null]]],
        'order_items' => ['data' => [['quantity' => 2, 'status_name' => 'Vollständig', 'item' => ['item_id' => 11281, 'name' => 'Fototasse', 'product_type' => 'Tasse'], 'data' => []]]],
    ], $overrides);

    foreach (['shipments', 'order_items'] as $list) {
        $order[$list] = $overrides[$list] ?? $order[$list];
    }

    return $order;
}

function retrAnalyze(array $input = []): void
{
    test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), array_merge(['kundengruppe' => 'reseller', 'variante' => 'verlauf'], $input));
}

function retrStored(): Analysis
{
    return Analysis::query()->latest('id')->firstOrFail();
}

function retrSent(): string
{
    return retrStored()->content['sent_input'];
}

function retrDoc(int $number, array $fields = [], string $body = "Gilt für alle.\n\n# Regel\n\nEin Satz."): array
{
    return [sprintf('policies/policy-%03d-x.md', $number) => knowledgeDoc(['id' => sprintf('POLICY-%03d', $number), 'title' => "Regel {$number}", 'summary' => 'Wann gebraucht.', ...$fields], $body)];
}

describe('the transition mode', function () {
    test('with the retrieval switched off the analysis sends everything in scope as before', function () {
        knowledgeBase(retrDoc(1, ['retrieval' => 'core']) + retrDoc(2, ['retrieval' => 'triggered', 'triggers' => ['Rechnung']]));
        retrTicket('Meine Tasse ist kaputt.', [retrOrder()]);
        CaseAgent::fake([retrAnswer()]);

        retrAnalyze(['bestellungen' => ['402-0000000-0000001']]);

        $stored = retrStored();

        expect(config('knowledge.retrieval.active'))->toBeFalse()
            ->and($stored->content['sent_input'])->toContain('## POLICY-001')->toContain('## POLICY-002')
            ->and($stored->content)->not->toHaveKey('retrieval')
            ->and($stored->content['sent_input'])->not->toContain('keine Sendung in EOCS gefunden')
            ->and($stored)->passes->toBe(1)->documents_retrieved->toBe(2)->documents_omitted->toBe(0);
    });

    test('unclassified documents still reach the prompt when the retrieval is switched on', function () {
        config(['knowledge.retrieval.active' => true]);
        knowledgeBase(retrDoc(1, []) + retrDoc(2, ['retrieval' => 'triggered', 'triggers' => ['Rechnung']]));
        retrTicket('Meine Tasse ist kaputt.');
        CaseAgent::fake([retrAnswer()]);

        retrAnalyze();

        $record = retrStored()->content['retrieval'];

        expect(retrSent())->toContain('## POLICY-001')->not->toContain('## POLICY-002')
            ->and($record['unclassified'])->toBe(['POLICY-001']);
    });
});

describe('with the retrieval switched on', function () {
    beforeEach(fn () => config(['knowledge.retrieval.active' => true]));

    test('only the documents the concern needs are sent, with the log and the figures', function () {
        knowledgeBase(retrDoc(1, ['retrieval' => 'core']) + retrDoc(2, ['retrieval' => 'triggered', 'triggers' => ['Rechnung']]) + retrDoc(3, ['retrieval' => 'triggered', 'triggers' => ['Kaputt']]));
        retrTicket('Meine Tasse ist kaputt.');
        CaseAgent::fake([retrAnswer()]);

        retrAnalyze();

        $stored = retrStored();
        $record = $stored->content['retrieval'];

        expect($stored->content['sent_input'])->toContain('## POLICY-001')->toContain('## POLICY-003')->not->toContain('## POLICY-002')
            ->and(array_column($record['documents'], 'id'))->toBe(['POLICY-001', 'POLICY-003'])
            ->and($record['documents'][1]['reasons'])->toBe(['Auslöser „Kaputt“ (aktuelles Anliegen)'])
            ->and($record['not_retrieved'])->toBe([['id' => 'POLICY-002', 'title' => 'Regel 2', 'code' => 'no_trigger', 'detail' => 'kein Auslöser im Anliegen']])
            ->and($record['sizes']['knowledge'])->toBe($stored->knowledge_characters)
            ->and($stored)->documents_retrieved->toBe(2)->passes->toBe(1);
    });

    test('a missing shipment is stated neutrally, not as not shipped', function () {
        knowledgeBase(retrDoc(1, ['retrieval' => 'core']));
        retrTicket('Wo ist mein Paket?', [retrOrder(['shipments' => ['data' => []]])]);
        CaseAgent::fake([retrAnswer()]);

        retrAnalyze(['bestellungen' => ['402-0000000-0000001']]);

        expect(retrSent())->toContain('Versand: keine Sendung in EOCS gefunden')->not->toContain('noch nicht versandt');
    });

    test('a binding rule whose precondition cannot be checked is sent with a note, never as fulfilled', function () {
        knowledgeBase(retrDoc(1, ['retrieval' => 'core']) + retrDoc(5, ['retrieval' => 'binding', 'when' => ['days_since_shipped' => ['min' => 14]]], "Gilt nach 14 Tagen.\n\n# Regel\n\nDann Ersatz."));
        retrTicket('Meine Tasse ist nicht angekommen.');
        CaseAgent::fake([retrAnswer()]);

        retrAnalyze();

        $stored = retrStored();
        $item = collect($stored->content['retrieval']['documents'])->firstWhere('id', 'POLICY-005');

        expect($stored->content['sent_input'])->toContain('Hinweis der App: Die Voraussetzung dieser Regel konnte nicht geprüft werden (fehlende Angaben: days_since_shipped)')
            ->toContain('Behandle sie nicht als erfüllt')
            ->and($item['unverified'])->toBeTrue()->and($item['unknown_keys'])->toBe(['days_since_shipped'])
            ->and($stored->content['retrieval']['conditions']['POLICY-005']['result'])->toBe('unknown')
            ->and(implode(' ', $stored->content['notes']))->toContain('Voraussetzung ungeklärt bei verbindlichem Wissen (POLICY-005)');
    });

    test('a binding rule with a known fulfilled precondition is sent without a note', function () {
        knowledgeBase(retrDoc(5, ['retrieval' => 'binding', 'when' => ['order_channel' => 'amazon']]));
        retrTicket('Meine Tasse ist nicht angekommen.', [retrOrder()]);
        CaseAgent::fake([retrAnswer()]);

        retrAnalyze(['bestellungen' => ['402-0000000-0000001']]);

        expect(retrSent())->toContain('## POLICY-005')->not->toContain('Hinweis der App')
            ->and(retrStored()->content['retrieval']['conditions']['POLICY-005']['result'])->toBe('yes');
    });
});

describe('general words do not pull special knowledge', function () {
    beforeEach(function () {
        config(['knowledge.retrieval.active' => true]);
        knowledgeBase(
            retrDoc(1, ['retrieval' => 'core'])
            + retrDoc(17, ['retrieval' => 'triggered', 'triggers' => ['Urheberrecht', 'Copyright', 'Markenrecht', 'Bildrechte']])
            + retrDoc(18, ['retrieval' => 'triggered', 'triggers' => ['Logo', 'Urheberrecht'], 'exclude_triggers' => ['unser Logo', 'unserem Logo', 'eigenes Logo', 'Firmenlogo']]),
        );
        CaseAgent::fake([retrAnswer()]);
    });

    test('a quote request with the own company logo retrieves neither rights document', function (string $text) {
        retrTicket($text);

        retrAnalyze();

        expect(retrSent())->not->toContain('## POLICY-017')->not->toContain('## POLICY-018');
    })->with([
        'logo and firmenlogo' => 'Wir benötigen ein Angebot für 200 Tassen mit unserem Firmenlogo.',
        'own logo' => 'Bitte ein Angebot für 200 Tassen. Darauf kommt unser Logo in Blau.',
        'logo in the picture list' => 'Angebot für 500 Stück, Motiv: unserem Logo auf der Vorderseite, 4-farbig.',
    ]);

    test('a concrete question about rights retrieves the rights document', function (string $text, string $id) {
        retrTicket($text);

        retrAnalyze();

        expect(retrSent())->toContain("## {$id}");
    })->with([
        'copyright question' => ['Ist das Motiv urheberrechtlich geschützt?', 'POLICY-017'],
        'copyright word' => ['Darf ich ein Foto mit Copyright verwenden?', 'POLICY-017'],
        'brand right' => ['Wie ist das mit dem Markenrecht, wenn ich ein Markenlogo drucken lasse?', 'POLICY-017'],
        'foreign logo question' => ['Darf ich das Logo von Adidas auf eine Tasse drucken lassen?', 'POLICY-018'],
    ]);

    test('the own logo is no reason to hide an explicit question about rights', function () {
        retrTicket('Wir drucken unser Logo auf 200 Tassen. Ist das urheberrechtlich in Ordnung?');

        retrAnalyze();

        expect(retrSent())->toContain('## POLICY-017')->toContain('## POLICY-018');
    });

    test('a hit only in an earlier message weighs less but a rights topic of the current concern wins', function () {
        fakeZammad([
            zammadArticle(['id' => 1, 'body' => '<p>Zum Urheberrecht habe ich noch eine Frage zu unserem Motiv.</p>']),
            zammadArticle(['id' => 2, 'sender' => 'Agent', 'from' => 'Kundenservice', 'body' => '<p>Gern, wir prüfen das.</p>']),
            zammadArticle(['id' => 3, 'body' => '<p>Danke, wo bleibt mein Paket mit der Bestellung?</p>']),
        ]);
        Http::fake(['eocs.test/*' => Http::response(['data' => []])]);

        retrAnalyze();

        $record = retrStored()->content['retrieval'];
        $item = collect($record['documents'])->firstWhere('id', 'POLICY-017');

        expect($item['strength'])->toBe('weak')->and($item['reasons'][0])->toContain('früherer Verlauf');
    });
});

describe('the safeguard for binding rules', function () {
    beforeEach(function () {
        config(['knowledge.retrieval.active' => true]);
        knowledgeBase(retrDoc(1, ['retrieval' => 'core']) + retrDoc(20, ['retrieval' => 'binding', 'actions' => ['partial-refund']], "Kulanz nur bis 20 Euro.\n\n# Regel\n\nBefugnis Kulanz.") + retrDoc(21, ['retrieval' => 'binding', 'actions' => ['refund']], "Erstattung nur mit Freigabe.\n\n# Regel\n\nVier Augen."));
        retrTicket('Die Tasse hat einen kleinen Kratzer.');
    });

    test('a measure the customer did not ask for loads its binding rules and runs once more', function () {
        CaseAgent::fake([
            retrAnswer(['actions' => ['partial-refund'], 'knowledge_ids' => ['POLICY-001'], 'reply' => ['language' => 'Deutsch', 'text' => 'Erster Entwurf.']]),
            retrAnswer(['actions' => ['partial-refund'], 'knowledge_ids' => ['POLICY-020'], 'reply' => ['language' => 'Deutsch', 'text' => 'Zweiter Entwurf mit Befugnis.']]),
        ]);

        retrAnalyze();

        $stored = retrStored();
        $record = $stored->content['retrieval'];

        CaseAgent::assertPromptedTimes(2);

        expect($stored->content['result']['reply']['text'])->toBe('Zweiter Entwurf mit Befugnis.')
            ->and($stored->content['sent_input'])->toContain('## POLICY-020')->not->toContain('## POLICY-021')
            ->and($record['safeguard'])->toMatchArray(['first_actions' => ['partial-refund'], 'loaded' => ['POLICY-020'], 'load_actions' => ['partial-refund'], 'failed' => false, 'not_considered' => []])
            ->and(array_column($record['documents'], 'id'))->toBe(['POLICY-001', 'POLICY-020'])
            ->and($record['documents'][1]['reasons'])->toBe(['nachgeladen wegen Maßnahme partial-refund'])
            ->and($stored)->passes->toBe(2)
            ->and(implode(' ', $stored->content['notes']))->toContain('Nachgeladen wegen Maßnahme partial-refund: POLICY-020')
            ->and(Analysis::query()->where('status', 'completed')->count())->toBe(1);
    });

    test('a measure without binding rules does not cost a second call', function () {
        CaseAgent::fake([retrAnswer(['actions' => ['photo-request'], 'knowledge_ids' => ['POLICY-001']])]);

        retrAnalyze();

        CaseAgent::assertPromptedTimes(1);

        expect(retrStored()->content['retrieval']['safeguard'])->toMatchArray(['loaded' => [], 'failed' => false])->and(retrStored())->passes->toBe(1);
    });

    test('it repeats only once: a further measure of the second round is reported, not chased', function () {
        CaseAgent::fake([
            retrAnswer(['actions' => ['partial-refund'], 'knowledge_ids' => ['POLICY-001']]),
            retrAnswer(['actions' => ['partial-refund', 'refund'], 'knowledge_ids' => ['POLICY-020']]),
        ]);

        retrAnalyze();

        CaseAgent::assertPromptedTimes(2);

        $stored = retrStored();

        expect($stored->content['retrieval']['safeguard']['not_considered'])->toBe(['POLICY-021'])
            ->and(implode(' ', $stored->content['notes']))->toContain('Verbindliche Regeln zur empfohlenen Maßnahme wurden nicht berücksichtigt: POLICY-021');
    });

    test('if the second round fails, the first result stays with a clear warning and no failure is logged', function () {
        CaseAgent::fake([
            retrAnswer(['actions' => ['partial-refund'], 'knowledge_ids' => ['POLICY-001'], 'reply' => ['language' => 'Deutsch', 'text' => 'Erster Entwurf.']]),
            fn () => throw new RuntimeException('Modell nicht erreichbar'),
        ]);

        retrAnalyze();

        $stored = retrStored();

        expect($stored->status)->toBe('completed')->and($stored->content['result']['reply']['text'])->toBe('Erster Entwurf.')
            ->and($stored->content['retrieval']['safeguard'])->toMatchArray(['failed' => true, 'loaded' => [], 'not_considered' => ['POLICY-020']])
            ->and(implode(' ', $stored->content['notes']))->toContain('Der Vorschlag ist nicht abgesichert')
            ->and($stored)->passes->toBe(1)
            ->and(Analysis::query()->where('status', 'failed')->count())->toBe(0);
    });

    test('a rule that no longer fits into the prompt is reported instead of cut', function () {
        knowledgeBase(retrDoc(1, ['retrieval' => 'core']) + retrDoc(20, ['retrieval' => 'binding', 'actions' => ['partial-refund']], str_repeat('Befugnis Kulanz. ', 17000)));
        CaseAgent::fake([retrAnswer(['actions' => ['partial-refund'], 'knowledge_ids' => ['POLICY-001']])]);

        retrAnalyze();

        CaseAgent::assertPromptedTimes(1);

        expect(retrStored()->content['retrieval']['safeguard'])->toMatchArray(['failed' => true, 'not_considered' => ['POLICY-020']]);
    });
});

describe('the limit of the whole prompt', function () {
    test('mandatory knowledge that does not fit stops the analysis with a clear message and a logged failure', function () {
        config(['knowledge.retrieval.active' => true, 'analysis.limits.context_characters' => 1000, 'analysis.limits.answer_reserve_characters' => 0]);
        knowledgeBase(retrDoc(1, ['retrieval' => 'core'], str_repeat('x', 500)));
        retrTicket('Meine Tasse ist kaputt.');
        CaseAgent::fake([retrAnswer()]);

        retrAnalyze();

        CaseAgent::assertNeverPrompted();

        expect(Analysis::query()->where('status', 'failed')->value('error'))->toBe('KnowledgeTooLarge');
    });

    test('a long ticket leaves less room for triggered knowledge but never cuts the mandatory part', function () {
        config(['knowledge.retrieval.active' => true]);
        knowledgeBase(retrDoc(1, ['retrieval' => 'core'], str_repeat('a', 400)) + retrDoc(2, ['retrieval' => 'triggered', 'triggers' => ['kaputt']], str_repeat('b', 400)));
        retrTicket('Meine Tasse ist kaputt. '.str_repeat('Lange Beschreibung. ', 50));

        CaseAgent::fake([retrAnswer(), retrAnswer()]);

        retrAnalyze();

        $base = (int) config('analysis.limits.context_characters') - (int) config('analysis.limits.answer_reserve_characters') - retrStored()->content['retrieval']['sizes']['available'];
        config(['analysis.limits.context_characters' => $base + 600, 'analysis.limits.answer_reserve_characters' => 0]);

        retrAnalyze();

        $record = retrStored()->content['retrieval'];

        expect(array_column($record['documents'], 'id'))->toBe(['POLICY-001'])->and($record['not_retrieved'][0]['code'])->toBe('out_of_space')
            ->and(implode(' ', retrStored()->content['notes']))->toContain('Wegen Umfang nicht mitgesendet: POLICY-002');
    });
});

describe('readiness for switching on', function () {
    test('the check names what stands in the way and says when everything is ready', function () {
        knowledgeBase(retrDoc(1, []) + retrDoc(2, ['retrieval' => 'core']));

        $this->artisan('knowledge:check')->expectsOutputToContain('Gezielter Abruf (PROJ-36): aus')->assertSuccessful();

        Artisan::call('knowledge:check');

        expect(Artisan::output())->toContain('Bereit für die Aktivierung: nein – 1 Dokument ist noch nicht klassifiziert');

        knowledgeBase(retrDoc(1, ['retrieval' => 'core']));
        Artisan::call('knowledge:check');

        expect(Artisan::output())->toContain('Bereit für die Aktivierung: ja.');
    });
});

describe('the standing instructions and the versions', function () {
    test('the rule about unchecked preconditions is part of the instructions, only when the retrieval is switched on', function () {
        knowledgeBase(retrDoc(1, ['retrieval' => 'core']));
        retrTicket('Meine Tasse ist kaputt.');
        CaseAgent::fake([retrAnswer(), retrAnswer()]);

        retrAnalyze();
        config(['knowledge.retrieval.active' => true]);
        retrAnalyze();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => str_contains((string) $prompt->agent->instructions(), 'niemals als erfüllt')
            && str_contains((string) $prompt->agent->instructions(), 'Hinweis der App'));
        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => ! str_contains((string) $prompt->agent->instructions(), 'Hinweis der App'));
    });

    test('the effective prompt version and the retrieval configuration are stored with the analysis', function () {
        config(['knowledge.retrieval.active' => true]);
        knowledgeBase(retrDoc(1, ['retrieval' => 'core']));
        retrTicket('Meine Tasse ist kaputt.');
        CaseAgent::fake([retrAnswer(), retrAnswer(), retrAnswer()]);

        retrAnalyze();
        $first = retrStored();

        config(['knowledge.retrieval.budget.target' => 41000]);
        retrAnalyze();
        $second = retrStored();

        config(['knowledge.retrieval.active' => false]);
        retrAnalyze();
        $third = retrStored();

        expect($first->prompt_version)->toBe('analysis-2026-10-09.2+retrieval-2026-10-13.1')
            ->and($first->retrieval_version)->toMatch('/^2026-10-13\.1\/[0-9a-f]{8}$/')
            ->and($second->retrieval_version)->not->toBe($first->retrieval_version)
            ->and($first->content['retrieval']['configuration'])->toMatchArray(['version' => $first->retrieval_version])
            ->and($first->content['retrieval']['configuration']['budget']['target'])->toBe(40000)
            ->and($third->prompt_version)->toBe('analysis-2026-10-09.2')->and($third->retrieval_version)->toBeNull();
    });
});

describe('the rule check status', function () {
    beforeEach(function () {
        config(['knowledge.retrieval.active' => true, 'staff.admins' => ['Etienne']]);
        knowledgeBase(retrDoc(1, ['retrieval' => 'core']) + retrDoc(20, ['retrieval' => 'binding', 'actions' => ['partial-refund']], "Kulanz nur bis 20 Euro.\n\n# Regel\n\nBefugnis Kulanz.") + retrDoc(21, ['retrieval' => 'binding', 'actions' => ['refund']], "Erstattung mit Freigabe.\n\n# Regel\n\nVier Augen."));
        retrTicket('Die Tasse hat einen kleinen Kratzer.');
    });

    test('a complete safeguard and a measure without binding rules are checked, without the retrieval there is no status', function () {
        CaseAgent::fake([retrAnswer(['actions' => ['photo-request'], 'knowledge_ids' => ['POLICY-001']]), retrAnswer(['actions' => ['partial-refund'], 'knowledge_ids' => ['POLICY-001']]), retrAnswer(['knowledge_ids' => ['POLICY-020']])]);

        retrAnalyze();
        $plain = retrStored();
        retrAnalyze();
        $loaded = retrStored();

        config(['knowledge.retrieval.active' => false]);
        CaseAgent::fake([retrAnswer()]);
        retrAnalyze();

        expect($plain->rule_check)->toBe('complete')->and($loaded->rule_check)->toBe('complete')->and(retrStored()->rule_check)->toBeNull();
    });

    test('a failed second round, rules that no longer fit and a new unchecked measure make the check incomplete', function (array $answers) {
        CaseAgent::fake($answers);

        retrAnalyze();

        expect(retrStored())->rule_check->toBe('incomplete')->rule_check_at->toBeNull()
            ->and(retrStored()->content['retrieval']['rule_check'])->toBe('incomplete')
            ->and(implode(' ', retrStored()->content['notes']))->toContain('Regelprüfung unvollständig');
    })->with([
        'second round fails' => fn () => [retrAnswer(['actions' => ['partial-refund'], 'knowledge_ids' => ['POLICY-001']]), fn () => throw new RuntimeException('Modell nicht erreichbar')],
        'new measure in the second round' => fn () => [retrAnswer(['actions' => ['partial-refund'], 'knowledge_ids' => ['POLICY-001']]), retrAnswer(['actions' => ['partial-refund', 'refund'], 'knowledge_ids' => ['POLICY-020']])],
    ]);

    test('rules that do not fit into the prompt any more make the check incomplete', function () {
        knowledgeBase(retrDoc(1, ['retrieval' => 'core']) + retrDoc(20, ['retrieval' => 'binding', 'actions' => ['partial-refund']], str_repeat('Befugnis Kulanz. ', 17000)));
        CaseAgent::fake([retrAnswer(['actions' => ['partial-refund'], 'knowledge_ids' => ['POLICY-001']])]);

        retrAnalyze();

        expect(retrStored()->rule_check)->toBe('incomplete');
    });

    test('the page marks the draft as unchecked and cannot copy it until someone confirms having checked the rules', function () {
        CaseAgent::fake([retrAnswer(['actions' => ['partial-refund'], 'knowledge_ids' => ['POLICY-001']]), fn () => throw new RuntimeException('weg')]);
        retrAnalyze();
        $id = retrStored()->uuid;

        $page = test()->withCookie('staff_name', 'Nele')->get("/tickets/2137942?analyse={$id}")->assertOk();

        $page->assertSeeText('Regelprüfung unvollständig – der Entwurf ist ungeprüft')->assertSeeText('POLICY-020')->assertSeeText('Regeln selbst geprüft – Entwurf freigeben')
            ->assertSeeText('Erst kopierbar, wenn die Regelprüfung bestätigt ist')->assertSee('x-bind:disabled="true"', false);

        test()->withCredentials()->withCookie('staff_name', 'Nele')->postJson(route('tickets.analysis.copied', ['number' => '2137942', 'analysis' => $id]), ['text' => 'x'])->assertStatus(409)
            ->assertJsonFragment(['message' => 'Die Regelprüfung ist unvollständig und noch nicht bestätigt.']);
        expect(retrStored()->reply_copied_at)->toBeNull();
    });

    test('a person confirms the check; afterwards the draft can be copied and the confirmation is shown', function () {
        CaseAgent::fake([retrAnswer(['actions' => ['partial-refund'], 'knowledge_ids' => ['POLICY-001']]), fn () => throw new RuntimeException('weg')]);
        retrAnalyze();
        $id = retrStored()->uuid;

        test()->withCookie('staff_name', 'Cara')->post(route('tickets.analysis.rule-check', ['number' => '2137942', 'analysis' => $id]))
            ->assertRedirect()->assertSessionHas('success');

        expect(retrStored())->rule_check->toBe('incomplete')->rule_check_by->toBe('Cara')->rule_check_at->not->toBeNull();

        $page = test()->withCookie('staff_name', 'Nele')->get("/tickets/2137942?analyse={$id}");

        $page->assertSeeText('Von Cara am')->assertSeeText('als selbst geprüft bestätigt')->assertDontSeeText('Erst kopierbar, wenn die Regelprüfung bestätigt ist')->assertDontSeeText('Regeln selbst geprüft – Entwurf freigeben');

        test()->withCredentials()->withCookie('staff_name', 'Nele')->postJson(route('tickets.analysis.copied', ['number' => '2137942', 'analysis' => $id]), ['text' => 'Guten Tag, wir prüfen das.'])->assertOk();
        expect(retrStored()->reply_copied_at)->not->toBeNull();
    });

    test('a complete or unknown check cannot be confirmed and nothing changes', function () {
        CaseAgent::fake([retrAnswer(['knowledge_ids' => ['POLICY-001']])]);
        retrAnalyze();
        $id = retrStored()->uuid;

        test()->withCookie('staff_name', 'Cara')->post(route('tickets.analysis.rule-check', ['number' => '2137942', 'analysis' => $id]))->assertRedirect()->assertSessionHas('error');
        test()->withCookie('staff_name', 'Cara')->post(route('tickets.analysis.rule-check', ['number' => '2139999', 'analysis' => $id]))->assertRedirect()->assertSessionHas('error');

        expect(retrStored())->rule_check->toBe('complete')->rule_check_by->toBeNull();
    });

    test('a confirmation needs a chosen name', function () {
        CaseAgent::fake([retrAnswer(['actions' => ['partial-refund'], 'knowledge_ids' => ['POLICY-001']]), fn () => throw new RuntimeException('weg')]);
        retrAnalyze();
        $id = retrStored()->uuid;
        $this->defaultCookies = [];

        test()->post(route('tickets.analysis.rule-check', ['number' => '2137942', 'analysis' => $id]));

        expect(retrStored()->rule_check_at)->toBeNull();
    });

    test('the analysis overview for admins marks incomplete rule checks', function () {
        CaseAgent::fake([retrAnswer(['actions' => ['partial-refund'], 'knowledge_ids' => ['POLICY-001']]), fn () => throw new RuntimeException('weg')]);
        retrAnalyze();

        test()->withCookie('staff_name', 'Etienne')->get(route('analysis-overview.index'))->assertOk()->assertSeeText('Regelprüfung unvollständig');
    });
});

describe('the retrieval in the result view', function () {
    beforeEach(function () {
        config(['knowledge.retrieval.active' => true, 'staff.admins' => ['Etienne']]);
        knowledgeBase(
            retrDoc(1, ['retrieval' => 'core'])
            + retrDoc(2, ['retrieval' => 'triggered', 'triggers' => ['Rechnung']])
            + retrDoc(5, ['retrieval' => 'binding', 'when' => ['days_since_shipped' => ['min' => 14]]])
            + retrDoc(17, ['retrieval' => 'triggered', 'triggers' => ['Logo'], 'exclude_triggers' => ['unser Logo']]),
        );
    });

    test('it lists what came along with the reason, what was checked and not retrieved, and offers to switch it on', function () {
        retrTicket('Wir wollen ein Angebot mit unser Logo drucken.');
        CaseAgent::fake([retrAnswer(['knowledge_ids' => ['POLICY-001']])]);
        retrAnalyze();
        $id = retrStored()->uuid;

        $html = test()->withCookie('staff_name', 'Nele')->get("/tickets/2137942?analyse={$id}")->assertOk()->getContent();

        expect($html)->toContain('Wissensabruf')->toContain('Grundwissen')->toContain('Voraussetzung ungeklärt')->toContain('Geprüft, nicht abgerufen (2)')
            ->toContain('kein Auslöser im Anliegen')->toContain('Unterdrückte Treffer')->toContain('Auslöser „Logo“ durch „unser Logo“')
            ->toContain('Zuschalten und neu analysieren')->toContain('name="zusatzwissen[]" value="POLICY-002"')->toContain('name="zusatzwissen[]" value="POLICY-017"')
            ->toContain('name="kundengruppe" value="reseller"');
    });

    test('technical details are only for admins', function () {
        retrTicket('Meine Tasse ist kaputt.');
        CaseAgent::fake([retrAnswer(['knowledge_ids' => ['POLICY-001']])]);
        retrAnalyze();
        $id = retrStored()->uuid;

        test()->withCookie('staff_name', 'Nele')->get("/tickets/2137942?analyse={$id}")->assertDontSeeText('Bewertete Bedingungen')->assertDontSeeText('Abrufregeln 2026-10-13.1');
        test()->withCookie('staff_name', 'Etienne')->get("/tickets/2137942?analyse={$id}")->assertSeeText('Bewertete Bedingungen (nur Admins)')->assertSeeText('Abrufregeln 2026-10-13.1');
    });

    test('an analysis from before the retrieval shows no such section', function () {
        config(['knowledge.retrieval.active' => false]);
        retrTicket('Meine Tasse ist kaputt.');
        CaseAgent::fake([retrAnswer(['knowledge_ids' => ['POLICY-001']])]);
        retrAnalyze();
        $id = retrStored()->uuid;

        test()->withCookie('staff_name', 'Nele')->get("/tickets/2137942?analyse={$id}")->assertOk()->assertDontSeeText('Geprüft, nicht abgerufen')->assertDontSeeText('Regelprüfung unvollständig');
    });

    test('switching a document on by hand sends it with the note and keeps it in the log', function () {
        retrTicket('Wo ist meine Bestellung?');
        CaseAgent::fake([retrAnswer(['knowledge_ids' => ['POLICY-001']]), retrAnswer(['knowledge_ids' => ['POLICY-002']])]);
        retrAnalyze();

        retrAnalyze(['zusatzwissen' => ['POLICY-002']]);

        $stored = retrStored();

        expect($stored->content['sent_input'])->toContain('## POLICY-002')->toContain('Hinweis der App: Dieses Wissen wurde vom Mitarbeiter von Hand zugeschaltet')
            ->and($stored->content['retrieval']['manual'])->toBe(['POLICY-002'])
            ->and(collect($stored->content['retrieval']['documents'])->firstWhere('id', 'POLICY-002')['reasons'])->toBe(['von Hand zugeschaltet']);
    });

    test('only well formed document IDs are accepted', function () {
        retrTicket('Wo ist meine Bestellung?');
        CaseAgent::fake([retrAnswer()]);

        retrAnalyze(['zusatzwissen' => ['kein-id-format; drop table']]);

        CaseAgent::assertNeverPrompted();
        expect(Analysis::query()->count())->toBe(0);
    });
});
