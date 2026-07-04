<?php

namespace App\Crm\Policies;

use App\Crm\Models\Contact;
use Illuminate\Contracts\Auth\Authenticatable;

class ContactPolicy extends CrmPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return $this->can($user, 'crm.contacts.view');
    }

    public function view(Authenticatable $user, Contact $contact): bool
    {
        return $this->can($user, 'crm.contacts.view');
    }

    public function create(Authenticatable $user): bool
    {
        return $this->can($user, 'crm.contacts.create');
    }

    public function update(Authenticatable $user, Contact $contact): bool
    {
        return $this->can($user, 'crm.contacts.update');
    }

    public function delete(Authenticatable $user, Contact $contact): bool
    {
        return $this->can($user, 'crm.contacts.delete');
    }

    public function export(Authenticatable $user): bool
    {
        return $this->can($user, 'crm.contacts.export');
    }

    public function import(Authenticatable $user): bool
    {
        return $this->can($user, 'crm.contacts.import');
    }
}
