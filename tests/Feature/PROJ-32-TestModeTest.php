<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\Agents\SummaryAgent;
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
        'staff.admins' => ['Etienne'],
    ]);

    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);

    fakeZammad([
        zammadArticle(['id' => 11, 'body' => '<p>Können zwei Bestellungen auf eine Rechnung? Bestellung 402-0000000-0000001</p>', 'created_at' => '2026-10-01T07:12:00.000Z']),
        zammadArticle(['id' => 12, 'sender' => 'Agent', 'from' => 'Kundenservice', 'body' => '<p>Nein, das geht leider nicht.</p>', 'created_at' => '2026-10-01T09:00:00.000Z']),
        zammadArticle(['id' => 13, 'body' => '<p>Schade, dann bitte stornieren. Bestellung 402-0000000-0000002</p>', 'created_at' => '2026-10-02T08:00:00.000Z']),
        zammadArticle(['id' => 14, 'sender' => 'Agent', 'from' => 'Kundenservice', 'body' => '<p>Storniert.</p>', 'created_at' => '2026-10-02T10:00:00.000Z']),
    ], ['state' => 'closed']);
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function asTester(string $name = 'Etienne', bool $testMode = true): object
{
    test()->withCookie('staff_name', $name);

    if ($testMode) {
        test()->withCookie('test_mode', '1');
    }

    return test();
}

function analyzeRewound(array $fields = []): TestResponse
{
    return test()->post(route('tickets.analysis.run', ['number' => '2137942']), array_merge([
        'kundengruppe' => 'reseller', 'variante' => 'verlauf', 'kontext' => '', 'stand' => '11',
    ], $fields));
}

function caseResult(): array
{
    return [
        'summary' => ['incident' => 'x', 'customer_wish' => 'y'], 'category' => 'complaint', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'z', 'actions' => [], 'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'r', 'missing_information' => [], 'knowledge_gaps' => [], 'knowledge_ids' => [], 'confidence' => ['level' => 'NIEDRIG', 'reasons' => []],
        'internal_todos' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Hallo'],
    ];
}

describe('switch', function () {
    test('an admin sees the switch and can turn the test mode on and off in this browser', function () {
        asTester(testMode: false)->get('/tickets/2137942')->assertSeeText('Testmodus')->assertDontSeeText('Testmodus aktiv');

        $on = asTester(testMode: false)->from('/tickets/2137942')->post(route('test-mode.switch'), ['aktiv' => '1']);
        $on->assertRedirect('/tickets/2137942')->assertCookie('test_mode', '1');

        asTester()->get('/tickets/2137942')->assertSeeText('Testmodus aktiv');

        asTester()->post(route('test-mode.switch'), ['aktiv' => '0'])->assertCookieExpired('test_mode');
    });

    test('others see nothing and may not switch, even with the cookie set', function () {
        asTester('Nele')->get('/tickets/2137942')
            ->assertDontSeeText('Testmodus')
            ->assertDontSeeText('Bis hierher testen');

        asTester('Nele')->post(route('test-mode.switch'), ['aktiv' => '1'])->assertForbidden();
    });

    test('without the switch on, an admin sees no rewind buttons', function () {
        asTester(testMode: false)->get('/tickets/2137942')->assertDontSeeText('Bis hierher testen');
    });
});

describe('rewinding', function () {
    test('every customer message offers "Bis hierher testen"', function () {
        $html = asTester()->get('/tickets/2137942')->getContent();

        expect(substr_count($html, '>Bis hierher testen</a>'))->toBe(2)
            ->and($html)->toContain('stand=11')->toContain('stand=13');
    });

    test('a rewound ticket ends with the chosen message, later ones are collapsed for comparison', function () {
        asTester()->get('/tickets/2137942?stand=11')
            ->assertSeeTextInOrder(['Testlauf:', 'Stand bis zur Kundennachricht vom 01.10.2026, 09:12 Uhr', 'Ganzen Verlauf zeigen'])
            ->assertSeeTextInOrder(['Können zwei Bestellungen', '3 spätere Nachrichten (nicht an die KI)', 'Nein, das geht leider nicht.', 'Storniert.']);
    });

    test('the preview and the order suggestions only use the rewound thread', function () {
        $html = asTester()->get('/tickets/2137942?stand=11')->getContent();
        $preview = str($html)->after('Was an die KI geht')->before('Zusätzliche Informationen / eigene Einschätzung')->toString();

        expect($preview)->toContain('Können zwei Bestellungen')->not->toContain('Schade, dann bitte stornieren')
            ->and(str($html)->before('Was an die KI geht')->toString())->not->toContain('402-0000000-0000002');
    });

    test('only the rewound thread is sent to the ai and the result is marked as a test run', function () {
        CaseAgent::fake([caseResult()]);

        $response = asTester()->from('/tickets/2137942?stand=11')->withCookie('test_mode', '1')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kundengruppe' => 'reseller', 'variante' => 'verlauf', 'kontext' => '', 'stand' => '11']);

        $response->assertRedirect();
        expect((string) $response->headers->get('Location'))->toContain('stand=11');
        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Können zwei Bestellungen') && ! $prompt->contains('Nein, das geht leider nicht') && ! $prompt->contains('stornieren'));

        asTester()->get($response->headers->get('Location'))->assertSeeText('Testlauf (Stand bis Nachricht vom 01.10.2026, 09:12)');
    });

    test('a test run does not remember the customer group for the customer, but for the ticket', function () {
        CaseAgent::fake([caseResult()]);

        asTester();
        analyzeRewound();

        expect(app(AnalysisStore::class)->customerGroup('customer-77'))->toBeNull()
            ->and(app(AnalysisStore::class)->caseChoice('2137942')['group'])->toBe('reseller');
    });

    test('the summary of a test run is kept apart from the real one', function () {
        SummaryAgent::fake([['facts' => 'Test-Fassung', 'timeline' => '', 'agreements' => '', 'open_questions' => '']]);

        asTester()->post(route('tickets.summary.create', ['number' => '2137942']), ['stand' => '13'])->assertRedirect();

        expect(app(AnalysisStore::class)->summary('2137942'))->toBeNull()
            ->and(app(AnalysisStore::class)->summary('2137942.stand-13'))->not->toBeNull();
        SummaryAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Nein, das geht leider nicht') && ! $prompt->contains('Storniert.'));

        asTester()->get('/tickets/2137942')->assertDontSeeText('Test-Fassung');
        asTester()->get('/tickets/2137942?stand=13')->assertSeeText('Test-Fassung');
    });

    test('a cut point that is no customer message of this ticket shows a hint and the full thread', function (string $stand) {
        asTester()->get('/tickets/2137942?stand='.$stand)
            ->assertSeeText('Stand nicht gefunden')
            ->assertDontSeeText('spätere Nachricht');
    })->with(['our reply' => ['12'], 'unknown' => ['999'], 'not a number' => ['abc']]);

    test('for others a cut point in address or form is ignored', function () {
        CaseAgent::fake([caseResult()]);

        asTester('Nele')->get('/tickets/2137942?stand=11')->assertDontSeeText('Testlauf')->assertDontSeeText('spätere Nachricht');

        asTester('Nele');
        analyzeRewound();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Storniert.'));
        expect(app(AnalysisStore::class)->customerGroup('customer-77'))->toBe('reseller');
    });

    test('the context field shows the test mode hint', function () {
        asTester()->get('/tickets/2137942')->assertSeeText('Testmodus: Hier keine Regeln oder Antworten eintragen');
        asTester('Nele')->get('/tickets/2137942')->assertDontSeeText('Hier keine Regeln oder Antworten eintragen');
    });
});

describe('qa additions', function () {
    test('the banner shows on every page while the test mode is on', function () {
        asTester()->get(route('knowledge.index'))->assertSeeText('Testmodus aktiv');
        asTester()->get(route('about'))->assertSeeText('Testmodus aktiv');
    });

    test('rewound to the first message there are no variants, only that message goes to the ai', function () {
        CaseAgent::fake([caseResult()]);

        asTester()->get('/tickets/2137942?stand=11')->assertDontSeeText('Was soll an die KI gehen?');
        analyzeRewound(['stand' => '11']);

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Können zwei Bestellungen') && ! $prompt->contains('Nein, das geht leider nicht'));
    });

    test('rewound to the last customer message with only our reply after it, one later message is collapsed', function () {
        asTester()->get('/tickets/2137942?stand=13')
            ->assertSeeText('1 spätere Nachricht (nicht an die KI)')
            ->assertSeeText('Testlauf:');
    });

    test('an edited summary of a test run is saved under the cut point only', function () {
        SummaryAgent::fake([['facts' => 'Test-Fassung', 'timeline' => '', 'agreements' => '', 'open_questions' => '']]);
        asTester()->post(route('tickets.summary.create', ['number' => '2137942']), ['stand' => '13']);

        asTester()->put(route('tickets.summary.update', ['number' => '2137942']), ['stand' => '13', 'zusammenfassung' => 'Korrigiert im Test'])->assertRedirect();

        expect(app(AnalysisStore::class)->summary('2137942.stand-13')->text)->toBe('Korrigiert im Test')
            ->and(app(AnalysisStore::class)->summary('2137942'))->toBeNull();
    });

    test('"Ganzen Verlauf zeigen" leads to the ticket without cut point', function () {
        asTester()->get('/tickets/2137942?stand=11')->assertSee('href="'.route('tickets.show', ['number' => '2137942']).'"', false);
    });

    test('switching never redirects to another site', function () {
        asTester(testMode: false)->withHeader('referer', 'https://evil.example/x')->post(route('test-mode.switch'), ['aktiv' => '1'])
            ->assertRedirect(route('tickets.analyze'));
    });

    test('the switch form is protected and validated', function () {
        expect(asTester(testMode: false)->get('/tickets/2137942')->getContent())->toMatch('/action="[^"]*\/testmodus"[^>]*>\s*<input type="hidden" name="_token"/');

        asTester(testMode: false)->post(route('test-mode.switch'), ['aktiv' => 'vielleicht'])->assertSessionHasErrors('aktiv');
    });
});
