<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\ProductQuestionLog;
use App\Models\KnowledgeGap;
use App\Models\ProductQuestion;
use App\Models\ProductQuestionState;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Prompts\AgentPrompt;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
        'cache.default' => 'array',
        'staff.admins' => ['Etienne'],
    ]);

    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);
    fakeZammad(extra: ['zammad.test/api/v1/ticket_articles/by_ticket/*' => fn () => Http::response([zammadArticle(['id' => 1, 'body' => '<p>Hallo</p>'])])]);
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function detailAnswer(array $detailQuestions, array $gaps = []): array
{
    return [
        'summary' => ['incident' => 'Frage', 'customer_wish' => 'Auskunft'], 'category' => 'product-question', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Antworten.', 'actions' => [], 'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'Zu dieser Frage liegt keine Produktangabe vor.', 'knowledge_gaps' => $gaps, 'knowledge_ids' => [], 'confidence' => ['level' => 'MITTEL', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Da sind wir uns nicht sicher.'],
        'detail_questions' => $detailQuestions,
    ];
}

function runDetail(array $answer, string $number = '2137942', array $fields = [])
{
    CaseAgent::fake([$answer]);

    return test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => $number]), withProductAnswer([
        'variante' => 'verlauf', 'kundengruppe' => 'reseller', 'produkte' => [], 'produkt_ohne_wissen' => 'Essbares Foto', ...$fields,
    ]));
}

function questionAt(string $topic, string $when, array $attributes = []): ProductQuestion
{
    return ProductQuestion::factory()->create(['topic' => $topic, 'created_at' => $when, 'updated_at' => $when, ...$attributes]);
}

describe('recording', function () {
    test('a detail question is kept silently: an entry with keyword and wording, no gap, no hint in the result', function () {
        $location = runDetail(detailAnswer([['product' => null, 'topic' => ' Lagerung  Tiefkühl ', 'question' => 'Kundin fragt, ob das Foto eingefroren werden kann.']]), fields: ['produkt_ohne_wissen' => '', 'kein_produktbezug' => '1'])->assertRedirect()->headers->get('Location');

        $question = ProductQuestion::query()->sole();
        expect($question->topic)->toBe('lagerung tiefkühl')->and($question->question)->toBe('Kundin fragt, ob das Foto eingefroren werden kann.')
            ->and($question->ticket_number)->toBe('2137942')->and($question->product_key)->toBe('')->and($question->test_run)->toBeFalse()
            ->and($question->getRawOriginal('question'))->not->toContain('eingefroren')
            ->and(KnowledgeGap::query()->count())->toBe(0);

        $this->withCookie('staff_name', 'Nele')->get($location)->assertOk()->assertDontSeeText('tiefkühl');
    });

    test('the same question again for the ticket refreshes the wording and does not add a row', function () {
        runDetail(detailAnswer([['product' => null, 'topic' => 'vegan', 'question' => 'erste Fassung']]));
        runDetail(detailAnswer([['product' => null, 'topic' => 'Vegan', 'question' => 'zweite Fassung']]));

        expect(ProductQuestion::query()->sole()->question)->toBe('zweite Fassung');
    });

    test('contact placeholders leave the wording, incomplete and duplicate entries are dropped, at most five', function () {
        runDetail(detailAnswer([
            ['product' => null, 'topic' => 'vegan', 'question' => 'Frau [NAME_1] fragt, ob es vegan ist [E-MAIL_1].'],
            ['product' => null, 'topic' => 'vegan', 'question' => 'doppelt'],
            ['product' => null, 'topic' => '', 'question' => 'ohne Stichwort'],
            ['product' => null, 'topic' => 'a', 'question' => ''],
            ...array_map(fn (int $i): array => ['product' => null, 'topic' => "thema {$i}", 'question' => "Frage {$i}"], range(1, 8)),
        ]));

        expect(ProductQuestion::query()->count())->toBe(5)->and(ProductQuestion::query()->where('topic', 'vegan')->sole()->question)->toBe('Frau fragt, ob es vegan ist.');
    });

    test('a question that is also reported as a gap is a gap and not counted', function () {
        runDetail(detailAnswer([['product' => null, 'topic' => 'Rückgabe', 'question' => 'Wie lange darf ich zurückgeben?']], [['topic' => 'rückgabe', 'question' => 'Welche Frist gilt?']]));

        expect(ProductQuestion::query()->count())->toBe(0)->and(KnowledgeGap::query()->count())->toBe(1);
    });

    test('a test run is recorded as such and an answer without the field is fine', function () {
        $answer = detailAnswer([['product' => null, 'topic' => 'vegan', 'question' => 'Ist es vegan?']]);
        runDetail($answer)->assertRedirect();
        ProductQuestion::query()->update(['test_run' => true]);

        unset($answer['detail_questions']);
        runDetail($answer, fields: [])->assertRedirect();

        expect(ProductQuestion::query()->where('test_run', true)->count())->toBe(1)->and(ProductQuestion::query()->where('test_run', false)->count())->toBe(0);
    });

    test('the keywords used so far for the product go to the model as a hint', function () {
        ProductQuestion::factory()->create(['product_key' => 'x:essbares foto', 'topic' => 'vegan']);
        ProductQuestion::factory()->create(['product_key' => 'x:essbares foto', 'topic' => 'vegan']);
        ProductQuestion::factory()->create(['product_key' => 'x:essbares foto', 'topic' => 'haltbarkeit']);
        ProductQuestion::factory()->create(['product_key' => 'pf:9', 'topic' => 'fremd']);

        runDetail(detailAnswer([]));

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Bisher verwendete Stichwörter für Detailfragen') && $prompt->contains('Essbares Foto: vegan, haltbarkeit') && ! $prompt->contains('fremd'));
    });
});

describe('the topics', function () {
    test('a topic appears from three tickets in three months, sorted by the recent count and then the total', function () {
        foreach (range(1, 3) as $i) {
            questionAt('vegan', now()->subDays($i)->toDateTimeString());
        }
        foreach (range(1, 4) as $i) {
            questionAt('haltbarkeit', now()->subDays($i)->toDateTimeString());
        }
        questionAt('haltbarkeit', now()->subMonths(8)->toDateTimeString());
        questionAt('faserrichtung', now()->subDays(2)->toDateTimeString());
        questionAt('alt', now()->subMonths(5)->toDateTimeString());
        questionAt('alt', now()->subMonths(5)->toDateTimeString());
        questionAt('alt', now()->subMonths(5)->toDateTimeString());

        $log = app(ProductQuestionLog::class);
        $topics = $log->topics();

        expect(array_column($topics, 'topic'))->toBe(['haltbarkeit', 'vegan'])
            ->and($topics[0]['recent'])->toBe(4)->and($topics[0]['total'])->toBe(5)
            ->and(array_column($log->topics('open', null, true), 'topic'))->toContain('faserrichtung')->toContain('alt');
    });

    test('the count goes on above the threshold and test runs and other products do not count', function () {
        foreach (range(1, 6) as $i) {
            questionAt('vegan', now()->subDays($i)->toDateTimeString());
        }
        questionAt('vegan', now()->toDateTimeString(), ['test_run' => true]);
        questionAt('vegan', now()->toDateTimeString(), ['product_key' => 'pf:77', 'product_name' => 'Anderes']);

        $topics = app(ProductQuestionLog::class)->topics('open', 'pf:2001');

        expect($topics)->toHaveCount(1)->and($topics[0]['recent'])->toBe(6);
    });

    test('done starts the count again, ignored hides but keeps counting, open removes the mark', function () {
        foreach (range(1, 3) as $i) {
            questionAt('vegan', now()->subDays($i + 5)->toDateTimeString());
        }
        $log = app(ProductQuestionLog::class);

        $log->mark('pf:2001', 'vegan', 'done', 'Etienne');
        expect($log->topics())->toBe([])->and(array_column($log->topics('done'), 'total'))->toBe([3]);

        foreach (range(1, 3) as $i) {
            questionAt('vegan', now()->addMinutes($i)->toDateTimeString());
        }
        $reopened = $log->topics();
        expect($reopened)->toHaveCount(1)->and($reopened[0]['total'])->toBe(3)->and($reopened[0]['since'])->not->toBeNull();

        $log->mark('pf:2001', 'vegan', 'ignored', 'Etienne');
        expect($log->topics())->toBe([])->and($log->topics('ignored')[0]['total'])->toBe(6);

        $log->mark('pf:2001', 'vegan', 'open', 'Etienne');
        expect(ProductQuestionState::query()->count())->toBe(0)->and($log->topics()[0]['total'])->toBe(6);
    });

    test('topics done for more than twelve months are deleted with their questions, newer ones stay', function () {
        $old = questionAt('alt', now()->subMonths(14)->toDateTimeString());
        ProductQuestionState::factory()->create(['topic' => 'alt', 'status' => 'done', 'changed_at' => now()->subMonths(13)]);
        $after = questionAt('alt', now()->subMonths(2)->toDateTimeString());
        questionAt('vegan', now()->subMonths(20)->toDateTimeString());
        ProductQuestionState::factory()->create(['topic' => 'vegan', 'status' => 'ignored', 'changed_at' => now()->subMonths(19)]);
        ProductQuestionState::factory()->create(['topic' => 'neu', 'status' => 'done', 'changed_at' => now()->subMonths(2)]);

        expect(app(ProductQuestionLog::class)->purge())->toBe(1)
            ->and(ProductQuestion::query()->whereKey($old->id)->exists())->toBeFalse()->and(ProductQuestion::query()->whereKey($after->id)->exists())->toBeTrue()
            ->and(ProductQuestion::query()->where('topic', 'vegan')->count())->toBe(1)
            ->and(ProductQuestionState::query()->pluck('topic')->sort()->values()->all())->toBe(['neu', 'vegan']);
    });
});

describe('the page', function () {
    test('only admins see it and work on it', function () {
        $this->withCookie('staff_name', 'Nele')->get(route('product-questions.index'))->assertForbidden();
        $this->get(route('product-questions.index'))->assertForbidden();
        $this->withCookie('staff_name', 'Nele')->patch(route('product-questions.update'), ['product_key' => 'pf:2001', 'topic' => 'vegan', 'status' => 'done'])->assertForbidden();

        $this->withCookie('staff_name', 'Etienne')->get(route('product-questions.index'))->assertOk();
        $this->withCookie('staff_name', 'Etienne')->get(route('tickets.analyze'))->assertSeeText('Häufige Produktfragen');
        $this->withCookie('staff_name', 'Nele')->get(route('tickets.analyze'))->assertDontSeeText('Häufige Produktfragen');
    });

    test('the list shows product, counts, wordings with links and the actions', function () {
        foreach (range(1, 3) as $i) {
            questionAt('vegan', now()->subDays($i)->toDateTimeString(), ['question' => "Frage Nummer {$i} zu vegan", 'ticket_number' => "213900{$i}"]);
        }
        questionAt('selten', now()->subDays(1)->toDateTimeString());

        $page = $this->withCookie('staff_name', 'Etienne')->get(route('product-questions.index'))->assertOk()
            ->assertSeeText('vegan')->assertSeeText('Demo Tortenaufleger')->assertSeeText('3 Anfragen in 3 Monaten')->assertSeeText('Frage Nummer 2 zu vegan')
            ->assertSeeText('Erledigt: in ProductForge ergänzt')->assertSeeText('Für den KI-Chat kopieren')->assertDontSeeText('selten');

        expect($page->getContent())->toContain(route('tickets.show', ['number' => '2139002']));

        $this->withCookie('staff_name', 'Etienne')->get(route('product-questions.index', ['alle' => 1]))->assertSeeText('selten');
    });

    test('the empty page says what is missing', function () {
        $this->withCookie('staff_name', 'Etienne')->get(route('product-questions.index'))->assertOk()->assertSeeText('Noch kein Thema hat 3 Tickets');
    });

    test('marking a topic works and the chat text names product, topic and wordings', function () {
        foreach (range(1, 3) as $i) {
            questionAt('vegan', now()->subDays($i)->toDateTimeString(), ['question' => "Wording {$i}"]);
        }

        $this->withCookie('staff_name', 'Etienne')->patch(route('product-questions.update'), ['product_key' => 'pf:2001', 'topic' => 'vegan', 'status' => 'done'])
            ->assertRedirect(route('product-questions.index'));
        expect(ProductQuestionState::query()->sole()->changed_by)->toBe('Etienne');

        $this->withCookie('staff_name', 'Etienne')->get(route('product-questions.index'))->assertDontSeeText('Wording 1');
        $this->withCookie('staff_name', 'Etienne')->get(route('product-questions.index', ['status' => 'done']))->assertSeeText('vegan')->assertSeeText('Wieder öffnen');

        $log = app(ProductQuestionLog::class);
        $topic = $log->topics('done')[0];
        $text = $log->chatText($topic, ProductQuestion::query()->get());
        expect($text)->toContain('Produkt: Demo Tortenaufleger')->toContain('Thema: vegan')->toContain('- Wording 2');
    });

    test('an invalid status is refused', function () {
        $this->withCookie('staff_name', 'Etienne')->patch(route('product-questions.update'), ['product_key' => 'pf:2001', 'topic' => 'vegan', 'status' => 'xyz'])->assertSessionHasErrors('status');
    });
});
