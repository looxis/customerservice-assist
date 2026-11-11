<?php

use App\Jobs\ClassifyIncomingTicket;
use App\Jobs\ClassifyLabelTicket;
use App\Labeling\ExampleSelector;
use App\Labeling\LabelAgent;
use App\Labeling\LabelCategory;
use App\Labeling\LabelingSettings;
use App\Labeling\LabelStats;
use App\Labeling\LabelTagging;
use App\Labeling\ReviewRecorder;
use App\Models\LabelRelease;
use App\Models\LabelTicket;
use App\Models\TrackedTicket;
use App\Zammad\TicketSynchronizer;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->withoutVite();

    config([
        'staff.admins' => ['Etienne'],
        'services.zammad.url' => 'https://zammad.test',
        'services.zammad.token' => 'z-token',
        'services.zammad.sync_lock_wait' => 0,
        'ai.providers.openai.key' => 'sk-test',
        'labeling.model' => 'gpt-test',
    ]);
});

/**
 * Zammad with one ticket 500, created an hour ago, and its articles.
 *
 * @param  array<string, mixed>  $ticket
 * @param  list<array<string, mixed>>|null  $articles
 * @param  list<string>  $tags
 */
function ikZammad(array $ticket = [], ?array $articles = null, array $tags = []): void
{
    $ticket = array_merge([
        'id' => 500, 'number' => '2140500', 'title' => 'Paket', 'state' => 'new', 'group' => 'allg. Kunden', 'owner_id' => 1, 'customer_id' => 77,
        'created_at' => now()->subHour()->toIso8601ZuluString(), 'updated_at' => now()->subMinutes(50)->toIso8601ZuluString(),
        'last_contact_at' => now()->subHour()->toIso8601ZuluString(), 'last_contact_customer_at' => now()->subHour()->toIso8601ZuluString(),
    ], $ticket);
    $articles ??= [zammadArticle(['id' => 9001, 'body' => '<p>Wo bleibt mein Paket?</p>', 'created_at' => now()->subHour()->toIso8601ZuluString()])];

    Http::swap(new Factory(app('events')));
    Http::preventStrayRequests();
    Http::fake(function (Request $request) use ($ticket, $articles, $tags) {
        $path = parse_url($request->url(), PHP_URL_PATH);

        return match (true) {
            $path === '/api/v1/tickets/'.$ticket['id'] => Http::response($ticket),
            str_starts_with($path, '/api/v1/ticket_articles/by_ticket/') => Http::response($articles),
            str_starts_with($path, '/api/v1/users/') => Http::response(['firstname' => 'Nele', 'lastname' => 'Gorka', 'email' => 'erika@example.org']),
            $path === '/api/v1/tags' => Http::response(['tags' => $tags]),
            default => Http::response([], 500),
        };
    });
}

function ikAnswer(array $overrides = []): array
{
    return array_merge(['category' => 'wismo', 'unclear' => false, 'multiple_concerns' => false, 'confidence' => 0.96, 'reason' => 'Lieferstatus.', 'evidence' => 'Wo bleibt mein Paket?'], $overrides);
}

/**
 * A classified ticket of the tagging, also tracked in the ticket list.
 *
 * @param  array<string, mixed>  $ticket
 * @param  array<string, mixed>  $prediction
 */
function ikMake(array $ticket = [], array $prediction = []): LabelTicket
{
    static $zammadId = 700;
    $zammadId++;

    $made = LabelTicket::query()->create(array_merge([
        'zammad_id' => $zammadId, 'number' => (string) (2160000 + $zammadId), 'text' => 'Wo bleibt mein Paket?', 'source' => 'eingang',
        'state' => 'classified', 'split' => 'training', 'last_activity_at' => now(),
    ], $ticket));

    $made->predictions()->create(array_merge([
        'category' => 'wismo', 'confidence' => 0.95, 'reason' => 'Lieferstatus.', 'evidence' => '', 'queue' => null,
        'model' => 'gpt-test', 'prompt_version' => 'labeling-8',
    ], $prediction));

    TrackedTicket::factory()->create(['zammad_id' => $made->zammad_id, 'number' => $made->number]);

    return $made->refresh();
}

function ikChoose(LabelTicket $ticket, string $decision, string $staff = 'Kerstin'): mixed
{
    return test()->withCredentials()->withCookie('staff_name', $staff)->postJson(route('tickets.label', ['number' => $ticket->number]), ['entscheidung' => $decision]);
}

/**
 * A Zammad ID whose stable value for the test set lies in [$from, $to).
 */
function ikIdWithShare(int $from, int $to): int
{
    for ($id = 1000; ; $id++) {
        $value = abs(crc32('labeling-'.$id)) % 100;

        if ($value >= $from && $value < $to) {
            return $id;
        }
    }
}

function ikTracked(LabelTicket $ticket): TrackedTicket
{
    return TrackedTicket::query()->where('zammad_id', $ticket->zammad_id)->firstOrFail();
}

describe('classification at intake', function () {
    test('a new ticket in which the customer wrote is captured and classified without a run', function () {
        ikZammad();
        LabelAgent::fake([ikAnswer()]);

        app(TicketSynchronizer::class)->sync(500);

        $ticket = LabelTicket::query()->where('zammad_id', 500)->firstOrFail();
        expect($ticket->source)->toBe('eingang')->and($ticket->state)->toBe('classified')->and($ticket->text)->toContain('Wo bleibt mein Paket?')
            ->and($ticket->latestPrediction->category)->toBe('wismo')->and($ticket->latestPrediction->label_run_id)->toBeNull()
            ->and(ikTracked($ticket)->category)->toBe('wismo')->and(ikTracked($ticket)->category_confirmed)->toBeFalse();
    });

    test('only a ticket CSA sees for the first time, created at most 24 hours ago, with a customer message is classified', function (array $ticket, ?array $articles, bool $known, bool $classified) {
        Queue::fake();
        ikZammad($ticket, $articles);

        if ($known) {
            TrackedTicket::factory()->create(['zammad_id' => 500, 'number' => '2140500']);
        }

        app(TicketSynchronizer::class)->sync(500);

        $classified ? Queue::assertPushed(ClassifyIncomingTicket::class, fn (ClassifyIncomingTicket $job): bool => $job->zammadId === 500) : Queue::assertNotPushed(ClassifyIncomingTicket::class);
    })->with([
        'new' => [[], null, false, true],
        'imported, two days old' => [['created_at' => now()->subDays(2)->toIso8601ZuluString()], null, false, false],
        'known already, e.g. reopened' => [[], null, true, false],
        'we wrote first, no customer message yet' => [[], [zammadArticle(['id' => 9001, 'sender' => 'Agent'])], false, false],
    ]);

    test('the switch on the page stops the classification of new tickets', function () {
        ikZammad();
        app(LabelingSettings::class)->save(['intake' => false]);

        ClassifyIncomingTicket::dispatchSync(500);

        expect(LabelTicket::query()->count())->toBe(0);
    });

    test('new tickets go to the test set with 5 percent, stable per ticket', function () {
        Queue::fake([ClassifyLabelTicket::class]);
        $test = ikIdWithShare(0, 5);
        $training = ikIdWithShare(5, 20);

        foreach ([$test, $training] as $id) {
            ikZammad(['id' => $id, 'number' => (string) (2170000 + $id)]);
            ClassifyIncomingTicket::dispatchSync($id);
        }

        expect(LabelTicket::query()->where('zammad_id', $test)->value('split'))->toBe('test')
            ->and(LabelTicket::query()->where('zammad_id', $training)->value('split'))->toBe('training');
    });

    test('a ticket of an excluded group is not captured', function () {
        ikZammad(['group' => 'Spam']);
        app(LabelingSettings::class)->save(['excluded_groups' => 'Spam']);

        ClassifyIncomingTicket::dispatchSync(500);

        expect(LabelTicket::query()->count())->toBe(0);
    });

    test('a ticket CSA tracks later takes over its category from the tagging', function () {
        Queue::fake();
        LabelTicket::query()->create(['zammad_id' => 500, 'number' => '2140500', 'text' => 'Hallo', 'state' => 'classified', 'split' => 'training', 'decision' => 'reklamation', 'decision_origin' => 'zweifel']);
        ikZammad(['created_at' => now()->subYear()->toIso8601ZuluString()]);

        app(TicketSynchronizer::class)->sync(500);

        expect(TrackedTicket::query()->where('zammad_id', 500)->first())->category->toBe('reklamation')->category_confirmed->toBeTrue();
    });
});

describe('the bar on the ticket page', function () {
    test('shows the proposal, one button per category with its rules, and "Unsicher"', function () {
        fakeZammad();
        ikMake(['zammad_id' => 51234, 'number' => '2137942'], ['category' => 'reklamation']);

        $this->withCookie('staff_name', 'Kerstin')->get('/tickets/2137942')->assertOk()
            ->assertSeeText('Worum geht es?')
            ->assertSee('\u0022suggestion\u0022:\u0022reklamation\u0022', false)
            ->assertSeeText('Adressänderung')->assertSeeText('Sonstiges')->assertSeeText('Unsicher')
            ->assertSeeText('Artikel fehlt in angekommener Sendung')
            ->assertSee('role="tooltip"', false);
    });

    test('a ticket of the test set shows no proposal', function () {
        fakeZammad();
        ikMake(['zammad_id' => 51234, 'number' => '2137942', 'split' => 'test'], ['category' => 'reklamation']);

        $this->withCookie('staff_name', 'Kerstin')->get('/tickets/2137942')->assertOk()
            ->assertSeeText('Worum geht es?')
            ->assertSee('\u0022suggestion\u0022:null', false)->assertDontSee('\u0022suggestion_label\u0022:\u0022Reklamation\u0022', false);
    });

    test('no bar for a ticket that is not classified', function (?string $state) {
        fakeZammad();

        if ($state !== null) {
            LabelTicket::query()->create(['zammad_id' => 51234, 'number' => '2137942', 'state' => $state, 'split' => 'training', 'text' => $state === LabelTicket::EXCLUDED ? null : 'Hallo']);
        }

        $this->withCookie('staff_name', 'Kerstin')->get('/tickets/2137942')->assertOk()->assertDontSeeText('Worum geht es?');
    })->with(['not captured' => [null], 'still captured' => ['captured'], 'removed' => [LabelTicket::EXCLUDED]]);
});

describe('the choice of the staff', function () {
    test('a choice that matches the proposal counts at once: tagged later, no example, no sample', function () {
        $ticket = ikMake([], ['queue' => 'stichprobe']);

        ikChoose($ticket, 'wismo')->assertOk()->assertJson(['decision' => 'wismo', 'decided_by' => 'Kerstin', 'by_staff' => true, 'waiting' => false]);

        $ticket->refresh();
        expect($ticket->decision_origin)->toBe(LabelTicket::STAFF)->and($ticket->tag_kind)->toBe('bestätigt')
            ->and($ticket->reviews()->first())->origin->toBe(LabelTicket::STAFF)->hit->toBeNull()
            ->and(app(LabelTagging::class)->pending()->pluck('id')->all())->toContain($ticket->id)
            ->and(app(ExampleSelector::class)->forPrompt(5)['ids'])->not->toContain($ticket->id)
            ->and(app(LabelStats::class)->categories()->firstWhere('category', LabelCategory::Wismo)['sampled'])->toBe(0)
            ->and(ikTracked($ticket)->category_confirmed)->toBeTrue();
    });

    test('a differing choice waits for an admin: queue "abweichung", no tag until decided', function (string $split) {
        $ticket = ikMake(['split' => $split]);

        ikChoose($ticket, 'reklamation')->assertOk()->assertJson(['decision' => 'reklamation', 'waiting' => true]);

        $ticket->refresh()->load('latestPrediction', 'reviews');
        expect($ticket->latestPrediction->queue)->toBe('abweichung')->and($ticket->hasOpenDeviation())->toBeTrue()
            ->and(app(LabelTagging::class)->pending()->pluck('id')->all())->not->toContain($ticket->id);

        $this->withCookie('staff_name', 'Etienne')->get(route('labeling.review', ['warteschlange' => 'abweichung']))->assertOk()
            ->assertSeeText('Wahl von Kerstin auf der Ticketseite:');

        app(ReviewRecorder::class)->record($ticket, 'reklamation', 'Etienne');

        $ticket->refresh()->load('latestPrediction', 'reviews');
        expect($ticket->hasOpenDeviation())->toBeFalse()->and($ticket->decision_origin)->toBe($split === 'test' ? 'test' : 'korrektur')
            ->and(app(LabelTagging::class)->pending()->pluck('id')->all())->toContain($ticket->id)
            ->and($ticket->desiredTag())->toBe($split === 'test' ? 'test:reklamation' : 'train:reklamation');
    })->with(['training', 'test']);

    test('"Unsicher" is only a note: no decision, queue "zweifel", never tagged automatically', function () {
        LabelRelease::query()->create(['category' => 'wismo', 'released' => true, 'released_by' => 'Etienne', 'released_at' => now()]);
        $ticket = ikMake();
        expect(app(LabelStats::class)->mayTagAutomatically($ticket))->toBeTrue();

        ikChoose($ticket, LabelTicket::UNSURE)->assertOk()->assertJson(['decision' => null, 'unsure_by' => 'Kerstin']);

        $ticket->refresh()->load('latestPrediction');
        expect($ticket->decision)->toBeNull()->and($ticket->staff_unsure_by)->toBe('Kerstin')->and($ticket->reviews()->count())->toBe(0)
            ->and($ticket->latestPrediction->queue)->toBe('zweifel')
            ->and(app(LabelStats::class)->mayTagAutomatically($ticket))->toBeFalse()
            ->and(ikTracked($ticket))->category->toBe('wismo')->category_confirmed->toBeFalse();

        $this->withCookie('staff_name', 'Etienne')->get(route('labeling.review', ['warteschlange' => 'zweifel']))->assertOk()
            ->assertSeeText('Mitarbeiter unsicher: Kerstin');
    });

    test('"Unsicher" after an own choice takes the choice back', function () {
        $ticket = ikMake();
        ikChoose($ticket, 'reklamation')->assertOk();

        ikChoose($ticket, LabelTicket::UNSURE)->assertOk();

        expect($ticket->refresh()->decision)->toBeNull()->and($ticket->decision_origin)->toBeNull()->and($ticket->staff_unsure_by)->toBe('Kerstin');
    });

    test('the staff may change its own choice until the tag is written, never a decision of an admin', function () {
        $own = ikMake();
        ikChoose($own, 'reklamation')->assertOk();
        ikChoose($own, 'wismo', 'Nele')->assertOk()->assertJson(['decision' => 'wismo', 'decided_by' => 'Nele', 'waiting' => false]);
        expect($own->refresh()->latestPrediction->queue)->toBeNull();

        $own->update(['tag_value' => 'train:wismo', 'tag_written_at' => now()]);
        ikChoose($own, 'reklamation')->assertStatus(409);

        $admin = ikMake(['decision' => 'wismo', 'decision_origin' => 'zweifel', 'decided_by' => 'Etienne']);
        ikChoose($admin, 'reklamation')->assertStatus(409);
        expect($admin->refresh()->decision)->toBe('wismo');
    });

    test('only a category or "unsicher" is accepted, and only with a chosen name', function () {
        $ticket = ikMake();

        $this->withCredentials()->postJson(route('tickets.label', ['number' => $ticket->number]), ['entscheidung' => 'wismo'])->assertStatus(409);
        ikChoose($ticket, 'keine')->assertStatus(422);
        expect($ticket->refresh()->decision)->toBeNull();
    });

    test('the choices of the staff stay out of "Zurück" on the review page', function () {
        $ticket = ikMake();
        ikChoose($ticket, 'wismo', 'Etienne')->assertOk();

        expect(app(ReviewRecorder::class)->recent('Etienne'))->toBeEmpty();
    });
});

describe('the ticket list on the start page', function () {
    test('shows the valid category of each ticket', function (array $ticket, ?string $category, bool $confirmed) {
        $made = ikMake($ticket, ['category' => 'reklamation']);

        $made->shareCategory();

        expect(ikTracked($made))->category->toBe($category)->category_confirmed->toBe($confirmed);
    })->with([
        'proposal' => [[], 'reklamation', false],
        'decided by an admin' => [['decision' => 'zahlung', 'decision_origin' => 'zweifel'], 'zahlung', true],
        'tagged automatically' => [['decision' => 'reklamation', 'decision_origin' => 'automatisch'], 'reklamation', false],
        'test set, undecided' => [['split' => 'test'], null, false],
        'test set, decided' => [['split' => 'test', 'decision' => 'zahlung', 'decision_origin' => LabelTicket::STAFF], 'zahlung', true],
        'admin unsure' => [['decision' => LabelTicket::UNSURE, 'decision_origin' => 'zweifel'], 'reklamation', false],
    ]);

    test('a removed ticket shows no category', function () {
        $ticket = ikMake();
        $ticket->shareCategory();

        $ticket->exclude('Etienne');

        expect(ikTracked($ticket)->category)->toBeNull();
    });

    test('the command fills the categories of the tickets tracked already', function () {
        $ticket = ikMake([], ['category' => 'zahlung']);

        $this->artisan('labeling:share-categories')->assertSuccessful();

        expect(ikTracked($ticket)->category)->toBe('zahlung');
    });
});

describe('admin pages', function () {
    test('the ticket list filters by source and by the staff', function () {
        $byStaff = ikMake(['number' => '2169001', 'decision' => 'wismo', 'decision_origin' => LabelTicket::STAFF, 'decided_by' => 'Kerstin']);
        $unsure = ikMake(['number' => '2169002', 'staff_unsure_by' => 'Nele', 'staff_unsure_at' => now()]);
        $pool = ikMake(['number' => '2169003', 'source' => 'pool']);
        $admin = test()->withCookie('staff_name', 'Etienne');

        $admin->get(route('labeling.tickets', ['entscheidung' => 'mitarbeiter']))->assertOk()->assertSee($byStaff->number)->assertDontSee($unsure->number)->assertSeeText('von Kerstin (Mitarbeiter)');
        $admin->get(route('labeling.tickets', ['entscheidung' => 'mitarbeiter-unsicher']))->assertOk()->assertSee($unsure->number)->assertDontSee($byStaff->number);
        $admin->get(route('labeling.tickets', ['quelle' => 'eingang']))->assertOk()->assertSee($byStaff->number)->assertDontSee($pool->number);
    });

    test('the switch for new tickets is saved with the settings', function () {
        $values = ['target' => 100, 'min_cases' => 50, 'min_rate' => 95, 'sample_share' => 15, 'confidence' => 0.85, 'examples' => 3, 'test_target' => 50];

        $this->withCookie('staff_name', 'Etienne')->patch(route('labeling.settings.update'), [...$values, 'intake' => '0'])->assertSessionHas('success');
        expect(app(LabelingSettings::class)->all()['intake'])->toBeFalse();

        $this->withCookie('staff_name', 'Etienne')->patch(route('labeling.settings.update'), [...$values, 'intake' => '1'])->assertSessionHas('success');
        expect(app(LabelingSettings::class)->all()['intake'])->toBeTrue();
    });
});
