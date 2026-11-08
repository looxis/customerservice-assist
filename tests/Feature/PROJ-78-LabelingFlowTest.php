<?php

use App\Jobs\ClassifyLabelTicket;
use App\Jobs\Middleware\SkipInactiveLabelRun;
use App\Labeling\ExampleSelector;
use App\Labeling\LabelAgent;
use App\Labeling\LabelCategory;
use App\Labeling\LabelClassifier;
use App\Labeling\LabelingSettings;
use App\Labeling\LabelRunner;
use App\Labeling\LabelStats;
use App\Labeling\LabelTagging;
use App\Labeling\ReviewQueue;
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
            'target' => 100, 'min_cases' => 50, 'min_rate' => 95, 'sample_share' => 15, 'confidence' => 0.85, 'examples' => 8,
            'excluded_senders' => "noreply@amazon.com\n@example.invalid",
        ])->assertSessionHas('success');

        expect($amazon->refresh()->state)->toBe(LabelTicket::EXCLUDED)->and($amazon->text)->toBeNull()
            ->and($customer->refresh()->state)->toBe('classified')
            ->and($tagged->refresh()->state)->toBe('classified');
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

    it('classifies only as many tickets as the limit says', function () {
        Queue::fake();
        lblMake(['state' => 'captured', 'split' => 'test']);
        $first = lblMake(['state' => 'captured', 'split' => 'training']);
        lblMake(['state' => 'captured', 'split' => 'training']);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'classify', 'limit' => '1'])->assertSessionHasNoErrors();

        $order = Queue::pushed(ClassifyLabelTicket::class)->map(fn (ClassifyLabelTicket $job): int => $job->labelTicketId)->all();
        expect($order)->toBe([$first->id])->and(LabelRun::query()->sole()->total)->toBe(1);
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

        expect($prediction->category)->toBe('wismo')->and($prediction->prompt_version)->toStartWith('labeling-4')->and($prediction->model)->not->toBe('')
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
        'no category' => [['category' => 'keine'], 'Hallo', 'training', 'konfidenz'],
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
        'none now payment' => ['training', 'keine', ['category' => 'zahlung']],
        'payment now none' => ['training', 'zahlung', ['category' => 'keine']],
        'test set' => ['test', 'keine', ['category' => 'zahlung']],
    ]);

    it('leaves a decided ticket out of every queue when the new proposal agrees', function () {
        LabelAgent::fake([lblAnswer(['category' => 'keine', 'unclear' => true])]);
        $ticket = lblMake(['decision' => 'keine', 'decision_origin' => 'zweifel']);

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
        expect($ids)->toBe([$open->id, $decided->id, $failed->id]);
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

        lblAdmin()->post("/beschriftung/tickets/{$ticket->id}/entscheidung", ['category' => 'keine']);

        expect($ticket->refresh()->decision)->toBe('keine')->and($ticket->decision_origin)->toBe('test')->and($ticket->desiredTag())->toBeNull();
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

    it('refuses an unknown category', function () {
        $ticket = lblMake();

        lblAdmin()->post("/beschriftung/tickets/{$ticket->id}/entscheidung", ['category' => 'werbung'])->assertSessionHasErrors('category');
        expect(LabelReview::query()->count())->toBe(0);
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

    it('needs fresh samples after a lock', function () {
        lblSamples('wismo', 50, 0, now()->subDays(3)->toDateTimeString());
        LabelRelease::query()->create(['category' => 'wismo', 'released' => false, 'locked_at' => now()->subDay()]);

        lblAdmin()->post('/beschriftung/kategorien/wismo/freigabe')->assertSessionHas('error');
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

    it('writes no tag for the decision none', function () {
        lblZammad();
        lblMake(['decision' => 'keine', 'decision_origin' => 'zweifel', 'zammad_id' => 51234]);

        lblAdmin()->post('/beschriftung/laeufe', ['kind' => 'tags']);

        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), 'tags/add'));
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
        $values = ['target' => 150, 'min_cases' => 40, 'min_rate' => 90, 'sample_share' => 20, 'confidence' => 0.8, 'examples' => 6, 'excluded_groups' => 'Spam'];

        lblAdmin()->patch('/beschriftung/einstellungen', $values)->assertSessionHas('success');

        expect(app(LabelingSettings::class)->all())->toMatchArray(['target' => 150, 'min_rate' => 90, 'confidence' => 0.8, 'excluded_groups' => 'Spam']);
        lblAdmin()->patch('/beschriftung/einstellungen', [...$values, 'min_rate' => 10, 'confidence' => 3])->assertSessionHasErrors(['min_rate', 'confidence']);
    });
});

describe('retention', function () {
    it('empties old texts and keeps the decisions', function () {
        $old = lblMake(['decision' => 'wismo', 'decision_origin' => 'zweifel', 'last_activity_at' => now()->subMonths(7)]);
        $recent = lblMake(['last_activity_at' => now()->subMonths(2)]);

        $this->artisan('labeling:purge')->assertSuccessful();

        expect($old->refresh()->text)->toBeNull()->and($old->decision)->toBe('wismo')->and($recent->refresh()->text)->not->toBeNull();
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
