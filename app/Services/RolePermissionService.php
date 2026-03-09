<?php

namespace App\Services;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

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
}
