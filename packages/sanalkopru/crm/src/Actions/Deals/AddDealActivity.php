<?php

namespace Sanalkopru\Crm\Actions\Deals;

use Illuminate\Contracts\Auth\Authenticatable;
use Sanalkopru\Crm\Models\Activity;
use Sanalkopru\Crm\Models\Deal;

class AddDealActivity
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Deal $deal, array $payload, ?Authenticatable $user = null): Activity
    {
        $payload['activityable_type'] = $deal::class;
        $payload['activityable_id'] = $deal->id;
        $payload['user_id'] = $payload['user_id'] ?? $user?->getAuthIdentifier();
        $payload['created_by'] = $user?->getAuthIdentifier();
        $payload['occurred_at'] = $payload['occurred_at'] ?? now();

        return Activity::query()->create($payload);
    }
}
