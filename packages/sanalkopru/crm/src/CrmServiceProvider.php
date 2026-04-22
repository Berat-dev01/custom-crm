<?php

namespace Sanalkopru\Crm;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Sanalkopru\Crm\Services\Ai\AiDriverManager;
use Sanalkopru\Crm\Services\Configuration\FeatureManager;
use Sanalkopru\Crm\Services\Configuration\MoneySettings;
use Sanalkopru\Crm\Services\Configuration\UiSettings;

class CrmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/crm.php', 'crm');

        $this->app->singleton(AiDriverManager::class);
        $this->app->singleton(FeatureManager::class);
        $this->app->singleton(MoneySettings::class);
        $this->app->singleton(UiSettings::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'crm');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadWebRoutes();
        $this->loadApiRoutes();

        if ($this->app->runningInConsole()) {
            $this->registerPublishables();
        }
    }

    private function loadWebRoutes(): void
    {
        Route::group([], __DIR__.'/routes/web.php');
    }

    private function loadApiRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/routes/api.php');
    }

    private function registerPublishables(): void
    {
        $this->publishes([
            __DIR__.'/../config/crm.php' => config_path('crm.php'),
        ], 'crm-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/crm'),
        ], 'crm-views');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'crm-migrations');

        $this->publishes([
            __DIR__.'/../resources/js' => resource_path('js/vendor/crm'),
            __DIR__.'/../resources/css' => resource_path('css/vendor/crm'),
        ], 'crm-assets');
    }
}
