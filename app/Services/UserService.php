<?php

namespace App\Services;

use App\Models\User;
use App\Models\InventoryItem;
use App\Models\UserProfile;
use App\Models\AcknowledgementItem;
use App\Models\AcknowledgementReceipt;


class UserService
{
    public function filterAndPaginateUsers(
        ?string $search = null,
        int|string|null $status = null,
        int $perPage = 10
    ) {
        return User::with('userProfiles.organizations', 'userProfiles.primaryOrganization', 'roles', 'permissions')
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
            ->when(
                auth()->check(),
                fn($query) =>
                $query->where('id', '!=', auth()->id())
            )
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }
    public function filterAndPaginateAssignedItems(
        int $userId,
        ?string $search = null,
        ?string $sort = null,
        string $direction = 'asc',
        int $perPage = 10
    ) {
        $sortable = ['item_name', 'date_acquired', 'date_assigned'];
        $sort = in_array($sort, $sortable, true) ? $sort : null;
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        $query = InventoryItem::query()
            ->with([
                'supplier',
                'latestAcknowledgementItem.acknowledgementReceipts:id,par_date,category',
                'latestAcknowledgementItem.files',
            ])
            ->whereHas('latestAcknowledgementItem', function ($q) use ($userId) {
                $q->where('accountable_person_id', $userId);
            })
            ->when(
                $search,
                fn($q, $search) => $q->searchAssignedItems($search)
            );

        if ($sort === 'date_assigned') {
            $query->addSelect([
                'sort_par_date' => AcknowledgementReceipt::select('par_date')
                    ->join('acknowledgement_items', 'acknowledgement_items.acknowledgement_id', '=', 'acknowledgement_receipts.id')
                    ->whereColumn('acknowledgement_items.inventory_item_id', 'inventory_items.id')
                    ->orderByDesc('acknowledgement_items.id')
                    ->limit(1),
            ]);
        }

        match ($sort) {
            'date_assigned' => $query->orderBy('sort_par_date', $direction),
            null             => $query->orderByDesc('created_at'),
            default          => $query->orderBy("inventory_items.{$sort}", $direction),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    public function getAuthenticatedUser()
    {
        return User::with('userProfiles')
            ->where('id', auth()->id())
            ->firstOrFail();
    }

    public function getDashboardStats(int $userId): array
    {
        $assignedItems = AcknowledgementItem::where('accountable_person_id', $userId)
            ->distinct('inventory_item_id')
            ->count('inventory_item_id');

        $receipts = AcknowledgementItem::where('accountable_person_id', $userId)
            ->distinct('acknowledgement_id')
            ->count('acknowledgement_id');

        return [
            'assigned_items' => $assignedItems,
            'receipts' => $receipts,
        ];
    }

    public function createUser(array $data): User
    {
        $user = User::create([
            'email'    => $data['email'],
            'password' => bcrypt($data['password']),
            'status'   => $data['status'],
        ]);

        $profile = $user->userProfiles()->create($data['user_profiles'] ?? []);

        $profile->organizations()->sync($data['organizations'] ?? []);
        $profile->update([
            'primary_organization_id' => $data['primary_organization_id'] ?? null,
        ]);

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

        $profile = UserProfile::updateOrCreate(
            ['user_id' => $user->id],
            array_merge($profileData, ['user_id' => $user->id])
        );

        $profile->organizations()->sync($data['organizations'] ?? []);
        $profile->update([
            'primary_organization_id' => $data['primary_organization_id'] ?? null,
        ]);

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
