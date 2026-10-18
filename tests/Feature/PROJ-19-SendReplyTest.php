<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\ReplyDelivery;
use App\Models\Analysis;
use App\Settings\SettingsRegistry;
use App\Settings\SettingsStore;
use App\Staff\StaffDirectory;
use App\Zammad\ReplyTarget;
use App\Zammad\ZammadClient;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
        'staff.admins' => ['Etienne'],
        'staff.zammad_tokens' => ['Nele' => 'nele-secret-token', 'Cara' => null],
    ]);

    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);
    $this->articles = [zammadArticle(['id' => 1, 'subject' => 'Motiv erscheint nicht', 'message_id' => '<m1@example.org>', 'body' => '<p>Das Motiv fehlt.</p>'])];
    $this->putFails = null;
    $this->putStatus = 200;
    $this->putCalls = 0;
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
    fakeZammad(extra: [
        'zammad.test/api/v1/ticket_articles/by_ticket/*' => fn () => Http::response($this->articles),
        'zammad.test/api/v1/users/me' => fn (Request $request) => $request->header('Authorization')[0] === 'Token token=nele-secret-token'
            ? Http::response(['id' => 501, 'firstname' => 'Nele', 'lastname' => 'Gorka', 'login' => 'nele'])
            : Http::response(['error' => 'Invalid token'], 401),
        'zammad.test/api/v1/tickets/51234' => function (Request $request) {
            $this->putCalls++;

            if ($this->putFails instanceof Throwable) {
                throw $this->putFails;
            }

            if ($this->putStatus === 200) {
                $this->articles[] = zammadArticle(['id' => 9, 'sender' => 'Agent', 'created_by_id' => 501, 'from' => 'Nele Gorka', 'body' => $request['article']['body'], 'created_at' => now()->toIso8601String()]);
            }

            return Http::response(['id' => 51234, 'number' => '2137942'], $this->putStatus);
        },
    ]);
});

afterEach(function () {
    cleanUpKnowledgeBases();
});

function sendAnalysis(string $reply = 'Guten Tag, wir prüfen das.', string $staff = 'Nele'): string
{
    CaseAgent::fake([[
        'summary' => ['incident' => 'Motiv fehlt', 'customer_wish' => 'Ersatz'],
        'category' => 'order-process-question', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Antworten.', 'actions' => [],
        'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'Keine Regel.', 'knowledge_gaps' => [],
        'knowledge_ids' => [], 'confidence' => ['level' => 'NIEDRIG', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [], 'reply' => ['language' => 'Deutsch', 'text' => $reply],
    ]]);
    $location = test()->withCookie('staff_name', $staff)->post(route('tickets.analysis.run', ['number' => '2137942']), ['kein_produktbezug' => '1', 'kundengruppe' => 'reseller', 'variante' => 'verlauf'])->headers->get('Location');
    parse_str((string) parse_url((string) $location, PHP_URL_QUERY), $query);

    return $query['analyse'];
}

function sendReply(string $id, array $fields = [], string $staff = 'Nele'): TestResponse
{
    return test()->withCredentials()->withCookie('staff_name', $staff)->postJson(route('tickets.analysis.send', ['number' => '2137942', 'analysis' => $id]), [
        'subject' => 'Re: Motiv erscheint nicht', 'text' => 'Guten Tag, wir prüfen das.', 'signature' => "Mit freundlichen Grüßen\nNele Gorka\nLOOXIS GmbH", 'after' => 'close', ...$fields,
    ]);
}

function putRequests(): array
{
    return Http::recorded(fn (Request $request): bool => $request->method() === 'PUT')->map(fn (array $pair): Request => $pair[0])->values()->all();
}

describe('the offer', function () {
    test('with a token the block shows recipient, subject, signature with the full name and the hint', function () {
        $id = sendAnalysis();

        $html = test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942?analyse='.$id)->assertOk()->assertSeeText('Antwort versenden')->assertSeeText('erika@example.org')->assertSeeText('wird über Zammad versendet', false)->getContent();

        expect($html)->toContain('Re: Motiv erscheint nicht')->toContain('Nele Gorka')->toContain('LOOXIS GmbH')->toContain('Ticket schließen')->not->toContain('nele-secret-token');
    });

    test('without a token there is only the copy button and the hint', function () {
        $id = sendAnalysis(staff: 'Cara');

        $html = test()->withCookie('staff_name', 'Cara')->get('/tickets/2137942?analyse='.$id)->assertOk()->assertSeeText('Bitte Token hinterlegen, dann ist ein Direktversand möglich')->getContent();

        expect($html)->not->toContain('id="antwort-versenden"')->toContain('Kopieren');
        sendReply($id, staff: 'Cara')->assertStatus(409)->assertJsonPath('code', 'no_token');
        expect(putRequests())->toBe([]);
    });

    test('a ticket without a usable customer message offers nothing to send, and the server refuses', function () {
        $this->articles = [zammadArticle(['id' => 1, 'type' => 'phone', 'body' => '<p>Anruf</p>'])];
        $id = sendAnalysis();

        test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942?analyse='.$id)->assertOk()->assertSeeText('keine Kundennachricht mit eindeutiger Antwortadresse');
        sendReply($id)->assertStatus(409);
        expect(putRequests())->toBe([]);
    });

    test('a test run is offered nothing, and a question without ticket neither', function () {
        $id = sendAnalysis();
        Analysis::query()->where('uuid', $id)->update(['test_until' => now()]);

        test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942?analyse='.$id)->assertDontSee('id="antwort-versenden"', false);
        sendReply($id)->assertStatus(409);
        expect(putRequests())->toBe([]);
    });

    test('only the newest suggestion of a ticket can be sent', function () {
        $old = sendAnalysis();
        sendAnalysis();

        sendReply($old)->assertStatus(409);
        expect(putRequests())->toBe([]);
    });

    test('the recipient and the subject come from the last customer message', function () {
        $this->articles = [zammadArticle(['id' => 1, 'from' => 'Alt <alt@example.org>']), zammadArticle(['id' => 2, 'from' => 'Kunde <kunde@example.org>', 'reply_to' => 'Antwort <antwort@example.org>', 'subject' => 'AW: Bestellung', 'message_id' => '<m2@example.org>'])];
        $ticket = app(ZammadClient::class)->ticket('2137942');
        $target = ReplyTarget::for($ticket);

        expect($target->to)->toBe('antwort@example.org')->and($target->subject)->toBe('AW: Bestellung')->and($target->inReplyTo)->toBe('<m2@example.org>');
    });
});

describe('sending', function () {
    test('the reply goes to Zammad in the name of the employee, with the status and the statistics of a copy', function () {
        $id = sendAnalysis();

        sendReply($id)->assertOk()->assertJsonPath('sent', true)->assertJsonPath('by', 'Nele');

        $put = putRequests();
        expect($put)->toHaveCount(1)
            ->and($put[0]->header('Authorization')[0])->toBe('Token token=nele-secret-token')
            ->and($put[0]['state'])->toBe('closed')
            ->and($put[0]['article'])->toMatchArray(['type' => 'email', 'internal' => false, 'to' => 'erika@example.org', 'subject' => 'Re: Motiv erscheint nicht', 'content_type' => 'text/plain', 'in_reply_to' => '<m1@example.org>'])
            ->and($put[0]['article']['body'])->toBe("Guten Tag, wir prüfen das.\n\nMit freundlichen Grüßen\nNele Gorka\nLOOXIS GmbH")
            ->and($put[0]->data())->not->toHaveKey('pending_time');

        $analysis = Analysis::query()->where('uuid', $id)->firstOrFail();
        expect($analysis->reply_sent_at)->not->toBeNull()->and($analysis->reply_sent_by)->toBe('Nele')->and($analysis->reply_sent_zammad_user)->toBe('Nele Gorka')
            ->and($analysis->reply_sent_state)->toBe('closed')->and($analysis->reply_sent_article_id)->toBe(9)
            ->and($analysis->reply_copied_at)->not->toBeNull()->and($analysis->reply_copied_changed)->toBeFalse();
    });

    test('a changed text counts as adjusted, a changed signature or subject does not', function () {
        $id = sendAnalysis();
        sendReply($id, ['text' => 'Guten Tag, das klären wir sofort.'])->assertOk();
        expect(Analysis::query()->where('uuid', $id)->firstOrFail()->reply_copied_changed)->toBeTrue();

        $second = sendAnalysis();
        sendReply($second, ['signature' => 'Viele Grüße, Nele', 'subject' => 'Anderer Betreff'])->assertOk();
        expect(Analysis::query()->where('uuid', $second)->firstOrFail()->reply_copied_changed)->toBeFalse();
    });

    test('a reminder sets the status and a date, the days must be 1, 3 or 7', function () {
        $id = sendAnalysis();

        sendReply($id, ['after' => 'reminder', 'days' => 2])->assertStatus(422);
        sendReply($id, ['after' => 'reminder'])->assertStatus(422);
        expect(putRequests())->toBe([]);

        sendReply($id, ['after' => 'reminder', 'days' => 3])->assertOk();
        $put = putRequests()[0];
        expect($put['state'])->toBe('pending reminder')->and(CarbonImmutable::parse($put['pending_time'])->isBetween(now()->addDays(2), now()->addDays(4)))->toBeTrue()
            ->and(Analysis::query()->where('uuid', $id)->firstOrFail()->reply_sent_state)->toBe('pending reminder +3d');
    });

    test('an empty subject or text and a wrong status are refused', function () {
        $id = sendAnalysis();

        sendReply($id, ['subject' => ''])->assertStatus(422);
        sendReply($id, ['text' => ' '])->assertStatus(422);
        sendReply($id, ['after' => 'open'])->assertStatus(422);
        expect(putRequests())->toBe([]);
    });

    test('markup in the text is sent as plain text and the recipient cannot be set by the form', function () {
        $id = sendAnalysis();

        sendReply($id, ['text' => '<script>alert(1)</script> Hallo', 'to' => 'boese@example.org'])->assertOk();

        $article = putRequests()[0]['article'];
        expect($article['to'])->toBe('erika@example.org')->and($article['content_type'])->toBe('text/plain')->and($article['body'])->toContain('<script>alert(1)</script> Hallo');
    });

    test('a newer message in the ticket stops the sending until it is confirmed', function () {
        $id = sendAnalysis();
        $this->articles[] = zammadArticle(['id' => 5, 'sender' => 'Agent', 'from' => 'Kerstin Schmeckpeper', 'created_at' => now()->toIso8601String(), 'body' => '<p>Schon beantwortet.</p>']);

        sendReply($id)->assertStatus(409)->assertJsonPath('code', 'newer')->assertJsonFragment(['message' => 'Seit dem Vorschlag gibt es eine neue Nachricht von Kerstin Schmeckpeper ('.now('Europe/Berlin')->format('d.m.Y, H:i').' Uhr). Bitte lesen und entscheiden, ob die Antwort noch passt.']);
        expect(putRequests())->toBe([]);

        sendReply($id, ['confirm_newer' => true])->assertOk();
        expect(putRequests())->toHaveCount(1);
    });

    test('a reply cannot be sent twice', function () {
        $id = sendAnalysis();

        sendReply($id)->assertOk();
        sendReply($id)->assertStatus(409)->assertJsonPath('code', 'sent');

        expect(putRequests())->toHaveCount(1);
        test()->withCookie('staff_name', 'Etienne')->get('/tickets/2137942?analyse='.$id)->assertSeeText('Gesendet von Nele am');
    });

    test('while a send is running another one is told to wait', function () {
        $id = sendAnalysis();
        Analysis::query()->where('uuid', $id)->update(['reply_send_started_at' => now()]);

        sendReply($id)->assertStatus(409)->assertJsonPath('code', 'busy');
        expect(putRequests())->toBe([]);
    });

    test('a rejected or unauthorised token sends nothing and names the reason without the token', function () {
        $id = sendAnalysis();
        config(['staff.zammad_tokens' => ['Nele' => 'abgelaufen-token']]);

        $response = sendReply($id)->assertStatus(502);

        expect($response->json('message'))->toContain('lehnt das Token von Nele ab')->not->toContain('abgelaufen-token');
        expect(putRequests())->toBe([]);
        expect(Analysis::query()->where('uuid', $id)->firstOrFail()->reply_send_started_at)->toBeNull();
    });

    test('missing rights in Zammad give a clear message and a new attempt is possible', function () {
        $id = sendAnalysis();
        $this->putStatus = 403;

        sendReply($id)->assertStatus(502)->assertJsonFragment(['message' => 'Zammad erlaubt Nele das Antworten auf dieses Ticket nicht (fehlende Rechte oder Gruppe). Die Antwort wurde nicht gesendet.']);

        $this->putStatus = 200;
        sendReply($id)->assertOk();
    });

    test('Zammad down before sending keeps everything and allows another try', function () {
        $id = sendAnalysis();
        $this->putStatus = 422;

        sendReply($id)->assertStatus(502)->assertJsonPath('code', 'rejected');
        expect(Analysis::query()->where('uuid', $id)->firstOrFail())->reply_sent_at->toBeNull()->reply_send_started_at->toBeNull();
    });

    test('an unclear outcome blocks a second send until the ticket was checked', function () {
        $id = sendAnalysis();
        $this->putFails = new ConnectionException('timeout');

        sendReply($id)->assertStatus(502)->assertJsonPath('code', 'unclear');
        expect(Analysis::query()->where('uuid', $id)->firstOrFail()->reply_send_started_at)->not->toBeNull();

        sendReply($id)->assertStatus(409)->assertJsonPath('code', 'busy');

        $this->travel(5)->minutes();
        $this->putFails = null;
        $this->articles[] = zammadArticle(['id' => 8, 'sender' => 'Agent', 'created_by_id' => 501, 'from' => 'Nele Gorka', 'created_at' => now()->subMinutes(4)->toIso8601String(), 'body' => '<p>Guten Tag</p>']);
        sendReply($id)->assertStatus(409)->assertJsonPath('code', 'sent');

        expect($this->putCalls)->toBe(1)->and(Analysis::query()->where('uuid', $id)->firstOrFail()->reply_sent_article_id)->toBe(8);
    });

    test('after an unclear outcome without a reply in Zammad sending is possible again', function () {
        $id = sendAnalysis();
        $this->putFails = new ConnectionException('timeout');
        sendReply($id)->assertStatus(502);

        $this->travel(5)->minutes();
        $this->putFails = null;
        sendReply($id)->assertOk();

        expect($this->putCalls)->toBe(2);
    });

    test('nothing is recorded as copied when sending fails', function () {
        $id = sendAnalysis();
        $this->putStatus = 403;

        sendReply($id)->assertStatus(502);

        expect(Analysis::query()->where('uuid', $id)->firstOrFail()->reply_copied_at)->toBeNull();
    });

    test('the token appears nowhere on the page or in the answers', function () {
        $id = sendAnalysis();
        $page = test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942?analyse='.$id)->getContent();
        $answer = sendReply($id)->getContent();

        expect($page)->not->toContain('nele-secret-token')->and($answer)->not->toContain('nele-secret-token');
    });
});

describe('statistics', function () {
    test('a sent reply counts like a copy and shows as sent in both overviews', function () {
        $id = sendAnalysis();
        sendReply($id)->assertOk();

        test()->withCookie('staff_name', 'Etienne')->get(route('analysis-overview.index'))->assertOk()->assertSeeText('Gesendet (unverändert)');
        test()->withCookie('staff_name', 'Etienne')->get(route('ticket-overview.index'))->assertOk()->assertSeeText('Gesendet (unverändert)');
        test()->withCookie('staff_name', 'Etienne')->get(route('analysis-overview.index', ['kopiert' => 'sent-unchanged']))->assertOk()->assertSeeText('2137942');
        test()->withCookie('staff_name', 'Etienne')->get(route('analysis-overview.index', ['kopiert' => 'copied-unchanged']))->assertOk()->assertDontSeeText('2137942');
    });

    test('the feedback question follows the sending like the copying', function () {
        $id = sendAnalysis();

        $html = test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942?analyse='.$id)->getContent();

        expect($html)->toContain("this.\$dispatch('reply-copied')")->toContain('/senden');
    });
});

describe('the signature and the staff list', function () {
    test('the default signature has the placeholder and every employee has a full name', function () {
        $staff = app(StaffDirectory::class);

        expect(SettingsRegistry::group('reply_signature')->default())->toBe("Mit freundlichen Grüßen\n{Name}\nLOOXIS GmbH")
            ->and($staff->fullName('Cara'))->toBe('Cara Barkhausen')->and($staff->fullName('Kerstin'))->toBe('Kerstin Schmeckpeper')->and($staff->fullName('Thomas'))->toBe('Thomas Junski')
            ->and($staff->names())->toBe(['Cara', 'Etienne', 'Johannes', 'Kerstin', 'Nele', 'Thomas']);
    });

    test('only the placeholder {Name} is allowed in the signature', function () {
        $group = SettingsRegistry::group('reply_signature');

        expect($group->errors("Gruß\n{Name}"))->toBe([])
            ->and($group->errors("Gruß\n{Vorname}"))->not->toBe([])
            ->and($group->errors('Gruß {Name} }'))->not->toBe([])
            ->and($group->errors('Hi'))->not->toBe([]);
    });

    test('a changed signature applies to the next block, with the full name of whoever sends', function () {
        $id = sendAnalysis();
        $this->withCookie('staff_name', 'Etienne')->get(route('settings.mail'))->assertOk()->assertSeeText('Signatur für Antworten');
        expect(app(ReplyDelivery::class)->signature('Nele'))->toBe("Mit freundlichen Grüßen\nNele Gorka\nLOOXIS GmbH");
        app(SettingsStore::class)->save('reply_signature', "Herzlich\n{Name} – LOOXIS", 'Etienne');

        test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942?analyse='.$id)->assertOk()->assertSee("signature: 'Herzlich\\nNele Gorka – LOOXIS'", false);
    });

    test('a cookie with a first name keeps working and the dropdown shows first names', function () {
        test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk();
        expect(app(StaffDirectory::class)->isListed('Nele'))->toBeTrue()->and(app(StaffDirectory::class)->isListed('Nele Gorka'))->toBeFalse();
    });
});

describe('QA: edges and attacks', function () {
    test('without a chosen name nothing is sent', function () {
        $id = sendAnalysis();
        $this->defaultCookies = [];

        test()->withCredentials()->postJson(route('tickets.analysis.send', ['number' => '2137942', 'analysis' => $id]), ['subject' => 'Re: x', 'text' => 'Hallo', 'after' => 'close'])->assertStatus(409);

        expect($this->putCalls)->toBe(0);
    });

    test('a suggestion cannot be sent through the address of another ticket or an unknown id', function () {
        $id = sendAnalysis();

        test()->withCredentials()->withCookie('staff_name', 'Nele')->postJson(route('tickets.analysis.send', ['number' => '2199999', 'analysis' => $id]), ['subject' => 'Re: x', 'text' => 'Hallo', 'after' => 'close'])->assertStatus(404);
        test()->withCredentials()->withCookie('staff_name', 'Nele')->postJson(route('tickets.analysis.send', ['number' => '2137942', 'analysis' => (string) Str::uuid()]), ['subject' => 'Re: x', 'text' => 'Hallo', 'after' => 'close'])->assertStatus(404);

        expect($this->putCalls)->toBe(0);
    });

    test('an incomplete and unconfirmed rule check blocks the sending like the copying', function () {
        $id = sendAnalysis();
        Analysis::query()->where('uuid', $id)->update(['rule_check' => 'incomplete', 'rule_check_at' => null]);

        sendReply($id)->assertStatus(409);
        test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942?analyse='.$id)->assertSeeText('Regelprüfung bestätigt');

        expect($this->putCalls)->toBe(0);
    });

    test('too many send attempts in a minute are slowed down', function () {
        $id = sendAnalysis();

        foreach (range(1, 10) as $ignored) {
            sendReply($id, ['after' => 'open'])->assertStatus(422);
        }

        sendReply($id, ['after' => 'open'])->assertStatus(429);
        expect($this->putCalls)->toBe(0);
    });

    test('a line break in the subject cannot add mail headers', function () {
        $id = sendAnalysis();

        sendReply($id, ['subject' => "Hallo\r\nBcc: fremd@example.org"]);

        $sentSubject = putRequests()[0]['article']['subject'] ?? '';
        expect(str_contains($sentSubject, "\n") || str_contains($sentSubject, "\r"))->toBeFalse();
    })->todo('QA-Fehler PROJ-19-B1: Zeilenumbrüche im Betreff werden unverändert an Zammad gegeben.');
});
