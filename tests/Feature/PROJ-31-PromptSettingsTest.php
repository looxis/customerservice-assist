<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\Prompt;
use App\Models\Analysis;
use App\Models\Setting;
use App\Models\SettingVersion;
use App\Settings\ModelCatalog;
use App\Settings\SettingsRegistry;
use App\Settings\SettingsStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test', 'ai.providers.openai.url' => 'https://api.openai.test/v1',
        'cache.default' => 'array',
        'staff.admins' => ['Etienne'],
    ]);

    Cache::flush();
    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);
    Http::fake([
        'api.openai.test/v1/models' => fn () => config('test.models_down') ? Http::response([], 500) : Http::response(['data' => [['id' => 'gpt-5.5'], ['id' => 'gpt-5.4-mini'], ['id' => 'gpt-5.4'], ['id' => 'text-embedding-3-small'], ['id' => 'whisper-1'], ['id' => 'dall-e-3'], ['id' => 'gpt-image-1']]]),
        'api.openai.test/v1/responses' => function ($request) {
            if (config('test.responses_status')) {
                return Http::response(['error' => ['message' => 'x']], (int) config('test.responses_status'));
            }

            return in_array($request['model'], ['gpt-5.5', 'gpt-5.4', 'gpt-5.4-mini', 'gpt-5.6'], true) ? Http::response(['id' => 'r', 'output' => []]) : Http::response(['error' => ['code' => 'model_not_found']], 404);
        },
    ]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function promptInput(array $changes = []): array
{
    $store = app(SettingsStore::class);
    $settings = [];

    foreach (SettingsRegistry::forPage('prompts') as $key => $group) {
        $value = $store->value($key);
        $settings[$key] = $group->kind === 'prompt' ? $value['text'] : $value;
    }

    return ['settings' => [...$settings, ...$changes], 'versions' => collect(SettingsRegistry::forPage('prompts'))->map(fn ($group) => $store->version($group->key))->all()];
}

function putPrompts(array $changes = [], string $as = 'Etienne')
{
    return test()->withCookie('staff_name', $as)->put(route('settings.prompts.update'), promptInput($changes));
}

describe('the page', function () {
    test('it lists four models and eight prompts with their defaults and when they are used', function () {
        $html = test()->withCookie('staff_name', 'Nele')->get(route('settings.prompts'))->assertOk()
            ->assertSeeText('Modell: Analyse')->assertSeeText('Modell: Router')->assertSeeText('Prompt: Analyse (Kern)')->assertSeeText('Prompt: Zusatz: Produktinformationen')->assertSeeText('Prompt: Übersetzung')
            ->assertSeeText('Wird dem Kern angehängt, wenn Produktinformationen aus dem Katalog vorliegen.')->assertSeeText('Fachliche Regeln, Befugnisse und Kulanz gehören in die Wissensbasis')
            ->assertSeeText('ändern dürfen nur Admins')->getContent();

        expect($html)->toContain('gpt-5.5')->toContain(e(Prompt::fromFile('analysis')->text === '' ? 'x' : substr(Prompt::fromFile('analysis')->text, 0, 40)))->not->toContain('>Speichern<');
        expect(count(array_filter(SettingsRegistry::forPage('prompts'), fn ($group) => $group->kind === 'prompt')))->toBe(8);
    });

    test('the overview offers the page and the list of models comes from the provider without non-text models', function () {
        test()->withCookie('staff_name', 'Nele')->get(route('settings.index'))->assertSeeText('Prompts und Modell')->assertSee(route('settings.prompts'), false);

        expect(app(ModelCatalog::class)->available())->toBe(['gpt-5.5', 'gpt-5.4-mini', 'gpt-5.4']);
        test()->withCookie('staff_name', 'Etienne')->get(route('settings.prompts'))->assertSee('<option value="gpt-5.4-mini">', false)->assertDontSee('whisper-1');
    });

    test('without the list of the provider the model can still be typed and the page says so', function () {
        Cache::flush();
        config(['test.models_down' => true]);

        test()->withCookie('staff_name', 'Etienne')->get(route('settings.prompts'))->assertOk()->assertSeeText('Die Liste der verfügbaren Modelle ist gerade nicht erreichbar');
    });

    test('someone who is no admin cannot save', function () {
        putPrompts([], 'Nele')->assertForbidden();

        expect(Setting::query()->count())->toBe(0);
    });
});

describe('prompts', function () {
    test('a saved prompt is used for the next call with its version and the file stays the default', function () {
        $default = Prompt::fromFile('summary');

        putPrompts(['prompt_summary' => "Fasse knapp zusammen.\nNur Fakten."])->assertRedirect(route('settings.prompts'))->assertSessionHas('saved', 1);

        $loaded = Prompt::load('summary');
        expect($loaded->text)->toBe("Fasse knapp zusammen.\nNur Fakten.")->and($loaded->version)->toBe($default->version.'~E1')
            ->and(Prompt::fromFile('summary')->text)->toBe($default->text)
            ->and(SettingVersion::query()->sole()->value['base'])->toBe($default->version);
    });

    test('the version of the saved prompt is stored with the proposal and the model is the saved one', function () {
        CaseAgent::fake([['summary' => ['incident' => 'x', 'customer_wish' => 'y'], 'category' => 'product-question', 'case_pattern' => null, 'assessment' => null, 'recommendation' => 'a', 'actions' => [], 'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null], 'reasoning' => 'x', 'knowledge_gaps' => [], 'knowledge_ids' => ['POLICY-001'], 'confidence' => ['level' => 'MITTEL', 'reasons' => []], 'next_step' => 'reply', 'decision' => null, 'internal_steps' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag']]]);
        fakeZammad([zammadArticle(['id' => 1, 'body' => '<p>Frage</p>'])]);
        Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
        putPrompts(['prompt_analysis' => 'Du bist ein Test-Prompt für die Analyse.', 'model_analysis' => 'gpt-5.6'])->assertSessionHas('saved', 2);

        test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['variante' => 'verlauf', 'kundengruppe' => 'unclear', 'kein_produktbezug' => '1'])->assertRedirect();

        $stored = Analysis::query()->latest('id')->firstOrFail();
        expect($stored->prompt_version)->toContain('~E1')->and($stored->model)->toBe('gpt-5.6');
        CaseAgent::assertPrompted(fn ($prompt): bool => true);
    });

    test('a prompt equal to the file removes the saved value and the history keeps counting', function () {
        putPrompts(['prompt_router' => 'Eigener Router-Prompt']);
        expect(Setting::query()->count())->toBe(1);

        putPrompts(['prompt_router' => Prompt::fromFile('router')->text]);

        expect(Setting::query()->count())->toBe(0)->and(SettingVersion::query()->count())->toBe(2)->and(Prompt::load('router')->version)->toBe(Prompt::fromFile('router')->version);
    });

    test('an empty or too long prompt is refused and nothing is saved', function (string $text) {
        putPrompts(['prompt_analysis' => $text])->assertSessionHasErrors('settings.prompt_analysis');

        expect(Setting::query()->count())->toBe(0);
    })->with(['empty' => '', 'only blanks' => "   \n  ", 'too long' => fn () => str_repeat('a', 30001)]);

    test('a prompt that is only a line like a version comment is plain text', function () {
        putPrompts(['prompt_translation' => '<!-- version: fake-1 --> Übersetze.']);

        expect(Prompt::load('translation')->text)->toBe('<!-- version: fake-1 --> Übersetze.')->and(Prompt::load('translation')->version)->toContain('~E1')->not->toContain('fake-1');
    });

    test('when the file of the app is renewed the own version stays and the page says so', function () {
        putPrompts(['prompt_summary' => 'Eigene Fassung']);
        $row = Setting::query()->where('group_key', 'prompt_summary')->firstOrFail();
        $row->update(['value' => ['text' => 'Eigene Fassung', 'base' => 'summary-alt-1']]);

        test()->withCookie('staff_name', 'Etienne')->get(route('settings.prompts'))->assertSeeText('Der Standard der App wurde seit deiner Änderung erneuert');
        expect(Prompt::load('summary')->text)->toBe('Eigene Fassung');
    });

    test('with an unusable stored value the file applies', function () {
        Setting::query()->create(['group_key' => 'prompt_summary', 'value' => ['text' => '   ', 'base' => 'x'], 'version' => 1, 'changed_by' => 'Etienne']);

        expect(Prompt::load('summary')->text)->toBe(Prompt::fromFile('summary')->text);
    });

    test('restoring and resetting work for a prompt', function () {
        putPrompts(['prompt_summary' => 'Fassung eins']);
        putPrompts(['prompt_summary' => 'Fassung zwei'], 'Etienne')->assertSessionHas('saved', 1);

        test()->withCookie('staff_name', 'Etienne')->post(route('settings.restore', ['group' => 'prompt_summary', 'version' => 1]))->assertRedirect(route('settings.prompts'));
        expect(Prompt::load('summary')->text)->toBe('Fassung eins');

        test()->withCookie('staff_name', 'Etienne')->post(route('settings.reset', ['group' => 'prompt_summary']))->assertRedirect(route('settings.prompts'));
        expect(Prompt::load('summary')->text)->toBe(Prompt::fromFile('summary')->text)->and(Setting::query()->count())->toBe(0);
    });

    test('a concurrent change is not overwritten', function () {
        $opened = promptInput(['prompt_summary' => 'Meine Fassung']);
        putPrompts(['prompt_summary' => 'Fassung der anderen']);

        test()->withCookie('staff_name', 'Etienne')->from(route('settings.prompts'))->put(route('settings.prompts.update'), $opened)->assertSessionHasErrors('settings.prompt_summary');
        expect(Prompt::load('summary')->text)->toBe('Fassung der anderen');
    });
});

describe('models', function () {
    test('a model that answers the test call is saved and used', function () {
        putPrompts(['model_summary' => 'gpt-5.6'])->assertSessionHas('saved', 1);

        expect(app(SettingsStore::class)->value('model_summary'))->toBe('gpt-5.6');
        Http::assertSent(fn ($request): bool => str_ends_with($request->url(), '/responses') && $request['model'] === 'gpt-5.6' && $request['max_output_tokens'] === 16 && $request['store'] === false && ! str_contains(json_encode($request->data()), 'sk-test'));
    });

    test('an unknown model is refused with the reason and nothing is saved', function () {
        putPrompts(['model_analysis' => 'gpt-9-ultra', 'prompt_summary' => 'Kein Speichern'])->assertSessionHasErrors('settings.model_analysis');

        expect(Setting::query()->count())->toBe(0);
        expect(session('errors')->first('settings.model_analysis'))->toContain('gibt es beim Anbieter nicht');
    });

    test('a model with a bad name is refused before any call', function () {
        putPrompts(['model_analysis' => 'gpt 5; drop'])->assertSessionHasErrors('settings.model_analysis');

        Http::assertNotSent(fn ($request): bool => str_ends_with($request->url(), '/responses'));
    });

    test('the provider reasons are told apart', function (int $status, string $text) {
        config(['test.responses_status' => $status]);

        expect(app(ModelCatalog::class)->check('gpt-5.4'))->toContain($text);
    })->with([
        'rejected key' => [401, 'Zugang abgelehnt'],
        'quota' => [429, 'Kontingent'],
        'not a text model' => [400, 'kein Modell für Textaufgaben'],
        'other' => [500, 'HTTP 500'],
    ]);

    test('an unchanged model is not tested again and the default applies without a saved value', function () {
        putPrompts()->assertSessionHas('saved', 0);

        Http::assertNotSent(fn ($request): bool => str_ends_with($request->url(), '/responses'));
        expect(app(SettingsStore::class)->value('model_router'))->toBe(config('knowledge.router.model'))->and(app(SettingsStore::class)->value('model_translation'))->toBe(config('analysis.models.translation'));
    });

    test('the key of the provider is never part of the page', function () {
        test()->withCookie('staff_name', 'Etienne')->get(route('settings.prompts'))->assertDontSee('sk-test');
    });
});
