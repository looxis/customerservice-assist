<?php

use App\Analysis\Agents\ReplyTranslationAgent;
use App\Models\Analysis;
use App\Models\TicketTodo;
use App\Todo\TodoStatus;
use App\Todo\TrackingNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Prompts\AgentPrompt;

const LANG_TRACKING = '00340434161094042557';

const LANG_ITALIAN = '<p>Buongiorno, non ho ricevuto il mio ordine. Vorrei sapere quando arriva. Grazie e saluti</p>';

const LANG_GERMAN = '<p>Guten Tag, ich habe meine Bestellung noch nicht bekommen. Können Sie bitte nachsehen? Vielen Dank und freundliche Grüße</p>';

function langItalianReply(string $reply = "Buongiorno Erika Beispiel,\n\nil suo reclamo R1-7KA-0QH8-6AF8 è stato spedito. Numero di spedizione: ".LANG_TRACKING.' (DHL)'): array
{
    return ['language' => 'Italienisch', 'is_german' => false, 'reply' => $reply, 'signature' => "Cordiali saluti\nNele Gorka\nLOOXIS GmbH"];
}

function langServices(string $customerBody): void
{
    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'staff.zammad_tokens' => ['Nele' => 'nele-secret-token'], 'staff.full_names' => ['Nele' => 'Nele Gorka'],
        'ai.providers.openai.key' => 'sk-test',
    ]);

    $order = [
        'id' => 810001, 'external_order_id' => 'R1-7KA-0QH8-6AF8', 'origin_order_id' => 700001, 'status_name' => 'Verschickt',
        'client' => ['name' => 'LOOXIS'], 'shipping' => [], 'billing' => [], 'invoice' => [],
        'shipments' => ['data' => [['carrier' => ['name' => 'DHL'], 'tracking_no' => LANG_TRACKING, 'created_at' => '12.11.2026 09:00', 'delivered' => null]]],
        'order_items' => ['data' => []],
    ];

    fakeZammad([zammadArticle(['id' => 1, 'message_id' => '<m1@example.org>', 'subject' => 'Ordine', 'body' => $customerBody, 'created_at' => '2026-11-10T09:00:00.000Z'])], extra: [
        'zammad.test/api/v1/users/me' => Http::response(['id' => 501, 'firstname' => 'Nele', 'lastname' => 'Gorka', 'login' => 'nele']),
        'zammad.test/api/v1/tickets/51234' => Http::response(['id' => 51234, 'number' => '2137942']),
        'eocs.test/api/v1/orders*' => Http::response(['data' => [$order], 'meta' => []]),
    ]);
}

function langTodo(array $overrides = []): TicketTodo
{
    return TicketTodo::factory()->create(array_merge([
        'zammad_id' => 51234, 'ticket_number' => '2137942', 'description' => 'Sendungsnummer nachreichen', 'kind' => 'waiting', 'waiting_for' => 'Sendungsnummer',
        'notify_customer' => true, 'notify_hint' => "Guten Tag Erika Beispiel,\n\nIhre Reklamation R1-7KA-0QH8-6AF8 wurde verschickt. Sendungsnummer: [Sendungsnummer] ([Carrier])",
        'external_ref' => 'R1-7KA-0QH8-6AF8', 'created_by' => 'Nele', 'auto_send' => true, 'auto_state' => 'watching', 'customer_language' => null, 'created_at' => '2026-11-11 08:00:00',
    ], $overrides));
}

/**
 * @return array{reply: array<string, mixed>|null, note: array<string, mixed>|null}
 */
function langSent(): array
{
    $puts = Http::recorded(fn (Request $request): bool => $request->method() === 'PUT' && str_ends_with($request->url(), 'tickets/51234'))->map(fn (array $pair): array => $pair[0]->data())->values();

    return [
        'reply' => $puts->first(fn (array $put): bool => ($put['article']['internal'] ?? true) === false),
        'note' => $puts->first(fn (array $put): bool => ($put['article']['internal'] ?? false) === true),
    ];
}

function langRun(): array
{
    return app(TrackingNotifier::class)->run(CarbonImmutable::parse('2026-11-12 16:16:00', 'Europe/Berlin'));
}

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2026-11-12 16:16:00', 'Europe/Berlin'));
});

describe('the automatic reply in the customer\'s language', function () {
    test('translates reply and signature, sends them and keeps the language', function () {
        langServices(LANG_ITALIAN);
        ReplyTranslationAgent::fake([langItalianReply()]);
        $todo = langTodo();

        expect(langRun())->toBe(['checked' => 1, 'sent' => 1]);

        ['reply' => $reply, 'note' => $note] = langSent();
        $todo->refresh();

        expect($reply['article']['body'])->toContain('Numero di spedizione: '.LANG_TRACKING)->toContain('Cordiali saluti')->not->toContain('Mit freundlichen Grüßen')
            ->and($note['article']['body'])->toContain('übersetzt ins Italienisch')
            ->and($todo->status)->toBe(TodoStatus::Done)->and($todo->customer_language)->toBe('Italienisch');
        ReplyTranslationAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('### Kundennachricht') && $prompt->contains('Mit freundlichen Grüßen') && $prompt->contains(LANG_TRACKING));
    });

    test('a customer known to write German gets the German text without a model call', function () {
        langServices(LANG_ITALIAN);
        ReplyTranslationAgent::fake([langItalianReply()]);
        $todo = langTodo(['customer_language' => 'Deutsch']);

        langRun();

        expect(langSent()['reply']['article']['body'])->toContain('Sendungsnummer: '.LANG_TRACKING.' (DHL)')->toContain('Mit freundlichen Grüßen')
            ->and($todo->refresh()->status)->toBe(TodoStatus::Done);
        ReplyTranslationAgent::assertNeverPrompted();
    });

    test('an unknown language with a plainly German message needs no model call', function () {
        langServices(LANG_GERMAN);
        ReplyTranslationAgent::fake([langItalianReply()]);
        $todo = langTodo();

        langRun();

        expect(langSent()['reply']['article']['body'])->toContain('Sendungsnummer: '.LANG_TRACKING)
            ->and($todo->refresh()->customer_language)->toBe('Deutsch');
        ReplyTranslationAgent::assertNeverPrompted();
    });

    test('when the model finds the customer writes German, the German text goes out', function () {
        langServices('<p>Hallo, wo bleibt mein Paket?</p>');
        ReplyTranslationAgent::fake([['language' => 'Deutsch', 'is_german' => true, 'reply' => '', 'signature' => '']]);
        $todo = langTodo();

        langRun();

        expect(langSent()['reply']['article']['body'])->toContain('Sendungsnummer: '.LANG_TRACKING)->toContain('Mit freundlichen Grüßen')
            ->and($todo->refresh()->status)->toBe(TodoStatus::Done);
    });

    test('asks for a look with the translated text when the translation is not safe to send', function (string $reply, string $reason) {
        langServices(LANG_ITALIAN);
        ReplyTranslationAgent::fake([langItalianReply($reply)]);
        $todo = langTodo();

        langRun();

        expect(langSent()['reply'])->toBeNull();
        expect($todo->refresh()->auto_state)->toBe('review')->and($todo->auto_reason)->toContain($reason)
            ->and($todo->notify_hint)->toBe($reply)->and($todo->status)->toBe(TodoStatus::Open);
    })->with([
        'number changed' => ["Buongiorno,\n\nNumero di spedizione: 0034 0434 1610 9404 2557 (DHL)", 'Sendungsnummer'],
        'foreign link' => ["Buongiorno,\n\nNumero: ".LANG_TRACKING.' (DHL). Visiti https://example.com', 'Link'],
        'amount' => ["Buongiorno,\n\nNumero: ".LANG_TRACKING.' (DHL). Rimborso 10 €', 'Geldbetrag'],
        'placeholder' => ["Buongiorno [Name],\n\nNumero: ".LANG_TRACKING.' (DHL)', 'Platzhalter'],
    ]);

    test('a failing translation counts as an error and asks for a look after the third', function () {
        langServices(LANG_ITALIAN);
        ReplyTranslationAgent::fake([['language' => 'Italienisch', 'is_german' => false, 'reply' => '', 'signature' => '']]);
        $todo = langTodo(['auto_errors' => 2]);

        langRun();

        expect(langSent()['reply'])->toBeNull();
        expect($todo->refresh()->auto_state)->toBe('review')->and($todo->auto_reason)->toContain('Übersetzung')->and($todo->auto_errors)->toBe(3);
    });
});

describe('the manual reply in the customer\'s language', function () {
    test('opens the draft translated, with the language and a translated signature', function () {
        langServices(LANG_ITALIAN);
        ReplyTranslationAgent::fake([langItalianReply("Buongiorno,\n\nle comunicheremo il numero di spedizione.")]);
        $todo = langTodo(['auto_send' => false, 'auto_state' => null, 'notify_hint' => "Guten Tag,\n\nwir teilen Ihnen die Sendungsnummer mit."]);

        $this->withCookie('staff_name', 'Nele')->post(route('todos.complete', $todo))->assertSessionHas('success')->assertSessionMissing('error');

        $analysis = Analysis::query()->where('status', 'manual')->sole();
        expect($analysis->content['reply_edit']['text'])->toContain('le comunicheremo')
            ->and($analysis->content['result']['reply']['language'])->toBe('Italienisch')
            ->and($analysis->content['reply_signature'])->toContain('Cordiali saluti')
            ->and($todo->refresh()->customer_language)->toBe('Italienisch');
    });

    test('the ticket page offers the translated signature for sending', function () {
        langServices(LANG_ITALIAN);
        ReplyTranslationAgent::fake([langItalianReply("Buongiorno,\n\nle comunicheremo il numero di spedizione.")]);
        $todo = langTodo(['auto_send' => false, 'auto_state' => null, 'notify_hint' => 'Guten Tag, wir melden uns.']);

        $this->withCookie('staff_name', 'Nele')->post(route('todos.complete', $todo));
        $analysis = Analysis::query()->where('status', 'manual')->sole();

        $this->withCookie('staff_name', 'Nele')->get(route('tickets.show', ['number' => '2137942', 'analyse' => $analysis->uuid]))
            ->assertOk()->assertSee('Cordiali saluti')->assertSee('Italienisch');
    });

    test('keeps the German draft and says so when the translation fails', function () {
        langServices(LANG_ITALIAN);
        ReplyTranslationAgent::fake([['language' => 'Italienisch', 'is_german' => false, 'reply' => '', 'signature' => '']]);
        $todo = langTodo(['auto_send' => false, 'auto_state' => null, 'notify_hint' => 'Guten Tag, wir melden uns.']);

        $this->withCookie('staff_name', 'Nele')->post(route('todos.complete', $todo))->assertSessionHas('error', fn (string $message): bool => str_contains($message, 'nicht in die Sprache des Kunden'));

        $analysis = Analysis::query()->where('status', 'manual')->sole();
        expect($analysis->content['reply_edit']['text'])->toBe('Guten Tag, wir melden uns.')
            ->and($analysis->content)->not->toHaveKey('reply_signature')
            ->and($todo->refresh()->status)->toBe(TodoStatus::NotifyPending);
    });
});
