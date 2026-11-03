<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();
    Http::preventStrayRequests();
    $this->travelTo('2026-11-10 09:00:00');
});

/**
 * Point the changelog at a temporary file.
 */
function changelogText(string $markdown): void
{
    $path = sys_get_temp_dir().'/changelog-test-'.bin2hex(random_bytes(6)).'/CHANGELOG.md';

    File::ensureDirectoryExists(dirname($path));
    File::put($path, $markdown);

    config(['app.changelog_path' => $path]);
}

describe('the notice on the start page', function () {
    test('it shows the days of the last week and links to all changes', function () {
        changelogText(<<<'MD'
            Hinweis für die Pflege, wird nicht angezeigt.

            ## 2026-11-10

            - Heute neu

            ## 2026-11-04

            - Vor sechs Tagen

            ## 2026-11-03

            - Vor sieben Tagen
            MD);

        $this->get('/')
            ->assertOk()
            ->assertSee('Aktuelle Änderungen')
            ->assertSeeInOrder(['10. Nov \'26', 'Heute neu', '4. Nov \'26', 'Vor sechs Tagen', route('changes')], false)
            ->assertDontSee('Vor sieben Tagen')
            ->assertDontSee('Hinweis für die Pflege')
            ->assertSee('Für heute schließen')
            ->assertSee('|2026-11-10', false);
    });

    test('it is left out without a day of the last week', function () {
        changelogText("## 2026-10-01\n\n- Alt\n");

        $this->get('/')->assertOk()->assertDontSee('Aktuelle Änderungen')->assertDontSee('Alt');
    });

    test('it is left out without a changelog file', function () {
        config(['app.changelog_path' => sys_get_temp_dir().'/changelog-missing/CHANGELOG.md']);

        $this->get('/')->assertOk()->assertDontSee('Aktuelle Änderungen');
    });
});

describe('the page „Änderungen“', function () {
    test('it shows every day, newest first, with German month names', function () {
        changelogText(<<<'MD'
            ## 2026-10-27

            - Retourenlabel

            ## 2026-11-10

            - Heute neu

            ## 2026-03-02

            - Im März
            MD);

        $this->get(route('changes'))
            ->assertOk()
            ->assertSeeInOrder(['10. Nov \'26', 'Heute neu', '27. Okt \'26', 'Retourenlabel', '2. Mär \'26', 'Im März'], false);
    });

    test('headings without a valid date and empty days are left out', function () {
        changelogText(<<<'MD'
            ## Demnächst

            - Ohne Datum

            ## 2026-02-30

            - Ungültiges Datum

            ## 2026-11-09

            ## 2026-11-08

            - Gültig
            MD);

        $this->get(route('changes'))
            ->assertOk()
            ->assertSee('Gültig')
            ->assertDontSee('9. Nov \'26', false)
            ->assertDontSee('Ohne Datum')
            ->assertDontSee('Ungültiges Datum');
    });

    test('a missing file shows a notice', function () {
        config(['app.changelog_path' => sys_get_temp_dir().'/changelog-missing/CHANGELOG.md']);

        $this->get(route('changes'))->assertOk()->assertSee('Noch keine Änderungen eingetragen');
    });

    test('it is in the navigation below „Über die App“', function () {
        changelogText("## 2026-11-10\n\n- Heute neu\n");

        $this->get(route('changes'))->assertOk()->assertSeeInOrder(['Über die App', route('changes'), 'Änderungen'], false);
    });
});
