<?php

use App\Jobs\AnswerWismoTicket;
use App\Labeling\LabelAgent;
use App\Labeling\LabelClassifier;
use App\Models\LabelTicket;
use App\Models\WismoRun;
use App\Settings\SettingsStore;
use App\Wismo\WismoAgent;
use App\Wismo\WismoResponder;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Prompts\AgentPrompt;

const WISMO_NUMBER = '028-1058128-5149960';
const WISMO_TRACKING = '00340434171079146248';
const WISMO_LINK = 'https://www.dhl.de/de/privatkunden/pakete-empfangen/verfolgen.html?piececode='.WISMO_TRACKING;

beforeEach(function () {
    Cache::flush();
    $this->travelTo(CarbonImmutable::parse('2026-11-11 10:00:00', 'Europe/Berlin'));
    config([
        'cache.default' => 'array',
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token', 'services.zammad.reply_token' => 'bot-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'services.dhl_tracking' => ['url' => 'https://dhl.test/track/shipments', 'key' => 'dhl-key', 'timeout' => 5],
        'ai.providers.openai.key' => 'sk-test',
    ]);
    app(SettingsStore::class)->save('wismo_mode', 'on', 'Etienne');
});

/**
 * @param  array<string, mixed>  $order
 * @param  list<array<string, mixed>>|null  $articles
 */
function wismoSetup(array $order = [], ?array $articles = null, int $dhlStatus = 200, ?array $dhl = null, int $putStatus = 200): void
{
    $given = $order;
    $order = array_replace_recursive([
        'id' => 716249, 'external_order_id' => WISMO_NUMBER, 'order_date' => '2026-11-04T07:17:15.000000Z', 'status' => 5, 'status_name' => 'Vollständig und verschickt',
        'client' => ['name' => 'Amazon.de'], 'shipping' => ['zipcode' => '46049', 'street' => 'Rosenstrasse 44', 'city' => 'Oberhausen', 'country_code' => 'DE'],
        'shipments' => ['data' => [['carrier' => ['name' => 'DHL', 'carrier_name' => 'dhl'], 'tracking_no' => WISMO_TRACKING, 'created_at' => '05.11.2026 11:03', 'delivered' => null]]],
        'order_items' => ['data' => []],
    ], $order);
    $order['shipments'] = $given['shipments'] ?? $order['shipments'];

    $articles ??= [zammadArticle(['id' => 1, 'body' => '<p>Hallo, wo ist mein Paket? Meine Bestellnummer lautet '.WISMO_NUMBER.'</p>', 'created_at' => '2026-11-11T07:30:00.000Z', 'message_id' => '<m1@example.org>', 'subject' => 'Wo ist mein Paket'])];

    Http::swap(new Factory(app('events')));
    fakeZammad($articles, ['title' => 'Wo ist mein Paket'], [
        'zammad.test/api/v1/users/me' => Http::response(['id' => 900, 'firstname' => 'customer', 'lastname' => 'serviceassist', 'login' => 'customerserviceassist']),
        'zammad.test/api/v1/tickets/51234' => Http::response(['id' => 51234, 'number' => '2137942'], $putStatus),
        'eocs.test/api/v1/orders*' => function (Request $request) use ($order) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            [$number] = explode(',', (string) ($query['filter']['external_order_id'] ?? ''));

            return Http::response(['data' => $number === $order['external_order_id'] ? [$order] : [], 'meta' => []]);
        },
        'dhl.test/*' => Http::response($dhl ?? ['shipments' => [['status' => ['statusCode' => 'transit', 'description' => 'Die Sendung wurde an die Zustellbasis übergeben'], 'events' => [['statusCode' => 'transit', 'description' => 'Die Sendung wurde an die Zustellbasis übergeben', 'timestamp' => '2026-11-11T05:00:00+01:00', 'location' => ['address' => ['addressLocality' => 'Oberhausen']]]]]]], $dhlStatus),
    ]);
}

function wismoLabel(array $overrides = []): LabelTicket
{
    return LabelTicket::query()->create(array_merge(['zammad_id' => 51234, 'number' => '2137942', 'article_id' => 1, 'text' => 'Wo ist mein Paket?', 'source' => 'eingang', 'split' => 'training', 'state' => 'classified'], $overrides));
}

function wismoReply(?string $body = null): array
{
    return ['language' => 'Deutsch', 'reply' => $body ?? "Guten Tag,\n\nIhre Sendungsnummer lautet: ".WISMO_TRACKING."\nDHL ist unterwegs.\n\n".WISMO_LINK."\nFalls der Link nicht klickbar ist, bitte kopieren.", 'reply_de' => 'Deutsche Fassung der Antwort.'];
}

/**
 * @return list<array<string, mixed>>
 */
function wismoPuts(): array
{
    return Http::recorded(fn (Request $request): bool => $request->method() === 'PUT' && str_ends_with($request->url(), 'tickets/51234'))->map(fn (array $pair): array => $pair[0]->data())->values()->all();
}

function wismoMails(): array
{
    return array_values(array_filter(wismoPuts(), fn (array $put): bool => ($put['article']['type'] ?? '') === 'email'));
}

function wismoNotes(): array
{
    return array_values(array_filter(wismoPuts(), fn (array $put): bool => ($put['article']['type'] ?? '') === 'note'));
}

describe('the reply', function () {
    test('it answers a new ticket with the state of the shipment, as the technical user, and sets the ticket to open', function () {
        wismoSetup();
        WismoAgent::fake([wismoReply()]);

        expect(app(WismoResponder::class)->run(wismoLabel()))->toBeNull();

        $mails = wismoMails();
        expect($mails)->toHaveCount(1)
            ->and($mails[0]['state'])->toBe('open')
            ->and($mails[0]['article']['to'])->toBe('erika@example.org')
            ->and($mails[0]['article']['body'])->toContain(WISMO_TRACKING)->toContain(WISMO_LINK)->toContain('LOOXIS Kundenservice')->toContain('automatisch verfasst')
            ->and(wismoNotes())->toHaveCount(1)
            ->and(wismoNotes()[0]['article']['body'])->toContain('Automatisch beantwortet (WISMO)')->toContain('Deutsche Fassung');

        $run = WismoRun::query()->firstOrFail();
        expect($run->result)->toBe(WismoRun::SENT)->and($run->order_number)->toBe(WISMO_NUMBER)->and($run->carrier)->toBe('DHL')->and($run->tracking_number)->toBe(WISMO_TRACKING)->and($run->delivery_problem)->toBeFalse()->and($run->reply_text)->toContain(WISMO_TRACKING)->and($run->model)->not->toBeNull();

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'dhl.test') && $request['recipientPostalCode'] === '46049' && $request->hasHeader('DHL-API-Key', 'dhl-key'));
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT' && $request->hasHeader('Authorization', 'Token token=bot-token'));
        WismoAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains(WISMO_TRACKING) && ! $prompt->contains('Rosenstrasse'));
    });

    test('a ticket is answered only once', function () {
        wismoSetup();
        WismoAgent::fake([wismoReply(), wismoReply()]);

        app(WismoResponder::class)->run(wismoLabel());
        expect(app(WismoResponder::class)->run(LabelTicket::query()->firstOrFail()))->toBeNull();

        expect(wismoMails())->toHaveCount(1)->and(WismoRun::query()->count())->toBe(1);
    });

    test('with the switch off nothing is written', function () {
        app(SettingsStore::class)->save('wismo_mode', 'off', 'Etienne');
        wismoSetup();
        WismoAgent::fake([wismoReply()]);

        app(WismoResponder::class)->run(wismoLabel());

        expect(wismoPuts())->toBe([])->and(WismoRun::query()->firstOrFail()->result)->toBe(WismoRun::SKIPPED);
        WismoAgent::assertNeverPrompted();
    });

    test('in the trial run only a note with the draft is written', function () {
        app(SettingsStore::class)->save('wismo_mode', 'dry', 'Etienne');
        wismoSetup();
        WismoAgent::fake([wismoReply()]);

        app(WismoResponder::class)->run(wismoLabel());

        expect(wismoMails())->toBe([])->and(wismoNotes())->toHaveCount(1)->and(wismoNotes()[0]['article']['body'])->toContain('Trockenlauf')->toContain(WISMO_TRACKING)
            ->and(WismoRun::query()->firstOrFail()->result)->toBe(WismoRun::DRY_RUN);
    });
});

describe('when the case needs a person', function () {
    test('without an order number a note is written and nothing is sent', function () {
        wismoSetup(articles: [zammadArticle(['id' => 1, 'body' => '<p>Wo ist mein Paket?</p>', 'created_at' => '2026-11-11T07:30:00.000Z'])]);
        WismoAgent::fake([wismoReply()]);

        app(WismoResponder::class)->run(wismoLabel());

        expect(wismoMails())->toBe([])->and(wismoNotes()[0]['article']['body'])->toContain('keine Bestellnummer')->and(WismoRun::query()->firstOrFail()->result)->toBe(WismoRun::NOTE);
        WismoAgent::assertNeverPrompted();
    });

    test('an order that EOCS does not know gets a note', function () {
        wismoSetup(articles: [zammadArticle(['id' => 1, 'body' => '<p>Bestellung 304-1111111-2222222</p>', 'created_at' => '2026-11-11T07:30:00.000Z'])]);
        WismoAgent::fake([wismoReply()]);

        app(WismoResponder::class)->run(wismoLabel());

        expect(wismoMails())->toBe([])->and(wismoNotes()[0]['article']['body'])->toContain('304-1111111-2222222')->toContain('nicht gefunden');
    });

    test('a ticket that was answered already is left alone', function () {
        wismoSetup(articles: [
            zammadArticle(['id' => 1, 'body' => '<p>Bestellung '.WISMO_NUMBER.'</p>', 'created_at' => '2026-11-11T07:30:00.000Z']),
            zammadArticle(['id' => 2, 'sender' => 'Agent', 'from' => 'LOOXIS <hello@looxis.de>', 'body' => '<p>Wir schauen nach.</p>', 'created_at' => '2026-11-11T08:00:00.000Z']),
        ]);
        WismoAgent::fake([wismoReply()]);

        app(WismoResponder::class)->run(wismoLabel());

        expect(wismoPuts())->toBe([])->and(WismoRun::query()->firstOrFail()->result)->toBe(WismoRun::SKIPPED)->and(WismoRun::query()->firstOrFail()->reason)->toContain('beantwortet');
    });

    test('a newer message of the customer stops the automatic reply', function () {
        wismoSetup(articles: [
            zammadArticle(['id' => 1, 'body' => '<p>Bestellung '.WISMO_NUMBER.'</p>', 'created_at' => '2026-11-11T07:30:00.000Z']),
            zammadArticle(['id' => 3, 'body' => '<p>Und eine Frage zur Rechnung.</p>', 'created_at' => '2026-11-11T07:40:00.000Z']),
        ]);

        app(WismoResponder::class)->run(wismoLabel());

        expect(wismoPuts())->toBe([])->and(WismoRun::query()->firstOrFail()->reason)->toContain('neuere Nachricht');
    });

    test('a reply without the tracking number or the link is not sent', function () {
        wismoSetup();
        WismoAgent::fake([wismoReply('Guten Tag, Ihr Paket ist unterwegs.')]);

        app(WismoResponder::class)->run(wismoLabel());

        expect(wismoMails())->toBe([])->and(wismoNotes()[0]['article']['body'])->toContain('nicht gesendet')->toContain(WISMO_TRACKING)
            ->and(WismoRun::query()->firstOrFail()->result)->toBe(WismoRun::NOTE);
    });

    test('without the token of the technical user the run ends with an error and sends nothing', function () {
        config(['services.zammad.reply_token' => null]);
        wismoSetup();
        WismoAgent::fake([wismoReply()]);

        app(WismoResponder::class)->run(wismoLabel());

        expect(wismoPuts())->toBe([])->and(WismoRun::query()->firstOrFail()->result)->toBe(WismoRun::ERROR);
    });
});

describe('shipment states', function () {
    test('a delivery problem is flagged, the reply is careful and the pickup point stays unnamed', function () {
        wismoSetup(dhl: ['shipments' => [['status' => ['statusCode' => 'failure'], 'events' => [['statusCode' => 'failure', 'description' => 'Die Sendung wird an den Absender zurück gesendet', 'remark' => 'Packstation 1, Weg 2', 'timestamp' => '2026-11-10T09:00:00+01:00']]]]]);
        WismoAgent::fake([wismoReply()]);

        app(WismoResponder::class)->run(wismoLabel());

        $run = WismoRun::query()->firstOrFail();
        expect($run->delivery_problem)->toBeTrue()->and($run->delivery_problem_reason)->toContain('Absender')
            ->and(wismoNotes()[0]['article']['body'])->toContain('Zustellproblem, bitte prüfen')
            ->and(wismoMails())->toHaveCount(1);
        WismoAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('"delivery_problem": true') && ! $prompt->contains('Packstation 1'));
    });

    test('an order without shipment gets the status reply and, after three working days, an internal hint', function () {
        wismoSetup(order: ['shipments' => ['data' => []], 'order_date' => '2026-11-04T07:17:15.000000Z']);
        WismoAgent::fake([wismoReply('Guten Tag, Ihre Bestellung wird bearbeitet. Wir melden uns mit der Sendungsnummer.')]);

        app(WismoResponder::class)->run(wismoLabel());

        $run = WismoRun::query()->firstOrFail();
        expect([$run->result, $run->reason])->toBe([WismoRun::SENT, null])->and($run->stuck_workdays)->toBe(5)->and(wismoNotes()[0]['article']['body'])->toContain('5 Werktagen ohne Versand');
        WismoAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('no_shipment_for_workdays') && $prompt->contains('"shipments"') === false);
    });

    test('another carrier gets its name and number without a status', function () {
        wismoSetup(order: ['shipments' => ['data' => [['carrier' => ['name' => 'GLS'], 'tracking_no' => 'GLS123456', 'created_at' => '05.11.2026 11:03']]]]);
        WismoAgent::fake([wismoReply('Guten Tag, Ihre Sendungsnummer lautet: GLS123456 bei GLS.')]);

        app(WismoResponder::class)->run(wismoLabel());

        expect(wismoMails())->toHaveCount(1);
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'dhl.test'));
    });
});

describe('problems on the way', function () {
    test('a carrier that is down is tried again, and after the third attempt a note asks for a person', function () {
        wismoSetup(dhlStatus: 503);
        WismoAgent::fake([wismoReply()]);

        $first = app(WismoResponder::class)->run(wismoLabel());
        expect($first)->toBe(WismoResponder::RETRY_SECONDS)->and(WismoRun::query()->firstOrFail()->result)->toBe(WismoRun::RETRY)->and(wismoPuts())->toBe([]);

        $second = app(WismoResponder::class)->run(LabelTicket::query()->firstOrFail());
        expect($second)->toBe(WismoResponder::RETRY_SECONDS * 2);

        $third = app(WismoResponder::class)->run(LabelTicket::query()->firstOrFail());
        expect($third)->toBeNull()->and(WismoRun::query()->firstOrFail()->result)->toBe(WismoRun::ERROR)->and(WismoRun::query()->firstOrFail()->attempts)->toBe(3)
            ->and(wismoMails())->toBe([])->and(wismoNotes()[0]['article']['body'])->toContain('fehlgeschlagen');
    });

    test('a refused DHL key ends the run at once with a note', function () {
        wismoSetup(dhlStatus: 401);
        WismoAgent::fake([wismoReply()]);

        expect(app(WismoResponder::class)->run(wismoLabel()))->toBeNull();
        expect(WismoRun::query()->firstOrFail()->result)->toBe(WismoRun::ERROR)->and(wismoNotes())->toHaveCount(1);
    });

    test('when Zammad does not answer the send, the app does not send again', function () {
        wismoSetup(putStatus: 504);
        WismoAgent::fake([wismoReply()]);

        app(WismoResponder::class)->run(wismoLabel());

        expect(app(WismoResponder::class)->run(LabelTicket::query()->firstOrFail()))->toBeNull()
            ->and(WismoRun::query()->firstOrFail()->result)->toBe(WismoRun::SENDING)
            ->and(count(wismoMails()))->toBeLessThanOrEqual(1);
    });
});

describe('the last check of the text', function () {
    test('a reply with a foreign link is not sent but becomes a note', function () {
        wismoSetup();
        WismoAgent::fake([wismoReply('Guten Tag, Ihre Sendungsnummer lautet: '.WISMO_TRACKING."\n".WISMO_LINK."\nBitte bestätigen Sie Ihre Daten unter https://evil.example/login")]);

        app(WismoResponder::class)->run(wismoLabel());

        expect(wismoMails())->toBe([])->and(wismoNotes()[0]['article']['body'])->toContain('kein Trackinglink')->and(WismoRun::query()->firstOrFail()->result)->toBe(WismoRun::NOTE);
    });

    test('a reply that promises money is not sent', function () {
        wismoSetup();
        WismoAgent::fake([wismoReply('Guten Tag, Ihre Sendungsnummer lautet: '.WISMO_TRACKING."\n".WISMO_LINK."\nWir erstatten Ihnen 49,90 € sofort.")]);

        app(WismoResponder::class)->run(wismoLabel());

        expect(wismoMails())->toBe([])->and(WismoRun::query()->firstOrFail()->reason)->toContain('Geldbetrag');
    });

    test('the model gets a time limit shorter than the retry time of the queue', function () {
        expect(WismoResponder::MODEL_TIMEOUT)->toBeLessThan(90)->and((new AnswerWismoTicket(1))->timeout)->toBeLessThan(90);
    });
});

describe('an abandoned run', function () {
    function abandonedRun(array $overrides = []): WismoRun
    {
        $run = WismoRun::query()->create(array_merge(['zammad_id' => 51234, 'ticket_number' => '2137942', 'mode' => 'on', 'result' => WismoRun::WORKING, 'attempts' => 1], $overrides));
        WismoRun::query()->whereKey($run->id)->update(['updated_at' => now()->subMinutes(6)]);

        return $run->refresh();
    }

    test('a run that is still fresh is not taken over', function () {
        wismoSetup();
        WismoAgent::fake([wismoReply()]);
        $run = WismoRun::query()->create(['zammad_id' => 51234, 'ticket_number' => '2137942', 'mode' => 'on', 'result' => WismoRun::WORKING, 'attempts' => 1]);

        expect(app(WismoResponder::class)->run(wismoLabel()))->toBeNull();

        expect($run->refresh()->result)->toBe(WismoRun::WORKING)->and(wismoPuts())->toBe([]);
    });

    test('a run that stayed working for minutes is taken over and answered', function () {
        wismoSetup();
        WismoAgent::fake([wismoReply()]);
        $run = abandonedRun();

        expect(app(WismoResponder::class)->run(wismoLabel()))->toBeNull();

        expect($run->refresh()->result)->toBe(WismoRun::SENT)->and($run->attempts)->toBe(2)->and(wismoMails())->toHaveCount(1);
    });

    test('after the third abandoned attempt a person gets a note and the run ends', function () {
        wismoSetup();
        WismoAgent::fake([wismoReply()]);
        $run = abandonedRun(['attempts' => WismoResponder::MAX_ATTEMPTS]);

        app(WismoResponder::class)->run(wismoLabel());

        expect($run->refresh()->result)->toBe(WismoRun::ERROR)->and(wismoMails())->toBe([])->and(wismoNotes()[0]['article']['body'])->toContain('mehrfach abgebrochen');
        WismoAgent::assertNeverPrompted();
    });

    test('a run that is sending is never taken over', function () {
        wismoSetup();
        WismoAgent::fake([wismoReply()]);
        $run = abandonedRun(['result' => WismoRun::SENDING]);

        app(WismoResponder::class)->run(wismoLabel());

        expect($run->refresh()->result)->toBe(WismoRun::SENDING)->and(wismoPuts())->toBe([]);
    });

    test('the recover command starts abandoned runs again and leaves the others alone', function () {
        Queue::fake();
        wismoLabel();
        abandonedRun();
        $fresh = wismoLabel(['zammad_id' => 777, 'number' => '2137999']);
        WismoRun::query()->create(['zammad_id' => 777, 'ticket_number' => '2137999', 'mode' => 'on', 'result' => WismoRun::WORKING, 'attempts' => 1]);
        $lost = wismoLabel(['zammad_id' => 888, 'number' => '2138000']);
        $retry = WismoRun::query()->create(['zammad_id' => 888, 'ticket_number' => '2138000', 'mode' => 'on', 'result' => WismoRun::RETRY, 'attempts' => 1]);
        WismoRun::query()->whereKey($retry->id)->update(['updated_at' => now()->subMinutes(10)]);

        $this->artisan('wismo:recover')->assertSuccessful();

        Queue::assertPushed(AnswerWismoTicket::class, 2);
        Queue::assertNotPushed(AnswerWismoTicket::class, fn ($job) => $job->labelTicketId === $fresh->id);
        expect($lost->id)->not->toBeNull();
    });
});

describe('the trigger after the classification', function () {
    function classifyWismo(array $answer = [], array $ticket = []): void
    {
        $label = wismoLabel(array_merge(['state' => 'captured'], $ticket));
        LabelAgent::fake([array_merge(['category' => 'wismo', 'unclear' => false, 'multiple_concerns' => false, 'confidence' => 0.96, 'reason' => 'Lieferstatus.', 'evidence' => 'Wo ist mein Paket?'], $answer)]);
        app(LabelClassifier::class)->classify($label);
    }

    beforeEach(function () {
        Queue::fake();
        config(['labeling.model' => 'gpt-test']);
    });

    test('a clear WISMO of a new ticket starts the answer', function () {
        classifyWismo();

        Queue::assertPushed(AnswerWismoTicket::class);
    });

    test('nothing starts for another category, an unclear or doubtful proposal, an old ticket or the switch off', function () {
        foreach ([['category' => 'reklamation'], ['unclear' => true], ['multiple_concerns' => true], ['confidence' => 0.5]] as $index => $answer) {
            LabelTicket::query()->delete();
            classifyWismo($answer, ['zammad_id' => 100 + $index]);
        }

        LabelTicket::query()->delete();
        classifyWismo(ticket: ['source' => 'pool', 'zammad_id' => 200]);
        LabelTicket::query()->delete();
        app(SettingsStore::class)->save('wismo_mode', 'off', 'Etienne');
        classifyWismo(ticket: ['zammad_id' => 202]);

        Queue::assertNotPushed(AnswerWismoTicket::class);
    });

    test('a ticket of the test set is answered too and leaves the test set', function () {
        classifyWismo(ticket: ['split' => 'test', 'zammad_id' => 201]);

        Queue::assertPushed(AnswerWismoTicket::class);
        expect(LabelTicket::query()->where('zammad_id', 201)->value('split'))->toBe('training');
    });
});
