<?php

namespace Sanalkopru\Crm\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Sanalkopru\Crm\Services\Authorization\CrmAuthorization;

abstract class CrmPolicy
{
    protected function can(Authenticatable $user, string $permission): bool
    {
        return app(CrmAuthorization::class)->can($user, $permission);
    }
}
