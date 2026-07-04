<?php

namespace App\Crm\Policies;

use App\Crm\Models\Activity;
use Illuminate\Contracts\Auth\Authenticatable;

class ActivityPolicy extends CrmPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return $this->can($user, 'crm.activities.view');
    }

    public function view(Authenticatable $user, Activity $activity): bool
    {
        return $this->can($user, 'crm.activities.view');
    }

    public function create(Authenticatable $user): bool
    {
        return $this->can($user, 'crm.activities.create');
    }

    public function update(Authenticatable $user, Activity $activity): bool
    {
        return $this->can($user, 'crm.activities.update');
    }

    public function delete(Authenticatable $user, Activity $activity): bool
    {
        return $this->can($user, 'crm.activities.delete');
    }
}
