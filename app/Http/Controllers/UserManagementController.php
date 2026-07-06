<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use App\Models\Organization;
use App\Services\UserService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function __construct(protected UserService $userService) {}

    public function userManagement(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $users = $this->userService->filterAndPaginateUsers($search, $status);

        $users->getCollection()->transform(function ($user) {
            $user->direct_permissions = $user->getDirectPermissions()->pluck('name');
            $user->forbidden_permissions = $user->permissions()
                ->wherePivot('forbidden', true)->pluck('name');
            return $user;
        });

        return inertia('UserManagement', [
            'users'       => $users,
            'roles'       => Role::with('permissions')->get(['id', 'name']),
            'permissions' => Permission::all(['id', 'name']),
            'organizations' => Organization::select('id', 'name', 'short_code')->get(),
        ]);
    }

    public function store(UserStoreRequest $request)
    {
        $this->userService->createUser($request->validated());

        return redirect()->back()->with('success', 'User created successfully');
    }

    public function update(UserUpdateRequest $request, User $user)
    {
        $this->userService->updateUser($user, $request->validated());

        return redirect()->back()->with('success', 'User updated successfully');
    }

    public function destroy(User $user)
    {
        $this->userService->deleteUser($user);

        return redirect()->back()->with('success', 'User deleted successfully');
    }
}