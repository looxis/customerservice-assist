<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangeSettingVersionRequest;
use App\Http\Requests\DetectionSettingsRequest;
use App\Http\Requests\MailSettingsRequest;
use App\Http\Requests\PromptSettingsRequest;
use App\Http\Requests\ReleaseGroupRequest;
use App\Http\Requests\TryDetectionRequest;
use App\Http\Requests\TryMailSettingsRequest;
use App\Http\Requests\ValuesSettingsRequest;
use App\Knowledge\CustomerGroup;
use App\Models\SettingVersion;
use App\Orders\OrderFormats;
use App\Orders\OrderNumber;
use App\Orders\OrderNumberDetector;
use App\ProductForge\SkuDetector;
use App\Settings\KnowledgeValueRules;
use App\Settings\KnowledgeValues;
use App\Settings\MailRules;
use App\Settings\ModelCatalog;
use App\Settings\SettingGroup;
use App\Settings\SettingsRegistry;
use App\Settings\SettingsStore;
use App\Staff\StaffDirectory;
use App\Staff\TestMode;
use App\Zammad\TicketArticle;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadException;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The page "Einstellungen" (PROJ-27): an overview of the sub pages and the sub page "Mail-Anzeige". Everyone
 * may look; only admins save, restore or reset.
 */
class SettingsController extends Controller
{
    public function index(): View
    {
        return view('settings.index', ['pages' => SettingsRegistry::pages()]);
    }

    public function mail(Request $request, SettingsStore $settings, TestMode $testMode): View
    {
        return view('settings.mail', [...$this->pageData(MailSettingsRequest::PAGE, $request, $settings, $testMode), 'trial' => session('trial')]);
    }

    public function prompts(Request $request, SettingsStore $settings, TestMode $testMode, ModelCatalog $catalog): View
    {
        $data = $this->pageData(PromptSettingsRequest::PAGE, $request, $settings, $testMode);
        $outdated = collect($data['groups'])->filter(fn (SettingGroup $group): bool => $group->kind === SettingGroup::PROMPT && ! $data['defaults'][$group->key] && ($data['values'][$group->key]['base'] ?? null) !== $group->baseVersion())->keys()->all();

        return view('settings.prompts', [...$data, 'outdated' => $outdated, 'models' => $catalog->available()]);
    }

    public function detection(Request $request, SettingsStore $settings, TestMode $testMode): View
    {
        return view('settings.detection', [...$this->pageData(DetectionSettingsRequest::PAGE, $request, $settings, $testMode), 'trial' => session('trial')]);
    }

    public function updateDetection(DetectionSettingsRequest $request, SettingsStore $settings, StaffDirectory $staff): RedirectResponse
    {
        return $this->saveDrafts($request, DetectionSettingsRequest::PAGE, $settings, $staff);
    }

    /**
     * Show what the saved rules and the rules as typed in (not saved) recognise in a text. Saves nothing.
     */
    public function tryDetection(TryDetectionRequest $request, SettingsStore $settings, OrderNumberDetector $detector, SkuDetector $skus): RedirectResponse
    {
        $text = (string) $request->validated('sample');
        $drafts = $request->drafts();
        $draftDetector = $detector->using(OrderFormats::from($drafts['order_formats'] ?? $settings->value('order_formats')));
        $describe = fn (OrderNumber $number): array => ['value' => $number->value, 'format' => $number->format->name, 'automatic' => ! $number->isEocsId()];

        return redirect()->route('settings.detection')->withInput()->with('trial', [
            'saved' => ['orders' => array_map($describe, $detector->inText($text)), 'skus' => $skus->in([$text])],
            'draft' => [
                'orders' => array_map($describe, $draftDetector->inText($text)),
                'skus' => $skus->with([$text], (array) ($drafts['sku_keywords'] ?? $settings->value('sku_keywords')), (int) ($drafts['sku_digits'] ?? $settings->value('sku_digits')), (int) ($drafts['sku_max'] ?? $settings->value('sku_max'))),
            ],
            'notes' => $request->notes(),
        ]);
    }

    public function values(Request $request, SettingsStore $settings, TestMode $testMode, KnowledgeValues $values): View
    {
        $data = $this->pageData(ValuesSettingsRequest::PAGE, $request, $settings, $testMode);
        $groups = collect((array) $settings->value('kn_groups'));
        $checklists = [];

        foreach ($groups->where('status', CustomerGroup::PREPARING) as $row) {
            $checklists[$row['key']] = $values->checklist(new CustomerGroup($row['key'], $row['label'], $row['customer_type'], $row['sales_channel'], $row['status']));
        }

        return view('settings.values', [
            ...$data,
            'checklists' => $checklists,
            'options' => ['types' => collect((array) $settings->value('kn_customer_types'))->pluck('label', 'key')->all(), 'channels' => collect((array) $settings->value('kn_sales_channels'))->pluck('label', 'key')->all()],
            'copyText' => $values->copyText(),
        ]);
    }

    public function updateValues(ValuesSettingsRequest $request, SettingsStore $settings, StaffDirectory $staff, KnowledgeValues $values): RedirectResponse
    {
        $response = $this->saveDrafts($request, ValuesSettingsRequest::PAGE, $settings, $staff);
        $values->apply();

        return $response;
    }

    /**
     * Release a customer group after its documents were checked; binding rules that stay excluded are confirmed one by one.
     */
    public function release(string $group, ReleaseGroupRequest $request, SettingsStore $settings, StaffDirectory $staff, KnowledgeValues $values): RedirectResponse
    {
        $rows = collect((array) $settings->value('kn_groups'));
        $row = $rows->firstWhere('key', $group);
        abort_if($row === null || $row['status'] !== CustomerGroup::PREPARING, 404);

        $back = redirect()->to(route('settings.values').'#gruppe-'.$group);

        if ((int) $request->validated('version') !== $settings->version('kn_groups')) {
            return $back->withErrors(['release' => 'Die Kundengruppen wurden inzwischen geändert. Bitte die Seite neu laden und die Freigabe erneut versuchen.']);
        }

        $binding = array_column($values->checklist(new CustomerGroup($row['key'], $row['label'], $row['customer_type'], $row['sales_channel'], $row['status']))['binding'], 'id');
        $missing = array_diff($binding, (array) $request->validated('confirmed', []));

        if ($missing !== []) {
            return $back->withErrors(['release' => 'Bitte jede verbindliche Regel einzeln bestätigen, die für diese Gruppe ausgeschlossen bleibt: '.implode(', ', $missing).'.']);
        }

        $released = $rows->map(fn (array $item): array => $item['key'] === $group ? [...$item, 'status' => CustomerGroup::ACTIVE, 'released_by' => (string) $staff->current($request), 'released_at' => now()->toIso8601String(), 'confirmed_binding' => array_values($binding)] : $item)->all();
        $settings->save('kn_groups', $released, (string) $staff->current($request), SettingsStore::RELEASED);
        $values->apply();

        return $back->with('released', $row['label']);
    }

    /**
     * @return array<string, mixed>
     */
    private function pageData(string $page, Request $request, SettingsStore $settings, TestMode $testMode): array
    {
        $groups = SettingsRegistry::forPage($page);

        return [
            'groups' => $groups,
            'canEdit' => $testMode->isAvailable($request),
            'values' => collect($groups)->map(fn ($group): mixed => $settings->value($group->key))->all(),
            'versions' => collect($groups)->map(fn ($group): int => $settings->version($group->key))->all(),
            'defaults' => collect($groups)->map(fn ($group): bool => $settings->isDefault($group->key))->all(),
            'last' => collect($groups)->map(fn ($group): ?array => $settings->lastChange($group->key))->all(),
            'history' => collect($groups)->map(fn ($group) => $settings->history($group->key))->all(),
        ];
    }

    /**
     * Save the groups that changed, each as a new version. Nothing is overwritten that someone else changed in the meantime.
     */
    public function updateMail(MailSettingsRequest $request, SettingsStore $settings, StaffDirectory $staff): RedirectResponse
    {
        return $this->saveDrafts($request, MailSettingsRequest::PAGE, $settings, $staff);
    }

    public function updatePrompts(PromptSettingsRequest $request, SettingsStore $settings, StaffDirectory $staff): RedirectResponse
    {
        return $this->saveDrafts($request, PromptSettingsRequest::PAGE, $settings, $staff);
    }

    private function saveDrafts(MailSettingsRequest $request, string $page, SettingsStore $settings, StaffDirectory $staff): RedirectResponse
    {
        $route = SettingsRegistry::pages()[$page]['route'];
        $by = (string) $staff->current($request);
        $changed = [];

        foreach ($request->drafts() as $key => $value) {
            if ($value === $settings->value($key)) {
                continue;
            }

            if ($request->openedVersion($key) !== $settings->version($key)) {
                $last = $settings->lastChange($key);

                return redirect()->route($route)->withInput()->withErrors(["settings.{$key}" => 'Diese Gruppe wurde inzwischen geändert'.($last === null ? '' : ' von '.$last['by'].' am '.CarbonImmutable::parse($last['at'])->setTimezone('Europe/Berlin')->format('d.m.Y, H:i').' Uhr').'. Es wurde nichts gespeichert; bitte die Seite neu laden und die Änderung noch einmal eintragen.']);
            }

            $changed[$key] = $value;
        }

        foreach ($changed as $key => $value) {
            $settings->save($key, $value, $by);
        }

        return redirect()->route($route)->with('saved', count($changed))->with('notes', $request->notes());
    }

    /**
     * Show a ticket cleaned with the saved rules and with the rules as typed in (not saved). Saves nothing.
     */
    public function tryMail(TryMailSettingsRequest $request, MailRules $rules): RedirectResponse
    {
        try {
            $saved = (new ZammadClient(config('services.zammad'), $rules->messageBody()))->ticket((string) $request->validated('ticket'));
            $draft = (new ZammadClient(config('services.zammad'), $rules->messageBody($request->drafts())))->ticket((string) $request->validated('ticket'));
        } catch (ZammadException $exception) {
            return redirect()->route('settings.mail')->withInput()->with('trial', ['error' => $exception->problem->message((string) $request->validated('ticket'))]);
        }

        $messages = [];
        $articles = array_slice($saved->articles, -10, null, true);

        foreach ($articles as $index => $article) {
            $other = $draft->articles[$index] ?? null;
            $parts = fn (?TicketArticle $item): array => ['body' => $item?->body->toHtml() ?? '', 'quote' => $item?->quote?->toHtml(), 'signature' => $item?->signature?->toHtml()];

            $messages[] = [
                'sender' => $article->senderName,
                'kind' => $article->kind->label(),
                'at' => $article->createdAt->setTimezone('Europe/Berlin')->format('d.m.Y, H:i'),
                'saved' => $parts($article),
                'draft' => $parts($other),
                'same' => $parts($article) === $parts($other),
            ];
        }

        return redirect()->route('settings.mail')->withInput()->with('trial', ['ticket' => $saved->number, 'title' => $saved->title, 'messages' => $messages, 'notes' => $request->notes()]);
    }

    public function restore(string $group, int $version, ChangeSettingVersionRequest $request, SettingsStore $settings, StaffDirectory $staff, KnowledgeValues $values): RedirectResponse
    {
        abort_if(SettingsRegistry::group($group) === null, 404);

        if (in_array($group, KnowledgeValues::GROUP_KEYS, true)) {
            $old = SettingVersion::query()->where('group_key', $group)->where('version', $version)->firstOrFail();

            return $this->guarded($group, $old->value, SettingsStore::RESTORED, 'restored', $settings, $staff, $request, $values);
        }

        $settings->restore($group, $version, (string) $staff->current($request)) || abort(404);

        return redirect()->route($this->routeOf($group))->with('restored', $group);
    }

    public function reset(string $group, ChangeSettingVersionRequest $request, SettingsStore $settings, StaffDirectory $staff, KnowledgeValues $values): RedirectResponse
    {
        abort_if(SettingsRegistry::group($group) === null, 404);

        if (in_array($group, KnowledgeValues::GROUP_KEYS, true)) {
            return $this->guarded($group, SettingsRegistry::group($group)->default(), SettingsStore::DEFAULT, 'reset', $settings, $staff, $request, $values);
        }

        $settings->reset($group, (string) $staff->current($request));

        return redirect()->route($this->routeOf($group))->with('reset', $group);
    }

    /**
     * Restore or reset a value group of the knowledge base: the same protection as saving (nothing named by a document
     * is removed, customer groups are not removed or released this way).
     */
    private function guarded(string $group, mixed $value, string $kind, string $flash, SettingsStore $settings, StaffDirectory $staff, Request $request, KnowledgeValues $values): RedirectResponse
    {
        $definition = SettingsRegistry::group($group);
        [$value] = $definition->normalizeInput($value);
        $errors = [...$definition->errors($value), ...(KnowledgeValueRules::crossErrors([$group => $value])[$group] ?? [])];

        if ($errors !== []) {
            return redirect()->route($this->routeOf($group))->withErrors(["settings.{$group}" => implode(' ', $errors)]);
        }

        $settings->save($group, $value, (string) $staff->current($request), $kind);
        $values->apply();

        return redirect()->route($this->routeOf($group))->with($flash, $group);
    }

    private function routeOf(string $group): string
    {
        return SettingsRegistry::pages()[SettingsRegistry::group($group)?->page ?? 'mail']['route'];
    }
}
