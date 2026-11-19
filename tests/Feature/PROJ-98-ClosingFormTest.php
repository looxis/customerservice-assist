<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\ClosingDecision;
use App\Analysis\GuidedForms;
use App\Models\Analysis;
use App\Models\LabelTicket;
use App\Models\ReturnLabel;
use Laravel\Ai\Prompts\AgentPrompt;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
    ]);

    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);
});

afterEach(fn () => cleanUpKnowledgeBases());

/**
 * A thread with our first answer and the given number of customer replies after it.
 */
function closingThread(int $replies): void
{
    $articles = [
        zammadArticle(['id' => 1, 'body' => '<p>Die Tasse ist verpixelt.</p>', 'message_id' => '<m1@example.org>']),
        zammadArticle(['id' => 2, 'sender' => 'Agent', 'from' => 'LOOXIS <service@looxis.de>', 'body' => '<p>Wir haben geprüft.</p>', 'created_at' => '2026-10-01T09:00:00.000Z']),
    ];

    for ($i = 0; $i < $replies; $i++) {
        $articles[] = zammadArticle(['id' => 3 + $i, 'body' => '<p>Ich sehe das anders, Nummer '.$i.'.</p>', 'created_at' => '2026-10-0'.(2 + $i).'T09:00:00.000Z', 'message_id' => '<r'.$i.'@example.org>']);
    }

    fakeZammad($articles);
}

function closingCategory(string $category = 'reklamation'): void
{
    LabelTicket::query()->create(['zammad_id' => 51234, 'number' => '2137942', 'text' => 'Hallo', 'state' => 'classified', 'split' => 'training', 'decision' => $category, 'decision_origin' => 'zweifel']);
}

function closingAnswer(): array
{
    return [
        'summary' => ['incident' => 'Diskussion', 'customer_wish' => 'Neuproduktion'],
        'category' => 'complaint', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Antwort senden und das Ticket schließen.', 'actions' => [],
        'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'Formular.', 'knowledge_gaps' => [],
        'knowledge_ids' => [], 'confidence' => ['level' => 'HOCH', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [],
        'reply' => ['language' => 'Deutsch', 'text' => 'Hallo, wir haben dem nichts hinzuzufügen.'],
    ];
}

function closingRun(array $fields = [])
{
    return test()->withCookie('staff_name', 'Nele')->from('/tickets/2137942')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kundengruppe' => 'reseller', 'verkaufskanal' => 'fachhaendler', 'variante' => 'verlauf', 'formular' => 'abschluss', ...$fields]);
}

function closingPage()
{
    return test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942');
}

describe('when the form is offered', function () {
    test('never preselected by a category', function () {
        expect(GuidedForms::defaultFor('reklamation'))->toBe('reklamation')
            ->and(GuidedForms::defaultFor('wismo'))->toBe('frei')
            ->and(GuidedForms::guidedLabel('abschluss'))->toBe('Diskussion beenden');
    });

    test('offered for a complaint after three customer replies', function () {
        closingThread(3);
        closingCategory();

        closingPage()->assertOk()->assertSee('name="formularwahl" value="abschluss"', false)->assertSee('Bleibt unser bisheriges Angebot bestehen?')->assertSee("mode: 'reklamation',", false);
    });

    test('offered for WISMO, address change and cancellation as well', function (string $category) {
        closingThread(3);
        closingCategory($category);

        closingPage()->assertOk()->assertSee('name="formularwahl" value="abschluss"', false);
    })->with(['wismo', 'adressaenderung', 'storno']);

    test('not offered after only two customer replies', function () {
        closingThread(2);
        closingCategory();

        closingPage()->assertOk()->assertDontSee('name="formularwahl" value="abschluss"', false)->assertDontSee('Bleibt unser bisheriges Angebot bestehen?');
    });

    test('not offered for another category or an unclassified ticket', function () {
        closingThread(4);
        closingCategory('produkt-frage');

        closingPage()->assertOk()->assertDontSee('name="formularwahl" value="abschluss"', false);

        LabelTicket::query()->delete();
        closingPage()->assertOk()->assertDontSee('name="formularwahl" value="abschluss"', false);
    });

    test('customer messages before our first answer and automatic messages do not count', function () {
        fakeZammad([
            zammadArticle(['id' => 1]),
            zammadArticle(['id' => 2, 'created_at' => '2026-10-01T08:00:00.000Z']),
            zammadArticle(['id' => 3, 'sender' => 'Agent', 'from' => 'LOOXIS <service@looxis.de>', 'created_at' => '2026-10-01T09:00:00.000Z']),
            zammadArticle(['id' => 4, 'created_at' => '2026-10-02T09:00:00.000Z']),
            zammadArticle(['id' => 5, 'created_at' => '2026-10-03T09:00:00.000Z']),
        ]);
        closingCategory();

        closingPage()->assertOk()->assertDontSee('name="formularwahl" value="abschluss"', false);
    });

    test('a request with the form is refused when it is not offered', function () {
        closingThread(2);
        closingCategory();
        CaseAgent::fake([closingAnswer()]);

        closingRun(['abschluss_angebot' => 'besteht'])->assertSessionHas('analysis_error');

        expect(Analysis::query()->count())->toBe(0);
    });
});

describe('validation', function () {
    test('the question about the offer must be answered', function () {
        closingThread(3);
        closingCategory();

        closingRun()->assertSessionHasErrors(['abschluss_angebot' => 'Bitte angeben, ob das bisherige Angebot bestehen bleibt.']);
    });

    test('no product, no free text and no return label go along', function () {
        closingThread(3);
        closingCategory();
        CaseAgent::fake([closingAnswer()]);
        $label = ReturnLabel::factory()->create(['ticket_number' => '2137942']);

        closingRun(['abschluss_angebot' => 'keins', 'produkt_ohne_wissen' => 'Fototasse', 'kein_produktbezug' => '0', 'kontext' => 'GEHEIMER FREITEXT', 'return_label' => $label->id])->assertSessionHasNoErrors();

        $inputs = Analysis::query()->latest()->first()->content['inputs'];
        expect((bool) $inputs['kein_produktbezug'])->toBeTrue()
            ->and($inputs['produkt_ohne_wissen'])->toBe('')
            ->and($inputs['return_label'])->toBeNull()
            ->and($inputs['formular'])->toBe('abschluss')
            ->and($inputs['abschluss_angebot'])->toBe('keins');
        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => ! $prompt->contains('GEHEIMER FREITEXT'));
    });
});

describe('what the model is told', function () {
    test('the fixed closing sentences reach the model, above everything else', function () {
        closingThread(3);
        closingCategory();
        CaseAgent::fake([closingAnswer()]);

        closingRun(['abschluss_angebot' => 'besteht', 'abschluss_kernaussage' => 'Ein Druckfehler ist nicht erkennbar.'])->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Angaben aus dem Formular „Diskussion beenden“')
            && $prompt->contains('nichts hinzuzufügen und beenden die Diskussion')
            && $prompt->contains('angebotenen Optionen gelten weiterhin')
            && $prompt->contains('Ein Druckfehler ist nicht erkennbar.')
            && $prompt->contains('kein Ausdruck von Verständnis'));
    });

    test('with an offer the options are only referred to, without one nothing is offered', function () {
        expect((new ClosingDecision('besteht'))->facts())
            ->toContain('in genau einem Satz')->toContain('und haben Optionen angeboten')->toContain('Nenne keinen Grund')
            ->and((new ClosingDecision('keins'))->facts())
            ->toContain('Es ist kein Angebot offen')->not->toContain('und haben Optionen angeboten');
    });

    test('the analysis prompt lets the block override the general rules', function () {
        expect(file_get_contents(resource_path('prompts/analysis.md')))->toContain('„Angaben aus dem Formular „Diskussion beenden““');
    });
});

describe('the stored proposal', function () {
    test('the form is prefilled and the internal note is suggested for sending', function () {
        closingThread(3);
        closingCategory();
        CaseAgent::fake([closingAnswer()]);

        closingRun(['abschluss_angebot' => 'besteht', 'abschluss_kernaussage' => 'Kein Druckfehler.'])->assertSessionHasNoErrors();
        $id = Analysis::query()->latest()->first()->uuid;

        test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942?analyse='.$id)->assertOk()
            ->assertSee("mode: 'abschluss',", false)
            ->assertSee("angebot: 'besteht',", false)
            ->assertSee('Kein Druckfehler.')
            ->assertSee('Erstellt mit: Diskussion beenden', false)
            ->assertSee('nicht mehr kommentiert', false);
    });

    test('a proposal with another form suggests no note', function () {
        expect(ClosingDecision::NOTE)->toContain('nur noch geschlossen');
        closingThread(3);
        closingCategory();

        closingPage()->assertOk()->assertDontSee('nicht mehr kommentiert', false);
    });
});
