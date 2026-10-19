<?php

use App\Analysis\AppStats;
use App\Models\KnowledgeReviewFlag;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->withoutVite();
    config(['staff.admins' => ['Etienne']]);
    $this->travelTo(CarbonImmutable::parse('2027-02-10 10:00', 'Europe/Berlin'));
});

afterEach(function () {
    cleanUpKnowledgeBases();
});

describe('QA: red team', function () {
    test('markup in a comment is shown as text to the admin', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['status' => 'active'])]);

        test()->withCookie('staff_name', 'Nele')->post(route('knowledge.review.store'), ['document_id' => 'POLICY-001', 'reason' => 'other', 'comment' => '<script>alert(1)</script><img src=x onerror=alert(2)>'])->assertRedirect();
        $html = test()->withCookie('staff_name', 'Etienne')->get(route('knowledge-gaps.index', ['status' => 'review']))->assertOk()->getContent();

        expect($html)->not->toContain('<script>alert(1)</script>')->not->toContain('<img src=x')->toContain('&lt;script&gt;alert(1)&lt;/script&gt;');
    });

    test('the reports are not visible to ordinary staff, not even their comments', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['status' => 'active'])]);
        KnowledgeReviewFlag::query()->create(['document_id' => 'POLICY-001', 'staff_name' => 'Cara', 'reason' => 'other', 'comment' => 'Geheimer Kommentar', 'status' => 'open']);

        $page = test()->withCookie('staff_name', 'Nele')->get(route('knowledge.show', ['path' => 'policies/policy-001-a.md']))->assertOk()->getContent();

        expect($page)->not->toContain('Geheimer Kommentar');
        test()->withCookie('staff_name', 'Nele')->get(route('knowledge-gaps.index', ['status' => 'review']))->assertForbidden();
    });

    test('the decision cannot reach a file outside the knowledge base', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['status' => 'active', 'trial_until' => '2027-03-31', 'trial_goal' => 'x'])]);

        foreach (['../../.env', '..%2F..%2F.env', 'policies/../../composer.json'] as $path) {
            test()->withCookie('staff_name', 'Etienne')->post('/knowledge/dokument/'.$path.'/testphase', ['decision' => 'end', 'fingerprint' => str_repeat('a', 64)])->assertSessionHas('status_error');
        }

        expect(file_exists(base_path('.env')))->toBeTrue();
    });

    test('a template or an example cannot be reported or decided about', function () {
        knowledgeBase(['templates/policy.md' => knowledgeDoc(['id' => 'POLICY-000', 'trial_until' => '2027-03-31', 'trial_goal' => 'x'])]);

        test()->withCookie('staff_name', 'Etienne')->post(route('knowledge.trial', ['path' => 'templates/policy.md']), ['decision' => 'end', 'fingerprint' => str_repeat('a', 64)])->assertSessionHas('status_error');
    });
});

describe('QA: after the end of a trial', function () {
    test('a document that was ended no longer counts as to be reviewed', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['status' => 'deprecated', 'trial_until' => '2027-01-31', 'trial_goal' => 'x'])]);

        expect(app(AppStats::class)->review())->toBe(0);
    })->todo('QA-Fehler PROJ-56-B2: ein beendetes (deprecated) Dokument zählt weiter als „zur Überprüfung“.');

    test('the check does not warn about a trial of a document that is deprecated', function () {
        $library = knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['status' => 'deprecated', 'trial_until' => '2027-01-31', 'trial_goal' => 'x'])]);

        expect(messagesOf($library, 'warning'))->not->toContain('Testphase');
    })->todo('QA-Fehler PROJ-56-B2: `knowledge:check` warnt weiter vor der Testphase eines beendeten Dokuments.');

    test('a deprecated document is offered no decision about its trial', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['status' => 'deprecated', 'trial_until' => '2027-01-31', 'trial_goal' => 'x'])]);

        test()->withCookie('staff_name', 'Etienne')->get(route('knowledge.show', ['path' => 'policies/policy-001-a.md']))->assertOk()->assertDontSeeText('Entscheidung zur Testphase');
    })->todo('QA-Fehler PROJ-56-B2: für ein beendetes Dokument wird weiter die Entscheidung zur Testphase angeboten.');
});
