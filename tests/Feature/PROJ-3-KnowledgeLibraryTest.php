<?php

use App\Knowledge\KnowledgeIssue;
use App\Knowledge\KnowledgeLibrary;
use App\Knowledge\KnowledgeState;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Yaml\Yaml;

/**
 * Build a throwaway knowledge base and return the library reading it.
 *
 * @param  array<string, string>  $files  Relative path => file content.
 */
function knowledgeBase(array $files): KnowledgeLibrary
{
    $root = sys_get_temp_dir().'/knowledge-test-'.bin2hex(random_bytes(6));

    File::ensureDirectoryExists($root);

    foreach ($files as $path => $content) {
        File::ensureDirectoryExists(dirname("{$root}/{$path}"));
        File::put("{$root}/{$path}", $content);
    }

    config(['knowledge.path' => $root]);
    app()->forgetScopedInstances();

    return app(KnowledgeLibrary::class);
}

/**
 * A valid knowledge file; overrides change or remove (null) frontmatter fields.
 *
 * @param  array<string, mixed>  $overrides
 */
function knowledgeDoc(array $overrides = [], string $body = "Gilt für alle Kundenarten.\n\n# Regel\n\nEin Satz."): string
{
    $frontmatter = array_merge(['id' => 'POLICY-001', 'title' => 'Titel', 'type' => 'policy', 'status' => 'draft'], $overrides);

    return "---\n".Yaml::dump($frontmatter)."---\n\n".$body."\n";
}

function messagesOf(KnowledgeLibrary $library, string $severity): string
{
    return ($severity === 'error' ? $library->errors() : $library->warnings())
        ->map(fn (KnowledgeIssue $issue): string => "{$issue->path}: {$issue->message}")
        ->implode("\n");
}

afterEach(function () {
    foreach (File::glob(sys_get_temp_dir().'/knowledge-test-*') as $directory) {
        File::deleteDirectory($directory);
    }
});

describe('reading', function () {
    test('every markdown file in a type folder becomes a document with frontmatter and body', function () {
        $library = knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['topics' => ['refund']]),
            'playbooks/playbook-001-b.md' => knowledgeDoc(['id' => 'PLAYBOOK-001', 'type' => 'playbook', 'categories' => ['complaint']]),
        ]);

        expect($library->all())->toHaveCount(2)
            ->and($library->issues())->toBeEmpty();

        $policy = $library->find('POLICY-001');

        expect($policy->title)->toBe('Titel')
            ->and($policy->type)->toBe('policy')
            ->and($policy->status)->toBe('draft')
            ->and($policy->topics())->toBe(['refund'])
            ->and($policy->body)->toStartWith('Gilt für alle Kundenarten.')
            ->and($policy->path)->toBe('policies/policy-001-a.md');
    });

    test('readme, templates and non-markdown files are ignored without any message', function () {
        $library = knowledgeBase([
            'README.md' => '# Knowledge Base',
            'templates/policy.md' => knowledgeDoc(['id' => 'POLICY-000']),
            'products/.gitkeep' => '',
            'policies/policy-001-a.md:Zone.Identifier' => '[ZoneTransfer]',
            'policies/notes.txt' => 'nur eine Notiz',
            'policies/policy-001-a.md' => knowledgeDoc(),
        ]);

        expect($library->all())->toHaveCount(1)
            ->and($library->issues())->toBeEmpty();
    });

    test('draft and active documents are usable, deprecated ones are not', function () {
        $library = knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['status' => 'draft']),
            'policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002', 'status' => 'active']),
            'policies/policy-003-c.md' => knowledgeDoc(['id' => 'POLICY-003', 'status' => 'deprecated']),
        ]);

        expect($library->usable()->pluck('id')->all())->toBe(['POLICY-001', 'POLICY-002'])
            ->and($library->all())->toHaveCount(3)
            ->and($library->find('POLICY-003')->status)->toBe('deprecated');
    });

    test('empty list fields are empty lists and a single value counts as a one-item list', function () {
        $library = knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['products' => null, 'customer_types' => 'b2c', 'categories' => ['complaint']]),
        ]);

        $document = $library->find('POLICY-001');

        expect($document->products())->toBe([])
            ->and($document->salesChannels())->toBe([])
            ->and($document->relatedKnowledge())->toBe([])
            ->and($document->customerTypes())->toBe(['b2c'])
            ->and($document->categories())->toBe(['complaint'])
            ->and($library->issues())->toBeEmpty();
    });

    test('a changed file is visible on the next read without restarting', function () {
        $library = knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc()]);

        expect($library->all())->toHaveCount(1);

        File::put(config('knowledge.path').'/policies/policy-002-b.md', knowledgeDoc(['id' => 'POLICY-002']));
        app()->forgetScopedInstances();

        expect(app(KnowledgeLibrary::class)->all())->toHaveCount(2);
    });

    test('files in a subfolder of a type folder belong to that type', function () {
        $library = knowledgeBase([
            'products/3d-glass-photo/overview.md' => knowledgeDoc(['id' => 'PRODUCT-001', 'type' => 'product', 'products' => ['3d-glass-photo']]),
        ]);

        expect($library->errors())->toBeEmpty()
            ->and($library->find('PRODUCT-001')->folderType)->toBe('product');
    });

    test('windows line endings, a byte order mark and dashes in the body are read correctly', function () {
        $content = knowledgeDoc(body: "Gilt für alle.\n\n# Regel\n\nOben.\n\n---\n\nUnten.");

        $library = knowledgeBase(['policies/policy-001-a.md' => "\xEF\xBB\xBF".str_replace("\n", "\r\n", $content)]);

        expect($library->issues())->toBeEmpty()
            ->and($library->find('POLICY-001')->body)->toContain("Oben.\n\n---\n\nUnten.");
    });

    test('dates and numbers in the frontmatter are accepted', function () {
        $library = knowledgeBase([
            'permissions/permission-001-a.md' => "---\nid: PERMISSION-001\ntitle: Titel\ntype: permission\nstatus: draft\naction: refund\nagent_allowed: true\nmax_value_eur: 35\nlast_reviewed: 2026-10-01\n---\n\nText.\n",
        ]);

        expect($library->issues())->toBeEmpty();
    });
});

describe('errors exclude a document', function () {
    test('faulty documents are reported and not usable', function (array $files, string $expected) {
        $library = knowledgeBase($files);

        expect(messagesOf($library, 'error'))->toContain($expected)
            ->and($library->usable())->toBeEmpty();
    })->with([
        'no frontmatter' => [['policies/policy-001-a.md' => "# Regel\n\nText."], 'Das Frontmatter fehlt'],
        'frontmatter not closed' => [['policies/policy-001-a.md' => "---\nid: POLICY-001\ntitle: Titel\n\n# Regel"], 'nicht abgeschlossen'],
        'invalid yaml' => [['policies/policy-001-a.md' => "---\nid: POLICY-001\ntitle: Regel: mit Doppelpunkt: zweimal\ntype: policy\nstatus: draft\n---\n\nText."], 'kein gültiges YAML'],
        'frontmatter without fields' => [['policies/policy-001-a.md' => "---\n---\n\nText."], 'enthält keine Felder'],
        'missing id' => [['policies/policy-001-a.md' => knowledgeDoc(['id' => null])], 'Pflichtfeld `id`'],
        'missing title' => [['policies/policy-001-a.md' => knowledgeDoc(['title' => ''])], 'Pflichtfeld `title`'],
        'missing type' => [['policies/policy-001-a.md' => knowledgeDoc(['type' => null])], 'Pflichtfeld `type`'],
        'missing status' => [['policies/policy-001-a.md' => knowledgeDoc(['status' => null])], 'Pflichtfeld `status`'],
        'unknown type' => [['policies/policy-001-a.md' => knowledgeDoc(['type' => 'rule'])], 'Unbekannter Typ `rule`. Erlaubt: policy, permission'],
        'unknown status' => [['policies/policy-001-a.md' => knowledgeDoc(['status' => 'final'])], 'Unbekannter Status `final`. Erlaubt: draft, active, deprecated'],
        'id does not match the type' => [['policies/policy-001-a.md' => knowledgeDoc(['id' => 'PLAYBOOK-001'])], 'passt nicht zum Schema POLICY-001'],
        'id without three digits' => [['policies/policy-1-a.md' => knowledgeDoc(['id' => 'POLICY-1'])], 'passt nicht zum Schema'],
        'template number 000' => [['policies/policy-000-a.md' => knowledgeDoc(['id' => 'POLICY-000'])], 'Die Nummer 000 ist für Vorlagen reserviert'],
        'type does not match the folder' => [['playbooks/policy-001-a.md' => knowledgeDoc()], 'gehören nach policies/'],
        'file outside a type folder' => [['policy-001-a.md' => knowledgeDoc()], 'liegt in keinem Typ-Ordner'],
        'file in an unknown folder' => [['rules/policy-001-a.md' => knowledgeDoc()], 'liegt in keinem Typ-Ordner'],
        'unknown customer type' => [['policies/policy-001-a.md' => knowledgeDoc(['customer_types' => ['reseller']])], 'Unbekannter Wert `reseller` in `customer_types`. Erlaubt: b2c, b2b'],
        'unknown sales channel' => [['policies/policy-001-a.md' => knowledgeDoc(['sales_channels' => ['etsy']])], 'Unbekannter Wert `etsy` in `sales_channels`'],
        'unknown category' => [['policies/policy-001-a.md' => knowledgeDoc(['categories' => ['reklamation']])], 'Unbekannter Wert `reklamation` in `categories`'],
        'nested list' => [['policies/policy-001-a.md' => knowledgeDoc(['topics' => [['a' => 'b']]])], 'Feld `topics` muss eine einfache Liste'],
        'permission without action' => [['permissions/permission-001-a.md' => knowledgeDoc(['id' => 'PERMISSION-001', 'type' => 'permission', 'agent_allowed' => true])], 'Permission ohne `action`'],
        'permission without agent_allowed' => [['permissions/permission-001-a.md' => knowledgeDoc(['id' => 'PERMISSION-001', 'type' => 'permission', 'action' => 'refund'])], '`agent_allowed` muss `true` oder `false` sein'],
        'permission with non-numeric limit' => [['permissions/permission-001-a.md' => knowledgeDoc(['id' => 'PERMISSION-001', 'type' => 'permission', 'action' => 'refund', 'agent_allowed' => true, 'max_value_eur' => '35 Euro'])], '`max_value_eur` muss eine Zahl'],
        'empty body' => [['policies/policy-001-a.md' => knowledgeDoc(body: '')], 'Der Textteil ist leer'],
        'not utf-8' => [['policies/policy-001-a.md' => mb_convert_encoding(knowledgeDoc(['title' => 'Rücknahme']), 'ISO-8859-1', 'UTF-8')], 'nicht als UTF-8 gespeichert'],
    ]);

    test('the yaml error names the line', function () {
        $library = knowledgeBase(['policies/policy-001-a.md' => "---\nid: POLICY-001\ntitle: Regel: mit Doppelpunkt: zweimal\n---\n\nText."]);

        expect(messagesOf($library, 'error'))->toMatch('/Zeile \d+/');
    });

    test('a duplicate id excludes both documents and each error names the other file', function () {
        $library = knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(),
            'policies/policy-001-b.md' => knowledgeDoc(['status' => 'deprecated']),
            'policies/policy-002-c.md' => knowledgeDoc(['id' => 'POLICY-002']),
        ]);

        expect(messagesOf($library, 'error'))
            ->toContain('policies/policy-001-a.md: Die ID `POLICY-001` ist doppelt vergeben, auch in: policies/policy-001-b.md.')
            ->toContain('policies/policy-001-b.md: Die ID `POLICY-001` ist doppelt vergeben, auch in: policies/policy-001-a.md.')
            ->and($library->usable()->pluck('id')->all())->toBe(['POLICY-002'])
            ->and($library->find('POLICY-001'))->toBeNull();
    });

    test('one faulty file does not affect the others', function () {
        $library = knowledgeBase([
            'policies/policy-001-a.md' => 'kaputt',
            'policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002']),
            'policies/policy-003-c.md' => knowledgeDoc(['id' => 'POLICY-003']),
        ]);

        expect($library->usable()->pluck('id')->all())->toBe(['POLICY-002', 'POLICY-003'])
            ->and($library->all())->toHaveCount(3)
            ->and($library->errors())->toHaveCount(1);
    });
});

describe('warnings keep a document usable', function () {
    test('quality problems are reported as warnings', function (array $files, string $expected) {
        $library = knowledgeBase($files);

        expect(messagesOf($library, 'warning'))->toContain($expected)
            ->and($library->errors())->toBeEmpty()
            ->and($library->usable())->toHaveCount(count($files));
    })->with([
        'related_knowledge points nowhere' => [['policies/policy-001-a.md' => knowledgeDoc(['related_knowledge' => ['POLICY-099']])], 'Verweis auf `POLICY-099`, aber dieses Dokument gibt es nicht'],
        'body points nowhere' => [['policies/policy-001-a.md' => knowledgeDoc(body: 'Dann gilt PLAYBOOK-007 und EXAMPLE-GOOD-002.')], 'Verweis auf `EXAMPLE-GOOD-002`'],
        'active with open questions' => [['policies/policy-001-a.md' => knowledgeDoc(['status' => 'active'], "# Regel\n\nText.\n\n# Noch zu klären\n\n- Punkt")], 'Aktives Dokument mit Abschnitt „Noch zu klären"'],
        'playbook without category' => [['playbooks/playbook-001-a.md' => knowledgeDoc(['id' => 'PLAYBOOK-001', 'type' => 'playbook'])], 'brauchen mindestens eine Kategorie'],
        'process without category' => [['processes/process-001-a.md' => knowledgeDoc(['id' => 'PROCESS-001', 'type' => 'process'])], 'brauchen mindestens eine Kategorie'],
        'file name does not match the id' => [['policies/policy-002-a.md' => knowledgeDoc()], 'Der Dateiname sollte dem Schema policy-001-kurzer-titel.md folgen'],
        'file name with capitals' => [['policies/policy-001-Titel.md' => knowledgeDoc()], 'Der Dateiname sollte dem Schema'],
        'product file name is no slug' => [['products/3D Glas.md' => knowledgeDoc(['id' => 'PRODUCT-001', 'type' => 'product'])], 'Der Dateiname einer Produktdatei ist der Produkt-Slug'],
        'topic is no slug' => [['policies/policy-001-a.md' => knowledgeDoc(['topics' => ['Rückgabe Wunsch']])], 'Wert `Rückgabe Wunsch` in `topics` ist kein Slug'],
        'product without product file' => [['policies/policy-001-a.md' => knowledgeDoc(['products' => ['lunchbox']])], 'Für das Produkt `lunchbox` gibt es keine Produktdatei'],
        'unknown field' => [['policies/policy-001-a.md' => knowledgeDoc(['catagories' => ['complaint']])], 'Unbekanntes Feld `catagories`'],
        'email address' => [['policies/policy-001-a.md' => knowledgeDoc(body: 'Der Kunde max.mustermann@example.com schrieb.')], 'Mögliche personenbezogene Daten'],
        'long digit sequence' => [['policies/policy-001-a.md' => knowledgeDoc(body: 'Bestellung 4711000123 wurde reklamiert.')], 'Mögliche personenbezogene Daten'],
        'very long body' => [['policies/policy-001-a.md' => knowledgeDoc(body: str_repeat('Wort ', 1700))], 'sehr lang'],
    ]);

    test('a reference to a deprecated document is a warning', function () {
        $library = knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['related_knowledge' => ['POLICY-002']]),
            'policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002', 'status' => 'deprecated']),
        ]);

        expect(messagesOf($library, 'warning'))->toContain('Verweis auf `POLICY-002`, das als deprecated markiert ist');
    });

    test('valid references, open questions in drafts and unlimited permissions raise nothing', function () {
        $library = knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['related_knowledge' => ['PERMISSION-001'], 'products' => ['lunchbox']], "# Regel\n\nSiehe POLICY-001 und PERMISSION-001. Bis 35 Euro, 50 bis 80 Prozent.\n\n# Noch zu klären\n\n- Punkt"),
            'permissions/permission-001-a.md' => knowledgeDoc(['id' => 'PERMISSION-001', 'type' => 'permission', 'action' => 'replacement', 'agent_allowed' => true, 'max_value_eur' => null, 'approval_role' => null]),
            'products/lunchbox.md' => knowledgeDoc(['id' => 'PRODUCT-001', 'type' => 'product', 'products' => ['lunchbox']]),
        ]);

        expect($library->issues())->toBeEmpty();
    });
});

describe('behaviour for the app', function () {
    test('a missing knowledge folder yields no documents and one clear message', function () {
        config(['knowledge.path' => sys_get_temp_dir().'/knowledge-test-gibt-es-nicht']);
        app()->forgetScopedInstances();

        $library = app(KnowledgeLibrary::class);

        expect($library->all())->toBeEmpty()
            ->and($library->usable())->toBeEmpty()
            ->and(messagesOf($library, 'error'))->toContain('Der Knowledge-Ordner fehlt')
            ->and($library->state()->label())->toBeString();
    });

    test('an empty knowledge folder yields no documents and a warning', function () {
        $library = knowledgeBase(['README.md' => '# leer']);

        expect($library->all())->toBeEmpty()
            ->and($library->errors())->toBeEmpty()
            ->and(messagesOf($library, 'warning'))->toContain('enthält keine Dokumente');
    });

    test('the library is shared within a request and reads the files only once', function () {
        $library = knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc()]);

        expect(app(KnowledgeLibrary::class))->toBe($library);

        $library->all();
        File::delete(config('knowledge.path').'/policies/policy-001-a.md');

        expect($library->all())->toHaveCount(1);

        $library->refresh();

        expect($library->all())->toBeEmpty();
    });

    test('the real knowledge base has no errors', function () {
        $library = app(KnowledgeLibrary::class);

        expect(messagesOf($library, 'error'))->toBe('')
            ->and($library->usable()->count())->toBeGreaterThanOrEqual(8);
    });

    test('reading and checking 200 documents takes less than a second', function () {
        $files = [];

        foreach (range(1, 200) as $number) {
            $id = sprintf('POLICY-%03d', $number);
            $files['policies/'.strtolower($id).'-a.md'] = knowledgeDoc(['id' => $id, 'topics' => ['refund']], str_repeat("Ein Satz mit Verweis auf POLICY-001.\n", 60));
        }

        $library = knowledgeBase($files);
        $start = microtime(true);

        expect($library->usable())->toHaveCount(200)
            ->and(microtime(true) - $start)->toBeLessThan(1.0);
    });
});

describe('fingerprint', function () {
    test('it is stable for the same content, independent of line endings and byte order mark', function () {
        $content = knowledgeDoc();

        $unix = knowledgeBase(['policies/policy-001-a.md' => $content])->find('POLICY-001')->fingerprint;
        $windows = knowledgeBase(['policies/policy-001-a.md' => "\xEF\xBB\xBF".str_replace("\n", "\r\n", $content)])->find('POLICY-001')->fingerprint;

        expect($unix)->toBe($windows)->toHaveLength(64);
    });

    test('it changes with every change to frontmatter or body', function () {
        $original = knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc()])->find('POLICY-001')->fingerprint;
        $otherTitle = knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Anderer Titel'])])->find('POLICY-001')->fingerprint;
        $otherBody = knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(body: "# Regel\n\nAnders.")])->find('POLICY-001')->fingerprint;

        expect($otherTitle)->not->toBe($original)
            ->and($otherBody)->not->toBe($original)
            ->and($otherBody)->not->toBe($otherTitle);
    });
});

describe('knowledge state', function () {
    test('it contains the short hash and date of the last commit', function () {
        Process::fake([
            '*log*' => Process::result("97af4ee|2026-10-02T08:34:41+02:00\n"),
            '*status*' => Process::result(''),
        ]);

        $state = KnowledgeState::detect(base_path('knowledge'), null);

        expect($state->commit)->toBe('97af4ee')
            ->and($state->committedAt->format('Y-m-d'))->toBe('2026-10-02')
            ->and($state->dirty)->toBeFalse()
            ->and($state->label())->toBe('97af4ee vom 02.10.2026');
    });

    test('uncommitted knowledge changes are flagged', function () {
        Process::fake([
            '*log*' => Process::result("97af4ee|2026-10-02T08:34:41+02:00\n"),
            '*status*' => Process::result("?? policies/policy-007-neu.md\n"),
        ]);

        $state = KnowledgeState::detect(base_path('knowledge'), null);

        expect($state->dirty)->toBeTrue()
            ->and($state->label())->toBe('97af4ee vom 02.10.2026, mit uncommitteten Änderungen');
    });

    test('without git the commit written at deployment is used', function () {
        Process::fake(['*' => Process::result('', 'fatal: not a git repository', 128)]);

        $state = KnowledgeState::detect(base_path('knowledge'), 'abc1234');

        expect($state->commit)->toBe('abc1234')
            ->and($state->dirty)->toBeFalse()
            ->and($state->label())->toBe('abc1234');
    });

    test('without git and without a deployed commit the state is unknown and nothing breaks', function () {
        Process::fake(['*' => fn () => throw new RuntimeException('git: command not found')]);

        $state = KnowledgeState::detect(base_path('knowledge'), null);

        expect($state->isKnown())->toBeFalse()
            ->and($state->label())->toBe('unbekannt');

        $library = knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc()]);

        expect($library->state()->label())->toBe('unbekannt')
            ->and($library->find('POLICY-001')->fingerprint)->toHaveLength(64);
    });
});

describe('check command', function () {
    test('a clean knowledge base reports counts per type and status and succeeds', function () {
        knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(),
            'policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002', 'status' => 'active']),
        ]);

        $this->artisan('knowledge:check')
            ->expectsOutputToContain('2 Dokumente, 2 verwendbar')
            ->expectsOutputToContain('policy: 2 (1 draft, 1 active)')
            ->expectsOutputToContain('Keine Fehler, keine Warnungen.')
            ->assertSuccessful();
    });

    test('errors are listed per file before warnings and make the command fail', function () {
        knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['status' => 'final', 'topics' => ['Kein Slug']]),
        ]);

        $this->artisan('knowledge:check')
            ->expectsOutputToContain('policies/policy-001-a.md')
            ->expectsOutputToContain('Fehler: Unbekannter Status `final`')
            ->expectsOutputToContain('Warnung: Wert `Kein Slug`')
            ->expectsOutputToContain('1 Fehler, 1 Warnungen')
            ->assertFailed();
    });

    test('warnings alone succeed, unless the strict option is given', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['topics' => ['Kein Slug']])]);

        $this->artisan('knowledge:check')->expectsOutputToContain('0 Fehler, 1 Warnungen')->assertSuccessful();
        $this->artisan('knowledge:check --strict')->assertFailed();
    });

    test('checking never changes a file', function () {
        $library = knowledgeBase(['policies/policy-001-a.md' => 'kaputt', 'policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002'])]);
        $path = config('knowledge.path');
        $before = [File::get("{$path}/policies/policy-001-a.md"), File::get("{$path}/policies/policy-002-b.md")];

        $this->artisan('knowledge:check');
        $library->overview();

        expect([File::get("{$path}/policies/policy-001-a.md"), File::get("{$path}/policies/policy-002-b.md")])->toBe($before);
    });
});

describe('id overview', function () {
    test('it lists assigned ids with title, the next free id per type and all tag values', function () {
        $library = knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Erste Regel', 'topics' => ['refund'], 'customer_types' => ['b2c'], 'sales_channels' => ['shop'], 'categories' => ['complaint'], 'products' => ['lunchbox']]),
            'policies/policy-003-c.md' => knowledgeDoc(['id' => 'POLICY-003', 'title' => 'Dritte Regel', 'topics' => ['goodwill']]),
            'products/lunchbox.md' => knowledgeDoc(['id' => 'PRODUCT-001', 'type' => 'product', 'title' => 'Lunchbox']),
        ]);

        expect($library->overview())
            ->toContain("policy (nächste freie ID: POLICY-004):\n  POLICY-001 – Erste Regel\n  POLICY-003 – Dritte Regel")
            ->toContain("product (nächste freie ID: PRODUCT-002):\n  PRODUCT-001 – Lunchbox")
            ->toContain("playbook (nächste freie ID: PLAYBOOK-001):\n  noch keine")
            ->toContain("example-good (nächste freie ID: EXAMPLE-GOOD-001):\n  noch keine")
            ->toContain("Vorhandene products-Slugs:\nlunchbox")
            ->toContain("Vorhandene topics-Werte:\ngoodwill, refund")
            ->toContain("Verwendete categories-Werte:\ncomplaint")
            ->toContain("Verwendete customer_types-Werte:\nb2c")
            ->toContain("Verwendete sales_channels-Werte:\nshop");
    });

    test('ids of deprecated and faulty documents still count as assigned', function () {
        $library = knowledgeBase([
            'policies/policy-004-a.md' => knowledgeDoc(['id' => 'POLICY-004', 'status' => 'deprecated', 'title' => 'Alt']),
            'policies/policy-009-b.md' => knowledgeDoc(['id' => 'POLICY-009', 'status' => 'kaputt', 'title' => 'Fehlerhaft']),
        ]);

        expect($library->usable())->toBeEmpty()
            ->and($library->overview())
            ->toContain('policy (nächste freie ID: POLICY-010):')
            ->toContain('POLICY-004 – Alt (deprecated)')
            ->toContain('POLICY-009 – Fehlerhaft');
    });

    test('it has the format of the session start block in the authoring guide', function () {
        $guide = File::get(base_path('docs/KNOWLEDGE_AUTHORING_GUIDE.md'));
        $overview = knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc()])->overview();

        foreach (['Vergebene IDs:', 'Vorhandene products-Slugs:', 'Vorhandene topics-Werte:', 'Heute möchte ich erfassen:'] as $heading) {
            expect($guide)->toContain($heading)
                ->and($overview)->toContain($heading);
        }

        expect($overview)->toStartWith('Vergebene IDs:')->toEndWith("Heute möchte ich erfassen:\n(Thema oder Fall)");
    });

    test('the overview command prints the overview only', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Erste Regel'])]);

        $this->artisan('knowledge:overview')
            ->expectsOutputToContain('POLICY-001 – Erste Regel')
            ->doesntExpectOutputToContain('Fehler')
            ->assertSuccessful();
    });
});
