<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;

class UserService
{
    public function filterAndPaginateUsers(
        ?string $search = null,
        int|string|null $status = null,
        int $perPage = 10
    ) {
        return User::with('userProfiles', 'roles', 'permissions')
            ->when(
                $search,
                fn($query, $search) =>
                $query->search($search)
            )
            ->when(
                !is_null($status),
                fn($query) =>
                $query->where('status', $status)
            )
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createUser(array $data): User
    {
        $user = User::create([
            'email'    => $data['email'],
            'password' => bcrypt($data['password']),
            'status'   => $data['status'],
        ]);

        $user->userProfiles()->create($data['user_profiles'] ?? []);

        if (!empty($data['role'])) {
            $user->assignRole($data['role']);
        }

        if (!empty($data['permissions'])) {
            $user->syncPermissions($data['permissions']);
        }

        return $user;
    }

    public function updateUser(User $user, array $data): User
    {
        $updateData = [
            'email'  => $data['email'],
            'status' => $data['status'],
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = bcrypt($data['password']);
        }

        $user->update($updateData);

        $profileData = $data['user_profiles'] ?? [];

        UserProfile::updateOrCreate(
            ['user_id' => $user->id],
            array_merge($profileData, ['user_id' => $user->id])
        );

        if (!empty($data['role'])) {
            $user->syncRoles([$data['role']]);
        } else {
            $user->roles()->detach();
        }

        if (!empty($data['permissions'])) {
            $user->syncPermissions($data['permissions']);
        } else {
            $user->permissions()->detach();
        }

        return $user;
    }
    public function deleteUser(User $user): void
    {
        $user->delete();
    }
}
