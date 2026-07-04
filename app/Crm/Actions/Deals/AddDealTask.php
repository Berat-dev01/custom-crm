<?php

namespace App\Crm\Actions\Deals;

use App\Crm\Models\Deal;
use App\Crm\Models\Task;
use Illuminate\Contracts\Auth\Authenticatable;

class AddDealTask
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Deal $deal, array $payload, ?Authenticatable $user = null): Task
    {
        $payload['taskable_type'] = $deal->getMorphClass();
        $payload['taskable_id'] = $deal->id;
        $payload['created_by'] = $user?->getAuthIdentifier();
        $payload['status'] = $payload['status'] ?? 'open';

        return Task::query()->create($payload);
    }
}
