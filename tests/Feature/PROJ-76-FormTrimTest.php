<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

function relatedFixture(array $selected = ['2138745']): array
{
    return [
        'selected' => $selected,
        'problems' => [],
        'groups' => [
            'linked' => [['number' => '2138745', 'title' => 'Frühere Reklamation', 'state' => 'geschlossen', 'articles' => 3, 'date' => '2026-10-02T07:12:00.000Z', 'source' => 'linked']],
            'order' => [['number' => '2138746', 'title' => 'Zur selben Bestellung', 'state' => 'offen', 'articles' => 1, 'date' => null, 'source' => 'order']],
            'customer' => [],
        ],
    ];
}

function renderRelated(array $related, bool $intro = false, ?ViewErrorBag $errors = null): string
{
    view()->share('errors', $errors ?? new ViewErrorBag);
    request()->cookies->set('intro_mode', $intro ? '1' : '0');

    return Blade::render('<x-analysis.related-tickets :related="$related" />', ['related' => $related]);
}

describe('related tickets in the form', function () {
    test('the area is folded and says how many tickets were found and preselected', function () {
        $html = renderRelated(relatedFixture());

        expect($html)->toMatch('/<details class="group min-w-0"\s*>/')->and($html)->toContain('2 gefunden, 1 vorgewählt');
    });

    test('the preselected tickets are still part of the form when it is folded', function () {
        $html = renderRelated(relatedFixture());

        expect($html)->toContain('name="related_set" value="1"')->and($html)->toMatch('/name="related\[\]" value="2138745"[^>]*checked/');
    });

    test('the explanation is only there in the introduction mode', function () {
        expect(renderRelated(relatedFixture()))->not->toContain('Der Verlauf dieser Tickets geht als früherer Verlauf mit in den Vorschlag')
            ->and(renderRelated(relatedFixture(), true))->toContain('Der Verlauf dieser Tickets geht als früherer Verlauf mit in den Vorschlag');
    });

    test('an error in the area opens it', function () {
        $errors = new ViewErrorBag;
        $errors->put('default', new MessageBag(['related_manual' => ['Ungültige Ticketnummer.']]));

        expect(renderRelated(relatedFixture(), false, $errors))->toMatch('/<details class="group min-w-0"\s+open\s*>/');
    });
});
