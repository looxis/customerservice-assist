<?php

use App\Knowledge\KnowledgeLibrary;
use App\Knowledge\KnowledgeState;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    $this->withoutVite();

    Process::fake([
        '*log*' => Process::result("97af4ee|2026-10-02T08:34:41+02:00\n"),
        '*status*' => Process::result(''),
    ]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function documentUrl(string $path): string
{
    return route('knowledge.show', ['path' => $path]);
}

describe('navigation', function () {
    test('the sidebar has a second item "Knowledge" below "Ticket analysieren"', function () {
        $this->get('/')
            ->assertSeeInOrder(['<nav', 'Ticket analysieren', 'Knowledge', '</nav>'], false);
    });

    test('"Knowledge" is the active item on the overview and in a document', function (string $url) {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc()]);

        $html = $this->get($url)->assertOk()->getContent();

        preg_match('/<nav\b.*?<\/nav>/s', $html, $nav);
        preg_match_all('/<a\b[^>]*aria-current="page"[^>]*>.*?<\/a>/s', $nav[0], $active);

        expect($active[0])->toHaveCount(1)
            ->and($active[0][0])->toContain('Knowledge');
    })->with(['/knowledge', '/knowledge/dokument/policies/policy-001-a.md']);
});

describe('document list', function () {
    test('documents are grouped by type in the precedence of the knowledge base and sorted by id', function () {
        knowledgeBase([
            'playbooks/playbook-001-a.md' => knowledgeDoc(['id' => 'PLAYBOOK-001', 'type' => 'playbook', 'title' => 'Ein Playbook', 'categories' => ['complaint']]),
            'policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002', 'title' => 'Zweite Policy']),
            'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Erste Policy']),
            'permissions/permission-001-a.md' => knowledgeDoc(['id' => 'PERMISSION-001', 'type' => 'permission', 'title' => 'Eine Befugnis', 'action' => 'refund', 'agent_allowed' => true]),
        ]);

        $this->get('/knowledge')
            ->assertOk()
            ->assertSeeInOrder(['Policies', 'POLICY-001', 'Erste Policy', 'POLICY-002', 'Zweite Policy', 'Permissions', 'PERMISSION-001', 'Playbooks', 'PLAYBOOK-001'])
            ->assertDontSee('Produkte</h2>', false)
            ->assertDontSee('id="group-product"', false);
    });

    test('a row shows id, title, status and scope, and an empty scope as "alle"', function () {
        knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Kulanz', 'customer_types' => ['b2c'], 'sales_channels' => ['shop'], 'categories' => ['complaint']]),
        ]);

        $this->get('/knowledge')
            ->assertSeeInOrder(['POLICY-001', 'Kulanz', 'Entwurf', 'Kundenart:', 'b2c', 'Kanal:', 'shop', 'Kategorie:', 'complaint', 'Produkt:', 'alle'])
            ->assertSee('href="'.documentUrl('policies/policy-001-a.md').'"', false);
    });

    test('each status has its own badge and deprecated documents are muted', function () {
        knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['status' => 'draft']),
            'policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002', 'status' => 'active']),
            'policies/policy-003-c.md' => knowledgeDoc(['id' => 'POLICY-003', 'status' => 'deprecated']),
        ]);

        $html = $this->get('/knowledge')
            ->assertSeeInOrder(['POLICY-001', 'Entwurf', 'POLICY-002', 'Aktiv', 'POLICY-003', 'Wird nicht verwendet', 'Veraltet'])
            ->getContent();

        expect(substr_count($html, 'opacity-60'))->toBe(1);
    });

    test('documents with errors and warnings are marked', function () {
        knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['status' => 'final']),
            'policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002', 'topics' => ['Kein Slug', 'Auch Keiner']]),
            'policies/policy-003-c.md' => knowledgeDoc(['id' => 'POLICY-003', 'topics' => ['Kein Slug']]),
        ]);

        $this->get('/knowledge')
            ->assertSeeInOrder(['POLICY-001', 'Wird nicht verwendet', 'POLICY-002', '2 Warnungen', 'POLICY-003', '1 Warnung'])
            ->assertSee('text-danger-700', false);
    });

    test('a file with unreadable frontmatter is listed with its path and the error badge', function () {
        knowledgeBase(['policies/kaputt.md' => 'ohne Frontmatter']);

        $this->get('/knowledge')
            ->assertSeeInOrder(['Policies', 'policies/kaputt.md', 'Wird nicht verwendet'])
            ->assertSee('href="'.documentUrl('policies/kaputt.md').'"', false);
    });
});

describe('figures and knowledge state', function () {
    test('the header shows the document counts and the knowledge state', function () {
        knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['status' => 'draft']),
            'policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002', 'status' => 'active']),
            'policies/policy-003-c.md' => knowledgeDoc(['id' => 'POLICY-003', 'status' => 'deprecated']),
            'policies/policy-004-d.md' => knowledgeDoc(['id' => 'POLICY-004', 'status' => 'final']),
        ]);

        $html = $this->get('/knowledge')
            ->assertSeeInOrder(['Dokumente', '4', 'Verwendbar', '2', 'Entwurf', '1', 'Aktiv', '1'])
            ->assertSeeText('Wissensstand: 97af4ee vom 02.10.2026')
            ->getContent();

        expect($html)->not->toContain('mit uncommitteten Änderungen');
    });

    test('uncommitted changes and an unknown state are shown', function (Closure $state, string $expected) {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc()]);

        $library = Mockery::mock(KnowledgeLibrary::class, [config('knowledge')])->makePartial();
        $library->shouldReceive('state')->andReturn($state());

        $this->swap(KnowledgeLibrary::class, $library);

        $this->get('/knowledge')->assertSeeText($expected);
    })->with([
        'uncommitted' => [fn () => new KnowledgeState('97af4ee', CarbonImmutable::parse('2026-10-02'), true), 'Wissensstand: 97af4ee vom 02.10.2026, mit uncommitteten Änderungen'],
        'unknown' => [fn () => new KnowledgeState(null, null, false), 'Wissensstand: unbekannt'],
    ]);

    test('a missing or empty knowledge folder shows a clear notice and the page still works', function (?array $files) {
        if ($files === null) {
            config(['knowledge.path' => sys_get_temp_dir().'/knowledge-test-fehlt']);
            app()->forgetScopedInstances();
        } else {
            knowledgeBase($files);
        }

        $this->get('/knowledge')
            ->assertOk()
            ->assertSeeText('Es ist kein Unternehmenswissen verfügbar')
            ->assertSeeText('Für den KI-Chat')
            ->assertDontSee('name="q"', false);
    })->with(['missing' => [null], 'empty' => [['README.md' => '# leer']]]);
});

describe('filter', function () {
    beforeEach(function () {
        knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Kulanz im Shop', 'status' => 'active']),
            'policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002', 'title' => 'Lieferverzögerung', 'topics' => ['Kein Slug']]),
            'permissions/permission-001-a.md' => knowledgeDoc(['id' => 'PERMISSION-001', 'type' => 'permission', 'title' => 'Erstattung', 'action' => 'refund', 'agent_allowed' => true]),
        ]);
    });

    test('filters narrow the list and stay selected', function (string $query, array $visible, array $hidden, string $selected) {
        $html = $this->get('/knowledge?'.$query)->assertOk()->getContent();

        // The ID overview further down always names every document.
        $list = strstr($html, 'Für die Pflege der Knowledge Base', true);

        expect(preg_replace('/\s+/', ' ', $list))->toContain($selected);

        foreach ($visible as $text) {
            expect($list)->toContain($text);
        }

        foreach ($hidden as $text) {
            expect($list)->not->toContain($text);
        }
    })->with([
        'type' => ['type=permission', ['Erstattung'], ['Kulanz im Shop', 'Lieferverzögerung'], '<option value="permission" selected>'],
        'status' => ['status=active', ['Kulanz im Shop'], ['Lieferverzögerung', 'Erstattung'], '<option value="active" selected>'],
        'issues only' => ['issues=1', ['Lieferverzögerung'], ['Kulanz im Shop', 'Erstattung'], 'name="issues" value="1" checked'],
        'search in title, any case' => ['q=KULANZ', ['Kulanz im Shop'], ['Lieferverzögerung', 'Erstattung'], 'value="KULANZ"'],
        'search in id' => ['q=policy-002', ['Lieferverzögerung'], ['Kulanz im Shop', 'Erstattung'], 'value="policy-002"'],
        'combined' => ['type=policy&status=draft', ['Lieferverzögerung'], ['Kulanz im Shop', 'Erstattung'], '<option value="draft" selected>'],
    ]);

    test('no match shows the empty state with a reset link', function (string $query) {
        $this->get('/knowledge?'.$query)
            ->assertOk()
            ->assertSeeText('Keine Dokumente gefunden')
            ->assertSee('href="'.route('knowledge.index').'"', false)
            ->assertSeeText('Filter zurücksetzen');
    })->with(['q=gibtesnicht', 'q=%25%27%22%3Cscript%3E', 'type=glossary']);

    test('invalid filter values are rejected and the overview is shown unfiltered', function (string $query, string $message) {
        $this->get('/knowledge?'.$query)
            ->assertRedirect(route('knowledge.index'))
            ->assertSessionHasErrors();

        $this->followingRedirects()->get('/knowledge?'.$query)
            ->assertOk()
            ->assertSeeText($message)
            ->assertSeeText('Kulanz im Shop');
    })->with([
        ['type=quatsch', 'Diesen Dokumenttyp gibt es nicht.'],
        ['status=final', 'Diesen Status gibt es nicht.'],
        ['q='.str_repeat('a', 101), 'höchstens 100 Zeichen'],
        ['issues=ja', 'ungültig'],
    ]);
});

describe('check result', function () {
    test('without issues there is a success note and the section is closed', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc()]);

        $html = $this->get('/knowledge')->assertSeeText('Keine Fehler, keine Warnungen')->getContent();

        expect($html)->not->toMatch('/<details\s+open/');
    });

    test('with errors the section is open and lists the issues per file, errors first', function () {
        knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['status' => 'final', 'topics' => ['Kein Slug']]),
            'policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002', 'catagories' => ['x']]),
        ]);

        $html = $this->get('/knowledge')->getContent();

        expect($html)->toMatch('/<details\s+open/');

        $this->get('/knowledge')
            ->assertSeeInOrder(['Prüfergebnis', '1 Fehler', '2 Warnungen', 'policies/policy-001-a.md', 'Fehler: Unbekannter Status', 'Warnung: Wert `Kein Slug`', 'policies/policy-002-b.md', 'Warnung: Unbekanntes Feld `catagories`']);
    });

    test('with warnings only the section is closed and shows their number', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['topics' => ['Kein Slug']])]);

        $html = $this->get('/knowledge')->assertSeeInOrder(['Prüfergebnis', '1 Warnung', 'Warnung: Wert `Kein Slug`'])->getContent();

        expect($html)->not->toMatch('/<details\s+open/');
    });

    test('an issue links to its document', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['topics' => ['Kein Slug']])]);

        $html = $this->get('/knowledge')->getContent();

        expect(substr_count($html, 'href="'.documentUrl('policies/policy-001-a.md').'"'))->toBe(2);
    });
});

describe('id overview', function () {
    test('the section shows exactly what the overview command prints, with a copy button', function () {
        $library = knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Erste "Regel" & mehr'])]);

        $this->get('/knowledge')
            ->assertSee(e($library->overview()), false)
            ->assertSeeInOrder(['Für den KI-Chat', '<textarea', 'readonly', 'Kopieren', 'Kopiert', 'bitte mit Strg+C kopieren'], false)
            ->assertSee('navigator.clipboard.writeText', false)
            ->assertSee('this.$refs.text.select()', false);
    });
});

describe('document view', function () {
    test('it shows header data, scope, topics, path and short fingerprint', function () {
        $library = knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Kulanz im Shop', 'customer_types' => ['b2c'], 'sales_channels' => ['shop'], 'categories' => ['complaint'], 'topics' => ['goodwill', 'refund']]),
        ]);

        $this->get(documentUrl('policies/policy-001-a.md'))
            ->assertOk()
            ->assertSee('<title>POLICY-001 – Customer Service Assist</title>', false)
            ->assertSeeInOrder(['POLICY-001', 'Policies', 'Kulanz im Shop', 'Entwurf', 'Kundenart:', 'b2c', 'Kanal:', 'shop', 'Kategorie:', 'complaint', 'Produkt:', 'alle', 'Themen:', 'goodwill', 'refund', 'policies/policy-001-a.md', 'Fingerabdruck'])
            ->assertSeeText($library->find('POLICY-001')->shortFingerprint());
    });

    test('a permission shows action, authority, limit and approval role', function (array $fields, array $expected) {
        knowledgeBase(['permissions/permission-001-a.md' => knowledgeDoc(['id' => 'PERMISSION-001', 'type' => 'permission', ...$fields])]);

        $this->get(documentUrl('permissions/permission-001-a.md'))->assertSeeInOrder($expected);
    })->with([
        'with limit' => [['action' => 'refund', 'agent_allowed' => true, 'max_value_eur' => 35, 'approval_role' => 'managing-director'], ['Maßnahme', 'refund', 'Kundenservice entscheidet selbst', 'Ja', 'Wertgrenze', '35 €', 'Freigabe durch', 'managing-director']],
        'without limit' => [['action' => 'replacement', 'agent_allowed' => false, 'max_value_eur' => null], ['replacement', 'Nein', 'keine Wertgrenze']],
    ]);

    test('the text is rendered as formatted html, not as markdown source', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(body: "# Regel\n\nEin **wichtiger** Satz.\n\n## Gilt wenn\n\n- Punkt eins\n- Punkt zwei\n\n1. Schritt\n\n| Fall | Lösung |\n|---|---|\n| A | B |")]);

        $this->get(documentUrl('policies/policy-001-a.md'))
            ->assertSee('<h2>Regel</h2>', false)
            ->assertSee('<h3>Gilt wenn</h3>', false)
            ->assertSee('<strong>wichtiger</strong>', false)
            ->assertSee('<li>Punkt eins</li>', false)
            ->assertSee('<ol>', false)
            ->assertSee('<table>', false)
            ->assertSee('<th>Fall</th>', false)
            ->assertDontSee('**wichtiger**', false)
            ->assertDontSee('# Regel', false);
    });

    test('existing ids in related knowledge and in the text are links, missing ones stay text', function () {
        knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['related_knowledge' => ['PERMISSION-001', 'POLICY-099']], "# Regel\n\nSiehe PERMISSION-001, POLICY-001 und PLAYBOOK-042. Im Code: `PERMISSION-001`."),
            'permissions/permission-001-a.md' => knowledgeDoc(['id' => 'PERMISSION-001', 'type' => 'permission', 'action' => 'refund', 'agent_allowed' => true]),
        ]);

        $html = $this->get(documentUrl('policies/policy-001-a.md'))->getContent();
        $target = documentUrl('permissions/permission-001-a.md');

        expect(substr_count($html, 'href="'.$target.'"'))->toBe(2)
            ->and($html)->toContain('<a href="'.$target.'">PERMISSION-001</a>, POLICY-001 und PLAYBOOK-042')
            ->toContain('<code>PERMISSION-001</code>')
            ->not->toContain('>POLICY-099</a>');
    });

    test('a document with errors is not linked from other documents', function () {
        knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(body: 'Siehe POLICY-002.'),
            'policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002', 'status' => 'final']),
        ]);

        $this->get(documentUrl('policies/policy-001-a.md'))->assertDontSee('>POLICY-002</a>', false);
    });

    test('drafts and deprecated documents carry a notice', function (string $status, string $notice) {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['status' => $status])]);

        $this->get(documentUrl('policies/policy-001-a.md'))->assertSeeText($notice);
    })->with([
        ['draft', 'Entwurfs-Wissen: Dieses Dokument ist noch nicht fachlich bestätigt'],
        ['deprecated', 'Veraltet: Dieses Dokument gilt nicht mehr'],
    ]);

    test('an active document carries no notice', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['status' => 'active'])]);

        $this->get(documentUrl('policies/policy-001-a.md'))->assertDontSeeText('Entwurfs-Wissen')->assertDontSeeText('Veraltet:');
    });

    test('issues are shown above the text, errors first', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['topics' => ['Kein Slug'], 'customer_types' => ['reseller']], "# Regel\n\nDer Regeltext.")]);

        $this->get(documentUrl('policies/policy-001-a.md'))
            ->assertSeeInOrder(['wird von der App nicht verwendet', 'Fehler: Unbekannter Wert `reseller`', 'Warnung: Wert `Kein Slug`', 'Der Regeltext.'])
            ->assertDontSeeText('Entwurfs-Wissen');
    });

    test('a file with unreadable frontmatter shows path and error but no text', function () {
        knowledgeBase(['policies/kaputt.md' => 'GEHEIMER-INHALT ohne Frontmatter']);

        $this->get(documentUrl('policies/kaputt.md'))
            ->assertOk()
            ->assertSeeText('policies/kaputt.md')
            ->assertSeeText('Fehler: Das Frontmatter fehlt')
            ->assertDontSeeText('GEHEIMER-INHALT');
    });

    test('unknown paths and paths outside the knowledge base are not found', function (string $url) {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc()]);

        $this->get($url)->assertNotFound()->assertSeeText('Seite nicht gefunden');
    })->with([
        '/knowledge/dokument/policies/gibt-es-nicht.md',
        '/knowledge/dokument/POLICY-001',
        '/knowledge/dokument/../.env',
        '/knowledge/dokument/..%2F..%2F.env',
        '/knowledge/dokument/README.md',
        '/knowledge/dokument/templates/policy.md',
        '/knowledge/dokument//etc/passwd',
    ]);

    test('two files with the same id are both reachable by their path', function () {
        knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Fassung A']),
            'policies/policy-001-b.md' => knowledgeDoc(['title' => 'Fassung B']),
        ]);

        $this->get(documentUrl('policies/policy-001-a.md'))->assertOk()->assertSeeText('Fassung A')->assertSeeText('doppelt vergeben');
        $this->get(documentUrl('policies/policy-001-b.md'))->assertOk()->assertSeeText('Fassung B');
    });

    test('the back link keeps the filters of the overview', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc()]);

        $filtered = route('knowledge.index', ['type' => 'policy', 'q' => 'titel']);

        $this->from($filtered)->get(documentUrl('policies/policy-001-a.md'))->assertSee('href="'.e($filtered).'"', false);
        $this->from('https://evil.example/knowledge')->get(documentUrl('policies/policy-001-a.md'))->assertSeeInOrder(['href="', 'Zur Übersicht'], false);
        $this->from('/')->get(documentUrl('policies/policy-001-a.md'))->assertSee('href="'.route('knowledge.index').'"', false);
    });
});

describe('safe rendering', function () {
    test('html and script code in title, text and frontmatter is shown as text', function () {
        $payload = '<script>alert(1)</script><img src=x onerror=alert(2)>';

        knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['title' => $payload, 'topics' => [$payload], 'related_knowledge' => [$payload]], "# Regel {$payload}\n\n{$payload}\n\n<div onclick=\"alert(3)\">Block</div>"),
        ]);

        foreach (['/knowledge', documentUrl('policies/policy-001-a.md')] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            expect($html)->not->toContain('<script>alert(1)</script>')
                ->not->toContain('<img src=x')
                ->not->toContain('<div onclick')
                ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;');
        }
    });

    test('unsafe links are not rendered as links and external links open in a new tab without referrer', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(body: '[klick](javascript:alert(1)) und [daten](data:text/html,x) und [extern](https://example.com/hilfe).')]);

        $html = $this->get(documentUrl('policies/policy-001-a.md'))->getContent();

        expect($html)->not->toContain('href="javascript:')
            ->not->toContain('href="data:')
            ->toContain('href="https://example.com/hilfe"')
            ->toMatch('/<a rel="[^"]*noopener[^"]*noreferrer[^"]*" target="_blank"[^>]*href="https:\/\/example\.com\/hilfe"|<a [^>]*href="https:\/\/example\.com\/hilfe"[^>]*target="_blank"/');
    });

    test('images are never loaded, the alternative text is shown instead', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(body: 'Vorher ![Foto der Gravur](https://tracker.example/pixel.png) nachher ![](https://tracker.example/leer.png).')]);

        $html = $this->get(documentUrl('policies/policy-001-a.md'))->getContent();

        expect($html)->toContain('[Bild: Foto der Gravur]')
            ->toContain('[Bild]')
            ->not->toContain('<img')
            ->not->toContain('tracker.example');
    });

    test('the pages reference no foreign hosts', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc()]);

        foreach (['/knowledge', documentUrl('policies/policy-001-a.md')] as $url) {
            preg_match_all('/(?:href|src)="(https?:)?\/\/([^"\/:]+)/', $this->get($url)->getContent(), $matches);

            expect(array_diff(array_unique($matches[2]), [parse_url(config('app.url'), PHP_URL_HOST), 'localhost']))->toBeEmpty();
        }
    });
});

describe('scale', function () {
    test('the overview with 200 documents renders in under a second', function () {
        $files = [];

        foreach (range(1, 200) as $number) {
            $id = sprintf('POLICY-%03d', $number);
            $files['policies/'.strtolower($id).'-a.md'] = knowledgeDoc(['id' => $id, 'title' => "Regel {$number}", 'topics' => ['Kein Slug']]);
        }

        knowledgeBase($files);

        $start = microtime(true);
        $this->get('/knowledge')->assertOk()->assertSeeText('Regel 200');

        expect(microtime(true) - $start)->toBeLessThan(1.0);
    });

    test('the real knowledge base renders, including every document view', function () {
        $this->get('/knowledge')->assertOk();

        foreach (app(KnowledgeLibrary::class)->all() as $document) {
            $this->get(documentUrl($document->path))->assertOk();
        }
    });
});

describe('qa: hostile input', function () {
    test('link tricks in the text never produce an executable link or attribute', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(body: "[a](JaVaScRiPt:alert(1)) [b](vbscript:x) <javascript:alert(1)> [c](https://x.example \"t\\\" onmouseover=\\\"alert(1)\")\n\n[d]: javascript:alert(2)\n\n[ref][d]")]);

        $text = strstr($this->get(documentUrl('policies/policy-001-a.md'))->getContent(), 'knowledge-text');

        expect($text)->not->toMatch('/href="\s*(javascript|vbscript|data):/i')
            ->not->toContain('onmouseover="alert')
            ->toContain('href="https://x.example"');
    });

    test('a title cannot break out of the copy field', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => '</textarea><script>alert(1)</script>'])]);

        expect($this->get('/knowledge')->getContent())->not->toContain('</textarea><script>');
    });

    test('file names with spaces, umlauts and ampersands are linked and open', function () {
        knowledgeBase(['products/3D Glas Ünïcode & Co.md' => knowledgeDoc(['id' => 'PRODUCT-001', 'type' => 'product', 'title' => 'Sonderzeichen'])]);

        preg_match('/href="([^"]*dokument[^"]*)"/', $this->get('/knowledge')->getContent(), $match);

        $this->get(html_entity_decode($match[1]))->assertOk()->assertSeeText('Sonderzeichen');
    });

    test('array values in filter parameters are rejected', function (string $query) {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc()]);

        $this->get('/knowledge?'.$query)->assertRedirect(route('knowledge.index'));
    })->with(['q[]=x', 'type[]=policy', 'status[a]=b', 'issues[]=1']);

    test('unknown parameters are ignored and the overview only answers to GET', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc()]);

        $this->get('/knowledge?unbekannt=1&q=titel')->assertOk();
        $this->post('/knowledge')->assertStatus(405);
    });

    test('deeply nested or pathological markdown renders quickly', function (string $body) {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(body: $body)]);

        $start = microtime(true);

        $this->get(documentUrl('policies/policy-001-a.md'))->assertOk();

        expect(microtime(true) - $start)->toBeLessThan(1.0);
    })->with([str_repeat('> ', 3000).'tief', str_repeat('*a **b ', 4000)]);

    test('the knowledge pages carry the security headers', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc()]);

        foreach (['/knowledge', documentUrl('policies/policy-001-a.md')] as $url) {
            $this->get($url)->assertHeader('X-Frame-Options', 'DENY')->assertHeader('X-Content-Type-Options', 'nosniff');
        }
    });
});
