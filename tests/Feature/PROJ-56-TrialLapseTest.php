<?php

use App\Analysis\AppStats;
use App\Knowledge\KnowledgeLibrary;
use App\Knowledge\TrialChanger;
use App\Models\KnowledgeReviewFlag;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->withoutVite();
    config(['staff.admins' => ['Etienne']]);
});

afterEach(function () {
    cleanUpKnowledgeBases();
});

function trialLibrary(string $until = '2027-01-31', array $extra = [], string $path = 'policies/policy-001-a.md'): KnowledgeLibrary
{
    return knowledgeBase([$path => knowledgeDoc(['status' => 'active', 'retrieval' => 'core', 'summary' => 'Eine Regel.', 'trial_until' => $until, 'trial_goal' => 'Hilft es?', ...$extra])]);
}

function trialFile(string $path = 'policies/policy-001-a.md'): string
{
    return File::get(rtrim((string) config('knowledge.path'), '/').'/'.$path);
}

function onDay(string $date): void
{
    test()->travelTo(CarbonImmutable::parse($date.' 10:00', 'Europe/Berlin'));
}

describe('overdue and lapsed', function () {
    test('the days count from the day after the last day', function () {
        $document = trialLibrary()->find('POLICY-001');

        expect($document->trialOverdueDays(CarbonImmutable::parse('2027-01-31 23:00', 'Europe/Berlin')))->toBeNull()
            ->and($document->trialOverdueDays(CarbonImmutable::parse('2027-02-01 08:00', 'Europe/Berlin')))->toBe(1)
            ->and($document->trialOverdueDays(CarbonImmutable::parse('2027-02-10 08:00', 'Europe/Berlin')))->toBe(10);
    });

    test('the document stays in use for 30 days and is lapsed from the 31st', function () {
        $document = trialLibrary()->find('POLICY-001');

        expect($document->trialLapsed(CarbonImmutable::parse('2027-03-02 08:00', 'Europe/Berlin')))->toBeFalse()
            ->and($document->trialOverdueDays(CarbonImmutable::parse('2027-03-02 08:00', 'Europe/Berlin')))->toBe(30)
            ->and($document->trialLapsed(CarbonImmutable::parse('2027-03-03 08:00', 'Europe/Berlin')))->toBeTrue();
    });

    test('a lapsed document is no longer usable, an overdue one still is', function () {
        $library = trialLibrary();

        onDay('2027-02-15');
        expect($library->usable()->pluck('id')->all())->toBe(['POLICY-001']);

        onDay('2027-03-05');
        expect($library->usable()->pluck('id')->all())->toBe([]);
    });

    test('the retrieval drops it with the lapse', function () {
        trialLibrary();

        onDay('2027-02-15');
        expect($this->artisan('knowledge:select', ['group' => 'reseller', '--text' => 'Eine Frage'])->run())->toBe(0);
        $this->artisan('knowledge:select', ['group' => 'reseller', '--text' => 'Eine Frage'])->expectsOutputToContain('POLICY-001');

        onDay('2027-03-05');
        $this->artisan('knowledge:select', ['group' => 'reseller', '--text' => 'Eine Frage'])->doesntExpectOutputToContain('POLICY-001');
    });

    test('the check says what happens', function () {
        $library = trialLibrary();

        onDay('2027-02-10');
        expect(messagesOf($library, 'warning'))->toContain('abgelaufen')->toContain('Nach 30 Tagen ohne Entscheidung wird das Dokument nicht mehr verwendet');

        onDay('2027-03-10');
        expect(messagesOf(trialLibrary(), 'warning'))->toContain('Nachfrist von 30 Tagen vorbei')->toContain('nicht mehr verwendet');
    });

    test('the pages and the model say how long it has been over', function () {
        trialLibrary();

        onDay('2027-02-10');
        test()->get(route('knowledge.show', ['path' => 'policies/policy-001-a.md']))->assertOk()->assertSeeText('Überprüfung überfällig seit 10 Tagen')->assertSeeText('Es gilt noch 21 Tage weiter');

        onDay('2027-03-05');
        test()->get(route('knowledge.show', ['path' => 'policies/policy-001-a.md']))->assertOk()->assertSeeText('Testphase abgelaufen und nicht entschieden')->assertSeeText('wird seit 3 Tagen nicht mehr verwendet');
        test()->get('/knowledge')->assertOk()->assertSeeText('wird nicht mehr verwendet');
    });
});

describe('the sidebar', function () {
    test('counts documents with a trial that is over or with open reports, each once', function () {
        knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['status' => 'active', 'trial_until' => '2027-01-31', 'trial_goal' => 'x']),
            'policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002', 'title' => 'B', 'status' => 'active']),
            'policies/policy-003-c.md' => knowledgeDoc(['id' => 'POLICY-003', 'title' => 'C', 'status' => 'active']),
        ]);
        KnowledgeReviewFlag::query()->create(['document_id' => 'POLICY-002', 'staff_name' => 'Nele', 'reason' => 'other', 'status' => 'open']);
        KnowledgeReviewFlag::query()->create(['document_id' => 'POLICY-002', 'staff_name' => 'Cara', 'reason' => 'other', 'status' => 'open']);
        KnowledgeReviewFlag::query()->create(['document_id' => 'POLICY-003', 'staff_name' => 'Nele', 'reason' => 'other', 'status' => 'checked']);

        expect(app(AppStats::class)->review())->toBe(1);

        onDay('2027-02-05');
        expect(app(AppStats::class)->review())->toBe(2);
        test()->get('/knowledge')->assertOk()->assertSeeText('Wissen zur Überprüfung');
    });

    test('nothing is shown when nothing is to look at', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['status' => 'active'])]);

        test()->get('/knowledge')->assertOk()->assertDontSeeText('Wissen zur Überprüfung');
    });
});

describe('the decision of an admin', function () {
    function decide(string $decision, array $fields = [], string $staff = 'Etienne', string $path = 'policies/policy-001-a.md')
    {
        $fingerprint = app(KnowledgeLibrary::class)->all()->first(fn ($document) => $document->path === $path)?->fingerprint ?? str_repeat('0', 64);

        return test()->withCookie('staff_name', $staff)->post(route('knowledge.trial', ['path' => $path]), ['decision' => $decision, 'fingerprint' => $fingerprint, ...$fields]);
    }

    test('only admins see the panel and decide', function () {
        trialLibrary();

        test()->withCookie('staff_name', 'Nele')->get(route('knowledge.show', ['path' => 'policies/policy-001-a.md']))->assertOk()->assertDontSeeText('Entscheidung zur Testphase');
        test()->withCookie('staff_name', 'Etienne')->get(route('knowledge.show', ['path' => 'policies/policy-001-a.md']))->assertOk()->assertSeeText('Entscheidung zur Testphase')->assertSeeText('Bewährt, dauerhaft gültig')->assertSeeText('Testphase verlängern')->assertSeeText('Beenden (veraltet)');

        decide('permanent', staff: 'Nele')->assertForbidden();
        expect(trialFile())->toContain('trial_until');
    });

    test('a document without a trial has no panel and no decision', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['status' => 'active'])]);

        test()->withCookie('staff_name', 'Etienne')->get(route('knowledge.show', ['path' => 'policies/policy-001-a.md']))->assertOk()->assertDontSeeText('Entscheidung zur Testphase');
        decide('permanent')->assertRedirect()->assertSessionHas('status_error');
    });

    test('"proven, valid for good" removes both lines and nothing else', function () {
        trialLibrary();
        $before = trialFile();

        decide('permanent')->assertRedirect()->assertSessionHas('trial_changed', 'permanent');

        $after = trialFile();
        expect($after)->not->toContain('trial_until')->not->toContain('trial_goal')->toContain('status: active')
            ->and(str_replace(array_values(array_filter(explode("\n", $before), fn (string $line): bool => str_starts_with($line, 'trial_'))), '', $before))->not->toBe($before)
            ->and(app(KnowledgeLibrary::class)->find('POLICY-001')->isTrial())->toBeFalse();
    });

    test('extending sets the new last day and keeps the goal, and a lapsed document is used again', function () {
        trialLibrary();
        onDay('2027-03-10');
        expect(app(KnowledgeLibrary::class)->usable()->pluck('id')->all())->toBe([]);

        decide('extend', ['until' => '2027-06-30'])->assertRedirect()->assertSessionHas('trial_changed', 'extend');

        expect(trialFile())->toContain('trial_until: 2027-06-30')->toContain('Hilft es?')
            ->and(app(KnowledgeLibrary::class)->usable()->pluck('id')->all())->toBe(['POLICY-001']);
    });

    test('a past date, a date too far away and a wrong format are refused', function () {
        trialLibrary();
        onDay('2027-03-10');

        decide('extend', ['until' => '2027-03-09'])->assertSessionHas('status_error');
        decide('extend', ['until' => '2029-01-01'])->assertSessionHas('status_error');
        decide('extend', ['until' => '30.06.2027'])->assertSessionHasErrors('until');
        decide('extend')->assertSessionHasErrors('until');

        expect(trialFile())->toContain("trial_until: '2027-01-31'");
    });

    test('ending sets the status to deprecated and the document is no longer used', function () {
        trialLibrary();

        decide('end')->assertRedirect()->assertSessionHas('trial_changed', 'end');

        expect(trialFile())->toContain('status: deprecated')->and(app(KnowledgeLibrary::class)->usable()->pluck('id')->all())->toBe([]);
    });

    test('a file changed since the page was opened is not touched', function () {
        trialLibrary();

        test()->withCookie('staff_name', 'Etienne')->post(route('knowledge.trial', ['path' => 'policies/policy-001-a.md']), ['decision' => 'permanent', 'fingerprint' => str_repeat('a', 64)])->assertSessionHas('status_error');

        expect(trialFile())->toContain('trial_until');
    });

    test('an unknown decision is refused', function () {
        trialLibrary();

        decide('einfach-so')->assertSessionHasErrors('decision');
    });

    test('the editing keeps unrelated lines byte for byte, also with a plain date and a multi-line goal', function () {
        trialLibrary();
        $changer = app(TrialChanger::class);
        $content = "---\r\nid: POLICY-001\r\ntitle: Titel\r\nstatus: active\r\ntrial_until: 2027-01-31\r\ntrial_goal: >\r\n  Hilft es\r\n  wirklich?\r\nsummary: Eine Regel.\r\n---\r\n\r\nText\r\n";

        expect($changer->edit($content, remove: ['trial_until', 'trial_goal']))->toBe("---\r\nid: POLICY-001\r\ntitle: Titel\r\nstatus: active\r\nsummary: Eine Regel.\r\n---\r\n\r\nText\r\n")
            ->and($changer->edit($content, set: ['trial_until' => '2027-05-01']))->toContain("trial_until: 2027-05-01\r\ntrial_goal: >")
            ->and($changer->edit($content, status: 'deprecated'))->toContain("status: deprecated\r\ntrial_until: 2027-01-31")
            ->and($changer->edit('kein Kopf', remove: ['x']))->toBeNull();
    });
});
