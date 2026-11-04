<?php

namespace App\Services;

use App\Models\AcknowledgementItem;
use App\Models\AcknowledgementReceipt;
use App\Models\AssetInspection;
use App\Models\InventoryItem;
use App\Models\ItemHistoryLocation;
use Illuminate\Support\Facades\DB;

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

    /**
     * Permanently removes an archived item and everything recorded against it:
     * its acknowledgement lines (and their transfer types), inspections and
     * location history, then any receipt left without a single item. A receipt
     * that still lists other items stays, since it is their record too.
     * All of it happens in one transaction, so a failure leaves nothing half gone.
     */
    public function forceDelete($id): void
    {
        DB::transaction(function () use ($id) {
            $item = InventoryItem::onlyTrashed()->findOrFail($id);

            $acknowledgementItemIds = AcknowledgementItem::where('inventory_item_id', $item->id)
                ->pluck('id');

            $receiptIds = AcknowledgementItem::whereIn('id', $acknowledgementItemIds)
                ->whereNotNull('acknowledgement_id')
                ->distinct()
                ->pluck('acknowledgement_id');

            DB::table('acknowledgement_item_transfer_type')
                ->whereIn('acknowledgement_item_id', $acknowledgementItemIds)
                ->delete();

            AcknowledgementItem::whereIn('id', $acknowledgementItemIds)->delete();

            AcknowledgementReceipt::whereIn('id', $receiptIds)
                ->whereDoesntHave('acknowledgementItems')
                ->delete();

            AssetInspection::where('inventory_item_id', $item->id)->delete();
            ItemHistoryLocation::where('inventory_item_id', $item->id)->delete();

            $item->forceDelete();
        });
    }
}
