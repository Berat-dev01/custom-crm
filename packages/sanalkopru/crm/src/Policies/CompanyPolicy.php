<?php

namespace Sanalkopru\Crm\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Sanalkopru\Crm\Models\Company;

class CompanyPolicy extends CrmPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return $this->can($user, 'crm.companies.view');
    }

    public function view(Authenticatable $user, Company $company): bool
    {
        return $this->can($user, 'crm.companies.view');
    }

    public function create(Authenticatable $user): bool
    {
        return $this->can($user, 'crm.companies.create');
    }

    public function update(Authenticatable $user, Company $company): bool
    {
        return $this->can($user, 'crm.companies.update');
    }

    public function delete(Authenticatable $user, Company $company): bool
    {
        return $this->can($user, 'crm.companies.delete');
    }

    public function export(Authenticatable $user): bool
    {
        return $this->can($user, 'crm.companies.export');
    }

    public function import(Authenticatable $user): bool
    {
        return $this->can($user, 'crm.companies.import');
    }
}
