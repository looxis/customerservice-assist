<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\GuidedForms;
use App\Analysis\WismoUpdateDecision;
use App\Models\Analysis;
use App\Models\LabelTicket;
use App\Models\ReturnLabel;
use App\Tracking\ShipmentHistory;
use App\Tracking\ShipmentTracker;
use App\Tracking\TrackingException;
use App\Tracking\TrackingStatus;
use App\Wismo\ShipmentSnapshot;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Prompts\AgentPrompt;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'services.lettermint.token' => null,
        'ai.providers.openai.key' => 'sk-test',
    ]);

    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function wismoInfoTicket(bool $shipped = true): void
{
    fakeZammad([zammadArticle(['id' => 1, 'subject' => 'ordine', 'message_id' => '<m1@example.org>', 'body' => '<p>Il pacco 7JQ-18SU-DVA4 non è ancora arrivato, è un disastro!</p>'])]);
    $shipments = $shipped ? ['data' => [['carrier' => ['name' => 'DHL'], 'tracking_no' => '00340434161094042557', 'created_at' => '12.11.2026 09:00', 'delivered' => null]]] : ['data' => []];
    Http::fake([
        'eocs.test/api/v1/orders*' => function (Request $request) use ($shipments) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            [$number] = explode(',', (string) ($query['filter']['external_order_id'] ?? ''));

            return Http::response(['data' => $number === '7JQ-18SU-DVA4' ? [returnEocsOrder(['shipments' => $shipments])] : []]);
        },
    ]);
}

function wismoInfoCategory(string $category = 'wismo'): void
{
    LabelTicket::query()->create(['zammad_id' => 51234, 'number' => '2137942', 'text' => 'Hallo', 'state' => 'classified', 'split' => 'training', 'decision' => $category, 'decision_origin' => 'zweifel']);
}

function wismoInfoAnswer(): array
{
    return [
        'summary' => ['incident' => 'Paket nicht angekommen', 'customer_wish' => 'Verbleib'],
        'category' => 'order-process-question', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Antwort senden.', 'actions' => [],
        'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'Formular.', 'knowledge_gaps' => [],
        'knowledge_ids' => [], 'confidence' => ['level' => 'HOCH', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [],
        'reply' => ['language' => 'Italienisch', 'text' => 'Buongiorno, il pacco è in consegna.'],
    ];
}

function wismoInfoRun(array $fields = [])
{
    return test()->withCookie('staff_name', 'Nele')->from('/tickets/2137942')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kundengruppe' => 'reseller', 'verkaufskanal' => 'fachhaendler', 'variante' => 'verlauf', 'formular' => 'wismo_info', ...$fields]);
}

function wismoInfoTracker(?TrackingStatus $status = null, bool $fails = false): void
{
    $tracker = test()->mock(ShipmentTracker::class);
    $fails
        ? $tracker->shouldReceive('track')->once()->andThrow(new TrackingException('DHL nicht erreichbar'))
        : $tracker->shouldReceive('track')->once()->andReturn($status ?? new TrackingStatus('DHL', '00340434161094042557', true, 'Die Sendung wird heute zugestellt.', link: 'https://www.dhl.de/de/privatkunden/pakete-empfangen/verfolgen.html?piececode=00340434161094042557'));
}

describe('when the form is offered', function () {
    test('preselected for the category WISMO, assumes goods', function () {
        expect(GuidedForms::defaultFor('wismo'))->toBe('wismo_info')
            ->and(GuidedForms::defaultFor('wismo', true))->toBe('frei')
            ->and(GuidedForms::guidedLabel('wismo_info'))->toBe('Aktualisierte WISMO-Infos')
            ->and(GuidedForms::forGoods())->toContain('wismo_info');
    });

    test('the ticket page preselects it and lists the shipments', function () {
        wismoInfoTicket();
        wismoInfoCategory();

        test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()
            ->assertSee("mode: 'wismo_info',", false)
            ->assertSee('name="formularwahl" value="wismo_info"', false)
            ->assertSee('Bestellung 7JQ-18SU-DVA4: DHL 00340434161094042557');
    });

    test('selectable on a complaint ticket, without a shipment a hint is shown', function () {
        wismoInfoTicket(false);
        wismoInfoCategory('reklamation');

        test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()
            ->assertSee('name="formularwahl" value="wismo_info"', false)
            ->assertDontSee("mode: 'wismo_info',", false)
            ->assertSee('Keine Sendung mit Sendungsnummer');
    });
});

describe('creating the proposal', function () {
    test('without a shipment with tracking number nothing is asked', function () {
        wismoInfoTicket(false);
        $this->mock(ShipmentTracker::class)->shouldNotReceive('track');
        CaseAgent::fake([wismoInfoAnswer()])->preventStrayPrompts();

        wismoInfoRun()->assertSessionHas('analysis_error', WismoUpdateDecision::NO_SHIPMENT);
        CaseAgent::assertNeverPrompted();
    });

    test('the shipment state is asked in code and goes to the model with the rules, without the tool', function () {
        wismoInfoTicket();
        wismoInfoTracker();
        CaseAgent::fake([wismoInfoAnswer()]);

        wismoInfoRun(['wismo_zusatz' => 'Kunde fragt nach Umleitung an eine Packstation.'])->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Angaben aus dem Formular „Aktualisierte WISMO-Infos“')
            && $prompt->contains('nur Verbleib und Zustellung')
            && $prompt->contains('soeben beim Paketdienst abgefragt')
            && $prompt->contains('Die Sendung wird heute zugestellt.')
            && $prompt->contains('Regel: Nenne für jede Sendung den `tracking_link`')
            && $prompt->contains('Antwort senden und das Ticket schließen')
            && $prompt->contains('Kunde fragt nach Umleitung an eine Packstation.')
            && $prompt->agent->tools() === []);
    });

    test('a carrier failure still makes a proposal and keeps the ticket open', function () {
        wismoInfoTicket();
        wismoInfoTracker(fails: true);
        CaseAgent::fake([wismoInfoAnswer()]);

        wismoInfoRun()->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Status beim Paketdienst nicht abrufbar')
            && $prompt->contains('bitte selbst in der Sendungsverfolgung prüfen'));
    });

    test('no product, no free text and no return label go along', function () {
        wismoInfoTicket();
        wismoInfoTracker();
        CaseAgent::fake([wismoInfoAnswer()]);
        $label = ReturnLabel::factory()->create(['ticket_number' => '2137942']);

        wismoInfoRun(['produkt_ohne_wissen' => 'Fototasse', 'kein_produktbezug' => '0', 'kontext' => 'GEHEIMER FREITEXT', 'return_label' => $label->id])->assertSessionHasNoErrors();

        $inputs = Analysis::query()->latest()->first()->content['inputs'];
        expect((bool) $inputs['kein_produktbezug'])->toBeTrue()
            ->and($inputs['produkt_ohne_wissen'])->toBe('')
            ->and($inputs['return_label'])->toBeNull()
            ->and($inputs['formular'])->toBe('wismo_info');
        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => ! $prompt->contains('GEHEIMER FREITEXT'));
    });
});

describe('the facts', function () {
    test('a delivered shipment recommends closing the ticket', function () {
        $snapshot = new ShipmentSnapshot(['Bestellung 1: zugestellt'], [new TrackingStatus('DHL', '1', true, 'Zugestellt', delivered: true, shipmentCase: ShipmentHistory::DELIVERED)], [], null, false);

        expect((new WismoUpdateDecision('', $snapshot))->facts())->toContain('Antwort senden und das Ticket schließen');
    });

    test('a return asks the customer to get in touch and mentions the notification mails', function () {
        $returned = new TrackingStatus('DHL', '1', true, 'Rücksendung', shipmentCase: ShipmentHistory::RETURNED_NOT_COLLECTED);
        $snapshot = new ShipmentSnapshot([], [$returned], [['order' => '1', 'mail' => 'Abholhinweis', 'sent_at' => '17.11.2026, 09:00 Uhr']], null, false);
        $facts = (new WismoUpdateDecision('', $snapshot))->facts();

        expect($facts)->toContain('per E-Mail benachrichtigt')->toContain('Versprich keinen erneuten Versand')->toContain('das Ticket schließen');
    });
});

describe('the stored proposal', function () {
    test('the form is prefilled from the stored answers', function () {
        wismoInfoTicket();
        wismoInfoTracker();
        CaseAgent::fake([wismoInfoAnswer()]);

        wismoInfoRun(['wismo_zusatz' => 'Bitte freundlich, Kunde ist verärgert.'])->assertSessionHasNoErrors();
        $id = Analysis::query()->latest()->first()->uuid;

        test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942?analyse='.$id)->assertOk()
            ->assertSee("mode: 'wismo_info',", false)
            ->assertSee('Bitte freundlich, Kunde ist verärgert.')
            ->assertSee('Erstellt mit: Aktualisierte WISMO-Infos', false);
    });
});
