<?php

namespace App\Crm\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use App\Crm\Models\Quote;

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
        // Accepted and rejected quotes are locked; changes require
        // duplicating the quote as a new draft.
        return $this->can($user, 'crm.quotes.update') && ! $quote->isLocked();
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
