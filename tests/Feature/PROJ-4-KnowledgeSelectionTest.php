<?php

use App\Knowledge\CaseContext;
use App\Knowledge\KnowledgeSelection;
use App\Knowledge\KnowledgeSelectionEntry;
use App\Knowledge\KnowledgeSelector;
use App\Knowledge\KnowledgeSuggester;

afterEach(function () {
    cleanUpKnowledgeBases();
});

/**
 * A knowledge file of the given ID; the folder and type follow from the ID prefix.
 *
 * @return array<string, string>
 */
function selectionDoc(string $id, array $overrides = [], ?string $body = null): array
{
    $types = [
        'POLICY' => ['policy', 'policies'],
        'PERMISSION' => ['permission', 'permissions'],
        'PROCESS' => ['process', 'processes'],
        'PLAYBOOK' => ['playbook', 'playbooks'],
        'TONE' => ['tone', 'tone'],
        'GLOSSARY' => ['glossary', 'glossary'],
        'EXAMPLE-GOOD' => ['example-good', 'examples/good'],
        'EXAMPLE-BAD' => ['example-bad', 'examples/bad'],
    ];
    $prefix = preg_replace('/-\d{3}$/', '', $id);
    [$type, $folder] = $types[$prefix];

    $fields = ['id' => $id, 'type' => $type];

    if ($type === 'permission') {
        $fields += ['action' => 'refund', 'agent_allowed' => true];
    }

    if (in_array($type, ['playbook', 'process'], true)) {
        $fields['categories'] = ['complaint'];
    }

    $path = $folder.'/'.strtolower($id).'-doc.md';

    return [$path => $body === null ? knowledgeDoc(array_merge($fields, $overrides)) : knowledgeDoc(array_merge($fields, $overrides), $body)];
}

/**
 * @return array<string, string>
 */
function productDoc(string $slug, string $id, array $overrides = [], ?string $path = null): array
{
    return [($path ?? "products/{$slug}.md") => knowledgeDoc(array_merge(['id' => $id, 'type' => 'product', 'title' => "Produkt {$slug}"], $overrides))];
}

function selectFor(string $group, array $products = []): KnowledgeSelection
{
    $selector = app(KnowledgeSelector::class);

    return $selector->select(new CaseContext($selector->customerGroup($group), $products));
}

/**
 * @return list<string>
 */
function selectedIds(KnowledgeSelection $selection): array
{
    return array_map(fn (KnowledgeSelectionEntry $entry): string => $entry->document->id, $selection->selected);
}

/**
 * @return array<string, string>
 */
function excludedReasons(KnowledgeSelection $selection): array
{
    $reasons = [];

    foreach ($selection->excluded as $entry) {
        $reasons[$entry->document->id] = $entry->reason;
    }

    return $reasons;
}

describe('value lists', function () {
    test('the six customer groups come in the configured order with german labels', function () {
        knowledgeBase([]);

        $groups = app(KnowledgeSelector::class)->customerGroups();

        expect(array_map(fn ($group) => $group->label, $groups))->toBe([
            'Privatkunde, looxis.de',
            'Privatkunde, Amazon',
            'Foto-Fachhändler / Reseller',
            'LOOXIS-Pro',
            'White-Label-Kunde, masterpics',
            'Noch unklar',
        ])->and(array_map(fn ($group) => [$group->customerType, $group->salesChannel], $groups))->toBe([
            ['b2c', 'looxis-de'],
            ['b2c', 'amazon'],
            ['b2b-reseller', 'fachhaendler'],
            ['b2b-pro', 'looxis-pro'],
            ['b2b-whitelabel', 'masterpics'],
            [null, null],
        ]);
    });

    test('selectable products are the usable product files sorted by title, a split product counts once', function () {
        knowledgeBase([
            ...productDoc('lunchbox', 'PRODUCT-001', ['title' => 'Lunchbox']),
            ...productDoc('3d-glass-photo', 'PRODUCT-002', ['title' => '3D-Glasfoto'], 'products/3d-glass-photo/overview.md'),
            ...productDoc('3d-glass-photo', 'PRODUCT-003', ['title' => '3D-Glasfoto Produktion'], 'products/3d-glass-photo/production.md'),
            ...productDoc('old', 'PRODUCT-004', ['status' => 'deprecated']),
            ...productDoc('broken', 'PRODUCT-005', ['status' => 'unknown']),
        ]);

        expect(app(KnowledgeSelector::class)->products())->toBe([
            ['slug' => '3d-glass-photo', 'title' => '3D-Glasfoto'],
            ['slug' => 'lunchbox', 'title' => 'Lunchbox'],
        ]);
    });

    test('product titles sort as a german reader expects, ignoring case and umlauts', function () {
        knowledgeBase([
            ...productDoc('zauber', 'PRODUCT-001', ['title' => 'Zaubertasse']),
            ...productDoc('oel', 'PRODUCT-002', ['title' => 'Ölbild']),
            ...productDoc('acryl', 'PRODUCT-003', ['title' => 'acrylglas']),
            ...productDoc('bild', 'PRODUCT-004', ['title' => 'Bild']),
            ...productDoc('ofen', 'PRODUCT-005', ['title' => 'Ofenkachel']),
        ]);

        expect(array_column(app(KnowledgeSelector::class)->products(), 'title'))
            ->toBe(['acrylglas', 'Bild', 'Ofenkachel', 'Ölbild', 'Zaubertasse']);
    });

    test('the new customer types and channels are valid', function () {
        $library = knowledgeBase([
            ...selectionDoc('POLICY-001', ['customer_types' => ['b2c'], 'sales_channels' => ['looxis-de', 'amazon']]),
            ...selectionDoc('POLICY-002', ['customer_types' => ['b2b-reseller'], 'sales_channels' => ['fachhaendler']]),
            ...selectionDoc('POLICY-003', ['customer_types' => ['b2b-pro'], 'sales_channels' => ['looxis-pro']]),
        ]);

        expect($library->issues())->toBeEmpty();
    });

    test('the former channel value shop is an error that names looxis-de', function () {
        $library = knowledgeBase(selectionDoc('POLICY-001', ['sales_channels' => ['shop']]));

        expect(messagesOf($library, 'error'))->toContain('Unbekannter Wert `shop` in `sales_channels`. Der eigene Shop heißt jetzt `looxis-de`.');
    });

    test('the former value b2b is an error that names the new values', function () {
        $library = knowledgeBase(selectionDoc('POLICY-001', ['customer_types' => ['b2b']]));

        expect(messagesOf($library, 'error'))->toContain('Unbekannter Wert `b2b` in `customer_types`. Stattdessen `b2b-reseller` (Foto-Fachhändler / Reseller), `b2b-pro` (LOOXIS-Pro) oder `b2b-whitelabel` (White-Label-Kunde, z. B. masterpics) verwenden.')
            ->and($library->usable())->toBeEmpty();
    });
});

describe('selection by customer group', function () {
    beforeEach(function () {
        knowledgeBase([
            ...selectionDoc('POLICY-001'),
            ...selectionDoc('POLICY-002', ['customer_types' => ['b2c']]),
            ...selectionDoc('POLICY-003', ['customer_types' => ['b2c'], 'sales_channels' => ['looxis-de']]),
            ...selectionDoc('POLICY-004', ['customer_types' => ['b2c'], 'sales_channels' => ['amazon']]),
            ...selectionDoc('POLICY-005', ['sales_channels' => ['looxis-de']]),
            ...selectionDoc('POLICY-006', ['customer_types' => ['b2b-reseller'], 'sales_channels' => ['fachhaendler']]),
            ...selectionDoc('POLICY-007', ['customer_types' => ['b2b-pro']]),
            ...selectionDoc('POLICY-008', ['customer_types' => ['b2b-reseller', 'b2b-pro']]),
            ...selectionDoc('POLICY-009', ['customer_types' => ['b2b-whitelabel'], 'sales_channels' => ['masterpics']]),
        ]);
    });

    test('each group gets the documents for its customer type and channel', function (string $group, array $expected) {
        expect(selectedIds(selectFor($group)))->toBe($expected);
    })->with([
        'private shop' => ['private-looxis-de', ['POLICY-001', 'POLICY-002', 'POLICY-003', 'POLICY-005']],
        'private amazon' => ['private-amazon', ['POLICY-001', 'POLICY-002', 'POLICY-004']],
        'reseller' => ['reseller', ['POLICY-001', 'POLICY-006', 'POLICY-008']],
        'looxis pro' => ['looxis-pro', ['POLICY-001', 'POLICY-007', 'POLICY-008']],
        'white label masterpics' => ['whitelabel-masterpics', ['POLICY-001', 'POLICY-009']],
        'unclear' => ['unclear', ['POLICY-001']],
    ]);

    test('selected documents carry an understandable reason', function () {
        $selected = collect(selectFor('private-looxis-de')->selected)->mapWithKeys(fn ($entry) => [$entry->document->id => $entry->reason]);

        expect($selected->all())->toBe([
            'POLICY-001' => 'gilt für alle',
            'POLICY-002' => 'Kundenart b2c',
            'POLICY-003' => 'Kundenart b2c, Kanal looxis-de',
            'POLICY-005' => 'Kanal looxis-de',
        ]);
    });

    test('left-out documents are listed with the first reason that applies', function () {
        expect(excludedReasons(selectFor('private-looxis-de')))->toBe([
            'POLICY-004' => 'nur für Kanal amazon',
            'POLICY-006' => 'nur für Kundenart b2b-reseller',
            'POLICY-007' => 'nur für Kundenart b2b-pro',
            'POLICY-008' => 'nur für Kundenart b2b-reseller, b2b-pro',
            'POLICY-009' => 'nur für Kundenart b2b-whitelabel',
        ]);
    });
});

describe('selection by product', function () {
    beforeEach(function () {
        knowledgeBase([
            ...selectionDoc('POLICY-001'),
            ...selectionDoc('PLAYBOOK-001', ['products' => ['lunchbox']]),
            ...selectionDoc('PLAYBOOK-002', ['products' => ['lunchbox', '3d-glass-photo']]),
            ...productDoc('lunchbox', 'PRODUCT-001'),
            ...productDoc('3d-glass-photo', 'PRODUCT-002', ['products' => ['3d-glass-photo']], 'products/3d-glass-photo/overview.md'),
            ...productDoc('3d-glass-photo', 'PRODUCT-003', [], 'products/3d-glass-photo/production.md'),
        ]);
    });

    test('without a product only documents without products are used', function () {
        $selection = selectFor('private-looxis-de');

        expect(selectedIds($selection))->toBe(['POLICY-001'])
            ->and(excludedReasons($selection))->toMatchArray([
                'PRODUCT-001' => 'nur für Produkt lunchbox',
                'PLAYBOOK-001' => 'nur für Produkt lunchbox',
            ]);
    });

    test('a chosen product adds its product file and the documents bound to it', function () {
        $selection = selectFor('private-looxis-de', ['lunchbox']);

        expect(selectedIds($selection))->toBe(['POLICY-001', 'PRODUCT-001', 'PLAYBOOK-001', 'PLAYBOOK-002'])
            ->and($selection->selected[1]->reason)->toBe('Produktwissen lunchbox')
            ->and($selection->selected[2]->reason)->toBe('Produkt lunchbox');
    });

    test('all files of a split product are used, a document for several products appears once', function () {
        expect(selectedIds(selectFor('private-looxis-de', ['3d-glass-photo', 'lunchbox'])))
            ->toBe(['POLICY-001', 'PRODUCT-001', 'PRODUCT-002', 'PRODUCT-003', 'PLAYBOOK-001', 'PLAYBOOK-002']);
    });

    test('an unknown product is ignored and named', function () {
        $selection = selectFor('private-looxis-de', ['mug']);

        expect(selectedIds($selection))->toBe(['POLICY-001'])
            ->and($selection->unknownProducts)->toBe(['mug'])
            ->and($selection->warnings)->toContain('Unbekanntes Produkt `mug`: Es gibt keine Produktdatei, das Produkt wird ignoriert.');
    });
});

test('a chosen product whose product file became faulty is named as without product knowledge', function () {
    knowledgeBase([
        ...selectionDoc('POLICY-001'),
        ...selectionDoc('PLAYBOOK-001', ['products' => ['lunchbox']]),
        ...productDoc('lunchbox', 'PRODUCT-001', ['status' => 'final']),
    ]);

    $selection = selectFor('private-looxis-de', ['lunchbox']);

    expect(selectedIds($selection))->toBe(['POLICY-001', 'PLAYBOOK-001'])
        ->and($selection->productsWithoutKnowledge)->toBe(['lunchbox'])
        ->and($selection->warnings)->toContain('Produkt `lunchbox` ohne Produktwissen: Keine Produktdatei ist derzeit verwendbar.');
});

describe('which documents qualify at all', function () {
    test('faulty and deprecated documents are never used, drafts are used and marked', function () {
        knowledgeBase([
            ...selectionDoc('POLICY-001', ['status' => 'active']),
            ...selectionDoc('POLICY-002', ['status' => 'draft']),
            ...selectionDoc('POLICY-003', ['status' => 'deprecated']),
            ...selectionDoc('POLICY-004', ['customer_types' => ['firma']]),
        ]);

        $selection = selectFor('private-looxis-de');

        expect(selectedIds($selection))->toBe(['POLICY-001', 'POLICY-002'])
            ->and(excludedReasons($selection))->toBe([])
            ->and($selection->selected[0]->isDraft())->toBeFalse()
            ->and($selection->selected[1]->isDraft())->toBeTrue()
            ->and($selection->draftCount())->toBe(1);
    });

    test('categories and topics do not affect the selection', function () {
        knowledgeBase([
            ...selectionDoc('POLICY-001', ['categories' => ['complaint'], 'topics' => ['refund']]),
            ...selectionDoc('POLICY-002', ['categories' => ['product-question'], 'topics' => ['shipping']]),
        ]);

        expect(selectedIds(selectFor('unclear')))->toBe(['POLICY-001', 'POLICY-002']);
    });

    test('reference cases are capped, preferring those for the chosen product, then by id', function () {
        knowledgeBase([
            ...productDoc('lunchbox', 'PRODUCT-001'),
            ...selectionDoc('EXAMPLE-GOOD-001'),
            ...selectionDoc('EXAMPLE-GOOD-002'),
            ...selectionDoc('EXAMPLE-GOOD-003'),
            ...selectionDoc('EXAMPLE-GOOD-004', ['products' => ['lunchbox']]),
            ...selectionDoc('EXAMPLE-BAD-001'),
            ...selectionDoc('EXAMPLE-BAD-002'),
            ...selectionDoc('EXAMPLE-BAD-003'),
        ]);

        $selection = selectFor('private-looxis-de', ['lunchbox']);

        expect(selectedIds($selection))->toBe(['PRODUCT-001', 'EXAMPLE-GOOD-001', 'EXAMPLE-GOOD-002', 'EXAMPLE-GOOD-004', 'EXAMPLE-BAD-001', 'EXAMPLE-BAD-002'])
            ->and(excludedReasons($selection))->toBe([
                'EXAMPLE-GOOD-003' => 'Obergrenze für gute Beispiele erreicht (3)',
                'EXAMPLE-BAD-003' => 'Obergrenze für schlechte Beispiele erreicht (2)',
            ]);
    });
});

describe('result', function () {
    test('documents are in knowledge base precedence, then by id', function () {
        knowledgeBase([
            ...selectionDoc('EXAMPLE-BAD-001'),
            ...selectionDoc('EXAMPLE-GOOD-001'),
            ...selectionDoc('GLOSSARY-001'),
            ...selectionDoc('TONE-001'),
            ...selectionDoc('PLAYBOOK-001'),
            ...selectionDoc('PROCESS-001'),
            ...productDoc('lunchbox', 'PRODUCT-001', ['products' => []]),
            ...selectionDoc('PERMISSION-001'),
            ...selectionDoc('POLICY-002'),
            ...selectionDoc('POLICY-001'),
        ]);

        expect(selectedIds(selectFor('private-looxis-de', ['lunchbox'])))->toBe([
            'POLICY-001', 'POLICY-002', 'PERMISSION-001', 'PRODUCT-001', 'PROCESS-001',
            'PLAYBOOK-001', 'TONE-001', 'GLOSSARY-001', 'EXAMPLE-GOOD-001', 'EXAMPLE-BAD-001',
        ]);
    });

    test('the result holds context, knowledge state, fingerprints, draft count and size', function () {
        $library = knowledgeBase([
            ...selectionDoc('POLICY-001', ['status' => 'active'], 'Zehn Zeich'),
            ...selectionDoc('POLICY-002', [], 'Fünf.'),
        ]);

        $selection = selectFor('private-amazon');

        expect($selection->context->customerGroup->key)->toBe('private-amazon')
            ->and($selection->context->products)->toBe([])
            ->and($selection->state)->toEqual($library->state())
            ->and($selection->fingerprints())->toBe([
                'POLICY-001' => $library->find('POLICY-001')->fingerprint,
                'POLICY-002' => $library->find('POLICY-002')->fingerprint,
            ])
            ->and($selection->draftCount())->toBe(1)
            ->and($selection->characterCount())->toBe(15)
            ->and($selection->warnings)->toBe([]);
    });

    test('the same input on the same knowledge state gives the same documents in the same order', function () {
        knowledgeBase([
            ...selectionDoc('POLICY-002'),
            ...selectionDoc('POLICY-001'),
            ...selectionDoc('EXAMPLE-GOOD-002'),
            ...selectionDoc('EXAMPLE-GOOD-001'),
            ...productDoc('lunchbox', 'PRODUCT-001'),
        ]);

        $first = selectFor('private-looxis-de', ['lunchbox', 'lunchbox']);
        $second = selectFor('private-looxis-de', ['lunchbox']);

        expect(selectedIds($first))->toBe(selectedIds($second))
            ->and(excludedReasons($first))->toBe(excludedReasons($second));
    });

    test('no matching knowledge gives an empty result with a warning', function () {
        knowledgeBase(selectionDoc('POLICY-001', ['customer_types' => ['b2c']]));

        $selection = selectFor('unclear');

        expect($selection->isEmpty())->toBeTrue()
            ->and($selection->warnings)->toBe(['Kein Wissen für diesen Fall.']);
    });

    test('a missing knowledge folder gives an empty result with the message from the library', function () {
        config(['knowledge.path' => sys_get_temp_dir().'/knowledge-test-missing']);
        app()->forgetScopedInstances();

        $selection = selectFor('private-looxis-de');

        expect($selection->isEmpty())->toBeTrue()
            ->and($selection->warnings)->toContain('Der Knowledge-Ordner fehlt. Es steht kein Unternehmenswissen zur Verfügung.');
    });
});

describe('size limit', function () {
    test('above the limit reference cases are dropped from the end and a warning names size and limit', function () {
        config(['knowledge.selection.max_characters' => 250]);
        knowledgeBase([
            ...selectionDoc('POLICY-001', [], str_repeat('a', 100)),
            ...selectionDoc('EXAMPLE-GOOD-001', [], str_repeat('b', 100)),
            ...selectionDoc('EXAMPLE-BAD-001', [], str_repeat('c', 100)),
        ]);

        $selection = selectFor('private-looxis-de');

        expect(selectedIds($selection))->toBe(['POLICY-001', 'EXAMPLE-GOOD-001'])
            ->and(excludedReasons($selection))->toBe(['EXAMPLE-BAD-001' => 'weggelassen, weil der Gesamtumfang die Obergrenze überschreitet'])
            ->and($selection->characterCount())->toBe(200)
            ->and($selection->warnings)->toBe(['Der Umfang von 300 Zeichen überschreitet die Obergrenze von 250 Zeichen. Ein Referenzfall wurde weggelassen.']);
    });

    test('binding knowledge is never cut and the warning stays when it alone exceeds the limit', function () {
        config(['knowledge.selection.max_characters' => 150]);
        knowledgeBase([
            ...selectionDoc('POLICY-001', [], str_repeat('a', 100)),
            ...selectionDoc('PLAYBOOK-001', [], str_repeat('b', 100)),
            ...selectionDoc('EXAMPLE-GOOD-001', [], str_repeat('c', 100)),
        ]);

        $selection = selectFor('private-looxis-de');

        expect(selectedIds($selection))->toBe(['POLICY-001', 'PLAYBOOK-001'])
            ->and($selection->exceedsLimit())->toBeTrue()
            ->and($selection->warnings)->toBe([
                'Der Umfang von 300 Zeichen überschreitet die Obergrenze von 150 Zeichen. Ein Referenzfall wurde weggelassen.',
                'Der Umfang liegt mit 200 Zeichen über der Obergrenze von 150 Zeichen. Verbindliches Wissen wird nicht gekürzt.',
            ]);
    });
});

describe('suggestions from the order', function () {
    beforeEach(function () {
        knowledgeBase([
            ...productDoc('lunchbox', 'PRODUCT-001', ['order_keywords' => ['LB-100', 'Lunchbox']]),
            ...productDoc('3d-glass-photo', 'PRODUCT-002', ['order_keywords' => ['Glasfoto']]),
            ...productDoc('mug', 'PRODUCT-003', ['order_keywords' => ['Tasse']]),
        ]);
    });

    test('every product with a keyword in article number or description is suggested, ignoring case', function () {
        $products = app(KnowledgeSuggester::class)->products([
            ['article_number' => 'lb-100-blue', 'description' => 'Brotdose blau'],
            ['article_number' => 'X-7', 'description' => '3D-GLASFOTO Herz'],
        ]);

        expect($products)->toBe(['3d-glass-photo', 'lunchbox']);
    });

    test('no matching line or no order gives no suggestion and no error', function () {
        $suggester = app(KnowledgeSuggester::class);

        expect($suggester->products([['article_number' => 'K-1', 'description' => 'Kalender']]))->toBe([])
            ->and($suggester->products([]))->toBe([]);
    });

    test('the channel of the order suggests the customer group', function (?string $channel, string $group) {
        expect(app(KnowledgeSuggester::class)->customerGroup($channel)->key)->toBe($group);
    })->with([
        'looxis-de' => ['looxis-de', 'private-looxis-de'],
        'amazon' => ['Amazon', 'private-amazon'],
        'reseller shop by its eocs name' => ['fachhaendler.looxis.de', 'reseller'],
        'looxis.de by its eocs name' => ['looxis.de Vanilo', 'private-looxis-de'],
        'french reseller by its eocs name' => ['reseller.looxis.fr', 'reseller'],
        'looxis pro by its eocs name' => ['looxis-pro.com', 'looxis-pro'],
        'masterpics by its eocs name' => ['Masterpics White Label DE', 'whitelabel-masterpics'],
        'looxis pro' => ['looxis-pro', 'looxis-pro'],
        'masterpics' => ['masterpics', 'whitelabel-masterpics'],
        'french reseller shop' => ['looxis.fr', 'reseller'],
        'unknown channel' => ['ebay', 'unclear'],
        'no channel' => [null, 'unclear'],
    ]);

    test('the channel names of the order are configurable', function () {
        config(['knowledge.order_channels' => ['Webshop DE' => 'looxis-de']]);

        expect(app(KnowledgeSuggester::class)->customerGroup('webshop de')->key)->toBe('private-looxis-de')
            ->and(app(KnowledgeSuggester::class)->customerGroup('looxis-de')->key)->toBe('unclear');
    });
});

describe('checks of the knowledge files', function () {
    test('order_keywords is a known list field in product files only', function () {
        $library = knowledgeBase([
            ...productDoc('lunchbox', 'PRODUCT-001', ['order_keywords' => ['Lunchbox']]),
            ...selectionDoc('POLICY-001', ['order_keywords' => ['Lunchbox']]),
            ...productDoc('mug', 'PRODUCT-002', ['order_keywords' => [['sku' => 'M-1']]]),
        ]);

        expect(messagesOf($library, 'warning'))->toContain('policies/policy-001-doc.md: Unbekanntes Feld `order_keywords`')
            ->not->toContain('products/lunchbox.md')
            ->and(messagesOf($library, 'error'))->toContain('products/mug.md: Feld `order_keywords` muss eine einfache Liste');
    });

    test('the same keyword for two products warns at both files', function () {
        $library = knowledgeBase([
            ...productDoc('lunchbox', 'PRODUCT-001', ['order_keywords' => ['Box']]),
            ...productDoc('giftbox', 'PRODUCT-002', ['order_keywords' => ['box']]),
        ]);

        expect(messagesOf($library, 'warning'))
            ->toContain('products/lunchbox.md: Das Schlüsselwort `box` in `order_keywords` steht auch beim Produkt giftbox.')
            ->toContain('products/giftbox.md: Das Schlüsselwort `box` in `order_keywords` steht auch beim Produkt lunchbox.');
    });

    test('the same keyword in two files of one split product is fine', function () {
        $library = knowledgeBase([
            ...productDoc('3d-glass-photo', 'PRODUCT-001', ['order_keywords' => ['Glasfoto']], 'products/3d-glass-photo/overview.md'),
            ...productDoc('3d-glass-photo', 'PRODUCT-002', ['order_keywords' => ['Glasfoto']], 'products/3d-glass-photo/production.md'),
        ]);

        expect($library->issues())->toBeEmpty();
    });

    test('a keyword shorter than three characters warns', function () {
        $library = knowledgeBase(productDoc('lunchbox', 'PRODUCT-001', ['order_keywords' => ['LB']]));

        expect(messagesOf($library, 'warning'))->toContain('Das Schlüsselwort `LB` in `order_keywords` ist kürzer als 3 Zeichen');
    });

    test('customer type and channel that fit no customer group warn', function () {
        $library = knowledgeBase([
            ...selectionDoc('POLICY-001', ['customer_types' => ['b2c'], 'sales_channels' => ['looxis-pro']]),
            ...selectionDoc('POLICY-002', ['customer_types' => ['b2c'], 'sales_channels' => ['looxis-pro', 'looxis-de']]),
        ]);

        expect(messagesOf($library, 'warning'))->toContain('policies/policy-001-doc.md: Kundenart und Kanal passen zu keiner Kundengruppe')
            ->not->toContain('policies/policy-002-doc.md');
    });

    test('the id overview lists the order keywords per product', function () {
        $library = knowledgeBase([
            ...productDoc('magic-mug', 'PRODUCT-001', ['order_keywords' => ['Zaubertasse', 'MM-100']]),
            ...productDoc('3d-glass-photo', 'PRODUCT-002', ['order_keywords' => ['Glasfoto']], 'products/3d-glass-photo/overview.md'),
            ...productDoc('3d-glass-photo', 'PRODUCT-003', ['order_keywords' => ['Glasfoto', 'GF-1']], 'products/3d-glass-photo/production.md'),
        ]);

        expect($library->overview())
            ->toContain("Vorhandene products-Slugs:\n3d-glass-photo, magic-mug\n")
            ->toContain("Vergebene order_keywords je Produkt:\n3d-glass-photo: GF-1, Glasfoto\nmagic-mug: MM-100, Zaubertasse");
    });

    test('the id overview says when no order keywords exist yet', function () {
        expect(knowledgeBase(selectionDoc('POLICY-001'))->overview())->toContain("Vergebene order_keywords je Produkt:\nnoch keine");
    });

    test('the authoring guide and the knowledge skill ask for order keywords', function () {
        expect(file_get_contents(base_path('docs/KNOWLEDGE_AUTHORING_GUIDE.md')))
            ->toContain('Frage beim Erfassen jeder Produktdatei ausdrücklich')
            ->toContain('Vergebene order_keywords je Produkt:')
            ->and(file_get_contents(base_path('.claude/skills/knowledge/SKILL.md')))
            ->toContain('For product files, ask for `order_keywords`');
    });

    test('the product template contains order_keywords', function () {
        expect(file_get_contents(base_path('knowledge/templates/product.md')))->toContain("\norder_keywords:\n");
    });
});

describe('preview command', function () {
    test('it lists selected and left-out documents with reasons, size and warnings', function () {
        knowledgeBase([
            ...selectionDoc('POLICY-001', ['title' => 'Für alle']),
            ...selectionDoc('POLICY-002', ['title' => 'Nur Amazon', 'sales_channels' => ['amazon']]),
            ...productDoc('lunchbox', 'PRODUCT-001', ['title' => 'Lunchbox']),
        ]);

        $this->artisan('knowledge:select private-looxis-de --product=lunchbox')
            ->expectsOutputToContain('Fallkontext: Privatkunde, looxis.de; Produkte: lunchbox')
            ->expectsOutputToContain('Ausgewählt: 2 Dokumente, davon 2 Entwurfs-Wissen')
            ->expectsTable(['ID', 'Titel', 'Status', 'Grund'], [
                ['POLICY-001', 'Für alle', 'draft', 'gilt für alle'],
                ['PRODUCT-001', 'Lunchbox', 'draft', 'Produktwissen lunchbox'],
            ])
            ->expectsOutputToContain('Nicht ausgewählt: 1 Dokument')
            ->doesntExpectOutputToContain('1 Dokumente')
            ->expectsTable(['ID', 'Titel', 'Status', 'Grund'], [
                ['POLICY-002', 'Nur Amazon', 'draft', 'nur für Kanal amazon'],
            ])
            ->expectsOutputToContain('Umfang: ')
            ->assertSuccessful();
    });

    test('an empty product option is ignored', function () {
        knowledgeBase(productDoc('lunchbox', 'PRODUCT-001'));

        $this->artisan('knowledge:select private-looxis-de --product=')
            ->expectsOutputToContain('Produkte: kein Produktbezug')
            ->assertSuccessful();
    });

    test('an unknown customer group names the allowed values and fails', function () {
        knowledgeBase(selectionDoc('POLICY-001'));

        $this->artisan('knowledge:select haendler')
            ->expectsOutputToContain('Unbekannte Kundengruppe `haendler`.')
            ->expectsOutputToContain('Erlaubt: private-looxis-de (Privatkunde, looxis.de), private-amazon')
            ->assertFailed();
    });

    test('an unknown product names the allowed values and fails', function () {
        knowledgeBase(productDoc('lunchbox', 'PRODUCT-001'));

        $this->artisan('knowledge:select private-looxis-de --product=mug')
            ->expectsOutputToContain('Unbekanntes Produkt: mug.')
            ->expectsOutputToContain('Erlaubt: lunchbox')
            ->assertFailed();
    });
});
