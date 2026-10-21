<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\ProductQuestionLog;
use App\Analysis\QuestionNumber;
use App\Models\ProductQuestion;
use App\Models\ProductQuestionState;
use Illuminate\Support\Facades\Http;

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
    fakeEocsItems();
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
    fakeForge(byId: [2002 => forgeFixture('variant-with-origin'), 3001 => forgeFixture('empty-product')], search: forgeFixture('search'));
});

afterEach(fn () => cleanUpKnowledgeBases());

function detailQaAnswer(array $detailQuestions): array
{
    return [
        'summary' => ['incident' => 'Frage', 'customer_wish' => 'Auskunft'], 'category' => 'product-question', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Antworten.', 'actions' => [], 'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'x', 'knowledge_gaps' => [], 'knowledge_ids' => [], 'confidence' => ['level' => 'MITTEL', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag'],
        'detail_questions' => $detailQuestions,
    ];
}

function detailQaRun(array $answer, array $forgeIds)
{
    CaseAgent::fake([$answer]);

    return test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), withProductAnswer([
        'variante' => 'verlauf', 'kundengruppe' => 'private-looxis-de', 'forge_produkte' => $forgeIds,
    ]));
}

describe('products of the catalog', function () {
    test('a question about a variant counts at its master', function () {
        detailQaRun(detailQaAnswer([['product' => 'Demo Tortenaufleger eckig', 'topic' => 'vegan', 'question' => 'Ist das vegan?']]), [2002])->assertRedirect();

        $question = ProductQuestion::query()->sole();
        expect($question->product_key)->toBe('pf:2001')->and($question->product_name)->toBe('Demo Tortenaufleger eckig');
    });

    test('with several products the model picks one by its name, an unknown name means no product', function () {
        detailQaRun(detailQaAnswer([
            ['product' => 'Demo Artikel leer', 'topic' => 'material', 'question' => 'Woraus besteht der Artikel?'],
            ['product' => 'Erfundenes Produkt', 'topic' => 'herkunft', 'question' => 'Woher kommt es?'],
        ]), [2002, 3001])->assertRedirect();

        expect(ProductQuestion::query()->where('topic', 'material')->sole()->product_key)->toBe('pf:3001')
            ->and(ProductQuestion::query()->where('topic', 'herkunft')->sole()->product_key)->toBe('');
    });

    test('with a single product the question belongs to it whatever the model names', function () {
        detailQaRun(detailQaAnswer([['product' => null, 'topic' => 'vegan', 'question' => 'Ist das vegan?']]), [3001])->assertRedirect();

        expect(ProductQuestion::query()->sole()->product_key)->toBe('pf:3001');
    });
});

describe('questions without a ticket', function () {
    test('a detail question of a question counts like a ticket and links to the question', function () {
        CaseAgent::fake([detailQaAnswer([['product' => null, 'topic' => 'vegan', 'question' => 'Ist das vegan?']])]);

        $this->withCookie('staff_name', 'Nele')->post(route('questions.store'), withProductAnswer([
            'text' => 'Ist das essbare Foto vegan?', 'kundengruppe' => 'private-looxis-de', 'produkte' => [], 'kein_produktbezug' => '1',
        ]))->assertRedirect();

        $question = ProductQuestion::query()->sole();
        expect(QuestionNumber::isQuestion($question->ticket_number))->toBeTrue();

        foreach (range(1, 2) as $i) {
            ProductQuestion::factory()->create(['topic' => 'vegan', 'product_key' => '', 'product_name' => null]);
        }

        $this->withCookie('staff_name', 'Etienne')->get(route('product-questions.index'))->assertOk()->assertSeeText('3 Anfragen in 3 Monaten')
            ->assertSee(route('questions.show', ['number' => $question->ticket_number]), false);
    });
});

describe('the page', function () {
    test('texts from customers are escaped', function () {
        foreach (range(1, 3) as $i) {
            ProductQuestion::factory()->create(['topic' => 'vegan', 'question' => '<script>alert(1)</script> Frage '.$i, 'product_name' => '<b>Produkt</b>']);
        }

        $html = $this->withCookie('staff_name', 'Etienne')->get(route('product-questions.index'))->assertOk()->getContent();

        expect($html)->not->toContain('<script>alert(1)</script>')->not->toContain('<b>Produkt</b>')->toContain('&lt;script&gt;alert(1)&lt;/script&gt;');
    });

    test('questions without a product can be filtered and marked, and the filters survive the redirect', function () {
        foreach (range(1, 3) as $i) {
            ProductQuestion::factory()->create(['topic' => 'allgemein', 'product_key' => '', 'product_name' => null]);
            ProductQuestion::factory()->create(['topic' => 'haltbarkeit']);
        }

        $page = $this->withCookie('staff_name', 'Etienne')->get(route('product-questions.index', ['produkt' => 'ohne']))->assertOk()->assertSeeText('allgemein')->assertSeeText('ohne Produkt')->assertDontSeeText('haltbarkeit');
        expect($page->getContent())->toContain('value="ohne"');

        $this->withCookie('staff_name', 'Etienne')->patch(route('product-questions.update'), ['product_key' => '', 'topic' => 'allgemein', 'status' => 'ignored', 'back_status' => 'open', 'back_product' => 'ohne'])
            ->assertRedirect(route('product-questions.index', ['status' => 'open', 'produkt' => 'ohne']));

        expect(ProductQuestionState::query()->sole()->product_key)->toBe('')
            ->and(app(ProductQuestionLog::class)->topics('open', ''))->toBe([])
            ->and(array_column(app(ProductQuestionLog::class)->topics('ignored'), 'topic'))->toBe(['allgemein']);
    });

    test('an unknown status or product filter falls back to the open list', function () {
        $this->withCookie('staff_name', 'Etienne')->get(route('product-questions.index', ['status' => 'zzz', 'produkt' => 'gibt-es-nicht']))->assertOk()->assertSeeText('Noch kein Thema');
    });

    test('a topic marked twice keeps one mark, the last status wins', function () {
        $log = app(ProductQuestionLog::class);
        $log->mark('pf:1', 'vegan', 'done', 'Etienne');
        $log->mark('pf:1', 'vegan', 'ignored', 'Kerstin');

        $state = ProductQuestionState::query()->sole();
        expect($state->status)->toBe('ignored')->and($state->changed_by)->toBe('Kerstin');
    });
});

describe('the log', function () {
    test('keywords are lowercase, trimmed, shortened to 80 characters', function () {
        expect(ProductQuestionLog::normalize("  Lagerung \n Tiefkühl  "))->toBe('lagerung tiefkühl')
            ->and(mb_strlen(ProductQuestionLog::normalize(str_repeat('ä', 200))))->toBe(80);
    });

    test('a ticket counts once per topic even when asked in two product spellings of the same master', function () {
        ProductQuestion::factory()->create(['ticket_number' => '2139001', 'topic' => 'vegan']);
        ProductQuestion::factory()->create(['ticket_number' => '2139002', 'topic' => 'vegan']);
        ProductQuestion::factory()->create(['ticket_number' => '2139003', 'topic' => 'vegan', 'product_name' => 'Demo Tortenaufleger (neu benannt)']);

        $topics = app(ProductQuestionLog::class)->topics();

        expect($topics)->toHaveCount(1)->and($topics[0]['recent'])->toBe(3)->and($topics[0]['product_name'])->toBe('Demo Tortenaufleger (neu benannt)');
    });

    test('known keywords are limited per product', function () {
        config(['analysis.product_questions.known_topics' => 2]);
        foreach (['a', 'b', 'c'] as $topic) {
            ProductQuestion::factory()->create(['topic' => $topic]);
        }

        expect(app(ProductQuestionLog::class)->knownTopics(['pf:2001'])['pf:2001'])->toHaveCount(2)->and(app(ProductQuestionLog::class)->knownTopics([]))->toBe([]);
    });
});
