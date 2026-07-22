<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Permissions missing from production.
     */
    private array $missingPermissions = [
        'view acknowledgements',
        'show acknowledgements',
        'upload acknowledgements',
        'view dashboard',
    ];

    /**
     * Admin gets all acknowledgement permissions plus view dashboard.
     * Staff only gets view dashboard.
     */
    private array $rolePermissions = [
        'admin' => [
            'view acknowledgements',
            'show acknowledgements',
            'upload acknowledgements',
            'view dashboard',
        ],
        'staff' => [
            'view dashboard',
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Insert missing permissions (no forced IDs — let DB auto-assign)
        foreach ($this->missingPermissions as $name) {
            DB::table('permissions')->insertOrIgnore([
                'name'       => $name,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Assign permissions to roles by name lookup (not hardcoded ID)
        foreach ($this->rolePermissions as $roleName => $permissionNames) {
            $roleId = DB::table('roles')
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->value('id');

            if (! $roleId) {
                continue;
            }

            foreach ($permissionNames as $name) {
                $permissionId = DB::table('permissions')
                    ->where('name', $name)
                    ->where('guard_name', 'web')
                    ->value('id');

                if (! $permissionId) continue;

                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id'       => $roleId,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->missingPermissions as $name) {
            $permissionId = DB::table('permissions')
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->value('id');

            if (! $permissionId) continue;

            // Remove from role assignments first
            DB::table('role_has_permissions')
                ->where('permission_id', $permissionId)
                ->delete();

            // Then remove the permission itself
            DB::table('permissions')
                ->where('id', $permissionId)
                ->delete();
        }
    }
};
