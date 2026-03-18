<?php

namespace AgenticMorf\FluxUIOtel;

use Illuminate\Support\ServiceProvider;

class FluxUIOtelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/fluxui-otel.php', 'fluxui-otel');
    }

    public function boot(): void
    {
        if ($this->app->bound('fluxui-otel.booted')) {
            return;
        }

        $this->app->instance('fluxui-otel.booted', true);

        $paths = [__DIR__.'/../resources/views'];
        $published = $this->app->resourcePath('views/vendor/fluxui-otel');

        if (is_dir($published)) {
            array_unshift($paths, $published);
        }

        $this->loadViewsFrom($paths, 'fluxui-otel');

        if (! $this->app->routesAreCached()) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/fluxui-otel.php' => config_path('fluxui-otel.php'),
            ], 'fluxui-otel-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/fluxui-otel'),
            ], 'fluxui-otel-views');

            $this->publishes([
                __DIR__.'/../resources/js/otel-browser.js' => resource_path('js/otel-browser.js'),
            ], 'fluxui-otel-js');
        }
    }
}
