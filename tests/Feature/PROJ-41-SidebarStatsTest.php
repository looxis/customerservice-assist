<?php

use App\Analysis\AppStats;
use App\Models\Analysis;
use App\Models\KnowledgeGap;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->withoutVite();
    config(['cache.default' => 'array', 'staff.admins' => ['Etienne']]);
    Cache::flush();
});

afterEach(fn () => cleanUpKnowledgeBases());

describe('figures', function () {
    test('processed tickets count each ticket once, without failed analyses and test runs', function () {
        Analysis::factory()->create(['ticket_number' => '2100001']);
        Analysis::factory()->create(['ticket_number' => '2100001']);
        Analysis::factory()->create(['ticket_number' => '2100002']);
        Analysis::factory()->create(['ticket_number' => '2100003', 'status' => 'failed']);
        Analysis::factory()->create(['ticket_number' => '2100004', 'test_until' => now()]);

        expect(app(AppStats::class)->processedTickets())->toBe(2);
    });

    test('gaps are counted with the open ones, test runs left out', function () {
        KnowledgeGap::factory()->count(2)->create();
        KnowledgeGap::factory()->create(['status' => 'done', 'resolved_by' => 'Etienne', 'resolved_at' => now()]);
        KnowledgeGap::factory()->create(['test_run' => true]);

        expect(app(AppStats::class)->gaps())->toBe(['gaps' => 3, 'openGaps' => 2]);
    });

    test('the knowledge figures are the usable files with the sum of their sizes', function () {
        $library = knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['title' => 'A']),
            'policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002', 'title' => 'B', 'status' => 'active']),
            'policies/policy-003-c.md' => knowledgeDoc(['id' => 'POLICY-003', 'title' => 'C', 'status' => 'deprecated']),
        ]);
        $bytes = $library->usable()->sum(fn ($document) => filesize(config('knowledge.path').'/'.$document->path));

        $stats = (new AppStats($library))->knowledge();

        expect($stats['documents'])->toBe(2)->and($stats['drafts'])->toBe(1)->and($stats['bytes'])->toBe((int) $bytes)->and($bytes)->toBeGreaterThan(0);
    });

    test('sizes are written in German notation', function () {
        expect(AppStats::size(512))->toBe('512 Byte')->and(AppStats::size(2048))->toBe('2 KB')->and(AppStats::size(1_572_864))->toBe('1,5 MB');
    });
});

describe('sidebar', function () {
    test('everybody sees the figures, also without admin rights, with the version', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc()]);
        Analysis::factory()->create();
        KnowledgeGap::factory()->count(2)->create();
        KnowledgeGap::factory()->create(['status' => 'done', 'resolved_by' => 'Etienne', 'resolved_at' => now()]);

        $this->withCookie('staff_name', 'Nele')->get('/')
            ->assertSeeTextInOrder(['Aktueller Stand', 'Wissensstand', 'Wissen', '1 Dateien', 'Bearbeitete Tickets', '1', 'Gemeldete Wissenslücken', '3', 'Davon noch offen', '2', 'Gemeldete Lücken werden bearbeitet', 'Version '.config('app.version')])
            ->assertDontSeeText('Version dev');
    });

    test('the hint about the work on gaps only shows when gaps were reported', function () {
        $this->get('/')->assertSeeText('Gemeldete Wissenslücken')->assertDontSeeText('Gemeldete Lücken werden bearbeitet');
    });
});
