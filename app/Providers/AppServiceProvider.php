<?php

namespace App\Providers;

use App\Knowledge\KnowledgeLibrary;
use App\Knowledge\KnowledgeMarkdown;
use App\Knowledge\KnowledgeSelector;
use App\Knowledge\KnowledgeSuggester;
use Illuminate\Contracts\Foundation\Application;
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
        $this->app->bind(KnowledgeSuggester::class, fn (Application $app): KnowledgeSuggester => new KnowledgeSuggester($app->make(KnowledgeLibrary::class), $app->make(KnowledgeSelector::class), config('knowledge')));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
