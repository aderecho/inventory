<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreAssetInspectionRequest;
use App\Http\Requests\StoreAssetConditionRequest;
use App\Services\RoomApiService;
use App\Services\InspectionService;
use App\Services\AssetConditionService;
use App\Models\AssetCondition;

class InspectionController extends Controller
{
    public function __construct(
        protected InspectionService $inspectionService,
        protected AssetConditionService $assetConditionService,
    ) {}

    public function index(Request $request, RoomApiService $roomsApi)
    {
        $roomResult = $roomsApi->fetchRooms();

        $roomsLookup = collect($roomResult['data'])->keyBy('id');

        $rooms = collect($roomResult['data'])
            ->map(function ($room) {
                return [
                    'id' => $room['id'],
                    'room_name' => $room['room_name'],
                    'room_code' => $room['room_code'],
                    'description' => $room['description'],
                    'building' => $room['building'],
                    'building_name' => $room['building_name'],
                    'capacity' => $room['capacity'],
                ];
            })
            ->values();

        $search = $request->input('search');
        $costRange = $request->input('cost_range');
        $status = $request->input('status');
        $acknowledgementStatus = $request->input('acknowledgement_status');
        $roomId = $request->input('room_id');

        $items = $this->inspectionService->filterAndPaginateInspection(
            $search,
            $costRange,
            $status,
            $acknowledgementStatus,
            $roomId,
        );

        $items->getCollection()->transform(function ($item) use ($roomsLookup) {

            $roomId = $item->latestHistoryLocation?->room_id;

            $item->room_name = isset($roomsLookup[$roomId])
                ? $roomsLookup[$roomId]['room_name']
                : 'N/A';

            $item->room_id = $roomId;

            return $item;
        });

        $assetConditions = $this->assetConditionService->getAllConditions();

        return inertia('Inspection/Index', [
            'rooms' => $rooms,
            'items' => $items,
            'assetConditions' => $assetConditions,
            'room_id' => $roomId,
        ]);
    }

    public function getItemLatestInspection(int $itemId)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->inspectionService->getLatestInspection($itemId),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch latest inspection.',
            ], 500);
        }
    }

    /**
     * Store multiple inspections.
     */
    public function store(StoreAssetInspectionRequest $request)
    {
        $validated = $request->validated();

        try {
            $result = $this->inspectionService->createInspections(
                assetConditionId: $validated['asset_condition_id'],
                inspectionDate: $validated['inspection_date'],
                remarks: $validated['remarks'] ?? null,
                itemIds: $validated['selectedItemIds'],
                inspectedBy: auth()->id(),
            );

            if (! $result['success']) {
                return back()
                    ->withInput()
                    ->with('error', 'Failed to create inspections.');
            }

            return redirect()
                ->route('inspection.index')
                ->with('success', sprintf(
                    'Successfully created %d inspection(s).',
                    $result['created_count']
                ));
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'An unexpected error occurred while creating inspections.');
        }
    }

    public function destroyCondition(AssetCondition $assetCondition)
    {
        try {
            $this->assetConditionService->deleteCondition($assetCondition->id);

            return redirect()
                ->route('inspection.index')
                ->with('success', 'Asset condition deleted successfully.');
        } catch (\Throwable $e) {
            report($e);

            return back()->with(
                'error',
                'Unable to delete this condition — it may still be in use by existing inspections.'
            );
        }
    }

    /**
     * Store a new asset condition.
     */
    public function storeCondition(StoreAssetConditionRequest $request)
    {
        $validated = $request->validated();

        try {
            $this->assetConditionService->createCondition(
                conditionName: $validated['condition_name'],
                description: $validated['description'] ?? null,
            );

            return redirect()
                ->route('inspection.index')
                ->with('success', 'Asset condition created successfully.');
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'Failed to create asset condition.');
        }
    }

    /**
     * Show inspection history.
     */
    public function getItemInspectionHistory(int $itemId)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->inspectionService->getItemInspectionHistory($itemId),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch inspection history.',
            ], 500);
        }
    }

    /**
     * Show inspection statistics.
     */
    public function getStats()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->inspectionService->getInspectionStats(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch inspection statistics.',
            ], 500);
        }
    }
}
