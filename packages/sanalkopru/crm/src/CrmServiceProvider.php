<?php

namespace Sanalkopru\Crm;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Sanalkopru\Crm\Console\SeedCrmDemoCommand;
use Sanalkopru\Crm\Console\SeedCrmPerformanceCommand;
use Sanalkopru\Crm\Console\SendTaskRemindersCommand;
use Sanalkopru\Crm\Contracts\AiProviderContract;
use Sanalkopru\Crm\Events\ContactCreated;
use Sanalkopru\Crm\Events\DealMoved;
use Sanalkopru\Crm\Events\QuoteSent;
use Sanalkopru\Crm\Events\TaskCompleted;
use Sanalkopru\Crm\Http\Middleware\AuthenticateCrmApi;
use Sanalkopru\Crm\Http\Middleware\EnsureCrmAccess;
use Sanalkopru\Crm\Listeners\LogContactCreatedActivity;
use Sanalkopru\Crm\Listeners\LogDealMovedActivity;
use Sanalkopru\Crm\Listeners\LogQuoteSentActivity;
use Sanalkopru\Crm\Listeners\LogTaskCompletedActivity;
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
use Sanalkopru\Crm\Services\Audit\CrmAuditLogger;
use Sanalkopru\Crm\Services\Authorization\CrmAuthorization;
use Sanalkopru\Crm\Services\Authorization\PermissionCatalog;
use Sanalkopru\Crm\Services\Configuration\FeatureManager;
use Sanalkopru\Crm\Services\Configuration\MoneySettings;
use Sanalkopru\Crm\Services\Configuration\UiSettings;
use Sanalkopru\Crm\Services\Navigation\CrmNavigation;
use Sanalkopru\Crm\Services\Settings\CrmSettingsManager;
use Sanalkopru\Crm\Support\CrmFormatter;

class CrmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/crm.php', 'crm');

        $this->app->singleton(AiDriverManager::class);
        $this->app->bind(AiProviderContract::class, fn ($app) => $app->make(AiDriverManager::class)->provider());
        $this->app->singleton(CrmAuthorization::class);
        $this->app->singleton(CrmAuditLogger::class);
        $this->app->singleton(PermissionCatalog::class);
        $this->app->singleton(FeatureManager::class);
        $this->app->singleton(MoneySettings::class);
        $this->app->singleton(CrmNavigation::class);
        $this->app->singleton(UiSettings::class);
        $this->app->singleton(CrmFormatter::class);
        $this->app->singleton(CrmSettingsManager::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'crm');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->registerAuthorization();
        $this->registerEvents();
        $this->registerRateLimiters();
        $this->loadWebRoutes();
        $this->loadApiRoutes();

        if ($this->app->runningInConsole()) {
            $this->registerPublishables();
            $this->commands([
                SeedCrmDemoCommand::class,
                SeedCrmPerformanceCommand::class,
                SendTaskRemindersCommand::class,
            ]);
        }

        $this->scheduleCommands();
    }

    private function registerAuthorization(): void
    {
        $this->app['router']->aliasMiddleware('crm.access', EnsureCrmAccess::class);
        $this->app['router']->aliasMiddleware('crm.api.auth', AuthenticateCrmApi::class);

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

        Blade::if('feature', fn (string $feature): bool => (bool) data_get(config('features', []), $feature, false));

        View::composer('crm::*', function ($view): void {
            $view->with('crmNavigation', $this->app->make(CrmNavigation::class)->items(request()));
            $view->with('crmFormat', $this->app->make(CrmFormatter::class));
        });
    }

    private function registerEvents(): void
    {
        Event::listen(ContactCreated::class, LogContactCreatedActivity::class);
        Event::listen(DealMoved::class, LogDealMovedActivity::class);
        Event::listen(QuoteSent::class, LogQuoteSentActivity::class);
        Event::listen(TaskCompleted::class, LogTaskCompletedActivity::class);
    }

    private function registerRateLimiters(): void
    {
        RateLimiter::for('crm-api', function (Request $request): Limit {
            $limit = (int) config('crm.api.rate_limit_per_minute', 120);
            $key = $request->user()?->getAuthIdentifier() ?: $request->ip();

            return Limit::perMinute($limit)->by('crm-api:'.$key);
        });

        RateLimiter::for('crm-ai', function (Request $request): Limit {
            $limit = (int) config('crm.ai.rate_limit_per_minute', 30);
            $key = $request->user()?->getAuthIdentifier() ?: $request->ip();

            return Limit::perMinute($limit)->by('crm-ai:'.$key);
        });
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
            __DIR__.'/../resources/js' => public_path('vendor/crm/js'),
            __DIR__.'/../resources/css' => public_path('vendor/crm/css'),
        ], 'crm-assets');
    }

    private function scheduleCommands(): void
    {
        $this->app->booted(function (): void {
            $this->app->make(Schedule::class)
                ->command('crm:tasks:send-reminders')
                ->everyFiveMinutes()
                ->withoutOverlapping();
        });
    }
}
