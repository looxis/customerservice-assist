<?php

use App\Settings\SettingsRegistry;
use App\Settings\SettingsStore;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->withoutVite();
    config(['cache.default' => 'array', 'staff.admins' => ['Etienne']]);
    Cache::flush();
});

function wismoInput(array $changes = []): array
{
    $store = app(SettingsStore::class);
    $settings = [];

    foreach (SettingsRegistry::forPage('wismo') as $key => $group) {
        $settings[$key] = $store->value($key);
    }

    return ['settings' => [...$settings, ...$changes], 'versions' => collect(SettingsRegistry::forPage('wismo'))->map(fn ($group) => $store->version($group->key))->all()];
}

test('the overview lists the page WISMO-Automatik', function () {
    $this->get(route('settings.index'))->assertOk()->assertSee('WISMO-Automatik');
});

test('the page shows the three modes, off by default, to everyone', function () {
    $this->withCookie('staff_name', 'Cara')->get(route('settings.wismo'))
        ->assertOk()
        ->assertSee('Trockenlauf')
        ->assertSee('LOOXIS Kundenservice')
        ->assertSee('automatisch verfasst')
        ->assertDontSee('Gespeichert werden nur Gruppen');

    expect(app(SettingsStore::class)->value('wismo_mode'))->toBe('off');
});

test('an admin saves the mode', function () {
    $this->withCookie('staff_name', 'Etienne')->put(route('settings.wismo.update'), wismoInput(['wismo_mode' => 'dry']))
        ->assertRedirect(route('settings.wismo'));

    expect(app(SettingsStore::class)->value('wismo_mode'))->toBe('dry');
});

test('an unknown mode is rejected and nothing is saved', function () {
    $this->withCookie('staff_name', 'Etienne')->put(route('settings.wismo.update'), wismoInput(['wismo_mode' => 'always']))
        ->assertSessionHasErrors('settings.wismo_mode');

    expect(app(SettingsStore::class)->value('wismo_mode'))->toBe('off');
});

test('placeholders in square brackets are not allowed in the notice', function () {
    $this->withCookie('staff_name', 'Etienne')->put(route('settings.wismo.update'), wismoInput(['wismo_notice' => 'Hallo [Name], automatisch verfasst']))
        ->assertSessionHasErrors('settings.wismo_notice');
});

test('a normal staff member may not save', function () {
    $this->withCookie('staff_name', 'Cara')->put(route('settings.wismo.update'), wismoInput(['wismo_mode' => 'on']))->assertForbidden();

    expect(app(SettingsStore::class)->value('wismo_mode'))->toBe('off');
});
