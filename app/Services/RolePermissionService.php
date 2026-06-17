<?php

namespace App\Services;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\DB;

class RolePermissionService
{
    // Roles
    public function getAllRoles()
    {
        return Role::with('permissions')->get();
    }

    public function createRole(string $name, array $permissions = []): Role
    {
        $role = Role::create(['name' => $name, 'guard_name' => 'web']);
        if (!empty($permissions)) {
            $role->syncPermissions($permissions);
        }
        return $role;
    }

    public function updateRole(Role $role, string $name, array $permissions = []): Role
    {
        $role->update(['name' => $name]);
        $role->syncPermissions($permissions);
        return $role;
    }

    public function deleteRole(Role $role): void
    {
        $role->delete();
    }

    // Permissions
    public function getAllPermissions()
    {
        return Permission::all();
    }

    public function createPermission(string $name): Permission
    {
        return Permission::create(['name' => $name, 'guard_name' => 'web']);
    }

    public function updatePermission(Permission $permission, string $name): Permission
    {
        $permission->update(['name' => $name]);
        return $permission;
    }

    public function deletePermission(Permission $permission): void
    {
        $permission->delete();
    }

    public function getUserPermissions(User $user): array
    {
        return [
            'roles'                => $user->getRoleNames(),
            'role_permissions'     => $user->getPermissionsViaRoles()->pluck('name'),
            'direct_permissions'   => $user->getDirectPermissions()->pluck('name'),
            'forbidden_permissions' => $user->permissions()->wherePivot('forbidden', true)->pluck('name'), // if using forbidden
        ];
    }

    // Sync per-user permission overrides
    public function updateUserPermissions(User $user, array $give = [], array $revoke = []): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Remove ALL direct permissions first (given and forbidden)
        $user->permissions()->detach();

        // Give selected direct permissions (not forbidden ones)
        $giveOnly = array_diff($give, $revoke);
        if (!empty($giveOnly)) {
            $user->givePermissionTo($giveOnly);
        }

        // Manually insert forbidden permissions via pivot
        if (!empty($revoke)) {
            $permissionIds = \Spatie\Permission\Models\Permission::whereIn('name', $revoke)->pluck('id');
            foreach ($permissionIds as $permissionId) {
                // Use updateOrInsert to avoid duplicate primary key
                \DB::table('model_has_permissions')->updateOrInsert(
                    [
                        'permission_id' => $permissionId,
                        'model_id'      => $user->id,
                        'model_type'    => User::class,
                    ],
                    [
                        'forbidden' => true,
                    ]
                );
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
