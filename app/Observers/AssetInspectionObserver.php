<?php

namespace App\Observers;

use App\Models\AssetCondition;
use App\Models\AssetInspection;
use App\Models\InventoryItem;

/**
 * Keeps the item's own status in step with what the latest inspection found, so
 * nothing downstream has to guess from a condition's name. Items that are out
 * on loan are left alone until they come back.
 */
class AssetInspectionObserver
{
    public function saved(AssetInspection $inspection): void
    {
        $this->syncItemStatus($inspection->inventory_item_id);
    }

    public function deleted(AssetInspection $inspection): void
    {
        $this->syncItemStatus($inspection->inventory_item_id);
    }

    private function syncItemStatus(int $itemId): void
    {
        $item = InventoryItem::find($itemId);

        if (! $item || $item->status === InventoryItem::STATUS_BORROWED) {
            return;
        }

        $latest = AssetInspection::with('assetCondition')
            ->where('inventory_item_id', $itemId)
            ->orderByDesc('inspection_date')
            ->orderByDesc('id')
            ->first();

        $disposition = $latest?->assetCondition?->disposition
            ?? AssetCondition::DISPOSITION_SERVICEABLE;

        $status = $disposition === AssetCondition::DISPOSITION_DISPOSED
            ? InventoryItem::STATUS_DISPOSED
            : InventoryItem::STATUS_AVAILABLE;

        if ($item->status !== $status) {
            $item->update(['status' => $status]);
        }
    }
}
