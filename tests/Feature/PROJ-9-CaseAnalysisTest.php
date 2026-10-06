<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\Agents\SummaryAgent;
use App\Analysis\AnalysisStore;
use App\Analysis\Pseudonymizer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
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
                'Ergebnis der Analyse', 'Unklar', 'Confidence MITTEL',
                'Die Tasse bleibt schwarz.', 'Ersatz', 'complaint', 'Thermoeffekt angeblich defekt',
                'Foto mit heißem Wasser anfordern.', 'Foto anfordern',
                'Der Kundenservice darf selbst entscheiden.', 'Ohne Test kein Urteil.',
                'Foto der warmen Tasse', 'Rückfrage:', 'Bitte senden Sie ein Foto.',
                'Bestellung gefunden', 'Antwort abwarten', 'POLICY-001', 'Entwurf',
                'Antwortentwurf', 'Hallo Erika,',
                'Nele', 'gpt-5.5', 'Prompt analysis-',
            ])
            ->assertSee('<mark class="placeholder-filled" title="Von der App eingesetzt">Erika Beispiel<br>Musterweg 7<br>12345 Musterstadt<br>DE</mark>', false);
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
            ->assertSee('<mark class="placeholder-missing" title="Bitte ausfüllen">[KUNDENNUMMER]</mark>', false);
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

        expect(Cache::get("analysis.result.{$query['analyse']}"))->toBeString()->not->toContain('Die Tasse bleibt schwarz');
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
            ->assertSeeTextInOrder(['Nachricht 4', 'Zusammenfassung des bisherigen Verlaufs', 'zusammengefasst bis Nachricht vom', 'vorübergehend gespeichert', 'Sachverhalt', 'Tasse defekt, Kontakt 0171 2345678', 'Nachricht 5'])
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

    test('the summary is stored encrypted for seven days', function () {
        SummaryAgent::fake([summaryAnswer()]);
        analysisTicket(longThread());

        $this->withCookie('staff_name', 'Nele')->post(route('tickets.summary.create', ['number' => '2137942']));

        expect(Cache::get('analysis.summary.2137942'))->toBeString()->not->toContain('Tasse defekt')
            ->and(app(AnalysisStore::class)->summary('2137942')->text)->toContain('Tasse defekt');

        $this->travel(8)->days();

        expect(app(AnalysisStore::class)->summary('2137942'))->toBeNull();
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
