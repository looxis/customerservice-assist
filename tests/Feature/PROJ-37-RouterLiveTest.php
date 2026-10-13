<?php

use App\Analysis\Pseudonymizer;
use App\Knowledge\CaseContext;
use App\Knowledge\CustomerGroup;
use App\Knowledge\KnowledgeLibrary;
use App\Knowledge\Retrieval\CaseFactsBuilder;
use App\Knowledge\Retrieval\KnowledgeRetriever;
use App\Knowledge\Retrieval\Router\KnowledgeRouter;
use App\Zammad\ArticleKind;
use App\Zammad\Ticket;
use App\Zammad\TicketArticle;
use Carbon\CarbonImmutable;
use Illuminate\Support\HtmlString;

/*
| Live check set of the router (PROJ-37): the real model, the real knowledge base, on demand only.
|
|   ./vendor/bin/sail pest --group=router-live
|
| It is left out of the normal run (phpunit.xml). It needs the API key and costs a few cents. Every run writes a
| report to storage/app/router-live/ with model, versions, knowledge state, expected and actual IDs, the selection of
| PROJ-36 beside the one of PROJ-37, tokens, time and cost. Only invented or anonymous texts are used.
*/

/**
 * @return array<string, array<string, mixed>>
 */
function routerLiveCases(): array
{
    return require dirname(__DIR__).'/Fixtures/router-live-cases.php';
}

/**
 * The report of this run, filled by the cases and written at the end.
 *
 * @param  array<string, mixed>|null  $entry
 * @return array<string, mixed>
 */
function routerLiveReport(?array $entry = null): array
{
    static $report = ['header' => [], 'cases' => []];

    if (isset($entry['header'])) {
        $report['header'] = $entry['header'];
    } elseif ($entry !== null) {
        $report['cases'][] = $entry;
    }

    return $report;
}

/**
 * One run of a case: the selection of PROJ-36 alone and the final selection with the router.
 *
 * @param  array<string, mixed>  $case
 * @return array<string, mixed>
 */
function routerLiveRun(array $case): array
{
    $group = collect(CustomerGroup::all(config('knowledge.customer_groups')))->first(fn (CustomerGroup $item): bool => $item->key === $case['group']);
    $context = new CaseContext($group, $case['products']);
    $article = new TicketArticle(ArticleKind::Customer, 'Kunde', CarbonImmutable::now(), new HtmlString(nl2br(e($case['text']))), id: 1);
    $ticket = new Ticket('0', 0, '', 'open', false, null, null, null, null, CarbonImmutable::now(), [$article], '');
    $translations = isset($case['translation']) ? [1 => ['text' => $case['translation']]] : [];
    $facts = app(CaseFactsBuilder::class)->build($ticket, $group, $case['products'], [], translations: $translations);
    $retriever = app(KnowledgeRetriever::class);
    $first = $retriever->retrieve($context, $facts);
    $router = app(KnowledgeRouter::class)->run($context, $facts, $first, [], $ticket, app(Pseudonymizer::class));
    $final = $router->additions === [] ? $first : $retriever->retrieve($context, $facts, [], null, $router->additions);

    return [
        'deterministic' => $first->ids(),
        'final' => $final->ids(),
        'router' => $router->toArray(),
        'status' => $router->status,
    ];
}

beforeEach(function () {
    if (blank(config('ai.providers.'.config('analysis.provider').'.key'))) {
        $this->markTestSkipped('Kein API-Schlüssel für das Live-Prüfset.');
    }

    config(['knowledge.retrieval.active' => true, 'knowledge.router.active' => true]);
});

afterAll(function () {
    $report = routerLiveReport();

    if ($report['cases'] === []) {
        return;
    }

    $directory = dirname(__DIR__, 2).'/storage/app/router-live';
    @mkdir($directory, 0775, true);
    file_put_contents($directory.'/report-'.date('Ymd-His').'.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
});

foreach (routerLiveCases() as $name => $case) {
    test("live: {$name}", function () use ($name, $case) {
        $runs = (int) ($case['runs'] ?? 1);
        $failures = [];

        for ($run = 1; $run <= $runs; $run++) {
            $result = routerLiveRun($case);
            $missing = array_values(array_diff($case['expected'] ?? [], $result['final']));
            $forbidden = array_values(array_intersect($case['forbidden'] ?? [], $result['final']));
            $added = count($result['router']['additions'] ?? []);
            $problems = [
                ...($result['status'] === 'failed' ? ['Router ausgefallen: '.$result['router']['reason']] : []),
                ...($missing === [] ? [] : ['fehlt: '.implode(', ', $missing)]),
                ...($forbidden === [] ? [] : ['verboten: '.implode(', ', $forbidden)]),
                ...(($case['no_additions'] ?? false) && $added > 0 ? ["{$added} Ergänzung(en), erwartet keine"] : []),
            ];

            routerLiveReport(['header' => [
                'created_at' => now()->toIso8601String(),
                'model' => config('knowledge.router.model'),
                'router_version' => config('knowledge.router.version'),
                'limits' => ['pool' => config('knowledge.router.pool'), 'max_additions' => config('knowledge.router.max_additions'), 'text_characters' => config('knowledge.router.text_characters'), 'timeout' => config('knowledge.router.timeout')],
                'knowledge_state' => app(KnowledgeLibrary::class)->state()->label(),
            ]]);
            routerLiveReport([
                'case' => $name,
                'run' => $run,
                'passed' => $problems === [],
                'problems' => $problems,
                'expected' => $case['expected'] ?? [],
                'forbidden' => $case['forbidden'] ?? [],
                'deterministic' => $result['deterministic'],
                'final' => $result['final'],
                'found_by_router' => array_values(array_diff($result['final'], $result['deterministic'])),
                'router' => $result['router'],
            ]);

            $failures = [...$failures, ...array_map(fn (string $problem): string => "Lauf {$run}: {$problem}", $problems)];
        }

        expect($failures)->toBe([], "Live-Fall „{$name}“ nicht bestanden");
    })->group('router-live');
}
