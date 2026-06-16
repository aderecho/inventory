<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    // --- JUST ADD NEW MODULES HERE ---
    protected array $modules = [
        'inventory',
        'suppliers',
        'categories',
        'reports',
        'acknowledgements',
        'users',
        'roles',
        'archive'
    ];

    // --- DEFINE WHICH ACTIONS EACH MODULE SUPPORTS ---
    protected array $actions = [
        'inventory'       => ['view', 'create', 'edit', 'delete', 'import', 'export', 'print'],
        'suppliers'       => ['view', 'create', 'edit', 'delete'],
        'categories'      => ['view', 'create', 'edit', 'delete'],
        'reports'         => ['view', 'export'],
        'acknowledgements'=> ['view', 'create'],
        'users'           => ['view', 'create', 'edit', 'delete'],
        'roles'           => ['view', 'create', 'edit', 'delete'],
        'archive'       => ['view', 'restore', 'force delete'],
    ];

    // --- DEFINE WHAT EACH ROLE CAN DO PER MODULE ---
    protected array $rolePermissions = [
        'admin' => '*', // wildcard = all permissions

        'staff' => [
            'inventory'        => ['view', 'create', 'edit', 'import', 'export', 'print'],
            'suppliers'        => ['view', 'create', 'edit'],
            'categories'       => ['view', 'create', 'edit'],
            'reports'          => ['view'],
            'acknowledgements' => ['view', 'create'],
        ],
    ];

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Auto-generate and create all permissions
        $allPermissions = [];

        foreach ($this->modules as $module) {
            $actions = $this->actions[$module] ?? ['view', 'create', 'edit', 'delete'];
            foreach ($actions as $action) {
                $name = "{$action} {$module}";
                Permission::firstOrCreate(['name' => $name]);
                $allPermissions[] = $name;
            }
        }

        // Assign permissions to roles
        foreach ($this->rolePermissions as $roleName => $access) {
            $role = Role::firstOrCreate(['name' => $roleName]);

            if ($access === '*') {
                $role->syncPermissions($allPermissions);
                continue;
            }

            $rolePerms = [];
            foreach ($access as $module => $actions) {
                foreach ($actions as $action) {
                    $rolePerms[] = "{$action} {$module}";
                }
            }

            $role->syncPermissions($rolePerms);
        }
    }
}