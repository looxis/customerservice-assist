<?php

use App\Zammad\AttachmentStore;
use App\Zammad\ZammadClient;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

const PROJ60_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('ticket-attachments');
    config(['services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'geheim-123', 'services.zammad.timeout' => 10]);
});

function attachmentTicket(array $extra = []): void
{
    fakeZammad([zammadArticle(['id' => 900, 'attachments' => [
        ['id' => 31, 'filename' => 'tasse.png', 'size' => 70, 'preferences' => ['Content-Type' => 'image/png']],
        ['id' => 32, 'filename' => 'foto.heic', 'size' => 5000, 'preferences' => ['Content-Type' => 'image/heic']],
        ['id' => 33, 'filename' => 'rechnung.pdf', 'size' => 51200, 'preferences' => ['Content-Type' => 'application/pdf']],
        ['id' => 35, 'filename' => 'IMG_3700.jpeg', 'size' => 70, 'preferences' => ['Content-Type' => 'image/jpeg; name=img_3700.jpeg']],
        ['id' => 36, 'filename' => '20261024_103508.jpg', 'size' => 70, 'preferences' => ['Content-Type' => 'image/jpg']],
        ['id' => 34, 'filename' => 'riesig.png', 'size' => 30 * 1024 * 1024, 'preferences' => ['Content-Type' => 'image/png']],
    ]])], extra: array_merge([
        'zammad.test/api/v1/ticket_attachment/51234/900/31' => Http::response(base64_decode(PROJ60_PNG), 200, ['Content-Type' => 'image/png']),
        'zammad.test/api/v1/ticket_attachment/51234/900/32' => Http::response('HEIC-DATEN', 200),
        'zammad.test/api/v1/ticket_attachment/51234/900/33' => Http::response('%PDF-1.4 test', 200),
    ], $extra));
}

function attachmentUrl(int $article, int $attachment, string $query = ''): string
{
    return route('tickets.attachments.show', ['number' => '2137942', 'article' => $article, 'attachment' => $attachment]).$query;
}

describe('ticket page', function () {
    test('an image attachment can be viewed and downloaded, other files only downloaded', function () {
        attachmentTicket();

        $html = $this->get('/tickets/2137942')->assertOk()->getContent();

        expect($html)
            ->toContain('view-attachment')
            ->toContain(e(attachmentUrl(900, 31, '?download=1')))
            ->toContain('x-data="imageViewer"');
        expect(substr_count($html, 'x-data="imageViewer"'))->toBe(1);
        $this->get('/tickets/2137942')->assertSeeTextInOrder(['tasse.png', 'Ansehen', 'Herunterladen', 'foto.heic', 'Herunterladen', 'rechnung.pdf', 'Herunterladen']);
    });
});

describe('content type from Zammad', function () {
    test('a content type with parameters is still a viewable image', function () {
        attachmentTicket();

        $ticket = app(ZammadClient::class)->ticket('2137942');
        $file = collect($ticket->articles[0]->attachments)->firstWhere('id', 35);

        expect($file->contentType)->toBe('image/jpeg')->and($file->isViewable())->toBeTrue();
        $this->get('/tickets/2137942')->assertOk()->assertSee('IMG_3700.jpeg');
    });

    test('the non-standard type image/jpg is treated as JPEG and viewable', function () {
        attachmentTicket();

        $file = collect(app(ZammadClient::class)->ticket('2137942')->articles[0]->attachments)->firstWhere('id', 36);

        expect($file->contentType)->toBe('image/jpeg')->and($file->isViewable())->toBeTrue();
    });
});

describe('viewer buttons', function () {
    test('only the images that a browser can show get a view button, HEIC and PDF do not', function () {
        attachmentTicket();

        $html = $this->get('/tickets/2137942')->assertOk()->getContent();

        // Viewable: tasse.png (31), riesig.png (34, too large only when fetched), IMG_3700.jpeg (35) and the image/jpg file (36). The message is shown twice
        // on the page (the message to read first and the thread), so each button appears twice; the viewer has one listener.
        expect(substr_count($html, "\$dispatch('view-attachment'"))->toBe(8);
        expect(substr_count($html, 'x-on:view-attachment.window'))->toBe(1);
        expect($html)->toContain(e(attachmentUrl(900, 32, '?download=1')))->toContain(e(attachmentUrl(900, 33, '?download=1')));
    });
});

describe('download', function () {
    test('an image comes inline with its own type and is fetched from Zammad only once', function () {
        attachmentTicket();

        $this->withCookie('staff_name', 'Nele')->get(attachmentUrl(900, 31))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->withCookie('staff_name', 'Kerstin')->get(attachmentUrl(900, 31))->assertOk();

        $downloads = Http::recorded(fn (Request $request): bool => str_contains($request->url(), 'ticket_attachment/'));
        expect($downloads)->toHaveCount(1);
        expect(Storage::disk('ticket-attachments')->exists('51234/900-31'))->toBeTrue();
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'ticket_attachment/') && $request->hasHeader('Authorization', 'Token token=geheim-123'));
    });

    test('with download=1 the image is sent as a download', function () {
        attachmentTicket();

        $response = $this->withCookie('staff_name', 'Nele')->get(attachmentUrl(900, 31, '?download=1'))->assertOk();

        expect($response->headers->get('Content-Disposition'))->toStartWith('attachment')->toContain('tasse.png');
    });

    test('HEIC and PDF are only offered as a download, never shown inline', function () {
        attachmentTicket();

        foreach ([32 => 'foto.heic', 33 => 'rechnung.pdf'] as $id => $name) {
            $response = $this->withCookie('staff_name', 'Nele')->get(attachmentUrl(900, $id))->assertOk();

            expect($response->headers->get('Content-Disposition'))->toStartWith('attachment')->toContain($name);
            expect($response->headers->get('Content-Type'))->toBe('application/octet-stream');
        }
    });

    test('a file whose content is not an image is not shown inline although Zammad calls it an image', function () {
        attachmentTicket(['zammad.test/api/v1/ticket_attachment/51234/900/31' => Http::response('<script>alert(1)</script>', 200, ['Content-Type' => 'image/png'])]);

        $response = $this->withCookie('staff_name', 'Nele')->get(attachmentUrl(900, 31))->assertOk();

        expect($response->headers->get('Content-Disposition'))->toStartWith('attachment');
    });

    test('an attachment that is not part of this ticket is not found and nothing is fetched', function () {
        attachmentTicket();

        $this->withCookie('staff_name', 'Nele')->get(attachmentUrl(900, 999))->assertNotFound();
        $this->withCookie('staff_name', 'Nele')->get(attachmentUrl(901, 31))->assertNotFound();

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'ticket_attachment/'));
    });

    test('without a chosen staff name the call is refused', function () {
        attachmentTicket();

        $this->getJson(attachmentUrl(900, 31))->assertStatus(409);
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'ticket_attachment/'));
    });

    test('a file that is too large is not fetched', function () {
        attachmentTicket();

        $this->withCredentials()->withCookie('staff_name', 'Nele')->getJson(attachmentUrl(900, 34))->assertStatus(413)->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'zu groß'));
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'ticket_attachment/'));
    });

    test('when Zammad cannot deliver the file, a clear message comes back', function () {
        attachmentTicket(['zammad.test/api/v1/ticket_attachment/51234/900/31' => Http::response('', 500)]);

        $this->withCredentials()->withCookie('staff_name', 'Nele')->getJson(attachmentUrl(900, 31))->assertStatus(502)->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'erneut versuchen'));
        expect(Storage::disk('ticket-attachments')->allFiles())->toBe([]);
    });
});

describe('purge', function () {
    test('only files older than the retention period are deleted and empty folders go too', function () {
        $disk = Storage::disk('ticket-attachments');
        $disk->put('1/10-1', 'alt');
        $disk->put('2/20-2', 'neu');
        touch($disk->path('1/10-1'), now()->subDays(31)->getTimestamp());
        touch($disk->path('2/20-2'), now()->subDays(29)->getTimestamp());

        $counts = app(AttachmentStore::class)->purge();

        expect($counts)->toBe(['files' => 1, 'folders' => 1]);
        expect($disk->exists('1/10-1'))->toBeFalse()->and($disk->exists('2/20-2'))->toBeTrue();
    });

    test('the artisan command runs and is scheduled daily', function () {
        $this->artisan('attachments:purge')->expectsOutputToContain('Anhänge gelöscht: 0')->assertSuccessful();

        expect(collect(app(Schedule::class)->events())->contains(fn ($event): bool => str_contains($event->command, 'attachments:purge')))->toBeTrue();
    });
});

describe('images embedded in the mail text', function () {
    /**
     * A customer mail as Zammad delivers it: images in the text point to the attachment, each attachment has a Content-ID.
     */
    function embeddedTicket(string $body, array $attachments): void
    {
        fakeZammad([zammadArticle(['id' => 900, 'body' => $body, 'attachments' => array_map(fn (array $file): array => [
            'id' => $file[0], 'filename' => $file[1], 'size' => $file[2],
            'preferences' => ['Content-Type' => 'image/png; name='.$file[1], 'Content-ID' => "{$file[0]}@mail", 'resizable' => true],
        ], $attachments)])], extra: [
            'zammad.test/api/v1/ticket_attachment/51234/900/41' => Http::response(base64_decode(PROJ60_PNG), 200, ['Content-Type' => 'image/png']),
        ]);
    }

    function embeddedImage(int $id): string
    {
        return '<img src="/api/v1/ticket_attachment/51234/900/'.$id.'?view=inline">';
    }

    test('a screenshot in the new text is offered like an attachment, with "Ansehen" in the viewer', function () {
        embeddedTicket('<div>Hier meine Bestellung:</div><div>'.embeddedImage(41).'</div>', [[41, 'image0.png', 819227]]);

        $this->get('/tickets/2137942')->assertOk()
            ->assertSeeTextInOrder(['image0.png', 'Bild im Text', 'Ansehen', 'Herunterladen'])
            ->assertSee(e(attachmentUrl(900, 41, '?download=1')), false);
        $this->withCookie('staff_name', 'Nele')->get(attachmentUrl(900, 41))->assertOk()->assertHeader('Content-Type', 'image/png');
    });

    test('an image only in the mail, without any text, is offered too', function () {
        embeddedTicket('<div>'.embeddedImage(41).'</div>', [[41, 'image0.png', 819227]]);

        expect(app(ZammadClient::class)->ticket('2137942')->articles[0]->attachments)->toHaveCount(1);
    });

    test('logos in the signature, images in a quote and small icons stay out', function () {
        embeddedTicket(
            '<div>Die Box ist kaputt.</div><div>'.embeddedImage(44).'</div><div>Mit freundlichen Grüßen</div><div>Erika Beispiel</div><div>'.embeddedImage(42).'</div>'
            .'<div class="gmail_quote">Am 01.10.2026 schrieb LOOXIS:<blockquote>Alte Nachricht '.embeddedImage(43).'</blockquote></div>',
            [[42, 'logo.png', 59181], [43, 'alt.png', 150666], [44, 'icon.png', 964]],
        );

        $ticket = app(ZammadClient::class)->ticket('2137942');

        expect($ticket->articles[0]->attachments)->toBe([]);
    });

    test('photos a mobile mail app puts below the signature are offered, the logo there is not', function () {
        embeddedTicket(
            '<div>Die Box ist kaputt.</div><div>Mit freundlichen Grüßen</div><div>Erika Beispiel</div><div>Musterweg 1</div><div>'.embeddedImage(42).embeddedImage(41).'</div>',
            [[41, 'image0.png', 819227], [42, 'logo.png', 59181]],
        );

        $attachments = app(ZammadClient::class)->ticket('2137942')->articles[0]->attachments;

        expect(array_map(fn ($file): string => $file->filename, $attachments))->toBe(['image0.png'])
            ->and($attachments[0]->embedded)->toBeTrue();
    });

    test('the mark never shows in the text', function () {
        embeddedTicket('<div>Hier meine Bestellung:</div><div>'.embeddedImage(41).'</div>', [[41, 'image0.png', 819227]]);

        $this->get('/tickets/2137942')->assertOk()->assertDontSee('csa-image')->assertDontSee("\u{27E6}");
    });
});
