<?php

namespace Sanalkopru\Crm\Actions\Deals;

use Illuminate\Contracts\Auth\Authenticatable;
use Sanalkopru\Crm\Models\Activity;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Services\Activities\ActivityLogger;

class AddDealActivity
{
    public function __construct(private readonly ActivityLogger $activities) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Deal $deal, array $payload, ?Authenticatable $user = null): Activity
    {
        return $this->activities->manual($deal, $payload, $user);
    }
}
