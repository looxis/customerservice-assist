<?php

use App\Analysis\Agents\CaseAgent;
use App\Knowledge\KnowledgeSelector;
use App\Knowledge\ProcedureFinder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
    ]);

    $body = fn (string $extra = '') => "Gilt für alle.\n\n# Voraussetzungen\n\n- Paket liegt vor.\n\n# Arbeitsschritte\n\n1. In EOCS öffnen.\n2. Problem melden.\n\n# Kritische Hinweise\n\nAchtung: Richtige Sendung wählen.\n\n# Abschlusskontrolle\n\n- [ ] Gespeichert.\n- [ ] Ticket erstellt.\n{$extra}";

    knowledgeBase([
        'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel']),
        'procedures/procedure-001-retoure.md' => knowledgeDoc(['id' => 'PROCEDURE-001', 'type' => 'procedure', 'title' => 'Retoure erfassen', 'actions' => ['return'], 'categories' => ['complaint'], 'sales_channels' => ['fachhaendler']], $body("\nSiehe POLICY-001.")),
        'procedures/procedure-002-neuversand.md' => knowledgeDoc(['id' => 'PROCEDURE-002', 'type' => 'procedure', 'title' => 'Neuversand anlegen', 'actions' => ['reshipment', 'return'], 'status' => 'active'], $body()),
        'procedures/procedure-003-erstattung.md' => knowledgeDoc(['id' => 'PROCEDURE-003', 'type' => 'procedure', 'title' => 'Erstattung', 'actions' => ['refund'], 'sales_channels' => ['amazon']], $body()),
        'procedures/procedure-004-alt.md' => knowledgeDoc(['id' => 'PROCEDURE-004', 'type' => 'procedure', 'title' => 'Alter Ablauf', 'actions' => ['return'], 'status' => 'deprecated'], $body()),
    ]);

    fakeZammad([zammadArticle(['id' => 1, 'body' => '<p>Paket kam zurück.</p>'])]);
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function procedureCase(array $actions, string $category = 'complaint', string $group = 'reseller'): string
{
    CaseAgent::fake([[
        'summary' => ['incident' => 'Retoure', 'customer_wish' => 'Info'], 'category' => $category, 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Retoure erfassen.', 'actions' => $actions,
        'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'x', 'missing_information' => [], 'knowledge_gaps' => [], 'knowledge_ids' => [],
        'confidence' => ['level' => 'MITTEL', 'reasons' => []], 'internal_todos' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag'],
    ]]);
    test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kundengruppe' => $group, 'variante' => 'verlauf']);

    return test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->getContent();
}

describe('suggestions', function () {
    test('procedures for the recommended actions are suggested within scope, in the order of the actions, each once', function () {
        $finder = app(ProcedureFinder::class);
        $reseller = app(KnowledgeSelector::class)->customerGroup('reseller');

        expect(array_map(fn ($document) => $document->id, $finder->suggested($reseller, [], 'complaint', ['reshipment', 'return'])))->toBe(['PROCEDURE-002', 'PROCEDURE-001'])
            ->and(array_map(fn ($document) => $document->id, $finder->suggested($reseller, [], 'complaint', ['return'])))->toBe(['PROCEDURE-001', 'PROCEDURE-002'])
            ->and(array_map(fn ($document) => $document->id, $finder->suggested($reseller, [], 'product-question', ['return'])))->toBe(['PROCEDURE-002'])
            ->and($finder->suggested($reseller, [], 'complaint', ['refund']))->toBe([]);
    });

    test('with an unclear customer group only procedures without restriction are suggested', function () {
        $unclear = app(KnowledgeSelector::class)->customerGroup('unclear');

        expect(array_map(fn ($document) => $document->id, app(ProcedureFinder::class)->suggested($unclear, [], 'complaint', ['return'])))->toBe(['PROCEDURE-002']);
    });

    test('the same input always gives the same suggestions', function () {
        $reseller = app(KnowledgeSelector::class)->customerGroup('reseller');
        $finder = app(ProcedureFinder::class);

        expect($finder->suggested($reseller, [], 'complaint', ['return', 'reshipment']))->toEqual($finder->suggested($reseller, [], 'complaint', ['return', 'reshipment']));
    });

    test('deprecated procedures are neither suggested nor in the catalogue', function () {
        $catalogue = app(ProcedureFinder::class)->catalogue(app(KnowledgeSelector::class)->customerGroup('reseller'), []);
        $ids = collect($catalogue)->flatten(1)->map(fn ($entry) => $entry['document']->id)->unique()->values()->all();

        expect($ids)->not->toContain('PROCEDURE-004');
    });
});

describe('view', function () {
    test('the internal procedures appear after the result, set apart from the reply, with id, title, actions, draft mark and text', function () {
        $html = procedureCase(['return']);

        expect($html)->toContain('Interne Abläufe')->toContain('– intern, nicht an den Kunden')
            ->and(strpos($html, 'aria-label="Antwortentwurf"'))->toBeLessThan(strpos($html, 'id="ablaeufe"'))
            ->and($html)->toContain('PROCEDURE-001')->toContain('Retoure erfassen')->toContain(config('knowledge.actions.return'))
            ->toContain('Dieser Ablauf ist noch ein Entwurf.');
    });

    test('steps and check items get checkboxes, critical notes are highlighted, ids are linked', function () {
        $html = procedureCase(['return']);
        $section = str($html)->after('id="ablaeufe"')->toString();

        $first = str($section)->after('text-slate-600">PROCEDURE-001</span>')->before('text-slate-600">PROCEDURE-002</span>')->toString();

        expect(substr_count($first, 'class="procedure-check"'))->toBe(4)
            ->and($section)->toContain('class="procedure-critical"')
            ->toContain('Achtung: Richtige Sendung wählen.')
            ->toMatch('/<a href="[^"]*knowledge\/dokument\/policies\/policy-001-a\.md">POLICY-001<\/a>/')
            ->and($first)->not->toContain('disabled');
    });

    test('without a fitting procedure the hint offers to report a gap; all procedures can be picked, unfitting ones are marked', function () {
        $html = procedureCase(['refund']);

        expect($html)->toContain('Kein passender Ablauf hinterlegt.')
            ->toContain('Kein Arbeitsablauf für Erstattung')
            ->toContain('Ablauf auswählen')
            ->toContain('gilt nicht für diese Kundengruppe');
    });

    test('three or more suggestions: only the first is open', function () {
        knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel']),
            'procedures/procedure-001-a.md' => knowledgeDoc(['id' => 'PROCEDURE-001', 'type' => 'procedure', 'title' => 'A', 'actions' => ['return']], "x\n\n# Voraussetzungen\n\n- a\n\n# Arbeitsschritte\n\n1. a\n\n# Abschlusskontrolle\n\n- [ ] a"),
            'procedures/procedure-002-b.md' => knowledgeDoc(['id' => 'PROCEDURE-002', 'type' => 'procedure', 'title' => 'B', 'actions' => ['return']], "x\n\n# Voraussetzungen\n\n- a\n\n# Arbeitsschritte\n\n1. a\n\n# Abschlusskontrolle\n\n- [ ] a"),
            'procedures/procedure-003-c.md' => knowledgeDoc(['id' => 'PROCEDURE-003', 'type' => 'procedure', 'title' => 'C', 'actions' => ['return']], "x\n\n# Voraussetzungen\n\n- a\n\n# Arbeitsschritte\n\n1. a\n\n# Abschlusskontrolle\n\n- [ ] a"),
        ]);

        $section = str(procedureCase(['return']))->after('id="ablaeufe"')->toString();

        expect(preg_match_all('/<details x-show="shown\([^)]*\)"\s*\n?\s*open/', $section))->toBe(1);
    });

    test('procedures never go to the ai', function () {
        procedureCase(['return']);

        CaseAgent::assertPrompted(fn ($prompt): bool => ! $prompt->contains('PROCEDURE-'));
    });
});
