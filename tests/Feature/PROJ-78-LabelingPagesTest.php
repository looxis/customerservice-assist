<?php

use App\Labeling\LabelAgent;
use App\Models\LabelPrediction;
use App\Models\LabelTicket;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;

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

it('shows the overview to admins with the seven categories', function () {
    $this->withCookie('staff_name', 'Etienne')->get('/beschriftung')->assertOk()->assertSeeText('Noch keine Tickets erfasst');

    lblTicket();

    $this->withCookie('staff_name', 'Etienne')->get('/beschriftung')
        ->assertOk()
        ->assertSeeText('train:wismo')
        ->assertSeeText('train:technischer-support')
        ->assertSeeText('train:produkt-frage');
});

it('lists the sidebar entry only for admins', function () {
    $this->withCookie('staff_name', 'Etienne')->get('/beschriftung')->assertSee(route('labeling.index'));
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
