<?php

use App\Analysis\Agents\CaseAgent;
use App\Models\TicketTodo;
use App\Todo\TodoService;
use App\Todo\TodoStatus;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
        'staff.zammad_tokens' => ['Nele' => 'nele-secret-token'],
    ]);

    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);
    $this->articles = [zammadArticle(['id' => 1, 'subject' => 'Motiv erscheint nicht', 'message_id' => '<m1@example.org>', 'body' => '<p>Das Motiv fehlt.</p>'])];
    $this->putStatuses = [];
    $this->putBodies = [];
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
    fakeZammad(extra: [
        'zammad.test/api/v1/ticket_articles/by_ticket/*' => fn () => Http::response($this->articles),
        'zammad.test/api/v1/users/me' => Http::response(['id' => 501, 'firstname' => 'Nele', 'lastname' => 'Gorka', 'login' => 'nele']),
        'zammad.test/api/v1/tickets/51234' => function (Request $request) {
            $status = array_shift($this->putStatuses) ?? 200;
            $this->putBodies[] = $request->data();

            if ($status === 200 && ($request['article']['internal'] ?? false) === false) {
                $this->articles[] = zammadArticle(['id' => 9, 'sender' => 'Agent', 'created_by_id' => 501, 'from' => 'Nele Gorka', 'body' => $request['article']['body'], 'created_at' => now()->toIso8601String()]);
            }

            return Http::response(['id' => 51234, 'number' => '2137942'], $status);
        },
    ]);
});

afterEach(function () {
    cleanUpKnowledgeBases();
});

function todoSendAnalysis(): string
{
    CaseAgent::fake([[
        'summary' => ['incident' => 'Motiv fehlt', 'customer_wish' => 'Ersatz'],
        'category' => 'order-process-question', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Antworten.', 'actions' => [],
        'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'Keine Regel.', 'knowledge_gaps' => [],
        'knowledge_ids' => [], 'confidence' => ['level' => 'NIEDRIG', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag, wir prüfen das.'],
    ]]);
    $location = test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kein_produktbezug' => '1', 'kundengruppe' => 'reseller', 'variante' => 'verlauf'])->headers->get('Location');
    parse_str((string) parse_url((string) $location, PHP_URL_QUERY), $query);

    return $query['analyse'];
}

function todoSend(string $id, array $fields = []): TestResponse
{
    return test()->withCredentials()->withCookie('staff_name', 'Nele')->postJson(route('tickets.analysis.send', ['number' => '2137942', 'analysis' => $id]), [
        'to' => 'erika@example.org', 'subject' => 'Re: Motiv erscheint nicht', 'text' => 'Guten Tag, wir prüfen das.', 'signature' => 'Nele', 'after' => 'close', ...$fields,
    ]);
}

/**
 * @return list<Request>
 */
function todoPuts(): array
{
    return Http::recorded(fn (Request $request): bool => $request->method() === 'PUT' && str_ends_with($request->url(), 'tickets/51234'))->map(fn (array $pair): Request => $pair[0])->values()->all();
}

describe('ToDos with the reply', function () {
    test('keeps the claim number the shipment belongs to at the ToDo', function () {
        $id = todoSendAnalysis();

        todoSend($id, ['todos' => [['description' => 'Sendungsnummer nachreichen', 'kind' => 'waiting', 'waiting_for' => 'Sendungsnummer', 'reference' => 'R1-7KA-0QH8-6AF8']]])->assertOk();

        expect(TicketTodo::query()->first()->external_ref)->toBe('R1-7KA-0QH8-6AF8');
    });

    test('starts the automatic check for a German customer when the switch is on (PROJ-77)', function () {
        $id = todoSendAnalysis();

        todoSend($id, ['todos' => [
            ['description' => 'Sendungsnummer nachreichen', 'kind' => 'waiting', 'waiting_for' => 'Sendungsnummer', 'reference' => 'R1-7KA-0QH8-6AF8', 'auto_send' => '1'],
            ['description' => 'Ohne Haken', 'kind' => 'direct', 'reference' => 'R1-7KA-0QH8-6AF8', 'auto_send' => '0'],
            ['description' => 'Ohne Nummer', 'kind' => 'direct', 'auto_send' => '1'],
        ]])->assertOk();

        $todos = TicketTodo::query()->orderBy('id')->get();
        expect($todos[0]->auto_send)->toBeTrue()->and($todos[0]->auto_state)->toBe('watching')->and($todos[0]->customer_language)->toBe('Deutsch')
            ->and($todos[1]->auto_send)->toBeFalse()->and($todos[1]->auto_state)->toBeNull()
            ->and($todos[2]->auto_send)->toBeFalse();
    });

    test('sends the reply, closes the ticket and creates the ToDos', function () {
        $id = todoSendAnalysis();

        todoSend($id, ['todos' => [
            ['description' => 'Teilerstattung nach Versand', 'assignee' => 'Nele', 'due_in' => '3', 'kind' => 'waiting', 'waiting_for' => 'Versand der Ersatzbestellung', 'notify_customer' => '1', 'notify_hint' => 'Sendungsnummer mitteilen'],
            ['description' => 'Zweite Aufgabe', 'kind' => 'direct', 'notify_customer' => '0'],
        ]])->assertOk()->assertJson(['sent' => true, 'todos' => 2, 'todos_failed' => false, 'note_failed' => false]);

        expect(todoPuts())->toHaveCount(1)->and(todoPuts()[0]['state'])->toBe('closed');

        $first = TicketTodo::query()->orderBy('id')->first();
        expect(TicketTodo::query()->count())->toBe(2)
            ->and($first->zammad_id)->toBe(51234)->and($first->ticket_number)->toBe('2137942')
            ->and($first->kind->value)->toBe('waiting')->and($first->notify_customer)->toBeTrue()->and($first->source)->toBe('reply')->and($first->source_analysis_uuid)->toBe($id)
            ->and($first->due_on->toDateString())->toBe(now('Europe/Berlin')->addDays(3)->toDateString());
    });

    test('a failed send creates no ToDos and says so', function () {
        $id = todoSendAnalysis();
        $this->putStatuses = [422];

        todoSend($id, ['todos' => [['description' => 'Aufgabe', 'kind' => 'direct']]])->assertStatus(502);

        expect(TicketTodo::query()->count())->toBe(0);
    });

    test('repeating the request creates no second reply and no second ToDo', function () {
        $id = todoSendAnalysis();
        $fields = ['todos' => [['description' => 'Aufgabe', 'kind' => 'direct']]];

        todoSend($id, $fields)->assertOk();
        todoSend($id, $fields)->assertStatus(409);

        expect(TicketTodo::query()->count())->toBe(1)->and(todoPuts())->toHaveCount(1);
    });

    test('a ToDo row without a description or with a name outside the list is refused before anything is sent', function () {
        $id = todoSendAnalysis();

        todoSend($id, ['todos' => [['description' => '', 'kind' => 'direct']]])->assertStatus(422)->assertJsonValidationErrors('todos.0.description');
        todoSend($id, ['todos' => [['description' => 'x', 'kind' => 'direct', 'assignee' => 'Fremder']]])->assertStatus(422)->assertJsonValidationErrors('todos.0.assignee');
        todoSend($id, ['todos' => [['description' => 'x', 'kind' => 'waiting']]])->assertStatus(422)->assertJsonValidationErrors('todos.0.waiting_for');

        expect(todoPuts())->toHaveCount(0)->and(TicketTodo::query()->count())->toBe(0);
    });

    test('the sent reply stays valid when the ToDos cannot be created', function () {
        $id = todoSendAnalysis();
        $this->partialMock(TodoService::class, fn ($mock) => $mock->shouldReceive('createFromReply')->andThrow(new RuntimeException('ToDo storage down')));

        todoSend($id, ['todos' => [['description' => 'Aufgabe', 'kind' => 'direct']]])->assertOk()->assertJson(['sent' => true, 'todos_failed' => true, 'todos' => 0]);
    });
});

describe('keeping the ticket open with an internal note', function () {
    test('sends the reply without closing and adds the note as a non-public article', function () {
        $id = todoSendAnalysis();

        todoSend($id, ['after' => 'keep', 'note' => 'Gutschrift nach Versand veranlassen'])->assertOk()->assertJson(['sent' => true, 'note_failed' => false]);

        $puts = todoPuts();
        expect($puts)->toHaveCount(2)
            ->and($puts[0]['state'])->toBe('open')->and($puts[0]['article']['internal'])->toBeFalse()
            ->and($puts[1]['article']['internal'])->toBeTrue()->and($puts[1]['article']['type'])->toBe('note')->and($puts[1]['article']['body'])->toBe('Gutschrift nach Versand veranlassen')
            ->and(isset($puts[1]['state']))->toBeFalse();
    });

    test('a note that fails is reported and the reply is not sent again', function () {
        $id = todoSendAnalysis();
        $this->putStatuses = [200, 422];

        todoSend($id, ['after' => 'keep', 'note' => 'Notiz'])->assertOk()->assertJson(['sent' => true, 'note_failed' => true]);
        todoSend($id, ['after' => 'keep', 'note' => 'Notiz'])->assertStatus(409);

        expect(todoPuts())->toHaveCount(2);
    });

    test('the reminder still works next to ToDos', function () {
        $id = todoSendAnalysis();

        todoSend($id, ['after' => 'reminder', 'days' => 3, 'todos' => [['description' => 'Aufgabe', 'kind' => 'direct']]])->assertOk();

        expect(todoPuts()[0]['state'])->toBe('pending reminder')->and(TicketTodo::query()->count())->toBe(1);
    });
});

describe('the message after finishing a ToDo', function () {
    test('sending the prepared reply completes the ToDo', function () {
        $todo = TicketTodo::factory()->create(['zammad_id' => 51234, 'ticket_number' => '2137942', 'notify_customer' => true, 'notify_hint' => 'Sendungsnummer mitteilen']);
        $this->withCookie('staff_name', 'Nele')->post(route('todos.complete', $todo));
        $id = $todo->refresh()->notify_analysis_uuid;

        expect($todo->status)->toBe(TodoStatus::NotifyPending);

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942?analyse='.$id)->assertOk()->assertSeeText('Sendungsnummer mitteilen');
        todoSend($id, ['text' => 'Ihre Sendungsnummer lautet 123.'])->assertOk();

        expect($todo->refresh()->status)->toBe(TodoStatus::Done);
    });

    test('a failed send leaves the ToDo waiting for the message', function () {
        $todo = TicketTodo::factory()->create(['zammad_id' => 51234, 'ticket_number' => '2137942', 'notify_customer' => true]);
        $this->withCookie('staff_name', 'Nele')->post(route('todos.complete', $todo));
        $this->putStatuses = [422];

        todoSend($todo->refresh()->notify_analysis_uuid)->assertStatus(502);

        expect($todo->refresh()->status)->toBe(TodoStatus::NotifyPending);
    });
});

describe('the reply form', function () {
    test('offers the ToDo rows, the internal note and "Ticket offen lassen"', function () {
        $id = todoSendAnalysis();

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942?analyse='.$id)->assertOk()
            ->assertSeeText('ToDos zu diesem Ticket')->assertSeeText('Interne Notiz')->assertSeeText('Ticket offen lassen')->assertSeeText('ToDo hinzufügen');
    });
});
