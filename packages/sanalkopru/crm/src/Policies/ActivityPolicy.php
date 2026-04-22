<?php

namespace Sanalkopru\Crm\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Sanalkopru\Crm\Models\Activity;

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
