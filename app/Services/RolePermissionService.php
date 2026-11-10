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

        $this->logActivity('role', "Role \"{$role->name}\" was created", $role, 'created', [
            'permissions' => $permissions,
        ]);

        return $role;
    }

    public function updateRole(Role $role, string $name, array $permissions = []): Role
    {
        $oldName = $role->name;
        $role->update(['name' => $name]);
        $role->syncPermissions($permissions);

        $this->logActivity('role', "Role \"{$role->name}\" was updated", $role, 'updated', [
            'old_name' => $oldName,
            'permissions' => $permissions,
        ]);

        return $role;
    }

    public function deleteRole(Role $role): void
    {
        $this->logActivity('role', "Role \"{$role->name}\" was deleted", $role, 'deleted');
        $role->delete();
    }

    // Permissions
    public function getAllPermissions()
    {
        return Permission::all();
    }

    public function createPermission(string $name): Permission
    {
        $permission = Permission::create(['name' => $name, 'guard_name' => 'web']);

        $this->logActivity('permission', "Permission \"{$permission->name}\" was created", $permission, 'created');

        return $permission;
    }

    public function updatePermission(Permission $permission, string $name): Permission
    {
        $oldName = $permission->name;
        $permission->update(['name' => $name]);

        $this->logActivity('permission', "Permission \"{$permission->name}\" was updated", $permission, 'updated', [
            'old_name' => $oldName,
        ]);

        return $permission;
    }

    public function deletePermission(Permission $permission): void
    {
        $this->logActivity('permission', "Permission \"{$permission->name}\" was deleted", $permission, 'deleted');
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

        $this->logActivity('user_permission', "Permissions for {$user->email} were updated", $user, 'permissions_updated', [
            'given' => array_values(array_diff($give, $revoke)),
            'revoked' => array_values($revoke),
        ]);
    }

    private function logActivity(
        string $logName,
        string $description,
        mixed $subject = null,
        string $event = 'updated',
        array $properties = [],
    ): void {
        $logger = activity($logName)
            ->causedBy(auth()->user())
            ->event($event)
            ->withProperties($properties);

        if ($subject !== null) {
            $logger->performedOn($subject);
        }

        $logger->log($description);
    }
}
