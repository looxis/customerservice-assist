<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\GuidedForms;
use App\Analysis\SupportDecision;
use App\Models\Analysis;
use App\Models\LabelTicket;
use App\Models\ReturnLabel;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Prompts\AgentPrompt;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
    ]);

    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);

    fakeZammad([zammadArticle(['id' => 1, 'subject' => 'Login', 'message_id' => '<m1@example.org>', 'body' => '<p>Ich kann mich im Shop nicht anmelden.</p>'])]);
    Http::fake(['eocs.test/api/v1/orders*' => fn (Request $request) => Http::response(['data' => []])]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function supportAnswer(): array
{
    return [
        'summary' => ['incident' => 'Login', 'customer_wish' => 'Zugang'],
        'category' => 'order-process-question', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Passwort zurücksetzen.', 'actions' => [],
        'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'Regel.', 'knowledge_gaps' => [],
        'knowledge_ids' => [], 'confidence' => ['level' => 'NIEDRIG', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [],
        'reply' => ['language' => 'Deutsch', 'text' => 'Hallo, bitte das Passwort zurücksetzen.'],
    ];
}

/**
 * A proposal with the support form: no product field is sent, as the form leaves it empty.
 */
function supportRun(array $fields = [])
{
    return test()->withCookie('staff_name', 'Nele')->from('/tickets/2137942')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kundengruppe' => 'reseller', 'verkaufskanal' => 'fachhaendler', 'variante' => 'verlauf', 'formular' => 'support', ...$fields]);
}

function supportTicketAs(string $decision): void
{
    LabelTicket::query()->create(['zammad_id' => 51234, 'number' => '2137942', 'text' => 'Hallo', 'state' => 'classified', 'split' => 'training', 'decision' => $decision, 'decision_origin' => 'zweifel']);
}

function supportPage()
{
    return test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942');
}

describe('choosing the form', function () {
    test('the form of the category is preselected, every other ticket starts with the free text', function (?string $category, string $form) {
        if ($category !== null) {
            supportTicketAs($category);
        }

        supportPage()->assertOk()->assertSee("mode: '{$form}',", false);
    })->with([['technischer-support', 'support'], ['reklamation', 'reklamation'], ['wismo', 'frei'], [null, 'frei']]);

    test('every ticket offers the three forms instead of the switch button', function () {
        supportPage()->assertOk()
            ->assertSeeInOrder(['name="formularwahl" value="support"', 'Technischer Support', 'name="formularwahl" value="reklamation"', 'Reklamation', 'name="formularwahl" value="frei"', 'Freitext'], false)
            ->assertDontSee('Reklamationsformular verwenden');
    });

    test('the list of forms names the guided forms before the free text', function () {
        expect(GuidedForms::options())->toBe(['support' => 'Technischer Support', 'reklamation' => 'Reklamation', 'neuversand' => 'Neuversand', 'frei' => 'Freitext'])
            ->and(GuidedForms::defaultFor('technischer-support'))->toBe('support')
            ->and(GuidedForms::defaultFor('produkt-frage'))->toBe('frei')
            ->and(GuidedForms::defaultFor(null))->toBe('frei');
    });

    test('an unknown form is rejected', function () {
        supportRun(['formular' => 'storno'])->assertSessionHasErrors('formular');
    });
});

describe('the fields', function () {
    test('the support form shows the question and both text fields with their headings and examples', function () {
        supportTicketAs('technischer-support');

        supportPage()->assertOk()
            ->assertSee('Ist eine mögliche Lösung bereits bekannt?')
            ->assertSee('Unklar, bitte weitere Informationen erfragen')
            ->assertSee('Zusätzliche Hinweise zur Lösung – optional')
            ->assertSee('z. B. Passwort-vergessen-Funktion nutzen')
            ->assertSee('Welche Informationen werden benötigt?')
            ->assertSee('Welche Informationen werden benötigt? – optional')
            ->assertDontSee('name="support_loesung" value="ja" x-model="loesung" checked', false);
    });

    test('without an answer the proposal is not created', function () {
        supportRun()->assertSessionHasErrors(['support_loesung' => 'Bitte angeben, ob eine Lösung bereits bekannt ist.']);

        expect(Analysis::query()->count())->toBe(0);
    });

    test('an unclear solution needs no further information: the standard request of the knowledge base applies', function () {
        CaseAgent::fake([supportAnswer()]);

        supportRun(['support_loesung' => 'unklar', 'support_info' => ' '])->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Rückfrage zu einem unklaren technischen Problem')
            && ! $prompt->contains('Angabe des Mitarbeiters, sie hat Vorrang'));
    });

    test('a failed validation keeps the typed texts', function () {
        supportRun(['support_loesung' => 'unklar', 'support_info' => 'Fehlermeldung nennen', 'verkaufskanal' => ''])
            ->assertSessionHasErrors('verkaufskanal')->assertSessionHasInput('support_info', 'Fehlermeldung nennen');
    });

    test('the text fields take at most 1,000 characters', function (string $answer, string $field) {
        supportRun(['support_loesung' => $answer, $field => str_repeat('a', 1001)])->assertSessionHasErrors($field);
    })->with([['ja', 'support_hinweis'], ['unklar', 'support_info']]);
});

describe('the product', function () {
    test('without a product the proposal is created as a case without a product', function () {
        CaseAgent::fake([supportAnswer()]);

        supportRun(['support_loesung' => 'ja'])->assertSessionHasNoErrors();

        expect((bool) Analysis::query()->latest()->first()->content['inputs']['kein_produktbezug'])->toBeTrue();
    });

    test('a tick for "no product" left over from another form does not stand against a chosen product', function () {
        CaseAgent::fake([supportAnswer()]);

        supportRun(['support_loesung' => 'ja', 'kein_produktbezug' => '1', 'produkt_ohne_wissen' => 'Fotopuzzle'])->assertSessionHasNoErrors();

        $inputs = Analysis::query()->latest()->first()->content['inputs'];
        expect((bool) $inputs['kein_produktbezug'])->toBeFalse()->and($inputs['produkt_ohne_wissen'])->toBe('Fotopuzzle');
    });

    test('the other forms still ask for the product', function (string $form) {
        supportRun(['formular' => $form, 'reklamation_berechtigt' => 'ja'])->assertSessionHasErrors('produkte');
    })->with(['frei', 'reklamation']);

    test('the page marks the product as optional for the support form only', function () {
        supportPage()->assertOk()->assertSee("mode === 'support'", false)->assertSee('(optional)');

        $this->withCookie('staff_name', 'Nele')->get(route('questions.create'))->assertOk()->assertDontSee("mode === 'support'", false);
    });
});

describe('what the model is told', function () {
    test('a known solution without a note comes from the knowledge and is never invented', function () {
        CaseAgent::fake([supportAnswer()]);

        supportRun(['support_loesung' => 'ja', 'kontext' => 'GEHEIMER FREITEXT'])->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Angaben aus dem Formular „Technischer Support“')
            && $prompt->contains('Eine mögliche Lösung ist bekannt')
            && $prompt->contains('erfinde keine')
            && ! $prompt->contains('Hinweis des Mitarbeiters zur Lösung')
            && ! $prompt->contains('GEHEIMER FREITEXT'));
    });

    test('the note of the employee on the solution goes to the model', function () {
        CaseAgent::fake([supportAnswer()]);

        supportRun(['support_loesung' => 'ja', 'support_hinweis' => 'Passwort-vergessen-Funktion nutzen', 'support_info' => 'NICHT SENDEN'])->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Hinweis des Mitarbeiters zur Lösung (halte dich daran')
            && $prompt->contains('Passwort-vergessen-Funktion nutzen')
            && ! $prompt->contains('NICHT SENDEN'));
    });

    test('an unclear solution promises nothing and asks for the information', function () {
        CaseAgent::fake([supportAnswer()]);

        supportRun(['support_loesung' => 'unklar', 'support_info' => 'Bitte einen Screenshot der Fehlermeldung senden', 'support_hinweis' => 'NICHT SENDEN'])->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Sage keine Lösung zu')
            && $prompt->contains('Diese Entscheidung geht dem Wissen vor')
            && $prompt->contains('Der Entwurf besteht nur aus der Rückfrage')
            && $prompt->contains('Angabe des Mitarbeiters, sie hat Vorrang')
            && $prompt->contains('Bitte einen Screenshot der Fehlermeldung senden')
            && ! $prompt->contains('Eine mögliche Lösung ist bekannt')
            && ! $prompt->contains('NICHT SENDEN'));
    });

    test('a return label never goes along with the support form', function () {
        CaseAgent::fake([supportAnswer()]);
        $label = ReturnLabel::factory()->create(['ticket_number' => '2137942']);

        supportRun(['support_loesung' => 'ja', 'return_label' => $label->id])->assertSessionHasNoErrors();

        expect(Analysis::query()->latest()->first()->content['inputs']['return_label'])->toBeNull();
    });
});

describe('the knowledge for the request', function () {
    test('choosing "unclear" retrieves the standard request, a known solution does not', function (string $answer, bool $retrieved) {
        config(['knowledge.retrieval.active' => true, 'knowledge.router.active' => false]);
        knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel']),
            'tone/tone-014-x.md' => knowledgeDoc(['id' => 'TONE-014', 'type' => 'tone', 'title' => 'Screenshot erfragen', 'summary' => 'Legt fest, worum die Rückfrage bei einem unklaren technischen Problem immer bittet, zum Beispiel einen Screenshot.', 'retrieval' => 'binding', 'triggers' => ['unklaren technischen Problem']], "Gilt für alle Kundenarten und Vertriebskanäle.\n\n# Ziel\n\nSTANDARD-RUECKFRAGE: Screenshot mit sichtbarer Internetadresse."),
        ]);
        CaseAgent::fake([supportAnswer()]);

        supportRun(['support_loesung' => $answer])->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('STANDARD-RUECKFRAGE') === $retrieved);
    })->with([['unklar', true], ['ja', false]]);

    test('the sentences of the form do not name topics that would retrieve unrelated knowledge', function () {
        expect((new SupportDecision('ja'))->facts().(new SupportDecision('unklar'))->facts())
            ->not->toContain('Zahlung')->not->toContain('Passwort')->not->toContain('Anmeldung');
    });
});

describe('the stored proposal', function () {
    test('only the answers of the chosen branch are stored and prefill the form', function () {
        CaseAgent::fake([supportAnswer()]);

        supportRun(['support_loesung' => 'unklar', 'support_info' => 'Screenshot', 'support_hinweis' => 'egal'])->assertSessionHasNoErrors();

        $inputs = Analysis::query()->latest()->first()->content['inputs'];
        expect($inputs)->toMatchArray(['formular' => 'support', 'support_loesung' => 'unklar', 'support_info' => 'Screenshot', 'kontext' => ''])->not->toHaveKey('support_hinweis');

        supportPage()->assertOk()->assertSee("mode: 'support',", false)->assertSee("loesung: 'unklar',", false)
            ->assertSeeText('Erstellt mit: Technischer Support · fachhaendler.looxis.de');
    });

    test('the decision reads the stored inputs and ignores other forms', function () {
        expect(SupportDecision::fromInput(['formular' => 'support', 'support_loesung' => 'ja', 'support_hinweis' => ' Tipp ']))->hint->toBe('Tipp')
            ->and(SupportDecision::fromInput(['formular' => 'frei', 'support_loesung' => 'ja']))->toBeNull()
            ->and(SupportDecision::fromInput(['formular' => 'support', 'support_loesung' => 'vielleicht']))->toBeNull();
    });
});
