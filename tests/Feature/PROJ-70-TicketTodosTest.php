<?php

use App\Analysis\AnalysisStore;
use App\Eocs\EocsClaim;
use App\Eocs\EocsOrder;
use App\Eocs\OrderLookup;
use App\Models\Analysis;
use App\Models\TicketTodo;
use App\Models\TrackedTicket;
use App\Orders\OrderNumber;
use App\Orders\OrderNumberDetector;
use App\Settings\SettingsRegistry;
use App\Settings\SettingsStore;
use App\Todo\TodoCaseContext;
use App\Todo\TodoRules;
use App\Todo\TodoStatus;
use App\Zammad\Ticket;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();
    config([
        'services.zammad.url' => 'https://zammad.test',
        'services.zammad.token' => 'tech-token',
        'staff.zammad_tokens' => ['Nele' => 'nele-token'],
    ]);
});

function createTodo(array $fields = [], string $staff = 'Nele'): mixed
{
    TrackedTicket::query()->where('number', '2137942')->exists() || TrackedTicket::factory()->create(['number' => '2137942', 'zammad_id' => 51234]);

    return test()->withCookie('staff_name', $staff)->post(route('tickets.todos.store', ['number' => '2137942']), ['description' => 'Teilerstattung nach Versand', 'kind' => 'direct', ...$fields]);
}

describe('creating', function () {
    test('creates a ToDo at the ticket with the creator as default assignee and a due date from the choice', function () {
        createTodo(['due_in' => '3'])->assertRedirect()->assertSessionHas('success');

        $todo = TicketTodo::query()->sole();

        expect($todo->zammad_id)->toBe(51234)
            ->and($todo->ticket_number)->toBe('2137942')
            ->and($todo->assignee)->toBe('Nele')
            ->and($todo->created_by)->toBe('Nele')
            ->and($todo->status)->toBe(TodoStatus::Open)
            ->and($todo->due_on->toDateString())->toBe(now('Europe/Berlin')->addDays(3)->toDateString());
    });

    test('a ToDo without a choice for the due date has none', function () {
        createTodo()->assertSessionHasNoErrors();

        expect(TicketTodo::query()->sole()->due_on)->toBeNull();
    });

    test('a ToDo that waits for an event needs to say for what', function () {
        createTodo(['kind' => 'waiting'])->assertSessionHasErrors('waiting_for');
        createTodo(['kind' => 'waiting', 'waiting_for' => 'Versand der Ersatzbestellung'])->assertSessionHasNoErrors();

        expect(TicketTodo::query()->sole()->waiting_for)->toBe('Versand der Ersatzbestellung');
    });

    test('rejects an empty description and a name that is not on the list', function () {
        createTodo(['description' => ''])->assertSessionHasErrors('description');
        createTodo(['assignee' => 'Fremder'])->assertSessionHasErrors('assignee');
        createTodo(['due_in' => '5'])->assertSessionHasErrors('due_in');

        expect(TicketTodo::query()->count())->toBe(0);
    });

    test('without a chosen name nothing happens', function () {
        TrackedTicket::factory()->create(['number' => '2137942', 'zammad_id' => 51234]);

        $this->post(route('tickets.todos.store', ['number' => '2137942']), ['description' => 'x', 'kind' => 'direct'])->assertRedirect();

        expect(TicketTodo::query()->count())->toBe(0);
    });

    test('a double click with the same dialog token creates one ToDo', function () {
        createTodo(['request_token' => 'abc']);
        createTodo(['request_token' => 'abc']);

        expect(TicketTodo::query()->count())->toBe(1);
    });

    test('a ticket unknown to the list is looked up in Zammad', function () {
        fakeZammad();

        $this->withCookie('staff_name', 'Nele')->post(route('tickets.todos.store', ['number' => '2137942']), ['description' => 'x', 'kind' => 'direct'])->assertSessionHas('success');

        expect(TicketTodo::query()->sole()->zammad_id)->toBe(51234);
    });
});

describe('the lists', function () {
    test('the start page shows open ToDos by view and never finished ones', function () {
        TicketTodo::factory()->create(['description' => 'Meins offen', 'assignee' => 'Nele']);
        TicketTodo::factory()->create(['description' => 'Von Cara offen', 'assignee' => 'Cara']);
        TicketTodo::factory()->create(['description' => 'Überfälliges von Cara', 'assignee' => 'Cara', 'due_on' => now('Europe/Berlin')->subDay()]);
        TicketTodo::factory()->done()->create(['description' => 'Längst erledigt']);

        $mine = $this->withCookie('staff_name', 'Nele')->get('/')->assertOk()->assertSeeText('Meins offen')->assertDontSeeText('Von Cara offen')->assertDontSeeText('Längst erledigt');
        $mine->assertSeeText('Meine (1)')->assertSeeText('Alle offen (3)')->assertSeeText('Überfällig (1)');

        $this->withCookie('staff_name', 'Nele')->get('/?todos=alle')->assertSeeText('Meins offen')->assertSeeText('Von Cara offen')->assertDontSeeText('Längst erledigt');
        $this->withCookie('staff_name', 'Nele')->get('/?todos=ueberfaellig')->assertSeeText('Überfälliges von Cara')->assertSeeText('Überfällig seit')->assertDontSeeText('Meins offen');
    });

    test('automatic ToDos stay off the start page and out of its counts, the ToDo page lists them', function () {
        TicketTodo::factory()->create(['description' => 'Von Hand zu erledigen', 'assignee' => 'Nele']);
        TicketTodo::factory()->create(['description' => 'Läuft automatisch', 'assignee' => 'Nele', 'auto_send' => true]);

        $this->withCookie('staff_name', 'Nele')->get('/')->assertOk()->assertSeeText('Von Hand zu erledigen')->assertDontSeeText('Läuft automatisch')->assertSeeText('Meine (1)')->assertSeeText('Alle offen (1)');
        $this->withCookie('staff_name', 'Nele')->get(route('todos.index'))->assertSeeText('Läuft automatisch')->assertSeeText('Automatisiert offen');
    });

    test('a ToDo for everyone shows up in the view of each employee', function () {
        TicketTodo::factory()->create(['description' => 'Für alle da', 'assignee' => 'Alle']);
        TicketTodo::factory()->create(['description' => 'Von Cara offen', 'assignee' => 'Cara']);

        $this->withCookie('staff_name', 'Nele')->get('/')->assertSeeText('Für alle da')->assertSeeText('Meine (1)')->assertDontSeeText('Von Cara offen');
        $this->withCookie('staff_name', 'Cara')->get(route('todos.index', ['zustaendig' => 'Cara']))->assertSeeText('Für alle da')->assertSeeText('Von Cara offen');
        createTodo(['assignee' => 'Alle'])->assertSessionHasNoErrors();
        expect(TicketTodo::query()->latest('id')->first()->assignee)->toBe('Alle');
    });

    test('the dialog and the list offer the frequent ToDos', function () {
        $this->withCookie('staff_name', 'Nele')->get(route('todos.index'))->assertSee('Sendungsnummer nachreichen')->assertSee('Nachforschungsauftrag Versand');
    });

    test('the dialog offers the number and the switch for the automatic shipment reply', function () {
        $this->withCookie('staff_name', 'Nele')->get(route('todos.index'))->assertSee('Reklamations- oder Bestellnummer')->assertSee('Mail bei Verfügbarkeit der Sendungsnummer automatisiert versenden');
    });

    test('ToDos stay when the ticket is closed or not in the ticket list', function () {
        TrackedTicket::factory()->create(['number' => '2137942', 'zammad_id' => 51234, 'state' => 'closed', 'is_closed' => true]);
        TicketTodo::factory()->create(['description' => 'Nach dem Schließen', 'zammad_id' => 51234, 'ticket_number' => '2137942']);
        TicketTodo::factory()->create(['description' => 'Ticket nie erfasst', 'zammad_id' => 999]);

        $this->withCookie('staff_name', 'Nele')->get('/?todos=alle')->assertSeeText('Nach dem Schließen')->assertSeeText('Ticket nie erfasst');
    });

    test('an empty view says so', function () {
        $this->withCookie('staff_name', 'Nele')->get('/')->assertOk()->assertSeeText('Keine offenen ToDos');
    });

    test('the ticket list marks tickets with open ToDos', function () {
        $with = TrackedTicket::factory()->create(['title' => 'Mit ToDo']);
        TrackedTicket::factory()->create(['title' => 'Ohne ToDo']);
        TicketTodo::factory()->create(['zammad_id' => $with->zammad_id, 'ticket_number' => $with->number]);

        $html = $this->withCookie('staff_name', 'Nele')->get('/?ansicht=offen')->assertOk()->getContent();

        expect(substr_count($html, 'Zu diesem Ticket gibt es offene ToDos'))->toBe(1);
    });

    test('the ToDo page lists open ToDos and those finished within 12 months, not older ones', function () {
        TicketTodo::factory()->create(['description' => 'Offen und alt', 'created_at' => now()->subYears(2)]);
        TicketTodo::factory()->done()->create(['description' => 'Kürzlich erledigt']);
        TicketTodo::factory()->done()->create(['description' => 'Vor langer Zeit erledigt', 'completed_at' => now()->subMonths(13)]);

        $this->withCookie('staff_name', 'Nele')->get(route('todos.index'))->assertOk()
            ->assertSeeText('Offen und alt')->assertSeeText('Kürzlich erledigt')->assertDontSeeText('Vor langer Zeit erledigt');
        $this->get(route('todos.index', ['zeige' => 'offen']))->assertDontSeeText('Kürzlich erledigt');
        $this->get(route('todos.index', ['zeige' => 'erledigt']))->assertDontSeeText('Offen und alt')->assertSeeText('Kürzlich erledigt');
    });

    test('the ToDo page shows 100 per page and pages on', function () {
        TicketTodo::factory()->count(101)->create();

        $this->get(route('todos.index'))->assertOk()->assertViewHas('rows', fn ($rows): bool => $rows->count() === 100 && $rows->total() === 101 && $rows->lastPage() === 2);
        $this->get(route('todos.index', ['page' => 2]))->assertViewHas('rows', fn ($rows): bool => $rows->count() === 1);
    });
});

describe('changing and finishing', function () {
    test('anyone on the list can change the assignee and the due date, or keep it', function () {
        $todo = TicketTodo::factory()->create(['assignee' => 'Nele', 'due_on' => '2026-11-10']);

        $this->withCookie('staff_name', 'Cara')->patch(route('todos.update', $todo), ['description' => 'Neu', 'assignee' => 'Cara', 'kind' => 'direct', 'due_in' => 'keep'])->assertSessionHas('success');
        expect($todo->refresh()->assignee)->toBe('Cara')->and($todo->description)->toBe('Neu')->and($todo->due_on->toDateString())->toBe('2026-11-10')->and($todo->updated_by)->toBe('Cara');

        $this->withCookie('staff_name', 'Cara')->patch(route('todos.update', $todo), ['description' => 'Neu', 'assignee' => 'Cara', 'kind' => 'direct', 'due_in' => ''])->assertSessionHasNoErrors();
        expect($todo->refresh()->due_on)->toBeNull();
    });

    test('finishes a ToDo with name and time, and refuses a second finish', function () {
        $todo = TicketTodo::factory()->create();

        $this->withCookie('staff_name', 'Cara')->post(route('todos.complete', $todo))->assertSessionHas('success');
        expect($todo->refresh()->status)->toBe(TodoStatus::Done)->and($todo->completed_by)->toBe('Cara')->and($todo->completed_at)->not->toBeNull();

        $this->withCookie('staff_name', 'Nele')->post(route('todos.complete', $todo))->assertSessionHas('error');
        expect($todo->refresh()->completed_by)->toBe('Cara');
    });

    test('reopens a finished ToDo', function () {
        $todo = TicketTodo::factory()->done()->create();

        $this->withCookie('staff_name', 'Nele')->post(route('todos.reopen', $todo))->assertSessionHas('success');

        expect($todo->refresh()->status)->toBe(TodoStatus::Open)->and($todo->completed_at)->toBeNull();
    });

    test('a finished ToDo cannot be changed before it is reopened', function () {
        $todo = TicketTodo::factory()->done()->create(['description' => 'Alt']);

        $this->withCookie('staff_name', 'Nele')->patch(route('todos.update', $todo), ['description' => 'Neu', 'kind' => 'direct'])->assertSessionHas('error');

        expect($todo->refresh()->description)->toBe('Alt');
    });
});

describe('telling the customer', function () {
    test('finishing prepares a reply draft with the hint and keeps the ToDo open for the message', function () {
        fakeZammad();
        $todo = TicketTodo::factory()->create(['zammad_id' => 51234, 'ticket_number' => '2137942', 'notify_customer' => true, 'notify_hint' => 'Sendungsnummer mitteilen']);

        $response = $this->withCookie('staff_name', 'Nele')->post(route('todos.complete', $todo));

        $analysis = Analysis::query()->where('status', 'manual')->sole();
        $response->assertRedirect(route('tickets.show', ['number' => '2137942', 'analyse' => $analysis->uuid]).'#antwort-versenden');
        expect($todo->refresh()->status)->toBe(TodoStatus::NotifyPending)->and($todo->notify_analysis_uuid)->toBe($analysis->uuid)
            ->and($analysis->content['reply_edit']['text'])->toBe('Sendungsnummer mitteilen');
    });

    test('finishing again opens the same draft instead of making another', function () {
        fakeZammad();
        $todo = TicketTodo::factory()->create(['zammad_id' => 51234, 'ticket_number' => '2137942', 'notify_customer' => true]);

        $this->withCookie('staff_name', 'Nele')->post(route('todos.complete', $todo));
        $this->withCookie('staff_name', 'Cara')->post(route('todos.complete', $todo))->assertRedirect();

        expect(Analysis::query()->where('status', 'manual')->count())->toBe(1);
    });

    test('the customer can be skipped, and that is noted', function () {
        $todo = TicketTodo::factory()->create(['notify_customer' => true]);

        $this->withCookie('staff_name', 'Nele')->post(route('todos.complete', $todo), ['without_notification' => '1'])->assertSessionHas('success');

        expect($todo->refresh()->status)->toBe(TodoStatus::Done)->and($todo->notify_skipped)->toBeTrue();
    });

    test('when the ticket cannot be read the ToDo stays open', function () {
        Http::preventStrayRequests();
        Http::fake(['zammad.test/*' => Http::response([], 500)]);
        $todo = TicketTodo::factory()->create(['zammad_id' => 51234, 'ticket_number' => '2137942', 'notify_customer' => true]);

        $this->withCookie('staff_name', 'Nele')->post(route('todos.complete', $todo))->assertSessionHas('error');

        expect($todo->refresh()->status)->toBe(TodoStatus::Open)->and(Analysis::query()->count())->toBe(0);
    });
});

describe('QA: protection', function () {
    test('texts are escaped everywhere they are shown', function () {
        $todo = TicketTodo::factory()->create(['description' => '<script>alert(1)</script>', 'waiting_for' => '<b>x</b>', 'kind' => 'waiting']);

        foreach (['/?todos=alle', route('todos.index')] as $url) {
            $html = $this->withCookie('staff_name', 'Nele')->get($url)->assertOk()->getContent();
            expect($html)->not->toContain('<script>alert(1)</script>')->and($html)->not->toContain('<b>x</b>');
        }

        expect($todo->refresh()->description)->toBe('<script>alert(1)</script>');
    });

    test('the text of a ToDo is stored encrypted', function () {
        TicketTodo::factory()->create(['description' => 'Kundin Erika Beispiel wartet', 'notify_hint' => 'Sendungsnummer']);

        $raw = (array) DB::table('ticket_todos')->first();

        expect(json_encode($raw))->not->toContain('Erika Beispiel')->not->toContain('Sendungsnummer');
    });

    test('changing actions need a chosen name and the form is protected by the token', function () {
        $todo = TicketTodo::factory()->create();

        $this->patch(route('todos.update', $todo), ['description' => 'x', 'kind' => 'direct'])->assertRedirect();
        $this->post(route('todos.complete', $todo))->assertRedirect();
        $this->post(route('todos.reopen', $todo))->assertRedirect();

        expect($todo->refresh()->status)->toBe(TodoStatus::Open)->and($todo->description)->not->toBe('x');

        $html = $this->withCookie('staff_name', 'Nele')->get('/?todos=alle')->getContent();
        TicketTodo::factory()->create();
        expect(substr_count($this->withCookie('staff_name', 'Nele')->get('/?todos=alle')->getContent(), 'name="_token"'))->toBeGreaterThanOrEqual(2);
    });

    test('the filter of the ToDo page refuses unknown values', function () {
        $this->get(route('todos.index', ['zeige' => 'alles', 'zustaendig' => 'Fremder']))->assertSessionHasErrors(['zeige', 'zustaendig']);
    });

    test('a ToDo that does not exist is a 404', function () {
        $this->withCookie('staff_name', 'Nele')->post(route('todos.complete', 999999))->assertNotFound();
    });
});

describe('QA fixes', function () {
    test('a ToDo whose assignee left the list is marked, and editing without a new name keeps the old one', function () {
        $todo = TicketTodo::factory()->create(['assignee' => 'Ehemalige', 'description' => 'Alt']);

        $this->withCookie('staff_name', 'Nele')->get('/?todos=alle')->assertOk()->assertSeeText('Zuständiger nicht mehr in der Liste')->assertSee('– Name wählen –', false);

        $this->withCookie('staff_name', 'Nele')->patch(route('todos.update', $todo), ['description' => 'Neu', 'kind' => 'direct', 'due_in' => 'keep'])->assertSessionHas('success');
        expect($todo->refresh()->assignee)->toBe('Ehemalige')->and($todo->description)->toBe('Neu');

        $this->withCookie('staff_name', 'Nele')->patch(route('todos.update', $todo), ['description' => 'Neu', 'assignee' => 'Cara', 'kind' => 'direct', 'due_in' => 'keep']);
        expect($todo->refresh()->assignee)->toBe('Cara');
    });

    test('a listed assignee is not marked', function () {
        TicketTodo::factory()->create(['assignee' => 'Cara']);

        $this->withCookie('staff_name', 'Nele')->get('/?todos=alle')->assertDontSeeText('Zuständiger nicht mehr in der Liste');
    });

    test('a prepared reply that is no longer the newest proposal is replaced by a new one', function () {
        fakeZammad();
        $todo = TicketTodo::factory()->create(['zammad_id' => 51234, 'ticket_number' => '2137942', 'notify_customer' => true, 'notify_hint' => 'Sendungsnummer mitteilen']);

        $this->withCookie('staff_name', 'Nele')->post(route('todos.complete', $todo));
        $first = $todo->refresh()->notify_analysis_uuid;
        $scope = Analysis::query()->where('uuid', $first)->value('scope_key');
        app(AnalysisStore::class)->putManualReply('2137942', $scope, 'Cara', null, null);

        $response = $this->withCookie('staff_name', 'Nele')->post(route('todos.complete', $todo));

        $second = $todo->refresh()->notify_analysis_uuid;
        expect($second)->not->toBe($first)->and($todo->status)->toBe(TodoStatus::NotifyPending);
        $response->assertRedirect(route('tickets.show', ['number' => '2137942', 'analyse' => $second]).'#antwort-versenden');
        expect(app(AnalysisStore::class)->latest($scope))->toBe($second);
    });

    test('deleted proposals do not leave the ToDo stuck either', function () {
        fakeZammad();
        $todo = TicketTodo::factory()->create(['zammad_id' => 51234, 'ticket_number' => '2137942', 'notify_customer' => true]);

        $this->withCookie('staff_name', 'Nele')->post(route('todos.complete', $todo));
        $first = $todo->refresh()->notify_analysis_uuid;
        Analysis::query()->where('uuid', $first)->delete();

        $this->withCookie('staff_name', 'Nele')->post(route('todos.complete', $todo))->assertRedirect();

        expect($todo->refresh()->notify_analysis_uuid)->not->toBe($first)->not->toBeNull();
    });

    test('a usable prepared reply is opened again, not replaced', function () {
        fakeZammad();
        $todo = TicketTodo::factory()->create(['zammad_id' => 51234, 'ticket_number' => '2137942', 'notify_customer' => true]);

        $this->withCookie('staff_name', 'Nele')->post(route('todos.complete', $todo));
        $first = $todo->refresh()->notify_analysis_uuid;
        $this->withCookie('staff_name', 'Nele')->post(route('todos.complete', $todo));

        expect($todo->refresh()->notify_analysis_uuid)->toBe($first);
    });

    test('"Antwort öffnen" is a button that goes through the ToDo, not a fixed link', function () {
        TicketTodo::factory()->create(['status' => TodoStatus::NotifyPending, 'notify_customer' => true, 'notify_analysis_uuid' => 'gone']);

        $html = $this->withCookie('staff_name', 'Nele')->get('/?todos=alle')->assertOk()->assertSeeText('Antwort öffnen')->getContent();

        expect($html)->not->toContain('#antwort-versenden');
    });
});

describe('the prepared replies', function () {
    test('the texts come from the settings and the case fills in what it knows', function () {
        app(SettingsStore::class)->save('todo_reply_shipping', '[Anrede] [Name], Nr. [Nummer] ([Bestellung/Reklamation]), [Sendungsnummer] bei [Carrier]', 'Etienne');

        $presets = TodoRules::presets(['name' => 'Erika Beispiel', 'kind' => 'claim', 'number' => 'R1-7KA-0QH8-6AF8']);

        expect($presets[0]['hint'])->toBe('Guten Tag Erika Beispiel, Nr. R1-7KA-0QH8-6AF8 (Reklamation), [Sendungsnummer] bei [Carrier]')
            ->and(TodoRules::presets([])[1]['hint'])->toContain("Guten Tag,\n\nwir haben die Erstattung")->toContain('[Nummer]');
    });

    test('only the known placeholders are allowed in the texts', function () {
        $group = SettingsRegistry::group('todo_reply_shipping');

        expect($group->errors($group->default()))->toBe([])
            ->and($group->errors('Guten Tag, [Kundennummer] ist unbekannt'))->not->toBe([])
            ->and($group->errors('Guten Tag, [Name wurde nicht geschlossen und der Text ist lang genug'))->not->toBe([]);
    });
});

describe('the automatic reply switch (PROJ-77)', function () {
    test('a ToDo created in the dialog is never sent automatically: the language is unknown', function () {
        createTodo(['reference' => 'R1-7KA-0QH8-6AF8', 'auto_send' => '1'])->assertSessionHasNoErrors();

        $todo = TicketTodo::query()->latest('id')->first();
        expect($todo->external_ref)->toBe('R1-7KA-0QH8-6AF8')->and($todo->auto_send)->toBeFalse()->and($todo->auto_state)->toBeNull();
    });

    test('switching the automatic reply on or off for a German customer starts or stops the check', function () {
        $todo = TicketTodo::factory()->create(['external_ref' => 'R1-7KA-0QH8-6AF8', 'customer_language' => 'Deutsch', 'auto_send' => true, 'auto_state' => 'watching']);
        $fields = ['description' => 'x', 'assignee' => 'Nele', 'kind' => 'direct', 'due_in' => 'keep', 'reference' => 'R1-7KA-0QH8-6AF8'];

        $this->withCookie('staff_name', 'Nele')->patch(route('todos.update', $todo), [...$fields, 'auto_send' => '0'])->assertSessionHas('success');
        expect($todo->refresh()->auto_send)->toBeFalse()->and($todo->auto_state)->toBeNull();

        $this->withCookie('staff_name', 'Nele')->patch(route('todos.update', $todo), [...$fields, 'auto_send' => '1'])->assertSessionHas('success');
        expect($todo->refresh()->auto_send)->toBeTrue()->and($todo->auto_state)->toBe('watching');
    });

    test('a ToDo that asks for a look keeps that state when it is saved with the switch still on', function () {
        $todo = TicketTodo::factory()->create(['external_ref' => 'R1-7KA-0QH8-6AF8', 'customer_language' => 'Deutsch', 'auto_send' => true, 'auto_state' => 'review', 'auto_reason' => 'Grund']);

        $this->withCookie('staff_name', 'Nele')->patch(route('todos.update', $todo), ['description' => 'x', 'assignee' => 'Nele', 'kind' => 'direct', 'due_in' => 'keep', 'reference' => 'R1-7KA-0QH8-6AF8', 'auto_send' => '1'])->assertSessionHas('success');

        expect($todo->refresh()->auto_state)->toBe('review')->and($todo->auto_reason)->toBe('Grund');
    });
});

describe('the number of the shipment', function () {
    function caseContext(string $reply, array $claims = [], array $ticketClaims = []): array
    {
        $order = new OrderNumber('7KA-0QH8-6AF8', app(OrderNumberDetector::class)->normalize('7KA-0QH8-6AF8')->format);
        $eocsOrder = new EocsOrder(1, '7KA-0QH8-6AF8', null, null, null, null, null, null, [], [], array_map(fn (string $number): EocsClaim => new EocsClaim(1, $number, null, null, null, 'https://eocs.test/1'), $claims), 'https://eocs.test/1', [], null);
        $ticket = new Ticket('2137942', 51234, 'Titel', 'open', false, null, null, 'Erika Beispiel', null, CarbonImmutable::now(), [], 'https://zammad.test/1');

        return app(TodoCaseContext::class)->build($ticket, [$order], [new OrderLookup($order, [$eocsOrder])], $ticketClaims, $reply);
    }

    test('the claim number named in the reply text wins and preselects the ToDo', function () {
        $context = caseContext('Der Auftrag läuft unter der Nummer R1-7KA-0QH8-6AF8.');

        expect($context)->toMatchArray(['number' => 'R1-7KA-0QH8-6AF8', 'kind' => 'claim', 'preselect' => true, 'name' => 'Erika Beispiel']);
    });

    test('without a claim in the text the newest claim from EOCS is used and nothing is preselected', function () {
        $context = caseContext('Wir prüfen das.', ['R1-7KA-0QH8-6AF8', 'R2-7KA-0QH8-6AF8']);

        expect($context)->toMatchArray(['number' => 'R2-7KA-0QH8-6AF8', 'kind' => 'claim', 'preselect' => false]);
    });

    test('without any claim the order number is used', function () {
        expect(caseContext('Wir prüfen das.'))->toMatchArray(['number' => '7KA-0QH8-6AF8', 'kind' => 'order', 'preselect' => false]);
    });
});
