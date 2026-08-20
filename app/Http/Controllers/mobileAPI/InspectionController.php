<?php

namespace App\Http\Controllers\mobileAPI;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetInspectionRequest;
use App\Services\InspectionService;
use App\Services\AssetConditionService;
use Illuminate\Http\Request;

class InspectionController extends Controller
{
    public function __construct(
        protected InspectionService $inspectionService,
        protected AssetConditionService $assetConditionService,
    ) {}

    /**
     * List items with latest inspection + condition (mobile list view).
     */
    public function index(Request $request)
    {
        try {
            $items = $this->inspectionService->paginateItemsForMobile(
                search: $request->input('search'),
                costRange: $request->input('cost_range'),
                status: $request->input('status'),
                acknowledgementStatus: $request->input('acknowledgement_status'),
                roomId: $request->input('room_id'),
                conditionId: $request->input('condition_id'),
                inspectionStatus: $request->input('inspection_status'),
            );

            return response()->json([
                'success' => true,
                'data' => $items,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch items.',
            ], 500);
        }
    }

    public function storeBatch(Request $request)
    {
        $validated = $request->validate([
            'inspections' => ['required', 'array', 'min:1'],

            'inspections.*.inventory_item_id' => [
                'required',
                'integer',
                'exists:inventory_items,id',
            ],

            'inspections.*.asset_condition_id' => [
                'required',
                'integer',
                'exists:asset_conditions,id',
            ],

            'inspections.*.inspection_date' => [
                'required',
                'date',
            ],

            'inspections.*.remarks' => [
                'nullable',
                'string',
            ],
        ]);

        try {
            $result = $this->inspectionService->createBatchInspections(
                inspections: $validated['inspections'],
                inspectedBy: $request->user()->id,
            );

            return response()->json([
                'success' => true,
                'message' => sprintf(
                    'Successfully created %d inspection(s).',
                    $result['created_count']
                ),
                'data' => $result,
            ], 201);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save inspections.',
            ], 500);
        }
    }

    public function conditions()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->assetConditionService->getAllConditions(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch conditions.',
            ], 500);
        }
    }

    /**
     * Full item details for the modal.
     */
    public function show(int $id)
    {
        try {
            $item = $this->inspectionService->getItemWithInspections($id);

            return response()->json([
                'success' => true,
                'data' => $item,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Item not found.',
            ], 404);
        }
    }

    /**
     * Store one or more inspections.
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
                inspectedBy: $request->user()->id,
            );

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create inspections.',
                    'data' => $result,
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => sprintf(
                    'Successfully created %d inspection(s).',
                    $result['created_count']
                ),
                'data' => $result,
            ], 201);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while creating inspections.',
            ], 500);
        }
    }

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
