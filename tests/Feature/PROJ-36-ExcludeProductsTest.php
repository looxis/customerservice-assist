<?php

use App\Knowledge\CaseContext;
use App\Knowledge\KnowledgeSelector;

afterEach(fn () => cleanUpKnowledgeBases());

/**
 * A knowledge base with three products and a policy with the given scope fields.
 *
 * @param  array<string, mixed>  $fields
 */
function excludeBase(array $fields): void
{
    $files = [
        'policies/policy-001-x.md' => knowledgeDoc(['id' => 'POLICY-001', 'type' => 'policy', 'summary' => 'Wann.', 'retrieval' => 'triggered', 'triggers' => ['Farbstich'], ...$fields]),
    ];
    foreach (['mug' => 'PRODUCT-001', 'glass-2d' => 'PRODUCT-002', 'glass-3d' => 'PRODUCT-003'] as $slug => $id) {
        $files["products/{$slug}.md"] = knowledgeDoc(['id' => $id, 'type' => 'product', 'title' => "Produkt {$slug}"]);
    }
    knowledgeBase($files);
}

/**
 * @param  list<string>  $products
 * @return array{selected: list<string>, excluded: array<string, string>}
 */
function excludeScope(array $products): array
{
    $selector = app(KnowledgeSelector::class);
    $scope = $selector->scope(new CaseContext($selector->customerGroup('private-looxis-de'), $products));

    return [
        'selected' => array_map(fn ($entry): string => $entry->document->id, $scope['selected']),
        'excluded' => collect($scope['excluded'])->mapWithKeys(fn ($entry): array => [$entry->document->id => $entry->reason])->all(),
    ];
}

describe('scope', function () {
    test('without a chosen product nothing is excluded', function () {
        excludeBase(['exclude_products' => ['glass-3d']]);

        expect(excludeScope([])['selected'])->toContain('POLICY-001');
    });

    test('an unknown product (no product file) excludes nothing', function () {
        excludeBase(['exclude_products' => ['glass-3d']]);

        expect(excludeScope(['no-such-product'])['selected'])->toContain('POLICY-001');
    });

    test('a chosen product on the list excludes the document, with a reason', function () {
        excludeBase(['exclude_products' => ['glass-2d', 'glass-3d']]);

        $scope = excludeScope(['glass-3d']);

        expect($scope['selected'])->not->toContain('POLICY-001')
            ->and($scope['excluded']['POLICY-001'])->toContain('glass-3d');
    });

    test('a chosen product off the list keeps the document', function () {
        excludeBase(['exclude_products' => ['glass-2d', 'glass-3d']]);

        expect(excludeScope(['mug'])['selected'])->toContain('POLICY-001');
    });

    test('with several products the document stays when at least one is not excluded', function () {
        excludeBase(['exclude_products' => ['glass-2d', 'glass-3d']]);

        expect(excludeScope(['glass-3d', 'mug'])['selected'])->toContain('POLICY-001')
            ->and(excludeScope(['glass-3d', 'glass-2d'])['selected'])->not->toContain('POLICY-001');
    });

    test('combined with products: the bound product still decides first', function () {
        excludeBase(['products' => ['mug', 'glass-3d'], 'exclude_products' => ['glass-3d']]);
        // A conflict is an error in the check; the scope must not crash and must not widen the document.
        expect(excludeScope(['glass-2d'])['selected'])->not->toContain('POLICY-001');
    });
});

describe('check', function () {
    test('a product in products and exclude_products is an error', function () {
        $library = knowledgeBase([
            'policies/policy-001-x.md' => knowledgeDoc(['id' => 'POLICY-001', 'products' => ['mug'], 'exclude_products' => ['mug']]),
            'products/mug.md' => knowledgeDoc(['id' => 'PRODUCT-001', 'type' => 'product']),
        ]);

        expect(messagesOf($library, 'error'))->toContain('`products` und in `exclude_products`');
    });

    test('a product file with exclude_products is an error', function () {
        $library = knowledgeBase([
            'products/mug.md' => knowledgeDoc(['id' => 'PRODUCT-001', 'type' => 'product', 'exclude_products' => ['glass']]),
        ]);

        expect(messagesOf($library, 'error'))->toContain('Produktdatei darf kein `exclude_products`');
    });

    test('an unknown product slug warns, a wrong spelling too', function () {
        $library = knowledgeBase([
            'policies/policy-001-x.md' => knowledgeDoc(['id' => 'POLICY-001', 'exclude_products' => ['Glas Foto']]),
            'products/mug.md' => knowledgeDoc(['id' => 'PRODUCT-001', 'type' => 'product']),
        ]);

        expect(messagesOf($library, 'warning'))->toContain('`Glas Foto` in `exclude_products`')->toContain('kein Slug');
    });

    test('the field is known and valid without warnings', function () {
        $library = knowledgeBase([
            'policies/policy-001-x.md' => knowledgeDoc(['id' => 'POLICY-001', 'exclude_products' => ['mug']]),
            'products/mug.md' => knowledgeDoc(['id' => 'PRODUCT-001', 'type' => 'product']),
        ]);

        expect(messagesOf($library, 'warning'))->not->toContain('exclude_products')->not->toContain('Unbekanntes Feld');
    });
});

describe('the authoring kit', function () {
    test('the guide explains exclude_products and the templates carry the field', function () {
        $guide = file_get_contents(base_path('docs/KNOWLEDGE_AUTHORING_GUIDE.md'));

        expect($guide)->toContain('`exclude_products`')->toContain('alle** gewählten Produkte');

        foreach (['policy', 'playbook', 'process', 'permission'] as $type) {
            expect(file_get_contents(base_path("knowledge/templates/{$type}.md")))->toContain("\nexclude_products:");
        }
    });
});
