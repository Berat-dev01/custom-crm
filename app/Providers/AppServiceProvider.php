<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Surface N+1 queries during development and tests; production
        // keeps lazy loading permissive to avoid hard failures.
        Model::preventLazyLoading(! $this->app->isProduction());

        // Baseline password policy for every password rule in the app.
        Password::defaults(function () {
            $min = (int) config('crm.security.password_min_length', 10);

            return Password::min($min)->letters()->numbers();
        });
    }
}
