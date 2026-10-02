<?php

namespace App\Providers;

use App\Knowledge\KnowledgeLibrary;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(KnowledgeLibrary::class, fn (): KnowledgeLibrary => new KnowledgeLibrary(config('knowledge')));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
