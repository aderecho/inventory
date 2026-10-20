<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = [
        'view borrowings',
        'borrow items',
        'return items',
    ];

    /**
     * Roles in this database are named SUPER-ADMIN / ADMIN / USER.
     */
    private array $rolePermissions = [
        'SUPER-ADMIN' => [
            'view borrowings',
            'borrow items',
            'return items',
        ],
        'ADMIN' => [
            'view borrowings',
            'borrow items',
            'return items',
        ],
    ];

    public function up(): void
    {
        foreach ($this->permissions as $name) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $name,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($this->rolePermissions as $roleName => $names) {
            $roleId = DB::table('roles')
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->value('id');

            if (! $roleId) {
                continue;
            }

            foreach ($names as $name) {
                $permissionId = DB::table('permissions')
                    ->where('name', $name)
                    ->where('guard_name', 'web')
                    ->value('id');

                if (! $permissionId) {
                    continue;
                }

                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->permissions as $name) {
            $permissionId = DB::table('permissions')
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->value('id');

            if (! $permissionId) {
                continue;
            }

            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};

