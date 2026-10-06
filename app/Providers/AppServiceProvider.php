<?php

namespace App\Providers;

use App\Eocs\EocsClient;
use App\Http\LocalRedirect;
use App\Knowledge\KnowledgeLibrary;
use App\Knowledge\KnowledgeMarkdown;
use App\Knowledge\KnowledgeSelector;
use App\Knowledge\KnowledgeSuggester;
use App\Staff\StaffDirectory;
use App\Staff\TestMode;
use App\Zammad\MessageBody;
use App\Zammad\ZammadClient;
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
        $this->app->bind(MessageBody::class, fn (): MessageBody => new MessageBody(
            config('services.zammad.signatures', []),
            config('services.zammad.allowed_link_hosts', []),
            config('services.zammad.clickable_link_hosts', []),
            config('services.zammad.footers', []),
        ));
        $this->app->bind(EocsClient::class, fn (): EocsClient => new EocsClient(config('services.eocs'), array_change_key_case(config('knowledge.order_channels', []), CASE_LOWER)));
        $this->app->bind(ZammadClient::class, fn (Application $app): ZammadClient => new ZammadClient(config('services.zammad'), $app->make(MessageBody::class)));
        $this->app->bind(StaffDirectory::class, fn (): StaffDirectory => new StaffDirectory(config('staff')));
        $this->app->bind(KnowledgeSuggester::class, fn (Application $app): KnowledgeSuggester => new KnowledgeSuggester($app->make(KnowledgeLibrary::class), $app->make(KnowledgeSelector::class), config('knowledge')));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('language-model', fn (Request $request): Limit => Limit::perMinute((int) config('analysis.calls_per_minute'))
            ->by((app(StaffDirectory::class)->current($request) ?? '').'|'.$request->ip())
            ->response(fn (Request $request): RedirectResponse => LocalRedirect::back($request, route('tickets.analyze'))
                ->withInput()
                ->with($request->routeIs('tickets.summary.*') ? 'summary_error' : 'analysis_error', 'Zu viele KI-Aufrufe in kurzer Zeit. Bitte eine Minute warten und dann erneut versuchen.')));

        View::composer(['components.layouts.app', 'tickets.analyze', 'tickets.show'], function (\Illuminate\View\View $view): void {
            $staff = app(StaffDirectory::class);
            $testMode = app(TestMode::class);

            $view->with([
                'staffNames' => $staff->names(),
                'currentStaff' => $staff->current(request()),
                'testModeAvailable' => $testMode->isAvailable(request()),
                'testModeActive' => $testMode->isActive(request()),
            ]);
        });
    }
}
