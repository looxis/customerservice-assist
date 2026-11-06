<?php

beforeEach(function () {
    $this->withoutVite();

    config(['staff.admins' => ['Etienne']]);
});

it('keeps the labeling pages from everyone but admins', function (string $url) {
    $this->withCookie('staff_name', 'Nele')->get($url)->assertForbidden();
})->with(['/beschriftung', '/beschriftung/pruefen', '/beschriftung/tickets']);

it('shows the labeling pages to admins with the seven categories', function () {
    $this->withCookie('staff_name', 'Etienne')->get('/beschriftung')
        ->assertOk()
        ->assertSeeText('train:wismo')
        ->assertSeeText('train:technischer-support')
        ->assertSeeText('Vorschau mit erfundenen Beispieldaten');
});

it('lists the sidebar entry only for admins', function () {
    $this->withCookie('staff_name', 'Etienne')->get('/beschriftung')->assertSee(route('labeling.index'));
    $this->withCookie('staff_name', 'Nele')->get('/')->assertDontSee(route('labeling.index'));
});

it('hides the suggestion for tickets of the test set', function () {
    $this->withCookie('staff_name', 'Etienne')->get('/beschriftung/pruefen?warteschlange=test')
        ->assertOk()
        ->assertSeeText('Der Vorschlag ist verborgen')
        ->assertDontSee('data-key="Enter"', false);

    $this->withCookie('staff_name', 'Etienne')->get('/beschriftung/pruefen')
        ->assertOk()
        ->assertSee('data-key="Enter"', false);
});

it('filters the ticket list by category', function () {
    $this->withCookie('staff_name', 'Etienne')->get('/beschriftung/tickets?kategorie=wismo')->assertOk()->assertSeeText('Wo bleibt mein Paket');
});

it('answers the not yet connected actions with a notice', function () {
    $this->withCookie('staff_name', 'Etienne')->from('/beschriftung')->post('/beschriftung/laeufe')->assertRedirect('/beschriftung')->assertSessionHas('error');
});
