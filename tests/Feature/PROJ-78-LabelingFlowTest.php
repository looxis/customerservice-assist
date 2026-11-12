<?php

use App\Jobs\ClassifyLabelTicket;
use App\Jobs\Middleware\SkipInactiveLabelRun;
use App\Labeling\ExampleSelector;
use App\Labeling\LabelAgent;
use App\Labeling\LabelCapture;
use App\Labeling\LabelCategory;
use App\Labeling\LabelClassifier;
use App\Labeling\LabelingSettings;
use App\Labeling\LabelRunner;
use App\Labeling\LabelStats;
use App\Labeling\LabelTagging;
use App\Labeling\ReviewQueue;
use App\Labeling\SenderFilter;
use App\Models\LabelPrediction;
use App\Models\LabelRelease;
use App\Models\LabelReview;
use App\Models\LabelRun;
use App\Models\LabelTicket;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->withoutVite();

    config([
        'staff.admins' => ['Etienne'],
        'staff.zammad_tokens' => ['Etienne' => 'etienne-token'],
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'ai.providers.openai.key' => 'sk-test',
        'labeling.earliest' => now()->startOfMonth()->toDateString(),
        'labeling.model' => 'gpt-test',
    ]);
});

/**
 * @param  list<string>  $tags
 * @param  list<array<string, mixed>>|null  $articles
 * @param  array<string, mixed>  $head
 */
function lblZammad(array $tags = [], ?array $articles = null, array $head = [], int $total = 1, array $override = []): void
{
    fakeZammad($articles ?? [zammadArticle(['id' => 1, 'body' => '<p>Wo bleibt mein Paket?</p>']), zammadArticle(['id' => 2, 'sender' => 'Agent', 'body' => '<p>Wir prüfen das.</p>', 'created_at' => '2026-10-02T07:12:00.000Z']), zammadArticle(['id' => 3, 'body' => '<p>Noch eine Frage zur Rechnung.</p>', 'created_at' => '2026-10-03T07:12:00.000Z'])], extra: array_merge([
        'zammad.test/api/v1/tickets/search*' => Http::response(['records' => [['id' => 51234]], 'total_count' => $total]),
        'zammad.test/api/v1/tickets/51234*' => Http::response(array_merge(['id' => 51234, 'number' => '2137942', 'title' => 'Paket', 'state' => 'open', 'group' => 'Kundenservice', 'customer_id' => 77, 'created_at' => '2026-10-01T07:12:00.000Z', 'updated_at' => '2026-10-03T07:12:00.000Z'], $head)),
        'zammad.test/api/v1/tags/add' => Http::response(['ok' => true]),
        'zammad.test/api/v1/tags/remove' => Http::response(['ok' => true]),
        'zammad.test/api/v1/tags*' => Http::response(['tags' => $tags]),
    ], $override));
}

function lblAdmin(): TestCase
{
    return test()->withCookie('staff_name', 'Etienne');
}

/**
 * @param  array<string, mixed>  $ticket
 * @param  array<string, mixed>  $prediction
 */
function lblMake(array $ticket = [], array $prediction = []): LabelTicket
{
    static $zammadId = 100;
    $zammadId++;

    $made = LabelTicket::query()->create(array_merge([
        'zammad_id' => $zammadId, 'number' => (string) (2150000 + $zammadId), 'text' => 'Wo bleibt mein Paket?',
        'state' => 'classified', 'split' => 'training', 'last_activity_at' => now(),
    ], $ticket));

    $made->predictions()->create(array_merge([
        'category' => 'wismo', 'confidence' => 0.95, 'reason' => 'Lieferstatus.', 'evidence' => '', 'queue' => null,
        'model' => 'gpt-test', 'prompt_version' => 'labeling-1',
    ], $prediction));

    return $made->refresh();
}

function lblAnswer(array $overrides = []): array
{
    return array_merge(['category' => 'wismo', 'unclear' => false, 'multiple_concerns' => false, 'confidence' => 0.96, 'reason' => 'Lieferstatus.', 'evidence' => 'Wo bleibt mein Paket?'], $overrides);
}

describe('retrieval', function () {
    it('tags spam plainly as spam and recognizes the tag on existing tickets', function () {
        expect(LabelCategory::Spam->tag())->toBe('spam')
            ->and(LabelCategory::Spam->tag(true))->toBe('test:spam')
            ->and(LabelCategory::Wismo->tag())->toBe('train:wismo')
            ->and(LabelCategory::Adressaenderung->tag())->toBe('train:adressaenderung')
            ->and(LabelCategory::Zahlung->tag(true))->toBe('test:zahlung')
            ->and(LabelTagging::existing(['vip', 'spam']))->toBe(['tag' => 'spam', 'category' => LabelCategory::Spam, 'test' => false])
            ->and(preg_match(LabelTagging::PATTERN, 'spam'))->toBe(1)
            ->and(preg_match(LabelTagging::PATTERN, 'spamfilter'))->toBe(0);
    });

    it('ignores the dry-run flag for runs other than writing tags', function () {
        lblZammad();

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'fetch', 'dry_run' => '1'])->assertSessionHas('success');

        expect(LabelRun::query()->sole()->options)->toBe(['dry_run' => false]);
    });

    it('captures only the first customer message of a ticket', function () {
        lblZammad();

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'fetch'])->assertRedirect()->assertSessionHas('success');

        $ticket = LabelTicket::query()->sole();
        expect($ticket->zammad_id)->toBe(51234)
            ->and($ticket->text)->toContain('Wo bleibt mein Paket')->not->toContain('Rechnung')->not->toContain('Wir prüfen')
            ->and($ticket->state)->toBe('captured')
            ->and($ticket->source)->toBe('pool');

        $run = LabelRun::query()->sole();
        expect($run->state)->toBe('done')->and($run->total)->toBe(1)->and($run->done)->toBe(1);
    });

    it('captures subject and sender of the first customer message', function () {
        lblZammad(head: ['title' => 'Produktanfrage des Amazon-Kunden Cristiano']);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'fetch'])->assertRedirect();

        $ticket = LabelTicket::query()->sole();
        expect($ticket->subject)->toBe('Produktanfrage des Amazon-Kunden Cristiano')->and($ticket->sender)->not->toBeEmpty();
        expect($ticket->classifierInput(1000))->toContain('Betreff: Produktanfrage des Amazon-Kunden Cristiano')->toContain('Absender: ')->toContain('Wo bleibt mein Paket');
    });

    it('skips tickets where we wrote first and the customer only answered', function () {
        lblZammad(articles: [
            zammadArticle(['id' => 1, 'sender' => 'Agent', 'from' => 'Kundenservice <info@example.com>', 'body' => '<p>Welches Motiv möchten Sie?</p>', 'created_at' => '2026-10-01T06:00:00.000Z']),
            zammadArticle(['id' => 2, 'body' => '<p>Das zweite bitte.</p>']),
        ]);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'fetch'])->assertRedirect();

        $ticket = LabelTicket::query()->sole();
        expect($ticket->state)->toBe(LabelTicket::EXCLUDED)->and($ticket->text)->toBeNull();
    });

    it('removes captured tickets that answer a mail of ours with the outreach command', function () {
        lblZammad(articles: [
            zammadArticle(['id' => 1, 'sender' => 'Agent', 'from' => 'Kundenservice <info@example.com>', 'body' => '<p>Welches Motiv möchten Sie?</p>', 'created_at' => '2026-10-01T06:00:00.000Z']),
            zammadArticle(['id' => 2, 'body' => '<p>Das zweite bitte.</p>']),
        ]);
        $outreach = lblMake(['zammad_id' => 51234]);

        $this->artisan('labeling:remove-outreach', ['--pause' => 0])->assertSuccessful();

        expect($outreach->fresh()->state)->toBe(LabelTicket::EXCLUDED)->and($outreach->fresh()->text)->toBeNull();
    });

    it('skips mails of filtered senders and keeps only a marker', function () {
        lblZammad(articles: [zammadArticle(['id' => 1, 'from' => 'Amazon <non-rispondere@amazon.it>', 'body' => '<p>Azione richiesta per l\'ordine</p>'])]);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'fetch'])->assertRedirect();

        $ticket = LabelTicket::query()->sole();
        expect($ticket->state)->toBe(LabelTicket::EXCLUDED)->and($ticket->text)->toBeNull()->and($ticket->sender)->toBeNull();
    });

    it('removes captured tickets of a new sender when the filter is saved', function () {
        $amazon = LabelTicket::query()->create(['zammad_id' => 1, 'number' => '1', 'text' => 'Hinweis', 'sender' => 'Amazon <NoReply@amazon.com>', 'state' => 'classified', 'source' => 'pool', 'split' => 'training']);
        $customer = LabelTicket::query()->create(['zammad_id' => 2, 'number' => '2', 'text' => 'Wo ist mein Paket?', 'sender' => 'Erika <erika@example.org>', 'state' => 'classified', 'source' => 'pool', 'split' => 'training']);
        $tagged = LabelTicket::query()->create(['zammad_id' => 3, 'number' => '3', 'text' => 'Hinweis', 'sender' => 'noreply@amazon.com', 'state' => 'classified', 'source' => 'vorhanden', 'split' => 'training']);

        lblAdmin()->patch('/beschriftung/einstellungen', [
            'target' => 100, 'min_cases' => 50, 'min_rate' => 95, 'sample_share' => 15, 'confidence' => 0.85, 'examples' => 8, 'test_target' => 50,
            'excluded_senders' => "noreply@amazon.com\n@example.invalid",
        ])->assertSessionHas('success');

        expect($amazon->refresh()->state)->toBe(LabelTicket::EXCLUDED)->and($amazon->text)->toBeNull()
            ->and($customer->refresh()->state)->toBe('classified')
            ->and($tagged->refresh()->state)->toBe('classified');
    });

    it('lets an exception pattern with a leading exclamation mark pass a sender of a filtered domain', function () {
        app(LabelingSettings::class)->save(['excluded_senders' => '@looxis.com, !france@looxis.com']);
        $filter = app(SenderFilter::class);

        expect($filter->matches('Intern <mail@looxis.com>'))->toBeTrue()
            ->and($filter->matches('France LOOXIS <France@looxis.com>'))->toBeFalse()
            ->and($filter->matches('Erika <erika@example.org>'))->toBeFalse();
    });

    it('does not capture a ticket twice', function () {
        lblZammad();

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'fetch']);
        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'fetch']);

        expect(LabelTicket::query()->count())->toBe(1);
    });

    it('takes over a ticket that already carries a tag as a reference', function () {
        lblZammad(['train:wismo']);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'fetch']);

        $ticket = LabelTicket::query()->sole();
        expect($ticket->source)->toBe('vorhanden')->and($ticket->decision)->toBe('wismo')->and($ticket->decision_origin)->toBe('vorhanden')->and($ticket->tag_value)->toBe('train:wismo')->and($ticket->isTest())->toBeFalse();
    });

    it('puts a ticket with a test tag into the test set', function () {
        lblZammad(['test:reklamation']);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'fetch']);

        expect(LabelTicket::query()->sole())->isTest()->toBeTrue();
    });

    it('marks a ticket without a customer message as unusable', function () {
        lblZammad(articles: [zammadArticle(['id' => 2, 'sender' => 'Agent', 'body' => '<p>Intern</p>'])]);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'fetch']);

        $ticket = LabelTicket::query()->sole();
        expect($ticket->state)->toBe('unusable')->and($ticket->text)->toBeNull();
    });

    it('keeps a customer mail with an empty body as the subject carries the message', function () {
        lblZammad(articles: [zammadArticle(['id' => 2, 'body' => '<div dir="auto"></div>', 'subject' => 'Ich möchte diese Tasse bestellen'])]);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'fetch']);

        $ticket = LabelTicket::query()->sole();
        expect($ticket->state)->toBe('captured')->and($ticket->text)->toBe(LabelCapture::SUBJECT_ONLY_TEXT)
            ->and($ticket->classifierInput(1000))->toContain('steht im Betreff');
    });

    it('skips merged tickets', function () {
        lblZammad(head: ['state' => 'merged']);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'fetch']);

        expect(LabelTicket::query()->count())->toBe(0)->and(LabelRun::query()->sole()->state)->toBe('done');
    });

    it('leaves out the groups named on the page', function () {
        lblZammad();

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'fetch', 'excluded_groups' => 'Spam']);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'tickets/search') && str_contains(urldecode($request->url()), 'NOT group.name:"Spam"'));
    });

    it('cuts a period with too many tickets in halves', function () {
        config(['labeling.window_limit' => 3]);
        lblZammad(override: [
            'zammad.test/api/v1/tickets/search*' => Http::sequence()->push(['records' => [['id' => 51234]], 'total_count' => 5])->whenEmpty(Http::response(['records' => [['id' => 51234]], 'total_count' => 1])),
        ]);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'fetch']);

        $searches = Http::recorded(fn (Request $request): bool => str_contains($request->url(), 'tickets/search'));
        expect($searches)->toHaveCount(3)->and(LabelTicket::query()->count())->toBe(1);
    });
});

describe('classification', function () {
    it('drops the jobs of a cancelled run before they wait for the rate limit', function () {
        $run = LabelRun::query()->create(['kind' => 'classify', 'state' => 'cancelled', 'admin' => 'Etienne']);
        $ran = false;

        (new SkipInactiveLabelRun)->handle(new ClassifyLabelTicket($run->id, 1), function () use (&$ran): void {
            $ran = true;
        });

        expect($ran)->toBeFalse();
    });

    it('classifies only as many tickets as the limit says, the newest first', function () {
        Queue::fake();
        lblMake(['state' => 'captured', 'split' => 'test', 'zammad_created_at' => '2026-10-20 08:00:00']);
        $newest = lblMake(['state' => 'captured', 'split' => 'training', 'zammad_created_at' => '2026-10-10 08:00:00']);
        lblMake(['state' => 'captured', 'split' => 'training', 'zammad_created_at' => '2024-03-01 08:00:00']);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'classify', 'limit' => '1'])->assertSessionHasNoErrors();

        $order = Queue::pushed(ClassifyLabelTicket::class)->map(fn (ClassifyLabelTicket $job): int => $job->labelTicketId)->all();
        expect($order)->toBe([$newest->id])->and(LabelRun::query()->sole()->total)->toBe(1);
    });

    it('classifies only tickets from the chosen day on and shows the day in the run list', function () {
        Queue::fake();
        $july = lblMake(['state' => 'captured', 'zammad_created_at' => '2026-06-30 22:30:00']);
        $october = lblMake(['state' => 'captured', 'zammad_created_at' => '2026-10-01 08:00:00']);
        lblMake(['state' => 'captured', 'zammad_created_at' => '2026-06-30 21:30:00']);
        lblMake(['state' => 'captured', 'zammad_created_at' => '2024-05-01 08:00:00']);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'classify', 'from' => '2026-07-01'])->assertSessionHasNoErrors();

        $order = Queue::pushed(ClassifyLabelTicket::class)->map(fn (ClassifyLabelTicket $job): int => $job->labelTicketId)->all();
        expect($order)->toBe([$october->id, $july->id])
            ->and(LabelRun::query()->sole()->options['from'])->toBe('2026-07-01');
        lblAdmin()->get('/beschriftung')->assertSee('0 von 2, ab 01.07.2026');
    });

    it('applies the day to a new classification too', function () {
        Queue::fake();
        $recent = lblMake(['zammad_created_at' => '2026-08-01 08:00:00']);
        lblMake(['zammad_created_at' => '2024-05-01 08:00:00']);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'reclassify', 'from' => '2026-07-01'])->assertSessionHasNoErrors();

        expect(Queue::pushed(ClassifyLabelTicket::class)->map(fn (ClassifyLabelTicket $job): int => $job->labelTicketId)->all())->toBe([$recent->id]);
    });

    it('refuses a day in the future', function () {
        Queue::fake();

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'classify', 'from' => now()->addDays(2)->toDateString()])->assertSessionHasErrors('from');

        expect(LabelRun::query()->count())->toBe(0);
    });

    it('classifies the training set before the test set', function () {
        Queue::fake();
        $test = lblMake(['state' => 'captured', 'split' => 'test']);
        $training = lblMake(['state' => 'captured', 'split' => 'training']);

        app(LabelRunner::class)->start('classify', 'Etienne');

        $order = Queue::pushed(ClassifyLabelTicket::class)->map(fn (ClassifyLabelTicket $job): int => $job->labelTicketId)->all();
        expect($order)->toBe([$training->id, $test->id]);
    });

    it('files the proposal with model and prompt version', function () {
        LabelAgent::fake([lblAnswer()]);
        $ticket = lblMake(['state' => 'captured']);
        $ticket->predictions()->delete();

        $prediction = app(LabelClassifier::class)->classify($ticket);

        expect($prediction->category)->toBe('wismo')->and($prediction->prompt_version)->toStartWith('labeling-8')->and($prediction->model)->not->toBe('')
            ->and($ticket->refresh()->state)->toBe('classified');
    });

    it('sorts a proposal into its review queue', function (array $answer, string $text, string $split, ?string $queue) {
        app(LabelingSettings::class)->save(['sample_share' => 0]);
        LabelAgent::fake([lblAnswer($answer)]);
        $ticket = lblMake(['text' => $text, 'split' => $split, 'state' => 'captured']);

        expect(app(LabelClassifier::class)->classify($ticket)->queue)->toBe($queue);
    })->with([
        'unclear' => [['unclear' => true], 'Hallo', 'training', 'zweifel'],
        'several concerns' => [['multiple_concerns' => true], 'Hallo', 'training', 'zweifel'],
        'low confidence' => [['confidence' => 0.5], 'Hallo', 'training', 'konfidenz'],
        'unknown category' => [['category' => 'keine'], 'Hallo', 'training', 'konfidenz'],
        'other, clear' => [['category' => 'sonstiges'], 'Danke für alles!', 'training', null],
        'storno' => [['category' => 'storno'], 'Bitte stornieren', 'training', 'storno'],
        'address change' => [['category' => 'adressaenderung'], 'Bitte Adresse ändern', 'training', 'storno'],
        'payment' => [['category' => 'zahlung'], 'Die Rechnung fehlt', 'training', null],
        'storno word, not wismo' => [['category' => 'reklamation'], 'Ich möchte Storno', 'training', 'storno-wort'],
        'storno word, wismo' => [['category' => 'wismo', 'confidence' => 0.99], 'Storno wegen Lieferung', 'training', null],
        'clear' => [[], 'Wo bleibt mein Paket?', 'training', null],
        'test set' => [['unclear' => true], 'Hallo', 'test', null],
    ]);

    it('puts clear proposals into the random sample according to the share', function () {
        app(LabelingSettings::class)->save(['sample_share' => 100]);
        LabelAgent::fake([lblAnswer()]);
        $ticket = lblMake(['state' => 'captured']);

        expect(app(LabelClassifier::class)->classify($ticket)->queue)->toBe('stichprobe');
    });

    it('does not put a reference with a tag into any queue', function () {
        LabelAgent::fake([lblAnswer(['unclear' => true])]);
        $ticket = lblMake(['state' => 'captured', 'decision' => 'wismo', 'decision_origin' => 'vorhanden']);

        expect(app(LabelClassifier::class)->classify($ticket)->queue)->toBeNull();
    });

    it('puts a decided ticket into the queue abweichung when the new proposal differs', function (string $split, string $decision, array $answer) {
        LabelAgent::fake([lblAnswer($answer)]);
        $ticket = lblMake(['split' => $split, 'decision' => $decision, 'decision_origin' => $split === 'test' ? 'test' : 'zweifel']);

        expect(app(LabelClassifier::class)->classify($ticket)->queue)->toBe('abweichung')->and($ticket->refresh()->decision)->toBe($decision);
    })->with([
        'storno now address change' => ['training', 'storno', ['category' => 'adressaenderung']],
        'other now payment' => ['training', 'sonstiges', ['category' => 'zahlung']],
        'payment now other' => ['training', 'zahlung', ['category' => 'sonstiges']],
        'test set' => ['test', 'sonstiges', ['category' => 'zahlung']],
    ]);

    it('leaves a decided ticket out of every queue when the new proposal agrees', function () {
        LabelAgent::fake([lblAnswer(['category' => 'sonstiges', 'unclear' => true])]);
        $ticket = lblMake(['decision' => 'sonstiges', 'decision_origin' => 'zweifel']);

        expect(app(LabelClassifier::class)->classify($ticket)->queue)->toBeNull();
    });

    it('never puts a reference into the queue abweichung', function () {
        LabelAgent::fake([lblAnswer(['category' => 'reklamation'])]);
        $ticket = lblMake(['decision' => 'wismo', 'decision_origin' => 'vorhanden']);

        expect(app(LabelClassifier::class)->classify($ticket)->queue)->toBeNull();
    });

    it('reclassifies classified tickets with and without decision, but neither captured nor tagged ones', function () {
        Queue::fake();
        $open = lblMake();
        $decided = lblMake(['decision' => 'storno', 'decision_origin' => 'zweifel']);
        $failed = lblMake(['state' => 'failed']);
        lblMake(['state' => 'captured']);
        lblMake(['decision' => 'wismo', 'decision_origin' => 'zweifel', 'tag_value' => 'train:wismo']);

        app(LabelRunner::class)->start('reclassify', 'Etienne');

        $ids = Queue::pushed(ClassifyLabelTicket::class)->map(fn (ClassifyLabelTicket $job): int => $job->labelTicketId)->all();
        expect($ids)->toEqualCanonicalizing([$open->id, $decided->id, $failed->id]);
    });

    it('reclassifies only tickets whose newest proposal is the chosen category and shows it in the run list', function () {
        Queue::fake();
        $wismo = lblMake();
        $decided = lblMake(['decision' => 'reklamation', 'decision_origin' => 'stichprobe']);
        lblMake([], ['category' => 'reklamation']);
        $changed = lblMake([], ['category' => 'reklamation']);
        $changed->predictions()->create(['category' => 'wismo', 'confidence' => 0.9, 'reason' => 'Lieferstatus.', 'evidence' => '', 'model' => 'gpt-test', 'prompt_version' => 'labeling-1']);
        $outdated = lblMake();
        $outdated->predictions()->create(['category' => 'reklamation', 'confidence' => 0.9, 'reason' => 'Mangel.', 'evidence' => '', 'model' => 'gpt-test', 'prompt_version' => 'labeling-1']);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'reclassify', 'category' => 'wismo'])->assertSessionHasNoErrors();

        $ids = Queue::pushed(ClassifyLabelTicket::class)->map(fn (ClassifyLabelTicket $job): int => $job->labelTicketId)->all();
        expect($ids)->toEqualCanonicalizing([$wismo->id, $decided->id, $changed->id]);
        lblAdmin()->get('/beschriftung')->assertSee('0 von 3, nur Vorschläge WISMO (Lieferstatus)');
    });

    it('ignores the category for a first classification and refuses an unknown one', function () {
        Queue::fake();
        lblMake(['state' => 'captured'], ['category' => 'reklamation']);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'classify', 'category' => 'wismo'])->assertSessionHasNoErrors();
        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'reclassify', 'category' => 'werbung'])->assertSessionHasErrors('category');

        expect(LabelRun::query()->sole()->options)->not->toHaveKey('category')
            ->and(Queue::pushed(ClassifyLabelTicket::class))->toHaveCount(1);
    });

    it('leaves tickets without text out of every run', function () {
        Queue::fake();
        $kept = lblMake();
        lblMake(['state' => 'failed', 'text' => null]);
        lblMake(['state' => 'captured', 'text' => null]);

        app(LabelRunner::class)->start('reclassify', 'Etienne');
        app(LabelRunner::class)->start('classify', 'Etienne');

        $ids = Queue::pushed(ClassifyLabelTicket::class)->map(fn (ClassifyLabelTicket $job): int => $job->labelTicketId)->all();
        expect($ids)->toBe([$kept->id]);
    });

    it('keeps a ticket removed when its classification fails afterwards', function () {
        $ticket = lblMake();
        $ticket->exclude('Etienne');
        $run = LabelRun::query()->create(['kind' => 'reclassify', 'state' => 'running', 'admin' => 'Etienne', 'total' => 1]);

        (new ClassifyLabelTicket($run->id, $ticket->id))->failed();

        expect($ticket->refresh())->state->toBe(LabelTicket::EXCLUDED)->problem->toBe('Entfernt');
    });

    it('drops the proposal of a ticket removed while the model answered', function () {
        $ticket = lblMake(['state' => 'captured']);
        $ticket->predictions()->delete();
        LabelAgent::fake(function () use ($ticket) {
            LabelTicket::query()->find($ticket->id)->exclude('Etienne');

            return lblAnswer();
        });

        app(LabelClassifier::class)->classify($ticket);

        expect($ticket->refresh()->state)->toBe(LabelTicket::EXCLUDED)->and(LabelPrediction::query()->count())->toBe(0);
    });

    it('takes confirmed tickets as examples but never the test set or the references', function () {
        LabelAgent::fake(function (string $prompt) {
            return lblAnswer();
        });
        $example = lblMake(['text' => 'Beispiel Konfigurator kaputt', 'decision' => 'technischer-support', 'decision_origin' => 'zweifel', 'decided_at' => now()]);
        lblMake(['text' => 'Testfall geheim', 'split' => 'test', 'decision' => 'wismo', 'decision_origin' => 'test', 'decided_at' => now()]);
        lblMake(['text' => 'Referenz alt', 'decision' => 'wismo', 'decision_origin' => 'vorhanden', 'decided_at' => now()]);

        $examples = app(ExampleSelector::class)->forPrompt(8);

        expect($examples['ids'])->toBe([$example->id])->and($examples['text'])->toContain('Beispiel Konfigurator kaputt')->not->toContain('Testfall geheim')->not->toContain('Referenz alt');
    });

    it('takes no examples of storno, sonstiges and bestellung decided before their meaning changed', function () {
        $this->travelTo('2026-11-11 08:00:00');
        lblMake(['text' => 'Alte Mahnung', 'decision' => 'sonstiges', 'decision_origin' => 'zweifel', 'decided_at' => '2026-11-07 10:00:00']);
        lblMake(['text' => 'Alte Adresse ändern', 'decision' => 'storno', 'decision_origin' => 'zweifel', 'decided_at' => '2026-11-07 10:00:00']);
        lblMake(['text' => 'Alter Lieferstatus', 'decision' => 'wismo', 'decision_origin' => 'zweifel', 'decided_at' => '2026-11-07 10:00:00']);
        lblMake(['text' => 'Habe bestellt, bitte beachten', 'decision' => 'bestellung', 'decision_origin' => 'korrektur', 'decided_at' => '2026-11-10 09:00:00']);
        lblMake(['text' => 'Neu geprüft Name falsch', 'decision' => 'storno', 'decision_origin' => 'korrektur', 'decided_at' => now()]);
        lblMake(['text' => 'Hiermit bestelle ich', 'decision' => 'bestellung', 'decision_origin' => 'zweifel', 'decided_at' => now()]);

        $examples = app(ExampleSelector::class)->forPrompt(8)['text'];

        expect($examples)->toContain('Alter Lieferstatus')->toContain('Neu geprüft Name falsch')->toContain('Hiermit bestelle ich')
            ->not->toContain('Alte Mahnung')->not->toContain('Alte Adresse ändern')->not->toContain('Habe bestellt, bitte beachten');
    });

    it('runs through a classification run', function () {
        LabelAgent::fake([lblAnswer(), lblAnswer()]);
        lblMake(['state' => 'captured'])->predictions()->delete();
        lblMake(['state' => 'captured'])->predictions()->delete();

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'classify'])->assertSessionHas('success');

        expect(LabelPrediction::query()->count())->toBe(2)->and(LabelRun::query()->sole())->done->toBe(2)->state->toBe('done');
    });
});

describe('review', function () {
    it('records a confirmed sample as a hit', function () {
        $ticket = lblMake([], ['queue' => 'stichprobe']);

        lblAdmin()->post("/beschriftung/tickets/{$ticket->id}/entscheidung", ['category' => 'wismo', 'queue' => 'stichprobe'])->assertRedirect(route('labeling.review', ['warteschlange' => 'stichprobe']));

        $review = LabelReview::query()->sole();
        expect($review->origin)->toBe('stichprobe')->and($review->hit)->toBeTrue()->and($review->reviewer)->toBe('Etienne')
            ->and($ticket->refresh()->decision)->toBe('wismo')->and($ticket->decision_origin)->toBe('stichprobe');
    });

    it('records a corrected sample as a miss', function () {
        $ticket = lblMake([], ['queue' => 'stichprobe']);

        lblAdmin()->post("/beschriftung/tickets/{$ticket->id}/entscheidung", ['category' => 'reklamation']);

        expect(LabelReview::query()->sole()->hit)->toBeFalse();
    });

    it('does not count doubtful cases in the hit rate', function () {
        $ticket = lblMake([], ['queue' => 'zweifel']);

        lblAdmin()->post("/beschriftung/tickets/{$ticket->id}/entscheidung", ['category' => 'wismo']);

        expect(LabelReview::query()->sole())->origin->toBe('zweifel')->hit->toBeNull();
    });

    it('records the decision for a ticket of the test set as test', function () {
        $ticket = lblMake(['split' => 'test']);

        lblAdmin()->post("/beschriftung/tickets/{$ticket->id}/entscheidung", ['category' => 'sonstiges']);

        expect($ticket->refresh()->decision)->toBe('sonstiges')->and($ticket->decision_origin)->toBe('test')->and($ticket->desiredTag())->toBe('test:sonstiges');
    });

    it('keeps the history when an admin corrects a decision', function () {
        $ticket = lblMake([], ['queue' => 'zweifel']);

        lblAdmin()->post("/beschriftung/tickets/{$ticket->id}/entscheidung", ['category' => 'wismo']);
        lblAdmin()->post("/beschriftung/tickets/{$ticket->id}/entscheidung", ['category' => 'reklamation']);

        expect(LabelReview::query()->count())->toBe(2)->and($ticket->refresh()->decision)->toBe('reklamation')->and($ticket->decision_origin)->toBe('korrektur');
    });

    it('takes a deviation out of its queue once an admin decided, also when the decision stays', function () {
        $ticket = lblMake(['decision' => 'storno', 'decision_origin' => 'zweifel'], ['category' => 'adressaenderung', 'queue' => 'abweichung']);
        $queue = app(ReviewQueue::class);

        expect($queue->query('abweichung')->pluck('id')->all())->toBe([$ticket->id]);

        lblAdmin()->post("/beschriftung/tickets/{$ticket->id}/entscheidung", ['category' => 'storno', 'queue' => 'abweichung']);

        expect($queue->query('abweichung')->count())->toBe(0)
            ->and($ticket->refresh()->decision)->toBe('storno')->and($ticket->decision_origin)->toBe('korrektur');
    });

    it('goes back to the last two decisions of the admin, not further', function () {
        [$first, $second, $third] = [lblMake([], ['queue' => 'konfidenz']), lblMake([], ['queue' => 'konfidenz']), lblMake([], ['queue' => 'konfidenz'])];

        foreach ([$first, $second, $third] as $ticket) {
            lblAdmin()->post("/beschriftung/tickets/{$ticket->id}/entscheidung", ['category' => 'wismo', 'queue' => 'konfidenz']);
        }

        lblAdmin()->get('/beschriftung/pruefen?warteschlange=konfidenz')->assertSeeText('In dieser Warteschlange ist nichts mehr zu prüfen.')->assertSeeText('Zurück zur letzten Entscheidung');
        lblAdmin()->get('/beschriftung/pruefen?warteschlange=konfidenz&zurueck=1')->assertSeeText('Ticket#'.$third->number)->assertSeeText('Deine letzte Entscheidung')->assertSee('data-key="z"', false)
            ->assertSee('action="'.route('labeling.reviews.amend', ['review' => LabelReview::query()->where('label_ticket_id', $third->id)->value('id')]).'"', false);
        lblAdmin()->get('/beschriftung/pruefen?warteschlange=konfidenz&zurueck=2')->assertSeeText('Ticket#'.$second->number)->assertSeeText('Deine vorletzte Entscheidung')->assertDontSee('data-key="z"', false);
        lblAdmin()->get('/beschriftung/pruefen?warteschlange=konfidenz&zurueck=3')->assertSeeText('Ticket#'.$second->number)->assertDontSeeText('Ticket#'.$first->number);
    });

    it('changes a decision in place and counts a sample by the new answer', function () {
        $ticket = lblMake([], ['queue' => 'stichprobe']);
        lblAdmin()->post("/beschriftung/tickets/{$ticket->id}/entscheidung", ['category' => 'reklamation', 'queue' => 'stichprobe']);
        $review = LabelReview::query()->sole();

        lblAdmin()->patch("/beschriftung/entscheidungen/{$review->id}", ['category' => 'wismo', 'queue' => 'stichprobe'])
            ->assertRedirect(route('labeling.review', ['warteschlange' => 'stichprobe']))->assertSessionHas('success');

        expect(LabelReview::query()->sole())->decision->toBe('wismo')->origin->toBe('stichprobe')->hit->toBeTrue()
            ->and($ticket->refresh()->decision)->toBe('wismo')->and($ticket->decision_origin)->toBe('stichprobe')->and($ticket->tag_kind)->toBe('bestätigt');
    });

    it('takes a changed decision out of hit rate and tag when it becomes unsure', function () {
        $ticket = lblMake(['decision' => 'wismo', 'decision_origin' => 'stichprobe', 'tag_kind' => 'bestätigt', 'tag_value' => 'train:wismo'], ['queue' => 'stichprobe']);
        $review = $ticket->reviews()->create(['label_prediction_id' => $ticket->latestPrediction->id, 'decision' => 'wismo', 'origin' => 'stichprobe', 'hit' => true, 'reviewer' => 'Etienne']);

        lblAdmin()->patch("/beschriftung/entscheidungen/{$review->id}", ['category' => 'unsicher']);

        expect($review->refresh()->hit)->toBeNull()->and($ticket->refresh()->decision)->toBe('unsicher')->and($ticket->tagPending())->toBeTrue()->and($ticket->desiredTag())->toBeNull();
    });

    it('refuses to change a decision beyond the last two, of another admin or replaced by a later one', function () {
        $other = lblMake(['decision' => 'zahlung', 'decision_origin' => 'zweifel'], ['queue' => 'zweifel']);
        $foreign = $other->reviews()->create(['decision' => 'zahlung', 'origin' => 'zweifel', 'reviewer' => 'Kerstin']);
        [$first, $second] = [lblMake([], ['queue' => 'zweifel']), lblMake([], ['queue' => 'zweifel'])];

        lblAdmin()->post("/beschriftung/tickets/{$second->id}/entscheidung", ['category' => 'wismo']);
        lblAdmin()->post("/beschriftung/tickets/{$first->id}/entscheidung", ['category' => 'wismo']);
        lblAdmin()->post("/beschriftung/tickets/{$first->id}/entscheidung", ['category' => 'storno']);
        $oldest = LabelReview::query()->where('label_ticket_id', $second->id)->sole();
        $replaced = LabelReview::query()->where('label_ticket_id', $first->id)->where('decision', 'wismo')->sole();

        foreach ([$foreign, $replaced, $oldest] as $review) {
            lblAdmin()->patch("/beschriftung/entscheidungen/{$review->id}", ['category' => 'sonstiges'])->assertSessionHas('error');
        }

        expect(LabelReview::query()->where('decision', 'sonstiges')->count())->toBe(0)
            ->and($second->refresh()->decision)->toBe('wismo')->and($first->refresh()->decision)->toBe('storno')->and($other->refresh()->decision)->toBe('zahlung');
    });

    it('refuses an unknown category', function () {
        $ticket = lblMake();

        lblAdmin()->post("/beschriftung/tickets/{$ticket->id}/entscheidung", ['category' => 'werbung'])->assertSessionHasErrors('category');
        expect(LabelReview::query()->count())->toBe(0);
    });

    it('shows the newest ticket of a queue first', function () {
        lblMake(['zammad_created_at' => '2024-03-01 08:00:00'], ['queue' => 'zweifel']);
        $newest = lblMake(['zammad_created_at' => '2026-10-01 08:00:00'], ['queue' => 'zweifel']);
        lblMake(['zammad_created_at' => '2025-06-01 08:00:00'], ['queue' => 'zweifel']);

        expect(app(ReviewQueue::class)->next('zweifel')->id)->toBe($newest->id);
    });

    it('leaves test tickets out of the review once their proposed category reached the test target', function () {
        app(LabelingSettings::class)->save(['test_target' => 2]);
        lblMake(['split' => 'test', 'decision' => 'wismo', 'decision_origin' => 'test']);
        lblMake(['split' => 'test', 'decision' => 'wismo', 'decision_origin' => 'test']);
        lblMake(['split' => 'test', 'decision' => 'zahlung', 'decision_origin' => 'test'], ['category' => 'zahlung']);
        lblMake(['split' => 'test', 'decision' => 'unsicher', 'decision_origin' => 'test'], ['category' => 'zahlung']);
        lblMake(['split' => 'test']);
        $payment = lblMake(['split' => 'test'], ['category' => 'zahlung']);

        expect(app(ReviewQueue::class)->query('test')->pluck('id')->all())->toBe([$payment->id]);
        lblAdmin()->get('/beschriftung')->assertSeeInOrder(['Testmenge', '2 / 2']);
    });

    it('empties the sample queue once and keeps the decided samples', function () {
        $open = lblMake([], ['queue' => 'stichprobe']);
        $decided = lblMake(['decision' => 'wismo', 'decision_origin' => 'stichprobe'], ['queue' => 'stichprobe']);
        $doubtful = lblMake([], ['queue' => 'zweifel']);

        (require database_path('migrations/2026_11_09_140435_clear_labeling_sample_queue.php'))->up();

        expect($open->latestPrediction()->value('queue'))->toBeNull()
            ->and($decided->latestPrediction()->value('queue'))->toBe('stichprobe')
            ->and($doubtful->latestPrediction()->value('queue'))->toBe('zweifel')
            ->and(app(ReviewQueue::class)->query('stichprobe')->count())->toBe(0);
    });
});

describe('unsure', function () {
    it('takes a sample marked unsure out of its queue without tag, hit or example', function () {
        $ticket = lblMake(['text' => 'Unklarer Fall mit Paket'], ['queue' => 'stichprobe']);
        $queue = app(ReviewQueue::class);

        lblAdmin()->post("/beschriftung/tickets/{$ticket->id}/entscheidung", ['category' => 'unsicher', 'queue' => 'stichprobe'])->assertRedirect(route('labeling.review', ['warteschlange' => 'stichprobe']));

        expect(LabelReview::query()->sole())->origin->toBe('stichprobe')->hit->toBeNull()->reviewer->toBe('Etienne')
            ->and($ticket->refresh())->decision->toBe('unsicher')->decided_by->toBe('Etienne')->decided_at->not->toBeNull()
            ->and($ticket->desiredTag())->toBeNull()
            ->and($queue->query('stichprobe')->count())->toBe(0)
            ->and($queue->query('unsicher')->pluck('id')->all())->toBe([$ticket->id])
            ->and(app(LabelStats::class)->sample(LabelCategory::Wismo))->toBe([0, 0])
            ->and(app(ExampleSelector::class)->forPrompt(8)['text'])->not->toContain('Unklarer Fall');
    });

    it('counts tickets marked unsure for the category the model proposed', function () {
        lblMake(['decision' => 'unsicher', 'decision_origin' => 'zweifel'], ['category' => 'storno']);
        lblMake(['decision' => 'unsicher', 'decision_origin' => 'test', 'split' => 'test'], ['category' => 'storno']);
        lblMake(['decision' => 'unsicher', 'decision_origin' => 'zweifel']);

        $figures = app(LabelStats::class)->categories()->keyBy(fn (array $row): string => $row['category']->value);

        expect($figures['storno']['unsure'])->toBe(2)->and($figures['wismo']['unsure'])->toBe(1)->and($figures['storno']['corrected'])->toBe(0);
        lblAdmin()->get('/beschriftung')->assertSeeText('Unsicher');
    });

    it('removes the tag of a ticket marked unsure afterwards', function () {
        lblZammad(['train:wismo']);
        $ticket = lblMake(['decision' => 'unsicher', 'decision_origin' => 'korrektur', 'tag_value' => 'train:wismo', 'zammad_id' => 51234]);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'tags']);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'tags/remove') && $request['item'] === 'train:wismo');
        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), 'tags/add'));
        expect($ticket->refresh()->tag_value)->toBeNull();
    });

    it('writes no tag for a ticket marked unsure, neither in training nor in the test set', function () {
        lblZammad();
        lblMake(['decision' => 'unsicher', 'decision_origin' => 'zweifel', 'zammad_id' => 51234]);
        lblMake(['decision' => 'unsicher', 'decision_origin' => 'test', 'split' => 'test', 'zammad_id' => 51235]);

        expect(app(LabelTagging::class)->pending())->toBeEmpty();
    });

    it('neither reclassifies a ticket marked unsure nor brings it back as a deviation', function () {
        Queue::fake();
        $unsure = lblMake(['decision' => 'unsicher', 'decision_origin' => 'zweifel']);

        app(LabelRunner::class)->start('reclassify', 'Etienne');

        expect(Queue::pushed(ClassifyLabelTicket::class))->toBeEmpty();

        LabelAgent::fake([lblAnswer(['category' => 'reklamation'])]);
        expect(app(LabelClassifier::class)->classify($unsure)->queue)->toBeNull();
    });

    it('moves a ticket marked unsure again to the end of its queue and lets an admin decide later', function () {
        $first = lblMake(['decision' => 'unsicher', 'decision_origin' => 'zweifel', 'decided_at' => now()->subHours(2)]);
        $second = lblMake(['decision' => 'unsicher', 'decision_origin' => 'zweifel', 'decided_at' => now()->subHour()]);
        $queue = app(ReviewQueue::class);

        expect($queue->next('unsicher')->id)->toBe($first->id);

        lblAdmin()->post("/beschriftung/tickets/{$first->id}/entscheidung", ['category' => 'unsicher', 'queue' => 'unsicher']);
        expect($queue->next('unsicher')->id)->toBe($second->id);

        lblAdmin()->post("/beschriftung/tickets/{$second->id}/entscheidung", ['category' => 'wismo', 'queue' => 'unsicher']);
        expect($second->refresh())->decision->toBe('wismo')->decision_origin->toBe('korrektur')->and($second->desiredTag())->toBe('train:wismo')
            ->and($queue->query('unsicher')->pluck('id')->all())->toBe([$first->id]);
    });

    it('offers sonstiges with key 0 and unsure with key u on the review page', function () {
        lblMake([], ['queue' => 'zweifel']);

        lblAdmin()->get('/beschriftung/pruefen?warteschlange=zweifel')->assertOk()
            ->assertSee('data-key="0"', false)->assertSeeText('Sonstiges')
            ->assertSee('data-key="u"', false)->assertSeeText('Unsicher, nicht verwenden')
            ->assertDontSeeText('Keine Kategorie');
    });

    it('explains several concerns with the main concern', function () {
        lblMake([], ['queue' => 'zweifel', 'multiple_concerns' => true]);

        lblAdmin()->get('/beschriftung/pruefen?warteschlange=zweifel')->assertOk()
            ->assertSeeText('Mehrere Anliegen')->assertSeeText('Entscheidend ist das Hauptanliegen');
    });

    it('filters the ticket list by tickets marked unsure', function () {
        lblMake(['text' => 'Unsicherer Text', 'decision' => 'unsicher', 'decision_origin' => 'zweifel']);
        lblMake(['text' => 'Entschiedener Text', 'decision' => 'wismo', 'decision_origin' => 'zweifel']);
        lblMake(['text' => 'Offener Text']);

        lblAdmin()->get('/beschriftung/tickets?entscheidung=unsicher')->assertOk()
            ->assertSeeText('Unsicherer Text')->assertSeeText('Unsicher, nicht verwendet')->assertDontSeeText('Entschiedener Text')->assertDontSeeText('Offener Text');
        lblAdmin()->get('/beschriftung/tickets?entscheidung=offen')->assertSeeText('Offener Text')->assertDontSeeText('Unsicherer Text');
    });
});

describe('need', function () {
    function lblRelease(string $category): void
    {
        LabelRelease::query()->create(['category' => $category, 'released' => true, 'released_by' => 'Etienne', 'released_at' => now()]);
    }

    it('works out training, test tickets and samples each category still needs', function () {
        app(LabelingSettings::class)->save(['target' => 3, 'test_target' => 2, 'min_cases' => 2]);
        lblRelease('wismo');
        lblMake(['decision' => 'wismo', 'decision_origin' => 'zweifel']);
        lblMake();
        lblMake();
        lblMake(['split' => 'test', 'decision' => 'wismo', 'decision_origin' => 'test']);
        lblMake(['decision' => 'zahlung', 'decision_origin' => 'zweifel'], ['category' => 'zahlung']);
        lblMake([], ['category' => 'zahlung']);

        $needs = app(LabelStats::class)->needs();

        expect($needs['wismo'])->toBe(['training' => 0, 'test' => 1, 'samples' => 2, 'released' => true])
            ->and($needs['zahlung'])->toBe(['training' => 2, 'test' => 2, 'samples' => 2, 'released' => false]);
    });

    it('splits the queues into needed and optional by the proposed category', function () {
        app(LabelingSettings::class)->save(['target' => 1, 'test_target' => 1]);
        lblMake(['decision' => 'wismo', 'decision_origin' => 'zweifel']);
        lblMake(['decision' => 'spam', 'decision_origin' => 'zweifel'], ['category' => 'spam']);
        lblMake(['split' => 'test', 'decision' => 'wismo', 'decision_origin' => 'test']);
        $covered = lblMake([], ['queue' => 'zweifel']);
        $needed = lblMake([], ['category' => 'zahlung', 'queue' => 'zweifel']);
        $deviationNeeded = lblMake(['decision' => 'wismo', 'decision_origin' => 'zweifel'], ['category' => 'zahlung', 'queue' => 'abweichung']);
        $deviationCovered = lblMake(['decision' => 'wismo', 'decision_origin' => 'zweifel'], ['category' => 'spam', 'queue' => 'abweichung']);
        $testCovered = lblMake(['split' => 'test']);
        $testNeeded = lblMake(['split' => 'test'], ['category' => 'zahlung']);
        $queue = app(ReviewQueue::class);

        expect($queue->query('zweifel')->pluck('id')->all())->toBe([$needed->id])
            ->and($queue->query('zweifel', ReviewQueue::OPTIONAL)->pluck('id')->all())->toBe([$covered->id])
            ->and($queue->query('abweichung')->pluck('id')->all())->toBe([$deviationNeeded->id])
            ->and($queue->query('abweichung', ReviewQueue::OPTIONAL)->pluck('id')->all())->toBe([$deviationCovered->id])
            ->and($queue->query('test')->pluck('id')->all())->toBe([$testNeeded->id])
            ->and($queue->query('test', ReviewQueue::OPTIONAL)->pluck('id')->all())->toBe([$testCovered->id])
            ->and($queue->queues()[ReviewQueue::NEEDED])->not->toHaveKey('unsicher')
            ->and($queue->queues()[ReviewQueue::OPTIONAL])->toHaveKey('unsicher');
    });

    it('draws samples for a released category first, only from clear proposals of the current model', function () {
        app(LabelingSettings::class)->save(['target' => 5, 'min_cases' => 1]);
        lblRelease('spam');
        lblMake(['decision' => 'spam', 'decision_origin' => 'zweifel'], ['category' => 'spam']);
        lblMake([], ['category' => 'spam', 'model' => 'gpt-old']);
        $spam = lblMake([], ['category' => 'spam']);
        $wismo = lblMake();
        $queue = app(ReviewQueue::class);

        expect($queue->queues()[ReviewQueue::NEEDED]['stichprobe']['count'])->toBe(2)
            ->and($queue->next('stichprobe')->id)->toBe($spam->id)
            ->and($spam->latestPrediction()->value('queue'))->toBe('stichprobe')
            ->and($queue->next('stichprobe')->id)->toBe($spam->id);

        lblAdmin()->post("/beschriftung/tickets/{$spam->id}/entscheidung", ['category' => 'spam', 'queue' => 'stichprobe']);

        expect(LabelReview::query()->where('label_ticket_id', $spam->id)->sole())->origin->toBe('stichprobe')->hit->toBeTrue()
            ->and(app(LabelStats::class)->needs()['spam']['samples'])->toBe(0)
            ->and($queue->next('stichprobe')->id)->toBe($wismo->id);
    });

    it('moves drawn samples to the optional part once the category has enough', function () {
        app(LabelingSettings::class)->save(['target' => 1]);
        $sample = lblMake([], ['queue' => 'stichprobe']);
        $queue = app(ReviewQueue::class);

        expect($queue->query('stichprobe')->pluck('id')->all())->toBe([$sample->id]);

        lblMake(['decision' => 'wismo', 'decision_origin' => 'zweifel']);

        expect($queue->query('stichprobe')->count())->toBe(0)
            ->and($queue->query('stichprobe', ReviewQueue::OPTIONAL)->pluck('id')->all())->toBe([$sample->id])
            ->and($queue->next('stichprobe'))->toBeNull();
    });

    it('limits the review to one category and keeps part and category after a decision', function () {
        lblMake(['text' => 'Wo bleibt mein Paket?'], ['queue' => 'zweifel']);
        $payment = lblMake(['text' => 'Die Rechnung ist doppelt abgebucht.'], ['category' => 'zahlung', 'queue' => 'zweifel']);

        lblAdmin()->get('/beschriftung/pruefen?kategorie=zahlung')->assertOk()->assertSeeText('doppelt abgebucht')->assertDontSeeText('Wo bleibt mein Paket');

        lblAdmin()->post("/beschriftung/tickets/{$payment->id}/entscheidung", ['category' => 'zahlung', 'queue' => 'zweifel', 'bereich' => ReviewQueue::OPTIONAL, 'kategorie' => 'zahlung'])
            ->assertRedirect(route('labeling.review', ['warteschlange' => 'zweifel', 'bereich' => ReviewQueue::OPTIONAL, 'kategorie' => 'zahlung']));
    });

    it('shows on the category card what is still needed and links to its review', function () {
        app(LabelingSettings::class)->save(['target' => 2, 'test_target' => 1]);
        lblMake(['decision' => 'zahlung', 'decision_origin' => 'zweifel'], ['category' => 'zahlung']);

        lblAdmin()->get('/beschriftung')->assertOk()
            ->assertSeeText('Noch nötig')
            ->assertSeeInOrder(['Zahlung', '1 Trainingsticket · 1 Testticket', 'Prüfen'])
            ->assertSee(route('labeling.review', ['kategorie' => 'zahlung']), false);
    });
});

describe('release', function () {
    function lblSamples(string $category, int $hits, int $misses, ?string $at = null): void
    {
        foreach (range(1, $hits + $misses) as $i) {
            $ticket = lblMake(['decision' => $category, 'decision_origin' => 'stichprobe'], ['category' => $category, 'queue' => 'stichprobe']);
            $review = $ticket->reviews()->create(['label_prediction_id' => $ticket->latestPrediction->id, 'decision' => $category, 'origin' => 'stichprobe', 'hit' => $i <= $hits, 'reviewer' => 'Etienne']);

            if ($at !== null) {
                $review->forceFill(['created_at' => $at])->save();
                $ticket->latestPrediction->forceFill(['created_at' => $at])->save();
            }
        }
    }

    it('releases a category whose sample is large and good enough', function () {
        lblSamples('wismo', 49, 1);

        lblAdmin()->post('/beschriftung/kategorien/wismo/freigabe')->assertSessionHas('success');

        expect(LabelRelease::query()->where('category', 'wismo')->sole())->released->toBeTrue()->released_by->toBe('Etienne');
    });

    it('refuses the release when the sample is too small or too weak', function (int $hits, int $misses) {
        lblSamples('wismo', $hits, $misses);

        lblAdmin()->post('/beschriftung/kategorien/wismo/freigabe')->assertSessionHas('error');

        expect(LabelRelease::query()->count())->toBe(0);
    })->with(['too small' => [10, 0], 'too weak' => [40, 10]]);

    it('locks a released category when new samples are bad', function () {
        LabelRelease::query()->create(['category' => 'wismo', 'released' => true, 'released_by' => 'Etienne', 'released_at' => now()->subDay()]);
        lblSamples('wismo', 8, 11);
        $ticket = lblMake([], ['category' => 'wismo', 'queue' => 'stichprobe']);

        lblAdmin()->post("/beschriftung/tickets/{$ticket->id}/entscheidung", ['category' => 'reklamation']);

        $release = LabelRelease::query()->where('category', 'wismo')->sole();
        expect($release->released)->toBeFalse()->and($release->locked_at)->not->toBeNull();
        lblAdmin()->get('/beschriftung')->assertSeeText('Gesperrt');
    });

    it('counts only samples of the current model for the release', function () {
        lblSamples('wismo', 50, 0);
        config(['labeling.model' => 'gpt-neu']);

        lblAdmin()->post('/beschriftung/kategorien/wismo/freigabe')->assertSessionHas('error');
        lblAdmin()->get('/beschriftung')->assertSeeText('Noch keine Stichprobe mit gpt-neu geprüft');
    });

    it('locks a released category only by samples of the current model', function () {
        LabelRelease::query()->create(['category' => 'wismo', 'released' => true, 'released_by' => 'Etienne', 'released_at' => now()->subDay()]);
        lblSamples('wismo', 8, 11);
        config(['labeling.model' => 'gpt-neu']);
        $ticket = lblMake([], ['category' => 'wismo', 'queue' => 'stichprobe', 'model' => 'gpt-neu']);

        lblAdmin()->post("/beschriftung/tickets/{$ticket->id}/entscheidung", ['category' => 'reklamation']);

        expect(LabelRelease::query()->where('category', 'wismo')->sole()->released)->toBeTrue();
    });

    it('needs fresh samples after a lock', function () {
        lblSamples('wismo', 50, 0, now()->subDays(3)->toDateTimeString());
        LabelRelease::query()->create(['category' => 'wismo', 'released' => false, 'locked_at' => now()->subDay()]);

        lblAdmin()->post('/beschriftung/kategorien/wismo/freigabe')->assertSessionHas('error');
    });

    it('counts after a lock only samples of proposals made after it, even if reviewed later', function () {
        LabelRelease::query()->create(['category' => 'wismo', 'released' => false, 'locked_at' => now()->subHour()]);
        lblSamples('wismo', 0, 20);
        LabelPrediction::query()->update(['created_at' => now()->subDay()]);

        expect(app(LabelStats::class)->sample(LabelCategory::Wismo))->toBe([0, 0]);

        lblSamples('wismo', 50, 0);

        lblAdmin()->get('/beschriftung')->assertSeeText('Gesperrt')->assertSeeText('Die Stichproben nach der Sperre reichen wieder.');
        lblAdmin()->post('/beschriftung/kategorien/wismo/freigabe')->assertSessionHas('success');
    });

    it('answers 404 for an unknown category', function () {
        lblAdmin()->post('/beschriftung/kategorien/werbung/freigabe')->assertNotFound();
    });
});

describe('writing tags', function () {
    it('shows a dry run without writing anything', function () {
        lblZammad();
        lblMake(['decision' => 'wismo', 'decision_origin' => 'zweifel']);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'tags', 'dry_run' => '1'])->assertSessionHas('success');

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'tags/add'));
        expect(LabelRun::query()->sole())->planned->toBe(1)->state->toBe('done');
    });

    it('writes the tag of a confirmed ticket with the token of the admin', function () {
        lblZammad();
        $ticket = lblMake(['decision' => 'wismo', 'decision_origin' => 'zweifel', 'zammad_id' => 51234]);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'tags']);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'tags/add') && $request->hasHeader('Authorization', 'Token token=etienne-token') && $request['item'] === 'train:wismo' && $request['object'] === 'Ticket' && $request['o_id'] === 51234);
        expect($ticket->refresh())->tag_value->toBe('train:wismo')->and($ticket->tag_written_at)->not->toBeNull();
    });

    it('writes test: for tickets of the test set', function () {
        lblZammad();
        $ticket = lblMake(['decision' => 'reklamation', 'decision_origin' => 'test', 'split' => 'test', 'zammad_id' => 51234]);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'tags']);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'tags/add') && $request['item'] === 'test:reklamation');
        expect($ticket->refresh()->tag_value)->toBe('test:reklamation');
    });

    it('holds back the tag of a ticket while its deviation is open', function () {
        lblZammad();
        $ticket = lblMake(['decision' => 'storno', 'decision_origin' => 'zweifel', 'zammad_id' => 51234], ['category' => 'adressaenderung', 'queue' => 'abweichung']);

        expect(app(LabelTagging::class)->pending())->toBeEmpty();

        lblAdmin()->post("/beschriftung/tickets/{$ticket->id}/entscheidung", ['category' => 'adressaenderung']);

        expect(app(LabelTagging::class)->pending()->pluck('id')->all())->toBe([$ticket->id]);
    });

    it('writes train:sonstiges for the decision sonstiges', function () {
        lblZammad();
        $ticket = lblMake(['decision' => 'sonstiges', 'decision_origin' => 'zweifel', 'zammad_id' => 51234], ['category' => 'sonstiges']);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'tags']);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'tags/add') && $request['item'] === 'train:sonstiges');
        expect($ticket->refresh()->tag_value)->toBe('train:sonstiges');
    });

    it('replaces the old tag when a decision is corrected', function () {
        lblZammad(['train:wismo']);
        $ticket = lblMake(['decision' => 'reklamation', 'decision_origin' => 'korrektur', 'tag_value' => 'train:wismo', 'zammad_id' => 51234]);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'tags']);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'tags/remove') && $request['item'] === 'train:wismo');
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'tags/add') && $request['item'] === 'train:reklamation');
        expect($ticket->refresh()->tag_value)->toBe('train:reklamation');
    });

    it('does not overwrite a tag it did not set', function () {
        lblZammad(['train:produkt-frage']);
        $ticket = lblMake(['decision' => 'wismo', 'decision_origin' => 'zweifel', 'zammad_id' => 51234]);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'tags']);

        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), 'tags/add'));
        expect($ticket->refresh()->tag_problem)->toContain('Konflikt')->and($ticket->tag_value)->toBeNull()->and(LabelRun::query()->sole()->failed)->toBe(1);
    });

    it('tags clear proposals automatically only in a released category', function () {
        lblZammad();
        $released = lblMake(['zammad_id' => 51234]);
        $other = lblMake([], ['category' => 'reklamation']);
        LabelRelease::query()->create(['category' => 'wismo', 'released' => true, 'released_by' => 'Etienne', 'released_at' => now()]);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'tags']);

        expect($released->refresh())->decision->toBe('wismo')->decision_origin->toBe('automatisch')->tag_kind->toBe('automatisch')->tag_value->toBe('train:wismo')
            ->and($other->refresh()->decision)->toBeNull();
    });

    it('never tags automatically what is in a review queue, in the test set or already tagged', function () {
        lblZammad();
        LabelRelease::query()->create(['category' => 'wismo', 'released' => true, 'released_by' => 'Etienne', 'released_at' => now()]);
        $sample = lblMake([], ['queue' => 'stichprobe']);
        $doubt = lblMake([], ['queue' => 'zweifel']);
        $test = lblMake(['split' => 'test']);
        $tagged = lblMake(['existing_tag' => 'train:wismo']);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'tags']);

        foreach ([$sample, $doubt, $test, $tagged] as $ticket) {
            expect($ticket->refresh()->decision)->toBeNull();
        }
        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), 'tags/add'));
    });

    it('keeps the ticket pending when Zammad refuses the token', function () {
        lblZammad(override: ['zammad.test/api/v1/tags/add' => Http::response([], 401)]);
        $ticket = lblMake(['decision' => 'wismo', 'decision_origin' => 'zweifel', 'zammad_id' => 51234]);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'tags']);

        expect($ticket->refresh())->tag_value->toBeNull()->tag_problem->toBe('token_rejected')->and($ticket->tagPending())->toBeTrue();
    });
});

describe('runs and settings', function () {
    it('refuses to write tags without a Zammad token of the admin', function () {
        config(['staff.zammad_tokens' => ['Etienne' => null]]);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'tags'])->assertSessionHasErrors('kind');
        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'tags', 'dry_run' => '1'])->assertSessionHasNoErrors();
    });

    it('does not start the same run twice at once', function () {
        LabelRun::query()->create(['kind' => 'fetch', 'state' => 'running', 'admin' => 'Etienne']);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'fetch'])->assertSessionHasErrors('kind');
    });

    it('refuses an unknown kind of run', function () {
        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'delete-all'])->assertSessionHasErrors('kind');
    });

    it('cancels a running run', function () {
        $run = LabelRun::query()->create(['kind' => 'fetch', 'state' => 'running', 'admin' => 'Etienne']);

        lblAdmin()->post("/beschriftung/laeufe/{$run->id}/abbrechen")->assertSessionHas('success');

        expect($run->refresh()->state)->toBe('cancelled');
    });

    it('saves the settings and validates them', function () {
        $values = ['target' => 150, 'min_cases' => 40, 'min_rate' => 90, 'sample_share' => 20, 'confidence' => 0.8, 'examples' => 6, 'test_target' => 60, 'excluded_groups' => 'Spam'];

        lblAdmin()->patch('/beschriftung/einstellungen', $values)->assertSessionHas('success');

        expect(app(LabelingSettings::class)->all())->toMatchArray(['target' => 150, 'min_rate' => 90, 'confidence' => 0.8, 'test_target' => 60, 'excluded_groups' => 'Spam']);
        lblAdmin()->patch('/beschriftung/einstellungen', [...$values, 'min_rate' => 10, 'confidence' => 3])->assertSessionHasErrors(['min_rate', 'confidence']);
    });
});

describe('retention', function () {
    it('empties the texts of tickets created more than 36 months ago and keeps the decisions', function () {
        $old = lblMake(['decision' => 'wismo', 'decision_origin' => 'zweifel', 'zammad_created_at' => now()->subMonths(37), 'subject' => 'Paket', 'sender' => 'kunde@example.com'], ['evidence' => 'Wo bleibt mein Paket?']);
        $recent = lblMake(['zammad_created_at' => now()->subMonths(35), 'last_activity_at' => now()->subMonths(30)], ['evidence' => 'Wo bleibt mein Paket?']);

        $this->artisan('labeling:purge')->assertSuccessful();

        expect($old->refresh()->text)->toBeNull()->and($old->subject)->toBeNull()->and($old->sender)->toBeNull()
            ->and($old->decision)->toBe('wismo')
            ->and($old->latestPrediction->evidence)->toBeNull()->and($old->latestPrediction->reason)->toBeNull()->and($old->latestPrediction->category)->toBe('wismo')
            ->and($recent->refresh()->text)->not->toBeNull()->and($recent->latestPrediction->evidence)->toBe('Wo bleibt mein Paket?');
    });

    it('stores the message text encrypted', function () {
        $ticket = lblMake(['text' => 'Sehr geheimer Kundentext']);

        expect(DB::table('labeling_tickets')->where('id', $ticket->id)->value('text'))->not->toContain('geheimer');
    });
});

describe('figures', function () {
    it('counts confirmed, corrected, automatic and open tickets per category', function () {
        lblMake(['decision' => 'wismo', 'decision_origin' => 'zweifel']);
        lblMake(['decision' => 'wismo', 'decision_origin' => 'korrektur'], ['category' => 'reklamation']);
        lblMake(['decision' => 'wismo', 'decision_origin' => 'automatisch']);
        lblMake(['decision' => 'wismo', 'decision_origin' => 'vorhanden']);
        lblMake();

        $wismo = app(LabelStats::class)->categories()->firstWhere('category', LabelCategory::Wismo);

        expect($wismo)->toMatchArray(['confirmed' => 1, 'corrected' => 1, 'automatic' => 1, 'open' => 1, 'released' => false, 'locked' => false]);
    });

    it('measures how many references the model recognized', function () {
        lblMake(['decision' => 'wismo', 'decision_origin' => 'vorhanden']);
        lblMake(['decision' => 'wismo', 'decision_origin' => 'vorhanden'], ['category' => 'reklamation']);

        expect(app(LabelStats::class)->references())->toBe(['total' => 2, 'hits' => 1]);
        lblAdmin()->get('/beschriftung')->assertSeeText('1 von 2 Tickets');
    });
});
