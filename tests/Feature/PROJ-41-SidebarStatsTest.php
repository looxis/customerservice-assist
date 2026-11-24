<?php

use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->withoutVite();
    config(['cache.default' => 'array', 'staff.admins' => ['Etienne']]);
    Cache::flush();
});

afterEach(fn () => cleanUpKnowledgeBases());

describe('sidebar', function () {
    test('the figures are no longer shown in the sidebar', function () {
        $this->get('/')->assertDontSeeText('Aktueller Stand')->assertDontSeeText('Gemeldete Wissenslücken');
    });
});
