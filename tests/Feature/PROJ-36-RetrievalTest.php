<?php

use App\Knowledge\CaseContext;
use App\Knowledge\CustomerGroup;
use App\Knowledge\Retrieval\CaseFacts;
use App\Knowledge\Retrieval\KnowledgeRetriever;
use App\Knowledge\Retrieval\KnowledgeTooLarge;
use App\Knowledge\Retrieval\MandatoryScope;
use App\Knowledge\Retrieval\OrderFacts;
use App\Knowledge\Retrieval\RetrievalResult;
use App\Knowledge\Retrieval\RetrievedDocument;

afterEach(fn () => cleanUpKnowledgeBases());

/**
 * @param  list<string>  $a
 * @param  list<string>  $b
 * @param  list<string>  $c
 * @param  array<string, mixed>  $values
 * @param  list<OrderFacts>  $orders
 */
function retrFacts(array $a = [], array $b = [], array $c = [], array $values = [], array $orders = [], string $reference = CaseFacts::REFERENCE_NONE): CaseFacts
{
    return new CaseFacts($values, $orders, $reference, ['A' => $a, 'B' => $b, 'C' => $c]);
}

function retrContext(string $group = 'reseller', array $products = []): CaseContext
{
    return new CaseContext(collect(CustomerGroup::all(config('knowledge.customer_groups')))->first(fn (CustomerGroup $item): bool => $item->key === $group), $products);
}

/**
 * A policy with the retrieval fields; the number picks the ID and the file.
 *
 * @param  array<string, mixed>  $fields
 */
function retrPolicy(int $number, array $fields = [], string $body = "Gilt für alle.\n\n# Regel\n\nEin Satz."): array
{
    $id = sprintf('POLICY-%03d', $number);

    return [sprintf('policies/policy-%03d-x.md', $number) => knowledgeDoc(['id' => $id, 'title' => "Regel {$number}", 'summary' => 'Wann gebraucht.', ...$fields], $body)];
}

function retrRun(array $files, CaseFacts $facts, ?CaseContext $context = null, array $manual = [], ?int $available = null): RetrievalResult
{
    knowledgeBase($files);

    return app(KnowledgeRetriever::class)->retrieve($context ?? retrContext(), $facts, $manual, $available);
}

function retrIds(RetrievalResult $result): array
{
    return $result->ids();
}

function retrStrength(RetrievalResult $result, string $id): ?string
{
    return collect($result->retrieved)->first(fn (RetrievedDocument $item): bool => $item->document->id === $id)?->strength;
}

function retrMissed(RetrievalResult $result, string $id): ?array
{
    return collect($result->notRetrieved)->first(fn (array $item): bool => $item['document']->id === $id);
}

describe('core and unclassified documents', function () {
    test('core knowledge is always retrieved as mandatory', function () {
        $result = retrRun(retrPolicy(1, ['retrieval' => 'core']), retrFacts());

        expect(retrIds($result))->toBe(['POLICY-001'])->and(retrStrength($result, 'POLICY-001'))->toBe('mandatory')->and($result->unclassified)->toBe([]);
    });

    test('a document without class counts as core in the transition mode and is listed as unclassified', function () {
        $result = retrRun(retrPolicy(1), retrFacts());

        expect(retrIds($result))->toBe(['POLICY-001'])->and($result->unclassified)->toBe(['POLICY-001'])
            ->and($result->retrieved[0]->reason())->toBe('Grundwissen (noch nicht klassifiziert)')
            ->and(implode(' ', $result->warnings))->toContain('noch nicht klassifiziert');
    });

    test('documents outside the customer group are not even considered', function () {
        $files = retrPolicy(1, ['retrieval' => 'core', 'customer_types' => ['b2c']]) + retrPolicy(2, ['retrieval' => 'core']);

        expect(retrIds(retrRun($files, retrFacts(), retrContext('reseller'))))->toBe(['POLICY-002']);
    });
});

describe('triggered documents', function () {
    test('a trigger in the current concern retrieves the document strongly, with the trigger as reason', function () {
        $result = retrRun(retrPolicy(1, ['retrieval' => 'triggered', 'triggers' => ['Urheberrecht']]), retrFacts(['Ist das Urheberrecht geklärt?']));

        expect(retrIds($result))->toBe(['POLICY-001'])->and(retrStrength($result, 'POLICY-001'))->toBe('strong')
            ->and($result->retrieved[0]->reason())->toBe('Auslöser „Urheberrecht“ (aktuelles Anliegen)');
    });

    test('without a hit the document is checked but not retrieved', function () {
        $result = retrRun(retrPolicy(1, ['retrieval' => 'triggered', 'triggers' => ['Urheberrecht']]), retrFacts(['Wo ist meine Bestellung?']));

        expect(retrIds($result))->toBe([])->and(retrMissed($result, 'POLICY-001')['code'])->toBe(RetrievalResult::NO_TRIGGER);
    });

    test('a hit only in earlier messages is weak', function () {
        $result = retrRun(retrPolicy(1, ['retrieval' => 'triggered', 'triggers' => ['Urheberrecht']]), retrFacts(['Danke'], ['Vorher ging es um das Urheberrecht.']));

        expect(retrStrength($result, 'POLICY-001'))->toBe('weak')->and($result->retrieved[0]->reason())->toContain('früherer Verlauf');
    });

    test('a trigger matches at the start of a word, in any case, not in the middle', function () {
        $files = retrPolicy(1, ['retrieval' => 'triggered', 'triggers' => ['Glasstein']]);

        expect(retrIds(retrRun($files, retrFacts(['Die GLASSTEINE sind gebrochen']))))->toBe(['POLICY-001'])
            ->and(retrIds(retrRun($files, retrFacts(['Ein Kleinglasstein ist gebrochen']))))->toBe([]);
    });

    test('several concerns retrieve every matching document, also with the same trigger', function () {
        $files = retrPolicy(1, ['retrieval' => 'triggered', 'triggers' => ['Erstattung', 'Rückgabe']])
            + retrPolicy(2, ['retrieval' => 'triggered', 'triggers' => ['Rechnung']])
            + retrPolicy(3, ['retrieval' => 'triggered', 'triggers' => ['Erstattung']]);

        $result = retrRun($files, retrFacts(['Ich möchte eine Erstattung und bitte um eine Rechnung.']));

        expect(retrIds($result))->toBe(['POLICY-001', 'POLICY-002', 'POLICY-003']);
    });

    test('a fulfilled condition retrieves the document without a trigger; unknown or failed does not', function () {
        $files = retrPolicy(1, ['retrieval' => 'triggered', 'when' => ['ticket_closed' => true]]);

        expect(retrStrength(retrRun($files, retrFacts(values: ['ticket_closed' => true])), 'POLICY-001'))->toBe('strong')
            ->and(retrMissed(retrRun($files, retrFacts(values: ['ticket_closed' => false])), 'POLICY-001')['code'])->toBe(RetrievalResult::CONDITION_FALSE)
            ->and(retrMissed(retrRun($files, retrFacts(values: ['ticket_closed' => null])), 'POLICY-001')['code'])->toBe(RetrievalResult::CONDITION_UNKNOWN);
    });

    test('the product is a scope, not a trigger: a product document needs a hit, the product file itself can be core', function () {
        $files = [
            'products/magic-mug.md' => knowledgeDoc(['id' => 'PRODUCT-001', 'type' => 'product', 'title' => 'Zaubertasse', 'retrieval' => 'core', 'summary' => 'Grundlagen.']),
            'playbooks/playbook-001-x.md' => knowledgeDoc(['id' => 'PLAYBOOK-001', 'type' => 'playbook', 'title' => 'Thermoeffekt', 'products' => ['magic-mug'], 'categories' => ['complaint'], 'retrieval' => 'triggered', 'summary' => 'x', 'triggers' => ['Thermoeffekt']]),
            'playbooks/playbook-002-x.md' => knowledgeDoc(['id' => 'PLAYBOOK-002', 'type' => 'playbook', 'title' => 'Schwarz', 'products' => ['magic-mug'], 'categories' => ['complaint'], 'retrieval' => 'triggered', 'summary' => 'x', 'triggers' => ['schwarz']]),
        ];

        $result = retrRun($files, retrFacts(['Der Thermoeffekt funktioniert nicht']), retrContext('reseller', ['magic-mug']));

        expect(retrIds($result))->toBe(['PRODUCT-001', 'PLAYBOOK-001'])->and(retrMissed($result, 'PLAYBOOK-002')['code'])->toBe(RetrievalResult::NO_TRIGGER);
        expect(retrIds(retrRun($files, retrFacts(['Der Thermoeffekt funktioniert nicht']))))->toBe([]);
    });
});

describe('binding documents', function () {
    test('without trigger, condition and action a binding document applies in its scope', function () {
        $result = retrRun(retrPolicy(1, ['retrieval' => 'binding']), retrFacts());

        expect(retrStrength($result, 'POLICY-001'))->toBe('mandatory')->and($result->retrieved[0]->reason())->toBe('verbindlich im Geltungsbereich');
    });

    test('a hit retrieves it even in an earlier message; no hit and no other way leaves it out', function () {
        $files = retrPolicy(1, ['retrieval' => 'binding', 'triggers' => ['Erstattung']]);

        expect(retrStrength(retrRun($files, retrFacts(['Ich möchte eine Erstattung'])), 'POLICY-001'))->toBe('mandatory')
            ->and(retrStrength(retrRun($files, retrFacts(['Danke'], ['Früher: Erstattung'])), 'POLICY-001'))->toBe('mandatory')
            ->and(retrMissed(retrRun($files, retrFacts(['Wo ist mein Paket'])), 'POLICY-001')['code'])->toBe(RetrievalResult::NO_TRIGGER);
    });

    test('a fulfilled condition retrieves it without any keyword', function () {
        $files = retrPolicy(1, ['retrieval' => 'binding', 'when' => ['ticket_age_days' => ['min' => 30]]]);

        expect(retrStrength(retrRun($files, retrFacts(values: ['ticket_age_days' => 45])), 'POLICY-001'))->toBe('mandatory')
            ->and(retrMissed(retrRun($files, retrFacts(values: ['ticket_age_days' => 5])), 'POLICY-001')['code'])->toBe(RetrievalResult::CONDITION_FALSE);
    });

    test('an unknown condition brings it along as a precaution, marked as unverified, never as fulfilled', function () {
        $files = retrPolicy(1, ['retrieval' => 'binding', 'when' => ['days_since_shipped' => ['min' => 14]]]);

        $result = retrRun($files, retrFacts());
        $item = $result->retrieved[0];

        expect($item->document->id)->toBe('POLICY-001')->and($item->unverified)->toBeTrue()->and($item->unknownKeys)->toBe(['days_since_shipped'])
            ->and($item->reason())->toContain('Voraussetzung ungeklärt')->and($result->evaluations['POLICY-001']->result->value)->toBe('unknown');
    });

    test('a known no with an unknown part does not bring it along', function () {
        $files = retrPolicy(1, ['retrieval' => 'binding', 'when' => ['ticket_closed' => true, 'days_since_shipped' => ['min' => 14]]]);

        expect(retrMissed(retrRun($files, retrFacts(values: ['ticket_closed' => false])), 'POLICY-001')['code'])->toBe(RetrievalResult::CONDITION_FALSE);
    });

    test('a document tied only to an action waits for the safeguard', function () {
        $result = retrRun(retrPolicy(1, ['retrieval' => 'binding', 'actions' => ['refund']]), retrFacts(['Wo ist mein Paket']));

        expect(retrIds($result))->toBe([])->and(retrMissed($result, 'POLICY-001')['code'])->toBe(RetrievalResult::ACTION_BOUND)
            ->and(array_map(fn ($document) => $document->id, $result->actionBound()))->toBe(['POLICY-001']);
    });

    test('an action-bound document still comes along when the customer asks for it by keyword', function () {
        $files = retrPolicy(1, ['retrieval' => 'binding', 'actions' => ['refund'], 'triggers' => ['Erstattung']]);

        expect(retrIds(retrRun($files, retrFacts(['Ich möchte eine Erstattung']))))->toBe(['POLICY-001']);
    });
});

describe('exclusions', function () {
    $logo = fn (): array => retrPolicy(17, ['retrieval' => 'triggered', 'triggers' => ['Logo', 'Urheberrecht'], 'exclude_triggers' => ['eigenen Logo', 'eigenes Logo']]);

    test('an exclusion in the same sentence suppresses the hit and says so', function () use ($logo) {
        $result = retrRun($logo(), retrFacts(['Wir hätten gern ein Angebot für 200 Tassen mit unserem eigenen Logo.']));

        expect(retrIds($result))->toBe([])->and(retrMissed($result, 'POLICY-017')['code'])->toBe(RetrievalResult::SUPPRESSED)
            ->and($result->suppressed)->toHaveCount(1)->and($result->suppressed[0])->toMatchArray(['id' => 'POLICY-017', 'trigger' => 'Logo', 'exclusion' => 'eigenen Logo'])
            ->and(retrMissed($result, 'POLICY-017')['detail'])->toContain('eigenen Logo');
    });

    test('a hit in another sentence stays', function () use ($logo) {
        $result = retrRun($logo(), retrFacts(['Wir drucken unser eigenes Logo. Außerdem fragen wir zum Urheberrecht nach einer Vorlage.']));

        expect(retrIds($result))->toBe(['POLICY-017']);
    });

    test('a question is never suppressed', function () use ($logo) {
        expect(retrIds(retrRun($logo(), retrFacts(['Ist unser eigenes Logo urheberrechtlich geschützt?']))))->toBe(['POLICY-017'])
            ->and(retrIds(retrRun($logo(), retrFacts(['Darf ich mein eigenes Logo mit Urheberrecht drucken lassen.']))))->toBe(['POLICY-017']);
    });

    test('an exclusion needs a hit in the same sentence to matter', function () use ($logo) {
        expect(retrIds(retrRun($logo(), retrFacts(['Mein eigenes Logo ist angehängt.', 'Bitte prüft das Urheberrecht.']))))->toBe(['POLICY-017']);
    });
});

describe('the budget', function () {
    test('mandatory knowledge is never cut, not even above the target, and warns', function () {
        config(['knowledge.retrieval.budget.target' => 100]);

        $result = retrRun(retrPolicy(1, ['retrieval' => 'core'], str_repeat('x', 300)), retrFacts());

        expect(retrIds($result))->toBe(['POLICY-001'])->and(implode(' ', $result->warnings))->toContain('liegt über dem Zielumfang');
    });

    test('strong comes before weak, weak before examples, and cutting goes from the back with a note', function () {
        config(['knowledge.retrieval.budget.target' => 700]);

        $files = retrPolicy(1, ['retrieval' => 'core'], str_repeat('a', 300))
            + retrPolicy(2, ['retrieval' => 'triggered', 'triggers' => ['stark']], str_repeat('b', 300))
            + retrPolicy(3, ['retrieval' => 'triggered', 'triggers' => ['schwach']], str_repeat('c', 300));

        $result = retrRun($files, retrFacts(['Das ist stark'], ['Das war schwach']));

        expect(retrIds($result))->toBe(['POLICY-001', 'POLICY-002'])->and(retrMissed($result, 'POLICY-003')['code'])->toBe(RetrievalResult::OUT_OF_SPACE)
            ->and(array_map(fn ($document) => $document->id, $result->omitted()))->toBe(['POLICY-003'])->and(retrMissed($result, 'POLICY-003')['detail'])->toContain('wegen Umfang nicht mitgesendet');
    });

    test('the stronger document wins when only one fits', function () {
        config(['knowledge.retrieval.budget.target' => 400]);

        $files = retrPolicy(1, ['retrieval' => 'triggered', 'triggers' => ['schwach']], str_repeat('a', 300))
            + retrPolicy(2, ['retrieval' => 'triggered', 'triggers' => ['stark']], str_repeat('b', 300));

        expect(retrIds(retrRun($files, retrFacts(['stark'], ['schwach']))))->toBe(['POLICY-002']);
    });

    test('strong hits are ranked by their score: more and longer triggers first', function () {
        config(['knowledge.retrieval.budget.target' => 400]);

        $files = retrPolicy(1, ['retrieval' => 'triggered', 'triggers' => ['Erstattung']], str_repeat('a', 300))
            + retrPolicy(2, ['retrieval' => 'triggered', 'triggers' => ['Erstattung', 'Rückzahlung']], str_repeat('b', 300));

        expect(retrIds(retrRun($files, retrFacts(['Ich möchte eine Erstattung bzw. Rückzahlung']))))->toBe(['POLICY-002']);
    });

    test('a hard limit smaller than the target shrinks only the optional part', function () {
        $files = retrPolicy(1, ['retrieval' => 'core'], str_repeat('a', 300)) + retrPolicy(2, ['retrieval' => 'triggered', 'triggers' => ['stark']], str_repeat('b', 300));

        expect(retrIds(retrRun($files, retrFacts(['stark']), available: 400)))->toBe(['POLICY-001'])
            ->and(retrIds(retrRun($files, retrFacts(['stark']), available: 700)))->toBe(['POLICY-001', 'POLICY-002']);
    });

    test('mandatory knowledge that does not fit the hard limit stops the analysis instead of cutting', function () {
        retrRun(retrPolicy(1, ['retrieval' => 'core'], str_repeat('a', 500)), retrFacts(), available: 100);
    })->throws(KnowledgeTooLarge::class, 'Zusammenfassung verwenden oder Admin informieren');

    test('binding documents are never displaced by triggered ones', function () {
        config(['knowledge.retrieval.budget.target' => 400]);

        $files = retrPolicy(1, ['retrieval' => 'binding'], str_repeat('a', 300))
            + retrPolicy(2, ['retrieval' => 'triggered', 'triggers' => ['stark']], str_repeat('b', 300));

        $result = retrRun($files, retrFacts(['stark']));

        expect(retrIds($result))->toBe(['POLICY-001'])->and(retrMissed($result, 'POLICY-002')['code'])->toBe(RetrievalResult::OUT_OF_SPACE);
    });

    test('reference cases come last and are capped per kind', function () {
        config(['knowledge.selection.max_good_examples' => 1]);

        $good = fn (int $number): array => ["examples/good/example-good-{$number}-x.md" => knowledgeDoc(['id' => sprintf('EXAMPLE-GOOD-%03d', $number), 'type' => 'example-good', 'title' => "Beispiel {$number}", 'summary' => 'x', 'retrieval' => 'core'])];
        $result = retrRun(retrPolicy(1, ['retrieval' => 'core']) + $good(1) + $good(2), retrFacts());

        expect(retrIds($result))->toBe(['POLICY-001', 'EXAMPLE-GOOD-001'])->and(retrMissed($result, 'EXAMPLE-GOOD-002')['detail'])->toContain('Obergrenze für Beispiele');
    });

    test('the retrieved documents keep the order of the knowledge base: type, then ID', function () {
        $files = retrPolicy(2, ['retrieval' => 'core']) + retrPolicy(1, ['retrieval' => 'triggered', 'triggers' => ['stark']])
            + ['tone/tone-001-x.md' => knowledgeDoc(['id' => 'TONE-001', 'type' => 'tone', 'title' => 'Ton', 'summary' => 'x', 'retrieval' => 'core'])];

        expect(retrIds(retrRun($files, retrFacts(['stark']))))->toBe(['POLICY-001', 'POLICY-002', 'TONE-001']);
    });
});

describe('switching a document on by hand', function () {
    test('a document that was not retrieved comes along as mandatory, marked as by hand', function () {
        $files = retrPolicy(1, ['retrieval' => 'triggered', 'triggers' => ['Urheberrecht']]);

        $result = retrRun($files, retrFacts(['Wo ist mein Paket']), manual: ['POLICY-001']);

        expect(retrIds($result))->toBe(['POLICY-001'])->and(retrStrength($result, 'POLICY-001'))->toBe('mandatory')->and($result->retrieved[0]->reason())->toBe('von Hand zugeschaltet')
            ->and(retrMissed($result, 'POLICY-001'))->toBeNull();
    });

    test('an unknown ID or a document outside the scope is ignored', function () {
        $files = retrPolicy(1, ['retrieval' => 'triggered', 'triggers' => ['x-nie'], 'customer_types' => ['b2c']]);

        expect(retrIds(retrRun($files, retrFacts(), manual: ['POLICY-001', 'POLICY-999'])))->toBe([]);
    });
});

describe('conditions and orders', function () {
    test('a condition on orders uses the reference order only', function () {
        $files = retrPolicy(1, ['retrieval' => 'triggered', 'when' => ['order_channel' => 'amazon', 'days_since_shipped' => ['min' => 14]]]);
        $a = new OrderFacts('A', ['order_channel' => 'amazon', 'days_since_shipped' => 3]);
        $b = new OrderFacts('B', ['order_channel' => 'looxis-de', 'days_since_shipped' => 30]);

        $result = retrRun($files, retrFacts(orders: [$a, $b], reference: CaseFacts::REFERENCE_UNCLEAR));

        expect(retrIds($result))->toBe([])->and($result->evaluations['POLICY-001']->orderUnclear)->toBeTrue();
    });
});

describe('the budget check of the knowledge base', function () {
    test('mandatory knowledge above the budget is a violation, per customer group', function () {
        config(['knowledge.retrieval.budget.core' => 100]);
        knowledgeBase(retrPolicy(1, ['retrieval' => 'core'], str_repeat('a', 300)));

        $violations = app(MandatoryScope::class)->violations();

        expect($violations)->not->toBeEmpty()->and($violations[0])->toContain('Pflichtwissen ohne Produktbezug umfasst 300 Zeichen (Grenze 100)');
    });

    test('documents without a class are not counted, binding with a trigger is not mandatory', function () {
        config(['knowledge.retrieval.budget.core' => 100]);
        knowledgeBase(retrPolicy(1, [], str_repeat('a', 300)) + retrPolicy(2, ['retrieval' => 'binding', 'triggers' => ['Erstattung'], 'when' => ['has_claim' => true]], str_repeat('b', 300)));

        $scope = app(MandatoryScope::class);

        expect($scope->violations())->toBe([])->and($scope->unclassified()->pluck('id')->all())->toBe(['POLICY-001']);
    });

    test('a binding document without trigger, condition and action is mandatory', function () {
        config(['knowledge.retrieval.budget.core' => 100]);
        knowledgeBase(retrPolicy(1, ['retrieval' => 'binding'], str_repeat('a', 300)));

        expect(app(MandatoryScope::class)->violations())->not->toBeEmpty();
    });

    test('product knowledge has its own budget', function () {
        config(['knowledge.retrieval.budget.core_per_product' => 100]);
        knowledgeBase(['products/magic-mug.md' => knowledgeDoc(['id' => 'PRODUCT-001', 'type' => 'product', 'title' => 'Tasse', 'retrieval' => 'core', 'summary' => 'x'], str_repeat('a', 300))]);

        expect(implode("\n", app(MandatoryScope::class)->violations()))->toContain('Produkt magic-mug')->toContain('Pflichtwissen zum Produkt umfasst 300 Zeichen (Grenze 100)');
    });

    test('the check command reports the violation as an error and the unclassified documents', function () {
        config(['knowledge.retrieval.budget.core' => 100]);
        knowledgeBase(retrPolicy(1, ['retrieval' => 'core'], str_repeat('a', 300)) + retrPolicy(2, [], 'Ein Satz.'));

        $this->artisan('knowledge:check')
            ->expectsOutputToContain('Noch nicht klassifiziert (retrieval fehlt): 1 Dokument')
            ->expectsOutputToContain('Pflichtwissen ohne Produktbezug umfasst 300 Zeichen')
            ->assertFailed();
    });

    test('the check command stays green for a small, classified base', function () {
        knowledgeBase(retrPolicy(1, ['retrieval' => 'core'], 'Ein Satz.'));

        $this->artisan('knowledge:check')->expectsOutputToContain('Keine Fehler, keine Warnungen.')->assertSuccessful();
    });
});

describe('the commands for the author', function () {
    test('select with a text shows why each document was retrieved or not, and the size', function () {
        knowledgeBase(retrPolicy(1, ['retrieval' => 'core']) + retrPolicy(2, ['retrieval' => 'triggered', 'triggers' => ['Urheberrecht']]) + retrPolicy(3, ['retrieval' => 'triggered', 'triggers' => ['Rechnung']]));

        $this->artisan('knowledge:select', ['group' => 'reseller', '--text' => 'Ist das Urheberrecht geklärt?'])
            ->expectsOutputToContain('Abgerufen: 2 Dokumente')
            ->expectsOutputToContain('Auslöser „Urheberrecht“ (aktuelles Anliegen)')
            ->expectsOutputToContain('Geprüft, nicht abgerufen: 1 Dokument')
            ->expectsOutputToContain('kein Auslöser im Anliegen')
            ->assertSuccessful();
    });

    test('select without a text behaves as before', function () {
        knowledgeBase(retrPolicy(1, ['retrieval' => 'triggered', 'triggers' => ['Urheberrecht']]));

        $this->artisan('knowledge:select', ['group' => 'reseller'])->expectsOutputToContain('Ausgewählt: 1 Dokument')->assertSuccessful();
    });

    test('the overview lists core knowledge with size, triggers with multiple use, the when keys and unclassified documents', function () {
        knowledgeBase(retrPolicy(1, ['retrieval' => 'core'], str_repeat('a', 120)) + retrPolicy(2, ['retrieval' => 'triggered', 'triggers' => ['Erstattung']]) + retrPolicy(3, ['retrieval' => 'triggered', 'triggers' => ['erstattung']]) + retrPolicy(4, []));

        Artisan::call('knowledge:overview');

        expect(Artisan::output())
            ->toContain('Grundwissen (retrieval: core)')
            ->toContain('POLICY-001 – Regel 1 (120 Zeichen)')
            ->toContain('erstattung → POLICY-002, POLICY-003 (mehrfach)')
            ->toContain('days_since_shipped (number) – je Bestellung')
            ->toContain('Noch nicht klassifiziert (retrieval fehlt, zählt vorläufig als Grundwissen): 1');
    });
});
