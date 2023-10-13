<?php

namespace LaravelLiberu\DataExport;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use LaravelLiberu\DataExport\Commands\Purge;
use LaravelLiberu\DataExport\Models\Export;
use LaravelLiberu\IO\Observers\IOObserver;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->load()
            ->publish()
            ->command()
            ->observe();
    }

    private function load()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        $this->mergeConfigFrom(__DIR__.'/../config/exports.php', 'enso.exports');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-enso/data-export');

        return $this;
    }

    private function publish()
    {
        $this->publishes([
            __DIR__.'/../config' => config_path('enso'),
        ], ['data-export-config', 'enso-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/laravel-enso/data-export'),
        ], ['data-export-mail', 'enso-mail']);

        return $this;
    }

    private function command(): self
    {
        $this->commands(Purge::class);

        $this->app->booted(fn () => $this->app->make(Schedule::class)
            ->command('enso:data-export:purge')->daily());

        return $this;
    }

    private function observe(): void
    {
        Export::observe(IOObserver::class);
    }
}
