<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\GuidedForms;
use App\Analysis\ReshipmentDecision;
use App\Models\Analysis;
use App\Models\LabelTicket;
use App\Models\ReturnLabel;
use App\Models\TicketTodo;
use App\Todo\TodoCaseContext;
use App\Todo\TrackingNotifier;
use App\Zammad\Ticket;
use Carbon\CarbonImmutable;
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

    fakeZammad([zammadArticle(['id' => 1, 'subject' => 'Paket nicht angekommen', 'message_id' => '<m1@example.org>', 'body' => '<p>Mein Paket ist nie angekommen.</p>'])]);
    $this->eocsOrders = [];
    Http::fake(['eocs.test/api/v1/orders*' => fn (Request $request) => Http::response(['data' => $this->eocsOrders, 'meta' => []])]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function reshipmentAnswer(string $text = 'Hallo, der Neuversand ist veranlasst.'): array
{
    return [
        'summary' => ['incident' => 'Paket verloren', 'customer_wish' => 'Neuversand'],
        'category' => 'shipping', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Neuversand mitteilen.', 'actions' => [],
        'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'Formular.', 'knowledge_gaps' => [],
        'knowledge_ids' => [], 'confidence' => ['level' => 'HOCH', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [],
        'reply' => ['language' => 'Deutsch', 'text' => $text],
    ];
}

/**
 * A proposal with the form "Neuversand": no product field is needed.
 */
function reshipmentRun(array $fields = [])
{
    return test()->withCookie('staff_name', 'Nele')->from('/tickets/2137942')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kundengruppe' => 'reseller', 'verkaufskanal' => 'fachhaendler', 'variante' => 'verlauf', 'formular' => 'neuversand', ...$fields]);
}

function reshipmentOrder(string $shipped): array
{
    return [
        'id' => 810002, 'external_order_id' => '7KA-0QH8-6AF9', 'origin_order_id' => null, 'status_name' => 'Verschickt',
        'client' => ['name' => 'LOOXIS'], 'shipping' => [], 'billing' => [], 'invoice' => [],
        'shipments' => ['data' => [['carrier' => ['name' => 'DHL'], 'tracking_no' => 'NEU123', 'created_at' => $shipped, 'delivered' => null]]],
        'order_items' => ['data' => []],
    ];
}

function reshipmentPage()
{
    return test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942');
}

describe('choosing the form', function () {
    test('Neuversand is offered on every ticket but never preselected by a category', function () {
        expect(GuidedForms::options())->toBe(['support' => 'Technischer Support', 'reklamation' => 'Reklamation', 'zahlung' => 'Zahlung / Rechnung', 'neuversand' => 'Neuversand', 'abschluss' => 'Diskussion beenden', 'frei' => 'Freitext'])
            ->and(GuidedForms::defaultFor('wismo'))->toBe('frei')
            ->and(GuidedForms::defaultFor(null))->toBe('frei')
            ->and(GuidedForms::guidedLabel('neuversand'))->toBe('Neuversand');

        LabelTicket::query()->create(['zammad_id' => 51234, 'number' => '2137942', 'text' => 'Hallo', 'state' => 'classified', 'split' => 'training', 'decision' => 'reklamation', 'decision_origin' => 'zweifel']);

        reshipmentPage()->assertOk()->assertSee('name="formularwahl" value="neuversand"', false)->assertSee("mode: 'reklamation',", false);
    });

    test('the form shows its questions and fields, the ToDo ticked in advance', function () {
        reshipmentPage()->assertOk()
            ->assertSee('Ist der Neuversand kostenpflichtig?')
            ->assertSee('Bereits bezahlt?')
            ->assertSee('Nein, Rechnung wird dem Paket beigelegt')
            ->assertSee('Auftragsnummer des Neuversands')
            ->assertSee('Aktualisierte Adresse')
            ->assertSee('Adresse aus EOCS laden')
            ->assertSee('name="neuversand_todo" value="1" checked', false)
            ->assertSee("mode === 'neuversand'", false);
    });
});

describe('validation', function () {
    test('the first question must be answered', function () {
        reshipmentRun()->assertSessionHasErrors(['neuversand_kostenpflichtig' => 'Bitte angeben, ob der Neuversand kostenpflichtig ist.']);

        expect(Analysis::query()->count())->toBe(0);
    });

    test('a charged shipment needs the answer whether it is paid', function () {
        reshipmentRun(['neuversand_kostenpflichtig' => 'ja'])->assertSessionHasErrors(['neuversand_bezahlt' => 'Bitte angeben, ob der Neuversand bereits bezahlt ist.']);
    });

    test('no product is needed and products sent anyway are dropped', function () {
        CaseAgent::fake([reshipmentAnswer()]);

        reshipmentRun(['neuversand_kostenpflichtig' => 'nein', 'produkt_ohne_wissen' => 'Fotopuzzle', 'kein_produktbezug' => '0'])->assertSessionHasNoErrors();

        $inputs = Analysis::query()->latest()->first()->content['inputs'];
        expect((bool) $inputs['kein_produktbezug'])->toBeTrue()->and($inputs['produkt_ohne_wissen'])->toBe('');
    });

    test('a return label never goes along', function () {
        CaseAgent::fake([reshipmentAnswer()]);
        $label = ReturnLabel::factory()->create(['ticket_number' => '2137942']);

        reshipmentRun(['neuversand_kostenpflichtig' => 'nein', 'return_label' => $label->id])->assertSessionHasNoErrors();

        expect(Analysis::query()->latest()->first()->content['inputs']['return_label'])->toBeNull();
    });
});

describe('what the model is told', function () {
    test('free of charge: goodwill, the order number and the tracking number to follow', function () {
        CaseAgent::fake([reshipmentAnswer()]);

        reshipmentRun(['neuversand_kostenpflichtig' => 'nein', 'neuversand_bezahlt' => 'rechnung', 'neuversand_auftrag' => '7KA-0QH8-6AF9', 'kontext' => 'GEHEIMER FREITEXT'])->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Angaben aus dem Formular „Neuversand“')
            && $prompt->contains('auf Kulanz kostenfrei veranlasst')
            && $prompt->contains('unter der Nummer 7KA-0QH8-6AF9')
            && $prompt->contains('Sendungsnummer wird dem Kunden nach dem Versand separat mitgeteilt')
            && ! $prompt->contains('Rechnung zur Zahlung')
            && ! $prompt->contains('GEHEIMER FREITEXT'));
    });

    test('charged and paid: only that the shipment is on its way, no costs', function () {
        expect((new ReshipmentDecision(true, 'ja', '7KA-1'))->facts())
            ->toContain('bereits bezahlt')->toContain('Erwähne keine Kosten')->toContain('unter der Nummer 7KA-1')
            ->not->toContain('Kulanz')->not->toContain('Rechnung zur Zahlung');
    });

    test('charged with an invoice in the parcel: the invoice is mentioned', function () {
        expect((new ReshipmentDecision(true, 'rechnung', '7KA-1'))->facts())
            ->toContain('jetzt unter der Nummer 7KA-1 veranlasst wird')->toContain('Rechnung zur Zahlung des Neuversands beiliegt')
            ->not->toContain('Kulanz');
    });

    test('without an order number the draft keeps a placeholder', function () {
        expect((new ReshipmentDecision(false))->facts())->toContain('[AUFTRAGSNUMMER]');
    });

    test('the notes of the employee go to the model, the address only as a placeholder', function () {
        CaseAgent::fake([reshipmentAnswer("Hallo,\nder Neuversand geht an:\n[NEUE-LIEFERADRESSE]")]);

        reshipmentRun([
            'neuversand_kostenpflichtig' => 'ja', 'neuversand_bezahlt' => 'ja', 'neuversand_auftrag' => '7KA-0QH8-6AF9',
            'neuversand_adresse' => "Erika Beispiel\nMusterweg 12\n12345 Neustadt", 'neuversand_zusatz' => 'Bitte für die Verzögerung entschuldigen',
        ])->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('PRIORITÄRE ZUSÄTZLICHE INFORMATIONEN')
            && $prompt->contains('Bitte für die Verzögerung entschuldigen')
            && $prompt->contains('[NEUE-LIEFERADRESSE]')
            && ! $prompt->contains('Musterweg')
            && ! $prompt->contains('Neustadt'));

        $content = Analysis::query()->latest()->first()->content;
        expect($content['placeholders']['[NEUE-LIEFERADRESSE]'])->toBe("Erika Beispiel\nMusterweg 12\n12345 Neustadt");

        reshipmentPage()->assertOk()->assertSee('Musterweg 12');
    });
});

describe('the claim number of the new shipment', function () {
    test('a claim number is stored in capitals', function () {
        CaseAgent::fake([reshipmentAnswer()]);

        reshipmentRun(['neuversand_kostenpflichtig' => 'nein', 'neuversand_auftrag' => ' r2-7ka-0qh8-6af8 '])->assertSessionHasNoErrors();

        expect(Analysis::query()->latest()->first()->content['inputs']['neuversand_auftrag'])->toBe('R2-7KA-0QH8-6AF8');
        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('unter der Nummer R2-7KA-0QH8-6AF8'));
    });

    test('the address is loaded from the claim itself, not from the original order', function () {
        $this->eocsOrders = [[...reshipmentOrder('12.11.2026 09:00'), 'external_order_id' => 'R2-7KA-0QH8-6AF8', 'shipping' => ['first_name' => 'Erika', 'last_name' => 'Beispiel', 'street' => 'Neuer Weg', 'street_no' => '5', 'zipcode' => '12345', 'city' => 'Neustadt', 'country_code' => 'de']]];

        $this->withCredentials()->withCookie('staff_name', 'Nele')->getJson(route('dhl-return.address', ['bestellung' => 'r2-7KA-0QH8-6AF8']))
            ->assertOk()->assertJsonPath('found', true)->assertJsonPath('address.street', 'Neuer Weg')->assertJsonPath('address.city', 'Neustadt');

        Http::assertSent(fn (Request $request): bool => str_contains(urldecode($request->url()), 'external_order_id]=R2-7KA-0QH8-6AF8,exact'));
    });
});

describe('the stored proposal', function () {
    test('the answers are stored, prefill the form and name the form', function () {
        CaseAgent::fake([reshipmentAnswer()]);

        reshipmentRun(['neuversand_kostenpflichtig' => 'ja', 'neuversand_bezahlt' => 'rechnung', 'neuversand_auftrag' => '7KA-0QH8-6AF9', 'neuversand_todo' => '0'])->assertSessionHasNoErrors();

        expect(Analysis::query()->latest()->first()->content['inputs'])->toMatchArray([
            'formular' => 'neuversand', 'neuversand_kostenpflichtig' => 'ja', 'neuversand_bezahlt' => 'rechnung', 'neuversand_auftrag' => '7KA-0QH8-6AF9', 'neuversand_todo' => false, 'kontext' => '',
        ]);

        reshipmentPage()->assertOk()->assertSee("mode: 'neuversand',", false)->assertSee("bezahlt: 'rechnung',", false)
            ->assertSeeText('Erstellt mit: Neuversand')->assertDontSeeText('kein Produktbezug ·');
    });

    test('a free shipment stores no payment answer', function () {
        expect(ReshipmentDecision::fromInput(['formular' => 'neuversand', 'neuversand_kostenpflichtig' => 'nein', 'neuversand_bezahlt' => 'ja'])->storedInput())->not->toHaveKey('neuversand_bezahlt')
            ->and(ReshipmentDecision::fromInput(['formular' => 'frei', 'neuversand_kostenpflichtig' => 'nein']))->toBeNull();
    });
});

describe('the ToDo "Sendungsnummer mitteilen"', function () {
    function reshipmentContext(array $inputs): array
    {
        $ticket = new Ticket('2137942', 51234, 'Titel', 'open', false, null, null, 'Erika Beispiel', null, CarbonImmutable::now(), [], 'https://zammad.test/1');

        return app(TodoCaseContext::class)->build($ticket, [], [], [], 'Text mit R1-9XX-0000-0000', $inputs);
    }

    test('is preselected with the order number of the new shipment', function () {
        expect(reshipmentContext(['formular' => 'neuversand', 'neuversand_kostenpflichtig' => 'nein', 'neuversand_auftrag' => '7KA-0QH8-6AF9', 'neuversand_todo' => true]))
            ->toMatchArray(['preselect' => true, 'kind' => 'order', 'number' => '7KA-0QH8-6AF9']);
    });

    test('a claim number names the shipment a claim in the prepared text', function () {
        expect(reshipmentContext(['formular' => 'neuversand', 'neuversand_kostenpflichtig' => 'ja', 'neuversand_bezahlt' => 'rechnung', 'neuversand_auftrag' => 'R2-7KA-0QH8-6AF8', 'neuversand_todo' => true]))
            ->toMatchArray(['preselect' => true, 'kind' => 'claim', 'number' => 'R2-7KA-0QH8-6AF8'])
            ->and(ReshipmentDecision::isClaimNumber('r1-7KA-0QH8-6AF8'))->toBeTrue()
            ->and(ReshipmentDecision::isClaimNumber('7KA-0QH8-6AF8'))->toBeFalse();
    });

    test('the automatic check sends the tracking number of a new shipment set up as a claim', function () {
        $this->travelTo(CarbonImmutable::parse('2026-11-12 16:16:00', 'Europe/Berlin'));
        config(['staff.zammad_tokens' => ['Nele' => 'nele-secret-token']]);
        fakeZammad([zammadArticle(['id' => 1, 'message_id' => '<m1@example.org>', 'subject' => 'Paket', 'created_at' => '2026-11-10T09:00:00.000Z'])], extra: [
            'zammad.test/api/v1/users/me' => Http::response(['id' => 501, 'firstname' => 'Nele', 'lastname' => 'Gorka']),
            'zammad.test/api/v1/tickets/51234' => Http::response(['id' => 51234, 'number' => '2137942']),
        ]);
        $this->eocsOrders = [[...reshipmentOrder('12.11.2026 09:00'), 'external_order_id' => 'R2-7KA-0QH8-6AF8', 'origin_order_id' => 700001]];
        $todo = TicketTodo::factory()->create([
            'zammad_id' => 51234, 'ticket_number' => '2137942', 'description' => 'Sendungsnummer nachreichen', 'kind' => 'waiting', 'waiting_for' => 'Sendungsnummer',
            'notify_customer' => true, 'notify_hint' => "Guten Tag Erika Beispiel,\n\nNummer: [Sendungsnummer] ([Carrier])", 'external_ref' => 'R2-7KA-0QH8-6AF8', 'created_by' => 'Nele',
            'auto_send' => true, 'auto_state' => 'watching', 'customer_language' => 'Deutsch', 'created_at' => '2026-11-11 08:00:00',
        ]);

        app(TrackingNotifier::class)->run(CarbonImmutable::parse('2026-11-12 16:16:00', 'Europe/Berlin'));

        expect($todo->refresh()->auto_state)->toBe('sent')->and($todo->auto_tracking)->toBe('NEU123');
    });

    test('is not preselected when unticked or without an order number', function (array $inputs) {
        expect(reshipmentContext(['formular' => 'neuversand', 'neuversand_kostenpflichtig' => 'nein', ...$inputs])['preselect'])->toBeFalse();
    })->with([[['neuversand_auftrag' => '7KA-0QH8-6AF9', 'neuversand_todo' => false]], [['neuversand_auftrag' => '', 'neuversand_todo' => true]]]);

    test('the automatic check sends the tracking number of the new order, but only a parcel shipped after the ToDo', function (string $shipped, bool $sent) {
        $this->travelTo(CarbonImmutable::parse('2026-11-12 16:16:00', 'Europe/Berlin'));
        config(['staff.zammad_tokens' => ['Nele' => 'nele-secret-token']]);
        fakeZammad([zammadArticle(['id' => 1, 'message_id' => '<m1@example.org>', 'subject' => 'Paket', 'created_at' => '2026-11-10T09:00:00.000Z'])], extra: [
            'zammad.test/api/v1/users/me' => Http::response(['id' => 501, 'firstname' => 'Nele', 'lastname' => 'Gorka']),
            'zammad.test/api/v1/tickets/51234' => Http::response(['id' => 51234, 'number' => '2137942']),
        ]);
        $this->eocsOrders = [reshipmentOrder($shipped)];
        $analysis = Analysis::factory()->create(['ticket_number' => '2137942', 'content' => ['ticket' => '', 'result' => [], 'inputs' => ['formular' => 'neuversand', 'neuversand_kostenpflichtig' => 'nein', 'neuversand_auftrag' => '7KA-0QH8-6AF9']]]);
        $todo = TicketTodo::factory()->create([
            'zammad_id' => 51234, 'ticket_number' => '2137942', 'description' => 'Sendungsnummer nachreichen', 'kind' => 'waiting', 'waiting_for' => 'Sendungsnummer',
            'notify_customer' => true, 'notify_hint' => "Guten Tag Erika Beispiel,\n\nNummer: [Sendungsnummer] ([Carrier])", 'external_ref' => '7KA-0QH8-6AF9', 'created_by' => 'Nele',
            'auto_send' => true, 'auto_state' => 'watching', 'customer_language' => 'Deutsch', 'created_at' => '2026-11-11 08:00:00', 'source_analysis_uuid' => $analysis->uuid,
        ]);

        app(TrackingNotifier::class)->run(CarbonImmutable::parse('2026-11-12 16:16:00', 'Europe/Berlin'));

        expect($todo->refresh()->auto_state)->toBe($sent ? 'sent' : 'watching');
    })->with([['12.11.2026 09:00', true], ['05.11.2026 09:00', false]]);

    test('an ordinary order ToDo still never sends the parcel of the order itself', function () {
        $this->travelTo(CarbonImmutable::parse('2026-11-12 16:16:00', 'Europe/Berlin'));
        config(['staff.zammad_tokens' => ['Nele' => 'nele-secret-token']]);
        $this->eocsOrders = [reshipmentOrder('12.11.2026 09:00')];
        $todo = TicketTodo::factory()->create([
            'zammad_id' => 51234, 'ticket_number' => '2137942', 'description' => 'Sendungsnummer nachreichen', 'kind' => 'waiting', 'waiting_for' => 'Sendungsnummer',
            'notify_customer' => true, 'notify_hint' => 'Nummer: [Sendungsnummer]', 'external_ref' => '7KA-0QH8-6AF9', 'created_by' => 'Nele',
            'auto_send' => true, 'auto_state' => 'watching', 'customer_language' => 'Deutsch', 'created_at' => '2026-11-11 08:00:00',
        ]);

        app(TrackingNotifier::class)->run(CarbonImmutable::parse('2026-11-12 16:16:00', 'Europe/Berlin'));

        expect($todo->refresh()->auto_state)->toBe('watching');
    });
});
