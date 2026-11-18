<?php

use App\Analysis\Tools\ShipmentStatusTool;
use App\Eocs\EocsOrder;
use App\Eocs\EocsShipment;
use App\Lettermint\DeliveryNotices;
use App\Lettermint\LettermintClient;
use App\Tracking\ShipmentTracker;
use App\Tracking\TrackingStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    Http::swap(new Factory);
    Http::preventStrayRequests();
    config(['services.lettermint' => [...config('services.lettermint'), 'url' => 'https://lettermint.test/v1', 'token' => 'team-token', 'project' => null, 'timeout' => 5]]);
});

function lmMessage(string $id, string $subject, string $created = '2026-11-17T08:00:00Z', array $to = ['kunde@example.test'], string $status = 'delivered'): array
{
    return ['id' => $id, 'subject' => $subject, 'status' => $status, 'created_at' => $created, 'to' => array_map(fn ($email) => ['email' => $email, 'name' => null], $to), 'cc' => null, 'bcc' => null];
}

function lmEvent(string $event, string $timestamp): array
{
    return ['event' => $event, 'timestamp' => $timestamp, 'metadata' => []];
}

function lmNotices(): DeliveryNotices
{
    return app(DeliveryNotices::class);
}

function lmOrder(string $number = '4711'): EocsOrder
{
    return new EocsOrder(1, $number, 'looxis.de', 'looxis-de', CarbonImmutable::now()->subDays(5), 'Versendet', 'green', null, [new EocsShipment('DHL', 'Paket', '003404340034123456', CarbonImmutable::now()->subDays(2), false)], [], [], 'https://eocs.test/orders/1');
}

it('counts only messages whose subject contains the whole order number', function () {
    Http::fake([
        'lettermint.test/v1/messages?*' => Http::response(['data' => [
            lmMessage('a', 'Dein Paket kommt heute – Bestellung 12345'),
            lmMessage('b', 'Dein Paket kommt heute – Bestellung 123456'),
            lmMessage('c', 'Dein Paket kommt heute – Bestellung 12345-2'),
        ], 'next_cursor' => null]),
        'lettermint.test/v1/messages/a/events*' => Http::response(['data' => [lmEvent('delivered', '2026-11-17T08:00:05Z')]]),
    ]);

    $found = lmNotices()->forOrder('12345', 'kunde@example.test');

    expect($found['status'])->toBe('ok')->and($found['notices'])->toHaveCount(1)
        ->and($found['notices'][0]->kind)->toBe('today')
        ->and($found['notices'][0]->outcome)->toBe('accepted');

    Http::assertSent(fn (HttpRequest $request) => str_contains(urldecode($request->url()), 'filter[subject]=12345') && str_contains(urldecode($request->url()), 'filter[type]=outbound') && str_contains(urldecode($request->url()), 'filter[delivery_mode]=live') && $request->hasHeader('Authorization', 'Bearer team-token'));
});

it('lists two messages with the same subject separately, the newest first', function () {
    Http::fake([
        'lettermint.test/v1/messages?*' => Http::response(['data' => [
            lmMessage('old', 'Paket kommt heute 4711', '2026-11-10T08:00:00Z'),
            lmMessage('new', 'Paket kommt heute 4711', '2026-11-12T08:00:00Z'),
        ]]),
        'lettermint.test/v1/messages/old/events*' => Http::response(['data' => [lmEvent('delivered', '2026-11-10T08:00:03Z')]]),
        'lettermint.test/v1/messages/new/events*' => Http::response(['data' => [lmEvent('delivered', '2026-11-12T08:00:03Z')]]),
    ]);

    $notices = lmNotices()->forOrder('4711', null)['notices'];

    expect($notices)->toHaveCount(2)->and($notices[0]->at)->toBe('12.11.2026, 09:00:03 Uhr')->and($notices[1]->at)->toBe('10.11.2026, 09:00:03 Uhr');
});

it('reads the next page with all filters and the cursor', function () {
    Http::fake([
        'lettermint.test/v1/messages?*' => function (HttpRequest $request) {
            return str_contains(urldecode($request->url()), 'page[cursor]=p2')
                ? Http::response(['data' => [lmMessage('x', 'Ihr Paket liegt zur Abholung bereit – 4711')], 'next_cursor' => null])
                : Http::response(['data' => [lmMessage('y', 'Rechnung 999')], 'next_cursor' => 'p2']);
        },
        'lettermint.test/v1/messages/x/events*' => Http::response(['data' => [lmEvent('delivered', '2026-11-17T08:00:05Z')]]),
    ]);

    $notices = lmNotices()->forOrder('4711', null)['notices'];

    expect($notices)->toHaveCount(1)->and($notices[0]->kind)->toBe('pickup');
    Http::assertSent(fn (HttpRequest $request) => str_contains(urldecode($request->url()), 'page[cursor]=p2') && str_contains(urldecode($request->url()), 'filter[subject]=4711'));
});

it('evaluates the event history', function (array $events, string $status, string $outcome, ?string $at) {
    [$result, $time] = DeliveryNotices::evaluate($events, $status);

    expect($result)->toBe($outcome)->and($time)->toBe($at);
})->with([
    'only processed' => [[lmEvent('queued', '2026-11-17T08:00:00Z'), lmEvent('processed', '2026-11-17T08:00:01Z')], 'processed', 'handed_over', '17.11.2026, 09:00:01 Uhr'],
    'opened after delivered keeps the delivered time' => [[lmEvent('delivered', '2026-11-17T08:00:05Z'), lmEvent('opened', '2026-11-17T10:00:00Z')], 'opened', 'accepted', '17.11.2026, 09:00:05 Uhr'],
    'bounce after delivered stays visible' => [[lmEvent('delivered', '2026-11-17T08:00:05Z'), lmEvent('hard_bounced', '2026-11-17T08:05:00Z')], 'hard_bounced', 'failed', '17.11.2026, 09:05:00 Uhr'],
    'soft bounce then delivered' => [[lmEvent('soft_bounced', '2026-11-17T08:00:05Z'), lmEvent('delivered', '2026-11-17T08:30:00Z')], 'delivered', 'accepted', '17.11.2026, 09:30:00 Uhr'],
    'only queued' => [[lmEvent('queued', '2026-11-17T08:00:00Z')], 'queued', 'pending', '17.11.2026, 09:00:00 Uhr'],
    'status delivered without event' => [[], 'delivered', 'accepted', null],
    'date change in Berlin' => [[lmEvent('delivered', '2026-11-17T23:30:00Z')], 'delivered', 'accepted', '18.11.2026, 00:30:00 Uhr'],
    'summer time' => [[lmEvent('delivered', '2026-07-01T22:15:00Z')], 'delivered', 'accepted', '02.07.2026, 00:15:00 Uhr'],
]);

it('tells whether the mail went to the ticket sender', function () {
    Http::fake([
        'lettermint.test/v1/messages?*' => Http::response(['data' => [lmMessage('a', 'Paket kommt heute 4711', to: ['andere@example.test'])]]),
        'lettermint.test/v1/messages/a/events*' => Http::response(['data' => [lmEvent('delivered', '2026-11-17T08:00:05Z')]]),
    ]);

    $notice = lmNotices()->forOrder('4711', 'Kunde@Example.test')['notices'][0];

    expect($notice->toTicketSender)->toBeFalse()->and(json_encode($notice->facts()))->not->toContain('example.test')->not->toContain('4711');
});

it('reports a refused access as not available', function () {
    Http::fake(['lettermint.test/*' => Http::response(['message' => 'Unauthenticated.'], 401)]);

    $found = lmNotices()->forOrder('4711', null);

    expect($found['status'])->toBe('unavailable')->and($found['reason'])->toContain('401');
});

it('sends no request without a token', function () {
    config(['services.lettermint.token' => null]);
    Http::fake();

    expect(lmNotices()->enabled())->toBeFalse()->and(app(LettermintClient::class)->configured())->toBeFalse();
    Http::assertNothingSent();
});

it('adds the notices to the shipment status answer and keeps them for the result view', function () {
    Http::fake([
        'lettermint.test/v1/messages?*' => Http::response(['data' => [lmMessage('a', 'Dein Paket kommt heute – Bestellung 4711')]]),
        'lettermint.test/v1/messages/a/events*' => Http::response(['data' => [lmEvent('delivered', '2026-11-17T08:00:05Z')]]),
    ]);
    $tracker = $this->mock(ShipmentTracker::class);
    $tracker->shouldReceive('track')->once()->andReturn(TrackingStatus::unknown('DHL', '003404340034123456', null));
    $tool = new ShipmentStatusTool([lmOrder()], $tracker, '2140400', lmNotices(), 'kunde@example.test');

    expect($tool->noticeRecord())->toBeNull();

    $answer = (string) $tool->handle(new Request);

    expect($answer)->toContain('Benachrichtigungen von LOOXIS per E-Mail')->toContain('"mail":"Paket kommt heute"')->toContain('"proven":true')->toContain('17.11.2026, 09:00:05 Uhr')
        ->and($tool->noticeRecord())->toBe([['order' => '4711', 'status' => 'ok', 'notices' => [['kind' => 'today', 'subject' => 'Dein Paket kommt heute – Bestellung 4711', 'outcome' => 'accepted', 'at' => '17.11.2026, 09:00:05 Uhr', 'failure' => null, 'to_ticket_sender' => true]]]]);
});

it('keeps the analysis going when Lettermint fails', function () {
    Http::fake(['lettermint.test/*' => Http::response([], 500)]);
    $tracker = $this->mock(ShipmentTracker::class);
    $tracker->shouldReceive('track')->once()->andReturn(TrackingStatus::unknown('DHL', '003404340034123456', null));

    $answer = (string) (new ShipmentStatusTool([lmOrder()], $tracker, '2140400', lmNotices(), null))->handle(new Request);

    expect($answer)->toContain('noch nicht bekannt')->toContain('Benachrichtigungen von LOOXIS per E-Mail: nicht abrufbar');
});

it('leaves the answer unchanged without a token', function () {
    config(['services.lettermint.token' => null]);
    Http::fake();
    $tracker = $this->mock(ShipmentTracker::class);
    $tracker->shouldReceive('track')->once()->andReturn(TrackingStatus::unknown('DHL', '003404340034123456', null));
    $tool = new ShipmentStatusTool([lmOrder()], $tracker, '2140400', lmNotices(), null);

    expect((string) $tool->handle(new Request))->not->toContain('Benachrichtigungen')->and($tool->noticeRecord())->toBeNull();
    Http::assertNothingSent();
});

it('shows the notices in the result view and warns without evidence', function () {
    $proven = Blade::render('<x-analysis.delivery-notices :record="$record" />', ['record' => [['order' => '4711', 'status' => 'ok', 'notices' => [['kind' => 'pickup', 'subject' => 'Abholbereit 4711', 'outcome' => 'accepted', 'at' => '17.11.2026, 09:00:05 Uhr', 'failure' => null, 'to_ticket_sender' => true]]]]]);
    $none = Blade::render('<x-analysis.delivery-notices :record="$record" />', ['record' => [['order' => '4711', 'status' => 'ok', 'notices' => []]]]);
    $down = Blade::render('<x-analysis.delivery-notices :record="$record" />', ['record' => [['order' => '4711', 'status' => 'unavailable', 'notices' => []]]]);

    expect($proven)->toContain('Versandbenachrichtigungen')->toContain('Abholhinweis')->toContain('vom Empfänger-Mailserver angenommen')->toContain('17.11.2026, 09:00:05 Uhr')->not->toContain('n8n-Workflow prüfen')
        ->and($none)->toContain('Keine Benachrichtigung zu dieser Bestellung gefunden')->toContain('n8n-Workflow prüfen')
        ->and($down)->toContain('nicht abrufbar')->toContain('n8n-Workflow prüfen')
        ->and(Blade::render('<x-analysis.delivery-notices :record="null" />'))->toBe('');
});

it('has the notification rule in the analysis prompt', function () {
    expect(file_get_contents(resource_path('prompts/analysis.md')))->toContain('- Benachrichtigungen:')->toContain('n8n-Workflow prüfen');
});
