<?php

namespace App\Crm\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use App\Crm\Actions\Companies\AttachContactsToCompany;
use App\Crm\Actions\Companies\UpsertCompany;
use App\Crm\Http\Requests\Companies\AttachCompanyContactsRequest;
use App\Crm\Http\Requests\Companies\StoreCompanyRequest;
use App\Crm\Http\Requests\Companies\UpdateCompanyRequest;
use App\Crm\Models\Company;
use App\Crm\Models\Contact;
use App\Crm\Models\SavedFilter;
use App\Crm\Models\Tag;
use App\Crm\Services\Companies\CompanyQuery;
use App\Crm\Support\CrmExportSchema;

class CompaniesController extends Controller
{
    public function __construct(private readonly CompanyQuery $companies) {}

    public function index(Request $request): View
    {
        Gate::authorize('crm.companies.view');

        return view('crm::admin.companies.index', [
            'companies' => $this->companies->paginate($request),
            'filters' => $this->companies->filters($request),
            'owners' => User::query()->orderBy('name')->limit(250)->get(['id', 'name']),
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name', 'color']),
            'savedFilters' => SavedFilter::query()->forModule('companies')->visibleTo($request->user())->orderBy('name')->get(),
            'sectors' => Company::query()
                ->whereNotNull('sector')
                ->distinct()
                ->orderBy('sector')
                ->pluck('sector', 'sector')
                ->all(),
            'exportColumns' => CrmExportSchema::columns('companies'),
            'exportFormats' => CrmExportSchema::formats('companies'),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('crm.companies.create');

        return view('crm::admin.companies.form', $this->formData(new Company));
    }

    public function store(StoreCompanyRequest $request, UpsertCompany $upsert): RedirectResponse
    {
        $company = $upsert->handle(new Company, $request->payload(), $request->user());

        return redirect()
            ->route('crm.companies.show', $company)
            ->with('crm_status', trans('crm::messages.companies.created'));
    }

    public function show(Company $company): View
    {
        Gate::authorize('view', $company);

        $company->load([
            'owner',
            'tags',
            'contacts.owner',
            'deals.stage',
            'quotes',
            'tasks.assignee',
            'activities.user',
        ]);

        return view('crm::admin.companies.show', [
            'company' => $company,
            'availableContacts' => Contact::query()
                ->whereNull('company_id')
                ->orderBy('full_name')
                ->limit(250)
                ->get(['id', 'full_name', 'email']),
            'openDeals' => $company->deals->where('status', 'open')->sortByDesc('value'),
            'openDealsValue' => $company->deals->where('status', 'open')->sum('value'),
            'openTasks' => $company->tasks->whereNull('completed_at')->sortBy('due_at'),
            'timeline' => $company->activities->sortByDesc('occurred_at'),
        ]);
    }

    public function edit(Company $company): View
    {
        Gate::authorize('update', $company);

        return view('crm::admin.companies.form', $this->formData($company));
    }

    public function update(UpdateCompanyRequest $request, Company $company, UpsertCompany $upsert): RedirectResponse
    {
        $upsert->handle($company, $request->payload(), $request->user());

        return redirect()
            ->route('crm.companies.show', $company)
            ->with('crm_status', trans('crm::messages.companies.updated'));
    }

    public function destroy(Company $company): RedirectResponse
    {
        Gate::authorize('delete', $company);

        if ($company->contacts()->exists() || $company->deals()->exists() || $company->quotes()->exists()) {
            return back()->withErrors([
                'company' => trans('crm::messages.companies.has_related_records'),
            ]);
        }

        $company->delete();

        return redirect()
            ->route('crm.companies.index')
            ->with('crm_status', trans('crm::messages.companies.deleted'));
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        Gate::authorize('crm.companies.delete');

        $validated = $request->validate([
            'record_ids' => ['required', 'array', 'min:1', 'max:500'],
            'record_ids.*' => ['integer', 'exists:companies,id'],
        ]);

        $deleted = 0;
        $blocked = 0;

        Company::query()
            ->whereKey($validated['record_ids'])
            ->chunkById(200, function (\Illuminate\Support\Collection $companies) use (&$deleted, &$blocked): void {
                $companies->each(function (Company $company) use (&$deleted, &$blocked): void {
                    Gate::authorize('delete', $company);

                    if ($company->contacts()->exists() || $company->deals()->exists() || $company->quotes()->exists()) {
                        $blocked++;

                        return;
                    }

                    $company->delete();
                    $deleted++;
                });
            });

        $message = $deleted > 0
            ? trans_choice('crm::messages.companies.bulk_deleted', $deleted, ['count' => $deleted])
            : trans('crm::messages.companies.none_deleted');

        if ($blocked > 0) {
            $message .= ' '.trans_choice('crm::messages.companies.bulk_skipped_related', $blocked, ['count' => $blocked]);
        }

        return back()->with('crm_status', $message);
    }

    public function attachContacts(
        AttachCompanyContactsRequest $request,
        Company $company,
        AttachContactsToCompany $attachContacts
    ): RedirectResponse {
        $attachContacts->handle($company, $request->validated('contact_ids'), $request->user());

        return redirect()
            ->route('crm.companies.show', $company)
            ->with('crm_status', trans('crm::messages.companies.contacts_attached'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Company $company): array
    {
        return [
            'company' => $company,
            'owners' => User::query()->orderBy('name')->limit(250)->get(['id', 'name']),
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name', 'color']),
            'selectedTags' => $company->exists ? $company->tags()->pluck('tags.id')->all() : [],
            'sectors' => [
                'Technology' => 'Technology',
                'Retail' => 'Retail',
                'Manufacturing' => 'Manufacturing',
                'Consulting' => 'Consulting',
                'Finance' => 'Finance',
                'Healthcare' => 'Healthcare',
                'Education' => 'Education',
            ],
        ];
    }
}
