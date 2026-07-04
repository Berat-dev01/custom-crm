<?php

namespace App\Crm\Policies;

use App\Crm\Services\Authorization\CrmAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;

abstract class CrmPolicy
{
    protected function can(Authenticatable $user, string $permission): bool
    {
        return app(CrmAuthorization::class)->can($user, $permission);
    }
}
