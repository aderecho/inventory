<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {

        $permissions = [
            'view',
            'edit',
            'delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);

        // Assign permissions
        $adminRole->givePermissionTo(Permission::all());

        $staffRole->givePermissionTo([
            'view',
            'edit',
        ]);
    }
}
