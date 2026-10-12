<?php

use Symfony\Component\Yaml\Yaml;

describe('the templates', function () {
    test('every template carries the retrieval fields, empty', function (string $type) {
        $raw = file_get_contents(base_path("knowledge/templates/{$type}.md"));
        preg_match('/^---\n(.*?)\n---/s', $raw, $match);
        $frontmatter = Yaml::parse($match[1]);

        expect($frontmatter)->toHaveKeys(['summary', 'retrieval', 'triggers', 'exclude_triggers', 'when', 'actions'])
            ->and($frontmatter['summary'])->toBeNull()->and($frontmatter['retrieval'])->toBeNull()->and($frontmatter['triggers'])->toBeNull()
            ->and(substr_count($match[1], "\nactions:"))->toBe(1)
            ->and($raw)->toContain('Abruf (docs/KNOWLEDGE_AUTHORING_GUIDE.md');
    })->with(['policy', 'permission', 'product', 'process', 'procedure', 'playbook', 'tone', 'glossary', 'example-good', 'example-bad']);
});

describe('the authoring guide', function () {
    $guide = fn (): string => file_get_contents(base_path('docs/KNOWLEDGE_AUTHORING_GUIDE.md'));

    test('it explains the retrieval fields, the classes and the rules for triggers', function () use ($guide) {
        expect($guide())->toContain('### Abruf: `summary`, `retrieval`, `triggers`, `exclude_triggers`, `when`, `actions`')
            ->toContain('`core`')->toContain('`triggered`')->toContain('`binding`')
            ->toContain('Ein Produkt ist **kein Auslöser**')
            ->toContain('„Logo“ findet „Firmenlogo“ nicht')
            ->toContain('Keine Allerweltswörter')
            ->toContain('Ausschlüsse wirken nie in Fragen')
            ->toContain('Mehrere Bestellbedingungen gelten für **dieselbe** Bestellung')
            ->toContain('Fehlende Daten sind „unbekannt“')
            ->toContain('Welcher ähnliche Fall soll sie **ausdrücklich nicht** auslösen?');
    });

    test('it says that categories filters nothing yet and adds the retrieval check to the final check', function () use ($guide) {
        expect($guide())->toContain('`categories` filtert Wissen heute noch nicht')
            ->toContain('- Abruf: `summary` und `retrieval` sind gesetzt')
            ->toContain('Klassen für retrieval, Grundwissen mit Zeichenzahl');
    });

    test('every field it names for when exists in the configuration', function () use ($guide) {
        preg_match('/```yaml\n  when:\n(.*?)```/s', $guide(), $match);
        preg_match_all('/^\s+(\w+):/m', $match[1], $keys);

        foreach (array_diff($keys[1], ['not']) as $key) {
            expect(config('knowledge.retrieval.when_keys'))->toHaveKey($key);
        }
    });
});

describe('the handbook for the chat', function () {
    test('it is one short page that names the two stages, the three classes and the questions', function () {
        $handbook = file_get_contents(base_path('docs/KNOWLEDGE_CHAT_HANDBUCH.md'));

        expect($handbook)->toContain('Die Auswahl in zwei Stufen')->toContain('`core`')->toContain('`triggered`')->toContain('`binding`')
            ->toContain('Ein leeres oder fehlendes Feld ist nie „egal“')->toContain('Fragen, die du stellst')->toContain('knowledge:select')
            ->and(substr_count($handbook, "\n"))->toBeLessThan(60);
    });
});
