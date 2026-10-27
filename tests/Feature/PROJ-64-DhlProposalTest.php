<?php

use App\Analysis\Agents\CaseAgent;
use App\Models\Analysis;
use App\Models\ReturnLabel;
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
        'services.dhl.url' => 'https://dhl.test/returns', 'services.dhl.token_url' => 'https://dhl.test/token',
        'staff.zammad_tokens' => ['Nele' => 'nele-secret-token'],
    ]);

    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);

    fakeZammad([zammadArticle(['id' => 1, 'subject' => 'Rücksendung', 'message_id' => '<m1@example.org>', 'body' => '<p>Bestellung 7JQ-18SU-DVA4: bitte zurücknehmen.</p>'])], extra: [
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

function proposalLabel(array $overrides = []): ReturnLabel
{
    Storage::disk('return-labels')->put('label.pdf', miniPdf());

    return ReturnLabel::factory()->create([
        'ticket_number' => '2137942', 'order_number' => '7JQ-18SU-DVA4', 'shipment_no' => '999991587211', 'file_path' => 'label.pdf', ...$overrides,
    ]);
}

function proposalAnswer(): array
{
    return [
        'summary' => ['incident' => 'Rücksendung', 'customer_wish' => 'Erstattung'],
        'category' => 'order-process-question', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Ware zurückholen.', 'actions' => [],
        'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'Rückgabe vereinbart.', 'knowledge_gaps' => [],
        'knowledge_ids' => [], 'confidence' => ['level' => 'NIEDRIG', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [],
        'reply' => ['language' => 'Deutsch', 'text' => "Guten Tag,\nwir schicken Ihnen ein Retourenlabel."],
    ];
}

function proposalRun(array $fields = [])
{
    return test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kein_produktbezug' => '1', 'kundengruppe' => 'reseller', 'variante' => 'verlauf', ...$fields]);
}

function proposalPage(TestResponse|Illuminate\Testing\TestResponse $response)
{
    return test()->withCookie('staff_name', 'Nele')->get($response->headers->get('Location'));
}

test('the form offers the label for a loaded order', function () {
    $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')
        ->assertOk()->assertSee('DHL-Retourenlabel erzeugen und im Antwortvorschlag berücksichtigen')->assertDontSee('Hauptstraße');
});

test('the model only learns that a label is attached, the draft gets the fixed steps and the PDF is offered', function () {
    $label = proposalLabel();
    CaseAgent::fake([proposalAnswer()]);

    $page = proposalPage(proposalRun(['return_label' => $label->id]));

    CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('vorfrankiertes DHL-Retourenlabel')
        && ! $prompt->contains('999991587211') && ! $prompt->contains('Hauptstraße') && ! $prompt->contains('Dresden'));

    $page->assertOk()
        ->assertSee('Retouren-Sendungsnummer: 999991587211')
        ->assertSee('Geben Sie das Paket in einer DHL-Filiale oder einem DHL-Paketshop ab')
        ->assertSee('DHL-Retoure-7JQ-18SU-DVA4.pdf')
        ->assertSee($label->id, false);

    expect(Analysis::query()->latest()->first()->content)->not->toBeNull();
});

test('the steps are not added twice once they stand in the saved draft', function () {
    $label = proposalLabel();
    CaseAgent::fake([proposalAnswer()]);
    $response = proposalRun(['return_label' => $label->id]);
    parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

    $text = "Guten Tag,\n\nSo senden Sie die Ware zurück ...\n\nRetouren-Sendungsnummer: 999991587211";
    $this->withCredentials()->withCookie('staff_name', 'Nele')->putJson(route('tickets.analysis.reply', ['number' => '2137942', 'analysis' => $query['analyse']]), ['text' => $text])->assertOk()->assertJsonPath('saved', true);

    preg_match('#<textarea[^>]*x-ref="text"[^>]*>(.*?)</textarea>#s', proposalPage($response)->getContent(), $draft);

    expect(substr_count($draft[1], 'Retouren-Sendungsnummer: 999991587211'))->toBe(1)
        ->and(substr_count($draft[1], 'So senden Sie die Ware zurück'))->toBe(1);
});

test('a proposal without a label has no steps and no attachment', function () {
    CaseAgent::fake([proposalAnswer()]);

    proposalPage(proposalRun())->assertOk()->assertDontSee('Retouren-Sendungsnummer')->assertDontSee('DHL-Retoure-7JQ');

    CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => ! $prompt->contains('DHL-Retourenlabel'));
});

test('a label of another ticket, without a file or unknown is refused and nothing is analysed', function (callable $label) {
    CaseAgent::fake([proposalAnswer()]);

    proposalRun(['return_label' => $label()])->assertRedirect()->assertSessionHas('analysis_error');

    CaseAgent::assertNeverPrompted();
})->with([
    'other ticket' => [fn () => proposalLabel(['ticket_number' => '1111111'])->id],
    'file gone' => [fn () => proposalLabel(['file_path' => null])->id],
    'unclear outcome' => [fn () => proposalLabel(['status' => ReturnLabel::OUTCOME_UNKNOWN])->id],
    'unknown' => [fn () => '2d6c1b0e-4b1c-4a4b-9a67-5e0f2e5a7a11'],
]);

test('the internal procedures offer the label for a proposal that exists already', function () {
    CaseAgent::fake([proposalAnswer()]);

    proposalPage(proposalRun())->assertSee('DHL-Retourenlabel erstellen')->assertSee('Absender der Rücksendung');
});

test('a label ordered after the proposal is tied to it and comes back with the steps and the link', function () {
    CaseAgent::fake([proposalAnswer()]);
    $response = proposalRun();
    parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
    $label = proposalLabel();

    $this->withCredentials()->withCookie('staff_name', 'Nele')
        ->putJson(route('tickets.analysis.return-label', ['number' => '2137942', 'analysis' => $query['analyse']]), ['label' => $label->id])
        ->assertOk()->assertJsonPath('linked', true);

    proposalPage($response)->assertOk()
        ->assertSee('Retouren-Sendungsnummer: 999991587211')
        ->assertSee('als Anhang der Antwort vorgemerkt')
        ->assertSee('Herunterladen');
});

test('a label of another ticket or without a file cannot be tied to a proposal', function (callable $label) {
    CaseAgent::fake([proposalAnswer()]);
    $response = proposalRun();
    parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

    $this->withCredentials()->withCookie('staff_name', 'Nele')
        ->putJson(route('tickets.analysis.return-label', ['number' => '2137942', 'analysis' => $query['analyse']]), ['label' => $label()])
        ->assertNotFound();
})->with([
    'other ticket' => [fn () => proposalLabel(['ticket_number' => '1111111'])->id],
    'file gone' => [fn () => proposalLabel(['file_path' => null])->id],
    'unknown' => [fn () => '2d6c1b0e-4b1c-4a4b-9a67-5e0f2e5a7a11'],
]);

test('the draft shows the download link of the label also without sending from the app', function () {
    config(['staff.zammad_tokens' => ['Nele' => null]]);
    $label = proposalLabel();
    CaseAgent::fake([proposalAnswer()]);

    proposalPage(proposalRun(['return_label' => $label->id]))->assertOk()->assertSee('DHL-Retourenlabel')->assertSee('Herunterladen')->assertSee('nicht automatisch angehängt');
});
