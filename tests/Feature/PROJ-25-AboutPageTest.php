<?php

use App\Knowledge\KnowledgeLibrary;
use App\Knowledge\KnowledgeState;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    $this->withoutVite();

    Process::fake([
        '*log*' => Process::result("97af4ee|2026-10-02T08:34:41+02:00\n"),
        '*status*' => Process::result(''),
    ]);
});

afterEach(fn () => cleanUpKnowledgeBases());

/**
 * Point the about page at a temporary text file.
 */
function aboutText(string $markdown): void
{
    $path = sys_get_temp_dir().'/knowledge-test-about-'.bin2hex(random_bytes(6)).'/ABOUT.md';

    File::ensureDirectoryExists(dirname($path));
    File::put($path, $markdown);

    config(['app.about_path' => $path]);
}

describe('navigation', function () {
    test('the sidebar has "Über die App" below the main navigation, apart from the work pages', function () {
        $html = $this->get('/')->assertOk()->getContent();

        preg_match_all('/<nav\b[^>]*aria-label="([^"]+)"[^>]*>(.*?)<\/nav>/s', $html, $navs, PREG_SET_ORDER);

        expect($navs)->toHaveCount(2)
            ->and($navs[0][1])->toBe('Hauptnavigation')
            ->and($navs[0][2])->not->toContain('Über die App')
            ->and($navs[1][1])->toBe('Weitere Seiten')
            ->and($navs[1][2])->toContain('Über die App')
            ->toContain(route('about'));
    });

    test('the page has the title "Über die App" and its sidebar item is active', function () {
        $html = $this->get('/ueber-die-app')
            ->assertOk()
            ->assertSee('<title>Über die App – ', false)
            ->getContent();

        preg_match_all('/<a\b[^>]*aria-current="page"[^>]*>.*?<\/a>/s', $html, $active);

        expect($active[0])->toHaveCount(1)
            ->and($active[0][0])->toContain('Über die App');
    });

    test('the page opens without login', function () {
        $this->get(route('about'))->assertOk();
    });
});

describe('content of the real text', function () {
    test('the sections come in the order of the spec, the status box before the developer part', function () {
        $this->get(route('about'))->assertSeeTextInOrder([
            'Wofür ist die App?',
            'So funktioniert es',
            'Ein Beispiel',
            'Was die App bewusst nicht tut',
            'Gut zu wissen',
            'Wissen ergänzen',
            'Aktueller Stand',
            'Für Entwickler',
            'Tech Stack',
            'Bausteine',
            'Arbeitsweise',
            'Wo steht was?',
        ]);
    });

    test('the limits name sending, deciding and changing orders, refunds or credit notes', function () {
        $this->get(route('about'))
            ->assertSeeText('schickt keine Antwort')
            ->assertSeeText('entscheidet nichts')
            ->assertSeeText('ändert keine Bestellungen')
            ->assertSeeText('Erstattungen, Gutschriften');
    });

    test('"good to know" explains unclear results, draft knowledge and sources', function () {
        $this->get(route('about'))
            ->assertSeeText('„Unklar" ist ein gutes Ergebnis.')
            ->assertSeeText('Entwurfs-Wissen')
            ->assertSeeText('Jede Empfehlung nennt ihre Quellen');
    });

    test('the section on adding knowledge links to the knowledge overview', function () {
        $this->get(route('about'))->assertSee('href="/knowledge"', false);
    });

    test('the developer part names the tech stack and where things are', function () {
        $response = $this->get(route('about'));

        foreach (['Laravel 13', 'PHP 8.5', 'Blade', 'Tailwind CSS', 'Alpine.js', 'MySQL', 'Pest', 'Laravel Sail', 'Laravel Boost'] as $tool) {
            $response->assertSeeText($tool);
        }

        foreach (['docs/PRD.md', 'features/INDEX.md', 'docs/KNOWLEDGE_AUTHORING_GUIDE.md', 'docs/KNOWLEDGE_BASE_DESIGN.md', 'docs/design-system.md', 'README.md'] as $file) {
            $response->assertSeeText($file);
        }

        $response->assertSeeText('/write-spec')->assertSeeText('./vendor/bin/sail');
    });

    test('the example is fictitious and contains no order or ticket numbers', function () {
        $text = file_get_contents(base_path('docs/ABOUT.md'));
        $example = str($text)->after('# Ein Beispiel')->before('# Was die App bewusst nicht tut')->toString();

        expect($example)->not->toMatch('/\d{5,}/')
            ->not->toMatch('/@/')
            ->not->toContain('Ticket #');
    });

    test('the text contains no secrets, addresses or ports', function () {
        $text = file_get_contents(base_path('docs/ABOUT.md'));

        expect($text)->not->toMatch('/https?:\/\//')
            ->not->toMatch('/\b(?:\d{1,3}\.){3}\d{1,3}\b/')
            ->not->toMatch('/:\d{2,5}\b/')
            ->not->toMatch('/(PASSWORD|SECRET|API_KEY|TOKEN)\s*=/i');
    });
});

describe('work in progress marker', function () {
    test('steps that are not usable yet carry an "in Arbeit" badge, usable ones none', function () {
        aboutText("# So funktioniert es\n\n1. **Ticket laden.** Holt das Ticket. [in Arbeit]\n2. **Wissen auswählen.** Läuft automatisch.\n");

        $html = $this->get(route('about'))->getContent();

        expect($html)->toContain('Holt das Ticket. <span class="about-wip">in Arbeit</span>')
            ->and(substr_count($html, 'class="about-wip"'))->toBe(1)
            ->and($html)->not->toContain('[in Arbeit]');
    });

    test('the marker inside code stays as written', function () {
        aboutText("# Für Entwickler\n\nDie Kennung `[in Arbeit]` wird entfernt.\n");

        $this->get(route('about'))->assertSee('<code>[in Arbeit]</code>', false);
    });

    test('in the real text only the steps that are not usable yet carry the marker', function () {
        $html = $this->get(route('about'))->getContent();

        preg_match('/So funktioniert es.*?<\/ol>/s', $html, $flow);

        expect(substr_count($flow[0], 'about-wip'))->toBe(6)
            ->and($flow[0])->toMatch('/Passendes Wissen auswählen\.<\/strong>((?!<\/li>).)*<\/li>/s')
            ->and(preg_match('/Passendes Wissen auswählen\.<\/strong>((?!<\/li>).)*about-wip/s', $flow[0]))->toBe(0)
            ->and(preg_match('/Deinen Namen wählen\.<\/strong>((?!<\/li>).)*about-wip/s', $flow[0]))->toBe(0);
    });
});

describe('current state box', function () {
    test('it shows the app version as in the sidebar footer', function () {
        config(['app.version' => '97af4ee · 02.10.2026']);

        $this->get(route('about'))->assertSeeTextInOrder(['Aktueller Stand', 'App-Version', '97af4ee · 02.10.2026']);
    });

    test('it shows the knowledge state and the number of usable documents and drafts', function () {
        knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['status' => 'active']),
            'policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002']),
            'policies/policy-003-c.md' => knowledgeDoc(['id' => 'POLICY-003']),
            'policies/policy-004-d.md' => knowledgeDoc(['id' => 'POLICY-004', 'status' => 'deprecated']),
            'policies/policy-005-e.md' => knowledgeDoc(['id' => 'POLICY-005', 'status' => 'final']),
        ]);

        $this->get(route('about'))->assertSeeTextInOrder([
            'Wissensstand', '97af4ee vom 02.10.2026',
            'Verwendbare Dokumente', '3',
            'Davon Entwürfe', '2',
        ]);
    });

    test('a changed knowledge file shows on the next load without restart', function () {
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc()]);

        $this->get(route('about'))->assertSeeTextInOrder(['Verwendbare Dokumente', '1']);

        File::put(config('knowledge.path').'/policies/policy-002-b.md', knowledgeDoc(['id' => 'POLICY-002']));
        app()->forgetScopedInstances();

        $this->get(route('about'))->assertSeeTextInOrder(['Verwendbare Dokumente', '2']);
    });

    test('a missing knowledge folder shows zero documents and the message, the page still loads', function () {
        config(['knowledge.path' => sys_get_temp_dir().'/knowledge-test-missing']);

        $this->get(route('about'))
            ->assertOk()
            ->assertSeeTextInOrder(['Verwendbare Dokumente', '0'])
            ->assertSeeText('Der Knowledge-Ordner fehlt.')
            ->assertSeeText('Für Entwickler');
    });

    test('an unknown or dirty knowledge state is named as on the knowledge page', function (Closure $state, string $expected) {
        $library = Mockery::mock(KnowledgeLibrary::class, [config('knowledge')])->makePartial();
        $library->shouldReceive('state')->andReturn($state());

        $this->swap(KnowledgeLibrary::class, $library);

        $this->get(route('about'))->assertSeeTextInOrder(['Wissensstand', $expected]);
    })->with([
        'unknown' => [fn () => new KnowledgeState(null, null, false), 'unbekannt'],
        'uncommitted' => [fn () => new KnowledgeState('97af4ee', CarbonImmutable::parse('2026-10-02'), true), '97af4ee vom 02.10.2026, mit uncommitteten Änderungen'],
    ]);
});

describe('missing text', function () {
    test('a missing or empty text shows a hint instead of an error, the status box stays', function (?string $markdown) {
        if ($markdown === null) {
            config(['app.about_path' => sys_get_temp_dir().'/knowledge-test-missing/ABOUT.md']);
        } else {
            aboutText($markdown);
        }

        $this->get(route('about'))
            ->assertOk()
            ->assertSeeText('Beschreibung fehlt')
            ->assertSeeText('Aktueller Stand')
            ->assertDontSeeText('Für Entwickler');
    })->with(['missing file' => [null], 'empty file' => ["  \n"]]);

    test('the developer heading is found with a colon or in lower case', function (string $heading) {
        aboutText("# Wofür ist die App?\n\nFür alle.\n\n{$heading}\n\nNur für Entwickler.\n");

        $this->get(route('about'))->assertSeeTextInOrder(['Für alle.', 'Aktueller Stand', 'Für Entwickler', 'Nur für Entwickler.']);
    })->with(['colon' => ['## Für Entwickler:'], 'lower case' => ['# für entwickler']]);

    test('a text without developer part shows only the general part', function () {
        aboutText("# Wofür ist die App?\n\nNur für alle.\n");

        $this->get(route('about'))
            ->assertSeeTextInOrder(['Wofür ist die App?', 'Nur für alle.', 'Aktueller Stand'])
            ->assertDontSeeText('Für Entwickler')
            ->assertDontSeeText('Beschreibung fehlt');
    });

    test('raw html in the text is not executed', function () {
        aboutText("# Wofür ist die App?\n\n<script>alert(1)</script>\n");

        $this->get(route('about'))->assertDontSee('<script>alert(1)</script>', false);
    });
});
