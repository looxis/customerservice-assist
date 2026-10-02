<?php

use Symfony\Component\Yaml\Yaml;

/**
 * @return array{frontmatter: array<string, mixed>, raw: string, body: string}
 */
function knowledgeFile(string $relativePath): array
{
    $content = file_get_contents(base_path($relativePath));

    expect($content)->toStartWith("---\n");

    [, $raw, $body] = explode("\n---\n", "\n".$content, 3);

    return ['frontmatter' => Yaml::parse($raw), 'raw' => $raw, 'body' => $body];
}

dataset('templates', [
    'policy' => ['policy', 'POLICY-000', ['Regel', 'Hintergrund', 'Gilt wenn', 'Gilt nicht wenn', 'Vorgehen', 'Ausnahmen', 'Eskalieren wenn', 'Beispiele']],
    'permission' => ['permission', 'PERMISSION-000', ['Befugnis', 'Grenzen', 'Freigabe erforderlich wenn', 'Zuständige Rolle', 'Hinweise']],
    'product' => ['product', 'PRODUCT-000', ['Überblick', 'Kundeneingaben und Konfiguration', 'Interne Entscheidungen', 'Herstellungsprozess', 'Technische Grenzen', 'Qualitätsmerkmale', 'Häufige Kundenerwartungen', 'Häufige Missverständnisse', 'Typische Reklamationen', 'Prüfung', 'Konsequenz für den Kundenservice']],
    'process' => ['process', 'PROCESS-000', ['Ziel', 'Auslöser', 'Voraussetzungen', 'Ablauf', 'Ergebnis', 'Sonderfälle', 'Eskalation']],
    'playbook' => ['playbook', 'PLAYBOOK-000', ['Fallmuster', 'Erkennungsmerkmale', 'Typische Ursache', 'Zu prüfen', 'Berechtigt wenn', 'Nicht berechtigt wenn', 'Noch nicht entscheidbar wenn', 'Fehlende Informationen', 'Empfohlene Maßnahme', 'Befugnis', 'Kommunikation', 'No-Gos', 'Beispiel']],
    'tone' => ['tone', 'TONE-000', ['Ziel', 'Grundton', 'Bevorzugte Formulierungen', 'Vermeiden', 'Reklamationen', 'Ablehnungen', 'Empathie']],
    'glossary' => ['glossary', 'GLOSSARY-000', ['Begriff', 'Bedeutung', 'Abgrenzung', 'Relevanz für den Kundenservice']],
    'example-good' => ['example-good', 'EXAMPLE-GOOD-000', ['Kundensituation', 'Relevante Fakten', 'Richtige Bewertung', 'Richtige Maßnahme', 'Warum?', 'Gute Kommunikationsstrategie', 'Lernpunkt']],
    'example-bad' => ['example-bad', 'EXAMPLE-BAD-000', ['Kundensituation', 'Was wurde gemacht?', 'Warum war das falsch?', 'Richtige Bewertung', 'Richtige Maßnahme', 'Ursache der Fehlentscheidung', 'Lernpunkt']],
]);

describe('folder structure', function () {
    test('every knowledge folder exists in a fresh checkout', function (string $folder) {
        expect(is_dir(base_path("knowledge/{$folder}")))->toBeTrue();

        $tracked = glob(base_path("knowledge/{$folder}/{,.}*"), GLOB_BRACE);
        $files = array_filter($tracked, 'is_file');

        expect($files)->not->toBeEmpty("knowledge/{$folder} holds no file, so git would drop it");
    })->with(['policies', 'permissions', 'products', 'processes', 'playbooks', 'tone', 'glossary', 'examples/good', 'examples/bad', 'templates']);

    test('the existing knowledge files are still present and readable', function () {
        $files = array_merge(glob(base_path('knowledge/policies/*.md')), glob(base_path('knowledge/permissions/*.md')));

        expect(count($files))->toBeGreaterThanOrEqual(8);

        foreach ($files as $file) {
            $parsed = knowledgeFile(str_replace(base_path().'/', '', $file));

            expect($parsed['frontmatter'])->toHaveKeys(['id', 'title', 'type', 'status'])
                ->and($parsed['frontmatter']['id'])->not->toEndWith('-000');
        }
    });
});

describe('readme', function () {
    test('it explains purpose, folders, precedence, status and workflow and links the rules', function () {
        $readme = file_get_contents(base_path('knowledge/README.md'));

        expect($readme)
            ->toContain('Source of Truth')
            ->toContain('`policies/`', '`permissions/`', '`products/`', '`processes/`', '`playbooks/`', '`tone/`', '`glossary/`', '`examples/good/`', '`examples/bad/`', '`templates/`')
            ->toContain('## Rangfolge bei Widersprüchen')
            ->toContain('`draft`', '`active`', '`deprecated`')
            ->toContain('## So entsteht eine Datei')
            ->toContain('docs/KNOWLEDGE_AUTHORING_GUIDE.md', 'docs/KNOWLEDGE_BASE_DESIGN.md');

        expect(strpos($readme, '1. Policies'))->toBeLessThan(strpos($readme, '2. Permissions'))
            ->and(strpos($readme, '4. Playbooks'))->toBeLessThan(strpos($readme, '5. Beispiele'));
    });

    test('its links to the rule documents resolve', function () {
        preg_match_all('/\]\((\.\.\/[^)]+|templates\/)\)/', file_get_contents(base_path('knowledge/README.md')), $links);

        expect($links[1])->not->toBeEmpty();

        foreach ($links[1] as $link) {
            expect(file_exists(base_path('knowledge/'.$link)))->toBeTrue("broken link: {$link}");
        }
    });
});

describe('templates', function () {
    test('there is exactly one template per document type', function () {
        $names = array_map(fn (string $path) => basename($path, '.md'), glob(base_path('knowledge/templates/*.md')));
        sort($names);

        expect($names)->toBe(['example-bad', 'example-good', 'glossary', 'permission', 'playbook', 'policy', 'process', 'product', 'tone']);
    });

    test('the frontmatter is valid YAML with required fields, placeholder id and draft status', function (string $type, string $id) {
        $frontmatter = knowledgeFile("knowledge/templates/{$type}.md")['frontmatter'];

        expect($frontmatter['id'])->toBe($id)
            ->and($frontmatter['type'])->toBe($type)
            ->and($frontmatter['status'])->toBe('draft')
            ->and($frontmatter)->toHaveKeys(['title', 'customer_types', 'sales_channels', 'related_knowledge', 'topics']);
    })->with('templates');

    test('empty fields are really empty, not placeholder text', function (string $type) {
        $frontmatter = knowledgeFile("knowledge/templates/{$type}.md")['frontmatter'];

        unset($frontmatter['id'], $frontmatter['type'], $frontmatter['status']);

        expect(array_filter($frontmatter, fn ($value) => $value !== null))->toBeEmpty();
    })->with('templates');

    test('it is marked as a template', function (string $type) {
        expect(knowledgeFile("knowledge/templates/{$type}.md")['raw'])->toContain('# VORLAGE');
    })->with('templates');

    test('the body starts with the scope line followed by the sections of its type in order', function (string $type, string $id, array $sections) {
        $body = knowledgeFile("knowledge/templates/{$type}.md")['body'];

        expect(ltrim($body))->toStartWith('Gilt für alle Kundenarten und Vertriebskanäle.');

        preg_match_all('/^# (.+)$/m', $body, $headings);

        expect($headings[1])->toBe($sections);
    })->with('templates');

    test('the type specific fields from the design document are present', function (string $type, array $fields) {
        expect(knowledgeFile("knowledge/templates/{$type}.md")['frontmatter'])->toHaveKeys($fields);
    })->with([
        ['policy', ['products', 'categories', 'priority', 'owner', 'last_reviewed']],
        ['permission', ['action', 'agent_allowed', 'max_value_eur', 'approval_role', 'owner', 'last_reviewed']],
        ['product', ['products', 'owner', 'last_reviewed']],
        ['process', ['products', 'categories', 'owner', 'last_reviewed']],
        ['playbook', ['products', 'categories', 'priority', 'risk_level', 'owner', 'last_reviewed']],
        ['tone', ['categories', 'owner', 'last_reviewed']],
        ['glossary', ['owner', 'last_reviewed']],
        ['example-good', ['products', 'categories']],
        ['example-bad', ['products', 'categories']],
    ]);

    test('templates use LF line endings and UTF-8', function (string $type) {
        $content = file_get_contents(base_path("knowledge/templates/{$type}.md"));

        expect($content)->not->toContain("\r")
            ->and(mb_check_encoding($content, 'UTF-8'))->toBeTrue();
    })->with('templates');
});

describe('skill', function () {
    test('the /knowledge skill is defined with its guard rails', function () {
        $skill = file_get_contents(base_path('.claude/skills/knowledge/SKILL.md'));

        expect($skill)
            ->toContain('name: knowledge', 'user-invocable: true')
            ->toContain('docs/KNOWLEDGE_AUTHORING_GUIDE.md', 'docs/KNOWLEDGE_BASE_DESIGN.md', 'knowledge/templates/')
            ->toContain('never reuse an ID', '`000` is reserved')
            ->toContain('status: draft')
            ->toContain('Do NOT set `status: active`')
            ->toContain('Do NOT commit or push')
            ->toContain('No personal data')
            ->toContain('extend that file instead of creating a second one')
            ->toContain('If two files share an ID, report it and stop');
    });
});

describe('guide', function () {
    test('the guide and the templates agree on types, folders and scope fields', function () {
        $guide = file_get_contents(base_path('docs/KNOWLEDGE_AUTHORING_GUIDE.md'));

        expect($guide)->toContain('knowledge/templates/', '`customer_types`', '`sales_channels`', '`related_knowledge`', 'Nummer 000');

        foreach (glob(base_path('knowledge/templates/*.md')) as $template) {
            $type = basename($template, '.md');
            $folder = match ($type) {
                'policy' => 'policies',
                'permission' => 'permissions',
                'product' => 'products',
                'process' => 'processes',
                'playbook' => 'playbooks',
                'example-good' => 'examples/good',
                'example-bad' => 'examples/bad',
                default => $type,
            };

            expect($guide)->toContain("| {$type} | `knowledge/{$folder}/`");
        }
    });
});
