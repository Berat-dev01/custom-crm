<?php

namespace App\Providers;

use App\AdminPanel\AdminPanel;
use App\Http\Middleware\AdminAuthenticate;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class AdminPanelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('admin-panel', fn () => new AdminPanel);
    }

    public function boot(Router $router): void
    {
        $this->loadJsonTranslationsFrom(lang_path('vendor/admin-panel'));
        $this->loadTranslationsFrom(lang_path('vendor/admin-panel'), 'admin-panel');
        $this->loadViewsFrom(resource_path('views/vendor/admin-panel'), 'admin-panel');

        Paginator::defaultView('admin-panel::pagination.links');
        Paginator::defaultSimpleView('admin-panel::pagination.simple-default');

        if (method_exists($this, 'loadAnonymousComponentsFrom')) {
            $this->loadAnonymousComponentsFrom(resource_path('views/vendor/admin-panel/components'), 'admin-panel');
        }

        $router->aliasMiddleware('admin.auth', AdminAuthenticate::class);
    }
}
