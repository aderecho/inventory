<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\AcknowledgementReceipt;

class ItemArchivingService
{
    public function filterAndPaginateArchive(
        ?string $search = null,
        ?string $costRange = null,
        int|string|null $assetConditionId = null,
        ?string $acknowledgementStatus = null,
        int|string|null $roomId = null,
        int $perPage = 10
    ) {
        return InventoryItem::onlyTrashed()
            ->with([
                'itemClassification',
                'supplier',
                'latestInspection.assetCondition',
                'latestAcknowledgementItem.accountablePerson',
                'latestAcknowledgementItem.acknowledgementReceipts',
                'latestHistoryLocation',
            ])
            ->when($search, fn($query, $search) => $query->search($search))
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
                filled($assetConditionId),
                fn($query) => $query->whereHas(
                    'latestInspection',
                    fn($q) => $q->where('asset_condition_id', $assetConditionId)
                )
            )
            ->when($acknowledgementStatus, function ($query, $acknowledgementStatus) {
                if ($acknowledgementStatus === 'with_acknowledgement') {
                    $query->whereHas('latestAcknowledgementItem');
                }

                if ($acknowledgementStatus === 'without_acknowledgement') {
                    $query->whereDoesntHave('latestAcknowledgementItem');
                }
            })
            ->when(
                filled($roomId),
                fn($query) => $query->whereHas(
                    'latestHistoryLocation',
                    fn($q) => $q->where('room_id', $roomId)
                )
            )
            ->orderBy('deleted_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function forceDelete($id)
    {
        $item = InventoryItem::onlyTrashed()
            ->with('acknowledgementItems')
            ->findOrFail($id);

        $receiptIds = $item->acknowledgementItems
            ->pluck('acknowledgement_id')
            ->filter()
            ->unique();

        // Neither acknowledgement model is soft deletable, so these are real
        // deletes: the item's link rows go first, then any receipt that is
        // left without a single item.
        $item->acknowledgementItems()->delete();

        foreach ($receiptIds as $receiptId) {
            $receipt = AcknowledgementReceipt::find($receiptId);

            if ($receipt && $receipt->acknowledgementItems()->count() === 0) {
                $receipt->delete();
            }
        }

        $item->forceDelete();
    }
}
