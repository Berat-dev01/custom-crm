<?php

namespace Sanalkopru\Crm\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Sanalkopru\Crm\Actions\Companies\UpsertCompany;
use Sanalkopru\Crm\Http\Requests\Companies\StoreCompanyRequest;
use Sanalkopru\Crm\Http\Requests\Companies\UpdateCompanyRequest;
use Sanalkopru\Crm\Http\Resources\Api\CompanyResource;
use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Services\Companies\CompanyQuery;

class CompaniesController extends Controller
{
    public function __construct(private readonly CompanyQuery $companies) {}

    public function index(Request $request): mixed
    {
        Gate::authorize('viewAny', Company::class);
        $this->validateIndex($request);

        return CompanyResource::collection($this->companies->paginate($request));
    }

    public function store(StoreCompanyRequest $request, UpsertCompany $upsert): mixed
    {
        $company = $upsert->handle(new Company, $request->payload(), $request->user());

        return (new CompanyResource($company->load(['owner', 'tags'])))
            ->additional(['message' => 'Company created.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Company $company): CompanyResource
    {
        Gate::authorize('view', $company);

        return new CompanyResource($company->load(['owner', 'tags'])->loadCount(['contacts', 'deals', 'quotes']));
    }

    public function update(UpdateCompanyRequest $request, Company $company, UpsertCompany $upsert): CompanyResource
    {
        $company = $upsert->handle($company, $request->payload(), $request->user());

        return (new CompanyResource($company->load(['owner', 'tags'])))
            ->additional(['message' => 'Company updated.']);
    }

    private function validateIndex(Request $request): void
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'sector' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'tag_id' => ['nullable', 'integer', 'exists:tags,id'],
            'sort' => ['nullable', 'string', 'in:'.implode(',', CompanyQuery::SORTS)],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.config('crm.api.max_per_page', 100)],
        ]);
    }
}
