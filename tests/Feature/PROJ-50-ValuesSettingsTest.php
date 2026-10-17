<?php

use App\Knowledge\KnowledgeLibrary;
use App\Knowledge\KnowledgeSelector;
use App\Models\Setting;
use App\Models\SettingVersion;
use App\Settings\KnowledgeValues;
use App\Settings\SettingsRegistry;
use App\Settings\SettingsStore;

beforeEach(function () {
    $this->withoutVite();
    config(['cache.default' => 'array', 'staff.admins' => ['Etienne']]);

    knowledgeBase([
        'policies/policy-001-a.md' => knowledgeDoc(['id' => 'POLICY-001', 'title' => 'Für alle']),
        'policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002', 'title' => 'Nur Fachhändler', 'customer_types' => ['b2b-reseller'], 'retrieval' => 'binding']),
        'policies/policy-003-c.md' => knowledgeDoc(['id' => 'POLICY-003', 'title' => 'Nur Amazon', 'sales_channels' => ['amazon']]),
        'policies/policy-004-d.md' => knowledgeDoc(['id' => 'POLICY-004', 'title' => 'Mit Vorgang', 'actions' => ['refund'], 'retrieval' => 'binding']),
        'policies/policy-005-e.md' => knowledgeDoc(['id' => 'POLICY-005', 'title' => 'Mit Kategorie', 'categories' => ['complaint']]),
    ]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function valuesInput(array $changes = []): array
{
    $store = app(SettingsStore::class);
    $settings = [];

    foreach (SettingsRegistry::forPage('values') as $key => $group) {
        $settings[$key] = $store->value($key);
    }

    return ['settings' => [...$settings, ...$changes], 'versions' => collect(SettingsRegistry::forPage('values'))->map(fn ($group) => $store->version($group->key))->all()];
}

function saveValues(array $changes = [], string $as = 'Etienne')
{
    return test()->withCookie('staff_name', $as)->put(route('settings.values.update'), valuesInput($changes));
}

function newGroup(array $fields = []): array
{
    return [...app(SettingsStore::class)->value('kn_groups'), ['key' => 'neu', 'label' => 'Neue Gruppe', 'customer_type' => 'b2b-reseller', 'sales_channel' => 'amazon', 'status' => 'active', ...$fields]];
}

function groupRow(string $key): array
{
    return collect(app(SettingsStore::class)->value('kn_groups'))->firstWhere('key', $key);
}

describe('the page', function () {
    test('everyone sees the six groups with the former values, only admins can change them', function () {
        $html = test()->withCookie('staff_name', 'Nele')->get(route('settings.values'))->assertOk()
            ->assertSeeText('Kundenarten')->assertSeeText('Vertriebskanäle')->assertSeeText('Kundengruppen')->assertSeeText('EOCS-Kanalnamen')->assertSeeText('Fallkategorien')->assertSeeText('Vorgänge')
            ->assertSeeText('ändern dürfen nur Admins')->getContent();

        expect($html)->toContain('b2b-reseller')->toContain('Neuversand')->toContain('Noch unklar')->not->toContain('>Speichern<');
        test()->withCookie('staff_name', 'Etienne')->get(route('settings.values'))->assertOk()->assertSeeText('Eintrag hinzufügen')->assertSeeText('Erlaubte Werte für Wissensautoren');
        test()->withCookie('staff_name', 'Nele')->get(route('settings.index'))->assertSeeText('Kundengruppen und Werte');
    });

    test('someone who is no admin cannot save, release, restore or reset', function () {
        saveValues([], 'Nele')->assertForbidden();
        test()->withCookie('staff_name', 'Nele')->post(route('settings.groups.release', ['group' => 'neu']), ['version' => 0])->assertForbidden();
        test()->withCookie('staff_name', 'Nele')->post(route('settings.reset', ['group' => 'kn_actions']))->assertForbidden();

        expect(Setting::query()->count())->toBe(0);
    });

    test('nothing saved means the behaviour of before', function () {
        saveValues()->assertSessionHas('saved', 0);

        expect(Setting::query()->count())->toBe(0)->and(array_keys(config('knowledge.actions')))->toContain('refund')->and(count(app(KnowledgeSelector::class)->selectableGroups()))->toBe(6);
    });

    test('the copy text lists the current allowed values', function () {
        $text = app(KnowledgeValues::class)->copyText();

        expect($text)->toContain('- b2c – Privatkunde')->toContain('- amazon – Amazon')->toContain('- complaint – Reklamation')->toContain('- refund – Erstattung')->toContain('Leere Listen bei customer_types');
        test()->withCookie('staff_name', 'Nele')->get(route('knowledge.index'))->assertOk()->assertSee('Erlaubte Werte für den KI-Chat', false);
    });
});

describe('a new customer group', function () {
    test('starts in preparation, is not selectable and has a checklist of the documents it does not get', function () {
        saveValues(['kn_groups' => newGroup(['status' => 'active', 'customer_type' => 'b2b-pro', 'sales_channel' => 'masterpics'])])->assertSessionHas('saved', 1);

        expect(groupRow('neu')['status'])->toBe('preparing')
            ->and(array_map(fn ($group) => $group->key, app(KnowledgeSelector::class)->selectableGroups()))->not->toContain('neu')
            ->and(app(KnowledgeSelector::class)->customerGroup('neu')?->label)->toBe('Neue Gruppe');

        $list = app(KnowledgeValues::class)->checklist(app(KnowledgeSelector::class)->customerGroup('neu'));
        expect(collect($list['binding'])->pluck('id')->all())->toBe(['POLICY-002'])->and(collect($list['others'])->pluck('id')->all())->toBe(['POLICY-003']);

        test()->withCookie('staff_name', 'Etienne')->get(route('settings.values'))->assertSeeText('Prüfliste: „Neue Gruppe“ ist in Vorbereitung')->assertSeeText('POLICY-003')->assertSeeText('Prüfung abgeschlossen, Gruppe freigeben');
    });

    test('a combination that excludes a binding rule needs the confirmation of each one', function () {
        saveValues(['kn_groups' => newGroup(['customer_type' => 'b2c', 'sales_channel' => 'masterpics'])]);
        $list = app(KnowledgeValues::class)->checklist(app(KnowledgeSelector::class)->customerGroup('neu'));
        expect(collect($list['binding'])->pluck('id')->all())->toBe(['POLICY-002']);

        $version = app(SettingsStore::class)->version('kn_groups');
        test()->withCookie('staff_name', 'Etienne')->post(route('settings.groups.release', ['group' => 'neu']), ['version' => $version])->assertSessionHasErrors('release');
        expect(groupRow('neu')['status'])->toBe('preparing');

        test()->withCookie('staff_name', 'Etienne')->post(route('settings.groups.release', ['group' => 'neu']), ['version' => $version, 'confirmed' => ['POLICY-002']])->assertSessionHas('released', 'Neue Gruppe');

        $row = groupRow('neu');
        expect($row['status'])->toBe('active')->and($row['released_by'])->toBe('Etienne')->and($row['confirmed_binding'])->toBe(['POLICY-002'])->and($row['released_at'])->not->toBeNull()
            ->and(SettingVersion::query()->where('group_key', 'kn_groups')->orderByDesc('version')->first()->kind)->toBe('freigegeben')
            ->and(array_map(fn ($group) => $group->key, app(KnowledgeSelector::class)->selectableGroups()))->toContain('neu');
    });

    test('a group with nothing binding excluded can be released at once, a stale version cannot', function () {
        saveValues(['kn_groups' => newGroup(['customer_type' => 'b2b-reseller', 'sales_channel' => 'amazon'])]);
        $version = app(SettingsStore::class)->version('kn_groups');

        test()->withCookie('staff_name', 'Etienne')->post(route('settings.groups.release', ['group' => 'neu']), ['version' => $version - 1])->assertSessionHasErrors('release');
        expect(groupRow('neu')['status'])->toBe('preparing');

        test()->withCookie('staff_name', 'Etienne')->post(route('settings.groups.release', ['group' => 'neu']), ['version' => $version])->assertSessionHas('released');
        expect(groupRow('neu')['status'])->toBe('active');
        test()->withCookie('staff_name', 'Etienne')->post(route('settings.groups.release', ['group' => 'neu']), ['version' => app(SettingsStore::class)->version('kn_groups')])->assertNotFound();
    });

    test('a document that is widened for the group leaves the checklist', function () {
        saveValues(['kn_groups' => newGroup(['customer_type' => 'b2c', 'sales_channel' => 'masterpics'])]);

        knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['id' => 'POLICY-001', 'title' => 'Für alle']),
            'policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002', 'title' => 'Nur Fachhändler', 'customer_types' => ['b2b-reseller', 'b2c'], 'retrieval' => 'binding']),
            'policies/policy-003-c.md' => knowledgeDoc(['id' => 'POLICY-003', 'title' => 'Nur Amazon', 'sales_channels' => ['amazon']]),
        ]);

        $list = app(KnowledgeValues::class)->checklist(app(KnowledgeSelector::class)->customerGroup('neu'));
        expect(collect($list['binding'])->pluck('id')->all())->toBe([])->and(collect($list['others'])->pluck('id')->all())->toBe(['POLICY-003']);
    });

    test('changing the customer type or channel of an active group puts it back in preparation', function () {
        $groups = array_map(fn ($group) => $group['key'] === 'reseller' ? [...$group, 'sales_channel' => 'amazon'] : $group, app(SettingsStore::class)->value('kn_groups'));

        saveValues(['kn_groups' => $groups])->assertSessionHas('saved', 1);

        expect(groupRow('reseller')['status'])->toBe('preparing')->and(app(KnowledgeSelector::class)->selectableGroups())->toHaveCount(5);
    });

    test('the form for a case lists only active groups and a stored one still resolves', function () {
        saveValues(['kn_groups' => newGroup()]);
        $groups = array_map(fn ($group) => $group['key'] === 'looxis-pro' ? [...$group, 'status' => 'inactive'] : $group, app(SettingsStore::class)->value('kn_groups'));
        saveValues(['kn_groups' => $groups])->assertSessionHas('saved', 1);

        $keys = array_map(fn ($group) => $group->key, app(KnowledgeSelector::class)->selectableGroups());
        expect($keys)->not->toContain('neu')->not->toContain('looxis-pro')->toContain('reseller')
            ->and(app(KnowledgeSelector::class)->customerGroup('looxis-pro')?->label)->toBe('LOOXIS-Pro')->and(app(KnowledgeSelector::class)->customerGroup('looxis-pro')->isSelectable())->toBeFalse();
    });
});

describe('protection of the values', function () {
    test('a value that a document names cannot be removed, an unused one can', function () {
        $actions = array_values(array_filter(app(SettingsStore::class)->value('kn_actions'), fn ($action) => ! in_array($action['key'], ['refund', 'pickup'], true)));

        saveValues(['kn_actions' => $actions])->assertSessionHasErrors('settings.kn_actions');
        expect(session('errors')->first('settings.kn_actions'))->toContain('„refund“')->toContain('POLICY-004')->and(Setting::query()->count())->toBe(0);

        saveValues(['kn_actions' => array_values(array_filter(app(SettingsStore::class)->value('kn_actions'), fn ($action) => $action['key'] !== 'pickup'))])->assertSessionHas('saved', 1);
        expect(config('knowledge.actions'))->not->toHaveKey('pickup')->toHaveKey('refund');
    });

    test('categories, customer types and channels are guarded the same way', function () {
        saveValues(['kn_categories' => array_values(array_filter(app(SettingsStore::class)->value('kn_categories'), fn ($row) => $row['key'] !== 'complaint'))])->assertSessionHasErrors('settings.kn_categories');
        saveValues(['kn_customer_types' => array_values(array_filter(app(SettingsStore::class)->value('kn_customer_types'), fn ($row) => $row['key'] !== 'b2b-pro'))])->assertSessionHasErrors('settings.kn_customer_types');
        expect(session('errors')->first('settings.kn_customer_types'))->toContain('Kundengruppe');
        saveValues(['kn_sales_channels' => array_values(array_filter(app(SettingsStore::class)->value('kn_sales_channels'), fn ($row) => $row['key'] !== 'amazon'))])->assertSessionHasErrors('settings.kn_sales_channels');
        expect(collect(session('errors')->get('settings.kn_sales_channels'))->implode(' '))->toContain('POLICY-003');

        expect(Setting::query()->count())->toBe(0);
    });

    test('customer groups are never removed and "Noch unklar" stays as it is', function () {
        $groups = collect(app(SettingsStore::class)->value('kn_groups'));

        saveValues(['kn_groups' => $groups->reject(fn ($group) => $group['key'] === 'reseller')->values()->all()])->assertSessionHasErrors('settings.kn_groups');
        expect(session('errors')->first('settings.kn_groups'))->toContain('deaktiviert');

        saveValues(['kn_groups' => $groups->reject(fn ($group) => $group['key'] === 'unclear')->values()->all()])->assertSessionHasErrors('settings.kn_groups');
        saveValues(['kn_groups' => $groups->map(fn ($group) => $group['key'] === 'unclear' ? [...$group, 'customer_type' => 'b2c', 'sales_channel' => 'amazon'] : $group)->all()])->assertSessionHas('saved', 0);
        saveValues(['kn_groups' => $groups->map(fn ($group) => $group['key'] === 'unclear' ? [...$group, 'status' => 'inactive'] : $group)->all()])->assertSessionHas('saved', 0);

        expect(groupRow('unclear')['status'])->toBe('active');
    });

    test('invalid entries are refused with the place named', function (string $group, array $rows) {
        saveValues([$group => $rows])->assertSessionHasErrors("settings.{$group}");

        expect(Setting::query()->count())->toBe(0);
    })->with([
        'key with capitals' => ['kn_actions', [['key' => 'Gift Card', 'label' => 'Gutschein', 'hint' => '']]],
        'key too short' => ['kn_categories', [['key' => 'a', 'label' => 'Eins']]],
        'no name' => ['kn_actions', [['key' => 'gift-card', 'label' => '', 'hint' => '']]],
        'hint too long' => ['kn_actions', [['key' => 'gift-card', 'label' => 'Gutschein', 'hint' => 'x']]],
        'no entries' => ['kn_customer_types', []],
    ]);

    test('a group needs an existing type and channel and no combination twice', function () {
        saveValues(['kn_groups' => newGroup(['customer_type' => 'b2x'])])->assertSessionHasErrors('settings.kn_groups');
        saveValues(['kn_groups' => newGroup(['sales_channel' => 'nirgends'])])->assertSessionHasErrors('settings.kn_groups');
        saveValues(['kn_groups' => newGroup(['customer_type' => 'b2c', 'sales_channel' => 'amazon'])])->assertSessionHasErrors('settings.kn_groups');
        saveValues(['kn_groups' => newGroup(['customer_type' => 'b2c', 'sales_channel' => null])])->assertSessionHasErrors('settings.kn_groups');

        expect(Setting::query()->count())->toBe(0);
    });

    test('a new type and a new group can be saved in one go', function () {
        saveValues([
            'kn_customer_types' => [...app(SettingsStore::class)->value('kn_customer_types'), ['key' => 'b2b-kaufland', 'label' => 'Kaufland']],
            'kn_groups' => newGroup(['customer_type' => 'b2b-kaufland', 'sales_channel' => 'amazon']),
        ])->assertSessionHas('saved', 2);

        expect(config('knowledge.customer_types'))->toContain('b2b-kaufland')->and(groupRow('neu')['status'])->toBe('preparing');
    });

    test('an EOCS channel name needs an existing channel and is unique without regard to case', function () {
        $rows = app(SettingsStore::class)->value('kn_order_channels');

        saveValues(['kn_order_channels' => [...$rows, ['name' => 'Webshop XY', 'channel' => 'nirgends']]])->assertSessionHasErrors('settings.kn_order_channels');
        saveValues(['kn_order_channels' => [...$rows, ['name' => 'Webshop XY', 'channel' => 'looxis-de'], ['name' => 'webshop xy', 'channel' => 'amazon']]])->assertSessionHas('saved', 1);

        expect(config('knowledge.order_channels'))->toHaveKey('Webshop XY')->and(collect(config('knowledge.order_channels'))->keys()->filter(fn ($name) => strtolower($name) === 'webshop xy')->count())->toBe(1);
    });
});

describe('new values reach the rest of the app', function () {
    test('a new action is allowed in documents, given to the model with its hint and shown with its name', function () {
        saveValues(['kn_actions' => [...app(SettingsStore::class)->value('kn_actions'), ['key' => 'gift-card', 'label' => 'Gutschein', 'hint' => 'Wertgutschein statt Erstattung']]])->assertSessionHas('saved', 1);

        expect(config('knowledge.actions.gift-card'))->toBe('Gutschein')->and(config('knowledge.action_hints.gift-card'))->toBe('Wertgutschein statt Erstattung');

        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['id' => 'POLICY-001', 'title' => 'Gutschein-Regel', 'actions' => ['gift-card'], 'retrieval' => 'binding'])]);
        expect(app(KnowledgeLibrary::class)->errors()->count())->toBe(0);
        expect(app(KnowledgeValues::class)->documentsUsing('actions', 'gift-card'))->toBe(['POLICY-001']);
    });

    test('a new category and its name apply', function () {
        saveValues(['kn_categories' => [...app(SettingsStore::class)->value('kn_categories'), ['key' => 'warranty', 'label' => 'Garantiefall']]]);

        expect(config('knowledge.categories'))->toContain('warranty')->and(config('knowledge.category_labels.warranty'))->toBe('Garantiefall');
    });

    test('the former names of customer types and channels are the defaults', function () {
        expect(collect(app(SettingsStore::class)->value('kn_customer_types'))->pluck('label', 'key')->all())->toMatchArray(['b2c' => 'Privatkunde', 'b2b-reseller' => 'Foto-Fachhändler / Reseller'])
            ->and(collect(app(SettingsStore::class)->value('kn_sales_channels'))->pluck('label', 'key')->all())->toMatchArray(['looxis-de' => 'looxis.de', 'amazon' => 'Amazon']);
    });
});

describe('history, restore and reset of values', function () {
    test('a reset or restore that would remove something in use is refused', function () {
        saveValues(['kn_actions' => [...app(SettingsStore::class)->value('kn_actions'), ['key' => 'gift-card', 'label' => 'Gutschein', 'hint' => '']]]);
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['id' => 'POLICY-001', 'title' => 'Gutschein-Regel', 'actions' => ['gift-card'], 'retrieval' => 'binding'])]);

        test()->withCookie('staff_name', 'Etienne')->post(route('settings.reset', ['group' => 'kn_actions']))->assertRedirect(route('settings.values'))->assertSessionHasErrors('settings.kn_actions');
        expect(session('errors')->first('settings.kn_actions'))->toContain('gift-card')->and(app(SettingsStore::class)->value('kn_actions'))->toContain(['key' => 'gift-card', 'label' => 'Gutschein', 'hint' => '']);
    });

    test('an earlier version can be restored and the default can be reset to when nothing is in use', function () {
        saveValues(['kn_categories' => [...app(SettingsStore::class)->value('kn_categories'), ['key' => 'warranty', 'label' => 'Garantiefall']]]);
        saveValues(['kn_categories' => [...app(SettingsStore::class)->value('kn_categories'), ['key' => 'service', 'label' => 'Service']]]);

        test()->withCookie('staff_name', 'Etienne')->post(route('settings.restore', ['group' => 'kn_categories', 'version' => 1]))->assertSessionHas('restored');
        expect(collect(app(SettingsStore::class)->value('kn_categories'))->pluck('key')->all())->toContain('warranty')->not->toContain('service');

        test()->withCookie('staff_name', 'Etienne')->post(route('settings.reset', ['group' => 'kn_categories']))->assertSessionHas('reset');
        expect(Setting::query()->count())->toBe(0)->and(SettingVersion::query()->where('group_key', 'kn_categories')->count())->toBe(4);
        test()->withCookie('staff_name', 'Nele')->get(route('settings.values'))->assertOk()->assertSeeText('Verlauf (4)');
    });

    test('a restored group that differs from the current one has to be checked again', function () {
        saveValues(['kn_groups' => newGroup(['customer_type' => 'b2b-reseller', 'sales_channel' => 'amazon'])]);
        test()->withCookie('staff_name', 'Etienne')->post(route('settings.groups.release', ['group' => 'neu']), ['version' => app(SettingsStore::class)->version('kn_groups')]);
        $released = app(SettingsStore::class)->version('kn_groups');
        saveValues(['kn_groups' => array_map(fn ($group) => $group['key'] === 'neu' ? [...$group, 'sales_channel' => 'looxis-de'] : $group, app(SettingsStore::class)->value('kn_groups'))]);
        expect(groupRow('neu')['status'])->toBe('preparing');

        test()->withCookie('staff_name', 'Etienne')->post(route('settings.restore', ['group' => 'kn_groups', 'version' => $released]))->assertSessionHas('restored');

        expect(groupRow('neu')['status'])->toBe('preparing')->and(groupRow('neu')['sales_channel'])->toBe('amazon');
    });

    test('a stored value that is unusable falls back on the defaults', function () {
        Setting::query()->create(['group_key' => 'kn_actions', 'value' => [['key' => 'X', 'label' => '']], 'version' => 1, 'changed_by' => 'Etienne']);

        expect(collect(app(SettingsStore::class)->value('kn_actions'))->pluck('key')->all())->toContain('refund');
    });
});
