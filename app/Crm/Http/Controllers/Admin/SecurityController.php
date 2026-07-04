<?php

namespace App\Crm\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use App\Crm\Services\Audit\CrmAuditLogger;
use App\Crm\Services\Security\TwoFactorService;

class SecurityController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactor,
        private readonly CrmAuditLogger $audit
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('crm.dashboard.view');

        $user = $request->user('admin');
        $pendingSecret = $request->session()->get('crm_2fa_pending_secret');

        return view('crm::admin.security.index', [
            'user' => $user,
            'pendingSecret' => $pendingSecret,
            'qrSvg' => $pendingSecret ? $this->twoFactor->qrSvg($user, $pendingSecret) : null,
            'recoveryCodes' => $request->session()->get('crm_2fa_recovery_codes'),
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        Gate::authorize('crm.dashboard.view');

        $request->session()->put('crm_2fa_pending_secret', $this->twoFactor->generateSecret());

        return redirect()->route('crm.security.index');
    }

    public function confirm(Request $request): RedirectResponse
    {
        Gate::authorize('crm.dashboard.view');

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10'],
        ]);

        $secret = $request->session()->get('crm_2fa_pending_secret');

        if (! $secret) {
            return redirect()->route('crm.security.index');
        }

        if (! $this->twoFactor->verify($secret, $validated['code'])) {
            return back()->withErrors(['code' => trans('crm::messages.security.invalid_code')]);
        }

        $user = $request->user('admin');
        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget('crm_2fa_pending_secret');
        $request->session()->flash('crm_2fa_recovery_codes', $recoveryCodes);

        $this->audit->record('crm.security.2fa_enabled', null, $user, [], []);

        return redirect()
            ->route('crm.security.index')
            ->with('crm_status', trans('crm::messages.security.enabled'));
    }

    public function regenerateCalendarToken(Request $request): RedirectResponse
    {
        Gate::authorize('crm.dashboard.view');

        $request->user('admin')->forceFill([
            'calendar_token' => \Illuminate\Support\Str::random(48),
        ])->save();

        return redirect()
            ->route('crm.security.index')
            ->with('crm_status', trans('crm::messages.security.calendar_token_regenerated'));
    }

    public function disable(Request $request): RedirectResponse
    {
        Gate::authorize('crm.dashboard.view');

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user('admin');

        if (! Hash::check($request->string('password'), $user->password)) {
            return back()->withErrors(['password' => trans('auth.password')]);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->audit->record('crm.security.2fa_disabled', null, $user, [], []);

        return redirect()
            ->route('crm.security.index')
            ->with('crm_status', trans('crm::messages.security.disabled'));
    }
}
