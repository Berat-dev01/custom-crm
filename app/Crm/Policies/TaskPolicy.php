<?php

namespace App\Crm\Policies;

use App\Crm\Models\Task;
use Illuminate\Contracts\Auth\Authenticatable;

class TaskPolicy extends CrmPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return $this->can($user, 'crm.tasks.view');
    }

    public function view(Authenticatable $user, Task $task): bool
    {
        return $this->can($user, 'crm.tasks.view');
    }

    public function create(Authenticatable $user): bool
    {
        return $this->can($user, 'crm.tasks.create');
    }

    public function update(Authenticatable $user, Task $task): bool
    {
        return $this->can($user, 'crm.tasks.update');
    }

    public function delete(Authenticatable $user, Task $task): bool
    {
        return $this->can($user, 'crm.tasks.delete');
    }

    public function assign(Authenticatable $user, Task $task): bool
    {
        return $this->can($user, 'crm.tasks.assign');
    }

    public function complete(Authenticatable $user, Task $task): bool
    {
        return $this->can($user, 'crm.tasks.complete');
    }
}
