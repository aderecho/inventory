<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ItemArchivingService;
use App\Services\RoomApiService;
use App\Models\InventoryItem;
use App\Models\AssetCondition;


class ItemArchivingController extends Controller
{
    public function __construct(
        protected ItemArchivingService $itemArchivingService,
    ) {}
    public function index(Request $request, RoomApiService $roomsApi)
    {
        $search = $request->input('search');
        $costRange = $request->input('cost_range');
        $assetConditionId = $request->input('asset_condition_id');
        $acknowledgementStatus = $request->input('acknowledgement_status');
        $roomId = $request->input('room_id');

        $items = $this->itemArchivingService->filterAndPaginateArchive(
            $search,
            $costRange,
            $assetConditionId,
            $acknowledgementStatus,
            $roomId,
        );

        // The room filter needs the same room list the inventory module uses.
        $rooms = collect($roomsApi->fetchRooms()['data'] ?? [])
            ->map(fn($room) => [
                'id' => $room['id'],
                'room_name' => $room['room_name'],
                'room_code' => $room['room_code'],
                'description' => $room['description'],
                'building' => $room['building'],
                'building_name' => $room['building_name'],
                'capacity' => $room['capacity'],
            ])
            ->values();

        return inertia('ItemDisposal', [
            'items' => $items,
            'rooms' => $rooms,
            'assetConditions' => AssetCondition::all(),
        ]);
    }

    public function restore($id)
    {
        $item = InventoryItem::onlyTrashed()->findOrFail($id);

        $exists = InventoryItem::query()
            ->where('property_number', $item->property_number)
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'restore' => 'Cannot restore. Property number already exists.'
            ]);
        }

        $item->restore();

        return back()->with('success', 'Item restored successfully.');
    }

    public function forceDelete($id)
    {
        $this->itemArchivingService->forceDelete($id);

        return back()->with('success', 'Item permanently deleted.');
    }

    /**
     * Restores several archived items. Property numbers that are already
     * taken by a live item are skipped and reported back.
     */
    public function batchRestore(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $items = InventoryItem::onlyTrashed()
            ->whereIn('id', $data['ids'])
            ->get();

        $restored = 0;
        $skipped = [];

        foreach ($items as $item) {
            $exists = InventoryItem::query()
                ->where('property_number', $item->property_number)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                $skipped[] = $item->property_number;
                continue;
            }

            $item->restore();
            $restored++;
        }

        $message = $restored === 1
            ? 'Item restored successfully.'
            : $restored . ' items restored successfully.';

        if (empty($skipped)) {
            return back()->with('success', $message);
        }

        $preview = implode(', ', array_slice($skipped, 0, 5));
        $more = count($skipped) > 5 ? ' and ' . (count($skipped) - 5) . ' more' : '';

        return back()
            ->with('success', $message)
            ->with(
                'warning',
                'Property number already exists, so these were not restored: '
                    . $preview . $more
            );
    }

    public function batchForceDelete(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $ids = InventoryItem::onlyTrashed()
            ->whereIn('id', $data['ids'])
            ->pluck('id');

        foreach ($ids as $id) {
            $this->itemArchivingService->forceDelete($id);
        }

        return back()->with(
            'success',
            $ids->count() === 1
                ? 'Item permanently deleted.'
                : $ids->count() . ' items permanently deleted.'
        );
    }
}
