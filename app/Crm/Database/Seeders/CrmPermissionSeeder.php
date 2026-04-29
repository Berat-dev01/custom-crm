<?php

namespace App\Crm\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Crm\Services\Authorization\PermissionCatalog;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CrmPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = app(PermissionCatalog::class);
        $guard = $catalog->guardName();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($catalog->permissions() as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => $guard,
            ]);
        }

        foreach ($catalog->roles() as $roleKey => $roleConfig) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleConfig['name'],
                'guard_name' => $guard,
            ]);

            $role->syncPermissions($catalog->permissionsForRole($roleKey));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
