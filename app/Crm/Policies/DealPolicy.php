<?php

namespace App\Crm\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use App\Crm\Models\Deal;

class DealPolicy extends CrmPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return $this->can($user, 'crm.deals.view');
    }

    public function view(Authenticatable $user, Deal $deal): bool
    {
        return $this->can($user, 'crm.deals.view');
    }

    public function create(Authenticatable $user): bool
    {
        return $this->can($user, 'crm.deals.create');
    }

    public function update(Authenticatable $user, Deal $deal): bool
    {
        return $this->can($user, 'crm.deals.update');
    }

    public function delete(Authenticatable $user, Deal $deal): bool
    {
        return $this->can($user, 'crm.deals.delete');
    }

    public function move(Authenticatable $user, Deal $deal): bool
    {
        return $this->can($user, 'crm.deals.move');
    }

    public function close(Authenticatable $user, Deal $deal): bool
    {
        return $this->can($user, 'crm.deals.close');
    }

    public function export(Authenticatable $user): bool
    {
        return $this->can($user, 'crm.deals.export');
    }

    public function import(Authenticatable $user): bool
    {
        return $this->can($user, 'crm.deals.import');
    }
}
