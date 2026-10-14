<?php

use App\Knowledge\DocumentEvidence;
use App\Knowledge\DocumentStatusChanger;
use App\Knowledge\KnowledgeLibrary;
use App\Models\Analysis;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->withoutVite();
    config(['staff.admins' => ['Etienne']]);

    $this->library = knowledgeBase([
        'policies/policy-001-a.md' => knowledgeDoc(['id' => 'POLICY-001', 'title' => 'Regel A', 'status' => 'draft']),
        'policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002', 'title' => 'Regel B', 'status' => 'active']),
        'policies/policy-003-c.md' => knowledgeDoc(['id' => 'POLICY-003', 'title' => 'Regel C', 'status' => 'draft', 'related_knowledge' => ['POLICY-001']], "Gilt für alle.\n\n# Regel\n\nEin Satz.\n\n# Noch zu klären\n\n- Offen"),
        'policies/policy-004-d.md' => knowledgeDoc(['id' => 'POLICY-004', 'title' => 'Regel D', 'status' => 'deprecated']),
    ]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function statusFile(string $name): string
{
    return rtrim((string) config('knowledge.path'), '/').'/policies/'.$name;
}

function fingerprintOf(string $path): string
{
    return app(KnowledgeLibrary::class)->all()->first(fn ($document) => $document->path === $path)->fingerprint;
}

function setStatus(string $path, string $status, ?string $fingerprint = null, string $staff = 'Etienne')
{
    return test()->withCookie('staff_name', $staff)->post(route('knowledge.status', ['path' => $path]), [
        'status' => $status, 'fingerprint' => $fingerprint ?? fingerprintOf($path),
    ]);
}

function analysisUsing(string $id, array $attributes = []): Analysis
{
    return Analysis::query()->create([
        'uuid' => (string) Str::uuid(), 'staff_name' => 'Nele', 'ticket_number' => (string) random_int(1000000, 9999999), 'scope_key' => 'x', 'status' => 'completed',
        'knowledge_ids' => [$id], 'knowledge_fingerprints' => [$id => str_repeat('a', 64)], ...$attributes,
    ]);
}

describe('the status panel', function () {
    test('admins see the actions of the current status, others see no panel', function () {
        $this->withCookie('staff_name', 'Etienne')->get(route('knowledge.show', ['path' => 'policies/policy-001-a.md']))
            ->assertOk()->assertSeeText('Als gültig bestätigen')->assertDontSeeText('Zurück auf Entwurf');
        $this->withCookie('staff_name', 'Etienne')->get(route('knowledge.show', ['path' => 'policies/policy-002-b.md']))
            ->assertSeeText('Zurück auf Entwurf')->assertSeeText('Als veraltet markieren')->assertDontSeeText('Als gültig bestätigen');
        $this->withCookie('staff_name', 'Etienne')->get(route('knowledge.show', ['path' => 'policies/policy-004-d.md']))
            ->assertSeeText('Zurück auf Entwurf')->assertDontSeeText('Als veraltet markieren');

        $this->withCookie('staff_name', 'Nele')->get(route('knowledge.show', ['path' => 'policies/policy-001-a.md']))
            ->assertOk()->assertDontSeeText('Als gültig bestätigen');
    });

    test('the evidence counts retrieved and named documents and ratings without test runs and failures', function () {
        analysisUsing('POLICY-001', ['feedback_level' => 'unchanged']);
        analysisUsing('POLICY-001', ['feedback_level' => 'discarded']);
        analysisUsing('POLICY-001');
        analysisUsing('POLICY-001', ['test_until' => now()]);
        analysisUsing('POLICY-001', ['status' => 'failed']);
        analysisUsing('POLICY-009', ['knowledge_fingerprints' => ['POLICY-001' => str_repeat('b', 64)]]);

        $evidence = app(DocumentEvidence::class)->for('POLICY-001');

        expect($evidence['named'])->toBe(3)->and($evidence['retrieved'])->toBe(4)->and($evidence['ratings']['unchanged'])->toBe(1)
            ->and($evidence['ratings']['discarded'])->toBe(1)->and($evidence['unrated'])->toBe(1)->and($evidence['thin'])->toBeFalse();
    });

    test('a document with an open section and a related draft is blocked for confirming, with the hint', function () {
        $this->withCookie('staff_name', 'Etienne')->get(route('knowledge.show', ['path' => 'policies/policy-003-c.md']))
            ->assertSeeText('Abschnitt „Noch zu klären“')->assertSeeText('Verwandtes Wissen ist noch Entwurf');
    });
});

describe('changing the status', function () {
    test('confirming changes only the status line of the file', function () {
        $before = file_get_contents(statusFile('policy-001-a.md'));

        setStatus('policies/policy-001-a.md', 'active')->assertRedirect()->assertSessionHas('status_changed', 'active');

        $after = file_get_contents(statusFile('policy-001-a.md'));
        expect($after)->toBe(preg_replace('/^status: draft$/m', 'status: active', $before))->and($after)->not->toBe($before);
    });

    test('the line endings and the rest of the file stay byte for byte', function () {
        $file = statusFile('policy-001-a.md');
        file_put_contents($file, str_replace("\n", "\r\n", file_get_contents($file)));
        $before = file_get_contents($file);

        setStatus('policies/policy-001-a.md', 'active');

        expect(file_get_contents($file))->toBe(preg_replace('/^status: draft(\r?)$/m', 'status: active$1', $before));
    });

    test('active can go back to draft or to deprecated and deprecated back to draft', function () {
        setStatus('policies/policy-002-b.md', 'deprecated')->assertSessionHas('status_changed');
        expect(file_get_contents(statusFile('policy-002-b.md')))->toContain('status: deprecated');

        setStatus('policies/policy-002-b.md', 'draft')->assertSessionHas('status_changed');
        expect(file_get_contents(statusFile('policy-002-b.md')))->toContain('status: draft');
    });

    test('others are refused with 403 and nothing changes', function () {
        $before = file_get_contents(statusFile('policy-001-a.md'));

        setStatus('policies/policy-001-a.md', 'active', staff: 'Nele')->assertForbidden();

        expect(file_get_contents(statusFile('policy-001-a.md')))->toBe($before);
    });

    test('a file that changed since the page was opened is not touched', function () {
        $fingerprint = fingerprintOf('policies/policy-001-a.md');
        file_put_contents(statusFile('policy-001-a.md'), "\nZusatz.\n", FILE_APPEND);
        $before = file_get_contents(statusFile('policy-001-a.md'));

        setStatus('policies/policy-001-a.md', 'active', $fingerprint)->assertSessionHas('status_error');

        expect(file_get_contents(statusFile('policy-001-a.md')))->toBe($before);
    });

    test('a document with an open section cannot be confirmed, not even by a direct request', function () {
        $before = file_get_contents(statusFile('policy-003-c.md'));

        setStatus('policies/policy-003-c.md', 'active')->assertSessionHas('status_error');

        expect(file_get_contents(statusFile('policy-003-c.md')))->toBe($before);
    });

    test('a transition that does not exist and an unknown status or path are refused', function () {
        setStatus('policies/policy-001-a.md', 'deprecated')->assertSessionHas('status_error');
        setStatus('policies/policy-001-a.md', 'sonstwas')->assertSessionHasErrors('status');
        setStatus('policies/gibt-es-nicht.md', 'active', str_repeat('a', 64))->assertSessionHas('status_error');
        setStatus('../.env', 'active', str_repeat('a', 64))->assertSessionHas('status_error');
    });

    test('a file that cannot be written is left alone and reported', function () {
        $file = statusFile('policy-001-a.md');
        chmod($file, 0444);
        $before = file_get_contents($file);

        if (is_writable($file)) {
            chmod($file, 0644);
            $this->markTestSkipped('The file stays writable for this user (for example root).');
        }

        setStatus('policies/policy-001-a.md', 'active')->assertSessionHas('status_error');
        expect(file_get_contents($file))->toBe($before);
        chmod($file, 0644);
    });

    test('the line replacement needs exactly one status line in a frontmatter', function () {
        $changer = app(DocumentStatusChanger::class);

        expect($changer->replaceStatus("---\nid: X\nstatus: draft\n---\nText status: draft\n", 'active'))->toBe("---\nid: X\nstatus: active\n---\nText status: draft\n")
            ->and($changer->replaceStatus("---\nid: X\n---\nBody\n", 'active'))->toBeNull()
            ->and($changer->replaceStatus("kein Frontmatter\nstatus: draft\n", 'active'))->toBeNull()
            ->and($changer->replaceStatus("---\nstatus: draft\nstatus: active\n---\n", 'active'))->toBeNull();
    });
});

describe('QA probes (PROJ-23)', function () {
    test('a confirmed document is no longer a draft in the knowledge that is used', function () {
        setStatus('policies/policy-001-a.md', 'active')->assertSessionHas('status_changed');

        $library = app(KnowledgeLibrary::class);
        $library->refresh();
        $document = $library->usable()->first(fn ($candidate) => $candidate->id === 'POLICY-001');

        expect($document->status)->toBe('active');
    });

    test('a retired document is no longer used', function () {
        setStatus('policies/policy-002-b.md', 'deprecated')->assertSessionHas('status_changed');

        $library = app(KnowledgeLibrary::class);
        $library->refresh();

        expect($library->usable()->pluck('id')->all())->not->toContain('POLICY-002');
    });

    test('a document with a prohibited status value offers no action and is refused', function () {
        $file = statusFile('policy-001-a.md');
        file_put_contents($file, str_replace('status: draft', 'status: irgendwas', file_get_contents($file)));

        $this->withCookie('staff_name', 'Etienne')->get(route('knowledge.show', ['path' => 'policies/policy-001-a.md']))
            ->assertOk()->assertSeeText('keine Statusänderung in der App möglich');
        setStatus('policies/policy-001-a.md', 'active')->assertSessionHas('status_error');
    });

    test('a document with a check error cannot be confirmed', function () {
        $file = statusFile('policy-001-a.md');
        file_put_contents($file, preg_replace('/^title:.*\n/m', '', file_get_contents($file)));
        $before = file_get_contents($file);

        setStatus('policies/policy-001-a.md', 'active')->assertSessionHas('status_error');

        expect(file_get_contents($file))->toBe($before);
    });

    test('the form carries the token and the fingerprint of the page', function () {
        $html = $this->withCookie('staff_name', 'Etienne')->get(route('knowledge.show', ['path' => 'policies/policy-001-a.md']))->getContent();

        expect($html)->toContain('name="_token"')->and($html)->toContain('name="fingerprint" value="'.fingerprintOf('policies/policy-001-a.md').'"');
    });

    test('a missing or malformed fingerprint is refused by the request', function () {
        $this->withCookie('staff_name', 'Etienne')->post(route('knowledge.status', ['path' => 'policies/policy-001-a.md']), ['status' => 'active'])->assertSessionHasErrors('fingerprint');
        $this->withCookie('staff_name', 'Etienne')->post(route('knowledge.status', ['path' => 'policies/policy-001-a.md']), ['status' => 'active', 'fingerprint' => 'abc'])->assertSessionHasErrors('fingerprint');
    });

    test('a folder that cannot be written blocks the actions with a reason', function () {
        $directory = dirname(statusFile('policy-001-a.md'));
        chmod($directory, 0555);

        if (is_writable($directory)) {
            chmod($directory, 0755);
            $this->markTestSkipped('The folder stays writable for this user (for example root).');
        }

        $this->withCookie('staff_name', 'Etienne')->get(route('knowledge.show', ['path' => 'policies/policy-001-a.md']))->assertSeeText('nicht geschrieben werden');
        chmod($directory, 0755);
    });
});
