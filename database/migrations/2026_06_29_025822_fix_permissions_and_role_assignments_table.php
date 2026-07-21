<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * All permissions that should exist.
     */
    private array $permissions = [
        ['id' => 1,  'name' => 'view inventory'],
        ['id' => 2,  'name' => 'create inventory'],
        ['id' => 3,  'name' => 'edit inventory'],
        ['id' => 4,  'name' => 'delete inventory'],
        ['id' => 5,  'name' => 'import inventory'],
        ['id' => 6,  'name' => 'export inventory'],
        ['id' => 7,  'name' => 'print inventory'],
        ['id' => 8,  'name' => 'view suppliers'],
        ['id' => 9,  'name' => 'create suppliers'],
        ['id' => 10, 'name' => 'edit suppliers'],
        ['id' => 11, 'name' => 'delete suppliers'],
        ['id' => 12, 'name' => 'view categories'],
        ['id' => 13, 'name' => 'create categories'],
        ['id' => 14, 'name' => 'edit categories'],
        ['id' => 15, 'name' => 'delete categories'],
        ['id' => 16, 'name' => 'view acknowledgements'],
        ['id' => 17, 'name' => 'create acknowledgements'],
        ['id' => 18, 'name' => 'show acknowledgements'],
        ['id' => 19, 'name' => 'upload acknowledgements'],
        ['id' => 20, 'name' => 'view users'],
        ['id' => 21, 'name' => 'create users'],
        ['id' => 22, 'name' => 'edit users'],
        ['id' => 23, 'name' => 'delete users'],
        ['id' => 24, 'name' => 'view roles'],
        ['id' => 25, 'name' => 'create roles'],
        ['id' => 26, 'name' => 'edit roles'],
        ['id' => 27, 'name' => 'delete roles'],
        ['id' => 28, 'name' => 'view archive_item'],
        ['id' => 29, 'name' => 'restore archive_item'],
        ['id' => 30, 'name' => 'force delete archive_item'],
        ['id' => 31, 'name' => 'view archive_supplier'],
        ['id' => 32, 'name' => 'restore archive_supplier'],
        ['id' => 33, 'name' => 'force delete archive_supplier'],
        ['id' => 34, 'name' => 'view item histories'],
        ['id' => 35, 'name' => 'show item histories'],
    ];

    /**
     * Admin gets all permissions (1-35).
     * Staff gets a limited subset.
     */
    private array $rolePermissions = [
        'admin' => [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35],
        'staff' => [1,2,3,5,6,7,8,9,10,12,13,14,17],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Delete old generic permissions from role_has_permissions first
        $oldIds = DB::table('permissions')
            ->whereIn('name', ['view', 'edit', 'delete'])
            ->pluck('id');

        if ($oldIds->isNotEmpty()) {
            DB::table('role_has_permissions')
                ->whereIn('permission_id', $oldIds)
                ->delete();

            // 2. Delete the old permissions themselves
            DB::table('permissions')
                ->whereIn('name', ['view', 'edit', 'delete'])
                ->delete();
        }

        // 3. Insert missing permissions (skip existing ones by name to avoid duplicates)
        foreach ($this->permissions as $permission) {
            DB::table('permissions')->insertOrIgnore([
                'id'         => $permission['id'],
                'name'       => $permission['name'],
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 4. Only assign permissions to roles that already exist. On a fresh
        // database roles are created later by RolePermissionSeeder.
        $roles = DB::table('roles')
            ->whereIn('name', array_keys($this->rolePermissions))
            ->where('guard_name', 'web')
            ->pluck('id', 'name');

        DB::table('role_has_permissions')
            ->whereIn('role_id', $roles->values())
            ->delete();

        $inserts = [];
        foreach ($this->rolePermissions as $roleName => $permissionIds) {
            $roleId = $roles->get($roleName);

            if (! $roleId) {
                continue;
            }

            foreach ($permissionIds as $permissionId) {
                $inserts[] = [
                    'permission_id' => $permissionId,
                    'role_id'       => $roleId,
                ];
            }
        }

        if ($inserts !== []) {
            DB::table('role_has_permissions')->insert($inserts);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $roleIds = DB::table('roles')
            ->whereIn('name', array_keys($this->rolePermissions))
            ->where('guard_name', 'web')
            ->pluck('id');

        // Remove the assignments created for the configured roles.
        DB::table('role_has_permissions')
            ->whereIn('role_id', $roleIds)
            ->delete();

        // Remove the new permissions
        DB::table('permissions')
            ->whereIn('name', array_column($this->permissions, 'name'))
            ->delete();

        // Restore old generic permissions
        foreach (['view', 'edit', 'delete'] as $name) {
            DB::table('permissions')->insertOrIgnore([
                'name'       => $name,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
