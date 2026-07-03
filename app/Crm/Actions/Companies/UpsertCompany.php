<?php

namespace App\Crm\Actions\Companies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use App\Crm\Models\Company;
use App\Crm\Services\Webhooks\CrmWebhookDispatcher;

class UpsertCompany
{
    public function __construct(private readonly CrmWebhookDispatcher $webhooks) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Company $company, array $payload, ?Authenticatable $user = null): Company
    {
        $tagIds = Arr::pull($payload, 'tag_ids', []);
        $isNew = ! $company->exists;
        $payload[$company->exists ? 'updated_by' : 'created_by'] = $user?->getAuthIdentifier();

        $company->fill($payload);
        $company->save();

        $company->tags()->sync($tagIds);
        $company = $company->refresh();

        if ($isNew) {
            $this->webhooks->dispatch('company.created', $company);
        }

        return $company;
    }
}
