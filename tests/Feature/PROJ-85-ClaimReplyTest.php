<?php

use App\ClaimReply\ClaimReplyAgent;
use App\ClaimReply\ClaimReplyResponder;
use App\Jobs\AnswerClaimTicket;
use App\Labeling\LabelAgent;
use App\Labeling\LabelClassifier;
use App\Models\ClaimReplyRun;
use App\Models\LabelTicket;
use App\Settings\SettingsStore;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Prompts\AgentPrompt;

const CLAIM_NUMBER = '028-1058128-5149960';

beforeEach(function () {
    Cache::flush();
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2026-11-11 10:00:00', 'Europe/Berlin'));
    config([
        'cache.default' => 'array', 'staff.admins' => ['Etienne'],
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token', 'services.zammad.reply_token' => 'bot-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
    ]);
    app(SettingsStore::class)->save('claim_reply_mode', 'on', 'Etienne');
});

function claimPhoto(int $id = 7): array
{
    return ['id' => $id, 'filename' => "foto{$id}.jpg", 'size' => 80000, 'preferences' => ['Content-Type' => 'image/jpeg']];
}

/**
 * @param  list<array<string, mixed>>|null  $articles
 */
function claimSetup(string $shipped = '05.11.2026 11:03', ?array $articles = null, int $putStatus = 200, bool $shipment = true): void
{
    $order = [
        'id' => 716249, 'external_order_id' => CLAIM_NUMBER, 'order_date' => '2026-11-01T07:17:15.000000Z', 'status' => 5, 'status_name' => 'Vollständig und verschickt',
        'client' => ['name' => 'looxis.de'], 'shipping' => ['zipcode' => '46049', 'street' => 'Rosenstrasse 44', 'city' => 'Oberhausen', 'country_code' => 'DE'],
        'shipments' => ['data' => $shipment ? [['carrier' => ['name' => 'DHL'], 'tracking_no' => '00340434171079146248', 'created_at' => $shipped]] : []],
        'order_items' => ['data' => []],
    ];
    $articles ??= [zammadArticle(['id' => 1, 'body' => '<p>Die Tasse ist kaputt. Bestellung '.CLAIM_NUMBER.'</p>', 'created_at' => '2026-11-11T07:30:00.000Z', 'message_id' => '<m1@example.org>', 'subject' => 'Reklamation'])];

    Http::swap(new Factory(app('events')));
    fakeZammad($articles, ['title' => 'Reklamation'], [
        'zammad.test/api/v1/users/me' => Http::response(['id' => 900, 'firstname' => 'customer', 'lastname' => 'serviceassist', 'login' => 'customerserviceassist']),
        'zammad.test/api/v1/tickets/51234' => Http::response(['id' => 51234, 'number' => '2137942'], $putStatus),
        'eocs.test/api/v1/orders*' => function (Request $request) use ($order) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            [$number] = explode(',', (string) ($query['filter']['external_order_id'] ?? ''));

            return Http::response(['data' => $number === $order['external_order_id'] ? [$order] : [], 'meta' => []]);
        },
    ]);
}

function claimLabel(array $overrides = []): LabelTicket
{
    return LabelTicket::query()->create(array_merge(['zammad_id' => 51234, 'number' => '2137942', 'article_id' => 1, 'text' => 'Die Tasse ist kaputt', 'source' => 'eingang', 'split' => 'training', 'state' => 'classified'], $overrides));
}

function claimReply(string $scenario = 'no_photo', ?string $body = null): array
{
    return ['language' => 'Deutsch', 'scenario' => $scenario, 'reply' => $body ?? "Guten Tag,\n\nein automatisierter Vorab-Check hat Ihre Nachricht als Reklamation erkannt. Bitte senden Sie uns ein Foto.", 'reply_de' => 'Deutsche Fassung der Antwort.'];
}

/**
 * @return list<array<string, mixed>>
 */
function claimPuts(): array
{
    return Http::recorded(fn (Request $request): bool => $request->method() === 'PUT' && str_ends_with($request->url(), 'tickets/51234'))->map(fn (array $pair): array => $pair[0]->data())->values()->all();
}

function claimMails(): array
{
    return array_values(array_filter(claimPuts(), fn (array $put): bool => ($put['article']['type'] ?? '') === 'email'));
}

function claimNotes(): array
{
    return array_values(array_filter(claimPuts(), fn (array $put): bool => ($put['article']['type'] ?? '') === 'note'));
}

describe('the reply', function () {
    test('without a photo it asks for photos, as the technical user, and the ticket stays open', function () {
        claimSetup();
        ClaimReplyAgent::fake([claimReply()]);

        expect(app(ClaimReplyResponder::class)->run(claimLabel()))->toBeNull();

        $mails = claimMails();
        expect($mails)->toHaveCount(1)
            ->and($mails[0]['state'])->toBe('open')
            ->and($mails[0]['article']['body'])->toContain('Vorab-Check')->toContain('LOOXIS Kundenservice')
            ->and(claimNotes()[0]['article']['body'])->toContain('Vorab-Antwort automatisch gesendet')->toContain('Fotos bitte');

        $run = ClaimReplyRun::query()->firstOrFail();
        expect([$run->result, $run->scenario, $run->photo_count, $run->days_since_shipped, $run->order_number])->toBe([ClaimReplyRun::SENT, 'no_photo', 0, 6, CLAIM_NUMBER])
            ->and($run->model)->not->toBeNull()->and($run->prompt_version)->not->toBeNull();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT' && $request->hasHeader('Authorization', 'Token token=bot-token'));
        ClaimReplyAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('"photo_count": 0') && ! $prompt->contains('Rosenstrasse'));
    });

    test('with a photo and a description it confirms receipt', function () {
        claimSetup(articles: [zammadArticle(['id' => 1, 'body' => '<p>Das Motiv ist verschoben, siehe Foto. '.CLAIM_NUMBER.'</p>', 'created_at' => '2026-11-11T07:30:00.000Z', 'attachments' => [claimPhoto()]])]);
        ClaimReplyAgent::fake([claimReply('photo_with_description', 'Guten Tag, wir entschuldigen uns. Ein Mitarbeiter kümmert sich in den nächsten Stunden darum.')]);

        app(ClaimReplyResponder::class)->run(claimLabel());

        $run = ClaimReplyRun::query()->firstOrFail();
        expect($run->result)->toBe(ClaimReplyRun::SENT)->and($run->photo_count)->toBe(1)->and($run->scenario)->toBe('photo_with_description')->and(claimMails())->toHaveCount(1);
    });

    test('a pdf or video is no photo', function () {
        claimSetup(articles: [zammadArticle(['id' => 1, 'body' => '<p>Defekt. '.CLAIM_NUMBER.'</p>', 'created_at' => '2026-11-11T07:30:00.000Z', 'attachments' => [['id' => 8, 'filename' => 'a.pdf', 'size' => 5000, 'preferences' => ['Content-Type' => 'application/pdf']]]])]);
        ClaimReplyAgent::fake([claimReply()]);

        app(ClaimReplyResponder::class)->run(claimLabel());

        expect(ClaimReplyRun::query()->firstOrFail()->photo_count)->toBe(0);
    });

    test('a reply whose case does not fit the number of photos is not sent', function () {
        claimSetup();
        ClaimReplyAgent::fake([claimReply('photo_with_description')]);

        app(ClaimReplyResponder::class)->run(claimLabel());

        expect(claimMails())->toBe([])->and(claimNotes()[0]['article']['body'])->toContain('passt nicht zur Zahl der Fotos')->and(ClaimReplyRun::query()->firstOrFail()->result)->toBe(ClaimReplyRun::NOTE);
    });

    test('a ticket is answered only once', function () {
        claimSetup();
        ClaimReplyAgent::fake([claimReply(), claimReply()]);

        app(ClaimReplyResponder::class)->run(claimLabel());
        expect(app(ClaimReplyResponder::class)->run(LabelTicket::query()->firstOrFail()))->toBeNull();

        expect(claimMails())->toHaveCount(1)->and(ClaimReplyRun::query()->count())->toBe(1);
    });

    test('with the switch off nothing is written', function () {
        app(SettingsStore::class)->save('claim_reply_mode', 'off', 'Etienne');
        claimSetup();
        ClaimReplyAgent::fake([claimReply()]);

        app(ClaimReplyResponder::class)->run(claimLabel());

        expect(claimPuts())->toBe([])->and(ClaimReplyRun::query()->firstOrFail()->result)->toBe(ClaimReplyRun::SKIPPED);
        ClaimReplyAgent::assertNeverPrompted();
    });

    test('in the trial run only a note with the draft and the German version is written', function () {
        app(SettingsStore::class)->save('claim_reply_mode', 'dry', 'Etienne');
        claimSetup();
        ClaimReplyAgent::fake([claimReply()]);

        app(ClaimReplyResponder::class)->run(claimLabel());

        expect(claimMails())->toBe([])->and(claimNotes())->toHaveCount(1)
            ->and(claimNotes()[0]['article']['body'])->toContain('Trockenlauf')->toContain('Vorab-Check')->toContain('Deutsche Fassung')
            ->and(ClaimReplyRun::query()->firstOrFail()->result)->toBe(ClaimReplyRun::DRY_RUN);
    });
});

describe('when nothing is sent', function () {
    test('a shipment older than 30 days gets a note', function () {
        claimSetup(shipped: '01.10.2026 11:03');
        ClaimReplyAgent::fake([claimReply()]);

        app(ClaimReplyResponder::class)->run(claimLabel());

        expect(claimMails())->toBe([])->and(claimNotes()[0]['article']['body'])->toContain('Keine Vorab-Antwort')->toContain('41 Tage')
            ->and(ClaimReplyRun::query()->firstOrFail()->result)->toBe(ClaimReplyRun::NOTE);
        ClaimReplyAgent::assertNeverPrompted();
    });

    test('exactly 30 days is still answered', function () {
        claimSetup(shipped: '12.10.2026 11:03');
        ClaimReplyAgent::fake([claimReply()]);

        app(ClaimReplyResponder::class)->run(claimLabel());

        expect(claimMails())->toHaveCount(1);
    });

    test('an order that is not shipped yet gets a note', function () {
        claimSetup(shipment: false);

        app(ClaimReplyResponder::class)->run(claimLabel());

        expect(claimMails())->toBe([])->and(ClaimReplyRun::query()->firstOrFail()->reason)->toContain('kein Versanddatum');
    });

    test('without an order number or with an unknown order a note is written', function () {
        claimSetup(articles: [zammadArticle(['id' => 1, 'body' => '<p>Kaputt</p>', 'created_at' => '2026-11-11T07:30:00.000Z'])]);
        app(ClaimReplyResponder::class)->run(claimLabel());
        expect(claimNotes()[0]['article']['body'])->toContain('keine Bestellnummer')->and(claimMails())->toBe([]);

        ClaimReplyRun::query()->delete();
        LabelTicket::query()->delete();
        claimSetup(articles: [zammadArticle(['id' => 1, 'body' => '<p>Bestellung 304-1111111-2222222</p>', 'created_at' => '2026-11-11T07:30:00.000Z'])]);
        app(ClaimReplyResponder::class)->run(claimLabel());
        expect(claimNotes()[0]['article']['body'])->toContain('nicht gefunden');
    });

    test('a ticket that was answered or has a newer message is left alone', function () {
        claimSetup(articles: [
            zammadArticle(['id' => 1, 'body' => '<p>Bestellung '.CLAIM_NUMBER.'</p>', 'created_at' => '2026-11-11T07:30:00.000Z']),
            zammadArticle(['id' => 2, 'sender' => 'Agent', 'from' => 'LOOXIS <hello@looxis.de>', 'body' => '<p>Wir schauen nach.</p>', 'created_at' => '2026-11-11T08:00:00.000Z']),
        ]);
        app(ClaimReplyResponder::class)->run(claimLabel());
        expect(claimPuts())->toBe([])->and(ClaimReplyRun::query()->firstOrFail()->reason)->toContain('beantwortet');

        ClaimReplyRun::query()->delete();
        LabelTicket::query()->delete();
        claimSetup(articles: [
            zammadArticle(['id' => 1, 'body' => '<p>Bestellung '.CLAIM_NUMBER.'</p>', 'created_at' => '2026-11-11T07:30:00.000Z']),
            zammadArticle(['id' => 3, 'body' => '<p>Noch etwas.</p>', 'created_at' => '2026-11-11T07:40:00.000Z']),
        ]);
        app(ClaimReplyResponder::class)->run(claimLabel());
        expect(claimPuts())->toBe([])->and(ClaimReplyRun::query()->firstOrFail()->reason)->toContain('neuere Nachricht');
    });

    test('a reply that promises replacement, money or a link is not sent', function (string $text, string $reason) {
        claimSetup();
        ClaimReplyAgent::fake([claimReply('no_photo', $text)]);

        app(ClaimReplyResponder::class)->run(claimLabel());

        expect(claimMails())->toBe([])->and(ClaimReplyRun::query()->firstOrFail()->reason)->toContain($reason);
    })->with([
        'replacement' => ['Guten Tag, wir senden Ihnen selbstverständlich Ersatz.', 'Ersatz, Erstattung'],
        'goodwill' => ['Guten Tag, wir zeigen uns kulant.', 'Ersatz, Erstattung'],
        'amount' => ['Guten Tag, wir erstatten 49,90 € zurück.', 'Geldbetrag'],
        'link' => ['Guten Tag, laden Sie Fotos hoch: https://evil.example/upload', 'Link'],
        'placeholder' => ['Guten Tag [E-MAIL_1], bitte ein Foto.', 'Platzhalter'],
    ]);

    test('without the token of the technical user the run ends with an error', function () {
        config(['services.zammad.reply_token' => null]);
        claimSetup();
        ClaimReplyAgent::fake([claimReply()]);

        app(ClaimReplyResponder::class)->run(claimLabel());

        expect(claimPuts())->toBe([])->and(ClaimReplyRun::query()->firstOrFail()->result)->toBe(ClaimReplyRun::ERROR);
    });
});

describe('problems on the way', function () {
    test('when Zammad does not answer the send, the app does not send again', function () {
        claimSetup(putStatus: 504);
        ClaimReplyAgent::fake([claimReply()]);

        app(ClaimReplyResponder::class)->run(claimLabel());

        expect(app(ClaimReplyResponder::class)->run(LabelTicket::query()->firstOrFail()))->toBeNull()
            ->and(ClaimReplyRun::query()->firstOrFail()->result)->toBe(ClaimReplyRun::SENDING)
            ->and(count(claimMails()))->toBeLessThanOrEqual(1);
    });

    test('the model gets a time limit shorter than the retry time of the queue', function () {
        expect(ClaimReplyResponder::MODEL_TIMEOUT)->toBeLessThan(90)->and((new AnswerClaimTicket(1))->timeout)->toBeLessThan(90);
    });
});

describe('an abandoned run', function () {
    function abandonedClaimRun(array $overrides = []): ClaimReplyRun
    {
        $run = ClaimReplyRun::query()->create(array_merge(['zammad_id' => 51234, 'ticket_number' => '2137942', 'mode' => 'on', 'result' => ClaimReplyRun::WORKING, 'attempts' => 1], $overrides));
        ClaimReplyRun::query()->whereKey($run->id)->update(['updated_at' => now()->subMinutes(6)]);

        return $run->refresh();
    }

    test('a run that stayed working for minutes is taken over and answered', function () {
        claimSetup();
        ClaimReplyAgent::fake([claimReply()]);
        $run = abandonedClaimRun();

        app(ClaimReplyResponder::class)->run(claimLabel());

        expect($run->refresh()->result)->toBe(ClaimReplyRun::SENT)->and($run->attempts)->toBe(2)->and(claimMails())->toHaveCount(1);
    });

    test('a run that is sending is never taken over', function () {
        claimSetup();
        ClaimReplyAgent::fake([claimReply()]);
        $run = abandonedClaimRun(['result' => ClaimReplyRun::SENDING]);

        app(ClaimReplyResponder::class)->run(claimLabel());

        expect($run->refresh()->result)->toBe(ClaimReplyRun::SENDING)->and(claimPuts())->toBe([]);
    });

    test('the recover command starts abandoned runs again', function () {
        Queue::fake();
        claimLabel();
        abandonedClaimRun();

        $this->artisan('claim-reply:recover')->assertSuccessful();

        Queue::assertPushed(AnswerClaimTicket::class, 1);
    });
});

describe('the trigger after the classification', function () {
    function classifyClaim(array $answer = [], array $ticket = []): void
    {
        $label = claimLabel(array_merge(['state' => 'captured'], $ticket));
        LabelAgent::fake([array_merge(['category' => 'reklamation', 'unclear' => false, 'multiple_concerns' => false, 'confidence' => 0.96, 'reason' => 'Mangel.', 'evidence' => 'kaputt'], $answer)]);
        app(LabelClassifier::class)->classify($label);
    }

    beforeEach(function () {
        Queue::fake();
        config(['labeling.model' => 'gpt-test']);
    });

    test('a clear complaint of a new ticket starts the reply', function () {
        classifyClaim();

        Queue::assertPushed(AnswerClaimTicket::class);
    });

    test('nothing starts for another category, a doubtful proposal, an old ticket or the switch off', function () {
        foreach ([['category' => 'wismo'], ['unclear' => true], ['multiple_concerns' => true], ['confidence' => 0.5]] as $index => $answer) {
            LabelTicket::query()->delete();
            classifyClaim($answer, ['zammad_id' => 100 + $index]);
        }

        LabelTicket::query()->delete();
        classifyClaim(ticket: ['source' => 'pool', 'zammad_id' => 200]);
        LabelTicket::query()->delete();
        app(SettingsStore::class)->save('claim_reply_mode', 'off', 'Etienne');
        classifyClaim(ticket: ['zammad_id' => 202]);

        Queue::assertNotPushed(AnswerClaimTicket::class);
    });

    test('a ticket of the test set is answered too and leaves the test set', function () {
        classifyClaim(ticket: ['split' => 'test', 'zammad_id' => 201]);

        Queue::assertPushed(AnswerClaimTicket::class);
        expect(LabelTicket::query()->where('zammad_id', 201)->value('split'))->toBe('training');
    });
});

describe('the pages', function () {
    test('the default of the switch is off', function () {
        expect(app(SettingsStore::class)->isDefault('claim_reply_mode'))->toBeFalse();
        app(SettingsStore::class)->reset('claim_reply_mode', 'Etienne');
        expect(app(SettingsStore::class)->value('claim_reply_mode'))->toBe('off');
    });

    test('only admins change the settings, everyone sees them', function () {
        $this->withCookie('staff_name', 'Cara')->get(route('settings.claim-reply'))->assertOk()->assertSee('Reklamations-Automatik');
        $this->withCookie('staff_name', 'Cara')->put(route('settings.claim-reply.update'), ['settings' => ['claim_reply_mode' => 'dry'], 'versions' => []])->assertForbidden();
    });

    test('only admins see the log, with the filter and the texts', function () {
        $this->withCookie('staff_name', 'Cara')->get(route('claim-reply-runs.index'))->assertForbidden();

        ClaimReplyRun::factory()->create(['ticket_number' => '2140001', 'result' => ClaimReplyRun::SENT, 'reply_text' => 'Bitte ein Foto', 'reply_text_de' => 'Bitte ein Foto']);
        ClaimReplyRun::factory()->create(['ticket_number' => '2140002', 'result' => ClaimReplyRun::NOTE, 'reason' => 'Der Versand liegt 41 Tage zurück']);

        $this->withCookie('staff_name', 'Etienne')->get(route('claim-reply-runs.index'))->assertOk()->assertSee('Ticket#2140001')->assertSee('Ticket#2140002')->assertSee('Alle (2)');
        $this->withCookie('staff_name', 'Etienne')->get(route('claim-reply-runs.index', ['ergebnis' => 'note']))->assertSee('Ticket#2140002')->assertDontSee('Ticket#2140001');
        $this->withCookie('staff_name', 'Etienne')->get(route('claim-reply-runs.show', ClaimReplyRun::query()->where('ticket_number', '2140001')->firstOrFail()))->assertOk()->assertSee('Gesendete Antwort')->assertSee('Bitte ein Foto');
    });

    test('the texts are emptied after 90 days, the result stays', function () {
        $old = ClaimReplyRun::factory()->create(['reply_text' => 'alt', 'reply_text_de' => 'alt', 'created_at' => now()->subDays(91)]);
        $new = ClaimReplyRun::factory()->create(['reply_text' => 'neu', 'reply_text_de' => 'neu']);

        $this->artisan('claim-reply:purge')->assertSuccessful();

        expect($old->refresh()->reply_text)->toBeNull()->and($old->result)->toBe(ClaimReplyRun::SENT)->and($new->refresh()->reply_text)->toBe('neu');
    });
});
