<?php

namespace App\Crm\Policies;

use App\Crm\Models\Tag;
use Illuminate\Contracts\Auth\Authenticatable;

class TagPolicy extends CrmPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return $this->can($user, 'crm.tags.view');
    }

    public function view(Authenticatable $user, Tag $tag): bool
    {
        return $this->can($user, 'crm.tags.view');
    }

    public function create(Authenticatable $user): bool
    {
        return $this->can($user, 'crm.tags.create');
    }

    public function update(Authenticatable $user, Tag $tag): bool
    {
        return $this->can($user, 'crm.tags.update');
    }

    public function delete(Authenticatable $user, Tag $tag): bool
    {
        return $this->can($user, 'crm.tags.delete');
    }
}
