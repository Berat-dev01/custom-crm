<?php

namespace App\Crm\Actions\Companies;

use Illuminate\Contracts\Auth\Authenticatable;
use App\Crm\Models\Company;
use App\Crm\Models\Contact;

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
