<?php

namespace App\Crm\Http\Controllers\Admin;

use App\Crm\Http\Requests\Settings\UpdateCrmSettingsRequest;
use App\Crm\Services\Settings\CrmSettingsManager;
use App\Crm\Support\Ai\AiDriver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class SettingsController extends Controller
{
    public function index(CrmSettingsManager $settings): View
    {
        Gate::authorize('crm.settings.manage');

        return view('crm::admin.settings.index', [
            'settings' => $settings->all(),
            'logoUrl' => $settings->logoUrl(),
            'currencies' => array_combine(
                config('crm.money.supported_currencies', ['TRY', 'USD', 'EUR']),
                config('crm.money.supported_currencies', ['TRY', 'USD', 'EUR'])
            ),
            'aiDrivers' => collect(AiDriver::values())
                ->mapWithKeys(fn (string $driver): array => [$driver => ucfirst($driver)])
                ->all(),
        ]);
    }

    public function update(UpdateCrmSettingsRequest $request, CrmSettingsManager $settings): RedirectResponse
    {
        $settings->update($request->validated(), $request->file('company_logo'), $request->user());

        return redirect()
            ->route('crm.settings.index')
            ->with('crm_status', trans('crm::messages.settings.updated'));
    }
}
