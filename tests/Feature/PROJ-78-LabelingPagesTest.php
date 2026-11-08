<?php

use App\Labeling\LabelAgent;
use App\Models\LabelPrediction;
use App\Models\LabelTicket;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();

    config(['staff.admins' => ['Etienne'], 'services.zammad.url' => 'https://zammad.test']);
});

/**
 * @param  array<string, mixed>  $overrides
 */
function lblTicket(array $overrides = [], array $prediction = []): LabelTicket
{
    static $zammadId = 9000;
    $zammadId++;

    $ticket = LabelTicket::query()->create(array_merge([
        'zammad_id' => $zammadId,
        'number' => (string) (2140000 + $zammadId),
        'group_name' => 'Kundenservice',
        'article_id' => $zammadId,
        'text' => 'Wo bleibt mein Paket? Bestellung vom 20.10.',
        'zammad_created_at' => now()->subDay(),
        'state' => 'classified',
        'split' => 'training',
        'last_activity_at' => now(),
    ], $overrides));

    if ($prediction !== [] || ($overrides['state'] ?? 'classified') === 'classified') {
        $ticket->predictions()->create(array_merge([
            'category' => 'wismo', 'unclear' => false, 'multiple_concerns' => false, 'confidence' => 0.95,
            'reason' => 'Lieferstatus.', 'evidence' => 'Wo bleibt mein Paket?', 'queue' => null,
            'model' => 'gpt-test', 'prompt_version' => 'labeling-1',
        ], $prediction));
    }

    return $ticket->refresh();
}

it('keeps the labeling pages from everyone but admins', function (string $url) {
    $this->withCookie('staff_name', 'Nele')->get($url)->assertForbidden();
})->with(['/beschriftung', '/beschriftung/pruefen', '/beschriftung/tickets']);

it('shows the overview to admins with the ten categories', function () {
    $this->withCookie('staff_name', 'Etienne')->get('/beschriftung')->assertOk()->assertSeeText('Noch keine Tickets erfasst');

    lblTicket();

    $this->withCookie('staff_name', 'Etienne')->get('/beschriftung')
        ->assertOk()
        ->assertSeeText('train:wismo')
        ->assertSeeText('train:technischer-support')
        ->assertSeeText('train:produkt-frage')
        ->assertSeeText('train:adressaenderung')
        ->assertSeeText('train:zahlung')
        ->assertSeeText('Spam');
});

it('lists the sidebar entry only for admins', function () {
    $this->withCookie('staff_name', 'Etienne')->get('/beschriftung')->assertSee(route('labeling.index'))->assertSeeText('Ticket-Klassifikation');
    $this->withCookie('staff_name', 'Nele')->get('/')->assertDontSee(route('labeling.index'));
});

it('hides the suggestion for tickets of the test set', function () {
    lblTicket(['split' => 'test']);

    $this->withCookie('staff_name', 'Etienne')->get('/beschriftung/pruefen?warteschlange=test')
        ->assertOk()
        ->assertSeeText('Der Vorschlag ist verborgen')
        ->assertDontSee('data-key="Enter"', false);
});

it('shows a ticket of the review queue with its suggestion', function () {
    lblTicket([], ['queue' => 'zweifel', 'unclear' => true]);

    $this->withCookie('staff_name', 'Etienne')->get('/beschriftung/pruefen')
        ->assertOk()
        ->assertSeeText('Unklar')
        ->assertSee('data-key="Enter"', false);
});

it('gives every category its own key, spam the key s and none the key 0', function () {
    lblTicket([], ['queue' => 'zweifel']);

    $this->withCookie('staff_name', 'Etienne')->get('/beschriftung/pruefen')
        ->assertOk()
        ->assertSee('data-key="9"', false)
        ->assertSee('data-key="s"', false)
        ->assertSee('data-key="0"', false)
        ->assertDontSee('data-key="10"', false);
});

it('shows the previous decision of a ticket in the queue abweichung', function () {
    lblTicket(['decision' => 'storno', 'decision_origin' => 'zweifel'], ['category' => 'adressaenderung', 'queue' => 'abweichung']);

    $this->withCookie('staff_name', 'Etienne')->get('/beschriftung/pruefen?warteschlange=abweichung')
        ->assertOk()
        ->assertSeeText('Bisherige Entscheidung')
        ->assertSeeText('Storno / Änderung der Bestellung')
        ->assertSeeText('Adressänderung');
});

it('shows subject and sender on the review page', function () {
    lblTicket(['subject' => 'Produktanfrage des Amazon-Kunden', 'sender' => 'Cristiano <cristiano@marketplace.amazon.it>'], ['queue' => 'konfidenz']);

    $this->withCookie('staff_name', 'Etienne')->get('/beschriftung/pruefen')
        ->assertOk()
        ->assertSeeText('Produktanfrage des Amazon-Kunden')
        ->assertSee('cristiano@marketplace.amazon.it');
});

it('keeps the decision buttons enabled while submitting so the chosen category is sent', function () {
    lblTicket([], ['queue' => 'zweifel']);

    $this->withCookie('staff_name', 'Etienne')->get('/beschriftung/pruefen')
        ->assertOk()
        ->assertDontSee('x-bind:disabled="busy"', false);
});

it('says so when a queue is empty', function () {
    $this->withCookie('staff_name', 'Etienne')->get('/beschriftung/pruefen?warteschlange=storno')->assertOk()->assertSeeText('nichts mehr zu prüfen');
});

it('filters the ticket list by category and set', function () {
    lblTicket(['text' => 'Wo bleibt mein Paket?']);
    lblTicket(['text' => 'Der Konfigurator lädt nicht.'], ['category' => 'technischer-support']);

    $this->withCookie('staff_name', 'Etienne')->get('/beschriftung/tickets?kategorie=wismo')->assertOk()->assertSeeText('Wo bleibt mein Paket')->assertDontSeeText('Konfigurator lädt');
    $this->withCookie('staff_name', 'Etienne')->get('/beschriftung/tickets?zuordnung=test')->assertOk()->assertSeeText('Kein Ticket entspricht');
});

it('shows a ticket whose text was purged', function () {
    lblTicket(['text' => null]);

    $this->withCookie('staff_name', 'Etienne')->get('/beschriftung/tickets')->assertOk()->assertSeeText('Text gelöscht');
});

it('keeps non admins from every action', function () {
    $ticket = lblTicket();

    $this->withCookie('staff_name', 'Nele')->post('/beschriftung/laeufe', ['kind' => 'fetch'])->assertForbidden();
    $this->withCookie('staff_name', 'Nele')->post("/beschriftung/tickets/{$ticket->id}/entscheidung", ['category' => 'wismo'])->assertForbidden();
    $this->withCookie('staff_name', 'Nele')->post('/beschriftung/kategorien/wismo/freigabe')->assertForbidden();
    $this->withCookie('staff_name', 'Nele')->patch('/beschriftung/einstellungen', [])->assertForbidden();
});

it('has an agent whose schema lists all categories and none', function () {
    $schema = (new LabelAgent('x'))->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKeys(['category', 'unclear', 'multiple_concerns', 'confidence', 'reason', 'evidence'])
        ->and(LabelPrediction::query()->count())->toBe(0);
});

it('removes a ticket from the tagging and keeps only a marker that later retrievals skip', function () {
    $ticket = lblTicket(['subject' => 'Test', 'sender' => 'etienne@example.test']);

    $this->withCookie('staff_name', 'Etienne')
        ->post(route('labeling.tickets.exclude', $ticket), ['queue' => 'test'])
        ->assertRedirect()
        ->assertSessionHas('success');

    $ticket->refresh();
    expect($ticket->state)->toBe(LabelTicket::EXCLUDED)
        ->and($ticket->text)->toBeNull()
        ->and($ticket->predictions()->count())->toBe(0);

    $this->withCookie('staff_name', 'Etienne')->get('/beschriftung/tickets')->assertOk()->assertSeeText('Kein Ticket entspricht diesem Filter.');
});

it('deletes the ticket in Zammad before removing it when asked to', function () {
    config(['staff.zammad_tokens' => ['Etienne' => 'secret-token']]);
    $ticket = lblTicket();
    Http::fake(['zammad.test/api/v1/tickets/'.$ticket->zammad_id => Http::response('', 200)]);

    $this->withCookie('staff_name', 'Etienne')
        ->post(route('labeling.tickets.exclude', $ticket), ['delete_in_zammad' => '1'])
        ->assertSessionHas('success');

    Http::assertSent(fn ($request) => $request->method() === 'DELETE' && str_ends_with($request->url(), '/tickets/'.$ticket->zammad_id));
    expect($ticket->refresh()->state)->toBe(LabelTicket::EXCLUDED);
});

it('keeps the ticket when Zammad refuses to delete it', function () {
    config(['staff.zammad_tokens' => ['Etienne' => 'secret-token']]);
    $ticket = lblTicket();
    Http::fake(['zammad.test/*' => Http::response('', 403)]);

    $this->withCookie('staff_name', 'Etienne')
        ->post(route('labeling.tickets.exclude', $ticket), ['delete_in_zammad' => '1'])
        ->assertSessionHas('error');

    expect($ticket->refresh()->state)->toBe('classified');
});

it('keeps non-admins from removing tickets', function () {
    $ticket = lblTicket();

    $this->withCookie('staff_name', 'Nele')->post(route('labeling.tickets.exclude', $ticket))->assertForbidden();
});
