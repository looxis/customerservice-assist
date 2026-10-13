<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\AnalysisException;
use App\Analysis\AnalysisStore;
use App\Analysis\ResultValidator;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Prompts\AgentPrompt;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
        'cache.default' => 'array',
    ]);

    knowledgeBase([
        'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Rücknahme Präsentationsmaterial'], 'Displays und Warenmuster werden auf Wunsch zurückgenommen.'),
    ]);

    fakeZammad(extra: ['zammad.test/api/v1/ticket_articles/by_ticket/*' => fn () => Http::response([zammadArticle(['id' => 1, 'body' => '<p>Ich gebe mein Geschäft auf. Nehmen Sie den Aufsteller zurück?</p>'])])]);
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function stepAnswer(array $overrides = []): array
{
    return array_replace([
        'summary' => ['incident' => 'Geschäftsaufgabe', 'customer_wish' => 'Rücknahme des Aufstellers'],
        'category' => 'order-process-question', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Rücknahmebedarf klären.', 'actions' => [],
        'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'Antwort hängt vom Bedarf ab.', 'knowledge_gaps' => [],
        'knowledge_ids' => ['POLICY-001'],
        'confidence' => ['level' => 'MITTEL', 'reasons' => ['Regel vorhanden']],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [],
        'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag, danke für Ihre Nachricht.'],
    ], $overrides);
}

function stepDecision(array $overrides = []): array
{
    return array_replace([
        'question' => 'Was wird zurückgenommen?',
        'why' => 'Die Antwort hängt vom Bedarf der Produktion ab.',
        'options' => [
            ['key' => 'display', 'label' => 'Nur Display zurück', 'consequence' => 'Rückfrage zum Zustand des Displays'],
            ['key' => 'sample', 'label' => 'Nur Glasfoto-Muster zurück', 'consequence' => 'Rückfrage zum Zustand der Muster'],
            ['key' => 'both', 'label' => 'Beides zurück', 'consequence' => 'Rückfrage zum Zustand beider'],
            ['key' => 'none', 'label' => 'Kein Bedarf – kann entsorgt werden', 'consequence' => 'Hinweis auf Entsorgung'],
        ],
        'knowledge_ids' => ['POLICY-001'],
    ], $overrides);
}

function stepBlocked(array $overrides = []): array
{
    return stepAnswer(array_replace(['next_step' => 'internal_first', 'reply' => null, 'decision' => stepDecision(), 'internal_steps' => ['Bei der Produktionsleitung nachfragen']], $overrides));
}

function stepRun(array $fields = [], string $staff = 'Nele'): string
{
    $response = test()->withCookie('staff_name', $staff)->post(route('tickets.analysis.run', ['number' => '2137942']), withProductAnswer(array_merge([
        'kein_produktbezug' => '1', 'kundengruppe' => 'reseller', 'variante' => 'verlauf', 'kontext' => 'Fotohändler',
    ], $fields)));

    parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

    return $query['analyse'] ?? '';
}

function stepContinue(string $id, array $fields = [], string $staff = 'Nele')
{
    return test()->withCookie('staff_name', $staff)->post(route('tickets.analysis.run', ['number' => '2137942']), ['klaerung_analyse' => $id, ...$fields]);
}

describe('validator', function () {
    test('internal first drops a reply the model delivered anyway', function () {
        $checked = (new ResultValidator)->check(stepBlocked(['reply' => ['language' => 'Deutsch', 'text' => 'zu früh']]), ['POLICY-001']);

        expect($checked['result']['reply'])->toBeNull()->and($checked['result']['decision']['options'])->toHaveCount(4);
    });

    test('internal first needs a decision or a step', function () {
        expect(fn () => (new ResultValidator)->check(stepBlocked(['decision' => null, 'internal_steps' => []]), ['POLICY-001']))->toThrow(AnalysisException::class);
    });

    test('a reply is required when the next step is a reply', function () {
        expect(fn () => (new ResultValidator)->check(stepAnswer(['reply' => null]), []))->toThrow(AnalysisException::class);
    });

    test('a decision without a knowledge reference stays valid, unknown references are removed', function () {
        $without = (new ResultValidator)->check(stepBlocked(['decision' => stepDecision(['knowledge_ids' => []])]), ['POLICY-001']);
        $unknown = (new ResultValidator)->check(stepBlocked(['decision' => stepDecision(['knowledge_ids' => ['POLICY-001', 'POLICY-999']])]), ['POLICY-001']);

        expect($without['result']['decision'])->not->toBeNull()
            ->and($unknown['result']['decision']['knowledge_ids'])->toBe(['POLICY-001'])
            ->and($unknown['notes'])->not->toBe([]);
    });

    test('a decision with fewer than two usable options becomes an internal step', function () {
        $checked = (new ResultValidator)->check(stepBlocked(['decision' => stepDecision(['options' => [['key' => 'a', 'label' => 'Nur eine', 'consequence' => '']]]), 'internal_steps' => []]), ['POLICY-001']);

        expect($checked['result']['decision'])->toBeNull()->and($checked['result']['internal_steps'])->toBe(['Was wird zurückgenommen?']);
    });
});

describe('blocked analysis', function () {
    test('shows the decision under internal procedures, no reply draft and no knowledge gap', function () {
        CaseAgent::fake([stepBlocked()]);
        stepRun();

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')
            ->assertSeeTextInOrder(['Noch kein Antwortentwurf', 'Interne Abläufe', 'Zuerst zu klären', 'Was wird zurückgenommen?', 'Nur Display zurück', 'Nur Glasfoto-Muster zurück', 'Beides zurück', 'Kein Bedarf – kann entsorgt werden', 'Für diesen Fall', 'Bei der Produktionsleitung nachfragen'])
            ->assertDontSeeText('Für diesen Fall fehlt eine Regel')
            ->assertDontSeeText('Antwortentwurf (Deutsch)')
            ->assertDontSeeText('Interne To-dos');
    });

    test('a reply that depends on nothing is shown as before', function () {
        CaseAgent::fake([stepAnswer(['internal_steps' => ['Notiz im Ticket']])]);
        stepRun();

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')
            ->assertSeeText('Antwortentwurf (Deutsch)')
            ->assertDontSeeText('Noch kein Antwortentwurf')
            ->assertDontSeeText('Fehlende Informationen')
            ->assertSeeText('Notiz im Ticket');
    });

    test('the options are radio choices with a pointer cursor, confirmed by a separate button', function () {
        CaseAgent::fake([stepBlocked()]);
        stepRun();

        $html = $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->getContent();

        expect($html)->toMatch('/<label class="[^"]*cursor-pointer[^"]*"[^>]*>\s*<input type="radio" name="klaerung_option" value="none"/')
            ->and($html)->toContain('Mit diesem Ergebnis fortsetzen')
            ->and($html)->toContain('Nur Hinweise zum Abhaken');
    });

    test('the free result is the last choice inside the box "Zuerst zu klären" and its field is visibly a field', function () {
        CaseAgent::fake([stepBlocked()]);
        stepRun();

        $html = $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')
            ->assertSeeTextInOrder(['Zuerst zu klären', 'Kein Bedarf – kann entsorgt werden', 'Anderes Ergebnis oder Ergänzung', 'Ohne gewählte Option gilt dein Text als Ergebnis', 'Mit diesem Ergebnis fortsetzen', 'Für diesen Fall'])
            ->getContent();

        expect($html)->toMatch('/<textarea id="klaerung-text"[^>]*class="[^"]*ring-1[^"]*ring-slate-300[^"]*"/')->and(substr_count($html, 'id="klaerung-text"'))->toBe(1);
    });

    test('the copy and edit actions are refused without a reply', function () {
        CaseAgent::fake([stepBlocked()]);
        $id = stepRun();

        $this->withCredentials()->withCookie('staff_name', 'Nele')->postJson(route('tickets.analysis.copied', ['number' => '2137942', 'analysis' => $id]), ['text' => 'x'])->assertStatus(409)->assertSee('keinen Antwortentwurf');
    });
});

describe('continuing after a clarification', function () {
    test('an option continues the case with the earlier inputs and the clarification as a fact', function () {
        CaseAgent::fake([stepBlocked(), stepAnswer(['reply' => ['language' => 'Deutsch', 'text' => 'Sie können das Display entsorgen. Alles Gute für die Zukunft!']])]);
        $first = stepRun(['kontext' => 'Telefonat geführt']);

        $second = stepContinue($first, ['klaerung_option' => 'none']);
        parse_str((string) parse_url((string) $second->headers->get('Location'), PHP_URL_QUERY), $query);

        expect($query['analyse'])->not->toBe($first);
        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Interne Klärungen') && $prompt->contains('Kein Bedarf – kann entsorgt werden') && $prompt->contains('Telefonat geführt') && $prompt->contains('Reseller'));

        $stored = app(AnalysisStore::class)->result($query['analyse']);
        expect($stored['decisions'])->toHaveCount(1)->and($stored['decisions'][0]['by'])->toBe('Nele');

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')
            ->assertSeeTextInOrder(['Bereits intern geklärt', 'Was wird zurückgenommen?', 'Kein Bedarf – kann entsorgt werden', 'Antwortentwurf', 'entsorgen']);
    });

    test('a free result works next to the options and without a decision', function () {
        CaseAgent::fake([stepBlocked(['decision' => null, 'internal_steps' => ['Lager fragen']]), stepAnswer()]);
        $first = stepRun();

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertSeeText('Ergebnis der internen Prüfung');

        stepContinue($first, ['klaerung_text' => 'Lager hat noch zwei Displays'])->assertRedirect();
        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Lager hat noch zwei Displays'));
    });

    test('an option can be combined with a note', function () {
        CaseAgent::fake([stepBlocked(), stepAnswer()]);
        $first = stepRun();

        stepContinue($first, ['klaerung_option' => 'both', 'klaerung_text' => 'Display ist zerkratzt'])->assertRedirect();
        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Beides zurück (Display ist zerkratzt)'));
    });

    test('an unknown option, no result at all or an older analysis is refused', function () {
        CaseAgent::fake([stepBlocked(), stepBlocked()]);
        $first = stepRun();

        stepContinue($first, ['klaerung_option' => 'erfunden'])->assertSessionHasErrors('klaerung_option');
        stepContinue($first)->assertSessionHasErrors('klaerung_text');

        $second = stepRun();
        expect($second)->not->toBe($first);
        stepContinue($first, ['klaerung_option' => 'none'])->assertSessionHasErrors('klaerung_analyse');
        CaseAgent::assertPromptedTimes(2);
    });

    test('decisions chain over several steps without a limit', function () {
        CaseAgent::fake([stepBlocked(), stepBlocked(['decision' => stepDecision(['question' => 'Wer holt ab?'])]), stepBlocked(['decision' => stepDecision(['question' => 'Wann?'])]), stepAnswer()]);
        $id = stepRun();

        foreach (['none', 'display', 'both'] as $option) {
            $response = stepContinue($id, ['klaerung_option' => $option]);
            $response->assertSessionHasNoErrors();
            parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);
            $id = $query['analyse'];
        }

        expect(app(AnalysisStore::class)->result($id)['decisions'])->toHaveCount(3);
    });
});

describe('older results', function () {
    test('an analysis from before this contract still renders', function () {
        CaseAgent::fake([stepAnswer()]);
        $id = stepRun();

        app(AnalysisStore::class)->changeResult($id, function (array $content): array {
            unset($content['result']['next_step'], $content['result']['decision'], $content['result']['internal_steps'], $content['decisions']);
            $content['result']['internal_todos'] = ['Altes To-do'];
            $content['result']['missing_information'] = [['what' => 'Foto', 'from' => 'Kunde', 'question' => 'Bitte Foto senden']];

            return $content;
        });

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')
            ->assertOk()
            ->assertSeeText('Altes To-do')
            ->assertSeeText('Fehlende Angaben (älterer Vorschlag)')
            ->assertSeeText('Antwortentwurf (Deutsch)');
    });
});

describe('procedures next to case steps', function () {
    test('stay complete but collapsed under their own heading', function () {
        knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Rücknahme'], 'Regel.'),
            'procedures/procedure-001-a.md' => knowledgeDoc(['id' => 'PROCEDURE-001', 'type' => 'procedure', 'title' => 'Rücknahme abwickeln', 'actions' => ['return']], "Gilt für alle.\n\n# Voraussetzungen\n\n- Eine\n\n# Arbeitsschritte\n\n1. Erster Schritt\n\n# Abschlusskontrolle\n\n- Fertig"),
        ]);
        CaseAgent::fake([stepAnswer(['actions' => ['return'], 'internal_steps' => ['Konkreter Fallschritt']])]);
        stepRun();

        $html = $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')
            ->assertSeeTextInOrder(['Für diesen Fall', 'Konkreter Fallschritt', 'Ergänzend aus der Wissensbasis', 'Rücknahme abwickeln', 'Erster Schritt'])
            ->getContent();

        expect($html)->not->toMatch('/<details data-procedure="PROCEDURE-001"[^>]*\sopen/');
    });
});

describe('prompt', function () {
    test('it tells the model to describe the authority after the clarifications and to offer combined options', function () {
        CaseAgent::fake([stepAnswer()]);
        stepRun();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => str_contains((string) $prompt->agent->instructions(), 'Stand nach den „Internen Klärungen“')
            && str_contains((string) $prompt->agent->instructions(), 'auch die Kombination')
            && str_contains((string) $prompt->agent->instructions(), 'Entschuldige dich im Antwortentwurf nur'));
    });
});

describe('security', function () {
    test('texts of the model and of the employee are escaped in the clarification area', function () {
        CaseAgent::fake([stepBlocked([
            'decision' => stepDecision(['question' => '<script>alert(1)</script>', 'options' => [
                ['key' => 'a', 'label' => '<img src=x onerror=alert(2)>', 'consequence' => '<b>fett</b>'],
                ['key' => 'b', 'label' => 'Zwei', 'consequence' => ''],
            ]]),
            'internal_steps' => ['<svg onload=alert(3)>'],
        ])]);
        stepRun();

        $html = $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->getContent();

        expect($html)->not->toContain('<script>alert(1)</script>')
            ->and($html)->not->toContain('<img src=x onerror=alert(2)>')
            ->and($html)->not->toContain('<svg onload=alert(3)>')
            ->and($html)->toContain('&lt;img src=x onerror=alert(2)&gt;');
    });

    test('a clarification text beyond the limit is refused', function () {
        CaseAgent::fake([stepBlocked()]);
        $id = stepRun();

        stepContinue($id, ['klaerung_text' => str_repeat('x', (int) config('analysis.max_context_length') + 1)])->assertSessionHasErrors('klaerung_text');
        CaseAgent::assertPromptedTimes(1);
    });

    test('an analysis of another ticket cannot be continued', function () {
        CaseAgent::fake([stepBlocked()]);
        $id = stepRun();

        $this->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '999999']), [
            'klaerung_analyse' => $id, 'klaerung_option' => 'none', 'kein_produktbezug' => '1', 'kundengruppe' => 'reseller', 'variante' => 'verlauf',
        ])->assertSessionHasErrors('klaerung_analyse');
        CaseAgent::assertPromptedTimes(1);
    });

    test('continuing needs a chosen name like every analysis', function () {
        CaseAgent::fake([stepBlocked()]);
        $id = stepRun();

        $this->withCookie('staff_name', '')->post(route('tickets.analysis.run', ['number' => '2137942']), ['klaerung_analyse' => $id, 'klaerung_option' => 'none'])->assertRedirect();
        CaseAgent::assertPromptedTimes(1);
    });
});
