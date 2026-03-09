<?php

namespace App\Http\Controllers;

use App\Services\RolePermissionService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function __construct(protected RolePermissionService $service) {}

    // Roles
    public function storeRole(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|unique:roles,name',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $this->service->createRole($request->name, $request->permissions ?? []);

        return redirect()->back()->with('success', 'Role created successfully.');
    }

    public function updateRole(Request $request, Role $role)
    {
        $request->validate([
            'name'          => 'required|string|unique:roles,name,' . $role->id,
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $this->service->updateRole($role, $request->name, $request->permissions ?? []);

        return redirect()->back()->with('success', 'Role updated successfully.');
    }

    public function destroyRole(Role $role)
    {
        $this->service->deleteRole($role);

        return redirect()->back()->with('success', 'Role deleted successfully.');
    }

    // Permissions
    public function storePermission(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name',
        ]);

        $this->service->createPermission($request->name);

        return redirect()->back()->with('success', 'Permission created successfully.');
    }

    public function updatePermission(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $permission->id,
        ]);

        $this->service->updatePermission($permission, $request->name);

        return redirect()->back()->with('success', 'Permission updated successfully.');
    }

    public function destroyPermission(Permission $permission)
    {
        $this->service->deletePermission($permission);

        return redirect()->back()->with('success', 'Permission deleted successfully.');
    }
}
