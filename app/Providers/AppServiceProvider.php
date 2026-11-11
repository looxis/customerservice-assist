<?php

namespace App\Providers;

use App\Analysis\AppStats;
use App\Analysis\KnowledgeGapLog;
use App\Dhl\DhlClient;
use App\Eocs\EocsClient;
use App\Http\LocalRedirect;
use App\Knowledge\KnowledgeLibrary;
use App\Knowledge\KnowledgeMarkdown;
use App\Knowledge\KnowledgeSelector;
use App\Knowledge\KnowledgeSuggester;
use App\ProductForge\ProductForgeClient;
use App\Settings\KnowledgeValues;
use App\Settings\MailRules;
use App\Settings\SettingsStore;
use App\Staff\AdvancedMode;
use App\Staff\IntroMode;
use App\Staff\StaffDirectory;
use App\Staff\TestMode;
use App\Todo\TodoBoard;
use App\Tracking\DhlTrackingClient;
use App\Tracking\UpsTrackingClient;
use App\Zammad\MessageBody;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadReplySender;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(KnowledgeLibrary::class, fn (): KnowledgeLibrary => new KnowledgeLibrary(config('knowledge')));
        $this->app->bind(KnowledgeMarkdown::class, fn (): KnowledgeMarkdown => new KnowledgeMarkdown(config('knowledge')));
        $this->app->bind(KnowledgeSelector::class, fn (Application $app): KnowledgeSelector => new KnowledgeSelector($app->make(KnowledgeLibrary::class), config('knowledge')));
        $this->app->scoped(SettingsStore::class);
        $this->app->singleton(KnowledgeValues::class);
        $this->app->bind(MessageBody::class, fn (Application $app): MessageBody => $app->make(MailRules::class)->messageBody());
        $this->app->bind(EocsClient::class, fn (): EocsClient => new EocsClient(config('services.eocs'), array_change_key_case(config('knowledge.order_channels', []), CASE_LOWER)));
        $this->app->bind(ProductForgeClient::class, fn (): ProductForgeClient => new ProductForgeClient(config('services.productforge')));
        $this->app->bind(ZammadClient::class, fn (Application $app): ZammadClient => new ZammadClient(config('services.zammad'), $app->make(MessageBody::class)));
        $this->app->bind(DhlClient::class, fn (): DhlClient => new DhlClient(config('services.dhl')));
        $this->app->bind(DhlTrackingClient::class, fn (): DhlTrackingClient => new DhlTrackingClient(config('services.dhl_tracking')));
        $this->app->bind(UpsTrackingClient::class, fn (): UpsTrackingClient => new UpsTrackingClient(config('services.ups')));
        $this->app->bind(ZammadReplySender::class, fn (): ZammadReplySender => new ZammadReplySender(config('services.zammad')));
        $this->app->bind(StaffDirectory::class, fn (): StaffDirectory => new StaffDirectory(config('staff')));
        $this->app->bind(KnowledgeSuggester::class, fn (Application $app): KnowledgeSuggester => new KnowledgeSuggester($app->make(KnowledgeLibrary::class), $app->make(KnowledgeSelector::class), config('knowledge')));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Saved values of the settings page (PROJ-50) are laid over the configuration of the knowledge base.
        $this->app->booted(fn () => $this->app->make(KnowledgeValues::class)->apply());

        // PROJ-78: the background runs of the ticket tagging pace their calls to the language model and to Zammad.
        RateLimiter::for('labeling-model', fn (): Limit => Limit::perMinute((int) config('labeling.calls_per_minute')));
        RateLimiter::for('labeling-zammad', fn (): Limit => Limit::perMinute((int) config('labeling.zammad_per_minute')));

        RateLimiter::for('language-model', fn (Request $request): Limit => Limit::perMinute((int) config('analysis.calls_per_minute'))
            ->by((app(StaffDirectory::class)->current($request) ?? '').'|'.$request->ip())
            ->response(fn (Request $request): RedirectResponse => LocalRedirect::back($request, route('tickets.analyze'))
                ->withInput()
                ->with(match (true) {
                    $request->routeIs('tickets.summary.*') => 'summary_error',
                    $request->routeIs('tickets.translation.*') => 'translation_error',
                    default => 'analysis_error',
                }, 'Zu viele KI-Aufrufe in kurzer Zeit. Bitte eine Minute warten und dann erneut versuchen.')));

        View::composer(['components.layouts.app', 'tickets.analyze', 'tickets.show', 'questions.create', 'questions.show', 'todos.index'], function (\Illuminate\View\View $view): void {
            $staff = app(StaffDirectory::class);
            $testMode = app(TestMode::class);

            $view->with([
                'staffNames' => $staff->names(),
                'currentStaff' => $staff->current(request()),
                'advancedModeActive' => app(AdvancedMode::class)->isActive(request()),
                'introModeActive' => app(IntroMode::class)->isActive(request()),
                'testModeAvailable' => $testMode->isAvailable(request()),
                'testModeActive' => $testMode->isActive(request()),
                'canCloseTickets' => $staff->zammadToken($staff->current(request())) !== null,
                'canAssignTickets' => $staff->zammadToken($staff->current(request())) !== null,
                'assignees' => $staff->assignableNames(),
                'currentStaffName' => $staff->current(request()),
                'ticketOverviewAvailable' => $testMode->isAvailable(request()),
                'stats' => app(AppStats::class)->all(),
                'openGapCount' => app(KnowledgeGapLog::class)->openCount(),
                'todoCounts' => app(TodoBoard::class)->counts($staff->current(request())),
            ]);
        });
    }
}
