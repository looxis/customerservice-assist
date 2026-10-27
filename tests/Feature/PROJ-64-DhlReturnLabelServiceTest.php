<?php

use App\Dhl\DhlClient;
use App\Dhl\DhlException;
use App\Dhl\DhlProblem;
use App\Models\ReturnLabel;
use App\Returns\ReturnLabelInput;
use App\Returns\ReturnLabelService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

function dhlInput(array $override = []): ReturnLabelInput
{
    $data = [
        'ticketNumber' => '2140026',
        'orderNumber' => 'R1-402-4907715-1581912',
        'eocsOrderId' => 4711,
        'name' => 'Max Mustermann',
        'street' => 'Kurfürstendamm',
        'houseNumber' => '1a',
        'postalCode' => '01099',
        'city' => 'Dresden',
        ...$override,
    ];

    return new ReturnLabelInput(...$data);
}

beforeEach(function () {
    config(['services.dhl' => [
        'url' => 'https://dhl.test/returns',
        'token_url' => 'https://dhl.test/token',
        'client_id' => 'key',
        'client_secret' => 'secret',
        'username' => 'system',
        'password' => 'pw',
        'receiver_id' => 'deu',
        'timeout' => 5,
        'retention_days' => 30,
    ]]);
    Cache::flush();
    Storage::fake('return-labels');
    Http::preventStrayRequests();
});

test('a label is ordered with the confirmed address and the references, without e-mail', function () {
    fakeDhl();

    $label = app(ReturnLabelService::class)->create(dhlInput(), 'Nele');

    expect($label->status)->toBe(ReturnLabel::CREATED)
        ->and($label->shipment_no)->toBe('999991587211')
        ->and($label->created_by)->toBe('Nele')
        ->and(Storage::disk('return-labels')->get($label->file_path))->toBe(miniPdf())
        ->and($label->file_sha256)->toBe(hash('sha256', miniPdf()));

    Http::assertSent(function (Request $request): bool {
        if (! str_contains($request->url(), '/orders')) {
            return false;
        }

        $body = $request->data();

        return $request->hasHeader('Authorization', 'Bearer tok-secret')
            && str_contains($request->url(), 'labelType=SHIPMENT_LABEL') && str_contains($request->url(), 'docFormat=PDF') && str_contains($request->url(), 'printFormat=A4')
            && $body['receiverId'] === 'deu'
            && $body['shipper'] === ['name1' => 'Max Mustermann', 'addressStreet' => 'Kurfürstendamm', 'addressHouse' => '1a', 'postalCode' => '01099', 'city' => 'Dresden']
            && $body['customerReference'] === '402-4907715-1581912 T2140026'
            && $body['shipmentReference'] === 'Ticket 2140026'
            && ! array_key_exists('email', $body['shipper']);
    });
});

test('without a ticket only the order number is printed, and a too long reference falls back to the order', function () {
    expect(dhlInput(['ticketNumber' => null])->customerReference())->toBe('402-4907715-1581912')
        ->and(dhlInput(['ticketNumber' => null])->shipmentReference())->toBeNull()
        ->and(dhlInput(['orderNumber' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ12'])->customerReference())->toBe('ABCDEFGHIJKLMNOPQRSTUVWXYZ12');
});

test('the token is cached and fetched once for several labels', function () {
    fakeDhl();

    app(ReturnLabelService::class)->create(dhlInput(), 'Nele');
    app(ReturnLabelService::class)->create(dhlInput(['houseNumber' => '2']), 'Nele');

    Http::assertSentCount(3);
});

test('a rejected token is renewed exactly once', function () {
    $orders = 0;
    fakeDhl(function () use (&$orders) {
        $orders++;

        return $orders === 1
            ? Http::response(['title' => 'Unauthorized', 'status' => 401], 401)
            : Http::response(['shipmentNo' => '1', 'label' => ['b64' => base64_encode(miniPdf())]], 201);
    });

    $label = app(ReturnLabelService::class)->create(dhlInput(), 'Nele');

    expect($label->status)->toBe(ReturnLabel::CREATED)->and($orders)->toBe(2);
});

test('the same request again returns the existing label instead of ordering twice', function () {
    fakeDhl();

    $first = app(ReturnLabelService::class)->create(dhlInput(), 'Nele');
    $second = app(ReturnLabelService::class)->create(dhlInput(), 'Cara');

    expect($second->id)->toBe($first->id)->and(ReturnLabel::count())->toBe(1);
    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/orders'));
    expect(collect(Http::recorded())->filter(fn ($pair) => str_contains($pair[0]->url(), '/orders'))->count())->toBe(1);
});

test('an additional label is ordered on purpose', function () {
    fakeDhl();

    $first = app(ReturnLabelService::class)->create(dhlInput(), 'Nele');
    $second = app(ReturnLabelService::class)->create(dhlInput(), 'Nele', additional: true);

    expect($second->id)->not->toBe($first->id)->and(ReturnLabel::count())->toBe(2);
});

test('a lost answer leads to an unclear outcome and is never ordered again by itself', function () {
    $orders = 0;
    fakeDhl(function () use (&$orders) {
        $orders++;

        throw new ConnectionException('cURL error 28: Operation timed out');
    });

    expect(fn () => app(ReturnLabelService::class)->create(dhlInput(), 'Nele'))
        ->toThrow(fn (DhlException $e) => $e->problem === DhlProblem::OutcomeUnknown);

    $label = ReturnLabel::first();
    expect($label->status)->toBe(ReturnLabel::OUTCOME_UNKNOWN);

    $again = app(ReturnLabelService::class)->create(dhlInput(), 'Nele');

    expect($again->id)->toBe($label->id)->and($orders)->toBe(1);
});

test('a connection that never came up is safe to repeat', function () {
    fakeDhl(fn () => throw new ConnectionException('cURL error 7: Failed to connect'));

    expect(fn () => app(ReturnLabelService::class)->create(dhlInput(), 'Nele'))
        ->toThrow(fn (DhlException $e) => $e->problem === DhlProblem::Unavailable);

    expect(ReturnLabel::first()->status)->toBe(ReturnLabel::FAILED);
});

test('server errors are an unclear outcome', function () {
    fakeDhl(Http::response('<html>Bad gateway</html>', 502));

    expect(fn () => app(ReturnLabelService::class)->create(dhlInput(), 'Nele'))
        ->toThrow(fn (DhlException $e) => $e->problem === DhlProblem::OutcomeUnknown);

    expect(ReturnLabel::first()->status)->toBe(ReturnLabel::OUTCOME_UNKNOWN);
});

test('a rejection shows what DHL complains about and allows a new attempt', function () {
    fakeDhl(Http::response(['title' => 'Bad Request', 'status' => 400, 'detail' => 'shipper.postalCode is invalid'], 400));

    try {
        app(ReturnLabelService::class)->create(dhlInput(), 'Nele');
    } catch (DhlException $exception) {
        expect($exception->problem)->toBe(DhlProblem::Rejected)->and($exception->detail)->toBe('shipper.postalCode is invalid');
    }

    expect(ReturnLabel::where('status', ReturnLabel::FAILED)->count())->toBe(1);
});

test('rate limit, forbidden and unauthorized are certain failures without a label', function (int $status, DhlProblem $problem) {
    fakeDhl(Http::response(['title' => 'x', 'status' => $status], $status));

    try {
        app(ReturnLabelService::class)->create(dhlInput(), 'Nele');
    } catch (DhlException $exception) {
        expect($exception->problem)->toBe($problem)->and($problem->notCreated())->toBeTrue();
    }

    expect(ReturnLabel::first()->status)->toBe(ReturnLabel::FAILED);
})->with([[429, DhlProblem::RateLimited], [403, DhlProblem::Forbidden]]);

test('an unusable file keeps the shipment number and is not handed out', function (string $b64) {
    fakeDhl(Http::response(['shipmentNo' => '999991587211', 'label' => ['b64' => $b64]], 201));

    try {
        app(ReturnLabelService::class)->create(dhlInput(), 'Nele');
    } catch (DhlException $exception) {
        expect($exception->problem)->toBe(DhlProblem::InvalidLabel)->and($exception->shipmentNo)->toBe('999991587211');
    }

    $label = ReturnLabel::first();
    expect($label->status)->toBe(ReturnLabel::INVALID)
        ->and($label->shipment_no)->toBe('999991587211')
        ->and($label->hasFile())->toBeFalse()
        ->and(app(ReturnLabelService::class)->pdf($label))->toBeNull();
})->with([
    'not base64' => ['@@@'],
    'not a pdf' => [base64_encode('iVBORw0KGgo')],
    'truncated pdf' => [base64_encode("%PDF-1.4\n".str_repeat('x', 3000))],
]);

test('missing configuration or wrong credentials stop before any label exists', function () {
    config(['services.dhl.password' => null]);

    expect(fn () => app(ReturnLabelService::class)->create(dhlInput(), 'Nele'))
        ->toThrow(fn (DhlException $e) => $e->problem === DhlProblem::Misconfigured);

    Http::assertNothingSent();

    config(['services.dhl.password' => 'pw']);
    Http::fake(['dhl.test/token' => Http::response(['error' => 'invalid_grant'], 401)]);

    expect(fn () => app(ReturnLabelService::class)->create(dhlInput(), 'Nele'))
        ->toThrow(fn (DhlException $e) => $e->problem === DhlProblem::Unauthorized);
});

test('neither credentials, token, address nor label content reach the log', function () {
    Log::spy();
    fakeDhl(Http::response(['title' => 'Bad Request', 'status' => 400, 'detail' => 'Kurfürstendamm invalid'], 400));

    try {
        app(ReturnLabelService::class)->create(dhlInput(), 'Nele');
    } catch (DhlException) {
    }

    Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context = []): bool {
        $all = $message.json_encode($context);

        return ! str_contains($all, 'secret') && ! str_contains($all, 'tok-') && ! str_contains($all, 'Kurfürstendamm') && ! str_contains($all, 'Mustermann');
    })->atLeast()->once();
});

test('purging deletes only the files after the retention period and keeps the shipment numbers', function () {
    fakeDhl();
    $old = app(ReturnLabelService::class)->create(dhlInput(), 'Nele');
    $new = app(ReturnLabelService::class)->create(dhlInput(['houseNumber' => '2']), 'Nele');
    $old->forceFill(['created_at' => now()->subDays(40)])->save();

    expect(app(ReturnLabelService::class)->purge())->toBe(['files' => 1]);

    expect($old->fresh()->file_path)->toBeNull()
        ->and($old->fresh()->shipment_no)->toBe('999991587211')
        ->and(Storage::disk('return-labels')->exists($new->fresh()->file_path))->toBeTrue();
});

test('dhl:check lists the return receivers', function () {
    fakeDhl();

    $this->artisan('dhl:check')->expectsOutputToContain('Anmeldung bei DHL erfolgreich')->assertSuccessful();
});

test('the client reports whether it is configured', function () {
    expect(app(DhlClient::class)->isConfigured())->toBeTrue();

    config(['services.dhl.receiver_id' => '']);

    expect(app(DhlClient::class)->isConfigured())->toBeFalse();
});

test('dhl:check works without the receiver ID, because it is there to find it', function () {
    fakeDhl();
    config(['services.dhl.receiver_id' => null]);

    $this->artisan('dhl:check')->expectsOutputToContain('deu')->expectsOutputToContain('DHL_RECEIVER_ID')->assertSuccessful();
});

test('dhl:check names the missing login data', function () {
    config(['services.dhl.password' => null]);

    $this->artisan('dhl:check')->expectsOutputToContain('DHL_PASSWORD')->assertFailed();
});
