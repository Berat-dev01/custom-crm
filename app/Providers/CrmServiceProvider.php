<?php

namespace App\Providers;

use App\Crm\Console\SeedCrmDemoCommand;
use App\Crm\Console\SeedCrmPerformanceCommand;
use App\Crm\Console\SendTaskRemindersCommand;
use App\Crm\Console\SendWeeklyDigestCommand;
use App\Crm\Contracts\AiProviderContract;
use App\Crm\Events\ContactCreated;
use App\Crm\Events\DealMoved;
use App\Crm\Events\QuoteSent;
use App\Crm\Events\TaskCompleted;
use App\Crm\Http\Middleware\AuthenticateCrmApi;
use App\Crm\Http\Middleware\EnsureCrmAccess;
use App\Crm\Listeners\LogContactCreatedActivity;
use App\Crm\Listeners\LogDealMovedActivity;
use App\Crm\Listeners\LogQuoteSentActivity;
use App\Crm\Listeners\LogTaskCompletedActivity;
use App\Crm\Models\Activity;
use App\Crm\Models\Company;
use App\Crm\Models\Contact;
use App\Crm\Models\CrmExport;
use App\Crm\Models\Deal;
use App\Crm\Models\Quote;
use App\Crm\Models\Tag;
use App\Crm\Models\Task;
use App\Crm\Policies\ActivityPolicy;
use App\Crm\Policies\CompanyPolicy;
use App\Crm\Policies\ContactPolicy;
use App\Crm\Policies\DealPolicy;
use App\Crm\Policies\QuotePolicy;
use App\Crm\Policies\TagPolicy;
use App\Crm\Policies\TaskPolicy;
use App\Crm\Services\Ai\AiDriverManager;
use App\Crm\Services\Audit\CrmAuditLogger;
use App\Crm\Services\Authorization\CrmAuthorization;
use App\Crm\Services\Authorization\PermissionCatalog;
use App\Crm\Services\Configuration\FeatureManager;
use App\Crm\Services\Configuration\MoneySettings;
use App\Crm\Services\Configuration\UiSettings;
use App\Crm\Services\Navigation\CrmNavigation;
use App\Crm\Services\Settings\CrmSettingsManager;
use App\Crm\Support\CrmFormatter;
use App\Crm\Support\CrmLabelCatalog;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class CrmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
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
        $this->app->singleton(CrmLabelCatalog::class);
        $this->app->singleton(CrmSettingsManager::class);
    }

    public function boot(): void
    {
        Relation::morphMap([
            'contact' => Contact::class,
            'company' => Company::class,
            'deal' => Deal::class,
            'quote' => Quote::class,
            'task' => Task::class,
            'activity' => Activity::class,
            'tag' => Tag::class,
            'crm_export' => CrmExport::class,
        ]);

        $this->loadTranslationsFrom(lang_path('crm'), 'crm');
        $this->loadViewsFrom(resource_path('views/crm'), 'crm');

        $this->registerAuthorization();
        $this->registerEvents();
        $this->registerRateLimiters();
        $this->loadWebRoutes();
        $this->loadApiRoutes();

        if ($this->app->runningInConsole()) {
            $this->commands([
                SeedCrmDemoCommand::class,
                SeedCrmPerformanceCommand::class,
                SendTaskRemindersCommand::class,
                SendWeeklyDigestCommand::class,
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
            $navigation = $this->app->make(CrmNavigation::class);

            $view->with('crmNavigation', $navigation->items(request()));
            $view->with('crmNavigationGroups', $navigation->groups(request()));
            $view->with('crmFormat', $this->app->make(CrmFormatter::class));
        });

        View::composer('admin-panel::layouts.app', function ($view): void {
            $navigation = $this->app->make(CrmNavigation::class);

            $commandItems = collect($navigation->items(request()))
                ->filter(fn ($item) => isset($item['permission']) ? Gate::allows($item['permission']) : true)
                ->map(fn ($item) => [
                    'label' => $item['label'],
                    'url' => route($item['route']),
                    'group' => 'CRM',
                ])
                ->values();

            $view->with('adminCommandItems', $commandItems);
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

        RateLimiter::for('crm-login', function (Request $request): Limit {
            $limit = (int) config('crm.security.login_attempts_per_minute', 5);
            $email = strtolower((string) $request->input('email'));

            return Limit::perMinute($limit)->by('crm-login:'.$email.'|'.$request->ip());
        });
    }

    private function loadWebRoutes(): void
    {
        Route::group([], base_path('routes/crm-web.php'));
    }

    private function loadApiRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/crm-api.php'));
    }

    private function scheduleCommands(): void
    {
        $this->app->booted(function (): void {
            $this->app->make(Schedule::class)
                ->command('crm:tasks:send-reminders')
                ->everyFiveMinutes()
                ->withoutOverlapping();

            $this->app->make(Schedule::class)
                ->command('crm:digest:send-weekly')
                ->weeklyOn(1, '08:00')
                ->withoutOverlapping();
        });
    }
}
