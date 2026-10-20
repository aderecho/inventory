<?php

namespace App\Http\Controllers;

use App\Services\InventoryService;
use App\Services\RoomApiService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItemApprovalController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected RoomApiService $roomsApi,
    ) {}

    /**
     * Every item with the approval status its accountable person gave it. The
     * list is read-only: approving or rejecting happens from the holder's own
     * dashboard.
     */
    public function index(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            // The shared filter bar sends its status select as
            // acknowledgement_status; here it means the approval status.
            'acknowledgement_status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
        ]);

        // Rooms live in the other system, so resolve each item's room name the
        // same way the Item List does.
        $roomsLookup = collect($this->roomsApi->fetchRooms()['data'])->keyBy('id');

        $items = $this->inventoryService->filterAndPaginateApprovals(
            $request->input('search'),
            $request->input('acknowledgement_status'),
        );

        $items->getCollection()->transform(function ($item) use ($roomsLookup) {
            $roomId = $item->latestHistoryLocation?->room_id;

            $item->room_name = $roomsLookup[$roomId]['room_name'] ?? 'N/A';

            return $item;
        });

        return inertia('ItemApproval/Index', [
            'items' => $items,
        ]);
    }
}
