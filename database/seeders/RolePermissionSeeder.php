<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'api';

        $allPermissions = collect(RoleType::cases())
            ->flatMap(fn(RoleType $role) => $role->defaultPermissions())
            ->unique();

        foreach ($allPermissions as $permissionName) {
            Permission::findOrCreate($permissionName, $guard);
        }

        foreach (RoleType::cases() as $roleEnum) {
            $role = Role::findOrCreate($roleEnum->value, $guard);

            $permissionNames = $roleEnum->defaultPermissions();

            if (!empty($permissionNames)) {
                $permissions = Permission::whereIn('name', $permissionNames)
                    ->where('guard_name', $guard)
                    ->get();

                $role->syncPermissions($permissions);
            }
        }
    }
}
