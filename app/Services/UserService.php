<?php

namespace App\Services;

use App\Models\User;
use App\Models\InventoryItem;
use App\Models\UserProfile;
use App\Models\AcknowledgementItem;
use App\Models\AcknowledgementReceipt;
use App\Models\ItemBorrowingItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;


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
        ?string $search,
        ?string $sort,
        string $direction = 'asc',
        int $perPage = 10,
        array $matchingRoomIds = [],
        ?string $approvalStatus = null,
    ) {
        $sortable = ['item_name', 'date_acquired', 'date_assigned'];
        $sort = in_array($sort, $sortable, true) ? $sort : null;
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        $query = InventoryItem::query()
            ->with([
                'supplier',
                'latestAcknowledgementItem.acknowledgementReceipts:id,par_date,category',
                'latestHistoryLocation',
            ])
            ->whereHas('latestAcknowledgementItem', function ($q) use ($userId) {
                $q->where('accountable_person_id', $userId);
            });

        if ($search) {
            $query->where(function ($q) use ($search, $matchingRoomIds) {

                // Existing search scope
                $q->searchAssignedItems($search);

                // Search by room from API
                if (!empty($matchingRoomIds)) {
                    $q->orWhereHas('latestHistoryLocation', function ($room) use ($matchingRoomIds) {
                        $room->whereIn('room_id', $matchingRoomIds);
                    });
                }
            });
        }

        if ($sort === 'date_assigned') {
            $query->addSelect([
                'sort_par_date' => AcknowledgementReceipt::select('par_date')
                    ->join(
                        'acknowledgement_items',
                        'acknowledgement_items.acknowledgement_id',
                        '=',
                        'acknowledgement_receipts.id'
                    )
                    ->whereColumn(
                        'acknowledgement_items.inventory_item_id',
                        'inventory_items.id'
                    )
                    ->orderByDesc('acknowledgement_items.id')
                    ->limit(1),
            ]);
        }

        if (filled($approvalStatus)) {
            $query->where('approval_status', $approvalStatus);
        }

        match ($sort) {
            'date_assigned' => $query->orderBy('sort_par_date', $direction),
            null            => $query->orderByDesc('created_at'),
            default         => $query->orderBy("inventory_items.{$sort}", $direction),
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
        // Matches the "Assigned to me" table: the property whose latest
        // acknowledgement is still held by this account.
        $assigned = InventoryItem::whereHas(
            'latestAcknowledgementItem',
            fn ($query) => $query->where('accountable_person_id', $userId)
        );

        $assignedItems = (clone $assigned)->count();

        // Items not yet approved or rejected still wait on this account.
        $byApproval = (clone $assigned)
            ->selectRaw('approval_status, COUNT(*) as total')
            ->groupBy('approval_status')
            ->pluck('total', 'approval_status');

        $approved = (int) ($byApproval['approved'] ?? 0);
        $rejected = (int) ($byApproval['rejected'] ?? 0);

        $receipts = AcknowledgementItem::where('accountable_person_id', $userId)
            ->distinct('acknowledgement_id')
            ->count('acknowledgement_id');

        $borrowed = $this->getBorrowedItems($userId);

        return [
            'assigned_items' => $assignedItems,
            'pending_items' => $assignedItems - $approved - $rejected,
            'approved_items' => $approved,
            'rejected_items' => $rejected,
            'receipts' => $receipts,
            'borrowed_items' => $borrowed->count(),
            'overdue_borrowings' => $borrowed->where('is_overdue', true)->count(),
        ];
    }

    /**
     * The lines this account still has out on loan, newest first.
     */
    public function getBorrowedItems(int $userId): Collection
    {
        return $this->borrowedLinesQuery($userId)
            ->whereNull('returned_at')
            ->latest('id')
            ->get()
            ->map(fn (ItemBorrowingItem $line) => $this->presentBorrowedLine($line));
    }

    /**
     * The lines this account has already given back, latest return first.
     */
    public function paginateReturnedItems(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->borrowedLinesQuery($userId)
            ->whereNotNull('returned_at')
            ->latest('returned_at')
            ->latest('id')
            ->paginate($perPage)
            ->through(fn (ItemBorrowingItem $line) => $this->presentBorrowedLine($line));
    }

    public function getBorrowingStats(int $userId): array
    {
        $borrowed = $this->getBorrowedItems($userId);

        return [
            'borrowed_items' => $borrowed->count(),
            'overdue_borrowings' => $borrowed->where('is_overdue', true)->count(),
            'returned_items' => $this->borrowedLinesQuery($userId)->whereNotNull('returned_at')->count(),
        ];
    }

    private function borrowedLinesQuery(int $userId): Builder
    {
        return ItemBorrowingItem::query()
            ->with(['inventoryItem', 'borrowing'])
            ->whereHas(
                'borrowing',
                fn ($query) => $query->where('borrower_user_id', $userId)
            );
    }

    private function presentBorrowedLine(ItemBorrowingItem $line): array
    {
        $borrowing = $line->borrowing;

        return [
            'id'              => $line->id,
            'reference_code'  => $borrowing?->reference_code,
            'item_name'       => $line->inventoryItem?->item_name ?? 'Item',
            'property_number' => $line->inventoryItem?->property_number ?? '—',
            'serial_number'   => $line->inventoryItem?->serial_number,
            'borrowed_at'     => $borrowing?->borrowed_at?->format('M j, Y'),
            'due_at'          => $borrowing?->due_at?->format('M j, Y'),
            'returned_at'     => $line->returned_at?->format('M j, Y'),
            'is_overdue'      => ! $line->returned_at && $borrowing?->is_overdue,
        ];
    }

    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'email' => $data['email'],
                'status' => $data['status'],
            ]);

            $profileData = collect($data['user_profiles'] ?? [])->only([
                'employee_number',
                'title_name',
                'first_name',
                'middle_name',
                'last_name',
                'ext_name',
                'primary_unit_division_department',
                'employee_primary_unit_college',
                'contact_number',
            ])->all();

            $user->userProfiles()->create($profileData);

            if (!empty($data['roles'])) {
                $user->assignRole($data['roles']);
            }

            return $user->load([
                'userProfiles',
                'roles',
            ]);
        });
    }

    public function updateUser(User $user, array $data): User
    {
        $updateData = [
            'email'  => $data['email'],
            'status' => $data['status'],
        ];

        $user->update($updateData);

        $profileData = collect($data['user_profiles'] ?? [])->only([
            'employee_number',
            'title_name',
            'first_name',
            'middle_name',
            'last_name',
            'ext_name',
            'primary_unit_division_department',
            'employee_primary_unit_college',
            'contact_number',
        ])->all();

        UserProfile::updateOrCreate(
            ['user_id' => $user->id],
            array_merge($profileData, ['user_id' => $user->id])
        );

        if (!empty($data['roles'])) {
            $user->syncRoles($data['roles']);
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

    public function filterAndPaginateArchiveUsers(
        ?string $search = null,
        int $perPage = 10
    ) {
        return User::onlyTrashed()
            ->with('userProfiles')
            ->when($search, fn($query, $search) => $query->search($search))
            ->orderBy('deleted_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }
}
