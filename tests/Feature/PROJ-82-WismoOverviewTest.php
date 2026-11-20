<?php

use App\Models\WismoRun;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->withoutVite();
    config(['cache.default' => 'array', 'staff.admins' => ['Etienne']]);
    Cache::flush();
});

test('only admins see the overview', function () {
    $this->withCookie('staff_name', 'Cara')->get(route('wismo-runs.index'))->assertForbidden();
    $this->withCookie('staff_name', 'Cara')->get(route('wismo-runs.show', WismoRun::factory()->create()))->assertForbidden();
});

test('an admin sees the runs and can filter by result', function () {
    WismoRun::factory()->create(['ticket_number' => '2140001', 'result' => WismoRun::SENT]);
    WismoRun::factory()->create(['ticket_number' => '2140002', 'result' => WismoRun::NOTE, 'reason' => 'Keine Bestellnummer im Ticket gefunden.']);
    WismoRun::factory()->create(['ticket_number' => '2140003', 'result' => WismoRun::SENT, 'delivery_problem' => true, 'delivery_problem_reason' => 'Rücksendung']);

    $this->withCookie('staff_name', 'Etienne')->get(route('wismo-runs.index'))->assertOk()
        ->assertSee('Ticket#2140001')->assertSee('Ticket#2140002')->assertSee('Keine Bestellnummer im Ticket gefunden.')->assertSee('Zustellproblem')->assertSee('Alle (3)');

    $this->withCookie('staff_name', 'Etienne')->get(route('wismo-runs.index', ['ergebnis' => 'note']))->assertOk()
        ->assertSee('Ticket#2140002')->assertDontSee('Ticket#2140001');
});

test('the overview shows the case of the shipment and the automatic closing, and filters by both (PROJ-99)', function () {
    WismoRun::factory()->create(['ticket_number' => '2140101', 'shipment_case' => 'delivered', 'auto_closed' => true]);
    WismoRun::factory()->create(['ticket_number' => '2140102', 'shipment_case' => 'returned_address', 'auto_closed' => true, 'delivery_problem' => true]);
    WismoRun::factory()->create(['ticket_number' => '2140103', 'shipment_case' => 'special', 'auto_closed' => false, 'delivery_problem' => true]);
    WismoRun::factory()->create(['ticket_number' => '2140104']);

    $this->withCookie('staff_name', 'Etienne')->get(route('wismo-runs.index'))->assertOk()
        ->assertSee('automatisiert geschlossen')->assertSee('Automatisiert geschlossen (2)')
        ->assertSee('Zugestellt (1)')->assertSee('Rücksendung: Adresse (1)')->assertSee('Sonderfall (1)')->assertSee('Alle Fälle');

    $this->withCookie('staff_name', 'Etienne')->get(route('wismo-runs.index', ['fall' => 'returned_address']))->assertOk()
        ->assertSee('Ticket#2140102')->assertDontSee('Ticket#2140101')->assertDontSee('Ticket#2140103');

    $this->withCookie('staff_name', 'Etienne')->get(route('wismo-runs.index', ['geschlossen' => '1']))->assertOk()
        ->assertSee('Ticket#2140101')->assertSee('Ticket#2140102')->assertDontSee('Ticket#2140103')->assertDontSee('Ticket#2140104');
});

test('a run shows the milestones of the shipment', function () {
    $run = WismoRun::factory()->create(['shipment_case' => 'returned_not_collected', 'auto_closed' => true, 'shipment_history' => [['tracking_number' => '00340434171079292013', 'case' => 'returned_not_collected', 'milestones' => [['date' => '13.11.2026', 'text' => 'Lagerfrist nicht abgeholt.']]]]]);

    $this->withCookie('staff_name', 'Etienne')->get(route('wismo-runs.show', $run))->assertOk()
        ->assertSee('Rücksendung: nicht angetroffen/abgeholt')->assertSee('automatisiert geschlossen')->assertSee('13.11.2026')->assertSee('Lagerfrist nicht abgeholt.');
});

test('an empty overview explains how runs come about', function () {
    $this->withCookie('staff_name', 'Etienne')->get(route('wismo-runs.index'))->assertOk()->assertSee('Noch keine WISMO-Antwort bearbeitet');
});

test('a run shows the data and the texts that were sent', function () {
    $run = WismoRun::factory()->create(['reply_text' => "Ihre Sendungsnummer lautet: 123\nGrüße", 'reply_text_de' => 'Deutsche Fassung', 'tracking_number' => '123']);

    $this->withCookie('staff_name', 'Etienne')->get(route('wismo-runs.show', $run))->assertOk()->assertSee('Gesendete Antwort')->assertSee('Ihre Sendungsnummer lautet: 123')->assertSee('Deutsche Fassung');
});

test('the texts are emptied after 90 days, the result stays', function () {
    $old = WismoRun::factory()->create(['reply_text' => 'alt', 'reply_text_de' => 'alt', 'created_at' => now()->subDays(91)]);
    $new = WismoRun::factory()->create(['reply_text' => 'neu', 'reply_text_de' => 'neu']);

    $this->artisan('wismo:purge')->assertSuccessful();

    expect($old->refresh()->reply_text)->toBeNull()->and($old->result)->toBe(WismoRun::SENT)->and($new->refresh()->reply_text)->toBe('neu');
});

test('the texts are stored encrypted', function () {
    $run = WismoRun::factory()->create(['reply_text' => 'Geheim']);

    expect(DB::table('wismo_runs')->where('id', $run->id)->value('reply_text'))->not->toContain('Geheim');
});
