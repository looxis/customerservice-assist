<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\AnalysisStore;
use App\Knowledge\KnowledgeReviewLog;
use App\Models\Analysis;
use App\Models\KnowledgeReviewFlag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withCookie('advanced_mode', '1');
    $this->withoutVite();
    config([
        'staff.admins' => ['Etienne'],
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
    ]);
    knowledgeBase([
        'policies/policy-001-a.md' => knowledgeDoc(['status' => 'active', 'retrieval' => 'core', 'summary' => 'Eine Regel.'], "Gilt für alle Kundenarten.\n\n# Regel\n\nEin Satz zur Regel."),
        'policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002', 'title' => 'Zweite Regel', 'status' => 'active']),
    ]);
});

afterEach(function () {
    cleanUpKnowledgeBases();
});

function reportDocument(array $fields = [], string $staff = 'Nele')
{
    return test()->withCookie('staff_name', $staff)->post(route('knowledge.review.store'), ['document_id' => 'POLICY-001', 'reason' => 'not-helping', 'comment' => 'Kunden reagieren verärgert.', ...$fields]);
}

describe('reporting', function () {
    test('anyone with a chosen name reports a document, with reason, comment and the state of the document', function () {
        reportDocument()->assertRedirect()->assertSessionHas('review_reported', 'POLICY-001');

        $flag = KnowledgeReviewFlag::query()->firstOrFail();
        expect($flag->document_id)->toBe('POLICY-001')->and($flag->staff_name)->toBe('Nele')->and($flag->reason)->toBe('not-helping')->and($flag->comment)->toBe('Kunden reagieren verärgert.')
            ->and($flag->status)->toBe('open')->and($flag->document_fingerprint)->not->toBeNull();
    });

    test('the comment is stored encrypted', function () {
        reportDocument();

        expect(DB::table('knowledge_review_flags')->value('comment'))->not->toContain('verärgert');
    });

    test('without a chosen name nothing is stored', function () {
        test()->post(route('knowledge.review.store'), ['document_id' => 'POLICY-001', 'reason' => 'other'])->assertRedirect();

        expect(KnowledgeReviewFlag::query()->count())->toBe(0);
    });

    test('an unknown document, an invalid id and an invalid reason are refused', function () {
        reportDocument(['document_id' => 'POLICY-099'])->assertSessionHas('review_error');
        reportDocument(['document_id' => '../etc/passwd'])->assertSessionHasErrors('document_id');
        reportDocument(['reason' => 'wegen-laune'])->assertSessionHasErrors('reason');
        reportDocument(['comment' => str_repeat('a', 1001)])->assertSessionHasErrors('comment');

        expect(KnowledgeReviewFlag::query()->count())->toBe(0);
    });

    test('a second report of the same person for the same document replaces the first', function () {
        reportDocument();
        reportDocument(['reason' => 'rule-outdated', 'comment' => 'Jetzt anders.']);

        expect(KnowledgeReviewFlag::query()->count())->toBe(1)->and(KnowledgeReviewFlag::query()->first()->reason)->toBe('rule-outdated')->and(KnowledgeReviewFlag::query()->first()->comment)->toBe('Jetzt anders.');
    });

    test('too many reports in a minute are slowed down', function () {
        foreach (range(1, 20) as $ignored) {
            reportDocument(['reason' => 'wegen-laune']);
        }

        reportDocument()->assertStatus(429);
    });
});

describe('reported several times', function () {
    test('three open reports from two people, not from one person', function () {
        foreach (['Nele', 'Nele', 'Nele'] as $name) {
            KnowledgeReviewFlag::query()->create(['document_id' => 'POLICY-001', 'staff_name' => $name.'x', 'reason' => 'other', 'status' => 'open']);
        }
        KnowledgeReviewFlag::query()->where('staff_name', 'Nelex')->update(['staff_name' => 'Nele']);

        expect(app(KnowledgeReviewLog::class)->openCounts()['POLICY-001'])->toBe(['flags' => 3, 'staff' => 1, 'many' => false]);

        KnowledgeReviewFlag::query()->latest('id')->first()->update(['staff_name' => 'Cara']);

        expect(app(KnowledgeReviewLog::class)->openCounts()['POLICY-001'])->toBe(['flags' => 3, 'staff' => 2, 'many' => true]);
    });

    test('closed reports do not count', function () {
        foreach (['Nele', 'Cara', 'Kerstin'] as $name) {
            KnowledgeReviewFlag::query()->create(['document_id' => 'POLICY-001', 'staff_name' => $name, 'reason' => 'other', 'status' => 'checked']);
        }

        expect(app(KnowledgeReviewLog::class)->openCounts())->toBe([]);
    });

    test('the list, the document page and a suggestion say so', function () {
        foreach (['Nele', 'Cara', 'Kerstin'] as $name) {
            KnowledgeReviewFlag::query()->create(['document_id' => 'POLICY-001', 'staff_name' => $name, 'reason' => 'other', 'status' => 'open']);
        }

        test()->get('/knowledge')->assertOk()->assertSeeText('Mehrfach zur Prüfung gemeldet');
        test()->get(route('knowledge.show', ['path' => 'policies/policy-001-a.md']))->assertOk()->assertSeeText('Mehrfach zur Prüfung gemeldet: Mitarbeiter halten dieses Wissen für möglicherweise veraltet');
        test()->get(route('knowledge.show', ['path' => 'policies/policy-002-b.md']))->assertOk()->assertDontSeeText('Mehrfach zur Prüfung gemeldet');
    });
});

describe('the buttons', function () {
    test('every document page offers the report to everyone', function () {
        test()->get(route('knowledge.show', ['path' => 'policies/policy-001-a.md']))->assertOk()->assertSeeText('Möglicherweise veraltet? Bitte prüfen lassen')->assertSee('action="'.route('knowledge.review.store').'"', false);
    });

    test('every cited source in a suggestion offers it, with the case', function () {
        Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
        fakeZammad([zammadArticle(['body' => '<p>Frage</p>'])]);
        CaseAgent::fake([[
            'summary' => ['incident' => 'Frage', 'customer_wish' => 'Auskunft'], 'category' => 'order-process-question', 'case_pattern' => null, 'assessment' => null,
            'recommendation' => 'Antworten.', 'actions' => [], 'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
            'reasoning' => 'Nach POLICY-001.', 'knowledge_gaps' => [], 'knowledge_ids' => ['POLICY-001'], 'confidence' => ['level' => 'NIEDRIG', 'reasons' => []],
            'next_step' => 'reply', 'decision' => null, 'internal_steps' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag.'],
        ]]);
        $location = test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kein_produktbezug' => '1', 'kundengruppe' => 'reseller', 'variante' => 'verlauf'])->headers->get('Location');
        parse_str((string) parse_url((string) $location, PHP_URL_QUERY), $query);

        $html = test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942?analyse='.$query['analyse'])->assertOk()->assertSeeText('Möglicherweise veraltet? Bitte prüfen lassen')->getContent();

        expect($html)->toContain('name="document_id" value="POLICY-001"')->toContain('name="analysis" value="'.$query['analyse'].'"')->toContain('name="ticket" value="2137942"');
    });
});

describe('the admin tab', function () {
    test('only admins see the reports', function () {
        reportDocument();

        test()->withCookie('staff_name', 'Nele')->get(route('knowledge-gaps.index', ['status' => 'review']))->assertForbidden();
        test()->withCookie('staff_name', 'Etienne')->get(route('knowledge-gaps.index', ['status' => 'review']))->assertOk()->assertSeeText('Zur Prüfung (1)')->assertSeeText('POLICY-001')->assertSeeText('Kunden reagieren verärgert.')->assertSeeText('Hilft nicht / führt nicht zum Ziel');
    });

    test('the tab says so when nothing is reported', function () {
        test()->withCookie('staff_name', 'Etienne')->get(route('knowledge-gaps.index', ['status' => 'review']))->assertOk()->assertSeeText('Es liegen keine Meldungen zur Prüfung vor.');
    });

    test('"checked, stays" closes the reports of that document only, and only for admins', function () {
        reportDocument();
        reportDocument(['document_id' => 'POLICY-002'], 'Cara');

        test()->withCookie('staff_name', 'Nele')->post(route('knowledge.review.resolve', ['documentId' => 'POLICY-001']))->assertForbidden();
        expect(KnowledgeReviewFlag::query()->where('status', 'open')->count())->toBe(2);

        test()->withCookie('staff_name', 'Etienne')->post(route('knowledge.review.resolve', ['documentId' => 'POLICY-001']))->assertRedirect(route('knowledge-gaps.index', ['status' => 'review']));

        $closed = KnowledgeReviewFlag::query()->where('document_id', 'POLICY-001')->firstOrFail();
        expect($closed->status)->toBe('checked')->and($closed->resolved_by)->toBe('Etienne')->and($closed->resolved_at)->not->toBeNull()
            ->and(KnowledgeReviewFlag::query()->where('document_id', 'POLICY-002')->value('status'))->toBe('open');
    });

    test('an invalid document id is not found', function () {
        test()->withCookie('staff_name', 'Etienne')->post(route('knowledge.review.resolve', ['documentId' => 'unsinn']))->assertNotFound();
    });

    test('the text for the AI chat has the document, the reports and the file content', function () {
        reportDocument();

        $text = app(KnowledgeReviewLog::class)->chatText('POLICY-001');

        expect($text)->toContain('Dokument: POLICY-001 – Titel (Status active)')->toContain('Meldung von Nele')->toContain('Hilft nicht / führt nicht zum Ziel – Kunden reagieren verärgert.')
            ->toContain('policies/policy-001-a.md')->toContain('Ein Satz zur Regel.');
    });

    test('a report about a document that is gone is still listed', function () {
        reportDocument();
        KnowledgeReviewFlag::query()->create(['document_id' => 'POLICY-077', 'staff_name' => 'Cara', 'reason' => 'other', 'status' => 'open']);

        test()->withCookie('staff_name', 'Etienne')->get(route('knowledge-gaps.index', ['status' => 'review']))->assertOk()->assertSeeText('POLICY-077')->assertSeeText('Dokument nicht mehr vorhanden');
    });

    test('a changed document is pointed out', function () {
        reportDocument();
        KnowledgeReviewFlag::query()->update(['document_fingerprint' => str_repeat('0', 64)]);

        test()->withCookie('staff_name', 'Etienne')->get(route('knowledge-gaps.index', ['status' => 'review']))->assertOk()->assertSeeText('Seit einer Meldung geändert');
    });
});

describe('retention', function () {
    test('reports are deleted with the other content after the retention period', function () {
        reportDocument();

        $this->travel(13)->months();
        app(AnalysisStore::class)->purge();

        expect(KnowledgeReviewFlag::query()->count())->toBe(0);
    });

    test('the analyses stay unchanged by a report', function () {
        reportDocument();

        expect(Analysis::query()->count())->toBe(0);
    });
});
