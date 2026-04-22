<?php

namespace Sanalkopru\Crm;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Sanalkopru\Crm\Models\Activity;
use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Models\Contact;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\Quote;
use Sanalkopru\Crm\Models\Tag;
use Sanalkopru\Crm\Models\Task;
use Sanalkopru\Crm\Policies\ActivityPolicy;
use Sanalkopru\Crm\Policies\CompanyPolicy;
use Sanalkopru\Crm\Policies\ContactPolicy;
use Sanalkopru\Crm\Policies\DealPolicy;
use Sanalkopru\Crm\Policies\QuotePolicy;
use Sanalkopru\Crm\Policies\TagPolicy;
use Sanalkopru\Crm\Policies\TaskPolicy;
use Sanalkopru\Crm\Services\Ai\AiDriverManager;
use Sanalkopru\Crm\Services\Authorization\CrmAuthorization;
use Sanalkopru\Crm\Services\Authorization\PermissionCatalog;
use Sanalkopru\Crm\Services\Configuration\FeatureManager;
use Sanalkopru\Crm\Services\Configuration\MoneySettings;
use Sanalkopru\Crm\Services\Configuration\UiSettings;

class CrmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/crm.php', 'crm');

        $this->app->singleton(AiDriverManager::class);
        $this->app->singleton(CrmAuthorization::class);
        $this->app->singleton(PermissionCatalog::class);
        $this->app->singleton(FeatureManager::class);
        $this->app->singleton(MoneySettings::class);
        $this->app->singleton(UiSettings::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'crm');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->registerAuthorization();
        $this->loadWebRoutes();
        $this->loadApiRoutes();

        if ($this->app->runningInConsole()) {
            $this->registerPublishables();
        }
    }

    private function registerAuthorization(): void
    {
        Gate::policy(Activity::class, ActivityPolicy::class);
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Contact::class, ContactPolicy::class);
        Gate::policy(Deal::class, DealPolicy::class);
        Gate::policy(Quote::class, QuotePolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);

        foreach ($this->app->make(PermissionCatalog::class)->permissions() as $permission) {
            Gate::define(
                $permission,
                fn (?Authenticatable $user = null): bool => $this->app->make(CrmAuthorization::class)->can($user, $permission)
            );
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
