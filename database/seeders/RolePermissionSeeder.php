<?php

namespace Database\Seeders;

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

        $rolesAndPermissions = [
            'admin' => [
                'manage users',
                'manage products',
            ],
            'warehouse' => [
                'manage warehouse',
            ],
            'customer' => [
                'view profile',
            ],
        ];

        $allPermissions = collect($rolesAndPermissions)->flatten()->unique();

        foreach ($allPermissions as $permissionName) {
            Permission::findOrCreate($permissionName, $guard);
        }

        foreach ($rolesAndPermissions as $roleName => $permissionNames) {
            $role = Role::findOrCreate($roleName, $guard);

            $permissions = Permission::whereIn('name', $permissionNames)
                ->where('guard_name', $guard)
                ->get();

            $role->syncPermissions($permissions);
        }

        Role::findOrCreate('super-admin', $guard);
    }
}
