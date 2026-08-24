<?php

namespace App\Services;

use App\Models\AssetInspection;
use App\Models\InventoryItem;
use App\Models\AssetCondition;
use Illuminate\Database\Eloquent\Collection;

class InspectionService
{
    public function filterAndPaginateInspection(
        ?string $search = null,
        ?string $costRange = null,
        int|string|null $status = null,
        ?string $acknowledgementStatus = null,
        ?int $roomId = null,
        int $perPage = 10
    ) {
        return InventoryItem::with([
            'itemClassification',
            'supplier',
            'latestHistoryLocation',
            'latestAcknowledgementItem.accountablePerson',
            'acknowledgementHistory.accountablePerson',
            'latestInspection.assetCondition',
        ])
            ->when(
                $search,
                fn($query, $search) => $query->search($search)
            )
            ->when($costRange, function ($query, $costRange) {
                [$min, $max] = array_pad(explode('-', $costRange), 2, null);

                $min = $min !== '' ? $min : null;
                $max = $max !== '' ? $max : null;

                if ($min !== null && $max !== null) {
                    $query->whereBetween('unit_cost', [(float) $min, (float) $max]);
                } elseif ($min !== null) {
                    $query->where('unit_cost', '>=', (float) $min);
                } elseif ($max !== null) {
                    $query->where('unit_cost', '<=', (float) $max);
                }
            })
            ->when(
                !is_null($status),
                fn($query) => $query->where('status', $status)
            )
            ->when($acknowledgementStatus, function ($query, $acknowledgementStatus) {
                if ($acknowledgementStatus === 'with_acknowledgement') {
                    $query->whereHas('latestAcknowledgementItem');
                }

                if ($acknowledgementStatus === 'without_acknowledgement') {
                    $query->whereDoesntHave('latestAcknowledgementItem');
                }
            })
            ->when($roomId, function ($query, $roomId) {
                $query->whereHas('latestHistoryLocation', function ($q) use ($roomId) {
                    $q->where('room_id', $roomId);
                });
            })
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getItemWithInspections(int $id): InventoryItem
    {
        return InventoryItem::with([
            'itemClassification',
            'supplier',
            'latestHistoryLocation',
            'latestAcknowledgementItem.accountablePerson',
            'inspections' => fn($query) => $query->recent()->with(['assetCondition', 'inspectedByUser']),
        ])->findOrFail($id);
    }

    public function createInspections(
        int $assetConditionId,
        string $inspectionDate,
        ?string $remarks,
        array $itemIds,
        ?int $inspectedBy = null
    ): array {
        $createdInspections = [];
        $failedItems = [];

        try {
            foreach ($itemIds as $itemId) {
                try {
                    $item = InventoryItem::findOrFail($itemId);

                    $inspection = AssetInspection::create([
                        'inventory_item_id' => $itemId,
                        'asset_condition_id' => $assetConditionId,
                        'inspected_by' => $inspectedBy,
                        'inspection_date' => $inspectionDate,
                        'remarks' => $remarks,
                    ]);

                    $createdInspections[] = $inspection;
                } catch (\Exception $e) {
                    $failedItems[] = [
                        'item_id' => $itemId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return [
                'success' => count($createdInspections) > 0,
                'created' => $createdInspections,
                'failed' => $failedItems,
                'created_count' => count($createdInspections),
                'failed_count' => count($failedItems),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'created' => [],
                'failed' => $itemIds,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getItemInspectionHistory(int $itemId): Collection
    {
        return AssetInspection::where('inventory_item_id', $itemId)
            ->with(['assetCondition', 'inspectedByUser'])
            ->orderByDesc('inspection_date')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getLatestInspection(int $itemId): ?AssetInspection
    {
        $item = InventoryItem::with([
            'latestInspection.assetCondition',
            'latestInspection.inspectedByUser',
        ])->findOrFail($itemId);

        return $item->latestInspection;
    }

    public function getInspectionsByCondition(int $conditionId): Collection
    {
        return AssetInspection::where('asset_condition_id', $conditionId)
            ->with(['inventoryItem', 'assetCondition', 'inspectedByUser'])
            ->orderByDesc('inspection_date')
            ->get();
    }

    public function getInspectionsByDateRange(
        string $startDate,
        string $endDate
    ): Collection {
        return AssetInspection::whereBetween('inspection_date', [$startDate, $endDate])
            ->with(['inventoryItem', 'assetCondition', 'inspectedByUser'])
            ->orderByDesc('inspection_date')
            ->get();
    }

    public function getInspectionStats(): array
    {
        $totalInspections = AssetInspection::count();
        $inspectionsThisMonth = AssetInspection::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $conditionBreakdown = AssetInspection::with('assetCondition')
            ->selectRaw('asset_condition_id, COUNT(*) as count')
            ->groupBy('asset_condition_id')
            ->get()
            ->map(function ($item) {
                return [
                    'condition_name' => $item->assetCondition->condition_name,
                    'count' => $item->count,
                ];
            });

        return [
            'total_inspections' => $totalInspections,
            'this_month' => $inspectionsThisMonth,
            'by_condition' => $conditionBreakdown,
        ];
    }

    public function updateInspection(
        int $inspectionId,
        array $data
    ): AssetInspection {
        $inspection = AssetInspection::findOrFail($inspectionId);
        $inspection->update($data);

        return $inspection->fresh();
    }

    public function deleteInspection(int $inspectionId): bool
    {
        return AssetInspection::destroy($inspectionId) > 0;
    }

    public function deleteInspectionsByItems(array $itemIds): int
    {
        return AssetInspection::whereIn('inventory_item_id', $itemIds)->delete();
    }
}
