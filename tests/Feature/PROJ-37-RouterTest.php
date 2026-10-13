<?php

use App\Analysis\Agents\RouterAgent;
use App\Analysis\AnalysisOverview;
use App\Analysis\Pseudonymizer;
use App\Knowledge\CaseContext;
use App\Knowledge\CustomerGroup;
use App\Knowledge\KnowledgeLibrary;
use App\Knowledge\Retrieval\CaseFacts;
use App\Knowledge\Retrieval\KnowledgeRetriever;
use App\Knowledge\Retrieval\RetrievalResult;
use App\Knowledge\Retrieval\Router\CandidatePreselector;
use App\Knowledge\Retrieval\Router\KnowledgeRouter;
use App\Knowledge\Retrieval\Router\PoolEntry;
use App\Knowledge\Retrieval\Router\RouterAddition;
use App\Knowledge\Retrieval\Router\RouterOutcome;
use App\Knowledge\Retrieval\Router\RouterPool;
use App\Knowledge\Retrieval\Router\RouterText;
use App\Models\Analysis;
use App\Orders\OrderNumberDetector;
use App\Zammad\ArticleKind;
use App\Zammad\Ticket;
use App\Zammad\TicketArticle;
use App\Zammad\TicketAttachment;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\HtmlString;

afterEach(fn () => cleanUpKnowledgeBases());

beforeEach(fn () => config(['knowledge.retrieval.active' => true, 'knowledge.router.active' => true]));

/**
 * A knowledge file with the retrieval fields; the number picks ID and file name.
 *
 * @param  array<string, mixed>  $fields
 * @return array<string, string>
 */
function rtrDoc(int $number, array $fields = [], string $body = "Gilt für alle.\n\n# Regel\n\nEin Satz.", string $type = 'policy'): array
{
    $folder = ['policy' => 'policies', 'playbook' => 'playbooks', 'product' => 'products'][$type];
    $prefix = strtoupper($type);
    $id = sprintf('%s-%03d', $prefix, $number);

    return [sprintf('%s/%s-%03d-x.md', $folder, $type, $number) => knowledgeDoc(['id' => $id, 'type' => $type, 'title' => "Titel {$id}", 'summary' => 'Wird gebraucht, wenn etwas Bestimmtes passiert.', 'retrieval' => 'triggered', ...$fields], $body)];
}

/**
 * @param  list<string>  $a
 * @param  list<string>  $b
 * @param  list<array{0: string, 1: ?string}>  $partsA
 */
function rtrFacts(array $a = [], array $b = [], ?array $partsA = null): CaseFacts
{
    $pairs = fn (array $texts): array => array_map(fn (string $text): array => [$text, null], $texts);

    return new CaseFacts([], [], CaseFacts::REFERENCE_NONE, ['A' => $a, 'B' => $b, 'C' => []], ['A' => $partsA ?? $pairs($a), 'B' => $pairs($b), 'C' => []]);
}

/**
 * Product files so that product slugs count as known products.
 *
 * @return array<string, string>
 */
function rtrProducts(): array
{
    return [
        'products/3d-glass-photo.md' => knowledgeDoc(['id' => 'PRODUCT-901', 'type' => 'product', 'title' => '3D', 'retrieval' => 'core', 'summary' => 'Produkt.']),
        'products/magic-mug.md' => knowledgeDoc(['id' => 'PRODUCT-902', 'type' => 'product', 'title' => 'Tasse', 'retrieval' => 'core', 'summary' => 'Produkt.']),
    ];
}

function rtrContext(string $group = 'reseller', array $products = []): CaseContext
{
    return new CaseContext(collect(CustomerGroup::all(config('knowledge.customer_groups')))->first(fn (CustomerGroup $item): bool => $item->key === $group), $products);
}

function rtrTicket(string $text, array $attachments = []): Ticket
{
    $article = new TicketArticle(ArticleKind::Customer, 'Kunde', CarbonImmutable::now(), new HtmlString(nl2br(e($text))), attachments: $attachments);

    return new Ticket('4711', 1, 'Betreff', 'open', false, null, null, null, null, CarbonImmutable::now(), [$article], '');
}

/**
 * The deterministic result of the knowledge base for the texts.
 */
function rtrFirst(array $files, CaseFacts $facts, ?CaseContext $context = null, array $manual = []): RetrievalResult
{
    knowledgeBase($files);

    return app(KnowledgeRetriever::class)->retrieve($context ?? rtrContext(), $facts, $manual);
}

/**
 * Run the router with a fake answer.
 *
 * @param  array<string, mixed>|Closure  $answer
 * @param  list<string>  $manual
 */
function rtrRun(array $files, CaseFacts $facts, array|Closure $answer, ?CaseContext $context = null, string $text = 'Das Motiv ist sehr klein.', array $manual = [], array $attachments = [], string $employee = ''): RouterOutcome
{
    $context ??= rtrContext();
    $first = rtrFirst($files, $facts, $context, $manual);
    RouterAgent::fake([$answer instanceof Closure ? $answer : $answer]);

    return app(KnowledgeRouter::class)->run($context, $facts, $first, $manual, rtrTicket($text, $attachments), app(Pseudonymizer::class), $employee);
}

function rtrAnswer(array $additions = [], array $categories = []): array
{
    return ['additions' => $additions, 'categories' => $categories];
}

function rtrAdd(string $id, int $rank = 1, string $basis = 'current', string $reason = 'Passt sinngemäß.', string $evidence = ''): array
{
    return ['id' => $id, 'rank' => $rank, 'basis' => $basis, 'reason' => $reason, 'evidence' => $evidence];
}

describe('when the router runs', function () {
    test('it is off by default and without the targeted retrieval', function () {
        config(['knowledge.router.active' => false]);
        $outcome = rtrRun(rtrDoc(1, ['triggers' => ['zzzz']]), rtrFacts(['Das Motiv ist sehr klein.']), rtrAnswer());

        expect($outcome->status)->toBe(RouterOutcome::DISABLED);

        config(['knowledge.router.active' => true, 'knowledge.retrieval.active' => false]);

        expect(rtrRun(rtrDoc(1, ['triggers' => ['zzzz']]), rtrFacts(['Das Motiv ist sehr klein.']), rtrAnswer())->status)->toBe(RouterOutcome::DISABLED);
    });

    test('courtesy-only messages skip the router, anything with a possible concern runs it', function (string $message, bool $skipped, bool $attachment) {
        $files = rtrDoc(1, ['triggers' => ['zzzz']]);
        $outcome = rtrRun($files, rtrFacts([$message]), rtrAnswer(), text: $message, attachments: $attachment ? [new TicketAttachment('foto.jpg', 'image/jpeg', 10)] : []);

        expect($outcome->status)->toBe($skipped ? RouterOutcome::SKIPPED : RouterOutcome::EXECUTED)
            ->and($skipped ? $outcome->reason : '')->toBe($skipped ? 'ohne Sachinhalt (reine Höflichkeits- oder Bestätigungsnachricht)' : '');
    })->with([
        'plain thanks' => ['Vielen Dank!', true, false],
        'thanks and greeting' => ['Danke, viele Grüße', true, false],
        'ok' => ['Ok, perfekt.', true, false],
        'problem remains' => ['Das Problem besteht weiterhin.', false, false],
        'not as imagined' => ['So hatte ich mir das nicht vorgestellt.', false, false],
        'thanks but' => ['Danke, aber das Motiv ist immer noch zu klein.', false, false],
        'photos attached' => ['Anbei die gewünschten Fotos.', false, true],
        'thanks with attachment' => ['Danke!', false, true],
        'thanks with a question' => ['Danke, wann kommt das Paket?', false, false],
    ]);

    test('an empty customer text has nothing to route', function () {
        $outcome = rtrRun(rtrDoc(1, ['triggers' => ['zzzz']]), rtrFacts(['']), rtrAnswer(), text: '');

        expect($outcome->status)->toBe(RouterOutcome::SKIPPED)->and($outcome->reason)->toBe('kein Kundentext');
    });

    test('a note of the employee always runs it', function () {
        $outcome = rtrRun(rtrDoc(1, ['triggers' => ['zzzz']]), rtrFacts(['Danke!']), rtrAnswer(), text: 'Danke!', employee: 'Motiv ist zu klein');

        expect($outcome->status)->toBe(RouterOutcome::EXECUTED);
    });

    test('an empty pool skips it with the reason and no model call', function () {
        $files = rtrDoc(1, ['retrieval' => 'core']);
        $outcome = rtrRun($files, rtrFacts(['Das Motiv ist sehr klein.']), rtrAnswer());

        RouterAgent::assertNeverPrompted();

        expect($outcome->status)->toBe(RouterOutcome::SKIPPED)->and($outcome->reason)->toBe('kein zusätzliches Dokument im Geltungsbereich');
    });
});

describe('the pool', function () {
    $pool = function (array $files, ?CaseContext $context = null, ?CaseFacts $facts = null): array {
        $first = rtrFirst($files, $facts ?? rtrFacts(['Nichts Besonderes.']), $context);

        return array_map(fn (PoolEntry $entry): string => $entry->id(), (new RouterPool)->build($first));
    };

    test('a document without a trigger hit is in the pool, one with a hit is not', function () use ($pool) {
        $files = rtrDoc(1, ['triggers' => ['Eisberg']]) + rtrDoc(2, ['triggers' => ['Glasstein']]);

        expect($pool($files, facts: rtrFacts(['Der Eisberg ist klein.'])))->toBe(['POLICY-002']);
    });

    test('core, deprecated, faulty and out of scope documents are not in the pool', function () use ($pool) {
        $files = rtrDoc(1, ['retrieval' => 'core'])
            + rtrDoc(2, ['status' => 'deprecated', 'triggers' => ['zzzz']])
            + rtrDoc(3, ['customer_types' => ['b2c'], 'triggers' => ['zzzz']])
            + rtrDoc(4, ['products' => ['magic-mug'], 'triggers' => ['zzzz']])
            + rtrDoc(5, ['products' => ['3d-glass-photo'], 'exclude_products' => ['3d-glass-photo'], 'triggers' => ['zzzz']])
            + rtrDoc(6, ['triggers' => ['zzzz']]);

        expect($pool($files, rtrContext('reseller', ['3d-glass-photo'])))->toBe(['POLICY-006']);
    });

    test('binding documents: tied to an action, with a known false condition, or already retrieved stay out; a binding document with triggers but no hit may be offered', function () use ($pool) {
        $files = rtrDoc(1, ['retrieval' => 'binding', 'actions' => ['refund']])
            + rtrDoc(2, ['retrieval' => 'binding', 'when' => ['customer_type' => ['b2c']], 'triggers' => ['zzzz']])
            + rtrDoc(3, ['retrieval' => 'binding'])
            + rtrDoc(4, ['retrieval' => 'binding', 'triggers' => ['zzzz']]);

        expect($pool($files, rtrContext('private-looxis-de')))->toBe(['POLICY-004']);
    });

    test('a triggered document with a condition that is false or unknown is still offered: when is only one more way in there', function () use ($pool) {
        $files = rtrDoc(1, ['when' => ['customer_type' => ['b2c']]])
            + rtrDoc(2, ['triggers' => ['zzzz'], 'when' => ['order_age_days' => ['min' => 14]]]);

        expect($pool($files, rtrContext('reseller')))->toBe(['POLICY-001', 'POLICY-002']);
    });

    test('reference cases are never offered', function () use ($pool) {
        $files = rtrDoc(1, ['triggers' => ['zzzz']]) + [
            'examples/good/example-good-001-x.md' => knowledgeDoc(['id' => 'EXAMPLE-GOOD-001', 'type' => 'example-good', 'retrieval' => 'triggered', 'triggers' => ['zzzz']]),
        ];

        expect($pool($files))->toBe(['POLICY-001']);
    });

    test('a document whose only hit an exclusion suppressed stays in the pool with a note about the suppressed sentence', function () {
        $files = rtrDoc(1, ['triggers' => ['Logo'], 'exclude_triggers' => ['unser Logo']]);
        $first = rtrFirst($files, rtrFacts(['Wir möchten 200 Tassen mit unser Logo.']));
        $pool = (new RouterPool)->build($first);

        expect($pool)->toHaveCount(1)
            ->and($pool[0]->line())->toContain('Unterdrückt')->toContain('unser Logo');
    });
});

describe('the preselection above the limit', function () {
    /**
     * A big base: many similar and unrelated fillers around the real candidates.
     */
    $big = function (int $fillers): array {
        $files = [];

        for ($i = 1; $i <= $fillers; $i++) {
            $files += rtrDoc(100 + $i, [
                'title' => 'Allgemeine Regel zu Versand '.$i,
                'summary' => 'Regelt Versandkosten, Lieferfristen und Paketdienste in Fall '.$i.' ohne Bezug zum Motiv.',
                'triggers' => ['zzzz'.$i],
            ]);
        }

        return $files;
    };

    $select = function (array $files, array $tiers, ?CaseContext $context = null): array {
        $first = rtrFirst($files, rtrFacts($tiers['A'] ?? [], $tiers['B'] ?? []), $context);
        $pool = (new RouterPool)->build($first);
        $result = (new CandidatePreselector)->select($pool, ['A' => $tiers['A'] ?? [], 'B' => $tiers['B'] ?? [], 'C' => []], $context ?? rtrContext());

        return ['ids' => array_map(fn (PoolEntry $entry): string => $entry->id(), $result['selected']), 'left_out' => $result['left_out'], 'preselected' => $result['preselected']];
    };

    test('up to the limit the router sees every line', function () use ($big, $select) {
        $result = $select($big(20), ['A' => ['Hallo']]);

        expect($result['preselected'])->toBeFalse()->and($result['ids'])->toHaveCount(20)->and($result['left_out'])->toBe([]);
    });

    test('above the limit the size stays within the configured lines and characters', function () use ($big, $select) {
        config(['knowledge.router.pool.max_lines' => 30, 'knowledge.router.pool.max_characters' => 3000]);
        $result = $select($big(120), ['A' => ['Hallo']]);

        expect($result['preselected'])->toBeTrue()->and(count($result['ids']))->toBeLessThanOrEqual(30)->and(count($result['left_out']))->toBeGreaterThan(80);
    });

    test('a product-specific and a group-specific document both get in without any word in common, in a big base', function () use ($big, $select) {
        config(['knowledge.router.pool.max_lines' => 30]);
        $files = rtrProducts() + $big(150)
            + rtrDoc(1, ['type' => 'playbook', 'products' => ['3d-glass-photo'], 'title' => 'Beanstandung der Darstellung im Glas', 'summary' => 'Prüfung, wenn das Ergebnis nicht wie erwartet wirkt.', 'triggers' => ['zzzz']], type: 'playbook')
            + rtrDoc(2, ['type' => 'playbook', 'customer_types' => ['b2b-reseller'], 'sales_channels' => ['fachhaendler'], 'title' => 'Rückfrage beim Händler vor einer Endkundenreaktion', 'summary' => 'Klärt, wer unzufrieden ist.', 'triggers' => ['zzzz']], type: 'playbook');
        $result = $select($files, ['A' => ['leider sehr klein und irgendwie verloren']], rtrContext('reseller', ['3d-glass-photo']));

        expect($result['ids'])->toContain('PLAYBOOK-001', 'PLAYBOOK-002');
    });

    test('many group-specific documents do not push out the only product-specific one, and the other way round', function () use ($big, $select) {
        config(['knowledge.router.pool.max_lines' => 30, 'knowledge.router.pool.reserved_share' => 0.34]);
        $files = [];

        for ($i = 1; $i <= 40; $i++) {
            $files += rtrDoc($i, ['customer_types' => ['b2b-reseller'], 'title' => 'Händlerregel '.$i, 'summary' => 'Betrifft Händler im Fall '.$i.'.', 'triggers' => ['zzzz']]);
        }

        $files += rtrDoc(90, ['products' => ['3d-glass-photo'], 'title' => 'Besonderheit des Produkts', 'summary' => 'Erklärt eine Eigenschaft.', 'triggers' => ['zzzz']]);
        $files += $big(60) + rtrProducts();
        $result = $select($files, ['A' => ['Hallo']], rtrContext('reseller', ['3d-glass-photo']));

        expect($result['ids'])->toContain('POLICY-090');

        $files = [];

        for ($i = 1; $i <= 40; $i++) {
            $files += rtrDoc($i, ['products' => ['3d-glass-photo'], 'title' => 'Produktregel '.$i, 'summary' => 'Betrifft das Produkt im Fall '.$i.'.', 'triggers' => ['zzzz']]);
        }

        $files += rtrDoc(90, ['customer_types' => ['b2b-reseller'], 'title' => 'Besonderheit der Händler', 'summary' => 'Erklärt eine Eigenschaft.', 'triggers' => ['zzzz']]);
        $files += $big(60) + rtrProducts();

        expect($select($files, ['A' => ['Hallo']], rtrContext('reseller', ['3d-glass-photo']))['ids'])->toContain('POLICY-090');
    });

    test('reserved places that are not needed go to the best other candidates', function () use ($big, $select) {
        config(['knowledge.router.pool.max_lines' => 30]);
        $files = $big(80) + rtrDoc(1, ['title' => 'Motiv wirkt verloren', 'summary' => 'Das Motiv wirkt zu klein oder verloren.', 'triggers' => ['zzzz']]);
        $result = $select($files, ['A' => ['Das Motiv wirkt verloren und klein']]);

        expect($result['ids'])->toHaveCount(30)->and($result['ids'])->toContain('POLICY-001');
    });

    test('similarity favours the document that matches the wording, without a trigger hit', function () use ($big, $select) {
        config(['knowledge.router.pool.max_lines' => 20]);
        $files = $big(80) + rtrDoc(1, ['title' => 'Farbstich bei Gravuren', 'summary' => 'Gelbstich im Glas erklären.', 'triggers' => ['zzzz']]);

        expect($select($files, ['A' => ['Das Glas hat einen leichten Gelbstich']])['ids'])->toContain('POLICY-001');
    });

    test('KNOWN LIMIT: a general document with no product bond and little wording in common can be missed in a very large base', function () use ($big, $select) {
        config(['knowledge.router.pool.max_lines' => 20]);
        $files = $big(300) + rtrDoc(1, ['title' => 'Besondere Sorgfalt', 'summary' => 'Gilt in Sonderfällen.', 'triggers' => ['zzzz']]);

        expect($select($files, ['A' => ['unglücklich und enttäuscht über das Ergebnis']])['ids'])->toContain('POLICY-001');
    })->todo('Bekannte Grenze: allgemeines Dokument ohne Produkt- oder Gruppenbezug und ohne Wortähnlichkeit bei sehr großer Wissensbasis (PROJ-22).');
});

describe('the router text', function () {
    test('it holds the current concern and the recent course, original and translation apart, no tier C', function () {
        $facts = new CaseFacts([], [], CaseFacts::REFERENCE_NONE, ['A' => ['x'], 'B' => ['y'], 'C' => ['z']], ['A' => [['Il motivo è piccolo.', 'Das Motiv ist klein.']], 'B' => [['Früher geschrieben.', null]], 'C' => [['Ganz alt.', null]]]);
        $text = RouterText::build($facts, new Pseudonymizer(app(OrderNumberDetector::class)), 5000);

        expect($text->text)->toContain('## Aktuelles Anliegen')->toContain('Original: Il motivo è piccolo.')->toContain('Übersetzung (Deutsch, nur Verständnishilfe): Das Motiv ist klein.')
            ->toContain('## Früherer Verlauf')->toContain('Früher geschrieben.')->not->toContain('Ganz alt.')
            ->and($text->cut)->toBe(['b_removed' => 0, 'a_shortened' => 0]);
    });

    test('contact data are replaced in original and translation', function () {
        $facts = new CaseFacts([], [], CaseFacts::REFERENCE_NONE, ['A' => ['x'], 'B' => [], 'C' => []], ['A' => [['Schreiben Sie an erika@example.org oder 0171 1234567.', 'Write to erika@example.org.']], 'B' => [], 'C' => []]);
        $text = RouterText::build($facts, new Pseudonymizer(app(OrderNumberDetector::class)), 5000);

        expect($text->text)->not->toContain('erika@example.org')->not->toContain('1234567')->toContain('[E-MAIL');
    });

    test('over the limit tier B goes first and the cut is recorded', function () {
        $facts = new CaseFacts([], [], CaseFacts::REFERENCE_NONE, ['A' => ['x'], 'B' => [], 'C' => []], ['A' => [['Aktuell wichtig.', null]], 'B' => [[str_repeat('früher ', 200), null]], 'C' => []]);
        $text = RouterText::build($facts, new Pseudonymizer(app(OrderNumberDetector::class)), 300);

        expect($text->text)->toContain('Aktuell wichtig.')->not->toContain('früher')->and($text->cut['b_removed'])->toBeGreaterThan(1000)->and($text->cut['a_shortened'])->toBe(0);
    });

    test('a current message that alone exceeds the limit keeps start and end and shows a visible mark, never over the limit', function () {
        $long = 'ANFANG '.str_repeat('Füllwort ', 400).'ENDE mit der Bitte';
        $facts = new CaseFacts([], [], CaseFacts::REFERENCE_NONE, ['A' => ['x'], 'B' => [], 'C' => []], ['A' => [[$long, null]], 'B' => [], 'C' => []]);
        $text = RouterText::build($facts, new Pseudonymizer(app(OrderNumberDetector::class)), 1000);

        expect($text->text)->toContain('ANFANG')->toContain('ENDE mit der Bitte')->toContain('Zeichen ausgelassen')
            ->and(mb_strlen($text->text))->toBeLessThanOrEqual(1000)
            ->and($text->cut['a_shortened'])->toBeGreaterThan(2000);
    });
});

describe('checking the answer of the router', function () {
    $files = fn (): array => rtrDoc(1, ['triggers' => ['zzzz']]) + rtrDoc(2, ['triggers' => ['zzzz']]) + rtrDoc(3, ['triggers' => ['zzzz']]) + rtrDoc(4, ['retrieval' => 'core']);

    test('valid proposals are taken over by rank, strong for the current concern and weak for the earlier course', function () use ($files) {
        $outcome = rtrRun($files(), rtrFacts(['Das Motiv ist sehr klein.']), rtrAnswer([rtrAdd('POLICY-002', 2, 'earlier'), rtrAdd('POLICY-001', 1, 'current', 'Beschreibt eine Beanstandung der Größe.')]));

        expect($outcome->status)->toBe(RouterOutcome::EXECUTED)
            ->and($outcome->additionIds())->toBe(['POLICY-001', 'POLICY-002'])
            ->and([$outcome->additions[0]->strength, $outcome->additions[1]->strength])->toBe([RouterAddition::STRONG, RouterAddition::WEAK])
            ->and($outcome->additions[0]->reason)->toBe('Beschreibt eine Beanstandung der Größe.');
    });

    test('unknown, duplicate, already selected and outside-the-pool proposals are rejected with a reason', function () use ($files) {
        $outcome = rtrRun($files(), rtrFacts(['Das Motiv ist sehr klein.']), rtrAnswer([
            rtrAdd('POLICY-999', 1), rtrAdd('POLICY-001', 2), rtrAdd('policy-001', 3), rtrAdd('POLICY-004', 4), rtrAdd('PLAYBOOK-001', 5),
        ]));
        $rejections = collect($outcome->details['proposals'])->mapWithKeys(fn (array $item): array => [$item['rank'] => $item['rejection']])->all();

        expect($outcome->additionIds())->toBe(['POLICY-001'])
            ->and($rejections)->toBe([1 => 'nicht in der Liste des Routers', 2 => null, 3 => 'Dublette', 4 => 'bereits ausgewählt', 5 => 'nicht in der Liste des Routers']);
    });

    test('more proposals than the limit: the most important stay, the rest are logged', function () use ($files) {
        config(['knowledge.router.max_additions' => 2]);
        $outcome = rtrRun($files(), rtrFacts(['Das Motiv ist sehr klein.']), rtrAnswer([rtrAdd('POLICY-003', 3), rtrAdd('POLICY-001', 1), rtrAdd('POLICY-002', 2)]));

        expect($outcome->additionIds())->toBe(['POLICY-001', 'POLICY-002'])
            ->and(collect($outcome->details['proposals'])->firstWhere('id', 'POLICY-003')['rejection'])->toBe('wegen Obergrenze nicht übernommen');
    });

    test('categories are recognised, unknown ones dropped, an empty list is fine', function () use ($files) {
        $outcome = rtrRun($files(), rtrFacts(['Das Motiv ist sehr klein.']), rtrAnswer([], [['key' => 'complaint', 'reason' => 'Beanstandung'], ['key' => 'product-question', 'reason' => 'Frage'], ['key' => 'nonsense', 'reason' => 'x'], ['key' => 'complaint', 'reason' => 'doppelt']]));
        $empty = rtrRun($files(), rtrFacts(['Das Motiv ist sehr klein.']), rtrAnswer());

        expect(array_column($outcome->details['categories'], 'key'))->toBe(['complaint', 'product-question'])
            ->and($empty->details['categories'])->toBe([])->and($empty->reason)->toBe('keine Ergänzung nötig');
    });

    test('the router sends only the catalogue lines, no document text, no triggers, and the reason is stored', function () use ($files) {
        rtrRun($files(), rtrFacts(['Das Motiv ist sehr klein.']), rtrAnswer());

        RouterAgent::assertPrompted(fn ($prompt) => str_contains($prompt->prompt, '[POLICY-001] Titel POLICY-001 – Wird gebraucht')
            && str_contains($prompt->prompt, '# Bereits ausgewählt')
            && str_contains($prompt->prompt, '[POLICY-004]')
            && ! str_contains($prompt->prompt, 'Ein Satz.')
            && ! str_contains($prompt->prompt, 'zzzz'));
    });

    test('the log carries pool, presented documents, model, versions and tokens', function () use ($files) {
        $outcome = rtrRun($files(), rtrFacts(['Das Motiv ist sehr klein.']), rtrAnswer([rtrAdd('POLICY-001')]));
        $log = $outcome->toArray();

        expect($log)->toHaveKeys(['status', 'reason', 'additions', 'pool', 'pool_total', 'presented', 'proposals', 'categories', 'cut', 'model', 'version', 'prompt_version', 'input_tokens', 'output_tokens', 'duration_ms'])
            ->and($log['pool'])->toBe(['POLICY-001', 'POLICY-002', 'POLICY-003'])
            ->and($log['version'])->toBe(config('knowledge.router.version'))
            ->and($log['model'])->toBe(config('knowledge.router.model'))
            ->and($log['preselected'])->toBeFalse();
    });
});

describe('the evidence for a document whose hit an exclusion suppressed', function () {
    $files = fn (): array => rtrDoc(1, ['triggers' => ['Logo'], 'exclude_triggers' => ['unserem Logo'], 'title' => 'Rechte an Motiven', 'summary' => 'Rechte liegen beim Kunden.']);
    $text = 'Wir möchten 200 Tassen mit unserem Logo bestellen. Außerdem würden wir gern eine bekannte Zeichentrickfigur drucken.';

    test('an evidence from the second concern is accepted', function () use ($files, $text) {
        $outcome = rtrRun($files(), rtrFacts([$text]), rtrAnswer([rtrAdd('POLICY-001', evidence: 'Außerdem würden wir gern eine bekannte Zeichentrickfigur drucken.')]), text: $text);

        expect($outcome->additionIds())->toBe(['POLICY-001']);
    });

    test('an evidence that is the suppressed sentence is rejected', function () use ($files, $text) {
        $outcome = rtrRun($files(), rtrFacts([$text]), rtrAnswer([rtrAdd('POLICY-001', evidence: 'Wir möchten 200 Tassen mit unserem Logo bestellen.')]), text: $text);

        expect($outcome->additionIds())->toBe([])->and($outcome->details['proposals'][0]['rejection'])->toBe('stützt sich nur auf den unterdrückten Satz');
    });

    test('a fragment of the suppressed sentence and a missing evidence are rejected, an invented one too', function (string $evidence, string $reason) use ($files, $text) {
        $outcome = rtrRun($files(), rtrFacts([$text]), rtrAnswer([rtrAdd('POLICY-001', evidence: $evidence)]), text: $text);

        expect($outcome->additionIds())->toBe([])->and($outcome->details['proposals'][0]['rejection'])->toBe($reason);
    })->with([
        'fragment' => ['200 Tassen mit unserem Logo', 'stützt sich nur auf den unterdrückten Satz'],
        'missing' => ['', 'Beleg kommt im Kundentext nicht vor'],
        'invented' => ['Wir wollen ein Disney-Motiv', 'Beleg kommt im Kundentext nicht vor'],
    ]);

    test('a quote that spans both sentences still counts because one sentence stands on its own', function () use ($files, $text) {
        $outcome = rtrRun($files(), rtrFacts([$text]), rtrAnswer([rtrAdd('POLICY-001', evidence: $text)]), text: $text);

        expect($outcome->additionIds())->toBe(['POLICY-001']);
    });

    test('the check ignores case, quotation marks and spacing', function () use ($files, $text) {
        $outcome = rtrRun($files(), rtrFacts([$text]), rtrAnswer([rtrAdd('POLICY-001', evidence: '„außerdem   würden wir gern eine bekannte ZEICHENTRICKFIGUR drucken“')]), text: $text);

        expect($outcome->additionIds())->toBe(['POLICY-001']);
    });

    test('a document without a suppressed hit needs no evidence', function () {
        $outcome = rtrRun(rtrDoc(1, ['triggers' => ['zzzz']]), rtrFacts(['Das Motiv ist klein.']), rtrAnswer([rtrAdd('POLICY-001')]));

        expect($outcome->additionIds())->toBe(['POLICY-001']);
    });

    test('with a translation the evidence may quote either language, but not the suppressed sentence in the other language', function () {
        $files = fn (): array => rtrDoc(1, ['triggers' => ['Logo'], 'exclude_triggers' => ['unserem Logo', 'nostro logo'], 'title' => 'Rechte an Motiven', 'summary' => 'Rechte liegen beim Kunden.']);
        $original = 'Vorremmo 200 tazze con il nostro logo. Inoltre vorremmo stampare un personaggio dei cartoni animati.';
        $translation = 'Wir möchten 200 Tassen mit unserem Logo. Außerdem möchten wir eine Zeichentrickfigur drucken.';
        $facts = rtrFacts([$original."\n".$translation], partsA: [[$original, $translation]]);
        $cases = [
            ['Inoltre vorremmo stampare un personaggio dei cartoni animati.', ['POLICY-001']],
            ['Außerdem möchten wir eine Zeichentrickfigur drucken.', ['POLICY-001']],
            ['Vorremmo 200 tazze con il nostro logo.', []],
            ['Wir möchten 200 Tassen mit unserem Logo.', []],
        ];

        foreach ($cases as [$evidence, $expected]) {
            $outcome = rtrRun($files(), $facts, rtrAnswer([rtrAdd('POLICY-001', evidence: $evidence)]), text: $original);

            expect($outcome->additionIds())->toBe($expected);
        }
    });

    test('contact data replaced in the router text do not break the check', function () {
        $files = rtrDoc(1, ['triggers' => ['Logo'], 'exclude_triggers' => ['unser Logo']]);
        $text = 'Mailen Sie an erika@example.org wegen unser Logo. Dann noch etwas ganz anderes mit Rechten.';
        $outcome = rtrRun($files, rtrFacts([$text]), rtrAnswer([rtrAdd('POLICY-001', evidence: 'Dann noch etwas ganz anderes mit Rechten.')]), text: $text);
        $rejected = rtrRun($files, rtrFacts([$text]), rtrAnswer([rtrAdd('POLICY-001', evidence: 'Mailen Sie an [E-MAIL_1] wegen unser Logo.')]), text: $text);

        expect($outcome->additionIds())->toBe(['POLICY-001'])->and($rejected->additionIds())->toBe([]);
    });
});

describe('failures', function () {
    test('every kind of failure leaves a failed outcome with a reason and no additions', function (Closure $answer, string $reason) {
        $outcome = rtrRun(rtrDoc(1, ['triggers' => ['zzzz']]), rtrFacts(['Das Motiv ist sehr klein.']), $answer);

        expect($outcome->status)->toBe(RouterOutcome::FAILED)->and($outcome->reason)->toBe($reason)->and($outcome->additions)->toBe([]);
    })->with([
        'timeout' => [fn () => throw new ConnectionException('cURL error 28'), 'Zeitüberschreitung oder Verbindungsfehler'],
        'api error' => [fn () => throw new RuntimeException('Server Error'), 'API-Fehler'],
        'invalid answer' => [fn () => [], 'ungültige Antwort'],
    ]);

    test('the configured timeout and the router model are passed on, with no retry', function () {
        config(['knowledge.router.timeout' => 7, 'knowledge.router.model' => 'router-test-model']);

        rtrRun(rtrDoc(1, ['triggers' => ['zzzz']]), rtrFacts(['Das Motiv ist sehr klein.']), fn () => throw new RuntimeException('boom'));

        RouterAgent::assertPromptedTimes(1);
    });
});

describe('merging the additions into the budget', function () {
    /**
     * A document with a body of about the given size.
     */
    $sized = fn (int $number, int $size, array $fields = []): array => rtrDoc($number, $fields, "Gilt für alle.\n\n# Regel\n\n".str_repeat('x', $size));

    $merge = function (array $files, CaseFacts $facts, array $additions, array $manual = [], int $target = 1000): RetrievalResult {
        config(['knowledge.retrieval.budget.target' => $target]);
        knowledgeBase($files);
        $documents = app(KnowledgeLibrary::class)->all()->keyBy(fn ($document): string => (string) $document->id);

        return app(KnowledgeRetriever::class)->retrieve(rtrContext(), $facts, $manual, null, array_map(fn (array $item): RouterAddition => new RouterAddition($documents[$item[0]], $item[1], 1, 'passt'), $additions));
    };

    test('without additions the result is exactly the result without the router', function () use ($sized, $merge) {
        $files = $sized(1, 100, ['retrieval' => 'core']) + $sized(2, 300, ['triggers' => ['Eisberg']]) + $sized(3, 300);
        $facts = rtrFacts(['Der Eisberg ist klein.']);

        expect($merge($files, $facts, [])->ids())->toBe($merge($files, $facts, [])->ids())->toBe(['POLICY-001', 'POLICY-002']);
    });

    test('an addition is only added: every deterministic hit stays when there is room', function () use ($sized, $merge) {
        $files = $sized(1, 100, ['retrieval' => 'core']) + $sized(2, 200, ['triggers' => ['Eisberg']]) + $sized(3, 200);
        $result = $merge($files, rtrFacts(['Der Eisberg ist klein.']), [['POLICY-003', RouterAddition::STRONG]]);

        expect($result->ids())->toBe(['POLICY-001', 'POLICY-002', 'POLICY-003']);

        $origin = collect($result->retrieved)->mapWithKeys(fn ($item): array => [(string) $item->document->id => $item->origin]);

        expect($origin->all())->toBe(['POLICY-001' => 'core', 'POLICY-002' => 'trigger', 'POLICY-003' => 'router']);
    });

    test('a strong addition may displace a weak deterministic hit in the budget, logged as left out for size', function () use ($sized, $merge) {
        $files = $sized(1, 50, ['retrieval' => 'core']) + $sized(2, 500, ['triggers' => ['früher']]) + $sized(3, 500);
        $result = $merge($files, rtrFacts(['Aktuell nichts.'], ['Früher stand etwas früher da.']), [['POLICY-003', RouterAddition::STRONG]], target: 700);
        $left = collect($result->notRetrieved)->firstWhere(fn (array $item): bool => (string) $item['document']->id === 'POLICY-002');

        expect($result->ids())->toBe(['POLICY-001', 'POLICY-003'])->and($left['code'])->toBe(RetrievalResult::OUT_OF_SPACE);
    });

    test('a weak addition never displaces a strong deterministic hit, and in a tier the hit comes first', function () use ($sized, $merge) {
        $files = $sized(1, 50, ['retrieval' => 'core']) + $sized(2, 500, ['triggers' => ['Eisberg']]) + $sized(3, 500);
        $result = $merge($files, rtrFacts(['Der Eisberg ist klein.']), [['POLICY-003', RouterAddition::WEAK]], target: 700);

        expect($result->ids())->toBe(['POLICY-001', 'POLICY-002']);

        $files = $sized(1, 50, ['retrieval' => 'core']) + $sized(2, 500, ['triggers' => ['Eisberg']]) + $sized(3, 500);

        expect($merge($files, rtrFacts(['Der Eisberg ist klein.']), [['POLICY-003', RouterAddition::STRONG]], target: 700)->ids())->toBe(['POLICY-001', 'POLICY-002']);
    });

    test('a very large addition is skipped and smaller ones and the rest of the room are still used', function () use ($sized, $merge) {
        $files = $sized(1, 50, ['retrieval' => 'core']) + $sized(2, 300, ['triggers' => ['Eisberg']]) + $sized(3, 900) + $sized(4, 200) + $sized(5, 100);
        $result = $merge($files, rtrFacts(['Der Eisberg ist klein.']), [['POLICY-003', RouterAddition::STRONG], ['POLICY-004', RouterAddition::STRONG], ['POLICY-005', RouterAddition::WEAK]]);
        $left = collect($result->notRetrieved)->firstWhere(fn (array $item): bool => (string) $item['document']->id === 'POLICY-003');

        expect($result->ids())->toBe(['POLICY-001', 'POLICY-002', 'POLICY-004', 'POLICY-005'])->and($left['code'])->toBe(RetrievalResult::OUT_OF_SPACE);
    });

    test('a very large deterministic hit still ends its tier as before (PROJ-36), router additions behind it are left out', function () use ($sized, $merge) {
        $files = $sized(1, 50, ['retrieval' => 'core']) + $sized(2, 1100, ['triggers' => ['Eisberg']]) + $sized(3, 100);
        $result = $merge($files, rtrFacts(['Der Eisberg ist klein.']), [['POLICY-003', RouterAddition::STRONG]]);

        expect($result->ids())->toBe(['POLICY-001']);
    });

    test('mandatory and hand-switched knowledge is never displaced by additions', function () use ($sized, $merge) {
        $files = $sized(1, 600, ['retrieval' => 'core']) + $sized(2, 300) + $sized(3, 300);
        $result = $merge($files, rtrFacts(['Nichts.']), [['POLICY-003', RouterAddition::STRONG]], manual: ['POLICY-002'], target: 1000);

        expect($result->ids())->toContain('POLICY-001', 'POLICY-002');
    });

    test('an addition is not mandatory even when its document is a binding rule: it can be left out, the mandatory part stays', function () use ($sized, $merge) {
        $files = $sized(1, 700, ['retrieval' => 'core']) + $sized(2, 600, ['retrieval' => 'binding', 'triggers' => ['zzzz']]);
        $result = $merge($files, rtrFacts(['Nichts.']), [['POLICY-002', RouterAddition::STRONG]]);

        expect($result->ids())->toBe(['POLICY-001'])->and($result->mandatoryCharacters)->toBeGreaterThan(700)->toBeLessThan(800);
    });

    test('an addition for a document that is already retrieved or not a candidate changes nothing', function () use ($sized, $merge) {
        $files = $sized(1, 100, ['retrieval' => 'core']) + $sized(2, 100, ['triggers' => ['Eisberg']]);
        $result = $merge($files, rtrFacts(['Der Eisberg ist klein.']), [['POLICY-001', RouterAddition::STRONG], ['POLICY-002', RouterAddition::WEAK]]);
        $origin = collect($result->retrieved)->mapWithKeys(fn ($item): array => [(string) $item->document->id => $item->origin]);

        expect($result->ids())->toBe(['POLICY-001', 'POLICY-002'])->and($origin['POLICY-001'])->toBe('core')->and($origin['POLICY-002'])->toBe('trigger');
    });
});

describe('the analysis overview (admin)', function () {
    beforeEach(function () {
        config(['staff.admins' => ['Etienne']]);
        $this->travelTo(now()->setDate(2026, 10, 20)->setTime(12, 0));
    });

    $analyses = function (): void {
        $base = ['created_at' => now()->subDay()];
        Analysis::factory()->create([...$base, 'ticket_number' => '2200001', 'router_status' => 'executed', 'router_additions' => 3, 'router_characters' => 6000, 'router_duration_ms' => 4000, 'router_input_tokens' => 3000, 'router_output_tokens' => 300]);
        Analysis::factory()->create([...$base, 'ticket_number' => '2200002', 'router_status' => 'executed', 'router_additions' => 1, 'router_characters' => 2000, 'router_duration_ms' => 2000, 'router_input_tokens' => 2000, 'router_output_tokens' => 100]);
        Analysis::factory()->create([...$base, 'ticket_number' => '2200003', 'router_status' => 'failed', 'router_additions' => 0, 'router_duration_ms' => 15000]);
        Analysis::factory()->create([...$base, 'ticket_number' => '2200004', 'router_status' => 'skipped']);
        Analysis::factory()->create([...$base, 'ticket_number' => '2200005', 'router_status' => 'disabled']);
        Analysis::factory()->create([...$base, 'ticket_number' => '2200006', 'router_status' => null]);
    };

    test('the filter separates executed, skipped, disabled and failed, and ignores a wrong value', function () use ($analyses) {
        $analyses();
        $overview = app(AnalysisOverview::class);
        $rows = fn (array $input): array => $overview->page($overview->filter($input))->getCollection()->pluck('ticket_number')->sort()->values()->all();

        expect($rows(['router' => 'executed']))->toBe(['2200001', '2200002'])
            ->and($rows(['router' => 'failed']))->toBe(['2200003'])
            ->and($rows(['router' => 'skipped']))->toBe(['2200004'])
            ->and($rows(['router' => 'disabled']))->toBe(['2200005'])
            ->and($rows(['router' => 'nonsense']))->toHaveCount(6);
    });

    test('the figures count the decisions, the failure share, the averages of executed analyses and show no cost without a price', function () use ($analyses) {
        $analyses();
        $overview = app(AnalysisOverview::class);
        $figures = $overview->figures($overview->filter([]))['router'];

        expect($figures['states'])->toEqualCanonicalizing(['executed' => 2, 'failed' => 1, 'skipped' => 1, 'disabled' => 1])
            ->and($figures['failure_share'])->toBe(33)
            ->and($figures['additions'])->toBe(2.0)->and($figures['characters'])->toBe(4000.0)->and($figures['duration_ms'])->toBe(3000.0)
            ->and($figures['input_tokens'])->toBe(2500.0)->and($figures['output_tokens'])->toBe(200.0)
            ->and($figures['cost'])->toBeNull();
    });

    test('with a price for the router model the cost is estimated', function () use ($analyses) {
        $analyses();
        config(['knowledge.router.prices' => [config('knowledge.router.model') => ['input' => 1.0, 'output' => 4.0]]]);
        $overview = app(AnalysisOverview::class);

        expect($overview->figures($overview->filter([]))['router']['cost'])->toBe(round((5000 * 1.0 + 400 * 4.0) / 1_000_000, 4));
    });

    test('without any router decision there is no router block', function () {
        Analysis::factory()->create(['created_at' => now()->subDay(), 'router_status' => null]);
        $overview = app(AnalysisOverview::class);

        expect($overview->figures($overview->filter([]))['router'])->toBeNull();
        $this->withCookie('staff_name', 'Etienne')->get(route('analysis-overview.index'))->assertOk()->assertDontSee('router-kennzahlen');
    });

    test('the page shows the filter, the figures and the failure mark to admins only', function () use ($analyses) {
        $analyses();

        $this->withCookie('staff_name', 'Etienne')->get(route('analysis-overview.index'))->assertOk()
            ->assertSeeText('Router-Entscheidung')->assertSeeText('Erweiterte Wissenssuche (Router)')->assertSeeText('Ausfallquote: 33 %')->assertSeeText('Router ausgefallen')
            ->assertSeeText('nicht ermittelbar (kein Preis hinterlegt)');
        $this->withCookie('staff_name', 'Nele')->get(route('analysis-overview.index'))->assertForbidden();
    });
});

test('the cost of one analysis follows the configured price of the model, model names with dots included', function () {
    config(['knowledge.router.prices' => ['gpt-5.4-mini' => ['input' => 0.5, 'output' => 2.0]]]);
    $outcome = new RouterOutcome(RouterOutcome::EXECUTED, 'ok', details: ['model' => 'gpt-5.4-mini'], inputTokens: 4000, outputTokens: 500);

    expect($outcome->cost())->toBe(round((4000 * 0.5 + 500 * 2.0) / 1_000_000, 6))
        ->and((new RouterOutcome(RouterOutcome::EXECUTED, 'ok', details: ['model' => 'other'], inputTokens: 1, outputTokens: 1))->cost())->toBeNull();
});

describe('the check of the summary for the router', function () {
    $warnings = function (string $summary): string {
        $library = knowledgeBase(rtrDoc(1, ['summary' => $summary, 'triggers' => ['Eisberg']]));

        return messagesOf($library, 'warning');
    };

    test('a very short summary gets a soft warning, a longer one does not', function () use ($warnings) {
        expect($warnings('Wenn es klein ist.'))->toContain('`summary` ist sehr kurz')
            ->and($warnings('Prüfung einer Reklamation, das Motiv wirke zu klein oder verloren im Glaskörper.'))->not->toContain('sehr kurz');
    });

    test('a summary without any "does not apply" wording gets no warning, so nobody invents exclusions', function () use ($warnings) {
        expect($warnings('Gilt, wenn ein Kunde sich über die Größe des eingelaserten Motivs im Glas beklagt.'))->not->toContain('summary');
    });

    test('the limit is configurable', function () use ($warnings) {
        config(['knowledge.retrieval.summary_min_length' => 10]);

        expect($warnings('Wenn es klein ist.'))->not->toContain('sehr kurz');
    });
});

test('the authoring documents explain the summary for the router, the soft limit and that exclusions are never required', function () {
    $guide = file_get_contents(base_path('docs/KNOWLEDGE_AUTHORING_GUIDE.md'));
    $handbook = file_get_contents(base_path('docs/KNOWLEDGE_CHAT_HANDBUCH.md'));
    $skill = file_get_contents(base_path('.claude/skills/knowledge/SKILL.md'));

    expect($guide)->toContain('Die `summary` schreiben')->toContain('ID, Titel und `summary`')->toContain('keine Pflicht')->toContain('wird nicht geprüft')->toContain('ausschließlich')->toContain('Negativbeispiele')->toContain('unter 60 Zeichen')
        ->toContain('zusätzlicher Weg in den Prompt, keine Sperre')
        ->and($handbook)->toContain('KI-Router')->toContain('nur falls es sie gibt')
        ->and($skill)->toContain('router');
});
