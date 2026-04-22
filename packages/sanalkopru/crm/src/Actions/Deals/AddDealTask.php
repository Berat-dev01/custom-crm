<?php

namespace Sanalkopru\Crm\Actions\Deals;

use Illuminate\Contracts\Auth\Authenticatable;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\Task;

class AddDealTask
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Deal $deal, array $payload, ?Authenticatable $user = null): Task
    {
        $payload['taskable_type'] = $deal::class;
        $payload['taskable_id'] = $deal->id;
        $payload['created_by'] = $user?->getAuthIdentifier();
        $payload['status'] = $payload['status'] ?? 'open';

        return Task::query()->create($payload);
    }
}
