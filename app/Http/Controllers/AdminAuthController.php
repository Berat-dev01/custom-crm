<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('admin-panel::auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => trans('auth.failed')])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('crm.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function redirectToCrm(): RedirectResponse
    {
        return redirect()->route('crm.dashboard');
    }

    public function updateLocale(Request $request): RedirectResponse
    {
        $supportedLocales = array_keys(config('localization.supported_locales', []));

        $locale = $request->validate([
            'locale' => ['required', 'string', Rule::in($supportedLocales)],
        ])['locale'];

        $request->session()->put(
            config('localization.session_key', 'locale'),
            $locale
        );

        return back();
    }
}
