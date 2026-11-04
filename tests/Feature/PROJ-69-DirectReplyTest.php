<?php

use App\Analysis\Agents\CaseAgent;
use App\Models\Analysis;
use App\Models\TrackedTicket;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'staff.zammad_tokens' => ['Nele' => 'nele-secret-token', 'Cara' => null],
    ]);

    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
    fakeZammad(extra: [
        'zammad.test/api/v1/users/me' => Http::response(['id' => 501, 'firstname' => 'Nele', 'lastname' => 'Gorka', 'login' => 'nele']),
        'zammad.test/api/v1/tickets/51234' => Http::response(['id' => 51234, 'number' => '2137942']),
    ]);
});

function startDirectReply(string $staff = 'Nele'): mixed
{
    return test()->withCookie('staff_name', $staff)->post(route('tickets.manual-reply', ['number' => '2137942']));
}

function manualId(mixed $response): string
{
    parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

    return $query['analyse'];
}

describe('the buttons', function () {
    test('both places show "Direkt antworten" next to the close button', function () {
        $html = $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()
            ->assertSeeTextInOrder(['Aktualisieren', 'Direkt antworten', 'Ohne Antwort schließen', 'Vorschlag erstellen', 'oder', 'Direkt antworten', 'Ticket ohne Antwort schließen'])
            ->getContent();

        expect(substr_count($html, route('tickets.manual-reply', ['number' => '2137942'])))->toBe(2);
    });

    test('no button without a personal token', function () {
        $this->withCookie('staff_name', 'Cara')->get('/tickets/2137942')->assertOk()->assertDontSeeText('Direkt antworten');
    });
});

describe('starting a direct reply', function () {
    test('stores an empty manual draft without any language model call and opens the sending view', function () {
        CaseAgent::fake([]);

        $response = startDirectReply();
        $id = manualId($response);

        $response->assertRedirect();
        expect($response->headers->get('Location'))->toEndWith('#antwort-versenden');
        expect(Analysis::query()->where('uuid', $id)->value('status'))->toBe('manual');

        $this->withCookie('staff_name', 'Nele')->get(route('tickets.show', ['number' => '2137942', 'analyse' => $id]))->assertOk()
            ->assertSeeText('Direkt antworten')
            ->assertSeeText('Antwort versenden')
            ->assertDontSeeText('Confidence')
            ->assertDontSeeText('Original der KI wiederherstellen');
    });

    test('a second click reuses the unused draft', function () {
        $first = manualId(startDirectReply());
        $second = manualId(startDirectReply());

        expect($second)->toBe($first)->and(Analysis::query()->count())->toBe(1);
    });

    test('it does not count as an analysis of the ticket', function () {
        startDirectReply();

        expect(Analysis::query()->where('status', 'completed')->count())->toBe(0)
            ->and(TrackedTicket::query()->where('number', '2137942')->where('processing_state', 'analyzed')->exists())->toBeFalse();
    });

    test('is refused without a personal token', function () {
        startDirectReply('Cara')->assertForbidden();

        expect(Analysis::query()->count())->toBe(0);
    });

    test('the draft can be saved and sent through the usual routes', function () {
        $id = manualId(startDirectReply());

        $this->withCredentials()->withCookie('staff_name', 'Nele')->putJson(route('tickets.analysis.reply', ['number' => '2137942', 'analysis' => $id]), ['text' => 'Guten Tag, danke für Ihre Nachricht.'])
            ->assertOk()->assertJson(['saved' => true]);

        $this->withCredentials()->withCookie('staff_name', 'Nele')->postJson(route('tickets.analysis.send', ['number' => '2137942', 'analysis' => $id]), [
            'to' => 'erika@example.org', 'subject' => 'Re: Motiv erscheint nicht', 'text' => 'Guten Tag, danke für Ihre Nachricht.', 'signature' => 'Nele', 'after' => 'close',
        ])->assertOk();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT' && str_ends_with($request->url(), 'tickets/51234'));
        expect(Analysis::query()->where('uuid', $id)->value('reply_sent_at'))->not->toBeNull();
    });
});
