<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Crm\Services\Security\TwoFactorService;

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

        $user = Auth::guard('admin')->user();

        if ($user instanceof User && $user->hasTwoFactorEnabled()) {
            // Credentials are valid but the session must not exist until
            // the TOTP challenge passes.
            Auth::guard('admin')->logout();

            $request->session()->put('crm_2fa_challenge', [
                'user_id' => $user->getKey(),
                'remember' => $request->boolean('remember'),
            ]);

            return redirect()->route('admin.login.2fa');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('crm.dashboard'));
    }

    public function showTwoFactorChallenge(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('crm_2fa_challenge')) {
            return redirect()->route('admin.login');
        }

        return view('crm::auth.two-factor');
    }

    public function verifyTwoFactor(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $challenge = $request->session()->get('crm_2fa_challenge');

        if (! $challenge) {
            return redirect()->route('admin.login');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ]);

        $user = User::query()->find($challenge['user_id']);

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            $request->session()->forget('crm_2fa_challenge');

            return redirect()->route('admin.login');
        }

        $code = $validated['code'];
        $valid = $twoFactor->verify($user->two_factor_secret, $code)
            || $twoFactor->useRecoveryCode($user, $code);

        if (! $valid) {
            return back()->withErrors(['code' => trans('crm::messages.security.invalid_code')]);
        }

        $request->session()->forget('crm_2fa_challenge');
        Auth::guard('admin')->login($user, (bool) ($challenge['remember'] ?? false));
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
