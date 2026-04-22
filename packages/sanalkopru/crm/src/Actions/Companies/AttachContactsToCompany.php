<?php

namespace Sanalkopru\Crm\Actions\Companies;

use Illuminate\Contracts\Auth\Authenticatable;
use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Models\Contact;

class AttachContactsToCompany
{
    /**
     * @param  list<int>  $contactIds
     */
    public function handle(Company $company, array $contactIds, ?Authenticatable $user = null): int
    {
        return Contact::query()
            ->whereKey($contactIds)
            ->update([
                'company_id' => $company->id,
                'updated_by' => $user?->getAuthIdentifier(),
                'updated_at' => now(),
            ]);
    }
}
