<?php

namespace App\Crm\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use App\Crm\Services\Authorization\CrmAuthorization;

abstract class CrmPolicy
{
    protected function can(Authenticatable $user, string $permission): bool
    {
        return app(CrmAuthorization::class)->can($user, $permission);
    }
}
