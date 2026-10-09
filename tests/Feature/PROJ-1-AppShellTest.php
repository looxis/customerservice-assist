<?php

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->withoutVite();
});

function useLocalEnvironment(): void
{
    app()->detectEnvironment(fn () => 'local');
}

describe('layout shell', function () {
    test('the start page shows "Ticket analysieren" in the app layout instead of the Laravel welcome page', function () {
        $this->get('/')
            ->assertOk()
            ->assertViewIs('tickets.analyze')
            ->assertSee('<html lang="de">', false)
            ->assertSee('<title>Ticket analysieren – Customer Service Assist</title>', false)
            ->assertSeeText('Customer Service Assist')
            ->assertSeeText('Ticket aus Zammad')
            ->assertDontSeeText('Laravel');
    });

    test('the sidebar lists the navigation items and marks the current one active', function () {
        $html = $this->get('/')->assertOk()->getContent();

        preg_match('/<nav\b.*?<\/nav>/s', $html, $nav);

        expect($nav)->not->toBeEmpty()
            ->and(substr_count($nav[0], '<a'))->toBe(2)
            ->and(substr_count($nav[0], 'aria-current="page"'))->toBe(1)
            ->and($nav[0])->toContain('Knowledge')
            ->and($nav[0])->toContain('Ticket analysieren')
            ->toContain('aria-current="page"')
            ->toContain('bg-brand-tint')
            ->toContain('text-brand-700')
            ->toContain('<svg');
    });

    test('the sidebar footer shows the configured version', function () {
        config(['app.version' => '97af4ee · 02.10.2026']);

        $this->get('/')->assertSeeText('Version 97af4ee · 02.10.2026');
    });

    test('the version falls back to "dev" when no version is configured', function () {
        expect(config('app.version'))->toBe('dev');
    });

    test('the topbar shows the page title and truncates overlong titles', function () {
        $html = $this->blade('<x-layouts.app :title="$title">Inhalt</x-layouts.app>', ['title' => str_repeat('Sehr langer Titel ', 20)]);

        $html->assertSee('truncate', false)->assertSeeText('Sehr langer Titel');
    });

    test('the topbar renders the user slot reserved for PROJ-5', function () {
        $this->blade(<<<'BLADE'
            <x-layouts.app title="T">
                <x-slot:user>Kerstin</x-slot:user>
                Inhalt
            </x-layouts.app>
            BLADE)
            ->assertSeeInOrder(['<header', 'Kerstin', '</header>'], false);
    });

    test('the sidebar is hidden below 768px and visible from 768px', function () {
        $this->get('/')->assertSee('hidden w-[250px] shrink-0 flex-col border-r border-slate-200 bg-white md:flex', false);
    });

    test('session messages are shown as alerts at the top of the content', function () {
        $this->withSession(['success' => 'Gespeichert.', 'error' => 'Fehlgeschlagen.'])
            ->get('/')
            ->assertSeeText('Gespeichert.')
            ->assertSeeText('Fehlgeschlagen.');
    });

    test('session messages are escaped', function () {
        $this->withSession(['error' => '<script>alert(1)</script>'])
            ->get('/')
            ->assertDontSee('<script>alert(1)</script>', false);
    });
});

describe('no third-party requests', function () {
    test('rendered pages reference no foreign hosts', function (string $path) {
        $html = $this->get($path)->getContent();

        preg_match_all('/(?:href|src)="(https?:)?\/\/([^"\/:]+)/', $html, $matches);

        $foreign = array_diff(array_unique($matches[2]), [parse_url(config('app.url'), PHP_URL_HOST), 'localhost']);

        expect($foreign)->toBeEmpty();
    })->with(['/', '/gibt-es-nicht']);

    test('the stylesheet loads fonts from packages, not from Google Fonts', function () {
        $css = file_get_contents(resource_path('css/app.css'));

        expect($css)->toContain('@fontsource-variable/manrope')
            ->toContain('@fontsource-variable/bricolage-grotesque')
            ->toContain('@fontsource-variable/jetbrains-mono')
            ->not->toContain('fonts.googleapis.com');
    });

    test('the default Tailwind palette is disabled and LOOXIS tokens are defined', function () {
        $css = file_get_contents(resource_path('css/app.css'));

        expect($css)->toContain('--color-*: initial')
            ->toContain('--color-brand: #fe7437')
            ->toContain('--color-slate-600: #6a7080')
            ->toContain('--radius-pill: 9999px')
            ->toContain('--shadow-1:');
    });
});

describe('pages', function () {
    test('the styleguide does not exist outside the local environment', function () {
        expect(Route::has('styleguide'))->toBeFalse();

        $this->get('/styleguide')->assertNotFound();
    });

    test('the styleguide is available in the local environment and shows every component', function () {
        useLocalEnvironment();
        require base_path('routes/web.php');

        $this->get('/styleguide')
            ->assertOk()
            ->assertSeeText('Komponenten-Übersicht')
            ->assertSeeTextInOrder(['Farben', 'Typografie', 'Karte', 'Buttons', 'Formularfelder', 'Badges', 'Alerts', 'Icons', 'Lade-Overlay']);
    });

    test('an unknown address shows the German not-found page with a link to the start page', function () {
        $this->get('/gibt-es-nicht')
            ->assertNotFound()
            ->assertSee('<html lang="de">', false)
            ->assertSeeText('Seite nicht gefunden')
            ->assertSee('href="/"', false)
            ->assertSeeText('Zur Startseite');
    });

    test('a server error shows the German error page without technical details', function () {
        config(['app.debug' => false]);
        Route::get('/kaputt', fn () => throw new RuntimeException('geheimes Detail'));

        $this->get('/kaputt')
            ->assertStatus(500)
            ->assertSeeText('Da ist etwas schiefgelaufen')
            ->assertDontSeeText('geheimes Detail')
            ->assertDontSeeText('RuntimeException');
    });
});

describe('security headers', function () {
    test('every response carries the required security headers', function (string $path) {
        $this->get($path)
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'origin-when-cross-origin');
    })->with(['/', '/gibt-es-nicht']);

    test('strict transport security is sent on secure requests only', function () {
        $this->get('/')->assertHeaderMissing('Strict-Transport-Security');

        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    });
});

describe('button', function () {
    test('each variant renders its design system classes', function (string $variant, string $expected) {
        $this->blade('<x-button :variant="$variant">Los</x-button>', ['variant' => $variant])
            ->assertSee($expected, false)
            ->assertSee('type="button"', false);
    })->with([
        ['primary', 'bg-brand text-white hover:bg-brand-hover'],
        ['secondary', 'bg-white text-slate-900 ring-1 ring-inset ring-slate-300 hover:bg-slate-50'],
        ['danger', 'bg-danger-500 text-white hover:bg-danger-500/90'],
    ]);

    test('a disabled button carries the disabled attribute and the muted styles', function () {
        $this->blade('<x-button disabled>Los</x-button>')
            ->assertSee('disabled="disabled"', false)
            ->assertSee('disabled:cursor-not-allowed disabled:opacity-50', false);
    });

    test('a button with href renders as a link', function () {
        $this->blade('<x-button href="/ziel">Los</x-button>')->assertSee('<a href="/ziel"', false);
    });
});

describe('form fields', function () {
    test('a field shows its label above and the hint below', function (string $component) {
        $this->blade("<x-{$component} name=\"feld\" label=\"Ticketnummer\" hint=\"Steht oben links.\" />")
            ->assertSeeInOrder(['<label for="feld"', 'Ticketnummer', 'name="feld"', 'id="feld-hint"', 'Steht oben links.'], false)
            ->assertSee('aria-describedby="feld-hint"', false)
            ->assertSee('ring-slate-300 focus:ring-brand', false)
            ->assertDontSee('aria-invalid', false);
    })->with(['input', 'textarea', 'select']);

    test('a field with an error shows the message, a red ring and hides the hint', function (string $component) {
        $this->blade("<x-{$component} name=\"feld\" label=\"L\" hint=\"Hinweis\" error=\"Bitte ausfüllen.\" />")
            ->assertSeeText('Bitte ausfüllen.')
            ->assertDontSeeText('Hinweis')
            ->assertSee('ring-danger-500 focus:ring-danger-500', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('aria-describedby="feld-error"', false);
    })->with(['input', 'textarea', 'select']);

    test('a field picks up validation errors by its name', function () {
        $this->withViewErrors(['ticket.number' => 'Ungültige Nummer.'])
            ->blade('<x-input name="ticket[number]" label="L" />')
            ->assertSeeText('Ungültige Nummer.')
            ->assertSee('ring-danger-500', false);
    });

    test('a required field shows a red star and the required attribute', function () {
        $this->blade('<x-input name="feld" label="Pflicht" required />')
            ->assertSee('<span class="text-danger-500" aria-hidden="true"> *</span>', false)
            ->assertSee('required', false);
    });

    test('labels, hints, errors and values are escaped', function () {
        $payload = '"><script>alert(1)</script>';

        $this->blade('<x-input name="feld" :label="$p" :hint="$p" :value="$p" /><x-textarea name="t" :value="$p" :error="$p" />', ['p' => $payload])
            ->assertDontSee('<script>alert(1)</script>', false);
    });
});

describe('badge and alert', function () {
    test('badge tones use the tinted status pattern', function (string $tone, string $expected) {
        $this->blade('<x-badge :tone="$tone">Status</x-badge>', ['tone' => $tone])
            ->assertSee($expected, false)
            ->assertSee('rounded-pill', false);
    })->with([
        ['neutral', 'bg-slate-100 text-slate-600 ring-slate-300/40'],
        ['success', 'bg-success-500/10 text-success-700 ring-success-500/20'],
        ['info', 'bg-trust-500/10 text-trust-500 ring-trust-500/20'],
        ['warning', 'bg-warning-500/10 text-warning-700 ring-warning-500/20'],
        ['danger', 'bg-danger-500/10 text-danger-700 ring-danger-500/20'],
    ]);

    test('alert types use the tinted status pattern', function (string $type, string $expected, string $role) {
        $this->blade('<x-alert :type="$type">Meldung</x-alert>', ['type' => $type])
            ->assertSee($expected, false)
            ->assertSee('role="'.$role.'"', false);
    })->with([
        ['info', 'bg-trust-500/10 text-trust-500 ring-trust-500/20', 'status'],
        ['success', 'bg-success-500/10 text-success-700 ring-success-500/20', 'status'],
        ['warning', 'bg-warning-500/10 text-warning-700 ring-warning-500/20', 'alert'],
        ['error', 'bg-danger-500/10 text-danger-700 ring-danger-500/20', 'alert'],
    ]);
});

describe('icon', function () {
    test('all 27 icons render as 18px stroke icons that follow the text colour', function (string $name) {
        $this->blade('<x-icon :name="$name" />', ['name' => $name])
            ->assertSee('<svg', false)
            ->assertSee('width="18" height="18"', false)
            ->assertSee('stroke="currentColor"', false)
            ->assertSee('stroke-width="1.8"', false);
    })->with(['plus', 'grid', 'grid2', 'folder', 'template', 'layers', 'doc', 'file', 'settings', 'chevron-right', 'chevron-left', 'chevron-down', 'search', 'bell', 'help', 'warning', 'sparkle', 'upload', 'download', 'refresh', 'edit', 'check', 'x', 'image', 'text', 'trash', 'pin']);

    test('size, stroke and classes can be adjusted', function () {
        $this->blade('<x-icon name="trash" size="20" stroke="2" class="text-danger-500" />')
            ->assertSee('width="20" height="20"', false)
            ->assertSee('stroke-width="2"', false)
            ->assertSee('text-danger-500', false);
    });

    test('an unknown icon renders nothing outside the local environment', function () {
        expect(trim((string) $this->blade('<x-icon name="gibt-es-nicht" />')))->toBe('');
    });

    test('an unknown icon is visible and escaped in the local environment', function () {
        useLocalEnvironment();

        $this->blade('<x-icon :name="$name" />', ['name' => '<b>x</b>'])
            ->assertSee('Unbekanntes Icon', false)
            ->assertDontSee('<b>x</b>', false);
    });
});

describe('loading overlay', function () {
    test('it is announced to screen readers, hidden by default and offers no way to dismiss it', function () {
        $this->blade('<x-loading-overlay />')
            ->assertSee('role="alert"', false)
            ->assertSee('aria-live="assertive"', false)
            ->assertSee('x-cloak', false)
            ->assertSee('loading-start.window', false)
            ->assertSee('loading-stop.window', false)
            ->assertSee('animate-spin text-brand', false)
            ->assertDontSee('x-on:click', false)
            ->assertDontSee('keydown.escape', false);
    });

    test('the layout includes the overlay on every page', function () {
        $this->get('/')->assertSee('loading-start.window', false);
    });
});

describe('leftovers fixed', function () {
    test('a wrong request method shows a german page', function () {
        config(['app.debug' => false]);

        $this->get('/name')->assertStatus(405)->assertSeeText('So geht das nicht')->assertSeeText('Zur Startseite');
    });

    test('the expired form page is german', function () {
        expect(view('errors.419')->render())->toContain('Die Seite war zu lange offen')->toContain('lade die Seite neu');
    });

    test('the loading overlay locks the page behind it for mouse and keyboard', function () {
        $html = $this->withoutVite()->get('/')->getContent();

        expect($html)->toContain('data-loading-overlay')->toContain('element.inert = open')->toContain('$refs.box.focus()');
    });

    test('small grey text uses a colour with enough contrast', function () {
        foreach (File::allFiles(resource_path('views')) as $file) {
            foreach (explode("\n", $file->getContents()) as $line) {
                if (str_contains($line, 'text-slate-400') && ! str_contains($line, 'x-icon') && ! str_contains($line, 'placeholder:text-slate-400')) {
                    $this->fail("text-slate-400 for text in {$file->getRelativePathname()}");
                }
            }
        }

        expect(true)->toBeTrue();
    });
});
