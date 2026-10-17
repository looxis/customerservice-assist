<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangeSettingVersionRequest;
use App\Http\Requests\MailSettingsRequest;
use App\Http\Requests\PromptSettingsRequest;
use App\Http\Requests\TryMailSettingsRequest;
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

    public function restore(string $group, int $version, ChangeSettingVersionRequest $request, SettingsStore $settings, StaffDirectory $staff): RedirectResponse
    {
        abort_if(SettingsRegistry::group($group) === null, 404);

        $settings->restore($group, $version, (string) $staff->current($request)) || abort(404);

        return redirect()->route($this->routeOf($group))->with('restored', $group);
    }

    public function reset(string $group, ChangeSettingVersionRequest $request, SettingsStore $settings, StaffDirectory $staff): RedirectResponse
    {
        abort_if(SettingsRegistry::group($group) === null, 404);

        $settings->reset($group, (string) $staff->current($request));

        return redirect()->route($this->routeOf($group))->with('reset', $group);
    }

    private function routeOf(string $group): string
    {
        return SettingsRegistry::pages()[SettingsRegistry::group($group)?->page ?? 'mail']['route'];
    }
}
