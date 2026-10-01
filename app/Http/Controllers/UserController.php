<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventoryItem;
use App\Models\User;
use App\Services\UserService;
use App\Services\RoomApiService;
use App\Models\UserProfile;
use App\Notifications\ItemApprovalStatusChanged;
use App\Notifications\ItemsPendingApproval;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected RoomApiService $roomsApi,
    ) {}

    public function index(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string'],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'approval_status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
        ]);

        $userId = auth()->id();

        $roomResult = $this->roomsApi->fetchRooms();

        $roomsLookup = collect($roomResult['data'])
            ->keyBy('id');

        $matchingRoomIds = [];

        if ($request->filled('search')) {
            $search = strtolower(trim($request->search));

            $matchingRoomIds = collect($roomResult['data'])
                ->filter(function ($room) use ($search) {
                    return str_contains(strtolower($room['room_name']), $search)
                        || str_contains(strtolower($room['description']), $search)
                        || str_contains(strtolower($room['building_name']), $search);
                })
                ->pluck('id')
                ->all();
        }

        $items = $this->userService->filterAndPaginateAssignedItems(
            $userId,
            $request->search,
            $request->sort,
            $request->direction ?? 'asc',
            10,
            $matchingRoomIds,
            $request->approval_status,
        );

        $items->getCollection()->transform(function ($item) use ($roomsLookup) {
            $roomId = $item->latestHistoryLocation?->room_id;

            $item->room_id = $roomId;
            $item->room_name = $roomsLookup[$roomId]['room_name'] ?? 'N/A';
            $item->room_description = $roomsLookup[$roomId]['description'] ?? null;
            $item->building_name = $roomsLookup[$roomId]['building_name'] ?? null;

            return $item;
        });

        $items->appends($request->only(['search', 'sort', 'direction', 'approval_status']));

        return inertia('Users/Dashboard', [
            'user' => $this->userService->getAuthenticatedUser(),
            'items' => $items,
            'stats' => $this->userService->getDashboardStats($userId),
            'filters' => [
                'search' => $request->search,
                'sort' => $request->sort,
                'direction' => $request->direction ?? 'asc',
                'approval_status' => $request->approval_status,
            ],
        ]);
    }

    public function updateApproval(Request $request, InventoryItem $inventoryItem)
    {
        $request->validate([
            'approval_status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
        ]);

        $accountablePersonId = $inventoryItem->latestAcknowledgementItem?->accountable_person_id;

        abort_unless(
            $accountablePersonId === auth()->id(),
            403,
            'You are not authorized to approve or reject this item.'
        );

        $inventoryItem->update([
            'approval_status' => $request->approval_status,
        ]);

        $this->syncPendingApprovalNotification(
            auth()->user(),
            $inventoryItem,
            $request->approval_status,
        );

        $approver = auth()->user();

        $profile = UserProfile::find($approver->id);

        $approverName = trim(
            ($profile?->first_name ?? '') . ' ' .
                ($profile?->middle_name ?? '') . ' ' .
                ($profile?->last_name ?? '')
        );

        $approverName = $approverName ?: $approver->email;

        $item = [
            'id' => $inventoryItem->id,
            'property_number' => $inventoryItem->property_number,
            'item_name' => $inventoryItem->item_name,
        ];

        $admins = User::role(['ADMIN', 'SUPER-ADMIN'])
            ->get()
            ->reject(fn(User $admin) => $admin->id === $approver->id);

        foreach ($admins as $admin) {
            $existingNotification = $admin->unreadNotifications()
                ->where('type', ItemApprovalStatusChanged::class)
                ->latest()
                ->first();

            $items = [$item];

            if ($existingNotification) {
                $data = $existingNotification->data;

                if (
                    ($data['approved_by'] ?? null) === $approverName &&
                    ($data['status'] ?? null) === $request->approval_status
                ) {
                    $existingItems = $data['items'] ?? [];

                    if (!collect($existingItems)->contains('id', $inventoryItem->id)) {
                        $existingItems[] = $item;
                    }

                    $items = $existingItems;

                    // Remove the stale row — notify() below creates the up-to-date
                    // replacement AND broadcasts it, keeping DB and socket in sync.
                    $existingNotification->delete();
                }
            }

            $admin->notify(
                new ItemApprovalStatusChanged($items, $request->approval_status, $approverName)
            );
        }

        return back();
    }

    protected function syncPendingApprovalNotification(
        User $user,
        InventoryItem $inventoryItem,
        string $status,
    ): void {
        $notifications = $user->notifications()
            ->where('type', ItemsPendingApproval::class)
            ->get();

        foreach ($notifications as $notification) {
            $data = $notification->data;
            $items = $data['items'] ?? [];
            $changed = false;

            foreach ($items as $index => $item) {
                if (($item['id'] ?? null) == $inventoryItem->id) {
                    $items[$index]['approval_status'] = $status;
                    $changed = true;
                }
            }

            if ($changed) {
                $notification->update([
                    'data' => array_merge($data, [
                        'items' => $items,
                    ]),
                ]);
            }
        }
    }
}
