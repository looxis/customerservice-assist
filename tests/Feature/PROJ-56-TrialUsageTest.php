<?php

use App\Knowledge\DocumentEvidence;
use App\Models\Analysis;
use App\Models\KnowledgeReviewFlag;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->withoutVite();
    config(['staff.admins' => ['Etienne']]);
    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['status' => 'active', 'trial_until' => '2027-01-31', 'trial_goal' => 'Hilft es?'])]);
});

afterEach(function () {
    cleanUpKnowledgeBases();
});

function usedAnalysis(array $attributes = [], string $id = 'POLICY-001'): Analysis
{
    return Analysis::query()->create([
        'uuid' => (string) Str::uuid(), 'staff_name' => 'Nele', 'ticket_number' => (string) random_int(1000000, 9999999), 'scope_key' => 'x', 'status' => 'completed',
        'knowledge_ids' => [$id], 'knowledge_fingerprints' => [$id => str_repeat('a', 64)], ...$attributes,
    ]);
}

describe('the figures', function () {
    test('they tell what became of the drafts, without test runs and failures', function () {
        usedAnalysis(['reply_sent_at' => now(), 'reply_copied_at' => now(), 'reply_copied_changed' => false]);
        usedAnalysis(['reply_sent_at' => now(), 'reply_copied_at' => now(), 'reply_copied_changed' => true]);
        usedAnalysis(['reply_copied_at' => now(), 'reply_copied_changed' => false]);
        usedAnalysis(['reply_copied_at' => now(), 'reply_copied_changed' => true]);
        usedAnalysis(['reply_edited_at' => now()]);
        usedAnalysis();
        usedAnalysis(['test_until' => now(), 'reply_sent_at' => now()]);
        usedAnalysis(['status' => 'failed']);
        usedAnalysis([], 'POLICY-009');

        $usage = app(DocumentEvidence::class)->usage('POLICY-001');

        expect($usage['named'])->toBe(6)->and($usage['outcomes'])->toBe(['sent_unchanged' => 1, 'sent_changed' => 1, 'copied_unchanged' => 1, 'copied_changed' => 1, 'edited' => 1, 'none' => 1]);
    });

    test('the recent ones are those of the last 30 days', function () {
        usedAnalysis();
        $old = usedAnalysis();
        Analysis::query()->whereKey($old->id)->update(['created_at' => now()->subDays(45)]);

        $usage = app(DocumentEvidence::class)->usage('POLICY-001');

        expect($usage['named'])->toBe(2)->and($usage['recent'])->toBe(1);
    });

    test('the share of usable ratings is compared with all proposals', function () {
        usedAnalysis(['feedback_level' => 'unchanged']);
        usedAnalysis(['feedback_level' => 'slight']);
        usedAnalysis(['feedback_level' => 'major']);
        usedAnalysis(['feedback_level' => 'discarded']);
        usedAnalysis();
        usedAnalysis(['feedback_level' => 'unchanged'], 'POLICY-009');
        usedAnalysis(['feedback_level' => 'unchanged'], 'POLICY-009');
        usedAnalysis(['feedback_level' => 'unchanged'], 'POLICY-009');
        usedAnalysis(['feedback_level' => 'unchanged'], 'POLICY-009');

        $usage = app(DocumentEvidence::class)->usage('POLICY-001');

        expect($usage['rated'])->toBe(4)->and($usage['usable_share'])->toBe(50)->and($usage['overall_share'])->toBe(75);
    });

    test('without ratings the share is empty', function () {
        usedAnalysis();

        expect(app(DocumentEvidence::class)->usage('POLICY-001')['usable_share'])->toBeNull();
    });

    test('the reports are counted by state and reason', function () {
        KnowledgeReviewFlag::query()->create(['document_id' => 'POLICY-001', 'staff_name' => 'Nele', 'reason' => 'not-helping', 'status' => 'open']);
        KnowledgeReviewFlag::query()->create(['document_id' => 'POLICY-001', 'staff_name' => 'Cara', 'reason' => 'not-helping', 'status' => 'open']);
        KnowledgeReviewFlag::query()->create(['document_id' => 'POLICY-001', 'staff_name' => 'Kerstin', 'reason' => 'other', 'status' => 'checked']);
        KnowledgeReviewFlag::query()->create(['document_id' => 'POLICY-002', 'staff_name' => 'Kerstin', 'reason' => 'other', 'status' => 'open']);

        expect(app(DocumentEvidence::class)->usage('POLICY-001')['reports'])->toBe(['open' => 2, 'checked' => 1, 'reasons' => ['not-helping' => 2]]);
    });
});

describe('the pages', function () {
    test('an admin sees the figures with the goal on the page of a trial document, others do not', function () {
        usedAnalysis(['reply_sent_at' => now(), 'reply_copied_at' => now(), 'reply_copied_changed' => false, 'feedback_level' => 'unchanged']);
        KnowledgeReviewFlag::query()->create(['document_id' => 'POLICY-001', 'staff_name' => 'Nele', 'reason' => 'customer-reaction', 'status' => 'open']);

        test()->withCookie('staff_name', 'Etienne')->get(route('knowledge.show', ['path' => 'policies/policy-001-a.md']))->assertOk()
            ->assertSeeText('Ziel der Testphase: Hilft es?')->assertSeeText('Was aus den Antwortentwürfen wurde')->assertSeeText('1 übernommen')->assertSeeText('1 gesendet, unverändert')
            ->assertSeeText('Kunden reagieren schlecht darauf')->assertSeeText('Bisher nur in 1 Vorschlag genannt');
        test()->withCookie('staff_name', 'Nele')->get(route('knowledge.show', ['path' => 'policies/policy-001-a.md']))->assertOk()->assertDontSeeText('Was aus den Antwortentwürfen wurde');
    });

    test('the tab with the reports offers the figures per document', function () {
        usedAnalysis(['feedback_level' => 'slight']);
        KnowledgeReviewFlag::query()->create(['document_id' => 'POLICY-001', 'staff_name' => 'Nele', 'reason' => 'other', 'status' => 'open']);

        test()->withCookie('staff_name', 'Etienne')->get(route('knowledge-gaps.index', ['status' => 'review']))->assertOk()->assertSeeText('Belege zum Dokument')->assertSeeText('Von der KI genannt')->assertSeeText('100 %');
    });

    test('a document without a trial shows no trial figures', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['status' => 'active'])]);
        usedAnalysis();

        test()->withCookie('staff_name', 'Etienne')->get(route('knowledge.show', ['path' => 'policies/policy-001-a.md']))->assertOk()->assertDontSeeText('Was aus den Antwortentwürfen wurde');
    });
});
