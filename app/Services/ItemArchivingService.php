<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\AcknowledgementReceipt;

class ItemArchivingService
{
    public function filterAndPaginateArchive(
        ?string $search = null,
        int|string|null $status = null,
        int $perPage = 10
    ) {
        return InventoryItem::onlyTrashed()
            ->with(['itemClassification', 'supplier'])
            ->when($search, fn($query, $search) => $query->search($search))
            ->when(
                filled($status),
                fn($query) => $query->where('status', (int) $status)
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
            ->unique();

        $item->acknowledgementItems()->forceDelete();

        foreach ($receiptIds as $receiptId) {
            $receipt = AcknowledgementReceipt::withTrashed()->find($receiptId);

            if ($receipt && $receipt->acknowledgementItems()->count() === 0) {
                $receipt->forceDelete();
            }
        }

        $item->forceDelete();
    }
}
