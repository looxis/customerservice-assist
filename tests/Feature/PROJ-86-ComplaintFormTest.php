<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\ComplaintDecision;
use App\Eocs\OrderLookup;
use App\Models\Analysis;
use App\Models\LabelTicket;
use App\Models\ReturnLabel;
use App\Orders\OrderNumber;
use App\Orders\OrderNumberDetector;
use App\Todo\TodoCaseContext;
use App\Zammad\Ticket;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Prompts\AgentPrompt;

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('return-labels');

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
        'staff.zammad_tokens' => ['Nele' => 'nele-secret-token'],
    ]);

    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);

    fakeZammad([zammadArticle(['id' => 1, 'subject' => 'Reklamation', 'message_id' => '<m1@example.org>', 'body' => '<p>Bestellung 7JQ-18SU-DVA4: Der Deckel der Box sitzt schief.</p>'])], extra: [
        'zammad.test/api/v1/users/me' => Http::response(['id' => 501, 'firstname' => 'Nele', 'lastname' => 'Gorka']),
    ]);
    Http::fake([
        'eocs.test/api/v1/orders*' => function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            [$number] = explode(',', (string) ($query['filter']['external_order_id'] ?? ''));

            return Http::response(['data' => $number === '7JQ-18SU-DVA4' ? [returnEocsOrder()] : []]);
        },
    ]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function complaintAnswer(): array
{
    return [
        'summary' => ['incident' => 'Deckel schief', 'customer_wish' => 'Ersatz'],
        'category' => 'complaint', 'case_pattern' => null, 'assessment' => 'berechtigt',
        'recommendation' => 'Neu produzieren.', 'actions' => [],
        'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'Schaden bestätigt.', 'knowledge_gaps' => [],
        'knowledge_ids' => [], 'confidence' => ['level' => 'NIEDRIG', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [],
        'reply' => ['language' => 'Deutsch', 'text' => "Hallo Ralf,\nwir produzieren neu."],
    ];
}

function complaintRun(array $fields = [])
{
    return test()->withCookie('staff_name', 'Nele')->from('/tickets/2137942')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kein_produktbezug' => '1', 'kundengruppe' => 'reseller', 'variante' => 'verlauf', 'formular' => 'reklamation', ...$fields]);
}

test('a justified complaint turns the answers into facts for the model, the shipping word comes from the form', function () {
    CaseAgent::fake([complaintAnswer()]);

    complaintRun(['reklamation_berechtigt' => 'ja', 'retoure' => 'nein', 'reklamationsnummer' => 'r1-028-0861097-0545938', 'versand' => 'zeitnah', 'sendungsnummer_todo' => '1'])->assertSessionHasNoErrors();

    CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Angaben aus dem Reklamationsformular')
        && $prompt->contains('als berechtigt anerkannt')
        && $prompt->contains('muss nicht zurückgesendet werden')
        && $prompt->contains('Die Reklamationsnummer lautet R1-028-0861097-0545938.')
        && $prompt->contains('erfolgt zeitnah: Nenne keinen Tag')
        && ! $prompt->contains('Du darfst „voraussichtlich heute noch“')
        && $prompt->contains('Sendungsnummer wird dem Kunden nach dem Versand mitgeteilt'));
});

test('shipping today or tomorrow is passed on as the employee chose it', function (string $shipping, string $expected) {
    CaseAgent::fake([complaintAnswer()]);

    complaintRun(['reklamation_berechtigt' => 'ja', 'retoure' => 'nein', 'versand' => $shipping])->assertSessionHasNoErrors();

    CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains($expected));
})->with([['heute', 'Du darfst „voraussichtlich heute noch“ schreiben'], ['morgen', 'Schreibe „voraussichtlich morgen“']]);

test('without a claim number the draft is told to keep the placeholder', function () {
    CaseAgent::fake([complaintAnswer()]);

    complaintRun(['reklamation_berechtigt' => 'ja', 'retoure' => 'nein', 'versand' => 'zeitnah'])->assertSessionHasNoErrors();

    CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Verwende im Entwurf den Platzhalter [REKLAMATIONSNUMMER]'));
});

test('with a return no shipping day is named', function () {
    CaseAgent::fake([complaintAnswer()]);

    complaintRun(['reklamation_berechtigt' => 'ja', 'retoure' => 'ja', 'versand' => 'heute'])->assertSessionHasNoErrors();

    CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Nenne keinen Versandtermin für den Ersatz') && ! $prompt->contains('Du darfst „voraussichtlich heute noch“'));
});

test('a complaint that is not justified sends the reason and the goodwill choice, and ignores the free text', function () {
    CaseAgent::fake([complaintAnswer()]);

    complaintRun(['reklamation_berechtigt' => 'nein', 'reklamation_grund' => 'Das Motiv entspricht der Vorschau.', 'kulanz' => 'ja', 'kontext' => 'GEHEIMER FREITEXT', 'versand' => 'heute', 'reklamationsnummer' => 'R1-1-2'])->assertSessionHasNoErrors();

    CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('nicht als berechtigt anerkannt')
        && $prompt->contains('Grund der Ablehnung: Das Motiv entspricht der Vorschau.')
        && $prompt->contains('Kulanzangebot unterbreitet')
        && ! $prompt->contains('GEHEIMER FREITEXT') && ! $prompt->contains('Versand der Neuproduktion') && ! $prompt->contains('R1-1-2'));
});

test('without a goodwill offer the model is told so', function () {
    CaseAgent::fake([complaintAnswer()]);

    complaintRun(['reklamation_berechtigt' => 'nein', 'reklamation_grund' => 'Falsche Datei geliefert.'])->assertSessionHasNoErrors();

    CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Es wird kein Kulanzangebot unterbreitet.'));
});

test('the complaint form needs the choice and for a refusal the reason, and nothing is analysed', function (array $fields, string $field) {
    CaseAgent::fake([complaintAnswer()]);

    complaintRun($fields)->assertSessionHasErrors($field);

    CaseAgent::assertPromptedTimes(0);
    expect(Analysis::query()->count())->toBe(0);
})->with([
    'no choice' => [[], 'reklamation_berechtigt'],
    'refused without a reason' => [['reklamation_berechtigt' => 'nein', 'reklamation_grund' => '  '], 'reklamation_grund'],
    'a claim number of another build' => [['reklamation_berechtigt' => 'ja', 'reklamationsnummer' => '1234'], 'reklamationsnummer'],
    'an unknown shipping choice' => [['reklamation_berechtigt' => 'ja', 'versand' => 'sofort'], 'versand'],
]);

test('the free-text form works as before and is not asked for the complaint fields', function () {
    CaseAgent::fake([complaintAnswer()]);

    complaintRun(['formular' => 'frei', 'kontext' => 'Foto geprüft: Deckel schief.'])->assertSessionHasNoErrors();

    CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Foto geprüft: Deckel schief.') && ! $prompt->contains('Angaben aus dem Reklamationsformular'));
    expect(Analysis::query()->latest()->first())->not->toBeNull();
});

test('a return label is only taken along when the complaint is justified and the product is sent back', function (string $return, bool $labelFact) {
    Storage::disk('return-labels')->put('label.pdf', miniPdf());
    $label = ReturnLabel::factory()->create(['ticket_number' => '2137942', 'order_number' => '7JQ-18SU-DVA4', 'shipment_no' => '999991587211', 'file_path' => 'label.pdf']);
    CaseAgent::fake([complaintAnswer()]);

    complaintRun(['reklamation_berechtigt' => 'ja', 'retoure' => $return, 'return_label' => $label->id])->assertSessionHasNoErrors();

    CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('vorfrankiertes DHL-Retourenlabel') === $labelFact);
    expect(Analysis::query()->latest()->first()->content['inputs']['return_label'])->toBe($labelFact ? $label->id : null);
})->with([['nein', false], ['ja', true]]);

test('the answers are stored with the analysis, only those of the branch that was answered', function () {
    CaseAgent::fake([complaintAnswer()]);

    complaintRun(['reklamation_berechtigt' => 'ja', 'retoure' => 'nein', 'reklamationsnummer' => 'R1-028-0861097-0545938', 'versand' => 'morgen', 'sendungsnummer_todo' => '1', 'reklamation_grund' => 'bleibt nicht'])->assertSessionHasNoErrors();

    $inputs = Analysis::query()->latest()->first()->content['inputs'];

    expect($inputs)->toMatchArray(['formular' => 'reklamation', 'reklamation_berechtigt' => 'ja', 'retoure' => 'nein', 'reklamationsnummer' => 'R1-028-0861097-0545938', 'versand' => 'morgen', 'sendungsnummer_todo' => true, 'kontext' => ''])
        ->and($inputs)->not->toHaveKey('reklamation_grund');
});

describe('the form on the ticket page', function () {
    function classifiedAs(string $decision): void
    {
        LabelTicket::query()->create(['zammad_id' => 51234, 'number' => '2137942', 'text' => 'Hallo', 'state' => 'classified', 'split' => 'training', 'decision' => $decision, 'decision_origin' => 'zweifel']);
    }

    test('an ordinary ticket keeps the free-text form', function () {
        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()->assertSee('Zusätzliche Informationen')->assertDontSee('Reklamationsformular verwenden')->assertDontSee('nach Prüfung als berechtigt anerkannt');
    });

    test('a ticket classified as another category keeps the free-text form', function () {
        classifiedAs('wismo');

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()->assertDontSee('nach Prüfung als berechtigt anerkannt')->assertDontSee('Reklamationsformular verwenden');
    });

    test('a complaint ticket shows the complaint form first and offers the switch to the free text', function () {
        classifiedAs('reklamation');

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()
            ->assertSee('Wird die Reklamation nach Prüfung als berechtigt anerkannt?')
            ->assertSee('Freitext statt Reklamationsformular')
            ->assertSee('Versand voraussichtlich')
            ->assertSee("mode: 'reklamation',", false);
    });
});

describe('the ToDo preselection from the complaint form', function () {
    function complaintContext(array $inputs, string $reply = ''): array
    {
        $order = new OrderNumber('7KA-0QH8-6AF8', app(OrderNumberDetector::class)->normalize('7KA-0QH8-6AF8')->format);
        $ticket = new Ticket('2137942', 51234, 'Titel', 'open', false, null, null, 'Erika Beispiel', null, CarbonImmutable::now(), [], 'https://zammad.test/1');

        return app(TodoCaseContext::class)->build($ticket, [$order], [new OrderLookup($order, [])], [], $reply, $inputs);
    }

    test('a justified complaint with the ToDo ticked preselects it with the number of the form', function () {
        $context = complaintContext(['formular' => 'reklamation', 'reklamation_berechtigt' => 'ja', 'reklamationsnummer' => 'R1-7KA-0QH8-6AF8', 'sendungsnummer_todo' => true]);

        expect($context)->toMatchArray(['preselect' => true, 'kind' => 'claim', 'number' => 'R1-7KA-0QH8-6AF8']);
    });

    test('without a claim number the ToDo is prepared but has no number to look for', function () {
        expect(complaintContext(['formular' => 'reklamation', 'reklamation_berechtigt' => 'ja', 'sendungsnummer_todo' => true]))->toMatchArray(['preselect' => true, 'number' => '']);
    });

    test('a ToDo that is not ticked or a refused complaint is not preselected, even if the text names a number', function (array $inputs) {
        expect(complaintContext(['formular' => 'reklamation', ...$inputs], 'Nummer R1-7KA-0QH8-6AF8')['preselect'])->toBeFalse();
    })->with([
        'ToDo not ticked' => [['reklamation_berechtigt' => 'ja', 'sendungsnummer_todo' => false]],
        'refused' => [['reklamation_berechtigt' => 'nein', 'reklamation_grund' => 'x']],
    ]);

    test('without the complaint form the rule of the reply text stays', function () {
        expect(complaintContext(['formular' => 'frei'], 'Nummer R1-7KA-0QH8-6AF8')['preselect'])->toBeTrue();
    });
});

test('the decision builds no facts for the free-text form', function () {
    expect(ComplaintDecision::fromInput(['formular' => 'frei', 'reklamation_berechtigt' => 'ja']))->toBeNull()
        ->and(ComplaintDecision::fromInput(['formular' => 'reklamation']))->toBeNull();
});
