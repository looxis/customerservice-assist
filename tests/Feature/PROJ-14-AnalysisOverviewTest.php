<?php

use App\Analysis\AnalysisOverview;
use App\Analysis\AnalysisStore;
use App\Models\Analysis;
use App\Models\KnowledgeGap;

beforeEach(function () {
    config(['staff.admins' => ['Etienne']]);
    $this->travelTo(now()->setDate(2026, 10, 20)->setTime(12, 0));
    $this->overview = app(AnalysisOverview::class);
});

function overviewAnalyses(array $fields = [], int $count = 1): void
{
    Analysis::factory()->count($count)->create(['created_at' => now()->subDay(), ...$fields]);
}

function rows(array $input = []): array
{
    $overview = app(AnalysisOverview::class);

    return $overview->page($overview->filter($input))->getCollection()->pluck('ticket_number')->all();
}

describe('access', function () {
    test('only admins may open the overview', function () {
        $this->withCookie('staff_name', 'Nele')->get(route('analysis-overview.index'))->assertForbidden();
        $this->get(route('analysis-overview.index'))->assertForbidden();
    });
});

describe('the page', function () {
    test('the sidebar entry is for admins only', function () {
        $this->withCookie('staff_name', 'Nele')->get(route('tickets.analyze'))->assertDontSee('Analysen</a>', false);
        $this->withCookie('staff_name', 'Etienne')->get(route('tickets.analyze'))->assertSee(route('analysis-overview.index'), false);
    });

    test('an empty list says so and offers to loosen the filters', function () {
        $this->withCookie('staff_name', 'Etienne')->get(route('analysis-overview.index'))->assertOk()->assertSeeText('Keine Analysen für diese Auswahl');
    });

    test('rows show ticket link, group, product, category, model, feedback, copy state and gaps, without customer content', function () {
        $analysis = Analysis::factory()->create([
            'created_at' => now()->subDay(), 'ticket_number' => '2100001', 'customer_group' => 'unclear', 'products' => ['xyz-produkt'], 'category' => 'complaint',
            'model' => 'gpt-5.5', 'prompt_version' => 'analysis-9', 'feedback_level' => 'slight', 'reply_copied_at' => now(), 'reply_copied_changed' => false,
            'content' => ['ticket' => '2100001', 'result' => ['reply' => ['text' => 'Geheimer Entwurf']]],
        ]);
        KnowledgeGap::factory()->count(2)->create(['analysis_id' => $analysis->id]);

        $page = $this->withCookie('staff_name', 'Etienne')->get(route('analysis-overview.index'))->assertOk();

        $page->assertSeeText('Ticket#2100001')->assertSee('/tickets/2100001?analyse='.$analysis->uuid, false)->assertSeeText('Reklamation')->assertSeeText('Noch unklar')
            ->assertSeeText('xyz-produkt')->assertSeeText('gpt-5.5')->assertSeeText('analysis-9')->assertSeeText('Leicht angepasst')->assertSeeText('Kopiert (unverändert)')
            ->assertDontSee('Geheimer Entwurf');
    });

    test('old, failed and test-run rows are marked', function () {
        Analysis::factory()->create(['ticket_number' => '2100001', 'created_at' => now()->setDate(2026, 10, 1)->subDay(), 'feedback_level' => null]);
        Analysis::factory()->create(['ticket_number' => '2100002', 'created_at' => now()->subDay(), 'status' => 'failed', 'error' => 'unavailable', 'content' => null]);
        Analysis::factory()->create(['ticket_number' => '2100003', 'created_at' => now()->subDay(), 'scope_key' => '2100003.stand-11']);

        $page = $this->withCookie('staff_name', 'Etienne')->get(route('analysis-overview.index', ['tage' => '90', 'testlaeufe' => '1', 'fehlgeschlagen' => '1']))->assertOk();

        $page->assertSeeText('Fehlgeschlagen')->assertSeeText('Die Analyse ist gerade nicht möglich')->assertSeeText('Testlauf')->assertSee('title="Nicht erfasst"', false)
            ->assertSee('/tickets/2100003?analyse=', false)->assertSee('stand=11', false);
    });

    test('the filter choice stays in the form and the figures are shown', function () {
        Analysis::factory()->count(3)->create(['created_at' => now()->subDay(), 'category' => 'complaint', 'feedback_level' => 'unchanged']);
        Analysis::factory()->create(['created_at' => now()->subDay(), 'category' => 'complaint', 'feedback_level' => 'discarded']);

        $page = $this->withCookie('staff_name', 'Etienne')->get(route('analysis-overview.index', ['kategorie' => 'complaint']))->assertOk();

        $page->assertSeeText('75 %')->assertSeeText('25 %')->assertSee('<option value="complaint" selected', false)->assertSeeText('Zurücksetzen');
        $this->withCookie('staff_name', 'Etienne')->get(route('analysis-overview.index'))->assertDontSeeText('Zurücksetzen');
    });

    test('paging keeps the filters', function () {
        Analysis::factory()->count(55)->create(['created_at' => now()->subDay(), 'category' => 'complaint']);

        $this->withCookie('staff_name', 'Etienne')->get(route('analysis-overview.index', ['kategorie' => 'complaint']))->assertOk()->assertSee('kategorie=complaint&amp;page=2', false);
    });

    test('a hand-changed address does not break the page', function () {
        $this->withCookie('staff_name', 'Etienne')->get(route('analysis-overview.index', ['tage' => 'x', 'von' => '2026-13-45', 'kopiert' => '<script>', 'produkt' => '<img src=x onerror=1>']))->assertOk()
            ->assertDontSee('<img src=x', false);
    });

    test('an invalid range shows a hint', function () {
        $this->withCookie('staff_name', 'Etienne')->get(route('analysis-overview.index', ['von' => '2026-10-10', 'bis' => '2026-10-01']))->assertOk()->assertSeeText('liegt nach „Bis“');
    });

    test('names from the data are escaped', function () {
        Analysis::factory()->create(['created_at' => now()->subDay(), 'staff_name' => '<script>alert(1)</script>']);

        $this->withCookie('staff_name', 'Etienne')->get(route('analysis-overview.index'))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    });
});

describe('standard view', function () {
    test('it shows finished real analyses of the last 28 days, newest first', function () {
        overviewAnalyses(['ticket_number' => '2100001', 'created_at' => now()->subDays(3)]);
        overviewAnalyses(['ticket_number' => '2100002', 'created_at' => now()->subDay()]);
        overviewAnalyses(['ticket_number' => '2100003', 'created_at' => now()->subDays(40)]);
        overviewAnalyses(['ticket_number' => '2100004', 'test_until' => now()]);
        overviewAnalyses(['ticket_number' => '2100005', 'scope_key' => '2100005.stand-11']);
        overviewAnalyses(['ticket_number' => '2100006', 'status' => 'failed', 'error' => 'unavailable']);

        expect(rows())->toBe(['2100002', '2100001']);
    });

    test('tests and failed analyses can be switched on', function () {
        overviewAnalyses(['ticket_number' => '2100001']);
        overviewAnalyses(['ticket_number' => '2100002', 'scope_key' => '2100002.stand-11', 'status' => 'failed', 'error' => 'unavailable']);
        overviewAnalyses(['ticket_number' => '2100003', 'test_until' => now()]);

        expect(rows(['testlaeufe' => '1']))->toContain('2100001', '2100003')->not->toContain('2100002')
            ->and(rows(['fehlgeschlagen' => '1']))->toContain('2100001')->not->toContain('2100003')
            ->and(rows(['testlaeufe' => '1', 'fehlgeschlagen' => '1']))->toHaveCount(3);
    });

    test('it is paged by fifty and keeps the filters', function () {
        overviewAnalyses(['category' => 'complaint'], 55);
        overviewAnalyses(['category' => 'quote-request'], 3);

        $filter = $this->overview->filter(['kategorie' => 'complaint']);
        $page = $this->overview->page($filter);

        expect($page->total())->toBe(55)->and($page->perPage())->toBe(50)->and($page->count())->toBe(50);
    });
});

describe('filters', function () {
    test('period: presets and a custom range', function () {
        overviewAnalyses(['ticket_number' => '2100001', 'created_at' => now()->subDays(5)]);
        overviewAnalyses(['ticket_number' => '2100002', 'created_at' => now()->subDays(60)]);
        overviewAnalyses(['ticket_number' => '2100003', 'created_at' => now()->subDays(200)]);

        expect(rows(['tage' => '7']))->toBe(['2100001'])
            ->and(rows(['tage' => '90']))->toBe(['2100001', '2100002'])
            ->and(rows(['tage' => '365']))->toHaveCount(3)
            ->and(rows(['tage' => '5']))->toBe(['2100001'])
            ->and(rows(['von' => '2026-08-20', 'bis' => '2026-08-22']))->toBe(['2100002']);
    });

    test('a range with from after to or from in the future falls back to the standard period with a hint', function () {
        overviewAnalyses(['ticket_number' => '2100001']);

        $backwards = $this->overview->filter(['von' => '2026-10-10', 'bis' => '2026-10-01']);
        $future = $this->overview->filter(['von' => '2026-12-01']);

        expect($backwards->problem)->toContain('„Von“ liegt nach „Bis“')->and($backwards->days)->toBe(28)
            ->and($future->problem)->toContain('Zukunft')->and($future->days)->toBe(28);
    });

    test('each filter alone and combined', function () {
        overviewAnalyses(['ticket_number' => '2100001', 'customer_group' => 'reseller', 'products' => ['3d-glass-photo'], 'category' => 'complaint', 'feedback_level' => 'unchanged', 'staff_name' => 'Nele', 'model' => 'gpt-5.5', 'prompt_version' => 'analysis-1']);
        overviewAnalyses(['ticket_number' => '2100002', 'customer_group' => 'looxis-de', 'products' => ['3d-glass-photo', 'magic-mug'], 'category' => 'quote-request', 'feedback_level' => 'discarded', 'staff_name' => 'Cara', 'model' => 'gpt-5.4', 'prompt_version' => 'analysis-2']);
        overviewAnalyses(['ticket_number' => '2100003', 'customer_group' => 'reseller', 'products' => [], 'category' => 'complaint', 'feedback_level' => null, 'staff_name' => 'Nele', 'model' => 'gpt-5.5', 'prompt_version' => 'analysis-2']);

        expect(rows(['kundengruppe' => 'reseller']))->toEqualCanonicalizing(['2100001', '2100003'])
            ->and(rows(['produkt' => 'magic-mug']))->toBe(['2100002'])
            ->and(rows(['produkt' => '3d-glass-photo']))->toEqualCanonicalizing(['2100001', '2100002'])
            ->and(rows(['kategorie' => 'quote-request']))->toBe(['2100002'])
            ->and(rows(['feedback' => 'discarded']))->toBe(['2100002'])
            ->and(rows(['feedback' => 'none']))->toBe(['2100003'])
            ->and(rows(['mitarbeiter' => 'Cara']))->toBe(['2100002'])
            ->and(rows(['modell' => 'gpt-5.4']))->toBe(['2100002'])
            ->and(rows(['prompt' => 'analysis-2']))->toEqualCanonicalizing(['2100002', '2100003'])
            ->and(rows(['kundengruppe' => 'reseller', 'prompt' => 'analysis-2', 'mitarbeiter' => 'Nele']))->toBe(['2100003']);
    });

    test('copy state filter: unchanged, changed, edited and not copied; unknown before the tracking date is not "not copied"', function () {
        overviewAnalyses(['ticket_number' => '2100001', 'reply_copied_at' => now(), 'reply_copied_changed' => false]);
        overviewAnalyses(['ticket_number' => '2100002', 'reply_copied_at' => now(), 'reply_copied_changed' => true, 'reply_edited_at' => now()]);
        overviewAnalyses(['ticket_number' => '2100003', 'reply_edited_at' => now()]);
        overviewAnalyses(['ticket_number' => '2100004']);
        overviewAnalyses(['ticket_number' => '2100005', 'created_at' => now()->setDate(2026, 10, 1)]);

        expect(rows(['kopiert' => 'copied-unchanged']))->toBe(['2100001'])
            ->and(rows(['kopiert' => 'copied-changed']))->toBe(['2100002'])
            ->and(rows(['kopiert' => 'edited']))->toBe(['2100003'])
            ->and(rows(['kopiert' => 'not-copied']))->toBe(['2100004']);
    });

    test('values that do not fit are ignored', function () {
        overviewAnalyses(['ticket_number' => '2100001', 'customer_group' => 'reseller']);
        overviewAnalyses(['ticket_number' => '2100002', 'customer_group' => 'looxis-de']);

        $filter = $this->overview->filter(['kundengruppe' => 'gibt-es-nicht', 'feedback' => 'super', 'kopiert' => 'x', 'tage' => '13', 'von' => 'gestern', 'produkt' => ['a'], 'modell' => 'nie-benutzt']);

        expect($filter)->group->toBeNull()->feedback->toBeNull()->copy->toBeNull()->days->toBe(28)->from->toBeNull()->product->toBeNull()->model->toBeNull()
            ->and($filter->isCustom())->toBeFalse()
            ->and($this->overview->page($filter)->total())->toBe(2);
    });

    test('the selection lists offer only values from the stored analyses', function () {
        overviewAnalyses(['customer_group' => 'reseller', 'products' => ['b', 'a'], 'category' => 'complaint', 'staff_name' => 'Nele', 'model' => 'm1', 'prompt_version' => 'p1']);
        overviewAnalyses(['customer_group' => 'reseller', 'products' => ['a', 'c'], 'category' => 'complaint', 'staff_name' => 'Cara', 'model' => 'm1', 'prompt_version' => 'p2', 'created_at' => now()->subYear()]);

        expect($this->overview->options())->toMatchArray(['groups' => ['reseller'], 'products' => ['a', 'b', 'c'], 'categories' => ['complaint'], 'models' => ['m1'], 'prompts' => ['p1', 'p2']])
            ->and($this->overview->options()['staff'])->toEqualCanonicalizing(['Nele', 'Cara']);
    });
});

describe('figures', function () {
    test('they cover the whole filtered set, not just one page', function () {
        overviewAnalyses(['feedback_level' => 'unchanged'], 30);
        overviewAnalyses(['feedback_level' => 'slight'], 30);
        overviewAnalyses(['feedback_level' => 'major'], 20);
        overviewAnalyses(['feedback_level' => 'discarded'], 20);
        overviewAnalyses(['feedback_level' => null], 100);

        expect($this->overview->figures($this->overview->filter([])))->toMatchArray(['count' => 200, 'rated' => 100, 'usable' => 60, 'discarded' => 20, 'without_feedback' => 50]);
    });

    test('shares are null without a base', function () {
        overviewAnalyses(['feedback_level' => null]);

        expect($this->overview->figures($this->overview->filter([])))->toMatchArray(['count' => 1, 'rated' => 0, 'usable' => null, 'discarded' => null, 'copied' => 0, 'copied_unchanged' => null]);
    });

    test('copied shares and the number of gaps follow the filter', function () {
        overviewAnalyses(['category' => 'complaint', 'reply_copied_at' => now(), 'reply_copied_changed' => false], 3);
        overviewAnalyses(['category' => 'complaint', 'reply_copied_at' => now(), 'reply_copied_changed' => true]);
        $other = Analysis::factory()->create(['category' => 'quote-request', 'created_at' => now()->subDay()]);
        $mine = Analysis::query()->where('category', 'complaint')->first();
        KnowledgeGap::factory()->count(2)->create(['analysis_id' => $mine->id]);
        KnowledgeGap::factory()->create(['analysis_id' => $other->id]);

        $figures = $this->overview->figures($this->overview->filter(['kategorie' => 'complaint']));

        expect($figures)->toMatchArray(['count' => 4, 'copied' => 4, 'copied_unchanged' => 75, 'gaps' => 2]);
    });

    test('the usable share equals the success figure of the gaps page for the same period', function () {
        overviewAnalyses(['feedback_level' => 'unchanged'], 3);
        overviewAnalyses(['feedback_level' => 'slight'], 2);
        overviewAnalyses(['feedback_level' => 'major'], 4);
        overviewAnalyses(['feedback_level' => 'discarded']);
        overviewAnalyses(['feedback_level' => 'unchanged', 'test_until' => now()]);
        overviewAnalyses(['feedback_level' => 'discarded', 'status' => 'failed']);

        $success = app(AnalysisStore::class)->successRate();

        expect($this->overview->figures($this->overview->filter([])))->toMatchArray(['usable' => $success['share'], 'rated' => $success['rated']]);
    });
});

describe('rows', function () {
    test('the copy state is unknown before the tracking date and for failed analyses', function () {
        $old = Analysis::factory()->create(['created_at' => now()->setDate(2026, 10, 1)]);
        $new = Analysis::factory()->create(['created_at' => now()->subDay()]);
        $failed = Analysis::factory()->create(['status' => 'failed', 'created_at' => now()->subDay()]);
        $copied = Analysis::factory()->create(['reply_copied_at' => now(), 'reply_copied_changed' => true, 'created_at' => now()->subDay()]);

        expect($this->overview->copyState($old))->toBeNull()
            ->and($this->overview->copyState($new))->toBe('not-copied')
            ->and($this->overview->copyState($failed))->toBeNull()
            ->and($this->overview->copyState($copied))->toBe('copied-changed');
    });

    test('the ticket address carries the analysis and, for a test run, its cut point', function () {
        $real = Analysis::factory()->create(['ticket_number' => '2100001']);
        $test = Analysis::factory()->create(['ticket_number' => '2100002', 'scope_key' => '2100002.stand-11']);
        $failed = Analysis::factory()->create(['ticket_number' => '2100003', 'status' => 'failed']);

        expect($this->overview->ticketParameters($real))->toBe(['number' => '2100001', 'analyse' => $real->uuid])
            ->and($this->overview->ticketParameters($test))->toBe(['number' => '2100002', 'analyse' => $test->uuid, 'stand' => '11'])
            ->and($this->overview->ticketParameters($failed))->toBe(['number' => '2100003']);
    });

    test('the rows count gaps per analysis and carry no customer content', function () {
        $analysis = Analysis::factory()->create(['created_at' => now()->subDay(), 'content' => ['ticket' => '1', 'result' => ['reply' => ['text' => 'Geheim']]]]);
        KnowledgeGap::factory()->count(2)->create(['analysis_id' => $analysis->id]);

        $row = $this->overview->page($this->overview->filter([]))->getCollection()->first();

        expect($row->gaps_count)->toBe(2)->and($row->getAttributes())->not->toHaveKey('content');
    });
});
