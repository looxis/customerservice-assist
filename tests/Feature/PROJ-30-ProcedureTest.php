<?php

use App\Knowledge\CaseContext;
use App\Knowledge\KnowledgeSelector;

afterEach(fn () => cleanUpKnowledgeBases());

function procedureDoc(array $overrides = [], ?string $body = null): string
{
    return knowledgeDoc(
        array_merge(['id' => 'PROCEDURE-001', 'type' => 'procedure', 'actions' => ['reshipment']], $overrides),
        $body ?? "Gilt für alle.\n\n# Zweck\n\nNeuversand anlegen.\n\n# Voraussetzungen\n\n- Freigabe liegt vor\n\n# Arbeitsschritte\n\n1. In EOCS Auftrag öffnen\n\n# Kritische Hinweise\n\n- Achtung: Adresse prüfen\n\n# Abschlusskontrolle\n\n- [ ] Neuer Auftrag angelegt",
    );
}

describe('document type', function () {
    test('a procedure in its folder with actions and all sections is valid', function () {
        $library = knowledgeBase(['procedures/procedure-001-neuversand.md' => procedureDoc()]);

        expect($library->issues())->toBeEmpty()
            ->and($library->find('PROCEDURE-001')->actions())->toBe(['reshipment']);
    });

    test('missing or unknown actions are errors that name the allowed values', function (array $overrides, string $expected) {
        $library = knowledgeBase(['procedures/procedure-001-neuversand.md' => procedureDoc($overrides)]);

        expect(messagesOf($library, 'error'))->toContain($expected)->toContain('Erlaubt: return, reshipment, reproduction')
            ->and($library->usable())->toBeEmpty();
    })->with([
        'missing' => [['actions' => null], 'Arbeitsablauf ohne `actions`'],
        'unknown' => [['actions' => ['neuversand']], 'Unbekannter Vorgang `neuversand` in `actions`'],
    ]);

    test('missing required sections warn, critical notes are optional', function () {
        $library = knowledgeBase(['procedures/procedure-001-neuversand.md' => procedureDoc(body: "Gilt für alle.\n\n# Arbeitsschritte\n\n1. Schritt")]);

        expect(messagesOf($library, 'warning'))
            ->toContain('Abschnitt „# Voraussetzungen" fehlt.')
            ->toContain('Abschnitt „# Abschlusskontrolle" fehlt.')
            ->not->toContain('# Arbeitsschritte" fehlt')
            ->not->toContain('Kritische Hinweise');
    });

    test('actions is an unknown field outside procedures', function () {
        $library = knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['actions' => ['refund']])]);

        expect(messagesOf($library, 'warning'))->toContain('Unbekanntes Feld `actions`');
    });

    test('a new action in the configuration is valid without code changes', function () {
        config(['knowledge.actions.gift-card' => 'Gutschein']);

        expect(knowledgeBase(['procedures/procedure-001-a.md' => procedureDoc(['actions' => ['gift-card']])])->issues())->toBeEmpty();
    });

    test('a permission action outside the list warns', function () {
        $library = knowledgeBase([
            'permissions/permission-001-a.md' => knowledgeDoc(['id' => 'PERMISSION-001', 'type' => 'permission', 'action' => 'replacement', 'agent_allowed' => true]),
            'permissions/permission-002-b.md' => knowledgeDoc(['id' => 'PERMISSION-002', 'type' => 'permission', 'action' => 'refund', 'agent_allowed' => true]),
        ]);

        expect(messagesOf($library, 'warning'))->toContain('permissions/permission-001-a.md: Unbekannter Vorgang `replacement` in `action`')
            ->not->toContain('permission-002-b.md')
            ->and(messagesOf($library, 'error'))->toBe('');
    });
});

describe('knowledge selection for the language model', function () {
    test('procedures are never selected, not even listed as left out', function () {
        knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(),
            'procedures/procedure-001-neuversand.md' => procedureDoc(),
        ]);

        $selector = app(KnowledgeSelector::class);
        $selection = $selector->select(new CaseContext($selector->customerGroup('private-looxis-de')));

        expect(array_map(fn ($entry) => $entry->document->id, $selection->selected))->toBe(['POLICY-001'])
            ->and($selection->excluded)->toBe([]);
    });
});

describe('authoring', function () {
    test('the id overview names the procedure type and the allowed actions', function () {
        $overview = knowledgeBase(['procedures/procedure-001-neuversand.md' => procedureDoc(['title' => 'Neuversand anlegen'])])->overview();

        expect($overview)->toContain("procedure (nächste freie ID: PROCEDURE-002):\n  PROCEDURE-001 – Neuversand anlegen")
            ->toContain("Erlaubte Vorgänge (actions):\nreturn (Retoure), reshipment (Neuversand)");
    });

    test('template, guide, readme and skill describe procedures', function () {
        expect(file_get_contents(base_path('knowledge/templates/procedure.md')))
            ->toContain('type: procedure')->toContain('actions:')
            ->toContain('# Voraussetzungen')->toContain('# Arbeitsschritte')->toContain('# Kritische Hinweise')->toContain('# Abschlusskontrolle')
            ->and(file_get_contents(base_path('docs/KNOWLEDGE_AUTHORING_GUIDE.md')))
            ->toContain('### Arbeitsabläufe (`procedure`)')->toContain('| `reshipment` | Neuversand |')
            ->and(file_get_contents(base_path('knowledge/README.md')))->toContain('`procedures/`')
            ->and(file_get_contents(base_path('.claude/skills/knowledge/SKILL.md')))->toContain('For procedures');
    });

    test('the knowledge overview page lists procedures under their own type', function () {
        $this->withoutVite();
        knowledgeBase(['procedures/procedure-001-neuversand.md' => procedureDoc(['title' => 'Neuversand anlegen'])]);

        $this->get('/knowledge')->assertSeeTextInOrder(['Arbeitsabläufe', 'PROCEDURE-001', 'Neuversand anlegen']);
    });
});
