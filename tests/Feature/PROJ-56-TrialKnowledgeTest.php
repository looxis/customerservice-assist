<?php

use App\Analysis\Agents\CaseAgent;
use App\Models\Analysis;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();
    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
    ]);
    $this->travelTo(CarbonImmutable::parse('2026-10-19 10:00', 'Europe/Berlin'));
});

afterEach(function () {
    cleanUpKnowledgeBases();
});

describe('the fields', function () {
    test('trial_until is the last day, written as text or as a plain YAML date', function () {
        $quoted = knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['trial_until' => '2027-01-31', 'trial_goal' => 'Hilft es?'])]);
        $document = $quoted->find('POLICY-001');

        expect($document->trialUntil()?->format('Y-m-d'))->toBe('2027-01-31')->and($document->isTrial())->toBeTrue()->and($document->trialGoal())->toBe('Hilft es?')
            ->and($document->trialExpired())->toBeFalse();

        $plain = knowledgeBase(['policies/policy-001-a.md' => "---\nid: POLICY-001\ntitle: Titel\ntype: policy\nstatus: active\ntrial_until: 2027-01-31\n---\n\nGilt für alle Kundenarten.\n\n# Regel\n\nEin Satz.\n"]);

        expect($plain->find('POLICY-001')->trialUntil()?->format('Y-m-d'))->toBe('2027-01-31');
    });

    test('a document without the fields is no trial', function () {
        $document = knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc()])->find('POLICY-001');

        expect($document->isTrial())->toBeFalse()->and($document->trialExpired())->toBeFalse()->and($document->trialUntil())->toBeNull();
    });

    test('the trial is over after its last day, not on it', function () {
        $document = knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['trial_until' => '2027-01-31', 'trial_goal' => 'x'])])->find('POLICY-001');

        expect($document->trialExpired(CarbonImmutable::parse('2027-01-31 23:30', 'Europe/Berlin')))->toBeFalse()
            ->and($document->trialExpired(CarbonImmutable::parse('2027-02-01 00:30', 'Europe/Berlin')))->toBeTrue();
    });
});

describe('the check', function () {
    test('a valid trial with a goal is fine', function () {
        $library = knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['trial_until' => '2027-01-31', 'trial_goal' => 'Hilft es?'])]);

        expect(messagesOf($library, 'error'))->not->toContain('trial')->and(messagesOf($library, 'warning'))->not->toContain('Testphase')->not->toContain('Unbekanntes Feld');
    });

    test('an invalid date is an error', function () {
        foreach (['31.01.2027', '2027-02-30', 'bald'] as $value) {
            $library = knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['trial_until' => $value, 'trial_goal' => 'x'])]);

            expect(messagesOf($library, 'error'))->toContain('`trial_until` muss ein Datum im Format JJJJ-MM-TT sein');
        }
    });

    test('a goal without an end date and a trial without a goal are warned about', function () {
        expect(messagesOf(knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['trial_goal' => 'x'])]), 'warning'))->toContain('`trial_goal` ohne `trial_until`')
            ->and(messagesOf(knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['trial_until' => '2027-01-31'])]), 'warning'))->toContain('fehlt `trial_goal`');
    });

    test('a trial that ends within two weeks and one that is over are warned about', function () {
        expect(messagesOf(knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['trial_until' => '2026-10-30', 'trial_goal' => 'x'])]), 'warning'))->toContain('Die Testphase endet am 30.10.2026')
            ->and(messagesOf(knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['trial_until' => '2026-10-01', 'trial_goal' => 'x'])]), 'warning'))->toContain('Die Testphase ist am 01.10.2026 abgelaufen');
    });
});

describe('the pages', function () {
    test('the document page and the list show the trial and its goal', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['status' => 'active', 'trial_until' => '2027-01-31', 'trial_goal' => 'Hilft es?'])]);

        test()->get(route('knowledge.show', ['path' => 'policies/policy-001-a.md']))->assertOk()->assertSeeText('Vorläufig: Dieses Vorgehen wird nur bis 31.01.2027 getestet')->assertSeeText('Ziel: Hilft es?');
        test()->get('/knowledge')->assertOk()->assertSeeText('Vorläufig bis 31.01.2027');
    });

    test('after the end the pages say the trial is over', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['status' => 'active', 'trial_until' => '2027-01-31', 'trial_goal' => 'x'])]);
        $this->travelTo(CarbonImmutable::parse('2027-02-10 09:00', 'Europe/Berlin'));

        test()->get(route('knowledge.show', ['path' => 'policies/policy-001-a.md']))->assertOk()->assertSeeText('Testphase abgelaufen, Überprüfung überfällig seit 10 Tagen: Dieses Vorgehen wurde nur bis 31.01.2027 getestet');
        test()->get('/knowledge')->assertOk()->assertSeeText('Vorläufig bis 31.01.2027 – abgelaufen, Überprüfung überfällig seit 10 Tg.');
    });
});

describe('in a suggestion', function () {
    function trialSuggestion(): string
    {
        CaseAgent::fake([[
            'summary' => ['incident' => 'Frage', 'customer_wish' => 'Auskunft'],
            'category' => 'order-process-question', 'case_pattern' => null, 'assessment' => null,
            'recommendation' => 'Antworten.', 'actions' => [],
            'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
            'reasoning' => 'Nach POLICY-001.', 'knowledge_gaps' => [],
            'knowledge_ids' => ['POLICY-001'], 'confidence' => ['level' => 'NIEDRIG', 'reasons' => []],
            'next_step' => 'reply', 'decision' => null, 'internal_steps' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag.'],
        ]]);
        $location = test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kein_produktbezug' => '1', 'kundengruppe' => 'reseller', 'variante' => 'verlauf'])->headers->get('Location');
        parse_str((string) parse_url((string) $location, PHP_URL_QUERY), $query);

        return $query['analyse'];
    }

    beforeEach(function () {
        Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
        fakeZammad([zammadArticle(['body' => '<p>Frage</p>'])]);
    });

    test('the model is told that the procedure is provisional, with the goal', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['status' => 'active', 'retrieval' => 'core', 'summary' => 'Eine Regel.', 'trial_until' => '2027-01-31', 'trial_goal' => 'Hilft es?'])]);

        $id = trialSuggestion();
        $sent = Analysis::query()->where('uuid', $id)->firstOrFail()->content['sent_input'];

        expect($sent)->toContain('## POLICY-001 – Titel (Policies, in Erprobung)')->toContain('Hinweis: Dieses Vorgehen ist vorläufig und wird nur bis 31.01.2027 getestet (Ziel: Hilft es?).')
            ->and($sent)->not->toContain('abgelaufen');
    });

    test('the result names the provisional source and, after the end, that the review is overdue', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['status' => 'active', 'retrieval' => 'core', 'summary' => 'Eine Regel.', 'trial_until' => '2027-01-31', 'trial_goal' => 'Hilft es?'])]);
        $id = trialSuggestion();

        test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942?analyse='.$id)->assertOk()
            ->assertSeeText('Beruht teilweise auf vorläufigem Wissen (in Erprobung)')->assertSeeText('bis 31.01.2027')->assertSeeText('Ziel: Hilft es?')->assertSeeText('Vorläufig bis 31.01.2027')
            ->assertDontSeeText('Überprüfung überfällig');

        $this->travelTo(CarbonImmutable::parse('2027-02-10 09:00', 'Europe/Berlin'));

        test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942?analyse='.$id)->assertOk()->assertSeeText('Testphase abgelaufen, Überprüfung überfällig');
    });

    test('a document that is no trial gets no note', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['status' => 'active', 'retrieval' => 'core', 'summary' => 'Eine Regel.'])]);
        $id = trialSuggestion();

        $sent = Analysis::query()->where('uuid', $id)->firstOrFail()->content['sent_input'];
        expect($sent)->not->toContain('in Erprobung')->not->toContain('vorläufig');
        test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942?analyse='.$id)->assertOk()->assertDontSeeText('vorläufigem Wissen');
    });
});
