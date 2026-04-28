<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = array_keys(config('localization.supported_locales', []));
        $defaultLocale = (string) config('localization.default_locale', config('app.locale', 'en'));
        $fallbackLocale = (string) config('localization.fallback_locale', config('app.fallback_locale', 'en'));
        $sessionKey = (string) config('localization.session_key', 'locale');

        if ($supportedLocales === []) {
            $supportedLocales = [$defaultLocale];
        }

        if (! in_array($defaultLocale, $supportedLocales, true)) {
            $defaultLocale = $supportedLocales[0];
        }

        if (! in_array($fallbackLocale, $supportedLocales, true)) {
            $fallbackLocale = $defaultLocale;
        }

        $locale = $request->session()->get($sessionKey, $defaultLocale);

        if (! is_string($locale) || ! in_array($locale, $supportedLocales, true)) {
            $locale = $defaultLocale;
        }

        app()->setLocale($locale);
        app()->setFallbackLocale($fallbackLocale);

        return $next($request);
    }
}
