<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\Agents\SummaryAgent;
use App\Analysis\AnalysisStore;
use App\Analysis\Pseudonymizer;
use App\Models\Analysis;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
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
        'cache.default' => 'array',
    ]);

    knowledgeBase([
        'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel']),
        'policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002', 'title' => 'Nur Amazon', 'sales_channels' => ['amazon'], 'status' => 'active']),
        'procedures/procedure-001-a.md' => knowledgeDoc(['id' => 'PROCEDURE-001', 'type' => 'procedure', 'actions' => ['return']], "Gilt für alle.\n\n# Voraussetzungen\n\n- x\n\n# Arbeitsschritte\n\n1. x\n\n# Abschlusskontrolle\n\n- [ ] x"),
        'products/magic-mug.md' => knowledgeDoc(['id' => 'PRODUCT-001', 'type' => 'product', 'title' => 'Zaubertasse', 'order_keywords' => ['11281']]),
    ]);
});

afterEach(fn () => cleanUpKnowledgeBases());

/**
 * @return array<string, mixed>
 */
function caseAnswer(array $overrides = []): array
{
    return array_replace([
        'summary' => ['incident' => 'Die Tasse bleibt schwarz.', 'customer_wish' => 'Ersatz'],
        'category' => 'complaint',
        'case_pattern' => 'Thermoeffekt angeblich defekt',
        'assessment' => 'unklar',
        'recommendation' => 'Foto mit heißem Wasser anfordern.',
        'actions' => ['photo-request'],
        'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'Ohne Test kein Urteil.',
        'missing_information' => [['what' => 'Foto der warmen Tasse', 'from' => 'Kunde', 'question' => 'Bitte senden Sie ein Foto.']],
        'knowledge_ids' => ['POLICY-001'],
        'confidence' => ['level' => 'MITTEL', 'reasons' => ['Bestellung gefunden']],
        'internal_todos' => ['Antwort abwarten'],
        'reply' => ['language' => 'Deutsch', 'text' => "Hallo Erika,\nist diese Adresse korrekt: [LIEFERADRESSE]?\nViele Grüße"],
    ], $overrides);
}

/**
 * @param  list<array<string, mixed>>  $articles
 */
function analysisTicket(array $articles = [], array $orders = []): void
{
    fakeZammad($articles === [] ? [
        zammadArticle(['id' => 1, 'body' => '<p>Meine Tasse bleibt schwarz. Erreichbar unter erika@example.org oder 0171 2345678.</p>']),
    ] : $articles);

    Http::fake([
        'eocs.test/api/v1/orders*' => function (Request $request) use ($orders) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            [$number] = explode(',', (string) ($query['filter']['external_order_id'] ?? ''));

            return Http::response(['data' => array_values(array_filter($orders, fn (array $order): bool => $order['external_order_id'] === $number))]);
        },
    ]);
}

/**
 * @return array<string, mixed>
 */
function analysisOrder(): array
{
    return [
        'id' => 700001, 'external_order_id' => '402-0000000-0000001', 'order_date' => '2026-09-09T13:06:53.000000Z',
        'status_name' => 'Vollständig und verschickt', 'state' => ['color' => 'green'],
        'client' => ['name' => 'Amazon.it', 'sales_platform' => ['name' => 'Amazon']],
        'customer_email' => 'geheim@example.org', 'billing' => ['total' => 24.9, 'payment_method' => 'amazon'],
        'shipping' => ['first_name' => 'Erika', 'last_name' => 'Beispiel', 'street' => 'Musterweg 7', 'zipcode' => '12345', 'city' => 'Musterstadt', 'country_code' => 'DE'],
        'shipments' => ['data' => []],
        'order_items' => ['data' => [['quantity' => 1, 'item' => ['item_id' => 11281, 'name' => 'Fototasse Schwarz'], 'data' => []]]],
    ];
}

function analyze(array $fields = [], string $staff = 'Nele'): TestResponse
{
    return test()->withCookie('staff_name', $staff)->post(route('tickets.analysis.run', ['number' => '2137942']), array_merge([
        'kundengruppe' => 'private-amazon', 'variante' => 'verlauf', 'kontext' => '',
    ], $fields));
}

describe('analysis form', function () {
    test('the ticket page has the analysis form with group, products, variants and context field', function () {
        analysisTicket();

        $this->get('/tickets/2137942')
            ->assertSeeTextInOrder(['Analyse', 'Kundengruppe', 'Produkt(e)', 'Zaubertasse', 'Zusätzliche Informationen / eigene Einschätzung', 'Analysieren'])
            ->assertSee('action="'.route('tickets.analysis.run', ['number' => '2137942']).'"', false);
    });

    test('group and products are suggested from the loaded eocs order, manual order data is hidden', function () {
        analysisTicket(orders: [analysisOrder()]);

        $html = $this->get(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000001']]))->getContent();

        expect($html)->toMatch('/<option value="private-amazon"\s+selected/')
            ->toMatch('/value="magic-mug"\s+checked/')
            ->not->toContain('Bestelldaten von Hand');
    });

    test('without an order the group is unclear and manual order fields are offered', function () {
        analysisTicket();

        $html = $this->get('/tickets/2137942')->assertSeeText('Bestelldaten von Hand')->getContent();

        expect($html)->toMatch('/<option value="unclear"\s+selected/');
    });

    test('a single message offers no variants', function () {
        analysisTicket();

        $this->get('/tickets/2137942')->assertDontSeeText('Was soll an die KI gehen?');
    });

    test('short threads default to the full thread, long ones to the summary', function (int $messages, int $length, string $expected) {
        $articles = [];
        for ($i = 1; $i <= $messages; $i++) {
            $articles[] = zammadArticle(['id' => $i, 'body' => '<p>'.str_repeat('x', $length).'</p>', 'created_at' => sprintf('2026-10-%02dT08:00:00.000Z', $i)]);
        }
        analysisTicket($articles);

        expect($this->get('/tickets/2137942')->getContent())->toMatch('/'.preg_quote($expected, '/').'\s*<span class="text-xs text-slate-600">\(empfohlen\)/');
    })->with([
        'four earlier short messages' => [5, 100, 'Ganzer Verlauf'],
        'five earlier messages' => [6, 100, 'Letzte Kundennachricht + Zusammenfassung'],
        'three long earlier messages' => [4, 2100, 'Letzte Kundennachricht + Zusammenfassung'],
        'two long earlier messages' => [3, 4000, 'Ganzer Verlauf'],
    ]);

    test('the preview shows the ticket part with contact data replaced', function () {
        analysisTicket();

        $this->get('/tickets/2137942')
            ->assertSee('Was an die KI geht')
            ->assertSee('Erreichbar unter [E-MAIL_1] oder [TELEFON_1]', false);
    });
});

describe('contact data', function () {
    test('addresses, e-mails and phone numbers in several languages are replaced', function (string $text, string $expected) {
        expect(app(Pseudonymizer::class)->apply($text))->toBe($expected);
    })->with([
        'german street and town' => ['Bitte an Musterstraße 12, 32423 Minden.', 'Bitte an [ADRESSE_1], [ADRESSE_2].'],
        'dutch street and postcode' => ['Adres: Zonnedauwlaan 8, 1433WB Kudelstaart', 'Adres: [ADRESSE_1], [ADRESSE_2]'],
        'dutch postcode with space' => ['1433 WB Kudelstaart', '[ADRESSE_1]'],
        'french street' => ['Livraison au 12 rue de la Paix, 75002 Paris', 'Livraison au [ADRESSE_1], [ADRESSE_2]'],
        'italian street' => ['Spedire a Via Roma 5, 00184 Roma', 'Spedire a [ADRESSE_1], [ADRESSE_2]'],
        'phone at sentence end' => ['Ruf an: 0171 2345678.', 'Ruf an: [TELEFON_1].'],
        'international phone' => ['Tel +31 6 26176737', 'Tel [TELEFON_1]'],
        'e-mail' => ['Mail: erika@example.org!', 'Mail: [E-MAIL_1]!'],
        'same value same placeholder' => ['a@b.de und a@b.de', '[E-MAIL_1] und [E-MAIL_1]'],
    ]);

    test('order numbers, tracking numbers, dates and amounts stay', function (string $text) {
        expect(app(Pseudonymizer::class)->apply($text))->toBe($text);
    })->with([
        'amazon order' => ['Bestellung 402-4907715-1581912'],
        'tracking number' => ['Sendung 00340434171079990018'],
        'date' => ['am 05.10.2026 bestellt'],
        'looxis pro order' => ['Auftrag 30019578'],
        'amount' => ['Preis 49,90 Euro'],
        'article number with product name' => ['Artikel 11282 Zaubertasse bestellt'],
        'article number after label' => ['Art.-Nr. 11281 Fototasse'],
        'quantity with unit' => ['Menge 12345 Stück'],
        'number with unit' => ['20 Stück, 11282 Tassen bestellt'],
        'heading with message id' => ["### Nachricht 82186\nHello, your order"],
        'number at line end, capital word on next line' => ["Bestellnummer 30012\nMinden ist schön"],
    ]);

    test('placeholders are put back for display, unknown ones are marked', function () {
        $pseudonymizer = app(Pseudonymizer::class);
        $masked = $pseudonymizer->apply('Mail erika@example.org');

        expect($masked)->toBe('Mail [E-MAIL_1]')
            ->and($pseudonymizer->restorePlain('An [E-MAIL_1]'))->toBe('An erika@example.org')
            ->and((string) $pseudonymizer->restore('An [E-MAIL_1] und [FOO]'))->toBe('An <mark class="placeholder-filled" title="Von der App eingesetzt">erika@example.org</mark> und <mark class="placeholder-missing" title="Bitte ausfüllen">[FOO]</mark>');
    });
});

describe('running an analysis', function () {
    test('without a chosen name nothing is analysed and the input is kept', function () {
        CaseAgent::fake();
        analysisTicket();

        $this->from('/tickets/2137942')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kundengruppe' => 'unclear', 'variante' => 'verlauf', 'kontext' => 'Foto geprüft'])
            ->assertRedirect('/tickets/2137942')
            ->assertSessionHasInput('kontext', 'Foto geprüft');

        CaseAgent::assertNeverPrompted();
    });

    test('an analysis sends context, order data and knowledge without contact data and shows the result', function () {
        CaseAgent::fake([caseAnswer()]);
        analysisTicket(orders: [analysisOrder()]);

        $response = analyze(['bestellungen' => ['402-0000000-0000001'], 'produkte' => ['magic-mug'], 'kontext' => 'Foto geprüft, kein Fehler. Kundin: erika@example.org']);
        $response->assertRedirect();
        expect($response->headers->get('Location'))->toContain('analyse=')->toContain('#ergebnis');

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Foto geprüft, kein Fehler')
            && $prompt->contains('Kundengruppe: Privatkunde, Amazon')
            && $prompt->contains('Bestellung 402-0000000-0000001')
            && $prompt->contains('Fototasse Schwarz (Art.-Nr. 11281)')
            && $prompt->contains('POLICY-001 – Allgemeine Regel (Policies, Entwurf)')
            && $prompt->contains('POLICY-002 – Nur Amazon (Policies)')
            && $prompt->contains('[LIEFERADRESSE]')
            && ! $prompt->contains('PROCEDURE-001')
            && ! $prompt->contains('erika@example.org')
            && ! $prompt->contains('0171 2345678')
            && ! $prompt->contains('Musterweg 7')
            && ! $prompt->contains('geheim@example.org'));

        $this->withCookie('staff_name', 'Nele')->get($response->headers->get('Location'))
            ->assertSeeTextInOrder([
                'Ergebnis der Analyse', 'Analyse von Nele', 'Unklar', 'Confidence MITTEL',
                'Was ist zu tun?', 'Foto mit heißem Wasser anfordern.', 'Foto anfordern',
                'Du darfst das selbst entscheiden.',
                'Foto der warmen Tasse', 'Rückfrage:', 'Bitte senden Sie ein Foto.',
                'Beruht teilweise auf Entwurfs-Wissen', 'POLICY-001',
                'Antwortentwurf', 'Von der App eingesetzt: Lieferadresse', 'Hallo Erika,',
                'Begründung', 'Ohne Test kein Urteil.',
                'Quellen', 'POLICY-001', 'Entwurf',
                'Kurzfassung', 'Die Tasse bleibt schwarz.', 'Ersatz', 'complaint', 'Thermoeffekt angeblich defekt',
                'Bestellung gefunden', 'Antwort abwarten',
                'Nele', 'gpt-5.5', 'Prompt analysis-',
            ])
            ->assertSee("ist diese Adresse korrekt: Erika Beispiel\nMusterweg 7\n12345 Musterstadt\nDE?", false);
    });

    test('the employee choice of group and products decides the knowledge', function () {
        CaseAgent::fake([caseAnswer(['knowledge_ids' => []])]);
        analysisTicket(orders: [analysisOrder()]);

        analyze(['kundengruppe' => 'unclear', 'bestellungen' => ['402-0000000-0000001']]);

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Kundengruppe: Noch unklar') && ! $prompt->contains('POLICY-002'));
    });

    test('manual order data goes to the analysis when no eocs order is loaded', function () {
        CaseAgent::fake([caseAnswer(['knowledge_ids' => []])]);
        analysisTicket();

        analyze(['bestellung_nummer' => '7JI-0WC1-6M49', 'bestellung_personalisierung' => 'Text: Für Oma']);

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Bestelldaten vom Mitarbeiter eingetragen')
            && $prompt->contains('Bestellnummer: 7JI-0WC1-6M49') && $prompt->contains('Personalisierung: Text: Für Oma'));
    });

    test('unknown knowledge ids, actions and category are dropped and reported', function () {
        CaseAgent::fake([caseAnswer(['knowledge_ids' => ['POLICY-001', 'POLICY-999'], 'actions' => ['photo-request', 'teleport'], 'category' => 'quatsch'])]);
        analysisTicket();

        $location = analyze()->headers->get('Location');

        $this->get($location)
            ->assertSeeText('Unbekannte Quelle „POLICY-999“ entfernt.')
            ->assertSeeText('Unbekannter Vorgang „teleport“ verworfen.')
            ->assertSeeText('Unbekannte Kategorie „quatsch“ verworfen.');
    });

    test('an unknown placeholder stays and is marked for filling in', function () {
        CaseAgent::fake([caseAnswer(['reply' => ['language' => 'Deutsch', 'text' => 'Ihre Nummer [KUNDENNUMMER] ist notiert.']])]);
        analysisTicket();

        $this->get(analyze()->headers->get('Location'))
            ->assertSee('Ihre Nummer [KUNDENNUMMER] ist notiert.', false)
            ->assertSee('noch ausfüllen (in eckigen Klammern)', false);
    });

    test('an unusable answer is tried once more', function () {
        CaseAgent::fake([['reply' => 'kaputt'], caseAnswer()]);
        analysisTicket();

        $this->get(analyze()->headers->get('Location'))->assertSeeText('Ergebnis der Analyse');

        CaseAgent::assertPromptedTimes(2);
    });

    test('two unusable answers show a message and keep the input', function () {
        CaseAgent::fake([['reply' => 'kaputt'], ['reply' => 'kaputt']]);
        analysisTicket();

        analyze(['kontext' => 'Meine Notiz'])
            ->assertSessionHas('analysis_error', 'Das Sprachmodell hat kein verwertbares Ergebnis geliefert. Bitte erneut versuchen.')
            ->assertSessionHasInput('kontext', 'Meine Notiz');
    });

    test('an unreachable provider shows a message and keeps the input', function () {
        CaseAgent::fake([fn () => throw new ConnectionException('timeout')]);
        analysisTicket();

        analyze(['kontext' => 'Meine Notiz'])
            ->assertSessionHas('analysis_error', 'Die Analyse ist gerade nicht möglich. Bitte erneut versuchen.')
            ->assertSessionHasInput('kontext', 'Meine Notiz');
    });

    test('a missing api key is reported without asking the provider', function () {
        config(['ai.providers.openai.key' => null]);
        CaseAgent::fake();
        analysisTicket();

        analyze()->assertSessionHas('analysis_error', 'Die Verbindung zum Sprachmodell ist nicht eingerichtet oder ungültig. Bitte wende dich an den Entwickler.');

        CaseAgent::assertNeverPrompted();
    });

    test('invalid input is rejected', function (array $fields) {
        CaseAgent::fake();
        analysisTicket();

        analyze($fields)->assertSessionHasErrors();

        CaseAgent::assertNeverPrompted();
    })->with([
        'unknown group' => [['kundengruppe' => 'vip']],
        'unknown product' => [['produkte' => ['rakete']]],
        'unknown variant' => [['variante' => 'alles']],
        'context too long' => [['kontext' => str_repeat('a', 4001)]],
    ]);

    test('the log names purpose, model, duration and tokens but no content', function () {
        CaseAgent::fake([caseAnswer()]);
        analysisTicket();
        Log::spy();

        analyze(['kontext' => 'Geheime Notiz']);

        Log::shouldHaveReceived('info')->withArgs(fn (string $message, array $context): bool => $message === 'Language model call'
            && $context['purpose'] === 'analysis' && $context['outcome'] === 'ok' && ! str_contains(json_encode($context), 'Geheime'));
    });

    test('the stored result is encrypted', function () {
        CaseAgent::fake([caseAnswer()]);
        analysisTicket();

        parse_str((string) parse_url(analyze()->headers->get('Location'), PHP_URL_QUERY), $query);

        expect(DB::table('analyses')->where('uuid', $query['analyse'])->value('content'))->toBeString()->not->toContain('Die Tasse bleibt schwarz');
    });

    test('a result of another ticket is not shown', function () {
        CaseAgent::fake([caseAnswer()]);
        analysisTicket();
        parse_str((string) parse_url(analyze()->headers->get('Location'), PHP_URL_QUERY), $query);

        fakeZammad([zammadArticle()], ['number' => '2137943']);

        $this->get(route('tickets.show', ['number' => '2137943', 'analyse' => $query['analyse']]))->assertDontSeeText('Ergebnis der Analyse');
    });
});

describe('summary', function () {
    function longThread(): array
    {
        $articles = [];
        for ($i = 1; $i <= 6; $i++) {
            $articles[] = zammadArticle(['id' => $i, 'sender' => $i % 2 ? 'Customer' : 'Agent', 'from' => $i % 2 ? 'Erika' : 'Nele', 'body' => "<p>Nachricht {$i}, Telefon 0171 2345678</p>", 'created_at' => sprintf('2026-10-%02dT08:00:00.000Z', $i)]);
        }

        return $articles;
    }

    function summaryAnswer(): array
    {
        return ['facts' => 'Tasse defekt, Kontakt [TELEFON_1]', 'customer_wish' => 'Ersatz', 'measures' => 'Foto angefordert', 'decisions' => '', 'corrections' => '', 'open_questions' => 'Foto fehlt'];
    }

    test('creating a summary sends the earlier thread with contact data replaced and shows it above the last customer message', function () {
        SummaryAgent::fake([summaryAnswer()]);
        analysisTicket(longThread());

        $this->withCookie('staff_name', 'Nele')->post(route('tickets.summary.create', ['number' => '2137942']))->assertRedirect();

        SummaryAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Nachricht 4') && ! $prompt->contains('Nachricht 5,') && ! $prompt->contains('0171 2345678'));

        $html = $this->get('/tickets/2137942')
            ->assertSeeTextInOrder(['Nachricht 4', 'Zusammenfassung des bisherigen Verlaufs', 'zusammengefasst bis Nachricht vom', 'Sachverhalt', 'Tasse defekt, Kontakt 0171 2345678', 'Nachricht 5'])
            ->getContent();

        expect($html)->not->toContain('veraltet');
    });

    test('an analysis with the summary variant reuses the summary without another call', function () {
        SummaryAgent::fake([summaryAnswer()]);
        CaseAgent::fake([caseAnswer()]);
        analysisTicket(longThread());

        $this->withCookie('staff_name', 'Nele')->post(route('tickets.summary.create', ['number' => '2137942']));
        analyze(['variante' => 'zusammenfassung']);

        SummaryAgent::assertPromptedTimes(1);
        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Zusammenfassung des bisherigen Verlaufs')
            && $prompt->contains('Letzte Kundennachricht') && $prompt->contains('Nachricht 5') && ! $prompt->contains('Nachricht 1,'));
    });

    test('without a summary the summary variant creates it first', function () {
        SummaryAgent::fake([summaryAnswer()]);
        CaseAgent::fake([caseAnswer()]);
        analysisTicket(longThread());

        analyze(['variante' => 'zusammenfassung'])->assertRedirect();

        SummaryAgent::assertPromptedTimes(1);
        CaseAgent::assertPromptedTimes(1);
    });

    test('an edited summary is marked and used as edited', function () {
        SummaryAgent::fake([summaryAnswer()]);
        CaseAgent::fake([caseAnswer()]);
        analysisTicket(longThread());
        $this->withCookie('staff_name', 'Nele')->post(route('tickets.summary.create', ['number' => '2137942']));

        $this->withCookie('staff_name', 'Nele')->put(route('tickets.summary.update', ['number' => '2137942']), ['zusammenfassung' => "## Sachverhalt\nVon mir korrigiert"])->assertRedirect();

        $this->get('/tickets/2137942')->assertSeeText('von Hand geändert')->assertSeeText('Von mir korrigiert');

        analyze(['variante' => 'zusammenfassung']);
        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Von mir korrigiert'));
    });

    test('new messages make the summary outdated; an edited one is not overwritten silently', function () {
        SummaryAgent::fake([summaryAnswer(), summaryAnswer()]);
        CaseAgent::fake([caseAnswer()]);
        $thread = longThread();
        Http::fake([
            'zammad.test/api/v1/tickets/search*' => Http::response([['id' => 51234, 'number' => '2137942', 'title' => 'Tasse', 'state' => 'open', 'created_at' => '2026-10-01T07:12:00.000Z']]),
            'zammad.test/api/v1/ticket_articles/by_ticket/*' => function () use (&$thread) {
                return Http::response($thread);
            },
        ]);
        $this->withCookie('staff_name', 'Nele')->post(route('tickets.summary.create', ['number' => '2137942']));
        $this->withCookie('staff_name', 'Nele')->put(route('tickets.summary.update', ['number' => '2137942']), ['zusammenfassung' => 'Meine Fassung']);

        $thread = [...longThread(), zammadArticle(['id' => 7, 'sender' => 'Agent', 'from' => 'Nele', 'body' => '<p>Neue Nachricht</p>', 'created_at' => '2026-10-07T08:00:00.000Z'])];

        $this->get('/tickets/2137942')->assertSeeText('veraltet – neue Nachrichten')->assertSeeText('Meine Fassung weiter verwenden');

        analyze(['variante' => 'zusammenfassung'])->assertSessionHas('analysis_error');
        CaseAgent::assertNeverPrompted();
    });

    test('the summary is stored encrypted and lasts beyond seven days', function () {
        SummaryAgent::fake([summaryAnswer()]);
        analysisTicket(longThread());

        $this->withCookie('staff_name', 'Nele')->post(route('tickets.summary.create', ['number' => '2137942']));

        expect(DB::table('ticket_summaries')->where('scope_key', '2137942')->value('content'))->toBeString()->not->toContain('Tasse defekt')
            ->and(app(AnalysisStore::class)->summary('2137942')->text)->toContain('Tasse defekt');

        $this->travel(8)->days();

        expect(app(AnalysisStore::class)->summary('2137942')?->text)->toContain('Tasse defekt');
    });

    test('creating a summary needs a chosen name', function () {
        SummaryAgent::fake();
        analysisTicket(longThread());

        $this->from('/tickets/2137942')->post(route('tickets.summary.create', ['number' => '2137942']))->assertRedirect('/tickets/2137942');

        SummaryAgent::assertNeverPrompted();
    });
});

describe('remembered choice', function () {
    test('group and products chosen for a ticket are preselected later with who chose them', function () {
        CaseAgent::fake([caseAnswer()]);
        analysisTicket();
        $this->travelTo(now()->setTimezone('Europe/Berlin')->setDate(2026, 10, 6)->setTime(14, 5));

        analyze(['kundengruppe' => 'reseller', 'produkte' => ['magic-mug']]);

        $this->withCookie('staff_name', 'Cara')->get('/tickets/2137942')->assertSeeText('Vorbelegt: wie in der angezeigten Analyse');

        Analysis::query()->delete();
        $html = $this->withCookie('staff_name', 'Cara')->get('/tickets/2137942')
            ->assertSeeText('Vorbelegt: zuletzt gewählt von Nele am 06.10.2026, 14:05 Uhr')
            ->getContent();

        expect($html)->toMatch('/<option value="reseller"\s+selected/')
            ->toMatch('/value="magic-mug"\s+checked/');
    });

    test('the choice for the ticket wins over the channel of a loaded order', function () {
        CaseAgent::fake([caseAnswer()]);
        analysisTicket(orders: [analysisOrder()]);

        analyze(['kundengruppe' => 'reseller']);

        expect($this->get(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000001']]))->getContent())
            ->toMatch('/<option value="reseller"\s+selected/');
    });

    test('a group chosen for a customer is suggested on their other tickets', function () {
        app(AnalysisStore::class)->putCustomerGroup('customer-77', 'reseller');
        analysisTicket();

        $html = $this->get('/tickets/2137942')->assertSeeText('Vorbelegt: bei früheren Tickets dieses Kunden gewählt')->getContent();

        expect($html)->toMatch('/<option value="reseller"\s+selected/');
    });

    test('an analysis remembers the group for the organization, but never "unclear"', function () {
        CaseAgent::fake([caseAnswer(), caseAnswer()]);
        fakeZammad(ticket: ['organization_id' => 12]);

        analyze(['kundengruppe' => 'looxis-pro']);
        analyze(['kundengruppe' => 'unclear']);

        expect(app(AnalysisStore::class)->customerGroup('organization-12'))->toBe('looxis-pro')
            ->and(app(AnalysisStore::class)->customerGroup('customer-77'))->toBeNull();
    });

    test('a remembered group that no longer exists is ignored', function () {
        app(AnalysisStore::class)->putCustomerGroup('customer-77', 'gibt-es-nicht');
        analysisTicket();

        expect($this->get('/tickets/2137942')->assertDontSeeText('Vorbelegt:')->getContent())->toMatch('/<option value="unclear"\s+selected/');
    });
});

describe('missing knowledge', function () {
    test('a knowledge gap named by the ai is shown prominently with topic and open question', function () {
        CaseAgent::fake([caseAnswer(['knowledge_gaps' => [['topic' => 'Bestellungen zusammenführen', 'question' => 'Können zwei Bestellungen auf eine Rechnung?']]])]);
        analysisTicket();

        $this->get(analyze()->headers->get('Location'))
            ->assertSeeTextInOrder(['Wissenslücke', 'Fehlendes Wissen', 'Bestellungen zusammenführen', 'Offene Frage: Können zwei Bestellungen auf eine Rechnung?', 'Was ist passiert?']);
    });

    test('without a gap, or with an unusable one, nothing is shown', function (mixed $gaps) {
        CaseAgent::fake([caseAnswer(['knowledge_gaps' => $gaps])]);
        analysisTicket();

        $this->get(analyze()->headers->get('Location'))->assertSeeText('Ergebnis der Analyse')->assertDontSeeText('Fehlendes Wissen');
    })->with(['empty' => [[]], 'no topic' => [[['topic' => ' ', 'question' => 'x']]], 'not a list' => ['x']]);

    test('the prompt separates missing case information from missing rules', function () {
        CaseAgent::fake([caseAnswer()]);
        analysisTicket();

        analyze();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => str_contains((string) $prompt->agent->instructions(), '`knowledge_gaps`')
            && str_contains((string) $prompt->agent->instructions(), '`missing_information`'));
    });

    test('the context field explains what belongs into it', function () {
        analysisTicket();

        $this->get('/tickets/2137942')
            ->assertSee('Was du über diesen Fall weißt, das nicht im Ticket steht', false)
            ->assertSeeText('Gilt für die KI als geprüfter Fakt. Allgemeine Regeln');
    });
});

describe('qa additions', function () {
    test('internal notes go to the ai marked as internal, closed tickets can be analysed', function () {
        CaseAgent::fake([caseAnswer()]);
        fakeZammad([
            zammadArticle(['id' => 1, 'body' => '<p>Tasse kaputt.</p>']),
            zammadArticle(['id' => 2, 'sender' => 'Agent', 'internal' => true, 'from' => 'Kundenservice', 'body' => '<p>Produktion fragen.</p>', 'created_at' => '2026-10-01T08:00:00.000Z']),
        ], ['state' => 'closed']);

        analyze()->assertRedirect();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('INTERN, nicht für den Kunden') && $prompt->contains('Produktion fragen.'));
    });

    test('a failing summary shows a hint to choose another variant', function () {
        SummaryAgent::fake(fn () => throw new ConnectionException('down'));
        analysisTicket(longThread());

        $this->withCookie('staff_name', 'Nele')->post(route('tickets.summary.create', ['number' => '2137942']))->assertRedirect();

        $this->get('/tickets/2137942')->assertSeeText('Die Zusammenfassung konnte nicht erstellt werden.')->assertSeeText('Du kannst auch eine andere Variante wählen.');
    });

    test('the stored metadata names staff, time, model, prompt, knowledge state, fingerprints, variant and duration', function () {
        CaseAgent::fake([caseAnswer()]);
        analysisTicket();

        parse_str((string) parse_url(analyze()->headers->get('Location'), PHP_URL_QUERY), $query);
        $meta = app(AnalysisStore::class)->result($query['analyse'])['meta'];

        expect($meta)->toHaveKeys(['staff', 'created_at', 'model', 'prompt_version', 'knowledge_state', 'knowledge_fingerprints', 'variant', 'duration_ms'])
            ->and($meta['staff'])->toBe('Nele')
            ->and($meta['knowledge_fingerprints'])->not->toBeEmpty();
    });

    test('ai output is escaped in the result', function () {
        CaseAgent::fake([caseAnswer(['recommendation' => '<script>alert(1)</script>', 'reply' => ['language' => 'Deutsch', 'text' => '<img src=x onerror=alert(1)>']])]);
        analysisTicket();

        $html = $this->get(analyze()->headers->get('Location'))->getContent();

        expect($html)->not->toContain('<script>alert(1)</script>')->not->toContain('<img src=x')->toContain('&lt;script&gt;');
    });
});

describe('bug fixes', function () {
    test('a retryable error offers "Erneut versuchen", a missing key does not', function () {
        CaseAgent::fake(fn () => throw new ConnectionException('down'));
        analysisTicket();

        analyze();
        $this->get('/tickets/2137942')->assertSeeText('Die Analyse ist gerade nicht möglich.')->assertSee('form="analyse-formular"', false);

        config(['ai.providers.openai.key' => '']);
        analyze();
        $this->get('/tickets/2137942')->assertDontSee('form="analyse-formular"', false);
    });

    test('without matching knowledge the result says so', function () {
        knowledgeBase(['policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002', 'title' => 'Nur Amazon', 'sales_channels' => ['amazon']])]);
        CaseAgent::fake([caseAnswer(['knowledge_ids' => []])]);
        analysisTicket();

        $this->get(analyze(['kundengruppe' => 'reseller'])->headers->get('Location'))->assertSeeText('Kein Wissen für diesen Fall');
    });

    test('with knowledge there is no such hint', function () {
        CaseAgent::fake([caseAnswer()]);
        analysisTicket();

        $this->get(analyze()->headers->get('Location'))->assertDontSeeText('Kein Wissen für diesen Fall');
    });

    test('a full thread above the limit is refused with a pointer to the summary variant', function () {
        config(['analysis.max_input_characters' => 500]);
        CaseAgent::fake();
        analysisTicket([
            zammadArticle(['id' => 1, 'body' => '<p>'.str_repeat('alt ', 200).'</p>']),
            zammadArticle(['id' => 2, 'body' => '<p>Neu</p>', 'created_at' => '2026-10-02T07:12:00.000Z']),
        ]);

        analyze(['variante' => 'verlauf']);

        CaseAgent::assertNeverPrompted();
        $this->get('/tickets/2137942')->assertSeeText('Der Verlauf ist zu lang für die KI.');
    });

    test('for the summary the oldest part of a very long thread is shortened', function () {
        config(['analysis.max_input_characters' => 300]);
        SummaryAgent::fake([summaryAnswer()]);
        analysisTicket([
            zammadArticle(['id' => 1, 'body' => '<p>ANFANG '.str_repeat('alt ', 200).'</p>']),
            zammadArticle(['id' => 2, 'body' => '<p>Mitte ENDE</p>', 'created_at' => '2026-10-02T07:12:00.000Z']),
            zammadArticle(['id' => 3, 'body' => '<p>Neu</p>', 'created_at' => '2026-10-03T07:12:00.000Z']),
        ]);

        $this->withCookie('staff_name', 'Nele')->post(route('tickets.summary.create', ['number' => '2137942']));

        SummaryAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('ENDE') && ! $prompt->contains('ANFANG') && $prompt->contains('gekürzt'));
    });

    test('language model calls are limited per name and minute', function () {
        config(['analysis.calls_per_minute' => 2]);
        CaseAgent::fake([caseAnswer(), caseAnswer(), caseAnswer()]);
        analysisTicket();

        analyze();
        analyze();
        test()->from('/tickets/2137942')->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kundengruppe' => 'unclear', 'variante' => 'verlauf'])
            ->assertRedirect('/tickets/2137942')
            ->assertSessionHas('analysis_error', 'Zu viele KI-Aufrufe in kurzer Zeit. Bitte eine Minute warten und dann erneut versuchen.');

        CaseAgent::assertPrompted(fn () => true);
    });
});

describe('order numbers known from the ticket', function () {
    test('an order number found in the ticket is loaded from eocs for the analysis without being asked for', function () {
        CaseAgent::fake([caseAnswer()]);
        analysisTicket([zammadArticle(['id' => 1, 'body' => '<p>Meine Bestellung 402-0000000-0000001 ist defekt.</p>'])], [analysisOrder()]);

        $response = analyze();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('## Bestellung 402-0000000-0000001 (aus EOCS)') && $prompt->contains('Fototasse Schwarz (Art.-Nr. 11281)'));
        expect(urldecode((string) $response->headers->get('Location')))->toContain('bestellungen[0]=402-0000000-0000001');
    });

    test('with the opt-out eocs is not asked, but the analysis still knows the number and the product from the amazon notice', function () {
        CaseAgent::fake([caseAnswer()]);
        analysisTicket([zammadArticle(['id' => 1, 'body' => amazonNotice('Wo bekomme ich die Rechnung?', '305-0000000-0000001', [['B000TEST01', 'Mauspad mit Foto']])])], [analysisOrder()]);

        analyze(['ohne_bestelldetails' => '1']);

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'eocs.test'));
        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('## Bestellung 305-0000000-0000001 (im Ticket genannt, nicht aus EOCS geladen)')
            && $prompt->contains('Kanal laut Nummernformat: Amazon')
            && $prompt->contains('- laut Benachrichtigung im Ticket: Mauspad mit Foto (ASIN B000TEST01)')
            && ! $prompt->contains('Keine Bestellung geladen.'));
    });

    test('an order eocs does not know reaches the analysis as a known number, with a note in the result', function () {
        CaseAgent::fake([caseAnswer()]);
        analysisTicket([zammadArticle(['id' => 1, 'body' => '<p>Bestellung 402-0000000-0000009 fehlt.</p>'])], [analysisOrder()]);

        $this->get(analyze()->headers->get('Location'))
            ->assertSeeText('Bestellung 402-0000000-0000009 wurde in EOCS nicht gefunden; die Analyse kennt nur die Nummer.');

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('## Bestellung 402-0000000-0000009 (im Ticket genannt, nicht aus EOCS geladen)'));
    });

    test('when eocs is down the analysis runs with the number only and says so', function () {
        CaseAgent::fake([caseAnswer()]);
        fakeZammad([zammadArticle(['id' => 1, 'body' => '<p>Bestellung 402-0000000-0000001 ist defekt.</p>'])]);
        Http::fake(['eocs.test/*' => Http::response('down', 500)]);

        $this->get(analyze()->assertRedirect()->headers->get('Location'))
            ->assertSeeText('Die Bestelldetails konnten nicht aus EOCS geladen werden')
            ->assertSeeText('Ergebnis der Analyse');

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('402-0000000-0000001 (im Ticket genannt, nicht aus EOCS geladen)'));
    });

    test('the form announces the automatic loading and offers the opt-out only when a found number is not loaded', function () {
        analysisTicket([zammadArticle(['id' => 1, 'body' => '<p>Bestellung 402-0000000-0000001 ist defekt.</p>'])], [analysisOrder()]);

        $this->get('/tickets/2137942')
            ->assertSeeText('Vor der Analyse ruft die App die Bestelldetails aus EOCS ab: 402-0000000-0000001')
            ->assertSee('name="ohne_bestelldetails"', false);

        $this->get(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000001']]))
            ->assertDontSee('name="ohne_bestelldetails"', false);
    });

    test('the prompt tells the model never to ask for an order number it was given', function () {
        CaseAgent::fake([caseAnswer()]);
        analysisTicket();

        analyze();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => str_contains((string) $prompt->agent->instructions(), 'Frage nie nach einer Bestellnummer, die dort genannt ist.'));
    });
});
