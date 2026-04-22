<?php

namespace Sanalkopru\Crm\Services\Authorization;

use Illuminate\Contracts\Auth\Authenticatable;
use Spatie\Permission\Exceptions\GuardDoesNotMatch;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class CrmAuthorization
{
    public function __construct(private readonly PermissionCatalog $catalog) {}

    public function can(?Authenticatable $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        if (! $this->enabled()) {
            return true;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole($this->catalog->roleName('owner'))) {
            return true;
        }

        if (! method_exists($user, 'hasPermissionTo')) {
            return false;
        }

        try {
            return $user->hasPermissionTo($permission, $this->catalog->guardName());
        } catch (GuardDoesNotMatch|PermissionDoesNotExist) {
            return false;
        }
    }

    public function enabled(): bool
    {
        return (bool) config('crm.permissions.enabled', true);
    }
}
