<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\KnownCaseValues;
use App\Knowledge\CaseContext;
use App\Knowledge\CasePatternFinder;
use App\Knowledge\CustomerGroup;
use App\Knowledge\KnowledgeLibrary;
use App\Knowledge\KnowledgeSelector;
use App\Knowledge\Retrieval\CaseFacts;
use App\Knowledge\Retrieval\KnowledgeRetriever;
use App\Knowledge\Retrieval\OrderFacts;
use App\Knowledge\Retrieval\RetrievalResult;
use App\Knowledge\Retrieval\RetrievedDocument;
use App\Knowledge\SalesChannels;
use App\Models\Analysis;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Prompts\AgentPrompt;

afterEach(fn () => cleanUpKnowledgeBases());

/**
 * A playbook that is a case pattern (PROJ-103).
 *
 * @param  array<string, mixed>  $fields
 */
function patternDoc(int $number, array $fields = [], string $body = "Gilt für alle.\n\n# Fallmuster\n\nText."): array
{
    $id = sprintf('PLAYBOOK-%03d', $number);

    return [sprintf('playbooks/playbook-%03d-x.md', $number) => knowledgeDoc([
        'id' => $id, 'type' => 'playbook', 'title' => "Fallmuster {$number}", 'summary' => 'Wann gebraucht, ausführlich genug.',
        'retrieval' => 'triggered', 'triggers' => ['nie-im-text'], 'categories' => ['complaint'],
        'recognize' => 'Auf dem Foto fehlen oben oder unten Teile des Motivs, dort ist es noch dunkel.', ...$fields,
    ], $body)];
}

function patternProcess(int $number, array $fields = []): array
{
    $id = sprintf('PROCESS-%03d', $number);

    return [sprintf('processes/process-%03d-x.md', $number) => knowledgeDoc(['id' => $id, 'type' => 'process', 'title' => "Anleitung {$number}", 'summary' => 'Anleitung für den Kunden, ausführlich genug.', 'retrieval' => 'triggered', 'triggers' => ['nie-im-text'], 'categories' => ['complaint'], ...$fields], "Gilt für alle.\n\n# Ablauf\n\n1. Heißes Wasser einfüllen.")];
}

function mugProduct(): array
{
    return ['products/magic-mug.md' => knowledgeDoc(['id' => 'PRODUCT-001', 'type' => 'product', 'title' => 'Zaubertasse', 'retrieval' => 'core', 'summary' => 'Grundlagen der Tasse.', 'order_keywords' => ['Zaubertasse']])];
}

function patternGroup(string $key = 'reseller'): CustomerGroup
{
    return app(KnowledgeSelector::class)->customerGroup($key);
}

function patternIds(array $documents): array
{
    return array_map(fn ($document): string => $document->id, $documents);
}

describe('finding the case patterns', function () {
    test('only playbooks with a recognition text are patterns, those of the chosen product come first', function () {
        knowledgeBase([
            ...mugProduct(),
            ...patternDoc(1),
            ...patternDoc(2, ['products' => ['magic-mug']]),
            ...patternDoc(3, ['recognize' => null]),
        ]);
        $finder = app(CasePatternFinder::class);

        expect(patternIds($finder->find(patternGroup(), ['magic-mug'], null)))->toBe(['PLAYBOOK-002', 'PLAYBOOK-001'])
            ->and(patternIds($finder->find(patternGroup(), [], null)))->toBe(['PLAYBOOK-001']);
    });

    test('a pattern outside the customer group is not offered', function () {
        knowledgeBase([...patternDoc(1, ['customer_types' => ['b2c']]), ...patternDoc(2)]);

        expect(patternIds(app(CasePatternFinder::class)->find(patternGroup('reseller'), [], null)))->toBe(['PLAYBOOK-002']);
    });

    test('every one of the eleven ticket categories is mapped, the chosen form wins over the category of the ticket', function () {
        $finder = app(CasePatternFinder::class);

        foreach (['reklamation', 'produkt-frage', 'wismo', 'bestellung', 'storno', 'adressaenderung', 'zahlung', 'technischer-support', 'angebotsanfrage', 'sonstiges', 'spam'] as $category) {
            expect(array_key_exists($category, config('knowledge.pattern_categories')))->toBeTrue("{$category} fehlt in der Zuordnung");
        }

        expect($finder->knowledgeCategories('reklamation', 'frei'))->toBe(['complaint'])
            ->and($finder->knowledgeCategories('zahlung', null))->toBe(['order-process-question'])
            ->and($finder->knowledgeCategories('technischer-support', null))->toBe(['order-process-question'])
            ->and($finder->knowledgeCategories('sonstiges', null))->toBeNull()
            ->and($finder->knowledgeCategories('spam', null))->toBe([])
            ->and($finder->knowledgeCategories(null, null))->toBeNull()
            ->and($finder->knowledgeCategories('reklamation', 'wismo_info'))->toBe(['order-process-question'])
            ->and($finder->knowledgeCategories('reklamation', 'neuversand'))->toBe(['complaint']);
    });

    test('patterns without a category are offered for every category, others only for theirs', function () {
        knowledgeBase([...patternDoc(1), ...patternDoc(2, ['categories' => ['order-process-question']]), ...patternDoc(3, ['categories' => []])]);
        $finder = app(CasePatternFinder::class);

        expect(patternIds($finder->find(patternGroup(), [], ['complaint'])))->toBe(['PLAYBOOK-001', 'PLAYBOOK-003'])
            ->and(patternIds($finder->find(patternGroup(), [], [])))->toBe(['PLAYBOOK-003'])
            ->and(patternIds($finder->find(patternGroup(), [], null)))->toBe(['PLAYBOOK-001', 'PLAYBOOK-002', 'PLAYBOOK-003']);
    });
});

describe('the endpoint for the form', function () {
    test('it lists the patterns with their questions and says why a chosen one no longer fits', function () {
        knowledgeBase([
            ...mugProduct(),
            ...patternProcess(4),
            ...patternDoc(1, ['products' => ['magic-mug'], 'checks' => [['key' => 'gefuellt', 'question' => 'Bis knapp unter den Rand gefüllt?'], ['key' => 'foto', 'question' => 'Fotos in der Nachricht?', 'known_from' => 'current_message_has_attachment']], 'customer_check' => ['name' => 'Funktionstest', 'knowledge' => 'PROCESS-004']]),
            ...patternDoc(2, ['customer_types' => ['b2c']]),
        ]);

        $response = $this->withCookie('staff_name', 'Nele')->withHeaders(['Accept' => 'application/json'])->get(route('case-patterns', ['kundengruppe' => 'reseller', 'produkte' => ['magic-mug'], 'kategorie' => 'reklamation', 'gewaehlt' => ['PLAYBOOK-002']]));

        $response->assertOk()
            ->assertJsonPath('patterns.0.id', 'PLAYBOOK-001')
            ->assertJsonPath('patterns.0.for_product', true)
            ->assertJsonPath('patterns.0.products', ['Zaubertasse'])
            ->assertJsonPath('patterns.0.checks.1.known_from', 'current_message_has_attachment')
            ->assertJsonPath('patterns.0.customer_check', ['name' => 'Funktionstest', 'knowledge' => 'PROCESS-004'])
            ->assertJsonCount(1, 'patterns')
            ->assertJsonPath('dropped.PLAYBOOK-002.reason', 'nur für Kundenart b2c');
    });

    test('a channel without products offers only general patterns', function () {
        knowledgeBase([...mugProduct(), ...patternDoc(1, ['products' => ['magic-mug']]), ...patternDoc(2)]);
        config(['knowledge.channel_profiles.wecreateyour.without_products' => true]);

        $this->withCookie('staff_name', 'Nele')->withHeaders(['Accept' => 'application/json'])->get(route('case-patterns', ['kundengruppe' => 'reseller', 'verkaufskanal' => 'wecreateyour', 'produkte' => ['magic-mug']]))
            ->assertOk()->assertJsonCount(1, 'patterns')->assertJsonPath('patterns.0.id', 'PLAYBOOK-002');
    })->skip(fn (): bool => ! app(SalesChannels::class)->withoutProducts('wecreateyour'), 'Kanal ohne Produkte in dieser Konfiguration nicht vorhanden');
});

describe('values the app already knows', function () {
    test('a ticket value needs no order, an order value only one referenced order', function () {
        $order = new OrderFacts('303-1', ['shipped' => true, 'days_since_shipped' => 8]);
        $known = app(KnownCaseValues::class)->from(new CaseFacts(['current_message_has_attachment' => true], [$order], CaseFacts::REFERENCE_SINGLE, ['A' => [], 'B' => [], 'C' => []]));

        expect($known['current_message_has_attachment'])->toMatchArray(['value' => true, 'sure' => true, 'level' => 'case'])
            ->and($known['shipped'])->toMatchArray(['value' => true, 'sure' => true])
            ->and($known['days_since_shipped'])->toMatchArray(['value' => 8, 'sure' => true, 'type' => 'number'])
            ->and($known['delivery_confirmed'])->toMatchArray(['value' => null, 'sure' => false]);
    });

    test('several candidate orders never answer a question, the values are a hint with their order', function () {
        $facts = new CaseFacts(['current_message_has_attachment' => false], [new OrderFacts('303-1', ['shipped' => true]), new OrderFacts('303-2', [])], CaseFacts::REFERENCE_UNCLEAR, ['A' => [], 'B' => [], 'C' => []]);
        $known = app(KnownCaseValues::class)->from($facts);

        expect($known['shipped']['sure'])->toBeFalse()
            ->and($known['shipped']['hint'])->toContain('303-1: ja')->toContain('303-2: unbekannt')
            ->and($known['current_message_has_attachment'])->toMatchArray(['value' => false, 'sure' => true]);
    });

    test('without an order the order values are missing, with a reason', function () {
        $known = app(KnownCaseValues::class)->from(new CaseFacts([], [], CaseFacts::REFERENCE_NONE, ['A' => [], 'B' => [], 'C' => []]));

        expect($known['shipped'])->toMatchArray(['sure' => false, 'hint' => 'keine Bestellung geladen']);
    });

    test('contradicting values of one order are not sure', function () {
        $known = app(KnownCaseValues::class)->from(new CaseFacts([], [new OrderFacts('303-1', ['shipped' => false, 'delivery_confirmed' => true])], CaseFacts::REFERENCE_SINGLE, ['A' => [], 'B' => [], 'C' => []]));

        expect($known['delivery_confirmed']['sure'])->toBeFalse()->and($known['delivery_confirmed']['hint'])->toContain('widersprüchlich');
    });
});

describe('knowledge:check for case patterns', function () {
    function patternMessages(array $files): string
    {
        knowledgeBase($files);

        return messagesOf(app(KnowledgeLibrary::class), 'error');
    }

    test('a valid pattern passes', function () {
        expect(patternMessages([...patternProcess(4), ...patternDoc(1, ['checks' => [['key' => 'gefuellt', 'question' => 'Gefüllt?'], ['key' => 'post', 'question' => 'Benachrichtigt?', 'auto' => true]], 'customer_check' => ['name' => 'Funktionstest', 'knowledge' => 'PROCESS-004']])]))->toBe('');
    });

    test('checks or a customer check without a recognition text, and pattern fields outside a playbook, are errors', function () {
        expect(patternMessages(patternDoc(1, ['recognize' => null, 'checks' => [['key' => 'a', 'question' => 'Frage?']]])))->toContain('ohne `recognize`')
            ->and(patternMessages(patternProcess(4, ['recognize' => 'Auf dem Foto fehlen oben oder unten Teile des Motivs.'])))->toContain('nur bei Playbooks');
    });

    test('an unknown value of the app, a list value, a doubled key and both known_from and auto are errors', function () {
        $messages = patternMessages(patternDoc(1, ['checks' => [
            ['key' => 'a', 'question' => 'Eins?', 'known_from' => 'gibt_es_nicht'],
            ['key' => 'b', 'question' => 'Zwei?', 'known_from' => 'order_channel'],
            ['key' => 'a', 'question' => 'Drei?'],
            ['key' => 'c', 'question' => 'Vier?', 'known_from' => 'shipped', 'auto' => true],
        ]]));

        expect($messages)->toContain('„gibt_es_nicht“ ist kein bekannter Wert')->toContain('weder ein Ja/Nein-Wert noch eine Zahl')->toContain('kommt doppelt vor')->toContain('nicht beides');
    });

    test('the instructions of a customer check must exist, have no condition and cover the scope of the pattern', function () {
        expect(patternMessages(patternDoc(1, ['customer_check' => ['name' => 'Test', 'knowledge' => 'PROCESS-009']])))->toContain('gibt es nicht')
            ->and(patternMessages([...patternProcess(4, ['when' => ['ticket_closed' => false]]), ...patternDoc(1, ['customer_check' => ['name' => 'Test', 'knowledge' => 'PROCESS-004']])]))->toContain('`when`-Bedingung')
            ->and(patternMessages([...patternProcess(4, ['customer_types' => ['b2c']]), ...patternDoc(1, ['customer_check' => ['name' => 'Test', 'knowledge' => 'PROCESS-004']])]))->toContain('muss das Fallmuster abdecken')
            ->and(patternMessages([...mugProduct(), ...patternProcess(4, ['products' => ['magic-mug']]), ...patternDoc(1, ['customer_check' => ['name' => 'Test', 'knowledge' => 'PROCESS-004']])]))->toContain('gilt nur für Produkt magic-mug');
    });

    test('the new action customer-check is known', function () {
        expect(config('knowledge.actions'))->toHaveKey('customer-check')
            ->and(patternMessages(patternProcess(4, ['retrieval' => 'binding', 'triggers' => null, 'actions' => ['customer-check']])))->not->toContain('customer-check');
    });
});

describe('the retrieval of a chosen pattern', function () {
    function chosenRun(array $files, array $chosen, array $instructions = [], array $values = [], string $group = 'reseller'): RetrievalResult
    {
        knowledgeBase($files);
        $context = new CaseContext(patternGroup($group), []);

        return app(KnowledgeRetriever::class)->retrieve($context, new CaseFacts($values, [], CaseFacts::REFERENCE_NONE, ['A' => ['Hallo'], 'B' => [], 'C' => []]), [], null, [], $chosen, $instructions);
    }

    function chosenItem($result, string $id): ?RetrievedDocument
    {
        return collect($result->retrieved)->first(fn (RetrievedDocument $item): bool => $item->document->id === $id);
    }

    test('the chosen pattern and the instructions are mandatory, a related document strong', function () {
        $result = chosenRun([...patternProcess(4), ...patternProcess(5), ...patternDoc(1, ['related_knowledge' => ['PROCESS-005']])], ['PLAYBOOK-001'], ['PROCESS-004']);

        expect(chosenItem($result, 'PLAYBOOK-001'))->strength->toBe('mandatory')->origin->toBe(RetrievedDocument::ORIGIN_CHOSEN)
            ->and(chosenItem($result, 'PROCESS-004'))->strength->toBe('mandatory')
            ->and(chosenItem($result, 'PROCESS-004')->reason())->toBe('Anleitung der übernommenen Prüfung durch den Kunden')
            ->and(chosenItem($result, 'PROCESS-005'))->strength->toBe('strong')->origin->toBe(RetrievedDocument::ORIGIN_RELATED)
            ->and(chosenItem($result, 'PROCESS-005')->reason())->toBe('verwandt mit dem gewählten Fallmuster PLAYBOOK-001');
    });

    test('a related document outside the scope or with an unfulfilled or unknown condition stays out', function () {
        $files = [
            ...patternProcess(4, ['customer_types' => ['b2c']]),
            ...patternProcess(5, ['when' => ['ticket_closed' => true]]),
            ...patternProcess(6, ['when' => ['ticket_closed' => true]]),
            ...patternDoc(1, ['related_knowledge' => ['PROCESS-004', 'PROCESS-005', 'PROCESS-006']]),
        ];

        $result = chosenRun($files, ['PLAYBOOK-001'], [], ['ticket_closed' => false]);

        expect($result->ids())->toBe(['PLAYBOOK-001']);
        expect(chosenRun($files, ['PLAYBOOK-001'], [], ['ticket_closed' => true])->ids())->toContain('PROCESS-005')->not->toContain('PROCESS-004');
    });

    test('a chosen pattern outside the scope is not retrieved', function () {
        expect(chosenRun(patternDoc(1, ['customer_types' => ['b2c']]), ['PLAYBOOK-001'])->ids())->toBe([]);
    });

    test('a related document that does not fit into the budget is left out, the chosen pattern stays', function () {
        config()->set('knowledge.retrieval.budget.target', 400);
        knowledgeBase([
            'processes/process-005-x.md' => knowledgeDoc(['id' => 'PROCESS-005', 'type' => 'process', 'title' => 'Groß', 'summary' => 'Sehr großes Dokument zum Testen.', 'retrieval' => 'triggered', 'triggers' => ['nie-im-text'], 'categories' => ['complaint']], str_repeat('x', 900)),
            ...patternDoc(1, ['related_knowledge' => ['PROCESS-005']]),
        ]);

        $result = app(KnowledgeRetriever::class)->retrieve(new CaseContext(patternGroup(), []), new CaseFacts([], [], CaseFacts::REFERENCE_NONE, ['A' => [], 'B' => [], 'C' => []]), [], null, [], ['PLAYBOOK-001']);

        expect($result->ids())->toBe(['PLAYBOOK-001']);
    });
});

describe('the analysis with guided fact-finding', function () {
    beforeEach(function () {
        $this->withoutVite();
        config(['services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token', 'ai.providers.openai.key' => 'sk-test']);
        fakeZammad([zammadArticle(['id' => 1, 'subject' => 'Reklamation', 'body' => '<p>Die Köpfe auf meiner Tasse sind abgeschnitten.</p>'])]);
        Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
    });

    function findingsAnswer(): array
    {
        return [
            'summary' => ['incident' => 'Köpfe abgeschnitten', 'customer_wish' => 'Lösung'],
            'category' => 'complaint', 'case_pattern' => null, 'assessment' => 'unklar',
            'recommendation' => 'Funktionstest erbitten.', 'actions' => ['customer-check'],
            'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
            'reasoning' => 'Erwärmung möglich.', 'knowledge_gaps' => [],
            'knowledge_ids' => [], 'confidence' => ['level' => 'MITTEL', 'reasons' => []],
            'next_step' => 'reply', 'decision' => null, 'internal_steps' => [],
            'reply' => ['language' => 'Deutsch', 'text' => "Hallo,\nbitte testen Sie die Tasse."],
        ];
    }

    function findingsRun(array $fields = [])
    {
        return test()->withCookie('staff_name', 'Nele')->from('/tickets/2137942')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kein_produktbezug' => '1', 'kundengruppe' => 'reseller', 'variante' => 'verlauf', 'formular' => 'reklamation', ...$fields]);
    }

    function findingsKnowledge(array $patternFields = []): void
    {
        knowledgeBase([
            ...patternProcess(4),
            ...patternDoc(1, ['checks' => [['key' => 'gefuellt', 'question' => 'Ist die Tasse bis knapp unter den Rand gefüllt?'], ['key' => 'rand', 'question' => 'Ist der Rand noch dunkel?'], ['key' => 'foto', 'question' => 'Enthält die Nachricht Fotos?', 'known_from' => 'current_message_has_attachment']], 'customer_check' => ['name' => 'Funktionstest mit heißem Wasser', 'knowledge' => 'PROCESS-004'], ...$patternFields]),
        ]);
    }

    test('without the guided fact-finding the prompt has no new section and the complaint still needs its text', function () {
        findingsKnowledge();
        CaseAgent::fake([findingsAnswer()]);

        findingsRun(['reklamation_berechtigt' => 'info'])->assertSessionHasErrors(['reklamation_info' => 'Bitte angeben, welche Informationen für die Entscheidung nötig sind.']);
        findingsRun(['reklamation_berechtigt' => 'info', 'reklamation_info' => 'Ein Foto der Unterseite.'])->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => ! $prompt->contains('Sachverhaltsermittlung des Mitarbeiters') && ! $prompt->contains('Entscheidung des Mitarbeiters'));
        expect(Analysis::query()->latest('id')->first()->content)->not->toHaveKey('findings');
    });

    test('patterns are hypotheses, answers observations, open questions stay open, apart from the checked facts', function () {
        findingsKnowledge();
        CaseAgent::fake([findingsAnswer()]);

        findingsRun(['reklamation_berechtigt' => 'info', 'reklamation_info' => 'Foto', 'fallmuster' => ['PLAYBOOK-001'], 'pruefung' => ['PLAYBOOK-001' => ['gefuellt' => 'nein', 'rand' => 'offen', 'foto' => 'nein']]])->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(function (AgentPrompt $prompt): bool {
            $text = $prompt->prompt;
            $section = str($text)->after('# Sachverhaltsermittlung des Mitarbeiters')->before('# Ticket')->toString();

            return str_contains($text, '# Sachverhaltsermittlung des Mitarbeiters (Hypothesen und Beobachtungen, nicht geprüft)')
                && str_contains($section, 'In Betracht gezogenes Fallmuster (Hypothese, nicht bestätigt): PLAYBOOK-001')
                && str_contains($section, 'Ist die Tasse bis knapp unter den Rand gefüllt? → nein (Beobachtung des Mitarbeiters)')
                && str_contains($section, 'Offen: Ist der Rand noch dunkel? (nicht beurteilbar)')
                && str_contains($section, 'Enthält die Nachricht Fotos? → nein (System:')
                && strpos($text, '# Zusätzliche Informationen vom Mitarbeiter') < strpos($text, '# Sachverhaltsermittlung');
        });
    });

    test('a customer check taken over is a decision, it replaces the required text and brings its instructions', function () {
        findingsKnowledge();
        CaseAgent::fake([findingsAnswer()]);

        findingsRun(['reklamation_berechtigt' => 'info', 'fallmuster' => ['PLAYBOOK-001'], 'kundenpruefung' => ['PLAYBOOK-001']])->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Entscheidung des Mitarbeiters: Der Kunde soll die Prüfung „Funktionstest mit heißem Wasser“ durchführen. Die Anleitung steht in PROCESS-004')
            && $prompt->contains('ergibt sich aus der übernommenen Prüfung durch den Kunden'));

        $stored = Analysis::query()->latest('id')->first()->content;
        expect($stored['findings']['patterns'][0]['adopted'])->toBe(['name' => 'Funktionstest mit heißem Wasser', 'knowledge' => 'PROCESS-004'])
            ->and($stored['inputs']['fallmuster'])->toBe(['PLAYBOOK-001'])
            ->and($stored['inputs']['kundenpruefung'])->toBe(['PLAYBOOK-001']);
    });

    test('only choosing the pattern does not replace the required text, nor does a check of a pattern that is not chosen', function () {
        findingsKnowledge();

        findingsRun(['reklamation_berechtigt' => 'info', 'fallmuster' => ['PLAYBOOK-001']])->assertSessionHasErrors(['reklamation_info' => 'Bitte angeben, welche Informationen für die Entscheidung nötig sind.']);
        findingsRun(['reklamation_berechtigt' => 'info', 'kundenpruefung' => ['PLAYBOOK-001']])->assertSessionHasErrors('reklamation_info');
    });

    test('a check of a pattern outside the scope does not count and says the instructions are missing', function () {
        findingsKnowledge(['customer_types' => ['b2c']]);

        $response = findingsRun(['reklamation_berechtigt' => 'info', 'fallmuster' => ['PLAYBOOK-001'], 'kundenpruefung' => ['PLAYBOOK-001']]);

        $response->assertSessionHasErrors('reklamation_info');
        expect(session('errors')->first('reklamation_info'))->toContain('ihre Anleitung fehlt im Wissen');
    });

    test('a chosen pattern that does not fit the case is left out and named in the notes', function () {
        findingsKnowledge(['customer_types' => ['b2c']]);
        CaseAgent::fake([findingsAnswer()]);

        findingsRun(['reklamation_berechtigt' => 'info', 'reklamation_info' => 'Foto', 'fallmuster' => ['PLAYBOOK-001']])->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => ! $prompt->contains('Sachverhaltsermittlung des Mitarbeiters'));
        expect(Analysis::query()->latest('id')->first()->content['notes'])->toContain('Nicht berücksichtigt, weil es für diesen Fall nicht gilt: Fallmuster Fallmuster 1.');
    });

    test('"no pattern fits" goes to the model and is no knowledge gap', function () {
        findingsKnowledge();
        CaseAgent::fake([findingsAnswer()]);

        findingsRun(['reklamation_berechtigt' => 'info', 'reklamation_info' => 'Foto', 'kein_fallmuster' => '1'])->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Keines passt. Das ist keine Wissenslücke.'));
    });

    test('the result shows the fact-finding with name and time, the page offers to open it again', function () {
        findingsKnowledge();
        CaseAgent::fake([findingsAnswer()]);

        findingsRun(['reklamation_berechtigt' => 'info', 'fallmuster' => ['PLAYBOOK-001'], 'pruefung' => ['PLAYBOOK-001' => ['gefuellt' => 'nein']], 'kundenpruefung' => ['PLAYBOOK-001']]);
        $id = Analysis::query()->latest('id')->first()->uuid;

        $this->withCookie('staff_name', 'Nele')->get("/tickets/2137942?analyse={$id}")
            ->assertOk()
            ->assertSeeTextInOrder(['Sachverhaltsermittlung', 'Nele', 'Fallmuster 1', 'in Betracht gezogen', 'Ist die Tasse bis knapp unter den Rand gefüllt?', 'nein', 'übernommen', 'Funktionstest mit heißem Wasser'])
            ->assertSee('open-case-patterns', false)
            ->assertSee('\u0022selected\u0022:[\u0022PLAYBOOK-001\u0022]', false);
    });
});

describe('the form', function () {
    beforeEach(function () {
        $this->withoutVite();
        config(['services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token']);
        fakeZammad([zammadArticle(['id' => 1, 'body' => '<p>Hallo</p>'])]);
        knowledgeBase(patternDoc(1));
    });

    test('the area is called "Ticket bearbeiten" with the three functions in order and the new button', function () {
        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')
            ->assertOk()
            ->assertSeeTextInOrder(['Ticket bearbeiten', 'Verkaufskanal', 'Direkte Analyse', 'Geführte Sachverhaltsermittlung', 'Geführte Bearbeitung', 'Formular', 'Freitext', 'Antwortvorschlag erstellen'])
            ->assertDontSeeText('Vorschlag erstellen</h2>', false)
            ->assertSee('fallmuster', false);
    });

    test('the required text of an undecided complaint is only required without a customer check taken over', function () {
        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')
            ->assertSee('x-bind:required="$store.caseFindings.adopted.length === 0"', false);
    });
});
