<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\GuidedForms;
use App\Analysis\PaymentDecision;
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
});

afterEach(fn () => cleanUpKnowledgeBases());

function paymentTicket(string $body = '<p>here the payment for order 7JQ-18SU-DVA4</p>'): void
{
    fakeZammad([zammadArticle(['id' => 1, 'subject' => 'virement', 'message_id' => '<m1@example.org>', 'body' => $body])]);
    Http::fake([
        'eocs.test/api/v1/orders*' => function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            [$number] = explode(',', (string) ($query['filter']['external_order_id'] ?? ''));

            return Http::response(['data' => $number === '7JQ-18SU-DVA4' ? [returnEocsOrder()] : []]);
        },
    ]);
}

function paymentCategory(string $category = 'zahlung'): void
{
    LabelTicket::query()->create(['zammad_id' => 51234, 'number' => '2137942', 'text' => 'Hallo', 'state' => 'classified', 'split' => 'training', 'decision' => $category, 'decision_origin' => 'zweifel']);
}

function paymentAnswer(): array
{
    return [
        'summary' => ['incident' => 'Zahlungsnachweis', 'customer_wish' => 'Freigabe'],
        'category' => 'order-process-question', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Antwort senden.', 'actions' => [],
        'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'Formular.', 'knowledge_gaps' => [],
        'knowledge_ids' => [], 'confidence' => ['level' => 'HOCH', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [],
        'reply' => ['language' => 'Deutsch', 'text' => 'Hallo, Mollie erkennt die Zahlung automatisch.'],
    ];
}

function paymentRun(array $fields = [])
{
    return test()->withCookie('staff_name', 'Nele')->from('/tickets/2137942')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kundengruppe' => 'reseller', 'verkaufskanal' => 'fachhaendler', 'variante' => 'verlauf', 'formular' => 'zahlung', ...$fields]);
}

describe('when the form is offered', function () {
    test('preselected for the category Zahlung / Rechnung', function () {
        expect(GuidedForms::defaultFor('zahlung'))->toBe('zahlung')
            ->and(GuidedForms::defaultFor('zahlung', true))->toBe('zahlung')
            ->and(GuidedForms::guidedLabel('zahlung'))->toBe('Zahlung / Rechnung')
            ->and(GuidedForms::forGoods())->not->toContain('zahlung');
    });

    test('the ticket page preselects it and links the payment state in EOCS', function () {
        paymentTicket();
        paymentCategory();

        test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()
            ->assertSee("mode: 'zahlung',", false)
            ->assertSee('name="formularwahl" value="zahlung"', false)
            ->assertSee('Zahlungsnachweis / Überweisungsbestätigung')
            ->assertSee('href="https://eocs.test/orders/700555/invoice/"', false)
            ->assertSee('Zahlungsstand in EOCS prüfen');
    });

    test('selectable for another category, without a loaded order a hint replaces the link', function () {
        paymentTicket('<p>Ich habe überwiesen.</p>');
        paymentCategory('wismo');

        test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()
            ->assertSee('name="formularwahl" value="zahlung"', false)
            ->assertDontSee("mode: 'zahlung',", false)
            ->assertSee('Keine Bestellung geladen.');
    });
});

describe('validation', function () {
    test('the concern must be chosen', function () {
        paymentTicket();

        paymentRun()->assertSessionHasErrors(['zahlung_anliegen' => 'Bitte das Anliegen angeben.']);
    });

    test('a proof of payment needs method and state', function () {
        paymentTicket();

        paymentRun(['zahlung_anliegen' => 'nachweis'])->assertSessionHasErrors(['zahlung_art' => 'Bitte die Zahlungsart der Bestellung angeben.']);
        paymentRun(['zahlung_anliegen' => 'nachweis', 'zahlung_art' => 'mollie'])->assertSessionHasErrors(['zahlung_stand' => 'Bitte den Zahlungsstand laut EOCS angeben.']);
    });

    test('invoice and refund need their answer, a question and a supplier need nothing more', function () {
        paymentTicket();
        CaseAgent::fake([paymentAnswer(), paymentAnswer()]);

        paymentRun(['zahlung_anliegen' => 'rechnung'])->assertSessionHasErrors(['zahlung_rechnung_anhang']);
        paymentRun(['zahlung_anliegen' => 'erstattung'])->assertSessionHasErrors(['zahlung_erstattung']);
        paymentRun(['zahlung_anliegen' => 'frage'])->assertSessionHasNoErrors();
        paymentRun(['zahlung_anliegen' => 'lieferant'])->assertSessionHasNoErrors();
    });

    test('no product, no free text and no return label go along', function () {
        paymentTicket();
        CaseAgent::fake([paymentAnswer()]);
        $label = ReturnLabel::factory()->create(['ticket_number' => '2137942']);

        paymentRun(['zahlung_anliegen' => 'nachweis', 'zahlung_art' => 'mollie', 'zahlung_stand' => 'bezahlt', 'produkt_ohne_wissen' => 'Fototasse', 'kein_produktbezug' => '0', 'kontext' => 'GEHEIMER FREITEXT', 'return_label' => $label->id])->assertSessionHasNoErrors();

        $inputs = Analysis::query()->latest()->first()->content['inputs'];
        expect((bool) $inputs['kein_produktbezug'])->toBeTrue()
            ->and($inputs['produkt_ohne_wissen'])->toBe('')
            ->and($inputs['return_label'])->toBeNull()
            ->and($inputs['formular'])->toBe('zahlung')
            ->and($inputs['zahlung_stand'])->toBe('bezahlt');
        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => ! $prompt->contains('GEHEIMER FREITEXT'));
    });
});

describe('what the model is told', function () {
    test('a Mollie proof of payment: automatic recognition, never added to the order', function () {
        paymentTicket();
        CaseAgent::fake([paymentAnswer()]);

        paymentRun(['zahlung_anliegen' => 'nachweis', 'zahlung_art' => 'mollie', 'zahlung_stand' => 'offen', 'zahlung_zusatz' => 'Referenz falsch übernommen.'])->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Angaben aus dem Formular „Zahlung / Rechnung“')
            && $prompt->contains('Mollie erkennt den Zahlungseingang automatisch')
            && $prompt->contains('noch nicht registriert')
            && $prompt->contains('nie, der Beleg werde der Bestellung hinzugefügt')
            && $prompt->contains('Referenz falsch übernommen.'));
    });

    test('each state of a Mollie payment gets its own sentence', function () {
        $facts = fn (string $state): string => (new PaymentDecision('nachweis', 'mollie', $state))->facts();

        expect($facts('bezahlt'))->toContain('registriert und die Bestellung freigegeben')->toContain('das Ticket schließen')
            ->and($facts('offen'))->toContain('Frist in der Zahlungsanweisung')->not->toContain('das Ticket schließen')
            ->and($facts('storniert'))->toContain('1 bis 3 Werktagen')->toContain('neu auszulösen');
    });

    test('a proof of payment on account is assigned by us and never mentions Mollie', function () {
        $facts = (new PaymentDecision('nachweis', 'rechnung', 'offen'))->facts();

        expect($facts)->toContain('wir ordnen die Zahlung zu')->toContain('Erwähne Mollie nicht')
            ->not->toContain('Mollie erkennt');
    });

    test('a supplier dunning letter gets a short acknowledgement', function () {
        expect((new PaymentDecision('lieferant'))->facts())
            ->toContain('an unsere Buchhaltung weitergegeben')->toContain('keine Bestellung')->toContain('Ticket schließen');
    });

    test('invoice and refund follow the answer', function () {
        expect((new PaymentDecision('rechnung', invoiceAttached: 'ja'))->facts())->toContain('als Anhang bei')
            ->and((new PaymentDecision('rechnung', invoiceAttached: 'nein'))->facts())->toContain('nicht bei')
            ->and((new PaymentDecision('erstattung', refundDone: 'ja'))->facts())->toContain('veranlasst')->toContain('keine Anzahl Tage')
            ->and((new PaymentDecision('erstattung', refundDone: 'nein'))->facts())->toContain('noch nicht veranlasst');
    });

    test('answers of another concern are dropped', function () {
        $decision = PaymentDecision::fromInput(['formular' => 'zahlung', 'zahlung_anliegen' => 'lieferant', 'zahlung_art' => 'mollie', 'zahlung_stand' => 'bezahlt', 'zahlung_erstattung' => 'ja']);

        expect($decision->method)->toBeNull()->and($decision->state)->toBeNull()->and($decision->refundDone)->toBeNull()
            ->and(PaymentDecision::fromInput(['formular' => 'neuversand', 'zahlung_anliegen' => 'frage']))->toBeNull();
    });
});

describe('the stored proposal', function () {
    test('the form is prefilled from the stored answers', function () {
        paymentTicket();
        CaseAgent::fake([paymentAnswer()]);

        paymentRun(['zahlung_anliegen' => 'nachweis', 'zahlung_art' => 'mollie', 'zahlung_stand' => 'bezahlt', 'zahlung_zusatz' => 'Doppelt bezahlt.'])->assertSessionHasNoErrors();
        $id = Analysis::query()->latest()->first()->uuid;

        test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942?analyse='.$id)->assertOk()
            ->assertSee("mode: 'zahlung',", false)
            ->assertSee("anliegen: 'nachweis',", false)
            ->assertSee("zahlungsart: 'mollie',", false)
            ->assertSee("zahlungsstand: 'bezahlt',", false)
            ->assertSee('Doppelt bezahlt.')
            ->assertSee('Erstellt mit: Zahlung / Rechnung', false);
    });
});
