<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryAcknowledgementStoreRequest;
use App\Http\Requests\InventoryStoreRequest;
use App\Http\Requests\InventoryUpdateRequest;
use App\Http\Requests\UpdateInventoryCategoryRequest;
use App\Http\Resources\InventoryApiResource;
use App\Models\InventoryItem;
use App\Models\ItemClassification;
use App\Models\Supplier;
use App\Models\UserProfile;
use App\Services\Api\InventoryApiService;
use App\Services\DownloadPngService;
use App\Services\InventoryService;
use App\Services\RoomApiService;
use Illuminate\Http\Request;


class InventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected InventoryApiService $inventoryApiService,
    ) {}

    public function InventoryItems(Request $request, RoomApiService $roomsApi)
    {
        $roomResult = $roomsApi->fetchRooms();

        $roomsLookup = collect($roomResult['data'])
            ->keyBy('id');

        $rooms = collect($roomResult['data'])
            ->map(function ($room) {
                return [
                    'id' => $room['id'],
                    'room_name' => $room['room_name'],
                    'description' => $room['description'],
                    'building_name' => $room['building_name'],
                    'capacity' => $room['capacity'],
                ];
            })
            ->values();

        $search = $request->input('search');
        $costRange = $request->input('cost_range');
        $status = $request->input('status');
        $acknowledgementStatus = $request->input('acknowledgement_status');

        $itemClassifications = ItemClassification::all();
        $suppliers = Supplier::all();
        $userProfiles = UserProfile::all();
        $adminProfiles = $this->inventoryService->getAdminProfiles();

        $items = $this->inventoryService->filterAndPaginateInventory(
            $search,
            $costRange,
            $status,
            $acknowledgementStatus
        );

        $items->getCollection()->transform(function ($item) use ($roomsLookup) {

            $roomId = $item->latestHistoryLocation?->room_id;

            $item->room_name = isset($roomsLookup[$roomId])
                ? $roomsLookup[$roomId]['room_name']
                : 'N/A';

            $item->room_id = $roomId;

            return $item;
        });

        return inertia('Inventory/InventoryItem', [
            'rooms' => $rooms,
            'items' => $items,
            'itemClassifications' => $itemClassifications,
            'suppliers' => $suppliers,
            'userProfiles' => $userProfiles,
            'adminProfiles' => $adminProfiles,
        ]);
    }

    public function apiIndex()
    {
        $items = $this->inventoryApiService->getInventory();

        return InventoryApiResource::collection($items);
    }

    public function apiShow($id)
    {
        return new InventoryApiResource(
            $this->inventoryApiService->getById($id)
        );
    }

    public function InventoryAcknowledgementsStore(InventoryAcknowledgementStoreRequest $request)
    {
        $this->inventoryService->createAcknowledgements($request->validated());

        return redirect()->route('inventory.items')->with('success', 'Items assigned successfully!');
    }

    public function updateCategoryForItems(UpdateInventoryCategoryRequest $request)
    {
        $this->inventoryService->updateCategory(
            $request->input('inventory_item_ids'),
            $request->input('category')
        );

        return back()->with('success', 'Category updated for selected items.');
    }


    public function store(InventoryStoreRequest $request)
    {
        $this->inventoryService->createinventoryItems($request->validated());

        return redirect()->route('inventory.items');
    }


    public function update(InventoryUpdateRequest $request, $id)
    {
        $this->inventoryService->updateInventoryItem($id, $request->validated());

        return redirect()->route('inventory.items');
    }

    public function downloadQrPngs(Request $request, DownloadPngService $downloadPngService)
    {
        $ids = $request->input('ids', []);

        if (count($ids) === 1) {
            $pngPath = $downloadPngService->generateQrPng($ids[0]);

            return response()
                ->download($pngPath)
                ->deleteFileAfterSend(true);
        }

        // Multiple items — return ZIP
        $zipPath = $downloadPngService->generateQrZip($ids);

        return response()
            ->download($zipPath)
            ->deleteFileAfterSend(true);
    }

    public function destroy($id)
    {
        $item = InventoryItem::findOrFail($id);
        $item->delete();

        return redirect()->back()->with('success', 'Item deleted successfully.');
    }

    public function convert(Request $request)
    {
        return $this->inventoryService->convertToCsv($request);
    }

    public function importCsv(Request $request)
    {
        return $this->inventoryService->importCsv($request);
    }

    public function exportCsv()
    {
        return $this->inventoryService->exportCsv();
    }
}
