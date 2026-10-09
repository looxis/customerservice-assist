<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\Agents\TranslationAgent;
use App\Analysis\LanguageDetector;
use App\Models\Analysis;
use App\Models\MessageTranslation;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Testing\TestResponse;
use Laravel\Ai\Prompts\AgentPrompt;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
        'staff.admins' => ['Etienne'],
    ]);

    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);

    $this->articles = [
        zammadArticle(['id' => 1, 'body' => '<p>Buongiorno, la tazza che ho ricevuto non funziona. Scrivetemi a maria@example.org, grazie.</p>']),
        zammadArticle(['id' => 2, 'sender' => 'Agent', 'from' => 'Kundenservice', 'body' => '<p>Buongiorno, ci dispiace per il problema con la tazza. Grazie per la segnalazione.</p>', 'created_at' => '2026-10-02T08:00:00.000Z']),
        zammadArticle(['id' => 3, 'body' => '<p>Vielen Dank, ich habe die Bestellung noch nicht erhalten und bitte um eine Antwort.</p>', 'created_at' => '2026-10-03T08:00:00.000Z']),
    ];
    fakeZammad(extra: ['zammad.test/api/v1/ticket_articles/by_ticket/*' => fn () => Http::response($this->articles)]);
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function translationAnswer(array $messages): array
{
    return ['messages' => array_map(fn (array $message): array => array_merge(['language' => 'Italienisch', 'is_german' => false, 'translation' => ''], $message), $messages)];
}

function translateTicket(array $fields = [], string $staff = 'Nele'): TestResponse
{
    return test()->withCookie('staff_name', $staff)->post(route('tickets.translation.store', ['number' => '2137942']), $fields);
}

function ticketPage(string $staff = 'Nele'): TestResponse
{
    return test()->withCookie('staff_name', $staff)->get('/tickets/2137942');
}

describe('language detection without ai', function () {
    test('texts are told apart by frequent words', function (string $text, ?bool $foreign) {
        expect(app(LanguageDetector::class)->isForeign($text))->toBe($foreign);
    })->with([
        'german' => ['Guten Tag, ich habe die Bestellung noch nicht erhalten und bitte um Antwort.', false],
        'italian' => ['Buongiorno, la tazza che ho ricevuto non funziona, grazie.', true],
        'french' => ['Bonjour, je vous écris pour ma commande que je ne trouve pas. Merci.', true],
        'english' => ['Hello, I have not received the order yet, could you please check?', true],
        'dutch' => ['Geachte, ik heb mijn bestelling niet ontvangen. Met vriendelijke groet', true],
        'too short' => ['Ok, grazie', null],
        'no words' => ['402-1234567-1234567 / 11282 / 2026-10-01', null],
    ]);
});

describe('hint and trigger', function () {
    test('foreign messages without translation are counted above the thread with a button', function () {
        ticketPage()->assertSeeText('2 Nachrichten sind nicht auf Deutsch.')->assertSee('Übersetzen</button>', false)
            ->assertSee('action="'.route('tickets.translation.store', ['number' => '2137942']).'"', false);
    });

    test('a german ticket shows no hint', function () {
        $this->articles = [$this->articles[2]];

        ticketPage()->assertDontSeeText('nicht auf Deutsch');
    });

    test('one click translates all foreign messages, stores them and they appear for everyone without another call', function () {
        TranslationAgent::fake([translationAnswer([
            ['id' => '1', 'translation' => "Guten Tag, die Tasse funktioniert nicht.\nSchreiben Sie mir an [E-MAIL_1], danke."],
            ['id' => '2', 'translation' => 'Guten Tag, das Problem mit der Tasse tut uns leid.'],
        ])]);

        translateTicket()->assertRedirect()->assertSessionHas('translation_done', 2);

        TranslationAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('### Nachricht 1') && $prompt->contains('### Nachricht 2')
            && ! $prompt->contains('### Nachricht 3') && $prompt->contains('[E-MAIL_1]') && ! $prompt->contains('maria@example.org'));

        ticketPage('Cara')
            ->assertDontSeeText('nicht auf Deutsch')
            ->assertSeeTextInOrder(['Übersetzt aus Italienisch · KI-Übersetzung', 'Guten Tag, die Tasse funktioniert nicht.', 'Schreiben Sie mir an maria@example.org, danke.', 'Original anzeigen', 'Buongiorno, la tazza che ho ricevuto non funziona.'])
            ->assertSeeText('Guten Tag, das Problem mit der Tasse tut uns leid.')
            ->assertSeeText('Deutsch zuerst')->assertSeeText('Original zuerst');

        expect(MessageTranslation::query()->count())->toBe(2)
            ->and(DB::table('message_translations')->where('article_id', 1)->value('content'))->not->toContain('Tasse')
            ->and(MessageTranslation::query()->where('article_id', 1)->first())->staff_name->toBe('Nele')->prompt_version->toStartWith('translation-')->model->not->toBeNull();
    });

    test('a message the model calls german is remembered and not offered again', function () {
        TranslationAgent::fake([translationAnswer([
            ['id' => '1', 'translation' => 'Guten Tag.'],
            ['id' => '2', 'language' => 'Deutsch', 'is_german' => true],
        ])]);

        translateTicket();

        ticketPage()->assertDontSeeText('nicht auf Deutsch')->assertSeeText('Buongiorno, ci dispiace per il problema con la tazza.');
        expect(MessageTranslation::query()->where('article_id', 2)->first())->status->toBe('german')->content->toBeNull();
    });

    test('a new foreign message later is the only one offered and sent', function () {
        TranslationAgent::fake([
            translationAnswer([['id' => '1', 'translation' => 'Eins'], ['id' => '2', 'translation' => 'Zwei']]),
            translationAnswer([['id' => '4', 'translation' => 'Vier']]),
        ]);
        translateTicket();
        $this->articles[] = zammadArticle(['id' => 4, 'body' => '<p>Buongiorno, non ho ancora ricevuto una risposta per la tazza, grazie.</p>', 'created_at' => '2026-10-04T08:00:00.000Z']);

        ticketPage()->assertSeeText('Eine Nachricht ist nicht auf Deutsch.');
        translateTicket();

        expect(MessageTranslation::query()->count())->toBe(3);
    });

    test('a changed message makes its translation outdated', function () {
        TranslationAgent::fake([translationAnswer([['id' => '1', 'translation' => 'ERSTE-FASSUNG'], ['id' => '2', 'translation' => 'Zwei']])]);
        translateTicket();
        $this->articles[0] = zammadArticle(['id' => 1, 'body' => '<p>Buongiorno, la tazza che ho ricevuto è rotta, grazie per una risposta.</p>']);

        ticketPage()->assertSeeText('Eine Nachricht ist nicht auf Deutsch.')->assertDontSeeText('ERSTE-FASSUNG');
    });

    test('translating needs a chosen name', function () {
        TranslationAgent::fake();

        $this->post(route('tickets.translation.store', ['number' => '2137942']))->assertRedirect();

        TranslationAgent::assertNeverPrompted();
    });
});

describe('bug fixes', function () {
    test('message ids as zammad assigns them reach the model unchanged and are matched', function () {
        $this->articles = [
            zammadArticle(['id' => 82186, 'body' => '<p>Hello, your order has been shipped. Kind regards, we have sent it with DHL.</p>', 'sender' => 'Agent', 'from' => 'Kundenservice']),
            zammadArticle(['id' => 82577, 'body' => '<p>Buongiorno, la tazza che ho ricevuto non funziona, grazie.</p>', 'created_at' => '2026-10-02T08:00:00.000Z']),
            zammadArticle(['id' => 82584, 'body' => '<p>Grazie, vorrei una sostituzione per la tazza che non funziona.</p>', 'created_at' => '2026-10-03T08:00:00.000Z']),
        ];
        TranslationAgent::fake([translationAnswer([
            ['id' => '82186', 'language' => 'Englisch', 'translation' => 'Hallo, Ihre Bestellung wurde versendet.'],
            ['id' => '82577', 'translation' => 'Guten Tag, die Tasse funktioniert nicht.'],
            ['id' => '82584', 'translation' => 'Danke, ich möchte Ersatz.'],
        ])]);

        translateTicket()->assertSessionHas('translation_done', 3);

        TranslationAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('### Nachricht 82186') && $prompt->contains('### Nachricht 82577') && $prompt->contains('### Nachricht 82584') && ! $prompt->contains('### Nachricht [ADRESSE'));
        expect(MessageTranslation::query()->count())->toBe(3);
    });
});

describe('errors', function () {
    test('a failing model shows a message and keeps what is stored', function () {
        MessageTranslation::factory()->create(['ticket_number' => '2137942', 'article_id' => 99]);
        TranslationAgent::fake(fn () => throw new ConnectionException('down'));

        translateTicket()->assertSessionHas('translation_error', 'Die Übersetzung ist gerade nicht möglich. Bitte erneut versuchen.');

        expect(MessageTranslation::query()->count())->toBe(1);
        ticketPage()->assertSeeText('2 Nachrichten sind nicht auf Deutsch.');
    });

    test('messages the answer leaves out stay open, the others are stored', function () {
        TranslationAgent::fake([translationAnswer([['id' => '1', 'translation' => 'Eins']])]);

        translateTicket()->assertSessionHas('translation_error', 'Eine Nachricht konnte nicht übersetzt werden. Bitte erneut versuchen.');

        expect(MessageTranslation::query()->count())->toBe(1);
        ticketPage()->assertSeeText('Eine Nachricht ist nicht auf Deutsch.');
    });

    test('a long thread is translated in parts; a failing part does not lose the others', function () {
        config(['analysis.translation.batch_characters' => 100]);
        $calls = 0;
        TranslationAgent::fake(function () use (&$calls) {
            $calls++;

            return $calls === 1 ? translationAnswer([['id' => '1', 'translation' => 'Eins']]) : throw new ConnectionException('down');
        });

        translateTicket()->assertSessionHas('translation_error');

        expect($calls)->toBe(2)->and(MessageTranslation::query()->pluck('article_id')->all())->toBe([1]);
    });

    test('the log names purpose and ticket but no content', function () {
        Log::spy();
        TranslationAgent::fake([translationAnswer([['id' => '1', 'translation' => 'Geheim übersetzt'], ['id' => '2', 'translation' => 'Zwei']])]);

        translateTicket();

        Log::shouldHaveReceived('info')->withArgs(fn (string $message, array $context): bool => $message === 'Language model call' && $context['purpose'] === 'translation' && ! str_contains(json_encode($context), 'Geheim'));
    });
});

describe('re-translating', function () {
    test('an admin can have one message translated anew; others cannot', function () {
        TranslationAgent::fake([
            translationAnswer([['id' => '1', 'translation' => 'Alt'], ['id' => '2', 'translation' => 'Zwei']]),
            translationAnswer([['id' => '1', 'translation' => 'Neu und besser']]),
        ]);
        translateTicket();

        translateTicket(['nachricht' => 1])->assertForbidden();
        ticketPage('Nele')->assertDontSeeText('Neu übersetzen');
        ticketPage('Etienne')->assertSeeText('Neu übersetzen');

        translateTicket(['nachricht' => 1], 'Etienne')->assertRedirect();

        expect(MessageTranslation::query()->where('article_id', 1)->first()->content['text'])->toBe('Neu und besser')
            ->and(MessageTranslation::query()->count())->toBe(2);
    });
});

describe('checking the reply draft in german', function () {
    function foreignAnalysis(string $language = 'Italienisch'): string
    {
        CaseAgent::fake([[
            'summary' => ['incident' => 'Tasse', 'customer_wish' => 'Ersatz'], 'category' => 'complaint', 'case_pattern' => null, 'assessment' => 'unklar',
            'recommendation' => 'Foto anfordern.', 'actions' => [], 'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
            'reasoning' => 'x', 'missing_information' => [], 'knowledge_gaps' => [], 'knowledge_ids' => [], 'confidence' => ['level' => 'MITTEL', 'reasons' => []],
            'internal_todos' => [], 'reply' => ['language' => $language, 'text' => $language === 'Deutsch' ? 'Guten Tag, bitte senden Sie ein Foto.' : 'Buongiorno, ci invii una foto per favore.'],
        ]]);
        $location = test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kundengruppe' => 'unclear', 'variante' => 'verlauf'])->headers->get('Location');
        parse_str((string) parse_url((string) $location, PHP_URL_QUERY), $query);

        return $query['analyse'];
    }

    function checkReply(string $id, string $text): TestResponse
    {
        return test()->withCredentials()->withCookie('staff_name', 'Nele')->postJson(route('tickets.analysis.back-translation', ['number' => '2137942', 'analysis' => $id]), ['text' => $text]);
    }

    test('a foreign draft offers the check, a german one does not', function () {
        foreignAnalysis();
        ticketPage()->assertSeeText('Auf Deutsch gegenlesen');

        Analysis::query()->delete();
        foreignAnalysis('Deutsch');
        ticketPage()->assertDontSeeText('Auf Deutsch gegenlesen');
    });

    test('the back translation is stored with the analysis and reused for the same text', function () {
        $id = foreignAnalysis();
        TranslationAgent::fake([translationAnswer([['id' => 'entwurf', 'translation' => 'Guten Tag, bitte senden Sie uns ein Foto.']])]);

        checkReply($id, 'Buongiorno, ci invii una foto per favore.')->assertOk()->assertJson(['text' => 'Guten Tag, bitte senden Sie uns ein Foto.']);
        checkReply($id, 'Buongiorno, ci invii una foto per favore.')->assertOk()->assertJson(['text' => 'Guten Tag, bitte senden Sie uns ein Foto.']);

        TranslationAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('### Nachricht entwurf'));
        expect(TranslationAgent::isFaked())->toBeTrue();

        ticketPage()->assertSeeTextInOrder(['Rückübersetzung zur Kontrolle – verschickt wird das Original', 'Guten Tag, bitte senden Sie uns ein Foto.']);
        expect(DB::table('analyses')->where('uuid', $id)->value('content'))->not->toContain('senden Sie uns');
    });

    test('after the draft changed the stored back translation is marked as outdated', function () {
        $id = foreignAnalysis();
        TranslationAgent::fake([translationAnswer([['id' => 'entwurf', 'translation' => 'Alt']])]);
        checkReply($id, 'Buongiorno, ci invii una foto per favore.');

        test()->withCredentials()->withCookie('staff_name', 'Nele')->putJson(route('tickets.analysis.reply', ['number' => '2137942', 'analysis' => $id]), ['text' => 'Buongiorno, grazie mille.']);

        expect(ticketPage()->getContent())->toMatch('/<p x-show="backFor !== text"\s+class="[^"]*">Entwurf seither geändert – erneut gegenlesen\.<\/p>/');
    });

    test('checking needs a name, a known analysis of the ticket and a text', function () {
        $id = foreignAnalysis();
        TranslationAgent::fake();

        checkReply((string) Str::uuid(), 'x')->assertNotFound();
        test()->withCredentials()->withCookie('staff_name', 'Nele')->postJson(route('tickets.analysis.back-translation', ['number' => '1111111', 'analysis' => $id]), ['text' => 'x'])->assertNotFound();
        checkReply($id, '')->assertUnprocessable();
        $this->defaultCookies = [];
        $this->withCredentials()->postJson(route('tickets.analysis.back-translation', ['number' => '2137942', 'analysis' => $id]), ['text' => 'x'])->assertStatus(409);

        TranslationAgent::assertNeverPrompted();
    });

    test('a failing model answers with a clear message', function () {
        $id = foreignAnalysis();
        TranslationAgent::fake(fn () => throw new ConnectionException('down'));

        checkReply($id, 'Buongiorno')->assertStatus(503)->assertJson(['message' => 'Die Übersetzung ist gerade nicht möglich. Bitte erneut versuchen.']);
    });
});

describe('retention and deletion', function () {
    test('translations are deleted after 12 months and with the analyses of a ticket', function () {
        MessageTranslation::factory()->create(['ticket_number' => '2137942', 'article_id' => 1]);
        MessageTranslation::factory()->create(['ticket_number' => '9999999', 'article_id' => 2]);

        $this->withCookie('staff_name', 'Etienne')->delete(route('tickets.analyses.destroy', ['number' => '2137942']));
        expect(MessageTranslation::query()->pluck('ticket_number')->all())->toBe(['9999999']);

        $this->travel(13)->months();
        $this->artisan('analysis:purge')->expectsOutputToContain('Übersetzungen gelöscht: 1');
        expect(MessageTranslation::query()->count())->toBe(0);
    });
});

describe('analysis', function () {
    test('the analysis keeps using the original, not the translation', function () {
        MessageTranslation::factory()->create(['ticket_number' => '2137942', 'article_id' => 1, 'fingerprint' => hash('sha256', 'Buongiorno, la tazza che ho ricevuto non funziona. Scrivetemi a maria@example.org, grazie.'), 'content' => ['text' => 'UEBERSETZT']]);
        CaseAgent::fake([[
            'summary' => ['incident' => 'x', 'customer_wish' => 'y'], 'category' => 'complaint', 'case_pattern' => null, 'assessment' => null, 'recommendation' => 'z', 'actions' => [],
            'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null], 'reasoning' => 'r', 'missing_information' => [], 'knowledge_gaps' => [],
            'knowledge_ids' => [], 'confidence' => ['level' => 'MITTEL', 'reasons' => []], 'internal_todos' => [], 'reply' => ['language' => 'Italienisch', 'text' => 'Buongiorno'],
        ]]);

        test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kundengruppe' => 'unclear', 'variante' => 'verlauf']);

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('la tazza che ho ricevuto') && ! $prompt->contains('UEBERSETZT'));
        ticketPage()->assertSeeText('UEBERSETZT');
    });
});

describe('about page', function () {
    test('the translation is explained', function () {
        $this->get(route('about'))->assertSeeText('übersetzt ein Klick auf „Übersetzen“ den Verlauf')->assertSeeText('Auf Deutsch gegenlesen');
    });
});
