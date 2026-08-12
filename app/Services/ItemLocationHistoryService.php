<?php

namespace App\Services;

use App\Models\InventoryItem;

class ItemLocationHistoryService
{
    public function filterAndPaginateHistory(
        ?string $search = null,
        int|string|null $status = null,
        int|string|null $room = null,
        int $perPage = 10
    ) {
        return InventoryItem::with([
            'itemClassification',
            'supplier',
            'latestHistoryLocation',
            'historyLocations',
            'latestAcknowledgementItem.accountablePerson',
        ])
            ->when(
                $search,
                fn($query, $search) => $query->searchItemHistory($search)
            )
            ->when(
                !is_null($status),
                fn($query) => $query->where('status', $status)
            )
            ->when(
                $room,
                fn($query, $room) => $query->filterByRoom($room)
            )
            ->whereHas('historyLocations') // only items that have location history
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getItemWithHistory(int $id): InventoryItem
    {
        return InventoryItem::with([
            'itemClassification',
            'supplier',
            'latestHistoryLocation',
            'historyLocations',
            'latestAcknowledgementItem.accountablePerson',
            'acknowledgementHistory.accountablePerson',
            'acknowledgementHistory.acknowledgementReceipts',
        ])
            ->findOrFail($id);
    }
}