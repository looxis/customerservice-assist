<?php

namespace App\Providers;

use App\Knowledge\KnowledgeLibrary;
use App\Knowledge\KnowledgeMarkdown;
use App\Knowledge\KnowledgeSelector;
use App\Knowledge\KnowledgeSuggester;
use App\Staff\StaffDirectory;
use App\Zammad\MessageBody;
use App\Zammad\ZammadClient;
use Illuminate\Contracts\Foundation\Application;
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
        $this->app->bind(ZammadClient::class, fn (Application $app): ZammadClient => new ZammadClient(config('services.zammad'), $app->make(MessageBody::class)));
        $this->app->bind(StaffDirectory::class, fn (): StaffDirectory => new StaffDirectory(config('staff')));
        $this->app->bind(KnowledgeSuggester::class, fn (Application $app): KnowledgeSuggester => new KnowledgeSuggester($app->make(KnowledgeLibrary::class), $app->make(KnowledgeSelector::class), config('knowledge')));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['components.layouts.app', 'tickets.analyze', 'tickets.show'], function (\Illuminate\View\View $view): void {
            $staff = app(StaffDirectory::class);

            $view->with([
                'staffNames' => $staff->names(),
                'currentStaff' => $staff->current(request()),
            ]);
        });
    }
}
