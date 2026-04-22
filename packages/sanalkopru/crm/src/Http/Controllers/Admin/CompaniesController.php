<?php

namespace Sanalkopru\Crm\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Sanalkopru\Crm\Actions\Companies\AttachContactsToCompany;
use Sanalkopru\Crm\Actions\Companies\UpsertCompany;
use Sanalkopru\Crm\Http\Requests\Companies\AttachCompanyContactsRequest;
use Sanalkopru\Crm\Http\Requests\Companies\StoreCompanyRequest;
use Sanalkopru\Crm\Http\Requests\Companies\UpdateCompanyRequest;
use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Models\Contact;
use Sanalkopru\Crm\Models\Tag;
use Sanalkopru\Crm\Services\Companies\CompanyQuery;

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
            'sectors' => Company::query()
                ->whereNotNull('sector')
                ->distinct()
                ->orderBy('sector')
                ->pluck('sector', 'sector')
                ->all(),
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
            ->with('crm_status', 'Company created.');
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
            ->with('crm_status', 'Company updated.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        Gate::authorize('delete', $company);

        if ($company->contacts()->exists() || $company->deals()->exists() || $company->quotes()->exists()) {
            return back()->withErrors([
                'company' => 'Company has related contacts, deals or quotes. Move those records before deleting it.',
            ]);
        }

        $company->delete();

        return redirect()
            ->route('crm.companies.index')
            ->with('crm_status', 'Company deleted.');
    }

    public function attachContacts(
        AttachCompanyContactsRequest $request,
        Company $company,
        AttachContactsToCompany $attachContacts
    ): RedirectResponse {
        $attachContacts->handle($company, $request->validated('contact_ids'), $request->user());

        return redirect()
            ->route('crm.companies.show', $company)
            ->with('crm_status', 'Contacts attached.');
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
