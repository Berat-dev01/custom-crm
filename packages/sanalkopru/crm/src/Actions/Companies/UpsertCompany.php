<?php

namespace Sanalkopru\Crm\Actions\Companies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Sanalkopru\Crm\Models\Company;

class UpsertCompany
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Company $company, array $payload, ?Authenticatable $user = null): Company
    {
        $tagIds = Arr::pull($payload, 'tag_ids', []);
        $payload[$company->exists ? 'updated_by' : 'created_by'] = $user?->getAuthIdentifier();

        $company->fill($payload);
        $company->save();

        $company->tags()->sync($tagIds);

        return $company->refresh();
    }
}
