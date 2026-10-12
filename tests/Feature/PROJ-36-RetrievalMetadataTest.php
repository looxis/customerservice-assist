<?php

use App\Knowledge\KnowledgeLibrary;
use App\Knowledge\WhenSchema;

afterEach(fn () => cleanUpKnowledgeBases());

/**
 * Check one policy with the given frontmatter fields.
 *
 * @param  array<string, mixed>  $fields
 */
function retrievalDoc(array $fields): KnowledgeLibrary
{
    return knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc($fields)]);
}

describe('reading the new fields', function () {
    test('summary, retrieval, triggers, exclusions, condition and actions are read', function () {
        $document = retrievalDoc([
            'retrieval' => 'triggered', 'summary' => 'Greift bei Rechtefragen.', 'triggers' => ['Urheberrecht', 'Copyright'],
            'exclude_triggers' => ['eigenes Firmenlogo'], 'when' => ['order_channel' => ['amazon']], 'actions' => ['refund'],
        ])->find('POLICY-001');

        expect($document->retrieval())->toBe('triggered')->and($document->summary())->toBe('Greift bei Rechtefragen.')
            ->and($document->triggers())->toBe(['Urheberrecht', 'Copyright'])->and($document->excludeTriggers())->toBe(['eigenes Firmenlogo'])
            ->and($document->when())->toBe(['order_channel' => ['amazon']])->and($document->actions())->toBe(['refund']);
    });

    test('a document without the fields is unclassified and raises no message in the transition mode', function () {
        $library = retrievalDoc([]);

        expect($library->find('POLICY-001')->retrieval())->toBeNull()->and($library->find('POLICY-001')->when())->toBe([])
            ->and($library->issues())->toBeEmpty();
    });

    test('a well formed classified document raises no message', function () {
        $library = retrievalDoc(['retrieval' => 'triggered', 'summary' => 'Wann gebraucht.', 'triggers' => ['Urheberrecht'], 'exclude_triggers' => ['Firmenlogo']]);

        expect($library->issues())->toBeEmpty();
    });
});

describe('class and summary', function () {
    test('an unknown class is an error', function () {
        expect(messagesOf(retrievalDoc(['retrieval' => 'sometimes']), 'error'))->toContain('Unbekannter Wert `sometimes` in `retrieval`');
    });

    test('a class that is no plain text is an error', function () {
        expect(messagesOf(retrievalDoc(['retrieval' => ['core']]), 'error'))->toContain('`retrieval` muss ein einfacher Text sein');
    });

    test('a missing class is an error once the field is required, not before', function () {
        expect(retrievalDoc([])->errors())->toBeEmpty();

        config(['knowledge.retrieval.required' => true]);

        expect(messagesOf(retrievalDoc([]), 'error'))->toContain('Pflichtfeld `retrieval` fehlt');
    });

    test('a classified document without summary gets a warning, an unclassified one does not', function () {
        expect(messagesOf(retrievalDoc(['retrieval' => 'core']), 'warning'))->toContain('`summary` fehlt');
        expect(retrievalDoc([])->warnings())->toBeEmpty();
    });

    test('a summary that is no plain text is an error', function () {
        expect(messagesOf(retrievalDoc(['retrieval' => 'core', 'summary' => ['a' => 'b']]), 'error'))->toContain('`summary` muss ein einfacher Text sein');
    });
});

describe('triggered documents', function () {
    test('without triggers and condition a warning in the transition mode and an error after the migration', function () {
        expect(messagesOf(retrievalDoc(['retrieval' => 'triggered', 'summary' => 'x']), 'warning'))->toContain('wird nie abgerufen');

        config(['knowledge.retrieval.required' => true]);

        expect(messagesOf(retrievalDoc(['retrieval' => 'triggered', 'summary' => 'x']), 'error'))->toContain('könnte nie abgerufen werden');
    });

    test('a condition or actions alone are enough', function () {
        config(['knowledge.retrieval.required' => true]);

        expect(retrievalDoc(['retrieval' => 'triggered', 'summary' => 'x', 'when' => ['has_claim' => true]])->errors())->toBeEmpty()
            ->and(retrievalDoc(['retrieval' => 'triggered', 'summary' => 'x', 'actions' => ['refund']])->errors())->toBeEmpty();
    });

    test('too short triggers and exclusions warn, more than 25 triggers warn', function () {
        $triggers = array_map(fn (int $number): string => "Begriff{$number}", range(1, 26));
        $messages = messagesOf(retrievalDoc(['retrieval' => 'triggered', 'summary' => 'x', 'triggers' => ['Foto', 'neu', ...$triggers], 'exclude_triggers' => ['ja']]), 'warning');

        expect($messages)->toContain('Der Eintrag `neu` in `triggers` ist kürzer als 4 Zeichen')
            ->and($messages)->toContain('Der Eintrag `ja` in `exclude_triggers` ist kürzer als 4 Zeichen')
            ->and($messages)->toContain('28 Auslöser (Warnschwelle: 25)')
            ->and($messages)->not->toContain('`Foto`');
    });

    test('exclusions without triggers do nothing and warn', function () {
        expect(messagesOf(retrievalDoc(['retrieval' => 'triggered', 'summary' => 'x', 'when' => ['has_claim' => true], 'exclude_triggers' => ['Firmenlogo']]), 'warning'))->toContain('`exclude_triggers` ohne `triggers`');
    });

    test('the same trigger in two documents is allowed without any message', function () {
        $library = knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['retrieval' => 'triggered', 'summary' => 'a', 'triggers' => ['Urheberrecht']]),
            'policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002', 'retrieval' => 'triggered', 'summary' => 'b', 'triggers' => ['Urheberrecht']]),
        ]);

        expect($library->issues())->toBeEmpty();
    });
});

describe('binding documents', function () {
    test('without triggers, condition and actions they are fine: they apply in their scope', function () {
        expect(retrievalDoc(['retrieval' => 'binding', 'summary' => 'x'])->issues())->toBeEmpty();
    });

    test('with triggers only they can be overlooked and warn', function () {
        expect(messagesOf(retrievalDoc(['retrieval' => 'binding', 'summary' => 'x', 'triggers' => ['Erstattung']]), 'warning'))->toContain('kann übersehen werden');
    });

    test('with triggers and a condition or actions they raise no warning', function () {
        expect(retrievalDoc(['retrieval' => 'binding', 'summary' => 'x', 'triggers' => ['Erstattung'], 'when' => ['has_claim' => true]])->warnings())->toBeEmpty()
            ->and(retrievalDoc(['retrieval' => 'binding', 'summary' => 'x', 'actions' => ['refund']])->issues())->toBeEmpty();
    });

    test('exclusions are an error', function () {
        expect(messagesOf(retrievalDoc(['retrieval' => 'binding', 'summary' => 'x', 'triggers' => ['Erstattung'], 'exclude_triggers' => ['Probe']]), 'error'))->toContain('dürfen keine `exclude_triggers` haben');
    });
});

describe('core documents', function () {
    test('triggers, exclusions and condition have no effect and warn', function () {
        expect(messagesOf(retrievalDoc(['retrieval' => 'core', 'summary' => 'x', 'triggers' => ['Urheberrecht']]), 'warning'))->toContain('bei `retrieval: core` keine Wirkung');
    });
});

describe('actions on any document', function () {
    test('known actions are fine, unknown ones are an error', function () {
        expect(retrievalDoc(['actions' => ['refund', 'partial-refund']])->issues())->toBeEmpty()
            ->and(messagesOf(retrievalDoc(['actions' => ['teleport']]), 'error'))->toContain('Unbekannter Vorgang `teleport` in `actions`');
    });
});

describe('the when condition', function () {
    test('every key of the start list is accepted with the right kind of value', function () {
        $when = [
            'customer_type' => ['b2c'], 'sales_channel' => 'amazon', 'products' => ['3d-glass-photo'], 'ticket_closed' => false, 'ticket_age_days' => ['min' => 2],
            'customer_message_count' => 3, 'current_message_has_attachment' => true, 'message_foreign' => true, 'order_channel' => ['amazon', 'looxis-de'],
            'order_age_days' => ['max' => 30], 'shipped' => true, 'days_since_shipped' => ['min' => 14, 'max' => 60], 'days_since_last_shipped' => ['min' => 14],
            'shipment_count' => 2, 'delivery_confirmed' => true, 'all_shipments_delivered' => false, 'has_claim' => false, 'claim_count' => ['min' => 1], 'item_quantity_total' => ['min' => 100],
        ];

        expect(retrievalDoc(['retrieval' => 'triggered', 'summary' => 'x', 'when' => $when])->errors())->toBeEmpty();
    });

    test('any_of and not take whole conditions, also nested', function () {
        $when = ['order_channel' => 'amazon', 'any_of' => [['shipped' => true], ['all_shipments_delivered' => true, 'not' => ['has_claim' => true]]], 'not' => ['days_since_shipped' => ['max' => 3]]];

        expect(retrievalDoc(['retrieval' => 'triggered', 'summary' => 'x', 'when' => $when])->errors())->toBeEmpty();
    });

    test('wrong conditions are errors with a plain message', function (mixed $when, string $message) {
        expect(messagesOf(retrievalDoc(['retrieval' => 'triggered', 'summary' => 'x', 'when' => $when]), 'error'))->toContain($message);
    })->with([
        'unknown key' => [['order_value' => ['min' => 5]], 'Unbekannter Schlüssel `order_value`'],
        'not a mapping' => [['shipped', 'has_claim'], 'muss eine Zuordnung'],
        'empty' => [[], 'darf nicht leer sein'],
        'bool as text' => [['shipped' => 'ja'], '`shipped` in `when` muss `true` oder `false` sein'],
        'number as text' => [['order_age_days' => 'alt'], 'braucht eine Zahl oder eine Angabe mit `min`'],
        'negative number' => [['order_age_days' => -2], 'darf nicht negativ sein'],
        'contradicting bounds' => [['order_age_days' => ['min' => 10, 'max' => 5]], 'kann nie zutreffen'],
        'unknown bound' => [['order_age_days' => ['less' => 5]], 'braucht eine Zahl oder eine Angabe mit `min`'],
        'unknown channel' => [['order_channel' => ['ebay']], 'Unbekannter Wert `ebay` bei `order_channel`'],
        'bad slug' => [['products' => ['Glas Foto']], 'braucht einen Slug'],
        'empty any_of' => [['any_of' => []], '`any_of` braucht eine Liste'],
        'any_of with a value' => [['any_of' => [true]], 'muss eine Bedingung sein'],
        'empty not' => [['not' => []], 'darf nicht leer sein'],
        'unknown key in not' => [['not' => ['foo' => 1]], 'Unbekannter Schlüssel `foo`'],
    ]);

    test('a key that is never known for any group a document applies to warns', function () {
        $document = ['retrieval' => 'triggered', 'summary' => 'x', 'when' => ['sales_channel' => ['amazon']]];

        expect(retrievalDoc($document)->warnings())->toBeEmpty();

        config(['knowledge.retrieval.when_keys.sales_channel.unavailable_for_groups' => array_keys(config('knowledge.customer_groups'))]);

        expect(messagesOf(retrievalDoc($document), 'warning'))->toContain('Der Schlüssel `sales_channel` in `when` ist für die Kundengruppen dieses Dokuments nie bekannt');
    });

    test('the schema reports used keys including nested ones', function () {
        $schema = new WhenSchema(config('knowledge.retrieval.when_keys'), []);

        expect($schema->usedKeys(['shipped' => true, 'any_of' => [['has_claim' => true], ['not' => ['order_age_days' => 1]]], 'bogus' => 1]))->toBe(['shipped', 'has_claim', 'order_age_days']);
    });
});

describe('the real knowledge base', function () {
    test('stays free of errors with the new rules in the transition mode', function () {
        config(['knowledge.path' => base_path('knowledge')]);
        app()->forgetScopedInstances();

        expect(app(KnowledgeLibrary::class)->errors())->toBeEmpty();
    });
});
