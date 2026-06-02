<?php

namespace App\Crm\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use App\Crm\Http\Requests\SavedFilters\StoreSavedFilterRequest;
use App\Crm\Models\SavedFilter;

class SavedFiltersController extends Controller
{
    public function store(StoreSavedFilterRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $filters = collect($payload['filters'] ?? [])
            ->filter(fn ($value): bool => $value !== null && $value !== '')
            ->all();

        SavedFilter::query()->create([
            'name' => $payload['name'],
            'module' => $payload['module'],
            'filters' => $filters,
            'visibility' => $payload['visibility'],
            'user_id' => $request->user()?->id,
        ]);

        return back()->with('crm_status', trans('crm::messages.saved_filters.created'));
    }

    public function apply(SavedFilter $savedFilter): RedirectResponse
    {
        $this->authorizeAccess($savedFilter);

        return redirect()->route($this->routeName($savedFilter->module), $savedFilter->filters ?? []);
    }

    public function destroy(SavedFilter $savedFilter): RedirectResponse
    {
        Gate::authorize("crm.{$savedFilter->module}.view");

        $userId = request()->user()?->id;
        $isOwner = $savedFilter->user_id === $userId;
        $canManage = Gate::allows('crm.settings.manage');

        if (! $isOwner && ! $canManage) {
            abort(403, trans('crm::messages.saved_filters.private_access_denied'));
        }

        $savedFilter->delete();

        return back()->with('crm_status', trans('crm::messages.saved_filters.deleted'));
    }

    private function authorizeAccess(SavedFilter $savedFilter): void
    {
        Gate::authorize("crm.{$savedFilter->module}.view");

        if ($savedFilter->visibility === 'private' && $savedFilter->user_id !== request()->user()?->id) {
            abort(403, trans('crm::messages.saved_filters.private_access_denied'));
        }
    }

    private function routeName(string $module): string
    {
        return match ($module) {
            'contacts' => 'crm.contacts.index',
            'companies' => 'crm.companies.index',
            'deals' => 'crm.deals.index',
            'tasks' => 'crm.tasks.index',
            'quotes' => 'crm.quotes.index',
            'activities' => 'crm.activities.index',
        };
    }
}
