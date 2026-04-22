<?php

namespace Sanalkopru\Crm\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Sanalkopru\Crm\Models\Quote;

class QuotePolicy extends CrmPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return $this->can($user, 'crm.quotes.view');
    }

    public function view(Authenticatable $user, Quote $quote): bool
    {
        return $this->can($user, 'crm.quotes.view');
    }

    public function create(Authenticatable $user): bool
    {
        return $this->can($user, 'crm.quotes.create');
    }

    public function update(Authenticatable $user, Quote $quote): bool
    {
        return $this->can($user, 'crm.quotes.update');
    }

    public function delete(Authenticatable $user, Quote $quote): bool
    {
        return $this->can($user, 'crm.quotes.delete');
    }

    public function send(Authenticatable $user, Quote $quote): bool
    {
        return $this->can($user, 'crm.quotes.send');
    }

    public function export(Authenticatable $user, Quote $quote): bool
    {
        return $this->can($user, 'crm.quotes.export');
    }

    public function accept(Authenticatable $user, Quote $quote): bool
    {
        return $this->can($user, 'crm.quotes.accept');
    }

    public function reject(Authenticatable $user, Quote $quote): bool
    {
        return $this->can($user, 'crm.quotes.reject');
    }
}
